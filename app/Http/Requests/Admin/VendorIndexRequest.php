<?php

namespace App\Http\Requests\Admin;

use App\Models\Vendor;
use App\Support\SyriaGovernorates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in([Vendor::STATUS_PENDING, Vendor::STATUS_ACTIVE, Vendor::STATUS_INACTIVE])],
            'business_type' => ['sometimes', Rule::in([Vendor::BUSINESS_TYPE_AGRICULTURE, Vendor::BUSINESS_TYPE_VETERINARY, Vendor::BUSINESS_TYPE_BOTH])],
            'category_type' => ['sometimes', Rule::in([Vendor::BUSINESS_TYPE_AGRICULTURE, Vendor::BUSINESS_TYPE_VETERINARY])],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'governorate' => ['sometimes', 'string', Rule::in(collect(SyriaGovernorates::ALL)->pluck('key')->all())],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
