<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class IndexNowSubmissionService
{
    /**
     * @var list<string>
     */
    private const BLOCKED_PATH_PREFIXES = [
        '/admin',
        '/api',
        '/auth',
        '/build',
        '/css',
        '/dashboard',
        '/email',
        '/fonts',
        '/js',
        '/konto',
        '/login',
        '/logout',
        '/nauka',
        '/profile',
        '/register',
        '/reset-password',
        '/sanctum',
        '/sitemaps',
        '/storage',
        '/storage-bulk',
    ];

    /**
     * @var list<string>
     */
    private const BLOCKED_EXACT_PATHS = [
        '/apple-touch-icon.png',
        '/favicon.ico',
        '/favicon.png',
        '/llms.txt',
        '/manifest.webmanifest',
        '/offline.html',
        '/robots.txt',
        '/service-worker.js',
        '/sitemap.xml',
    ];

    /**
     * @var list<string>
     */
    private const BLOCKED_EXTENSIONS = [
        'avif',
        'css',
        'gif',
        'ico',
        'jpeg',
        'jpg',
        'js',
        'json',
        'map',
        'mp4',
        'png',
        'svg',
        'txt',
        'webmanifest',
        'webp',
        'woff',
        'woff2',
        'xml',
    ];

    /**
     * @param  iterable<mixed>  $urls
     * @return array<string, mixed>
     */
    public function submit(iterable $urls, bool $dryRun = false, ?int $limit = null, ?string $keyOverride = null): array
    {
        $host = $this->configuredHost();
        $endpoint = trim((string) config('indexnow.endpoint', ''));
        $enabled = (bool) config('indexnow.enabled', false);
        $key = trim($keyOverride ?? (string) config('indexnow.key', ''));
        $keyValid = $this->isValidKey($key);
        $maxUrlsPerRequest = $this->maxUrlsPerRequest();
        $inputUrls = is_array($urls) ? array_values($urls) : iterator_to_array($urls, false);
        $errors = [];
        $warnings = [];

        if ($host === null) {
            $errors[] = [
                'code' => 'invalid_host',
                'message' => 'INDEXNOW_HOST must be a valid host name.',
            ];
        }

        $normalization = $host !== null
            ? $this->normalizeUrls($inputUrls, $host)
            : ['accepted' => [], 'rejected' => []];

        $acceptedUrls = $normalization['accepted'];
        $acceptedBeforeLimit = count($acceptedUrls);

        if ($limit !== null) {
            $acceptedUrls = array_slice($acceptedUrls, 0, max(0, $limit));
        }

        if ($acceptedUrls === []) {
            $errors[] = [
                'code' => 'no_accepted_urls',
                'message' => 'No URL passed IndexNow safety filters.',
            ];
        }

        if (! $dryRun && ! $enabled) {
            $errors[] = [
                'code' => 'indexnow_disabled',
                'message' => 'INDEXNOW_ENABLED is false.',
            ];
        } elseif ($dryRun && ! $enabled) {
            $warnings[] = [
                'code' => 'indexnow_disabled_dry_run',
                'message' => 'INDEXNOW_ENABLED is false; dry-run will not send HTTP requests.',
            ];
        }

        if ($key === '') {
            $message = 'INDEXNOW_KEY is not configured.';
            $entry = ['code' => 'missing_key', 'message' => $message];
            $dryRun ? $warnings[] = $entry : $errors[] = $entry;
        } elseif (! $keyValid) {
            $errors[] = [
                'code' => 'invalid_key',
                'message' => 'INDEXNOW_KEY must use 8-128 letters, numbers or dashes.',
            ];
        }

        if (! $this->isValidEndpoint($endpoint)) {
            $entry = [
                'code' => 'invalid_endpoint',
                'message' => 'INDEXNOW_ENDPOINT must be a valid HTTPS URL.',
            ];
            $dryRun ? $warnings[] = $entry : $errors[] = $entry;
        }

        $keyLocation = $keyValid && $host !== null ? $this->keyLocation($key, $host) : null;
        $chunks = array_chunk($acceptedUrls, $maxUrlsPerRequest);
        $requests = [];
        $summary = [
            'input_urls' => count($inputUrls),
            'accepted_urls' => count($acceptedUrls),
            'accepted_before_limit' => $acceptedBeforeLimit,
            'rejected_urls' => count($normalization['rejected']),
            'planned_requests' => count($chunks),
            'sent_requests' => 0,
            'accepted_requests' => 0,
            'failed_requests' => 0,
            'max_urls_per_request' => $maxUrlsPerRequest,
            'limit' => $limit,
        ];

        if ($errors === []) {
            foreach ($chunks as $index => $chunk) {
                if ($dryRun) {
                    $requests[] = [
                        'index' => $index + 1,
                        'url_count' => count($chunk),
                        'status' => 'dry-run',
                        'accepted' => true,
                    ];

                    continue;
                }

                $result = $this->postChunk($endpoint, $host, $key, $keyLocation, $chunk);
                $summary['sent_requests']++;
                $requests[] = $result + [
                    'index' => $index + 1,
                    'url_count' => count($chunk),
                ];

                if ((bool) ($result['accepted'] ?? false)) {
                    $summary['accepted_requests']++;
                } else {
                    $summary['failed_requests']++;
                }
            }
        }

        return [
            'ok' => $errors === [] && (int) $summary['failed_requests'] === 0,
            'generated_at' => now()->toIso8601String(),
            'dry_run' => $dryRun,
            'enabled' => $enabled,
            'endpoint' => $endpoint,
            'host' => $host,
            'key_configured' => $key !== '',
            'key_valid' => $keyValid,
            'key_location' => $keyLocation !== null ? $this->maskKeyInString($keyLocation, $key) : null,
            'summary' => $summary,
            'accepted_urls' => $acceptedUrls,
            'rejected_urls' => $normalization['rejected'],
            'requests' => $requests,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<mixed>  $urls
     * @return array{accepted: list<string>, rejected: list<array{url: string, reason: string}>}
     */
    private function normalizeUrls(array $urls, string $host): array
    {
        $accepted = [];
        $seen = [];
        $rejected = [];

        foreach ($urls as $url) {
            $normalized = $this->normalizeUrl($url, $host);

            if (isset($normalized['reason'])) {
                $rejected[] = [
                    'url' => is_scalar($url) ? (string) $url : get_debug_type($url),
                    'reason' => $normalized['reason'],
                ];

                continue;
            }

            $normalizedUrl = $normalized['url'];

            if (isset($seen[$normalizedUrl])) {
                continue;
            }

            $seen[$normalizedUrl] = true;
            $accepted[] = $normalizedUrl;
        }

        return [
            'accepted' => $accepted,
            'rejected' => $rejected,
        ];
    }

    /**
     * @return array{url: string}|array{reason: string}
     */
    private function normalizeUrl(mixed $value, string $host): array
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return ['reason' => 'not_a_string'];
        }

        $url = trim((string) $value);

        if ($url === '') {
            return ['reason' => 'blank_url'];
        }

        if (preg_match('/\s/', $url) === 1) {
            return ['reason' => 'url_contains_whitespace'];
        }

        if (str_starts_with($url, '//')) {
            return ['reason' => 'scheme_relative_url'];
        }

        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = '/'.ltrim($url, '/');
            $url = 'https://'.$host.$url;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return ['reason' => 'invalid_url'];
        }

        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return ['reason' => 'non_https_url'];
        }

        if (strtolower((string) ($parts['host'] ?? '')) !== strtolower($host)) {
            return ['reason' => 'non_canonical_host'];
        }

        if (($parts['user'] ?? null) !== null || ($parts['pass'] ?? null) !== null) {
            return ['reason' => 'url_contains_credentials'];
        }

        if (isset($parts['query']) && $parts['query'] !== '') {
            return ['reason' => 'query_string_not_allowed'];
        }

        $path = $parts['path'] ?? '/';
        $path = $path !== '' ? $path : '/';

        if (! str_starts_with($path, '/')) {
            return ['reason' => 'invalid_path'];
        }

        $path = '/'.ltrim($path, '/');
        $pathLower = strtolower($path);

        if (in_array($pathLower, self::BLOCKED_EXACT_PATHS, true)) {
            return ['reason' => 'non_page_path'];
        }

        foreach (self::BLOCKED_PATH_PREFIXES as $prefix) {
            if ($pathLower === $prefix || str_starts_with($pathLower, $prefix.'/')) {
                return ['reason' => 'private_or_technical_path'];
            }
        }

        $extension = strtolower((string) pathinfo($pathLower, PATHINFO_EXTENSION));

        if ($extension !== '' && in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return ['reason' => 'non_html_resource'];
        }

        return [
            'url' => $path === '/'
                ? 'https://'.$host
                : 'https://'.$host.$path,
        ];
    }

    /**
     * @param  list<string>  $urls
     * @return array<string, mixed>
     */
    private function postChunk(string $endpoint, string $host, string $key, ?string $keyLocation, array $urls): array
    {
        $payload = [
            'host' => $host,
            'key' => $key,
            'urlList' => $urls,
        ];

        if ($keyLocation !== null) {
            $payload['keyLocation'] = $keyLocation;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(max(1, (int) config('indexnow.timeout', 10)))
                ->withHeaders(['User-Agent' => 'PrawkoNaRazIndexNow/1.0'])
                ->post($endpoint, $payload);

            return $this->responseReport($response);
        } catch (Throwable $exception) {
            return [
                'accepted' => false,
                'status' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{accepted: bool, status: int, reason: string}
     */
    private function responseReport(Response $response): array
    {
        $status = $response->status();

        return [
            'accepted' => in_array($status, [200, 202], true),
            'status' => $status,
            'reason' => match ($status) {
                200 => 'ok',
                202 => 'accepted_pending_validation',
                400 => 'bad_request',
                403 => 'forbidden_or_invalid_key',
                422 => 'unprocessable_urls',
                429 => 'rate_limited',
                default => $status >= 500 ? 'search_engine_error' : 'unexpected_status',
            },
        ];
    }

    private function configuredHost(): ?string
    {
        $host = trim((string) config('indexnow.host', ''));

        if (preg_match('/^https?:\/\//i', $host) === 1) {
            $host = (string) parse_url($host, PHP_URL_HOST);
        }

        $host = strtolower(trim($host));

        return preg_match('/\A[a-z0-9.-]+\z/', $host) === 1 ? $host : null;
    }

    private function isValidEndpoint(string $endpoint): bool
    {
        $parts = parse_url($endpoint);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && filled($parts['host'] ?? null);
    }

    private function isValidKey(string $key): bool
    {
        return preg_match('/\A[A-Za-z0-9-]{8,128}\z/', $key) === 1;
    }

    private function maxUrlsPerRequest(): int
    {
        $value = (int) config('indexnow.max_urls_per_request', 10000);

        return max(1, min(10000, $value));
    }

    private function keyLocation(string $key, string $host): string
    {
        $configuredLocation = trim((string) config('indexnow.key_location', ''));

        if ($configuredLocation !== '') {
            return $configuredLocation;
        }

        return 'https://'.$host.'/'.$key.'.txt';
    }

    private function maskKeyInString(string $value, string $key): string
    {
        if ($key === '' || ! str_contains($value, $key)) {
            return $value;
        }

        $masked = strlen($key) <= 12
            ? substr($key, 0, 4).'...'
            : substr($key, 0, 8).'...'.substr($key, -4);

        return str_replace($key, $masked, $value);
    }
}
