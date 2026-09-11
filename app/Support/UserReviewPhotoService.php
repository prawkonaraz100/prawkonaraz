<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class UserReviewPhotoService
{
    public const DISK = 'local';

    public const DIRECTORY = 'user-review-photos';

    public const MAX_EDGE = 1600;

    public function store(UploadedFile $file): string
    {
        $sourceBytes = file_get_contents($file->getRealPath());

        if (! is_string($sourceBytes) || $sourceBytes === '') {
            throw new RuntimeException('Nie udało się odczytać zdjęcia.');
        }

        $source = @imagecreatefromstring($sourceBytes);

        if ($source === false) {
            throw new RuntimeException('Nie udało się przetworzyć zdjęcia.');
        }

        try {
            $source = $this->applyJpegOrientation($source, $file);
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $scale = min(1, self::MAX_EDGE / max($sourceWidth, $sourceHeight));
            $targetWidth = max(1, (int) round($sourceWidth * $scale));
            $targetHeight = max(1, (int) round($sourceHeight * $scale));
            $target = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($target === false) {
                throw new RuntimeException('Nie udało się przygotować zdjęcia.');
            }

            try {
                $white = imagecolorallocate($target, 255, 255, 255);
                imagefill($target, 0, 0, $white);
                imagecopyresampled(
                    $target,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $sourceWidth,
                    $sourceHeight,
                );

                ob_start();
                $encoded = imagejpeg($target, null, 85) ? ob_get_clean() : false;

                if (! is_string($encoded) || $encoded === '') {
                    ob_end_clean();
                    throw new RuntimeException('Nie udało się zapisać zdjęcia.');
                }
            } finally {
                imagedestroy($target);
            }
        } finally {
            imagedestroy($source);
        }

        $path = self::DIRECTORY.'/'.Str::uuid().'.jpg';

        if (! Storage::disk(self::DISK)->directoryExists(self::DIRECTORY)
            && ! Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY)) {
            throw new RuntimeException('Nie udało się przygotować katalogu na zdjęcie.');
        }

        if (! Storage::disk(self::DISK)->put($path, $encoded)) {
            throw new RuntimeException('Nie udało się zapisać zdjęcia.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk(self::DISK)->delete((string) $path);
        }
    }

    private function applyJpegOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $metadata = @exif_read_data($file->getRealPath());
        $orientation = is_array($metadata) ? (int) ($metadata['Orientation'] ?? 1) : 1;
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
