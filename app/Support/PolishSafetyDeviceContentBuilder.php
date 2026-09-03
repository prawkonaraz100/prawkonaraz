<?php

namespace App\Support;

class PolishSafetyDeviceContentBuilder
{
    /**
     * @param  array<string, mixed>  $deviceData
     * @return array<string, mixed>
     */
    public function build(array $deviceData, int $index): array
    {
        $code = $deviceData['code'];
        $name = $deviceData['name'];

        $introDefinition = $this->getIntroDefinition($code);
        $meaning = $this->getMeaning($code);
        $placement = 'Urządzenia BRD umieszcza się najczęściej na drodze (np. pachołki, separatory), bezpośrednio przy krawędzi jezdni (słupki prowadzące) lub na wysepkach i przeszkodach na jezdni (sierżanty, słupki przeszkodowe).';
        $driverBehavior = 'Kierowca musi bezwzględnie stosować się do wytyczonych przez urządzenia BRD torów jazdy i granic obszarów wyłączonych, ze względu na fizyczne ryzyko kolizji z urządzeniem lub zabezpieczaną przez nie przeszkodą.';
        $legalSummary = 'Urządzenia bezpieczeństwa ruchu drogowego (Grupa U) nie są znakami drogowymi, lecz fizycznymi elementami infrastruktury, służącymi do optycznego prowadzenia ruchu, zabezpieczenia robót i wskazania pikietażu (kilometrażu) drogi.';

        $legalReferenceLabel = 'Rozporządzenie ws. szczegółowych warunków technicznych dla znaków i sygnałów - Urządzenia BRD';
        $legalReferenceUrl = 'https://isap.sejm.gov.pl';

        $fineSummary = 'Za potrącenie i zniszczenie urządzenia BRD (np. wpadnięcie w pachołki na robotach drogowych) grozi mandat karny za spowodowanie zagrożenia bezpieczeństwa w ruchu drogowym, a także odpowiedzialność finansowa za zniszczoną infrastrukturę państwową.';
        $commonMistakes = 'Ignorowanie kierunku pasków na sierżancie drogowym (U-21) oraz mylenie strony drogi w nocy na podstawie koloru odblasku na słupkach prowadzących (U-1).';
        $editorialNotes = 'Pytania egzaminacyjne z grupy urządzeń U bardzo często opierają się na spostrzegawczości. Pasy opadające na "sierżancie" tworzą strzałkę, która pokazuje bezpieczną stronę ominięcia.';

        $faqItems = $this->getFaqItems($code);

        $metaTitle = "Urządzenie BRD {$code} - do czego służy?";
        $metaDescription = "Sprawdź, jakie jest zastosowanie urządzenia BRD {$code} ({$name}). Dowiedz się, co oznacza to oznakowanie dla kierowcy na drodze.";

        $imagePath = 'traffic-signs/signs/devices/urzadzenie-'.strtolower($code).'.webp';
        $ogImagePath = 'traffic-signs/signs/devices/urzadzenie-'.strtolower($code).'-og.webp';

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
            'review_notes' => 'Wygenerowane automatycznie dla grupy Urządzeń BRD.',
            'source_notes' => 'Załącznik 4 do tzw. "Czerwonej Książki" - warunki techniczne dla urządzeń BRD.',
            'faq_items' => $faqItems,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'image_path' => $imagePath,
            'image_alt' => "Urządzenie bezpieczeństwa ruchu {$code}",
            'image_width' => 800,
            'image_height' => 800,
            'og_image_path' => $ogImagePath,
            'og_image_alt' => "Urządzenie BRD {$code} na drodze",
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'sort_order' => $index * 10,
        ];
    }

    private function getIntroDefinition(string $code): string
    {
        return match ($code) {
            'U-1a' => 'Słupek wskaźnikowy (tzw. słupek prowadzący) U-1 to najpowszechniejsze urządzenie na polskich drogach, mające kształt białego pachołka wbijanego w pobocze. Posiada on elementy odblaskowe.',
            'U-5a' => 'Słupek przeszkodowy (żółty cylinder, często z folią odblaskową) ustawia się na początku wysp dzielących pasy ruchu oraz na azylach dla pieszych, aby fizycznie i wizualnie zabezpieczyć przeszkodę.',
            'U-9' => 'Pachołek drogowy to elastyczny, zwężający się ku górze pachołek w poziome, naprzemienne pasy czerwone i białe. Służy do tymczasowego wyznaczania toru jazdy.',
            'U-12' => 'Zapora drogowa to poprzeczna belka malowana w biało-czerwone pasy, oznaczająca fizyczne zamknięcie drogi dla ruchu, np. z powodu głębokiego wykopu instalacyjnego.',
            'U-21' => 'Tablica kierująca (popularnie zwana "sierżantem") to pionowa, wąska tablica w naprzemienne ukośne pasy biało-czerwone. Przeznaczona do wyznaczania krawędzi zwężonego pasa podczas robót.',
            'U-25' => 'Separator ruchu to niski, najczęściej gumowy, plastikowy lub betonowy próg przytwierdzony do nawierzchni. Może mieć wmontowane odblaski lub otwory na pionowe elastyczne słupki.',
            default => 'Urządzenie bezpieczeństwa ruchu drogowego.',
        };
    }

    private function getMeaning(string $code): string
    {
        return match ($code) {
            'U-1a' => 'Słupki ułatwiają kierowcom orientację co do szerokości drogi oraz jej przebiegu w łukach, zwłaszcza w warunkach nocnych. Dodatkowo na ich profilu umieszcza się numerację drogi i jej kilometraż (pikietaż).',
            'U-5a' => 'Sygnalizuje, że na jezdni znajduje się wysepka lub fizyczna przeszkoda, którą należy ominąć zgodnie z namalowanymi na asfalcie i na samym słupku pasami.',
            'U-9' => 'Wydziela strefy na jezdni wyłączone z ruchu, zabezpiecza strefy wypadków, lub wyznacza prowizoryczny pas ruchu podczas imprez masowych.',
            'U-12' => 'Kategorycznie zabrania dalszej jazdy na wprost. Należy się przed nią zatrzymać i skierować na wytyczony objazd.',
            'U-21' => 'Ostrzega o prowadzonych pracach drogowych i wskazuje stronę, z której należy ominąć przeszkodę. Kierunek ominięcia wyznaczają skośne pasy, które układają się z góry na dół, w stronę właściwego pasa ruchu.',
            'U-25' => 'Fizycznie rozdziela przeciwne pasy ruchu tam, gdzie wymalowanie podwójnej ciągłej to za mało (np. na zwężeniach budowlanych), chroniąc auta przed zderzeniem czołowym.',
            default => '',
        };
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function getFaqItems(string $code): array
    {
        return match ($code) {
            'U-1a' => [
                [
                    'question' => 'Jakie kolory odblasków mają słupki prowadzące po lewej i prawej stronie drogi?',
                    'answer' => 'To kluczowe do bezpieczeństwa w nocy. Słupki po PRAWEJ stronie jezdni mają CZERWONE odblaski, natomiast te po LEWEJ stronie mają BIAŁE odblaski.',
                ],
            ],
            'U-21' => [
                [
                    'question' => 'Jak odczytać z tablicy sierżanta, z której strony omijać remont?',
                    'answer' => 'Pasy na tablicy zawsze opadają skośnie do dołu w tę stronę, po której należy bezpiecznie ominąć tablicę. Jeśli pas leci od lewej góry do prawego dołu - omijaj z prawej strony.',
                ],
            ],
            'U-9' => [
                [
                    'question' => 'Czy podczas omijania pachołków U-9 mogę najechać na podwójną linię ciągłą (P-4)?',
                    'answer' => 'Tak, jeśli pachołki wyznaczają nowy, tymczasowy tor jazdy w miejscu awarii. Znaki tymczasowe (oraz osoby kierujące ruchem) znoszą ważność znaków stałych (poziomych i pionowych).',
                ],
            ],
            default => [],
        };
    }
}
