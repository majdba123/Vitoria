<?php

namespace App\Services\Import\Importers;

use App\Models\Product;
use App\Models\Vendor;
use App\Services\Import\AbstractCsvImporter;
use App\Services\ProductService;
use Illuminate\Validation\Rule;

/**
 * Imports "basic profile" products only (no photos, no agricultural/veterinary
 * detail) — matching ProductController::STORE_PROFILE_BASIC.
 */
class ProductImporter extends AbstractCsvImporter
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly bool $forVendor = false,
    ) {}

    public function headers(): array
    {
        $headers = [
            'name_ar', 'name_en', 'category_id', 'subcategory_id',
            'description', 'price', 'discount_percentage',
            'discount_starts_at', 'discount_ends_at', 'quantity', 'is_active',
        ];

        if (! $this->forVendor) {
            array_unshift($headers, 'vendor_id');
        }

        return $headers;
    }

    public function exampleRows(): array
    {
        $row = [
            'اسم المنتج بالعربي', 'Product English Name', '1', '',
            'Product description', '100', '10',
            '2026-01-01', '2026-01-31', '50', '1',
        ];

        if (! $this->forVendor) {
            array_unshift($row, '1');
        }

        return [$row];
    }

    protected function normalizeRow(array $row, array $context): array
    {
        if (array_key_exists('is_active', $row) && $row['is_active'] !== null) {
            $row['is_active'] = filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return $row;
    }

    protected function rules(array $row, array $context): array
    {
        $forcedVendor = $context['vendor'] ?? null;
        $vendorId = $forcedVendor?->id ?? (int) ($row['vendor_id'] ?? 0);

        $rules = [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required', 'integer', 'exists:categories,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($vendorId): void {
                    if ($vendorId > 0 && ! Vendor::query()->whereKey($vendorId)->whereHas(
                        'categories',
                        fn ($query) => $query->where('categories.id', (int) $value)
                    )->exists()) {
                        $fail(__('Selected category is not assigned to this vendor.'));
                    }
                },
            ],
            'subcategory_id' => [
                'nullable', 'integer',
                Rule::exists('subcategories', 'id')->where(fn ($query) => $query->where('category_id', (int) ($row['category_id'] ?? 0))),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_starts_at' => ['nullable', 'date'],
            'discount_ends_at' => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
            'quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if (! $this->forVendor) {
            $rules['vendor_id'] = ['required', 'integer', 'exists:vendors,id'];
        }

        return $rules;
    }

    protected function persist(array $row, array $context): void
    {
        $vendor = $context['vendor'] ?? Vendor::query()->find($row['vendor_id'] ?? null);

        foreach (['subcategory_id', 'description', 'discount_percentage', 'discount_starts_at', 'discount_ends_at', 'is_active'] as $optional) {
            if (array_key_exists($optional, $row) && $row[$optional] === null) {
                unset($row[$optional]);
            }
        }

        $discountPercentage = isset($row['discount_percentage']) ? (float) $row['discount_percentage'] : null;
        $discountPercentage = $discountPercentage !== null && $discountPercentage > 0 ? $discountPercentage : null;
        $row['discount_percentage'] = $discountPercentage;
        $row['discount_is_active'] = $discountPercentage !== null;
        $row['discount_status'] = Product::resolveDiscountStatus(
            $row['discount_is_active'],
            $discountPercentage,
            $row['discount_starts_at'] ?? null,
            $row['discount_ends_at'] ?? null,
        );

        unset($row['vendor_id']);

        $this->productService->create($vendor, $row);
    }
}
