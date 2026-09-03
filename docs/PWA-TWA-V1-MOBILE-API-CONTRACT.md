# PWA/TWA v1 - Mobile API Contract

## Status dokumentu

Status: kontrakt czesciowo wdrozony w Sprintach 2-5.

Wdrozone w Sprincie 2:

- `GET /api/v1/sessions/current`,
- `GET /api/v1/sessions/current/questions`,
- `GET /api/v1/sessions/current/questions/{question}`,
- `POST /api/v1/sessions/current/answers`,
- `POST /api/v1/sessions/current/complete`,
- pelne filtry startu API zgodne z webowym `/study-sessions`,
- alias startu `license_category_id -> category_id`,
- kanoniczne `selected_answer` z legacy aliasem `user_answer`,
- konflikt `ACTIVE_SESSION_EXISTS`, gdy klient jawnie wysle `replace_active_session=false`.

Wdrozone w Sprincie 3:

- `GET /api/v1/me/learning-home`,
- wspolny `LearningHomePayloadBuilder` dla webowego `/nauka` i API mobile,
- mobilne `modes` opisujace tryby startu,
- mobilne `actions` z URL-ami API i web,
- semantyczny blad `LEARNING_ACCESS_REQUIRED` dla uzytkownika bez dostepu.

Wdrozone w Sprincie 4:

- bogatszy response `POST /api/v1/sessions/current/answers` i `POST /api/v1/sessions/{studySession}/answers`,
- envelope `answer`, `session`, `progress`, `completed`, `next_question`, `next_question_number`,
- reveal po odpowiedzi dla `learn`, `pjm`, `sr_review`,
- `correct_answer_text` oraz visual explanation asset/annotations w API answer response,
- kompatybilnosc wsteczna plaskich pol `accepted`, `question_id`, `session_status`.

Wdrozone w Sprincie 5:

- `POST /api/v1/sessions/current/exam-state`,
- synchronizacja fazy egzaminu przez API,
- akcja `start-answer`,
- payload `exam_ui`, `current_question`, `current_question_number`, `completed`, `redirect`,
- test kontraktowy dla mobilnego exam-state.

Cel dokumentu: opisac czysty kontrakt API dla PWA/TWA i przyszlego klienta mobile, na podstawie realnego kodu `/nauka`, webowego `/study-sessions` i obecnego `/api/v1/sessions`.

Ten dokument nie oznacza, ze PWA v1 musi od razu przepisac player na API. PWA v1 moze dalej korzystac z tras web/Inertia. Kontrakt API jest potrzebny, zeby backend mial jeden jasny kierunek przed TWA, offline-lite i ewentualnym natywnym klientem.

## Dokumenty zrodlowe

- [PWA-NAUKA-MOBILE-RAPORT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-NAUKA-MOBILE-RAPORT.md)
- [PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md)
- [PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md)
- [PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md)
- [API-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/API-SPEC.md)

## Kod zrodlowy kontraktu

- [routes/web.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/routes/web.php)
- [app/Http/Controllers/SessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/SessionPageController.php)
- [app/Http/Controllers/StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)
- [app/Http/Controllers/StudySessionAnswerController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionAnswerController.php)
- [app/Http/Controllers/ApiStudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/ApiStudySessionController.php)
- [app/Http/Controllers/ApiStudySessionAnswerController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/ApiStudySessionAnswerController.php)
- [app/Http/Controllers/ApiLearningHomeController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/ApiLearningHomeController.php)
- [app/Http/Requests/StudySessionStoreRequest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/StudySessionStoreRequest.php)
- [app/Http/Requests/ApiStudySessionStoreRequest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/ApiStudySessionStoreRequest.php)
- [app/Http/Requests/StoreStudySessionAnswerRequest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/StoreStudySessionAnswerRequest.php)
- [app/Http/Requests/ApiStudySessionAnswerStoreRequest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/ApiStudySessionAnswerStoreRequest.php)
- [app/Support/LearningHomePayloadBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/LearningHomePayloadBuilder.php)
- [app/Support/StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)
- [app/Support/StudySessionApiPayloadBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionApiPayloadBuilder.php)
- [tests/Feature/ApiLearningHomeTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/ApiLearningHomeTest.php)
- [tests/Feature/ApiSessionTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/ApiSessionTest.php)
- [tests/Feature/StudySessionFlowTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/StudySessionFlowTest.php)

## Glowne ustalenie

Obecne `/api/v1/sessions` jest dobra baza techniczna, bo korzysta z tego samego `StudySessionManager`. Po Sprintach 2-5 mobile ma juz bootstrap `/nauka`, kanoniczny tor start/current/answer/complete, reveal po odpowiedzi dla glownego flow nauki oraz synchronizacje stanu egzaminu. Nie jest to jeszcze pelny docelowy kontrakt offline, bo offline-lite i trwala idempotencja nadal wymagaja dopracowania.

Pozostale najwazniejsze braki:

- brak jawnej idempotencji mobilnej przez `client_answer_id` / `client_operation_id`,
- trzeba rozszerzyc semantyczne kody bledow poza `LEARNING_ACCESS_REQUIRED` i `ACTIVE_SESSION_EXISTS`.

## Zasady kontraktu v1

1. PWA/TWA v1 jest online-first.
2. Offline zapis odpowiedzi nie wchodzi do v1.
3. Prywatne odpowiedzi, sesje i progres nie sa cache'owane przez service worker.
4. `GET /api/v1/me/learning-home` nie moze byc za `product.access`, bo musi umiec powiedziec, ze dostepu brakuje.
5. Endpointy faktycznej nauki moga zostac za `product.access`.
6. PWA/TWA v1 uzywa cookie/session/CSRF jak web, nie Bearer tokenow.
7. `selected_answer` jest kanoniczna nazwa pola odpowiedzi.
8. `user_answer` zostaje legacy aliasem w API.
9. `category_id` jest kanoniczna nazwa kategorii w API.
10. `license_category_id` moze byc legacy aliasem przy starcie sesji, zeby latwo mapowac webowy formularz.
11. `API-SPEC.md` jest dzis czesciowo historyczne i trzeba je zaktualizowac po zatwierdzeniu tego dokumentu.

## Stan obecny endpointow

| Endpoint | Status | Problem mobile |
| --- | --- | --- |
| `POST /api/v1/sessions` | wdrozone Sprint 2 | ma pelne filtry i `replace_active_session`; brak trwalej idempotencji |
| `GET /api/v1/sessions/current` | wdrozone Sprint 2 | zwraca `200` z `session=null`, gdy brak aktywnej sesji |
| `GET /api/v1/sessions/current/questions` | wdrozone Sprint 2 | batch po `ids[]`; window `from/limit` mozna dodac pozniej |
| `GET /api/v1/sessions/current/questions/{question}` | wdrozone Sprint 2 | pojedyncze pytanie aktywnej sesji |
| `POST /api/v1/sessions/current/answers` | wdrozone Sprint 4 | `answer/session/progress/completed`, reveal dla nauki i trenera; brak trwalej idempotencji |
| `POST /api/v1/sessions/current/complete` | wdrozone Sprint 2 | konczy aktywna sesje i zwraca `sessionDetails` |
| `POST /api/v1/sessions/current/exam-state` | wdrozone Sprint 5 | synchronizacja fazy egzaminu, `start-answer`, payload `exam_ui` |
| `GET /api/v1/sessions/{studySession}` | istnieje | kompatybilnosc po id |
| `POST /api/v1/sessions/{studySession}/answers` | istnieje | obsluguje `selected_answer` i legacy `user_answer` |
| `POST /api/v1/sessions/{studySession}/complete` | istnieje | OK jako baza |
| `GET /api/v1/me/learning-home` | wdrozone Sprint 3 | bootstrap mobile home `/nauka`, bez `product.access` |
| `GET /api/v1/me/dashboard` | istnieje | nie jest home `/nauka`, jest za innym celem |
| `GET /api/v1/me/review-queue` | istnieje | dobry osobny kontrakt dla trenera |
| `GET /api/v1/ranked/*` | istnieje | osobna domena rankingu |

## Rozjazd web vs API

| Obszar | Web `/study-sessions` i `/nauka/teraz` | Obecne API | Decyzja |
| --- | --- | --- | --- |
| Start sesji | `license_category_id`, pelne filtry | `category_id`, `mode`, `question_count` | API dostaje pelne filtry, zachowuje `category_id` |
| Tryby | `exam`, `learn`, `review`, `sr_review`, `hard`, `quick`, `pjm` | bez `pjm` | core v1 bez PJM API; PJM osobna decyzja |
| UI shell | `exam`, `exam_like`, `zen` | brak | dodac |
| Dzial | `question_topic_id` | brak | dodac |
| Scope | `all`, `basic`, `specialist` | brak | dodac |
| Status pytan | `all`, `unanswered`, `memorized`, `incorrect`, `correct` | brak | dodac |
| Kolejnosc | `randomize_order` | brak | dodac |
| Aktywna sesja | `/nauka/teraz` wybiera aktywna | brak | dodac `sessions/current` |
| Batch pytan | `/nauka/teraz/pytania?ids[]=` | brak | dodac `current/questions` |
| Odpowiedz request | `selected_answer` | `user_answer` | `selected_answer` kanoniczne, `user_answer` alias |
| Odpowiedz response | `answer`, `session`, `progress`, reveal, next | wdrozone Sprint 4 | utrzymac plaskie pola kompatybilnosci |
| Egzamin state | `/nauka/teraz/egzamin/stan` | wdrozone Sprint 5 | utrzymac zgodnosc semantyki z web |
| Idempotencja | czesciowa przez jedna odpowiedz na pytanie | `accepted=false` przy powtorce | v1 online OK, offline-lite wymaga `client_answer_id` |

## Docelowe endpointy v1

| Endpoint | Cel | Middleware |
| --- | --- | --- |
| `GET /api/v1/me/learning-home` | bootstrap mobile home `/nauka` | `auth`, `verified`, bez `product.access` |
| `POST /api/v1/sessions` | start nowej sesji z pelnymi filtrami | `auth`, `verified`, `product.access` |
| `GET /api/v1/sessions/current` | resume aktywnej sesji | `auth`, `verified`, `product.access` |
| `GET /api/v1/sessions/current/questions` | batch/window pytan | `auth`, `verified`, `product.access` |
| `GET /api/v1/sessions/current/questions/{question}` | pojedyncze pytanie | `auth`, `verified`, `product.access` |
| `POST /api/v1/sessions/current/answers` | zapis odpowiedzi aktywnej sesji | `auth`, `verified`, `product.access` |
| `POST /api/v1/sessions/current/complete` | zakonczenie aktywnej sesji | `auth`, `verified`, `product.access` |
| `POST /api/v1/sessions/current/exam-state` | synchronizacja egzaminu | `auth`, `verified`, `product.access` |

Istniejace endpointy z `{studySession}` moga zostac dla kompatybilnosci i paneli/testow, ale mobile powinien uzywac `current`, kiedy pracuje na aktywnym flow.

## `GET /api/v1/me/learning-home`

Cel:

- jeden bootstrap dla mobile home,
- odpowiednik webowego `learning_dashboard`,
- ten sam builder danych co webowe `/nauka`,
- bez `product.access`, zeby zwrocic required action przy braku dostepu.

Status: wdrozone w Sprincie 3.

Response:

```json
{
  "data": {
    "schema_version": 1,
    "access": {
      "full_product": {
        "allowed": true,
        "reason": null,
        "activation_url": "/aktywuj-dostep"
      }
    },
    "category": {
      "id": 1,
      "code": "B",
      "name": "Prawo jazdy kat. B",
      "short_name": "B",
      "questions_count": 700
    },
    "filters": {
      "question_topic_id": 12,
      "ui_shell": "exam_like",
      "question_scope": "all",
      "question_status": "all",
      "randomize_order": false,
      "question_count": 20
    },
    "group_options": [],
    "status_options": [
      {
        "value": "all",
        "label": "Wszystkie z tego działu"
      }
    ],
    "learning_overview": {
      "due_review_count": 4
    },
    "learning_dashboard": {
      "active_session": null,
      "course_progress": {
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
      "ranking": {
        "position": null,
        "rating": null,
        "matches_played": 0,
        "message": "Zobacz swoja pozycje wsrod najlepszych."
      },
      "recent_learning_activity": null,
      "weekly_activity": null,
      "study_time": null
    },
    "ranking_preview": {
      "position": null,
      "rating": null,
      "matches_played": 0,
      "message": "Pierwszy pojedynek ustawi Twoją pozycję."
    },
    "pjm_module": {
      "available": false,
      "reason": "pjm_track_not_selected",
      "preferred": false,
      "show_entry_tile": false,
      "starter_mode": false,
      "href": "/nauka/pjm",
      "coverage": null
    },
    "friend_invitation_cta": {
      "visible": false
    },
    "modes": [
      {
        "key": "classic",
        "label": "Nauka klasyczna",
        "session_mode": "learn",
        "ui_shell": "exam_like",
        "enabled": true,
        "api_supported": true
      }
    ],
    "actions": {
      "start_session": {
        "method": "POST",
        "api_url": "/api/v1/sessions",
        "web_url": "/study-sessions"
      },
      "resume_session": {
        "method": "GET",
        "api_url": "/api/v1/sessions/current",
        "web_url": "/nauka/teraz"
      }
    }
  }
}
```

Access error:

```json
{
  "message": "Aktywuj dostęp do nauki, żeby kontynuować.",
  "error_code": "LEARNING_ACCESS_REQUIRED",
  "data": {
    "access": {
      "full_product": {
        "allowed": false,
        "reason": "no_active_grant",
        "activation_url": "/aktywuj-dostep"
      },
      "pjm": {
        "allowed": false,
        "reason": "pjm_track_not_selected"
      }
    }
  }
}
```

Uwagi:

- `learning_dashboard.active_session.resume_url` zostaje webowym `/nauka/teraz`,
- `actions.*.api_url` sa kanoniczne dla PWA/TWA,
- `modes.api_supported=false` oznacza, ze tryb istnieje w webowym `/nauka`, ale nie ma jeszcze core API v1.

## `active_session` DTO

Ten sam ksztalt powinien byc uzyty w `learning-home` i `sessions/current`.

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
    "question_count": 20,
    "question_count_strategy": "fixed"
  },
  "started_at": "2026-07-06T10:00:00+02:00",
  "updated_at": "2026-07-06T10:08:00+02:00",
  "resume_url": "/nauka/teraz",
  "api_resume_url": "/api/v1/sessions/current",
  "can_replace": true,
  "replace_warning": "Masz aktywna sesje. Rozpoczecie nowej zakonczy obecna."
}
```

## `POST /api/v1/sessions`

Cel:

- startuje sesje z tymi samymi filtrami, ktore ma webowy `/nauka`,
- jawnie opisuje semantyke zastapienia aktywnej sesji.

Request:

```json
{
  "category_id": 1,
  "mode": "learn",
  "ui_shell": "exam_like",
  "question_topic_id": 12,
  "question_scope": "all",
  "question_status": "unanswered",
  "randomize_order": false,
  "question_count": 20,
  "question_count_strategy": "fixed",
  "replace_active_session": true
}
```

Alias compatibility:

- `license_category_id` moze byc przyjete jako alias `category_id`,
- `review` moze zostac aliasem `sr_review`,
- `user_answer` moze zostac aliasem tylko w endpointach odpowiedzi, nie w starcie sesji.

Dozwolone `mode` w core mobile v1:

- `learn`,
- `exam`,
- `sr_review`,
- `hard`,
- `quick`.

`pjm`:

- web wspiera `pjm`,
- obecne API go nie wspiera,
- core mobile API v1 nie musi wlaczac `pjm`,
- jezeli `pjm` ma wejsc do API, powinien dostac osobna decyzje dostepu i assetow PJM.

Zasady zastapienia aktywnej sesji:

- jezeli istnieje aktywna sesja i `replace_active_session` nie jest `true`, backend powinien zwrocic `409 ACTIVE_SESSION_EXISTS`,
- response bledu powinien zawierac `active_session`,
- frontend pokazuje wtedy `Wroc do sesji` albo `Rozpocznij nowa`,
- po `replace_active_session=true` backend moze uzyc obecnej logiki `StudySessionManager::start()`, ktora zamyka stare `in_progress`.

Response `201`:

```json
{
  "data": {
    "session": {
      "id": 123,
      "category_id": 1,
      "mode": "learn",
      "ui_shell": "exam_like",
      "status": "in_progress",
      "started_at": "2026-07-06T10:00:00+02:00",
      "completed_at": null,
      "correct_answers_count": 0,
      "total_questions": 20,
      "answered_questions": 0,
      "score_percent": null
    },
    "category": {
      "id": 1,
      "code": "B",
      "name": "Prawo jazdy kat. B"
    },
    "progress": {
      "answered": 0,
      "remaining": 20,
      "total": 20,
      "percent": 0,
      "current_question_number": 1
    },
    "current_question": {},
    "question_window": {
      "questions": [],
      "window_size": 12,
      "has_more": true
    }
  }
}
```

## `GET /api/v1/sessions/current`

Cel:

- bezpieczne resume aktywnej sesji,
- brak koniecznosci trzymania `session_id` przez klienta.

Brak aktywnej sesji:

```json
{
  "data": {
    "session": null,
    "active_session": null
  }
}
```

Aktywna sesja:

```json
{
  "data": {
    "session": {},
    "active_session": {},
    "category": {},
    "progress": {},
    "current_question": {},
    "exam_ui": null,
    "review_completion": null
  }
}
```

Decyzja:

- brak aktywnej sesji jest normalnym stanem, wiec `GET /current` powinien zwrocic `200`, nie `404`.
- `GET /api/v1/sessions/{id}` moze dalej zwracac `404`, gdy konkretna sesja nie istnieje.

## `GET /api/v1/sessions/current/questions`

Cel:

- odpowiednik webowego `/nauka/teraz/pytania`,
- batch/window pytan bez zwracania calej sesji za kazdym razem.

Request query:

```text
ids[]=10&ids[]=11&ids[]=12
```

Alternatywa dla klienta window:

```text
from=current&limit=12
```

Response:

```json
{
  "data": {
    "session": {
      "id": 123,
      "status": "in_progress"
    },
    "questions": [],
    "meta": {
      "requested_ids": [10, 11, 12],
      "returned_count": 3,
      "window_size": 12
    }
  }
}
```

## Question DTO

Kanoniczny payload pytania powinien bazowac na `StudySessionApiPayloadBuilder::question()`.

```json
{
  "id": 10,
  "category_id": 1,
  "external_id": "B-001",
  "question_text": "Czy w tej sytuacji masz pierwszenstwo?",
  "question_type": "single_choice",
  "difficulty": 2,
  "points": 3,
  "structure_scope": "basic",
  "answers": {
    "A": "Tak",
    "B": "Nie",
    "C": "Tylko warunkowo"
  },
  "media": [],
  "audio": null,
  "sign_language_assets": [],
  "explanation_asset": null,
  "explanation_annotations": [],
  "topic": {
    "id": 12,
    "key": "vehicle_operation_and_safety",
    "name": "Obsluga pojazdu i bezpieczenstwo jazdy"
  },
  "selected_answer": null,
  "answer_kind": null,
  "is_answered": false,
  "response_time_ms": null
}
```

Reveal fields po odpowiedzi albo po zakonczeniu:

```json
{
  "correct_answer": "A",
  "correct_answer_text": "Tak",
  "is_correct": true,
  "explanation": "Wyjasnienie...",
  "explanation_asset": {},
  "explanation_annotations": []
}
```

## `POST /api/v1/sessions/current/answers`

Cel:

- kanoniczny zapis odpowiedzi aktywnej sesji,
- semantyka jak webowy `/nauka/teraz/odpowiedzi`,
- przygotowanie pod przyszla idempotencje mobile.

Request:

```json
{
  "question_id": 10,
  "selected_answer": "A",
  "answer_kind": "choice",
  "response_time_ms": 8421
}
```

Zasady:

- `selected_answer` przyjmujemy jako `A/B/C` lub `a/b/c`,
- backend normalizuje do lowercase w bazie i uppercase w response,
- `answer_kind=choice` wymaga `selected_answer`,
- `answer_kind=unknown` jest dozwolone tylko dla `sr_review`,
- `user_answer` zostaje legacy aliasem `selected_answer`,
- `client_answer_id` w PWA v1 moze byc opcjonalne, ale kontrakt powinien je zarezerwowac.

Status: response ujednolicony w Sprincie 4. Prawdziwe `client_answer_id` i `sync_status` zostaja na etap offline-lite.

Response:

```json
{
  "data": {
    "accepted": true,
    "question_id": 10,
    "answer_kind": "choice",
    "is_correct": true,
    "answered_questions": 1,
    "session_status": "in_progress",
    "answer": {
      "question_id": 10,
      "selected_answer": "A",
      "answer_kind": "choice",
      "is_correct": true,
      "response_time_ms": 8421,
      "correct_answer": "A",
      "correct_answer_text": "Tak",
      "explanation": "Wyjasnienie...",
      "explanation_asset": null,
      "explanation_annotations": []
    },
    "session": {
      "id": 123,
      "status": "in_progress",
      "correct_answers_count": 1,
      "total_questions": 20,
      "score_percent": null,
      "completed_at": null
    },
    "progress": {
      "answered": 1,
      "remaining": 19,
      "total": 20,
      "percent": 5,
      "current_question_number": 2
    },
    "completed": false,
    "correct_answer": "A",
    "correct_answer_text": "Tak",
    "explanation": "Wyjasnienie...",
    "explanation_asset": null,
    "explanation_annotations": [],
    "next_question": {
      "id": 11,
      "question_text": "Nastepne pytanie...",
      "correct_answer": null,
      "explanation": null
    },
    "next_question_number": 2,
    "review_completion": null
  }
}
```

Przyszly `sync_status` enum dla offline-lite:

| Wartosc | Znaczenie |
| --- | --- |
| `accepted` | nowa odpowiedz zapisana |
| `already_recorded` | powtorka tego samego zapisu/retry |
| `conflict` | klient probuje zapisac inna odpowiedz niz juz zapisana |

Wazne: obecny backend rozroznia `accepted=true/false`, ale przy drugim zapisie zwraca istniejaca odpowiedz bez porownania payloadu. To jest czesciowa idempotencja. Dla offline-lite trzeba dodac prawdziwe `client_answer_id` i konflikt.

## `POST /api/v1/sessions/current/complete`

Cel:

- zakonczenie aktywnej sesji bez znajomosci `session_id`.

Response:

- taki sam envelope jak `GET /api/v1/sessions/current`,
- `session.status = completed`,
- `current_question = null`,
- `questions` moga ujawnic `correct_answer` i `is_correct`, jesli klient potrzebuje pelnego wyniku.

## `POST /api/v1/sessions/current/exam-state`

Cel:

- odpowiednik webowego `/nauka/teraz/egzamin/stan`,
- synchronizacja fazy egzaminu i czasu.

Status: wdrozone w Sprincie 5.

Request:

```json
{
  "action": "start-answer"
}
```

Response:

```json
{
  "data": {
    "session": {
      "id": 123,
      "mode": "exam",
      "ui_shell": "exam",
      "status": "in_progress",
      "total_questions": 32,
      "answered_questions": 0
    },
    "category": {
      "id": 1,
      "code": "B",
      "short_name": "B"
    },
    "progress": {
      "answered": 0,
      "remaining": 32,
      "total": 32,
      "current_question_number": 1
    },
    "exam_ui": {
      "duration_seconds": 1500,
      "remaining_seconds": 1492,
      "started_at": "2026-07-07T10:00:00+02:00",
      "deadline_at": "2026-07-07T10:25:00+02:00",
      "pass_threshold": 68,
      "max_points": 74,
      "current_scope": "PODSTAWOWY",
      "current_points": 3,
      "basic": {
        "answered": 0,
        "total": 20
      },
      "specialist": {
        "answered": 0,
        "total": 12
      },
      "phase": "answer",
      "question_remaining_seconds": 20,
      "question_started_at": "2026-07-07T10:00:05+02:00",
      "question_deadline_at": "2026-07-07T10:00:25+02:00",
      "preview_duration_seconds": 20,
      "basic_answer_duration_seconds": 15,
      "specialist_answer_duration_seconds": 50
    },
    "current_question_number": 1,
    "current_question": {
      "id": 456,
      "structure_scope": "PODSTAWOWY",
      "correct_answer": null,
      "explanation": null,
      "explanation_asset": null,
      "explanation_annotations": []
    },
    "completed": false,
    "redirect": "/nauka/teraz"
  }
}
```

Zasady:

- endpoint wymaga aktywnej sesji `exam`,
- `action=start-answer` przechodzi z preview/media do fazy answer,
- `current_question.correct_answer` i `current_question.explanation` nie sa ujawniane w aktywnym egzaminie,
- po automatycznym zakonczeniu egzaminu `completed=true`, `current_question=null`, `redirect=/nauka/wynik/{id}`.

## Error contract

Standardowy blad mobile API:

```json
{
  "error": {
    "code": "PRODUCT_ACCESS_REQUIRED",
    "message": "Aktywuj dostep do produktu.",
    "details": {},
    "required_action": "PRODUCT_ACCESS_REQUIRED",
    "request_id": "req_abc123"
  }
}
```

Kody wymagane dla mobile:

| Code | HTTP | Znaczenie |
| --- | --- | --- |
| `UNAUTHENTICATED` | 401 | brak zalogowanej sesji |
| `CSRF_TOKEN_MISMATCH` | 419 | wygasla sesja/CSRF |
| `EMAIL_VERIFICATION_REQUIRED` | 403 | email niezweryfikowany |
| `PASSWORD_CHANGE_REQUIRED` | 403 | wymagane haslo |
| `ACCOUNT_CLAIM_REQUIRED` | 403 | konto tymczasowe |
| `PRODUCT_ACCESS_REQUIRED` | 403 | brak pelnego dostepu |
| `CATEGORY_LOCKED` | 403 | kategoria poza dostepem |
| `ACTIVE_SESSION_EXISTS` | 409 | start nowej sesji wymaga potwierdzenia |
| `SESSION_NOT_FOUND` | 404 | konkretna sesja nie istnieje |
| `SESSION_COMPLETED` | 409 | proba odpowiedzi na zakonczona sesje |
| `QUESTION_NOT_IN_SESSION` | 422 | pytanie nie nalezy do sesji |
| `QUESTION_NOT_CURRENT` | 422 | pytanie nie jest aktualne |
| `ANSWER_ALREADY_RECORDED` | 409 albo 200 | ponowny zapis odpowiedzi |
| `ANSWER_CONFLICT` | 409 | inny payload dla tej samej odpowiedzi |
| `PJM_NOT_SUPPORTED` | 422 | PJM nie jest w tym kontrakcie API |

## Idempotencja

Stan obecny:

- `study_session_answers` pozwala miec jedna odpowiedz na pytanie w sesji,
- `StudySessionManager::recordAnswer()` przy drugim zapisie zwraca istniejaca odpowiedz,
- `ApiStudySessionAnswerController` zwraca wtedy `accepted=false`,
- backend nie sprawdza jeszcze, czy drugi payload jest identyczny z pierwszym.

Dla PWA/TWA online-first:

- to wystarcza jako minimalny retry protection,
- UI nie powinien jednak obiecywac offline odpowiedzi.

Dla offline-lite:

- wymagany jest `client_answer_id` albo `client_operation_id`,
- backend musi zapisac ten identyfikator,
- retry tego samego id zwraca `already_recorded`,
- inne dane dla tego samego pytania zwracaja `ANSWER_CONFLICT`,
- response musi rozroznic: zapisano, juz bylo, konflikt, sesja zakonczona.

## Auth i session lifecycle

PWA/TWA v1:

- korzysta z webowego cookie/session auth,
- korzysta z CSRF jak obecny web,
- nie wprowadza `auth:sanctum` tokenow ani Bearer tokenow,
- musi obslugiwac `401`, `419`, `403` i `409` semantycznie.

Przyszly natywny klient:

- moze wymagac osobnej decyzji o tokenach/Sanctum,
- nie powinien byc projektowany przypadkiem przez obecny PWA sprint.

## Cache policy

Nie cache'owac:

- `GET /api/v1/me/learning-home`,
- `GET /api/v1/sessions/current`,
- `GET /api/v1/sessions/current/questions*`,
- `POST /api/v1/sessions*`,
- `POST /api/v1/sessions/current/answers`,
- `POST /api/v1/sessions/current/complete`,
- `POST /api/v1/sessions/current/exam-state`.

Mozna cache'owac ostroznie:

- wersjonowane assety frontendu,
- ikony,
- manifest,
- publiczne kategorie, jesli nie zawieraja prywatnego stanu.

## Testy kontraktowe

Minimalny zestaw testow przy implementacji:

- `GET /api/v1/me/learning-home` z pelnym dostepem - zrobione w `ApiLearningHomeTest`,
- `GET /api/v1/me/learning-home` bez pelnego dostepu - zrobione w `ApiLearningHomeTest`,
- `GET /api/v1/me/learning-home` z aktywna sesja - zrobione w `ApiLearningHomeTest`,
- `GET /api/v1/sessions/current` bez aktywnej sesji,
- `GET /api/v1/sessions/current` z aktywna sesja,
- `POST /api/v1/sessions` z `ui_shell`, `question_topic_id`, `question_scope`, `question_status`, `randomize_order`,
- `POST /api/v1/sessions` przy aktywnej sesji bez `replace_active_session`,
- `POST /api/v1/sessions` przy aktywnej sesji z `replace_active_session=true`,
- `POST /api/v1/sessions/current/answers` z `selected_answer`,
- `POST /api/v1/sessions/current/answers` z legacy `user_answer`,
- drugi zapis tej samej odpowiedzi,
- proba innej odpowiedzi dla juz odpowiedzianego pytania,
- `answer_kind=unknown` tylko dla `sr_review`,
- reveal po odpowiedzi dla `learn` - zrobione w Sprincie 4,
- reveal i `next_question` dla `sr_review` - zrobione w Sprintach 2/4,
- `POST /api/v1/sessions/current/exam-state` dla egzaminu - zrobione w Sprincie 5,
- cudza sesja przez endpoint `{studySession}` nadal forbidden.

Sugerowane pliki:

- `tests/Feature/ApiLearningHomeTest.php`,
- `tests/Feature/MobileStudySessionApiContractTest.php`,
- rozszerzenia w [tests/Feature/ApiSessionTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/ApiSessionTest.php).

## Kolejnosc implementacji

1. Zaktualizowac `API-SPEC.md` albo oznaczyc obecny rozdzial sesji jako legacy.
2. Dodac testy kontraktowe dla `learning-home` i `sessions/current`. Czesciowo zrobione.
3. Wystawic `GET /api/v1/me/learning-home`. Zrobione.
4. Dodac `GET /api/v1/sessions/current`. Zrobione.
5. Rozszerzyc `POST /api/v1/sessions` o pelne filtry i `replace_active_session`. Zrobione.
6. Dodac alias `selected_answer` do API answer request. Zrobione.
7. Ujednolicic response odpowiedzi z web current. Zrobione czesciowo w Sprincie 4.
8. Dodac batch/window `current/questions`. Batch po `ids[]` zrobiony.
9. Dodac `current/exam-state`. Zrobione.
10. Dopiero potem projektowac offline-lite i trwala idempotencje.

## Decyzje do zatwierdzenia

| ID | Pytanie | Rekomendacja |
| --- | --- | --- |
| API-D1 | Czy `learning-home` jest bez `product.access`? | Tak |
| API-D2 | Czy `sessions/current` zwraca 200 z `session=null` przy braku aktywnej sesji? | Tak |
| API-D3 | Czy `category_id` zostaje kanoniczne w API? | Tak |
| API-D4 | Czy `license_category_id` zostaje aliasem? | Tak |
| API-D5 | Czy `selected_answer` jest kanoniczne w odpowiedziach? | Tak |
| API-D6 | Czy `user_answer` zostaje aliasem legacy? | Tak |
| API-D7 | Czy klient moze zablokowac zastapienie aktywnej sesji? | Tak, `replace_active_session=false` zwraca `409 ACTIVE_SESSION_EXISTS`; brak pola zachowuje kompatybilne domyslne zastapienie |
| API-D8 | Czy PJM wchodzi do core mobile API v1? | Nie, osobna decyzja |
| API-D9 | Czy offline-lite wchodzi do PWA/TWA v1? | Nie |
| API-D10 | Czy natywne Bearer tokeny wchodza do PWA/TWA v1? | Nie |

## Wniosek

Najlepszy kierunek to nie pisac drugiego backendu dla mobile, tylko uporzadkowac obecne API wokol `StudySessionManager`:

- `learning-home` jako bootstrap,
- `sessions/current` jako resume,
- `POST /sessions` z filtrami web `/nauka`,
- `selected_answer` jako wspolny jezyk odpowiedzi,
- semantyczne bledy,
- jawna idempotencja dopiero przy offline-lite.
