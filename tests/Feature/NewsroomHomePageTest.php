<?php

use App\Models\ContentArticle;
use App\Models\ContentCategory;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('newsroom home keeps the pre launch placeholder while the public gate is disabled', function () {
    config(['newsroom.public_enabled' => false]);

    $this->get(route('public.news'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertSee('Tu pojawią się aktualności dla kandydatów, kursantów i instruktorów prawa jazdy.')
        ->assertDontSee('Czy zdałbyś teorię dzisiaj?');
});

test('newsroom home renders the public blade from the gated read model when enabled', function () {
    Carbon::setTestNow('2026-09-18 10:00:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy',
        'description' => 'Zmiany i praktyczne informacje o egzaminach.',
        'position' => 10,
    ]);

    $lead = ContentArticle::factory()->published()->featured()->create([
        'category_id' => $category->id,
        'title' => 'Najważniejsza informacja dnia',
        'slug' => 'najwazniejsza-informacja-dnia',
        'lead' => 'Krótki lead materiału otwierającego serwis.',
        'editorial_priority' => 1000,
        'first_published_at' => now()->subMinute(),
        'published_at' => now()->subMinute(),
    ]);

    foreach (range(1, 16) as $index) {
        ContentArticle::factory()->published()->create([
            'category_id' => $category->id,
            'title' => "Materiał newsroomu {$index}",
            'slug' => "material-newsroomu-{$index}",
            'editorial_priority' => 100 - $index,
            'first_published_at' => now()->subMinutes($index + 1),
            'published_at' => now()->subMinutes($index + 1),
        ]);
    }

    $response = $this->get(route('public.news'));

    $response
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertViewIs('newsroom.home')
        ->assertViewHas('home')
        ->assertSee('<h1', false)
        ->assertSee('Aktualności')
        ->assertSee($lead->title)
        ->assertSee('Najnowsze')
        ->assertSee('Egzaminy')
        ->assertSee('Czy zdałbyś teorię dzisiaj?')
        ->assertSee('Rozpocznij bezpłatny test')
        ->assertSee(route('public.tests'), false)
        ->assertDontSee('Tu pojawią się aktualności dla kandydatów, kursantów i instruktorów prawa jazdy.')
        ->assertDontSee('MarketingPlaceholder');
});

test('newsroom home omits empty editorial sections instead of rendering fake cards', function () {
    config(['newsroom.public_enabled' => true]);

    $this->get(route('public.news'))
        ->assertOk()
        ->assertViewIs('newsroom.home')
        ->assertSee('Aktualności')
        ->assertSee('Czy zdałbyś teorię dzisiaj?')
        ->assertDontSee('Najnowsze')
        ->assertDontSee('Ważne teraz')
        ->assertDontSee('Brak leadu')
        ->assertDontSee('Brak kart')
        ->assertDontSee('MarketingPlaceholder');
});
