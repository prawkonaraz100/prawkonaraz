<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemSettingsStore
{
    protected ?bool $isAvailable = null;

    public function get(string $key, mixed $default = null): mixed
    {
        if (! $this->available()) {
            return $default;
        }

        try {
            return SystemSetting::query()
                ->where('key', $key)
                ->value('payload') ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }

    public function put(string $key, mixed $payload): void
    {
        if (! $this->available()) {
            return;
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['payload' => $payload],
        );
    }

    public function forget(string $key): void
    {
        if (! $this->available()) {
            return;
        }

        SystemSetting::query()
            ->where('key', $key)
            ->delete();
    }

    public function has(string $key): bool
    {
        if (! $this->available()) {
            return false;
        }

        try {
            return SystemSetting::query()
                ->where('key', $key)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    protected function available(): bool
    {
        if ($this->isAvailable !== null) {
            return $this->isAvailable;
        }

        try {
            return $this->isAvailable = Schema::hasTable('system_settings');
        } catch (Throwable) {
            return $this->isAvailable = false;
        }
    }
}
