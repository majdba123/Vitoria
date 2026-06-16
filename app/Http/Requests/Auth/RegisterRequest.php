<?php

namespace App\Http\Requests\Auth;

use App\Models\Vendor;
use App\Rules\CategoriesMatchBusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Prepare incoming registration data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('category_ids') && $this->filled('category_id')) {
            $this->merge([
                'category_ids' => [$this->input('category_id')],
            ]);
        }
    }

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
            'account_type' => ['nullable', 'string', 'in:user,vendor'],
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone_number'],
            'national_id' => ['required', 'string', 'max:50', 'unique:users,national_id'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'membership_number' => ['required', 'string', 'max:100', 'unique:users,membership_number'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'store_name' => ['required_if:account_type,vendor', 'string', 'max:255'],
            'business_type' => ['required_if:account_type,vendor', Rule::in([
                Vendor::BUSINESS_TYPE_AGRICULTURE,
                Vendor::BUSINESS_TYPE_VETERINARY,
                Vendor::BUSINESS_TYPE_BOTH,
            ])],
            'category_ids' => [
                'required_if:account_type,vendor',
                'array',
                'min:1',
                new CategoriesMatchBusinessType(
                    $this->input('business_type'),
                    array_map('intval', (array) $this->input('category_ids', [])),
                ),
            ],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'commercial_register_file' => ['required_if:account_type,vendor', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
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
            'phone_number.required' => 'Phone number is required.',
            'phone_number.unique' => 'This phone number is already registered.',
            'national_id.required' => 'National ID is required.',
            'national_id.unique' => 'This national ID is already registered.',
            'age.required' => 'Age is required.',
            'age.min' => 'Please enter a valid age.',
            'age.max' => 'Please enter a valid age.',
            'membership_number.required' => 'Membership number is required.',
            'membership_number.unique' => 'This membership number is already registered.',
            'city_id.required' => 'Please select your city.',
            'city_id.exists' => 'Selected city is invalid.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email is already registered.',
            'email.email' => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'account_type.in' => 'Please select a valid account type.',
            'store_name.required_if' => 'Store name is required for merchant accounts.',
            'business_type.required_if' => 'Please select a merchant business type.',
            'business_type.in' => 'Please select a valid merchant business type.',
            'category_ids.required_if' => 'Please select at least one merchant category.',
            'category_ids.array' => 'Please select valid merchant categories.',
            'category_ids.min' => 'Please select at least one merchant category.',
            'category_ids.*.exists' => 'One of the selected merchant categories is invalid.',
            'commercial_register_file.required_if' => 'Commercial registration document is required for merchant accounts.',
            'commercial_register_file.mimes' => 'Commercial registration document must be a PDF, DOC, DOCX, JPG, JPEG, or PNG file.',
            'commercial_register_file.max' => 'Commercial registration document may not be greater than 5 MB.',
        ];
    }
}
