<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendor compliance documents (spec §24).
     *
     * `vendors.commercial_register_file` — the one document field that
     * existed before this — is left in place, untouched, not migrated: it
     * is still written by self-registration and still downloadable via the
     * existing admin endpoint. Rewriting it into this table would be a
     * destructive migration for no functional gain (§60); it simply stops
     * being the only document a vendor can have. Self-registration is
     * additionally wired to create a `commercial_registration` row here
     * (see VendorDocumentService / AuthService), so day-one vendors appear
     * in the review queue this table powers.
     *
     * One row per (vendor, type): re-uploading replaces the file and resets
     * review state rather than accumulating history rows, since only the
     * current document for a type is ever meaningful to a reviewer.
     */
    public function up(): void
    {
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 20)->default('pending_review')->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'type']);
        });

        $this->addDocumentsPermission();
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }

    /**
     * `documents.manage` reuses the RBAC tables the vendor-staff migration
     * created (decision D15) — this is data only, no schema change to
     * `permissions`/`role_permissions`. Granted to the same roles as
     * `profile.manage`: documents are part of the store's compliance
     * profile, not a separate concern that needs its own role split.
     */
    private function addDocumentsPermission(): void
    {
        $now = now();

        $permissionId = DB::table('permissions')->insertGetId([
            'key' => 'documents.manage',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $roleIds = DB::table('roles')->whereIn('key', ['owner', 'manager'])->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
