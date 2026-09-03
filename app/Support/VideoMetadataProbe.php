<?php

namespace App\Support;

use Symfony\Component\Process\Process;
use Throwable;

class VideoMetadataProbe
{
    /**
     * @return array{duration_seconds:int|null,width:int|null,height:int|null,source:string|null,error:string|null}
     */
    public function probe(string $sourcePath, int $timeoutSeconds = 20): array
    {
        $empty = [
            'duration_seconds' => null,
            'width' => null,
            'height' => null,
            'source' => null,
            'error' => null,
        ];

        $binary = trim((string) config('media.ffprobe_binary', 'ffprobe'));

        if ($binary === '') {
            return array_replace($empty, ['error' => 'ffprobe binary is not configured.']);
        }

        try {
            $process = new Process([
                $binary,
                '-v',
                'error',
                '-select_streams',
                'v:0',
                '-show_entries',
                'stream=width,height,duration:format=duration',
                '-of',
                'json',
                $sourcePath,
            ]);
            $process->setTimeout(max(1, $timeoutSeconds));
            $process->run();

            if (! $process->isSuccessful()) {
                $message = trim($process->getErrorOutput()) ?: trim($process->getOutput());

                return array_replace($empty, ['error' => $message !== '' ? $message : 'ffprobe failed.']);
            }

            $payload = json_decode($process->getOutput(), true);

            if (! is_array($payload)) {
                return array_replace($empty, ['error' => 'ffprobe returned invalid JSON.']);
            }

            $stream = is_array($payload['streams'][0] ?? null)
                ? $payload['streams'][0]
                : [];
            $format = is_array($payload['format'] ?? null)
                ? $payload['format']
                : [];

            return [
                'duration_seconds' => $this->normalizeDuration($stream['duration'] ?? $format['duration'] ?? null),
                'width' => isset($stream['width']) ? max((int) $stream['width'], 0) ?: null : null,
                'height' => isset($stream['height']) ? max((int) $stream['height'], 0) ?: null : null,
                'source' => 'ffprobe',
                'error' => null,
            ];
        } catch (Throwable $exception) {
            return array_replace($empty, ['error' => $exception->getMessage()]);
        }
    }

    protected function normalizeDuration(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $duration = (float) $value;

        if ($duration <= 0) {
            return null;
        }

        return max(1, (int) round($duration));
    }
}
