<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    protected function resolvedProductType(): ?string
    {
        $categoryType = $this->category?->type;

        if ($categoryType === \App\Models\Category::TYPE_VETERINARY) {
            return 'veterinary_medicine';
        }

        if ($categoryType === \App\Models\Category::TYPE_AGRICULTURE) {
            if (
                $this->relationLoaded('sharedDetail')
                && $this->sharedDetail
                && $this->sharedDetail->relationLoaded('agriculturalDetail')
            ) {
                return $this->sharedDetail->agriculturalDetail?->agricultural_product_type ?: 'other';
            }

            return \App\Models\Category::TYPE_AGRICULTURE;
        }

        return $categoryType;
    }

    protected function localizedName(): string
    {
        return $this->resource->getLocalizedName(app()->getLocale());
    }

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
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'name' => $this->localizedName(),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description' => $this->description,
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
            'product_type' => $this->resolvedProductType(),
            'commercial_name' => $this->whenLoaded('sharedDetail', fn () => $this->sharedDetail?->commercial_name),
            'barcodes' => $this->whenLoaded('sharedDetail', fn () => $this->sharedDetail?->barcodes),
            'first_photo_url' => $displayPhoto ? asset('storage/'.$displayPhoto->path) : null,
            'fallback_photo_url' => asset('images/product-placeholder.svg'),
            'average_rating' => round((float) ($this->reviews_avg_rating ?? 0), 2),
            'review_count' => (int) ($this->reviews_count ?? 0),
        ];

        if ($this->relationLoaded('category')) {
            $category = $this->category;
            $data['category'] = $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'type_label' => \App\Models\Category::typeLabels()[$category->type] ?? $category->type,
            ] : null;
        }

        if ($this->relationLoaded('subcategory')) {
            $subcategory = $this->subcategory;
            $data['subcategory'] = $subcategory ? [
                'id' => $subcategory->id,
                'category_id' => $subcategory->category_id,
                'name_ar' => $subcategory->name_ar,
                'name_en' => $subcategory->name_en,
            ] : null;
        }

        if ($this->relationLoaded('vendor') && $this->vendor) {
            $vendor = $this->vendor;
            $data['vendor'] = [
                'id' => $vendor->id,
                'store_name' => $vendor->store_name,
                'logo' => $vendor->logo,
                'logo_url' => $vendor->logo ? asset('storage/'.$vendor->logo) : null,
                'user' => $this->shouldExposeVendor($request) && $vendor->relationLoaded('user') && $vendor->user ? [
                    'id' => $vendor->user->id,
                    'name' => $vendor->user->name,
                ] : null,
            ];
        }

        return $data;
    }
}
