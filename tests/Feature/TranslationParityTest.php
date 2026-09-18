<?php

/**
 * Structural PHP-file parity between lang/ar/*.php and lang/en/*.php is
 * already covered by LocalizationConsistencyTest's "translation php files
 * are synchronized" test (which owns the flattenTranslationKeys() helper).
 * This file only adds the checks that test does NOT cover: JSON-locale
 * parity (recursive, so nested objects are compared too), blank-value
 * detection at every depth, and a real resolvability round-trip.
 */
function namespacedLangFiles(string $locale): array
{
    $base = lang_path($locale);

    $files = collect(glob("{$base}/*.php"))
        ->mapWithKeys(fn (string $path) => [basename($path, '.php') => $path])
        ->all();

    ksort($files);

    return $files;
}

/**
 * Local flatten helper (distinct name from LocalizationConsistencyTest's
 * flattenTranslationKeys() to avoid a redeclaration fatal — both files load
 * into the same global namespace during a full test run).
 *
 * @return array<string, mixed>
 */
function flattenParityCheckKeys(array $data, string $prefix = ''): array
{
    $keys = [];

    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $keys = array_merge($keys, flattenParityCheckKeys($value, $path));
        } else {
            $keys[$path] = $value;
        }
    }

    return $keys;
}

it('keeps lang/ar.json and lang/en.json key sets identical at every nesting depth', function () {
    $ar = json_decode(file_get_contents(lang_path('ar.json')), true, flags: JSON_THROW_ON_ERROR);
    $en = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);

    $arKeys = array_keys(flattenParityCheckKeys($ar));
    $enKeys = array_keys(flattenParityCheckKeys($en));

    $missingInEn = array_values(array_diff($arKeys, $enKeys));
    $missingInAr = array_values(array_diff($enKeys, $arKeys));

    expect($missingInEn)->toBe([], 'Keys in lang/ar.json missing from lang/en.json: '.implode(' | ', $missingInEn));
    expect($missingInAr)->toBe([], 'Keys in lang/en.json missing from lang/ar.json: '.implode(' | ', $missingInAr));
});

it('never leaves a blank translation value in either locale JSON file', function () {
    $ar = json_decode(file_get_contents(lang_path('ar.json')), true, flags: JSON_THROW_ON_ERROR);
    $en = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);

    $blankAr = array_keys(array_filter(flattenParityCheckKeys($ar), fn ($v) => trim((string) $v) === ''));
    $blankEn = array_keys(array_filter(flattenParityCheckKeys($en), fn ($v) => trim((string) $v) === ''));

    expect($blankAr)->toBe([], 'Blank Arabic translations for: '.implode(' | ', $blankAr));
    expect($blankEn)->toBe([], 'Blank English translations for: '.implode(' | ', $blankEn));
});

it('never serves a raw key string when a namespaced lookup resolves', function () {
    // Laravel validator "attributes"/"custom" arrays use flat keys containing literal
    // dots for wildcard array inputs (e.g. 'items.*.product_id' => 'product'). Those are
    // resolved internally by the Validator's own attribute-formatting logic
    // (FormatsMessages::getAttribute()), never through a plain __() dot-path lookup, so
    // __() legitimately can't resolve them and they are excluded from this round-trip check.
    //
    // The "value must not echo the dotted key" assertion only applies to Arabic: the
    // English locale deliberately uses full English sentences as keys whose values are
    // identical (the app's full-sentence convention), so equality there is by design.
    $isWildcardAttributeKey = fn (string $key) => str_contains($key, '*');

    foreach (['ar', 'en'] as $locale) {
        foreach (namespacedLangFiles($locale) as $namespace => $path) {
            $values = flattenParityCheckKeys(require $path);

            foreach ($values as $key => $value) {
                if ($isWildcardAttributeKey($key)) {
                    continue;
                }

                $dotted = "{$namespace}.{$key}";

                expect(\Illuminate\Support\Facades\Lang::has($dotted, $locale))
                    ->toBeTrue("Missing {$locale} translation for key: {$dotted}");

                if ($locale === 'ar') {
                    expect(trim((string) __($dotted, [], $locale)))
                        ->not->toBe($dotted, "Raw key fallback for {$locale} key: {$dotted}");
                }
            }
        }
    }
});
