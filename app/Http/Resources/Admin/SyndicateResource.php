<?php

namespace App\Http\Resources\Admin;

use App\Models\Category;
use App\Models\Syndicate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyndicateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => Category::typeLabels()[$this->type] ?? $this->type,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'status_label' => Syndicate::statusLabels()[$this->status] ?? $this->status,
            'is_active' => $this->status === Syndicate::STATUS_ACTIVE,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? asset('storage/'.$this->logo) : asset('images/syndicate-placeholder.svg'),
            'categories_count' => (int) ($this->categories_count ?? 0),
            'vendors_count' => (int) ($this->vendors_count ?? 0),
            'products_count' => (int) ($this->products_count ?? 0),
            'orders_count' => (int) ($this->orders_count ?? 0),
            'completed_orders_count' => (int) ($this->completed_orders_count ?? 0),
            'total_sales' => round((float) ($this->total_sales ?? 0), 2),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number,
                'type' => $this->user->type,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
