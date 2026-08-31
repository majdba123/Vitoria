<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone_number' => ['sometimes', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('users')->ignore($userId)],
            'timezone' => ['sometimes', 'nullable', 'string', Rule::in(timezone_identifiers_list())],
            'locale' => ['sometimes', 'nullable', Rule::in(['en', 'ar'])],
            'preferred_product_type' => ['sometimes', 'nullable', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => __('validation.application.name_invalid'),
            'name.max' => __('validation.application.name_max'),
            'email.email' => __('validation.application.email_invalid'),
            'email.unique' => __('validation.application.email_taken'),
            'phone_number.regex' => __('validation.application.phone_invalid'),
            'phone_number.unique' => __('validation.application.phone_taken'),
        ];
    }
}
