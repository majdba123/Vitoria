<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ArabicCategorySeeder extends Seeder
{
    public function run(): void
    {
        $agricultureLogo = $this->storeDemoAsset('demo/categories/agriculture.png');
        $veterinaryLogo = $this->storeDemoAsset('demo/categories/veterinary.png');

        foreach (self::categories() as $item) {
            Category::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'type' => $item['type'],
                    'logo' => $item['type'] === Category::TYPE_AGRICULTURE ? $agricultureLogo : $veterinaryLogo,
                    'icon' => $item['type'] === Category::TYPE_AGRICULTURE ? $agricultureLogo : $veterinaryLogo,
                    'icon_class' => $item['icon_class'],
                    'commission' => $item['commission'],
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, type: string, icon_class: string, commission: float}>
     */
    public static function categories(): array
    {
        return [
            ['name' => 'Ø§Ù„Ø¨Ø°ÙˆØ±', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-seedling', 'commission' => 4.5],
            ['name' => 'Ø§Ù„Ø£Ø³Ù…Ø¯Ø©', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-leaf', 'commission' => 5.0],
            ['name' => 'Ø£Ù†Ø¸Ù…Ø© Ø§Ù„Ø±ÙŠ', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-droplet', 'commission' => 5.5],
            ['name' => 'Ø§Ù„Ø¨ÙŠÙˆØª Ø§Ù„Ø¨Ù„Ø§Ø³ØªÙŠÙƒÙŠØ©', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-warehouse', 'commission' => 6.0],
            ['name' => 'Ø§Ù„Ù…Ø¹Ø¯Ø§Øª Ø§Ù„Ø²Ø±Ø§Ø¹ÙŠØ©', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-tractor', 'commission' => 6.5],
            ['name' => 'Ø§Ù„Ù…Ø¨ÙŠØ¯Ø§Øª Ø§Ù„Ø²Ø±Ø§Ø¹ÙŠØ©', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-spray-can-sparkles', 'commission' => 5.25],
            ['name' => 'Ø§Ù„ØªØ±Ø¨Ø© ÙˆØ§Ù„Ø³Ù…Ø§Ø¯ Ø§Ù„Ø¹Ø¶ÙˆÙŠ', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-mound', 'commission' => 4.75],
            ['name' => 'Ø£Ø¯ÙˆØ§Øª Ø§Ù„Ø­ØµØ§Ø¯', 'type' => Category::TYPE_AGRICULTURE, 'icon_class' => 'fa-solid fa-wheat-awn', 'commission' => 5.75],
            ['name' => 'Ø§Ù„Ø£Ø¯ÙˆÙŠØ© Ø§Ù„Ø¨ÙŠØ·Ø±ÙŠØ©', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-prescription-bottle-medical', 'commission' => 6.0],
            ['name' => 'Ø§Ù„Ù„Ù‚Ø§Ø­Ø§Øª', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-syringe', 'commission' => 6.25],
            ['name' => 'Ù…Ø¹Ø¯Ø§Øª Ø§Ù„Ø¹ÙŠØ§Ø¯Ø§Øª Ø§Ù„Ø¨ÙŠØ·Ø±ÙŠØ©', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-stethoscope', 'commission' => 6.5],
            ['name' => 'Ù…ÙƒÙ…Ù„Ø§Øª Ø§Ù„Ø£Ø¹Ù„Ø§Ù', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-bowl-food', 'commission' => 5.5],
            ['name' => 'Ø£Ø¯ÙˆØ§Øª Ø±Ø¹Ø§ÙŠØ© Ø§Ù„Ø­ÙŠÙˆØ§Ù†Ø§Øª', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-hand-holding-medical', 'commission' => 5.75],
            ['name' => 'Ù…Ø³ØªÙ„Ø²Ù…Ø§Øª Ø§Ù„Ù…ÙˆØ§Ø´ÙŠ', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-cow', 'commission' => 5.25],
            ['name' => 'Ø§Ù„Ù…Ø·Ù‡Ø±Ø§Øª Ø§Ù„Ø¨ÙŠØ·Ø±ÙŠØ©', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-pump-medical', 'commission' => 5.0],
            ['name' => 'Ø®Ø¯Ù…Ø§Øª Ø¨ÙŠØ·Ø±ÙŠØ©', 'type' => Category::TYPE_VETERINARY, 'icon_class' => 'fa-solid fa-user-doctor', 'commission' => 7.0],
        ];
    }

    private function storeDemoAsset(string $path): string
    {
        Storage::disk('public')->makeDirectory(dirname($path));

        $fixture = database_path('seeders/fixtures/default-product.png');
        $destination = Storage::disk('public')->path($path);

        if (File::exists($fixture) && ! File::exists($destination)) {
            File::copy($fixture, $destination);
        }

        return $path;
    }
}
