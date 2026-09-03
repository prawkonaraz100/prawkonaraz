# Workflow dodawania wpisow do dzialu Przepisy

Status: runbook operacyjny  
Ostatnia aktualizacja: 2026-06-22
Zakres: publiczny dzial `/przepisy`, strony `/przepisy/{slug}` i blok `Uzasadnienie prawne` na stronach pytan

## Cel dokumentu

Ten dokument jest dla kolejnego agenta, ktory ma dodac nastepny wysokiej jakosci wpis prawno-edukacyjny bez ponownego skanowania calego repozytorium.

Najwazniejsza zasada: kazdy nowy wpis musi byc dodany do sekcji `Rejestr opublikowanych tematow` w tym dokumencie. Dzieki temu kolejny agent od razu widzi, co juz istnieje, czego nie dublowac i gdzie dopisywac kolejny temat.

## TL;DR dla agenta

Nie zaczynaj od pelnego audytu repo. Pracuj glownie na tych plikach:

- `database/seeders/LegalTrustLayerMvpSeeder.php` - glowne miejsce dodawania tematow, stron, jednostek prawnych, source checks i powiazan z pytaniami.
- `tests/Feature/Public/LegalTrustLayerMvpTest.php` - test publicznego huba, detail page, bloku prawnego i sitemap.
- `docs/LEGAL-CONTENT-AUTHORING-WORKFLOW.md` - ten runbook i rejestr tematow.
- `docs/LEGAL-CONTENT-QUESTION-FIRST-AUDIT.md` - mapa dopiecia obecnych 10 artykulow do konkretnych pytan i per-question uzasadnien.
- `docs/LEGAL-ARTICLE-TOPIC-CANDIDATES.md` - gotowa lista 87 tematow wynikajacych z calej aktywnej bazy pytan.
- `resources/legal-content/generated/article-topic-question-map.json` - pelna mapa temat -> kanoniczne ID pytan -> prompty -> kategorie.
- `resources/legal-content/generated/agent-workspace/QUEUE.md` - krotka kolejka dla agenta: tematy wolne, wykorzystane i wymagajace recznej oceny.
- `resources/legal-content/generated/agent-workspace/topics/{slug}.md` - gotowe dossier wybranego tematu z odpowiedziami, mediami i relacjami prawnymi.
- `resources/legal-content/article-topic-candidates.php` - wersjonowane reguly grupowania promptow; zmieniaj je tylko po sprawdzeniu wynikow.
- `docs/LEGAL-CONTENT-SCALING-PLAN.md` - plan odkrywania kolejnych generacji tematow i utrzymania dziesiatek lub setek artykulow.
- Opcjonalnie `docs/LEGAL-TRUST-LAYER-PLAN.md` - tylko gdy zmienia sie status, liczba tematow albo szerszy zakres warstwy prawnej.

`/admin/content-authors` sluzy do profili autorow i reviewerow. Same wpisy w dziale `/przepisy` nie sa obecnie zarzadzane jako klasyczne artykuly w panelu. Aktualny workflow publikacji idzie przez idempotentny seeder.

## Jak dziala obecny workflow

Publiczny dzial prawny sklada sie z kilku warstw danych:

- `LegalAct` - caly akt prawny, np. Prawo o ruchu drogowym.
- `LegalUnit` - konkretna jednostka, np. `art. 20`.
- `LegalTopic` - temat edukacyjny, np. `predkosc-odstep-i-hamowanie`.
- `LegalContentPage` - publiczna strona `/przepisy/{slug}`.
- `legal_content_page_legal_unit` - powiazanie strony z jednostkami prawnymi.
- `LegalSourceCheck` - historia weryfikacji oficjalnego zrodla.
- `QuestionLegalReference` - zweryfikowane powiazanie pytania z przepisem i strona.
- `LegalArticleTopicCandidate` - wewnetrzny kandydat na artykul, wyprowadzony z istniejacego tematu nauki albo slow w promptcie.
- `legal_article_topic_candidate_question` - robocze powiazanie kandydata z pytaniami; nie jest publikowane jako podstawa prawna.

Publiczne renderowanie:

- `/przepisy` bierze tylko strony `published` z opublikowanym tematem.
- `/przepisy/{slug}` pokazuje autora, reviewera, date publikacji, date ostatniego review, tresc, podstawe prawna i powiazane pytania.
- Publiczna strona pytania pokazuje blok `Uzasadnienie prawne` tylko dla relacji `verified`.
- Nie wolno zmieniac `questions.explanation`, odpowiedzi, mediow ani logiki `/nauka` przy dodawaniu wpisu prawnego.

## Gotowa mapa tematow z calej bazy pytan

Nie skanuj ponownie wszystkich pytan przy wyborze kolejnego artykulu. Skan 17 044 aktywnych rekordow zostal wykonany 19.06.2026 i pogrupowany do:

- 31 tematow filarowych, ktore pokrywaja wszystkie 3 570 pytan kanonicznych,
- 56 wezsych kandydatow wykrytych wylacznie z tresci promptow,
- 767 pytan kanonicznych przypisanych do co najmniej jednego wezszego tematu.

### Najszybszy start dla kolejnego agenta

1. Otworz `resources/legal-content/generated/agent-workspace/QUEUE.md`.
2. Wybierz temat ze statusem `ready`.
3. Wygeneruj dossier:

```powershell
docker compose exec -T app php artisan legal-content:prepare-agent-workspace SLUG --refresh
```

4. Otworz:

```text
resources/legal-content/generated/agent-workspace/topics/SLUG.md
```

Dossier zawiera:

- wszystkie pytania kanoniczne i warianty z kategorii,
- prompty, odpowiedzi i tekst poprawnej odpowiedzi,
- istniejace wyjasnienia,
- informacje o obrazach i nagraniach,
- obecne zweryfikowane relacje prawne,
- automatyczne ostrzezenia o roznych promptach, zestawach odpowiedzi lub sprzecznych poprawnych odpowiedziach,
- status `ready`, `published`, `manual_review`, `in_progress` albo `rejected`.

Pelny JSON dossier lezy obok pliku Markdown i jest kanonicznym wejsciem maszynowym dla agenta.

Raport do szybkiego wyboru:

```text
docs/LEGAL-ARTICLE-TOPIC-CANDIDATES.md
```

Pelna lista pytan dla kazdego tematu:

```text
resources/legal-content/generated/article-topic-question-map.json
```

Poziomy:

- `pillar` - szeroki dzial oparty o istniejacy `QuestionTopic`; gwarantuje pokrycie calej bazy, ale zwykle jest za szeroki na jeden wpis,
- `focused` - kandydat na konkretny artykul wykryty z promptu; od niego zaczynaj planowanie nowej tresci.

Wazne rozroznienie:

- relacja kandydacka oznacza tylko `to pytanie prawdopodobnie pasuje do tematu`,
- `QuestionLegalReference` oznacza `to pytanie zostalo recznie zweryfikowane z oficjalnym przepisem`,
- synchronizacja kandydatow nigdy nie tworzy ani nie publikuje `QuestionLegalReference`.

Odswiezenie mapy po zmianie bazy albo regul:

```powershell
docker compose exec -T app php artisan legal-content:sync-article-topic-candidates
docker compose exec -T app php artisan legal-content:sync-article-topic-candidates --write --report=resources/legal-content/generated/LEGAL-ARTICLE-TOPIC-CANDIDATES.md --map=resources/legal-content/generated/article-topic-question-map.json
```

Pierwsza komenda jest dry-runem. Druga zapisuje kandydatow i relacje oraz generuje pliki. W lokalnym Dockerze katalog `docs` nie jest montowany do kontenera, dlatego wygenerowany raport trzeba przeniesc z `resources/legal-content/generated/` do `docs/LEGAL-ARTICLE-TOPIC-CANDIDATES.md`.

Odswiezenie samej kolejki i wybranego dossier:

```powershell
docker compose exec -T app php artisan legal-content:prepare-agent-workspace SLUG --refresh
```

## Dwa tryby pracy: article-first i question-first

Mamy dwa rownoprawne tryby tworzenia tresci. Oba koncza sie tym samym zestawem danych, ale zaczynaja od innego punktu.

### Tryb article-first

Ten tryb jest dobry, gdy dodajemy nowy, szeroki temat prawny do `/przepisy`, np. `pojazd-uprzywilejowany`.

Kolejnosc:

1. wybierz temat,
2. zweryfikuj oficjalne zrodlo,
3. dodaj `LegalUnit`,
4. dodaj `LegalTopic` i `LegalContentPage`,
5. dopiero potem dobierz pytania, ktore realnie sprawdzaja ten przepis,
6. dla kazdego pytania dodaj osobny `QuestionLegalReference.public_note`.

### Tryb question-first

Ten tryb jest preferowany przy obecnych 10 artykulach, bo mamy juz realne pytania, ktore powinny prowadzic do konkretnych podstaw prawnych.

Kolejnosc:

1. wybierz pytanie albo maly klaster pytan,
2. przeczytaj prompt, poprawna odpowiedz, istniejace wyjasnienie, media i kategorie,
3. okresl, jaki dokladnie obowiazek lub zakaz jest sprawdzany,
4. zweryfikuj oficjalny przepis w ISAP / ELI / Dzienniku Ustaw,
5. dopasuj mozliwie najdokladniejsza jednostke prawna,
6. dopisz albo popraw `QuestionLegalReference.public_note`,
7. sprawdz, czy strona `/przepisy/{slug}` opisuje ten typ pytania w `exam_context` i `body`,
8. jesli artykul jest zbyt ogolny, popraw artykul, ale nie zmieniaj odpowiedzi ani `questions.explanation`.

W praktyce: artykul ma byc filarem tematu, a `public_note` ma byc mostem miedzy tym artykulem i konkretnym pytaniem.

## Standard pytaniowego uzasadnienia prawnego

`QuestionLegalReference.public_note` jest kanonicznym miejscem na uzasadnienie prawne dla konkretnego pytania. To nie jest tresc odpowiedzi w module nauki.

Zasady:

- ma miec 1-3 krotkie zdania,
- ma byc unikalne dla konkretnego pytania albo bardzo waskiego wariantu pytan,
- ma wyjasniac, dlaczego to pytanie laczy sie z ta jednostka prawna,
- nie moze kopiowac dlugich fragmentow ustawy,
- nie moze zmieniac ani nadpisywac `questions.explanation`,
- nie powinno zdradzac wiecej niz potrzeba, jesli pytanie ma prosty charakter,
- powinno unikac pustych formul typu `to pytanie dotyczy przepisow ruchu drogowego`.

Wzor roboczy:

```text
To pytanie sprawdza [konkretny obowiazek/zakaz/uprawnienie] w sytuacji [kontekst z pytania]. Podstawa jest [dokladna jednostka], bo [krotkie powiazanie z poprawna odpowiedzia].
```

Przyklad stylu, nie gotowa tresc do publikacji:

```text
To pytanie sprawdza obowiazek zachowania sie kierujacego przy przystanku tramwajowym bez wysepki. Podstawa powinna wskazywac dokladny ustep art. 26 po weryfikacji w aktualnym tekscie jednolitym.
```

## Granularnosc jednostek prawnych

Dotychczasowe artykuly czesto sa przypiete do szerokich jednostek, np. `art. 26`. To jest akceptowalne dla strony tematu, ale dla pytania powinnismy isc nizej, jesli przepis ma konkretne ustepy albo punkty.

Reguly:

- strona `/przepisy/{slug}` moze byc przypieta do szerokiego artykulu albo kilku jednostek,
- `QuestionLegalReference` powinien wskazywac najdokladniejsza jednostke, jaka da sie uczciwie zweryfikowac,
- jesli pytanie dotyczy konkretnego ustepu, dodaj osobny `LegalUnit`, np. `art. 26 ust. X`,
- jesli pytanie wynika z kilku przepisow, wybierz glowna jednostke i opisz pomocniczy kontekst w `public_note`,
- jesli nie umiemy wskazac dokladnej podstawy, relacja zostaje `draft` albo `needs_review`, nie `verified`.

## Jak artykul ma wynikac z pytan

Kazdy artykul powinien miec warstwe edukacyjna zbudowana na realnych pytaniach, nie tylko ogolny opis przepisu.

Minimalny standard dla obecnych i nowych artykulow:

- `exam_context` opisuje, jak temat pojawia sie w pytaniach egzaminacyjnych,
- `body` ma osobna czesc praktyczna: co kandydat ma rozpoznac na ekranie pytania,
- `key_points` nie powtarza samych naglowkow ustawy, tylko streszcza decyzje egzaminacyjne,
- powiazane pytania maja `public_note`, ktory laczy widoczna sytuacje z podstawa prawna,
- pytania z tym samym artykulem, ale innym problemem, nie dostaja identycznej notatki.

To jest najwazniejsze polaczenie obu swiatow: artykul daje zaufanie i kontekst, a pytanie pokazuje praktyczny egzaminacyjny przypadek.

## Audyt pierwszych 10 artykulow

Pierwsze 10 artykulow przechodzi osobny audyt question-first. Nowe artykuly od razu powinny powstawac wedlug standardu z tego dokumentu.

| Slug | Obecny stan | Ryzyko | Decyzja |
| --- | --- | --- | --- |
| `zatrzymanie-i-postoj` | Question-first wykonany 20.06.2026. | Nowe pytania moga dotyczyc innych punktow art. 49. | Pytanie `10237` wskazuje art. 49 ust. 1 pkt 5; nowe scenariusze przypisywac do konkretnego zakazu. |
| `tramwaje-i-przystanki` | Question-first wykonany 15.06.2026. | Nowe pytania moga dotyczyc innego rodzaju przystanku albo osobnej reguly. | Pytania `99` i `10314` wskazuja art. 26 ust. 6; szeroka relacja do art. 26 jest usuwana. |
| `piesi-i-przejscia` | Question-first wykonany dla pytan `10249`, `10369`, `10474`. | Art. 13 opisuje obowiazki pieszego, ale badane pytania dotycza obowiazkow kierujacego. | Pytania `10249` i `10474` wskazuja art. 26 ust. 1, a `10369` wskazuje art. 26 ust. 4. |
| `pierwszenstwo-przejazdu` | Question-first wykonany 20.06.2026. | Temat laczy ustawe i rozporzadzenie o znakach. | Skret w lewo wskazuje art. 25 ust. 1, a rondo z A-7 i C-12 wskazuje § 36 ust. 2 rozporzadzenia. |
| `sygnalizacja-i-osoby-kierujace-ruchem` | Question-first wykonany 20.06.2026. | Sam art. 5 nie opisuje znaczenia konkretnego sygnalu. | Pytania wskazuja § 108 ust. 2 albo § 95 ust. 1 pkt 4; art. 5 pozostaje podstawa hierarchii strony. |
| `predkosc-odstep-i-hamowanie` | Question-first wykonany 20.06.2026 dla osmiu opublikowanych pytan. | Szeroki artykul nadal pelni role huba dla kilku podtematow, dlatego nowe pytania trzeba przypisywac do precyzyjnych jednostek. | Pytania wskazuja art. 19 ust. 1, art. 19 ust. 2 pkt 3, art. 19 ust. 3a, art. 20 ust. 2 albo art. 20 ust. 3 pkt 1 lit. a; stare relacje do szerokich art. 19 i 20 sa usuwane. |
| `zmiana-kierunku-i-pasa-ruchu` | Question-first wykonany 20.06.2026. | Nowe pytania moga wymagac art. 22 ust. 4 albo ust. 6. | Obecne pytania wskazuja art. 22 ust. 1, ust. 2 pkt 2 albo ust. 5. |
| `wlaczanie-sie-do-ruchu` | Question-first wykonany 20.06.2026. | Latwo pomylic definicje z obowiazkami albo ruszaniem na zielonym. | Obecne pytania wskazuja art. 17 ust. 1, ust. 1 pkt 1 albo ust. 2; autobusy pozostaja osobnym tematem. |
| `wymijanie-omijanie-cofanie` | Question-first wykonany 20.06.2026. | Slowo `odstep` nadal moze mylic z art. 24. | Obecne pytania zostaly potwierdzone dla art. 23 ust. 1 pkt 1-3 i ust. 2. |
| `wyprzedzanie` | Question-first wykonany 20.06.2026. | Pytania czysto znakowe nadal wymagaja osobnej podstawy. | Obecny zestaw przypisano do konkretnych jednostek art. 24; nie dodano pytan opartych tylko na znakach. |

Szczegolowy plan audytu jest w `docs/LEGAL-CONTENT-QUESTION-FIRST-AUDIT.md`.

## Rejestr opublikowanych tematow

Kazdy nastepny agent ma dopisac tutaj swoj nowy temat.

| Slug | Tytul strony | Jednostki prawne | Publikacja / review | Powiazane pytania | Uwagi |
| --- | --- | --- | --- | --- | --- |
| `zatrzymanie-i-postoj` | Zatrzymanie i postoj pojazdu | Prawo o ruchu drogowym, art. 49; pytanie: art. 49 ust. 1 pkt 5 | 03.06.2026 / 20.06.2026 | `10237` | Question-first wykonany: zatrzymanie na poboczu obok ciaglej linii krawedziowej. |
| `tramwaje-i-przystanki` | Tramwaje, przystanki i bezpieczenstwo pasazerow | Prawo o ruchu drogowym, art. 26 ust. 6 | 03.06.2026 / 15.06.2026 | `99`, `10314` | Zachowanie przy przystanku tramwajowym bez wysepki. Relacje pytan doprecyzowane question-first 15.06.2026. |
| `piesi-i-przejscia` | Piesi i przejscia dla pieszych | Prawo o ruchu drogowym, art. 13, art. 26; pytania: art. 26 ust. 1 i ust. 4 | 03.06.2026 / 19.06.2026 | `10249`, `10369`, `10474` | Question-first wykonany: przejscie z pieszym, dojazd do pustego przejscia i wjazd do bramy przez droge dla pieszych. |
| `pierwszenstwo-przejazdu` | Pierwszenstwo przejazdu na skrzyzowaniu | Prawo o ruchu drogowym, art. 25 ust. 1; rozporzadzenie o znakach, § 36 ust. 2 | 03.06.2026 / 20.06.2026 | `10154`, `10247` | Question-first wykonany: rondo A-7 + C-12 oraz skret w lewo wobec pojazdu z przeciwka. |
| `sygnalizacja-i-osoby-kierujace-ruchem` | Sygnalizacja i osoby kierujace ruchem | Prawo o ruchu drogowym, art. 5; rozporzadzenie o znakach, § 95 ust. 1 pkt 4 i § 108 ust. 2 | 03.06.2026 / 20.06.2026 | `10107`, `469` | Question-first wykonany: policjant zwrocony bokiem oraz jednoczesny sygnal czerwony i zolty. |
| `predkosc-odstep-i-hamowanie` | Predkosc, odstep i hamowanie pojazdu | Prawo o ruchu drogowym, art. 19 ust. 1, art. 19 ust. 2 pkt 2-3, art. 19 ust. 3a, art. 20 ust. 2, art. 20 ust. 3 pkt 1 lit. a | 13.06.2026 / 20.06.2026 | `13562`, `13781`, `13782`, `4170`, `7237`, `13035`, `2484`, `3966` | Question-first wykonany: widocznosc drogi, bezpieczne hamowanie i odstep, regula polowy predkosci, strefa zamieszkania oraz limit 140 km/h na autostradzie. |
| `zmiana-kierunku-i-pasa-ruchu` | Zmiana kierunku i pasa ruchu | Prawo o ruchu drogowym, art. 22 ust. 1, ust. 2 pkt 2, ust. 5 | 13.06.2026 / 20.06.2026 | `7219`, `11089`, `11090`, `11500`, `4488`, `2568`, `4155` | Question-first wykonany: ostroznosc, ustawienie przed skretem i sygnalizowanie. |
| `wlaczanie-sie-do-ruchu` | Wlaczanie sie do ruchu | Prawo o ruchu drogowym, art. 17 ust. 1, ust. 1 pkt 1, ust. 2 | 13.06.2026 / 20.06.2026 | `2287`, `6084`, `6095`, `7336`, `1142`, `1338`, `10847` | Question-first wykonany: definicja, typowe wyjazdy, ustapienie i kontrprzyklad ruszenia po zielonym. |
| `wymijanie-omijanie-cofanie` | Wymijanie, omijanie i cofanie | Prawo o ruchu drogowym, art. 23 ust. 1 pkt 1-3, ust. 2 | 13.06.2026 / 20.06.2026 | `3154`, `10966`, `3155`, `3157`, `13540`, `2501`, `13773`, `6206`, `6203`, `6208` | Question-first wykonany: osobne podstawy dla wymijania, omijania, cofania i zakazow cofania. |
| `wyprzedzanie` | Wyprzedzanie | Prawo o ruchu drogowym, art. 24 ust. 1 pkt 2, ust. 2, ust. 6, ust. 7 pkt 1 i 3, ust. 11 | 13.06.2026 / 20.06.2026 | `7403`, `10208`, `7640`, `9541`, `8979`, `10053`, `7398`, `7777`, `10937`, `7012`, `10939`, `12779` | Question-first wykonany: rozpoczecie manewru, odstep, minimum 1 m, zachowanie wyprzedzanego i zakazy. |
| `autobus-wyjezdzajacy-z-przystanku` | Autobus wyjezdzajacy z przystanku | Prawo o ruchu drogowym, art. 18 ust. 1 i 2 | 19.06.2026 / 19.06.2026 | `1133`, `4260`, `7379`, `11046`, `11055`, `11120`, `11122`, `11123`, `11124` | Pierwszy artykul wybrany z mapy kandydatow. Obejmuje warunki obowiazku w obszarze zabudowanym, kontrprzyklad poza nim i obowiazek kierowcy autobusu, by nie tworzyc zagrozenia. |
| `zawracanie` | Zawracanie: gdzie wolno, znaki i sygnalizacja | Prawo o ruchu drogowym, art. 22 ust. 6 pkt 1-4 i art. 25 ust. 2; rozporzadzenie w sprawie znakow i sygnalow drogowych, par. 22 ust. 1-2 i 5, par. 87 ust. 1-2, par. 96 ust. 2-3, par. 97 ust. 1 i 3 | 20.06.2026 / 20.06.2026 | 45 pytan: `1492`, `4211`, `6033`, `6181`, `6185`, `1490`, `1491`, `1514`, `4596`, `1428`, `6019`, `6023`, `7274`, `7279`, `8359`, `8391`, `13085`, `11152`, `1517`, `7277`, `8380`, `8387`, `8388`, `8389`, `9633`, `10189`, `10252`, `10435`, `11076`, `2904`, `8439`, `8440`, `610`, `1691`, `2902`, `6054`, `7308`, `1474`, `6134`, `6129`, `8648`, `8660`, `3364`, `4001`, `3445` | Drugi artykul z kolejki agent workspace. Dossier mialo 62 pytania kanoniczne; opublikowano 45 relacji mozliwych do potwierdzenia z promptu i wyjasnienia, a 17 zależnych od obrazu pozostawiono do recznej analizy mediow. |
| `sygnal-dzwiekowy` | Sygnal dzwiekowy: kiedy wolno uzyc klaksonu | Prawo o ruchu drogowym, art. 29 ust. 1, art. 29 ust. 2 pkt 1-2, art. 30 ust. 1 pkt 1 lit. b; rozporzadzenie w sprawie warunkow technicznych pojazdow, par. 11 ust. 1 pkt 6 | 20.06.2026 / 20.06.2026 | 20 pytan: `2187`, `1157`, `3653`, `6217`, `7127`, `7129`, `13763`, `2212`, `1144`, `1770`, `3363`, `6235`, `7471`, `8284`, `9255`, `9261`, `10428`, `10981`, `10986`, `6448` | Trzeci artykul z kolejki agent workspace. Rozdziela ostrzeganie o zagrozeniu, zakaz naduzywania, wyjatek w obszarze zabudowanym, szczegolny obowiazek podczas mgly i wymagania techniczne klaksonu. Pozostalych 12 pytan nie przypisano z powodu zaleznosci od mediow albo innej glownej podstawy prawnej. |
| `zabezpieczenie-ladunku-i-wymiary` | Zabezpieczenie ladunku: mocowanie, wymiary i oznakowanie | Prawo o ruchu drogowym, art. 61 ust. 1-7 i ust. 9 pkt 3-4 | 21.06.2026 / 21.06.2026 | 24 pytania: `6695`, `7615`, `11474`, `1877`, `6580`, `6589`, `10086`, `11475`, `1881`, `1883`, `1878`, `6575`, `6579`, `10085`, `1880`, `1879`, `6694`, `6402`, `7725`, `7531`, `11476`, `11517`, `11518`, `7451` | Czwarty artykul z kolejki. Z 28 pytan kandydata 23 przypisano bezposrednio do precyzyjnych jednostek art. 61; dodatkowo dopieto pytanie o drewno dlugie z klastra przyczep. Piec pytan technicznych pozostawiono bez relacji. |
| `przyczepa-masa-wymiary-i-oswietlenie` | Przyczepa: masa, wymiary i obowiazkowe oswietlenie | Prawo o ruchu drogowym, art. 62 ust. 1 pkt 1 i 3 oraz ust. 4a pkt 1-2; rozporzadzenie w sprawie warunkow technicznych pojazdow, par. 12 ust. 1 pkt 11 | 21.06.2026 / 21.06.2026 | 8 pytan: `7452`, `7511`, `6571`, `6588`, `6403`, `6857`, `10084`, `6663` | Piaty artykul z kolejki. Opublikowano wyłącznie pytania o masę, dlugosc zespolu i boczne odblaski; pytania o cofanie, wzniesienie, holowanie i przewoz osob pozostaly poza relacjami. |
| `wjazd-na-przejazd-kolejowy` | Przejazd kolejowy: kiedy nie wolno wjechac na tory | Prawo o ruchu drogowym, art. 28 ust. 1-3; rozporzadzenie w sprawie znakow i sygnalow drogowych, par. 21 ust. 1 i 4 oraz par. 98 ust. 5 | 21.06.2026 / 21.06.2026 | 23 pytania: `8328`, `13638`, `2472`, `6072`, `6078`, `6278`, `6280`, `6283`, `6286`, `8305`, `8309`, `8314`, `10242`, `13464`, `13466`, `13635`, `13637`, `13469`, `4258`, `8304`, `8386`, `11265`, `2443` | Szosty artykul z kolejki. Rozdziela ostroznosc, zapory, brak miejsca za przejazdem, wyprzedzanie, STOP i czerwony sygnal. Trzy pytania zbyt zalezne od obrazu pozostawiono bez relacji. |
| `holowanie-pojazdu` | Holowanie pojazdu: predkosc, oznakowanie i zakazy | Prawo o ruchu drogowym, art. 31 ust. 1-2 i art. 45 ust. 1 pkt 2 | 21.06.2026 / 21.06.2026 | 14 pytan: `13088`, `7171`, `6581`, `6582`, `6657`, `7510`, `7512`, `10109`, `10081`, `7539`, `7540`, `7454`, `10080`, `11506` | Siodmy artykul z kolejki. Pytania tramwajowe, pole widzenia i swiatla awaryjne pozostawiono poza relacjami, bo nie wynikaja wprost z wybranych jednostek art. 31. |
| `pasy-bezpieczenstwa` | Pasy bezpieczenstwa: kto musi zapinac i jakie sa wyjatki | Prawo o ruchu drogowym, art. 39 ust. 1, ust. 2 pkt 2 i 10 | 21.06.2026 / 21.06.2026 | 8 pytan: `4483`, `6344`, `6388`, `6389`, `6393`, `6907`, `7441`, `10811` | Osmy artykul z kolejki. Obejmuje obowiazek kierowcy i pasazerow oraz dwa jednoznaczne wyjatki; technika zapinania, kontrolki i foteliki pozostaly odrebnymi tematami. |
| `swiatla-do-jazdy-dziennej-i-mijania` | Swiatla mijania i dzienne: kiedy ktorych uzywac | Prawo o ruchu drogowym, art. 51 ust. 1-2 i art. 30 ust. 1 pkt 1 lit. a | 21.06.2026 / 21.06.2026 | 11 pytan: `1319`, `3544`, `6226`, `7470`, `7884`, `2065`, `4396`, `6211`, `10976`, `6224`, `10979` | Dziewiaty artykul z kolejki. Rozdziela podstawowy obowiazek, warunki uzycia swiatel dziennych i pogorszona przejrzystosc; swiatla drogowe, przeciwmglowe i tunel pozostawiono poza relacjami. |
| `rogatki-i-sygnaly-na-przejezdzie-kolejowym` | Rogatki i czerwone swiatlo: kiedy wolno ruszyc | Prawo o ruchu drogowym, art. 28 ust. 1-2 i ust. 3 pkt 1; rozporzadzenie w sprawie znakow i sygnalow drogowych, par. 98 ust. 5 | 21.06.2026 / 21.06.2026 | 13 pytan: `2436`, `2467`, `13473`, `13618`, `2458`, `2472`, `6283`, `8309`, `8316`, `10242`, `13464`, `13466`, `2443` | Dziesiaty artykul z kolejki. Skupia sie na sekwencji otwierania przejazdu: wygaszenie czerwonego sygnalu, pelne podniesienie zapor i ponowna obserwacja torow. Pytania konstrukcyjne oraz zalezne od nieczytelnego kadru pozostaly bez relacji. |
| `swiatla-przeciwmglowe` | Swiatla przeciwmglowe: przednie, tylne i granica 50 metrow | Prawo o ruchu drogowym, art. 30 ust. 1 pkt 1 lit. a, art. 30 ust. 3 i art. 51 ust. 5 | 21.06.2026 / 21.06.2026 | 10 pytan: `6216`, `7464`, `6363`, `7675`, `8238`, `8245`, `11457`, `13105`, `13444`, `2072` | Jedenasty artykul z kolejki. Rozdziela zasady swiatel przednich, prog ponizej 50 m dla tylnych oraz nocny wyjatek na oznakowanej drodze kretej. Dwa pytania oceniane wylacznie z obrazu pozostaly bez publikacji relacji. |
| `foteliki-i-przewoz-dzieci` | Przewoz dzieci: fotelik, wzrost 150 cm i wyjatki | Prawo o ruchu drogowym, art. 39 ust. 3-3c, ust. 4 pkt 4 oraz art. 45 ust. 2 pkt 4-6 | 21.06.2026 / 21.06.2026 | 14 pytan: `10812`, `10869`, `10876`, `10815`, `10871`, `10813`, `10882`, `13382`, `10806`, `10807`, `10809`, `10810`, `10840`, `10969` | Dwunasty artykul z kolejki. Obejmuje regule 150 cm, ograniczony wyjatek od 135 cm, trzecie dziecko, montaz zgodny z instrukcja, zaswiadczenie lekarskie i zakazy dotyczace przedniego siedzenia. |
| `obowiazki-uczestnika-wypadku` | Wypadek drogowy: obowiazki uczestnika krok po kroku | Prawo o ruchu drogowym, art. 44 ust. 1-3 | 21.06.2026 / 21.06.2026 | 11 pytan: `2621`, `3658`, `6292`, `6294`, `6295`, `6296`, `6297`, `6298`, `6299`, `6404`, `11250` | Trzynasty artykul z kolejki. Rozdziela zdarzenie z rannymi od kolizji bez ofiar oraz wskazuje zabezpieczenie miejsca, pomoc, wezwanie sluzb, pozostanie na miejscu, usuniecie pojazdu i wymiane danych. |
| `telefon-podczas-kierowania` | Telefon za kierownica: co dokladnie jest zabronione | Prawo o ruchu drogowym, art. 45 ust. 2 pkt 1 | 21.06.2026 / 21.06.2026 | 6 pytan: `3991`, `3992`, `4006`, `4027`, `6390`, `13535` | Czternasty artykul z kolejki. Rozdziela telefon trzymany w rece od zestawu niewymagajacego trzymania i obejmuje samochod, tramwaj oraz hulajnoge elektryczna. |
| `swiatla-drogowe-i-oslepianie` | Swiatla drogowe: kiedy wolno ich uzywac i kiedy je wylaczyc | Prawo o ruchu drogowym, art. 51 ust. 3 oraz ust. 4 pkt 1-2 | 21.06.2026 / 21.06.2026 | 11 pytan: `6213`, `6222`, `6223`, `6225`, `7414`, `7416`, `7469`, `10406`, `10779`, `10980`, `11027` | Pietnasty artykul z kolejki. Rozdziela podstawowe warunki uzywania swiatel drogowych, pojazd z przeciwka oraz pojazd poprzedzajacy, takze po zakonczeniu wyprzedzania. |
| `gasnica-trojkat-i-obowiazkowe-wyposazenie` | Wyposazenie pojazdu: gasnica, trojkat i najczestsze pulapki | Rozporzadzenie w sprawie warunkow technicznych pojazdow, par. 11 ust. 1 pkt 5c, 6, 13-14, par. 12 ust. 3 pkt 11, par. 40 ust. 2 pkt 8 i par. 46 ust. 1 pkt 2 | 21.06.2026 / 21.06.2026 | 6 pytan: `1905`, `6499`, `6500`, `6501`, `7177`, `11442` | Szesnasty artykul z kolejki. Z 11 pytan klastra odrzucono pytania tramwajowe, porady gasnicze bez bezposredniej podstawy technicznej oraz pytanie o apteczke oparte na braku uniwersalnego obowiazku. |
| `opony-bieznik-i-cisnienie` | Opony: minimalny bieznik, cisnienie i zgodnosc na osi | Rozporzadzenie w sprawie warunkow technicznych pojazdow, par. 11 ust. 5 i ust. 7 pkt 1 oraz 4 | 21.06.2026 / 21.06.2026 | 7 pytan: `3784`, `6438`, `6626`, `6629`, `7553`, `7603`, `11492` | Siedemnasty artykul z kolejki. Cztery pytania o czerwona kontrolke cisnienia oleju odrzucono jako falszywe trafienia wynikajace ze wspolnego slowa „cisnienie”. |
| `dokumenty-podczas-kontroli-drogowej` | Dokumenty podczas kontroli drogowej: co trzeba miec przy sobie | Prawo o ruchu drogowym, art. 38 ust. 1 pkt 1, 3a, 4a, 4b lit. a-b i 5 oraz ust. 2-3 | 22.06.2026 / 22.06.2026 | 22 pytania: `10827`, `10834`, `10835`, `10836`, `10838`, `10839`, `10841`, `10843`, `10885`, `10886`, `10887`, `10888`, `10889`, `10890`, `10891`, `10892`, `10893`, `10894`, `10895`, `10999`, `11001`, `11041` | Osiemnasty artykul z kolejki. Rozdziela elektroniczna weryfikacje krajowych dokumentow, inne dokumenty uprawnienia, pokwitowania, blokade alkoholowa, pojazd zagraniczny i jazde testowa. Pytanie `2684` o dokument tramwaju pozostawiono bez relacji, bo klaster nie dawal wystarczajaco precyzyjnej podstawy. |
| `kategorie-prawa-jazdy-i-uprawnienia` | Kategorie prawa jazdy: czym mozesz kierowac | Ustawa o kierujacych pojazdami, art. 3 ust. 1 pkt 1, art. 6 ust. 1 i 3, art. 13 ust. 1 oraz art. 18 ust. 1 | 22.06.2026 / 22.06.2026 | 23 pytania: `6401`, `6414`, `6422`, `6430`, `6431`, `6599`, `6604`, `6606`, `6608`, `6610`, `6613`, `6617`, `6618`, `6708`, `6709`, `7560`, `7596`, `7625`, `7626`, `10090`, `10091`, `11060`, `11070` | Dziewietnasty artykul z kolejki. Rozdziela AM, A1, A2, A, B1, B i T, krajowe rozszerzenia kategorii B, termin waznosci dokumentu oraz 30-dniowy termin zgloszenia utraty, zniszczenia lub zmiany danych. |
| `alkohol-i-srodki-dzialajace-podobnie` | Alkohol za kierownica: limity, zakaz i odpowiedzialnosc | Prawo o ruchu drogowym, art. 45 ust. 1 pkt 1-2; ustawa o wychowaniu w trzezwosci, art. 46 ust. 2-3; Kodeks wykroczen, art. 87 par. 1; Kodeks karny, art. 178a par. 1 | 22.06.2026 / 22.06.2026 | 8 pytan: `2756`, `2759`, `4025`, `8416`, `8417`, `8419`, `11417`, `11506` | Dwudziesty artykul z kolejki. Z 15 pytan klastra opublikowano tylko relacje wynikajace z konkretnych norm; pytania o zmeczenie, pole widzenia, wielozadaniowosc i subiektywna ocene ryzyka pozostaly bez bezposredniej podstawy prawnej. |
| `zielona-strzalka-warunkowa` | Zielona strzalka warunkowa: zatrzymanie i pierwszenstwo | Rozporzadzenie w sprawie znakow i sygnalow drogowych, par. 96 ust. 1-3 | 22.06.2026 / 22.06.2026 | 4 pytania: `475`, `7280`, `7329`, `10454` | Dwudziesty pierwszy artykul z kolejki. Wszystkie pytania sprawdzaja ten sam warunek: przed skretem trzeba calkowicie zatrzymac pojazd i nie utrudnic ruchu innym uczestnikom. |
| `pojazd-uprzywilejowany` | Pojazd uprzywilejowany i korytarz zycia: jak ustapic | Prawo o ruchu drogowym, art. 2 pkt 38, art. 9 ust. 1-4 i art. 24 ust. 11; rozporzadzenie w sprawie znakow i sygnalow drogowych, par. 58 ust. 3 | 22.06.2026 / 22.06.2026 | 6 pytan: `7012`, `7153`, `10698`, `10700`, `10720`, `10730` | Dwudziesty drugi artykul z kolejki. Rozdziela rozpoznanie pojazdu, ogolny obowiazek ustapienia, korytarz dla dwoch i wielu pasow, zakaz jazdy korytarzem oraz granice obszaru zabudowanego przy D-43. |
| `sygnal-zolty-i-zolty-migajacy` | Zolte swiatlo: kiedy sie zatrzymac i co oznacza miganie | Rozporzadzenie w sprawie znakow i sygnalow drogowych, par. 95 ust. 1 pkt 2 i 4 oraz par. 98 ust. 6 | 22.06.2026 / 22.06.2026 | 4 pytania: `7779`, `10411`, `10459`, `10460` | Dwudziesty trzeci artykul z kolejki. Wszystkie pytania przypisano do funkcji ostrzegawczej zoltego migajacego, a strona dodatkowo wyjasnia staly zolty i czerwony z zoltym. |

## Jak wybrac kolejny temat

Dobry temat powinien spelniac minimum 3 warunki:

- wystepuje jako `focused` w `docs/LEGAL-ARTICLE-TOPIC-CANDIDATES.md`,
- wystepuje w wielu pytaniach egzaminacyjnych albo spina wazny klaster znakow,
- da sie oprzec o konkretna jednostke prawna z oficjalnego zrodla,
- nie dubluje istniejacego sluga z rejestru powyzej.

Kolejnosc wyboru:

1. Otworz `agent-workspace/QUEUE.md` i wybierz temat `ready`.
2. Wygeneruj dossier sluga i przeczytaj wszystkie przypisane pytania.
3. W razie potrzeby siegnij do pelnej mapy JSON, ale nie zaczynaj od jej recznego skanowania.
4. Odrzuc falszywe dopasowania i podziel temat, jesli pytania sprawdzaja rozne przepisy.
5. Sprawdz, czy `existing_article_slug` wskazuje artykul do rozbudowy; jesli nie, przygotuj nowy wpis.
6. Dopiero po weryfikacji oficjalnego prawa tworz `LegalUnit`, strone i `QuestionLegalReference`.

Przed publikacja zawsze sprawdz oficjalny tekst aktu. Mapa zostala zbudowana bez analizy odpowiedzi, wyjasnien i mediow, wiec jest kolejka redakcyjna, a nie dowodem prawnym.

## Zrodla i weryfikacja prawna

Przed napisaniem tresci zawsze zweryfikuj aktualny stan prawa.

Preferowana kolejnosc zrodel:

1. ISAP / Dziennik Ustaw / ELI.
2. Oficjalne strony `gov.pl` albo ministerstw.
3. Inne zrodla tylko jako trop roboczy, nigdy jako finalna podstawa.

Wazne:

- Dla aktualnych wartosci i brzmienia przepisu uzywaj tekstu ujednoliconego, nie wylacznie tekstu ogloszonego.
- Nie kopiuj komentarzy, opisow ani interpretacji z konkurencji.
- Jesli podstawa jest niepewna, nie publikuj relacji `verified`.
- Kazda publiczna jednostka prawna ma miec `source_url`, `last_checked_at`, `status = verified` i wpis w `LegalSourceCheck`.

## Daty i nazwy zmiennych

W seederze trzymaj daty publikacji/review jako zmienne partii, nie jako nazwy jednego tematu.

Dobrze:

```php
$june13ReviewedAt = Carbon::parse('2026-06-13 10:00:00', config('app.timezone'))->utc();
$june13PublishedAt = Carbon::parse('2026-06-13 10:00:00', config('app.timezone'))->utc();
```

W dokumentacji i szkicach mozesz uzywac nazw neutralnych:

```php
$batchReviewedAt
$batchPublishedAt
```

Unikaj nazw typu `$speedReviewedAt`, jesli ta sama data ma potem obslugiwac kilka wpisow. Przy kolejnym wpisie taka nazwa myli agenta i zwieksza ryzyko przypadkowych zmian.

## Kroki implementacji

### 1. Znajdz pytania i potwierdz temat

Najpierw uzyj gotowej mapy:

```powershell
$map = Get-Content -Raw resources/legal-content/generated/article-topic-question-map.json | ConvertFrom-Json
$map.topics | Where-Object slug -eq "pojazd-uprzywilejowany" | Select-Object -ExpandProperty questions
```

Zapytania do bazy stosuj dopiero do sprawdzania wybranych ID, odpowiedzi, wyjasnien i mediow:

```powershell
docker compose exec -T app php artisan tinker --execute 'dump(App\Models\Question::query()->where("prompt", "ilike", "%wyprzedz%")->limit(20)->get(["id","external_id","prompt","is_active"])->toArray());'
```

```powershell
docker compose exec -T app php artisan tinker --execute 'dump(App\Models\Question::query()->whereIn("external_id", ["1234","pj360:1234"])->get(["id","external_id","prompt","is_active"])->toArray());'
```

Mozesz tez szukac tropow w:

- `resources/topic-overrides/*exact-topic-membership-package.json`
- istniejacych seederach znakow drogowych, jesli temat ma spinac znaki i pytania.

W PowerShell unikaj podawania wildcarda jako czesci sciezki dla `rg`, bo latwo dostac falszywy blad I/O. Bezpieczniej:

```powershell
rg -n "wyprzedz|zawrac" resources/topic-overrides
rg --glob "*exact-topic-membership-package.json" -n "wyprzedz|zawrac" resources/topic-overrides
```

### 2. Dodaj lub rozszerz jednostki prawne

W `LegalTrustLayerMvpSeeder.php` dodaj definicje w `upsertLegalUnits()`.

Wzor:

```php
'art-9' => [
    'label' => 'art. 9',
    'title' => 'Pojazd uprzywilejowany',
    'summary' => 'Krotkie, edukacyjne streszczenie bez kopiowania przepisu.',
    'last_checked_at' => $batchReviewedAt,
    'source_check_notes' => 'Weryfikacja oficjalnego zrodla ISAP/ELI dla art. 9 Prawa o ruchu drogowym.',
],
```

Jesli temat korzysta z nowego aktu prawnego, najpierw trzeba dodac albo rozszerzyc `LegalAct`. Przy obecnych tematach najczesciej wystarcza istniejacy akt `prawo-o-ruchu-drogowym`.

### 3. Dodaj temat

W `upsertTopics()` dodaj nowy slug:

```php
'pojazd-uprzywilejowany' => [
    'title' => 'Pojazd uprzywilejowany',
    'sort_order' => 110,
    'description' => 'Opis tematu widoczny jako kontekst katalogowy.',
    'published_at' => $batchPublishedAt,
],
```

Uzywaj kolejnego `sort_order` po ostatnim temacie. Obecnie ostatni zajety to `100`.

### 4. Dodaj strone publiczna

W `upsertPages()` dodaj definicje `LegalContentPage`.

Minimalny pakiet jakosci:

- `title` - jasny tytul strony, nie SEO-spam.
- `meta_title` - naturalny tytul do wynikow wyszukiwania.
- `meta_description` - 140-180 znakow, praktyczna obietnica.
- `intro` - jedno zdanie ustawiajace temat.
- `summary` - krotka odpowiedz "w skrocie".
- `exam_context` - jak temat pojawia sie na egzaminie.
- `body` - 3-5 akapitow praktycznego wyjasnienia.
- `key_points` - 3-5 punktow.
- `published_at` i `last_reviewed_at` - data aktualnego wpisu.

Styl:

- pisz prosto, dla kandydata na kierowce,
- rozdzielaj limit prawny od praktycznego zachowania,
- wskazuj pulapki egzaminacyjne,
- nie tworz porady prawnej ani komentarza prawniczego,
- nie cytuj dlugich fragmentow aktu prawnego.

### 5. Podepnij jednostki prawne do strony

W `run()` dodaj:

```php
$this->attachUnits($pages['pojazd-uprzywilejowany'], [$units['art-9']]);
```

Jesli temat ma kilka podstaw, ustaw je w kolejnosci od najwazniejszej.

### 6. Dodaj powiazania z pytaniami

Powiazania dodawaj tylko wtedy, gdy relacja jest realna i zweryfikowana.

Wzor:

```php
$this->attachQuestionReferences([
    '1234' => [
        'page' => $pages['pojazd-uprzywilejowany'],
        'unit' => $units['art-9'],
        'topic' => $topics['pojazd-uprzywilejowany'],
        'note' => 'Krotka publiczna notatka: co dokladnie sprawdza pytanie.',
    ],
], $reviewer, $batchReviewedAt);
```

`attachQuestionReferences()` samo szuka zarowno `external_id = 1234`, jak i `pj360:1234`.

Nie dodawaj pytan tylko dlatego, ze maja podobne slowo w promptcie. Pytanie musi wynikac z danej jednostki albo bezposrednio ja sprawdzac.

### 7. Zaktualizuj testy

W `tests/Feature/Public/LegalTrustLayerMvpTest.php` dopisz:

- asercje, ze hub `/przepisy` widzi nowy tytul,
- request detail page `/przepisy/{slug}`,
- asercje podstaw prawnych, daty review i najwazniejszej frazy z body,
- jesli dodajesz powiazania pytan: test bloku `Uzasadnienie prawne` dla jednego pytania,
- asercje, ze sitemap zawiera nowy URL.

### 8. Zaktualizuj dokumenty

Obowiazkowo:

- dopisz nowy wiersz w `Rejestr opublikowanych tematow` w tym pliku.
- w `resources/legal-content/article-topic-candidates.php` ustaw `existing_article_slug` przy opublikowanym kandydacie i odswiez mape.

Opcjonalnie, ale wskazane przy zmianie statusu:

- zaktualizuj `docs/LEGAL-TRUST-LAYER-PLAN.md`, jesli zmienia sie liczba tematow, wynik testow, status wdrozenia albo zakres.

## Komendy weryfikacyjne

Uzywaj Dockera, bo lokalne `php` moze nie byc w PATH.

```powershell
docker compose exec -T app php -l database/seeders/LegalTrustLayerMvpSeeder.php
docker compose exec -T app php artisan test tests/Feature/Public/LegalTrustLayerMvpTest.php
docker compose exec -T app php artisan test tests/Feature/Public/SeoSitemapGenerationTest.php
docker compose exec -T app php artisan test --filter=PublicQuestionDatabasePageTest
git diff --check
```

Po zielonych testach zasiej lokalna baze i sprawdz URL:

```powershell
docker compose exec -T app php artisan db:seed --class=LegalTrustLayerMvpSeeder
(Invoke-WebRequest -Uri http://localhost:8000/przepisy/TWOJ-SLUG -UseBasicParsing).StatusCode
```

Oczekiwany status: `200`.

## Definition of Done

Nowy wpis jest gotowy, gdy:

- ma oficjalne zrodlo i date weryfikacji,
- ma autora i reviewera przez istniejacy mechanizm seedera,
- jest opublikowany jako `LegalTopic` i `LegalContentPage`,
- ma podlaczone `LegalUnit` i `LegalSourceCheck`,
- ma powiazania z pytaniami tylko tam, gdzie sa zweryfikowane,
- kandydat i jego pytania zostaly przejrzane w pelnej mapie, a falszywe dopasowania odrzucone,
- nie zmienia `questions.explanation`, odpowiedzi, mediow ani logiki nauki,
- przechodza testy publiczne i sitemap,
- lokalny URL zwraca `200`,
- nowy temat jest dopisany w rejestrze tego dokumentu.

## Notatka po wpisie z 13.06.2026

Przy wpisie `predkosc-odstep-i-hamowanie` wazna byla pulapka zrodlowa: tekst ogloszony Prawa o ruchu drogowym moze pokazywac historyczne wartosci predkosci. Dla aktualnych limitow uzyto tekstu ujednoliconego ISAP/ELI i dlatego w tresci sa m.in. `50 km/h` w obszarze zabudowanym oraz `140 km/h` na autostradzie.

Przy wpisie `zmiana-kierunku-i-pasa-ruchu` workflow pokazal dwa usprawnienia: przy wielu wpisach z jednej daty warto uzywac wspolnej nazwy partii dat, a przy szukaniu w PowerShell lepiej stosowac `rg --glob` zamiast wildcarda w sciezce.

Przy wpisie `wlaczanie-sie-do-ruchu` wazne bylo odciecie tematu autobusow z art. 18 od ogolnego art. 17. Pytania o umozliwienie autobusowi wyjazdu z przystanku zostaw na osobny temat `autobus-i-autobus-szkolny`, chyba ze brief wyraznie laczy oba zakresy.

Przy wpisie `wymijanie-omijanie-cofanie` wazne bylo odciecie art. 23 od art. 24. Pytania o `minimum 1 m` czesto dotycza wyprzedzania, nie wymijania lub omijania, wiec nie dopinaj ich automatycznie tylko po slowie `odstep`.

Przy wpisie `wyprzedzanie` nie dopinano pytan zaleznych glownie od znakow poziomych lub pionowych, np. podwojnej linii ciaglej albo znaku zakazu wyprzedzania. Takie pytania powinny trafiac do osobnych tematow znakow albo miec dodatkowa podstawe, a nie tylko art. 24.

Przy ujednoliceniu starszych wpisow z 03.06.2026 dopisano brakujace pole `body` do: `zatrzymanie-i-postoj`, `tramwaje-i-przystanki`, `piesi-i-przejscia`, `pierwszenstwo-przejazdu`, `sygnalizacja-i-osoby-kierujace-ruchem`. To jest dolna sekcja strony po bloku `Najwazniejsze punkty`; kolejne wpisy powinny miec ja od razu.

Przy przejsciu `tramwaje-i-przystanki` przez workflow question-first 15.06.2026 doprecyzowano podstawe dla pytan `99` i `10314` z szerokiego `art. 26` na `art. 26 ust. 6`. Seeder usuwa stare relacje tych pytan do `art. 26`, zeby publiczny blok `Uzasadnienie prawne` nie pokazywal dwoch podstaw naraz.

Przy przejsciu `piesi-i-przejscia` przez workflow question-first 19.06.2026 sprawdzono prompt, odpowiedzi, wyjasnienia i nagrania pytan `10249`, `10369`, `10474`. Pytania `10249` i `10474` przepieto na `art. 26 ust. 1`, a pytanie `10369` na `art. 26 ust. 4`. Art. 13 zostaje podstawa szerszego artykulu o zachowaniu pieszego, ale nie jest bezposrednia podstawa tych trzech pytan o obowiazki kierujacego.

Przy wpisie `autobus-wyjezdzajacy-z-przystanku` 19.06.2026 po raz pierwszy wykorzystano pelna mape kandydatow zamiast ponownego skanowania bazy. Klaster 9 pytan okazal sie spojny: 8 pytan dotyczy obowiazku na obszarze zabudowanym, a `11123` sprawdza brak tego szczegolnego obowiazku poza obszarem zabudowanym. Artykul i pytania wskazuja precyzyjny art. 18 ust. 1, a strona dodatkowo pokazuje art. 18 ust. 2, aby nie sugerowac autobusowi bezwzglednego prawa do wjazdu.

Przy wpisie `zawracanie` 20.06.2026 wykorzystano dossier 62 pytan kanonicznych i rozdzielono relacje na ustawowe zakazy miejsca, znaki B-21/B-22/B-23, strzalki P-8, sygnalizatory S-3 oraz pierwszenstwo pojazdu szynowego. Zweryfikowano i opublikowano 45 relacji. Pozostalych 17 pytan nie przypisano automatycznie, poniewaz ich prawidlowa kwalifikacja zalezy od elementow obrazu lub nagrania niewynikajacych jednoznacznie z promptu i wyjasnienia.

Przy modernizacji `predkosc-odstep-i-hamowanie` 20.06.2026 szerokie relacje do art. 19 i art. 20 zastapiono precyzyjnymi jednostkami dla osmiu opublikowanych pytan. Szczegolnie wazne bylo rozdzielenie ogolnego bezpiecznego odstepu z art. 19 ust. 2 pkt 3 od matematycznej reguly polowy predkosci z art. 19 ust. 3a oraz dopowiedzenie, ze dostosowanie predkosci do widocznosci nigdy nie zezwala na przekroczenie limitu.

Przy wpisie `sygnal-dzwiekowy` 20.06.2026 kluczowe bylo oddzielenie prawa do ostrzegania o realnym niebezpieczenstwie od zakazu ponaglania innych uczestnikow. Artykul uwzglednia tez mniej oczywisty art. 30 ust. 1 pkt 1 lit. b, czyli obowiazek krotkich sygnalow podczas wyprzedzania lub omijania we mgle poza obszarem zabudowanym, oraz techniczny wymog ciaglego, nieprzerazliwego tonu. Pytania o pojazd uprzywilejowany i niejednoznaczne sytuacje tramwajowe pozostawiono poza relacjami.

Przy wpisach o ladunku i przyczepie 21.06.2026 rozdzielono wspolny klaster na dwie odpowiedzialnosci: art. 61 opisuje ladunek, jego zabezpieczenie, wymiary i oznakowanie, a art. 62 parametry zespolu pojazdow. Pytania o technike jazdy, cofanie, przewoz osob i historycznie watpliwe wymagania tablicy rejestracyjnej nie zostaly opublikowane jako relacje prawne.

Przy wpisach o przejezdzie kolejowym i holowaniu 21.06.2026 zastosowano ten sam filtr. W przejezdzie rozdzielono art. 28 od znaku B-20 i czerwonego sygnalu, a w holowaniu odseparowano ustawowe parametry i zakazy od pytan tramwajowych oraz porad technicznych niewynikajacych wprost z art. 31.

Przy pasach i swiatlach 21.06.2026 zachowano waski zakres ustawowy. Pasy oparto na obowiazku i wyjatkach z art. 39 bez przejmowania pytan o foteliki, a swiatla dzienne ograniczono do art. 51 ust. 1-2 oraz pogorszonej przejrzystosci z art. 30.

Przy wpisach `alkohol-i-srodki-dzialajace-podobnie` oraz `zielona-strzalka-warunkowa` 22.06.2026 rozdzielono normy ruchu od definicji progow i odpowiedzialnosci. W temacie alkoholu z 15 pytan klastra opublikowano 8 relacji do art. 45 Prawa o ruchu drogowym, art. 46 ustawy o wychowaniu w trzezwosci i art. 87 Kodeksu wykroczen; pytania o skutki psychofizyczne pozostaly bez relacji. W zielonej strzalce wszystkie 4 pytania wskazuja precyzyjny par. 96 ust. 3, bo sprawdzaja obowiazek zatrzymania przed sygnalizatorem.

Przy wpisach `pojazd-uprzywilejowany` oraz `sygnal-zolty-i-zolty-migajacy` 22.06.2026 wszystkie 10 pytan kandydackich otrzymalo zweryfikowane relacje. Dla korytarza zycia wykorzystano art. 9 ust. 2, a pytania o wyprzedzanie rozdzielono miedzy art. 24 ust. 11 i znaczenie D-43 z par. 58 ust. 3. Dla zoltego migajacego wszystkie pytania wskazuja par. 98 ust. 6; staly zolty i czerwony z zoltym sa wyjasnione na stronie, ale nie sa bezposrednia podstawa tych czterech pytan.

Kolejny agent powinien zachowac ten sam standard: najpierw aktualne oficjalne zrodlo, potem tresc, potem powiazania, testy i dopisanie tematu do rejestru.
