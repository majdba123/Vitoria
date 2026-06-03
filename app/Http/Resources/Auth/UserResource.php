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
            'preferred_product_type' => $this->preferred_product_type,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
