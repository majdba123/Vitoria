<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing vendor RBAC primitives (roles/permissions/role_permissions,
 * see 2026_08_13_100000_create_rbac_and_vendor_members_tables) to marketplace
 * employees (stakeholder review #24).
 *
 * Employees are not scoped to a vendor, so this adds a simple `employee_roles`
 * pivot rather than reusing `vendor_members` (which is keyed by vendor_id).
 * The same `roles`/`permissions` tables are reused — a role is just a named
 * bundle of permission keys regardless of which actor type holds it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        $this->seedEmployeePermissionsAndRoles();
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_roles');

        DB::table('role_permissions')->whereIn('role_id', function ($query) {
            $query->select('id')->from('roles')->whereIn('key', [Role::KEY_CATALOG_MODERATOR, Role::KEY_ORDER_REVIEWER]);
        })->delete();
        DB::table('roles')->whereIn('key', [Role::KEY_CATALOG_MODERATOR, Role::KEY_ORDER_REVIEWER])->delete();
        DB::table('permissions')->where('key', 'products.moderate')->delete();
    }

    /**
     * Production only runs `migrate --force` (see the RBAC migration's
     * class doc), so the roles this feature needs must be created here, and
     * every existing employee must be backfilled with a role that preserves
     * their current capability — before this migration, every employee could
     * already view, edit, and approve/reject any product, so the safe
     * backfill is `catalog_moderator`, not the more restrictive `order_reviewer`.
     */
    private function seedEmployeePermissionsAndRoles(): void
    {
        $now = now();

        $moderatePermissionId = DB::table('permissions')->insertGetId([
            'key' => 'products.moderate',
            'description' => 'Approve or reject vendor product submissions',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productsViewId = DB::table('permissions')->where('key', 'products.view')->value('id');
        $ordersViewId = DB::table('permissions')->where('key', 'orders.view')->value('id');

        $catalogModeratorId = DB::table('roles')->insertGetId([
            'key' => Role::KEY_CATALOG_MODERATOR,
            'name_en' => 'Catalog Moderator',
            'name_ar' => 'مشرف الكتالوج',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderReviewerId = DB::table('roles')->insertGetId([
            'key' => Role::KEY_ORDER_REVIEWER,
            'name_en' => 'Order Reviewer',
            'name_ar' => 'مراجع الطلبات',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rolePermissions = [];
        foreach ([$productsViewId, $moderatePermissionId] as $permissionId) {
            $rolePermissions[] = ['role_id' => $catalogModeratorId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_filter([$productsViewId, $ordersViewId]) as $permissionId) {
            $rolePermissions[] = ['role_id' => $orderReviewerId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('role_permissions')->insert($rolePermissions);

        $existingEmployeeIds = DB::table('users')->where('type', User::TYPE_EMPLOYEE)->pluck('id');
        $backfill = $existingEmployeeIds->map(fn ($userId) => [
            'user_id' => $userId,
            'role_id' => $catalogModeratorId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (! empty($backfill)) {
            DB::table('employee_roles')->insert($backfill);
        }
    }
};
