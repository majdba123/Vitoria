<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRoleRequest extends FormRequest
{
    /**
     * Route middleware (`roles.manage`) and AdminRbacService enforce authority.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The role key is immutable once created — it is what audit rows and
     * assignments refer to.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:100'],
            'name_ar' => ['sometimes', 'required', 'string', 'max:100'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(AdminPermissions::all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permissions.*.in' => __('admin.rbac_error_invalid_permission'),
        ];
    }
}
