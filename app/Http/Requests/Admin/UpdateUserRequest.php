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
            // Vendor and syndicate are both included here (unlike
            // StoreUserRequest, which excludes them) — an existing vendor or
            // syndicate account edited through this generic form always
            // resubmits its current type (see admin/users/edit.blade.php),
            // so rejecting it would break saving any other field on that
            // account, not just prevent new broken ones from being created.
            // The closure below still blocks *widening* into vendor/syndicate
            // from anything else: only resubmitting the account's own current
            // type is allowed for those two, exactly like StoreUserRequest
            // blocks them outright for brand-new accounts (both guard the
            // same invariant — a vendor/syndicate-typed user must always have
            // a paired vendors/syndicates row created atomically with it via
            // the dedicated endpoints, never via this generic one).
            'type' => [
                'sometimes', 'nullable', 'integer',
                Rule::in([User::TYPE_USER, User::TYPE_ADMIN, User::TYPE_VENDOR, User::TYPE_SYNDICATE, User::TYPE_EMPLOYEE]),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $target = (int) $value;

                    if (in_array($target, [User::TYPE_VENDOR, User::TYPE_SYNDICATE], true) && $target !== (int) $this->route('user')->type) {
                        $fail(__('Vendor and syndicate accounts can only be created through their dedicated endpoints.'));
                    }
                },
            ],
        ];
    }
}
