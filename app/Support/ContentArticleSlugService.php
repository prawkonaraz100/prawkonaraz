<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Events\ContentArticleIndexNowRequested;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use App\Models\IndexNowUrlSubmission;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ContentArticleSlugService
{
    private const PATH_LOCK_PREFIX = 'newsroom:article:path:';

    private const SLUG_LOCK_PREFIX = 'newsroom:article:slug:';

    public function __construct(
        private readonly PostgresTransactionAdvisoryLock $advisoryLock,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $actor = null): ContentArticle
    {
        return DB::transaction(function () use ($attributes, $actor): ContentArticle {
            $type = $this->normalizeType($attributes['type'] ?? ContentArticleType::News);
            $title = trim((string) ($attributes['title'] ?? ''));

            if ($title === '') {
                throw new InvalidArgumentException('Content article title is required.');
            }

            $explicitSlug = array_key_exists('slug', $attributes)
                && trim((string) $attributes['slug']) !== '';

            $slug = $explicitSlug
                ? $this->normalizeSlug((string) $attributes['slug'])
                : $this->allocateGeneratedSlug($type, $title);

            $canonicalPath = NewsroomRouteContract::canonicalPath($type, $slug);

            if ($explicitSlug) {
                $this->acquireMutationLocks([$slug], [$canonicalPath]);
                $this->assertCurrentSlugAvailable($slug);
                $this->assertHistoricalPathAvailable($canonicalPath);
            }

            $attributes['type'] = $type;
            $attributes['title'] = $title;
            $attributes['slug'] = $slug;

            $article = ContentArticle::query()->create($attributes);

            $this->auditLogService->record(
                action: 'content_article.created',
                entityType: ContentArticle::class,
                entityId: $article->getKey(),
                actor: $actor,
                metadata: [
                    'type' => $type,
                    'slug' => $slug,
                    'canonical_path' => $canonicalPath,
                ],
            );

            return $article->refresh();
        });
    }

    public function changeSlug(
        ContentArticle $article,
        string $requestedSlug,
        ?User $actor = null,
    ): ContentArticle {
        return DB::transaction(function () use ($article, $requestedSlug, $actor): ContentArticle {
            $locked = ContentArticle::query()
                ->whereKey($article->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $type = $this->articleTypeValue($locked);
            $newSlug = $this->normalizeSlug($requestedSlug);
            $oldSlug = (string) $locked->slug;

            if ($newSlug === $oldSlug) {
                return $locked;
            }

            $oldPath = NewsroomRouteContract::canonicalPath($type, $oldSlug);
            $newPath = NewsroomRouteContract::canonicalPath($type, $newSlug);

            $this->acquireMutationLocks(
                [$oldSlug, $newSlug],
                [$oldPath, $newPath],
            );

            $this->assertCurrentSlugAvailable($newSlug, (int) $locked->getKey());

            $newPathRedirect = $this->redirectAtPath($newPath);

            if ($newPathRedirect !== null) {
                if ((int) $newPathRedirect->article_id !== (int) $locked->getKey()) {
                    throw new DomainException("Historical article path [{$newPath}] is reserved by another article.");
                }

                $newPathRedirect->delete();
            }

            $oldPathRedirect = $this->redirectAtPath($oldPath);

            if (
                $oldPathRedirect !== null
                && (int) $oldPathRedirect->article_id !== (int) $locked->getKey()
            ) {
                throw new DomainException("Current article path [{$oldPath}] collides with another article history.");
            }

            if ($locked->first_published_at !== null) {
                ContentArticleRedirect::query()
                    ->where('article_id', $locked->getKey())
                    ->update([
                        'to_path' => $newPath,
                        'updated_at' => now(),
                    ]);

                ContentArticleRedirect::query()->updateOrCreate(
                    ['from_path' => $oldPath],
                    [
                        'article_id' => $locked->getKey(),
                        'to_path' => $newPath,
                        'http_status' => 301,
                    ],
                );
            }

            $locked->slug = $newSlug;
            $locked->save();

            $this->auditLogService->record(
                action: 'content_article.slug_changed',
                entityType: ContentArticle::class,
                entityId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'old_slug' => $oldSlug,
                    'new_slug' => $newSlug,
                    'from_path' => $oldPath,
                    'to_path' => $newPath,
                    'redirect_created' => $locked->first_published_at !== null,
                ],
            );

            if ($locked->first_published_at !== null) {
                ContentArticleIndexNowRequested::dispatch(
                    (int) $locked->getKey(),
                    $oldPath,
                    IndexNowUrlSubmission::EVENT_UPDATED,
                    'content_article.slug_changed',
                );
                ContentArticleIndexNowRequested::dispatch(
                    (int) $locked->getKey(),
                    $newPath,
                    IndexNowUrlSubmission::EVENT_UPDATED,
                    'content_article.slug_changed',
                );
            }

            return $locked->refresh();
        });
    }

    public function changeType(
        ContentArticle $article,
        ContentArticleType|string $nextType,
        ?User $actor = null,
    ): ContentArticle {
        return DB::transaction(function () use ($article, $nextType, $actor): ContentArticle {
            $locked = ContentArticle::query()
                ->whereKey($article->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $currentType = $this->articleTypeValue($locked);
            $nextTypeValue = $this->normalizeType($nextType);

            if ($currentType === $nextTypeValue) {
                return $locked;
            }

            NewsroomRouteContract::assertTypeTransitionAllowed(
                $currentType,
                $nextTypeValue,
                $locked->first_published_at,
            );

            $slug = (string) $locked->slug;
            $oldPath = NewsroomRouteContract::canonicalPath($currentType, $slug);
            $newPath = NewsroomRouteContract::canonicalPath($nextTypeValue, $slug);

            $this->acquireMutationLocks([$slug], [$oldPath, $newPath]);

            if ($newPath !== $oldPath) {
                $redirect = $this->redirectAtPath($newPath);

                if ($redirect !== null) {
                    throw new DomainException("Article path [{$newPath}] is reserved by redirect history.");
                }
            }

            $locked->type = $nextTypeValue;
            $locked->save();

            $this->auditLogService->record(
                action: 'content_article.type_changed',
                entityType: ContentArticle::class,
                entityId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'old_type' => $currentType,
                    'new_type' => $nextTypeValue,
                    'old_path' => $oldPath,
                    'new_path' => $newPath,
                ],
            );

            return $locked->refresh();
        });
    }

    private function allocateGeneratedSlug(string $type, string $title): string
    {
        $base = $this->normalizeSlug($title);

        for ($attempt = 1; $attempt <= 1000; $attempt++) {
            $candidate = $attempt === 1
                ? $base
                : $this->appendSuffix($base, $attempt);

            try {
                $path = NewsroomRouteContract::canonicalPath($type, $candidate);
            } catch (InvalidArgumentException) {
                continue;
            }

            $this->acquireMutationLocks([$candidate], [$path]);

            if ($this->currentSlugExists($candidate)) {
                continue;
            }

            if ($this->redirectAtPath($path) !== null) {
                continue;
            }

            return $candidate;
        }

        throw new DomainException('Unable to allocate a unique content article slug.');
    }

    private function normalizeSlug(string $value): string
    {
        $slug = Str::slug($value);
        $slug = trim(Str::limit($slug, 255, ''), '-');

        if ($slug === '') {
            throw new InvalidArgumentException('Content article slug cannot be empty.');
        }

        if (preg_match('/\A'.NewsroomRouteContract::SLUG_PATTERN.'\z/', $slug) !== 1) {
            throw new InvalidArgumentException("Invalid content article slug [{$slug}].");
        }

        return $slug;
    }

    private function appendSuffix(string $base, int $suffix): string
    {
        $tail = '-'.$suffix;
        $maxBaseLength = 255 - strlen($tail);
        $base = rtrim(Str::limit($base, $maxBaseLength, ''), '-');

        return $base.$tail;
    }

    private function normalizeType(ContentArticleType|string $type): string
    {
        $value = $type instanceof ContentArticleType ? $type->value : trim($type);

        if (ContentArticleType::tryFrom($value) === null) {
            throw new InvalidArgumentException("Unsupported content article type [{$value}].");
        }

        return $value;
    }

    private function articleTypeValue(ContentArticle $article): string
    {
        return $article->type instanceof ContentArticleType
            ? $article->type->value
            : (string) $article->type;
    }

    private function assertCurrentSlugAvailable(string $slug, ?int $ignoreArticleId = null): void
    {
        $query = ContentArticle::query()->where('slug', $slug);

        if ($ignoreArticleId !== null) {
            $query->where('id', '!=', $ignoreArticleId);
        }

        if ($query->exists()) {
            throw new DomainException("Content article slug [{$slug}] is already in use.");
        }
    }

    private function currentSlugExists(string $slug): bool
    {
        return ContentArticle::query()
            ->where('slug', $slug)
            ->exists();
    }

    private function assertHistoricalPathAvailable(string $path): void
    {
        if ($this->redirectAtPath($path) !== null) {
            throw new DomainException("Historical article path [{$path}] is reserved.");
        }
    }

    private function redirectAtPath(string $path): ?ContentArticleRedirect
    {
        return ContentArticleRedirect::query()
            ->where('from_path', $path)
            ->lockForUpdate()
            ->first();
    }

    /**
     * @param  list<string>  $slugs
     * @param  list<string>  $paths
     */
    private function acquireMutationLocks(array $slugs, array $paths): void
    {
        $keys = [];

        foreach ($slugs as $slug) {
            $keys[] = self::SLUG_LOCK_PREFIX.$slug;
        }

        foreach ($paths as $path) {
            $keys[] = self::PATH_LOCK_PREFIX.$path;
        }

        $this->advisoryLock->acquire($keys);
    }
}
