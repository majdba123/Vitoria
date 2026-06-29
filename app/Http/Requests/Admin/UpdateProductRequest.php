<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
        return [
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'quantity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'discount_starts_at' => ['nullable', 'date'],
            'discount_ends_at' => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
            'primary_photo_id' => ['nullable', 'integer', 'exists:product_photos,id'],
        ];
    }
}
