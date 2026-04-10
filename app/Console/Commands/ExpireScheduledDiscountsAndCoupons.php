<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireScheduledDiscountsAndCoupons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-scheduled-discounts-and-coupons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire discounts and coupons by end date.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        $expiredCoupons = Coupon::query()
            ->where('status', '!=', Coupon::STATUS_EXPIRED)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->update(['status' => Coupon::STATUS_EXPIRED]);

        $expiredDiscounts = Product::query()
            ->where('discount_status', Product::DISCOUNT_STATUS_ACTIVE)
            ->whereNotNull('discount_ends_at')
            ->where('discount_ends_at', '<', $now)
            ->update(['discount_status' => Product::DISCOUNT_STATUS_EXPIRED]);

        $this->info("Expired coupons: {$expiredCoupons}");
        $this->info("Expired product discounts: {$expiredDiscounts}");

        return self::SUCCESS;
    }
}
