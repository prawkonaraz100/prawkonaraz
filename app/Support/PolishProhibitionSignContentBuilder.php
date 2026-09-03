<?php

namespace App\Support;

use Illuminate\Support\Str;

class PolishProhibitionSignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $assetPath = $this->assetPath($sign);

        $content = match ($sign['code']) {
            'B-3', 'B-3a', 'B-4', 'B-5', 'B-6', 'B-7', 'B-8', 'B-9', 'B-10', 'B-11', 'B-12' => $this->entryBanContent($sign, $this->entryBanConfig()[$sign['code']]),
            'B-13', 'B-13a', 'B-14' => $this->dangerousCargoContent($sign, $this->dangerousCargoConfig()[$sign['code']]),
            'B-15', 'B-16', 'B-17', 'B-18' => $this->dimensionLimitContent($sign, $this->dimensionLimitConfig()[$sign['code']]),
            'B-19' => $this->axleLoadContent($sign),
            'B-21', 'B-22' => $this->turnBanContent($sign, $this->turnBanConfig()[$sign['code']]),
            'B-23' => $this->uTurnBanContent($sign),
            'B-24' => $this->endOfUTurnBanContent($sign),
            'B-25', 'B-26' => $this->overtakingBanContent($sign, $this->overtakingBanConfig()[$sign['code']]),
            'B-27', 'B-28' => $this->endOfOvertakingBanContent($sign, $this->endOfOvertakingBanConfig()[$sign['code']]),
            'B-29' => $this->hornBanContent($sign),
            'B-30' => $this->endOfHornBanContent($sign),
            'B-31' => $this->oncomingPriorityContent($sign),
            'B-32' => $this->customsStopContent($sign),
            'B-32a', 'B-32b', 'B-32c', 'B-32d', 'B-32e' => $this->checkpointStopContent($sign, $this->checkpointStopConfig()[$sign['code']]),
            'B-33' => $this->speedLimitContent($sign),
            'B-34' => $this->endOfSpeedLimitContent($sign),
            'B-37', 'B-38' => $this->alternatingParkingBanContent($sign, $this->alternatingParkingConfig()[$sign['code']]),
            'B-39' => $this->limitedParkingZoneContent($sign),
            'B-40' => $this->endOfLimitedParkingZoneContent($sign),
            'B-41' => $this->pedestrianBanContent($sign),
            'B-42' => $this->endOfBansContent($sign),
            'B-43' => $this->speedZoneContent($sign),
            'B-44' => $this->endOfSpeedZoneContent($sign),
            default => $this->fallbackContent($sign),
        };

        return array_merge($content, [
            'sort_order' => 100 + ($index * 10),
            'meta_title' => "{$sign['code']} {$sign['name']} - znaczenie, przepisy i zachowanie kierowcy",
            'meta_description' => "Sprawdź, co oznacza znak {$sign['code']} {$sign['name']}, kogo dotyczy, gdzie najczęściej występuje i jak powinien zachować się kierowca.",
            'image_path' => $assetPath,
            'image_alt' => $sign['name'],
            'image_width' => 1200,
            'image_height' => 1200,
            'og_image_path' => $assetPath,
            'og_image_alt' => $sign['name'],
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'legal_reference_label' => 'Rozporządzenie w sprawie znaków i sygnałów drogowych',
            'legal_reference_url' => 'https://isap.sejm.gov.pl/',
            'source_notes' => 'Treść bazowa przygotowana na podstawie urzędowego wykazu znaków zakazu i praktyki egzaminacyjnej. Przed publikacją wymagany finalny source check oraz doprecyzowanie wyjątków z tabliczek, jeśli występują.',
            'review_notes' => 'Szkic merytoryczny przygotowany do pełnego pokrycia kategorii znaków zakazu. Wymaga passu redakcyjnego i source check przed publikacją.',
        ]);
    }

    /**
     * @param  array{placement: string, behavior: string, mistake: string, scope: string}  $config
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function entryBanContent(array $sign, array $config): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Dotyczy dokładnie tej grupy uczestników lub pojazdów, którą pokazuje symbol na znaku.",
            'meaning' => "Od miejsca ustawienia znaku dalej nie mogą wjechać {$config['scope']}, chyba że tabliczka pod znakiem przewiduje wyjątek. Kierowca nie może tego znaku interpretować uznaniowo ani zakładać, że skoro droga jest pusta, zakaz go nie dotyczy.",
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => "To klasyczny znak zakazu wjazdu. Trzeba czytać go razem z organizacją ruchu oraz ewentualną tabliczką pod znakiem, bo to właśnie tam mogą znajdować się wyjątki dla wybranych pojazdów, służb albo dojazdu do posesji.",
            'fine_summary' => "Zignorowanie tego znaku może skończyć się mandatem, ale praktyczny problem jest szerszy: kierowca wjeżdża wtedy w strefę, w której dany rodzaj ruchu został wyłączony ze względów bezpieczeństwa, organizacyjnych albo technicznych.",
            'common_mistakes' => $config['mistake'],
            'faq_items' => [
                [
                    'question' => "Kogo dotyczy znak {$sign['code']}?",
                    'answer' => "Dotyczy dokładnie {$config['scope']}, których ruch został wyłączony przez ten znak. Zakres zakazu wynika z symbolu na znaku i ewentualnych tabliczek pod nim.",
                ],
                [
                    'question' => 'Czy tabliczka pod znakiem może dopuścić wyjątek?',
                    'answer' => 'Tak. Tabliczka pod znakiem może wskazać grupę pojazdów lub sytuację, dla których zakaz nie obowiązuje. Dlatego zawsze trzeba czytać znak razem z oznakowaniem dodatkowym.',
                ],
            ],
            'editorial_notes' => 'Powiązać z materiałami o wyjątkach z tabliczek oraz z przyszłym klastrem znaków nakazu i stref ograniczeń.',
        ];
    }

    /**
     * @param  array{placement: string, behavior: string, mistake: string, scope: string}  $config
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function dangerousCargoContent(array $sign, array $config): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Ogranicza wjazd pojazdów przewożących szczególnie wrażliwe lub niebezpieczne ładunki.",
            'meaning' => "Zakaz ma chronić infrastrukturę i otoczenie przed skutkami ewentualnego wycieku, zapłonu albo eksplozji. Od miejsca ustawienia znaku nie wolno kontynuować jazdy pojazdem objętym zakazem, chyba że organizacja ruchu przewiduje wyraźny wyjątek.",
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'To znak zakazu stosowany w miejscach o podwyższonej wrażliwości infrastrukturalnej lub środowiskowej. Kierowca wykonujący przewóz niebezpieczny ma obowiązek czytać go łącznie z oznakowaniem objazdu, tabliczkami dodatkowymi i lokalną organizacją ruchu.',
            'fine_summary' => 'Zlekceważenie tego znaku może oznaczać nie tylko mandat, ale także poważne konsekwencje administracyjne i ryzyko wjazdu w obszar, który został wyłączony dla konkretnego rodzaju przewozu ze względów bezpieczeństwa.',
            'common_mistakes' => $config['mistake'],
            'faq_items' => [
                [
                    'question' => "Dlaczego znak {$sign['code']} pojawia się w konkretnych miejscach?",
                    'answer' => 'Najczęściej dlatego, że dane miejsce ma podwyższone ryzyko dla środowiska, infrastruktury albo bezpieczeństwa ruchu i przewóz określonych materiałów powinien zostać skierowany objazdem.',
                ],
                [
                    'question' => 'Czy przy tym znaku trzeba sprawdzić oznakowanie objazdu?',
                    'answer' => 'Tak. W praktyce znaki dotyczące materiałów niebezpiecznych zwykle trzeba czytać razem z całą organizacją objazdu i dodatkowymi tablicami prowadzącymi.',
                ],
            ],
            'editorial_notes' => 'Warto później rozwinąć o osobny materiał wspierający o przewozach ADR i o różnicach między B-13, B-13a i B-14.',
        ];
    }

    /**
     * @param  array{placement: string, behavior: string, mistake: string, parameter: string}  $config
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function dimensionLimitContent(array $sign, array $config): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Kierowca musi porównać parametry swojego pojazdu lub zestawu z wartością wpisaną na znaku.",
            'meaning' => "Jeżeli rzeczywista {$config['parameter']} pojazdu przekracza wartość pokazaną na znaku, wjazd jest zabroniony. To zakaz twardy: nie wolno go ignorować tylko dlatego, że przejazd wydaje się możliwy na oko.",
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'Znaki ograniczeń gabarytowych i masowych chronią obiekty inżynieryjne, jezdnię oraz bezpieczeństwo ruchu. Kierowca ma obowiązek znać parametry swojego pojazdu, ładunku i zestawu, a nie oceniać ich intuicyjnie w ostatniej chwili.',
            'fine_summary' => 'Zignorowanie takiego zakazu może skończyć się mandatem, ale dużo groźniejsze bywa zablokowanie przejazdu, uszkodzenie infrastruktury albo konieczność wycofywania dużego pojazdu z miejsca bez zapasu manewru.',
            'common_mistakes' => $config['mistake'],
            'faq_items' => [
                [
                    'question' => "Co porównać przy znaku {$sign['code']}?",
                    'answer' => "Trzeba porównać rzeczywistą {$config['parameter']} pojazdu lub zestawu z wartością wpisaną na znaku. Nie wystarczy ogólne przekonanie, że 'powinno się zmieścić'.",
                ],
                [
                    'question' => 'Czy ładunek i przyczepa też mają znaczenie?',
                    'answer' => 'Tak. W praktyce trzeba brać pod uwagę cały zestaw i rzeczywiste parametry w danym momencie, a nie tylko bazowe dane samego ciągnika lub auta.',
                ],
            ],
            'editorial_notes' => 'Dobrze powiązać z przyszłymi treściami o masie rzeczywistej, DMC i praktyce doboru trasy dla większych pojazdów.',
        ];
    }

    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function axleLoadContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Chodzi konkretnie o nacisk pojedynczej osi napędowej, a nie o ogólną masę całkowitą pojazdu.",
            'meaning' => 'Jeżeli rzeczywisty nacisk pojedynczej osi napędowej przekracza wartość z znaku, wjazd jest zabroniony. To właśnie ta cecha odróżnia B-19 od znaków dotyczących szerokości, wysokości czy całkowitej masy pojazdu.',
            'placement' => 'Najczęściej pojawia się na drogach lub obiektach, których konstrukcja nie powinna być obciążana zbyt dużym naciskiem osiowym, nawet jeżeli sam pojazd mieści się w innych limitach.',
            'driver_behavior' => 'Przed wjazdem trzeba mieć pewność, jaki nacisk wywiera pojedyncza oś napędowa Twojego pojazdu lub zestawu. Jeśli parametr przekracza wartość na znaku, nie wolno wjechać dalej i trzeba szukać alternatywnej trasy.',
            'legal_summary' => 'B-19 odnosi się do bardzo konkretnego parametru technicznego. Kierowca i przewoźnik nie mogą zastępować go ogólną wiedzą o dopuszczalnej masie całkowitej, bo znak chroni infrastrukturę przed naciskiem skupionym na osi napędowej.',
            'fine_summary' => 'Zignorowanie B-19 może skończyć się mandatem, ale praktycznie oznacza też ryzyko naruszenia warunków dopuszczalnego przejazdu po drodze lub obiekcie narażonym na uszkodzenia od zbyt dużego nacisku osiowego.',
            'common_mistakes' => 'Najczęstszy błąd to mylenie nacisku osi napędowej z całkowitą masą pojazdu. Drugim problemem jest założenie, że jeśli auto mieści się w innych limitach, to B-19 również go nie dotyczy.',
            'faq_items' => [
                [
                    'question' => 'Czym B-19 różni się od B-18?',
                    'answer' => 'B-18 dotyczy rzeczywistej masy całkowitej pojazdu, a B-19 odnosi się do nacisku pojedynczej osi napędowej. To dwa różne parametry techniczne.',
                ],
                [
                    'question' => 'Czy przy B-19 wystarczy znać DMC pojazdu?',
                    'answer' => 'Nie. Trzeba wiedzieć, jaki jest rzeczywisty nacisk pojedynczej osi napędowej, bo właśnie ten parametr ogranicza znak B-19.',
                ],
            ],
            'editorial_notes' => 'Przed publikacją zrobić dodatkowy pass źródłowy pod aktualne brzmienie znaku po zmianie z 2021 r.',
        ];
    }

    /**
     * @param  array{placement: string, behavior: string, mistake: string, direction: string}  $config
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function turnBanContent(array $sign, array $config): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Od miejsca jego ustawienia nie wolno wykonać manewru skrętu w wskazanym kierunku.",
            'meaning' => "Zakaz dotyczy skrętu {$config['direction']} w miejscu objętym oznakowaniem. Kierowca musi podporządkować się organizacji ruchu nawet wtedy, gdy skręt wydaje się wygodny albo chwilowo bezkolizyjny.",
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'To znak zakazu manewru. Odczytuje się go razem z pasami ruchu, sygnalizacją i oznakowaniem poziomym, bo często właśnie cały układ skrzyżowania wyjaśnia, dlaczego dany skręt został wyłączony.',
            'fine_summary' => 'Zignorowanie znaku może skończyć się mandatem, ale jeszcze częściej prowadzi do wjechania w tor ruchu, którego organizacja skrzyżowania nie przewiduje dla danego kierunku jazdy.',
            'common_mistakes' => $config['mistake'],
            'faq_items' => [
                [
                    'question' => "Czy znak {$sign['code']} dotyczy tylko samego skrętu?",
                    'answer' => 'Tak, zakazuje konkretnego manewru wskazanego na znaku. Nie oznacza automatycznie zakazu jazdy prosto, jeśli inne oznakowanie tego nie wprowadza.',
                ],
                [
                    'question' => 'Czy trzeba patrzeć też na strzałki na pasach?',
                    'answer' => 'Zdecydowanie tak. Znaki zakazu skrętu bardzo często działają razem z oznakowaniem poziomym i sygnalizacją kierunkową.',
                ],
            ],
            'editorial_notes' => 'Powiązać z materiałem wspierającym o relacji znaków zakazu skrętu do strzałek kierunkowych i sygnalizacji.',
        ];
    }

    protected function uTurnBanContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. W miejscu objętym oznakowaniem kierowca nie może zawrócić.",
            'meaning' => 'Zakaz dotyczy manewru zawracania, nawet jeśli sam skręt w lewo byłby dopuszczony przez inne elementy organizacji ruchu. To ważne rozróżnienie, bo wielu kierowców traktuje oba manewry jak jedną rzecz.',
            'placement' => 'Najczęściej pojawia się na skrzyżowaniach, wlotach dróg wielopasowych i miejscach o dużym natężeniu ruchu, gdzie zawracanie mogłoby zaburzać płynność albo bezpieczeństwo ruchu.',
            'driver_behavior' => 'Jeśli potrzebujesz zmienić kierunek jazdy, szukaj miejsca wyznaczonego do zawracania albo takiego, w którym manewr nie jest objęty zakazem. Nie próbuj improwizować zawracania "na dwa razy" w obrębie skrzyżowania objętego znakiem.',
            'legal_summary' => 'To znak zakazu konkretnego manewru. Trzeba go odczytywać łącznie z układem pasów, sygnalizacją i geometrią skrzyżowania, bo właśnie tam najczęściej wychodzi praktyczna różnica między zakazem zawracania a zakazem skrętu.',
            'fine_summary' => 'Zignorowanie B-23 może skończyć się mandatem, ale częściej realnym ryzykiem przecięcia torów ruchu kilku relacji jednocześnie. To jeden z manewrów, które bardzo szybko destabilizują ruch na skrzyżowaniu.',
            'common_mistakes' => 'Najczęstszy błąd to utożsamianie zakazu zawracania z zakazem skrętu w lewo albo odwrotnie. Drugim problemem jest próba wykonania zawracania przez kilka krótkich manewrów w miejscu, gdzie organizacja ruchu wprost tego zabrania.',
            'faq_items' => [
                [
                    'question' => 'Czy przy B-23 mogę skręcić w lewo?',
                    'answer' => 'To zależy od pozostałego oznakowania. B-23 zakazuje zawracania, ale nie zawsze oznacza automatycznie zakaz skrętu w lewo.',
                ],
                [
                    'question' => 'Czym B-23 różni się od B-21?',
                    'answer' => 'B-21 zakazuje skrętu w lewo, a B-23 zakazuje zawracania. To dwa różne manewry i dwa różne zakazy.',
                ],
            ],
            'editorial_notes' => 'Mocno powiązać z B-21 i późniejszym supporting page o różnicy skręt w lewo vs zawracanie.',
        ];
    }

    protected function endOfUTurnBanContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Informuje, że od tego miejsca przestaje obowiązywać wcześniejszy zakaz zawracania.",
            'meaning' => 'Od znaku B-24 kierowca może znowu rozważać zawracanie, ale tylko wtedy, gdy nie zabraniają tego inne znaki, sygnalizacja, oznakowanie poziome albo ogólne przepisy ruchu drogowego.',
            'placement' => 'Najczęściej pojawia się za odcinkiem lub skrzyżowaniem, na którym obowiązywał zakaz zawracania i gdzie kierowca potrzebuje czytelnego sygnału, że ograniczenie już nie działa.',
            'driver_behavior' => 'Nie traktuj B-24 jako zachęty do natychmiastowego manewru. Najpierw sprawdź, czy miejsce rzeczywiście nadaje się do zawracania i czy nie ma innych ograniczeń wynikających z pasa ruchu, widoczności albo przepisów ogólnych.',
            'legal_summary' => 'To znak odwołujący wcześniejszy zakaz. Nie tworzy on samodzielnie "prawa do zawracania" w dowolnym miejscu, ale usuwa konkretne ograniczenie wprowadzone wcześniej znakiem B-23.',
            'fine_summary' => 'Sam znak B-24 nie generuje ryzyka mandatu. Problem pojawia się wtedy, gdy kierowca po jego minięciu uzna, że może zawracać gdziekolwiek, ignorując inne zakazy i warunki bezpieczeństwa.',
            'common_mistakes' => 'Najczęstszy błąd to odczytywanie B-24 jako automatycznej zgody na zawracanie od razu po minięciu znaku. W praktyce trzeba jeszcze sprawdzić resztę organizacji ruchu i warunki wykonania manewru.',
            'faq_items' => [
                [
                    'question' => 'Czy B-24 oznacza, że od razu mogę zawrócić?',
                    'answer' => 'Nie zawsze. B-24 kończy wcześniejszy zakaz zawracania, ale nadal musisz uwzględnić inne znaki, oznakowanie poziome i warunki bezpieczeństwa.',
                ],
                [
                    'question' => 'Czy po B-24 mogą nadal obowiązywać inne ograniczenia?',
                    'answer' => 'Tak. Znak odwołuje tylko zakaz zawracania, a nie wszystkie możliwe zakazy czy ograniczenia w danym miejscu.',
                ],
            ],
            'editorial_notes' => 'Połączyć z B-23 oraz z przyszłym materiałem o odwoływaniu zakazów i znaczeniu znaków kończących ograniczenia.',
        ];
    }

    /**
     * @param  array{placement: string, behavior: string, mistake: string, scope: string}  $config
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function overtakingBanContent(array $sign, array $config): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Ogranicza możliwość wyprzedzania dla uczestników ruchu objętych zakazem.",
            'meaning' => "Od miejsca ustawienia znaku {$config['scope']} nie mogą wykonywać manewru wyprzedzania w zakresie objętym zakazem. Znak ma porządkować bezpieczeństwo na odcinku, gdzie wyprzedzanie jest szczególnie ryzykowne albo kolizyjne.",
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'To znak zakazu manewru. W praktyce trzeba czytać go razem z geometrią drogi, widocznością, oznakowaniem poziomym i klasyfikacją pojazdu, bo to wszystko wpływa na to, kogo i w jakim zakresie ograniczenie dotyczy.',
            'fine_summary' => 'Zignorowanie zakazu wyprzedzania może skończyć się mandatem, ale przede wszystkim zwiększa ryzyko wejścia w konflikt z ruchem z przeciwka lub z pojazdem wyprzedzanym na odcinku bez zapasu bezpieczeństwa.',
            'common_mistakes' => $config['mistake'],
            'faq_items' => [
                [
                    'question' => "Kogo dotyczy znak {$sign['code']}?",
                    'answer' => "Dotyczy {$config['scope']}, które od tego miejsca nie mogą wykonywać manewru wyprzedzania w zakresie wynikającym z organizacji ruchu.",
                ],
                [
                    'question' => 'Czy zakaz wyprzedzania działa niezależnie od tego, że droga wydaje się pusta?',
                    'answer' => 'Tak. Kierowca nie może samodzielnie uchylać znaku tylko dlatego, że w danym momencie nie widzi pojazdu z przeciwka albo uważa odcinek za bezpieczny.',
                ],
            ],
            'editorial_notes' => 'Powiązać z końcem zakazu wyprzedzania i z przyszłymi treściami o relacji znaków do linii ciągłej.',
        ];
    }

    /**
     * @param  array{scope: string, behavior: string, mistake: string}  $config
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function endOfOvertakingBanContent(array $sign, array $config): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Informuje, że kończy się wcześniejszy zakaz wyprzedzania dotyczący wskazanej grupy pojazdów.",
            'meaning' => "Od tego miejsca {$config['scope']} nie są już objęte wcześniejszym zakazem wyprzedzania wynikającym z odpowiadającego mu znaku zakazu. Nie oznacza to jednak, że każdy manewr wyprzedzania staje się automatycznie dozwolony w każdych warunkach.",
            'placement' => 'Najczęściej pojawia się za odcinkiem niebezpiecznym, na którym obowiązywało ograniczenie wyprzedzania i gdzie kierowca powinien dostać czytelny sygnał, że to konkretne ograniczenie właśnie wygasa.',
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'To znak odwołujący wcześniejszy zakaz. Nie znosi ogólnych obowiązków dotyczących bezpiecznego wyprzedzania i nie uchyla innych ograniczeń, takich jak oznakowanie poziome czy warunki widoczności.',
            'fine_summary' => 'Sam znak końca zakazu nie jest ryzykowny. Problem powstaje wtedy, gdy kierowca traktuje go jako zgodę na natychmiastowe wyprzedzanie bez oceny widoczności, linii na jezdni i sytuacji na drodze.',
            'common_mistakes' => $config['mistake'],
            'faq_items' => [
                [
                    'question' => "Czy po znaku {$sign['code']} mogę od razu wyprzedzać?",
                    'answer' => 'Dopiero jeśli pozwalają na to warunki ruchu, widoczność i pozostałe oznakowanie. Znak odwołuje konkretny zakaz, ale nie zwalnia z ogólnych zasad bezpieczeństwa.',
                ],
                [
                    'question' => 'Czy znak końca zakazu znosi też ograniczenia z linii ciągłej?',
                    'answer' => 'Nie. Oznakowanie poziome i inne przepisy nadal obowiązują niezależnie od znaku końca zakazu wyprzedzania.',
                ],
            ],
            'editorial_notes' => 'Spinać z B-25/B-26 i późniejszym materiałem o tym, dlaczego koniec zakazu nie zawsze oznacza możliwość manewru od razu.',
        ];
    }

    protected function hornBanContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. W miejscu objętym oznakowaniem nie wolno używać sygnału dźwiękowego, poza sytuacjami wymagającymi ostrzeżenia o bezpośrednim zagrożeniu.",
            'meaning' => 'To zakaz mający ograniczyć hałas i nadużywanie klaksonu tam, gdzie nie jest to uzasadnione bezpieczeństwem ruchu. Kierowca nie może traktować sygnału dźwiękowego jako narzędzia do poganiania innych uczestników ruchu lub wyrażania irytacji.',
            'placement' => 'Najczęściej pojawia się w pobliżu szpitali, sanatoriów, obszarów szczególnie wrażliwych na hałas oraz tam, gdzie organizacja ruchu chce wyeliminować zbędne używanie klaksonu.',
            'driver_behavior' => 'Zachowaj ostrożność i polegaj przede wszystkim na obserwacji, prędkości oraz bezpiecznej odległości. Sygnału dźwiękowego użyj wyłącznie wtedy, gdy rzeczywiście trzeba ostrzec o bezpośrednim niebezpieczeństwie.',
            'legal_summary' => 'Zakaz nie eliminuje wyjątków wynikających z konieczności zapobieżenia zagrożeniu. To ważne: znak ogranicza używanie sygnału w zwykłych sytuacjach drogowych, ale nie znosi obowiązku ostrzeżenia, gdy bezpieczeństwo tego wymaga.',
            'fine_summary' => 'Nadużywanie sygnału dźwiękowego w miejscu objętym zakazem może skończyć się mandatem, ale najczęściej problemem jest po prostu niepotrzebne naruszenie zasad ruchu i porządku w strefie wrażliwej na hałas.',
            'common_mistakes' => 'Najczęstszy błąd to przekonanie, że skoro znak zakazuje używania klaksonu, nie wolno go użyć nigdy. Drugi problem to traktowanie sygnału dźwiękowego jako zwykłego środka komunikacji z innymi kierowcami.',
            'faq_items' => [
                [
                    'question' => 'Czy przy B-29 mogę użyć klaksonu w razie zagrożenia?',
                    'answer' => 'Tak. Znak nie znosi możliwości ostrzeżenia o bezpośrednim niebezpieczeństwie. Zakazuje natomiast używania sygnału dźwiękowego bez takiej potrzeby.',
                ],
                [
                    'question' => 'Po co ustawia się znak B-29?',
                    'answer' => 'Najczęściej po to, żeby ograniczyć zbędny hałas w miejscach szczególnie wrażliwych i zdyscyplinować sposób używania sygnałów dźwiękowych przez kierowców.',
                ],
            ],
            'editorial_notes' => 'Dobre do połączenia z materiałem o wyjątkach od zakazów i praktyce używania sygnałów dźwiękowych w ruchu miejskim.',
        ];
    }

    protected function endOfHornBanContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Od tego miejsca przestaje obowiązywać wcześniejszy zakaz używania sygnałów dźwiękowych.",
            'meaning' => 'Znak odwołuje poprzednie ograniczenie, ale nie oznacza zachęty do nadużywania klaksonu. Po jego minięciu wracają zwykłe zasady używania sygnału dźwiękowego wynikające z przepisów ogólnych.',
            'placement' => 'Najczęściej stoi za odcinkiem wrażliwym na hałas, np. po minięciu obszaru szpitala, uzdrowiska lub strefy, w której wcześniej obowiązywał szczególny rygor akustyczny.',
            'driver_behavior' => 'Po minięciu znaku nadal używaj sygnału dźwiękowego oszczędnie i tylko wtedy, gdy jest to uzasadnione sytuacją drogową. Sam koniec zakazu nie oznacza swobody dowolnego klaksonowania.',
            'legal_summary' => 'To znak odwołujący wcześniejszy zakaz. Zdejmuje konkretne ograniczenie w danym miejscu, ale nie zmienia ogólnych zasad używania sygnału dźwiękowego wyłącznie zgodnie z jego funkcją ostrzegawczą.',
            'fine_summary' => 'Sam znak nie rodzi ryzyka naruszenia. Problem pojawia się wtedy, gdy kierowca po minięciu B-30 traktuje koniec zakazu jako przyzwolenie na używanie klaksonu bez potrzeby.',
            'common_mistakes' => 'Najczęstszy błąd to odczytywanie B-30 jako zgody na pełną dowolność. W praktyce po znaku po prostu wracają zwykłe reguły korzystania z sygnału dźwiękowego.',
            'faq_items' => [
                [
                    'question' => 'Czy po B-30 mogę używać klaksonu bez ograniczeń?',
                    'answer' => 'Nie. Po znaku kończy się szczególny zakaz, ale nadal obowiązują ogólne przepisy, zgodnie z którymi sygnału dźwiękowego używa się przede wszystkim w funkcji ostrzegawczej.',
                ],
                [
                    'question' => 'Co właściwie odwołuje B-30?',
                    'answer' => 'Odwołuje wcześniejszy zakaz używania sygnałów dźwiękowych wprowadzony znakiem B-29 na poprzednim odcinku drogi.',
                ],
            ],
            'editorial_notes' => 'Naturalna para dla B-29; warto je później spiąć supporting page o wyjątkach od zakazu używania klaksonu.',
        ];
    }

    protected function oncomingPriorityContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Kierowca ma obowiązek ustąpić pierwszeństwa pojazdom nadjeżdżającym z przeciwka na przewężonym odcinku.",
            'meaning' => 'To znak porządkujący pierwszeństwo na odcinku, na którym dwa pojazdy nie mogą bezpiecznie minąć się jednocześnie. Nie chodzi o ogólną uprzejmość, tylko o konkretny obowiązek wynikający z organizacji ruchu.',
            'placement' => 'Najczęściej pojawia się przed zwężeniami, wąskimi mostkami, przejazdami technicznymi i innymi miejscami, gdzie szerokość jezdni lub obiektu wymusza uprzednie ustalenie, kto wjeżdża pierwszy.',
            'driver_behavior' => 'Dojeżdżając do zwężenia, oceń sytuację odpowiednio wcześnie. Jeśli z przeciwka nadjeżdża pojazd, któremu trzeba ustąpić, zatrzymaj się przed przewężeniem i poczekaj, aż odcinek będzie wolny.',
            'legal_summary' => 'Choć wizualnie znak nie wygląda jak klasyczne ograniczenie prędkości czy wjazdu, w praktyce działa jak konkretny zakaz wjazdu w przewężenie bez pierwszeństwa. Kierowca ma obowiązek podporządkować się temu układowi, a nie próbować "zmieścić się na styk".',
            'fine_summary' => 'Zignorowanie B-31 może doprowadzić do zablokowania zwężenia albo konfliktu czołowego z pojazdem z przeciwka. To sytuacja bardzo praktyczna: błąd szybko przekłada się na realne zagrożenie, a nie tylko na formalne naruszenie.',
            'common_mistakes' => 'Najczęstszy błąd to wjazd w przewężenie z założeniem, że drugi kierowca "jakoś poczeka". Drugim problemem jest zbyt późna ocena, przez co pojazd wjeżdża w wąski odcinek bez realnej możliwości ustąpienia.',
            'faq_items' => [
                [
                    'question' => 'Kiedy znak B-31 obowiązuje najbardziej praktycznie?',
                    'answer' => 'Przed przewężeniem lub wąskim obiektem, gdzie trzeba wyraźnie ustalić, który kierunek ma pierwszeństwo przejazdu.',
                ],
                [
                    'question' => 'Czy przy B-31 mogę wjechać pierwszy, jeśli jestem już bardzo blisko zwężenia?',
                    'answer' => 'Tylko wtedy, gdy sytuacja naprawdę na to pozwala i nie zmusisz pojazdu z przeciwka do wycofywania się lub zatrzymania w niebezpiecznym miejscu.',
                ],
            ],
            'editorial_notes' => 'Dobrze połączyć z materiałem o zwężeniach i porównaniem do znaków informujących o pierwszeństwie na odcinku przewężonym.',
        ];
    }

    protected function customsStopContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Nakazuje zatrzymanie pojazdu w miejscu kontroli wskazanym przez uprawnione służby.",
            'meaning' => 'To nie jest zwykły znak ostrzegawczy ani sugestia zwolnienia. Kierowca ma zatrzymać pojazd zgodnie z organizacją kontroli i poleceniami służb uprawnionych do jej prowadzenia.',
            'placement' => 'Najczęściej pojawia się przy punktach kontroli celnej, granicznej lub w miejscach, w których organizacja ruchu przewiduje obowiązkowe zatrzymanie przed dalszym przejazdem.',
            'driver_behavior' => 'Zwoliń odpowiednio wcześnie, przygotuj się do pełnego zatrzymania i obserwuj polecenia służb lub sygnalizacji pomocniczej. Nie próbuj przejeżdżać przez punkt kontroli na zasadzie "skoro nikt mnie nie zatrzymał".',
            'legal_summary' => 'B-32 wprowadza obowiązek zatrzymania w miejscu kontroli. W praktyce jego znaczenie trzeba czytać razem z organizacją danego punktu oraz poleceniami funkcjonariuszy, bo to one decydują o dalszym przejeździe.',
            'fine_summary' => 'Zignorowanie B-32 to nie tylko wykroczenie drogowe, ale też wejście w konflikt z formalnym reżimem kontroli. Kierowca naraża się wtedy na dużo poważniejsze konsekwencje niż zwykłe naruszenie znaku porządkowego.',
            'common_mistakes' => 'Najczęstszy błąd to potraktowanie B-32 jak zwykłego znaku "zwolnij". Drugim problemem jest założenie, że jeśli stanowisko kontroli wygląda na puste, znak przestaje mieć znaczenie.',
            'faq_items' => [
                [
                    'question' => 'Czy B-32 wymaga pełnego zatrzymania pojazdu?',
                    'answer' => 'Tak. To znak nakazujący zatrzymanie w punkcie kontroli, a nie samo przygotowanie się do ewentualnego postoju.',
                ],
                [
                    'question' => 'Czy znak B-32 działa bez polecenia funkcjonariusza?',
                    'answer' => 'Tak, bo już sama organizacja ruchu wprowadza obowiązek zatrzymania. Dalszy sposób przejazdu zależy później od organizacji punktu i poleceń służb.',
                ],
            ],
            'editorial_notes' => 'To znak niszowy, ale warto opracować go starannie ze względu na wysoką formalność obowiązku zatrzymania.',
        ];
    }

    /**
     * @param  array{placement: string, behavior: string, legal_summary: string, mistake: string, faq_question: string, faq_answer: string, editorial_notes: string}  $config
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function checkpointStopContent(array $sign, array $config): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Nakazuje zatrzymanie pojazdu przed miejscem objętym szczególną organizacją ruchu lub kontrolą.",
            'meaning' => 'To znak wymagający pełnego zatrzymania. Kierowca nie może ograniczyć się do zwolnienia ani samodzielnie uznać, że skoro miejsce wygląda na wolne, można przejechać bez postoju.',
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => $config['legal_summary'],
            'fine_summary' => 'Zignorowanie tego znaku może oznaczać nie tylko wykroczenie drogowe, ale też wejście w konflikt z procedurą kontroli albo z zasadami bezpiecznego przejazdu w miejscu o podwyższonym ryzyku.',
            'common_mistakes' => $config['mistake'],
            'faq_items' => [
                [
                    'question' => "Czy przy znaku {$sign['code']} trzeba zatrzymać pojazd całkowicie?",
                    'answer' => 'Tak. To znak zatrzymania, więc samo mocne zwolnienie nie wystarcza.',
                ],
                [
                    'question' => $config['faq_question'],
                    'answer' => $config['faq_answer'],
                ],
            ],
            'editorial_notes' => $config['editorial_notes'],
        ];
    }

    protected function speedLimitContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Wprowadza maksymalną prędkość wskazaną cyfrą na znaku.",
            'meaning' => 'Od miejsca ustawienia znaku nie wolno jechać szybciej niż wynika to z podanej wartości. To ograniczenie działa niezależnie od subiektywnego wrażenia kierowcy, że warunki pozwalają na więcej.',
            'placement' => 'Najczęściej występuje przed zakrętami, zwężeniami, przejściami, obszarami zabudowanymi, robotami drogowymi i wszędzie tam, gdzie organizacja ruchu wymaga konkretnego limitu prędkości.',
            'driver_behavior' => 'Zacznij redukować prędkość przed znakiem tak, aby w miejscu jego obowiązywania już jechać zgodnie z limitem. Nie odkładaj hamowania na ostatnią chwilę i nie traktuj ograniczenia jako "sugestii".',
            'legal_summary' => 'B-33 wprowadza konkretny limit prędkości na danym odcinku. Kierowca musi go respektować do miejsca odwołania, kolejnego znaku zmieniającego limit albo końca strefy wynikającego z organizacji ruchu.',
            'fine_summary' => 'Przekroczenie prędkości przy B-33 to klasyczne źródło mandatu i punktów karnych, ale przede wszystkim jeden z najczęstszych powodów utraty zapasu bezpieczeństwa w miejscu, które już z założenia wymaga wolniejszej jazdy.',
            'common_mistakes' => 'Najczęstszy błąd to rozpoczęcie hamowania dopiero po minięciu znaku. Drugim problemem jest założenie, że jeśli warunki wydają się dobre, ograniczenie można potraktować z marginesem uznaniowym.',
            'faq_items' => [
                [
                    'question' => 'Od kiedy obowiązuje ograniczenie z B-33?',
                    'answer' => 'Od miejsca ustawienia znaku. Kierowca powinien więc zredukować prędkość odpowiednio wcześniej.',
                ],
                [
                    'question' => 'Kiedy przestaje działać B-33?',
                    'answer' => 'Do czasu odwołania znakiem końca ograniczenia, nowym limitem albo zmianą organizacji ruchu, która zgodnie z przepisami kończy jego obowiązywanie.',
                ],
            ],
            'editorial_notes' => 'Dobre do mocnego linkowania z przyszłych treści o strefach ograniczonej prędkości i mandatach.',
        ];
    }

    protected function endOfSpeedLimitContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Informuje, że kończy się wcześniejsze ograniczenie prędkości wprowadzone znakiem B-33.",
            'meaning' => 'Po minięciu B-34 przestaje obowiązywać konkretny limit wskazany wcześniej znakiem B-33. Nie oznacza to jednak dowolności: dalej trzeba stosować się do ogólnych limitów i pozostałego oznakowania.',
            'placement' => 'Najczęściej pojawia się za odcinkiem, dla którego wprowadzono czasowe albo miejscowe ograniczenie prędkości i gdzie kierowca powinien dostać jasny sygnał, że ten konkretny limit właśnie wygasa.',
            'driver_behavior' => 'Nie przyspieszaj odruchowo tylko dlatego, że ograniczenie się skończyło. Najpierw oceń warunki drogi, otoczenie i to, jaki limit obowiązuje dalej z przepisów ogólnych albo z kolejnych znaków.',
            'legal_summary' => 'B-34 odwołuje wcześniejszy limit z B-33, ale nie usuwa innych ograniczeń. Kierowca po minięciu znaku wraca do limitów wynikających z ogólnych zasad albo z kolejnego obowiązującego oznakowania.',
            'fine_summary' => 'Sam znak końca ograniczenia nie generuje ryzyka naruszenia. Problem pojawia się wtedy, gdy kierowca utożsamia go z pełną swobodą prędkości i ignoruje pozostałe przepisy lub warunki drogi.',
            'common_mistakes' => 'Najczęstszy błąd to traktowanie B-34 jako zgody na natychmiastowe mocne przyspieszenie bez sprawdzenia, jaki limit obowiązuje dalej. Często kierowcy zapominają też o ograniczeniach wynikających z obszaru zabudowanego albo innych znaków.',
            'faq_items' => [
                [
                    'question' => 'Czy po B-34 mogę od razu jechać z dowolną prędkością?',
                    'answer' => 'Nie. Kończy się tylko wcześniejsze ograniczenie z B-33. Dalej obowiązują ogólne limity i pozostałe znaki na drodze.',
                ],
                [
                    'question' => 'Co dokładnie odwołuje B-34?',
                    'answer' => 'Odwołuje konkretny limit wprowadzony wcześniej znakiem B-33, a nie wszystkie możliwe ograniczenia prędkości w okolicy.',
                ],
            ],
            'editorial_notes' => 'Naturalna para dla B-33 i dobra baza pod supporting page o końcu ograniczenia vs koniec strefy.',
        ];
    }

    /**
     * @param  array{placement: string, behavior: string, mistake: string, day_rule: string}  $config
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    protected function alternatingParkingBanContent(array $sign, array $config): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Wprowadza zakaz postoju według zasady opartej na dniach {$config['day_rule']}.",
            'meaning' => 'To znak porządkujący parkowanie tam, gdzie trzeba zachować możliwość sprzątania, odśnieżania albo utrzymania płynności ruchu po jednej stronie ulicy. Kierowca musi pilnować nie tylko miejsca, ale też daty i momentu zmiany obowiązywania zakazu.',
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'B-37 i B-38 wprowadzają zmienny zakaz postoju zależny od dnia miesiąca. W praktyce trzeba bardzo uważnie czytać znak razem z tabliczkami dotyczącymi godzin albo sposobu stosowania zakazu.',
            'fine_summary' => 'Naruszenie zwykle kończy się mandatem i może prowadzić do odholowania, jeśli pojazd utrudnia organizację ruchu albo prace porządkowe, dla których wprowadzono ten system postoju naprzemiennego.',
            'common_mistakes' => $config['mistake'],
            'faq_items' => [
                [
                    'question' => "Kiedy obowiązuje znak {$sign['code']}?",
                    'answer' => "W dniach {$config['day_rule']}, zgodnie z zasadą opisaną na znaku i ewentualnych tabliczkach dodatkowych.",
                ],
                [
                    'question' => 'Na co uważać najbardziej przy znakach naprzemiennego postoju?',
                    'answer' => 'Na zmianę dnia i godziny obowiązywania. Wielu kierowców poprawnie parkuje wieczorem, ale zapomina, że po północy sytuacja prawna miejsca może się zmienić.',
                ],
            ],
            'editorial_notes' => 'Naturalnie łączyć z B-35, B-36 i materiałem wspierającym o postoju naprzemiennym.',
        ];
    }

    protected function limitedParkingZoneContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Wprowadza obszar, na którym postój jest ograniczony zasadami wskazanymi w organizacji ruchu.",
            'meaning' => 'To znak strefowy. Nie dotyczy tylko jednego punktu, ale całego obszaru od miejsca wjazdu do jego odwołania. Kierowca musi więc myśleć o zakazie lub ograniczeniu nie punktowo, lecz strefowo.',
            'placement' => 'Najczęściej pojawia się przy wjazdach do obszarów miejskich, osiedlowych lub centrów miejscowości, w których zarządca drogi chce uporządkować sposób postoju na większym fragmencie ulic.',
            'driver_behavior' => 'Po wjechaniu do strefy sprawdź, jakie dokładnie zasady postoju w niej obowiązują: czas, sposób ustawienia pojazdu, wyjątki albo obowiązki dodatkowe. Nie zakładaj, że brak kolejnego znaku oznacza pełną swobodę.',
            'legal_summary' => 'B-39 działa obszarowo. To jedna z najważniejszych rzeczy praktycznych: ograniczenie obowiązuje aż do jego odwołania i nie trzeba powtarzać znaku na każdej kolejnej ulicy wewnątrz strefy.',
            'fine_summary' => 'Naruszenie zasad postoju w strefie ograniczonego postoju może skończyć się mandatem, a w praktyce też blokadą lub odholowaniem pojazdu, jeśli organizacja ruchu lub regulamin strefy tego wymaga.',
            'common_mistakes' => 'Najczęstszy błąd to potraktowanie B-39 jak zwykłego znaku punktowego. Kierowcy często sądzą, że po skręcie w następną ulicę ograniczenie przestaje działać, choć strefa trwa nadal.',
            'faq_items' => [
                [
                    'question' => 'Czy B-39 działa tylko przy samym znaku?',
                    'answer' => 'Nie. To znak strefowy, więc obejmuje cały obszar od wjazdu do miejsca odwołania strefy.',
                ],
                [
                    'question' => 'Skąd mam wiedzieć, jakie zasady postoju obowiązują w strefie?',
                    'answer' => 'Trzeba czytać oznakowanie przy wjeździe do strefy i wszystkie dodatkowe tablice lub informacje, które doprecyzowują sposób postoju na jej terenie.',
                ],
            ],
            'editorial_notes' => 'Połączyć z B-40 i późniejszym materiałem o znakach strefowych vs znakach punktowych.',
        ];
    }

    protected function endOfLimitedParkingZoneContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Informuje o wyjeździe z obszaru objętego znakiem B-39.",
            'meaning' => 'Od tego miejsca przestają obowiązywać zasady ograniczonego postoju wynikające ze strefy. Nie oznacza to jednak automatycznie pełnej swobody parkowania, bo dalej mogą działać inne znaki lub zasady ogólne.',
            'placement' => 'Najczęściej stoi przy wyjazdach ze strefy ograniczonego postoju, gdzie kierowca powinien dostać czytelny sygnał, że kończy się właśnie obszarowy reżim parkowania.',
            'driver_behavior' => 'Po minięciu B-40 sprawdź, jakie zasady obowiązują dalej. To, że skończyła się strefa ograniczonego postoju, nie wyklucza istnienia innych znaków zakazu lub lokalnych zasad parkowania za jej granicą.',
            'legal_summary' => 'B-40 odwołuje zasady wynikające z B-39. Nie znosi innych ograniczeń, więc kierowca po wyjeździe ze strefy powinien przejść na analizę bieżącego oznakowania punktowego i przepisów ogólnych.',
            'fine_summary' => 'Sama obecność B-40 nie niesie ryzyka wykroczenia. Problem pojawia się wtedy, gdy kierowca uzna, że poza strefą wolno parkować w dowolnym miejscu bez sprawdzenia nowych ograniczeń.',
            'common_mistakes' => 'Najczęstszy błąd to utożsamienie końca strefy z pełną zgodą na dowolny postój. W praktyce po wyjeździe ze strefy trzeba jeszcze przeczytać oznakowanie obowiązujące już poza nią.',
            'faq_items' => [
                [
                    'question' => 'Co odwołuje znak B-40?',
                    'answer' => 'Odwołuje zasady ograniczonego postoju wynikające ze znaku B-39, czyli z całej wcześniejszej strefy.',
                ],
                [
                    'question' => 'Czy po B-40 znikają wszystkie ograniczenia parkowania?',
                    'answer' => 'Nie. Kończy się tylko konkretna strefa. Dalej mogą obowiązywać inne znaki zakazu lub przepisy ogólne dotyczące postoju.',
                ],
            ],
            'editorial_notes' => 'Spinać z B-39 i z przyszłymi treściami o końcu stref oraz o czytaniu znaków strefowych.',
        ];
    }

    protected function pedestrianBanContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Odcinek za znakiem nie jest przeznaczony dla ruchu pieszego.",
            'meaning' => 'To zakaz kierowany do pieszych, ale ważny także dla kierowcy, który powinien rozumieć organizację ruchu w takim miejscu. Znak wyznacza przestrzeń, w której ruch pieszych został wyłączony z przyczyn bezpieczeństwa albo organizacji drogi.',
            'placement' => 'Najczęściej pojawia się przy drogach szybkiego ruchu, odcinkach technicznych, łącznicach, tunelach i wszędzie tam, gdzie obecność pieszego byłaby szczególnie niebezpieczna.',
            'driver_behavior' => 'Jako kierowca nie zakładaj jednak, że pieszy nigdy się tam nie pojawi. Znak opisuje organizację ruchu, ale nie zwalnia z obserwacji otoczenia i reakcji w sytuacji niebezpiecznej.',
            'legal_summary' => 'B-41 porządkuje przestrzeń ruchu i wyłącza pieszych z odcinka drogi lub obiektu. Ma znaczenie także dla pozostałych uczestników, bo wyjaśnia, dlaczego dany odcinek nie przewiduje obecności pieszego.',
            'fine_summary' => 'Dla kierowcy znak sam w sobie nie rodzi typowego ryzyka mandatu tak jak zakazy wjazdu, ale ignorowanie jego znaczenia może prowadzić do błędnej oceny charakteru drogi i oczekiwanego ruchu wokół pojazdu.',
            'common_mistakes' => 'Najczęstszy błąd interpretacyjny to traktowanie B-41 jak znaku "droga tylko dla samochodów". W rzeczywistości chodzi o wyłączenie ruchu pieszego, a nie o opis wszystkich dopuszczonych grup uczestników.',
            'faq_items' => [
                [
                    'question' => 'Kogo dotyczy znak B-41?',
                    'answer' => 'Bezpośrednio dotyczy pieszych, bo zakazuje im poruszania się dalej za znakiem. Dla kierowcy jest to ważna informacja o organizacji ruchu na danym odcinku.',
                ],
                [
                    'question' => 'Czy kierowca może całkowicie przestać uważać na pieszych przy B-41?',
                    'answer' => 'Nie. Znak opisuje organizację ruchu, ale nie zwalnia z ostrożności i reagowania, jeśli pieszy znalazł się tam mimo zakazu.',
                ],
            ],
            'editorial_notes' => 'Warto później połączyć z materiałem o drogach szybkiego ruchu i o znakach opisujących dostępność drogi dla różnych uczestników.',
        ];
    }

    protected function endOfBansContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Informuje, że kończą się zakazy wyrażone wcześniej znakami B.",
            'meaning' => 'To znak odwołujący grupę wcześniejszych zakazów obowiązujących na danym odcinku. Nie oznacza jednak anulowania wszystkiego w promieniu drogi: kierowca nadal musi brać pod uwagę inne aktywne znaki, przepisy ogólne i organizację ruchu w nowym miejscu.',
            'placement' => 'Najczęściej pojawia się za odcinkiem, na którym obowiązywały jednocześnie różne zakazy, np. prędkości, wyprzedzania czy używania sygnałów, i gdzie zarządca drogi chce jednym znakiem je odwołać.',
            'driver_behavior' => 'Po minięciu B-42 oceń, jakie zasady obowiązują dalej. Nie zakładaj automatycznie, że każda wcześniejsza restrykcja zniknęła, jeśli w nowym miejscu pojawiły się kolejne znaki albo wciąż obowiązują przepisy ogólne.',
            'legal_summary' => 'B-42 odwołuje wcześniejsze zakazy z grupy B obowiązujące na poprzednim odcinku. W praktyce trzeba go czytać ostrożnie, bo nie uchyla przepisów ogólnych ani innych, niezależnych ograniczeń wprowadzonych nową organizacją ruchu.',
            'fine_summary' => 'Sam znak nie tworzy ryzyka wykroczenia. Problem zaczyna się wtedy, gdy kierowca potraktuje go jak ogólną zgodę na wszystko i przestanie czytać dalsze oznakowanie drogi.',
            'common_mistakes' => 'Najczęstszy błąd to przekonanie, że po B-42 znikają wszystkie możliwe ograniczenia. W rzeczywistości kończą się tylko zakazy objęte tym odcinkiem, a dalej mogą działać kolejne znaki lub przepisy ogólne.',
            'faq_items' => [
                [
                    'question' => 'Co dokładnie odwołuje znak B-42?',
                    'answer' => 'Odwołuje zakazy wyrażone wcześniej znakami z grupy B na danym odcinku drogi, ale nie usuwa innych ograniczeń, które wynikają z nowego oznakowania lub z przepisów ogólnych.',
                ],
                [
                    'question' => 'Czy po B-42 mogę zignorować kolejne znaki?',
                    'answer' => 'Nie. Po B-42 trzeba po prostu czytać drogę dalej od nowa i stosować się do aktualnego oznakowania oraz przepisów.',
                ],
            ],
            'editorial_notes' => 'Dobry materiał do późniejszego supporting page o odwoływaniu zakazów i o znakach kończących ograniczenia.',
        ];
    }

    protected function speedZoneContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Wprowadza obszar, na którym obowiązuje prędkość wskazana na znaku.",
            'meaning' => 'To znak strefowy, więc ograniczenie działa nie punktowo, ale na całym obszarze od miejsca wjazdu do jego odwołania. To jedna z najważniejszych różnic praktycznych względem zwykłego znaku B-33.',
            'placement' => 'Najczęściej występuje przy wjazdach do osiedli, stref uspokojonego ruchu, rejonów szkolnych i innych obszarów, w których zarządca drogi chce utrzymać obniżoną prędkość na większej siatce ulic.',
            'driver_behavior' => 'Po minięciu znaku jedź z prędkością nieprzekraczającą wartości ze znaku przez cały obszar strefy. Nie zakładaj, że po pierwszym skrzyżowaniu albo po skręcie w boczną ulicę ograniczenie znika.',
            'legal_summary' => 'B-43 działa obszarowo. Ograniczenie trwa na całym terenie strefy aż do znaku B-44 lub innej zmiany organizacji ruchu, więc kierowca musi myśleć o limicie jako o zasadzie obowiązującej na całym obszarze, a nie tylko przy jednym wlocie.',
            'fine_summary' => 'Przekroczenie prędkości w strefie ograniczonej prędkości to klasyczne źródło mandatu i punktów karnych, ale praktycznie chodzi też o bezpieczeństwo ruchu w miejscach, gdzie zarządca drogi świadomie uspokoił tempo jazdy na większym obszarze.',
            'common_mistakes' => 'Najczęstszy błąd to traktowanie B-43 jak zwykłego punktowego ograniczenia prędkości. Wielu kierowców po skręcie w następną ulicę uznaje, że limit przestaje działać, choć w strefie nadal obowiązuje.',
            'faq_items' => [
                [
                    'question' => 'Czym B-43 różni się od B-33?',
                    'answer' => 'B-33 wprowadza punktowy limit na odcinku, a B-43 tworzy strefę, czyli obszar, na którym dany limit obowiązuje aż do odwołania.',
                ],
                [
                    'question' => 'Czy po skręcie w boczną ulicę limit ze strefy nadal działa?',
                    'answer' => 'Tak, jeśli nadal znajdujesz się wewnątrz strefy ograniczonej prędkości i nie minąłeś znaku jej końca.',
                ],
            ],
            'editorial_notes' => 'Mocno powiązać z B-44 i z materiałem wspierającym o różnicy B-33 vs B-43.',
        ];
    }

    protected function endOfSpeedZoneContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}. Informuje o wyjeździe ze strefy ograniczonej prędkości.",
            'meaning' => 'Od tego miejsca przestaje obowiązywać obszarowy limit prędkości wprowadzony znakiem B-43. Nie znika jednak obowiązek jazdy zgodnie z przepisami ogólnymi, znakami punktowymi i realnymi warunkami na drodze.',
            'placement' => 'Najczęściej stoi na wyjazdach z osiedli, stref uspokojonego ruchu i obszarów, w których wcześniej obowiązywał strefowy limit prędkości.',
            'driver_behavior' => 'Po minięciu B-44 ustal, jaki limit obowiązuje dalej z przepisów ogólnych albo z kolejnych znaków. Nie przyspieszaj automatycznie tylko dlatego, że skończyła się strefa.',
            'legal_summary' => 'B-44 odwołuje obszarowe ograniczenie prędkości wprowadzone przez B-43. To ważne, bo kończy zasadę obowiązującą na całej strefie, ale nie znosi innych, niezależnych limitów wynikających z nowej organizacji ruchu.',
            'fine_summary' => 'Sama obecność znaku nie tworzy ryzyka wykroczenia. Problem pojawia się wtedy, gdy kierowca traktuje koniec strefy jak pełną swobodę i ignoruje dalsze ograniczenia lub warunki bezpieczeństwa.',
            'common_mistakes' => 'Najczęstszy błąd to utożsamienie końca strefy z nieograniczoną możliwością przyspieszenia. W praktyce po wyjeździe z obszaru uspokojonego ruchu trzeba od nowa czytać drogę i obowiązujące limity.',
            'faq_items' => [
                [
                    'question' => 'Co odwołuje znak B-44?',
                    'answer' => 'Odwołuje strefę ograniczonej prędkości wprowadzoną znakiem B-43, czyli obszarowy limit obowiązujący na wcześniejszej części drogi.',
                ],
                [
                    'question' => 'Czy po B-44 obowiązuje już tylko limit ogólny?',
                    'answer' => 'Najczęściej tak, ale tylko jeśli nie ma kolejnych znaków albo innych zasad organizacji ruchu, które wprowadzają nowy limit zaraz za końcem strefy.',
                ],
            ],
            'editorial_notes' => 'Spinać z B-43 i z supporting page o różnicach między końcem zwykłego ograniczenia i końcem strefy.',
        ];
    }

    protected function fallbackContent(array $sign): array
    {
        return [
            'intro_definition' => "Znak {$sign['code']} oznacza {$this->lcfirst($sign['name'])}.",
            'meaning' => 'To znak zakazu, który wymaga podporządkowania się organizacji ruchu w miejscu jego ustawienia.',
            'placement' => 'Najczęściej pojawia się tam, gdzie zarządca drogi musi wprowadzić czytelne ograniczenie ze względów bezpieczeństwa lub organizacji ruchu.',
            'driver_behavior' => 'Po zauważeniu znaku odczytaj jego zakres i dostosuj dalszą jazdę do obowiązującego zakazu.',
            'legal_summary' => 'Znak należy czytać razem z pozostałym oznakowaniem i przepisami ogólnymi.',
            'fine_summary' => 'Zignorowanie znaku zakazu może skutkować mandatem oraz wejściem w konflikt z organizacją ruchu.',
            'common_mistakes' => 'Najczęstszy błąd to potraktowanie znaku zbyt ogólnie i pominięcie jego dokładnego zakresu lub wyjątków.',
            'faq_items' => [
                [
                    'question' => "Co oznacza znak {$sign['code']}?",
                    'answer' => "Oznacza {$this->lcfirst($sign['name'])} i trzeba stosować się do tego ograniczenia zgodnie z organizacją ruchu.",
                ],
                [
                    'question' => 'Na co zwrócić uwagę przy tym znaku?',
                    'answer' => 'Na dokładny zakres zakazu, ewentualne wyjątki na tabliczkach oraz na to, jak znak łączy się z innymi elementami oznakowania.',
                ],
            ],
            'editorial_notes' => 'Wymaga dopracowania ręcznego przed publikacją.',
        ];
    }

    /**
     * @return array<string, array{placement: string, behavior: string, mistake: string, scope: string}>
     */
    protected function entryBanConfig(): array
    {
        return [
            'B-3' => [
                'scope' => 'pojazdy silnikowe objęte zakazem',
                'placement' => 'Najczęściej stoi na wlotach ulic lub odcinków, na które nie powinien wjeżdżać zwykły ruch pojazdów silnikowych, np. przy strefach rekreacyjnych, dojazdach technicznych albo lokalnych ograniczeniach ruchu.',
                'behavior' => 'Jeśli prowadzisz pojazd silnikowy objęty zakazem, nie wjeżdżaj za znak. Zatrzymaj się przed wlotem, sprawdź tabliczki z wyjątkami i wybierz legalną trasę objazdu.',
                'mistake' => 'Częsty błąd to uznanie, że znak dotyczy wszystkich pojazdów bez wyjątku albo przeciwnie: że nie obejmuje auta osobowego, bo droga wygląda na zwykłą ulicę lokalną.',
            ],
            'B-3a' => [
                'scope' => 'autobusy',
                'placement' => 'Najczęściej pojawia się na odcinkach, które nie są przystosowane do ruchu autobusów ze względu na geometrię drogi, szerokość, zabudowę lub lokalną organizację ruchu.',
                'behavior' => 'Jeśli prowadzisz autobus, potraktuj znak jako twardy zakaz wjazdu i szukaj wyznaczonego objazdu albo alternatywnej relacji dojazdu.',
                'mistake' => 'Najczęstszy błąd to założenie, że skoro autobus "fizycznie się zmieści", znak można zignorować. W praktyce ograniczenie bywa związane z promieniem skrętu, bezpieczeństwem lub ruchem lokalnym.',
            ],
            'B-4' => [
                'scope' => 'motocykle',
                'placement' => 'Najczęściej występuje tam, gdzie organizacja ruchu wyłącza ruch motocykli ze względów bezpieczeństwa, hałasu albo charakteru drogi.',
                'behavior' => 'Jeśli jedziesz motocyklem, nie wjeżdżaj za znak i szukaj innej trasy. Warto też sprawdzić, czy nie ma dodatkowej tabliczki dopuszczającej konkretny wyjątek.',
                'mistake' => 'Częsty błąd to utożsamianie B-4 z zakazem dla wszystkich pojazdów silnikowych albo zakładanie, że lekki motocykl czy skuter "i tak może przejechać".',
            ],
            'B-5' => [
                'scope' => 'samochody ciężarowe objęte zakazem',
                'placement' => 'Najczęściej stoi na wlotach ulic miejskich, osiedlowych albo historycznych, gdzie ruch ciężki został wyłączony ze względu na hałas, nośność, szerokość lub ochronę zabudowy.',
                'behavior' => 'Jeśli prowadzisz pojazd ciężarowy, zatrzymaj się przed wlotem, sprawdź tabliczki z wyjątkami i kieruj się wyznaczonym objazdem.',
                'mistake' => 'Najczęstszy błąd to pomijanie tabliczek z wyjątkami albo odwrotnie: próba samodzielnej interpretacji, że "mały dostawczak to jeszcze nie ciężarówka".',
            ],
            'B-6' => [
                'scope' => 'ciągniki rolnicze',
                'placement' => 'Najczęściej pojawia się na odcinkach, na których ruch ciągników rolniczych został wyłączony ze względu na prędkość, charakter drogi albo lokalną organizację ruchu.',
                'behavior' => 'Jeśli poruszasz się ciągnikiem rolniczym, nie wjeżdżaj dalej za znak i skorzystaj z dopuszczalnej trasy alternatywnej.',
                'mistake' => 'Częsty błąd to założenie, że zakaz nie dotyczy ciągnika jadącego "tylko kawałek" albo bez osprzętu. Znak nie zostawia takiej dowolności.',
            ],
            'B-7' => [
                'scope' => 'pojazdy silnikowe z przyczepą objęte zakazem',
                'placement' => 'Najczęściej stoi tam, gdzie zestaw z przyczepą miałby problem z przejezdnością, manewrowaniem albo bezpieczeństwem, np. na wąskich ulicach lub przy ostrych łukach.',
                'behavior' => 'Jeżeli ciągniesz przyczepę, potraktuj znak jako zakaz dla całego zestawu. Nie próbuj wjeżdżać z założeniem, że "jakoś się uda skręcić".',
                'mistake' => 'Najczęstszy błąd to mylenie zakazu dla pojazdów z przyczepą z zakazem tylko dla ciężarówek albo założenie, że lekka przyczepka nie ma znaczenia.',
            ],
            'B-8' => [
                'scope' => 'pojazdy zaprzęgowe',
                'placement' => 'Najczęściej występuje tam, gdzie charakter drogi albo intensywność ruchu nie przewidują bezpiecznego prowadzenia ruchu zaprzęgowego.',
                'behavior' => 'Jeśli poruszasz się pojazdem zaprzęgowym, nie wjeżdżaj za znak i korzystaj z trasy dopuszczonej dla takiego ruchu.',
                'mistake' => 'Częsty błąd interpretacyjny to traktowanie znaku jako historycznego reliktu i niedocenianie tego, że nadal może mieć pełne zastosowanie na lokalnych drogach.',
            ],
            'B-9' => [
                'scope' => 'rowery',
                'placement' => 'Najczęściej pojawia się tam, gdzie droga lub odcinek nie są przeznaczone dla ruchu rowerowego albo gdzie wyznaczono alternatywny przebieg ruchu rowerów.',
                'behavior' => 'Jeśli jedziesz rowerem, nie wjeżdżaj dalej za znak. Sprawdź, czy obok nie biegnie droga dla rowerów, pas rowerowy albo inna dopuszczona relacja.',
                'mistake' => 'Najczęstszy błąd to mylenie B-9 z zakazem dla wszystkich pojazdów albo zignorowanie go dlatego, że odcinek "wydaje się pusty".',
            ],
            'B-10' => [
                'scope' => 'motorowery',
                'placement' => 'Najczęściej stoi tam, gdzie ruch motorowerów nie powinien mieszać się z ruchem na danym odcinku ze względów bezpieczeństwa lub organizacji ruchu.',
                'behavior' => 'Jeśli jedziesz motorowerem, potraktuj znak dosłownie i wybierz trasę, na której ruch tego typu pojazdów jest dopuszczony.',
                'mistake' => 'Częsty błąd to utożsamianie motoroweru z rowerem albo motocyklem i przez to błędne ocenianie, czy zakaz dotyczy akurat tego pojazdu.',
            ],
            'B-11' => [
                'scope' => 'wozy ręczne',
                'placement' => 'Najczęściej pojawia się na odcinkach, na których ruch wozów ręcznych utrudniałby bezpieczeństwo pieszych albo kolidował z charakterem drogi i jej organizacją.',
                'behavior' => 'Jeśli prowadzisz wóz ręczny, nie wjeżdżaj dalej za znak i wybierz dopuszczoną trasę obejścia albo transportu.',
                'mistake' => 'Najczęstszy błąd to uznanie, że zakaz dotyczy tylko dużych wózków transportowych, a nie każdego wozu ręcznego objętego tym oznakowaniem.',
            ],
            'B-12' => [
                'scope' => 'wozy ręczne z towarem',
                'placement' => 'Najczęściej stoi tam, gdzie ruch załadowanych wozów ręcznych utrudniałby bezpieczeństwo pieszych albo kolidował z przeznaczeniem danego odcinka drogi.',
                'behavior' => 'Jeśli prowadzisz wóz ręczny z towarem, nie wchodź dalej na odcinek za znakiem i wybierz dopuszczoną trasę obejścia lub dostawy.',
                'mistake' => 'Częsty błąd to założenie, że skoro pusty wóz ręczny mógłby przejść, to zakaz nie dotyczy go po załadowaniu towarem. Tu właśnie ładunek zmienia zakres zakazu.',
            ],
        ];
    }

    /**
     * @return array<string, array{placement: string, behavior: string, mistake: string, scope: string}>
     */
    protected function dangerousCargoConfig(): array
    {
        return [
            'B-13' => [
                'scope' => 'pojazdów przewożących materiały wybuchowe lub łatwo zapalne',
                'placement' => 'Najczęściej występuje przed tunelami, obiektami inżynieryjnymi, zwartą zabudową i miejscami, gdzie ryzyko pożaru lub eksplozji wymaga skierowania takiego transportu inną trasą.',
                'behavior' => 'Jeżeli wykonujesz przewóz objęty zakazem, nie wjeżdżaj dalej i stosuj się do oznakowania objazdu. Tego znaku nie można "przeczekać" ani interpretować według własnej oceny ryzyka.',
                'mistake' => 'Najczęstszy błąd to pobieżne założenie, że zakaz dotyczy tylko dużych cystern. W praktyce decyduje charakter przewożonego materiału, a nie wyłącznie wygląd pojazdu.',
            ],
            'B-13a' => [
                'scope' => 'pojazdów przewożących materiały niebezpieczne',
                'placement' => 'Najczęściej stoi przed odcinkami szczególnie wrażliwymi dla bezpieczeństwa, np. tunelami, centrami miast lub obszarami o podwyższonym ryzyku skutków awarii transportu.',
                'behavior' => 'Jeżeli przewozisz materiał objęty zakazem, musisz skorzystać z dopuszczonego objazdu i czytać oznakowanie dodatkowe bardzo dokładnie.',
                'mistake' => 'Częsty błąd to utożsamianie znaku wyłącznie z paliwami lub gazem, choć kategoria materiałów niebezpiecznych może być szersza.',
            ],
            'B-14' => [
                'scope' => 'pojazdów przewożących materiały mogące skazić wodę',
                'placement' => 'Najczęściej pojawia się w pobliżu ujęć wody, mostów, obszarów ochronnych i odcinków, na których wyciek ładunku mógłby szybko doprowadzić do skażenia środowiska.',
                'behavior' => 'Jeżeli Twój przewóz podpada pod ten znak, nie wjeżdżaj dalej. Szukaj trasy alternatywnej przewidzianej dla tego rodzaju ładunku.',
                'mistake' => 'Najczęstszy błąd to niedocenianie znaku i założenie, że dotyczy tylko "dużych chemikaliów". W praktyce decyduje możliwość skażenia wody, a nie tylko skala przewozu.',
            ],
        ];
    }

    /**
     * @return array<string, array{placement: string, behavior: string, mistake: string, parameter: string}>
     */
    protected function dimensionLimitConfig(): array
    {
        return [
            'B-15' => [
                'parameter' => 'szerokość',
                'placement' => 'Najczęściej stoi przed wąskimi przejazdami, zwężeniami, bramami, wlotami do stref historycznych i innymi miejscami, gdzie gabaryt pojazdu ma bezpośrednie znaczenie dla przejezdności.',
                'behavior' => 'Jeżeli rzeczywista szerokość pojazdu lub zestawu przekracza wartość na znaku, nie wjeżdżaj dalej. Uwzględnij też lusterka, osprzęt i ładunek, jeśli wpływają na gabaryt.',
                'mistake' => 'Najczęstszy błąd to patrzenie wyłącznie na nadwozie i pomijanie dodatkowych elementów wystających poza jego obrys albo ocenianie szerokości "na oko".',
            ],
            'B-16' => [
                'parameter' => 'wysokość',
                'placement' => 'Najczęściej pojawia się przed wiaduktami, bramami, tunelami i wszędzie tam, gdzie prześwit nad jezdnią nie pozwala na przejazd wyższych pojazdów.',
                'behavior' => 'Jeśli wysokość pojazdu lub ładunku przekracza wartość na znaku, bezwzględnie zrezygnuj z wjazdu. Tu nie ma miejsca na eksperymenty ani "sprawdzanie, czy może się uda".',
                'mistake' => 'Najczęstszy błąd to nieuwzględnienie ładunku na dachu, plandeki, nadwozia specjalnego albo tego, że wysokość całego zestawu jest większa niż samego pojazdu bazowego.',
            ],
            'B-17' => [
                'parameter' => 'długość',
                'placement' => 'Najczęściej stoi przed odcinkami, na których zbyt długi pojazd miałby problem z przejazdem lub manewrowaniem, np. na ciasnych ulicach, zakrętach i w starych układach miejskich.',
                'behavior' => 'Jeżeli rzeczywista długość pojazdu lub zestawu przekracza wartość na znaku, nie wjeżdżaj dalej i szukaj trasy o odpowiednich parametrach.',
                'mistake' => 'Częsty błąd to nieuwzględnianie długości przyczepy albo założenie, że skoro sam ciągnik się mieści, to cały zestaw również.',
            ],
            'B-18' => [
                'parameter' => 'masa całkowita',
                'placement' => 'Najczęściej pojawia się na drogach o ograniczonej nośności, przed mostami, na słabszych konstrukcyjnie odcinkach oraz w miejscach, gdzie ruch cięższych pojazdów powinien zostać wyeliminowany.',
                'behavior' => 'Przed wjazdem musisz znać rzeczywistą masę całkowitą pojazdu lub zestawu w danym momencie. Jeśli przekracza wartość z znaku, zakaz obowiązuje bez wyjątku wynikającego z samego "krótkiego przejazdu".',
                'mistake' => 'Najczęstszy błąd to mylenie masy rzeczywistej z danymi "na pusto" albo utożsamianie znaku wyłącznie z dopuszczalną masą z dowodu rejestracyjnego.',
            ],
        ];
    }

    /**
     * @return array<string, array{placement: string, behavior: string, mistake: string, direction: string}>
     */
    protected function turnBanConfig(): array
    {
        return [
            'B-21' => [
                'direction' => 'w lewo',
                'placement' => 'Najczęściej stoi przed skrzyżowaniami, wlotami na drogi jednokierunkowe albo miejscami, w których skręt w lewo kolidowałby z układem pasów lub bezpieczeństwem ruchu.',
                'behavior' => 'Jeśli dojeżdżasz do miejsca objętego zakazem, wybierz inny kierunek jazdy albo zaplanuj zawczasu alternatywną relację. Nie ustawiaj się na pasie z myślą, że "może jednak uda się skręcić".',
                'mistake' => 'Najczęstszy błąd to mylenie zakazu skrętu w lewo z zakazem zawracania albo zignorowanie znaków poziomych, które wzmacniają ten zakaz.',
            ],
            'B-22' => [
                'direction' => 'w prawo',
                'placement' => 'Najczęściej pojawia się przed skrzyżowaniami, zjazdami i wlotami, gdzie skręt w prawo byłby sprzeczny z organizacją ruchu albo niebezpieczny dla innych uczestników.',
                'behavior' => 'Dojeżdżając do miejsca objętego znakiem, trzymaj się relacji dopuszczonej przez organizację ruchu i nie próbuj ratować trasy skrętem w ostatniej chwili.',
                'mistake' => 'Częsty błąd to założenie, że jeśli skręt w prawo wygląda na pusty i wygodny, znak można potraktować elastycznie. To klasyczny zakaz manewru, nie sugestia.',
            ],
        ];
    }

    /**
     * @return array<string, array{placement: string, behavior: string, mistake: string, scope: string}>
     */
    protected function overtakingBanConfig(): array
    {
        return [
            'B-25' => [
                'scope' => 'pojazdy objęte zakazem',
                'placement' => 'Najczęściej stoi na odcinkach o słabej widoczności, przy zakrętach, wzniesieniach, zwężeniach i wszędzie tam, gdzie manewr wyprzedzania zwiększałby ryzyko kolizji.',
                'behavior' => 'Po minięciu znaku zachowuj pozycję za poprzedzającym pojazdem i nie rozpoczynaj manewru wyprzedzania, nawet jeśli chwilowo droga wydaje się pusta.',
                'mistake' => 'Najczęstszy błąd to traktowanie znaku jako ograniczenia tylko przy dużym ruchu z przeciwka. W praktyce zakaz działa stale na całym odcinku.',
            ],
            'B-26' => [
                'scope' => 'samochody ciężarowe objęte zakazem',
                'placement' => 'Najczęściej pojawia się na drogach, na których wyprzedzanie przez pojazdy cięższe destabilizowałoby ruch albo wydłużało czas blokowania pasa ruchu.',
                'behavior' => 'Jeśli prowadzisz pojazd objęty zakazem, utrzymuj tor jazdy za wolniejszym pojazdem i nie rozpoczynaj manewru wyprzedzania do miejsca jego odwołania.',
                'mistake' => 'Częsty błąd to założenie, że zakaz dotyczy tylko pełnych zestawów ciężarowych, a nie np. określonych samochodów ciężarowych bez przyczepy.',
            ],
        ];
    }

    /**
     * @return array<string, array{scope: string, behavior: string, mistake: string}>
     */
    protected function endOfOvertakingBanConfig(): array
    {
        return [
            'B-27' => [
                'scope' => 'kierowcy objęci wcześniejszym zakazem',
                'behavior' => 'Po minięciu znaku nadal oceniaj widoczność, oznakowanie poziome i sytuację na drodze. Sam koniec zakazu nie oznacza jeszcze, że manewr jest natychmiast bezpieczny.',
                'mistake' => 'Najczęstszy błąd to rozpoczęcie wyprzedzania odruchowo zaraz po minięciu znaku, bez sprawdzenia linii na jezdni i warunków widoczności.',
            ],
            'B-28' => [
                'scope' => 'samochody ciężarowe objęte wcześniejszym zakazem',
                'behavior' => 'Jeśli prowadzisz pojazd ciężarowy, po minięciu znaku przejdź z powrotem na ogólne zasady wyprzedzania, ale nie ignoruj ograniczeń wynikających z drogi i jej geometrii.',
                'mistake' => 'Częsty błąd to założenie, że po znaku końca zakazu każda okazja do wyprzedzania jest od razu dopuszczalna także dla większego pojazdu.',
            ],
        ];
    }

    /**
     * @return array<string, array{placement: string, behavior: string, mistake: string, day_rule: string}>
     */
    protected function alternatingParkingConfig(): array
    {
        return [
            'B-37' => [
                'day_rule' => 'nieparzystych',
                'placement' => 'Najczęściej pojawia się na ulicach, na których zarządca drogi chce rotacyjnie zwalniać jedną stronę dla utrzymania przejezdności, prac porządkowych albo sezonowego odśnieżania.',
                'behavior' => 'Zanim zostawisz pojazd, sprawdź aktualną datę, godziny obowiązywania i to, czy zaraz nie zbliża się zmiana dnia, która przerzuci zakaz na tę stronę ulicy.',
                'mistake' => 'Najczęstszy błąd to zaparkowanie prawidłowo wieczorem i zapomnienie, że po północy zmieni się dzień, a razem z nim legalność postoju.',
            ],
            'B-38' => [
                'day_rule' => 'parzystych',
                'placement' => 'Najczęściej stoi na ulicach, na których postój naprzemienny ma utrzymywać przejezdność i porządek po jednej stronie jezdni w zależności od dnia miesiąca.',
                'behavior' => 'Przed postoju sprawdź dzień, godzinę i organizację ruchu na całej ulicy. Przy znaku naprzemiennym trzeba myśleć nie tylko o "tu i teraz", ale też o momencie zmiany dnia.',
                'mistake' => 'Częsty błąd to nieuwzględnienie, że zakaz związany z parzystością dnia może zacząć obowiązywać w nocy, kiedy kierowca nadal zostawia auto na tym samym miejscu.',
            ],
        ];
    }

    /**
     * @return array<string, array{placement: string, behavior: string, legal_summary: string, mistake: string, faq_question: string, faq_answer: string, editorial_notes: string}>
     */
    protected function checkpointStopConfig(): array
    {
        return [
            'B-32a' => [
                'placement' => 'Najczęściej pojawia się przy przejściach granicznych albo czasowych punktach kontroli, gdzie dalszy przejazd zależy od organizacji odprawy i poleceń służb.',
                'behavior' => 'Dojeżdżając do punktu kontroli, zwolnij odpowiednio wcześnie, zatrzymaj pojazd w wyznaczonym miejscu i obserwuj sygnały oraz polecenia funkcjonariuszy.',
                'legal_summary' => 'Znak wprowadza obowiązek zatrzymania przed kontrolą graniczną. Kierowca ma podporządkować się organizacji punktu, nawet jeśli akurat nie widzi kolejki albo stanowisko wygląda na chwilowo puste.',
                'mistake' => 'Najczęstszy błąd to potraktowanie pustego pasa lub otwartego szlabanu jako automatycznej zgody na przejazd bez zatrzymania.',
                'faq_question' => 'Czy jeśli nie widzę funkcjonariusza, mogę przejechać bez zatrzymania?',
                'faq_answer' => 'Nie. Najpierw trzeba zatrzymać się zgodnie z organizacją punktu i dopiero potem stosować się do sygnałów, urządzeń albo poleceń służb.',
                'editorial_notes' => 'Warto trzymać tę treść blisko B-32 i B-32e, bo użytkownik zwykle szuka całej rodziny znaków zatrzymania do kontroli.',
            ],
            'B-32b' => [
                'placement' => 'Najczęściej stosuje się go przed przejazdami lub wjazdami, na których standardowe zabezpieczenie rogatkowe nie działa prawidłowo i trzeba wymusić pełne zatrzymanie kierowcy.',
                'behavior' => 'Zatrzymaj pojazd, bardzo dokładnie oceń możliwość bezpiecznego przejazdu i ruszaj dopiero wtedy, gdy masz pewność, że tor lub przejazd są wolne oraz organizacja ruchu dopuszcza dalszy wjazd.',
                'legal_summary' => 'To znak awaryjny stosowany tam, gdzie zwykły sposób zabezpieczenia przejazdu nie daje pełnej ochrony. W praktyce kierowca musi odzyskać zapas bezpieczeństwa przez pełne zatrzymanie i własną ocenę sytuacji.',
                'mistake' => 'Najczęstszy błąd to założenie, że skoro rogatka jest podniesiona albo nie zamyka przejazdu, można przejechać bez dodatkowej ostrożności.',
                'faq_question' => 'Czy uszkodzona rogatka oznacza, że przejazd jest automatycznie otwarty?',
                'faq_answer' => 'Nie. Właśnie dlatego wprowadza się znak wymagający zatrzymania i samodzielnej, bardzo ostrożnej oceny warunków przejazdu.',
                'editorial_notes' => 'Przed dalszą rozbudową dobrze będzie spiąć ten znak z materiałami o przejazdach kolejowych i zachowaniu przy awarii urządzeń zabezpieczających.',
            ],
            'B-32c' => [
                'placement' => 'Najczęściej pojawia się tam, gdzie uszkodzona sygnalizacja nie może wiarygodnie prowadzić ruchu i trzeba zastąpić ją obowiązkiem zatrzymania oraz ręczną oceną sytuacji.',
                'behavior' => 'Zatrzymaj pojazd, rozejrzyj się bardzo dokładnie i ruszaj dalej dopiero wtedy, gdy masz pewność, że przejazd lub wjazd są bezpieczne mimo awarii sygnalizacji.',
                'legal_summary' => 'Jeżeli urządzenia sygnalizacyjne nie działają prawidłowo, znak przejmuje ich rolę w podstawowym zakresie: ma wymusić zatrzymanie i świadomą ocenę zagrożenia przed dalszą jazdą.',
                'mistake' => 'Częsty błąd to potraktowanie uszkodzonej sygnalizacji tylko jako niedogodności technicznej i przejazd bez zatrzymania, bo „nic się nie świeci”.',
                'faq_question' => 'Czy brak działającej sygnalizacji zwalnia z obowiązku zatrzymania?',
                'faq_answer' => 'Nie. Właśnie w takiej sytuacji znak wymaga pełnego zatrzymania i bardzo ostrożnej oceny warunków dalszej jazdy.',
                'editorial_notes' => 'Dobrze utrzymać prosty, praktyczny ton i nie mieszać tego znaku z ogólnymi zasadami pierwszeństwa poza miejscem objętym awarią.',
            ],
            'B-32d' => [
                'placement' => 'Najczęściej stosuje się go przed rampą promową i w strefie załadunku, gdzie ruch pojazdów musi być prowadzony etapami zgodnie z organizacją wejścia na prom.',
                'behavior' => 'Zatrzymaj pojazd w wyznaczonym miejscu, czekaj na sygnał obsługi i nie wjeżdżaj na rampę ani na pokład promu, dopóki organizator przejazdu nie dopuści ruchu.',
                'legal_summary' => 'Znak porządkuje ruch w miejscu, w którym margines błędu jest mały, a kolejność wjazdu ma znaczenie dla bezpieczeństwa załadunku. Kierowca ma obowiązek stosować się zarówno do znaku, jak i do poleceń obsługi promu.',
                'mistake' => 'Najczęstszy błąd to ruszanie za poprzednim pojazdem tylko dlatego, że kolejka się poruszyła, bez wyraźnego dopuszczenia własnego wjazdu.',
                'faq_question' => 'Czy opuszczona rampa oznacza, że mogę od razu wjechać na prom?',
                'faq_answer' => 'Nie. Najpierw trzeba zatrzymać się zgodnie ze znakiem i czekać na wyraźny sygnał lub polecenie obsługi.',
                'editorial_notes' => 'Ta strona dobrze zadziała w parze z treściami o nietypowych miejscach obowiązkowego zatrzymania i zachowaniu na rampie.',
            ],
            'B-32e' => [
                'placement' => 'Najczęściej pojawia się przy zorganizowanych punktach kontroli drogowej, gdzie pojazdy są kierowane do zatrzymania przez służby kontrolne albo urządzenia pomocnicze.',
                'behavior' => 'Zwoliń, przygotuj się do pełnego zatrzymania i obserwuj, na którym stanowisku masz się zatrzymać. Dalszą jazdę podejmuj dopiero po wyraźnym dopuszczeniu przejazdu.',
                'legal_summary' => 'Znak wprowadza obowiązek zatrzymania na potrzeby kontroli drogowej. W praktyce trzeba go czytać razem z organizacją punktu i poleceniami służb, bo to one przesądzają o dalszym przebiegu przejazdu.',
                'mistake' => 'Najczęstszy błąd to potraktowanie punktu kontroli jak zwykłego przewężenia i przejazd bez zatrzymania, jeśli akurat nie widać funkcjonariusza przy pasie.',
                'faq_question' => 'Czy jeśli pas wygląda na pusty, mogę ominąć zatrzymanie przy kontroli drogowej?',
                'faq_answer' => 'Nie. Najpierw trzeba zatrzymać się zgodnie z organizacją punktu kontroli, a dopiero potem stosować do dalszych sygnałów i poleceń.',
                'editorial_notes' => 'Warto utrzymać tę treść obok B-32 i B-32a, bo użytkownik szuka tu zwykle jednego wzorca: znak zatrzymania związany z kontrolą.',
            ],
        ];
    }

    /**
     * @param  array{slug: string}  $sign
     */
    protected function assetPath(array $sign): string
    {
        return 'traffic-signs/signs/prohibitions/znak-'.$sign['slug'].'.webp';
    }

    protected function lcfirst(string $value): string
    {
        return Str::of($value)->lcfirst()->toString();
    }
}
