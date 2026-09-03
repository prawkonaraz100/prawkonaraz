<?php

namespace App\Support;

use Illuminate\Support\Str;

class PolishMandatorySignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $config = $this->mandatoryConfig()[$sign['code']] ?? $this->fallbackConfig($sign);
        $assetPath = $this->assetPath($sign['slug']);
        $imageAlt = $sign['name'];

        return [
            'intro_definition' => "Znak {$sign['code']} {$sign['name']} wskazuje obowiązkowy sposób jazdy albo obowiązkowy sposób korzystania z drogi. Po minięciu znaku kierowca nie wybiera wariantu dowolnie: ma zastosować się do nakazu wynikającego z organizacji ruchu.",
            'meaning' => $config['meaning'],
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'Znaki nakazu z grupy C porządkują ruch przez wskazanie jedynego dopuszczalnego kierunku, toru jazdy lub sposobu korzystania z odcinka drogi. W praktyce trzeba je czytać razem z geometrią skrzyżowania, układem pasa ruchu, oznakowaniem poziomym i ewentualnymi tabliczkami, bo właśnie ten komplet określa, jak kierowca ma przejechać dane miejsce zgodnie z prawem.',
            'fine_summary' => "Największe ryzyko przy {$sign['code']} nie wynika z samego minięcia znaku, tylko z wykonania manewru sprzecznego z nakazem. To zwykle prowadzi do przecięcia toru ruchu innych pojazdów, błędnego wjazdu na pas albo wejścia w strefę przeznaczoną dla innego uczestnika ruchu.",
            'common_mistakes' => $config['mistake'],
            'editorial_notes' => $config['editorial_notes'],
            'review_notes' => "Szkic przygotowany dla pełnego pierwszego rollout'u znaków nakazu. Przy kolejnych passach dopisać przykłady sytuacyjne, relacje do oznakowania poziomego i porównania z najbardziej podobnymi znakami z grupy C.",
            'source_notes' => 'Treść bazowa przygotowana na podstawie urzędowego wykazu znaków nakazu i praktyki szkolenia kierowców. Przed dalszą rozbudową sprawdzić przykłady egzaminacyjne, częste błędy kursantów i lokalne warianty ustawienia znaku względem pasa ruchu.',
            'faq_items' => [
                [
                    'question' => "Jaki obowiązek wprowadza znak {$sign['code']}?",
                    'answer' => $config['faq_duty'],
                ],
                [
                    'question' => "Jaki błąd najczęściej pojawia się przy znaku {$sign['code']}?",
                    'answer' => $config['faq_mistake'],
                ],
            ],
            'meta_title' => "{$sign['code']} {$sign['name']} - znaczenie, obowiązek i zachowanie kierowcy",
            'meta_description' => "Sprawdź, co oznacza znak {$sign['code']} {$sign['name']}, gdzie najczęściej występuje i jak kierowca powinien zastosować się do nakazu na drodze.",
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
     * @return array<string, array{meaning: string, placement: string, behavior: string, mistake: string, faq_duty: string, faq_mistake: string, editorial_notes: string}>
     */
    protected function mandatoryConfig(): array
    {
        return [
            'C-1' => [
                'meaning' => 'Znak nakazuje dalszą jazdę wyłącznie w prawo. Nie zostawia kierowcy pola na przejazd prosto ani na skręt w lewo, nawet jeśli geometria jezdni wygląda na „otwartą”.',
                'placement' => 'Najczęściej pojawia się na wlotach skrzyżowań, przed wyspami kanalizującymi ruch albo tam, gdzie organizacja pasa ruchu dopuszcza tylko jeden wariant przejazdu.',
                'behavior' => 'Ustaw pojazd wcześniej pod prawy tor jazdy i nie odkładaj decyzji o zmianie pasa lub kierunku na ostatni moment. Przy tym znaku ważne jest przygotowanie manewru jeszcze przed dojazdem do punktu skrętu.',
                'mistake' => 'Najczęstszy błąd to traktowanie znaku jak sugestii i szukanie możliwości jazdy prosto mimo wyraźnego nakazu skrętu lub jazdy w prawo.',
                'faq_duty' => 'Kierowca ma przejechać dalej wyłącznie w prawo, zgodnie z układem skrzyżowania i pasa ruchu.',
                'faq_mistake' => 'Najczęściej kierowca zbyt późno ustawia się do manewru i próbuje improwizować, gdy wlot jest już bezpośrednio przed pojazdem.',
                'editorial_notes' => 'Później zestawić z C-2 oraz z oznakowaniem poziomym dla pasa tylko w prawo.',
            ],
            'C-2' => [
                'meaning' => 'Ten znak nakazuje wykonanie skrętu w prawo na najbliższym skrzyżowaniu albo wlocie. Odcina jazdę prosto i w lewo na relacji, której dotyczy.',
                'placement' => 'Najczęściej stoi przed samym punktem skrętu, gdy organizacja ruchu chce jasno odciąć inne kierunki jazdy z danego pasa lub wlotu.',
                'behavior' => 'Podejdź do znaku z odpowiednim ustawieniem auta i obserwacją pieszych, rowerzystów oraz prawej krawędzi skrzyżowania. Sam nakaz kierunku nie zwalnia z kontroli otoczenia podczas manewru.',
                'mistake' => 'Częstym błędem jest mylenie C-2 z C-1 i czytanie obu znaków jako identycznego polecenia, mimo że C-2 dotyczy samego skrętu na najbliższym wlocie.',
                'faq_duty' => 'Kierowca ma skręcić w prawo w miejscu, którego dotyczy znak, a nie jechać prosto ani szukać innej relacji przejazdu.',
                'faq_mistake' => 'Najczęściej pojawia się za późna reakcja na ustawienie pojazdu albo skupienie się tylko na innych autach bez kontroli pieszych i rowerzystów.',
                'editorial_notes' => 'Dobrze spiąć z C-1, żeby użytkownik łatwo rozdzielił jazdę w prawo od samego skrętu w prawo.',
            ],
            'C-3' => [
                'meaning' => 'Znak nakazuje dalszą jazdę wyłącznie w lewo. Wyłącza możliwość jazdy prosto i w prawo na relacji objętej oznakowaniem.',
                'placement' => 'Najczęściej stoi przy wyspach kanalizujących ruch, przed skrzyżowaniami oraz na pasach ruchu prowadzących wyłącznie w lewo.',
                'behavior' => 'Wcześniej przygotuj pozycję na pasie i obserwację toru przejazdu, zwłaszcza gdy lewy skręt przecina ruch pieszych albo wymaga wjazdu w wielowlotowe skrzyżowanie.',
                'mistake' => 'Najczęstszy błąd to próba przejazdu prosto „bo jest miejsce” albo zbyt późne zorientowanie się, że dany pas prowadzi wyłącznie w lewo.',
                'faq_duty' => 'Kierowca ma kontynuować jazdę tylko w lewo, zgodnie z geometrią i oznaczeniem pasa ruchu.',
                'faq_mistake' => 'Kierowcy często zbyt późno czytają układ pasa i dopiero przed samym skrzyżowaniem próbują wrócić na relację prostą.',
                'editorial_notes' => 'Połączyć później z C-4 i z relacją do strzałek kierunkowych na jezdni.',
            ],
            'C-4' => [
                'meaning' => 'Znak nakazuje skręcenie w lewo w miejscu, którego dotyczy. To polecenie konkretnego manewru na najbliższym skrzyżowaniu lub wlocie.',
                'placement' => 'Najczęściej występuje tam, gdzie ruch z danego pasa albo wlotu może wykonać tylko lewy skręt i trzeba to podkreślić jeszcze przed wjazdem w skrzyżowanie.',
                'behavior' => 'Przygotuj tor skrętu wcześniej, ale jednocześnie obserwuj pieszych, rowerzystów i sygnalizację. Nakaz kierunku nie pozwala wykonać manewru mechanicznie bez kontroli otoczenia.',
                'mistake' => 'Typowy błąd to utożsamienie znaku z samą informacją o kierunku i pominięcie tego, że chodzi o obowiązkowy skręt, a nie o dalszy przebieg drogi.',
                'faq_duty' => 'Kierowca ma wykonać skręt w lewo w miejscu objętym znakiem i nie może pojechać prosto ani w prawo.',
                'faq_mistake' => 'Najczęściej kierowca skupia się tylko na włączeniu kierunkowskazu, a za mało na wcześniejszym ustawieniu auta i obserwacji kolizyjnych relacji.',
                'editorial_notes' => 'Dobrze zestawić z C-3 jako parę znaków o lewym kierunku jazdy.',
            ],
            'C-5' => [
                'meaning' => 'Znak nakazuje jazdę wyłącznie prosto. Od tego miejsca kierowca nie może skręcić ani w prawo, ani w lewo, jeśli znak dotyczy jego relacji jazdy.',
                'placement' => 'Najczęściej stoi na wlotach skrzyżowań oraz na pasach ruchu, które mają wyłącznie funkcję przelotową.',
                'behavior' => 'Trzymaj tor jazdy zgodny z osią pasa i nie zakładaj, że brak fizycznej bariery oznacza dowolność manewru. Przy takim znaku warto wcześniej sprawdzić, czy nie trzeba zmienić pasa, jeśli cel podróży wymaga skrętu.',
                'mistake' => 'Najczęstszy błąd to zbyt późne zauważenie ograniczenia i próba skrętu z pasa prowadzącego wyłącznie na wprost.',
                'faq_duty' => 'Obowiązek jest prosty: jechać dalej prosto w relacji wskazanej przez organizację ruchu.',
                'faq_mistake' => 'Kierowcy często zostawiają zmianę pasa zbyt późno i dopiero przy samym skrzyżowaniu orientują się, że nie mogą skręcić.',
                'editorial_notes' => 'Naturalny kandydat do powiązań z C-6 i C-7.',
            ],
            'C-6' => [
                'meaning' => 'Znak dopuszcza tylko dwa warianty przejazdu: prosto albo w prawo. Wyłącza możliwość skrętu w lewo z relacji, której dotyczy.',
                'placement' => 'Najczęściej pojawia się nad pasem ruchu albo przy wlocie skrzyżowania, gdzie trzeba ograniczyć kierunki do dwóch bez zostawiania pełnej dowolności.',
                'behavior' => 'Jeszcze przed dojazdem do skrzyżowania zdecyduj, czy jedziesz prosto, czy w prawo, i przygotuj pozycję auta oraz obserwację pod konkretny wariant. To ogranicza nerwowe korekty na ostatnich metrach.',
                'mistake' => 'Najczęstszy błąd to czytanie znaku tylko jako „wolno też skręcić w prawo”, bez zauważenia, że lewy kierunek został całkowicie wyłączony.',
                'faq_duty' => 'Kierowca może wybrać wyłącznie jazdę prosto albo w prawo, zgodnie z sytuacją drogową.',
                'faq_mistake' => 'Problemem bywa zbyt późna decyzja o wariancie jazdy i brak przygotowania pasa lub toru ruchu pod wybrany manewr.',
                'editorial_notes' => 'Warto później porównać z C-7 i C-8 jako rodziną znaków kombinowanych.',
            ],
            'C-7' => [
                'meaning' => 'Znak dopuszcza dalszą jazdę wyłącznie prosto albo w lewo. Odcina relację w prawo z pasa lub wlotu, którego dotyczy.',
                'placement' => 'Najczęściej jest stosowany tam, gdzie układ skrzyżowania lub sterowanie ruchem wymaga zostawienia tylko dwóch wariantów przejazdu.',
                'behavior' => 'Zawczasu zdecyduj, czy wybierasz jazdę prosto, czy w lewo, i czytaj znak razem z liniami pasa oraz sygnalizacją. Dzięki temu unikniesz nagłych korekt przed samym skrzyżowaniem.',
                'mistake' => 'Częstym błędem jest skupienie się na dopuszczonym lewym wariancie i pominięcie tego, że skręt w prawo jest tutaj wyłączony.',
                'faq_duty' => 'Dozwolone są tylko dwa kierunki: prosto lub w lewo. Inne relacje na tym pasie odpadają.',
                'faq_mistake' => 'Najczęściej kierowca zbyt późno czyta układ pasa i dopiero pod sam koniec dojazdu odkrywa brak możliwości skrętu w prawo.',
                'editorial_notes' => 'Dobrze połączyć z C-6 oraz C-8.',
            ],
            'C-8' => [
                'meaning' => 'Znak dopuszcza skręt wyłącznie w lewo albo w prawo. Nie pozwala pojechać prosto z relacji, której dotyczy.',
                'placement' => 'Najczęściej pojawia się tam, gdzie z danego pasa albo wlotu ruch ma rozdzielić się na dwa boczne kierunki bez przejazdu na wprost.',
                'behavior' => 'Wcześniej przygotuj się do jednego z dwóch dopuszczonych manewrów i nie zakładaj, że środek skrzyżowania pozostawia opcję jazdy prosto. To znak, który trzeba czytać przed dojazdem, a nie w jego trakcie.',
                'mistake' => 'Najczęstszy błąd to wjazd z nastawieniem na przejazd na wprost i dopiero w ostatniej chwili szukanie awaryjnego rozwiązania.',
                'faq_duty' => 'Kierowca może skręcić tylko w lewo albo w prawo, zależnie od celu jazdy i sytuacji na skrzyżowaniu.',
                'faq_mistake' => 'Błędem bywa zbyt późne zauważenie, że jazda prosto została wyłączona mimo pozornie otwartego układu skrzyżowania.',
                'editorial_notes' => 'Naturalna para do porównań z C-6 i C-7.',
            ],
            'C-9' => [
                'meaning' => 'Znak nakazuje ominąć przeszkodę z prawej strony. To polecenie konkretnego poprowadzenia toru jazdy wokół wyspy, robót albo innej fizycznej bariery.',
                'placement' => 'Najczęściej występuje przy wyspach dzielących jezdnię, robotach drogowych i elementach kanalizujących ruch, gdzie organizacja ruchu wymaga prowadzenia auta tylko z jednej strony przeszkody.',
                'behavior' => 'Podejdź do znaku spokojnie, zostaw miejsce na płynny objazd i nie próbuj przeciskać się bliżej przeszkody od niewłaściwej strony. Tu liczy się wcześniejsze odczytanie toru przejazdu.',
                'mistake' => 'Najczęstszy błąd to zbyt późne zauważenie, z której strony wolno ominąć przeszkodę, i wykonanie gwałtownej korekty tuż przed nią.',
                'faq_duty' => 'Przeszkodę trzeba ominąć wyłącznie z prawej strony, zgodnie z wyznaczonym torem ruchu.',
                'faq_mistake' => 'Kierowcy często reagują dopiero przy samej wyspie albo robotach i zostawiają sobie za mało miejsca na bezpieczny objazd.',
                'editorial_notes' => 'Warto później spiąć z C-10 i C-11 w jednym materiale porównawczym.',
            ],
            'C-10' => [
                'meaning' => 'Znak nakazuje ominąć przeszkodę z lewej strony. Wyklucza przejazd po jej prawej stronie, nawet jeśli geometrycznie wydaje się to możliwe.',
                'placement' => 'Najczęściej stoi przy wyspach, robotach i zwężeniach, gdy ruch ma zostać poprowadzony po lewej stronie przeszkody.',
                'behavior' => 'Ustaw pojazd wcześniej pod lewy tor objazdu i nie zakładaj, że krótki przejazd prawą stroną „też by przeszedł”. Znak porządkuje ruch precyzyjnie.',
                'mistake' => 'Najczęstszy błąd to rutynowe szukanie wolniejszej lub szerszej strony objazdu bez zauważenia, że organizacja ruchu dopuszcza tylko lewą.',
                'faq_duty' => 'Przeszkodę wolno ominąć tylko z lewej strony.',
                'faq_mistake' => 'Błędem bywa czytanie znaku za późno i podejście do przeszkody z ustawieniem auta pod niewłaściwy wariant przejazdu.',
                'editorial_notes' => 'Połączyć z C-9 i C-11.',
            ],
            'C-11' => [
                'meaning' => 'Znak pozwala ominąć przeszkodę z obu stron. Nie narzuca jednego wariantu, ale wciąż wymaga świadomego wybrania toru przejazdu zgodnego z aktualną sytuacją na jezdni.',
                'placement' => 'Najczęściej pojawia się przy wyspach i przeszkodach, które można bezpiecznie ominąć zarówno z lewej, jak i z prawej strony.',
                'behavior' => 'Wybierz stronę objazdu wcześniej i nie zmieniaj decyzji w ostatniej chwili pod presją innych pojazdów. Nawet przy dopuszczeniu obu wariantów trzeba przejechać płynnie i przewidywalnie.',
                'mistake' => 'Częstym błędem jest interpretowanie znaku jako pełnej dowolności bez czytania, która strona będzie bezpieczniejsza przy aktualnym ruchu i układzie pasa.',
                'faq_duty' => 'Kierowca może ominąć przeszkodę z dowolnej strony, ale powinien zrobić to zgodnie z warunkami ruchu i bez tworzenia chaosu.',
                'faq_mistake' => 'Najczęściej problemem jest spóźniona decyzja o stronie objazdu i nerwowe korygowanie toru jazdy tuż przed przeszkodą.',
                'editorial_notes' => 'Dobra baza do materiału o rodzinie znaków objazdowych C-9 do C-11.',
            ],
            'C-12' => [
                'meaning' => 'Znak nakazuje ruch okrężny wokół wyspy centralnej. Informuje, że dalsza jazda ma odbywać się zgodnie z organizacją ronda, a nie jak na zwykłym skrzyżowaniu prostych wlotów.',
                'placement' => 'Najczęściej stoi na wlotach rond, zwłaszcza tam, gdzie kierowca dojeżdża do nich z odcinka zachęcającego do zbyt szybkiej jazdy albo z mniej czytelnego układu pasów.',
                'behavior' => 'Zweryfikuj pas ruchu jeszcze przed wjazdem na rondo, zwolnij i przygotuj obserwację pod pojazdy poruszające się po obwiedni. Sam znak nie zastępuje oceny pierwszeństwa na wjeździe.',
                'mistake' => 'Najczęstszym błędem jest zbyt późne czytanie pasa ruchu i traktowanie ronda jak prostego przecięcia dróg bez przygotowania do jazdy po obwiedni.',
                'faq_duty' => 'Kierowca ma wjechać w układ ruchu okrężnego i poruszać się zgodnie z geometrią ronda oraz organizacją pasa.',
                'faq_mistake' => 'Kursanci i kierowcy często spóźniają decyzję o pasie albo nie przygotowują obserwacji pod pojazdy już jadące po rondzie.',
                'editorial_notes' => 'Świetny kandydat do dalszego klastra z A-8 i zasadami jazdy po rondzie.',
            ],
            'C-13' => [
                'meaning' => 'Znak wskazuje drogę przeznaczoną dla rowerów. W praktyce porządkuje relacje między rowerzystą, pieszym i ruchem samochodowym na odcinku, który ma jasno określoną funkcję.',
                'placement' => 'Najczęściej pojawia się na początku wydzielonej drogi dla rowerów albo odcinka, na którym ruch rowerowy ma zostać odsunięty od jezdni ogólnej.',
                'behavior' => 'Czytaj znak przez pryzmat uczestnika ruchu, którego dotyczy, ale także przez relację z wlotami, przejazdami rowerowymi i miejscami przecinania torów ruchu. Kierowca powinien wcześniej zauważyć, że wjeżdża w otoczenie aktywnego ruchu rowerowego.',
                'mistake' => 'Najczęstszym błędem jest patrzenie na znak wyłącznie jak na informację dla rowerzysty i pomijanie jego znaczenia dla kierowcy zbliżającego się do przejazdu lub skrzyżowania z drogą rowerową.',
                'faq_duty' => 'Znak wyznacza drogę dla rowerów i porządkuje to, kto oraz na jakich zasadach powinien korzystać z tego odcinka.',
                'faq_mistake' => 'Kierowcy najczęściej za późno identyfikują relację z drogą rowerową i dopiero przy punkcie przecięcia torów ruchu zauważają jej znaczenie.',
                'editorial_notes' => 'Dobrze połączyć z C-13a oraz z C-13/16.',
            ],
            'C-13a' => [
                'meaning' => 'Znak informuje o końcu drogi dla rowerów. To ważny moment zmiany organizacji ruchu, bo kończy się wcześniej uporządkowany odcinek przeznaczony wyłącznie dla rowerzystów.',
                'placement' => 'Najczęściej stoi tam, gdzie wydzielona droga rowerowa przechodzi w inną organizację ruchu, kończy się lub włącza do jezdni ogólnej.',
                'behavior' => 'Zachowaj czujność na zmianę relacji ruchu i możliwe miejsca włączania się rowerzystów. Koniec drogi dla rowerów to często początek bardziej złożonego kontaktu między różnymi uczestnikami ruchu.',
                'mistake' => 'Częsty błąd to potraktowanie znaku jako mało istotnej informacji, mimo że właśnie za nim zmienia się sposób prowadzenia ruchu rowerowego.',
                'faq_duty' => 'Znak oznacza, że wcześniejszy odcinek drogi dla rowerów już się kończy i dalej obowiązuje inna organizacja ruchu.',
                'faq_mistake' => 'Najczęściej pomijana jest praktyczna konsekwencja znaku: zmiana miejsca, w którym rowerzysta może pojawić się względem jezdni ogólnej.',
                'editorial_notes' => 'Spiąć z C-13 i materiałem o przejazdach rowerowych.',
            ],
            'C-13/16' => [
                'meaning' => 'Znak wskazuje wspólną albo łączoną drogę dla rowerów i pieszych. To sygnał, że na danym odcinku organizacja ruchu łączy dwie grupy uczestników w jednej przestrzeni lub w jednym ciągu funkcjonalnym.',
                'placement' => 'Najczęściej występuje na ciągach pieszo-rowerowych, przy parkach, osiedlach i trasach miejskich, gdzie ruch pieszy i rowerowy ma być uporządkowany wspólnie.',
                'behavior' => 'Kierowca powinien odczytać znak jako zapowiedź miejsca o większej aktywności niechronionych uczestników ruchu. Szczególnie ważne staje się wcześniejsze czytanie przejazdów, zjazdów i skrzyżowań z takim ciągiem.',
                'mistake' => 'Najczęstszy błąd to pomijanie faktu, że w pobliżu znaku trzeba myśleć jednocześnie o pieszych i rowerzystach, a nie tylko o jednej grupie.',
                'faq_duty' => 'Znak porządkuje wspólny ciąg dla pieszych i rowerów i zapowiada relacje z obiema grupami uczestników ruchu.',
                'faq_mistake' => 'Kierowcy często zbyt wąsko patrzą na otoczenie i nie przygotowują się na obecność dwóch różnych grup uczestników w jednym miejscu.',
                'editorial_notes' => 'Później zestawić z C-13, C-16 i C-13a/16a.',
            ],
            'C-13a/16a' => [
                'meaning' => 'Znak informuje o końcu drogi dla rowerów i pieszych. To ważna zmiana organizacji ruchu, bo kończy się uporządkowany wspólny ciąg dla dwóch grup uczestników.',
                'placement' => 'Najczęściej stoi tam, gdzie ciąg pieszo-rowerowy przechodzi w inną organizację ruchu albo rozdziela się na osobne relacje.',
                'behavior' => 'Po zauważeniu znaku kierowca powinien zachować czujność na miejsca, w których pieszy lub rowerzysta wraca do innego układu ruchu. To często punkt zwiększonej złożoności sytuacji na drodze.',
                'mistake' => 'Najczęściej pomijana jest sama zmiana organizacji ruchu po końcu wspólnego ciągu i jej wpływ na możliwe pojawienie się rowerzystów lub pieszych w nowych relacjach.',
                'faq_duty' => 'Znak kończy wcześniejszy wspólny ciąg dla rowerów i pieszych i zapowiada przejście do innego układu ruchu.',
                'faq_mistake' => 'Kierowcy często nie łączą końca wspólnego ciągu z tym, że zachowanie pieszych i rowerzystów za znakiem może wyglądać inaczej niż chwilę wcześniej.',
                'editorial_notes' => 'Trzymać w parze z C-13/16.',
            ],
            'C-14' => [
                'meaning' => 'Znak wprowadza obowiązek jazdy z prędkością co najmniej wskazaną na znaku, o ile warunki ruchu i bezpieczeństwo na to pozwalają. W tej wersji znak pokazuje minimalne 40 km/h.',
                'placement' => 'Najczęściej pojawia się na odcinkach, gdzie organizacja ruchu wymaga utrzymania płynności i wyeliminowania zbyt wolnej jazdy utrudniającej ruch.',
                'behavior' => 'Czytaj znak rozsądnie: utrzymuj wymaganą minimalną prędkość tylko wtedy, gdy pozwalają na to warunki ruchu, widoczność i bezpieczeństwo. Znak nie nakazuje ignorować realnego zagrożenia na drodze.',
                'mistake' => 'Częsty błąd to potraktowanie minimalnej prędkości jako obowiązku absolutnego, bez uwzględnienia warunków ruchu, albo odwrotnie: jazda znacznie wolniej bez uzasadnienia mimo czytelnego nakazu.',
                'faq_duty' => 'Na tym odcinku trzeba utrzymywać co najmniej wskazaną prędkość, jeśli warunki ruchu i bezpieczeństwa na to pozwalają.',
                'faq_mistake' => 'Najczęściej myli się minimalną prędkość z „zalecaną” albo traktuje ją jak obowiązek niezależny od realnej sytuacji drogowej.',
                'editorial_notes' => 'Później połączyć z C-15 i z B-33 jako materiał o różnych rodzajach limitów prędkości.',
            ],
            'C-15' => [
                'meaning' => 'Znak odwołuje wcześniejszy nakaz utrzymywania minimalnej prędkości. Informuje, że od tego miejsca kierowca nie jest już związany limitem minimalnym wynikającym z wcześniejszego oznakowania.',
                'placement' => 'Najczęściej stoi na końcu odcinka, dla którego organizacja ruchu wymagała utrzymywania minimalnej prędkości.',
                'behavior' => 'Nie traktuj znaku jako zachęty do nagłego zwolnienia. To tylko koniec wcześniejszego obowiązku. Dalszą jazdę nadal trzeba dostosować do warunków ruchu i innych limitów na drodze.',
                'mistake' => 'Najczęstszy błąd to utożsamienie końca prędkości minimalnej z pełną dowolnością albo z początkiem bardzo wolnej jazdy bez czytania dalszego oznakowania.',
                'faq_duty' => 'Znak kończy wcześniejszy nakaz jazdy z minimalną prędkością i odwołuje ten obowiązek.',
                'faq_mistake' => 'Kierowcy czasem odczytują go jak zmianę maksymalnej prędkości, choć dotyczy wyłącznie końca wcześniejszego limitu minimalnego.',
                'editorial_notes' => 'Naturalna para do C-14.',
            ],
            'C-16' => [
                'meaning' => 'Znak wskazuje drogę dla pieszych. Porządkuje przestrzeń ruchu i informuje, że na tym odcinku głównym uczestnikiem jest pieszy.',
                'placement' => 'Najczęściej występuje na ciągach pieszych, w strefach miejskich, przy obiektach użyteczności publicznej oraz wszędzie tam, gdzie ruch pieszy ma otrzymać własny, jednoznacznie oznaczony przebieg.',
                'behavior' => 'Dla kierowcy znak jest ważny dlatego, że opisuje otoczenie drogi i miejsca możliwego kontaktu z ruchem pieszym. W pobliżu wlotów, przejazdów i skrzyżowań trzeba wcześniej zakładać obecność pieszych.',
                'mistake' => 'Najczęstszy błąd to uznanie znaku za informację wyłącznie dla pieszego, bez wyciągnięcia wniosku, że w otoczeniu odcinka trzeba wcześniej przygotować obserwację pod jego obecność.',
                'faq_duty' => 'Znak wyznacza drogę dla pieszych i określa funkcję odcinka, z którym ruch drogowy może się przecinać.',
                'faq_mistake' => 'Najczęściej kierowca za późno reaguje na obecność pieszych w pobliżu ciągu oznaczonego tym znakiem.',
                'editorial_notes' => 'Dobrze zestawić z C-16a i C-13/16.',
            ],
            'C-16a' => [
                'meaning' => 'Znak informuje o końcu drogi dla pieszych. To sygnał zmiany organizacji ruchu pieszych i możliwego wejścia ich w inne relacje przestrzenne względem jezdni.',
                'placement' => 'Najczęściej stoi tam, gdzie wydzielony ciąg pieszy kończy się albo przechodzi w strefę o innym sposobie prowadzenia ruchu.',
                'behavior' => 'Zachowaj czujność na to, jak zmienia się zachowanie pieszych po końcu wydzielonego ciągu. Taki punkt bywa początkiem bardziej złożonych kontaktów z ruchem ogólnym.',
                'mistake' => 'Najczęstszym błędem jest pomijanie znaczenia końca wydzielonego ciągu i brak przygotowania na zmianę sposobu poruszania się pieszych za znakiem.',
                'faq_duty' => 'Znak kończy wcześniejszą drogę dla pieszych i zamyka obowiązywanie tej organizacji ruchu na odcinku.',
                'faq_mistake' => 'Kierowcy często ignorują to, że za znakiem piesi mogą pojawiać się w innej relacji do jezdni niż chwilę wcześniej.',
                'editorial_notes' => 'Trzymać blisko C-16.',
            ],
            'C-17' => [
                'meaning' => 'Znak wskazuje nakazany kierunek dla pojazdów przewożących materiały niebezpieczne. To sposób bezpiecznego skierowania ruchu szczególnie wrażliwych przewozów poza niepożądane odcinki.',
                'placement' => 'Najczęściej występuje przy objazdach, w pobliżu tuneli, stref ochronnych i miejsc, gdzie przewóz materiałów niebezpiecznych ma zostać przeprowadzony tylko określoną trasą.',
                'behavior' => 'Jeśli przewóz podpada pod ten rodzaj ruchu, nie wolno traktować znaku orientacyjnie. Trzeba konsekwentnie podążać wskazaną relacją i wcześniej czytać wszystkie uzupełniające elementy oznakowania.',
                'mistake' => 'Najczęstszym błędem jest zbyt ogólne czytanie znaku bez odniesienia do rodzaju przewożonego ładunku i do ścisłego obowiązku przejazdu wskazanym kierunkiem.',
                'faq_duty' => 'Pojazd objęty znakiem ma jechać wyłącznie nakazanym kierunkiem wskazanym przez organizację ruchu.',
                'faq_mistake' => 'Błąd pojawia się wtedy, gdy kierowca zna ogólnie trasę, ale ignoruje lokalne nakierowanie przewozu w danym miejscu.',
                'editorial_notes' => 'Warto później połączyć z rodziną B-13 do B-14.',
            ],
            'C-18' => [
                'meaning' => 'Znak wprowadza obowiązek używania łańcuchów przeciwślizgowych na danym odcinku. To jednoznaczna informacja, że warunki ruchu wymagają dodatkowego zabezpieczenia przyczepności.',
                'placement' => 'Najczęściej pojawia się na drogach górskich, w rejonach o trudnych warunkach zimowych i tam, gdzie sama ostrożna jazda może nie wystarczyć do bezpiecznego przejazdu.',
                'behavior' => 'Nie próbuj „sprawdzić, czy jednak się uda” bez łańcuchów. Znak trzeba potraktować dosłownie i przygotować pojazd jeszcze przed wjazdem na odcinek objęty nakazem.',
                'mistake' => 'Najczęstszy błąd to bagatelizowanie znaku przy pozornie przejezdnej nawierzchni albo montowanie łańcuchów dopiero wtedy, gdy warunki stają się krytyczne.',
                'faq_duty' => 'Na oznaczonym odcinku trzeba używać łańcuchów przeciwślizgowych zgodnie z wymaganiem znaku.',
                'faq_mistake' => 'Kierowcy często przeceniają możliwości samego ogumienia zimowego i ignorują fakt, że znak wprowadza już obowiązek dodatkowego zabezpieczenia.',
                'editorial_notes' => 'Naturalna para do C-19.',
            ],
            'C-19' => [
                'meaning' => 'Znak odwołuje nakaz używania łańcuchów przeciwślizgowych. Informuje, że od tego miejsca wcześniejszy obowiązek już nie obowiązuje.',
                'placement' => 'Najczęściej stoi na końcu górskiego albo zimowego odcinka, dla którego wcześniej wprowadzono nakaz używania łańcuchów.',
                'behavior' => 'Czytaj znak jako koniec wcześniejszego obowiązku, ale nie jako sygnał do natychmiastowej utraty ostrożności. Warunki drogowe nadal mogą być trudne, nawet jeśli obowiązek formalnie się skończył.',
                'mistake' => 'Najczęstszy błąd to utożsamienie końca nakazu z końcem trudnych warunków na drodze i zbyt szybki powrót do zwykłego tempa jazdy.',
                'faq_duty' => 'Znak kończy wcześniejszy obowiązek jazdy z łańcuchami przeciwślizgowymi.',
                'faq_mistake' => 'Kierowcy czasem czytają znak jak potwierdzenie idealnych warunków, choć realna przyczepność nadal może wymagać ostrożności.',
                'editorial_notes' => 'Trzymać blisko C-18.',
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
            'meaning' => "Znak {$sign['code']} wprowadza obowiązek opisany jako {$lowerName}. Kierowca powinien odczytać go łącznie z organizacją pasa ruchu i zastosować się do nakazu bez zostawiania sobie uznaniowej dowolności.",
            'placement' => 'Najczęściej taki znak stoi tam, gdzie organizacja ruchu chce jednoznacznie narzucić kierunek przejazdu albo sposób korzystania z odcinka drogi.',
            'behavior' => 'Po zauważeniu znaku przygotuj pozycję auta, tor jazdy i obserwację odpowiednio wcześnie, tak aby manewr wykonać płynnie i zgodnie z nakazem.',
            'mistake' => 'Najczęstszy błąd to potraktowanie znaku nakazu jak sugestii albo odczytanie go zbyt późno, gdy kierowca jest już przy samym punkcie manewru.',
            'faq_duty' => 'Kierowca ma zastosować się do obowiązku wynikającego z organizacji ruchu opisanej przez znak.',
            'faq_mistake' => 'Najczęściej problemem jest spóźnione czytanie znaku i improwizowanie tuż przed manewrem.',
            'editorial_notes' => 'Przed publikacją doprecyzować scenariusz praktyczny i najbardziej podobne znaki z tej rodziny.',
        ];
    }

    protected function assetPath(string $slug): string
    {
        return 'traffic-signs/signs/mandatory/znak-'.$slug.'.webp';
    }
}
