<?php

namespace App\Http\Requests\ImportShipments;

use App\Models\ImportShipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImportShipmentRequest extends FormRequest
{
    /**
     * Route-group middleware (admin / employee / syndicate) already gates access.
     */
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
            'merchant_name' => ['required', 'string', 'max:255'],
            'merchant_register_number' => ['required', 'string', 'max:100'],
            'merchant_address' => ['required', 'string', 'max:500'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'port_id' => ['required', 'integer', Rule::exists('ports', 'id')->where('is_active', true)],
            'syndicate_id' => ['nullable', 'integer', Rule::exists('syndicates', 'id')],
            'stickers_count' => ['required', 'integer', 'min:0', 'max:1000000'],
            'sticker_price' => ['required', 'integer', Rule::in(ImportShipment::STICKER_PRICES)],
            'clearance_number' => ['nullable', 'string', 'max:100'],
            'release_decision' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.country' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000000'],
            'items.*.total_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sticker_price.in' => __('import_shipments.validation_sticker_price'),
            'port_id.exists' => __('import_shipments.validation_port'),
            'items.required' => __('import_shipments.validation_items_required'),
            'items.min' => __('import_shipments.validation_items_required'),
        ];
    }
}
