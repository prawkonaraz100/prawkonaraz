# Ranking 1 na 1 (tryb rankingowy `/nauka/ranking`): roadmapa

**Dotyczy wyłącznie funkcji rankingu 1 na 1:** kolejka i dobieranie, mecze na żywo i z nagraniem, ELO, transport realtime (SSE / Reverb) oraz pojemność serwera. Dokument jest żywy: po każdym etapie zmień status i dopisz decyzję do rejestru (1.5).

Ostatnia aktualizacja: 2026-11-26 (etap 1e lokalnie).

Kod: `app/Support/RankedMatchService.php`, `RankedRealtimeEventStreamPublisher.php`, `ApiRankedStreamController.php`, `resources/js/Pages/Session/Ranking.vue`, `RankedRealtimeSignal.php`, `RankedRealtimeStreamLoop.php`, `app/Console/Commands/RankedClockCommand.php` (zegar), `config/ranked.php`, skrypty `scripts/ranked-*.mjs` i `scripts/prod-ranked-*`.

Widok mobilny (ciemny motyw) ma osobną roadmapę: `docs/MOBILE-DARK-UI-ROADMAP.md`.

**Legenda**

- ✅ zrobione i na produkcji
- 🟡 zrobione lokalnie, czeka na wdrożenie (Sol, po zgodzie właściciela)
- 🔵 w toku albo czeka na wynik
- ⬜ do zrobienia
- ❔ czeka na decyzję właściciela
- ⏸️ odłożone

**Dokumenty źródłowe**

- `docs/RANKED-MODE-ANALYSIS-20261125.md`: analiza trybu rankingowego, etapy 0–4, testy na produkcji (sekcje 3a–3h).
- `docs/RANKED-CAPACITY-20261125.md`: przyczyna (mało CPU w kontenerze LXC), etap 1d.
- `docs/RANKED-CAPACITY-RAMP-20261126.md`: dobór limitu graczy i runbook testu na produkcji.
- `docs/RANKED-ETAP-1E-20261126.md`: wspólny zegar i lekki strumień: zmiany, pomiar A/B, wdrożenie i wycofanie.

---

## 1. Stan prac

### 1.1 Zrobione

| Status | Zakres | Co dało | Źródło |
|---|---|---|---|
| ✅ | **Etap 0: uczciwy wynik** | Czas odpowiedzi liczony przez serwer. Bezpieczne dobieranie (blokady i unikalny indeks), więc koniec z podwójnymi meczami i błędami 500. Kategoria z `?category=` i profilu. Polskie teksty, wiersz „Ty” w rankingu, usunięty martwy kod. | ANALYSIS 3c |
| ✅ | **Mecz z nagraniem** | Gdy nie ma przeciwnika, gracz ściga się z zapisanym meczem prawdziwego gracza z tej samej kategorii. Przeciwnik jest jawnie oznaczony „· nagranie”. Pełne ELO tylko dla żywego gracza. Propozycja po 20 s czekania i przycisk w lobby. | ANALYSIS 3d |
| ✅ | **Etap 1 / 1b: lżejsze odczyty** | Tick strumienia: 120 → 15 zapytań SQL. Zapis odpowiedzi: 31 → 14 zapytań. Kursor zdarzeń w SQL. `ranked:sweep` co minutę rozstrzyga porzucone mecze. | ANALYSIS 3e–3f |
| ✅ | **Poprawki po teście produkcyjnym (3h)** | Pula strumieni `static`. API rankingu bez danych Inertia. Klient wysyła 1 żądanie na odpowiedź zamiast 4. `event:cache`, `view:cache` i `filament:optimize` przy wdrożeniu. | ANALYSIS 3h |
| ✅ | **Diagnoza wydajności** | Kontener LXC ma efektywnie ok. 0,5–0,6 rdzenia, mimo że widzi „2 CPU”. Emulacja tego w kopii (cgroup 0,62 rdzenia) daje wyniki zgodne z produkcją. | CAPACITY |
| ✅ | **Etap 1d: wolniejsze tempo i odchudzenie** (wdrożone 2026-11-26, bez migracji; Sol sprawdził prawdziwy mecz B–B) | Format 15 pytań, nowe co 6 s, mecz 90 s (konfigurowalny). Remis rozstrzyga suma czasów poprawnych odpowiedzi. `RANKED_MAX_CONCURRENT_PLAYERS` w `.env`. Obecność przez strumień (Redis). Dostęp do produktu w cache na 60 s. Możliwe `config:cache`. | CAPACITY |
| ✅ | **Narzędzia testu pojemności** (użyte w teście na produkcji) | `scripts/ranked-loadgen.mjs` z botami uczącymi się w tle i sondą strony. `scripts/ranked-capacity-ramp.mjs` uruchamia próby z rosnącą liczbą graczy, ocenia je progami i zapisuje JSON z rekomendacją. | RAMP |
| ✅ | **Emulowany dobór limitu** | Wariant najgorszy: 16 graczy OK, 20 przekroczone. Wariant realistyczny: 18 OK, 20 na granicy. Rekomendacja na start była **16**, pula strumieni 20. **Produkcja pokazała mniej (6), więc emulacja zawyża wynik ok. 2,5×; przyczyna do ustalenia (1.2).** | RAMP |
| ✅ | **Test rampy na produkcji (Sol, 2026-11-26)** | Przy 8 graczach równoczesny start przekroczył progi opóźnień. Przy 6 graczach oba warianty przeszły, z 6 osobami uczącymi się w tle: zapis odpowiedzi p95 363–376 ms, zdarzenie u przeciwnika 1,14–1,19 s, zero 503. **Stały limit: 6 graczy naraz, 10 połączeń SSE.** 30 kont pomiarowych i ich dane usunięte. Kopia bazy i poprzednie wydanie gotowe do rollbacku. | wiadomość Sola |
| 🟡 | **Etap 1e: wspólny zegar i lekki strumień** (lokalnie 2026-11-26, czeka na wdrożenie) | Usługa `ranked:clock` prowadzi przejścia czasowe wszystkich meczów. Strumień co 250 ms sprawdza wersję w Redis i liczy stan tylko po zmianie albo co 10 s; połączenie trwa 55 s. Tańsze nagrania. Kopia z emulacją CPU, 20 graczy, A/B: strumienie −45–50% CPU, baza −55–60%, cały serwer ok. −20%, zdarzenie p95 1,3–1,7 s → 0,3–0,5 s. Wariant rozłożony przechodzi wszystkie progi (wcześniej padało zdarzenie). | `docs/RANKED-ETAP-1E-20261126.md` |
| ✅ | **Analiza meczów z nagraniem** | Na mecz nagranie kosztuje ok. 55–60% zwykłego meczu, ale na żywego gracza tyle samo. Ukrywanie, że to nagranie, nie zwiększa pojemności (limit liczy ludzi), a niesie ryzyko prawne i utraty zaufania do ELO. **Decyzja: nagrania zostają jawne.** | rozmowa 2026-11-26 |

### 1.2 Teraz (następne kroki)

| # | Status | Zadanie | Kto | Uwagi |
|---|---|---|---|---|
| 1 | ✅ | Wdrożenie etapu 1d i plików UI mobilnego | Sol | Wykonane 2026-11-26, bez migracji. Według Sola aktualne widoki mobilne zachowane, a lobby sprawdzone na telefonie i komputerze. |
| 2 | ✅ | Test rampy na produkcji | Sol | Limit 6 graczy, 10 SSE (1.1) |
| 3 | ⬜ | **Ustalenie, skąd różnica 16 (emulacja) → 6 (produkcja)** | Claude, z danymi od Sola | Potrzebne: `ramp-worst.json` i `ramp-real.json` (który próg padł przy 8: odpowiedź, zdarzenie, nauka, strona czy `/up`?), log `prod-ranked-monitor` z testu (CPU, load, PSI) i czas `/up` oraz zapisu odpowiedzi bez obciążenia z tego samego komputera (opóźnienie sieci przez Cloudflare). Rozstrzyga, czy limit wyznacza CPU, czy sieć i ziarnistość ticku 1 s. |
| 4 | ⬜ | Korekta progów, jeśli winna jest sieć | Claude | Progi liczone od czasu bez obciążenia (np. p95 − p95 bez obciążenia), żeby opóźnienie Cloudflare nie zaniżało limitu |
| 5 | ✅ | Start etapu 1e | właściciel | Zgoda 2026-11-26, w tym na nową usługę systemd zegara |
| 6 | 🟡 | **Wdrożenie etapu 1e i test rampy** | Sol, po zgodzie | Kroki, wycofanie i monitorowanie: `docs/RANKED-ETAP-1E-20261126.md`. Ramp z krokami `6,8,10,12,14`, potem nowy limit. |

### 1.3 Do zrobienia: zwiększenie pojemności

Wąskim gardłem jest CPU, a nie liczba połączeń. Do etapu 1d każdy strumień co sekundę liczył stan gracza, nawet gdy nic się nie działo; etap 1e to usuwa. **Po 1e największym kosztem jest pula `www`** (zapisy odpowiedzi, nauka, strony): ok. 60% CPU serwera w czasie meczów. W wariancie najgorszym wszyscy odpowiadają w tych samych sekundach, więc zapisy odpowiedzi tworzą skokową kolejkę.

| Status | Etap | Zakres | Spodziewany efekt | Szacunek pracy |
|---|---|---|---|---|
| 🟡 | **1e: wspólny zegar i lekki strumień** | Zrobione lokalnie (1.1). Usługa systemd `prawkonaraz-ranked-clock`, flaga `RANKED_CLOCK_ENABLED`. | Zmierzone w kopii: serwer ok. −20% CPU, zdarzenie 3–5× szybciej. Spodziewany limit na produkcji: umiarkowanie wyżej niż 6 (szacunek 8–10), do potwierdzenia rampem. | zrobione |
| 🟡 | Test rampy po 1e | Kopia: zrobione (A/B). Produkcja: Sol | Nowy limit; decyzja o kolejnym kroku | ok. pół dnia |
| ⬜ | **1f: tańszy zapis odpowiedzi** (nowy największy koszt) | Zmierzyć CPU jednego `POST answers` (rozruch Laravela, middleware, budowa odpowiedzi) i odchudzić: lżejsza odpowiedź API, mniej middleware na trasie API rankingu, ewentualnie wspólne zapytanie o mecz i graczy. | Zależny od profilu; zapisy odpowiedzi to dziś większość CPU puli `www` w czasie meczów | ok. 1 dzień |
| ⬜ | **1g: strumień liczy tylko to, co się zmieniło** | Dziś każda zmiana wersji (np. odpowiedź przeciwnika) liczy pełny stan: overview i zdarzenia. Osobna wersja „zdarzenia” i „stan” pozwoli przy odpowiedzi przeciwnika czytać tylko nowe zdarzenia. | Dalsze ok. 30–50% mniej CPU strumieni (dziś ok. 20% CPU serwera) | mały |
| ⏸️ | **2: Laravel Reverb** (szczegóły: 1.6) | Osobny serwer WebSocket (usługa systemd). PHP publikuje zdarzenia przy zapisie, a obecność idzie przez kanały presence. Frontend przechodzi na Laravel Echo. Stary strumień SSE zostaje jako zapas za przełącznikiem. Nowa sekcja WebSocket w nginx, restart Reverb przy wdrożeniu, prywatne autoryzowane kanały meczu. | **Szacunek skorygowany 2026-11-26:** Reverb usuwa koszt strumieni PHP, który po 1e to już tylko ok. 20% CPU serwera. Zapisy odpowiedzi zostają. Realny zysk to raczej +20–30% pojemności, a nie wcześniejsze 40–60 graczy. Ma sens dopiero po 1f i 1g, gdy strumienie znów staną się dużą częścią kosztu. | ok. 3–5 dni + testy |
| ⬜ | Opcjonalnie: wznawianie strumienia od `Last-Event-ID` | Dziś każde ponowne połączenie wysyła całą historię zdarzeń meczu | Mniej zbędnej pracy (ok. 15% rdzenia przy 20 graczach) | mały |

**Warunek startu Reverb (etap 2):** po 1e ramp pokazuje za mały zysk albo gracze w praktyce dochodzą do limitu (rośnie kolejka czekających, w szczycie blisko limitu). Wcześniej warto sprawdzić, ilu graczy gra naraz. Pomiar z 2026-11-25: 0 meczów w ostatnich 30 dniach.

**Aktualizacja 2026-11-26 (po pomiarze 1e):** kolejność po wdrożeniu 1e: test rampy na produkcji, potem 1f (zapis odpowiedzi) i 1g (strumień), a Reverb dopiero, gdy pomiar pokaże, że strumienie znów dominują.

**Aktualizacja 2026-11-26:** produkcja zmieściła tylko 6 graczy, czyli 3 mecze naraz. Ostatni gracz ponad limit dostaje komunikat o braku miejsc, a mecz z nagraniem nie omija limitu, bo liczy się żywy gracz. Etap 1e przesuwa się więc z „na zapas” na „pierwszy w kolejce”, a Reverb staje się bardziej prawdopodobny. Ostateczna kolejność po ustaleniu przyczyny różnicy (1.2, punkt 3).

Szczegóły obu etapów są w sekcji 1.6.

### 1.4 Do zrobienia: porządek w kodzie i produkt

| Status | Zakres | Źródło |
|---|---|---|
| ⬜ | **Backend:** podział `RankedMatchService` (kolejka, cykl meczu, obecność, ELO), jeden `RankedOverviewResource`, konfiguracja tylko w `config/ranked.php` | ANALYSIS etap 1 |
| ⬜ | **Frontend (etap 3):** rozbicie `Ranking.vue` (ponad 4600 linii) na lobby, oczekiwanie, mecz i wynik oraz composables. Serwer jako jedyne źródło stanu. Symulacja scenariuszy tylko w trybie developerskim. | ANALYSIS etap 3 |
| ⬜ | **ELO i ranking osobno dla każdej kategorii (K6):** zatwierdzone 2026-11-25, do wykonania i zweryfikowania | ANALYSIS etap 4 |
| ❔ | **Walkower:** bez utraty ELO, gdy połączenie zerwie się na samym początku meczu | ANALYSIS etap 4 |
| ⬜ | **Historia meczów i ekran wyniku** w stylu archiwum chess.com | ANALYSIS etap 4 |
| ⬜ | **Zaznaczona odpowiedź w meczu rankingowym** (dziś zielona `pk-selected`) → biała ramka jak w nauce | UI mobilne |

### 1.5 Decyzje (rejestr)

| Data | Decyzja |
|---|---|
| 2026-11-25 | Bez dobierania po ELO: pierwszy wolny gracz z tej samej kategorii |
| 2026-11-25 | Ścisła separacja kategorii, ELO i ranking osobno dla każdej kategorii |
| 2026-11-25 | Mecz z nagraniem: pełne ELO, tylko nagrania ludzi, propozycja po 20 s i przycisk w lobby |
| 2026-11-25 | Test obciążenia bezpośrednio na produkcji (domena bez ruchu) |
| 2026-11-25 | Format 15 × 6 s, limit graczy w `.env` (etap 1d) |
| 2026-11-26 | Limit startowy 16 (emulacja), ostateczny z testu na produkcji |
| 2026-11-26 | Test na produkcji: stały limit **6 graczy naraz i 10 połączeń SSE** (Sol) |
| 2026-11-26 | Nagrania zostają jawne, bez udawania żywego przeciwnika |
| 2026-11-26 | Najpierw etap 1e (wspólny zegar), decyzja o Reverb po pomiarze |
| 2026-11-26 | Zgoda na etap 1e i nową usługę systemd `prawkonaraz-ranked-clock` na produkcji (wdraża Sol, z możliwością wyłączenia flagą) |
| 2026-11-26 | Pomiar 1e: po nim największym kosztem są zapisy odpowiedzi; szacunek zysku z Reverb obniżony (1.3) |

### 1.6 Plan realtime: szczegóły (etap 1e i Reverb)

#### Co dziś obciąża serwer

Test rampy pokazał, że brakuje CPU, a nie miejsc na połączenia. Kolejka PHP-FPM wynosiła 0, a cgroup był przycinany w 37% okresów. Samo utrzymanie otwartego połączenia jest tanie.

Drogie jest to, że **każdy strumień co sekundę od nowa liczy stan gracza**: overview, `syncMatchPresence`, odczyt nowych zdarzeń i odtwarzanie nagrania, za każdym razem z blokadą i zapytaniami do bazy. Przy 16 graczach to około 16 takich obliczeń na sekundę, nawet gdy nic się nie dzieje.

Wniosek: samo przeniesienie połączeń do Reverb, gdy obliczenia nadal idą co sekundę, dałoby mało. Prawdziwa zmiana to przejście z modelu „każdy gracz pyta co sekundę” na „serwer pracuje i wysyła tylko przy zdarzeniu”. Reverb jest narzędziem do tego drugiego modelu.

#### Co robi dziś strumień i gdzie to trafi

| Zadanie strumienia dziś | Etap 1e (bez nowej usługi) | Etap 2 (Reverb) |
|---|---|---|
| **Dostarczanie zdarzeń** (odpowiedź przeciwnika, następne pytanie, koniec meczu) | Strumień tylko sprawdza nowe zdarzenia (najlepiej przez Redis), bez liczenia stanu | PHP zapisuje zdarzenie (tabela zdarzeń meczu już jest) i od razu publikuje je do Reverb, a Reverb przekazuje je przeglądarce |
| **Obecność** (czy gracz jest połączony) | Bez zmian względem 1d: tick strumienia co 10 s, Redis | Kanał presence w Reverb, bez odświeżania obecności przez PHP |
| **Zegar** (koniec czasu na pytanie, walkower, odtwarzanie nagrań) | **Jeden proces zegara dla wszystkich meczów** (systemd, co 1 s) zamiast liczenia w strumieniu każdego gracza | Bez zmian względem 1e |
| **Kojarzenie z kolejki** | Zegar albo `join` łączy graczy, a strumień tylko odbiera zdarzenie | Zdarzenie „mecz znaleziony” publikowane przez Reverb |

Po obu etapach PHP pracuje głównie przy odpowiedziach graczy (raz na pytanie na gracza) oraz w jednym wspólnym zegarze.

#### Reverb czy Mercure

| | Laravel Reverb (rekomendacja) | Mercure |
|---|---|---|
| Czym jest | Oficjalny serwer WebSocket Laravela, jeden długo działający proces PHP (pętla ReactPHP) | Hub SSE napisany w Go; PHP publikuje do huba przez HTTP |
| Protokół | Pusher: kanały prywatne, kanały presence, zdarzenia od klienta | SSE z autoryzacją przez JWT |
| Frontend | Zamiana obecnego strumienia na Laravel Echo (więcej zmian) | `EventSource` prawie bez zmian (mniej zmian) |
| Obecność | Wbudowana (presence) | Trzeba zrobić samemu |
| Stos | Ten sam ekosystem (PHP i Laravel) | Dodatkowa technologia (Go) |

Wybór Reverb: ten sam ekosystem, oficjalne wsparcie i wbudowana obecność. Mercure ma sens tylko wtedy, gdy priorytetem są minimalne zmiany we frontendzie.

#### Zmiany na serwerze (Mikrus) przy Reverb

- Usługa systemd z automatycznym restartem, nasłuch na 127.0.0.1.
- Sekcja `location` z upgrade WebSocket w istniejącym vhoście nginx, bez nowego portu publicznego. Cloudflare przepuszcza WebSockety.
- `BROADCAST_CONNECTION=reverb`. Zdarzenia jako `ShouldBroadcastNow`, bo `QUEUE_CONNECTION=sync`.
- Restart Reverb przy każdym wdrożeniu (dodatkowy krok w runbooku Sola).
- Podniesiony limit otwartych plików dla procesu (dziś `ulimit -n` to 1024).
- Pula PHP-FPM na strumienie przestaje być potrzebna, co zwalnia RAM i usuwa jeden z limitów.

> **Uwaga 2026-11-26:** etap 1e jest zrobiony, a jego wyniki są w `docs/RANKED-ETAP-1E-20261126.md`. Pomiar pokazał, że po 1e największym kosztem są zapisy odpowiedzi (pula `www`), więc szacunek poniżej dla Reverb jest za wysoki. Aktualna ocena jest w sekcji 1.3.

#### Spodziewany zysk (szacunek, do potwierdzenia rampem)

- **Dziś:** na gracza przypada ok. 1 pełne obliczenie stanu na sekundę plus odpowiedzi.
- **Po zmianie:** odpowiedzi (mniej więcej jedna na kilka sekund) plus udział we wspólnym zegarze. Praca PHP na gracza spada kilkukrotnie.
- **Szacunek:** 40–60+ graczy na tych samych ok. 0,6 rdzenia. Z tej samej mocy korzysta jednak nauka i strony publiczne, a Reverb też zużywa CPU, więc liczba wiarygodna jest dopiero z testu rampy.

#### Ryzyka i zabezpieczenia

| Ryzyko | Zabezpieczenie |
|---|---|
| Reverb przestaje działać, więc nie ma aktualizacji na żywo | Obecny strumień SSE zostaje jako zapas za przełącznikiem w konfiguracji. Przeglądarka po nieudanym połączeniu wraca do starego sposobu. |
| Zgubione zdarzenie po chwilowym rozłączeniu | Zdarzenia mają numery. Po ponownym połączeniu klient dociąga brakujące przez API. |
| Ktoś podsłuchuje odpowiedzi przeciwnika | Kanał meczu prywatny, autoryzowany przez Laravel (tylko dwaj gracze meczu) |
| Zegar to pojedynczy proces; gdy stanie, mecze nie idą dalej | systemd z restartem, monitoring. `ranked:sweep` co minutę zostaje jako siatka bezpieczeństwa. |
| Cloudflare zrywa bezczynne połączenia albo restartuje się | Ping protokołu Pusher, automatyczne wznawianie w Echo, zapasowe odpytywanie |

#### Kolejność

1. **Etap 1e** na gałęzi funkcjonalnej, z PR. Zawiera wspólny zegar, lekki strumień i tańsze odtwarzanie nagrań. Jest potrzebny niezależnie od decyzji o Reverb, bo to połowa przebudowy pod Reverb.
2. **Ramp w kopii z emulacją CPU** (wariant najgorszy i rozłożony), porównanie z obecnym limitem 16.
3. **Wdrożenie przez Sola** po zgodzie, potem ramp na produkcji i nowy limit.
4. **Decyzja o etapie 2 (Reverb)** według warunku startu z sekcji 1.3.
5. Jeśli Reverb wchodzi: usługa obok obecnego strumienia, za przełącznikiem. Ramp, potem przełączenie, a usunięcie starego strumienia dopiero po okresie stabilnego działania.

---

## 2. Uwagi techniczne

- **Testy, które nie przechodziły już wcześniej** (nie są regresją): Kod 95 `max-w-[64rem]`, `QualificationProgressReset`, PWA, tachograf.
- **Środowisko testowe:** replika `/srv/replica` z emulacją CPU `/home/claude/replica/prodemu.sh` (cgroup 0,62 rdzenia).

## 3. Zasady pracy (bez zmian)

- Gałąź funkcjonalna, potem PR. Wdrożenie tylko ręcznie, po jawnej zgodzie, z kopią i planem rollbacku. Bazą wdrożenia jest aktywne wydanie produkcji, nie `main` z GitHuba.
- Nie edytować `production-source`. Nie publikować `.env`, zrzutów bazy ani mediów. Nie używać `docker compose down -v`.
- Dostęp do produkcji tylko przez skrypty uruchamiane przez właściciela lub Sola. Klucze i hasła nigdy w czacie.
- Po każdym etapie aktualizować ten plik: zmienić status i dopisać decyzję do rejestru.
