# OSK V2.12 — Etap 5C: cykl sesji przez HTTP

**Status:** zakończony lokalnie; integracja z playerem jest wykonana w osobnym Etapie 5D, brak deployu.
**Data weryfikacji:** 2026-08-25.
**Branch implementacyjny:** `codex/osk-course-program-pilot-b`.

Etap 5C wystawia minimalną warstwę HTTP nad serwisem `LearningSessionService`. Nie zmienia źródłowego modelu czasu, nie tworzy evidence i nie nadaje sesji znaczenia formalnego, dopóki nie powstanie zaakceptowany UX oraz kolejne warstwy postępu.

## 1. Endpointy

```text
POST /osk/nauka/{courseEnrollment}/sesje
POST /osk/nauka/{courseEnrollment}/sesje/{learningSession}/heartbeat
POST /osk/nauka/{courseEnrollment}/sesje/{learningSession}/close
```

Wszystkie trasy są częścią `web`, wymagają zalogowanego i zweryfikowanego użytkownika oraz korzystają z CSRF. Endpointy są nazwane odpowiednio:

```text
osk.theory-learning.sessions.start
osk.theory-learning.sessions.heartbeat
osk.theory-learning.sessions.close
```

## 2. Kontrakt żądań

Start lub wznowienie:

```json
{
  "client_session_key": "losowy-klucz-przegladarki"
}
```

Heartbeat:

```json
{
  "client_session_key": "losowy-klucz-przegladarki",
  "idempotency_key": "unikalny-klucz-zadania"
}
```

Zamknięcie:

```json
{
  "client_session_key": "losowy-klucz-przegladarki"
}
```

Oba klucze mają ograniczony format i długość. Klient nie przesyła czasu rozpoczęcia, czasu heartbeat-u ani czasu zamknięcia; źródłem czasu pozostaje serwer.

## 3. Zasady bezpieczeństwa i spójności

- `LearningSessionService` pozostaje jedynym miejscem zapisu. Kontroler nie wykonuje bezpośrednich zapisów do tabel.
- Enrollment jest sprawdzany przez route binding i dodatkową kontrolę przynależności sesji. Cudza sesja kończy się `404`, bez ujawniania jej stanu.
- Dostęp do enrollmentu jest sprawdzany ponownie przez istniejący `TheoryLearningAccessService` przy każdej operacji.
- Heartbeat ma klucz idempotencji i nie tworzy drugiego rekordu po ponowieniu tego samego żądania.
- Limity tras: start i close `30/min`, heartbeat `90/min`.
- Po timeout lub limicie heartbeat zwraca `409` z `SESSION_CLOSED`; klient nie powinien próbować dopisywać czasu do zamkniętej sesji.
- Błąd polityki lub nieprawidłowy stan źródłowy kończy się neutralnym `409 SESSION_UNAVAILABLE`, bez ujawniania szczegółów wewnętrznych.

## 4. Payload odpowiedzi

Odpowiedź zawiera stan sesji, serwerowy `server_time` oraz minimalny snapshot granic polityki potrzebny przyszłemu klientowi. Nie zwraca klucza przeglądarki, hashy programu ani pełnego snapshotu polityki.

Heartbeat dodatkowo zwraca `disposition`:

```text
RECORDED
IDEMPOTENT
THROTTLED
SESSION_CLOSED
```

## 5. Poza zakresem tego kontraktu

- świadomy UX i wywołania z `LessonPlayer.vue` należą do [Etapu 5D](./OSK_V2_12_STAGE_5D_LESSON_PLAYER_SESSION_UX.md);
- timer, obsługa widoczności karty i komunikaty dla kursanta należą do Etapu 5D;
- nadal brak workera, crona i heurystyk aktywności;
- brak postępu, ukończenia lekcji, `ModuleAssessment`, `TheoryCompletionGate` i formalnego evidence;
- brak zmian w `StudySession`, `QuestionCollection` oraz istniejącym `/nauka`;
- brak produkcyjnego routingu, seeda i feature flagi.

## 6. Weryfikacja lokalna

`LearningSessionFoundationTest` przechodzi lokalnie: **13 testów / 84 asercje**, w tym testy endpointów start/heartbeat/close, idempotencji, walidacji kluczy, izolacji enrollmentu i timeoutu. Aktualny pełny pakiet `tests/Feature/Osk` również przeszedł: **49 testów / 341 asercji**. Regresja istniejącej nauki, kolekcji, B2C access, checkoutu i audytu przeszła: **72 testy / 1265 asercji**.

## 7. Następny bezpieczny krok

Kontrakt HTTP jest podłączony do playera w Etapie 5D. Następny krok to ręczny smoke test na aktywnym enrollmentcie pilota: start bez requestu przy wejściu, wznowienie, heartbeat, przejście między lekcjami, jawne close, timeout i wygasłe logowanie. Nie wolno jeszcze traktować sesji jako formalnego ukończenia ani evidence.
