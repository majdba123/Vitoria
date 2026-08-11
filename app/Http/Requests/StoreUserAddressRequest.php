<?php

namespace App\Http\Requests;

use App\Models\UserAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', Rule::in(UserAddress::LABELS)],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'governorate' => ['required', 'string', 'max:120'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'street' => ['nullable', 'string', 'max:180'],
            'building' => ['nullable', 'string', 'max:120'],
            'floor' => ['nullable', 'string', 'max:50'],
            'landmark' => ['nullable', 'string', 'max:180'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
