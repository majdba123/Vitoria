<?php

namespace App\Http\Resources;

use App\Http\Resources\Admin\VendorResource;
use App\Http\Resources\PublicVendorResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
     * Agricultural/veterinary spec fields (dosage, application rates, withdrawal
     * periods, warnings, ...) are buying-decision data for this marketplace's
     * domain, not internal review notes, so every viewer — including anonymous
     * shoppers — gets them (stakeholder review #19). Nothing in these detail
     * models holds vendor-private or internal-only data.
     */
    protected function shouldExposeSpecializedDetails(Request $request): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photos = $this->whenLoaded('photos') ? $this->photos : collect();
        $displayPhoto = $photos->firstWhere('image_type', \App\Models\ProductPhoto::TYPE_PRIMARY)
            ?? $photos->where('is_primary', true)->first()
            ?? $photos->first();
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
            'rejection_reason' => $this->when($this->shouldExposeVendor($request), $this->rejection_reason),
            'has_active_discount' => $hasActiveDiscount,
            'discounted_price' => number_format($discountedPrice, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'quantity' => $this->quantity,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'product_type' => $this->resolvedProductType(),
            'first_photo_url' => $displayPhoto ? asset('storage/'.$displayPhoto->path) : null,
            'fallback_photo_url' => asset('images/product-placeholder.svg'),
            'shared_detail' => $this->whenLoaded('sharedDetail', function (): ?array {
                $detail = $this->sharedDetail;

                if (! $detail) {
                    return null;
                }

                return [
                    'commercial_name' => $detail->commercial_name,
                    'aliases' => $detail->aliases,
                    'barcodes' => $detail->barcodes,
                    'sku' => $detail->sku,
                    'manufacturer_name_ar' => $detail->manufacturer_name_ar,
                    'manufacturer_name_en' => $detail->manufacturer_name_en,
                    'brand_name_ar' => $detail->brand_name_ar,
                    'brand_name_en' => $detail->brand_name_en,
                    'country_of_origin' => $detail->country_of_origin,
                    'registration_number' => $detail->registration_number,
                    'registration_status' => $detail->registration_status,
                    'package_size' => $detail->package_size,
                    'package_unit' => $detail->package_unit,
                    'short_description' => $detail->short_description,
                    'approved_description' => $detail->approved_description,
                    'keywords' => $detail->keywords,
                ];
            }),
            'agricultural_detail' => $this->when(
                $this->shouldExposeSpecializedDetails($request)
                && $this->relationLoaded('sharedDetail')
                && $this->sharedDetail
                && $this->sharedDetail->relationLoaded('agriculturalDetail'),
                fn (): ?array => $this->sharedDetail?->agriculturalDetail?->toArray()
            ),
            'veterinary_detail' => $this->when(
                $this->shouldExposeSpecializedDetails($request)
                && $this->relationLoaded('sharedDetail')
                && $this->sharedDetail
                && $this->sharedDetail->relationLoaded('veterinaryDetail'),
                fn (): ?array => $this->sharedDetail?->veterinaryDetail?->toArray()
            ),
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
            'subcategory' => $this->whenLoaded('subcategory', function (): ?array {
                $subcategory = $this->subcategory;

                if (! $subcategory) {
                    return null;
                }

                return [
                    'id' => $subcategory->id,
                    'category_id' => $subcategory->category_id,
                    'name_ar' => $subcategory->name_ar,
                    'name_en' => $subcategory->name_en,
                ];
            }),
            'photos' => ProductPhotoResource::collection($this->whenLoaded('photos')),
            // Every shopper gets a public-safe vendor identity (name, logo, city) so
            // they know who they're buying from; only admin/vendor viewers get the
            // full resource (contact details, documents, payout figures).
            'vendor' => $this->whenLoaded('vendor', fn () => $this->shouldExposeVendor($request)
                ? new VendorResource($this->vendor)
                : new PublicVendorResource($this->vendor)),
            'average_rating' => round((float) ($this->reviews_avg_rating ?? 0), 2),
            'review_count' => (int) ($this->reviews_count ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
