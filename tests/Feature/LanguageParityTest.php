<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

test('arabic and english translation sources have recursive key parity', function () {
    $localeFiles = collect(['ar', 'en'])->mapWithKeys(function (string $locale): array {
        $files = collect(File::files(lang_path($locale)))
            ->map(fn ($file): string => $file->getFilename())
            ->sort()
            ->values()
            ->all();

        return [$locale => $files];
    });

    expect($localeFiles['ar'])->not->toBeEmpty()
        ->and($localeFiles['ar'])->toBe($localeFiles['en']);

    $mismatches = [];

    foreach ($localeFiles['en'] as $filename) {
        $arabic = flattenLanguageParityValues(require lang_path("ar/{$filename}"));
        $english = flattenLanguageParityValues(require lang_path("en/{$filename}"));

        $missingInArabic = array_diff_key($english, $arabic);
        $missingInEnglish = array_diff_key($arabic, $english);

        foreach (array_keys($missingInArabic) as $key) {
            $mismatches[] = "MISSING_AR {$filename}:{$key}";
        }

        foreach (array_keys($missingInEnglish) as $key) {
            $mismatches[] = "MISSING_EN {$filename}:{$key}";
        }
    }

    $arabicJson = flattenLanguageParityValues(
        json_decode(File::get(lang_path('ar.json')), true, 512, JSON_THROW_ON_ERROR),
    );
    $englishJson = flattenLanguageParityValues(
        json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR),
    );

    foreach (array_keys(array_diff_key($englishJson, $arabicJson)) as $key) {
        $mismatches[] = "MISSING_AR ar.json:{$key}";
    }

    foreach (array_keys(array_diff_key($arabicJson, $englishJson)) as $key) {
        $mismatches[] = "MISSING_EN en.json:{$key}";
    }

    expect($mismatches)->toBe([]);
});

test('matched translations preserve interpolation placeholders', function () {
    $mismatches = [];

    foreach (File::files(lang_path('en')) as $englishFile) {
        $filename = $englishFile->getFilename();
        $arabic = flattenLanguageParityValues(require lang_path("ar/{$filename}"));
        $english = flattenLanguageParityValues(require $englishFile->getPathname());

        foreach ($english as $key => $englishValue) {
            if (! isset($arabic[$key])) {
                continue;
            }

            $englishPlaceholders = translationPlaceholders($englishValue);
            $arabicPlaceholders = translationPlaceholders($arabic[$key]);

            if ($englishPlaceholders !== $arabicPlaceholders) {
                $mismatches[] = "{$filename}:{$key}";
            }
        }
    }

    expect($mismatches)->toBe([]);
});

test('arabic validation uses translated messages and attribute names', function () {
    app()->setLocale('ar');

    $validator = Validator::make([], [
        'phone_number' => ['required'],
        'email' => ['required'],
    ]);

    expect($validator->errors()->first('phone_number'))->toContain('رقم الهاتف')
        ->not->toContain('required')
        ->and($validator->errors()->first('email'))->toContain('البريد الإلكتروني')
        ->not->toContain('required');
});

/**
 * @param  array<string|int, mixed>  $translations
 * @return array<string, string>
 */
function flattenLanguageParityValues(array $translations, string $prefix = ''): array
{
    $values = [];

    foreach ($translations as $key => $value) {
        $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $values = array_merge($values, flattenLanguageParityValues($value, $fullKey));

            continue;
        }

        $values[$fullKey] = (string) $value;
    }

    ksort($values);

    return $values;
}

/** @return list<string> */
function translationPlaceholders(string $translation): array
{
    preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*/', $translation, $matches);
    $placeholders = array_values(array_unique($matches[0]));
    sort($placeholders);

    return $placeholders;
}
