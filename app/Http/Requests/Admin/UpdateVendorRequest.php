<?php

namespace App\Http\Requests\Admin;

use App\Models\Vendor;
use App\Rules\CategoriesMatchBusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vendor = $this->route('vendor');
        $userId = $vendor->user_id;
        $businessType = $this->input('business_type', $vendor->business_type);
        $categoryIds = $this->has('category_ids')
            ? array_map('intval', (array) $this->input('category_ids', []))
            : $vendor->categories()->pluck('categories.id')->all();

        return [
            // User account fields
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:6'],
            'phone_number' => ['sometimes', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($userId)],
            'national_id' => ['sometimes', 'string', 'max:50', Rule::unique('users', 'national_id')->ignore($userId)],

            // Vendor profile fields
            'store_name' => ['sometimes', 'string', 'max:255'],
            'business_type' => ['sometimes', Rule::in([
                Vendor::BUSINESS_TYPE_AGRICULTURE,
                Vendor::BUSINESS_TYPE_VETERINARY,
                Vendor::BUSINESS_TYPE_BOTH,
            ])],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],

            // Allowed categories
            'category_ids' => [
                'nullable',
                'array',
                new CategoriesMatchBusinessType(
                    $businessType,
                    $categoryIds,
                ),
            ],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }
}
