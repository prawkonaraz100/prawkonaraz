# Audyt nazw działów i kategorii nauki

Status: roboczy audyt na branchu `codex/audit-category-topic-names`
Data: 2026-06-08

## Cel

Sprawdzamy, skąd aplikacja bierze nazwy kategorii prawa jazdy oraz nazwy działów widocznych w module nauki. Ten dokument ma być punktem odniesienia przed ewentualnym porządkowaniem nazewnictwa.

## Najważniejszy wniosek

W module `/nauka` są trzy różne warstwy nazw:

1. `license_categories` - kategorie prawa jazdy, np. `A`, `B`, `C`, `T`.
2. `question_topics.name` - surowa/długa nazwa działu zapisana w bazie.
3. `QuestionTopicClassifier::displayLabelForKey()` - krótka nazwa działu widoczna w UI.

To oznacza, że zmiana samego rekordu w tabeli `question_topics` nie musi zmienić etykiety widocznej dla użytkownika. Widok `/nauka` bierze krótką nazwę z klasyfikatora.

## Źródła w kodzie

- `app/Models/LicenseCategory.php` - model kategorii prawa jazdy.
- `app/Models/QuestionTopic.php` - model działów pytań.
- `app/Support/QuestionTopicClassifier.php` - definicje działów, krótkie etykiety UI, bucket `Pytania podstawowe/specjalistyczne`.
- `app/Support/StudyTopicGroupsService.php` - buduje listę działów dla `/nauka`.
- `app/Http/Controllers/SessionPageController.php` - przekazuje listę działów do strony nauki.

## Jak działa lista działów na `/nauka`

Lista działów jest budowana tylko z pytań, które spełniają warunki:

- mają wybraną kategorię prawa jazdy,
- `questions.is_active = true`,
- `delivery_issue IS NULL`,
- mają ustawione `question_topic_id`.

Potem `StudyTopicGroupsService`:

- liczy pytania per `question_topic_id`,
- pobiera aktywne `QuestionTopic`,
- ustawia `label` przez `QuestionTopicClassifier::displayLabelForKey($topic->key)`,
- ustawia grupę przez `QuestionTopicClassifier::bucketLabelForKey($topic->key)`.

## Snapshot lokalnych danych

Artefakty wygenerowane w tym przebiegu:

- `output/analysis/category-topic-names/local-topic-groups.json`
- `output/analysis/category-topic-names/topic-name-matrix.json`

### Kategorie prawa jazdy widoczne w bazie

| Kategoria | Nazwa w bazie | Liczba działów z aktywnymi pytaniami | Liczba pytań |
| --- | --- | ---: | ---: |
| A | Kategoria A | 31 | 1428 |
| A1 | Kategoria A1 | 31 | 1414 |
| A2 | Kategoria A2 | 31 | 1412 |
| AM | Kategoria AM | 30 | 1513 |
| B | Kategoria B | 31 | 2194 |
| B1 | Kategoria B1 | 30 | 1424 |
| C | Kategoria C | 31 | 1498 |
| C1 | Kategoria C1 | 31 | 1445 |
| D | Kategoria D | 31 | 1492 |
| D1 | Kategoria D1 | 31 | 1479 |
| PT | Kategoria PT | 29 | 439 |
| T | Kategoria T | 31 | 1306 |

### Brakujące działy w kategoriach

| Kategoria | Brakujący dział według pełnej listy definicji |
| --- | --- |
| AM | `driving_with_trailer` / Jazda z przyczepą |
| B1 | `driving_with_trailer` / Jazda z przyczepą |
| PT | `prohibition_and_mandatory_signs`, `road_markings` |

Do decyzji: `AM` i `B1` bez działu przyczepy może być merytorycznie poprawne. `PT` wymaga osobnej decyzji produktowej, bo kategoria jest aktywna w bazie, ale nie należy do standardowej listy kategorii, którą komunikujemy użytkownikowi.

## Obecne nazwy działów

| Bucket | Klucz techniczny | Nazwa widoczna w UI | Surowa nazwa w bazie |
| --- | --- | --- | --- |
| Podstawowe | `warning_signs` | Znaki ostrzegawcze | Znaki ostrzegawcze |
| Podstawowe | `prohibition_and_mandatory_signs` | Znaki zakazu i nakazu | Znaki zakazu, nakazu |
| Podstawowe | `informational_direction_and_supplementary_signs` | Znaki informacyjne i tabliczki | Znaki informacyjne, kierunku i miejscowosci, uzupelniajace |
| Podstawowe | `road_markings` | Oznakowanie poziome | Znaki drogowe poziome |
| Podstawowe | `traffic_lights_and_controller_signals` | Sygnaly swietlne i kierujacy ruchem | Sygnaly swietlne, sygnaly dawane przez kierujacego ruchem |
| Podstawowe | `vehicle_lights_and_signals` | Swiatla i sygnaly pojazdu | Uzywanie swiatel zewnetrznych i sygnalow pojazdu |
| Podstawowe | `joining_traffic_and_equal_intersections` | Wlaczanie sie do ruchu i rownorzedne | Wlaczanie sie do ruchu, skrzyzowania rownorzedne |
| Podstawowe | `intersections_with_priority_signs` | Skrzyzowania z pierwszenstwem | Skrzyzowania ze znakami okreslajacymi pierwszenstwo przejazdu |
| Podstawowe | `intersections_with_traffic_lights` | Skrzyzowania z sygnalizacja | Skrzyzowania z sygnalizacja swietlna |
| Podstawowe | `controlled_crossings_and_public_transport_stops` | Przejscia, przystanki i kierujacy ruchem | Skrzyzowania lub przejscia dla pieszych z kierujacym ruchem, miejsca przystankow komunikacji publicznej |
| Podstawowe | `rail_and_tram_crossings` | Przejazdy kolejowe i tramwajowe | Zachowanie na przejazdach kolejowych i tramwajowych |
| Podstawowe | `road_position_entry_exit_stopping` | Pozycja pojazdu, zatrzymanie i postoj | Pozycja pojazdu na drodze, wjazd i zjazd ze skrzyzowania, zatrzymanie i postoj |
| Podstawowe | `lane_change_and_turning` | Zmiana pasa i kierunku jazdy | Zmiana pasa ruchu, zmiana kierunku jazdy |
| Podstawowe | `overtaking` | Wyprzedzanie | Wyprzedzanie |
| Podstawowe | `passing_reversing` | Omijanie, wymijanie i cofanie | Omijanie, wymijanie, cofanie |
| Podstawowe | `behaviour_towards_pedestrians_and_reduced_mobility` | Piesi i ograniczona mobilnosc | Zachowanie wobec pieszego, wobec osoby o ograniczonej mozliwosci poruszania sie |
| Podstawowe | `behaviour_towards_cyclists_and_children` | Rowerzysci i dzieci | Zachowanie wobec rowerzysty i dzieci |
| Podstawowe | `special_caution_exiting_and_securing_vehicle` | Szczegolna ostroznosc i pojazd | Znaczenie zachowania szczegolnej ostroznosci w stosunku do innych uzytkownikow drogi, wysiadanie z pojazdu, zabezpieczenie pojazdu |
| Podstawowe | `breakdown_accident_and_first_aid` | Awaria, wypadek i pierwsza pomoc | Ogolne zasady okreslajace zachowanie kierowcy w momencie awarii lub wypadku, udzielanie pierwszej pomocy przedmedycznej |
| Podstawowe | `perception_decision_alcohol_fatigue` | Percepcja, zmeczenie i alkohol | Spostrzeganie, ocena sytuacji i podejmowanie decyzji, szczegolnie w zakresie czasu reakcji oraz zmian w zachowaniu za kierownica, spowodowanych wplywem alkoholu, lekow i produktow leczniczych, stanem swiadomosci i zmeczeniem |
| Specjalistyczne | `speed_limits` | Predkosci i ograniczenia | Dopuszczalne predkosci pojazdu, ograniczenia |
| Specjalistyczne | `distances_and_braking` | Odstepy i hamowanie | Odstepy i hamowanie pojazdu |
| Specjalistyczne | `driving_technique` | Technika kierowania | Technika kierowania pojazdem |
| Specjalistyczne | `safety_equipment_and_restraints` | Pasy, foteliki i wyposazenie | Wyposazenie pojazdu zwiazane z bezpieczenstwem, korzystanie z pasow, zaglowkow i fotelikow |
| Specjalistyczne | `vehicle_load_and_passenger_safety` | Ladunek i przewozone osoby | Czynniki bezpieczenstwa odnoszace sie do pojazdu, ladunku i przewozonych osob |
| Specjalistyczne | `risk_factors_conditions_and_weather` | Warunki, ryzyko i pogoda | Czynniki ryzyka zwiazane z roznymi warunkami drogowymi, w szczegolnosci ze zmiana tych warunkow w zaleznosci od pogody i pory dnia lub nocy, wlasciwosci roznych typow drog i zwiazane z tym obowiazujace wymagania |
| Specjalistyczne | `driver_field_of_view` | Pole widzenia kierowcy | Rozne pola widzenia kierowcow |
| Specjalistyczne | `owner_obligations_insurance_documents` | Dokumenty, ubezpieczenie i obowiazki | Obowiazki wlasciciela/posiadacza pojazdu, ubezpieczenia, wymagane dokumenty |
| Specjalistyczne | `mechanical_aspects_of_safety` | Mechanika i bezpieczenstwo pojazdu | Aspekty mechaniczne zwiazane z zachowaniem bezpieczenstwa na drodze |
| Specjalistyczne | `rescue_actions` | Akcje ratunkowe | Akcje ratunkowe |
| Specjalistyczne | `driving_with_trailer` | Jazda z przyczepa | Kierowanie pojazdem z przyczepa |

## Rekomendacja nazewnicza

Przed zmianami warto zdecydować, czy krótkie etykiety w UI mają mieć polskie znaki. Moja rekomendacja: tak, bo to są teksty widoczne dla użytkownika i powinny wyglądać profesjonalnie.

Zmiana powinna dotyczyć przede wszystkim `display_name` w `QuestionTopicClassifier`, a nie surowych nazw w bazie. Dzięki temu nie zmieniamy klasyfikacji, relacji pytań ani migracji danych.

Proponowane kierunki:

- poprawić polskie znaki w krótkich etykietach UI,
- zostawić klucze techniczne bez zmian,
- nie zmieniać `question_topic_id` przy pytaniach,
- osobno zdecydować, czy `PT` ma pozostać aktywne,
- osobno potwierdzić, czy brak działu `Jazda z przyczepą` dla `AM` i `B1` jest pożądany.

## Proponowane krótkie etykiety po redakcji

| Obecnie | Propozycja |
| --- | --- |
| Sygnaly swietlne i kierujacy ruchem | Sygnały świetlne i kierujący ruchem |
| Swiatla i sygnaly pojazdu | Światła i sygnały pojazdu |
| Wlaczanie sie do ruchu i rownorzedne | Włączanie się do ruchu i skrzyżowania równorzędne |
| Skrzyzowania z pierwszenstwem | Skrzyżowania z pierwszeństwem |
| Skrzyzowania z sygnalizacja | Skrzyżowania z sygnalizacją |
| Przejscia, przystanki i kierujacy ruchem | Przejścia, przystanki i kierujący ruchem |
| Pozycja pojazdu, zatrzymanie i postoj | Pozycja pojazdu, zatrzymanie i postój |
| Piesi i ograniczona mobilnosc | Piesi i osoby o ograniczonej mobilności |
| Rowerzysci i dzieci | Rowerzyści i dzieci |
| Szczegolna ostroznosc i pojazd | Szczególna ostrożność i zabezpieczenie pojazdu |
| Percepcja, zmeczenie i alkohol | Percepcja, zmęczenie i alkohol |
| Predkosci i ograniczenia | Prędkości i ograniczenia |
| Odstepy i hamowanie | Odstępy i hamowanie |
| Pasy, foteliki i wyposazenie | Pasy, foteliki i wyposażenie |
| Ladunek i przewozone osoby | Ładunek i przewożone osoby |
| Dokumenty, ubezpieczenie i obowiazki | Dokumenty, ubezpieczenie i obowiązki |
| Mechanika i bezpieczenstwo pojazdu | Mechanika i bezpieczeństwo pojazdu |
| Jazda z przyczepa | Jazda z przyczepą |

## Ryzyko zmian

Niskie, jeśli zmieniamy tylko `display_name` w klasyfikatorze:

- nie zmieniamy przypisań pytań,
- nie zmieniamy liczników,
- nie zmieniamy sesji nauki,
- nie zmieniamy `topic_key`,
- zmieniają się tylko etykiety widoczne w UI.

Średnie, jeśli zaczniemy zmieniać `key`, `question_topics.name`, aktywność kategorii albo `question_topic_id`, bo wtedy wpływamy na klasyfikację, liczniki, widoki nauki i możliwe porównania z PJ360.

