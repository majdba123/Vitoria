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
            'phone_number.required' => __('validation.application.phone_required'),
            'phone_number.regex' => __('validation.application.phone_invalid'),
            'phone_number.unique' => __('validation.application.phone_taken'),
            'national_id.required' => __('validation.application.national_id_required'),
            'national_id.unique' => __('validation.application.national_id_taken'),
            'age.required' => __('validation.application.age_required'),
            'age.min' => __('validation.application.age_invalid'),
            'age.max' => __('validation.application.age_invalid'),
            'membership_number.required' => __('validation.application.membership_number_required'),
            'membership_number.unique' => __('validation.application.membership_number_taken'),
            'city_id.required' => __('validation.application.city_required'),
            'city_id.exists' => __('validation.application.city_invalid'),
            'email.required' => __('validation.application.email_required'),
            'email.unique' => __('validation.application.email_taken'),
            'email.email' => __('validation.application.email_invalid'),
            'password.required' => __('validation.application.password_required'),
            'password.min' => __('validation.application.password_min'),
            'password.confirmed' => __('validation.application.password_confirmation'),
        ];
    }
}
