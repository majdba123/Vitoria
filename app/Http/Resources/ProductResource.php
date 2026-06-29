<?php

namespace App\Http\Resources;

use App\Http\Resources\Admin\VendorResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    protected function shouldExposeVendor(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User
            && ($user->type === User::TYPE_ADMIN || $user->type === User::TYPE_VENDOR);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price = (float) $this->price;
        $hasActiveDiscount = method_exists($this->resource, 'hasActiveDiscount')
            ? $this->resource->hasActiveDiscount()
            : false;
        $discountedPrice = method_exists($this->resource, 'getDiscountedPrice')
            ? $this->resource->getDiscountedPrice()
            : $price;
        $discountAmount = max($price - $discountedPrice, 0);

        return [
            'id' => $this->id,
            'vendor_id' => $this->when($this->shouldExposeVendor($request), $this->vendor_id),
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'icon_url' => $this->icon ? asset('storage/'.$this->icon) : null,
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/'.$this->image) : null,
            'price' => $this->price,
            'discount_percentage' => $this->discount_percentage,
            'discount_is_active' => $this->discount_is_active,
            'discount_starts_at' => $this->discount_starts_at,
            'discount_ends_at' => $this->discount_ends_at,
            'discount_status' => $this->discount_status,
            'rejection_reason' => $this->rejection_reason,
            'has_active_discount' => $hasActiveDiscount,
            'discounted_price' => number_format($discountedPrice, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'quantity' => $this->quantity,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'category' => $this->whenLoaded('category', function () use ($request): ?array {
                $category = $this->category;

                if (! $category) {
                    return null;
                }

                $data = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                ];

                if ($this->shouldExposeVendor($request)) {
                    $data['commission'] = $category->commission;
                }

                return $data;
            }),
            'photos' => ProductPhotoResource::collection($this->whenLoaded('photos')),
            'vendor' => $this->when(
                $this->shouldExposeVendor($request) && $this->relationLoaded('vendor'),
                fn () => new VendorResource($this->vendor)
            ),
            'average_rating' => round((float) ($this->reviews_avg_rating ?? 0), 2),
            'review_count' => (int) ($this->reviews_count ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
