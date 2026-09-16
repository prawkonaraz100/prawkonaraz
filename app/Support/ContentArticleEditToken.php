<?php

namespace App\Support;

use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use Illuminate\Support\Facades\DB;
use JsonException;

final class ContentArticleEditToken
{
    /**
     * Token for optimistic locking of the complete admin-editable article state.
     *
     * @throws JsonException
     */
    public function make(ContentArticle $article): string
    {
        return $this->hash([
            'article' => $this->stableMap($article->getRawOriginal()),
            'sources' => ContentArticleSource::query()
                ->where('article_id', $article->getKey())
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (ContentArticleSource $source): array => $this->stableMap($source->getRawOriginal()))
                ->values()
                ->all(),
            'questions' => $this->pivotRows(
                'content_article_question',
                (int) $article->getKey(),
                ['question_id', 'relation_type', 'sort_order', 'note', 'created_at', 'updated_at'],
                'question_id',
            ),
            'legal_units' => $this->pivotRows(
                'content_article_legal_unit',
                (int) $article->getKey(),
                ['legal_unit_id', 'relation_type', 'sort_order', 'note', 'created_at', 'updated_at'],
                'legal_unit_id',
            ),
            'traffic_signs' => $this->pivotRows(
                'content_article_traffic_sign',
                (int) $article->getKey(),
                ['traffic_sign_id', 'relation_type', 'sort_order', 'created_at', 'updated_at'],
                'traffic_sign_id',
            ),
            'topics' => $this->membershipIds('content_article_topic', 'topic_id', (int) $article->getKey()),
            'tags' => $this->membershipIds('content_article_tag', 'tag_id', (int) $article->getKey()),
        ]);
    }

    /**
     * Fingerprint only semantically public fields. Internal notes and technical
     * timestamps are intentionally excluded from SEO freshness semantics.
     *
     * @throws JsonException
     */
    public function publicFingerprint(ContentArticle $article): string
    {
        $raw = $article->getRawOriginal();

        return $this->hash([
            'article' => $this->stableMap(array_intersect_key($raw, array_flip([
                'type',
                'category_id',
                'author_id',
                'reviewer_id',
                'title',
                'slug',
                'lead',
                'body_blocks',
                'body_schema_version',
                'key_points',
                'correction_note',
                'origin_type',
                'regulatory_status',
                'effective_from',
                'change_summary',
                'applies_to',
                'exam_impact',
                'hero_image_path',
                'hero_image_alt',
                'hero_image_width',
                'hero_image_height',
                'hero_image_caption',
                'hero_focal_x',
                'hero_focal_y',
                'og_image_path',
                'og_image_alt',
                'og_image_width',
                'og_image_height',
                'image_credit',
                'seo_title',
                'seo_description',
                'robots',
            ]))),
            'sources' => ContentArticleSource::query()
                ->where('article_id', $article->getKey())
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (ContentArticleSource $source): array => $this->stableMap(array_intersect_key(
                    $source->getRawOriginal(),
                    array_flip([
                        'source_type',
                        'publisher',
                        'title',
                        'url',
                        'published_at',
                        'accessed_at',
                        'is_primary',
                        'is_official',
                        'is_publicly_cited',
                        'sort_order',
                    ]),
                )))
                ->values()
                ->all(),
            'questions' => $this->pivotRows(
                'content_article_question',
                (int) $article->getKey(),
                ['question_id', 'relation_type', 'sort_order'],
                'question_id',
            ),
            'legal_units' => $this->pivotRows(
                'content_article_legal_unit',
                (int) $article->getKey(),
                ['legal_unit_id', 'relation_type', 'sort_order'],
                'legal_unit_id',
            ),
            'traffic_signs' => $this->pivotRows(
                'content_article_traffic_sign',
                (int) $article->getKey(),
                ['traffic_sign_id', 'relation_type', 'sort_order'],
                'traffic_sign_id',
            ),
            'topics' => $this->membershipIds('content_article_topic', 'topic_id', (int) $article->getKey()),
        ]);
    }

    /**
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    private function pivotRows(
        string $table,
        int $articleId,
        array $columns,
        string $targetColumn,
    ): array {
        return DB::table($table)
            ->where('article_id', $articleId)
            ->orderBy('sort_order')
            ->orderBy($targetColumn)
            ->get($columns)
            ->map(fn (object $row): array => $this->stableMap((array) $row))
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function membershipIds(string $table, string $targetColumn, int $articleId): array
    {
        return DB::table($table)
            ->where('article_id', $articleId)
            ->orderBy($targetColumn)
            ->pluck($targetColumn)
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function stableMap(array $values): array
    {
        ksort($values);

        return $values;
    }

    /**
     * @param  array<string, mixed>  $state
     *
     * @throws JsonException
     */
    private function hash(array $state): string
    {
        return hash('sha256', json_encode(
            $state,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }
}
