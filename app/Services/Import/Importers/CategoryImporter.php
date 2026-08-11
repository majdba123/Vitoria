<?php

namespace App\Services\Import\Importers;

use App\Models\Category;
use App\Services\Import\AbstractCsvImporter;
use Illuminate\Validation\Rule;

class CategoryImporter extends AbstractCsvImporter
{
    public function headers(): array
    {
        return ['name', 'type', 'commission'];
    }

    public function exampleRows(): array
    {
        return [
            ['Fertilizers', Category::TYPE_AGRICULTURE, '10'],
            ['Vaccines', Category::TYPE_VETERINARY, '7.5'],
        ];
    }

    protected function uniqueWithinFile(): array
    {
        // Category names aren't DB-unique (matching StoreCategoryRequest), this only
        // guards against accidentally pasting the same row twice in one file.
        return ['name', 'type'];
    }

    protected function rules(array $row, array $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected function persist(array $row, array $context): void
    {
        Category::query()->create([
            'name' => $row['name'],
            'type' => $row['type'],
            'commission' => $row['commission'] ?? null,
        ]);
    }
}
