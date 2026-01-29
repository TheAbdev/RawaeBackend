<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Service to process and optimize images before storage.
 *
 * According to specification:
 * - Resize and optimize uploaded images before storage
 * - Supported formats: jpg, jpeg, png, gif, webp
 * - Max file size: 5MB per file
 */
class ImageProcessingService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Process and store an image.
     *
     * @param UploadedFile $file
     * @param string $path Storage path
     * @param int $maxWidth Maximum width (default: 1920)
     * @param int $maxHeight Maximum height (default: 1920)
     * @param int $quality JPEG quality (default: 85)
     * @return array ['path' => string, 'url' => string]
     */
    public function processAndStore(
        UploadedFile $file,
        string $path,
        int $maxWidth = 1920,
        int $maxHeight = 1920,
        int $quality = 85
    ): array {
        // Read the image
        $image = $this->imageManager->read($file->getRealPath());

        // Get original dimensions
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Resize if necessary (maintain aspect ratio)
        if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
            $image->scaleDown($maxWidth, $maxHeight);
        }

        // Optimize based on file type
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();

        // Generate filename
        $timestamp = now()->timestamp;
        $filename = $timestamp . '_' . uniqid() . '.' . $extension;
        $fullPath = $path . '/' . $filename;

        // Ensure directory exists
        $directory = dirname(Storage::disk('public')->path($fullPath));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Save optimized image
        if (in_array($mimeType, ['image/jpeg', 'image/jpg'])) {
            $image->toJpeg($quality)->save(Storage::disk('public')->path($fullPath));
        } elseif ($mimeType === 'image/png') {
            // PNG: use compression level (0-9, 9 is maximum compression)
            $image->toPng(9)->save(Storage::disk('public')->path($fullPath));
        } elseif ($mimeType === 'image/webp') {
            $image->toWebp($quality)->save(Storage::disk('public')->path($fullPath));
        } elseif ($mimeType === 'image/gif') {
            // GIF: save as-is (no optimization for animated GIFs)
            $image->toGif()->save(Storage::disk('public')->path($fullPath));
        } else {
            // Fallback: save as JPEG
            $image->toJpeg($quality)->save(Storage::disk('public')->path($fullPath));
        }

        // Generate relative URL (without domain)
        $url = '/storage/' . $fullPath;

        return [
            'path' => $fullPath,
            'url' => $url,
        ];
    }

    /**
     * Process and store tank image.
     *
     * @param UploadedFile $file
     * @param int $mosqueId
     * @return array
     */
    public function processTankImage(UploadedFile $file, int $mosqueId): array
    {
        return $this->processAndStore(
            $file,
            "tanks/{$mosqueId}",
            maxWidth: 1920,
            maxHeight: 1920,
            quality: 85
        );
    }

    /**
     * Process and store delivery proof image.
     *
     * @param UploadedFile $file
     * @param int $deliveryId
     * @return array
     */
    public function processDeliveryProof(UploadedFile $file, int $deliveryId): array
    {
        return $this->processAndStore(
            $file,
            "proofs/{$deliveryId}",
            maxWidth: 1920,
            maxHeight: 1920,
            quality: 85
        );
    }

    /**
     * Process and store ad image.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function processAdImage(UploadedFile $file): array
    {
        return $this->processAndStore(
            $file,
            "ads",
            maxWidth: 1920,
            maxHeight: 1920,
            quality: 90 // Higher quality for ads
        );
    }
}

