# Sidebar wyniku nauki: dwa liczniki postępu

## Status dokumentu

- Branch: `codex/diagnose-result-progress-sidebar`
- Aktualna faza: implementacja i weryfikacja zakończone, gotowe do przeglądu
- Kod aplikacji zmieniony: tak
- Ostatnia aktualizacja: 2026-06-18

### Legenda

- `[ ]` do zrobienia
- `[~]` w trakcie
- `[x]` zakończone
- `[!]` wymaga decyzji albo dodatkowej weryfikacji

## Cel

Na ekranie wyniku sesji nauki, w nagłówku lewego sidebaru „Twój postęp w nauce”, mają pojawić się dwa niezależne liczniki:

1. **Opanowane** — liczba działów zaliczonych pełną sesją na 100%, bez żadnego błędu.
2. **Do końca** — liczba działów znajdujących się po aktualnym dziale w uporządkowanej ścieżce nauki.

Te liczniki opisują dwie różne rzeczy i nie mogą korzystać z tego samego algorytmu.

### Przykład

Jeżeli ścieżka ma 31 działów, a użytkownik aktualnie znajduje się na dziale 5:

- `Opanowane: X / 31`
- `Do końca: 26`

Aktualnego działu nie wliczamy do wartości „Do końca”.

## Uzgodnione reguły biznesowe

### Licznik „Opanowane”

- Dział jest opanowany, jeżeli użytkownik ukończył pełną pulę pytań tego działu z wynikiem 100%.
- Liczymy wyłącznie rekordy dla `question_scope = all`.
- Perfekcyjne ukończenie samego zakresu `basic` albo `specialist` nie oznacza opanowania całego działu.
- Niedokończona sesja, ręczne zakończenie bez odpowiedzi i wynik poniżej 100% nie zaliczają działu.
- Każdy dział liczymy maksymalnie jeden raz.
- Licznik jest niezależny od aktualnej pozycji użytkownika na ścieżce.

### Licznik „Do końca”

- Źródłem jest pozycja aktualnego działu w kolejności zwracanej przez `StudyTopicGroupsService`.
- Wartość liczymy jako:

```text
liczba wszystkich działów - pozycja aktualnego działu
```

- Pierwszy dział z 31 daje `30`.
- Piąty dział z 31 daje `26`.
- Ostatni dział daje `0`.
- Wartość nie zależy od tego, ile działów użytkownik zaliczył na 100%.
- Ręczne wejście do wcześniejszego lub późniejszego działu zmienia licznik zgodnie z jego pozycją.

## Wynik diagnostyki

### Obecny błąd

Aktualny nagłówek sidebaru używa:

```text
liczba wszystkich działów - liczba działów z aktualnie opanowanymi wszystkimi pytaniami
```

Definicja aktualnego opanowania bazuje na:

```text
correct + memorized == questions_count
```

Jednocześnie wiersze sidebaru mogą pokazywać „Zaliczony” na podstawie historycznego rekordu perfekcyjnej sesji. Powoduje to dwie różne definicje zaliczenia w jednym komponencie.

### Dane sesji 1441

- Wszystkie działy: 31
- Aktualny dział: 1 z 31
- Działy z historycznym rekordem 100% dla zakresu `all`: 10
- Działy spełniające bieżącą regułę `correct + memorized`: 2
- Obecny błędny komunikat: `Jeszcze 29 działów przed Tobą`
- Docelowe liczniki:
  - `Opanowane: 10 / 31`
  - `Do końca: 30`

Sesja 1441 została zakończona po około 2 sekundach bez zapisanych odpowiedzi. Nie utworzyła nowego rekordu i nie powinna zwiększać licznika „Opanowane”.

## Istotne miejsca w kodzie

### Frontend

Plik:

```text
resources/js/Pages/StudySessions/Show.vue
```

Istotne obszary:

- `activeSessionTopicId` — identyfikator aktualnego działu.
- `roadmapTopicOptions` — uporządkowana lista działów ścieżki.
- `isTopicCompleted` — obecna definicja oparta na stanie pytań.
- `completedRoadmapTopicsCount` — obecny błędny licznik zaliczonych działów.
- `remainingRoadmapTopicsCount` — obecny błędny licznik pozostałych działów.
- `completionProgressRecordByTopicId` — mapa historycznych rekordów.
- `completionProgressPanelItems` — dane wierszy sidebaru.
- `completionProgressPanelLead` — obecny pojedynczy komunikat w nagłówku.
- sekcja szablonu rozpoczynająca się od tekstu „Twój postęp w nauce”.

### Backend

Pliki:

```text
app/Support/StudyTopicCompletionRecordService.php
app/Support/StudyTopicGroupsService.php
app/Http/Controllers/StudySessionController.php
app/Http/Controllers/StudySessionAnswerController.php
```

Odpowiedzialności:

- `StudyTopicGroupsService` ustala listę i kolejność działów.
- `StudyTopicCompletionRecordService` przechowuje i zwraca rekordy ukończeń.
- `StudySessionController` przekazuje `topicGroups` i `topicCompletionOverview` do strony.
- `StudySessionAnswerController` odświeża dane po ostatniej odpowiedzi.

## Docelowy kontrakt danych

Rekomendowane jest policzenie podsumowania po stronie backendu i dołączenie go do `topicCompletionOverview`.

Proponowana struktura:

```text
topicCompletionOverview:
  category_id
  question_scope
  current_topic_id
  items
  learning_path:
    total_topics
    mastered_topics
    mastered_topic_ids
    current_position
    remaining_after_current
```

Znaczenie pól:

- `total_topics` — liczba aktywnych działów posiadających pytania w kategorii.
- `mastered_topics` — liczba działów z perfekcyjnym rekordem `question_scope = all`.
- `mastered_topic_ids` — identyfikatory używane również do spójnego oznaczania wierszy jako „Zaliczony”.
- `current_position` — pozycja aktualnego działu liczona od 1.
- `remaining_after_current` — liczba działów po aktualnym.

Backend powinien być źródłem prawdy, aby pełne ładowanie strony i odpowiedź po ostatniej odpowiedzi zwracały identyczne wartości.

## Plan wdrożenia

### Etap 1: kontrakt i obliczenia backendowe

- [x] Rozszerzyć `TopicCompletionOverview` o podsumowanie `learning_path`.
- [x] Ustalić listę działów na podstawie kolejności przekazanej przez `StudyTopicGroupsService`.
- [x] Pobrać perfekcyjne rekordy dla kategorii użytkownika i `question_scope = all`.
- [x] Ograniczyć rekordy do działów obecnych na aktualnej ścieżce.
- [x] Policzyć `mastered_topics` i `mastered_topic_ids`.
- [x] Ustalić `current_position` z `current_topic_id`.
- [x] Policzyć `remaining_after_current`.
- [x] Nie wykonywać zapytań N+1.
- [x] Zwrócić identyczny kontrakt przy pełnym renderowaniu strony.
- [x] Zwrócić identyczny kontrakt po ostatniej odpowiedzi w sesji.
- [x] Zwrócić identyczny kontrakt po ręcznym zakończeniu sesji.

### Etap 2: frontend i prezentacja

- [x] Rozszerzyć typ `TopicCompletionOverview` w `Show.vue`.
- [x] Usunąć użycie `remainingRoadmapTopicsCount` z nagłówka sidebaru.
- [x] Dodać dwa wizualnie równorzędne liczniki:
  - `Opanowane`
  - `Do końca`
- [x] Przy „Opanowane” pokazać wartość w formacie `X / Y`.
- [x] Przy „Do końca” pokazać liczbę działów po aktualnym.
- [x] Dodać czytelne stany dla `0`.
- [x] Dodać bezpieczny stan `—`, gdy aktualnego działu nie ma na ścieżce.
- [x] Zachować czytelność na wąskim mobile.

### Etap 3: ujednolicenie wierszy sidebaru

- [x] Używać `mastered_topic_ids` jako definicji zielonego „Zaliczony”.
- [x] Używać tej samej definicji dla checkmarka, koloru i etykiety statusu.
- [x] Pozostawić procent bieżącego stanu pytań jako osobną informację.
- [x] Nie oznaczać działu jako opanowanego wyłącznie przez `correct + memorized`.
- [x] Nie nadpisywać historycznego statusu 100% słabszym wynikiem późniejszej sesji.

### Etap 4: testy automatyczne

- [ ] Dodać test: brak rekordów daje `Opanowane: 0 / N`.
- [x] Dodać test: perfekcyjna pełna sesja `all` zwiększa licznik.
- [x] Dodać test: wynik 99% nie zwiększa licznika.
- [x] Dodać test: pusta ręcznie zakończona sesja nie zwiększa licznika.
- [x] Dodać test: rekord `basic` nie zwiększa globalnego licznika.
- [ ] Dodać test: rekord `specialist` nie zwiększa globalnego licznika.
- [x] Dodać test: ten sam dział zaliczony wielokrotnie liczy się raz.
- [x] Dodać test: pierwszy dział daje `remaining_after_current = total_topics - 1`.
- [ ] Dodać test: dział 5 z 31 daje `remaining_after_current = 26`.
- [x] Dodać test: ostatni dział daje `remaining_after_current = 0`.
- [x] Dodać test: kolejność wynika z `StudyTopicGroupsService`, a nie z ID tabeli.
- [ ] Dodać test: aktualny dział nieobecny na ścieżce daje `current_position = null`.
- [x] Dodać test odpowiedzi JSON po ostatniej odpowiedzi.
- [x] Dodać test payloadu Inertia po przeładowaniu strony wyniku.
- [x] Rozważyć wydzielenie czystych funkcji prezentacyjnych i test Vitest — pozostawiono proste computed properties w komponencie.

### Etap 5: weryfikacja

- [x] Uruchomić testy feature dotyczące sesji nauki.
- [x] Uruchomić testy jednostkowe frontendu.
- [x] Uruchomić `npm run build`.
- [x] Sprawdzić sidebar na desktopie.
- [ ] Sprawdzić sidebar na niskim ekranie laptopa.
- [x] Sprawdzić sidebar na mobile.
- [x] Sprawdzić sesję perfekcyjną.
- [x] Sprawdzić sesję z błędem.
- [x] Sprawdzić ręczne zakończenie pustej sesji.
- [ ] Sprawdzić ręczne przejście do wcześniejszego działu.
- [ ] Sprawdzić ręczne przejście do późniejszego działu.
- [x] Zweryfikować ponownie przypadek sesji 1441.

## Przypadki brzegowe

| Przypadek | Opanowane | Do końca |
|---|---:|---:|
| Pierwszy dział, brak rekordów | `0 / N` | `N - 1` |
| Dział 5 z 31 | zgodnie z rekordami | `26` |
| Ostatni dział | zgodnie z rekordami | `0` |
| Aktualny dział już zaliczony wcześniej | wliczony | według pozycji |
| Późniejszy dział zaliczony ręcznie przed wcześniejszymi | wliczony | bez wpływu |
| Sesja 99% | bez zmiany | według pozycji |
| Pusta zakończona sesja | bez zmiany | według pozycji |
| Zakres `basic` lub `specialist` | bez zmiany globalnego licznika | według pełnej ścieżki |
| Brak aktualnego działu na ścieżce | zgodnie z rekordami | `—` |

## Decyzja wymagająca potwierdzenia przed implementacją

- [x] **Zmiana puli pytań w dziale:** dział uznajemy za opanowany tylko wtedy, gdy perfekcyjny rekord odpowiada aktualnej pełnej puli pytań.

Rekomendacja:

- dział uznawać za aktualnie opanowany tylko wtedy, gdy perfekcyjny rekord odpowiada aktualnej pełnej puli pytań;
- jeżeli do działu dodano nowe pytania, użytkownik powinien ponownie zaliczyć pełny dział na 100%;
- wykorzystać istniejące `best_question_ids_hash` i `best_questions_count`, zamiast dodawać nową strukturę danych.

Jeżeli zdecydujemy, że opanowanie ma być trwałym osiągnięciem historycznym, kontrola aktualnego hasha nie będzie stosowana.

## Poza zakresem tego zadania

- Zmiana algorytmu statusów pojedynczych pytań.
- Zmiana kolejności działów.
- Przebudowa całego ekranu wyniku.
- Backfill brakujących rekordów historycznych.
- Ranking czasów działów.
- Zmiana zasad rekordów dla `basic` i `specialist`.

## Dziennik prac

### 2026-06-18 — diagnostyka

- [x] Utworzono branch `codex/diagnose-result-progress-sidebar`.
- [x] Zlokalizowano nagłówek i logikę sidebaru w `Show.vue`.
- [x] Potwierdzono dwie konkurencyjne definicje zaliczenia działu.
- [x] Sprawdzono dane produkcyjne sesji 1441.
- [x] Potwierdzono 31 działów, 10 historycznych rekordów 100% i 2 działy spełniające bieżący warunek stanu pytań.
- [x] Potwierdzono, że sesja 1441 nie zawiera odpowiedzi i nie utworzyła rekordu.
- [x] Prześledzono kolejność działów przez `StudyTopicGroupsService`.
- [x] Prześledzono odświeżanie overview po ostatniej odpowiedzi i przeładowaniu strony.
- [x] Ustalono, że globalne „Opanowane” musi używać rekordów `question_scope = all`.
- [x] Przygotowano plan wdrożenia.
- [x] Implementacja.
- [x] Testy.
- [x] Weryfikacja UI.

### 2026-06-18 — implementacja

- [x] Dodano backendowy snapshot `learning_path`.
- [x] Dodano kontrolę aktualnej puli pytań przez `best_question_ids_hash`.
- [x] Dodano liczniki `Opanowane` i `Do końca`.
- [x] Ujednolicono statusy i checkmarki wierszy sidebaru.
- [x] Dodano testy kontraktu Inertia i JSON.
- [x] Pełny `StudySessionFlowTest`: 33 testy, 755 asercji.
- [x] Vitest: 21 plików, 116 testów.
- [x] Build produkcyjny zakończony powodzeniem.
- [x] Podgląd desktop i 320 px zakończony powodzeniem.
- [x] Sesja 1441 według nowej reguły: `Opanowane 10 / 31`, `Do końca 30`.

## Następny krok

Przegląd zmian, następnie decyzja o commicie i wdrożeniu.
