# Tryb Rankingowy: Button Runbook

## Cel

Ten dokument porządkuje wyłącznie temat przycisku `Tryb rankingowy` na ekranie [Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue).

Ma odpowiedzieć na trzy pytania:

1. co dokładnie robi dziś ten button,
2. które elementy architektury ranking 1v1 są już wdrożone,
3. jak bezpiecznie rozwijać ten moduł dalej bez mieszania go z klasycznym `StudySession`.

Ten plik jest krótkim, praktycznym wejściem. Szersze tło historyczne i głęboki handoff są już w:

- [TRYB-RANKINGOWY-1V1-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TRYB-RANKINGOWY-1V1-PLAN.md)
- [RANKING-1V1-HANDOFF.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/RANKING-1V1-HANDOFF.md)

## Status na dziś

Najważniejszy wniosek: `Tryb rankingowy` nie jest już placeholderem.

Button jest już podpięty do działającego modułu rankingowego 1v1:

- na froncie istnieje osobny entrypoint rankingowy,
- na backendzie istnieją osobne strony, API, modele pomocnicze i transport realtime,
- ranking nie przechodzi przez `study-sessions.store`,
- ranking nie powinien być implementowany jako kolejny wariant zwykłej sesji nauki.

## Jak działa button dziś

### Punkt wejścia frontend

Przycisk żyje w:

- [Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue)

Technicznie:

- nie jest częścią zwykłego submitu formularza `sessionForm`,
- nie ustawia `mode = ranking`,
- nie przechodzi przez `StudySessionStoreRequest`,
- wylicza `rankingModeHref`,
- prowadzi do trasy `session.ranking`.

To jest poprawna decyzja architektoniczna, bo ranking 1v1 ma osobny lifecycle niż zwykła nauka.

### Punkt wejścia backend

Trasa wejściowa:

- [web.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/routes/web.php)

Aktualne strony rankingu:

- `GET /nauka/ranking`
- `GET /nauka/ranking/oczekiwanie`
- `GET /nauka/ranking/mecz`
- `GET /nauka/ranking/wynik`

Kontroler strony:

- [RankedSessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/RankedSessionPageController.php)

Widok Inertia:

- [Ranking.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Ranking.vue)

## Czego nie wolno robić

Nie należy rozwijać `Tryb rankingowy` przez dopisywanie go do klasycznego flow:

- [StudySessionStoreRequest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/StudySessionStoreRequest.php)
- [StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)
- [StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)

Powód:

- `StudySession` jest trybem jednoosobowym,
- ranking 1v1 potrzebuje kolejki, przeciwnika, presence, reconnectu, walkovera, wyniku meczu i ratingu,
- mieszanie tych dwóch światów zwiększa ryzyko regresji w `Nauka klasyczna`, `Zen mode` i `Egzamin`.

Najbezpieczniejsza granica modułu jest już dziś prawidłowa:

- zwykła nauka -> `StudySession`
- ranking 1v1 -> osobny bounded context `Ranked*`

## Co już mamy w module rankingowym

### UI i routing

- osobne ekrany rankingu,
- lobby,
- ekran oczekiwania,
- ekran meczu,
- ekran wyniku.

### HTTP API

W repo są już endpointy:

- `GET /api/v1/ranked/overview`
- `GET /api/v1/ranked/stream`
- `GET /api/v1/ranked/history`
- `POST /api/v1/ranked/queue/join`
- `POST /api/v1/ranked/queue/leave`
- `GET /api/v1/ranked/matches/{public_id}`
- `GET /api/v1/ranked/matches/{public_id}/events`
- `POST /api/v1/ranked/matches/{public_id}/ready`
- `POST /api/v1/ranked/matches/{public_id}/abandon`
- `POST /api/v1/ranked/matches/{public_id}/answers`
- `POST /api/v1/ranked/matches/{public_id}/pong`

### Backend support

Najważniejsze klasy pomocnicze:

- [RankedMatchService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedMatchService.php)
- [RankedMatchPresenceStore.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedMatchPresenceStore.php)
- [RankedRealtimeEventStreamPublisher.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedRealtimeEventStreamPublisher.php)
- [RankedRealtimeConnectionState.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedRealtimeConnectionState.php)
- [RankedWebSocketTicketService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedWebSocketTicketService.php)

### Frontend realtime

Najważniejsze pliki klienta:

- [rankedRealtimeTransportClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeTransportClient.ts)
- [rankedRealtimeWebSocketClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeWebSocketClient.ts)
- [rankedRealtimeEventSourceClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeEventSourceClient.ts)
- [rankedRealtimePollingTransport.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimePollingTransport.ts)
- [rankedRealtimeEventReducer.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeEventReducer.ts)
- [rankedRealtimeClock.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeClock.ts)

### Konfiguracja

Moduł ma już osobny config:

- [ranked.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/config/ranked.php)

To obejmuje m.in.:

- presence store,
- TTL,
- SSE,
- WebSocket,
- URL ticketowanego połączenia,
- retry i publish interval.

## Co jeszcze wymaga dopracowania

Moduł jest realny i testowalny, ale nie wygląda jeszcze jak zamknięte production-ready realtime PvP.

Najważniejsze luki do domknięcia:

1. ustabilizować transport realtime w środowisku produkcyjnym,
2. dopiąć monitoring i observability połączeń WebSocket/SSE,
3. zrobić końcowe regresje dla reconnectów i edge-case'ów queue,
4. zdecydować, czy ranking zostaje tylko dla kategorii `B`, czy rozszerzamy go na inne kategorie,
5. dopiąć spójne UX wejścia i powrotu między `lobby / waiting / match / result`.

## Plan bezpiecznego dalszego wdrożenia

### Etap 1. Utrzymanie entrypointu

Zachować obecną architekturę:

- button w [Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue),
- osobna trasa rankingowa,
- zero zmian w `StudySessionStoreRequest`.

To jest warunek bezpieczeństwa.

### Etap 2. Domknięcie transportu realtime

Skupić się na:

- `websocket_enabled`,
- `websocket_url`,
- fallback `websocket -> sse -> polling`,
- stabilności reconnectu,
- pełnym logowaniu błędów transportu.

To jest naturalny następny krok, bo UI i flow już istnieją.

### Etap 3. Hardening backendu meczu

Dopięcie testów i reguł dla:

- join/leave queue,
- concurrent cap,
- heartbeat/pong,
- opponent disconnect,
- reconnect grace,
- auto-abandon,
- końca meczu i wyniku.

### Etap 4. Rozszerzenie produktu

Dopiero po stabilizacji można iść dalej z:

- większą liczbą kategorii,
- rankingiem sezonowym,
- nagrodami/progresem,
- lepszym onboardingiem z poziomu lobby.

## Checklist dla developera

### Już jest

- button `Tryb rankingowy` kieruje do osobnego modułu,
- routing rankingu działa,
- ranking ma osobny kontroler i widok,
- istnieją endpointy queue/match/history/stream,
- istnieje warstwa realtime frontendowa,
- istnieje config rankingowy,
- ranking jest odseparowany od zwykłego `StudySession`.

### Do dopięcia

- potwierdzić finalną konfigurację WebSocket/SSE dla środowiska docelowego,
- dopisać/regresyjnie odpalić testy disconnect/reconnect/server_full,
- dopiąć monitoring transportu,
- zdecydować politykę kategorii dla rankingu,
- dopracować finalny UX wejścia z `/nauka`.

## Najkrótsza odpowiedź architektoniczna

Jeżeli ktoś wróci do tego tematu za kilka tygodni, najważniejsza zasada brzmi:

`Tryb rankingowy` rozwijamy jako osobny moduł `Ranked*`, a nie jako kolejny wariant `StudySession`.

Button ma być tylko wejściem do tego modułu, nie miejscem, w którym dokładamy kolejne wyjątki do klasycznej sesji nauki.
