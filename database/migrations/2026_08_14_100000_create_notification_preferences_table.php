<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user opt-out of non-critical notification categories (spec §33).
     *
     * In-app only. Real email delivery does not exist anywhere in this
     * codebase today (no Notification/Mailable class, no Mail:: call,
     * MAIL_MAILER defaults to `log`) — the same reasoning §33 already gives
     * for SMS/push ("do not implement unless infrastructure already
     * exists") applies to email here too, and a channel toggle that
     * delivers nothing would be the fake-feature problem decisions D9/D12
     * already rejected elsewhere. No `channel` column is added on spec —
     * one would exist to hold exactly one ever-used value, which is a
     * speculative column, not a structural one (unlike D7/D12's zero-rate
     * money columns, which a real total formula needs even before a rate
     * is configured).
     *
     * Absence of a row means "enabled" — this is an opt-out model, so no
     * backfill is needed for existing users.
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'category']);
        });

        // Lets the notification list/unread-count query filter public
        // broadcasts by category without joining back through whatever
        // created them. Nullable and unbackfilled — historical rows have no
        // category and are therefore never hidden by a preference.
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->string('category', 30)->nullable()->after('type');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });

        Schema::dropIfExists('notification_preferences');
    }
};
