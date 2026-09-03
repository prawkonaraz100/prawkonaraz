<?php

namespace App\Support;

use Illuminate\Support\Str;

class PolishHorizontalSignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $config = $this->horizontalConfig()[$sign['code']] ?? $this->fallbackConfig($sign);
        $assetPath = $this->assetPath($sign['slug']);
        $imageAlt = $sign['name'];

        return [
            'intro_definition' => $config['intro_definition'] ?? "Znak {$sign['code']} to poziomy znak drogowy, naklejany lub malowany bezpośrednio na nawierzchni jezdni. Znaki poziome służą do organizacji ruchu, wyznaczania pasów oraz wskazywania miejsc zatrzymania lub przejść.",
            'meaning' => $config['meaning'],
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => $config['legal_summary'] ?? 'Znaki poziome (grupa P) są pełnoprawnymi znakami drogowymi, na równi ze znakami pionowymi. Niezastosowanie się do linii ciągłych czy wyznaczonych stref zatrzymania jest wykroczeniem nierzadko surowiej karanym niż błędy przy znakach ostrzegawczych.',
            'fine_summary' => $config['fine_summary'] ?? 'Najeżdżanie lub przekraczanie linii ciągłych (pojedynczych i podwójnych) czy też niezatrzymanie się przed właściwą linią warunkowego zatrzymania grozi wysokim mandatem oraz punktami karnymi. Wyprzedzanie na przejściu dla pieszych to jedno z najsurowiej karanych przewinień.',
            'common_mistakes' => $config['mistake'],
            'editorial_notes' => $config['editorial_notes'],
            'review_notes' => 'Szkic przygotowany dla implementacji znaków grupy P (Znaki poziome). Gotowy pod dodanie docelowych grafik obrazujących zachowanie na drodze.',
            'source_notes' => 'Baza wiedzy oparta na rozporządzeniu w sprawie znaków i sygnałów drogowych ze szczególnym uwzględnieniem organizacji pasów ruchu.',
            'faq_items' => [
                [
                    'question' => "O czym decyduje znak poziomy {$sign['code']}?",
                    'answer' => $config['faq_duty'],
                ],
                [
                    'question' => "Jakie błędy kierowcy popełniają przy znaku {$sign['code']}?",
                    'answer' => $config['faq_mistake'],
                ],
            ],
            'meta_title' => $config['meta_title'] ?? "Znak {$sign['code']} - znaczenie, mandat i zasady na drodze",
            'meta_description' => $config['meta_description'] ?? "Dowiedz się, jak prawidłowo interpretować znak poziomy {$sign['code']}. Sprawdź, co wolno Ci zrobić na jezdni, a co grozi wysokim mandatem.",
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
    protected function horizontalConfig(): array
    {
        return [
            'P-1' => [
                'meaning' => 'Linia pojedyncza przerywana rozdziela pasy ruchu. Długość kresek i przerw między nimi może sygnalizować, czy zbliżasz się do niebezpiecznego miejsca (kreski stają się dłuższe).',
                'placement' => 'Pomiędzy pasami ruchu o tym samym, bądź przeciwnym kierunku na odcinkach o standardowym poziomie bezpieczeństwa.',
                'behavior' => 'Możesz najeżdżać na tę linię i przekraczać ją w celu wyprzedzania, omijania lub zmiany pasa ruchu, pod warunkiem zachowania szczególnej ostrożności i upewnienia się, że manewr jest bezpieczny.',
                'mistake' => 'Powszechnym błędem jest jazda w taki sposób, że samochód stale znajduje się okrakiem na linii, co jest zabronione. Linię przekraczamy tylko na czas manewru.',
                'faq_duty' => 'Pozwala na zmianę pasa ruchu lub wyprzedzanie, jeśli warunki na to pozwalają.',
                'faq_mistake' => 'Zapominanie o włączeniu kierunkowskazu przed przekroczeniem linii.',
                'editorial_notes' => 'Podstawowy znak, trzeba podkreślić różnicę między P-1 a linią ostrzegawczą P-6.',
            ],
            'P-2' => [
                'meaning' => 'Linia pojedyncza ciągła oddziela pasy ruchu o tym samym kierunku. Bezwzględnie zabrania zmieniania pasa ruchu oraz najeżdżania na nią.',
                'placement' => 'Najczęściej w obrębie skrzyżowań, przejść dla pieszych czy wysepek, gdzie nagła zmiana pasa ruchu stanowiłaby duże zagrożenie.',
                'behavior' => 'Musisz pozostać na swoim pasie ruchu. Nie wolno Ci najechać na linię nawet jednym kołem.',
                'mistake' => 'Omijanie pojazdu skręcającego lub wyprzedzanie rowerzysty poprzez najechanie lewymi kołami na linię ciągłą.',
                'faq_duty' => 'Zabrania zmiany pasa ruchu i najeżdżania na linię.',
                'faq_mistake' => 'Tłumaczenie się "tylko lekkim najechaniem" - przepisy traktują to równie surowo co pełne przekroczenie.',
                'editorial_notes' => 'Kluczowe przy omawianiu zachowania na dojazdach do skrzyżowań.',
            ],
            'P-2Y' => [
                'intro_definition' => 'Żółta linia pojedyncza ciągła to tymczasowe oznakowanie poziome stosowane najczęściej przy robotach drogowych albo czasowej zmianie organizacji ruchu. Jeżeli koliduje z białymi znakami poziomymi, kierowca powinien stosować się do oznakowania żółtego.',
                'meaning' => 'Żółta linia pojedyncza ciągła działa jak linia ciągła w tymczasowej organizacji ruchu: wyznacza granicę pasa i zakazuje najeżdżania na nią oraz jej przekraczania.',
                'placement' => 'Stosuje się ją na odcinkach z czasowo zmienionym przebiegiem pasów, przy zwężeniach, objazdach, remontach albo na jezdniach, gdzie dotychczasowe białe linie nie odpowiadają aktualnej organizacji ruchu.',
                'behavior' => 'Jedź zgodnie z żółtą linią, nawet jeśli biała linia na jezdni sugeruje inny tor jazdy. Nie zmieniaj pasa przez żółtą linię ciągłą i nie najeżdżaj na nią kołem.',
                'legal_summary' => 'Żółte znaki poziome oznaczają organizację czasową. W razie sprzeczności z białym oznakowaniem pierwszeństwo ma oznakowanie żółte, dlatego kierowca powinien traktować je jako aktualną instrukcję przebiegu pasa ruchu.',
                'fine_summary' => 'Zignorowanie żółtej linii ciągłej może zostać potraktowane tak samo jak naruszenie zwykłej linii ciągłej, zwłaszcza gdy manewr powoduje zagrożenie w miejscu robót drogowych lub zwężenia.',
                'mistake' => 'Najczęstszy błąd to jazda według starych białych linii i przecięcie żółtej linii ciągłej, bo kierowca zakłada, że żółte oznakowanie jest tylko pomocnicze.',
                'faq_duty' => 'Nakazuje trzymać się tymczasowego przebiegu pasa i zabrania najeżdżania na żółtą linię ciągłą.',
                'faq_mistake' => 'Ignorowanie żółtego oznakowania, gdy na jezdni nadal widoczne są stare białe linie.',
                'editorial_notes' => 'Podkreślić zasadę pierwszeństwa żółtego oznakowania tymczasowego nad białym oznakowaniem stałym.',
                'meta_title' => 'Żółta linia pojedyncza ciągła - znaczenie i zasady jazdy',
                'meta_description' => 'Sprawdź, co oznacza żółta linia pojedyncza ciągła, kiedy ma pierwszeństwo przed białym oznakowaniem i jak zachować się przy tymczasowej organizacji ruchu.',
            ],
            'P-3' => [
                'meaning' => 'Linia jednostronnie przekraczalna łączy linię ciągłą z przerywaną. Można ją przekroczyć od strony linii przerywanej, natomiast od strony linii ciągłej obowiązuje zakaz przejeżdżania przez nią i najeżdżania na nią, z wyjątkiem powrotu po wyprzedzaniu na wcześniej zajmowany pas położony przy linii przerywanej.',
                'placement' => 'Stosuje się ją tam, gdzie możliwość wykonania manewru zależy od kierunku jazdy, widoczności i układu drogi, między innymi przed łukami, wzniesieniami lub innymi miejscami wymagającymi ograniczenia manewrów tylko dla jednej strony.',
                'behavior' => 'Sprawdź, która część znaku znajduje się po Twojej stronie. Od strony przerywanej możesz przekroczyć linię po upewnieniu się, że manewr jest bezpieczny. Od strony ciągłej nie rozpoczynaj wyprzedzania ani zmiany pasa.',
                'mistake' => 'Kierowcy patrzą na cały znak zamiast na linię po swojej stronie i uznają, że obecność części przerywanej zawsze pozwala na przekroczenie oznakowania.',
                'faq_duty' => 'Pozwala przekroczyć oznakowanie od strony linii przerywanej, a od strony ciągłej zasadniczo tego zabrania.',
                'faq_mistake' => 'Rozpoczynanie manewru od strony linii ciągłej tylko dlatego, że obok widoczna jest linia przerywana.',
                'editorial_notes' => 'W materiale wizualnym wyraźnie wskazać, że decyduje linia znajdująca się po stronie kierującego.',
            ],
            'P-4' => [
                'meaning' => 'Linia podwójna ciągła rozdziela pasy ruchu o kierunkach przeciwnych. Oznacza całkowity zakaz przejeżdżania przez nią oraz najeżdżania na nią.',
                'placement' => 'Na odcinkach dróg dwukierunkowych o ograniczonej widoczności, przed zakrętami, wzniesieniami i na skrzyżowaniach.',
                'behavior' => 'Nawet jeśli jedziesz za wolno poruszającym się pojazdem rolniczym, nie wolno Ci rozpocząć wyprzedzania, jeśli wiązałoby się to z najechaniem na linię podwójną ciągłą.',
                'mistake' => 'Wyprzedzanie "na trzeciego" lub rozpoczynanie wyprzedzania z nadzieją powrotu przed początkiem linii ciągłej.',
                'faq_duty' => 'Stanowi "wirtualny mur" między pasami o przeciwnych kierunkach jazdy.',
                'faq_mistake' => 'Skręcanie w lewo (np. na posesję) przez linię podwójną ciągłą - jest to kategorycznie zabronione.',
                'editorial_notes' => 'Bardzo ważny znak w kontekście bezpieczeństwa drogowego i zderzeń czołowych.',
            ],
            'P-4Y' => [
                'intro_definition' => 'Żółta linia podwójna ciągła to tymczasowe oznakowanie poziome, które wyznacza zakaz przekraczania osi jezdni w aktualnej organizacji ruchu. Żółty kolor oznacza, że kierowca powinien stosować się do niej przed oznakowaniem białym.',
                'meaning' => 'Żółta linia podwójna ciągła rozdziela przeciwne kierunki ruchu w organizacji tymczasowej i zakazuje najeżdżania na nią oraz przejeżdżania przez nią.',
                'placement' => 'Występuje przy czasowych zwężeniach, objazdach, remontach i zmianach toru jazdy, szczególnie tam, gdzie ruch dwukierunkowy trzeba oddzielić w sposób bardzo czytelny.',
                'behavior' => 'Traktuj żółtą podwójną ciągłą jak nieprzekraczalną granicę. Nie wyprzedzaj, nie omijaj i nie skręcaj przez nią, jeśli wymagałoby to najechania na linię.',
                'legal_summary' => 'Tymczasowe żółte oznakowanie organizuje ruch w pierwszej kolejności. Gdy żółta linia podwójna ciągła różni się od białej, kierujący powinien jechać według żółtej linii.',
                'fine_summary' => 'Najechanie lub przejechanie przez żółtą linię podwójną ciągłą może skutkować mandatem i punktami karnymi, a w rejonie robót drogowych często wiąże się też z realnym zagrożeniem czołowym.',
                'mistake' => 'Błędem jest traktowanie żółtej podwójnej ciągłej jako luźnej sugestii objazdu i wykonywanie manewru według starej, białej osi jezdni.',
                'faq_duty' => 'Oddziela przeciwne kierunki ruchu w organizacji tymczasowej i zabrania przekraczania tej granicy.',
                'faq_mistake' => 'Wyprzedzanie wolniejszego pojazdu na odcinku z żółtą podwójną ciągłą.',
                'editorial_notes' => 'Pokazać w kontekście robót drogowych i zasady pierwszeństwa oznakowania tymczasowego.',
                'meta_title' => 'Żółta linia podwójna ciągła - co oznacza i czego zabrania',
                'meta_description' => 'Wyjaśniamy żółtą linię podwójną ciągłą: kiedy ma pierwszeństwo przed białą linią, jak wyznacza ruch tymczasowy i jakich manewrów zabrania.',
            ],
            'P-7a' => [
                'meaning' => 'Linia krawędziowa przerywana wyznacza krawędź jezdni. Jej przerwana forma odróżnia ją od linii P-7b, która dodatkowo zabrania kierującemu pojazdem samochodowym wjazdu na pobocze.',
                'placement' => 'Malowana jest przy prawej albo lewej krawędzi jezdni, szczególnie na odcinkach, na których trzeba czytelnie oddzielić jezdnię od pobocza.',
                'behavior' => 'Traktuj linię jako czytelną granicę jezdni. Każdy zjazd poza jej przebieg wykonuj tylko wtedy, gdy jest dozwolony, potrzebny i bezpieczny.',
                'mistake' => 'Mylenie P-7a z linią rozdzielającą pasy ruchu albo zakładanie, że przerywana krawędź jezdni automatycznie tworzy dodatkowy pas.',
                'faq_duty' => 'Wyznacza krawędź jezdni i pomaga utrzymać prawidłowy tor jazdy.',
                'faq_mistake' => 'Jazda po poboczu tak, jakby było ono zwykłym pasem ruchu.',
                'editorial_notes' => 'Zestawić z P-7b i wyjaśnić różnicę dotyczącą wjazdu pojazdu samochodowego na pobocze.',
            ],
            'P-7b' => [
                'meaning' => 'Linia krawędziowa ciągła wyznacza krawędź jezdni i oznacza zakaz wjazdu na pobocze dla kierującego pojazdem samochodowym.',
                'placement' => 'Występuje przy krawędzi jezdni na odcinkach, na których wjazd pojazdów samochodowych na pobocze powinien być wykluczony.',
                'behavior' => 'Prowadź pojazd po stronie jezdni i nie najeżdżaj na linię w celu jazdy poboczem lub omijania innych uczestników ruchu.',
                'mistake' => 'Wykorzystywanie pobocza do omijania korka, przepuszczania szybszego pojazdu albo kontynuowania jazdy obok pasa ruchu.',
                'faq_duty' => 'Wyznacza krawędź jezdni i zabrania pojazdom samochodowym wjazdu na pobocze.',
                'faq_mistake' => 'Traktowanie przestrzeni za linią jako dodatkowego pasa do jazdy.',
                'editorial_notes' => 'Podkreślić dodatkowy zakaz odróżniający P-7b od P-7a.',
            ],
            'P-8a' => [
                'meaning' => 'Strzałka kierunkowa na wprost oznacza, że z pasa ruchu, na którym ją umieszczono, wolno jechać tylko na wprost, chyba że symbol występuje w połączeniu z inną strzałką kierunkową.',
                'placement' => 'Najczęściej występuje na pasach dojazdowych do skrzyżowań oraz w miejscach, gdzie poszczególnym pasom przypisano dozwolone kierunki ruchu.',
                'behavior' => 'Przed skrzyżowaniem zajmij pas zgodny z planowanym kierunkiem jazdy. Po znalezieniu się na pasie oznaczonym wyłącznie P-8a kontynuuj jazdę na wprost.',
                'mistake' => 'Skręcanie z pasa oznaczonego wyłącznie strzałką na wprost albo późna, gwałtowna zmiana pasa tuż przed skrzyżowaniem.',
                'faq_duty' => 'Ogranicza dozwolony kierunek jazdy z danego pasa do jazdy na wprost.',
                'faq_mistake' => 'Ignorowanie strzałki, gdy sygnalizator ogólny zezwala na ruch.',
                'editorial_notes' => 'Powiązać z zasadą wcześniejszego wyboru pasa przed skrzyżowaniem.',
            ],
            'P-8aY' => [
                'intro_definition' => 'Żółta strzałka kierunkowa na wprost to tymczasowe oznakowanie poziome, które wskazuje aktualny kierunek jazdy z danego pasa. Żółte strzałki stosuje się przy czasowej zmianie organizacji ruchu i mają one pierwszeństwo przed sprzecznymi białymi strzałkami.',
                'meaning' => 'Żółta strzałka na wprost oznacza, że z pasa oznaczonego tą strzałką należy jechać zgodnie z czasową organizacją ruchu na wprost.',
                'placement' => 'Malowana jest na pasach dojazdowych do skrzyżowań, przewiązek, zwężeń albo objazdów, gdy dotychczasowy kierunek jazdy z pasa został zmieniony na czas robót lub innej organizacji tymczasowej.',
                'behavior' => 'Ustaw pojazd na właściwym pasie wcześniej i jedź zgodnie z żółtą strzałką. Jeżeli biała strzałka pokazuje inny kierunek, kieruj się oznakowaniem żółtym.',
                'legal_summary' => 'Żółte znaki poziome informują o czasowej organizacji ruchu i w razie rozbieżności są ważniejsze od białych znaków poziomych pozostawionych na nawierzchni.',
                'fine_summary' => 'Jazda w innym kierunku niż wskazany żółtą strzałką może zostać potraktowana jako niezastosowanie się do oznakowania poziomego i spowodować mandat, szczególnie gdy przecinasz tor innych pojazdów.',
                'mistake' => 'Najczęściej kierowcy jadą według zapamiętanego układu skrzyżowania albo białej strzałki, ignorując żółtą strzałkę na jezdni.',
                'faq_duty' => 'Wskazuje obowiązujący kierunek jazdy z pasa w tymczasowej organizacji ruchu.',
                'faq_mistake' => 'Wykonywanie skrętu z pasa, na którym żółta strzałka wskazuje jazdę na wprost.',
                'editorial_notes' => 'W treści warto przypomnieć, że żółte strzałki są typowe dla robót drogowych i objazdów.',
                'meta_title' => 'Żółta strzałka kierunkowa na wprost - znaczenie na jezdni',
                'meta_description' => 'Zobacz, co oznacza żółta strzałka kierunkowa na wprost i dlaczego przy czasowej organizacji ruchu ma pierwszeństwo przed białymi strzałkami.',
            ],
            'P-8b' => [
                'meaning' => 'Strzałka kierunkowa do skręcania oznacza, że z pasa ruchu, na którym ją umieszczono, wolno jechać tylko w kierunku wskazanym strzałką. Symbol może być również połączony ze strzałką na wprost.',
                'placement' => 'Umieszczana jest na pasach przeznaczonych do skrętu przed skrzyżowaniami i innymi miejscami rozdzielenia kierunków jazdy.',
                'behavior' => 'Zajmij odpowiedni pas odpowiednio wcześnie i wykonaj skręt zgodnie z kierunkiem wskazanym na jezdni. Strzałka skrętu w lewo na skrajnym lewym pasie zezwala także na zawracanie, chyba że zabrania tego B-23 albo ruch jest kierowany sygnalizatorem S-3.',
                'mistake' => 'Jazda na wprost z pasa do skrętu albo automatyczne zawracanie przy sygnalizatorze kierunkowym S-3 bez sprawdzenia, czy wskazany sygnał na to pozwala.',
                'faq_duty' => 'Nakazuje wybrać z danego pasa kierunek wskazany strzałką.',
                'faq_mistake' => 'Zmiana decyzji na skrzyżowaniu i przecięcie toru pojazdów jadących z sąsiedniego pasa.',
                'editorial_notes' => 'Dodać praktyczne porównanie strzałki w lewo z osobną strzałką P-8c do zawracania.',
            ],
            'P-8bY-L' => [
                'intro_definition' => 'Żółta strzałka kierunkowa do skręcania w lewo wskazuje tymczasowo obowiązujący kierunek jazdy z pasa. Jeżeli na jezdni widzisz jednocześnie stare białe strzałki, pierwszeństwo ma żółte oznakowanie.',
                'meaning' => 'Żółta strzałka do skręcania w lewo oznacza, że z oznaczonego pasa należy skręcić w lewo zgodnie z czasową organizacją ruchu.',
                'placement' => 'Stosuje się ją przed skrzyżowaniami, przewiązkami i objazdami, gdy na czas robót albo przebudowy zmieniono przypisanie kierunków do pasów ruchu.',
                'behavior' => 'Przed dojazdem do skrzyżowania wybierz pas zgodny z żółtym oznakowaniem. Z pasa z żółtą strzałką w lewo wykonaj skręt w lewo, obserwując sygnalizację, znaki pionowe i uczestników ruchu.',
                'legal_summary' => 'Żółta strzałka jest elementem czasowej organizacji ruchu. Jeżeli wskazuje inny kierunek niż biała strzałka, kierujący powinien zastosować się do żółtego oznakowania.',
                'fine_summary' => 'Nieprawidłowa jazda z pasa oznaczonego żółtą strzałką może skutkować mandatem, zwłaszcza gdy kierowca pojedzie według starego układu pasów i wjedzie w tor ruchu innych pojazdów.',
                'mistake' => 'Częsty błąd to jazda na wprost lub skręt w innym kierunku, bo kierowca sugeruje się starym oznakowaniem białym albo nawykiem z wcześniejszej organizacji skrzyżowania.',
                'faq_duty' => 'Nakazuje jechać z danego pasa w lewo zgodnie z tymczasową organizacją ruchu.',
                'faq_mistake' => 'Zakładanie, że żółta strzałka jest mniej ważna od białej strzałki namalowanej wcześniej.',
                'editorial_notes' => 'Wskazać, że możliwość zawracania zależy od pozostałych znaków i sygnałów, a sama żółta strzałka w lewo nie znosi zakazów.',
                'meta_title' => 'Żółta strzałka do skręcania w lewo - znaczenie i zasady',
                'meta_description' => 'Wyjaśniamy, co oznacza żółta strzałka kierunkowa do skręcania w lewo i jak stosować się do niej przy czasowej organizacji ruchu.',
            ],
            'P-8bY-R' => [
                'intro_definition' => 'Żółta strzałka kierunkowa do skręcania w prawo wskazuje aktualny, tymczasowy kierunek jazdy z pasa. W razie sprzeczności ze strzałkami białymi należy jechać według oznakowania żółtego.',
                'meaning' => 'Żółta strzałka do skręcania w prawo oznacza, że z oznaczonego pasa należy skręcić w prawo zgodnie z tymczasową organizacją ruchu.',
                'placement' => 'Pojawia się przed skrzyżowaniami, objazdami, zawężeniami i miejscami robót drogowych, gdy pas ruchu czasowo prowadzi w innym kierunku niż zwykle.',
                'behavior' => 'Zajmij pas oznaczony żółtą strzałką odpowiednio wcześnie i wykonaj skręt w prawo zgodnie z jej kierunkiem. Nie jedź według starej białej strzałki, jeśli pokazuje inny kierunek.',
                'legal_summary' => 'Oznakowanie żółte wyznacza tymczasową organizację ruchu i w praktyce porządkuje jazdę przed starymi białymi znakami poziomymi, które mogą być jeszcze widoczne na nawierzchni.',
                'fine_summary' => 'Jazda niezgodna z żółtą strzałką może oznaczać niezastosowanie się do znaku poziomego oraz stworzenie zagrożenia w rejonie robót drogowych, objazdu lub zmienionego układu pasów.',
                'mistake' => 'Najczęstszy błąd to przejechanie na wprost z pasa czasowo przeznaczonego do skrętu w prawo, bo kierowca patrzy na stary układ jezdni.',
                'faq_duty' => 'Nakazuje jechać z danego pasa w prawo zgodnie z czasową organizacją ruchu.',
                'faq_mistake' => 'Ignorowanie żółtej strzałki, gdy pas wygląda znajomo z poprzedniej organizacji ruchu.',
                'editorial_notes' => 'Dodać nacisk na wczesny wybór pasa i uważne czytanie żółtego oznakowania przy remontach.',
                'meta_title' => 'Żółta strzałka do skręcania w prawo - znaczenie na drodze',
                'meta_description' => 'Sprawdź, co oznacza żółta strzałka kierunkowa do skręcania w prawo i jak zachować się przy tymczasowym oznakowaniu pasów.',
            ],
            'P-8c' => [
                'meaning' => 'Strzałka kierunkowa do zawracania oznacza, że z pasa ruchu, na którym ją umieszczono, dozwolona jest jazda w kierunku zawracania wskazanym symbolem.',
                'placement' => 'Stosowana jest na pasach przeznaczonych do zawracania, zwykle przed skrzyżowaniem lub specjalnie przygotowanym miejscem zmiany kierunku jazdy.',
                'behavior' => 'Zawracaj z wyznaczonego pasa, zachowując ostrożność i ustępując uczestnikom ruchu, którym przecinasz tor jazdy.',
                'mistake' => 'Rozpoczynanie zawracania z sąsiedniego pasa albo wykonywanie manewru bez obserwacji pieszych, rowerzystów i pojazdów jadących z innych kierunków.',
                'faq_duty' => 'Wskazuje pas, z którego należy wykonać manewr zawracania.',
                'faq_mistake' => 'Mylenie P-8c ze zwykłą strzałką skrętu w lewo.',
                'editorial_notes' => 'Powiązać z materiałami o zakazie zawracania B-23 i sygnalizatorze S-3.',
            ],
            'P-9' => [
                'meaning' => 'Strzałka naprowadzająca w lewo oznacza nakaz wjazdu na sąsiedni pas ruchu wskazany strzałką, zwykle z powodu kończącego się pasa lub przeszkody.',
                'placement' => 'Malowana jest przed końcem pasa ruchu, zwężeniem albo miejscem, w którym dalsza jazda zajmowanym pasem nie jest możliwa.',
                'behavior' => 'Rozpocznij zmianę pasa odpowiednio wcześnie, zasygnalizuj manewr i ustąp pierwszeństwa pojazdom jadącym pasem, na który wjeżdżasz.',
                'mistake' => 'Dojeżdżanie do samego końca oznakowania bez obserwacji sąsiedniego pasa albo wymuszanie pierwszeństwa podczas zmiany pasa.',
                'faq_duty' => 'Nakazuje wjazd na sąsiedni pas po lewej stronie wskazany strzałką.',
                'faq_mistake' => 'Traktowanie strzałki jako luźnej sugestii zamiast nakazu opuszczenia pasa.',
                'editorial_notes' => 'Pokazać razem z wariantem kierującym w prawo.',
            ],
            'P-9b' => [
                'meaning' => 'Strzałka naprowadzająca w prawo oznacza nakaz wjazdu na sąsiedni pas ruchu wskazany strzałką, zwykle z powodu kończącego się pasa lub przeszkody.',
                'placement' => 'Malowana jest przed końcem pasa ruchu, zwężeniem albo miejscem, w którym kierujący musi opuścić zajmowany pas w kierunku prawej strony.',
                'behavior' => 'Zasygnalizuj zmianę pasa, sprawdź martwe pole i wjedź na prawy pas bez wymuszania pierwszeństwa na jadących nim pojazdach.',
                'mistake' => 'Gwałtowny zjazd w prawo bez kierunkowskazu lub oczekiwanie, że kierujący na sąsiednim pasie zawsze musi zrobić miejsce.',
                'faq_duty' => 'Nakazuje wjazd na sąsiedni pas po prawej stronie wskazany strzałką.',
                'faq_mistake' => 'Pozostawanie na zanikającym pasie mimo kolejnych strzałek naprowadzających.',
                'editorial_notes' => 'Zestawić z wariantem naprowadzającym w lewo i zasadami bezpiecznej zmiany pasa.',
            ],
            'P-10' => [
                'meaning' => 'Przejście dla pieszych, potocznie zwane "zebrą", to powierzchnia jezdni przeznaczona do przechodzenia przez pieszych, którzy w tym miejscu mają pierwszeństwo (również wchodząc na przejście).',
                'placement' => 'W miejscach wyznaczonych do bezpiecznego przekraczania jezdni przez niezmotoryzowanych.',
                'behavior' => 'Zbliżając się do przejścia, musisz zachować szczególną ostrożność, zmniejszyć prędkość i ustąpić pierwszeństwa pieszemu znajdującemu się na przejściu lub wchodzącemu na nie.',
                'mistake' => 'Omijanie pojazdu, który zatrzymał się w celu ustąpienia pierwszeństwa pieszemu, lub wyprzedzanie bezpośrednio przed przejściem.',
                'faq_duty' => 'Wyznacza strefę bezwzględnego pierwszeństwa pieszych.',
                'faq_mistake' => 'Zatrzymywanie pojazdu na przejściu w trakcie oczekiwania w korku.',
                'editorial_notes' => 'P-10 to jeden z najistotniejszych elementów w nowelizacji przepisów drogowych z ostatnich lat.',
            ],
            'P-12' => [
                'meaning' => 'Linia bezwzględnego zatrzymania – stop wskazuje dokładne miejsce zatrzymania pojazdu w związku ze znakiem B-20 STOP albo B-32 „stój – kontrola celna”.',
                'placement' => 'Umieszczana jest poprzecznie do kierunku jazdy przed miejscem, w którym obowiązek pełnego zatrzymania wynika ze znaku pionowego B-20 lub B-32.',
                'behavior' => 'Zatrzymaj pojazd całkowicie przed linią. Dopiero po zatrzymaniu i upewnieniu się, że dalsza jazda jest dozwolona i bezpieczna, kontynuuj manewr.',
                'mistake' => 'Zatrzymanie przednich kół na linii albo za nią oraz wykonanie tak zwanego toczącego zatrzymania bez wyzerowania prędkości.',
                'faq_duty' => 'Wyznacza miejsce obowiązkowego zatrzymania związanego ze znakiem B-20 lub B-32.',
                'faq_mistake' => 'Samo zwolnienie zamiast pełnego zatrzymania pojazdu przed linią.',
                'editorial_notes' => 'Wyraźnie odróżnić od P-13 i P-14, które mają inne zastosowania.',
            ],
            'P-13' => [
                'meaning' => 'Linia warunkowego zatrzymania złożona z trójkątów wyznacza miejsce zatrzymania się w związku ze znakiem A-7 "ustąp pierwszeństwa".',
                'placement' => 'Na wlotach skrzyżowań podporządkowanych oznaczonych znakiem A-7.',
                'behavior' => 'Jeśli sytuacja drogowa tego wymaga (nadjeżdża pojazd z pierwszeństwem), musisz zatrzymać się przed linią złożoną z trójkątów. Jeśli droga jest pusta, linia nie nakazuje bezwzględnego zatrzymania.',
                'mistake' => 'Zatrzymywanie się zbyt wcześnie lub zderzakiem mocno za linią, wymuszając pierwszeństwo.',
                'faq_duty' => 'Pokazuje, dokąd bezpiecznie podjechać, by ustąpić pierwszeństwa na skrzyżowaniu bez konieczności bezwzględnego stopu.',
                'faq_mistake' => 'Mylenie tej linii z koniecznością obowiązkowego zatrzymania (jak przy znaku STOP).',
                'editorial_notes' => 'Wielu kursantów myli P-13 z P-14. Zrobić z tego mocny atut poradnika.',
            ],
            'P-14' => [
                'meaning' => 'Linia warunkowego zatrzymania złożona z prostokątów wskazuje miejsce zatrzymania pojazdu między innymi na wlotach dróg równorzędnych, przed przejściem dla pieszych, przystankiem tramwajowym bez wysepki, przejazdem tramwajowym lub kolejowym, przejazdem dla rowerzystów, śluzą rowerową albo sygnałem świetlnym.',
                'placement' => 'Stosuje się ją w miejscach, w których kierujący powinien zatrzymać pojazd, gdy wymagają tego przepisy, sygnał świetlny albo sytuacja w ruchu.',
                'behavior' => 'Jeżeli zachodzi obowiązek zatrzymania, zatrzymaj pojazd przed linią tak, aby nie wjechać w chronioną przestrzeń przejścia, przejazdu, przystanku lub skrzyżowania.',
                'mistake' => 'Mylenie P-14 z linią P-12 związaną ze znakiem STOP albo zatrzymywanie pojazdu już na przejściu, przejeździe lub za sygnalizatorem.',
                'faq_duty' => 'Wskazuje miejsce zatrzymania, gdy obowiązek ten wynika z sytuacji, przepisów albo sygnałów.',
                'faq_mistake' => 'Zakładanie, że sama linia P-14 w każdej sytuacji działa identycznie jak znak STOP.',
                'editorial_notes' => 'Bezpośrednio porównać P-12, P-13 i P-14, bo ich funkcje są często mylone.',
            ],
            'P-17' => [
                'meaning' => 'Linia przystankowa wyznacza na jezdni miejsce przystanku autobusu, trolejbusu lub tramwaju i oznacza, że zakaz zatrzymywania się innych pojazdów obowiązuje na całej długości tej linii.',
                'placement' => 'Malowana jest wzdłuż krawędzi jezdni w obrębie przystanku transportu publicznego.',
                'behavior' => 'Nie zatrzymuj pojazdu na odcinku oznaczonym linią P-17. Obserwuj pasażerów oraz pojazd komunikacji publicznej włączający się do ruchu.',
                'mistake' => 'Krótkie zatrzymanie „tylko na chwilę” na zygzakowatej linii albo wykorzystanie obszaru przystanku do wysadzania pasażera.',
                'faq_duty' => 'Wyznacza całą długość przystanku, na której inne pojazdy nie mogą się zatrzymywać.',
                'faq_mistake' => 'Uznawanie, że zakaz dotyczy wyłącznie miejsca bezpośrednio przy słupku przystankowym.',
                'editorial_notes' => 'Powiązać ze znakami przystanków D-15, D-16 i D-17.',
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

        return [
            'meaning' => "Znak poziomy {$sign['code']} (opisywany w przepisach jako {$lowerName}) porządkuje ruch i informuje o specyficznej konfiguracji nawierzchni na danym odcinku.",
            'placement' => 'Znak malowany jest na jezdni w miejscu, w którym zaczynają obowiązywać wynikające z niego zasady.',
            'behavior' => 'Zawsze dostosuj swój tor jazdy do wyznaczonych znakami poziomymi pasów ruchu i linii zatrzymania.',
            'mistake' => 'Najczęstszym błędem jest ignorowanie znaków poziomych w sytuacji, gdy na drodze nie ma gęstego ruchu, co prowadzi do przekraczania linii ciągłych.',
            'faq_duty' => 'Pełni funkcję korygującą lub wyznaczającą pas ruchu i strefy wyłączone.',
            'faq_mistake' => 'Traktowanie znaków poziomych jako mniej ważnych od znaków na słupkach.',
            'editorial_notes' => 'Doprecyzować opisy rzadszych znaków poziomych podczas uzupełniania inwentarza P.',
        ];
    }

    protected function assetPath(string $slug): string
    {
        return 'traffic-signs/signs/horizontal/znak-'.$slug.'.webp';
    }
}
