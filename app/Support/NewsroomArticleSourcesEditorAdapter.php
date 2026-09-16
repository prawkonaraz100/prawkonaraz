<?php

namespace App\Support;

use App\Enums\ContentArticleSourceType;
use App\Models\ContentArticle;
use InvalidArgumentException;

final class NewsroomArticleSourcesEditorAdapter
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     article_data: array<string, mixed>,
     *     sources: list<array<string, mixed>>
     * }
     */
    public static function extractArticleData(array $data): array
    {
        $sources = self::normalizeSources($data['sources'] ?? []);
        unset($data['sources']);

        return [
            'article_data' => $data,
            'sources' => $sources,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sources
     */
    public static function sync(ContentArticle $article, array $sources): ContentArticle
    {
        $article->sources()->delete();

        foreach ($sources as $source) {
            $article->sources()->create($source);
        }

        return $article->refresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function normalizeSources(mixed $sources): array
    {
        if (! is_array($sources)) {
            throw new InvalidArgumentException('Content article sources payload must be an array.');
        }

        $normalized = [];

        foreach (array_values($sources) as $index => $source) {
            if (! is_array($source)) {
                throw new InvalidArgumentException('Each content article source must be an object-like array.');
            }

            $sourceType = $source['source_type'] ?? null;
            $sourceType = $sourceType instanceof ContentArticleSourceType
                ? $sourceType->value
                : trim((string) $sourceType);

            if (ContentArticleSourceType::tryFrom($sourceType) === null) {
                throw new InvalidArgumentException('Content article source has an unsupported source_type.');
            }

            $title = trim((string) ($source['title'] ?? ''));

            if ($title === '') {
                throw new InvalidArgumentException('Content article source title is required.');
            }

            $url = self::nullableTrimmedString($source['url'] ?? null);

            if ($url !== null && ! self::isSafeHttpUrl($url)) {
                throw new InvalidArgumentException('Content article source URL must use a valid http or https URL.');
            }

            $normalized[] = [
                'source_type' => $sourceType,
                'publisher' => self::nullableTrimmedString($source['publisher'] ?? null),
                'title' => $title,
                'url' => $url,
                'published_at' => $source['published_at'] ?? null,
                'accessed_at' => $source['accessed_at'] ?? null,
                'is_primary' => self::booleanValue($source['is_primary'] ?? false),
                'is_official' => self::booleanValue($source['is_official'] ?? false),
                'is_publicly_cited' => array_key_exists('is_publicly_cited', $source)
                    ? self::booleanValue($source['is_publicly_cited'])
                    : true,
                'note' => self::nullableTrimmedString($source['note'] ?? null),
                'sort_order' => $index + 1,
            ];
        }

        return $normalized;
    }

    private static function nullableTrimmedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new InvalidArgumentException('Content article source boolean field has an invalid value.');
        }

        return $parsed;
    }

    private static function isSafeHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(
            strtolower((string) parse_url($url, PHP_URL_SCHEME)),
            ['http', 'https'],
            true,
        );
    }
}
