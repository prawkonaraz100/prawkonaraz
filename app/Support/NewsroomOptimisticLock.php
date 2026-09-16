<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

final class NewsroomOptimisticLock
{
    public static function assertVersion(
        Model $record,
        int $expectedVersion,
        string $entityLabel = 'Rekord',
    ): void {
        $actualVersion = (int) $record->getAttribute('lock_version');

        if ($actualVersion === $expectedVersion) {
            return;
        }

        throw new NewsroomStaleWriteException(
            "{$entityLabel} został zmieniony przez inną operację lub użytkownika. Odśwież dane i ponów zmianę.",
        );
    }
}
