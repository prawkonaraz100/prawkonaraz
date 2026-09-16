<?php

use App\Enums\ContentArticleSourceType;
use App\Filament\Resources\ContentArticles\Pages\EditContentArticle;
use App\Filament\Resources\ContentArticles\Pages\ViewContentArticle;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\User;
use App\Support\NewsroomBodyContract;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

test('newsroom article preview requires authentication', function () {
    $article = ContentArticle::factory()->draft()->create();

    $this->get(route('admin.newsroom.articles.preview', $article))
        ->assertRedirect();
});

test('newsroom article preview rejects moderator and ordinary users', function () {
    $article = ContentArticle::factory()->draft()->create();

    foreach ([
        User::factory()->moderator()->create(),
        User::factory()->create(),
    ] as $user) {
        $this->actingAs($user)
            ->get(route('admin.newsroom.articles.preview', $article))
            ->assertForbidden();
    }
});

test('administrator preview is private noindex and does not load public analytics', function () {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-PREVIEW-TEST');
    config()->set('services.google_analytics.consent_required', true);

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->draft()->create([
        'title' => 'Roboczy artykuł do podglądu',
        'lead' => 'Lead widoczny tylko w prywatnym podglądzie.',
        'body_blocks' => NewsroomBodyContract::normalize([
            [
                'type' => NewsroomBodyContract::BLOCK_RICH_TEXT,
                'data' => [
                    'content' => [
                        'type' => 'doc',
                        'content' => [[
                            'type' => 'paragraph',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => '<script>alert(1)</script>',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => ' Bezpieczny link',
                                    'marks' => [[
                                        'type' => 'link',
                                        'attrs' => [
                                            'href' => 'https://example.test/source',
                                            'target' => '_blank',
                                        ],
                                    ]],
                                ],
                            ],
                        ]],
                    ],
                ],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_CONTEXT,
                'data' => [
                    'variant' => 'uwaga',
                    'title' => 'Ważne',
                    'text' => 'Treść calloutu.',
                ],
            ],
        ]),
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Official->value,
            'title' => 'Źródło publiczne',
            'url' => 'https://example.test/public',
            'is_publicly_cited' => true,
        ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->privateEvidence()
        ->create([
            'source_type' => ContentArticleSourceType::Interview->value,
            'title' => 'Prywatne źródło redakcyjne',
            'url' => null,
            'is_publicly_cited' => false,
            'note' => 'Notatka prywatna, której nie wolno pokazać.',
        ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.newsroom.articles.preview', $article));

    $response
        ->assertOk()
        ->assertSee('Podgląd redakcyjny')
        ->assertSee('Roboczy artykuł do podglądu')
        ->assertSee('Źródło publiczne')
        ->assertDontSee('Prywatne źródło redakcyjne')
        ->assertDontSee('Notatka prywatna, której nie wolno pokazać.')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('rel="noopener noreferrer"', false)
        ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('google-analytics-consent', false);

    expect((string) $response->headers->get('Cache-Control'))
        ->toContain('private')
        ->toContain('no-store')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow')
        ->and($response->headers->get('Referrer-Policy'))->toBe('no-referrer');
});

test('preview route is admin scoped and has no signed share token contract', function () {
    $article = ContentArticle::factory()->draft()->create();
    $route = Route::getRoutes()->getByName('admin.newsroom.articles.preview');

    expect($route)->not->toBeNull()
        ->and($route?->uri())->toBe('admin/newsroom/articles/{contentArticle}/preview')
        ->and($route?->gatherMiddleware())->toContain('auth')
        ->and($route?->gatherMiddleware())->not->toContain('signed');

    $url = route('admin.newsroom.articles.preview', $article);

    expect($url)
        ->not->toContain('signature=')
        ->not->toContain('expires=');

    $this->actingAs(User::factory()->admin()->create())
        ->get('/aktualnosci/'.$article->slug)
        ->assertNotFound();
});

test('content article edit and view pages expose preview action', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->draft()->create();

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->assertSee('Podgląd');

    Livewire::test(ViewContentArticle::class, ['record' => $article->getRouteKey()])
        ->assertSee('Podgląd');
});
