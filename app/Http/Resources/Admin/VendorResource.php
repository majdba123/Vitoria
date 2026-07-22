<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
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
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => $this->city ? [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ] : null),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? asset('storage/'.$this->logo) : null,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'registration_source' => $this->registration_source,
            'commercial_register_file' => $this->commercial_register_file,
            'commercial_register_url' => $this->commercial_register_file
                ? route('admin.vendors.commercial-register', $this->id)
                : null,
            'paid_amount' => $this->paid_amount,
            'user' => new UserResource($this->whenLoaded('user')),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type,
                'type_label' => \App\Models\Category::typeLabels()[$c->type] ?? $c->type,
                'commission' => $c->commission,
                'subcategories' => $c->relationLoaded('subcategories')
                    ? $c->subcategories->map(fn ($subcategory) => [
                        'id' => $subcategory->id,
                        'category_id' => $subcategory->category_id,
                        'name_ar' => $subcategory->name_ar,
                        'name_en' => $subcategory->name_en,
                    ])->values()
                    : [],
            ])),
            'category_ids' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
