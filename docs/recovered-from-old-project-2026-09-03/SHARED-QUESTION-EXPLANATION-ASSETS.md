# Shared assets dla wspolnych kart wyjasnienia

## Status

Stan na `2026-04-13`:

- shared flow dla grafiki po lewej stronie wyjasnienia jest wdrozony,
- runtime czyta shared assety po `external_id + source`,
- admin moze zapisac grafike:
  - tylko dla jednego pytania,
  - albo dla wszystkich pytan z tym samym numerem zrodlowym,
- lokalny override dalej istnieje i ma wyzszy priorytet niz shared asset,
- adnotacje na medium pytania nie sa objete tym mechanizmem.

Ten dokument jest kanonicznym opisem wdrozonego modelu, a nie planem na przyszlosc.

## O jakim elemencie mowimy

Chodzi o lewy blok z obrazkiem renderowany przez:

- [QuestionExplanationRuntimeBlock.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Components/QuestionExplanationRuntimeBlock.vue)

Ten komponent moze pokazac:

1. prawdziwy asset wyjasnienia zwrocony z backendu,
2. globalny fallback `public/images/session/explanation-fallback.svg`, gdy asset nie istnieje albo nie jest aktywny.

To rozroznienie pozostaje kluczowe:

- fallback nie jest shared assetem pytania,
- fallback jest tylko placeholderem UI.

## Co teraz dziala

### Prompt i tekst wyjasnienia

Wspolna edycja po `external_id + source` jest juz wdrozona dla:

- `questions.prompt`
- `questions.explanation`

### Grafika obok wyjasnienia

Ten sam model jest teraz wdrozony takze dla grafiki obok wyjasnienia.

Nowa warstwa danych:

- [SharedQuestionExplanationAsset.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Models/SharedQuestionExplanationAsset.php)
- tabela `shared_question_explanation_assets`
- migracja [2026_04_13_120000_create_shared_question_explanation_assets_table.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/migrations/2026_04_13_120000_create_shared_question_explanation_assets_table.php)

Klucz unikalnosci shared assetu:

- `external_id`
- `source_scope`
- `kind`

W praktyce dla dzisiejszego flow:

- jeden wspolny rekord `reference_sign` na `external_id + source`
- wspiera przypisanie `traffic_sign_id` (znaku drogowego z bazy) zamiast uploadu fizycznego pliku

## Model odczytu runtime

Odczyt jest rozwiazywany przez:

- [SharedQuestionExplanationAssetResolver.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/SharedQuestionExplanationAssetResolver.php)

Kolejnosc odczytu jest nastepujaca:

1. jesli pytanie ma lokalny `referenceExplanationAsset`, runtime uzywa lokalnego override,
2. w przeciwnym razie sprawdzany jest shared asset dla `external_id + source_scope`,
3. jesli nie ma ani lokalnego, ani shared assetu, frontend pokazuje fallback SVG.

Payload do runtime buduje:

- [QuestionExplanationAssetPayloadBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionExplanationAssetPayloadBuilder.php)

Builder jest swiadomie slepy na to, czy rekord pochodzi z:

- `question_explanation_assets`
- czy `shared_question_explanation_assets`

To jest poprawne i celowe. Runtime ma dostac juz rozwiazany asset.

## Model zapisu w adminie

Shared write path jest wdrozony w:

- [InteractsWithQuestionExplanationAsset.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Filament/Resources/Questions/Pages/Concerns/InteractsWithQuestionExplanationAsset.php)
- [QuestionForm.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Filament/Resources/Questions/Schemas/QuestionForm.php)
- [SharedQuestionExplanationAssetManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/SharedQuestionExplanationAssetManager.php)

Admin ma do wyboru:

- `Tylko to pytanie`
- `Wszystkie pytania z tym samym numerem zrodlowym`

### Tryb `single`

Zapisuje albo aktualizuje lokalny override dla jednego `question_id`.

Sciezka storage pozostaje per pytanie:

- `question-explanations/{question_id}/...`

### Tryb `shared_external_id`

Zapisuje albo aktualizuje jeden shared asset dla calej grupy:

- `external_id + source_scope + kind`

Sciezka storage dla shared assetu:

- `question-explanations/shared/{source_scope}/{external_id}/...`

Dodatkowo przy zapisie wspolnym:

- lokalne `referenceExplanationAsset` dla calej grupy sa czyszczone,
- dzieki temu runtime nie wpada w lokalne override i wszystkie kategorie rzeczywiscie zaczynaja uzywac jednej wspolnej grafiki.

To jest bardzo wazne: shared zapis nie tylko tworzy wspolny rekord, ale tez aktywnie zamyka lokalne rozjazdy w tej grupie.

## Ostrzezenia i bezpieczniki

### Ostrzezenie o konfliktach

Panel admina sprawdza, czy grupa ma juz rozjazd lokalnych grafik:

- [SharedQuestionScopeService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/SharedQuestionScopeService.php)

Jesli tak:

- admin widzi ostrzezenie,
- ale nadal moze swiadomie ujednolicic grupe wspolnym zapisem.

To zachowanie jest celowe:

- nie blokujemy pracy redakcji,
- ale nie ukrywamy, ze grupa ma historie rozjazdu.

### Bezpieczenstwo plikow

Najwazniejszy guard w tej implementacji:

- lokalne i shared assety sprawdzaja referencje do plikow po obu tabelach,
- zanim plik zostanie przeniesiony albo skasowany.

Odpowiadaja za to:

- [QuestionExplanationAssetManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionExplanationAssetManager.php)
- [SharedQuestionExplanationAssetManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/SharedQuestionExplanationAssetManager.php)

To zabezpiecza nas przed sytuacja, w ktorej:

- shared asset i lokalny override wskazuja na ten sam plik,
- a jedna operacja przypadkowo usuwa albo przenosi plik nadal potrzebny drugiej warstwie.

## Zsynchronizowany zapis adnotacji (Współdzielenie Markerów)

Nie nalezy mieszac dwoch roznych warstw na poziomie struktury bazy, ale ich UX dla redakcji jest teraz spójny:

1. `reference explanation asset`
   To jest grafika po lewej stronie tekstu wyjasnienia. Ma własną tabelę `shared_question_explanation_assets`.

2. `question_explanation_annotations`
   To sa markery i overlaye nanoszone na obraz albo stopklatke pytania. Zostają w tabeli powiązanej per `question_id`.

**NOWOSĆ (2026-04-29):**
Mimo osobnych architektur bazy danych, formularz admina obsługuje teraz jednoczesne współdzielenie obu warstw. Wybór zakresu `Wszystkie pytania z tym samym numerem źródłowym` dla adnotacji spowoduje fizyczne skopiowanie markerów z edytowanego pytania na wszystkie inne pytania w tej samej grupie (o tym samym `external_id`). Daje to efekt natychmiastowego współdzielenia wizualnego bez zmiany struktury tabeli adnotacji.

## Backfill i zgodnosc wstecz

Wdrozenie jest kompatybilne wstecz:

- stare lokalne assety per `question_id` nadal dzialaja,
- shared read path nie wymaga natychmiastowej migracji starych danych.

Na dzis:

- nie ma jeszcze automatycznego backfillu historycznych lokalnych assetow do shared tabeli,
- nie ma automatycznego scalania konfliktowych grup.

To jest swiadoma decyzja bezpieczenstwa.

Jesli kiedys zrobimy backfill, wolno migrowac automatycznie tylko grupy jednoznaczne.

## Weryfikacja

### Testy automatyczne

Shared flow jest pokryty testami:

- [QuestionExplanationAssetManagerTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Unit/Support/QuestionExplanationAssetManagerTest.php)
- [SharedQuestionExplanationAssetManagerTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Unit/Support/SharedQuestionExplanationAssetManagerTest.php)
- [SharedQuestionExplanationAssetResolverTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Unit/Support/SharedQuestionExplanationAssetResolverTest.php)
- [QuestionVisualExplanationEditPageTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/Admin/QuestionVisualExplanationEditPageTest.php)
- [StudySessionFlowTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/StudySessionFlowTest.php)

Pokryte przypadki:

- resolver wybiera shared asset, gdy nie ma lokalnego override,
- resolver preferuje lokalny override nad shared,
- lokalny manager nie usuwa pliku nadal uzywanego przez shared asset,
- shared manager nie usuwa pliku nadal uzywanego przez lokalny asset,
- admin moze zapisac jeden shared asset dla wszystkich pytan z tym samym `external_id + source`,
- runtime dla nauki potrafi zwrocic shared asset i poprawny `image_url`.

### Weryfikacja manualna

Flow zostal tez sprawdzony manualnie na tymczasowej grupie pytan w kilku kategoriach.

Zweryfikowano, ze:

- jedna shared grafika pojawia sie we wszystkich rekordach grupy,
- wszystkie rekordy rozwiazuja ten sam `file_path`,
- wszystkie rekordy rozwiazuja ten sam `image_url`,
- lokalne override po zapisie shared sa wyczyszczone,
- cleanup nie zostawia smieciowych rekordow ani plikow.

## Rekomendacja operacyjna

Dla redakcji i dalszego rozwoju przyjmujemy:

- pytanie shared
- wyjasnienie shared
- explanation asset shared
- annotations (synchronizowane w tle na żądanie admina)

Najbezpieczniejsza praktyka redakcyjna:

- dla wspolnych pytan domyslnie uzywac shared assetu,
- lokalny override robic tylko wtedy, gdy naprawde potrzebna jest roznica per konkretne pytanie.

## Co jeszcze mozna zrobic pozniej

Nastepne sensowne kroki, ale juz poza tym wdrozeniem:

1. raport grup z lokalnymi assetami, ktore nadaja sie do backfillu,
2. bezpieczny skrypt backfillu tylko dla grup jednoznacznych,
3. ewentualny audit UI admina, czy ostrzezenie konfliktu powinno byc jeszcze mocniejsze,
4. decyzja, czy chcemy w przyszlosci wspierac lokalny override shared assetu jako w pelni pierwszorzedny workflow redakcyjny.

## Decyzja kanoniczna

Przyjmujemy jako stan docelowy i aktualnie wdrozony:

- grafika po lewej stronie wyjasnienia ma shared flow po `external_id + source`,
- lokalny override per `question_id` nadal jest wspierany,
- runtime rozwiazuje asset w kolejnosci `local -> shared -> fallback`,
- adnotacje na medium pytania mają opcję hurtowego kopiowania (sync) na wszystkie pytania z danym `external_id` bezpośrednio z panelu edycji.
