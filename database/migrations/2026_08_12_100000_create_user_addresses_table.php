<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 20)->default('home');
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->string('alternate_phone', 30)->nullable();
            $table->string('governorate');
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('street')->nullable();
            $table->string('building')->nullable();
            $table->string('floor', 50)->nullable();
            $table->string('landmark')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
            $table->timestamps();

            // Address list is always read as "this user's addresses, default first".
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
