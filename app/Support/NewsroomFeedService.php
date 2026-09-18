<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Support\Carbon;

final class NewsroomFeedService
{
    private const EMPTY_FEED_UPDATED = '1970-01-01T00:00:00+00:00';

    public function __construct(
        private readonly ContentArticlePublicCatalogService $catalog,
        private readonly NewsroomPublicReadCache $cache,
        private readonly NewsroomPublicGate $publicGate,
        private readonly SiteIdentitySchema $siteIdentity,
    ) {}

    /**
     * @return array{
     *     title:string,
     *     id:string,
     *     link:string,
     *     self:string,
     *     updated:string,
     *     author:string,
     *     items:list<array{
     *         id:string,
     *         title:string,
     *         link:string,
     *         published:string,
     *         updated:string,
     *         summary:string,
     *         author:string|null
     *     }>
     * }|null
     */
    public function build(): ?array
    {
        if ($this->publicGate->disabled()) {
            return null;
        }

        return $this->cache->rememberFeed(fn (): array => $this->buildUncached());
    }

    /**
     * @param  array{
     *     title:string,
     *     id:string,
     *     link:string,
     *     self:string,
     *     updated:string,
     *     author:string,
     *     items:list<array{
     *         id:string,
     *         title:string,
     *         link:string,
     *         published:string,
     *         updated:string,
     *         summary:string,
     *         author:string|null
     *     }>
     * }  $feed
     */
    public function render(array $feed): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="pl">';
        $xml[] = '    <title>'.$this->escape($feed['title']).'</title>';
        $xml[] = '    <id>'.$this->escape($feed['id']).'</id>';
        $xml[] = '    <link rel="alternate" type="text/html" href="'.$this->escape($feed['link']).'"/>';
        $xml[] = '    <link rel="self" type="application/atom+xml" href="'.$this->escape($feed['self']).'"/>';
        $xml[] = '    <updated>'.$this->escape($feed['updated']).'</updated>';
        $xml[] = '    <author>';
        $xml[] = '        <name>'.$this->escape($feed['author']).'</name>';
        $xml[] = '    </author>';

        foreach ($feed['items'] as $item) {
            $xml[] = '    <entry>';
            $xml[] = '        <title>'.$this->escape($item['title']).'</title>';
            $xml[] = '        <id>'.$this->escape($item['id']).'</id>';
            $xml[] = '        <link rel="alternate" type="text/html" href="'.$this->escape($item['link']).'"/>';
            $xml[] = '        <published>'.$this->escape($item['published']).'</published>';
            $xml[] = '        <updated>'.$this->escape($item['updated']).'</updated>';
            $xml[] = '        <summary type="text">'.$this->escape($item['summary']).'</summary>';

            if ($item['author'] !== null) {
                $xml[] = '        <author>';
                $xml[] = '            <name>'.$this->escape($item['author']).'</name>';
                $xml[] = '        </author>';
            }

            $xml[] = '    </entry>';
        }

        $xml[] = '</feed>';

        return implode("\n", $xml)."\n";
    }

    /**
     * @return array{
     *     title:string,
     *     id:string,
     *     link:string,
     *     self:string,
     *     updated:string,
     *     author:string,
     *     items:list<array{
     *         id:string,
     *         title:string,
     *         link:string,
     *         published:string,
     *         updated:string,
     *         summary:string,
     *         author:string|null
     *     }>
     * }
     */
    private function buildUncached(): array
    {
        $limit = max(1, (int) config('newsroom.feed_items_limit', 50));

        $articles = $this->catalog
            ->activelyDistributedQuery(NewsroomRouteContract::FAMILY_NEWSROOM)
            ->where('type', ContentArticleType::News->value)
            ->whereNotNull('first_published_at')
            ->limit($limit)
            ->get();

        $items = $articles
            ->map(function ($article): array {
                $publishedAt = $article->first_published_at;
                $updatedAt = $article->last_substantive_update_at ?? $publishedAt;
                $type = $article->type instanceof ContentArticleType
                    ? $article->type->value
                    : (string) $article->type;
                $canonicalPath = NewsroomRouteContract::canonicalPath($type, (string) $article->slug);

                return [
                    'id' => 'urn:prawkonaraz:content-article:'.$article->getKey(),
                    'title' => trim((string) $article->title),
                    'link' => url($canonicalPath),
                    'published' => $publishedAt->toAtomString(),
                    'updated' => $updatedAt->toAtomString(),
                    'summary' => trim(strip_tags((string) $article->lead)),
                    'author' => $article->author?->is_published === true
                        ? trim((string) $article->author->name)
                        : null,
                ];
            })
            ->values()
            ->all();

        $latestUpdated = collect($items)
            ->pluck('updated')
            ->filter()
            ->map(fn (string $value): Carbon => Carbon::parse($value)->utc())
            ->sortByDesc(fn (Carbon $value): int => $value->getTimestamp())
            ->first();

        $siteName = $this->siteIdentity->siteName();
        $feedUrl = route('public.news.feed');

        return [
            'title' => $siteName.' — Aktualności',
            'id' => $feedUrl,
            'link' => route('public.news'),
            'self' => $feedUrl,
            'updated' => $latestUpdated?->toAtomString() ?? self::EMPTY_FEED_UPDATED,
            'author' => $siteName,
            'items' => $items,
        ];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
