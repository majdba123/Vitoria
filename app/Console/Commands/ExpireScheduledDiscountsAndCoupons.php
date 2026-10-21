<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Product;
use App\Services\ApplicationCacheService;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
    public function handle(ApplicationCacheService $cacheService, AuditLogService $auditLog): int
    {
        $now = Carbon::now();

        [$expiredCoupons, $expiredDiscounts] = DB::transaction(function () use ($now, $auditLog): array {
            $coupons = Coupon::query()
                ->where('status', '!=', Coupon::STATUS_EXPIRED)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', $now)
                ->lockForUpdate()
                ->get(['id', 'status']);

            $discounts = Product::query()
                ->where('discount_status', Product::DISCOUNT_STATUS_ACTIVE)
                ->whereNotNull('discount_ends_at')
                ->whereDate('discount_ends_at', '<', $now->toDateString())
                ->lockForUpdate()
                ->get(['id', 'discount_status']);

            Coupon::query()->whereKey($coupons->modelKeys())->update(['status' => Coupon::STATUS_EXPIRED]);
            Product::query()->whereKey($discounts->modelKeys())->update(['discount_status' => Product::DISCOUNT_STATUS_EXPIRED]);

            $coupons->each(fn (Coupon $coupon) => $auditLog->record(
                null,
                'coupon.expired_by_schedule',
                'Coupon',
                $coupon->id,
                ['status' => $coupon->status],
                ['status' => Coupon::STATUS_EXPIRED],
                isSystemAction: true,
            ));
            $discounts->each(fn (Product $product) => $auditLog->record(
                null,
                'product.discount.expired_by_schedule',
                'Product',
                $product->id,
                ['discount_status' => $product->discount_status],
                ['discount_status' => Product::DISCOUNT_STATUS_EXPIRED],
                isSystemAction: true,
            ));

            return [$coupons->count(), $discounts->count()];
        });

        if ($expiredDiscounts > 0) {
            $cacheService->flushProducts();
        }

        $this->info("Expired coupons: {$expiredCoupons}");
        $this->info("Expired product discounts: {$expiredDiscounts}");

        return self::SUCCESS;
    }
}
