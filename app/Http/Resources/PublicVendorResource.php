<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-safe vendor identity: what a shopper may see on a product page or a
 * vendor storefront. Never include email, phone, national ID, commercial
 * register file, payout amounts, or anything else from the vendor's owning
 * user account — those stay on the admin/vendor-only VendorResource.
 */
class PublicVendorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'business_type' => $this->business_type,
            'business_type_label' => \App\Models\Vendor::businessTypeLabels()[$this->business_type] ?? $this->business_type,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->whenLoaded('city', fn () => $this->city ? [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ] : null),
            'logo_url' => $this->logo ? asset('storage/'.$this->logo) : null,
            'is_active' => $this->is_active,
        ];
    }
}
