<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubcategoryRequest extends FormRequest
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
        $subcategoryId = $this->route('subcategory')?->id;
        $categoryId = (int) ($this->input('category_id') ?: $this->route('subcategory')?->category_id);

        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('subcategories', 'name_ar')->ignore($subcategoryId)->where(fn ($query) => $query->where('category_id', $categoryId))],
            'name_en' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('subcategories', 'name_en')->ignore($subcategoryId)->where(fn ($query) => $query->where('category_id', $categoryId))],
        ];
    }
}
