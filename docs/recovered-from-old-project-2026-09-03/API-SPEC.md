# Specyfikacja API

## 1. Cel dokumentu

Ten dokument definiuje kanoniczny kontrakt API dla MVP oraz granice rozszerzen V2.

Cel:

- ustalic publiczny i prywatny surface API,
- ujednolicic requesty i odpowiedzi,
- zapobiec przypadkowemu rozlewaniu logiki do niespojnych endpointow,
- przygotowac kontrakty pod frontend, aplikacje mobilna i panel B2B.

## 2. Zasady projektowe

1. API zwraca JSON.
2. API nie przesyla binarnych mediow.
3. Media sa reprezentowane przez URL i metadane.
4. Endpointy sesyjne sa zorientowane na przebieg egzaminu i nauki.
5. API MVP jest male i latwe do utrzymania.
6. API V2 rozszerza MVP, a nie lamie jego podstaw.

## 3. Konwencje techniczne

### Base path

```text
/api/v1
```

### Content type

```text
Content-Type: application/json
Accept: application/json
```

### Request correlation

- kazda odpowiedz API powinna zwracac naglowek `X-Request-Id`,
- klient moze przeslac wlasny `X-Request-Id`, jesli uzywa bezpiecznego identyfikatora korelacyjnego,
- przy bledach `request_id` powinno byc tez dostepne w `meta`.

### Auth

- endpointy publiczne: brak auth,
- endpointy uzytkownika: first-party session auth przez `Laravel` cookies,
- endpointy admin/B2B: session auth + kontrola roli po stronie backendu,
- `Bearer` tokens sa odlozone do momentu, gdy pojawi sie realna potrzeba zewnetrznych klientow lub integracji.

### CSRF

- requesty zmieniajace stan w web app wymagaja ochrony CSRF,
- frontend przesyla sesje i token CSRF zgodnie z first-party web flow,
- frontend oparty o `Inertia.js` i `Vue` korzysta z first-party session flow zamiast tokenowego auth jako domyslnego modelu MVP.

### Idempotencja

Wymagana dla operacji, ktore moga byc retried:

- tworzenie sesji opcjonalnie przez `Idempotency-Key`,
- upload presign,
- eksport raportow w V2.

### Timestamps

- wszystkie daty-czasy w `ISO 8601 UTC`,
- same daty typu kalendarzowego jako `YYYY-MM-DD`.

## 4. Standard odpowiedzi

### Sukces

```json
{
  "data": {},
  "meta": {}
}
```

### Blad

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid payload.",
    "details": []
  }
}
```

### Kody bledow kanoniczne

- `UNAUTHORIZED`
- `FORBIDDEN`
- `NOT_FOUND`
- `METHOD_NOT_ALLOWED`
- `VALIDATION_ERROR`
- `CONFLICT`
- `RATE_LIMITED`
- `SESSION_ALREADY_COMPLETED`
- `QUESTION_NOT_IN_SESSION`
- `INTERNAL_ERROR`

## 5. Standard `meta`

`meta` jest opcjonalne, ale stosowane gdy potrzebne sa:

- paginacja,
- cursor,
- request id, zwykle w parze z naglowkiem `X-Request-Id`,
- server time.

## 6. DTO kanoniczne

### Category DTO

```json
{
  "id": "B",
  "name": "Kategoria B",
  "description": "Samochod osobowy do 3.5t",
  "priority": 1,
  "is_active": true
}
```

### Media DTO

```json
{
  "id": "fefe8c8a-5d73-418a-8bfb-e14606b8e540",
  "kind": "video",
  "url": "https://media.example.com/media/questions/B/000001/video/clip.abcd1234.mp4",
  "mime_type": "video/mp4",
  "bytes": 4123456,
  "width": 1280,
  "height": 720,
  "duration_ms": 12000,
  "variant": "full"
}
```

### Question DTO

```json
{
  "id": "7dca7d56-9b5f-4b6f-a7d3-f4f6d0a52cd7",
  "category_id": "B",
  "subcategory": "pierwszenstwo",
  "scope": "basic",
  "points": 3,
  "question_text": "Czy w tej sytuacji masz pierwszenstwo?",
  "answers": {
    "A": "Tak",
    "B": "Nie",
    "C": "Tylko warunkowo"
  },
  "media": []
}
```

### Session DTO

```json
{
  "id": "a34310fc-1bbb-4d11-84f0-72b808e7013e",
  "category_id": "B",
  "mode": "exam",
  "status": "in_progress",
  "score": null,
  "max_score": 74,
  "passed": null,
  "started_at": "2026-03-18T20:00:00Z",
  "completed_at": null,
  "total_questions": 32,
  "answered_questions": 0
}
```

## 7. Publiczne endpointy MVP

### `GET /api/v1/health`

Cel:

- health check dla load balancera i monitoringu.

Response:

```json
{
  "data": {
    "status": "ok",
    "server_time": "2026-03-19T12:00:00+00:00",
    "checks": {
      "database": {
        "status": "ok",
        "latency_ms": 4
      },
      "backup": {
        "status": "ok",
        "last_backup_at": "2026-03-19T02:15:00+00:00",
        "age_hours": 10.2,
        "max_age_hours": 36
      }
    },
    "monitoring": {
      "backup_enforced": true
    }
  }
}
```

Zasady:

- `status=failed` powinien zwracac HTTP `503`,
- `status=degraded` moze nadal zwracac HTTP `200`,
- health check bazy ma byc lekki,
- brak swiezego backupu nie powinien automatycznie wyjmowac noda z ruchu lokalnie, ale moze degradowac health w produkcji.

### `GET /api/v1/categories`

Cel:

- zwraca aktywne kategorie.

### `GET /api/v1/categories/{categoryId}`

Cel:

- zwraca podstawowe informacje o kategorii i zakresie.

## 8. Endpointy profilu i dashboardu MVP

### `GET /api/v1/me/profile`

Cel:

- pobiera profil produktowy uzytkownika.

### `PUT /api/v1/me/profile`

Cel:

- aktualizuje profil, np. kategorie docelowa i date egzaminu.

Request:

```json
{
  "display_name": "Jan Kowalski",
  "target_category_id": "B",
  "exam_date": "2026-05-01"
}
```

### `GET /api/v1/me/dashboard`

Cel:

- zwraca podstawowy dashboard MVP.

Response:

```json
{
  "data": {
    "sessions_today": 3,
    "answered_today": 42,
    "classic_sessions_today": 2,
    "classic_answered_today": 30,
    "memory_trainer_sessions_today": 1,
    "memory_trainer_answered_today": 12,
    "ready_for_review_count": 12,
    "readiness_score": 61,
    "study_streak": 5,
    "last_session_at": "2026-03-18T18:32:11Z"
  }
}
```

### `GET /api/v1/me/learning-home`

Cel:

- bootstrap ekranu mobilnego `/nauka`,
- zwraca kategorie, filtry startowe, dzialy, aktywna sesje, tryby i URL-e akcji dla PWA/TWA,
- nie jest za `product.access`, dzieki czemu klient dostaje semantyczny blad dostepu.

Response:

```json
{
  "data": {
    "schema_version": 1,
    "category": {
      "id": 1,
      "code": "B",
      "name": "Kategoria B",
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
      }
    },
    "modes": [
      {
        "key": "classic",
        "session_mode": "learn",
        "ui_shell": "exam_like",
        "enabled": true,
        "api_supported": true
      }
    ],
    "actions": {
      "start_session": {
        "method": "POST",
        "api_url": "/api/v1/sessions"
      },
      "resume_session": {
        "method": "GET",
        "api_url": "/api/v1/sessions/current"
      }
    }
  }
}
```

Bledy:

| Kod | HTTP | Znaczenie |
| --- | --- | --- |
| `LEARNING_ACCESS_REQUIRED` | `403` | konto nie ma pelnego dostepu ani dostepu PJM starter |

## 9. Endpointy sesji MVP

### `POST /api/v1/sessions`

Cel:

- tworzy nowa sesje `exam`, `learn`, `hard`, `quick` albo `sr_review`.

Request:

```json
{
  "category_id": "B",
  "mode": "exam"
}
```

Uwagi:

- `review` jest akceptowane jako alias kompatybilnosci wstecznej, ale backend normalizuje ten tryb do `sr_review`,
- `quick` jest lekkim trybem szybkiej serii i w MVP jest ograniczany do maksymalnie `10` pytan na sesje.

Response:

```json
{
  "data": {
    "session": {
      "id": "a34310fc-1bbb-4d11-84f0-72b808e7013e",
      "category_id": "B",
      "mode": "exam",
      "status": "in_progress",
      "score": null,
      "max_score": 74,
      "passed": null,
      "started_at": "2026-03-18T20:00:00Z",
      "completed_at": null,
      "total_questions": 32,
      "answered_questions": 0
    },
    "questions": [
      {
        "id": "7dca7d56-9b5f-4b6f-a7d3-f4f6d0a52cd7",
        "category_id": "B",
        "subcategory": "pierwszenstwo",
        "scope": "basic",
        "points": 3,
        "question_text": "Czy w tej sytuacji masz pierwszenstwo?",
        "answers": {
          "A": "Tak",
          "B": "Nie",
          "C": "Tylko warunkowo"
        },
        "media": []
      }
    ]
  }
}
```

Zasady:

- backend zapisuje snapshot pytan do `session_questions`,
- `questions` sa zwracane w kolejnosci sesji,
- dla `sr_review` pytania pochodza z kolejki powtorek.

### `GET /api/v1/sessions/{sessionId}`

Cel:

- pobiera szczegoly sesji wraz ze stanem odpowiedzi.

### `POST /api/v1/sessions/{sessionId}/answers`

Cel:

- zapisuje odpowiedz dla konkretnego pytania w sesji,
- zwraca mobilny envelope z odpowiedzia, stanem sesji, progresem i revealem po odpowiedzi dla trybow nauki.

Request:

```json
{
  "question_id": 123,
  "selected_answer": "A",
  "response_time_ms": 8421
}
```

`user_answer` jest legacy aliasem `selected_answer`.

Response:

```json
{
  "data": {
    "accepted": true,
    "question_id": 123,
    "answer_kind": "choice",
    "is_correct": true,
    "answered_questions": 1,
    "session_status": "in_progress",
    "answer": {
      "question_id": 123,
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
      "id": 55,
      "mode": "learn",
      "status": "in_progress",
      "answered_questions": 1,
      "total_questions": 20
    },
    "progress": {
      "answered": 1,
      "remaining": 19,
      "total": 20,
      "current_question_number": 2
    },
    "completed": false,
    "correct_answer": "A",
    "correct_answer_text": "Tak",
    "explanation": "Wyjasnienie...",
    "next_question": null,
    "next_question_number": null
  }
}
```

### `POST /api/v1/sessions/{sessionId}/complete`

Cel:

- finalizuje sesje, liczy wynik i aktualizuje postep.

### `POST /api/v1/sessions/current/exam-state`

Cel:

- synchronizuje aktywna sesje egzaminacyjna z zegarem backendu,
- pozwala przejsc z preview/media do fazy odpowiedzi przez `action=start-answer`,
- zwraca mobilny payload `exam_ui`, aktualne pytanie i progres.

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
      "id": 55,
      "mode": "exam",
      "status": "in_progress",
      "answered_questions": 0,
      "total_questions": 32
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
      "question_remaining_seconds": 15
    },
    "current_question_number": 1,
    "current_question": {
      "id": 123,
      "structure_scope": "PODSTAWOWY",
      "correct_answer": null,
      "explanation": null
    },
    "completed": false,
    "redirect": "/nauka/teraz"
  }
}
```

## 10. Endpointy nauki i analityki MVP

### `GET /api/v1/me/review-queue`

Cel:

- zwraca plan trenera pamieci oraz opcjonalny preview payload pytan zaplanowanych do powtorki.

Query:

- `category` - opcjonalne ID kategorii w aktywnym zakresie uzytkownika,
- `include_questions` - opcjonalny boolean, domyslnie `true`.

Zasady `include_questions`:

- `include_questions=1`, `true`, `yes` albo brak parametru zwraca kompatybilny payload z `data.questions` oraz `meta.plan.preview_question_ids`,
- `include_questions=0`, `false`, `no` albo `off` zwraca plan-first payload: `data.questions` jest puste, a `meta.plan.preview_question_ids` nie jest ujawniane,
- niepoprawna wartosc zwraca blad walidacji `422`.

Najwazniejsze pola `meta.plan`:

- `planner_version` - wersja planera, np. `review-planner-v2`,
- `review_day` - dzien planu w strefie aplikacji,
- `daily_target_count` - dzienny cel trenera pamieci,
- `minimum_session_question_count` - pierwszy docelowy blok sesji,
- `completed_today_count` i `daily_remaining_count` - dzisiejszy postep z ledgeru trenera,
- `due_count` - pilne pytania z aktualnej kolejki/recovery,
- `booster_count` - pytania juz widziane, uzyte jako bezpieczne wzmocnienie,
- `new_candidate_count` - limitowana pierwsza ekspozycja nowych pytan,
- `candidate_source_counts.primary` - liczba pytan pilnych/recovery,
- `candidate_source_counts.seen_booster` - liczba boosterow z materialu juz widzianego,
- `candidate_source_counts.new_candidate` - liczba nowych kandydatow,
- `candidate_count` - suma kandydatow wybranych przez plan,
- `recommended_question_count` - liczba pytan proponowana do najblizszej sesji,
- `estimated_duration_seconds` i `estimated_duration_label` - orientacyjny czas sesji.

Zasady zrodel kandydatow:

- `primary` ma pierwszenstwo przed boosterami i nowymi pytaniami,
- `seen_booster` dopelnia plan tylko z materialu, ktory user juz widzial,
- `new_candidate` jest limitowana pierwsza ekspozycja; poprawna odpowiedz w tym segmencie nie oznacza jeszcze potwierdzonej pamieci,
- domyslny preview pytan moze zwrocic `data.questions[].memory_signal.source = "new_candidate"`,
- plan-first payload (`include_questions=0`) zachowuje liczniki zrodel, ale nie ujawnia listy pytan.

### `GET /api/v1/me/analytics/categories/{categoryId}`

Cel:

- zwraca statystyki dla kategorii.

Auth:

- zalogowany i zweryfikowany uzytkownik.

Response:

```json
{
  "data": {
    "category": {
      "id": 1,
      "code": "B",
      "name": "Kategoria B"
    },
    "summary": {
      "questions_total": 1200,
      "tracked_questions_count": 320,
      "coverage_pct": 26.7,
      "completed_sessions_count": 18,
      "answered_count": 540,
      "correct_answers_count": 412,
      "accuracy_pct": 76.3,
      "avg_response_time_ms": 8200,
      "readiness_score": 61.4,
      "ready_for_review_count": 24,
      "hard_questions_count": 17,
      "last_answered_at": "2026-03-19T12:00:00+00:00"
    },
    "breakdowns": {
      "question_types": [],
      "points": [],
      "difficulty": []
    },
    "weak_spots": [],
    "recent_activity": []
  }
}
```

Zasady:

- endpoint zwraca tylko dane zalogowanego uzytkownika,
- aktywnosc innych uzytkownikow nie moze wplywac na wynik,
- `breakdowns` powstaja z realnego `user_question_progress`,
- `weak_spots` pokazuja pytania o najnizszym mastery i najwyzszej presji bledow,
- `recent_activity` obejmuje ostatnie 7 dni.

### `GET /api/v1/me/hard-questions`

Cel:

- zwraca pytania o wysokim `difficulty_score`.

## 11. Endpointy administracyjne MVP

### `POST /api/v1/admin/media/presign`

Cel:

- generuje `presigned URL` do uploadu assetu do R2.

Request:

```json
{
  "question_id": "7dca7d56-9b5f-4b6f-a7d3-f4f6d0a52cd7",
  "kind": "video",
  "mime_type": "video/mp4",
  "bytes": 4123456,
  "variant": "full"
}
```

Response:

```json
{
  "data": {
    "upload_token": "01JPJQ2RAXQ4F3Q6YBQ8Q6T2AJ",
    "question_id": "7dca7d56-9b5f-4b6f-a7d3-f4f6d0a52cd7",
    "kind": "video",
    "variant": "full",
    "mime_type": "video/mp4",
    "bytes": 4123456,
    "disk": "r2",
    "path": "media/questions/B/000001/video/full.abcd1234.mp4",
    "method": "PUT",
    "upload_url": "https://example.r2.cloudflarestorage.com/...",
    "headers": {
      "Content-Type": "video/mp4"
    },
    "expires_at": "2026-03-19T10:15:00Z"
  }
}
```

Zasady:

- `Idempotency-Key` moze byc uzyty do bezpiecznego retriable `presign`,
- `variant=poster` sluzy do posterow podpietych do wideo,
- backend waliduje `kind`, `mime_type`, `bytes` i `variant` przed wystawieniem URL.

### `POST /api/v1/admin/media/confirm`

Cel:

- potwierdza upload i zapisuje rekord `media_assets`.

Request:

```json
{
  "upload_token": "01JPJQ2RAXQ4F3Q6YBQ8Q6T2AJ",
  "poster_upload_token": "01JPJQ3B0WG7B9E1Q2S9F7ZK4H",
  "width": 1280,
  "height": 720,
  "duration_seconds": 12,
  "sort_order": 0,
  "metadata": {
    "codec": "h264"
  }
}
```

Response:

```json
{
  "data": {
    "id": "fefe8c8a-5d73-418a-8bfb-e14606b8e540",
    "question_id": "7dca7d56-9b5f-4b6f-a7d3-f4f6d0a52cd7",
    "kind": "video",
    "disk": "r2",
    "path": "media/questions/B/000001/video/full.abcd1234.mp4",
    "poster_path": "media/questions/B/000001/image/poster.efgh5678.webp",
    "url": "https://media.example.com/media/questions/B/000001/video/full.abcd1234.mp4",
    "poster_url": "https://media.example.com/media/questions/B/000001/image/poster.efgh5678.webp",
    "mime_type": "video/mp4",
    "bytes": 4123456,
    "width": 1280,
    "height": 720,
    "duration_seconds": 12,
    "variant": "full",
    "sort_order": 0,
    "metadata": {
      "codec": "h264"
    }
  }
}
```

Zasady:

- `confirm` zapisuje rekord dopiero po sprawdzeniu, ze plik istnieje w storage,
- `poster_upload_token` jest opcjonalny, ale jesli wystepuje, musi nalezec do tego samego pytania i wskazywac `kind=image`, `variant=poster`,
- `upload_token` nie moze byc uzyty do arbitralnego zapisu sciezki spoza backendowego ticketu.

### `PATCH /api/v1/admin/questions/{questionId}/media/reorder`

Cel:

- aktualizuje kolejnosc mediow przypisanych do pytania.

Request:

```json
{
  "media_ids": [12, 8, 9]
}
```

Response:

```json
{
  "data": {
    "items": [
      {
        "id": 12,
        "sort_order": 0
      },
      {
        "id": 8,
        "sort_order": 1
      },
      {
        "id": 9,
        "sort_order": 2
      }
    ]
  }
}
```

Zasady:

- wszystkie `media_ids` musza nalezec do wskazanego pytania,
- backend przepisuje `sort_order` od `0` zgodnie z kolejnoscia tablicy,
- endpoint nie sluzy do przenoszenia assetow miedzy pytaniami.

### `DELETE /api/v1/admin/media/{mediaId}`

Cel:

- usuwa rekord media i probuje usunac odpowiadajace mu obiekty ze storage.

Response:

```json
{
  "data": {
    "deleted": true,
    "items": []
  }
}
```

Zasady:

- usuniecie wideo powinno objac tez `poster_path`, jesli istnieje,
- po usunieciu backend normalizuje `sort_order` pozostalych mediow dla pytania,
- brak obiektu w storage nie powinien blokowac sprzatania rekordu w bazie.

### `POST /api/v1/admin/questions`

Cel:

- tworzy pytanie albo importuje je przez panel lub pipeline.

Auth:

- tylko `admin`.

Request:

```json
{
  "question_id": 123,
  "license_category_id": 1,
  "category_code": "B",
  "external_id": "B-500",
  "prompt": "Czy wolno parkowac na przejsciu dla pieszych?",
  "explanation": "Nie, poniewaz ogranicza to widocznosc i zagraza bezpieczenstwu.",
  "option_a": "Tak",
  "option_b": "Nie",
  "option_c": "Tylko w nocy",
  "correct_answer": "B",
  "difficulty": 3,
  "points": 2,
  "question_type": "single_choice",
  "is_active": true,
  "source": "official",
  "published_at": "2026-03-19T12:00:00Z",
  "media": [
    {
      "kind": "image",
      "disk": "public",
      "path": "questions/b/b-500-full.webp",
      "mime_type": "image/webp",
      "bytes": 120000,
      "variant": "full",
      "width": 1280,
      "height": 720,
      "sort_order": 0
    },
    {
      "kind": "video",
      "disk": "public",
      "path": "questions/b/b-500.mp4",
      "poster_path": "questions/b/posters/b-500.webp",
      "mime_type": "video/mp4",
      "bytes": 2400000,
      "duration_seconds": 16,
      "variant": "full",
      "sort_order": 1
    }
  ]
}
```

Zasady:

- `question_id` oznacza update konkretnego rekordu,
- bez `question_id` i z `license_category_id + external_id` backend robi upsert po tym kluczu,
- `category_code` moze zastapic `license_category_id`,
- brak pola `media` zostawia aktualne media bez zmian,
- `media: []` oznacza wyczyszczenie powiazanych mediow,
- przeslany `media[]` traktujemy jako docelowy stan i synchronizujemy z baza,
- usuniete w czasie syncu media sa sprzatane tak samo jak przez `DELETE /api/v1/admin/media/{mediaId}`,
- dla `question_type=boolean` poprawna odpowiedz moze byc tylko `A` albo `B`.

Response `201 Created` albo `200 OK`:

```json
{
  "data": {
    "question": {
      "id": 123,
      "license_category": {
        "id": 1,
        "code": "B",
        "name": "Kategoria B"
      },
      "external_id": "B-500",
      "prompt": "Czy wolno parkowac na przejsciu dla pieszych?",
      "correct_answer": "B",
      "question_type": "single_choice",
      "difficulty": 3,
      "points": 2,
      "is_active": true,
      "media": [
        {
          "id": 9001,
          "kind": "image",
          "path": "questions/b/b-500-full.webp",
          "url": "https://media.example.com/questions/b/b-500-full.webp",
          "variant": "full",
          "sort_order": 0
        }
      ]
    }
  },
  "meta": {
    "action": "created"
  }
}
```

## 12. Rate limiting

Minimalne zasady:

- `GET /categories`: 120 req/min/IP
- `POST /sessions`: 20 req/min/user
- `POST /sessions/{id}/answers`: 120 req/min/user
- `POST /admin/media/presign`: 30 req/min/user
- `POST /admin/questions`: 30 req/min/user

## 13. Bezpieczenstwo kontraktu

API nie powinno:

- zwracac poprawnych odpowiedzi w trybie `exam` przed zakonczeniem,
- zwracac prywatnych URL do admin/import bucketow,
- umozliwiac uploadu bez walidacji typu i rozmiaru,
- opierac autoryzacji tylko na tym, co przyslal frontend,
- mieszac danych tenantow w jednym response w V2.

## 14. V2: rozszerzenia B2B

### `GET /api/v1/orgs/{orgId}`

- podstawowy widok organizacji.

### `GET /api/v1/orgs/{orgId}/members`

- lista czlonkow organizacji.

### `POST /api/v1/orgs/{orgId}/invites`

- zapraszanie nowych osob do organizacji.

### `GET /api/v1/orgs/{orgId}/reports/overview`

- dashboard B2B z agregatami dla szkoly jazdy.

### `POST /api/v1/orgs/{orgId}/exports`

- uruchamia asynchroniczny eksport raportu.

## 15. Wersjonowanie

Reguly:

- zmiany kompatybilne wstecz nie wymagaja nowego prefixu,
- breaking changes wymagaja nowej wersji, np. `/api/v2`,
- nie zmieniamy semantyki endpointu po cichu.

## 16. Testowanie kontraktow

Kazdy krytyczny endpoint powinien miec:

- test walidacji requestu,
- test autoryzacji,
- test sciezki sukcesu,
- test glownych bledow,
- test regresji payloadu.

Priorytet testowy:

1. `POST /sessions`
2. `POST /sessions/{id}/answers`
3. `POST /sessions/{id}/complete`
4. `GET /me/dashboard`
5. `POST /admin/media/presign`

## 17. Finalna rekomendacja

Profesjonalne API dla tego projektu powinno byc:

- male i przewidywalne w MVP,
- mocno skupione na flow sesji i nauki,
- zabezpieczone przed wyciekiem odpowiedzi i assetow,
- gotowe na rozszerzenie o B2B bez burzenia podstawowych kontraktow.
