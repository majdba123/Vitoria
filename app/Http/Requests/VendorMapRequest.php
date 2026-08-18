<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorMapRequest extends FormRequest
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
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'business_type' => ['nullable', Rule::in([
                Vendor::BUSINESS_TYPE_AGRICULTURE,
                Vendor::BUSINESS_TYPE_VETERINARY,
                Vendor::BUSINESS_TYPE_BOTH,
            ])],
            'status' => ['nullable', Rule::in([
                Vendor::STATUS_PENDING,
                Vendor::STATUS_ACTIVE,
                Vendor::STATUS_INACTIVE,
            ])],
        ];
    }

    /**
     * @return array{city_id: int|null, business_type: string|null, status: string|null}
     */
    public function filters(): array
    {
        return [
            'city_id' => $this->filled('city_id') ? (int) $this->input('city_id') : null,
            'business_type' => $this->filled('business_type') ? (string) $this->input('business_type') : null,
            'status' => $this->filled('status') ? (string) $this->input('status') : null,
        ];
    }
}
