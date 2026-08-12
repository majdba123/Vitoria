<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductDocument>
 */
class ProductDocumentFactory extends Factory
{
    protected $model = ProductDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'vendor_id' => Vendor::factory(),
            'type' => ProductDocument::TYPE_LEAFLET,
            'title' => fake()->words(3, true),
            'language' => 'ar',
            'file_path' => 'product-documents/'.fake()->uuid().'.pdf',
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1000, 500000),
            'source' => ProductDocument::SOURCE_VENDOR,
            'status' => ProductDocument::STATUS_PENDING_REVIEW,
        ];
    }
}
