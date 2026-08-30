<?php

namespace App\Http\Requests\Admin;

use App\Models\VendorSettlement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorSettlementRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', Rule::in(VendorSettlement::METHODS)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['sometimes', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.gt' => __('vendor_ledger.settlement_amount_invalid'),
            'payment_date.before_or_equal' => __('vendor_ledger.settlement_date_future'),
        ];
    }
}
