# Tryb Rankingowy 1v1

## Cel

Ten dokument zamienia spec z pliku `C:/Users/xxx/Desktop/websocket_event_spec_simplified.md` na plan wdrożeniowy dla tego repo.

Ma być jednym źródłem prawdy dla:

- produktu `Tryb rankingowy` dostępnego z `/nauka`,
- kontraktu frontend-backend dla eventów realtime,
- kolejności wdrożenia,
- granicy między tym, co działa dziś jako UI, a tym, co musi dowieźć backend WebSocket.

## Co dowozimy teraz

W tym kroku wdrażamy:

- osobną stronę `/nauka/ranking`,
- UI shell 1v1 dla zalogowanego użytkownika,
- maksymalnie uproszczony default UI pod testy 2 graczy:
  - na pierwszym planie ranking innych użytkowników,
  - obok prosty panel startu `profil -> start`,
  - publiczny flow działa dziś na jednej kategorii `B`, więc użytkownik nie wybiera już kategorii ręcznie,
  - stare query stringi `?category=...` są ignorowane przez ranking i nie są częścią flow produktu,
  - status kolejki i mecz dopiero po wejściu w flow, bez osobnego panelu historii w lobby użytkownika,
  - bez eksponowania diagnostyki developerskiej w UI użytkownika,
- rozdzielony flow produktu na cztery osobne ekrany:
  - `/nauka/ranking` jako lobby z rankingiem i przyciskiem startu,
  - `/nauka/ranking/oczekiwanie` jako czysty ekran matchmakingu z timerem,
  - `/nauka/ranking/mecz` jako skupiony ekran pytań bez tabel startowych,
  - `/nauka/ranking/wynik` jako uproszczony ekran końca meczu `gracz vs gracz`,
- lokalną symulację scenariuszy ze specyfikacji,
- logikę prezentacji queue, meczu, disconnectów i wyniku,
- dziennik eventów zgodny z kontraktem `api_version = 1.0`,
- backendowy bootstrap rankingu przez HTTP:
  - `GET /api/v1/ranked/overview`
  - `GET /api/v1/ranked/history`
  - `POST /api/v1/ranked/queue/join`
  - `POST /api/v1/ranked/queue/leave`
  - `GET /api/v1/ranked/matches/{match}`
  - `GET /api/v1/ranked/matches/{match}/events`
  - `POST /api/v1/ranked/matches/{match}/answers`
  - `POST /api/v1/ranked/matches/{match}/ready`
  - `POST /api/v1/ranked/matches/{match}/abandon`
  - `POST /api/v1/ranked/matches/{match}/pong`
  - auto-match dwóch graczy w tej samej kategorii,
  - heartbeat/presence przez HTTP,
  - cache/Redis-backed source of truth dla heartbeatów i `last_seen`,
  - timeout disconnectu i reconnect window,
  - auto-abandon meczu po wygaśnięciu reconnectu,
  - persystentny event feed backendowy zgodny z kontraktem `api_version = 1.0`,
  - zapis wyniku meczu i persystencję historii po HTTP,
  - queue polling po froncie przed wejściem w aktywny mecz,
  - lokalny zegar UI dla queue wait, match timer i reconnect countdownów,
- pierwszy prawdziwy bridge WebSocket:
  - plain PHP WebSocket server uruchamiany komendą `php artisan ranked:websocket-serve`,
  - per-user ticket auth do połączenia WS,
  - wspólny publisher eventów dla SSE i WS,
  - dockerowy serwis `ranked-websocket`,
  - frontend preferujący ticketowany `websocket.url`, ale nadal umiejący spaść do `sse` i `polling`.

Nie wdrażamy jeszcze:

- docelowego, utwardzonego kanałowego transportu realtime,
- kanałowego transportu presence na Redisie pod WebSocket,
- właściwego kanałowego feedu queue ponad obecny bridge `WS + SSE + polling fallback`.

To jest celowe. Najpierw zamrażamy produktowy shell i kontrakt eventów, a dopiero potem podpinamy transport realtime.

## Źródło prawdy

Bazą jest uproszczona specyfikacja WebSocket dla 1v1:

- heartbeat co `10s`,
- disconnect po `3` heartbeat bez ponga, czyli `30s`,
- reconnect grace `25s`,
- queue per user na kanale `queue:{user_id}`,
- match events na kanale `match:{match_id}`,
- `40` pytań i `90s` czasu meczu,
- limit `100` concurrent graczy,
- deduplikacja po `event.id`.

## Stany produktu na froncie

Frontend musi umieć obsłużyć dokładnie te stany:

1. `idle`
2. `queueing`
3. `matched`
4. `in_progress`
5. `opponent_disconnected`
6. `server_full`
7. `finished`
8. `abandoned`

To są stany produktu, nie tylko nazwy eventów. Jeden event może przenieść UI między stanami, ale sam frontend też utrzymuje lokalny timer, reveal pytań i deduplikację.

## Event contract v1.0

### Kanały

- `queue:{user_id}` dla matchmakingu i sytuacji `server_full`
- `match:{match_id}` dla heartbeatów i wszystkich eventów meczu

### Eventy wymagane

- `heartbeat`
- `error`
- `queue.matched`
- `queue.server_full`
- `queue.left`
- `queue.queued`
- `queue.resumed`
- `match.started`
- `match.countdown_started`
- `match.opponent_answered`
- `match.opponent_disconnected`
- `match.opponent_reconnected`
- `match.finished`
- `match.abandoned`

### Wymagania wspólne

Każdy event musi zawierać:

- `id`
- `event`
- `api_version`
- `timestamp`

Frontend musi:

- ignorować duplikaty po `id`,
- używać zegara serwera do znaczników zdarzeń,
- utrzymywać lokalny countdown meczu bez `match.timer_update`,
- renderować reveal pytań lokalnie na podstawie `revealed_at`.

## Architektura w tym repo

### Warstwa wejścia

- `resources/js/Pages/Session/Index.vue`
- `app/Http/Controllers/RankedSessionPageController.php`
- `resources/js/Pages/Session/Ranking.vue`

`/nauka` pozostaje ekranem wyboru trybu, ale `Tryb rankingowy` nie jest już martwym placeholderem. Przycisk prowadzi do osobnego shellu 1v1.

### Warstwa symulacji

- `resources/js/utils/rankingModeSimulation.ts`

To jest adapter tymczasowy. Dziś produkuje lokalny skrypt eventów, ale jego interfejs ma od razu odpowiadać przyszłemu adapterowi WebSocket.

Docelowo ta warstwa powinna zostać rozbita na:

- `RankedMatchSocketClient`
- `RankedMatchStore`
- `RankedMatchEventReducer`
- `RankedMatchQueueService`

Aktualnie mamy już pośredni krok:

- `rankedRealtimePollingTransport.ts`
- `rankedRealtimeEventSourceClient.ts`

Te pliki izolują scheduling oraz live stream `overview/events` od widoku i mają zostać później podmienione na docelową implementację kanałowego WebSocketa bez ruszania całego UI.

Po stronie backendu pierwszy bridge WebSocket jest już wydzielony do:

- `app/Support/RankedRealtimeConnectionState.php`
- `app/Support/RankedRealtimeEventStreamPublisher.php`
- `app/Support/RankedWebSocketTicketService.php`
- `app/Console/Commands/ServeRankedWebSocketCommand.php`

To nie jest jeszcze finalny realtime backbone, ale daje już prawdziwy transport WS na tym samym kontrakcie eventów co SSE.

## Docelowy backend module boundary

Nie wciskamy 1v1 do istniejącego `StudySession`.

To powinien być osobny moduł domenowy, na przykład:

- `ranked_matches`
- `ranked_match_players`
- `ranked_match_answers`
- `ranked_queue_entries`
- `ranked_player_ratings`

Powód jest prosty:

- `StudySession` jest dzisiaj sesją jednoosobową,
- 1v1 potrzebuje obecności graczy, timeoutów, walkoverów i ELO,
- miksowanie tego w jednym modelu zwiększyłoby ryzyko regresji w `learn/exam/review`.

## Backend responsibilities

### HTTP

Minimalny zestaw endpointów do podpięcia później:

1. `POST /api/v1/ranked/queue/join`
2. `POST /api/v1/ranked/queue/leave`
3. `GET /api/v1/ranked/history`
4. `GET /api/v1/ranked/matches/{match}`
5. `POST /api/v1/ranked/matches/{match}/answers`
6. `POST /api/v1/ranked/matches/{match}/ready`
7. `POST /api/v1/ranked/matches/{match}/pong`

### WebSocket

Serwer realtime docelowo musi:

- emitować `heartbeat` co 10 sekund,
- przyjmować `pong`,
- pilnować `last_seen:{user_id}` z TTL `25s`,
- wykrywać disconnect po `3` heartbeat bez odpowiedzi,
- emitować `match.opponent_disconnected` i `match.opponent_reconnected`,
- pilnować globalnego limitu `100` concurrent users,
- emitować `error + queue.server_full` gdy limit jest przekroczony.

Pierwszy bridge WebSocket już istnieje, ale jeszcze nie jest finalnym kanałowym transportem domenowym:

- handshake i auth odbywają się dziś przez ticket w URL,
- bridge korzysta ze wspólnego publishera eventów współdzielonego z SSE,
- frontend dostaje już realny `websocket.url`,
- przy problemach z WS klient spada do `sse`, a potem do `polling`.

### Redis

Minimalne klucze robocze:

- `last_seen:{user_id}`
- `active_users`
- `processed_events:{match_id}`
- `ranked_queue:{category_code}`

Docelowo Redis ma też przejąć większą część realtime backbone:

- presence pod kanały WS,
- active connections,
- queue/match fanout,
- restart-safe state dla długich połączeń.

## Frontend responsibilities po podpięciu realtime

Frontend rankingowy musi:

1. subskrybować `queue:{user_id}` zanim gracz trafi do meczu,
2. przepiąć się na `match:{match_id}` po `queue.matched`,
3. odpowiadać `pong` na każdy `heartbeat`,
4. ignorować duplikaty eventów,
5. utrzymywać lokalny timer `90s`,
6. lokalnie odsłaniać pytania na podstawie `revealed_at`,
7. pokazać ekran disconnectu z odliczaniem do reconnect deadline,
8. pokazać wynik końcowy z `answer_breakdown` i ELO delta.

## Stany i przejścia UX

### Queue

- użytkownik wchodzi do rankingu i klika `Start ranking`,
- UI wchodzi w `queueing`,
- jeśli serwer pełny: `error` i `queue.server_full`,
- jeśli przeciwnik znaleziony: `queue.matched`.

### Match warmup

- po `queue.matched` UI pokazuje przeciwnika i ekran oczekiwania,
- frontend wysyła `ready`, gdy ekran pytań jest naprawdę załadowany,
- backend czeka na `ready` od obu stron,
- gdy obie strony są gotowe, backend emituje `match.countdown_started`,
- po wspólnym countdownie przychodzi `match.started`,
- frontend inicjuje lokalny reveal pytań i licznik czasu.

### Match live

- `match.opponent_answered` aktualizuje scoreboard przeciwnika,
- `heartbeat` odświeża stan połączenia,
- brak pong przez `30s` przełącza UI w `opponent_disconnected`.

### Recovery

- jeśli przeciwnik wróci w `25s` -> `match.opponent_reconnected`,
- jeśli nie wróci -> `match.abandoned`.

### Final

- `match.finished` dla normalnego końca,
- `match.abandoned` dla walkowera, timeoutów, braku reconnectu albo intencjonalnego poddania meczu przez użytkownika.

## Kolejność wdrożenia

### Etap 0

UI shell, lokalna symulacja i HTTP bootstrap backendu.

To jest stan obecny po tej zmianie. Mamy już:

- osobny moduł danych dla rankingu,
- przegląd stanu rankingu dla użytkownika,
- join/leave queue,
- auto-match bez WebSocketów,
- fair start meczu przez `ready` handshake zamiast startu od pierwszej odpowiedzi,
- heartbeat `pong` bez WebSocketów,
- HTTP-owy shim presence z reconnect window,
- persystencję eventów `queue/match` po backendzie,
- inkrementalne pobieranie eventów po `after_event_id`,
- frontendowy reducer eventów na prawdziwym feedzie backendowym,
- frontendowy clock helper dla countdownów i timerów bazujących na backendowych timestampach,
- automatyczny polling kolejki, zanim pojawi się `active_match`,
- osobną warstwę pollingu `overview/events/pong`, która przygotowuje swap na WS,
- backendowy stream `text/event-stream` emitujący `heartbeat`, `overview.sync`, `queue.left`, `queue.queued`, `queue.resumed`, `queue.server_full`, `queue.matched` i `match.events`,
- backendowy stream `text/event-stream` emitujący również live `error` dla queue-side `SERVER_FULL`, na tym samym kształcie danych co HTTP `error_event`,
- frontendowy klient EventSource z fallbackiem do obecnego pollingu,
- wspólny dispatcher payloadów oraz klient WebSocket, który zna ten sam kontrakt eventów co SSE,
- negocjację transportu po propsach backendu z preferencją `websocket -> sse -> polling`,
- `pong` sterowany eventem `heartbeat`, kiedy aktywny mecz jest już obsługiwany przez live stream,
- planszę pełnego meczu 40 pytań w UI z nawigacją pytanie-po-pytaniu zamiast skróconego dev preview,
- realny `response_time_ms` liczony w kliencie od wejścia w pytanie, żeby wynik `faster_time` był testowalny w praktyce,
- cache-backed `RankedMatchPresenceStore`, który w dockerowym runtime korzysta z Redis jako source of truth heartbeatów,
- backendowe `server_full` jako source of truth w `ranked_queue_entries`,
- auto-resume `server_full -> queued/matched` przy kolejnym `overview`,
- submit odpowiedzi,
- endpoint `ready`, który potwierdza załadowanie ekranu meczu po stronie klienta,
- wspólny countdown backendowy po `ready` obu stron:
  - `match.countdown_started`,
  - `countdown_started_at`,
  - `countdown_seconds`,
  - `starts_at`,
- blokadę odpowiedzi przed właściwym `match.started` z błędem `MATCH_NOT_STARTED`,
- poprawkę CSRF w wspólnym `apiClient`, dzięki której UI używa stabilnego `X-XSRF-TOKEN` i nie blokuje już rankingu błędem `419`,
- natychmiastowe odświeżenie własnego progresu i planszy pytań po odpowiedzi gracza w `Ranking.vue`,
- backendowe auto-zamykanie meczu jako `finished` po przekroczeniu `started_at + duration_seconds`, nawet jeśli nie padł jeszcze komplet odpowiedzi,
- odrzucanie spóźnionych odpowiedzi po limicie czasu bez cofania już zapisanego `match.finished`,
- finish meczu z ELO,
- abandon meczu po disconnect timeout,
- bogatszy payload `match.started`:
  - `status`,
  - `player1` / `player2`,
  - pełna lista `questions`,
  - `revealed_at` wyliczone po backendzie dla każdego pytania,
- bogatszy payload `match.opponent_answered`:
  - `current_question`,
  - `opponent_score`,
  - `last_answer`,
  - `answered_at`,
- bogatszy payload `match.opponent_disconnected` / `match.opponent_reconnected`:
  - `reconnect_deadline` obok `reconnect_deadline_at`,
  - `timeout_seconds`,
  - `opponent_current_state`,
- rankedowy HTTP error envelope dla `/api/v1/ranked/*`:
  - top-level `error`,
  - `error_event` kompatybilny z kontraktem realtime,
  - zachowane `errors` dla odpowiedzi walidacyjnych `422`,
  - mapowanie:
    - `MATCH_NOT_FOUND`,
    - `MATCH_FINISHED`,
    - `INVALID_ANSWER`,
- bogatszy payload `match.finished` / `match.abandoned`:
  - `status`,
  - `finished_at` / `abandoned_at`,
  - `winner` / `winner_by_default` / `loser`,
  - current-user-aware snapshot:
    - `current_user`,
    - `opponent`,
    - `outcome`,
    - `result_label`,
  - `scores`,
  - `elo_changes`,
- frontendowy reducer eventów, który rozumie `opponent_score` w kształcie zgodnym ze specyfikacją, a nie tylko stare pola top-level,
- frontendowy reducer eventów, który rozumie reconnect również z `reconnect_deadline` i `opponent_current_state`,
- frontendowy shell rankingu, który pokazuje błędy backendu już na podstawie `error_event`,
- frontendowy reducer, który pracuje już na połączonym strumieniu `queue + match`, więc:
  - projection state widzi też `queue.left`, więc opuszczenie kolejki może zejść do `idle` bez czekania wyłącznie na kolejny snapshot overview,
  - projection state widzi też `queue.resumed`, więc powrót z `server_full` do `queueing` jest first-class przejściem kanału queue,
  - projection state widzi `queue.server_full`,
  - projection state przechowuje `position_in_queue`, `current_capacity`, `max_concurrent_players` i `estimated_wait_minutes`,
  - `queue.matched` czyści queue-side blocker i przywraca countdown pod live match flow,
  - nowe wejście w `queueing`, `server_full` i `matched` czyści też runtime poprzedniego meczu, więc poprzedni `matchId`, `finishedAt` i snapshot przeciwnika nie przeciekają do kolejnej próby queue,
  - projection state hydratuje też terminalny snapshot przeciwnika bezpośrednio z `match.finished` / `match.abandoned`, więc końcowy stan jest spójny także bez osobnego `overview.sync`,
  - projection state hydratuje też terminalny snapshot bieżącego gracza:
    - `currentUserAnsweredCount`,
    - `currentUserCorrectAnswers`,
    - `currentUserEloChange`,
    - `outcome`,
    - `resultLabel`,
    - `resultCategoryName`,
    - `resultTotalQuestions`,
- frontendowy event log, który dostaje też syntetyczne `overview.sync`:
  - snapshot kolejki i active match trafia do wspólnego raw logu backendowego,
  - live kanał queue emituje też jawne `queue.left`, więc wyjście z kolejki jest osobnym krokiem eventowym widocznym w innych zakładkach,
  - live kanał queue emituje też jawne `queue.queued`, więc zwykłe oczekiwanie na pairing nie musi być rozpoznawane wyłącznie po syntetycznym snapshotcie overview,
  - live kanał queue emituje też jawne `queue.resumed`, więc zwolnienie slotu po `server_full` nie musi być rozpoznawane wyłącznie po kolejnym snapshotcie overview,
  - kontrakt streamu kanału queue ma już regresje dla `queue.server_full`, `queue.queued` i `queue.matched`, a helpery `queue.left` / `queue.resumed` są przypięte testami jednostkowymi kontrolera,
  - syntetyczny `overview.sync` potrafi teraz objąć także czysty snapshot `idle`, więc powrót do stanu bez kolejki i bez meczu jest widoczny w raw logu,
  - klient deduplikuje też kolejne identyczne snapshoty `idle -> idle`, żeby raw log nie rósł od pustych odświeżeń overview,
  - reducer potrafi wejść w `queueing` już na bazie samego `overview.sync`,
  - reducer potrafi też zbootstrapować z `overview.sync` stan `idle`, aktywnego albo świeżo zakończonego meczu po reloadzie klienta,
  - `overview.sync` z `state = queued` i jednoczesnym `recent_match` nie cofa już projection state do terminalnego wyniku poprzedniego meczu,
  - reducer hydratuje z `overview.sync` także dane przeciwnika:
    - `opponentUsername`,
    - `opponentAnsweredCount`,
    - `opponentCorrectAnswers`,
  - reducer hydratuje z `overview.sync active_match.players` także własny progres gracza:
    - `currentUserAnsweredCount`,
    - `currentUserCorrectAnswers`,
  - projection state nie czeka już wyłącznie na `queue.matched` albo eventy meczu, żeby pokazać sensowny stan live,
  - raw log backendowy pokazuje już prawdziwy `queue.queued` obok syntetycznego `overview.sync`, więc bootstrap snapshot i kanał queue są od siebie czytelnie oddzielone,
  - po leave/reset kolejki raw log nie jest już czyszczony lokalnie, tylko dostaje kolejny snapshot `idle`,
  - UI może od razu po reloadzie pokazać nazwę przeciwnika i jego progres nawet zanim dociągną się pełne detale meczu,
  - UI może też od razu pokazać własny progres w reducer panelu, bez czekania na osobny payload detali meczu,
  - UI pokazuje też wynikowy `Powód` dla `finished/abandoned` z projection state, nie tylko z osobnego panelu historii,
  - UI pokazuje też `Twój wynik`, `Outcome` i `ELO` z projection state, więc terminalny ekran jest czytelny także bez odwołania do osobnego `recent_match`,
  - sekcja „Ostatni wynik” ma fallback do projection state, więc wynik końcowy może pojawić się od razu po eventach terminalnych, bez czekania na osobne `recent_match` albo historię,
  - fallback „Ostatni wynik” bierze też kategorię i liczbę pytań z projection state terminalnego meczu, zamiast zgadywać je z aktualnie wybranej kategorii w UI,
- parser i klienci transportu, które rozumieją również live `error`:
  - EventSource nie wywraca się na pustym/native `error`,
  - WebSocket i SSE dispatchują custom `error` do osobnego handlera eventowego,
  - UI potrafi pokazać ten sam komunikat niezależnie od tego, czy przyszedł po HTTP czy po streamie,
- recent result i historię,
- historię trzymaną w danych/backendzie, ale bez eksponowania jej jako osobnej ścieżki startowej dla użytkownika,
- domyślnie uproszczony ekran dla człowieka testującego, bez eksponowania diagnostyki developerskiej w UI użytkownika.
- server-renderowany leaderboard z `ranked_player_ratings`, żeby wejście do rankingu pokazywało od razu realny ranking innych użytkowników.
- uproszczony wynik końcowy `gracz vs gracz` bez dodatkowego panelu historii i bez `Rematch`.

Na tym etapie ranking jest już ręcznie sprawdzony jako testowalne MVP dla 2 zalogowanych graczy:

- oba konta mogą wejść z `/nauka/ranking` do tej samej kolejki,
- matchmaking tworzy prawdziwy mecz,
- live stream przechodzi przez `matched -> in_progress`,
- event `match.opponent_answered` dochodzi do drugiego klienta,
- własny progres odpowiadającego gracza aktualizuje się od razu po kliknięciu odpowiedzi,
- backend domyka mecz po `90s` i nie przyjmuje już odpowiedzi po czasie,
- backendowy feed `match.started` / `match.opponent_answered` ma już shape bliski lokalnej symulacji i docelowemu kontraktowi WebSocket,
- backendowy feed `match.opponent_disconnected` / `match.opponent_reconnected` ma już `timeout_seconds`, alias `reconnect_deadline` i `opponent_current_state` po reconnectcie,
- ranked API zwraca już spójne `error_event` dla `MATCH_NOT_FOUND`, `MATCH_FINISHED` i `INVALID_ANSWER`, a frontend potrafi ten envelope odczytać.

### Etap 1

Adapter transportu realtime:

- gotowe już teraz:
  - WS connection manager po froncie,
  - backendowy config/capabilities dla `preferred transport`, `ws url` i `sse fallback`,
  - ticket auth i realny `websocket.url`,
  - pierwszy backendowy bridge WS współdzielący kontrakt payloadów z SSE,
  - `pong` sender podłączony do istniejącego endpointu,
  - reducer eventów podłączony do istniejącego feedu backendowego,
  - zachowanie obecnej warstwy local clock po przejściu na WS,
- do domknięcia w tym etapie:
  - `queue` subscription jako docelowy source of truth zamiast obecnego queue pollingu,
  - `match` subscription jako docelowy source of truth zamiast obecnego incremental event pollingu,
  - redukcja zależności od SSE fallbacku,
  - observability i hardening połączeń WS.

### Etap 2

Backend queue i matchmaking:

- właściwy queue channel na WebSockecie dla `queue.server_full` / `queue.matched`,
- pełne pokrycie live `error` na streamie / WS dla błędów match-side oraz reconnect edge-case'ów, na reuse'ie obecnego HTTP `error_event`,
- pairing po kategorii w warstwie realtime,
- start meczu przez event `queue.matched` -> `match.started`.

### Etap 3

Silnik meczu:

- emit `match.started`,
- odpowiedzi graczy,
- `match.opponent_answered`,
- disconnect recovery,
- domknięcie pełnego payloadu eventów meczu pod spec:
  - live `error`,
- `finished/abandoned`.

### Etap 4

Persistence i ranking:

- historia meczów,
- rating ELO,
- statystyki usera.

## Acceptance criteria

Zmienę uznajemy za gotową na poziomie produktu, jeśli:

1. `/nauka` prowadzi do realnego trybu rankingowego zamiast placeholdera.
2. Ranking ma osobny ekran i nie psuje istniejących trybów `learn/exam`.
3. UI umie pokazać wszystkie główne scenariusze ze specyfikacji.
4. Dziennik eventów używa kontraktu `api_version = 1.0`.
5. Kod jest gotowy do wymiany lokalnej symulacji na prawdziwy adapter WebSocket.
6. Bridge WebSocket może zostać uruchomiony bez przepisywania obecnego UI, a klient nadal ma bezpieczny fallback do `sse` i `polling`.

## Testy

Minimalne testy dla tego etapu:

- route + render nowej strony rankingowej,
- poprawny wybór kategorii startowej,
- sekwencje scenariuszy symulacji:
  - happy path,
  - reconnect,
  - abandoned,
  - server full.

## Decyzja architektoniczna

Najważniejsza decyzja brzmi:

`Tryb rankingowy 1v1 jest osobnym modułem realtime, a nie kolejną odmianą StudySession`.

Dzięki temu:

- nie psujemy istniejącej nauki,
- nie mieszamy logiki jednoosobowej z wieloosobową,
- możemy podpiąć Redis i WS bez wycieku złożoności do obecnego flow sesji.
