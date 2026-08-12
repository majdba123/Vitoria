<?php

use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Vendor compliance documents (spec §24).
 *
 * The load-bearing properties: files are never public, a resubmission
 * replaces rather than accumulates, and a document can't be reviewed twice.
 */
function vdVendor(): array
{
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $owner->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    return ['vendor' => $vendor, 'owner' => $owner];
}

it('stores an uploaded document privately, never on the public disk', function () {
    Storage::fake('local');
    Storage::fake('public');
    $context = vdVendor();

    Sanctum::actingAs($context['owner']);
    $documentId = $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_BUSINESS_LICENSE,
        'file' => UploadedFile::fake()->create('license.pdf', 200, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    $document = VendorDocument::query()->findOrFail($documentId);

    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);
    // The server-generated path never echoes the client's original filename.
    expect($document->file_path)->not->toContain('license.pdf')
        ->and($document->original_filename)->toBe('license.pdf')
        ->and($document->status)->toBe(VendorDocument::STATUS_PENDING_REVIEW);
});

it('rejects an unsupported document type', function () {
    Storage::fake('local');
    $context = vdVendor();

    Sanctum::actingAs($context['owner']);
    $this->postJson('/api/vendor/documents', [
        'type' => 'passport',
        'file' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
    ])->assertStatus(422);
});

it('replaces the file and resets review state when a document type is resubmitted', function () {
    Storage::fake('local');
    $context = vdVendor();
    Sanctum::actingAs($context['owner']);

    $firstId = $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_TAX_REGISTRATION,
        'file' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    $first = VendorDocument::query()->findOrFail($firstId);
    $firstPath = $first->file_path;

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->patchJson("/api/admin/vendor-documents/{$firstId}/review", ['status' => VendorDocument::STATUS_REJECTED, 'rejection_reason' => 'blurry'])
        ->assertOk();

    Sanctum::actingAs($context['owner']);
    $secondId = $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_TAX_REGISTRATION,
        'file' => UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    expect($secondId)->toBe($firstId)
        ->and(VendorDocument::query()->where('vendor_id', $context['vendor']->id)->where('type', VendorDocument::TYPE_TAX_REGISTRATION)->count())->toBe(1);

    $refreshed = $first->refresh();
    expect($refreshed->status)->toBe(VendorDocument::STATUS_PENDING_REVIEW)
        ->and($refreshed->rejection_reason)->toBeNull()
        ->and($refreshed->reviewed_at)->toBeNull();

    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($refreshed->file_path);
});

it('lets a manager upload documents but not a viewer', function () {
    Storage::fake('local');
    $context = vdVendor();

    $viewer = User::factory()->create(['type' => User::TYPE_USER]);
    Sanctum::actingAs($context['owner']);
    $this->postJson('/api/vendor/staff', ['identifier' => $viewer->email, 'role' => Role::KEY_VIEWER])->assertCreated();

    Sanctum::actingAs($viewer->fresh());
    $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_OTHER,
        'file' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
    ])->assertForbidden();

    // But a viewer can still see the document list — it isn't sensitive.
    $this->getJson('/api/vendor/documents')->assertOk();
});

it('lets an admin approve a pending document', function () {
    Storage::fake('local');
    $context = vdVendor();
    Sanctum::actingAs($context['owner']);
    $documentId = $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_COMMERCIAL_REGISTRATION,
        'file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->patchJson("/api/admin/vendor-documents/{$documentId}/review", ['status' => VendorDocument::STATUS_VERIFIED])
        ->assertOk()
        ->assertJsonPath('data.status', VendorDocument::STATUS_VERIFIED);
});

it('requires a rejection reason to reject a document', function () {
    Storage::fake('local');
    $context = vdVendor();
    Sanctum::actingAs($context['owner']);
    $documentId = $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_COMMERCIAL_REGISTRATION,
        'file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->patchJson("/api/admin/vendor-documents/{$documentId}/review", ['status' => VendorDocument::STATUS_REJECTED])
        ->assertStatus(422);
});

it('cannot review the same document twice', function () {
    Storage::fake('local');
    $context = vdVendor();
    Sanctum::actingAs($context['owner']);
    $documentId = $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_COMMERCIAL_REGISTRATION,
        'file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->patchJson("/api/admin/vendor-documents/{$documentId}/review", ['status' => VendorDocument::STATUS_VERIFIED])->assertOk();
    $this->patchJson("/api/admin/vendor-documents/{$documentId}/review", ['status' => VendorDocument::STATUS_REJECTED, 'rejection_reason' => 'x'])
        ->assertStatus(422);

    expect(VendorDocument::query()->findOrFail($documentId)->status)->toBe(VendorDocument::STATUS_VERIFIED);
});

it('lets an admin suspend a previously verified document', function () {
    Storage::fake('local');
    $context = vdVendor();
    Sanctum::actingAs($context['owner']);
    $documentId = $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_COMMERCIAL_REGISTRATION,
        'file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->patchJson("/api/admin/vendor-documents/{$documentId}/review", ['status' => VendorDocument::STATUS_VERIFIED])->assertOk();
    $this->patchJson("/api/admin/vendor-documents/{$documentId}/suspend", ['reason' => 'expired license on file'])
        ->assertOk()
        ->assertJsonPath('data.status', VendorDocument::STATUS_SUSPENDED);
});

it('stops a vendor from viewing or downloading another vendor\'s document', function () {
    Storage::fake('local');
    $contextA = vdVendor();
    $contextB = vdVendor();

    Sanctum::actingAs($contextA['owner']);
    $documentId = $this->postJson('/api/vendor/documents', [
        'type' => VendorDocument::TYPE_COMMERCIAL_REGISTRATION,
        'file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
    ])->assertCreated()->json('data.id');

    Sanctum::actingAs($contextB['owner']);
    $this->getJson("/api/vendor/documents/{$documentId}")->assertForbidden();
    $this->getJson("/api/vendor/documents/{$documentId}/download")->assertForbidden();
});

it('expires overdue verified documents when the admin queue is loaded', function () {
    Storage::fake('local');
    $context = vdVendor();

    $document = VendorDocument::factory()->create([
        'vendor_id' => $context['vendor']->id,
        'status' => VendorDocument::STATUS_VERIFIED,
        'expires_at' => now()->subDay()->toDateString(),
    ]);

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->getJson('/api/admin/vendor-documents')->assertOk();

    expect($document->refresh()->status)->toBe(VendorDocument::STATUS_EXPIRED);
});

it('creates a commercial_registration document automatically at vendor self-registration', function () {
    Storage::fake('local');
    $city = \App\Models\City::query()->create(['name' => 'Damascus']);
    $category = \App\Models\Category::query()->create(['name' => 'Agricultural Products']);

    $this->post('/api/auth/register', [
        'account_type' => 'vendor',
        'name' => 'New Merchant',
        'phone_number' => '0991000099',
        'national_id' => '1234509999',
        'age' => 30,
        'membership_number' => 'MEM-999099',
        'city_id' => $city->id,
        'email' => 'newmerchant@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'New Merchant Store',
        'business_type' => Vendor::BUSINESS_TYPE_BOTH,
        'category_ids' => [$category->id],
        'commercial_register_file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
    ])->assertCreated();

    $vendor = Vendor::query()->where('store_name', 'New Merchant Store')->firstOrFail();

    expect(VendorDocument::query()
        ->where('vendor_id', $vendor->id)
        ->where('type', VendorDocument::TYPE_COMMERCIAL_REGISTRATION)
        ->where('status', VendorDocument::STATUS_PENDING_REVIEW)
        ->exists())->toBeTrue();
});
