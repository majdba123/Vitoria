<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            // Public self-registration always creates a customer account.
            // Vendor accounts are created only through the admin-managed
            // flow (App\Http\Controllers\Api\Admin\VendorController), so no
            // account_type/role field is accepted here — accepting one would
            // let a forged request choose its own role.
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/', 'unique:users,phone_number'],
            'national_id' => ['required', 'string', 'max:50', 'unique:users,national_id'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'membership_number' => ['required', 'string', 'max:100', 'unique:users,membership_number'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            // A map pin is only meaningful as a pair, so each coordinate is
            // required as soon as the other one is present.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
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
            'phone_number.regex' => 'Please provide a valid phone number.',
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
        ];
    }
}
