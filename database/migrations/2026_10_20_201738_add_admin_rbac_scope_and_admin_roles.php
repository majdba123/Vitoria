<?php

use App\Models\Role;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform back-office RBAC, built on the existing roles/permissions/
 * role_permissions primitives (2026_08_13 vendor staff, 2026_08_19 employees).
 *
 * - `roles.scope` makes the actor scope of a role explicit (vendor, employee,
 *   admin) so a vendor role can never be honoured as a platform-admin role
 *   merely because both live in the same table.
 * - `admin_roles` is the admin counterpart of `employee_roles`.
 * - `super_admin` is the only unrestricted admin capability. Every admin that
 *   exists when this runs is backfilled with it, so no current administrator
 *   loses access; admins created afterwards start with no back-office
 *   permissions until a role is explicitly assigned.
 *
 * Production only runs `migrate --force` (see .github/workflows/deploy.yml),
 * so the catalog rows and the backfill must happen here rather than a seeder.
 * MySQL commits DDL implicitly, so every step tolerates having already run:
 * a re-run after a partial failure completes the backfill instead of crashing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'scope')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('scope', 20)->default(Role::SCOPE_VENDOR)->after('key');
                $table->index('scope');
            });
        }

        DB::table('roles')
            ->whereIn('key', [Role::KEY_CATALOG_MODERATOR, Role::KEY_ORDER_REVIEWER])
            ->update(['scope' => Role::SCOPE_EMPLOYEE]);

        if (! Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                // No cascade: a role still assigned to someone cannot be deleted.
                $table->foreignId('role_id')->constrained();
                $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'role_id']);
                $table->index('role_id');
            });
        }

        $this->seedCatalogAndBackfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');

        $adminRoleIds = DB::table('roles')->where('scope', Role::SCOPE_ADMIN)->pluck('id');
        DB::table('role_permissions')->whereIn('role_id', $adminRoleIds)->delete();
        DB::table('roles')->whereIn('id', $adminRoleIds)->delete();

        // Only drop admin-only keys that no surviving vendor/employee role uses.
        $stillUsed = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->pluck('permissions.key')
            ->all();
        DB::table('permissions')
            ->whereIn('key', AdminPermissions::all())
            ->whereNotIn('key', $stillUsed)
            ->delete();

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['scope']);
            $table->dropColumn('scope');
        });
    }

    private function seedCatalogAndBackfill(): void
    {
        $now = now();

        $existing = DB::table('permissions')->pluck('id', 'key')->all();
        $permissionIds = [];

        foreach (AdminPermissions::all() as $key) {
            $permissionIds[$key] = $existing[$key] ?? DB::table('permissions')->insertGetId([
                'key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $existingSuperAdmin = DB::table('roles')->where('key', Role::KEY_SUPER_ADMIN)->first();

        // Never silently promote an unrelated role that happens to use the key.
        if ($existingSuperAdmin && $existingSuperAdmin->scope !== Role::SCOPE_ADMIN) {
            throw new RuntimeException('A non-admin role already uses the key "super_admin"; rename it before migrating.');
        }

        $superAdminId = $existingSuperAdmin->id ?? DB::table('roles')->insertGetId([
            'key' => Role::KEY_SUPER_ADMIN,
            'scope' => Role::SCOPE_ADMIN,
            'name_en' => 'Super Administrator',
            'name_ar' => 'المدير العام',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Granted rows are informational — the super_admin bypass in
        // User::adminPermissionKeys() does not consult them.
        DB::table('role_permissions')->insertOrIgnore(array_map(fn (int $permissionId) => [
            'role_id' => $superAdminId,
            'permission_id' => $permissionId,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_values($permissionIds)));

        $existingAdminIds = DB::table('users')->where('type', User::TYPE_ADMIN)->pluck('id');

        if ($existingAdminIds->isNotEmpty()) {
            DB::table('admin_roles')->insertOrIgnore($existingAdminIds->map(fn ($userId) => [
                'user_id' => $userId,
                'role_id' => $superAdminId,
                'assigned_by_user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }
    }
};
