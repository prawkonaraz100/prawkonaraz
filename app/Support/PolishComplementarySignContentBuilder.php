<?php

namespace App\Support;

use Illuminate\Support\Str;

class PolishComplementarySignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $config = $this->complementaryConfig()[$sign['code']] ?? $this->fallbackConfig($sign);
        $assetPath = $this->assetPath($sign['slug']);
        $imageAlt = $sign['name'];

        return [
            'intro_definition' => "Znak {$sign['code']} {$sign['name']} należy do grupy znaków uzupełniających. Jego głównym zadaniem jest doprecyzowanie organizacji ruchu, informowanie o specyficznych warunkach (np. przekraczaniu granicy, zbliżającym się zakazie) lub kierunkach jazdy dla poszczególnych pasów.",
            'meaning' => $config['meaning'],
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'Znaki uzupełniające (grupa F) dostarczają kluczowych informacji porządkowych. Często zapowiadają inne znaki drogowe (np. zakazy) dla wyznaczonych pojazdów lub precyzują skomplikowaną organizację ruchu na wielopasmowych drogach i skrzyżowaniach.',
            'fine_summary' => 'Zignorowanie wskazań znaków z grupy F prowadzi zwykle do naruszenia innych, twardszych przepisów, takich jak jazda niezgodnie z kierunkiem przypisanym do danego pasa ruchu. To z kolei skutkuje mandatem i punktami karnymi za nieprawidłowe zachowanie na skrzyżowaniu.',
            'common_mistakes' => $config['mistake'],
            'editorial_notes' => $config['editorial_notes'],
            'review_notes' => 'Szkic przygotowany dla pierwszej implementacji znaków uzupełniających F. Należy uzupełnić docelowymi grafikami.',
            'source_notes' => 'Treść stworzona zgodnie z wykazem znaków z grupy F i praktyką ich występowania w polskim systemie drogowym.',
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
            'meta_title' => "{$sign['code']} {$sign['name']} - znaczenie, organizacja ruchu i zasady",
            'meta_description' => "Sprawdź, co oznacza znak uzupełniający {$sign['code']} {$sign['name']}. Dowiedz się, gdzie występuje i jak odpowiednio zinterpretować jego wskazania na drodze.",
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
    protected function complementaryConfig(): array
    {
        return [
            'F-1' => [
                'meaning' => 'Znak informuje kierowcę o zbliżaniu się do przejścia granicznego.',
                'placement' => 'Umieszczany przed samą granicą państwa w rejonach z wciąż funkcjonującymi punktami kontrolnymi lub w historycznych punktach przekraczania granicy.',
                'behavior' => 'Należy zwolnić i dostosować się do obowiązującej na przejściu organizacji ruchu (np. ograniczenia prędkości, strefy kontroli).',
                'mistake' => 'Częstym błędem jest ignorowanie dodatkowych znaków ograniczenia prędkości występujących w strefie granicznej.',
                'faq_duty' => 'Uprzedza o przekraczaniu strefy przygranicznej, w której mogą obowiązywać szczególne zasady ruchu.',
                'faq_mistake' => 'Należy zawsze zwracać uwagę na polecenia służb (Straż Graniczna, Policja) w rejonie obowiązywania tego znaku.',
                'editorial_notes' => 'W przyszłości warto objaśnić kontekst w ramach strefy Schengen.',
            ],
            'F-5' => [
                'meaning' => 'Znak uprzedza o zbliżającym się znaku zakazu (np. zakazu wjazdu pojazdów ciężarowych) za skrzyżowaniem lub na najbliższym odcinku.',
                'placement' => 'Umieszcza się go zazwyczaj przed skrzyżowaniem w sposób pozwalający na wybór innej drogi przed napotkaniem zakazu.',
                'behavior' => 'Odczytaj czy zakaz dotyczy twojego pojazdu. Jeśli tak – przygotuj się do zmiany planowanej trasy, by nie wjechać za zakaz docelowy.',
                'mistake' => 'Późne reagowanie na znak i konieczność kłopotliwego zawracania pojazdem wielkogabarytowym tuż przed wlotem objętym zakazem.',
                'faq_duty' => 'Informuje wcześnie, że za chwilę dany pojazd (np. powyżej określonego tonażu) napotka zakaz.',
                'faq_mistake' => 'Znak sam w sobie jeszcze nie zabrania wjazdu – to jedynie ostrzeżenie, dające czas na znalezienie objazdu.',
                'editorial_notes' => 'Naturalne nawiązanie do znaków zakazu B-18 i B-19.',
            ],
            'F-10' => [
                'meaning' => 'Wskazuje dokładnie, w jakich kierunkach wolno kontynuować jazdę z poszczególnych pasów ruchu na wielopasmowym skrzyżowaniu.',
                'placement' => 'Wiesza się go nad jezdnią lub stawia z boku drogi przed skrzyżowaniem, wystarczająco wcześnie by umożliwić bezkolizyjną zmianę pasa.',
                'behavior' => 'Szybko ustal, który pas prowadzi do Twojego celu i ustaw się na nim bez gwałtownego zajeżdżania drogi innym samochodom.',
                'mistake' => 'Najpoważniejszym błędem jest ignorowanie przypisanych kierunków i np. jazda na wprost z pasa, na którym tablica oraz strzałki na jezdni wymuszają skręt w lewo.',
                'faq_duty' => 'Organizuje ruch i pomaga płynnie pokonać skomplikowane skrzyżowanie rozdzielając potoki ruchu.',
                'faq_mistake' => 'Nigdy nie przekraczaj linii ciągłej w pobliżu sygnalizatorów, nawet jeśli pomyliłeś pas. Zmień kierunek i zawróć w bezpiecznym miejscu.',
                'editorial_notes' => 'Częste przyczyny oblania egzaminu państwowego na prawo jazdy – do szerokiego opisania.',
            ],
            'F-11' => [
                'meaning' => 'Jest to wersja znaku zawieszana często nad pojedynczym, konkretnym pasem ruchu, by jednoznacznie wskazać, jaki kierunek jazdy mu przypisano.',
                'placement' => 'Rozwieszony najczęściej na bramownicach tuż przed dużymi węzłami miejskimi.',
                'behavior' => 'Obserwuj znaki nad swoim pasem, by płynnie połączyć je z nawigacją i strzałkami wymalowanymi na asfalcie.',
                'mistake' => 'Jazda "na pamięć" na nowo zmodernizowanych odcinkach dróg.',
                'faq_duty' => 'Przypisuje pas do kierunku jazdy (w lewo, w prawo lub prosto).',
                'faq_mistake' => 'Skupianie uwagi tylko na asfalcie i nie zauważenie znaków wiszących na górze, co prowadzi do błędów na zasypanej śniegiem jezdni.',
                'editorial_notes' => 'Praktyczne porównanie z F-10.',
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
            'meaning' => "Znak {$sign['code']} opisany jako {$lowerName} przekazuje kluczowe informacje uzupełniające na temat specyficznej organizacji ruchu na danym odcinku drogi.",
            'placement' => 'Umieszczany w miejscach, gdzie kierowca potrzebuje dodatkowego, obszernego wyjaśnienia sytuacji drogowej wykraczającej poza standardowe ramy pojedynczego zakazu.',
            'behavior' => 'Dokładnie zapoznaj się z treścią znaku, ustalając do kogo jest adresowany (np. tonaż) i jak modyfikuje on standardowe zasady ruchu.',
            'mistake' => 'Ignorowanie tego typu znaków, ponieważ „wydają się nieistotne”, skutkuje później błędnymi decyzjami na węzłach drogowych.',
            'faq_duty' => 'Porządkuje i wspiera bezpieczne pokonywanie wielopasmowych dróg i przejazdów granicznych.',
            'faq_mistake' => 'Czytanie tablic jedynie pobieżnie.',
            'editorial_notes' => 'Uzupełnić o kontekst drogowy na późniejszym etapie.',
        ];
    }

    protected function assetPath(string $slug): string
    {
        return 'traffic-signs/signs/complementary/znak-'.$slug.'.webp';
    }
}
