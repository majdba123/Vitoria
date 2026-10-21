<?php

namespace App\Services\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AdminPermissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every write to the platform-admin RBAC goes through here: role CRUD, role
 * assignment, and the guards UserController applies before touching another
 * administrator's account. Each change runs in one transaction together with
 * its audit row, so a change is never committed without its audit record and
 * an audit row never describes a change that rolled back.
 *
 * Escalation rules (enforced server-side, whatever the UI shows):
 *  - A delegated administrator can only create, edit, delete, grant or revoke
 *    roles whose permissions are a subset of their own effective permissions.
 *  - Nobody changes their own admin roles.
 *  - Only a super administrator grants or revokes `super_admin`, or manages a
 *    super administrator's account.
 *  - The last super administrator can never lose the role, be demoted out of
 *    the admin type, or be deleted.
 *  - System roles (super_admin, vendor and employee roles) are immutable here.
 */
class AdminRbacService
{
    public function __construct(private AuditLogService $auditLog) {}

    /**
     * @param  array{key: string, name_en: string, name_ar: string, permissions: list<string>}  $data
     */
    public function createRole(User $actor, array $data): Role
    {
        $keys = $this->normalizePermissionKeys($data['permissions']);
        $this->assertActorHoldsAll($actor, $keys);

        return DB::transaction(function () use ($actor, $data, $keys) {
            $role = Role::query()->create([
                'key' => $data['key'],
                'scope' => Role::SCOPE_ADMIN,
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'is_system' => false,
            ]);
            $role->permissions()->sync($this->permissionIds($keys));

            $this->auditLog->record($actor, 'role.created', 'Role', $role->id, null, [
                'key' => $role->key,
                'name_en' => $role->name_en,
                'name_ar' => $role->name_ar,
                'permissions' => $keys,
            ]);

            return $role->load('permissions');
        });
    }

    /**
     * @param  array{name_en?: string, name_ar?: string, permissions?: list<string>}  $data
     */
    public function updateRole(User $actor, Role $role, array $data): Role
    {
        $this->assertEditableRole($role);
        $currentKeys = $this->roleKeys($role);
        $this->assertActorHoldsAll($actor, $currentKeys);

        $newKeys = array_key_exists('permissions', $data)
            ? $this->normalizePermissionKeys($data['permissions'])
            : $currentKeys;
        $this->assertActorHoldsAll($actor, $newKeys);

        return DB::transaction(function () use ($actor, $role, $data, $currentKeys, $newKeys) {
            $old = ['name_en' => $role->name_en, 'name_ar' => $role->name_ar, 'permissions' => $currentKeys];

            $role->fill(array_intersect_key($data, array_flip(['name_en', 'name_ar'])))->save();
            $role->permissions()->sync($this->permissionIds($newKeys));

            $new = ['name_en' => $role->name_en, 'name_ar' => $role->name_ar, 'permissions' => $newKeys];

            if ($old !== $new) {
                $this->auditLog->record($actor, 'role.updated', 'Role', $role->id, $old + [
                    'permissions_removed' => array_values(array_diff($currentKeys, $newKeys)),
                ], $new + [
                    'permissions_added' => array_values(array_diff($newKeys, $currentKeys)),
                ]);
            }

            return $role->load('permissions');
        });
    }

    public function deleteRole(User $actor, Role $role): void
    {
        $this->assertEditableRole($role);
        $keys = $this->roleKeys($role);
        $this->assertActorHoldsAll($actor, $keys);

        DB::transaction(function () use ($actor, $role, $keys) {
            if ($role->adminUsers()->lockForUpdate()->exists()) {
                throw new AdminAccessException(__('admin.rbac_error_role_in_use'), 422);
            }

            $snapshot = ['key' => $role->key, 'name_en' => $role->name_en, 'name_ar' => $role->name_ar, 'permissions' => $keys];
            $roleId = $role->id;

            $role->permissions()->detach();
            $role->delete();

            $this->auditLog->record($actor, 'role.deleted', 'Role', $roleId, $snapshot, null);
        });
    }

    /**
     * Replace the target administrator's admin roles with `$roleIds`.
     *
     * @param  list<int>  $roleIds
     */
    public function syncAdminRoles(User $actor, User $target, array $roleIds): User
    {
        if (! $target->isAdmin()) {
            throw new AdminAccessException(__('admin.rbac_error_target_not_admin'), 422);
        }

        if ($actor->is($target)) {
            throw new AdminAccessException(__('admin.rbac_error_self_assignment'));
        }

        $this->assertCanManageAccount($actor, $target);

        $requested = Role::query()->admin()->whereIn('id', $roleIds)->with('permissions:id,key')->get();

        if ($requested->count() !== count(array_unique($roleIds))) {
            throw new AdminAccessException(__('admin.rbac_error_invalid_role'), 422);
        }

        return DB::transaction(function () use ($actor, $target, $requested) {
            // Serialize concurrent changes to super-admin holders so two
            // requests can never each remove "another" last super admin.
            $superAdminHolderIds = $this->lockSuperAdmins();

            $current = $target->adminRoles()->where('roles.scope', Role::SCOPE_ADMIN)->with('permissions:id,key')->get();
            $added = $requested->whereNotIn('id', $current->pluck('id'));
            $removed = $current->whereNotIn('id', $requested->pluck('id'));

            foreach ($added->merge($removed) as $role) {
                if ($role->isSuperAdminRole() && ! $actor->isSuperAdmin()) {
                    throw new AdminAccessException(__('admin.rbac_error_super_admin_only'));
                }

                $this->assertActorHoldsAll($actor, $role->permissions->pluck('key')->all());
            }

            if ($removed->contains(fn (Role $role) => $role->isSuperAdminRole())) {
                $this->assertNotLastSuperAdmin($target, $superAdminHolderIds);
            }

            $target->adminRoles()->sync(
                $requested->mapWithKeys(fn (Role $role) => [
                    $role->id => ['assigned_by_user_id' => $current->contains('id', $role->id)
                        ? $current->firstWhere('id', $role->id)->pivot->assigned_by_user_id
                        : $actor->id],
                ])->all(),
            );
            $target->forgetAdminAccess();

            if ($added->isNotEmpty() || $removed->isNotEmpty()) {
                $this->auditLog->record($actor, 'user.admin_roles_changed', 'User', $target->id, [
                    'role_keys' => $current->pluck('key')->values()->all(),
                ], [
                    'role_keys' => $requested->pluck('key')->values()->all(),
                    'added' => $added->pluck('key')->values()->all(),
                    'removed' => $removed->pluck('key')->values()->all(),
                ]);
            }

            return $target;
        });
    }

    /**
     * Refuse when `$actor` may not modify or delete `$target`'s account.
     * Non-admin targets are governed by the route permission alone.
     */
    public function assertCanManageAccount(User $actor, User $target): void
    {
        if (! $target->isAdmin() || $actor->isSuperAdmin()) {
            return;
        }

        if ($target->isSuperAdmin()) {
            throw new AdminAccessException(__('admin.rbac_error_super_admin_only'));
        }

        $this->assertActorHoldsAll($actor, $target->adminPermissionKeys());
    }

    /**
     * Guards for the generic user update/delete endpoints. Call inside the
     * transaction performing the change.
     *
     * @param  array<string, mixed>  $changes
     */
    public function assertCanChangeAccount(User $actor, User $target, array $changes): void
    {
        $newType = array_key_exists('type', $changes) && $changes['type'] !== null ? (int) $changes['type'] : $target->type;
        $typeChanges = $newType !== $target->type;

        if ($actor->is($target) && $typeChanges) {
            throw new AdminAccessException(__('admin.rbac_error_self_type_change'));
        }

        $this->assertCanManageAccount($actor, $target);

        if ($typeChanges && $newType === User::TYPE_ADMIN) {
            $this->assertMayCreateAdmins($actor);
        }

        if ($typeChanges && $target->isAdmin()) {
            $this->assertNotLastSuperAdmin($target, $this->lockSuperAdmins());
        }
    }

    public function assertCanDeleteAccount(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw new AdminAccessException(__('admin.rbac_error_self_delete'));
        }

        $this->assertCanManageAccount($actor, $target);

        if ($target->isAdmin()) {
            $this->assertNotLastSuperAdmin($target, $this->lockSuperAdmins());
        }
    }

    /**
     * Minting an admin-type account (even one with no roles yet) is reserved
     * for administrators who may assign roles.
     */
    public function assertMayCreateAdmins(User $actor): void
    {
        if (! $actor->hasAdminPermission('roles.assign')) {
            throw new AdminAccessException(__('admin.rbac_error_create_admin'));
        }
    }

    /**
     * Drop every admin role from an account that is no longer admin-typed,
     * so re-promoting it later never silently restores old privileges.
     */
    public function revokeAllAdminRoles(User $actor, User $target): void
    {
        $keys = $target->adminRoles()->pluck('key')->values()->all();

        if ($keys === []) {
            return;
        }

        $target->adminRoles()->detach();
        $target->forgetAdminAccess();

        $this->auditLog->record($actor, 'user.admin_roles_changed', 'User', $target->id, ['role_keys' => $keys], [
            'role_keys' => [],
            'added' => [],
            'removed' => $keys,
            'reason' => 'account_type_changed',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function effectivePermissions(User $user): array
    {
        $roles = $user->adminRoles()->where('roles.scope', Role::SCOPE_ADMIN)->with('permissions:id,key')->get();
        $isSuper = $user->isSuperAdmin();
        $effective = $user->adminPermissionKeys();

        $sources = [];
        foreach ($effective as $key) {
            $sources[$key] = $isSuper
                ? [Role::KEY_SUPER_ADMIN]
                : $roles->filter(fn (Role $role) => $role->permissions->contains('key', $key))->pluck('key')->values()->all();
        }

        return [
            'is_admin' => $user->isAdmin(),
            'is_super_admin' => $isSuper,
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => app()->getLocale() === 'ar' ? $role->name_ar : $role->name_en,
                'assigned_by_user_id' => $role->pivot->assigned_by_user_id,
                'assigned_at' => $role->pivot->created_at,
            ])->values()->all(),
            'permissions' => $effective,
            'sources' => $sources,
        ];
    }

    private function assertEditableRole(Role $role): void
    {
        if ($role->scope !== Role::SCOPE_ADMIN || $role->is_system || $role->isSuperAdminRole()) {
            throw new AdminAccessException(__('admin.rbac_error_system_role'));
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function assertActorHoldsAll(User $actor, array $keys): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if (array_diff($keys, $actor->adminPermissionKeys()) !== []) {
            throw new AdminAccessException(__('admin.rbac_error_escalation'));
        }
    }

    /**
     * Decided only from locking reads taken after lockSuperAdmins(): a
     * locking read sees the latest committed rows, whereas a plain read (or
     * the request-memoized isSuperAdmin()) could reflect a snapshot from
     * before a concurrent request removed another super administrator.
     *
     * @param  Collection<int, int>  $superAdminHolderIds
     */
    private function assertNotLastSuperAdmin(User $target, Collection $superAdminHolderIds): void
    {
        if (! $superAdminHolderIds->contains($target->id)) {
            return;
        }

        $otherSuperAdmins = User::query()
            ->whereIn('id', $superAdminHolderIds)
            ->where('type', User::TYPE_ADMIN)
            ->whereKeyNot($target->id)
            ->lockForUpdate()
            ->count();

        if ($otherSuperAdmins === 0) {
            throw new AdminAccessException(__('admin.rbac_error_last_super_admin'), 422);
        }
    }

    /**
     * @return Collection<int, int> ids of the current super-admin holders, row-locked
     */
    private function lockSuperAdmins(): Collection
    {
        return DB::table('admin_roles')
            ->where('role_id', $this->superAdminRoleId())
            ->lockForUpdate()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id);
    }

    private function superAdminRoleId(): ?int
    {
        return Role::query()->where('key', Role::KEY_SUPER_ADMIN)->value('id');
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function normalizePermissionKeys(array $keys): array
    {
        $keys = array_values(array_unique($keys));

        foreach ($keys as $key) {
            if (! AdminPermissions::exists($key)) {
                throw new AdminAccessException(__('admin.rbac_error_invalid_permission'), 422);
            }
        }

        return array_values(array_intersect(AdminPermissions::all(), $keys));
    }

    /**
     * @return list<string>
     */
    private function roleKeys(Role $role): array
    {
        return array_values(array_intersect(AdminPermissions::all(), $role->permissions()->pluck('key')->all()));
    }

    /**
     * @param  list<string>  $keys
     * @return Collection<int, int>
     */
    private function permissionIds(array $keys): Collection
    {
        $existing = Permission::query()->whereIn('key', $keys)->pluck('id', 'key');

        foreach (array_diff($keys, $existing->keys()->all()) as $missing) {
            $existing[$missing] = Permission::query()->create(['key' => $missing])->id;
        }

        return $existing->values();
    }
}
