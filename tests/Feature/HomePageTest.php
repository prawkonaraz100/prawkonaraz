<?php

use App\Support\PaymentRequirementService;

afterEach(function (): void {
    app(PaymentRequirementService::class)->forgetCachedRequirement();
});

test('home page renders the public landing page', function () {
    $seoYear = now('Europe/Warsaw')->format('Y');

    $this->get('/')
        ->assertOk()
        ->assertSeeText("Testy na prawo jazdy {$seoYear}")
        ->assertSeeText('Ucz się szybko')
        ->assertSeeText('prawko na raz!')
        ->assertSeeText('Rozpocznij test')
        ->assertSeeText('Kontynuuj z Google')
        ->assertSee('data-home-site-header', false)
        ->assertSee('data-home-header-menu', false)
        ->assertSee('class="home-site-header is-visible', false)
        ->assertSee('aria-label="Otwórz menu serwisu"', false)
        ->assertSeeText('Cała nauka')
        ->assertSeeText('Każdy błąd')
        ->assertSeeText('Oficjalne źródła')
        ->assertSee('alt="Herb Rzeczypospolitej Polskiej"', false)
        ->assertSee('alt="Ministerstwo Infrastruktury"', false)
        ->assertSee('alt="CEPiK — Centralna Ewidencja Pojazdów i Kierowców"', false)
        ->assertSee('alt="ELI — Europejski Identyfikator Prawodawstwa"', false)
        ->assertSee('images/partners/herb-polski.svg', false)
        ->assertSee('images/partners/ministerstwo-infrastruktury.png', false)
        ->assertSee('images/partners/cepik-gov.png', false)
        ->assertSee('images/partners/eli.png', false)
        ->assertSeeText('Zgodność platformy z wymaganiami e-learningu OSK')
        ->assertSeeText('Nadzór OSK nad szkoleniem')
        ->assertDontSeeText('Zobacz pełną podstawę prawną')
        ->assertDontSeeText('Testy są zgodne z obowiązującymi przepisami, w szczególności:')
        ->assertDontSeeText('Przeglądaj najważniejsze')
        ->assertDontSeeText('Platforma żyje razem z kursantami')
        ->assertDontSeeText('Testy na prawo jazdy — dzisiaj w liczbach')
        ->assertDontSeeText('Prawo-Jazdy-360')
        ->assertDontSeeText('To, co naprawdę pomaga zdać')
        ->assertDontSeeText('Tryb wspólnej nauki')
        ->assertSeeText('Oficjalna baza pytań na prawo jazdy')
        ->assertSeeText('Przygotuj się do egzaminu teoretycznego krok po kroku.')
        ->assertSee("<title>Testy na prawo jazdy {$seoYear} – oficjalna baza pytań | PrawkoNaRaz</title>", false)
        ->assertSee('przygotuj się do egzaminu teoretycznego na prawo jazdy', false)
        ->assertSee('<meta property="og:site_name" content="PrawkoNaRaz">', false)
        ->assertSee('<meta name="application-name" content="PrawkoNaRaz">', false)
        ->assertSee('data-home-contact-dialog', false)
        ->assertDontSeeText('Wybierz dostęp i zacznij naukę');
});

test('home page uses the canonical site identity graph', function () {
    config()->set('content.organization.name', 'Canonical Test Brand');
    config()->set('content.organization.alternate_name', 'canonical.example');
    config()->set('content.organization.logo_url', 'https://cdn.example.test/brand.png');
    config()->set('content.organization.logo_width', 1200);
    config()->set('content.organization.logo_height', 320);

    $response = $this->get(route('home'))
        ->assertOk()
        ->assertViewHas('meta', fn (array $meta): bool => $meta['image_alt'] === 'Widok platformy Canonical Test Brand'
            && str_ends_with($meta['title'], '| Canonical Test Brand'))
        ->assertSee('<meta property="og:site_name" content="Canonical Test Brand">', false)
        ->assertSee('<meta name="application-name" content="Canonical Test Brand">', false)
        ->assertDontSee('Orły na Drodze');

    $structuredData = $response->viewData('structuredData');
    expect($structuredData['@context'])->toBe('https://schema.org');

    $graph = collect($structuredData['@graph']);
    $organization = $graph->firstWhere('@type', 'Organization');
    $website = $graph->firstWhere('@type', 'WebSite');

    expect($organization)
        ->not->toBeNull()
        ->and($organization['@id'])->toEndWith('/#organization')
        ->and($organization['name'])->toBe('Canonical Test Brand')
        ->and($organization['alternateName'])->toBe('canonical.example')
        ->and($organization['logo']['url'])->toBe('https://cdn.example.test/brand.png')
        ->and($organization['logo']['width'])->toBe(1200)
        ->and($organization['logo']['height'])->toBe(320)
        ->and($website)
        ->not->toBeNull()
        ->and($website['@id'])->toEndWith('/#website')
        ->and($website['name'])->toBe('Canonical Test Brand')
        ->and($website['publisher']['@id'])->toBe($organization['@id']);
});

test('open access mode hides public friend invitation entry points', function () {
    app(PaymentRequirementService::class)->setRequiresPayment(false);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Wpisz kod zaproszenia');

    $this->get(route('about.how-it-works'))
        ->assertOk()
        ->assertDontSeeText('Mam kod zaproszenia')
        ->assertDontSeeText('Zaproszenie od znajomego');
});
