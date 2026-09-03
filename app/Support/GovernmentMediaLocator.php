<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class GovernmentMediaLocator
{
    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $index = [];

    /**
     * @param  array<int, string>  $sources
     */
    public function __construct(array $sources)
    {
        foreach ($sources as $source) {
            $this->indexSource($source);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function locate(string $basename): ?array
    {
        $matches = $this->index[Str::lower($basename)] ?? null;

        return $matches[0] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function duplicates(string $basename): array
    {
        return $this->index[Str::lower($basename)] ?? [];
    }

    /**
     * @return array<int, array{basename:string,count:int,candidates:array<int, array<string, mixed>>}>
     */
    public function duplicateReport(): array
    {
        $duplicates = [];

        foreach ($this->index as $basename => $candidates) {
            if (count($candidates) < 2) {
                continue;
            }

            $duplicates[] = [
                'basename' => $basename,
                'count' => count($candidates),
                'candidates' => $candidates,
            ];
        }

        return $duplicates;
    }

    /**
     * @return array<string, mixed>
     */
    public function materialize(string $basename, string $destination): array
    {
        $asset = $this->locate($basename);

        if ($asset === null) {
            throw new RuntimeException(sprintf('Nie znaleziono media o nazwie %s.', $basename));
        }

        File::ensureDirectoryExists(dirname($destination));

        if (is_file($destination)) {
            return [
                'status' => 'skipped',
                'source' => $asset,
                'destination' => $destination,
            ];
        }

        if ($asset['type'] === 'directory') {
            File::copy((string) $asset['path'], $destination);
        } else {
            $zip = new ZipArchive;

            if ($zip->open((string) $asset['source']) !== true) {
                throw new RuntimeException(sprintf('Nie mozna otworzyc archiwum %s.', (string) $asset['source']));
            }

            try {
                $stream = $zip->getStream((string) $asset['entry']);

                if (! is_resource($stream)) {
                    throw new RuntimeException(sprintf('Nie mozna odczytac wpisu %s z archiwum.', (string) $asset['entry']));
                }

                $target = fopen($destination, 'wb');

                if (! is_resource($target)) {
                    fclose($stream);

                    throw new RuntimeException(sprintf('Nie mozna zapisac pliku %s.', $destination));
                }

                try {
                    stream_copy_to_stream($stream, $target);
                } finally {
                    fclose($stream);
                    fclose($target);
                }
            } finally {
                $zip->close();
            }
        }

        return [
            'status' => 'materialized',
            'source' => $asset,
            'destination' => $destination,
        ];
    }

    protected function indexSource(string $source): void
    {
        if (is_dir($source)) {
            foreach (File::allFiles($source) as $file) {
                $this->pushAsset(strtolower($file->getFilename()), [
                    'type' => 'directory',
                    'source' => $source,
                    'path' => $file->getPathname(),
                    'bytes' => $file->getSize(),
                ]);
            }

            return;
        }

        if (is_file($source) && strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'zip') {
            $zip = new ZipArchive;

            if ($zip->open($source) !== true) {
                throw new RuntimeException(sprintf('Nie mozna otworzyc archiwum media: %s', $source));
            }

            try {
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $stat = $zip->statIndex($index);

                    if (! is_array($stat)) {
                        continue;
                    }

                    $entryName = str_replace('\\', '/', (string) ($stat['name'] ?? ''));

                    if ($entryName === '' || str_ends_with($entryName, '/')) {
                        continue;
                    }

                    $this->pushAsset(strtolower(basename($entryName)), [
                        'type' => 'zip',
                        'source' => $source,
                        'entry' => $entryName,
                        'bytes' => (int) ($stat['size'] ?? 0),
                    ]);
                }
            } finally {
                $zip->close();
            }

            return;
        }

        throw new RuntimeException(sprintf('Nieobslugiwane zrodlo mediow: %s', $source));
    }

    /**
     * @param  array<string, mixed>  $asset
     */
    protected function pushAsset(string $basename, array $asset): void
    {
        if ($basename === '') {
            return;
        }

        $this->index[$basename] ??= [];
        $this->index[$basename][] = $asset;
    }
}
