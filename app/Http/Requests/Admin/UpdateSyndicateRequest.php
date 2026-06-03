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
        }
    }

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
        $syndicate = $this->route('syndicate');
        $syndicateUserId = $syndicate instanceof Syndicate ? $syndicate->user_id : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($syndicateUserId)],
            'phone' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('users', 'phone_number')->ignore($syndicateUserId)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'type' => ['sometimes', 'required', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'status' => ['sometimes', Rule::in([Syndicate::STATUS_ACTIVE, Syndicate::STATUS_INACTIVE])],
            'logo' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.image' => 'يجب أن يكون الشعار صورة صالحة.',
            'logo.mimes' => 'يجب أن يكون الشعار من نوع jpg أو jpeg أو png أو webp.',
            'logo.max' => 'يجب ألا يتجاوز حجم الشعار 2 ميجابايت.',
        ];
    }
}
