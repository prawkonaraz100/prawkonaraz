<?php

namespace App\Support;

use Illuminate\Support\Str;

class PolishPlateSignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $config = $this->plateConfig()[$sign['code']] ?? $this->fallbackConfig($sign);
        $assetPath = $this->assetPath($sign['slug']);
        $imageAlt = $sign['name'];

        return [
            'intro_definition' => "Tabliczka {$sign['code']} to dodatkowy element informacyjny umieszczany pod znakami pionowymi (najczęściej ostrzegawczymi, zakazu lub nakazu). Jej zadaniem jest doprecyzowanie zakresu, odległości lub specyfiki działania znaku, pod którym została umieszczona.",
            'meaning' => $config['meaning'],
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => 'Tabliczki do znaków drogowych (grupa T) nigdy nie występują samodzielnie. Stanowią integralną część znaku, pod którym są umieszczone, a ich treść ma moc wiążącą dla kierowcy w kontekście głównego znaku.',
            'fine_summary' => 'Złamanie przepisów wynikających z połączonego działania znaku i tabliczki (np. zakazu zatrzymywania się z tabliczką T-24) skutkuje nałożeniem mandatu za naruszenie danego znaku, często połączonym z surowszymi konsekwencjami (np. odholowaniem pojazdu).',
            'common_mistakes' => $config['mistake'],
            'editorial_notes' => $config['editorial_notes'],
            'review_notes' => 'Szkic przygotowany dla implementacji tabliczek T. Wymaga docelowych grafik w odpowiednim formacie.',
            'source_notes' => 'Baza wiedzy oparta na rozporządzeniu w sprawie znaków drogowych z uwzględnieniem specyfiki relacji tabliczka-znak.',
            'faq_items' => [
                [
                    'question' => "Co oznacza tabliczka {$sign['code']} pod znakiem?",
                    'answer' => $config['faq_duty'],
                ],
                [
                    'question' => "O czym warto pamiętać widząc {$sign['code']}?",
                    'answer' => $config['faq_mistake'],
                ],
            ],
            'meta_title' => "Tabliczka {$sign['code']} - znaczenie i zastosowanie na drodze",
            'meta_description' => "Sprawdź, jak czytać tabliczkę {$sign['code']}. Dowiedz się, w jaki sposób modyfikuje ona znaczenie znaku, z którym występuje, i jak uniknąć błędów.",
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
    protected function plateConfig(): array
    {
        return [
            'T-1' => [
                'meaning' => 'Wskazuje faktyczną odległość od znaku ostrzegawczego do miejsca niebezpiecznego, przed którym znak ten ostrzega.',
                'placement' => 'Umieszczana pod znakami ostrzegawczymi (A), gdy odległość ta jest inna niż standardowa dla danego rodzaju drogi.',
                'behavior' => 'Należy dostosować prędkość i przygotować się na wystąpienie niebezpieczeństwa po przejechaniu dystansu wskazanego na tabliczce.',
                'mistake' => 'Powszechnym błędem jest ignorowanie precyzyjnego dystansu i reagowanie dopiero w momencie pojawienia się zagrożenia w polu widzenia.',
                'faq_duty' => 'Precyzuje, za ile metrów kierowca napotka ostrzegane zagrożenie.',
                'faq_mistake' => 'Nie myl tej tabliczki z długością odcinka, na którym występuje niebezpieczeństwo (do tego służy inna tabliczka z dwiema strzałkami).',
                'editorial_notes' => 'Podstawowa tabliczka, omówić różnicę między T-1 a T-2.',
            ],
            'T-6a' => [
                'meaning' => 'Graficznie odwzorowuje skomplikowany, łamany układ skrzyżowania, wskazując grubą linią przebieg drogi z pierwszeństwem.',
                'placement' => 'Pod znakami D-1 (droga z pierwszeństwem) lub A-7 (ustąp pierwszeństwa) oraz B-20 (STOP) przed skrzyżowaniami.',
                'behavior' => 'Zanim wjedziesz na skrzyżowanie, zanalizuj układ linii. Ustal, na której odnodze stoisz Ty, a z których wlotów mogą nadjechać pojazdy mające pierwszeństwo.',
                'mistake' => 'Często kierowcy patrzą tylko na główny znak (np. Ustąp pierwszeństwa), nie sprawdzając na tabliczce, kto dokładnie na tym skrzyżowaniu ma pierwszeństwo.',
                'faq_duty' => 'Pomaga zrozumieć nietypowy rozkład pierwszeństwa na skrzyżowaniu.',
                'faq_mistake' => 'Błędem jest jazda "na wprost" z założeniem posiadania pierwszeństwa, gdy gruba linia nakazuje zmianę kierunku.',
                'editorial_notes' => 'Konieczne do wykorzystania w dziale o skrzyżowaniach i pierwszeństwie łamanym.',
            ],
            'T-24' => [
                'meaning' => 'Oznacza, że pojazd pozostawiony w miejscu obowiązywania zakazu (np. zatrzymywania się) zostanie usunięty na koszt właściciela pojazdu.',
                'placement' => 'Umieszczana pod znakami z grupy zakazu zatrzymywania się i postoju (B-35, B-36) w strefach o szczególnym znaczeniu.',
                'behavior' => 'Bezwzględnie zrezygnuj z parkowania w tym miejscu, nawet "tylko na chwilę".',
                'mistake' => 'Lekceważenie tabliczki na zasadzie "zaraz wracam", co skutkuje kosztownym odbiorem auta z parkingu policyjnego.',
                'faq_duty' => 'Ostrzega przed fizycznym odholowaniem pojazdu łamiącego zakaz.',
                'faq_mistake' => 'Zostawienie włączonych świateł awaryjnych nie uchroni przed odholowaniem z miejsca oznaczonego tą tabliczką.',
                'editorial_notes' => 'Bardzo poszukiwana fraza przez zdezorientowanych kierowców miejskich.',
            ],
            'T-30' => [
                'meaning' => 'Precyzuje procentową wartość lub stopień nachylenia drogi na zjazdach i podjazdach.',
                'placement' => 'Stosowana często pod znakami A-22 (niebezpieczny zjazd) lub A-23 (stromy podjazd).',
                'behavior' => 'W górach przygotuj się na hamowanie silnikiem lub redukcję biegów. Im wyższa wartość na tabliczce, tym bardziej stromy jest dany odcinek.',
                'mistake' => 'Zjeżdżanie ze wzniesienia z wciśniętym sprzęgłem lub używając tylko i wyłącznie hamulca nożnego.',
                'faq_duty' => 'Wskazuje konkretny kąt pochylenia terenu.',
                'faq_mistake' => 'Nieużywanie hamowania silnikiem na długich i stromych zjazdach prowadzi do spalenia hamulców.',
                'editorial_notes' => 'Element wprowadzający do nauki jazdy w terenie górzystym.',
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
            'meaning' => "Tabliczka {$sign['code']}, nazywana w przepisach jako {$lowerName}, stanowi nierozerwalne uzupełnienie głównego znaku drogowego.",
            'placement' => 'Znajduje się bezpośrednio pod tarczą znaku, którego dotyczy. Jej postanowienia obowiązują na tym samym obszarze.',
            'behavior' => 'Analizując sytuację drogową zawsze odczytuj znaczenie znaku w połączonym kontekście z wiszącą pod nim tabliczką.',
            'mistake' => 'Najczęstszym problemem jest skupienie się tylko na głównej tarczy znaku (np. samym trójkącie lub zakazie) z pominięciem treści na tabliczce.',
            'faq_duty' => 'Jej zadaniem jest precyzyjne ukierunkowanie poleceń wydanych przez znak drogowy, do którego jest przymocowana.',
            'faq_mistake' => 'Ignorowanie treści uzupełniających podczas interpretacji skomplikowanych skrzyżowań.',
            'editorial_notes' => 'Doprecyzować przy kolejnym przeglądzie bazy tekstów.',
        ];
    }

    protected function assetPath(string $slug): string
    {
        return 'traffic-signs/signs/plates/znak-'.$slug.'.webp';
    }
}
