<?php

namespace App\Support;

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use Illuminate\Support\Str;

final class NewsroomArticlePresentationService
{
    public function __construct(
        private readonly NewsroomArticleBodyRenderer $bodyRenderer,
        private readonly NewsroomSemanticLinkService $semanticLinks,
        private readonly MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function present(ContentArticle $article, string $family): array
    {
        $article->loadMissing(['category', 'author', 'sources']);
        $type = $article->type instanceof ContentArticleType
            ? $article->type
            : ContentArticleType::tryFrom((string) $article->type);
        $workflow = $article->workflow_status instanceof ContentArticleWorkflowStatus
            ? $article->workflow_status
            : ContentArticleWorkflowStatus::tryFrom((string) $article->workflow_status);
        $disk = (string) config('media.newsroom_disk', config('media.public_disk', 'public'));

        return [
            'article' => $article,
            'breadcrumbs' => $this->breadcrumbs($article, $family),
            'bodyBlocks' => $this->bodyRenderer->render($article),
            'heroImageUrl' => $this->mediaUrlResolver->resolve($article->hero_image_path, $disk),
            'heroObjectPosition' => $this->objectPosition($article->hero_focal_x, $article->hero_focal_y),
            'publicSources' => $this->publicSources($article),
            'provenanceLabel' => $this->originLabel($article->origin_type),
            'regulatoryContext' => $this->regulatoryContext($article),
            'publishedLabel' => $this->publishedLabel($article, $type),
            'modifiedLabel' => $this->modifiedLabel($article),
            'isNeedsReview' => $workflow === ContentArticleWorkflowStatus::NeedsReview,
            'isArchived' => $workflow === ContentArticleWorkflowStatus::Archived,
            'authorBox' => $this->authorBox($article),
            'topicLinks' => $this->semanticLinks->topics($article),
            'relatedArticles' => $this->semanticLinks->relatedArticles($article),
            'internalLinkAudit' => $this->semanticLinks->audit($article),
        ];
    }

    /** @return list<array{label:string,url?:string}> */
    private function breadcrumbs(ContentArticle $article, string $family): array
    {
        $items = [
            ['label' => 'Strona główna', 'url' => route('home')],
        ];

        if ($family === NewsroomRouteContract::FAMILY_GUIDES) {
            $items[] = ['label' => 'Poradniki', 'url' => route('public.guides')];
        } else {
            $items[] = ['label' => 'Aktualności', 'url' => route('public.news')];

            if ($article->category) {
                $items[] = [
                    'label' => (string) $article->category->name,
                    'url' => route('public.news.categories.show', $article->category->slug),
                ];
            }
        }

        $items[] = ['label' => (string) $article->title];

        return $items;
    }

    /** @return list<array<string,mixed>> */
    private function publicSources(ContentArticle $article): array
    {
        return $article->sources
            ->filter(fn (ContentArticleSource $source): bool => (bool) $source->is_publicly_cited)
            ->sortBy('sort_order')
            ->values()
            ->map(fn (ContentArticleSource $source): array => [
                'title' => (string) $source->title,
                'publisher' => filled($source->publisher) ? (string) $source->publisher : null,
                'url' => $this->safeExternalUrl($source->url),
                'published_at' => $source->published_at?->format('d.m.Y'),
            ])
            ->all();
    }

    private function originLabel(mixed $origin): ?string
    {
        $origin = $origin instanceof ContentArticleOriginType
            ? $origin
            : ContentArticleOriginType::tryFrom((string) $origin);

        return match ($origin) {
            ContentArticleOriginType::Original => 'Materiał własny',
            ContentArticleOriginType::Compiled => 'Opracowanie na podstawie źródeł',
            ContentArticleOriginType::OfficialSource => 'Opracowanie na podstawie oficjalnych źródeł',
            ContentArticleOriginType::DataAnalysis => 'Analiza własna',
            ContentArticleOriginType::LicensedAgency => 'Na podstawie materiału agencyjnego',
            default => null,
        };
    }

    /** @return array{status:?string,change_summary:?string,effective_from:?string,applies_to:?string,exam_impact:?string}|null */
    private function regulatoryContext(ContentArticle $article): ?array
    {
        $status = $article->regulatory_status instanceof ContentArticleRegulatoryStatus
            ? $article->regulatory_status
            : ContentArticleRegulatoryStatus::tryFrom((string) $article->regulatory_status);
        $statusLabel = match ($status) {
            ContentArticleRegulatoryStatus::Proposal => 'Projekt',
            ContentArticleRegulatoryStatus::Consultation => 'Konsultacje',
            ContentArticleRegulatoryStatus::OfficialAnnouncement => 'Oficjalnie ogłoszone',
            ContentArticleRegulatoryStatus::AdoptedFuture => 'Przyjęte — wejdzie w życie',
            ContentArticleRegulatoryStatus::InForce => 'Obowiązuje',
            default => null,
        };

        $context = [
            'status' => $statusLabel,
            'change_summary' => $this->cleanText($article->change_summary),
            'effective_from' => $article->effective_from?->format('d.m.Y'),
            'applies_to' => $this->cleanText($article->applies_to),
            'exam_impact' => $this->cleanText($article->exam_impact),
        ];

        return collect($context)->filter(fn (mixed $value): bool => filled($value))->isEmpty()
            ? null
            : $context;
    }

    private function publishedLabel(ContentArticle $article, ?ContentArticleType $type): ?string
    {
        $publishedAt = $article->first_published_at;

        if ($publishedAt === null) {
            return null;
        }

        return $type === ContentArticleType::News
            ? $publishedAt->format('d.m.Y, H:i')
            : $publishedAt->format('d.m.Y');
    }

    private function modifiedLabel(ContentArticle $article): ?string
    {
        $modifiedAt = $article->last_substantive_update_at;

        if ($modifiedAt === null || $modifiedAt->equalTo($article->first_published_at)) {
            return null;
        }

        return $modifiedAt->format('d.m.Y, H:i');
    }

    /** @return array<string,mixed>|null */
    private function authorBox(ContentArticle $article): ?array
    {
        $author = $article->author;

        if ($author === null) {
            return null;
        }

        return [
            'name' => (string) $author->name,
            'job_title' => $this->cleanText($author->job_title),
            'bio' => $this->cleanText($author->bio),
            'url' => route('content-authors.show', $author->slug),
            'photo_url' => $this->mediaUrlResolver->resolve($author->photo_path),
        ];
    }

    private function safeExternalUrl(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($url === '' || ! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    private function cleanText(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = Str::squish(strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $value !== '' ? $value : null;
    }

    private function objectPosition(mixed $x, mixed $y): ?string
    {
        if (! is_numeric($x) || ! is_numeric($y)) {
            return null;
        }

        $x = min(max((float) $x, 0.0), 1.0) * 100;
        $y = min(max((float) $y, 0.0), 1.0) * 100;

        return number_format($x, 2, '.', '').'% '.number_format($y, 2, '.', '').'%';
    }
}
