<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class NewsroomMediaStorage
{
    public const BASELINE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
    ];

    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array{disk:string,path:string,mime_type:string,bytes:int}
     */
    public function prepareImageUpload(string $mimeType, int $bytes): array
    {
        $mimeType = $this->normalizeMimeType($mimeType);

        $this->assertAllowedMimeType($mimeType);
        $this->assertAllowedBytes($bytes);

        return [
            'disk' => $this->disk(),
            'path' => $this->newImagePath($mimeType),
            'mime_type' => $mimeType,
            'bytes' => $bytes,
        ];
    }

    /**
     * Inspect the actual object already stored on the dedicated newsroom disk.
     *
     * @return array{disk:string,path:string,mime_type:string,bytes:int,width:int,height:int}
     */
    public function inspectStoredImage(
        string $path,
        ?string $declaredMimeType = null,
        ?int $declaredBytes = null,
    ): array {
        $this->assertManagedPath($path);

        $filesystem = Storage::disk($this->disk());

        if (! $filesystem->exists($path)) {
            throw ValidationException::withMessages([
                'path' => 'Newsroom image was not found on storage.',
            ]);
        }

        $actualBytes = (int) $filesystem->size($path);

        $this->assertAllowedBytes($actualBytes);

        if ($declaredBytes !== null && $declaredBytes !== $actualBytes) {
            throw ValidationException::withMessages([
                'bytes' => 'Declared newsroom image size does not match the stored object.',
            ]);
        }

        $contents = $filesystem->get($path);
        $imageInfo = @getimagesizefromstring($contents);

        if (! is_array($imageInfo)) {
            throw ValidationException::withMessages([
                'path' => 'Stored newsroom asset is not a supported raster image.',
            ]);
        }

        $actualMimeType = $this->normalizeMimeType((string) ($imageInfo['mime'] ?? ''));

        $this->assertAllowedMimeType($actualMimeType);

        if ($declaredMimeType !== null && $this->normalizeMimeType($declaredMimeType) !== $actualMimeType) {
            throw ValidationException::withMessages([
                'mime_type' => 'Declared newsroom image MIME type does not match the stored object.',
            ]);
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        $maxDimension = $this->maxDimension();

        if ($width < 1 || $height < 1 || $width > $maxDimension || $height > $maxDimension) {
            throw ValidationException::withMessages([
                'dimensions' => "Newsroom image dimensions must be between 1 and {$maxDimension} pixels.",
            ]);
        }

        return [
            'disk' => $this->disk(),
            'path' => $path,
            'mime_type' => $actualMimeType,
            'bytes' => $actualBytes,
            'width' => $width,
            'height' => $height,
        ];
    }

    public function publicUrl(string $path): string
    {
        $this->assertManagedPath($path);

        $url = $this->mediaUrlResolver->resolve($path, $this->disk());

        if (! is_string($url) || preg_match('#\Ahttps?://#i', $url) !== 1) {
            throw new RuntimeException('Newsroom media must resolve to a stable public HTTP(S) URL.');
        }

        return $url;
    }

    /**
     * @return list<string>
     */
    public function allowedMimeTypes(): array
    {
        $shared = array_map(
            fn (mixed $mime): string => $this->normalizeMimeType((string) $mime),
            (array) config('media.allowed_mime_types.image', []),
        );

        return array_values(array_filter(
            self::BASELINE_MIME_TYPES,
            fn (string $mime): bool => in_array($mime, $shared, true),
        ));
    }

    public function disk(): string
    {
        return (string) config('media.newsroom_disk', config('media.public_disk', 'public'));
    }

    public function prefix(): string
    {
        $prefix = trim((string) config('media.newsroom_prefix', 'newsroom/articles'), '/');

        if (
            $prefix === ''
            || str_contains($prefix, '..')
            || str_contains($prefix, '\\')
            || preg_match('#\A[a-z0-9/_-]+\z#', $prefix) !== 1
        ) {
            throw new RuntimeException('Invalid newsroom media storage prefix.');
        }

        return $prefix;
    }

    public function maxBytes(): int
    {
        $bytes = (int) config('media.max_bytes.image', 8 * 1024 * 1024);

        return $bytes > 0 ? $bytes : 8 * 1024 * 1024;
    }

    public function maxDimension(): int
    {
        $dimension = (int) config('media.newsroom_max_dimension', 10000);

        return $dimension > 0 ? $dimension : 10000;
    }

    public function newImagePath(string $mimeType): string
    {
        $mimeType = $this->normalizeMimeType($mimeType);

        $this->assertAllowedMimeType($mimeType);

        return $this->prefix()
            .'/source/'
            .Str::lower((string) Str::ulid())
            .'.'
            .$this->extensionForMimeType($mimeType);
    }

    public function assertManagedPath(string $path): void
    {
        if (
            str_contains($path, "\0")
            || str_contains($path, '\\')
            || str_contains($path, '..')
            || str_starts_with($path, '/')
            || preg_match('/\A[a-z][a-z0-9+.-]*:/i', $path) === 1
        ) {
            throw ValidationException::withMessages([
                'path' => 'Newsroom image path is not a managed storage-relative path.',
            ]);
        }

        $pattern = '#\A'
            .preg_quote($this->prefix(), '#')
            .'/source/[0-9a-hjkmnp-tv-z]{26}\.(?:jpg|png|webp|avif)\z#';

        if (preg_match($pattern, $path) !== 1) {
            throw ValidationException::withMessages([
                'path' => 'Newsroom image path is outside the managed immutable namespace.',
            ]);
        }
    }

    protected function assertAllowedMimeType(string $mimeType): void
    {
        if (! in_array($mimeType, $this->allowedMimeTypes(), true)) {
            throw ValidationException::withMessages([
                'mime_type' => 'Unsupported newsroom image MIME type.',
            ]);
        }
    }

    protected function assertAllowedBytes(int $bytes): void
    {
        if ($bytes < 1 || $bytes > $this->maxBytes()) {
            throw ValidationException::withMessages([
                'bytes' => 'Newsroom image size exceeds the configured image upload policy.',
            ]);
        }
    }

    protected function normalizeMimeType(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        return $mimeType === 'image/jpg' ? 'image/jpeg' : $mimeType;
    }

    protected function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default => throw new RuntimeException("Unsupported newsroom image MIME type [{$mimeType}]."),
        };
    }
}
