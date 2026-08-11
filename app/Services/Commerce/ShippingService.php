<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Delivery zones, methods, rates, and per-order shipment tracking (spec §14).
 *
 * No warehouse or inventory concept is involved — this is purely "what does
 * delivery cost and where is the package", against the existing
 * `products.quantity` model (decision D1 is untouched by this service).
 *
 * Rates default to 0 (decision D12): no real business rate exists in the
 * repository, and inventing one would be the fake shipping behaviour §63
 * forbids. The mechanism is real; the numbers are configuration.
 */
class ShippingService
{
    /**
     * @return array{zone: ShippingZone, method: ShippingMethod, rate: ShippingRate|null, amount: float}
     *
     * @throws CartException when the method code is not one the platform
     *                       configures
     */
    public function quoteFor(?string $governorate, string $methodCode, float $subtotal): array
    {
        $method = ShippingMethod::query()->where('code', $methodCode)->where('is_active', true)->first();

        if (! $method) {
            throw new CartException(__('shipping.method_invalid'));
        }

        $zone = $this->resolveZone($governorate);
        $rate = $zone
            ? ShippingRate::query()
                ->where('shipping_zone_id', $zone->id)
                ->where('shipping_method_id', $method->id)
                ->where('is_active', true)
                ->first()
            : null;

        return [
            'zone' => $zone,
            'method' => $method,
            'rate' => $rate,
            'amount' => $rate ? $rate->amountFor($subtotal) : 0.0,
        ];
    }

    /**
     * All active methods with their quoted amount for this governorate and
     * subtotal — what the checkout screen offers the customer.
     *
     * @return list<array{method: ShippingMethod, amount: float}>
     */
    public function availableMethods(?string $governorate, float $subtotal): array
    {
        return ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (ShippingMethod $method) use ($governorate, $subtotal) {
                $quote = $this->quoteFor($governorate, $method->code, $subtotal);

                return ['method' => $method, 'amount' => $quote['amount']];
            })
            ->all();
    }

    /**
     * Create the tracking record for a newly placed order, priced by the
     * same quote CheckoutService already used for `shipping_total` — so the
     * shipment and the money on the order can never disagree.
     */
    public function createShipmentForOrder(Order $order, string $methodCode, ?ShippingZone $zone): Shipment
    {
        $method = ShippingMethod::query()->where('code', $methodCode)->first();

        return Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $method?->id,
            'shipping_zone_id' => $zone?->id,
            'status' => Shipment::STATUS_PENDING,
        ]);
    }

    /**
     * Keep the shipment's status in lockstep with the order's happy-path
     * fulfilment transitions. Best-effort: a shipment-side quirk (e.g. it is
     * already `failed`) must never block the order transition that is
     * already committed, so failures here are swallowed rather than thrown.
     */
    public function syncFromOrderStatus(Order $order, string $newOrderStatus, User $actor, string $actorType): void
    {
        $target = match ($newOrderStatus) {
            Order::STATUS_PREPARING => Shipment::STATUS_PREPARING,
            Order::STATUS_SHIPPED => Shipment::STATUS_SHIPPED,
            Order::STATUS_OUT_FOR_DELIVERY => Shipment::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_COMPLETED => Shipment::STATUS_DELIVERED,
            default => null,
        };

        if ($target === null) {
            return;
        }

        $shipment = $order->shipment ?? Shipment::query()->where('order_id', $order->id)->first();

        if (! $shipment) {
            return;
        }

        try {
            $this->transition($shipment, $target, $actor, $actorType);
        } catch (CartException) {
            // Shipment-side state does not gate the order's own transition.
        }
    }

    /**
     * @throws CartException
     */
    public function transition(Shipment $shipment, string $to, User $actor, string $actorType, ?string $notes = null): Shipment
    {
        if (! $shipment->canTransitionTo($to)) {
            throw new CartException(__('shipping.transition_invalid', [
                'from' => __("shipping.status.{$shipment->status}"),
                'to' => __("shipping.status.{$to}"),
            ]));
        }

        $from = $shipment->status;
        $extra = match ($to) {
            Shipment::STATUS_SHIPPED => ['shipped_at' => now()],
            Shipment::STATUS_DELIVERED => ['delivered_at' => now()],
            Shipment::STATUS_FAILED => ['failed_at' => now(), 'failure_reason' => $notes],
            default => [],
        };

        DB::transaction(function () use ($actor, $actorType, $extra, $from, $notes, $shipment, $to): void {
            $applied = Shipment::query()
                ->whereKey($shipment->id)
                ->where('status', $from)
                ->update(array_merge($extra, ['status' => $to, 'updated_at' => now()]));

            if ($applied === 0) {
                throw new CartException(__('shipping.transition_conflict'));
            }

            ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'previous_status' => $from,
                'new_status' => $to,
                'changed_by_user_id' => $actor->id,
                'actor_type' => $actorType,
                'notes' => $notes,
            ]);
        });

        return $shipment->refresh();
    }

    /**
     * @throws CartException
     */
    public function markFailed(Shipment $shipment, string $reason, User $actor, string $actorType): Shipment
    {
        return $this->transition($shipment, Shipment::STATUS_FAILED, $actor, $actorType, $reason);
    }

    /**
     * @throws CartException
     */
    public function markReturned(Shipment $shipment, User $actor, string $actorType, ?string $notes = null): Shipment
    {
        return $this->transition($shipment, Shipment::STATUS_RETURNED, $actor, $actorType, $notes);
    }

    public function setTracking(Shipment $shipment, ?string $trackingNumber, ?string $carrierName): Shipment
    {
        $shipment->update([
            'tracking_number' => $trackingNumber,
            'carrier_name' => $carrierName,
        ]);

        return $shipment;
    }

    /**
     * Match a free-text governorate to its configured zone, falling back to
     * the single catch-all zone (`is_default`) seeded by the shipping
     * migration. Returns null only if no zone exists at all.
     */
    private function resolveZone(?string $governorate): ?ShippingZone
    {
        if ($governorate) {
            $governorate = trim($governorate);
            $zoneId = DB::table('shipping_zone_governorates')
                ->where('governorate', $governorate)
                ->value('shipping_zone_id');

            if ($zoneId) {
                $zone = ShippingZone::query()->where('is_active', true)->find($zoneId);
                if ($zone) {
                    return $zone;
                }
            }
        }

        return ShippingZone::query()->where('is_default', true)->where('is_active', true)->first();
    }
}
