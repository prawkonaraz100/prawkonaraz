<?php

use App\Support\NewsroomMediaStorage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Storage::fake('newsroom_test');

    config([
        'media.public_disk' => 'newsroom_test',
        'media.public_base_url' => 'https://cdn.example.com/media',
        'media.newsroom_disk' => 'newsroom_test',
        'media.newsroom_prefix' => 'newsroom/articles',
        'media.newsroom_max_dimension' => 10000,
        'media.max_bytes.image' => 8 * 1024 * 1024,
        'media.allowed_mime_types.image' => [
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp',
            'image/avif',
            'image/svg+xml',
        ],
    ]);
});

function newsroomTestPng(): string
{
    return (string) base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAIAAAA2iEnWAAAAE0lEQVR4nGP8z8DAwMDAxIBMAQAUQAEF3SN5DgAAAABJRU5ErkJggg==',
        true,
    );
}

test('newsroom media contract reuses shared raster allowlist while excluding svg', function () {
    $storage = app(NewsroomMediaStorage::class);

    expect($storage->allowedMimeTypes())->toBe([
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
    ]);

    $storage->prepareImageUpload('image/svg+xml', 100);
})->throws(ValidationException::class);

test('prepared newsroom image uploads use a dedicated immutable namespace', function () {
    $storage = app(NewsroomMediaStorage::class);

    $first = $storage->prepareImageUpload('image/jpeg', 1024);
    $second = $storage->prepareImageUpload('image/jpg', 1024);

    expect($first['disk'])->toBe('newsroom_test')
        ->and($first['path'])->toMatch('#\Anewsroom/articles/source/[0-9a-hjkmnp-tv-z]{26}\.jpg\z#')
        ->and($first['mime_type'])->toBe('image/jpeg')
        ->and($second['mime_type'])->toBe('image/jpeg')
        ->and($second['path'])->not->toBe($first['path']);
});

test('stored newsroom images are inspected from actual bytes mime and dimensions', function () {
    $storage = app(NewsroomMediaStorage::class);
    $binary = newsroomTestPng();
    $upload = $storage->prepareImageUpload('image/png', strlen($binary));

    Storage::disk('newsroom_test')->put($upload['path'], $binary);

    $inspected = $storage->inspectStoredImage(
        $upload['path'],
        declaredMimeType: 'image/png',
        declaredBytes: strlen($binary),
    );

    expect($inspected)->toBe([
        'disk' => 'newsroom_test',
        'path' => $upload['path'],
        'mime_type' => 'image/png',
        'bytes' => strlen($binary),
        'width' => 2,
        'height' => 3,
    ]);
});

test('stored newsroom image inspection rejects client metadata mismatches', function (string $declaredMime, int $byteDelta) {
    $storage = app(NewsroomMediaStorage::class);
    $binary = newsroomTestPng();
    $upload = $storage->prepareImageUpload('image/png', strlen($binary));

    Storage::disk('newsroom_test')->put($upload['path'], $binary);

    $storage->inspectStoredImage(
        $upload['path'],
        declaredMimeType: $declaredMime,
        declaredBytes: strlen($binary) + $byteDelta,
    );
})->with([
    'mime mismatch' => ['image/jpeg', 0],
    'byte mismatch' => ['image/png', -1],
])->throws(ValidationException::class);

test('stored newsroom asset must actually be a supported raster image', function () {
    $storage = app(NewsroomMediaStorage::class);
    $upload = $storage->prepareImageUpload('image/png', 41);
    $fakeSvg = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';

    Storage::disk('newsroom_test')->put($upload['path'], $fakeSvg);

    $storage->inspectStoredImage($upload['path']);
})->throws(ValidationException::class);

test('stored newsroom image inspection enforces shared byte policy against the actual object', function () {
    $storage = app(NewsroomMediaStorage::class);
    $binary = newsroomTestPng();

    config()->set('media.max_bytes.image', strlen($binary) - 1);

    $path = $storage->newImagePath('image/png');
    Storage::disk('newsroom_test')->put($path, $binary);

    $storage->inspectStoredImage($path);
})->throws(ValidationException::class);

test('newsroom media paths cannot escape the dedicated immutable namespace', function (string $path) {
    app(NewsroomMediaStorage::class)->assertManagedPath($path);
})->with([
    '../private/file.png',
    'media/questions/full.png',
    'newsroom/articles/source/manual-name.png',
    'https://cdn.example.com/media/newsroom/articles/source/file.png',
])->throws(ValidationException::class);

test('public newsroom media url is resolved through the shared media url resolver', function () {
    $storage = app(NewsroomMediaStorage::class);
    $upload = $storage->prepareImageUpload('image/webp', 1000);

    $url = $storage->publicUrl($upload['path']);

    expect($url)
        ->toBe('https://cdn.example.com/media/'.$upload['path'])
        ->not->toContain('X-Amz-')
        ->not->toContain('signature=');
});

test('newsroom media dimension ceiling is enforced from actual image dimensions', function () {
    $storage = app(NewsroomMediaStorage::class);
    $binary = newsroomTestPng();
    $upload = $storage->prepareImageUpload('image/png', strlen($binary));

    Storage::disk('newsroom_test')->put($upload['path'], $binary);
    config()->set('media.newsroom_max_dimension', 2);

    $storage->inspectStoredImage($upload['path']);
})->throws(ValidationException::class);
