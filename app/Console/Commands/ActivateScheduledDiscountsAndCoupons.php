<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Product;
use App\Services\ApplicationCacheService;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
    public function handle(ApplicationCacheService $cacheService, AuditLogService $auditLog): int
    {
        $now = Carbon::now();

        [$activatedCoupons, $activatedDiscounts] = DB::transaction(function () use ($now, $auditLog): array {
            $coupons = Coupon::query()
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
                ->lockForUpdate()
                ->get(['id', 'status']);

            $discounts = Product::query()
                ->where('discount_is_active', true)
                ->where('discount_status', Product::DISCOUNT_STATUS_PENDING)
                ->whereNotNull('discount_percentage')
                ->where('discount_percentage', '>', 0)
                ->where(function ($query) use ($now) {
                    $query->whereNull('discount_starts_at')
                        ->orWhereDate('discount_starts_at', '<=', $now->toDateString());
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('discount_ends_at')
                        ->orWhereDate('discount_ends_at', '>=', $now->toDateString());
                })
                ->lockForUpdate()
                ->get(['id', 'discount_status']);

            Coupon::query()->whereKey($coupons->modelKeys())->update(['status' => Coupon::STATUS_ACTIVE]);
            Product::query()->whereKey($discounts->modelKeys())->update(['discount_status' => Product::DISCOUNT_STATUS_ACTIVE]);

            $coupons->each(fn (Coupon $coupon) => $auditLog->record(
                null,
                'coupon.activated_by_schedule',
                'Coupon',
                $coupon->id,
                ['status' => $coupon->status],
                ['status' => Coupon::STATUS_ACTIVE],
                isSystemAction: true,
            ));
            $discounts->each(fn (Product $product) => $auditLog->record(
                null,
                'product.discount.activated_by_schedule',
                'Product',
                $product->id,
                ['discount_status' => $product->discount_status],
                ['discount_status' => Product::DISCOUNT_STATUS_ACTIVE],
                isSystemAction: true,
            ));

            return [$coupons->count(), $discounts->count()];
        });

        if ($activatedDiscounts > 0) {
            $cacheService->flushProducts();
        }

        $this->info("Activated coupons: {$activatedCoupons}");
        $this->info("Activated product discounts: {$activatedDiscounts}");

        return self::SUCCESS;
    }
}
