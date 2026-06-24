<?php

namespace App\Http\Requests\Admin\Concerns;

trait NormalizesDateTimeInputs
{
    /**
     * @param  list<string>  $fields
     */
    protected function normalizeDateTimeInputs(array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $trimmed = trim($value);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed) === 1) {
                $normalized[$field] = str_ends_with($field, 'ends_at')
                    ? $trimmed.' 23:59:00'
                    : $trimmed.' 00:00:00';

                continue;
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $trimmed) === 1) {
                $normalized[$field] = str_replace('T', ' ', $trimmed).':00';
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
