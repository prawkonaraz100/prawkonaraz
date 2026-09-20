<?php

use App\Models\LegalContentPage;
use App\Models\LegalUnit;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use Database\Seeders\LegalTrustLayerMvpSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('content.organization.name', 'PrawkoNaRaz');
    config()->set('content.organization.legal_name', null);
    config()->set('content.organization.logo_url', 'https://prawkonaraz.pl/favicon.png');
    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');

    File::delete(public_path('sitemap.xml'));

    foreach (File::glob(public_path('sitemaps/*.xml')) ?: [] as $file) {
        File::delete($file);
    }
});

afterEach(function (): void {
    URL::forceRootUrl(null);
    URL::forceScheme(null);

    File::delete(public_path('sitemap.xml'));

    foreach (File::glob(public_path('sitemaps/*.xml')) ?: [] as $file) {
        File::delete($file);
    }
});

/**
 * @return list<array<string, mixed>>
 */
function legalContentJsonLdScripts(TestResponse $response): array
{
    preg_match_all(
        '/<script type="application\/ld\+json">(.+?)<\/script>/s',
        $response->getContent(),
        $matches,
    );

    return collect($matches[1] ?? [])
        ->map(fn (string $json): mixed => json_decode($json, true))
        ->filter(fn (mixed $schema): bool => is_array($schema))
        ->values()
        ->all();
}

/**
 * @return list<array<string, mixed>>
 */
function legalContentJsonLdGraph(TestResponse $response): array
{
    $scripts = legalContentJsonLdScripts($response);

    expect($scripts)->toHaveCount(1);
    expect($scripts[0]['@context'] ?? null)->toBe('https://schema.org');
    expect($scripts[0]['@graph'] ?? null)->toBeArray();

    return $scripts[0]['@graph'];
}

/**
 * @return array<string, mixed>|null
 */
function legalContentGraphNode(TestResponse $response, string $type): ?array
{
    return collect(legalContentJsonLdGraph($response))
        ->first(fn (mixed $node): bool => is_array($node) && ($node['@type'] ?? null) === $type);
}

/**
 * @return list<string>
 */
function legalContentGraphDuplicateIds(TestResponse $response): array
{
    return collect(legalContentJsonLdGraph($response))
        ->pluck('@id')
        ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
        ->countBy()
        ->filter(fn (int $count): bool => $count > 1)
        ->keys()
        ->values()
        ->all();
}

test('public regulations hub and detail pages render verified legal content', function () {
    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.regulations'))
        ->assertOk()
        ->assertSeeText('Poznaj przepisy drogowe bez prawniczego języka')
        ->assertSeeText('Zatrzymanie i postój pojazdu')
        ->assertSeeText('Tramwaje, przystanki i bezpieczeństwo pasażerów')
        ->assertSeeText('Piesi i przejścia dla pieszych')
        ->assertSeeText('Pierwszeństwo przejazdu na skrzyżowaniu')
        ->assertSeeText('Sygnalizacja i osoby kierujące ruchem')
        ->assertSeeText('Prędkość, odstęp i hamowanie pojazdu')
        ->assertSeeText('Zmiana kierunku i pasa ruchu')
        ->assertSeeText('Włączanie się do ruchu')
        ->assertSeeText('Wymijanie, omijanie i cofanie')
        ->assertSeeText('Wyprzedzanie')
        ->assertSeeText('Autobus wyjeżdżający z przystanku')
        ->assertSeeText('Zawracanie: gdzie wolno, znaki i sygnalizacja')
        ->assertSeeText('Sygnał dźwiękowy: kiedy wolno użyć klaksonu')
        ->assertSeeText('Zabezpieczenie ładunku: mocowanie, wymiary i oznakowanie')
        ->assertSeeText('Przyczepa: masa, wymiary i obowiązkowe oświetlenie')
        ->assertSeeText('Przejazd kolejowy: kiedy nie wolno wjechać na tory')
        ->assertSeeText('Holowanie pojazdu: prędkość, oznakowanie i zakazy')
        ->assertSeeText('Pasy bezpieczeństwa: kto musi zapinać i jakie są wyjątki')
        ->assertSeeText('Światła mijania i dzienne: kiedy których używać')
        ->assertSeeText('Rogatki i czerwone światło: kiedy wolno ruszyć')
        ->assertSeeText('Światła przeciwmgłowe: przednie, tylne i granica 50 metrów')
        ->assertSeeText('Przewóz dzieci: fotelik, wzrost 150 cm i wyjątki')
        ->assertSeeText('Wypadek drogowy: obowiązki uczestnika krok po kroku')
        ->assertSeeText('Dokumenty podczas kontroli drogowej: co trzeba mieć przy sobie')
        ->assertSeeText('Kategorie prawa jazdy: czym możesz kierować')
        ->assertSeeText('Alkohol za kierownicą: limity, zakaz i odpowiedzialność')
        ->assertSeeText('Zielona strzałka warunkowa: zatrzymanie i pierwszeństwo')
        ->assertSeeText('Pojazd uprzywilejowany i korytarz życia: jak ustąpić')
        ->assertSeeText('Żółte światło: kiedy się zatrzymać i co oznacza miganie')
        ->assertSeeText('Jak weryfikujemy treści?');

    $this->get(route('public.regulations.show', 'zatrzymanie-i-postoj'))
        ->assertOk()
        ->assertSeeText('Zatrzymanie i postój pojazdu')
        ->assertSee('images/authors/katarzyna-wisniewska.png', false)
        ->assertSee('images/authors/jakub-wisniewski.png', false)
        ->assertSeeText('samo włączenie świateł awaryjnych nie zamienia dowolnego miejsca w legalny postój')
        ->assertSeeText('Ciągła linia krawędziowa wyklucza zatrzymanie zarówno na jezdni, jak i na poboczu.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 49')
        ->assertSeeText('art. 49 ust. 1 pkt 5')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'tramwaje-i-przystanki'))
        ->assertOk()
        ->assertSeeText('Tramwaje, przystanki i bezpieczeństwo pasażerów')
        ->assertSeeText('Jeżeli pasażerowie muszą przejść przez jezdnię, to Twoja decyzja ma chronić ich ruch')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('Prawo o ruchu drogowym')
        ->assertSeeText('art. 26 ust. 6')
        ->assertSeeText('Stan prawny na dzień')
        ->assertSeeText('15.06.2026')
        ->assertDontSeeText('Ostatni review')
        ->assertSeeText('Charakter materiału')
        ->assertSeeText('15.06.2026');

    $this->get(route('public.regulations.show', 'piesi-i-przejscia'))
        ->assertOk()
        ->assertSeeText('Piesi i przejścia dla pieszych')
        ->assertSeeText('Brak pieszego bezpośrednio przed pojazdem nie kończy analizy')
        ->assertSeeText('Wjeżdżając do bramy przez drogę dla pieszych, jedź powoli i ustąp pieszemu.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 13')
        ->assertSeeText('art. 26')
        ->assertSeeText('19.06.2026');

    $this->get(route('public.regulations.show', 'pierwszenstwo-przejazdu'))
        ->assertOk()
        ->assertSeeText('Pierwszeństwo przejazdu na skrzyżowaniu')
        ->assertSeeText('samo posiadanie pierwszeństwa nie oznacza prawa do blokowania przejazdu')
        ->assertSeeText('Zestaw A-7 i C-12 daje pierwszeństwo pojazdom znajdującym się już na rondzie.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 25 ust. 1')
        ->assertSeeText('§ 36 ust. 2')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'sygnalizacja-i-osoby-kierujace-ruchem'))
        ->assertOk()
        ->assertSeeText('Sygnalizacja i osoby kierujące ruchem')
        ->assertSeeText('nie wolno zatrzymać się na pierwszym zauważonym znaku')
        ->assertSeeText('Jednoczesny sygnał czerwony i żółty nadal zakazuje wjazdu.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 5')
        ->assertSeeText('§ 95 ust. 1 pkt 4')
        ->assertSeeText('§ 108 ust. 2')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'predkosc-odstep-i-hamowanie'))
        ->assertOk()
        ->assertSeeText('Prędkość, odstęp i hamowanie pojazdu')
        ->assertSeeText('Prędkość bezpieczna nie jest drugim, niezależnym limitem.')
        ->assertSeeText('Nie jest to stałe 100 m')
        ->assertSeeText('Limit 140 km/h na autostradzie nie jest przypisany wyłącznie do lewego pasa.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 19 ust. 1')
        ->assertSeeText('art. 19 ust. 2 pkt 3')
        ->assertSeeText('art. 19 ust. 3a')
        ->assertSeeText('art. 20 ust. 2')
        ->assertSeeText('art. 20 ust. 3 pkt 1 lit. a')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'zmiana-kierunku-i-pasa-ruchu'))
        ->assertOk()
        ->assertSeeText('Zmiana kierunku i pasa ruchu')
        ->assertSeeText('Kierunkowskaz informuje o zamiarze, ale nie daje pierwszeństwa.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 22 ust. 1')
        ->assertSeeText('art. 22 ust. 2 pkt 2')
        ->assertSeeText('art. 22 ust. 5')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'wlaczanie-sie-do-ruchu'))
        ->assertOk()
        ->assertSeeText('Włączanie się do ruchu')
        ->assertSeeText('Ruszenie po zielonym świetle nie jest włączaniem się do ruchu')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 17 ust. 1')
        ->assertSeeText('art. 17 ust. 1 pkt 1')
        ->assertSeeText('art. 17 ust. 2')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'wymijanie-omijanie-cofanie'))
        ->assertOk()
        ->assertSeeText('Wymijanie, omijanie i cofanie')
        ->assertSeeText('Cofanie nie jest dozwolone wszędzie, nawet przy zachowaniu ostrożności.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 23 ust. 1 pkt 1')
        ->assertSeeText('art. 23 ust. 1 pkt 2')
        ->assertSeeText('art. 23 ust. 1 pkt 3')
        ->assertSeeText('art. 23 ust. 2')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'wyprzedzanie'))
        ->assertOk()
        ->assertSeeText('Wyprzedzanie')
        ->assertSeeText('Nie każdy pojazd ma więc ustawowe minimum 1 m, ale każdy wymaga odstępu bezpiecznego.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 24 ust. 1 pkt 2')
        ->assertSeeText('art. 24 ust. 2')
        ->assertSeeText('art. 24 ust. 6')
        ->assertSeeText('art. 24 ust. 7 pkt 1')
        ->assertSeeText('art. 24 ust. 7 pkt 3')
        ->assertSeeText('art. 24 ust. 11')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'autobus-wyjezdzajacy-z-przystanku'))
        ->assertOk()
        ->assertSeeText('Autobus wyjeżdżający z przystanku')
        ->assertSee('images/legal/articles/autobus-wyjezdzajacy-z-przystanku.webp', false)
        ->assertSee('Autobus wyjeżdżający z zatoki przystankowej na jezdnię w obszarze zabudowanym', false)
        ->assertSeeText('Obowiązki obu kierujących działają równocześnie.')
        ->assertSeeText('Poza obszarem zabudowanym szczególny obowiązek z art. 18 ust. 1 nie obowiązuje.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 18 ust. 1')
        ->assertSeeText('art. 18 ust. 2')
        ->assertSeeText('19.06.2026');

    $this->get(route('public.regulations.show', 'zawracanie'))
        ->assertOk()
        ->assertSeeText('Zawracanie: gdzie wolno, znaki i sygnalizacja')
        ->assertSee('images/legal/articles/zawracanie.webp', false)
        ->assertSee('Samochód wykonujący zawracanie z lewego pasa na szerokim skrzyżowaniu miejskim', false)
        ->assertSeeText('Znak B-21 zabrania nie tylko skrętu w lewo, ale także zawracania.')
        ->assertSeeText('P-8b na skrajnym lewym pasie co do zasady pozwala zawrócić, chyba że zabrania tego B-23 albo ruchem kieruje S-3.')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('art. 22 ust. 6 pkt 1')
        ->assertSeeText('art. 22 ust. 6 pkt 2')
        ->assertSeeText('art. 22 ust. 6 pkt 3')
        ->assertSeeText('art. 22 ust. 6 pkt 4')
        ->assertSeeText('art. 25 ust. 2')
        ->assertSeeText('§ 22 ust. 1-2')
        ->assertSeeText('§ 22 ust. 5')
        ->assertSeeText('§ 87 ust. 2')
        ->assertSeeText('§ 97 ust. 1')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'sygnal-dzwiekowy'))
        ->assertOk()
        ->assertSeeText('Sygnał dźwiękowy: kiedy wolno użyć klaksonu')
        ->assertSee('images/legal/articles/sygnal-dzwiekowy.webp', false)
        ->assertSee('Kierowca ostrzegający sygnałem dźwiękowym pieszego wchodzącego na jezdnię', false)
        ->assertSeeText('Klakson nie służy do ponaglania')
        ->assertSeeText('Poza obszarem zabudowanym podczas mgły')
        ->assertSeeText('art. 29 ust. 1')
        ->assertSeeText('art. 29 ust. 2 pkt 1')
        ->assertSeeText('art. 29 ust. 2 pkt 2')
        ->assertSeeText('art. 30 ust. 1 pkt 1 lit. b')
        ->assertSeeText('§ 11 ust. 1 pkt 6')
        ->assertSeeText('20.06.2026');

    $this->get(route('public.regulations.show', 'zabezpieczenie-ladunku-i-wymiary'))
        ->assertOk()
        ->assertSeeText('Zabezpieczenie ładunku: mocowanie, wymiary i oznakowanie')
        ->assertSee('images/legal/articles/zabezpieczenie-ladunku-i-wymiary.webp', false)
        ->assertSee('Ładunek na paletach prawidłowo zabezpieczony pasami w samochodzie ciężarowym', false)
        ->assertSeeText('Ładunek nie może przeciążać pojazdu')
        ->assertSeeText('art. 61 ust. 1')
        ->assertSeeText('art. 61 ust. 3')
        ->assertSeeText('art. 61 ust. 6 pkt 2')
        ->assertSeeText('art. 61 ust. 9 pkt 4')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'przyczepa-masa-wymiary-i-oswietlenie'))
        ->assertOk()
        ->assertSeeText('Przyczepa: masa, wymiary i obowiązkowe oświetlenie')
        ->assertSee('images/legal/articles/przyczepa-masa-wymiary-i-oswietlenie.webp', false)
        ->assertSee('Samochód osobowy ciągnący przyczepę kempingową na drodze', false)
        ->assertSeeText('Rzeczywista masa przyczepy musi odpowiadać rodzajowi pojazdu ciągnącego')
        ->assertSeeText('art. 62 ust. 1 pkt 1')
        ->assertSeeText('art. 62 ust. 4a pkt 1')
        ->assertSeeText('art. 62 ust. 4a pkt 2')
        ->assertSeeText('§ 12 ust. 1 pkt 11')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'wjazd-na-przejazd-kolejowy'))
        ->assertOk()
        ->assertSeeText('Przejazd kolejowy: kiedy nie wolno wjechać na tory')
        ->assertSee('images/legal/articles/wjazd-na-przejazd-kolejowy.webp', false)
        ->assertSee('Samochód zatrzymany przed opuszczonymi półzaporami na przejeździe kolejowym', false)
        ->assertSeeText('Czerwony sygnał migający nadal zakazuje wjazdu')
        ->assertSeeText('art. 28 ust. 1')
        ->assertSeeText('art. 28 ust. 3 pkt 1')
        ->assertSeeText('art. 28 ust. 3 pkt 2')
        ->assertSeeText('art. 28 ust. 3 pkt 3')
        ->assertSeeText('§ 21 ust. 1 i 4')
        ->assertSeeText('§ 98 ust. 5')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'holowanie-pojazdu'))
        ->assertOk()
        ->assertSeeText('Holowanie pojazdu: prędkość, oznakowanie i zakazy')
        ->assertSee('images/legal/articles/holowanie-pojazdu.webp', false)
        ->assertSee('Samochód osobowy holujący drugi pojazd za pomocą oznaczonego połączenia giętkiego', false)
        ->assertSeeText('Pojazd holujący musi mieć włączone światła mijania')
        ->assertSeeText('art. 31 ust. 1 pkt 1')
        ->assertSeeText('art. 31 ust. 1 pkt 5')
        ->assertSeeText('art. 31 ust. 2 pkt 5')
        ->assertSeeText('art. 45 ust. 1 pkt 2')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'pasy-bezpieczenstwa'))
        ->assertOk()
        ->assertSeeText('Pasy bezpieczeństwa: kto musi zapinać i jakie są wyjątki')
        ->assertSee('images/legal/articles/pasy-bezpieczenstwa.webp', false)
        ->assertSee('Kierowca i pasażer na tylnym siedzeniu prawidłowo korzystający z pasów bezpieczeństwa', false)
        ->assertSeeText('Tylna kanapa nie jest strefą zwolnioną')
        ->assertSeeText('art. 39 ust. 1')
        ->assertSeeText('art. 39 ust. 2 pkt 2')
        ->assertSeeText('art. 39 ust. 2 pkt 10')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'swiatla-do-jazdy-dziennej-i-mijania'))
        ->assertOk()
        ->assertSeeText('Światła mijania i dzienne: kiedy których używać')
        ->assertSee('images/legal/articles/swiatla-do-jazdy-dziennej-i-mijania.webp', false)
        ->assertSee('Samochód jadący w dzień z włączonymi światłami do jazdy dziennej', false)
        ->assertSeeText('Obowiązek działa przez cały rok')
        ->assertSeeText('art. 51 ust. 1')
        ->assertSeeText('art. 51 ust. 2')
        ->assertSeeText('art. 30 ust. 1 pkt 1 lit. a')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'rogatki-i-sygnaly-na-przejezdzie-kolejowym'))
        ->assertOk()
        ->assertSeeText('Rogatki i czerwone światło: kiedy wolno ruszyć')
        ->assertSee('images/legal/articles/rogatki-i-sygnaly-na-przejezdzie-kolejowym.webp', false)
        ->assertSee('Samochód oczekujący przed częściowo podniesionymi rogatkami i działającym czerwonym sygnałem', false)
        ->assertSeeText('Czerwony sygnał migający jest oddzielnym zakazem')
        ->assertSeeText('art. 28 ust. 1')
        ->assertSeeText('art. 28 ust. 3 pkt 1')
        ->assertSeeText('§ 98 ust. 5')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'swiatla-przeciwmglowe'))
        ->assertOk()
        ->assertSeeText('Światła przeciwmgłowe: przednie, tylne i granica 50 metrów')
        ->assertSee('images/legal/articles/swiatla-przeciwmglowe.webp', false)
        ->assertSee('Samochód z włączonymi przednimi światłami przeciwmgłowymi na drodze we mgle', false)
        ->assertSeeText('Tylne światła przeciwmgłowe mają odrębną, liczbową granicę')
        ->assertSeeText('art. 30 ust. 1 pkt 1 lit. a')
        ->assertSeeText('art. 30 ust. 3')
        ->assertSeeText('art. 51 ust. 5')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'foteliki-i-przewoz-dzieci'))
        ->assertOk()
        ->assertSeeText('Przewóz dzieci: fotelik, wzrost 150 cm i wyjątki')
        ->assertSee('images/legal/articles/foteliki-i-przewoz-dzieci.webp', false)
        ->assertSee('Rodzic prawidłowo zapinający dziecko w foteliku na tylnym siedzeniu samochodu', false)
        ->assertSeeText('Na przednim siedzeniu dziecko mające mniej niż 150 cm')
        ->assertSeeText('art. 39 ust. 3')
        ->assertSeeText('art. 39 ust. 3c')
        ->assertSeeText('art. 45 ust. 2 pkt 4')
        ->assertSeeText('art. 45 ust. 2 pkt 6')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'obowiazki-uczestnika-wypadku'))
        ->assertOk()
        ->assertSeeText('Wypadek drogowy: obowiązki uczestnika krok po kroku')
        ->assertSee('images/legal/articles/obowiazki-uczestnika-wypadku.webp', false)
        ->assertSee('Uczestnicy zabezpieczający miejsce wypadku i wzywający pomoc dla poszkodowanego', false)
        ->assertSeeText('Najważniejsza granica przebiega między zdarzeniem bez osób rannych')
        ->assertSeeText('art. 44 ust. 1 pkt 2')
        ->assertSeeText('art. 44 ust. 2 pkt 1')
        ->assertSeeText('art. 44 ust. 2 pkt 3')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'telefon-podczas-kierowania'))
        ->assertOk()
        ->assertSeeText('Telefon za kierownicą: co dokładnie jest zabronione')
        ->assertSee('images/legal/articles/telefon-podczas-kierowania.webp', false)
        ->assertSee('Kierowca prowadzący samochód obiema rękami z telefonem umieszczonym w uchwycie', false)
        ->assertSeeText('Tryb głośnomówiący nie usuwa zakazu')
        ->assertSeeText('art. 45 ust. 2 pkt 1')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'swiatla-drogowe-i-oslepianie'))
        ->assertOk()
        ->assertSeeText('Światła drogowe: kiedy wolno ich używać i kiedy je wyłączyć')
        ->assertSee('images/legal/articles/swiatla-drogowe-i-oslepianie.webp', false)
        ->assertSee('Dwa samochody mijające się nocą bez oślepiania światłami', false)
        ->assertSeeText('Światła drogowe mogą oślepić także kierującego jadącego przed nami')
        ->assertSeeText('art. 51 ust. 3')
        ->assertSeeText('art. 51 ust. 4 pkt 1')
        ->assertSeeText('art. 51 ust. 4 pkt 2')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'gasnica-trojkat-i-obowiazkowe-wyposazenie'))
        ->assertOk()
        ->assertSeeText('Wyposażenie pojazdu: gaśnica, trójkąt i najczęstsze pułapki')
        ->assertSee('images/legal/articles/gasnica-trojkat-i-obowiazkowe-wyposazenie.webp', false)
        ->assertSee('Prawidłowo zamocowana gaśnica i trójkąt ostrzegawczy w bagażniku samochodu', false)
        ->assertSeeText('Gaśnica w pojeździe samochodowym ma znajdować się w miejscu łatwo dostępnym')
        ->assertSeeText('§ 11 ust. 1 pkt 14')
        ->assertSeeText('§ 12 ust. 3 pkt 11')
        ->assertSeeText('§ 40 ust. 2 pkt 8')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'opony-bieznik-i-cisnienie'))
        ->assertOk()
        ->assertSeeText('Opony: minimalny bieżnik, ciśnienie i zgodność na osi')
        ->assertSee('images/legal/articles/opony-bieznik-i-cisnienie.webp', false)
        ->assertSee('Pomiar głębokości bieżnika i ciśnienia w oponie samochodu', false)
        ->assertSeeText('Ciśnienie oleju silnikowego i ciśnienie w oponach to odrębne zagadnienia')
        ->assertSeeText('§ 11 ust. 5')
        ->assertSeeText('§ 11 ust. 7 pkt 1')
        ->assertSeeText('§ 11 ust. 7 pkt 4')
        ->assertSeeText('21.06.2026');

    $this->get(route('public.regulations.show', 'dokumenty-podczas-kontroli-drogowej'))
        ->assertOk()
        ->assertSeeText('Dokumenty podczas kontroli drogowej: co trzeba mieć przy sobie')
        ->assertSee('images/legal/articles/dokumenty-podczas-kontroli-drogowej.webp', false)
        ->assertSee('Policjant kontrolujący dokumenty kierowcy przy samochodzie podczas kontroli drogowej', false)
        ->assertSeeText('W zwykłej jeździe pojazdem zarejestrowanym w Polsce nie ma ogólnego obowiązku wożenia krajowego prawa jazdy')
        ->assertSeeText('art. 38 ust. 1 pkt 1')
        ->assertSeeText('art. 38 ust. 1 pkt 4b lit. b')
        ->assertSeeText('art. 38 ust. 2')
        ->assertSeeText('22.06.2026');

    $this->get(route('public.regulations.show', 'kategorie-prawa-jazdy-i-uprawnienia'))
        ->assertOk()
        ->assertSeeText('Kategorie prawa jazdy: czym możesz kierować')
        ->assertSee('images/legal/articles/kategorie-prawa-jazdy-i-uprawnienia.webp', false)
        ->assertSee('Samochód, motocykl, czterokołowiec i ciągnik ilustrujące kategorie prawa jazdy', false)
        ->assertSeeText('Zakres kategorii wynika z parametrów pojazdu, a nie z jego potocznej nazwy')
        ->assertSeeText('art. 6 ust. 1 pkt 6 lit. a-b')
        ->assertSeeText('art. 6 ust. 3 pkt 4 lit. a')
        ->assertSeeText('art. 18 ust. 1')
        ->assertSeeText('22.06.2026');

    $this->get(route('public.regulations.show', 'alkohol-i-srodki-dzialajace-podobnie'))
        ->assertOk()
        ->assertSeeText('Alkohol za kierownicą: limity, zakaz i odpowiedzialność')
        ->assertSee('images/legal/articles/alkohol-i-srodki-dzialajace-podobnie.webp', false)
        ->assertSee('Kontrola trzeźwości kierowcy wykonywana przez policjanta przy zatrzymanym samochodzie', false)
        ->assertSeeText('Stan po użyciu alkoholu zachodzi, gdy stężenie wynosi od 0,2‰ do 0,5‰ we krwi')
        ->assertSeeText('art. 45 ust. 1 pkt 1')
        ->assertSeeText('art. 46 ust. 2')
        ->assertSeeText('art. 87 § 1')
        ->assertSeeText('art. 178a § 1')
        ->assertSeeText('22.06.2026');

    $this->get(route('public.regulations.show', 'zielona-strzalka-warunkowa'))
        ->assertOk()
        ->assertSeeText('Zielona strzałka warunkowa: zatrzymanie i pierwszeństwo')
        ->assertSee('images/legal/articles/zielona-strzalka-warunkowa.webp', false)
        ->assertSee('Samochód zatrzymany przed skrzyżowaniem przy czerwonym świetle i zielonej strzałce w prawo', false)
        ->assertSeeText('Pojazd musi rzeczywiście przestać się poruszać.')
        ->assertSeeText('§ 96 ust. 1')
        ->assertSeeText('§ 96 ust. 2')
        ->assertSeeText('§ 96 ust. 3')
        ->assertSeeText('22.06.2026');

    $this->get(route('public.regulations.show', 'pojazd-uprzywilejowany'))
        ->assertOk()
        ->assertSeeText('Pojazd uprzywilejowany i korytarz życia: jak ustąpić')
        ->assertSee('images/legal/articles/pojazd-uprzywilejowany.webp', false)
        ->assertSee('Korytarz życia utworzony na wielopasowej drodze dla nadjeżdżającego ambulansu', false)
        ->assertSeeText('Tylko pojazdy ze skrajnego lewego pasa zjeżdżają w lewo.')
        ->assertSeeText('art. 2 pkt 38')
        ->assertSeeText('art. 9 ust. 2 pkt 1')
        ->assertSeeText('art. 9 ust. 3-4')
        ->assertSeeText('art. 24 ust. 11')
        ->assertSeeText('§ 58 ust. 3')
        ->assertSeeText('22.06.2026');

    $this->get(route('public.regulations.show', 'sygnal-zolty-i-zolty-migajacy'))
        ->assertOk()
        ->assertSeeText('Żółte światło: kiedy się zatrzymać i co oznacza miganie')
        ->assertSee('images/legal/articles/sygnal-zolty-i-zolty-migajacy.webp', false)
        ->assertSee('Samochód ostrożnie zbliżający się do skrzyżowania z migającym żółtym sygnałem', false)
        ->assertSeeText('Żółty migający ma funkcję ostrzegawczą.')
        ->assertSeeText('§ 95 ust. 1 pkt 2')
        ->assertSeeText('§ 95 ust. 1 pkt 4')
        ->assertSeeText('§ 98 ust. 6')
        ->assertSeeText('22.06.2026');
});

test('every published legal article has a configured local header image', function () {
    $this->seed(LegalTrustLayerMvpSeeder::class);

    $articleImages = collect(config('content.legal_content.article_images', []));
    $publishedPages = LegalContentPage::query()
        ->published()
        ->orderBy('slug')
        ->get(['slug', 'title']);

    expect($publishedPages)->not->toBeEmpty();

    $publishedPages->each(function (LegalContentPage $page) use ($articleImages): void {
        $image = $articleImages->get($page->slug);

        expect($image)
            ->toBeArray()
            ->and($image['path'] ?? null)->toBeString()->not->toBeEmpty()
            ->and($image['alt'] ?? null)->toBeString()->not->toBeEmpty()
            ->and($image['width'] ?? null)->toBe(1400)
            ->and($image['height'] ?? null)->toBe(788)
            ->and(file_exists(public_path((string) ($image['path'] ?? ''))))->toBeTrue();
    });
});

test('public regulations hub and methodology render enterprise schema graph', function () {
    $this->seed(LegalTrustLayerMvpSeeder::class);

    $hubResponse = $this->get(route('public.regulations'));
    $hubGraph = legalContentJsonLdGraph($hubResponse);
    $hubTypes = collect($hubGraph)->pluck('@type')->all();
    $articleList = collect($hubGraph)->first(
        fn (mixed $node): bool => is_array($node)
            && ($node['@id'] ?? null) === route('public.regulations').'#articles',
    );

    $hubResponse->assertOk();

    expect(legalContentGraphDuplicateIds($hubResponse))->toBe([])
        ->and($hubTypes)->toContain('Organization')
        ->and($hubTypes)->toContain('WebSite')
        ->and($hubTypes)->toContain('BreadcrumbList')
        ->and($hubTypes)->toContain('CollectionPage')
        ->and($hubTypes)->toContain('ItemList')
        ->and($hubTypes)->toContain('DefinedTerm')
        ->and($articleList)->toBeArray()
        ->and($articleList['numberOfItems'] ?? null)->toBe(33);

    $articleItemReferences = collect($articleList['itemListElement'] ?? [])
        ->pluck('item');

    expect($articleItemReferences)->toHaveCount(33);

    $articleItemReferences->each(function (mixed $item): void {
        expect($item)->toBeArray()
            ->and(array_keys($item))->toBe(['@id'])
            ->and($item['@id'] ?? null)->toBeString()->not->toBeEmpty();
    });

    $methodologyResponse = $this->get(route('public.regulations.methodology'));
    $methodologyGraph = legalContentJsonLdGraph($methodologyResponse);

    $methodologyResponse->assertOk();

    expect(legalContentGraphDuplicateIds($methodologyResponse))->toBe([])
        ->and(collect($methodologyGraph)->pluck('@type')->all())->toContain('Organization')
        ->and(collect($methodologyGraph)->pluck('@type')->all())->toContain('WebSite')
        ->and(collect($methodologyGraph)->pluck('@type')->all())->toContain('BreadcrumbList')
        ->and(collect($methodologyGraph)->pluck('@type')->all())->toContain('WebPage');
});

test('public regulation detail renders article legislation and related question graph', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek umożliwić pasażerom opuszczenie tramwaju, który zatrzymał się na przystanku?';

    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '10314',
            'prompt' => $prompt,
            'explanation' => 'Tak, bo jeżeli przystanek nie jest wyposażony w wysepkę dla pasażerów, a tramwaj stoi na nim, kierujący ma obowiązek zatrzymać pojazd.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 3,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $page = LegalContentPage::query()->where('slug', 'tramwaje-i-przystanki')->firstOrFail();
    $legalUnit = LegalUnit::query()->where('slug', 'art-26-ust-6')->firstOrFail();
    $response = $this->get(route('public.regulations.show', $page->slug));
    $graph = legalContentJsonLdGraph($response);
    $types = collect($graph)->pluck('@type')->all();
    $article = legalContentGraphNode($response, 'Article');
    $legislation = collect($graph)->first(
        fn (mixed $node): bool => is_array($node)
            && ($node['@id'] ?? null) === url('/entity/law/'.$legalUnit->slug),
    );
    $questionNode = collect($graph)->first(
        fn (mixed $node): bool => is_array($node)
            && ($node['@id'] ?? null) === url('/entity/question/'.$question->getKey()),
    );
    $relatedQuestions = collect($graph)->first(
        fn (mixed $node): bool => is_array($node)
            && ($node['@id'] ?? null) === route('public.regulations.show', $page->slug).'#related-questions',
    );

    $response->assertOk();

    expect(legalContentGraphDuplicateIds($response))->toBe([])
        ->and($types)->toContain('Organization')
        ->and($types)->toContain('WebSite')
        ->and($types)->toContain('BreadcrumbList')
        ->and($types)->toContain('WebPage')
        ->and($types)->toContain('Article')
        ->and($types)->toContain('Legislation')
        ->and($types)->toContain('Person')
        ->and($types)->toContain('DefinedTerm')
        ->and($types)->toContain('ItemList')
        ->and($types)->toContain('Question')
        ->and($article)->toBeArray()
        ->and($article['@id'] ?? null)->toBe(url('/entity/legal-content/'.$page->getKey()))
        ->and($article['citation'][0]['@id'] ?? null)->toBe(url('/entity/law/'.$legalUnit->slug))
        ->and($legislation)->toBeArray()
        ->and($legislation['legislationIdentifier'] ?? null)->toBe('art. 26 ust. 6')
        ->and($questionNode)->toBeArray()
        ->and($questionNode['url'] ?? '')->toContain('/pytanie/10314/')
        ->and($relatedQuestions)->toBeArray()
        ->and($relatedQuestions['numberOfItems'] ?? null)->toBe(1);
});

test('public question detail shows legal basis without changing answer explanation', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?';
    $explanation = 'Tak, masz obowiązek w tej sytuacji zapewnić bezpieczne wyjście wszystkim pasażerom tramwaju.';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => $prompt,
            'explanation' => $explanation,
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 3,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '99',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Wyjaśnienie')
        ->assertSeeText($explanation)
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie sprawdza obowiązek zatrzymania pojazdu przy oznaczonym przystanku tramwajowym bez wysepki')
        ->assertSeeText('Zobacz opracowanie przepisu')
        ->assertSeeText('art. 26 ust. 6')
        ->assertSee(
            'href="'.route('content-authors.show', 'jakub-wisniewski').'"',
            false,
        )
        ->assertSeeText('Jakub Wiśniewski')
        ->assertDontSeeText('questions.explanation');
});

test('public question detail links tram passenger exit legal basis precisely', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek umożliwić pasażerom opuszczenie tramwaju, który zatrzymał się na przystanku?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '10314',
            'prompt' => $prompt,
            'explanation' => 'Tak, bo jeżeli przystanek nie jest wyposażony w wysepkę dla pasażerów, a tramwaj stoi na nim, kierujący ma obowiązek zatrzymać pojazd.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 3,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '10314',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie dotyczy tramwaju stojącego na przystanku bez wysepki dla pasażerów')
        ->assertSeeText('art. 26 ust. 6')
        ->assertSee('tramwaje-i-przystanki', false);
});

test('pedestrian questions link precise question first legal bases', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $crossingExplanation = 'Kierujący zbliżający się do przejścia ustępuje pieszemu znajdującemu się na przejściu albo na nie wchodzącemu.';
    $gateExplanation = 'Przy wjeździe do bramy przez drogę dla pieszych kierujący jedzie powoli i ustępuje pieszemu.';
    $cautionExplanation = 'Szczególna ostrożność obowiązuje już podczas zbliżania się do przejścia dla pieszych.';

    $crossingQuestion = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '10249',
            'prompt' => 'Czy w tej sytuacji powinieneś ustąpić pierwszeństwa pieszemu?',
            'explanation' => $crossingExplanation,
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
        ]);

    $gateQuestion = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '10369',
            'prompt' => 'Zamierzasz skręcić w prawo do bramy. Czy masz obowiązek ustąpić pierwszeństwa pieszemu?',
            'explanation' => $gateExplanation,
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
        ]);

    $cautionQuestion = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '10474',
            'prompt' => 'Czy w tej sytuacji należy zachować szczególną ostrożność mimo, że w okolicy przejścia nie widać pieszych?',
            'explanation' => $cautionExplanation,
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '10249',
        'slug' => Str::slug($crossingQuestion->prompt),
    ]))
        ->assertOk()
        ->assertSeeText($crossingExplanation)
        ->assertSeeText('To pytanie sprawdza obowiązek ustąpienia pieszemu wchodzącemu na oznakowane przejście.')
        ->assertSeeText('art. 26 ust. 1')
        ->assertSee('piesi-i-przejscia', false);

    $this->get(route('public.questions.show', [
        'externalId' => '10369',
        'slug' => Str::slug($gateQuestion->prompt),
    ]))
        ->assertOk()
        ->assertSeeText($gateExplanation)
        ->assertSeeText('To pytanie dotyczy wjazdu do bramy przez drogę dla pieszych.')
        ->assertSeeText('art. 26 ust. 4')
        ->assertSee('piesi-i-przejscia', false);

    $this->get(route('public.questions.show', [
        'externalId' => '10474',
        'slug' => Str::slug($cautionQuestion->prompt),
    ]))
        ->assertOk()
        ->assertSeeText($cautionExplanation)
        ->assertSeeText('To pytanie sprawdza obowiązek szczególnej ostrożności już podczas zbliżania się do oznakowanego przejścia')
        ->assertSeeText('art. 26 ust. 1')
        ->assertSee('piesi-i-przejscia', false);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', [
            $crossingQuestion->getKey(),
            $gateQuestion->getKey(),
            $cautionQuestion->getKey(),
        ])
        ->with('legalUnit:id,slug')
        ->get();

    expect($references)->toHaveCount(3)
        ->and($references->pluck('legalUnit.slug')->sort()->values()->all())
        ->toBe(['art-26-ust-1', 'art-26-ust-1', 'art-26-ust-4'])
        ->and($references->contains(fn (QuestionLegalReference $reference): bool => $reference->legalUnit->slug === 'art-26'))
        ->toBeFalse();
});

test('question without verified legal relation does not render legal basis block', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy możesz kontynuować jazdę?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '777',
            'prompt' => $prompt,
            'explanation' => 'To pytanie nie ma jeszcze zweryfikowanej podstawy prawnej.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '777',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertDontSeeText('Uzasadnienie prawne')
        ->assertDontSeeText('Zobacz opracowanie przepisu');
});

test('public question detail links speed limit legal basis', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Z jaką dopuszczalną prędkością możesz jechać w strefie zamieszkania?';

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '4170',
            'prompt' => $prompt,
            'explanation' => 'W strefie zamieszkania obowiązuje niski limit prędkości.',
            'option_a' => '40 km/h',
            'option_b' => '30 km/h',
            'option_c' => '20 km/h',
            'correct_answer' => 'c',
            'points' => 2,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '4170',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie sprawdza podstawowy limit w strefie zamieszkania.')
        ->assertSeeText('Prędkość w strefie zamieszkania')
        ->assertSeeText('art. 20 ust. 2')
        ->assertSee('predkosc-odstep-i-hamowanie', false);
});

test('speed distance and braking questions link precise question first legal bases', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $definitions = [
        '13562' => [
            'prompt' => 'Czy prędkość pojazdu powinna być dostosowana do widoczności drogi?',
            'correct_answer' => 'a',
        ],
        '13781' => [
            'prompt' => 'Czy na drodze ekspresowej minimalny odstęp zawsze wynosi 100 m?',
            'correct_answer' => 'b',
        ],
        '13782' => [
            'prompt' => 'Czy na autostradzie minimalny odstęp zawsze wynosi 100 m?',
            'correct_answer' => 'b',
        ],
        '4170' => [
            'prompt' => 'Z jaką dopuszczalną prędkością możesz jechać w strefie zamieszkania?',
            'correct_answer' => 'c',
        ],
        '7237' => [
            'prompt' => 'Z jaką prędkością nie możesz poruszać się w strefie zamieszkania?',
            'correct_answer' => 'c',
        ],
        '13035' => [
            'prompt' => 'Czy z przyczepą w strefie zamieszkania możesz jechać 30 km/h?',
            'correct_answer' => 'c',
        ],
        '2484' => [
            'prompt' => 'Na których pasach autostrady możesz jechać z prędkością 140 km/h?',
            'correct_answer' => 'a',
        ],
        '3966' => [
            'prompt' => 'Co należy uwzględnić, ustalając odstęp od poprzedzającego pojazdu?',
            'correct_answer' => 'a',
        ],
    ];

    $questions = collect($definitions)->mapWithKeys(function (array $definition, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => $definition['prompt'],
                'explanation' => 'Testowe wyjaśnienie pytania o prędkość lub odstęp.',
                'option_a' => 'Odpowiedź A',
                'option_b' => 'Odpowiedź B',
                'option_c' => 'Odpowiedź C',
                'correct_answer' => $definition['correct_answer'],
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    expect($references)->toHaveCount(8)
        ->and($unitByQuestion)->toBe([
            '13562' => 'art-19-ust-1',
            '13781' => 'art-19-ust-3a',
            '13782' => 'art-19-ust-3a',
            '4170' => 'art-20-ust-2',
            '7237' => 'art-20-ust-2',
            '13035' => 'art-20-ust-2',
            '2484' => 'art-20-ust-3-pkt-1-lit-a',
            '3966' => 'art-19-ust-2-pkt-3',
        ])
        ->and($references->contains(
            fn (QuestionLegalReference $reference): bool => in_array(
                $reference->legalUnit->slug,
                ['art-19', 'art-20'],
                true,
            ),
        ))->toBeFalse();
});

test('modernized legacy articles map every question to a precise legal unit', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '7403' => 'art-24-ust-1-pkt-2',
        '10208' => 'art-24-ust-2',
        '7640' => 'art-24-ust-2',
        '9541' => 'art-24-ust-2',
        '8979' => 'art-24-ust-2',
        '10053' => 'art-24-ust-2',
        '7398' => 'art-24-ust-6',
        '7777' => 'art-24-ust-7-pkt-1',
        '10937' => 'art-24-ust-7-pkt-3',
        '7012' => 'art-24-ust-11',
        '10939' => 'art-24-ust-2',
        '12779' => 'art-24-ust-2',
        '3154' => 'art-23-ust-1-pkt-1',
        '10966' => 'art-23-ust-1-pkt-1',
        '3155' => 'art-23-ust-1-pkt-2',
        '3157' => 'art-23-ust-1-pkt-2',
        '13540' => 'art-23-ust-1-pkt-2',
        '2501' => 'art-23-ust-1-pkt-3',
        '13773' => 'art-23-ust-1-pkt-3',
        '6206' => 'art-23-ust-2',
        '6203' => 'art-23-ust-2',
        '6208' => 'art-23-ust-2',
        '7219' => 'art-22-ust-5',
        '11089' => 'art-22-ust-1',
        '11090' => 'art-22-ust-5',
        '11500' => 'art-22-ust-2-pkt-2',
        '4488' => 'art-22-ust-5',
        '2568' => 'art-22-ust-5',
        '4155' => 'art-22-ust-1',
        '2287' => 'art-17-ust-2',
        '6084' => 'art-17-ust-1-pkt-1',
        '6095' => 'art-17-ust-2',
        '7336' => 'art-17-ust-1-pkt-1',
        '1142' => 'art-17-ust-1',
        '1338' => 'art-17-ust-2',
        '10847' => 'art-17-ust-1',
        '10154' => 'par-36-ust-2',
        '10247' => 'art-25-ust-1',
        '10107' => 'par-108-ust-2',
        '469' => 'par-95-ust-1-pkt-4',
        '10237' => 'art-49-ust-1-pkt-5',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie testowe {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(42)
        ->and($unitByQuestion)->toBe($expected)
        ->and($references->where('question_id', $questions['7012']->getKey()))->toHaveCount(2)
        ->and($references->contains(
            fn (QuestionLegalReference $reference): bool => in_array(
                $reference->legalUnit->slug,
                ['art-17', 'art-22', 'art-23', 'art-24', 'art-25', 'art-49'],
                true,
            ),
        ))->toBeFalse();
});

test('u turn article maps verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '1492' => 'art-22-ust-6-pkt-1',
        '4211' => 'art-22-ust-6-pkt-1',
        '6033' => 'art-22-ust-6-pkt-1',
        '6181' => 'art-22-ust-6-pkt-1',
        '6185' => 'art-22-ust-6-pkt-1',
        '1490' => 'art-22-ust-6-pkt-2',
        '1491' => 'art-22-ust-6-pkt-3',
        '1514' => 'art-22-ust-6-pkt-4',
        '4596' => 'art-22-ust-6-pkt-4',
        '1428' => 'par-22-ust-1-2',
        '6019' => 'par-22-ust-1-2',
        '6023' => 'par-22-ust-1-2',
        '7274' => 'par-22-ust-1-2',
        '7279' => 'par-22-ust-1-2',
        '8359' => 'par-22-ust-1-2',
        '8391' => 'par-22-ust-1-2',
        '13085' => 'par-22-ust-1-2',
        '11152' => 'par-22-ust-1-2',
        '1517' => 'par-22-ust-5',
        '7277' => 'par-22-ust-5',
        '8380' => 'par-22-ust-5',
        '8387' => 'par-22-ust-5',
        '8388' => 'par-22-ust-5',
        '8389' => 'par-22-ust-5',
        '9633' => 'par-22-ust-5',
        '10189' => 'par-22-ust-5',
        '10252' => 'par-22-ust-5',
        '10435' => 'par-22-ust-5',
        '11076' => 'par-22-ust-5',
        '2904' => 'par-87-ust-1',
        '8439' => 'par-87-ust-1',
        '8440' => 'par-87-ust-1',
        '610' => 'par-87-ust-2',
        '1691' => 'par-87-ust-2',
        '2902' => 'par-87-ust-2',
        '6054' => 'par-87-ust-2',
        '7308' => 'par-87-ust-2',
        '1474' => 'par-87-ust-2',
        '6134' => 'par-87-ust-2',
        '6129' => 'par-97-ust-1',
        '8648' => 'par-97-ust-1',
        '8660' => 'par-97-ust-1',
        '3364' => 'par-97-ust-3',
        '4001' => 'par-97-ust-3',
        '3445' => 'art-25-ust-2',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o zawracanie {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(45)
        ->and($unitByQuestion)->toBe($expected);
});

test('public question detail links u turn legal basis and article', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy wolno Ci zawrócić na moście?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '1492',
            'prompt' => $prompt,
            'explanation' => 'Zawracanie na moście jest zabronione.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
            'points' => 2,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '1492',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie sprawdza ustawowy zakaz zawracania w tunelu, na moście, wiadukcie albo drodze jednokierunkowej.')
        ->assertSeeText('art. 22 ust. 6 pkt 1')
        ->assertSee('zawracanie', false);
});

test('sound signal article maps only verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '2187' => 'art-29-ust-2-pkt-2',
        '1157' => 'art-29-ust-2-pkt-2',
        '3653' => 'art-29-ust-1',
        '6217' => 'art-29-ust-1',
        '7127' => 'art-29-ust-1',
        '7129' => 'art-29-ust-1',
        '13763' => 'art-29-ust-1',
        '2212' => 'art-29-ust-1',
        '1144' => 'art-29-ust-2-pkt-1',
        '1770' => 'art-29-ust-2-pkt-1',
        '3363' => 'art-29-ust-2-pkt-1',
        '6235' => 'art-29-ust-2-pkt-1',
        '7471' => 'art-29-ust-2-pkt-1',
        '8284' => 'art-29-ust-2-pkt-1',
        '9255' => 'art-29-ust-2-pkt-1',
        '9261' => 'art-29-ust-2-pkt-1',
        '10428' => 'art-29-ust-2-pkt-1',
        '10981' => 'art-29-ust-2-pkt-1',
        '10986' => 'art-29-ust-2-pkt-1',
        '6448' => 'par-11-ust-1-pkt-6',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o sygnał dźwiękowy {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(20)
        ->and($unitByQuestion)->toBe($expected);
});

test('load and trailer articles map only verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '6695' => 'art-61-ust-1',
        '7615' => 'art-61-ust-1',
        '11474' => 'art-61-ust-1',
        '1877' => 'art-61-ust-2-pkt-2',
        '6580' => 'art-61-ust-2-pkt-2',
        '6589' => 'art-61-ust-2-pkt-2',
        '10086' => 'art-61-ust-2-pkt-2',
        '11475' => 'art-61-ust-2-pkt-2',
        '1881' => 'art-61-ust-2-pkt-4',
        '1883' => 'art-61-ust-2-pkt-4',
        '1878' => 'art-61-ust-3',
        '6575' => 'art-61-ust-3',
        '6579' => 'art-61-ust-3',
        '10085' => 'art-61-ust-3',
        '1880' => 'art-61-ust-4',
        '1879' => 'art-61-ust-5',
        '6694' => 'art-61-ust-5',
        '6402' => 'art-61-ust-6-pkt-2',
        '7725' => 'art-61-ust-6-pkt-3',
        '7531' => 'art-61-ust-7',
        '11476' => 'art-61-ust-9-pkt-3',
        '11517' => 'art-61-ust-9-pkt-3',
        '11518' => 'art-61-ust-9-pkt-3',
        '7451' => 'art-61-ust-9-pkt-4',
        '7452' => 'art-62-ust-1-pkt-1',
        '7511' => 'art-62-ust-1-pkt-3',
        '6571' => 'art-62-ust-4a-pkt-1',
        '6588' => 'art-62-ust-4a-pkt-1',
        '6403' => 'art-62-ust-4a-pkt-2',
        '6857' => 'art-62-ust-4a-pkt-2',
        '10084' => 'art-62-ust-4a-pkt-2',
        '6663' => 'par-12-ust-1-pkt-11',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o ładunek lub przyczepę {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(32)
        ->and($unitByQuestion)->toBe($expected);
});

test('railway crossing and towing articles map only verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '8328' => 'art-28-ust-1',
        '13638' => 'art-28-ust-1',
        '2472' => 'art-28-ust-3-pkt-1',
        '6072' => 'art-28-ust-3-pkt-1',
        '6078' => 'art-28-ust-3-pkt-1',
        '6278' => 'art-28-ust-3-pkt-1',
        '6280' => 'art-28-ust-3-pkt-1',
        '6283' => 'art-28-ust-3-pkt-1',
        '6286' => 'art-28-ust-3-pkt-1',
        '8305' => 'art-28-ust-3-pkt-1',
        '8309' => 'art-28-ust-3-pkt-1',
        '8314' => 'art-28-ust-3-pkt-1',
        '10242' => 'art-28-ust-3-pkt-1',
        '13464' => 'art-28-ust-3-pkt-1',
        '13466' => 'art-28-ust-3-pkt-1',
        '13635' => 'art-28-ust-3-pkt-1',
        '13637' => 'art-28-ust-3-pkt-2',
        '13469' => 'art-28-ust-3-pkt-3',
        '4258' => 'par-21-ust-1-4',
        '8304' => 'par-21-ust-1-4',
        '8386' => 'par-21-ust-1-4',
        '11265' => 'par-21-ust-1-4',
        '2443' => 'par-98-ust-5',
        '13088' => 'art-31-ust-1-pkt-1',
        '7171' => 'art-31-ust-1-pkt-3',
        '6581' => 'art-31-ust-1-pkt-4',
        '6582' => 'art-31-ust-1-pkt-4',
        '6657' => 'art-31-ust-1-pkt-4',
        '7510' => 'art-31-ust-1-pkt-4',
        '7512' => 'art-31-ust-1-pkt-5',
        '10109' => 'art-31-ust-1-pkt-6',
        '10081' => 'art-31-ust-2-pkt-1',
        '7539' => 'art-31-ust-2-pkt-3',
        '7540' => 'art-31-ust-2-pkt-3',
        '7454' => 'art-31-ust-2-pkt-5',
        '10080' => 'art-31-ust-2-pkt-5',
        '11506' => 'art-45-ust-1-pkt-2',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o przejazd kolejowy lub holowanie {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $pageIds = LegalContentPage::query()
        ->whereIn('slug', [
            'wjazd-na-przejazd-kolejowy',
            'holowanie-pojazdu',
        ])
        ->pluck('id');

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->whereIn('legal_content_page_id', $pageIds)
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(37)
        ->and($unitByQuestion)->toBe($expected);
});

test('seat belt and daytime light articles map only verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '4483' => 'art-39-ust-1',
        '6344' => 'art-39-ust-1',
        '6388' => 'art-39-ust-1',
        '6389' => 'art-39-ust-1',
        '6393' => 'art-39-ust-1',
        '6907' => 'art-39-ust-1',
        '7441' => 'art-39-ust-2-pkt-2',
        '10811' => 'art-39-ust-2-pkt-10',
        '1319' => 'art-51-ust-1',
        '3544' => 'art-51-ust-1',
        '6226' => 'art-51-ust-1',
        '7470' => 'art-51-ust-1',
        '7884' => 'art-51-ust-1',
        '2065' => 'art-51-ust-2',
        '4396' => 'art-51-ust-2',
        '6211' => 'art-51-ust-2',
        '10976' => 'art-51-ust-2',
        '6224' => 'art-30-ust-1-pkt-1-lit-a',
        '10979' => 'art-30-ust-1-pkt-1-lit-a',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o pasy lub światła {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(19)
        ->and($unitByQuestion)->toBe($expected);
});

test('railway barrier and fog light articles map only verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '2436' => 'art-28-ust-1',
        '2467' => 'art-28-ust-1',
        '13473' => 'art-28-ust-1',
        '13618' => 'art-28-ust-1',
        '2458' => 'art-28-ust-3-pkt-1',
        '2472' => 'art-28-ust-3-pkt-1',
        '6283' => 'art-28-ust-3-pkt-1',
        '8309' => 'art-28-ust-3-pkt-1',
        '8316' => 'art-28-ust-3-pkt-1',
        '10242' => 'art-28-ust-3-pkt-1',
        '13464' => 'art-28-ust-3-pkt-1',
        '13466' => 'art-28-ust-3-pkt-1',
        '2443' => 'par-98-ust-5',
        '6216' => 'art-30-ust-1-pkt-1-lit-a',
        '7464' => 'art-30-ust-1-pkt-1-lit-a',
        '6363' => 'art-30-ust-3',
        '7675' => 'art-30-ust-3',
        '8238' => 'art-30-ust-3',
        '8245' => 'art-30-ust-3',
        '11457' => 'art-30-ust-3',
        '13105' => 'art-30-ust-3',
        '13444' => 'art-30-ust-3',
        '2072' => 'art-51-ust-5',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o rogatki lub światła przeciwmgłowe {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $pageIds = LegalContentPage::query()
        ->whereIn('slug', [
            'rogatki-i-sygnaly-na-przejezdzie-kolejowym',
            'swiatla-przeciwmglowe',
        ])
        ->pluck('id');

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->whereIn('legal_content_page_id', $pageIds)
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(23)
        ->and($unitByQuestion)->toBe($expected);
});

test('child restraint and accident duty articles map only verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '10812' => 'art-39-ust-3',
        '10869' => 'art-39-ust-3',
        '10876' => 'art-39-ust-3',
        '10815' => 'art-39-ust-3a',
        '10871' => 'art-39-ust-3a',
        '10813' => 'art-39-ust-3c',
        '10882' => 'art-39-ust-3c',
        '13382' => 'art-39-ust-4-pkt-4',
        '10806' => 'art-45-ust-2-pkt-4',
        '10807' => 'art-45-ust-2-pkt-4',
        '10809' => 'art-45-ust-2-pkt-5',
        '10810' => 'art-45-ust-2-pkt-6',
        '10840' => 'art-45-ust-2-pkt-6',
        '10969' => 'art-45-ust-2-pkt-6',
        '2621' => 'art-44-ust-1-pkt-2',
        '3658' => 'art-44-ust-2-pkt-1',
        '6298' => 'art-44-ust-2-pkt-1',
        '6404' => 'art-44-ust-2-pkt-1',
        '11250' => 'art-44-ust-2-pkt-1',
        '6296' => 'art-44-ust-2-pkt-1',
        '6294' => 'art-44-ust-2-pkt-2',
        '6295' => 'art-44-ust-2-pkt-2',
        '6292' => 'art-44-ust-2-pkt-3',
        '6297' => 'art-44-ust-1-pkt-3',
        '6299' => 'art-44-ust-1-pkt-4',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o przewóz dzieci lub obowiązki po wypadku {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $pageIds = LegalContentPage::query()
        ->whereIn('slug', [
            'foteliki-i-przewoz-dzieci',
            'obowiazki-uczestnika-wypadku',
        ])
        ->pluck('id');

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->whereIn('legal_content_page_id', $pageIds)
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(25)
        ->and($unitByQuestion)->toBe($expected);
});

test('phone and high beam articles map verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '3991' => 'art-45-ust-2-pkt-1',
        '3992' => 'art-45-ust-2-pkt-1',
        '4006' => 'art-45-ust-2-pkt-1',
        '4027' => 'art-45-ust-2-pkt-1',
        '6390' => 'art-45-ust-2-pkt-1',
        '13535' => 'art-45-ust-2-pkt-1',
        '7414' => 'art-51-ust-3',
        '7416' => 'art-51-ust-3',
        '7469' => 'art-51-ust-3',
        '10406' => 'art-51-ust-3',
        '10779' => 'art-51-ust-3',
        '10980' => 'art-51-ust-3',
        '11027' => 'art-51-ust-3',
        '6213' => 'art-51-ust-4-pkt-1',
        '6223' => 'art-51-ust-4-pkt-1',
        '6222' => 'art-51-ust-4-pkt-2',
        '6225' => 'art-51-ust-4-pkt-2',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o telefon lub światła drogowe {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $pageIds = LegalContentPage::query()
        ->whereIn('slug', [
            'telefon-podczas-kierowania',
            'swiatla-drogowe-i-oslepianie',
        ])
        ->pluck('id');

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->whereIn('legal_content_page_id', $pageIds)
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(17)
        ->and($unitByQuestion)->toBe($expected);
});

test('equipment and tire articles map only verified questions to precise technical units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '1905' => 'par-11-ust-1-pkt-14',
        '6499' => 'par-11-ust-1-pkt-6',
        '6500' => 'par-11-ust-1-pkt-5c',
        '6501' => 'par-12-ust-3-pkt-11',
        '7177' => 'par-46-ust-1-pkt-2',
        '11442' => 'par-40-ust-2-pkt-8',
        '3784' => 'par-11-ust-7-pkt-4',
        '6438' => 'par-11-ust-7-pkt-4',
        '11492' => 'par-11-ust-7-pkt-4',
        '6626' => 'par-11-ust-5',
        '6629' => 'par-11-ust-5',
        '7553' => 'par-11-ust-5',
        '7603' => 'par-11-ust-7-pkt-1',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o wyposażenie lub opony {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $pageIds = LegalContentPage::query()
        ->whereIn('slug', [
            'gasnica-trojkat-i-obowiazkowe-wyposazenie',
            'opony-bieznik-i-cisnienie',
        ])
        ->pluck('id');

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->whereIn('legal_content_page_id', $pageIds)
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(13)
        ->and($unitByQuestion)->toBe($expected);
});

test('document and license category articles map verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '10834' => 'art-38-ust-1-pkt-1',
        '10838' => 'art-38-ust-1-pkt-1',
        '10892' => 'art-38-ust-1-pkt-1',
        '10895' => 'art-38-ust-1-pkt-1',
        '10841' => 'art-38-ust-1-pkt-3a',
        '11001' => 'art-38-ust-1-pkt-3a',
        '10843' => 'art-38-ust-1-pkt-4a',
        '10835' => 'art-38-ust-1-pkt-4b-lit-a',
        '10893' => 'art-38-ust-1-pkt-4b-lit-a',
        '10894' => 'art-38-ust-1-pkt-4b-lit-a',
        '10827' => 'art-38-ust-1-pkt-4b-lit-b',
        '10885' => 'art-38-ust-1-pkt-4b-lit-b',
        '10886' => 'art-38-ust-1-pkt-4b-lit-b',
        '10887' => 'art-38-ust-1-pkt-4b-lit-b',
        '10888' => 'art-38-ust-1-pkt-4b-lit-b',
        '10999' => 'art-38-ust-1-pkt-4b-lit-b',
        '11041' => 'art-38-ust-1-pkt-4b-lit-b',
        '10836' => 'art-38-ust-1-pkt-5',
        '10889' => 'art-38-ust-2',
        '10890' => 'art-38-ust-2',
        '10891' => 'art-38-ust-2',
        '10839' => 'art-38-ust-3',
        '6401' => 'ukp-art-6-ust-1-pkt-6-lit-a-b',
        '6414' => 'ukp-art-13-ust-1',
        '6422' => 'ukp-art-13-ust-1',
        '6430' => 'ukp-art-6-ust-1-pkt-5-lit-a',
        '6431' => 'ukp-art-6-ust-1-pkt-5-lit-b',
        '6599' => 'ukp-art-6-ust-1-pkt-4-lit-a',
        '7560' => 'ukp-art-6-ust-1-pkt-4-lit-a',
        '6604' => 'ukp-art-6-ust-1-pkt-2-lit-b',
        '11060' => 'ukp-art-6-ust-1-pkt-2-lit-b',
        '6606' => 'ukp-art-6-ust-1-pkt-2-lit-c',
        '6608' => 'ukp-art-6-ust-1-pkt-3-lit-b',
        '6610' => 'ukp-art-6-ust-1-pkt-3-lit-c',
        '6613' => 'ukp-art-6-ust-1-pkt-1-lit-b',
        '6618' => 'ukp-art-6-ust-1-pkt-1-lit-b',
        '6617' => 'ukp-art-6-ust-1-pkt-1-lit-a',
        '7596' => 'ukp-art-6-ust-1-pkt-1-lit-a',
        '6708' => 'ukp-art-6-ust-1-pkt-11-lit-a',
        '7625' => 'ukp-art-6-ust-1-pkt-11-lit-a',
        '6709' => 'ukp-art-6-ust-1-pkt-11-lit-b',
        '7626' => 'ukp-art-6-ust-1-pkt-11-lit-b',
        '10090' => 'ukp-art-6-ust-3-pkt-1',
        '10091' => 'ukp-art-6-ust-3-pkt-4-lit-a',
        '11070' => 'ukp-art-18-ust-1',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o dokumenty lub kategorię prawa jazdy {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'option_c' => 'Nie wiem',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $pageIds = LegalContentPage::query()
        ->whereIn('slug', [
            'dokumenty-podczas-kontroli-drogowej',
            'kategorie-prawa-jazdy-i-uprawnienia',
        ])
        ->pluck('id');

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->whereIn('legal_content_page_id', $pageIds)
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(45)
        ->and($unitByQuestion)->toBe($expected);
});

test('alcohol and green arrow articles map verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    $expected = [
        '2756' => 'sobriety-art-46-ust-2',
        '2759' => 'art-45-ust-1-pkt-1',
        '4025' => 'art-45-ust-1-pkt-1',
        '11417' => 'art-45-ust-1-pkt-1',
        '8416' => 'sobriety-art-46-ust-2',
        '8417' => 'sobriety-art-46-ust-2',
        '8419' => 'kw-art-87-par-1',
        '11506' => 'art-45-ust-1-pkt-2',
        '475' => 'par-96-ust-3',
        '7280' => 'par-96-ust-3',
        '7329' => 'par-96-ust-3',
        '10454' => 'par-96-ust-3',
    ];

    $questions = collect($expected)->mapWithKeys(function (string $unitSlug, string $externalId) use ($category): array {
        $question = Question::factory()
            ->booleanType()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o alkohol albo zieloną strzałkę {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$unitSlug}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $pageIds = LegalContentPage::query()
        ->whereIn('slug', [
            'alkohol-i-srodki-dzialajace-podobnie',
            'zielona-strzalka-warunkowa',
        ])
        ->pluck('id');

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->whereIn('legal_content_page_id', $pageIds)
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get();

    $unitByQuestion = $references
        ->mapWithKeys(fn (QuestionLegalReference $reference): array => [
            $reference->question->external_id => $reference->legalUnit->slug,
        ])
        ->all();

    ksort($expected);
    ksort($unitByQuestion);

    expect($references)->toHaveCount(12)
        ->and($unitByQuestion)->toBe($expected);
});

test('public question detail links alcohol and green arrow legal bases', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $alcoholPrompt = 'Czy możesz kierować pojazdem, gdy zawartość alkoholu we krwi przekracza 0,2 promila?';
    $arrowPrompt = 'Czy przy zielonej strzałce możesz skręcić w prawo bez zatrzymania pojazdu?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '8417',
            'prompt' => $alcoholPrompt,
            'explanation' => 'Nie, taki wynik oznacza stan po użyciu alkoholu.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ]);

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '475',
            'prompt' => $arrowPrompt,
            'explanation' => 'Nie, przed sygnalizatorem trzeba całkowicie zatrzymać pojazd.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '8417',
        'slug' => Str::slug($alcoholPrompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('kwalifikuje wartości od tego poziomu jako stan po użyciu alkoholu')
        ->assertSeeText('art. 46 ust. 2')
        ->assertSee('alkohol-i-srodki-dzialajace-podobnie', false);

    $this->get(route('public.questions.show', [
        'externalId' => '475',
        'slug' => Str::slug($arrowPrompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('wymaga najpierw całkowitego zatrzymania przed sygnalizatorem')
        ->assertSeeText('§ 96 ust. 3')
        ->assertSee('zielona-strzalka-warunkowa', false);
});

test('emergency vehicle and yellow signal articles map verified questions to precise legal units', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $questionIds = ['7012', '7153', '10698', '10700', '10720', '10730', '7779', '10411', '10459', '10460'];
    $questions = collect($questionIds)->mapWithKeys(function (string $externalId) use ($category): array {
        $question = Question::factory()
            ->booleanType()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Pytanie o pojazd uprzywilejowany albo żółty sygnał {$externalId}",
                'explanation' => "Wyjaśnienie testowe {$externalId}.",
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'correct_answer' => 'a',
            ]);

        return [$externalId => $question];
    });

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $pageIds = LegalContentPage::query()
        ->whereIn('slug', [
            'pojazd-uprzywilejowany',
            'sygnal-zolty-i-zolty-migajacy',
        ])
        ->pluck('id');

    $pairs = QuestionLegalReference::query()
        ->whereIn('question_id', $questions->pluck('id'))
        ->whereIn('legal_content_page_id', $pageIds)
        ->with(['question:id,external_id', 'legalUnit:id,slug'])
        ->get()
        ->map(fn (QuestionLegalReference $reference): string => $reference->question->external_id.':'.$reference->legalUnit->slug)
        ->sort()
        ->values()
        ->all();

    expect($pairs)->toBe([
        '10411:par-98-ust-6',
        '10459:par-98-ust-6',
        '10460:par-98-ust-6',
        '10698:art-9-ust-2',
        '10700:art-9-ust-2',
        '10720:art-9-ust-2',
        '10730:art-9-ust-2',
        '7012:art-24-ust-11',
        '7153:art-24-ust-11',
        '7153:par-58-ust-3',
        '7779:par-98-ust-6',
    ]);
});

test('public question detail links emergency corridor and yellow flashing signal legal bases', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $corridorPrompt = 'Czy pokazana sytuacja przedstawia poprawnie utworzony korytarz życia?';
    $yellowPrompt = 'Czy sygnał żółty migający oznacza, że za chwilę zapali się sygnał zielony?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '10720',
            'prompt' => $corridorPrompt,
            'explanation' => 'Tak, pojazdy utworzyły prawidłowy korytarz życia.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
        ]);

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '10459',
            'prompt' => $yellowPrompt,
            'explanation' => 'Nie, sygnał ostrzega o niebezpieczeństwie lub utrudnieniu.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '10720',
        'slug' => Str::slug($corridorPrompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('pozostawić drogę przejazdu między skrajnym lewym pasem')
        ->assertSeeText('art. 9 ust. 2')
        ->assertSee('pojazd-uprzywilejowany', false);

    $this->get(route('public.questions.show', [
        'externalId' => '10459',
        'slug' => Str::slug($yellowPrompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('nie zapowiada sygnału zielonego')
        ->assertSeeText('§ 98 ust. 6')
        ->assertSee('sygnal-zolty-i-zolty-migajacy', false);
});

test('public question detail links sound signal legal basis and article', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy możesz użyć sygnału dźwiękowego, aby ostrzec kierowcę stwarzającego bezpośrednie zagrożenie?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '2187',
            'prompt' => $prompt,
            'explanation' => 'Bezpośrednie zagrożenie uzasadnia użycie sygnału dźwiękowego.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 2,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '2187',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie wprost dotyczy ostrzeżenia kierowcy, który stwarza bezpośrednie zagrożenie')
        ->assertSeeText('art. 29 ust. 2 pkt 2')
        ->assertSee('sygnal-dzwiekowy', false);
});

test('public question detail links lane change legal basis', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji kontynuując jazdę masz obowiązek sygnalizować zamiar zmiany pasa ruchu?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '7219',
            'prompt' => $prompt,
            'explanation' => 'Zmiana pasa ruchu wymaga wcześniejszego i wyraźnego sygnalizowania zamiaru.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 2,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '7219',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie sprawdza sygnalizowanie planowanej zmiany pasa ruchu.')
        ->assertSeeText('Sygnalizowanie manewru kierunkowskazem')
        ->assertSeeText('art. 22 ust. 5')
        ->assertSee('zmiana-kierunku-i-pasa-ruchu', false);
});

test('public question detail links entering traffic legal basis', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy wyjeżdżając ze strefy zamieszkania, włączasz się do ruchu?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '6084',
            'prompt' => $prompt,
            'explanation' => 'Wyjazd ze strefy zamieszkania jest jedną z sytuacji włączania się do ruchu.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 2,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '6084',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie sprawdza samą kwalifikację wyjazdu ze strefy zamieszkania.')
        ->assertSeeText('Wyjazd z nieruchomości, obiektu, drogi niebędącej drogą publiczną lub strefy zamieszkania')
        ->assertSeeText('art. 17 ust. 1 pkt 1')
        ->assertSee('wlaczanie-sie-do-ruchu', false);
});

test('bus stop questions link precise legal basis for built up and non built up areas', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $builtUpPrompt = 'Czy masz obowiązek zwolnić, aby umożliwić autobusowi wyjazd z zatoki w obszarze zabudowanym?';
    $outsidePrompt = 'Czy poza obszarem zabudowanym masz szczególny obowiązek umożliwić autobusowi wyjazd z przystanku?';

    $builtUpQuestion = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '1133',
            'prompt' => $builtUpPrompt,
            'explanation' => 'W obszarze zabudowanym trzeba umożliwić autobusowi sygnalizowany wyjazd z oznaczonego przystanku.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
        ]);

    $outsideQuestion = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '11123',
            'prompt' => $outsidePrompt,
            'explanation' => 'Szczególny obowiązek z art. 18 ust. 1 dotyczy obszaru zabudowanego.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '1133',
        'slug' => Str::slug($builtUpPrompt),
    ]))
        ->assertOk()
        ->assertSeeText('To pytanie sprawdza obowiązek zmniejszenia prędkości przy oznaczonym przystanku autobusowym na obszarze zabudowanym.')
        ->assertSeeText('art. 18 ust. 1')
        ->assertSee('autobus-wyjezdzajacy-z-przystanku', false);

    $this->get(route('public.questions.show', [
        'externalId' => '11123',
        'slug' => Str::slug($outsidePrompt),
    ]))
        ->assertOk()
        ->assertSeeText('To pytanie sprawdza granicę zastosowania art. 18 ust. 1.')
        ->assertSeeText('dlatego poza nim nie wynika z tego przepisu.')
        ->assertSeeText('art. 18 ust. 1')
        ->assertSee('autobus-wyjezdzajacy-z-przystanku', false);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', [
            $builtUpQuestion->getKey(),
            $outsideQuestion->getKey(),
        ])
        ->with('legalUnit:id,slug')
        ->get();

    expect($references)->toHaveCount(2)
        ->and($references->pluck('legalUnit.slug')->unique()->values()->all())
        ->toBe(['art-18-ust-1']);
});

test('public question detail links passing and reversing legal basis', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy masz obowiązek zachować bezpieczny odstęp od omijanego pojazdu?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '3155',
            'prompt' => $prompt,
            'explanation' => 'Podczas omijania trzeba zachować bezpieczny odstęp od omijanego pojazdu.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 2,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '3155',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie dotyczy przejazdu obok nieruchomego pojazdu.')
        ->assertSeeText('Bezpieczne omijanie')
        ->assertSeeText('art. 23 ust. 1 pkt 2')
        ->assertSee('wymijanie-omijanie-cofanie', false);
});

test('public question detail links overtaking legal basis', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w przedstawionej sytuacji minimalna odległość jaką należy zachować od rowerzysty podczas wyprzedzania wynosi 1 m?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '9541',
            'prompt' => $prompt,
            'explanation' => 'Podczas wyprzedzania rowerzysty trzeba zachować odstęp co najmniej 1 m.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 2,
        ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->get(route('public.questions.show', [
        'externalId' => '9541',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('To pytanie dotyczy ustawowego minimum przy wyprzedzaniu rowerzysty.')
        ->assertSeeText('Ostrożność i odstęp podczas wyprzedzania')
        ->assertSeeText('art. 24 ust. 2')
        ->assertSee('wyprzedzanie', false);
});

test('legal content sitemap and reviewer author profile are public', function () {
    $this->seed(LegalTrustLayerMvpSeeder::class);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemaps/legal-content.xml')))->toBeTrue();

    $index = File::get(public_path('sitemap.xml'));
    $legalContent = File::get(public_path('sitemaps/legal-content.xml'));

    expect($index)->toContain('https://prawkonaraz.pl/sitemaps/legal-content.xml');
    expect($legalContent)
        ->toContain('https://prawkonaraz.pl/przepisy/tramwaje-i-przystanki')
        ->toContain('https://prawkonaraz.pl/przepisy/piesi-i-przejscia')
        ->toContain('https://prawkonaraz.pl/przepisy/predkosc-odstep-i-hamowanie')
        ->toContain('https://prawkonaraz.pl/przepisy/zmiana-kierunku-i-pasa-ruchu')
        ->toContain('https://prawkonaraz.pl/przepisy/wlaczanie-sie-do-ruchu')
        ->toContain('https://prawkonaraz.pl/przepisy/wymijanie-omijanie-cofanie')
        ->toContain('https://prawkonaraz.pl/przepisy/wyprzedzanie')
        ->toContain('https://prawkonaraz.pl/przepisy/autobus-wyjezdzajacy-z-przystanku')
        ->toContain('https://prawkonaraz.pl/przepisy/zawracanie')
        ->toContain('https://prawkonaraz.pl/przepisy/sygnal-dzwiekowy')
        ->toContain('https://prawkonaraz.pl/przepisy/zabezpieczenie-ladunku-i-wymiary')
        ->toContain('https://prawkonaraz.pl/przepisy/przyczepa-masa-wymiary-i-oswietlenie')
        ->toContain('https://prawkonaraz.pl/przepisy/wjazd-na-przejazd-kolejowy')
        ->toContain('https://prawkonaraz.pl/przepisy/holowanie-pojazdu')
        ->toContain('https://prawkonaraz.pl/przepisy/pasy-bezpieczenstwa')
        ->toContain('https://prawkonaraz.pl/przepisy/swiatla-do-jazdy-dziennej-i-mijania')
        ->toContain('https://prawkonaraz.pl/przepisy/rogatki-i-sygnaly-na-przejezdzie-kolejowym')
        ->toContain('https://prawkonaraz.pl/przepisy/swiatla-przeciwmglowe')
        ->toContain('https://prawkonaraz.pl/przepisy/foteliki-i-przewoz-dzieci')
        ->toContain('https://prawkonaraz.pl/przepisy/obowiazki-uczestnika-wypadku')
        ->toContain('https://prawkonaraz.pl/przepisy/telefon-podczas-kierowania')
        ->toContain('https://prawkonaraz.pl/przepisy/swiatla-drogowe-i-oslepianie')
        ->toContain('https://prawkonaraz.pl/przepisy/gasnica-trojkat-i-obowiazkowe-wyposazenie')
        ->toContain('https://prawkonaraz.pl/przepisy/opony-bieznik-i-cisnienie')
        ->toContain('https://prawkonaraz.pl/przepisy/dokumenty-podczas-kontroli-drogowej')
        ->toContain('https://prawkonaraz.pl/przepisy/kategorie-prawa-jazdy-i-uprawnienia')
        ->toContain('https://prawkonaraz.pl/przepisy/alkohol-i-srodki-dzialajace-podobnie')
        ->toContain('https://prawkonaraz.pl/przepisy/zielona-strzalka-warunkowa')
        ->toContain('https://prawkonaraz.pl/przepisy/pojazd-uprzywilejowany')
        ->toContain('https://prawkonaraz.pl/przepisy/sygnal-zolty-i-zolty-migajacy');

    $this->get('/sitemaps/legal-content.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('https://prawkonaraz.pl/przepisy/tramwaje-i-przystanki', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/predkosc-odstep-i-hamowanie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/zmiana-kierunku-i-pasa-ruchu', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/wlaczanie-sie-do-ruchu', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/wymijanie-omijanie-cofanie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/wyprzedzanie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/autobus-wyjezdzajacy-z-przystanku', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/zawracanie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/sygnal-dzwiekowy', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/zabezpieczenie-ladunku-i-wymiary', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/przyczepa-masa-wymiary-i-oswietlenie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/wjazd-na-przejazd-kolejowy', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/holowanie-pojazdu', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/pasy-bezpieczenstwa', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/swiatla-do-jazdy-dziennej-i-mijania', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/rogatki-i-sygnaly-na-przejezdzie-kolejowym', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/swiatla-przeciwmglowe', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/foteliki-i-przewoz-dzieci', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/obowiazki-uczestnika-wypadku', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/telefon-podczas-kierowania', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/swiatla-drogowe-i-oslepianie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/gasnica-trojkat-i-obowiazkowe-wyposazenie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/opony-bieznik-i-cisnienie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/dokumenty-podczas-kontroli-drogowej', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/kategorie-prawa-jazdy-i-uprawnienia', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/alkohol-i-srodki-dzialajace-podobnie', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/zielona-strzalka-warunkowa', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/pojazd-uprzywilejowany', false)
        ->assertSee('https://prawkonaraz.pl/przepisy/sygnal-zolty-i-zolty-migajacy', false);

    $this->get(route('content-authors.show', 'jakub-wisniewski'))
        ->assertOk()
        ->assertSeeText('Jakub Wiśniewski')
        ->assertSeeText('były egzaminator WORD')
        ->assertSee('images/authors/jakub-wisniewski.png', false)
        ->assertSeeText('Publikacje')
        ->assertSeeText('Tramwaje, przystanki i bezpieczeństwo pasażerów')
        ->assertSeeText('Autobus wyjeżdżający z przystanku')
        ->assertSeeText('Zawracanie: gdzie wolno, znaki i sygnalizacja')
        ->assertSeeText('Sygnał dźwiękowy: kiedy wolno użyć klaksonu')
        ->assertSeeText('Zabezpieczenie ładunku: mocowanie, wymiary i oznakowanie')
        ->assertSeeText('Przyczepa: masa, wymiary i obowiązkowe oświetlenie')
        ->assertSeeText('Przejazd kolejowy: kiedy nie wolno wjechać na tory')
        ->assertSeeText('Holowanie pojazdu: prędkość, oznakowanie i zakazy')
        ->assertSeeText('Pasy bezpieczeństwa: kto musi zapinać i jakie są wyjątki')
        ->assertSeeText('Światła mijania i dzienne: kiedy których używać')
        ->assertSeeText('Rogatki i czerwone światło: kiedy wolno ruszyć')
        ->assertSeeText('Światła przeciwmgłowe: przednie, tylne i granica 50 metrów')
        ->assertSeeText('Przewóz dzieci: fotelik, wzrost 150 cm i wyjątki')
        ->assertSeeText('Wypadek drogowy: obowiązki uczestnika krok po kroku')
        ->assertSeeText('Dokumenty podczas kontroli drogowej: co trzeba mieć przy sobie')
        ->assertSeeText('Kategorie prawa jazdy: czym możesz kierować')
        ->assertSeeText('Alkohol za kierownicą: limity, zakaz i odpowiedzialność')
        ->assertSeeText('Zielona strzałka warunkowa: zatrzymanie i pierwszeństwo')
        ->assertSeeText('Pojazd uprzywilejowany i korytarz życia: jak ustąpić')
        ->assertSeeText('Żółte światło: kiedy się zatrzymać i co oznacza miganie');
});
