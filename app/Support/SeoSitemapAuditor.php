<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class SeoSitemapAuditor
{
    /**
     * @return list<string>
     */
    public function audit(): array
    {
        $errors = [];
        $robotsPath = public_path('robots.txt');
        $sitemapPath = public_path('sitemap.xml');

        if (! File::exists($robotsPath)) {
            $errors[] = 'Missing public/robots.txt.';
        } else {
            $robots = File::get($robotsPath);

            if (! str_contains($robots, 'Sitemap: https://prawkonaraz.pl/sitemap.xml')) {
                $errors[] = 'robots.txt does not point at https://prawkonaraz.pl/sitemap.xml.';
            }
        }

        if (! File::exists($sitemapPath)) {
            $errors[] = 'Missing public/sitemap.xml. Run php artisan seo:generate-sitemaps.';

            return $errors;
        }

        $allLocs = [];
        $index = $this->loadXml($sitemapPath, $errors);

        if ($index === null) {
            return $errors;
        }

        $this->validateFileLimits($sitemapPath, 'sitemap.xml', $index, $errors);

        foreach ($index->children() as $sitemap) {
            $loc = trim((string) $sitemap->loc);
            $this->validateLoc($loc, 'sitemap index', $errors);

            $relativePath = $this->relativePathFromPublicUrl($loc);

            if ($relativePath === null) {
                $errors[] = 'Sitemap index loc is outside prawkonaraz.pl: '.$loc;

                continue;
            }

            $childPath = public_path($relativePath);

            if (! File::exists($childPath)) {
                $errors[] = 'Missing child sitemap file for '.$loc.' at public/'.$relativePath.'.';

                continue;
            }

            $child = $this->loadXml($childPath, $errors);

            if ($child === null) {
                continue;
            }

            $this->validateFileLimits($childPath, $relativePath, $child, $errors);

            foreach ($child->children() as $entry) {
                if ($entry->getName() === 'sitemap') {
                    $childLoc = trim((string) $entry->loc);
                    $this->validateLoc($childLoc, $relativePath, $errors);

                    continue;
                }

                $url = trim((string) $entry->loc);
                $this->validateLoc($url, $relativePath, $errors);
                $isVideoSitemap = $relativePath === 'sitemaps/videos.xml';
                $isNewsSitemap = $relativePath === ltrim(SeoSitemapBuilder::NEWS_SITEMAP_PATH, '/')
                    || preg_match('#^sitemaps/news-\d+-\d+\.xml$#', $relativePath) === 1;
                $isSupplementalSitemap = $isVideoSitemap || $isNewsSitemap;

                if (! $isSupplementalSitemap && isset($allLocs[$url])) {
                    $errors[] = 'Duplicate sitemap URL: '.$url;
                }

                if (! $isSupplementalSitemap) {
                    $allLocs[$url] = true;
                }

                if ($isVideoSitemap) {
                    $this->validateVideoEntry($entry, $url, $errors);
                }
            }
        }

        return $errors;
    }

    /**
     * @param  list<string>  $errors
     */
    protected function loadXml(string $path, array &$errors): ?\SimpleXMLElement
    {
        $xml = @simplexml_load_file($path);

        if (! $xml instanceof \SimpleXMLElement) {
            $errors[] = 'Invalid XML: '.$path;

            return null;
        }

        return $xml;
    }

    /**
     * @param  list<string>  $errors
     */
    protected function validateLoc(string $loc, string $source, array &$errors): void
    {
        if ($loc === '') {
            $errors[] = 'Empty loc in '.$source.'.';

            return;
        }

        if ($loc !== 'https://prawkonaraz.pl' && ! str_starts_with($loc, 'https://prawkonaraz.pl/')) {
            $errors[] = 'Non-canonical loc in '.$source.': '.$loc;
        }

        foreach (['http://', 'prawkoapp.pl', 'prawkobit.pl', '/admin', '/api/', '/profile', '/nauka'] as $forbidden) {
            if (str_contains($loc, $forbidden)) {
                $errors[] = 'Forbidden loc pattern "'.$forbidden.'" in '.$source.': '.$loc;
            }
        }
    }

    /**
     * @param  list<string>  $errors
     */
    protected function validateFileLimits(
        string $path,
        string $source,
        \SimpleXMLElement $xml,
        array &$errors,
    ): void {
        $maxUrls = max(
            1,
            (int) config('seo.sitemap_max_urls_per_file', SeoSitemapGenerator::MAX_URLS_PER_FILE),
        );
        $maxBytes = max(
            1,
            (int) config('seo.sitemap_max_uncompressed_bytes', SeoSitemapGenerator::MAX_UNCOMPRESSED_BYTES),
        );
        $entries = count($xml->children());

        if ($entries > $maxUrls) {
            $errors[] = sprintf(
                'Sitemap entry limit exceeded in %s: entries=%d limit=%d.',
                $source,
                $entries,
                $maxUrls,
            );
        }

        $bytes = File::size($path);

        if ($bytes > $maxBytes) {
            $errors[] = sprintf(
                'Sitemap byte limit exceeded in %s: bytes=%d limit=%d.',
                $source,
                $bytes,
                $maxBytes,
            );
        }
    }

    /**
     * @param  list<string>  $errors
     */
    protected function validateVideoEntry(\SimpleXMLElement $entry, string $pageUrl, array &$errors): void
    {
        $videoNamespace = 'http://www.google.com/schemas/sitemap-video/1.1';
        $videos = $entry->children($videoNamespace);

        if (! isset($videos->video)) {
            $errors[] = 'Video sitemap entry has no video:video block: '.$pageUrl;

            return;
        }

        foreach ($videos->video as $video) {
            $thumbnail = trim((string) $video->thumbnail_loc);
            $title = trim((string) $video->title);
            $description = trim((string) $video->description);
            $content = trim((string) $video->content_loc);
            $duration = trim((string) $video->duration);

            foreach ([
                'video:thumbnail_loc' => $thumbnail,
                'video:title' => $title,
                'video:description' => $description,
                'video:content_loc' => $content,
            ] as $tag => $value) {
                if ($value === '') {
                    $errors[] = 'Missing '.$tag.' in video sitemap entry: '.$pageUrl;
                }
            }

            foreach ([
                'video:thumbnail_loc' => $thumbnail,
                'video:content_loc' => $content,
            ] as $tag => $url) {
                if ($url !== '' && ! str_starts_with($url, 'https://')) {
                    $errors[] = 'Non-HTTPS '.$tag.' in video sitemap entry: '.$url;
                }
            }

            if ($content !== '' && $content === $pageUrl) {
                $errors[] = 'video:content_loc must not equal page loc: '.$pageUrl;
            }

            if ($duration !== '' && (! ctype_digit($duration) || (int) $duration <= 0)) {
                $errors[] = 'Invalid video:duration in video sitemap entry: '.$pageUrl;
            }

            if (mb_strlen($description) > 2048) {
                $errors[] = 'video:description is longer than 2048 characters in video sitemap entry: '.$pageUrl;
            }

            if (preg_match('/^(tak|nie)(?:[\s,.!?]|$)/iu', $description) === 1) {
                $errors[] = 'video:description looks like an answer explanation instead of a neutral video description: '.$pageUrl;
            }
        }
    }

    protected function relativePathFromPublicUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== 'prawkonaraz.pl') {
            return null;
        }

        $path = ltrim((string) ($parts['path'] ?? ''), '/');

        return $path !== '' ? $path : null;
    }
}
