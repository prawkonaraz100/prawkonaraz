# Ranking: pojemność produkcji (Mikrus 4.1 PRO), 2026-11-25

Uzupełnienie sekcji 3h–3j w `docs/RANKED-MODE-ANALYSIS-20261125.md`. Aktualny stan i plan: `docs/RANKED-1V1-ROADMAP.md`.

## Testy 20 graczy na produkcji (dawny format 40 pytań we własnym tempie)

| Pomiar | Test 1 | Test 2 (po poprawkach 3h) | Próg |
|---|---|---|---|
| Zdarzenie u przeciwnika, p95 | 7,69 s | 4,02 s | < 1,5 s |
| Zapis odpowiedzi rankingowej, p95 | 6,66 s | 3,19 s | < 0,3 s |
| Zapis odpowiedzi w nauce, p95 | 6,36 s | 3,24 s | < 0,5 s |
| load1, maksimum | 9,41 | 6,32 | < 1,6 |

Po obu testach limity wróciły do 2/2/5. Kod i cache (trasy, zdarzenia, widoki, Filament) zostały na produkcji.

## Przyczyna: mało CPU w kontenerze

- **Wolniejszy wątek:** test PHP trwa 329 ms na produkcji wobec 153 ms w kopii, czyli ok. 2,2× wolniej.
- **Efektywna moc:** 2 procesy naraz trwają po ok. 570 ms, a 4 po ok. 1230 ms. Kontener LXC dostaje więc łącznie ok. połowę jednego rdzenia kopii, choć widzi „2 CPU”.
- **Konkurencja o CPU:** steal w LXC zawsze wynosi 0%, ale PSI CPU `some avg60` po teście wynosił 54%.

Emulacja w kopii: cgroup z limitem 0,62 rdzenia, skalibrowana testem PHP. Dla 20 graczy daje wynik zgodny z produkcją.

## Etap 1d (decyzja właściciela: dalsze odchudzenie + wolniejsze tempo)

- **Format meczu:** 15 pytań, nowe co 6 s u obu graczy jednocześnie, mecz 90 s. Konfigurowalny przez `RANKED_MATCH_QUESTIONS`, `RANKED_QUESTION_INTERVAL_SECONDS` i `RANKED_MATCH_DURATION_SECONDS`.
- **Remis punktowy:** rozstrzyga mniejsza suma czasów reakcji na poprawnych odpowiedziach.
- **Limit graczy naraz:** `RANKED_MAX_CONCURRENT_PLAYERS`, wcześniej stała 100.
- **Obecność przez strumień:** tick co 10 s odświeża obecność w Redis. Naprawia to też błąd: gracz bez kliknięć przez 30 s był uznawany za rozłączonego mimo otwartego strumienia.
- **Cache dostępu do produktu:** pozytywna decyzja dla API rankingu pamiętana 60 s (`RANKED_ACCESS_CACHE_SECONDS`). Ban nadal sprawdzany przy każdym żądaniu.
- **`config:cache` możliwe:** `env()` przeniesione z `routes/console.php` do `config/schedule_times.php`.

Emulacja produkcji, 15 × 6 s, reakcja 0,8–4 s, bez ruchu nauki:

| Wariant | Odpowiedź p50 / p95 | Zdarzenie p95 |
|---|---|---|
| 16 graczy, start meczów w tej samej sekundzie | 100 / 500 ms | 1,09 s |
| 20 graczy, start meczów w tej samej sekundzie | 155 / 851 ms | 1,56 s |
| 20 graczy, dołączanie rozłożone na 30 s | 67 / 419 ms | 1,21 s |
| Dawny format, 20 graczy (dla porównania) | 1779 / 2575 ms | 7,39 s |

## Rekomendacja (z 2026-11-25; zastąpiona testem na produkcji 2026-11-26: limit 6 graczy, 10 SSE)

- **`.env`:** `RANKED_MAX_CONCURRENT_PLAYERS=20`. Format domyślny (15 × 6 s).
- **Serwer:** strumienie PHP-FPM `static` 24, nginx `limit_conn` 24, `www` 8 (`scripts/prod-ranked-capacity.sh apply`).
- **Wdrożenie:** dodatkowo `config:cache`, a po każdej zmianie `.env` ponownie.
- **Test na produkcji:** `scripts/ranked-loadgen.mjs --no-pong`, wariant realistyczny `--stagger-ms=30000` i wariant najgorszy (bez rozłożenia).
- **Jeśli najgorszy wariant nie przejdzie:** limit 16 przez samą zmianę `.env`.
