<?php

namespace App\Support;

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use Closure;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class ContentArticlePublicationChecklist
{
    public const STATE_OK = 'ok';

    public const STATE_WARNING = 'warning';

    public const STATE_BLOCKING = 'blocking';

    /**
     * @return list<array{key: string, label: string, state: string, message: string}>
     */
    public function items(ContentArticle $article, ?DateTimeInterface $at = null): array
    {
        return [
            $this->blockingItem(
                'title',
                'Tytuł',
                fn (): mixed => $this->assertRequiredText($article->title, 'title'),
            ),
            $this->blockingItem(
                'type',
                'Typ artykułu',
                fn (): ContentArticleType => $this->articleType($article),
            ),
            $this->blockingItem(
                'slug',
                'Slug i ścieżka',
                function () use ($article): void {
                    $this->assertRequiredText($article->slug, 'slug');
                    NewsroomRouteContract::canonicalPath(
                        $this->articleType($article)->value,
                        (string) $article->slug,
                    );
                },
            ),
            $this->blockingItem(
                'category',
                'Kategoria',
                fn (): mixed => $this->assertPublicationCategory($article),
            ),
            $this->blockingItem(
                'author',
                'Autor',
                fn (): mixed => $this->assertPublicationAuthor($article),
            ),
            $this->blockingItem(
                'lead',
                'Lead',
                fn (): mixed => $this->assertRequiredText($article->lead, 'lead'),
            ),
            $this->blockingItem(
                'body',
                'Treść artykułu',
                fn (): mixed => $this->assertRenderableBody($article),
            ),
            $this->blockingItem(
                'origin',
                'Pochodzenie',
                fn (): mixed => $this->assertOriginType($article),
            ),
            $this->blockingItem(
                'regulatory',
                'Kontekst regulacyjny',
                fn (): mixed => $this->assertRegulatoryContext($article),
            ),
            $this->blockingItem(
                'sources',
                'Źródła',
                fn (): mixed => $this->assertSourcePolicy($article, $this->articleType($article)),
            ),
            $this->blockingItem(
                'key_points',
                'Najważniejsze punkty',
                fn (): mixed => $this->assertKeyPoints($article),
            ),
            $this->blockingItem(
                'hero',
                'Metadane hero',
                fn (): mixed => $this->assertHeroMetadata($article),
            ),
            $this->blockingItem(
                'og',
                'Metadane Open Graph',
                fn (): mixed => $this->assertOgMetadata($article),
            ),
            $this->blockingItem(
                'breaking',
                'Stan Pilne',
                fn (): mixed => $this->assertBreakingInvariant($article, $at),
            ),
            $this->blockingItem(
                'review',
                'Review',
                fn (): mixed => $this->assertFreshReview($article),
            ),
            $this->blockingItem(
                'schedule',
                'Stan terminu publikacji',
                fn (): mixed => $this->assertScheduledState($article),
            ),
            $this->warningItem(
                'hero_missing',
                'Hero',
                blank($article->hero_image_path),
                'Brak hero. Nie blokuje publikacji, ale obniża jakość prezentacji artykułu.',
            ),
            $this->warningItem(
                'seo_description_missing',
                'SEO description',
                blank($article->seo_description),
                'Brak ręcznego SEO description. Publiczny renderer może użyć bezpiecznego fallbacku.',
            ),
            $this->warningItem(
                'related_questions_missing',
                'Powiązane pytania',
                ! $article->questions()->exists(),
                'Brak powiązanych pytań. Nie blokuje publikacji.',
            ),
            $this->warningItem(
                'og_specific_image_missing',
                'Dedykowany obraz Open Graph',
                filled($article->hero_image_path) && blank($article->og_image_path),
                'Brak dedykowanego obrazu OG. Hero może być użyte jako fallback.',
            ),
            $this->warningItem(
                'legal_primary_source_missing',
                'Pierwotne źródło prawne',
                $this->missingLegalPrimarySource($article),
                'Dla materiału o przepisach nie wskazano primary official/legislation source.',
            ),
            $this->warningItem(
                'regulatory_change_summary_missing',
                'Co się zmienia',
                $this->hasRegulatoryContext($article) && blank($article->change_summary),
                'Aktywny kontekst regulacyjny nie ma jeszcze krótkiego opisu zmiany.',
            ),
            $this->warningItem(
                'regulatory_applies_to_missing',
                'Kogo dotyczy',
                $this->hasRegulatoryContext($article) && blank($article->applies_to),
                'Aktywny kontekst regulacyjny nie opisuje jeszcze, kogo dotyczy zmiana.',
            ),
            $this->warningItem(
                'regulatory_exam_impact_missing',
                'Wpływ na egzamin',
                $this->hasRegulatoryContext($article) && blank($article->exam_impact),
                'Aktywny kontekst regulacyjny nie opisuje jeszcze wpływu na egzamin.',
            ),
            $this->warningItem(
                'hero_focal_point_missing',
                'Focal point hero',
                filled($article->hero_image_path)
                    && ($article->hero_focal_x === null || $article->hero_focal_y === null),
                'Hero nie ma ustawionego focal pointu. Publiczny renderer użyje środka obrazu.',
            ),
        ];
    }

    public function assertReviewReady(ContentArticle $article): void
    {
        $this->assertRequiredText($article->title, 'title');
        $this->assertRequiredText($article->slug, 'slug');
        $this->assertRequiredText($article->lead, 'lead');

        $type = $this->articleType($article);
        NewsroomRouteContract::canonicalPath($type->value, (string) $article->slug);

        if ($article->category_id === null) {
            throw new DomainException('Content article category is required.');
        }

        if ($article->author_id === null) {
            throw new DomainException('Content article author is required.');
        }

        $this->assertRenderableBody($article);
        $this->assertOriginType($article);
        $this->assertRegulatoryContext($article);
        $this->assertSourcePolicy($article, $type);
        $this->assertKeyPoints($article);
    }

    public function assertPublicationReady(
        ContentArticle $article,
        ?DateTimeInterface $at = null,
    ): void {
        $this->assertReviewReady($article);
        $this->assertPublicationCategory($article);
        $this->assertPublicationAuthor($article);
        $this->assertHeroMetadata($article);
        $this->assertOgMetadata($article);
        $this->assertBreakingInvariant($article, $at);
    }

    public function assertFreshReview(ContentArticle $article): void
    {
        if ($article->reviewed_at === null) {
            throw new DomainException('Content article requires a completed review.');
        }

        $reference = collect([
            $article->needs_review_at,
            $article->archived_at,
            $article->withdrawn_at,
        ])
            ->filter()
            ->sortByDesc(fn ($date) => $date->getTimestamp())
            ->first();

        if ($reference !== null && ! $article->reviewed_at->gt($reference)) {
            throw new DomainException('Content article requires a fresh review after its latest public-state change.');
        }
    }

    private function assertOriginType(ContentArticle $article): void
    {
        $origin = $article->origin_type instanceof ContentArticleOriginType
            ? $article->origin_type
            : ContentArticleOriginType::tryFrom((string) $article->origin_type);

        if ($origin === null) {
            throw new DomainException('Content article has an unsupported origin_type.');
        }

        if ($origin === ContentArticleOriginType::OfficialSource && ! $this->hasPublicOfficialSource($article)) {
            throw new DomainException('official_source origin requires a publicly cited official or legislation source with a safe URL.');
        }
    }

    private function assertRegulatoryContext(ContentArticle $article): void
    {
        $status = $article->regulatory_status instanceof ContentArticleRegulatoryStatus
            ? $article->regulatory_status
            : ContentArticleRegulatoryStatus::tryFrom((string) $article->regulatory_status);

        if ($status === null) {
            throw new DomainException('Content article has an unsupported regulatory_status.');
        }

        if ($status === ContentArticleRegulatoryStatus::NotApplicable) {
            return;
        }

        if (! $this->hasPublicOfficialSource($article)) {
            throw new DomainException('Regulatory content requires a publicly cited official or legislation source with a safe URL.');
        }

        if (
            in_array($status, [
                ContentArticleRegulatoryStatus::AdoptedFuture,
                ContentArticleRegulatoryStatus::InForce,
            ], true)
            && $article->effective_from === null
        ) {
            throw new DomainException('Adopted or in-force regulatory content requires effective_from.');
        }
    }

    private function hasRegulatoryContext(ContentArticle $article): bool
    {
        $status = $article->regulatory_status instanceof ContentArticleRegulatoryStatus
            ? $article->regulatory_status
            : ContentArticleRegulatoryStatus::tryFrom((string) $article->regulatory_status);

        return $status !== null && $status !== ContentArticleRegulatoryStatus::NotApplicable;
    }

    private function hasPublicOfficialSource(ContentArticle $article): bool
    {
        return $article->sources()
            ->get()
            ->contains(function ($source): bool {
                $sourceType = ContentArticleSourceType::tryFrom((string) $source->getRawOriginal('source_type'));

                return in_array($sourceType, [
                    ContentArticleSourceType::Official,
                    ContentArticleSourceType::Legislation,
                ], true)
                    && $source->is_publicly_cited
                    && $this->isSafeHttpUrl($source->url);
            });
    }

    private function assertPublicationCategory(ContentArticle $article): void
    {
        if ($article->category_id === null) {
            throw new DomainException('Content article category is required.');
        }

        $category = $article->category()->first();

        if ($category === null || ! $category->isPublicationEligible()) {
            throw new DomainException('Content article requires an active category.');
        }
    }

    private function assertPublicationAuthor(ContentArticle $article): void
    {
        if ($article->author_id === null) {
            throw new DomainException('Content article author is required.');
        }

        $author = $article->author()->first();

        if ($author === null || ! $author->isPubliclyVisible()) {
            throw new DomainException('Content article requires a published author.');
        }
    }

    private function assertRenderableBody(ContentArticle $article): void
    {
        $body = is_array($article->body_blocks) ? $article->body_blocks : [];
        $version = (int) ($article->body_schema_version ?? 0);
        $normalized = NewsroomBodyContract::normalize($body, $version);

        if ($normalized === []) {
            throw new DomainException('Content article requires at least one renderable body block.');
        }
    }

    private function assertSourcePolicy(ContentArticle $article, ContentArticleType $articleType): void
    {
        $sources = $article->sources()->get();

        if ($articleType === ContentArticleType::News && $sources->isEmpty()) {
            throw new DomainException('News article requires at least one source.');
        }

        foreach ($sources as $source) {
            $this->assertRequiredText($source->title, 'source title');

            $sourceType = ContentArticleSourceType::tryFrom((string) $source->getRawOriginal('source_type'));

            if ($sourceType === null) {
                throw new DomainException('Content article source has an unsupported source_type.');
            }

            if (filled($source->url) && ! $this->isSafeHttpUrl($source->url)) {
                throw new DomainException('Content article source URL must use a valid http or https URL.');
            }
        }

        if ($articleType !== ContentArticleType::News) {
            return;
        }

        $category = $article->category()->first();

        if ($category?->slug !== 'przepisy') {
            return;
        }

        $legalPrimarySources = $sources->filter(function ($source): bool {
            if (! $source->is_primary) {
                return false;
            }

            $sourceType = ContentArticleSourceType::tryFrom((string) $source->getRawOriginal('source_type'));

            return in_array($sourceType, [
                ContentArticleSourceType::Official,
                ContentArticleSourceType::Legislation,
            ], true);
        });

        if ($legalPrimarySources->isEmpty()) {
            return;
        }

        $hasPublicPrimaryUrl = $legalPrimarySources->contains(
            fn ($source): bool => $source->is_publicly_cited && $this->isSafeHttpUrl($source->url),
        );

        if (! $hasPublicPrimaryUrl) {
            throw new DomainException(
                'Legal news with a primary official or legislation source requires a publicly cited http or https URL.',
            );
        }
    }

    private function assertKeyPoints(ContentArticle $article): void
    {
        if ($article->key_points === null) {
            return;
        }

        if (! is_array($article->key_points) || count($article->key_points) < 2 || count($article->key_points) > 5) {
            throw new DomainException('Content article key points must contain between 2 and 5 items.');
        }

        foreach ($article->key_points as $point) {
            if (
                ! is_string($point)
                || trim($point) === ''
                || strip_tags($point) !== $point
            ) {
                throw new DomainException('Content article key points must be non-empty plain text.');
            }
        }
    }

    private function assertHeroMetadata(ContentArticle $article): void
    {
        if (blank($article->hero_image_path)) {
            return;
        }

        $this->assertRequiredText($article->hero_image_alt, 'hero_image_alt');

        if ((int) $article->hero_image_width < 1 || (int) $article->hero_image_height < 1) {
            throw new DomainException('Hero image requires positive width and height.');
        }

        $verified = app(NewsroomMediaStorage::class)->inspectStoredImage((string) $article->hero_image_path);
        app(NewsroomMediaStorage::class)->publicUrl((string) $article->hero_image_path);

        if (
            $verified['width'] !== (int) $article->hero_image_width
            || $verified['height'] !== (int) $article->hero_image_height
        ) {
            throw new DomainException('Hero image dimensions do not match the stored newsroom asset.');
        }

        foreach ([$article->hero_focal_x, $article->hero_focal_y] as $coordinate) {
            if ($coordinate !== null && ((float) $coordinate < 0.0 || (float) $coordinate > 1.0)) {
                throw new DomainException('Hero focal point coordinates must be between 0 and 1.');
            }
        }

        if (($article->hero_focal_x === null) xor ($article->hero_focal_y === null)) {
            throw new DomainException('Hero focal point requires both X and Y coordinates.');
        }
    }

    private function assertOgMetadata(ContentArticle $article): void
    {
        if (blank($article->og_image_path)) {
            return;
        }

        $canInheritHeroAlt = filled($article->hero_image_alt)
            && $article->og_image_path === $article->hero_image_path;

        if (! filled($article->og_image_alt) && ! $canInheritHeroAlt) {
            throw new DomainException('OG image requires its own alt unless it reuses the hero asset.');
        }

        if ((int) $article->og_image_width < 1 || (int) $article->og_image_height < 1) {
            throw new DomainException('OG image requires positive width and height.');
        }

        $verified = app(NewsroomMediaStorage::class)->inspectStoredImage((string) $article->og_image_path);
        app(NewsroomMediaStorage::class)->publicUrl((string) $article->og_image_path);

        if (
            $verified['width'] !== (int) $article->og_image_width
            || $verified['height'] !== (int) $article->og_image_height
        ) {
            throw new DomainException('OG image dimensions do not match the stored newsroom asset.');
        }
    }

    private function assertBreakingInvariant(
        ContentArticle $article,
        ?DateTimeInterface $at = null,
    ): void {
        if (! $article->is_breaking) {
            return;
        }

        if ($this->articleType($article) !== ContentArticleType::News) {
            throw new DomainException('Breaking article must be a news article.');
        }

        $referenceAt = $at === null
            ? now()
            : Carbon::parse($at->format(DATE_ATOM));

        if (
            $article->breaking_expires_at === null
            || $article->breaking_expires_at->lte($referenceAt)
        ) {
            throw new DomainException('Breaking article requires a future expiration timestamp after the evaluated publication time.');
        }
    }

    private function assertScheduledState(ContentArticle $article): void
    {
        $status = $article->workflow_status instanceof ContentArticleWorkflowStatus
            ? $article->workflow_status
            : ContentArticleWorkflowStatus::tryFrom((string) $article->workflow_status);

        if ($status !== ContentArticleWorkflowStatus::Scheduled) {
            return;
        }

        if ($article->first_published_at !== null) {
            throw new DomainException('Newsroom v1 does not support scheduled republish.');
        }

        if ($article->scheduled_for === null) {
            throw new DomainException('Scheduled article requires a publication timestamp.');
        }
    }

    private function missingLegalPrimarySource(ContentArticle $article): bool
    {
        $type = $article->type instanceof ContentArticleType
            ? $article->type
            : ContentArticleType::tryFrom((string) $article->type);

        if ($type !== ContentArticleType::News || $article->category?->slug !== 'przepisy') {
            return false;
        }

        return ! $article->sources()
            ->get()
            ->contains(function ($source): bool {
                if (! $source->is_primary) {
                    return false;
                }

                $sourceType = ContentArticleSourceType::tryFrom((string) $source->getRawOriginal('source_type'));

                return in_array($sourceType, [
                    ContentArticleSourceType::Official,
                    ContentArticleSourceType::Legislation,
                ], true);
            });
    }

    private function isSafeHttpUrl(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $url = trim($value);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(
            strtolower((string) parse_url($url, PHP_URL_SCHEME)),
            ['http', 'https'],
            true,
        );
    }

    private function articleType(ContentArticle $article): ContentArticleType
    {
        if ($article->type instanceof ContentArticleType) {
            return $article->type;
        }

        $type = ContentArticleType::tryFrom((string) $article->type);

        if ($type === null) {
            throw new DomainException('Content article has an unsupported type.');
        }

        return $type;
    }

    private function assertRequiredText(mixed $value, string $field): void
    {
        if (! is_string($value) || trim($value) === '') {
            throw new DomainException("Content article {$field} is required.");
        }
    }

    /**
     * @param  Closure(): mixed  $check
     * @return array{key: string, label: string, state: string, message: string}
     */
    private function blockingItem(string $key, string $label, Closure $check): array
    {
        try {
            $check();

            return [
                'key' => $key,
                'label' => $label,
                'state' => self::STATE_OK,
                'message' => 'Gotowe.',
            ];
        } catch (DomainException|InvalidArgumentException $exception) {
            return [
                'key' => $key,
                'label' => $label,
                'state' => self::STATE_BLOCKING,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{key: string, label: string, state: string, message: string}
     */
    private function warningItem(
        string $key,
        string $label,
        bool $hasWarning,
        string $message,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'state' => $hasWarning ? self::STATE_WARNING : self::STATE_OK,
            'message' => $hasWarning ? $message : 'Gotowe.',
        ];
    }
}
