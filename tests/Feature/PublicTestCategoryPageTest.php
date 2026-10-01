<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Support\PublicNavigation;
use App\Support\SeoSitemapBuilder;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('content.organization.name', 'PrawkoNaRaz');
    config()->set('content.organization.email', 'kontakt@prawkonaraz.pl');
    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');
});

afterEach(function (): void {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

test('public test category page renders dedicated category content and question preview', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '469',
            'prompt' => 'Czy kierujący powinien zachować szczególną ostrożność?',
        ]);

    $url = route('public.tests.category', ['categorySlug' => 'b']);

    $this->get($url)
        ->assertOk()
        ->assertSeeText('Testy na prawo jazdy kat. B '.now('Europe/Warsaw')->year)
        ->assertSeeText('Jak wygląda egzamin teoretyczny na prawo jazdy kat. B?')
        ->assertSeeText('32')
        ->assertSeeText('25 minut')
        ->assertSeeText('68 punktów')
        ->assertSeeText('Co można prowadzić z prawem jazdy kategorii B?')
        ->assertSeeText('Jak skutecznie przygotować się do teorii kat. B?')
        ->assertSeeText('Bez zakładania konta możesz wypróbować publiczny zestaw')
        ->assertSeeText('Czy kierujący powinien zachować szczególną ostrożność?')
        ->assertSee('Testy na prawo jazdy kat. B '.now('Europe/Warsaw')->year.' – pytania egzaminacyjne WORD | PrawkoNaRaz.pl')
        ->assertSee('<link rel="canonical" href="'.$url.'">', false)
        ->assertSee('"@type":"FAQPage"', false);
});

test('category C has its own guide, metadata and specialist FAQ', function () {
    $category = LicenseCategory::factory()->withCode('C')->create([
        'name' => 'Kategoria C',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'c-469',
            'prompt' => 'Jak zabezpieczyć ładunek w samochodzie ciężarowym?',
        ]);

    $url = route('public.tests.category', ['categorySlug' => 'c']);

    $this->get($url)
        ->assertOk()
        ->assertSeeText('Testy na prawo jazdy kat. C '.now('Europe/Warsaw')->year)
        ->assertSeeText('Czym różni się kategoria C od C+E?')
        ->assertSeeText('Prawo jazdy kat. C a kierowca zawodowy i kod 95')
        ->assertSeeText('Jak zabezpieczyć ładunek w samochodzie ciężarowym?')
        ->assertSeeText('Publiczny zestaw próbny na stronie testów zawiera')
        ->assertSee('pytania egzaminacyjne WORD | PrawkoNaRaz.pl')
        ->assertSee('<link rel="canonical" href="'.$url.'">', false)
        ->assertSee('"@type":"FAQPage"', false);
});

test('category D has its own guide, metadata and updated age rules', function () {
    $category = LicenseCategory::factory()->withCode('D')->create([
        'name' => 'Kategoria D',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'd-469',
            'prompt' => 'Jak bezpiecznie zatrzymać autobus na przystanku?',
        ]);

    $url = route('public.tests.category', ['categorySlug' => 'd']);

    $this->get($url)
        ->assertOk()
        ->assertSeeText('Testy na prawo jazdy kat. D '.now('Europe/Warsaw')->year)
        ->assertSeeText('Czym różni się kategoria D od D+E?')
        ->assertSeeText('3 września 2026 r.')
        ->assertSeeText('Jak bezpiecznie zatrzymać autobus na przystanku?')
        ->assertSeeText('Prawo jazdy kat. D a kwalifikacja zawodowa i kod 95')
        ->assertSeeText('Publiczny zestaw próbny na stronie testów zawiera')
        ->assertSee('pytania egzaminacyjne WORD | PrawkoNaRaz.pl')
        ->assertSee('<link rel="canonical" href="'.$url.'">', false)
        ->assertSee('"@type":"FAQPage"', false);
});

test('category T has its own guide, metadata and agricultural vehicle FAQ', function () {
    $category = LicenseCategory::factory()->withCode('T')->create([
        'name' => 'Kategoria T',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 't-469',
            'prompt' => 'Jak bezpiecznie połączyć ciągnik rolniczy z przyczepą?',
        ]);

    $url = route('public.tests.category', ['categorySlug' => 't']);

    $this->get($url)
        ->assertOk()
        ->assertSeeText('Testy na prawo jazdy kat. T '.now('Europe/Warsaw')->year)
        ->assertSeeText('Kategoria T a kategoria B — jaka jest różnica przy ciągniku?')
        ->assertSeeText('Czy kategorią T można prowadzić quada lub kombajn?')
        ->assertSeeText('Jak bezpiecznie połączyć ciągnik rolniczy z przyczepą?')
        ->assertSeeText('Publiczny zestaw próbny na stronie testów zawiera')
        ->assertSee('pytania egzaminacyjne WORD | PrawkoNaRaz.pl')
        ->assertSee('<link rel="canonical" href="'.$url.'">', false)
        ->assertSee('"@type":"FAQPage"', false);
});

test('category A1 has its own motorcycle guide, metadata and FAQ', function () {
    $category = LicenseCategory::factory()->withCode('A1')->create([
        'name' => 'Kategoria A1',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'a1-469',
            'prompt' => 'Jak bezpiecznie hamować motocyklem na mokrej drodze?',
        ]);

    $url = route('public.tests.category', ['categorySlug' => 'a1']);

    $this->get($url)
        ->assertOk()
        ->assertSeeText('Testy na prawo jazdy kat. A1 '.now('Europe/Warsaw')->year)
        ->assertSeeText('Masz kategorię B? Kiedy możesz jeździć motocyklem 125 cm³?')
        ->assertSeeText('A1, AM, A2 czy A — czym się różnią?')
        ->assertSeeText('Jak bezpiecznie hamować motocyklem na mokrej drodze?')
        ->assertSeeText('Publiczny zestaw próbny na stronie testów zawiera')
        ->assertSee('pytania egzaminacyjne WORD | PrawkoNaRaz.pl')
        ->assertSee('<link rel="canonical" href="'.$url.'">', false)
        ->assertSee('"@type":"FAQPage"', false);
});

test('category AM has its own moped guide, metadata and FAQ', function () {
    $category = LicenseCategory::factory()->withCode('AM')->create([
        'name' => 'Kategoria AM',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'am-469',
            'prompt' => 'Jak bezpiecznie włączyć się do ruchu na motorowerze?',
        ]);

    $url = route('public.tests.category', ['categorySlug' => 'am']);

    $this->get($url)
        ->assertOk()
        ->assertSeeText('Testy na prawo jazdy kat. AM '.now('Europe/Warsaw')->year)
        ->assertSeeText('Motorower i skuter na kategorię AM')
        ->assertSeeText('Czterokołowiec lekki i mały quad na AM')
        ->assertSeeText('Jak bezpiecznie włączyć się do ruchu na motorowerze?')
        ->assertSeeText('Publiczny zestaw próbny na stronie testów zawiera')
        ->assertSee('pytania egzaminacyjne WORD | PrawkoNaRaz.pl')
        ->assertSee('<link rel="canonical" href="'.$url.'">', false)
        ->assertSee('"@type":"FAQPage"', false);
});

test('all supported public test category routes render their own category heading', function () {
    foreach ([
        'a' => 'A',
        'b' => 'B',
        'c' => 'C',
        'd' => 'D',
        't' => 'T',
        'a1' => 'A1',
        'am' => 'AM',
    ] as $slug => $code) {
        $category = LicenseCategory::factory()->withCode($code)->create([
            'sort_order' => 1,
        ]);

        Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => 'test-'.$slug,
                'prompt' => 'Przykładowe pytanie dla kategorii '.$code,
            ]);

        $this->get(route('public.tests.category', ['categorySlug' => $slug]))
            ->assertOk()
            ->assertSeeText('Testy na prawo jazdy kat. '.$code);
    }
});

test('homepage test menu points category links to dedicated test category pages', function () {
    $items = app(PublicNavigation::class)->data(null)['test_menu'];

    expect(collect($items)->firstWhere('label', 'Kategoria A')['href'])
        ->toBe('/testy-na-prawo-jazdy/kategoria-a')
        ->and(collect($items)->firstWhere('label', 'Kategoria B')['href'])
        ->toBe('/testy-na-prawo-jazdy/kategoria-b')
        ->and(collect($items)->firstWhere('label', 'Kategoria AM')['href'])
        ->toBe('/testy-na-prawo-jazdy/kategoria-am');
});

test('static sitemap includes all dedicated test category pages', function () {
    foreach ([
        'a',
        'b',
        'c',
        'd',
        't',
        'a1',
        'am',
    ] as $slug) {
        LicenseCategory::factory()->withCode(strtoupper($slug))->create();
    }

    $urls = collect(app(SeoSitemapBuilder::class)->staticUrls())->pluck('loc');

    foreach (['a', 'b', 'c', 'd', 't', 'a1', 'am'] as $slug) {
        expect($urls)->toContain(route('public.tests.category', ['categorySlug' => $slug]));
    }
});

test('unsupported test category route returns not found', function () {
    $this->get('/testy-na-prawo-jazdy/kategoria-x')
        ->assertNotFound();
});
