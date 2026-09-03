<?php

namespace App\Support;

class TrafficSignRedirectPolicy
{
    /**
     * @return array{type: 'none'|'redirect'|'gone', slug?: string}
     */
    public function resolve(string $group, string $slug): array
    {
        $redirects = (array) config("content.redirects.{$group}.redirects", []);

        if (array_key_exists($slug, $redirects) && filled($redirects[$slug])) {
            return [
                'type' => 'redirect',
                'slug' => (string) $redirects[$slug],
            ];
        }

        $gone = (array) config("content.redirects.{$group}.gone", []);

        if (in_array($slug, $gone, true)) {
            return [
                'type' => 'gone',
            ];
        }

        return [
            'type' => 'none',
        ];
    }
}
