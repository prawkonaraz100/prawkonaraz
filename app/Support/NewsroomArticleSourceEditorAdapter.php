<?php

namespace App\Support;

use App\Enums\ContentArticleSourceType;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class NewsroomArticleSourceEditorAdapter
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function hydrate(ContentArticle $article): array
    {
        return $article->sources()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ContentArticleSource $source): array => [
                'id' => (int) $source->getKey(),
                'source_type' => $source->source_type?->value ?? (string) $source->source_type,
                'publisher' => $source->publisher,
                'title' => $source->title,
                'url' => $source->url,
                'published_at' => $source->published_at,
                'accessed_at' => $source->accessed_at,
                'is_primary' => (bool) $source->is_primary,
                'is_official' => (bool) $source->is_official,
                'is_publicly_cited' => (bool) $source->is_publicly_cited,
                'note' => $source->note,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function normalize(ContentArticle $article, mixed $rows): array
    {
        if ($rows === null) {
            return [];
        }

        if (! is_array($rows)) {
            throw new InvalidArgumentException('Źródła muszą być listą rekordów.');
        }

        $normalized = [];
        $seenIds = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Każde źródło musi być rekordem formularza.');
            }

            $id = self::positiveInteger($row['id'] ?? null);

            if ($id !== null) {
                if (isset($seenIds[$id])) {
                    throw new InvalidArgumentException('To samo źródło nie może wystąpić dwa razy.');
                }

                $belongsToArticle = ContentArticleSource::query()
                    ->whereKey($id)
                    ->where('article_id', $article->getKey())
                    ->exists();

                if (! $belongsToArticle) {
                    throw new InvalidArgumentException('Co najmniej jedno źródło nie należy do edytowanego artykułu.');
                }

                $seenIds[$id] = true;
            }

            $sourceType = trim((string) ($row['source_type'] ?? ''));

            if (ContentArticleSourceType::tryFrom($sourceType) === null) {
                throw new InvalidArgumentException('Źródło ma nieobsługiwany typ.');
            }

            $title = trim((string) ($row['title'] ?? ''));

            if ($title === '') {
                throw new InvalidArgumentException('Każde źródło wymaga tytułu.');
            }

            $url = self::nullableString($row['url'] ?? null);

            if ($url !== null && ! self::isSafeHttpUrl($url)) {
                throw new InvalidArgumentException('URL źródła musi używać poprawnego http lub https.');
            }

            $normalized[] = [
                'id' => $id,
                'source_type' => $sourceType,
                'publisher' => self::nullableString($row['publisher'] ?? null),
                'title' => $title,
                'url' => $url,
                'published_at' => self::nullableDateTime($row['published_at'] ?? null),
                'accessed_at' => self::nullableDateTime($row['accessed_at'] ?? null),
                'is_primary' => (bool) ($row['is_primary'] ?? false),
                'is_official' => (bool) ($row['is_official'] ?? false),
                'is_publicly_cited' => (bool) ($row['is_publicly_cited'] ?? false),
                'note' => self::nullableString($row['note'] ?? null),
                'sort_order' => $index,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function sync(ContentArticle $article, array $rows): ContentArticle
    {
        $keepIds = [];

        foreach ($rows as $row) {
            $id = $row['id'] ?? null;
            unset($row['id']);

            if (is_int($id)) {
                $source = ContentArticleSource::query()
                    ->whereKey($id)
                    ->where('article_id', $article->getKey())
                    ->firstOrFail();

                $source->fill($row);
                $source->save();
                $keepIds[] = $id;

                continue;
            }

            $source = $article->sources()->create($row);
            $keepIds[] = (int) $source->getKey();
        }

        $obsolete = $article->sources()
            ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
            ->get();

        foreach ($obsolete as $source) {
            $source->delete();
        }

        $article->touch();

        return $article->refresh();
    }

    private static function positiveInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $validated = filter_var($value, FILTER_VALIDATE_INT);

        return $validated !== false && $validated > 0 ? (int) $validated : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private static function nullableDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
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
