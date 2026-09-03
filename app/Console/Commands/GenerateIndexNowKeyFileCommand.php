<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateIndexNowKeyFileCommand extends Command
{
    protected $signature = 'seo:indexnow-key-file
        {--source= : Read the IndexNow key from a local text file instead of INDEXNOW_KEY}
        {--dry-run : Show the target path and URL without writing the file}
        {--force : Overwrite an existing key file when its content differs from the normalized key file}';

    protected $description = 'Generate the public IndexNow key verification file under public/.';

    public function handle(): int
    {
        $key = $this->resolveKey();

        if ($key === '') {
            $this->error('INDEXNOW_KEY is not configured.');

            return self::FAILURE;
        }

        if (! $this->isValidKey($key)) {
            $this->error('Invalid INDEXNOW_KEY format. Use 8-128 letters, numbers or dashes.');

            return self::FAILURE;
        }

        $filename = $key.'.txt';
        $targetPath = public_path($filename);
        $publicUrl = $this->keyLocation($filename);
        $expectedContent = $key.PHP_EOL;
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! str_starts_with($key, 'indexnow-')) {
            $this->warn('IndexNow key does not use the optional "indexnow-" prefix; this is valid.');
        }

        $this->line('IndexNow key file target: '.$targetPath);
        $this->line('IndexNow key file URL: '.$publicUrl);

        if ($dryRun) {
            $this->info('IndexNow key file dry-run finished. No file was written.');

            return self::SUCCESS;
        }

        if (File::exists($targetPath)) {
            $currentContent = File::get($targetPath);

            if ($currentContent === $expectedContent || (! $force && trim($currentContent) === $key)) {
                $this->info('IndexNow key file already exists with expected content.');

                return self::SUCCESS;
            }

            if (! $force) {
                $this->error('IndexNow key file already exists with different content. Re-run with --force to overwrite it.');

                return self::FAILURE;
            }
        }

        File::put($targetPath, $expectedContent);

        $this->info('IndexNow key file written.');

        return self::SUCCESS;
    }

    private function isValidKey(string $key): bool
    {
        return preg_match('/\A[A-Za-z0-9-]{8,128}\z/', $key) === 1;
    }

    private function resolveKey(): string
    {
        $source = $this->option('source');

        if (is_string($source) && trim($source) !== '') {
            $path = $this->resolveSourcePath(trim($source));

            if (! File::isFile($path)) {
                $this->error('IndexNow key source file does not exist: '.$path);

                return '';
            }

            return trim(File::get($path));
        }

        return trim((string) config('indexnow.key', ''));
    }

    private function resolveSourcePath(string $source): string
    {
        if (
            str_starts_with($source, '/')
            || str_starts_with($source, '\\\\')
            || preg_match('/\A[A-Za-z]:[\\\\\\/]/', $source) === 1
        ) {
            return $source;
        }

        return base_path($source);
    }

    private function keyLocation(string $filename): string
    {
        $configuredLocation = trim((string) config('indexnow.key_location', ''));

        if ($configuredLocation !== '') {
            return $configuredLocation;
        }

        $appUrl = rtrim((string) config('app.url', 'https://prawkonaraz.pl'), '/');

        return $appUrl.'/'.$filename;
    }
}
