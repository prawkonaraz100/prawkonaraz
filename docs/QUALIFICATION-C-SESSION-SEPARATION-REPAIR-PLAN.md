# Plan naprawy: rozdzielenie sesji kategorii C i kwalifikacji wstępnej przyspieszonej

Data: 2026-09-29  
Status: **usterka lokalna odtworzona i poprawiona, desktop/mobile zweryfikowane; commit i wdrożenie pozostają otwarte**  
Zakres: lokalne `/nauka`, kurs `qualification-c-accelerated`, sesje modułów i powtórki błędów; bez zmian w danych ani na produkcji na etapie diagnozy.

## Cel i kryterium końcowe

Po rozpoczęciu modułu kwalifikacji każdy następny krok — odpowiedź, zakończenie, wynik, powtórka i poprawa błędów — musi pozostać w **tej samej kolekcji pytań**. Zwykła nauka kategorii C musi zachować dotychczasowe zachowanie. Sama `license_category_id = C` nie identyfikuje kursu; granicą są `question_collection_id`, `question_module_id` i typ kontekstu sesji.

Usterka do odtworzenia: po zakończeniu modułu kwalifikacji przycisk „Powtórz ten dział od początku” otwiera zwykłą sesję C (`1 / 1497`) zamiast powtórki modułu. „Popraw błędne pytania” również korzysta ze zwykłych błędów C.

## Co już ustalono

- [x] Start modułu ma dedykowaną trasę i pobiera identyfikatory pytań z przypisań do modułu (`QuestionCollectionLearningController::start`, `StudySessionManager::startQuestionModule`).
- [x] Start powtórki błędów ma osobną trasę i korzysta z listy błędów kursu (`startIncorrectQuestions`, `startQuestionCollectionReview`).
- [x] Sesja kursowa zapisuje `question_collection_id`, opcjonalnie `question_module_id`, a także `payload.context.type` (`question_module` lub `question_collection_review`). Kategoria C jest tutaj technicznym powiązaniem, nie definicją zakresu pytań.
- [x] Odpowiedzi z prawidłowej sesji kursowej zapisują błędy kursu i nie zapisują zwykłego postępu C. Postęp modułu jest liczony z sesji tej kolekcji. Uwaga: obecne 100% oznacza przerobienie pytań, a nie 100% poprawnych odpowiedzi.
- [x] W stanie wyjściowym zakończenie zachowywało kontekst i adres powrotu do kursu, ale widok wyniku nie rozróżniał kursu od zwykłej nauki przy budowaniu akcji kontynuacji.
- [x] Przed naprawą `startFollowUpSession()` w `resources/js/Pages/StudySessions/Show.vue` wysyłał `license_category_id=C` do ogólnej trasy `study-sessions.store`, bez identyfikatorów kursu/modułu. W trybie `learn` bez działu menedżer wybiera całą aktywną pulę C, ignorując `question_count` jako limit zapytania. Powstała sesja nie miała powiązania z kwalifikacją.
- [x] W lokalnej bazie audyt `questions:preflight-collection qualification-c-accelerated` wykazał 14 modułów, 1323 unikalne pytania, 0 aktywnych pytań kolekcji i 0 błędów/ostrzeżeń. Pytania kursu nie są obecnie w aktywnej zwykłej puli C. To zależy od inwariantu `is_active = false`; ogólne zapytanie nie wyklucza kolekcji strukturalnie.
- [x] W bieżącym katalogu `tests` nie znaleziono testów obejmujących identyfikatory kolekcji/modułu ani pełny przepływ kwalifikacji.
- [x] Lokalny przykład: po sesji kursowej 2246 (kolekcja 30, moduł 34, 80 pytań) powstała zwykła sesja C 2247 (bez kolekcji i modułu, 319 pytań). Podobne błędne przejścia widać w sesjach 2242/2240 (1497 pytań). Większość sprawdzonych omyłkowych sesji ma 0 odpowiedzi, ale co najmniej jedna ma odpowiedzi. Bez pewnej reguły identyfikacji nie usuwamy ani nie przeklasyfikowujemy historii.
- [x] Przyczyna utrzymywania się błędu lokalnie: plik `public/build/manifest.json` i paczka JS były z godz. 14:56, sprzed zmian w `Show.vue`; nie działał lokalny hot reload. Sam `vue-tsc` nie aktualizował przeglądarki. Po `npm run build` wynik sesji 2246 pokazał „Powtórz ten moduł”, link błędów kursu i nie pokazał bocznego panelu 31 działów C.

## Docelowe zachowanie od początku do końca

| Krok | Zwykła nauka C | Moduł kwalifikacji | Powtórka błędów kwalifikacji |
| --- | --- | --- | --- |
| Wejście | Widok kategorii C | Widok kursu i wybranego modułu | Lista błędnych pytań danego kursu |
| Start | Ogólna trasa `study-sessions.store` | Dedykowana trasa startu modułu | Dedykowana trasa startu błędów kursu |
| Zakres pytań | Aktywna pula C i filtry działu | Tylko pytania przypisane do modułu | Tylko aktywna lista błędów tej kolekcji |
| Odpowiedzi | Zwykły postęp/statystyki C | Błędy i postęp kursu | Błędy i postęp tego samego kursu |
| Zakończenie | Wynik zwykłej nauki | Wynik modułu z kontekstem kursu | Wynik powtórki z kontekstem kursu |
| Ponowna nauka | Obecne akcje zwykłej nauki | Ponowny start **tego samego modułu** | Ponowny start **aktualnej listy błędów kursu** albo powrót do listy, gdy jest pusta |
| Powrót | Panel zwykłej nauki | Widok kursu | Lista błędów kursu |

Wynik powtórki błędów nie powinien zawierać przycisku „Powtórz ten dział”, ponieważ sesja może obejmować wiele modułów. Na wyniku modułu akcja poprawy błędów musi prowadzić do **kursowej** listy błędów (lub uruchamiać jej dedykowaną trasę), nigdy do ogólnego filtra C. Nie dodajemy nowego filtrowania błędów per moduł w ramach tej naprawy.

## Plan wykonania

### 0. Zabezpieczenie stanu — przed zmianą

- [x] Sprawdzić aktualny branch, bazę commita i `git status`; zachować istniejące niezacommitowane zmiany, zwłaszcza w `Show.vue` i widokach kursu. Stan wyjściowy: `main` / `af7673f`; istniejące zmiany oraz `output/` pozostawiono bez ingerencji.
- [x] Przygotować powtarzalny scenariusz bez konta produkcyjnego: testy tworzą użytkownika i dane w odizolowanej tymczasowej bazie SQLite.
- [x] Lokalny odczyt kontrolny: 1497 aktywnych pytań zwykłej C, 1323 unikalne pytania kolekcji kwalifikacji i 55 aktywnych błędów kursu na koncie QA w chwili pomiaru. Lista błędów może zmieniać się podczas równoległej nauki. Bez danych osobowych.

### 1. Kontrakt typu sesji i akcji wyniku

- [x] Ustalić po stronie serwera jednoznaczny typ sesji: `regular_category`, `course_module`, `course_review` na podstawie powiązań i kontekstu, również dla starych rekordów. Nie wywodzić go z samej kategorii C ani nazwy modułu w interfejsie.
- [x] Udostępnić widokowi wyniku właściwe URL-e dla danego typu. Istniejące trasy startu ponownie sprawdzają dostęp i przynależność modułu do kolekcji; lista błędów jest ustalana po stronie serwera przy starcie.
- [x] Zabezpieczyć ogólną akcję `startFollowUpSession()` przed uruchomieniem z sesji kursowej. Pozostawić ją bez zmian dla zwykłych sesji C/B i innych dotychczasowych trybów.
- [x] Dodać kontrakt `source_study_session_id` do przejść ogólnych i odmowę na serwerze, gdy źródłem jest kurs. Stary klient bez ID otrzymuje błąd z prośbą o odświeżenie strony; nie może już utworzyć zwykłej C z wyniku kursu.

### 2. Akcje desktopowe i mobilne

- [x] W `Show.vue` skierować „Powtórz moduł” do istniejącej trasy startu **tego samego** modułu. Nie tworzyć ogólnej sesji C.
- [x] „Pytania do poprawy w kursie” po module skierować do listy błędów tej kolekcji; istniejący widok listy blokuje start, gdy jest pusta.
- [x] Na wyniku powtórki błędów pokazać akcje właściwe kursowi: przejście do **aktualnej** listy błędów i powrót do kursu. Ponowny start odbywa się z listy; przy pustej liście przycisk startu jest niedostępny.
- [x] W kodzie poprawić etykiety powrotu i podpowiedzi wyniku dla modułu/kursu oraz obie wersje wyniku: mobilną i desktopową. Ręczna ocena wyglądu pozostaje otwarta w pkt 4.
- [x] Przycisk „Powtórz ten moduł” jest decyzją o ponownym starcie: jednym żądaniem z `replace_active_session=true` kończy ewentualną aktywną sesję i uruchamia wskazany moduł. Modal potwierdzenia usunięto po zgłoszeniu użytkownika; błąd serwera jest widoczny obok przycisku i pozwala ponowić próbę.

### 3. Granice danych i zgodność wsteczna

- [x] Ujednolicić rozpoznawanie obu typów sesji kursowych w kontrolerach, middleware i `StudySession::regularCategory()`, również dla starszego rekordu o `payload.context.type = question_collection_review` bez kolumn relacyjnych.
- [x] Sprawdzono bieżącą bazę: 0 aktywnych pytań przypisanych do kolekcji kwalifikacji trafia do zwykłej puli C. Ewentualne strukturalne wykluczenie po przyszłej zmianie `is_active` wymaga osobnej decyzji, ponieważ pytania mogą być współdzielone.
- [x] Nie modyfikować historycznych sesji automatycznie. Ewentualne czyszczenie błędnych zapisów C to osobna decyzja po audycie wpływu i przygotowaniu kopii danych.

### 4. Minimalna, celowana weryfikacja regresyjna

- [x] Test: start modułu → odpowiedzi → koniec → wynik → powtórka. Nowa sesja ma tę samą kolekcję i moduł, prawidłowe ID pytań; zwykły postęp C pozostaje bez zmian.
- [x] Test: błąd w module → lista błędów kursu → start powtórki → odpowiedź → wynik. Pusta lista blokuje ponowny start, bez uruchamiania zwykłej C.
- [x] Test: zwykła C i istniejąca ścieżka B zachowują ogólny start i powtórkę błędów.
- [x] Test: dostęp do kursu cofnięty, moduł nie należy do kolekcji, inna aktywna sesja, stary rekord z kontekstem tylko w `payload` — brak obejścia kontroli dostępu i brak przypadkowego startu C.
- [x] Sprawdzić ręcznie wynik i CTA na desktopie i telefonie. Wynik 2246 po nowym buildzie ma właściwe CTA, bez panelu 31 działów C; kliknięcie powtórki utworzyło sesję 2248 z tą samą kolekcją 30 i modułem 34. Wynik kursowej powtórki błędów 2251 ma linki do listy błędów kursu i kursu, bez ogólnych akcji C. Aktywna sesja kursowa lokalnie pokazała „Moduł 2.1, 1 / 164”.
- [x] Uruchomić celowane testy PHP, kontrolę składni zmienionych plików, `npm run build` (obejmuje `vue-tsc`) i `git diff --check`. Pełnego zestawu testów nie uruchamiano.

### 5. Wdrożenie i kontrola

- [ ] Po zielonej weryfikacji zrobić commit ograniczony do tej naprawy (bez przypadkowego dodania wcześniejszych zmian i `output/`).
- [ ] Przed wdrożeniem zapisać commit produkcyjny jako punkt cofnięcia i wykonać deploy zgodnie z dokumentacją projektu.
- [ ] Smoke test: moduł kwalifikacji, wynik, powtórka modułu, powtórka błędów, zwykła C, desktop/mobile. Sprawdzić brak nowego `1 / 1497` po akcji kursowej.
- [ ] Jeżeli smoke test nie przejdzie, przywrócić poprzedni commit aplikacji; nie kasować danych sesji w ramach rollbacku bez osobnej analizy.

## Definicja ukończenia (DoD)

Naprawa jest gotowa dopiero wtedy, gdy każda nowa sesja wywołana z wyniku kwalifikacji ma poprawny `question_collection_id` (oraz `question_module_id` dla modułu), pytania należą do właściwego zakresu, błędy i postęp kursu aktualizują się we właściwych tabelach, zwykłe C nie zmienia się wskutek tych akcji, a widoki końcowe nie oferują mylących przycisków. Testy regresyjne i smoke test muszą to potwierdzić.

## Mapa kodu

- `routes/web.php` — osobne trasy startu modułu i błędów kursu.
- `app/Http/Controllers/QuestionCollectionLearningController.php` — start sesji kursowych i adresy powrotu.
- `app/Http/Controllers/StudySessionController.php` — ogólny start, zakończenie i dane widoku wyniku.
- `app/Support/StudySessionManager.php` — dobór pytań, identyfikatory kursu, zapis odpowiedzi i wynik sesji.
- `app/Support/QuestionCollectionIncorrectQuestionService.php` — lista błędów kursu.
- `app/Support/QuestionCollectionProgressService.php` — postęp modułów.
- `app/Support/QuestionCollectionAccessService.php` i `app/Http/Middleware/EnsureStudySessionAccess.php` — rozpoznawanie sesji i kontrola dostępu.
- `app/Models/StudySession.php` — zakres „zwykłej kategorii” w zapytaniach/analityce.
- `resources/js/Pages/StudySessions/Show.vue` — wszystkie akcje i warianty ekranu końcowego.

## Dziennik postępu

| Data | Stan | Wynik |
| --- | --- | --- |
| 2026-09-29 | Diagnoza | Potwierdzono błąd w akcjach wyniku, poprawne rozdzielenie właściwych sesji kursowych i lokalny stan kolekcji. Bez zmian w kodzie i danych. |
| 2026-09-29 | Poprawka lokalna | Dodano typ sesji i adresy akcji z serwera, osobne przyciski kursowe na desktopie i telefonie oraz zgodność starszych powtórek. Zachowano wcześniejsze niezacommitowane zmiany użytkownika. Bez commita i deployu. |
| 2026-09-29 | Weryfikacja automatyczna | Pełny przebieg nowych testów: 4 scenariusze / 143 asercje. Po rozszerzeniu ponowiono osobno scenariusz zwykłej C (75 asercji) i granicy dostępu (10 asercji), oba zielone. Dwa istniejące testy zwykłej nauki: 104 asercje. `vue-tsc --noEmit`, składnia PHP i `git diff --check`: poprawne. Ręczny QA desktop/mobile pozostaje otwarty. |
| 2026-09-29 | Odtworzenie lokalne | Użytkownik pokazał wynik 2247 z boczną mapą C; baza potwierdziła, że to omyłkowo utworzona zwykła sesja po kursowej 2246. Stary bundle nie zawierał nowych akcji. Po buildzie wynik 2246 ma właściwy widok, a ponowny start modułu zachował jego kolekcję i moduł. Dodano kontrolę źródła sesji na serwerze i celowane asercje dla starego klienta. Bez ingerencji w historyczne dane. |
| 2026-09-29 | Weryfikacja końcowa lokalna | Testy kursowe: 4 scenariusze / 157 asercji, po finalnej blokadzie dwa kluczowe scenariusze ponownie zielone. Test zwykłej powtórki po zmianie kontraktu: 7 asercji, zielony. `npm run build`, składnia PHP i `git diff --check`: poprawne. Mobilny i desktopowy wynik sprawdzone w przeglądarce. Bez commita i deployu. |
| 2026-09-29 | Konflikt przy powtórce | Po zgłoszeniu komunikatu o synchronizacji dodano potwierdzenie zastąpienia innej aktywnej sesji na ekranie wyniku. Celowany test serwera: 1 scenariusz / 17 asercji, zielony. `npm run build` i `git diff --check`: poprawne. Nie kończono aktywnej sesji użytkownika podczas ręcznej kontroli. Bez commita i deployu. |
| 2026-09-29 | Powtórka jednym kliknięciem | Użytkownik odrzucił dodatkowy modal. Akcja wyniku wysyła teraz bezpośrednio `replace_active_session=true`; przycisk pokazuje stan wysyłania, a błąd pozostaje lokalny i nie blokuje kolejnej próby. Celowany test serwera: 1 scenariusz / 17 asercji, `npm run build` (z `vue-tsc`) i `git diff --check`: zielone. Aktywnej sesji użytkownika nie kończono podczas QA. Bez commita i deployu. |
| 2026-09-29 | Stan widoku po ponownym starcie | Po zgłoszeniu „strona się przeładowuje, ale nic poza tym” potwierdzono w bazie nową aktywną sesję właściwego modułu (2271). Otwarty widok `/nauka/teraz` nadal pokazywał stary wynik, a nowa karta pod tym samym adresem prawidłowo pokazała pytanie 1/117. Przyczyną było domyślne zachowanie stanu komponentu Inertia po POST do tej samej strony. Ustawiono `preserveState: 'errors'`: sukces montuje nowy widok, błąd zachowuje komunikat. Dodano asercję GET bieżącej sesji po starcie. Celowany test: 32 asercje, build i `git diff --check`: zielone. Bez modyfikacji sesji użytkownika przez QA. |

Po każdej zakończonej fazie aktualizować checkboxy i dopisać do dziennika: commit/PR, wykonane testy, wynik smoke testu oraz ewentualny punkt rollbacku. Nie oznaczać etapu jako zakończony na podstawie samej implementacji bez weryfikacji.
