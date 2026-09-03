# Plan ujednolicenia wyszukiwarki publicznej bazy pytan

Data: 2026-07-02  
Branch roboczy: `codex/public-question-search-plan`

## Cel

Uzytkownik ma miec mozliwosc znalezc pytanie po tresci, np.

`Czy masz obowiazek zatrzymac swoj pojazd przed ostatnim wagonem tramwaju?`

niezaleznie od tego, czy wpisuje je w globalnej wyszukiwarce w naglowku, czy na stronie konkretnej kategorii.

Wyszukiwanie ma respektowac fakt, ze jedno pytanie moze byc wspoldzielone przez wiele kategorii. Taki przypadek jest normalny i nie powinien skutkowac lista 11 identycznych wynikow.

## Co ustalilismy

### Dane sa poprawne

Na aktualnym lokalnym Postgresie uzywanym przez aplikacje Docker pytanie istnieje jako `external_id = 3540` w 11 oficjalnych kategoriach:

- `A`
- `A1`
- `A2`
- `AM`
- `B`
- `B1`
- `C`
- `C1`
- `D`
- `D1`
- `T`

Rekordy sa aktywne i nie maja `delivery_issue`, wiec przechodza publiczny scope.

Uwaga: lokalny plik `database/database.sqlite` jest starszym snapshotem i pokazuje to samo pytanie jako `zdamyto:3037` tylko w czesci kategorii. Dla tej diagnozy wazniejszy jest Postgres z dzialajacego stacka, bo `.env` wskazuje `DB_CONNECTION=pgsql`, a kontener `serwistestyprawojazdy-postgres-1` jest aktywny.

### Obecne zachowanie globalnej wyszukiwarki

Globalny formularz w naglowku wysyla `GET /oficjalna-baza-pytan-na-prawo-jazdy?question=...`.

Miejsca:

- `resources/js/Components/SiteHeader.vue`
- `resources/views/components/site/public-header.blade.php`

Kontroler huba odczytuje tylko parametr `question` i traktuje go jako identyfikator pytania:

- `app/Http/Controllers/PublicQuestionDatabaseController.php`
  - `index()`
  - `findCanonicalUrlByExternalId($searchQuery)`

Skutek:

- `?question=3540` dziala i przekierowuje na strone pytania.
- `?question=pelna tresc pytania` nie szuka po tresci. Kod probuje znalezc `external_id` rowny calej frazie.
- Widok huba dostaje `searchNotFound`, ale `resources/views/questions-database/index.blade.php` go nie renderuje, wiec uzytkownik wraca na zwykla strone kategorii bez czytelnego komunikatu.

### Obecne zachowanie wyszukiwarki kategorii

Na stronie kategorii formularz wysyla `q`, np.

`/oficjalna-baza-pytan-na-prawo-jazdy/a?q=ostatnim%20wagonem`

Miejsca:

- `resources/views/questions-database/category.blade.php`
- `app/Http/Controllers/PublicQuestionDatabaseController.php`
  - `categoryFilters()`
  - `filterQuestionItems()`

Ta wyszukiwarka dziala tekstowo, ale tylko w ramach jednej kategorii. Filtr jest prosty: `str_contains()` na `prompt_plain`, `external_id` i `display_external_id`.

Skutek:

- pelna fraza pytania znajduje wynik na stronie kategorii,
- globalna wyszukiwarka nie znajduje tej samej frazy,
- mamy dwa rozne mechanizmy wyszukiwania z roznymi parametrami i innym zakresem.

## Dodatkowy przebieg kontrolny

Po pierwszej diagnozie sprawdzilismy jeszcze miejsca, ktore moga latwo spowodowac regresje przy implementacji:

- `database/factories/QuestionFactory.php`
- `database/factories/LicenseCategoryFactory.php`
- testy w `tests/Feature/PublicQuestionDatabasePageTest.php`
- obecna normalizacja wyszukiwania w adminie:
  - `app/Filament/Resources/Questions/Tables/QuestionsTable.php`
- logike ID, display ID i URL-i pomocniczych w:
  - `app/Support/PublicQuestionCatalogService.php`
- schema.org `SearchAction` w:
  - `app/Support/PublicQuestionSchemaService.php`

Wnioski:

- Istniejace testy juz pokrywaja wazne zachowania, ktorych nie wolno zepsuc:
  - filtr kategorii po `gov_id`,
  - pytanie wspoldzielone przez wiele kategorii,
  - link z listy kategorii zachowuje kontekst kategorii,
  - kolizje typu `3540` oraz `pj360:3540`.
- Grupowanie globalnych wynikow musi byc po surowym `external_id`, a nie po `display_external_id`.
  - `3540` i `pj360:3540` moga miec ten sam numer wyswietlany, ale oznaczac dwa rozne pytania.
  - wspoldzielone pytanie to wiele rekordow z tym samym surowym `external_id` w roznych kategoriach.
- Lookup po ID powinien zostac oparty o istniejace `findCanonicalUrlByExternalId()`, bo ta metoda zna obecne reguly:
  - surowe ID,
  - display ID z prefiksem,
  - neutralne URL-e pomocnicze typu `pytanie-pomocnicze-3540`.
- Testy aplikacji ida na SQLite, wiec implementacja nie powinna opierac sie na Postgres-only funkcjach typu `unaccent` albo `to_tsvector`, jesli nie dodamy dla nich alternatywy testowej.
- Admin ma precedens normalizacji promptu ignorujacej znaczniki inline, ale publiczny search powinien miec wlasna wspolna normalizacje, bo musi obslugiwac takze polskie znaki i tryb globalny/kategorii.

## Decyzja kierunkowa

Ujednolicamy logike wyszukiwania.

Nie laczymy koniecznie calego UI w jedno miejsce, ale oba wejscia maja korzystac z tego samego mechanizmu:

- globalna wyszukiwarka: wyszukiwanie po calej publicznej bazie,
- wyszukiwarka kategorii: ten sam search, ale z filtrem kategorii.

Parametr docelowy powinien byc `q`.

Dla kompatybilnosci zostawiamy `question` jako alias:

- istniejace formularze i schema.org moga jeszcze dzialac,
- docelowo header moze wysylac `q`,
- `question=3540` nadal powinno dzialac jako lookup po numerze.

## Proponowana architektura

### 1. Nowa wspolna usluga

Dodac serwis, np.

`app/Support/PublicQuestionSearchService.php`

Odpowiedzialnosci:

- przyjmowanie frazy i opcjonalnego filtra kategorii,
- normalizacja zapytania,
- rozpoznanie, czy wpis wyglada jak ID pytania,
- wyszukiwanie po publicznym scope,
- grupowanie pytan wspoldzielonych,
- zwracanie wynikow gotowych dla huba i strony kategorii.

Serwis powinien korzystac z istniejacych regul publicznej widocznosci z `PublicQuestionCatalogService`, zeby nie powstaly dwa rozne scope'y publicznej bazy.

Decyzja techniczna po drugim przebiegu:

- nie kopiowac `PublicQuestionCatalogService::baseQuery()` do nowego serwisu,
- zamiast tego wyprowadzic bezpieczny publiczny punkt dostepu w `PublicQuestionCatalogService`, np. `publicQuestionsQuery()`,
- obecne metody katalogu powinny korzystac z tego samego punktu dostepu, zeby publiczny scope byl jeden.

### 2. Wspolna normalizacja

MVP:

- `trim`
- `Str::squish`
- male litery
- usuniecie albo zneutralizowanie znacznikow inline uzywanych w promptach, np. `[green]`, `[red]`, `**`
- ignorowanie nadmiarowych spacji
- ignorowanie interpunkcji, w tym koncowego `?`

Nastepny krok:

- wyszukiwanie bez polskich znakow, np. `obowiazek zatrzymac swoj pojazd`,
- tokenizacja slow, zeby wpis `ostatni wagon tramwaj` nadal mial sensowne wyniki,
- ranking: dokladne trafienie > fraza w tresci > dopasowanie po slowach > ID.

Rekomendacja implementacyjna:

- normalizacje robic w PHP w jednym miejscu, z uzyciem `QuestionTextFormatter::plainText()` dla promptow,
- ewentualne SQL `LIKE` traktowac tylko jako prefiltr wydajnosciowy,
- finalne dopasowanie powinno przechodzic przez te sama normalizacje niezaleznie od bazy danych.

### 3. Grupowanie wynikow

Wyniki globalne nie powinny pokazywac duplikatow po kategoriach.

Grupowanie:

- podstawowo po surowym `external_id`,
- z zachowaniem listy kategorii, w ktorych pytanie wystepuje,
- reprezentant wyniku powinien uzywac obecnych zasad z `PublicQuestionCatalogService::representativeQuestion()`.

Przyklad wyniku:

- `#3540`
- prompt pytania,
- miniatura,
- typ pytania,
- kategorie: `A, A1, A2, AM, B, B1, C, C1, D, D1, T`,
- link do kanonicznej strony pytania.

### 4. Zachowanie redirectow

Zachowac obecna szybka sciezke dla ID:

- `?question=3540`
- `?q=3540`

powinno przekierowac na kanoniczna strone pytania, jesli ID istnieje.

Dla tekstu:

- dokladne trafienie jednej unikalnej grupy moze przekierowac od razu na pytanie,
- czesciowe trafienie powinno pokazac liste wynikow,
- brak wyniku powinien pokazac jawny empty state.

Otwarte pytanie produktowe:

- czy pelna tresc pytania ma zawsze przekierowywac od razu, czy pokazac wynik z kategoriami?

Rekomendacja: dla dokladnego dopasowania do jednej grupy surowego `external_id` robic redirect. Dla czesciowych fraz pokazac liste. Jesli ta sama pelna tresc dopasuje wiecej niz jeden surowy `external_id`, nie robic automatycznego redirectu, tylko pokazac liste, zeby nie wybrac zlego pytania.

### 5. Widok huba z wynikami

`resources/views/questions-database/index.blade.php` musi dostac sekcje wynikow wyszukiwania.

Stany:

- brak zapytania: obecny widok kategorii,
- zapytanie z wynikami: lista wynikow ponad kategoriami albo zamiast sekcji kategorii,
- zapytanie bez wynikow: jasny komunikat i link do wyczyszczenia.

Nie wystarczy przekazywac `searchNotFound`; widok musi go renderowac.

### 6. Strona kategorii po zmianie

`category()` powinno uzywac tego samego serwisu z filtrem kategorii.

Obecny `filterQuestionItems()` mozna wtedy usunac albo zostawic tylko tymczasowo na czas migracji.

Docelowo nie chcemy dwoch algorytmow:

- globalny search po `question`,
- lokalny filter po `q`.

Ma byc jeden search:

- hub: bez kategorii,
- category: z kategoria.

Kompatybilnosc:

- `gov_id` na stronie kategorii trzeba zachowac jako dokladny filtr/alias dla ID, bo istnieje test i prawdopodobnie linki wejscia.
- `q=100` powinno nadal znajdowac `pj360:100` przez `display_external_id`, tak jak dzis.

### 7. Schema.org

Obecnie `PublicQuestionSchemaService` deklaruje:

`/oficjalna-baza-pytan-na-prawo-jazdy?question={search_term_string}`

Po ujednoliceniu trzeba zdecydowac:

- zostawic `question` dla kompatybilnosci,
- albo zmienic target na `?q={search_term_string}` i nadal obslugiwac `question` jako alias.

Rekomendacja: zmienic docelowo na `q`, ale dopiero razem z testami i aliasem dla `question`.

## Plan prac

### Etap 1: testy opisujace obecny problem

Dopisac testy w `tests/Feature/PublicQuestionDatabasePageTest.php`:

- globalny search po `q` znajduje pytanie po pelnej frazie,
- globalny search po `question` nadal dziala jako alias,
- `q=3540` i `question=3540` przekierowuja na pytanie,
- pytanie wspoldzielone przez wiele kategorii pojawia sie jako jeden wynik globalny z lista kategorii,
- strona kategorii uzywa tej samej logiki i pokazuje wynik tylko w ramach danej kategorii,
- brak wynikow renderuje empty state.
- `3540` i `pj360:3540` pozostaja osobnymi wynikami, jesli oba pasuja tekstowo albo po display ID,
- `gov_id` na stronie kategorii nadal dziala,
- wyszukiwanie ignoruje formatowanie inline w promptach.

### Etap 2: serwis wyszukiwania

Utworzyc `PublicQuestionSearchService`.

Minimalny kontrakt:

- `search(string $query, ?LicenseCategory $category = null): PublicQuestionSearchResult`
- `canonicalRedirectFor(string $query): ?string`
- normalizacja query w jednym miejscu.

Wynik powinien zawierac:

- oryginalna fraze,
- znormalizowana fraze,
- kolekcje pogrupowanych itemow,
- flage `isExactSingleMatch`,
- ewentualny `redirectUrl`.

Zanim powstanie serwis:

- dodac lub wydzielic publiczny query builder publicznej bazy w `PublicQuestionCatalogService`,
- nie zmieniac zachowania `questionGroupByExternalOrDisplayId()` bez testow kolizji ID,
- zachowac budowanie URL-i przez `questionUrl()` i `publicExternalIdForQuestion()`.

### Etap 3: integracja huba

Zmienic `PublicQuestionDatabaseController::index()`:

- czytac `q` i `question`,
- najpierw zachowac lookup po ID,
- potem wykonac search tekstowy,
- przekazac wyniki do `questions-database.index`.

Zmienic `index.blade.php`:

- pokazac wyniki,
- pokazac empty state,
- zachowac obecna liste kategorii dla braku zapytania.

### Etap 4: integracja kategorii

Zmienic `category()`:

- uzyc wspolnego serwisu z filtrem kategorii,
- zachowac paginacje,
- zachowac wyglad obecnej listy.

Usunac lub ograniczyc `filterQuestionItems()`, jesli stanie sie martwe.

### Etap 5: header i schema

Zmienic formularze headera:

- `name="q"` jako docelowy parametr,
- opcjonalnie zostawic kompatybilnosc kontrolera z `question`.

Zmienic schema.org target w `PublicQuestionSchemaService`, jesli decydujemy sie na `q`.

Zaktualizowac testy oczekujace `?question={search_term_string}`.

### Etap 6: weryfikacja

Sprawdzic recznie:

- `/oficjalna-baza-pytan-na-prawo-jazdy?q=3540`
- `/oficjalna-baza-pytan-na-prawo-jazdy?q=Czy%20masz%20obowiazek%20zatrzymac%20swoj%20pojazd%20przed%20ostatnim%20wagonem%20tramwaju`
- `/oficjalna-baza-pytan-na-prawo-jazdy?q=ostatnim%20wagonem`
- `/oficjalna-baza-pytan-na-prawo-jazdy/a?q=ostatnim%20wagonem`
- brak wynikow, np. losowa fraza.

Sprawdzic testami:

- publiczna baza pytan,
- schema graph,
- routing pytan z prefiksem typu `pj360:3540`.

## Ryzyka

- `PublicQuestionCatalogService::baseQuery()` jest prywatnym miejscem prawdy dla publicznego scope, ale metoda jest `protected`. Nowy serwis nie powinien kopiowac logiki w sposob, ktory latwo sie rozjedzie.
- Wyniki globalne moga byc kosztowniejsze niz filtr kategorii, bo obejmuja cala publiczna baze. MVP moze dzialac kolekcyjnie, ale jesli baza urosnie, trzeba rozwazyc indeksy albo dedykowana tabele/search index.
- ID z prefiksami, np. `pj360:3540`, maja specjalna logike publicznych URL-i. Nowy search musi respektowac `publicExternalIdForQuestion()` i istniejace neutralne URL-e pomocnicze.
- Wyszukiwanie bez polskich znakow jest wygodne, ale wymaga jasnej normalizacji po obu stronach porownania.
- Testy feature dzialaja na SQLite, a lokalna aplikacja na Postgresie. Trzeba unikac zachowania, ktore przejdzie tylko w jednym silniku.
- Automatyczny redirect po pelnej tresci jest wygodny, ale moze byc bledny, jesli dwa rozne pytania maja identyczny prompt. Redirect tylko przy jednym surowym `external_id`.

## Co zaimplementowano

Implementacja wykonana na branchu `codex/public-question-search-plan`.

Dodane / zmienione pliki:

- `app/Support/PublicQuestionSearchService.php`
  - wspolna normalizacja zapytan,
  - wyszukiwanie po publicznym scope,
  - scoring dopasowan,
  - grupowanie po surowym `external_id`,
  - bezpieczny redirect przy jednym dokladnym dopasowaniu promptu.
- `app/Support/PublicQuestionSearchResult.php`
  - prosty obiekt wyniku searcha.
- `app/Support/PublicQuestionCatalogService.php`
  - publiczny `publicQuestionsQuery()` jako jedno miejsce scope'u publicznych pytan,
  - publiczny wrapper `representativeQuestionForGroup()` dla wyboru reprezentanta grupy.
- `app/Http/Controllers/PublicQuestionDatabaseController.php`
  - hub czyta `q` i zachowuje `question` jako alias,
  - hub najpierw robi lookup po ID, potem search tekstowy,
  - kategoria uzywa tego samego searcha z filtrem kategorii i zachowanym `gov_id`.
- `resources/views/questions-database/index.blade.php`
  - sekcja wynikow globalnego wyszukiwania,
  - empty state,
  - paginacja wynikow,
  - chipy kategorii dla pytan wspoldzielonych.
- `resources/js/Components/SiteHeader.vue`
  - formularze wysylaja `q`.
- `resources/views/components/site/public-header.blade.php`
  - formularze wysylaja `q`.
- `app/Support/PublicQuestionSchemaService.php`
  - schema.org `SearchAction` wskazuje `?q={search_term_string}`.
- `tests/Feature/PublicQuestionDatabasePageTest.php`
  - testy globalnego searcha po tresci,
  - test starego aliasu `question`,
  - test grupowania pytan wspoldzielonych,
  - test kolizji `3540` / `pj360:3540`,
  - test kategorii bez polskich znakow,
  - test ignorowania znacznikow inline.

## Weryfikacja

Automatyczna:

`docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/PublicQuestionDatabasePageTest.php`

Wynik:

- 28 testow przeszlo,
- 601 asercji.

Reczna HTTP na lokalnym stacku Postgres:

- `/oficjalna-baza-pytan-na-prawo-jazdy?q=3540`
  - redirect 302 na `/pytanie/3540/czy-masz-obowiazek-zatrzymac-swoj-pojazd-przed-ostatnim-wagonem-tramwaju`
- `/oficjalna-baza-pytan-na-prawo-jazdy?q=pelna-fraza-pytania`
  - redirect 302 na to samo pytanie
- `/oficjalna-baza-pytan-na-prawo-jazdy?question=pelna-fraza-pytania`
  - stary alias nadal dziala i redirectuje
- `/oficjalna-baza-pytan-na-prawo-jazdy?q=ostatnim%20wagonem`
  - renderuje wyniki globalnego wyszukiwania i zawiera szukane pytanie
- `/oficjalna-baza-pytan-na-prawo-jazdy/a?q=obowiazek%20zatrzymac%20swoj%20pojazd%20przed%20ostatnim%20wagonem%20tramwaju`
  - znajduje pytanie w kategorii A bez polskich znakow
- `/oficjalna-baza-pytan-na-prawo-jazdy?q=fraza-ktorej-nie-ma-xyz123`
  - pokazuje empty state
- hub renderuje `name="q"` i schema target `?q={search_term_string}`

## Gdzie jestesmy teraz

Zrobione:

- zdiagnozowano rozjazd miedzy globalnym lookupiem po ID a lokalnym filtrem kategorii,
- potwierdzono na Postgresie, ze pytanie `3540` jest aktywne i publiczne,
- potwierdzono pierwotny problem HTTP:
  - `?question=3540` przekierowywalo na pytanie,
  - `?question=pelna fraza` nie znajdowalo,
  - `/a?q=pelna fraza` znajdowalo,
- utworzono branch `codex/public-question-search-plan`,
- zapisano niniejszy plan.
- wykonano dodatkowy przebieg kontrolny po factory, testach publicznej bazy pytan, logice prefiksow ID, schema.org i adminowej normalizacji promptow.
- zaimplementowano wspolny search dla huba i kategorii.
- zaktualizowano formularze headera i schema.org na parametr `q`.
- zachowano alias `question` dla kompatybilnosci.
- potwierdzono testami i recznie na lokalnym stacku, ze `q` i alias `question` dzialaja juz dla pelnej frazy.

Do zrobienia w tej iteracji:

- przejrzec finalny diff przed commit/PR.
- zdecydowac, czy uruchamiamy szerszy zestaw testow poza publiczna baza pytan.

Mozliwe usprawnienia pozniej:

- indeks / dedykowana tabela search, jesli globalne wyszukiwanie po calej bazie okaze sie za wolne.
- lepsze rankingowanie po tokenach dla bardzo ogolnych fraz.
- podswietlanie znalezionych fragmentow w wynikach.
- telemetryka zapytan bez wynikow.

## Proponowane usprawnienia

Kolejnosc rekomendowana:

1. Indeks / tabela search

   Obecny MVP dziala w PHP na publicznym scope. Funkcjonalnie jest bezpieczny, ale przy wiekszej liczbie pytan albo wiekszym ruchu warto dodac np. `question_search_documents`.

   Taka tabela moglaby trzymac:

   - surowy `external_id`,
   - display ID,
   - znormalizowany prompt,
   - kategorie,
   - URL kanoniczny,
   - date aktualizacji,
   - ewentualnie znormalizowane odpowiedzi albo wyjasnienia, jesli search ma pozniej obejmowac wiecej niz prompt.

   Korzysci:

   - szybszy globalny search,
   - latwiejsze sortowanie i ranking,
   - fundament pod autocomplete,
   - mniej ryzyka przy zapytaniach bardzo ogolnych.

2. Metryki wydajnosci i telemetryka zapytan

   Warto mierzyc:

   - czas wykonania wyszukiwania,
   - liczbe kandydatow,
   - liczbe wynikow,
   - zapytania bez wynikow,
   - najczestsze zapytania.

   Dane powinny byc anonimowe. Celem jest zobaczyc, czego realnie szukaja uzytkownicy i czy search robi sie za ciezki.

3. Lepszy ranking wynikow

   Obecny scoring jest prosty:

   - ID,
   - dokladny prompt,
   - fraza w prompcie,
   - wszystkie tokeny w prompcie.

   Docelowo mozna dodac wiecej wag:

   - dokladne ID najwyzej,
   - pelny prompt,
   - dopasowanie od poczatku promptu,
   - slowa blisko siebie,
   - wszystkie slowa w dowolnej kolejnosc,
   - pojedyncze wazne slowa nizej.

   To poprawi krotkie zapytania typu `tramwaj wagon`.

4. Podswietlanie trafionych fragmentow

   Wyniki powinny pokazywac, dlaczego pasuja, np. wyroznic fragment `ostatnim wagonem`.

   To jest glownie usprawnienie UX:

   - uzytkownik szybciej rozpoznaje trafny wynik,
   - latwiej odroznic podobne pytania,
   - wyniki sa bardziej wiarygodne.

5. Sugestie przy braku wynikow

   Empty state moze podpowiadac:

   - sprobuj krotszej frazy,
   - usun interpunkcje,
   - wpisz numer pytania,
   - sprawdz inna kategorie.

   W przyszlosci mozna dodac najblizsze wyniki fuzzy, ale dopiero po indeksie/rankingu.

6. Autocomplete / live search

   Po wpisaniu 2-3 slow mozna pokazac szybkie sugestie pytan.

   To warto robic dopiero po indeksie albo cache'u, zeby nie odpalac pelnego wyszukiwania po calej bazie przy kazdym klawiszu.

7. Decyzja produktowa: redirect vs lista

   Aktualne zachowanie:

   - dokladne jednoznaczne trafienie pelnego promptu robi redirect,
   - czesciowe zapytanie pokazuje liste.

   To jest dobre domyslnie. Mozna jednak obserwowac telemetryke i zdecydowac, czy przy pelnym promptcie lepiej zawsze pokazac wynik z kategoriami, zamiast od razu przenosic na pytanie.

Najpierw rekomendowane do realizacji:

- metryki wydajnosci,
- indeks / tabela search.

To da stabilny fundament pod autocomplete, fuzzy matching i bardziej zaawansowany ranking.
