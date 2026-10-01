<?php

use App\Support\PaymentRequirementService;

afterEach(function (): void {
    app(PaymentRequirementService::class)->forgetCachedRequirement();
});

test('homepage uses the canonical site identity graph and social site name', function () {
    config()->set('app.name', 'prawkonaraz.pl');
    config()->set('content.organization.name', 'PrawkoNaRaz');
    config()->set('content.organization.logo_url', '/favicon.png');
    config()->set('content.organization.logo_width', 256);
    config()->set('content.organization.logo_height', 256);

    $root = rtrim(url('/'), '/');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('meta property="og:site_name" content="PrawkoNaRaz"', false)
        ->assertSee('"@id":"'.$root.'/#organization"', false)
        ->assertSee('"@id":"'.$root.'/#website"', false)
        ->assertSee('"name":"PrawkoNaRaz"', false)
        ->assertSee('"alternateName":"prawkonaraz.pl"', false)
        ->assertSee('"url":"'.$root.'/favicon.png","contentUrl":"'.$root.'/favicon.png","width":256,"height":256', false)
        ->assertSee('"isPartOf":{"@id":"'.$root.'/#website"}', false)
        ->assertSee('"publisher":{"@id":"'.$root.'/#organization"}', false)
        ->assertDontSee('Orły na Drodze', false);
});

test('home page renders the public landing page', function () {
    $seoYear = now('Europe/Warsaw')->format('Y');

    $this->get('/')
        ->assertOk()
        ->assertSeeText("Testy na prawo jazdy {$seoYear}")
        ->assertSeeText('Ucz się szybko')
        ->assertSeeText('prawko na raz!')
        ->assertSeeText('Rozpocznij naukę za darmo')
        ->assertSeeText('Kontynuuj z Google')
        ->assertSee('data-home-site-header', false)
        ->assertSee('data-home-header-menu', false)
        ->assertSee('class="home-site-header home-site-header--reference is-visible', false)
        ->assertSee('aria-label="Otwórz menu serwisu"', false)
        ->assertSeeText('Zobacz jak się uczyć szybko!')
        ->assertDontSeeText('Nauka w praktyce')
        ->assertDontSee('home-entry__source-arrow', false)
        ->assertSeeText('Własne filmy PrawkoNaRaz pojawią się tutaj wkrótce.')
        ->assertSee('data-home-learning-videos', false)
        ->assertSee('data-home-learning-video-dialog', false)
        ->assertSee('home-showcase-videos__card--empty', false)
        ->assertDontSee('data-home-learning-video-id=', false)
        ->assertSeeText('Wybierz swój sposób nauki')
        ->assertSeeText('Tryb skupienia')
        ->assertSeeText('Ty decydujesz, jak przebiega sesja')
        ->assertSeeText('Skróty klawiaturowe')
        ->assertSeeText('To środowisko nauki, które dopasowuje się do Twojego tempa, sposobu zapamiętywania i poziomu skupienia.')
        ->assertSee('alt="Tryb klasyczny nauki z panelem postępu i ustawieniami sesji"', false)
        ->assertSee('alt="Tryb skupienia z pytaniem i ograniczoną liczbą elementów interfejsu"', false)
        ->assertDontSee('class="home-mobile-app-banner"', false)
        ->assertDontSeeText('Pobierz aplikację mobilną')
        ->assertDontSeeText('Błąd zostaje tam, gdzie możesz do niego wrócić.')
        ->assertDontSeeText('Na końcu próbny egzamin.')
        ->assertSeeText('Opinie użytkowników PrawkoNaRaz')
        ->assertSeeText('Zobacz wszystkie opinie')
        ->assertSeeText('Zaloguj się i dodaj opinię')
        ->assertSeeText('Najczęściej zadawane pytania - FAQ')
        ->assertSeeText('Czym jest PrawkoNaRaz?')
        ->assertSeeText('Jak działa nauka w PrawkoNaRaz?')
        ->assertSeeText('Czy PrawkoNaRaz korzysta z oficjalnej bazy pytań?')
        ->assertSeeText('Czy PrawkoNaRaz jest darmowe?')
        ->assertSeeText('Czy PrawkoNaRaz zapisuje postęp i błędne odpowiedzi?')
        ->assertSeeText('Ile pytań jest na egzaminie teoretycznym na prawo jazdy?')
        ->assertSeeText('Ile punktów trzeba mieć, żeby zdać egzamin teoretyczny na prawo jazdy?')
        ->assertSeeText('Ile błędów można zrobić na egzaminie teoretycznym na prawo jazdy?')
        ->assertSeeText('Czy pytania na egzaminie na prawo jazdy są takie same jak w oficjalnej bazie?')
        ->assertSeeText('Ile jest pytań w bazie na prawo jazdy kat. B?')
        ->assertSeeText('Jak wygląda egzamin teoretyczny na prawo jazdy?')
        ->assertSeeText('Ile trwa egzamin teoretyczny na prawo jazdy?')
        ->assertSeeText('Jak zdać teorię na prawo jazdy za pierwszym razem?')
        ->assertSeeText('Jak szybko nauczyć się pytań na prawo jazdy?')
        ->assertSeeText('Czy wszystkie pytania na egzaminie pochodzą z oficjalnej bazy Ministerstwa Infrastruktury?')
        ->assertSeeText('Czy pytania na egzaminie na prawo jazdy się powtarzają?')
        ->assertSeeText('Czy baza pytań na prawo jazdy jest jawna?')
        ->assertDontSee('🔥', false)
        ->assertDontSee('class="home-trust"', false)
        ->assertDontSeeText('Oficjalne źródła')
        ->assertDontSeeText('Zgodność platformy z wymaganiami e-learningu OSK')
        ->assertDontSeeText('Nadzór OSK nad szkoleniem')
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
        ->assertSee("Testy na prawo jazdy {$seoYear} z oficjalnej bazy pytań. Rozwiąż darmowy test: 20 pytań bez logowania. Ucz się z wyjaśnieniami do egzaminu teoretycznego.", false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertDontSee('Orły na Drodze', false)
        ->assertSee('data-home-contact-dialog', false)
        ->assertDontSeeText('Wybierz dostęp i zacznij naukę');
});

test('top navigation renders grouped mega menus with category and sign links', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-home-header-mega', false)
        ->assertSee('data-mega-tab="2"', false)
        ->assertSeeText('Motocykle i motorowery')
        ->assertSeeText('Sygnały i kontrolki')
        ->assertSee('href="/testy-na-prawo-jazdy/kategoria-am"', false)
        ->assertSee('href="/znaki-drogowe/kategorie/sygnaly-swietlne"', false)
        ->assertSee('class="home-site-header__mobile-catalog"', false);
});

test('open access mode hides public friend invitation entry points', function () {
    app(PaymentRequirementService::class)->setRequiresPayment(false);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Wpisz kod zaproszenia');

    $this->get(route('about.how-it-works'))
        ->assertRedirect(route('public.tests', absolute: false));
});
