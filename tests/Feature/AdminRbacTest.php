<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminPermissions;
use App\Support\AdminRoutePermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

/**
 * Native back-office RBAC: deny-by-default route enforcement, the escalation
 * rules around role management, and the single explicit super_admin bypass.
 */
function rbacRole(array $permissionKeys, ?string $key = null): Role
{
    $role = Role::query()->create([
        'key' => $key ?? 'role_'.Str::lower(Str::random(8)),
        'scope' => Role::SCOPE_ADMIN,
        'name_en' => 'Test role',
        'name_ar' => 'دور تجريبي',
        'is_system' => false,
    ]);
    $role->permissions()->sync(Permission::query()->whereIn('key', $permissionKeys)->pluck('id'));

    return $role;
}

function rbacAdminWith(array $permissionKeys = []): User
{
    $user = User::factory()->delegatedAdmin()->create();

    if ($permissionKeys !== []) {
        $user->adminRoles()->attach(rbacRole($permissionKeys)->id);
    }

    return $user;
}

function rbacSuperAdmin(): User
{
    return User::factory()->create(['type' => User::TYPE_ADMIN]);
}

it('maps every admin web and api route to a known permission', function () {
    $unmapped = [];
    $unknown = [];

    foreach (Route::getRoutes() as $route) {
        $name = $route->getName();
        $isAdminRoute = $name && (str_starts_with($name, 'admin.') || str_starts_with($name, 'api.admin.'));

        if (! $isAdminRoute) {
            continue;
        }

        $permission = AdminRoutePermissions::for($name);

        if ($permission === null) {
            $unmapped[] = $name;
        } elseif ($permission !== AdminRoutePermissions::OPEN) {
            foreach (explode('|', $permission) as $key) {
                if (! AdminPermissions::exists($key)) {
                    $unknown[] = "{$name} => {$key}";
                }
            }
        }
    }

    expect($unmapped)->toBe([])->and($unknown)->toBe([]);
});

it('applies the permission middleware to every admin route group', function () {
    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();

        if (str_starts_with($name, 'admin.') || str_starts_with($name, 'api.admin.')) {
            expect(in_array('admin.permission', $route->gatherMiddleware(), true))->toBeTrue("{$name} is missing admin.permission");
        }
    }
});

it('denies a delegated admin without the permission and records the denial', function () {
    $admin = rbacAdminWith();
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/users')->assertForbidden();

    $denial = AuditLog::query()->where('action', 'authorization.denied')->latest('id')->firstOrFail();
    expect($denial->actor_user_id)->toBe($admin->id)
        ->and($denial->new_values['route'])->toBe('api.admin.users.index')
        ->and($denial->new_values['required_permission'])->toBe('users.view');
});

it('renders the forbidden page for a denied admin web page', function () {
    $this->actingAs(rbacAdminWith())
        ->get('/admin/users')
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Forbidden')->where('requiredPermission', 'users.view'));
});

it('grants access once the permission is held and revokes it immediately when removed', function () {
    $admin = rbacAdminWith(['users.view']);
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/users')->assertOk();
    $this->getJson('/api/admin/orders')->assertForbidden();

    $admin->adminRoles()->detach();
    $admin->forgetAdminAccess();

    $this->getJson('/api/admin/users')->assertForbidden();
});

it('keeps open admin routes reachable without any permission', function () {
    Sanctum::actingAs(rbacAdminWith());

    $this->getJson('/api/admin/categories')->assertOk();
});

it('never lets a non-admin account through, whatever roles it holds', function () {
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    $customer->adminRoles()->attach(Role::query()->where('key', Role::KEY_SUPER_ADMIN)->value('id'));
    Sanctum::actingAs($customer);

    expect($customer->adminPermissionKeys())->toBe([])
        ->and($customer->isSuperAdmin())->toBeFalse();
    $this->getJson('/api/admin/users')->assertForbidden();
});

it('does not inherit admin permissions from vendor or employee scoped roles', function () {
    $admin = rbacAdminWith();
    $vendorRole = Role::query()->create(['key' => 'vendor_lookalike', 'scope' => Role::SCOPE_VENDOR, 'name_en' => 'V', 'name_ar' => 'V', 'is_system' => false]);
    $vendorRole->permissions()->sync(Permission::query()->whereIn('key', ['users.view'])->pluck('id'));
    DB::table('admin_roles')->insert(['user_id' => $admin->id, 'role_id' => $vendorRole->id, 'created_at' => now(), 'updated_at' => now()]);

    expect($admin->fresh()->hasAdminPermission('users.view'))->toBeFalse();
});

it('shares the effective permissions with the admin frontend', function () {
    $admin = rbacAdminWith(['dashboard.view', 'orders.view']);

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.is_super_admin', false)
            ->where('auth.permissions', fn ($keys) => collect($keys)->sort()->values()->all() === ['dashboard.view', 'orders.view']));
});

it('lets a role manager create a role within their own permissions and audits it', function () {
    $manager = rbacAdminWith(['roles.view', 'roles.manage', 'orders.view']);
    Sanctum::actingAs($manager);

    $this->postJson('/api/admin/roles', [
        'key' => 'order_viewer',
        'name_en' => 'Order viewer',
        'name_ar' => 'مشاهد الطلبات',
        'permissions' => ['orders.view'],
    ])->assertCreated()->assertJsonPath('data.permissions', ['orders.view']);

    $log = AuditLog::query()->where('action', 'role.created')->firstOrFail();
    expect($log->actor_user_id)->toBe($manager->id)
        ->and($log->new_values['permissions'])->toBe(['orders.view']);
});

it('refuses to grant a permission the actor does not hold', function () {
    Sanctum::actingAs(rbacAdminWith(['roles.view', 'roles.manage']));

    $this->postJson('/api/admin/roles', [
        'key' => 'escalated',
        'name_en' => 'Escalated',
        'name_ar' => 'مصعد',
        'permissions' => ['refunds.process'],
    ])->assertForbidden();

    expect(Role::query()->where('key', 'escalated')->exists())->toBeFalse();
});

it('refuses to edit a role holding permissions the actor lacks', function () {
    $target = rbacRole(['refunds.process']);
    Sanctum::actingAs(rbacAdminWith(['roles.view', 'roles.manage']));

    $this->putJson("/api/admin/roles/{$target->id}", ['permissions' => []])->assertForbidden();
    expect($target->permissions()->pluck('key')->all())->toBe(['refunds.process']);
});

it('rejects unknown permission keys', function () {
    Sanctum::actingAs(rbacSuperAdmin());

    $this->postJson('/api/admin/roles', [
        'key' => 'bogus',
        'name_en' => 'Bogus',
        'name_ar' => 'Bogus',
        'permissions' => ['everything.allow'],
    ])->assertUnprocessable();
});

it('keeps system roles immutable', function () {
    $super = Role::query()->where('key', Role::KEY_SUPER_ADMIN)->firstOrFail();
    Sanctum::actingAs(rbacSuperAdmin());

    $this->putJson("/api/admin/roles/{$super->id}", ['name_en' => 'Renamed'])->assertForbidden();
    $this->deleteJson("/api/admin/roles/{$super->id}")->assertForbidden();
    expect($super->fresh()->name_en)->toBe('Super Administrator');
});

it('refuses to delete a role still assigned to an administrator', function () {
    $role = rbacRole(['orders.view']);
    rbacAdminWith()->adminRoles()->attach($role->id);
    Sanctum::actingAs(rbacSuperAdmin());

    $this->deleteJson("/api/admin/roles/{$role->id}")->assertUnprocessable();
    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue();
});

it('hides vendor and employee roles from the admin role endpoints', function () {
    $employeeRole = Role::query()->create(['key' => 'employee_only', 'scope' => Role::SCOPE_EMPLOYEE, 'name_en' => 'E', 'name_ar' => 'E', 'is_system' => false]);
    Sanctum::actingAs(rbacSuperAdmin());

    $this->getJson("/api/admin/roles/{$employeeRole->id}")->assertNotFound();
    expect(collect($this->getJson('/api/admin/roles')->json('data'))->pluck('key'))->not->toContain('employee_only');
});

it('assigns admin roles, audits the change and blocks self-assignment', function () {
    $assigner = rbacAdminWith(['roles.view', 'roles.assign', 'orders.view', 'users.view']);
    $target = rbacAdminWith();
    $role = rbacRole(['orders.view']);
    Sanctum::actingAs($assigner);

    $this->putJson("/api/admin/users/{$target->id}/admin-roles", ['role_ids' => [$role->id]])
        ->assertOk()
        ->assertJsonPath('data.permissions', ['orders.view']);

    expect(DB::table('admin_roles')->where('user_id', $target->id)->value('assigned_by_user_id'))->toBe($assigner->id)
        ->and(AuditLog::query()->where('action', 'user.admin_roles_changed')->where('entity_id', $target->id)->exists())->toBeTrue();

    $this->putJson("/api/admin/users/{$assigner->id}/admin-roles", ['role_ids' => []])->assertForbidden();
});

it('only lets a super admin grant the super admin role', function () {
    $superRoleId = Role::query()->where('key', Role::KEY_SUPER_ADMIN)->value('id');
    $target = rbacAdminWith();

    Sanctum::actingAs(rbacAdminWith(AdminPermissions::all()));
    $this->putJson("/api/admin/users/{$target->id}/admin-roles", ['role_ids' => [$superRoleId]])->assertForbidden();
    expect($target->fresh()->isSuperAdmin())->toBeFalse();

    Sanctum::actingAs(rbacSuperAdmin());
    $this->putJson("/api/admin/users/{$target->id}/admin-roles", ['role_ids' => [$superRoleId]])->assertOk();
    expect($target->fresh()->isSuperAdmin())->toBeTrue();
});

it('refuses to assign roles to non-admin accounts', function () {
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    Sanctum::actingAs(rbacSuperAdmin());

    $this->putJson("/api/admin/users/{$customer->id}/admin-roles", ['role_ids' => [rbacRole(['orders.view'])->id]])
        ->assertUnprocessable();
});

it('protects the last super administrator', function () {
    $onlySuper = rbacSuperAdmin();
    Sanctum::actingAs($onlySuper);

    $this->putJson("/api/admin/users/{$onlySuper->id}", ['type' => User::TYPE_USER])->assertForbidden();
    $this->deleteJson("/api/admin/users/{$onlySuper->id}")->assertForbidden();
    expect($onlySuper->fresh()->isSuperAdmin())->toBeTrue();

    $otherSuper = rbacSuperAdmin();
    Sanctum::actingAs($otherSuper);
    $this->putJson("/api/admin/users/{$onlySuper->id}/admin-roles", ['role_ids' => []])->assertOk();
    expect($onlySuper->fresh()->isSuperAdmin())->toBeFalse();

    $this->putJson("/api/admin/users/{$otherSuper->id}/admin-roles", ['role_ids' => []])->assertForbidden();
});

it('prevents a delegated admin from managing a super admin account', function () {
    $super = rbacSuperAdmin();
    Sanctum::actingAs(rbacAdminWith(['users.view', 'users.update', 'users.delete']));

    $this->putJson("/api/admin/users/{$super->id}", ['name' => 'Hijacked'])->assertForbidden();
    $this->deleteJson("/api/admin/users/{$super->id}")->assertForbidden();
    expect($super->fresh()->name)->not->toBe('Hijacked');
});

it('requires roles.assign to create or promote an administrator account', function () {
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    Sanctum::actingAs(rbacAdminWith(['users.view', 'users.create', 'users.update']));

    $this->postJson('/api/admin/users', [
        'name' => 'New Admin',
        'phone_number' => '0991234567',
        'national_id' => '12345678901',
        'password' => 'Secret-Password-123',
        'type' => User::TYPE_ADMIN,
    ])->assertForbidden();

    $this->putJson("/api/admin/users/{$customer->id}", ['type' => User::TYPE_ADMIN])->assertForbidden();
    expect($customer->fresh()->type)->toBe(User::TYPE_USER);
});

it('creates new admin accounts with no permissions', function () {
    Sanctum::actingAs(rbacSuperAdmin());

    $id = $this->postJson('/api/admin/users', [
        'name' => 'Fresh Admin',
        'phone_number' => '0997654321',
        'national_id' => '10987654321',
        'password' => 'Secret-Password-123',
        'type' => User::TYPE_ADMIN,
    ])->assertCreated()->json('data.id');

    $created = User::query()->findOrFail($id);
    expect($created->adminPermissionKeys())->toBe([])
        ->and(AuditLog::query()->where('action', 'user.created')->where('entity_id', $id)->exists())->toBeTrue();
});

it('revokes admin roles when an account stops being an administrator', function () {
    $admin = rbacAdminWith(['orders.view']);
    Sanctum::actingAs(rbacSuperAdmin());

    $this->putJson("/api/admin/users/{$admin->id}", ['type' => User::TYPE_USER])->assertOk();

    expect($admin->adminRoles()->count())->toBe(0);
});

it('revokes the target tokens when an admin resets their password', function () {
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    $customer->createToken('device');
    Sanctum::actingAs(rbacSuperAdmin());

    $this->putJson("/api/admin/users/{$customer->id}", ['password' => 'Another-Secret-456'])->assertOk();

    expect($customer->tokens()->count())->toBe(0);
    $log = AuditLog::query()->where('action', 'user.updated')->where('entity_id', $customer->id)->latest('id')->firstOrFail();
    expect($log->new_values)->not->toHaveKey('password')
        ->and($log->new_values['password_changed'])->toBeTrue();
});

it('ends an existing browser session on its next request after an admin resets the password', function () {
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    $this->actingAs($customer, 'web')->get('/profile')->assertOk();
    $storedPasswordHash = session('password_hash_web');
    expect($storedPasswordHash)->not->toBeNull();

    Sanctum::actingAs(rbacSuperAdmin());
    $this->putJson("/api/admin/users/{$customer->id}", ['password' => \Illuminate\Support\Str::random(40)])->assertOk();

    $this->actingAs($customer->fresh(), 'web')->get('/profile')->assertRedirect('/login');
});

it('blocks self deletion and self type change', function () {
    $super = rbacSuperAdmin();
    rbacSuperAdmin();
    Sanctum::actingAs($super);

    $this->deleteJson("/api/admin/users/{$super->id}")->assertForbidden();
    $this->putJson("/api/admin/users/{$super->id}", ['type' => User::TYPE_USER])->assertForbidden();
});

it('reports effective permissions with their source roles', function () {
    $target = rbacAdminWith();
    $role = rbacRole(['orders.view', 'invoices.view'], 'order_desk');
    $target->adminRoles()->attach($role->id);
    Sanctum::actingAs(rbacAdminWith(['users.view']));

    $response = $this->getJson("/api/admin/users/{$target->id}/permissions")
        ->assertOk()
        ->assertJsonPath('data.is_super_admin', false)
        ->assertJsonPath('data.can_manage', false);

    expect($response->json('data.sources')['orders.view'])->toBe(['order_desk']);

    Sanctum::actingAs(rbacAdminWith(['users.view', 'orders.view', 'invoices.view']));
    $this->getJson("/api/admin/users/{$target->id}/permissions")->assertJsonPath('data.can_manage', true);
});

it('enforces record policies with the delegated permission, not the account type', function () {
    $viewer = rbacAdminWith(['orders.view']);
    $noAccess = rbacAdminWith();

    expect($viewer->can('viewAny', App\Models\Order::class) || $viewer->hasAdminPermission('orders.view'))->toBeTrue()
        ->and($noAccess->hasAdminPermission('orders.view'))->toBeFalse();

    $order = App\Models\Order::factory()->create();
    expect(app(App\Policies\OrderPolicy::class)->view($viewer, $order))->toBeTrue()
        ->and(app(App\Policies\OrderPolicy::class)->view($noAccess, $order))->toBeFalse();
});

it('backfills super admin for existing admins when the migration runs', function () {
    $migration = require database_path('migrations/2026_10_20_201738_add_admin_rbac_scope_and_admin_roles.php');
    $legacyAdmin = rbacSuperAdmin();
    $customer = User::factory()->create(['type' => User::TYPE_USER]);

    $migration->down();
    expect(DB::getSchemaBuilder()->hasTable('admin_roles'))->toBeFalse();

    $migration->up();

    $superRoleId = Role::query()->where('key', Role::KEY_SUPER_ADMIN)->value('id');
    expect(DB::table('admin_roles')->where('user_id', $legacyAdmin->id)->where('role_id', $superRoleId)->exists())->toBeTrue()
        ->and(DB::table('admin_roles')->where('user_id', $customer->id)->exists())->toBeFalse()
        ->and(Permission::query()->whereIn('key', AdminPermissions::all())->count())->toBe(count(AdminPermissions::all()));
});

it('labels every permission and group in both locales', function () {
    foreach (['en', 'ar'] as $locale) {
        $labels = trans('admin.rbac_permission_labels', [], $locale);
        $groups = trans('admin.rbac_groups', [], $locale);

        foreach (AdminPermissions::GROUPS as $group => $keys) {
            expect($groups)->toHaveKey($group);

            foreach ($keys as $key) {
                [$prefix, $action] = explode('.', $key);
                expect($labels[$prefix][$action] ?? null)->toBeString("{$locale} label missing for {$key}");
            }
        }
    }
});

it('completes cleanly when the migration is re-run after a partial failure', function () {
    $migration = require database_path('migrations/2026_10_20_201738_add_admin_rbac_scope_and_admin_roles.php');
    $admin = rbacSuperAdmin();

    $migration->up();

    $superRoleId = Role::query()->where('key', Role::KEY_SUPER_ADMIN)->value('id');
    expect(Role::query()->where('key', Role::KEY_SUPER_ADMIN)->count())->toBe(1)
        ->and(DB::table('admin_roles')->where('user_id', $admin->id)->where('role_id', $superRoleId)->count())->toBe(1);
});

it('refuses to adopt a non-admin role that already uses the super admin key', function () {
    $migration = require database_path('migrations/2026_10_20_201738_add_admin_rbac_scope_and_admin_roles.php');
    $migration->down();
    DB::table('roles')->insert(['key' => Role::KEY_SUPER_ADMIN, 'name_en' => 'Vendor', 'name_ar' => 'Vendor', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => $migration->up())->toThrow(RuntimeException::class);
});

it('only requests another section\'s admin data when the signed-in admin holds its permission', function (string $page, string $endpoint, string $permission) {
    $source = file_get_contents(resource_path("js/{$page}"));

    expect($source)->toContain($endpoint)
        ->and(str_contains($source, "can('{$permission}')"))->toBeTrue("{$page} fetches {$endpoint} without checking {$permission}");
})->with([
    'dashboard users' => ['hooks/use-admin-dashboard.js', "'/api/admin/users'", 'users.view'],
    'dashboard vendors' => ['hooks/use-admin-dashboard.js', "'/api/admin/vendors'", 'vendors.view'],
    'dashboard products' => ['hooks/use-admin-dashboard.js', "'/api/admin/products'", 'products.view'],
    'dashboard vendor map' => ['Pages/Admin/Dashboard.jsx', '/api/admin/vendors/map', 'vendors.view'],
    'orders user filter' => ['Pages/Admin/Orders/Index.jsx', "'/api/admin/users'", 'users.view'],
    'orders vendor filter' => ['Pages/Admin/Orders/Index.jsx', "'/api/admin/vendors'", 'vendors.view'],
    'products vendor filter' => ['Pages/Admin/Products/Index.jsx', "'/api/admin/vendors'", 'vendors.view'],
    'user profile orders' => ['Pages/Admin/Users/Show.jsx', "'/api/admin/orders'", 'orders.view'],
]);

it('asks for confirmation only for sensitive permissions a role does not already hold', function () {
    $source = file_get_contents(resource_path('js/Pages/Admin/Roles/Form.jsx'));

    expect($source)->toContain('permissionMeta[key]?.sensitive && !savedPermissions.includes(key)')
        ->toContain('setSavedPermissions(isEdit ? (role.permissions ?? []) : [])');
});

it('sends each admin from /admin to the first back-office page they may open', function (array $permissions, string $expectedRoute) {
    $this->actingAs(rbacAdminWith($permissions))
        ->get('/admin')
        ->assertRedirect(route($expectedRoute));
})->with([
    'dashboard holder' => [['dashboard.view', 'orders.view'], 'admin.dashboard'],
    'orders only' => [['orders.view'], 'admin.orders.index'],
    'audit trail only' => [['audit_logs.view'], 'admin.audit-logs.index'],
    'no permissions' => [[], 'admin.categories.index'],
]);

it('points the admin login redirect at the landing route rather than the dashboard', function () {
    $admin = rbacAdminWith(['orders.view']);
    $admin->forceFill(['password' => bcrypt('landing-secret-1')])->save();

    $this->postJson('/api/auth/login', ['phone_number' => $admin->phone_number, 'password' => 'landing-secret-1'])
        ->assertOk()
        ->assertJsonPath('data.redirect_url', route('admin.home'));
});

it('sends admins to the landing route from every frontend dashboard link', function (string $path) {
    $source = file_get_contents(resource_path($path));

    expect($source)->toContain("1: 'admin.home'")
        ->and($source)->not->toContain("1: 'admin.dashboard'");
})->with([
    'login page' => 'js/Pages/Auth/Login.jsx',
    'profile menu' => 'js/Components/public/ProfileMenu.jsx',
    'mobile drawer' => 'js/Components/public/MobileDrawer.jsx',
]);

it('lets a role manager without roles.view load the role editor data', function () {
    $role = rbacRole(['orders.view']);
    Sanctum::actingAs(rbacAdminWith(['roles.manage', 'orders.view']));

    $this->getJson('/api/admin/roles/permissions')->assertOk();
    $this->getJson("/api/admin/roles/{$role->id}")->assertOk();
    $this->getJson('/api/admin/roles')->assertForbidden();
});

it('shows product mutations only to admins who may perform them', function () {
    $source = file_get_contents(resource_path('js/Pages/Admin/Products/Index.jsx'));

    expect($source)->toContain("const canModerate = can('products.moderate');")
        ->toContain("const canManage = can('products.manage');")
        ->toContain('return canModerate ? <button type="button" onClick={() => toggleActive(row)}>')
        ->toContain('render: (row) => !canModerate ? (')
        ->toContain('actions={canManage && (')
        ->toContain("{canManage && (\n                        <>\n                            <Button asChild variant=\"outline\" size=\"sm\">\n                                <Link href={route('admin.products.edit', row.id)}>");
});
