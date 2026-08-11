<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubcategoryRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('subcategories', 'name_ar')->where(fn ($query) => $query->where('category_id', (int) $this->input('category_id')))],
            'name_en' => ['required', 'string', 'max:255', Rule::unique('subcategories', 'name_en')->where(fn ($query) => $query->where('category_id', (int) $this->input('category_id')))],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Please select a valid parent category.',
            'name_ar.unique' => 'This Arabic name is already used in this category.',
            'name_en.unique' => 'This English name is already used in this category.',
        ];
    }
}
