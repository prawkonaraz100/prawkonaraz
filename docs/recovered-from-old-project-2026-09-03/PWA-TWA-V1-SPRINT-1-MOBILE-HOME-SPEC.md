# PWA/TWA v1 - Sprint 1 Mobile Home `/nauka` spec

## Status dokumentu

Status: spec wykonawczy przed kodowaniem.

Cel dokumentu: zamienic ustalenia z raportu i backlogu PWA/TWA na konkretna specyfikacje pierwszego sprintu implementacyjnego. Ten sprint dotyczy tylko mobilnego startu aplikacji na `/nauka`.

Nie jest to jeszcze spec service workera, TWA ani pelnego mobile API.

## Dokumenty zrodlowe

- [PWA-NAUKA-MOBILE-RAPORT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-NAUKA-MOBILE-RAPORT.md)
- [PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md)
- [PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md)
- [MOBILE-NAUKA-DASHBOARD-REDESIGN-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MOBILE-NAUKA-DASHBOARD-REDESIGN-PLAN.md)
- [MOBILE-VIEWS-IMPLEMENTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MOBILE-VIEWS-IMPLEMENTATION-PLAN.md)

## Kod, ktory dotyczy sprintu

- [app/Http/Controllers/SessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/SessionPageController.php)
- [app/Http/Controllers/StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)
- [app/Http/Requests/StudySessionStoreRequest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/StudySessionStoreRequest.php)
- [app/Support/StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)
- [resources/js/Pages/Session/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue)
- [resources/js/Pages/Session/Partials/MobileLearningDashboard.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Partials/MobileLearningDashboard.vue)

## Cel Sprintu 1

Zrobic z `/nauka` prawdziwy mobilny ekran startowy aplikacji:

- pokazuje prawdziwy stan uzytkownika,
- pozwala wrocic do aktywnej sesji,
- nie udaje statystyk placeholderami,
- nie nadpisuje aktywnej sesji bez swiadomej decyzji,
- zachowuje obecny webowy core `/study-sessions`,
- przygotowuje grunt pod PWA/TWA bez service workera i bez przepisywania playera.

## Poza zakresem Sprintu 1

- manifest PWA,
- service worker,
- offline answers,
- IndexedDB,
- TWA wrapper,
- Android package,
- pelny `GET /api/v1/me/learning-home`,
- pelne ujednolicenie `/api/v1/sessions` z `/study-sessions`,
- przebudowa `StudySessions/Show.vue`,
- nowy natywny klient mobile,
- ciezkie metryki czasu nauki i tygodniowej aktywnosci, jesli nie sa tanie backendowo,
- redesign desktopowego `/nauka`.

## Obecny stan

### Backend `/nauka`

`SessionPageController` renderuje `Session/Index` i przekazuje dzisiaj:

- `category`,
- `filters`,
- `group_options`,
- `status_options`,
- `access.full_product`,
- `learning_overview.due_review_count`,
- `ranking_preview`,
- `pjm_module`,
- `friend_invitation_cta`.

Brakujacy element dla PWA/TWA: `active_session`.

### Frontend `/nauka`

`Session/Index.vue`:

- trzyma formularz startu sesji,
- wylicza `recommendedStep`,
- przekazuje propsy do `MobileLearningDashboard.vue`,
- obsluguje start nauki przez webowy `POST /study-sessions`.

`MobileLearningDashboard.vue`:

- jest juz realnie wpiety jako mobilny dashboard,
- ma hero i bottom sheet wyboru trybu,
- ma szybkie sciezki: klasyczna, zen, egzamin, trener, znaki, ranking, PJM,
- liczy czesc progresu z prawdziwych danych `group_options`,
- ma jednak placeholdery produkcyjne.

### Placeholdery do usuniecia albo ukrycia

W `MobileLearningDashboard.vue` nie moga zostac jako produkcyjne dane:

- `weeklyProgressBars`,
- `weeklyProgressLabels`,
- statyczny lub pusty `czas nauki`,
- `Dzis, 14:32`,
- `85%` przy ostatniej aktywnosci,
- sekcja `Ostatnia aktywnosc`, jesli nie dostanie realnego backendowego obiektu.

W `Session/Index.vue` desktopowe placeholdery typu `--` / `--:--` moga zostac poza zakresem tylko wtedy, gdy nie sa eksponowane w mobilnym PWA home jako realna statystyka.

## Decyzje Sprintu 1

| ID | Decyzja | Status |
| --- | --- | --- |
| S1-D1 | `active_session` w Sprincie 1 obejmuje tylko `study_sessions` | przyjete |
| S1-D2 | aktywne sesje znakow drogowych nie wchodza do `active_session` v1 | przyjete |
| S1-D3 | start nowej sesji przy aktywnej sesji wymaga potwierdzenia | przyjete |
| S1-D4 | `weekly_activity` jest ukryte, jesli nie ma realnego backendu | przyjete |
| S1-D5 | `study_time` jest ukryte, jesli nie ma realnego backendu | przyjete |
| S1-D6 | `recent_learning_activity` jest opcjonalne; brak danych oznacza ukrycie sekcji | przyjete |
| S1-D7 | nie zmieniamy payloadu `StudySessionStoreRequest` poza minimalna obsluga intencji, jesli bedzie potrzebna | przyjete |
| S1-D8 | Play/TWA nie pokazuje checkout/pricing CTA w app shell | przyjete jako wymog, mechanizm do potwierdzenia |

## Kontrakt danych Inertia

Sprint 1 powinien dodac do `Session/Index` nowy obiekt:

```json
{
  "learning_dashboard": {
    "schema_version": 1,
    "active_session": null,
    "course_progress": {
      "category_id": 1,
      "answered_questions": 120,
      "unanswered_questions": 580,
      "incorrect_questions": 18,
      "correct_questions": 102,
      "total_questions": 700,
      "percent": 17
    },
    "review": {
      "due_count": 4
    },
    "recent_learning_activity": null,
    "weekly_activity": null,
    "study_time": null,
    "access_ui": {
      "can_show_pricing_link": true,
      "purchase_mode": "web"
    }
  }
}
```

Uwagi:

- `learning_overview` zostaje w Sprincie 1 dla kompatybilnosci.
- `ranking_preview`, `pjm_module` i `friend_invitation_cta` moga zostac jako osobne propsy.
- `course_progress` moze byc na start wyliczane z tych samych zrodel, z ktorych dzisiaj korzysta frontend, ale powinno miec jeden jawny ksztalt.
- `weekly_activity` i `study_time` sa `null`, dopoki nie sa prawdziwe.
- UI nie renderuje sekcji metryki, jezeli odpowiadajace pole jest `null`.

## `active_session` shape

Gdy istnieje aktywna sesja `study_sessions.status = in_progress`, backend zwraca:

```json
{
  "id": 123,
  "mode": "learn",
  "ui_shell": "exam_like",
  "status": "in_progress",
  "title": "Nauka klasyczna",
  "subtitle": "Kategoria B",
  "category": {
    "id": 1,
    "code": "B",
    "short_name": "B"
  },
  "progress": {
    "answered": 6,
    "remaining": 14,
    "total": 20,
    "percent": 30,
    "current_question_number": 7
  },
  "filters": {
    "question_topic_id": 12,
    "question_scope": "all",
    "question_status": "unanswered",
    "randomize_order": false,
    "question_count": 20
  },
  "started_at": "2026-07-06T10:00:00+02:00",
  "updated_at": "2026-07-06T10:08:00+02:00",
  "resume_url": "/nauka/teraz",
  "can_replace": true,
  "replace_warning": "Masz aktywna sesje. Rozpoczecie nowej zakonczy obecna."
}
```

### Mapowanie tytulow

| Warunek | `title` |
| --- | --- |
| `mode=exam` | `Egzamin probny` |
| `mode=sr_review` | `Trener pamieci` |
| `mode=pjm` | `PJM` |
| `mode=learn` i `ui_shell=zen` | `Zen mode` |
| `mode=learn` | `Nauka klasyczna` |
| `mode=quick` | `Szybka seria` |
| `mode=hard` | `Trudne pytania` |

### Zrodlo prawdy dla progresu

Rekomendacja:

- uzyc `StudySessionManager::activeSessionForUser()`,
- uzyc tych samych metod progresu, ktore zasila `StudySessionController::current`,
- nie liczyc progresu z DOM ani lokalnego frontendu,
- nie pobierac calego payloadu pytan dla samego kafla resume.

## UX mobilnego home

### Priorytet ekranu

Kolejnosc widokow w mobile home:

1. Aktywna sesja, jesli istnieje.
2. Rekomendowany krok.
3. Quick actions.
4. Progres kursu, tylko z realnych danych.
5. Trener pamieci strip, jesli `due_count > 0`.
6. Ostatnia aktywnosc, tylko jesli `recent_learning_activity` jest realne.

### Aktywna sesja

Kafel aktywnej sesji musi miec:

- tytul trybu,
- kategorie,
- progres `answered / total`,
- pasek procentowy,
- przycisk `Wroc do sesji`,
- drugorzedna akcje `Rozpocznij nowa`, jesli user wybral nowy tryb.

Klik `Wroc do sesji`:

- robi zwykly link do `/nauka/teraz`,
- nie wykonuje `POST /study-sessions`,
- nie zmienia obecnej sesji.

### Start nowej sesji przy aktywnej sesji

Jesli istnieje `active_session`, kazda akcja startujaca nowa sesje musi przejsc przez potwierdzenie.

Minimalny copy:

- Tytul: `Masz aktywna sesje`
- Tresc: `Rozpoczecie nowej sesji zakonczy obecna. Mozesz tez wrocic do aktualnej nauki.`
- Primary: `Rozpocznij nowa`
- Secondary: `Wroc do sesji`
- Cancel: `Anuluj`

Po potwierdzeniu frontend moze uzyc obecnego `POST /study-sessions`, bo backend juz zamyka poprzednie `in_progress`. UI musi jednak pokazac to jako swiadoma decyzje uzytkownika.

### Brak aktywnej sesji

Gdy `active_session = null`:

- rekomendowany krok zachowuje obecna logike,
- hero klasycznej nauki i bottom sheet dzialaja jak dzisiaj,
- nie pokazujemy pustego kafla resume.

### Brak pelnego dostepu

W trybie web:

- mozna pokazac `Aktywuj pelna nauke`, jezeli `access_ui.can_show_pricing_link = true`.

W trybie Play/TWA:

- nie pokazujemy pricing/checkout CTA,
- pokazujemy neutralny stan dostepu,
- CTA prowadzi co najwyzej do informacji o koncie albo instrukcji aktywacji poza aplikacja, jezeli to zostanie zatwierdzone produktowo.

Mechanizm wykrycia `purchase_mode` zostaje do zatwierdzenia przed kodowaniem.

## Backend tasks

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S1-BE-1 | Dodac `learning_dashboard` do payloadu `/nauka` | `SessionPageController.php` | Inertia dostaje obiekt ze `schema_version` |
| S1-BE-2 | Dodac `active_session` summary | `SessionPageController.php`, `StudySessionManager.php` albo nowy presenter | user z `in_progress` widzi id, mode, progress, resume_url |
| S1-BE-3 | Nie pobierac calej sesji pytan dla kafla resume | presenter | payload `/nauka` zostaje lekki |
| S1-BE-4 | Dodac `access_ui` | `SessionPageController.php` | frontend zna `can_show_pricing_link` i `purchase_mode` |
| S1-BE-5 | Zachowac stare propsy | `SessionPageController.php` | desktop i obecne mobile nie wymagaja jednorazowego przepiecia wszystkiego |

Rekomendowany wariant implementacji: maly presenter, np. `LearningDashboardPresenter`, zamiast rozbudowywania kontrolera o dlugie metody.

## Frontend tasks

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S1-FE-1 | Dodac typy `LearningDashboard` i `ActiveSession` | `Session/Index.vue`, `MobileLearningDashboard.vue` | TypeScript zna nowy kontrakt |
| S1-FE-2 | Przekazac `learning_dashboard` do mobile dashboardu | `Session/Index.vue` | komponent nie dostaje danych przez nowe lokalne placeholdery |
| S1-FE-3 | Dodac kafel aktywnej sesji | `MobileLearningDashboard.vue` | `Wroc do sesji` linkuje do `/nauka/teraz` |
| S1-FE-4 | Dodac confirmation flow dla startu nowej sesji | `Index.vue`, `MobileLearningDashboard.vue` | start nowej sesji przy `active_session` wymaga potwierdzenia |
| S1-FE-5 | Usunac/ukryc placeholdery | `MobileLearningDashboard.vue` | brak `Dzis, 14:32`, `85%`, fake tygodnia jako danych usera |
| S1-FE-6 | Uporzadkowac stan braku dostepu pod Play/TWA | `MobileLearningDashboard.vue` | brak checkout CTA, gdy `can_show_pricing_link = false` |
| S1-FE-7 | Zachowac obecne quick actions | `MobileLearningDashboard.vue` | klasyczna, zen, egzamin, trener, znaki, ranking, PJM nadal sa osiagalne |

## Minimalne testy backendowe

Nowy lub rozszerzony test feature powinien sprawdzic:

- `/nauka` bez aktywnej sesji zwraca `learning_dashboard.active_session = null`,
- `/nauka` z aktywna sesja zwraca `active_session.id`,
- `active_session.resume_url = /nauka/teraz`,
- progres ma `answered`, `remaining`, `total`, `percent`,
- sesja innego usera nie pojawia sie w payloadzie,
- aktywna sesja `sr_review` poza zakresem zostaje obsluzona zgodnie z obecna logika `retireOutOfScopeReviewSessionsForUser`,
- `weekly_activity = null`, gdy nie ma realnego backendu,
- `study_time = null`, gdy nie ma realnego backendu.

Sugerowany plik: `tests/Feature/LearningHomeDashboardTest.php`.

## Minimalne testy frontendu/QA

Przed zamknieciem sprintu:

- `npm run build`,
- test manualny `/nauka` na 360x740,
- test manualny `/nauka` na 390x844,
- test manualny `/nauka` na 430x932,
- test usera bez aktywnej sesji,
- test usera z aktywna sesja learn,
- test usera z aktywna sesja exam,
- test usera z brakiem pelnego dostepu,
- test PJM starter mode,
- test, ze `Wroc do sesji` nie wysyla POST,
- test, ze `Rozpocznij nowa` pokazuje potwierdzenie.

## Kontrola placeholderow

Przed merge nalezy uruchomic:

```powershell
rg -n "Dzi.s, 14:32|85%|weeklyProgressBars|weeklyProgressLabels|czas nauki|--:--" resources/js/Pages/Session
```

Wynik moze pokazywac tylko miejsca swiadomie ukryte albo desktopowe poza zakresem. Nie moze pokazywac produkcyjnej mobilnej statystyki udajacej realny stan usera.

## Definition of Done

Sprint 1 jest gotowy, gdy:

- `/nauka` ma `learning_dashboard`,
- `/nauka` ma `active_session` summary,
- aktywna sesja jest pierwszoplanowa w mobile home,
- start nowej sesji przy aktywnej sesji wymaga potwierdzenia,
- mobile dashboard nie pokazuje fake metryk,
- brak realnych `weekly_activity`, `study_time`, `recent_learning_activity` skutkuje ukryciem sekcji,
- user moze dalej uruchomic wszystkie obecne sciezki nauki,
- desktop nie ma regresji funkcjonalnej,
- testy backendowe dla payloadu przechodza,
- build frontendu przechodzi,
- manual QA na 360/390/430 nie pokazuje nachodzenia tekstu ani rozjechanego bottom sheetu.

## Ryzyka

| Ryzyko | Skutek | Ograniczenie |
| --- | --- | --- |
| za duzo metryk w `learning_dashboard` | wolniejszy `/nauka` | Sprint 1 tylko lekki payload |
| brak potwierdzenia startu nowej sesji | user traci aktywny kontekst | confirmation flow |
| ukrycie placeholderow zostawi pusty ekran | mobile home bedzie zbyt ubogi | aktywna sesja + recommended step + quick actions zostaja core |
| Play/TWA pokaze checkout | ryzyko polityk Google Play | `can_show_pricing_link` |
| presenter zacznie dublowac logike playera | rozjazd kontraktu | korzystac z `StudySessionManager`, nie kopiowac logiki pytan |

## Open questions przed kodowaniem

1. Jak ustawiamy `purchase_mode`: config/env, feature flag, czy wykrycie standalone/TWA?
2. Czy `recent_learning_activity` wchodzi do Sprintu 1, czy ukrywamy sekcje do Sprintu 4?
3. Czy `active_session` dla `exam` ma pokazywac pozostaly czas juz na `/nauka`, czy tylko progres liczbowy?
4. Czy potwierdzenie startu nowej sesji ma byc modalem globalnym, czy bottom sheetem w `MobileLearningDashboard`?
5. Czy w Sprincie 1 tworzymy presenter jako osobna klase, czy prywatne metody w `SessionPageController` i ekstrakcja w Sprincie 4?

## Rekomendacja startu implementacji

Najbezpieczniejsza kolejnosc:

1. Test backendowy dla `learning_dashboard.active_session`.
2. Minimalny presenter `learning_dashboard`.
3. Podpiecie typow w `Session/Index.vue`.
4. Kafel aktywnej sesji w `MobileLearningDashboard.vue`.
5. Usuniecie/ukrycie placeholderow.
6. Confirmation flow dla startu nowej sesji.
7. Mobile QA 360/390/430.
