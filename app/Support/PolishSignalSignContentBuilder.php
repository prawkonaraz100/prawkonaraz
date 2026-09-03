<?php

namespace App\Support;

use Illuminate\Support\Str;

class PolishSignalSignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $config = $this->signalConfig()[$sign['code']] ?? $this->fallbackConfig($sign);
        $assetPath = $this->assetPath($sign['slug']);
        $displayCode = $this->displayCode($sign['code']);
        $displayTitle = trim($displayCode.' '.$sign['name']);
        $imageAlt = $displayTitle;
        $defaultLegalSummary = 'Zgodnie z hierarchią znaków i sygnałów, polecenia nadawane przez sygnalizację świetlną są ważniejsze niż znaki drogowe pionowe i poziome ustalające pierwszeństwo. Jedynie polecenia osoby kierującej ruchem (np. policjanta) stoją wyżej w hierarchii.';
        $defaultFineSummary = 'Wjazd na skrzyżowanie na czerwonym świetle to jedno z najpoważniejszych i najsurowiej karanych wykroczeń, bezpośrednio zagrażające życiu. Grozi za to 15 punktów karnych i mandat rzędu kilkuset złotych.';

        return [
            'intro_definition' => $config['intro_definition'] ?? "Sygnalizator {$displayCode} to nadrzędne urządzenie kierujące ruchem na skrzyżowaniu (grupa S). Sygnały świetlne zawsze mają pierwszeństwo przed znakami drogowymi regulującymi pierwszeństwo przejazdu.",
            'meaning' => $config['meaning'],
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => $config['legal_summary'] ?? $defaultLegalSummary,
            'fine_summary' => $config['fine_summary'] ?? $defaultFineSummary,
            'common_mistakes' => $config['mistake'],
            'editorial_notes' => $config['editorial_notes'],
            'review_notes' => 'Szkic przygotowany dla implementacji sygnalizacji świetlnej (grupa S). Gotowy pod integrację z grafikami i schematami skrzyżowań.',
            'source_notes' => 'Baza wiedzy oparta na rozporządzeniu w sprawie znaków i sygnałów drogowych oraz hierarchii dyrektyw na skrzyżowaniu.',
            'faq_items' => [
                [
                    'question' => "Co dokładnie nakazuje sygnalizator {$displayCode}?",
                    'answer' => $config['faq_duty'],
                ],
                [
                    'question' => "Na czym najczęściej oblewają na egzaminie przy {$displayCode}?",
                    'answer' => $config['faq_mistake'],
                ],
            ],
            'meta_title' => $config['meta_title'] ?? "Sygnalizator {$displayCode} - jak czytać światła na skrzyżowaniu",
            'meta_description' => $config['meta_description'] ?? "Dowiedz się, jak prawidłowo przejechać przez skrzyżowanie z sygnalizatorem {$displayCode}. Sprawdź najważniejsze zasady i uniknij oblania egzaminu.",
            'image_path' => $assetPath,
            'image_alt' => $imageAlt,
            'image_width' => 1200,
            'image_height' => 1200,
            'og_image_path' => $assetPath,
            'og_image_alt' => $imageAlt,
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'sort_order' => 10 + ($index * 10),
            'legal_reference_label' => 'Rozporządzenie w sprawie znaków i sygnałów drogowych',
            'legal_reference_url' => 'https://isap.sejm.gov.pl/',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function signalConfig(): array
    {
        return [
            'S-1' => [
                'meaning' => 'Sygnalizator ogólny (bez strzałek). Zielone światło pozwala na wjazd na skrzyżowanie w dowolnym dozwolonym kierunku, jednak nie gwarantuje bezkolizyjności (np. przy skręcie w lewo trzeba ustąpić jadącym z przeciwka).',
                'placement' => 'Podstawowy typ sygnalizatora montowany przed skrzyżowaniami oraz przed przejściami dla pieszych.',
                'behavior' => 'Mając zielone światło możesz jechać prosto, ale skręcając w lewo upewnij się, że nie wymusisz pierwszeństwa na pojazdach jadących z naprzeciwka na wprost lub w prawo. Pamiętaj też o pieszych na drodze poprzecznej.',
                'mistake' => 'Częstym błędem jest przeświadczenie, że zielone światło na sygnalizatorze ogólnym oznacza absolutne pierwszeństwo we wszystkich kierunkach.',
                'faq_duty' => 'Otwiera ruch, ale wymaga samodzielnej oceny pierwszeństwa podczas zmiany kierunku jazdy.',
                'faq_mistake' => 'Wymuszenie pierwszeństwa przy lewoskręcie na zielonym świetle to klasyczny błąd i częsta przyczyna wypadków.',
                'editorial_notes' => 'Punkt wyjścia do nauki jazdy na skrzyżowaniach z sygnalizacją. Zestawić z różnicą względem S-3.',
            ],
            'S-1a' => [
                'intro_definition' => 'S-1 Sygnał czerwony pokazuje sygnalizator podstawowy S-1 z nadawanym sygnałem czerwonym. To najprostszy komunikat: nie wolno wjechać za sygnalizator.',
                'meaning' => 'Sygnał czerwony oznacza zakaz wjazdu za sygnalizator. Nie ma tu znaczenia, że skrzyżowanie wygląda na puste albo że inni kierujący ruszają wcześniej.',
                'placement' => 'Czerwony sygnał znajduje się w górnej komorze sygnalizatora podstawowego ustawianego przy skrzyżowaniach, przejściach dla pieszych, przejazdach i innych miejscach kierowania ruchem.',
                'behavior' => 'Zatrzymaj pojazd przed linią zatrzymania, a gdy jej nie ma — przed sygnalizatorem. Jeżeli sygnalizator wisi nad jezdnią, zatrzymanie wykonaj przed jezdnią, nad którą jest umieszczony.',
                'fine_summary' => 'Wjazd za sygnalizator przy czerwonym świetle jest traktowany jako bardzo poważne naruszenie i typowo kończy egzamin wynikiem negatywnym.',
                'mistake' => 'Najczęstszy błąd to ruszanie „na pamięć” albo toczenie się za linię zatrzymania, bo kierowca patrzy na ruch poprzeczny zamiast na własny sygnalizator.',
                'faq_duty' => 'Nakazuje zatrzymać się i czekać na sygnał zezwalający na wjazd.',
                'faq_mistake' => 'Nie wolno ruszać tylko dlatego, że widzisz czerwone z żółtym albo że inne pojazdy obok zaczęły się poruszać.',
                'editorial_notes' => 'Wariant czerwony warto zestawić z sygnałami czerwonym i żółtym, bo oba zakazują wjazdu, ale czerwony z żółtym zapowiada zmianę na zielone.',
                'meta_title' => 'S-1 sygnał czerwony - znaczenie i zachowanie kierowcy',
                'meta_description' => 'Sprawdź, co oznacza czerwone światło S-1, gdzie zatrzymać pojazd i dlaczego wjazd za sygnalizator jest jednym z najpoważniejszych błędów.',
            ],
            'S-1b' => [
                'intro_definition' => 'S-1 Sygnał żółty pokazuje sygnalizator podstawowy S-1 z nadawanym sygnałem żółtym. To sygnał zakazu wjazdu, z jednym praktycznym wyjątkiem.',
                'meaning' => 'Sygnał żółty oznacza zakaz wjazdu za sygnalizator, chyba że w chwili jego zapalenia pojazd znajduje się tak blisko, że zatrzymanie przed sygnalizatorem wymagałoby gwałtownego hamowania. Żółte zapowiada czerwone.',
                'placement' => 'Żółta komora znajduje się pośrodku sygnalizatora podstawowego S-1, stosowanego na skrzyżowaniach i w miejscach, w których ruchem kieruje sygnalizacja.',
                'behavior' => 'Jeżeli możesz zatrzymać się bezpiecznie, zatrzymaj pojazd przed linią zatrzymania. Nie przyspieszaj, żeby „zdążyć”. Przejeżdżaj tylko wtedy, gdy hamowanie byłoby gwałtowne i niebezpieczne.',
                'fine_summary' => 'Nieuzasadniony wjazd na żółtym świetle jest bardzo ryzykowny: na egzaminie często kończy się przerwaniem zadania, a w ruchu drogowym może zostać oceniony jako niezastosowanie się do sygnału.',
                'mistake' => 'Najczęstszy błąd to automatyczne dodanie gazu na żółtym. W praktyce żółte ma przygotować do zatrzymania, a nie do sprintu przez skrzyżowanie.',
                'faq_duty' => 'Zasadą jest zatrzymanie pojazdu; wyjątek dotyczy tylko sytuacji, gdy bezpieczne zatrzymanie nie jest już możliwe.',
                'faq_mistake' => 'Kandydaci często traktują żółte jak „ostatnią szansę na przejazd”. To zły nawyk i prosta droga do błędu egzaminacyjnego.',
                'editorial_notes' => 'Wyraźnie odróżniać żółte od czerwonego z żółtym: żółte zapowiada czerwone, a czerwone z żółtym zapowiada zielone.',
                'meta_title' => 'S-1 sygnał żółty - kiedy wolno przejechać',
                'meta_description' => 'Sygnał żółty S-1 zasadniczo zakazuje wjazdu. Zobacz, kiedy można kontynuować jazdę i jak uniknąć błędu na egzaminie.',
            ],
            'S-1c' => [
                'intro_definition' => 'S-1 Sygnał zielony pokazuje sygnalizator podstawowy S-1 z nadawanym sygnałem zielonym. Zielone pozwala wjechać za sygnalizator, ale nie zwalnia z oceny sytuacji.',
                'meaning' => 'Sygnał zielony oznacza zezwolenie na wjazd za sygnalizator. Nie wolno jednak wjechać, jeżeli utrudniłoby to opuszczenie jezdni pieszym lub rowerzystom albo nie da się opuścić skrzyżowania przed końcem nadawanego sygnału.',
                'placement' => 'Zielona komora znajduje się w dolnej części sygnalizatora podstawowego S-1. Taki sygnalizator może obsługiwać jazdę na wprost i skręty, ale bez gwarancji bezkolizyjności.',
                'behavior' => 'Wjedź tylko wtedy, gdy masz miejsce za skrzyżowaniem. Przy skręcie w lewo nadal oceniaj ruch z przeciwka, a przy skręcie w prawo obserwuj pieszych i rowerzystów na drodze poprzecznej.',
                'fine_summary' => 'Samo zielone światło nie usprawiedliwia blokowania skrzyżowania, wymuszenia pierwszeństwa ani wjazdu bez możliwości kontynuowania jazdy. Na egzaminie to bardzo częsta pułapka.',
                'mistake' => 'Najczęstszy błąd to przekonanie, że zielone na S-1 daje absolutne pierwszeństwo. Daje zezwolenie na wjazd, ale nie gwarantuje przejazdu bezkolizyjnego.',
                'faq_duty' => 'Pozwala wjechać za sygnalizator, o ile nie zablokujesz skrzyżowania i nie utrudnisz ruchu pieszym lub rowerzystom.',
                'faq_mistake' => 'Najbardziej typowe jest wymuszenie przy skręcie w lewo albo wjazd na skrzyżowanie bez miejsca do zjazdu.',
                'editorial_notes' => 'Ten wariant warto linkować do S-3, bo różnica między zielonym ogólnym a zieloną strzałką kierunkową jest kluczowa dla egzaminu.',
                'meta_title' => 'S-1 sygnał zielony - kiedy można wjechać',
                'meta_description' => 'Zielone światło S-1 pozwala wjechać za sygnalizator, ale nie zawsze gwarantuje bezkolizyjny przejazd. Sprawdź zasady i częste błędy.',
            ],
            'S-1d' => [
                'intro_definition' => 'S-1 Sygnały czerwony i żółty to wariant sygnalizatora podstawowego S-1 z jednocześnie nadawanymi sygnałami czerwonym i żółtym. To nadal zakaz wjazdu, choć zapowiada zmianę na zielone.',
                'meaning' => 'Sygnały czerwony i żółty nadawane jednocześnie oznaczają zakaz wjazdu za sygnalizator. Informują jednocześnie, że za chwilę zapali się sygnał zielony.',
                'placement' => 'Ten układ pojawia się na sygnalizatorze podstawowym S-1 jako faza przejściowa między czerwonym a zielonym sygnałem.',
                'behavior' => 'Nie ruszaj jeszcze za sygnalizator. Przygotuj się do jazdy, obserwuj skrzyżowanie i włącz bieg, ale rusz dopiero po zapaleniu zielonego sygnału.',
                'fine_summary' => 'Wjazd na czerwonym z żółtym jest nadal wjazdem przy sygnale zabraniającym. Na egzaminie ruszenie przed zielonym zwykle oznacza poważny błąd.',
                'mistake' => 'Najczęstszy błąd to ruszanie w chwili pojawienia się czerwonego z żółtym. Ten sygnał zapowiada zielone, ale sam jeszcze nie zezwala na wjazd.',
                'faq_duty' => 'Nakazuje czekać; dopiero kolejne zielone światło daje zezwolenie na wjazd.',
                'faq_mistake' => 'Traktowanie czerwonego z żółtym jak zielonego. To nie jest sygnał do jazdy, tylko do przygotowania się.',
                'editorial_notes' => 'Dobra para porównawcza z sygnałem żółtym: żółte zapowiada czerwone, czerwone z żółtym zapowiada zielone.',
                'meta_title' => 'S-1 sygnały czerwony i żółty - czy wolno ruszyć',
                'meta_description' => 'S-1 z jednoczesnym sygnałem czerwonym i żółtym nadal zakazuje wjazdu. Sprawdź, dlaczego nie wolno jeszcze ruszyć.',
            ],
            'S-2' => [
                'intro_definition' => 'S-2 to sygnalizator z warunkowym zezwoleniem na skręt w kierunku wskazanym strzałką, najczęściej kojarzony z zieloną strzałką w prawo.',
                'meaning' => 'Sygnalizator z dołączoną strzałką warunkową. Gdy świeci się czerwone światło, a zapali się mała zielona strzałka, wolno skręcić w kierunku wskazanym strzałką WYŁĄCZNIE po wcześniejszym bezwzględnym zatrzymaniu pojazdu.',
                'placement' => 'Z prawej strony, na wysokości czerwonego światła sygnalizatora ogólnego na wlotach skrzyżowań.',
                'behavior' => 'Musisz całkowicie zatrzymać auto przed sygnalizatorem (bądź linią warunkowego zatrzymania). Dopiero po upewnieniu się, że nie utrudnisz ruchu pieszym i pojazdom na drodze poprzecznej (którzy mają obecnie zielone światło), możesz kontynuować manewr.',
                'mistake' => 'Niezatrzymanie pojazdu do zera przed strzałką. Przetoczenie się na "zielonej strzałce" to natychmiastowo przerwany egzamin i wysoki mandat.',
                'faq_duty' => 'Zezwala na warunkowy skręt przy czerwonym świetle, ale narzuca obowiązek zachowania się jak przy znaku STOP.',
                'faq_mistake' => 'Brak całkowitego zatrzymania kół pojazdu przed sygnalizatorem.',
                'editorial_notes' => 'Najważniejszy sygnalizator dla kursantów. Must-have dla frazy zielona strzałka.',
                'meta_title' => 'S-2 sygnalizator z warunkowym zezwoleniem na skręt w prawo',
                'meta_description' => 'S-2 to zielona strzałka warunkowa, m.in. do skrętu w prawo. Sprawdź obowiązek zatrzymania i zasady ustępowania pierwszeństwa.',
            ],
            'S-3a' => [
                'intro_definition' => 'S-3a to sygnalizator kierunkowy z zielonymi strzałkami na wprost i w lewo. Dotyczy tylko kierujących jadących w kierunkach pokazanych strzałkami.',
                'meaning' => 'Zielony sygnał kierunkowy na wprost i w lewo oznacza zezwolenie na jazdę w tych kierunkach oraz brak kolizji z innymi uczestnikami ruchu podczas przejazdu.',
                'placement' => 'Stosowany przy pasach, z których organizacja ruchu dopuszcza jazdę na wprost i skręt w lewo, zwykle na większych skrzyżowaniach z wydzielonymi fazami sygnalizacji.',
                'behavior' => 'Jedź na wprost albo skręć w lewo zgodnie ze strzałkami. Nie traktuj tego sygnału jako zgody na inny kierunek. Przy lewoskręcie nie czekasz na lukę w ruchu z przeciwka tak jak przy S-1, bo sygnał kierunkowy jest bezkolizyjny.',
                'fine_summary' => 'Jazda w kierunku innym niż wskazany przez sygnalizator kierunkowy albo wjazd mimo braku sygnału zezwalającego jest poważnym naruszeniem zasad ruchu.',
                'mistake' => 'Najczęstszy błąd to zatrzymywanie się w środku skrzyżowania jak przy zwykłym zielonym S-1 albo próba zawracania, gdy sygnał pokazuje tylko jazdę na wprost i w lewo.',
                'faq_duty' => 'Pozwala jechać tylko na wprost lub w lewo i oznacza, że te relacje są bezkolizyjne.',
                'faq_mistake' => 'Mylenie sygnalizatora kierunkowego z ogólnym S-1, szczególnie przy skręcie w lewo.',
                'editorial_notes' => 'Warto połączyć z P-8a/P-8b, bo sygnalizator kierunkowy trzeba czytać razem z kierunkami na pasach.',
                'meta_title' => 'S-3a sygnalizator kierunkowy na wprost i w lewo',
                'meta_description' => 'S-3a zezwala na jazdę na wprost i w lewo bez kolizji z innym ruchem. Sprawdź, czym różni się od S-1 i zielonej strzałki S-2.',
            ],
            'S-3b' => [
                'intro_definition' => 'S-3b to sygnalizator kierunkowy z zielonymi strzałkami na wprost i w prawo. Dotyczy wyłącznie jazdy w kierunkach pokazanych na sygnale.',
                'meaning' => 'Zielony sygnał kierunkowy na wprost i w prawo oznacza zezwolenie na przejazd w tych kierunkach oraz brak kolizji z ruchem przecinającym wskazany tor jazdy.',
                'placement' => 'Występuje na skrzyżowaniach z wydzielonym sterowaniem dla pasów prowadzących na wprost i w prawo.',
                'behavior' => 'Jedź tylko na wprost lub w prawo. Mimo bezkolizyjnej fazy nadal obserwuj sytuację, ale nie zatrzymuj się bez potrzeby tak, jak przy warunkowej zielonej strzałce S-2.',
                'fine_summary' => 'Zignorowanie kierunku wskazanego przez S-3b albo wykonanie manewru spoza pokazanej relacji może zostać ocenione jak niezastosowanie się do sygnalizacji.',
                'mistake' => 'Najczęstszy błąd to traktowanie S-3b jak zwykłego zielonego światła dla całego pasa i wykonywanie innego manewru niż wskazany.',
                'faq_duty' => 'Zezwala na jazdę na wprost lub w prawo w fazie bezkolizyjnej.',
                'faq_mistake' => 'Zatrzymywanie się przed skrętem w prawo jak przy zielonej strzałce warunkowej. Przy S-3 nie ma obowiązku zatrzymania do zera.',
                'editorial_notes' => 'Dobry kontrast z S-2: zielona strzałka warunkowa wymaga zatrzymania, a S-3b jest kierunkowe i bezkolizyjne.',
                'meta_title' => 'S-3b sygnalizator kierunkowy na wprost i w prawo',
                'meta_description' => 'S-3b pozwala jechać na wprost lub w prawo w fazie bezkolizyjnej. Zobacz różnicę między S-3b a zieloną strzałką warunkową.',
            ],
            'S-3c' => [
                'intro_definition' => 'S-3c to sygnalizator kierunkowy ze strzałką na wprost. Dotyczy wyłącznie jazdy prosto w kierunku pokazanym sygnałem.',
                'meaning' => 'Zielony sygnał kierunkowy na wprost oznacza zezwolenie na jazdę prosto oraz brak kolizji z ruchem przecinającym tor jazdy w tej fazie sygnalizacji.',
                'placement' => 'Stosowany przy pasach przeznaczonych do jazdy na wprost, gdy organizacja ruchu przewiduje osobną, kierunkową fazę sygnalizacji.',
                'behavior' => 'Jedź prosto zgodnie ze strzałką i oznakowaniem pasa. Nie traktuj S-3c jak ogólnego zielonego S-1 — sygnał dotyczy tylko kierunku wskazanego na sygnalizatorze.',
                'fine_summary' => 'Skręt w lewo, skręt w prawo albo zawracanie przy sygnale S-3c może zostać potraktowane jako niezastosowanie się do sygnalizacji kierunkowej i oznakowania pasa.',
                'mistake' => 'Najczęstszy błąd to uznanie zielonej strzałki na wprost za zgodę na wszystkie manewry z pasa. S-3c pozwala jechać tylko prosto.',
                'faq_duty' => 'Zezwala wyłącznie na jazdę na wprost w fazie bezkolizyjnej.',
                'faq_mistake' => 'Wykonywanie skrętu mimo sygnału pokazującego tylko kierunek na wprost.',
                'editorial_notes' => 'Warto zestawiać z zielonym sygnałem S-1, bo oba pozwalają ruszyć, ale S-3c jest sygnałem kierunkowym i ogranicza kierunek jazdy.',
                'meta_title' => 'S-3c sygnalizator kierunkowy na wprost',
                'meta_description' => 'S-3c zezwala na jazdę na wprost w fazie kierunkowej. Sprawdź, czym różni się od zwykłego zielonego światła S-1.',
            ],
            'S-3d' => [
                'intro_definition' => 'S-3d to sygnalizator kierunkowy ze strzałką w prawo. Zezwala wyłącznie na skręt w prawo w fazie kierunkowej.',
                'meaning' => 'Zielony sygnał kierunkowy w prawo oznacza zezwolenie na skręt w prawo oraz brak kolizji z ruchem przecinającym tor jazdy w tej fazie sygnalizacji.',
                'placement' => 'Stosowany przy pasach przeznaczonych do skrętu w prawo, gdy organizacja ruchu przewiduje osobną fazę dla tej relacji.',
                'behavior' => 'Skręć w prawo zgodnie ze strzałką i oznakowaniem pasa. Nie zatrzymuj się jak przy zielonej strzałce warunkowej S-2, bo S-3d jest sygnałem kierunkowym, a nie warunkowym zezwoleniem.',
                'fine_summary' => 'Jazda w innym kierunku niż wskazany przez S-3d albo wjazd bez nadawanego sygnału kierunkowego może być potraktowany jako niezastosowanie się do sygnalizacji.',
                'mistake' => 'Najczęstszy błąd to mylenie S-3d z zieloną strzałką warunkową S-2 i niepotrzebne zatrzymanie albo ustępowanie, mimo że sygnał kierunkowy daje fazę bezkolizyjną.',
                'faq_duty' => 'Zezwala wyłącznie na skręt w prawo w kierunku pokazanym strzałką.',
                'faq_mistake' => 'Traktowanie S-3d jak S-2. Przy S-3d nie ma obowiązku zatrzymania do zera przed skrętem.',
                'editorial_notes' => 'Dobry wpis do linkowania z S-2, bo użytkownicy często mylą strzałkę kierunkową z warunkową zieloną strzałką.',
                'meta_title' => 'S-3d sygnalizator kierunkowy w prawo',
                'meta_description' => 'S-3d pozwala skręcić w prawo w fazie kierunkowej. Sprawdź, czym różni się od zielonej strzałki warunkowej S-2.',
            ],
            'S-3e' => [
                'intro_definition' => 'S-3e to sygnalizator kierunkowy ze strzałką w lewo. Zezwala wyłącznie na skręt w lewo w kierunku wskazanym sygnałem.',
                'meaning' => 'Zielony sygnał kierunkowy w lewo oznacza zezwolenie na skręt w lewo w fazie bezkolizyjnej. Sam S-3e nie jest zgodą na zawracanie.',
                'placement' => 'Stosowany przy pasach do skrętu w lewo na skrzyżowaniach z wydzieloną fazą sygnalizacji dla lewoskrętu.',
                'behavior' => 'Skręć w lewo płynnie z właściwego pasa. Nie zatrzymuj się w oczekiwaniu na pojazdy z przeciwka tak jak przy zwykłym zielonym S-1, bo sygnalizator kierunkowy oznacza przejazd bezkolizyjny.',
                'fine_summary' => 'Zawracanie albo jazda w innym kierunku niż wskazany przez S-3e może zostać oceniona jako naruszenie sygnałów i oznakowania pasa.',
                'mistake' => 'Najczęstszy błąd to założenie, że każda zielona strzałka w lewo pozwala także zawracać. Do zawracania służą inne warianty, np. S-3f albo S-3g, jeśli organizacja ruchu to przewiduje.',
                'faq_duty' => 'Zezwala na skręt w lewo w fazie bezkolizyjnej.',
                'faq_mistake' => 'Automatyczne zawracanie przy S-3e. Ten sygnał pokazuje skręt w lewo, nie manewr zawracania.',
                'editorial_notes' => 'Warto linkować z S-3f i S-3g, bo różnica między lewoskrętem a zawracaniem jest częstą pułapką egzaminacyjną.',
                'meta_title' => 'S-3e sygnalizator kierunkowy w lewo',
                'meta_description' => 'S-3e zezwala na skręt w lewo w fazie bezkolizyjnej. Sprawdź, dlaczego nie oznacza automatycznej zgody na zawracanie.',
            ],
            'S-3f' => [
                'intro_definition' => 'S-3f to sygnalizator kierunkowy w lewo, który zezwala także na zawracanie. To sygnał bezkolizyjny dla kierujących jadących we wskazanych relacjach.',
                'meaning' => 'Zielony sygnał kierunkowy S-3f pozwala skręcić w lewo oraz zawrócić, o ile kierujący znajduje się na właściwym pasie i nie zabrania tego inna organizacja ruchu.',
                'placement' => 'Stosowany przy pasach do skrętu w lewo, na których organizacja ruchu przewiduje również zawracanie w tej samej fazie sygnalizacji.',
                'behavior' => 'Wykonaj skręt w lewo albo zawracanie płynnie i zgodnie z pasem ruchu. Nie zatrzymuj się w oczekiwaniu na pojazdy z przeciwka, bo zielony sygnał kierunkowy oznacza przejazd bezkolizyjny.',
                'fine_summary' => 'Nieprawidłowe zawracanie mimo sygnalizatora kierunkowego, zwłaszcza z niewłaściwego pasa, może być ocenione jako naruszenie znaków, sygnałów i zasad zmiany kierunku jazdy.',
                'mistake' => 'Najczęstszy błąd to rezygnacja z zawracania mimo sygnału, który je dopuszcza, albo zawracanie z pasa, który nie jest do tego przeznaczony.',
                'faq_duty' => 'Zezwala na skręt w lewo oraz zawracanie w fazie bezkolizyjnej.',
                'faq_mistake' => 'Mylenie S-3f z zakazem zawracania lub z ogólnym S-1, przy którym zawracanie trzeba oceniać przez inne znaki i układ skrzyżowania.',
                'editorial_notes' => 'Powiązać z B-23 i P-8c, bo to naturalny klaster pytań egzaminacyjnych o zawracanie.',
                'meta_title' => 'S-3f sygnalizator w lewo zezwalający na zawracanie',
                'meta_description' => 'S-3f pozwala skręcić w lewo i zawrócić w fazie bezkolizyjnej. Sprawdź, jak odróżnić go od zwykłego S-3 i od zielonej strzałki S-2.',
            ],
            'S-3g' => [
                'intro_definition' => 'S-3g to sygnalizator kierunkowy przeznaczony dla kierujących zawracających. Pokazuje relację zawracania jako osobny, bezkolizyjny kierunek jazdy.',
                'meaning' => 'Zielony sygnał S-3g zezwala na zawracanie w kierunku pokazanym strzałką. Oznacza, że dla tego manewru nie występuje kolizja z innymi uczestnikami ruchu.',
                'placement' => 'Stosowany na skrzyżowaniach, gdzie zawracanie jest wydzielone jako osobna relacja, najczęściej z pasa przeznaczonego do zawracania.',
                'behavior' => 'Zawracaj wyłącznie z właściwego pasa i zgodnie z torem wskazanym przez oznakowanie. Nie wykonuj skrętu w lewo, jeśli sygnał i układ pasa przewidują tylko zawracanie.',
                'fine_summary' => 'Wykonanie innego manewru niż wskazany przez S-3g może zostać potraktowane jako niezastosowanie się do sygnalizacji kierunkowej i oznakowania pasa.',
                'mistake' => 'Najczęstszy błąd to traktowanie sygnału dla zawracania jako ogólnej zgody na skręt w lewo. S-3g wskazuje konkretny manewr.',
                'faq_duty' => 'Zezwala na zawracanie w fazie bezkolizyjnej, z pasa i w kierunku wskazanym organizacją ruchu.',
                'faq_mistake' => 'Skręcenie w lewo zamiast zawrócenia albo wykonanie manewru z niewłaściwego pasa.',
                'editorial_notes' => 'Trzymać blisko S-3f i P-8c, żeby użytkownik szybko rozumiał różnicę między skrętem w lewo a zawracaniem.',
                'meta_title' => 'S-3g sygnalizator kierunkowy dla zawracających',
                'meta_description' => 'S-3g wskazuje bezkolizyjne zawracanie. Sprawdź, kiedy wolno zawrócić i dlaczego nie należy mylić go ze skrętem w lewo.',
            ],
        ];
    }

    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array{meaning: string, placement: string, behavior: string, mistake: string, faq_duty: string, faq_mistake: string, editorial_notes: string}
     */
    protected function fallbackConfig(array $sign): array
    {
        $lowerName = Str::of($sign['name'])->lcfirst()->toString();
        $displayCode = $this->displayCode($sign['code']);

        return [
            'meaning' => "Sygnalizator {$displayCode}, nazywany powszechnie {$lowerName}, to urządzenie do kierowania ruchem o najwyższym priorytecie.",
            'placement' => 'Umiejscowiony bezpośrednio na skrzyżowaniach, przejściach dla pieszych i w miejscach o wzmożonym ruchu.',
            'behavior' => 'Sygnały świetlne wykluczają potrzebę stosowania się do znaków ustalających pierwszeństwo przejazdu, jednak nie zwalniają z zasady ograniczonego zaufania.',
            'mistake' => 'Najczęstszy błąd to "późne żółte" - wjeżdżanie na skrzyżowanie, gdy można było się jeszcze bezpiecznie zatrzymać.',
            'faq_duty' => 'Kieruje potokami ruchu i chroni przed kolizjami z innych relacji.',
            'faq_mistake' => 'Nadmierna prędkość przy zbliżaniu się do sygnalizacji, w efekcie hamowanie awaryjne.',
            'editorial_notes' => 'Przy następnej aktualizacji można uzupełnić bazę o sygnalizatory dla rowerzystów i tramwajów.',
        ];
    }

    protected function assetPath(string $slug): string
    {
        return 'traffic-signs/signs/signals/znak-'.$slug.'.webp';
    }

    protected function displayCode(string $code): string
    {
        return match ($code) {
            'S-1a', 'S-1b', 'S-1c', 'S-1d' => 'S-1',
            default => $code,
        };
    }
}
