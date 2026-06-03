<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductTypePreferenceRequest extends FormRequest
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
            'preferred_product_type' => ['required', 'string', Rule::in([
                Category::TYPE_AGRICULTURE,
                Category::TYPE_VETERINARY,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferred_product_type.required' => 'يرجى اختيار نوع صحيح للمتابعة.',
            'preferred_product_type.in' => 'نوع التصفح المحدد غير صالح.',
        ];
    }
}
