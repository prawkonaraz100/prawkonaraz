<?php

namespace Database\Seeders;

use App\Models\ContentAuthor;
use App\Models\QuestionPublicExplanation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PublicQuestionExplanationSeeder extends Seeder
{
    public function run(): void
    {
        $reviewedAt = Carbon::parse('2026-06-15');
        $publishedAt = Carbon::parse('2026-06-15 12:00:00');
        $reviewer = ContentAuthor::query()
            ->where('slug', 'jakub-wisniewski')
            ->first();

        $definitions = [
            '99' => [
                'body' => 'Tak, w tej sytuacji kierujący powinien zatrzymać pojazd, ponieważ najważniejsze jest bezpieczne wyjście pasażerów z tramwaju. Gdy przystanek nie oddziela pasażerów od jezdni wysepką albo przejście do tramwaju prowadzi przez tor jazdy samochodów, piesi mogą pojawić się bezpośrednio przed pojazdem. Kierowca nie powinien próbować przejeżdżać obok tramwaju ani omijać pasażerów na siłę. Zatrzymanie przed wyznaczoną linią lub w bezpiecznym miejscu daje pasażerom czas na spokojne opuszczenie tramwaju i wejście na chodnik. W praktyce chodzi o prostą zasadę: tramwaj i wysiadający pasażerowie wymagają od kierowcy szczególnej ostrożności oraz gotowości do pełnego zatrzymania.',
                'exam_trap' => 'W tym pytaniu nie chodzi tylko o obecność tramwaju. Kluczowe jest to, że tramwaj znajduje się na oznaczonym przystanku bez wysepki dla pasażerów. W takiej sytuacji kierujący musi zatrzymać pojazd, a nie tylko zwolnić.',
                'common_mistakes' => [
                    [
                        'title' => 'Wystarczy zwolnić i ostrożnie przejechać obok tramwaju.',
                        'explanation' => 'Samo zmniejszenie prędkości nie wystarcza. Gdy przystanek nie ma wysepki dla pasażerów, kierujący musi zatrzymać pojazd, aby zapewnić pieszym swobodny dostęp do tramwaju lub chodnika.',
                    ],
                    [
                        'title' => 'Zatrzymanie jest konieczne tylko przy przejściu dla pieszych.',
                        'explanation' => 'Ten obowiązek wynika z obsługi przystanku tramwajowego bez wysepki, a nie z obecności przejścia dla pieszych. Trzeba go wykonać również wtedy, gdy na jezdni nie ma oznakowanego przejścia.',
                    ],
                    [
                        'title' => 'Można ruszyć od razu po zamknięciu drzwi tramwaju.',
                        'explanation' => 'Zamknięcie drzwi nie przesądza jeszcze, że można bezpiecznie jechać. Najpierw trzeba upewnić się, że pasażerowie mają swobodny dostęp do chodnika, a dalsza jazda nikomu nie zagraża.',
                    ],
                ],
                'source_note' => 'Opracowanie własne na podstawie zweryfikowanej relacji pytania 99 z art. 26 ust. 6 ustawy Prawo o ruchu drogowym.',
                'internal_note' => 'Pierwszy golden sample publicznej warstwy Omówienie sytuacji. Nie wgrywać do questions.explanation.',
            ],
            '10314' => [
                'body' => 'Tak, w pokazanej sytuacji trzeba umożliwić pasażerom bezpieczne opuszczenie tramwaju. Kluczowe jest to, że tramwaj zatrzymał się przy przystanku, a pasażerowie mogą przechodzić przez część drogi, po której poruszają się inne pojazdy. Kierowca nie powinien traktować tego jak zwykłego postoju pojazdu szynowego i próbować przejechać obok. Zatrzymanie daje pieszym czas na wyjście z wagonu, wejście na chodnik i uniknięcie kontaktu z jadącymi pojazdami. W pytaniu liczy się więc rozpoznanie przystanku bez bezpiecznego odseparowania pasażerów od jezdni oraz przyjęcie zasady, że ochrona osób wysiadających ma pierwszeństwo przed kontynuowaniem jazdy.',
                'source_note' => 'Opracowanie własne na podstawie pytania 10314 oraz relacji z tematem tramwaje-i-przystanki.',
            ],
            '10249' => [
                'body' => 'Tak, kierujący powinien ustąpić pierwszeństwa pieszemu, bo sytuacja dotyczy rejonu przejścia dla pieszych. Znak i oznakowanie nie są tylko informacją o miejscu na drodze, ale sygnałem, że trzeba wcześniej obserwować otoczenie, zmniejszyć prędkość i przygotować się do zatrzymania. Jeżeli pieszy znajduje się przy przejściu albo wchodzi na nie w sposób widoczny dla kierowcy, dalsza jazda nie może wymuszać na nim zatrzymania się lub cofnięcia. Publiczne wyjaśnienie nie sprowadza się więc do samej odpowiedzi "tak"; chodzi o ocenę całej sceny: znaku, przejścia, zachowania pieszego i tego, czy kierowca ma realnie czas zareagować bez gwałtownego manewru.',
                'source_note' => 'Opracowanie własne na podstawie pytania 10249 oraz relacji z tematem piesi-i-przejscia.',
            ],
            '10369' => [
                'body' => 'Tak, skręcając w prawo do bramy kierujący musi uwzględnić pieszego, którego tor ruchu przecina. Ten manewr wygląda inaczej niż zwykła jazda pasem na wprost: samochód zjeżdża z jezdni i przejeżdża przez przestrzeń, po której może poruszać się pieszy. Dlatego przed skrętem trzeba zwolnić, sprawdzić prawą stronę i upewnić się, że pieszy nie zostanie zmuszony do zatrzymania się. Poprawna odpowiedź wynika z praktycznej zasady ostrożności przy przecinaniu drogi pieszego. Nawet jeśli brama lub wjazd znajduje się blisko jezdni, kierujący nie może traktować tego miejsca jak własnego pierwszeństwa nad osobą idącą chodnikiem albo drogą dla pieszych.',
                'source_note' => 'Opracowanie własne na podstawie pytania 10369 oraz relacji z tematem piesi-i-przejscia.',
            ],
            '10474' => [
                'body' => 'Tak, szczególną ostrożność trzeba zachować już przy zbliżaniu się do przejścia dla pieszych, nawet gdy w danej chwili nie widać osoby wchodzącej na jezdnię. Przejście jest miejscem, w którym sytuacja może zmienić się bardzo szybko: pieszy może pojawić się zza przeszkody, z chodnika, z pobocza albo z martwego pola widzenia. Odpowiedź nie zależy więc wyłącznie od tego, czy na pierwszym kadrze widać pieszego. Kierowca ma rozpoznać oznakowanie i przygotować prędkość oraz obserwację do możliwego zatrzymania. Właśnie dlatego prawidłowe zachowanie polega na wcześniejszym skupieniu uwagi, a nie dopiero na reakcji w ostatnim momencie.',
                'source_note' => 'Opracowanie własne na podstawie pytania 10474 oraz relacji z tematem piesi-i-przejscia.',
            ],
            '10154' => [
                'body' => 'Nie, w tej sytuacji nie wystarczy mechanicznie zastosować zasady prawej strony. Na skrzyżowaniu o pierwszeństwie mogą decydować znaki, przebieg drogi z pierwszeństwem albo konkretna organizacja ruchu widoczna w nagraniu. Jeżeli z oznakowania wynika, że pojazd nadjeżdżający z prawej nie ma pierwszeństwa przed kierującym, nie trzeba mu ustępować tylko dlatego, że pojawia się po prawej stronie. To pytanie sprawdza umiejętność połączenia obserwacji skrzyżowania z zasadą pierwszeństwa, a nie zapamiętania jednego skrótu myślowego. Bezpieczna odpowiedź wymaga więc spojrzenia na znaki i układ drogi przed oceną, kto powinien przejechać pierwszy.',
                'source_note' => 'Opracowanie własne na podstawie pytania 10154 oraz relacji z tematem pierwszenstwo-przejazdu.',
            ],
            '10247' => [
                'body' => 'Nie, skręcając w lewo kierujący przecina tor jazdy pojazdu jadącego z przeciwka na wprost. W typowej sytuacji taki pojazd ma pierwszeństwo, a kierowca wykonujący skręt powinien poczekać, aż manewr będzie bezpieczny. Ważne jest, że samo włączenie kierunkowskazu i zajęcie właściwego miejsca na jezdni nie daje pierwszeństwa przed ruchem z przeciwnego kierunku. Pytanie sprawdza rozpoznanie kolizyjnych torów jazdy na skrzyżowaniu: jeden pojazd jedzie prosto, drugi zmienia kierunek i przecina jego drogę. Dlatego poprawną decyzją jest ustąpienie, a nie rozpoczęcie skrętu z założeniem, że inni kierujący zwolnią lub zatrzymają się za nas.',
                'source_note' => 'Opracowanie własne na podstawie pytania 10247 oraz relacji z tematem pierwszenstwo-przejazdu.',
            ],
            '10107' => [
                'body' => 'Tak, postawa policjanta kierującego ruchem pozwala w tej sytuacji kontynuować jazdę i wykonać skręt w lewo, o ile sam manewr można przeprowadzić bezpiecznie. Przy osobie kierującej ruchem najważniejsze jest odczytanie jej ustawienia i gestów, ponieważ takie polecenia porządkują ruch na skrzyżowaniu niezależnie od zwykłego odruchu patrzenia tylko na światła lub znaki. Kierowca powinien jednak nadal obserwować tor jazdy, pieszych i inne pojazdy. Zezwolenie na wjazd nie zwalnia z ostrożności podczas skrętu. Pytanie sprawdza więc dwie rzeczy naraz: rozpoznanie sygnału dawanego przez policjanta oraz wykonanie manewru dopiero wtedy, gdy nie stworzy to zagrożenia.',
                'source_note' => 'Opracowanie własne na podstawie pytania 10107 oraz relacji z tematem sygnalizacja-i-osoby-kierujace-ruchem.',
            ],
            '469' => [
                'body' => 'Tak, jednoczesny sygnał czerwony i żółty oznacza, że za chwilę powinien pojawić się sygnał zielony. Nie jest to jednak pozwolenie na ruszenie natychmiast po zauważeniu żółtego światła. Kierowca ma przygotować się do jazdy, obserwować skrzyżowanie i poczekać na zielony sygnał. W pytaniu chodzi o przewidywanie kolejności sygnałów, a nie o samo zachowanie pedałów czy sprzęgła. Odpowiedź "tak" oznacza więc, że następnym etapem pracy sygnalizatora będzie zielone światło, ale decyzja o ruszeniu nadal musi nastąpić dopiero wtedy, gdy sygnał rzeczywiście na to pozwala i sytuacja na skrzyżowaniu jest bezpieczna.',
                'source_note' => 'Opracowanie własne na podstawie pytania 469 oraz relacji z tematem sygnalizacja-i-osoby-kierujace-ruchem.',
            ],
            '10237' => [
                'body' => 'Nie, w pokazanym miejscu zatrzymanie pojazdu na poboczu nie jest dopuszczalne. Kluczowa jest linia ciągła wyznaczająca krawędź jezdni: kierujący nie powinien traktować pobocza za taką linią jako zwykłego, bezpiecznego miejsca postoju. Zatrzymanie w takim miejscu mogłoby utrudnić ruch, ograniczyć czytelność sytuacji dla innych kierujących albo zmusić ich do niebezpiecznej reakcji. Pytanie wymaga odróżnienia pobocza, na którym czasem można się zatrzymać, od pobocza objętego zakazem wynikającym z oznakowania i układu drogi. Poprawna odpowiedź to "nie", bo widoczna organizacja ruchu wyłącza możliwość swobodnego zatrzymania pojazdu w tym miejscu.',
                'source_note' => 'Opracowanie własne na podstawie pytania 10237 oraz relacji z tematem zatrzymanie-i-postoj.',
            ],
            '13562' => [
                'body' => 'Tak, prędkość trzeba dostosować do widoczności drogi, a nie tylko do liczby widocznej na znaku lub ogólnego limitu. Limit określa najwyższą dopuszczalną prędkość, ale nie oznacza, że zawsze wolno jechać z taką samą szybkością. Jeżeli widoczność jest ograniczona, droga jest kręta, mokra, nieoświetlona albo zasłonięta przez przeszkody, kierowca powinien zwolnić tak, aby zachować panowanie nad pojazdem i móc zareagować na zagrożenie. W tym pytaniu poprawna odpowiedź wskazuje na praktyczną ocenę warunków. Bezpieczna prędkość może być niższa niż dopuszczalna, natomiast nigdy nie usprawiedliwia przekroczenia obowiązującego limitu.',
                'source_note' => 'Opracowanie własne na podstawie pytania 13562 oraz relacji z tematem predkosc-odstep-i-hamowanie.',
            ],
            '13781' => [
                'body' => 'Nie, na tak oznaczonej drodze nie obowiązuje stały wymóg zachowania dokładnie co najmniej 100 metrów odstępu od pojazdu z przodu. Minimalny odstęp na autostradzie i drodze ekspresowej zależy od aktualnej prędkości, dlatego trzeba go obliczać w relacji do tego, jak szybko jedzie pojazd. W praktyce oznacza to, że przy niższej prędkości wymagany odstęp będzie mniejszy niż 100 metrów, a przy bardzo wysokiej może być większy. Pytanie sprawdza, czy kierowca rozumie zasadę zmiennego odstępu, a nie zapamiętuje jednej liczby. Odpowiedź "nie" jest poprawna, bo 100 metrów nie jest uniwersalną wartością dla każdej sytuacji na takiej drodze.',
                'source_note' => 'Opracowanie własne na podstawie pytania 13781 oraz relacji z tematem predkosc-odstep-i-hamowanie.',
            ],
            '13782' => [
                'body' => 'Nie, samo oznakowanie drogi ekspresowej nie oznacza, że zawsze trzeba utrzymywać co najmniej 100 metrów za poprzedzającym pojazdem. Odstęp jest powiązany z prędkością, więc kierujący powinien umieć przeliczyć zasadę na realną sytuację. Jeżeli pojazd jedzie wolniej, minimalny wymagany dystans może być mniejszy niż 100 metrów, choć nadal musi być bezpieczny. Jeżeli jedzie szybciej, potrzebny dystans rośnie. To pytanie odcina popularny błąd: traktowanie jednej liczby jako odpowiedzi na każdy przypadek. Poprawna odpowiedź brzmi "nie", bo właściwe zachowanie polega na zachowaniu odstępu wynikającego z prędkości, a nie stałej odległości niezależnej od jazdy.',
                'source_note' => 'Opracowanie własne na podstawie pytania 13782 oraz relacji z tematem predkosc-odstep-i-hamowanie.',
            ],
            '4170' => [
                'body' => 'Prawidłowa odpowiedź to 20 km/h. Strefa zamieszkania jest miejscem, w którym kierowca powinien spodziewać się pieszych korzystających z całej szerokości drogi, dzieci, rowerzystów oraz pojazdów wyjeżdżających z miejsc postojowych. Niski limit nie jest przypadkowy: ma dać kierującemu czas na spokojną reakcję i ograniczyć skutki ewentualnego błędu. Odpowiedzi 30 km/h i 40 km/h są zbyt wysokie dla tej strefy, nawet jeśli droga wygląda szeroko albo ruch wydaje się mały. W pytaniu chodzi o rozpoznanie szczególnego obszaru, a nie o zwykły limit na obszarze zabudowanym. Po wjeździe do strefy zamieszkania trzeba jechać maksymalnie 20 km/h.',
                'source_note' => 'Opracowanie własne na podstawie pytania 4170 oraz relacji z tematem predkosc-odstep-i-hamowanie.',
            ],
            '7237' => [
                'body' => 'W strefie zamieszkania nie możesz poruszać się z prędkością 25 km/h, ponieważ przekracza ona dopuszczalne 20 km/h. Odpowiedzi 10 km/h i 15 km/h mieszczą się poniżej limitu, więc same w sobie nie naruszają zasady prędkości maksymalnej. To pytanie jest podchwytliwe, bo pyta o prędkość, z którą nie wolno jechać, a nie o najwyższą dopuszczalną wartość. Trzeba więc najpierw rozpoznać znak strefy zamieszkania, przypomnieć sobie limit i dopiero potem porównać warianty odpowiedzi. W takiej strefie kierowca powinien dodatkowo jechać szczególnie uważnie, bo piesi mogą korzystać z drogi szerzej niż na zwykłej jezdni.',
                'source_note' => 'Opracowanie własne na podstawie pytania 7237 oraz relacji z tematem predkosc-odstep-i-hamowanie.',
            ],
            '13035' => [
                'body' => 'Nie, prowadząc pojazd silnikowy z przyczepą w strefie zamieszkania nie masz prawa jechać 30 km/h. W tej strefie limit 20 km/h dotyczy pojazdów i zespołów pojazdów, więc przyczepa nie tworzy wyjątku pozwalającego jechać szybciej. Odpowiedź trzeba oprzeć na miejscu, w którym się znajdujesz, a nie na samym rodzaju pojazdu. Strefa zamieszkania zakłada bardzo bliski kontakt różnych uczestników ruchu, dlatego wymaga niskiej prędkości i gotowości do zatrzymania. Warianty sugerujące możliwość jazdy 30 km/h, także warunkowo, są błędne. Poprawna decyzja to traktować 20 km/h jako górną granicę dla całego zestawu.',
                'source_note' => 'Opracowanie własne na podstawie pytania 13035 oraz relacji z tematem predkosc-odstep-i-hamowanie.',
            ],
        ];

        foreach ($definitions as $externalId => $definition) {
            QuestionPublicExplanation::query()->updateOrCreate(
                ['external_id' => $externalId],
                [
                    'question_id' => null,
                    'title' => 'Omówienie sytuacji',
                    'body' => $definition['body'],
                    'dont_confuse_with' => $definition['dont_confuse_with'] ?? null,
                    'exam_trap' => $definition['exam_trap'] ?? null,
                    'common_mistakes' => $definition['common_mistakes'] ?? null,
                    'related_questions' => $definition['related_questions'] ?? null,
                    'status' => QuestionPublicExplanation::STATUS_PUBLISHED,
                    'author_id' => $reviewer?->getKey(),
                    'reviewer_id' => $reviewer?->getKey(),
                    'published_at' => $publishedAt,
                    'last_reviewed_at' => $reviewedAt,
                    'source_note' => $definition['source_note'],
                    'internal_note' => $definition['internal_note']
                        ?? 'Batch 1 publicznych wyjaśnień pytań powiązanych z /przepisy. Nie wgrywać do questions.explanation.',
                ],
            );
        }
    }
}
