<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;

test('question topic classification command assigns thematic topics to existing questions', function () {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $warningQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-TOPIC-001',
            'prompt' => 'Czy ten znak ostrzega o niebezpiecznym zakrecie w prawo?',
            'metadata' => [
                'main_media_original' => 'A-2_org.jpg',
            ],
        ]);

    $prohibitionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-TOPIC-002',
            'prompt' => 'Czy ten znak zakazu zabrania zawracania?',
            'metadata' => [
                'main_media_original' => 'B-23_org.jpg',
            ],
        ]);

    $signalQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-003',
            'prompt' => 'Czy sygnalizator nadaje sygnal zielony dla Twojego pasa ruchu?',
            'metadata' => [],
        ]);

    $genericIntersectionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-004',
            'prompt' => 'Czy na tym skrzyzowaniu masz obowiazek zachowac szczegolna ostroznosc?',
            'metadata' => [],
        ]);

    $vehicleLightsQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-005',
            'prompt' => 'Czy w tej sytuacji masz obowiazek uzywac swiatel mijania?',
            'metadata' => [],
        ]);

    $informationalQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-006',
            'prompt' => 'Czy ten znak oznacza wyjazd z obszaru zabudowanego?',
            'metadata' => [],
        ]);

    $rescueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-007',
            'prompt' => 'Czy numer telefonu 112 jest ogolnopolskim numerem alarmowym dla wszystkich sluzb ratowniczych?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $equipmentQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-008',
            'prompt' => 'Ktory z tych przedmiotow stanowi obowiazkowe wyposazenie kazdego samochodu osobowego?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $warningKeywordQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-009',
            'prompt' => 'Czy umieszczony na tablicy znak ostrzega Cie o miejscu na drodze, w ktorym moze wystepowac niebezpieczenstwo?',
            'metadata' => [],
        ]);

    $daytimeLightsQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-010',
            'prompt' => 'Czy w warunkach zmniejszonej przejrzystosci powietrza wolno Ci jechac z wlaczonymi swiatlami do jazdy dziennej?',
            'metadata' => [],
        ]);

    $lanePositionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-011',
            'prompt' => 'Czy w tej sytuacji zajmujesz wlasciwy pas ruchu?',
            'metadata' => [],
        ]);

    $railQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-012',
            'prompt' => 'Czy zblizajac sie do widocznego przejazdu masz obowiazek upewnic sie, ze nie nadjezdza pociag?',
            'metadata' => [],
        ]);

    $pedestrianQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-013',
            'prompt' => 'Czy w tej sytuacji masz obowiazek zareagowac na zachowanie pieszego?',
                'metadata' => [],
        ]);

    $controllerSignalQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-014',
            'prompt' => 'Czy w tej sytuacji masz obowiazek zastosowac sie do sygnalu podawanego przez widoczna osobe?',
            'metadata' => [],
        ]);

    $tramQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-015',
            'prompt' => 'Czy w tej sytuacji masz obowiazek upewnic sie, czy nie nadjezdza tramwaj?',
            'metadata' => [],
        ]);

    $roadMarkingArrowQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-016',
            'prompt' => 'Czy na tym pasie ruchu jazda jest dozwolona tylko w kierunku wskazanym strzalka?',
            'metadata' => [],
        ]);

    $speedLimitQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-017',
            'prompt' => 'Z jaka predkoscia masz obowiazek jechac na autostradzie?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $fieldOfViewQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-018',
            'prompt' => 'Jaki wplyw ma predkosc jazdy na kat widzenia kierujacego samochodem osobowym?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $loadPassengersQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-019',
            'prompt' => 'Ile maksymalnie osob, wliczajac siebie, mozesz przewozic gdy masz prawo jazdy kategorii B?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $triangleQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-020',
            'prompt' => 'W jakiej odleglosci za uszkodzonym pojazdem masz obowiazek umiescic trojkat odblaskowy?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $laneWithMarkingQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-021',
            'prompt' => 'Czy w tej sytuacji wolno Ci zmienic zajmowany pas ruchu przejezdzajac przez biala przerywana linie?',
            'metadata' => [],
        ]);

    $intersectionTurnLaneQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-022',
            'prompt' => 'Czy przed tym skrzyzowaniem zajales wlasciwy pas ruchu, jezeli zamierzasz skrecic w lewo?',
            'metadata' => [],
        ]);

    $intersectionProhibitionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-023',
            'prompt' => 'Czy ten znak zakazu zabrania Ci skretu w prawo na najblizszym skrzyzowaniu?',
            'metadata' => [],
        ]);

    $signalizedIntersectionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-024',
            'prompt' => 'Czy na tym skrzyzowaniu bedzie Ci wolno zawrocic, gdy zapali sie sygnal zielony?',
            'metadata' => [],
        ]);

    $trailerLoadQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-025',
            'prompt' => 'W jaki sposob wolno Ci przewozic ladunek w przyczepie ciagnietej przez samochod osobowy?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $icySkidQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-026',
            'prompt' => 'Jakie zachowanie zwieksza ryzyko wpadniecia w poslizg na oblodzonej nawierzchni jezdni?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $starostaQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-027',
            'prompt' => 'Jaka decyzje podejmie starosta, jesli zostales zatrzymany do kontroli kierujac pojazdem pomimo przedluzenia okresu zatrzymania prawa jazdy do szesciu miesiecy?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $mirrorQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-028',
            'prompt' => 'W jaki sposob powinno byc ustawione lusterko zewnetrzne w samochodzie osobowym?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $brakePipeQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-029',
            'prompt' => 'Jaki moze byc objaw pekniecia przewodu hamulcowego w samochodzie osobowym?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $lightSignalComplianceQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-030',
            'prompt' => 'Czy w tej sytuacji masz obowiazek zastosowac sie do sygnalu swietlnego?',
            'metadata' => [],
        ]);

    $continueLeftLaneQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-031',
            'prompt' => 'Czy w tej sytuacji masz prawo kontynuowac jazde lewym pasem ruchu?',
            'metadata' => [],
        ]);

    $childSeatQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-032',
            'prompt' => 'Co musisz zrobic, gdy chcesz przewiezc trzyletnie dziecko na tylnym siedzeniu samochodu osobowego, a na urzadzeniach przytrzymujacych siedzi juz dwoje dzieci?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $indicatorQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-033',
            'prompt' => 'Czy masz obowiazek uzyc kierunkowskazu, jesli zamierzasz zmienic pas ruchu?',
            'metadata' => [],
        ]);

    $equalIntersectionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-034',
            'prompt' => 'Czy na skrzyzowaniu rownorzednym masz obowiazek ustapic pierwszenstwa pojazdowi nadjezdzajacemu z prawej strony?',
            'metadata' => [],
        ]);

    $stopLineQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-035',
            'prompt' => 'Czy widoczna linia wskazuje miejsce obowiazkowego zatrzymania sie?',
            'metadata' => [],
        ]);

    $rescueIncidentQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-036',
            'prompt' => 'Ktora z wymienionych czynnosci nalezy do obowiazkow kierowcy uczestniczacego w zdarzeniu drogowym, w ktorym nie ma osob zabitych lub rannych?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $perceptionSymptomsQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-037',
            'prompt' => 'Co powinien zrobic kierowca, gdy podczas jazdy zrobi mu sie duszno, zdretwieja rece i pojawi sie szum w uszach?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $reverseLightQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-038',
            'prompt' => 'Do czego sluza swiatla cofania w pojezdzie samochodowym?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $prioritySignQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-039',
            'prompt' => 'Czy na tym skrzyzowaniu oznaczonym znakiem STOP masz obowiazek ustapic pierwszenstwa wszystkim pojazdom?',
            'metadata' => [],
        ]);

    $pedestrianPriorityQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-040',
            'prompt' => 'Czy w tej sytuacji masz pierwszenstwo przed pieszymi?',
            'metadata' => [],
        ]);

    $rectangleStopLineQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-041',
            'prompt' => 'Czy linia zlozona z prostokatow oznacza miejsce zatrzymania pojazdu przed przejsciem dla pieszych?',
            'metadata' => [],
        ]);

    $parkingPositionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-042',
            'prompt' => 'Czy z tak oznakowanej jezdni wolno Ci zjechac na widoczny po lewej stronie parking?',
            'metadata' => [],
        ]);

    $tramStopQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-043',
            'prompt' => 'Czy w tej sytuacji masz prawo jechac dalej dopiero po tym, gdy tramwaj ruszy?',
            'metadata' => [],
        ]);

    $yellowMarkingQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-044',
            'prompt' => 'Czy w tej sytuacji masz obowiazek zastosowac sie do zoltych znakow poziomych?',
            'metadata' => [],
        ]);

    $warningDistanceQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-045',
            'prompt' => 'Czy odleglosc od znaku ostrzegawczego do miejsca niebezpiecznego wynosi do 100 metrow?',
            'metadata' => [],
        ]);

    $mediaSignSignalQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-046',
            'prompt' => 'Czy ten znak oznacza wyjazd z obszaru zabudowanego?',
            'metadata' => [
                'main_media_original' => 'MD_D5_03_ff_loorg.wmv',
            ],
        ]);

    $transportedChildQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-047',
            'prompt' => 'Czy masz prawo przewozic niemowlaka na kolanach osoby doroslej?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $childSeatAirbagQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-048',
            'prompt' => 'Przewozenie dziecka w foteliku bezpieczenstwa zamontowanym tylem do kierunku jazdy na przednim siedzeniu pojazdu, wyposazonego w poduszke powietrzna dla pasazera jest:',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $mediaRoadMarkingQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-049',
            'prompt' => 'Czy ten znak poziomy wyznacza miejsce bezwzglednego zatrzymania sie?',
            'metadata' => [
                'main_media_original' => 'MD_D5_03_ff_loorg.wmv',
            ],
        ]);

    $explanationNoiseQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-050',
            'prompt' => 'Czy w tej sytuacji mozesz wyprzedzac?',
            'explanation' => 'Nie, bo znak informacyjny D-6 oznacza miejsce przeznaczone do przechodzenia pieszych w poprzek drogi.',
            'metadata' => [
                'main_media_original' => 'D38WCz_2org.wmv',
            ],
        ]);

    $turnPermissionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-051',
            'prompt' => 'Czy na tym skrzyzowaniu wolno Ci zawrocic?',
            'metadata' => [],
        ]);

    $mediaSignTurnPermissionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-052',
            'prompt' => 'Czy na widocznym skrzyzowaniu wolno Ci skrecic w lewo?',
            'metadata' => [
                'main_media_original' => '1472D14MMorg.jpg',
            ],
        ]);

    $passengerCountInfoQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-053',
            'prompt' => 'Gdzie mozna znalezc informacje o dozwolonej liczbie przewozonych w samochodzie osobowym osob?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $turnSignalPermissionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-054',
            'prompt' => 'Czy na tej drodze wolno Ci zrezygnowac z sygnalizowania zamiaru skretu w prawo, jesli nikt za Toba nie jedzie?',
            'metadata' => [],
        ]);

    $signalizedTurnPermissionQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-055',
            'prompt' => 'Czy w tej sytuacji, po zmianie sygnalu na zielony, mozesz zawrocic na skrzyzowaniu?',
            'metadata' => [],
        ]);

    $laneFromCurrentLaneQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-056',
            'prompt' => 'Czy z zajmowanego pasa ruchu wolno Ci zawrocic?',
            'explanation' => 'Nie, bo znak poziomy P-8b strzalka kierunkowa oznacza, ze jazda z pasa ruchu, na ktorym sa umieszczone, jest dozwolona tylko w kierunkach wskazanych strzalka kierunkowa.',
            'metadata' => [],
        ]);

    $vehicleCombinationMassQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-057',
            'prompt' => 'Czy prawo jazdy kat. B uprawnia do kierowania zespolem pojazdow skladajacym sie z samochodu osobowego i przyczepy, ktorych laczna dopuszczalna masa calkowita nie przekracza 3,5 tony?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $passengerCapacityDocumentQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-058',
            'prompt' => 'Ktory z wymienionych dokumentow okresla dopuszczalna liczbe osob przewozonych pojazdem samochodowym?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $childRestraintInstallQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->booleanType()
        ->create([
            'external_id' => 'B-TOPIC-059',
            'prompt' => 'W jaki sposob zainstalujesz w pojezdzie fotelik bezpieczenstwa sluzacy do przewozu dziecka?',
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->artisan('content:classify-question-topics')
        ->assertSuccessful();

    expect(QuestionTopic::query()->count())->toBeGreaterThanOrEqual(10);
    expect($warningQuestion->fresh()->questionTopic?->key)->toBe('warning_signs');
    expect($prohibitionQuestion->fresh()->questionTopic?->key)->toBe('prohibition_and_mandatory_signs');
    expect($signalQuestion->fresh()->questionTopic?->key)->toBe('traffic_lights_and_controller_signals');
    expect($genericIntersectionQuestion->fresh()->questionTopic?->key)->toBe('special_caution_exiting_and_securing_vehicle');
    expect($vehicleLightsQuestion->fresh()->questionTopic?->key)->toBe('vehicle_lights_and_signals');
    expect($informationalQuestion->fresh()->questionTopic?->key)->toBe('informational_direction_and_supplementary_signs');
    expect($rescueQuestion->fresh()->questionTopic?->key)->toBe('rescue_actions');
    expect($equipmentQuestion->fresh()->questionTopic?->key)->toBe('safety_equipment_and_restraints');
    expect($warningKeywordQuestion->fresh()->questionTopic?->key)->toBe('warning_signs');
    expect($daytimeLightsQuestion->fresh()->questionTopic?->key)->toBe('vehicle_lights_and_signals');
    expect($lanePositionQuestion->fresh()->questionTopic?->key)->toBe('lane_change_and_turning');
    expect($railQuestion->fresh()->questionTopic?->key)->toBe('rail_and_tram_crossings');
    expect($pedestrianQuestion->fresh()->questionTopic?->key)->toBe('behaviour_towards_pedestrians_and_reduced_mobility');
    expect($controllerSignalQuestion->fresh()->questionTopic?->key)->toBe('traffic_lights_and_controller_signals');
    expect($tramQuestion->fresh()->questionTopic?->key)->toBe('rail_and_tram_crossings');
    expect($roadMarkingArrowQuestion->fresh()->questionTopic?->key)->toBe('road_markings');
    expect($speedLimitQuestion->fresh()->questionTopic?->key)->toBe('speed_limits');
    expect($fieldOfViewQuestion->fresh()->questionTopic?->key)->toBe('driver_field_of_view');
    expect($loadPassengersQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
    expect($triangleQuestion->fresh()->questionTopic?->key)->toBe('safety_equipment_and_restraints');
    expect($laneWithMarkingQuestion->fresh()->questionTopic?->key)->toBe('lane_change_and_turning');
    expect($intersectionTurnLaneQuestion->fresh()->questionTopic?->key)->toBe('lane_change_and_turning');
    expect($intersectionProhibitionQuestion->fresh()->questionTopic?->key)->toBe('prohibition_and_mandatory_signs');
    expect($signalizedIntersectionQuestion->fresh()->questionTopic?->key)->toBe('intersections_with_traffic_lights');
    expect($trailerLoadQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
    expect($icySkidQuestion->fresh()->questionTopic?->key)->toBe('risk_factors_conditions_and_weather');
    expect($starostaQuestion->fresh()->questionTopic?->key)->toBe('owner_obligations_insurance_documents');
    expect($mirrorQuestion->fresh()->questionTopic?->key)->toBe('driver_field_of_view');
    expect($brakePipeQuestion->fresh()->questionTopic?->key)->toBe('mechanical_aspects_of_safety');
    expect($lightSignalComplianceQuestion->fresh()->questionTopic?->key)->toBe('traffic_lights_and_controller_signals');
    expect($continueLeftLaneQuestion->fresh()->questionTopic?->key)->toBe('lane_change_and_turning');
    expect($childSeatQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
    expect($indicatorQuestion->fresh()->questionTopic?->key)->toBe('lane_change_and_turning');
    expect($equalIntersectionQuestion->fresh()->questionTopic?->key)->toBe('joining_traffic_and_equal_intersections');
    expect($stopLineQuestion->fresh()->questionTopic?->key)->toBe('road_markings');
    expect($rescueIncidentQuestion->fresh()->questionTopic?->key)->toBe('rescue_actions');
    expect($perceptionSymptomsQuestion->fresh()->questionTopic?->key)->toBe('perception_decision_alcohol_fatigue');
    expect($reverseLightQuestion->fresh()->questionTopic?->key)->toBe('mechanical_aspects_of_safety');
    expect($prioritySignQuestion->fresh()->questionTopic?->key)->toBe('intersections_with_priority_signs');
    expect($pedestrianPriorityQuestion->fresh()->questionTopic?->key)->toBe('behaviour_towards_pedestrians_and_reduced_mobility');
    expect($rectangleStopLineQuestion->fresh()->questionTopic?->key)->toBe('road_markings');
    expect($parkingPositionQuestion->fresh()->questionTopic?->key)->toBe('road_position_entry_exit_stopping');
    expect($tramStopQuestion->fresh()->questionTopic?->key)->toBe('controlled_crossings_and_public_transport_stops');
    expect($yellowMarkingQuestion->fresh()->questionTopic?->key)->toBe('road_markings');
    expect($warningDistanceQuestion->fresh()->questionTopic?->key)->toBe('warning_signs');
    expect($mediaSignSignalQuestion->fresh()->questionTopic?->key)->toBe('informational_direction_and_supplementary_signs');
    expect($transportedChildQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
    expect($childSeatAirbagQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
    expect($mediaRoadMarkingQuestion->fresh()->questionTopic?->key)->toBe('road_markings');
    expect($explanationNoiseQuestion->fresh()->questionTopic?->key)->toBe('overtaking');
    expect($turnPermissionQuestion->fresh()->questionTopic?->key)->toBe('intersections_with_priority_signs');
    expect($mediaSignTurnPermissionQuestion->fresh()->questionTopic?->key)->toBe('intersections_with_priority_signs');
    expect($passengerCountInfoQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
    expect($turnSignalPermissionQuestion->fresh()->questionTopic?->key)->toBe('vehicle_lights_and_signals');
    expect($signalizedTurnPermissionQuestion->fresh()->questionTopic?->key)->toBe('intersections_with_traffic_lights');
    expect($laneFromCurrentLaneQuestion->fresh()->questionTopic?->key)->toBe('lane_change_and_turning');
    expect($vehicleCombinationMassQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
    expect($passengerCapacityDocumentQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
    expect($childRestraintInstallQuestion->fresh()->questionTopic?->key)->toBe('vehicle_load_and_passenger_safety');
});
