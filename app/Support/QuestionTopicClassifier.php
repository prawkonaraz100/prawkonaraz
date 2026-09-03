<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuestionTopicClassifier
{
    protected const BASIC_BUCKET = 'Pytania podstawowe';

    protected const SPECIALIST_BUCKET = 'Pytania specjalistyczne';

    /**
     * @return array<int, array{key:string,name:string,display_name:string,description:string,bucket:string,sort_order:int}>
     */
    public function definitions(): array
    {
        return [
            [
                'key' => 'warning_signs',
                'name' => 'Znaki ostrzegawcze',
                'display_name' => 'Znaki ostrzegawcze',
                'description' => 'Znaki ostrzegawcze i pytania o niebezpieczenstwa sygnalizowane znakami grupy A.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 10,
            ],
            [
                'key' => 'prohibition_and_mandatory_signs',
                'name' => 'Znaki zakazu, nakazu',
                'display_name' => 'Znaki zakazu i nakazu',
                'description' => 'Znaki zakazu i nakazu oraz wynikajace z nich ograniczenia i obowiazki.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 20,
            ],
            [
                'key' => 'informational_direction_and_supplementary_signs',
                'name' => 'Znaki informacyjne, kierunku i miejscowosci, uzupelniajace',
                'display_name' => 'Znaki informacyjne i tabliczki',
                'description' => 'Znaki informacyjne, kierunku, miejscowosci oraz tabliczki uzupelniajace.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 30,
            ],
            [
                'key' => 'road_markings',
                'name' => 'Znaki drogowe poziome',
                'display_name' => 'Oznakowanie poziome',
                'description' => 'Oznakowanie poziome, linie, strzalki i pola ruchu na jezdni.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 40,
            ],
            [
                'key' => 'traffic_lights_and_controller_signals',
                'name' => 'Sygnaly swietlne, sygnaly dawane przez kierujacego ruchem',
                'display_name' => 'Sygnaly swietlne i kierujacy ruchem',
                'description' => 'Sygnalizacja swietlna oraz polecenia dawane przez osoby kierujace ruchem.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 50,
            ],
            [
                'key' => 'vehicle_lights_and_signals',
                'name' => 'Uzywanie swiatel zewnetrznych i sygnalow pojazdu',
                'display_name' => 'Swiatla i sygnaly pojazdu',
                'description' => 'Swiatla zewnetrzne pojazdu oraz sygnaly dzwiekowe i swietlne.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 60,
            ],
            [
                'key' => 'joining_traffic_and_equal_intersections',
                'name' => 'Wlaczanie sie do ruchu, skrzyzowania rownorzedne',
                'display_name' => 'Wlaczanie sie do ruchu i rownorzedne',
                'description' => 'Wlaczanie sie do ruchu, wyjazd z posesji i zasady na skrzyzowaniach rownorzednych.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 70,
            ],
            [
                'key' => 'intersections_with_priority_signs',
                'name' => 'Skrzyzowania ze znakami okreslajacymi pierwszenstwo przejazdu',
                'display_name' => 'Skrzyzowania z pierwszenstwem',
                'description' => 'Skrzyzowania ze znakami pierwszenstwa, ustapieniem pierwszenstwa i STOP.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 80,
            ],
            [
                'key' => 'intersections_with_traffic_lights',
                'name' => 'Skrzyzowania z sygnalizacja swietlna',
                'display_name' => 'Skrzyzowania z sygnalizacja',
                'description' => 'Przejazd przez skrzyzowania sterowane sygnalizacja swietlna.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 90,
            ],
            [
                'key' => 'controlled_crossings_and_public_transport_stops',
                'name' => 'Skrzyzowania lub przejscia dla pieszych z kierujacym ruchem, miejsca przystankow komunikacji publicznej',
                'display_name' => 'Przejscia, przystanki i kierujacy ruchem',
                'description' => 'Przejscia dla pieszych, miejsca przystankow i sytuacje z kierujacym ruchem poza typowym skrzyzowaniem.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 100,
            ],
            [
                'key' => 'rail_and_tram_crossings',
                'name' => 'Zachowanie na przejazdach kolejowych i tramwajowych',
                'display_name' => 'Przejazdy kolejowe i tramwajowe',
                'description' => 'Przejazdy kolejowe, tramwajowe oraz tory i rogatki.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 110,
            ],
            [
                'key' => 'road_position_entry_exit_stopping',
                'name' => 'Pozycja pojazdu na drodze, wjazd i zjazd ze skrzyzowania, zatrzymanie i postoj',
                'display_name' => 'Pozycja pojazdu, zatrzymanie i postoj',
                'description' => 'Ustawienie pojazdu na jezdni, zajmowanie miejsca na drodze oraz zatrzymanie i postoj.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 120,
            ],
            [
                'key' => 'lane_change_and_turning',
                'name' => 'Zmiana pasa ruchu, zmiana kierunku jazdy',
                'display_name' => 'Zmiana pasa i kierunku jazdy',
                'description' => 'Zmiana pasa, skret, zawracanie i przygotowanie do zmiany kierunku jazdy.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 130,
            ],
            [
                'key' => 'overtaking',
                'name' => 'Wyprzedzanie',
                'display_name' => 'Wyprzedzanie',
                'description' => 'Wyprzedzanie i warunki bezpiecznego wykonania tego manewru.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 140,
            ],
            [
                'key' => 'passing_reversing',
                'name' => 'Omijanie, wymijanie, cofanie',
                'display_name' => 'Omijanie, wymijanie i cofanie',
                'description' => 'Omijanie, wymijanie i cofanie w ruchu drogowym.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 150,
            ],
            [
                'key' => 'behaviour_towards_pedestrians_and_reduced_mobility',
                'name' => 'Zachowanie wobec pieszego, wobec osoby o ograniczonej mozliwosci poruszania sie',
                'display_name' => 'Piesi i ograniczona mobilnosc',
                'description' => 'Relacje z pieszymi oraz osobami o ograniczonej mozliwosci poruszania sie.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 160,
            ],
            [
                'key' => 'behaviour_towards_cyclists_and_children',
                'name' => 'Zachowanie wobec rowerzysty i dzieci',
                'display_name' => 'Rowerzysci i dzieci',
                'description' => 'Bezpieczne zachowanie wobec rowerzystow, dzieci i innych narazonych uczestnikow.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 170,
            ],
            [
                'key' => 'special_caution_exiting_and_securing_vehicle',
                'name' => 'Znaczenie zachowania szczegolnej ostroznosci w stosunku do innych uzytkownikow drogi, wysiadanie z pojazdu, zabezpieczenie pojazdu',
                'display_name' => 'Szczegolna ostroznosc i pojazd',
                'description' => 'Szczegolna ostroznosc wobec innych uczestnikow, wysiadanie i zabezpieczanie pojazdu.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 180,
            ],
            [
                'key' => 'breakdown_accident_and_first_aid',
                'name' => 'Ogolne zasady okreslajace zachowanie kierowcy w momencie awarii lub wypadku, udzielanie pierwszej pomocy przedmedycznej',
                'display_name' => 'Awaria, wypadek i pierwsza pomoc',
                'description' => 'Awaria, wypadek drogowy i pierwsza pomoc przedmedyczna.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 190,
            ],
            [
                'key' => 'perception_decision_alcohol_fatigue',
                'name' => 'Spostrzeganie, ocena sytuacji i podejmowanie decyzji, szczegolnie w zakresie czasu reakcji oraz zmian w zachowaniu za kierownica, spowodowanych wplywem alkoholu, lekow i produktow leczniczych, stanem swiadomosci i zmeczeniem',
                'display_name' => 'Percepcja, zmeczenie i alkohol',
                'description' => 'Percepcja, czas reakcji, zmeczenie, alkohol, leki i podejmowanie decyzji za kierownica.',
                'bucket' => self::BASIC_BUCKET,
                'sort_order' => 200,
            ],
            [
                'key' => 'speed_limits',
                'name' => 'Dopuszczalne predkosci pojazdu, ograniczenia',
                'display_name' => 'Predkosci i ograniczenia',
                'description' => 'Dopuszczalne predkosci, limity i ograniczenia dla pojazdu.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 210,
            ],
            [
                'key' => 'distances_and_braking',
                'name' => 'Odstepy i hamowanie pojazdu',
                'display_name' => 'Odstepy i hamowanie',
                'description' => 'Bezpieczny odstep, droga hamowania i skuteczne hamowanie.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 220,
            ],
            [
                'key' => 'driving_technique',
                'name' => 'Technika kierowania pojazdem',
                'display_name' => 'Technika kierowania',
                'description' => 'Technika prowadzenia pojazdu, panowanie nad autem i prawidlowa obsluga w ruchu.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 230,
            ],
            [
                'key' => 'safety_equipment_and_restraints',
                'name' => 'Wyposazenie pojazdu zwiazane z bezpieczenstwem, korzystanie z pasow, zaglowkow i fotelikow',
                'display_name' => 'Pasy, foteliki i wyposazenie',
                'description' => 'Wyposazenie bezpieczenstwa, pasy, zaglowki, foteliki i elementy ochronne pojazdu.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 240,
            ],
            [
                'key' => 'vehicle_load_and_passenger_safety',
                'name' => 'Czynniki bezpieczenstwa odnoszace sie do pojazdu, ladunku i przewozonych osob',
                'display_name' => 'Ladunek i przewozone osoby',
                'description' => 'Ladunek, bagaz i bezpieczenstwo przewozonych osob.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 250,
            ],
            [
                'key' => 'risk_factors_conditions_and_weather',
                'name' => 'Czynniki ryzyka zwiazane z roznymi warunkami drogowymi, w szczegolnosci ze zmiana tych warunkow w zaleznosci od pogody i pory dnia lub nocy, wlasciwosci roznych typow drog i zwiazane z tym obowiazujace wymagania',
                'display_name' => 'Warunki, ryzyko i pogoda',
                'description' => 'Ryzyka zwiazane z pogoda, pora dnia, typem drogi i warunkami jazdy.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 260,
            ],
            [
                'key' => 'driver_field_of_view',
                'name' => 'Rozne pola widzenia kierowcow',
                'display_name' => 'Pole widzenia kierowcy',
                'description' => 'Pole widzenia kierowcy, martwe pola i ograniczona widocznosc.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 270,
            ],
            [
                'key' => 'owner_obligations_insurance_documents',
                'name' => 'Obowiazki wlasciciela/posiadacza pojazdu, ubezpieczenia, wymagane dokumenty',
                'display_name' => 'Dokumenty, ubezpieczenie i obowiazki',
                'description' => 'Obowiazki wlasciciela, ubezpieczenia, dokumenty i formalnosci pojazdu.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 280,
            ],
            [
                'key' => 'mechanical_aspects_of_safety',
                'name' => 'Aspekty mechaniczne zwiazane z zachowaniem bezpieczenstwa na drodze',
                'display_name' => 'Mechanika i bezpieczenstwo pojazdu',
                'description' => 'Stan techniczny pojazdu, opony, plyny i uklady wspierajace bezpieczenstwo.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 290,
            ],
            [
                'key' => 'rescue_actions',
                'name' => 'Akcje ratunkowe',
                'display_name' => 'Akcje ratunkowe',
                'description' => 'Dzialania ratunkowe, pomoc poszkodowanym i zabezpieczenie miejsca zdarzenia.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 300,
            ],
            [
                'key' => 'driving_with_trailer',
                'name' => 'Kierowanie pojazdem z przyczepa',
                'display_name' => 'Jazda z przyczepa',
                'description' => 'Przepisy i praktyka kierowania pojazdem z przyczepa.',
                'bucket' => self::SPECIALIST_BUCKET,
                'sort_order' => 310,
            ],
        ];
    }

    /**
     * @return array<int, array{key:string,name:string,description:string,sort_order:int,is_active:bool}>
     */
    public function persistedDefinitions(): array
    {
        return array_map(
            function (array $definition): array {
                $persisted = Arr::only($definition, ['key', 'name', 'description', 'sort_order']);
                $persisted['is_active'] = true;

                return $persisted;
            },
            $this->definitions(),
        );
    }

    public function bucketLabelForKey(string $key): string
    {
        return (string) ($this->definitionsByKey()->get($key)['bucket'] ?? 'Grupy pytan');
    }

    public function displayLabelForKey(string $key): string
    {
        $definition = $this->definitionsByKey()->get($key);

        return (string) ($definition['display_name'] ?? $definition['name'] ?? $key);
    }

    public function learningSortOrderForKey(string $key, ?int $fallback = null): int
    {
        return (int) ($this->definitionsByKey()->get($key)['sort_order'] ?? $fallback ?? 9999);
    }

    /**
     * @return array{key:string,matched_by:string}
     */
    public function classify(Question|array $question): array
    {
        $payload = $question instanceof Question
            ? [
                'external_id' => $question->external_id,
                'prompt' => $question->prompt,
                'explanation' => $question->explanation,
                'option_a' => $question->option_a,
                'option_b' => $question->option_b,
                'option_c' => $question->option_c,
                'metadata' => $question->metadata ?? [],
            ]
            : $question;

        $zdamytoSectionClassification = $this->classifyZdamytoSection($payload);

        if ($zdamytoSectionClassification !== null) {
            return $zdamytoSectionClassification;
        }

        $mainMediaOriginal = Str::lower((string) Arr::get($payload, 'metadata.main_media_original', ''));
        $structureScope = Str::upper((string) Arr::get($payload, 'metadata.structure_scope', ''));
        $questionText = $this->normalizedText([
            (string) ($payload['prompt'] ?? ''),
            (string) ($payload['option_a'] ?? ''),
            (string) ($payload['option_b'] ?? ''),
            (string) ($payload['option_c'] ?? ''),
            $mainMediaOriginal,
        ]);
        $text = $this->normalizedText([
            (string) ($payload['prompt'] ?? ''),
            (string) ($payload['explanation'] ?? ''),
            (string) ($payload['option_a'] ?? ''),
            (string) ($payload['option_b'] ?? ''),
            (string) ($payload['option_c'] ?? ''),
            $mainMediaOriginal,
        ]);

        return match ($structureScope) {
            'SPECJALISTYCZNY' => $this->classifySpecialistTopic($text),
            'PODSTAWOWY' => $this->classifyBasicTopic($text, $mainMediaOriginal, true, $questionText),
            default => $this->classifyBasicTopic($text, $mainMediaOriginal, false, $questionText)
                ?? $this->classifySpecialistTopic($text, false)
                ?? ['key' => 'special_caution_exiting_and_securing_vehicle', 'matched_by' => 'fallback:unknown'],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{key:string,matched_by:string}|null
     */
    protected function classifyZdamytoSection(array $payload): ?array
    {
        $source = Str::lower((string) ($payload['source'] ?? Arr::get($payload, 'metadata.source_site', '')));

        if ($source !== 'zdamyto' && $source !== 'zdamyto.com') {
            return null;
        }

        $sectionSlugs = collect(Arr::wrap(Arr::get($payload, 'metadata.zdamyto_section_slugs', [])))
            ->prepend(Arr::get($payload, 'metadata.zdamyto_primary_section_slug'))
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): string => $this->normalizeZdamytoSectionSlug((string) $value))
            ->filter()
            ->values();

        foreach ($sectionSlugs as $slug) {
            $matchedKey = $this->zdamytoSectionTopicKey($slug);

            if ($matchedKey !== null) {
                return [
                    'key' => $matchedKey,
                    'matched_by' => 'zdamyto_section:'.$slug,
                ];
            }
        }

        return null;
    }

    protected function normalizeZdamytoSectionSlug(string $slug): string
    {
        $normalized = (string) Str::of($slug)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-');

        $normalized = preg_replace('/-(kat|dat)-[a-z0-9]+$/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/-cz[0-9]+$/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/-\d+$/', '', $normalized) ?? $normalized;

        return trim($normalized, '-');
    }

    protected function zdamytoSectionTopicKey(string $slug): ?string
    {
        return match (true) {
            $slug === 'znaki-ostrzegawcze' => 'warning_signs',
            in_array($slug, ['znaki-zakazu', 'znaki-nakazu', 'znaki-zakazu-nakazu'], true) => 'prohibition_and_mandatory_signs',
            in_array($slug, ['znaki-informacyjne', 'znaki-informacyjne-kierunku-i-miejscowosci-uzupelniajace'], true) => 'informational_direction_and_supplementary_signs',
            $slug === 'znaki-drogowe-poziome' => 'road_markings',
            $slug === 'sygnaly-swietlne-sygnaly-dawane-przez-kierujacego-ruchem' => 'traffic_lights_and_controller_signals',
            $slug === 'wlaczanie-sie-do-ruchu-skrzyzowania-rownorzedne' => 'joining_traffic_and_equal_intersections',
            $slug === 'skrzyzowania-ze-znakami-okreslajacymi-pierwszenstwo-przejazdu' => 'intersections_with_priority_signs',
            $slug === 'skrzyzowania-z-sygnalizacje-swietlna' => 'intersections_with_traffic_lights',
            $slug === 'skrzyzowania-lub-przejscia-dla-pieszych-z-kierujacych-ruchem-miejsca-przystankow-komunikacji-publicznej' => 'controlled_crossings_and_public_transport_stops',
            $slug === 'pozycja-pojazdu-na-drodze-wjazd-i-zjazd-ze-skrzyzowania-zatrzymanie-i-postoj' => 'road_position_entry_exit_stopping',
            $slug === 'zmiana-pasa-ruchu-zmiana-kierunku-jazdy' => 'lane_change_and_turning',
            $slug === 'wyprzedzanie' => 'overtaking',
            $slug === 'omijanie-wymijanie-cofanie' => 'passing_reversing',
            $slug === 'uzywanie-swiatel-zewnetrznych-i-sygnalow-pojazdu' => 'vehicle_lights_and_signals',
            $slug === 'znaczenie-zachowania-szczegolnej-ostrosnosci-w-stosunku-do-innych-uzytkownikow-drogi-wysiadanie-z-pojazdu-zabezpieczenie-pojazdu' => 'special_caution_exiting_and_securing_vehicle',
            $slug === 'zachowanie-wobec-pieszego-wobec-osoby-o-ograniczonej-mozliwosci-poruszania-sie' => 'behaviour_towards_pedestrians_and_reduced_mobility',
            $slug === 'zachowanie-wobec-rowerzysty-i-dzieci' => 'behaviour_towards_cyclists_and_children',
            $slug === 'zachowanie-na-przejazdach-kolejowych-i-tramwajowych' => 'rail_and_tram_crossings',
            $slug === 'ogolne-zasady-okreslajace-zachowanie-kierowcy-w-momencie-awarii-lub-wypadku-udzielanie-pierwszej-pomocy-przedmedycznej' => 'breakdown_accident_and_first_aid',
            $slug === 'spostrzeganie-ocena-sytuacji-i-podejmowanie-decyzji' => 'perception_decision_alcohol_fatigue',
            Str::startsWith($slug, 'dopuszczalne-predkosci-pojazdu-ograniczenia') => 'speed_limits',
            Str::startsWith($slug, 'wyposazenie-pojazdu-zwiazane-z-bezpieczenstwem-wykorzystanie-srodkow-ochronnych') => 'safety_equipment_and_restraints',
            Str::startsWith($slug, 'wyposazenie-pojazdu-zwiazane-z-bezpieczenstwem') => 'safety_equipment_and_restraints',
            Str::startsWith($slug, 'odstepy-i-hamowanie-pojazdu') => 'distances_and_braking',
            Str::startsWith($slug, 'czynniki-ryzyka-zwiazane-z-roznymi-warunkami-drogowymi') => 'risk_factors_conditions_and_weather',
            Str::startsWith($slug, 'rozne-pola-widzenia-kierowcow-widocznosc-kierowcow-motocykli') => 'driver_field_of_view',
            Str::startsWith($slug, 'technika-kierowania-pojazdem') => 'driving_technique',
            Str::startsWith($slug, 'czynniki-bezpieczenstwa-odnoszace-sie-do-pojazdu-ladunku-i-przewozonych-osob') => 'vehicle_load_and_passenger_safety',
            Str::startsWith($slug, 'obowiazki-wlasciciela-posiadacza-pojazdu-ubezpieczenia-wymagane-dokumenty') => 'owner_obligations_insurance_documents',
            Str::startsWith($slug, 'dokumenty-dotyczace-pojazdu-i-transportowe-wymagane') => 'owner_obligations_insurance_documents',
            Str::startsWith($slug, 'zasady-odnoszace-sie-do-rodzaju-transportu-czas-pracy-i-odpoczynku-kierowcy-wykorzystanie-tachografow') => 'owner_obligations_insurance_documents',
            Str::startsWith($slug, 'aspekty-mechaniczne-zwiazane-z-zachowaniem-bezpieczenstwa-na-drodze') => 'mechanical_aspects_of_safety',
            Str::startsWith($slug, 'akcje-ratunkowe') => 'rescue_actions',
            Str::startsWith($slug, 'postepowanie-w-sytuacjach-nadzwyczajnych-akcje-ratunkowe-po-wypadku') => 'rescue_actions',
            Str::startsWith($slug, 'kierowanie-pojazdem-z-przyczepa') => 'driving_with_trailer',
            default => null,
        };
    }

    /**
     * @return array{key:string,matched_by:string}|null
     */
    protected function classifyBasicTopic(string $text, string $mainMediaOriginal, bool $allowFallback = true, ?string $questionText = null): ?array
    {
        $promptText = $questionText ?? $text;
        $signTopic = $this->signTopicFromMedia($mainMediaOriginal);

        if ($this->containsAny($promptText, [
            'znak ostrzega',
            'znak ostrzega cie',
            'znak ostrzega ci',
            'moze wystepowac niebezpieczenstwo',
        ])) {
            return ['key' => 'warning_signs', 'matched_by' => 'keyword:warning_sign'];
        }

        if ($this->containsAny($promptText, [
            'znak zakazu',
            'znak nakazu',
        ])) {
            return ['key' => 'prohibition_and_mandatory_signs', 'matched_by' => 'keyword:sign_prohibition_mandatory'];
        }

        if ($this->containsAny($promptText, [
            'znak informacyj',
            'pod tym znakiem informacyjnym',
            'tabliczka wskazuje',
            'tabliczka umieszczona pod znakiem',
        ])) {
            return ['key' => 'informational_direction_and_supplementary_signs', 'matched_by' => 'keyword:informational_sign_explicit'];
        }

        if ($this->containsAny($promptText, [
            'odleglosc od znaku ostrzegawczego',
            'znaku ostrzegawczego do miejsca niebezpiecznego',
        ])) {
            return ['key' => 'warning_signs', 'matched_by' => 'keyword:warning_sign_distance'];
        }

        if ($this->containsAny($promptText, [
            'kierunkowskaz',
            'kierunkowskazu',
            'kierunkowskazem',
            'zasygnalizowac',
            'zasygnalizujesz',
            'sygnalizowac zamiar',
            'sygnalizowania zamiaru',
            'sygnalizujesz zamiar',
        ]) && $this->containsAny($text, [
            'zmiana pasa',
            'zmienic pas',
            'zmienic zajmowany pas',
            'zajmowany pas ruchu',
            'zajmowanego pasa ruchu',
            'zajmowanego przez ciebie pasa ruchu',
            'zajmowanego obecnie pasa ruchu',
            'na pas ruchu',
            'pasa ruchu',
            'lewy pas',
            'prawy pas',
            'wlasciwy pas ruchu',
            'lewym pasie ruchu',
            'prawym pasie ruchu',
            'z lewego pasa',
            'z prawego pasa',
            'z tego pasa',
            'pozostalych pasach ruchu',
            'dowolnym pasem',
            'pas przeznaczony',
            'zmieniajac pas',
            'po zmianie pasa',
            'kontynuowac jazde lewym pasem',
            'kontynuowac jazde srodkowym pasem',
            'srodkowym pasem ruchu',
            'tor jazdy',
            'wlasciwy tor',
        ])) {
            return ['key' => 'lane_change_and_turning', 'matched_by' => 'keyword:lane_change_signal_usage'];
        }

        if ($this->containsAny($promptText, [
            'sygnal dzwiekow',
            'sygnalu dzwiekow',
            'uzyc sygnalu',
            'uzycia sygnalu',
            'ostrzec pieszego',
            'sygnaly pojazdu',
            'ostrzegawczego sygnalu',
            'swiatla mijania',
            'swiatel mijania',
            'swiatlami mijania',
            'swiatla drogowe',
            'swiatel drogowych',
            'swiatlami drogowymi',
            'swiatla pozycyjne',
            'swiatel pozycyjnych',
            'swiatla do jazdy dziennej',
            'swiatlami do jazdy dziennej',
            'swiatel do jazdy dziennej',
            'swiatla przeciwmgielne',
            'swiatlami przeciwmgielnymi',
            'swiatel przeciwmgielnych',
            'swiatlami przeciwmglowymi',
            'swiatla przeciwmglowe',
            'swiatel przeciwmglowych',
        ])) {
            return ['key' => 'vehicle_lights_and_signals', 'matched_by' => 'keyword:vehicle_signal_usage'];
        }

        if ($this->containsAny($promptText, [
            'wskazania sygnalow swietlnych',
            'sygnaly dawane przez osoby kierujace ruchem',
            'polecenia i sygnaly dawane przez osoby kierujace ruchem',
            'polecenia i sygnaly dawane przez osoby kierujace',
        ])) {
            return ['key' => 'traffic_lights_and_controller_signals', 'matched_by' => 'keyword:signal_priority'];
        }

        if ($this->containsAny($promptText, [
            'wlaczanie sie do ruchu',
            'wlaczasz sie do ruchu',
            'skrzyzowanie rownorzedne',
            'skrzyzowaniu rownorzednym',
            'wyjezdzasz z posesji',
            'wyjezdzasz z drogi gruntowej',
            'z drogi gruntowej',
            'na droge twarda',
            'droge twardej',
            'na ktora wjezdzasz',
            'wlaczajacym sie do ruchu',
        ])) {
            return ['key' => 'joining_traffic_and_equal_intersections', 'matched_by' => 'keyword:joining_traffic'];
        }

        if ($this->containsAny($promptText, [
            'przejazd kolejow',
            'przejazdu kolejow',
            'przejazd tramwaj',
            'przejazdu tramwaj',
            'przed przejazdem',
            'na przejazd',
            'rogatk',
            'zapora',
            'szlaban',
            'zapory kolejowe',
            'torowisk',
            'torow',
            'wagonem tramwaju',
            'wagon tramwaju',
            'nadjezdza pociag',
            'zbliza sie pociag',
            'nadjedzie pociag',
            'nadjezdza tramwaj',
            'nadjedzie tramwaj',
        ])) {
            if ($this->containsAny($promptText, ['zatrzymac', 'zatrzymania', 'ostatnim wagonem'])) {
                return ['key' => 'road_position_entry_exit_stopping', 'matched_by' => 'keyword:tram_stop_position'];
            }

            return ['key' => 'rail_and_tram_crossings', 'matched_by' => 'keyword:rail_crossing'];
        }

        if ($this->containsAny($promptText, ['przystanek', 'przystanku', 'komunikacji publicznej', 'tramwaj ruszy', 'linia przystankowa', 'przystankowa'])) {
            return ['key' => 'controlled_crossings_and_public_transport_stops', 'matched_by' => 'keyword:public_transport_stop'];
        }

        if (
            $this->containsAny($promptText, [
                'widoczna linia wskazuje miejsce',
                'miejsce obowiazkowego zatrzymania',
                'wyznaczonym linia miejscu',
                'linia zlozona z prostokatow',
                'warunkowego zatrzymania pojazdu',
                'zoltych znakow poziomych',
            ])
            || (
                $this->containsAny($promptText, ['wyznaczonym miejscu'])
                && $this->containsAny($promptText, ['zatrzymac', 'zatrzymania', 'obowiazkowego zatrzymania'])
            )
        ) {
            return ['key' => 'road_markings', 'matched_by' => 'keyword:road_markings_stop_line'];
        }

        if (
            $this->containsAny($promptText, [
                'poziome znaki ostrzegawcze',
                'punktowych elementow odblaskowych',
                'elementy odblaskowe',
                'krawedz jezdni',
                'barwy czerwonej',
                'barwy bialej',
                'linia poprzeczna',
                'linia zatrzymania',
                'pas postojowy',
                'znak poziomy',
                'oznakowanie poziome',
                'linia przerywana',
                'linia ciagla',
                'biala przerywana linia',
                'wskazanym strzalka',
                'bialych strzalek',
                'strzalki kierunkowe',
            ])
            && ! $this->containsAny($promptText, [
                'zmiana pasa',
                'zmienic pas',
                'wjechac na pas ruchu',
                'zajmowany pas ruchu',
                'zajmowanego pasa ruchu',
                'zajmowanego przez ciebie pasa ruchu',
                'skrec',
                'zawrac',
                'zawrocic',
            ])
        ) {
            return ['key' => 'road_markings', 'matched_by' => 'keyword:road_markings_reflectors'];
        }

        $roadMarkingHint = $this->bestKeywordMatch($promptText, [
            'road_markings' => $this->basicKeywordRules()['road_markings'],
        ]);

        if ($roadMarkingHint !== null) {
            return $roadMarkingHint;
        }

        if (
            $this->containsAny($text, [
                'znak poziomy p-',
                'znaki poziome p-',
                'linia jednostronnie przekraczalna',
                'linia pojedyncza ciagla',
                'linia podwojna ciagla',
                'linia podwojna przerywana',
                'linia bezwzglednego zatrzymania',
                'linia warunkowego zatrzymania',
                'strzalka kierunkowa',
                'strzalki kierunkowe',
                'strzalka do skrecania',
                'powierzchnia wylaczona',
            ])
            && $this->containsAny($promptText, [
                'pas ruchu',
                'zajmowany pas',
                'zmienic pas',
                'na wprost',
                'skrecic',
                'zawrocic',
                'zawrac',
                'wjechac',
                'linia',
                'strzalka',
                'zatrzymac sie',
            ])
            && ! $this->containsAny($promptText, [
                'wjechac na pas ruchu',
                'z zajmowanego pasa ruchu',
                'zajmowanym pasem ruchu',
                'zajmujesz wlasciwy pas ruchu',
                'tym pasem ruchu wolno ci',
                'tym pasem ruchu masz prawo',
            ])
        ) {
            return ['key' => 'road_markings', 'matched_by' => 'keyword:road_markings_explanation'];
        }

        if ($this->containsAny($promptText, ['strefa zamieszkania', 'strefie zamieszkania'])) {
            return ['key' => 'informational_direction_and_supplementary_signs', 'matched_by' => 'keyword:residential_zone_sign'];
        }

        if (
            $signTopic !== null
            && $this->containsAny($promptText, [
                'ten znak',
                'widoczny znak',
                'pokazany znak',
                'przedstawiony znak',
                'znak ',
                'znaki ',
                'znakiem',
                'tabliczka',
                'drogowskaz',
            ])
            && ! $this->containsAny($promptText, [
                'znak poziomy',
                'linia ',
                'linie ',
                'oznakowanie poziome',
            ])
        ) {
            return [
                'key' => $signTopic,
                'matched_by' => 'media_sign_family',
            ];
        }

        if (
            $this->containsAny($promptText, [
                'skret',
                'skrec',
                'zawrac',
                'zawrocic',
            ])
            && $this->containsAny($promptText, [
                'wolno',
                'mozesz',
                'masz prawo',
                'dozwolone',
                'dozwolony',
            ])
            && $this->containsAny($promptText, [
                'sygnalizowania zamiaru',
                'sygnalizowac zamiar',
                'kierunkowskaz',
                'kierunkowskazu',
            ])
        ) {
            return ['key' => 'vehicle_lights_and_signals', 'matched_by' => 'keyword:turn_signal_permission'];
        }

        if ($this->containsAny($promptText, ['ostrzec innych uczestnikow', 'zazegnac niebezpieczenstwo', 'przedsiewziac inne srodki'])) {
            return ['key' => 'special_caution_exiting_and_securing_vehicle', 'matched_by' => 'keyword:warn_other_road_users'];
        }

        if ($this->containsAny($promptText, ['kierujacy ruchem', 'policjant'])) {
            if ($this->containsAny($promptText, ['przejscie dla pieszych', 'pieszy', 'pieszych'])) {
                return ['key' => 'controlled_crossings_and_public_transport_stops', 'matched_by' => 'keyword:controller_and_crossing'];
            }

            if ($this->containsAny($promptText, ['skrzyz'])) {
                return ['key' => 'traffic_lights_and_controller_signals', 'matched_by' => 'keyword:controller_on_intersection'];
            }

            return ['key' => 'traffic_lights_and_controller_signals', 'matched_by' => 'keyword:traffic_controller'];
        }

        if ($this->containsAny($promptText, [
            'sygnalu podawanego przez widoczna osobe',
            'sygnalu osoby kierujacej ruchem',
            'osoby kierujacej ruchem',
            'widoczna osoba',
            'zastosowac sie do sygnalu swietlnego',
            'polecenia lub sygnaly wydawane',
            'dawac uczestnikowi drogi polecenia lub sygnaly',
        ])) {
            return ['key' => 'traffic_lights_and_controller_signals', 'matched_by' => 'keyword:traffic_controller'];
        }

        if ($this->containsAny($promptText, ['przejscie dla pieszych', 'pieszy', 'pieszych', 'pieszymi', 'niepelnosprawn', 'ograniczonej mozliwosci poruszania', 'wtargnieciem pieszego', 'zachowanie pieszego'])) {
            return ['key' => 'behaviour_towards_pedestrians_and_reduced_mobility', 'matched_by' => 'keyword:pedestrians'];
        }

        if ($this->containsAny($promptText, ['sygnalizacja swietlna', 'sygnalizator', 'sygnalizatorem', 'sygnal swietl', 'zielone swiatlo', 'czerwone swiatlo', 'zolte swiatlo', 'sygnal zielony', 'sygnal czerwony', 'sygnal zolty', 'zapali sie sygnal', 'sygnalem bedzie', 'nadawany sygnal', 'widoczny sygnal']) && $this->containsAny($promptText, ['skrzyz'])) {
            return ['key' => 'intersections_with_traffic_lights', 'matched_by' => 'keyword:signalized_intersection'];
        }

        if ($this->containsAny($promptText, ['sygnalizacja swietlna', 'sygnalizator', 'sygnalizatorem', 'sygnal swietl', 'zielone swiatlo', 'czerwone swiatlo', 'zolte swiatlo', 'sygnal zielony', 'sygnal czerwony', 'sygnal zolty', 'zapali sie sygnal', 'sygnalem bedzie', 'nadawany sygnal', 'widoczny sygnal'])) {
            return ['key' => 'traffic_lights_and_controller_signals', 'matched_by' => 'keyword:traffic_signal'];
        }

        if (
            $this->containsAny($promptText, ['ustap pierwszenstwa', 'droga z pierwszenstwem', 'drogi z pierwszenstwem', 'znak stop', 'znakiem stop'])
            && $this->containsAny($promptText, ['skrzyz'])
            && ! $this->containsAny($promptText, ['pieszy', 'pieszych', 'pieszymi', 'rowerzyst'])
        ) {
            return ['key' => 'intersections_with_priority_signs', 'matched_by' => 'keyword:priority_intersection'];
        }

        if (
            $this->containsAny($promptText, ['pierwszenstw', 'ustap pierwszenstwa', 'z prawej strony', 'nadjezdzajac', 'pojazdowi szynowemu'])
            && $this->containsAny($promptText, ['skrzyz', 'skrec', 'zawrac'])
            && ! $this->containsAny($promptText, ['ustap pierwszenstwa', 'droga z pierwszenstwem', 'drogi z pierwszenstwem', 'znak stop', 'znakiem stop'])
            && ! $this->containsAny($promptText, ['pieszy', 'pieszych', 'pieszymi', 'rowerzyst'])
        ) {
            return ['key' => 'joining_traffic_and_equal_intersections', 'matched_by' => 'keyword:equal_intersection_priority'];
        }

        if ($this->containsAny($promptText, ['wyprzedzan', 'wyprzedzac', 'wyprzedzic', 'wyprzedzony', 'wyprzedzenie'])) {
            return ['key' => 'overtaking', 'matched_by' => 'keyword:overtaking'];
        }

        if ($this->containsAny($promptText, ['omijan', 'ominac', 'omijasz', 'wymijan', 'wymijac', 'cofani', 'cofac'])) {
            return ['key' => 'passing_reversing', 'matched_by' => 'keyword:passing_reversing'];
        }

        if ($this->containsAny($promptText, [
            'zmiana pasa',
            'zmienic pas',
            'zmienic zajmowany pas',
            'zajmowany pas ruchu',
            'zajmowanego pasa ruchu',
            'zajmowanego przez ciebie pasa ruchu',
            'na pas ruchu',
            'lewy pas',
            'prawy pas',
            'wlasciwy pas ruchu',
            'lewym pasie ruchu',
            'prawym pasie ruchu',
            'pozostalych pasach ruchu',
            'dowolnym pasem',
            'pas przeznaczony',
            'zmieniajac pas',
            'po zmianie pasa',
            'zmiane kierunku',
            'zmiana kierunku',
            'kontynuowac jazde lewym pasem',
            'kontynuowac jazde srodkowym pasem',
            'srodkowym pasem ruchu',
            'oddalic sie od prawej krawedzi jezdni',
        ])) {
            return ['key' => 'lane_change_and_turning', 'matched_by' => 'keyword:lane_change_turning'];
        }

        if (
            $this->containsAny($promptText, [
                'tor jazdy',
                'wlasciwy tor',
                'wlasciwy pas',
                'zajales wlasciwy pas',
                'zajac wlasciwy pas',
            ])
            && $this->containsAny($promptText, [
                'skret',
                'skrec',
                'zawrac',
                'zawrocic',
            ])
        ) {
            return ['key' => 'lane_change_and_turning', 'matched_by' => 'keyword:turning_trajectory'];
        }

        if (
            $this->containsAny($promptText, [
                'skret',
                'skrec',
                'zawrac',
                'zawrocic',
            ])
            && $this->containsAny($promptText, [
                'wolno',
                'mozesz',
                'masz prawo',
                'dozwolony',
                'dozwolone',
            ])
            && $this->containsAny($promptText, [
                'sygnalizacja swietlna',
                'sygnalizator',
                'sygnalizatorem',
                'sygnal swietl',
                'sygnalu swietlnego',
                'sygnale swietlnym',
                'zielone swiatlo',
                'czerwone swiatlo',
                'zolte swiatlo',
                'sygnal zielony',
                'sygnal czerwony',
                'sygnal zolty',
                'w czasie nadawania tego sygnalu',
                'zmianie sygnalu na zielony',
                'zmianie sygnalu na czerwony',
                'zmianie sygnalu na zolty',
                'zapali sie sygnal',
                'sygnalem bedzie',
                'nadawany sygnal',
                'widoczny sygnal',
            ])
            && $this->containsAny($promptText, ['skrzyz'])
        ) {
            return ['key' => 'intersections_with_traffic_lights', 'matched_by' => 'keyword:signalized_turn_permission'];
        }

        if (
            $this->containsAny($promptText, [
                'skret',
                'skrec',
                'zawrac',
                'zawrocic',
            ])
            && $this->containsAny($promptText, [
                'wolno',
                'mozesz',
                'masz prawo',
                'dozwolony',
                'dozwolone',
            ])
            && $this->containsAny($promptText, [
                'pas ruchu',
                'pasa ruchu',
                'z pasa',
                'pasem',
                'z lewego pasa',
                'z prawego pasa',
                'z tego pasa',
                'zajmowany',
                'zajmujesz',
                'zajales',
                'zajac',
                'obecnie pasa',
                'tor jazdy',
                'wlasciwy pas',
                'wlasciwy tor',
            ])
            && ! $this->containsAny($promptText, [
                'pierwszenstw',
                'ustap pierwszenstwa',
                'nadjezdzajac',
                'znak stop',
                'droga z pierwszenstwem',
                'drogi z pierwszenstwem',
                'pojazdowi szynowemu',
            ])
        ) {
            return ['key' => 'lane_change_and_turning', 'matched_by' => 'keyword:turn_permission'];
        }

        if (
            $this->containsAny($promptText, [
                'skret',
                'skrec',
                'zawrac',
                'zawrocic',
            ])
            && $this->containsAny($promptText, [
                'wolno',
                'mozesz',
                'masz prawo',
                'dozwolony',
                'dozwolone',
            ])
            && $this->containsAny($promptText, ['skrzyz'])
        ) {
            return ['key' => 'intersections_with_priority_signs', 'matched_by' => 'keyword:intersection_turn_permission'];
        }

        if ($this->containsAny($promptText, [
            'zatrzyman',
            'zatrzymac pojazd',
            'zatrzymac swoj pojazd',
            'postoj',
            'parkowan',
            'zaparkowac',
            'pozycja pojazdu',
            'wjazd',
            'zjazd',
            'poboczu',
            'zatoce',
            'pasie miedzy jezdniami',
            'pasie dzielacym',
            'linii zatrzymania',
        ])) {
            return ['key' => 'road_position_entry_exit_stopping', 'matched_by' => 'keyword:stopping_position'];
        }

        if (
            $this->containsAny($promptText, ['parking', 'przy prawej krawedzi jezdni'])
            && $this->containsAny($promptText, ['zjechac', 'skrecic', 'poruszac sie', 'wjechac'])
        ) {
            return ['key' => 'road_position_entry_exit_stopping', 'matched_by' => 'keyword:road_position_parking'];
        }

        if ($this->containsAny($promptText, [
            'autostrad',
            'droga ekspresowa',
            'droga dla rowerow',
            'droga jednokierunkowa',
            'pas prowadzacy do wyjazdu',
            'miejscowosc',
            'obszar zabudowany',
            'obszarze zabudowanym',
            'wyjazd z obszaru zabudowanego',
            'koniec obszaru zabudowanego',
            'tabliczka',
            'drogowskaz',
        ])) {
            return ['key' => 'informational_direction_and_supplementary_signs', 'matched_by' => 'keyword:informational_sign'];
        }

        if ($this->containsAny($promptText, [
            'wysiadanie',
            'wysiadasz',
            'opuszczasz pojazd',
            'zabezpieczenie pojazdu',
            'zabezpieczyc pojazd',
            'otwierajac drzwi',
            'otwarciem drzwi',
        ])) {
            return ['key' => 'special_caution_exiting_and_securing_vehicle', 'matched_by' => 'keyword:special_caution'];
        }

        if ($this->containsAny($promptText, ['rowerzyst', 'rowerow', 'dzieci', 'dziecko'])) {
            return ['key' => 'behaviour_towards_cyclists_and_children', 'matched_by' => 'keyword:cyclists_children'];
        }

        if ($this->containsAny($promptText, ['pierwsza pomoc', 'wypadk', 'kolizj', 'awari', 'krwotok', 'rana', 'resuscyt', 'poszkodowan', 'opatrunek', 'miejsce zdarzenia'])) {
            return ['key' => 'breakdown_accident_and_first_aid', 'matched_by' => 'keyword:accident_first_aid'];
        }

        if ($this->containsAny($promptText, [
            'alkohol',
            'lekow',
            'produktow leczniczych',
            'zmeczenie',
            'czas reakcji',
            'spostrzeganie',
            'podejmowanie decyzji',
            'amfetamin',
            'narkot',
            'srodk odurz',
            'agresywn',
            'ryzykown',
        ])) {
            return ['key' => 'perception_decision_alcohol_fatigue', 'matched_by' => 'keyword:perception_fatigue'];
        }

        $scoredMatch = $this->bestKeywordMatch($promptText, $this->basicKeywordRules());

        if ($scoredMatch !== null) {
            return $scoredMatch;
        }

        if ($this->containsAny($promptText, ['skrzyz'])) {
            return ['key' => 'joining_traffic_and_equal_intersections', 'matched_by' => 'keyword:generic_intersection'];
        }

        return $allowFallback
            ? ['key' => 'special_caution_exiting_and_securing_vehicle', 'matched_by' => 'fallback:basic']
            : null;
    }

    /**
     * @return array{key:string,matched_by:string}|null
     */
    protected function classifySpecialistTopic(string $text, bool $allowFallback = true): ?array
    {
        if ($this->containsAny($text, ['pierwsza pomoc', 'krwawien', 'krwotok', 'opatrunek', 'resuscyt', 'poszkodowan', 'akcj ratunk', 'ratunkow', 'numer alarmowy', 'numerem alarmowym', 'telefon 112', '112', 'sluzb ratowniczych', 'miejsce wypadku', 'zdarzeniu drogowym', 'zabitych lub rannych'])) {
            return ['key' => 'rescue_actions', 'matched_by' => 'keyword:rescue_actions'];
        }

        if ($this->containsAny($text, ['dopuszczalna predkosc', 'maksymalna dopuszczalna predkosc', 'maksymalna predkosc', 'ograniczenie predkosci', 'z jaka predkoscia', 'jaka predkoscia masz obowiazek'])) {
            return ['key' => 'speed_limits', 'matched_by' => 'keyword:speed_limits'];
        }

        if ($this->containsAny($text, ['odstep', 'droga hamowania', 'hamowania', 'hamowac', 'system abs', 'uklad abs', 'najkrotsza droga hamowania'])) {
            return ['key' => 'distances_and_braking', 'matched_by' => 'keyword:distances_braking'];
        }

        if ($this->containsAny($text, ['pole widzenia', 'kat widzenia', 'martwe pole', 'widocznosc w lusterkach', 'lusterkach', 'lusterko zewnetrzne'])) {
            return ['key' => 'driver_field_of_view', 'matched_by' => 'keyword:field_of_view'];
        }

        if ($this->containsAny($text, ['duszno', 'dreszcze', 'zdretwieja rece', 'szum w uszach', 'trzymajac go w reku', 'telefon w reku'])) {
            return ['key' => 'perception_decision_alcohol_fatigue', 'matched_by' => 'keyword:perception_fatigue'];
        }

        if ($this->containsAny($text, [
            'ubezpieczen',
            'dowod rejestracyjny',
            'wymagane dokumenty',
            'wlasciciela',
            'posiadacza pojazdu',
            'badanie techniczne',
            'badania technicznego',
            'jakiej kategorii prawo jazdy',
            'towary niebezpieczne',
            'tablice wskazujace',
            'oznaczenie pojazdu',
            'starosta',
            'zatrzyma prawo jazdy',
            'zatrzymania prawa jazdy',
            'inspektor transportu drogowego',
            'centralnej ewidencji pojazdow',
            'kontroli drogowej',
        ])) {
            return ['key' => 'owner_obligations_insurance_documents', 'matched_by' => 'keyword:owner_documents'];
        }

        if (
            $this->containsAny($text, [
                'przewozic',
                'przewozisz',
                'przewiezc',
                'przewieziesz',
                'przewozenie',
                'przewozonych osob',
                'dozwolonej liczbie',
                'liczbie przewozonych',
                'przewozic rowery',
                'rowery samochodem',
                'przewozu dziecka',
                'sluzacy do przewozu dziecka',
                'fotelik bezpieczenstwa',
                'urzadzenia przytrzymujace dla dzieci',
                'pasazer',
                'ile maksymalnie osob',
                'liczbe miejsc',
                'miejsc siedzacych',
                'na kolanach osoby doroslej',
                'na tylnym siedzeniu',
                'na przednim siedzeniu',
                'trzecie dziecko',
                'dwoje dzieci',
                'dziecko',
                'dzieci',
                'niemowlaka',
                'noworodka',
                'przedszkolaka',
                'mniej niz trzy lata',
                'ponizej 135 cm',
                'ponizej 150 cm',
                'wzroscie mniejszym niz 150 cm',
                'wzrostu mniejszym niz 150 cm',
                'ladunek',
                'ladunku',
                'bagaz',
                'ladunek sypki',
                'ladunek wystajacy',
                'choragiewka',
                'rzeczywista masa calkowita',
                'mocowales ladunek',
                'mocowales bagaz',
                'przyczepie ciagnietej',
                'przyczepy ciagnietej',
                'zespol pojazdow',
                'laczna dopuszczalna masa calkowita',
                'dopuszczalna masa calkowita',
                'dopuszczalna dlugosc zespolu pojazdow',
                'maksymalna dopuszczalna dlugosc',
                'dopuszczalna liczbe osob przewozonych',
                'liczbe miejsc wpisanej w dowodzie rejestracyjnym',
                'liczbe miejsc okreslona w dowodzie rejestracyjnym',
                'przewozenie osob w liczbie przekraczajacej',
                'przewozi o trzy osoby wiecej',
                'ktora osoba ma obowiazek korzystania z pasow bezpieczenstwa',
            ])
            && ! $this->containsAny($text, [
                'w prawidlowo ustawionym lusterku',
                'prawidlowo ustawionym lusterku',
                'lusterko wsteczne',
                'lusterko zewnetrzne',
                'lampki kontrolnych',
                'lampki kontrolne',
                'zainstalowac trzeciego fotelika',
                'czy apteczka doraznej pomocy jest obowiazkowym wyposazeniem',
            ])
            && ! (
                $this->containsAny($text, ['predkosc', 'km/h'])
                && $this->containsAny($text, ['przyczep', 'holow', 'zespol pojazdow'])
            )
        ) {
            return ['key' => 'vehicle_load_and_passenger_safety', 'matched_by' => 'keyword:transported_people'];
        }

        if ($this->containsAny($text, ['pasy bezpieczenstwa', 'pas bezpieczenstwa', 'pasow bezpieczenstwa', 'pasy sa zapiete', 'zapiac pas', 'zapinac pas', 'zapiete pasy', 'zaglowek', 'fotelik', 'poduszka powietrzna', 'gasnic', 'trojkat ostrzegawczy', 'trojkat odblaskowy', 'obowiazkowe wyposazenie', 'wyposazenie kazdego samochodu', 'urzadzen przytrzymujacych', 'urzadzeniach przytrzymujacych'])) {
            return ['key' => 'safety_equipment_and_restraints', 'matched_by' => 'keyword:safety_equipment'];
        }

        if ($this->containsAny($text, [
            'ladunek',
            'bagaz',
            'pasazer',
            'przewozonych osob',
            'przewozic',
            'przewozenie osob',
            'ile maksymalnie osob',
            'liczbe miejsc',
            'ladunek sypki',
            'ladunek wystajacy',
            'choragiewka',
            'rzeczywista masa calkowita',
            'mocowales ladunek',
            'mocowales bagaz',
            'przyczepie ciagnietej',
            'przyczepy ciagnietej',
        ])) {
            return ['key' => 'vehicle_load_and_passenger_safety', 'matched_by' => 'keyword:load_passengers'];
        }

        if ($this->containsAny($text, ['deszcz', 'ulewn', 'mgla', 'snieg', 'oblodz', 'pogody', 'pora dnia', 'pora nocy', 'koleina', 'mokra nawierzchnia', 'sliska nawierzchnia', 'warunkach drogowych', 'wpadniecia w poslizg', 'poslizg', 'przyczepnosc'])) {
            return ['key' => 'risk_factors_conditions_and_weather', 'matched_by' => 'keyword:conditions_weather'];
        }

        if ($this->containsAny($text, ['cisnienie w ogumieniu', 'opon', 'bieznik', 'oleju', 'plyn', 'asr', 'esp', 'hamulec', 'uklad kierowniczy', 'amortyzator', 'lampka kontrolna', 'lampki kontrolnych', 'akumulatora', 'przewodu hamulcowego', 'reflektorow', 'ksenonowa', 'usterek', 'swiatla pozycyjne', 'swiatla cofania', 'swiatel cofania', 'ustawione swiatla mijania'])) {
            return ['key' => 'mechanical_aspects_of_safety', 'matched_by' => 'keyword:mechanical_safety'];
        }

        if ($this->containsAny($text, ['technika kierowania', 'trzymac kierownice', 'sprzeglo', 'bieg', 'tor jazdy', 'przyspieszan'])) {
            return ['key' => 'driving_technique', 'matched_by' => 'keyword:driving_technique'];
        }

        $scoredMatch = $this->bestKeywordMatch($text, $this->specialistKeywordRules());

        if ($scoredMatch !== null) {
            return $scoredMatch;
        }

        return $allowFallback
            ? ['key' => 'driving_technique', 'matched_by' => 'fallback:specialist']
            : null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function basicKeywordRules(): array
    {
        return [
            'warning_signs' => [
                'znak ostrzegawcz',
                'ten znak ostrzega',
                'widoczny znak ostrzega',
                'widoczne znaki ostrzegaja',
                'jestes ostrzegany',
                'ostrzegany o zblizaniu',
                'ostrzegany o miejscu',
            ],
            'prohibition_and_mandatory_signs' => [
                'znak zakazu',
                'znak nakazu',
                'zakaz',
                'nakaz',
            ],
            'informational_direction_and_supplementary_signs' => [
                'znak informacyj',
                'tabliczka',
                'tablica',
                'drogowskaz',
                'miejscowosci',
            ],
            'road_markings' => [
                'znak poziomy',
                'oznaczenie poziome',
                'oznakowanie poziome',
                'linia ciagla',
                'linia przerywana',
                'strzalka kierunkowa',
                'powierzchnia wylaczona',
            ],
            'traffic_lights_and_controller_signals' => [
                'sygnal swietln',
                'sygnalizacj',
                'zielone swiatlo',
                'czerwone swiatlo',
                'kierujacy ruchem',
                'policjant',
            ],
            'joining_traffic_and_equal_intersections' => [
                'wlaczanie sie do ruchu',
                'skrzyzowanie rownorzedne',
                'skrzyzowaniu rownorzednym',
                'wyjezdzasz z posesji',
            ],
            'intersections_with_priority_signs' => [
                'droga z pierwszenstwem',
                'ustap pierwszenstwa',
                'pierwszenstw',
                'znak stop',
            ],
            'intersections_with_traffic_lights' => [
                'skrzyzowanie z sygnalizacja',
                'skrzyzowaniu z sygnalizacja',
                'na skrzyzowaniu z sygnalizacja',
            ],
            'controlled_crossings_and_public_transport_stops' => [
                'przystanek autobusowy',
                'przystanek tramwajowy',
                'komunikacji publicznej',
                'na przystanku',
            ],
            'road_position_entry_exit_stopping' => [
                'pozycja pojazdu',
                'zatrzymanie',
                'postoj',
                'parkowanie',
                'wjazd',
                'zjazd',
            ],
            'lane_change_and_turning' => [
                'zmiana pasa',
                'zmiane pasa',
                'na pas ruchu',
                'pasa ruchu',
                'lewy pas',
                'prawy pas',
                'z lewego pasa',
                'z prawego pasa',
                'zmiana kierunku',
                'zmiane kierunku',
            ],
            'overtaking' => [
                'wyprzedzan',
                'wyprzedzac',
                'wyprzedzic',
            ],
            'passing_reversing' => [
                'omijan',
                'wymijan',
                'cofani',
            ],
            'vehicle_lights_and_signals' => [
                'swiatla drogowe',
                'swiatla mijania',
                'swiatla przeciwmg',
                'swiatla pozycyjne',
                'sygnal dzwiekowy',
                'sygnal dzwiekow',
                'sygnaly pojazdu',
            ],
            'special_caution_exiting_and_securing_vehicle' => [
                'szczegolna ostroznosc',
                'wysiadanie',
                'wysiadasz',
                'zabezpieczenie pojazdu',
            ],
            'behaviour_towards_pedestrians_and_reduced_mobility' => [
                'pieszy',
                'pieszych',
                'przejscie dla pieszych',
                'osoba o ograniczonej mozliwosci poruszania',
                'niepelnosprawn',
            ],
            'behaviour_towards_cyclists_and_children' => [
                'rowerzyst',
                'rowerow',
                'dziecko',
                'dzieci',
            ],
            'rail_and_tram_crossings' => [
                'przejazd kolejow',
                'przejazd tramwaj',
                'rogatk',
                'torowisk',
            ],
            'breakdown_accident_and_first_aid' => [
                'pierwsza pomoc',
                'wypadk',
                'awari',
                'krwotok',
                'resuscyt',
                'opatrunek',
            ],
            'perception_decision_alcohol_fatigue' => [
                'alkohol',
                'zmeczenie',
                'czas reakcji',
                'spostrzeganie',
                'podejmowanie decyzji',
                'lekow',
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function specialistKeywordRules(): array
    {
        return [
            'speed_limits' => [
                'dopuszczalna predkosc',
                'maksymalna dopuszczalna predkosc',
                'maksymalna predkosc',
                'ograniczenie predkosci',
            ],
            'safety_equipment_and_restraints' => [
                'pasy bezpieczenstwa',
                'pasow bezpieczenstwa',
                'pasy sa zapiete',
                'zapiac pas',
                'zaglowek',
                'fotelik',
                'poduszka powietrzna',
                'gasnic',
                'trojkat ostrzegawczy',
            ],
            'distances_and_braking' => [
                'odstep',
                'droga hamowania',
                'hamowania',
                'uklad abs',
                'system abs',
            ],
            'risk_factors_conditions_and_weather' => [
                'deszcz',
                'mgla',
                'snieg',
                'oblodz',
                'warunkach drogowych',
                'mokra nawierzchnia',
                'pora dnia',
                'pora nocy',
            ],
            'driver_field_of_view' => [
                'pole widzenia',
                'martwe pole',
                'widocznosc',
                'lusterkach',
            ],
            'driving_technique' => [
                'technika kierowania',
                'kierownice',
                'sprzeglo',
                'tor jazdy',
                'przyspieszan',
            ],
            'vehicle_load_and_passenger_safety' => [
                'ladunek',
                'bagaz',
                'pasazer',
                'przewozonych osob',
                'dozwolonej liczbie',
                'liczbie przewozonych',
                'przewozenie osob',
                'liczbe miejsc',
                'ladunku',
                'ladunek sypki',
                'ladunek wystajacy',
                'choragiewka',
                'rzeczywista masa calkowita',
                'przyczepie ciagnietej',
                'przyczepy ciagnietej',
            ],
            'owner_obligations_insurance_documents' => [
                'ubezpieczen',
                'dokument',
                'dowod rejestracyjny',
                'wlasciciela',
                'posiadacza pojazdu',
                'badanie techniczne',
                'jakiej kategorii prawo jazdy',
            ],
            'mechanical_aspects_of_safety' => [
                'opon',
                'bieznik',
                'oleju',
                'plyn',
                'asr',
                'esp',
                'hamulec',
                'amortyzator',
            ],
            'rescue_actions' => [
                'pierwsza pomoc',
                'krwotok',
                'opatrunek',
                'resuscyt',
                'ratunk',
                'poszkodowan',
            ],
            'driving_with_trailer' => [
                'przyczepa',
                'przyczepy',
                'przyczepie',
                'przyczepa kempingowa',
                'ciagnietej przez',
                'zespol pojazdow',
            ],
        ];
    }

    /**
     * @param  array<string, array<int, string>>  $rules
     * @return array{key:string,matched_by:string}|null
     */
    protected function bestKeywordMatch(string $text, array $rules): ?array
    {
        $bestKey = null;
        $bestScore = 0;
        $bestKeyword = null;

        foreach ($rules as $key => $keywords) {
            $score = 0;
            $matchedKeyword = null;

            foreach ($keywords as $keyword) {
                if (! str_contains($text, $keyword)) {
                    continue;
                }

                $score++;
                $matchedKeyword ??= $keyword;
            }

            if ($score <= $bestScore) {
                continue;
            }

            $bestKey = $key;
            $bestScore = $score;
            $bestKeyword = $matchedKeyword;
        }

        if ($bestKey === null || $bestKeyword === null) {
            return null;
        }

        return [
            'key' => $bestKey,
            'matched_by' => sprintf('keyword:%s', $bestKeyword),
        ];
    }

    protected function signTopicFromMedia(string $mainMediaOriginal): ?string
    {
        if ($mainMediaOriginal === '') {
            return null;
        }

        if (! preg_match('/(^|[^a-z])([abcdt])\s*-?\s*\d+[a-z]{0,2}(?=[^a-z]|$|org|jpg|jpeg|png|webp|wmv)/i', $mainMediaOriginal, $matches)) {
            return null;
        }

        return match (strtoupper((string) ($matches[2] ?? ''))) {
            'A' => 'warning_signs',
            'B', 'C' => 'prohibition_and_mandatory_signs',
            'D', 'T' => 'informational_direction_and_supplementary_signs',
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $parts
     */
    protected function normalizedText(array $parts): string
    {
        return (string) Str::of(implode(' ', $parts))
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }

    /**
     * @param  array<int, string>  $needles
     */
    protected function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<string, array{key:string,name:string,display_name:string,description:string,bucket:string,sort_order:int}>
     */
    protected function definitionsByKey(): Collection
    {
        return collect($this->definitions())->keyBy('key');
    }
}
