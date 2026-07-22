<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalProductResource extends JsonResource
{
    protected function resolvedProductType(): ?string
    {
        $categoryType = $this->category?->type;

        return match ($categoryType) {
            'veterinary' => 'veterinary_medicine',
            'agriculture' => $this->sharedDetail?->agriculturalDetail?->agricultural_product_type ?: 'other',
            default => $categoryType,
        };
    }

    protected function resolveSpecializedPayload(): array
    {
        $productType = $this->category?->type;

        return match ($productType) {
            'agriculture' => [
                'type' => 'agriculture',
                'shared' => $this->sharedPayload(),
                'agricultural_detail' => $this->sharedDetail?->agriculturalDetail?->toArray(),
            ],
            'veterinary' => [
                'type' => 'veterinary',
                'shared' => $this->sharedPayload(),
                'veterinary_detail' => $this->sharedDetail?->veterinaryDetail?->toArray(),
            ],
            default => [
                'type' => $productType,
                'shared' => $this->sharedPayload(),
            ],
        };
    }

    protected function sharedPayload(): array
    {
        $sharedDetail = $this->sharedDetail;

        return [
            'product_id' => $this->id,
            'product_type' => $this->resolvedProductType(),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'commercial_name' => $sharedDetail?->commercial_name,
            'aliases' => $sharedDetail?->aliases,
            'barcodes' => $sharedDetail?->barcodes,
            'sku' => $sharedDetail?->sku,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'manufacturer_name_ar' => $sharedDetail?->manufacturer_name_ar,
            'manufacturer_name_en' => $sharedDetail?->manufacturer_name_en,
            'brand_name_ar' => $sharedDetail?->brand_name_ar,
            'brand_name_en' => $sharedDetail?->brand_name_en,
            'country_of_origin' => $sharedDetail?->country_of_origin,
            'registration_number' => $sharedDetail?->registration_number,
            'registration_status' => $sharedDetail?->registration_status,
            'package_size' => $sharedDetail?->package_size,
            'package_unit' => $sharedDetail?->package_unit,
            'short_description' => $sharedDetail?->short_description,
            'approved_description' => $sharedDetail?->approved_description,
            'keywords' => $sharedDetail?->keywords,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge([
            'id' => $this->id,
            'name' => $this->resource->getLocalizedName(app()->getLocale()),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'description' => $this->description,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'has_active_discount' => method_exists($this->resource, 'hasActiveDiscount')
                ? $this->resource->hasActiveDiscount()
                : false,
            'discount_percentage' => $this->discount_percentage,
            'category' => $this->whenLoaded('category', fn (): ?array => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type,
            ] : null),
            'subcategory' => $this->whenLoaded('subcategory', fn (): ?array => $this->subcategory ? [
                'id' => $this->subcategory->id,
                'category_id' => $this->subcategory->category_id,
                'name_ar' => $this->subcategory->name_ar,
                'name_en' => $this->subcategory->name_en,
            ] : null),
            'vendor' => $this->whenLoaded('vendor', fn (): ?array => $this->vendor ? [
                'id' => $this->vendor->id,
                'store_name' => $this->vendor->store_name,
                'status' => $this->vendor->status,
                'is_active' => (bool) $this->vendor->is_active,
                'logo' => $this->vendor->logo,
                'logo_url' => $this->vendor->logo ? asset('storage/'.$this->vendor->logo) : null,
            ] : null),
            'photos' => $this->whenLoaded('photos', fn (): array => $this->photos->map(fn ($photo): array => [
                'id' => $photo->id,
                'path' => $photo->path,
                'url' => asset('storage/'.$photo->path),
                'image_type' => $photo->image_type,
                'sort_order' => $photo->sort_order,
                'is_primary' => (bool) $photo->is_primary,
            ])->values()->all()),
        ], $this->resolveSpecializedPayload());
    }
}
