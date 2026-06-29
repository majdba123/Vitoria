<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')->id;

        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($userId)],
            'national_id' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('users', 'national_id')->ignore($userId)],
            'age' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120'],
            'membership_number' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('users', 'membership_number')->ignore($userId)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'type' => ['sometimes', 'nullable', 'integer', Rule::in([User::TYPE_USER, User::TYPE_ADMIN, User::TYPE_VENDOR, User::TYPE_EMPLOYEE])],
        ];
    }
}
