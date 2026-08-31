<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'type' => ['required', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:4096'],
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.application.category_name_required'),
            'type.required' => __('validation.application.category_type_required'),
            'type.in' => __('validation.application.category_type_invalid'),
            'logo.image' => __('validation.application.category_image_invalid'),
            'logo.max' => __('validation.application.category_image_max'),
            'commission.numeric' => __('validation.application.commission_numeric'),
            'commission.min' => __('validation.application.commission_min'),
            'commission.max' => __('validation.application.commission_max'),
        ];
    }
}
