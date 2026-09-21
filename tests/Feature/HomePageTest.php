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
        ->assertSeeText('Rozpocznij darmowy test')
        ->assertSeeText('Rozpocznij naukę za darmo')
        ->assertSeeText('Kontynuuj z Google')
        ->assertSee('data-home-site-header', false)
        ->assertSee('data-home-header-menu', false)
        ->assertSee('class="home-site-header is-visible', false)
        ->assertSee('aria-label="Otwórz menu serwisu"', false)
        ->assertSeeText('Cała nauka')
        ->assertSeeText('Każdy błąd')
        ->assertSeeText('Nie każdy uczy się tak samo.')
        ->assertSeeText('Dwa tryby widoku')
        ->assertSeeText('Tryb klasyczny')
        ->assertSeeText('Tryb skupienia')
        ->assertSeeText('Ty decydujesz, jak przebiega sesja.')
        ->assertSeeText('Skróty klawiaturowe')
        ->assertSeeText('To środowisko nauki, które dopasowuje się do Twojego tempa, sposobu zapamiętywania i poziomu skupienia.')
        ->assertSee('alt="Tryb klasyczny nauki z panelem postępu i ustawieniami sesji"', false)
        ->assertSee('alt="Tryb skupienia z pytaniem i ograniczoną liczbą elementów interfejsu"', false)
        ->assertSee('mobile-app-banner', false)
        ->assertSee('alt="Aplikacja mobilna PrawkoNaRaz dostępna w Google Play, App Store i AppGallery"', false)
        ->assertSeeText('Pobierz aplikację mobilną')
        ->assertSeeText('i ucz się teorii')
        ->assertSeeText('Setki pytań, realne testy i pełna wygoda.')
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
        ->assertSeeText('Oficjalne źródła')
        ->assertSee('alt="Herb Rzeczypospolitej Polskiej"', false)
        ->assertSee('alt="Ministerstwo Infrastruktury"', false)
        ->assertSee('alt="CEPiK — Centralna Ewidencja Pojazdów i Kierowców"', false)
        ->assertSee('alt="ELI — Europejski Identyfikator Prawodawstwa"', false)
        ->assertSee('images/partners/herb-polski.svg', false)
        ->assertSee('images/partners/ministerstwo-infrastruktury.png', false)
        ->assertSee('images/partners/cepik-gov.png', false)
        ->assertSee('images/partners/eli.png', false)
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
        ->assertSee('images/site-brand-mark-shield-v2.png', false)
        ->assertDontSee('Orły na Drodze', false)
        ->assertSee('data-home-contact-dialog', false)
        ->assertDontSeeText('Wybierz dostęp i zacznij naukę');
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
