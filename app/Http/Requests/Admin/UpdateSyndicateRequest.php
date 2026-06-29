<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Syndicate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSyndicateRequest extends FormRequest
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
        $syndicate = $this->route('syndicate');
        $syndicateUserId = $syndicate instanceof Syndicate ? $syndicate->user_id : null;

        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($syndicateUserId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users', 'phone_number')->ignore($syndicateUserId)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'type' => ['sometimes', 'nullable', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'status' => ['sometimes', 'nullable', Rule::in([Syndicate::STATUS_ACTIVE, Syndicate::STATUS_INACTIVE])],
            'logo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.image' => 'The syndicate image must be a valid image file.',
            'logo.max' => 'The syndicate image may not be greater than 4 MB.',
        ];
    }
}
