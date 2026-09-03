<?php

namespace App\Support;

use App\Models\TrafficSign;

class TrafficSignSupportingPageCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'slug' => 'a-7-vs-b-20',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-7 a B-20 STOP: kiedy trzeba ustąpić, a kiedy bezwzględnie się zatrzymać',
                'title' => 'A-7 a B-20 STOP - najważniejsze różnice dla kierowcy',
                'description' => 'Porównujemy znak A-7 Ustąp pierwszeństwa i B-20 STOP: kiedy wystarczy ustąpić, kiedy trzeba się zatrzymać i gdzie kierowcy najczęściej popełniają błąd.',
                'intro' => 'To jedno z najczęstszych pytań na egzaminie i w codziennej jeździe. A-7 każe ustąpić pierwszeństwa, ale nie zawsze wymaga pełnego zatrzymania. B-20 jest ostrzejszy: tu zatrzymanie pojazdu jest obowiązkowe nawet wtedy, gdy droga wydaje się wolna.',
                'published_at' => '2026-04-27T09:00:00+02:00',
                'updated_at' => '2026-04-27T16:00:00+02:00',
                'related_sign_slugs' => [
                    'a-7-ustap-pierwszenstwa',
                    'b-20-stop',
                ],
                'takeaways' => [
                    'Przy A-7 kluczowe jest ustąpienie pierwszeństwa, a nie samo zatrzymanie za wszelką cenę.',
                    'Przy B-20 kierowca musi zatrzymać pojazd całkowicie, niezależnie od pierwszego wrażenia po dojechaniu do skrzyżowania.',
                    'Na egzaminie i w praktyce najwięcej błędów bierze się z traktowania B-20 jak mocniejszego A-7.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-7 oznacza obowiązek ustąpienia pierwszeństwa. Jeśli sytuacja jest czytelna i nie ma potrzeby pełnego zatrzymania, kierowca może przejechać po odpowiednim zwolnieniu. B-20 STOP wymaga pełnego zatrzymania pojazdu i dopiero potem dalszej oceny sytuacji.',
                    ],
                    [
                        'title' => 'Jak zachować się przy A-7',
                        'body' => 'Do A-7 trzeba dojechać z wyraźnym zdjęciem nogi z gazu i szeroką obserwacją drogi z pierwszeństwem. Jeśli widoczność jest dobra i nie ma pojazdu, któremu trzeba ustąpić, przejazd może odbyć się płynnie. Jeśli sytuacja jest nieczytelna, zatrzymanie staje się naturalnym skutkiem obowiązku ustąpienia.',
                    ],
                    [
                        'title' => 'Jak zachować się przy B-20 STOP',
                        'body' => 'Przy B-20 nie wystarczy mocno zwolnić. Pojazd musi się zatrzymać całkowicie w miejscu wyznaczonym organizacją ruchu albo przed strefą kolizji. Dopiero po zatrzymaniu kierowca przechodzi do obserwacji i decyzji o ruszeniu.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka egzaminacyjna',
                        'body' => 'Kursanci często wiedzą, że oba znaki dotyczą pierwszeństwa, ale mieszają poziom obowiązku. Przy B-20 egzaminator oczekuje wyraźnego zatrzymania. Przy A-7 ocenia przede wszystkim prawidłową obserwację i ustąpienie pierwszeństwa, a nie sztuczne zatrzymanie w każdej sytuacji.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy przy A-7 mogę przejechać bez zatrzymania?',
                        'answer' => 'Tak, ale tylko wtedy, gdy po zwolnieniu i obserwacji możesz bezpiecznie ustąpić pierwszeństwa bez pełnego zatrzymania. Jeśli sytuacja tego wymaga, zatrzymanie jest obowiązkowe.',
                    ],
                    [
                        'question' => 'Czy B-20 zawsze oznacza pełne zatrzymanie?',
                        'answer' => 'Tak. To właśnie odróżnia B-20 od A-7. Znak STOP wymaga pełnego zatrzymania pojazdu przed dalszą jazdą.',
                    ],
                ],
            ],
            [
                'slug' => 'a-5-do-a-8',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-5, A-6 i A-8: jak czytać znaki ostrzegające o układzie skrzyżowania',
                'title' => 'A-5, A-6 i A-8 - jak nie pomylić ostrzeżeń o skrzyżowaniu',
                'description' => 'Porównujemy znaki A-5, A-6a, A-6b, A-6c, A-7 i A-8, żeby szybko rozpoznać, czy znak ostrzega o samym skrzyżowaniu, podporządkowanym wlocie czy ruchu okrężnym.',
                'intro' => 'To jeden z tych mini-klastrów, w których bardzo łatwo o pomyłkę zbyt szybkiego czytania znaku. Wszystkie te ostrzeżenia dotyczą skrzyżowania, ale nie przekazują dokładnie tej samej informacji. Jedne mówią o samym fakcie przecięcia dróg, inne o podporządkowanych wlotach, a jeszcze inne o ruchu okrężnym lub konieczności ustąpienia pierwszeństwa.',
                'published_at' => '2026-04-27T21:00:00+02:00',
                'updated_at' => '2026-04-27T21:00:00+02:00',
                'related_sign_slugs' => [
                    'a-5-skrzyzowanie-drog',
                    'a-6a-skrzyzowanie-z-droga-podporzadkowana-po-obu-stronach',
                    'a-6b-skrzyzowanie-z-droga-podporzadkowana-po-prawej-stronie',
                    'a-6c-skrzyzowanie-z-droga-podporzadkowana-po-lewej-stronie',
                    'a-7-ustap-pierwszenstwa',
                    'a-8-skrzyzowanie-o-ruchu-okreznym',
                ],
                'takeaways' => [
                    'A-5 ostrzega o samym skrzyżowaniu dróg, bez wskazania konkretnego podporządkowanego wlotu.',
                    'Znaki A-6a, A-6b i A-6c doprecyzowują układ podporządkowanych wlotów na skrzyżowaniu.',
                    'A-7 mówi już o obowiązku ustąpienia pierwszeństwa, a A-8 zapowiada ruch okrężny.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-5 ostrzega o skrzyżowaniu jako takim. Rodzina A-6 dopowiada, jak dochodzi droga podporządkowana. A-7 mówi o obowiązku ustąpienia pierwszeństwa, a A-8 o skrzyżowaniu o ruchu okrężnym. Wszystkie te znaki dotyczą skrzyżowania, ale każdy przygotowuje kierowcę na inny wariant decyzji i obserwacji.',
                    ],
                    [
                        'title' => 'Po co istnieje rodzina A-6',
                        'body' => 'Właśnie po to, żeby kierowca wcześniej wiedział, gdzie szukać podporządkowanego wlotu. Przy A-6a trzeba szerzej obserwować obie strony. Przy A-6b i A-6c uwaga może wcześniej przenieść się odpowiednio na prawy albo lewy wlot boczny.',
                    ],
                    [
                        'title' => 'Gdzie w tym układzie są A-7 i A-8',
                        'body' => 'A-7 nie opisuje samego układu wlotów, tylko podnosi temat pierwszeństwa przejazdu. A-8 z kolei uprzedza o ruchu okrężnym, więc kierowca powinien wcześniej przygotować dojazd pod obserwację ronda i pojazdów poruszających się po obwiedni.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z czytania wszystkich tych znaków jak jednego ogólnego ostrzeżenia o skrzyżowaniu. W praktyce każdy z nich daje kierowcy inną wskazówkę: gdzie patrzeć, jak wcześnie zwalniać i z jakim scenariuszem zbliża się do punktu kolizji.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-5 i A-6 oznaczają to samo?',
                        'answer' => 'Nie. A-5 ostrzega o skrzyżowaniu dróg, a rodzina A-6 doprecyzowuje, gdzie występują wloty drogi podporządkowanej.',
                    ],
                    [
                        'question' => 'Czym A-7 różni się od A-5 i A-6?',
                        'answer' => 'A-7 nie jest tylko ostrzeżeniem o układzie skrzyżowania. Wskazuje już obowiązek ustąpienia pierwszeństwa pojazdom jadącym drogą z pierwszeństwem.',
                    ],
                ],
            ],
            [
                'slug' => 'a-16-vs-a-17-vs-a-24',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-16, A-17 i A-24: przejście dla pieszych, dzieci i rowerzyści to trzy różne ostrzeżenia',
                'title' => 'A-16, A-17 i A-24 - jak odróżnić trzy częste ostrzeżenia o uczestnikach ruchu',
                'description' => 'Porównujemy znaki A-16, A-17 i A-24, żeby pokazać, kiedy kierowca powinien szukać przejścia dla pieszych, obecności dzieci albo ruchu rowerowego.',
                'intro' => 'Te trzy znaki bardzo często pojawiają się w podobnym otoczeniu: w mieście, przy szkołach, osiedlach i przejazdach. Łatwo więc wrzucić je do jednego worka „uważaj na ludzi”, ale każdy z nich ostrzega przed trochę innym scenariuszem i wymaga innego akcentu w obserwacji.',
                'published_at' => '2026-04-27T21:05:00+02:00',
                'updated_at' => '2026-04-27T21:05:00+02:00',
                'related_sign_slugs' => [
                    'a-16-przejscie-dla-pieszych',
                    'a-17-dzieci',
                    'a-24-rowerzysci',
                ],
                'takeaways' => [
                    'A-16 ostrzega o przejściu dla pieszych.',
                    'A-17 ostrzega o miejscu, w którym można spodziewać się obecności dzieci.',
                    'A-24 skupia uwagę kierowcy na ruchu rowerowym i miejscach przecinania się tras rowerzystów z ruchem samochodowym.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-16 każe wcześnie szukać przejścia dla pieszych i ludzi zbliżających się do pasów. A-17 ostrzega szerzej o obecności dzieci, często także poza samym przejściem. A-24 przygotowuje kierowcę na rowerzystów oraz na przejazdy rowerowe lub włączanie się roweru do ruchu.',
                    ],
                    [
                        'title' => 'Jak zmienia się obserwacja kierowcy',
                        'body' => 'Przy A-16 wzrok powinien iść w kierunku przejścia oraz krawędzi chodnika. Przy A-17 zakres obserwacji robi się jeszcze szerszy, bo dziecko może pojawić się także poza samymi pasami. Przy A-24 ważne staje się także tempo dojazdu rowerzysty do punktu przecinania toru jazdy auta.',
                    ],
                    [
                        'title' => 'Dlaczego nie warto ich sklejać w jedną odpowiedź',
                        'body' => 'Choć wszystkie trzy znaki podnoszą poziom ostrożności, każdy odpowiada na inne pytanie kierowcy: gdzie szukać pieszego, gdzie spodziewać się dziecka i jak oceniać ruch rowerowy. Właśnie dlatego osobne strony znaków i materiał porównawczy mają tu realną wartość użytkową.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najczęstszy błąd polega na tym, że kierowca reaguje na te znaki dokładnie tak samo. W praktyce przy A-16, A-17 i A-24 potrzeba podobnej ostrożności, ale akcent obserwacji i typ potencjalnego zagrożenia są różne.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-17 oznacza to samo co A-16 przy szkole?',
                        'answer' => 'Nie. A-16 ostrzega o przejściu dla pieszych, a A-17 o miejscu, w którym można spodziewać się obecności dzieci także poza samymi pasami.',
                    ],
                    [
                        'question' => 'Na czym polega różnica między A-16 i A-24?',
                        'answer' => 'A-16 przygotowuje kierowcę głównie na pieszych w rejonie przejścia, a A-24 na rowerzystów i miejsca, w których ich tor jazdy może przeciąć się z ruchem samochodowym.',
                    ],
                ],
            ],
            [
                'slug' => 'a-1-do-a-4',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-1 do A-4: pojedynczy zakręt czy seria zakrętów, w prawo czy w lewo',
                'title' => 'A-1 do A-4 - jak czytać znaki ostrzegające o zakrętach',
                'description' => 'Porównujemy znaki A-1, A-2, A-3 i A-4, żeby szybko rozdzielić pojedynczy niebezpieczny zakręt od serii zakrętów oraz prawy kierunek od lewego.',
                'intro' => 'To grupa znaków, którą kierowcy często upraszczają do jednego komunikatu: "będzie zakręt". W praktyce każdy z tych znaków mówi coś więcej. Trzeba odróżnić pojedynczy łuk od ciągu zakrętów i od razu wiedzieć, z której strony zaczyna się zagrożenie.',
                'published_at' => '2026-04-27T22:15:00+02:00',
                'updated_at' => '2026-04-27T22:15:00+02:00',
                'related_sign_slugs' => [
                    'a-1-niebezpieczny-zakret-w-prawo',
                    'a-2-niebezpieczny-zakret-w-lewo',
                    'a-3-niebezpieczne-zakrety-pierwszy-w-prawo',
                    'a-4-niebezpieczne-zakrety-pierwszy-w-lewo',
                ],
                'takeaways' => [
                    'A-1 i A-2 ostrzegają o pojedynczym niebezpiecznym zakręcie.',
                    'A-3 i A-4 zapowiadają serię zakrętów, a nie tylko jeden łuk.',
                    'Pierwszy kierunek na znaku ma znaczenie, bo ustawia wcześniejszą obserwację i przygotowanie prędkości.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-1 i A-2 odnoszą się do pojedynczego niebezpiecznego zakrętu odpowiednio w prawo lub w lewo. A-3 i A-4 ostrzegają już o całej sekwencji zakrętów, przy czym znak pokazuje, w którą stronę prowadzi pierwszy łuk.',
                    ],
                    [
                        'title' => 'Dlaczego seria zakrętów to nie to samo co jeden zakręt',
                        'body' => 'Przy A-3 i A-4 kierowca nie powinien wracać do przyspieszania zaraz po pierwszym łuku. Znak od początku ustawia myślenie o całym odcinku, na którym tempo jazdy i margines bezpieczeństwa trzeba utrzymać dłużej niż przy pojedynczym zakręcie.',
                    ],
                    [
                        'title' => 'Jak czytać kierunek na znaku',
                        'body' => 'Kierunek nie jest ozdobą. A-1 i A-3 przygotowują do pierwszego ruchu auta w prawo, a A-2 i A-4 do pierwszego łuku w lewo. To zmienia sposób ustawienia wzroku, toru jazdy i wcześniejszej redukcji prędkości.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z traktowania A-3 i A-4 jak zwykłego ostrzeżenia o jednym zakręcie. Kierowca wychodzi wtedy z pierwszego łuku zbyt optymistycznie i za wcześnie wraca do gazu.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-3 oznacza po prostu mocniejszy A-1?',
                        'answer' => 'Nie. A-3 nie ostrzega o jednym zakręcie, tylko o serii zakrętów, z których pierwszy prowadzi w prawo. To zmienia sposób prowadzenia auta na całym odcinku.',
                    ],
                    [
                        'question' => 'Czy kierunek na A-1 i A-2 ma znaczenie praktyczne?',
                        'answer' => 'Tak. Wskazuje, z której strony zacznie się łuk i gdzie kierowca powinien wcześniej przenieść uwagę przy ocenie toru jazdy oraz widoczności.',
                    ],
                ],
            ],
            [
                'slug' => 'a-9-vs-a-10',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-9 a A-10: przejazd kolejowy z zaporami czy bez zapór',
                'title' => 'A-9 a A-10 - jak czytać dwa podstawowe ostrzeżenia o przejeździe kolejowym',
                'description' => 'Wyjaśniamy różnicę między znakami A-9 i A-10 oraz pokazujemy, jak kierowca powinien przygotować obserwację i tempo dojazdu do przejazdu kolejowego.',
                'intro' => 'Oba znaki dotyczą przejazdu kolejowego, ale nie przygotowują kierowcy do identycznej sytuacji. Informacja o zaporach albo ich braku wpływa na to, jak wcześnie trzeba szukać sygnałów ostrzegawczych, ruchu pociągu i zachowania innych pojazdów.',
                'published_at' => '2026-04-27T22:20:00+02:00',
                'updated_at' => '2026-04-27T22:20:00+02:00',
                'related_sign_slugs' => [
                    'a-9-przejazd-kolejowy-z-zaporami',
                    'a-10-przejazd-kolejowy-bez-zapor',
                ],
                'takeaways' => [
                    'A-9 ostrzega o przejeździe kolejowym z zaporami.',
                    'A-10 ostrzega o przejeździe kolejowym bez zapór.',
                    'Brak zapór oznacza jeszcze większą wagę własnej obserwacji i spokojnego dojazdu do przejazdu.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-9 zapowiada przejazd kolejowy wyposażony w zapory, a A-10 przejazd bez zapór. W obu przypadkach kierowca ma przygotować się na bezpieczny dojazd do torów, ale przy A-10 ciężar obserwacji i oceny sytuacji jest jeszcze bardziej po stronie samego kierowcy.',
                    ],
                    [
                        'title' => 'Jak zmienia się zachowanie kierowcy',
                        'body' => 'Przy obu znakach ważne jest wcześniejsze wytracenie prędkości i gotowość do zatrzymania. Różnica polega na tym, że przy A-10 nie można zakładać, że fizyczna bariera będzie czytelnym, ostatnim ostrzeżeniem przed torem.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z patrzenia na oba znaki jak na zwykły wariant graficzny tego samego ostrzeżenia. Tymczasem informacja o zaporach wpływa na tempo dojazdu, zakres obserwacji i margines bezpieczeństwa.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-10 wymaga większej ostrożności niż A-9?',
                        'answer' => 'Tak, bo ostrzega o przejeździe bez zapór. Kierowca musi jeszcze mocniej opierać się na własnej obserwacji, sygnałach ostrzegawczych i gotowości do zatrzymania.',
                    ],
                    [
                        'question' => 'Czy przy A-9 można dojechać szybciej, skoro są zapory?',
                        'answer' => 'Nie. Obecność zapór nie zwalnia z obowiązku wcześniejszego ograniczenia prędkości i spokojnej oceny sytuacji na dojeździe do torów.',
                    ],
                ],
            ],
            [
                'slug' => 'a-11-do-a-12c',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-11 do A-12c: nierówna droga, próg zwalniający i zwężenia jezdni',
                'title' => 'A-11 do A-12c - jak odróżnić ostrzeżenia o nawierzchni i przewężeniu',
                'description' => 'Porównujemy A-11, A-11a, A-12a, A-12b i A-12c, żeby szybko rozdzielić zagrożenia związane z nierówną nawierzchnią od ostrzeżeń o zwężeniu jezdni.',
                'intro' => 'Te znaki łatwo wrzucić do jednego worka "trzeba zwolnić", ale ich sens jest różny. Jedne uprzedzają o tym, co dzieje się z nawierzchnią pod kołami, a inne o tym, jak zmieni się dostępna szerokość jezdni i margines mijania pojazdów.',
                'published_at' => '2026-04-27T22:25:00+02:00',
                'updated_at' => '2026-04-27T22:25:00+02:00',
                'related_sign_slugs' => [
                    'a-11-nierowna-droga',
                    'a-11a-prog-zwalniajacy',
                    'a-12a-zwezenie-jezdni-dwustronne',
                    'a-12b-zwezenie-jezdni-prawostronne',
                    'a-12c-zwezenie-jezdni-lewostronne',
                ],
                'takeaways' => [
                    'A-11 i A-11a dotyczą zachowania auta na nawierzchni.',
                    'A-12a, A-12b i A-12c dotyczą zmniejszenia dostępnej szerokości jezdni.',
                    'Wariant zwężenia mówi kierowcy, z której strony trzeba spodziewać się zmiany marginesu.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-11 ostrzega o nierównej drodze, A-11a o progu zwalniającym, a rodzina A-12 o zwężeniu jezdni. W praktyce pierwsza para przygotowuje głównie do zmiany pracy zawieszenia i przyczepności, a druga do innego ustawienia toru jazdy i obserwacji pojazdów z naprzeciwka.',
                    ],
                    [
                        'title' => 'Co zmienia się przy zwężeniu',
                        'body' => 'A-12a zapowiada przewężenie z obu stron, A-12b z prawej, a A-12c z lewej. To ważne, bo kierowca powinien odpowiednio wcześniej wiedzieć, z której strony zaczyna tracić margines i jak ustawić pojazd względem osi jezdni.',
                    ],
                    [
                        'title' => 'Dlaczego próg i nierówna droga to nie to samo',
                        'body' => 'A-11a ostrzega o konkretnym progu zwalniającym, czyli miejscowym, wyraźnym wymuszeniu redukcji prędkości. A-11 jest szerszy i może opisywać odcinek, na którym problemem są ogólnie nierówności nawierzchni.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z reakcji "zwolnię dopiero na samym znaku". Przy tej rodzinie to za mało. Kierowca powinien wejść w nierówność lub przewężenie już z uporządkowaną prędkością i ustawionym torem jazdy.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-11a to po prostu mocniejsza wersja A-11?',
                        'answer' => 'Nie. A-11a dotyczy konkretnego progu zwalniającego, a A-11 ogólnie nierównej drogi. To dwa różne typy ostrzeżeń o nawierzchni.',
                    ],
                    [
                        'question' => 'Po co na A-12 rozróżnia się stronę zwężenia?',
                        'answer' => 'Bo to od razu podpowiada kierowcy, z której strony zniknie margines i jak wcześniej ustawić tor jazdy oraz obserwację przy mijaniu innych pojazdów.',
                    ],
                ],
            ],
            [
                'slug' => 'a-6d-vs-a-6e',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-6d a A-6e: wlot drogi jednokierunkowej z prawej czy z lewej strony',
                'title' => 'A-6d a A-6e - jak czytać dwa warianty wlotu drogi jednokierunkowej',
                'description' => 'Porównujemy A-6d i A-6e, żeby szybko rozdzielić wlot drogi jednokierunkowej z prawej strony od wariantu z lewej strony.',
                'intro' => 'To małe graficzne różnice, ale w praktyce ustawiają obserwację w zupełnie inną stronę. Kierowca nie powinien czytać tych znaków jako zwykłego ostrzeżenia o bocznym wlocie, tylko jako precyzyjną informację, z której strony dochodzi droga jednokierunkowa.',
                'published_at' => '2026-04-27T22:45:00+02:00',
                'updated_at' => '2026-04-27T22:45:00+02:00',
                'related_sign_slugs' => [
                    'a-6d-wlot-drogi-jednokierunkowej-z-prawej-strony',
                    'a-6e-wlot-drogi-jednokierunkowej-z-lewej-strony',
                ],
                'takeaways' => [
                    'A-6d ostrzega o wlocie drogi jednokierunkowej z prawej strony.',
                    'A-6e ostrzega o wlocie drogi jednokierunkowej z lewej strony.',
                    'Najważniejsza praktyczna różnica dotyczy tego, gdzie kierowca powinien wcześniej przenieść uwagę.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-6d uprzedza, że droga jednokierunkowa dochodzi z prawej strony, a A-6e z lewej. Sam fakt jednokierunkowości jest ważny, ale jeszcze ważniejsze jest to, z której strony trzeba wcześniej przygotować obserwację.',
                    ],
                    [
                        'title' => 'Dlaczego jednokierunkowy wlot nie jest zwykłym wlotem bocznym',
                        'body' => 'Taki znak daje kierowcy bardziej uporządkowaną informację o potencjalnym kierunku pojawienia się pojazdów. To może zmienić sposób skanowania skrzyżowania jeszcze przed dojazdem do wlotu.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z potraktowania A-6d i A-6e jak zwykłych wariantów A-6b i A-6c. Kierowca traci wtedy praktyczną korzyść z informacji, że wlot ma uporządkowany ruch jednokierunkowy.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-6d i A-6e różnią się tylko stroną znaku?',
                        'answer' => 'Nie tylko. Ta różnica ustawia też wcześniejszą obserwację kierowcy pod konkretną stronę skrzyżowania i konkretny kierunek możliwego ruchu.',
                    ],
                    [
                        'question' => 'Czy te znaki można czytać jak zwykły podporządkowany wlot boczny?',
                        'answer' => 'Nie warto ich upraszczać. Informacja o drodze jednokierunkowej daje kierowcy bardziej precyzyjny obraz ruchu niż zwykły wlot boczny.',
                    ],
                ],
            ],
            [
                'slug' => 'a-14-a-15-a-20',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-14, A-15 i A-20: trzy ostrzeżenia, które zmieniają rytm jazdy na odcinku',
                'title' => 'A-14, A-15 i A-20 - roboty, śliska jezdnia i powrót ruchu z przeciwka',
                'description' => 'Wyjaśniamy różnice między A-14, A-15 i A-20 oraz pokazujemy, jak te trzy znaki zmieniają obserwację i tempo jazdy kierowcy.',
                'intro' => 'To nie jest jedna rodzina graficzna, ale bardzo praktyczna grupa użytkowa. Każdy z tych znaków każe kierowcy od razu przełączyć się na inny styl jazdy: przy robotach, przy śliskiej nawierzchni albo przy powrocie ruchu z przeciwka.',
                'published_at' => '2026-04-27T22:50:00+02:00',
                'updated_at' => '2026-04-27T22:50:00+02:00',
                'related_sign_slugs' => [
                    'a-14-roboty-na-drodze',
                    'a-15-sliska-jezdnia',
                    'a-20-odcinek-jezdni-o-ruchu-dwukierunkowym',
                ],
                'takeaways' => [
                    'A-14 przygotowuje na zmianę organizacji ruchu i pracę ludzi lub sprzętu na drodze.',
                    'A-15 ostrzega o spadku przyczepności nawierzchni.',
                    'A-20 oznacza odcinek, na którym kierowca musi znów brać pod uwagę ruch z przeciwka.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-14 dotyczy prac drogowych i możliwej zmiany organizacji ruchu. A-15 ostrzega o śliskiej jezdni. A-20 informuje, że za chwilę wraca ruch dwukierunkowy. Wszystkie trzy znaki każą zwolnić, ale każdy z innego powodu.',
                    ],
                    [
                        'title' => 'Co łączy tę trójkę',
                        'body' => 'Każdy z tych znaków zmienia "normalny rytm" jazdy na odcinku. Kierowca nie może jechać na pamięć: musi na nowo ustawić prędkość, szerokość obserwacji i margines błędu.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z reakcji zbyt późnej albo zbyt ogólnej. Kierowca widzi znak, ale nie przekłada go na konkretną zmianę zachowania: przy A-14 nie przygotowuje się na zmianę toru ruchu, przy A-15 nie redukuje prędkości, a przy A-20 za późno wraca do pełnej obserwacji przeciwnego kierunku.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-14, A-15 i A-20 wymagają tej samej reakcji kierowcy?',
                        'answer' => 'Nie. Wszystkie podnoszą poziom ostrożności, ale A-14 dotyczy robót i organizacji ruchu, A-15 przyczepności nawierzchni, a A-20 powrotu ruchu z przeciwka.',
                    ],
                    [
                        'question' => 'Co jest wspólnym błędem przy tych znakach?',
                        'answer' => 'Zbyt późna reakcja. Kierowca zauważa znak, ale nie zmienia odpowiednio wcześnie prędkości, obserwacji albo ustawienia pojazdu.',
                    ],
                ],
            ],
            [
                'slug' => 'a-18a-vs-a-18b',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-18a a A-18b: zwierzęta gospodarskie czy zwierzęta dzikie',
                'title' => 'A-18a a A-18b - jak odróżnić dwa ostrzeżenia o zwierzętach na drodze',
                'description' => 'Porównujemy A-18a i A-18b, żeby pokazać różnicę między ostrzeżeniem o zwierzętach gospodarskich i o zwierzętach dzikich.',
                'intro' => 'Oba znaki każą kierowcy przygotować się na obecność zwierząt, ale nie opisują identycznej sytuacji. Zwierzęta gospodarskie i dzikie pojawiają się przy drodze w innym kontekście, a to wpływa na obserwację i przewidywanie zachowań.',
                'published_at' => '2026-04-27T22:55:00+02:00',
                'updated_at' => '2026-04-27T22:55:00+02:00',
                'related_sign_slugs' => [
                    'a-18a-zwierzeta-gospodarskie',
                    'a-18b-zwierzeta-dzikie',
                ],
                'takeaways' => [
                    'A-18a ostrzega o możliwej obecności zwierząt gospodarskich.',
                    'A-18b ostrzega o możliwej obecności zwierząt dzikich.',
                    'Różnica ma znaczenie, bo zmienia kontekst miejsca i sposób przewidywania zachowania zwierząt.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-18a dotyczy zwierząt gospodarskich, zwykle związanych z otoczeniem gospodarstw, pastwisk i lokalnych dróg. A-18b dotyczy zwierząt dzikich, których pojawienie się bywa mniej przewidywalne i częściej wiąże się z terenami leśnymi lub otwartymi.',
                    ],
                    [
                        'title' => 'Jak zmienia się obserwacja kierowcy',
                        'body' => 'Przy A-18a warto mocniej czytać otoczenie gospodarstw, ogrodzeń i pobocza. Przy A-18b kierowca powinien bardziej liczyć się z nagłym wtargnięciem zwierzęcia z zarośli, lasu albo otwartego terenu.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z założenia, że oba znaki znaczą dokładnie to samo. W praktyce różny jest kontekst miejsca i przewidywalność zagrożenia, a to wpływa na styl jazdy i zakres obserwacji.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-18a i A-18b można traktować identycznie?',
                        'answer' => 'Oba wymagają zwiększonej ostrożności, ale nie opisują dokładnie tego samego kontekstu zagrożenia. Inaczej czyta się okolice gospodarstw, a inaczej odcinki leśne i otwarte.',
                    ],
                    [
                        'question' => 'Który znak częściej wiąże się z nagłym wtargnięciem na jezdnię?',
                        'answer' => 'W praktyce częściej myślimy tak o A-18b, bo zwierzęta dzikie pojawiają się mniej przewidywalnie niż zwierzęta gospodarskie prowadzone lub przebywające w pobliżu zabudowań.',
                    ],
                ],
            ],
            [
                'slug' => 'a-29-vs-a-30',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-29 a A-30: sygnały świetlne czy inne niebezpieczeństwo',
                'title' => 'A-29 a A-30 - konkretne ostrzeżenie czy ogólny sygnał zagrożenia',
                'description' => 'Wyjaśniamy różnicę między A-29 i A-30, żeby kierowca od razu wiedział, kiedy szukać sygnalizacji świetlnej, a kiedy doprecyzowania innego zagrożenia.',
                'intro' => 'To dobre zestawienie dwóch znaków, które działają bardzo inaczej. A-29 od razu podpowiada konkret: zbliżasz się do sygnalizacji świetlnej. A-30 jest szeroki i wymaga doprecyzowania przez tabliczkę albo kontekst miejsca.',
                'published_at' => '2026-04-27T23:00:00+02:00',
                'updated_at' => '2026-04-27T23:00:00+02:00',
                'related_sign_slugs' => [
                    'a-29-sygnaly-swietlne',
                    'a-30-inne-niebezpieczenstwo',
                ],
                'takeaways' => [
                    'A-29 zapowiada sygnalizację świetlną.',
                    'A-30 ostrzega o innym, ogólnie opisanym niebezpieczeństwie wymagającym doprecyzowania.',
                    'Największa różnica polega na tym, że A-29 mówi kierowcy od razu, czego szukać, a A-30 wymaga czytania kontekstu.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-29 jest konkretnym ostrzeżeniem o sygnalizacji świetlnej. A-30 to znak ogólny, który dopiero razem z tabliczką lub kontekstem miejsca wyjaśnia, przed czym dokładnie ostrzega.',
                    ],
                    [
                        'title' => 'Dlaczego A-30 wymaga więcej czytania',
                        'body' => 'Przy A-29 kierowca od razu wie, że ma przygotować się na światła i zmianę wskazań. Przy A-30 nie wystarczy zauważyć samego trójkąta. Trzeba od razu szukać doprecyzowania i przełożyć je na zachowanie na drodze.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z mechanicznego reagowania tak samo na oba znaki. Przy A-29 to zwykle zbyt szybki dojazd do sygnalizacji, a przy A-30 zignorowanie informacji, co dokładnie jest źródłem zagrożenia.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-30 bez tabliczki mówi dokładnie, jakie jest zagrożenie?',
                        'answer' => 'Nie. To właśnie odróżnia go od bardziej konkretnych znaków ostrzegawczych. Kierowca musi czytać go razem z dodatkowym oznakowaniem albo kontekstem miejsca.',
                    ],
                    [
                        'question' => 'Czy A-29 zawsze oznacza, że światła będą słabo widoczne?',
                        'answer' => 'Nie zawsze, ale oznacza, że kierowca powinien wcześniej przygotować się na sygnalizację i nie dojeżdżać do niej na pamięć lub zbyt szybko.',
                    ],
                ],
            ],
            [
                'slug' => 'a-13-a-19-a-21',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-13, A-19 i A-21: ruchomy most, boczny wiatr i tramwaj to trzy różne źródła zagrożenia',
                'title' => 'A-13, A-19 i A-21 - jak odróżnić trzy nietypowe ostrzeżenia na odcinku',
                'description' => 'Porównujemy znaki A-13, A-19 i A-21, żeby pokazać, kiedy kierowca ma przygotować się na ruchomy most, boczny wiatr albo przecięcie ruchu z tramwajem.',
                'intro' => 'Te trzy znaki nie tworzą oczywistej rodziny graficznej, ale w praktyce łączy je jedno: każdy wymusza szybkie przełączenie się na bardzo konkretny scenariusz zagrożenia. Kierowca nie może czytać ich jako zwykłego sygnału, że "trzeba uważać". Musi wiedzieć, na co dokładnie przenieść uwagę.',
                'published_at' => '2026-04-28T09:00:00+02:00',
                'updated_at' => '2026-04-28T09:00:00+02:00',
                'related_sign_slugs' => [
                    'a-13-ruchomy-most',
                    'a-19-boczny-wiatr',
                    'a-21-tramwaj',
                ],
                'takeaways' => [
                    'A-13 ostrzega o ruchomym moście i możliwej zmianie organizacji przejazdu.',
                    'A-19 ostrzega o bocznym wietrze i ryzyku znoszenia pojazdu z toru jazdy.',
                    'A-21 ostrzega o miejscu, w którym trzeba liczyć się z ruchem tramwajowym.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-13 dotyczy infrastruktury i organizacji przejazdu przez ruchomy most. A-19 dotyczy oddziaływania warunków atmosferycznych na pojazd. A-21 dotyczy przecięcia ruchu drogowego z ruchem tramwajowym. Każdy z tych znaków ostrzega przed czymś innym i wymaga innego punktu skupienia uwagi.',
                    ],
                    [
                        'title' => 'Na czym skupia się kierowca przy A-13',
                        'body' => 'Przy ruchomym moście kluczowe są sygnały, zapory, tempo dojazdu i gotowość do spokojnego zatrzymania. To znak, który zmienia sposób czytania całego miejsca przejazdu, a nie tylko jednego punktu na jezdni.',
                    ],
                    [
                        'title' => 'Na czym skupia się kierowca przy A-19',
                        'body' => 'Tu problemem jest stabilność pojazdu. Kierowca powinien przygotować chwyt kierownicy, zapas toru jazdy i ostrożniejsze tempo, szczególnie na otwartych przestrzeniach, mostach i wiaduktach.',
                    ],
                    [
                        'title' => 'Na czym skupia się kierowca przy A-21',
                        'body' => 'A-21 kieruje uwagę na torowisko, punkt przecięcia ruchu i zachowanie tramwaju. To ostrzeżenie miejskie, które wymaga bardzo świadomego podejścia do pierwszeństwa, widoczności i szerokości skrętu.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-19 i A-21 wymagają tej samej reakcji kierowcy?',
                        'answer' => 'Nie. A-19 dotyczy wpływu wiatru na prowadzenie pojazdu, a A-21 relacji z ruchem tramwajowym. W obu przypadkach trzeba zwolnić i zwiększyć uwagę, ale źródło zagrożenia jest inne.',
                    ],
                    [
                        'question' => 'Co najbardziej odróżnia A-13 od dwóch pozostałych znaków?',
                        'answer' => 'A-13 dotyczy przede wszystkim organizacji przejazdu przez ruchomy most i pracy infrastruktury, a nie warunków atmosferycznych czy innych uczestników ruchu.',
                    ],
                ],
            ],
            [
                'slug' => 'a-22-vs-a-23',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-22 a A-23: niebezpieczny zjazd i stromy podjazd to nie to samo',
                'title' => 'A-22 a A-23 - jak odróżnić zjazd od podjazdu i dobrać reakcję kierowcy',
                'description' => 'Wyjaśniamy różnicę między A-22 i A-23 oraz pokazujemy, jak zmienia się zachowanie kierowcy na zjeździe i na stromym podjeździe.',
                'intro' => 'Oba znaki dotyczą nachylenia drogi, ale nie opisują tej samej pracy auta ani tej samej reakcji kierowcy. Zjazd mocniej wiąże się z kontrolą prędkości i hamowaniem, a podjazd z utrzymaniem tempa, biegu i płynności jazdy.',
                'published_at' => '2026-04-28T09:05:00+02:00',
                'updated_at' => '2026-04-28T09:05:00+02:00',
                'related_sign_slugs' => [
                    'a-22-niebezpieczny-zjazd',
                    'a-23-stromy-podjazd',
                ],
                'takeaways' => [
                    'A-22 ostrzega o niebezpiecznym zjeździe.',
                    'A-23 ostrzega o stromym podjeździe.',
                    'Największa różnica dotyczy kontroli prędkości na zjeździe i utrzymania płynności na podjeździe.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-22 przygotowuje kierowcę do zejścia drogi w dół, a A-23 do jej stromego wzniesienia. W praktyce zjazd oznacza większą wagę kontroli prędkości, a podjazd większą wagę doboru biegu i utrzymania tempa.',
                    ],
                    [
                        'title' => 'Jak myśleć o A-22',
                        'body' => 'Przy niebezpiecznym zjeździe kierowca powinien zwolnić wcześniej, nie czekać z reakcją do połowy spadku i pilnować, żeby auto nie zaczęło nabierać prędkości ponad kontrolę.',
                    ],
                    [
                        'title' => 'Jak myśleć o A-23',
                        'body' => 'Przy stromym podjeździe ważne jest wcześniejsze przygotowanie auta do wzniesienia. Trzeba zadbać o właściwy bieg, płynność i zapas, żeby nie wejść w podjazd z nieczytelnym, spóźnionym manewrem.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z traktowania obu znaków identycznie. Kierowca wie, że droga będzie "mocno nachylona", ale nie przekłada tego na inne zachowanie auta na zjeździe i na podjeździe.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy A-22 i A-23 różnią się tylko kierunkiem nachylenia drogi?',
                        'answer' => 'Nie. Za tą różnicą idzie też inna praca auta i inny priorytet dla kierowcy: kontrola prędkości na zjeździe albo utrzymanie płynności na podjeździe.',
                    ],
                    [
                        'question' => 'Który znak bardziej wiąże się z ryzykiem zbyt szybkiego rozpędzenia auta?',
                        'answer' => 'Bardziej dotyczy to A-22, bo niebezpieczny zjazd łatwo prowokuje zbyt późne hamowanie i narastanie prędkości na spadku drogi.',
                    ],
                ],
            ],
            [
                'slug' => 'a-25-do-a-28',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-25 do A-28: skały, lotnisko, nabrzeże i sypki żwir to cztery różne typy zagrożeń',
                'title' => 'A-25 do A-28 - jak czytać warning signs związane z terenem i otoczeniem drogi',
                'description' => 'Porównujemy A-25, A-26, A-27 i A-28, żeby uporządkować ostrzeżenia o otoczeniu drogi: skałach, lotnisku, nabrzeżu i sypkim żwirze.',
                'intro' => 'To grupa znaków, którą łatwo wrzucić do jednego worka "nietypowe zagrożenia". Tymczasem każdy z nich odnosi się do innego źródła problemu: jedne do otoczenia skalnego, inne do pobliża lotniska, brzegu rzeki albo luźnej nawierzchni pod kołami.',
                'published_at' => '2026-04-28T09:10:00+02:00',
                'updated_at' => '2026-04-28T09:10:00+02:00',
                'related_sign_slugs' => [
                    'a-25-spadajace-odlamki-skalne',
                    'a-26-lotnisko',
                    'a-27-nabrzeze-lub-brzeg-rzeki',
                    'a-28-sypki-zwir',
                ],
                'takeaways' => [
                    'A-25 ostrzega o spadających odłamkach skalnych.',
                    'A-26 ostrzega o sąsiedztwie lotniska i jego wpływie na warunki jazdy.',
                    'A-27 ostrzega o nabrzeżu lub brzegu rzeki, a A-28 o sypkim żwirze i luźnej nawierzchni.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-25 dotyczy ryzyka spadających odłamków skalnych, A-26 otoczenia drogi przy lotnisku, A-27 bliskości nabrzeża lub brzegu rzeki, a A-28 luźnej nawierzchni. To cztery różne sytuacje i cztery różne punkty ciężkości w zachowaniu kierowcy.',
                    ],
                    [
                        'title' => 'Które znaki dotyczą bardziej otoczenia, a który nawierzchni',
                        'body' => 'A-25, A-26 i A-27 bardziej opisują specyfikę miejsca i otoczenia drogi. A-28 najmocniej dotyka samego kontaktu opony z nawierzchnią, bo zmienia przyczepność i wydłuża reakcję auta przy hamowaniu oraz skręcie.',
                    ],
                    [
                        'title' => 'Dlaczego nie warto ich upraszczać',
                        'body' => 'Jeśli kierowca wrzuci te znaki do jednej kategorii "dziwne ostrzeżenia", traci najważniejszą informację: czy problemem jest teren, krawędź drogi, rozproszenie bodźcami z otoczenia czy sama nawierzchnia pod kołami.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Największy błąd to reagowanie identycznie na wszystkie cztery znaki. Owszem, zwykle trzeba uspokoić jazdę, ale z innego powodu: raz chodzi o tor jazdy przy krawędzi, raz o nawierzchnię, a raz o charakter samego miejsca.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Który z tych znaków najmocniej dotyczy przyczepności nawierzchni?',
                        'answer' => 'Najbardziej bezpośrednio dotyczy tego A-28, bo ostrzega o sypkim żwirze i luźnej nawierzchni pod kołami.',
                    ],
                    [
                        'question' => 'Czy A-26 i A-27 można traktować jak zwykłe ostrzeżenie o "nietypowym miejscu"?',
                        'answer' => 'Nie warto tego upraszczać. A-26 i A-27 dotyczą innych rodzajów ryzyka i inaczej ustawiają obserwację oraz margines bezpieczeństwa kierowcy.',
                    ],
                ],
            ],
            [
                'slug' => 'a-31-do-a-34',
                'kicker' => 'Porównanie znaków',
                'headline' => 'A-31 do A-34: pobocze, oszronienie, zator i wypadek to cztery różne sytuacje drogowe',
                'title' => 'A-31 do A-34 - jak odróżnić końcową rodzinę warning signs o stanie odcinka i sytuacji na drodze',
                'description' => 'Wyjaśniamy różnice między A-31, A-32, A-33 i A-34 oraz pokazujemy, jak kierowca powinien czytać pobocze, oszronienie, zator i miejsce wypadku.',
                'intro' => 'Ta końcowa grupa znaków ostrzegawczych dobrze pokazuje, jak szerokie może być pojęcie zagrożenia na drodze. Raz chodzi o pobocze i tor jazdy, raz o przyczepność, raz o kolumnę pojazdów, a raz o miejsce zdarzenia i wtórne zagrożenia.',
                'published_at' => '2026-04-28T09:15:00+02:00',
                'updated_at' => '2026-04-28T09:15:00+02:00',
                'related_sign_slugs' => [
                    'a-31-niebezpieczne-pobocze',
                    'a-32-oszronienie-jezdni',
                    'a-33-zator-drogowy',
                    'a-34-wypadek-drogowy',
                ],
                'takeaways' => [
                    'A-31 dotyczy ryzyka związanego z poboczem i utrzymaniem auta przy krawędzi jezdni.',
                    'A-32 dotyczy spadku przyczepności z powodu oszronienia jezdni.',
                    'A-33 ostrzega o zatorze, a A-34 o wypadku drogowym i jego następstwach na odcinku.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'A-31 ostrzega o poboczu, A-32 o oszronieniu nawierzchni, A-33 o zatorze, a A-34 o wypadku drogowym. Wszystkie te znaki wymagają uspokojenia jazdy, ale każdy z innego powodu i w innym punkcie procesu obserwacji drogi.',
                    ],
                    [
                        'title' => 'Które znaki dotyczą bardziej auta, a które sytuacji przed autem',
                        'body' => 'A-31 i A-32 bardziej wpływają na to, jak zachowa się sam pojazd na jezdni i przy jej krawędzi. A-33 i A-34 bardziej ustawiają obserwację tego, co dzieje się przed kierowcą: kolumny pojazdów, miejsca zdarzenia, ludzi i służb.',
                    ],
                    [
                        'title' => 'Dlaczego A-33 i A-34 nie są tym samym',
                        'body' => 'Zator oznacza głównie spiętrzenie ruchu i potrzebę spokojnego dojazdu do końca kolumny. Wypadek drogowy oznacza dodatkowo wtórne zagrożenia, obecność służb, rozproszenie kierowców i często bardziej nieprzewidywalny układ sytuacji na drodze.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z reakcji zbyt ogólnej: kierowca wie, że "trzeba uważać", ale nie dopasowuje konkretu. Inaczej trzeba podejść do pobocza, inaczej do oszronionej jezdni, a inaczej do końca zatoru i miejsca wypadku.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Który z tych znaków najmocniej dotyczy przyczepności nawierzchni?',
                        'answer' => 'Najbardziej bezpośrednio dotyczy tego A-32, bo ostrzega o oszronieniu jezdni i spadku przyczepności.',
                    ],
                    [
                        'question' => 'Czy A-33 i A-34 można czytać tak samo, bo oba oznaczają problem przed autem?',
                        'answer' => 'Nie. A-33 dotyczy zatoru i końca kolumny pojazdów, a A-34 miejsca wypadku i wtórnych zagrożeń, które zwykle wymagają jeszcze szerszej obserwacji i większego marginesu bezpieczeństwa.',
                    ],
                ],
            ],
            [
                'slug' => 'b-21-vs-b-23',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-21 a B-23: zakaz skrętu w lewo i zakaz zawracania to nie to samo',
                'title' => 'B-21 a B-23 - najważniejsze różnice dla kierowcy',
                'description' => 'Porównujemy znak B-21 Zakaz skręcania w lewo i B-23 Zakaz zawracania: czego dokładnie zabraniają i gdzie kierowcy najczęściej mylą te manewry.',
                'intro' => 'To jedna z najbardziej klasycznych pomyłek w znakach zakazu. B-21 zabrania skrętu w lewo, a B-23 zawracania. Na niektórych skrzyżowaniach oba manewry wydają się podobne, ale w organizacji ruchu oznaczają coś innego.',
                'published_at' => '2026-04-27T18:10:00+02:00',
                'updated_at' => '2026-04-27T18:10:00+02:00',
                'related_sign_slugs' => [
                    'b-21-zakaz-skrecania-w-lewo',
                    'b-23-zakaz-zawracania',
                    'b-24-koniec-zakazu-zawracania',
                ],
                'takeaways' => [
                    'B-21 zabrania skrętu w lewo, a B-23 zabrania zawracania.',
                    'Zakaz zawracania nie zawsze oznacza zakaz skrętu w lewo.',
                    'Najwięcej błędów bierze się z patrzenia na oba manewry jak na jedną rzecz.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-21 dotyczy skrętu w lewo. B-23 dotyczy zawracania. To dwa różne manewry i dwa różne zakazy. Kierowca musi najpierw rozpoznać, który manewr zamierza wykonać, a dopiero potem ocenić znak.',
                    ],
                    [
                        'title' => 'Kiedy zakaz skrętu w lewo nie oznacza zakazu zawracania',
                        'body' => 'W praktyce wiele zależy od całej organizacji skrzyżowania. Sam znak B-21 zabrania skrętu w lewo, ale nie zawsze odpowiada od razu na temat zawracania, jeśli inne oznakowanie nie reguluje tego osobno.',
                    ],
                    [
                        'title' => 'Kiedy zakaz zawracania nie blokuje skrętu w lewo',
                        'body' => 'B-23 zabrania wykonania pełnego zawrócenia. Jeśli organizacja ruchu dopuszcza skręt w lewo, sam zakaz zawracania nie musi go uchylać. To właśnie ten szczegół najczęściej pojawia się w pytaniach egzaminacyjnych.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Kursanci i kierowcy często patrzą tylko na ogólny kierunek ruchu i zakładają, że skoro oba manewry są „w lewo”, to znak powinien działać identycznie. Tymczasem dla organizacji ruchu to dwa osobne zachowania pojazdu.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy przy B-23 mogę skręcić w lewo?',
                        'answer' => 'To zależy od pozostałego oznakowania. B-23 zabrania zawracania, ale nie zawsze automatycznie zakazuje skrętu w lewo.',
                    ],
                    [
                        'question' => 'Czy B-21 oznacza to samo co B-23?',
                        'answer' => 'Nie. B-21 dotyczy skrętu w lewo, a B-23 zawracania. To dwa różne zakazy i dwa różne manewry.',
                    ],
                ],
            ],
            [
                'slug' => 'b-33-vs-b-43',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-33 a B-43: ograniczenie prędkości czy strefa ograniczonej prędkości',
                'title' => 'B-33 a B-43 - czym różni się limit punktowy od strefy',
                'description' => 'Wyjaśniamy różnicę między znakiem B-33 Ograniczenie prędkości i B-43 Strefa ograniczonej prędkości oraz pokazujemy, jak kierowca powinien czytać oba oznaczenia.',
                'intro' => 'Oba znaki dotyczą prędkości, ale nie działają tak samo. B-33 zwykle ustawia konkretny limit na danym odcinku, a B-43 wprowadza zasady obowiązujące w całej strefie, aż do jej odwołania.',
                'published_at' => '2026-04-27T18:15:00+02:00',
                'updated_at' => '2026-04-27T18:15:00+02:00',
                'related_sign_slugs' => [
                    'b-33-ograniczenie-predkosci',
                    'b-34-koniec-ograniczenia-predkosci',
                    'b-43-strefa-ograniczonej-predkosci',
                    'b-44-koniec-strefy-ograniczonej-predkosci',
                ],
                'takeaways' => [
                    'B-33 najczęściej oznacza limit na konkretnym odcinku lub do miejsca jego odwołania.',
                    'B-43 wprowadza zasady strefowe, które trwają aż do końca strefy.',
                    'Największe pomyłki biorą się z traktowania B-43 jak zwykłego znaku punktowego.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-33 mówi: od tego miejsca obowiązuje konkretny limit prędkości. B-43 mówi: wjeżdżasz do strefy, w której limit i związane z nią zasady obowiązują szerzej, aż do znaku kończącego strefę.',
                    ],
                    [
                        'title' => 'Jak czytać B-33',
                        'body' => 'To klasyczny znak ograniczenia prędkości. Kierowca powinien osiągnąć wskazany limit już w miejscu obowiązywania znaku i pilnować, kiedy ograniczenie zostaje odwołane przez kolejny znak albo zmianę organizacji ruchu.',
                    ],
                    [
                        'title' => 'Jak czytać B-43',
                        'body' => 'B-43 nie dotyczy tylko jednego punktu na drodze. Wprowadza strefę, czyli obszar, w którym kierowca ma stosować się do określonego limitu i układu ruchu aż do znaku B-44 kończącego strefę.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najczęstszy błąd polega na tym, że po minięciu kilku skrzyżowań kierowca traktuje B-43 jak dawno nieaktualny znak punktowy. Tymczasem ograniczenie strefowe wciąż działa, dopóki nie pojawi się znak kończący strefę.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy B-43 działa do najbliższego skrzyżowania?',
                        'answer' => 'Nie. B-43 wprowadza strefę ograniczonej prędkości i jej zasady obowiązują do znaku kończącego strefę, a nie tylko do najbliższego skrzyżowania.',
                    ],
                    [
                        'question' => 'Czym B-44 różni się od B-34?',
                        'answer' => 'B-34 kończy zwykłe ograniczenie prędkości, a B-44 kończy całą strefę ograniczonej prędkości. To dwa różne sposoby odwoływania ograniczeń.',
                    ],
                ],
            ],
            [
                'slug' => 'b-35-vs-b-36',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-35 a B-36: kiedy wolno się zatrzymać, a kiedy nie wolno nawet na chwilę',
                'title' => 'B-35 a B-36 - zakaz postoju i zakaz zatrzymywania się w praktyce',
                'description' => 'Porównujemy znak B-35 Zakaz postoju i B-36 Zakaz zatrzymywania się oraz wyjaśniamy, kiedy kierowca może zatrzymać auto tylko na moment.',
                'intro' => 'To jeden z najbardziej praktycznych duetów w całej kategorii znaków zakazu. B-35 dopuszcza krótkie zatrzymanie, ale zabrania postoju. B-36 jest ostrzejszy i zakazuje już samego zatrzymania pojazdu, poza wyjątkami wynikającymi z ruchu.',
                'published_at' => '2026-04-27T18:20:00+02:00',
                'updated_at' => '2026-04-27T18:20:00+02:00',
                'related_sign_slugs' => [
                    'b-35-zakaz-postoju',
                    'b-36-zakaz-zatrzymywania-sie',
                    'b-37-zakaz-postoju-w-dni-nieparzyste',
                    'b-38-zakaz-postoju-w-dni-parzyste',
                ],
                'takeaways' => [
                    'B-35 zabrania postoju, ale dopuszcza krótkie zatrzymanie.',
                    'B-36 zakazuje już samego zatrzymania pojazdu.',
                    'B-37 i B-38 rozszerzają klaster postoju o zakazy zależne od dnia miesiąca.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'Przy B-35 można zatrzymać pojazd na chwilę, ale nie wolno go pozostawić w postoju. Przy B-36 samo zatrzymanie w strefie obowiązywania znaku jest co do zasady zabronione.',
                    ],
                    [
                        'title' => 'Jak myśleć o B-35',
                        'body' => 'To znak dla miejsc, w których dłuższe pozostawienie auta utrudnia ruch, ale krótkie zatrzymanie jeszcze może być tolerowane. Kierowca powinien jednak rozumieć, że „na chwilę” nie oznacza swobodnego postoju bez nadzoru.',
                    ],
                    [
                        'title' => 'Jak myśleć o B-36',
                        'body' => 'To ostrzejszy znak. W praktyce kierowca nie powinien planować tam nawet krótkiego zatrzymania na odbiór pasażera czy szybkie wysadzenie, jeśli organizacja ruchu nie przewiduje wyjątku.',
                    ],
                    [
                        'title' => 'Co zmieniają B-37 i B-38',
                        'body' => 'Znaki B-37 i B-38 nie zakazują zatrzymywania się tak jak B-36, ale porządkują postój zależnie od dnia parzystego lub nieparzystego. To odrębny, ale powiązany fragment klastra parkingowego.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy przy B-35 mogę wysadzić pasażera?',
                        'answer' => 'Tak, jeśli to krótkie zatrzymanie i nie przeradza się w postój. Trzeba jednak zachować ostrożność i nie zostawiać auta wbrew organizacji ruchu.',
                    ],
                    [
                        'question' => 'Czy przy B-36 mogę stanąć tylko na sekundę?',
                        'answer' => 'Nie. Znak B-36 zakazuje już samego zatrzymania, o ile nie wynika ono z warunków ruchu lub wyjątków przewidzianych w organizacji ruchu.',
                    ],
                ],
            ],
            [
                'slug' => 'b-1-vs-b-2',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-1 a B-2: zakaz ruchu w obu kierunkach i zakaz wjazdu to nie to samo',
                'title' => 'B-1 a B-2 - jak szybko odróżnić te dwa zakazy',
                'description' => 'Wyjaśniamy różnicę między znakiem B-1 Zakaz ruchu w obu kierunkach i B-2 Zakaz wjazdu oraz pokazujemy, jak czytać oba znaki w praktyce.',
                'intro' => 'Na pierwszy rzut oka oba znaki wyglądają jak „nie jedź dalej”, ale działają inaczej. B-1 zamyka ruch pojazdów na danym odcinku, a B-2 zakazuje wjazdu od strony, po której stoi znak.',
                'published_at' => '2026-04-27T19:00:00+02:00',
                'updated_at' => '2026-04-27T19:00:00+02:00',
                'related_sign_slugs' => [
                    'b-1-zakaz-ruchu-w-obu-kierunkach',
                    'b-2-zakaz-wjazdu',
                ],
                'takeaways' => [
                    'B-1 dotyczy zakazu ruchu pojazdów na odcinku drogi.',
                    'B-2 dotyczy zakazu wjazdu od jednej strony.',
                    'Najczęstszy błąd to traktowanie obu znaków jak pełnych odpowiedników.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-1 mówi, że ruch pojazdów na tym odcinku jest zakazany. B-2 mówi, że z tej konkretnej strony nie wolno wjechać dalej. To dwa inne komunikaty organizacji ruchu.',
                    ],
                    [
                        'title' => 'Jak czytać B-1',
                        'body' => 'To znak pełnego zakazu ruchu pojazdów na danym odcinku, chyba że tabliczka przewiduje wyjątek. Kierowca powinien czytać go jak zamknięcie ruchu, a nie jak zakaz „tylko z tej strony”.',
                    ],
                    [
                        'title' => 'Jak czytać B-2',
                        'body' => 'B-2 zakazuje wjazdu od strony ustawienia znaku. Najczęściej prowadzi to do ochrony drogi jednokierunkowej, wyłączenia jednego wlotu albo zamknięcia wjazdu z konkretnego kierunku.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najczęściej kierowcy patrzą na oba znaki jako ogólny zakaz jazdy naprzód. Tymczasem organizacyjnie B-2 bywa powiązany z ruchem jednokierunkowym, a B-1 z pełnym wyłączeniem ruchu pojazdów na odcinku.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy B-2 zawsze oznacza drogę jednokierunkową?',
                        'answer' => 'Nie zawsze. Bardzo często tak bywa, ale B-2 sam w sobie oznacza po prostu zakaz wjazdu od tej strony. Pełen sens wynika z całej organizacji ruchu.',
                    ],
                    [
                        'question' => 'Czy B-1 i B-2 można stosować zamiennie?',
                        'answer' => 'Nie. To dwa różne znaki i dwa różne znaczenia. Właśnie dlatego warto nauczyć się czytać je osobno, a nie jako jeden wspólny zakaz.',
                    ],
                ],
            ],
            [
                'slug' => 'b-3-vs-b-5-vs-b-7',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-3, B-5 i B-7: które pojazdy obejmuje zakaz wjazdu',
                'title' => 'B-3, B-5 i B-7 - różnice między zakazami grupowego wjazdu',
                'description' => 'Porównujemy znaki B-3, B-5 i B-7 oraz pokazujemy, kiedy zakaz dotyczy pojazdów silnikowych, ciężarowych albo zestawów z przyczepą.',
                'intro' => 'Te trzy znaki są do siebie podobne tylko na pierwszy rzut oka. Każdy z nich dotyczy innej grupy pojazdów i właśnie dlatego warto nauczyć się odróżniać je szybko, bez zgadywania przy samym wlocie.',
                'published_at' => '2026-04-27T19:05:00+02:00',
                'updated_at' => '2026-04-27T19:05:00+02:00',
                'related_sign_slugs' => [
                    'b-3-zakaz-wjazdu-pojazdow-silnikowych',
                    'b-5-zakaz-wjazdu-samochodow-ciezarowych',
                    'b-7-zakaz-wjazdu-pojazdow-silnikowych-z-przyczepa',
                ],
                'takeaways' => [
                    'B-3 dotyczy szerokiej grupy pojazdów silnikowych.',
                    'B-5 jest bardziej precyzyjny i odnosi się do samochodów ciężarowych.',
                    'B-7 koncentruje się na pojazdach silnikowych z przyczepą.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-3 obejmuje pojazdy silnikowe jako szerszą kategorię. B-5 dotyczy samochodów ciężarowych. B-7 odnosi się do pojazdów silnikowych z przyczepą. To właśnie grupa pojazdu decyduje o tym, który znak ma zastosowanie.',
                    ],
                    [
                        'title' => 'Po co są trzy osobne znaki',
                        'body' => 'Organizacja ruchu nie zawsze chce zamknąć wjazd dla wszystkich pojazdów silnikowych. Czasem celem jest tylko ciężki transport, a czasem zestawy z przyczepą, które mają problem z promieniem skrętu albo bezpieczeństwem na odcinku.',
                    ],
                    [
                        'title' => 'Jak kierowca powinien to czytać w praktyce',
                        'body' => 'Najpierw trzeba wiedzieć, do jakiej grupy należy Twój pojazd lub zestaw. Dopiero potem da się uczciwie odpowiedzieć, czy znak Cię obejmuje. Zgadywanie na oko bardzo łatwo prowadzi do błędu.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Często kierowcy uznają, że skoro nie jadą klasyczną ciężarówką, to znak ich nie dotyczy. Tymczasem zestaw z przyczepą albo szersza definicja pojazdu silnikowego może wprowadzać inny zakaz niż ten, którego intuicyjnie się spodziewają.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy B-5 i B-7 oznaczają to samo dla ciężarówki z przyczepą?',
                        'answer' => 'Nie zawsze. B-5 dotyczy samochodów ciężarowych, a B-7 pojazdów silnikowych z przyczepą. W praktyce zestaw może podpadać pod oba znaki albo pod jeden z nich, zależnie od układu sytuacji.',
                    ],
                    [
                        'question' => 'Czy B-3 jest szerszy niż B-5?',
                        'answer' => 'Tak. B-3 dotyczy pojazdów silnikowych jako szerszej grupy, dlatego zwykle trzeba go czytać bardziej ogólnie niż B-5 odnoszący się do ciężarówek.',
                    ],
                ],
            ],
            [
                'slug' => 'b-13-vs-b-13a-vs-b-14',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-13, B-13a i B-14: jak rozróżnić zakazy dla materiałów niebezpiecznych',
                'title' => 'B-13, B-13a i B-14 - różnice w znakach dla przewozów niebezpiecznych',
                'description' => 'Wyjaśniamy różnice między znakami B-13, B-13a i B-14 oraz pokazujemy, jak czytać te zakazy w kontekście przewozu materiałów niebezpiecznych i ochrony środowiska.',
                'intro' => 'To bardzo podobna rodzina znaków, ale każdy z nich opisuje inny zakres ryzyka. Jedne dotyczą materiałów wybuchowych lub łatwo zapalnych, inne szerzej materiałów niebezpiecznych, a jeszcze inne ryzyka skażenia wody.',
                'published_at' => '2026-04-27T19:10:00+02:00',
                'updated_at' => '2026-04-27T19:10:00+02:00',
                'related_sign_slugs' => [
                    'b-13-zakaz-wjazdu-pojazdow-z-materialami-wybuchowymi',
                    'b-13a-zakaz-wjazdu-pojazdow-z-materialami-niebezpiecznymi',
                    'b-14-zakaz-wjazdu-pojazdow-z-materialami-mogacymi-skazic-wode',
                ],
                'takeaways' => [
                    'B-13 dotyczy materiałów wybuchowych lub łatwo zapalnych.',
                    'B-13a obejmuje szerzej materiały niebezpieczne.',
                    'B-14 koncentruje się na ryzyku skażenia wody.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-13 jest węższy i skupia się na materiałach wybuchowych lub łatwo zapalnych. B-13a dotyczy szerzej materiałów niebezpiecznych. B-14 odnosi się do przewozów, które mogą skazić wodę, więc jego sens jest silnie środowiskowy.',
                    ],
                    [
                        'title' => 'Dlaczego te znaki nie są tym samym',
                        'body' => 'Choć wszystkie dotyczą przewozu ryzykownych ładunków, ich zakres nie jest identyczny. Organizacja ruchu może chcieć wyłączyć z przejazdu tylko część przewozów, np. z uwagi na tunel, ujęcie wody albo szczególnie wrażliwy obiekt.',
                    ],
                    [
                        'title' => 'Jak kierowca powinien do nich podejść',
                        'body' => 'Kierowca przewożący taki ładunek nie może czytać tych znaków intuicyjnie. Trzeba znać charakter przewozu i dopiero wtedy uczciwie ocenić, czy dany znak obejmuje konkretny pojazd i ładunek.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Największy błąd polega na traktowaniu wszystkich trzech znaków jak jednego wspólnego zakazu ADR. W rzeczywistości ich zakres jest inny i właśnie ta różnica decyduje o prawidłowej trasie przewozu.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy B-13a jest szerszy niż B-13?',
                        'answer' => 'Tak, co do zasady B-13a opisuje szerszą grupę materiałów niebezpiecznych niż B-13 dotyczący materiałów wybuchowych lub łatwo zapalnych.',
                    ],
                    [
                        'question' => 'Czym wyróżnia się B-14?',
                        'answer' => 'B-14 skupia się na materiałach, które mogą skazić wodę. To znak o bardzo wyraźnym kontekście środowiskowym.',
                    ],
                ],
            ],
            [
                'slug' => 'b-15-do-b-19',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-15 do B-19: który parametr pojazdu ogranicza dany znak',
                'title' => 'B-15 do B-19 - szerokość, wysokość, długość, masa i nacisk osi',
                'description' => 'Porównujemy znaki B-15, B-16, B-17, B-18 i B-19, żeby szybko rozdzielić ograniczenia gabarytowe od masowych i osiowych.',
                'intro' => 'Ta grupa znaków wygląda jak jedna rodzina i faktycznie działa podobnie: kierowca musi porównać parametr pojazdu z wartością na znaku. Różnica polega na tym, czy chodzi o szerokość, wysokość, długość, masę całkowitą czy nacisk pojedynczej osi napędowej.',
                'published_at' => '2026-04-27T20:00:00+02:00',
                'updated_at' => '2026-04-27T20:00:00+02:00',
                'related_sign_slugs' => [
                    'b-15-zakaz-wjazdu-pojazdow-o-szerokosci-ponad',
                    'b-16-zakaz-wjazdu-pojazdow-o-wysokosci-ponad',
                    'b-17-zakaz-wjazdu-pojazdow-o-dlugosci-ponad',
                    'b-18-zakaz-wjazdu-pojazdow-o-rzeczywistej-masie-calkowitej-ponad',
                    'b-19-zakaz-wjazdu-pojazdow-o-nacisku-pojedynczej-osi-napedowej-powyzej',
                ],
                'takeaways' => [
                    'B-15, B-16 i B-17 dotyczą wymiarów pojazdu lub zestawu.',
                    'B-18 dotyczy rzeczywistej masy całkowitej pojazdu.',
                    'B-19 dotyczy nacisku pojedynczej osi napędowej, a nie całkowitej masy.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-15 ogranicza szerokość. B-16 ogranicza wysokość. B-17 ogranicza długość. B-18 odnosi się do rzeczywistej masy całkowitej pojazdu, a B-19 do nacisku pojedynczej osi napędowej. Każdy znak sprawdza inny parametr techniczny.',
                    ],
                    [
                        'title' => 'Które znaki są gabarytowe, a które masowe',
                        'body' => 'B-15, B-16 i B-17 należą do rodziny gabarytowej, bo pilnują rozmiaru pojazdu lub zestawu. B-18 i B-19 przechodzą w ograniczenia obciążenia: pierwszy patrzy na masę całkowitą, drugi na nacisk skupiony na osi napędowej.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka kierowcy',
                        'body' => 'Najwięcej pomyłek bierze się z założenia, że skoro pojazd mieści się w jednym limicie, to powinien mieścić się we wszystkich. Tymczasem można spełniać warunek szerokości, a nie spełniać warunku wysokości albo nacisku osiowego.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czym B-18 różni się od B-19?',
                        'answer' => 'B-18 dotyczy rzeczywistej masy całkowitej pojazdu, a B-19 nacisku pojedynczej osi napędowej. To dwa różne parametry techniczne.',
                    ],
                    [
                        'question' => 'Czy przy tej rodzinie znaków trzeba znać parametry zestawu, a nie tylko samego auta?',
                        'answer' => 'Tak. W praktyce trzeba brać pod uwagę rzeczywisty parametr całego pojazdu lub zestawu, a nie tylko bazowe dane ciągnika albo samochodu bez ładunku.',
                    ],
                ],
            ],
            [
                'slug' => 'b-25-do-b-28',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-25 do B-28: jak czytać zakazy wyprzedzania i ich odwołanie',
                'title' => 'B-25 do B-28 - zakazy wyprzedzania dla wszystkich pojazdów i ciężarówek',
                'description' => 'Wyjaśniamy różnicę między B-25, B-26, B-27 i B-28 oraz pokazujemy, kiedy zakaz dotyczy wszystkich pojazdów, a kiedy tylko samochodów ciężarowych.',
                'intro' => 'Ta rodzina znaków dobrze pokazuje, że sam symbol zakazu to dopiero początek. Trzeba jeszcze wiedzieć, kogo dotyczy zakaz i który znak go odwołuje: ogólny, czy tylko dla ciężarówek.',
                'published_at' => '2026-04-27T20:05:00+02:00',
                'updated_at' => '2026-04-27T20:05:00+02:00',
                'related_sign_slugs' => [
                    'b-25-zakaz-wyprzedzania',
                    'b-26-zakaz-wyprzedzania-przez-samochody-ciezarowe',
                    'b-27-koniec-zakazu-wyprzedzania',
                    'b-28-koniec-zakazu-wyprzedzania-przez-samochody-ciezarowe',
                ],
                'takeaways' => [
                    'B-25 dotyczy ogólnego zakazu wyprzedzania.',
                    'B-26 zawęża zakaz do samochodów ciężarowych.',
                    'B-27 i B-28 odwołują odpowiednio właściwy zakaz.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-25 zakazuje wyprzedzania szerzej. B-26 skupia się na samochodach ciężarowych. B-27 kończy zakaz z B-25, a B-28 kończy zakaz z B-26. Kluczowe jest nie tylko to, że zakaz istnieje, ale też kogo obejmuje.',
                    ],
                    [
                        'title' => 'Jak nie pomylić znaków kończących',
                        'body' => 'Najczęstszy błąd polega na tym, że kierowca widzi znak kończący i zakłada, że wszystko wróciło do pełnej swobody. Tymczasem trzeba sprawdzić, czy odwołany został zakaz ogólny, czy tylko ten dla samochodów ciężarowych.',
                    ],
                    [
                        'title' => 'Kiedy ten klaster jest szczególnie ważny',
                        'body' => 'Największe znaczenie ma na dłuższych odcinkach poza miastem, gdzie znaki wyprzedzania działają razem z geometrią drogi, widocznością i ruchem ciężkim. To dlatego materiał porównawczy jest tu tak ważny dla praktyki kierowcy.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy B-28 kończy także ogólny zakaz z B-25?',
                        'answer' => 'Nie. B-28 kończy zakaz wyprzedzania przez samochody ciężarowe. Trzeba uważać, żeby nie pomylić go z B-27.',
                    ],
                    [
                        'question' => 'Czy B-26 dotyczy każdego dużego pojazdu?',
                        'answer' => 'Nie warto zgadywać. Znak dotyczy samochodów ciężarowych, więc trzeba czytać go przez pryzmat kategorii pojazdu, a nie tylko jego rozmiaru na oko.',
                    ],
                ],
            ],
            [
                'slug' => 'b-29-vs-b-30',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-29 a B-30: gdzie kończy się zakaz używania sygnałów dźwiękowych',
                'title' => 'B-29 a B-30 - początek i koniec zakazu używania klaksonu',
                'description' => 'Porównujemy znaki B-29 i B-30 oraz pokazujemy, jak kierowca powinien czytać zakaz używania sygnałów dźwiękowych w praktyce.',
                'intro' => 'To prosty duet, ale bardzo praktyczny. B-29 wprowadza zakaz używania sygnałów dźwiękowych, a B-30 informuje, że ten zakaz już się skończył. Najwięcej błędów bierze się z ignorowania wyjątków wynikających z bezpieczeństwa.',
                'published_at' => '2026-04-27T20:10:00+02:00',
                'updated_at' => '2026-04-27T20:10:00+02:00',
                'related_sign_slugs' => [
                    'b-29-zakaz-uzywania-sygnalow-dzwiekowych',
                    'b-30-koniec-zakazu-uzywania-sygnalow-dzwiekowych',
                ],
                'takeaways' => [
                    'B-29 wprowadza zakaz używania sygnałów dźwiękowych.',
                    'B-30 kończy ten zakaz.',
                    'Nawet przy B-29 bezpieczeństwo ruchu nadal pozostaje nadrzędne.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-29 mówi, że od tego miejsca nie używamy sygnałów dźwiękowych w zwykłej jeździe. B-30 kończy ten zakaz i zamyka wcześniejsze ograniczenie.',
                    ],
                    [
                        'title' => 'Dlaczego ten znak bywa źle rozumiany',
                        'body' => 'Kierowcy często traktują B-29 jako całkowity zakaz klaksonu bez wyjątków. Tymczasem w realnym ruchu zawsze trzeba pamiętać o nadrzędnym celu, jakim jest bezpieczeństwo. Strona znaku powinna być tu konkretna i spokojna, bez nadinterpretacji.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy przy B-29 naprawdę nigdy nie można użyć sygnału dźwiękowego?',
                        'answer' => 'Nie należy go używać zwyczajowo, ale bezpieczeństwo ruchu pozostaje kluczowe. Właśnie dlatego ten znak trzeba czytać rozsądnie, a nie mechanicznie.',
                    ],
                    [
                        'question' => 'Co robi B-30?',
                        'answer' => 'B-30 informuje, że wcześniejszy zakaz używania sygnałów dźwiękowych już nie obowiązuje.',
                    ],
                ],
            ],
            [
                'slug' => 'b-39-vs-b-40',
                'kicker' => 'Porównanie znaków',
                'headline' => 'B-39 a B-40: jak działa strefa ograniczonego postoju',
                'title' => 'B-39 a B-40 - początek i koniec strefy ograniczonego postoju',
                'description' => 'Wyjaśniamy różnicę między B-39 i B-40 oraz pokazujemy, czym strefa ograniczonego postoju różni się od pojedynczych zakazów postoju.',
                'intro' => 'Znaki B-39 i B-40 nie działają jak pojedynczy zakaz stojący przy jednej krawędzi jezdni. To logika strefowa: kierowca wjeżdża do obszaru z określonymi zasadami postoju, a potem z niego wyjeżdża dopiero przy znaku kończącym.',
                'published_at' => '2026-04-27T20:15:00+02:00',
                'updated_at' => '2026-04-27T20:15:00+02:00',
                'related_sign_slugs' => [
                    'b-39-strefa-ograniczonego-postoju',
                    'b-40-koniec-strefy-ograniczonego-postoju',
                    'b-35-zakaz-postoju',
                    'b-36-zakaz-zatrzymywania-sie',
                ],
                'takeaways' => [
                    'B-39 wprowadza zasady postoju w całej strefie.',
                    'B-40 kończy te zasady.',
                    'To nie to samo co pojedynczy znak B-35 albo B-36 przy konkretnym odcinku drogi.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'B-39 rozpoczyna strefę ograniczonego postoju, a B-40 ją kończy. Kierowca powinien myśleć o tych znakach obszarowo, a nie punktowo.',
                    ],
                    [
                        'title' => 'Czym strefa różni się od pojedynczego zakazu',
                        'body' => 'Przy zwykłym zakazie postoju kierowca często patrzy na jeden odcinek i szuka momentu odwołania. Przy strefie ważne jest to, że zasady postoju rozciągają się na cały obszar objęty oznakowaniem, aż do znaku kończącego.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najwięcej błędów bierze się z założenia, że po kilku skrzyżowaniach zakaz już "na pewno minął". Właśnie dlatego warto mieć osobny materiał porównawczy dla B-39 i B-40.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy B-39 działa tylko do najbliższego skrzyżowania?',
                        'answer' => 'Nie. To logika strefowa, więc trzeba szukać znaku kończącego, a nie zakładać, że ograniczenie wygasa samo po kilku metrach.',
                    ],
                    [
                        'question' => 'Czy B-40 działa jak B-34 albo B-44 dla postoju?',
                        'answer' => 'W pewnym sensie tak: informuje o końcu wcześniejszego ograniczenia, ale trzeba pamiętać, że dotyczy strefy ograniczonego postoju, a nie zwykłego punktowego zakazu.',
                    ],
                ],
            ],
            [
                'slug' => 'c-1-do-c-4',
                'kicker' => 'Porównanie znaków',
                'headline' => 'C-1 do C-4: kiedy znak nakazuje jazdę w danym kierunku, a kiedy sam skręt',
                'title' => 'C-1 do C-4 - kiedy znak nakazuje jazdę, a kiedy sam skręt',
                'description' => 'Porównujemy znaki C-1, C-2, C-3 i C-4, żeby rozdzielić obowiązek jazdy w prawo lub w lewo od obowiązku wykonania samego skrętu na najbliższym wlocie.',
                'intro' => 'To jedna z tych rodzin znaków, które wyglądają podobnie, ale sterują ruchem trochę inaczej. Kierowca musi odróżnić znak prowadzący dalszy przebieg toru jazdy od znaku, który nakazuje wykonać konkretny skręt już na najbliższym skrzyżowaniu albo wlocie.',
                'published_at' => '2026-04-29T10:00:00+02:00',
                'updated_at' => '2026-04-29T10:00:00+02:00',
                'related_sign_slugs' => [
                    'c-1-nakaz-jazdy-w-prawo',
                    'c-2-nakaz-skrecania-w-prawo',
                    'c-3-nakaz-jazdy-w-lewo',
                    'c-4-nakaz-skrecania-w-lewo',
                ],
                'takeaways' => [
                    'C-1 i C-3 ustawiają dalszy kierunek jazdy odpowiednio w prawo albo w lewo.',
                    'C-2 i C-4 dotyczą wykonania konkretnego skrętu na najbliższej relacji ruchu.',
                    'Najwięcej błędów bierze się z czytania tych czterech znaków jak jednej wspólnej rodziny bez rozróżnienia momentu manewru.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'C-1 i C-3 mówią, w którą stronę ma dalej prowadzić tor jazdy kierowcy. C-2 i C-4 są bardziej manewrowe: nakazują sam skręt odpowiednio w prawo albo w lewo już w miejscu objętym znakiem. To podobne symbole, ale inny akcent decyzji za kierownicą.',
                    ],
                    [
                        'title' => 'Jak czytać znaki jazdy i znaki skrętu',
                        'body' => 'Przy C-1 i C-3 kierowca wcześniej układa się pod obowiązkowy kierunek dalszego przejazdu. Przy C-2 i C-4 kluczowe jest to, że znak prowadzi do konkretnego skrętu na najbliższym wlocie. W praktyce warto czytać je razem z pasem ruchu, geometrią wyspy i oznakowaniem poziomym.',
                    ],
                    [
                        'title' => 'Gdzie najczęściej pojawia się pomyłka',
                        'body' => 'Najwięcej nieporozumień bierze się z utożsamiania C-1 z C-2 oraz C-3 z C-4. Kierowca widzi strzałkę w prawo albo w lewo i nie dopowiada sobie, czy chodzi o sam kierunek dalszej jazdy, czy o obowiązkowy skręt na najbliższym skrzyżowaniu.',
                    ],
                    [
                        'title' => 'Dlaczego to ważne na egzaminie i w mieście',
                        'body' => 'W ruchu miejskim takie znaki często występują nad pasami albo tuż przed wyspami kanalizującymi ruch. Jeśli kierowca odczyta je za późno, zaczyna improwizować przy samym skrzyżowaniu. Właśnie dlatego ten klaster dobrze pracuje i pod praktykę, i pod pytania egzaminacyjne.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy C-1 i C-2 oznaczają to samo?',
                        'answer' => 'Nie. C-1 nakazuje jazdę w prawo, a C-2 nakazuje wykonanie skrętu w prawo na najbliższej relacji objętej znakiem.',
                    ],
                    [
                        'question' => 'Jak odróżnić C-3 od C-4 w praktyce?',
                        'answer' => 'Najprościej przez pytanie, czy znak prowadzi dalszy kierunek jazdy, czy sam manewr skrętu. C-3 ustawia jazdę w lewo, a C-4 nakazuje wykonać lewy skręt.',
                    ],
                ],
            ],
            [
                'slug' => 'c-5-do-c-8',
                'kicker' => 'Porównanie znaków',
                'headline' => 'C-5 do C-8: jak czytać nakazy z jednym kierunkiem prostym i z wariantami bocznymi',
                'title' => 'C-5 do C-8 - jazda prosto, dwa warianty i zakaz jazdy na wprost',
                'description' => 'Wyjaśniamy różnicę między znakami C-5, C-6, C-7 i C-8 oraz pokazujemy, kiedy kierowca może jechać prosto, kiedy wybiera jeden z dwóch wariantów, a kiedy jazda na wprost odpada.',
                'intro' => 'Ta rodzina znaków jest bardzo praktyczna, bo działa dokładnie tam, gdzie kierowca musi wcześniej zdecydować o pasie ruchu. Wszystkie znaki wyglądają jak warianty tego samego pomysłu, ale każdy zostawia trochę inny zestaw dopuszczalnych kierunków.',
                'published_at' => '2026-04-29T10:05:00+02:00',
                'updated_at' => '2026-04-29T10:05:00+02:00',
                'related_sign_slugs' => [
                    'c-5-nakaz-jazdy-prosto',
                    'c-6-nakaz-jazdy-prosto-lub-w-prawo',
                    'c-7-nakaz-jazdy-prosto-lub-w-lewo',
                    'c-8-nakaz-jazdy-w-lewo-lub-w-prawo',
                ],
                'takeaways' => [
                    'C-5 zostawia wyłącznie jazdę prosto.',
                    'C-6 i C-7 dopuszczają jazdę prosto plus jeden kierunek boczny.',
                    'C-8 wyklucza jazdę na wprost i zostawia tylko dwa skręty.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'C-5 oznacza wyłącznie jazdę prosto. C-6 pozwala pojechać prosto albo w prawo, a C-7 prosto albo w lewo. C-8 zostawia tylko dwa skręty: w lewo albo w prawo. Najważniejsze jest to, czy jazda na wprost pozostaje dostępna, czy została wyłączona.',
                    ],
                    [
                        'title' => 'Jak wcześnie podjąć decyzję',
                        'body' => 'Przy tej rodzinie znaków najwięcej zyskuje kierowca, który czyta pas jeszcze przed dojazdem do linii zatrzymania. Jeśli decyzja o kierunku zapada dopiero przy samym skrzyżowaniu, kończy się to gwałtowną korektą toru jazdy albo próbą ucieczki na niewłaściwy pas.',
                    ],
                    [
                        'title' => 'Które znaki najłatwiej pomylić',
                        'body' => 'Najczęściej mylą się C-6, C-7 i C-8, bo wszystkie zostawiają dwa warianty jazdy. Różnica jest jednak prosta: przy C-6 i C-7 możesz nadal pojechać prosto, a przy C-8 taka relacja już znika.',
                    ],
                    [
                        'title' => 'Jak ten klaster pomaga na egzaminie',
                        'body' => 'To dobre porównanie do pytań o organizację pasa ruchu. Egzaminator patrzy nie tylko na nazwę znaku, ale na to, czy kierowca z wyprzedzeniem ustawia pojazd i nie próbuje wykonywać manewru spoza dopuszczonych kierunków.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Który znak z tej grupy wyklucza jazdę prosto?',
                        'answer' => 'C-8. Ten znak zostawia tylko skręt w lewo albo w prawo.',
                    ],
                    [
                        'question' => 'Czym C-6 różni się od C-7?',
                        'answer' => 'Oba znaki dopuszczają jazdę prosto, ale C-6 dodaje wariant w prawo, a C-7 wariant w lewo.',
                    ],
                ],
            ],
            [
                'slug' => 'c-9-do-c-11',
                'kicker' => 'Porównanie znaków',
                'headline' => 'C-9 do C-11: z której strony wolno ominąć przeszkodę',
                'title' => 'C-9 do C-11 - prawa, lewa czy obie strony objazdu przeszkody',
                'description' => 'Porównujemy znaki C-9, C-10 i C-11, żeby szybko rozdzielić obowiązkowy objazd przeszkody z prawej, z lewej albo z obu stron.',
                'intro' => 'To mała, ale bardzo praktyczna rodzina znaków. Na pierwszy rzut oka wszystkie mówią o ominięciu przeszkody, ale z perspektywy kierowcy różnica jest zasadnicza: czasem wariant jest tylko jeden, a czasem organizacja ruchu zostawia dwa dopuszczalne tory przejazdu.',
                'published_at' => '2026-04-29T10:10:00+02:00',
                'updated_at' => '2026-04-29T10:10:00+02:00',
                'related_sign_slugs' => [
                    'c-9-nakaz-objazdu-przeszkody-z-prawej-strony',
                    'c-10-nakaz-objazdu-przeszkody-z-lewej-strony',
                    'c-11-nakaz-objazdu-przeszkody-z-obu-stron',
                ],
                'takeaways' => [
                    'C-9 prowadzi wyłącznie na prawą stronę przeszkody.',
                    'C-10 prowadzi wyłącznie na lewą stronę przeszkody.',
                    'C-11 pozwala wybrać jedną z dwóch stron, ale nadal wymaga wcześniejszej, czytelnej decyzji.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'C-9 i C-10 narzucają jeden konkretny wariant objazdu przeszkody. C-11 zostawia dwa warianty i pozwala ominąć ją z obu stron. To prosta rodzina znaków, ale bardzo ważna przy robotach drogowych, wyspach i zwężeniach.',
                    ],
                    [
                        'title' => 'Dlaczego decyzja musi paść wcześniej',
                        'body' => 'Przy objazdach najwięcej szkód robi spóźniona reakcja. Kierowca, który czyta znak dopiero przy samej przeszkodzie, zaczyna gwałtownie korygować tor jazdy. To właśnie dlatego znaki C-9 do C-11 trzeba czytać kilka sekund przed punktem manewru, a nie tuż przy nim.',
                    ],
                    [
                        'title' => 'Co zmienia C-11',
                        'body' => 'C-11 daje większą elastyczność, ale nie oznacza pełnego chaosu. Nadal trzeba zdecydować, która strona będzie bezpieczniejsza przy aktualnym ruchu, i nie można przeskakiwać z jednego wariantu na drugi w ostatnim momencie.',
                    ],
                    [
                        'title' => 'Najczęstsza pułapka',
                        'body' => 'Najczęściej kierowcy czytają całą rodzinę jako ogólne „trzeba coś objechać”. W praktyce to za mało. Właśnie strona objazdu decyduje, czy manewr będzie zgodny z organizacją ruchu i czy nie wejdziemy w konflikt z pojazdem nadjeżdżającym z przeciwka.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy przy C-11 mogę wybrać dowolną stronę w ostatniej chwili?',
                        'answer' => 'Nie warto tak robić. C-11 dopuszcza oba warianty, ale manewr nadal powinien być przewidywalny i podjęty odpowiednio wcześnie.',
                    ],
                    [
                        'question' => 'Jak odróżnić C-9 od C-10 bez zastanawiania się nad nazwą znaku?',
                        'answer' => 'Najprościej patrzeć na stronę, po której znak prowadzi tor jazdy wokół przeszkody: C-9 w prawo, a C-10 w lewo.',
                    ],
                ],
            ],
            [
                'slug' => 'c-13-do-c-16a',
                'kicker' => 'Porównanie znaków',
                'headline' => 'C-13 do C-16a: droga dla rowerów, pieszych i wspólne ciągi z ich końcem',
                'title' => 'C-13 do C-16a - kto korzysta z drogi i kiedy kończy się wydzielony ciąg',
                'description' => 'Wyjaśniamy różnicę między znakami C-13, C-13a, C-13/16, C-13a/16a, C-16 i C-16a oraz pokazujemy, jak czytać początek i koniec dróg dla rowerów, pieszych i ciągów wspólnych.',
                'intro' => 'To klaster, który mocno pracuje na zrozumienie otoczenia drogi. Kierowca nie powinien traktować tych znaków jak informacji wyłącznie dla pieszego albo rowerzysty. One podpowiadają również, gdzie i jak zmieniają się relacje ruchu niechronionych uczestników względem jezdni.',
                'published_at' => '2026-04-29T10:15:00+02:00',
                'updated_at' => '2026-04-29T10:15:00+02:00',
                'related_sign_slugs' => [
                    'c-13-droga-dla-rowerow',
                    'c-13a-koniec-drogi-dla-rowerow',
                    'c-13-16-droga-dla-rowerow-i-pieszych',
                    'c-13a-16a-koniec-drogi-dla-rowerow-i-pieszych',
                    'c-16-droga-dla-pieszych',
                    'c-16a-koniec-drogi-dla-pieszych',
                ],
                'takeaways' => [
                    'C-13 i C-16 wskazują odpowiednio drogę dla rowerów oraz drogę dla pieszych.',
                    'C-13/16 opisuje wspólny albo łączony ciąg dla rowerów i pieszych.',
                    'Znaki z końcówką a lub a/16a mówią o końcu wcześniejszej organizacji ruchu, a więc o zmianie relacji uczestników względem jezdni.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'C-13 dotyczy rowerów, C-16 pieszych, a C-13/16 wspólnego albo łączonego ciągu dla obu grup. Warianty C-13a, C-16a i C-13a/16a nie wprowadzają nowego ciągu, tylko kończą wcześniejszą organizację ruchu i sygnalizują zmianę układu na odcinku.',
                    ],
                    [
                        'title' => 'Dlaczego kierowca też powinien to czytać uważnie',
                        'body' => 'Te znaki opisują otoczenie skrzyżowania, przejazdu albo zjazdu. Jeśli kończy się droga dla rowerów lub wspólny ciąg, zmienia się sposób, w jaki pieszy albo rowerzysta może pojawić się przy jezdni. To właśnie z takiej zmiany bierze się wiele drobnych, ale kosztownych pomyłek w obserwacji.',
                    ],
                    [
                        'title' => 'Które znaki najłatwiej pomylić',
                        'body' => 'Najczęściej mieszają się C-13 z C-13/16 oraz końce tych ciągów, bo kierowca widzi rodzinę niebieskich znaków i nie rozdziela, czy chodzi o samych rowerzystów, samych pieszych, czy obie grupy jednocześnie. Przy znakach kończących dodatkowo łatwo przeoczyć, że za nimi organizacja ruchu już wygląda inaczej.',
                    ],
                    [
                        'title' => 'Jak ten materiał pomaga w praktyce',
                        'body' => 'To nie jest tylko teoria o symbolach. Dobrze uporządkowany klaster C-13 do C-16a pomaga lepiej czytać wloty dróg osiedlowych, zjazdy z posesji i przejazdy przez ciągi pieszo-rowerowe, czyli miejsca, w których kierowca musi szybko zauważyć zmianę relacji z pieszym i rowerzystą.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy C-13/16 oznacza to samo co C-13 albo C-16?',
                        'answer' => 'Nie. C-13/16 dotyczy wspólnego albo łączonego ciągu dla rowerów i pieszych, a nie wyłącznie jednej z tych grup.',
                    ],
                    [
                        'question' => 'Po co kierowcy znak końca drogi dla rowerów albo pieszych?',
                        'answer' => 'Bo za takim znakiem zmienia się organizacja ruchu i miejsce, w którym pieszy lub rowerzysta może pojawić się względem jezdni.',
                    ],
                ],
            ],
            [
                'slug' => 'c-14-vs-c-15',
                'kicker' => 'Porównanie znaków',
                'headline' => 'C-14 a C-15: kiedy obowiązuje prędkość minimalna i gdzie kończy się ten nakaz',
                'title' => 'C-14 a C-15 - początek i koniec prędkości minimalnej',
                'description' => 'Porównujemy znaki C-14 i C-15 oraz pokazujemy, jak czytać obowiązek jazdy z prędkością minimalną bez mylenia go z limitem maksymalnym.',
                'intro' => 'To prosty duet, ale bardzo przydatny. Jeden znak wprowadza obowiązek utrzymywania co najmniej wskazanej prędkości, drugi ten obowiązek kończy. Najwięcej pomyłek bierze się z mieszania prędkości minimalnej z ograniczeniem prędkości maksymalnej.',
                'published_at' => '2026-04-29T10:20:00+02:00',
                'updated_at' => '2026-04-29T10:20:00+02:00',
                'related_sign_slugs' => [
                    'c-14-predkosc-minimalna-40',
                    'c-15-koniec-predkosci-minimalnej',
                ],
                'takeaways' => [
                    'C-14 wprowadza obowiązek jazdy z prędkością co najmniej wskazaną na znaku.',
                    'C-15 kończy wcześniejszy nakaz minimalnej prędkości.',
                    'To nie jest ta sama logika co przy ograniczeniu maksymalnej prędkości z grupy B.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'C-14 zaczyna odcinek, na którym trzeba utrzymywać co najmniej wskazaną prędkość, jeśli pozwalają na to warunki ruchu i bezpieczeństwo. C-15 odwołuje ten obowiązek. To znak początku i znak końca jednej, konkretnej zasady ruchu.',
                    ],
                    [
                        'title' => 'Czego nie wolno pomylić',
                        'body' => 'Najczęstszy błąd to czytanie C-14 tak, jakby oznaczał zwykły limit maksymalny. Tymczasem chodzi o minimalne tempo jazdy. Z drugiej strony C-15 nie wprowadza nowej wartości, tylko mówi, że wcześniejszy obowiązek już nie obowiązuje.',
                    ],
                    [
                        'title' => 'Jak stosować to w praktyce',
                        'body' => 'Kierowca nie może zasłaniać się C-14, jeśli warunki na drodze wymagają ostrożniejszej jazdy. Minimalna prędkość działa rozsądnie, a nie mechanicznie. Po C-15 nie ma z kolei powodu do nagłego zwolnienia, bo to tylko koniec wcześniejszego nakazu.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy C-14 zawsze wymaga jazdy dokładnie z liczbą ze znaku?',
                        'answer' => 'Nie. Wymaga jazdy co najmniej z taką prędkością, o ile pozwalają na to warunki ruchu i bezpieczeństwo.',
                    ],
                    [
                        'question' => 'Czy C-15 oznacza nowy limit prędkości?',
                        'answer' => 'Nie. Oznacza tylko koniec wcześniejszego nakazu minimalnej prędkości.',
                    ],
                ],
            ],
            [
                'slug' => 'c-18-vs-c-19',
                'kicker' => 'Porównanie znaków',
                'headline' => 'C-18 a C-19: kiedy trzeba używać łańcuchów i kiedy kończy się ten obowiązek',
                'title' => 'C-18 a C-19 - nakaz używania łańcuchów i jego odwołanie',
                'description' => 'Wyjaśniamy różnicę między znakami C-18 i C-19 oraz pokazujemy, jak kierowca powinien czytać początek i koniec obowiązku używania łańcuchów przeciwślizgowych.',
                'intro' => 'To drugi prosty duet z kategorii C, ale bardzo ważny zimą i w górach. Jeden znak nakazuje używanie łańcuchów przeciwślizgowych, a drugi informuje, że taki obowiązek już się skończył. Największy błąd polega na ignorowaniu samego początku obowiązywania albo na uznaniu, że koniec nakazu oznacza idealne warunki na drodze.',
                'published_at' => '2026-04-29T10:25:00+02:00',
                'updated_at' => '2026-04-29T10:25:00+02:00',
                'related_sign_slugs' => [
                    'c-18-nakaz-uzywania-lancuchow-przeciwslizgowych',
                    'c-19-koniec-nakazu-uzywania-lancuchow-przeciwslizgowych',
                ],
                'takeaways' => [
                    'C-18 wprowadza obowiązek używania łańcuchów przeciwślizgowych.',
                    'C-19 kończy ten obowiązek.',
                    'Koniec nakazu nie oznacza automatycznie końca trudnych warunków na drodze.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'C-18 oznacza, że na oznaczonym odcinku trzeba używać łańcuchów przeciwślizgowych. C-19 informuje, że ten obowiązek już się skończył. To para znaków działająca dokładnie jak początek i koniec jednej zasady ruchu na trudnym odcinku.',
                    ],
                    [
                        'title' => 'Jak nie zlekceważyć C-18',
                        'body' => 'Największy problem pojawia się wtedy, gdy kierowca uznaje, że nawierzchnia „wygląda jeszcze dobrze” i próbuje jechać bez łańcuchów. Ten znak trzeba czytać dosłownie i przygotować pojazd przed wjazdem na odcinek objęty nakazem, a nie dopiero wtedy, gdy auto traci przyczepność.',
                    ],
                    [
                        'title' => 'Co naprawdę mówi C-19',
                        'body' => 'C-19 kończy formalny obowiązek używania łańcuchów, ale nie obiecuje suchych i prostych warunków. Kierowca nadal powinien czytać nawierzchnię, temperaturę i geometrię drogi. Znak kończy nakaz, a nie ostrożność.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy mogę zignorować C-18, jeśli mam dobre opony zimowe?',
                        'answer' => 'Nie. Znak wprowadza obowiązek używania łańcuchów przeciwślizgowych na oznaczonym odcinku.',
                    ],
                    [
                        'question' => 'Czy po C-19 mogę od razu wrócić do zwykłego tempa jazdy?',
                        'answer' => 'Nie zawsze. Znak kończy obowiązek łańcuchów, ale warunki drogowe nadal mogą wymagać ostrożnej jazdy.',
                    ],
                ],
            ],
            [
                'slug' => 'd-1-vs-d-2',
                'kicker' => 'Porównanie znaków',
                'headline' => 'D-1 a D-2: kiedy droga ma pierwszeństwo, a kiedy ten status się kończy',
                'title' => 'D-1 a D-2 - kiedy droga ma pierwszeństwo, a kiedy ten status się kończy',
                'description' => 'Porównujemy znaki D-1 i D-2, żeby szybko odróżnić potwierdzenie drogi z pierwszeństwem od informacji o zakończeniu tego statusu.',
                'intro' => 'To prosty, ale bardzo ważny duet. D-1 buduje pewność, że kierowca jedzie drogą z pierwszeństwem. D-2 od razu tę pewność odbiera i przypomina, że dalsze skrzyżowania trzeba czytać od nowa. Najwięcej błędów bierze się z jazdy na pamięć po minięciu znaku kończącego pierwszeństwo.',
                'published_at' => '2026-04-29T18:00:00+02:00',
                'updated_at' => '2026-04-29T18:00:00+02:00',
                'related_sign_slugs' => [
                    'd-1-droga-z-pierwszenstwem',
                    'd-2-koniec-drogi-z-pierwszenstwem',
                ],
                'takeaways' => [
                    'D-1 potwierdza, że jedziesz drogą z pierwszeństwem.',
                    'D-2 kończy wcześniejszy status drogi z pierwszeństwem.',
                    'Po D-2 trzeba wrócić do aktywnego czytania oznakowania na kolejnych skrzyżowaniach.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'D-1 mówi kierowcy, że jedzie drogą z pierwszeństwem. D-2 informuje, że ten status już się skończył. To nie są dwa warianty tego samego znaku, tylko początek i koniec jednej ważnej informacji o relacji do pozostałych wlotów.',
                    ],
                    [
                        'title' => 'Jak zachowuje się kierowca po D-1',
                        'body' => 'Po D-1 można prowadzić jazdę pewnie, ale nie bezrefleksyjnie. Znak daje przewagę w relacji pierwszeństwa, ale nie zwalnia z obserwacji pojazdów, które mogą popełnić błąd lub wymusić wjazd.',
                    ],
                    [
                        'title' => 'Co zmienia D-2 w praktyce',
                        'body' => 'D-2 to sygnał, że od tego miejsca nie wolno już zakładać dalszego pierwszeństwa bez potwierdzenia innym oznakowaniem. Kierowca powinien „zresetować” wcześniejsze założenie i ponownie czytać układ skrzyżowania od początku.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy D-2 oznacza, że od razu tracę pierwszeństwo na następnym skrzyżowaniu?',
                        'answer' => 'D-2 kończy wcześniejszy status drogi z pierwszeństwem. Dalszą relację trzeba ocenić już na podstawie kolejnych znaków i samego układu skrzyżowania.',
                    ],
                    [
                        'question' => 'Czy po D-1 mogę przestać obserwować wloty podporządkowane?',
                        'answer' => 'Nie. Znak daje informację o pierwszeństwie, ale nie zwalnia z obserwacji i przewidywania błędów innych kierowców.',
                    ],
                ],
            ],
            [
                'slug' => 'd-4a-vs-d-4b',
                'kicker' => 'Porównanie znaków',
                'headline' => 'D-4a a D-4b: kiedy sama droga jest ślepa, a kiedy ślepy jest tylko boczny wlot',
                'title' => 'D-4a a D-4b - droga bez przejazdu i wjazd na drogę bez przejazdu',
                'description' => 'Wyjaśniamy różnicę między znakami D-4a i D-4b oraz pokazujemy, jak kierowca powinien czytać informację o ślepej ulicy z perspektywy całego odcinka i samego skrzyżowania.',
                'intro' => 'Oba znaki mówią o braku dalszego przejazdu, ale robią to z innej perspektywy. D-4a opisuje samą drogę bez przejazdu. D-4b uprzedza, że konkretny boczny wlot prowadzi właśnie na taki odcinek. To drobiazg, który bardzo pomaga w planowaniu trasy i unikaniu niepotrzebnych manewrów.',
                'published_at' => '2026-04-29T18:05:00+02:00',
                'updated_at' => '2026-04-29T18:05:00+02:00',
                'related_sign_slugs' => [
                    'd-4a-droga-bez-przejazdu',
                    'd-4b-wjazd-na-droge-bez-przejazdu',
                ],
                'takeaways' => [
                    'D-4a informuje, że droga sama w sobie nie ma dalszego przejazdu.',
                    'D-4b ostrzega, że boczny wlot prowadzi na drogę bez przejazdu.',
                    'Najwięcej błędów bierze się z niedoczytania, którego odcinka dotyczy informacja o ślepej ulicy.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'D-4a opisuje całą drogę bez przejazdu. D-4b odnosi się do bocznego wlotu i uprzedza, że po skręcie w ten odcinek trafisz właśnie na drogę ślepą. Kluczowe jest więc to, czy znak opisuje drogę, na której już jesteś, czy relację boczną widoczną na skrzyżowaniu.',
                    ],
                    [
                        'title' => 'Jak to czytać na skrzyżowaniu',
                        'body' => 'Przy D-4b trzeba wcześniej zrozumieć, że znak dotyczy konkretnego skrętu. To pomaga nie tracić czasu na wjazd w ślepą ulicę, jeśli szukasz przejazdu przelotowego albo miejsca do płynnej dalszej jazdy.',
                    ],
                    [
                        'title' => 'Dlaczego D-4a nadal jest praktyczny',
                        'body' => 'D-4a bywa lekceważony jako mało ważna informacja, a tymczasem pozwala wcześniej zaplanować zawracanie, dostawę, parkowanie albo rezygnację z błędnej trasy. To znak organizacyjny, który dobrze czytany oszczędza nerwowe manewry na końcu ulicy.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy D-4b dotyczy drogi, po której już jadę?',
                        'answer' => 'Nie. Znak odnosi się do bocznego wlotu prowadzącego na drogę bez przejazdu.',
                    ],
                    [
                        'question' => 'Czy D-4a zabrania wjazdu w ślepą ulicę?',
                        'answer' => 'Nie. To znak informacyjny, a nie zakaz. Pokazuje tylko, że dalej nie będzie przejazdu przelotowego.',
                    ],
                ],
            ],
            [
                'slug' => 'd-6-vs-d-6a',
                'kicker' => 'Porównanie znaków',
                'headline' => 'D-6 a D-6a: pieszy i rowerzysta nie wchodzą do tej samej sytuacji drogowej',
                'title' => 'D-6 a D-6a - przejście dla pieszych i przejazd dla rowerzystów',
                'description' => 'Porównujemy znaki D-6 i D-6a, żeby rozdzielić przejście dla pieszych od przejazdu dla rowerzystów i pokazać, jak zmienia się obserwacja kierowcy przy obu znakach.',
                'intro' => 'Na pierwszy rzut oka oba znaki dotyczą przecięcia torów ruchu z niechronionym uczestnikiem. W praktyce to jednak dwie różne dynamiki. Pieszy zwykle wchodzi na przejście wolniej i bardziej czytelnie. Rowerzysta potrafi dojechać do przejazdu dużo szybciej. To właśnie dlatego kierowca powinien czytać D-6 i D-6a inaczej, choć oba są równie ważne.',
                'published_at' => '2026-04-29T18:10:00+02:00',
                'updated_at' => '2026-04-29T18:10:00+02:00',
                'related_sign_slugs' => [
                    'd-6-przejscie-dla-pieszych',
                    'd-6a-przejazd-dla-rowerzystow',
                ],
                'takeaways' => [
                    'D-6 wskazuje przejście dla pieszych.',
                    'D-6a wskazuje przejazd dla rowerzystów.',
                    'Największa różnica dla kierowcy leży w tempie dojazdu uczestnika ruchu do punktu przecięcia.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'D-6 dotyczy przejścia dla pieszych, a D-6a przejazdu dla rowerzystów. Oba znaki wymagają wcześniejszej obserwacji, ale przy D-6a kierowca musi szybciej oszacować prędkość dojazdu roweru do punktu kolizji.',
                    ],
                    [
                        'title' => 'Jak zmienia się obserwacja',
                        'body' => 'Przy D-6 ważna jest szeroka obserwacja chodnika, krawędzi jezdni i strefy oczekiwania pieszego. Przy D-6a dochodzi większy nacisk na ocenę tempa i kierunku jazdy rowerzysty, który może pojawić się w przejeździe dużo szybciej niż pieszy na pasach.',
                    ],
                    [
                        'title' => 'Najczęstsza pomyłka kierowcy',
                        'body' => 'Wielu kierowców czyta oba znaki jak jedną rodzinę bez różnicy dynamiki. To właśnie prowadzi do spóźnionej reakcji przy przejeździe rowerowym, gdzie kilka sekund nieuwagi oznacza bardzo mały margines bezpieczeństwa.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy D-6a trzeba czytać tak samo jak D-6?',
                        'answer' => 'Nie całkiem. Oba znaki są ważne, ale przy D-6a trzeba szybciej ocenić tempo dojazdu rowerzysty do przejazdu.',
                    ],
                    [
                        'question' => 'Dlaczego kierowcy częściej spóźniają reakcję przy D-6a?',
                        'answer' => 'Bo rowerzysta może dojechać do punktu przecięcia znacznie szybciej niż pieszy, a kierowca często zbyt długo zakłada podobną dynamikę ruchu.',
                    ],
                ],
            ],
            [
                'slug' => 'd-18-d-23-d-28-d-34',
                'kicker' => 'Porównanie znaków',
                'headline' => 'D-18, D-23, D-28 i D-34: jak czytać znaki usługowe przy drodze',
                'title' => 'D-18, D-23, D-28 i D-34 - parking, paliwo, restauracja i informacja turystyczna',
                'description' => 'Zbieramy w jednym miejscu znaki D-18, D-23, D-28 i D-34, żeby uporządkować usługowe znaki informacyjne i pokazać, jak wpływają na planowanie postoju i zjazdu.',
                'intro' => 'Ta grupa znaków rzadziej trafia do pytań o typowy manewr, ale bardzo mocno wpływa na codzienne decyzje kierowcy. Parking, paliwo, gastronomia i informacja turystyczna to nie tylko ikony usług. To również sygnały, które pomagają wcześniej zaplanować zjazd, postój i zachowanie na pasie ruchu.',
                'published_at' => '2026-04-29T18:15:00+02:00',
                'updated_at' => '2026-04-29T18:15:00+02:00',
                'related_sign_slugs' => [
                    'd-18-parking',
                    'd-23-stacja-paliwowa',
                    'd-28-restauracja',
                    'd-34-punkt-informacji-turystycznej',
                ],
                'takeaways' => [
                    'D-18 wskazuje parking, D-23 stację paliwową, D-28 restaurację, a D-34 punkt informacji turystycznej.',
                    'To znaki usługowe, które pomagają planować postój i bezpieczny zjazd z trasy.',
                    'Największy błąd przy tej rodzinie to spóźniona, impulsywna decyzja o zmianie pasa albo skręcie.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'Wszystkie cztery znaki należą do rodziny usługowej, ale każdy odpowiada na inną potrzebę kierowcy: postój, paliwo, jedzenie albo orientację turystyczną. W praktyce ich wspólnym mianownikiem jest planowanie dalszego ruchu, a nie sam symbol usługi.',
                    ],
                    [
                        'title' => 'Dlaczego te znaki trzeba czytać wcześniej',
                        'body' => 'Znaki usługowe łatwo prowokują impulsywne decyzje. Kierowca widzi paliwo albo parking i od razu chce tam zjechać. Tymczasem najważniejsze jest wcześniejsze ustawienie pojazdu na właściwym pasie i spokojne przygotowanie manewru.',
                    ],
                    [
                        'title' => 'Jak wykorzystać je rozsądnie',
                        'body' => 'Najlepiej potraktować te znaki jako element planu jazdy: gdzie zatrzymać się bezpiecznie, gdzie zatankować, gdzie zjeść i gdzie zdobyć informacje o miejscu. To nie są znaki do nagłej reakcji, tylko do rozsądnego planowania kilku kolejnych decyzji za kierownicą.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy znak usługowy daje od razu prawo do gwałtownego zjazdu, jeśli widzę usługę obok?',
                        'answer' => 'Nie. To nadal tylko informacja, którą trzeba wykorzystać spokojnie i bezpiecznie, z odpowiednim przygotowaniem pasa i manewru.',
                    ],
                    [
                        'question' => 'Który znak z tej grupy dotyczy parkowania?',
                        'answer' => 'D-18. Pozostałe znaki z tego materiału dotyczą stacji paliwowej, restauracji i punktu informacji turystycznej.',
                    ],
                ],
            ],
            [
                'slug' => 'e-1-vs-e-2a-vs-e-2b',
                'kicker' => 'Porównanie znaków',
                'headline' => 'E-1, E-2a i E-2b: jak tablice przeddrogowskazowe ułatwiają wybór pasa na wielopasmowej drodze',
                'title' => 'E-1 a E-2a i E-2b - tablica przeddrogowskazowa a drogowskazy tablicowe',
                'description' => 'Porównujemy znaki E-1, E-2a i E-2b. Dowiedz się, dlaczego E-1 pojawia się wcześniej i jak pomaga uniknąć nagłej zmiany pasa ruchu tuż przed zjazdem.',
                'intro' => 'Znaki kierunku często sprawiają problem na egzaminach z powodu dynamiki ruchu. Tablica E-1 daje nam czas na decyzję jeszcze długo przed skrzyżowaniem, podczas gdy drogowskazy E-2a i E-2b nakazują konkretne manewry już w strefie zjazdu.',
                'published_at' => '2026-04-29T18:20:00+02:00',
                'updated_at' => '2026-04-29T18:20:00+02:00',
                'related_sign_slugs' => [
                    'e-1-tablica-przeddrogowskazowa',
                    'e-2a-drogowskaz-tablicowy-obok-jezdni',
                    'e-2b-drogowskaz-tablicowy-nad-jezdnia',
                ],
                'takeaways' => [
                    'E-1 to uprzedzenie o skrzyżowaniu, pozwalające na wstępne ustawienie pojazdu na właściwym pasie.',
                    'E-2a i E-2b wskazują konkretny tor jazdy niezbędny do opuszczenia obecnego szlaku.',
                    'Najczęstszy błąd to ignorowanie E-1 i paniczna zmiana pasa dopiero przed E-2.',
                ],
                'sections' => [
                    [
                        'title' => 'Najkrótsza różnica',
                        'body' => 'Znak E-1 to rozrysowana mapa zbliżającego się skrzyżowania, ustawiana kilkaset metrów wcześniej. Znaki E-2a i E-2b pełnią funkcję "tu skręcaj" na bieżącym rozwidleniu.',
                    ],
                    [
                        'title' => 'Jak planować jazdę',
                        'body' => 'Kierowca po zobaczeniu E-1 powinien upewnić się, w jakim kierunku podąża, a następnie zawczasu (przy bezpiecznej prędkości) zająć odpowiedni pas. Pozwala to na płynne minięcie E-2 bez zakłócania płynności ruchu szybkim autom z tyłu.',
                    ],
                    [
                        'title' => 'Pułapki egzaminacyjne',
                        'body' => 'Próba wyprzedzania w strefie między E-1 a E-2b często kończy się "zaklinowaniem" na złym pasie i niemożnością kontynuacji jazdy w kierunku wymaganym przez egzaminatora.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy po minięciu E-1 mogę jeszcze zmienić pas?',
                        'answer' => 'Oczywiście, wręcz należy to zrobić (jeśli nasz cel tego wymaga), zanim dojedziemy do miejsca w którym pasy oddzieli linia ciągła przy znakach E-2.',
                    ],
                    [
                        'question' => 'Dlaczego E-2b umieszcza się nad jezdnią?',
                        'answer' => 'Na autostradach i drogach szybkiego ruchu tablica boczna mogłaby być niewidoczna zza jadących obok ciężarówek. Znak nad pasem daje absolutną pewność.',
                    ],
                ],
            ],
            [
                'slug' => 'f-10-vs-f-11',
                'kicker' => 'Porównanie znaków',
                'headline' => 'F-10 a F-11: kierunki na pasach ruchu czy kierunki z konkretnego pasa',
                'title' => 'F-10 a F-11 - kiedy znak dotyczy całej jezdni, a kiedy Twojego pasa',
                'description' => 'Wyjaśniamy różnicę między F-10 a F-11. Zobacz, jak unikać wymuszeń i prawidłowo zajmować pas przed wejściem na rondo.',
                'intro' => 'Oba znaki z grupy F mówią o tym, w którą stronę możesz jechać. F-10 opisuje sytuację dla całej jezdni (wszystkich pasów). F-11 natomiast precyzuje dozwolone manewry z tego jednego, konkretnego pasa, nad którym się znajduje.',
                'published_at' => '2026-04-29T18:25:00+02:00',
                'updated_at' => '2026-04-29T18:25:00+02:00',
                'related_sign_slugs' => [
                    'f-10-kierunki-na-pasach-ruchu',
                    'f-11-kierunki-na-pasie-ruchu',
                ],
                'takeaways' => [
                    'F-10 pokazuje Ci globalny układ pasów na dojeździe.',
                    'F-11 wiszący nad Twoim pasem to Twoja "przepustka" na konkretne wloty skrzyżowania.',
                    'Ignorowanie tych znaków prowadzi do groźnych skrętów w lewo ze środkowego pasa.',
                ],
                'sections' => [
                    [
                        'title' => 'Rozróżnienie z praktyki',
                        'body' => 'F-10 pojawia się najczęściej po prawej stronie drogi i jest dużą, zespoloną tablicą. Wymaga szybkiej analizy całej "mapy" pasów i odnalezienia się na niej. F-11 to najczęściej mała tabliczka wisząca na bramownicy tylko nad pasem, na którym się znajdujesz.',
                    ],
                    [
                        'title' => 'Konsekwencje pomyłki',
                        'body' => 'Jeśli znajdziesz się na pasie oznaczonym F-11 z dozwolonym tylko lewoskrętem, nie wolno Ci jechać prosto! Niestosowanie się do tego to przepis na wymuszenie pierwszeństwa wobec jadących pasem obok.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy F-10 i F-11 zastępują znaki poziome P-8 (strzałki)?',
                        'answer' => 'Uzupełniają je. Często są stosowane, gdy strzałki na jezdni mogą być niewidoczne pod śniegiem lub w korku.',
                    ],
                ],
            ],
            [
                'slug' => 'g-3-vs-g-4',
                'kicker' => 'Porównanie znaków',
                'headline' => 'G-3 a G-4: dlaczego krzyż św. Andrzeja to jedno z najważniejszych ostrzeżeń na drodze',
                'title' => 'G-3 a G-4 - krzyż św. Andrzeja przed przejazdem kolejowym jedno i wielotorowym',
                'description' => 'Różnica między G-3 a G-4 to nie tylko kwestia wyglądu znaku. Tłumaczymy, dlaczego zignorowanie "dodatkowej strzałki" na przejeździe kosztuje życie.',
                'intro' => 'Krzyże św. Andrzeja to znaki kluczowe. G-3 oznacza, że po przejechaniu przez torowisko jesteś już bezpieczny. G-4 oznacza, że torów jest więcej, i to właśnie tuż za nimi może pojawić się pociąg jadący w przeciwnym kierunku.',
                'published_at' => '2026-04-29T18:30:00+02:00',
                'updated_at' => '2026-04-29T18:30:00+02:00',
                'related_sign_slugs' => [
                    'g-3-krzyz-sw-andrzeja-przed-przejazdem-kolejowym-jednotorowym',
                    'g-4-krzyz-sw-andrzeja-przed-przejazdem-kolejowym-wielotorowym',
                ],
                'takeaways' => [
                    'G-3 informuje o jednym torze. Po jego opuszczeniu zagrożenie mija.',
                    'G-4 (z podwójnymi dolnymi ramionami) mówi o więcej niż jednym torze.',
                    'Najczęstsza i najbardziej tragiczna w skutkach pomyłka to wjazd na przejazd wielotorowy bezpośrednio za mijającym pociągiem.',
                ],
                'sections' => [
                    [
                        'title' => 'Jeden vs wiele torów',
                        'body' => 'Różnica polega na dolnych ramionach znaku. G-4 posiada dodatkowe elementy pod krzyżem. Widząc go, wiesz, że za pierwszym składem może natychmiast wyjechać kolejny, z przeciwnej lub tej samej strony.',
                    ],
                    [
                        'title' => 'Pułapka pośpiechu',
                        'body' => 'Kierowcy bardzo często po minięciu pociągu przy znaku G-4 z automatu wciskają gaz. Zasada brzmi: poczekaj, aż pociąg upewni się w obu kierunkach widoczności i zgaśnie czerwone światło na sygnalizatorze. Nigdy nie ruszaj na pamięć.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy krzyż św. Andrzeja zmusza mnie do bezwzględnego zatrzymania?',
                        'answer' => 'Nie. Bezpośredni nakaz zatrzymania nakłada dopiero znak B-20 (STOP) postawiony w towarzystwie krzyża lub migający czerwony sygnalizator.',
                    ],
                ],
            ],
            [
                'slug' => 'p-13-vs-p-14',
                'kicker' => 'Porównanie znaków',
                'headline' => 'P-13 a P-14: trójkąty i prostokąty, czyli jak zatrzymać auto bez oblania egzaminu',
                'title' => 'P-13 a P-14 - linie warunkowego zatrzymania. Kiedy stop jest obowiązkowy?',
                'description' => 'Omawiamy różnicę między trójkątną linią P-13 dla A-7 a prostokątną P-14 dla znaku STOP (B-20). Dowiedz się, do którego momentu dociągnąć auto na egzaminie.',
                'intro' => 'Linie te są dla kierowców sygnałem: "tu zaczyna się niebezpieczna strefa skrzyżowania". P-13 (trójkąty) wiąże się ze znakiem ustąp pierwszeństwa, a P-14 (prostokąty) nakłada bezwzględny obowiązek zatrzymania osi auta przed linią.',
                'published_at' => '2026-04-29T18:35:00+02:00',
                'updated_at' => '2026-04-29T18:35:00+02:00',
                'related_sign_slugs' => [
                    'p-13-linia-warunkowego-zatrzymania-zlozona-z-trojkatow',
                    'p-14-linia-warunkowego-zatrzymania-zlozona-z-prostokatow',
                ],
                'takeaways' => [
                    'P-13 (trójkąty) tylko podpowiada, gdzie zatrzymać się w razie konieczności ustąpienia pierwszeństwa (A-7).',
                    'P-14 (prostokąty) bezwzględnie nakazuje zerową prędkość całego pojazdu przed obrębem skrzyżowania (B-20, czerwone światło).',
                    'Przekroczenie P-14 przed zatrzymaniem to natychmiastowe oblanie na egzaminie WORD.',
                ],
                'sections' => [
                    [
                        'title' => 'Co, jeśli linia się zatarła?',
                        'body' => 'Jeśli linia P-14 uległa zatarciu, powinieneś zatrzymać się w takim miejscu, aby nie utrudniać ruchu, ale widzieć wyraźnie drogę poprzeczną (tzw. "zatrzymanie na róg skrzyżowania").',
                    ],
                    [
                        'title' => 'Błąd egzaminacyjny',
                        'body' => 'Ustawienie kół NA linii P-14 lub za nią to złamanie nakazu. Należy dojeżdżać tak, by widzieć linię pod kołami (bądź zderzakiem). Z kolei przy P-13, brak zatrzymania jest poprawny, o ile nie wymuszasz na nikim pierwszeństwa.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy muszę się zatrzymać, gdy widzę P-13 a droga jest pusta?',
                        'answer' => 'Nie. Przy braku innych pojazdów, P-13 nie wymusza bezwzględnego zatrzymania – możesz jechać dalej.',
                    ],
                ],
            ],
            [
                'slug' => 'p-1-vs-p-2-vs-p-4',
                'kicker' => 'Porównanie znaków',
                'headline' => 'P-1, P-2 i P-4: linie wyznaczające pasy ruchu. Gdzie kończy się swoboda wyprzedzania?',
                'title' => 'P-1, P-2 a P-4 - linia przerywana, ciągła pojedyncza i podwójna',
                'description' => 'Rozróżniamy najważniejsze poziome znaki rozdzielające pasy ruchu. Dowiedz się, co oznaczają i kiedy grozi najwyższy mandat za najechanie na kołami.',
                'intro' => 'Linie wyznaczające pasy ruchu w Polsce to absolutna podstawa dyscypliny w jeździe wielopasmowej. O ile linię P-1 traktujemy jako granicę o dowolnej "przepuszczalności", o tyle P-2 i P-4 to fizyczne ściany zakazujące pewnych manewrów.',
                'published_at' => '2026-04-29T18:40:00+02:00',
                'updated_at' => '2026-04-29T18:40:00+02:00',
                'related_sign_slugs' => [
                    'p-1-linia-pojedyncza-przerywana',
                    'p-2-linia-pojedyncza-ciagla',
                    'p-4-linia-podwojna-ciagla',
                ],
                'takeaways' => [
                    'P-1 (przerywana) pozwala na zmianę pasa lub wyprzedzanie pod warunkiem ostrożności.',
                    'P-2 (pojedyncza ciągła) służy do oznaczania niebezpiecznych odcinków w jednym kierunku - nie najeżdżaj.',
                    'P-4 (podwójna ciągła) dzieli jezdnię o przeciwnych kierunkach jazdy i jest strefą surowo zakazaną do przekraczania.',
                ],
                'sections' => [
                    [
                        'title' => 'Najechanie kontra przekroczenie',
                        'body' => 'Z punktu widzenia taryfikatora, "najechanie" na linię podwójną ciągłą już stanowi wykroczenie drogowe. Nawet omijając przeszkodę powinieneś starać się zmieścić we własnym pasie.',
                    ],
                    [
                        'title' => 'Ciągła pośrodku pasa',
                        'body' => 'Linię podwójną ciągłą często używa się jako bufora przed skrzyżowaniami i łukami o bardzo złej widoczności, tam gdzie uderzenie czołowe niosłoby za sobą najwyższe żniwo.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy mogę wyprzedzić ciągnik najeżdżając na P-4?',
                        'answer' => 'Nie. Zabronione jest przejeżdżanie przez nią niezależnie od tego, jak wolno jedzie pojazd przed Tobą.',
                    ],
                ],
            ],
            [
                'slug' => 's-1-vs-s-2-vs-s-3',
                'kicker' => 'Porównanie znaków',
                'headline' => 'S-1, S-2 i S-3: różne warianty zielonego światła. Dlaczego "zielone" to nie zawsze pewne pierwszeństwo?',
                'title' => 'S-1 a S-2 i S-3 - sygnalizator ogólny, strzałka warunkowa i sygnalizator kierunkowy',
                'description' => 'Jak odczytywać sygnały świetlne na trudnych skrzyżowaniach? Kiedy zielone światło wymaga ustąpienia, a kiedy jesteś w pełni chroniony ruchem bezkolizyjnym.',
                'intro' => 'Wielu kierowców widząc zieleń wciska pedał gazu w podłogę. Prawda jest jednak taka, że zielone światło S-1 na głównym sygnalizatorze nie chroni przed ruchem kolizyjnym podczas skrętów w lewo.',
                'published_at' => '2026-04-29T18:45:00+02:00',
                'updated_at' => '2026-04-29T18:45:00+02:00',
                'related_sign_slugs' => [
                    's-1-sygnalizator-ogolny',
                    's-2-sygnalizator-ze-strzalka-warunkowa',
                    's-3a-sygnalizator-kierunkowy-na-wprost-i-w-lewo',
                ],
                'takeaways' => [
                    'S-1 pozwala na wjazd na skrzyżowanie, jednak w trakcie lewoskrętu musisz przepuścić jadących z naprzeciwka.',
                    'S-2 (zielona strzałka) to zgoda na warunkowy skręt w czasie czerwonego światła, z obowiązkiem ZATRZYMANIA.',
                    'S-3 (bezkolizyjny) daje 100% gwarancję braku ruchu przecinającego w danej fazie świetlnej.',
                ],
                'sections' => [
                    [
                        'title' => 'Dylemat lewoskrętu',
                        'body' => 'Skręt w lewo na sygnalizatorze S-1 to bodajże najniebezpieczniejszy manewr w miastach. Wymaga wyjazdu na sam środek skrzyżowania i oceny zderzenia na "oko".',
                    ],
                    [
                        'title' => 'Dlaczego oblewają na S-2',
                        'body' => 'Około 40% przerwanych egzaminów na prawo jazdy na skrzyżowaniu wiąże się ze strzałką warunkową. Powód? Kierowca traktuje ją jak pomarańczowe światło i przetacza się z prędkością 5 km/h, bez zatrzymania całego kół.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy jeśli strzałka w S-2 gaśnie, gdy jestem już za linią, to muszę stanąć?',
                        'answer' => 'Jeśli już minąłeś linię warunkowego zatrzymania i upewniłeś się o wolnej drodze, należy zjechać ze skrzyżowania. Wymaga to jednak wielkiego wyczucia dynamiki.',
                    ],
                ],
            ],
            [
                'slug' => 'k-1-vs-k-2',
                'kicker' => 'Porównanie znaków',
                'headline' => 'K-1 (Check Engine) a K-2 (Ciśnienie oleju): kiedy można jechać, a kiedy trzeba gasić silnik',
                'title' => 'K-1 a K-2 - kontrolka żółta a czerwona, różnica w powadze usterki',
                'description' => 'Tłumaczymy kluczową różnicę między żółtymi i czerwonymi kontrolkami w aucie. Dowiedz się, dlaczego z Check Engine dojedziesz do serwisu, a przy braku ciśnienia oleju silnik zatrze się w minutę.',
                'intro' => 'Barwa kontrolki określa priorytet działania. Żółte światło (np. K-1 Check Engine) to ostrzeżenie pozwalające zazwyczaj na ostrożną jazdę do mechanika. Czerwone światło (np. K-2) nakazuje absolutne, natychmiastowe unieruchomienie pojazdu.',
                'published_at' => '2026-04-29T18:50:00+02:00',
                'updated_at' => '2026-04-29T18:50:00+02:00',
                'related_sign_slugs' => [
                    'k-1-check-engine',
                    'k-2-cisnienie-oleju',
                ],
                'takeaways' => [
                    'Żółta kontrolka to tryb awaryjny - silnik może pracować gorzej, ale dojedziesz do celu.',
                    'Czerwona kontrolka to brak smarowania lub chłodzenia - jazda z nią niszczy silnik w mgnieniu oka.',
                    'Dla bezpieczeństwa, czerwoną kontrolkę traktuj jak fizyczną ścianę.',
                ],
                'sections' => [
                    [
                        'title' => 'Czy z Check Engine zdam egzamin?',
                        'body' => 'Jeśli na egzaminie zapali się żółta kontrolka usterki silnika, ale pojazd jedzie prawidłowo, egzaminator najczęściej zaleci powrót do WORD. W przypadku zapalenia się "olejarki" egzamin jest natychmiast przerywany w trosce o sprzęt.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy czerwona kontrolka akumulatora też oznacza zatarcie silnika?',
                        'answer' => 'Nie, oznacza brak ładowania, jednak silnik też zgaśnie, gdy prądu zabraknie do układu wtryskowego.',
                    ],
                ],
            ],
            [
                'slug' => 'r-2-vs-r-3',
                'kicker' => 'Porównanie znaków',
                'headline' => 'Policjant przodem (R-2) a bokiem (R-3): gesty osoby kierującej ruchem',
                'title' => 'R-2 a R-3 - dlaczego postawa policjanta działa jak sygnalizacja świetlna',
                'description' => 'Zapomnij o znakach i światłach, gdy na skrzyżowaniu staje policjant! Tłumaczymy, dlaczego postawa przodem to "czerwone", a bokiem to "zielone" światło.',
                'intro' => 'Na skrzyżowaniach z kierującym ruchem decyduje tylko i wyłącznie układ jego ciała. Postawa przodem lub tyłem (R-2) całkowicie zamyka wloty przed nim, natomiast odwrócenie się policjanta bokiem (R-3) "otwiera" ruch dla pojazdów na tej samej osi.',
                'published_at' => '2026-04-29T18:55:00+02:00',
                'updated_at' => '2026-04-29T18:55:00+02:00',
                'related_sign_slugs' => [
                    'r-2-postawa-przodem-lub-tylem',
                    'r-3-postawa-bokiem',
                ],
                'takeaways' => [
                    'R-2 (przodem/tyłem) zrównuje się z bezwzględnym czerwonym światłem.',
                    'R-3 (bokiem) daje zielone światło do jazdy.',
                    'Jeśli policjant stoi tyłem, a masz zielone na sygnalizatorze - stoisz! Policjant znosi światła.',
                ],
                'sections' => [
                    [
                        'title' => 'Pułapka świateł',
                        'body' => 'Gdy następuje awaria sygnalizacji i wchodzi policjant, często światła nadal działają. Skup wzrok WYŁĄCZNIE na ciele policjanta, ignorując migające czy zapalone klosze latarni.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Jeśli policjant stoi bokiem, a ja chcę skręcić w lewo, kto ma pierwszeństwo?',
                        'answer' => 'Jadąc w lewo ustępujesz pierwszeństwa autom z naprzeciwka jadącym na wprost (do nich policjant również stoi bokiem!).',
                    ],
                ],
            ],
            [
                'slug' => 'w-1-vs-b-18',
                'kicker' => 'Porównanie znaków',
                'headline' => 'W-1 a B-18: żółte znaki wojskowe kontra cywilne limity na moście',
                'title' => 'W-1 (klasa mostu) a B-18 (zakaz wjazdu ton) - czy cywil musi zwracać uwagę na klasę MLC?',
                'description' => 'Rozwiewamy obawy kierowców ciężarówek. Zobacz, czym różni się znak W-1 (Military Load Classification) od znaku B-18 i kiedy możesz go spokojnie ominąć.',
                'intro' => 'Kierowcy pojazdów dostawczych i ciężarowych nierzadko panikują przed mostami, widząc żółtą, okrągłą tarczę z dużą liczbą. To znak z grupy W, informujący jedynie dowódców transportów wojskowych. Twoim ogranicznikiem jest natomiast czerwono-biały znak B-18.',
                'published_at' => '2026-04-29T19:00:00+02:00',
                'updated_at' => '2026-04-29T19:00:00+02:00',
                'related_sign_slugs' => [
                    'w-1-klasa-obciazenia-mostu-ruch-jednokierunkowy',
                    'b-18-zakaz-wjazdu-pojazdow-o-rzeczywistej-masie-calkowitej-ponad-t',
                ],
                'takeaways' => [
                    'Znak grupy W (żółty, okrągły) dotyczy wyłącznie Sił Zbrojnych i określa wskaźnik MLC.',
                    'Znak B-18 to wiążący zakaz wjazdu powiązany z Rzeczywistą Masą Całkowitą (RMC).',
                    'Liczba na znaku W to klasa obciążenia, a nie liczba ton!',
                ],
                'sections' => [
                    [
                        'title' => 'Mit ograniczenia tonażu',
                        'body' => 'Wskaźnik MLC to skomplikowany wzór zależący od rozstawu osi i styku opon/gąsienic z podłożem. Nie ma bezpośredniego przełożenia na cywilne DMC z dowodu rejestracyjnego.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy jeśli nie ma B-18, a jest W-1 z liczbą mniejszą niż moja waga, to wjadę?',
                        'answer' => 'Tak. Jeśli zarządca drogi nie powiesił cywilnego zakazu (B-18), to most posiada odpowiednią nośność dla normalnych pojazdów poruszających się po publicznej sieci dróg.',
                    ],
                ],
            ],
            [
                'slug' => 'st-1-vs-st-2',
                'kicker' => 'Porównanie znaków',
                'headline' => 'ST-1 a ST-2: pozioma i pionowa kreska dla tramwajów - co oznaczają dla kierowcy auta?',
                'title' => 'Sygnał ST-1 a ST-2 - jak ułatwić sobie jazdę, "czytając" światła tramwaju',
                'description' => 'Odróżnij poziomą i pionową białą kreskę na sygnalizatorze ST. Znając te światła, ominiesz wymuszenia pierwszeństwa z tramwajami.',
                'intro' => 'Obserwacja sygnalizatora ST to trik stosowany przez dobrych kierowców. Jeśli widzisz, że tramwaj stojący przed Tobą ma zapaloną poziomą kreskę (ST-1), wiesz na 100%, że nie wjedzie Ci na maskę, mimo iż to on jest pojazdem szynowym.',
                'published_at' => '2026-04-29T19:05:00+02:00',
                'updated_at' => '2026-04-29T19:05:00+02:00',
                'related_sign_slugs' => [
                    'st-1-zakaz-wjazdu-pozioma-kreska',
                    'st-2-jazda-na-wprost-pionowa-kreska',
                ],
                'takeaways' => [
                    'ST-1 (pozioma kreska) to bezwzględny zakaz ruchu dla tramwaju (czerwone).',
                    'ST-2 (pionowa kreska) zezwala tramwajowi jechać na wprost (zielone).',
                    'Nawet jeśli tramwaj jedzie drogą z pierwszeństwem, ST-1 trzyma go przed skrzyżowaniem.',
                ],
                'sections' => [
                    [
                        'title' => 'Przewaga z wiedzy',
                        'body' => 'Gdy wykonujesz manewr na trudnym rozwidleniu i sam masz zielone, jednoerknięcie na słup obok torowiska rozwiązuje dylemat, czy wóz szynowy ruszy razem z Tobą.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Dlaczego wprowadzono osobne światła dla tramwajów?',
                        'answer' => 'Torowiska i trasy pojazdów wielkogabarytowych potrzebują niezależnego cyklu świetlnego (faz bezkolizyjnych), by w bezpieczny sposób "przepchnąć" wagony przez tkankę miasta.',
                    ],
                ],
            ],
            [
                'slug' => 'bt-1-vs-b-33',
                'kicker' => 'Porównanie znaków',
                'headline' => 'BT-1 a B-33: dlaczego tramwaj zwalnia do 15 km/h, a Ty obok jedziesz 50 km/h?',
                'title' => 'Znak tramwajowy BT-1 a Znak B-33 - kogo obowiązują te kwadratowe znaki?',
                'description' => 'Rozbijamy wątpliwości z egzaminów. Sprawdź, czy kwadratowe ograniczenie prędkości na drutach sieci trakcyjnej zwalnia też samochód na prawym pasie.',
                'intro' => 'Znak tramwajowy BT-1 to biały kwadrat z grubą ramką, nakazujący zwolnić do widocznej na nim wartości (często 10-20 km/h). Jednak z perspektywy samochodu uwięzionego w korku na torowisku... znak ten jest "niewidzialny". Ograniczenia te chronią wózki torowe, nie opony.',
                'published_at' => '2026-04-29T19:10:00+02:00',
                'updated_at' => '2026-04-29T19:10:00+02:00',
                'related_sign_slugs' => [
                    'bt-1-ograniczenie-predkosci',
                    'b-33-ograniczenie-predkosci',
                ],
                'takeaways' => [
                    'Znaki BT (kwadratowe, czarno-białe) to prawo wewnątrz przedsiębiorstwa komunikacyjnego usankcjonowane państwową legislacją.',
                    'Znak B-33 (okrągły z czerwoną ramką) to limit dla wszystkich pojazdów ogólnych w strefie.',
                    'Dopóki nie ma cywilnego B-33, auto może jechać zgodnie z limitami w terenie zbudowanym (50 km/h).',
                ],
                'sections' => [
                    [
                        'title' => 'Jazda po zintegrowanym torowisku',
                        'body' => 'Gdy wjeżdżasz na wspólną część jezdni, by ominąć korek lub skręcić, widzisz wiszące 15-tki dla zjazdów krzyżowych. Masz pełne prawo je ominąć bez redukcji swojej prędkości, chyba że stwarza to fizyczne zagrożenie.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Czy fotoradar może ustawić limit z kwadratowego znaku?',
                        'answer' => 'Nie. Fotoradary (GITD) operują wyłącznie w oparciu o cywilne okrągłe tarcze lub ogólne zasady ze stref zabudowanych.',
                    ],
                ],
            ],
            [
                'slug' => 'u-1a-vs-u-21',
                'kicker' => 'Porównanie znaków',
                'headline' => 'U-1a a U-21: jak słupki i sierżanty ratują Cię nocą przed wjechaniem w przeszkodę',
                'title' => 'U-1a (słupek) a U-21 (sierżant) - jak "czytać" odblaski i pasy?',
                'description' => 'Dlaczego słupki mają białe i czerwone odblaski? Z której strony omijać wielką tablicę na zwężeniu? Wyjaśniamy tajemnice urządzeń BRD.',
                'intro' => 'Urządzenia BRD komunikują się z kierowcą bez użycia słów i cyfr. Słupek prowadzący (U-1a) to "oczko" wyznaczające koniec jezdni, a sierżant (U-21) to mur ochronny wymuszający gwałtowny manewr omijania na budowie.',
                'published_at' => '2026-04-29T19:15:00+02:00',
                'updated_at' => '2026-04-29T19:15:00+02:00',
                'related_sign_slugs' => [
                    'u-1a-slupek-wskaznikowy-prowadzacy',
                    'u-21-tablica-kierujaca-sierzant',
                ],
                'takeaways' => [
                    'Słupek U-1a na prawym poboczu ma zawsze odblask czerwony, a na lewym biały.',
                    'Tablicę U-21 omijasz zawsze w tę stronę, w którą skośnie w dół opadają jej biało-czerwone pasy.',
                    'Potrącenie któregoś z nich to nie tylko stłuczka, ale uszkodzenie infrastruktury państwowej.',
                ],
                'sections' => [
                    [
                        'title' => 'Klucz do testów na prawo jazdy',
                        'body' => 'Pytania o kierunek omijania U-21 to absolutny klasyk na egzaminach WORD. Skup się tylko na wzorze: jeśli pasy opadają w prawo (\\), jedziesz w prawo. Jeśli w lewo (/), jedziesz w lewo.',
                    ],
                ],
                'faq_items' => [
                    [
                        'question' => 'Dlaczego na słupkach prowadzących (U-1a) są cyfry?',
                        'answer' => 'To kilometraż (pikietaż) drogi. W razie wypadku w lesie, podając dyspozytorowi numer drogi i liczbę ze słupka, służby zlokalizują Cię co do 100 metrów.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        foreach ($this->all() as $page) {
            if ($page['slug'] === $slug) {
                return $page;
            }
        }

        return null;
    }

    /**
     * @return list<array{title: string, description: string, url: string, related_sign_slugs: list<string>}>
     */
    public function forSign(TrafficSign $sign): array
    {
        $pages = [];

        foreach ($this->all() as $page) {
            $relatedSignSlugs = $page['related_sign_slugs'] ?? [];

            if (! in_array($sign->slug, $relatedSignSlugs, true)) {
                continue;
            }

            $pages[] = [
                'title' => $page['title'],
                'description' => $page['description'],
                'url' => route('traffic-signs.supporting.show', $page['slug']),
                'related_sign_slugs' => $relatedSignSlugs,
            ];
        }

        return $pages;
    }

    /**
     * @return list<array{title: string, description: string, url: string, related_sign_slugs: list<string>}>
     */
    public function forCategorySlug(string $categorySlug, int $limit = 6): array
    {
        $prefix = match ($categorySlug) {
            'znaki-ostrzegawcze' => 'a-',
            'znaki-zakazu' => 'b-',
            'znaki-nakazu' => 'c-',
            'znaki-informacyjne' => 'd-',
            'znaki-kierunku-i-miejscowosci' => 'e-',
            'znaki-uzupelniajace' => 'f-',
            'tabliczki-do-znakow' => 't-',
            'znaki-przed-przejazdami-kolejowymi' => 'g-',
            'znaki-drogowe-poziome' => 'p-',
            'sygnaly-swietlne' => 's-',
            'kontrolki-w-samochodzie' => 'k-',
            'osoba-kierujaca-ruchem' => 'r-',
            'znaki-wojskowe' => 'w-',
            'sygnaly-dla-tramwajow' => 'st-',
            'znaki-tramwajowe' => ['at-', 'bt-'],
            'urzadzenia-bezpieczenstwa-ruchu' => 'u-',
            default => null,
        };

        if ($prefix === null) {
            return [];
        }

        $pages = [];

        foreach ($this->all() as $page) {
            $relatedSignSlugs = $page['related_sign_slugs'] ?? [];

            if ($relatedSignSlugs === []) {
                continue;
            }

            $belongsToCategory = collect($relatedSignSlugs)
                ->every(function (string $slug) use ($prefix): bool {
                    if (is_array($prefix)) {
                        foreach ($prefix as $p) {
                            if (str_starts_with($slug, $p)) {
                                return true;
                            }
                        }

                        return false;
                    }

                    return str_starts_with($slug, $prefix);
                });

            if (! $belongsToCategory) {
                continue;
            }

            $pages[] = [
                'title' => $page['title'],
                'description' => $page['description'],
                'url' => route('traffic-signs.supporting.show', $page['slug']),
                'related_sign_slugs' => $relatedSignSlugs,
            ];
        }

        return array_slice($pages, 0, $limit);
    }
}
