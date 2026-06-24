<?php

namespace App\Http\Requests\Admin;

use App\Models\Vendor;
use App\Rules\CategoriesMatchBusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
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
        return [
            // User account fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone_number'],
            'national_id' => ['required', 'string', 'max:50', 'unique:users,national_id'],

            // Vendor profile fields
            'store_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', Rule::in([
                Vendor::BUSINESS_TYPE_AGRICULTURE,
                Vendor::BUSINESS_TYPE_VETERINARY,
                Vendor::BUSINESS_TYPE_BOTH,
            ])],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'logo' => ['nullable', 'string', 'max:255'],

            // Allowed categories
            'category_ids' => [
                'required',
                'array',
                'min:1',
                new CategoriesMatchBusinessType(
                    $this->input('business_type'),
                    array_map('intval', (array) $this->input('category_ids', [])),
                ),
            ],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'phone_number.unique' => 'This phone number is already registered.',
            'national_id.unique' => 'This national ID is already registered.',
        ];
    }
}
