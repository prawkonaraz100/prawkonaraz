<?php

namespace App\Support;

class PolishTramSignContentBuilder
{
    /**
     * @param array<string, mixed> $signData
     * @return array<string, mixed>
     */
    public function build(array $signData, int $index): array
    {
        $code = $signData['code'];
        $name = $signData['name'];

        $introDefinition = $this->getIntroDefinition($code);
        $meaning = $this->getMeaning($code);
        $placement = 'Znaki dla kierujących tramwajami są najczęściej umieszczane bezpośrednio nad torowiskiem (np. na zawieszeniu sieci trakcyjnej) lub na słupach trakcyjnych w bezpośrednim sąsiedztwie torów.';
        $driverBehavior = 'Kierujący pojazdem cywilnym (samochód osobowy, ciężarowy, motocykl) musi całkowicie zignorować ten znak. Nie jest on skierowany do kierowców pojazdów kołowych i nie wpływa na ich zasady poruszania się.';
        $legalSummary = 'Zgodnie z przepisami, znaki grupy AT i BT dotyczą wyłącznie kierujących tramwajami. Nie mają one żadnej mocy wiążącej dla pojazdów poruszających się po jezdni (nawet jeśli jezdnia pokrywa się z torowiskiem).';
        
        $legalReferenceLabel = 'Rozporządzenie ws. znaków i sygnałów drogowych - Znaki tramwajowe';
        $legalReferenceUrl = 'https://isap.sejm.gov.pl';
        
        $fineSummary = 'Kierowca cywilny nie otrzyma mandatu za zignorowanie znaku tramwajowego. Przykładowo: zignorowanie tramwajowego ograniczenia prędkości (BT-1) przez kierowcę auta jadącego po torowisku wbudowanym w jezdnię nie grozi mandatem z fotoradaru.';
        $commonMistakes = 'Błędne odczytywanie kwadratowych tabliczek ograniczenia prędkości tramwaju (BT-1) przez kursantów prawa jazdy na kategorię B jako ograniczeń dla samochodów jadących po torowisku wbudowanym w jezdnię.';
        $editorialNotes = 'Warto w pytaniach testowych podkreślać, że to "wewnętrzne" oznakowanie przedsiębiorstw komunikacyjnych usankcjonowane państwowym rozporządzeniem, którego zadaniem jest ochrona sieci trakcyjnej i taboru, a nie regulacja ruchu aut.';
        
        $faqItems = $this->getFaqItems($code);
        
        $metaTitle = "Znak tramwajowy {$code} - znaczenie";
        $metaDescription = "Sprawdź, co oznacza znak tramwajowy {$code} ({$name}). Dowiedz się, czy znaki wieszane nad torowiskiem obowiązują kierowców samochodów osobowych.";

        $imagePath = 'traffic-signs/signs/tram-signs/znak-'.strtolower($code).'.webp';
        $ogImagePath = 'traffic-signs/signs/tram-signs/znak-'.strtolower($code).'-og.webp';

        return [
            'intro_definition' => $introDefinition,
            'meaning' => $meaning,
            'placement' => $placement,
            'driver_behavior' => $driverBehavior,
            'legal_summary' => $legalSummary,
            'legal_reference_label' => $legalReferenceLabel,
            'legal_reference_url' => $legalReferenceUrl,
            'fine_summary' => $fineSummary,
            'common_mistakes' => $commonMistakes,
            'editorial_notes' => $editorialNotes,
            'review_notes' => 'Wygenerowane automatycznie dla grupy Znaków Tramwajowych.',
            'source_notes' => 'Baza pytań WORD, odróżnianie znaków tramwajowych od drogowych.',
            'faq_items' => $faqItems,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'image_path' => $imagePath,
            'image_alt' => "Znak tramwajowy {$code}",
            'image_width' => 800,
            'image_height' => 800,
            'og_image_path' => $ogImagePath,
            'og_image_alt' => "Znak tramwajowy {$code} na sieci trakcyjnej",
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'sort_order' => $index * 10,
        ];
    }

    private function getIntroDefinition(string $code): string
    {
        return match ($code) {
            'AT-1' => 'Znak ostrzegawczy AT-1 ma kształt rombu. Ostrzega motorniczego o zbliżaniu się do skrzyżowania lub miejsca, gdzie ruch jest kierowany za pomocą sygnalizacji świetlnej.',
            'AT-2' => 'Znak ostrzegawczy AT-2 (romb) informuje o zbliżaniu się do sygnalizacji świetlnej, która uruchamia się (wzbudza) samoczynnie poprzez detekcję nadjeżdżającego tramwaju.',
            'AT-3' => 'Znak AT-3 ostrzega motorniczego o znacznym spadku wzdłużnym torowiska, na którym trzeba zachować szczególną ostrożność przy hamowaniu tak potężnej masy.',
            'AT-4' => 'Znak AT-4 informuje kierującego tramwajem o zbliżaniu się do odcinka torowiska ze znacznym wzniesieniem, co może wymagać płynnej jazdy bez zatrzymywania, by nie utracić pędu.',
            'AT-5' => 'Znak AT-5 (ruch kolizyjny) ostrzega o miejscu, gdzie torowisko przecina się z innym torem lub torem ruchu dla innych pojazdów o zwiększonym ryzyku kolizji.',
            'BT-1' => 'Znak zakazu BT-1 to biały kwadrat z czarną ramką i liczbą wewnątrz, określający maksymalną dopuszczalną prędkość z jaką może poruszać się tramwaj.',
            default => 'Znak pionowy dla kierujących tramwajami.',
        };
    }

    private function getMeaning(string $code): string
    {
        return match ($code) {
            'AT-1' => 'Motorniczy musi przygotować się na ewentualne zatrzymanie w zależności od podanego sygnału świetlnego na sygnalizatorze ST.',
            'AT-2' => 'Motorniczy dowiaduje się, że systemy automatyczne zarejestrowały jego nadjechanie (np. przez pętlę indukcyjną na torowisku) i odpowiednio wcześnie przygotują dla niego pionową kreskę (zielone światło).',
            'AT-3' => 'Nakazuje dostosowanie prędkości tramwaju do pochyłości terenu. Waga tramwaju to dziesiątki ton, zjazd ze stromego wzniesienia drastycznie wydłuża drogę hamowania.',
            'AT-4' => 'Ostrzega, że przy niekorzystnych warunkach (np. liście na torach) tramwaj może mieć problem z wyjechaniem pod górkę, jeśli motorniczy nie zaplanuje rozbiegu.',
            'AT-5' => 'Wzmożona czujność – zjazd do zajezdni, skomplikowany splot torowy lub niebezpieczny przejazd przez środek ronda.',
            'BT-1' => 'Podana liczba (np. 15) to wartość prędkości w km/h, której motorniczemu nie wolno przekroczyć na danym odcinku (często np. na zwrotnicach lub krzyżownicach, by tramwaj nie wypadł z torów).',
            default => '',
        };
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function getFaqItems(string $code): array
    {
        return match ($code) {
            'BT-1' => [
                [
                    'question' => 'Czy jeśli jadę autem po torowisku wydzielonym liniami i widzę kwadratowy znak "20", to muszę zwolnić do 20 km/h?',
                    'answer' => 'Nie. Kwadratowe znaki z czarną ramką to ograniczenia prędkości wyłącznie dla tramwajów (BT-1). Ty podlegasz wyłącznie okrągłym znakom B-33 (z czerwoną ramką).',
                ],
            ],
            'AT-1', 'AT-2', 'AT-3', 'AT-4', 'AT-5' => [
                [
                    'question' => 'Czy znaki w kształcie rombu nad torowiskiem są ważne dla cywilów?',
                    'answer' => 'Nie. Z punktu widzenia kierowcy samochodu osobowego, nie oznaczają one niczego istotnego dla dynamiki jego ruchu.',
                ],
            ],
            default => [],
        };
    }
}
