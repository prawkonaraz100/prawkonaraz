<?php

namespace App\Support;

use Illuminate\Support\Str;

class PolishDirectionSignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $config = $this->directionConfig()[$sign['code']] ?? $this->fallbackConfig($sign);
        $assetPath = $this->assetPath($sign['slug']);
        $imageAlt = $sign['name'];

        return [
            'intro_definition' => "Znak {$sign['code']} {$sign['name']} przekazuje kierowcy kluczową informację o kierunku do wybranej miejscowości, drogi lub ważnego obiektu. Ułatwia nawigację i płynne przygotowanie do manewrów na skrzyżowaniach i węzłach.",
            'meaning' => $config['meaning'],
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'Znaki kierunku i miejscowości (grupa E) pełnią funkcję nawigacyjną i porządkową. Choć same z siebie nie wprowadzają zakazów, ich kolor i wielkość (np. zielone tło na drogach nieszybkiego ruchu, niebieskie na autostradach) informują też o kategorii drogi.',
            'fine_summary' => 'Błędy przy odczytywaniu znaków grupy E rzadko skutkują bezpośrednio mandatem za zignorowanie znaku, ale błędna nawigacja często prowadzi do gwałtownych manewrów na skrzyżowaniach (nagła zmiana pasa, ostre hamowanie), co stanowi duże zagrożenie i jest surowo karane.',
            'common_mistakes' => $config['mistake'],
            'editorial_notes' => $config['editorial_notes'],
            'review_notes' => 'Szkic przygotowany dla pierwszej implementacji znaków kierunku i miejscowości E. Należy uzupełnić docelowymi grafikami oraz rozbudować o kontekst tablic autostradowych i ekspresowych w przyszłości.',
            'source_notes' => 'Treść stworzona zgodnie ze strukturą oznakowania z grupy E, które w polskim systemie różnicuje kierunki i odległości.',
            'faq_items' => [
                [
                    'question' => "Do czego służy znak {$sign['code']} w praktyce?",
                    'answer' => $config['faq_duty'],
                ],
                [
                    'question' => "O czym warto pamiętać widząc znak {$sign['code']}?",
                    'answer' => $config['faq_mistake'],
                ],
            ],
            'meta_title' => "{$sign['code']} {$sign['name']} - jak czytać drogowskazy i tablice przeddrogowskazowe",
            'meta_description' => "Sprawdź, jak prawidłowo interpretować znak {$sign['code']} {$sign['name']}, jakie niesie informacje nawigacyjne i gdzie można go spotkać.",
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
    protected function directionConfig(): array
    {
        return [
            'E-1' => [
                'meaning' => 'Tablica przeddrogowskazowa uprzedza o skrzyżowaniu i wskazuje kierunki do konkretnych miejscowości. Pokazuje układ graficzny dróg przecinających się na najbliższym węźle.',
                'placement' => 'Umieszcza się ją w odpowiedniej odległości przed skrzyżowaniem, żeby dać kierowcy czas na zmianę pasa ruchu lub przygotowanie się do skrętu.',
                'behavior' => 'Odczytaj swój kierunek i upewnij się, że jedziesz pasem przypisanym do danej relacji na skrzyżowaniu.',
                'mistake' => 'Późne odczytanie tablicy skutkuje nerwowymi zmianami pasów tuż przed skrzyżowaniem lub jazdą niezgodnie ze wskazaniami strzałek kierunkowych na jezdni.',
                'faq_duty' => 'Pomaga zaplanować przejazd przez skrzyżowanie jeszcze zanim dojedziemy do sygnalizatorów i strzałek na jezdni.',
                'faq_mistake' => 'Upewnij się, że nie zmieniasz pasa na linii ciągłej, jeśli za późno zorientujesz się, w którym kierunku poprowadzi Cię nawigacja.',
                'editorial_notes' => 'Później warto omówić kolory tablic (zielone, niebieskie).',
            ],
            'E-2a' => [
                'meaning' => 'Drogowskaz tablicowy umieszczany obok jezdni wskazuje bezpośrednio przy skrzyżowaniu kierunek do danej miejscowości.',
                'placement' => 'Lokalizowany jest tuż przy samym punkcie, w którym należy wykonać manewr skrętu.',
                'behavior' => 'Potwierdź swój zjazd i wykonaj manewr bezpiecznie, nie wahając się na skrzyżowaniu.',
                'mistake' => 'Częstym błędem jest zatrzymywanie się na środku skrzyżowania by doczytać tekst na drogowskazie, gdy dojazd był zbyt szybki.',
                'faq_duty' => 'Potwierdza miejsce manewru skrętu zgodnego z wcześniej wskazanym kierunkiem.',
                'faq_mistake' => 'Ostrożność przed gwałtownym dohamowaniem, by trafić w odpowiedni zjazd wskazywany przez tablicę.',
                'editorial_notes' => 'Współgra z E-1 jako kontynuacja naprowadzania kierowcy.',
            ],
            'E-3' => [
                'meaning' => 'Drogowskaz strzałkowy podający numer drogi pozwala na orientację, czy podróżujemy właściwym szlakiem krajowym, wojewódzkim lub europejskim.',
                'placement' => 'Stawiany najczęściej na mniejszych węzłach, skrzyżowaniach bez tablic przeddrogowskazowych lub jako potwierdzenie kierunku po wyjeździe ze skrzyżowania.',
                'behavior' => 'Nawiguj po numerach dróg – ułatwia to orientację na trasach przelotowych bardziej niż same nazwy mniejszych miejscowości.',
                'mistake' => 'Ignorowanie numeracji dróg i poleganie wyłącznie na systemach GPS może prowadzić do zagubienia przy objazdach.',
                'faq_duty' => 'Informuje o kierunku do miejscowości bazowej wraz z numerem drogi, po której się poruszamy.',
                'faq_mistake' => 'Warto upewnić się, czy numer drogi jest krajowy czy wojewódzki (kolory tabliczek czerwone vs żółte).',
                'editorial_notes' => 'Przy okazji objaśnić różnice w numeracji dróg.',
            ],
            'E-4' => [
                'meaning' => 'Drogowskaz informujący o odległości w kilometrach do wskazywanej miejscowości ułatwia planowanie zasięgu paliwa i czasu przejazdu.',
                'placement' => 'Umieszczany najczęściej za większymi skrzyżowaniami oraz na wyjazdach z miast.',
                'behavior' => 'Odczytaj dystans i skalkuluj rezerwę paliwową oraz czasową – przydatne przy długich trasach poza autostradami.',
                'mistake' => 'Błędne szacowanie czasu przejazdu przez opieranie się tylko na odległościach, a nie na typie trasy.',
                'faq_duty' => 'Wskazuje dokładną odległość do centrum miejscowości wyrażoną w kilometrach.',
                'faq_mistake' => 'Odległość dotyczy centrum administracyjnego (często dworca lub rynku), a nie tablicy oznaczającej teren zabudowany miejscowosci.',
                'editorial_notes' => 'Ciekawostka o tym skąd liczone są odległości w PL.',
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
            'meaning' => "Znak {$sign['code']} opisany jako {$lowerName} jest drogowskazem wskazującym kierunek do ważnego obiektu, regionu lub innej trasy.",
            'placement' => 'Umieszczany przed lub na skrzyżowaniach w celu ułatwienia nawigacji.',
            'behavior' => 'Odpowiednio wcześnie zajmij pas właściwy do wykonania wskazywanego manewru.',
            'mistake' => 'Nerwowe zmiany pasa tuż przed skrzyżowaniem po późnym dostrzeżeniu informacji kierunkowej na znaku.',
            'faq_duty' => 'Nawiguje i wspiera płynność ruchu bez konieczności korzystania wyłącznie z GPS.',
            'faq_mistake' => 'Gwałtowne zatrzymywanie w celu odczytania treści tablic kierunkowych.',
            'editorial_notes' => 'W razie potrzeby zaktualizować konkretnym kontekstem znaku kierunkowego.',
        ];
    }

    protected function assetPath(string $slug): string
    {
        return 'traffic-signs/signs/directions/znak-'.$slug.'.webp';
    }
}
