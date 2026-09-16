<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasOptimisticLockVersion
{
    public static function bootHasOptimisticLockVersion(): void
    {
        static::updating(function (Model $model): void {
            $model->setAttribute(
                'lock_version',
                ((int) $model->getRawOriginal('lock_version')) + 1,
            );
        });
    }

    public function optimisticLockVersion(): int
    {
        return (int) $this->getAttribute('lock_version');
    }
}
