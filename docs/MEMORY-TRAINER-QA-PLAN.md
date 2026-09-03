# Plan testow QA trenera pamieci

Data: 2026-05-11

## Cel

Zweryfikowac, ze `/trener-pamieci` dziala zgodnie z domknietym planem funkcjonalnym:

- dzienny cel wynosi 80 pytan,
- minimalny blok startowy nie wymusza pustej listy, kiedy realnych kandydatow jest mniej,
- `new_candidate_count` jest jawnie limitowany i widoczny w planie/telemetrii,
- trener pamieci korzysta z osobnej logiki `sr_review`, bez agregowania klasycznej nauki, Zen mode i egzaminu jako potwierdzonej pamieci,
- pytania nie sa ujawniane przed odpowiedzia, a po odpowiedzi wracaja wyjasnienia/adnotacje.

## Konta QA

Haslo dla wszystkich kont: `change-me-now`.

| Konto | Stan poczatkowy | Oczekiwany plan |
| --- | --- | --- |
| `qa-memory-new@local.test` | brak historii pytan w kat. B | `primary=0`, `seen_booster=0`, `new_candidate=15`, `recommended_question_count=15` |
| `qa-memory-small-due@local.test` | 5 pytan due w `user_question_progress` | `primary=5`, `seen_booster=0`, `new_candidate=15`, `recommended_question_count=20` |
| `qa-memory-booster@local.test` | 20 widzianych pytan z przyszlym terminem powtorki | `primary=0`, `seen_booster=20`, `new_candidate=15`, `recommended_question_count=35` |
| `qa-memory-partial-day@local.test` | 70 odpowiedzi `sr_review` dzisiaj | `completed_today_count=70`, `daily_remaining_count=10`, `new_candidate=3`, `recommended_question_count=3` |

## Scenariusze

1. Backend plan-first
   - Pobierz plan dla kazdego konta przez `ReviewPlannerService`.
   - Sprawdz `daily_target_count`, `completed_today_count`, `daily_remaining_count`, `candidate_source_counts`, `candidate_count`, `recommended_question_count`.

2. API kolejki
   - Dla kont QA wejdz w `/api/v1/me/review-queue?include_questions=0`.
   - Sprawdz, ze metadane planu nie wymagaja zaladowania pelnej listy pytan.
   - Dla wariantu z pytaniami sprawdz, ze `memory_signal.source` odpowiada zrodlom planu.

3. Widok `/trener-pamieci`
   - Zaloguj sie na kazde konto QA.
   - Sprawdz, ze okragly wskaznik pamieci pokazuje realny stan dziennego celu.
   - Sprawdz, ze komunikaty coacha nie sugeruja recznego wybierania listy pytan.
   - Dla konta `partial_day` sprawdz, ze widok pokazuje domykanie dnia, a nie kolejny blok 50/80.

4. Start sesji i telemetry
   - Rozpocznij sesje na `qa-memory-small-due@local.test`.
   - Sprawdz payload `study_sessions.payload.review_plan`.
   - Sprawdz event `review.session_started` w `review_trainer_events`, szczegolnie `candidate_source_counts` i `new_candidate_count`.

5. Reveal po odpowiedzi
   - W sesji `sr_review` odpowiedz na pytanie.
   - Przed odpowiedzia nie powinno byc poprawnej odpowiedzi, wyjasnienia ani adnotacji.
   - Po odpowiedzi powinny pojawic sie poprawna odpowiedz, wyjasnienie, asset i adnotacje, jezeli pytanie je ma.

## Wyniki biezace

Konta QA zostaly odtworzone lokalnie w bazie. Aktualne wyniki planera:

| Konto | `completed_today_count` | `daily_remaining_count` | `candidate_source_counts` | `recommended_question_count` |
| --- | ---: | ---: | --- | ---: |
| `qa-memory-new@local.test` | 0 | 80 | `primary=0`, `seen_booster=0`, `new_candidate=15` | 15 |
| `qa-memory-small-due@local.test` | 0 | 80 | `primary=5`, `seen_booster=0`, `new_candidate=15` | 20 |
| `qa-memory-booster@local.test` | 0 | 80 | `primary=0`, `seen_booster=20`, `new_candidate=15` | 35 |
| `qa-memory-partial-day@local.test` | 70 | 10 | `primary=0`, `seen_booster=0`, `new_candidate=3` | 3 |

## Status

- Plan funkcjonalny: domkniety.
- Konta QA: utworzone.
- Backend planner: zgodny z oczekiwaniami dla czterech stanow kontrolnych.
- Testy automatyczne: zaliczone.
- API/UI/telemetry smoke test: zaliczony.
- Do wykonania: opcjonalna powtorka manualna na swiezym seedzie przed merge/release.

## Log QA 2026-05-11

Automaty:

- `docker compose exec -T app php artisan test tests/Unit/Support/ReviewPlannerServiceTest.php tests/Feature/ReviewQueueTest.php tests/Feature/ReviewTrainerTelemetryTest.php tests/Feature/ApiSessionTest.php`
- Wynik: 49 testow, 742 asercje, wszystkie zielone.

Backend planner:

- `qa-memory-new@local.test`: `0/80`, plan `15`, `new_candidate=15`.
- `qa-memory-small-due@local.test`: `0/80`, plan `20`, `primary=5`, `new_candidate=15`.
- `qa-memory-booster@local.test`: `0/80`, plan `35`, `seen_booster=20`, `new_candidate=15`.
- `qa-memory-partial-day@local.test`: `70/80`, plan `3`, `daily_remaining_count=10`, `new_candidate=3`.

UI:

- `qa-memory-new@local.test` pokazuje okragly wskaznik `0 / 80`, `15 / 80 po sesji`, `15 pytań`.
- `qa-memory-small-due@local.test` pokazuje okragly wskaznik `0 / 80`, `20 / 80 po sesji`, `Pilne=5`, `Nowe=15`.
- Start sesji z `qa-memory-small-due@local.test` przenosi do `/nauka/teraz` i tworzy sesje `1 / 20`.
- Przed odpowiedzia w sesji nie widac wyjasnienia ani poprawnej odpowiedzi.
- Po odpowiedzi, po powrocie do odpowiedzianego pytania i kliknieciu `Pokaż wyjaśnienie`, widac wyjasnienie i grafike pomocnicza.
- `qa-memory-partial-day@local.test` pokazuje domykanie dnia: `70 / 80`, `73 / 80 po sesji`, `3 pytań`.

API:

- `GET /api/v1/me/review-queue?include_questions=0` zwraca `questions=[]` i nie ujawnia `preview_question_ids`.
- Po smoke tescie na `qa-memory-small-due@local.test` API pokazuje `completed_today_count=2`, `daily_remaining_count=78`, `primary=3`, `new_candidate=15`, `recommended_question_count=18`.
- `GET /api/v1/me/review-queue?include_questions=1` zwraca 18 pytan; pierwsze zrodla to `classic_progress`, nastepnie `new_candidate`.

Telemetry:

- Sesja `sr_review` dla `qa-memory-small-due@local.test`: `study_sessions.id=776`, `total_questions_count=20`.
- Event `review.session_started`: `review_trainer_events.id=65`.
- Payload sesji i eventu zawiera `planner_version=review-planner-v2`, `candidate_source_counts={primary:5, seen_booster:0, new_candidate:15}`, `new_candidate_count=15`, `recommended_question_count=20`.

Uwaga: konto `qa-memory-small-due@local.test` zostalo celowo poruszone smoke testem, wiec po tescie ma 2 odpowiedzi `sr_review` dzisiaj. Jezeli potrzebny jest ponownie czysty baseline, trzeba odtworzyc konta QA tym samym seedem.
