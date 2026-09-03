<?php

namespace Database\Seeders;

use App\Models\ContentAuthor;
use App\Models\LegalAct;
use App\Models\LegalContentPage;
use App\Models\LegalSourceCheck;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use App\Support\TrafficSignAuthorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LegalTrustLayerMvpSeeder extends Seeder
{
    private const ROAD_TRAFFIC_ACT_URL = 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602';

    private const ROAD_TRAFFIC_ACT_ELI_URL = 'https://eli.gov.pl/eli/DU/1997/602/ogl';

    private const TRAFFIC_SIGNS_REGULATION_URL = 'https://eli.gov.pl/eli/DU/2019/2310/ogl';

    private const VEHICLE_TECHNICAL_REGULATION_URL = 'https://eli.gov.pl/eli/DU/2024/502/ogl';

    private const DRIVERS_ACT_URL = 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU20110300151';

    private const DRIVERS_ACT_ELI_URL = 'https://eli.gov.pl/eli/DU/2011/151/ogl';

    private const SOBRIETY_ACT_URL = 'https://eli.gov.pl/eli/DU/2023/2151/ogl';

    private const PETTY_OFFENCES_CODE_URL = 'https://eli.gov.pl/eli/DU/2023/2119/ogl';

    private const PENAL_CODE_URL = 'https://eli.gov.pl/eli/DU/2024/17/ogl';

    public function run(): void
    {
        DB::transaction(function (): void {
            $reviewedAt = Carbon::parse('2026-06-03 10:00:00', config('app.timezone'))->utc();
            $publishedAt = Carbon::parse('2026-06-03 10:00:00', config('app.timezone'))->utc();
            $june13ReviewedAt = Carbon::parse('2026-06-13 10:00:00', config('app.timezone'))->utc();
            $june13PublishedAt = Carbon::parse('2026-06-13 10:00:00', config('app.timezone'))->utc();
            $june15ReviewedAt = Carbon::parse('2026-06-15 10:00:00', config('app.timezone'))->utc();
            $june19ReviewedAt = Carbon::parse('2026-06-19 10:00:00', config('app.timezone'))->utc();
            $june19PublishedAt = Carbon::parse('2026-06-19 10:00:00', config('app.timezone'))->utc();
            $june20ReviewedAt = Carbon::parse('2026-06-20 10:00:00', config('app.timezone'))->utc();
            $june20PublishedAt = Carbon::parse('2026-06-20 10:00:00', config('app.timezone'))->utc();
            $june21ReviewedAt = Carbon::parse('2026-06-21 10:00:00', config('app.timezone'))->utc();
            $june21PublishedAt = Carbon::parse('2026-06-21 10:00:00', config('app.timezone'))->utc();
            $june22ReviewedAt = Carbon::parse('2026-06-22 10:00:00', config('app.timezone'))->utc();
            $june22PublishedAt = Carbon::parse('2026-06-22 10:00:00', config('app.timezone'))->utc();

            $author = TrafficSignAuthorProfile::upsert($publishedAt);
            TrafficSignAuthorProfile::moveLegacyTrafficSignsTo($author);

            $reviewer = ContentAuthor::query()->updateOrCreate(
                ['slug' => 'jakub-wisniewski'],
                [
                    'name' => 'Jakub Wiśniewski',
                    'job_title' => 'Ekspert ds. bezpieczeństwa ruchu drogowego i recenzent treści szkoleniowych, były egzaminator WORD, instruktor techniki jazdy',
                    'bio' => 'Ponad 12 lat w systemie szkolenia i egzaminowania kierowców. Jako egzaminator WORD przeprowadził blisko 9 tys. egzaminów praktycznych na kategorię B. Od 3 lat współpracuje z wydawnictwami i platformami e-learningowymi przy recenzji podręczników, testów i scenariuszy kursów - wychwytuje błędy merytoryczne, nieaktualne przepisy i nieprecyzyjne sformułowania.',
                    'photo_path' => 'images/authors/jakub-wisniewski.png',
                    'is_published' => true,
                    'published_at' => $publishedAt,
                ],
            );

            $roadTrafficAct = LegalAct::query()->updateOrCreate(
                ['slug' => 'prawo-o-ruchu-drogowym'],
                [
                    'title' => 'Ustawa z dnia 20 czerwca 1997 r. - Prawo o ruchu drogowym',
                    'short_title' => 'Prawo o ruchu drogowym',
                    'publisher' => 'Dziennik Ustaw',
                    'source_url' => self::ROAD_TRAFFIC_ACT_URL,
                    'eli_url' => self::ROAD_TRAFFIC_ACT_ELI_URL,
                    'isap_url' => self::ROAD_TRAFFIC_ACT_URL,
                    'last_checked_at' => $june22ReviewedAt,
                    'status' => LegalAct::STATUS_VERIFIED,
                ],
            );

            $trafficSignsRegulation = LegalAct::query()->updateOrCreate(
                ['slug' => 'znaki-i-sygnaly-drogowe'],
                [
                    'title' => 'Rozporządzenie Ministrów Infrastruktury oraz Spraw Wewnętrznych i Administracji z dnia 31 lipca 2002 r. w sprawie znaków i sygnałów drogowych',
                    'short_title' => 'Rozporządzenie w sprawie znaków i sygnałów drogowych',
                    'publisher' => 'Dziennik Ustaw',
                    'source_url' => self::TRAFFIC_SIGNS_REGULATION_URL,
                    'eli_url' => self::TRAFFIC_SIGNS_REGULATION_URL,
                    'isap_url' => null,
                    'last_checked_at' => $june21ReviewedAt,
                    'status' => LegalAct::STATUS_VERIFIED,
                ],
            );

            $vehicleTechnicalRegulation = LegalAct::query()->updateOrCreate(
                ['slug' => 'warunki-techniczne-pojazdow'],
                [
                    'title' => 'Rozporządzenie Ministra Infrastruktury z dnia 31 grudnia 2002 r. w sprawie warunków technicznych pojazdów oraz zakresu ich niezbędnego wyposażenia',
                    'short_title' => 'Rozporządzenie w sprawie warunków technicznych pojazdów',
                    'publisher' => 'Dziennik Ustaw',
                    'source_url' => self::VEHICLE_TECHNICAL_REGULATION_URL,
                    'eli_url' => self::VEHICLE_TECHNICAL_REGULATION_URL,
                    'isap_url' => null,
                    'last_checked_at' => $june21ReviewedAt,
                    'status' => LegalAct::STATUS_VERIFIED,
                ],
            );

            $driversAct = LegalAct::query()->updateOrCreate(
                ['slug' => 'ustawa-o-kierujacych-pojazdami'],
                [
                    'title' => 'Ustawa z dnia 5 stycznia 2011 r. o kierujących pojazdami',
                    'short_title' => 'Ustawa o kierujących pojazdami',
                    'publisher' => 'Dziennik Ustaw',
                    'source_url' => self::DRIVERS_ACT_URL,
                    'eli_url' => self::DRIVERS_ACT_ELI_URL,
                    'isap_url' => self::DRIVERS_ACT_URL,
                    'last_checked_at' => $june22ReviewedAt,
                    'status' => LegalAct::STATUS_VERIFIED,
                ],
            );

            $sobrietyAct = LegalAct::query()->updateOrCreate(
                ['slug' => 'wychowanie-w-trzezwosci'],
                [
                    'title' => 'Ustawa z dnia 26 października 1982 r. o wychowaniu w trzeźwości i przeciwdziałaniu alkoholizmowi',
                    'short_title' => 'Ustawa o wychowaniu w trzeźwości',
                    'publisher' => 'Dziennik Ustaw',
                    'source_url' => self::SOBRIETY_ACT_URL,
                    'eli_url' => self::SOBRIETY_ACT_URL,
                    'isap_url' => null,
                    'last_checked_at' => $june22ReviewedAt,
                    'status' => LegalAct::STATUS_VERIFIED,
                ],
            );

            $pettyOffencesCode = LegalAct::query()->updateOrCreate(
                ['slug' => 'kodeks-wykroczen'],
                [
                    'title' => 'Ustawa z dnia 20 maja 1971 r. - Kodeks wykroczeń',
                    'short_title' => 'Kodeks wykroczeń',
                    'publisher' => 'Dziennik Ustaw',
                    'source_url' => self::PETTY_OFFENCES_CODE_URL,
                    'eli_url' => self::PETTY_OFFENCES_CODE_URL,
                    'isap_url' => null,
                    'last_checked_at' => $june22ReviewedAt,
                    'status' => LegalAct::STATUS_VERIFIED,
                ],
            );

            $penalCode = LegalAct::query()->updateOrCreate(
                ['slug' => 'kodeks-karny'],
                [
                    'title' => 'Ustawa z dnia 6 czerwca 1997 r. - Kodeks karny',
                    'short_title' => 'Kodeks karny',
                    'publisher' => 'Dziennik Ustaw',
                    'source_url' => self::PENAL_CODE_URL,
                    'eli_url' => self::PENAL_CODE_URL,
                    'isap_url' => null,
                    'last_checked_at' => $june22ReviewedAt,
                    'status' => LegalAct::STATUS_VERIFIED,
                ],
            );

            $units = [
                ...$this->upsertLegalUnits($roadTrafficAct, $reviewedAt, $reviewer, $june13ReviewedAt, $june15ReviewedAt, $june19ReviewedAt, $june20ReviewedAt, $june21ReviewedAt, $june22ReviewedAt),
                ...$this->upsertTrafficSignsUnits($trafficSignsRegulation, $reviewer, $june22ReviewedAt),
                ...$this->upsertVehicleTechnicalUnits($vehicleTechnicalRegulation, $reviewer, $june21ReviewedAt),
                ...$this->upsertDriversActUnits($driversAct, $reviewer, $june22ReviewedAt),
                ...$this->upsertSobrietyActUnits($sobrietyAct, $reviewer, $june22ReviewedAt),
                ...$this->upsertPettyOffencesUnits($pettyOffencesCode, $reviewer, $june22ReviewedAt),
                ...$this->upsertPenalCodeUnits($penalCode, $reviewer, $june22ReviewedAt),
            ];
            $topics = $this->upsertTopics($publishedAt, $june13PublishedAt, $june19PublishedAt, $june20PublishedAt, $june21PublishedAt, $june22PublishedAt);
            $pages = $this->upsertPages($topics, $author, $reviewer, $publishedAt, $reviewedAt, $june13PublishedAt, $june13ReviewedAt, $june15ReviewedAt, $june19PublishedAt, $june19ReviewedAt, $june20PublishedAt, $june20ReviewedAt, $june21PublishedAt, $june21ReviewedAt, $june22PublishedAt, $june22ReviewedAt);

            $this->attachUnits($pages['zatrzymanie-i-postoj'], [$units['art-49'], $units['art-49-ust-1-pkt-5']]);
            $this->attachUnits($pages['tramwaje-i-przystanki'], [$units['art-26-ust-6']]);
            $this->attachUnits($pages['piesi-i-przejscia'], [$units['art-13'], $units['art-26']]);
            $this->attachUnits($pages['pierwszenstwo-przejazdu'], [$units['art-25-ust-1'], $units['par-36-ust-2']]);
            $this->attachUnits($pages['sygnalizacja-i-osoby-kierujace-ruchem'], [$units['art-5'], $units['par-95-ust-1-pkt-4'], $units['par-108-ust-2']]);
            $this->attachUnits($pages['predkosc-odstep-i-hamowanie'], [
                $units['art-19-ust-1'],
                $units['art-19-ust-2-pkt-2'],
                $units['art-19-ust-2-pkt-3'],
                $units['art-19-ust-3a'],
                $units['art-20-ust-2'],
                $units['art-20-ust-3-pkt-1-lit-a'],
            ]);
            $this->attachUnits($pages['zmiana-kierunku-i-pasa-ruchu'], [$units['art-22-ust-1'], $units['art-22-ust-2-pkt-2'], $units['art-22-ust-5']]);
            $this->attachUnits($pages['wlaczanie-sie-do-ruchu'], [$units['art-17-ust-1'], $units['art-17-ust-1-pkt-1'], $units['art-17-ust-2']]);
            $this->attachUnits($pages['autobus-wyjezdzajacy-z-przystanku'], [$units['art-18-ust-1'], $units['art-18-ust-2']]);
            $this->attachUnits($pages['wymijanie-omijanie-cofanie'], [$units['art-23-ust-1-pkt-1'], $units['art-23-ust-1-pkt-2'], $units['art-23-ust-1-pkt-3'], $units['art-23-ust-2']]);
            $this->attachUnits($pages['wyprzedzanie'], [$units['art-24-ust-1-pkt-2'], $units['art-24-ust-2'], $units['art-24-ust-6'], $units['art-24-ust-7-pkt-1'], $units['art-24-ust-7-pkt-3'], $units['art-24-ust-11']]);
            $this->attachUnits($pages['zawracanie'], [
                $units['art-22-ust-6-pkt-1'],
                $units['art-22-ust-6-pkt-2'],
                $units['art-22-ust-6-pkt-3'],
                $units['art-22-ust-6-pkt-4'],
                $units['art-25-ust-2'],
                $units['par-22-ust-1-2'],
                $units['par-22-ust-5'],
                $units['par-87-ust-1'],
                $units['par-87-ust-2'],
                $units['par-96-ust-2-3'],
                $units['par-97-ust-1'],
                $units['par-97-ust-3'],
            ]);
            $this->attachUnits($pages['sygnal-dzwiekowy'], [
                $units['art-29-ust-1'],
                $units['art-29-ust-2-pkt-1'],
                $units['art-29-ust-2-pkt-2'],
                $units['art-30-ust-1-pkt-1-lit-b'],
                $units['par-11-ust-1-pkt-6'],
            ]);
            $this->attachUnits($pages['zabezpieczenie-ladunku-i-wymiary'], [
                $units['art-61-ust-1'],
                $units['art-61-ust-2-pkt-2'],
                $units['art-61-ust-2-pkt-4'],
                $units['art-61-ust-3'],
                $units['art-61-ust-4'],
                $units['art-61-ust-5'],
                $units['art-61-ust-6-pkt-2'],
                $units['art-61-ust-6-pkt-3'],
                $units['art-61-ust-7'],
                $units['art-61-ust-9-pkt-3'],
                $units['art-61-ust-9-pkt-4'],
            ]);
            $this->attachUnits($pages['przyczepa-masa-wymiary-i-oswietlenie'], [
                $units['art-62-ust-1-pkt-1'],
                $units['art-62-ust-1-pkt-3'],
                $units['art-62-ust-4a-pkt-1'],
                $units['art-62-ust-4a-pkt-2'],
                $units['par-12-ust-1-pkt-11'],
            ]);
            $this->attachUnits($pages['wjazd-na-przejazd-kolejowy'], [
                $units['art-28-ust-1'],
                $units['art-28-ust-2'],
                $units['art-28-ust-3-pkt-1'],
                $units['art-28-ust-3-pkt-2'],
                $units['art-28-ust-3-pkt-3'],
                $units['par-21-ust-1-4'],
                $units['par-98-ust-5'],
            ]);
            $this->attachUnits($pages['holowanie-pojazdu'], [
                $units['art-31-ust-1-pkt-1'],
                $units['art-31-ust-1-pkt-2'],
                $units['art-31-ust-1-pkt-3'],
                $units['art-31-ust-1-pkt-4'],
                $units['art-31-ust-1-pkt-5'],
                $units['art-31-ust-1-pkt-6'],
                $units['art-31-ust-1-pkt-7'],
                $units['art-31-ust-2-pkt-1'],
                $units['art-31-ust-2-pkt-3'],
                $units['art-31-ust-2-pkt-4'],
                $units['art-31-ust-2-pkt-5'],
                $units['art-45-ust-1-pkt-2'],
            ]);
            $this->attachUnits($pages['pasy-bezpieczenstwa'], [
                $units['art-39-ust-1'],
                $units['art-39-ust-2-pkt-2'],
                $units['art-39-ust-2-pkt-10'],
            ]);
            $this->attachUnits($pages['swiatla-do-jazdy-dziennej-i-mijania'], [
                $units['art-51-ust-1'],
                $units['art-51-ust-2'],
                $units['art-30-ust-1-pkt-1-lit-a'],
            ]);
            $this->attachUnits($pages['rogatki-i-sygnaly-na-przejezdzie-kolejowym'], [
                $units['art-28-ust-1'],
                $units['art-28-ust-2'],
                $units['art-28-ust-3-pkt-1'],
                $units['par-98-ust-5'],
            ]);
            $this->attachUnits($pages['swiatla-przeciwmglowe'], [
                $units['art-30-ust-1-pkt-1-lit-a'],
                $units['art-30-ust-3'],
                $units['art-51-ust-5'],
            ]);
            $this->attachUnits($pages['foteliki-i-przewoz-dzieci'], [
                $units['art-39-ust-3'],
                $units['art-39-ust-3a'],
                $units['art-39-ust-3b'],
                $units['art-39-ust-3c'],
                $units['art-39-ust-4-pkt-4'],
                $units['art-45-ust-2-pkt-4'],
                $units['art-45-ust-2-pkt-5'],
                $units['art-45-ust-2-pkt-6'],
            ]);
            $this->attachUnits($pages['obowiazki-uczestnika-wypadku'], [
                $units['art-44-ust-1-pkt-1'],
                $units['art-44-ust-1-pkt-2'],
                $units['art-44-ust-1-pkt-3'],
                $units['art-44-ust-1-pkt-4'],
                $units['art-44-ust-2-pkt-1'],
                $units['art-44-ust-2-pkt-2'],
                $units['art-44-ust-2-pkt-3'],
                $units['art-44-ust-3'],
            ]);
            $this->attachUnits($pages['telefon-podczas-kierowania'], [
                $units['art-45-ust-2-pkt-1'],
            ]);
            $this->attachUnits($pages['swiatla-drogowe-i-oslepianie'], [
                $units['art-51-ust-3'],
                $units['art-51-ust-4-pkt-1'],
                $units['art-51-ust-4-pkt-2'],
            ]);
            $this->attachUnits($pages['gasnica-trojkat-i-obowiazkowe-wyposazenie'], [
                $units['par-11-ust-1-pkt-5c'],
                $units['par-11-ust-1-pkt-6'],
                $units['par-11-ust-1-pkt-13'],
                $units['par-11-ust-1-pkt-14'],
                $units['par-12-ust-3-pkt-11'],
                $units['par-40-ust-2-pkt-8'],
                $units['par-46-ust-1-pkt-2'],
            ]);
            $this->attachUnits($pages['opony-bieznik-i-cisnienie'], [
                $units['par-11-ust-5'],
                $units['par-11-ust-7-pkt-1'],
                $units['par-11-ust-7-pkt-4'],
            ]);
            $this->attachUnits($pages['dokumenty-podczas-kontroli-drogowej'], [
                $units['art-38-ust-1-pkt-1'],
                $units['art-38-ust-1-pkt-3a'],
                $units['art-38-ust-1-pkt-4a'],
                $units['art-38-ust-1-pkt-4b-lit-a'],
                $units['art-38-ust-1-pkt-4b-lit-b'],
                $units['art-38-ust-1-pkt-5'],
                $units['art-38-ust-2'],
                $units['art-38-ust-3'],
            ]);
            $this->attachUnits($pages['kategorie-prawa-jazdy-i-uprawnienia'], [
                $units['ukp-art-3-ust-1-pkt-1'],
                $units['ukp-art-6-ust-1-pkt-1-lit-a'],
                $units['ukp-art-6-ust-1-pkt-1-lit-b'],
                $units['ukp-art-6-ust-1-pkt-2-lit-b'],
                $units['ukp-art-6-ust-1-pkt-2-lit-c'],
                $units['ukp-art-6-ust-1-pkt-3-lit-b'],
                $units['ukp-art-6-ust-1-pkt-3-lit-c'],
                $units['ukp-art-6-ust-1-pkt-4-lit-a'],
                $units['ukp-art-6-ust-1-pkt-5-lit-a'],
                $units['ukp-art-6-ust-1-pkt-5-lit-b'],
                $units['ukp-art-6-ust-1-pkt-6-lit-a-b'],
                $units['ukp-art-6-ust-1-pkt-11-lit-a'],
                $units['ukp-art-6-ust-1-pkt-11-lit-b'],
                $units['ukp-art-6-ust-3-pkt-1'],
                $units['ukp-art-6-ust-3-pkt-4-lit-a'],
                $units['ukp-art-13-ust-1'],
                $units['ukp-art-18-ust-1'],
            ]);
            $this->attachUnits($pages['alkohol-i-srodki-dzialajace-podobnie'], [
                $units['art-45-ust-1-pkt-1'],
                $units['art-45-ust-1-pkt-2'],
                $units['sobriety-art-46-ust-2'],
                $units['sobriety-art-46-ust-3'],
                $units['kw-art-87-par-1'],
                $units['kk-art-178a-par-1'],
            ]);
            $this->attachUnits($pages['zielona-strzalka-warunkowa'], [
                $units['par-96-ust-1'],
                $units['par-96-ust-2'],
                $units['par-96-ust-3'],
            ]);
            $this->attachUnits($pages['pojazd-uprzywilejowany'], [
                $units['art-2-pkt-38'],
                $units['art-9-ust-1'],
                $units['art-9-ust-2'],
                $units['art-9-ust-2-pkt-1'],
                $units['art-9-ust-2-pkt-2'],
                $units['art-9-ust-3-4'],
                $units['art-24-ust-11'],
                $units['par-58-ust-3'],
            ]);
            $this->attachUnits($pages['sygnal-zolty-i-zolty-migajacy'], [
                $units['par-95-ust-1-pkt-2'],
                $units['par-95-ust-1-pkt-4'],
                $units['par-98-ust-6'],
            ]);

            $this->deleteQuestionReferences(['99', '10314'], [$units['art-26']], $topics['tramwaje-i-przystanki']);
            $this->deleteQuestionReferences(['10249', '10369', '10474'], [$units['art-26']], $topics['piesi-i-przejscia']);
            $this->deleteQuestionReferences(
                ['13562', '13781', '13782', '4170', '7237', '13035', '2484', '3966'],
                [$units['art-19'], $units['art-20']],
                $topics['predkosc-odstep-i-hamowanie'],
            );
            $this->deleteQuestionReferences(['7403', '10208', '7640', '9541', '8979', '10053', '7398', '7777', '10937', '7012', '10939', '12779'], [$units['art-24']], $topics['wyprzedzanie']);
            $this->deleteQuestionReferences(['3154', '10966', '3155', '3157', '13540', '2501', '13773', '6206', '6203', '6208'], [$units['art-23']], $topics['wymijanie-omijanie-cofanie']);
            $this->deleteQuestionReferences(['7219', '11089', '11090', '11500', '4488', '2568', '4155'], [$units['art-22']], $topics['zmiana-kierunku-i-pasa-ruchu']);
            $this->deleteQuestionReferences(['2287', '6084', '6095', '7336', '1142', '1338', '10847'], [$units['art-17']], $topics['wlaczanie-sie-do-ruchu']);
            $this->deleteQuestionReferences(['10154', '10247'], [$units['art-25']], $topics['pierwszenstwo-przejazdu']);
            $this->deleteQuestionReferences(['10107', '469'], [$units['art-5']], $topics['sygnalizacja-i-osoby-kierujace-ruchem']);
            $this->deleteQuestionReferences(['10237'], [$units['art-49']], $topics['zatrzymanie-i-postoj']);

            $this->attachQuestionReferences([
                '99' => [
                    'page' => $pages['tramwaje-i-przystanki'],
                    'unit' => $units['art-26-ust-6'],
                    'topic' => $topics['tramwaje-i-przystanki'],
                    'note' => 'To pytanie sprawdza obowiązek zatrzymania pojazdu przy oznaczonym przystanku tramwajowym bez wysepki. Art. 26 ust. 6 wymaga zatrzymania na czas potrzebny, aby pasażerowie mogli swobodnie dojść do tramwaju albo na drogę dla pieszych.',
                ],
                '10314' => [
                    'page' => $pages['tramwaje-i-przystanki'],
                    'unit' => $units['art-26-ust-6'],
                    'topic' => $topics['tramwaje-i-przystanki'],
                    'note' => 'To pytanie dotyczy tramwaju stojącego na przystanku bez wysepki dla pasażerów. Art. 26 ust. 6 nakazuje zatrzymać pojazd w miejscu i na czas potrzebny do bezpiecznego opuszczenia tramwaju.',
                ],
            ], $reviewer, $june15ReviewedAt);

            $this->attachQuestionReferences([
                '10249' => [
                    'page' => $pages['piesi-i-przejscia'],
                    'unit' => $units['art-26-ust-1'],
                    'topic' => $topics['piesi-i-przejscia'],
                    'note' => 'To pytanie sprawdza obowiązek ustąpienia pieszemu wchodzącemu na oznakowane przejście. Art. 26 ust. 1 nakazuje kierującemu zbliżającemu się do przejścia zachować szczególną ostrożność, zmniejszyć prędkość i ustąpić pieszemu znajdującemu się na przejściu albo na nie wchodzącemu.',
                ],
                '10369' => [
                    'page' => $pages['piesi-i-przejscia'],
                    'unit' => $units['art-26-ust-4'],
                    'topic' => $topics['piesi-i-przejscia'],
                    'note' => 'To pytanie dotyczy wjazdu do bramy przez drogę dla pieszych. Art. 26 ust. 4 wymaga, aby kierujący przejeżdżający przez taką część drogi jechał powoli i ustąpił pierwszeństwa pieszemu.',
                ],
                '10474' => [
                    'page' => $pages['piesi-i-przejscia'],
                    'unit' => $units['art-26-ust-1'],
                    'topic' => $topics['piesi-i-przejscia'],
                    'note' => 'To pytanie sprawdza obowiązek szczególnej ostrożności już podczas zbliżania się do oznakowanego przejścia, nawet gdy nie widać pieszego. Art. 26 ust. 1 wiąże ten obowiązek z samym dojazdem do przejścia i wymaga także zmniejszenia prędkości.',
                ],
            ], $reviewer, $june19ReviewedAt);

            $this->attachQuestionReferences([
                '10154' => [
                    'page' => $pages['pierwszenstwo-przejazdu'],
                    'unit' => $units['par-36-ust-2'],
                    'topic' => $topics['pierwszenstwo-przejazdu'],
                    'note' => 'To pytanie pokazuje wjazd na skrzyżowanie oznaczone znakami A-7 i C-12. Zgodnie z § 36 ust. 2 pierwszeństwo ma kierujący znajdujący się już na skrzyżowaniu, a nie pojazd wjeżdżający na nie z prawej strony.',
                ],
                '10247' => [
                    'page' => $pages['pierwszenstwo-przejazdu'],
                    'unit' => $units['art-25-ust-1'],
                    'topic' => $topics['pierwszenstwo-przejazdu'],
                    'note' => 'To pytanie dotyczy skrętu w lewo na skrzyżowaniu. Art. 25 ust. 1 wymaga ustąpienia pojazdowi jadącemu z kierunku przeciwnego na wprost albo skręcającemu w prawo.',
                ],
                '10107' => [
                    'page' => $pages['sygnalizacja-i-osoby-kierujace-ruchem'],
                    'unit' => $units['par-108-ust-2'],
                    'topic' => $topics['sygnalizacja-i-osoby-kierujace-ruchem'],
                    'note' => 'Na ilustracji osoba kierująca ruchem jest zwrócona bokiem do nadjeżdżającego pojazdu. § 108 ust. 2 oznacza taką postawę jako zezwolenie na wjazd na skrzyżowanie lub odcinek drogi za tą osobą.',
                ],
                '469' => [
                    'page' => $pages['sygnalizacja-i-osoby-kierujace-ruchem'],
                    'unit' => $units['par-95-ust-1-pkt-4'],
                    'topic' => $topics['sygnalizacja-i-osoby-kierujace-ruchem'],
                    'note' => 'To pytanie pokazuje jednocześnie sygnał czerwony i żółty. § 95 ust. 1 pkt 4 nadal zakazuje wjazdu za sygnalizator i jedynie zapowiada, że za chwilę pojawi się sygnał zielony.',
                ],
                '10237' => [
                    'page' => $pages['zatrzymanie-i-postoj'],
                    'unit' => $units['art-49-ust-1-pkt-5'],
                    'topic' => $topics['zatrzymanie-i-postoj'],
                    'note' => 'To pytanie dotyczy zatrzymania przy ciągłej linii wyznaczającej krawędź jezdni. Art. 49 ust. 1 pkt 5 zabrania zatrzymania obok takiej linii zarówno na jezdni, jak i na poboczu.',
                ],
            ], $reviewer, $june20ReviewedAt);

            $this->attachQuestionReferences([
                '13562' => [
                    'page' => $pages['predkosc-odstep-i-hamowanie'],
                    'unit' => $units['art-19-ust-1'],
                    'topic' => $topics['predkosc-odstep-i-hamowanie'],
                    'note' => 'To pytanie sprawdza obowiązek dostosowania prędkości do widoczności drogi. Art. 19 ust. 1 wymaga uwzględnienia widoczności i innych warunków ruchu; nie oznacza to jednak prawa do przekroczenia prędkości dopuszczalnej.',
                ],
                '13781' => [
                    'page' => $pages['predkosc-odstep-i-hamowanie'],
                    'unit' => $units['art-19-ust-3a'],
                    'topic' => $topics['predkosc-odstep-i-hamowanie'],
                    'note' => 'To pytanie pokazuje znak drogi ekspresowej i sprawdza, czy minimalny odstęp zawsze wynosi 100 m. Art. 19 ust. 3a uzależnia go od prędkości: w metrach ma wynosić co najmniej połowę jej wartości wyrażonej w km/h.',
                ],
                '13782' => [
                    'page' => $pages['predkosc-odstep-i-hamowanie'],
                    'unit' => $units['art-19-ust-3a'],
                    'topic' => $topics['predkosc-odstep-i-hamowanie'],
                    'note' => 'To pytanie pokazuje znak autostrady i sprawdza twierdzenie o stałym odstępie 100 m. Z art. 19 ust. 3a wynika reguła połowy prędkości, dlatego wymagany odstęp zależy od aktualnej prędkości pojazdu.',
                ],
                '4170' => [
                    'page' => $pages['predkosc-odstep-i-hamowanie'],
                    'unit' => $units['art-20-ust-2'],
                    'topic' => $topics['predkosc-odstep-i-hamowanie'],
                    'note' => 'To pytanie sprawdza podstawowy limit w strefie zamieszkania. Art. 20 ust. 2 ustala prędkość dopuszczalną pojazdu lub zespołu pojazdów na 20 km/h.',
                ],
                '7237' => [
                    'page' => $pages['predkosc-odstep-i-hamowanie'],
                    'unit' => $units['art-20-ust-2'],
                    'topic' => $topics['predkosc-odstep-i-hamowanie'],
                    'note' => 'Na obrazie widoczny jest znak strefy zamieszkania, a pytanie wymaga wskazania prędkości niedozwolonej. Art. 20 ust. 2 ustala limit 20 km/h, dlatego jazda 25 km/h przekracza prędkość dopuszczalną.',
                ],
                '13035' => [
                    'page' => $pages['predkosc-odstep-i-hamowanie'],
                    'unit' => $units['art-20-ust-2'],
                    'topic' => $topics['predkosc-odstep-i-hamowanie'],
                    'note' => 'To pytanie dotyczy pojazdu silnikowego z przyczepą w strefie zamieszkania. Art. 20 ust. 2 obejmuje pojazd oraz zespół pojazdów, więc także w tym przypadku limit wynosi 20 km/h, a nie 30 km/h.',
                ],
                '2484' => [
                    'page' => $pages['predkosc-odstep-i-hamowanie'],
                    'unit' => $units['art-20-ust-3-pkt-1-lit-a'],
                    'topic' => $topics['predkosc-odstep-i-hamowanie'],
                    'note' => 'To pytanie sprawdza limit samochodu osobowego na autostradzie, a nie odrębny limit dla konkretnego pasa. Art. 20 ust. 3 pkt 1 lit. a ustala 140 km/h na autostradzie; wybór pasa nadal podlega osobnym zasadom ruchu prawostronnego.',
                ],
                '3966' => [
                    'page' => $pages['predkosc-odstep-i-hamowanie'],
                    'unit' => $units['art-19-ust-2-pkt-3'],
                    'topic' => $topics['predkosc-odstep-i-hamowanie'],
                    'note' => 'To pytanie sprawdza, od czego zależy bezpieczny odstęp od poprzedzającego pojazdu. Art. 19 ust. 2 pkt 3 wymaga odstępu pozwalającego uniknąć zderzenia przy hamowaniu lub zatrzymaniu, dlatego prędkość pojazdów ma bezpośrednie znaczenie.',
                ],
            ], $reviewer, $june20ReviewedAt);

            $this->attachQuestionReferences([
                '7219' => [
                    'page' => $pages['zmiana-kierunku-i-pasa-ruchu'],
                    'unit' => $units['art-22-ust-5'],
                    'topic' => $topics['zmiana-kierunku-i-pasa-ruchu'],
                    'note' => 'To pytanie sprawdza sygnalizowanie planowanej zmiany pasa ruchu. Art. 22 ust. 5 wymaga włączenia kierunkowskazu zawczasu i wyraźnie, zanim kierujący rozpocznie manewr.',
                ],
                '11089' => [
                    'page' => $pages['zmiana-kierunku-i-pasa-ruchu'],
                    'unit' => $units['art-22-ust-1'],
                    'topic' => $topics['zmiana-kierunku-i-pasa-ruchu'],
                    'note' => 'To pytanie dotyczy poziomu ostrożności przy zmianie pasa ruchu. Art. 22 ust. 1 pozwala wykonać ten manewr tylko z zachowaniem szczególnej ostrożności.',
                ],
                '11090' => [
                    'page' => $pages['zmiana-kierunku-i-pasa-ruchu'],
                    'unit' => $units['art-22-ust-5'],
                    'topic' => $topics['zmiana-kierunku-i-pasa-ruchu'],
                    'note' => 'To pytanie sprawdza moment wyłączenia kierunkowskazu. Art. 22 ust. 5 nakazuje zaprzestać sygnalizowania niezwłocznie po wykonaniu zmiany kierunku jazdy lub pasa ruchu.',
                ],
                '11500' => [
                    'page' => $pages['zmiana-kierunku-i-pasa-ruchu'],
                    'unit' => $units['art-22-ust-2-pkt-2'],
                    'topic' => $topics['zmiana-kierunku-i-pasa-ruchu'],
                    'note' => 'To pytanie pokazuje przygotowanie do skrętu w lewo z jezdni o kilku pasach. Art. 22 ust. 2 pkt 2 wymaga odpowiednio wczesnego ustawienia pojazdu po stronie właściwej dla planowanego skrętu.',
                ],
                '4488' => [
                    'page' => $pages['zmiana-kierunku-i-pasa-ruchu'],
                    'unit' => $units['art-22-ust-5'],
                    'topic' => $topics['zmiana-kierunku-i-pasa-ruchu'],
                    'note' => 'To pytanie sprawdza ogólny obowiązek użycia kierunkowskazu. Art. 22 ust. 5 obejmuje zarówno zamiar zmiany kierunku jazdy, jak i zamiar zmiany zajmowanego pasa ruchu.',
                ],
                '2568' => [
                    'page' => $pages['zmiana-kierunku-i-pasa-ruchu'],
                    'unit' => $units['art-22-ust-5'],
                    'topic' => $topics['zmiana-kierunku-i-pasa-ruchu'],
                    'note' => 'To pytanie dotyczy kierunkowskazu po zakończeniu skrętu. Art. 22 ust. 5 wymaga jego niezwłocznego wyłączenia, gdy manewr został już wykonany.',
                ],
                '4155' => [
                    'page' => $pages['zmiana-kierunku-i-pasa-ruchu'],
                    'unit' => $units['art-22-ust-1'],
                    'topic' => $topics['zmiana-kierunku-i-pasa-ruchu'],
                    'note' => 'To pytanie pokazuje przygotowanie do zjazdu z autostrady przez zmianę pasa. Art. 22 ust. 1 wymaga wykonania tej zmiany ze szczególną ostrożnością po ocenie sytuacji na sąsiednim pasie.',
                ],
            ], $reviewer, $june20ReviewedAt);

            $this->attachQuestionReferences([
                '2287' => [
                    'page' => $pages['wlaczanie-sie-do-ruchu'],
                    'unit' => $units['art-17-ust-2'],
                    'topic' => $topics['wlaczanie-sie-do-ruchu'],
                    'note' => 'To pytanie dotyczy sposobu wyjazdu z posesji na drogę. Art. 17 ust. 2 wymaga od włączającego się do ruchu szczególnej ostrożności i ustąpienia pierwszeństwa innym uczestnikom.',
                ],
                '6084' => [
                    'page' => $pages['wlaczanie-sie-do-ruchu'],
                    'unit' => $units['art-17-ust-1-pkt-1'],
                    'topic' => $topics['wlaczanie-sie-do-ruchu'],
                    'note' => 'To pytanie sprawdza samą kwalifikację wyjazdu ze strefy zamieszkania. Art. 17 ust. 1 pkt 1 wprost zalicza taki wyjazd do włączania się do ruchu.',
                ],
                '6095' => [
                    'page' => $pages['wlaczanie-sie-do-ruchu'],
                    'unit' => $units['art-17-ust-2'],
                    'topic' => $topics['wlaczanie-sie-do-ruchu'],
                    'note' => 'To pytanie dotyczy wyjazdu z parkingu na jezdnię. Art. 17 ust. 2 nakazuje kierującemu włączającemu się do ruchu ustąpić pierwszeństwa pojazdom i innym uczestnikom ruchu.',
                ],
                '7336' => [
                    'page' => $pages['wlaczanie-sie-do-ruchu'],
                    'unit' => $units['art-17-ust-1-pkt-1'],
                    'topic' => $topics['wlaczanie-sie-do-ruchu'],
                    'note' => 'To pytanie pokazuje wjazd na drogę z miejsca objętego katalogiem art. 17 ust. 1 pkt 1. Taki wyjazd jest włączaniem się do ruchu, a nie zwykłym przejazdem przez skrzyżowanie.',
                ],
                '1142' => [
                    'page' => $pages['wlaczanie-sie-do-ruchu'],
                    'unit' => $units['art-17-ust-1'],
                    'topic' => $topics['wlaczanie-sie-do-ruchu'],
                    'note' => 'To pytanie sprawdza ruszanie po postoju niezwiązanym z warunkami lub przepisami ruchu. Art. 17 ust. 1 kwalifikuje takie rozpoczęcie jazdy jako włączanie się do ruchu.',
                ],
                '1338' => [
                    'page' => $pages['wlaczanie-sie-do-ruchu'],
                    'unit' => $units['art-17-ust-2'],
                    'topic' => $topics['wlaczanie-sie-do-ruchu'],
                    'note' => 'To pytanie dotyczy ruszania po postoju w celu zabrania pasażera. Art. 17 ust. 2 oznacza, że kierujący włączający się do ruchu nie ma pierwszeństwa i musi ustąpić innym uczestnikom.',
                ],
                '10847' => [
                    'page' => $pages['wlaczanie-sie-do-ruchu'],
                    'unit' => $units['art-17-ust-1'],
                    'topic' => $topics['wlaczanie-sie-do-ruchu'],
                    'note' => 'To pytanie odróżnia ruszanie po sygnale zielonym od włączania się do ruchu. Art. 17 ust. 1 nie obejmuje zatrzymania wynikającego z przepisów lub warunków ruchu, więc ruszenie spod sygnalizatora nie jest nowym włączeniem się do ruchu.',
                ],
            ], $reviewer, $june20ReviewedAt);

            $this->attachQuestionReferences([
                '1133' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'To pytanie sprawdza obowiązek zmniejszenia prędkości przy oznaczonym przystanku autobusowym na obszarze zabudowanym. Art. 18 ust. 1 wymaga umożliwienia autobusowi włączenia się do ruchu, gdy jego kierujący sygnalizuje taki zamiar.',
                ],
                '4260' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'To pytanie dotyczy umożliwienia autobusowi włączenia się do ruchu z oznaczonego przystanku na obszarze zabudowanym. Obowiązek powstaje, gdy autobus kierunkowskazem sygnalizuje zmianę pasa albo wjazd z zatoki na jezdnię.',
                ],
                '7379' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'To pytanie sprawdza zachowanie wobec autobusu wyjeżdżającego z zatoki przystankowej na jezdnię w obszarze zabudowanym. Art. 18 ust. 1 nakazuje zwolnić, a w razie potrzeby zatrzymać się, aby umożliwić ten manewr.',
                ],
                '11046' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'To pytanie sprawdza szczególny obowiązek kierującego zbliżającego się do oznaczonego przystanku autobusowego na obszarze zabudowanym. Trzeba umożliwić autobusowi sygnalizowane włączenie się do ruchu.',
                ],
                '11055' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'Pytanie ocenia prawidłowość zwolnienia i pozostawienia autobusowi miejsca na włączenie się do ruchu w obszarze zabudowanym. Takie zachowanie realizuje obowiązek z art. 18 ust. 1.',
                ],
                '11120' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'To pytanie wprost sprawdza sposób wykonania obowiązku z art. 18 ust. 1: należy zmniejszyć prędkość, a gdy wymaga tego sytuacja, zatrzymać się, aby umożliwić autobusowi sygnalizowany wyjazd z przystanku.',
                ],
                '11122' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'To pytanie dotyczy obowiązku umożliwienia autobusowi włączenia się do ruchu przy oznaczonym przystanku w obszarze zabudowanym. Kluczowe jest sygnalizowanie manewru kierunkowskazem.',
                ],
                '11123' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'To pytanie sprawdza granicę zastosowania art. 18 ust. 1. Szczególny obowiązek umożliwienia autobusowi wyjazdu z oznaczonego przystanku dotyczy obszaru zabudowanego, dlatego poza nim nie wynika z tego przepisu.',
                ],
                '11124' => [
                    'page' => $pages['autobus-wyjezdzajacy-z-przystanku'],
                    'unit' => $units['art-18-ust-1'],
                    'topic' => $topics['autobus-wyjezdzajacy-z-przystanku'],
                    'note' => 'Pytanie sprawdza, czy kierujący prawidłowo rozpoznał obowiązek umożliwienia autobusowi sygnalizowanego wyjazdu z oznaczonego przystanku na obszarze zabudowanym.',
                ],
            ], $reviewer, $june19ReviewedAt);

            $this->attachQuestionReferences([
                '3154' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-1-pkt-1'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie dotyczy odstępu przy wymijaniu pojazdu jadącego z przeciwka. Art. 23 ust. 1 pkt 1 wymaga odstępu bezpiecznego dla warunków drogi, a nie jednej stałej wartości w metrach.',
                ],
                '10966' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-1-pkt-1'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie sprawdza reakcję przy zbyt małej przestrzeni do wymijania. Art. 23 ust. 1 pkt 1 wymaga w razie potrzeby zjechania na prawo, zmniejszenia prędkości albo zatrzymania się.',
                ],
                '3155' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-1-pkt-2'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie dotyczy przejazdu obok nieruchomego pojazdu. Art. 23 ust. 1 pkt 2 wymaga przy omijaniu zachowania bezpiecznego odstępu od omijanego pojazdu.',
                ],
                '3157' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-1-pkt-2'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie pokazuje omijanie przeszkody. Art. 23 ust. 1 pkt 2 nakazuje pozostawić od niej bezpieczny odstęp i w razie potrzeby zmniejszyć prędkość.',
                ],
                '13540' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-1-pkt-2'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie dotyczy omijania pieszego pozostającego na torze lub przy torze jazdy. Art. 23 ust. 1 pkt 2 wymaga bezpiecznego odstępu oraz zmniejszenia prędkości, jeżeli wymaga tego sytuacja.',
                ],
                '2501' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-1-pkt-3'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie sprawdza poziom ostrożności podczas cofania. Art. 23 ust. 1 pkt 3 wymaga szczególnej ostrożności i sprawdzenia, czy manewr nie stworzy zagrożenia ani utrudnienia.',
                ],
                '13773' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-1-pkt-3'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie pokazuje pieszego podczas cofania pojazdu. Art. 23 ust. 1 pkt 3 nakazuje kierującemu cofającemu ustąpić pierwszeństwa innemu uczestnikowi ruchu.',
                ],
                '6206' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-2'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie dotyczy cofania na moście. Art. 23 ust. 2 wprost obejmuje most bezwzględnym zakazem cofania pojazdem.',
                ],
                '6203' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-2'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie sprawdza cofanie na autostradzie po minięciu zjazdu. Art. 23 ust. 2 zabrania takiego manewru; włączenie świateł awaryjnych nie tworzy wyjątku.',
                ],
                '6208' => [
                    'page' => $pages['wymijanie-omijanie-cofanie'],
                    'unit' => $units['art-23-ust-2'],
                    'topic' => $topics['wymijanie-omijanie-cofanie'],
                    'note' => 'To pytanie dotyczy cofania na drodze ekspresowej. Art. 23 ust. 2 wymienia tę drogę wprost wśród miejsc, w których cofanie jest zabronione.',
                ],
            ], $reviewer, $june20ReviewedAt);

            $this->attachQuestionReferences([
                '7403' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-1-pkt-2'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie sprawdza obserwację sytuacji za pojazdem przed rozpoczęciem wyprzedzania. Art. 24 ust. 1 pkt 2 wymaga upewnienia się, że kierujący jadący z tyłu nie rozpoczął już tego manewru.',
                ],
                '10208' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-2'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie dotyczy ogólnego odstępu od wyprzedzanego pojazdu. Art. 24 ust. 2 wymaga odstępu bezpiecznego dla sytuacji, choć nie wskazuje jednej wartości dla każdego rodzaju pojazdu.',
                ],
                '7640' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-2'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie podaje odstęp 0,5 m przy wyprzedzaniu rowerzysty. Art. 24 ust. 2 wymaga w tej sytuacji co najmniej 1 m, dlatego wskazana odległość jest niewystarczająca.',
                ],
                '9541' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-2'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie dotyczy ustawowego minimum przy wyprzedzaniu rowerzysty. Art. 24 ust. 2 wskazuje, że odstęp nie może być wtedy mniejszy niż 1 m.',
                ],
                '8979' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-2'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie obejmuje wyprzedzanie roweru albo motoroweru. Obie grupy są wymienione w art. 24 ust. 2, więc minimalny odstęp wynosi 1 m.',
                ],
                '10053' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-2'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie sprawdza odstęp od motocyklisty podczas wyprzedzania. Art. 24 ust. 2 wymienia motocykl w katalogu przypadków objętych minimum 1 m.',
                ],
                '7398' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-6'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie dotyczy zachowania kierującego pojazdem wyprzedzanym. Art. 24 ust. 6 zabrania mu zwiększania prędkości w czasie wyprzedzania i bezpośrednio po nim.',
                ],
                '7777' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-7-pkt-1'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie pokazuje dojazd do wierzchołka wzniesienia na drodze dwukierunkowej. Art. 24 ust. 7 pkt 1 co do zasady zabrania tam wyprzedzania pojazdu silnikowego.',
                ],
                '10937' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-7-pkt-3'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie dotyczy wyprzedzania na skrzyżowaniu. Art. 24 ust. 7 pkt 3 co do zasady go zabrania, poza skrzyżowaniem o ruchu okrężnym albo takim, na którym ruch jest kierowany.',
                ],
                '7012' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-11'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie łączy pojazd uprzywilejowany z obszarem zabudowanym. Art. 24 ust. 11 zabrania w takich warunkach wyprzedzania tego pojazdu.',
                ],
                '10939' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-2'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie odróżnia bezpieczny odstęp od stałego minimum 1 m. Art. 24 ust. 2 wymaga 1 m tylko wobec wymienionych uczestników; przy innym pojeździe odstęp nadal ma być bezpieczny, ale nie zawsze wynosi dokładnie 1 m.',
                ],
                '12779' => [
                    'page' => $pages['wyprzedzanie'],
                    'unit' => $units['art-24-ust-2'],
                    'topic' => $topics['wyprzedzanie'],
                    'note' => 'To pytanie sprawdza poziom ostrożności podczas wyprzedzania rowerzysty. Art. 24 ust. 2 wymaga szczególnej ostrożności, bezpiecznego odstępu i co najmniej 1 m.',
                ],
            ], $reviewer, $june20ReviewedAt);

            $this->attachZawracanieQuestionReferences(
                $pages['zawracanie'],
                $topics['zawracanie'],
                $units,
                $reviewer,
                $june20ReviewedAt,
            );

            $this->attachSignalDzwiekowyQuestionReferences(
                $pages['sygnal-dzwiekowy'],
                $topics['sygnal-dzwiekowy'],
                $units,
                $reviewer,
                $june20ReviewedAt,
            );

            $this->attachLadunekQuestionReferences(
                $pages['zabezpieczenie-ladunku-i-wymiary'],
                $topics['zabezpieczenie-ladunku-i-wymiary'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachPrzyczepaQuestionReferences(
                $pages['przyczepa-masa-wymiary-i-oswietlenie'],
                $topics['przyczepa-masa-wymiary-i-oswietlenie'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachPrzejazdKolejowyQuestionReferences(
                $pages['wjazd-na-przejazd-kolejowy'],
                $topics['wjazd-na-przejazd-kolejowy'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachHolowanieQuestionReferences(
                $pages['holowanie-pojazdu'],
                $topics['holowanie-pojazdu'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachPasyQuestionReferences(
                $pages['pasy-bezpieczenstwa'],
                $topics['pasy-bezpieczenstwa'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachSwiatlaDzienneQuestionReferences(
                $pages['swiatla-do-jazdy-dziennej-i-mijania'],
                $topics['swiatla-do-jazdy-dziennej-i-mijania'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachRogatkiQuestionReferences(
                $pages['rogatki-i-sygnaly-na-przejezdzie-kolejowym'],
                $topics['rogatki-i-sygnaly-na-przejezdzie-kolejowym'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachSwiatlaPrzeciwmgloweQuestionReferences(
                $pages['swiatla-przeciwmglowe'],
                $topics['swiatla-przeciwmglowe'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachFotelikiQuestionReferences(
                $pages['foteliki-i-przewoz-dzieci'],
                $topics['foteliki-i-przewoz-dzieci'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachWypadekQuestionReferences(
                $pages['obowiazki-uczestnika-wypadku'],
                $topics['obowiazki-uczestnika-wypadku'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachTelefonQuestionReferences(
                $pages['telefon-podczas-kierowania'],
                $topics['telefon-podczas-kierowania'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachSwiatlaDrogoweQuestionReferences(
                $pages['swiatla-drogowe-i-oslepianie'],
                $topics['swiatla-drogowe-i-oslepianie'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachWyposazenieQuestionReferences(
                $pages['gasnica-trojkat-i-obowiazkowe-wyposazenie'],
                $topics['gasnica-trojkat-i-obowiazkowe-wyposazenie'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachOponyQuestionReferences(
                $pages['opony-bieznik-i-cisnienie'],
                $topics['opony-bieznik-i-cisnienie'],
                $units,
                $reviewer,
                $june21ReviewedAt,
            );

            $this->attachDokumentyQuestionReferences(
                $pages['dokumenty-podczas-kontroli-drogowej'],
                $topics['dokumenty-podczas-kontroli-drogowej'],
                $units,
                $reviewer,
                $june22ReviewedAt,
            );

            $this->attachKategoriePrawaJazdyQuestionReferences(
                $pages['kategorie-prawa-jazdy-i-uprawnienia'],
                $topics['kategorie-prawa-jazdy-i-uprawnienia'],
                $units,
                $reviewer,
                $june22ReviewedAt,
            );

            $this->attachAlcoholQuestionReferences(
                $pages['alkohol-i-srodki-dzialajace-podobnie'],
                $topics['alkohol-i-srodki-dzialajace-podobnie'],
                $units,
                $reviewer,
                $june22ReviewedAt,
            );

            $this->attachGreenArrowQuestionReferences(
                $pages['zielona-strzalka-warunkowa'],
                $topics['zielona-strzalka-warunkowa'],
                $units,
                $reviewer,
                $june22ReviewedAt,
            );

            $this->attachEmergencyVehicleQuestionReferences(
                $pages['pojazd-uprzywilejowany'],
                $topics['pojazd-uprzywilejowany'],
                $units,
                $reviewer,
                $june22ReviewedAt,
            );

            $this->attachYellowSignalQuestionReferences(
                $pages['sygnal-zolty-i-zolty-migajacy'],
                $topics['sygnal-zolty-i-zolty-migajacy'],
                $units,
                $reviewer,
                $june22ReviewedAt,
            );
        });
    }

    /**
     * @return array<string, LegalUnit>
     */
    private function upsertLegalUnits(LegalAct $act, Carbon $reviewedAt, ContentAuthor $reviewer, Carbon $june13ReviewedAt, Carbon $june15ReviewedAt, Carbon $june19ReviewedAt, Carbon $june20ReviewedAt, Carbon $june21ReviewedAt, Carbon $june22ReviewedAt): array
    {
        $definitions = [
            'art-5' => [
                'label' => 'art. 5',
                'title' => 'Hierarchia poleceń, sygnałów i znaków drogowych',
                'summary' => 'Ten przepis porządkuje, co jest ważniejsze w ruchu: polecenia osoby kierującej ruchem, sygnały świetlne, znaki drogowe i ogólne zasady.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla art. 5: obowiązek stosowania się do poleceń, sygnałów i znaków oraz ich hierarchia.',
            ],
            'art-2-pkt-38' => [
                'type' => 'point',
                'label' => 'art. 2 pkt 38',
                'title' => 'Definicja pojazdu uprzywilejowanego',
                'summary' => 'Pojazd uprzywilejowany wysyła niebieskie sygnały błyskowe i jednocześnie sygnał dźwiękowy o zmiennym tonie oraz jedzie z włączonymi światłami mijania lub drogowymi.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 2 pkt 38: łączne sygnały wymagane do uznania pojazdu za uprzywilejowany.',
            ],
            'art-9-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 9 ust. 1',
                'title' => 'Obowiązek ułatwienia przejazdu pojazdu uprzywilejowanego',
                'summary' => 'Uczestnik ruchu i inna osoba na drodze muszą ułatwić przejazd pojazdu uprzywilejowanego, w szczególności niezwłocznie usunąć się z jego drogi, a w razie potrzeby zatrzymać.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 9 ust. 1: ogólny obowiązek ułatwienia przejazdu.',
            ],
            'art-9-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 9 ust. 2',
                'title' => 'Korytarz życia przy zwiększonym natężeniu ruchu',
                'summary' => 'Gdy zwiększone natężenie ruchu utrudnia swobodny przejazd pojazdu uprzywilejowanego, kierujący tworzą drogę przejazdu przez odpowiednie rozsunięcie pojazdów.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 9 ust. 2: przesłanka i cel utworzenia korytarza życia.',
            ],
            'art-9-ust-2-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 9 ust. 2 pkt 1',
                'title' => 'Korytarz życia na jezdni z dwoma pasami',
                'summary' => 'Na dwóch pasach w tym samym kierunku pojazdy z lewego pasa zjeżdżają jak najbliżej lewej krawędzi, a pojazdy z prawego pasa jak najbliżej prawej krawędzi.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 9 ust. 2 pkt 1: rozmieszczenie pojazdów na jezdni dwupasowej.',
            ],
            'art-9-ust-2-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 9 ust. 2 pkt 2',
                'title' => 'Korytarz życia na jezdni z więcej niż dwoma pasami',
                'summary' => 'Na co najmniej trzech pasach pojazdy ze skrajnego lewego pasa zjeżdżają w lewo, a pojazdy ze wszystkich pozostałych pasów w prawo.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 9 ust. 2 pkt 2: rozmieszczenie pojazdów na jezdni wielopasowej.',
            ],
            'art-9-ust-3-4' => [
                'type' => 'paragraph',
                'label' => 'art. 9 ust. 3-4',
                'title' => 'Zakaz korzystania z korytarza życia',
                'summary' => 'Kierujący innym pojazdem nie może korzystać z utworzonej drogi przejazdu; wyjątek dotyczy pojazdów zarządcy drogi lub pomocy drogowej uczestniczących w akcji ratowniczej.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 9 ust. 3-4: zakaz jazdy korytarzem i ustawowy wyjątek.',
            ],
            'art-13' => [
                'label' => 'art. 13',
                'title' => 'Zasady przechodzenia przez jezdnię i torowisko',
                'summary' => 'Ten przepis opisuje podstawowe obowiązki pieszego oraz sposób korzystania z przejść i torowisk.',
                'last_checked_at' => $june19ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 13: przechodzenie przez jezdnię, pierwszeństwo pieszego i szczególna ostrożność.',
            ],
            'art-17' => [
                'label' => 'art. 17',
                'title' => 'Włączanie się do ruchu',
                'summary' => 'Ten przepis wskazuje, kiedy kierujący włącza się do ruchu, oraz wymaga wtedy szczególnej ostrożności i ustąpienia pierwszeństwa innym pojazdom lub uczestnikom ruchu.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla art. 17: przypadki włączania się do ruchu, szczególna ostrożność i obowiązek ustąpienia pierwszeństwa.',
            ],
            'art-17-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 17 ust. 1',
                'title' => 'Rozpoznanie włączania się do ruchu',
                'summary' => 'Ten ustęp określa, że włączaniem się do ruchu jest między innymi ruszanie po postoju niezwiązanym z warunkami ruchu oraz wjazd na drogę z miejsc wskazanych w przepisie.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 17 ust. 1: definicja włączania się do ruchu i granica wobec ruszania po zatrzymaniu wynikającym z przepisów lub warunków ruchu.',
            ],
            'art-17-ust-1-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 17 ust. 1 pkt 1',
                'title' => 'Wyjazd z nieruchomości, obiektu, drogi niebędącej drogą publiczną lub strefy zamieszkania',
                'summary' => 'Wjazd na drogę z nieruchomości, obiektu przydrożnego lub dojazdu do niego, drogi niepublicznej albo strefy zamieszkania jest włączaniem się do ruchu.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 17 ust. 1 pkt 1: katalog typowych wyjazdów traktowanych jako włączanie się do ruchu.',
            ],
            'art-17-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 17 ust. 2',
                'title' => 'Ostrożność i ustąpienie pierwszeństwa przy włączaniu się do ruchu',
                'summary' => 'Kierujący włączający się do ruchu zachowuje szczególną ostrożność i ustępuje pierwszeństwa innemu pojazdowi lub uczestnikowi ruchu.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 17 ust. 2: szczególna ostrożność i obowiązek ustąpienia pierwszeństwa.',
            ],
            'art-18-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 18 ust. 1',
                'title' => 'Umożliwienie autobusowi wyjazdu z przystanku',
                'summary' => 'Ten ustęp nakazuje kierującemu zbliżającemu się do oznaczonego przystanku autobusowego lub trolejbusowego na obszarze zabudowanym zmniejszyć prędkość, a w razie potrzeby zatrzymać się, aby umożliwić sygnalizowane włączenie się autobusu do ruchu.',
                'last_checked_at' => $june19ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 18 ust. 1: oznaczony przystanek autobusowy lub trolejbusowy, obszar zabudowany, sygnalizowany manewr oraz obowiązek zmniejszenia prędkości lub zatrzymania.',
            ],
            'art-18-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 18 ust. 2',
                'title' => 'Obowiązek kierującego autobusem przed wjazdem na jezdnię',
                'summary' => 'Ten ustęp pozwala kierującemu autobusem wjechać na sąsiedni pas ruchu albo na jezdnię dopiero po upewnieniu się, że nie spowoduje to zagrożenia bezpieczeństwa ruchu drogowego.',
                'last_checked_at' => $june19ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 18 ust. 2: obowiązek upewnienia się przez kierującego autobusem, że wjazd na pas lub jezdnię nie stworzy zagrożenia.',
            ],
            'art-25' => [
                'label' => 'art. 25',
                'title' => 'Pierwszeństwo na skrzyżowaniu i przy zmianie kierunku jazdy',
                'summary' => 'Ten przepis opisuje zachowanie kierującego na skrzyżowaniu, w tym sytuacje, w których trzeba ustąpić pierwszeństwa.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla art. 25: szczególna ostrożność na skrzyżowaniu, pojazd z prawej strony i skręt w lewo.',
            ],
            'art-25-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 25 ust. 1',
                'title' => 'Pierwszeństwo z prawej strony i przy skręcie w lewo',
                'summary' => 'Kierujący zbliżający się do skrzyżowania zachowuje szczególną ostrożność i ustępuje pojazdowi z prawej strony, a przy skręcie w lewo także pojazdowi z przeciwka jadącemu na wprost lub skręcającemu w prawo.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 25 ust. 1: reguła prawej strony i obowiązek ustąpienia podczas skrętu w lewo.',
            ],
            'art-25-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 25 ust. 2',
                'title' => 'Pierwszeństwo pojazdu szynowego',
                'summary' => 'Pojazd szynowy ma pierwszeństwo w stosunku do innych pojazdów bez względu na to, z której strony nadjeżdża.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 25 ust. 2: pierwszeństwo pojazdu szynowego bez względu na stronę, z której nadjeżdża.',
            ],
            'art-26' => [
                'label' => 'art. 26',
                'title' => 'Obowiązki kierującego wobec pieszych i przy przystankach',
                'summary' => 'Ten przepis jest kluczowy dla pytań o przejścia dla pieszych, szczególną ostrożność oraz zachowanie przy przystankach tramwajowych.',
                'last_checked_at' => $june19ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 26: obowiązki kierującego przy przejściach dla pieszych, drogach dla pieszych i przystankach tramwajowych.',
            ],
            'art-26-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 26 ust. 1',
                'title' => 'Zbliżanie się do przejścia dla pieszych',
                'summary' => 'Ten ustęp wymaga od kierującego zbliżającego się do przejścia szczególnej ostrożności, zmniejszenia prędkości oraz ustąpienia pierwszeństwa pieszemu znajdującemu się na przejściu albo na nie wchodzącemu.',
                'last_checked_at' => $june19ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 26 ust. 1: szczególna ostrożność, zmniejszenie prędkości i pierwszeństwo pieszego przy przejściu.',
            ],
            'art-26-ust-4' => [
                'type' => 'paragraph',
                'label' => 'art. 26 ust. 4',
                'title' => 'Przejazd przez drogę dla pieszych',
                'summary' => 'Ten ustęp nakazuje kierującemu przejeżdżającemu przez drogę dla pieszych albo drogę dla pieszych i rowerów jechać powoli i ustąpić pierwszeństwa pieszemu.',
                'last_checked_at' => $june19ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 26 ust. 4: wolna jazda i pierwszeństwo pieszego przy przejeżdżaniu przez drogę dla pieszych.',
            ],
            'art-26-ust-6' => [
                'type' => 'paragraph',
                'label' => 'art. 26 ust. 6',
                'title' => 'Przystanek tramwajowy bez wysepki dla pasażerów',
                'summary' => 'Ten ustęp dotyczy szczególnej ostrożności przy oznaczonym przystanku tramwajowym oraz obowiązku zatrzymania pojazdu, gdy tramwaj wjeżdża na przystanek albo stoi na nim, a pasażerowie muszą bezpiecznie dojść do tramwaju lub na drogę dla pieszych.',
                'last_checked_at' => $june15ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla art. 26 ust. 6: przystanek tramwajowy bez wysepki, obowiązek zatrzymania pojazdu oraz bezpieczne dojście pasażerów.',
            ],
            'art-29-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 29 ust. 1',
                'title' => 'Ostrzeganie o niebezpieczeństwie',
                'summary' => 'Kierujący może użyć sygnału dźwiękowego lub świetlnego, gdy jest to konieczne do ostrzeżenia o niebezpieczeństwie.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Prawa o ruchu drogowym, Dz.U. z 2024 r. poz. 1251, dla art. 29 ust. 1: użycie sygnału dźwiękowego lub świetlnego wyłącznie w razie konieczności ostrzeżenia o niebezpieczeństwie.',
            ],
            'art-29-ust-2-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 29 ust. 2 pkt 1',
                'title' => 'Zakaz nadużywania sygnałów',
                'summary' => 'Zabronione jest nadużywanie sygnału dźwiękowego lub świetlnego, w tym wykorzystywanie go do ponaglania innych uczestników ruchu.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Prawa o ruchu drogowym, Dz.U. z 2024 r. poz. 1251, dla art. 29 ust. 2 pkt 1: zakaz nadużywania sygnału dźwiękowego lub świetlnego.',
            ],
            'art-29-ust-2-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 29 ust. 2 pkt 2',
                'title' => 'Sygnał dźwiękowy na obszarze zabudowanym',
                'summary' => 'Na obszarze zabudowanym sygnału dźwiękowego wolno użyć tylko wtedy, gdy jest to konieczne w związku z bezpośrednim niebezpieczeństwem.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Prawa o ruchu drogowym, Dz.U. z 2024 r. poz. 1251, dla art. 29 ust. 2 pkt 2: zakaz używania sygnału dźwiękowego na obszarze zabudowanym z wyjątkiem bezpośredniego niebezpieczeństwa.',
            ],
            'art-30-ust-1-pkt-1-lit-a' => [
                'type' => 'letter',
                'label' => 'art. 30 ust. 1 pkt 1 lit. a',
                'title' => 'Światła podczas zmniejszonej przejrzystości powietrza',
                'summary' => 'Podczas mgły, opadów lub innej zmniejszonej przejrzystości kierujący pojazdem silnikowym włącza światła mijania albo przednie przeciwmgłowe, albo oba te rodzaje świateł jednocześnie.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 30 ust. 1 pkt 1 lit. a: obowiązkowe światła w warunkach zmniejszonej przejrzystości.',
            ],
            'art-30-ust-1-pkt-1-lit-b' => [
                'type' => 'letter',
                'label' => 'art. 30 ust. 1 pkt 1 lit. b',
                'title' => 'Krótkie sygnały podczas mgły poza obszarem zabudowanym',
                'summary' => 'Poza obszarem zabudowanym podczas mgły kierujący pojazdem silnikowym daje krótkotrwałe sygnały dźwiękowe podczas wyprzedzania lub omijania.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Prawa o ruchu drogowym, Dz.U. z 2024 r. poz. 1251, dla art. 30 ust. 1 pkt 1 lit. b: obowiązek krótkotrwałych sygnałów dźwiękowych podczas wyprzedzania lub omijania we mgle poza obszarem zabudowanym.',
            ],
            'art-30-ust-3' => [
                'type' => 'paragraph',
                'label' => 'art. 30 ust. 3',
                'title' => 'Tylne światła przeciwmgłowe i granica 50 metrów',
                'summary' => 'Tylnych świateł przeciwmgłowych wolno używać, gdy zmniejszona przejrzystość powietrza ogranicza widoczność na odległość mniejszą niż 50 m; po poprawie widoczności trzeba je niezwłocznie wyłączyć.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 30 ust. 3: próg widoczności poniżej 50 m oraz obowiązek niezwłocznego wyłączenia tylnych świateł przeciwmgłowych po poprawie warunków.',
            ],
            'art-19' => [
                'label' => 'art. 19',
                'title' => 'Prędkość bezpieczna, hamowanie i odstęp od pojazdu',
                'summary' => 'Ten przepis wymaga jazdy z prędkością pozwalającą panować nad pojazdem, bezpiecznego hamowania i utrzymywania odstępu od poprzedzającego pojazdu.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego źródła ISAP/ELI dla art. 19 Prawa o ruchu drogowym, w tym reguły odstępu na autostradzie i drodze ekspresowej.',
            ],
            'art-19-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 19 ust. 1',
                'title' => 'Prędkość zapewniająca panowanie nad pojazdem',
                'summary' => 'Kierujący dobiera prędkość z uwzględnieniem między innymi widoczności i stanu drogi, pogody, natężenia ruchu, rzeźby terenu oraz stanu i ładunku pojazdu.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 19 ust. 1: prędkość zapewniająca panowanie nad pojazdem i katalog warunków wpływających na jej dobór.',
            ],
            'art-19-ust-2-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 19 ust. 2 pkt 2',
                'title' => 'Hamowanie bez tworzenia zagrożenia',
                'summary' => 'Kierujący ma hamować w sposób, który nie powoduje zagrożenia bezpieczeństwa ruchu ani jego utrudnienia.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 19 ust. 2 pkt 2: obowiązek bezpiecznego hamowania.',
            ],
            'art-19-ust-2-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 19 ust. 2 pkt 3',
                'title' => 'Odstęp pozwalający uniknąć zderzenia',
                'summary' => 'Kierujący utrzymuje odstęp potrzebny do uniknięcia zderzenia, gdy poprzedzający pojazd zahamuje albo się zatrzyma.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 19 ust. 2 pkt 3: ogólna reguła bezpiecznego odstępu.',
            ],
            'art-19-ust-3a' => [
                'type' => 'paragraph',
                'label' => 'art. 19 ust. 3a',
                'title' => 'Minimalny odstęp na autostradzie i drodze ekspresowej',
                'summary' => 'Na autostradzie i drodze ekspresowej minimalny odstęp w metrach ma wynosić co najmniej połowę aktualnej prędkości wyrażonej w km/h; wyjątek dotyczy manewru wyprzedzania.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 19 ust. 3a: reguła połowy prędkości i wyjątek podczas wyprzedzania.',
            ],
            'art-20' => [
                'label' => 'art. 20',
                'title' => 'Dopuszczalne prędkości pojazdów',
                'summary' => 'Ten przepis określa podstawowe limity prędkości, między innymi w obszarze zabudowanym, strefie zamieszkania, na autostradzie, drogach ekspresowych i pozostałych drogach.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla aktualnych limitów prędkości obowiązujących w pytaniach egzaminacyjnych.',
            ],
            'art-20-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 20 ust. 2',
                'title' => 'Prędkość w strefie zamieszkania',
                'summary' => 'Dopuszczalna prędkość pojazdu lub zespołu pojazdów w strefie zamieszkania wynosi 20 km/h.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 20 ust. 2: limit 20 km/h w strefie zamieszkania.',
            ],
            'art-20-ust-3-pkt-1-lit-a' => [
                'type' => 'letter',
                'label' => 'art. 20 ust. 3 pkt 1 lit. a',
                'title' => 'Prędkość samochodu osobowego na autostradzie',
                'summary' => 'Dla samochodu osobowego, motocykla oraz samochodu ciężarowego o DMC do 3,5 t dopuszczalna prędkość na autostradzie wynosi 140 km/h.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 20 ust. 3 pkt 1 lit. a: limit 140 km/h na autostradzie dla wskazanych pojazdów.',
            ],
            'art-21' => [
                'label' => 'art. 21',
                'title' => 'Zmiana dopuszczalnej prędkości znakami',
                'summary' => 'Ten przepis wyjaśnia, że organ zarządzający ruchem może znakami zmniejszyć lub zwiększyć dopuszczalną prędkość na określonych odcinkach.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego źródła ISAP/ELI dla zasad zmieniania dopuszczalnej prędkości za pomocą znaków drogowych.',
            ],
            'art-22' => [
                'label' => 'art. 22',
                'title' => 'Zmiana kierunku jazdy lub pasa ruchu',
                'summary' => 'Ten przepis opisuje szczególną ostrożność przy zmianie kierunku i pasa ruchu, właściwe ustawienie pojazdu, sygnalizowanie manewru oraz podstawowe zakazy zawracania.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla art. 22: zmiana kierunku jazdy, zmiana pasa ruchu, sygnalizowanie i zawracanie.',
            ],
            'art-22-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 22 ust. 1',
                'title' => 'Szczególna ostrożność przy zmianie kierunku lub pasa',
                'summary' => 'Zmiana kierunku jazdy albo zajmowanego pasa ruchu jest dozwolona tylko z zachowaniem szczególnej ostrożności.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 22 ust. 1: szczególna ostrożność przy zmianie kierunku jazdy lub pasa ruchu.',
            ],
            'art-22-ust-2-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 22 ust. 2 pkt 2',
                'title' => 'Ustawienie pojazdu przed skrętem w lewo',
                'summary' => 'Przed skrętem w lewo kierujący zbliża się do środka jezdni, a na jezdni jednokierunkowej do jej lewej krawędzi, z wyjątkami wskazanymi w przepisie.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 22 ust. 2 pkt 2: ustawienie pojazdu przed skrętem w lewo.',
            ],
            'art-22-ust-5' => [
                'type' => 'paragraph',
                'label' => 'art. 22 ust. 5',
                'title' => 'Sygnalizowanie manewru kierunkowskazem',
                'summary' => 'Zamiar zmiany kierunku jazdy lub pasa ruchu trzeba sygnalizować zawczasu i wyraźnie, a sygnalizowanie zakończyć niezwłocznie po wykonaniu manewru.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 22 ust. 5: moment rozpoczęcia i zakończenia sygnalizowania kierunkowskazem.',
            ],
            'art-22-ust-6-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 22 ust. 6 pkt 1',
                'title' => 'Zakaz zawracania w tunelu, na moście, wiadukcie i drodze jednokierunkowej',
                'summary' => 'Zawracanie jest zabronione w tunelu, na moście, na wiadukcie oraz na drodze jednokierunkowej.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 22 ust. 6 pkt 1: zamknięty katalog czterech miejsc objętych zakazem zawracania.',
            ],
            'art-22-ust-6-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 22 ust. 6 pkt 2',
                'title' => 'Zakaz zawracania na autostradzie',
                'summary' => 'Zawracanie na autostradzie jest zabronione.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 22 ust. 6 pkt 2: bezwzględny zakaz zawracania na autostradzie.',
            ],
            'art-22-ust-6-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 22 ust. 6 pkt 3',
                'title' => 'Zakaz zawracania na drodze ekspresowej',
                'summary' => 'Na drodze ekspresowej zawracanie jest zabronione, z wyjątkiem skrzyżowania lub miejsca do tego przeznaczonego.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 22 ust. 6 pkt 3: zakaz na drodze ekspresowej oraz wyjątek dla skrzyżowania i miejsca przeznaczonego do zawracania.',
            ],
            'art-22-ust-6-pkt-4' => [
                'type' => 'point',
                'label' => 'art. 22 ust. 6 pkt 4',
                'title' => 'Zakaz zawracania przy zagrożeniu lub utrudnieniu ruchu',
                'summary' => 'Zawracanie jest zabronione w warunkach, w których mogłoby zagrozić bezpieczeństwu ruchu albo ten ruch utrudnić.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 22 ust. 6 pkt 4: ocena zagrożenia bezpieczeństwa i utrudnienia ruchu niezależnie od oznakowania.',
            ],
            'art-23' => [
                'label' => 'art. 23',
                'title' => 'Wymijanie, omijanie i cofanie',
                'summary' => 'Ten przepis opisuje bezpieczny odstęp przy wymijaniu i omijaniu, zachowanie wobec przeszkód oraz obowiązki i zakazy związane z cofaniem pojazdu.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla art. 23: wymijanie, omijanie, cofanie i zakazy cofania.',
            ],
            'art-23-ust-1-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 23 ust. 1 pkt 1',
                'title' => 'Bezpieczne wymijanie',
                'summary' => 'Przy wymijaniu trzeba zachować bezpieczny odstęp, a w razie potrzeby zjechać na prawo, zmniejszyć prędkość lub zatrzymać się.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 23 ust. 1 pkt 1: odstęp i reakcja przy wymijaniu.',
            ],
            'art-23-ust-1-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 23 ust. 1 pkt 2',
                'title' => 'Bezpieczne omijanie',
                'summary' => 'Przy omijaniu trzeba zachować bezpieczny odstęp od pojazdu, uczestnika ruchu lub przeszkody, a w razie potrzeby zmniejszyć prędkość.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 23 ust. 1 pkt 2: odstęp, prędkość i strona omijania.',
            ],
            'art-23-ust-1-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 23 ust. 1 pkt 3',
                'title' => 'Obowiązki podczas cofania',
                'summary' => 'Podczas cofania kierujący ustępuje pierwszeństwa, zachowuje szczególną ostrożność i upewnia się, że manewr nie stworzy zagrożenia ani utrudnienia.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 23 ust. 1 pkt 3: pierwszeństwo, szczególna ostrożność i kontrola przestrzeni za pojazdem.',
            ],
            'art-23-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 23 ust. 2',
                'title' => 'Miejsca objęte zakazem cofania',
                'summary' => 'Cofanie jest zabronione w tunelu, na moście, wiadukcie, autostradzie i drodze ekspresowej.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 23 ust. 2: zamknięty katalog miejsc objętych zakazem cofania.',
            ],
            'art-24' => [
                'label' => 'art. 24',
                'title' => 'Wyprzedzanie',
                'summary' => 'Ten przepis opisuje warunki rozpoczęcia wyprzedzania, bezpieczny odstęp, minimalny odstęp wobec wybranych uczestników ruchu, stronę wykonywania manewru oraz najważniejsze zakazy wyprzedzania.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla art. 24: warunki, odstępy, strona manewru, wyjątki i zakazy wyprzedzania.',
            ],
            'art-24-ust-1-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 24 ust. 1 pkt 2',
                'title' => 'Sprawdzenie pojazdu jadącego z tyłu przed wyprzedzaniem',
                'summary' => 'Przed wyprzedzaniem kierujący upewnia się, czy kierujący jadący za nim nie rozpoczął już wyprzedzania.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 24 ust. 1 pkt 2: kontrola sytuacji za pojazdem przed rozpoczęciem wyprzedzania.',
            ],
            'art-24-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 24 ust. 2',
                'title' => 'Ostrożność i odstęp podczas wyprzedzania',
                'summary' => 'Wyprzedzanie wymaga szczególnej ostrożności i bezpiecznego odstępu; wobec wskazanych uczestników ruchu odstęp nie może być mniejszy niż 1 m.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 24 ust. 2: bezpieczny odstęp i ustawowe minimum 1 m.',
            ],
            'art-24-ust-6' => [
                'type' => 'paragraph',
                'label' => 'art. 24 ust. 6',
                'title' => 'Zakaz zwiększania prędkości przez pojazd wyprzedzany',
                'summary' => 'Kierujący pojazdem wyprzedzanym nie może zwiększać prędkości w czasie wyprzedzania ani bezpośrednio po nim.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 24 ust. 6: zachowanie kierującego pojazdem wyprzedzanym.',
            ],
            'art-24-ust-7-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 24 ust. 7 pkt 1',
                'title' => 'Zakaz wyprzedzania przy wierzchołku wzniesienia',
                'summary' => 'Co do zasady zabronione jest wyprzedzanie pojazdu silnikowego jadącego po jezdni przy dojeżdżaniu do wierzchołka wzniesienia.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 24 ust. 7 pkt 1 wraz z wyjątkami określonymi w ust. 8.',
            ],
            'art-24-ust-7-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 24 ust. 7 pkt 3',
                'title' => 'Zakaz wyprzedzania na skrzyżowaniu',
                'summary' => 'Wyprzedzanie pojazdu silnikowego na skrzyżowaniu jest co do zasady zabronione, z wyjątkiem ruchu okrężnego lub kierowanego i innych sytuacji wskazanych w przepisie.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 24 ust. 7 pkt 3 wraz z wyjątkami dotyczącymi skrzyżowań.',
            ],
            'art-24-ust-11' => [
                'type' => 'paragraph',
                'label' => 'art. 24 ust. 11',
                'title' => 'Zakaz wyprzedzania pojazdu uprzywilejowanego',
                'summary' => 'Na obszarze zabudowanym zabronione jest wyprzedzanie pojazdu uprzywilejowanego.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 24 ust. 11: miejsce i zakres zakazu wyprzedzania pojazdu uprzywilejowanego.',
            ],
            'art-28-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 28 ust. 1',
                'title' => 'Szczególna ostrożność przed przejazdem kolejowym',
                'summary' => 'Kierujący zbliżający się do przejazdu kolejowego i przejeżdżający przez niego zachowuje szczególną ostrożność oraz upewnia się, czy nie zbliża się pojazd szynowy.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 28 ust. 1: szczególna ostrożność i obowiązek upewnienia się przed wjazdem na tory.',
            ],
            'art-28-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 28 ust. 2',
                'title' => 'Prędkość umożliwiająca zatrzymanie przed torami',
                'summary' => 'Kierujący powinien prowadzić pojazd z taką prędkością, aby mógł zatrzymać się w bezpiecznym miejscu, gdy nadjeżdża pojazd szynowy albo wymagają tego urządzenia zabezpieczające.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 28 ust. 2: możliwość zatrzymania przed torem kolejowym.',
            ],
            'art-28-ust-3-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 28 ust. 3 pkt 1',
                'title' => 'Zakaz omijania i wjazdu przy zaporach',
                'summary' => 'Nie wolno objeżdżać opuszczonych zapór lub półzapór ani wjeżdżać na przejazd, jeżeli ich opuszczanie rozpoczęto lub podnoszenia nie zakończono.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 28 ust. 3 pkt 1: pełny zakres zakazu związanego z zaporami i półzaporami.',
            ],
            'art-28-ust-3-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 28 ust. 3 pkt 2',
                'title' => 'Zakaz wjazdu bez miejsca za przejazdem',
                'summary' => 'Nie wolno wjeżdżać na przejazd kolejowy, jeżeli po jego drugiej stronie nie ma miejsca do kontynuowania jazdy.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 28 ust. 3 pkt 2: konieczność możliwości pełnego opuszczenia torowiska.',
            ],
            'art-28-ust-3-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 28 ust. 3 pkt 3',
                'title' => 'Zakaz wyprzedzania na przejeździe i przed nim',
                'summary' => 'Zabronione jest wyprzedzanie pojazdu na przejeździe kolejowym oraz bezpośrednio przed nim.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 28 ust. 3 pkt 3: zakaz wyprzedzania na przejeździe i bezpośrednio przed nim.',
            ],
            'art-31-ust-1-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 1 pkt 1',
                'title' => 'Prędkość podczas holowania',
                'summary' => 'Podczas holowania prędkość nie może przekraczać 30 km/h w obszarze zabudowanym i 60 km/h poza nim.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 1 pkt 1: limity 30 i 60 km/h.',
            ],
            'art-31-ust-1-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 1 pkt 2',
                'title' => 'Światła mijania pojazdu holującego',
                'summary' => 'Pojazd holujący ma mieć włączone światła mijania również w okresie dostatecznej widoczności.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 1 pkt 2: obowiązek świateł mijania w pojeździe holującym.',
            ],
            'art-31-ust-1-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 1 pkt 3',
                'title' => 'Kierujący w pojeździe holowanym',
                'summary' => 'W pojeździe holowanym powinien znajdować się kierujący posiadający uprawnienie do kierowania tym pojazdem, chyba że sposób holowania wyklucza potrzebę kierowania.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 1 pkt 3: obecność i uprawnienia kierującego w pojeździe holowanym.',
            ],
            'art-31-ust-1-pkt-4' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 1 pkt 4',
                'title' => 'Holowanie motocykla',
                'summary' => 'Motocykl może być holowany wyłącznie za pomocą połączenia giętkiego umożliwiającego łatwe odczepienie.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 1 pkt 4: szczególny sposób połączenia motocykla.',
            ],
            'art-31-ust-1-pkt-5' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 1 pkt 5',
                'title' => 'Oznakowanie pojazdu holowanego',
                'summary' => 'Pojazd holowany oznacza się z tyłu po lewej stronie trójkątem ostrzegawczym, a przy niedostatecznej widoczności włącza się światła pozycyjne.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 1 pkt 5: trójkąt albo żółty sygnał błyskowy i światła pozycyjne.',
            ],
            'art-31-ust-1-pkt-6' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 1 pkt 6',
                'title' => 'Sprawność układów hamulcowych',
                'summary' => 'Przy połączeniu sztywnym w pojeździe holowanym musi być sprawny co najmniej jeden układ hamulcowy, a przy giętkim dwa układy.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 1 pkt 6: wymagania hamulców dla połączenia sztywnego i giętkiego.',
            ],
            'art-31-ust-1-pkt-7' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 1 pkt 7',
                'title' => 'Odległość i oznaczenie połączenia',
                'summary' => 'Odległość między pojazdami wynosi najwyżej 3 m przy połączeniu sztywnym oraz od 4 do 6 m przy giętkim, które musi być oznakowane.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 1 pkt 7: odstępy i oznaczenie połączenia holowniczego.',
            ],
            'art-31-ust-2-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 2 pkt 1',
                'title' => 'Zakaz holowania przy niesprawnym układzie kierowniczym lub hamulcach',
                'summary' => 'Nie wolno holować pojazdu z niesprawnym układem kierowniczym lub hamulcowym, chyba że sposób holowania wyklucza potrzebę ich używania.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 2 pkt 1: niesprawny układ kierowniczy lub hamulce.',
            ],
            'art-31-ust-2-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 2 pkt 3',
                'title' => 'Zakaz holowania więcej niż jednego pojazdu',
                'summary' => 'Nie wolno holować więcej niż jednego pojazdu, z wyjątkiem pojazdu członowego.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 2 pkt 3: zakaz holowania zespołu złożonego z kilku pojazdów.',
            ],
            'art-31-ust-2-pkt-4' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 2 pkt 4',
                'title' => 'Zakaz holowania pojazdem z przyczepą',
                'summary' => 'Nie wolno holować pojazdem, do którego jest już przyczepiona przyczepa lub naczepa.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 2 pkt 4: zakaz użycia jako holującego pojazdu z przyczepą lub naczepą.',
            ],
            'art-31-ust-2-pkt-5' => [
                'type' => 'point',
                'label' => 'art. 31 ust. 2 pkt 5',
                'title' => 'Zakaz holowania na autostradzie',
                'summary' => 'Na autostradzie holowanie jest zabronione, z wyjątkiem holowania przez przeznaczony do tego pojazd do najbliższego wyjazdu lub miejsca obsługi podróżnych.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 31 ust. 2 pkt 5: wyjątek wyłącznie dla profesjonalnego pojazdu holującego.',
            ],
            'art-38-ust-1-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 38 ust. 1 pkt 1',
                'title' => 'Dokument uprawnienia inny niż krajowe prawo jazdy',
                'summary' => 'Kierujący okazuje na żądanie dokument stwierdzający uprawnienie do kierowania pojazdem inny niż wydane w kraju prawo jazdy, pozwolenie na kierowanie tramwajem albo ich tymczasowa elektroniczna postać.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 1251 i późniejszych nowelizacji dla art. 38 ust. 1 pkt 1. Przepis nie ustanawia ogólnego obowiązku wożenia krajowego prawa jazdy, ale obejmuje inne dokumenty uprawnienia.',
            ],
            'art-38-ust-1-pkt-3a' => [
                'type' => 'point',
                'label' => 'art. 38 ust. 1 pkt 3a',
                'title' => 'Zaświadczenie o badaniu pojazdu z blokadą alkoholową',
                'summary' => 'Kierujący pojazdem wyposażonym w blokadę alkoholową okazuje zaświadczenie o przeprowadzonym badaniu technicznym potwierdzającym jej prawidłowe działanie.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 1251 i późniejszych nowelizacji dla art. 38 ust. 1 pkt 3a.',
            ],
            'art-38-ust-1-pkt-4a' => [
                'type' => 'point',
                'label' => 'art. 38 ust. 1 pkt 4a',
                'title' => 'Dokument kalibracji blokady alkoholowej',
                'summary' => 'W pojeździe wyposażonym w blokadę alkoholową kierujący okazuje dokument potwierdzający jej kalibrację.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 1251 i późniejszych nowelizacji dla art. 38 ust. 1 pkt 4a.',
            ],
            'art-38-ust-1-pkt-4b-lit-a' => [
                'type' => 'point',
                'label' => 'art. 38 ust. 1 pkt 4b lit. a',
                'title' => 'Pokwitowanie zatrzymania prawa jazdy lub pozwolenia',
                'summary' => 'Jeżeli zatrzymane prawo jazdy albo pozwolenie na kierowanie tramwajem zastępuje ważne pokwitowanie, kierujący okazuje to pokwitowanie.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 1251 i późniejszych nowelizacji dla art. 38 ust. 1 pkt 4b lit. a.',
            ],
            'art-38-ust-1-pkt-4b-lit-b' => [
                'type' => 'point',
                'label' => 'art. 38 ust. 1 pkt 4b lit. b',
                'title' => 'Pokwitowanie zatrzymania dowodu rejestracyjnego',
                'summary' => 'Kierujący okazuje pokwitowanie zatrzymania dowodu rejestracyjnego albo pozwolenia czasowego, gdy dokument ten uprawnia jeszcze do używania pojazdu.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 1251 i późniejszych nowelizacji dla art. 38 ust. 1 pkt 4b lit. b, również w odniesieniu do przyczepy.',
            ],
            'art-38-ust-1-pkt-5' => [
                'type' => 'point',
                'label' => 'art. 38 ust. 1 pkt 5',
                'title' => 'Dokumenty wymagane przez przepisy szczególne',
                'summary' => 'Kierujący okazuje również inne dokumenty, jeżeli obowiązek ich posiadania albo okazania wynika z odrębnej ustawy.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 1251 i późniejszych nowelizacji dla art. 38 ust. 1 pkt 5.',
            ],
            'art-38-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 38 ust. 2',
                'title' => 'Dokumenty pojazdu zarejestrowanego za granicą',
                'summary' => 'Kierujący pojazdem zarejestrowanym za granicą okazuje dokument dopuszczający pojazd do ruchu oraz dokument ubezpieczenia OC lub dowód opłacenia składki.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 1251 i późniejszych nowelizacji dla art. 38 ust. 2.',
            ],
            'art-38-ust-3' => [
                'type' => 'paragraph',
                'label' => 'art. 38 ust. 3',
                'title' => 'Dokument dopuszczający pojazd do jazdy testowej',
                'summary' => 'Podczas jazdy testowej kierujący okazuje dokument stwierdzający dopuszczenie pojazdu do ruchu.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 1251 i późniejszych nowelizacji dla art. 38 ust. 3.',
            ],
            'art-39-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 39 ust. 1',
                'title' => 'Obowiązek korzystania z pasów bezpieczeństwa',
                'summary' => 'Kierujący pojazdem samochodowym oraz osoba przewożona pojazdem wyposażonym w pasy bezpieczeństwa korzystają z nich podczas jazdy.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 39 ust. 1: obowiązek kierującego i przewożonych osób.',
            ],
            'art-39-ust-2-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 39 ust. 2 pkt 2',
                'title' => 'Wyjątek dla kobiety o widocznej ciąży',
                'summary' => 'Obowiązek korzystania z pasów bezpieczeństwa nie dotyczy kobiety o widocznej ciąży.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 39 ust. 2 pkt 2: ustawowy wyjątek dla widocznej ciąży.',
            ],
            'art-39-ust-2-pkt-10' => [
                'type' => 'point',
                'label' => 'art. 39 ust. 2 pkt 10',
                'title' => 'Wyjątek dla dziecka poniżej 3 lat w autobusie',
                'summary' => 'Obowiązek korzystania z pasów bezpieczeństwa nie dotyczy dziecka w wieku poniżej 3 lat przewożonego autobusem.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 39 ust. 2 pkt 10: wyjątek dotyczący dziecka poniżej 3 lat w autobusie.',
            ],
            'art-39-ust-3' => [
                'type' => 'paragraph',
                'label' => 'art. 39 ust. 3',
                'title' => 'Dziecko poniżej 150 cm w urządzeniu przytrzymującym',
                'summary' => 'W pojeździe kategorii M1, N1, N2 lub N3 wyposażonym w pasy dziecko mające mniej niż 150 cm przewozi się w urządzeniu przytrzymującym dobranym do jego masy i wzrostu oraz zgodnym z wymaganiami technicznymi.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 39 ust. 3: reguła 150 cm oraz dobór urządzenia do masy i wzrostu dziecka.',
            ],
            'art-39-ust-3a' => [
                'type' => 'paragraph',
                'label' => 'art. 39 ust. 3a',
                'title' => 'Montaż fotelika zgodnie z zaleceniami producenta',
                'summary' => 'Fotelik lub inne urządzenie przytrzymujące instaluje się zgodnie z zaleceniami producenta urządzenia, wskazującymi sposób bezpiecznego stosowania.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 39 ust. 3a: obowiązek montażu zgodnie z zaleceniami producenta.',
            ],
            'art-39-ust-3b' => [
                'type' => 'paragraph',
                'label' => 'art. 39 ust. 3b',
                'title' => 'Wyjątek od 135 cm wyłącznie na tylnym siedzeniu',
                'summary' => 'Na tylnym siedzeniu można przewozić dziecko mające co najmniej 135 cm wzrostu przytrzymywane pasami, jeżeli ze względu na masę i wzrost nie można zapewnić odpowiedniego urządzenia.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 39 ust. 3b: ograniczony wyjątek dla dziecka od 135 cm, wyłącznie na tylnym siedzeniu.',
            ],
            'art-39-ust-3c' => [
                'type' => 'paragraph',
                'label' => 'art. 39 ust. 3c',
                'title' => 'Trzecie dziecko na tylnym siedzeniu',
                'summary' => 'Jeżeli dwa urządzenia przytrzymujące uniemożliwiają montaż trzeciego, dziecko mające co najmniej 3 lata może siedzieć między nimi na tylnym siedzeniu i korzystać z pasów bezpieczeństwa.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 39 ust. 3c: warunki przewozu trzeciego dziecka między dwoma urządzeniami przytrzymującymi.',
            ],
            'art-39-ust-4-pkt-4' => [
                'type' => 'point',
                'label' => 'art. 39 ust. 4 pkt 4',
                'title' => 'Zaświadczenie o przeciwwskazaniu do przewozu w foteliku',
                'summary' => 'Obowiązek korzystania z urządzenia przytrzymującego nie dotyczy dziecka mającego zaświadczenie lekarskie o przeciwwskazaniu do takiego przewozu.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 39 ust. 4 pkt 4: wyjątek oparty na zaświadczeniu lekarskim.',
            ],
            'art-44-ust-1-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 44 ust. 1 pkt 1',
                'title' => 'Zatrzymanie pojazdu bez powodowania nowego zagrożenia',
                'summary' => 'Kierujący uczestniczący w wypadku zatrzymuje pojazd, nie powodując przy tym zagrożenia bezpieczeństwa ruchu.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 44 ust. 1 pkt 1: bezpieczne zatrzymanie po zdarzeniu.',
            ],
            'art-44-ust-1-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 44 ust. 1 pkt 2',
                'title' => 'Zapewnienie bezpieczeństwa w miejscu wypadku',
                'summary' => 'Kierujący podejmuje odpowiednie działania w celu zapewnienia bezpieczeństwa ruchu w miejscu wypadku.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 44 ust. 1 pkt 2: zabezpieczenie miejsca zdarzenia.',
            ],
            'art-44-ust-1-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 44 ust. 1 pkt 3',
                'title' => 'Usunięcie pojazdu, gdy nie ma rannych',
                'summary' => 'Jeżeli w wypadku nie ma osoby zabitej ani rannej, pojazd trzeba niezwłocznie usunąć z miejsca zdarzenia, aby nie powodował zagrożenia lub tamowania ruchu.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 44 ust. 1 pkt 3: obowiązek usunięcia pojazdu przy zdarzeniu bez ofiar.',
            ],
            'art-44-ust-1-pkt-4' => [
                'type' => 'point',
                'label' => 'art. 44 ust. 1 pkt 4',
                'title' => 'Dane uczestników, właścicieli i ubezpieczyciela',
                'summary' => 'Na żądanie osoby uczestniczącej w wypadku kierujący podaje swoje dane, dane właściciela lub posiadacza pojazdu oraz informacje o ubezpieczeniu OC.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 44 ust. 1 pkt 4: zakres danych przekazywanych innym uczestnikom.',
            ],
            'art-44-ust-2-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 44 ust. 2 pkt 1',
                'title' => 'Pomoc poszkodowanym i wezwanie służb',
                'summary' => 'Jeżeli w wypadku jest osoba zabita lub ranna, kierujący udziela niezbędnej pomocy oraz wzywa zespół ratownictwa medycznego i Policję.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 44 ust. 2 pkt 1: pomoc rannym oraz wezwanie ratownictwa i Policji.',
            ],
            'art-44-ust-2-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 44 ust. 2 pkt 2',
                'title' => 'Zakaz działań utrudniających ustalenie przebiegu wypadku',
                'summary' => 'Przy osobie zabitej lub rannej nie wolno podejmować czynności, które mogłyby utrudnić ustalenie przebiegu wypadku.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 44 ust. 2 pkt 2: pozostawienie istotnych elementów miejsca zdarzenia do czasu czynności służb.',
            ],
            'art-44-ust-2-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 44 ust. 2 pkt 3',
                'title' => 'Pozostanie na miejscu wypadku z rannymi',
                'summary' => 'Kierujący pozostaje na miejscu wypadku, a gdy wezwanie służb wymaga oddalenia się, niezwłocznie na nie powraca.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 44 ust. 2 pkt 3: zakaz oddalania się z wyjątkiem wezwania służb i obowiązek powrotu.',
            ],
            'art-44-ust-3' => [
                'type' => 'paragraph',
                'label' => 'art. 44 ust. 3',
                'title' => 'Obowiązki innych uczestników wypadku',
                'summary' => 'Obowiązki z art. 44 ust. 1 pkt 1-3 stosuje się odpowiednio także do innych osób uczestniczących w wypadku.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 44 ust. 3: odpowiednie stosowanie wyłącznie obowiązków z ust. 1 pkt 1-3 do innych uczestników.',
            ],
            'art-45-ust-1-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 45 ust. 1 pkt 1',
                'title' => 'Zakaz kierowania po alkoholu lub środku działającym podobnie',
                'summary' => 'Zabronione jest kierowanie pojazdem, prowadzenie kolumny pieszych oraz jazda wierzchem osobie w stanie nietrzeźwości, po użyciu alkoholu albo środka działającego podobnie do alkoholu.',
                'last_checked_at' => $june22ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu Prawa o ruchu drogowym dla art. 45 ust. 1 pkt 1: zakaz obejmuje stan nietrzeźwości, stan po użyciu alkoholu i środek działający podobnie.',
            ],
            'art-45-ust-1-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 45 ust. 1 pkt 2',
                'title' => 'Zakaz holowania pojazdu kierowanego przez osobę po alkoholu',
                'summary' => 'Zabronione jest holowanie pojazdu, którym kieruje osoba nietrzeźwa, po użyciu alkoholu albo środka działającego podobnie.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 45 ust. 1 pkt 2: zakaz dotyczący osoby kierującej pojazdem holowanym.',
            ],
            'art-45-ust-2-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 45 ust. 2 pkt 1',
                'title' => 'Zakaz trzymania telefonu podczas kierowania',
                'summary' => 'Kierującemu pojazdem zabrania się korzystania podczas jazdy z telefonu wymagającego trzymania słuchawki lub mikrofonu w ręku.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 45 ust. 2 pkt 1: zakaz obejmuje sposób korzystania wymagający trzymania słuchawki lub mikrofonu w ręku.',
            ],
            'art-45-ust-2-pkt-4' => [
                'type' => 'point',
                'label' => 'art. 45 ust. 2 pkt 4',
                'title' => 'Fotelik tyłem do kierunku jazdy i aktywna poduszka',
                'summary' => 'Zabroniony jest przewóz dziecka tyłem do kierunku jazdy na przednim siedzeniu wyposażonym w aktywną poduszkę powietrzną pasażera.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 45 ust. 2 pkt 4: zakaz przy aktywnej poduszce powietrznej.',
            ],
            'art-45-ust-2-pkt-5' => [
                'type' => 'point',
                'label' => 'art. 45 ust. 2 pkt 5',
                'title' => 'Dziecko poniżej 3 lat w pojeździe bez pasów',
                'summary' => 'Zabroniony jest przewóz dziecka poniżej 3 lat w pojeździe kategorii M1, N1, N2 lub N3, który nie jest wyposażony w pasy bezpieczeństwa i urządzenie przytrzymujące.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 45 ust. 2 pkt 5: zakaz przewozu dziecka poniżej 3 lat w pojeździe bez wymaganych zabezpieczeń.',
            ],
            'art-45-ust-2-pkt-6' => [
                'type' => 'point',
                'label' => 'art. 45 ust. 2 pkt 6',
                'title' => 'Dziecko poniżej 150 cm na przednim siedzeniu',
                'summary' => 'Zabroniony jest przewóz dziecka mającego mniej niż 150 cm na przednim siedzeniu poza urządzeniem przytrzymującym.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 45 ust. 2 pkt 6: bezwzględny wymóg urządzenia przytrzymującego na przednim siedzeniu poniżej 150 cm.',
            ],
            'art-51-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 51 ust. 1',
                'title' => 'Całodobowy obowiązek używania świateł mijania',
                'summary' => 'Kierujący pojazdem używa świateł mijania podczas jazdy w warunkach normalnej przejrzystości powietrza.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 51 ust. 1: całoroczny i całodobowy obowiązek świateł mijania.',
            ],
            'art-51-ust-2' => [
                'type' => 'paragraph',
                'label' => 'art. 51 ust. 2',
                'title' => 'Kiedy światła dzienne mogą zastąpić mijania',
                'summary' => 'Od świtu do zmierzchu, przy normalnej przejrzystości powietrza, zamiast świateł mijania można używać świateł do jazdy dziennej.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 51 ust. 2: pora dnia i warunki dopuszczające światła dzienne.',
            ],
            'art-51-ust-3' => [
                'type' => 'paragraph',
                'label' => 'art. 51 ust. 3',
                'title' => 'Warunki używania świateł drogowych',
                'summary' => 'Od zmierzchu do świtu na nieoświetlonej drodze można używać świateł drogowych zamiast mijania lub razem z nimi, jeżeli nie oślepia się innych kierujących ani pieszych poruszających się w kolumnie.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 51 ust. 3: pora, brak oświetlenia drogi i bezwzględny warunek nieoślepiania.',
            ],
            'art-51-ust-4-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 51 ust. 4 pkt 1',
                'title' => 'Zmiana świateł przy pojeździe jadącym z przeciwka',
                'summary' => 'Światła drogowe trzeba przełączyć na mijania przy zbliżaniu się pojazdu nadjeżdżającego z przeciwka, także gdy jego kierujący niewłaściwie pozostawia własne światła drogowe.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 51 ust. 4 pkt 1: obowiązek zmiany świateł wobec pojazdu nadjeżdżającego z przeciwka.',
            ],
            'art-51-ust-4-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 51 ust. 4 pkt 2',
                'title' => 'Zmiana świateł przy pojeździe poprzedzającym',
                'summary' => 'Światła drogowe trzeba przełączyć na mijania przy zbliżaniu się do pojazdu poprzedzającego, jeżeli kierujący może zostać oślepiony.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 51 ust. 4 pkt 2: ochrona kierującego pojazdem jadącym przed nami, również po zakończeniu wyprzedzania.',
            ],
            'art-51-ust-5' => [
                'type' => 'paragraph',
                'label' => 'art. 51 ust. 5',
                'title' => 'Przednie światła przeciwmgłowe na drodze krętej',
                'summary' => 'Od zmierzchu do świtu na drodze krętej oznaczonej odpowiednimi znakami wolno używać przednich świateł przeciwmgłowych także przy normalnej przejrzystości powietrza.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 51 ust. 5: nocny wyjątek dla oznakowanej drogi krętej, niezależny od aktualnej przejrzystości powietrza.',
            ],
            'art-61-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 61 ust. 1',
                'title' => 'Zakaz przekraczania dopuszczalnej masy i ładowności',
                'summary' => 'Ładunek nie może powodować przekroczenia dopuszczalnej masy całkowitej ani dopuszczalnej ładowności pojazdu.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 1: zakaz przekraczania dopuszczalnej masy całkowitej i ładowności pojazdu.',
            ],
            'art-61-ust-2-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 61 ust. 2 pkt 2',
                'title' => 'Stateczność i kierowanie pojazdem z ładunkiem',
                'summary' => 'Ładunek trzeba umieścić tak, aby nie naruszał stateczności pojazdu i nie utrudniał kierowania.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 2 pkt 2: stateczność pojazdu oraz brak utrudnienia kierowania.',
            ],
            'art-61-ust-2-pkt-4' => [
                'type' => 'point',
                'label' => 'art. 61 ust. 2 pkt 4',
                'title' => 'Widoczność, światła i tablice nie mogą być zasłonięte',
                'summary' => 'Ładunek nie może ograniczać widoczności ani zasłaniać świateł, urządzeń sygnalizacyjnych, tablic rejestracyjnych lub innych oznaczeń.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 2 pkt 4: zakaz ograniczania widoczności i zasłaniania wyposażenia oraz oznaczeń.',
            ],
            'art-61-ust-3' => [
                'type' => 'paragraph',
                'label' => 'art. 61 ust. 3',
                'title' => 'Zabezpieczenie ładunku przed zmianą położenia',
                'summary' => 'Ładunek umieszczony na pojeździe musi być zabezpieczony przed zmianą położenia lub wywoływaniem nadmiernego hałasu.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 3: zabezpieczenie przed przemieszczaniem, hałasem i uciążliwością.',
            ],
            'art-61-ust-4' => [
                'type' => 'paragraph',
                'label' => 'art. 61 ust. 4',
                'title' => 'Zabezpieczenie urządzeń mocujących',
                'summary' => 'Urządzenia służące do mocowania ładunku trzeba zabezpieczyć przed rozluźnieniem, swobodnym zwisaniem lub spadnięciem podczas jazdy.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 4: zabezpieczenie pasów, lin i innych urządzeń mocujących.',
            ],
            'art-61-ust-5' => [
                'type' => 'paragraph',
                'label' => 'art. 61 ust. 5',
                'title' => 'Przewóz ładunku sypkiego',
                'summary' => 'Ładunek sypki może być przewożony wyłącznie w szczelnej skrzyni ładunkowej, dodatkowo zabezpieczonej odpowiednimi zasłonami.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 5: szczelna skrzynia i zasłony dla ładunku sypkiego.',
            ],
            'art-61-ust-6-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 61 ust. 6 pkt 2',
                'title' => 'Maksymalne wystawanie ładunku z tyłu',
                'summary' => 'Co do zasady ładunek nie może wystawać z tyłu dalej niż 2 m od tylnej płaszczyzny obrysu pojazdu lub zespołu pojazdów.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 6 pkt 2: limit 2 m dla ładunku wystającego z tyłu.',
            ],
            'art-61-ust-6-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 61 ust. 6 pkt 3',
                'title' => 'Maksymalne wystawanie ładunku z przodu',
                'summary' => 'Ładunek nie może wystawać z przodu dalej niż 0,5 m od przedniej płaszczyzny obrysu i 1,5 m od siedzenia kierującego.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 6 pkt 3: limity wystawania ładunku przed pojazd.',
            ],
            'art-61-ust-7' => [
                'type' => 'paragraph',
                'label' => 'art. 61 ust. 7',
                'title' => 'Drewno długie na przyczepie kłonicowej',
                'summary' => 'Przy przewozie drewna długiego na przyczepie kłonicowej dopuszcza się wystawanie ładunku z tyłu do 5 m od osi przyczepy.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 7: szczególny limit dla drewna długiego na przyczepie kłonicowej.',
            ],
            'art-61-ust-9-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 61 ust. 9 pkt 3',
                'title' => 'Oznakowanie ładunku wystającego z tyłu',
                'summary' => 'Ładunek wystający z tyłu oznacza się pasami białymi i czerwonymi na ładunku, tarczy lub bryle geometrycznej o wymaganej powierzchni.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 9 pkt 3: oznaczenie tylnego końca ładunku pasami białymi i czerwonymi.',
            ],
            'art-61-ust-9-pkt-4' => [
                'type' => 'point',
                'label' => 'art. 61 ust. 9 pkt 4',
                'title' => 'Czerwona chorągiewka przy samochodzie osobowym',
                'summary' => 'Ładunek wystający z tyłu samochodu osobowego lub jego przyczepy może być oznaczony czerwoną chorągiewką.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 61 ust. 9 pkt 4: uproszczone oznaczenie czerwoną chorągiewką.',
            ],
            'art-62-ust-1-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 62 ust. 1 pkt 1',
                'title' => 'Masa przyczepy ciągniętej przez samochód osobowy',
                'summary' => 'Rzeczywista masa całkowita przyczepy ciągniętej przez samochód osobowy nie może przekraczać rzeczywistej masy całkowitej samochodu.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 62 ust. 1 pkt 1: relacja mas przyczepy i samochodu osobowego.',
            ],
            'art-62-ust-1-pkt-3' => [
                'type' => 'point',
                'label' => 'art. 62 ust. 1 pkt 3',
                'title' => 'Masa przyczepy ciągniętej przez motocykl lub motorower',
                'summary' => 'Przyczepa motocykla lub motoroweru nie może być cięższa od pojazdu ciągnącego, a jej rzeczywista masa całkowita nie może przekraczać 100 kg.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 62 ust. 1 pkt 3: limit masy przyczepy motocykla i motoroweru.',
            ],
            'art-62-ust-4a-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 62 ust. 4a pkt 1',
                'title' => 'Długość motocykla lub motoroweru z przyczepą',
                'summary' => 'Długość zespołu złożonego z motocykla albo motoroweru i przyczepy nie może przekraczać 4 m.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 62 ust. 4a pkt 1: maksymalna długość 4 m.',
            ],
            'art-62-ust-4a-pkt-2' => [
                'type' => 'point',
                'label' => 'art. 62 ust. 4a pkt 2',
                'title' => 'Długość pojazdu z przyczepą',
                'summary' => 'Długość zespołu dwóch pojazdów, z wyjątkiem motocykla lub motoroweru z przyczepą, nie może przekraczać 18,75 m.',
                'last_checked_at' => $june21ReviewedAt,
                'source_check_notes' => 'Weryfikacja aktualnego tekstu Prawa o ruchu drogowym dla art. 62 ust. 4a pkt 2: maksymalna długość zespołu dwóch pojazdów 18,75 m.',
            ],
            'art-49' => [
                'label' => 'art. 49',
                'title' => 'Zakazy zatrzymania i postoju',
                'summary' => 'Ten przepis wskazuje sytuacje i miejsca, w których zatrzymanie albo postój pojazdu są zabronione.',
                'last_checked_at' => $june13ReviewedAt,
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu ujednoliconego Prawa o ruchu drogowym dla art. 49: zakazy zatrzymania, zakazy postoju i wyjątek dla zatrzymania wynikającego z warunków lub przepisów ruchu.',
            ],
            'art-49-ust-1-pkt-5' => [
                'type' => 'point',
                'label' => 'art. 49 ust. 1 pkt 5',
                'title' => 'Zakaz zatrzymania obok linii wyznaczającej krawędź jezdni',
                'summary' => 'Zabronione jest zatrzymanie na jezdni obok przerywanej linii krawędziowej oraz na jezdni i poboczu obok ciągłej linii wyznaczającej krawędź jezdni.',
                'last_checked_at' => $june20ReviewedAt,
                'source_check_notes' => 'Weryfikacja tekstu ujednoliconego Prawa o ruchu drogowym z 3 marca 2026 r. dla art. 49 ust. 1 pkt 5: zakaz zatrzymania przy przerywanej i ciągłej linii krawędziowej.',
            ],
        ];

        $units = [];

        foreach ($definitions as $slug => $definition) {
            $unitReviewedAt = $definition['last_checked_at'] ?? $reviewedAt;

            $unit = LegalUnit::query()->updateOrCreate(
                [
                    'legal_act_id' => $act->getKey(),
                    'slug' => $slug,
                ],
                [
                    'type' => $definition['type'] ?? 'article',
                    'label' => $definition['label'],
                    'title' => $definition['title'],
                    'summary' => $definition['summary'],
                    'source_url' => self::ROAD_TRAFFIC_ACT_URL,
                    'last_checked_at' => $unitReviewedAt,
                    'status' => LegalUnit::STATUS_VERIFIED,
                ],
            );

            LegalSourceCheck::query()->updateOrCreate(
                [
                    'legal_unit_id' => $unit->getKey(),
                    'source_url' => self::ROAD_TRAFFIC_ACT_URL,
                    'checked_at' => $unitReviewedAt,
                ],
                [
                    'checked_by' => $reviewer->getKey(),
                    'source_status' => 'available',
                    'notes' => $definition['source_check_notes'] ?? 'MVP: weryfikacja oficjalnego źródła ISAP dla aktu Prawo o ruchu drogowym.',
                ],
            );

            $units[$slug] = $unit;
        }

        return $units;
    }

    /**
     * @return array<string, LegalUnit>
     */
    private function upsertTrafficSignsUnits(LegalAct $act, ContentAuthor $reviewer, Carbon $reviewedAt): array
    {
        $definitions = [
            'par-21-ust-1-4' => [
                'type' => 'paragraph',
                'label' => '§ 21 ust. 1 i 4',
                'title' => 'Znak B-20 STOP przed przejazdem kolejowym',
                'summary' => 'Znak B-20 wymaga zatrzymania przed drogą z pierwszeństwem lub przed przejazdem kolejowym; zatrzymanie powinno nastąpić w wyznaczonym miejscu, a przy jego braku tam, gdzie można upewnić się, że ruch jest bezpieczny.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 21 ust. 1 i 4: obowiązek zatrzymania oraz miejsce zatrzymania przy znaku B-20.',
            ],
            'par-22-ust-1-2' => [
                'type' => 'paragraph',
                'label' => '§ 22 ust. 1-2',
                'title' => 'Zakaz zawracania wynikający ze znaku B-21',
                'summary' => 'Znak B-21 zabrania skręcania w lewo i zawracania, a zakaz obowiązuje na najbliższym skrzyżowaniu, z wyjątkami określonymi w rozporządzeniu.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 22 ust. 1-2: znaczenie znaku B-21, brak zakazu zawracania wynikającego z B-22 oraz zakres obowiązywania na najbliższym skrzyżowaniu.',
            ],
            'par-22-ust-5' => [
                'type' => 'paragraph',
                'label' => '§ 22 ust. 5',
                'title' => 'Zakaz zawracania wynikający ze znaku B-23',
                'summary' => 'Znak B-23 zabrania zawracania od miejsca ustawienia znaku do najbliższego skrzyżowania włącznie; zakaz kończy także znak B-24.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 22 ust. 5: zakres zakazu B-23 od znaku do najbliższego skrzyżowania włącznie oraz sposób jego zakończenia.',
            ],
            'par-36-ust-2' => [
                'type' => 'paragraph',
                'label' => '§ 36 ust. 2',
                'title' => 'Pierwszeństwo na skrzyżowaniu oznaczonym A-7 i C-12',
                'summary' => 'Zestaw znaków C-12 i A-7 oznacza pierwszeństwo kierującego znajdującego się na skrzyżowaniu przed kierującym, który na nie wjeżdża.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia w sprawie znaków i sygnałów drogowych dla § 36 ust. 2: znaczenie zestawu znaków A-7 i C-12.',
            ],
            'par-58-ust-3' => [
                'type' => 'paragraph',
                'label' => '§ 58 ust. 3',
                'title' => 'Znaki początku i końca obszaru zabudowanego',
                'summary' => 'Znak D-42 oznacza wjazd na obszar zabudowany, a znak D-43 oznacza wyjazd z obszaru zabudowanego.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 58 ust. 3: znaczenie znaków D-42 i D-43.',
            ],
            'par-87-ust-1' => [
                'type' => 'paragraph',
                'label' => '§ 87 ust. 1',
                'title' => 'Kierunki jazdy dozwolone z pasa ruchu',
                'summary' => 'Strzałki kierunkowe P-8 wskazują kierunki jazdy dozwolone z pasa ruchu, na którym zostały umieszczone.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 87 ust. 1: znaczenie strzałek kierunkowych P-8 jako dozwolonych kierunków jazdy z danego pasa.',
            ],
            'par-87-ust-2' => [
                'type' => 'paragraph',
                'label' => '§ 87 ust. 2',
                'title' => 'Zawracanie ze skrajnego lewego pasa ze strzałką P-8b',
                'summary' => 'Strzałka do skręcania w lewo na skrajnym lewym pasie zezwala także na zawracanie, chyba że zabrania tego znak B-23 lub ruch jest kierowany sygnalizatorem S-3.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 87 ust. 2: dodatkowe zezwolenie na zawracanie ze skrajnego lewego pasa i wyjątek przy sygnalizatorze S-3.',
            ],
            'par-95-ust-1-pkt-4' => [
                'type' => 'point',
                'label' => '§ 95 ust. 1 pkt 4',
                'title' => 'Jednoczesny sygnał czerwony i żółty',
                'summary' => 'Jednoczesne sygnały czerwony i żółty zakazują wjazdu za sygnalizator i zapowiadają, że za chwilę zapali się sygnał zielony.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia w sprawie znaków i sygnałów drogowych dla § 95 ust. 1 pkt 4: znaczenie jednoczesnego sygnału czerwonego i żółtego.',
            ],
            'par-95-ust-1-pkt-2' => [
                'type' => 'point',
                'label' => '§ 95 ust. 1 pkt 2',
                'title' => 'Stały sygnał żółty',
                'summary' => 'Stały sygnał żółty zakazuje wjazdu za sygnalizator, chyba że pojazd jest tak blisko, że nie można go zatrzymać bez gwałtownego hamowania; zapowiada sygnał czerwony.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 95 ust. 1 pkt 2: zakaz wjazdu, wyjątek dla braku możliwości bezpiecznego zatrzymania i zapowiedź sygnału czerwonego.',
            ],
            'par-96-ust-2-3' => [
                'type' => 'paragraph',
                'label' => '§ 96 ust. 2-3',
                'title' => 'Warunkowe zawracanie przy zielonej strzałce w lewo',
                'summary' => 'Zielona strzałka w lewo przy sygnale czerwonym zezwala po zatrzymaniu także na zawracanie z lewego skrajnego pasa, chyba że zabrania tego znak B-23.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 96 ust. 2-3: obowiązek zatrzymania przed sygnalizatorem S-2 oraz możliwość zawracania przy zielonej strzałce w lewo, o ile nie obowiązuje B-23.',
            ],
            'par-96-ust-1' => [
                'type' => 'paragraph',
                'label' => '§ 96 ust. 1',
                'title' => 'Zielona strzałka zezwala na jazdę we wskazanym kierunku',
                'summary' => 'Sygnał w kształcie zielonej strzałki nadawany przy sygnale czerwonym zezwala na ruch w kierunku wskazanym strzałką.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 96 ust. 1: zakres zezwolenia wynikającego z sygnału w kształcie zielonej strzałki.',
            ],
            'par-96-ust-2' => [
                'type' => 'paragraph',
                'label' => '§ 96 ust. 2',
                'title' => 'Zielona strzałka w lewo a zawracanie',
                'summary' => 'Strzałka w lewo zezwala także na zawracanie z lewego skrajnego pasa, jeżeli nie zabrania tego znak B-23.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 96 ust. 2: dodatkowe zezwolenie na zawracanie przy strzałce w lewo i wyjątek dla znaku B-23.',
            ],
            'par-96-ust-3' => [
                'type' => 'paragraph',
                'label' => '§ 96 ust. 3',
                'title' => 'Obowiązkowe zatrzymanie i brak utrudnienia ruchu',
                'summary' => 'Jazda na zielonej strzałce jest dozwolona dopiero po zatrzymaniu przed sygnalizatorem i tylko wtedy, gdy nie spowoduje utrudnienia ruchu innym uczestnikom.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 96 ust. 3: dwa łączne warunki przejazdu, czyli zatrzymanie i brak utrudnienia ruchu.',
            ],
            'par-97-ust-1' => [
                'type' => 'paragraph',
                'label' => '§ 97 ust. 1',
                'title' => 'Kierunki dozwolone przez sygnalizator S-3',
                'summary' => 'Sygnalizator kierunkowy S-3 zezwala na ruch wyłącznie w kierunkach wskazanych strzałkami.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 97 ust. 1: ograniczenie ruchu do kierunków wskazanych przez sygnalizator kierunkowy S-3.',
            ],
            'par-97-ust-3' => [
                'type' => 'paragraph',
                'label' => '§ 97 ust. 3',
                'title' => 'Bezkolizyjny przebieg ruchu przy sygnalizatorze S-3',
                'summary' => 'Zielony sygnał kierunkowy S-3 oznacza, że podczas jazdy we wskazanym kierunku nie występuje kolizja z innymi uczestnikami ruchu.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 97 ust. 3: znaczenie bezkolizyjności ruchu wskazanego przez sygnalizator kierunkowy.',
            ],
            'par-98-ust-5' => [
                'type' => 'paragraph',
                'label' => '§ 98 ust. 5',
                'title' => 'Czerwony sygnał migający na przejeździe',
                'summary' => 'Czerwony sygnał migający lub dwa naprzemiennie migające sygnały czerwone zakazują wjazdu na przejazd albo inną część drogi.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 98 ust. 5: bezwzględny zakaz wjazdu przy czerwonym sygnale migającym.',
            ],
            'par-98-ust-6' => [
                'type' => 'paragraph',
                'label' => '§ 98 ust. 6',
                'title' => 'Żółty sygnał migający ostrzega o niebezpieczeństwie',
                'summary' => 'Migający żółty sygnał ostrzega o niebezpieczeństwie lub utrudnieniu ruchu i nakazuje zachować szczególną ostrożność, ale sam nie wprowadza obowiązku zatrzymania.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia dla § 98 ust. 6: funkcja ostrzegawcza migającego żółtego sygnału i obowiązek szczególnej ostrożności.',
            ],
            'par-108-ust-2' => [
                'type' => 'paragraph',
                'label' => '§ 108 ust. 2',
                'title' => 'Kierujący ruchem zwrócony bokiem',
                'summary' => 'Postawa osoby kierującej ruchem zwróconej bokiem do nadjeżdżających pojazdów oznacza zezwolenie na wjazd na skrzyżowanie lub odcinek drogi za tą osobą.',
                'source_check_notes' => 'Weryfikacja oficjalnego tekstu rozporządzenia w sprawie znaków i sygnałów drogowych dla § 108 ust. 2: znaczenie postawy osoby kierującej ruchem zwróconej bokiem.',
            ],
        ];

        $units = [];

        foreach ($definitions as $slug => $definition) {
            $unit = LegalUnit::query()->updateOrCreate(
                [
                    'legal_act_id' => $act->getKey(),
                    'slug' => $slug,
                ],
                [
                    'type' => $definition['type'],
                    'label' => $definition['label'],
                    'title' => $definition['title'],
                    'summary' => $definition['summary'],
                    'source_url' => self::TRAFFIC_SIGNS_REGULATION_URL,
                    'last_checked_at' => $reviewedAt,
                    'status' => LegalUnit::STATUS_VERIFIED,
                ],
            );

            LegalSourceCheck::query()->updateOrCreate(
                [
                    'legal_unit_id' => $unit->getKey(),
                    'source_url' => self::TRAFFIC_SIGNS_REGULATION_URL,
                    'checked_at' => $reviewedAt,
                ],
                [
                    'checked_by' => $reviewer->getKey(),
                    'source_status' => 'available',
                    'notes' => $definition['source_check_notes'],
                ],
            );

            $units[$slug] = $unit;
        }

        return $units;
    }

    /**
     * @return array<string, LegalUnit>
     */
    private function upsertVehicleTechnicalUnits(LegalAct $act, ContentAuthor $reviewer, Carbon $reviewedAt): array
    {
        $definitions = [
            'par-11-ust-1-pkt-5c' => [
                'type' => 'point',
                'label' => '§ 11 ust. 1 pkt 5c',
                'title' => 'Dwa lusterka motocykla szybszego niż 100 km/h',
                'summary' => 'Motocykl jednośladowy o prędkości maksymalnej przekraczającej 100 km/h wyposaża się w co najmniej dwa lusterka zewnętrzne, po jednym z każdej strony pojazdu.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 11 ust. 1 pkt 5 lit. c oraz nowelizacji Dz.U. z 2024 r. poz. 983 i 1417, które nie zmieniły tego wymagania.',
            ],
            'par-11-ust-1-pkt-6' => [
                'type' => 'point',
                'label' => '§ 11 ust. 1 pkt 6',
                'title' => 'Sygnał dźwiękowy pojazdu samochodowego',
                'summary' => 'Pojazd samochodowy wyposaża się w sygnał dźwiękowy o ciągłym i nieprzeraźliwym tonie, którego poziom dźwięku nie przekracza 112 dB(A).',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego rozporządzenia w sprawie warunków technicznych pojazdów, Dz.U. z 2024 r. poz. 502, dla § 11 ust. 1 pkt 6: ciągły, nieprzeraźliwy ton i maksymalny poziom dźwięku.',
            ],
            'par-11-ust-1-pkt-13' => [
                'type' => 'point',
                'label' => '§ 11 ust. 1 pkt 13',
                'title' => 'Trójkąt ostrzegawczy ze znakiem homologacji',
                'summary' => 'Pojazd samochodowy wyposaża się w trójkąt do ustawiania na drodze, przeznaczony do ostrzegania o obecności unieruchomionego pojazdu.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 11 ust. 1 pkt 13 oraz późniejszych nowelizacji, które nie zmieniły obowiązku posiadania trójkąta.',
            ],
            'par-11-ust-1-pkt-14' => [
                'type' => 'point',
                'label' => '§ 11 ust. 1 pkt 14',
                'title' => 'Gaśnica łatwo dostępna w razie potrzeby',
                'summary' => 'Pojazd samochodowy wyposaża się w gaśnicę umieszczoną w miejscu łatwo dostępnym w razie potrzeby.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 11 ust. 1 pkt 14 oraz późniejszych nowelizacji, które nie zmieniły wymagania dostępności gaśnicy.',
            ],
            'par-11-ust-5' => [
                'type' => 'paragraph',
                'label' => '§ 11 ust. 5',
                'title' => 'Ciśnienie w ogumieniu zgodne z zaleceniami',
                'summary' => 'Ciśnienie w ogumieniu powinno odpowiadać wartości zalecanej przez producenta pojazdu albo opony dla danego obciążenia.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 11 ust. 5 oraz nowelizacji Dz.U. z 2024 r. poz. 983 i 1417, które nie zmieniły tej reguły.',
            ],
            'par-11-ust-7-pkt-1' => [
                'type' => 'point',
                'label' => '§ 11 ust. 7 pkt 1',
                'title' => 'Jednakowa konstrukcja opon na jednej osi',
                'summary' => 'Na kołach jednej osi nie wolno stosować opon o różnej konstrukcji, w tym o różnej rzeźbie bieżnika.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 11 ust. 7 pkt 1 oraz późniejszych nowelizacji, które nie zmieniły zakazu mieszania konstrukcji opon na osi.',
            ],
            'par-11-ust-7-pkt-4' => [
                'type' => 'point',
                'label' => '§ 11 ust. 7 pkt 4',
                'title' => 'Graniczne zużycie bieżnika i minimum 1,6 mm',
                'summary' => 'Nie wolno używać opony zużytej ponad wskaźnik granicznego zużycia, a gdy nie ma takiego wskaźnika, opony z bieżnikiem płytszym niż 1,6 mm.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 11 ust. 7 pkt 4 oraz późniejszych nowelizacji, które nie zmieniły ogólnego minimum 1,6 mm.',
            ],
            'par-12-ust-1-pkt-11' => [
                'type' => 'point',
                'label' => '§ 12 ust. 1 pkt 11',
                'title' => 'Boczne światła odblaskowe przyczepy',
                'summary' => 'Przyczepa powinna być wyposażona w boczne światła odblaskowe o barwie żółtej samochodowej.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego rozporządzenia w sprawie warunków technicznych pojazdów, Dz.U. z 2024 r. poz. 502, dla § 12 ust. 1 pkt 11: obowiązkowe boczne światła odblaskowe przyczepy.',
            ],
            'par-12-ust-3-pkt-11' => [
                'type' => 'point',
                'label' => '§ 12 ust. 3 pkt 11',
                'title' => 'Dopuszczalne światła awaryjne motocykla',
                'summary' => 'Motocykl może być wyposażony w światła awaryjne, ale przepis nie ustanawia ich jako obowiązkowego wyposażenia motocykla.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 12 ust. 3 pkt 11 oraz późniejszych nowelizacji, które nie zmieniły dopuszczalności świateł awaryjnych motocykla.',
            ],
            'par-40-ust-2-pkt-8' => [
                'type' => 'point',
                'label' => '§ 40 ust. 2 pkt 8',
                'title' => 'Dwie gaśnice przy przewozie osób poza kabiną',
                'summary' => 'Samochód ciężarowy przewożący osoby poza kabiną kierowcy wyposaża się w dwie gaśnice: jedną przy kabinie i drugą wewnątrz przestrzeni przewozowej.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 40 ust. 2 pkt 8 oraz późniejszych nowelizacji, które nie zmieniły liczby ani rozmieszczenia gaśnic.',
            ],
            'par-46-ust-1-pkt-2' => [
                'type' => 'point',
                'label' => '§ 46 ust. 1 pkt 2',
                'title' => 'Gaśnica jako wyposażenie ciągnika rolniczego',
                'summary' => 'Do ciągnika rolniczego stosuje się między innymi wymaganie § 11 ust. 1 pkt 14 dotyczące gaśnicy.',
                'source_check_notes' => 'Weryfikacja tekstu jednolitego Dz.U. z 2024 r. poz. 502 dla § 46 ust. 1 pkt 2 w związku z § 11 ust. 1 pkt 14 oraz późniejszych nowelizacji.',
            ],
        ];

        $units = [];

        foreach ($definitions as $slug => $definition) {
            $unit = LegalUnit::query()->updateOrCreate(
                [
                    'legal_act_id' => $act->getKey(),
                    'slug' => $slug,
                ],
                [
                    'type' => $definition['type'],
                    'label' => $definition['label'],
                    'title' => $definition['title'],
                    'summary' => $definition['summary'],
                    'source_url' => self::VEHICLE_TECHNICAL_REGULATION_URL,
                    'last_checked_at' => $reviewedAt,
                    'status' => LegalUnit::STATUS_VERIFIED,
                ],
            );

            LegalSourceCheck::query()->updateOrCreate(
                [
                    'legal_unit_id' => $unit->getKey(),
                    'source_url' => self::VEHICLE_TECHNICAL_REGULATION_URL,
                    'checked_at' => $reviewedAt,
                ],
                [
                    'checked_by' => $reviewer->getKey(),
                    'source_status' => 'available',
                    'notes' => $definition['source_check_notes'],
                ],
            );

            $units[$slug] = $unit;
        }

        return $units;
    }

    /**
     * @return array<string, LegalUnit>
     */
    private function upsertDriversActUnits(LegalAct $act, ContentAuthor $reviewer, Carbon $reviewedAt): array
    {
        $definitions = [
            'ukp-art-3-ust-1-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 3 ust. 1 pkt 1',
                'title' => 'Wymagane umiejętności i odpowiedni dokument',
                'summary' => 'Kierującym pojazdem może być osoba, która osiągnęła wymagany wiek, jest sprawna pod względem fizycznym i psychicznym, ma umiejętność kierowania w sposób niezagrażający bezpieczeństwu oraz odpowiedni dokument stwierdzający uprawnienie.',
            ],
            'ukp-art-6-ust-1-pkt-1-lit-a' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 1 lit. a',
                'title' => 'Kategoria AM i motorower',
                'summary' => 'Prawo jazdy kategorii AM uprawnia do kierowania motorowerem.',
            ],
            'ukp-art-6-ust-1-pkt-1-lit-b' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 1 lit. b',
                'title' => 'Kategoria AM i czterokołowiec lekki',
                'summary' => 'Prawo jazdy kategorii AM uprawnia do kierowania czterokołowcem lekkim.',
            ],
            'ukp-art-6-ust-1-pkt-2-lit-b' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 2 lit. b',
                'title' => 'Kategoria A1 i motocykl do 125 cm3',
                'summary' => 'Kategoria A1 obejmuje motocykl o pojemności do 125 cm3, mocy do 11 kW i stosunku mocy do masy własnej do 0,1 kW/kg.',
            ],
            'ukp-art-6-ust-1-pkt-2-lit-c' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 2 lit. c',
                'title' => 'Kategoria A1 i motocykl trójkołowy',
                'summary' => 'Kategoria A1 uprawnia do kierowania motocyklem trójkołowym o mocy nieprzekraczającej 15 kW.',
            ],
            'ukp-art-6-ust-1-pkt-3-lit-b' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 3 lit. b',
                'title' => 'Kategoria A2 i motocykl trójkołowy',
                'summary' => 'Kategoria A2 uprawnia do kierowania motocyklem trójkołowym o mocy nieprzekraczającej 15 kW.',
            ],
            'ukp-art-6-ust-1-pkt-3-lit-c' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 3 lit. c',
                'title' => 'Kategoria A2 obejmuje pojazdy kategorii AM',
                'summary' => 'Prawo jazdy kategorii A2 obejmuje również pojazdy określone dla kategorii AM.',
            ],
            'ukp-art-6-ust-1-pkt-4-lit-a' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 4 lit. a',
                'title' => 'Kategoria A i motocykl',
                'summary' => 'Prawo jazdy kategorii A uprawnia do kierowania motocyklem.',
            ],
            'ukp-art-6-ust-1-pkt-5-lit-a' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 5 lit. a',
                'title' => 'Kategoria B1 i czterokołowiec',
                'summary' => 'Prawo jazdy kategorii B1 uprawnia do kierowania czterokołowcem.',
            ],
            'ukp-art-6-ust-1-pkt-5-lit-b' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 5 lit. b',
                'title' => 'Kategoria B1 obejmuje pojazdy kategorii AM',
                'summary' => 'Prawo jazdy kategorii B1 obejmuje również pojazdy określone dla kategorii AM.',
            ],
            'ukp-art-6-ust-1-pkt-6-lit-a-b' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 6 lit. a-b',
                'title' => 'Podstawowy zakres kategorii B',
                'summary' => 'Kategoria B obejmuje pojazd samochodowy o dopuszczalnej masie całkowitej do 3,5 t, z wyjątkiem autobusu i motocykla, oraz taki pojazd z przyczepą lekką.',
            ],
            'ukp-art-6-ust-1-pkt-11-lit-a' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 11 lit. a',
                'title' => 'Kategoria T i ciągnik rolniczy',
                'summary' => 'Prawo jazdy kategorii T uprawnia do kierowania ciągnikiem rolniczym oraz pojazdem wolnobieżnym.',
            ],
            'ukp-art-6-ust-1-pkt-11-lit-b' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 1 pkt 11 lit. b',
                'title' => 'Kategoria T i zespół pojazdów z przyczepą',
                'summary' => 'Kategoria T obejmuje zespół złożony z ciągnika rolniczego albo pojazdu wolnobieżnego i przyczepy lub przyczep.',
            ],
            'ukp-art-6-ust-3-pkt-1' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 3 pkt 1',
                'title' => 'Kategoria B a ciągnik z przyczepą lekką w Polsce',
                'summary' => 'Na terytorium Polski kategorie B, C1, C, D1 i D uprawniają do kierowania ciągnikiem rolniczym lub pojazdem wolnobieżnym także z przyczepą lekką.',
            ],
            'ukp-art-6-ust-3-pkt-4-lit-a' => [
                'type' => 'point',
                'label' => 'art. 6 ust. 3 pkt 4 lit. a',
                'title' => 'Kategoria B od 3 lat a motocykl 125 cm3',
                'summary' => 'Na terytorium Polski posiadacz kategorii B od co najmniej 3 lat może kierować motocyklem do 125 cm3, 11 kW i 0,1 kW/kg.',
            ],
            'ukp-art-13-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 13 ust. 1',
                'title' => 'Okres ważności prawa jazdy',
                'summary' => 'Prawo jazdy wydaje się na okres wskazany w ustawie; po upływie terminu dokument nie potwierdza dalej ważnego uprawnienia w zakresie wymagającym odnowienia.',
            ],
            'ukp-art-18-ust-1' => [
                'type' => 'paragraph',
                'label' => 'art. 18 ust. 1',
                'title' => 'Zgłoszenie utraty, zniszczenia lub zmiany danych',
                'summary' => 'Posiadacz prawa jazdy zawiadamia starostę w terminie 30 dni o utracie dokumentu, jego zniszczeniu w stopniu powodującym nieczytelność albo zmianie danych wymagającej wydania nowego dokumentu.',
            ],
        ];

        $units = [];

        foreach ($definitions as $slug => $definition) {
            $unit = LegalUnit::query()->updateOrCreate(
                [
                    'legal_act_id' => $act->getKey(),
                    'slug' => $slug,
                ],
                [
                    'type' => $definition['type'],
                    'label' => $definition['label'],
                    'title' => $definition['title'],
                    'summary' => $definition['summary'],
                    'source_url' => self::DRIVERS_ACT_URL,
                    'last_checked_at' => $reviewedAt,
                    'status' => LegalUnit::STATUS_VERIFIED,
                ],
            );

            LegalSourceCheck::query()->updateOrCreate(
                [
                    'legal_unit_id' => $unit->getKey(),
                    'source_url' => self::DRIVERS_ACT_URL,
                    'checked_at' => $reviewedAt,
                ],
                [
                    'checked_by' => $reviewer->getKey(),
                    'source_status' => 'available',
                    'notes' => 'Weryfikacja tekstu jednolitego ustawy o kierujących pojazdami, Dz.U. z 2025 r. poz. 1226, oraz późniejszych nowelizacji obowiązujących 22 czerwca 2026 r.',
                ],
            );

            $units[$slug] = $unit;
        }

        return $units;
    }

    /**
     * @return array<string, LegalUnit>
     */
    private function upsertSobrietyActUnits(LegalAct $act, ContentAuthor $reviewer, Carbon $reviewedAt): array
    {
        $definitions = [
            'sobriety-art-46-ust-2' => [
                'label' => 'art. 46 ust. 2',
                'title' => 'Stan po użyciu alkoholu',
                'summary' => 'Stan po użyciu alkoholu zachodzi przy stężeniu od 0,2‰ do 0,5‰ we krwi albo od 0,1 mg do 0,25 mg alkoholu w 1 dm3 wydychanego powietrza.',
                'notes' => 'Weryfikacja oficjalnego tekstu ustawy o wychowaniu w trzeźwości dla art. 46 ust. 2: oba alternatywne progi stanu po użyciu alkoholu.',
            ],
            'sobriety-art-46-ust-3' => [
                'label' => 'art. 46 ust. 3',
                'title' => 'Stan nietrzeźwości',
                'summary' => 'Stan nietrzeźwości zachodzi przy stężeniu powyżej 0,5‰ we krwi albo powyżej 0,25 mg alkoholu w 1 dm3 wydychanego powietrza.',
                'notes' => 'Weryfikacja oficjalnego tekstu ustawy o wychowaniu w trzeźwości dla art. 46 ust. 3: oba alternatywne progi stanu nietrzeźwości.',
            ],
        ];

        $units = [];

        foreach ($definitions as $slug => $definition) {
            $unit = LegalUnit::query()->updateOrCreate(
                ['legal_act_id' => $act->getKey(), 'slug' => $slug],
                [
                    'type' => 'paragraph',
                    'label' => $definition['label'],
                    'title' => $definition['title'],
                    'summary' => $definition['summary'],
                    'source_url' => self::SOBRIETY_ACT_URL,
                    'last_checked_at' => $reviewedAt,
                    'status' => LegalUnit::STATUS_VERIFIED,
                ],
            );

            LegalSourceCheck::query()->updateOrCreate(
                ['legal_unit_id' => $unit->getKey(), 'source_url' => self::SOBRIETY_ACT_URL, 'checked_at' => $reviewedAt],
                ['checked_by' => $reviewer->getKey(), 'source_status' => 'available', 'notes' => $definition['notes']],
            );

            $units[$slug] = $unit;
        }

        return $units;
    }

    /**
     * @return array<string, LegalUnit>
     */
    private function upsertPettyOffencesUnits(LegalAct $act, ContentAuthor $reviewer, Carbon $reviewedAt): array
    {
        $unit = LegalUnit::query()->updateOrCreate(
            ['legal_act_id' => $act->getKey(), 'slug' => 'kw-art-87-par-1'],
            [
                'type' => 'paragraph',
                'label' => 'art. 87 § 1',
                'title' => 'Prowadzenie pojazdu mechanicznego w stanie po użyciu alkoholu',
                'summary' => 'Prowadzenie pojazdu mechanicznego w ruchu lądowym, wodnym lub powietrznym w stanie po użyciu alkoholu albo podobnie działającego środka jest wykroczeniem zagrożonym aresztem albo grzywną nie niższą niż 2500 zł.',
                'source_url' => self::PETTY_OFFENCES_CODE_URL,
                'last_checked_at' => $reviewedAt,
                'status' => LegalUnit::STATUS_VERIFIED,
            ],
        );

        LegalSourceCheck::query()->updateOrCreate(
            ['legal_unit_id' => $unit->getKey(), 'source_url' => self::PETTY_OFFENCES_CODE_URL, 'checked_at' => $reviewedAt],
            ['checked_by' => $reviewer->getKey(), 'source_status' => 'available', 'notes' => 'Weryfikacja oficjalnego tekstu Kodeksu wykroczeń dla art. 87 § 1: stan po użyciu alkoholu lub podobnego środka, pojazd mechaniczny i ustawowe zagrożenie karą.'],
        );

        return ['kw-art-87-par-1' => $unit];
    }

    /**
     * @return array<string, LegalUnit>
     */
    private function upsertPenalCodeUnits(LegalAct $act, ContentAuthor $reviewer, Carbon $reviewedAt): array
    {
        $unit = LegalUnit::query()->updateOrCreate(
            ['legal_act_id' => $act->getKey(), 'slug' => 'kk-art-178a-par-1'],
            [
                'type' => 'paragraph',
                'label' => 'art. 178a § 1',
                'title' => 'Prowadzenie pojazdu mechanicznego w stanie nietrzeźwości',
                'summary' => 'Prowadzenie pojazdu mechanicznego w ruchu lądowym, wodnym lub powietrznym w stanie nietrzeźwości albo pod wpływem środka odurzającego jest przestępstwem zagrożonym karą pozbawienia wolności do 3 lat.',
                'source_url' => self::PENAL_CODE_URL,
                'last_checked_at' => $reviewedAt,
                'status' => LegalUnit::STATUS_VERIFIED,
            ],
        );

        LegalSourceCheck::query()->updateOrCreate(
            ['legal_unit_id' => $unit->getKey(), 'source_url' => self::PENAL_CODE_URL, 'checked_at' => $reviewedAt],
            ['checked_by' => $reviewer->getKey(), 'source_status' => 'available', 'notes' => 'Weryfikacja oficjalnego tekstu Kodeksu karnego dla art. 178a § 1: stan nietrzeźwości lub środek odurzający, pojazd mechaniczny i kara do 3 lat pozbawienia wolności.'],
        );

        return ['kk-art-178a-par-1' => $unit];
    }

    /**
     * @return array<string, LegalTopic>
     */
    private function upsertTopics(Carbon $publishedAt, Carbon $june13PublishedAt, Carbon $june19PublishedAt, Carbon $june20PublishedAt, Carbon $june21PublishedAt, Carbon $june22PublishedAt): array
    {
        $topics = [];
        $definitions = [
            'zatrzymanie-i-postoj' => ['title' => 'Zatrzymanie i postój', 'sort_order' => 10, 'description' => 'Zasady zatrzymywania pojazdu, postoju oraz miejsc, w których takie zachowanie jest zabronione.'],
            'tramwaje-i-przystanki' => ['title' => 'Tramwaje i przystanki', 'sort_order' => 20, 'description' => 'Zachowanie kierującego przy przystankach tramwajowych oraz bezpieczeństwo pasażerów.'],
            'piesi-i-przejscia' => ['title' => 'Piesi i przejścia dla pieszych', 'sort_order' => 30, 'description' => 'Obowiązki kierujących wobec pieszych i zasady szczególnej ostrożności w rejonie przejść.'],
            'pierwszenstwo-przejazdu' => ['title' => 'Pierwszeństwo przejazdu', 'sort_order' => 40, 'description' => 'Zasady ustępowania pierwszeństwa na skrzyżowaniach i przy zmianie kierunku jazdy.'],
            'sygnalizacja-i-osoby-kierujace-ruchem' => ['title' => 'Sygnalizacja i osoby kierujące ruchem', 'sort_order' => 50, 'description' => 'Hierarchia poleceń, sygnałów świetlnych, znaków drogowych i zasad ogólnych.'],
            'predkosc-odstep-i-hamowanie' => ['title' => 'Prędkość, odstęp i hamowanie', 'sort_order' => 60, 'description' => 'Dobór bezpiecznej prędkości, limity prędkości, odstęp od pojazdu i hamowanie bez tworzenia zagrożenia.', 'published_at' => $june13PublishedAt],
            'zmiana-kierunku-i-pasa-ruchu' => ['title' => 'Zmiana kierunku i pasa ruchu', 'sort_order' => 70, 'description' => 'Szczególna ostrożność, właściwe ustawienie pojazdu, kierunkowskazy, zmiana pasa ruchu i zawracanie.', 'published_at' => $june13PublishedAt],
            'wlaczanie-sie-do-ruchu' => ['title' => 'Włączanie się do ruchu', 'sort_order' => 80, 'description' => 'Wyjazd z posesji, parkingu, strefy zamieszkania lub pobocza oraz obowiązek szczególnej ostrożności i ustąpienia pierwszeństwa.', 'published_at' => $june13PublishedAt],
            'wymijanie-omijanie-cofanie' => ['title' => 'Wymijanie, omijanie i cofanie', 'sort_order' => 90, 'description' => 'Bezpieczny odstęp przy wymijaniu i omijaniu, ostrożność wobec przeszkód oraz obowiązki i zakazy podczas cofania.', 'published_at' => $june13PublishedAt],
            'wyprzedzanie' => ['title' => 'Wyprzedzanie', 'sort_order' => 100, 'description' => 'Warunki rozpoczęcia wyprzedzania, bezpieczny odstęp, minimum 1 m wobec wybranych uczestników oraz zakazy wyprzedzania.', 'published_at' => $june13PublishedAt],
            'autobus-wyjezdzajacy-z-przystanku' => ['title' => 'Autobus wyjeżdżający z przystanku', 'sort_order' => 110, 'description' => 'Obowiązki kierujących przy sygnalizowanym wyjeździe autobusu lub trolejbusu z oznaczonego przystanku na obszarze zabudowanym.', 'published_at' => $june19PublishedAt],
            'zawracanie' => ['title' => 'Zawracanie', 'sort_order' => 120, 'description' => 'Ustawowe zakazy zawracania, wpływ znaków B-21 i B-23, strzałek P-8, sygnalizatorów S-3 oraz zasady bezpiecznego wykonania manewru.', 'published_at' => $june20PublishedAt],
            'sygnal-dzwiekowy' => ['title' => 'Sygnał dźwiękowy', 'sort_order' => 130, 'description' => 'Dozwolone ostrzeganie o niebezpieczeństwie, zakaz nadużywania klaksonu, zasady w obszarze zabudowanym oraz wymagania techniczne sygnału.', 'published_at' => $june20PublishedAt],
            'zabezpieczenie-ladunku-i-wymiary' => ['title' => 'Zabezpieczenie ładunku', 'sort_order' => 140, 'description' => 'Rozmieszczenie i mocowanie ładunku, dopuszczalne wymiary, przewóz materiałów sypkich oraz oznakowanie wystających elementów.', 'published_at' => $june21PublishedAt],
            'przyczepa-masa-wymiary-i-oswietlenie' => ['title' => 'Przyczepa: masa, wymiary i oświetlenie', 'sort_order' => 150, 'description' => 'Limity masy i długości zespołu pojazdów oraz podstawowe wymagania dotyczące widoczności i wyposażenia przyczepy.', 'published_at' => $june21PublishedAt],
            'wjazd-na-przejazd-kolejowy' => ['title' => 'Wjazd na przejazd kolejowy', 'sort_order' => 160, 'description' => 'Szczególna ostrożność, zakazy związane z zaporami, sygnalizacją, brakiem miejsca za przejazdem i wyprzedzaniem przy torach.', 'published_at' => $june21PublishedAt],
            'holowanie-pojazdu' => ['title' => 'Holowanie pojazdu', 'sort_order' => 170, 'description' => 'Prędkość, światła, oznakowanie, wymagane połączenie, sprawność układów oraz najważniejsze zakazy podczas holowania.', 'published_at' => $june21PublishedAt],
            'pasy-bezpieczenstwa' => ['title' => 'Pasy bezpieczeństwa', 'sort_order' => 180, 'description' => 'Obowiązek kierującego i pasażerów, tylne siedzenia, autobusy oraz ustawowe wyjątki od korzystania z pasów.', 'published_at' => $june21PublishedAt],
            'swiatla-do-jazdy-dziennej-i-mijania' => ['title' => 'Światła dzienne i mijania', 'sort_order' => 190, 'description' => 'Całodobowe używanie świateł mijania, warunki dopuszczające światła dzienne oraz zachowanie przy pogorszonej przejrzystości.', 'published_at' => $june21PublishedAt],
            'rogatki-i-sygnaly-na-przejezdzie-kolejowym' => ['title' => 'Rogatki i sygnały na przejeździe kolejowym', 'sort_order' => 200, 'description' => 'Znaczenie położenia zapór, czerwonego sygnału migającego oraz obowiązek obserwacji torów przed rozpoczęciem jazdy.', 'published_at' => $june21PublishedAt],
            'swiatla-przeciwmglowe' => ['title' => 'Światła przeciwmgłowe', 'sort_order' => 210, 'description' => 'Zasady używania przednich i tylnych świateł przeciwmgłowych, granica widoczności 50 m oraz wyjątek dla oznakowanej drogi krętej.', 'published_at' => $june21PublishedAt],
            'foteliki-i-przewoz-dzieci' => ['title' => 'Foteliki i przewóz dzieci', 'sort_order' => 220, 'description' => 'Dobór i montaż urządzeń przytrzymujących, reguły wzrostu 150 i 135 cm oraz wyjątki i zakazy dotyczące przewozu dzieci.', 'published_at' => $june21PublishedAt],
            'obowiazki-uczestnika-wypadku' => ['title' => 'Obowiązki uczestnika wypadku', 'sort_order' => 230, 'description' => 'Zabezpieczenie miejsca zdarzenia, pomoc rannym, wezwanie służb, pozostanie na miejscu oraz postępowanie przy kolizji bez ofiar.', 'published_at' => $june21PublishedAt],
            'telefon-podczas-kierowania' => ['title' => 'Telefon podczas kierowania', 'sort_order' => 240, 'description' => 'Zakres zakazu trzymania telefonu lub mikrofonu w ręku podczas kierowania samochodem, tramwajem, rowerem albo hulajnogą elektryczną.', 'published_at' => $june21PublishedAt],
            'swiatla-drogowe-i-oslepianie' => ['title' => 'Światła drogowe i zakaz oślepiania', 'sort_order' => 250, 'description' => 'Warunki używania świateł drogowych oraz obowiązek przełączenia ich na mijania wobec pojazdów jadących z przeciwka i poprzedzających.', 'published_at' => $june21PublishedAt],
            'gasnica-trojkat-i-obowiazkowe-wyposazenie' => ['title' => 'Gaśnica, trójkąt i wyposażenie pojazdu', 'sort_order' => 260, 'description' => 'Obowiązkowe i dopuszczalne wyposażenie samochodu, motocykla, ciągnika oraz pojazdu ciężarowego przewożącego osoby.', 'published_at' => $june21PublishedAt],
            'opony-bieznik-i-cisnienie' => ['title' => 'Opony, bieżnik i ciśnienie', 'sort_order' => 270, 'description' => 'Minimalna głębokość bieżnika, jednakowe opony na osi oraz ciśnienie zgodne z zaleceniami dla obciążenia pojazdu.', 'published_at' => $june21PublishedAt],
            'dokumenty-podczas-kontroli-drogowej' => ['title' => 'Dokumenty podczas kontroli drogowej', 'sort_order' => 280, 'description' => 'Dokumenty wymagane przy kontroli, wyjątki od elektronicznej weryfikacji oraz zasady dotyczące pojazdów zagranicznych i jazd testowych.', 'published_at' => $june22PublishedAt],
            'kategorie-prawa-jazdy-i-uprawnienia' => ['title' => 'Kategorie prawa jazdy i uprawnienia', 'sort_order' => 290, 'description' => 'Zakres kategorii AM, A1, A2, A, B1, B i T, krajowe rozszerzenia uprawnień oraz ważność i wymiana dokumentu.', 'published_at' => $june22PublishedAt],
            'alkohol-i-srodki-dzialajace-podobnie' => ['title' => 'Alkohol i środki działające podobnie', 'sort_order' => 300, 'description' => 'Zakaz kierowania, ustawowe progi stanu po użyciu alkoholu i nietrzeźwości oraz podstawowe różnice w odpowiedzialności.', 'published_at' => $june22PublishedAt],
            'zielona-strzalka-warunkowa' => ['title' => 'Zielona strzałka warunkowa', 'sort_order' => 310, 'description' => 'Warunki skrętu przy czerwonym sygnale: pełne zatrzymanie, obserwacja przejścia i ustąpienie innym uczestnikom ruchu.', 'published_at' => $june22PublishedAt],
            'pojazd-uprzywilejowany' => ['title' => 'Pojazd uprzywilejowany i korytarz życia', 'sort_order' => 320, 'description' => 'Rozpoznanie pojazdu uprzywilejowanego, ułatwienie przejazdu, prawidłowe tworzenie korytarza życia i zakaz korzystania z niego.', 'published_at' => $june22PublishedAt],
            'sygnal-zolty-i-zolty-migajacy' => ['title' => 'Sygnał żółty i żółty migający', 'sort_order' => 330, 'description' => 'Różnica między stałym sygnałem żółtym, żółtym migającym i jednoczesnym sygnałem czerwonym z żółtym.', 'published_at' => $june22PublishedAt],
        ];

        foreach ($definitions as $slug => $definition) {
            $topics[$slug] = LegalTopic::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    ...$definition,
                    'status' => LegalTopic::STATUS_PUBLISHED,
                    'published_at' => $definition['published_at'] ?? $publishedAt,
                ],
            );
        }

        return $topics;
    }

    /**
     * @param  array<string, LegalTopic>  $topics
     * @return array<string, LegalContentPage>
     */
    private function upsertPages(array $topics, ContentAuthor $author, ContentAuthor $reviewer, Carbon $publishedAt, Carbon $reviewedAt, Carbon $june13PublishedAt, Carbon $june13ReviewedAt, Carbon $june15ReviewedAt, Carbon $june19PublishedAt, Carbon $june19ReviewedAt, Carbon $june20PublishedAt, Carbon $june20ReviewedAt, Carbon $june21PublishedAt, Carbon $june21ReviewedAt, Carbon $june22PublishedAt, Carbon $june22ReviewedAt): array
    {
        $pages = [];
        $definitions = [
            'zatrzymanie-i-postoj' => [
                'title' => 'Zatrzymanie i postój pojazdu',
                'meta_title' => 'Zatrzymanie i postój pojazdu - przepisy do egzaminu',
                'meta_description' => 'Zobacz, kiedy zatrzymanie i postój pojazdu są dozwolone, a kiedy zabronione. Opracowanie przepisu z pytaniami egzaminacyjnymi.',
                'intro' => 'Zatrzymanie i postój to jeden z tych tematów, w których na egzaminie liczy się dokładna ocena miejsca, widoczności i wpływu na bezpieczeństwo ruchu.',
                'summary' => 'Kierujący powinien zatrzymywać pojazd tylko wtedy i w takim miejscu, w którym nie narusza zakazów oraz nie tworzy zagrożenia lub utrudnienia dla innych uczestników ruchu.',
                'exam_context' => 'W aktualnie powiązanym pytaniu trzeba rozpoznać ciągłą linię wyznaczającą krawędź jezdni i ocenić zatrzymanie na poboczu. Szerszy temat obejmuje też skrzyżowania, przejścia, przejazdy i inne miejsca objęte zakazem.',
                'body' => <<<'TEXT'
Najpierw oddziel zatrzymanie wynikające z ruchu od zatrzymania wybranego przez kierującego. Jeżeli stoisz, bo wymaga tego sygnał, znak, korek albo sytuacja na drodze, przepis o zakazach zatrzymania i postoju nie działa tak samo jak przy dobrowolnym zatrzymaniu pojazdu.

Przy dobrowolnym zatrzymaniu trzeba sprawdzić miejsce. Ustawa zakazuje zatrzymania między innymi na przejeździe kolejowym lub tramwajowym, na skrzyżowaniu, na przejściu dla pieszych, na przejeździe dla rowerów oraz w określonych odległościach od tych miejsc. W pytaniach egzaminacyjnych często rozstrzyga właśnie to, czy pojazd stoi za blisko punktu szczególnie chronionego.

Znaczenie ma też widoczność i organizacja jezdni. Nie wolno zatrzymać pojazdu w tunelu, na moście, na wiadukcie, obok linii, która wymusiłaby niebezpieczne najechanie przez innych kierujących, ani w miejscu zasłaniającym znak lub sygnał drogowy. To nie są tylko techniczne szczegóły: takie zatrzymanie może od razu stworzyć ryzyko dla innych.

Osobno sprawdź linię wyznaczającą krawędź jezdni. Art. 49 ust. 1 pkt 5 zabrania zatrzymania na jezdni obok przerywanej linii krawędziowej oraz na jezdni i poboczu obok linii ciągłej. W pytaniu z poboczem sama dostępna przestrzeń nie wystarcza więc do uznania zatrzymania za dozwolone.

Postój ma własne zakazy. Typowe przykłady to miejsce utrudniające wjazd do bramy, garażu lub parkingu, miejsce blokujące dostęp do prawidłowo zaparkowanego pojazdu, postój w strefie zamieszkania poza miejscem wyznaczonym albo pozostawienie dużego pojazdu w obszarze zabudowanym poza wyznaczonym parkingiem.

Na autostradzie i drodze ekspresowej zatrzymanie lub postój są dopuszczalne tylko w miejscach do tego wyznaczonych. Jeżeli pojazd unieruchomi się z przyczyn technicznych, kierujący ma obowiązek usunąć go z jezdni i ostrzec innych uczestników ruchu, więc samo włączenie świateł awaryjnych nie zamienia dowolnego miejsca w legalny postój.
TEXT,
                'key_points' => ['Nie każde pobocze oznacza legalne miejsce do zatrzymania.', 'Ciągła linia krawędziowa wyklucza zatrzymanie zarówno na jezdni, jak i na poboczu.', 'Trzeba ocenić widoczność, oznakowanie i odległość od miejsc szczególnie chronionych.', 'Najpierw ustal, czy zatrzymanie jest dobrowolne, czy wynika z warunków lub przepisów ruchu.'],
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'tramwaje-i-przystanki' => [
                'title' => 'Tramwaje, przystanki i bezpieczeństwo pasażerów',
                'meta_title' => 'Tramwaje i przystanki - podstawa prawna pytań egzaminacyjnych',
                'meta_description' => 'Wyjaśnienie obowiązków kierowcy przy przystanku tramwajowym, pasażerach i sytuacjach wymagających zatrzymania pojazdu.',
                'intro' => 'Przy przystanku tramwajowym najważniejsze jest bezpieczeństwo osób wsiadających i wysiadających, a nie sama możliwość przejazdu obok pojazdu szynowego.',
                'summary' => 'Jeżeli sytuacja przy przystanku wymaga zapewnienia pasażerom bezpiecznego dojścia, kierujący musi odpowiednio zwolnić albo zatrzymać pojazd.',
                'exam_context' => 'Na egzaminie pytania pokazują zwykle tramwaj, linię zatrzymania, przystanek bez wyspy albo pasażerów, którzy muszą przejść przez jezdnię.',
                'body' => <<<'TEXT'
Przy tramwaju najpierw sprawdź rodzaj przystanku. Największe obowiązki kierującego pojawiają się przy oznaczonym przystanku tramwajowym, który nie znajduje się przy drodze dla pieszych ani przy drodze dla pieszych i rowerów, zwłaszcza gdy nie ma wysepki dla pasażerów.

Jeżeli taki przystanek nie ma wysepki, a tramwaj wjeżdża na przystanek albo już na nim stoi, kierujący musi zatrzymać pojazd w takim miejscu i na taki czas, aby piesi mogli swobodnie dojść do tramwaju lub wrócić na drogę dla pieszych. Nie chodzi więc o zatrzymanie tylko przy samej linii, ale o realne zabezpieczenie ruchu pasażerów.

Gdy przystanek ma wysepkę, pytanie zwykle wymaga spokojniejszej analizy. Sama obecność tramwaju nie zawsze oznacza obowiązek zatrzymania, ale nadal trzeba obserwować pieszych, przejścia, sygnalizację i to, czy ktoś nie wchodzi na tor jazdy.

Przepis o takim zachowaniu stosuje się odpowiednio także przy ruchu innych pojazdów komunikacji publicznej. Dlatego w pytaniach nie wystarczy rozpoznać pojazd jako tramwaj: trzeba sprawdzić, czy widoczna sytuacja dotyczy pasażerów, przystanku i ich bezpiecznego dojścia.

Egzaminacyjna pułapka polega na patrzeniu wyłącznie na własny pas ruchu. Jeżeli pasażerowie muszą przejść przez jezdnię, to Twoja decyzja ma chronić ich ruch, nawet gdy fizycznie nie stoją jeszcze bezpośrednio przed maską pojazdu.
TEXT,
                'key_points' => ['Patrz na pasażerów, nie tylko na sam tramwaj.', 'Brak wyspy dla pasażerów zwiększa obowiązki kierowcy.', 'Zatrzymanie ma trwać tyle, ile potrzeba do bezpiecznego przejścia osób.'],
                'last_reviewed_at' => $june15ReviewedAt,
            ],
            'piesi-i-przejscia' => [
                'title' => 'Piesi i przejścia dla pieszych',
                'meta_title' => 'Piesi i przejścia dla pieszych - przepisy do egzaminu',
                'meta_description' => 'Praktyczne opracowanie zasad wobec pieszych i przejść dla pieszych wraz z powiązanymi pytaniami egzaminacyjnymi.',
                'intro' => 'Pytania o pieszych wymagają połączenia obserwacji drogi, szczególnej ostrożności i prawidłowej oceny pierwszeństwa.',
                'summary' => 'Kierujący ma obowiązek zachować szczególną ostrożność w rejonie przejść i reagować tak, aby nie narazić pieszego na niebezpieczeństwo.',
                'exam_context' => 'Na egzaminie temat pojawia się przy dojeździe do przejścia z pieszym albo bez widocznego pieszego, przy skręcie w drogę poprzeczną oraz przy wjeździe do bramy przez drogę dla pieszych.',
                'body' => <<<'TEXT'
Przy przejściu dla pieszych kierujący nie ocenia tylko tego, czy pieszy jest już na pasach. Zbliżając się do przejścia, ma zachować szczególną ostrożność, zmniejszyć prędkość tak, aby nie narazić pieszego na niebezpieczeństwo, i ustąpić pierwszeństwa pieszemu znajdującemu się na przejściu albo na nie wchodzącemu.

Trzeba rozróżnić dwa podobne scenariusze. Przy skręcie w drogę poprzeczną kierujący ustępuje pieszemu przechodzącemu na skrzyżowaniu przez jezdnię drogi, na którą wjeżdża. Przy wjeździe do bramy lub posesji przez drogę dla pieszych albo drogę dla pieszych i rowerów kierujący ma jechać powoli i ustąpić pierwszeństwa pieszemu. W tym drugim przypadku podstawą jest art. 26 ust. 4.

W rejonie przejścia są też konkretne zakazy. Kierującemu nie wolno wyprzedzać pojazdu na przejściu dla pieszych i bezpośrednio przed nim, poza przejściem o ruchu kierowanym. Nie wolno też omijać pojazdu, który jechał w tym samym kierunku, ale zatrzymał się, aby ustąpić pierwszeństwa pieszemu.

Art. 13 pokazuje drugą stronę tej samej sytuacji: pieszy wchodzący na jezdnię, drogę dla rowerów albo torowisko ma zachować szczególną ostrożność i zasadniczo korzystać z przejścia. Poza przejściem może przechodzić tylko w określonych warunkach, bez tworzenia zagrożenia lub utrudnienia ruchu.

Najczęstsza pułapka to odpowiedź oparta na jednym kadrze. Brak pieszego bezpośrednio przed pojazdem nie kończy analizy, jeśli widać przejście, skręt, ograniczoną widoczność albo osobę, która może wejść na tor jazdy.
TEXT,
                'key_points' => ['Sama obecność przejścia zmienia poziom wymaganej ostrożności.', 'Pieszemu na przejściu albo wchodzącemu na przejście trzeba ustąpić pierwszeństwa.', 'Wjeżdżając do bramy przez drogę dla pieszych, jedź powoli i ustąp pieszemu.', 'Brak pieszego w kadrze nie znosi obowiązku szczególnej ostrożności przy dojeździe do przejścia.'],
                'last_reviewed_at' => $june19ReviewedAt,
            ],
            'pierwszenstwo-przejazdu' => [
                'title' => 'Pierwszeństwo przejazdu na skrzyżowaniu',
                'meta_title' => 'Pierwszeństwo przejazdu - przepisy i pytania egzaminacyjne',
                'meta_description' => 'Wyjaśnienie zasad pierwszeństwa na skrzyżowaniach, przy skręcie w lewo i wobec pojazdów nadjeżdżających z innych kierunków.',
                'intro' => 'Pierwszeństwo przejazdu to nie tylko zapamiętanie reguły, ale umiejętność odczytania całej sytuacji: znaków, kierunków jazdy i torów ruchu.',
                'summary' => 'Kierujący zbliżający się do skrzyżowania musi zachować szczególną ostrożność i ustąpić pierwszeństwa wtedy, gdy wynika to z przepisów lub organizacji ruchu.',
                'exam_context' => 'W powiązanych pytaniach trzeba odróżnić skręt w lewo wobec pojazdu jadącego z przeciwka od wjazdu na rondo oznaczone zestawem A-7 i C-12. W drugim przypadku o pierwszeństwie rozstrzygają znaki i § 36 ust. 2 rozporządzenia.',
                'body' => <<<'TEXT'
Analizę skrzyżowania zacznij od organizacji ruchu: poleceń, sygnałów świetlnych i znaków. Dopiero gdy nie rozstrzygają one pierwszeństwa, przechodzisz do zasad ogólnych z art. 25.

Na skrzyżowaniu o ruchu okrężnym nie stosuj automatycznie zasady prawej strony. Jeżeli przed wjazdem ustawiono razem znaki A-7 i C-12, pierwszeństwo ma kierujący znajdujący się już na skrzyżowaniu, a wjeżdżający ma mu ustąpić. To znaczenie wynika z § 36 ust. 2 rozporządzenia w sprawie znaków i sygnałów drogowych.

Podstawowa reguła mówi, że kierujący zbliżający się do skrzyżowania zachowuje szczególną ostrożność i ustępuje pierwszeństwa pojazdowi nadjeżdżającemu z prawej strony. To dlatego w pytaniach często najpierw trzeba ustalić, z której strony nadjeżdża drugi pojazd, a dopiero potem oceniać własny tor jazdy.

Skręt w lewo dodaje kolejną warstwę. Jeżeli skręcasz w lewo, musisz ustąpić także pojazdowi jadącemu z kierunku przeciwnego na wprost albo skręcającemu w prawo. W praktyce egzaminacyjnej ten fragment przepisu często przesądza odpowiedź nawet wtedy, gdy Twój pojazd jest już ustawiony do skrętu.

Pojazd szynowy ma w tym przepisie szczególną pozycję: ma pierwszeństwo wobec innych pojazdów bez względu na to, z której strony nadjeżdża. Nie wolno więc mechanicznie stosować zasady prawej strony bez sprawdzenia, czy w sytuacji nie uczestniczy tramwaj.

Art. 25 działa również wtedy, gdy kierunki ruchu przecinają się poza klasycznym skrzyżowaniem. Przepis zakazuje też wjazdu na skrzyżowanie, jeżeli na nim lub za nim nie ma miejsca do kontynuowania jazdy, więc samo posiadanie pierwszeństwa nie oznacza prawa do blokowania przejazdu.
TEXT,
                'key_points' => ['Najpierw odczytaj znaki i sygnały, potem stosuj zasady ogólne.', 'Zestaw A-7 i C-12 daje pierwszeństwo pojazdom znajdującym się już na rondzie.', 'Przy skręcie w lewo ustąp pojazdowi z przeciwka jadącemu na wprost lub skręcającemu w prawo.', 'Nie zakładaj pierwszeństwa tylko dlatego, że Twój pojazd jest już blisko skrzyżowania.'],
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'sygnalizacja-i-osoby-kierujace-ruchem' => [
                'title' => 'Sygnalizacja i osoby kierujące ruchem',
                'meta_title' => 'Sygnalizacja i osoby kierujące ruchem - hierarchia na egzaminie',
                'meta_description' => 'Wyjaśnienie hierarchii poleceń osoby kierującej ruchem, sygnałów świetlnych, znaków i zasad ogólnych w pytaniach egzaminacyjnych.',
                'intro' => 'W ruchu drogowym nie wszystkie informacje mają taką samą moc. Egzamin często sprawdza, czy wiesz, co jest ważniejsze w danej sytuacji.',
                'summary' => 'Polecenia osoby kierującej ruchem są nadrzędne wobec sygnałów świetlnych, znaków drogowych i zasad ogólnych, a sygnalizacja może zmieniać znaczenie znaków pierwszeństwa.',
                'exam_context' => 'W powiązanych pytaniach trzeba odczytać policjanta zwróconego bokiem oraz jednoczesny sygnał czerwony i żółty. Najpierw rozpoznaj konkretny sygnał, a dopiero potem umieść go w hierarchii z art. 5.',
                'body' => <<<'TEXT'
W tym temacie najważniejsza jest kolejność sprawdzania informacji. Uczestnik ruchu ma stosować się do poleceń osób kierujących ruchem lub uprawnionych do jego kontroli, do sygnałów świetlnych i znaków drogowych nawet wtedy, gdy ogólna zasada ruchu sugerowałaby inne zachowanie.

Najwyżej w hierarchii stoją polecenia i sygnały osoby kierującej ruchem albo uprawnionej do kontroli. Jeżeli policjant lub inna uprawniona osoba pokazuje sygnał, to on rozstrzyga sytuację przed sygnalizatorem, znakiem i zasadą ogólną.

Postawa osoby kierującej ruchem ma własne, precyzyjne znaczenie. Gdy jest zwrócona bokiem do nadjeżdżających pojazdów, § 108 ust. 2 zezwala im na wjazd na skrzyżowanie lub odcinek drogi za tą osobą. Postawa przodem albo tyłem oznacza zakaz i nie należy jej mylić z pozycją boczną.

Następnie analizujesz sygnały świetlne. Sygnały świetlne mają pierwszeństwo przed znakami drogowymi regulującymi pierwszeństwo przejazdu, więc zielone lub czerwone światło może zmienić odpowiedź, którą dałby sam znak ustawiony przy skrzyżowaniu.

Jednoczesne światło czerwone i żółte nadal oznacza zakaz wjazdu za sygnalizator. Zapowiada jedynie, że za chwilę zapali się sygnał zielony, dlatego nie pozwala ruszyć wcześniej.

Znaki drogowe i zasady ogólne nadal są ważne, ale dopiero po sprawdzeniu elementów wyższych w hierarchii. W pytaniach obrazkowych oznacza to, że nie wolno zatrzymać się na pierwszym zauważonym znaku, jeśli w kadrze jest osoba kierująca ruchem albo działający sygnalizator.

Typowa pułapka egzaminacyjna polega na mieszaniu porządków. Jeżeli polecenie osoby kierującej ruchem jest sprzeczne ze światłem lub znakiem, stosujesz polecenie osoby. Jeżeli światło rozstrzyga pierwszeństwo, znak pierwszeństwa nie jest pierwszym kryterium odpowiedzi.
TEXT,
                'key_points' => ['Osoba kierująca ruchem zwrócona bokiem zezwala na wjazd.', 'Jednoczesny sygnał czerwony i żółty nadal zakazuje wjazdu.', 'Polecenia osoby kierującej ruchem stoją wyżej niż sygnalizacja i znaki.', 'Znaki i zasady ogólne analizuj dopiero po sprawdzeniu sygnałów nadrzędnych.'],
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'predkosc-odstep-i-hamowanie' => [
                'title' => 'Prędkość, odstęp i hamowanie pojazdu',
                'meta_title' => 'Prędkość, odstęp i hamowanie - zasady i pytania egzaminacyjne',
                'meta_description' => 'Sprawdź, jak odróżnić prędkość bezpieczną od limitu, obliczyć odstęp na autostradzie i rozpoznać zasady strefy zamieszkania.',
                'intro' => 'W pytaniach o prędkość najpierw ustal, czy sprawdzany jest limit liczbowy, prędkość bezpieczna, sposób hamowania czy odstęp od poprzedzającego pojazdu.',
                'summary' => 'Limit wyznacza najwyższą dozwoloną prędkość, ale warunki mogą wymagać jazdy wolniejszej. Odstęp musi pozwalać uniknąć zderzenia, a na autostradzie i drodze ekspresowej działa dodatkowa reguła połowy prędkości.',
                'exam_context' => 'Egzamin łączy pytania tekstowe z obrazami znaków strefy zamieszkania, drogi ekspresowej i autostrady. Pułapki polegają na myleniu limitu z prędkością bezpieczną, traktowaniu 100 m jako stałego odstępu albo przypisywaniu 140 km/h wyłącznie lewemu pasowi.',
                'body' => <<<'TEXT'
Zacznij od rozpoznania rodzaju pytania. Jeżeli odpowiedzi zawierają wartości w km/h, zwykle chodzi o prędkość dopuszczalną dla danego pojazdu i drogi. Jeżeli pytanie opisuje widoczność, pogodę, nawierzchnię lub natężenie ruchu, sprawdzana jest prędkość bezpieczna. Osobną grupę tworzą pytania o hamowanie i odstęp.

Prędkość bezpieczna nie jest drugim, niezależnym limitem. Art. 19 ust. 1 nakazuje dobrać ją tak, aby zachować panowanie nad pojazdem, z uwzględnieniem między innymi widoczności i stanu drogi, pogody, ruchu oraz stanu pojazdu. Gorsze warunki mogą wymagać jazdy znacznie wolniejszej, ale nigdy nie pozwalają przekroczyć prędkości dopuszczalnej.

W strefie zamieszkania limit wynosi 20 km/h dla pojazdu i zespołu pojazdów. Oznacza to, że 25 km/h jest już prędkością niedozwoloną, a dołączenie przyczepy nie podnosi limitu do 30 km/h. Na obrazie najpierw rozpoznaj znak D-40 rozpoczynający strefę zamieszkania, a dopiero potem wybierz wartość.

Dla samochodu osobowego prędkość dopuszczalna na autostradzie wynosi 140 km/h. Przepis wiąże limit z rodzajem drogi i pojazdu, a nie z konkretnym pasem, dlatego nie istnieje osobny limit 140 km/h zarezerwowany dla lewego pasa. Nadal obowiązuje ruch prawostronny, a sam limit nie jest poleceniem jazdy z maksymalną wartością.

Ogólna zasada odstępu wymaga pozostawienia miejsca potrzebnego do uniknięcia zderzenia, gdy pojazd poprzedzający zahamuje albo się zatrzyma. Prędkość ma tu bezpośrednie znaczenie: im szybciej jedziesz, tym więcej drogi i czasu potrzeba na reakcję oraz hamowanie.

Na autostradzie i drodze ekspresowej obowiązuje dodatkowy minimalny odstęp. Jego wartość w metrach ma wynosić co najmniej połowę aktualnej prędkości wyrażonej w km/h: przy 100 km/h jest to 50 m, a przy 140 km/h 70 m. Nie jest to stałe 100 m, a reguły tej nie stosuje się podczas manewru wyprzedzania.

Hamowanie również podlega ocenie bezpieczeństwa. Nie chodzi wyłącznie o możliwość zatrzymania pojazdu, ale o wykonanie tego bez tworzenia zagrożenia lub niepotrzebnego utrudnienia ruchu. Dlatego właściwa prędkość, odstęp i sposób hamowania tworzą jeden ciąg decyzji, choć każde pytanie może sprawdzać inny jego element.
TEXT,
                'key_points' => ['Najpierw rozpoznaj, czy pytanie dotyczy limitu, warunków jazdy, odstępu czy hamowania.', 'Prędkość bezpieczna może być niższa od dopuszczalnej, ale nigdy nie pozwala przekroczyć limitu.', 'W strefie zamieszkania pojazd i zespół pojazdów mogą jechać najwyżej 20 km/h.', 'Na autostradzie i drodze ekspresowej minimalny odstęp to co najmniej połowa prędkości.', 'Limit 140 km/h na autostradzie nie jest przypisany wyłącznie do lewego pasa.'],
                'published_at' => $june13PublishedAt,
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'zmiana-kierunku-i-pasa-ruchu' => [
                'title' => 'Zmiana kierunku i pasa ruchu',
                'meta_title' => 'Zmiana kierunku i pasa ruchu - przepisy do egzaminu',
                'meta_description' => 'Wyjaśnienie zasad zmiany kierunku jazdy, zmiany pasa ruchu, kierunkowskazów, ustawienia pojazdu i zawracania na egzaminie.',
                'intro' => 'Zmiana kierunku jazdy i pasa ruchu to manewry, w których egzamin sprawdza nie tylko kierunkowskaz, ale też obserwację, pierwszeństwo i właściwe ustawienie pojazdu.',
                'summary' => 'Kierujący może zmienić kierunek jazdy lub pas ruchu tylko ze szczególną ostrożnością, po odpowiednim ustawieniu pojazdu, zawczasu i wyraźnie sygnalizując zamiar oraz ustępując tam, gdzie wymaga tego przepis.',
                'exam_context' => 'Powiązane pytania sprawdzają szczególną ostrożność, przygotowanie pasa do skrętu w lewo, rozpoczęcie zmiany pasa przed zjazdem oraz dwa momenty pracy kierunkowskazu: przed manewrem i bezpośrednio po nim.',
                'body' => <<<'TEXT'
Najpierw ustal, czy zmieniasz kierunek jazdy, czy tylko zajmowany pas ruchu. W obu przypadkach obowiązuje szczególna ostrożność, czyli wcześniejsza obserwacja drogi, lusterek, martwego pola i zachowania innych uczestników ruchu.

Przed skrętem w prawo ustaw pojazd możliwie blisko prawej krawędzi jezdni. Przed skrętem w lewo ustaw się przy środku jezdni albo, na jezdni jednokierunkowej, przy jej lewej krawędzi. Na egzaminie ten obowiązek często pojawia się w pytaniach o wcześniejsze zajęcie właściwego pasa.

Zmiana pasa ruchu nie polega tylko na włączeniu kierunkowskazu. Trzeba ustąpić pierwszeństwa pojazdowi jadącemu po pasie, na który chcesz wjechać, oraz pojazdowi wjeżdżającemu na ten pas z prawej strony, poza szczególnymi sytuacjami jazdy na suwak.

Kierunkowskaz ma być włączony zawczasu i wyraźnie, a po wykonaniu manewru trzeba go niezwłocznie wyłączyć. Typowy błąd egzaminacyjny to zbyt późne sygnalizowanie albo pozostawienie kierunkowskazu po zakończonym skręcie.

Przy zawracaniu sprawdź nie tylko znaki, ale też miejsce i warunki. Ustawowy zakaz obejmuje między innymi tunel, most, wiadukt, drogę jednokierunkową, autostradę oraz drogę ekspresową poza skrzyżowaniem lub miejscem do tego przeznaczonym. Nawet tam, gdzie nie ma takiego zakazu miejsca, zawracanie odpada, gdy mogłoby zagrozić bezpieczeństwu lub utrudnić ruch.
TEXT,
                'key_points' => ['Szczególna ostrożność obowiązuje przy każdej zmianie kierunku jazdy albo pasa ruchu.', 'Kierunkowskaz informuje o zamiarze, ale nie daje pierwszeństwa.', 'Przed skrętem ustaw pojazd po właściwej stronie jezdni albo pasa.', 'Po wykonaniu manewru kierunkowskaz trzeba niezwłocznie wyłączyć.', 'Jazda na suwak jest wyjątkiem od zwykłej zasady ustępowania przy zmianie pasa.'],
                'published_at' => $june13PublishedAt,
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'wlaczanie-sie-do-ruchu' => [
                'title' => 'Włączanie się do ruchu',
                'meta_title' => 'Włączanie się do ruchu - przepisy do egzaminu',
                'meta_description' => 'Wyjaśnienie, kiedy kierujący włącza się do ruchu, komu ustępuje pierwszeństwa i jak odróżnić to od zwykłego ruszania.',
                'intro' => 'Włączanie się do ruchu to moment, w którym kierujący wraca na drogę z miejsca poza zwykłym ruchem albo rusza po postoju niezwiązanym z warunkami ruchu.',
                'summary' => 'Przy włączaniu się do ruchu kierujący musi zachować szczególną ostrożność i ustąpić pierwszeństwa innemu pojazdowi lub uczestnikowi ruchu.',
                'exam_context' => 'Powiązane pytania pokazują wyjazd z posesji, parkingu i strefy zamieszkania, ruszanie po postoju niezwiązanym z ruchem oraz kontrprzykład: ruszenie po zapaleniu zielonego światła nie jest włączaniem się do ruchu.',
                'body' => <<<'TEXT'
Najpierw sprawdź, czy w ogóle jesteś włączającym się do ruchu. Art. 17 obejmuje między innymi rozpoczynanie jazdy po postoju albo zatrzymaniu niewynikającym z warunków lub przepisów ruchu, a także wyjazd na drogę z posesji, parkingu, obiektu przydrożnego, drogi niepublicznej albo strefy zamieszkania.

Włączaniem się do ruchu jest również wjazd na drogę twardą z drogi gruntowej, wjazd na jezdnię z pobocza albo z drogi dla pieszych oraz niektóre sytuacje związane z drogą dla rowerów lub drogą dla pieszych i rowerów. W pytaniach egzaminacyjnych najczęściej wystarczy rozpoznać, że kierujący nie jest już zwykłym uczestnikiem jadącym swoim pasem, tylko dopiero wchodzi do głównego ruchu.

Najważniejszy obowiązek jest prosty: zachowaj szczególną ostrożność i ustąp pierwszeństwa innemu pojazdowi lub uczestnikowi ruchu. Kierunkowskaz, wolna luka albo to, że manewr wydaje się krótki, nie daje pierwszeństwa.

Nie każde ruszanie jest jednak włączaniem się do ruchu. Jeżeli zatrzymałeś się dlatego, że wynikało to z sygnału czerwonego, znaku, korka albo innych warunków ruchu, po ruszeniu nadal jesteś uczestnikiem tego samego ruchu. Dlatego pytanie o ruszenie po zmianie sygnału na zielony ma inną odpowiedź niż pytanie o wyjazd z posesji lub parkingu.

Na egzaminie największa pułapka polega na pomyleniu pierwszeństwa. Kierowca wyjeżdżający z posesji, parkingu albo strefy zamieszkania musi założyć, że to on ma poczekać i wjechać dopiero wtedy, gdy nie wymusi pierwszeństwa.
TEXT,
                'key_points' => ['Wyjazd z posesji, parkingu albo strefy zamieszkania to typowe włączanie się do ruchu.', 'Włączający się do ruchu musi zachować szczególną ostrożność.', 'Przy włączaniu się do ruchu trzeba ustąpić pierwszeństwa innym pojazdom i uczestnikom ruchu.', 'Ruszenie po zielonym świetle nie jest włączaniem się do ruchu, bo zatrzymanie wynikało z przepisów ruchu.', 'Kierunkowskaz nie daje pierwszeństwa przy wyjeździe na jezdnię.'],
                'published_at' => $june13PublishedAt,
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'autobus-wyjezdzajacy-z-przystanku' => [
                'title' => 'Autobus wyjeżdżający z przystanku',
                'meta_title' => 'Autobus wyjeżdżający z przystanku - zasady i pytania',
                'meta_description' => 'Sprawdź, kiedy trzeba zwolnić lub zatrzymać się przed autobusem wyjeżdżającym z przystanku i jakie obowiązki nadal ma kierowca autobusu.',
                'intro' => 'Wyjazd autobusu z zatoki nie oznacza automatycznego pierwszeństwa w każdej sytuacji. Szczególny obowiązek innych kierujących powstaje po spełnieniu warunków wskazanych w art. 18.',
                'summary' => 'Na obszarze zabudowanym kierujący zbliżający się do oznaczonego przystanku autobusowego lub trolejbusowego musi zwolnić, a w razie potrzeby zatrzymać się, aby umożliwić autobusowi sygnalizowane włączenie się do ruchu. Kierowca autobusu nadal musi upewnić się, że jego manewr nie stworzy zagrożenia.',
                'exam_context' => 'Pytania egzaminacyjne pokazują autobus przy zatoce lub oznaczonym przystanku, włączony kierunkowskaz i informację, czy sytuacja dzieje się na obszarze zabudowanym. Trzeba rozpoznać wszystkie warunki, a nie reagować wyłącznie na sam widok autobusu.',
                'body' => <<<'TEXT'
Najpierw sprawdź miejsce. Szczególny obowiązek z art. 18 ust. 1 dotyczy kierującego zbliżającego się do oznaczonego przystanku autobusowego lub trolejbusowego na obszarze zabudowanym. Poza obszarem zabudowanym ten konkretny obowiązek nie wynika z art. 18 ust. 1, choć nadal trzeba jechać ostrożnie i nie tworzyć zagrożenia.

Następnie sprawdź zamiar kierowcy autobusu. Autobus musi sygnalizować kierunkowskazem zmianę pasa ruchu albo wjazd z zatoki na jezdnię. Sama obecność autobusu na przystanku nie wystarcza do mechanicznego uznania, że każdy pojazd ma się zatrzymać.

Jeżeli warunki są spełnione, kierujący zbliżający się do przystanku ma zmniejszyć prędkość. Gdy samo zwolnienie nie wystarczy do umożliwienia manewru, powinien się zatrzymać. Przepis nie wymaga zatrzymania w każdej sytuacji: reakcja ma być taka, aby autobus mógł bezpiecznie włączyć się do ruchu.

Autobus nie otrzymuje jednak prawa do nagłego wjazdu bez obserwacji drogi. Art. 18 ust. 2 wymaga, aby jego kierowca przed wjazdem na sąsiedni pas lub jezdnię upewnił się, że nie spowoduje zagrożenia bezpieczeństwa ruchu. Obowiązki obu kierujących działają równocześnie.

Na egzaminie największa pułapka polega na pominięciu jednego z warunków. Sprawdź: czy przystanek jest oznaczony, czy znajduje się na obszarze zabudowanym, czy autobus sygnalizuje manewr oraz czy do umożliwienia wyjazdu wystarczy zwolnić, czy trzeba się zatrzymać. Autobus szkolny jest objęty odrębną regulacją art. 18a i nie należy automatycznie stosować do niego wszystkich wniosków z tego artykułu.
TEXT,
                'key_points' => ['Szczególny obowiązek dotyczy oznaczonego przystanku na obszarze zabudowanym.', 'Autobus musi kierunkowskazem sygnalizować zmianę pasa albo wjazd z zatoki na jezdnię.', 'Najpierw zmniejsz prędkość; zatrzymaj się, jeżeli jest to potrzebne do umożliwienia manewru.', 'Kierowca autobusu nadal musi upewnić się, że jego wjazd nie stworzy zagrożenia.', 'Poza obszarem zabudowanym szczególny obowiązek z art. 18 ust. 1 nie obowiązuje.'],
                'published_at' => $june19PublishedAt,
                'last_reviewed_at' => $june19ReviewedAt,
            ],
            'zawracanie' => [
                'title' => 'Zawracanie: gdzie wolno, znaki i sygnalizacja',
                'meta_title' => 'Zawracanie - gdzie wolno, znaki B-21 i B-23, sygnalizacja',
                'meta_description' => 'Sprawdź, gdzie zawracanie jest zabronione, jak działają znaki B-21 i B-23, strzałki P-8 oraz sygnalizatory S-3 i zielona strzałka.',
                'intro' => 'Odpowiedź na pytanie o zawracanie nie zależy od jednego znaku. Trzeba kolejno sprawdzić miejsce, oznakowanie, pas ruchu, sygnalizację oraz to, czy manewr będzie bezpieczny i nie utrudni ruchu.',
                'summary' => 'Zawracanie jest dozwolone tylko wtedy, gdy nie obowiązuje ustawowy zakaz miejsca, znak lub sygnał nie wyklucza manewru, wybrany pas pozwala jechać w tym kierunku, a wykonanie manewru nie stworzy zagrożenia ani utrudnienia ruchu.',
                'exam_context' => 'Powiązane pytania pokazują most, tunel, drogę jednokierunkową, autostradę i drogę ekspresową, znaki B-21, B-22 i B-23, strzałki P-8, sygnalizatory kierunkowe S-3 oraz sytuacje z tramwajem. Najpewniejsza metoda to analiza zawsze w tej samej kolejności.',
                'body' => <<<'TEXT'
Zacznij od miejsca, bo część zakazów działa nawet bez znaku. Nie wolno zawracać w tunelu, na moście, wiadukcie ani na drodze jednokierunkowej. Zakaz obowiązuje również na autostradzie. Na drodze ekspresowej wolno zawrócić wyłącznie na skrzyżowaniu albo w miejscu do tego przeznaczonym. Brak znaku zakazu nie uchyla żadnej z tych reguł.

Następnie sprawdź znaki pionowe. Znak B-21 zabrania nie tylko skrętu w lewo, ale także zawracania. Co do zasady dotyczy najbliższego skrzyżowania. Znak B-23 jest szerszy przestrzennie: zakazuje zawracania od miejsca ustawienia do najbliższego skrzyżowania włącznie, a zakaz może wcześniej zakończyć znak B-24. Znak B-22 zabrania skrętu w prawo, lecz sam nie ustanawia zakazu zawracania.

Trzeci krok to pas ruchu i strzałki P-8. Strzałki na jezdni wskazują kierunki dozwolone z danego pasa. Strzałka do skręcania w lewo P-8b umieszczona na skrajnym lewym pasie co do zasady zezwala także na zawracanie. Nie wolno jednak zawrócić, jeżeli zabrania tego znak B-23 albo ruchem kieruje sygnalizator S-3. W tym drugim przypadku decydują kierunki pokazane na sygnalizatorze.

Sygnalizator kierunkowy S-3 pozwala jechać tylko w kierunkach wskazanych strzałkami. Sygnał ze strzałką wyłącznie w lewo nie oznacza automatycznie zgody na zawracanie, nawet gdy na jezdni znajduje się P-8b. Zawracanie jest dozwolone, gdy sygnalizator wskazuje również ten kierunek. Zielony sygnał kierunkowy oznacza, że podczas jazdy we wskazanym kierunku nie występuje kolizja z innymi uczestnikami ruchu.

Osobną sytuacją jest zielona strzałka warunkowa w lewo przy sygnale czerwonym. Po obowiązkowym zatrzymaniu pozwala ona również zawrócić ze skrajnego lewego pasa, chyba że zabrania tego znak B-23. Nadal trzeba ustąpić pierwszeństwa i wykonać manewr bez powodowania zagrożenia.

Na końcu oceń bezpieczeństwo i pierwszeństwo. Nawet jeżeli miejsce, znaki, pas i sygnalizacja pozwalają zawrócić, manewr jest zabroniony, gdy mógłby zagrozić bezpieczeństwu albo utrudnić ruch. Trzeba też respektować pierwszeństwo innych uczestników, w tym pojazdu szynowego, jeżeli znak lub sygnał nie rozstrzyga sytuacji inaczej.

Na egzaminie stosuj stałą kolejność: miejsce, znaki pionowe, strzałki na pasie, sygnalizator, bezpieczeństwo i pierwszeństwo. Dzięki temu nie pomylisz ogólnego zezwolenia wynikającego z pasa ruchu z zakazem, który pojawia się wcześniej w tej analizie.
TEXT,
                'key_points' => ['Najpierw wyklucz tunel, most, wiadukt, drogę jednokierunkową, autostradę i niedozwolone miejsce na drodze ekspresowej.', 'B-21 zabrania skrętu w lewo i zawracania, B-23 zakazuje zawracania do najbliższego skrzyżowania włącznie, a B-22 sam nie zabrania zawracania.', 'P-8b na skrajnym lewym pasie co do zasady pozwala zawrócić, chyba że zabrania tego B-23 albo ruchem kieruje S-3.', 'S-3 zezwala wyłącznie na kierunki pokazane strzałkami; zwykła strzałka w lewo nie daje automatycznie prawa do zawracania.', 'Nawet bez znaku zakazu nie wolno zawracać, jeżeli manewr stworzy zagrożenie lub utrudni ruch.'],
                'published_at' => $june20PublishedAt,
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'sygnal-dzwiekowy' => [
                'title' => 'Sygnał dźwiękowy: kiedy wolno użyć klaksonu',
                'meta_title' => 'Sygnał dźwiękowy - kiedy wolno użyć klaksonu',
                'meta_description' => 'Sprawdź, kiedy wolno użyć sygnału dźwiękowego, czego zabrania art. 29, jaka zasada obowiązuje w obszarze zabudowanym i kiedy klakson jest obowiązkowy we mgle.',
                'intro' => 'Klakson nie służy do ponaglania, wyrażania złości ani informowania o zwykłym zamiarze jazdy. Jego podstawowym zadaniem jest ostrzeganie o rzeczywistym niebezpieczeństwie.',
                'summary' => 'Sygnału dźwiękowego wolno użyć, gdy jest to konieczne do ostrzeżenia o niebezpieczeństwie. Na obszarze zabudowanym wymóg jest jeszcze węższy: musi chodzić o bezpośrednie niebezpieczeństwo. Nadużywanie klaksonu jest zabronione.',
                'exam_context' => 'Pytania egzaminacyjne zestawiają realne zagrożenie z sytuacjami, w których kierujący chce jedynie ponaglić pieszego, rowerzystę lub inny pojazd. Osobno sprawdzają brzmienie sygnału oraz szczególny obowiązek używania krótkich sygnałów podczas mgły poza obszarem zabudowanym.',
                'body' => <<<'TEXT'
Zacznij od celu sygnału. Art. 29 ust. 1 pozwala użyć sygnału dźwiękowego albo świetlnego, gdy jest to konieczne do ostrzeżenia o niebezpieczeństwie. Liczy się więc rzeczywiste ryzyko: pieszy wchodzący pod pojazd, kierujący zajeżdżający drogę albo uczestnik ruchu, który może nie zauważyć zagrożenia. Samo zdenerwowanie lub chęć szybszej jazdy nie wystarczają.

Na obszarze zabudowanym reguła jest bardziej rygorystyczna. Art. 29 ust. 2 pkt 2 zabrania używania sygnału dźwiękowego, chyba że jest to konieczne w związku z bezpośrednim niebezpieczeństwem. Nie wystarczy przewidywać, że ktoś może za chwilę zachować się niewłaściwie. Zagrożenie musi być konkretne i wymagać ostrzeżenia właśnie w tym momencie.

Osobny zakaz dotyczy nadużywania sygnału. Klakson nie może zastępować cierpliwości, hamowania ani zachowania bezpiecznego odstępu. Nie używa się go po to, aby przyspieszyć ruszenie stojących pojazdów, nakłonić rowerzystę do szybszej jazdy, pospieszyć pieszego albo oznajmić, że zamierzasz wyprzedzać w zwykłych warunkach.

W pytaniach zwróć uwagę na różnicę między prawem a obowiązkiem. Art. 29 mówi, że kierujący może użyć sygnału, gdy trzeba ostrzec o niebezpieczeństwie. Nie tworzy ogólnego obowiązku trąbienia przy każdym dziecku, pieszym, rowerzyście, przejściu lub pojeździe jadącym wolniej. Najpierw trzeba wykonać właściwe działanie obronne: zmniejszyć prędkość, zahamować, zatrzymać się albo ustąpić pierwszeństwa.

Jest jednak szczególny przypadek ustawowego obowiązku. Poza obszarem zabudowanym podczas mgły kierujący pojazdem silnikowym ma dawać krótkotrwałe sygnały dźwiękowe podczas wyprzedzania lub omijania. Ta reguła wynika z art. 30 ust. 1 pkt 1 lit. b i nie oznacza zgody na ciągłe używanie klaksonu.

Znaczenie ma również sam rodzaj sygnału. Pojazd samochodowy powinien być wyposażony w sygnał o ciągłym i nieprzeraźliwym tonie. Zwykły klakson nie może naśladować sygnału o zmiennym tonie przeznaczonego dla pojazdów uprzywilejowanych.

Na egzaminie stosuj prosty test: czy istnieje konkretne niebezpieczeństwo, czy sygnał jest potrzebny do ostrzeżenia, czy znajdujesz się w obszarze zabudowanym i czy właściwszą reakcją nie jest hamowanie lub zatrzymanie. Jeżeli celem jest wyłącznie ponaglenie albo okazanie niezadowolenia, użycie sygnału jest nieprawidłowe.
TEXT,
                'key_points' => ['Klakson służy do ostrzegania o niebezpieczeństwie, a nie do ponaglania.', 'Na obszarze zabudowanym sygnał dźwiękowy jest dopuszczalny tylko przy bezpośrednim niebezpieczeństwie.', 'Sygnał nie zastępuje hamowania, zatrzymania ani ustąpienia pierwszeństwa.', 'Poza obszarem zabudowanym podczas mgły krótkie sygnały są obowiązkowe przy wyprzedzaniu lub omijaniu.', 'Sygnał pojazdu samochodowego ma mieć ciągły i nieprzeraźliwy ton.'],
                'published_at' => $june20PublishedAt,
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'zabezpieczenie-ladunku-i-wymiary' => [
                'title' => 'Zabezpieczenie ładunku: mocowanie, wymiary i oznakowanie',
                'meta_title' => 'Zabezpieczenie ładunku - mocowanie, wymiary i oznakowanie',
                'meta_description' => 'Sprawdź, jak prawidłowo rozmieścić i zamocować ładunek, ile może wystawać poza pojazd oraz jak oznaczyć jego wystającą część.',
                'intro' => 'Bezpieczny przewóz ładunku zaczyna się przed ruszeniem. Trzeba sprawdzić masę, rozmieszczenie, mocowanie, widoczność oraz to, czy wystające elementy mieszczą się w limitach i są prawidłowo oznaczone.',
                'summary' => 'Ładunek nie może przeciążać pojazdu, naruszać jego stateczności, utrudniać kierowania ani zasłaniać świateł lub tablic. Musi być zabezpieczony przed przemieszczaniem, a jego wystające części wymagają zachowania ustawowych wymiarów i oznakowania.',
                'exam_context' => 'Pytania egzaminacyjne sprawdzają najczęściej zabezpieczenie przed przesuwaniem, wpływ ładunku na stateczność, przewóz materiałów sypkich, limity wystawania z przodu i z tyłu oraz czerwono-białe oznakowanie końca ładunku.',
                'body' => <<<'TEXT'
Najpierw sprawdź masę. Ładunek nie może spowodować przekroczenia dopuszczalnej masy całkowitej ani dopuszczalnej ładowności pojazdu. Dotyczy to samochodu, motocykla, motoroweru i przyczepy. Sam fakt, że przedmioty mieszczą się w przestrzeni ładunkowej, nie oznacza jeszcze, że wolno je przewieźć.

Następnie oceń rozmieszczenie. Ładunek nie może naruszać stateczności pojazdu ani utrudniać kierowania. Ciężkie elementy powinny być ułożone możliwie nisko i stabilnie. Szczególnej uwagi wymaga motocykl i motorower, ponieważ niewłaściwe położenie ładunku szybciej wpływa na równowagę i możliwość wykonania manewru.

Każdy ładunek trzeba zabezpieczyć przed zmianą położenia. Pasy, liny, łańcuchy i inne urządzenia mocujące również muszą być zabezpieczone, aby podczas jazdy nie rozluźniły się, nie zwisały i nie spadły. Po gwałtownym hamowaniu albo innym zdarzeniu, które mogło zmienić ułożenie ładunku, rozsądna kontrola mocowania jest konieczna przed dalszą jazdą.

Ładunek nie może ograniczać widoczności drogi ani zasłaniać świateł, kierunkowskazów, tablic rejestracyjnych lub innych wymaganych oznaczeń pojazdu. W pytaniu egzaminacyjnym sprawdź więc nie tylko pasy mocujące, lecz także to, czy tył przyczepy pozostaje widoczny.

Materiały sypkie przewozi się w szczelnej skrzyni ładunkowej, dodatkowo zabezpieczonej zasłonami. Chodzi o to, aby piasek, ziemia lub inne drobne materiały nie wysypywały się i nie pyliły na drogę.

Przy ładunku wystającym poza pojazd liczą się konkretne liczby. Z tyłu jest to co do zasady najwyżej 2 m od tylnej płaszczyzny obrysu pojazdu lub zespołu pojazdów. Z przodu ładunek nie może wystawać dalej niż 0,5 m od przedniej płaszczyzny obrysu i dalej niż 1,5 m od siedzenia kierującego. Szczególny wyjątek dotyczy drewna długiego przewożonego na przyczepie kłonicowej.

Wystający ładunek trzeba oznaczyć. Z tyłu stosuje się pasy białe i czerwone umieszczone bezpośrednio na ładunku, tarczy albo odpowiedniej bryle geometrycznej. Przy samochodzie osobowym lub jego przyczepie dopuszczalna jest również czerwona chorągiewka. Nie wybieraj oznaczenia wyłącznie po kolorze: w pytaniu może decydować też powierzchnia i sposób jego umieszczenia.

Na egzaminie analizuj zawsze tę samą kolejność: masa, stateczność, mocowanie, widoczność, rodzaj ładunku, wymiary oraz oznakowanie. Dzięki temu nie przeoczysz zakazu, który działa jeszcze zanim zaczniesz oceniać sam wygląd zabezpieczenia.
TEXT,
                'key_points' => ['Ładunek nie może przekraczać dopuszczalnej masy ani ładowności pojazdu.', 'Mocowanie ma zapobiegać zmianie położenia ładunku, a jego elementy nie mogą się rozluźniać ani zwisać.', 'Ładunek nie może pogarszać stateczności, kierowania, widoczności ani zasłaniać świateł i tablic.', 'Materiały sypkie wymagają szczelnej skrzyni i odpowiednich zasłon.', 'Wystający ładunek musi mieścić się w ustawowych limitach i być prawidłowo oznaczony.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'przyczepa-masa-wymiary-i-oswietlenie' => [
                'title' => 'Przyczepa: masa, wymiary i obowiązkowe oświetlenie',
                'meta_title' => 'Przyczepa - masa, długość zespołu i oświetlenie',
                'meta_description' => 'Sprawdź limity masy przyczepy, maksymalną długość zespołu pojazdów oraz wymagania dotyczące świateł odblaskowych przyczepy.',
                'intro' => 'Przyczepa zmienia nie tylko sposób prowadzenia pojazdu. Przed jazdą trzeba sprawdzić dopuszczalne masy, długość całego zespołu, widoczność świateł oraz wymagane wyposażenie odblaskowe.',
                'summary' => 'Rzeczywista masa przyczepy musi odpowiadać rodzajowi pojazdu ciągnącego, a długość zespołu nie może przekraczać ustawowych limitów. Przyczepa powinna również zachować wymagane oznakowanie i oświetlenie, w tym boczne światła odblaskowe.',
                'exam_context' => 'Egzamin zestawia limity 4 m i 18,75 m z pytaniami o rzeczywistą masę przyczepy samochodu osobowego lub motocykla. Osobną grupę stanowią pytania o obowiązkowe światła odblaskowe oraz błędne odpowiedzi o technice cofania czy przewozie osób.',
                'body' => <<<'TEXT'
Zacznij od rozróżnienia masy rzeczywistej od wartości wpisanych w dokumentach. W pytaniach o relację pojazdu ciągnącego i przyczepy art. 62 posługuje się rzeczywistą masą całkowitą, czyli masą pojazdu wraz z tym, co faktycznie znajduje się na nim w danej chwili.

W przypadku przyczepy ciągniętej przez samochód osobowy jej rzeczywista masa całkowita co do zasady nie może przekraczać rzeczywistej masy całkowitej samochodu. To ustawowa granica ruchowa, ale przed podpięciem przyczepy nadal trzeba sprawdzić także wartości dopuszczalne wynikające z dokumentów pojazdu i uprawnień kierującego.

Dla motocykla i motoroweru ograniczenie jest ostrzejsze. Rzeczywista masa całkowita przyczepy nie może przekraczać masy własnej pojazdu ciągnącego, a jednocześnie nie może być większa niż 100 kg. Oba warunki muszą być spełnione równocześnie.

Druga grupa pytań dotyczy długości całego zespołu. Motocykl albo motorower wraz z przyczepą może mieć najwyżej 4 m długości. Zespół złożony z dwóch innych pojazdów, na przykład samochodu osobowego i przyczepy kempingowej albo autobusu i przyczepy, może mieć najwyżej 18,75 m.

Do przyczepy stosuje się także ogólne zasady przewozu ładunku. Nie wolno przekraczać jej dopuszczalnej ładowności, a ładunek trzeba zabezpieczyć przed przesuwaniem i ułożyć tak, aby nie pogarszał stateczności całego zespołu. Szczegóły mocowania, wymiary wystających elementów i ich oznakowanie opisuje osobny artykuł o zabezpieczeniu ładunku.

Widoczność przyczepy nie kończy się na tylnych lampach. Przepisy techniczne wymagają między innymi bocznych świateł odblaskowych barwy żółtej samochodowej. Ładunek nie może ich zasłaniać, podobnie jak tylnych świateł, kierunkowskazów i tablicy rejestracyjnej.

Nie każde pytanie zawierające słowo „przyczepa” dotyczy masy lub wymiarów. Cofanie, jazda po łuku, wjazd na wzniesienie, przewóz osób i holowanie wynikają z innych reguł. Na egzaminie najpierw rozpoznaj, czy pytanie sprawdza parametr prawny, wyposażenie, sposób prowadzenia czy przewożenie pasażerów.
TEXT,
                'key_points' => ['Przyczepa samochodu osobowego nie może być rzeczywiście cięższa od samochodu, który ją ciągnie.', 'Przyczepa motocykla lub motoroweru nie może przekraczać masy pojazdu ciągnącego ani 100 kg.', 'Motocykl lub motorower z przyczepą może mieć najwyżej 4 m długości.', 'Typowy zespół dwóch pojazdów może mieć najwyżej 18,75 m długości.', 'Przyczepa wymaga widocznych świateł i bocznych elementów odblaskowych.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'wjazd-na-przejazd-kolejowy' => [
                'title' => 'Przejazd kolejowy: kiedy nie wolno wjechać na tory',
                'meta_title' => 'Przejazd kolejowy - kiedy nie wolno wjechać na tory',
                'meta_description' => 'Sprawdź zasady wjazdu na przejazd kolejowy, znaczenie zapór, czerwonego sygnału i znaku STOP oraz zakaz blokowania torowiska.',
                'intro' => 'Przed przejazdem kolejowym nie wystarczy zobaczyć, że pociąg już odjechał. Trzeba sprawdzić sygnały, zapory, oznakowanie i możliwość całkowitego opuszczenia torowiska.',
                'summary' => 'Na przejazd nie wolno wjechać przy czerwonym sygnale, podczas opuszczania zapór ani przed zakończeniem ich podnoszenia. Zakaz obowiązuje również wtedy, gdy za torami nie ma miejsca do dalszej jazdy.',
                'exam_context' => 'Pytania egzaminacyjne pokazują czerwone migające światła, półzapory w ruchu, znak B-20 STOP, kolejkę pojazdów za przejazdem oraz próbę wyprzedzania bezpośrednio przed torami. Jeden detal może całkowicie zmienić odpowiedź.',
                'body' => <<<'TEXT'
Zbliżając się do przejazdu kolejowego, zachowaj szczególną ostrożność. Przed wjazdem upewnij się, czy nie nadjeżdża pojazd szynowy, zwłaszcza gdy widoczność torów jest ograniczona. Brak widocznego pociągu nie uchyla jednak znaków, sygnałów ani działania zapór.

Prędkość musi umożliwiać zatrzymanie przed torem. Nie chodzi o gwałtowne hamowanie na ostatnich metrach, lecz o wcześniejsze rozpoznanie przejazdu i dojazd w sposób pozwalający bezpiecznie zareagować na pociąg, sygnalizację lub zamykające się zapory.

Zapory i półzapory tworzą jednoznaczny zakaz. Nie wolno ich objeżdżać, gdy są opuszczone, ani wjeżdżać na przejazd, jeżeli rozpoczęło się ich opuszczanie. Trzeba także czekać do całkowitego zakończenia podnoszenia. Sama szeroka luka obok półzapory albo pewność, że pociąg już przejechał, nie pozwalają ominąć urządzenia.

Czerwony sygnał migający nadal zakazuje wjazdu, nawet gdy zapory są już wysoko. Najpierw musi zniknąć sygnał zabraniający. W pytaniach egzaminacyjnych to częsta pułapka: kierujący koncentruje się na pozycji zapory i ignoruje działające czerwone światła.

Znak B-20 STOP wymaga rzeczywistego zatrzymania pojazdu. Samo zwolnienie i sprawdzenie torów podczas jazdy nie wystarczają. Zatrzymaj się w miejscu wyznaczonym, a jeżeli go nie wskazano, tam, skąd możesz ocenić sytuację bez wjeżdżania na torowisko.

Przed wjazdem oceń także drogę za przejazdem. Jeżeli korek lub zatrzymane pojazdy nie pozostawiają miejsca na pełne opuszczenie torów, musisz pozostać przed przejazdem. Zielone światło, podniesione zapory lub brak pociągu nie pozwalają zatrzymać się na torowisku.

Na przejeździe kolejowym i bezpośrednio przed nim zabronione jest wyprzedzanie. Próba szybszego minięcia kolejki zwiększa ryzyko utknięcia na torach i może uniemożliwić reakcję na urządzenia zabezpieczające.

Brak znaku STOP nie oznacza automatycznego obowiązku zatrzymania przed każdym przejazdem. Nadal trzeba zwolnić, zachować szczególną ostrożność i upewnić się, że można bezpiecznie przejechać. Odpowiedź zależy więc od całego oznakowania i aktualnego stanu urządzeń.
TEXT,
                'key_points' => ['Czerwony sygnał zabrania wjazdu niezależnie od pozycji zapór.', 'Nie wolno wjeżdżać podczas opuszczania zapór ani przed zakończeniem ich podnoszenia.', 'Znak B-20 STOP wymaga pełnego zatrzymania pojazdu.', 'Wjazd jest zabroniony, jeżeli za przejazdem nie ma miejsca do kontynuowania jazdy.', 'Na przejeździe i bezpośrednio przed nim nie wolno wyprzedzać.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'holowanie-pojazdu' => [
                'title' => 'Holowanie pojazdu: prędkość, oznakowanie i zakazy',
                'meta_title' => 'Holowanie pojazdu - prędkość, połączenie i zakazy',
                'meta_description' => 'Sprawdź limity prędkości podczas holowania, wymagane oznakowanie, odległość między pojazdami oraz miejsca i sytuacje objęte zakazem.',
                'intro' => 'Holowanie wymaga przygotowania obu pojazdów i kierujących. Przed ruszeniem trzeba dobrać właściwe połączenie, sprawdzić hamulce, oznakowanie i trasę.',
                'summary' => 'Pojazd holujący jedzie na światłach mijania, a holowany musi być właściwie oznakowany. Obowiązują limity 30 i 60 km/h, wymagania dotyczące połączenia oraz zakazy między innymi na autostradzie.',
                'exam_context' => 'Egzamin sprawdza limity prędkości, trójkąt z tyłu po lewej stronie, wymagania dla połączenia giętkiego i sztywnego, holowanie motocykla, sprawność hamulców oraz wyjątek od zakazu na autostradzie.',
                'body' => <<<'TEXT'
Podczas holowania prędkość nie może przekraczać 30 km/h w obszarze zabudowanym i 60 km/h poza nim. Są to maksymalne wartości. Warunki drogi, rodzaj połączenia lub zachowanie pojazdu holowanego mogą wymagać jeszcze wolniejszej jazdy.

Pojazd holujący musi mieć włączone światła mijania również przy dobrej widoczności. Pojazd holowany oznacza się z tyłu po lewej stronie trójkątem ostrzegawczym. Przy niedostatecznej widoczności powinien mieć także włączone światła pozycyjne. Zamiast trójkąta może wysyłać widoczny dla innych uczestników żółty sygnał błyskowy.

Światła awaryjne nie zastępują ustawowego oznakowania pojazdu holowanego. Mogą również utrudnić sygnalizowanie skrętów, dlatego egzamin wymaga rozpoznania świateł i oznaczeń wskazanych wprost w art. 31.

Rodzaj połączenia wpływa na odległość i wymagania techniczne. Przy połączeniu sztywnym odległość między pojazdami może wynosić najwyżej 3 m. Przy giętkim powinna wynosić od 4 do 6 m, a samo połączenie musi być oznakowane naprzemiennymi pasami białymi i czerwonymi albo chorągiewką odpowiedniej barwy.

W pojeździe holowanym powinien znajdować się kierujący posiadający właściwe uprawnienia, chyba że zastosowany sposób holowania wyklucza potrzebę kierowania nim. Motocykl wolno holować wyłącznie za pomocą połączenia giętkiego umożliwiającego łatwe odczepienie.

Przy połączeniu sztywnym w pojeździe holowanym musi być sprawny co najmniej jeden układ hamulcowy. Połączenie giętkie wymaga sprawności dwóch układów. Nie wolno holować pojazdu z niesprawnym układem kierowniczym lub hamulcowym, chyba że sposób holowania całkowicie wyklucza potrzebę ich używania.

Nie wolno holować więcej niż jednego pojazdu, poza wyjątkiem dotyczącym pojazdu członowego. Zabronione jest także holowanie pojazdem, do którego jest już przyczepiona przyczepa lub naczepa. Osoba kierująca pojazdem holowanym nie może być po użyciu alkoholu, nietrzeźwa ani pod wpływem środka działającego podobnie.

Na autostradzie zwykłe holowanie jest zabronione. Do najbliższego wyjazdu lub miejsca obsługi podróżnych może holować wyłącznie pojazd przeznaczony do holowania. Samochód osobowy nie korzysta z tego wyjątku tylko dlatego, że odległość do zjazdu jest niewielka.
TEXT,
                'key_points' => ['Maksymalna prędkość wynosi 30 km/h w obszarze zabudowanym i 60 km/h poza nim.', 'Pojazd holujący używa świateł mijania, a holowany jest oznaczony z tyłu po lewej stronie.', 'Połączenie sztywne oznacza maksymalnie 3 m, a giętkie od 4 do 6 m.', 'Motocykl holuje się wyłącznie na połączeniu giętkim umożliwiającym łatwe odczepienie.', 'Zwykłe holowanie na autostradzie jest zabronione.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'pasy-bezpieczenstwa' => [
                'title' => 'Pasy bezpieczeństwa: kto musi zapinać i jakie są wyjątki',
                'meta_title' => 'Pasy bezpieczeństwa - obowiązek kierowcy i pasażerów',
                'meta_description' => 'Sprawdź, kto musi korzystać z pasów bezpieczeństwa, czy obowiązek dotyczy tylnego siedzenia i autobusu oraz jakie wyjątki przewiduje ustawa.',
                'intro' => 'Pasy bezpieczeństwa nie są obowiązkowe tylko na szybkich drogach ani wyłącznie dla osób siedzących z przodu. Zasadą jest korzystanie z nich przez kierującego i pasażerów podczas każdej jazdy.',
                'summary' => 'Jeżeli pojazd lub siedzenie jest wyposażone w pasy, kierujący i przewożone osoby mają obowiązek z nich korzystać. Ustawa przewiduje zamknięty katalog wyjątków, którego nie wolno rozszerzać na podstawie wygody lub krótkiej trasy.',
                'exam_context' => 'Pytania egzaminacyjne sprawdzają obowiązek kierowcy, pasażera z tyłu i osoby dorosłej w autobusie. Osobno pojawiają się dwa jednoznaczne wyjątki: kobieta o widocznej ciąży oraz dziecko poniżej 3 lat przewożone autobusem.',
                'body' => <<<'TEXT'
Podstawowa reguła jest prosta: kierujący pojazdem samochodowym oraz osoba przewożona pojazdem wyposażonym w pasy bezpieczeństwa mają korzystać z nich podczas jazdy. Obowiązek nie zależy od rodzaju drogi, pory dnia, prędkości ani długości trasy.

Pasy zapina również dorosły pasażer na tylnym siedzeniu. Tylna kanapa nie jest strefą zwolnioną z obowiązku. W razie zderzenia niezapięta osoba może uderzyć w przednie fotele, innych pasażerów albo wypaść z pojazdu.

Ta sama zasada dotyczy autobusu, jeżeli konkretne siedzenie jest wyposażone w pas bezpieczeństwa. Dorosły pasażer korzysta z takiego pasa podczas jazdy. Sam fakt podróżowania autobusem nie tworzy ogólnego zwolnienia.

Ustawa przewiduje konkretne wyjątki. Z obowiązku korzystania z pasów zwolniona jest między innymi kobieta o widocznej ciąży. Przepis wymaga widocznej ciąży i nie tworzy ogólnego wyjątku dla każdej osoby, której pas wydaje się niewygodny.

Osobny wyjątek dotyczy dziecka w wieku poniżej 3 lat przewożonego autobusem. Nie należy przenosić tej zasady na samochód osobowy ani na starsze dziecko. Przewóz dzieci w fotelikach i innych urządzeniach przytrzymujących jest odrębnym, szerszym tematem prawnym.

Pozostałe wyjątki również są wymienione w ustawie, na przykład dla osoby mającej odpowiednie zaświadczenie lekarskie, taksówkarza podczas przewożenia pasażera lub niektórych służb w czasie wykonywania czynności. Na egzaminie nie zakładaj zwolnienia, jeżeli opis sytuacji nie wskazuje konkretnej ustawowej przesłanki.

Prawidłowe zapięcie ma znaczenie praktyczne, ale art. 39 przede wszystkim ustanawia obowiązek korzystania z pasów. Pas powinien przylegać do ciała, nie być skręcony, a jego część biodrowa powinna przebiegać nisko. Szczegółowe pytania o uszkodzenia taśmy, kontrolki lub współpracę z poduszką powietrzną dotyczą wiedzy technicznej i bezpieczeństwa, a nie katalogu wyjątków.
TEXT,
                'key_points' => ['Pasy zapina kierujący i każda przewożona osoba, jeżeli pojazd lub siedzenie jest w nie wyposażone.', 'Obowiązek dotyczy także dorosłego pasażera na tylnym siedzeniu.', 'Dorosły pasażer autobusu używa pasa, jeżeli jego siedzenie jest w niego wyposażone.', 'Kobieta o widocznej ciąży jest ustawowo zwolniona z obowiązku korzystania z pasów.', 'Wyjątek dla dziecka poniżej 3 lat dotyczy przewozu autobusem.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'swiatla-do-jazdy-dziennej-i-mijania' => [
                'title' => 'Światła mijania i dzienne: kiedy których używać',
                'meta_title' => 'Światła mijania i do jazdy dziennej - zasady używania',
                'meta_description' => 'Sprawdź całodobowy obowiązek świateł mijania, kiedy mogą je zastąpić światła dzienne i dlaczego nie wolno ich używać po zmierzchu lub przy złej widoczności.',
                'intro' => 'Światła do jazdy dziennej nie są zamiennikiem świateł mijania w każdej sytuacji. Można ich używać tylko w określonej porze i przy normalnej przejrzystości powietrza.',
                'summary' => 'Podczas jazdy zasadą są światła mijania. Od świtu do zmierzchu, przy normalnej przejrzystości powietrza, mogą je zastąpić światła do jazdy dziennej. Po zmierzchu albo przy pogorszeniu widoczności trzeba przejść na właściwe światła.',
                'exam_context' => 'Pytania egzaminacyjne zestawiają jasny dzień z nocą, pochmurną pogodę z opadami i mgłą oraz sprawdzają, czy obowiązek świateł działa tylko zimą. Decydują jednocześnie pora dnia i przejrzystość powietrza.',
                'body' => <<<'TEXT'
W warunkach normalnej przejrzystości powietrza kierujący używa podczas jazdy świateł mijania. Obowiązek działa przez cały rok, a nie tylko jesienią lub zimą. Nie zależy również od tego, czy droga znajduje się w obszarze zabudowanym.

Światła do jazdy dziennej mogą zastąpić mijania wyłącznie od świtu do zmierzchu i tylko przy normalnej przejrzystości powietrza. Oba warunki muszą być spełnione równocześnie. Pochmurny dzień nadal może oznaczać normalną przejrzystość, jeżeli widoczności nie ograniczają opady, mgła lub inne zjawisko.

Po zmierzchu światła dzienne nie wystarczają, nawet na dobrze oświetlonej ulicy. Oświetlenie drogi nie zmienia ich ustawowego przeznaczenia. Kierujący powinien używać świateł mijania albo innych świateł dopuszczonych w konkretnej sytuacji.

Gdy przejrzystość powietrza jest zmniejszona przez mgłę, deszcz, śnieg lub inne opady, nie wolno pozostać wyłącznie na światłach dziennych. Kierujący pojazdem silnikowym włącza światła mijania albo przednie przeciwmgłowe, albo oba rodzaje jednocześnie. Światła dzienne często nie włączają tylnych lamp, co dodatkowo pogarsza widoczność pojazdu od tyłu.

Reguły dotyczą również motoroweru i czterokołowca lekkiego, jeżeli pojazd jest wyposażony w światła do jazdy dziennej. W dzień i przy normalnej przejrzystości można ich użyć zamiast mijania. Przy pogarszającej się pogodzie trzeba włączyć właściwe światła mijania.

Nie mieszaj tej zasady ze światłami drogowymi. Drogowe służą do oświetlania nieoświetlonej drogi od zmierzchu do świtu i podlegają obowiązkowi nieoślepiania. Pytania o zmianę świateł drogowych na mijania sprawdzają inną część art. 51.

Na egzaminie stosuj dwa pytania kontrolne: czy jest czas od świtu do zmierzchu oraz czy przejrzystość powietrza jest normalna. Jeżeli choć jedna odpowiedź brzmi „nie”, same światła do jazdy dziennej nie są prawidłowym wyborem.
TEXT,
                'key_points' => ['Obowiązek używania świateł mijania działa przez cały rok.', 'Światła dzienne mogą zastąpić mijania tylko od świtu do zmierzchu.', 'Drugim warunkiem użycia świateł dziennych jest normalna przejrzystość powietrza.', 'Po zmierzchu dobrze oświetlona droga nie pozwala pozostać na światłach dziennych.', 'Mgła, deszcz lub śnieg wymagają włączenia świateł odpowiednich do zmniejszonej przejrzystości.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'rogatki-i-sygnaly-na-przejezdzie-kolejowym' => [
                'title' => 'Rogatki i czerwone światło: kiedy wolno ruszyć',
                'meta_title' => 'Rogatki i czerwone światło na przejeździe kolejowym',
                'meta_description' => 'Sprawdź, kiedy wolno ruszyć po przejeździe pociągu, co oznacza niepełne podniesienie rogatek i dlaczego czerwony sygnał nadal zabrania wjazdu.',
                'intro' => 'Odjazd pociągu nie oznacza jeszcze pozwolenia na wjazd. Zanim ruszysz, czerwony sygnał musi zgasnąć, a zapory lub półzapory muszą zostać całkowicie podniesione.',
                'summary' => 'Nie wolno objeżdżać opuszczonych zapór ani wjeżdżać od rozpoczęcia ich opuszczania aż do całkowitego zakończenia podnoszenia. Podniesiona zapora nie zwalnia z obserwacji torów, a czerwony sygnał nadal samodzielnie zakazuje wjazdu.',
                'exam_context' => 'Pytania pokazują zapory w ruchu, półzapory pozostawiające wolną przestrzeń oraz czerwone światła działające już po przejeździe pociągu. Kluczowe jest sprawdzenie osobno sygnału, położenia zapór i sytuacji na torach.',
                'body' => <<<'TEXT'
Zbliżając się do przejazdu kolejowego, zachowaj szczególną ostrożność i upewnij się, czy nie nadjeżdża pojazd szynowy. Ten obowiązek działa również wtedy, gdy zapory są podniesione i sygnalizacja nie nadaje czerwonego światła. Urządzenia zabezpieczające pomagają, ale nie zastępują obserwacji torów.

Prędkość dojazdu powinna pozwalać zatrzymać pojazd w bezpiecznym miejscu, jeżeli pojawi się pociąg albo zadziałają urządzenia zabezpieczające. Nie czekaj z oceną sytuacji do chwili, gdy przód samochodu znajdzie się przy zaporze.

Jeżeli zapory lub półzapory są opuszczone, nie wolno ich objeżdżać. Zakaz obowiązuje także od chwili rozpoczęcia ich opuszczania. Wolna przestrzeń obok półzapory nie jest przejazdem pozostawionym dla samochodów.

Po przejeździe pociągu trzeba czekać do całkowitego zakończenia podnoszenia zapór. Niepełne podniesienie, ruch ramienia do góry albo przekonanie, że następny pociąg nie nadjedzie, nie pozwalają rozpocząć wjazdu.

Czerwony sygnał migający jest oddzielnym zakazem. Jeżeli nadal działa, nie wolno wjechać nawet wtedy, gdy półzapory są już całkowicie podniesione. Ruszyć można dopiero po wyłączeniu sygnału, pełnym otwarciu zapór i ponownym upewnieniu się, że przejazd jest bezpieczny.

Podniesienie zapór nie daje bezwzględnej gwarancji, że tor jest wolny. Awaria, opóźnienie urządzenia lub nietypowa sytuacja nie znoszą obowiązku szczególnej ostrożności. Dlatego po otwarciu przejazdu spójrz w obie strony i przejedź bez niepotrzebnego zatrzymywania się na torach.

Na egzaminie analizuj obraz w stałej kolejności: najpierw czerwone światła, następnie położenie i ruch zapór, potem widoczność torów oraz możliwość całkowitego opuszczenia przejazdu. Jeden aktywny zakaz wystarczy, aby odpowiedź brzmiała „nie”.
TEXT,
                'key_points' => ['Czerwony sygnał zakazuje wjazdu niezależnie od położenia zapór.', 'Nie wolno wjechać od rozpoczęcia opuszczania zapór aż do całkowitego zakończenia ich podnoszenia.', 'Półzapory nie wolno objeżdżać przez pozostawioną obok przestrzeń.', 'Podniesione zapory nie zwalniają z upewnienia się, czy nie nadjeżdża pociąg.', 'Przed ruszeniem sprawdź osobno sygnalizację, zapory i sytuację na torach.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'swiatla-przeciwmglowe' => [
                'title' => 'Światła przeciwmgłowe: przednie, tylne i granica 50 metrów',
                'meta_title' => 'Światła przeciwmgłowe - kiedy używać przednich i tylnych',
                'meta_description' => 'Sprawdź zasady używania przednich i tylnych świateł przeciwmgłowych, próg widoczności 50 m oraz wyjątek dotyczący oznakowanej drogi krętej.',
                'intro' => 'Przednie i tylne światła przeciwmgłowe nie działają według tej samej zasady. Najważniejsza różnica to próg widoczności 50 m, który dotyczy świateł tylnych.',
                'summary' => 'Przy zmniejszonej przejrzystości można używać przednich świateł przeciwmgłowych zamiast mijania albo razem z nimi. Tylne wolno włączyć dopiero przy widoczności mniejszej niż 50 m i trzeba je wyłączyć natychmiast po poprawie warunków.',
                'exam_context' => 'Egzamin zestawia intensywny deszcz i mgłę z widocznością 50 lub 100 m, pyta o moment wyłączenia tylnych lamp oraz o pogodną noc na oznakowanej drodze krętej. Najpierw ustal, czy pytanie dotyczy świateł przednich czy tylnych.',
                'body' => <<<'TEXT'
Podczas mgły, intensywnych opadów albo innej zmniejszonej przejrzystości kierujący pojazdem silnikowym włącza światła mijania lub przednie przeciwmgłowe, albo oba rodzaje jednocześnie. Przednie światła przeciwmgłowe nie wymagają więc widoczności poniżej 50 m.

Można ich użyć także podczas intensywnego deszczu lub śniegu, jeżeli opady rzeczywiście zmniejszają przejrzystość powietrza. Sam pochmurny dzień, lekki deszcz albo chęć lepszego oświetlenia pobocza nie tworzą automatycznie takiej sytuacji.

Tylne światła przeciwmgłowe mają odrębną, liczbową granicę. Wolno je włączyć tylko wtedy, gdy zmniejszona przejrzystość ogranicza widoczność na odległość mniejszą niż 50 m. Przy widoczności dokładnie 50 m albo większej warunek nie jest spełniony.

Gdy widoczność się poprawi, tylne światła trzeba niezwłocznie wyłączyć. Dotyczy to także sytuacji, w której zasięg widzenia wzrasta do 60 lub 100 m. Mocne czerwone światło może wtedy oślepiać kierującego jadącego z tyłu i utrudniać mu zauważenie świateł hamowania.

Przyczyna ograniczenia widoczności nie musi być mgłą. Przepis obejmuje również śnieg, intensywny deszcz lub inne zjawisko, jeżeli faktyczny zasięg widzenia spada poniżej 50 m. Na egzaminie decyduje odległość, a nie sama nazwa pogody.

Istnieje szczególny wyjątek dla przednich świateł przeciwmgłowych. Od zmierzchu do świtu można ich używać na drodze krętej oznaczonej odpowiednimi znakami także przy normalnej przejrzystości powietrza. Muszą jednak wystąpić łącznie: nocna pora i właściwe oznakowanie drogi.

Najprostszy schemat egzaminacyjny brzmi: przednie światła oceniaj przez warunki przejrzystości albo nocny wyjątek na oznakowanej drodze krętej; tylne zawsze oceniaj przez próg mniejszy niż 50 m. Nie przenoś limitu 50 m na światła przednie.
TEXT,
                'key_points' => ['Przy zmniejszonej przejrzystości przednie przeciwmgłowe mogą działać samodzielnie albo razem ze światłami mijania.', 'Próg widoczności mniejszej niż 50 m dotyczy tylnych świateł przeciwmgłowych.', 'Po poprawie widoczności tylne światła trzeba niezwłocznie wyłączyć.', 'Śnieg lub intensywny deszcz także mogą uzasadniać tylne światła, jeżeli widoczność spada poniżej 50 m.', 'W nocy przednich przeciwmgłowych można używać na odpowiednio oznakowanej drodze krętej także przy dobrej widoczności.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'foteliki-i-przewoz-dzieci' => [
                'title' => 'Przewóz dzieci: fotelik, wzrost 150 cm i wyjątki',
                'meta_title' => 'Przewóz dzieci w foteliku - wzrost 150 cm i wyjątki',
                'meta_description' => 'Sprawdź, kiedy dziecko musi jechać w foteliku, jak działa granica 150 i 135 cm, kiedy wolno przewieźć trzecie dziecko oraz czego zabraniają przepisy.',
                'intro' => 'O obowiązku użycia fotelika decyduje przede wszystkim wzrost dziecka i wyposażenie pojazdu, a nie sam wiek. Wyjątki są konkretne i nie działają na przednim siedzeniu w taki sam sposób jak z tyłu.',
                'summary' => 'Dziecko mające mniej niż 150 cm co do zasady przewozi się w urządzeniu przytrzymującym dobranym do jego masy i wzrostu. Przepisy przewidują ograniczone wyjątki, ale utrzymują szczególne zakazy dotyczące przedniego siedzenia, aktywnej poduszki oraz pojazdu bez pasów.',
                'exam_context' => 'Pytania egzaminacyjne sprawdzają granice 150 i 135 cm, montaż zgodny z instrukcją, wyłączenie poduszki przy foteliku tyłem, trzecie dziecko między dwoma fotelikami oraz zaświadczenie lekarskie.',
                'body' => <<<'TEXT'
W samochodzie osobowym i innych wskazanych pojazdach wyposażonych w pasy dziecko mające mniej niż 150 cm wzrostu przewozi się w foteliku bezpieczeństwa albo innym urządzeniu przytrzymującym. Urządzenie musi odpowiadać masie i wzrostowi dziecka oraz spełniać właściwe wymagania techniczne.

Sam zakup odpowiedniego fotelika nie wystarcza. Trzeba go zamontować zgodnie z zaleceniami producenta urządzenia, które określają bezpieczny sposób stosowania. Dotyczy to między innymi przebiegu pasa, użycia mocowań i kierunku ustawienia.

Na przednim siedzeniu dziecko mające mniej niż 150 cm nie może podróżować poza fotelikiem lub innym urządzeniem przytrzymującym. Wyjątek pozwalający niektórym dzieciom od 135 cm korzystać wyłącznie z pasa dotyczy tylko tylnego siedzenia i wymaga, aby ze względu na masę oraz wzrost nie można było zapewnić odpowiedniego urządzenia.

Fotelik ustawiony tyłem do kierunku jazdy może znaleźć się na przednim siedzeniu tylko wtedy, gdy poduszka powietrzna pasażera nie jest aktywna podczas przewozu. Aktywna poduszka tworzy ustawowy zakaz niezależnie od długości trasy.

Dziecka poniżej 3 lat nie wolno przewozić w samochodzie osobowym lub innym objętym przepisem pojeździe, który nie ma pasów bezpieczeństwa i odpowiedniego urządzenia przytrzymującego. Wiek nie zastępuje więc zabezpieczeń technicznych.

Szczególny wyjątek dotyczy tylnego siedzenia zajętego przez dwa urządzenia przytrzymujące. Jeżeli nie można zainstalować trzeciego, pomiędzy nimi może podróżować trzecie dziecko mające co najmniej 3 lata, pod warunkiem przytrzymania go pasami bezpieczeństwa.

Obowiązek korzystania z urządzenia nie dotyczy między innymi dziecka mającego właściwe zaświadczenie lekarskie o przeciwwskazaniu do takiego przewozu. Nie jest to jednak ogólne zwolnienie wynikające z dyskomfortu ani decyzji opiekuna.

Na egzaminie najpierw ustal wzrost dziecka, później miejsce w pojeździe, a następnie rodzaj zabezpieczenia. Przy foteliku tyłem sprawdź poduszkę powietrzną, a przy trzecim dziecku na tylnej kanapie jego wiek, dwa zamontowane urządzenia i brak możliwości instalacji kolejnego.
TEXT,
                'key_points' => ['Dziecko poniżej 150 cm co do zasady korzysta z urządzenia przytrzymującego dobranego do masy i wzrostu.', 'Fotelik instaluje się zgodnie z zaleceniami producenta urządzenia.', 'Wyjątek od 135 cm dotyczy wyłącznie tylnego siedzenia i określonych warunków.', 'Fotelik tyłem na przednim siedzeniu wymaga nieaktywnej poduszki pasażera.', 'Trzecie dziecko mające co najmniej 3 lata może siedzieć między dwoma fotelikami tylko wtedy, gdy nie można zamontować trzeciego urządzenia.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'obowiazki-uczestnika-wypadku' => [
                'title' => 'Wypadek drogowy: obowiązki uczestnika krok po kroku',
                'meta_title' => 'Wypadek drogowy - obowiązki uczestnika zdarzenia',
                'meta_description' => 'Sprawdź, jak zabezpieczyć miejsce wypadku, kiedy udzielić pomocy i wezwać służby, czy wolno przestawić pojazd oraz jakie dane przekazać uczestnikom.',
                'intro' => 'Po zdarzeniu drogowym pierwsza decyzja zależy od tego, czy ktoś został ranny lub zginął. Od tego rozróżnienia zależy wezwanie służb, przemieszczanie pojazdów i możliwość opuszczenia miejsca.',
                'summary' => 'Każde zdarzenie wymaga bezpiecznego zatrzymania i zabezpieczenia miejsca. Przy rannych trzeba udzielić pomocy, wezwać ratownictwo i Policję, nie zmieniać śladów oraz pozostać na miejscu. Gdy nie ma ofiar, pojazdy usuwa się, jeżeli utrudniają ruch.',
                'exam_context' => 'Egzamin rozdziela wypadek z rannymi od kolizji powodującej wyłącznie szkody materialne. Pytania dotyczą pomocy, telefonów do służb, usuwania pojazdów, zakazu oddalania się i wymiany danych.',
                'body' => <<<'TEXT'
Po zdarzeniu kierujący zatrzymuje pojazd w sposób, który nie powoduje kolejnego zagrożenia. Następnie podejmuje odpowiednie działania dla bezpieczeństwa ruchu, na przykład ostrzega innych uczestników, włącza właściwe światła i ustawia trójkąt, jeżeli wymagają tego miejsce oraz rodzaj drogi.

Najważniejsza granica przebiega między zdarzeniem bez osób rannych lub zabitych a wypadkiem z ofiarami. Jeżeli są poszkodowani, priorytetem jest ich bezpieczeństwo: trzeba udzielić niezbędnej pomocy oraz wezwać zespół ratownictwa medycznego i Policję. Obowiązek pomocy nie zależy od posiadania wykształcenia medycznego.

Przy rannych nie wolno podejmować czynności, które utrudniłyby ustalenie przebiegu zdarzenia. Oznacza to między innymi, że co do zasady nie usuwa się pojazdów tylko po to, aby udrożnić ruch. Wyjątkiem mogą być działania konieczne dla ratowania życia lub zapobieżenia bezpośredniemu niebezpieczeństwu.

Uczestnik pozostaje na miejscu. Może oddalić się wyłącznie wtedy, gdy jest to konieczne do wezwania ratownictwa lub Policji, po czym musi niezwłocznie powrócić. Samo wykonanie telefonu nie kończy jego obowiązków.

Jeżeli nie ma osób zabitych ani rannych, pojazd należy niezwłocznie usunąć z miejsca, gdy pozostawienie go powodowałoby zagrożenie lub tamowanie ruchu. W takiej sytuacji Prawo o ruchu drogowym nie tworzy automatycznego obowiązku wezwania Policji do każdego zdarzenia.

Na żądanie innej osoby uczestniczącej w zdarzeniu kierujący podaje swoje dane personalne, dane właściciela lub posiadacza pojazdu oraz informacje o ubezpieczeniu OC. Wymiana danych jest potrzebna także wtedy, gdy strony nie wzywają Policji.

Art. 44 formułuje większość opisanych obowiązków bezpośrednio wobec kierującego. Ustęp 3 rozszerza na inne osoby uczestniczące w wypadku obowiązki z ust. 1 pkt 1-3. W pytaniach użycie słowa „uczestnik” często opisuje kierującego biorącego udział w zdarzeniu, dlatego zawsze sprawdź, jaką rolę wskazuje cały opis.

Na egzaminie zacznij od pytania: czy są ranni lub zabici? Jeżeli tak, wybieraj pomoc, służby, zachowanie miejsca i pozostanie na miejscu. Jeżeli nie, skup się na zabezpieczeniu ruchu, usunięciu pojazdów i wymianie danych.
TEXT,
                'key_points' => ['Najpierw zatrzymaj pojazd bez tworzenia dodatkowego zagrożenia i zabezpiecz miejsce.', 'Przy rannych udziel pomocy oraz wezwij ratownictwo medyczne i Policję.', 'Gdy są ranni, nie przemieszczaj pojazdów ani innych elementów, jeżeli utrudniłoby to ustalenie przebiegu zdarzenia.', 'Przy zdarzeniu bez ofiar usuń pojazd, jeżeli zagraża bezpieczeństwu lub tamuje ruch.', 'Na żądanie innego uczestnika przekaż dane swoje, właściciela pojazdu i ubezpieczenia OC.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'telefon-podczas-kierowania' => [
                'title' => 'Telefon za kierownicą: co dokładnie jest zabronione',
                'meta_title' => 'Telefon podczas jazdy - trzymanie w ręku i zestaw głośnomówiący',
                'meta_description' => 'Sprawdź, kiedy korzystanie z telefonu podczas kierowania jest zabronione, czy tryb głośnomówiący wystarcza i jak przepis obejmuje samochód, tramwaj oraz hulajnogę.',
                'intro' => 'Przepis nie wprowadza całkowitego zakazu każdej rozmowy telefonicznej. Zakazuje korzystania z telefonu w sposób wymagający trzymania słuchawki lub mikrofonu w ręku podczas jazdy.',
                'summary' => 'Kierujący nie może podczas jazdy trzymać telefonu w ręku, także przy włączonym trybie głośnomówiącym. Dopuszczalny może być zestaw niewymagający trzymania urządzenia, o ile nie odrywa uwagi od bezpiecznego kierowania.',
                'exam_context' => 'Pytania zestawiają telefon trzymany przy uchu lub w dłoni z urządzeniem zamocowanym w uchwycie albo zestawem głośnomówiącym. Zakaz dotyczy kierujących różnymi pojazdami, w tym samochodem, tramwajem i hulajnogą elektryczną.',
                'body' => <<<'TEXT'
Art. 45 ust. 2 pkt 1 Prawa o ruchu drogowym opisuje zakaz przez sposób korzystania z telefonu. Podczas jazdy kierujący nie może używać urządzenia, jeżeli wymaga to trzymania słuchawki lub mikrofonu w ręku. Nie decyduje więc nazwa funkcji telefonu, lecz to, czy urządzenie pozostaje w dłoni.

Tryb głośnomówiący nie usuwa zakazu, jeżeli telefon nadal jest trzymany w ręku. Przepis obejmuje zarówno rozmowę z telefonem przy uchu, jak i trzymanie go przed sobą podczas połączenia. Odłożenie urządzenia na fotel lub kolana także nie tworzy bezpiecznego sposobu obsługi, jeżeli kierujący sięga po nie i przestaje kontrolować drogę.

Zestaw głośnomówiący, system samochodowy albo telefon umieszczony w stabilnym uchwycie nie wymagają trzymania słuchawki lub mikrofonu. Z perspektywy tego konkretnego zakazu mogą być używane, ale nie zwalniają kierującego z obowiązku zachowania ostrożności i pełnej kontroli nad pojazdem.

Zakaz jest skierowany do kierującego pojazdem, a nie wyłącznie do kierowcy samochodu osobowego. Dlatego pytania egzaminacyjne odnoszą go również do motorniczego tramwaju oraz osoby kierującej hulajnogą elektryczną. Rodzaj pojazdu nie zmienia kluczowej reguły dotyczącej trzymania telefonu.

Na egzaminie najpierw sprawdź, czy pojazd jest w ruchu, a następnie spójrz na ręce kierującego. Jeżeli telefon, słuchawka albo mikrofon są trzymane w dłoni, odpowiedź o dozwolonym korzystaniu jest błędna. Jeżeli urządzenie działa bez trzymania, sam art. 45 ust. 2 pkt 1 nie ustanawia zakazu.

Nie traktuj jednak legalności technicznego sposobu rozmowy jako potwierdzenia, że każda rozmowa jest bezpieczna. Jeżeli obsługa ekranu, wybieranie numeru lub czytanie wiadomości odciągają uwagę, kierujący może nie reagować prawidłowo na sytuację drogową mimo użycia uchwytu.
TEXT,
                'key_points' => ['Zakaz dotyczy telefonu wymagającego trzymania słuchawki lub mikrofonu w ręku.', 'Tryb głośnomówiący nie pomaga, jeżeli telefon nadal znajduje się w dłoni.', 'Telefon w uchwycie lub system samochodowy mogą spełniać warunek obsługi bez trzymania.', 'Reguła dotyczy także kierującego tramwajem, rowerem lub hulajnogą elektryczną.', 'Na egzaminie patrz przede wszystkim na ręce kierującego i sposób korzystania z urządzenia.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'swiatla-drogowe-i-oslepianie' => [
                'title' => 'Światła drogowe: kiedy wolno ich używać i kiedy je wyłączyć',
                'meta_title' => 'Światła drogowe - używanie, zmiana na mijania i oślepianie',
                'meta_description' => 'Sprawdź warunki używania świateł drogowych, zakaz oślepiania oraz obowiązek zmiany na światła mijania przy pojazdach z przeciwka i jadących przed Tobą.',
                'intro' => 'Światła drogowe poprawiają widoczność nocą, ale wolno ich używać tylko na nieoświetlonej drodze i tylko wtedy, gdy nie oślepiają innych uczestników ruchu.',
                'summary' => 'Od zmierzchu do świtu na nieoświetlonej drodze można używać świateł drogowych zamiast mijania lub razem z nimi. Trzeba je przełączyć na mijania przed oślepieniem kierującego jadącego z przeciwka albo przed nami.',
                'exam_context' => 'Pytania pokazują jazdę w dzień, oświetlone odcinki, pojazd nadjeżdżający z przeciwka oraz samochód, który właśnie wyprzedził i znalazł się przed nami. Każdy z tych szczegółów może wykluczyć dalsze używanie świateł drogowych.',
                'body' => <<<'TEXT'
Światła drogowe nie są podstawowym oświetleniem do każdej jazdy nocnej. Art. 51 ust. 3 pozwala ich używać od zmierzchu do świtu na drodze nieoświetlonej. Mogą wtedy zastąpić światła mijania albo działać razem z nimi, ale tylko pod warunkiem, że nie oślepiają innych kierujących ani pieszych poruszających się w kolumnie.

Najpierw sprawdź porę i oświetlenie drogi. W dzień światła drogowe nie służą do zwykłego oświetlania trasy. Po zmierzchu również nie należy ich używać na odcinku oświetlonym w sposób zapewniający widoczność. Sam fakt, że jest ciemno, nie wystarcza.

Drugim krokiem jest ocena innych uczestników. Gdy z przeciwka zbliża się pojazd, światła drogowe trzeba odpowiednio wcześnie przełączyć na mijania. Obowiązek działa także wtedy, gdy tamten kierujący sam niewłaściwie pozostawia włączone światła drogowe. Nie wolno odpowiadać oślepianiem.

Światła drogowe mogą oślepić także kierującego jadącego przed nami przez jego lusterka. Dlatego przy zbliżaniu się do pojazdu poprzedzającego należy przełączyć je na mijania, jeżeli jego kierujący może zostać oślepiony. Ta sama zasada działa po zakończeniu wyprzedzania: pojazd, który znalazł się przed nami, jest już pojazdem poprzedzającym.

Nie czekaj ze zmianą świateł do chwili, gdy drugi kierowca wyraźnie zareaguje. Przepis wymaga zapobiegania oślepieniu. W praktyce decyzja powinna nastąpić na tyle wcześnie, aby silny strumień światła nie ograniczył widzenia drugiej osoby.

Na egzaminie analizuj sytuację w stałej kolejności: pora dnia, oświetlenie drogi, pojazd z przeciwka, pojazd przed Tobą i możliwość oślepienia. Jeżeli którykolwiek z wymaganych warunków nie jest spełniony, dalsze używanie świateł drogowych nie jest prawidłowe.
TEXT,
                'key_points' => ['Światła drogowe są dozwolone od zmierzchu do świtu na drodze nieoświetlonej.', 'Nie wolno oślepiać innych kierujących ani pieszych poruszających się w kolumnie.', 'Przy pojeździe z przeciwka trzeba odpowiednio wcześnie przełączyć światła na mijania.', 'Pojazd jadący przed Tobą może zostać oślepiony przez lusterka.', 'Po wyprzedzeniu przez inny pojazd traktuj go jako pojazd poprzedzający i zmień światła na mijania.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'gasnica-trojkat-i-obowiazkowe-wyposazenie' => [
                'title' => 'Wyposażenie pojazdu: gaśnica, trójkąt i najczęstsze pułapki',
                'meta_title' => 'Gaśnica, trójkąt i obowiązkowe wyposażenie pojazdu',
                'meta_description' => 'Sprawdź, jak przewozić gaśnicę, kiedy wymagany jest trójkąt, jakie wyposażenie dotyczy motocykla i ciągnika oraz kiedy potrzebne są dwie gaśnice.',
                'intro' => 'Lista wyposażenia zależy od rodzaju i przeznaczenia pojazdu. Inne wymagania dotyczą typowego samochodu, inne motocykla, ciągnika rolniczego albo samochodu ciężarowego przewożącego osoby.',
                'summary' => 'Gaśnica ma być łatwo dostępna, a nie ukryta pod bagażem. Samochód wyposaża się również w homologowany trójkąt ostrzegawczy. Motocykl ma własne wymagania dotyczące lusterek i sygnału dźwiękowego, a światła awaryjne są w nim dopuszczalne, lecz nie zawsze obowiązkowe.',
                'exam_context' => 'Pytania sprawdzają miejsce przewożenia gaśnicy, wyposażenie motocykla, gaśnicę w ciągniku oraz dwie gaśnice w samochodzie ciężarowym przewożącym osoby poza kabiną. Osobno pojawia się popularna pułapka z apteczką.',
                'body' => <<<'TEXT'
Podstawowe wymagania techniczne nie tworzą jednej identycznej listy dla każdego pojazdu. Na egzaminie najpierw ustal, czy pytanie dotyczy samochodu osobowego, motocykla, ciągnika rolniczego, przyczepy albo pojazdu wykorzystywanego do szczególnego przewozu osób.

Gaśnica w pojeździe samochodowym ma znajdować się w miejscu łatwo dostępnym w razie potrzeby. Powinna być prawidłowo zamocowana, aby nie przemieszczała się podczas jazdy, ale jednocześnie nie może być schowana pod warstwą bagażu. Liczy się możliwość szybkiego i bezpiecznego wyjęcia.

Trójkąt ostrzegawczy służy do ostrzegania o obecności unieruchomionego pojazdu. Typowy samochód powinien mieć trójkąt ze znakiem homologacji. Rozporządzenie przewiduje jednak wyjątki dla motocykli jednośladowych, dlatego nie należy mechanicznie przenosić pełnej listy wyposażenia samochodu na motocykl.

Motocykl musi mieć sygnał dźwiękowy. Jeżeli jego prędkość maksymalna przekracza 100 km/h, wymagane są co najmniej dwa lusterka zewnętrzne, po jednym z każdej strony. Światła awaryjne mogą być zamontowane, lecz rozporządzenie wymienia je jako wyposażenie dopuszczalne motocykla, a nie obowiązkowe dla każdego egzemplarza.

Ciągnik rolniczy również podlega obowiązkowi wyposażenia w gaśnicę. Jeszcze szersze wymaganie dotyczy samochodu ciężarowego przystosowanego do przewozu osób poza kabiną kierowcy: potrzebne są dwie gaśnice, jedna przy kabinie i druga wewnątrz przestrzeni przewozowej.

Apteczka jest rozsądnym i zalecanym wyposażeniem każdego samochodu, ale rozporządzenie nie ustanawia jej jako uniwersalnego obowiązku dla każdego pojazdu samochodowego. Szczególne rodzaje pojazdów lub sposób ich wykorzystywania mogą podlegać dodatkowym wymaganiom, dlatego pytanie zawsze trzeba czytać do końca.

Pytania o sposób gaszenia pożaru sprawdzają także wiedzę praktyczną. Przed próbą użycia gaśnicy należy ocenić zagrożenie i nie narażać siebie ani pasażerów. Przy pożarze komory silnika nie otwiera się gwałtownie całej pokrywy, ponieważ dopływ tlenu może zwiększyć intensywność ognia.
TEXT,
                'key_points' => ['Gaśnica ma być zamocowana i łatwo dostępna, a nie ukryta pod bagażem.', 'Typowy samochód wymaga homologowanego trójkąta ostrzegawczego.', 'Motocykl musi mieć sygnał dźwiękowy, a szybszy niż 100 km/h także dwa lusterka.', 'Światła awaryjne motocykla są dopuszczalne, lecz nie są obowiązkowe w każdym motocyklu.', 'Ciągnik wymaga gaśnicy, a ciężarówka przewożąca osoby poza kabiną wymaga dwóch gaśnic.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'opony-bieznik-i-cisnienie' => [
                'title' => 'Opony: minimalny bieżnik, ciśnienie i zgodność na osi',
                'meta_title' => 'Opony, bieżnik i ciśnienie - wymagania techniczne',
                'meta_description' => 'Sprawdź minimalną głębokość bieżnika 1,6 mm, wymagania dla opon na jednej osi oraz skutki zbyt niskiego i zbyt wysokiego ciśnienia.',
                'intro' => 'Stan opon ocenia się przez trzy osobne elementy: zużycie bieżnika, zgodność opon na jednej osi oraz ciśnienie dobrane do pojazdu i jego obciążenia.',
                'summary' => 'Opony bez wskaźnika granicznego zużycia muszą mieć co najmniej 1,6 mm bieżnika. Na jednej osi powinny mieć jednakową konstrukcję i rzeźbę bieżnika, a ciśnienie musi odpowiadać zaleceniom producenta.',
                'exam_context' => 'Egzamin pyta o wartość 1,6 mm, zużyty bieżnik na mokrej nawierzchni, zgodność opon przyczepy rolniczej oraz wpływ zbyt niskiego lub zbyt wysokiego ciśnienia na przyczepność i hamowanie.',
                'body' => <<<'TEXT'
Najpierw sprawdź zużycie bieżnika. Jeżeli opona ma wskaźnik granicznego zużycia, nie wolno jej używać po osiągnięciu tego wskaźnika. Gdy producent nie zastosował wskaźnika, rzeźba bieżnika nie może mieć głębokości mniejszej niż 1,6 mm. Ta ogólna wartość pojawia się również w pytaniach dotyczących samochodu ciężarowego.

Sama minimalna liczba nie opisuje całego bezpieczeństwa opony. Płytki bieżnik gorzej odprowadza wodę, zwiększa ryzyko utraty przyczepności i może wydłużyć drogę hamowania na mokrej nawierzchni. Dlatego oponę warto wymienić wcześniej, jeżeli jej zachowanie albo stan techniczny budzą wątpliwości.

Na kołach jednej osi nie wolno stosować opon o różnej konstrukcji, w tym o różnej rzeźbie bieżnika. Reguła ma znaczenie również dla przyczepy rolniczej. Opony na osi powinny współpracować w podobny sposób podczas hamowania, skrętu i przenoszenia obciążenia.

Ciśnienie powinno odpowiadać wartości zalecanej przez producenta pojazdu albo opony dla danego obciążenia. Informację można znaleźć w instrukcji, na etykiecie pojazdu lub w danych producenta. Pomiar wykonuj na zimnych oponach, ponieważ rozgrzane powietrze podnosi odczyt.

Zbyt niskie ciśnienie zwiększa ugięcie opony, pogarsza precyzję prowadzenia i może powodować nadmierne nagrzewanie. Zbyt wysokie zmniejsza powierzchnię prawidłowego kontaktu bieżnika z nawierzchnią, co może pogorszyć przyczepność i skuteczność hamowania.

Ciśnienie oleju silnikowego i ciśnienie w oponach to odrębne zagadnienia. Czerwona kontrolka oleju wymaga bezpiecznego zatrzymania i wyłączenia silnika, ale nie jest informacją o stanie ogumienia. Na egzaminie nie łącz tych pytań tylko dlatego, że zawierają słowo „ciśnienie”.

Przed jazdą obejrzyj opony także pod kątem pęknięć, wybrzuszeń, przecięć i ciał obcych. Prawidłowa głębokość bieżnika nie legalizuje opony, która ma widoczne uszkodzenie zagrażające bezpieczeństwu.
TEXT,
                'key_points' => ['Bez wskaźnika granicznego zużycia minimalna głębokość bieżnika wynosi 1,6 mm.', 'Zużyty bieżnik pogarsza odprowadzanie wody i hamowanie na mokrej nawierzchni.', 'Opony na jednej osi muszą mieć jednakową konstrukcję, w tym rzeźbę bieżnika.', 'Ciśnienie dobiera się według zaleceń producenta i obciążenia pojazdu.', 'Zbyt niskie i zbyt wysokie ciśnienie mogą pogorszyć przyczepność oraz skuteczność hamowania.'],
                'published_at' => $june21PublishedAt,
                'last_reviewed_at' => $june21ReviewedAt,
            ],
            'dokumenty-podczas-kontroli-drogowej' => [
                'title' => 'Dokumenty podczas kontroli drogowej: co trzeba mieć przy sobie',
                'meta_title' => 'Dokumenty podczas kontroli drogowej - co trzeba okazać',
                'meta_description' => 'Sprawdź, których dokumentów nie trzeba wozić przy krajowym pojeździe, a kiedy wymagane są pokwitowania, dokumenty blokady alkoholowej, OC lub dokument jazdy testowej.',
                'intro' => 'Brak ogólnego obowiązku wożenia polskiego prawa jazdy i dowodu rejestracyjnego nie oznacza, że podczas każdej kontroli wystarczy podać dane. Art. 38 wskazuje sytuacje, w których dokument nadal trzeba mieć i okazać.',
                'summary' => 'W typowej jeździe krajowym pojazdem dane polskiego prawa jazdy i dowodu rejestracyjnego są sprawdzane elektronicznie. Dokumenty są jednak wymagane między innymi przy innym dokumencie uprawnienia, zatrzymanym prawie jazdy lub dowodzie, blokadzie alkoholowej, pojeździe zarejestrowanym za granicą i jeździe testowej.',
                'exam_context' => 'Pytania egzaminacyjne rozróżniają zwykłą jazdę krajowym pojazdem od kontroli pojazdu zagranicznego, jazdy na pokwitowaniu oraz pojazdu wyposażonego w blokadę alkoholową. Najczęstszy błąd to uznanie jednej zasady za właściwą dla każdego przypadku.',
                'body' => <<<'TEXT'
Przy kontroli drogowej najpierw ustal, jaki dokument i jaki pojazd opisuje pytanie. W zwykłej jeździe pojazdem zarejestrowanym w Polsce nie ma ogólnego obowiązku wożenia krajowego prawa jazdy ani dowodu rejestracyjnego, ponieważ uprawnienia i dane pojazdu mogą być sprawdzone w systemach teleinformatycznych. Nie jest to jednak zwolnienie dotyczące wszystkich dokumentów i wszystkich sytuacji.

Jeżeli kierujący posługuje się dokumentem stwierdzającym uprawnienie innym niż wydane w kraju prawo jazdy, pozwolenie na kierowanie tramwajem albo ich tymczasowa elektroniczna postać, powinien mieć ten dokument przy sobie i okazać go na żądanie uprawnionego organu. W pytaniu trzeba więc odróżnić polski dokument widoczny w systemie od innego dokumentu uprawnienia.

Osobną grupę tworzą pokwitowania. Gdy prawo jazdy lub pozwolenie na kierowanie tramwajem zostało zatrzymane, ważne pokwitowanie może czasowo zastępować dokument i trzeba je okazać. Tak samo działa pokwitowanie zatrzymania dowodu rejestracyjnego albo pozwolenia czasowego, jeżeli nadal zezwala na używanie pojazdu. Reguła dotyczy również dokumentów przyczepy.

Pojazd wyposażony w blokadę alkoholową wymaga dodatkowych dokumentów. Kierujący powinien okazać zaświadczenie z badania technicznego potwierdzające prawidłowe działanie blokady oraz dokument jej kalibracji. Sam wpis w dowodzie rejestracyjnym nie zastępuje obu tych dokumentów.

Przy pojeździe zarejestrowanym za granicą zakres kontroli jest szerszy. Kierujący okazuje dokument dopuszczający pojazd do ruchu oraz dokument potwierdzający zawarcie obowiązkowego ubezpieczenia OC albo dowód opłacenia składki. Nie należy przenosić na taki przypadek uproszczenia dotyczącego krajowego dowodu rejestracyjnego.

Podczas jazdy testowej trzeba okazać dokument stwierdzający dopuszczenie pojazdu do ruchu. Przepisy szczególne mogą też wymagać innych dokumentów, dlatego art. 38 zawiera odesłanie do obowiązków wynikających z odrębnych ustaw.

Na egzaminie stosuj prostą kolejność: krajowy czy zagraniczny pojazd, zwykły dokument czy pokwitowanie, blokada alkoholowa czy jej brak, jazda zwykła czy testowa. Dopiero po takim rozpoznaniu oceń, co kierujący ma obowiązek mieć przy sobie.
TEXT,
                'key_points' => ['Polskiego prawa jazdy i dowodu rejestracyjnego krajowego pojazdu co do zasady nie trzeba wozić tylko po to, aby organ odczytał dane z systemu.', 'Inny dokument stwierdzający uprawnienie do kierowania trzeba mieć i okazać.', 'Ważne pokwitowanie może zastępować zatrzymane prawo jazdy albo dowód rejestracyjny.', 'Blokada alkoholowa wymaga zaświadczenia z badania technicznego i dokumentu kalibracji.', 'Pojazd zarejestrowany za granicą wymaga dokumentu dopuszczenia do ruchu oraz potwierdzenia OC.'],
                'published_at' => $june22PublishedAt,
                'last_reviewed_at' => $june22ReviewedAt,
            ],
            'kategorie-prawa-jazdy-i-uprawnienia' => [
                'title' => 'Kategorie prawa jazdy: czym możesz kierować',
                'meta_title' => 'Kategorie prawa jazdy i zakres uprawnień',
                'meta_description' => 'Poznaj zakres kategorii AM, A1, A2, A, B1, B i T, uprawnienia do motocykla 125 cm3 i ciągnika oraz zasady ważności i wymiany prawa jazdy.',
                'intro' => 'Kategoria prawa jazdy określa dokładny zakres uprawnienia. Na egzaminie nie wystarczy rozpoznać pojazdu po wyglądzie: znaczenie mają masa, moc, pojemność, liczba kół, przyczepa i terytorium, na którym działa rozszerzenie.',
                'summary' => 'AM obejmuje motorower i czterokołowiec lekki, kategorie A1, A2 i A stopniują uprawnienia motocyklowe, B1 dotyczy czterokołowca, B obejmuje podstawowe pojazdy do 3,5 t, a T ciągniki i zespoły z przyczepami. Część rozszerzeń kategorii B działa tylko w Polsce.',
                'exam_context' => 'Pytania wymagają odróżnienia motoroweru od motocykla, czterokołowca lekkiego od czterokołowca, sprawdzenia limitów 125 cm3, 11 kW i 0,1 kW/kg oraz zauważenia, że niektóre uprawnienia kategorii B są krajowymi wyjątkami.',
                'body' => <<<'TEXT'
Zakres kategorii wynika z parametrów pojazdu, a nie z jego potocznej nazwy. Przed odpowiedzią sprawdź dopuszczalną masę całkowitą, pojemność i moc silnika, stosunek mocy do masy, liczbę kół oraz to, czy pojazd ciągnie przyczepę. Kierujący musi mieć odpowiedni i ważny dokument stwierdzający uprawnienie.

Kategoria AM obejmuje motorower i czterokołowiec lekki. A1 pozwala kierować między innymi motocyklem o pojemności do 125 cm3, mocy do 11 kW i stosunku mocy do masy własnej do 0,1 kW/kg oraz motocyklem trójkołowym do 15 kW. A2 obejmuje motocykle przewidziane dla tej kategorii, motocykl trójkołowy do 15 kW i pojazdy kategorii AM. Kategoria A daje uprawnienie do kierowania motocyklem bez ograniczeń właściwych dla A1 i A2.

Kategoria B1 dotyczy czterokołowca i obejmuje także pojazdy kategorii AM. Nie należy mylić czterokołowca z każdym małym samochodem osobowym. O kwalifikacji pojazdu decyduje jego kategoria homologacyjna i parametry techniczne.

Podstawowy zakres kategorii B obejmuje pojazd samochodowy o dopuszczalnej masie całkowitej do 3,5 t, z wyjątkiem autobusu i motocykla, oraz taki pojazd z przyczepą lekką. Inne konfiguracje zespołu pojazdów mogą wymagać spełnienia dodatkowych warunków albo kategorii B+E.

Na terytorium Polski kategoria B pozwala również kierować ciągnikiem rolniczym lub pojazdem wolnobieżnym z przyczepą lekką. Posiadacz kategorii B od co najmniej 3 lat może w Polsce kierować motocyklem do 125 cm3, 11 kW i 0,1 kW/kg. Te rozszerzenia są zapisane jako uprawnienia krajowe, więc nie należy automatycznie zakładać, że obowiązują za granicą.

Kategoria T obejmuje ciągnik rolniczy lub pojazd wolnobieżny oraz zespoły złożone z takiego pojazdu i przyczepy albo przyczep. To szerszy zakres przyczep niż krajowe rozszerzenie kategorii B ograniczone do przyczepy lekkiej.

Prawo jazdy jest wydawane na określony czas. Upływ terminu ważności dokumentu wymaga jego odnowienia zgodnie z ustawą; samo wcześniejsze zdanie egzaminu nie zastępuje ważnego dokumentu. Utratę prawa jazdy, jego zniszczenie powodujące nieczytelność albo zmianę danych wymagającą nowego dokumentu trzeba zgłosić staroście w terminie 30 dni.

W zadaniu egzaminacyjnym analizuj kolejno: rodzaj pojazdu, jego parametry, przyczepę, posiadaną kategorię, okres jej posiadania, ważność dokumentu i terytorium. Jedno słowo, takie jak „motocykl” albo „ciągnik”, zwykle nie wystarcza do rozstrzygnięcia.
TEXT,
                'key_points' => ['AM obejmuje motorower i czterokołowiec lekki.', 'A1 wymaga jednoczesnego spełnienia limitów 125 cm3, 11 kW i 0,1 kW/kg.', 'Podstawowy zakres B to pojazd do 3,5 t, z wyjątkiem autobusu i motocykla, także z przyczepą lekką.', 'Uprawnienie kategorii B do motocykla 125 cm3 po 3 latach oraz do ciągnika z przyczepą lekką działa na terytorium Polski.', 'Utratę, nieczytelne zniszczenie albo wymaganą zmianę danych zgłasza się staroście w terminie 30 dni.'],
                'published_at' => $june22PublishedAt,
                'last_reviewed_at' => $june22ReviewedAt,
            ],
            'alkohol-i-srodki-dzialajace-podobnie' => [
                'title' => 'Alkohol za kierownicą: limity, zakaz i odpowiedzialność',
                'meta_title' => 'Alkohol za kierownicą - limity i odpowiedzialność',
                'meta_description' => 'Sprawdź progi stanu po użyciu alkoholu i nietrzeźwości, zakaz prowadzenia pojazdu oraz różnicę między wykroczeniem i przestępstwem.',
                'intro' => 'W pytaniach o alkohol nie oceniaj samopoczucia kierowcy. Najpierw sprawdź, czy opis dotyczy zakazu prowadzenia, ustawowego progu stężenia czy rodzaju odpowiedzialności.',
                'summary' => 'Kierowanie pojazdem po użyciu alkoholu, w stanie nietrzeźwości albo po środku działającym podobnie jest zabronione. Ustawa rozróżnia progi stanu po użyciu i nietrzeźwości, a Kodeks wykroczeń oraz Kodeks karny przewidują odmienne podstawy odpowiedzialności za prowadzenie pojazdu mechanicznego.',
                'exam_context' => 'Powiązane pytania sprawdzają zakaz kierowania samochodem, motorowerem lub tramwajem, progi 0,2‰ i 0,1 mg/dm3, kwalifikację stanu po użyciu alkoholu oraz zakaz holowania pojazdu kierowanego przez osobę po alkoholu. Pytania o zmęczenie, pole widzenia i subiektywną ocenę ryzyka pozostają poza relacjami prawnymi tej strony.',
                'body' => <<<'TEXT'
Najbezpieczniejsza reguła decyzyjna brzmi: jeżeli piłeś alkohol albo nie masz pewności co do swojej trzeźwości, nie kieruj. Samopoczucie, kawa, prysznic, sen ani przekonanie, że „to było wczoraj”, nie potwierdzają zdolności do legalnej i bezpiecznej jazdy.

Art. 45 ust. 1 pkt 1 Prawa o ruchu drogowym zabrania kierowania pojazdem osobie w stanie nietrzeźwości, w stanie po użyciu alkoholu albo środka działającego podobnie do alkoholu. Zakaz nie ogranicza się do samochodu osobowego. W pytaniach egzaminacyjnych może dotyczyć także motoroweru lub tramwaju.

Ustawa o wychowaniu w trzeźwości rozdziela dwa zakresy. Stan po użyciu alkoholu zachodzi, gdy stężenie wynosi od 0,2‰ do 0,5‰ we krwi albo od 0,1 mg do 0,25 mg alkoholu w 1 dm3 wydychanego powietrza. Stan nietrzeźwości zaczyna się po przekroczeniu 0,5‰ we krwi albo 0,25 mg w 1 dm3 wydychanego powietrza. Wartości graniczne należą więc jeszcze do stanu po użyciu alkoholu.

Przy odpowiedzialności trzeba sprawdzić rodzaj pojazdu i poziom stężenia. Prowadzenie pojazdu mechanicznego w stanie po użyciu alkoholu lub podobnie działającego środka jest wykroczeniem z art. 87 § 1 Kodeksu wykroczeń. Prowadzenie pojazdu mechanicznego w stanie nietrzeźwości lub pod wpływem środka odurzającego jest przestępstwem z art. 178a § 1 Kodeksu karnego. Nie należy jednak sprowadzać decyzji o jeździe do kalkulacji kary: zakaz z art. 45 działa już na poziomie zasad ruchu.

Środek działający podobnie do alkoholu może zaburzać uwagę, czas reakcji, ocenę odległości i koordynację. Jeżeli lek lub inna substancja wpływa na zdolność kierowania, ostrzega o tym ulotka albo lekarz, kierujący powinien zrezygnować z jazdy. Artykuł nie zastępuje indywidualnej oceny medycznej.

Osobny zakaz dotyczy holowania. Nie wolno holować pojazdu, którym kieruje osoba nietrzeźwa, po użyciu alkoholu albo środka działającego podobnie. Trzeźwy kierowca pojazdu holującego nie usuwa więc problemu po stronie osoby kierującej pojazdem holowanym.

Na egzaminie czytaj jednostkę i znak nierówności bardzo dokładnie. Pytanie o wynik przekraczający 0,1 mg/dm3 wydychanego powietrza albo 0,2‰ we krwi opisuje już wartość niezgodną z legalnym kierowaniem. Gdy pytanie dotyczy skutków zmęczenia lub alkoholu dla percepcji, jest to wiedza o bezpieczeństwie, ale nie zawsze bezpośrednie sprawdzenie konkretnej jednostki prawnej.
TEXT,
                'key_points' => ['Po alkoholu lub środku działającym podobnie nie wolno kierować pojazdem.', 'Stan po użyciu alkoholu to od 0,2‰ do 0,5‰ we krwi albo od 0,1 mg do 0,25 mg/dm3 w wydychanym powietrzu.', 'Stan nietrzeźwości zaczyna się powyżej 0,5‰ albo powyżej 0,25 mg/dm3.', 'Stan po użyciu przy pojeździe mechanicznym jest zasadniczo wykroczeniem, a stan nietrzeźwości przestępstwem.', 'Nie wolno holować pojazdu kierowanego przez osobę po alkoholu lub podobnie działającym środku.'],
                'published_at' => $june22PublishedAt,
                'last_reviewed_at' => $june22ReviewedAt,
            ],
            'zielona-strzalka-warunkowa' => [
                'title' => 'Zielona strzałka warunkowa: zatrzymanie i pierwszeństwo',
                'meta_title' => 'Zielona strzałka warunkowa - zatrzymanie i zasady',
                'meta_description' => 'Sprawdź, kiedy wolno skręcić na zielonej strzałce, gdzie trzeba się zatrzymać i komu należy ustąpić przed wykonaniem manewru.',
                'intro' => 'Mała zielona strzałka przy czerwonym świetle nie działa jak zwykły sygnał zielony. Zezwolenie jest warunkowe i zaczyna się od pełnego zatrzymania pojazdu.',
                'summary' => 'Na zielonej strzałce można pojechać tylko w jej kierunku, po zatrzymaniu przed sygnalizatorem i pod warunkiem, że manewr nie utrudni ruchu innym uczestnikom.',
                'exam_context' => 'Wszystkie powiązane pytania pytają, czy wolno skręcić w prawo bez zatrzymania. Prawidłowa odpowiedź brzmi „nie”, ponieważ § 96 ust. 3 wymaga pełnego zatrzymania przed sygnalizatorem niezależnie od tego, czy droga wydaje się pusta.',
                'body' => <<<'TEXT'
Zielona strzałka nadawana razem z czerwonym sygnałem zezwala na ruch tylko w kierunku wskazanym strzałką. Czerwone światło nadal obowiązuje dla pozostałych kierunków, dlatego mały sygnał warunkowy nie jest odpowiednikiem zwykłego zielonego światła.

Pierwszy warunek to zatrzymanie przed sygnalizatorem. Pojazd musi rzeczywiście przestać się poruszać. Bardzo wolne przetoczenie się przez skrzyżowanie, tak zwany rolling stop, nie spełnia tego wymagania. Obowiązek istnieje także wtedy, gdy kierujący nie widzi innych pojazdów ani pieszych.

Drugi warunek to brak utrudnienia ruchu innym uczestnikom. Po zatrzymaniu trzeba ponownie ocenić przejście dla pieszych, przejazd dla rowerów i jezdnię, na którą zamierzasz wjechać. Należy przepuścić pieszych, rowerzystów i pojazdy poruszające się zgodnie z ich sygnałem lub pierwszeństwem.

Strzałka nie przyznaje pierwszeństwa. Daje jedynie warunkową możliwość wykonania manewru mimo czerwonego sygnału głównego. Jeżeli bezpieczny przejazd wymaga dalszego oczekiwania, kierujący pozostaje zatrzymany nawet wtedy, gdy strzałka nadal świeci.

Zielona strzałka może wskazywać także lewo. W takim przypadku zezwala również na zawracanie z lewego skrajnego pasa, chyba że zabrania tego znak B-23. Nadal trzeba najpierw się zatrzymać i nie utrudnić ruchu innym uczestnikom.

Na egzaminie stosuj stałą sekwencję: rozpoznaj czerwony sygnał i małą strzałkę, zatrzymaj pojazd przed sygnalizatorem, sprawdź pieszych i rowerzystów, oceń ruch poprzeczny, a dopiero potem wykonaj manewr. Jeżeli pytanie zawiera słowa „bez zatrzymania”, odpowiedź jest negatywna.
TEXT,
                'key_points' => ['Zielona strzałka zezwala na jazdę tylko w kierunku, który wskazuje.', 'Przed sygnalizatorem trzeba całkowicie zatrzymać pojazd.', 'Powolne przetoczenie się nie zastępuje zatrzymania.', 'Strzałka nie daje pierwszeństwa i nie może utrudnić ruchu innym uczestnikom.', 'Strzałka w lewo może zezwalać na zawracanie z lewego skrajnego pasa, chyba że obowiązuje B-23.'],
                'published_at' => $june22PublishedAt,
                'last_reviewed_at' => $june22ReviewedAt,
            ],
            'pojazd-uprzywilejowany' => [
                'title' => 'Pojazd uprzywilejowany i korytarz życia: jak ustąpić',
                'meta_title' => 'Pojazd uprzywilejowany i korytarz życia - zasady',
                'meta_description' => 'Sprawdź, jak rozpoznać pojazd uprzywilejowany, jak utworzyć korytarz życia oraz kiedy obowiązuje zakaz jego wyprzedzania.',
                'intro' => 'Ułatwienie przejazdu pojazdu uprzywilejowanego nie zaczyna się dopiero wtedy, gdy ambulans stoi za Twoim samochodem. Trzeba wcześnie rozpoznać sygnały, zostawić miejsce i ustawić pojazd zgodnie z liczbą pasów.',
                'summary' => 'Każdy uczestnik ruchu musi niezwłocznie ułatwić przejazd pojazdu uprzywilejowanego. W zatorze kierujący tworzą korytarz między skrajnym lewym pasem a wszystkimi pozostałymi i nie mogą wykorzystywać powstałej drogi do własnej jazdy.',
                'exam_context' => 'Pytania pokazują układ pojazdów tworzących korytarz życia oraz sprawdzają zakaz wyprzedzania pojazdu uprzywilejowanego na obszarze zabudowanym. Przy znaku D-43 trzeba zauważyć, że zakaz z art. 24 ust. 11 dotyczy wyłącznie obszaru zabudowanego.',
                'body' => <<<'TEXT'
Najpierw upewnij się, że chodzi o pojazd uprzywilejowany w rozumieniu ustawy. Podczas jazdy musi on jednocześnie wysyłać niebieskie sygnały błyskowe i sygnał dźwiękowy o zmiennym tonie oraz mieć włączone światła mijania lub drogowe. Sam niebieski błysk albo sam sygnał dźwiękowy nie wystarcza do zastosowania pełnej definicji.

Art. 9 ust. 1 nakazuje uczestnikom ruchu i innym osobom znajdującym się na drodze ułatwić przejazd. W praktyce oznacza to niezwłoczne usunięcie się z toru jazdy, a gdy wymaga tego sytuacja, także zatrzymanie. Manewr ma być przewidywalny: obserwuj otoczenie, sygnalizuj zmianę położenia i nie wjeżdżaj pod inny pojazd.

Korytarz życia tworzy się w warunkach zwiększonego natężenia ruchu, gdy swobodny przejazd pojazdu uprzywilejowanego jest utrudniony. Na jezdni z dwoma pasami w tym samym kierunku kierujący z lewego pasa zjeżdżają jak najbliżej lewej krawędzi, a kierujący z prawego pasa jak najbliżej prawej krawędzi.

Na jezdni z trzema lub większą liczbą pasów zasada nie polega na dzieleniu ruchu po połowie. Tylko pojazdy ze skrajnego lewego pasa zjeżdżają w lewo. Kierujący ze wszystkich pozostałych pasów przesuwają się w prawo. Droga przejazdu powstaje więc zawsze obok skrajnego lewego pasa.

Nie wolno jechać za pojazdem uprzywilejowanym utworzonym korytarzem. Po jego przejeździe kierujący może kontynuować jazdę po pasie, który zajmował wcześniej. Ustawowy wyjątek dotyczy pojazdów zarządcy drogi lub pomocy drogowej biorących udział w akcji ratowniczej.

Wyprzedzanie pojazdu uprzywilejowanego jest zabronione na obszarze zabudowanym. Zakaz nie został zapisany jako ogólny dla każdej drogi. Znak D-43 oznacza wyjazd z obszaru zabudowanego, dlatego po jego minięciu art. 24 ust. 11 nie tworzy już tego szczególnego zakazu, choć każdy manewr nadal musi spełniać pozostałe warunki bezpiecznego wyprzedzania.

W pytaniu egzaminacyjnym stosuj kolejność: rozpoznaj pojazd i jego sygnały, policz pasy w jednym kierunku, sprawdź położenie skrajnego lewego pasa, a następnie oceń, czy pozostali kierujący zjechali w prawo. Przy pytaniu o wyprzedzanie ustal najpierw, czy nadal znajdujesz się na obszarze zabudowanym.
TEXT,
                'key_points' => ['Pojazd uprzywilejowany podczas jazdy używa łącznie niebieskich świateł błyskowych, sygnału dźwiękowego o zmiennym tonie oraz świateł mijania lub drogowych.', 'Każdy uczestnik ruchu ma niezwłocznie ułatwić jego przejazd, a w razie potrzeby się zatrzymać.', 'Skrajny lewy pas zjeżdża w lewo, a wszystkie pozostałe pasy w prawo.', 'Nie wolno wykorzystywać korytarza życia do jazdy za pojazdem uprzywilejowanym.', 'Zakaz wyprzedzania pojazdu uprzywilejowanego z art. 24 ust. 11 dotyczy obszaru zabudowanego.'],
                'published_at' => $june22PublishedAt,
                'last_reviewed_at' => $june22ReviewedAt,
            ],
            'sygnal-zolty-i-zolty-migajacy' => [
                'title' => 'Żółte światło: kiedy się zatrzymać i co oznacza miganie',
                'meta_title' => 'Żółte światło i żółty migający - znaczenie sygnałów',
                'meta_description' => 'Sprawdź znaczenie stałego i migającego żółtego sygnału, wyjątek od zatrzymania oraz różnicę wobec czerwonego i żółtego razem.',
                'intro' => 'Stały sygnał żółty i żółty migający mają inne znaczenie. Pierwszy co do zasady zakazuje wjazdu, drugi ostrzega i wymaga szczególnej ostrożności.',
                'summary' => 'Stałe żółte światło oznacza zakaz wjazdu z wyjątkiem sytuacji, gdy bez gwałtownego hamowania nie można już zatrzymać pojazdu. Żółty migający nie zapowiada kolejnego koloru: ostrzega o zagrożeniu lub utrudnieniu.',
                'exam_context' => 'Powiązane pytania sprawdzają, czy na żółtym migającym wolno przejechać bez obowiązkowego zatrzymania oraz czy miganie zapowiada sygnał zielony albo czerwony. Odpowiedź zależy od odróżnienia § 98 ust. 6 od stałego żółtego z § 95 ust. 1 pkt 2.',
                'body' => <<<'TEXT'
Stały sygnał żółty oznacza zakaz wjazdu za sygnalizator. Nie jest zachętą do przyspieszenia przed zmianą światła. Jeżeli możesz zatrzymać pojazd przed sygnalizatorem bez gwałtownego hamowania, powinieneś to zrobić.

Przepis przewiduje jeden praktyczny wyjątek. Gdy sygnał żółty zapala się w chwili, w której pojazd jest już tak blisko sygnalizatora, że zatrzymanie wymagałoby gwałtownego hamowania, kierujący może kontynuować jazdę. Wyjątku nie ocenia się na podstawie pośpiechu, lecz realnej możliwości bezpiecznego zatrzymania.

Stały sygnał żółty zapowiada, że za chwilę pojawi się sygnał czerwony. Nie należy go mylić z jednoczesnym sygnałem czerwonym i żółtym. Ta druga kombinacja nadal zakazuje wjazdu, ale zapowiada sygnał zielony.

Żółty migający ma funkcję ostrzegawczą. Informuje o niebezpieczeństwie lub utrudnieniu ruchu i nakazuje zachowanie szczególnej ostrożności. Sam w sobie nie wprowadza bezwzględnego obowiązku zatrzymania przed sygnalizatorem.

Możliwość wjazdu na żółtym migającym nie oznacza automatycznego pierwszeństwa ani dowolnej jazdy. Kierujący musi stosować się do znaków, zasad pierwszeństwa i sytuacji na skrzyżowaniu. Jeżeli bezpieczeństwo wymaga zwolnienia albo zatrzymania, trzeba odpowiednio zareagować.

Migający żółty nie jest sygnałem przejściowym w cyklu czerwone-zielone. Nie oznacza więc, że za chwilę zapali się czerwone ani zielone światło. Na egzaminie słowa „migający” i „stały” są kluczowe dla odpowiedzi.

Stosuj prostą sekwencję: ustal, czy żółte światło jest stałe czy miga; przy stałym oceń możliwość zatrzymania bez gwałtownego hamowania; przy migającym wyszukaj zagrożenie, znaki i uczestników mających pierwszeństwo.
TEXT,
                'key_points' => ['Stały żółty co do zasady zakazuje wjazdu za sygnalizator.', 'Można kontynuować jazdę, gdy bez gwałtownego hamowania nie da się już zatrzymać przed sygnalizatorem.', 'Stały żółty zapowiada sygnał czerwony.', 'Żółty migający ostrzega o zagrożeniu lub utrudnieniu i wymaga szczególnej ostrożności.', 'Żółty migający nie zapowiada ani czerwonego, ani zielonego sygnału.'],
                'published_at' => $june22PublishedAt,
                'last_reviewed_at' => $june22ReviewedAt,
            ],
            'wymijanie-omijanie-cofanie' => [
                'title' => 'Wymijanie, omijanie i cofanie',
                'meta_title' => 'Wymijanie, omijanie i cofanie - przepisy do egzaminu',
                'meta_description' => 'Wyjaśnienie zasad wymijania, omijania i cofania: bezpieczny odstęp, zmniejszenie prędkości, ustąpienie pierwszeństwa i zakazy cofania.',
                'intro' => 'Wymijanie, omijanie i cofanie wyglądają podobnie tylko na pierwszy rzut oka. Na egzaminie trzeba rozpoznać manewr i dobrać do niego właściwy obowiązek.',
                'summary' => 'Kierujący ma obowiązek zachować bezpieczny odstęp przy wymijaniu i omijaniu, a przy cofaniu ustąpić pierwszeństwa innym uczestnikom ruchu, zachować szczególną ostrożność i pamiętać o miejscach, gdzie cofanie jest zakazane.',
                'exam_context' => 'Powiązane pytania rozdzielają trzy działania: odstęp i zjazd w prawo przy wymijaniu, odstęp i zmniejszenie prędkości przy omijaniu oraz pierwszeństwo, ostrożność i zakazy podczas cofania.',
                'body' => <<<'TEXT'
Najpierw nazwij manewr. Wymijanie dotyczy przejeżdżania obok uczestnika ruchu jadącego z przeciwnego kierunku. Omijanie dotyczy przejeżdżania obok nieporuszającego się pojazdu, uczestnika ruchu albo przeszkody. Cofanie to osobny manewr, w którym kierujący musi szczególnie uważać na to, czego nie widzi za pojazdem.

Przy wymijaniu podstawą jest bezpieczny odstęp od wymijanego pojazdu lub uczestnika ruchu. Jeżeli sytuacja tego wymaga, kierujący ma zjechać na prawo, zmniejszyć prędkość albo się zatrzymać. Na egzaminie nie szukaj jednej stałej liczby w metrach, tylko oceniaj, czy odstęp jest bezpieczny w danej sytuacji.

Przy omijaniu również trzeba zachować bezpieczny odstęp od omijanego pojazdu, uczestnika ruchu lub przeszkody, a w razie potrzeby zmniejszyć prędkość. Jeżeli pojazd sygnalizuje zamiar skrętu w lewo, jego omijanie może odbywać się tylko z prawej strony.

Cofanie wymaga ustąpienia pierwszeństwa innemu pojazdowi lub uczestnikowi ruchu i zachowania szczególnej ostrożności. Kierujący powinien sprawdzić, czy manewr nie spowoduje zagrożenia albo utrudnienia ruchu, upewnić się, czy za pojazdem nie ma przeszkody, a gdy sam nie może tego dobrze ocenić, zapewnić sobie pomoc innej osoby.

Cofanie nie jest dozwolone wszędzie, nawet przy zachowaniu ostrożności. Przepis zabrania cofania w tunelu, na moście, na wiadukcie, na autostradzie i na drodze ekspresowej. To częsta pułapka w pytaniach: światła awaryjne albo niewielka odległość do zjazdu nie uchylają zakazu.
TEXT,
                'key_points' => ['Przy wymijaniu i omijaniu nie ma jednej stałej odległości: liczy się bezpieczny odstęp.', 'Przy wymijaniu w razie potrzeby trzeba zjechać na prawo, zwolnić albo zatrzymać się.', 'Omijając przeszkodę lub pieszego, trzeba zostawić bezpieczny odstęp i w razie potrzeby zmniejszyć prędkość.', 'Przy cofaniu trzeba ustąpić pierwszeństwa i upewnić się, że za pojazdem nie ma przeszkody.', 'Cofanie jest zakazane w tunelu, na moście, wiadukcie, autostradzie i drodze ekspresowej.'],
                'published_at' => $june13PublishedAt,
                'last_reviewed_at' => $june20ReviewedAt,
            ],
            'wyprzedzanie' => [
                'title' => 'Wyprzedzanie',
                'meta_title' => 'Wyprzedzanie - przepisy do egzaminu',
                'meta_description' => 'Wyjaśnienie zasad wyprzedzania: kiedy wolno rozpocząć manewr, jaki zachować odstęp, kiedy obowiązuje 1 m i gdzie wyprzedzanie jest zabronione.',
                'intro' => 'Wyprzedzanie jest jednym z najbardziej ryzykownych manewrów egzaminacyjnych, bo wymaga jednocześnie oceny widoczności, miejsca, odstępu, znaków i zachowania innych kierujących.',
                'summary' => 'Przed wyprzedzaniem trzeba upewnić się, że manewr da się wykonać bez utrudnienia ruchu, zachować szczególną ostrożność i bezpieczny odstęp, a w określonych sytuacjach powstrzymać się od manewru mimo chęci szybszej jazdy.',
                'exam_context' => 'Powiązane pytania sprawdzają obserwację pojazdu z tyłu, bezpieczny odstęp i minimum 1 m, zakaz zwiększania prędkości przez wyprzedzanego oraz zakazy przy wzniesieniu, na skrzyżowaniu i wobec pojazdu uprzywilejowanego w obszarze zabudowanym.',
                'body' => <<<'TEXT'
Przed rozpoczęciem wyprzedzania nie wystarczy ocenić, że pojazd z przodu jedzie wolno. Trzeba sprawdzić, czy masz odpowiednią widoczność i miejsce, czy kierujący za Tobą nie rozpoczął już wyprzedzania oraz czy pojazd przed Tobą nie sygnalizuje zamiaru wyprzedzania, zmiany kierunku jazdy albo zmiany pasa ruchu.

Podczas wyprzedzania obowiązuje szczególna ostrożność i bezpieczny odstęp od wyprzedzanego pojazdu lub uczestnika ruchu. Minimum 1 m dotyczy wyprzedzania roweru, wózka rowerowego, motoroweru, motocykla, hulajnogi elektrycznej, urządzenia transportu osobistego, osoby poruszającej się przy użyciu urządzenia wspomagającego ruch oraz kolumny pieszych. Nie każdy pojazd ma więc ustawowe minimum 1 m, ale każdy wymaga odstępu bezpiecznego.

Co do zasady wyprzedza się z lewej strony. Wyjątki są konkretne: pojazd szynowy zwykle wyprzedza się z prawej strony, a pojazd lub uczestnika ruchu sygnalizującego zamiar skrętu w lewo można wyprzedzić tylko z prawej strony. Wyprzedzanie z prawej strony jest też dopuszczalne na wybranych odcinkach z wyznaczonymi pasami ruchu, jeśli spełnione są warunki z przepisu.

Są miejsca, w których wyprzedzanie pojazdu silnikowego jest co do zasady zabronione: przy dojeżdżaniu do wierzchołka wzniesienia, na zakręcie oznaczonym znakami ostrzegawczymi oraz na skrzyżowaniu. Przepis przewiduje wyjątki, między innymi dla skrzyżowań o ruchu okrężnym lub takich, na których ruch jest kierowany.

Jeżeli jesteś wyprzedzany, nie wolno Ci zwiększać prędkości w czasie wyprzedzania ani bezpośrednio po nim. Na obszarze zabudowanym nie wolno też wyprzedzać pojazdu uprzywilejowanego. W pytaniach egzaminacyjnych te zasady często decydują o odpowiedzi, nawet gdy sama geometria drogi wygląda pozornie bezpiecznie.
TEXT,
                'key_points' => ['Przed wyprzedzaniem sprawdź widoczność, miejsce oraz zachowanie pojazdów z tyłu i z przodu.', 'Wyprzedzanie wymaga szczególnej ostrożności i bezpiecznego odstępu.', 'Minimum 1 m dotyczy wybranych uczestników ruchu, między innymi roweru, motoroweru, motocykla i hulajnogi elektrycznej.', 'Pojazd wyprzedzany nie może zwiększać prędkości w czasie manewru ani bezpośrednio po nim.', 'Wyprzedzanie pojazdu silnikowego jest co do zasady zabronione przy wierzchołku wzniesienia, na oznaczonym zakręcie i na skrzyżowaniu.'],
                'published_at' => $june13PublishedAt,
                'last_reviewed_at' => $june20ReviewedAt,
            ],
        ];

        foreach ($definitions as $slug => $definition) {
            $pages[$slug] = LegalContentPage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    ...$definition,
                    'legal_topic_id' => $topics[$slug]->getKey(),
                    'source_note' => 'Źródło: oficjalne teksty aktów prawnych w ELI i ISAP. Opracowanie ma charakter edukacyjny i dotyczy kontekstu pytań egzaminacyjnych.',
                    'author_id' => $author->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'published_at' => $definition['published_at'] ?? $publishedAt,
                    'last_reviewed_at' => $definition['last_reviewed_at'] ?? $reviewedAt,
                    'status' => LegalContentPage::STATUS_PUBLISHED,
                ],
            );
        }

        return $pages;
    }

    /**
     * @param  list<LegalUnit>  $units
     */
    private function attachUnits(LegalContentPage $page, array $units): void
    {
        $sync = [];

        foreach (array_values($units) as $index => $unit) {
            $sync[$unit->getKey()] = [
                'relation_type' => 'direct_basis',
                'sort_order' => ($index + 1) * 10,
            ];
        }

        $page->legalUnits()->sync($sync);
    }

    /**
     * @param  list<string>  $externalIds
     * @param  list<LegalUnit>  $units
     */
    private function deleteQuestionReferences(array $externalIds, array $units, LegalTopic $topic): void
    {
        $lookupExternalIds = [];

        foreach ($externalIds as $externalId) {
            $lookupExternalIds[] = $externalId;
            $lookupExternalIds[] = 'pj360:'.$externalId;
        }

        $questionIds = Question::query()
            ->whereIn('external_id', array_values(array_unique($lookupExternalIds)))
            ->pluck('id');

        if ($questionIds->isEmpty()) {
            return;
        }

        $unitIds = array_map(
            fn (LegalUnit $unit): int => (int) $unit->getKey(),
            $units,
        );

        QuestionLegalReference::query()
            ->whereIn('question_id', $questionIds)
            ->whereIn('legal_unit_id', $unitIds)
            ->where('legal_topic_id', $topic->getKey())
            ->where('assignment_source', '!=', QuestionLegalReference::SOURCE_MANUAL)
            ->delete();
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachZawracanieQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['1492', '4211', '6033', '6181', '6185'],
                'unit' => 'art-22-ust-6-pkt-1',
                'note' => 'To pytanie sprawdza ustawowy zakaz zawracania w tunelu, na moście, wiadukcie albo drodze jednokierunkowej. Zakaz wynika bezpośrednio z art. 22 ust. 6 pkt 1 i nie wymaga dodatkowego znaku.',
            ],
            [
                'ids' => ['1490'],
                'unit' => 'art-22-ust-6-pkt-2',
                'note' => 'To pytanie dotyczy zawracania na autostradzie. Art. 22 ust. 6 pkt 2 zabrania tego manewru niezależnie od natężenia ruchu i widoczności.',
            ],
            [
                'ids' => ['1491'],
                'unit' => 'art-22-ust-6-pkt-3',
                'note' => 'To pytanie dotyczy drogi ekspresowej. Art. 22 ust. 6 pkt 3 dopuszcza zawracanie jedynie na skrzyżowaniu albo w miejscu do tego przeznaczonym.',
            ],
            [
                'ids' => ['1514', '4596'],
                'unit' => 'art-22-ust-6-pkt-4',
                'note' => 'To pytanie wymaga oceny skutków manewru, a nie tylko oznakowania. Zawracanie jest zabronione, jeżeli mogłoby zagrozić bezpieczeństwu albo utrudnić ruch.',
            ],
            [
                'ids' => ['1428', '6019', '6023', '7274', '7279', '8359', '8391', '13085'],
                'unit' => 'par-22-ust-1-2',
                'note' => 'To pytanie sprawdza znaczenie znaku B-21. Znak ten zabrania na najbliższym skrzyżowaniu nie tylko skrętu w lewo, ale również zawracania.',
            ],
            [
                'ids' => ['11152'],
                'unit' => 'par-22-ust-1-2',
                'note' => 'To pytanie rozróżnia znaki B-21 i B-22. B-22 zabrania skrętu w prawo, lecz sam nie ustanawia zakazu zawracania.',
            ],
            [
                'ids' => ['1517', '7277', '8380', '8387', '8388', '8389', '9633', '10189', '10252', '10435', '11076'],
                'unit' => 'par-22-ust-5',
                'note' => 'To pytanie sprawdza zakres znaku B-23. Zakaz zawracania obowiązuje od miejsca ustawienia znaku do najbliższego skrzyżowania włącznie, chyba że wcześniej zakończy go B-24.',
            ],
            [
                'ids' => ['2904', '8439', '8440'],
                'unit' => 'par-87-ust-1',
                'note' => 'To pytanie dotyczy wyboru kierunku z oznaczonego pasa. Strzałki P-8 pokazują kierunki jazdy dozwolone właśnie z tego pasa ruchu.',
            ],
            [
                'ids' => ['610', '1691', '2902', '6054', '7308'],
                'unit' => 'par-87-ust-2',
                'note' => 'To pytanie sprawdza znaczenie strzałki P-8b na skrajnym lewym pasie. Taka strzałka zezwala także na zawracanie, jeżeli nie zabrania tego B-23 i ruchem nie kieruje S-3.',
            ],
            [
                'ids' => ['1474', '6134'],
                'unit' => 'par-87-ust-2',
                'note' => 'To pytanie sprawdza wyjątek od reguły P-8b. Gdy ruchem kieruje sygnalizator S-3, sama strzałka w lewo na jezdni nie daje prawa do zawracania.',
            ],
            [
                'ids' => ['6129', '8648', '8660'],
                'unit' => 'par-97-ust-1',
                'note' => 'To pytanie dotyczy sygnalizatora kierunkowego S-3. Wolno jechać wyłącznie w kierunkach pokazanych strzałkami, więc zawracanie wymaga wskazania tego kierunku.',
            ],
            [
                'ids' => ['3364', '4001'],
                'unit' => 'par-97-ust-3',
                'note' => 'To pytanie sprawdza znaczenie zielonego sygnału kierunkowego S-3. Podczas jazdy we wskazanym kierunku nie występuje kolizja z innymi uczestnikami ruchu.',
            ],
            [
                'ids' => ['3445'],
                'unit' => 'art-25-ust-2',
                'note' => 'To pytanie łączy zawracanie z pierwszeństwem tramwaju. Jeżeli znaki lub sygnały nie rozstrzygają inaczej, kierujący ustępuje pierwszeństwa pojazdowi szynowemu.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachSignalDzwiekowyQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $references = [
            '2187' => [
                'unit' => 'art-29-ust-2-pkt-2',
                'note' => 'To pytanie wprost dotyczy ostrzeżenia kierowcy, który stwarza bezpośrednie zagrożenie na obszarze zabudowanym. Art. 29 ust. 2 pkt 2 dopuszcza wtedy użycie sygnału dźwiękowego.',
            ],
            '1157' => [
                'unit' => 'art-29-ust-2-pkt-2',
                'note' => 'To pytanie sprawdza wyjątek od zakazu używania klaksonu na obszarze zabudowanym. Sygnał nie jest zabroniony, jeżeli jest konieczny z powodu bezpośredniego niebezpieczeństwa.',
            ],
            '3653' => [
                'unit' => 'art-29-ust-1',
                'note' => 'To pytanie pokazuje pojazd rozpoczynający zmianę pasa podczas wyprzedzania. Sygnał może ostrzec jego kierującego o powstającym niebezpieczeństwie, zgodnie z art. 29 ust. 1.',
            ],
            '6217' => [
                'unit' => 'art-29-ust-1',
                'note' => 'To pytanie sprawdza ogólną funkcję sygnału dźwiękowego. Art. 29 ust. 1 pozwala go użyć, gdy jest potrzebny do ostrzeżenia innych przed bezpośrednim niebezpieczeństwem.',
            ],
            '7127' => [
                'unit' => 'art-29-ust-1',
                'note' => 'To pytanie dotyczy ostrzeżenia pieszego zagrożonego wejściem na tor jazdy pojazdu. Art. 29 ust. 1 pozwala użyć sygnału, gdy ma on zapobiec realnemu niebezpieczeństwu.',
            ],
            '7129' => [
                'unit' => 'art-29-ust-1',
                'note' => 'To pytanie sprawdza reakcję na konkretne zagrożenie dla pieszego. Sygnał dźwiękowy jest dozwolony jako ostrzeżenie, ale nie zastępuje zmniejszenia prędkości ani zatrzymania.',
            ],
            '13763' => [
                'unit' => 'art-29-ust-1',
                'note' => 'Zataczający się pieszy idący po jezdni może nagle wejść pod pojazd. Art. 29 ust. 1 pozwala ostrzec go sygnałem dźwiękowym o tym niebezpieczeństwie.',
            ],
            '2212' => [
                'unit' => 'art-29-ust-1',
                'note' => 'W tym pytaniu sygnał ma funkcję ostrzegawczą, a nie porządkową. Art. 29 ust. 1 dopuszcza użycie klaksonu, gdy widoczna sytuacja wymaga ostrzeżenia o niebezpieczeństwie.',
            ],
            '1144' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'To pytanie odróżnia potrzebne ostrzeżenie od automatycznego trąbienia na pieszego. Brak realnego zagrożenia oznacza, że sygnał byłby nadużyciem zabronionym przez art. 29 ust. 2 pkt 1.',
            ],
            '1770' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Sama chęć uprzedzenia rowerzysty o zwykłym wyprzedzaniu nie uzasadnia klaksonu. Bez konkretnego niebezpieczeństwa takie użycie sygnału stanowiłoby jego nadużywanie.',
            ],
            '3363' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Kilkukrotne trąbienie nie może zastąpić obowiązku zatrzymania pojazdu. Art. 29 ust. 2 pkt 1 zabrania nadużywania sygnału dźwiękowego.',
            ],
            '6235' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Przed wyprzedzeniem rowerzysty w normalnych warunkach nie istnieje ogólny obowiązek użycia klaksonu. Sygnał bez potrzeby ostrzeżenia o zagrożeniu byłby nadużyciem.',
            ],
            '7471' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Klakson nie służy do nakłaniania pojazdu z przodu do szybszego opuszczenia skrzyżowania. Takie ponaglanie jest nadużywaniem sygnału dźwiękowego.',
            ],
            '8284' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Nie wolno używać sygnału dźwiękowego tylko po to, aby rowerzysta jechał szybciej. Art. 29 ust. 2 pkt 1 zabrania takiego nadużywania klaksonu.',
            ],
            '9255' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Ponaglanie stojących pojazdów nie jest ostrzeganiem o niebezpieczeństwie. Użycie klaksonu w tym celu narusza zakaz nadużywania sygnału.',
            ],
            '9261' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Sygnału dźwiękowego nie używa się po to, aby zmusić rowerzystę do szybszej jazdy. Taki cel oznacza nadużycie sygnału, a nie ostrzeganie o zagrożeniu.',
            ],
            '10428' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Samo zbliżanie się do przejścia i obecność dzieci nie tworzą ogólnego obowiązku trąbienia. Kierujący powinien przede wszystkim zwolnić i zachować szczególną ostrożność.',
            ],
            '10981' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Przejazd obok autobusu na sąsiednim pasie nie jest sam w sobie powodem do użycia klaksonu. Bez konkretnego zagrożenia sygnał byłby nadużyciem.',
            ],
            '10986' => [
                'unit' => 'art-29-ust-2-pkt-1',
                'note' => 'Motorowerzysta nie może trąbić po to, aby pospieszyć osobę przechodzącą przez jezdnię. Sygnał dźwiękowy nie służy do wymuszania szybszego zachowania pieszego.',
            ],
            '6448' => [
                'unit' => 'par-11-ust-1-pkt-6',
                'note' => 'To pytanie sprawdza techniczne wymagania sygnału w pojeździe samochodowym. § 11 ust. 1 pkt 6 wymaga sygnału o ciągłym i nieprzeraźliwym tonie.',
            ],
        ];

        $payload = [];

        foreach ($references as $externalId => $reference) {
            $payload[$externalId] = [
                'page' => $page,
                'unit' => $units[$reference['unit']],
                'topic' => $topic,
                'note' => $reference['note'],
            ];
        }

        $this->attachQuestionReferences($payload, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachLadunekQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['6695', '7615', '11474'],
                'unit' => 'art-61-ust-1',
                'note' => 'To pytanie sprawdza zakaz przeciążenia pojazdu lub przyczepy. Art. 61 ust. 1 nie pozwala, aby ładunek powodował przekroczenie dopuszczalnej masy całkowitej albo dopuszczalnej ładowności.',
            ],
            [
                'ids' => ['1877', '6580', '6589', '10086', '11475'],
                'unit' => 'art-61-ust-2-pkt-2',
                'note' => 'To pytanie dotyczy rozmieszczenia ładunku w sposób zachowujący stateczność i możliwość kierowania pojazdem. Taki obowiązek wynika wprost z art. 61 ust. 2 pkt 2.',
            ],
            [
                'ids' => ['1881', '1883'],
                'unit' => 'art-61-ust-2-pkt-4',
                'note' => 'Ładunek nie może ograniczać widoczności ani zasłaniać świateł, urządzeń sygnalizacyjnych lub tablic. To pytanie sprawdza właśnie zakaz określony w art. 61 ust. 2 pkt 4.',
            ],
            [
                'ids' => ['1878', '6575', '6579', '10085'],
                'unit' => 'art-61-ust-3',
                'note' => 'To pytanie sprawdza obowiązek zabezpieczenia ładunku przed zmianą położenia podczas jazdy. Art. 61 ust. 3 wymaga takiego mocowania niezależnie od rodzaju pojazdu.',
            ],
            [
                'ids' => ['1880'],
                'unit' => 'art-61-ust-4',
                'note' => 'Pytanie dotyczy samego urządzenia mocującego. Art. 61 ust. 4 wymaga zabezpieczenia go przed rozluźnieniem, swobodnym zwisaniem lub spadnięciem.',
            ],
            [
                'ids' => ['1879', '6694'],
                'unit' => 'art-61-ust-5',
                'note' => 'To pytanie sprawdza szczególną zasadę przewozu materiału sypkiego. Art. 61 ust. 5 wymaga szczelnej skrzyni ładunkowej i odpowiednich zasłon.',
            ],
            [
                'ids' => ['6402'],
                'unit' => 'art-61-ust-6-pkt-2',
                'note' => 'Pytanie wymaga wskazania maksymalnego wystawania ładunku z tyłu. Art. 61 ust. 6 pkt 2 ustanawia co do zasady limit 2 m od tylnej płaszczyzny obrysu.',
            ],
            [
                'ids' => ['7725'],
                'unit' => 'art-61-ust-6-pkt-3',
                'note' => 'Pytanie dotyczy przedniej części ładunku. Art. 61 ust. 6 pkt 3 wyznacza jednocześnie limit 0,5 m od obrysu pojazdu i 1,5 m od siedzenia kierującego.',
            ],
            [
                'ids' => ['7531'],
                'unit' => 'art-61-ust-7',
                'note' => 'To pytanie sprawdza szczególny wyjątek dla drewna długiego na przyczepie kłonicowej. Art. 61 ust. 7 dopuszcza wystawanie z tyłu do 5 m od osi przyczepy.',
            ],
            [
                'ids' => ['11476', '11517', '11518'],
                'unit' => 'art-61-ust-9-pkt-3',
                'note' => 'To pytanie sprawdza oznakowanie ładunku wystającego z tyłu samochodu ciężarowego. Art. 61 ust. 9 pkt 3 dopuszcza pasy białe i czerwone na ładunku, tarczy albo bryle geometrycznej o wymaganej powierzchni.',
            ],
            [
                'ids' => ['7451'],
                'unit' => 'art-61-ust-9-pkt-4',
                'note' => 'To pytanie dotyczy uproszczonego oznaczenia ładunku wystającego z tyłu samochodu osobowego. Art. 61 ust. 9 pkt 4 pozwala zastosować czerwoną chorągiewkę.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachPrzyczepaQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['7452'],
                'unit' => 'art-62-ust-1-pkt-1',
                'note' => 'To pytanie porównuje rzeczywiste masy samochodu osobowego i przyczepy. Art. 62 ust. 1 pkt 1 nie pozwala, aby przyczepa była rzeczywiście cięższa od ciągnącego ją samochodu.',
            ],
            [
                'ids' => ['7511'],
                'unit' => 'art-62-ust-1-pkt-3',
                'note' => 'To pytanie dotyczy przyczepy ciągniętej przez motocykl. Jej rzeczywista masa nie może przekraczać masy własnej motocykla, a jednocześnie nie może być większa niż 100 kg.',
            ],
            [
                'ids' => ['6571', '6588'],
                'unit' => 'art-62-ust-4a-pkt-1',
                'note' => 'To pytanie sprawdza długość zespołu z motocyklem albo motorowerem i przyczepą. Art. 62 ust. 4a pkt 1 ustanawia limit 4 m.',
            ],
            [
                'ids' => ['6403', '6857', '10084'],
                'unit' => 'art-62-ust-4a-pkt-2',
                'note' => 'To pytanie dotyczy maksymalnej długości zespołu dwóch pojazdów innego niż motocykl lub motorower z przyczepą. Art. 62 ust. 4a pkt 2 wyznacza limit 18,75 m.',
            ],
            [
                'ids' => ['6663'],
                'unit' => 'par-12-ust-1-pkt-11',
                'note' => 'To pytanie sprawdza obowiązkowe wyposażenie przyczepy ciągnika rolniczego. § 12 ust. 1 pkt 11 wymaga bocznych świateł odblaskowych barwy żółtej samochodowej.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachPrzejazdKolejowyQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['8328', '13638'],
                'unit' => 'art-28-ust-1',
                'note' => 'To pytanie sprawdza obowiązek upewnienia się przed wjazdem, czy nie zbliża się pojazd szynowy. Art. 28 ust. 1 wymaga szczególnej ostrożności zarówno podczas dojazdu, jak i przejazdu przez tory.',
            ],
            [
                'ids' => ['2472', '6072', '6078', '6278', '6280', '6283', '6286', '8305', '8309', '8314', '10242', '13464', '13466', '13635'],
                'unit' => 'art-28-ust-3-pkt-1',
                'note' => 'To pytanie dotyczy zapór lub półzapór. Art. 28 ust. 3 pkt 1 zabrania ich objeżdżania oraz wjazdu od rozpoczęcia opuszczania aż do całkowitego zakończenia podnoszenia.',
            ],
            [
                'ids' => ['13637'],
                'unit' => 'art-28-ust-3-pkt-2',
                'note' => 'To pytanie pokazuje brak miejsca za przejazdem. Art. 28 ust. 3 pkt 2 zabrania wjazdu, jeżeli kierujący nie będzie mógł całkowicie opuścić torowiska.',
            ],
            [
                'ids' => ['13469'],
                'unit' => 'art-28-ust-3-pkt-3',
                'note' => 'To pytanie sprawdza zakaz wyprzedzania na przejeździe kolejowym i bezpośrednio przed nim. Zakaz działa niezależnie od pośpiechu i aktualnej widoczności pociągu.',
            ],
            [
                'ids' => ['4258', '8304', '8386', '11265'],
                'unit' => 'par-21-ust-1-4',
                'note' => 'Na przejeździe znajduje się znak B-20 STOP. § 21 wymaga pełnego zatrzymania pojazdu w wyznaczonym miejscu albo tam, skąd można bezpiecznie ocenić sytuację.',
            ],
            [
                'ids' => ['2443'],
                'unit' => 'par-98-ust-5',
                'note' => 'Mimo podniesionych półzapór nadal działa czerwony sygnał migający. § 98 ust. 5 zakazuje wtedy wjazdu na przejazd aż do wyłączenia sygnału.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachHolowanieQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['13088'],
                'unit' => 'art-31-ust-1-pkt-1',
                'note' => 'To pytanie sprawdza maksymalną prędkość podczas holowania. Art. 31 ust. 1 pkt 1 pozwala jechać najwyżej 30 km/h w obszarze zabudowanym i 60 km/h poza nim.',
            ],
            [
                'ids' => ['7171'],
                'unit' => 'art-31-ust-1-pkt-3',
                'note' => 'To pytanie dotyczy osoby kierującej holowanym motocyklem. Art. 31 ust. 1 pkt 3 wymaga posiadania uprawnienia do kierowania tym pojazdem, jeżeli sposób holowania nie wyklucza potrzeby kierowania.',
            ],
            [
                'ids' => ['6581', '6582', '6657', '7510'],
                'unit' => 'art-31-ust-1-pkt-4',
                'note' => 'To pytanie sprawdza szczególną zasadę holowania motocykla. Art. 31 ust. 1 pkt 4 wymaga połączenia giętkiego umożliwiającego łatwe odczepienie.',
            ],
            [
                'ids' => ['7512'],
                'unit' => 'art-31-ust-1-pkt-5',
                'note' => 'To pytanie dotyczy oznakowania pojazdu holowanego. Art. 31 ust. 1 pkt 5 wymaga umieszczenia trójkąta ostrzegawczego z tyłu po lewej stronie.',
            ],
            [
                'ids' => ['10109'],
                'unit' => 'art-31-ust-1-pkt-6',
                'note' => 'Holowany motocykl korzysta z połączenia giętkiego. Art. 31 ust. 1 pkt 6 wymaga wtedy sprawności dwóch układów hamulcowych w pojeździe holowanym.',
            ],
            [
                'ids' => ['10081'],
                'unit' => 'art-31-ust-2-pkt-1',
                'note' => 'To pytanie dotyczy niesprawnego układu kierowniczego. Art. 31 ust. 2 pkt 1 zakazuje takiego holowania, chyba że zastosowana metoda całkowicie wyklucza potrzebę używania tego układu.',
            ],
            [
                'ids' => ['7539', '7540'],
                'unit' => 'art-31-ust-2-pkt-3',
                'note' => 'To pytanie sprawdza zakaz holowania więcej niż jednego pojazdu. Pojazd z przyczepą tworzy zespół, którego nie wolno w ten sposób holować, poza wyjątkiem dla pojazdu członowego.',
            ],
            [
                'ids' => ['7454', '10080'],
                'unit' => 'art-31-ust-2-pkt-5',
                'note' => 'To pytanie dotyczy autostrady. Art. 31 ust. 2 pkt 5 dopuszcza tam holowanie wyłącznie przez pojazd przeznaczony do holowania i tylko do najbliższego wyjazdu lub miejsca obsługi podróżnych.',
            ],
            [
                'ids' => ['11506'],
                'unit' => 'art-45-ust-1-pkt-2',
                'note' => 'To pytanie dotyczy osoby kierującej pojazdem holowanym po użyciu alkoholu. Art. 45 ust. 1 pkt 2 wprost zabrania holowania w takiej sytuacji.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachPasyQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['4483', '6344', '6388', '6389', '6393', '6907'],
                'unit' => 'art-39-ust-1',
                'note' => 'To pytanie sprawdza podstawowy obowiązek korzystania z pasów bezpieczeństwa. Art. 39 ust. 1 obejmuje kierującego oraz osoby przewożone pojazdem lub na siedzeniu wyposażonym w pasy.',
            ],
            [
                'ids' => ['7441'],
                'unit' => 'art-39-ust-2-pkt-2',
                'note' => 'To pytanie dotyczy ustawowego wyjątku dla kobiety o widocznej ciąży. Zwolnienie wynika wprost z art. 39 ust. 2 pkt 2.',
            ],
            [
                'ids' => ['10811'],
                'unit' => 'art-39-ust-2-pkt-10',
                'note' => 'To pytanie sprawdza wyjątek dotyczący dziecka poniżej 3 lat przewożonego autobusem. Art. 39 ust. 2 pkt 10 wyłącza wtedy obowiązek korzystania z pasów.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachSwiatlaDzienneQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['1319', '3544', '6226', '7470', '7884'],
                'unit' => 'art-51-ust-1',
                'note' => 'To pytanie sprawdza całoroczny obowiązek używania świateł podczas jazdy. Art. 51 ust. 1 ustanawia światła mijania jako podstawową regułę w warunkach normalnej przejrzystości.',
            ],
            [
                'ids' => ['2065', '4396', '6211', '10976'],
                'unit' => 'art-51-ust-2',
                'note' => 'To pytanie sprawdza warunki zastąpienia świateł mijania światłami do jazdy dziennej. Art. 51 ust. 2 pozwala na to tylko od świtu do zmierzchu i przy normalnej przejrzystości powietrza.',
            ],
            [
                'ids' => ['6224', '10979'],
                'unit' => 'art-30-ust-1-pkt-1-lit-a',
                'note' => 'W tej sytuacji przejrzystość powietrza jest zmniejszona. Art. 30 ust. 1 pkt 1 lit. a wymaga wtedy świateł mijania albo przednich przeciwmgłowych, więc same światła dzienne nie wystarczają.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachRogatkiQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['2436', '2467', '13473', '13618'],
                'unit' => 'art-28-ust-1',
                'note' => 'To pytanie sprawdza obowiązek obserwacji torów mimo podniesionych zapór. Art. 28 ust. 1 wymaga szczególnej ostrożności i upewnienia się, czy nie zbliża się pojazd szynowy.',
            ],
            [
                'ids' => ['2458', '2472', '6283', '8309', '8316', '10242', '13464', '13466'],
                'unit' => 'art-28-ust-3-pkt-1',
                'note' => 'To pytanie dotyczy położenia lub ruchu zapór. Art. 28 ust. 3 pkt 1 zakazuje ich objeżdżania oraz wjazdu od rozpoczęcia opuszczania aż do całkowitego zakończenia podnoszenia.',
            ],
            [
                'ids' => ['2443'],
                'unit' => 'par-98-ust-5',
                'note' => 'Zapory są już podniesione, ale czerwony sygnał migający nadal działa. § 98 ust. 5 samodzielnie zakazuje wtedy wjazdu na przejazd.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachSwiatlaPrzeciwmgloweQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['6216', '7464'],
                'unit' => 'art-30-ust-1-pkt-1-lit-a',
                'note' => 'To pytanie dotyczy przednich świateł przeciwmgłowych przy zmniejszonej przejrzystości. Art. 30 ust. 1 pkt 1 lit. a pozwala używać ich zamiast świateł mijania albo razem z nimi.',
            ],
            [
                'ids' => ['6363', '7675', '8238', '8245', '11457', '13105', '13444'],
                'unit' => 'art-30-ust-3',
                'note' => 'To pytanie sprawdza próg dla tylnych świateł przeciwmgłowych. Art. 30 ust. 3 pozwala ich używać przy widoczności mniejszej niż 50 m i wymaga niezwłocznego wyłączenia po poprawie warunków.',
            ],
            [
                'ids' => ['2072'],
                'unit' => 'art-51-ust-5',
                'note' => 'To pytanie dotyczy nocnej jazdy po oznakowanej drodze krętej. Art. 51 ust. 5 pozwala wtedy używać przednich świateł przeciwmgłowych również przy normalnej przejrzystości powietrza.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachFotelikiQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['10812', '10869', '10876'],
                'unit' => 'art-39-ust-3',
                'note' => 'To pytanie sprawdza podstawową regułę przewozu dziecka poniżej 150 cm. Art. 39 ust. 3 wymaga urządzenia przytrzymującego dobranego do masy i wzrostu oraz zgodnego z wymaganiami technicznymi.',
            ],
            [
                'ids' => ['10815', '10871'],
                'unit' => 'art-39-ust-3a',
                'note' => 'To pytanie dotyczy montażu fotelika lub innego urządzenia. Art. 39 ust. 3a wymaga instalacji zgodnej z zaleceniami producenta urządzenia.',
            ],
            [
                'ids' => ['10813', '10882'],
                'unit' => 'art-39-ust-3c',
                'note' => 'To pytanie sprawdza wyjątek dla trzeciego dziecka na tylnym siedzeniu. Art. 39 ust. 3c wymaga wieku co najmniej 3 lat, dwóch już zamontowanych urządzeń, braku możliwości montażu trzeciego i zapięcia pasa.',
            ],
            [
                'ids' => ['13382'],
                'unit' => 'art-39-ust-4-pkt-4',
                'note' => 'To pytanie dotyczy wyjątku opartego na zaświadczeniu lekarskim. Art. 39 ust. 4 pkt 4 wyłącza obowiązek urządzenia przytrzymującego przy przeciwwskazaniu potwierdzonym takim dokumentem.',
            ],
            [
                'ids' => ['10806', '10807'],
                'unit' => 'art-45-ust-2-pkt-4',
                'note' => 'To pytanie dotyczy fotelika ustawionego tyłem na przednim siedzeniu. Art. 45 ust. 2 pkt 4 zakazuje takiego przewozu przy aktywnej poduszce powietrznej pasażera.',
            ],
            [
                'ids' => ['10809'],
                'unit' => 'art-45-ust-2-pkt-5',
                'note' => 'To pytanie sprawdza zakaz przewozu dziecka poniżej 3 lat w pojeździe bez pasów bezpieczeństwa i odpowiedniego urządzenia przytrzymującego.',
            ],
            [
                'ids' => ['10810', '10840', '10969'],
                'unit' => 'art-45-ust-2-pkt-6',
                'note' => 'To pytanie dotyczy przedniego siedzenia. Art. 45 ust. 2 pkt 6 zabrania przewożenia na nim dziecka poniżej 150 cm poza fotelikiem lub innym urządzeniem przytrzymującym.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachWypadekQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['2621'],
                'unit' => 'art-44-ust-1-pkt-2',
                'note' => 'To pytanie sprawdza zabezpieczenie miejsca zdarzenia. Art. 44 ust. 1 pkt 2 wymaga podjęcia odpowiednich działań dla zapewnienia bezpieczeństwa ruchu.',
            ],
            [
                'ids' => ['3658', '6298', '6404', '11250'],
                'unit' => 'art-44-ust-2-pkt-1',
                'note' => 'W zdarzeniu są osoby ranne. Art. 44 ust. 2 pkt 1 wymaga udzielenia niezbędnej pomocy oraz wezwania zespołu ratownictwa medycznego i Policji.',
            ],
            [
                'ids' => ['6296'],
                'unit' => 'art-44-ust-2-pkt-1',
                'note' => 'To pytanie sprawdza granicę obowiązku wezwania Policji. Szczególny obowiązek z art. 44 ust. 2 pkt 1 powstaje, gdy w wypadku jest osoba zabita lub ranna, a opis wyklucza takie ofiary.',
            ],
            [
                'ids' => ['6294', '6295'],
                'unit' => 'art-44-ust-2-pkt-2',
                'note' => 'W zdarzeniu są osoby ranne. Art. 44 ust. 2 pkt 2 zabrania działań, które mogłyby utrudnić ustalenie przebiegu wypadku, dlatego pojazdu nie usuwa się wyłącznie dla udrożnienia ruchu.',
            ],
            [
                'ids' => ['6292'],
                'unit' => 'art-44-ust-2-pkt-3',
                'note' => 'To pytanie dotyczy pozostania na miejscu. Art. 44 ust. 2 pkt 3 pozwala oddalić się tylko w celu wezwania służb i wymaga niezwłocznego powrotu.',
            ],
            [
                'ids' => ['6297'],
                'unit' => 'art-44-ust-1-pkt-3',
                'note' => 'W zdarzeniu nie ma zabitych ani rannych. Art. 44 ust. 1 pkt 3 nakazuje niezwłocznie usunąć pojazd, jeżeli powodowałby zagrożenie lub tamował ruch.',
            ],
            [
                'ids' => ['6299'],
                'unit' => 'art-44-ust-1-pkt-4',
                'note' => 'To pytanie sprawdza wymianę danych. Art. 44 ust. 1 pkt 4 wymaga podania na żądanie uczestnika danych swoich, właściciela lub posiadacza pojazdu oraz ubezpieczenia OC.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachTelefonQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $references = [];

        foreach (['3991', '3992', '4006', '4027', '6390', '13535'] as $externalId) {
            $references[$externalId] = [
                'page' => $page,
                'unit' => $units['art-45-ust-2-pkt-1'],
                'topic' => $topic,
                'note' => 'To pytanie sprawdza, czy korzystanie z telefonu wymaga trzymania słuchawki lub mikrofonu w ręku. Art. 45 ust. 2 pkt 1 zabrania właśnie takiego sposobu używania urządzenia podczas kierowania.',
            ];
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachSwiatlaDrogoweQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['7414', '7416', '7469', '10406', '10779', '10980', '11027'],
                'unit' => 'art-51-ust-3',
                'note' => 'To pytanie sprawdza podstawowe warunki używania świateł drogowych: porę od zmierzchu do świtu, nieoświetloną drogę oraz brak ryzyka oślepienia innych uczestników.',
            ],
            [
                'ids' => ['6213', '6223'],
                'unit' => 'art-51-ust-4-pkt-1',
                'note' => 'W tej sytuacji z przeciwka zbliża się pojazd. Art. 51 ust. 4 pkt 1 wymaga przełączenia świateł drogowych na mijania, zanim jego kierujący zostanie oślepiony.',
            ],
            [
                'ids' => ['6222', '6225'],
                'unit' => 'art-51-ust-4-pkt-2',
                'note' => 'Pojazd po wyprzedzeniu znajduje się przed nami i jego kierujący może zostać oślepiony przez lusterka. Art. 51 ust. 4 pkt 2 wymaga wtedy zmiany świateł drogowych na mijania.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachWyposazenieQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $references = [
            '1905' => [
                'unit' => 'par-11-ust-1-pkt-14',
                'note' => 'To pytanie sprawdza sposób przewożenia gaśnicy. § 11 ust. 1 pkt 14 wymaga miejsca łatwo dostępnego, a prawidłowe zamocowanie chroni przed jej przemieszczaniem podczas jazdy.',
            ],
            '6499' => [
                'unit' => 'par-11-ust-1-pkt-6',
                'note' => 'To pytanie dotyczy obowiązkowego wyposażenia motocykla. Motocykl jako pojazd samochodowy musi mieć sygnał dźwiękowy spełniający wymagania § 11 ust. 1 pkt 6.',
            ],
            '6500' => [
                'unit' => 'par-11-ust-1-pkt-5c',
                'note' => 'To pytanie sprawdza wyposażenie szybkiego motocykla jednośladowego. § 11 ust. 1 pkt 5c wymaga powyżej 100 km/h co najmniej dwóch lusterek, po jednym z każdej strony.',
            ],
            '6501' => [
                'unit' => 'par-12-ust-3-pkt-11',
                'note' => 'To pytanie odróżnia wyposażenie dopuszczalne od obowiązkowego. § 12 ust. 3 pkt 11 pozwala wyposażyć motocykl w światła awaryjne, lecz nie wymaga ich w każdym motocyklu.',
            ],
            '7177' => [
                'unit' => 'par-46-ust-1-pkt-2',
                'note' => 'To pytanie dotyczy ciągnika rolniczego. § 46 ust. 1 pkt 2 odsyła między innymi do obowiązku posiadania łatwo dostępnej gaśnicy z § 11 ust. 1 pkt 14.',
            ],
            '11442' => [
                'unit' => 'par-40-ust-2-pkt-8',
                'note' => 'Samochód ciężarowy przewożący osoby poza kabiną wymaga dwóch gaśnic. § 40 ust. 2 pkt 8 wskazuje jedną gaśnicę przy kabinie i drugą wewnątrz przestrzeni przewozowej.',
            ],
        ];

        $payload = [];

        foreach ($references as $externalId => $reference) {
            $payload[$externalId] = [
                'page' => $page,
                'unit' => $units[$reference['unit']],
                'topic' => $topic,
                'note' => $reference['note'],
            ];
        }

        $this->attachQuestionReferences($payload, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachOponyQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['3784', '6438', '11492'],
                'unit' => 'par-11-ust-7-pkt-4',
                'note' => 'To pytanie dotyczy zużycia bieżnika. § 11 ust. 7 pkt 4 zakazuje jazdy po osiągnięciu wskaźnika granicznego, a bez wskaźnika wymaga co najmniej 1,6 mm głębokości rzeźby.',
            ],
            [
                'ids' => ['6626', '6629', '7553'],
                'unit' => 'par-11-ust-5',
                'note' => 'To pytanie sprawdza znaczenie prawidłowego ciśnienia w ogumieniu. § 11 ust. 5 wymaga wartości zalecanej przez producenta dla danego obciążenia; odchylenie pogarsza pracę opony.',
            ],
            [
                'ids' => ['7603'],
                'unit' => 'par-11-ust-7-pkt-1',
                'note' => 'To pytanie dotyczy opon na jednej osi przyczepy rolniczej. § 11 ust. 7 pkt 1 zabrania stosowania na tej samej osi opon o różnej konstrukcji, w tym rzeźbie bieżnika.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachDokumentyQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['10834', '10838', '10892', '10895'],
                'unit' => 'art-38-ust-1-pkt-1',
                'note' => 'To pytanie rozróżnia krajowe prawo jazdy weryfikowane elektronicznie od innego dokumentu stwierdzającego uprawnienie. Art. 38 ust. 1 pkt 1 wymaga okazania takiego innego dokumentu.',
            ],
            [
                'ids' => ['10841', '11001'],
                'unit' => 'art-38-ust-1-pkt-3a',
                'note' => 'Pojazd jest wyposażony w blokadę alkoholową. Art. 38 ust. 1 pkt 3a wymaga okazania zaświadczenia o badaniu technicznym potwierdzającym jej prawidłowe działanie.',
            ],
            [
                'ids' => ['10843'],
                'unit' => 'art-38-ust-1-pkt-4a',
                'note' => 'To pytanie dotyczy dokumentacji blokady alkoholowej. Art. 38 ust. 1 pkt 4a wymaga okazania dokumentu potwierdzającego jej kalibrację.',
            ],
            [
                'ids' => ['10835', '10893', '10894'],
                'unit' => 'art-38-ust-1-pkt-4b-lit-a',
                'note' => 'Prawo jazdy lub pozwolenie zostało zatrzymane. Art. 38 ust. 1 pkt 4b lit. a wymaga okazania ważnego pokwitowania, które czasowo zastępuje zatrzymany dokument.',
            ],
            [
                'ids' => ['10827', '10885', '10886', '10887', '10888', '10999', '11041'],
                'unit' => 'art-38-ust-1-pkt-4b-lit-b',
                'note' => 'To pytanie dotyczy zatrzymanego dowodu rejestracyjnego albo pozwolenia czasowego. Art. 38 ust. 1 pkt 4b lit. b wymaga okazania pokwitowania, jeżeli nadal uprawnia ono do używania pojazdu lub przyczepy.',
            ],
            [
                'ids' => ['10836'],
                'unit' => 'art-38-ust-1-pkt-5',
                'note' => 'To pytanie dotyczy dokumentu wymaganego na podstawie przepisów szczególnych. Art. 38 ust. 1 pkt 5 obejmuje dokumenty, których obowiązek okazania wynika z odrębnej ustawy.',
            ],
            [
                'ids' => ['10889', '10890', '10891'],
                'unit' => 'art-38-ust-2',
                'note' => 'Pojazd jest zarejestrowany za granicą. Art. 38 ust. 2 wymaga dokumentu dopuszczającego go do ruchu oraz dokumentu OC albo dowodu opłacenia składki.',
            ],
            [
                'ids' => ['10839'],
                'unit' => 'art-38-ust-3',
                'note' => 'To pytanie dotyczy jazdy testowej. Art. 38 ust. 3 wymaga wtedy okazania dokumentu stwierdzającego dopuszczenie pojazdu do ruchu.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachKategoriePrawaJazdyQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['6401'],
                'unit' => 'ukp-art-6-ust-1-pkt-6-lit-a-b',
                'note' => 'To pytanie sprawdza podstawowy zakres kategorii B. Art. 6 ust. 1 pkt 6 lit. a-b obejmuje pojazd samochodowy do 3,5 t, z wyjątkiem autobusu i motocykla, także z przyczepą lekką.',
            ],
            [
                'ids' => ['6414', '6422'],
                'unit' => 'ukp-art-13-ust-1',
                'note' => 'To pytanie dotyczy terminu ważności prawa jazdy. Art. 13 ust. 1 określa okres, na jaki wydaje się dokument odpowiedniej kategorii.',
            ],
            [
                'ids' => ['6430'],
                'unit' => 'ukp-art-6-ust-1-pkt-5-lit-a',
                'note' => 'To pytanie dotyczy kategorii B1. Art. 6 ust. 1 pkt 5 lit. a uprawnia jej posiadacza do kierowania czterokołowcem.',
            ],
            [
                'ids' => ['6431'],
                'unit' => 'ukp-art-6-ust-1-pkt-5-lit-b',
                'note' => 'To pytanie sprawdza dodatkowy zakres B1. Art. 6 ust. 1 pkt 5 lit. b obejmuje pojazdy określone dla kategorii AM.',
            ],
            [
                'ids' => ['6599', '7560'],
                'unit' => 'ukp-art-6-ust-1-pkt-4-lit-a',
                'note' => 'To pytanie dotyczy kategorii A. Art. 6 ust. 1 pkt 4 lit. a uprawnia do kierowania motocyklem.',
            ],
            [
                'ids' => ['6604', '11060'],
                'unit' => 'ukp-art-6-ust-1-pkt-2-lit-b',
                'note' => 'To pytanie sprawdza limity kategorii A1: do 125 cm3, 11 kW i 0,1 kW/kg. Wszystkie trzy warunki z art. 6 ust. 1 pkt 2 lit. b muszą być spełnione łącznie.',
            ],
            [
                'ids' => ['6606'],
                'unit' => 'ukp-art-6-ust-1-pkt-2-lit-c',
                'note' => 'To pytanie dotyczy motocykla trójkołowego. Art. 6 ust. 1 pkt 2 lit. c ogranicza zakres kategorii A1 do mocy nieprzekraczającej 15 kW.',
            ],
            [
                'ids' => ['6608'],
                'unit' => 'ukp-art-6-ust-1-pkt-3-lit-b',
                'note' => 'To pytanie dotyczy motocykla trójkołowego w kategorii A2. Art. 6 ust. 1 pkt 3 lit. b wskazuje limit mocy 15 kW.',
            ],
            [
                'ids' => ['6610'],
                'unit' => 'ukp-art-6-ust-1-pkt-3-lit-c',
                'note' => 'To pytanie sprawdza, że kategoria A2 obejmuje także pojazdy kategorii AM, zgodnie z art. 6 ust. 1 pkt 3 lit. c.',
            ],
            [
                'ids' => ['6613', '6618'],
                'unit' => 'ukp-art-6-ust-1-pkt-1-lit-b',
                'note' => 'To pytanie dotyczy czterokołowca lekkiego. Art. 6 ust. 1 pkt 1 lit. b włącza taki pojazd do zakresu kategorii AM.',
            ],
            [
                'ids' => ['6617', '7596'],
                'unit' => 'ukp-art-6-ust-1-pkt-1-lit-a',
                'note' => 'To pytanie dotyczy motoroweru. Art. 6 ust. 1 pkt 1 lit. a włącza go do zakresu kategorii AM.',
            ],
            [
                'ids' => ['6708', '7625'],
                'unit' => 'ukp-art-6-ust-1-pkt-11-lit-a',
                'note' => 'To pytanie sprawdza podstawowy zakres kategorii T. Art. 6 ust. 1 pkt 11 lit. a obejmuje ciągnik rolniczy i pojazd wolnobieżny.',
            ],
            [
                'ids' => ['6709', '7626'],
                'unit' => 'ukp-art-6-ust-1-pkt-11-lit-b',
                'note' => 'To pytanie dotyczy zespołu pojazdów. Art. 6 ust. 1 pkt 11 lit. b pozwala posiadaczowi kategorii T kierować ciągnikiem lub pojazdem wolnobieżnym z przyczepą albo przyczepami.',
            ],
            [
                'ids' => ['10090'],
                'unit' => 'ukp-art-6-ust-3-pkt-1',
                'note' => 'To pytanie sprawdza krajowe rozszerzenie kategorii B. Art. 6 ust. 3 pkt 1 pozwala w Polsce kierować ciągnikiem rolniczym lub pojazdem wolnobieżnym także z przyczepą lekką.',
            ],
            [
                'ids' => ['10091'],
                'unit' => 'ukp-art-6-ust-3-pkt-4-lit-a',
                'note' => 'To pytanie dotyczy motocykla 125 cm3. Art. 6 ust. 3 pkt 4 lit. a wymaga kategorii B posiadanej co najmniej 3 lata i działa na terytorium Polski.',
            ],
            [
                'ids' => ['11070'],
                'unit' => 'ukp-art-18-ust-1',
                'note' => 'To pytanie dotyczy utraty, nieczytelnego zniszczenia albo zmiany danych w prawie jazdy. Art. 18 ust. 1 wymaga zawiadomienia starosty w terminie 30 dni.',
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['note'],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachAlcoholQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $groups = [
            [
                'ids' => ['2759', '4025', '11417'],
                'unit' => 'art-45-ust-1-pkt-1',
                'notes' => [
                    '2759' => 'To pytanie dotyczy kierowania tramwajem po środku działającym podobnie do alkoholu. Art. 45 ust. 1 pkt 1 obejmuje taki środek tym samym zakazem kierowania pojazdem.',
                    '4025' => 'To pytanie sprawdza zakaz kierowania tramwajem w stanie po użyciu alkoholu. Art. 45 ust. 1 pkt 1 zabrania kierowania pojazdem już w tym stanie, nie dopiero po osiągnięciu stanu nietrzeźwości.',
                    '11417' => 'To pytanie dotyczy kierowania motorowerem po alkoholu lub środku działającym podobnie. Motorower jest pojazdem, dlatego zakaz z art. 45 ust. 1 pkt 1 ma zastosowanie również do jego kierującego.',
                ],
            ],
            [
                'ids' => ['2756', '8416', '8417'],
                'unit' => 'sobriety-art-46-ust-2',
                'notes' => [
                    '2756' => 'To pytanie sprawdza, czy motorniczego obowiązują te same ustawowe progi alkoholu. Art. 46 ust. 2 definiuje stan po użyciu alkoholu niezależnie od rodzaju pojazdu, a Prawo o ruchu drogowym obejmuje zakazem także kierowanie tramwajem.',
                    '8416' => 'To pytanie używa progu 0,1 mg alkoholu w 1 dm3 wydychanego powietrza. Art. 46 ust. 2 kwalifikuje wartości od tego poziomu jako stan po użyciu alkoholu, który wyklucza kierowanie.',
                    '8417' => 'To pytanie używa progu 0,2‰ alkoholu we krwi. Art. 46 ust. 2 kwalifikuje wartości od tego poziomu jako stan po użyciu alkoholu, więc kierowanie nie jest dozwolone.',
                ],
            ],
            [
                'ids' => ['8419'],
                'unit' => 'kw-art-87-par-1',
                'notes' => [
                    '8419' => 'To pytanie sprawdza podstawową kwalifikację prowadzenia pojazdu mechanicznego w stanie po użyciu alkoholu. Art. 87 § 1 Kodeksu wykroczeń określa takie zachowanie jako wykroczenie.',
                ],
            ],
            [
                'ids' => ['11506'],
                'unit' => 'art-45-ust-1-pkt-2',
                'notes' => [
                    '11506' => 'To pytanie dotyczy osoby kierującej pojazdem holowanym po użyciu alkoholu. Art. 45 ust. 1 pkt 2 zabrania holowania w takiej sytuacji, także gdy kierujący pojazdem holującym jest trzeźwy.',
                ],
            ],
        ];

        $references = [];

        foreach ($groups as $group) {
            foreach ($group['ids'] as $externalId) {
                $references[$externalId] = [
                    'page' => $page,
                    'unit' => $units[$group['unit']],
                    'topic' => $topic,
                    'note' => $group['notes'][$externalId],
                ];
            }
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachGreenArrowQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $references = [];

        foreach (['475', '7280', '7329', '10454'] as $externalId) {
            $references[$externalId] = [
                'page' => $page,
                'unit' => $units['par-96-ust-3'],
                'topic' => $topic,
                'note' => 'To pytanie sprawdza możliwość skrętu na zielonej strzałce bez zatrzymania. § 96 ust. 3 wymaga najpierw całkowitego zatrzymania przed sygnalizatorem, a następnie wykonania manewru bez utrudnienia ruchu innym uczestnikom.',
            ];
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachEmergencyVehicleQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $references = [
            '7012' => [
                'page' => $page,
                'unit' => $units['art-24-ust-11'],
                'topic' => $topic,
                'note' => 'To pytanie sprawdza szczególny zakaz dotyczący obszaru zabudowanego. Art. 24 ust. 11 zabrania na nim wyprzedzania pojazdu uprzywilejowanego.',
            ],
            '7153' => [
                'page' => $page,
                'unit' => $units['par-58-ust-3'],
                'topic' => $topic,
                'note' => 'W tym pytaniu znak D-43 oznacza wyjazd z obszaru zabudowanego. Po jego minięciu nie działa już szczególny zakaz wyprzedzania pojazdu uprzywilejowanego ograniczony w art. 24 ust. 11 do obszaru zabudowanego.',
            ],
        ];

        foreach (['10698', '10700', '10720', '10730'] as $externalId) {
            $references[$externalId] = [
                'page' => $page,
                'unit' => $units['art-9-ust-2'],
                'topic' => $topic,
                'note' => 'To pytanie wymaga oceny sposobu utworzenia korytarza życia. Art. 9 ust. 2 nakazuje pozostawić drogę przejazdu między skrajnym lewym pasem a wszystkimi pozostałymi pasami ruchu.',
            ];
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
        $this->attachQuestionReferences([
            '7153' => [
                'page' => $page,
                'unit' => $units['art-24-ust-11'],
                'topic' => $topic,
                'note' => 'Art. 24 ust. 11 zabrania wyprzedzania pojazdu uprzywilejowanego wyłącznie na obszarze zabudowanym. Znak D-43 widoczny w pytaniu kończy ten obszar.',
            ],
        ], $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, LegalUnit>  $units
     */
    private function attachYellowSignalQuestionReferences(
        LegalContentPage $page,
        LegalTopic $topic,
        array $units,
        ContentAuthor $reviewer,
        Carbon $reviewedAt,
    ): void {
        $notes = [
            '7779' => 'To pytanie sprawdza, czy żółty sygnał migający sam nakazuje zatrzymanie. § 98 ust. 6 ma charakter ostrzegawczy: wymaga szczególnej ostrożności, ale nie wprowadza bezwzględnego zakazu wjazdu.',
            '10411' => 'To pytanie dotyczy możliwości wjazdu przy żółtym sygnale migającym. § 98 ust. 6 ostrzega o niebezpieczeństwie lub utrudnieniu, a dalszy przejazd zależy od znaków, pierwszeństwa i sytuacji na drodze.',
            '10459' => 'To pytanie odróżnia żółty migający od jednoczesnego czerwonego i żółtego. § 98 ust. 6 nie zapowiada sygnału zielonego, lecz ostrzega o niebezpieczeństwie lub utrudnieniu.',
            '10460' => 'To pytanie odróżnia żółty migający od stałego żółtego. § 98 ust. 6 nie zapowiada sygnału czerwonego, lecz nakazuje zachować szczególną ostrożność.',
        ];
        $references = [];

        foreach ($notes as $externalId => $note) {
            $references[$externalId] = [
                'page' => $page,
                'unit' => $units['par-98-ust-6'],
                'topic' => $topic,
                'note' => $note,
            ];
        }

        $this->attachQuestionReferences($references, $reviewer, $reviewedAt);
    }

    /**
     * @param  array<string, array{page: LegalContentPage, unit: LegalUnit, topic: LegalTopic, note: string}>  $references
     */
    private function attachQuestionReferences(array $references, ContentAuthor $reviewer, Carbon $reviewedAt): void
    {
        foreach ($references as $externalId => $reference) {
            $questions = Question::query()
                ->whereIn('external_id', [$externalId, 'pj360:'.$externalId])
                ->where('is_active', true)
                ->get();

            foreach ($questions as $question) {
                $questionReference = QuestionLegalReference::query()->firstOrNew([
                    'question_id' => $question->getKey(),
                    'legal_unit_id' => $reference['unit']->getKey(),
                    'legal_topic_id' => $reference['topic']->getKey(),
                ]);

                if (
                    $questionReference->exists
                    && $questionReference->assignment_source === QuestionLegalReference::SOURCE_MANUAL
                ) {
                    continue;
                }

                $questionReference->fill([
                    'legal_content_page_id' => $reference['page']->getKey(),
                    'relation_type' => 'direct_basis',
                    'public_note' => $reference['note'],
                    'internal_note' => 'MVP Legal Trust Layer: relacja przypisana po external_id i zweryfikowana ręcznie na poziomie tematu.',
                    'assignment_source' => QuestionLegalReference::SOURCE_SEED,
                    'confidence' => 85,
                    'status' => QuestionLegalReference::STATUS_VERIFIED,
                    'verified_by' => $reviewer->getKey(),
                    'verified_at' => $reviewedAt,
                ])->save();
            }
        }
    }
}
