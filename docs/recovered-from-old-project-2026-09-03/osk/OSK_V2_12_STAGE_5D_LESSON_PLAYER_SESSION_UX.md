# OSK V2.12 — Etap 5D: sesja w LessonPlayerze

**Status:** zakończony technicznie i ręcznie zweryfikowany lokalnie; bez deployu.
**Data weryfikacji:** 2026-08-26.
**Branch implementacyjny:** `codex/osk-course-program-pilot-b`.

Etap 5D podłącza HTTP-owy cykl sesji z Etapu 5C do odizolowanego `LessonPlayer.vue` nowej **Nauki teorii OSK**. Nie zmienia obecnego modułu testów pod `/nauka` i nie nadaje sesji znaczenia formalnego ukończenia szkolenia.

## 1. Świadomy start i wznowienie

Wejście na lekcję nie rozpoczyna zapisu czasu. Player pokazuje bramkę `Gotowy do nauki?` i uruchamia `start/resume` dopiero po kliknięciu przez kursanta.

- pierwszy start pokazuje `Rozpocznij naukę`;
- jeśli ten sam enrollment ma otwartą sesję z tego samego klucza przeglądarki, serwer zwraca wznowienie i player pokazuje `Sesja wznowiona`;
- odświeżenie lub przejście do kolejnej lekcji nie wysyła automatycznego `close`, więc otwarta sesja może zostać wznowiona;
- zamknięcie jest wyłącznie świadome, przez `Zakończ sesję`.

Treść kroków i przyciski nawigacji są dostępne dopiero dla aktywnej sesji. To chroni granicę między samym odczytem programu a rozpoczętą nauką.

## 2. Klucz przeglądarki i idempotencja

Klient przechowuje losowy `client_session_key` w `localStorage` pod kluczem związanym z identyfikatorem enrollmentu. Wartość jest ograniczona do formatu akceptowanego przez backend i nie jest wyświetlana ani wysyłana do Inertia jako część treści lekcji.

Każdy heartbeat otrzymuje osobny `idempotency_key`. Jeśli request zakończy się błędem sieciowym po stronie klienta, ten sam klucz jest używany przy ponowieniu, aby odpowiedź utracona po drodze nie utworzyła drugiego rekordu źródłowego.

Jeśli `localStorage` jest niedostępny, player używa klucza tylko w pamięci bieżącej strony. Nie blokuje to nauki, ale kolejna karta nie może wtedy wznowić tej samej sesji z tego urządzenia.

## 3. Heartbeat i granice czasu

Po świadomym starcie player ustawia pojedynczy `setTimeout` zgodnie z `heartbeat_seconds` z odpowiedzi serwera. Po zakończeniu requestu planuje następne wywołanie, więc nie ma nakładających się interwałów.

- zwykły heartbeat jest wysyłany zgodnie z polityką V1: co 60 sekund;
- powrót karty na pierwszy plan może wywołać jedno wcześniejsze sprawdzenie, które backend może oznaczyć jako `THROTTLED`;
- błąd sieci powoduje ponowienie nie częściej niż co 30 sekund i nie zmienia czasu po stronie klienta;
- `SESSION_CLOSED` zatrzymuje timer i blokuje dalszą naukę do ponownego startu;
- `401`/`419` zatrzymuje ponowienia, aby wygasłe logowanie nie generowało ruchu bez końca;
- unmount komponentu czy zamknięcie karty nie próbuje zgadywać czasu ani wysyłać best-effort zamknięcia.

Źródłem czasu pozostaje `LearningSessionService` i zegar serwera. Timer w Vue jest wyłącznie mechanizmem wywołania heartbeat-u, nie licznikiem formalnych minut.

## 4. Obsługa stanów UI

| Stan | Zachowanie playera |
| --- | --- |
| `idle` | bramka startu, brak zapisu czasu |
| `starting` | przycisk zablokowany, oczekiwanie na odpowiedź |
| `active` | kroki i nawigacja dostępne, heartbeat zaplanowany |
| `closing` | przyciski sesji zablokowane do odpowiedzi |
| `closed` | treść ukryta, dostępne ponowne rozpoczęcie |
| `error` | neutralny komunikat, brak automatycznej pętli retry startu |

Przejściowy błąd sieci zachowuje aktywną lekcję i pokazuje ostrzeżenie, ale nie dopisuje lokalnego czasu. Nieprawidłowy payload sesji kończy się stanem błędu zamiast optymistycznego kontynuowania.

## 5. Granice architektoniczne

- kontroler nadal nie zapisuje bezpośrednio do bazy; zapis pozostaje w `LearningSessionService`;
- endpointy są przekazane z Laravelu jako URL-e, a Vue nie buduje routingu na podstawie identyfikatorów;
- player korzysta z istniejącego `apiClient`, więc zachowuje CSRF i wspólną obsługę odpowiedzi JSON;
- nie dodano postępu, ukończenia lekcji, `ModuleAssessment`, `TheoryCompletionGate` ani formalnego evidence;
- nie zmieniono `StudySession`, `QuestionCollection`, checkoutu B2C ani istniejącego `/nauka`;
- nie dodano workera, crona ani globalnego timera.

## 6. Weryfikacja lokalna

- `TheoryLearningPlayerTest` oraz `LearningSessionFoundationTest`: **20 testów / 172 asercje**;
- pełny pakiet `tests/Feature/Osk`: **49 testów / 345 asercji**;
- regresja istniejącej nauki, kolekcji, B2C access, checkoutu i audytu: **72 testy / 1265 asercji**;
- `docker compose exec -T vite npm run build`: przechodzi (`vue-tsc` + `vite build`);
- Pint dla zmienionego kontrolera i testu: przechodzi;
- `git diff --check`: przechodzi;
- środowisko działa lokalnie w Dockerze; brak deployu.

### Ręczny smoke test — 2026-08-26

Test wykonano na dwóch lokalnych enrollmentach utworzonych wyłącznie na potrzeby smoke testu. Nie użyto seeda ani danych produkcyjnych.

- samo wejście na lekcję nie wysłało `POST start` i pokazało świadomą bramkę `Gotowy do nauki?`;
- kliknięcie startu utworzyło sesję (`201`), a heartbeat-y wracały z `200`;
- odświeżenie strony wznowiło tę samą sesję (`200`) i pokazało `Sesja wznowiona`;
- przejście z pierwszej do drugiej lekcji nie wysłało automatycznego `close`, a druga lekcja mogła wznowić aktywną sesję;
- timeout backendu zwrócił `409 SESSION_CLOSED`, po czym UI ukryło treść i zatrzymało timer;
- jawne `Zakończ sesję` zwróciło `200`, UI pokazało `Sesja zakończona`, a kolejne heartbeat-y nie były wysyłane;
- lokalny mock `503` pokazał komunikat `Połączenie jest chwilowo niedostępne. Spróbujemy ponownie.` i zachował sesję aktywną;
- lokalny mock `401` zatrzymał sesję po stronie UI i pokazał komunikat o ponownym logowaniu;
- po usunięciu mocków i końcowym odświeżeniu konsola przeglądarki miała **0 błędów**; globalne menu, nagłówek i stopka renderowały się poprawnie.

Smoke test ujawnił konflikt nazwy payloadu Inertia: lokalne dane nawigacji lekcji były przekazywane jako `navigation` i nadpisywały współdzielone globalne `navigation` używane przez `SiteHeader`. Payload został przemianowany na `lesson_navigation`, a test kontraktowy sprawdza teraz obecność obu struktur.

## 7. Następny krok

Warstwa cyklu sesji jest gotowa technicznie do dalszego projektowania lokalnego. Następny bezpieczny krok to osobny kontrakt postępu i evidence, bez dopisywania formalnego ukończenia, egzaminu ani zmian w istniejącym `/nauka`. Przed jakimkolwiek pilotem nadal potrzebne są decyzja o zatwierdzonej treści, feature fladze, ograniczonym rolloucie i akceptacji compliance. Etap 6 (PAPER) pozostaje kolejnym biznesowym vertical slice po tych decyzjach.
