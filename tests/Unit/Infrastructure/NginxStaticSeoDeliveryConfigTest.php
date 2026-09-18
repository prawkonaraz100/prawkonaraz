<?php

use Tests\TestCase;

uses(TestCase::class);

test('mikrus nginx serves canonical static seo artifacts without php fallback', function () {
    $config = file_get_contents(base_path('deploy/mikrus/nginx/prawkobit.conf.example'));

    expect($config)->not->toBeFalse();

    $extractLocation = static function (string $pattern) use ($config): string {
        preg_match($pattern, (string) $config, $matches);

        return (string) ($matches['body'] ?? '');
    };

    $robots = $extractLocation('/location = \/robots\.txt \{(?<body>.*?)\n    \}/s');
    $rootSitemap = $extractLocation('/location = \/sitemap\.xml \{(?<body>.*?)\n    \}/s');
    $childSitemaps = $extractLocation('/location \^~ \/sitemaps\/ \{(?<body>.*?)\n    \}/s');

    expect($robots)
        ->not->toBe('')
        ->toContain('default_type text/plain;')
        ->toContain('Cache-Control "public, max-age=300, s-maxage=300, must-revalidate"')
        ->toContain('etag on;')
        ->toContain('if_modified_since exact;')
        ->toContain('try_files $uri =404;')
        ->not->toContain('index.php');

    foreach ([$rootSitemap, $childSitemaps] as $location) {
        expect($location)
            ->not->toBe('')
            ->toContain('default_type application/xml;')
            ->toContain('Cache-Control "public, max-age=60, s-maxage=60, must-revalidate"')
            ->toContain('etag on;')
            ->toContain('if_modified_since exact;')
            ->toContain('try_files $uri =404;')
            ->not->toContain('index.php');
    }
});
