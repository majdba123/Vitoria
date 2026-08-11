<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            // Nullable so a system-driven transition is representable, and so
            // deleting a user never erases the order's audit trail.
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 20)->nullable();
            $table->string('reason', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Timeline is always read as "this order's events, oldest first".
            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
