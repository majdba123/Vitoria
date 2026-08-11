<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvFile
{
    /**
     * Parse an uploaded CSV file into a header row and data rows.
     *
     * @return array{header: list<string>, rows: list<list<string|null>>}
     */
    public static function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            throw new CsvImportException(__('Unable to read the uploaded file.'));
        }

        $header = [];
        $rows = [];
        $isFirstLine = true;

        try {
            while (($line = fgetcsv($handle)) !== false) {
                if (self::isBlankRow($line)) {
                    continue;
                }

                if ($isFirstLine) {
                    $header = array_map(fn ($cell) => self::normalizeCell((string) $cell), $line);
                    $isFirstLine = false;

                    continue;
                }

                $rows[] = array_map(
                    fn ($cell) => $cell === null ? null : self::normalizeCell((string) $cell),
                    $line
                );
            }
        } finally {
            fclose($handle);
        }

        return ['header' => $header, 'rows' => $rows];
    }

    /**
     * Stream a downloadable CSV template (with a UTF-8 BOM so Excel renders Arabic correctly).
     *
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $exampleRows
     */
    public static function download(string $filename, array $headers, array $exampleRows = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $exampleRows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($exampleRows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  list<string|null>  $line
     */
    private static function isBlankRow(array $line): bool
    {
        foreach ($line as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function normalizeCell(string $value): string
    {
        return trim(ltrim($value, "\xEF\xBB\xBF"));
    }
}
