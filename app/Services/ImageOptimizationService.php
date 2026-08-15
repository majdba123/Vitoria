<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Downscales and re-compresses an uploaded image before it's stored, so
 * vendor-uploaded product photos and category logos don't get served to
 * every visitor at whatever resolution the source phone camera produced.
 * Falls back to storing the original file unprocessed on any failure - a
 * marketplace losing a listing's image because of a processing hiccup is
 * worse than serving one unoptimized photo.
 */
class ImageOptimizationService
{
    /**
     * Largest dimension (px) an optimized image is allowed to have.
     * Downscale-only - never upscales a smaller source image.
     */
    protected const MAX_DIMENSION = 1600;

    /**
     * Re-encoding quality (0-100) applied when optimizing images.
     */
    protected const QUALITY = 82;

    public function storeOptimized(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $path = $file->hashName($directory);

        try {
            $image = (new ImageManager(new Driver))->read($file->getRealPath());
            $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);
            $encoded = $image->encodeByExtension(quality: self::QUALITY);

            Storage::disk($disk)->put($path, (string) $encoded);
        } catch (\Throwable $e) {
            Log::warning('Image optimization failed; storing original upload unprocessed.', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);

            Storage::disk($disk)->putFileAs($directory, $file, basename($path));
        }

        return $path;
    }
}
