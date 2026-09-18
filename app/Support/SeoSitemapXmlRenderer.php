<?php

namespace App\Support;

class SeoSitemapXmlRenderer
{
    /**
     * @param  list<array{loc: string, lastmod?: string|null}>  $items
     */
    public function sitemapIndex(array $items): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($items as $item) {
            $xml[] = '    <sitemap>';
            $xml[] = '        <loc>'.$this->escape($item['loc']).'</loc>';

            if (! empty($item['lastmod'])) {
                $xml[] = '        <lastmod>'.$this->escape($item['lastmod']).'</lastmod>';
            }

            $xml[] = '    </sitemap>';
        }

        $xml[] = '</sitemapindex>';

        return implode("\n", $xml)."\n";
    }

    /**
     * @param  list<array{loc: string, lastmod?: string|null, images?: array<int, string>}>  $urls
     */
    public function urlset(array $urls, bool $includeImages = false): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = $includeImages
            ? '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'
            : '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $item) {
            $xml[] = '    <url>';
            $xml[] = '        <loc>'.$this->escape($item['loc']).'</loc>';

            if (! empty($item['lastmod'])) {
                $xml[] = '        <lastmod>'.$this->escape($item['lastmod']).'</lastmod>';
            }

            if ($includeImages) {
                foreach ($item['images'] ?? [] as $image) {
                    $xml[] = '        <image:image>';
                    $xml[] = '            <image:loc>'.$this->escape($image).'</image:loc>';
                    $xml[] = '        </image:image>';
                }
            }

            $xml[] = '    </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml)."\n";
    }

    /**
     * @param  list<array{loc:string,publication_name:string,language:string,publication_date:string,title:string}>  $urls
     */
    public function newsUrlset(array $urls): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';

        foreach ($urls as $item) {
            $xml[] = '    <url>';
            $xml[] = '        <loc>'.$this->escape($item['loc']).'</loc>';
            $xml[] = '        <news:news>';
            $xml[] = '            <news:publication>';
            $xml[] = '                <news:name>'.$this->escape($item['publication_name']).'</news:name>';
            $xml[] = '                <news:language>'.$this->escape($item['language']).'</news:language>';
            $xml[] = '            </news:publication>';
            $xml[] = '            <news:publication_date>'.$this->escape($item['publication_date']).'</news:publication_date>';
            $xml[] = '            <news:title>'.$this->escape($item['title']).'</news:title>';
            $xml[] = '        </news:news>';
            $xml[] = '    </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml)."\n";
    }

    /**
     * @param  list<array{loc: string, lastmod?: string|null, videos: list<array{thumbnail_loc: string, title: string, description: string, content_loc: string, duration?: int|null, publication_date?: string|null, family_friendly?: string|null}>}>  $urls
     */
    public function videoUrlset(array $urls): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">';

        foreach ($urls as $item) {
            $xml[] = '    <url>';
            $xml[] = '        <loc>'.$this->escape($item['loc']).'</loc>';

            if (! empty($item['lastmod'])) {
                $xml[] = '        <lastmod>'.$this->escape($item['lastmod']).'</lastmod>';
            }

            foreach ($item['videos'] ?? [] as $video) {
                $xml[] = '        <video:video>';
                $xml[] = '            <video:thumbnail_loc>'.$this->escape($video['thumbnail_loc']).'</video:thumbnail_loc>';
                $xml[] = '            <video:title>'.$this->escape($video['title']).'</video:title>';
                $xml[] = '            <video:description>'.$this->escape($video['description']).'</video:description>';
                $xml[] = '            <video:content_loc>'.$this->escape($video['content_loc']).'</video:content_loc>';

                if (! empty($video['duration'])) {
                    $xml[] = '            <video:duration>'.$this->escape((string) $video['duration']).'</video:duration>';
                }

                if (! empty($video['publication_date'])) {
                    $xml[] = '            <video:publication_date>'.$this->escape($video['publication_date']).'</video:publication_date>';
                }

                if (! empty($video['family_friendly'])) {
                    $xml[] = '            <video:family_friendly>'.$this->escape($video['family_friendly']).'</video:family_friendly>';
                }

                $xml[] = '        </video:video>';
            }

            $xml[] = '    </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml)."\n";
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
