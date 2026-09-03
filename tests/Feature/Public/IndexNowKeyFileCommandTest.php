<?php

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    cleanupIndexNowTestFiles();

    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('indexnow.host', 'prawkonaraz.pl');
    config()->set('indexnow.key_location', null);
});

afterEach(function (): void {
    cleanupIndexNowTestFiles();
});

function cleanupIndexNowTestFiles(): void
{
    foreach (File::glob(public_path('indexnow-test-*.txt')) ?: [] as $file) {
        File::delete($file);
    }

    foreach (File::glob(public_path('d0eff-test-*.txt')) ?: [] as $file) {
        File::delete($file);
    }

    File::deleteDirectory(storage_path('app/testing-indexnow-key-file'));
}

test('indexnow key file command dry-run does not write the key file', function () {
    $key = 'indexnow-test-dry-run';

    config()->set('indexnow.key', $key);

    $this->artisan('seo:indexnow-key-file', ['--dry-run' => true])
        ->expectsOutputToContain('IndexNow key file target:')
        ->expectsOutputToContain('https://prawkonaraz.pl/'.$key.'.txt')
        ->expectsOutput('IndexNow key file dry-run finished. No file was written.')
        ->assertSuccessful();

    expect(File::exists(public_path($key.'.txt')))->toBeFalse();
});

test('indexnow key file command writes the configured key file', function () {
    $key = 'indexnow-test-write';
    $targetPath = public_path($key.'.txt');

    config()->set('indexnow.key', $key);

    $this->artisan('seo:indexnow-key-file')
        ->expectsOutputToContain('IndexNow key file target:')
        ->expectsOutputToContain('https://prawkonaraz.pl/'.$key.'.txt')
        ->expectsOutput('IndexNow key file written.')
        ->assertSuccessful();

    expect(File::exists($targetPath))->toBeTrue();
    expect(trim(File::get($targetPath)))->toBe($key);
});

test('indexnow key file command can read the key from a local source file', function () {
    $key = 'd0eff-test-source';
    $sourcePath = storage_path('app/testing-indexnow-key-file/source.txt');
    $targetPath = public_path($key.'.txt');

    File::ensureDirectoryExists(dirname($sourcePath));
    File::put($sourcePath, $key.PHP_EOL);

    $this->artisan('seo:indexnow-key-file', [
        '--source' => $sourcePath,
    ])
        ->expectsOutput('IndexNow key does not use the optional "indexnow-" prefix; this is valid.')
        ->expectsOutputToContain('IndexNow key file target:')
        ->expectsOutput('IndexNow key file written.')
        ->assertSuccessful();

    expect(File::exists($targetPath))->toBeTrue();
    expect(trim(File::get($targetPath)))->toBe($key);
});

test('indexnow key file command fails when the source file is missing', function () {
    $sourcePath = storage_path('app/testing-indexnow-key-file/missing.txt');

    $this->artisan('seo:indexnow-key-file', [
        '--source' => $sourcePath,
    ])
        ->expectsOutput('IndexNow key source file does not exist: '.$sourcePath)
        ->expectsOutput('INDEXNOW_KEY is not configured.')
        ->assertFailed();
});

test('indexnow key file command rejects missing and invalid keys', function () {
    config()->set('indexnow.key', '');

    $this->artisan('seo:indexnow-key-file')
        ->expectsOutput('INDEXNOW_KEY is not configured.')
        ->assertFailed();

    config()->set('indexnow.key', 'bad key!');

    $this->artisan('seo:indexnow-key-file')
        ->expectsOutput('Invalid INDEXNOW_KEY format. Use 8-128 letters, numbers or dashes.')
        ->assertFailed();
});

test('indexnow key file command does not overwrite a different existing file without force', function () {
    $key = 'indexnow-test-force';
    $targetPath = public_path($key.'.txt');

    config()->set('indexnow.key', $key);
    File::put($targetPath, 'different-content');

    $this->artisan('seo:indexnow-key-file')
        ->expectsOutputToContain('IndexNow key file already exists with different content.')
        ->assertFailed();

    expect(File::get($targetPath))->toBe('different-content');

    $this->artisan('seo:indexnow-key-file', ['--force' => true])
        ->expectsOutput('IndexNow key file written.')
        ->assertSuccessful();

    expect(trim(File::get($targetPath)))->toBe($key);
});

test('indexnow key file command accepts an existing file with matching content', function () {
    $key = 'indexnow-test-existing';
    $targetPath = public_path($key.'.txt');

    config()->set('indexnow.key', $key);
    File::put($targetPath, $key.PHP_EOL);

    $this->artisan('seo:indexnow-key-file')
        ->expectsOutput('IndexNow key file already exists with expected content.')
        ->assertSuccessful();

    expect(trim(File::get($targetPath)))->toBe($key);
});

test('indexnow key file command accepts an existing file without trailing newline', function () {
    $key = 'indexnow-test-no-newline';
    $targetPath = public_path($key.'.txt');

    config()->set('indexnow.key', $key);
    File::put($targetPath, $key);

    $this->artisan('seo:indexnow-key-file')
        ->expectsOutput('IndexNow key file already exists with expected content.')
        ->assertSuccessful();

    expect(File::get($targetPath))->toBe($key);
});

test('indexnow key file command force normalizes an existing file with matching trimmed content', function () {
    $key = 'indexnow-test-normalize';
    $targetPath = public_path($key.'.txt');
    $nonNormalizedContent = $key.(PHP_EOL === "\n" ? "\r\n" : "\n");

    config()->set('indexnow.key', $key);
    File::put($targetPath, $nonNormalizedContent);

    $this->artisan('seo:indexnow-key-file', ['--force' => true])
        ->expectsOutput('IndexNow key file written.')
        ->assertSuccessful();

    expect(File::get($targetPath))->toBe($key.PHP_EOL);
});
