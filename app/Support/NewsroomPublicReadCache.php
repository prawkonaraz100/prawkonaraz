<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class NewsroomPublicReadCache
{
    private const HOME_GENERATION_KEY = 'newsroom:cache:home:generation:v1';

    private const CATEGORY_GENERATION_KEY = 'newsroom:cache:category:generation:v1';

    public function rememberHome(Closure $resolver): ?array
    {
        return Cache::remember(
            $this->homeKey(),
            $this->ttlSeconds(),
            $resolver,
        );
    }

    public function rememberCategory(string $categorySlug, int $page, Closure $resolver): ?array
    {
        return Cache::remember(
            $this->categoryKey($categorySlug, $page),
            $this->ttlSeconds(),
            $resolver,
        );
    }

    public function invalidateHome(): void
    {
        $this->rotate(self::HOME_GENERATION_KEY);
    }

    public function invalidateCategories(): void
    {
        $this->rotate(self::CATEGORY_GENERATION_KEY);
    }

    public function invalidateAll(): void
    {
        $this->invalidateHome();
        $this->invalidateCategories();
    }

    public function homeKey(): string
    {
        return 'newsroom:home:v1:g:'.$this->generation(self::HOME_GENERATION_KEY);
    }

    public function categoryKey(string $categorySlug, int $page): string
    {
        return implode(':', [
            'newsroom',
            'category',
            trim($categorySlug),
            'page',
            (string) $page,
            'v1',
            'g',
            $this->generation(self::CATEGORY_GENERATION_KEY),
        ]);
    }

    private function generation(string $key): string
    {
        return (string) Cache::get($key, 'base');
    }

    private function rotate(string $key): void
    {
        Cache::forever($key, (string) Str::uuid());
    }

    private function ttlSeconds(): int
    {
        return max(1, (int) config('newsroom.cache_ttl_seconds', 60));
    }
}
