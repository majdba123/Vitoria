<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractCsvImporter
{
    protected const MAX_ROWS = 2000;

    /**
     * Column names expected in the CSV, in order.
     *
     * @return list<string>
     */
    abstract public function headers(): array;

    /**
     * Example data rows shown in the downloadable template.
     *
     * @return list<list<string|int|float|null>>
     */
    abstract public function exampleRows(): array;

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    abstract protected function rules(array $row, array $context): array;

    /**
     * Persist a single validated row. Throw to fail just this row.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     */
    abstract protected function persist(array $row, array $context): void;

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Column names that, combined, must be unique within a single uploaded file.
     * Catches accidental duplicate rows independently of any DB-level uniqueness.
     *
     * @return list<string>
     */
    protected function uniqueWithinFile(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row, array $context): array
    {
        return $row;
    }

    public function templateFilename(): string
    {
        $name = (new \ReflectionClass($this))->getShortName();
        $name = preg_replace('/Importer$/', '', $name) ?? $name;

        return \Illuminate\Support\Str::snake($name).'_import_template.csv';
    }

    public function template(): StreamedResponse
    {
        return CsvFile::download($this->templateFilename(), $this->headers(), $this->exampleRows());
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{total_rows: int, created: int, failed: int, errors: list<array{row: int, errors: array<string, list<string>>}>}
     */
    public function import(UploadedFile $file, array $context = []): array
    {
        $parsed = CsvFile::parse($file);
        $expectedHeaders = $this->headers();

        if (count($parsed['rows']) > static::MAX_ROWS) {
            throw new CsvImportException(__('The file contains too many rows (:count). Please split it into files of at most :max rows.', [
                'count' => count($parsed['rows']),
                'max' => static::MAX_ROWS,
            ]));
        }

        $headerIndex = $this->buildHeaderIndex($parsed['header'], $expectedHeaders);

        $created = 0;
        $errors = [];
        $seen = [];
        $uniqueColumns = $this->uniqueWithinFile();

        foreach ($parsed['rows'] as $index => $line) {
            $rowNumber = $index + 2; // +1 for header row, +1 for 1-based line numbers
            $row = $this->normalizeRow($this->mapRow($line, $headerIndex, $expectedHeaders), $context);

            if ($uniqueColumns !== []) {
                $key = collect($uniqueColumns)
                    ->map(fn ($column) => mb_strtolower((string) ($row[$column] ?? '')))
                    ->implode('|');

                if ($key !== '' && isset($seen[$key])) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'errors' => ['duplicate' => [__('This row duplicates row :row in the same file.', ['row' => $seen[$key]])]],
                    ];

                    continue;
                }

                $seen[$key] = $rowNumber;
            }

            $validator = Validator::make($row, $this->rules($row, $context), $this->messages());

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->toArray(),
                ];

                continue;
            }

            try {
                DB::transaction(fn () => $this->persist($validator->validated(), $context));
                $created++;
            } catch (\Throwable $exception) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['general' => [__('This row could not be saved.')]],
                ];
            }
        }

        return [
            'total_rows' => count($parsed['rows']),
            'created' => $created,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string>  $fileHeader
     * @param  list<string>  $expectedHeaders
     * @return array<string, int>
     */
    private function buildHeaderIndex(array $fileHeader, array $expectedHeaders): array
    {
        $normalizedFileHeader = [];

        foreach ($fileHeader as $position => $name) {
            $normalizedFileHeader[mb_strtolower($name)] = $position;
        }

        $missing = [];
        $index = [];

        foreach ($expectedHeaders as $name) {
            $key = mb_strtolower($name);

            if (! array_key_exists($key, $normalizedFileHeader)) {
                $missing[] = $name;

                continue;
            }

            $index[$name] = $normalizedFileHeader[$key];
        }

        if ($missing !== []) {
            throw new CsvImportException(__('The file is missing required columns: :columns. Please download the template and use it as-is.', [
                'columns' => implode(', ', $missing),
            ]));
        }

        return $index;
    }

    /**
     * @param  list<string|null>  $line
     * @param  array<string, int>  $headerIndex
     * @param  list<string>  $expectedHeaders
     * @return array<string, mixed>
     */
    private function mapRow(array $line, array $headerIndex, array $expectedHeaders): array
    {
        $row = [];

        foreach ($expectedHeaders as $name) {
            $value = $line[$headerIndex[$name]] ?? null;
            $row[$name] = $value === '' ? null : $value;
        }

        return $row;
    }
}
