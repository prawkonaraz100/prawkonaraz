<?php

namespace App\Support;

class PolishMilitarySignContentBuilder
{
    /**
     * @param  array<string, mixed>  $signData
     * @return array<string, mixed>
     */
    public function build(array $signData, int $index): array
    {
        $code = $signData['code'];
        $name = $signData['name'];

        $introDefinition = $this->getIntroDefinition($code);
        $meaning = $this->getMeaning($code);
        $placement = 'Znaki te umieszcza się najczęściej przed obiektami mostowymi (mostami, wiaduktami) znajdującymi się w ciągach dróg publicznych, wykorzystywanych jako trasy przejazdu kolumn wojskowych.';
        $driverBehavior = $this->getDriverBehavior($code);
        $legalSummary = 'Znaki z grupy W nie dotyczą kierowców pojazdów cywilnych. Opierają się one na klasyfikacji MLC (Military Load Classification), która uwzględnia nie tylko samą masę pojazdu, ale też nacisk osi, wymiary i dynamikę przejazdu w kolumnie wojskowej.';

        $legalReferenceLabel = 'Rozporządzenie ws. znaków i sygnałów drogowych - Znaki wojskowe';
        $legalReferenceUrl = 'https://isap.sejm.gov.pl';

        $fineSummary = 'Ponieważ znaki te kierowane są wyłącznie do kierujących pojazdami Sił Zbrojnych RP i wojsk sojuszniczych, kierowca pojazdu cywilnego nie może otrzymać mandatu za niestosowanie się do nich (o ile nie łamie cywilnego znaku np. B-18).';
        $commonMistakes = $this->getCommonMistakes($code);
        $editorialNotes = 'Warto wyraźnie zaznaczyć dla kursantów, że widoczna na znakach liczba NIE oznacza ograniczenia tonażowego w tonach. To klasa MLC, do której wojsko ma własne tabele przeliczeniowe.';

        $faqItems = $this->getFaqItems($code);

        $metaTitle = "Znak {$code} {$name} - znaczenie";
        $metaDescription = "Sprawdź, co oznacza żółty znak wojskowy {$code}. Dowiedz się, czy dotyczy kierowców cywilnych i jak odczytać klasę obciążenia MLC przed mostem.";

        $imagePath = 'traffic-signs/signs/military/znak-'.strtolower($code).'.webp';
        $ogImagePath = 'traffic-signs/signs/military/znak-'.strtolower($code).'-og.webp';

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
            'review_notes' => 'Wygenerowane automatycznie dla grupy Znaków Wojskowych.',
            'source_notes' => 'Baza pytań WORD, klasyfikacja STANAG 2021 (MLC).',
            'faq_items' => $faqItems,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'image_path' => $imagePath,
            'image_alt' => "Znak wojskowy {$name}",
            'image_width' => 800,
            'image_height' => 800,
            'og_image_path' => $ogImagePath,
            'og_image_alt' => "Znak wojskowy {$name} na drodze",
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'sort_order' => $index * 10,
        ];
    }

    private function getIntroDefinition(string $code): string
    {
        return match ($code) {
            'W-1' => 'Znak W-1 to okrągła żółta tarcza z czarnym symbolem, określająca klasę obciążenia mostu dla kolumn wojskowych poruszających się w jednym kierunku.',
            'W-2' => 'Znak W-2 to żółta tarcza wskazująca, jaką klasę obciążenia wytrzyma dany obiekt w sytuacji, gdy odbywa się na nim jednoczesny ruch wojskowy w obu kierunkach.',
            'W-3' => 'Znak W-3 to zespolona informacja określająca klasę obciążenia mostu, pokazująca różne wartości w zależności od tego, czy na obiekcie odbywa się ruch jedno- czy dwukierunkowy.',
            'W-4' => 'Znak W-4 rozróżnia klasę obciążenia mostu o ruchu jednokierunkowym w zależności od typu pojazdu (górna wartość dla pojazdów kołowych, dolna dla gąsienicowych).',
            'W-5' => 'Znak W-5 precyzuje maksymalną klasę obciążenia przy ruchu dwukierunkowym, podając odrębne wartości dla sprzętu kołowego i gąsienicowych wozów bojowych.',
            'W-6' => 'Znak W-6 ma formę żółtego okręgu z wpisaną szerokością. Wskazuje on na fizyczne ograniczenie szerokości mostu lub samej jezdni w metrach, kluczowe dla przejazdu transportów ponadgabarytowych.',
            'W-7' => 'Znak W-7 (żółty okrąg z trójkątami u góry i dołu) określa dopuszczalną wysokość skrajni pionowej nad jezdnią.',
            default => 'Znak dla kierujących pojazdami wojskowymi.',
        };
    }

    private function getMeaning(string $code): string
    {
        return match ($code) {
            'W-1' => 'Wskazuje dowódcy kolumny wojskowej, że most jest w stanie przenieść obciążenie pojazdów danej klasy MLC, o ile jadą one w tym samym kierunku, z zachowaniem wojskowych odstępów.',
            'W-2' => 'Informuje, że z mostu mogą korzystać jednocześnie dwie kolumny jadące z naprzeciwka, pod warunkiem że ich klasa obciążenia nie przekracza wartości na znaku.',
            'W-3' => 'Dostarcza podwójnej informacji – zazwyczaj zezwala na ruch cięższych pojazdów, jeśli puszczone zostaną wahadłowo (ruch jednokierunkowy), i mniejszych wozów, jeśli most będzie obciążony na obu pasach.',
            'W-4' => 'Pojazdy gąsienicowe (czołgi) mają inny rozkład nacisku na podłoże niż wozy kołowe (KTO Rosomak). Znak ten określa limity osobno dla obu trakcji w ruchu jednokierunkowym.',
            'W-5' => 'Określa bezpieczne limity obciążenia MLC dla ruchu dwukierunkowego, uwzględniając różne siły działające na konstrukcję mostu ze strony kół i gąsienic.',
            'W-6' => 'Informuje kierowców potężnych transporterów logistycznych i platform z czołgami, czy ich sprzęt w ogóle zmieści się fizycznie między barierami mostu.',
            'W-7' => 'Analogicznie do znaku zakazu B-16 dla cywilów, określa prześwit pod wiaduktem lub konstrukcją mostu, by zapobiec zablokowaniu się sprzętu z ładunkiem.',
            default => '',
        };
    }

    private function getDriverBehavior(string $code): string
    {
        return match ($code) {
            'W-6', 'W-7' => 'Kierowca pojazdu cywilnego powinien zignorować ten znak. Dla niego wiążące są jedynie cywilne znaki zakazu z czerwoną obwódką (np. B-15 zakaz wjazdu pojazdów o szerokości ponad... lub B-16 zakaz wjazdu pojazdów o wysokości ponad...).',
            default => 'Jeśli prowadzisz samochód osobowy, ciężarowy czy autobus – całkowicie zignoruj ten znak. Nie jest on skierowany do Ciebie i nie nakłada na Ciebie żadnych ograniczeń tonażowych czy kierunkowych.',
        };
    }

    private function getCommonMistakes(string $code): string
    {
        return 'Kierowcy samochodów ciężarowych czasami mylą liczbę na żółtym znaku z cywilnym ograniczeniem w tonach (znak B-18) i próbują zawracać, podczas gdy rzeczywisty zakaz wjazdu ich nie dotyczy.';
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function getFaqItems(string $code): array
    {
        return match ($code) {
            'W-1', 'W-2', 'W-3', 'W-4', 'W-5' => [
                [
                    'question' => 'Czy liczba na tym znaku to waga w tonach?',
                    'answer' => 'Nie. Klasa MLC (Military Load Classification) to skomplikowany współczynnik NATO uwzględniający rozstaw osi, powierzchnię styku kół z jezdnią oraz inne siły przenoszone na przęsła mostu, a nie czysta masa w tonach.',
                ],
                [
                    'question' => 'Czy ten znak dotyczy mojego auta dostawczego?',
                    'answer' => 'Absolutnie nie. Znaki grupy W (żółte okrągłe tarcze) dotyczą wyłącznie kierujących pojazdami Sił Zbrojnych oraz wojsk sojuszniczych.',
                ],
            ],
            'W-6', 'W-7' => [
                [
                    'question' => 'Skoro szerokość lub wysokość na żółtym znaku dotyczy wojska, to skąd mam wiedzieć czy moja ciężarówka się zmieści?',
                    'answer' => 'Jeśli fizyczne wymiary obiektu są zbyt małe dla standardowych pojazdów ciężarowych, zarządca drogi ma obowiązek powiesić cywilne znaki zakazu (B-15 lub B-16). Żółte znaki pomagają jedynie dowódcom transportów ponadnormatywnych.',
                ],
            ],
            default => [],
        };
    }
}
