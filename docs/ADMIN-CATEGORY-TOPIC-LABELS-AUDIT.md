# Audyt admina: customowe nazwy działów per kategoria

Status: koncepcja bez implementacji
Branch: `codex/audit-category-topic-names`
Data: 2026-06-08

## Cel funkcji

Chcemy umożliwić administratorowi ustawianie własnych nazw działów dla każdej kategorii prawa jazdy osobno.

Przykład:

- globalny dział techniczny: `warning_signs`
- standardowy label: `Znaki ostrzegawcze`
- label dla kategorii AM: `Znaki ostrzegawcze dla motorowerów`
- label dla kategorii B: `Znaki ostrzegawcze w ruchu samochodem osobowym`

Najważniejszy wymóg: nie wolno naruszyć przypisania pytań, postępów użytkownika, kolejności nauki ani statystyk.

## Stan obecny admin panelu

Admin panel jest oparty o Filament i automatycznie odkrywa zasoby z:

- `app/Filament/Resources`
- `app/Filament/Pages`
- `app/Filament/Widgets`

Główne zasoby admina:

| Obszar | Pliki | Znaczenie dla tej funkcji |
| --- | --- | --- |
| Kategorie prawa jazdy | `app/Filament/Resources/LicenseCategories` | Można edytować `code`, `name`, `slug`, `description`, `is_active`, `sort_order`. Nie ma tu działów per kategoria. |
| Baza pytań | `app/Filament/Resources/Questions` | Pytanie ma `license_category_id` i `question_topic_id`. Formularz i tabela pokazują globalne `questionTopic.name`. |
| Kolejność pytań | `app/Filament/Pages/QuestionLearningOrder.php` + `app/Support/QuestionLearningOrderAdminService.php` | Działa na parze `license_category_id + question_topic_id`. Obecnie lista działów pokazuje globalne `QuestionTopic.name`. |
| Dziennik audytu | `app/Filament/Resources/AuditLogs` + `app/Support/AuditLogService.php` | Można wykorzystać do logowania zmian nazw działów. Trzeba dodać nowe typy akcji. |
| PJM | `app/Filament/Pages/PjmCoverage.php` | Raporty PJM korzystają z kategorii i pytań, ale nie mają osobnej edycji nazw działów. |

Nie istnieje obecnie zasób Filament dla:

- `QuestionTopic`
- etykiet działów per kategoria
- konfiguracji nazw działów widocznych w `/nauka`

## Obecny model danych

### `license_categories`

Kategoria prawa jazdy:

- `id`
- `code`
- `slug`
- `name`
- `description`
- `is_active`
- `sort_order`

Model: `app/Models/LicenseCategory.php`

### `question_topics`

Globalny dział/temat pytania:

- `id`
- `key`
- `name`
- `description`
- `sort_order`
- `is_active`

Model: `app/Models/QuestionTopic.php`

Uwaga: to jest globalna definicja działu. Zmiana `question_topics.name` wpływa szeroko na admina, publiczne raporty i miejsca, które nie korzystają z `QuestionTopicClassifier::displayLabelForKey()`.

### `questions`

Pytanie wskazuje:

- `license_category_id`
- `question_topic_id`

To jest właściwe przypisanie merytoryczne. Nowa funkcja nie powinna tego zmieniać.

### `question_topic_overrides`

Istniejąca tabela override'ów nie służy do nazw. Ona służy do nadpisania klasyfikacji konkretnego pytania:

- `license_category_code`
- `source`
- `external_id`
- `question_topic_key`

Nie należy jej używać do customowych nazw działów, bo to wymiesza dwa różne pojęcia:

- do jakiego działu należy pytanie,
- jak dział ma się nazywać w konkretnej kategorii.

## Główne powiązania z nauką

### `/nauka`

Źródła:

- `app/Http/Controllers/SessionPageController.php`
- `app/Support/StudyTopicGroupsService.php`
- `resources/js/Pages/Session/Index.vue`

Obecnie label działu jest budowany przez:

```php
$classifier->displayLabelForKey($topic->key)
```

To jest pierwsze miejsce, które trzeba będzie podpiąć do resolvera etykiet per kategoria.

### `/nauka/pjm`

Źródła:

- `app/Http/Controllers/PjmSessionPageController.php`
- `app/Support/PjmQuestionProgressService.php`
- `resources/js/Pages/Session/PjmIndex.vue`

Obecnie PJM także używa:

```php
$classifier->displayLabelForKey($topic->key)
```

Jeśli customowe nazwy mają być spójne w module nauki, PJM powinien korzystać z tej samej warstwy etykiet.

### Wynik sesji i ścieżka działów

Źródła:

- `app/Support/StudyTopicCompletionRecordService.php`
- `resources/js/Pages/StudySessions/Show.vue`

Ten obszar dostaje już gotowe `topic.label` z topic groups. Jeśli poprawimy `StudyTopicGroupsService`, większość widoku wyników powinna przejąć label automatycznie.

### Kolejność pytań w adminie

Źródła:

- `app/Filament/Pages/QuestionLearningOrder.php`
- `app/Support/QuestionLearningOrderAdminService.php`

Ta funkcja działa na `license_category_id + question_topic_id`. Obecnie lista tematów używa `QuestionTopic.name`.

Rekomendacja:

- w adminie kolejności pokazywać label customowy jako główny,
- obok albo pod spodem pokazywać globalny temat techniczny,
- nie zmieniać identyfikatora `question_topic_id`.

## Publiczne miejsca, które mogą korzystać z nazwy działu

### Najtrudniejsze pytania

Źródła:

- `app/Support/PublicQuestionDifficultyService.php`
- `resources/js/Pages/Public/HardestQuestions/Index.vue`

Obecnie publiczny ranking używa `topics.name`, czyli surowej nazwy z bazy.

Do decyzji produktowej:

- czy customowe nazwy per kategoria mają działać tylko w panelu nauki,
- czy także na publicznych rankingach / SEO.

Rekomendacja MVP:

1. Najpierw podpiąć customowe nazwy tylko do modułów nauki i adminowego podglądu.
2. Dopiero potem świadomie rozszerzyć na publiczne strony, bo to wpływa na SEO i treści indeksowane.

## Proponowana architektura

### Nowa tabela

Nazwa proponowana:

`question_topic_category_labels`

Pola:

| Pole | Typ | Cel |
| --- | --- | --- |
| `id` | bigint | Klucz rekordu. |
| `license_category_id` | FK | Kategoria prawa jazdy. |
| `question_topic_id` | FK | Globalny dział. |
| `display_name` | string, max 120 | Nazwa widoczna dla użytkownika w tej kategorii. |
| `admin_note` | text nullable | Notatka dla zespołu, dlaczego nazwa została zmieniona. |
| `is_active` | boolean | Czy override jest aktywny. Nieaktywny rekord daje fallback do globalnego labela. |
| `created_by` | FK users nullable | Kto utworzył. |
| `updated_by` | FK users nullable | Kto ostatnio zmienił. |
| `created_at`, `updated_at` | timestamps | Historia techniczna. |

Indeksy:

- unique: `license_category_id + question_topic_id`
- index: `question_topic_id`
- index: `is_active`

Dlaczego tak:

- nie zmieniamy `question_topics`,
- nie zmieniamy `questions.question_topic_id`,
- zachowujemy pełną kompatybilność z postępami i statystykami,
- możemy dodać etykietę tylko tam, gdzie jest potrzebna.

### Nowy model

Proponowana nazwa:

`QuestionTopicCategoryLabel`

Relacje:

- `belongsTo(LicenseCategory::class)`
- `belongsTo(QuestionTopic::class)`
- `belongsTo(User::class, 'created_by')`
- `belongsTo(User::class, 'updated_by')`

### Nowy resolver etykiet

Proponowana nazwa:

`QuestionTopicLabelResolver`

Odpowiedzialność:

- przyjąć kategorię i topic,
- sprawdzić aktywny custom label,
- jeśli istnieje, zwrócić `display_name`,
- jeśli nie istnieje, zwrócić obecny fallback:
  - `QuestionTopicClassifier::displayLabelForKey($topic->key)`
  - potem `QuestionTopic.name`

Ważne: resolver powinien mieć metodę do pracy zbiorczej, żeby nie robić N+1 query przy budowaniu listy działów.

Przykładowe API koncepcyjne:

```php
public function labelsForCategory(LicenseCategory $category, Collection $topics): Collection;
public function labelFor(LicenseCategory $category, QuestionTopic $topic): string;
```

## Proponowany admin UX

Najbezpieczniejsza wersja:

### Nowy zasób Filament: `Nazwy działów`

Nawigacja:

- grupa: `Zawartość`
- etykieta: `Nazwy działów`
- obok istniejących `Kategorie` i `Baza pytań`

Tabela:

- Kategoria
- Kod kategorii
- Globalny dział
- Customowa nazwa
- Liczba aktywnych/gotowych pytań w tej kategorii i dziale
- Aktywny
- Ostatnia aktualizacja
- Edytował

Filtry:

- kategoria
- dział
- aktywne/nieaktywne
- tylko działy z pytaniami

Formularz:

- `license_category_id`
- `question_topic_id`
- readonly preview: globalna nazwa działu
- readonly preview: domyślna etykieta z klasyfikatora
- `display_name`
- `admin_note`
- `is_active`

Zabezpieczenia formularza:

- nie pozwalać na pusty `display_name`, jeśli `is_active = true`,
- limit długości, np. 80-120 znaków,
- ostrzegać albo blokować duplikaty aktywnych nazw w tej samej kategorii,
- nie pozwalać edytować `question_topic_id` po utworzeniu rekordu, żeby nie zmieniać znaczenia wpisu.

### Alternatywa: zakładka w widoku kategorii

Można też dodać podgląd/edit matrix bezpośrednio w `LicenseCategoryResource`.

Zaleta:

- admin najpierw wybiera kategorię, potem widzi wszystkie działy tej kategorii.

Wada:

- więcej customowej pracy w Filament,
- trudniej zrobić proste wyszukiwanie po wszystkich override'ach.

Rekomendacja MVP:

- zacząć od osobnego zasobu `Nazwy działów`,
- później ewentualnie dodać skrót z widoku kategorii.

## Co musi pozostać nienaruszone

Nie zmieniamy:

- `question_topics.key`
- `question_topics.id`
- `questions.question_topic_id`
- `question_topic_overrides`
- `question_learning_order_sets`
- `user_topic_completion_records`
- logiki klasyfikacji pytań
- liczników pytań w działach
- ukrywania działów bez pytań

Zmienia się tylko warstwa prezentacji nazwy.

## Miejsca do podpięcia w implementacji

### Wymagane w MVP

- `app/Support/StudyTopicGroupsService.php`
  - obecnie: `displayLabelForKey($topic->key)`
  - docelowo: resolver z kategorią

- `app/Support/PjmQuestionProgressService.php`
  - obecnie: `displayLabelForKey($topic->key)`
  - docelowo: resolver z kategorią

- `app/Support/QuestionLearningOrderAdminService.php`
  - obecnie: `QuestionTopic.name`
  - docelowo: custom label jako główna nazwa w adminie, globalny topic jako opis pomocniczy

- nowy zasób Filament
  - adminowe CRUD dla etykiet

- `AuditLogPresenter` / `AuditLogService`
  - akcja utworzenia/zmiany/dezaktywacji etykiety

### Opcjonalne po MVP

- `app/Support/PublicQuestionDifficultyService.php`
  - publiczne rankingi i “najtrudniejsze działy”

- `app/Filament/Resources/Questions`
  - podgląd pytania może pokazywać custom label w kontekście kategorii, ale globalny temat powinien zostać widoczny technicznie

- publiczne strony SEO
  - tylko po decyzji, że customowe nazwy mają wpływać na treści indeksowane

## Ryzyka

### 1. Pomylenie etykiety z klasyfikacją

Największe ryzyko to stworzenie wrażenia, że admin zmienia dział pytania, gdy tak naprawdę zmienia tylko nazwę działu.

Zabezpieczenie:

- w UI używać jasnych opisów:
  - `Globalny dział techniczny`
  - `Nazwa widoczna dla kursanta`

### 2. Duplikaty nazw w jednej kategorii

Jeśli dwa różne działy dostaną tę samą nazwę w kategorii B, użytkownik zobaczy nieczytelną ścieżkę nauki.

Zabezpieczenie:

- blokada albo ostrzeżenie dla duplikatu aktywnego `display_name` w tej samej kategorii.

### 3. Zbyt długie nazwy

Nazwy działów są widoczne w sidebarach, kartach i wynikach sesji.

Zabezpieczenie:

- limit długości,
- preview w adminie,
- testy UI dla długiego labela.

### 4. Rozjazd między nauką a publicznymi stronami

Jeśli `/nauka` będzie pokazywać custom label, a publiczny ranking surowy `question_topics.name`, nazwy mogą się różnić.

Zabezpieczenie:

- świadoma decyzja zakresu.
- MVP: tylko nauka.
- Faza 2: publiczne strony, jeśli chcemy spójności SEO.

### 5. Import / klasyfikacja

`QuestionTopicAssigner::seedTopics()` aktualizuje `question_topics`, ale nie powinien dotykać nowych etykiet per kategoria.

Zabezpieczenie:

- osobna tabela,
- brak ingerencji w import i klasyfikator.

## Testy wymagane przed wdrożeniem

### Backend

- resolver zwraca custom label dla konkretnej kategorii,
- resolver robi fallback, gdy override nie istnieje,
- resolver ignoruje nieaktywny override,
- custom label dla AM nie wpływa na B,
- brak N+1 przy listach działów,
- nie da się utworzyć dwóch aktywnych labeli dla tej samej pary `category + topic`.

### Nauka

- `/nauka` pokazuje custom label dla kategorii B,
- `/nauka` dla kategorii AM pokazuje inny custom label dla tego samego topicu,
- liczba pytań nie zmienia się po zmianie nazwy,
- start sesji dalej wysyła ten sam `question_topic_id`,
- wynik sesji pokazuje label zgodny z kategorią.

### PJM

- `/nauka/pjm` pokazuje custom label,
- progres i current topic nadal liczą się po `question_topic_id`.

### Admin

- admin może dodać/edytować/dezaktywować custom label,
- zmiana zapisuje audit log,
- panel kolejności pytań pokazuje custom label bez zmiany topic ID,
- formularz pytania nadal pozwala przypisać globalny topic.

## Rekomendowany plan wdrożenia

1. Dodać tabelę i model `QuestionTopicCategoryLabel`.
2. Dodać resolver etykiet z fallbackiem do obecnego klasyfikatora.
3. Dodać testy resolvera.
4. Dodać zasób Filament `Nazwy działów`.
5. Dodać audit log dla zmian etykiet.
6. Podpiąć resolver tylko do `StudyTopicGroupsService`.
7. Przetestować `/nauka`.
8. Podpiąć resolver do `PjmQuestionProgressService`.
9. Przetestować `/nauka/pjm`.
10. Podpiąć resolver do adminowej kolejności pytań jako label prezentacyjny.
11. Dopiero po stabilizacji zdecydować, czy rozszerzamy to na publiczne rankingi i SEO.

## Decyzja rekomendowana

Tak, funkcja ma sens, ale powinna być zrobiona jako warstwa prezentacyjna:

- globalne działy zostają bez zmian,
- pytania zostają przypisane do tych samych `question_topic_id`,
- admin zarządza tylko nazwą widoczną dla konkretnej kategorii,
- zmiany są audytowane,
- publiczne SEO zostawiamy poza MVP, dopóki świadomie nie zdecydujemy, że customowe nazwy mają wejść do indeksowanych treści.

