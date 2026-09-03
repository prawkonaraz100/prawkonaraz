<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUrlResolver
{
    public function __construct(
        protected PublicUrlResolver $publicUrlResolver,
    ) {}

    public function resolve(?string $path, ?string $disk = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim((string) $path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if ($this->isCheckedInPublicAsset($path)) {
            return $this->publicUrlResolver->normalize($this->versionedPublicPath($path));
        }

        $disk ??= (string) config('media.public_disk');
        $publicDisk = (string) config('media.public_disk');
        $publicBaseUrl = (string) config('media.public_base_url', '');

        if ($publicBaseUrl !== '' && $disk === $publicDisk) {
            return $this->publicUrlResolver->normalize($this->join($publicBaseUrl, $path));
        }

        if (! is_array(config("filesystems.disks.{$disk}"))) {
            return $this->publicUrlResolver->normalize($path);
        }

        return $this->publicUrlResolver->normalize(Storage::disk($disk)->url($path));
    }

    public function resolveIfPublicAssetExists(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim((string) $path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $this->publicUrlResolver->normalize($path);
        }

        if (! $this->isCheckedInPublicAsset($path)) {
            return null;
        }

        return $this->publicUrlResolver->normalize($this->versionedPublicPath($path));
    }

    protected function join(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    protected function isCheckedInPublicAsset(string $path): bool
    {
        return is_file(public_path(ltrim($path, '/')));
    }

    protected function versionedPublicPath(string $path): string
    {
        $normalizedPath = '/'.ltrim($path, '/');
        $publicAssetPath = public_path(ltrim($path, '/'));
        $modifiedAt = @filemtime($publicAssetPath);

        if (! is_int($modifiedAt) || $modifiedAt <= 0) {
            return $normalizedPath;
        }

        $separator = str_contains($normalizedPath, '?') ? '&' : '?';

        return "{$normalizedPath}{$separator}v={$modifiedAt}";
    }
}
