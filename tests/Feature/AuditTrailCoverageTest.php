<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * Application-wide audit trail: what is recorded, what is never recorded,
 * the append-only guarantee at the model layer, and who may read or export it.
 */
function auditAdminWith(array $permissionKeys): User
{
    $user = User::factory()->delegatedAdmin()->create();
    $role = Role::query()->create([
        'key' => 'audit_'.Str::lower(Str::random(8)),
        'scope' => Role::SCOPE_ADMIN,
        'name_en' => 'Audit test',
        'name_ar' => 'اختبار',
        'is_system' => false,
    ]);
    $role->permissions()->sync(Permission::query()->whereIn('key', $permissionKeys)->pluck('id'));
    $user->adminRoles()->attach($role->id);

    return $user;
}

it('redacts credential-like keys by fragment and masks personal identifiers', function () {
    $log = app(AuditLogService::class)->record(null, 'test.redaction', 'Test', null, null, [
        'client_secret' => 'shh',
        'refresh_token' => 'r-123',
        'Authorization' => 'Bearer abc',
        'card_number' => '4111111111111111',
        'otp_code' => '123456',
        'nested' => ['new_password_confirmation' => 'x', 'kept' => 'visible'],
        'national_id' => '12345678901',
        'phone_number' => '0991234567',
        'password_changed' => true,
        'name' => 'Visible Name',
    ]);

    $values = $log->new_values;
    expect($values['client_secret'])->toBe('[redacted]')
        ->and($values['refresh_token'])->toBe('[redacted]')
        ->and($values['Authorization'])->toBe('[redacted]')
        ->and($values['card_number'])->toBe('[redacted]')
        ->and($values['otp_code'])->toBe('[redacted]')
        ->and($values['nested']['new_password_confirmation'])->toBe('[redacted]')
        ->and($values['nested']['kept'])->toBe('visible')
        ->and($values['national_id'])->not->toContain('12345678')
        ->and($values['national_id'])->toEndWith('901')
        ->and($values['phone_number'])->toEndWith('567')
        ->and($values['phone_number'])->not->toContain('0991234')
        ->and($values['password_changed'])->toBeTrue()
        ->and($values['name'])->toBe('Visible Name');
});

it('refuses to update or delete an audit entry through the model', function () {
    $log = app(AuditLogService::class)->record(null, 'test.append_only', 'Test', null);

    expect(fn () => $log->update(['action' => 'tampered']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class)
        ->and(AuditLog::query()->find($log->id)->action)->toBe('test.append_only');
});

it('exposes no update or delete endpoint for audit entries', function () {
    $log = app(AuditLogService::class)->record(null, 'test.no_endpoint', 'Test', null);
    Sanctum::actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));

    $this->putJson("/api/admin/audit-logs/{$log->id}", ['action' => 'x'])->assertStatus(405);
    $this->deleteJson("/api/admin/audit-logs/{$log->id}")->assertStatus(405);
});

it('records a generic mutation entry with field names but never values', function () {
    $admin = auditAdminWith(['catalog.manage']);
    Sanctum::actingAs($admin);

    $this->postJson('/api/admin/cities', ['name' => 'Latakia'])->assertCreated();

    $log = AuditLog::query()->where('action', 'api.mutation')->latest('id')->firstOrFail();
    expect($log->actor_user_id)->toBe($admin->id)
        ->and($log->new_values['route'])->toBe('api.admin.cities.store')
        ->and($log->new_values['method'])->toBe('POST')
        ->and($log->new_values['fields'])->toBe(['name'])
        ->and(json_encode($log->new_values))->not->toContain('Latakia');
});

it('does not add a generic entry when the action already wrote a domain audit', function () {
    $vendor = Vendor::factory()->create(['status' => Vendor::STATUS_PENDING, 'is_active' => false]);
    Sanctum::actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));

    $this->patchJson("/api/admin/vendors/{$vendor->id}/approve")->assertOk();

    expect(AuditLog::query()->where('action', 'vendor.approved')->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'api.mutation')->count())->toBe(0);
});

it('does not audit failed or read-only requests generically', function () {
    Sanctum::actingAs(auditAdminWith(['catalog.manage']));

    $this->getJson('/api/admin/cities')->assertOk();
    $this->postJson('/api/admin/cities', [])->assertUnprocessable();

    expect(AuditLog::query()->where('action', 'api.mutation')->count())->toBe(0);
});

it('audits product edits with before and after values', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $product = Product::factory()->for($vendor)->create(['price' => 100]);
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->putJson("/api/admin/products/{$product->id}", ['price' => 125])->assertOk();

    $log = AuditLog::query()->where('action', 'product.updated')->where('entity_id', $product->id)->firstOrFail();
    expect((float) $log->old_values['price'])->toBe(100.0)
        ->and((float) $log->new_values['price'])->toBe(125.0)
        ->and($log->actor_user_id)->toBe($admin->id);
});

it('audits successful and failed sign-in without storing the password', function () {
    $user = User::factory()->create(['phone_number' => '0988777666', 'password' => Hash::make('right-secret-1')]);

    $this->postJson('/api/auth/login', ['phone_number' => '0988777666', 'password' => 'wrong-secret'])->assertStatus(422);
    $this->postJson('/api/auth/login', ['phone_number' => '0911000111', 'password' => 'whatever'])->assertStatus(422);
    $this->postJson('/api/auth/login', ['phone_number' => '0988777666', 'password' => 'right-secret-1'])->assertOk();

    $failed = AuditLog::query()->where('action', 'auth.login_failed')->orderBy('id')->get();
    expect($failed)->toHaveCount(2)
        ->and($failed[0]->entity_id)->toBe($user->id)
        ->and($failed[0]->new_values['reason'])->toBe('invalid_password')
        ->and($failed[1]->new_values['reason'])->toBe('unknown_account')
        ->and($failed[1]->new_values['phone_number'])->not->toContain('0911000')
        ->and(AuditLog::query()->where('action', 'auth.login')->where('actor_user_id', $user->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->pluck('new_values')->toJson())->not->toContain('right-secret-1')
        ->and(AuditLog::query()->pluck('new_values')->toJson())->not->toContain('wrong-secret');
});

it('audits bulk data exports with their row count', function () {
    $admin = auditAdminWith(['reports.export']);
    Sanctum::actingAs($admin);

    $this->get('/api/admin/exports/vendors?status=active')->assertOk();

    $log = AuditLog::query()->where('action', 'export.vendors')->firstOrFail();
    expect($log->actor_user_id)->toBe($admin->id)
        ->and($log->new_values['filters'])->toBe(['status' => 'active'])
        ->and($log->new_values)->toHaveKey('row_count');
});

it('requires audit_logs.view to read and audit_logs.export to export the trail', function () {
    $log = app(AuditLogService::class)->record(null, 'test.read', 'Test', null);

    Sanctum::actingAs(auditAdminWith(['dashboard.view']));
    $this->getJson('/api/admin/audit-logs')->assertForbidden();
    $this->getJson("/api/admin/audit-logs/{$log->id}")->assertForbidden();

    Sanctum::actingAs(auditAdminWith(['audit_logs.view']));
    $this->getJson('/api/admin/audit-logs')->assertOk()->assertJsonStructure(['meta' => ['facets' => ['actions', 'entity_types', 'actor_types']]]);
    $this->getJson("/api/admin/audit-logs/{$log->id}")->assertOk()->assertJsonPath('data.action', 'test.read');
    $this->get('/api/admin/audit-logs/export', ['Accept' => 'application/json'])->assertForbidden();
});

it('filters the trail by action prefix, actor and date', function () {
    $actor = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $service = app(AuditLogService::class);
    $service->record($actor, 'role.created', 'Role', 1);
    $service->record($actor, 'role.deleted', 'Role', 1);
    $service->record(null, 'auth.login_failed', 'User', 2);
    Sanctum::actingAs($actor);

    expect($this->getJson('/api/admin/audit-logs?action=role.*')->json('meta.total'))->toBe(2)
        ->and($this->getJson("/api/admin/audit-logs?actor_user_id={$actor->id}&entity_type=Role")->json('meta.total'))->toBe(2)
        ->and($this->getJson('/api/admin/audit-logs?from='.now()->addDay()->toDateString())->json('meta.total'))->toBe(0);

    $this->getJson('/api/admin/audit-logs?from=2026-05-02&to=2026-05-01')->assertUnprocessable();
});

it('exports the trail as formula-safe CSV and audits the export itself', function () {
    $admin = auditAdminWith(['audit_logs.view', 'audit_logs.export']);
    app(AuditLogService::class)->record(null, '=HYPERLINK("http://evil")', 'Test', null);
    Sanctum::actingAs($admin);

    $response = $this->get('/api/admin/audit-logs/export');
    $response->assertOk();
    $csv = $response->streamedContent();

    expect($csv)->toContain("'=HYPERLINK")
        ->and($csv)->not->toMatch('/(^|,)=HYPERLINK/m')
        ->and(AuditLog::query()->where('action', 'audit_logs.exported')->where('actor_user_id', $admin->id)->exists())->toBeTrue();
});

it('re-redacts stored values when presenting legacy rows', function () {
    $log = app(AuditLogService::class)->record(null, 'legacy.row', 'Test', null);
    AuditLog::query()->whereKey($log->id)->toBase()->update(['new_values' => json_encode(['api_key' => 'leaked-key'])]);
    Sanctum::actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));

    $this->getJson("/api/admin/audit-logs/{$log->id}")
        ->assertOk()
        ->assertJsonPath('data.new_values.api_key', '[redacted]');
});

it('audits review deletion from the admin web panel', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $product = Product::factory()->for($vendor)->create();
    $author = User::factory()->create(['type' => User::TYPE_USER]);
    $review = App\Models\ProductReview::query()->create(['product_id' => $product->id, 'user_id' => $author->id, 'rating' => 2, 'body' => 'Not great']);
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);

    $this->actingAs($admin)->delete("/admin/products/{$product->id}/reviews/{$review->id}")->assertRedirect();

    $log = AuditLog::query()->where('action', 'product_review.deleted')->where('entity_id', $review->id)->firstOrFail();
    expect($log->actor_user_id)->toBe($admin->id)
        ->and($log->old_values['body'])->toBe('Not great');
});

it('never bulk updates or deletes audit rows from application code', function () {
    $offenders = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $source = file_get_contents($file->getPathname());

        if (preg_match('/AuditLog::(query\(\))?[^;]*->(update|delete|forceDelete|truncate)\(/s', $source)
            || preg_match("/DB::table\('audit_logs'\)[^;]*->(update|delete|truncate)\(/s", $source)) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([]);
});
