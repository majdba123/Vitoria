<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'national_id' => $this->national_id,
            'age' => $this->age,
            'membership_number' => $this->membership_number,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar ? '/storage/'.$this->avatar : null,
            'type' => $this->type,
            'syndicate' => $this->whenLoaded('syndicate', fn () => $this->syndicate ? [
                'id' => $this->syndicate->id,
                'name' => $this->syndicate->name,
                'type' => $this->syndicate->type,
                'status' => $this->syndicate->status,
            ] : null),
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => $this->city ? ['id' => $this->city->id, 'name' => $this->city->name] : null),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'preferred_product_type' => $this->preferred_product_type,
            'employee_roles' => $this->whenLoaded('employeeRoles', fn () => $this->employeeRoles->map(fn ($role) => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => app()->getLocale() === 'ar' ? $role->name_ar : $role->name_en,
            ])),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // orders_count is always present (defaulting to 0) once withCount
            // has run, so it's the reliable signal that the customer-view
            // aggregates were attached at all — total_purchases/last_order_at
            // are null instead of 0 for a buyer with no orders yet.
            'orders_count' => $this->when(isset($this->orders_count), fn () => (int) $this->orders_count),
            'total_purchases' => $this->when(isset($this->orders_count), fn () => (float) ($this->total_purchases ?? 0)),
            'last_order_at' => $this->when(isset($this->orders_count), fn () => $this->last_order_at),
        ];
    }
}
