<?php

uses(Tests\TestCase::class);

test('media local disk uses a writable unix style root when enabled in linux environments', function () {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('This check is only relevant for Linux-based runtime environments.');
    }

    if (config('media.public_disk') !== 'media_local') {
        $this->markTestSkipped('The media_local disk is not active in this environment.');
    }

    $root = (string) config('filesystems.disks.media_local.root');

    expect($root)
        ->toStartWith('/')
        ->and($root)->not->toContain(':');

    if (is_dir($root)) {
        expect(is_writable($root))->toBeTrue();
    }
});
