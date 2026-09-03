# Legal Trust Layer Plan

Status: koncepcja wykonawcza  
Data: 2026-06-02  
Zakres: `przepisy`, `kodeks drogowy`, podstawy prawne przy pytaniach i znakach

## Status developmentu

Ostatnia aktualizacja: 2026-06-20
Branch roboczy: `codex/legal-article-zawracanie`

Aktualny stan:

- rozpoczęto implementację MVP warstwy prawnej na osobnym branchu,
- dodano fundament danych dla aktów prawnych, jednostek prawnych, tematów, stron prawnych, powiązań z pytaniami, powiązań ze znakami oraz audytu źródeł,
- dodano modele Eloquent dla nowych tabel i relacje z `Question`, `TrafficSign` oraz `ContentAuthor`,
- dodano publiczny hub `/przepisy`,
- dodano publiczną stronę szczegółową `/przepisy/{slug}`,
- dodano publiczną metodologię `/metodologia/przepisy-i-podstawy-prawne`,
- dodano blok `Uzasadnienie prawne` na publicznej stronie pytania, renderowany wyłącznie dla zweryfikowanych relacji,
- dodano sitemapę `/sitemaps/legal-content.xml` oraz wpis w głównym indeksie sitemap,
- rozszerzono publiczny profil autora tak, żeby autor/reviewer treści prawnych był dostępny także wtedy, gdy nie ma przypisanych opublikowanych znaków drogowych,
- ustawiono imienny profil reviewera MVP: `Jakub Wiśniewski` (`/autorzy/jakub-wisniewski`),
- dodano przycięte zdjęcie profilowe reviewera jako publiczny asset `public/images/authors/jakub-wisniewski.png`,
- zastąpiono bezosobową `Redakcję BRD` imiennym profilem autorki znaków i warstwy BRD: `Katarzyna Wiśniewska` (`/autorzy/katarzyna-wisniewska`), z redirectem 301 ze starego `/autorzy/redakcja-brd`,
- dodano przycięte zdjęcie profilowe autorki znaków jako publiczny asset `public/images/authors/katarzyna-wisniewska.png`,
- dodano idempotentny seeder `LegalTrustLayerMvpSeeder` z 12 tematami i powiązaniami pytań,
- rozszerzono dział o temat `predkosc-odstep-i-hamowanie` oparty o art. 19-21 Prawa o ruchu drogowym,
- rozszerzono dział o temat `zmiana-kierunku-i-pasa-ruchu` oparty o art. 22 Prawa o ruchu drogowym,
- rozszerzono dział o temat `wlaczanie-sie-do-ruchu` oparty o art. 17 Prawa o ruchu drogowym,
- rozszerzono dział o temat `wymijanie-omijanie-cofanie` oparty o art. 23 Prawa o ruchu drogowym,
- rozszerzono dział o temat `wyprzedzanie` oparty o art. 24 Prawa o ruchu drogowym,
- rozwinieto dolna sekcje `body` dla pierwszych 5 stron prawnych z MVP, z aktualnym review 13.06.2026,
- dopisano workflow `question-first`, który łączy artykuły prawne z konkretnymi pytaniami przez `QuestionLegalReference.public_note`,
- utworzono audyt `docs/LEGAL-CONTENT-QUESTION-FIRST-AUDIT.md` dla dopracowania obecnych 10 artykułów od strony pytań i podstaw prawnych,
- wykonano pierwszy etap workflow `question-first`: pytania `99` i `10314` w temacie `tramwaje-i-przystanki` wskazują precyzyjne `art. 26 ust. 6`, a seeder usuwa ich starą szeroką relację do `art. 26`,
- wykonano drugi etap workflow `question-first`: pytania `10249` i `10474` w temacie `piesi-i-przejscia` wskazują `art. 26 ust. 1`, pytanie `10369` wskazuje `art. 26 ust. 4`, a seeder usuwa ich stare relacje do szerokiego `art. 26`,
- wykonano trzeci etap workflow `question-first`: osiem pytan w temacie `predkosc-odstep-i-hamowanie` rozdzielono miedzy precyzyjne jednostki art. 19 i 20, przebudowano tresc strony, a seeder usuwa stare relacje do szerokich art. 19 i 20,
- zakonczono audyt question-first wszystkich 10 pierwotnych artykulow: pozostale tematy rozbito na dokladne jednostki art. 17, 22-25 i 49 oraz § 36, § 95 i § 108 rozporzadzenia w sprawie znakow i sygnalow drogowych; wszystkie strony maja review 20.06.2026, unikalne notatki i czyszczenie starych szerokich relacji,
- przeskanowano 17 044 aktywne rekordy pytań bez analizy mediów, zgrupowano 3 570 pytań kanonicznych w 31 filarach i 56 węższych kandydatach oraz zapisano wewnętrzną mapę temat -> pytania,
- dodano `LegalArticleTopicCandidate`, relacje planistyczne z pytaniami i komendę `legal-content:sync-article-topic-candidates`; ta warstwa nie publikuje automatycznie `QuestionLegalReference`,
- dodano raport `docs/LEGAL-ARTICLE-TOPIC-CANDIDATES.md` i pełną mapę `resources/legal-content/generated/article-topic-question-map.json`, aby kolejne etapy nie skanowały całej bazy od zera,
- opublikowano pierwszy artykuł wybrany z mapy kandydatów: `autobus-wyjezdzajacy-z-przystanku`, oparty o art. 18 ust. 1-2 i powiązany z 9 pytaniami kanonicznymi,
- opublikowano artykuł `zawracanie` wybrany z `QUEUE.md`, oparty o art. 22 ust. 6 pkt 1-4, art. 25 ust. 2 oraz przepisy o B-21/B-23, P-8 i S-3; z 62 pytań kanonicznych 45 otrzymało zweryfikowane relacje, a 17 pozostawiono do analizy mediów,
- dodano agent workspace z kolejką tematów i generowanym dossier pojedynczego klastra; agent otrzymuje prompty, odpowiedzi, media, konflikty danych i stan zweryfikowanych relacji bez ponownego skanowania bazy,
- utworzono `docs/LEGAL-CONTENT-SCALING-PLAN.md` z progami uruchamiania kolejnych generacji discovery, planem odejścia od monolitycznego seedera, lifecycle tematów, utrzymaniem aktualności prawa i roadmapą panelu operacyjnego,
- dodano testy publiczne `LegalTrustLayerMvpTest`,
- ujednolicono publiczne schema `/przepisy`, `/przepisy/{slug}` i `/metodologia/przepisy-i-podstawy-prawne` do jednego JSON-LD `@graph`; szczegóły w `docs/LEGAL-CONTENT-ENTERPRISE-SCHEMA-PLAN.md`,
- nowe dane są osobną warstwą publiczną / SEO / zaufania i nie modyfikują `questions.explanation`, odpowiedzi, mediów, playera ani logiki `/nauka`,
- lokalnie wykonano migrację i seedera na Dockerze oraz sprawdzono `/przepisy`, `/przepisy/tramwaje-i-przystanki`, `/przepisy/predkosc-odstep-i-hamowanie`, `/przepisy/zmiana-kierunku-i-pasa-ruchu`, `/przepisy/wlaczanie-sie-do-ruchu`, `/przepisy/wymijanie-omijanie-cofanie`, `/przepisy/wyprzedzanie` i `/pytanie/99/...`.

Wynik testów po aktualnej implementacji:

- `php artisan test tests/Feature/Public/LegalTrustLayerMvpTest.php` - OK, 16 testów / 283 asercje,
- `php artisan test tests/Feature/PublicQuestionDatabasePageTest.php` - OK, 15 testów / 382 asercji,
- `php artisan test tests/Feature/Public/TrafficSignSeoInfrastructureTest.php` - OK, 10 testów / 171 asercji.

Aktualna weryfikacja po etapach `tramwaje-i-przystanki` oraz `piesi-i-przejscia`:

- `php -l database/seeders/LegalTrustLayerMvpSeeder.php` - OK,
- `php artisan test tests/Feature/Public/LegalTrustLayerMvpTest.php` - OK, 16 testów / 283 asercje,
- `php artisan test tests/Feature/PublicQuestionDatabasePageTest.php` - OK, 15 testów / 382 asercji.

Co jest jeszcze do zrobienia przed produkcyjnym deployem:

- przejrzeć finalnie copy 10 stron prawnych pod kątem tonu i precyzji,
- uruchomić pełniejszy smoke test SEO/public po ewentualnym commicie,
- przy deployu wykonać `php artisan migrate --force`, potem `php artisan db:seed --class=LegalTrustLayerMvpSeeder --force`, potem odświeżyć cache i sitemapę.

Zasada bezpieczeństwa na czas developmentu:

- publicznie renderujemy wyłącznie rekordy o statusie `verified` / `published`,
- dane prawne pobieramy oddzielnymi relacjami,
- nie używamy tych pól w module nauki ani egzaminu,
- pierwsze treści MVP są krótkimi opracowaniami edukacyjnymi z oficjalnymi źródłami i datą weryfikacji.

## 0. Decyzje MVP

Ustalenia zaakceptowane przed developmentem:

- glowny publiczny hub startuje pod `/przepisy`,
- MVP obejmuje od razu model danych oraz pierwsze `5` stron prawnych,
- MVP obejmuje `5` tematow prawnych,
- po pierwszym MVP zestaw rozszerzono do `10` stron / tematow o `predkosc-odstep-i-hamowanie`, `zmiana-kierunku-i-pasa-ruchu`, `wlaczanie-sie-do-ruchu`, `wymijanie-omijanie-cofanie` oraz `wyprzedzanie`,
- blok `Uzasadnienie prawne` na stronach pytan startuje od `5-10` pytan,
- tworzymy prawdziwy profil autora i reviewera, zamiast pokazywac wylacznie bezosobowa role redakcyjna,
- `/kodeks-drogowy` zostaje jako mozliwy pozniejszy indeks albo alias po osobnej decyzji canonical/redirect.

### 0.1 Proponowane tematy MVP

Pierwsze `5` tematow powinno dac dobry przekroj po pytaniach, znakach i typowych intencjach egzaminacyjnych:

1. `zatrzymanie-i-postoj` - zatrzymanie pojazdu, postoj, sytuacje wymagajace zatrzymania.
2. `tramwaje-i-przystanki` - bezpieczenstwo pasazerow, przystanki, zachowanie przy tramwaju.
3. `piesi-i-przejscia` - przejscia dla pieszych, pierwszenstwo pieszych, szczegolna ostroznosc.
4. `pierwszenstwo-przejazdu` - skrzyzowania, znaki pierwszenstwa, sytuacje kolizyjne.
5. `sygnalizacja-i-osoby-kierujace-ruchem` - sygnaly swietlne, polecenia policjanta i osob uprawnionych.

## 1. Cel

Ten dokument opisuje kolejna warstwe zaufania dla publicznej czesci serwisu:

- dzial `Przepisy` / `Kodeks drogowy`,
- podstawy prawne przy publicznych stronach pytan,
- powiazania miedzy pytaniami, znakami drogowymi i przepisami,
- jawne zrodla, daty weryfikacji oraz role autor / reviewer.

To nie ma byc doklejona sekcja SEO. To ma byc osobny, utrzymywalny system tresci prawno-edukacyjnych, ktory wzmacnia publiczne pytania i znaki.

## 2. Dlaczego to ma sens

Warstwa prawna moze podniesc:

- zaufanie uzytkownika, bo pokazujemy skad wynika odpowiedz,
- E-E-A-T, bo mamy jawne zrodla i proces weryfikacji,
- SEO, bo budujemy topical authority wokol `pytania -> przepis -> znak -> temat`,
- internal linking, bo pytania i znaki przestaja byc izolowanymi kartami,
- stabilnosc contentu, bo zmiany prawne moga byc obslugiwane w jednym miejscu.

Docelowy graf:

```text
Pytanie egzaminacyjne
  -> Uzasadnienie prawne
  -> Artykul / paragraf
  -> Strona kodeksowa
  -> Powiazane znaki
  -> Powiazane pytania
```

## 3. Aktualny stan

Na dzien spisania dokumentu:

- `/przepisy` istnieje w menu, ale jest placeholderem,
- roadmapa SEO ma zaplanowany `Etap 3: Kodeks drogowy`,
- strony znakow maja juz pola i tresci typu `legal_summary`, `legal_reference_label`, `legal_reference_url`,
- publiczne strony pytan nie maja jeszcze osobnej warstwy danych na podstawy prawne,
- tabela `questions` ma `source`, `explanation` i `metadata`, ale nie ma pol typu `legal_basis` / `legal_reference`,
- widok pytania ma naturalne miejsce na nowy blok: po `Wyjasnienie`, przed `Zakres kategorii`.

## 4. Zasady redakcyjne

### 4.1 Oficjalne zrodla

Podstawy prawne publikujemy tylko na podstawie zrodel oficjalnych lub pierwotnych:

- ISAP,
- ELI / Dziennik Ustaw,
- gov.pl,
- oficjalne teksty ustaw i rozporzadzen.

Serwisy konkurencyjne moga byc uzyte tylko jako benchmark UX albo trop do audytu, nigdy jako finalne zrodlo prawne.

### 4.2 Zdamyto jako benchmark, nie zrodlo

Zdamyto pokazuje przy pytaniach podstawy prawne, wiec mozna analizowac:

- gdzie umieszczaja blok prawny,
- jak lacza pytanie z podstawa prawna,
- jakich typow danych uzywaja.

Nie kopiujemy jednak:

- tresci opisow,
- uzasadnien,
- interpretacji,
- list podstaw prawnych bez naszej weryfikacji.

Jesli podstawa prawna znaleziona u konkurencji wydaje sie trafna, traktujemy ja jako sygnal do recznego sprawdzenia w oficjalnym akcie prawnym.

### 4.3 Brak falszywej precyzji

Nie dopisujemy artykulu lub paragrafu, jesli nie mamy pewnosci.

Dopuszczalne statusy redakcyjne:

- `draft` - kandydat do weryfikacji,
- `verified` - sprawdzone z oficjalnym zrodlem,
- `needs_review` - wymaga ponownej kontroli,
- `rejected` - nie publikowac.

Publicznie renderujemy tylko rekordy `verified`.

### 4.4 Standard E-E-A-T dla warstwy prawnej

Dzial `Przepisy` i bloki `Uzasadnienie prawne` powinny byc projektowane jako jedna z glownych warstw E-E-A-T calego serwisu.

#### Experience

Pokazujemy praktyczny kontekst egzaminacyjny:

- jak przepis pojawia sie w pytaniach,
- na co uwazac w sytuacji egzaminacyjnej,
- ktore znaki sa powiazane z danym przepisem,
- ktore pytania sprawdzaja dana zasade.

Kazda strona prawna powinna odpowiadac na pytanie: `jak ten przepis pomaga zdac egzamin albo zrozumiec sytuacje drogowa?`

#### Expertise

Kazda publiczna strona prawna powinna miec widocznie:

- autora opracowania,
- reviewera albo role osoby weryfikujacej,
- date publikacji,
- date ostatniej weryfikacji prawnej,
- status aktualnosci.

Minimalny fallback tekstowy:

```text
Opracowanie: Zespol prawkonaraz.pl
Weryfikacja merytoryczna: redaktor ds. przepisow drogowych
Ostatnia weryfikacja podstawy prawnej: 2026-06-02
```

W zaakceptowanym MVP tworzymy jednak prawdziwy profil autora i prawdziwy profil reviewera. Fallback bezosobowy jest dopuszczalny tylko jako awaryjny placeholder techniczny w srodowisku developerskim, nie jako finalny stan produkcyjny.

#### Authoritativeness

Autorytet budujemy przez strukture, a nie przez pojedynczy akapit:

- osobny dzial `/przepisy`,
- strony konkretnych zagadnien,
- powiazania z publicznymi pytaniami,
- powiazania ze znakami drogowymi,
- powiazania z oficjalnymi aktami prawnymi,
- spójne breadcrumbs, canonicale i sitemap.

Strona przepisu powinna byc wezlem grafu wiedzy, nie samotnym artykulem.

#### Trust

Najwazniejsza warstwa zaufania:

- oficjalne zrodla: ISAP, ELI, Dziennik Ustaw, gov.pl,
- konkretna jednostka prawna, jesli jest znana i zweryfikowana,
- data ostatniej weryfikacji,
- jasne rozroznienie miedzy `wyjasnieniem edukacyjnym` a `tekstem aktu prawnego`,
- brak kopiowania konkurencji,
- brak publikacji niezweryfikowanych podstaw prawnych,
- brak falszywej precyzji.

### 4.5 Strona metodologii prawnej

Dla tej warstwy powinna powstac osobna strona metodologii, np.:

```text
/metodologia/przepisy-i-podstawy-prawne
```

Rola strony:

- wyjasnia, skad bierzemy przepisy,
- pokazuje, jak upraszczamy tekst prawny na jezyk edukacyjny,
- opisuje proces review,
- wyjasnia, jak czesto robimy przeglad,
- pokazuje, co robimy po zmianie prawa,
- odroznia material edukacyjny od porady prawnej.

Minimalna tresc:

```text
Materialy prawne w prawkonaraz.pl maja charakter edukacyjny i pomagaja zrozumiec pytania egzaminacyjne. Nie stanowia indywidualnej porady prawnej. Podstawy prawne weryfikujemy na podstawie oficjalnych zrodel, takich jak ISAP, ELI, Dziennik Ustaw i gov.pl.
```

### 4.6 Checklist publikacji prawnej

Przed publikacja kazdej strony prawnej lub bloku `Uzasadnienie prawne` trzeba sprawdzic:

- czy jest oficjalne zrodlo,
- czy link do zrodla dziala,
- czy wskazano konkretna jednostke prawna albo uczciwie opisano tylko temat prawny,
- czy jest data weryfikacji,
- czy jest autor i reviewer,
- czy nie ma kopiowania tresci z konkurencji,
- czy nie ma niepewnych twierdzen przedstawionych jako fakt,
- czy strona ma powiazane pytania lub znaki,
- czy schema odpowiada temu, co widac publicznie na stronie,
- czy nowe dane nie zmieniaja `/nauka`, odpowiedzi ani playera.

### 4.7 Minimalny pakiet E-E-A-T dla MVP

Pierwsza wersja warstwy prawnej nie musi miec pelnego systemu, ale musi miec minimum zaufania.

MVP E-E-A-T:

1. `/przepisy` jako publiczny hub.
2. Strona metodologii prawnej.
3. Widoczny prawdziwy profil autora/reviewera i data weryfikacji.
4. Oficjalne zrodla przy kazdej podstawie prawnej.
5. Publiczny blok `Uzasadnienie prawne` tylko przy pytaniach `verified`.
6. Linkowanie `pytanie -> przepis -> powiazane pytania / znaki`.

## 5. Decyzje URL

Aktualnie w menu istnieje etykieta `Przepisy` i URL `/przepisy`.

Docelowo trzeba uniknac duplikatow typu:

- `/przepisy`
- `/kodeks-drogowy`
- `/prawo-o-ruchu-drogowym`

Rekomendacja na start:

- `/przepisy` jako publiczny hub w menu,
- `/kodeks-drogowy` jako mozliwy dokladny indeks kodeksowy po analizie keywordow,
- nie publikowac dwoch stron o tej samej roli bez canonical/redirect decyzji,
- przed implementacja ustalic jedna kanoniczna sciezke dla indeksu prawnego.

## 6. Docelowe typy stron

### 6.1 Hub przepisow

Przyklad:

- `/przepisy`

Rola:

- wejscie z menu,
- podzial na obszary: kodeks, znaki, pierwszenstwo, piesi, tramwaje, predkosc, zatrzymanie/postoj,
- linkowanie do najwazniejszych stron prawnych i pytan.

### 6.1.1 Koncepcja zakladki `Przepisy`

Zakladka w glownym menu powinna zostac nazwana:

```text
Przepisy
```

Rekomendowany URL pierwszego ekranu:

```text
/przepisy
```

To nie powinien byc blog ani klasyczna strona marketingowa. To powinien byc praktyczny indeks prawa do egzaminu: narzedzie referencyjne, ktore pozwala szybko przejsc do tematu, przepisu, pytania lub znaku.

#### Pierwszy ekran

Cel pierwszego ekranu:

- od razu wyjasnic, ze uzytkownik jest w dziale przepisow do egzaminu,
- pokazac zaufanie przez oficjalne zrodla,
- dac szybka wyszukiwarke,
- pokazac najwazniejsze tematy.

Proponowana tresc:

```text
Przepisy drogowe do egzaminu na prawo jazdy

Najwazniejsze zasady, artykuly i podstawy prawne wyjasnione prostym jezykiem. Sprawdz, z ktorych przepisow wynikaja pytania egzaminacyjne i znaki drogowe.
```

Pod spodem:

```text
Szukaj przepisu, znaku albo tematu...
```

#### Glowne sekcje huba

1. `Najwazniejsze tematy`

   Przykladowe kafle:

   - Pierwszenstwo przejazdu
   - Piesi i przejscia dla pieszych
   - Tramwaje i przystanki
   - Znaki drogowe
   - Predkosc
   - Zatrzymanie i postoj
   - Osoby kierujace ruchem
   - Sygnalizacja swietlna

2. `Kodeks drogowy`

   Rola:

   - uporzadkowany indeks aktow i najwazniejszych jednostek prawnych,
   - linki do Prawa o ruchu drogowym,
   - linki do rozporzadzenia o znakach i sygnalach drogowych,
   - linki do przepisow o egzaminowaniu kandydatow.

3. `Przepisy powiazane z pytaniami`

   Rola:

   - pokazac, ze publiczna baza pytan ma warstwe prawna,
   - prowadzic do pytan, ktore wynikaja z danego przepisu,
   - wzmacniac strony pytan przez kontekst prawny.

   Przyklad:

   ```text
   Obowiazek zatrzymania pojazdu
   Powiazane pytania: 99, 100, 2137...
   Powiazane tematy: tramwaje, bezpieczenstwo pasazerow
   ```

4. `Przepisy powiazane ze znakami`

   Rola:

   - spiac dzial `Przepisy` z istniejacym klastrem `Znaki drogowe`,
   - pokazac podstawe prawna danego znaku,
   - przeprowadzac uzytkownika miedzy znakiem, pytaniami i przepisem.

   Przyklad:

   ```text
   Znak B-20 STOP
   Podstawa prawna: rozporzadzenie w sprawie znakow i sygnalow drogowych
   Powiazane pytania egzaminacyjne...
   ```

5. `Jak weryfikujemy przepisy`

   Krotka sekcja zaufania:

   ```text
   Korzystamy z oficjalnych zrodel: ISAP, ELI, Dziennik Ustaw i gov.pl. Kazda publiczna podstawa prawna ma date ostatniej weryfikacji.
   ```

#### Zasady UX

- unikac layoutu blogowego z przypadkowymi artykulami,
- nie robic wielkiego marketingowego hero,
- traktowac strone jak indeks i narzedzie do znalezienia odpowiedzi,
- kazdy kafel powinien prowadzic do realnej strony tematu lub przepisu,
- widocznie odroznic tresci zweryfikowane od planowanych,
- nie obiecywac kompletnego komentarza prawnego, jesli mamy tylko edukacyjne wyjasnienie pod egzamin.

#### Strona szczegolowa przepisu

Przyklad URL:

```text
/przepisy/zatrzymanie-pojazdu-przy-tramwaju
```

Proponowany uklad:

```text
Zatrzymanie pojazdu przy tramwaju

Krotko:
Kierujacy musi zachowac sie tak, aby zapewnic bezpieczenstwo pasazerom wsiadajacym lub wysiadajacym z tramwaju.

W praktyce na egzaminie:
Ten przepis pojawia sie w pytaniach, w ktorych trzeba ocenic, czy nalezy zatrzymac pojazd przed wyznaczona linia albo przed miejscem wysiadania pasazerow.

Podstawa prawna:
Prawo o ruchu drogowym, art. ...

Zrodlo:
Oficjalny tekst aktu prawnego

Ostatnia weryfikacja:
2026-06-02

Powiazane pytania:
- Pytanie 99
- Pytanie 100

Powiazane znaki:
- jesli dotyczy
```

#### Blok na publicznej stronie pytania

Na stronie pytania, np. `/pytanie/99/...`, pokazujemy tylko zweryfikowane powiazanie:

```text
Uzasadnienie prawne

To pytanie dotyczy obowiazku zatrzymania pojazdu w sytuacji zwiazanej z bezpieczenstwem pasazerow komunikacji zbiorowej.

Zobacz podstawe prawna:
Zatrzymanie pojazdu przy tramwaju
```

Taki blok ma byc krotki i pomocniczy. Pelniejsze wyjasnienie prawne znajduje sie na stronie przepisu.

### 6.2 Indeks kodeksowy

Przyklad:

- `/kodeks-drogowy`

Rola:

- strona referencyjna,
- wyszukiwarka lub indeks dzialow,
- lista najwazniejszych artykulow/paragrafow w kontekście egzaminu.

### 6.3 Strona przepisu / zagadnienia prawnego

Przyklady:

- `/przepisy/pierwszenstwo-przejazdu`
- `/przepisy/zatrzymanie-i-postoj`
- `/kodeks-drogowy/art-5-polecenia-osob-kierujacych-ruchem`

Rola:

- tlumaczy przepis prostym jezykiem,
- pokazuje oficjalne zrodlo,
- wskazuje powiazane pytania,
- wskazuje powiazane znaki,
- ma date ostatniej weryfikacji.

### 6.4 Blok prawny na stronie pytania

Miejsce:

- po sekcji `Wyjasnienie`,
- przed sekcja `Zakres kategorii`.

Przyklad:

```text
Uzasadnienie prawne

To pytanie dotyczy obowiazku zachowania sie kierujacego w sytuacji, w ktorej trzeba zapewnic bezpieczenstwo innym uczestnikom ruchu.

Podstawa prawna:
Prawo o ruchu drogowym, art. ...

Zrodlo:
Oficjalny tekst aktu prawnego

Ostatnia weryfikacja:
2026-06-02
```

Wazne:

- nie edytujemy istniejacego `explanation`,
- nie dotykamy tresci uzywanych w `/nauka`,
- sekcja prawna jest osobna warstwa danych,
- nie pokazujemy niezweryfikowanych podstaw prawnych.

### 6.5 Blok prawny na stronie znaku

Strony znakow juz maja fundament prawny. Docelowo powinny linkowac do tych samych rekordow prawnych co pytania.

Przyklad grafu:

```text
Znak A-7
  -> strona znaku
  -> podstawa prawna znaku
  -> powiazane pytania o ustapienie pierwszenstwa
  -> strona przepisu o pierwszenstwie
```

## 7. Proponowany model danych

### 7.1 `legal_acts`

Akt prawny jako calosc.

Pola:

- `id`
- `slug`
- `title`
- `short_title`
- `publisher`
- `source_url`
- `eli_url`
- `isap_url`
- `effective_from`
- `last_checked_at`
- `status`
- `created_at`
- `updated_at`

### 7.2 `legal_units`

Konkretny artykul, paragraf, ustep lub punkt.

Pola:

- `id`
- `legal_act_id`
- `type` - `article`, `paragraph`, `section`, `point`
- `label` - np. `art. 5`, `par. 27`
- `slug`
- `title`
- `summary`
- `official_excerpt`
- `source_url`
- `last_checked_at`
- `status`
- `created_at`
- `updated_at`

### 7.3 `legal_topics`

Warstwa tematyczna laczaca przepisy z pytaniami i znakami.

Pola:

- `id`
- `slug`
- `title`
- `description`
- `status`

Przyklady:

- `pierwszenstwo-przejazdu`
- `piesi`
- `tramwaje`
- `zatrzymanie-i-postoj`
- `predkosc`
- `znaki-zakazu`
- `osoby-kierujace-ruchem`

### 7.4 `question_legal_references`

Relacja pytanie -> przepis.

Pola:

- `id`
- `question_id`
- `legal_unit_id`
- `legal_topic_id`
- `relation_type` - `direct_basis`, `supporting_context`, `related`
- `public_note`
- `internal_note`
- `confidence`
- `status`
- `verified_by`
- `verified_at`
- `created_at`
- `updated_at`

### 7.5 `traffic_sign_legal_references`

Relacja znak -> przepis.

Pola:

- `id`
- `traffic_sign_id`
- `legal_unit_id`
- `legal_topic_id`
- `relation_type`
- `public_note`
- `internal_note`
- `status`
- `verified_by`
- `verified_at`

### 7.6 `legal_content_pages`

Publiczne strony edukacyjne dla przepisow.

Pola:

- `id`
- `legal_topic_id`
- `slug`
- `title`
- `meta_title`
- `meta_description`
- `intro`
- `body`
- `author_id`
- `reviewer_id`
- `published_at`
- `last_reviewed_at`
- `status`

### 7.7 `legal_source_checks`

Opcjonalna, ale rekomendowana tabela audytowa dla procesu E-E-A-T.

Pola:

- `id`
- `legal_unit_id`
- `checked_by`
- `checked_at`
- `source_url`
- `source_status`
- `notes`
- `created_at`
- `updated_at`

Rola:

- zapisuje historie weryfikacji zrodel,
- pomaga udowodnic, kiedy i przez kogo sprawdzono podstawe prawna,
- pozwala wykryc rekordy wymagajace ponownego review po zmianach prawa.

## 8. Korelacja pytan z podstawami prawnymi

### 8.1 Dane wejsciowe

Kandydatow do mapowania mozemy dobierac po:

- `external_id`,
- tresci pytania,
- poprawnej odpowiedzi,
- kategorii,
- `question_topic_id`,
- mediach i kontekście sytuacji,
- istniejacym wyjasnieniu,
- widocznych powiazaniach ze znakami.

### 8.2 Benchmark konkurencji

Jesli konkurencja pokazuje podstawe prawna dla podobnego pytania:

1. zapisujemy to jako trop roboczy,
2. sprawdzamy, czy nasze pytanie jest tym samym lub rownowaznym pytaniem,
3. weryfikujemy podstawe w oficjalnym zrodle,
4. dopiero wtedy tworzymy rekord `question_legal_references`,
5. nie kopiujemy tresci opisu ani interpretacji.

### 8.3 Minimalny proces weryfikacji

Kazdy rekord publikowany przy pytaniu musi miec:

- powiazany akt prawny,
- konkretny artykul/paragraf albo jasny temat prawny,
- link do oficjalnego zrodla,
- status `verified`,
- date weryfikacji,
- osobe lub role reviewera.

### 8.4 Workflow question-first dla obecnych artykulow

Od 15.06.2026 przy obecnych 10 artykułach stosujemy tryb `question-first`: zaczynamy od konkretnego pytania albo małego klastra pytań, a dopiero potem doprecyzowujemy jednostkę prawną i treść artykułu.

Praktyczna zasada:

- `LegalContentPage` jest artykułem filarowym,
- `LegalUnit` ma być tak precyzyjny, jak pozwala oficjalny tekst,
- `QuestionLegalReference.public_note` jest unikalnym uzasadnieniem dla konkretnego pytania,
- `questions.explanation` pozostaje nietknięte, bo jest częścią modułu nauki i egzaminu.

Szczegółowy plan i kolejność audytu są w:

- `docs/LEGAL-CONTENT-AUTHORING-WORKFLOW.md`,
- `docs/LEGAL-CONTENT-QUESTION-FIRST-AUDIT.md`.

## 9. Fazy wdrozenia

### Faza 0: Plan i audyt

- wybrac kanoniczna strukture URL-i,
- przyjac `/przepisy` jako hub MVP,
- potwierdzic standard E-E-A-T i minimalny blok autora/reviewera,
- zaplanowac strone metodologii prawnej,
- przygotowac pierwsza liste `5-10` pytan do MVP,
- przygotowac pierwsze `5` tematow prawnych,
- sprawdzic, ktore znaki maja juz dobre dane prawne.

### Faza 1: Model danych i panel

- dodac tabele `legal_acts`, `legal_units`, `legal_topics`,
- dodac relacje `question_legal_references`,
- dodac relacje `traffic_sign_legal_references`,
- dodac statusy i daty weryfikacji,
- dodac pola autora, reviewera i daty ostatniego review dla stron prawnych,
- rozwazyc tabele `legal_source_checks` dla historii sprawdzen zrodel,
- dodac widok/admin panel do recznej pracy redakcyjnej.

### Faza 2: Publiczny rendering

- dodac publiczna strone metodologii prawnej,
- dodac blok `Uzasadnienie prawne` na stronach pytan,
- dodac linkowanie ze znakow do przepisow,
- dodac pierwsze strony `/przepisy` / `/kodeks-drogowy`,
- pokazac autora/reviewera i date ostatniej weryfikacji,
- dodac breadcrumbs i canonicale,
- dodac sitemap dla stron prawnych.

### Faza 3: Schema i SEO QA

- wdrozyc `Legislation` tylko tam, gdzie mamy realne, widoczne strony prawne,
- utrzymac `WebPage` / `Article` dla stron edukacyjnych,
- nie udawac schema, jesli dane nie sa widoczne na stronie,
- monitorowac GSC, indeksacje i CTR.

### Faza 4: Skalowanie

Szczegolowy plan znajduje sie w `docs/LEGAL-CONTENT-SCALING-PLAN.md`.

- rozszerzac mapowanie pytan partiami,
- uruchamiac deterministyczne discovery, gdy kolejka `ready` spadnie ponizej 10,
- przed 25 artykulami przeniesc definicje do osobnych paczek z idempotentnym importerem,
- laczyc pytania, znaki i przepisy w wersjonowane klastry tematyczne,
- przed 50 artykulami wdrozyc audyt portfela i cykliczny review zmian prawnych,
- przy pracy wielu agentow dodac ownership, blokade rownoleglej pracy i panel operacyjny.

## 10. Definition of Done dla pierwszego pilota

Pilot jest gotowy, gdy:

- istnieje publiczny hub `/przepisy`,
- istnieje publiczna strona metodologii prawnej,
- istnieje minimum 5 stron prawnych / tematow prawnych,
- minimum `5-10` pytan ma zweryfikowana podstawe prawna,
- wybrane znaki z pierwszych tematow linkuja do powiazanej podstawy prawnej, jesli relacja jest realna i zweryfikowana,
- kazda publiczna podstawa prawna ma oficjalne zrodlo i date weryfikacji,
- kazda publiczna strona prawna ma widoczny prawdziwy profil autora i reviewera,
- kazda publiczna strona prawna ma date ostatniej weryfikacji,
- sitemap obejmuje nowe strony prawne,
- testy potwierdzaja, ze `/nauka` i egzamin nie korzystaja z nowych pol prawnych.

## 11. Ryzyka

### Ryzyko 1: Nieprawidlowe podstawy prawne

Mitigacja:

- publikowac tylko `verified`,
- wymagac oficjalnego zrodla,
- utrzymywac date weryfikacji,
- zaczac od malego pilota.

### Ryzyko 2: Kopiowanie konkurencji

Mitigacja:

- konkurencja tylko jako benchmark,
- brak kopiowania tresci,
- finalna tresc oparta o nasze opisy i oficjalne akty prawne.

### Ryzyko 3: Zbyt duzy zakres

Mitigacja:

- nie mapowac calej bazy pytan naraz,
- zaczac od `5-10` pytan,
- nie budowac masowego legal graph na zapas.

### Ryzyko 4: Thin content

Mitigacja:

- kazda strona prawna musi miec realna wartosc,
- laczyc przepisy z pytaniami i znakami,
- unikac stron z samym cytatem i bez wyjasnienia.

### Ryzyko 5: Wplyw na `/nauka`

Mitigacja:

- nowe dane sa tylko publiczna warstwa SEO/zaufania,
- nie zmieniamy `questions.explanation`,
- nie zmieniamy odpowiedzi,
- nie zmieniamy playera ani logiki egzaminu.

## 12. Najblizszy rekomendowany krok

Przed implementacja:

1. przygotowac szczegolowy plan migracji i panelu redakcyjnego,
2. przygotowac tresc strony metodologii prawnej,
3. przygotowac pierwszych `5` stron prawnych,
4. przygotowac liste `5-10` pytan MVP,
5. przygotowac prawdziwe profile autora i reviewera,
6. dopiero potem zaczac development.
