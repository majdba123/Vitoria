<?php

namespace App\Rules;

use App\Models\Category;
use App\Models\Vendor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CategoriesMatchBusinessType implements ValidationRule
{
    /**
     * @param  list<int>  $categoryIds
     */
    public function __construct(
        protected ?string $businessType,
        protected array $categoryIds,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->businessType || empty($this->categoryIds)) {
            return;
        }

        $allowedTypes = match ($this->businessType) {
            Vendor::BUSINESS_TYPE_AGRICULTURE => [Category::TYPE_AGRICULTURE],
            Vendor::BUSINESS_TYPE_VETERINARY => [Category::TYPE_VETERINARY],
            Vendor::BUSINESS_TYPE_BOTH => [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY],
            default => [],
        };

        if ($allowedTypes === []) {
            $fail('Please select a valid vendor business type.');

            return;
        }

        $invalidExists = Category::query()
            ->whereIn('id', $this->categoryIds)
            ->whereNotIn('type', $allowedTypes)
            ->exists();

        if ($invalidExists) {
            $fail('Selected categories must match the vendor business type.');
        }
    }
}
