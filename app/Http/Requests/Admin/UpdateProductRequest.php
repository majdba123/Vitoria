<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\InteractsWithProductDetails;
use App\Models\ProductPhoto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    use InteractsWithProductDetails;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('subcategories', 'id')->where(function ($query) {
                    $categoryId = (int) (request()->input('category_id') ?: $this->route('product')?->category_id);

                    return $query->where('category_id', $categoryId);
                }),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'quantity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'discount_starts_at' => ['sometimes', 'nullable', 'date'],
            'discount_ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:discount_starts_at'],
            'primary_photo_id' => ['sometimes', 'nullable', 'integer', $this->productPhotoExistsRule()],
            'photo_ids' => ['sometimes', 'array'],
            'photo_ids.*' => ['required', 'integer', $this->productPhotoExistsRule()],
            'photo_types' => ['sometimes', 'array'],
            'photo_types.*' => ['required', Rule::in(ProductPhoto::allowedTypes())],
            'photo_sort_orders' => ['sometimes', 'array'],
            'photo_sort_orders.*' => ['nullable', 'integer', 'min:1'],
        ], $this->localizedNameRules(), $this->sharedDetailRules(), $this->agriculturalDetailRules(), $this->veterinaryDetailRules());
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeProductDetailPayload();
    }
}
