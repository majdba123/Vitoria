<?php

namespace App\Support;

/**
 * Static Syria governorate reference data, used only to turn a vendor's
 * `city` into a governorate-level aggregate for the vendor map. The `cities`
 * table (see CitySeeder) has no governorate column of its own, so this is
 * the single place that maps every seeded city name to its governorate.
 *
 * Deliberately hand-maintained rather than derived: adding a city elsewhere
 * without updating CITY_TO_GOVERNORATE just drops it from the map counters
 * instead of guessing a governorate for it.
 */
class SyriaGovernorates
{
    /**
     * @var list<array{key: string, name_en: string, name_ar: string, lat: float, lng: float}>
     */
    public const ALL = [
        ['key' => 'damascus', 'name_en' => 'Damascus', 'name_ar' => 'دمشق', 'lat' => 33.5138, 'lng' => 36.2765],
        ['key' => 'rif_dimashq', 'name_en' => 'Rif Dimashq', 'name_ar' => 'ريف دمشق', 'lat' => 33.5060, 'lng' => 36.4161],
        ['key' => 'aleppo', 'name_en' => 'Aleppo', 'name_ar' => 'حلب', 'lat' => 36.2021, 'lng' => 37.1343],
        ['key' => 'homs', 'name_en' => 'Homs', 'name_ar' => 'حمص', 'lat' => 34.7324, 'lng' => 36.7137],
        ['key' => 'hama', 'name_en' => 'Hama', 'name_ar' => 'حماة', 'lat' => 35.1318, 'lng' => 36.7500],
        ['key' => 'latakia', 'name_en' => 'Latakia', 'name_ar' => 'اللاذقية', 'lat' => 35.5211, 'lng' => 35.7826],
        ['key' => 'tartus', 'name_en' => 'Tartus', 'name_ar' => 'طرطوس', 'lat' => 34.8890, 'lng' => 35.8866],
        ['key' => 'idlib', 'name_en' => 'Idlib', 'name_ar' => 'إدلب', 'lat' => 35.9306, 'lng' => 36.6339],
        ['key' => 'raqqa', 'name_en' => 'Raqqa', 'name_ar' => 'الرقة', 'lat' => 35.9500, 'lng' => 39.0100],
        ['key' => 'deir_ez_zor', 'name_en' => 'Deir ez-Zor', 'name_ar' => 'دير الزور', 'lat' => 35.3359, 'lng' => 40.1408],
        ['key' => 'al_hasakah', 'name_en' => 'Al-Hasakah', 'name_ar' => 'الحسكة', 'lat' => 36.5024, 'lng' => 40.7477],
        ['key' => 'daraa', 'name_en' => 'Daraa', 'name_ar' => 'درعا', 'lat' => 32.6189, 'lng' => 36.1021],
        ['key' => 'as_suwayda', 'name_en' => 'As-Suwayda', 'name_ar' => 'السويداء', 'lat' => 32.7094, 'lng' => 36.5697],
        ['key' => 'quneitra', 'name_en' => 'Quneitra', 'name_ar' => 'القنيطرة', 'lat' => 33.1263, 'lng' => 35.8244],
    ];

    /**
     * @var array<string, string>
     */
    public const CITY_TO_GOVERNORATE = [
        'Damascus' => 'damascus',
        'Aleppo' => 'aleppo',
        'Homs' => 'homs',
        'Hama' => 'hama',
        'Latakia' => 'latakia',
        'Tartus' => 'tartus',
        'Idlib' => 'idlib',
        'Raqqa' => 'raqqa',
        'Deir ez-Zor' => 'deir_ez_zor',
        'Al-Hasakah' => 'al_hasakah',
        'Daraa' => 'daraa',
        'As-Suwayda' => 'as_suwayda',
        'Quneitra' => 'quneitra',
        'Palmyra' => 'homs',
        'Jableh' => 'latakia',
        'Manbij' => 'aleppo',
        'Afrin' => 'aleppo',
        'Al-Bab' => 'aleppo',
        'Al-Qamishli' => 'al_hasakah',
        'Qamishli' => 'al_hasakah',
        'Salamiyah' => 'hama',
        'Masyaf' => 'hama',
        'Safita' => 'tartus',
        'Baniyas' => 'tartus',
        'Al-Mayadin' => 'deir_ez_zor',
        'Abu Kamal' => 'deir_ez_zor',
        'Al-Safira' => 'aleppo',
        'Maarat al-Numan' => 'idlib',
        'Ariha' => 'idlib',
        'Jisr al-Shughur' => 'idlib',
        'Kobani' => 'aleppo',
    ];

    public static function keyForCity(?string $cityName): ?string
    {
        if ($cityName === null) {
            return null;
        }

        return self::CITY_TO_GOVERNORATE[$cityName] ?? null;
    }

    /** @return list<string> */
    public static function cityNamesForKey(string $key): array
    {
        return array_keys(array_filter(self::CITY_TO_GOVERNORATE, fn (string $governorate): bool => $governorate === $key));
    }
}
