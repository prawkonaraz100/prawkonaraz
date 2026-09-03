<?php

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use Database\Seeders\TrafficSignSeoSeeder;
use Illuminate\Support\Facades\File;

test('traffic sign page renders canonical meta and structured data', function () {
    config()->set('content.organization.logo_url', 'https://example.test/favicon.png');

    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Anna SEO',
        'slug' => 'anna-seo',
    ]);

    $category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
    ]);

    $sign = TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'code' => 'A-7',
        'slug' => 'a-7-ustap-pierwszenstwa',
        'name' => 'Ustąp pierwszeństwa',
        'image_path' => 'traffic-signs/signs/prohibitions/znak-b-20-stop.webp',
        'image_alt' => 'Znak A-7 Ustąp pierwszeństwa',
        'image_width' => 320,
        'image_height' => 320,
        'og_image_path' => 'traffic-signs/signs/mandatory/znak-c-1-nakaz-jazdy-w-prawo.webp',
        'og_image_alt' => 'Grafika OG dla znaku A-7 Ustąp pierwszeństwa',
        'og_image_width' => 320,
        'og_image_height' => 320,
        'faq_items' => [
            ['question' => 'Co oznacza znak A-7?', 'answer' => 'Nakazuje ustąpić pierwszeństwa.'],
        ],
    ]);

    $response = $this->get(route('traffic-signs.show', $sign->slug));

    $response
        ->assertOk()
        ->assertSee('meta name="robots" content="index,follow,max-image-preview:large"', false)
        ->assertSee('rel="canonical" href="'.route('traffic-signs.show', $sign->slug).'"', false)
        ->assertSee('"@type":"Article"', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('meta property="og:image:alt" content="Grafika OG dla znaku A-7 Ustąp pierwszeństwa"', false)
        ->assertSee('meta property="og:image:width" content="320"', false)
        ->assertSee('meta property="og:image:height" content="320"', false)
        ->assertSee('meta property="og:image" content="http://localhost:8000/traffic-signs/signs/mandatory/znak-c-1-nakaz-jazdy-w-prawo.webp?v=', false)
        ->assertSee('rel="preload" href="http://localhost:8000/traffic-signs/signs/prohibitions/znak-b-20-stop.webp?v=', false);
});

test('public seo urls respect trusted proxy host and scheme', function () {
    config()->set('content.organization.logo_url', 'http://localhost:8000/favicon.png');

    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Proxy Autor',
        'slug' => 'proxy-autor',
    ]);

    $category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
    ]);

    $sign = TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'code' => 'A-7',
        'slug' => 'a-7-proxy-check',
        'name' => 'Ustąp pierwszeństwa',
        'image_path' => 'traffic-signs/signs/prohibitions/znak-b-20-stop.webp',
        'og_image_path' => 'traffic-signs/signs/mandatory/znak-c-1-nakaz-jazdy-w-prawo.webp',
        'og_image_alt' => 'Grafika OG dla testu proxy',
        'og_image_width' => 320,
        'og_image_height' => 320,
    ]);

    $this
        ->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'seo.example.test',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_HOST' => 'seo.example.test',
            'REMOTE_ADDR' => '10.0.0.1',
        ])
        ->get(route('traffic-signs.show', $sign->slug))
        ->assertOk()
        ->assertSee('rel="canonical" href="https://seo.example.test/znaki-drogowe/a-7-proxy-check"', false)
        ->assertSee('meta property="og:image" content="https://seo.example.test/traffic-signs/signs/mandatory/znak-c-1-nakaz-jazdy-w-prawo.webp?v=', false)
        ->assertSee('meta property="og:image:alt" content="Grafika OG dla testu proxy"', false)
        ->assertSee('rel="preload" href="https://seo.example.test/traffic-signs/signs/prohibitions/znak-b-20-stop.webp?v=', false)
        ->assertSee('"url":"https://seo.example.test"', false)
        ->assertDontSee('http://localhost:8000', false);
});

test('author and trust pages render schema-backed trust layer', function () {
    config()->set('content.organization.logo_url', 'https://example.test/favicon.png');
    config()->set('content.organization.email', 'kontakt@example.test');

    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Piotr Autor',
        'slug' => 'piotr-autor',
    ]);

    $category = TrafficSignCategory::factory()->published()->create();

    TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
    ]);

    $this->get(route('content-authors.show', $author->slug))
        ->assertOk()
        ->assertSee('"@type":"ProfilePage"', false)
        ->assertSee($author->name);

    $this->get(route('about.organization'))
        ->assertOk()
        ->assertSee('"@type":"Organization"', false);

    $this->get(route('about.contact'))
        ->assertOk()
        ->assertSee('"@type":"ContactPage"', false)
        ->assertSee('kontakt@example.test');

    $this->get(route('about.methodology'))
        ->assertOk()
        ->assertSee('"@type":"WebPage"', false)
        ->assertSeeText('Jak uczymy teorii i pytań na prawo jazdy?');
});

test('supporting comparison page renders article schema and canonical meta', function () {
    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Redakcja BRD',
        'slug' => 'redakcja-brd',
    ]);

    $warningCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
    ]);

    $prohibitionCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki zakazu',
        'slug' => 'znaki-zakazu',
    ]);

    TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $warningCategory->getKey(),
        'code' => 'A-7',
        'slug' => 'a-7-ustap-pierwszenstwa',
        'name' => 'Ustąp pierwszeństwa',
        'image_path' => 'traffic-signs/a-7.svg',
        'image_alt' => 'Znak A-7 Ustąp pierwszeństwa',
        'image_width' => 320,
        'image_height' => 320,
        'og_image_path' => 'traffic-signs/og/a-7.png',
        'og_image_alt' => 'Grafika OG dla znaku A-7 Ustąp pierwszeństwa',
        'og_image_width' => 320,
        'og_image_height' => 320,
    ]);

    TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $prohibitionCategory->getKey(),
        'code' => 'B-20',
        'slug' => 'b-20-stop',
        'name' => 'STOP',
        'image_path' => 'traffic-signs/b-20.svg',
        'og_image_path' => 'traffic-signs/og/b-20.png',
        'og_image_alt' => 'Grafika OG dla znaku B-20 STOP',
        'og_image_width' => 320,
        'og_image_height' => 320,
    ]);

    $this->get(route('traffic-signs.supporting.show', 'a-7-vs-b-20'))
        ->assertOk()
        ->assertSee('rel="canonical" href="'.route('traffic-signs.supporting.show', 'a-7-vs-b-20').'"', false)
        ->assertSee('"@type":"Article"', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('"A-7 a B-20 STOP - najważniejsze różnice dla kierowcy"', false);
});

test('robots and sitemaps expose crawlable seo infrastructure', function () {
    $author = ContentAuthor::factory()->published()->create([
        'slug' => 'anna-mapa',
    ]);
    $category = TrafficSignCategory::factory()->published()->create([
        'slug' => 'znaki-informacyjne',
    ]);
    $sign = TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'slug' => 'd-6-przejscie-dla-pieszych',
        'image_path' => 'traffic-signs/signs/prohibitions/znak-b-20-stop.webp',
        'og_image_path' => 'traffic-signs/signs/mandatory/znak-c-1-nakaz-jazdy-w-prawo.webp',
    ]);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: OAI-SearchBot')
        ->assertSee('User-agent: Claude-SearchBot')
        ->assertSee('User-agent: PerplexityBot')
        ->assertSee('User-agent: Applebot')
        ->assertSee('User-agent: DuckAssistBot')
        ->assertSee('User-agent: Meta-ExternalFetcher')
        ->assertSee('User-agent: GPTBot')
        ->assertSee('User-agent: ClaudeBot')
        ->assertSee('User-agent: FacebookBot')
        ->assertSee('User-agent: Google-CloudVertexBot')
        ->assertSee('Content-Signal: search=yes,ai-input=yes,ai-train=no')
        ->assertSee('Disallow: /admin/')
        ->assertSee('Disallow: /nauka/')
        ->assertSee('Disallow: /zaproszenie/')
        ->assertSee('Sitemap: '.route('sitemap.index'));

    expect(File::get(public_path('robots.txt')))
        ->toContain('User-agent: OAI-SearchBot')
        ->toContain('User-agent: Claude-SearchBot')
        ->toContain('User-agent: PerplexityBot')
        ->toContain('User-agent: Applebot')
        ->toContain('User-agent: DuckAssistBot')
        ->toContain('User-agent: Meta-ExternalFetcher')
        ->toContain('User-agent: GPTBot')
        ->toContain('User-agent: FacebookBot')
        ->toContain('Content-Signal: search=yes,ai-input=yes,ai-train=no')
        ->toContain('Sitemap: https://prawkonaraz.pl/sitemap.xml');

    $this->get(route('sitemap.index'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.url('/sitemaps/static.xml').'</loc>', false)
        ->assertSee('<loc>'.route('sitemap.signs').'</loc>', false)
        ->assertSee('<loc>'.route('sitemap.supporting-pages').'</loc>', false)
        ->assertSee('<loc>'.route('sitemap.categories').'</loc>', false)
        ->assertSee('<loc>'.route('sitemap.authors').'</loc>', false);

    $this->get(route('sitemap.static'))
        ->assertOk()
        ->assertSee('<loc>'.route('about.organization').'</loc>', false)
        ->assertSee('<loc>'.route('about.methodology').'</loc>', false)
        ->assertSee('<loc>'.route('about.contact').'</loc>', false);

    $this->get(route('sitemap.signs'))
        ->assertOk()
        ->assertSee('<loc>'.route('traffic-signs.show', $sign->slug).'</loc>', false)
        ->assertSee('<image:loc>http://localhost:8000/traffic-signs/signs/prohibitions/znak-b-20-stop.webp?v=', false)
        ->assertSee('<image:loc>http://localhost:8000/traffic-signs/signs/mandatory/znak-c-1-nakaz-jazdy-w-prawo.webp?v=', false);

    $this->get(route('sitemap.categories'))
        ->assertOk()
        ->assertSee('<loc>'.route('traffic-signs.categories.show', $category->slug).'</loc>', false);

    $this->get(route('sitemap.supporting-pages'))
        ->assertOk()
        ->assertSee('<loc>'.route('traffic-signs.supporting.show', 'a-7-vs-b-20').'</loc>', false);

    $this->get(route('sitemap.authors'))
        ->assertOk()
        ->assertSee('<loc>'.route('content-authors.show', $author->slug).'</loc>', false);
});

test('llms text exposes curated public discovery guide for AI retrieval', function () {
    $this->get('/llms.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('# PrawkoNaRaz.pl', false)
        ->assertSee('https://prawkonaraz.pl/sitemap.xml', false)
        ->assertSee('https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy', false)
        ->assertSee('https://prawkonaraz.pl/znaki-drogowe', false)
        ->assertSee('Use for model training is not granted', false);
});

test('redirect and gone policy works for sign slugs', function () {
    $author = ContentAuthor::factory()->published()->create();
    $category = TrafficSignCategory::factory()->published()->create();
    $sign = TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'slug' => 'nowy-slug-znaku',
    ]);

    config()->set('content.redirects.signs.redirects.stary-slug-znaku', $sign->slug);
    config()->set('content.redirects.signs.gone', ['usuniety-slug-znaku']);

    $this->get('/znaki-drogowe/stary-slug-znaku')
        ->assertRedirect(route('traffic-signs.show', $sign->slug), 301);

    $this->get('/znaki-drogowe/usuniety-slug-znaku')
        ->assertStatus(410);
});

test('legacy prohibition slugs redirect to canonical renamed sign pages', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $this->get('/znaki-drogowe/b-11-zakaz-wjazdu-wozkow-rowerowych')
        ->assertRedirect(route('traffic-signs.show', 'b-11-zakaz-wjazdu-wozow-recznych'), 301);

    $this->get('/znaki-drogowe/b-12-zakaz-wjazdu-wozkow-recznych')
        ->assertRedirect(route('traffic-signs.show', 'b-12-zakaz-wjazdu-wozow-recznych-z-towarem'), 301);

    $this->get('/znaki-drogowe/b-32-stoj-kontrola-celna')
        ->assertRedirect(route('traffic-signs.show', 'b-32-zatrzymanie-i-odprawa-celna'), 301);
});
