<?php

namespace Database\Seeders;

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishComplementarySignCatalog;
use App\Support\PolishComplementarySignContentBuilder;
use App\Support\PolishDirectionSignCatalog;
use App\Support\PolishDirectionSignContentBuilder;
use App\Support\PolishHorizontalSignCatalog;
use App\Support\PolishHorizontalSignContentBuilder;
use App\Support\PolishInformationalSignCatalog;
use App\Support\PolishInformationalSignContentBuilder;
use App\Support\PolishMandatorySignCatalog;
use App\Support\PolishMandatorySignContentBuilder;
use App\Support\PolishPlateSignCatalog;
use App\Support\PolishPlateSignContentBuilder;
use App\Support\PolishProhibitionSignCatalog;
use App\Support\PolishProhibitionSignContentBuilder;
use App\Support\PolishRailwaySignCatalog;
use App\Support\PolishRailwaySignContentBuilder;
use App\Support\PolishSignalSignCatalog;
use App\Support\PolishSignalSignContentBuilder;
use App\Support\PolishWarningSignCatalog;
use App\Support\PolishWarningSignContentBuilder;
use App\Support\TrafficSignAuthorProfile;
use Illuminate\Database\Seeder;

class TrafficSignSeoSeeder extends Seeder
{
    public function run(
        PolishWarningSignCatalog $polishWarningSignCatalog,
        PolishWarningSignContentBuilder $polishWarningSignContentBuilder,
        PolishProhibitionSignCatalog $polishProhibitionSignCatalog,
        PolishProhibitionSignContentBuilder $polishProhibitionSignContentBuilder,
        PolishMandatorySignCatalog $polishMandatorySignCatalog,
        PolishMandatorySignContentBuilder $polishMandatorySignContentBuilder,
        PolishInformationalSignCatalog $polishInformationalSignCatalog,
        PolishInformationalSignContentBuilder $polishInformationalSignContentBuilder,
        PolishDirectionSignCatalog $polishDirectionSignCatalog,
        PolishDirectionSignContentBuilder $polishDirectionSignContentBuilder,
        PolishComplementarySignCatalog $polishComplementarySignCatalog,
        PolishComplementarySignContentBuilder $polishComplementarySignContentBuilder,
        PolishPlateSignCatalog $polishPlateSignCatalog,
        PolishPlateSignContentBuilder $polishPlateSignContentBuilder,
        PolishRailwaySignCatalog $polishRailwaySignCatalog,
        PolishRailwaySignContentBuilder $polishRailwaySignContentBuilder,
        PolishHorizontalSignCatalog $polishHorizontalSignCatalog,
        PolishHorizontalSignContentBuilder $polishHorizontalSignContentBuilder,
        PolishSignalSignCatalog $polishSignalSignCatalog,
        PolishSignalSignContentBuilder $polishSignalSignContentBuilder,
    ): void {
        $publishedAt = now()->subDay();

        $author = TrafficSignAuthorProfile::upsert($publishedAt);

        TrafficSignAuthorProfile::moveLegacyTrafficSignsTo($author);

        $warningCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-ostrzegawcze'],
            [
                'name' => 'Znaki ostrzegawcze',
                'description' => 'Znaki ostrzegawcze uprzedzają o zagrożeniu i wymagają wcześniejszej reakcji kierowcy.',
                'intro_title' => 'Jak czytać znaki ostrzegawcze',
                'intro_body' => 'Ta grupa znaków ma przygotować kierowcę na zmianę warunków na drodze jeszcze zanim dojedzie do miejsca zagrożenia.',
                'sort_order' => 10,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $prohibitionCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-zakazu'],
            [
                'name' => 'Znaki zakazu',
                'description' => 'Znaki zakazu wprowadzają konkretne ograniczenia lub zakazy zachowania na drodze.',
                'intro_title' => 'Jak czytać znaki zakazu',
                'intro_body' => 'W tej kategorii skupiamy się na tym, czego kierowca nie może zrobić i jakie są praktyczne skutki zignorowania znaku.',
                'sort_order' => 15,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $mandatoryCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-nakazu'],
            [
                'name' => 'Znaki nakazu',
                'description' => 'Znaki nakazu wskazują obowiązkowy kierunek jazdy albo obowiązkowy sposób korzystania z drogi.',
                'intro_title' => 'Jak czytać znaki nakazu',
                'intro_body' => 'W tej kategorii skupiamy się na tym, co kierowca ma zrobić zgodnie z organizacją ruchu i jak wcześnie przygotować właściwy tor jazdy.',
                'sort_order' => 20,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $informationalCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-informacyjne'],
            [
                'name' => 'Znaki informacyjne',
                'description' => 'Znaki informacyjne opisują organizację ruchu, funkcję drogi i dostępne usługi widoczne z trasy kierowcy.',
                'intro_title' => 'Jak czytać znaki informacyjne',
                'intro_body' => 'W tej kategorii skupiamy się na tym, co znak D mówi o statusie drogi, przejściach, przejazdach i usługach, które mają wpływ na decyzję kierowcy.',
                'sort_order' => 25,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $directionCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-kierunku-i-miejscowosci'],
            [
                'name' => 'Znaki kierunku i miejscowości',
                'description' => 'Znaki kierunku i miejscowości pełnią funkcję nawigacyjną i porządkową.',
                'intro_title' => 'Jak czytać znaki kierunku',
                'intro_body' => 'W tej kategorii dowiesz się, jak prawidłowo nawigować przy pomocy tablic przeddrogowskazowych i drogowskazów oraz jak kolorystyka znaków wskazuje na klasę drogi.',
                'sort_order' => 30,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $complementaryCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-uzupelniajace'],
            [
                'name' => 'Znaki uzupełniające',
                'description' => 'Znaki uzupełniające informują o specyficznej organizacji ruchu na pasach i strefach drogowych.',
                'intro_title' => 'Jak czytać znaki uzupełniające',
                'intro_body' => 'Znaki grupy F dostarczają istotnych informacji doprecyzowujących i organizujących ruch na obszarach skrzyżowań, węzłów czy przejść granicznych.',
                'sort_order' => 35,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $plateCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'tabliczki-do-znakow'],
            [
                'name' => 'Tabliczki do znaków drogowych',
                'description' => 'Tabliczki do znaków uzupełniają i modyfikują znaczenie znaków pionowych.',
                'intro_title' => 'Jak czytać tabliczki T',
                'intro_body' => 'Znaki grupy T występują wyłącznie pod innymi znakami drogowymi, precyzując ich znaczenie, dystans czy układ dróg na skrzyżowaniu.',
                'sort_order' => 40,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $railwayCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-przed-przejazdami-kolejowymi'],
            [
                'name' => 'Dodatkowe znaki przed przejazdami kolejowymi',
                'description' => 'Znaki informujące o zbliżaniu się i infrastrukturze przejazdu kolejowego.',
                'intro_title' => 'Jak czytać znaki przed przejazdami kolejowymi',
                'intro_body' => 'Znaki grupy G pomagają kierowcy określić odległość do torów (słupki wskaźnikowe) oraz rodzaj przejazdu (krzyże św. Andrzeja).',
                'sort_order' => 45,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $horizontalCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-drogowe-poziome'],
            [
                'name' => 'Znaki drogowe poziome',
                'description' => 'Znaki malowane bezpośrednio na jezdni, wyznaczające pasy ruchu i strefy zatrzymania.',
                'intro_title' => 'Jak odczytywać znaki na jezdni',
                'intro_body' => 'Znaki grupy P regulują linie warunkujące przekraczanie pasów ruchu oraz wskazują dokładne miejsca, gdzie kierowca ma obowiązek się zatrzymać (np. przed znakiem STOP).',
                'sort_order' => 50,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $signalCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'sygnaly-swietlne'],
            [
                'name' => 'Sygnały świetlne',
                'description' => 'Sygnalizatory świetlne regulujące pierwszeństwo i kierujące ruchem na skrzyżowaniach.',
                'intro_title' => 'Hierarchia i sygnały świetlne na drodze',
                'intro_body' => 'Sygnały świetlne (grupa S) to najważniejsze urządzenia na drodze zaraz po poleceniach policjanta. Wyłączają one przepisy o pierwszeństwie łamanym i znakach pionowych.',
                'sort_order' => 55,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $dashboardLightCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'kontrolki-w-samochodzie'],
            [
                'name' => 'Kontrolki w samochodzie',
                'description' => 'Ostrzegawcze i informacyjne kontrolki na desce rozdzielczej pojazdu.',
                'intro_title' => 'Jak czytać kontrolki na desce rozdzielczej',
                'intro_body' => 'Znajomość kontrolek w pojeździe pozwala uniknąć zniszczenia silnika oraz gwarantuje bezpieczną podróż. Czerwone kontrolki oznaczają bezwzględną konieczność zatrzymania.',
                'sort_order' => 60,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $trafficDirectorCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'osoba-kierujaca-ruchem'],
            [
                'name' => 'Osoba kierująca ruchem',
                'description' => 'Postawy i sygnały dawane przez policjanta lub inną osobę uprawnioną do kierowania ruchem.',
                'intro_title' => 'Jak odczytywać sygnały policjanta',
                'intro_body' => 'Osoba kierująca ruchem ma najwyższą hierarchię na drodze. Jej polecenia znoszą zasady wynikające ze znaków, świateł oraz przepisów ogólnych.',
                'sort_order' => 65,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $militaryCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-wojskowe'],
            [
                'name' => 'Znaki wojskowe',
                'description' => 'Znaki dla kierujących pojazdami wojskowymi, określające klasy obciążenia obiektów mostowych (MLC).',
                'intro_title' => 'Czym są znaki grupy W',
                'intro_body' => 'Żółte, okrągłe znaki wojskowe dotyczą wyłącznie pojazdów Sił Zbrojnych i wojsk sojuszniczych. Kierowcy cywilni nie muszą się do nich stosować, jednak warto wiedzieć, co oznaczają.',
                'sort_order' => 70,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $tramSignalCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'sygnaly-dla-tramwajow'],
            [
                'name' => 'Sygnały dla tramwajów',
                'description' => 'Białe sygnały świetlne kierujące ruchem tramwajów na skrzyżowaniach.',
                'intro_title' => 'Jak odczytywać światła tramwajów',
                'intro_body' => 'Znajomość białych sygnałów ST pozwala kierowcom samochodów przewidzieć ruch tramwaju. Zapamiętaj: na skrzyżowaniu ze światłami tramwaj NIE zawsze ma pierwszeństwo!',
                'sort_order' => 75,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $tramSignCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'znaki-tramwajowe'],
            [
                'name' => 'Znaki tramwajowe',
                'description' => 'Specjalistyczne znaki pionowe umieszczane przy torowiskach, dedykowane kierującym tramwajami.',
                'intro_title' => 'Czym są znaki tramwajowe',
                'intro_body' => 'Plakietki w kształcie rombów i kwadratów wiszące przy sieci trakcyjnej dotyczą wyłącznie motorniczych. Dowiedz się, dlaczego jako kierowca samochodu nie musisz na nie zwracać uwagi.',
                'sort_order' => 80,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $safetyDeviceCategory = TrafficSignCategory::query()->updateOrCreate(
            ['slug' => 'urzadzenia-bezpieczenstwa-ruchu'],
            [
                'name' => 'Urządzenia bezpieczeństwa ruchu',
                'description' => 'Optyczne prowadzenie ruchu i zabezpieczenie pieszego oraz robót drogowych.',
                'intro_title' => 'Czym są urządzenia BRD?',
                'intro_body' => 'Pachołki, sierżanty i słupki prowadzące to elementy, które nie są znakami, ale fizycznie wytyczają tor jazdy. Dowiedz się, po jakich kolorach odblasków rozpoznasz lewą i prawą stronę drogi w nocy.',
                'sort_order' => 85,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );

        $this->cleanupLegacyRenamedProhibitionQueries();
        $this->cleanupLegacyInformationalData();
        $this->cleanupLegacySignalData();

        $signs = [];

        foreach ([
            [
                'category' => $warningCategory,
                'code' => 'A-7',
                'slug' => 'a-7-ustap-pierwszenstwa',
                'name' => 'Ustąp pierwszeństwa',
                'intro_definition' => 'Znak A-7 uprzedza, że kierowca zbliża się do miejsca, w którym musi ustąpić pierwszeństwa pojazdom jadącym drogą z pierwszeństwem.',
                'meaning' => 'To nie jest tylko informacja o skrzyżowaniu. Ten znak oznacza konkretny obowiązek: przed wjazdem trzeba ocenić sytuację i dopuścić przejazd tym uczestnikom ruchu, którzy mają pierwszeństwo.',
                'placement' => 'Najczęściej zobaczysz go przed skrzyżowaniami dróg podporządkowanych, wyjazdami z dróg lokalnych i miejscami, gdzie geometria drogi nie daje intuicyjnej odpowiedzi, kto jedzie pierwszy.',
                'driver_behavior' => 'Podejdź do znaku z wyraźnym odjęciem gazu. Obserwuj drogę z pierwszeństwem odpowiednio wcześnie, a jeśli widoczność jest słaba, dojedź wolniej i przygotuj się do zatrzymania przed linią warunkowego zatrzymania albo krawędzią jezdni.',
                'legal_summary' => 'A-7 działa razem z zasadami pierwszeństwa przejazdu oraz organizacją ruchu na skrzyżowaniu. Kierowca nie może traktować go jako sugestii: ma obowiązek ustąpić pojazdom poruszającym się drogą z pierwszeństwem.',
                'fine_summary' => 'Zignorowanie A-7 najczęściej kończy się wymuszeniem pierwszeństwa, a to oznacza wysokie ryzyko kolizji, mandatu i punktów karnych. W praktyce to jeden z tych znaków, przy których błąd bywa od razu kosztowny.',
                'common_mistakes' => 'Najczęstszy błąd to dojechanie zbyt szybko i dopiero przy samej krawędzi jezdni próba oceny sytuacji. Drugi typowy problem to skupienie się tylko na jednym kierunku i przeoczenie pojazdu nadjeżdżającego z drugiej strony.',
                'source_notes' => 'Potwierdzone jako znak wysokiego priorytetu do batcha QA: popularny, edukacyjny i dobrze pokazuje zależność między obserwacją a decyzją kierowcy.',
                'editorial_notes' => 'Przy dalszej rozbudowie dodać sekcję o relacji A-7 do B-20 oraz osobny przykład ze słabą widocznością na skrzyżowaniu.',
                'review_notes' => 'Batch QA potwierdzony na publicznym HTML, schema validatorze i manualnym passu metadanych.',
                'faq_items' => [
                    [
                        'question' => 'Czy przy znaku A-7 zawsze trzeba się zatrzymać?',
                        'answer' => 'Nie zawsze. Trzeba natomiast ustąpić pierwszeństwa. Jeśli sytuacja na drodze wymaga zatrzymania, kierowca ma obowiązek to zrobić.',
                    ],
                    [
                        'question' => 'Jak wcześnie zacząć obserwację przy A-7?',
                        'answer' => 'Tak wcześnie, żeby zdążyć spokojnie ocenić ruch na drodze z pierwszeństwem. Ten znak wymaga reakcji przed samym skrzyżowaniem, a nie dopiero na jego krawędzi.',
                    ],
                ],
                'sort_order' => 10,
                'image_path' => 'traffic-signs/signs/warnings/a-7-ustap-pierwszenstwa.webp',
                'image_alt' => 'A-7 Ustąp pierwszeństwa',
                'image_width' => 1200,
                'image_height' => 1200,
                'og_image_path' => 'traffic-signs/signs/warnings/a-7-ustap-pierwszenstwa.webp',
                'og_image_alt' => 'A-7 Ustąp pierwszeństwa',
                'og_image_width' => 1200,
                'og_image_height' => 1200,
            ],
            [
                'category' => $warningCategory,
                'code' => 'A-17',
                'slug' => 'a-17-dzieci',
                'name' => 'Dzieci',
                'intro_definition' => 'Znak A-17 ostrzega o miejscu, w którym na drodze albo w jej bezpośrednim otoczeniu można spodziewać się obecności dzieci.',
                'meaning' => 'To sygnał, że kierowca powinien od razu podnieść poziom uwagi. W praktyce znak nie oznacza tylko szkoły czy przedszkola, ale ogólnie strefę, w której zachowanie pieszych może być mniej przewidywalne niż zwykle.',
                'placement' => 'Najczęściej występuje przy szkołach, przedszkolach, przejściach w pobliżu osiedli i innych miejscach, gdzie ruch pieszych dzieci jest regularny lub zwiększony w określonych godzinach.',
                'driver_behavior' => 'Zmniejsz prędkość wcześniej niż zwykle, patrz szeroko na pobocze i przejścia, a nie tylko na pas ruchu przed sobą. Przy A-17 kierowca powinien zostawić sobie zapas na nagłą reakcję dziecka.',
                'legal_summary' => 'A-17 nie wprowadza osobnego zakazu, ale silnie podbija standard ostrożności kierowcy. Zignorowanie znaku może zostać ocenione przez pryzmat niedostosowania zachowania do warunków na drodze.',
                'fine_summary' => 'Największe ryzyko nie dotyczy samego mandatu za minięcie znaku, tylko konsekwencji zbyt późnej reakcji przy przejściu, zatoce autobusowej albo wylocie ze szkolnego parkingu. To znak, który ma wymusić wcześniejsze myślenie kierowcy.',
                'common_mistakes' => 'Częsty błąd to zauważenie A-17 i brak realnej zmiany stylu jazdy. Innym problemem jest patrzenie wyłącznie na jezdnię, bez kontroli krawędzi chodnika, zza której dziecko może wejść bardzo szybko.',
                'source_notes' => 'Dodany do batcha rozszerzającego kategorię ostrzegawczą, żeby related signs i pierwsze linkowanie wewnętrzne miały sens także poza A-7.',
                'editorial_notes' => 'W kolejnym passu dodać mikrosekcję o relacji A-17 do przejść dla pieszych oraz zachowania przy autobusach szkolnych.',
                'review_notes' => 'Przygotowany jako strona o wysokim potencjale edukacyjnym i sezonowej świeżości w ruchu szkolnym.',
                'faq_items' => [
                    [
                        'question' => 'Czy A-17 oznacza obowiązek jazdy bardzo wolno przez cały odcinek?',
                        'answer' => 'Nie wprost, ale oznacza potrzebę realnego ograniczenia prędkości i zwiększenia gotowości do hamowania w miejscu, gdzie zachowanie pieszych może być nagłe.',
                    ],
                    [
                        'question' => 'Na co patrzeć przy znaku A-17 oprócz samej jezdni?',
                        'answer' => 'Na pobocze, okolice przejścia, zatoki autobusowe i miejsca, z których dziecko może wejść na drogę bez ostrzeżenia.',
                    ],
                ],
                'sort_order' => 20,
                'image_path' => 'traffic-signs/signs/warnings/a-17-dzieci.webp',
                'image_alt' => 'A-17 Dzieci',
                'image_width' => 1200,
                'image_height' => 1200,
                'og_image_path' => 'traffic-signs/signs/warnings/a-17-dzieci.webp',
                'og_image_alt' => 'A-17 Dzieci',
                'og_image_width' => 1200,
                'og_image_height' => 1200,
            ],
            [
                'category' => $prohibitionCategory,
                'code' => 'B-20',
                'slug' => 'b-20-stop',
                'name' => 'STOP',
                'intro_definition' => 'Znak B-20 nakazuje bezwzględne zatrzymanie pojazdu i upewnienie się, że dalszy przejazd nie stworzy zagrożenia.',
                'meaning' => 'To jeden z najmocniejszych znaków organizacji ruchu. Nie chodzi o samo zwolnienie, tylko o pełne zatrzymanie pojazdu w miejscu wyznaczonym albo przed wjazdem w strefę kolizji.',
                'placement' => 'B-20 stawia się tam, gdzie sama ostrożność może nie wystarczyć: przy ograniczonej widoczności, przed torowiskiem albo przed skrzyżowaniem wymagającym pewnego zatrzymania i ponownej obserwacji.',
                'driver_behavior' => 'Zatrzymaj pojazd całkowicie, upewnij się, że koła przestały się toczyć, rozejrzyj się w obu kierunkach i dopiero po ponownej ocenie sytuacji ruszaj dalej. Samo toczenie się na półsprzęgle nie spełnia obowiązku wynikającego z tego znaku.',
                'legal_summary' => 'B-20 wprowadza obowiązek zatrzymania niezależnie od tego, czy kierowcy wydaje się, że droga jest wolna. To znak, przy którym organizacja ruchu wymaga pełnego zatrzymania jako elementu bezpieczeństwa.',
                'fine_summary' => 'Przejechanie przez B-20 bez zatrzymania jest łatwe do wychwycenia i zwykle traktowane surowiej niż zwykłe niedostosowanie prędkości. To również częsty punkt konfliktowy na egzaminie i w codziennej jeździe.',
                'common_mistakes' => 'Najczęściej kierowcy tylko mocno zwalniają i uznają to za wystarczające. Drugim błędem jest zatrzymanie w złym miejscu, z którego nadal nie widać dobrze drogi głównej albo torowiska.',
                'source_notes' => 'To znak mandatowy i egzaminacyjny, dlatego zostaje w batchu jako reprezentant treści o wysokim znaczeniu operacyjnym.',
                'editorial_notes' => 'Rozbudować o różnicę między B-20 i A-7 oraz o praktykę zatrzymania przed linią STOP vs przed krawędzią jezdni.',
                'review_notes' => 'Po kolejnym review dodać mocniejszy blok o absolutnym obowiązku pełnego zatrzymania.',
                'faq_items' => [
                    [
                        'question' => 'Czy toczenie się bardzo wolno przy B-20 wystarczy?',
                        'answer' => 'Nie. Znak B-20 wymaga pełnego zatrzymania pojazdu, a nie tylko mocnego zwolnienia.',
                    ],
                    [
                        'question' => 'Gdzie zatrzymać się przy znaku STOP?',
                        'answer' => 'Najpierw w miejscu wyznaczonym organizacją ruchu, a jeśli z tego miejsca nie widać wystarczająco dużo, trzeba dodatkowo dojechać do punktu zapewniającego bezpieczną obserwację.',
                    ],
                ],
                'sort_order' => 20,
                'image_path' => $this->prohibitionAssetPath('b-20-stop'),
                'image_alt' => 'STOP',
                'image_width' => 1200,
                'image_height' => 1200,
                'og_image_path' => $this->prohibitionAssetPath('b-20-stop'),
                'og_image_alt' => 'STOP',
                'og_image_width' => 1200,
                'og_image_height' => 1200,
            ],
            [
                'category' => $prohibitionCategory,
                'code' => 'B-2',
                'slug' => 'b-2-zakaz-wjazdu',
                'name' => 'Zakaz wjazdu',
                'intro_definition' => 'Znak B-2 zakazuje wjazdu od strony, po której został ustawiony, chyba że pod znakiem znajduje się wyjątek dopuszczający określone pojazdy.',
                'meaning' => 'To czytelny sygnał, że z tego kierunku nie wolno kontynuować jazdy. Znak zamyka wjazd niezależnie od tego, czy kierowca uważa drogę za pustą albo „technicznie przejezdną”.',
                'placement' => 'Najczęściej występuje na wlotach dróg jednokierunkowych od niewłaściwej strony, przy zamkniętych odcinkach oraz tam, gdzie trzeba wyeliminować ruch z konkretnego kierunku.',
                'driver_behavior' => 'Po zauważeniu B-2 nie wjeżdżaj dalej. Jeśli dojechałeś za blisko, nie improwizuj w ostatniej chwili. Zatrzymaj się bezpiecznie i wybierz legalną drogę objazdu albo wycofaj się tylko wtedy, gdy można to zrobić bez stwarzania zagrożenia.',
                'legal_summary' => 'B-2 jest klasycznym znakiem zakazu. Ogranicza możliwość wjazdu od jednej strony i działa niezależnie od subiektywnej oceny kierowcy, czy „przecież nikogo tam nie ma”.',
                'fine_summary' => 'Wjazd za B-2 może skończyć się mandatem, ale przede wszystkim naraża kierowcę na jazdę pod prąd albo w strefę wyłączoną z ruchu z tego kierunku. To znak, którego zignorowanie szybko przekłada się na realne zagrożenie.',
                'common_mistakes' => 'Częsty błąd to pomylenie B-2 z innymi znakami zakazu albo zignorowanie tabliczki pod znakiem, która może dotyczyć tylko wybranych pojazdów. Problemem bywa też późne zauważenie znaku i nerwowy manewr ratunkowy.',
                'source_notes' => 'Wybrany do batcha jako przykład znaku zakazu z bardzo czytelną intencją użytkownika i prostym potencjałem na wysoki CTR.',
                'editorial_notes' => 'W następnym kroku dodać sekcję o relacji B-2 do znaku D-3 oraz scenariusz z ulicą jednokierunkową.',
                'review_notes' => 'Przy rozbudowie dopisać jeszcze jeden przykład pomyłki z drogą jednokierunkową i tabliczką wyłączającą.',
                'faq_items' => [
                    [
                        'question' => 'Czy B-2 oznacza to samo co droga jednokierunkowa?',
                        'answer' => 'Nie. B-2 zakazuje wjazdu z danej strony, a znak drogi jednokierunkowej opisuje organizację ruchu na całym odcinku.',
                    ],
                    [
                        'question' => 'Czy mogę wjechać za B-2, jeśli droga wygląda na pustą?',
                        'answer' => 'Nie. Ten znak nie zostawia pola na uznaniową decyzję kierowcy. Jeśli nie ma tabliczki dopuszczającej wyjątek, wjazd jest zabroniony.',
                    ],
                ],
                'sort_order' => 30,
                'image_path' => $this->prohibitionAssetPath('b-2-zakaz-wjazdu'),
                'image_alt' => 'Zakaz wjazdu',
                'image_width' => 1200,
                'image_height' => 1200,
                'og_image_path' => $this->prohibitionAssetPath('b-2-zakaz-wjazdu'),
                'og_image_alt' => 'Zakaz wjazdu',
                'og_image_width' => 1200,
                'og_image_height' => 1200,
            ],
            [
                'category' => $prohibitionCategory,
                'code' => 'B-1',
                'slug' => 'b-1-zakaz-ruchu-w-obu-kierunkach',
                'name' => 'Zakaz ruchu w obu kierunkach',
                'intro_definition' => 'Znak B-1 zakazuje ruchu wszelkich pojazdów na danym odcinku drogi, chyba że tabliczka pod znakiem wyłącza z zakazu określone grupy.',
                'meaning' => 'To nie jest tylko sygnał, że „raczej nie powinno się wjeżdżać”. B-1 zamyka ruch dla pojazdów z obu kierunków i wymaga bezwzględnego zastosowania się do organizacji ruchu.',
                'placement' => 'Najczęściej spotyka się go przy drogach wyłączonych z ruchu, remontach, wjazdach do stref ograniczonych albo na odcinkach, na których organizacja ruchu wymaga pełnego zamknięcia dla pojazdów.',
                'driver_behavior' => 'Nie wjeżdżaj za znak. Jeśli jesteś już blisko wlotu, zatrzymaj się bezpiecznie, przeczytaj ewentualną tabliczkę i wybierz legalny objazd. Tu nie ma miejsca na interpretację „może tylko kawałek”.',
                'legal_summary' => 'B-1 jest klasycznym znakiem zakazu o szerokim zasięgu. Ogranicza wjazd pojazdów na odcinek drogi i działa do miejsca wynikającego z organizacji ruchu albo znaku odwołującego.',
                'fine_summary' => 'Wjazd za B-1 może prowadzić nie tylko do mandatu, ale też do realnego konfliktu z ruchem technicznym, pieszym albo organizacją objazdów. To znak, którego zignorowanie szybko staje się problemem praktycznym.',
                'common_mistakes' => 'Często kierowcy mylą B-1 z zakazem wjazdu od jednej strony albo ignorują tabliczkę, która precyzuje wyjątek. Drugim błędem jest próba „krótkiego przejazdu”, bo droga wydaje się pusta.',
                'source_notes' => 'Rozszerza batch znaków zakazu o stronę mocno intencyjną i prostą do wykorzystania w linkowaniu z przyszłego klastra mandatowego.',
                'editorial_notes' => 'Przy dalszej rozbudowie dodać różnicę między B-1 i B-2 oraz scenariusz z odcinkiem czasowo zamkniętym.',
                'review_notes' => 'Dobra strona do testowania intencji użytkownika wokół zakazu ruchu i wyjątków na tabliczkach.',
                'faq_items' => [
                    [
                        'question' => 'Czym B-1 różni się od B-2?',
                        'answer' => 'B-1 zamyka ruch pojazdów na odcinku drogi, a B-2 zakazuje wjazdu tylko od strony, po której stoi znak.',
                    ],
                    [
                        'question' => 'Czy przy B-1 mogę wjechać, jeśli pod znakiem jest tabliczka z wyjątkiem?',
                        'answer' => 'Tak, ale tylko wtedy, gdy Twój pojazd albo cel wjazdu mieści się dokładnie w zakresie wyjątku opisanym na tabliczce.',
                    ],
                ],
                'sort_order' => 40,
                'image_path' => $this->prohibitionAssetPath('b-1-zakaz-ruchu-w-obu-kierunkach'),
                'image_alt' => 'Zakaz ruchu w obu kierunkach',
                'image_width' => 1200,
                'image_height' => 1200,
                'og_image_path' => $this->prohibitionAssetPath('b-1-zakaz-ruchu-w-obu-kierunkach'),
                'og_image_alt' => 'Zakaz ruchu w obu kierunkach',
                'og_image_width' => 1200,
                'og_image_height' => 1200,
            ],
            [
                'category' => $prohibitionCategory,
                'code' => 'B-35',
                'slug' => 'b-35-zakaz-postoju',
                'name' => 'Zakaz postoju',
                'intro_definition' => 'Znak B-35 oznacza zakaz postoju, czyli zakaz unieruchomienia pojazdu na dłużej niż wynika to z warunków ruchu lub przepisów.',
                'meaning' => 'B-35 rozdziela krótkie zatrzymanie od postoju. Kierowca może zatrzymać auto na chwilę, ale nie może zostawić go w tym miejscu na dłużej.',
                'placement' => 'Najczęściej pojawia się w miejscach o ograniczonej przestrzeni, przy wjazdach, na wąskich ulicach oraz tam, gdzie zaparkowany samochód utrudniałby ruch lub widoczność.',
                'driver_behavior' => 'Jeżeli musisz się zatrzymać, zrób to tylko na czas konieczny do wysadzenia pasażera albo krótkiej czynności wynikającej z ruchu. Nie zostawiaj auta bez nadzoru i nie traktuj tego miejsca jak zwykłego parkingu.',
                'legal_summary' => 'B-35 działa w systemie znaków zakazu i porządkuje miejsca, w których dłuższe pozostawienie pojazdu byłoby uciążliwe albo niebezpieczne. Trzeba czytać go razem z organizacją ruchu i oznaczeniami dodatkowymi.',
                'fine_summary' => 'Naruszenie zakazu postoju może skończyć się mandatem, a w praktyce także odholowaniem pojazdu, jeśli samochód blokuje ruch albo stwarza zagrożenie.',
                'common_mistakes' => 'Najczęstszy błąd to mylenie postoju z krótkim zatrzymaniem. Kierowcy często odchodzą od auta na kilka minut, uznając, że skoro silnik był zgaszony tylko chwilę, to zakaz ich nie dotyczy.',
                'source_notes' => 'Treść przygotowana na bazie obowiązującego opisu znaku B-35 oraz praktycznej różnicy między zakazem postoju i zakazem zatrzymywania się.',
                'editorial_notes' => 'Dobra strona do rollout-01 pod zapytania o różnicę między zakazem postoju a zakazem zatrzymywania się.',
                'review_notes' => 'Panelowy pass QA potwierdził kompletność checklisty publikacyjnej i gotowość do seryjnej pracy w kategorii znaków zakazu.',
                'faq_items' => [
                    [
                        'question' => 'Czy przy znaku B-35 można zatrzymać się na chwilę?',
                        'answer' => 'Tak, ale tylko na krótko i bez wchodzenia w postój. Jeśli zostawiasz samochód na dłużej albo odchodzisz od niego, naruszasz zakaz postoju.',
                    ],
                    [
                        'question' => 'Czym B-35 różni się od B-36?',
                        'answer' => 'B-35 zabrania postoju, ale dopuszcza krótkie zatrzymanie. B-36 jest ostrzejszy i zakazuje już samego zatrzymania pojazdu, poza wyjątkami wynikającymi z ruchu.',
                    ],
                ],
                'meta_title' => 'B-35 Zakaz postoju - co oznacza i jak się zachować',
                'meta_description' => 'Wyjaśniamy, co oznacza znak B-35 Zakaz postoju, gdzie się pojawia, kiedy wolno się zatrzymać i jakie są konsekwencje naruszenia zakazu.',
                'sort_order' => 35,
                'image_path' => $this->prohibitionAssetPath('b-35-zakaz-postoju'),
                'image_alt' => 'Zakaz postoju',
                'image_width' => 1200,
                'image_height' => 1200,
                'og_image_path' => $this->prohibitionAssetPath('b-35-zakaz-postoju'),
                'og_image_alt' => 'Zakaz postoju',
                'og_image_width' => 1200,
                'og_image_height' => 1200,
            ],
            [
                'category' => $prohibitionCategory,
                'code' => 'B-36',
                'slug' => 'b-36-zakaz-zatrzymywania-sie',
                'name' => 'Zakaz zatrzymywania się',
                'intro_definition' => 'Znak B-36 zakazuje zatrzymywania pojazdu w miejscu, od którego obowiązuje zakaz, chyba że znak albo tabliczka wskazuje wyjątek.',
                'meaning' => 'To jeden z tych znaków, przy których kierowca musi odróżniać krótkie zatrzymanie od zwykłego postoju. B-36 nie zostawia tu luzu interpretacyjnego: zatrzymanie w strefie obowiązywania jest co do zasady zabronione.',
                'placement' => 'Najczęściej spotkasz go tam, gdzie zatrzymanie pojazdu blokowałoby widoczność, ruch autobusów, przejścia dla pieszych albo newralgiczne odcinki ulic o dużym natężeniu ruchu.',
                'driver_behavior' => 'Po zauważeniu B-36 nie planuj nawet krótkiego zatrzymania na poboczu czy przy krawędzi jezdni. Jeśli musisz kogoś wysadzić albo odebrać, szukaj miejsca poza strefą obowiązywania znaku.',
                'legal_summary' => 'B-36 działa jako klasyczny znak zakazu i wymaga respektowania organizacji ruchu na całym odcinku jego obowiązywania. Kierowca nie może samodzielnie uznać, że zatrzymanie na moment jest dopuszczalne, jeśli znak tego nie przewiduje.',
                'fine_summary' => 'Zignorowanie B-36 to nie tylko ryzyko mandatu, ale też realne utrudnienie ruchu i zagrożenie dla widoczności innych uczestników. To znak mocno związany z codzienną praktyką parkowania i zatrzymań w mieście.',
                'common_mistakes' => 'Najczęstszy błąd to mylenie zakazu zatrzymywania się z zakazem postoju. Drugim problemem jest przekonanie, że awaryjne zatrzymanie na chwilę przy pustej ulicy nie ma znaczenia, mimo że znak działa niezależnie od ruchu w danym momencie.',
                'source_notes' => 'Do rollout-01 bierzemy ten znak jako kandydat o wysokim potencjale egzaminacyjnym i mandatowym. Źródła podstawowe: rozporządzenie o znakach oraz praktyczny kontekst różnicy B-35 vs B-36.',
                'editorial_notes' => 'Po publikacji dodać materiał wspierający porównujący B-35 i B-36 oraz linkowanie do przyszłych treści mandatowych.',
                'review_notes' => 'Przeszedł pełną checklistę publikacyjną w panelu i potwierdził, że bulk workflow działa także dla znaków w nowym batchu produkcyjnym.',
                'faq_items' => [
                    [
                        'question' => 'Czy przy B-36 można zatrzymać się tylko na moment?',
                        'answer' => 'Nie. W strefie obowiązywania B-36 zakazane jest samo zatrzymanie pojazdu, o ile organizacja ruchu nie przewiduje wyjątku.',
                    ],
                    [
                        'question' => 'Czym B-36 różni się od B-35?',
                        'answer' => 'B-36 zakazuje już samego zatrzymania pojazdu, a B-35 dotyczy zakazu postoju. To jedna z najważniejszych różnic praktycznych dla kierowcy.',
                    ],
                ],
                'meta_title' => 'B-36 Zakaz zatrzymywania się - znaczenie, przepisy i zachowanie kierowcy',
                'meta_description' => 'Sprawdź, co oznacza znak B-36 Zakaz zatrzymywania się, gdzie obowiązuje i jak powinien zachować się kierowca.',
                'sort_order' => 50,
                'image_path' => $this->prohibitionAssetPath('b-36-zakaz-zatrzymywania-sie'),
                'image_alt' => 'Zakaz zatrzymywania się',
                'image_width' => 1200,
                'image_height' => 1200,
                'og_image_path' => $this->prohibitionAssetPath('b-36-zakaz-zatrzymywania-sie'),
                'og_image_alt' => 'Zakaz zatrzymywania się',
                'og_image_width' => 1200,
                'og_image_height' => 1200,
            ],
        ] as $signData) {
            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $signData['category'],
                data: $signData,
                publishedAt: $publishedAt,
            );
        }

        $this->seedPublishedWarningBatch(
            author: $author,
            category: $warningCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishWarningSignCatalog: $polishWarningSignCatalog,
            polishWarningSignContentBuilder: $polishWarningSignContentBuilder,
        );

        $this->seedPublishedProhibitionBatch(
            author: $author,
            category: $prohibitionCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishProhibitionSignCatalog: $polishProhibitionSignCatalog,
            polishProhibitionSignContentBuilder: $polishProhibitionSignContentBuilder,
        );

        $this->seedPublishedMandatoryBatch(
            author: $author,
            category: $mandatoryCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishMandatorySignCatalog: $polishMandatorySignCatalog,
            polishMandatorySignContentBuilder: $polishMandatorySignContentBuilder,
        );

        $this->seedPublishedInformationalBatch(
            author: $author,
            category: $informationalCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishInformationalSignCatalog: $polishInformationalSignCatalog,
            polishInformationalSignContentBuilder: $polishInformationalSignContentBuilder,
        );

        $this->seedPublishedDirectionBatch(
            author: $author,
            category: $directionCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishDirectionSignCatalog: $polishDirectionSignCatalog,
            polishDirectionSignContentBuilder: $polishDirectionSignContentBuilder,
        );

        $this->seedPublishedComplementaryBatch(
            author: $author,
            category: $complementaryCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishComplementarySignCatalog: $polishComplementarySignCatalog,
            polishComplementarySignContentBuilder: $polishComplementarySignContentBuilder,
        );

        $this->seedPublishedPlateBatch(
            author: $author,
            category: $plateCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishPlateSignCatalog: $polishPlateSignCatalog,
            polishPlateSignContentBuilder: $polishPlateSignContentBuilder,
        );

        $this->seedPublishedRailwayBatch(
            author: $author,
            category: $railwayCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishRailwaySignCatalog: $polishRailwaySignCatalog,
            polishRailwaySignContentBuilder: $polishRailwaySignContentBuilder,
        );

        $this->seedPublishedHorizontalBatch(
            author: $author,
            category: $horizontalCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishHorizontalSignCatalog: $polishHorizontalSignCatalog,
            polishHorizontalSignContentBuilder: $polishHorizontalSignContentBuilder,
        );

        $this->seedPublishedSignalBatch(
            author: $author,
            category: $signalCategory,
            publishedAt: $publishedAt,
            signs: $signs,
            polishSignalSignCatalog: $polishSignalSignCatalog,
            polishSignalSignContentBuilder: $polishSignalSignContentBuilder,
        );

        $this->seedQueryMapEntries(
            warningCategory: $warningCategory,
            prohibitionCategory: $prohibitionCategory,
            mandatoryCategory: $mandatoryCategory,
            informationalCategory: $informationalCategory,
            directionCategory: $directionCategory,
            complementaryCategory: $complementaryCategory,
            plateCategory: $plateCategory,
            railwayCategory: $railwayCategory,
            horizontalCategory: $horizontalCategory,
            signalCategory: $signalCategory,
            signs: $signs,
            polishMandatorySignCatalog: $polishMandatorySignCatalog,
            polishInformationalSignCatalog: $polishInformationalSignCatalog,
            polishDirectionSignCatalog: $polishDirectionSignCatalog,
            polishComplementarySignCatalog: $polishComplementarySignCatalog,
            polishPlateSignCatalog: $polishPlateSignCatalog,
            polishRailwaySignCatalog: $polishRailwaySignCatalog,
            polishHorizontalSignCatalog: $polishHorizontalSignCatalog,
            polishSignalSignCatalog: $polishSignalSignCatalog,
        );
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedQueryMapEntries(
        TrafficSignCategory $warningCategory,
        TrafficSignCategory $prohibitionCategory,
        TrafficSignCategory $mandatoryCategory,
        TrafficSignCategory $informationalCategory,
        TrafficSignCategory $directionCategory,
        TrafficSignCategory $complementaryCategory,
        TrafficSignCategory $plateCategory,
        TrafficSignCategory $railwayCategory,
        TrafficSignCategory $horizontalCategory,
        TrafficSignCategory $signalCategory,
        array $signs,
        PolishMandatorySignCatalog $polishMandatorySignCatalog,
        PolishInformationalSignCatalog $polishInformationalSignCatalog,
        PolishDirectionSignCatalog $polishDirectionSignCatalog,
        PolishComplementarySignCatalog $polishComplementarySignCatalog,
        PolishPlateSignCatalog $polishPlateSignCatalog,
        PolishRailwaySignCatalog $polishRailwaySignCatalog,
        PolishHorizontalSignCatalog $polishHorizontalSignCatalog,
        PolishSignalSignCatalog $polishSignalSignCatalog,
    ): void {
        $entries = [
            [
                'primary_query' => 'a-7 ustąp pierwszeństwa',
                'mapped_title' => 'A-7 Ustąp pierwszeństwa',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'sample-qa-01',
                'target_path' => '/znaki-drogowe/a-7-ustap-pierwszenstwa',
                'traffic_sign_id' => $signs['a-7-ustap-pierwszenstwa']->getKey(),
                'traffic_sign_category_id' => $warningCategory->getKey(),
                'source_plan' => 'Podstawa prawna z ISAP + praktyczny kontekst ustępowania pierwszeństwa i powiązanie z linią warunkowego zatrzymania.',
                'correction_notes' => 'Wymaga aktualizacji przy zmianach w zasadach pierwszeństwa albo przy rozbudowie porównań A-7 vs B-20.',
                'competitor_notes' => 'To query o wysokiej ekspozycji, gdzie sucha definicja nie wystarczy bez sekcji zachowania kierowcy.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Strona już opublikowana i stanowi wzorzec dla batcha ostrzegawczego.',
            ],
            [
                'primary_query' => 'a-17 dzieci znak',
                'mapped_title' => 'A-17 Dzieci',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'sample-qa-01',
                'target_path' => '/znaki-drogowe/a-17-dzieci',
                'traffic_sign_id' => $signs['a-17-dzieci']->getKey(),
                'traffic_sign_category_id' => $warningCategory->getKey(),
                'source_plan' => 'Treść powinna opierać się na organizacji ruchu i praktyce zwiększonej ostrożności w pobliżu szkół oraz przejść.',
                'correction_notes' => 'Warto robić pass przed sezonem szkolnym i po każdej większej aktualizacji zasad ochrony pieszych.',
                'competitor_notes' => 'Mocne query sezonowe i egzaminacyjne, dobre do rozbudowy klastrów wokół pieszych i szkół.',
                'first_mover_note' => 'Dobre miejsce na szybkie odświeżenia sezonowe zanim konkurencja poprawi leady i FAQ.',
                'watch_reason' => null,
                'notes' => 'Publikacja gotowa; kandydat do późniejszej rozbudowy o materiał wspierający.',
            ],
            [
                'primary_query' => 'b-20 stop znak',
                'mapped_title' => 'B-20 STOP',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'sample-qa-01',
                'target_path' => '/znaki-drogowe/b-20-stop',
                'traffic_sign_id' => $signs['b-20-stop']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Podkreślić bezwzględny obowiązek zatrzymania i różnicę względem samego zwolnienia przy A-7.',
                'correction_notes' => 'Reagować przy zmianach punktów karnych, mandatów albo interpretacji dotyczących pełnego zatrzymania.',
                'competitor_notes' => 'Dobre query do budowania przewagi na kontrastach A-7 vs B-20 i w treściach egzaminacyjnych.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Opublikowany core page o wysokiej intencji egzaminacyjnej i mandatowej.',
            ],
            [
                'primary_query' => 'b-2 zakaz wjazdu',
                'mapped_title' => 'B-2 Zakaz wjazdu',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'sample-qa-01',
                'target_path' => '/znaki-drogowe/b-2-zakaz-wjazdu',
                'traffic_sign_id' => $signs['b-2-zakaz-wjazdu']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Utrzymać jasne odróżnienie od B-1 i drogi jednokierunkowej; to ma być szybka, praktyczna odpowiedź.',
                'correction_notes' => 'Przegląd przy rozbudowie klastra znaków zakazu i materiałach o ruchu jednokierunkowym.',
                'competitor_notes' => 'W tym query łatwo wpaść w bardzo cienką treść, więc przewagą ma być praktyka i częste pomyłki kierowcy.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Publikacja działa jako jeden z anchor pages kategorii zakazu.',
            ],
            [
                'primary_query' => 'b-1 zakaz ruchu w obu kierunkach',
                'mapped_title' => 'B-1 Zakaz ruchu w obu kierunkach',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'sample-qa-01',
                'target_path' => '/znaki-drogowe/b-1-zakaz-ruchu-w-obu-kierunkach',
                'traffic_sign_id' => $signs['b-1-zakaz-ruchu-w-obu-kierunkach']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Pilnować rozróżnienia B-1 vs B-2 i wyjątków z tabliczek, bo to buduje sens query porównawczych.',
                'correction_notes' => 'Wrócić do rewizji, gdy dojdą kolejne strony znaków zakazu i materiał porównawczy B-1 vs B-2.',
                'competitor_notes' => 'Query dobre do rozszerzania klastra zakazu i future batcha porównawczego.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Opublikowany reprezentant znaku zakazu o potencjale porównawczym.',
            ],
            [
                'primary_query' => 'znaki ostrzegawcze',
                'mapped_title' => 'Kategoria Znaki ostrzegawcze',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'sample-qa-01',
                'target_path' => '/znaki-drogowe/kategorie/znaki-ostrzegawcze',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $warningCategory->getKey(),
                'source_plan' => 'Rozwijać kategorię jako wejście do kolejnych stron ostrzegawczych i materiałów wspierających.',
                'correction_notes' => 'Aktualizować wraz z rozwojem liczby stron oraz nowych bloków powiązanych znaków.',
                'competitor_notes' => 'Kategoria powinna być bardziej użyteczna niż lista; musi prowadzić do konkretnych wzorców zachowania kierowcy.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Category page opublikowana jako pierwszy poziom klastra.',
            ],
            [
                'primary_query' => 'znaki zakazu',
                'mapped_title' => 'Kategoria Znaki zakazu',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'sample-qa-01',
                'target_path' => '/znaki-drogowe/kategorie/znaki-zakazu',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Traktować kategorię jako wejście do praktyki zakazów, wyjątków i typowych pomyłek kierowcy.',
                'correction_notes' => 'Korekta przy dołożeniu kolejnych zakazów i stron porównawczych wewnątrz kategorii.',
                'competitor_notes' => 'Dobra baza pod silne linkowanie do przyszłego klastra mandatowego i materiałów o wyjątkach z tabliczek.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Category page opublikowana jako drugi poziom nawigacyjny klastra.',
            ],
            [
                'primary_query' => 'a-7 a stop różnice',
                'mapped_title' => 'Porównanie A-7 i B-20',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-01',
                'target_path' => '/znaki-drogowe/porownania/a-7-vs-b-20',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Materiał powinien oprzeć się o istniejące strony A-7 i B-20 oraz przepisy dotyczące obowiązku zatrzymania i ustępowania pierwszeństwa.',
                'correction_notes' => 'Aktualizować po każdej zmianie w treści bazowej A-7 albo B-20, żeby porównanie nie rozjeżdżało się z core pages.',
                'competitor_notes' => 'Mocny query pomocniczy, którym można zamknąć intencję użytkownika zanim wróci do SERP po porównanie.',
                'first_mover_note' => 'Szybkie wdrożenie tej strony może dać przewagę nad konkurencją, która rozdziela te odpowiedzi między dwa osobne artykuły.',
                'watch_reason' => null,
                'notes' => 'Opublikowana jako pierwsza strona wspierająca domykająca rollout-01 wokół A-7 i B-20.',
            ],
            [
                'primary_query' => 'b-36 zakaz zatrzymywania się',
                'mapped_title' => 'B-36 Zakaz zatrzymywania się',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-01',
                'target_path' => '/znaki-drogowe/b-36-zakaz-zatrzymywania-sie',
                'traffic_sign_id' => $signs['b-36-zakaz-zatrzymywania-sie']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Przygotować komplet źródeł pod różnicę zatrzymanie vs postój i od razu pomyśleć o linkowaniu z materiałami mandatowymi.',
                'correction_notes' => 'Wrócić do review przy każdej zmianie taryfikatora albo rozbudowie klastra znaków zakazu.',
                'competitor_notes' => 'Silny potencjał egzaminacyjny i mandatowy, dobry kandydat do pierwszego większego batcha po sample QA.',
                'first_mover_note' => 'Dobre query do szybkiego domknięcia, zanim wystartuje klaster mandatowy.',
                'watch_reason' => 'Wysokie znaczenie użytkowe i naturalne powiązanie z już opublikowanymi znakami zakazu.',
                'notes' => 'Opublikowany w rollout-01 po przejściu pełnego flow create/edit/bulk publish w panelu admina.',
            ],
            [
                'primary_query' => 'b-35 zakaz postoju',
                'mapped_title' => 'B-35 Zakaz postoju',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-01',
                'target_path' => '/znaki-drogowe/b-35-zakaz-postoju',
                'traffic_sign_id' => $signs['b-35-zakaz-postoju']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Od początku pisać z myślą o porównaniu z B-36 i o konkretnych zachowaniach kierowcy, nie tylko o definicji.',
                'correction_notes' => 'Do rewizji razem z B-36, bo te strony powinny żyć jako sparowany mini-klaster.',
                'competitor_notes' => 'Query bliskie intencji „różnice” i „kiedy wolno się zatrzymać”, więc łatwo je rozwinąć w supporting content.',
                'first_mover_note' => null,
                'watch_reason' => 'Powinien wejść od razu po B-36 albo równolegle, żeby nie zostawić luki w klastrze zakazu.',
                'notes' => 'Opublikowany w rollout-01 razem z B-36, żeby od razu zamknąć mini-klaster postoju vs zatrzymania.',
            ],
        ];

        $entries = array_merge(
            $entries,
            $this->rolloutTwoQueryMapEntries($signs, $prohibitionCategory),
            $this->rolloutThreeQueryMapEntries($signs, $prohibitionCategory),
            $this->rolloutFourQueryMapEntries($signs, $prohibitionCategory),
            $this->rolloutFiveQueryMapEntries($signs, $warningCategory),
            $this->rolloutSixQueryMapEntries($signs, $warningCategory),
            $this->rolloutSevenQueryMapEntries($signs, $warningCategory),
            $this->rolloutEightQueryMapEntries($signs, $warningCategory),
            $this->mandatoryQueryMapEntries($mandatoryCategory, $signs, $polishMandatorySignCatalog),
            $this->informationalQueryMapEntries($informationalCategory, $signs, $polishInformationalSignCatalog),
            $this->directionQueryMapEntries($directionCategory, $signs, $polishDirectionSignCatalog),
            $this->complementaryQueryMapEntries($complementaryCategory, $signs, $polishComplementarySignCatalog),
            $this->plateQueryMapEntries($plateCategory, $signs, $polishPlateSignCatalog),
            $this->railwayQueryMapEntries($railwayCategory, $signs, $polishRailwaySignCatalog),
            $this->horizontalQueryMapEntries($horizontalCategory, $signs, $polishHorizontalSignCatalog),
            $this->signalQueryMapEntries($signalCategory, $signs, $polishSignalSignCatalog),
        );

        $this->pruneLegacySignalVariantQueryMapEntries();

        foreach ($entries as $entryData) {
            TrafficSignQueryMapEntry::query()->updateOrCreate(
                ['primary_query' => $entryData['primary_query']],
                $entryData,
            );
        }
    }

    protected function pruneLegacySignalVariantQueryMapEntries(): void
    {
        TrafficSignQueryMapEntry::query()
            ->whereIn('primary_query', [
                's-1a sygnał czerwony',
                's-1b sygnał żółty',
                's-1c sygnał zielony',
                's-1d sygnały czerwony i żółty',
            ])
            ->whereIn('target_path', [
                '/znaki-drogowe/s-1a-sygnal-czerwony',
                '/znaki-drogowe/s-1b-sygnal-zolty',
                '/znaki-drogowe/s-1c-sygnal-zielony',
                '/znaki-drogowe/s-1d-sygnaly-czerwony-i-zolty',
            ])
            ->delete();
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedWarningBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishWarningSignCatalog $polishWarningSignCatalog,
        PolishWarningSignContentBuilder $polishWarningSignContentBuilder,
    ): void {
        $catalogByCode = [];

        foreach ($polishWarningSignCatalog->all() as $signData) {
            $catalogByCode[$signData['code']] = $signData;
        }

        foreach (array_merge(
            $this->rolloutFivePublishedSigns(),
            $this->rolloutSixPublishedSigns(),
            $this->rolloutSevenPublishedSigns(),
            $this->rolloutEightPublishedSigns(),
        ) as $code => $overrides) {
            $signData = $catalogByCode[$code] ?? null;

            if ($signData === null) {
                continue;
            }

            $content = array_merge(
                $polishWarningSignContentBuilder->build($signData, (int) $overrides['builder_index']),
                [
                    'sort_order' => $overrides['sort_order'],
                    'source_notes' => $overrides['source_notes'],
                    'editorial_notes' => $overrides['editorial_notes'],
                    'review_notes' => $overrides['review_notes'],
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedProhibitionBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishProhibitionSignCatalog $polishProhibitionSignCatalog,
        PolishProhibitionSignContentBuilder $polishProhibitionSignContentBuilder,
    ): void {
        $catalogByCode = [];

        foreach ($polishProhibitionSignCatalog->all() as $signData) {
            $catalogByCode[$signData['code']] = $signData;
        }

        foreach (array_merge(
            $this->rolloutTwoPublishedSigns(),
            $this->rolloutThreePublishedSigns(),
            $this->rolloutFourPublishedSigns(),
        ) as $code => $overrides) {
            $signData = $catalogByCode[$code] ?? null;

            if ($signData === null) {
                continue;
            }

            $content = array_merge(
                $polishProhibitionSignContentBuilder->build($signData, (int) $overrides['builder_index']),
                [
                    'sort_order' => $overrides['sort_order'],
                    'source_notes' => $overrides['source_notes'],
                    'editorial_notes' => $overrides['editorial_notes'],
                    'review_notes' => $overrides['review_notes'],
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedMandatoryBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishMandatorySignCatalog $polishMandatorySignCatalog,
        PolishMandatorySignContentBuilder $polishMandatorySignContentBuilder,
    ): void {
        foreach ($polishMandatorySignCatalog->all() as $index => $signData) {
            $content = array_merge(
                $polishMandatorySignContentBuilder->build($signData, $index),
                [
                    'source_notes' => "Rollout-09 obejmuje pełny publiczny batch znaków nakazu. Strona {$signData['code']} ma domykać coverage kategorii C bez zostawiania pojedynczych znaków poza katalogiem.",
                    'editorial_notes' => "Przy kolejnym passie rozbudować {$signData['code']} o przykłady sytuacyjne, relacje do oznakowania poziomego oraz najbliższe znaki z rodziny C.",
                    'review_notes' => 'Publikacja w rollout-09 jako część kompletnej kategorii znaków nakazu z docelowym assetem WebP i spójnym rich snippet image graph.',
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedInformationalBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishInformationalSignCatalog $polishInformationalSignCatalog,
        PolishInformationalSignContentBuilder $polishInformationalSignContentBuilder,
    ): void {
        foreach ($polishInformationalSignCatalog->all() as $index => $signData) {
            $content = array_merge(
                $polishInformationalSignContentBuilder->build($signData, $index),
                [
                    'source_notes' => "Rollout-10 obejmuje pierwszy pełny publiczny batch znaków informacyjnych D. Strona {$signData['code']} ma domykać podstawowy coverage kategorii bez zostawiania pojedynczych znaków poza katalogiem.",
                    'editorial_notes' => "Przy kolejnym passie rozbudować {$signData['code']} o relacje do najbliższych znaków ostrzegawczych, zakazu albo nakazu oraz o przykłady sytuacyjne z miasta i tras przelotowych.",
                    'review_notes' => 'Publikacja w rollout-10 jako część pierwszej kompletnej publicznej odsłony znaków informacyjnych z assetem WebP i spójnym graph image dla rich resultów.',
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function mandatoryQueryMapEntries(
        TrafficSignCategory $mandatoryCategory,
        array $signs,
        PolishMandatorySignCatalog $polishMandatorySignCatalog,
    ): array {
        $entries = [
            [
                'primary_query' => 'znaki nakazu',
                'mapped_title' => 'Kategoria Znaki nakazu',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-09-mandatory',
                'target_path' => '/znaki-drogowe/kategorie/znaki-nakazu',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $mandatoryCategory->getKey(),
                'source_plan' => 'Kategoria ma prowadzić użytkownika od obowiązkowego kierunku jazdy do znaków dla pieszych, rowerów, prędkości minimalnej i łańcuchów przeciwślizgowych.',
                'correction_notes' => 'Rozbudowywać razem z nowymi materiałami wspierającymi dla rodziny C i z kolejnymi porównaniami wewnątrz kategorii.',
                'competitor_notes' => 'Przewaga tej kategorii powinna brać się z kompletnego coverage znaków C oraz z czytelnego podziału na kierunki, ciągi pieszo-rowerowe i nakazy techniczne.',
                'first_mover_note' => null,
                'watch_reason' => 'Pierwsza kompletna publiczna odsłona kategorii znaków nakazu.',
                'notes' => 'Category page opublikowana jako wejście do pełnego rollout-09 dla znaków C.',
            ],
        ];

        foreach ($polishMandatorySignCatalog->all() as $signData) {
            $sign = $signs[$signData['slug']] ?? null;

            if (! $sign instanceof TrafficSign) {
                continue;
            }

            $entries[] = [
                'primary_query' => $signData['primary_query'],
                'mapped_title' => $signData['code'].' '.$signData['name'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => $this->mandatorySearchIntentFor($signData['code']),
                'priority' => $this->mandatoryPriorityFor($signData['code']),
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-09-mandatory',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $mandatoryCategory->getKey(),
                'source_plan' => "Stronę {$signData['code']} oprzeć o oficjalne znaczenie znaku, przygotowanie toru jazdy i praktyczne zachowanie kierowcy albo uczestnika ruchu na odcinku objętym nakazem.",
                'correction_notes' => "Przy następnej rewizji sprawdzić {$signData['code']} razem z oznakowaniem poziomym, sygnalizacją i typowymi pytaniami egzaminacyjnymi dla rodziny C.",
                'competitor_notes' => "Treść {$signData['code']} ma wygrywać kompletnością i praktycznym opisem obowiązku, a nie samą słownikową definicją znaku.",
                'first_mover_note' => null,
                'watch_reason' => "Element pełnego publicznego coverage kategorii C dla znaku {$signData['code']}.",
                'notes' => "Rollout-09: strona znaku {$signData['code']}.",
            ];
        }

        return array_merge($entries, [
            [
                'primary_query' => 'c-1 c-2 c-3 c-4 różnice',
                'mapped_title' => 'Porównanie C-1 do C-4',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-09-mandatory',
                'target_path' => '/znaki-drogowe/porownania/c-1-do-c-4',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jedną stroną rozdzielić znaki prowadzące dalszy kierunek jazdy od znaków nakazujących sam skręt, z naciskiem na moment podjęcia manewru.',
                'correction_notes' => 'Aktualizować wspólnie z C-1, C-2, C-3 i C-4 oraz sprawdzać zgodność z przykładami pasa ruchu i wysp kanalizujących.',
                'competitor_notes' => 'To naturalne query porównawcze dla kursantów, którzy mylą znaki podobne graficznie, ale różne funkcjonalnie.',
                'first_mover_note' => null,
                'watch_reason' => 'Mocny materiał wspierający dla bazowej rodziny kierunkowej znaków C.',
                'notes' => 'Supporting page otwierający comparison cluster dla C-1 do C-4.',
            ],
            [
                'primary_query' => 'c-5 c-6 c-7 c-8 różnice',
                'mapped_title' => 'Porównanie C-5 do C-8',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-09-mandatory',
                'target_path' => '/znaki-drogowe/porownania/c-5-do-c-8',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Wyjaśnić jednym materiałem, kiedy jazda prosto nadal jest dostępna, a kiedy organizacja ruchu zostawia wyłącznie dwa warianty skrętu.',
                'correction_notes' => 'Trzymać w jednej rewizji z C-5, C-6, C-7 i C-8 oraz z opisami doboru pasa ruchu pod nakazy kierunkowe.',
                'competitor_notes' => 'Dobry supporting page pod częste pytanie egzaminacyjne o dwa podobne znaki różniące się możliwością jazdy na wprost.',
                'first_mover_note' => null,
                'watch_reason' => 'Kluczowy mini-klaster dla praktycznych decyzji o pasie ruchu.',
                'notes' => 'Supporting page porządkujący relacje między C-5, C-6, C-7 i C-8.',
            ],
            [
                'primary_query' => 'c-9 c-10 c-11 różnice',
                'mapped_title' => 'Porównanie C-9 do C-11',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-09-mandatory',
                'target_path' => '/znaki-drogowe/porownania/c-9-do-c-11',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Pokazać różnicę między objazdem z jednej strony a wariantem z obu stron przez praktykę dojazdu do przeszkody i moment wyboru toru jazdy.',
                'correction_notes' => 'Aktualizować razem z C-9, C-10 i C-11 oraz z przykładami objazdu wysp i robót drogowych.',
                'competitor_notes' => 'To bardzo czytelna intencja porównawcza, która dobrze odpowiada na realny problem kierowcy przy przeszkodzie na jezdni.',
                'first_mover_note' => null,
                'watch_reason' => 'Silne wsparcie dla najbardziej użytkowej rodziny objazdowej w kategorii C.',
                'notes' => 'Supporting page domykający rodzinę C-9 do C-11.',
            ],
            [
                'primary_query' => 'c-13 c-13a c-13/16 c-13a/16a c-16 c-16a różnice',
                'mapped_title' => 'Porównanie C-13 do C-16a',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-09-mandatory',
                'target_path' => '/znaki-drogowe/porownania/c-13-do-c-16a',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jednym materiałem uporządkować początki i końce dróg dla rowerów, pieszych oraz ciągów wspólnych, z perspektywy zmian w relacji do jezdni.',
                'correction_notes' => 'Rewidować wspólnie z C-13, C-13a, C-13/16, C-13a/16a, C-16 i C-16a oraz pilnować spójności nazewnictwa ciągów łączonych.',
                'competitor_notes' => 'To materiał, który może wygrać kompletnością, bo konkurencja zwykle rozbija te znaki na osobne, słabiej spięte odpowiedzi.',
                'first_mover_note' => null,
                'watch_reason' => 'Najszerszy supporting page w rodzinie C związanej z ruchem pieszym i rowerowym.',
                'notes' => 'Supporting page zbierający rodzinę C-13 do C-16a w jeden klaster praktyczny.',
            ],
            [
                'primary_query' => 'c-14 c-15 różnice',
                'mapped_title' => 'Porównanie C-14 i C-15',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-09-mandatory',
                'target_path' => '/znaki-drogowe/porownania/c-14-vs-c-15',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Wyjaśnić parą stron logikę początku i końca prędkości minimalnej, bez mieszania jej z ograniczeniem maksymalnym z kategorii B.',
                'correction_notes' => 'Utrzymywać ten materiał razem z C-14 i C-15 oraz z opisami relacji między nakazem minimalnej prędkości a warunkami bezpieczeństwa.',
                'competitor_notes' => 'Bardzo naturalne query porównawcze, które zamyka częstą pomyłkę między znakami C i B dotyczącymi prędkości.',
                'first_mover_note' => null,
                'watch_reason' => 'Mały, ale mocny duet egzaminacyjny w rodzinie technicznych nakazów C.',
                'notes' => 'Supporting page spinający C-14 i C-15.',
            ],
            [
                'primary_query' => 'c-18 c-19 różnice',
                'mapped_title' => 'Porównanie C-18 i C-19',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-09-mandatory',
                'target_path' => '/znaki-drogowe/porownania/c-18-vs-c-19',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jednym materiałem uporządkować początek i koniec obowiązku używania łańcuchów oraz przypomnieć, że odwołanie nakazu nie oznacza automatycznie idealnych warunków.',
                'correction_notes' => 'Aktualizować wspólnie z C-18 i C-19 oraz z przykładami warunków zimowych, w których kierowcy najczęściej lekceważą moment wejścia w obowiązek.',
                'competitor_notes' => 'Dobry materiał sezonowy, który łączy prostą definicję z praktyką jazdy zimą i w terenie górskim.',
                'first_mover_note' => null,
                'watch_reason' => 'Wzmacnia techniczny mini-klaster zimowy kategorii C.',
                'notes' => 'Supporting page dla duetu C-18 i C-19.',
            ],
        ]);
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function informationalQueryMapEntries(
        TrafficSignCategory $informationalCategory,
        array $signs,
        PolishInformationalSignCatalog $polishInformationalSignCatalog,
    ): array {
        $entries = [
            [
                'primary_query' => 'znaki informacyjne',
                'mapped_title' => 'Kategoria Znaki informacyjne',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-10-informational',
                'target_path' => '/znaki-drogowe/kategorie/znaki-informacyjne',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $informationalCategory->getKey(),
                'source_plan' => 'Kategoria ma prowadzić użytkownika od statusu drogi i przejść do znaków usługowych oraz podstawowych relacji organizacji ruchu z grupy D.',
                'correction_notes' => 'Rozbudowywać razem z kolejnymi znakami informacyjnymi i materiałami wspierającymi dla relacji jednokierunkowych, przejść oraz usług przy drodze.',
                'competitor_notes' => 'Przewaga tej kategorii powinna wynikać z czytelnego rozdzielenia znaków opisujących organizację ruchu od znaków usługowych i z kompletnego pakietu porównań.',
                'first_mover_note' => null,
                'watch_reason' => 'Pierwsza pełna publiczna odsłona kategorii znaków D.',
                'notes' => 'Category page opublikowana jako wejście do rollout-10 dla znaków informacyjnych.',
            ],
        ];

        foreach ($polishInformationalSignCatalog->all() as $signData) {
            $sign = $signs[$signData['slug']] ?? null;

            if (! $sign instanceof TrafficSign) {
                continue;
            }

            $entries[] = [
                'primary_query' => $signData['primary_query'],
                'mapped_title' => $signData['code'].' '.$signData['name'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => $this->informationalSearchIntentFor($signData['code']),
                'priority' => $this->informationalPriorityFor($signData['code']),
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-10-informational',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $informationalCategory->getKey(),
                'source_plan' => "Stronę {$signData['code']} oprzeć o oficjalne znaczenie znaku, praktyczny wpływ na decyzję kierowcy oraz relację z dalszą organizacją ruchu albo usługą widoczną z drogi.",
                'correction_notes' => "Przy następnej rewizji sprawdzić {$signData['code']} razem z oznakowaniem poziomym, najbliższymi znakami z grup A/B/C i najczęstszymi pytaniami egzaminacyjnymi lub sytuacyjnymi dla znaku D.",
                'competitor_notes' => "Treść {$signData['code']} ma wygrywać praktyką i czytelnym opisem sytuacji drogowej, a nie samą katalogową definicją znaku.",
                'first_mover_note' => null,
                'watch_reason' => "Element pierwszego kompletnego publicznego coverage kategorii D dla znaku {$signData['code']}.",
                'notes' => "Rollout-10: strona znaku {$signData['code']}.",
            ];
        }

        return array_merge($entries, [
            [
                'primary_query' => 'd-1 d-2 różnice',
                'mapped_title' => 'Porównanie D-1 i D-2',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-10-informational',
                'target_path' => '/znaki-drogowe/porownania/d-1-vs-d-2',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jedną stroną rozdzielić potwierdzenie drogi z pierwszeństwem od informacji o zakończeniu tego statusu, z naciskiem na zmianę sposobu obserwacji kolejnych skrzyżowań.',
                'correction_notes' => 'Aktualizować wspólnie z D-1 i D-2 oraz z materiałami o relacji do A-7 i B-20.',
                'competitor_notes' => 'To naturalne query porównawcze dla kierowcy, który pamięta symbol drogi z pierwszeństwem, ale niepewnie czyta znak odwołujący ten status.',
                'first_mover_note' => null,
                'watch_reason' => 'Mocny materiał wspierający dla podstawowej relacji pierwszeństwa w kategorii D.',
                'notes' => 'Supporting page otwierający comparison cluster dla D-1 i D-2.',
            ],
            [
                'primary_query' => 'd-4a d-4b różnice',
                'mapped_title' => 'Porównanie D-4a i D-4b',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-10-informational',
                'target_path' => '/znaki-drogowe/porownania/d-4a-vs-d-4b',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Pokazać różnicę między ślepą ulicą jako cechą całego odcinka a bocznym wlotem prowadzącym do drogi bez przejazdu.',
                'correction_notes' => 'Trzymać razem z D-4a i D-4b oraz z przykładami planowania manewru na osiedlach i w ciasnych ulicach miejskich.',
                'competitor_notes' => 'To query dobrze domyka intencję użytkownika, który widzi dwa podobne znaki o ślepej ulicy i nie wie, czy opisują to samo.',
                'first_mover_note' => null,
                'watch_reason' => 'Przydatny materiał praktyczny dla miejskiej organizacji ruchu i planowania trasy.',
                'notes' => 'Supporting page spinający rodzinę D-4a i D-4b.',
            ],
            [
                'primary_query' => 'd-6 d-6a różnice',
                'mapped_title' => 'Porównanie D-6 i D-6a',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-10-informational',
                'target_path' => '/znaki-drogowe/porownania/d-6-vs-d-6a',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jedną stroną rozdzielić przejście dla pieszych od przejazdu dla rowerzystów przez tempo dojazdu uczestnika ruchu i rodzaj obserwacji wymaganej od kierowcy.',
                'correction_notes' => 'Rewidować razem z D-6 i D-6a oraz z materiałami o A-16, A-24 i C-13.',
                'competitor_notes' => 'Bardzo naturalne query porównawcze, bo kierowcy często widzą oba znaki przez podobny filtr, mimo że sytuacja drogowa i dynamika uczestnika ruchu są inne.',
                'first_mover_note' => null,
                'watch_reason' => 'Silny materiał wspierający dla najważniejszych znaków D związanych z ruchem niechronionych uczestników.',
                'notes' => 'Supporting page dla duetu D-6 i D-6a.',
            ],
            [
                'primary_query' => 'd-18 d-23 d-28 d-34 różnice',
                'mapped_title' => 'Porównanie D-18, D-23, D-28 i D-34',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-10-informational',
                'target_path' => '/znaki-drogowe/porownania/d-18-d-23-d-28-d-34',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Uporządkować jednym materiałem znaki usługowe związane z parkingiem, paliwem, gastronomią i informacją turystyczną, z naciskiem na planowanie postoju i zjazdu.',
                'correction_notes' => 'Aktualizować wspólnie z D-18, D-23, D-28 i D-34 oraz z przyszłymi znakami usługowymi z tej samej rodziny.',
                'competitor_notes' => 'Dobry supporting page do spięcia mniej egzaminacyjnej, ale bardzo użytkowej części kategorii D wokół usług przy drodze.',
                'first_mover_note' => null,
                'watch_reason' => 'Pierwszy materiał wspierający porządkujący usługowe znaki D w jednym klastrze.',
                'notes' => 'Supporting page zbierający usługową rodzinę D-18, D-23, D-28 i D-34.',
            ],
        ]);
    }

    protected function mandatoryPriorityFor(string $code): string
    {
        return in_array($code, [
            'C-1',
            'C-2',
            'C-3',
            'C-4',
            'C-5',
            'C-6',
            'C-7',
            'C-8',
            'C-9',
            'C-10',
            'C-11',
            'C-12',
            'C-13',
            'C-13/16',
            'C-14',
            'C-16',
            'C-18',
        ], true)
            ? TrafficSignQueryMapEntry::PRIORITY_P1
            : TrafficSignQueryMapEntry::PRIORITY_P2;
    }

    protected function mandatorySearchIntentFor(string $code): string
    {
        return match (true) {
            in_array($code, ['C-14', 'C-15', 'C-18', 'C-19'], true) => TrafficSignQueryMapEntry::INTENT_EXAM,
            $code === 'C-17' => TrafficSignQueryMapEntry::INTENT_LEGAL,
            in_array($code, ['C-13', 'C-13a', 'C-13/16', 'C-13a/16a', 'C-16', 'C-16a'], true) => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
            default => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
        };
    }

    protected function informationalPriorityFor(string $code): string
    {
        return in_array($code, [
            'D-1',
            'D-2',
            'D-3',
            'D-4a',
            'D-4b',
            'D-6',
            'D-6a',
            'D-15',
            'D-17',
            'D-18',
        ], true)
            ? TrafficSignQueryMapEntry::PRIORITY_P1
            : TrafficSignQueryMapEntry::PRIORITY_P2;
    }

    protected function informationalSearchIntentFor(string $code): string
    {
        return match (true) {
            in_array($code, ['D-18', 'D-23', 'D-28', 'D-34'], true) => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
            in_array($code, ['D-6', 'D-6a', 'D-15', 'D-17'], true) => TrafficSignQueryMapEntry::INTENT_EXAM,
            $code === 'D-2' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
            default => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
        };
    }

    /**
     * @return array<string, array{builder_index: int, sort_order: int, source_notes: string, editorial_notes: string, review_notes: string}>
     */
    protected function rolloutFivePublishedSigns(): array
    {
        return [
            'A-5' => [
                'builder_index' => 4,
                'sort_order' => 30,
                'source_notes' => 'Rollout-05 otwiera publiczny batch warning signs od rodziny skrzyżowań. A-5 ma być prostą stroną wejściową do obserwacji układu skrzyżowania jeszcze przed ustaleniem szczegółów pierwszeństwa.',
                'editorial_notes' => 'Spinać z rodziną A-6, A-7 i A-8 oraz z materiałem wspierającym o czytaniu znaków skrzyżowaniowych.',
                'review_notes' => 'Publikacja w rollout-05 jako anchor page dla pierwszego warning mini-klastra o skrzyżowaniach.',
            ],
            'A-6a' => [
                'builder_index' => 5,
                'sort_order' => 40,
                'source_notes' => 'Wysoka wartość edukacyjna, bo znak wymusza szeroką obserwację obu stron skrzyżowania podporządkowanego.',
                'editorial_notes' => 'Trzymać w ścisłej relacji z A-6b i A-6c, żeby użytkownik mógł szybko zrozumieć różnicę między wariantami.',
                'review_notes' => 'Wpuszczony do rollout-05 razem z całą rodziną A-6, bez zostawiania asymetrii między wariantami znaku.',
            ],
            'A-6b' => [
                'builder_index' => 6,
                'sort_order' => 50,
                'source_notes' => 'Ważny dla pytań egzaminacyjnych i codziennego czytania podporządkowanych wlotów po prawej stronie.',
                'editorial_notes' => 'Połączyć z A-6c i jasno utrzymać kierunkowe różnice w obserwacji skrzyżowania.',
                'review_notes' => 'Publikacja w rollout-05 jako element pełnej rodziny znaków A-6 pierwszego batcha ostrzegawczego.',
            ],
            'A-6c' => [
                'builder_index' => 7,
                'sort_order' => 60,
                'source_notes' => 'Domyka podstawowe trio znaków podporządkowanych wlotów i wzmacnia query coverage dla lewego wariantu skrzyżowania.',
                'editorial_notes' => 'Trzymać spójnie z A-6a i A-6b oraz z materiałem porównawczym o rodzinie A-5 do A-8.',
                'review_notes' => 'Wchodzi do rollout-05, żeby warning mini-klaster o skrzyżowaniach był od razu czytelny i kompletny.',
            ],
            'A-8' => [
                'builder_index' => 11,
                'sort_order' => 70,
                'source_notes' => 'To silny znak użytkowy i egzaminacyjny, który dobrze domyka batch skrzyżowaniowy od strony ronda i ruchu okrężnego.',
                'editorial_notes' => 'Mocno spiąć z A-5 i A-7 oraz z materiałem wspierającym o znakach skrzyżowaniowych.',
                'review_notes' => 'Publikacja w rollout-05 jako drugi anchor page warning batcha obok A-5.',
            ],
            'A-16' => [
                'builder_index' => 22,
                'sort_order' => 80,
                'source_notes' => 'Jeden z najmocniejszych użytkowo warning signs. Strona ma przygotowywać pod realne zachowanie kierowcy przy przejściu dla pieszych, a nie tylko pod definicję znaku.',
                'editorial_notes' => 'Spinać z A-17 i A-24 oraz z materiałem wspierającym o pieszych, dzieciach i rowerzystach.',
                'review_notes' => 'W rollout-05 pełni rolę anchor page dla ostrzeżeń o niechronionych uczestnikach ruchu.',
            ],
            'A-24' => [
                'builder_index' => 31,
                'sort_order' => 90,
                'source_notes' => 'Silny kandydat do praktycznej odpowiedzi o ruchu rowerowym i przecięciu toru jazdy auta z rowerzystą.',
                'editorial_notes' => 'Łączyć z A-16 i A-17 w ramach mini-klastra o uczestnikach ruchu widocznych w pobliżu przejść i przejazdów.',
                'review_notes' => 'Publikacja w rollout-05 razem z A-16, żeby nie zostawić luki między pieszymi a rowerzystami w pierwszym batchu warning signs.',
            ],
        ];
    }

    /**
     * @return array<string, array{builder_index: int, sort_order: int, source_notes: string, editorial_notes: string, review_notes: string}>
     */
    protected function rolloutSixPublishedSigns(): array
    {
        return [
            'A-1' => [
                'builder_index' => 0,
                'sort_order' => 100,
                'source_notes' => 'Rollout-06 otwiera warning klaster o zakrętach od podstawowego pojedynczego łuku w prawo. To strona o wysokiej wartości praktycznej dla kierowcy, który szuka szybkiej odpowiedzi, jak wcześniej ustawić prędkość.',
                'editorial_notes' => 'Spinać bezpośrednio z A-2, A-3 i A-4 oraz z materiałem wspierającym o różnicy między pojedynczym zakrętem a serią zakrętów.',
                'review_notes' => 'Publikacja w rollout-06 jako anchor page mini-klastra o niebezpiecznych zakrętach.',
            ],
            'A-2' => [
                'builder_index' => 1,
                'sort_order' => 110,
                'source_notes' => 'Naturalna para dla A-1. Wchodzi od razu, żeby nie zostawić luki między prawym i lewym wariantem pojedynczego niebezpiecznego zakrętu.',
                'editorial_notes' => 'Pilnować symetrii merytorycznej z A-1 i nie rozjechać logiki obserwacji lewego oraz prawego łuku.',
                'review_notes' => 'Rollout-06 publikuje A-2 równolegle z A-1, żeby klaster zakrętów był od razu kompletny kierunkowo.',
            ],
            'A-3' => [
                'builder_index' => 2,
                'sort_order' => 120,
                'source_notes' => 'Silny znak edukacyjny dla pytań o serię zakrętów i o to, dlaczego pierwszy łuk nie kończy zagrożenia na odcinku.',
                'editorial_notes' => 'Mocno łączyć z A-4 oraz z supporting page A-1 do A-4, bo użytkownik bardzo często myli pojedynczy zakręt z sekwencją zakrętów.',
                'review_notes' => 'Publikacja w rollout-06 jako część pełnej rodziny ostrzeżeń o zakrętach.',
            ],
            'A-4' => [
                'builder_index' => 3,
                'sort_order' => 130,
                'source_notes' => 'Domyka rodzinę zakrętów od strony sekwencji zaczynającej się w lewo i poprawia pełne query coverage dla jednego z najczęściej mylonych wariantów.',
                'editorial_notes' => 'Trzymać blisko A-3 i dbać o czyste rozróżnienie: seria zakrętów, nie pojedynczy łuk.',
                'review_notes' => 'Wchodzi do rollout-06 razem z A-3, żeby warning batch o zakrętach był semantycznie domknięty.',
            ],
            'A-9' => [
                'builder_index' => 12,
                'sort_order' => 140,
                'source_notes' => 'To ważna strona użytkowa, bo dotyczy dojazdu do przejazdu kolejowego z zaporami i porządkuje obserwację torów, sygnałów oraz zapór.',
                'editorial_notes' => 'Spinać z A-10 i z materiałem porównawczym o przejazdach kolejowych, żeby nie rozdzielać logicznie pary z zaporami / bez zapór.',
                'review_notes' => 'Publikacja w rollout-06 jako anchor page mini-klastra o przejazdach kolejowych.',
            ],
            'A-10' => [
                'builder_index' => 13,
                'sort_order' => 150,
                'source_notes' => 'Wysoka wartość praktyczna i egzaminacyjna. Strona ma podkreślać różnicę między przejazdem bez zapór a wariantem z zaporami bez uciekania w prawniczy ton.',
                'editorial_notes' => 'Pilnować spójności z A-9 oraz z supporting page A-9 vs A-10.',
                'review_notes' => 'W rollout-06 publikuje się równolegle z A-9, żeby klaster kolejowy nie był asymetryczny.',
            ],
            'A-11' => [
                'builder_index' => 14,
                'sort_order' => 160,
                'source_notes' => 'Mocny znak praktyczny dla codziennej jazdy. Ta strona ma tłumaczyć nie tylko samą nierówną drogę, ale też zmianę zachowania auta na nawierzchni.',
                'editorial_notes' => 'Połączyć z A-11a i rodziną A-12, bo użytkownik często pyta o wszystkie ostrzeżenia nawierzchniowo-przewężeniowe podczas jednego czytania.',
                'review_notes' => 'Rollout-06 włącza A-11 jako wejście do mini-klastra o nawierzchni i zwężeniach.',
            ],
            'A-11a' => [
                'builder_index' => 15,
                'sort_order' => 170,
                'source_notes' => 'Ważny znak miejski i osiedlowy. Strona ma szybko rozróżnić próg zwalniający od ogólnie nierównej drogi.',
                'editorial_notes' => 'Trzymać w relacji do A-11 i do materiału A-11 do A-12c, żeby użytkownik łatwo odróżniał rodzaj ostrzeżenia.',
                'review_notes' => 'Publikacja w rollout-06, bo bez A-11a klaster nawierzchniowy byłby zbyt teoretyczny i niepełny.',
            ],
            'A-12a' => [
                'builder_index' => 16,
                'sort_order' => 180,
                'source_notes' => 'Kluczowy wariant ostrzeżenia o przewężeniu, bo pokazuje obustronne zmniejszenie marginesu jezdni.',
                'editorial_notes' => 'Spinać z A-12b i A-12c oraz z materiałem porównawczym o rodzinie A-11 do A-12c.',
                'review_notes' => 'W rollout-06 pełni rolę anchor page dla rodziny znaków o zwężeniu jezdni.',
            ],
            'A-12b' => [
                'builder_index' => 17,
                'sort_order' => 190,
                'source_notes' => 'Rozszerza klaster przewężeń o prawostronny wariant, ważny dla praktycznej obserwacji krawędzi jezdni i mijania.',
                'editorial_notes' => 'Pilnować jasnej różnicy względem A-12c i A-12a; tu ważna jest strona, z której kierowca traci margines.',
                'review_notes' => 'Publikacja w rollout-06 razem z całą rodziną A-12 dla pełnego pokrycia podstawowych wariantów zwężenia.',
            ],
            'A-12c' => [
                'builder_index' => 18,
                'sort_order' => 200,
                'source_notes' => 'Domyka rodzinę przewężeń od strony lewego wariantu i wzmacnia kompletność klastra pod realne porównania kierowców i kursantów.',
                'editorial_notes' => 'Trzymać równą logikę z A-12b oraz z supporting page A-11 do A-12c.',
                'review_notes' => 'Wpuszczony do rollout-06, żeby warning cluster o przewężeniach był od razu pełny kierunkowo.',
            ],
        ];
    }

    /**
     * @return array<string, array{builder_index: int, sort_order: int, source_notes: string, editorial_notes: string, review_notes: string}>
     */
    protected function rolloutSevenPublishedSigns(): array
    {
        return [
            'A-6d' => [
                'builder_index' => 8,
                'sort_order' => 210,
                'source_notes' => 'Rollout-07 domyka rodzinę A-6 o wariant wlotu drogi jednokierunkowej z prawej strony. To ważny znak porządkujący obserwację przy bardziej złożonych układach skrzyżowań.',
                'editorial_notes' => 'Spinać z A-6e oraz z wcześniejszym mini-klastrem A-5 do A-8, żeby cała rodzina skrzyżowaniowa była czytana jako jeden logiczny system.',
                'review_notes' => 'Publikacja w rollout-07 jako domknięcie warning rodziny podporządkowanych i jednokierunkowych wlotów.',
            ],
            'A-6e' => [
                'builder_index' => 9,
                'sort_order' => 220,
                'source_notes' => 'Naturalny partner dla A-6d. Strona ma szybko rozdzielać lewy i prawy wariant wlotu drogi jednokierunkowej.',
                'editorial_notes' => 'Trzymać w ścisłej relacji z A-6d i supporting page A-6d vs A-6e.',
                'review_notes' => 'Wpuszczony do rollout-07 razem z A-6d, żeby nie zostawiać asymetrii w rodzinie A-6.',
            ],
            'A-14' => [
                'builder_index' => 20,
                'sort_order' => 230,
                'source_notes' => 'Mocny znak praktyczny i sezonowy. Strona ma przygotowywać pod zmianę organizacji ruchu, obecność ludzi i sprzętu oraz zawężony margines błędu na odcinku robót.',
                'editorial_notes' => 'Łączyć z A-15 i A-20 oraz z materiałem wspierającym o zmianie rytmu jazdy na odcinku.',
                'review_notes' => 'Rollout-07 otwiera mini-klaster ostrzeżeń o zmiennych warunkach organizacji ruchu i nawierzchni.',
            ],
            'A-15' => [
                'builder_index' => 21,
                'sort_order' => 240,
                'source_notes' => 'Silny znak użytkowy, ważny dla pytań o przyczepność, hamowanie i zachowanie auta na nawierzchni.',
                'editorial_notes' => 'Spinać z A-14, A-20 oraz później z A-28 i A-32 w szerszym klastrze warunków nawierzchniowych.',
                'review_notes' => 'Publikacja w rollout-07 jako jeden z core warning pages dla bezpiecznej jazdy w gorszych warunkach nawierzchni.',
            ],
            'A-18a' => [
                'builder_index' => 24,
                'sort_order' => 250,
                'source_notes' => 'Daje praktyczne pokrycie znaku o zwierzętach gospodarskich, ważnego szczególnie na drogach lokalnych i w terenach wiejskich.',
                'editorial_notes' => 'Trzymać blisko A-18b i materiału porównawczego o zwierzętach na drodze.',
                'review_notes' => 'W rollout-07 publikuje się równolegle z A-18b, żeby mini-klaster zwierząt był od razu kompletny.',
            ],
            'A-18b' => [
                'builder_index' => 25,
                'sort_order' => 260,
                'source_notes' => 'Wysoka wartość praktyczna i szeroka rozpoznawalność. Strona ma tłumaczyć nie tylko obecność zwierząt dzikich, ale też różnicę wobec zwierząt gospodarskich.',
                'editorial_notes' => 'Połączyć z A-18a i supporting page A-18a vs A-18b.',
                'review_notes' => 'Rollout-07 zamyka parę warning signs o zwierzętach z pełnym porównaniem użytkowym.',
            ],
            'A-20' => [
                'builder_index' => 27,
                'sort_order' => 270,
                'source_notes' => 'Bardzo ważny znak praktyczny przy zmianie organizacji ruchu. Strona ma jasno pokazać moment powrotu do pełnej obserwacji ruchu z przeciwka.',
                'editorial_notes' => 'Spinać z A-14, A-15 oraz z materiałem wspierającym A-14, A-15 i A-20.',
                'review_notes' => 'Publikacja w rollout-07 jako anchor page dla ostrzeżeń o zmianie rytmu jazdy na odcinku.',
            ],
            'A-29' => [
                'builder_index' => 36,
                'sort_order' => 280,
                'source_notes' => 'Silny znak miejski i egzaminacyjny. Strona ma przygotowywać pod dojazd do sygnalizacji, a nie tylko tłumaczyć symbol świateł.',
                'editorial_notes' => 'Łączyć z A-30 i pilnować czytelnej różnicy między ostrzeżeniem konkretnym a ogólnym.',
                'review_notes' => 'Rollout-07 włącza A-29 jako praktyczną stronę wejściową dla pytań o sygnalizację świetlną.',
            ],
            'A-30' => [
                'builder_index' => 37,
                'sort_order' => 290,
                'source_notes' => 'To jeden z ważniejszych znaków z perspektywy jakości treści, bo wymaga spokojnego wyjaśnienia, że sam symbol jest ogólny i działa z kontekstem lub tabliczką.',
                'editorial_notes' => 'Trzymać w relacji do A-29 oraz przyszłych przykładów użycia A-30 z tabliczkami doprecyzowującymi.',
                'review_notes' => 'Publikacja w rollout-07 jako domknięcie mini-klastra A-29 / A-30 o sygnałach i zagrożeniach ogólnych.',
            ],
        ];
    }

    /**
     * @return array<string, array{builder_index: int, sort_order: int, source_notes: string, editorial_notes: string, review_notes: string}>
     */
    protected function rolloutEightPublishedSigns(): array
    {
        return [
            'A-13' => [
                'builder_index' => 19,
                'sort_order' => 300,
                'source_notes' => 'Rollout-08 zamyka warning katalog od znaku A-13, który dobrze porządkuje mniej codzienne, ale ważne sytuacje infrastrukturalne. Strona ma szybko wyjaśnić, że zagrożenie wynika z ruchomego mostu i zmiennej organizacji przejazdu.',
                'editorial_notes' => 'Spinać z A-19 i A-21 oraz z materiałem porównawczym o nietypowych źródłach zagrożenia na odcinku.',
                'review_notes' => 'Publikacja w rollout-08 jako część finalnego batcha domykającego warning cluster bez luk katalogowych.',
            ],
            'A-19' => [
                'builder_index' => 26,
                'sort_order' => 310,
                'source_notes' => 'Silny znak praktyczny dla mostów, otwartych przestrzeni i wysokich pojazdów. Treść ma iść w zachowanie kierowcy przy bocznym podmuchu, nie w suchą definicję.',
                'editorial_notes' => 'Łączyć z A-13 i A-21 oraz później z szerszym klastrem warunków drogowych i pogodowych.',
                'review_notes' => 'Rollout-08 publikuje A-19 jako jedną z najmocniejszych stron behavioral w końcówce warning signs.',
            ],
            'A-21' => [
                'builder_index' => 28,
                'sort_order' => 320,
                'source_notes' => 'Ważny znak miejski i egzaminacyjny. Strona ma porządkować relację kierowcy do torowiska, pierwszeństwa i punktów przecięcia z ruchem tramwajowym.',
                'editorial_notes' => 'Spinać z A-13 i A-19 oraz dbać o prosty, operacyjny ton bez rozwadniania w przepisy poboczne.',
                'review_notes' => 'Publikacja w rollout-08 jako domknięcie mini-klastra ostrzeżeń o specyficznym układzie odcinka.',
            ],
            'A-22' => [
                'builder_index' => 29,
                'sort_order' => 330,
                'source_notes' => 'Strona ma od razu ustawiać zachowanie kierowcy na zjeździe: hamowanie silnikiem, zapas prędkości i wcześniejsze uspokojenie auta.',
                'editorial_notes' => 'Trzymać w ścisłej relacji z A-23 i materiałem porównawczym o zjeździe oraz podjeździe.',
                'review_notes' => 'Rollout-08 publikuje A-22 jako anchor page mini-klastra o zmianie nachylenia drogi.',
            ],
            'A-23' => [
                'builder_index' => 30,
                'sort_order' => 340,
                'source_notes' => 'Naturalny partner dla A-22. Odpowiedź ma tłumaczyć podjazd przez dobór tempa, biegów i obserwacji, bez robienia z tego strony tylko dla ciężkich pojazdów.',
                'editorial_notes' => 'Pilnować symetrii z A-22 i porównania A-22 vs A-23.',
                'review_notes' => 'Publikacja w rollout-08 razem z A-22, żeby nie zostawiać asymetrii w rodzinie nachylenia drogi.',
            ],
            'A-25' => [
                'builder_index' => 32,
                'sort_order' => 350,
                'source_notes' => 'To dobry znak do praktycznego opisu ryzyka związanego z osuwiskiem, ścianą skalną i ograniczonym czasem reakcji na odłamki.',
                'editorial_notes' => 'Łączyć z A-26, A-27 i A-28 jako częścią szerszego klastru zagrożeń terenowych i otoczenia drogi.',
                'review_notes' => 'Rollout-08 traktuje A-25 jako wejście do mini-klastra ostrzeżeń środowiskowych i terenowych.',
            ],
            'A-26' => [
                'builder_index' => 33,
                'sort_order' => 360,
                'source_notes' => 'Strona ma szybko wyjaśnić, że problemem nie jest samo lotnisko, tylko hałas, rozproszenie i specyficzne warunki w sąsiedztwie pasa lub osi podejścia.',
                'editorial_notes' => 'Spinać z A-25, A-27 i A-28, ale zachować własny, czytelny kontekst lotniczy.',
                'review_notes' => 'Publikacja w rollout-08 jako element kończący grupę warning signs o nietypowym otoczeniu drogi.',
            ],
            'A-27' => [
                'builder_index' => 34,
                'sort_order' => 370,
                'source_notes' => 'Ważny znak pod praktykę utrzymania toru jazdy przy samej krawędzi i pod świadomość konsekwencji błędu na nabrzeżu albo brzegu rzeki.',
                'editorial_notes' => 'Łączyć z A-25, A-26 i A-28 oraz mocno pilnować operacyjnego języka bezpieczeństwa.',
                'review_notes' => 'Rollout-08 publikuje A-27 jako stronę o wysokiej wartości edukacyjnej mimo mniejszej codzienności query.',
            ],
            'A-28' => [
                'builder_index' => 35,
                'sort_order' => 380,
                'source_notes' => 'Silny znak użytkowy, bo łatwo przełożyć go na zachowanie auta na luźnej nawierzchni, hamowanie i bezpieczne odstępy.',
                'editorial_notes' => 'Spinać z A-15 i A-32 w dalszym quality passie warunków nawierzchniowych, ale na teraz utrzymać go w rodzinie A-25 do A-28.',
                'review_notes' => 'Publikacja w rollout-08 jako najbardziej praktyczny behavioral page końcówki warning batcha terenowego.',
            ],
            'A-31' => [
                'builder_index' => 38,
                'sort_order' => 390,
                'source_notes' => 'To znak bardzo praktyczny przy wąskich drogach lokalnych i poboczu bez nośności. Treść ma mówić o prowadzeniu auta, nie o samej nazwie znaku.',
                'editorial_notes' => 'Łączyć z A-32, A-33 i A-34 oraz z materiałem porównawczym o końcowej rodzinie warning signs.',
                'review_notes' => 'Rollout-08 otwiera ostatni mini-klaster warningów związanych ze stanem odcinka i sytuacją na drodze.',
            ],
            'A-32' => [
                'builder_index' => 39,
                'sort_order' => 400,
                'source_notes' => 'Bardzo praktyczna strona sezonowa. Akcentować przyczepność, temperaturę nawierzchni i konieczność spokojnego wejścia w manewry przy oszronieniu.',
                'editorial_notes' => 'Dobrze spinać z A-15 i A-28, ale w rollout-08 trzymać ją przede wszystkim z A-31, A-33 i A-34.',
                'review_notes' => 'Publikacja w rollout-08 jako jeden z najmocniejszych użytkowo znaków kończących warning katalog.',
            ],
            'A-33' => [
                'builder_index' => 40,
                'sort_order' => 410,
                'source_notes' => 'Strona ma od razu ustawiać kierowcę na czytanie hamowania kolumny pojazdów i na unikanie dojechania zbyt szybko do końca zatoru.',
                'editorial_notes' => 'Spinać z A-34 i końcowym materiałem porównawczym A-31 do A-34.',
                'review_notes' => 'Rollout-08 publikuje A-33 jako ważny behavioral page dla codziennej jazdy w ruchu miejskim i trasowym.',
            ],
            'A-34' => [
                'builder_index' => 41,
                'sort_order' => 420,
                'source_notes' => 'To mocna strona pod realną ostrożność przy miejscu zdarzenia i wtórnych zagrożeniach na drodze. Ma być konkretna, nie sensacyjna.',
                'editorial_notes' => 'Trzymać blisko A-33 oraz materiału A-31 do A-34, żeby końcówka warning catalog była semantycznie domknięta.',
                'review_notes' => 'Publikacja w rollout-08 finalizuje cały publiczny warning cluster: 42/42 znaków ostrzegawczych.',
            ],
        ];
    }

    /**
     * @return array<string, array{builder_index: int, sort_order: int, source_notes: string, editorial_notes: string, review_notes: string}>
     */
    protected function rolloutTwoPublishedSigns(): array
    {
        return [
            'B-21' => [
                'builder_index' => 21,
                'sort_order' => 60,
                'source_notes' => 'Rollout-02 otwiera klaster znaków manewrowych. Ta strona ma odpowiadać szybko i praktycznie na pytanie o zakaz skrętu w lewo oraz o relację do zawracania.',
                'editorial_notes' => 'Spinać z B-23 i supporting page B-21 vs B-23, bo użytkownicy bardzo często mieszają oba zakazy.',
                'review_notes' => 'Opublikowany jako część pierwszego dużego batcha znaków zakazu po rollout-01. Priorytet: intencja egzaminacyjna i praktyczna na skrzyżowaniach.',
            ],
            'B-22' => [
                'builder_index' => 22,
                'sort_order' => 70,
                'source_notes' => 'Wysoka wartość użytkowa przy pytaniach o relację znaków zakazu do pasów i sygnalizacji kierunkowej.',
                'editorial_notes' => 'W przyszłości rozwinąć o sytuacje, w których zakaz skrętu w prawo występuje przy pasie do jazdy w prawo z innych znaków i sygnałów.',
                'review_notes' => 'Publikacja w rollout-02, żeby domknąć podstawową parę zakazów kierunkowych B-21 / B-22.',
            ],
            'B-23' => [
                'builder_index' => 23,
                'sort_order' => 80,
                'source_notes' => 'Silny kandydat egzaminacyjny. Strona ma jasno tłumaczyć różnicę między zawracaniem a skrętem w lewo bez prawniczego rozwlekania.',
                'editorial_notes' => 'Łączyć z B-21 i B-24 oraz z materiałem wspierającym o zakazie zawracania.',
                'review_notes' => 'Weszło do batcha jako jeden z core pages dla klastrów manewrowych.',
            ],
            'B-24' => [
                'builder_index' => 24,
                'sort_order' => 90,
                'source_notes' => 'Znak kończący, ale ważny dla kompletności klastra B-23 i dla pytań o odwołanie wcześniejszego zakazu.',
                'editorial_notes' => 'Dobrze później uzupełnić o relację do innych znaków kończących zakazy oraz do oznakowania poziomego.',
                'review_notes' => 'Publikacja razem z B-23, żeby nie zostawiać luki w parze zakaz / koniec zakazu.',
            ],
            'B-25' => [
                'builder_index' => 25,
                'sort_order' => 100,
                'source_notes' => 'Strona o wysokim potencjale egzaminacyjnym i bezpieczeństwa ruchu. Ma opierać się na praktyce manewru, a nie tylko na suchej definicji.',
                'editorial_notes' => 'W kolejnym kroku spinać z B-27 oraz z przyszłym materiałem o relacji zakazu wyprzedzania do linii ciągłej.',
                'review_notes' => 'W rollout-02 traktowany jako jeden z anchor pages całej kategorii znaków zakazu.',
            ],
            'B-27' => [
                'builder_index' => 27,
                'sort_order' => 110,
                'source_notes' => 'Potrzebny jako naturalne domknięcie klastru o wyprzedzaniu i o znakach odwołujących ograniczenia.',
                'editorial_notes' => 'Połączyć z B-25 i jasno podkreślić, że koniec zakazu nie oznacza dowolności przy złej widoczności albo linii ciągłej.',
                'review_notes' => 'Wpuszczony razem z B-25, żeby klaster wyprzedzania był od razu semantycznie pełny.',
            ],
            'B-33' => [
                'builder_index' => 33,
                'sort_order' => 120,
                'source_notes' => 'To jedna z najmocniejszych stron pod zapytania egzaminacyjne i mandatowe. Ma być konkretna, szybka i czytelna operacyjnie.',
                'editorial_notes' => 'Spinać z B-34, B-43 i B-44 oraz z przyszłymi treściami o odwoływaniu ograniczeń prędkości.',
                'review_notes' => 'Rollout-02 traktuje B-33 jako główny anchor page dla całego mini-klastra prędkości.',
            ],
            'B-34' => [
                'builder_index' => 34,
                'sort_order' => 130,
                'source_notes' => 'Strona kończąca ograniczenie jest potrzebna, bo użytkownicy często pytają nie tylko o sam limit, ale o moment jego odwołania.',
                'editorial_notes' => 'Wzmocnić relacje do B-33 i B-43 w kolejnych porównaniach.',
                'review_notes' => 'Publikowana razem z B-33, żeby zachować kompletność klastra ograniczeń prędkości.',
            ],
            'B-37' => [
                'builder_index' => 37,
                'sort_order' => 140,
                'source_notes' => 'Wysoka wartość praktyczna w mieście. Strona ma wyjaśnić nie tylko zakaz, ale też sposób czytania dni i zmian strony postoju.',
                'editorial_notes' => 'Łączyć z B-38 oraz z istniejącymi B-35 / B-36, bo użytkownik zwykle porównuje te znaki w jednej sesji.',
                'review_notes' => 'Wpuszczony do pierwszego dużego batcha parkingowego po pozytywnym rollout-01 dla B-35 i B-36.',
            ],
            'B-38' => [
                'builder_index' => 38,
                'sort_order' => 150,
                'source_notes' => 'Powinien wejść równolegle z B-37, bo intencja użytkownika jest zwykle parami i opiera się na porównaniu dni parzystych / nieparzystych.',
                'editorial_notes' => 'Dobrze później dołożyć materiał wspierający o zmianie strony postoju przy B-37 i B-38.',
                'review_notes' => 'Publikacja równoległa do B-37 w tym samym batchu, żeby nie zostawiać asymetrii kategorii.',
            ],
            'B-43' => [
                'builder_index' => 43,
                'sort_order' => 160,
                'source_notes' => 'Strefa ograniczonej prędkości to częste źródło pomyłek względem zwykłego B-33, dlatego publikujemy ją razem z materiałem porównawczym.',
                'editorial_notes' => 'Spinać z B-33 i B-44 oraz z materiałem wspierającym o różnicy między limitem punktowym i strefą.',
                'review_notes' => 'W rollout-02 pełni rolę drugiego anchor page klastra prędkości, obok B-33.',
            ],
            'B-44' => [
                'builder_index' => 44,
                'sort_order' => 170,
                'source_notes' => 'Potrzebny jako logiczne domknięcie strefy ograniczonej prędkości i odpowiedź na pytania o moment wygaśnięcia zasad strefy.',
                'editorial_notes' => 'Łączyć z B-43 i podkreślać różnicę między końcem strefy a końcem zwykłego ograniczenia punktowego.',
                'review_notes' => 'Publikacja razem z B-43, żeby od razu zamknąć mini-klaster strefy ograniczonej prędkości.',
            ],
        ];
    }

    /**
     * @return array<string, array{builder_index: int, sort_order: int, source_notes: string, editorial_notes: string, review_notes: string}>
     */
    protected function rolloutThreePublishedSigns(): array
    {
        return [
            'B-3' => [
                'builder_index' => 3,
                'sort_order' => 180,
                'source_notes' => 'Rollout-03 otwiera rodzinę znaków zakazu wjazdu dla konkretnych grup pojazdów. B-3 ma być anchor page dla pytań o pojazdy silnikowe i wyjątki z tabliczek.',
                'editorial_notes' => 'Powiązać z B-5 i B-7 oraz z materiałem porównującym zakazy dla pojazdów silnikowych, ciężarowych i z przyczepą.',
                'review_notes' => 'Publikacja w rollout-03 jako pierwszy duży batch znaków zakazu wjazdu dla grup pojazdów.',
            ],
            'B-3a' => [
                'builder_index' => 4,
                'sort_order' => 190,
                'source_notes' => 'Strona uzupełniająca dla bardziej wyspecjalizowanego zakazu wjazdu autobusów.',
                'editorial_notes' => 'W kolejnych rewizjach rozważyć krótką sekcję o wyjątkach dla komunikacji miejskiej i przewozów zorganizowanych, jeśli będą potrzebne.',
                'review_notes' => 'Dodany do batcha jako część kompletnego pokrycia zakazów grupowych.',
            ],
            'B-4' => [
                'builder_index' => 5,
                'sort_order' => 200,
                'source_notes' => 'Wartość użytkowa bierze się z prostego pytania, kogo dotyczy zakaz i jak czytać wyjątki na tabliczkach.',
                'editorial_notes' => 'Połączyć z B-3 i B-9 przy dalszej rozbudowie grup zakazu dla różnych uczestników ruchu.',
                'review_notes' => 'Publikacja w rollout-03, żeby kategoria nie miała dziur między znakami wjazdu grupowego.',
            ],
            'B-5' => [
                'builder_index' => 6,
                'sort_order' => 210,
                'source_notes' => 'Silny kandydat użytkowy dla kierowców zawodowych i pytań o różnice między ciężarówką a szerszą grupą pojazdów silnikowych.',
                'editorial_notes' => 'Mocno spiąć z B-3 i B-7 oraz z materiałem porównawczym o zakazach dla ciężarówek i zestawów.',
                'review_notes' => 'Opublikowany jako jeden z ważniejszych anchor pages rollout-03.',
            ],
            'B-6' => [
                'builder_index' => 7,
                'sort_order' => 220,
                'source_notes' => 'Strona uzupełniająca dla zakazu wjazdu ciągników rolniczych i wolniejszego ruchu specjalistycznego.',
                'editorial_notes' => 'Warto później zobaczyć, czy potrzebne będzie bardziej praktyczne rozwinięcie pod lokalny ruch rolniczy.',
                'review_notes' => 'Wprowadzony dla kompletności rodziny zakazów wjazdu grupowego.',
            ],
            'B-7' => [
                'builder_index' => 8,
                'sort_order' => 230,
                'source_notes' => 'Bardzo praktyczny znak dla pytań o zestawy z przyczepą i o rozróżnienie względem zwykłej ciężarówki.',
                'editorial_notes' => 'Spinać z B-3 i B-5 oraz z materiałem porównującym różne zakazy wjazdu dla zestawów i pojazdów ciężkich.',
                'review_notes' => 'Publikacja w rollout-03 jako trzeci silny punkt mini-klastra zakazów grupowego wjazdu.',
            ],
            'B-8' => [
                'builder_index' => 9,
                'sort_order' => 240,
                'source_notes' => 'Niższy wolumen, ale ważny dla kompletności oficjalnego katalogu i zachowania pełnego pokrycia kategorii.',
                'editorial_notes' => 'Na późniejszym etapie można sprawdzić, czy są potrzebne przykłady praktycznego użycia w ruchu lokalnym lub turystycznym.',
                'review_notes' => 'Publikacja dla kompletności rodziny zakazów grupowych.',
            ],
            'B-9' => [
                'builder_index' => 10,
                'sort_order' => 250,
                'source_notes' => 'Silna użyteczność pod pytania o relację rowerzysty do organizacji ruchu i wyjątków lokalnych.',
                'editorial_notes' => 'Później powiązać z klastrem rowerowym i znakami dotyczącymi ruchu pieszo-rowerowego, jeśli taki powstanie.',
                'review_notes' => 'Wszedł do rollout-03, bo ma sens użytkowy większy niż tylko formalna kompletność.',
            ],
            'B-10' => [
                'builder_index' => 11,
                'sort_order' => 260,
                'source_notes' => 'Strona uzupełniająca dla motorowerów i pytań o zakres zakazu wobec lżejszych pojazdów silnikowych.',
                'editorial_notes' => 'W kolejnych rewizjach warto doprecyzować relację do innych lekkich pojazdów z punktu widzenia użytkownika.',
                'review_notes' => 'Publikacja w rollout-03 dla pełnego pokrycia grup lekkich pojazdów.',
            ],
            'B-11' => [
                'builder_index' => 12,
                'sort_order' => 270,
                'source_notes' => 'Niższy popyt, ale znak zostaje opublikowany jako część pełnego pokrycia oficjalnego katalogu grupowego zakazu wjazdu.',
                'editorial_notes' => 'Przy przyszłym review sprawdzić, czy potrzebna jest mocniejsza sekcja wyjaśniająca sam zakres pojęcia wózka rowerowego.',
                'review_notes' => 'Publikacja dla kompletności i spójności klastru grup pojazdów.',
            ],
            'B-12' => [
                'builder_index' => 13,
                'sort_order' => 280,
                'source_notes' => 'Znak specjalistyczny, ale ważny dla zachowania kompletności i dla rzetelności całej kategorii.',
                'editorial_notes' => 'Można później skrócić lub rozbudować zależnie od realnych danych z query mapy i GSC.',
                'review_notes' => 'Włączony do batcha jako element pełnego pokrycia zakazów wjazdu.',
            ],
            'B-13' => [
                'builder_index' => 14,
                'sort_order' => 290,
                'source_notes' => 'Anchor page dla mini-klastra materiałów niebezpiecznych. Ma jasno tłumaczyć różnicę między materiałami wybuchowymi, niebezpiecznymi i skażającymi wodę.',
                'editorial_notes' => 'Spinać z B-13a i B-14 oraz z materiałem porównującym trzy znaki dotyczące ładunków niebezpiecznych.',
                'review_notes' => 'Publikacja w rollout-03 jako główny punkt klastra ADR i zakazów środowiskowych.',
            ],
            'B-13a' => [
                'builder_index' => 15,
                'sort_order' => 300,
                'source_notes' => 'Silny znak porównawczy wobec B-13 i B-14, potrzebny do uniknięcia luk w rodzinie zakazów dla przewozów niebezpiecznych.',
                'editorial_notes' => 'Później rozważyć mocniejsze przykłady praktycznego czytania tych znaków przez kierowcę ciężarówki.',
                'review_notes' => 'Publikowany razem z B-13 i B-14, żeby klaster był od razu semantycznie pełny.',
            ],
            'B-14' => [
                'builder_index' => 16,
                'sort_order' => 310,
                'source_notes' => 'Potrzebny, żeby wyraźnie rozróżnić zakazy związane z ochroną środowiska od szerszych zakazów ADR.',
                'editorial_notes' => 'Powiązać z B-13/B-13a i w przyszłości rozważyć supporting page o znakach zakazu dla przewozów niebezpiecznych.',
                'review_notes' => 'Domyka w rollout-03 mini-klaster materiałów niebezpiecznych.',
            ],
            'B-41' => [
                'builder_index' => 17,
                'sort_order' => 320,
                'source_notes' => 'Strona o czytelnej wartości użytkowej, bo dotyczy relacji zakazu do ruchu pieszego i wyjątków wynikających z organizacji terenu.',
                'editorial_notes' => 'W przyszłości można rozważyć powiązanie z klastrem pieszym i strefami o ograniczonym dostępie.',
                'review_notes' => 'Wpisany do rollout-03 jako znak o praktycznym znaczeniu dla ruchu pieszego.',
            ],
            'B-42' => [
                'builder_index' => 18,
                'sort_order' => 330,
                'source_notes' => 'Ważny dla zrozumienia znaków kończących ograniczenia i dla kompletności oficjalnego katalogu zakazów.',
                'editorial_notes' => 'Później można rozwinąć o relację do innych znaków kończących oraz do tego, jakie zakazy kończy w praktyce dany układ oznakowania.',
                'review_notes' => 'Publikowany jako uzupełnienie batcha i ważny element semantycznego domknięcia kategorii.',
            ],
        ];
    }

    /**
     * @return array<string, array{builder_index: int, sort_order: int, source_notes: string, editorial_notes: string, review_notes: string}>
     */
    protected function rolloutFourPublishedSigns(): array
    {
        return [
            'B-15' => [
                'builder_index' => 15,
                'sort_order' => 340,
                'source_notes' => 'Rollout-04 domyka rodzinę znaków gabarytowych. B-15 ma wyraźnie tłumaczyć ograniczenie szerokości i potrzebę czytania realnych parametrów zestawu.',
                'editorial_notes' => 'Spinać z B-16, B-17, B-18 i B-19 oraz z materiałem wspierającym o parametrach pojazdu.',
                'review_notes' => 'Publikacja w rollout-04 jako początek finalnego batcha domykającego kategorię znaków zakazu.',
            ],
            'B-16' => [
                'builder_index' => 16,
                'sort_order' => 350,
                'source_notes' => 'Wysoka wartość użytkowa przy obiektach z ograniczeniem wysokości i tunelach.',
                'editorial_notes' => 'Dobrze trzymać w jednej rewizji z B-15 i B-17, bo użytkownik często szuka całej rodziny ograniczeń gabarytowych.',
                'review_notes' => 'Publikowany razem z resztą klastru parametrów technicznych pojazdu.',
            ],
            'B-17' => [
                'builder_index' => 17,
                'sort_order' => 360,
                'source_notes' => 'Potrzebny do pełnego pokrycia znaków gabarytowych i pytań o długość zestawu.',
                'editorial_notes' => 'Łączyć z B-15 i B-18 oraz z przyszłymi materiałami o planowaniu trasy dla dłuższych pojazdów.',
                'review_notes' => 'Rollout-04: kompletność rodziny ograniczeń wymiarowych.',
            ],
            'B-18' => [
                'builder_index' => 18,
                'sort_order' => 370,
                'source_notes' => 'To ważny znak dla ruchu cięższego i dla odróżnienia masy całkowitej od nacisku osi.',
                'editorial_notes' => 'Mocno spiąć z B-19 oraz z materiałem porównawczym o parametrach technicznych pojazdu.',
                'review_notes' => 'Publikowany jako anchor page mini-klastra o masie i nacisku.',
            ],
            'B-19' => [
                'builder_index' => 19,
                'sort_order' => 380,
                'source_notes' => 'Wymaga precyzji, bo kierowcy i kursanci często mylą nacisk osi z masą całkowitą pojazdu.',
                'editorial_notes' => 'Przy kolejnych rewizjach pilnować aktualnego brzmienia znaku oraz różnicy wobec B-18.',
                'review_notes' => 'Rollout-04: jeden z ważniejszych znaków technicznych w całej kategorii zakazu.',
            ],
            'B-26' => [
                'builder_index' => 26,
                'sort_order' => 390,
                'source_notes' => 'Domyka logicznie klaster wyprzedzania i odpowiada na pytania kierowców ciężarówek.',
                'editorial_notes' => 'Spinać z B-25, B-27 i B-28 oraz z materiałem porównawczym o zakazach wyprzedzania.',
                'review_notes' => 'Publikacja w rollout-04 jako brakujące ogniwo rodziny znaków o wyprzedzaniu.',
            ],
            'B-28' => [
                'builder_index' => 28,
                'sort_order' => 400,
                'source_notes' => 'Potrzebny jako znak kończący i odpowiedź na pytania o odwołanie zakazu dla ciężarówek.',
                'editorial_notes' => 'Utrzymywać razem z B-26 w jednym passu rewizyjnym.',
                'review_notes' => 'Rollout-04: domknięcie mini-klastra wyprzedzania przez samochody ciężarowe.',
            ],
            'B-29' => [
                'builder_index' => 29,
                'sort_order' => 410,
                'source_notes' => 'Praktyczny znak miejski i lokalny; dobrze tłumaczy relację między bezpieczeństwem a zakazem używania klaksonu.',
                'editorial_notes' => 'Łączyć z B-30 i unikać zbyt skrótowego, tylko definicyjnego opisu.',
                'review_notes' => 'Publikowany jako część małego klastra o sygnałach dźwiękowych.',
            ],
            'B-30' => [
                'builder_index' => 30,
                'sort_order' => 420,
                'source_notes' => 'Potrzebny do pełnej odpowiedzi o początku i końcu zakazu używania sygnałów dźwiękowych.',
                'editorial_notes' => 'Spinać z B-29 i zachować prosty, praktyczny ton treści.',
                'review_notes' => 'Rollout-04: znak kończący dla klastra sygnałów dźwiękowych.',
            ],
            'B-31' => [
                'builder_index' => 31,
                'sort_order' => 430,
                'source_notes' => 'Wysoka wartość egzaminacyjna i praktyczna przy zwężeniach oraz relacji do ruchu z przeciwka.',
                'editorial_notes' => 'Później rozważyć mocniejsze powiązanie z treściami o pierwszeństwie na zwężeniach.',
                'review_notes' => 'Publikowany w rollout-04 jako ważny znak organizacji ruchu jednokierunkowego i zwężeń.',
            ],
            'B-32' => [
                'builder_index' => 32,
                'sort_order' => 440,
                'source_notes' => 'Niszowy, ale potrzebny dla kompletności i wiarygodności pełnego katalogu znaków zakazu.',
                'editorial_notes' => 'Trzymać odpowiedź prostą i jasno osadzoną w realiach kontroli granicznej lub celnej.',
                'review_notes' => 'Rollout-04: publikacja dla pełnego pokrycia oficjalnego katalogu.',
            ],
            'B-32a' => [
                'builder_index' => 321,
                'sort_order' => 441,
                'source_notes' => 'Publikowany jako brakujący wariant rodziny znaków zatrzymania związanych z kontrolą i odprawą.',
                'editorial_notes' => 'Trzymać blisko B-32 i B-32e, bo użytkownik zwykle szuka całej rodziny podobnych znaków.',
                'review_notes' => 'Rollout-04: domknięcie luki po wariancie kontroli granicznej.',
            ],
            'B-32b' => [
                'builder_index' => 322,
                'sort_order' => 442,
                'source_notes' => 'Rzadki, ale ważny znak dla kompletności katalogu i dla logiki znaków zatrzymania przy awarii zabezpieczeń.',
                'editorial_notes' => 'Pilnować praktycznego tonu i nie rozmywać znaczenia znaku poza miejscem awarii zabezpieczenia.',
                'review_notes' => 'Rollout-04: dodany jako brakujący wariant B-32b.',
            ],
            'B-32c' => [
                'builder_index' => 323,
                'sort_order' => 443,
                'source_notes' => 'Wzmacnia kompletność rodziny znaków awaryjnego zatrzymania i odpowiada na niszowe, ale konkretne pytania o sygnalizację.',
                'editorial_notes' => 'Dobrze zachować prostą odpowiedź o obowiązku zatrzymania i manualnej ocenie sytuacji.',
                'review_notes' => 'Rollout-04: brakująca publikacja wariantu B-32c.',
            ],
            'B-32d' => [
                'builder_index' => 324,
                'sort_order' => 444,
                'source_notes' => 'Nietypowy, ale bardzo czytelny znak specjalistyczny związany z organizacją wjazdu na prom.',
                'editorial_notes' => 'Nie przeciążać teorii; najważniejsze jest zatrzymanie i oczekiwanie na sygnał obsługi.',
                'review_notes' => 'Rollout-04: dopełnienie wariantów B-32 o wjazd na prom.',
            ],
            'B-32e' => [
                'builder_index' => 325,
                'sort_order' => 445,
                'source_notes' => 'Potrzebny do pełnego pokrycia znaków zatrzymania związanych z kontrolą na drodze.',
                'editorial_notes' => 'Trzymać spójnie z B-32 i B-32a oraz mocno akcentować obowiązek zatrzymania.',
                'review_notes' => 'Rollout-04: publikacja brakującego wariantu kontroli drogowej.',
            ],
            'B-39' => [
                'builder_index' => 39,
                'sort_order' => 450,
                'source_notes' => 'Wartościowy znak dla klastru parkingowego i pytań o zasady postoju w całej strefie.',
                'editorial_notes' => 'Spinać z B-40 oraz z istniejącym klastrem B-35/B-36/B-37/B-38.',
                'review_notes' => 'Publikowany jako anchor page strefy ograniczonego postoju.',
            ],
            'B-40' => [
                'builder_index' => 40,
                'sort_order' => 460,
                'source_notes' => 'Potrzebny, żeby domknąć logikę strefy ograniczonego postoju i odpowiedzieć na pytania o jej koniec.',
                'editorial_notes' => 'Powiązać z B-39 i z materiałem porównawczym o strefie ograniczonego postoju.',
                'review_notes' => 'Rollout-04 kończy rodzinę stref postoju i finalizuje publikację całej kategorii znaków zakazu.',
            ],
        ];
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function rolloutTwoQueryMapEntries(array $signs, TrafficSignCategory $prohibitionCategory): array
    {
        return [
            [
                'primary_query' => 'b-21 zakaz skręcania w lewo',
                'mapped_title' => 'B-21 Zakaz skręcania w lewo',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-21-zakaz-skrecania-w-lewo',
                'traffic_sign_id' => $signs['b-21-zakaz-skrecania-w-lewo']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Pilnować różnicy między zakazem skrętu w lewo i zakazem zawracania oraz relacji do strzałek na pasie i sygnalizacji.',
                'correction_notes' => 'Weryfikować przy rozbudowie klastru porównawczego B-21 vs B-23 i przy zmianach w materiałach wspierających o manewrach.',
                'competitor_notes' => 'Mocny query użytkowy, na którym przewagę robi czytelne odróżnienie manewrów, a nie sama definicja znaku.',
                'first_mover_note' => null,
                'watch_reason' => 'Wysoka intencja egzaminacyjna i praktyczna na skrzyżowaniach.',
                'notes' => 'Opublikowany w pierwszym dużym batchu rollout-02 jako anchor page klastru zakazów manewru.',
            ],
            [
                'primary_query' => 'b-22 zakaz skręcania w prawo',
                'mapped_title' => 'B-22 Zakaz skręcania w prawo',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-22-zakaz-skrecania-w-prawo',
                'traffic_sign_id' => $signs['b-22-zakaz-skrecania-w-prawo']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Tłumaczyć zakaz w kontekście pasa ruchu, sygnalizacji i geometrii skrzyżowania.',
                'correction_notes' => 'Przeglądać przy kolejnych materiałach o znakach kierunkowych i skrętach warunkowych.',
                'competitor_notes' => 'Dobre query do budowania kompletności pary B-21/B-22 i do czytelnych FAQ manewrowych.',
                'first_mover_note' => null,
                'watch_reason' => 'Publikacja ma domknąć podstawową parę kierunkową bez luk w kategorii.',
                'notes' => 'Rollout-02: podstawowa strona znaku zakazu skrętu w prawo.',
            ],
            [
                'primary_query' => 'b-23 zakaz zawracania',
                'mapped_title' => 'B-23 Zakaz zawracania',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-23-zakaz-zawracania',
                'traffic_sign_id' => $signs['b-23-zakaz-zawracania']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Wyraźnie rozróżnić zawracanie i skręt w lewo oraz pokazać, gdzie kierowcy mylą te manewry.',
                'correction_notes' => 'Aktualizować razem z materiałem wspierającym B-21 vs B-23, żeby oba URL-e nie rozjechały się merytorycznie.',
                'competitor_notes' => 'Jedno z ważniejszych pytań egzaminacyjnych, na którym warto wygrać prostotą i precyzją odpowiedzi.',
                'first_mover_note' => 'Dobre query do przejęcia przez mocne porównanie zamiast dwóch odseparowanych suchych definicji.',
                'watch_reason' => 'Wysoki potencjał egzaminacyjny i naturalne linkowanie do B-21 oraz B-24.',
                'notes' => 'Opublikowany jako rdzeń mini-klastra zakazów manewru.',
            ],
            [
                'primary_query' => 'b-24 koniec zakazu zawracania',
                'mapped_title' => 'B-24 Koniec zakazu zawracania',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-24-koniec-zakazu-zawracania',
                'traffic_sign_id' => $signs['b-24-koniec-zakazu-zawracania']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Pokazać, że znak odwołuje zakaz, ale nie daje automatycznej zgody na manewr w dowolnym miejscu.',
                'correction_notes' => 'Pilnować spójności z B-23 oraz z materiałami o znakach odwołujących ograniczenia.',
                'competitor_notes' => 'Znak kończący zwykle bywa pomijany, więc dobra treść może szybko zbudować przewagę kompletności.',
                'first_mover_note' => null,
                'watch_reason' => 'Potrzebny do zachowania pełnego, logicznego klastru B-23/B-24.',
                'notes' => 'Opublikowany razem z B-23, aby zamknąć parę zakaz / koniec zakazu.',
            ],
            [
                'primary_query' => 'b-25 zakaz wyprzedzania',
                'mapped_title' => 'B-25 Zakaz wyprzedzania',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-25-zakaz-wyprzedzania',
                'traffic_sign_id' => $signs['b-25-zakaz-wyprzedzania']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Oprzeć treść o praktykę bezpiecznego wyprzedzania, a nie tylko o sam przepis.',
                'correction_notes' => 'Warto wracać do rewizji razem z B-27 i ewentualnym materiałem o relacji zakazu do linii ciągłej.',
                'competitor_notes' => 'Anchor query dla bezpieczeństwa ruchu; przewagą ma być praktyczny kontekst manewru.',
                'first_mover_note' => null,
                'watch_reason' => 'Core page klastra wyprzedzania.',
                'notes' => 'Opublikowany w rollout-02 jako jedna z najmocniejszych stron znaku zakazu.',
            ],
            [
                'primary_query' => 'b-27 koniec zakazu wyprzedzania',
                'mapped_title' => 'B-27 Koniec zakazu wyprzedzania',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-27-koniec-zakazu-wyprzedzania',
                'traffic_sign_id' => $signs['b-27-koniec-zakazu-wyprzedzania']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Podkreślić, że koniec zakazu nie uchyla innych ograniczeń, w tym linii ciągłej i warunków bezpieczeństwa.',
                'correction_notes' => 'Aktualizować w tandemie z B-25 i ewentualnym materiałem wspierającym o końcu zakazu.',
                'competitor_notes' => 'Dobre uzupełnienie klastra, bo konkurencja często pomija znaki odwołujące zakaz wyprzedzania.',
                'first_mover_note' => null,
                'watch_reason' => 'Domyka logicznie klaster wyprzedzania.',
                'notes' => 'Publikacja uzupełniająca B-25 w rollout-02.',
            ],
            [
                'primary_query' => 'b-33 ograniczenie prędkości',
                'mapped_title' => 'B-33 Ograniczenie prędkości',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-33-ograniczenie-predkosci',
                'traffic_sign_id' => $signs['b-33-ograniczenie-predkosci']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Skupić się na praktyce wejścia w ograniczenie i na odwoływaniu limitu, nie tylko na przepisowym opisie.',
                'correction_notes' => 'Rewidować wraz z B-34, B-43 i B-44 oraz po zmianach taryfikatora lub polityki prędkości.',
                'competitor_notes' => 'Jedna z najmocniejszych stron pod ruch z wyszukiwarki; wymaga bardzo czytelnej odpowiedzi operacyjnej.',
                'first_mover_note' => 'Szybkie domknięcie całego mini-klastra prędkości może dać przewagę nad konkurencją publikującą pojedyncze, rozproszone odpowiedzi.',
                'watch_reason' => 'Najważniejszy anchor page rollout-02 po stronie zapytań o prędkość.',
                'notes' => 'Opublikowany razem z B-34, B-43 i B-44 jako klaster ograniczeń prędkości.',
            ],
            [
                'primary_query' => 'b-34 koniec ograniczenia prędkości',
                'mapped_title' => 'B-34 Koniec ograniczenia prędkości',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-34-koniec-ograniczenia-predkosci',
                'traffic_sign_id' => $signs['b-34-koniec-ograniczenia-predkosci']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Wyjaśnić precyzyjnie, co kończy znak B-34 i czym różni się od końca strefy ograniczonej prędkości.',
                'correction_notes' => 'Spinać z B-33 i B-44, bo tu łatwo o nieścisłości przy porównaniach.',
                'competitor_notes' => 'Uzupełniający, ale ważny URL dla pełnego klastru prędkości.',
                'first_mover_note' => null,
                'watch_reason' => 'Potrzebny dla kompletności odpowiedzi wokół ograniczeń prędkości.',
                'notes' => 'Opublikowany równolegle do B-33 jako znak kończący ograniczenie.',
            ],
            [
                'primary_query' => 'b-37 zakaz postoju w dni nieparzyste',
                'mapped_title' => 'B-37 Zakaz postoju w dni nieparzyste',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-37-zakaz-postoju-w-dni-nieparzyste',
                'traffic_sign_id' => $signs['b-37-zakaz-postoju-w-dni-nieparzyste']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Oprzeć tekst o praktykę zmian strony postoju i czytania znaku w kalendarzu kierowcy, nie tylko o słownikową definicję.',
                'correction_notes' => 'Warto odświeżać razem z B-38 i B-35/B-36, żeby zachować spójność parkingowego mini-klastra.',
                'competitor_notes' => 'Mocny query miejski, zwykle obsługiwany zbyt powierzchownie; przewagą ma być praktyczne FAQ.',
                'first_mover_note' => null,
                'watch_reason' => 'Wysoka wartość użytkowa i naturalne powiązanie z już opublikowanym klastrem parkingowym.',
                'notes' => 'Opublikowany w rollout-02 jako rozwinięcie kategorii postoju i zatrzymywania.',
            ],
            [
                'primary_query' => 'b-38 zakaz postoju w dni parzyste',
                'mapped_title' => 'B-38 Zakaz postoju w dni parzyste',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-38-zakaz-postoju-w-dni-parzyste',
                'traffic_sign_id' => $signs['b-38-zakaz-postoju-w-dni-parzyste']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Tłumaczyć znak równolegle z B-37 i jasno pokazywać logikę parzystych / nieparzystych dni.',
                'correction_notes' => 'Trzymać w jednej rewizji z B-37 oraz z materiałem wspierającym o znakach postoju zależnych od dnia.',
                'competitor_notes' => 'Naturalna strona parowana z B-37; dobrze odpowiada na zamiar użytkownika porównującego oba znaki.',
                'first_mover_note' => null,
                'watch_reason' => 'Publikacja ma zamknąć parkingowy mini-klaster bez asymetrii znaków parzyste / nieparzyste.',
                'notes' => 'Opublikowany razem z B-37.',
            ],
            [
                'primary_query' => 'b-43 strefa ograniczonej prędkości',
                'mapped_title' => 'B-43 Strefa ograniczonej prędkości',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-43-strefa-ograniczonej-predkosci',
                'traffic_sign_id' => $signs['b-43-strefa-ograniczonej-predkosci']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Mocno rozróżnić strefę od zwykłego B-33 i pokazać konsekwencje w całym obszarze, nie tylko w jednym punkcie drogi.',
                'correction_notes' => 'Aktualizować razem z B-44 oraz ze stroną porównawczą B-33 vs B-43.',
                'competitor_notes' => 'Dobre query do budowania przewagi na różnicy między ograniczeniem punktowym i strefowym.',
                'first_mover_note' => 'Silne porównanie z B-33 może szybko stać się wyróżnikiem klastra prędkości.',
                'watch_reason' => 'Wysoka wartość użytkowa i naturalna potrzeba porównania z B-33.',
                'notes' => 'Opublikowany jako drugi anchor page klastra prędkości w rollout-02.',
            ],
            [
                'primary_query' => 'b-44 koniec strefy ograniczonej prędkości',
                'mapped_title' => 'B-44 Koniec strefy ograniczonej prędkości',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/b-44-koniec-strefy-ograniczonej-predkosci',
                'traffic_sign_id' => $signs['b-44-koniec-strefy-ograniczonej-predkosci']->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => 'Wyjaśnić, co kończy B-44 i czym różni się od B-34 w praktyce kierowcy.',
                'correction_notes' => 'Pilnować spójności z B-43 oraz ze stroną porównawczą o prędkości punktowej i strefowej.',
                'competitor_notes' => 'Uzupełniający URL, który wzmacnia kompletność klastra prędkości i ogranicza luki merytoryczne.',
                'first_mover_note' => null,
                'watch_reason' => 'Potrzebny dla pełnej odpowiedzi o początku i końcu strefy ograniczonej prędkości.',
                'notes' => 'Publikowany razem z B-43 jako znak kończący strefę.',
            ],
            [
                'primary_query' => 'b-21 a b-23 różnice',
                'mapped_title' => 'Porównanie B-21 i B-23',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/porownania/b-21-vs-b-23',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Materiał porównawczy ma zamknąć najczęstszą pomyłkę między zakazem skrętu w lewo i zakazem zawracania.',
                'correction_notes' => 'Aktualizować przy każdej większej zmianie treści B-21 lub B-23.',
                'competitor_notes' => 'Porównanie może przejąć intencję użytkownika, który po samej definicji nadal nie jest pewny różnicy.',
                'first_mover_note' => 'To szybka okazja na wyróżnienie, bo konkurencja często nie robi osobnego materiału porównawczego dla tych dwóch znaków.',
                'watch_reason' => null,
                'notes' => 'Supporting page domykający mini-klaster manewrowy rollout-02.',
            ],
            [
                'primary_query' => 'b-33 a b-43 różnice',
                'mapped_title' => 'Porównanie B-33 i B-43',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/porownania/b-33-vs-b-43',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Wyjaśnić różnicę między ograniczeniem punktowym i strefą ograniczonej prędkości na przykładach zachowania kierowcy.',
                'correction_notes' => 'Trzymać spójność z B-33, B-43 i B-44.',
                'competitor_notes' => 'Mocny materiał pomocniczy dla klastra prędkości, domykający typową intencję „jaka jest różnica”.',
                'first_mover_note' => 'Porównawcza strona może szybko zbudować przewagę nad rozproszonymi odpowiedziami konkurencji.',
                'watch_reason' => null,
                'notes' => 'Supporting page dla klastru prędkości w rollout-02.',
            ],
            [
                'primary_query' => 'b-35 a b-36 różnice',
                'mapped_title' => 'Porównanie B-35 i B-36',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-02',
                'target_path' => '/znaki-drogowe/porownania/b-35-vs-b-36',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Porównanie powinno praktycznie rozdzielać postój i zatrzymanie oraz wskazać relację do znaków B-37 i B-38.',
                'correction_notes' => 'Aktualizować przy każdej rozbudowie klastra parkingowego.',
                'competitor_notes' => 'Silny materiał o wysokiej intencji porównawczej i codziennej użyteczności dla kierowcy.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page rozszerzający istniejący mini-klaster parkingowy.',
            ],
        ];
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function rolloutThreeQueryMapEntries(array $signs, TrafficSignCategory $prohibitionCategory): array
    {
        $signEntries = [
            [
                'primary_query' => 'b-3 zakaz wjazdu pojazdów silnikowych',
                'sign_slug' => 'b-3-zakaz-wjazdu-pojazdow-silnikowych',
                'mapped_title' => 'B-3 Zakaz wjazdu pojazdów silnikowych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyjaśniać zakres pojęcia pojazdów silnikowych i relację do bardziej szczegółowych zakazów grupowych.',
                'correction_notes' => 'Spinać z B-5 i B-7 przy każdej większej rewizji klastra zakazów wjazdu.',
                'competitor_notes' => 'Query dobrze znosi porównanie z bardziej szczegółowymi zakazami, a nie tylko słownikową definicję.',
                'watch_reason' => 'Anchor page dla rodzinnego klastra zakazów wjazdu grupowego.',
                'notes' => 'Rollout-03: podstawowa strona znaku B-3.',
            ],
            [
                'primary_query' => 'b-3a zakaz wjazdu autobusów',
                'sign_slug' => 'b-3a-zakaz-wjazdu-autobusow',
                'mapped_title' => 'B-3a Zakaz wjazdu autobusów',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Pokazać zakres zakazu dla autobusów i czytanie tabliczek z wyjątkami.',
                'correction_notes' => 'Weryfikować razem z resztą klastru zakazów grupowego wjazdu.',
                'competitor_notes' => 'Niszowy, ale istotny dla kompletności i długiego ogona kategorii.',
                'watch_reason' => 'Element pełnego pokrycia kategorii zakazu wjazdu dla grup pojazdów.',
                'notes' => 'Rollout-03: strona znaku B-3a.',
            ],
            [
                'primary_query' => 'b-4 zakaz wjazdu motocykli',
                'sign_slug' => 'b-4-zakaz-wjazdu-motocykli',
                'mapped_title' => 'B-4 Zakaz wjazdu motocykli',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić prosty zakres zakazu i różnicę względem bardziej ogólnego B-3.',
                'correction_notes' => 'Można później zderzyć z pytaniami o motorowery i rowery przy analizie GSC.',
                'competitor_notes' => 'Przewaga wynika z jasnego wyjaśnienia kogo zakaz obejmuje, bez przeładowania treści.',
                'watch_reason' => null,
                'notes' => 'Rollout-03: strona znaku B-4.',
            ],
            [
                'primary_query' => 'b-5 zakaz wjazdu samochodów ciężarowych',
                'sign_slug' => 'b-5-zakaz-wjazdu-samochodow-ciezarowych',
                'mapped_title' => 'B-5 Zakaz wjazdu samochodów ciężarowych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pisać z myślą o praktyce kierowcy ciężarówki i o odróżnieniu znaku od B-3 oraz B-7.',
                'correction_notes' => 'Pilnować spójności z materiałem porównawczym B-3/B-5/B-7.',
                'competitor_notes' => 'Jedno z ważniejszych zapytań logistycznych w kategorii zakazów wjazdu.',
                'watch_reason' => 'Silny kandydat użytkowy w klastrze dla pojazdów ciężkich.',
                'notes' => 'Rollout-03: anchor page dla zakazu wjazdu ciężarówek.',
            ],
            [
                'primary_query' => 'b-6 zakaz wjazdu ciągników rolniczych',
                'sign_slug' => 'b-6-zakaz-wjazdu-ciagnikow-rolniczych',
                'mapped_title' => 'B-6 Zakaz wjazdu ciągników rolniczych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśniać znak w kontekście ruchu rolniczego i lokalnej organizacji ruchu.',
                'correction_notes' => 'Zostawić pod obserwacją realnego popytu po wdrożeniu.',
                'competitor_notes' => 'Long tail, ale potrzebny dla kompletności kategorii i wiarygodności corpus.',
                'watch_reason' => null,
                'notes' => 'Rollout-03: strona znaku B-6.',
            ],
            [
                'primary_query' => 'b-7 zakaz wjazdu pojazdów silnikowych z przyczepą',
                'sign_slug' => 'b-7-zakaz-wjazdu-pojazdow-silnikowych-z-przyczepa',
                'mapped_title' => 'B-7 Zakaz wjazdu pojazdów silnikowych z przyczepą',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Tłumaczyć praktycznie, kiedy zakaz dotyczy zestawu z przyczepą i jak odróżniać go od innych zakazów dla pojazdów ciężkich.',
                'correction_notes' => 'Rewidować wraz z B-3 i B-5 oraz z porównaniem dla zakazów grupowych.',
                'competitor_notes' => 'Dobre query specjalistyczne, które zyskuje na praktycznym rozróżnieniu rodzajów zestawów.',
                'watch_reason' => 'Mocny element klastru pojazdów cięższych i zestawów.',
                'notes' => 'Rollout-03: strona znaku B-7.',
            ],
            [
                'primary_query' => 'b-8 zakaz wjazdu pojazdów zaprzęgowych',
                'sign_slug' => 'b-8-zakaz-wjazdu-pojazdow-zaprzegowych',
                'mapped_title' => 'B-8 Zakaz wjazdu pojazdów zaprzęgowych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Krótko i precyzyjnie opisać kogo dotyczy zakaz i jak czytać wyjątki.',
                'correction_notes' => 'Obserwować pod długi ogon i kompletność, bez sztucznego rozbudowywania.',
                'competitor_notes' => 'Treść ma wygrać prostotą i kompletnością katalogu.',
                'watch_reason' => null,
                'notes' => 'Rollout-03: strona znaku B-8.',
            ],
            [
                'primary_query' => 'b-9 zakaz wjazdu rowerów',
                'sign_slug' => 'b-9-zakaz-wjazdu-rowerow',
                'mapped_title' => 'B-9 Zakaz wjazdu rowerów',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Utrzymać praktyczny ton: kogo obejmuje zakaz i jak rowerzysta powinien czytać alternatywne ciągi ruchu.',
                'correction_notes' => 'Później rozważyć supporting content, jeśli pojawi się więcej treści rowerowych.',
                'competitor_notes' => 'Mocny potencjał codzienny, szczególnie przy krótkich i konkretnych odpowiedziach użytkowych.',
                'watch_reason' => 'Znacząca użyteczność dla ruchu rowerowego.',
                'notes' => 'Rollout-03: strona znaku B-9.',
            ],
            [
                'primary_query' => 'b-10 zakaz wjazdu motorowerów',
                'sign_slug' => 'b-10-zakaz-wjazdu-motorowerow',
                'mapped_title' => 'B-10 Zakaz wjazdu motorowerów',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić zakres zakazu i odróżnienie motoroweru od innych lekkich pojazdów.',
                'correction_notes' => 'Warto obserwować, czy potrzebne będą dodatkowe FAQ doprecyzowujące grupę pojazdów.',
                'competitor_notes' => 'Długi ogon, ale dobry dla kompletności i zaufania do całego katalogu.',
                'watch_reason' => null,
                'notes' => 'Rollout-03: strona znaku B-10.',
            ],
            [
                'primary_query' => 'b-11 zakaz wjazdu wozów ręcznych',
                'sign_slug' => 'b-11-zakaz-wjazdu-wozow-recznych',
                'mapped_title' => 'B-11 Zakaz wjazdu wozów ręcznych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Trzymać odpowiedź prostą i definicyjną, ale z jednym praktycznym doprecyzowaniem zakresu zakazu.',
                'correction_notes' => 'Później obserwować, czy potrzebne jest mocniejsze objaśnienie samej definicji wozu ręcznego.',
                'competitor_notes' => 'Niszowy wpis wzmacniający pełne pokrycie kategorii.',
                'watch_reason' => null,
                'notes' => 'Rollout-03: strona znaku B-11.',
            ],
            [
                'primary_query' => 'b-12 zakaz wjazdu wozów ręcznych z towarem',
                'sign_slug' => 'b-12-zakaz-wjazdu-wozow-recznych-z-towarem',
                'mapped_title' => 'B-12 Zakaz wjazdu wozów ręcznych z towarem',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Utrzymać prostą strukturę treści z naciskiem na zakres zakazu i organizację ruchu pieszo-towarowego.',
                'correction_notes' => 'Sprawdzać realny popyt po wdrożeniu.',
                'competitor_notes' => 'Long tail ważny dla kompletności, ale bez sztucznego pompowania treści.',
                'watch_reason' => null,
                'notes' => 'Rollout-03: strona znaku B-12.',
            ],
            [
                'primary_query' => 'b-13 zakaz wjazdu materiałów wybuchowych',
                'sign_slug' => 'b-13-zakaz-wjazdu-pojazdow-z-materialami-wybuchowymi',
                'mapped_title' => 'B-13 Zakaz wjazdu pojazdów z materiałami wybuchowymi lub łatwo zapalnymi',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pisać na tle całej rodziny znaków dla przewozów niebezpiecznych i mocno rozróżniać zakres B-13, B-13a i B-14.',
                'correction_notes' => 'Pilnować spójności z materiałem porównawczym B-13/B-13a/B-14.',
                'competitor_notes' => 'Wysoki potencjał jakościowy w niszy, bo konkurencja często myli lub skleja te trzy znaki.',
                'watch_reason' => 'Anchor page klastra dla materiałów niebezpiecznych.',
                'notes' => 'Rollout-03: główna strona znaku B-13.',
            ],
            [
                'primary_query' => 'b-13a zakaz wjazdu materiałów niebezpiecznych',
                'sign_slug' => 'b-13a-zakaz-wjazdu-pojazdow-z-materialami-niebezpiecznymi',
                'mapped_title' => 'B-13a Zakaz wjazdu pojazdów z materiałami niebezpiecznymi',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyraźnie rozdzielać ten znak od B-13 i B-14 oraz pokazać, jak czytać go w praktyce przewozu ADR.',
                'correction_notes' => 'Utrzymywać w jednej rewizji z B-13/B-14 i stroną porównawczą.',
                'competitor_notes' => 'Dobre query specjalistyczne, gdzie różnicowanie znaków daje realną przewagę jakościową.',
                'watch_reason' => 'Kluczowy element mini-klastra o materiałach niebezpiecznych.',
                'notes' => 'Rollout-03: strona znaku B-13a.',
            ],
            [
                'primary_query' => 'b-14 zakaz wjazdu pojazdów mogących skazić wodę',
                'sign_slug' => 'b-14-zakaz-wjazdu-pojazdow-z-materialami-mogacymi-skazic-wode',
                'mapped_title' => 'B-14 Zakaz wjazdu pojazdów z materiałami, które mogą skazić wodę',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pokazać środowiskowy sens znaku i różnicę wobec szerszych zakazów przewozu materiałów niebezpiecznych.',
                'correction_notes' => 'Spinać z B-13 i B-13a oraz z materiałem porównawczym o zakazach dla ładunków niebezpiecznych.',
                'competitor_notes' => 'Dobry kandydat do jakościowej przewagi przez precyzję pojęć i kontekstu środowiskowego.',
                'watch_reason' => 'Domyka triadę znaków dla przewozów niebezpiecznych.',
                'notes' => 'Rollout-03: strona znaku B-14.',
            ],
            [
                'primary_query' => 'b-41 zakaz ruchu pieszych',
                'sign_slug' => 'b-41-zakaz-ruchu-pieszych',
                'mapped_title' => 'B-41 Zakaz ruchu pieszych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Tłumaczyć praktycznie, kiedy pieszy nie może wejść na dany odcinek i jak czytać organizację alternatywnego dojścia.',
                'correction_notes' => 'Przy dalszym rozwoju można połączyć z treściami o ruchu pieszym i strefach ograniczonego dostępu.',
                'competitor_notes' => 'Dobra strona użytkowa dla pytań o relację pieszego do oznakowania zakazu.',
                'watch_reason' => 'Wysoka użyteczność i ważna kompletność kategorii zakazów.',
                'notes' => 'Rollout-03: strona znaku B-41.',
            ],
            [
                'primary_query' => 'b-42 koniec zakazów',
                'sign_slug' => 'b-42-koniec-zakazow',
                'mapped_title' => 'B-42 Koniec zakazów',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyjaśniać, jakie zakazy kończy znak B-42 i dlaczego nie należy go czytać jak uniwersalnego znoszenia wszystkich możliwych ograniczeń.',
                'correction_notes' => 'W kolejnych iteracjach dobrze powiązać z materiałem o znakach kończących ograniczenia.',
                'competitor_notes' => 'Mocny kandydat do przewagi jakościowej, bo znak jest często tłumaczony zbyt ogólnie.',
                'watch_reason' => 'Kończy logicznie kategorię zakazów i ma wysoką wartość edukacyjną.',
                'notes' => 'Rollout-03: strona znaku B-42.',
            ],
        ];

        $entries = [];

        foreach ($signEntries as $entry) {
            $sign = $signs[$entry['sign_slug']];

            $entries[] = [
                'primary_query' => $entry['primary_query'],
                'mapped_title' => $entry['mapped_title'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => $entry['search_intent'],
                'priority' => $entry['priority'],
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-03',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => $entry['source_plan'],
                'correction_notes' => $entry['correction_notes'],
                'competitor_notes' => $entry['competitor_notes'],
                'first_mover_note' => null,
                'watch_reason' => $entry['watch_reason'],
                'notes' => $entry['notes'],
            ];
        }

        return array_merge($entries, [
            [
                'primary_query' => 'b-1 a b-2 różnice',
                'mapped_title' => 'Porównanie B-1 i B-2',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-03',
                'target_path' => '/znaki-drogowe/porownania/b-1-vs-b-2',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Porównanie ma szybko rozdzielić zakaz ruchu w obu kierunkach i zakaz wjazdu od jednej strony.',
                'correction_notes' => 'Trzymać spójność z B-1 i B-2 oraz z dalszymi treściami o ruchu jednokierunkowym.',
                'competitor_notes' => 'Bardzo naturalna intencja porównawcza, która dobrze domyka dwa już istniejące anchor pages.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page rozszerzający istniejący klaster podstawowych zakazów wjazdu.',
            ],
            [
                'primary_query' => 'b-3 b-5 b-7 różnice',
                'mapped_title' => 'Porównanie B-3, B-5 i B-7',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-03',
                'target_path' => '/znaki-drogowe/porownania/b-3-vs-b-5-vs-b-7',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Porównać zakres zakazu dla pojazdów silnikowych, ciężarowych i zestawów z przyczepą na praktycznych przykładach.',
                'correction_notes' => 'Utrzymywać w jednej rewizji z B-3, B-5 i B-7.',
                'competitor_notes' => 'Silny materiał wspierający dla kierowców, którzy gubią się w podobnych zakazach grupowych.',
                'first_mover_note' => 'Porównanie trzech zbliżonych znaków może stać się jednym z najmocniejszych materiałów klastra zakazów wjazdu.',
                'watch_reason' => null,
                'notes' => 'Supporting page domykający mini-klaster zakazów grupowego wjazdu.',
            ],
            [
                'primary_query' => 'b-13 b-13a b-14 różnice',
                'mapped_title' => 'Porównanie B-13, B-13a i B-14',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-03',
                'target_path' => '/znaki-drogowe/porownania/b-13-vs-b-13a-vs-b-14',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Wyjaśnić, jak rozdzielić trzy zakazy dotyczące materiałów niebezpiecznych i ochrony środowiska.',
                'correction_notes' => 'Każda zmiana na stronach B-13/B-13a/B-14 powinna pociągać za sobą rewizję tego porównania.',
                'competitor_notes' => 'To porównanie ma duży potencjał jakościowy, bo konkurencja często myli zakres tych znaków albo je skleja.',
                'first_mover_note' => 'Szybkie, precyzyjne porównanie może zbudować wyraźną przewagę ekspercką w niszy.',
                'watch_reason' => null,
                'notes' => 'Supporting page dla klastra materiałów niebezpiecznych i zakazów środowiskowych.',
            ],
        ]);
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function rolloutFourQueryMapEntries(array $signs, TrafficSignCategory $prohibitionCategory): array
    {
        $signEntries = [
            [
                'primary_query' => 'b-15 zakaz szerokości',
                'sign_slug' => 'b-15-zakaz-wjazdu-pojazdow-o-szerokosci-ponad',
                'mapped_title' => 'B-15 Zakaz wjazdu pojazdów o szerokości ponad ... m',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pokazać praktykę porównywania rzeczywistej szerokości zestawu z wartością na znaku.',
                'correction_notes' => 'Trzymać w jednym passu z B-16, B-17, B-18 i B-19.',
                'competitor_notes' => 'Mocny query techniczny, gdzie przewagą jest konkret i brak skrótów myślowych.',
                'watch_reason' => 'Element klastru ograniczeń gabarytowych.',
                'notes' => 'Rollout-04: strona znaku B-15.',
            ],
            [
                'primary_query' => 'b-16 zakaz wysokości',
                'sign_slug' => 'b-16-zakaz-wjazdu-pojazdow-o-wysokosci-ponad',
                'mapped_title' => 'B-16 Zakaz wjazdu pojazdów o wysokości ponad ... m',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyjaśniać znak na tle tuneli, wiaduktów i innych obiektów z ograniczeniem wysokości.',
                'correction_notes' => 'Utrzymywać spójność z resztą klastru parametrów technicznych.',
                'competitor_notes' => 'Bardzo praktyczny znak, gdzie użytkownik oczekuje szybkiej odpowiedzi bez dygresji.',
                'watch_reason' => 'Wysoka użyteczność dla przewozów i planowania trasy.',
                'notes' => 'Rollout-04: strona znaku B-16.',
            ],
            [
                'primary_query' => 'b-17 zakaz długości',
                'sign_slug' => 'b-17-zakaz-wjazdu-pojazdow-o-dlugosci-ponad',
                'mapped_title' => 'B-17 Zakaz wjazdu pojazdów o długości ponad ... m',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Tłumaczyć znaczenie długości zestawu i wpływ na manewrowanie na wąskich odcinkach.',
                'correction_notes' => 'Pilnować spójności z B-15 i B-18.',
                'competitor_notes' => 'Long tail techniczny, ale ważny dla kompletności katalogu.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-17.',
            ],
            [
                'primary_query' => 'b-18 zakaz masy całkowitej',
                'sign_slug' => 'b-18-zakaz-wjazdu-pojazdow-o-rzeczywistej-masie-calkowitej-ponad',
                'mapped_title' => 'B-18 Zakaz wjazdu pojazdów o rzeczywistej masie całkowitej ponad ... t',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Jasno odróżniać rzeczywistą masę całkowitą od DMC i od nacisku osi.',
                'correction_notes' => 'Spinać z B-19 oraz z materiałem wspierającym o parametrach pojazdu.',
                'competitor_notes' => 'To query lubi się rozmywać, więc precyzja i przykład praktyczny są tu najważniejsze.',
                'watch_reason' => 'Anchor page części masowo-gabarytowej.',
                'notes' => 'Rollout-04: strona znaku B-18.',
            ],
            [
                'primary_query' => 'b-19 zakaz nacisku pojedynczej osi napędowej',
                'sign_slug' => 'b-19-zakaz-wjazdu-pojazdow-o-nacisku-pojedynczej-osi-napedowej-powyzej',
                'mapped_title' => 'B-19 Zakaz wjazdu pojazdów o nacisku pojedynczej osi napędowej powyżej ... t',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_LEGAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Bardzo wyraźnie rozdzielić nacisk osi od masy całkowitej i od innych parametrów technicznych.',
                'correction_notes' => 'Przy każdej rewizji zachować odniesienie do aktualnego brzmienia znaku.',
                'competitor_notes' => 'Precyzyjne wyjaśnienie tego znaku buduje eksperckość całego klastra technicznego.',
                'watch_reason' => 'Jeden z ważniejszych technicznych znaków zakazu wjazdu.',
                'notes' => 'Rollout-04: strona znaku B-19.',
            ],
            [
                'primary_query' => 'b-26 zakaz wyprzedzania przez samochody ciężarowe',
                'sign_slug' => 'b-26-zakaz-wyprzedzania-przez-samochody-ciezarowe',
                'mapped_title' => 'B-26 Zakaz wyprzedzania przez samochody ciężarowe',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pokazać, kogo dokładnie dotyczy zakaz i jak czytać jego relację do ogólnego zakazu wyprzedzania.',
                'correction_notes' => 'Pilnować spójności z B-25, B-27 i B-28.',
                'competitor_notes' => 'Wysoka wartość praktyczna dla kierowców zawodowych i kursantów.',
                'watch_reason' => 'Brakujące ogniwo klastra wyprzedzania.',
                'notes' => 'Rollout-04: strona znaku B-26.',
            ],
            [
                'primary_query' => 'b-28 koniec zakazu wyprzedzania przez samochody ciężarowe',
                'sign_slug' => 'b-28-koniec-zakazu-wyprzedzania-przez-samochody-ciezarowe',
                'mapped_title' => 'B-28 Koniec zakazu wyprzedzania przez samochody ciężarowe',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić moment odwołania zakazu dla ciężarówek i różnicę względem B-27.',
                'correction_notes' => 'Aktualizować razem z B-26.',
                'competitor_notes' => 'Uzupełniający, ale ważny URL dla kompletności klastra o wyprzedzaniu.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-28.',
            ],
            [
                'primary_query' => 'b-29 zakaz używania sygnałów dźwiękowych',
                'sign_slug' => 'b-29-zakaz-uzywania-sygnalow-dzwiekowych',
                'mapped_title' => 'B-29 Zakaz używania sygnałów dźwiękowych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Osadzić znak w praktyce terenów zabudowanych i miejsc wymagających ograniczenia hałasu.',
                'correction_notes' => 'Spinać z B-30 i zachować czytelne wyjątki związane z bezpieczeństwem.',
                'competitor_notes' => 'Praktyczny query, gdzie użytkownik oczekuje krótkiej i jednoznacznej odpowiedzi.',
                'watch_reason' => 'Anchor page dla małego klastra sygnałów dźwiękowych.',
                'notes' => 'Rollout-04: strona znaku B-29.',
            ],
            [
                'primary_query' => 'b-30 koniec zakazu używania sygnałów dźwiękowych',
                'sign_slug' => 'b-30-koniec-zakazu-uzywania-sygnalow-dzwiekowych',
                'mapped_title' => 'B-30 Koniec zakazu używania sygnałów dźwiękowych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić moment zakończenia zakazu i zachować prostą, praktyczną odpowiedź.',
                'correction_notes' => 'Trzymać w jednej rewizji z B-29.',
                'competitor_notes' => 'Dobry URL uzupełniający dla pełnej odpowiedzi o zakazie klaksonu.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-30.',
            ],
            [
                'primary_query' => 'b-31 pierwszeństwo dla nadjeżdżających z przeciwka',
                'sign_slug' => 'b-31-pierwszenstwo-dla-nadjezdzajacych-z-przeciwka',
                'mapped_title' => 'B-31 Pierwszeństwo dla nadjeżdżających z przeciwka',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Podkreślać zachowanie kierowcy przy zwężeniach i relację do ruchu z przeciwka.',
                'correction_notes' => 'W przyszłości można połączyć z materiałem o pierwszeństwie na zwężeniach.',
                'competitor_notes' => 'Mocna strona edukacyjna, bo znak jest często pytany egzaminacyjnie.',
                'watch_reason' => 'Wysoka wartość egzaminacyjna i praktyczna.',
                'notes' => 'Rollout-04: strona znaku B-31.',
            ],
            [
                'primary_query' => 'b-32 zatrzymanie i odprawa celna',
                'sign_slug' => 'b-32-zatrzymanie-i-odprawa-celna',
                'mapped_title' => 'B-32 Zatrzymanie i odprawa celna',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić, że to znak obowiązkowego zatrzymania związany z odprawą celną i kontrolą graniczną lub celną w punkcie przejazdu.',
                'correction_notes' => 'Trzymać treść zwięzłą i prostą, bez sztucznego rozbudowywania.',
                'competitor_notes' => 'Niszowy wpis wzmacniający kompletność pełnego katalogu.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-32.',
            ],
            [
                'primary_query' => 'b-32a kontrola graniczna',
                'sign_slug' => 'b-32a-kontrola-graniczna',
                'mapped_title' => 'B-32a Kontrola graniczna',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić, że znak wymaga pełnego zatrzymania przed kontrolą graniczną i podporządkowania się organizacji punktu.',
                'correction_notes' => 'Trzymać razem z B-32 i B-32e jako rodzinę znaków zatrzymania związanych z kontrolą.',
                'competitor_notes' => 'Niszowy URL, ale budujący kompletność katalogu i wiarygodność pokrycia znaków B.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-32a.',
            ],
            [
                'primary_query' => 'b-32b rogatka uszkodzona',
                'sign_slug' => 'b-32b-rogatka-uszkodzona',
                'mapped_title' => 'B-32b Rogatka uszkodzona',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Pokazać, że znak zastępuje komfort zwykłego zabezpieczenia obowiązkiem zatrzymania i samodzielnej oceny przejazdu.',
                'correction_notes' => 'Nie mieszać z ogólną teorią przejazdów kolejowych; utrzymać nacisk na awaryjną organizację ruchu.',
                'competitor_notes' => 'Rzadkie query, ale dobre dla kompletności i dla użytkownika szukającego konkretnej odpowiedzi po nazwie znaku.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-32b.',
            ],
            [
                'primary_query' => 'b-32c sygnalizacja uszkodzona',
                'sign_slug' => 'b-32c-sygnalizacja-uszkodzona',
                'mapped_title' => 'B-32c Sygnalizacja uszkodzona',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić, że awaria sygnalizacji nie znosi ryzyka, tylko przerzuca odpowiedzialność na pełne zatrzymanie i ocenę warunków przejazdu.',
                'correction_notes' => 'Trzymać prostą, praktyczną odpowiedź i nie rozlewać jej na inne typy sygnalizacji.',
                'competitor_notes' => 'Bardzo długi ogon, ale wzmacnia pełne pokrycie znaków niszowych i specjalistycznych.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-32c.',
            ],
            [
                'primary_query' => 'b-32d wjazd na prom',
                'sign_slug' => 'b-32d-wjazd-na-prom',
                'mapped_title' => 'B-32d Wjazd na prom',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Osadzić znak w praktyce kolejki i załadunku na prom oraz wytłumaczyć, że sama opuszczona rampa nie daje jeszcze prawa wjazdu.',
                'correction_notes' => 'Utrzymywać mocny nacisk na polecenia obsługi i bezpieczeństwo ruchu na rampie.',
                'competitor_notes' => 'Niszowy, ale bardzo konkretny query, który dobrze domyka rodzinę nietypowych znaków zatrzymania.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-32d.',
            ],
            [
                'primary_query' => 'b-32e kontrola drogowa',
                'sign_slug' => 'b-32e-kontrola-drogowa',
                'mapped_title' => 'B-32e Kontrola drogowa',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić, że znak wymaga pełnego zatrzymania w punkcie kontroli drogowej i podporządkowania się dalszym sygnałom.',
                'correction_notes' => 'Trzymać w jednym passu z B-32 i B-32a, bo użytkownik często szuka tych znaków jako jednej rodziny.',
                'competitor_notes' => 'Mały wolumen, ale ważny dla kompletności i widoczności długiego ogona wokół znaków zakazu.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-32e.',
            ],
            [
                'primary_query' => 'b-39 strefa ograniczonego postoju',
                'sign_slug' => 'b-39-strefa-ograniczonego-postoju',
                'mapped_title' => 'B-39 Strefa ograniczonego postoju',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Mocno odróżnić strefę od pojedynczego zakazu postoju i pokazać, że zasada działa w całym obszarze.',
                'correction_notes' => 'Utrzymywać spójność z B-40 oraz z klastrem parkingowym.',
                'competitor_notes' => 'Silna wartość użytkowa dla codziennej jazdy w miastach i strefach śródmiejskich.',
                'watch_reason' => 'Anchor page dla strefy ograniczonego postoju.',
                'notes' => 'Rollout-04: strona znaku B-39.',
            ],
            [
                'primary_query' => 'b-40 koniec strefy ograniczonego postoju',
                'sign_slug' => 'b-40-koniec-strefy-ograniczonego-postoju',
                'mapped_title' => 'B-40 Koniec strefy ograniczonego postoju',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśnić moment odwołania zasad strefy postoju i relację do B-39.',
                'correction_notes' => 'Spinać z B-39 oraz z materiałem porównawczym dla tej strefy.',
                'competitor_notes' => 'Dobry URL domykający klaster parkingowy bez luk semantycznych.',
                'watch_reason' => null,
                'notes' => 'Rollout-04: strona znaku B-40.',
            ],
        ];

        $entries = [];

        foreach ($signEntries as $entry) {
            $sign = $signs[$entry['sign_slug']];

            $entries[] = [
                'primary_query' => $entry['primary_query'],
                'mapped_title' => $entry['mapped_title'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => $entry['search_intent'],
                'priority' => $entry['priority'],
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-04',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $prohibitionCategory->getKey(),
                'source_plan' => $entry['source_plan'],
                'correction_notes' => $entry['correction_notes'],
                'competitor_notes' => $entry['competitor_notes'],
                'first_mover_note' => null,
                'watch_reason' => $entry['watch_reason'],
                'notes' => $entry['notes'],
            ];
        }

        return array_merge($entries, [
            [
                'primary_query' => 'b-15 b-16 b-17 b-18 b-19 różnice',
                'mapped_title' => 'Porównanie B-15, B-16, B-17, B-18 i B-19',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-04',
                'target_path' => '/znaki-drogowe/porownania/b-15-do-b-19',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Porównać ograniczenia szerokości, wysokości, długości, masy całkowitej i nacisku osi w jednym praktycznym materiale.',
                'correction_notes' => 'Każda rewizja stron B-15 do B-19 powinna pociągać za sobą update tej strony porównawczej.',
                'competitor_notes' => 'Bardzo mocny materiał wspierający dla długiego ogona technicznych zapytań transportowych.',
                'first_mover_note' => 'Jedna zbiorcza strona o parametrach technicznych może zebrać intencję, której konkurencja zwykle nie porządkuje w jednym miejscu.',
                'watch_reason' => null,
                'notes' => 'Supporting page domykający klaster ograniczeń gabarytowych i masowych.',
            ],
            [
                'primary_query' => 'b-25 b-26 b-27 b-28 różnice',
                'mapped_title' => 'Porównanie B-25, B-26, B-27 i B-28',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-04',
                'target_path' => '/znaki-drogowe/porownania/b-25-do-b-28',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Rozdzielić zakazy wyprzedzania dla wszystkich pojazdów i dla ciężarówek oraz pokazać, jak odczytywać znaki kończące.',
                'correction_notes' => 'Rewidować przy każdej mocniejszej zmianie w treściach B-25 do B-28.',
                'competitor_notes' => 'Naturalny materiał porównawczy dla kursantów i kierowców zawodowych.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający klaster wyprzedzania.',
            ],
            [
                'primary_query' => 'b-29 b-30 różnice',
                'mapped_title' => 'Porównanie B-29 i B-30',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-04',
                'target_path' => '/znaki-drogowe/porownania/b-29-vs-b-30',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Wyjaśnić początek i koniec zakazu używania sygnałów dźwiękowych bez prawniczego przeładowania.',
                'correction_notes' => 'Trzymać prostą spójność z B-29 i B-30.',
                'competitor_notes' => 'Krótki, praktyczny materiał uzupełniający klaster sygnałów dźwiękowych.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page dla zakazu używania sygnałów dźwiękowych.',
            ],
            [
                'primary_query' => 'b-39 b-40 różnice',
                'mapped_title' => 'Porównanie B-39 i B-40',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-04',
                'target_path' => '/znaki-drogowe/porownania/b-39-vs-b-40',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Pokazać, jak działa strefa ograniczonego postoju i w którym momencie jej zasady wygasają.',
                'correction_notes' => 'Aktualizować razem z B-39 i B-40 oraz z parkingowym klastrem znaków zakazu.',
                'competitor_notes' => 'To praktyczne porównanie dobrze domyka parkingową część kategorii.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page dla strefy ograniczonego postoju.',
            ],
        ]);
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function rolloutFiveQueryMapEntries(array $signs, TrafficSignCategory $warningCategory): array
    {
        $signEntries = [
            [
                'primary_query' => 'a-5 skrzyżowanie dróg',
                'sign_slug' => 'a-5-skrzyzowanie-drog',
                'mapped_title' => 'A-5 Skrzyżowanie dróg',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Traktować jako stronę wejściową dla pytań o obserwację skrzyżowania i o rodzinę znaków A-5 do A-8.',
                'correction_notes' => 'Aktualizować równolegle z rodziną A-6 i materiałem wspierającym o znakach skrzyżowaniowych.',
                'competitor_notes' => 'Mocne query edukacyjne, gdzie przewagą ma być prostota, precyzja i praktyczny kontekst obserwacji.',
                'watch_reason' => 'Anchor page pierwszego warning batcha skrzyżowaniowego.',
                'notes' => 'Rollout-05: strona znaku A-5.',
            ],
            [
                'primary_query' => 'a-6a skrzyżowanie z drogą podporządkowaną po obu stronach',
                'sign_slug' => 'a-6a-skrzyzowanie-z-droga-podporzadkowana-po-obu-stronach',
                'mapped_title' => 'A-6a Skrzyżowanie z drogą podporządkowaną po obu stronach',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyjaśniać znak jako przygotowanie do szerszej obserwacji obu stron skrzyżowania podporządkowanego.',
                'correction_notes' => 'Pilnować spójności z A-6b i A-6c, żeby różnice kierunkowe nie rozjechały się redakcyjnie.',
                'competitor_notes' => 'To query zyskuje, gdy strona bardzo jasno pokazuje, gdzie kierowca ma przenieść uwagę.',
                'watch_reason' => 'Mocny element mini-klastra A-6.',
                'notes' => 'Rollout-05: strona znaku A-6a.',
            ],
            [
                'primary_query' => 'a-6b skrzyżowanie z drogą podporządkowaną po prawej stronie',
                'sign_slug' => 'a-6b-skrzyzowanie-z-droga-podporzadkowana-po-prawej-stronie',
                'mapped_title' => 'A-6b Skrzyżowanie z drogą podporządkowaną po prawej stronie',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Akcentować kierunkowy charakter obserwacji i czytelnie odróżniać prawy wlot od innych wariantów A-6.',
                'correction_notes' => 'Trzymać jedną logikę z A-6a i A-6c w przyszłych rewizjach.',
                'competitor_notes' => 'Przy tym typie znaku liczy się precyzja różnic, nie długość treści.',
                'watch_reason' => null,
                'notes' => 'Rollout-05: strona znaku A-6b.',
            ],
            [
                'primary_query' => 'a-6c skrzyżowanie z drogą podporządkowaną po lewej stronie',
                'sign_slug' => 'a-6c-skrzyzowanie-z-droga-podporzadkowana-po-lewej-stronie',
                'mapped_title' => 'A-6c Skrzyżowanie z drogą podporządkowaną po lewej stronie',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować odpowiedź wokół obserwacji lewego wlotu i różnic względem A-6a / A-6b.',
                'correction_notes' => 'Nie dopuścić do sklejenia tej strony z A-6b w zbyt ogólne tłumaczenie podporządkowanych wlotów.',
                'competitor_notes' => 'Dobra strona jakościowa, jeśli od razu rozwiązuje realną pomyłkę kierowcy i kursanta.',
                'watch_reason' => null,
                'notes' => 'Rollout-05: strona znaku A-6c.',
            ],
            [
                'primary_query' => 'a-8 skrzyżowanie o ruchu okrężnym',
                'sign_slug' => 'a-8-skrzyzowanie-o-ruchu-okreznym',
                'mapped_title' => 'A-8 Skrzyżowanie o ruchu okrężnym',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyjaśniać znak przez pryzmat dojazdu do ronda, obserwacji ruchu na obwiedni i zachowania kierowcy.',
                'correction_notes' => 'Spinać z A-5 i A-7 oraz z materiałem porównawczym o znakach skrzyżowaniowych.',
                'competitor_notes' => 'Mocny, często pytany query, który zyskuje na prostym i praktycznym leadzie.',
                'watch_reason' => 'Anchor page pierwszego warning batcha o rondach i skrzyżowaniach.',
                'notes' => 'Rollout-05: strona znaku A-8.',
            ],
            [
                'primary_query' => 'a-16 przejście dla pieszych',
                'sign_slug' => 'a-16-przejscie-dla-pieszych',
                'mapped_title' => 'A-16 Przejście dla pieszych',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pisać pod realne zachowanie kierowcy przed przejściem, a nie wyłącznie pod samą definicję znaku.',
                'correction_notes' => 'Aktualizować razem z A-17, A-24 i materiałem wspierającym o pieszych, dzieciach i rowerzystach.',
                'competitor_notes' => 'Silny query użytkowy, w którym przewagą będzie konkret obserwacyjny i czysty lead.',
                'watch_reason' => 'Anchor page mini-klastra o niechronionych uczestnikach ruchu.',
                'notes' => 'Rollout-05: strona znaku A-16.',
            ],
            [
                'primary_query' => 'a-24 rowerzyści',
                'sign_slug' => 'a-24-rowerzysci',
                'mapped_title' => 'A-24 Rowerzyści',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować odpowiedź pod realne punkty kolizji z ruchem rowerowym, przejazdami i włączaniem się rowerzystów do ruchu.',
                'correction_notes' => 'Rewidować równolegle z A-16 i A-17, żeby utrzymać spójny klaster uczestników ruchu.',
                'competitor_notes' => 'To query dobrze reaguje na praktyczne przykłady i precyzyjne odróżnienie rowerzysty od pieszego.',
                'watch_reason' => 'Silna strona użytkowa dla pierwszego warning batcha.',
                'notes' => 'Rollout-05: strona znaku A-24.',
            ],
        ];

        $entries = [];

        foreach ($signEntries as $entry) {
            $sign = $signs[$entry['sign_slug']];

            $entries[] = [
                'primary_query' => $entry['primary_query'],
                'mapped_title' => $entry['mapped_title'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => $entry['search_intent'],
                'priority' => $entry['priority'],
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-05',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $warningCategory->getKey(),
                'source_plan' => $entry['source_plan'],
                'correction_notes' => $entry['correction_notes'],
                'competitor_notes' => $entry['competitor_notes'],
                'first_mover_note' => null,
                'watch_reason' => $entry['watch_reason'],
                'notes' => $entry['notes'],
            ];
        }

        return array_merge($entries, [
            [
                'primary_query' => 'a-5 a-6 a-8 różnice',
                'mapped_title' => 'Porównanie A-5, A-6 i A-8',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-05',
                'target_path' => '/znaki-drogowe/porownania/a-5-do-a-8',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Materiał ma porządkować rodzinę warning signs o skrzyżowaniach: od ogólnego ostrzeżenia do podporządkowanych wlotów, ustępowania pierwszeństwa i ronda.',
                'correction_notes' => 'Każda rewizja A-5, A-6, A-7 lub A-8 powinna pociągać za sobą korektę tego materiału porównawczego.',
                'competitor_notes' => 'To bardzo praktyczny supporting page, bo użytkownik często miesza te znaki między sobą podczas jednej sesji nauki lub szukania odpowiedzi.',
                'first_mover_note' => 'Jedna dobra strona porównawcza dla rodziny skrzyżowaniowej może szybko zebrać intencję, którą konkurencja zwykle rozprasza po osobnych hasłach.',
                'watch_reason' => null,
                'notes' => 'Supporting page domykający pierwszy warning mini-klaster o skrzyżowaniach.',
            ],
            [
                'primary_query' => 'a-16 a-17 a-24 różnice',
                'mapped_title' => 'Porównanie A-16, A-17 i A-24',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-05',
                'target_path' => '/znaki-drogowe/porownania/a-16-vs-a-17-vs-a-24',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Porządkować trzy częste ostrzeżenia o uczestnikach ruchu i pokazywać, jak zmienia się obserwacja kierowcy przy pieszych, dzieciach i rowerzystach.',
                'correction_notes' => 'Aktualizować razem z A-16, A-17 i A-24, żeby klaster zachował jedną logikę obserwacji i zachowania kierowcy.',
                'competitor_notes' => 'Mocny materiał pomocniczy dla intencji egzaminacyjnej i praktycznej w mieście oraz przy szkołach i przejazdach rowerowych.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page dla pierwszego warning mini-klastra o pieszych, dzieciach i rowerzystach.',
            ],
        ]);
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function rolloutSixQueryMapEntries(array $signs, TrafficSignCategory $warningCategory): array
    {
        $signEntries = [
            [
                'primary_query' => 'a-1 niebezpieczny zakręt w prawo',
                'sign_slug' => 'a-1-niebezpieczny-zakret-w-prawo',
                'mapped_title' => 'A-1 Niebezpieczny zakręt w prawo',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować odpowiedź wokół wcześniejszego wytracenia prędkości i ustawienia auta jeszcze przed wejściem w prawy łuk.',
                'correction_notes' => 'Pilnować spójności z A-2, A-3 i A-4 oraz z materiałem porównawczym o rodzinie zakrętów.',
                'competitor_notes' => 'Silny query praktyczny, gdzie przewagą jest spokojne wyjaśnienie zachowania kierowcy zamiast suchej definicji.',
                'watch_reason' => 'Anchor page rollout-06 dla rodziny ostrzeżeń o zakrętach.',
                'notes' => 'Rollout-06: strona znaku A-1.',
            ],
            [
                'primary_query' => 'a-2 niebezpieczny zakręt w lewo',
                'sign_slug' => 'a-2-niebezpieczny-zakret-w-lewo',
                'mapped_title' => 'A-2 Niebezpieczny zakręt w lewo',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Akcentować wcześniejsze ustawienie prędkości i obserwacji pod lewy łuk, bez odkładania decyzji na środek zakrętu.',
                'correction_notes' => 'Trzymać merytoryczną symetrię z A-1 i z szerszym materiałem A-1 do A-4.',
                'competitor_notes' => 'Bardzo dobre query do pokazania różnicy między teorią znaku a praktyką dojazdu do zakrętu.',
                'watch_reason' => null,
                'notes' => 'Rollout-06: strona znaku A-2.',
            ],
            [
                'primary_query' => 'a-3 niebezpieczne zakręty pierwszy w prawo',
                'sign_slug' => 'a-3-niebezpieczne-zakrety-pierwszy-w-prawo',
                'mapped_title' => 'A-3 Niebezpieczne zakręty - pierwszy w prawo',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyjaśniać znak jako ostrzeżenie o całej sekwencji zakrętów, a nie pojedynczym prawym łuku.',
                'correction_notes' => 'Każda rewizja A-4 powinna pociągać za sobą sprawdzenie spójności tej strony.',
                'competitor_notes' => 'Przewaga jakościowa będzie wynikać z czytelnego rozróżnienia serii zakrętów od pojedynczego zakrętu.',
                'watch_reason' => 'Mocny element klastra o serii zakrętów.',
                'notes' => 'Rollout-06: strona znaku A-3.',
            ],
            [
                'primary_query' => 'a-4 niebezpieczne zakręty pierwszy w lewo',
                'sign_slug' => 'a-4-niebezpieczne-zakrety-pierwszy-w-lewo',
                'mapped_title' => 'A-4 Niebezpieczne zakręty - pierwszy w lewo',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować odpowiedź wokół dłuższego zarządzania prędkością na sekwencji zakrętów zaczynającej się w lewo.',
                'correction_notes' => 'Trzymać bliską relację z A-3 i supporting page A-1 do A-4.',
                'competitor_notes' => 'Dobre query do przejęcia dzięki prostemu pokazaniu, że pierwszy łuk nie zamyka zagrożenia.',
                'watch_reason' => null,
                'notes' => 'Rollout-06: strona znaku A-4.',
            ],
            [
                'primary_query' => 'a-9 przejazd kolejowy z zaporami',
                'sign_slug' => 'a-9-przejazd-kolejowy-z-zaporami',
                'mapped_title' => 'A-9 Przejazd kolejowy z zaporami',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pisać pod realny dojazd do przejazdu: tory, sygnały, zapory i gotowość do spokojnego zatrzymania.',
                'correction_notes' => 'Aktualizować równolegle z A-10 i materiałem porównawczym o przejazdach kolejowych.',
                'competitor_notes' => 'Mocny query edukacyjny i bezpieczeństwa ruchu, gdzie użytkownik oczekuje prostego przewodnika zachowania.',
                'watch_reason' => 'Anchor page rollout-06 dla warning mini-klastra kolejowego.',
                'notes' => 'Rollout-06: strona znaku A-9.',
            ],
            [
                'primary_query' => 'a-10 przejazd kolejowy bez zapór',
                'sign_slug' => 'a-10-przejazd-kolejowy-bez-zapor',
                'mapped_title' => 'A-10 Przejazd kolejowy bez zapór',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Akcentować różnicę względem A-9 i ciężar własnej obserwacji przy przejeździe bez fizycznych zapór.',
                'correction_notes' => 'Pilnować wspólnej logiki z A-9 i porównaniem A-9 vs A-10.',
                'competitor_notes' => 'To zapytanie dobrze reaguje na praktyczny, krokowy opis zachowania kierowcy.',
                'watch_reason' => null,
                'notes' => 'Rollout-06: strona znaku A-10.',
            ],
            [
                'primary_query' => 'a-11 nierówna droga',
                'sign_slug' => 'a-11-nierowna-droga',
                'mapped_title' => 'A-11 Nierówna droga',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować stronę wokół wpływu nawierzchni na tor jazdy, przyczepność i komfort prowadzenia auta.',
                'correction_notes' => 'Trzymać spójność z A-11a i rodziną A-12, żeby użytkownik łatwo rozróżniał charakter ostrzeżeń.',
                'competitor_notes' => 'Praktyczny query codziennej jazdy, który zyskuje na konkretnym tłumaczeniu zachowania auta.',
                'watch_reason' => 'Anchor page mini-klastra o nawierzchni i przewężeniach.',
                'notes' => 'Rollout-06: strona znaku A-11.',
            ],
            [
                'primary_query' => 'a-11a próg zwalniający',
                'sign_slug' => 'a-11a-prog-zwalniajacy',
                'mapped_title' => 'A-11a Próg zwalniający',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyraźnie odróżniać miejscowy próg zwalniający od bardziej ogólnej nierównej drogi.',
                'correction_notes' => 'Aktualizować razem z A-11 i materiałem porównawczym A-11 do A-12c.',
                'competitor_notes' => 'Silna wartość praktyczna w ruchu miejskim i osiedlowym.',
                'watch_reason' => null,
                'notes' => 'Rollout-06: strona znaku A-11a.',
            ],
            [
                'primary_query' => 'a-12a zwężenie jezdni dwustronne',
                'sign_slug' => 'a-12a-zwezenie-jezdni-dwustronne',
                'mapped_title' => 'A-12a Zwężenie jezdni - dwustronne',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Tłumaczyć znak przez pryzmat utraty marginesu z obu stron i wcześniejszego ustawienia toru jazdy.',
                'correction_notes' => 'Pilnować spójności z A-12b i A-12c oraz z materiałem porównawczym rodziny A-12.',
                'competitor_notes' => 'Bardzo dobra strona do przejęcia jakościowo dzięki prostemu opisowi praktyki mijania i zwężenia.',
                'watch_reason' => 'Anchor page dla rodziny A-12 w rollout-06.',
                'notes' => 'Rollout-06: strona znaku A-12a.',
            ],
            [
                'primary_query' => 'a-12b zwężenie jezdni prawostronne',
                'sign_slug' => 'a-12b-zwezenie-jezdni-prawostronne',
                'mapped_title' => 'A-12b Zwężenie jezdni - prawostronne',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Akcentować, z której strony kierowca traci margines i jak wcześnie powinien skorygować tor jazdy.',
                'correction_notes' => 'Nie dopuścić do zlania strony z A-12c; różnica strony zwężenia musi być bardzo czytelna.',
                'competitor_notes' => 'Przy tym query liczy się precyzja i szybkość odpowiedzi, nie objętość tekstu.',
                'watch_reason' => null,
                'notes' => 'Rollout-06: strona znaku A-12b.',
            ],
            [
                'primary_query' => 'a-12c zwężenie jezdni lewostronne',
                'sign_slug' => 'a-12c-zwezenie-jezdni-lewostronne',
                'mapped_title' => 'A-12c Zwężenie jezdni - lewostronne',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować odpowiedź wokół wcześniejszej obserwacji lewej strony i korekty toru jazdy przed zwężeniem.',
                'correction_notes' => 'Trzymać w parze z A-12b i z materiałem A-11 do A-12c.',
                'competitor_notes' => 'Dobre query jakościowe, jeśli strona szybko rozwiązuje pomyłkę co do kierunku zwężenia.',
                'watch_reason' => null,
                'notes' => 'Rollout-06: strona znaku A-12c.',
            ],
        ];

        $entries = [];

        foreach ($signEntries as $entry) {
            $sign = $signs[$entry['sign_slug']];

            $entries[] = [
                'primary_query' => $entry['primary_query'],
                'mapped_title' => $entry['mapped_title'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => $entry['search_intent'],
                'priority' => $entry['priority'],
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-06',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $warningCategory->getKey(),
                'source_plan' => $entry['source_plan'],
                'correction_notes' => $entry['correction_notes'],
                'competitor_notes' => $entry['competitor_notes'],
                'first_mover_note' => null,
                'watch_reason' => $entry['watch_reason'],
                'notes' => $entry['notes'],
            ];
        }

        return array_merge($entries, [
            [
                'primary_query' => 'a-1 a-2 a-3 a-4 różnice',
                'mapped_title' => 'Porównanie A-1, A-2, A-3 i A-4',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-06',
                'target_path' => '/znaki-drogowe/porownania/a-1-do-a-4',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jednym materiałem uporządkować pojedyncze zakręty i serię zakrętów oraz różnice kierunkowe dla pierwszego łuku.',
                'correction_notes' => 'Aktualizować razem z A-1 do A-4, żeby zachować spójną logikę całego mini-klastra zakrętów.',
                'competitor_notes' => 'To praktyczny supporting page pod częste pomyłki kursantów i kierowców podczas nauki rodziny A-1 do A-4.',
                'first_mover_note' => 'Dobra strona porównawcza o zakrętach może szybko przejąć intencję, którą konkurencja zwykle rozbija na zbyt ogólne osobne hasła.',
                'watch_reason' => null,
                'notes' => 'Supporting page domykający rollout-06 dla rodziny znaków o zakrętach.',
            ],
            [
                'primary_query' => 'a-9 a-10 różnice',
                'mapped_title' => 'Porównanie A-9 i A-10',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-06',
                'target_path' => '/znaki-drogowe/porownania/a-9-vs-a-10',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Wyjaśnić różnicę między przejazdem kolejowym z zaporami i bez zapór przez pryzmat zachowania kierowcy na dojeździe do torów.',
                'correction_notes' => 'Każda rewizja A-9 lub A-10 powinna pociągać za sobą sprawdzenie tego porównania.',
                'competitor_notes' => 'Mocny materiał wspierający dla pytań o bezpieczeństwo na przejazdach kolejowych i o różnice w dojeździe do torów.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający rollout-06 dla warning mini-klastra kolejowego.',
            ],
            [
                'primary_query' => 'a-11 a-11a a-12a a-12b a-12c różnice',
                'mapped_title' => 'Porównanie A-11, A-11a i A-12',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-06',
                'target_path' => '/znaki-drogowe/porownania/a-11-do-a-12c',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jednym materiałem rozdzielić ostrzeżenia o nawierzchni od ostrzeżeń o zwężeniu jezdni oraz wyjaśnić, jak czytać stronę zwężenia.',
                'correction_notes' => 'Aktualizować równolegle z A-11, A-11a i rodziną A-12 przy każdym większym passu redakcyjnym.',
                'competitor_notes' => 'Przy tym supporting page przewagą będzie prosty podział: co dzieje się pod kołami, a co dzieje się z szerokością jezdni.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający rollout-06 dla mini-klastra nawierzchni i przewężeń.',
            ],
        ]);
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function rolloutSevenQueryMapEntries(array $signs, TrafficSignCategory $warningCategory): array
    {
        $signEntries = [
            [
                'primary_query' => 'a-6d wlot drogi jednokierunkowej z prawej strony',
                'sign_slug' => 'a-6d-wlot-drogi-jednokierunkowej-z-prawej-strony',
                'mapped_title' => 'A-6d Wlot drogi jednokierunkowej z prawej strony',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Akcentować kierunek obserwacji i różnicę między zwykłym wlotem podporządkowanym a drogą jednokierunkową z prawej strony.',
                'correction_notes' => 'Aktualizować razem z A-6e oraz z materiałem porównawczym A-6d vs A-6e.',
                'competitor_notes' => 'To query zyskuje, gdy strona szybko rozwiązuje prostą, ale częstą pomyłkę kierunkową.',
                'watch_reason' => 'Domknięcie warning rodziny A-6.',
                'notes' => 'Rollout-07: strona znaku A-6d.',
            ],
            [
                'primary_query' => 'a-6e wlot drogi jednokierunkowej z lewej strony',
                'sign_slug' => 'a-6e-wlot-drogi-jednokierunkowej-z-lewej-strony',
                'mapped_title' => 'A-6e Wlot drogi jednokierunkowej z lewej strony',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować odpowiedź wokół lewego wlotu jednokierunkowego i wcześniejszego przesunięcia uwagi na tę stronę skrzyżowania.',
                'correction_notes' => 'Trzymać pełną spójność z A-6d oraz ze starszymi stronami A-6b i A-6c.',
                'competitor_notes' => 'Przewaga jakościowa będzie wynikać z prostoty i precyzji wyjaśnienia kierunku wlotu.',
                'watch_reason' => null,
                'notes' => 'Rollout-07: strona znaku A-6e.',
            ],
            [
                'primary_query' => 'a-14 roboty na drodze',
                'sign_slug' => 'a-14-roboty-na-drodze',
                'mapped_title' => 'A-14 Roboty na drodze',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pisać pod realne zachowanie kierowcy na odcinku robót: organizację ruchu, ludzi, sprzęt i zmianę toru jazdy.',
                'correction_notes' => 'Aktualizować równolegle z A-15 i A-20 oraz z materiałem wspierającym o zmianie rytmu jazdy.',
                'competitor_notes' => 'Silny query praktyczny, gdzie użytkownik szuka nie tylko definicji, ale reakcji na odcinku robót.',
                'watch_reason' => 'Anchor page rollout-07 dla mini-klastra warunków odcinkowych.',
                'notes' => 'Rollout-07: strona znaku A-14.',
            ],
            [
                'primary_query' => 'a-15 śliska jezdnia',
                'sign_slug' => 'a-15-sliska-jezdnia',
                'mapped_title' => 'A-15 Śliska jezdnia',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Akcentować spadek przyczepności, wydłużenie reakcji auta i konieczność wcześniejszego uspokojenia jazdy.',
                'correction_notes' => 'Trzymać spójność z A-14, A-20 i przyszłymi stronami pogodowo-nawierzchniowymi.',
                'competitor_notes' => 'Mocne query użytkowe pod praktykę hamowania i przyczepności, nie tylko pod sam znak.',
                'watch_reason' => null,
                'notes' => 'Rollout-07: strona znaku A-15.',
            ],
            [
                'primary_query' => 'a-18a zwierzęta gospodarskie',
                'sign_slug' => 'a-18a-zwierzeta-gospodarskie',
                'mapped_title' => 'A-18a Zwierzęta gospodarskie',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować stronę wokół kontekstu wiejskiego i przewidywania zachowania zwierząt gospodarskich w pobliżu drogi.',
                'correction_notes' => 'Aktualizować razem z A-18b oraz z materiałem porównawczym o zwierzętach na drodze.',
                'competitor_notes' => 'Dobrze rokuje jako konkretna odpowiedź w prostym, precyzyjnym stylu bez nadmiernej teorii.',
                'watch_reason' => 'Anchor page rollout-07 dla mini-klastra o zwierzętach.',
                'notes' => 'Rollout-07: strona znaku A-18a.',
            ],
            [
                'primary_query' => 'a-18b zwierzęta dzikie',
                'sign_slug' => 'a-18b-zwierzeta-dzikie',
                'mapped_title' => 'A-18b Zwierzęta dzikie',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Akcentować nagłość wtargnięcia zwierzęcia i odmienny kontekst obserwacji względem zwierząt gospodarskich.',
                'correction_notes' => 'Spinać z A-18a i supporting page A-18a vs A-18b.',
                'competitor_notes' => 'Bardzo praktyczny query pod ruch pozamiejski i leśny, dobrze reagujący na proste, konkretne leady.',
                'watch_reason' => null,
                'notes' => 'Rollout-07: strona znaku A-18b.',
            ],
            [
                'primary_query' => 'a-20 odcinek jezdni o ruchu dwukierunkowym',
                'sign_slug' => 'a-20-odcinek-jezdni-o-ruchu-dwukierunkowym',
                'mapped_title' => 'A-20 Odcinek jezdni o ruchu dwukierunkowym',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyjaśniać znak przez pryzmat powrotu ruchu z przeciwka i zmiany ustawienia toru jazdy po wcześniejszym odcinku bez takich relacji.',
                'correction_notes' => 'Aktualizować równolegle z A-14 i A-15 oraz z materiałem wspierającym o zmianie rytmu jazdy.',
                'competitor_notes' => 'Mocny query egzaminacyjny i praktyczny, bo użytkownik często myli sam symbol z szerszym skutkiem dla obserwacji drogi.',
                'watch_reason' => 'Anchor page rollout-07 dla zmiany organizacji ruchu na odcinku.',
                'notes' => 'Rollout-07: strona znaku A-20.',
            ],
            [
                'primary_query' => 'a-29 sygnały świetlne',
                'sign_slug' => 'a-29-sygnaly-swietlne',
                'mapped_title' => 'A-29 Sygnały świetlne',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pisać pod dojazd do sygnalizacji świetlnej i gotowość do zmiany wskazań, nie pod sam opis symbolu świateł.',
                'correction_notes' => 'Trzymać ścisłą różnicę względem A-30 i materiału porównawczego A-29 vs A-30.',
                'competitor_notes' => 'Silny miejski query, który zyskuje na konkretnym opisaniu zachowania kierowcy przed światłami.',
                'watch_reason' => 'Anchor page rollout-07 dla mini-klastra A-29 / A-30.',
                'notes' => 'Rollout-07: strona znaku A-29.',
            ],
            [
                'primary_query' => 'a-30 inne niebezpieczeństwo',
                'sign_slug' => 'a-30-inne-niebezpieczenstwo',
                'mapped_title' => 'A-30 Inne niebezpieczeństwo',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Tłumaczyć znak przez pryzmat jego ogólności i konieczności czytania tabliczki lub kontekstu miejsca.',
                'correction_notes' => 'Aktualizować razem z A-29 oraz z przyszłymi przykładami użycia A-30 z tabliczkami doprecyzowującymi.',
                'competitor_notes' => 'To dobra strona jakościowa, jeśli nie zostanie rozwodniona i od razu wyjaśni, że znak wymaga doprecyzowania.',
                'watch_reason' => null,
                'notes' => 'Rollout-07: strona znaku A-30.',
            ],
        ];

        $entries = [];

        foreach ($signEntries as $entry) {
            $sign = $signs[$entry['sign_slug']];

            $entries[] = [
                'primary_query' => $entry['primary_query'],
                'mapped_title' => $entry['mapped_title'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => $entry['search_intent'],
                'priority' => $entry['priority'],
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-07',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $warningCategory->getKey(),
                'source_plan' => $entry['source_plan'],
                'correction_notes' => $entry['correction_notes'],
                'competitor_notes' => $entry['competitor_notes'],
                'first_mover_note' => null,
                'watch_reason' => $entry['watch_reason'],
                'notes' => $entry['notes'],
            ];
        }

        return array_merge($entries, [
            [
                'primary_query' => 'a-6d a-6e różnice',
                'mapped_title' => 'Porównanie A-6d i A-6e',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-07',
                'target_path' => '/znaki-drogowe/porownania/a-6d-vs-a-6e',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jednym materiałem wyjaśnić różnicę między wlotem drogi jednokierunkowej z prawej i z lewej strony.',
                'correction_notes' => 'Aktualizować razem z A-6d i A-6e oraz trzymać spójność z wcześniejszym materiałem A-5 do A-8.',
                'competitor_notes' => 'To zwięzły, bardzo użyteczny supporting page pod typowe pomyłki kursantów i kierowców.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający rollout-07 dla warning rodziny A-6d / A-6e.',
            ],
            [
                'primary_query' => 'a-14 a-15 a-20 różnice',
                'mapped_title' => 'Porównanie A-14, A-15 i A-20',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-07',
                'target_path' => '/znaki-drogowe/porownania/a-14-a-15-a-20',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Pokazać trzy różne powody zmiany rytmu jazdy: roboty, śliską jezdnię i powrót ruchu z przeciwka.',
                'correction_notes' => 'Każda większa rewizja A-14, A-15 lub A-20 powinna pociągać za sobą sprawdzenie tego materiału porównawczego.',
                'competitor_notes' => 'Dobry materiał porządkujący praktyczne, a nie tylko definicyjne różnice między znakami.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający rollout-07 dla mini-klastra warunków odcinkowych.',
            ],
            [
                'primary_query' => 'a-18a a-18b różnice',
                'mapped_title' => 'Porównanie A-18a i A-18b',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-07',
                'target_path' => '/znaki-drogowe/porownania/a-18a-vs-a-18b',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jedną stroną uporządkować różnicę między zwierzętami gospodarskimi i dzikimi w kontekście obserwacji drogi.',
                'correction_notes' => 'Aktualizować razem z A-18a i A-18b.',
                'competitor_notes' => 'Mocny supporting page dla prostej, ale regularnie mylonej pary warning signs.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający rollout-07 dla znaków o zwierzętach.',
            ],
            [
                'primary_query' => 'a-29 a-30 różnice',
                'mapped_title' => 'Porównanie A-29 i A-30',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-07',
                'target_path' => '/znaki-drogowe/porownania/a-29-vs-a-30',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Wyjaśnić różnicę między ostrzeżeniem o sygnalizacji świetlnej i ogólnym ostrzeżeniem wymagającym doprecyzowania.',
                'correction_notes' => 'Trzymać ścisłą relację z A-29 i A-30 oraz z przyszłymi przykładami użycia A-30 z tabliczkami.',
                'competitor_notes' => 'Dobry supporting page dla odróżnienia znaku konkretnego od znaku ogólnego.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający rollout-07 dla A-29 / A-30.',
            ],
        ]);
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function rolloutEightQueryMapEntries(array $signs, TrafficSignCategory $warningCategory): array
    {
        $signEntries = [
            [
                'primary_query' => 'a-13 ruchomy most',
                'sign_slug' => 'a-13-ruchomy-most',
                'mapped_title' => 'A-13 Ruchomy most',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśniać znak przez zmienną organizację przejazdu i potrzebę wcześniejszej obserwacji mostu oraz sygnałów. ',
                'correction_notes' => 'Aktualizować razem z A-19, A-21 i materiałem A-13 / A-19 / A-21, żeby utrzymać spójny mini-klaster nietypowych zagrożeń odcinkowych.',
                'competitor_notes' => 'Przewaga jakościowa może wynikać z prostego pokazania, jak ten rzadziej spotykany znak wpływa na zachowanie kierowcy.',
                'watch_reason' => 'Początek finalnego batcha warning signs.',
                'notes' => 'Rollout-08: strona znaku A-13.',
            ],
            [
                'primary_query' => 'a-19 boczny wiatr',
                'sign_slug' => 'a-19-boczny-wiatr',
                'mapped_title' => 'A-19 Boczny wiatr',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować treść wokół utrzymania toru jazdy, wpływu podmuchu na pojazd i realnych miejsc, w których znak ma największy sens.',
                'correction_notes' => 'Wspólnie aktualizować z A-13 i A-21 oraz z końcowym materiałem porównawczym rollout-08.',
                'competitor_notes' => 'Mocny practical query, gdzie użytkownik oczekuje konkretnej odpowiedzi o zachowaniu auta i kierownicy.',
                'watch_reason' => 'Jedna z najmocniejszych behavioral pages końcówki warningów.',
                'notes' => 'Rollout-08: strona znaku A-19.',
            ],
            [
                'primary_query' => 'a-21 tramwaj',
                'sign_slug' => 'a-21-tramwaj',
                'mapped_title' => 'A-21 Tramwaj',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_EXAM,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pisać o przecinaniu się ruchu samochodu z torem tramwaju, obserwacji torowiska i typowych pomyłkach egzaminacyjnych.',
                'correction_notes' => 'Trzymać spójność z A-13, A-19 i supporting page A-13 / A-19 / A-21.',
                'competitor_notes' => 'Bardzo naturalne query miejskie i egzaminacyjne, które dobrze reaguje na prostą, praktyczną strukturę odpowiedzi.',
                'watch_reason' => 'Anchor page rollout-08 dla miejskiego ostrzeżenia o tramwaju.',
                'notes' => 'Rollout-08: strona znaku A-21.',
            ],
            [
                'primary_query' => 'a-22 niebezpieczny zjazd',
                'sign_slug' => 'a-22-niebezpieczny-zjazd',
                'mapped_title' => 'A-22 Niebezpieczny zjazd',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Oprzeć stronę o tempo zjazdu, hamowanie silnikiem i przygotowanie auta przed dłuższym spadkiem drogi.',
                'correction_notes' => 'Aktualizować w parze z A-23 oraz z materiałem porównawczym o zjeździe i podjeździe.',
                'competitor_notes' => 'Dobre query praktyczne, bo kierowca szuka zwykle odpowiedzi operacyjnej, a nie tylko nazwy znaku.',
                'watch_reason' => 'Anchor page dla mini-klastra A-22 / A-23.',
                'notes' => 'Rollout-08: strona znaku A-22.',
            ],
            [
                'primary_query' => 'a-23 stromy podjazd',
                'sign_slug' => 'a-23-stromy-podjazd',
                'mapped_title' => 'A-23 Stromy podjazd',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Pokazywać znak przez dobór biegu, zachowanie płynności i wcześniejsze przewidywanie tempa jazdy na podjeździe.',
                'correction_notes' => 'Trzymać ścisłą relację z A-22 i supporting page A-22 vs A-23.',
                'competitor_notes' => 'Dobra para do porównania ze zjazdem; przewagą będzie czytelna, symetryczna odpowiedź dla obu znaków.',
                'watch_reason' => null,
                'notes' => 'Rollout-08: strona znaku A-23.',
            ],
            [
                'primary_query' => 'a-25 spadające odłamki skalne',
                'sign_slug' => 'a-25-spadajace-odlamki-skalne',
                'mapped_title' => 'A-25 Spadające odłamki skalne',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Akcentować ograniczony czas reakcji, specyfikę terenu i konieczność spokojnego prowadzenia pojazdu pod ścianą skalną.',
                'correction_notes' => 'Aktualizować razem z A-26, A-27 i A-28 oraz z materiałem o zagrożeniach terenowych.',
                'competitor_notes' => 'Niszowy, ale jakościowo ważny query wzmacniający pełne pokrycie warning catalog.',
                'watch_reason' => 'Początek mini-klastra A-25 do A-28.',
                'notes' => 'Rollout-08: strona znaku A-25.',
            ],
            [
                'primary_query' => 'a-26 lotnisko',
                'sign_slug' => 'a-26-lotnisko',
                'mapped_title' => 'A-26 Lotnisko',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Wyjaśniać praktyczne znaczenie znaku przez hałas, rozproszenie i specyfikę otoczenia drogi przy lotnisku.',
                'correction_notes' => 'Utrzymywać w jednej rewizji z A-25, A-27 i A-28.',
                'competitor_notes' => 'Rzadziej spotykany znak może wygrać prostym, konkretnym opisem zastosowania w praktyce.',
                'watch_reason' => null,
                'notes' => 'Rollout-08: strona znaku A-26.',
            ],
            [
                'primary_query' => 'a-27 nabrzeże lub brzeg rzeki',
                'sign_slug' => 'a-27-nabrzeze-lub-brzeg-rzeki',
                'mapped_title' => 'A-27 Nabrzeże lub brzeg rzeki',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Pisać o utrzymaniu toru jazdy przy samej krawędzi i o dużych konsekwencjach błędu na tego typu odcinkach.',
                'correction_notes' => 'Spinać z A-25, A-26 i A-28 oraz pilnować operacyjnego tonu bez przesadnej teorii.',
                'competitor_notes' => 'Długi ogon, ale dobry dla pełnej wiarygodności katalogu warning signs.',
                'watch_reason' => null,
                'notes' => 'Rollout-08: strona znaku A-27.',
            ],
            [
                'primary_query' => 'a-28 sypki żwir',
                'sign_slug' => 'a-28-sypki-zwir',
                'mapped_title' => 'A-28 Sypki żwir',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Budować odpowiedź wokół przyczepności, wydłużenia drogi hamowania i bezpiecznych odstępów na luźnej nawierzchni.',
                'correction_notes' => 'Dobrze później połączyć z A-15 i A-32, ale w rollout-08 utrzymać go w rodzinie A-25 do A-28.',
                'competitor_notes' => 'Mocny practical query pod codzienną jazdę poza miastem i na drogach o gorszej nawierzchni.',
                'watch_reason' => 'Najbardziej użytkowy znak kończący mini-klaster terenowy A-25 do A-28.',
                'notes' => 'Rollout-08: strona znaku A-28.',
            ],
            [
                'primary_query' => 'a-31 niebezpieczne pobocze',
                'sign_slug' => 'a-31-niebezpieczne-pobocze',
                'mapped_title' => 'A-31 Niebezpieczne pobocze',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'source_plan' => 'Opisać znak przez stabilność auta, ryzyko zjazdu kołem poza jezdnię i reakcję przy odzyskiwaniu toru jazdy.',
                'correction_notes' => 'Trzymać w relacji do A-32, A-33 i A-34 oraz końcowego materiału porównawczego rollout-08.',
                'competitor_notes' => 'Dobre query do jakościowej odpowiedzi o błędach kierowcy na wąskich drogach i poboczu bez nośności.',
                'watch_reason' => 'Początek końcowego mini-klastra A-31 do A-34.',
                'notes' => 'Rollout-08: strona znaku A-31.',
            ],
            [
                'primary_query' => 'a-32 oszronienie jezdni',
                'sign_slug' => 'a-32-oszronienie-jezdni',
                'mapped_title' => 'A-32 Oszronienie jezdni',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Tłumaczyć znak przez spadek przyczepności, temperaturę nawierzchni i spokojne prowadzenie auta na śliskim odcinku.',
                'correction_notes' => 'W quality passie dobrze połączyć z A-15 i A-28, ale tu utrzymać go w rodzinie A-31 do A-34.',
                'competitor_notes' => 'Silny sezonowy query, który dobrze reaguje na zwięzłą i praktyczną strukturę odpowiedzi.',
                'watch_reason' => 'Najmocniejszy użytkowo znak końcówki warning rollout-08.',
                'notes' => 'Rollout-08: strona znaku A-32.',
            ],
            [
                'primary_query' => 'a-33 zator drogowy',
                'sign_slug' => 'a-33-zator-drogowy',
                'mapped_title' => 'A-33 Zator drogowy',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Akcentować przewidywanie końca kolumny pojazdów, bezpieczny odstęp i ryzyko zbyt szybkiego dojazdu do zatoru.',
                'correction_notes' => 'Trzymać blisko A-34 i materiału A-31 do A-34.',
                'competitor_notes' => 'Mocny, bardzo praktyczny query pod codzienną jazdę w ruchu miejskim i trasowym.',
                'watch_reason' => null,
                'notes' => 'Rollout-08: strona znaku A-33.',
            ],
            [
                'primary_query' => 'a-34 wypadek drogowy',
                'sign_slug' => 'a-34-wypadek-drogowy',
                'mapped_title' => 'A-34 Wypadek drogowy',
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'source_plan' => 'Wyjaśniać znak przez wtórne zagrożenia, obecność służb i konieczność spokojnego, uporządkowanego minięcia miejsca zdarzenia.',
                'correction_notes' => 'Aktualizować wspólnie z A-33 i końcowym porównaniem A-31 do A-34.',
                'competitor_notes' => 'Dobra strona jakościowa pod mocne, konkretne instrukcje zachowania kierowcy przy miejscu zdarzenia.',
                'watch_reason' => 'Finalny anchor page kończący warning cluster 42/42.',
                'notes' => 'Rollout-08: strona znaku A-34.',
            ],
        ];

        $entries = [];

        foreach ($signEntries as $entry) {
            $sign = $signs[$entry['sign_slug']];

            $entries[] = [
                'primary_query' => $entry['primary_query'],
                'mapped_title' => $entry['mapped_title'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => $entry['search_intent'],
                'priority' => $entry['priority'],
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-08',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $warningCategory->getKey(),
                'source_plan' => $entry['source_plan'],
                'correction_notes' => $entry['correction_notes'],
                'competitor_notes' => $entry['competitor_notes'],
                'first_mover_note' => null,
                'watch_reason' => $entry['watch_reason'],
                'notes' => $entry['notes'],
            ];
        }

        return array_merge($entries, [
            [
                'primary_query' => 'a-13 a-19 a-21 różnice',
                'mapped_title' => 'Porównanie A-13, A-19 i A-21',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-08',
                'target_path' => '/znaki-drogowe/porownania/a-13-a-19-a-21',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jedną stroną rozdzielić ruchomy most, boczny wiatr i tramwaj jako trzy różne źródła zagrożenia, które nie mieszczą się w jednym prostym schemacie.',
                'correction_notes' => 'Aktualizować w jednej rewizji z A-13, A-19 i A-21.',
                'competitor_notes' => 'Dobry materiał porządkujący mniej oczywiste warning signs i pokazujący przewagę kompletności klastra.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający pierwszy mini-klaster rollout-08.',
            ],
            [
                'primary_query' => 'a-22 a-23 różnice',
                'mapped_title' => 'Porównanie A-22 i A-23',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-08',
                'target_path' => '/znaki-drogowe/porownania/a-22-vs-a-23',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Porównać zjazd i podjazd przez zachowanie auta, dobór tempa i wcześniejsze przygotowanie kierowcy do nachylenia drogi.',
                'correction_notes' => 'Trzymać ścisłą spójność z A-22 i A-23.',
                'competitor_notes' => 'Bardzo naturalna intencja porównawcza, zwłaszcza dla kursantów i użytkowników szukających prostego rozdzielenia obu znaków.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page dla mini-klastra nachylenia drogi.',
            ],
            [
                'primary_query' => 'a-25 a-26 a-27 a-28 różnice',
                'mapped_title' => 'Porównanie A-25, A-26, A-27 i A-28',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-08',
                'target_path' => '/znaki-drogowe/porownania/a-25-do-a-28',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Uporządkować terenowe warning signs: skały, lotnisko, nabrzeże i sypki żwir jako cztery różne rodzaje zagrożeń otoczenia drogi.',
                'correction_notes' => 'Aktualizować razem z A-25, A-26, A-27 i A-28 oraz pilnować spójnego tonu praktycznego.',
                'competitor_notes' => 'Silny materiał wspierający dla końcówki warning catalog, gdzie przewagą jest kompletność i sensowne grupowanie znaków.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page domykający mini-klaster terenowy rollout-08.',
            ],
            [
                'primary_query' => 'a-31 a-32 a-33 a-34 różnice',
                'mapped_title' => 'Porównanie A-31, A-32, A-33 i A-34',
                'target_type' => TrafficSignQueryMapEntry::TARGET_SUPPORTING,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-08',
                'target_path' => '/znaki-drogowe/porownania/a-31-do-a-34',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => null,
                'source_plan' => 'Jednym materiałem rozdzielić ostrzeżenia o poboczu, oszronieniu, zatorze i wypadku jako czterech różnych sytuacjach końcowego odcinka warning catalog.',
                'correction_notes' => 'Utrzymywać w jednej rewizji z A-31, A-32, A-33 i A-34.',
                'competitor_notes' => 'Dobra strona wspierająca do spięcia końcówki warning cluster i do pokazania szerokiego, ale uporządkowanego coverage.',
                'first_mover_note' => null,
                'watch_reason' => null,
                'notes' => 'Supporting page finalizujący rollout-08 i cały warning cluster.',
            ],
        ]);
    }

    protected function upsertSign(
        ContentAuthor $author,
        TrafficSignCategory $category,
        array $data,
        $publishedAt,
    ): TrafficSign {
        $freshnessReviewDueAt = now()->addMonths(6);

        $sign = TrafficSign::query()->firstOrNew([
            'code' => $data['code'],
        ]);

        $sign->fill([
            'content_author_id' => $author->getKey(),
            'traffic_sign_category_id' => $category->getKey(),
            'code' => $data['code'],
            'slug' => $data['slug'],
            'name' => $data['name'],
            'intro_definition' => $data['intro_definition'],
            'meaning' => $data['meaning'],
            'placement' => $data['placement'],
            'driver_behavior' => $data['driver_behavior'],
            'legal_summary' => $data['legal_summary'],
            'legal_reference_label' => 'Rozporządzenie w sprawie znaków i sygnałów drogowych',
            'legal_reference_url' => 'https://isap.sejm.gov.pl/',
            'fine_summary' => $data['fine_summary'],
            'common_mistakes' => $data['common_mistakes'],
            'source_notes' => $data['source_notes'],
            'editorial_notes' => $data['editorial_notes'],
            'review_notes' => $data['review_notes'],
            'faq_items' => $data['faq_items'],
            'meta_title' => $data['meta_title'] ?? "{$data['code']} {$data['name']} - znaczenie, przepisy i zachowanie kierowcy",
            'meta_description' => $data['meta_description'] ?? "Sprawdź, co oznacza znak {$data['code']} {$data['name']}, gdzie występuje i jak powinien zachować się kierowca.",
            'image_path' => $data['image_path'],
            'image_alt' => $data['image_alt'] ?? "Znak {$data['code']} {$data['name']}",
            'image_width' => $data['image_width'] ?? 320,
            'image_height' => $data['image_height'] ?? 320,
            'og_image_path' => $data['og_image_path'],
            'og_image_alt' => $data['og_image_alt'] ?? "Grafika OG dla znaku {$data['code']} {$data['name']}",
            'og_image_width' => $data['og_image_width'] ?? 1200,
            'og_image_height' => $data['og_image_height'] ?? 630,
            'sort_order' => $data['sort_order'],
            'workflow_status' => TrafficSign::WORKFLOW_PUBLISHED,
            'reviewed_at' => $publishedAt,
            'source_checked_at' => $publishedAt,
            'freshness_review_due_at' => $freshnessReviewDueAt,
            'is_published' => true,
            'published_at' => $publishedAt,
        ]);

        $sign->save();

        return $sign;
    }

    protected function cleanupLegacyRenamedProhibitionQueries(): void
    {
        foreach ($this->legacyRenamedProhibitionQueries() as $legacyQuery) {
            TrafficSignQueryMapEntry::query()
                ->where('primary_query', $legacyQuery)
                ->delete();
        }
    }

    protected function cleanupLegacyInformationalData(): void
    {
        TrafficSignQueryMapEntry::query()
            ->where('batch_label', 'rollout-10-informational')
            ->delete();

        $obsoleteSignIds = TrafficSign::query()
            ->whereIn('code', $this->obsoleteInformationalCodes())
            ->pluck('id');

        if ($obsoleteSignIds->isNotEmpty()) {
            TrafficSignQueryMapEntry::query()
                ->whereIn('traffic_sign_id', $obsoleteSignIds)
                ->delete();
        }

        TrafficSign::query()
            ->whereIn('code', $this->obsoleteInformationalCodes())
            ->delete();
    }

    protected function cleanupLegacySignalData(): void
    {
        $obsoleteSignIds = TrafficSign::query()
            ->whereIn('code', $this->obsoleteSignalCodes())
            ->pluck('id');

        if ($obsoleteSignIds->isNotEmpty()) {
            TrafficSignQueryMapEntry::query()
                ->whereIn('traffic_sign_id', $obsoleteSignIds)
                ->delete();
        }

        TrafficSignQueryMapEntry::query()
            ->whereIn('primary_query', $this->obsoleteSignalQueries())
            ->orWhereIn('target_path', $this->obsoleteSignalPaths())
            ->delete();

        TrafficSign::query()
            ->whereIn('code', $this->obsoleteSignalCodes())
            ->delete();
    }

    /**
     * @return list<string>
     */
    protected function legacyRenamedProhibitionQueries(): array
    {
        return [
            'b-11 zakaz wjazdu wózków rowerowych',
            'b-12 zakaz wjazdu wózków ręcznych',
            'b-32 stój kontrola celna',
        ];
    }

    /**
     * @return list<string>
     */
    protected function obsoleteInformationalCodes(): array
    {
        return [
            'D-13',
            'D-13b',
            'D-51aa',
        ];
    }

    /**
     * @return list<string>
     */
    protected function obsoleteSignalCodes(): array
    {
        return [
            'S-2a',
            'S-2b',
            'S-3',
        ];
    }

    /**
     * @return list<string>
     */
    protected function obsoleteSignalQueries(): array
    {
        return [
            's-2 zielona strzałka warunkowa',
            's-2a zielona strzałka w prawo',
            's-2b zielona strzałka w lewo',
            's-3 sygnalizator kierunkowy',
        ];
    }

    /**
     * @return list<string>
     */
    protected function obsoleteSignalPaths(): array
    {
        return [
            '/znaki-drogowe/s-2a-sygnal-dopuszczajacy-skret-w-prawo',
            '/znaki-drogowe/s-2b-sygnal-dopuszczajacy-skret-w-lewo',
            '/znaki-drogowe/s-3-sygnalizator-kierunkowy',
        ];
    }

    protected function prohibitionAssetPath(string $slug): string
    {
        return 'traffic-signs/signs/prohibitions/znak-'.$slug.'.webp';
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedDirectionBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishDirectionSignCatalog $polishDirectionSignCatalog,
        PolishDirectionSignContentBuilder $polishDirectionSignContentBuilder,
    ): void {
        foreach ($polishDirectionSignCatalog->all() as $index => $signData) {
            $content = array_merge(
                $polishDirectionSignContentBuilder->build($signData, $index),
                [
                    'source_notes' => "Rollout-11 obejmuje pierwszy pełny publiczny batch znaków kierunku. Strona {$signData['code']} ma domykać podstawowy coverage kategorii bez zostawiania pojedynczych znaków poza katalogiem.",
                    'editorial_notes' => "Przy kolejnym passie rozbudować {$signData['code']} o relacje do najbliższych tablic informacyjnych oraz o przykłady sytuacyjne ze skrzyżowań.",
                    'review_notes' => 'Publikacja w rollout-11 jako część pierwszej kompletnej publicznej odsłony znaków kierunku z assetem WebP i spójnym graph image dla rich resultów.',
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function directionQueryMapEntries(
        TrafficSignCategory $directionCategory,
        array $signs,
        PolishDirectionSignCatalog $polishDirectionSignCatalog,
    ): array {
        $entries = [
            [
                'primary_query' => 'znaki kierunku i miejscowości',
                'mapped_title' => 'Kategoria Znaki kierunku i miejscowości',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-11-directions',
                'target_path' => '/znaki-drogowe/kategorie/znaki-kierunku-i-miejscowosci',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $directionCategory->getKey(),
                'source_plan' => 'Kategoria ma prowadzić użytkownika od tablic przeddrogowskazowych i drogowskazów w stronę orientacji i nawigacji.',
                'correction_notes' => 'Rozbudowywać razem z nowymi materiałami wspierającymi dla rodziny E.',
                'competitor_notes' => 'Przewaga tej kategorii polega na uwydatnieniu różnic kolorystycznych znaków E.',
                'first_mover_note' => null,
                'watch_reason' => 'Pierwsza kompletna publiczna odsłona kategorii znaków kierunku i miejscowości.',
                'notes' => 'Category page opublikowana jako wejście do pełnego rollout-11 dla znaków E.',
            ],
        ];

        foreach ($polishDirectionSignCatalog->all() as $signData) {
            $sign = $signs[$signData['slug']] ?? null;

            if (! $sign instanceof TrafficSign) {
                continue;
            }

            $entries[] = [
                'primary_query' => $signData['primary_query'],
                'mapped_title' => $signData['code'].' '.$signData['name'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-11-directions',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $directionCategory->getKey(),
                'source_plan' => "Stronę {$signData['code']} oprzeć o oficjalne znaczenie znaku oraz nawigacyjne zachowanie kierowcy w obszarze skrzyżowań.",
                'correction_notes' => "Przy następnej rewizji rozbudować {$signData['code']} o przykłady kolorystyki autostradowej i ekspresowej.",
                'competitor_notes' => "Treść {$signData['code']} stawia nacisk na wczesne czytanie informacji i unikanie gwałtownych manewrów.",
                'first_mover_note' => null,
                'watch_reason' => "Element pełnego publicznego coverage kategorii E dla znaku {$signData['code']}.",
                'notes' => "Rollout-11: strona znaku {$signData['code']}.",
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedComplementaryBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishComplementarySignCatalog $polishComplementarySignCatalog,
        PolishComplementarySignContentBuilder $polishComplementarySignContentBuilder,
    ): void {
        foreach ($polishComplementarySignCatalog->all() as $index => $signData) {
            $content = array_merge(
                $polishComplementarySignContentBuilder->build($signData, $index),
                [
                    'source_notes' => "Rollout-12 obejmuje batch znaków uzupełniających. Strona {$signData['code']} wspiera organizację ruchu i ułatwia poruszanie się po węzłach.",
                    'editorial_notes' => 'Dopisać wkrótce materiał edukacyjny o błędach na wielopasmowych skrzyżowaniach.',
                    'review_notes' => 'Publikacja z rollout-12 (Znaki F) pod SEO.',
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function complementaryQueryMapEntries(
        TrafficSignCategory $complementaryCategory,
        array $signs,
        PolishComplementarySignCatalog $polishComplementarySignCatalog,
    ): array {
        $entries = [
            [
                'primary_query' => 'znaki uzupełniające',
                'mapped_title' => 'Kategoria Znaki uzupełniające',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-12-complementary',
                'target_path' => '/znaki-drogowe/kategorie/znaki-uzupelniajace',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $complementaryCategory->getKey(),
                'source_plan' => 'Kategoria ma skupiać się na organizacji ruchu i wyznaczonych pasach.',
                'correction_notes' => 'Uzupełnić z materiałami egzaminacyjnymi.',
                'competitor_notes' => 'Wyróżnienie się poprzez lepsze omówienie F-10 i F-11.',
                'first_mover_note' => null,
                'watch_reason' => 'Wprowadzenie znaków F do portalu.',
                'notes' => 'Category page opublikowana w rollout-12 dla znaków F.',
            ],
        ];

        foreach ($polishComplementarySignCatalog->all() as $signData) {
            $sign = $signs[$signData['slug']] ?? null;

            if (! $sign instanceof TrafficSign) {
                continue;
            }

            $entries[] = [
                'primary_query' => $signData['primary_query'],
                'mapped_title' => $signData['code'].' '.$signData['name'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-12-complementary',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $complementaryCategory->getKey(),
                'source_plan' => "Strona dedykowana prawidłowej interpretacji znaku {$signData['code']}.",
                'correction_notes' => 'Dodać sytuacje błędne przy najbliższej okazji.',
                'competitor_notes' => "Treść {$signData['code']} wsparta dobrym zachowaniem kierowcy na pasach.",
                'first_mover_note' => null,
                'watch_reason' => "Cześć wdrożenia grupy F dla znaku {$signData['code']}.",
                'notes' => "Rollout-12: strona znaku {$signData['code']}.",
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedPlateBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishPlateSignCatalog $polishPlateSignCatalog,
        PolishPlateSignContentBuilder $polishPlateSignContentBuilder,
    ): void {
        foreach ($polishPlateSignCatalog->all() as $index => $signData) {
            $content = array_merge(
                $polishPlateSignContentBuilder->build($signData, $index),
                [
                    'source_notes' => 'Rollout-13 obejmuje podstawowe tabliczki modyfikujące znaki (T). Zawsze występują łącznie z innym znakiem.',
                    'editorial_notes' => 'Dodać z czasem grafiki łączone (znak bazowy + tabliczka).',
                    'review_notes' => 'Publikacja z rollout-13 (Znaki T) pod SEO.',
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function plateQueryMapEntries(
        TrafficSignCategory $plateCategory,
        array $signs,
        PolishPlateSignCatalog $polishPlateSignCatalog,
    ): array {
        $entries = [
            [
                'primary_query' => 'tabliczki do znaków',
                'mapped_title' => 'Kategoria Tabliczki do znaków drogowych',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-13-plates',
                'target_path' => '/znaki-drogowe/kategorie/tabliczki-do-znakow',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $plateCategory->getKey(),
                'source_plan' => 'Kategoria dedykowana modyfikatorom (tabliczkom).',
                'correction_notes' => 'Wyjaśnić różnicę między tabliczką a głównym znakiem.',
                'competitor_notes' => 'T-24 (laweta) i T-6a (pierwszeństwo) jako największe generatory ruchu.',
                'first_mover_note' => null,
                'watch_reason' => 'Wprowadzenie znaków T do portalu.',
                'notes' => 'Category page opublikowana w rollout-13 dla znaków T.',
            ],
        ];

        foreach ($polishPlateSignCatalog->all() as $signData) {
            $sign = $signs[$signData['slug']] ?? null;

            if (! $sign instanceof TrafficSign) {
                continue;
            }

            $entries[] = [
                'primary_query' => $signData['primary_query'],
                'mapped_title' => $signData['code'].' '.$signData['name'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-13-plates',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $plateCategory->getKey(),
                'source_plan' => "Strona objaśniająca sposób interakcji tabliczki {$signData['code']} ze znakiem głównym.",
                'correction_notes' => 'Dodać przykładowe kombosy przy rewizji.',
                'competitor_notes' => "Treść {$signData['code']} powinna jasno rozgraniczać, co robi sam znak, a co tabliczka.",
                'first_mover_note' => null,
                'watch_reason' => "Cześć wdrożenia grupy T dla znaku {$signData['code']}.",
                'notes' => "Rollout-13: strona znaku {$signData['code']}.",
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedRailwayBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishRailwaySignCatalog $polishRailwaySignCatalog,
        PolishRailwaySignContentBuilder $polishRailwaySignContentBuilder,
    ): void {
        foreach ($polishRailwaySignCatalog->all() as $index => $signData) {
            $content = array_merge(
                $polishRailwaySignContentBuilder->build($signData, $index),
                [
                    'source_notes' => "Rollout-14 dodaje znaki z grupy G (kolejowe). Strona {$signData['code']} jest kluczowa dla bezpieczeństwa.",
                    'editorial_notes' => 'Opisać dokładnie różnice w ilości torów dla G-3 i G-4.',
                    'review_notes' => 'Publikacja z rollout-14 (Znaki G) pod SEO.',
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function railwayQueryMapEntries(
        TrafficSignCategory $railwayCategory,
        array $signs,
        PolishRailwaySignCatalog $polishRailwaySignCatalog,
    ): array {
        $entries = [
            [
                'primary_query' => 'dodatkowe znaki przed przejazdami kolejowymi',
                'mapped_title' => 'Kategoria Dodatkowe znaki przed przejazdami kolejowymi',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-14-railway',
                'target_path' => '/znaki-drogowe/kategorie/znaki-przed-przejazdami-kolejowymi',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $railwayCategory->getKey(),
                'source_plan' => 'Kategoria dedykowana przejazdom kolejowym i słupkom wskaźnikowym.',
                'correction_notes' => 'Uzbroić w zdjęcia połączone z A-9 i A-10.',
                'competitor_notes' => 'Krzyże św. Andrzeja to bardzo popularne zapytania wśród kursantów.',
                'first_mover_note' => null,
                'watch_reason' => 'Wprowadzenie znaków G do portalu.',
                'notes' => 'Category page opublikowana w rollout-14 dla znaków G.',
            ],
        ];

        foreach ($polishRailwaySignCatalog->all() as $signData) {
            $sign = $signs[$signData['slug']] ?? null;

            if (! $sign instanceof TrafficSign) {
                continue;
            }

            $entries[] = [
                'primary_query' => $signData['primary_query'],
                'mapped_title' => $signData['code'].' '.$signData['name'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-14-railway',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $railwayCategory->getKey(),
                'source_plan' => "Strona objaśniająca znak {$signData['code']} na przejeździe.",
                'correction_notes' => 'Dodać zasady zatrzymania się przed torami.',
                'competitor_notes' => "Treść {$signData['code']} stawia nacisk na śmiertelne zagrożenia i punkty karne.",
                'first_mover_note' => null,
                'watch_reason' => "Cześć wdrożenia grupy G dla znaku {$signData['code']}.",
                'notes' => "Rollout-14: strona znaku {$signData['code']}.",
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedHorizontalBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishHorizontalSignCatalog $polishHorizontalSignCatalog,
        PolishHorizontalSignContentBuilder $polishHorizontalSignContentBuilder,
    ): void {
        foreach ($polishHorizontalSignCatalog->all() as $index => $signData) {
            $content = array_merge(
                $polishHorizontalSignContentBuilder->build($signData, $index),
                [
                    'source_notes' => 'Rollout-15 wprowadza kluczowe znaki poziome (P).',
                    'editorial_notes' => 'Dodać z czasem artykuły łączące podwójną ciągłą z bezpiecznym wyprzedzaniem.',
                    'review_notes' => 'Publikacja z rollout-15 (Znaki poziome) pod SEO.',
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function horizontalQueryMapEntries(
        TrafficSignCategory $horizontalCategory,
        array $signs,
        PolishHorizontalSignCatalog $polishHorizontalSignCatalog,
    ): array {
        $entries = [
            [
                'primary_query' => 'znaki drogowe poziome',
                'mapped_title' => 'Kategoria Znaki drogowe poziome',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-15-horizontal',
                'target_path' => '/znaki-drogowe/kategorie/znaki-drogowe-poziome',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $horizontalCategory->getKey(),
                'source_plan' => 'Kategoria dedykowana pasom ruchu i znakom malowanym.',
                'correction_notes' => 'Wyjaśnić różnicę w mandatach pomiędzy najechaniem a przekroczeniem.',
                'competitor_notes' => 'Podwójna ciągła i linie zatrzymania to must-have dla kursantów.',
                'first_mover_note' => null,
                'watch_reason' => 'Wprowadzenie znaków P do portalu.',
                'notes' => 'Category page opublikowana w rollout-15 dla znaków P.',
            ],
        ];

        foreach ($polishHorizontalSignCatalog->all() as $signData) {
            $sign = $signs[$signData['slug']] ?? null;

            if (! $sign instanceof TrafficSign) {
                continue;
            }

            $entries[] = [
                'primary_query' => $signData['primary_query'],
                'mapped_title' => $signData['code'].' '.$signData['name'],
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-15-horizontal',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $horizontalCategory->getKey(),
                'source_plan' => "Strona objaśniająca znak poziomy {$signData['code']} na jezdni.",
                'correction_notes' => 'Dodać zasady dotyczące najeżdżania vs przekraczania linii.',
                'competitor_notes' => "Treść {$signData['code']} powinna jasno tłumaczyć co wolno a czego nie w kontekście zmiany pasa.",
                'first_mover_note' => null,
                'watch_reason' => "Cześć wdrożenia grupy P dla znaku {$signData['code']}.",
                'notes' => "Rollout-15: strona znaku poziomego {$signData['code']}.",
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     */
    protected function seedPublishedSignalBatch(
        ContentAuthor $author,
        TrafficSignCategory $category,
        $publishedAt,
        array &$signs,
        PolishSignalSignCatalog $polishSignalSignCatalog,
        PolishSignalSignContentBuilder $polishSignalSignContentBuilder,
    ): void {
        foreach ($polishSignalSignCatalog->all() as $index => $signData) {
            $content = array_merge(
                $polishSignalSignContentBuilder->build($signData, $index),
                [
                    'source_notes' => 'Rollout-16 dotyczy sygnałów świetlnych (grupa S).',
                    'editorial_notes' => 'S-2 (zielona strzałka) powinno mieć wyraźny alert o konieczności zatrzymania.',
                    'review_notes' => 'Publikacja z rollout-16 (Sygnały świetlne) pod SEO.',
                ],
            );

            $signs[$signData['slug']] = $this->upsertSign(
                author: $author,
                category: $category,
                data: array_merge($signData, $content),
                publishedAt: $publishedAt,
            );
        }
    }

    /**
     * @param  array<string, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    protected function signalQueryMapEntries(
        TrafficSignCategory $signalCategory,
        array $signs,
        PolishSignalSignCatalog $polishSignalSignCatalog,
    ): array {
        $entries = [
            [
                'primary_query' => 'sygnały świetlne',
                'mapped_title' => 'Kategoria Sygnały świetlne',
                'target_type' => TrafficSignQueryMapEntry::TARGET_CATEGORY,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-16-signals',
                'target_path' => '/znaki-drogowe/kategorie/sygnaly-swietlne',
                'traffic_sign_id' => null,
                'traffic_sign_category_id' => $signalCategory->getKey(),
                'source_plan' => 'Kategoria nadrzędna dla klasycznych sygnalizatorów.',
                'correction_notes' => 'Wyjaśnić hierarchię znaków vs światła.',
                'competitor_notes' => 'Zielona strzałka to jedno z najczęstszych pytań.',
                'first_mover_note' => null,
                'watch_reason' => 'Wprowadzenie sygnałów S do portalu.',
                'notes' => 'Category page opublikowana w rollout-16 dla sygnałów S.',
            ],
        ];

        foreach ($polishSignalSignCatalog->all() as $signData) {
            $sign = $signs[$signData['slug']] ?? null;

            if (! $sign instanceof TrafficSign) {
                continue;
            }

            $publicCode = $sign->publicCode();

            $entries[] = [
                'primary_query' => $signData['primary_query'],
                'mapped_title' => $sign->publicTitle(),
                'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                'rollout_status' => TrafficSignQueryMapEntry::STATUS_PUBLISHED,
                'batch_label' => 'rollout-16-signals',
                'target_path' => '/znaki-drogowe/'.$sign->slug,
                'traffic_sign_id' => $sign->getKey(),
                'traffic_sign_category_id' => $signalCategory->getKey(),
                'source_plan' => "Strona objaśniająca sygnalizator {$publicCode}.",
                'correction_notes' => 'Zestawić ewentualnie z wyłączeniami prądu (co się dzieje gdy mruga żółte).',
                'competitor_notes' => "Treść {$publicCode} musi być prosta i bazować na bezwzględności tych sygnałów.",
                'first_mover_note' => null,
                'watch_reason' => "Część wdrożenia grupy S dla znaku {$publicCode}.",
                'notes' => "Rollout-16: strona sygnalizatora {$publicCode}.",
            ];
        }

        return $entries;
    }
}
