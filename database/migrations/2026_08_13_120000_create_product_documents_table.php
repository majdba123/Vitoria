<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Official product documents (spec §25) — leaflets, labels, safety data
     * sheets, registration certificates, manufacturer documents. A
     * deliberately separate table and model from `vendor_documents`: those
     * are private compliance material an admin reviews and only the vendor
     * and admin ever see; these are catalog content that becomes publicly
     * downloadable once approved. Conflating the two would risk exactly the
     * "do not expose private vendor documents" mistake §25 warns against.
     *
     * No `unique(product_id, type)` — unlike vendor_documents, a product can
     * legitimately have several documents of the same type (a leaflet in
     * Arabic and one in English), so each upload is its own row rather than
     * replacing the last one.
     */
    public function up(): void
    {
        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Denormalized for vendor-scoped queries without a join through
            // products, matching the pattern order_returns/shipments already
            // use for vendor_id.
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title');
            $table->string('language', 5)->default('ar');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->string('source', 10);
            $table->string('status', 20)->default('pending_review')->index();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_documents');
    }
};
