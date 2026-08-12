<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable audit log for sensitive actions (spec §35). Rows are only
     * ever inserted — nothing in this codebase updates or deletes one.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // Nullable: an action taken by an unauthenticated or deleted
            // actor must not lose its audit row.
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 20)->nullable();
            $table->string('action', 60);
            $table->string('entity_type', 60)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            // Correlates every audit row written during one HTTP request —
            // see App\Http\Middleware\AssignRequestId.
            $table->string('request_id', 36)->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('actor_user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
