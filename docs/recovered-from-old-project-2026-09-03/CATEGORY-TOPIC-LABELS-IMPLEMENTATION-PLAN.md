# Customowe nazwy działów per kategoria - plan wdrożenia

Status: implementacja lokalna w toku
Branch: `codex/audit-category-topic-names`
Data: 2026-06-08

Aktualny etap: fundament danych, resolver etykiet, panel administracyjny przy kategorii, audit log oraz podpięcie w `/nauka`, `/nauka/pjm` i adminowej kolejności pytań są zaimplementowane lokalnie.

## Cel

Chcemy umożliwić administratorowi ustawienie osobnej, customowej nazwy działu dla konkretnej kategorii prawa jazdy, bez zmiany merytorycznego przypisania pytań.

Przykład:

- globalny dział: `warning_signs`
- domyślna etykieta: `Znaki ostrzegawcze`
- etykieta dla kategorii AM: `Znaki ostrzegawcze dla motorowerów`
- etykieta dla kategorii B: `Znaki ostrzegawcze w kategorii B`

## Decyzja architektoniczna

Zmieniamy wyłącznie etykiety prezentacyjne.

Nie zmieniamy:

- `question_topics.id`,
- `question_topics.key`,
- `questions.question_topic_id`,
- klasyfikacji pytań,
- liczników pytań,
- kolejności pytań,
- rekordów ukończenia działów,
- postępów użytkownika,
- importów i override'ów klasyfikacji.

## Dokumenty referencyjne

- `docs/QUESTION-TOPIC-NAMES-AUDIT.md` - audyt obecnych nazw działów i kategorii.
- `docs/ADMIN-CATEGORY-TOPIC-LABELS-AUDIT.md` - audyt admin panelu i powiązań.
- `docs/PJ360-CATEGORY-CONSISTENCY-AUDIT-PLAN.md` - wcześniejszy plan zgodności z PJ360.
- `docs/QUESTION-LEARNING-ORDER-ADMIN-PLAN.md` - kontekst adminowej kolejności pytań.

## Co jest już zrobione

- [x] Utworzony branch roboczy: `codex/audit-category-topic-names`.
- [x] Sprawdzone źródła nazw kategorii i działów.
- [x] Potwierdzone, że `/nauka` nie używa bezpośrednio `question_topics.name`, tylko `QuestionTopicClassifier::displayLabelForKey()`.
- [x] Wygenerowany snapshot lokalnych działów:
  - `output/analysis/category-topic-names/local-topic-groups.json`
  - `output/analysis/category-topic-names/topic-name-matrix.json`
- [x] Potwierdzone, że pełna definicja ma 31 działów.
- [x] Potwierdzone, że `AM` i `B1` mają po 30 widocznych działów, bo nie mają pytań w dziale `Jazda z przyczepą`.
- [x] Potwierdzone, że obecnie nie pokazujemy działów bez pytań.
- [x] Zbadany admin panel i główne powiązania.
- [x] Ustalona rekomendacja: osobna tabela etykiet per `license_category_id + question_topic_id`.
- [x] Dodana migracja `question_topic_category_labels`.
- [x] Dodany model `QuestionTopicCategoryLabel`.
- [x] Dodane relacje z `LicenseCategory` i `QuestionTopic`.
- [x] Dodany `QuestionTopicLabelResolver`.
- [x] Dodane testy resolvera i unikalności pary `category + topic`.
- [x] Dodany relation manager `Nazwy działów w tej kategorii` przy zasobie kategorii.
- [x] Podpięty resolver do `StudyTopicGroupsService`.
- [x] Podpięty resolver do `PjmQuestionProgressService`.
- [x] Dodane testy regresyjne payloadu `/nauka` i `/nauka/pjm` z custom labelami.
- [x] Dodane audit logi dla create/update/delete etykiet.
- [x] Podpięty resolver do adminowej kolejności pytań jako label prezentacyjny z techniczną nazwą obok.

## Co nie jest jeszcze zrobione

- [ ] Publiczne rankingi i SEO nie są jeszcze objęte decyzją.

## Zakres MVP

MVP ma obejmować:

1. Nową tabelę etykiet działów per kategoria.
2. Nowy model.
3. Resolver etykiet z bezpiecznym fallbackiem.
4. Panel admina do zarządzania etykietami.
5. Audit log zmian albo minimum pola `created_by` / `updated_by` w MVP technicznym.
6. Podpięcie resolvera do `/nauka`.
7. Podpięcie resolvera do `/nauka/pjm`.
8. Podpięcie resolvera do adminowej kolejności pytań jako label prezentacyjny.

MVP nie obejmuje:

- zmiany nazw na publicznych stronach SEO,
- zmiany nazw w sitemapach,
- zmiany klasyfikacji pytań,
- zmiany struktury działów,
- pokazywania pustych działów bez pytań,
- masowego generowania nazw dla wszystkich kategorii.

## Proponowany model danych

Tabela:

`question_topic_category_labels`

Pola:

| Pole | Typ | Cel |
| --- | --- | --- |
| `id` | bigint | Klucz rekordu. |
| `license_category_id` | foreign id | Kategoria prawa jazdy. |
| `question_topic_id` | foreign id | Globalny dział. |
| `display_name` | string, max 120 | Nazwa widoczna dla kursanta/admina. |
| `admin_note` | text nullable | Notatka wewnętrzna. |
| `is_active` | boolean | Czy custom label obowiązuje. |
| `created_by` | foreign id nullable | Kto utworzył wpis. |
| `updated_by` | foreign id nullable | Kto ostatnio zmienił wpis. |
| `created_at`, `updated_at` | timestamps | Historia techniczna. |

Indeksy:

- unique: `license_category_id + question_topic_id`
- index: `question_topic_id`
- index: `is_active`

Fallback:

1. Aktywna etykieta z `question_topic_category_labels`.
2. `QuestionTopicClassifier::displayLabelForKey($topic->key)`.
3. `question_topics.name`.
4. `question_topics.key`.

## Proponowany resolver

Nazwa:

`QuestionTopicLabelResolver`

Odpowiedzialność:

- zwraca label dla pary `LicenseCategory + QuestionTopic`,
- obsługuje fallback,
- pozwala pobrać etykiety zbiorczo dla listy topiców,
- nie zmienia żadnych danych pytań.

Wymagana cecha:

- brak N+1 query przy budowaniu listy działów.

## Panel admina

Implementacja MVP:

`Nazwy działów w tej kategorii`

Miejsce:

relation manager przy zasobie `Kategorie`.

Tabela powinna pokazywać:

- globalny dział,
- domyślną etykietę,
- customową etykietę,
- status aktywności,
- ostatnią aktualizację,
- osobę aktualizującą.

Formularz powinien zawierać:

- `question_topic_id`,
- `display_name`,
- `admin_note`,
- `is_active`.

Ponieważ edycja odbywa się wewnątrz konkretnej kategorii, `license_category_id` pochodzi z rekordu nadrzędnego i nie jest ręcznie wybierane przez admina.

Zabezpieczenia:

- blokada pustego `display_name` przy aktywnym wpisie,
- limit długości,
- ostrzeżenie albo blokada duplikatu aktywnej nazwy w tej samej kategorii,
- po utworzeniu rekordu nie zmieniać pary `category + topic`, tylko tworzyć nowy wpis.

## Miejsca do zmiany w kodzie

### Faza 1 - infrastruktura

- `database/migrations/*_create_question_topic_category_labels_table.php`
- `app/Models/QuestionTopicCategoryLabel.php`
- relacje w:
  - `LicenseCategory`
  - `QuestionTopic`
- `app/Support/QuestionTopicLabelResolver.php`
- testy resolvera

### Faza 2 - admin

- `app/Filament/Resources/LicenseCategories/RelationManagers/QuestionTopicCategoryLabelsRelationManager.php`
- `app/Support/AuditLogPresenter.php`
- zapis audit logów przy create/update/deactivate
- testy admin resource

### Faza 3 - nauka

- `app/Support/StudyTopicGroupsService.php`
- testy `/nauka`

### Faza 4 - PJM

- `app/Support/PjmQuestionProgressService.php`
- testy `/nauka/pjm`

### Faza 5 - kolejność pytań

- `app/Support/QuestionLearningOrderAdminService.php`
- `app/Filament/Pages/QuestionLearningOrder.php`
- testy panelu kolejności pytań

### Faza 6 - decyzja publiczna

Do osobnej decyzji:

- `app/Support/PublicQuestionDifficultyService.php`
- publiczne rankingi,
- SEO,
- sitemap/llms, jeśli nazwy działów mają stać się publiczną treścią indeksowaną.

## Plan wdrożenia krok po kroku

### Krok 0 - obecny status

- [x] Audyt nazw działów.
- [x] Audyt admin panelu.
- [x] Decyzja, że zmieniamy tylko etykiety.
- [x] Plan wdrożenia.

### Krok 1 - dane i resolver

- [x] Dodać migrację.
- [x] Dodać model.
- [x] Dodać relacje.
- [x] Dodać resolver.
- [x] Dodać test fallbacku.
- [x] Dodać test izolacji kategorii.
- [x] Dodać test ignorowania nieaktywnej etykiety.

Warunek przejścia dalej:

- [x] Resolver działa i nie wpływa na `question_topic_id`.

Weryfikacja:

- `docker compose exec -T app php artisan test tests/Feature/Support/QuestionTopicLabelResolverTest.php` - 6 testów przeszło.
- Lokalnie po dodaniu migracji trzeba uruchomić `docker compose exec -T app php artisan migrate`; bez tego `/nauka` zwróci 500, bo resolver czyta tabelę `question_topic_category_labels`.

### Krok 2 - admin resource

- [x] Dodać panel Filament `Nazwy działów w tej kategorii` jako relation manager przy kategorii.
- [x] Dodać listę, podgląd, edycję i tworzenie w kontekście kategorii.
- [x] Dodać licznik pytań przy wyborze działu technicznego.
- [x] Dodać walidację duplikatów.
- [x] Dodać audit log.
- [ ] Dodać testy admin panelu albo smoke test panelu po uruchomieniu lokalnym.

Warunek przejścia dalej:

- admin może dodać etykietę dla jednej kategorii i nie zmienia danych pytań.

### Krok 3 - `/nauka`

- [x] Podpiąć resolver w `StudyTopicGroupsService`.
- [x] Upewnić się testem, że liczba działów i pytań się nie zmienia.
- [x] Sprawdzić kategorię z custom label.
- [x] Sprawdzić kategorię bez custom label przez fallback resolvera.
- [ ] Sprawdzić wynik sesji i ścieżkę działów.

Warunek przejścia dalej:

- zmienia się tylko tekst labela.

### Krok 4 - PJM

- [x] Podpiąć resolver w `PjmQuestionProgressService`.
- [x] Sprawdzić listę działów PJM testem payloadu.
- [ ] Sprawdzić start sesji PJM.
- [x] Sprawdzić, że current/review topic nadal opiera się na `question_topic_id`.

Warunek przejścia dalej:

- PJM pokazuje custom label, ale progres działa bez zmian.

### Krok 5 - adminowa kolejność pytań

- [x] Pokazać custom label jako nazwę prezentacyjną.
- [x] Pokazać globalny temat jako opis techniczny.
- [x] Upewnić się testem, że drafty i aktywne sety dalej zapisują `question_topic_id`.

Warunek przejścia dalej:

- admin wie, który dział edytuje, bez ryzyka pomylenia labela z klasyfikacją.

### Krok 6 - stabilizacja

- [ ] Pełny `php artisan test` albo przynajmniej zestaw testów dotkniętych modułów.
- [ ] `npm run build`.
- [ ] Smoke test lokalny `/nauka`.
- [ ] Smoke test lokalny `/nauka/pjm`, jeśli dostępne dane.
- [ ] Smoke test panelu admina.
- [ ] Aktualizacja dokumentacji statusu.

### Krok 7 - deploy

- [ ] Merge do `main`.
- [ ] Build assetów.
- [ ] Deploy produkcyjny.
- [ ] `php artisan migrate --force` - wymagane, bo `/nauka` i `/nauka/pjm` czytają tabelę `question_topic_category_labels`.
- [ ] Health check.
- [ ] Smoke test produkcyjny:
  - `/nauka`,
  - panel admina,
  - podstawowy start sesji.

## Kryteria akceptacji

Funkcja jest gotowa, gdy:

- admin może ustawić inną nazwę tego samego działu dla dwóch różnych kategorii,
- `/nauka` pokazuje nazwę zależną od kategorii,
- `/nauka/pjm` pokazuje nazwę zależną od kategorii,
- liczby pytań nie zmieniają się po zmianie etykiety,
- start sesji nadal używa tego samego `question_topic_id`,
- wynik sesji nadal działa,
- adminowa kolejność pytań nadal działa,
- zmiana nazwy zostawia ślad w audit logu,
- brak custom labela oznacza pełny fallback do obecnego zachowania.

## Plan rollbacku

Rollback funkcjonalny:

1. Wyłączyć custom label przez `is_active = false`.
2. Jeśli problem jest szerszy, odpiąć resolver z usług nauki i wrócić do `displayLabelForKey()`.
3. Nie trzeba ruszać pytań ani postępów, bo nowa tabela nie zmienia danych merytorycznych.

Rollback techniczny:

- migracja `down()` usuwa tylko tabelę etykiet,
- usunięcie tabeli nie powinno naruszyć `questions`, `question_topics`, `study_sessions`, `user_topic_completion_records`.

## Ryzyka i zabezpieczenia

| Ryzyko | Skutek | Zabezpieczenie |
| --- | --- | --- |
| Admin uzna, że zmienia dział pytania | Błędna interpretacja panelu | UI: `Globalny dział` vs `Nazwa widoczna`. |
| Dwa działy w jednej kategorii dostaną tę samą nazwę | Nieczytelny widok nauki | Walidacja/ostrzeżenie duplikatu. |
| Zbyt długa nazwa | Psuje layout kart i sidebarów | Limit długości + test UI. |
| N+1 query | Spowolnienie `/nauka` | Resolver zbiorczy. |
| Publiczne strony pokażą inne nazwy niż nauka | Niespójność SEO/UX | Publiczne strony poza MVP, osobna decyzja. |
| Import nadpisze nazwy | Utrata zmian admina | Osobna tabela, import nie dotyka etykiet. |

## Otwarte decyzje

- Czy customowe nazwy mają być używane publicznie w rankingach i SEO?
- Czy w adminie pytań pokazywać custom label, globalny label, czy oba?
- Czy blokować duplikat nazwy w kategorii, czy tylko ostrzegać?
- Czy przygotować predefiniowane etykiety dla wszystkich kategorii, czy pozwolić adminowi uzupełniać ręcznie stopniowo?

## Rekomendacja

Idziemy etapami.

Najpierw wdrażamy bezpieczne MVP jako warstwę etykiet dla nauki i admina. Publiczne SEO zostawiamy na później, bo tam nazwy działów stają się treścią indeksowaną i wymagają osobnej decyzji redakcyjnej.
