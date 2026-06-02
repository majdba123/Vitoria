<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Syndicate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSyndicateRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:6'],
            'type' => ['required', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'status' => ['sometimes', Rule::in([Syndicate::STATUS_ACTIVE, Syndicate::STATUS_INACTIVE])],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ];
    }
}
