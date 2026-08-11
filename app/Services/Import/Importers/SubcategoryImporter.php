<?php

namespace App\Services\Import\Importers;

use App\Models\Subcategory;
use App\Services\Import\AbstractCsvImporter;
use Illuminate\Validation\Rule;

class SubcategoryImporter extends AbstractCsvImporter
{
    public function headers(): array
    {
        return ['category_id', 'name_ar', 'name_en'];
    }

    public function exampleRows(): array
    {
        return [
            ['1', 'أسمدة عضوية', 'Organic Fertilizers'],
            ['1', 'أسمدة كيميائية', 'Chemical Fertilizers'],
        ];
    }

    protected function uniqueWithinFile(): array
    {
        return ['category_id', 'name_ar'];
    }

    protected function rules(array $row, array $context): array
    {
        $categoryId = (int) ($row['category_id'] ?? 0);

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name_ar' => [
                'required', 'string', 'max:255',
                Rule::unique('subcategories', 'name_ar')->where(fn ($query) => $query->where('category_id', $categoryId)),
            ],
            'name_en' => [
                'required', 'string', 'max:255',
                Rule::unique('subcategories', 'name_en')->where(fn ($query) => $query->where('category_id', $categoryId)),
            ],
        ];
    }

    protected function persist(array $row, array $context): void
    {
        Subcategory::query()->create($row);
    }
}
