<?php

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Support\NewsroomBodyContract;
use App\Support\NewsroomMediaStorage;

test('public newsroom article renders canonical content seo schema and safe presentation', function () {
    $media = app(NewsroomMediaStorage::class);
    $heroPath = $media->newImagePath('image/jpeg');
    $bodyImagePath = $media->newImagePath('image/jpeg');
    $related = ContentArticle::factory()->published()->create([
        'title' => 'Powiązany publiczny materiał',
        'slug' => 'powiazany-publiczny-material',
    ]);

    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'title' => 'Nowe zasady egzaminu teoretycznego',
        'slug' => 'nowe-zasady-egzaminu-teoretycznego',
        'lead' => 'Najważniejsze informacje dla kandydatów na kierowców.',
        'origin_type' => ContentArticleOriginType::OfficialSource->value,
        'key_points' => ['Pierwszy punkt', 'Drugi punkt'],
        'correction_note' => 'Korekta z 17.09.2026: doprecyzowano termin.',
        'regulatory_status' => ContentArticleRegulatoryStatus::InForce->value,
        'effective_from' => '2026-10-01',
        'change_summary' => 'Zmienia się sposób prezentacji zagadnienia.',
        'applies_to' => 'Kandydatów na kategorię B.',
        'exam_impact' => 'Na egzaminie trzeba znać nową regułę.',
        'hero_image_path' => $heroPath,
        'hero_image_alt' => 'Samochód na placu egzaminacyjnym',
        'hero_image_width' => 1600,
        'hero_image_height' => 900,
        'hero_image_caption' => 'Plac egzaminacyjny.',
        'hero_focal_x' => 0.25,
        'hero_focal_y' => 0.75,
        'image_credit' => 'PrawkoNaRaz',
        'body_blocks' => NewsroomBodyContract::normalize([
            [
                'type' => NewsroomBodyContract::BLOCK_RICH_TEXT,
                'data' => ['content' => [
                    'type' => 'doc',
                    'content' => [[
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => '<script>alert(1)</script>'],
                            ['type' => 'text', 'text' => ' Bezpieczna treść.'],
                        ],
                    ]],
                ]],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_IMAGE,
                'data' => [
                    'path' => $bodyImagePath,
                    'alt' => 'Schemat sytuacji drogowej',
                    'caption' => 'Przykład sytuacji.',
                    'credit' => 'Redakcja',
                    'width' => 1200,
                    'height' => 800,
                    'focal_x' => 0.4,
                    'focal_y' => 0.6,
                ],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_QUOTE,
                'data' => [
                    'text' => 'Treść oficjalnego komunikatu.',
                    'attribution' => 'Organ publiczny',
                    'source_url' => 'https://example.test/komunikat',
                ],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_CONTEXT,
                'data' => [
                    'variant' => 'uwaga',
                    'title' => 'Uwaga',
                    'text' => 'To ważny kontekst.',
                ],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_RELATED_ARTICLE,
                'data' => ['article_id' => $related->id],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_LEGAL_REFERENCE,
                'data' => ['legal_unit_id' => 999],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_QUESTION_GROUP,
                'data' => ['question_ids' => [101, 102]],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_TRAFFIC_SIGN_GROUP,
                'data' => ['traffic_sign_ids' => [201]],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_PRODUCT_CTA,
                'data' => ['kind' => 'test'],
            ],
        ]),
    ]);

    ContentArticleSource::factory()->for($article, 'article')->create([
        'source_type' => ContentArticleSourceType::Official->value,
        'title' => 'Publiczne źródło rozporządzenia',
        'publisher' => 'Dziennik Ustaw',
        'url' => 'https://example.test/akt',
        'is_publicly_cited' => true,
    ]);

    ContentArticleSource::factory()->for($article, 'article')->privateEvidence()->create([
        'title' => 'Prywatny dowód redakcyjny',
        'note' => 'Poufna notatka redakcyjna.',
    ]);

    $response = $this->get(route('public.news.show', ['articleSlug' => $article->slug]));

    $response
        ->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('Nowe zasady egzaminu teoretycznego')
        ->assertSee('Najważniejsze informacje dla kandydatów na kierowców.')
        ->assertSee('Opracowanie na podstawie oficjalnych źródeł')
        ->assertSee('Kontekst zmian i egzaminu')
        ->assertSee('Na egzaminie trzeba znać nową regułę.')
        ->assertSee('Publiczne źródło rozporządzenia')
        ->assertDontSee('Prywatny dowód redakcyjny')
        ->assertDontSee('Poufna notatka redakcyjna.')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Powiązany publiczny materiał')
        ->assertSee('Korekta z 17.09.2026')
        ->assertSee('object-position: 25.00% 75.00%', false)
        ->assertSee('object-position: 40.00% 60.00%', false);

    $response
        ->assertSee('<link rel="canonical" href="'.route('public.news.show', ['articleSlug' => $article->slug]).'">', false)
        ->assertSee('property="og:type" content="article"', false)
        ->assertSee('"@type":"NewsArticle"', false)
        ->assertSee('href="'.route('content-authors.show', $article->author->slug).'"', false)
        ->assertSee('href="'.route('public.news.categories.show', $article->category->slug).'"', false)
        ->assertDontSee('Moduł publiczny N3')
        ->assertDontSee('podstawa prawna #999')
        ->assertDontSee('grupa pytań')
        ->assertDontSee('CTA produktu');
});

test('public guide uses guide route family and does not inject category into breadcrumbs', function () {
    $guide = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Jak przygotować się do egzaminu',
        'slug' => 'jak-przygotowac-sie-do-egzaminu',
        'lead' => 'Praktyczny poradnik krok po kroku.',
    ]);

    $response = $this->get(route('public.guides.show', ['articleSlug' => $guide->slug]));

    $response
        ->assertOk()
        ->assertSee('Jak przygotować się do egzaminu')
        ->assertSee('href="'.route('public.guides').'"', false)
        ->assertDontSee('href="'.route('public.news.categories.show', $guide->category->slug).'"', false)
        ->assertSee('"@type":"Article"', false)
        ->assertDontSee('"@type":"NewsArticle"', false);
});

test('public detail routes distinguish hidden wrong-family and withdrawn content', function () {
    $draft = ContentArticle::factory()->draft()->create([
        'title' => 'Tajny szkic artykułu',
        'slug' => 'tajny-szkic-artykulu',
    ]);
    $guide = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Poradnik tylko w swojej rodzinie',
        'slug' => 'poradnik-tylko-w-swojej-rodzinie',
    ]);
    $withdrawn = ContentArticle::factory()->withdrawn()->create([
        'title' => 'Wycofany materiał o egzaminie',
        'slug' => 'wycofany-material-o-egzaminie',
        'body_blocks' => NewsroomBodyContract::normalize([
            [
                'type' => NewsroomBodyContract::BLOCK_RICH_TEXT,
                'data' => ['content' => [
                    'type' => 'doc',
                    'content' => [[
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Treść, której nie wolno pokazać.']],
                    ]],
                ]],
            ],
        ]),
    ]);

    $this->get(route('public.news.show', ['articleSlug' => $draft->slug]))
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertDontSee('Tajny szkic artykułu');

    $this->get(route('public.news.show', ['articleSlug' => $guide->slug]))
        ->assertNotFound()
        ->assertDontSee('Poradnik tylko w swojej rodzinie');

    $this->get(route('public.news.show', ['articleSlug' => $withdrawn->slug]))
        ->assertStatus(410)
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertSee('Materiał został wycofany')
        ->assertSee('href="'.route('public.news').'"', false)
        ->assertDontSee('Wycofany materiał o egzaminie')
        ->assertDontSee('Treść, której nie wolno pokazać.');
});

test('archived and needs review articles keep their public 200 transparency state', function () {
    $archived = ContentArticle::factory()->archived()->create([
        'title' => 'Archiwalny materiał',
        'slug' => 'archiwalny-material',
    ]);
    $needsReview = ContentArticle::factory()->needsReview()->create([
        'title' => 'Materiał wymagający ponownej weryfikacji',
        'slug' => 'material-wymagajacy-ponownej-weryfikacji',
        'last_substantive_update_at' => now()->subDay(),
    ]);

    $this->get(route('public.news.show', ['articleSlug' => $archived->slug]))
        ->assertOk()
        ->assertSee('Materiał archiwalny')
        ->assertSee('Archiwalny materiał');

    $this->get(route('public.news.show', ['articleSlug' => $needsReview->slug]))
        ->assertOk()
        ->assertSee('Materiał jest w trakcie ponownej weryfikacji')
        ->assertSee('Materiał wymagający ponownej weryfikacji');
});

test('related article blocks fail closed for non public author or inactive category', function () {
    $hiddenAuthor = ContentAuthor::factory()->create();
    $inactiveCategory = ContentCategory::factory()->inactive()->create();

    $hiddenAuthorArticle = ContentArticle::factory()->published()->create([
        'author_id' => $hiddenAuthor->id,
        'title' => 'Materiał z ukrytym autorem',
        'slug' => 'material-z-ukrytym-autorem',
    ]);
    $inactiveCategoryArticle = ContentArticle::factory()->published()->create([
        'category_id' => $inactiveCategory->id,
        'title' => 'Materiał z nieaktywną kategorią',
        'slug' => 'material-z-nieaktywna-kategoria',
    ]);

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Artykuł z relacjami fail closed',
        'slug' => 'artykul-z-relacjami-fail-closed',
        'body_blocks' => NewsroomBodyContract::normalize([
            ['type' => NewsroomBodyContract::BLOCK_RELATED_ARTICLE, 'data' => ['article_id' => $hiddenAuthorArticle->id]],
            ['type' => NewsroomBodyContract::BLOCK_RELATED_ARTICLE, 'data' => ['article_id' => $inactiveCategoryArticle->id]],
        ]),
    ]);

    $this->get(route('public.news.show', ['articleSlug' => $article->slug]))
        ->assertOk()
        ->assertDontSee('Materiał z ukrytym autorem')
        ->assertDontSee('Materiał z nieaktywną kategorią');
});
