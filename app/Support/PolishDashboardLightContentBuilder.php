<?php

namespace App\Support;

class PolishDashboardLightContentBuilder
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
        $placement = 'Kontrolka znajduje się na zestawie wskaźników (desce rozdzielczej) w polu widzenia kierowcy, najczęściej tuż za kołem kierownicy. Jej dokładne położenie zależy od marki i modelu pojazdu.';
        $driverBehavior = $this->getDriverBehavior($code);
        $legalSummary = 'Znajomość kontrolek ostrzegawczych (czerwonych) i informacyjnych (żółtych) wchodzi w zakres podstawowej wiedzy z obsługi pojazdu, sprawdzanej na egzaminie państwowym na placu manewrowym. Niestosowanie się do sygnałów awarii może narazić pojazd na uszkodzenie, a kierowcę na utratę kontroli nad pojazdem.';

        $legalReferenceLabel = 'Załącznik do rozporządzenia w sprawie egzaminowania';
        $legalReferenceUrl = 'https://isap.sejm.gov.pl';

        $fineSummary = 'Zignorowanie kontrolki z reguły nie wiąże się z mandatem karnym wprost, chyba że poruszanie się niesprawnym pojazdem stworzy zagrożenie w ruchu lub pojazd nie spełnia warunków technicznych (np. wycieki płynów, brak oświetlenia). Koszty zaniedbania usterki to przede wszystkim ogromne rachunki w serwisie mechanicznym.';
        $commonMistakes = $this->getCommonMistakes($code);
        $editorialNotes = 'Warto pamiętać, że na egzaminie państwowym kolor kontrolki od razu podpowiada powagę sytuacji. Czerwone kontrolki oznaczają bezwzględną konieczność przerwania jazdy. Kontrolki żółte pozwalają na kontynuowanie jazdy, ale wymuszają ostrożność i pilną wizytę u mechanika.';

        $faqItems = $this->getFaqItems($code);

        $metaTitle = "Kontrolka {$code} {$name} - znaczenie i co robić";
        $metaDescription = "Sprawdź, co oznacza kontrolka {$code} ({$name}). Dowiedz się, czy możesz kontynuować jazdę i co sprawdzić pod maską samochodu.";

        $imagePath = 'traffic-signs/signs/dashboard/kontrolka-'.$signData['slug'].'.webp';
        $ogImagePath = 'traffic-signs/signs/dashboard/kontrolka-'.$signData['slug'].'-og.webp';

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
            'review_notes' => 'Wygenerowane automatycznie dla grupy Kontrolki Pojazdu.',
            'source_notes' => 'Baza pytań WORD, instrukcje obsługi pojazdów.',
            'faq_items' => $faqItems,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'image_path' => $imagePath,
            'image_alt' => "Kontrolka {$name}",
            'image_width' => 800,
            'image_height' => 800,
            'og_image_path' => $ogImagePath,
            'og_image_alt' => "Kontrolka {$name} na tablicy rozdzielczej",
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'sort_order' => $index * 10,
        ];
    }

    private function getIntroDefinition(string $code): string
    {
        return match ($code) {
            'K-1' => 'Żółta kontrolka o kształcie przypominającym zarys silnika (często z napisem CHECK lub bez), oznaczająca awarię układu sterowania silnikiem lub układu emisji spalin.',
            'K-2' => 'Czerwona kontrolka w kształcie "lampy Aladyna" z kroplą spadającą z "dzióbka", symbolizująca brak odpowiedniego ciśnienia oleju w układzie smarowania silnika.',
            'K-3' => 'Czerwona kontrolka w kształcie prostokątnej baterii z symbolem plusa (+) i minusa (-), wskazująca na brak ładowania akumulatora przez alternator.',
            'K-4' => 'Czerwona kontrolka z symbolem termometru zanurzonego w falach płynu, oznaczająca zbyt wysoką temperaturę płynu chłodzącego silnik.',
            'K-5' => 'Czerwona kontrolka w kształcie okręgu ujętego z obu stron w nawiasy, wewnątrz którego znajduje się znak wykrzyknika (!), litera P lub oba te symbole. Oznacza zaciągnięty hamulec postojowy lub poważną awarię układu.',
            default => 'Kontrolka informacyjna lub ostrzegawcza na desce rozdzielczej pojazdu.',
        };
    }

    private function getMeaning(string $code): string
    {
        return match ($code) {
            'K-1' => 'Zapalenie się tej kontrolki sygnalizuje, że komputer pokładowy (ECU) wykrył usterkę w pracy silnika, układu wtryskowego lub układu oczyszczania spalin (np. uszkodzona sonda lambda). Ponieważ jest to kontrolka żółta, awaria nie jest z reguły krytyczna dla natychmiastowego zniszczenia silnika, ale drastycznie pogarsza parametry pracy.',
            'K-2' => 'Ta czerwona kontrolka oznacza całkowity spadek ciśnienia oleju w silniku (bądź jego poziomu poniżej krytycznego minimum). Pompa oleju nie jest w stanie dostarczyć środka smarnego do ruchomych części silnika (tłoki, panewki, wał). To jeden z najbardziej alarmujących sygnałów w całym aucie.',
            'K-3' => 'Mimo iż symbolizuje akumulator, najczęściej oznacza usterkę alternatora (prądnicy) lub pęknięcie paska wielorowkowego, który go napędza. Silnik zaczyna pracować pobierając prąd bezpośrednio z akumulatora, który bardzo szybko się wyczerpie.',
            'K-4' => 'Oznacza, że silnik zaczął się przegrzewać. Płyn chłodniczy mógł wyciec, wentylator chłodnicy uległ awarii lub zablokował się termostat. Ignorowanie tego sygnału prowadzi do zatarcia silnika i "wydmuchania" uszczelki pod głowicą.',
            'K-5' => 'Ma ona podwójne znaczenie. Jeśli świeci się podczas postoju, przypomina o zaciągniętym hamulcu ręcznym. Jeśli świeci się w trakcie jazdy po zwolnieniu ręcznego, oznacza krytycznie niski poziom płynu hamulcowego lub poważną awarię systemu hamulcowego.',
            default => '',
        };
    }

    private function getDriverBehavior(string $code): string
    {
        return match ($code) {
            'K-1' => 'Możesz kontynuować jazdę, ale powinieneś to robić ostrożnie, nie obciążając silnika, i skierować się do najbliższego warsztatu na diagnostykę komputerową. Jeśli jednak kontrolka zaczyna mrugać - należy natychmiast się zatrzymać, gdyż z reguły oznacza to wypadanie zapłonów mogące zniszczyć katalizator.',
            'K-2' => 'Należy natychmiast zatrzymać pojazd w bezpiecznym miejscu i wyłączyć silnik. Praca silnika bez smarowania przez chociażby kilka minut doprowadzi do jego nieodwracalnego zatarcia i całkowitego zniszczenia. Należy sprawdzić poziom oleju bagnetem i ewentualnie go uzupełnić lub wezwać lawetę.',
            'K-3' => 'Zatrzymaj pojazd w bezpiecznym miejscu. Wyłącz wszystkie zbędne odbiorniki prądu (klimatyzacja, radio, podgrzewanie szyb). Pamiętaj, że w niektórych autach ten sam pasek napędza również pompę wody - sprawdź, czy pasek fizycznie się nie zerwał. Jeśli silnik nie jest przegrzewany, na samym akumulatorze można przejechać jeszcze od kilku do kilkunastu kilometrów do warsztatu.',
            'K-4' => 'Natychmiast zjedź na pobocze i zgaś silnik. Jeśli musisz kontynuować jazdę do pobliskiej zatoki, włącz maksymalne ogrzewanie wnętrza z najsilniejszym nawiewem - nagrzewnica to "druga chłodnica" i pomoże odebrać trochę ciepła. Uważaj podczas sprawdzania poziomu płynu - odkręcanie korka przy gorącym silniku grozi poważnymi poparzeniami!',
            'K-5' => 'Upewnij się, że zwolniłeś hamulec ręczny do samego końca. Jeśli kontrolka nadal świeci, natychmiast zatrzymaj auto! Brak płynu hamulcowego może oznaczać całkowity brak hamulców przy kolejnym wciśnięciu pedału. Jazda takim pojazdem jest surowo zabroniona i skrajnie niebezpieczna.',
            default => '',
        };
    }

    private function getCommonMistakes(string $code): string
    {
        return match ($code) {
            'K-1' => 'Ignorowanie kontrolki przez dłuższy czas z myślą, że "ten typ tak ma", co doprowadza do stopienia reaktora katalitycznego.',
            'K-2' => 'Próba dojechania do mechanika lub stacji benzynowej "bo to tylko kilometr". Zatarciu silnika wystarczy kilkadziesiąt sekund pod obciążeniem bez smarowania.',
            'K-3' => 'Kontynuowanie jazdy w ciemności na wyczerpującym się akumulatorze. Może to spowodować nagłe zgaśnięcie świateł mijania na ruchliwej drodze i w efekcie wypadek.',
            'K-4' => 'Próba odkręcenia korka zbiorniczka wyrównawczego płynu chłodniczego bezpośrednio po zatrzymaniu auta, co prowadzi do wytrysku wrzątku pod ciśnieniem prosto w twarz.',
            'K-5' => 'Dalsza jazda ze świecącą kontrolką przy założeniu, że "hamulce jeszcze jakoś działają" i "pewnie czujnik ręcznego się zaciął".',
            default => '',
        };
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function getFaqItems(string $code): array
    {
        return match ($code) {
            'K-1' => [
                [
                    'question' => 'Czy z kontrolką Check Engine można przejść badanie techniczne?',
                    'answer' => 'Nie. Świecąca się kontrolka check engine jest klasyfikowana przez diagnostów jako usterka istotna i pojazd nie przejdzie okresowego badania technicznego.',
                ],
                [
                    'question' => 'Dlaczego silnik stracił moc, gdy zapalił się Check Engine?',
                    'answer' => 'Komputer pokładowy prawdopodobnie przeszedł w "tryb awaryjny" (Limp Mode), obcinając moc i doładowanie w celu ochrony jednostki przed dalszymi uszkodzeniami.',
                ],
            ],
            'K-2' => [
                [
                    'question' => 'Zapaliła mi się czerwona konewka oleju na zakręcie na sekundę. Co robić?',
                    'answer' => 'To oznaka skrajnie niskiego poziomu oleju. Na zakręcie resztki oleju przelały się na bok i smok pompy zassał powietrze. Natychmiast zaparkuj i dolej odpowiedni olej!',
                ],
            ],
            'K-3' => [
                [
                    'question' => 'Czy ta kontrolka oznacza, że mój akumulator po prostu padł ze starości?',
                    'answer' => 'Nie. Jeśli pali się podczas pracy silnika, oznacza brak dopływu prądu z alternatora, a nie samą awarię baterii.',
                ],
            ],
            'K-4' => [
                [
                    'question' => 'Mogę dolać zimnej wody do przegrzanego silnika?',
                    'answer' => 'W ostateczności (np. by zjechać w bezpieczne miejsce), tak. Jednak nalewanie lodowatej cieczy do w pełni rozgrzanego silnika grozi pęknięciem bloku lub głowicy z powodu szoku termicznego. Należy dolewać płyn stopniowo przy pracującym, letnim silniku.',
                ],
            ],
            'K-5' => [
                [
                    'question' => 'Hamulec spuszczony, a ikona wykrzyknika nadal mruga. O co chodzi?',
                    'answer' => 'Najprawdopodobniej układ zgłasza zbyt małą ilość płynu hamulcowego, ewentualnie, w zależności od modelu, skrajne zużycie klocków hamulcowych. Sprawdź zbiorniczek pod maską.',
                ],
            ],
            default => [],
        };
    }
}
