<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Syndicate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSyndicateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->hasFile('logo')) {
            $this->files->remove('logo');
            $this->request->remove('logo');
        }
    }

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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($syndicateUserId)],
            'phone' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('users', 'phone_number')->ignore($syndicateUserId)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'type' => ['sometimes', 'required', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'status' => ['sometimes', Rule::in([Syndicate::STATUS_ACTIVE, Syndicate::STATUS_INACTIVE])],
            'logo' => ['sometimes', 'nullable', 'image', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.image' => 'The logo must be a valid image.',
            'logo.max' => 'The logo may not be greater than 4 MB.',
        ];
    }
}
