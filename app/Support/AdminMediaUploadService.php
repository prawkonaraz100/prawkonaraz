<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AdminMediaUploadService
{
    public function __construct(
        protected QuestionDeliveryReadinessService $questionDeliveryReadinessService,
    ) {}

    /**
     * @param  array{kind:string,mime_type:string,bytes:int,variant:string}  $attributes
     * @return array<string, mixed>
     */
    public function presign(User $user, Question $question, array $attributes, ?string $idempotencyKey = null): array
    {
        $question->loadMissing('licenseCategory');

        $kind = strtolower((string) $attributes['kind']);
        $mimeType = strtolower((string) $attributes['mime_type']);
        $bytes = (int) $attributes['bytes'];
        $variant = strtolower((string) $attributes['variant']);

        $this->validateUpload($kind, $mimeType, $bytes, $variant);

        if (filled($idempotencyKey)) {
            $cachedResponse = $this->resolveIdempotentPresign(
                $user,
                (string) $idempotencyKey,
                [
                    'question_id' => $question->getKey(),
                    'kind' => $kind,
                    'mime_type' => $mimeType,
                    'bytes' => $bytes,
                    'variant' => $variant,
                ],
            );

            if ($cachedResponse !== null) {
                return $cachedResponse;
            }
        }

        $disk = (string) config('media.upload_disk', 'r2');
        $filesystem = Storage::disk($disk);

        if (! $filesystem->providesTemporaryUploadUrls()) {
            throw new RuntimeException("Disk [{$disk}] does not support temporary upload URLs.");
        }

        $path = $this->buildPath($question, $kind, $variant, $mimeType);
        $expiresAt = now()->utc()->addMinutes((int) config('media.presign_ttl_minutes', 15));
        $upload = $filesystem->temporaryUploadUrl($path, $expiresAt, [
            'ContentType' => $mimeType,
            'ContentLength' => $bytes,
        ]);

        $response = [
            'upload_token' => (string) Str::ulid(),
            'question_id' => $question->getKey(),
            'kind' => $kind,
            'variant' => $variant,
            'mime_type' => $mimeType,
            'bytes' => $bytes,
            'disk' => $disk,
            'path' => $path,
            'method' => 'PUT',
            'upload_url' => (string) ($upload['url'] ?? ''),
            'headers' => $this->normalizeHeaders((array) ($upload['headers'] ?? []), $mimeType),
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        $this->cache()->put(
            $this->ticketCacheKey($response['upload_token']),
            [
                'user_id' => $user->getKey(),
                'question_id' => $question->getKey(),
                'kind' => $kind,
                'mime_type' => $mimeType,
                'bytes' => $bytes,
                'variant' => $variant,
                'disk' => $disk,
                'path' => $path,
                'expires_at' => $response['expires_at'],
                'response' => $response,
            ],
            $expiresAt,
        );

        if (filled($idempotencyKey)) {
            $this->cache()->put(
                $this->idempotencyCacheKey($user, (string) $idempotencyKey),
                [
                    'payload_hash' => $this->payloadHash([
                        'question_id' => $question->getKey(),
                        'kind' => $kind,
                        'mime_type' => $mimeType,
                        'bytes' => $bytes,
                        'variant' => $variant,
                    ]),
                    'upload_token' => $response['upload_token'],
                ],
                $expiresAt,
            );
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{media:QuestionMedia,status:int}
     */
    public function confirm(User $user, array $attributes): array
    {
        $ticket = $this->ticketFor($user, (string) $attributes['upload_token']);

        if (($ticket['kind'] ?? null) === 'image' && ($ticket['variant'] ?? null) === 'poster') {
            throw ValidationException::withMessages([
                'upload_token' => 'Poster assets must be attached to a video via poster_upload_token.',
            ]);
        }

        $posterTicket = null;

        if (filled($attributes['poster_upload_token'] ?? null)) {
            $posterTicket = $this->ticketFor($user, (string) $attributes['poster_upload_token']);

            if (($ticket['kind'] ?? null) !== 'video') {
                throw ValidationException::withMessages([
                    'poster_upload_token' => 'Poster uploads can only be attached to video assets.',
                ]);
            }

            if (($posterTicket['question_id'] ?? null) !== ($ticket['question_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'poster_upload_token' => 'Poster upload must belong to the same question.',
                ]);
            }

            if (($posterTicket['kind'] ?? null) !== 'image' || ($posterTicket['variant'] ?? null) !== 'poster') {
                throw ValidationException::withMessages([
                    'poster_upload_token' => 'Poster upload token must reference an image poster asset.',
                ]);
            }
        }

        $filesystem = Storage::disk((string) $ticket['disk']);

        if (! $filesystem->exists((string) $ticket['path'])) {
            throw ValidationException::withMessages([
                'upload_token' => 'Uploaded asset was not found on storage.',
            ]);
        }

        if ($posterTicket !== null && ! $filesystem->exists((string) $posterTicket['path'])) {
            throw ValidationException::withMessages([
                'poster_upload_token' => 'Uploaded poster asset was not found on storage.',
            ]);
        }

        $bytes = $this->storedBytes($filesystem, (string) $ticket['path'], (int) ($ticket['bytes'] ?? 0));
        $question = Question::query()->findOrFail((int) $ticket['question_id']);

        $media = QuestionMedia::query()->updateOrCreate(
            [
                'question_id' => $question->getKey(),
                'path' => (string) $ticket['path'],
            ],
            [
                'kind' => (string) $ticket['kind'],
                'disk' => (string) $ticket['disk'],
                'poster_path' => $posterTicket['path'] ?? null,
                'mime_type' => (string) $ticket['mime_type'],
                'bytes' => $bytes,
                'duration_seconds' => isset($attributes['duration_seconds']) ? (int) $attributes['duration_seconds'] : null,
                'width' => isset($attributes['width']) ? (int) $attributes['width'] : null,
                'height' => isset($attributes['height']) ? (int) $attributes['height'] : null,
                'variant' => (string) $ticket['variant'],
                'sort_order' => isset($attributes['sort_order']) ? (int) $attributes['sort_order'] : 0,
                'metadata' => is_array($attributes['metadata'] ?? null) ? $attributes['metadata'] : null,
            ],
        );

        $this->questionDeliveryReadinessService->sync($question);

        return [
            'media' => $media,
            'status' => $media->wasRecentlyCreated ? 201 : 200,
        ];
    }

    public function delete(QuestionMedia $media): void
    {
        $questionId = $media->question_id;
        $disk = $media->disk;
        $paths = collect([$media->path, $media->poster_path])
            ->filter()
            ->unique()
            ->values();

        if (is_array(config("filesystems.disks.{$disk}")) && $paths->isNotEmpty()) {
            $filesystem = Storage::disk($disk);

            foreach ($paths as $path) {
                try {
                    $filesystem->delete((string) $path);
                } catch (\Throwable) {
                    // Keep database cleanup resilient even if the object is already gone.
                }
            }
        }

        $question = Question::query()->find($questionId);

        DB::transaction(function () use ($media, $questionId): void {
            $media->delete();

            QuestionMedia::query()
                ->where('question_id', $questionId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->values()
                ->each(fn (QuestionMedia $item, int $index) => $item->update([
                    'sort_order' => $index,
                ]));
        });

        if ($question !== null) {
            $this->questionDeliveryReadinessService->sync($question);
        }
    }

    /**
     * @param  array<int, int>  $mediaIds
     * @return Collection<int, QuestionMedia>
     */
    public function reorder(Question $question, array $mediaIds): Collection
    {
        $media = $question->media()
            ->whereIn('id', $mediaIds)
            ->get()
            ->keyBy('id');

        if ($media->count() !== count($mediaIds)) {
            throw ValidationException::withMessages([
                'media_ids' => 'One or more media items do not belong to the selected question.',
            ]);
        }

        DB::transaction(function () use ($mediaIds, $media): void {
            foreach (array_values($mediaIds) as $sortOrder => $mediaId) {
                $media->get($mediaId)?->update([
                    'sort_order' => $sortOrder,
                ]);
            }
        });

        return $question->fresh('media')->media;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function resolveIdempotentPresign(User $user, string $idempotencyKey, array $payload): ?array
    {
        $entry = $this->cache()->get($this->idempotencyCacheKey($user, $idempotencyKey));

        if (! is_array($entry)) {
            return null;
        }

        if (($entry['payload_hash'] ?? null) !== $this->payloadHash($payload)) {
            throw new ConflictHttpException('Idempotency key is already associated with another upload payload.');
        }

        $ticket = $this->cache()->get($this->ticketCacheKey((string) ($entry['upload_token'] ?? '')));

        return is_array($ticket) ? ($ticket['response'] ?? null) : null;
    }

    protected function validateUpload(string $kind, string $mimeType, int $bytes, string $variant): void
    {
        if (! in_array($kind, ['image', 'video'], true)) {
            throw ValidationException::withMessages([
                'kind' => 'Unsupported media kind.',
            ]);
        }

        if (! in_array($mimeType, (array) config("media.allowed_mime_types.{$kind}", []), true)) {
            throw ValidationException::withMessages([
                'mime_type' => 'Unsupported MIME type for the selected media kind.',
            ]);
        }

        if (! in_array($variant, (array) config("media.allowed_variants.{$kind}", []), true)) {
            throw ValidationException::withMessages([
                'variant' => 'Unsupported media variant for the selected media kind.',
            ]);
        }

        $maxBytes = (int) config("media.max_bytes.{$kind}", 0);

        if ($maxBytes > 0 && $bytes > $maxBytes) {
            throw ValidationException::withMessages([
                'bytes' => "File size exceeds the {$kind} upload limit.",
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function ticketFor(User $user, string $token): array
    {
        $ticket = $this->cache()->get($this->ticketCacheKey($token));

        if (! is_array($ticket)) {
            throw ValidationException::withMessages([
                'upload_token' => 'Upload token is invalid or expired.',
            ]);
        }

        if (($ticket['user_id'] ?? null) !== $user->getKey()) {
            abort(403);
        }

        return $ticket;
    }

    protected function buildPath(Question $question, string $kind, string $variant, string $mimeType): string
    {
        $prefix = trim((string) config('media.upload_prefix', 'media/questions'), '/');
        $categorySegment = $this->slugSegment((string) ($question->licenseCategory?->code ?? $question->license_category_id), 'category');
        $questionSegment = $this->slugSegment((string) ($question->external_id ?: 'question-'.$question->getKey()), 'question-'.$question->getKey());

        return implode('/', [
            $prefix,
            $categorySegment,
            $questionSegment,
            $kind,
            "{$variant}.".Str::lower((string) Str::ulid()).'.'.$this->extensionForMime($mimeType),
        ]);
    }

    protected function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'video/mp4' => 'mp4',
            default => throw new RuntimeException("Unsupported MIME type [{$mimeType}] for upload path generation."),
        };
    }

    protected function slugSegment(string $value, string $fallback): string
    {
        $segment = Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->value();

        return $segment !== '' ? $segment : $fallback;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode(Arr::sortRecursive($payload)) ?: '');
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    protected function normalizeHeaders(array $headers, string $mimeType): array
    {
        $normalized = collect($headers)
            ->map(function (mixed $value): mixed {
                if (! is_array($value)) {
                    return (string) $value;
                }

                $values = array_map(static fn (mixed $headerValue): string => (string) $headerValue, $value);

                return count($values) === 1 ? $values[0] : $values;
            })
            ->all();

        $normalized['Content-Type'] = $mimeType;

        return $normalized;
    }

    protected function storedBytes(mixed $filesystem, string $path, int $fallback): int
    {
        try {
            return (int) $filesystem->size($path);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    protected function ticketCacheKey(string $token): string
    {
        return 'admin-media-upload:ticket:'.$token;
    }

    protected function idempotencyCacheKey(User $user, string $idempotencyKey): string
    {
        return 'admin-media-upload:idempotency:'.$user->getKey().':'.$idempotencyKey;
    }

    protected function cache(): CacheRepository
    {
        $store = config('media.presign_cache_store');

        return filled($store)
            ? Cache::store((string) $store)
            : Cache::store();
    }
}
