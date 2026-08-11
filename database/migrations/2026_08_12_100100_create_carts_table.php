<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            // Exactly one of user_id / session_token identifies the owner.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_token', 64)->nullable();
            $table->string('coupon_code')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            // One active cart per user, one per guest session.
            $table->unique('user_id');
            $table->unique('session_token');
            // Expiry sweep scans by inactivity.
            $table->index('last_activity_at');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            // Adding the same product twice updates the row instead of duplicating it.
            $table->unique(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
