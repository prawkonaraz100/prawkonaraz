<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

final class NewsroomSeoArtifactRefreshCoordinator
{
    private const VERSION_KEY = 'newsroom:seo-artifacts:version:v1';

    private const CLEAN_VERSION_KEY = 'newsroom:seo-artifacts:clean-version:v1';

    private const LOCK_KEY = 'newsroom:seo-artifacts:refresh-lock:v1';

    public function markDirty(): int
    {
        return (int) $this->cache()->increment(self::VERSION_KEY);
    }

    public function currentVersion(): int
    {
        return max(0, (int) $this->cache()->get(self::VERSION_KEY, 0));
    }

    public function cleanVersion(): int
    {
        return max(0, (int) $this->cache()->get(self::CLEAN_VERSION_KEY, 0));
    }

    public function isDirty(): bool
    {
        return $this->currentVersion() > $this->cleanVersion();
    }

    public function markCleanIfUnchanged(int $version): bool
    {
        if ($version < 1 || $this->currentVersion() !== $version) {
            return false;
        }

        $this->cache()->forever(self::CLEAN_VERSION_KEY, $version);

        return true;
    }

    public function lock(): Lock
    {
        return $this->cache()->lock(
            self::LOCK_KEY,
            max(30, (int) config('newsroom.seo_artifact_refresh_lock_seconds', 300)),
        );
    }

    private function cache(): Repository
    {
        $store = trim((string) config('newsroom.seo_artifact_cache_store', ''));

        return $store === ''
            ? Cache::store()
            : Cache::store($store);
    }
}
