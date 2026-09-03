# PJ360 Category Consistency Audit

## Status

Audit jest rozpoczęty i mamy już pierwszy twardy przebieg porównawczy:

- snapshot topic groups z naszego `/nauka`
- snapshot topic groups z `prawo-jazdy-360.pl/kurs`
- porownanie bucket totals per kategoria
- porownanie per temat po sensownej normalizacji etykiet

Aktualny etap prac:

- audit i target matrix sa gotowe
- narzedzia `classifier + audited overrides` sa wdrozone
- kategoria `B` jest w aktywnym rolloutcie zamykajacym
- fale `1-5` dla `B` sa juz wykonane i zapisane jako odtwarzalne spec/package artefakty

Artefakty robocze:

- [pj360-topic-groups.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-category-consistency/pj360-topic-groups.json)
- [local-topic-groups.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-category-consistency/local-topic-groups.json)
- [bucket-total-comparison.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-category-consistency/bucket-total-comparison.json)
- [topic-label-delta-preview.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-category-consistency/topic-label-delta-preview.json)

## Metodologia

Po naszej stronie porownujemy nie surowa baze, tylko to, co naprawde buduje `/nauka`:

- [SessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/SessionPageController.php)
- [StudyTopicGroupsService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudyTopicGroupsService.php)
- [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php)

Po stronie PJ360 porownujemy tylko mianownik z `x / y`, czyli:

- `y = laczna liczba pytan w dziale`

Pierwszy preview `topic-label-delta-preview.json` byl oparty o zbyt surowe porownanie tekstowe i zawyzal liczbe `missing_exact_label_match`, bo:

- nasze kanoniczne nazwy sa zwykle ascii-only
- PJ360 uzywa polskich znakow
- nasze `display_label` sa czesto skrocone

Po normalizacji polskich znakow i znakow interpunkcyjnych obraz jest duzo czystszy.

## Co juz wiemy

### 1. PJ360 i my pokrywamy prawie ten sam zestaw tematow

Po normalizacji etykiet i po policzeniu liczby tematow per kategoria:

- `AM`, `A1`, `A2`, `A`, `B1`, `B`, `C1`, `C`, `D1`, `D` maja:
  - `20` tematow podstawowych
  - `11` tematow specjalistycznych
  - lacznie `31 / 31`
- `T` ma:
  - `20` tematow podstawowych
  - `9` tematow specjalistycznych
  - lacznie `29 / 31`

Wyjatek dla `T`:

- PJ360 ma temat `Dopuszczalne predkosci pojazdu, ograniczenia`
- PJ360 ma temat `Wyposazenie pojazdu zwiazane z bezpieczenstwem, korzystanie z pasow, zaglowkow i fotelikow`
- w naszym snapshotcie `T` te dwa tematy nie wystepuja

Wazne doprecyzowanie:

- to nie jest problem braku `question_topic_id`
- to nie jest problem filtra `readyForDelivery()`
- to nie jest problem `is_active`

Sprawdzilismy `T` na trzech poziomach:

- `active + ready`
- `active`
- `all`

W kazdym z tych przebiegow:

- `speed_limits = 0`
- `safety_equipment_and_restraints = 0`

To oznacza, ze po naszej stronie w kategorii `T` po prostu nie ma rekordow przypisanych do tych dwoch tematow, podczas gdy w PJ360 one istnieja i sa liczone:

- `speed_limits` -> `10`
- `safety_equipment_and_restraints` -> `5`

To oznacza, ze problem nie jest glownie w samych nazwach dzialow.

### 2. Bucket totals sa dosc blisko, ale stale nizsze po naszej stronie

W kazdej kategorii nasz laczny wynik jest nizszy od PJ360.

Najwieksze delty:

- `B`: `2187 vs 2137` (`-50`)
- `B1`: `1472 vs 1424` (`-48`)

Mniejsze, ale stale rozjazdy:

- `AM`: `-18`
- `A1`: `-20`
- `A2`: `-21`
- `A`: `-23`
- `C1`: `-18`
- `C`: `-18`
- `D1`: `-17`
- `D`: `-17`
- `T`: `-20`

Wniosek:

- globalnie nie wyglada to na dramatyczny brak setek pytan
- ale lokalne przypisanie do tematow jest bardzo inne niz w PJ360

### 3. Najwiekszy problem siedzi w rozkladzie pytan per temat

Po znormalizowanym mapowaniu tematow roznice per temat sa bardzo duze i bardzo powtarzalne miedzy kategoriami.

Najbardziej przeszacowane u nas:

- `Skrzyzowania ze znakami okreslajacymi pierwszenstwo przejazdu`
  - sredni delta: `+220.82`
  - zakres: `+204 .. +298`
- `Znaczenie zachowania szczegolnej ostroznosci w stosunku do innych uzytkownikow drogi, wysiadanie z pojazdu, zabezpieczenie pojazdu`
  - sredni delta: `+214.73`
  - zakres: `+172 .. +294`
- `Technika kierowania pojazdem`
  - sredni delta: `+51.91`
- `Kierowanie pojazdem z przyczepa`
  - sredni delta: `+12.36`

Najbardziej zanizone u nas:

- `Znaki drogowe poziome`
  - sredni delta: `-65.73`
  - zakres: `-61 .. -89`
- `Wlaczanie sie do ruchu, skrzyzowania rownorzedne`
  - sredni delta: `-60.55`
- `Zachowanie wobec pieszego, wobec osoby o ograniczonej mozliwosci poruszania sie`
  - sredni delta: `-45.64`
- `Skrzyzowania z sygnalizacja swietlna`
  - sredni delta: `-42.55`
- `Zachowanie na przejazdach kolejowych i tramwajowych`
  - sredni delta: `-37.82`
- `Znaki ostrzegawcze`
  - sredni delta: `-32.91`
- `Znaki zakazu, nakazu`
  - sredni delta: `-28.27`

To nie wyglada jak losowe odchylenia. To wyglada jak systemowy problem klasyfikacji tematow po naszej stronie.

## Najmocniejsza hipoteza

Najbardziej prawdopodobne jest to, ze:

- mamy zblizony laczny zbior pytan
- ale nasz [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php) przypisuje duza liczbe pytan do innych tematow niz PJ360

Najbardziej podejrzane miejsca w klasyfikatorze:

1. Bardzo agresywne przypisanie wszystkiego ze `skrzyz` do tematu pierwszenstwa

W [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php) sa reguly typu:

- `keyword:priority_intersection`
- `keyword:priority_turning`
- finalnie nawet ogolne `keyword:generic_intersection`

To dobrze tlumaczy, czemu temat:

- `Skrzyzowania ze znakami okreslajacymi pierwszenstwo przejazdu`

jest u nas tak przepompowany prawie we wszystkich kategoriach.

2. Zbyt szeroki fallback do `special_caution_exiting_and_securing_vehicle`

Jesli pytanie podstawowe nie zostanie lepiej rozpoznane, klasyfikator potrafi wpasc w:

- `fallback:basic`
- i wtedy trafia do `special_caution_exiting_and_securing_vehicle`

To bardzo dobrze zgadza sie z tym, ze temat:

- `Znaczenie zachowania szczegolnej ostroznosci ...`

jest zawyzony o setki rekordow.

3. Zbyt szeroki fallback do `driving_technique` dla pytan specjalistycznych

Jesli pytanie specjalistyczne nie zostanie poprawnie rozpoznane, klasyfikator wpada w:

- `fallback:specialist`

co kieruje je do:

- `Technika kierowania pojazdem`

To tez bardzo dobrze zgadza sie z obserwowanym zawyzeniem tego tematu we wszystkich kategoriach.

## Dowod z poziomu pytan

Zeszlismy tez poziom nizej, na konkretne pytania w kategorii `B`, dla 3 najbardziej podejrzanych tematow.

### 1. `intersections_with_priority_signs`

W `B` ten temat ma u nas `426` pytan. Rozklad `matched_by` pokazuje, ze bardzo duza czesc wpada tu szeroko:

- `keyword:priority_intersection` -> `186`
- `keyword:generic_intersection` -> `101`
- `keyword:priority_turning` -> `29`
- `keyword:signalized_intersection` -> `24`
- `keyword:road_markings_reflectors` -> `22`

To jest bardzo mocny sygnal, ze temat jest przepompowany nie tylko przez pytania o pierwszenstwo, ale tez przez:

- ogolne pytania o skrzyzowania
- pytania o sygnalizacje swietlna
- pytania o znaki ostrzegawcze przed skrzyzowaniem

Przykladowe pytania siedzące dziś w tym temacie:

- `8389` - `Czy w widocznej sytuacji masz prawo zawrócić na najbliższym skrzyżowaniu?`
- `13430` - `Czy ten znak ostrzega, że zbliżasz się do skrzyżowania z wlotem drogi jednokierunkowej występującej z lewej strony?`
- `10122` - `Czy ten znak ostrzega o zbliżaniu się do skrzyżowania z drogą podporządkowaną znajdującą się po lewej stronie?`
- `1699` - `Czy skręcając w lewo na tym skrzyżowaniu zachowałeś właściwy tor jazdy?`

To wyglada na mieszanie co najmniej kilku osobnych tematow:

- ostrzeganie znakami
- skrzyzowania z sygnalizacja
- zmiana kierunku jazdy / tor jazdy
- skrzyzowania z pierwszenstwem

### 2. `special_caution_exiting_and_securing_vehicle`

W `B` ten temat ma u nas `355` pytan. To juz samo w sobie jest bardzo podejrzane. Rozklad `matched_by`:

- `fallback:basic` -> `163`
- `keyword:stopping_position` -> `22`
- `keyword:overtaking` -> `16`
- `keyword:road_markings_reflectors` -> `15`
- `keyword:znak informacyj` -> `13`
- `keyword:rail_crossing` -> `11`
- `keyword:znak ostrzegawcz` -> `11`
- `keyword:public_transport_stop` -> `10`

To jest praktycznie dowod, ze ten temat robi dziś za "kosz na wszystko", czego klasyfikator nie potrafi jednoznacznie dopasowac.

Przykladowe pytania siedzące dziś w tym temacie:

- `2845` - `Czy ten znak oznacza wyjazd z obszaru zabudowanego?`
- `1672` - `Czy w tej sytuacji powinieneś zmniejszyć prędkość?`
- `3544` - `Czy w tej sytuacji masz obowiązek używać świateł mijania?`
- `4354` - `Czy w tej sytuacji masz prawo jechać z włączonymi światłami drogowymi?`
- `6248` - `Czy w tej sytuacji masz obowiązek zastosować zasadę ograniczonego zaufania?`

To nie sa pytania o:

- wysiadanie z pojazdu
- zabezpieczenie pojazdu
- szczegolna ostroznosc w tym waskim sensie

To sa pytania z wielu innych tematow, ktore wpadly tu glownie przez fallback.

### 3. `driving_technique`

W `B` ten temat ma u nas `132` pytania. Rozklad `matched_by`:

- `fallback:specialist` -> `97`
- `keyword:driving_technique` -> `14`
- `keyword:mechanical_safety` -> `5`
- `keyword:field_of_view` -> `5`
- `keyword:distances_braking` -> `4`

To jest bardzo czytelne: zdecydowana wiekszosc pytan w tym temacie nie trafia tu przez rzeczywiste dopasowanie, tylko przez specjalistyczny fallback.

Przykladowe pytania siedzące dziś w tym temacie:

- `4364` - `Jaki wpływ ma prędkość jazdy na kąt widzenia kierującego samochodem osobowym?`
- `4600` - `Czy numer telefonu 112 jest ogólnopolskim numerem alarmowym dla wszystkich służb ratowniczych?`
- `10099` - `Jaki może być objaw pęknięcia przewodu hamulcowego w samochodzie osobowym?`
- `6410` - `Który z tych przedmiotów stanowi obowiązkowe wyposażenie każdego samochodu osobowego?`
- `9090` - `Która z widocznych na ilustracji lampek kontrolnych informuje o włączonych kierunkowskazach?`

To sa pytania, ktore intuicyjnie powinny siedziec raczej w:

- `driver_field_of_view`
- `rescue_actions`
- `mechanical_aspects_of_safety`
- `safety_equipment_and_restraints`
- `vehicle_lights_and_signals`

czyli znowu mamy bardzo mocny sygnal, ze winny jest fallback, a nie brak materialu.

## Najwazniejsze wnioski na teraz

1. Preview `missing_exact_label_match` nie byl wiarygodny jako sygnal braku tematu.
Po normalizacji nazw prawie wszystkie tematy mapuja sie poprawnie.

2. Glowny problem to nie nazewnictwo, tylko rozklad pytan pomiedzy tematami.

3. Rozjazd jest systemowy i powtarzalny miedzy kategoriami.
To sugeruje problem w wspolnej logice klasyfikacji, a nie pojedynczy blad danych w jednej kategorii.

4. `T` wymaga osobnego sprawdzenia dla dwoch brakujacych tematow specjalistycznych.
To wyglada jak rzeczywisty brak widocznosci lub brak przypisania po naszej stronie.

5. Mamy juz dowod na poziomie konkretnych pytan, ze 3 najbardziej odstajace tematy sa zanieczyszczone wpisami, ktore logicznie powinny trafic do innych topic keys.

## Co sprawdzic dalej

### Priorytet 1

Zweryfikowac w danych pytania, ktore u nas trafiaja do:

- `intersections_with_priority_signs`
- `special_caution_exiting_and_securing_vehicle`
- `driving_technique`

oraz sprawdzic, czy PJ360 lokuje je w innych dzialach.

### Priorytet 2

Zbadac, dlaczego w `T` nie widzimy:

- `speed_limits`
- `safety_equipment_and_restraints`

Mozliwe przyczyny:

- brak pytan tej kategorii po filtrze `/nauka`
- brak `question_topic_id`
- nieprzejscie `readyForDelivery()`
- inna polityka kategorii po naszej stronie niz w PJ360

### Priorytet 3

Przygotowac drugi etap raportu:

- zejscie do poziomu konkretnych pytan tylko dla tematow z najwiekszym delta
- porownanie probki pytan i ich klasyfikacji
- wskazanie konkretnych regul w [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php), ktore warto zaostrzyc

## Plan doprowadzenia rozkladu do PJ360

### Cel

Target referencyjny jest prosty:

- chcemy, zeby rozklad pytan per temat i per kategoria byl zgodny z PJ360
- PJ360 traktujemy jako referencje dla:
  - liczby tematow
  - granic tematow
  - przydzialu pytan do tematow

Jednoczesnie nie chcemy:

- recznie przepinac tysiecy rekordow bez kontroli
- zepsuc `/nauka`
- pogorszyc aktualnych flow sesji i statystyk

Dlatego zmiany powinny isc w dwoch warstwach:

1. poprawa logiki klasyfikatora
2. kontrolowany reclassify danych i porownanie po kazdym kroku

## Zasada wdrozenia

Nie poprawiamy wszystkiego naraz.

Idziemy od tematow, ktore:

- maja najwiekszy i najbardziej systemowy delta
- maja najbardziej oczywisty problem z fallbackiem albo zbyt szeroka regula
- po naprawie powinny automatycznie poprawic tez kilka innych tematow

To oznacza taka kolejnosc:

1. `intersections_with_priority_signs`
2. `special_caution_exiting_and_securing_vehicle`
3. `driving_technique`
4. dopiero potem pozostale tematy z duzym ujemnym delta
5. osobno i jawnie przypadek `T`

## Etap 1. Uspokoic `intersections_with_priority_signs`

### Problem

Dzis ten temat jest przepompowany przez:

- `keyword:priority_intersection`
- `keyword:priority_turning`
- `keyword:generic_intersection`
- dodatkowe wtoki typu `signalized_intersection` i `road_markings_reflectors`

Najbardziej niebezpieczna regula to:

- [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php#L569)
  `if ($this->containsAny($text, ['skrzyz']))`

Ta regula robi z prawie kazdego pytania o skrzyzowaniu temat pierwszenstwa.

### Co zmienic

1. Usunac lub bardzo mocno ograniczyc `keyword:generic_intersection`.
2. Temat `intersections_with_priority_signs` powinien wymagac wyraznych sygnalow:
   - `pierwszenstw`
   - `ustap pierwszenstwa`
   - `droga z pierwszenstwem`
   - `STOP`
3. Pytania o:
   - `skrzyzowanie + sygnalizacja`
   powinny trafic do `intersections_with_traffic_lights`
4. Pytania o:
   - tor jazdy
   - zajecie pasa
   - skret
   - zawracanie
   powinny trafic do `lane_change_and_turning`
5. Pytania typu:
   - `ten znak ostrzega o zblizaniu sie do skrzyzowania...`
   najpierw przechodza przez logike znaku / media, a nie ogolne `skrzyz`

### Oczekiwany efekt

- mocny spadek `intersections_with_priority_signs`
- wzrost:
  - `intersections_with_traffic_lights`
  - `lane_change_and_turning`
  - czesciowo `warning_signs`

## Etap 2. Zlikwidowac temat-smietnik `special_caution_exiting_and_securing_vehicle`

### Problem

Dzis ten temat jest przepompowany glownie przez:

- [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php#L683)
  `fallback:basic`

oraz przez zbyt luzne trafienia, ktore nie odnosza sie faktycznie do:

- wysiadania
- zabezpieczenia pojazdu
- otwierania drzwi

### Co zmienic

1. Usunac `fallback:basic -> special_caution_exiting_and_securing_vehicle`.
2. Ten temat powinien zostac tylko dla pytan, ktore maja w tekscie jasne sygnaly:
   - `wysiadanie`
   - `opuszczasz pojazd`
   - `otwieranie drzwi`
   - `zabezpieczenie pojazdu`
   - ewentualnie bardzo jawne `szczegolna ostroznosc`, ale tylko gdy nie ma silniejszego tematu wyzej
3. Pytania bez mocnego dopasowania nie powinny automatycznie spadac do tego tematu.
4. Zamiast tego powinny:
   - przejsc przez lepsze dopasowanie keywordowe
   - albo trafic do jawnej kolejki `unresolved_basic_topic` na czas przebudowy

### Oczekiwany efekt

- duzy spadek `special_caution_exiting_and_securing_vehicle`
- wzrost:
  - `vehicle_lights_and_signals`
  - `warning_signs`
  - `controlled_crossings_and_public_transport_stops`
  - `behaviour_towards_pedestrians_and_reduced_mobility`
  - `rail_and_tram_crossings`
  - `informational_direction_and_supplementary_signs`

## Etap 3. Ograniczyc `fallback:specialist -> driving_technique`

### Problem

Dzis:

- [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php#L754)
  `fallback:specialist`

wrzuca do `driving_technique` praktycznie wszystko, czego nie rozpoznamy lepiej.

To powoduje, ze w `driving_technique` laduja pytania o:

- `112`
- awarie i usterki
- obowiazkowe wyposazenie
- lampki kontrolne
- pole widzenia
- predkosc i hamowanie

### Co zmienic

1. Usunac `fallback:specialist -> driving_technique`.
2. `driving_technique` powinno zostac tylko dla rzeczywistej techniki jazdy:
   - technika prowadzenia
   - operowanie kierownica, sprzeglem, biegiem
   - tor jazdy
   - ekonomika jazdy
3. Wzmocnic rozpoznawanie tematow specjalistycznych:
   - `rescue_actions`
   - `mechanical_aspects_of_safety`
   - `safety_equipment_and_restraints`
   - `driver_field_of_view`
   - `speed_limits`
   - `owner_obligations_insurance_documents`
4. Pytania bez dobrego dopasowania powinny trafic do jawnej kolejki review, a nie domyslnie do `driving_technique`

### Oczekiwany efekt

- mocny spadek `driving_technique`
- wzrost:
  - `rescue_actions`
  - `mechanical_aspects_of_safety`
  - `safety_equipment_and_restraints`
  - `driver_field_of_view`
  - `speed_limits`
  - `owner_obligations_insurance_documents`

## Etap 4. Osobny przypadek `T`

### Problem

Kategoria `T` nie ma u nas w ogole:

- `speed_limits`
- `safety_equipment_and_restraints`

To nie jest problem klasyfikatora fallbackowego na poziomie samego `/nauka`, bo:

- w danych tej kategorii nie ma rekordow z tymi topic keys

### Co zmienic

1. Zweryfikowac, czy pytania `T`, ktore w PJ360 wpadaja do tych tematow:
   - istnieja u nas z innym `question_topic_id`
   - czy w ogole nie zostaly przypisane do `T`
2. Jesli istnieja, to trzeba:
   - poprawic klasyfikacje
   - zrobic reclassify dla `T`
3. Jesli nie istnieja, to trzeba uznac to za osobny problem danych / polityki kategorii

### Oczekiwany efekt

- `T` ma dojsc z `29` do `31` tematow

## Bezpieczna strategia wdrozenia

### Krok 1. Najpierw tryb audit-only

Nie ruszamy od razu rekordow produkcyjnych.

Dodajemy mechanizm:

- `classify(question)` -> `proposed_topic_key`
- raport diffu:
  - `current_topic_key`
  - `proposed_topic_key`
  - `matched_by`
  - `category`

I najpierw patrzymy:

- ile pytan zmieniloby temat
- czy delta zbliza nas do PJ360
- czy nie psujemy innych bucketow

### Krok 2. Reclassify tylko dla 1 tematu albo malej grupy

Najpierw robimy iteracje:

1. poprawa regul
2. dry run
3. nowy snapshot `/nauka`
4. porownanie z PJ360

Nie robimy jednego wielkiego reclassify na wszystko.

### Krok 3. Zapis baseline i diffu

Przed kazdym reclassify zapisujemy:

- snapshot topic counts before
- snapshot topic counts after
- lista pytan, ktore zmienily temat

Tak, zeby mozna bylo:

- ocenic zysk
- cofnac sie logicznie
- nie zgadywac po fakcie

### Krok 4. Testy regresyjne

Po kazdej iteracji musimy miec:

- testy jednostkowe klasyfikatora dla reprezentatywnych promptow
- test snapshotowy counts per kategoria
- przynajmniej smoke porownanie z PJ360 dla tematow objetych zmiana

## Docelowa kolejnosc pracy

1. Zrobic `audit-only reclassifier` i snapshot diffu.
2. Naprawic `generic_intersection`.
3. Naprawic `fallback:basic`.
4. Naprawic `fallback:specialist`.
5. Zrobic osobny przebieg dla `T`.
6. Po kazdej iteracji przeliczyc:
   - topic counts
   - bucket totals
   - delta do PJ360

## Definicja sukcesu

Uznajemy, ze osiagamy target PJ360, gdy:

1. Dla `AM..D` mamy:
   - `31 / 31` tematow
   - sensownie male delty per temat
2. Dla `T` mamy:
   - `31 / 31` tematow
3. Najbardziej odstajace tematy przestaja miec systemowy bias:
   - `intersections_with_priority_signs`
   - `special_caution_exiting_and_securing_vehicle`
   - `driving_technique`
4. Rozklad nie tylko "sumuje sie podobnie", ale takze semantycznie odpowiada PJ360

## Korekta targetow referencyjnych

W trakcie pierwszych iteracji strojenia okazalo sie, ze czesc roboczych liczb, na ktorych chwilowo opieralismy tuning, pochodzila z innej kategorii niz aktywnie analizowana `B`.

To wymaga jawnego zapisania, zeby nie wrocic do tego bledu:

- nie stroimy tematow pod pojedyncze liczby zapisane w notatkach
- nie stroimy `B` pod targety `C`, `C1` albo `T`
- kazda kolejna iteracja musi byc porownywana z kanoniczna macierza targetow PJ360 per kategoria

Kanoniczny punkt odniesienia od tego momentu jest tutaj:

- [PJ360-CATEGORY-TARGET-MATRIX.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-CATEGORY-TARGET-MATRIX.md)

Ten dokument jest operacyjnym "freeze" aktualnych targetow PJ360 dla tematow, ktore aktywnie stroimy.

## Aktualny stan po iteracjach klasyfikatora

Na dzis mamy juz trzy warstwy pracy:

1. audit porownawczy PJ360 vs nasze `/nauka`
2. audit-only reclassifier, ktory niczego nie zapisuje do bazy
3. kilka iteracji samego klasyfikatora, ktore poprawily najwieksze systemowe odchylenia

Najwazniejsze zmiany, ktore juz zaszly:

- `generic_intersection` nie pompuje juz tak agresywnie tematu pierwszenstwa
- czesc falszywych trafien ze `sygnalizowac` zostala odcieta od `traffic_lights`
- `road_markings` dostaly lepsze rozpoznawanie `P-*`, linii i strzalek
- `vehicle_load_and_passenger_safety` dostalo lepsze rozpoznawanie:
  - `DMC`
  - zespolu pojazdow
  - liczby miejsc
  - przewozu dziecka
  - dokumentow ograniczajacych liczbe osob

Najwazniejszy wniosek po tych iteracjach:

- sam klasyfikator jako baseline jest potrzebny
- ale sam klasyfikator nie bedzie najbezpieczniejszym narzedziem do dojscia `1:1` do PJ360 dla wszystkich kategorii

Powod:

- globalne reguly potrafia poprawic `B`, a jednoczesnie psuc `AM`, `C1` albo `T`
- w kilku tematach potrzebujemy precyzji na poziomie konkretnych pytan i konkretnych kategorii

## Realne targety per kategoria

Realne targety PJ360 per kategoria i per temat pilotowy sa zapisane w:

- [PJ360-CATEGORY-TARGET-MATRIX.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-CATEGORY-TARGET-MATRIX.md)

To jest teraz kanoniczny dokument operacyjny do strojenia.

Na obecnym etapie aktywnie sledzimy zwlaszcza:

- `road_markings`
- `lane_change_and_turning`
- `vehicle_load_and_passenger_safety`
- `safety_equipment_and_restraints`
- `owner_obligations_insurance_documents`

Najwazniejsze przekrojowe wnioski z macierzy:

- `road_markings` sa nadal zanizone prawie wszedzie
- `lane_change_and_turning` sa nadal zanizone prawie wszedzie
- `vehicle_load_and_passenger_safety` jest zanizony prawie wszedzie, zwlaszcza w `B`, `D1`, `D`
- `safety_equipment_and_restraints` jest juz dobrze trafione w `B`, ale nadal zbyt nisko w `C1`, `C`, `T`
- `owner_obligations_insurance_documents` ma rozjazd mieszany:
  - za wysoko w `B` i `B1`
  - idealnie w `D`
  - za nisko w pozostalych kategoriach

## Najbezpieczniejszy model dojscia do PJ360

Najbezpieczniejszy i najbardziej spojny model dla tego projektu to:

- `classifier as baseline`
- plus `audited overrides`

Nie wybieramy:

- slepego, masowego przepisywania `question_topic_id` recznie
- ani nieskonczonego przepychania wszystkich rozjazdow coraz bardziej agresywnym classifierem

### Dlaczego to jest najlepsze rozwiazanie

1. Klasyfikator pozostaje kanonicznym baseline dla:
   - nowego importu
   - nowych pytan
   - reclassify po zmianach regul
2. Override pozwala precyzyjnie dopiac:
   - konkretne pytanie
   - w konkretnej kategorii
   - do konkretnego tematu PJ360
3. Override jest stabilniejszy od recznej edycji po `question_id`, jesli zrobimy go po:
   - `source`
   - `external_id`
   - `license_category_code`
4. To podejscie daje nam:
   - powtarzalnosc po imporcie
   - mozliwosc audytu
   - latwe porownanie `classifier vs override`
   - bezpieczny rollback

### Docelowa zasada

Efektywny temat pytania powinien byc wyznaczany w tej kolejnosci:

1. `audited override`
2. `classifier result`
3. fallback tylko tam, gdzie nie mamy nic lepszego

To pozwala nam:

- zachowac obecna architekture topicow
- dopinac zgodnosc z PJ360 tam, gdzie classifier jest za szeroki lub za waski
- nie niszczyc spojnosc po kolejnych importach danych

## Otwarte luki do domkniecia

Zanim uznamy temat za domkniety, musimy jeszcze rozwiazac:

1. `T` nadal nie ma dwoch tematow specjalistycznych:
   - `speed_limits`
   - `safety_equipment_and_restraints`
2. Nie mamy jeszcze kompletnej, reviewowalnej tabeli `question -> target topic` dla wszystkich fal domykania kategorii.
   Mamy juz:
   - audit-only reclassifier
   - review-only eksport kandydatow
   - spec-driven package builder
   Ale nie mamy jeszcze zamknietej, pelnej paczki fal rolloutowych dla calego `B`.
3. Nie mamy jeszcze kontrolowanego rolloutu:
   - snapshot before
   - apply overrides
   - snapshot after
   - diff

## Lista zadan

### Zadanie 1. Domknac dokumentacje operacyjna

- zaktualizowac ten raport o model `classifier + audited overrides`
- utrzymywac [PJ360-CATEGORY-TARGET-MATRIX.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-CATEGORY-TARGET-MATRIX.md) jako freeze targetow
- przy kazdej iteracji zapisywac najnowszy stan delt dla tematow pilotowych

### Zadanie 2. Dodac warstwe audited overrides

- status: `DONE`
- stworzona tabela override po stabilnym kluczu:
  - `source`
  - `external_id`
  - `license_category_code`
- przechowujemy w niej:
  - docelowy `question_topic_key`
  - `reason`
  - opcjonalne `metadata`
- jest unikalnosc i indeksy pod powtarzalny import

### Zadanie 3. Wpiac override w flow przypisywania tematow

- status: `DONE`
- `QuestionTopicAssigner` najpierw sprawdza override
- dopiero potem spada do classifiera
- wynik nadal zapisuje `questions.question_topic_id`, zeby nie rozwalic:
  - `/nauka`
  - statystyk
  - filtrow sesji
  - agregacji dziennych i miesiecznych

### Zadanie 4. Dodac narzedzia audytowe do rolloutu override

- status: `DONE`
- audit-only raport ma pokazywac roznice:
  - `current`
  - `classifier`
  - `override`
  - `effective`
- to juz dziala w raporcie audit-only
- review-only eksport kandydatow override ma domyslnie pomijac `fallback:*`
- prosty raport `before / after` jest juz dostepny przez `content:report-question-topic-assignment-delta`
- builder review-only paczek override ze specyfikacji jest juz dostepny przez `content:build-question-topic-override-package`
- builder review-only paczek override ze specyfikacji obsluguje juz:
  - `current_topic_keys`
  - `classifier_matched_by`
  - `prompt_contains_any`
  - `prompt_not_contains_any`
  - `media_contains_any`
  - `media_not_contains_any`
  - `external_ids`
- dla `B` zwykle `current != classifier` przestalo juz wystarczac, wiec kolejne fale powinny isc juz przez spec-driven package

### Pierwszy curated batch dla `B`

Pierwszy high-confidence batch dla kategorii `B` zostal juz wygenerowany i zapisany operacyjnie tutaj:

- [PJ360-B-SAFE-OVERRIDE-PACKAGE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-SAFE-OVERRIDE-PACKAGE.md)

Ten batch:

- bierze tylko allowliste `matched_by`
- bierze tylko allowliste tematow
- aktywnie odcina `fallback:*`
- jest przygotowany jako pierwszy bezpieczny rollout override dla `B`

### Co juz nie wystarcza dla `B`

Po pierwszym przebiegu override dla `B` doszlismy do waznej granicy operacyjnej:

- lokalny stan `B` jest juz zgodny z obecnym `classifier baseline`
- przez to zwykly eksport `current != classifier` nie pokazuje juz dalszych kandydatow dla kolejnych ruchow

To oznacza, ze dalszy rollout do targetow PJ360 nie moze opierac sie tylko o:

- `current_topic_key`
- `classifier_topic_key`

Potrzebujemy kolejnej warstwy:

- review-only paczki override budowanej ze specyfikacji
- opartej o jawne reguly:
  - `target_topic_key`
  - `current_topic_keys`
  - `classifier_matched_by`
  - `reason`

To jest bezpieczniejsze niz dalsze "dopychanie" wszystkiego klasyfikatorem, bo pozwala:

- dopinac konkretne przypadki PJ360 kategoria po kategorii
- zostawic classifier jako stabilny baseline
- zachowac review i rollback na poziomie pojedynczych paczek

### Zadanie 5. Dopiac pierwsza fale tematow pilotowych

- `road_markings`
- `lane_change_and_turning`
- `vehicle_load_and_passenger_safety`
- `safety_equipment_and_restraints`
- `owner_obligations_insurance_documents`

Dla tych tematow:

- classifier dalej poprawiamy tylko tam, gdzie daje to zysk przekrojowy
- przypadki kategorio-zalezne i graniczne przechodza do override

### Zadanie 6. Osobno rozwiazac kategorie `T`

- ustalic, czy brak 2 tematow to:
  - problem klasyfikacji
  - problem importu
  - problem polityki mapowania kategorii
- nie przykrywac tego samym override, jesli brakuje realnych danych

### Zadanie 7. Dodac testy bezpieczenstwa rolloutu

- test assignera z override
- test assignera bez override
- test stabilnosci po ponownym imporcie
- test audit-only raportu z widokiem `classifier vs override`
- test, ze override w jednej kategorii nie przecieka do innej

## Kolejny krok implementacyjny

Od tego miejsca dalszy development powinien isc tak:

1. dodac builder review-only paczek override ze specyfikacji
2. utrzymywac spec per kategoria i fala rolloutowa
3. budowac paczki override z tej specyfikacji
4. policzyc snapshot `before / after` po zastosowaniu override
5. dopiero potem kontynuowac dalszy tuning classifiera tam, gdzie daje zysk przekrojowy

Ta kolejnosc jest najbezpieczniejsza, bo najpierw buduje mechanizm precyzyjnego strojenia, a dopiero potem wykorzystuje go do dojscia do zgodnosci z PJ360.
