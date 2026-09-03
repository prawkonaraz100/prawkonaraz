<?php

namespace App\Support;

use Illuminate\Support\Str;

class PolishRailwaySignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $config = $this->railwayConfig()[$sign['code']] ?? $this->fallbackConfig($sign);
        $assetPath = $this->assetPath($sign['slug']);
        $imageAlt = $sign['name'];

        return [
            'intro_definition' => "Znak {$sign['code']} to dodatkowy znak umieszczany przed przejazdami kolejowymi (grupa G). Jego obecność jest sygnałem dla kierowcy, że zbliża się do miejsca o wyjątkowo dużym stopniu zagrożenia – skrzyżowania drogi z torowiskiem.",
            'meaning' => $config['meaning'],
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'Dodatkowe znaki przed przejazdami kolejowymi z grupy G stanowią bezwzględne uzupełnienie znaków ostrzegawczych A-9 i A-10. Wskazują dokładną pozycję lub dystans do niebezpieczeństwa, a ignorowanie ich nakazów jest jednym z najcięższych wykroczeń drogowych.',
            'fine_summary' => 'Zignorowanie wskazań znaków G, w szczególności Krzyża św. Andrzeja bez zatrzymania się, gdy zachodzi taka konieczność, grozi najwyższymi mandatami, punktami karnymi, a nawet w określonych sytuacjach utratą prawa jazdy ze względu na stwarzanie katastrofalnego zagrożenia.',
            'common_mistakes' => $config['mistake'],
            'editorial_notes' => $config['editorial_notes'],
            'review_notes' => 'Szkic przygotowany dla implementacji znaków grupy G (Przejazdy kolejowe). Gotowy pod dodanie docelowych grafik.',
            'source_notes' => 'Baza wiedzy oparta na rozporządzeniu w sprawie znaków i sygnałów drogowych oraz zasadach bezpiecznego przekraczania przejazdów kolejowych.',
            'faq_items' => [
                [
                    'question' => "O czym informuje znak {$sign['code']}?",
                    'answer' => $config['faq_duty'],
                ],
                [
                    'question' => "Na co zwrócić uwagę widząc znak {$sign['code']}?",
                    'answer' => $config['faq_mistake'],
                ],
            ],
            'meta_title' => "Znak {$sign['code']} przed przejazdem kolejowym - znaczenie i zachowanie",
            'meta_description' => "Sprawdź, co dokładnie oznacza znak {$sign['code']}. Dowiedz się, jak prawidłowo dojechać do przejazdu kolejowego i w którym miejscu musisz się zatrzymać.",
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
    protected function railwayConfig(): array
    {
        return [
            'G-1a' => [
                'meaning' => 'Słupek wskaźnikowy z trzema kreskami znajduje się najdalej od przejazdu. Oznacza pierwszą fazę zbliżania się do strefy niebezpiecznej (zwykle umieszczany pod znakiem A-9 lub A-10).',
                'placement' => 'Umieszczany po prawej stronie drogi, od 150 do 300 metrów przed przejazdem kolejowym, zależnie od dozwolonej prędkości na drodze.',
                'behavior' => 'Należy zdjąć nogę z gazu, wyłączyć radio, uciszyć pasażerów i rozpocząć wzmożoną obserwację przedpola pod kątem torowiska.',
                'mistake' => 'Częstym błędem jest zignorowanie słupka z trzema kreskami i rozpoczęcie redukcji prędkości dopiero tuż przed samymi torami.',
                'faq_duty' => 'Sygnalizuje kierowcy, że wjeżdża w strefę dojazdu do przejazdu kolejowego.',
                'faq_mistake' => 'Brak obniżenia prędkości pomimo wejścia w strefę wskaźników.',
                'editorial_notes' => 'Od tego znaku zaczyna się odliczanie. Skupić się na budowaniu napięcia i skupienia.',
            ],
            'G-1b' => [
                'meaning' => 'Słupek z dwiema kreskami to środkowy element odliczania dystansu. Jesteś w połowie drogi między pierwszym znakiem ostrzegawczym a torami.',
                'placement' => 'Umieszczany w odległości 2/3 od torów w stosunku do słupka z trzema kreskami.',
                'behavior' => 'Prędkość powinna być już znacząco zredukowana. Szukaj wzrokiem sygnalizatorów świetlnych lub rogatek.',
                'mistake' => 'Rozpoczęcie hamowania z dużej prędkości w okolicy słupka z dwiema kreskami może być już niebezpieczne na śliskiej nawierzchni.',
                'faq_duty' => 'Potwierdza zmniejszający się dystans do torowiska.',
                'faq_mistake' => 'Rozkojarzenie i przeoczenie faktu, że to już drugi z trzech słupków.',
                'editorial_notes' => 'Często pomijany w edukacji - warto to podkreślić.',
            ],
            'G-1c' => [
                'meaning' => 'Słupek z jedną kreską umieszczony jest najbliżej torów. Stanowi "ostatnie ostrzeżenie" przed fizycznym wjazdem na przejazd.',
                'placement' => 'Umieszczany w 1/3 odległości w stosunku do słupka z trzema kreskami, zazwyczaj bardzo blisko samego przejazdu.',
                'behavior' => 'Pełne skupienie. Należy zredukować bieg do tego, na którym będziemy pokonywać tory, upewnić się o braku pociągu (lewo, prawo).',
                'mistake' => 'Przyspieszanie przed słupkiem z jedną kreską, żeby "zdążyć" przed opadającymi zaporami.',
                'faq_duty' => 'Wskazuje bezpośrednią bliskość skrzyżowania z torami.',
                'faq_mistake' => 'Zbyt szybki dojazd do przejazdu, wymuszający gwałtowne hamowanie awaryjne.',
                'editorial_notes' => 'Wiąże się bezpośrednio z linią bezwzględnego zatrzymania.',
            ],
            'G-1d' => [
                'meaning' => 'Słupek wskaźnikowy z trzema kreskami po lewej stronie jezdni powtarza pierwszą informację o zbliżaniu się do przejazdu kolejowego. Pomaga utrzymać czytelność oznakowania tam, gdzie kierowca powinien widzieć wskaźniki po obu stronach dojazdu.',
                'placement' => 'Umieszczany po lewej stronie drogi, zwykle na wysokości słupka G-1a, czyli najdalej od przejazdu w sekwencji trzech wskaźników.',
                'behavior' => 'Traktuj go jako początek strefy podwyższonej ostrożności: zwolnij, przygotuj obserwację torów i szukaj kolejnych słupków oraz urządzeń zabezpieczających przejazd.',
                'mistake' => 'Błędem jest uznanie lewego słupka za mniej ważny tylko dlatego, że główna obserwacja prowadzona jest po prawej stronie jezdni.',
                'faq_duty' => 'Potwierdza rozpoczęcie odliczania odległości do przejazdu kolejowego również po lewej stronie drogi.',
                'faq_mistake' => 'Ignorowanie powtórzonego oznakowania i utrzymywanie zbyt dużej prędkości na dojeździe do torów.',
                'editorial_notes' => 'Podkreślić, że wariant lewostronny uzupełnia, a nie zastępuje, czytanie znaków po prawej stronie.',
            ],
            'G-1e' => [
                'meaning' => 'Słupek wskaźnikowy z dwiema kreskami po lewej stronie jezdni wskazuje środkowy etap zbliżania się do przejazdu kolejowego.',
                'placement' => 'Umieszczany po lewej stronie drogi na odcinku pomiędzy słupkiem z trzema kreskami a słupkiem z jedną kreską.',
                'behavior' => 'Na tym etapie prędkość powinna być już wyraźnie obniżona. Kierowca powinien kontrolować sygnalizację, rogatki i możliwość bezpiecznego opuszczenia przejazdu.',
                'mistake' => 'Częsty błąd to dopiero rozpoczynanie intensywnego hamowania przy drugim słupku, zamiast wcześniej przygotować spokojny dojazd.',
                'faq_duty' => 'Przypomina, że dystans do torów jest już krótki i trzeba utrzymywać pełną koncentrację.',
                'faq_mistake' => 'Przeoczenie drugiego słupka i zbyt późne ustawienie prędkości do przejazdu przez torowisko.',
                'editorial_notes' => 'W tekście jasno odróżnić funkcję G-1e od G-1b: lewa strona jezdni, ten sam etap odliczania.',
            ],
            'G-1f' => [
                'meaning' => 'Słupek wskaźnikowy z jedną kreską po lewej stronie jezdni jest ostatnim elementem odliczania przed przejazdem kolejowym.',
                'placement' => 'Umieszczany po lewej stronie drogi najbliżej przejazdu, na wysokości odpowiadającej słupkowi G-1c.',
                'behavior' => 'Przed tym punktem kierowca powinien mieć już dobraną prędkość, bieg i pełną obserwację torów oraz sygnałów. Nie wolno przyspieszać, żeby zdążyć przed zamykającym się przejazdem.',
                'mistake' => 'Najgroźniejszy błąd to potraktowanie ostatniego słupka jako sygnału do szybkiego przejazdu zamiast do ostatecznej kontroli sytuacji.',
                'faq_duty' => 'Wskazuje bezpośrednią bliskość torów także po lewej stronie jezdni.',
                'faq_mistake' => 'Wjazd na przejazd bez upewnienia się, czy nie nadjeżdża pociąg i czy za przejazdem jest miejsce.',
                'editorial_notes' => 'Powiązać z G-1c oraz praktycznym zachowaniem tuż przed torami.',
            ],
            'G-3' => [
                'meaning' => 'Krzyż św. Andrzeja wyznacza precyzyjne miejsce, w którym należy się zatrzymać w związku z ruchem pociągu (oraz znakiem STOP, jeśli występuje). Ten wariant informuje, że przejazd obejmuje tylko jeden tor.',
                'placement' => 'Ustawiany bezpośrednio przed torowiskiem, często z sygnalizatorem świetlnym i dźwiękowym.',
                'behavior' => 'Jeśli masz obowiązek się zatrzymać (czerwone światło, pociąg w zasięgu wzroku, lub znak B-20), zatrzymaj się przed linią krzyża. Ponieważ to jeden tor – po przejechaniu pociągu nie musisz obawiać się drugiego zza niego.',
                'mistake' => 'Zatrzymywanie auta zbyt blisko torów (za krzyżem) grożące "zahaczeniem" przez skrajnię pociągu.',
                'faq_duty' => 'Wyznacza linię zatrzymania i informuje o obecności jednego toru.',
                'faq_mistake' => 'Wjeżdżanie maską samochodu za oś krzyża.',
                'editorial_notes' => 'Podstawa dla kursantów. Często łączony ze znakiem B-20 STOP.',
            ],
            'G-4' => [
                'meaning' => 'Krzyż św. Andrzeja wyznaczający miejsce zatrzymania, informujący jednocześnie, że przejazd składa się z dwóch lub większej ilości torów.',
                'placement' => 'Bezpośrednio przed przejazdami wielotorowymi.',
                'behavior' => 'Ekstremalna ostrożność. Po przejechaniu pociągu z jednego kierunku absolutnie musisz poczekać i upewnić się, czy po drugim torze nie nadjeżdża drugi pociąg, często z przeciwnego kierunku i zasłonięty przez pierwszy.',
                'mistake' => 'Najbardziej tragiczny błąd to ruszanie tuż po przejechaniu pociągu towarowego, zza którego nagle wyłania się pociąg na drugim torze.',
                'faq_duty' => 'Wskazuje punkt zatrzymania i kluczową informację o wielu torach.',
                'faq_mistake' => 'Ruszanie bez upewnienia się, że pozostałe tory również są wolne.',
                'editorial_notes' => 'Ostrzeżenie o ukrytym pociągu z przeciwka to najważniejsza nauka dla tego znaku.',
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
            'meaning' => "Znak {$sign['code']}, opisywany jako {$lowerName}, dostarcza kierowcy szczegółowych informacji w obrębie przejazdu kolejowego.",
            'placement' => 'Znajduje się w bezpośrednim sąsiedztwie torowiska lub na dojeździe do niego.',
            'behavior' => 'Należy bezwzględnie zachować szczególną ostrożność i reagować zgodnie z zasadami zachowania na przejazdach kolejowych.',
            'mistake' => 'Najczęstszym problemem jest rutyna i ignorowanie oznakowania w przypadku znajomych, rzadko uczęszczanych tras kolejowych.',
            'faq_duty' => 'Służy podniesieniu poziomu bezpieczeństwa w strefie najwyższego ryzyka na drodze.',
            'faq_mistake' => 'Lekceważenie wskazań dodatkowych znaków pomimo braku widocznego pociągu.',
            'editorial_notes' => 'Doprecyzować przy kolejnym przeglądzie bazy tekstów pod przejazdy kolejowe.',
        ];
    }

    protected function assetPath(string $slug): string
    {
        return 'traffic-signs/signs/railway/znak-'.$slug.'.webp';
    }
}
