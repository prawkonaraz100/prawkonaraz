<?php

uses(Tests\TestCase::class);

test('php upload limits support configured explanation image uploads', function () {
    $toBytes = static function (string $value): int {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $suffix = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($suffix) {
            'g' => (int) round($number * 1024 * 1024 * 1024),
            'm' => (int) round($number * 1024 * 1024),
            'k' => (int) round($number * 1024),
            default => (int) round($number),
        };
    };

    $requiredBytes = (int) config('media.max_bytes.image', 8 * 1024 * 1024);
    $uploadLimit = $toBytes((string) ini_get('upload_max_filesize'));
    $postLimit = $toBytes((string) ini_get('post_max_size'));

    expect($uploadLimit)
        ->toBeGreaterThanOrEqual($requiredBytes)
        ->and($postLimit)->toBeGreaterThanOrEqual($requiredBytes);
});
