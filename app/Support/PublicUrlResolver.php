<?php

namespace App\Support;

use Illuminate\Support\Str;

class PublicUrlResolver
{
    public function currentRoot(): string
    {
        return rtrim(url('/'), '/');
    }

    public function normalize(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim((string) $url);

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $this->rewriteKnownLocalOrigin($url);
        }

        if (Str::startsWith($url, '/')) {
            return $this->currentRoot().$url;
        }

        return $this->currentRoot().'/'.ltrim($url, '/');
    }

    protected function rewriteKnownLocalOrigin(string $absoluteUrl): string
    {
        $target = parse_url($absoluteUrl);

        if (! is_array($target) || ! isset($target['host'])) {
            return $absoluteUrl;
        }

        $current = parse_url($this->currentRoot());
        $configured = parse_url((string) config('app.url', ''));

        $knownHosts = array_values(array_filter([
            'localhost',
            '127.0.0.1',
            $configured['host'] ?? null,
        ]));

        $knownPorts = array_values(array_filter([
            $configured['port'] ?? null,
            $target['scheme'] === 'http' ? 80 : 443,
        ], fn (mixed $port): bool => is_int($port) || ctype_digit((string) $port)));

        $targetPort = $target['port'] ?? ($target['scheme'] === 'http' ? 80 : 443);

        if (! in_array($target['host'], $knownHosts, true) || ! in_array($targetPort, $knownPorts, true)) {
            return $absoluteUrl;
        }

        if (! is_array($current) || ! isset($current['scheme'], $current['host'])) {
            return $absoluteUrl;
        }

        $rewritten = $current['scheme'].'://'.$current['host'];

        if (isset($current['port'])) {
            $rewritten .= ':'.$current['port'];
        }

        $rewritten .= $target['path'] ?? '';

        if (isset($target['query'])) {
            $rewritten .= '?'.$target['query'];
        }

        if (isset($target['fragment'])) {
            $rewritten .= '#'.$target['fragment'];
        }

        return $rewritten;
    }
}
