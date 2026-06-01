<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    protected function shouldExposeVendor(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User
            && ($user->type === User::TYPE_ADMIN || $user->type === User::TYPE_VENDOR);
    }

    /**
     * Slim representation for product listing / index endpoints.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photos = $this->whenLoaded('photos') ? $this->photos : collect();
        $price = (float) $this->price;
        $hasActiveDiscount = method_exists($this->resource, 'hasActiveDiscount')
            ? $this->resource->hasActiveDiscount()
            : false;
        $discountedPrice = method_exists($this->resource, 'getDiscountedPrice')
            ? $this->resource->getDiscountedPrice()
            : $price;
        $discountAmount = max($price - $discountedPrice, 0);

        // Use primary photo if available, otherwise use first photo
        $displayPhoto = $photos->where('is_primary', true)->first() ?? $photos->first();

        $data = [
            'id' => $this->id,
            'vendor_id' => $this->when($this->shouldExposeVendor($request), $this->vendor_id),
            'category_id' => $this->subcategory?->category_id,
            'subcategory_id' => $this->subcategory_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'discount_percentage' => $this->discount_percentage,
            'discount_is_active' => $this->discount_is_active,
            'discount_starts_at' => $this->discount_starts_at,
            'discount_ends_at' => $this->discount_ends_at,
            'discount_status' => $this->discount_status,
            'has_active_discount' => $hasActiveDiscount,
            'discounted_price' => number_format($discountedPrice, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'quantity' => $this->quantity,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'first_photo_url' => $displayPhoto ? '/storage/'.$displayPhoto->path : null,
            'average_rating' => round((float) ($this->reviews_avg_rating ?? 0), 2),
            'review_count' => (int) ($this->reviews_count ?? 0),
        ];

        if ($this->shouldExposeVendor($request) && $this->relationLoaded('vendor') && $this->vendor) {
            $vendor = $this->vendor;
            $data['vendor'] = [
                'id' => $vendor->id,
                'store_name' => $vendor->store_name,
                'user' => $vendor->relationLoaded('user') && $vendor->user ? [
                    'id' => $vendor->user->id,
                    'name' => $vendor->user->name,
                ] : null,
            ];
        }

        return $data;
    }
}
