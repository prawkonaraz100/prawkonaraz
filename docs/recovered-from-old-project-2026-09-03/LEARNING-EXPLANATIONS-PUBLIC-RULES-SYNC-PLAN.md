# Synchronizacja krotkich zasad z wyjasnien publicznych do nauki

Status: wdrozone produkcyjnie 2026-08-13 - pelna synchronizacja zakonczona, rollback przygotowany
Branch: `codex/public-question-explanations-to-learning`
Ostatnia weryfikacja produkcji: 2026-08-13

## Cel

Po udzieleniu odpowiedzi w `/nauka` kursant ma zobaczyc krotka, konkretna
`Zasade do zapamietania` pochodzaca z opublikowanego wyjasnienia publicznego.

Zakres obejmuje tylko zwiezle reguly, na przyklad:

> **Zasada do zapamietania:** znak **B-20 nakazuje zatrzymanie**, a znak
> **P-12 pokazuje miejsce tego zatrzymania**. Pusta droga nie zwalnia z
> obowiazku zatrzymania.

Nie kopiujemy calych artykulow publicznych, pulapek egzaminacyjnych, sekcji
"Nie myl z", materialow powiazanych ani metadanych SEO.

## Co juz wiemy

### Miejsce docelowe

- Docelowym polem jest `questions.explanation`.
- Pole jest juz zwracane dopiero po odpowiedzi przez
  `StudySessionAnswerController` i `StudySessionApiPayloadBuilder`.
- Aktywny egzamin nie ujawnia wyjasnienia przed zakonczeniem odpowiedzi.
- Zmiana tekstu nie modyfikuje pytania, wariantow A/B/C, poprawnej odpowiedzi,
  punktow, sesji, postepu, mediow ani danych uzytkownika.

### Zrodlo

- Zrodlem jest wylacznie `QuestionPublicExplanation::published()`:
  `status = published` oraz `published_at IS NOT NULL`.
- Opublikowane wyjasnienia publiczne sa laczone z pytaniami po surowym,
  dokladnym `external_id`. Nie wolno laczyc po numerze widocznym w URL.
- Nie wszystkie rekordy `question_public_explanations` maja `question_id`;
  na produkcji nie wolno opierac migracji na tym kluczu.

### Wyniki audytu produkcji

| Kontrola | Wynik |
| --- | ---: |
| Opublikowane wyjasnienia publiczne | 2 492 |
| Wyjasnienia z rozpoznana zasada | 2 195 |
| Wyjasnienia bez takiej zasady | 278 |
| Niejednoznaczne wpisy z markerem | 19 |
| Wszystkie rekordy nauki dla 2 195 zasad | 14 189 |
| Grupy bez lokalnego odpowiednika | 0 |
| Rozbieznosci pytania, A/B/C, poprawnej odpowiedzi, typu, punktow lub zrodla | 0 |
| Nieaktywne rekordy w zasiegu | 0 |
| Grupy z roznymi obecnymi wyjasnieniami miedzy kategoriami | 4 |
| Grupy z reczna edycja administratora | 216 |
| Rekordy objete wylaczeniem recznym lub konfliktem | 2 162 |
| Maksymalny zakres automatycznej aktualizacji po wylaczeniach | 12 027 |

Przyklad kontrolny: publiczne pytanie `external_id=595` mapuje sie do 11
kategorii. Wszystkie 11 rekordow ma identyczne pytanie, warianty odpowiedzi,
poprawna odpowiedz i zrodlo.

### Finalny preview i zapis produkcyjny - 2026-08-13

| Kontrola | Wynik |
| --- | ---: |
| Opublikowane zrodla sprawdzone przez finalny parser | 2 492 |
| Bezpieczne grupy z rozpoznana regula | 2 155 |
| Grupy gotowe do zapisu | 1 938 |
| Rekordy pytan objete zapisem | 11 878 |
| Grupy pominiete przez preview | 554 |
| Pominiete przez reczna edycje | 215 |
| Pominiete przez brak bezpiecznej reguly | 337 |
| Pominiete przez konflikt obecnych wyjasnien | 2 |
| Grupy pominiete po ponownej kontroli podczas zapisu | 0 |

Finalny manifest: preview `63`, checksum
`da56a3b26e952e76855cd80a27479a4f1cebed6ec874f59fdc8ddb1488c3df3c`.
Zapis: run `64`, status `completed`, `11 878` snapshotow ze statusem `applied`.

## Bramy bezpieczenstwa

Kazda brama jest obowiazkowa. Niespelnienie dowolnej oznacza pominiecie calej
grupy `external_id`, nigdy zgadywanie lub czesciowa aktualizacje kategorii.

1. Dokladne dopasowanie surowego `external_id` z opublikowanego zrodla.
2. Grupa musi zawierac co najmniej jeden rekord nauki.
3. Wszystkie rekordy grupy musza miec identyczne: `prompt`, `option_a`,
   `option_b`, `option_c`, `correct_answer`, `question_type`, `points` i
   `source`.
4. Wszystkie rekordy grupy musza byc aktywne.
5. Grupa z wiecej niz jednym obecnym `questions.explanation` jest wykluczona.
6. Grupa, ktorej pytanie bylo recznie edytowane w panelu (`audit_logs`, akcja
   `admin.question.explanation_updated`), jest wykluczona.
7. Parser musi zwrocic jedna krotka zasade o dlugosci 20-600 znakow.
8. Zrodlo, lokalne pytanie i stare wyjasnienie musza miec ten sam hash jak w
   manifeście preview. Zmiana po preview oznacza pominiecie grupy.
9. Zapis obejmuje cala grupe kategorii albo zadnego jej rekordu.

## Parser zasad

Parser nie korzysta z AI, podobienstwa tresci ani luznego wyszukiwania.
Normalizuje konce linii i analizuje akapity Markdown.

Dozwolone sa tylko jawne formy redakcyjne:

- `**Zasada do zapamietania:** ...`
- `**Prosta zasada do zapamietania:** ...`
- `Najwazniejsza zasada do zapamietania: ...`
- `Najprostsza zasada do zapamietania: ...`
- `Prosta zasada do zapamietania brzmi: ...`

Wynik ma zawsze jeden format:

```text
**Zasada do zapamietania:** tresc krotkiej reguly
```

### Formatowanie Markdown

| Stan | Liczba | Zasada obslugi |
| --- | ---: | --- |
| Poprawny Markdown | 1 424 | import po zwyklej walidacji |
| Zbedne koncowe `**` | 726 | usunac tylko osierocony znacznik koncowy |
| Krotka regula zapisana w calosci pogrubieniem, a dalej omowienie | 45 | po jawnym naglowku pobrac pierwszy kompletny fragment pogrubiony; nie wolno wycinac przy dowolnym `**`, bo poprawne reguly zawieraja pogrubienia, np. `B-20` i `P-12` |

Jesli w ramach jednej zasady pozostanie niejednoznaczny Markdown, grupa trafia
do raportu i nie jest aktualizowana.

## Architektura implementacji

Nie wolno uruchamiac istniejacego `pj360:apply-explanation-drafts` z
`--overwrite-existing`. Komenda nie ma wystarczajacej ochrony przed zmiana
danych miedzy preview a zapisem ani trwalego rollbacku calej migracji.

Nalezy dodac oddzielny mechanizm:

1. Serwis parsera, np. `PublicExplanationMemoryRuleExtractor`.
2. Serwis planowania, np. `LearningExplanationRuleSyncPlanner`.
3. Komende preview, np. `questions:sync-public-memory-rules` bez `--write`.
4. Trwaly rejestr przebiegu w `content_import_runs`.
5. Tabele snapshotow, np. `question_explanation_sync_entries`, z polami:
   - `content_import_run_id`, `question_id`, `question_public_explanation_id`,
     `external_id`;
   - poprzedni i planowany tekst;
   - hash zrodla, hash starego i nowego wyjasnienia;
   - snapshot pytania i odpowiedzi;
   - status, powod pominiecia oraz znaczniki czasu.
6. Komende rollbacku przyjmujaca identyfikator runu.

`content_import_runs` jest tylko naglowkiem i raportem przebiegu. Nie moze byc
jedynym backupem, bo pelne stare teksty musza byc odtwarzalne per pytanie.

## Przebieg operacyjny

### Etap 1: implementacja i testy lokalne

- [x] Dodano parser `PublicExplanationMemoryRuleExtractor` z testami dozwolonych wariantow.
- [x] Dodano testy odrzucenia wielu markerow i niepoprawnego Markdown.
- [x] Dodano planner `LearningExplanationRuleSyncPlanner` ze wszystkimi bramami bezpieczenstwa.
- [x] Dodano migracje i snapshot per pytanie w `question_explanation_sync_entries`, powiazany z `content_import_runs`.
- [x] Dodano `questions:sync-public-memory-rules`; zapis wymaga `--write`, ID preview oraz checksumy manifestu.
- [x] Dodano `questions:rollback-public-memory-rules`; rollback sprawdza, czy tekst nie zostal zmieniony po imporcie.
- [x] Potwierdzono testami kontrakt nauki: brak wyjasnienia przed odpowiedzia i obecnosci po odpowiedzi w sesji klasycznej, powtorkach oraz API.
- [x] Potwierdzono testem, ze aktywny egzamin nie zwraca wyjasnienia pytania.

### Etap 2: preview produkcyjny

- [x] Wdrozyc sam kod, bez uruchamiania zapisu danych.
- [x] Uruchomic preview na produkcji: run `60`.
- [x] Zapisac manifest JSON i rejestr `content_import_runs`.
- [x] Porownac liczby z finalnym raportem w tym dokumencie.
- [x] Przejrzec powody wylaczen: reczna edycja, brak bezpiecznej reguly albo konflikt obecnego wyjasnienia.

### Etap 3: canary

- [x] Przygotowac manifest tylko dla `external_id=595`: preview `61`.
- [x] Wykonac zapis dla wszystkich 11 kategorii w jednej grupie: run `62`.
- [x] Zweryfikowac kontrakt odpowiedzi po blednej i poprawnej odpowiedzi testami sesji, API i egzaminu.
- [x] Sprawdzic publiczne zrodlo pytania: pelne wyjasnienie publiczne pozostalo bez zmian.
- [x] Wykonac preview rollbacku canary: 11 rekordow do odtworzenia, 0 konfliktow.

### Etap 4: pelny zapis

- [x] Wygenerowac swiezy preview bezposrednio przed zapisem: run `63`.
- [x] Zapisac snapshoty przed aktualizacja w runie `64`.
- [x] Aktualizowac grupy w osobnych transakcjach.
- [x] Przed zapisem kazdej grupy ponownie sprawdzic hashe i wszystkie bramy.
- [x] Zapisac status przebiegu i raport porealizacyjny.
- [x] Uruchomic produkcyjny health report, smoke test i preview rollbacku.

## Rollback

Rollback nie nadpisuje zmian wykonanych po imporcie. Dla kazdego wpisu:

1. Sprawdza, czy aktualne wyjasnienie ma hash planowanego importu.
2. Jesli tak, odtwarza dokladny poprzedni tekst ze snapshotu.
3. Jesli nie, oznacza rekord jako konflikt i go pomija.
4. Zapisuje raport w tym samym runie oraz w nowym wpisie rollbacku.

## Czego nie dotykamy

- `prompt`, `option_a`, `option_b`, `option_c`, `correct_answer`;
- mediow, adnotacji wizualnych, PJM i audio;
- wynikow, historii odpowiedzi, list blednych pytan i postepu;
- pelnego tekstu publicznych wyjasnien;
- grup wykluczonych przez reczna edycje, konflikt lub brak jednoznacznej reguly.

## Kryterium gotowosci do pelnego zapisu

Mozemy przejsc do Etapu 4 tylko, gdy:

- wszystkie testy przechodza;
- canary zostal poprawnie wykonany, a rollback preview potwierdza brak konfliktow;
- preview produkcyjny nie ma nowych konfliktow strukturalnych;
- raport wyjatkow zostal zatwierdzony;
- backup i rollback zostaly sprawdzone na prawdziwym snapshotcie;
- monitoring oraz smoke test po wdrozeniu aplikacji sa poprawne.

## Stan na teraz

- [x] Utworzono branch roboczy.
- [x] Zbadano zrodlo publiczne i miejsce docelowe w nauce.
- [x] Zweryfikowano zgodnosc pytan i odpowiedzi na produkcji.
- [x] Zidentyfikowano reczne edycje, konflikty i problemy Markdown.
- [x] Okreslono zasady canary, rollbacku i pelnego zapisu.
- [x] Zaimplementowano parser, planner, snapshoty, preview, zapis, rollback i raport JSON.
- [x] Zakonczono testy jednostkowe i funkcjonalne mechanizmu synchronizacji: 14 testow, 67 asercji.
- [x] Zakonczono testy kontraktu odpowiedzi sesji i egzaminu: 4 testy, 194 asercje.
- [x] Lokalny preview kontrolny `external_id=595` na aktualnym schemacie bazy.
- [x] Preview produkcyjny z nowym kodem.
- [x] Canary `external_id=595` dla 11 kategorii.
- [x] Pelna synchronizacja produkcyjna: run `64`, 11 878 rekordow.
- [x] Rozszerzono parser o samodzielny akapit `Prosta zasada: ...`. Audyt
  lokalnego raportu z 2026-08-13 wykazal 120 kandydatow; wszystkie maja
  widoczna tresc krotsza niz 300 znakow. Pozostale 217 rekordow nadal nie
  przechodzi przez automatyczny import.
- [x] Canary drugiej partii: `external_id=10041`, preview `65`, zapis `66`.
  Jedno pytanie kategorii B zaktualizowano z manifestu bez konfliktow;
  rollback preview dla runu `66` wskazuje 1 rekord do odtworzenia i 0 konfliktow.
- [x] Pelny preview drugiej partii: run `68`, `107` grup i `709` rekordow
  pytan. Dwanascie grup zostalo pominietych wylacznie przez reczna edycje
  administratora; nie bylo konfliktow struktury ani aktualnych wyjasnien.
- [x] Pelny zapis drugiej partii: run `69`, `107` grup i `709` rekordow.
  Wszystkie snapshoty maja status `applied`, a kontrola po zapisie potwierdzila
  zgodnosc aktualnego tekstu z manifestem dla wszystkich 709 pytan. Grupa
  `external_id=13560` ma jeden identyczny tekst we wszystkich 12 kategoriach.
  Rollback preview runu `69`: 709 rekordow do odtworzenia, 0 konfliktow.
- [x] Trzeci przeglad i import rozszerzonych formatow: parser wdrozono bez
  zmiany danych, a preview `70` rozpoznal 92 zrodla. Trzy grupy z reczna
  edycja (`6739`, `7133`, `13069`) zostaly automatycznie wykluczone.
- [x] Canary trzeciej partii: `external_id=13142`, preview `71`, zapis `72`.
  Wszystkie 11 kategorii otrzymalo jeden identyczny tekst; kontrola w
  rzeczywistym `/nauka` i rollback preview potwierdzily brak konfliktow.
- [x] Pelny zapis trzeciej partii: preview `73`, zapis `74`, 88 grup i 318
  rekordow. Wszystkie wpisy maja status `applied`, a aktualne tresci sa zgodne
  z hashami manifestu. Rollback preview runu `74`: 318 rekordow do
  odtworzenia, 0 konfliktow.
- [x] Zatwierdzone reguly redakcyjne dla szesciu zlozonych przypadkow dostaly
  osobny, zamkniety tryb synchronizacji. Canary `11018`: preview `76`, zapis
  `77`, cztery kategorie oraz kontrola w rzeczywistym `/nauka`.
- [x] Pelny zapis zasad redakcyjnych: preview `78`, zapis `79`, cztery grupy
  i 34 rekordy. Laczenie z canary daje 38 zaktualizowanych pytan w pieciu
  grupach; rollback preview obu runow nie wykazal konfliktow. `13575` zostalo
  pominiete, bo jego 11 kategorii ma wczesniejsza reczna edycje.

## Dziennik implementacji

### 2026-08-13 - start Etapu 1

- Rozpoczeto implementacje w branchu `codex/public-question-explanations-to-learning`.
- Zakres tej fazy: parser, planner, manifest preview, snapshoty, rollback i testy.
- Zakres tej fazy nie obejmuje uruchomienia `--write` na produkcji.
- Przyklad testowy `595` jest traktowany jako canary; jego wewnetrzne pogrubienia
  `B-20` i `P-12` musza zostac zachowane przez parser.

### 2026-08-13 - zakonczenie implementacji Etapu 1

- Dodano tabele `question_explanation_sync_entries` z pelnym snapshotem starego
  i planowanego tekstu, hashami zrodla oraz struktury pytania.
- Preview zapisuje tylko rejestr i snapshot. Nie aktualizuje
  `questions.explanation`.
- Zapis wymaga swiadomego wywolania z trzema elementami: `--write`, ID preview
  oraz dokladna checksumą manifestu. Zmiana zrodla albo pytania po preview
  pomija cala grupe kategorii.
- Kazda grupa `external_id` jest aktualizowana w jednej transakcji: wszystkie
  kategorie albo zadna.
- Rollback odtwarza jedynie tekst nadal zgodny z importem; pozniejsza reczna
  edycja jest oznaczana jako konflikt i pozostaje nienaruszona.
- Zweryfikowano formatowanie przez Laravel Pint oraz testy parsera i komend
  (14 testow, 67 asercji), a takze zachowanie wyjasnien w sesjach i egzaminie
  (4 testy, 194 asercje).

### Bezpieczne polecenia operacyjne

Lokalny lub produkcyjny preview (nie zmienia tresci pytan):

```powershell
php artisan questions:sync-public-memory-rules --external-id=595 --report=reports/memory-rule-595-preview.json
```

Zapis mozna wykonac dopiero po niezaleznej kontroli preview. Komenda wymaga
identyfikatora zwroconego przez preview i jego checksumy:

```powershell
php artisan questions:sync-public-memory-rules --write --run=PREVIEW_ID --confirm=CHECKSUM
```

Przed kazdym zapisem nalezy wygenerowac swiezy preview. Nie wolno wykonywac
pelnej synchronizacji ani rollbacku na podstawie starego manifestu.

### 2026-08-13 - lokalny preview kontrolny

- Uruchomiono migracje lokalnej bazy i preview dla `external_id=595`.
- Lokalna kopia ma obecnie tylko 16 rekordow `question_public_explanations` i
  nie zawiera publicznego zrodla `595`; preview utworzyl run `78` z zerowym
  zakresem. To potwierdza dzialanie trybu preview, ale nie zastepuje canary na
  danych produkcyjnych.
- Preview nie zmienil zadnego `questions.explanation`.

### 2026-08-13 - deploy i synchronizacja produkcyjna

- Wdrozono commit `89ddc732` na Mikrusie wraz z migracja
  `2026_08_13_120000_create_question_explanation_sync_entries_table`.
- Backup plikow aplikacji przed deployem:
  `/tmp/prawkonaraz-memory-rules-backup-20260813154800`.
- Zdrowie aplikacji przed i po deployu: HTTP `200`, PostgreSQL i Redis OK.
- Produkcyjny smoke test potwierdzil publiczne kategorie, sesje nauki,
  dashboard oraz dostepnosc mediow.
- Full preview `60` nie modyfikowal danych; canary `61`/`62` poprawnie
  zaktualizowal 11 kategorii pytania `595`.
- Swiezy full preview `63` zostal zastosowany jako run `64`: 1 938 grup,
  11 878 rekordow, bez pominiec po ponownej kontroli.
- Preview rollbacku runu `64` wskazuje 11 878 rekordow gotowych do odtworzenia
  i 0 konfliktow. Nie wykonano rollbacku.
