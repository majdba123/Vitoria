<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'logo' => ['nullable', 'image', 'max:4096'],
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Please select a valid category type.',
            'logo.image' => 'The category image must be a valid image file.',
            'logo.max' => 'The category image may not be greater than 4 MB.',
            'commission.numeric' => 'Commission must be a valid number.',
            'commission.min' => 'Commission cannot be less than 0%.',
            'commission.max' => 'Commission cannot be greater than 100%.',
        ];
    }
}
