<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationAsset;
use App\Models\QuestionMedia;
use App\Models\QuestionSignLanguageAsset;
use App\Models\SharedQuestionExplanationAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class QuestionMediaSitemapReadinessAuditor
{
    protected const SAMPLE_LIMIT = 100;

    protected int $httpChecked = 0;

    protected int $videoBlockingIssueCount = 0;

    /**
     * @var array<string, mixed>
     */
    protected array $report = [];

    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function audit(bool $checkHttp = false, int $httpLimit = 100, int $httpTimeout = 5): array
    {
        $this->httpChecked = 0;
        $this->videoBlockingIssueCount = 0;
        $this->report = [
            'generated_at' => now()->toIso8601String(),
            'checks' => [
                'storage_exists' => true,
                'http_public_urls' => $checkHttp,
                'http_limit' => $checkHttp ? $httpLimit : 0,
                'http_timeout_seconds' => $httpTimeout,
            ],
            'questions' => $this->questionSummary(),
            'question_media' => $this->emptySection(),
            'pjm_sign_language' => $this->emptySection(),
            'explanation_assets' => $this->emptySection(),
            'issues' => [
                'errors' => [],
                'warnings' => [],
            ],
            'issue_counts' => [
                'errors' => 0,
                'warnings' => 0,
            ],
            'issue_type_counts' => [
                'errors' => [],
                'warnings' => [],
            ],
            'readiness' => [
                'image_sitemap' => [
                    'status' => 'unknown',
                    'candidate_urls' => 0,
                    'note' => 'Image sitemap may contain only image URLs or video poster thumbnails, never raw video files.',
                ],
                'video_sitemap' => [
                    'status' => 'unknown',
                    'candidate_urls' => 0,
                    'blocking_errors' => 0,
                    'note' => 'Video sitemap requires public video content/player URLs, thumbnails, titles and descriptions on public host pages.',
                ],
                'pjm_video_sitemap' => [
                    'status' => 'not_ready',
                    'reason' => 'PJM assets are used in learning flows, not as public canonical question-page videos.',
                ],
            ],
        ];

        $this->auditQuestionMedia($checkHttp, $httpLimit, $httpTimeout);
        $this->auditPjmAssets($checkHttp, $httpLimit, $httpTimeout);
        $this->auditExplanationAssets($checkHttp, $httpLimit, $httpTimeout);
        $this->finalizeReadiness();

        return $this->report;
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionSummary(): array
    {
        $publicQuestions = $this->publicQuestionQuery();

        return [
            'public_rows' => (clone $publicQuestions)->count(),
            'canonical_external_ids' => (clone $publicQuestions)
                ->whereNotNull('external_id')
                ->distinct()
                ->count('external_id'),
            'rows_requiring_primary_media' => (clone $publicQuestions)
                ->where('requires_primary_media', true)
                ->count(),
            'rows_requiring_primary_media_without_media' => (clone $publicQuestions)
                ->where('requires_primary_media', true)
                ->whereDoesntHave('media')
                ->count(),
            'rows_with_question_media' => (clone $publicQuestions)
                ->whereHas('media')
                ->count(),
            'rows_with_image_media' => (clone $publicQuestions)
                ->whereHas('media', fn (Builder $query) => $query->where('kind', 'image'))
                ->count(),
            'rows_with_video_media' => (clone $publicQuestions)
                ->whereHas('media', fn (Builder $query) => $query->where('kind', 'video'))
                ->count(),
        ];
    }

    protected function auditQuestionMedia(bool $checkHttp, int $httpLimit, int $httpTimeout): void
    {
        $allowedImageMimes = (array) config('media.allowed_mime_types.image', []);
        $allowedVideoMimes = (array) config('media.allowed_mime_types.video', []);
        $section = $this->emptySection() + [
            'image_sitemap_candidates' => 0,
            'video_sitemap_candidates' => 0,
            'videos_missing_posters' => 0,
            'videos_missing_duration' => 0,
            'rows_missing_dimensions' => 0,
        ];

        QuestionMedia::query()
            ->with([
                'question:id,license_category_id,external_id,prompt,explanation,is_active,delivery_issue,requires_primary_media',
                'question.licenseCategory:id,code,slug,is_active',
            ])
            ->whereHas('question', fn (Builder $query) => $this->applyPublicQuestionScope($query))
            ->lazyById()
            ->each(function (QuestionMedia $media) use (&$section, $allowedImageMimes, $allowedVideoMimes, $checkHttp, $httpLimit, $httpTimeout): void {
                $this->incrementCounters($section, $media->kind, $media->disk, $media->mime_type, $media->variant);

                $context = [
                    'id' => $media->getKey(),
                    'question_id' => $media->question_id,
                    'external_id' => $media->question?->external_id,
                    'kind' => $media->kind,
                    'disk' => $media->disk,
                    'path' => $media->path,
                ];

                $asset = $this->validatePath(
                    path: $media->path,
                    disk: $media->disk,
                    context: $context,
                    field: 'path',
                    checkHttp: $checkHttp,
                    httpLimit: $httpLimit,
                    httpTimeout: $httpTimeout,
                );

                if (! in_array($media->kind, ['image', 'video'], true)) {
                    $this->issue('errors', 'unsupported_question_media_kind', 'question_media.kind must be image or video.', $context);

                    return;
                }

                if ($media->kind === 'image') {
                    if (filled($media->mime_type) && ! in_array($media->mime_type, $allowedImageMimes, true)) {
                        $this->issue('warnings', 'unsupported_image_mime', 'Image media mime_type is outside configured image mime types.', $context);
                    }

                    if (! $media->width || ! $media->height) {
                        $section['rows_missing_dimensions']++;
                        $this->issue('warnings', 'image_missing_dimensions', 'Image media is missing width or height metadata.', $context);
                    }

                    if ($asset['ok'] && $this->looksLikeImageUrl((string) $asset['url'], (string) $media->mime_type)) {
                        $section['image_sitemap_candidates']++;
                    }

                    return;
                }

                if (filled($media->mime_type) && ! in_array($media->mime_type, $allowedVideoMimes, true)) {
                    $this->issue('errors', 'unsupported_video_mime', 'Video media mime_type is outside configured video mime types.', $context);
                }

                if (! $media->width || ! $media->height) {
                    $section['rows_missing_dimensions']++;
                    $this->issue('warnings', 'video_missing_dimensions', 'Video media is missing width or height metadata.', $context);
                }

                if (! $media->duration_seconds) {
                    $section['videos_missing_duration']++;
                    $this->issue('warnings', 'video_missing_duration', 'Video sitemap duration is optional, but this video is missing duration metadata.', $context);
                }

                if (blank($media->poster_path)) {
                    $section['videos_missing_posters']++;
                    $this->issue('errors', 'video_missing_poster', 'Video sitemap requires a thumbnail/poster URL.', $context);

                    return;
                }

                $poster = $this->validatePath(
                    path: $media->poster_path,
                    disk: $media->disk,
                    context: $context + ['poster_path' => $media->poster_path],
                    field: 'poster_path',
                    checkHttp: $checkHttp,
                    httpLimit: $httpLimit,
                    httpTimeout: $httpTimeout,
                );

                if ($poster['ok'] && ! $this->looksLikeImageUrl((string) $poster['url'], null)) {
                    $this->issue('errors', 'video_poster_not_image', 'Video poster URL does not look like an image file.', $context + ['poster_url' => $poster['url']]);
                }

                if ($asset['ok'] && $poster['ok'] && filled($media->question?->prompt) && filled($media->question?->explanation)) {
                    $section['video_sitemap_candidates']++;
                    $section['image_sitemap_candidates']++;
                }
            });

        $this->report['question_media'] = $section;
    }

    protected function auditPjmAssets(bool $checkHttp, int $httpLimit, int $httpTimeout): void
    {
        $section = $this->emptySection();

        QuestionSignLanguageAsset::query()
            ->where('is_active', true)
            ->whereIn('processing_status', [
                QuestionSignLanguageAsset::STATUS_READY,
                QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
            ])
            ->lazyById()
            ->each(function (QuestionSignLanguageAsset $asset) use (&$section, $checkHttp, $httpLimit, $httpTimeout): void {
                $this->incrementCounters($section, $asset->asset_role, $asset->disk, $asset->mime_type, $asset->variant, $asset->processing_status);

                $context = [
                    'id' => $asset->getKey(),
                    'external_id' => $asset->external_id,
                    'role' => $asset->asset_role,
                    'status' => $asset->processing_status,
                    'disk' => $asset->disk,
                    'path' => $asset->path,
                ];

                $this->validatePath(
                    path: $asset->path,
                    disk: $asset->disk,
                    context: $context,
                    field: 'path',
                    checkHttp: $checkHttp,
                    httpLimit: $httpLimit,
                    httpTimeout: $httpTimeout,
                );

                if (! $asset->duration_seconds) {
                    $this->issue('warnings', 'pjm_missing_duration', 'PJM asset is missing duration metadata.', $context);
                }

                if (! $asset->width || ! $asset->height) {
                    $this->issue('warnings', 'pjm_missing_dimensions', 'PJM asset is missing width or height metadata.', $context);
                }
            });

        $this->report['pjm_sign_language'] = $section;
    }

    protected function auditExplanationAssets(bool $checkHttp, int $httpLimit, int $httpTimeout): void
    {
        $section = $this->emptySection();

        QuestionExplanationAsset::query()
            ->where('is_active', true)
            ->whereNotNull('file_path')
            ->lazyById()
            ->each(function (QuestionExplanationAsset $asset) use (&$section, $checkHttp, $httpLimit, $httpTimeout): void {
                $this->incrementCounters($section, $asset->kind, $asset->disk, null, null);
                $this->validatePath(
                    path: $asset->file_path,
                    disk: $asset->disk,
                    context: [
                        'id' => $asset->getKey(),
                        'table' => 'question_explanation_assets',
                        'question_id' => $asset->question_id,
                        'kind' => $asset->kind,
                        'disk' => $asset->disk,
                        'path' => $asset->file_path,
                    ],
                    field: 'file_path',
                    checkHttp: $checkHttp,
                    httpLimit: $httpLimit,
                    httpTimeout: $httpTimeout,
                );
            });

        SharedQuestionExplanationAsset::query()
            ->where('is_active', true)
            ->whereNotNull('file_path')
            ->lazyById()
            ->each(function (SharedQuestionExplanationAsset $asset) use (&$section, $checkHttp, $httpLimit, $httpTimeout): void {
                $this->incrementCounters($section, $asset->kind, $asset->disk, null, null);
                $this->validatePath(
                    path: $asset->file_path,
                    disk: $asset->disk,
                    context: [
                        'id' => $asset->getKey(),
                        'table' => 'shared_question_explanation_assets',
                        'external_id' => $asset->external_id,
                        'source_scope' => $asset->source_scope,
                        'kind' => $asset->kind,
                        'disk' => $asset->disk,
                        'path' => $asset->file_path,
                    ],
                    field: 'file_path',
                    checkHttp: $checkHttp,
                    httpLimit: $httpLimit,
                    httpTimeout: $httpTimeout,
                );
            });

        $this->report['explanation_assets'] = $section;
    }

    /**
     * @return array{ok: bool, url: string|null}
     */
    protected function validatePath(?string $path, ?string $disk, array $context, string $field, bool $checkHttp, int $httpLimit, int $httpTimeout): array
    {
        $errorsBefore = (int) ($this->report['issue_counts']['errors'] ?? 0);

        if (blank($path)) {
            $this->issue('errors', 'blank_media_path', "Media {$field} is blank.", $context);

            return ['ok' => false, 'url' => null];
        }

        try {
            $url = $this->mediaUrlResolver->resolve($path, $disk);
        } catch (Throwable $exception) {
            $this->issue('errors', 'media_url_resolution_failed', $exception->getMessage(), $context);

            return ['ok' => false, 'url' => null];
        }

        if (blank($url)) {
            $this->issue('errors', 'media_url_empty', "Media {$field} did not resolve to a public URL.", $context);

            return ['ok' => false, 'url' => null];
        }

        if (! str_starts_with((string) $url, 'https://')) {
            $this->issue('warnings', 'media_url_not_https', 'Resolved media URL is not HTTPS.', $context + ['url' => $url]);
        }

        if (! $this->isExternalUrl((string) $path)) {
            $this->assertStorageExists((string) $path, $disk, $context + ['url' => $url]);
        }

        if ($checkHttp && $this->shouldCheckHttp((string) $url, $httpLimit)) {
            $this->assertHttpReachable((string) $url, $httpTimeout, $context);
        }

        return [
            'ok' => (int) ($this->report['issue_counts']['errors'] ?? 0) === $errorsBefore,
            'url' => (string) $url,
        ];
    }

    protected function assertStorageExists(string $path, ?string $disk, array $context): void
    {
        if (blank($disk)) {
            $this->issue('errors', 'blank_media_disk', 'Media disk is blank for a local storage path.', $context);

            return;
        }

        if (! is_array(config("filesystems.disks.{$disk}"))) {
            $this->issue('errors', 'unconfigured_media_disk', 'Media disk is not configured.', $context);

            return;
        }

        try {
            if (! Storage::disk((string) $disk)->exists($path)) {
                $this->issue('errors', 'missing_media_file', 'Media file does not exist on configured storage disk.', $context);
            }
        } catch (Throwable $exception) {
            $this->issue('errors', 'media_storage_check_failed', $exception->getMessage(), $context);
        }
    }

    protected function assertHttpReachable(string $url, int $timeout, array $context): void
    {
        $this->httpChecked++;

        try {
            $response = Http::timeout(max(1, $timeout))
                ->withHeaders(['User-Agent' => 'PrawkoNaRazMediaSitemapAudit/1.0'])
                ->head($url);

            if ($response->status() === 405) {
                $response = Http::timeout(max(1, $timeout))
                    ->withHeaders(['User-Agent' => 'PrawkoNaRazMediaSitemapAudit/1.0'])
                    ->get($url);
            }

            if ($response->status() < 200 || $response->status() >= 400) {
                $this->issue('errors', 'media_http_unreachable', 'Public media URL returned HTTP '.$response->status().'.', $context + ['url' => $url]);
            }
        } catch (Throwable $exception) {
            $this->issue('errors', 'media_http_check_failed', $exception->getMessage(), $context + ['url' => $url]);
        }
    }

    protected function shouldCheckHttp(string $url, int $httpLimit): bool
    {
        if (! $this->isExternalUrl($url)) {
            return false;
        }

        return $httpLimit <= 0 || $this->httpChecked < $httpLimit;
    }

    protected function finalizeReadiness(): void
    {
        $errorCount = (int) ($this->report['issue_counts']['errors'] ?? 0);
        $questionMedia = $this->report['question_media'];

        $this->report['checks']['http_checked'] = $this->httpChecked;

        $this->report['readiness']['image_sitemap']['candidate_urls'] = (int) ($questionMedia['image_sitemap_candidates'] ?? 0);
        $this->report['readiness']['image_sitemap']['status'] = $errorCount === 0
            ? 'ready'
            : 'needs_media_fixes_before_expansion';

        $this->report['readiness']['video_sitemap']['candidate_urls'] = (int) ($questionMedia['video_sitemap_candidates'] ?? 0);
        $this->report['readiness']['video_sitemap']['blocking_errors'] = $this->videoBlockingIssueCount;
        $this->report['readiness']['video_sitemap']['status'] = $this->videoBlockingIssueCount === 0 && (int) ($questionMedia['video_sitemap_candidates'] ?? 0) > 0
            ? 'ready_for_implementation'
            : 'blocked';
    }

    /**
     * @return Builder<Question>
     */
    protected function publicQuestionQuery(): Builder
    {
        return $this->applyPublicQuestionScope(Question::query());
    }

    /**
     * @param  Builder<Question>  $query
     * @return Builder<Question>
     */
    protected function applyPublicQuestionScope(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereHas('licenseCategory', fn (Builder $categoryQuery) => $categoryQuery->where('is_active', true));
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptySection(): array
    {
        return [
            'total' => 0,
            'by_kind_or_role' => [],
            'by_disk' => [],
            'by_mime_type' => [],
            'by_variant' => [],
            'by_status' => [],
        ];
    }

    protected function incrementCounters(array &$section, ?string $kindOrRole, ?string $disk, ?string $mimeType = null, ?string $variant = null, ?string $status = null): void
    {
        $section['total']++;
        $this->incrementKey($section['by_kind_or_role'], $kindOrRole ?: 'unknown');
        $this->incrementKey($section['by_disk'], $disk ?: 'unknown');

        if (filled($mimeType)) {
            $this->incrementKey($section['by_mime_type'], (string) $mimeType);
        }

        if (filled($variant)) {
            $this->incrementKey($section['by_variant'], (string) $variant);
        }

        if (filled($status)) {
            $this->incrementKey($section['by_status'], (string) $status);
        }
    }

    protected function incrementKey(array &$bucket, string $key): void
    {
        $bucket[$key] = (int) ($bucket[$key] ?? 0) + 1;
    }

    protected function issue(string $severity, string $type, string $message, array $context): void
    {
        if (isset($this->report['issue_counts'][$severity])) {
            $this->report['issue_counts'][$severity]++;
        }

        if (isset($this->report['issue_type_counts'][$severity])) {
            $this->incrementKey($this->report['issue_type_counts'][$severity], $type);
        }

        if ($severity === 'errors' && $this->isVideoBlockingIssue($type, $context)) {
            $this->videoBlockingIssueCount++;
        }

        if (! isset($this->report['issues'][$severity]) || count($this->report['issues'][$severity]) >= self::SAMPLE_LIMIT) {
            return;
        }

        $this->report['issues'][$severity][] = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
        ];
    }

    protected function isVideoBlockingIssue(string $type, array $context): bool
    {
        if (str_starts_with($type, 'video_') || $type === 'unsupported_video_mime') {
            return true;
        }

        return ($context['kind'] ?? null) === 'video';
    }

    protected function isExternalUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    protected function looksLikeImageUrl(string $url, ?string $mimeType): bool
    {
        if (filled($mimeType) && str_starts_with((string) $mimeType, 'image/')) {
            return true;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return preg_match('/\.(avif|gif|jpe?g|png|webp)$/i', $path) === 1;
    }
}
