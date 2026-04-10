<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('is_active');
            $table->index('status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('discount_status', 20)->default('pending')->after('discount_ends_at');
            $table->index('discount_status');
        });

        $now = Carbon::now();

        DB::table('coupons')->select(['id', 'is_active', 'starts_at', 'ends_at'])->orderBy('id')->chunkById(200, function ($rows) use ($now) {
            foreach ($rows as $row) {
                $startsAt = $row->starts_at ? Carbon::parse($row->starts_at) : null;
                $endsAt = $row->ends_at ? Carbon::parse($row->ends_at) : null;

                $status = 'pending';
                if ($row->is_active) {
                    if ($endsAt && $endsAt->lt($now)) {
                        $status = 'expired';
                    } elseif (! $startsAt || $startsAt->lte($now)) {
                        $status = 'active';
                    }
                }

                DB::table('coupons')->where('id', $row->id)->update(['status' => $status]);
            }
        });

        DB::table('products')->select(['id', 'discount_is_active', 'discount_percentage', 'discount_starts_at', 'discount_ends_at'])->orderBy('id')->chunkById(200, function ($rows) use ($now) {
            foreach ($rows as $row) {
                $startsAt = $row->discount_starts_at ? Carbon::parse($row->discount_starts_at) : null;
                $endsAt = $row->discount_ends_at ? Carbon::parse($row->discount_ends_at) : null;

                $status = 'pending';
                if ($row->discount_is_active && $row->discount_percentage !== null && (float) $row->discount_percentage > 0) {
                    if ($endsAt && $endsAt->lt($now)) {
                        $status = 'expired';
                    } elseif (! $startsAt || $startsAt->lte($now)) {
                        $status = 'active';
                    }
                }

                DB::table('products')->where('id', $row->id)->update(['discount_status' => $status]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['discount_status']);
            $table->dropColumn('discount_status');
        });
    }
};
