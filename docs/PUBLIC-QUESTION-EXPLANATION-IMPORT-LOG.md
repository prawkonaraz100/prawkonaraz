# Publiczne wyjasnienia pytan - log importow

## 2026-07-10

Zrodlo: `D:/pytania-main/pytania-main/pytania/`.

Finalny stamp payloadow: `20260710204914`.

Zakres:

- pliki markdown: `1447`,
- poprzedni udany import: `20260709230819` (`1259` pytan),
- nowe pytania wzgledem poprzedniego importu: `188`,
- pelne publiczne wyjasnienia: `1447` zapisane (`188` nowych, `1259` zaktualizowanych),
- `Nie pomyl z`: `1447` zapisane,
- `Powiazane pytania`: `1028` zestawow, `2759` relacji,
- puste powiazania w zrodle: `419`,
- podstawy prawne: `4429` unikalnych wpisow,
- relacje prawne utworzone: `493`,
- relacje prawne zaktualizowane bez podlaczonego artykulu: `33601`,
- relacje prawne pominiete, bo byly juz zlinkowane z artykulem: `1099`,
- pytania z kodami znakow w tresci: `756`,
- unikalne kody znakow w payloadzie: `182`.

Review promptow i parsera:

- zaakceptowane warianty z dotychczasowej allowlisty: `894`, `990`, `994`, `1107`, `1158`, `1430`, `1459`, `1480`, `1578`, `2305`, `2392`, `2402`, `2403`, `3535`,
- nowych prompt mismatch wymagajacych dopisania do allowlisty: `0`,
- generator rozbija teraz zlozone podstawy typu `§ 11 ust. 1 i ust. 2 pkt 8` oraz `§ 21 ust. 1-2 i ust. 4` na pojedyncze realne jednostki.

Przepisy dodane/uzupelnione przed importem:

- finalny preview podstaw prawnych wykazal `15` brakujacych wierszy jednostek, czyli `15` unikalnych docelowych etykiet,
- wygenerowano manifesty brakujacych jednostek skryptem `output/analysis/generate-missing-legal-unit-manifests.py`,
- zaktualizowano metadane aktu `wychowanie-w-trzezwosci` na podstawie ELI `https://eli.gov.pl/eli/DU/1982/230/ogl`,
- `prawo-o-ruchu-drogowym`: `3` jednostki w manifescie, `1` utworzona, `2` zaktualizowane,
- `ustawa-o-kierujacych-pojazdami`: `2` jednostki w manifescie, `2` utworzone,
- `wychowanie-w-trzezwosci`: `5` jednostek w manifescie, `5` utworzonych,
- `znaki-i-sygnaly-drogowe`: `20` jednostek w manifescie, `11` utworzonych, `9` zaktualizowanych,
- lacznie katalog przepisow: `19` jednostek utworzonych, `11` zaktualizowanych i `1` akt zaktualizowany,
- `official_excerpt` pozostawiony pusty/null dla nowych jednostek bez pobranego oficjalnego brzmienia.

Backup produkcyjny:

- `/tmp/legal-units-question-update-prewrite-backup-20260710204914.json`,
- `/tmp/question-update-prewrite-backup-20260710204914.json`,
- backup sekcji publicznych wyjasnien: `/tmp/question-public-explanations-pilot-backup-20260710205538.json`,
- backup `Nie pomyl z`: `/tmp/question-public-explanations-dont-confuse-backup-20260710205627.json`,
- backup `Powiazane pytania`: `/tmp/question-public-explanations-related-questions-backup-20260710205627.json`.

Weryfikacja:

- `public_expected`: `1447`,
- `public_found`: `1447`,
- `public_issues_count`: `0`,
- `dont_confuse_expected`: `1447`,
- `dont_confuse_missing_count`: `0`,
- `related_expected`: `1028`,
- `related_issues_count`: `0`,
- `legal_payload_unique`: `4429`,
- `legal_checked_question_rows`: `35193`,
- `legal_linked_article_rows`: `1099`,
- `legal_missing_count`: `0`,
- `legal_without_verifier_count`: `0`,
- weryfikator podstaw prawnych: `Jakub Wisniewski` (`jakub-wisniewski`),
- publiczne URL-e testowe `8251`, `8266`, `8416`, `9012` zwrocily HTTP `200` i zawieraly wyjasnienie, `Nie pomyl z`, blok prawny, podpis weryfikatora oraz sekcje powiazanych pytan.

## 2026-07-09 - partia 2

Zrodlo: `D:/pytania-main/pytania-main/pytania/`.

Finalny stamp payloadow: `20260709230819`.

Zakres:

- pliki markdown: `1259`,
- poprzedni udany import: `20260709183025` (`1159` pytan),
- nowe pytania wzgledem poprzedniego importu: `100`,
- pelne publiczne wyjasnienia: `1259` zapisane (`100` nowych, `1159` zaktualizowanych),
- `Nie pomyl z`: `1259` zapisane,
- `Powiazane pytania`: `892` zestawy, `2502` relacje,
- puste powiazania w zrodle: `367`,
- podstawy prawne: `3932` unikalne wpisy,
- relacje prawne utworzone: `1530`,
- relacje prawne zaktualizowane bez podlaczonego artykulu: `32084`,
- relacje prawne pominiete, bo byly juz zlinkowane z artykulem: `1082`,
- pytania z kodami znakow w tresci: `637`,
- unikalne kody znakow w payloadzie: `171`.

Review promptow i parsera:

- zaakceptowane warianty z dotychczasowej allowlisty: `894`, `990`, `994`, `1107`, `1158`, `1430`, `1459`, `1480`, `1578`, `2305`, `2392`, `2402`, `2403`, `3535`,
- nowych prompt mismatch wymagajacych dopisania do allowlisty: `0`,
- generator znormalizowal odpowiedz wielokrotnego wyboru z opisem przy `7455`: `A — 60 km/h` -> `A`, bo baza porownuje wariant odpowiedzi, a opis zostaje w tresci wyjasnienia.

Przepisy dodane/uzupelnione przed importem:

- finalny preview podstaw prawnych wykazal `7` brakujacych wierszy jednostek, czyli `6` unikalnych docelowych etykiet,
- wygenerowano manifesty brakujacych jednostek skryptem `output/analysis/generate-missing-legal-unit-manifests.py`,
- `prawo-o-ruchu-drogowym`: `2` jednostki w manifescie, `1` utworzona, `1` zaktualizowana,
- `system-powiadamiania-ratunkowego`: `8` jednostek w manifescie, `7` utworzonych, `1` zaktualizowana,
- `znaki-i-sygnaly-drogowe`: `5` jednostek w manifescie, `2` utworzone, `3` zaktualizowane,
- lacznie katalog przepisow: `10` jednostek utworzonych i `5` zaktualizowanych,
- `official_excerpt` pozostawiony pusty/null dla nowych jednostek bez pobranego oficjalnego brzmienia.

Backup produkcyjny:

- `/tmp/legal-units-question-update-prewrite-backup-20260709230819.json`,
- `/tmp/question-update-prewrite-backup-20260709230819.json`,
- backup sekcji publicznych wyjasnien: `/tmp/question-public-explanations-pilot-backup-20260709231342.json`,
- backup `Nie pomyl z`: `/tmp/question-public-explanations-dont-confuse-backup-20260709231413.json`,
- backup `Powiazane pytania`: `/tmp/question-public-explanations-related-questions-backup-20260709231413.json`.

Weryfikacja:

- `public_expected`: `1259`,
- `public_found`: `1259`,
- `public_issues_count`: `0`,
- `dont_confuse_expected`: `1259`,
- `dont_confuse_missing_count`: `0`,
- `related_expected`: `892`,
- `related_issues_count`: `0`,
- `legal_payload_unique`: `3932`,
- `legal_checked_question_rows`: `34696`,
- `legal_linked_article_rows`: `1082`,
- `legal_missing_count`: `0`,
- `legal_without_verifier_count`: `0`,
- weryfikator podstaw prawnych: `Jakub Wisniewski` (`jakub-wisniewski`),
- publiczne URL-e testowe `7455`, `7646`, `7780`, `8163` zwrocily HTTP `200` i zawieraly wyjasnienie, `Nie pomyl z`, blok prawny, podpis weryfikatora oraz sekcje powiazanych pytan.

## 2026-07-09

Zrodlo: `D:/pytania-main/pytania-main/pytania/`.

Finalny stamp payloadow: `20260709183025`.

Zakres:

- pliki markdown: `1159`,
- poprzedni udany import: `20260708213911` (`1052` pytania),
- nowe pytania wzgledem poprzedniego importu: `107`,
- pelne publiczne wyjasnienia: `1159` zapisane (`107` nowych, `1052` zaktualizowane),
- `Nie pomyl z`: `1159` zapisane,
- `Powiazane pytania`: `858` zestawow, `2457` relacji,
- puste powiazania w zrodle: `301`,
- podstawy prawne: `3619` unikalnych wpisow,
- relacje prawne utworzone: `3459`,
- relacje prawne zaktualizowane bez podlaczonego artykulu: `28625`,
- relacje prawne pominiete, bo byly juz zlinkowane z artykulem: `993`,
- pytania z kodami znakow w tresci: `595`,
- unikalne kody znakow w payloadzie: `167`.

Review promptow i parsera:

- zaakceptowane warianty z dotychczasowej allowlisty: `894`, `990`, `994`, `1107`, `1158`, `1430`, `1459`, `1480`, `1578`, `2305`, `2392`, `2402`, `2403`, `3535`,
- nowych prompt mismatch wymagajacych dopisania do allowlisty: `0`,
- generator dostal fallback parsera front matter na wypadek braku lokalnego `PyYAML`; fallback obsluguje uzywany w plikach podzbior YAML,
- generator rozbija podstawy prawne typu `w zwiazku z`, np. `§ 97 ust. 2 w zwiazku z § 95 ust. 1 pkt 3`, na osobne realne jednostki zamiast tworzyc sztuczny przepis laczony.

Przepisy dodane/uzupelnione przed importem:

- finalny preview podstaw prawnych wykazal `22` brakujace wiersze jednostek, czyli `18` unikalnych docelowych etykiet,
- wygenerowano manifesty brakujacych jednostek skryptem `output/analysis/generate-missing-legal-unit-manifests.py`,
- `prawo-o-ruchu-drogowym`: `17` jednostek w manifescie, `9` utworzonych, `8` zaktualizowanych,
- `ustawa-o-kierujacych-pojazdami`: `8` jednostek w manifescie, `6` utworzonych, `2` zaktualizowane,
- `znaki-i-sygnaly-drogowe`: `16` jednostek w manifescie, `7` utworzonych, `9` zaktualizowanych,
- lacznie katalog przepisow: `22` jednostki utworzone i `19` zaktualizowanych,
- `official_excerpt` pozostawiony pusty/null dla nowych jednostek bez pobranego oficjalnego brzmienia.

Backup produkcyjny:

- `/tmp/legal-units-question-update-prewrite-backup-20260709183025.json`,
- `/tmp/question-update-prewrite-backup-20260709183025.json`,
- backup sekcji publicznych wyjasnien: `/tmp/question-public-explanations-pilot-backup-20260709183611.json`,
- backup `Nie pomyl z`: `/tmp/question-public-explanations-dont-confuse-backup-20260709183657.json`,
- backup `Powiazane pytania`: `/tmp/question-public-explanations-related-questions-backup-20260709183657.json`.

Weryfikacja:

- `public_expected`: `1159`,
- `public_found`: `1159`,
- `public_issues_count`: `0`,
- `dont_confuse_expected`: `1159`,
- `dont_confuse_missing_count`: `0`,
- `related_expected`: `858`,
- `related_issues_count`: `0`,
- `legal_payload_unique`: `3619`,
- `legal_checked_question_rows`: `33077`,
- `legal_linked_article_rows`: `993`,
- `legal_missing_count`: `0`,
- `legal_without_verifier_count`: `0`,
- weryfikator podstaw prawnych: `Jakub Wisniewski` (`jakub-wisniewski`),
- publiczne URL-e testowe `7292`, `7388`, `10960`, `14062` zwrocily HTTP `200` i zawieraly wyjasnienie, `Nie pomyl z`, blok prawny, podpis weryfikatora oraz sekcje powiazanych pytan.

## 2026-07-08 - partia 2

Zrodlo: `D:/pytania-main/pytania-main/pytania/`.

Finalny stamp payloadow: `20260708213911`.

Zakres:

- pliki markdown: `1052`,
- poprzedni udany import: `20260708002516` (`901` pytan),
- nowe pytania wzgledem poprzedniego importu: `151`,
- pelne publiczne wyjasnienia: `1052` zapisane (`151` nowych, `901` zaktualizowanych),
- `Nie pomyl z`: `1052` zapisane,
- `Powiazane pytania`: `786` zestawow,
- puste powiazania w zrodle: `266`,
- podstawy prawne: `3277` unikalnych wpisow,
- relacje prawne utworzone: `3115`,
- relacje prawne zaktualizowane bez podlaczonego artykulu: `25510`,
- relacje prawne pominiete, bo byly juz zlinkowane z artykulem: `921`,
- pytania z kodami znakow w tresci: `520`,
- unikalne kody znakow w payloadzie: `162`.

Review promptow i parsera:

- zaakceptowane warianty z dotychczasowej allowlisty: `994`, `1107`, `1459`, `2305`, `2392`, `2402`, `2403`,
- nowych prompt mismatch wymagajacych dopisania do allowlisty: `0`,
- generator dopasowano do naglowkow `## Wyjasnienie`, `## Haczyk egzaminacyjny` i `## Trzy najczestsze bledy`, bo czesc nowych plikow uzywala tych naglowkow zamiast wariantu pogrubionego,
- `art. 162 § 1 i 2` rozbijany jest teraz na `art. 162 § 1` i `art. 162 § 2`,
- aliasy URL dodane w generatorze: `DU/2025/1226` -> `DU/2011/151` dla ustawy o kierujacych pojazdami, `DU/2026/141` -> `DU/2006/1410` dla ustawy o Panstwowym Ratownictwie Medycznym.

Przepisy dodane/uzupelnione przed importem:

- finalny preview podstaw prawnych wykazal `73` brakujace wiersze jednostek, czyli `58` unikalnych docelowych etykiet,
- wygenerowano manifesty brakujacych jednostek skryptem `output/analysis/generate-missing-legal-unit-manifests.py`,
- utworzono nowy akt: `rejestracja-i-oznaczanie-pojazdow`,
- `panstwowe-ratownictwo-medyczne`: `5` jednostek w manifescie, `5` utworzonych,
- `prawo-o-ruchu-drogowym`: `12` jednostek w manifescie, `9` utworzonych, `3` zaktualizowane,
- `rejestracja-i-oznaczanie-pojazdow`: `1` jednostka w manifescie, `1` utworzona,
- `ustawa-o-kierujacych-pojazdami`: `16` jednostek w manifescie, `13` utworzonych, `3` zaktualizowane,
- `warunki-techniczne-pojazdow`: `45` jednostek w manifescie, `39` utworzonych, `6` zaktualizowanych,
- `znaki-i-sygnaly-drogowe`: `39` jednostek w manifescie, `22` utworzone, `17` zaktualizowanych,
- lacznie katalog przepisow: `89` jednostek utworzonych, `29` zaktualizowanych i `1` nowy akt,
- `official_excerpt` pozostawiony pusty/null dla nowych jednostek bez pobranego oficjalnego brzmienia.

Backup produkcyjny:

- `/tmp/legal-units-question-update-prewrite-backup-20260708213911.json`,
- `/tmp/question-update-prewrite-backup-20260708213911.json`,
- backup sekcji publicznych wyjasnien: `/tmp/question-public-explanations-pilot-backup-20260708215648.json`,
- backup `Nie pomyl z`: `/tmp/question-public-explanations-dont-confuse-backup-20260708215725.json`,
- backup `Powiazane pytania`: `/tmp/question-public-explanations-related-questions-backup-20260708215724.json`.

Weryfikacja:

- `public_expected`: `1052`,
- `public_found`: `1052`,
- `public_issues_count`: `0`,
- `dont_confuse_expected`: `1052`,
- `dont_confuse_missing_count`: `0`,
- `related_expected`: `786`,
- `related_issues_count`: `0`,
- `legal_payload_unique`: `3277`,
- `legal_checked_question_rows`: `29546`,
- `legal_linked_article_rows`: `921`,
- `legal_missing_count`: `0`,
- `legal_without_verifier_count`: `0`,
- weryfikator podstaw prawnych: `Jakub Wisniewski` (`jakub-wisniewski`),
- publiczne URL-e testowe `6434`, `7132`, `7291` zwrocily HTTP `200` i zawieraly wyjasnienie, `Nie pomyl z`, blok prawny, podpis weryfikatora oraz sekcje powiazanych pytan.

## 2026-07-08

Zrodlo: `D:/pytania-main/pytania-main/pytania/`.

Finalny stamp payloadow: `20260708002516`.

Zakres:

- pliki markdown: `901`,
- poprzedni udany import: `20260704174332` (`652` pytania),
- nowe pytania wzgledem poprzedniego importu: `249`,
- pelne publiczne wyjasnienia: `901` zapisanych (`249` nowych, `652` zaktualizowane),
- `Nie pomyl z`: `901` zapisanych,
- `Powiazane pytania`: `715` zestawow,
- puste powiazania w zrodle: `186`,
- podstawy prawne: `2781` unikalnych wpisow,
- relacje prawne utworzone: `7269`,
- relacje prawne zaktualizowane bez podlaczonego artykulu: `18241`,
- relacje prawne pominiete, bo byly juz zlinkowane z artykulem: `840`,
- pytania z kodami znakow w tresci: `457`,
- unikalne kody znakow w payloadzie: `151`.

Review promptow:

- zaakceptowane warianty z dotychczasowej allowlisty: `994`, `1107`, `1459`, `2305`, `2392`, `2402`, `2403`,
- nowych prompt mismatch wymagajacych dopisania do allowlisty: `0`,
- `6304` mial w bazie spacje przed znakiem zapytania (`nim ?`), a plik `nim?`; potraktowano to jako wariant interpunkcyjny przez normalizacje spacji przed `?`, bez dopisywania do allowlisty.

Przepisy dodane/uzupelnione przed importem:

- pierwszy preview podstaw prawnych wykazal `46` brakujacych wierszy jednostek, czyli `35` unikalnych docelowych etykiet,
- wygenerowano manifesty brakujacych jednostek skryptem `output/analysis/generate-missing-legal-unit-manifests.py`,
- `prawo-o-ruchu-drogowym`: `26` jednostek w manifescie, `10` utworzonych, `16` zaktualizowanych,
- `warunki-techniczne-pojazdow`: `1` jednostka w manifescie, `1` utworzona,
- `znaki-i-sygnaly-drogowe`: `42` jednostki w manifescie, `32` utworzone, `10` zaktualizowanych,
- lacznie katalog przepisow: `43` jednostki utworzone i `26` zaktualizowanych,
- `official_excerpt` pozostawiony pusty/null dla nowych jednostek bez pobranego oficjalnego brzmienia.

Backup produkcyjny:

- `/tmp/legal-units-question-update-prewrite-backup-20260708002516.json`,
- `/tmp/question-update-prewrite-backup-20260708002516.json`,
- backup sekcji publicznych wyjasnien: `/tmp/question-public-explanations-pilot-backup-20260708003619.json`,
- backup `Nie pomyl z`: `/tmp/question-public-explanations-dont-confuse-backup-20260708004224.json`,
- backup `Powiazane pytania`: `/tmp/question-public-explanations-related-questions-backup-20260708004223.json`.

Weryfikacja:

- `public_expected`: `901`,
- `public_found`: `901`,
- `public_issues_count`: `0`,
- `dont_confuse_expected`: `901`,
- `dont_confuse_missing_count`: `0`,
- `related_expected`: `715`,
- `related_issues_count`: `0`,
- `legal_payload_unique`: `2781`,
- `legal_checked_question_rows`: `26350`,
- `legal_linked_article_rows`: `840`,
- `legal_missing_count`: `0`,
- `legal_without_verifier_count`: `0`,
- weryfikator podstaw prawnych: `Jakub Wisniewski` (`jakub-wisniewski`),
- publiczne URL-e testowe `6014`, `6304`, `6366` zwrocily HTTP `200` i zawieraly wyjasnienie, `Nie pomyl z`, blok prawny, podpis weryfikatora oraz sekcje powiazanych pytan.

## 2026-07-04

Zrodlo: `D:/pytania-main/pytania-main/pytania/`.

Finalny stamp payloadow: `20260704174332`.

Zakres:

- pliki markdown: `652`,
- nowe pytania wzgledem importu `20260702032902`: `198`,
- pelne publiczne wyjasnienia: `652` zapisane,
- `Nie pomyl z`: `652` zapisane,
- `Powiazane pytania`: `549` zestawow, `1936` relacji,
- puste powiazania w zrodle: `103`,
- podstawy prawne: `2008` unikalnych wpisow,
- relacje prawne utworzone: `4807`,
- relacje prawne zaktualizowane bez podlaczonego artykulu: `13434`,
- relacje prawne pominiete, bo byly juz zlinkowane z artykulem: `588`.

Review promptow:

- zaakceptowane recznie warianty z dotychczasowej allowlisty: `14`,
- nowych prompt mismatch wymagajacych dopisania do allowlisty: `0`.

Przepisy dodane/uzupelnione przed importem:

- dodano/brakujace jednostki z manifestow dla: `znaki-i-sygnaly-drogowe`, `prawo-o-ruchu-drogowym`, `kodeks-karny`, `warunki-techniczne-pojazdow`,
- dodano brakujace akty: `panstwowe-ratownictwo-medyczne`, `system-powiadamiania-ratunkowego`, `egzaminowanie-kierowcow`,
- aliasy URL w payloadzie: `DU/2024/1251` -> `DU/1997/602` dla Prawa o ruchu drogowym, `DU/2003/262` -> `DU/2024/502` dla warunkow technicznych pojazdow,
- zakresy typu `pkt 1-2`, `ust. 1-3`, `art. 162 § 1-2` rozbite na pojedyncze jednostki,
- `official_excerpt` pozostawiony pusty/null dla nowych jednostek bez pobranego oficjalnego brzmienia.

Backup produkcyjny:

- `/tmp/legal-units-question-update-prewrite-backup-20260704174332.json`,
- `/tmp/question-update-prewrite-backup-20260704174332.json`,
- backup sekcji publicznych wyjasnien: raport importera `write-public-explanations-20260704174332.json`,
- backup `Nie pomyl z`: raport importera `write-dont-confuse-20260704174332.json`,
- backup `Powiazane pytania`: raport importera `write-related-questions-20260704174332.json`.

Weryfikacja:

- `public_expected`: `652`,
- `public_found`: `652`,
- `public_issues_count`: `0`,
- `dont_confuse_missing_count`: `0`,
- `related_issues_count`: `0`,
- `legal_payload_unique`: `2008`,
- `legal_checked_question_rows`: `18829`,
- `legal_missing_count`: `0`,
- `legal_without_verifier_count`: `0`,
- weryfikator podstaw prawnych: `Jakub Wisniewski` (`jakub-wisniewski`),
- publiczne URL-e testowe `3541`, `3658`, `4374`, `4600`, `6012` zwrocily HTTP `200` i zawieraly wyjasnienie, `Nie pomyl z`, blok prawny oraz podpis weryfikatora.

## 2026-07-01

Zrodlo: `D:/pytania-main/pytania-main/pytania/`.

Zakres:

- pliki markdown: `255`,
- `index.jsonl`: `88` rekordow, pominiety jako niekompletny,
- nowe pytania wzgledem importu `2026-06-30`: `48`,
- pelne publiczne wyjasnienia: `255` zapisanych (`48` nowych, `207` zaktualizowanych),
- `Nie pomyl z`: `255` zapisanych,
- `Powiazane pytania`: `251` zestawow, `957` relacji,
- puste powiazania w zrodle: `1864`, `1866`, `2392`, `2402`,
- podstawy prawne: `795` unikalnych wpisow.

Review promptow:

- zaakceptowane recznie warianty: `2305`, `2392`, `2402`, `2403`,
- `2305`: roznica spacji/literowka `wtej` vs `w tej`, ta sama odpowiedz i sens pytania,
- `2392`: baza ma pelniejsze brzmienie z osoba niepelnosprawna, plik ma krotszy prompt; odpowiedz, external id i podstawa prawna sa zgodne,
- `2402`: `wysiasc z pojazdu bez upewnienia sie` vs `opuscic pojazd bez sprawdzenia`, ta sama odpowiedz i sens,
- `2403`: `otworzyc drzwi bez upewnienia sie` vs `otworzyc drzwi bez sprawdzenia`, ta sama odpowiedz i sens.

Przepisy dodane/uzupelnione przed importem:

- `znaki-i-sygnaly-drogowe`: `§ 108`, `§ 108 ust. 1`, `§ 108 ust. 3`,
- `prawo-o-ruchu-drogowym`: `art. 57a`, `art. 57a ust. 2`,
- `kodeks-karny`: `art. 162`, `art. 162 § 1`,
- `official_excerpt` pozostawiony pusty/null; notatki redakcyjne zostaly zapisane przy relacjach pytan.

Wynik podstaw prawnych:

- utworzone relacje: `1221`,
- zaktualizowane niezlinkowane relacje: `6468`,
- pominiete relacje zlinkowane z artykulami: `193`,
- brakujace akty/jednostki po drugim preview: `0`.

Backup produkcyjny:

- `/tmp/question-update-prewrite-backup-20260701140611.json`,
- backup sekcji publicznych wyjasnien: `/tmp/question-public-explanations-pilot-backup-20260701161251.json`,
- backup `Nie pomyl z`: `/tmp/question-public-explanations-dont-confuse-backup-20260701161331.json`,
- backup `Powiazane pytania`: `/tmp/question-public-explanations-related-questions-backup-20260701161331.json`.

Weryfikacja:

- `public_rows_count`: `255`,
- `related_expected_count`: `251`,
- `legal_payload_unique_rows`: `795`,
- `public_issues_count`: `0`,
- `legal_issues_count`: `0`,
- `last_reviewed_at`: `2026-07-01`,
- publiczne URL-e testowe `2239`, `2305`, `2392`, `2402`, `2420`, `2430` zwrocily HTTP `200`.

Audit znakow:

- surowe referencje znakow: `112`,
- znormalizowane pojedyncze kody: `100`,
- kody w produkcyjnej bazie znakow: `313`,
- brakujace kody znakow: `20`,
- brakujace: `A-12`, `A-6`, `B-32f`, `D-16`, `P-22`, `P-8`, `S-3`, `T-19`, `T-2`, `T-20`, `T-21`, `T-25a`, `T-25b`, `T-25c`, `T-4`, `T-5`, `T-6`, `T-6c`, `T-6d`, `T-9`.
