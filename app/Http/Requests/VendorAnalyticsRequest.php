<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorAnalyticsRequest extends FormRequest
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
            'range' => ['sometimes', 'string', Rule::in(['today', '7_days', '30_days', '90_days', 'this_year', 'custom', 'all'])],
            'date_from' => ['nullable', 'date', 'required_if:range,custom'],
            'date_to' => ['nullable', 'date', 'required_if:range,custom', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'product_status' => ['nullable', 'string', Rule::in([Product::STATUS_PENDING, Product::STATUS_APPROVED, Product::STATUS_REJECTED])],
            'active' => ['nullable', 'boolean'],
            'order_status' => ['nullable', 'string', Rule::in(array_keys(Order::TRANSITIONS))],
            'sort' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null}
     */
    public function period(): array
    {
        $key = (string) $this->input('range', '30_days');
        $today = CarbonImmutable::today();

        return match ($key) {
            'today' => ['key' => $key, 'from' => $today->startOfDay(), 'to' => $today->endOfDay()],
            '7_days' => ['key' => $key, 'from' => $today->subDays(6)->startOfDay(), 'to' => $today->endOfDay()],
            '90_days' => ['key' => $key, 'from' => $today->subDays(89)->startOfDay(), 'to' => $today->endOfDay()],
            'this_year' => ['key' => $key, 'from' => $today->startOfYear(), 'to' => $today->endOfDay()],
            'custom' => [
                'key' => $key,
                'from' => CarbonImmutable::parse((string) $this->input('date_from'))->startOfDay(),
                'to' => CarbonImmutable::parse((string) $this->input('date_to'))->endOfDay(),
            ],
            'all' => ['key' => $key, 'from' => null, 'to' => null],
            default => ['key' => '30_days', 'from' => $today->subDays(29)->startOfDay(), 'to' => $today->endOfDay()],
        };
    }

    public function perPage(): int
    {
        return min(max((int) $this->input('per_page', 15), 1), 50);
    }
}
