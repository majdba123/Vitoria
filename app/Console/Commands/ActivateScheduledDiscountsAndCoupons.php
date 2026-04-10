<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ActivateScheduledDiscountsAndCoupons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:activate-scheduled-discounts-and-coupons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate pending discounts and coupons by start date.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        $activatedCoupons = Coupon::query()
            ->where('is_active', true)
            ->where('status', Coupon::STATUS_PENDING)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->update(['status' => Coupon::STATUS_ACTIVE]);

        $activatedDiscounts = Product::query()
            ->where('discount_is_active', true)
            ->where('discount_status', Product::DISCOUNT_STATUS_PENDING)
            ->whereNotNull('discount_percentage')
            ->where('discount_percentage', '>', 0)
            ->where(function ($query) use ($now) {
                $query->whereNull('discount_starts_at')
                    ->orWhere('discount_starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('discount_ends_at')
                    ->orWhere('discount_ends_at', '>=', $now);
            })
            ->update(['discount_status' => Product::DISCOUNT_STATUS_ACTIVE]);

        $this->info("Activated coupons: {$activatedCoupons}");
        $this->info("Activated product discounts: {$activatedDiscounts}");

        return self::SUCCESS;
    }
}
