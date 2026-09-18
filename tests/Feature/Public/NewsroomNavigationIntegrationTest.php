<?php

use App\Support\PublicFooter;
use App\Support\PublicNavigation;

test('newsroom primary navigation links stay canonical and duplicate free', function () {
    $navigation = app(PublicNavigation::class)->data(null);
    $primary = collect($navigation['primary']);

    $news = $primary->where('label', 'Aktualności')->values();
    $guides = $primary->where('label', 'Poradniki')->values();

    expect($news)->toHaveCount(1)
        ->and($guides)->toHaveCount(1)
        ->and($news->first()['href'])->toBe(route('public.news', absolute: false))
        ->and($news->first()['match'])->toBe(['/aktualnosci'])
        ->and($guides->first()['href'])->toBe(route('public.guides', absolute: false))
        ->and($guides->first()['match'])->toBe(['/poradniki']);

    $hrefs = $primary->pluck('href');

    expect($hrefs->count())->toBe($hrefs->unique()->count());
});

test('shared footer exposes each newsroom hub exactly once for blade and vue renderers', function () {
    $navigation = app(PublicNavigation::class)->data(null);
    $footer = app(PublicFooter::class)->data(null, $navigation);
    $serviceLinks = collect($footer['service_links']);

    expect($serviceLinks->where('href', route('public.news', absolute: false)))->toHaveCount(1)
        ->and($serviceLinks->where('href', route('public.guides', absolute: false)))->toHaveCount(1)
        ->and($serviceLinks->pluck('href')->count())->toBe($serviceLinks->pluck('href')->unique()->count());

    $blade = file_get_contents(resource_path('views/components/site/public-footer.blade.php'));
    $vue = file_get_contents(resource_path('js/Components/SiteFooter.vue'));

    expect($blade)->toContain("@foreach (\$footer['service_links'] as \$link)")
        ->and($vue)->toContain('v-for="link in footer.service_links"');
});

test('newsroom document navigation prefixes remain explicit and duplicate free', function () {
    $source = file_get_contents(resource_path('js/support/public-navigation.ts'));

    expect(substr_count($source, "'/aktualnosci'"))->toBe(1)
        ->and(substr_count($source, "'/poradniki'"))->toBe(1);
});
