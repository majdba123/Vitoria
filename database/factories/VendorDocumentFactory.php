<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorDocument>
 */
class VendorDocumentFactory extends Factory
{
    protected $model = VendorDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'type' => VendorDocument::TYPE_COMMERCIAL_REGISTRATION,
            'file_path' => 'vendor-documents/'.fake()->uuid().'.pdf',
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1000, 500000),
            'status' => VendorDocument::STATUS_PENDING_REVIEW,
        ];
    }
}
