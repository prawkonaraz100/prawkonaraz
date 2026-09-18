<?php

use Tests\TestCase;

uses(TestCase::class);

test('production nginx gives robots and static sitemaps crawler safe cache headers and validators', function () {
    $config = file_get_contents(base_path('deploy/mikrus/nginx/prawkobit.conf.example'));

    expect($config)->not->toBeFalse();

    $requiredBlocks = [
        'location = /robots.txt {' => 'default_type text/plain;',
        'location = /sitemap.xml {' => 'default_type application/xml;',
        'location ^~ /sitemaps/ {' => 'default_type application/xml;',
    ];

    foreach ($requiredBlocks as $location => $contentType) {
        $start = strpos($config, $location);

        expect($start)->not->toBeFalse();

        $end = strpos($config, "\n    }", $start);

        expect($end)->not->toBeFalse();

        $block = substr($config, $start, $end - $start);

        expect($block)
            ->toContain($contentType)
            ->toContain('etag on;')
            ->toContain('if_modified_since exact;')
            ->toContain('add_header Cache-Control "public, max-age=3600";')
            ->toContain('add_header X-Content-Type-Options "nosniff";')
            ->toContain('try_files $uri /index.php?$query_string;');
    }
});
