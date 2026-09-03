# Plan skalowania tresci prawnych

Status: zaakceptowany kierunek architektoniczny, implementacja etapowa
Utworzono: 2026-06-19
Zakres: odkrywanie nowych tematow z pytan, produkcja wielu artykulow, nadzor jakosci i aktualnosci prawa

## Dokumenty powiazane

Czytaj w tej kolejnosci:

1. `resources/legal-content/generated/agent-workspace/QUEUE.md` - aktualna kolejka i pierwszy punkt wejscia.
2. `docs/LEGAL-CONTENT-AUTHORING-WORKFLOW.md` - tworzenie i publikacja jednego artykulu.
3. `docs/LEGAL-CONTENT-SCALING-PLAN.md` - ten dokument; uruchamiany po spadku kolejki albo wzroscie portfela.
4. `docs/LEGAL-CONTENT-QUESTION-FIRST-AUDIT.md` - naprawa i doprecyzowanie juz opublikowanych relacji.
5. `docs/LEGAL-TRUST-LAYER-PLAN.md` - nadrzedny kontekst architektury warstwy prawnej.

## Cel

Warstwa `/przepisy` ma rosnac bez ponownego skanowania calej bazy przez kazdego agenta i bez utraty kontroli nad tym:

- ktore pytania zostaly juz wykorzystane,
- ktore klastry doprowadzily do artykulu,
- ktore pytania maja zweryfikowana podstawe prawna,
- ktore tematy sa duplikatami albo powinny zostac polaczone,
- ktore artykuly wymagaja ponownej weryfikacji po zmianie prawa,
- gdzie szukac kolejnej generacji tematow po wyczerpaniu aktualnej kolejki.

Najwazniejsza zasada pozostaje bez zmian: automatyczne grupowanie tworzy kandydatow redakcyjnych, nigdy automatycznie nie publikuje podstaw prawnych.

## Aktualny punkt startowy

Na 19.06.2026:

- baza zawiera 17 044 aktywne rekordy pytan,
- rekordy skladaja sie na 3 570 pytan kanonicznych,
- wszystkie pytania maja jeden z 31 tematow filarowych,
- 767 pytan kanonicznych ma co najmniej jeden z 56 tematow `focused`,
- 33 klastry sa gotowe do opracowania,
- 21 klastrow wskazuje istniejace artykuly,
- klastry wskazuja 11 unikalnych artykulow,
- dwa male klastry wymagaja recznej decyzji.

Operacyjny punkt wejscia:

```text
resources/legal-content/generated/agent-workspace/QUEUE.md
```

## Trzy oddzielne procesy

Skalowanie dzielimy na trzy kolejki. Nie wolno mieszac ich w jeden status.

### 1. Discovery

Odkrywa nowe klastry z pytan, ktore nie maja jeszcze dobrego tematu `focused`.

Wynik:

- propozycja nazwy i sluga,
- pytania kanoniczne,
- dowody tekstowe grupowania,
- mozliwe podobienstwo do istniejacych tematow,
- rekomendacja: nowy artykul, rozbudowa artykulu, podzial albo odrzucenie.

### 2. Production

Prowadzi zaakceptowany temat od dossier do publikacji.

Wynik:

- oficjalnie zweryfikowane `LegalUnit`,
- `LegalContentPage`,
- unikalne `QuestionLegalReference.public_note`,
- testy i aktualizacja rejestru.

### 3. Maintenance

Pilnuje juz opublikowanych artykulow.

Wynik:

- lista tresci po terminie review,
- lista stron dotknietych zmiana jednostki prawnej,
- lista pytan z szeroka albo nieaktualna podstawa,
- decyzja: pozostawic, poprawic, polaczyc, podzielic, wycofac.

## Cykl zycia kandydata

Docelowe statusy `LegalArticleTopicCandidate`:

| Status | Znaczenie |
| --- | --- |
| `discovered` | Klaster utworzony automatycznie, bez recznej oceny. |
| `triage` | Agent lub redaktor ocenia nazwe, zakres i jakosc pytan. |
| `ready` | Temat zaakceptowany do researchu i napisania. |
| `claimed` | Agent przejal temat; drugi agent nie powinien rozpoczynac tej samej pracy. |
| `legal_review` | Trwa albo oczekuje weryfikacja podstaw prawnych. |
| `content_review` | Tresc i powiazania sa gotowe do kontroli redakcyjnej. |
| `published` | Klaster zostal wykorzystany w opublikowanym artykule. |
| `merged` | Klaster zostal wlaczony do innego kandydata lub artykulu. |
| `split` | Klaster podzielono na wezsze tematy. |
| `rejected` | Klaster nie nadaje sie na samodzielny temat. |
| `needs_review` | Zmiana pytan, prawa albo regul wymaga ponownej oceny. |

Status `published` nie oznacza, ze wszystkie pytania z klastra automatycznie maja `QuestionLegalReference`. Licza sie tylko recznie zweryfikowane relacje.

## Relacja klaster - artykul

Pole tekstowe `existing_article_slug` wystarcza dla MVP, ale przy wiekszej liczbie wpisow trzeba zastapic je jawna relacja.

Docelowo:

- jeden artykul moze wykorzystywac wiele klastrow,
- jeden klaster ma jeden glowny artykul albo decyzje `split`,
- artykul moze miec klaster glowny i klastry pomocnicze,
- historia polaczen i podzialow nie jest usuwana.

Proponowana tabela:

```text
legal_article_topic_candidate_pages
- legal_article_topic_candidate_id
- legal_content_page_id
- relation_type: primary, supporting, merged_into
- reviewed_by
- reviewed_at
- notes
```

Zapobiega to sytuacji, w ktorej 20 wykorzystanych klastrow jest mylone z 20 unikalnymi artykulami.

## Odkrywanie kolejnych generacji tematow

### Kiedy uruchamiac discovery

Nowa generacje uruchamiamy, gdy wystapi co najmniej jeden warunek:

- liczba tematow `ready` spadnie ponizej 10,
- od ostatniego discovery dodano co najmniej 100 pytan kanonicznych,
- wiecej niz 500 pytan nie ma zadnego tematu `focused`,
- od ostatniego discovery minelo 30 dni,
- redaktor wskaze filar o slabym pokryciu.

### Dane wejsciowe

Discovery analizuje przede wszystkim pytania:

- aktywne i gotowe do publikacji,
- bez tematu `focused` albo tylko z bardzo szerokim dopasowaniem,
- pogrupowane po kanonicznym `external_id`,
- z uwzglednieniem filaru `QuestionTopic`.

Pierwszy przebieg wykorzystuje prompty. Odpowiedzi i wyjasnienia sluza do kontroli spojnosci klastra, nie do automatycznego publikowania wnioskow prawnych.

### Generacja 1: deterministyczna

Najpierw rozwijamy obecny mechanizm bez modelu semantycznego:

1. normalizacja tekstu,
2. usuwanie pustych formul typu `Czy w tej sytuacji`,
3. ekstrakcja powtarzajacych sie fraz i n-gramow,
4. grupowanie tylko wewnatrz zgodnych filarow,
5. minimalny rozmiar klastra: 3 pytania kanoniczne,
6. porownanie z istniejacymi slugami i tytulami,
7. raport propozycji bez zapisu albo zapis ze statusem `discovered`.

Planowana komenda:

```powershell
php artisan legal-content:discover-topic-candidates
```

Opcje:

```text
--write
--pillar=
--min-size=3
--max-size=80
--generation=
--report=
```

Artefakty:

```text
resources/legal-content/generated/discovery/RUN-ID.md
resources/legal-content/generated/discovery/RUN-ID.json
```

### Generacja 2: podobienstwo semantyczne

Uruchamiamy dopiero, gdy deterministyczne grupowanie przestanie dawac sensowne wyniki.

Zasady:

- embedding lub model musi miec zapisana nazwe i wersje,
- wynik musi byc odtwarzalny,
- podobienstwo jest tylko sygnalem kandydackim,
- agent widzi reprezentatywne pytania i przypadki odstajace,
- klaster nie moze zostac zapisany jako `ready` bez triage,
- surowe dane pytan nie sa wysylane do niezatwierdzonego zewnetrznego dostawcy.

### Jak oceniac propozycje

Kazdy nowy klaster dostaje:

- `cohesion_score` - spojność promptow,
- `distinctiveness_score` - odroznienie od istniejacych tematow,
- `coverage_gain` - liczba nowych pytan bez poprzedniego tematu,
- `overlap_percent` - nakladanie z istniejacymi klastrami,
- `outlier_count` - pytania slabo pasujace,
- `suggested_action`.

Do automatycznej kolejki `triage` trafia tylko klaster:

- z co najmniej 3 pytaniami kanonicznymi,
- bez sprzecznych poprawnych odpowiedzi dla jednego ID,
- bez ponad 60% pokrycia z istniejacym klastrem,
- z jednym dominujacym filarem albo jasno opisanym powodem laczenia filarow.

## Wersjonowanie discovery

Potrzebujemy historii przebiegow, aby wiedziec, skad pojawil sie temat.

Proponowane tabele:

```text
legal_content_discovery_runs
- id
- generation
- algorithm_version
- input_question_count
- uncovered_question_count
- parameters
- started_at
- completed_at
- status

legal_content_discovery_suggestions
- discovery_run_id
- candidate_id
- suggested_title
- suggested_slug
- evidence
- scores
- decision
- decided_by
- decided_at
- notes
```

Ponowne discovery nie usuwa decyzji redakcyjnych. Tematy `published`, `merged`, `split` i `rejected` sa chronione przed automatycznym nadpisaniem.

## Skalowanie przechowywania artykulow

Jeden duzy `LegalTrustLayerMvpSeeder` nie powinien byc docelowym magazynem setek tresci.

### Do 25 artykulow

- obecny seeder pozostaje dopuszczalny,
- kazdy wpis ma test publiczny,
- agent workspace jest obowiazkowym punktem startowym.

### 25-75 artykulow

Przenosimy definicje do wersjonowanych paczek:

```text
resources/legal-content/articles/{slug}.php
```

Paczka zawiera:

- metadane strony,
- `LegalUnit`,
- pytania i `public_note`,
- daty publikacji i review,
- wersje formatu.

Idempotentny importer zastapi reczne rozbudowywanie jednej metody seedera.

Planowana komenda:

```powershell
php artisan legal-content:import-articles --path=resources/legal-content/articles --write
```

### Powyzej 75 artykulow

- baza danych i panel administracyjny staja sie glownym miejscem pracy redakcyjnej,
- paczki pozostaja formatem eksportu, backupu i code review,
- kazda zmiana tresci tworzy rewizje,
- seeder sluzy tylko do bootstrapu albo importu paczek.

Proponowana tabela rewizji:

```text
legal_content_page_revisions
- legal_content_page_id
- revision
- payload
- change_summary
- created_by
- reviewed_by
- created_at
- published_at
```

## Panel operacyjny

Przy kilkudziesieciu artykulach potrzebny jest widok admina z czterema zakladkami:

### Kolejka

- tematy `ready`,
- mozliwosc przejecia tematu,
- liczba pytan i kategorii,
- podobne istniejace artykuly,
- przycisk generowania dossier.

### W produkcji

- wlasciciel zadania,
- etap `legal_review` / `content_review`,
- braki: zrodlo, jednostka, reviewer, pytania, test.

### Opublikowane

- liczba klastrow i pytan,
- pokrycie zweryfikowanymi relacjami,
- data ostatniego review,
- jednostki prawne,
- alerty duplikacji lub thin content.

### Wymaga review

- zmieniona jednostka prawna,
- zrodlo niedostepne,
- przeterminowany review,
- zmienione pytanie lub poprawna odpowiedz,
- artykul bez pytan albo z pytaniami usunietymi z bazy.

## Utrzymanie aktualnosci prawa

Kazda strona dziedziczy stan review z powiazanych `LegalUnit`.

Artykul trafia do `needs_review`, gdy:

- zmieni sie tekst albo identyfikator jednostki prawnej,
- oficjalne zrodlo przestanie byc dostepne,
- `LegalUnit.last_checked_at` jest starsze niz przyjety termin,
- pytanie zmieni prompt, poprawna odpowiedz albo zostanie dezaktywowane,
- minie termin okresowego review artykulu.

Rekomendowana czestotliwosc:

- co 30 dni: automatyczny audyt linkow i zmian danych pytan,
- co 90 dni: przeglad artykulow wysokiego ryzyka, np. limity, dokumenty, kary i uprawnienia,
- co 180 dni: standardowy przeglad pozostalych artykulow,
- natychmiast: po wykrytej nowelizacji powiazanej jednostki.

Planowana komenda:

```powershell
php artisan legal-content:audit-portfolio
```

Raport powinien pokazywac:

- strony po terminie review,
- strony z jednostkami `needs_review`,
- pytania bez aktywnej relacji,
- relacje do szerokiego artykulu zamiast ustepu lub punktu,
- artykuly bez pytan,
- kilka artykulow konkurujacych o ten sam klaster.

## Laczenie, dzielenie i wycofywanie

### Laczenie

Gdy dwa artykuly maja ten sam zamiar uzytkownika i znaczne nakladanie pytan:

- wybieramy kanoniczny slug,
- drugi artykul dostaje status `merged`,
- publiczny URL otrzymuje redirect 301,
- relacje pytan sa przenoszone dopiero po weryfikacji,
- historia pozostaje w rewizjach.

### Dzielenie

Artykul dzielimy, gdy:

- obejmuje kilka niezaleznych podstaw prawnych,
- dossier pokazuje wyrazne podklastry,
- uzytkownik musi podejmowac rozne decyzje egzaminacyjne,
- strona staje sie zbyt dluga albo jej tytul przestaje opisywac calosc.

Stary artykul moze pozostac hubem albo zostac zastapiony przekierowaniami.

### Wycofanie

Strona jest wycofywana, gdy:

- temat nie ma realnej wartosci edukacyjnej,
- podstawa prawna utracila aktualnosc i nie ma nastepcy,
- artykul jest duplikatem,
- wszystkie pytania okazaly sie falszywymi dopasowaniami.

Nie usuwamy historii ani decyzji discovery.

## Metryki portfela

Podstawowy dashboard powinien pokazywac:

- liczbe aktywnych artykulow,
- liczbe tematow `ready`,
- procent pytan z tematem `focused`,
- procent pytan ze zweryfikowana podstawa prawna,
- liczbe pytan bez zadnego kandydata `focused`,
- liczbe artykulow po terminie review,
- liczbe konfliktow odpowiedzi,
- liczbe klastrow z duzym nakladaniem,
- srednia i mediana pytan na artykul,
- artykuly bez pytan,
- pytania powiazane z wieloma konkurencyjnymi artykulami.

Wazne progi:

- `ready < 10` - uruchom discovery,
- `uncovered > 500` - zaplanuj nowa generacje regul,
- `stale articles > 10%` - zatrzymaj publikacje nowych wpisow i wykonaj maintenance,
- `overlap > 60%` - wymagany triage przed nowym artykulem,
- `verified coverage < 50%` w opublikowanym klastrze - oznacz jako niepelny, ale nie dopinaj pytan automatycznie.

## Roadmapa implementacji

### Etap A: obecny system

Status: wykonany.

- kandydaci i relacje planistyczne,
- pelna mapa JSON,
- kolejka i dossier dla agenta,
- oznaczanie wykorzystanego artykulu,
- brak automatycznej publikacji.

### Etap B: discovery deterministyczne

Priorytet: nastepny po spadku kolejki `ready` ponizej 10.

- komenda `discover-topic-candidates`,
- raport pytan bez tematu `focused`,
- ekstrakcja fraz i grupowanie wewnatrz filarow,
- zapis runow i propozycji,
- triage bez zmiany publicznych danych.

### Etap C: pelny lifecycle i ownership

Priorytet: przed rownolegla praca wielu agentow.

- statusy `discovered` do `published`,
- przejmowanie tematu przez agenta,
- blokada rownoleglej pracy,
- jawna relacja klaster - artykul,
- historia decyzji merge/split/reject.

### Etap D: paczki artykulow

Priorytet: przed przekroczeniem 25 artykulow.

- jeden plik na artykul,
- idempotentny importer,
- walidacja schematu paczki,
- migracja definicji z duzego seedera,
- test importu i eksportu.

### Etap E: maintenance automation

Priorytet: przed przekroczeniem 50 artykulow.

- audyt portfela,
- terminy review,
- wykrywanie zmian pytan,
- propagowanie `needs_review` z jednostek prawnych,
- raport stron wymagajacych reakcji.

### Etap F: panel administracyjny

Priorytet: przy 50-75 artykulach albo wczesniej, jesli kilka osob pracuje rownolegle.

- kolejka, ownership i status,
- podglad dossier,
- przypisywanie pytan,
- decyzje merge/split/reject,
- publikacja i rewizje,
- dashboard metryk.

### Etap G: discovery semantyczne

Priorytet: tylko jesli deterministyczne discovery nie zapewnia kolejnych dobrych tematow.

- wersjonowany model,
- odtwarzalne wyniki,
- raport outlierow i podobienstwa,
- brak automatycznej publikacji.

## Definition of Done skalowalnego systemu

System jest gotowy do obslugi duzej liczby wpisow, gdy:

- agent widzi jedna kolejke bez skanowania repo i bazy,
- kazdy temat ma historie pochodzenia i decyzji,
- nie da sie przypadkowo rozpoczac tej samej pracy przez dwoch agentow,
- wiele klastrow moze jawnie wskazywac jeden artykul,
- artykuly sa przechowywane poza jednym monolitycznym seederem,
- istnieje automatyczny raport nowych niepokrytych pytan,
- istnieje automatyczny raport artykulow wymagajacych review,
- merge, split i wycofanie nie usuwaja historii,
- publicznie nadal trafiaja tylko tresci i relacje `verified` / `published`.

## Najblizsza decyzja wdrozeniowa

Nie implementujemy jeszcze semantycznego discovery ani pelnego panelu.

Nastepny krok techniczny powinien nastapic, gdy:

- liczba `ready` spadnie z 33 do 10, albo
- liczba artykulow zblizy sie do 25.

Wtedy najpierw wdrazamy Etap B i D: deterministyczne discovery oraz paczki artykulow. Pozwoli to dalej rosnac bez rozbudowywania jednego seedera i bez utraty historii tematow.
