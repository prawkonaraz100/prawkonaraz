<?php

namespace App\Support;

class PolishTramSignalContentBuilder
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
        $placement = 'Sygnalizatory dla tramwajów (ST) umieszczane są na skrzyżowaniach i węzłach przesiadkowych, najczęściej obok lub nad torowiskiem. Ich światła mają barwę białą.';
        $driverBehavior = $this->getDriverBehavior($code);
        $legalSummary = 'Sygnały świetlne kierujące ruchem (w tym tramwajowe) odwołują pierwszeństwo wynikające ze znaków drogowych. Jeśli kierowca ma zielone światło ogólne na wprost (S-1), a skręcający tramwaj ma sygnał zakazujący (poziomą kreskę), tramwaj musi ustąpić.';

        $legalReferenceLabel = 'Rozporządzenie w sprawie znaków i sygnałów drogowych - Sygnały świetlne ST';
        $legalReferenceUrl = 'https://isap.sejm.gov.pl';

        $fineSummary = 'Kierowcy pojazdów cywilnych nie podlegają sygnałom ST, ale ich znajomość jest testowana na egzaminach teoretycznych, ponieważ pozwala przewidzieć zachowanie motorniczego na skomplikowanym skrzyżowaniu.';
        $commonMistakes = $this->getCommonMistakes($code);
        $editorialNotes = 'To kluczowe zagadnienie na egzamin. Kursanci często ulegają złudzeniu, że "tramwaj ma zawsze pierwszeństwo". To nieprawda na skrzyżowaniu z sygnalizacją świetlną. Znajomość światła tramwaju rozwiązuje ten dylemat.';

        $faqItems = $this->getFaqItems($code);

        $metaTitle = "Sygnał tramwajowy {$name} - znaczenie i zasady";
        $metaDescription = "Sprawdź, co oznacza białe światło dla tramwaju: {$name}. Dowiedz się, kto ma pierwszeństwo na skrzyżowaniu i jak odczytywać sygnalizatory ST.";

        $imagePath = 'traffic-signs/signs/trams/sygnal-'.strtolower($code).'.webp';
        $ogImagePath = 'traffic-signs/signs/trams/sygnal-'.strtolower($code).'-og.webp';

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
            'review_notes' => 'Wygenerowane automatycznie dla grupy Sygnałów Tramwajowych.',
            'source_notes' => 'Baza pytań WORD, zasady zbiegu sygnalizacji ST z S-1.',
            'faq_items' => $faqItems,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'image_path' => $imagePath,
            'image_alt' => "Sygnał dla tramwaju {$name}",
            'image_width' => 800,
            'image_height' => 800,
            'og_image_path' => $ogImagePath,
            'og_image_alt' => "Sygnał dla tramwaju {$name} na skrzyżowaniu",
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'sort_order' => $index * 10,
        ];
    }

    private function getIntroDefinition(string $code): string
    {
        return match ($code) {
            'ST-1' => 'Sygnał w kształcie poziomej, białej kreski. Jest to kategoryczny zakaz wjazdu dla tramwaju za sygnalizator.',
            'ST-2' => 'Sygnał w kształcie pionowej, białej kreski. Zezwala motorniczemu na jazdę wyłącznie w kierunku na wprost.',
            'ST-3' => 'Sygnał w kształcie ukośnej lub łamanej białej kreski. Zezwala tramwajowi na wjazd na skrzyżowanie z zamiarem skrętu w lewo lub w prawo.',
            'ST-4' => 'Migająca pionowa lub ukośna biała kreska (rzadziej kropka w starszych systemach AT). Pełni funkcję sygnału ostrzegawczego o rychłej zmianie światła na zabraniające (poziomą kreskę).',
            default => 'Sygnał świetlny dla kierujących tramwajami.',
        };
    }

    private function getMeaning(string $code): string
    {
        return match ($code) {
            'ST-1' => 'Dla tramwaju jest to absolutny odpowiednik czerwonego światła. Mimo iż tramwaj jako pojazd szynowy posiada w wielu sytuacjach uprzywilejowanie, to pozioma kreska całkowicie "zabiera" mu możliwość ruchu.',
            'ST-2' => 'Oznacza zielone światło do jazdy na wprost. Uwaga: tramwaj jadący na tym sygnale na wprost ma absolutne pierwszeństwo przed samochodami skręcającymi w lewo (które mają zielone światło ogólne S-1).',
            'ST-3' => 'Działa jak zielone światło kierunkowe (odpowiednik S-3). Jeśli kreska jest przechylona w lewo, tramwaj będzie skręcał w lewo. Pojazd szynowy wykonujący ten manewr na sygnalizacji świetlnej musi ustąpić pojazdom jadącym na wprost!',
            'ST-4' => 'Jest to odpowiednik żółtego światła dla samochodów. Zakazuje tramwajowi wjazdu na skrzyżowanie, chyba że w chwili jego zapalenia motorniczy nie ma fizycznej możliwości zatrzymania ciężkiego składu przed linią warunkowego zatrzymania.',
            default => '',
        };
    }

    private function getDriverBehavior(string $code): string
    {
        return match ($code) {
            'ST-1' => 'Jako kierowca auta: jeśli masz zielone światło do skrętu, a widzisz, że na sygnalizatorze tramwajowym pali się "pozioma kreska", wiesz z absolutną pewnością, że tramwaj na Ciebie nie wjedzie i możesz bezpiecznie opuścić skrzyżowanie.',
            'ST-2' => 'Jako kierowca auta: jeśli zamierzasz skręcić w lewo (a masz zielone na S-1), a z naprzeciwka zbliża się tramwaj jadący na wprost na pionowej kresce, musisz ustąpić mu pierwszeństwa w pierwszej kolejności.',
            'ST-3' => 'Jako kierowca auta: obserwacja ukośnej kreski pozwoli Ci przewidzieć, w którą stronę zjedzie potężny pojazd szynowy, co zminimalizuje ryzyko wjechania pod jego koła przy równoległym skręcie.',
            'ST-4' => 'Jako kierowca auta: potraktuj ten sygnał tak, jakby na skrzyżowaniu zapalało się powoli światło czerwone dla tramwaju. Skład powinien zacząć hamować.',
            default => '',
        };
    }

    private function getCommonMistakes(string $code): string
    {
        return match ($code) {
            'ST-1' => 'Zatrzymywanie się osobówką przed torowiskiem "żeby przepuścić tramwaj" w sytuacji, w której tramwaj ma poziomą kreskę (zakaz wjazdu). Powoduje to groźne zatory i blokowanie własnego zielonego światła.',
            'ST-2' => 'Wjechanie autem "pod nos" rozpędzonego na wprost tramwaju, przy błędnym założeniu, że "skoro obaj mamy zielone, to ja zdążę pierwszy przed nim skręcić".',
            'ST-3' => 'Zapominanie, że na skrzyżowaniach ZE ŚWIATŁAMI tramwaj skręcający w lewo ustępuje pierwszeństwa autom jadącym na wprost (sytuacja zrównania praw przy sygnalizacji).',
            'ST-4' => 'Próba "przecięcia" torów przed hamującym tramwajem. Droga hamowania pojazdu szynowego jest gigantyczna w stosunku do auta.',
            default => '',
        };
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function getFaqItems(string $code): array
    {
        return match ($code) {
            'ST-1' => [
                [
                    'question' => 'Czy jeśli na sygnalizatorze jest pozioma kreska (stój), ale znaki pionowe mówią, że tramwaj jedzie drogą główną, to czy on pojedzie?',
                    'answer' => 'Nie pojedzie. Sygnalizacja świetlna zawsze znosi pierwszeństwo wynikające ze znaków (A-7/D-1). Dopóki kreska jest pozioma, tramwaj stoi.',
                ],
            ],
            'ST-2', 'ST-3' => [
                [
                    'question' => 'Kiedy tramwaj na świetle ZIELONYM (pionowa/ukośna kreska) traci swoje "magiczne pierwszeństwo"?',
                    'answer' => 'Traci je w dwóch sytuacjach: gdy wyjeżdża z pętli (włącza się do ruchu) oraz gdy ma światło zezwalające, ale SKRĘCA, podczas gdy samochód na równoległym zielonym świetle jedzie PROSTO.',
                ],
                [
                    'question' => 'Dlaczego powielacie mit, że samochód nie puszcza tramwaju przy sygnalizacji? Przecież tramwaj to czołg!',
                    'answer' => 'Większość kolizji aut z tramwajami wynika z nieznajomości Art. 95 PoRD - na skrzyżowaniu sterowanym sygnalizacją zrównuje się prawa pojazdów szynowych i cywilnych, wprowadzając logiczne ustępowanie (jadący na wprost jedzie przed skręcającym, nawet jeśli skręca tramwaj).',
                ],
            ],
            'ST-4' => [
                [
                    'question' => 'Czy migająca kreska oznacza awarię sygnalizatora tramwajowego?',
                    'answer' => 'Nie, oznacza ona dokładnie to samo co żółte światło na cywilnym sygnalizatorze – sygnał wkrótce zamieni się w zakazujący wjazdu.',
                ],
            ],
            default => [],
        };
    }
}
