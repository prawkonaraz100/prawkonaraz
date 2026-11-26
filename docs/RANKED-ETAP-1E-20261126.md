# Ranking 1 na 1, etap 1e: wspólny zegar i lekki strumień (2026-11-26)

Dotyczy wyłącznie trybu rankingowego 1 na 1. Plan i kontekst: `docs/RANKED-1V1-ROADMAP.md`, sekcja 1.6.

## Co się zmienia

**Przed:** każdy otwarty strumień SSE co sekundę liczył cały stan gracza: overview, przejścia czasowe meczu, odtworzenie nagrania i odczyt zdarzeń, z zapytaniami do bazy. Działo się to nawet wtedy, gdy nic się nie zmieniło.

**Po:**

1. **Wspólny zegar** (`php artisan ranked:clock`, usługa systemd). Jeden proces co sekundę obsługuje wszystkie aktywne mecze:
   - start po odliczaniu,
   - koniec czasu,
   - rozłączenie i walkower,
   - odpowiedzi nagrań,
   - łączenie graczy, którzy minęli się w kolejce.

   Mecz, w którym nic nie wypada, sprawdza w pamięci, bez blokad i zapisów.
2. **Sygnał zmian w Redis.** Każde zatwierdzone zdarzenie meczu oraz dołączenie do kolejki lub wyjście z niej zmienia „wersję” obu graczy (`ranked:rt:user:{id}`). Wersja zmienia się po commicie transakcji, więc strumień nie przegapi zdarzenia.
3. **Lekki strumień.** Gdy zegar żyje (puls w Redis młodszy niż 5 s), strumień:
   - co 250 ms czyta tylko wersję (jeden GET z Redis),
   - pełny stan liczy po zmianie wersji albo co 10 s (wtedy też odświeża obecność i wysyła heartbeat),
   - jedno połączenie trwa 55 s zamiast 25 s, więc jest mniej rozruchów Laravela.

   Gdy zegar stanie, strumień w locie wraca do dawnego trybu (pełny stan co 1 s).
4. **Tańsze odtwarzanie nagrań.** Harmonogram odpowiedzi nagrania liczony jest raz i trzymany w pamięci zegara. Blokada wiersza meczu pojawia się tylko wtedy, gdy faktycznie wypada odpowiedź.

Frontend, API i baza danych się nie zmieniają. Nie ma migracji.

## Pomiar w kopii z emulacją CPU produkcji (0,62 rdzenia, 20 graczy, 6 uczących się w tle)

A/B na tym samym kodzie: przełącznik `RANKED_CLOCK_ENABLED` (wyłączony = dawne zachowanie).

**CPU serwera w czasie próby (CPU-sekundy):**

| Wariant | Zegar | Strumienie PHP | PostgreSQL | Pula `www` | Zegar (proces) | Razem* | Przycinanie CPU |
|---|---|---|---|---|---|---|---|
| Najgorszy (wszystkie mecze startują razem) | wył. | 13,0 | 6,3 | 20,6 | — | 42,4 | 47% |
| Najgorszy | wł. | 6,9–7,2 | 2,7–2,9 | 20,3–21,1 | 0,6–0,8 | 33,4–34,4 | 38% |
| Rozłożony (dołączanie na 30 s) | wył. | 15,0 | 7,5 | 21,7 | — | 47,1 | 37% |
| Rozłożony | wł. | 7,6 | 3,0 | 22,5 | 0,7 | 36,7 | 29% |

\* bez generatora ruchu. Kolumna „Zegar (proces)” to przyrost w grupie „other”.

**Opóźnienia (p95):**

| Wariant | Zegar | Odpowiedź w meczu | Zdarzenie u przeciwnika | Nauka | Wynik progów |
|---|---|---|---|---|---|
| Najgorszy | wył. | 897 ms | 1324 ms | 528 ms | przekroczone (odpowiedź, nauka) |
| Najgorszy | wł. | 464–690 ms | 318–511 ms | 565–579 ms | przekroczone (nauka, raz odpowiedź) |
| Rozłożony | wył. | 441 ms | **1698 ms** | 434 ms | przekroczone (zdarzenie) |
| Rozłożony | wł. | 253 ms | 335 ms | 261 ms | **wszystko OK** |

**Wnioski:**

- Strumienie zużywają o ok. 45–50% mniej CPU, baza o ok. 55–60% mniej. Cały serwer zużywa o ok. 20% mniej CPU przy tym samym ruchu.
- Zdarzenie dociera do przeciwnika 3–5× szybciej (p95 ok. 0,3–0,5 s zamiast 1,3–1,7 s), bo strumień sprawdza zmiany co 250 ms, a nie co sekundę.
- **Teraz najwięcej kosztuje pula `www`** (zapisy odpowiedzi, nauka, strony): ok. 60% CPU serwera. W wariancie najgorszym wszyscy gracze odpowiadają w tych samych sekundach (pytania odblokowują się co 6 s jednocześnie we wszystkich meczach), więc kolejka zapisów odpowiedzi rośnie skokowo. Etap 1e tego nie zmienia.
- Kopia jest głośna: ten sam krok potrafi różnić się o ±50% opóźnień między przebiegami. Najstabilniejsze są CPU-sekundy i przycinanie CPU. Ostateczny limit trzeba ustalić testem na produkcji.

## Wdrożenie (Sol, po zgodzie właściciela)

Baza: aktywne wydanie produkcji. Gałąź funkcjonalna, potem PR. Kopia bazy jest niepotrzebna (bez migracji), ale poprzednie wydanie musi być gotowe do powrotu.

**Pliki:**

- nowe:
  - `app/Support/RankedRealtimeSignal.php`
  - `app/Support/RankedRealtimeStreamLoop.php`
  - `app/Console/Commands/RankedClockCommand.php`
  - `app/Console/Commands/RankedClockRestartCommand.php`
  - `tests/Feature/RankedClockTest.php`
  - `deploy/mikrus/ranked/prawkonaraz-ranked-clock.service.example`
- zmienione:
  - `app/Support/RankedMatchService.php`
  - `app/Http/Controllers/ApiRankedStreamController.php`
  - `config/ranked.php`
  - `deploy/mikrus/deploy.sh` (po `php artisan up`: `ranked:clock-restart`)
  - `.env.example`

**Kroki:**

1. Wdrożyć kod jak zwykle i uruchomić `deploy.sh` (zawiera `ranked:clock-restart`, nieszkodliwe bez usługi). Flaga jest jeszcze wyłączona, więc zachowanie się nie zmienia.
2. Zainstalować usługę:
   - `cp deploy/mikrus/ranked/prawkonaraz-ranked-clock.service.example /etc/systemd/system/prawkonaraz-ranked-clock.service`
   - `systemctl daemon-reload`
3. W `.env` ustawić `RANKED_CLOCK_ENABLED=true`, potem `php artisan config:cache`.
4. `systemctl enable --now prawkonaraz-ranked-clock`.
5. Sprawdzić:
   - `systemctl status prawkonaraz-ranked-clock` i `journalctl -u prawkonaraz-ranked-clock -n 20` (powinno być „ranked:clock start”),
   - `php artisan ranked:clock --once` (zwraca JSON z liczbą sprawdzonych meczów),
   - ręczny mecz B–B na telefonie: odliczanie, odpowiedzi przeciwnika na żywo, koniec meczu; potem mecz z nagraniem.
6. Test rampy jak w `docs/RANKED-CAPACITY-RAMP-20261126.md`, wariant najgorszy i rozłożony, kroki `6,8,10,12,14`. Tymczasowo:
   - `RANKED_MAX_CONCURRENT_PLAYERS=20`,
   - `STREAM_MAX=24 bash scripts/prod-ranked-capacity.sh apply`,
   - `config:cache`.
7. Ustawić limit na największą liczbę, która przeszła wariant najgorszy, a `STREAM_MAX` = limit + 4. Sprzątanie kont testowych jak poprzednio.

**Od Sola potrzebne:** `ramp-worst.json`, `ramp-real.json` i log `prod-ranked-monitor` z testu. Oddzielnie: pomiar bez obciążenia z tego samego komputera (p95 `/up` i zapisu odpowiedzi w nauce, ok. 30 żądań), żeby oddzielić opóźnienie sieci przez Cloudflare od pracy serwera.

**Wycofanie (od najszybszego):**

1. `RANKED_CLOCK_ENABLED=false` i `php artisan config:cache`. Strumienie po ok. 5 s wracają do dawnego trybu, a zegar przestaje liczyć. Potem `systemctl disable --now prawkonaraz-ranked-clock`.
2. Poprzednie wydanie (bez migracji do cofania).

**Monitorowanie:**

- `journalctl -u prawkonaraz-ranked-clock` (restart co godzinę jest normalny: `--max-seconds=3600`),
- w logu Laravela wpisy `ranked.clock.query_failed`.

Gdy zegar stoi, mecze nadal działają po staremu: strumień wraca do pełnego trybu, a `ranked:sweep` co minutę sprząta resztę.

## Weryfikacja

- Testy rankingu na SQLite: 126/126, w tym 17 nowych w `RankedClockTest`:
  - wersja zmienia się po commicie,
  - start, koniec czasu i walkower przez zegar,
  - łączenie kolejki,
  - tick bez blokad i zapisów, gdy nic nie wypada,
  - nagranie bez blokady, gdy nic nie wypada,
  - dzierżawa jednej instancji, puls, flaga, restart,
  - strumień: tryb lekki, reakcja na zmianę, powrót do dawnego trybu, dłuższe połączenie.
- Testy rankingu na PostgreSQL 16: 71/71.
- Pint bez uwag. `deploy.sh`: `bash -n` OK.
