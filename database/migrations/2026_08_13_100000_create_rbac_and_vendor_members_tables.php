<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendor staff and the RBAC primitives behind it (spec §22, §23).
     *
     * Decision D3 deferred a `roles`/`permissions`/`role_permissions` layer
     * until something needed genuinely different permission sets *within*
     * one actor type. Vendor staff is exactly that trigger — five fixed,
     * non-overlapping user types (admin/vendor/employee/syndicate/customer)
     * still do not need it, so this stays scoped to vendors.
     *
     * `vendors.user_id` (the owner) is untouched: the owner is not a
     * `vendor_members` row and is not looked up through the permissions
     * table at all — `User::hasVendorPermission()` short-circuits to true
     * for the owner unconditionally, so an owner can never be locked out of
     * their own vendor by a missing or misconfigured role. `vendor_members`
     * exists only for staff added on top of that owner.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('name_en');
            $table->string('name_ar');
            // Seeded roles cannot be edited or deleted from the admin API —
            // there is no requirement yet for vendor-defined custom roles.
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('vendor_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained();
            $table->string('status', 20)->default('active');
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            // One membership row per (vendor, user) — removing and
            // re-inviting reactivates the same row rather than duplicating.
            $table->unique(['vendor_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        $this->seedPermissionsAndRoles();
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_members');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }

    /**
     * Production only runs `migrate --force`, never `db:seed` (see
     * .github/workflows/deploy.yml), so — as with the shipping migration —
     * the roles and permissions a live database needs must be created here.
     */
    private function seedPermissionsAndRoles(): void
    {
        $now = now();

        $permissionKeys = [
            'products.view', 'products.manage',
            'orders.view', 'orders.update', 'orders.cancel',
            'returns.view', 'returns.review', 'returns.refund',
            'refunds.view',
            'shipments.view', 'shipments.manage',
            'invoices.view',
            'ledger.view', 'settlements.view',
            'staff.manage',
            'profile.manage',
        ];

        $permissionIds = [];
        foreach ($permissionKeys as $key) {
            $permissionIds[$key] = DB::table('permissions')->insertGetId([
                'key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Owner is seeded for completeness (e.g. listing "who has which
        // permissions") but is never assigned via `vendor_members` — the
        // owner bypass in User::hasVendorPermission() does not consult it.
        $rolePermissions = [
            Role::KEY_OWNER => $permissionKeys,
            Role::KEY_MANAGER => $permissionKeys,
            Role::KEY_CATALOG_MANAGER => [
                'products.view', 'products.manage',
                'orders.view', 'returns.view', 'shipments.view', 'invoices.view',
            ],
            Role::KEY_ORDER_MANAGER => [
                'products.view',
                'orders.view', 'orders.update', 'orders.cancel',
                'returns.view', 'returns.review',
                'shipments.view', 'shipments.manage',
                'invoices.view',
            ],
            Role::KEY_FINANCE => [
                'products.view',
                'orders.view',
                'returns.view', 'returns.refund',
                'refunds.view', 'invoices.view', 'ledger.view', 'settlements.view',
            ],
            Role::KEY_VIEWER => [
                'products.view', 'orders.view', 'returns.view', 'shipments.view', 'invoices.view',
            ],
        ];

        $roleNames = [
            Role::KEY_OWNER => ['Owner', 'المالك'],
            Role::KEY_MANAGER => ['Manager', 'مدير'],
            Role::KEY_CATALOG_MANAGER => ['Catalog Manager', 'مدير الكتالوج'],
            Role::KEY_ORDER_MANAGER => ['Order Manager', 'مدير الطلبات'],
            Role::KEY_FINANCE => ['Finance', 'المالية'],
            Role::KEY_VIEWER => ['Viewer', 'مشاهد'],
        ];

        foreach ($rolePermissions as $roleKey => $keys) {
            $roleId = DB::table('roles')->insertGetId([
                'key' => $roleKey,
                'name_en' => $roleNames[$roleKey][0],
                'name_ar' => $roleNames[$roleKey][1],
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rows = array_map(fn (string $key) => [
                'role_id' => $roleId,
                'permission_id' => $permissionIds[$key],
                'created_at' => $now,
                'updated_at' => $now,
            ], $keys);

            DB::table('role_permissions')->insert($rows);
        }
    }
};
