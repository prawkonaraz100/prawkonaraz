# PJ360 Category Consistency Audit Plan

## Cel

Celem tego audytu jest porownanie tego, co widzi uzytkownik w:

- `https://www.prawo-jazdy-360.pl/kurs`
- `http://localhost:8000/nauka`

dla kazdej kategorii prawa jazdy, ktora obslugujemy w produkcie:

- `AM`
- `A1`
- `A2`
- `A`
- `B1`
- `B`
- `C1`
- `C`
- `D1`
- `D`
- `T`

Chcemy odpowiedziec na 4 pytania:

1. Czy nazwy dzialow/tematow sa takie same po obu stronach.
2. Czy liczba pytan w konkretnych dzialach jest taka sama.
3. Jesli nie jest taka sama, to czy rozjazd wynika z:
   - roznicy etykiet,
   - roznicy polityki kategorii,
   - roznicy naszego przypisania `question_topic_id`,
   - roznicy filtrow widocznosci pytan,
   - czy z rzeczywistego braku / nadmiaru pytan.
4. Ktore rozjazdy sa tylko prezentacyjne, a ktore wymagaja zmiany danych lub klasyfikacji.

## Co juz wiemy

### Nasza strona `/nauka`

Widok `/nauka` nie pokazuje "surowych" rekordow z bazy. Liczby dzialow sa budowane przez:

- [SessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/SessionPageController.php)
- [StudyTopicGroupsService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudyTopicGroupsService.php)
- [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php)

To oznacza, ze po naszej stronie liczymy tylko pytania, ktore sa:

- przypisane do wybranej kategorii `license_category_id`
- `is_active = true`
- `readyForDelivery()`
- maja ustawione `question_topic_id`

Nazwy dzialow, ktore widzi uzytkownik, nie sa po prostu nazwami z tabeli `question_topics`, tylko sa mapowane przez:

- `QuestionTopicClassifier::displayLabelForKey()`
- `QuestionTopicClassifier::bucketLabelForKey()`

Wniosek:
- nie mozemy porownywac PJ360 do "wszystkich pytan w bazie"
- musimy porownywac PJ360 do dokladnie tego samego zestawu, ktory idzie do `/nauka`

### PJ360

Na PJ360 w sekcji `Twoje postepy` widzimy pozycje w formacie:

- `x / y`

W tym audycie za kanoniczna liczbe pytan przyjmujemy:

- `y` = laczna liczba pytan w dziale

Wartosc `x` jest zalezna od postepu konkretnego konta i nie sluzy do porownania z naszym licznikiem pytan.

### Istniejace artefakty w repo

Mamy juz w repo poprzedni audit i pakiety porownawcze PJ360:

- [UI-AUDIT-PRAWO-JAZDY-360.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-AUDIT-PRAWO-JAZDY-360.md)
- [PJ360-EXPLANATION-ADAPTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXPLANATION-ADAPTATION-PLAN.md)
- [PJ360-EXPLANATION-ADAPTATION-RUNBOOK.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXPLANATION-ADAPTATION-RUNBOOK.md)
- [comparison_report.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-compare/comparison_report.json)
- [by-category-summary.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-compare/review-packets/by-category-summary.json)

To jest bardzo pomocne, ale tamten zestaw porownywal glownie pytania i dopasowanie promptow. Ten audit jest inny:

- porownujemy poziom `kategoria -> dzial -> liczba pytan`
- a dopiero potem schodzimy do pytan, jesli liczby sie nie zgadzaja

## Zakres audytu

### W scope

- porownanie wszystkich kategorii od `AM` do `T`, ktore obslugujemy
- porownanie bucketow:
  - `Pytania podstawowe`
  - `Pytania specjalistyczne`
- porownanie nazw dzialow
- porownanie liczby pytan per dzial
- wyjasnienie rozjazdow
- wskazanie, czy problem lezy po stronie:
  - danych,
  - klasyfikacji tematow,
  - mapowania kategorii,
  - czy tylko warstwy prezentacyjnej

### Poza scope

- przepisywanie pytan lub wyjasnien
- poprawianie odpowiedzi
- adaptacja explanation stacku
- zmiany UI PJ360
- rozliczanie postepu uzytkownika `x / y`

## Ryzyka i pulapki

### 1. Rozne etykiety tego samego dzialu

Przyklad:

- PJ360 moze pokazywac: `Znaki informacyjne, kierunku i miejscowosci, uzupelniajace`
- my mozemy pokazywac krotszy label: `Znaki informacyjne i tabliczki`

To nie jest od razu blad liczbowy. Najpierw trzeba ustalic mapowanie nazw.

### 2. Rozne grupowanie tego samego materialu

PJ360 moze laczyc lub rozbijac dzialy inaczej niz nasz `QuestionTopicClassifier`.

To oznacza, ze rozjazd typu:

- `45 vs 52`

moze wynikac nie z brakujacych pytan, ale z innej granicy dzialu.

### 3. Nasze filtry delivery

Na naszej stronie liczby sa filtrowane przez:

- `is_active`
- `readyForDelivery()`
- `question_topic_id`

Wiec jesli pytanie jest w bazie, ale nie jest gotowe do dostarczenia, nie pojawi sie w `/nauka`.

### 4. Wspolne pytania miedzy kategoriami

To samo pytanie moze wystepowac w kilku kategoriach. Przy audycie pytan per dzial nie porownujemy unikalnych promptow globalnie, tylko:

- rekordy widoczne dla konkretnej kategorii

### 5. Konto PJ360 ma stan postepu

Poniewaz konto nie jest czyste, trzeba ignorowac licznik wykonania `x` i porownywac tylko:

- nazwe dzialu
- bucket
- wartosc `y`

## Docelowy wynik audytu

Po audycie chcemy miec:

1. Jedna tabele per kategoria:
   - bucket
   - label PJ360
   - label u nas
   - count PJ360
   - count u nas
   - delta
   - status
   - notatka o przyczynie

2. Jedna tabele zbiorcza:
   - kategorie z pelna zgodnoscia
   - kategorie z rozjazdami tylko w nazewnictwie
   - kategorie z rozjazdami liczbowymi
   - kategorie wymagajace recznego zejscia do poziomu pytan

3. Paczke dowodowa:
   - screenshoty PJ360 per kategoria
   - snapshot naszych danych per kategoria
   - eksport porownawczy do JSON/CSV

## Plan wykonania

### Etap 1. Zbudowac lokalny baseline

Najpierw generujemy kanoniczny snapshot tego, co pokazuje nasze `/nauka`, dla kazdej kategorii.

Snapshot musi zawierac:

- `category_code`
- `bucket_label`
- `topic_key`
- `topic_label`
- `questions_count`
- opcjonalnie `counts.unanswered / incorrect / correct / memorized`

Zrodlem prawdy ma byc:

- [StudyTopicGroupsService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudyTopicGroupsService.php)

a nie reczne zapytania SQL bezposrednio do `questions`.

Dlaczego:
- tylko wtedy porownujemy to, co naprawde widzi uzytkownik na `/nauka`

Artefakty:

- `output/analysis/pj360-category-consistency/local-topic-groups.json`
- `output/analysis/pj360-category-consistency/local-topic-groups.csv`

### Etap 2. Zalogowac sie do PJ360 i zebrac topic counts

Nastepnie logujemy sie na:

- [https://www.prawo-jazdy-360.pl/logowanie](https://www.prawo-jazdy-360.pl/logowanie)

i przechodzimy do:

- [https://www.prawo-jazdy-360.pl/kurs](https://www.prawo-jazdy-360.pl/kurs)

Dla kazdej kategorii:

- `AM`
- `A1`
- `A2`
- `A`
- `B1`
- `B`
- `C1`
- `C`
- `D1`
- `D`
- `T`

zbieramy:

- bucket `Pytania podstawowe / Pytania specjalistyczne`
- label dzialu
- `x / y`
- do porownania zapisujemy tylko `y`

Dodatkowo dla kazdej kategorii zapisujemy screenshot sekcji `Twoje postepy`.

Artefakty:

- `output/analysis/pj360-category-consistency/pj360-topic-groups.json`
- `output/analysis/pj360-category-consistency/pj360-topic-groups.csv`
- `output/analysis/pj360-category-consistency/screenshots/<CATEGORY>.png`

### Etap 3. Zmapowac etykiety PJ360 na nasze topic keys

To jest najwazniejszy etap logiczny.

Tworzymy tabelę mapowania:

- `pj360_bucket`
- `pj360_label`
- `local_topic_key`
- `local_bucket`
- `local_display_label`
- `mapping_status`

Status mapowania:

- `exact_label_match`
- `same_topic_different_label`
- `split_or_merge_needed`
- `unknown`

Na tym etapie jeszcze nie rozstrzygamy brakow pytan. Najpierw ustalamy:

- czy to sa w ogole te same dzialy

Artefakt:

- `output/analysis/pj360-category-consistency/topic-label-mapping.csv`

### Etap 4. Policzyc delty na poziomie dzialow

Po mapowaniu porownujemy:

- `pj360_count_total`
- `local_questions_count`

Klasy rozjazdu:

- `exact_match`
- `label_only_mismatch`
- `count_mismatch`
- `missing_on_local`
- `missing_on_pj360`
- `cannot_map_topic`

Artefakty:

- `output/analysis/pj360-category-consistency/topic-delta-report.json`
- `output/analysis/pj360-category-consistency/topic-delta-report.csv`

### Etap 5. Zejsc do pytan tylko tam, gdzie sa rozjazdy

Dopiero jesli jakis dzial sie nie zgadza, schodzimy do poziomu pytan.

Tutaj wykorzystujemy juz istniejace artefakty z:

- [output/analysis/pj360-compare](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-compare)

oraz pytania widoczne po kliknieciu `Pokaż pytania` w PJ360.

Cele tego etapu:

- sprawdzic, czy PJ360 ma pytania, ktorych my nie mamy
- sprawdzic, czy my mamy pytania w innym dziale
- sprawdzic, czy rozjazd wynika z innego przypisania kategorii
- sprawdzic, czy pytanie istnieje u nas, ale nie ma `question_topic_id`
- sprawdzic, czy pytanie istnieje u nas, ale nie przechodzi `readyForDelivery()`

### Etap 6. Zapisac finalna dokumentacje

Finalny dokument powinien powstac jako osobny raport, np.:

- `docs/PJ360-CATEGORY-CONSISTENCY-AUDIT.md`

z sekcjami:

- `Cel`
- `Metodologia`
- `Zrodla danych`
- `Mapowanie dzialow`
- `Per-category comparison`
- `Zidentyfikowane rozjazdy`
- `Przyczyny rozjazdow`
- `Rekomendowane poprawki`
- `Otwarte pytania`

## Co dokladnie chcemy sprawdzic per kategoria

Dla kazdej kategorii chcemy miec odpowiedz:

1. Czy PJ360 i my pokazujemy ten sam zestaw dzialow.
2. Czy bucket `podstawowe / specjalistyczne` jest zgodny.
3. Czy count per dzial jest zgodny.
4. Jesli nie:
   - czy rozjazd wynika z innej nazwy
   - czy z innej granicy dzialu
   - czy z brakujacych pytan
   - czy z blednego przypisania pytan do kategorii
   - czy z filtrow widocznosci po naszej stronie

## Jak rozpoznamy, ze audit jest zakonczony

Audit uznajemy za domkniety, gdy:

- dla wszystkich kategorii od `AM` do `T` mamy screenshot PJ360
- dla wszystkich kategorii mamy snapshot naszych topic groups
- kazdy dzial PJ360 jest:
  - zmapowany do naszego topic key
  - albo oznaczony jako `cannot_map_topic`
- kazdy rozjazd liczbowy ma przypisana hipoteze przyczyny
- mamy finalny raport w `docs/`

## Rekomendowana kolejnosc wykonania

1. Zrobic eksport lokalnych topic groups.
2. Zrobic pass Playwrightem po PJ360 i zapisac screenshoty + counts.
3. Zrobic tabele mapowania etykiet.
4. Policzyc delty.
5. Zejsc tylko w rozjazdy.
6. Zapisac finalny raport.

## Dlaczego to podejscie jest bezpieczne

To podejscie nie miesza trzech roznych problemow:

- `czy mamy te same pytania`
- `czy mamy te same dzialy`
- `czy pokazujemy te same liczby`

Najpierw ustalamy warstwe prezentacyjna i liczniki, a dopiero potem schodzimy do poziomu rekordow pytan. Dzięki temu:

- nie przepalamy czasu na review pytan tam, gdzie problemem jest tylko nazwa dzialu,
- nie wyciagamy falszywych wnioskow z samego `x / y`,
- i mozemy jasno odroznic problem danych od problemu klasyfikatora.
