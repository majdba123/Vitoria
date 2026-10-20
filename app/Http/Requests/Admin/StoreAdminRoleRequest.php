<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRoleRequest extends FormRequest
{
    /**
     * Route middleware (`roles.manage`) and AdminRbacService enforce authority.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('roles', 'key')],
            'name_en' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(AdminPermissions::all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.regex' => __('admin.rbac_validation_key_format'),
            'key.unique' => __('admin.rbac_validation_key_taken'),
            'permissions.*.in' => __('admin.rbac_error_invalid_permission'),
        ];
    }
}
