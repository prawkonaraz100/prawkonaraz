<?php

namespace App\Support;

class StudySessionAnswerKind
{
    public const CHOICE = 'choice';

    public const UNKNOWN = 'unknown';

    public const TIMEOUT = 'timeout';

    public const SKIPPED = 'skipped';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::CHOICE,
            self::UNKNOWN,
            self::TIMEOUT,
            self::SKIPPED,
        ];
    }

    public static function isValid(string $answerKind): bool
    {
        return in_array($answerKind, self::values(), true);
    }
}
