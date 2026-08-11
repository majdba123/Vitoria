<?php

namespace App\Services\Import\Importers;

use App\Models\City;
use App\Services\Import\AbstractCsvImporter;
use Illuminate\Validation\Rule;

class CityImporter extends AbstractCsvImporter
{
    public function headers(): array
    {
        return ['name'];
    }

    public function exampleRows(): array
    {
        return [
            ['Riyadh'],
            ['Jeddah'],
        ];
    }

    protected function uniqueWithinFile(): array
    {
        return ['name'];
    }

    protected function rules(array $row, array $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('cities', 'name')],
        ];
    }

    protected function persist(array $row, array $context): void
    {
        City::query()->create($row);
    }
}
