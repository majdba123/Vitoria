<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone_number'],
            'national_id' => ['required', 'string', 'max:50', 'unique:users,national_id'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120'],
            'membership_number' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:users,membership_number'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            // Vendor and syndicate accounts are deliberately excluded: both
            // need a paired row (vendors / syndicates) created atomically
            // with the user, which only their dedicated creation endpoints
            // (POST /api/admin/vendors, /api/admin/syndicates) do. Allowing
            // type here would let this endpoint mint a "vendor" user with no
            // vendors row — an account that's locked out of every vendor
            // feature since User::managedVendor() would always return null.
            'type' => ['sometimes', 'integer', Rule::in([User::TYPE_USER, User::TYPE_ADMIN, User::TYPE_EMPLOYEE])],
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
            'phone_number.unique' => __('validation.application.phone_taken'),
            'national_id.unique' => __('validation.application.national_id_taken'),
            'membership_number.unique' => __('validation.application.membership_number_taken'),
            'email.unique' => __('validation.application.email_taken'),
        ];
    }
}
