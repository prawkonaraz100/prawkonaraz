<?php

namespace App\Support;

class PolishTrafficDirectorContentBuilder
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
        $placement = 'Osoba kierująca ruchem (najczęściej umundurowany policjant, ale też strażak, żołnierz czy pracownik drogowy) znajduje się zazwyczaj na środku skrzyżowania, w miejscu o najlepszej widoczności dla wszystkich wlotów.';
        $driverBehavior = $this->getDriverBehavior($code);
        $legalSummary = 'Zgodnie z Prawem o ruchu drogowym, polecenia i sygnały dawane przez osobę kierującą ruchem mają absolutne pierwszeństwo przed sygnałami świetlnymi, znakami drogowymi oraz ogólnymi zasadami ruchu. Niestosowanie się do nich to jedno z najcięższych wykroczeń drogowych.';
        
        $legalReferenceLabel = 'Prawo o ruchu drogowym - Hierarchia ważności (Art. 5)';
        $legalReferenceUrl = 'https://isap.sejm.gov.pl';
        
        $fineSummary = 'Niezastosowanie się do sygnałów dawanych przez osobę kierującą ruchem wiąże się z bardzo wysokim mandatem karnym, a nierzadko również z utratą prawa jazdy (szczególnie w przypadku stworzenia zagrożenia w ruchu lądowym lub zignorowania nakazu zatrzymania).';
        $commonMistakes = $this->getCommonMistakes($code);
        $editorialNotes = 'To kluczowy element edukacyjny dla kursantów. Często na egzaminie państwowym kursanci widząc zielone światło próbują wjechać na skrzyżowanie, ignorując fakt, że policjant stoi do nich przodem lub tyłem.';
        
        $faqItems = $this->getFaqItems($code);
        
        $metaTitle = "Sygnał {$code} - {$name} - znaczenie";
        $metaDescription = "Sprawdź, co oznacza postawa policjanta {$name} na skrzyżowaniu. Dowiedz się, do jakiego światła na sygnalizatorze można porównać ten gest.";

        $imagePath = 'traffic-signs/signs/directors/postawa-'.$signData['slug'].'.webp';
        $ogImagePath = 'traffic-signs/signs/directors/postawa-'.$signData['slug'].'-og.webp';

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
            'review_notes' => 'Wygenerowane automatycznie dla grupy Osoba Kierująca Ruchem.',
            'source_notes' => 'Baza pytań WORD, Ustawa PoRD.',
            'faq_items' => $faqItems,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'image_path' => $imagePath,
            'image_alt' => "Sygnał {$name}",
            'image_width' => 800,
            'image_height' => 800,
            'og_image_path' => $ogImagePath,
            'og_image_alt' => "Sygnał {$name} na drodze",
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'sort_order' => $index * 10,
        ];
    }

    private function getIntroDefinition(string $code): string
    {
        return match ($code) {
            'R-1' => 'Policjant (osoba kierująca ruchem) unosi jedną rękę (prawą) pionowo do góry. Dłoń zazwyczaj skierowana jest w stronę wlotu skrzyżowania.',
            'R-2' => 'Policjant stoi na skrzyżowaniu na baczność lub z rozłożonymi rękami, skierowany twarzą (przodem) lub plecami (tyłem) do nadjeżdżających pojazdów.',
            'R-3' => 'Policjant stoi na skrzyżowaniu z rozłożonymi na boki rękami lub na baczność, będąc ustawionym bokiem (lewym lub prawym ramieniem) w stronę nadjeżdżających pojazdów.',
            'R-4' => 'Policjant wyciąga jedną rękę w konkretnym kierunku, wskazując bezpośrednio na zbliżający się pojazd lub grupę pojazdów.',
            default => 'Sygnał dawany przez osobę uprawnioną do kierowania ruchem.',
        };
    }

    private function getMeaning(string $code): string
    {
        return match ($code) {
            'R-1' => 'Ten gest jest bezpośrednim odpowiednikiem żółtego światła na sygnalizatorze. Oznacza zakaz wjazdu na skrzyżowanie dla zbliżających się pojazdów oraz nakaz bezzwłocznego opuszczenia skrzyżowania przez pojazdy, które już na nie wjechały.',
            'R-2' => 'Postawa przodem lub tyłem to odpowiednik czerwonego światła. Oznacza całkowity, bezwzględny zakaz wjazdu na skrzyżowanie dla pojazdów nadjeżdżających z kierunków, w stronę których policjant jest zwrócony klatką piersiową lub plecami.',
            'R-3' => 'Ta postawa jest odpowiednikiem zielonego światła. Zezwala na wjazd na skrzyżowanie pojazdom nadjeżdżającym z kierunku, do którego policjant jest zwrócony bokiem (lewym lub prawym).',
            'R-4' => 'Jest to nakaz bezwzględnego zatrzymania się w oznaczonym miejscu lub, jeśli takiego miejsca nie ma, bezpośrednio przed osobą kierującą ruchem w sposób niepowodujący zagrożenia.',
            default => '',
        };
    }

    private function getDriverBehavior(string $code): string
    {
        return match ($code) {
            'R-1' => 'Jeśli dojeżdżasz do skrzyżowania – musisz się zatrzymać. Jeśli w chwili podniesienia ręki znajdowałeś się już na skrzyżowaniu (np. za linią warunkowego zatrzymania) – masz obowiązek jak najszybciej je opuścić, upewniając się o braku kolizji.',
            'R-2' => 'Musisz zatrzymać się przed linią zatrzymania, przejściem dla pieszych lub krawędzią skrzyżowania. Nawet jeśli masz zielone światło na sygnalizatorze, postawa policjanta przodem lub tyłem kategorycznie zakazuje wjazdu.',
            'R-3' => 'Możesz kontynuować jazdę i wjechać na skrzyżowanie. Podczas wykonywania manewru skrętu w lewo pamiętaj o obowiązku ustąpienia pierwszeństwa pojazdom jadącym z przeciwka na wprost oraz skręcającym w prawo (one również widzą policjanta bokiem i mają "zielone").',
            'R-4' => 'Niezwłocznie włącz kierunkowskaz zjeżdżając na skraj jezdni (jeśli policjant tak wskaże) i bezpiecznie zatrzymaj pojazd przed policjantem lub w miejscu przez niego wskazanym ruchem dłoni.',
            default => '',
        };
    }

    private function getCommonMistakes(string $code): string
    {
        return match ($code) {
            'R-1' => 'Gwałtowne hamowanie w obrębie skrzyżowania, co prowadzi do kolizji (najechanie na tył). Kierowca bedący na skrzyżowaniu w trakcie tego gestu nie ma prawa na nim zostać.',
            'R-2' => 'Sugerowanie się sygnalizacją świetlną zamiast sylwetką policjanta. Jeśli światło jest zielone, a policjant stoi przodem/tyłem, wjechanie na skrzyżowanie to automatycznie oblany egzamin za przejazd "na czerwonym".',
            'R-3' => 'Wahanie się przed wjazdem mimo bycia "na zielonym" (bok do kierowcy) lub zapominanie o ustąpieniu pierwszeństwa przy lewoskręcie (policjant nie zwalnia nas z zasad ruchu przy spotkaniu z pojazdem z przeciwka).',
            'R-4' => 'Niezauważenie sygnału lub niebezpieczne hamowanie "w miejscu" na pasie ruchu, bez zjechania do krawędzi jezdni i bez zasygnalizowania zamiaru zatrzymania.',
            default => '',
        };
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function getFaqItems(string $code): array
    {
        return match ($code) {
            'R-1' => [
                [
                    'question' => 'Czy jeśli podniesienie ręki przez policjanta "zaskoczy" mnie na linii zatrzymania, to mogę jechać?',
                    'answer' => 'Gest ten odpowiednik żółtego światła. Jeśli nie możesz zatrzymać pojazdu bez gwałtownego hamowania stwarzającego zagrożenie, powinieneś bezzwłocznie opuścić skrzyżowanie.',
                ],
            ],
            'R-2' => [
                [
                    'question' => 'Policjant stoi do mnie tyłem, ale macha ręką do innego kierowcy, żebym szybciej zjeżdżał. Czy mogę skręcić w prawo?',
                    'answer' => 'Nie! Postawa przodem lub tyłem to absolutne "czerwone światło". Nie wolno wjeżdżać na skrzyżowanie z żadnego pasa ruchu, nawet w celu skrętu.',
                ],
            ],
            'R-3' => [
                [
                    'question' => 'Policjant stoi bokiem. Kto ma pierwszeństwo przy skręcie w lewo?',
                    'answer' => 'Obowiązują ogólne zasady ruchu. Przy skręcie w lewo musisz ustąpić pierwszeństwa pojazdom nadjeżdżającym z przeciwka (do których policjant też stoi bokiem), o ile policjant nie nakaże Ci inaczej.',
                ],
            ],
            'R-4' => [
                [
                    'question' => 'Czy strażak PSP ma prawo kazać mi się zatrzymać na drodze krajowej?',
                    'answer' => 'Tak, strażacy Państwowej Straży Pożarnej oraz OSP w tracie akcji ratowniczych mają uprawnienia do kierowania ruchem i wydawania wiążących poleceń.',
                ],
            ],
            default => [],
        };
    }
}
