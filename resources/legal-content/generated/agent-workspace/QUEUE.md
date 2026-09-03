# Kolejka artykułów prawnych dla agenta

Ten plik jest krótkim indeksem operacyjnym. Status `ready` oznacza temat niewykorzystany, `published` oznacza temat posiadający artykuł.

## Podsumowanie

- Tematy `focused`: `56`.
- Gotowe do opracowania: `15`.
- Wykorzystane klastry kandydackie: `39`.
- Unikalne artykuły wskazane przez klastry: `29`.
- Małe klastry do ręcznej oceny: `2`.

## Co zrobić po wyczerpaniu kolejki

Nie twórz tematów ręcznie i nie skanuj ponownie całej bazy. Otwórz `docs/LEGAL-CONTENT-SCALING-PLAN.md` i przejdź do etapu discovery. Plan określa progi, komendę docelową, format raportu oraz zasady ochrony opublikowanych i odrzuconych decyzji.

Główny workflow publikacji pozostaje w `docs/LEGAL-CONTENT-AUTHORING-WORKFLOW.md`.

## Następne tematy

| Temat | Pytania | Kategorie | Komenda dossier |
| --- | ---: | --- | --- |
| Śnieg, lód, przyczepność i poślizg | 20 | A, A1, A2, AM, B, B1, C, C1, D, D1, PT, T | `php artisan legal-content:prepare-agent-workspace snieg-lod-i-poslizg` |
| Alkohol i środki działające podobnie | 15 | A, A1, A2, AM, B, B1, C, C1, D, D1, PT, T | `php artisan legal-content:prepare-agent-workspace alkohol-i-srodki-dzialajace-podobnie` |
| Czas pracy, przerwy i odpoczynek kierowcy zawodowego | 15 | C, C1, D, D1 | `php artisan legal-content:prepare-agent-workspace czas-pracy-przerwy-i-odpoczynek-kierowcy` |
| Krwotok, rany i złamania: pierwsza pomoc | 14 | A, A1, A2, AM, B, B1, PT, T | `php artisan legal-content:prepare-agent-workspace krwotok-rany-i-zlamania` |
| Skrzyżowanie równorzędne i zasada prawej ręki | 13 | A, A1, A2, AM, B, B1, C, C1, D, D1, T | `php artisan legal-content:prepare-agent-workspace skrzyzowanie-rownorzedne-i-zasada-prawej-reki` |
| ABS, ESP, ASR i elektroniczne systemy bezpieczeństwa | 12 | B, B1, D, D1 | `php artisan legal-content:prepare-agent-workspace abs-esp-asr-i-systemy-bezpieczenstwa` |
| Resuscytacja, AED i brak oddechu | 10 | A, A1, A2, AM, B, B1, C, C1, D, D1, PT, T | `php artisan legal-content:prepare-agent-workspace resuscytacja-aed-i-brak-oddechu` |
| Ustawienie lusterek i martwe pole | 10 | B, B1, D, D1, T | `php artisan legal-content:prepare-agent-workspace lusterka-i-martwe-pole` |
| Jazda we mgle i przy ograniczonej widoczności | 9 | B, C, C1, D, D1, T | `php artisan legal-content:prepare-agent-workspace mgla-i-ograniczona-widocznosc` |
| Leki, zmęczenie i senność za kierownicą | 8 | A, A1, A2, AM, B, B1, C, C1, D, D1, PT, T | `php artisan legal-content:prepare-agent-workspace leki-zmeczenie-i-sennosc` |
| Hulajnoga elektryczna i urządzenia transportu osobistego | 7 | A, A1, A2, AM, B, B1, C, C1, D, D1, T | `php artisan legal-content:prepare-agent-workspace hulajnoga-elektryczna-i-uto` |
| Pojazd uprzywilejowany i tworzenie korytarza życia | 6 | A, A1, A2, AM, B, B1, C, C1, D, D1, PT, T | `php artisan legal-content:prepare-agent-workspace pojazd-uprzywilejowany` |
| Rondo i skrzyżowanie o ruchu okrężnym | 4 | A, A1, A2, AM, B, B1, C, C1, D, D1, T | `php artisan legal-content:prepare-agent-workspace rondo-i-ruch-okrezny` |
| Sygnał żółty i żółty migający | 4 | A, A1, A2, AM, B, B1, C, C1, D, D1, T | `php artisan legal-content:prepare-agent-workspace sygnal-zolty-i-zolty-migajacy` |
| Zielona strzałka warunkowa | 4 | A, A1, A2, AM, B, B1, C, C1, D, D1, T | `php artisan legal-content:prepare-agent-workspace zielona-strzalka-warunkowa` |

## Wszystkie tematy

| Status | Slug | Pytania | Zweryfikowane | Artykuł |
| --- | --- | ---: | ---: | --- |
| `ready` | `snieg-lod-i-poslizg` | 20 | 0 | - |
| `ready` | `alkohol-i-srodki-dzialajace-podobnie` | 15 | 1 | - |
| `ready` | `czas-pracy-przerwy-i-odpoczynek-kierowcy` | 15 | 0 | - |
| `ready` | `krwotok-rany-i-zlamania` | 14 | 0 | - |
| `ready` | `skrzyzowanie-rownorzedne-i-zasada-prawej-reki` | 13 | 0 | - |
| `ready` | `abs-esp-asr-i-systemy-bezpieczenstwa` | 12 | 0 | - |
| `ready` | `resuscytacja-aed-i-brak-oddechu` | 10 | 0 | - |
| `ready` | `lusterka-i-martwe-pole` | 10 | 0 | - |
| `ready` | `mgla-i-ograniczona-widocznosc` | 9 | 2 | - |
| `ready` | `leki-zmeczenie-i-sennosc` | 8 | 0 | - |
| `ready` | `hulajnoga-elektryczna-i-uto` | 7 | 1 | - |
| `ready` | `pojazd-uprzywilejowany` | 6 | 1 | - |
| `ready` | `rondo-i-ruch-okrezny` | 4 | 0 | - |
| `ready` | `sygnal-zolty-i-zolty-migajacy` | 4 | 0 | - |
| `ready` | `zielona-strzalka-warunkowa` | 4 | 0 | - |
| `manual_review` | `kask-i-odziez-ochronna` | 2 | 0 | - |
| `manual_review` | `zablokowane-skrzyzowanie` | 1 | 0 | - |
| `published` | `zawracanie` | 62 | 45 | `zawracanie` |
| `published` | `kierunkowskaz-przy-zmianie-pasa-i-skrecie` | 38 | 3 | `zmiana-kierunku-i-pasa-ruchu` |
| `published` | `sygnal-dzwiekowy` | 32 | 20 | `sygnal-dzwiekowy` |
| `published` | `skret-w-lewo-i-pojazd-z-naprzeciwka` | 32 | 0 | `pierwszenstwo-przejazdu` |
| `published` | `zabezpieczenie-ladunku-i-wymiary` | 28 | 23 | `zabezpieczenie-ladunku-i-wymiary` |
| `published` | `przyczepa-masa-wymiary-i-oswietlenie` | 27 | 19 | `przyczepa-masa-wymiary-i-oswietlenie` |
| `published` | `wjazd-na-przejazd-kolejowy` | 26 | 23 | `wjazd-na-przejazd-kolejowy` |
| `published` | `droga-hamowania-a-predkosc-i-nawierzchnia` | 25 | 0 | `predkosc-odstep-i-hamowanie` |
| `published` | `dokumenty-podczas-kontroli-drogowej` | 23 | 22 | `dokumenty-podczas-kontroli-drogowej` |
| `published` | `kategorie-prawa-jazdy-i-uprawnienia` | 23 | 23 | `kategorie-prawa-jazdy-i-uprawnienia` |
| `published` | `cofanie-i-zakazy-cofania` | 22 | 4 | `wymijanie-omijanie-cofanie` |
| `published` | `wyprzedzanie-rowerzysty-i-odstep-jeden-metr` | 22 | 1 | `wyprzedzanie` |
| `published` | `holowanie-pojazdu` | 20 | 14 | `holowanie-pojazdu` |
| `published` | `pieszy-na-przejsciu-i-wchodzacy-na-przejscie` | 20 | 0 | `piesi-i-przejscia` |
| `published` | `omijanie-zatrzymanego-pojazdu` | 19 | 3 | `wymijanie-omijanie-cofanie` |
| `published` | `pasy-bezpieczenstwa` | 19 | 10 | `pasy-bezpieczenstwa` |
| `published` | `swiatla-do-jazdy-dziennej-i-mijania` | 17 | 14 | `swiatla-do-jazdy-dziennej-i-mijania` |
| `published` | `rogatki-i-sygnaly-na-przejezdzie-kolejowym` | 16 | 13 | `rogatki-i-sygnaly-na-przejezdzie-kolejowym` |
| `published` | `foteliki-i-przewoz-dzieci` | 14 | 14 | `foteliki-i-przewoz-dzieci` |
| `published` | `polecenia-policjanta-i-kierujacego-ruchem` | 14 | 1 | `sygnalizacja-i-osoby-kierujace-ruchem` |
| `published` | `predkosc-w-obszarze-zabudowanym-i-strefach` | 12 | 3 | `predkosc-odstep-i-hamowanie` |
| `published` | `swiatla-przeciwmglowe` | 12 | 10 | `swiatla-przeciwmglowe` |
| `published` | `gasnica-trojkat-i-obowiazkowe-wyposazenie` | 11 | 6 | `gasnica-trojkat-i-obowiazkowe-wyposazenie` |
| `published` | `obowiazki-uczestnika-wypadku` | 11 | 11 | `obowiazki-uczestnika-wypadku` |
| `published` | `opony-bieznik-i-cisnienie` | 11 | 7 | `opony-bieznik-i-cisnienie` |
| `published` | `swiatla-drogowe-i-oslepianie` | 11 | 11 | `swiatla-drogowe-i-oslepianie` |
| `published` | `zakazy-zatrzymania-przy-przejsciu-skrzyzowaniu-i-przystanku` | 10 | 0 | `zatrzymanie-i-postoj` |
| `published` | `predkosc-na-autostradzie-i-drodze-ekspresowej` | 10 | 1 | `predkosc-odstep-i-hamowanie` |
| `published` | `autobus-wlaczajacy-sie-z-przystanku` | 9 | 9 | `autobus-wyjezdzajacy-z-przystanku` |
| `published` | `minimalny-odstep-w-tunelu` | 8 | 0 | `predkosc-odstep-i-hamowanie` |
| `published` | `zakazy-wyprzedzania` | 8 | 0 | `wyprzedzanie` |
| `published` | `wyprzedzanie-z-prawej-strony` | 7 | 0 | `wyprzedzanie` |
| `published` | `telefon-podczas-kierowania` | 6 | 6 | `telefon-podczas-kierowania` |
| `published` | `skret-a-pierwszenstwo-pieszego` | 5 | 1 | `piesi-i-przejscia` |
| `published` | `parkowanie-na-chodniku` | 5 | 0 | `zatrzymanie-i-postoj` |
| `published` | `pierwszenstwo-przy-zmianie-pasa` | 4 | 0 | `zmiana-kierunku-i-pasa-ruchu` |
| `published` | `tramwaj-na-przystanku-bez-wysepki` | 4 | 0 | `tramwaje-i-przystanki` |
| `published` | `wyjazd-z-posesji-parkingu-i-strefy` | 3 | 3 | `wlaczanie-sie-do-ruchu` |
| `published` | `przejazd-przez-chodnik-droge-dla-pieszych-i-brame` | 1 | 0 | `piesi-i-przejscia` |
