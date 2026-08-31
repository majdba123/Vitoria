<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'payment_way' => ['nullable', 'string', 'in:cash'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => __('validation.application.cart_empty'),
            'items.min' => __('validation.application.cart_empty'),
            'items.max' => __('validation.application.checkout_items_max'),
            'items.*.quantity.max' => __('validation.application.item_quantity_max'),
            'items.*.product_id.distinct' => __('validation.application.duplicate_products'),
        ];
    }
}
