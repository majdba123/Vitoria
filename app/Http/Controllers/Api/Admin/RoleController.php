<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRoleRequest;
use App\Http\Requests\Admin\UpdateAdminRoleRequest;
use App\Models\Role;
use App\Services\Admin\AdminAccessException;
use App\Services\Admin\AdminRbacService;
use App\Support\AdminPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Platform-admin role management. Only admin-scoped roles are reachable
 * here — vendor and employee roles live in the same table but are never
 * listed, shown, or editable through this controller.
 */
class RoleController extends Controller
{
    public function __construct(public AdminRbacService $rbac) {}

    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->admin()
            ->with('permissions:id,key')
            ->withCount('adminUsers')
            ->orderByDesc('is_system')
            ->orderBy('name_en')
            ->get();

        return response()->json([
            'data' => $roles->map(fn (Role $role) => $this->present($role, $request))->values(),
        ]);
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        $this->ensureAdminScope($role);
        $role->load('permissions:id,key')->loadCount('adminUsers');

        return response()->json(['data' => $this->present($role, $request)]);
    }

    /**
     * The permission catalog, grouped for the role editor.
     */
    public function permissions(Request $request): JsonResponse
    {
        $actorKeys = $request->user()->adminPermissionKeys();

        return response()->json([
            'data' => [
                'groups' => collect(AdminPermissions::GROUPS)->map(fn (array $keys, string $group) => [
                    'key' => $group,
                    'permissions' => array_map(fn (string $key) => [
                        'key' => $key,
                        'sensitive' => in_array($key, AdminPermissions::SENSITIVE, true),
                        'grantable' => in_array($key, $actorKeys, true),
                    ], $keys),
                ])->values(),
            ],
        ]);
    }

    public function store(StoreAdminRoleRequest $request): JsonResponse
    {
        try {
            $role = $this->rbac->createRole($request->user(), $request->validated());
        } catch (AdminAccessException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        return response()->json([
            'message' => __('admin.rbac_role_created'),
            'data' => $this->present($role->loadCount('adminUsers'), $request),
        ], 201);
    }

    public function update(UpdateAdminRoleRequest $request, Role $role): JsonResponse
    {
        $this->ensureAdminScope($role);

        try {
            $role = $this->rbac->updateRole($request->user(), $role, $request->validated());
        } catch (AdminAccessException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        return response()->json([
            'message' => __('admin.rbac_role_updated'),
            'data' => $this->present($role->loadCount('adminUsers'), $request),
        ]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->ensureAdminScope($role);

        try {
            $this->rbac->deleteRole($request->user(), $role);
        } catch (AdminAccessException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        return response()->json(['message' => __('admin.rbac_role_deleted')]);
    }

    private function ensureAdminScope(Role $role): void
    {
        abort_unless($role->scope === Role::SCOPE_ADMIN, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Role $role, Request $request): array
    {
        $keys = $role->isSuperAdminRole()
            ? AdminPermissions::all()
            : array_values(array_intersect(AdminPermissions::all(), $role->permissions->pluck('key')->all()));
        $actor = $request->user();
        $actorHoldsAll = $actor->isSuperAdmin() || array_diff($keys, $actor->adminPermissionKeys()) === [];
        $locked = $role->is_system || $role->isSuperAdminRole();

        return [
            'id' => $role->id,
            'key' => $role->key,
            'name_en' => $role->name_en,
            'name_ar' => $role->name_ar,
            'name' => app()->getLocale() === 'ar' ? $role->name_ar : $role->name_en,
            'is_system' => $locked,
            'is_super_admin' => $role->isSuperAdminRole(),
            'permissions' => $keys,
            'permissions_count' => count($keys),
            'users_count' => (int) ($role->admin_users_count ?? 0),
            'can_edit' => ! $locked && $actorHoldsAll,
            'can_delete' => ! $locked && $actorHoldsAll && (int) ($role->admin_users_count ?? 0) === 0,
            'can_assign' => $actorHoldsAll && (! $role->isSuperAdminRole() || $actor->isSuperAdmin()),
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at,
        ];
    }
}
