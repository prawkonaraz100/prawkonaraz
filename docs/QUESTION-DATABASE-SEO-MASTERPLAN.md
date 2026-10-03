# Question Database SEO Masterplan

## 1. Cel dokumentu

Ten dokument spisuje kanoniczny plan rozwoju publicznej bazy pytan egzaminacyjnych tak, aby:

- zbudowac realny klaster SEO wokol teorii na prawo jazdy,
- przejsc z modelu `publiczna baza pytan` do modelu `topical authority`,
- wykorzystac nasze przewagi produktowe, a nie tylko kopiowac widoczne wzorce konkurencji,
- ulozyc wdrozenie w sekwencji `30 / 60 / 90 dni`, bez przypadkowego scope creep.

To nie jest dokument dla pojedynczego taska frontendowego. To jest dokument kierunkowy dla:

- produktu,
- SEO,
- contentu,
- routingu i architektury publicznych stron,
- roadmapy deweloperskiej.

## 2. Punkt wyjscia

Na dzien `2026-04-30` mamy juz wdrozony zdrowy fundament techniczny dla publicznej bazy pytan:

- serwerowo renderowane strony `hub -> category -> question`,
- kanoniczne URL-e pytan ze slugiem,
- `BreadcrumbList` i podstawowa warstwa structured data,
- osobna sitemap dla pytan,
- powiazane pytania na kartach pytan,
- poprawne renderowanie danych pytania po stronie Laravel / Blade.

Aktualna implementacja opiera sie glownie o:

- `app/Http/Controllers/PublicQuestionDatabaseController.php`
- `app/Support/PublicQuestionCatalogService.php`
- `app/Support/PublicQuestionSeoService.php`
- `app/Support/PublicQuestionSchemaService.php`
- `app/Http/Controllers/SitemapController.php`
- `app/Http/Controllers/SitemapQuestionsController.php`
- `resources/views/questions-database/index.blade.php`
- `resources/views/questions-database/category.blade.php`
- `resources/views/questions-database/show.blade.php`

To oznacza, ze najwiekszy techniczny blocker poprzedniej wersji juz nie istnieje: SEO-critical HTML nie jest juz zalezne od client-side Inertia.

## 3. Co robi konkurencja

### 3.1 Prawo-Jazdy-360

Referencje:

- [home](https://www.prawo-jazdy-360.pl/)
- [publiczne pytanie](https://www.prawo-jazdy-360.pl/testy-na-prawo-jazdy/pytania-egzaminacyjne/ile-maksymalnie-osob-wliczajac-siebie-mozesz-przewozic-gdy-masz-prawo-jazdy-kategorii-b)
- [robots.txt](https://www.prawo-jazdy-360.pl/robots.txt)
- [sitemap pytan](https://www.prawo-jazdy-360.pl/sitemap-pytania.xml)

Co robia dobrze:

- maja publicznie indeksowane strony pojedynczych pytan,
- maja osobna sitemap dla pytan,
- buduja szeroki ekosystem sekcji wspierajacych: kurs, wyklady, znaki, kodeks, aktualnosci, forum, ranking, kontakt,
- pytania maja silne `title`, `description`, `canonical` i `og:image`,
- struktura URL-i pytan jest czytelna i semantyczna.

Co jest ich prawdziwa przewaga:

- nie wygrywaja jedna strona,
- wygrywaja calym content graph wokol tej samej intencji uzytkownika.

### 3.2 Teoria.pl

Referencje:

- [pytania z odpowiedziami](https://www.teoria.pl/pytania-na-prawo-jazdy-z-odpowiedziami)
- [przykladowe pytanie](https://www.teoria.pl/pytania-na-prawo-jazdy-z-odpowiedziami/czy-posiadajac-prawo-jazdy-kat-b-od-co-najmniej-3-lat-masz-prawo%2C16264)

Co robia dobrze:

- maja bardzo prosty, lekki model stron,
- bardzo trafnie lapia long tail typu `pytania na prawo jazdy z odpowiedziami`,
- maja czytelne wejscia per kategoria,
- publiczne pytania sa crawlable i latwe do zrozumienia przez Google.

Co jest istotne:

- technicznie ich warstwa SEO nie jest wybitna,
- mimo to rankuja, bo trafiaja bardzo dobrze w intencje i nie komplikuja strony.

### 3.3 LTesty

Referencje:

- [sekcja testow](https://ltesty.pl/testy/)
- [demo](https://ltesty.pl/demo/0)

Co robia dobrze:

- maja szeroki klaster tematow wspierajacych,
- spinaja testy z podrecznikiem, prawem o ruchu drogowym, znakami, taryfikatorem i dokumentami,
- zbieraja ruch nie tylko z query exact-match, ale tez z query edukacyjnych i poradnikowych.

Co jest ich prawdziwa przewaga:

- coverage intencji,
- nie tylko coverage pojedynczych pytan.

### 3.4 pytaniaprawojazdy.pl

Referencje:

- [home](https://pytaniaprawojazdy.pl/)
- [przykladowy artykul](https://pytaniaprawojazdy.pl/artykuly/testy-na-prawo-jazdy-ile-punktow-trzeba-by-zdac)

Co robia dobrze:

- buduja ruch na pytania informacyjne typu `ile punktow`, `ile pytan`, `jak wyglada egzamin`,
- dokladaja FAQ i poradniki wspierajace money pages,
- probuja laczyc testy, artykuly i znaki.

Co jest wazne:

- to nie jest najmocniejszy gracz technologicznie,
- ale dobrze pokazuje, jak przechwytywac ruch informacyjny wokol egzaminu.

## 4. Co z tego wynika dla nas

### 4.1 Gdzie juz jestesmy mocni

- mamy juz lepszy fundament techniczny niz czesc konkurencji,
- mamy SSR zamiast metadata-only po JS,
- mamy czyste kanoniczne URL-e,
- mamy dane, ktorych konkurencja zwykle nie pokazuje tak dobrze: trudnosc, statystyki, topiki, relacje miedzy pytaniami,
- mamy duzy corpus z wyjasnieniami i mediami.

### 4.2 Gdzie jestesmy jeszcze z tylu

- baza pytan nie jest jeszcze centralnym hubem serwisu,
- za malo mocnego linkowania prowadzi do niej z home, headera i innych klastrow,
- mamy za malo stron posrednich miedzy `hub -> kategoria -> pytanie`,
- nie wykorzystujemy jeszcze w pelni `question_topic_id`,
- nie mamy jeszcze warstwy stron intencyjnych typu `pytania z odpowiedziami`, `ile punktow`, `jak uczyc sie do teorii`, `najczesciej oblewane pytania`,
- nie budujemy jeszcze wystarczajaco szerokiego topical graph wokol teorii.

### 4.3 Najwazniejszy wniosek strategiczny

Nie wygramy samym:

- `schema`,
- `ladniejszym widokiem`,
- sama liczba URL-i pytan.

Wygramy wtedy, gdy polaczymy:

- publiczne karty pojedynczych pytan,
- strony kategorii,
- strony tematow,
- strony intencyjne,
- poradniki,
- statystyki i strony oparte o nasze wlasne dane.

## 5. North Star

Naszym celem nie jest `miec baze pytan`.

Naszym celem jest zbudowac najpelniejszy, najlepiej zlinkowany i najbardziej przydatny publiczny hub wiedzy o teorii na prawo jazdy w Polsce, tak aby niezaleznie od intencji uzytkownik trafil do nas.

To oznacza, ze chcemy zbierac ruch z query:

- exact-match na konkretne pytania,
- `pytania na prawo jazdy z odpowiedziami`,
- `pytania kategorii B`,
- `najtrudniejsze pytania`,
- `ile pytan`, `ile punktow`, `ile czasu`,
- `czy testy online sa takie same jak w WORD`,
- `pierwszenstwo przejazdu pytania`,
- `pytania z pierwszej pomocy`,
- `pytania za 3 punkty`,
- `najczesciej bledne pytania`.

## 6. Zasady wdrozenia

1. Najpierw budujemy mocna architekture i internal linking, potem skalujemy liczbe typow stron.
2. Nie indeksujemy wszystkiego jak leci. Indeksujemy tylko strony, ktore maja unikalna wartosc i sens.
3. Strony programmatic bez sensownego intro, bez kontekstu i bez roznicy informacyjnej nie powinny byc publicznym targetem SEO.
4. `Schema` ma wspierac, a nie udawac strategie.
5. Najmocniejsza przewaga ma przyjsc z naszych danych produktowych, nie z samego przepisywania korpusu pytan.

## 7. Docelowy model stron

### 7.1 Warstwa 1: Hub glowny

- `/oficjalna-baza-pytan-na-prawo-jazdy`

Rola:

- glowny punkt wejscia do calego klastra,
- dystrybucja link equity,
- wejscie do kategorii, tematow i stron intencyjnych.

### 7.2 Warstwa 2: Kategorie

- `/oficjalna-baza-pytan-na-prawo-jazdy/{categorySlug}`

Rola:

- ranking na query kategorii,
- przejscie do pytan i tematow danej kategorii,
- rozdzielenie intencji `kat. B`, `kat. C`, `kat. T` itd.

### 7.3 Warstwa 3: Pytania

- `/pytanie/{externalId}/{slug}`

Rola:

- long tail na konkretne tresci pytan,
- wejscie z exact-match i z query quasi-natural-language,
- punkt konwersji do `/nauka`.

### 7.4 Warstwa 4: Tematy

Nowe URL-e:

- `/tematy-pytan`
- `/tematy-pytan/{topicSlug}`

Rola:

- wykorzystanie `question_topic_id`,
- przejmowanie ruchu na query tematyczne,
- zbudowanie warstwy posredniej miedzy kategoria a pytaniem.

### 7.5 Warstwa 5: Podstawy prawne i przepisy

Powiazane URL-e:

- `/przepisy`
- `/kodeks-drogowy`
- `/przepisy/{topicSlug}`
- `/kodeks-drogowy/{legalUnitSlug}`

Rola:

- podniesienie zaufania do publicznych stron pytan,
- pokazanie, skad wynika poprawna odpowiedz,
- linkowanie `pytanie -> podstawa prawna -> przepis -> powiazane pytania`,
- polaczenie bazy pytan z klastrem znakow drogowych,
- budowanie topical authority wokol prawa o ruchu drogowym.

Zasady:

- nie zmieniamy `questions.explanation` uzywanego w produkcie i `/nauka`,
- `Uzasadnienie prawne` jest osobna warstwa danych,
- dluzsze publiczne omowienia SEO pytan maja osobny plan i osobna warstwe danych: [PUBLIC-QUESTION-EXPLANATION-LAYER-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PUBLIC-QUESTION-EXPLANATION-LAYER-PLAN.md),
- publikujemy tylko zweryfikowane podstawy prawne z oficjalnych zrodel,
- konkurencja moze byc benchmarkiem UX lub tropem do audytu, ale nie zrodlem tresci,
- szczegolowy plan: [LEGAL-TRUST-LAYER-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/LEGAL-TRUST-LAYER-PLAN.md).

### 7.6 Warstwa 6: Strony intencyjne

Przykladowe URL-e:

- `/pytania-na-prawo-jazdy-z-odpowiedziami`
- `/pytania-na-prawo-jazdy-kategorii-b-z-odpowiedziami`
- `/ile-pytan-jest-na-egzaminie-na-prawo-jazdy`
- `/ile-punktow-trzeba-zeby-zdac-teorie`
- `/ile-trwa-egzamin-teoretyczny-na-prawo-jazdy`
- `/czy-testy-online-sa-takie-same-jak-w-word`

Rola:

- zbieranie query informacyjnych i porownawczych,
- wspieranie category pages i question pages.

### 7.7 Warstwa 7: Strony oparte o nasze dane

Przykladowe URL-e:

- `/najtrudniejsze-pytania`
- `/najczesciej-oblewane-pytania`
- `/pytania-za-3-punkty`
- `/pytania-z-filmem`
- `/pytania-tak-nie`
- `/pytania-z-najwieksza-liczba-bledow`

Rola:

- zbudowanie przewagi, ktorej konkurencja nie ma 1:1,
- pokazanie `information gain`,
- mocne wsparcie SEO i konwersji.

## 8. Roadmap 30 / 60 / 90 dni

### 8.1 Dni 0-30: Fundament i dystrybucja

#### Cel

Zmienic baze pytan z pobocznej sekcji w centralny, silnie podlinkowany hub serwisu.

#### Zakres

- dodac mocny link do bazy pytan w headerze,
- dodac mocny modul i CTA na home, ktore prowadza do huba pytan,
- rozbudowac footer i linkowanie z innych publicznych klastrow,
- rozszerzyc sitemap index o:
  - hub pytan
  - kategorie pytan
  - przyszle topic pages
- ustabilizowac `Podobne pytania` i usunac losowosc z internal linkingu,
- poprawic `title` i `description` dla pytan i paginacji,
- ustalic polityke `index / noindex / canonical` dla przyszlych faceted views,
- zredukowac zaleznosc strategii od `QAPage` jako glownego argumentu SEO.

#### Konkretne prace techniczne

- update `resources/views/components/site/public-header.blade.php`
- update `resources/js/Pages/Home.vue`
- update `app/Http/Controllers/SitemapController.php`
- update `app/Support/PublicQuestionCatalogService.php`
- update `app/Support/PublicQuestionSeoService.php`
- update `app/Support/PublicQuestionSchemaService.php`

#### Exit criteria

- baza pytan jest widoczna w glownym shellu publicznym,
- hub i kategorie sa czescia oficjalnej strategii sitemap,
- internal linking jest stabilny i przewidywalny,
- metadata pytan sa mocniejsze pod CTR.

### 8.2 Dni 31-60: Tematy i intent capture

#### Cel

Rozszerzyc zasieg SEO poza same pytania i kategorie.

#### Zakres

- uruchomic topic index i topic pages oparte o `question_topic_id`,
- uruchomic pierwsze strony intencyjne pod `pytania z odpowiedziami`,
- uruchomic pierwsze FAQ / explainery wokol egzaminu teoretycznego,
- podpiac tematowe i intencyjne strony do pytan oraz kategorii,
- zbudowac pierwsze kontrolowane strony facetowe.

#### Nowe kontrolery

- `PublicQuestionTopicController`
- `PublicQuestionFacetController`
- `QuestionGuideController`
- `SitemapQuestionTopicsController`
- `SitemapQuestionGuidesController`

#### Nowe widoki

- `resources/views/questions-database/topics/index.blade.php`
- `resources/views/questions-database/topics/show.blade.php`
- `resources/views/questions-database/facets/show.blade.php`
- `resources/views/questions-guides/show.blade.php`

#### Strony do wdrozenia w pierwszym batchu

- `pytania-na-prawo-jazdy-z-odpowiedziami`
- `pytania-na-prawo-jazdy-kategorii-b-z-odpowiedziami`
- `ile-pytan-jest-na-egzaminie-na-prawo-jazdy`
- `ile-punktow-trzeba-zeby-zdac-teorie`
- `ile-trwa-egzamin-teoretyczny-na-prawo-jazdy`
- `czy-testy-online-sa-takie-same-jak-w-word`

#### Exit criteria

- istnieje warstwa tematow,
- istnieje pierwsza warstwa stron intencyjnych,
- klaster pytan zaczyna zbierac ruch nie tylko z exact-match.

### 8.3 Dni 61-90: Product SEO i przewaga z danych

#### Cel

Zbudowac warstwe, ktora bedzie trudna do skopiowania przez konkurencje.

#### Zakres

- uruchomic strony oparte o trudnosc i wyniki,
- wykorzystac `najtrudniejsze pytania` jako czesc glownego graphu,
- uruchomic strony typu:
  - `najczesciej oblewane`
  - `najbardziej mylace`
  - `za 3 punkty`
  - `z filmem`
  - `tak / nie`
- zrobic osobny dashboard SEO + product metrics dla tego klastra,
- dolozyc mocniejsze interlinking rules miedzy:
  - pytanie
  - temat
  - kategoria
  - guide
  - ranking trudnosci

#### Exit criteria

- mamy nie tylko publiczna baze pytan,
- mamy publiczny ekosystem pytan, tematow, stron intencyjnych i statystyk,
- konkurencja nie da sie juz dogonic samym skopiowaniem URL-i pytan.

## 9. Priorytety backlogu

### Priorytet P1

- header / home / footer linking do huba,
- rozszerzenie sitemap index,
- stabilny internal linking,
- mocniejsze metadata pytan,
- polityka canonical / noindex dla przyszlych faceted pages.

### Priorytet P2

- topic pages,
- intent pages,
- pierwsze guide pages,
- page templates dla statystyk i faceted contentu.

### Priorytet P3

- product SEO oparte o trudnosc i user behavior,
- rozbudowane klastry poradnikowe,
- eksperymenty z dodatkowymi typami structured data tam, gdzie maja sens.

## 10. Metryki sukcesu

Musimy mierzyc ten pion osobno od reszty serwisu.

### SEO

- liczba zindeksowanych question pages,
- liczba zindeksowanych topic pages,
- liczba zindeksowanych intent pages,
- impressions per cluster,
- CTR per cluster,
- liczba query `top 3 / top 10 / top 20`.

### Produkt

- wejscia `question page -> /nauka`,
- wejscia `topic page -> question page`,
- wejscia `guide page -> /nauka`,
- rejestracje wspierane przez publiczna baze pytan.

### Techniczne

- status sitemap,
- crawl errors,
- duplicate title / description,
- liczba stron z `noindex`,
- median TTFB dla pytan i topic pages.

## 11. Ryzyka i no-go rules

### Ryzyka

- zbyt agresywne mnozenie cienkich stron,
- indeksowanie wszystkich filtrow i query pages,
- zbyt mocne oparcie narracji SEO o `QAPage`,
- niestabilny internal linking,
- rozjazd miedzy contentem publicznym a rzeczywistym zakresem pytan aktywnych.

### No-go rules

- nie publikujemy surowych search results jako stron SEO,
- nie publikujemy faceted pages bez unikalnego intro i konkretnej roznicy wartosci,
- nie mnozymy prawie identycznych landingow tylko po to, zeby zwiekszyc liczbe URL-i,
- nie traktujemy structured data jako zamiennika za mocna architekture informacji.

## 12. Najblizszy sprint

Jesli mamy wejsc od razu w wykonanie, pierwszy sprint powinien objac:

1. link do huba pytan w glownym headerze,
2. modul na home prowadzacy do huba pytan,
3. rozszerzenie sitemap index,
4. stabilizacje `Podobnych pytan`,
5. update `title` i `description` dla kart pytan,
6. ADR / decyzje o docelowej polityce topic pages i intent pages.

To jest najkrotsza droga do przejscia z `mamy sekcje` do `mamy realny silnik wzrostu SEO`.

## 13. Backlog sprintowy

Ta sekcja rozbija masterplan na trzy wykonawcze sprinty. Ich celem nie jest "zrobic wszystko", tylko dowozic kolejne warstwy przewagi w logicznej kolejnosci:

1. najpierw dystrybucja link equity i fundament SEO,
2. potem nowe typy stron i coverage intencji,
3. na koncu product SEO i strony oparte o nasze dane.

## 13.1 Sprint 1: Fundament SEO i dystrybucja

### Cel sprintu

Przeniesc baze pytan z pobocznej sekcji do roli centralnego huba publicznego.

### Zakres biznesowy

- uzytkownik ma latwy dostep do bazy pytan z glownych publicznych ekranow,
- Google dostaje mocniejszy sygnal, ze to jest kluczowy klaster serwisu,
- publiczne pytania maja lepsze metadata i stabilniejsze linkowanie.

### Zadania techniczne

1. Header: dodac staly link do huba bazy pytan.
   Pliki:
   - `resources/views/components/site/public-header.blade.php`

2. Home: dodac mocny modul kierujacy do huba bazy pytan.
   Pliki:
   - `resources/js/Pages/Home.vue`

3. Footer / trust pages: dodac lub wzmocnic linkowanie do huba pytan.
   Pliki:
   - `resources/views/components/site/public-footer.blade.php`
   - ewentualnie strony wspierajace publiczny shell

4. Sitemap index: dodac pozycje dla huba i kategorii pytan.
   Pliki:
   - `app/Http/Controllers/SitemapController.php`
   - nowy kontroler sitemap, jesli wygodniej rozdzielic odpowiedzialnosc

5. Karty pytan: ustabilizowac `Podobne pytania`.
   Pliki:
   - `app/Support/PublicQuestionCatalogService.php`

6. Meta SEO: wzmocnic `title` i `description` dla:
   - huba
   - kategorii
   - pytan
   - paginacji kategorii
   Pliki:
   - `app/Support/PublicQuestionSeoService.php`

7. Structured data: ustalic i wdrozyc bezpieczna polityke schema.
   Pliki:
   - `app/Support/PublicQuestionSchemaService.php`
   - ewentualnie osobny ADR w `docs/`

8. Indexation policy: zapisac zasady dla przyszlych filtrow i stron facetowych.
   Deliverable:
   - sekcja ADR lub osobny dokument wykonawczy w `docs/`

### Zadania SEO / content

1. Uzgodnic finalne title patterns.
2. Uzgodnic description patterns.
3. Uzgodnic anchor texty dla:
   - header
   - home module
   - category cards
   - related questions

### Definition of Done

- baza pytan jest widoczna w headerze i na home,
- hub i kategorie sa formalnie uwzglednione w sitemap strategy,
- `Podobne pytania` nie sa losowe request-to-request,
- pytania maja finalny, zaakceptowany wzorzec metadata,
- polityka `canonical / index / noindex` jest zapisana, nie domyslana.

### Ryzyka sprintu

- zbyt duzy scope redesignu home,
- dyskusja o `QAPage` moze zablokowac rollout,
- przypadkowe rozjechanie anchorow i IA przez zbyt duzo linkow naraz.

### Oczekiwany efekt

- mocniejszy crawl flow,
- lepsza dystrybucja wewnetrznego PageRanku,
- wzrost szans na indeksacje i CTR bez zmiany corpusu contentowego.

## 13.2 Sprint 2: Tematy i strony intencyjne

### Cel sprintu

Wyjsc poza model `hub -> kategoria -> pytanie` i dodac warstwe, ktora lapie szersze query.

### Zakres biznesowy

- przejmujemy ruch tematyczny i informacyjny,
- zaczynamy rankowac nie tylko na pytania, ale tez na zagadnienia,
- budujemy posrednie landing pages, ktore zasilaja karty pytan ruchem i link equity.

### Zadania techniczne

1. Topic index i topic show pages.
   Nowe URL-e:
   - `/tematy-pytan`
   - `/tematy-pytan/{topicSlug}`
   Pliki:
   - nowy `PublicQuestionTopicController.php`
   - nowe widoki Blade dla topics
   - routing w `routes/web.php`

2. Topic sitemap.
   Pliki:
   - nowy `SitemapQuestionTopicsController.php`
   - update `SitemapController.php`

3. Intent pages engine.
   Pierwszy batch:
   - `pytania-na-prawo-jazdy-z-odpowiedziami`
   - `pytania-na-prawo-jazdy-kategorii-b-z-odpowiedziami`
   - `ile-pytan-jest-na-egzaminie-na-prawo-jazdy`
   - `ile-punktow-trzeba-zeby-zdac-teorie`
   - `ile-trwa-egzamin-teoretyczny-na-prawo-jazdy`
   - `czy-testy-online-sa-takie-same-jak-w-word`
   Pliki:
   - nowy `QuestionGuideController.php`
   - nowe widoki w `resources/views/questions-guides/`
   - routing w `routes/web.php`

4. Internal linking rules miedzy:
   - topic page -> questions
   - question -> topic page
   - guide -> category / topic / question
   - category -> topic

5. Meta and schema support dla topic pages i guide pages.
   Pliki:
   - `PublicQuestionSeoService.php`
   - `PublicQuestionSchemaService.php`
   - ewentualnie nowe wspierajace serwisy

### Zadania SEO / content

1. Stworzyc slownik topic slugow i topic titles.
2. Przygotowac unikalne intro copy dla pierwszego batcha topic pages.
3. Przygotowac unikalne intro copy dla pierwszego batcha intent pages.
4. Uzgodnic wzorzec CTA z topic / guide do `/nauka`.

### Definition of Done

- istnieje publiczny indeks tematow,
- istnieja publiczne strony tematow,
- istnieje pierwszy batch stron intencyjnych,
- topic pages i guide pages sa uwzglednione w sitemap strategy,
- linkowanie miedzy tematami, pytaniami i guide pages jest obustronne.

### Ryzyka sprintu

- zbyt szybkie mnozenie stron bez wystarczajacej unikalnosci,
- zbyt slabe intro copy na pages programmatic,
- wejscie w spory o taxonomy zanim uzgodnimy docelowe mapowanie tematow.

### Oczekiwany efekt

- szersze pokrycie long taila,
- pierwsze wejscia z query informacyjnych i tematycznych,
- mocniejszy topical graph wokol teorii.

## 13.3 Sprint 3: Product SEO i przewaga z danych

### Cel sprintu

Zbudowac strony, ktorych konkurencja nie skopiuje samym publicznym corpusem pytan.

### Zakres biznesowy

- wykorzystujemy nasza przewage z danych i zachowan uzytkownikow,
- budujemy publiczne strony oparte o trudnosc i jakosc odpowiedzi,
- laczymy SEO z produktem i konwersja, a nie tylko z publikacja tresci.

### Zadania techniczne

1. Strony statystyczne / rankingowe.
   Kandydaci:
   - `/najtrudniejsze-pytania`
   - `/najczesciej-oblewane-pytania`
   - `/pytania-z-najwieksza-liczba-bledow`
   - `/pytania-za-3-punkty`
   - `/pytania-z-filmem`
   - `/pytania-tak-nie`

2. Wspolny template dla stron opartych o ranking / facety.
   Pliki:
   - nowy `PublicQuestionFacetController.php`
   - nowe widoki w `resources/views/questions-database/facets/`

3. Ranking / stats sitemap.
   Pliki:
   - nowy kontroler sitemap
   - update `SitemapController.php`

4. KPI instrumentation.
   Mierzyc:
   - wejscia `question -> /nauka`
   - wejscia `topic -> question`
   - wejscia `guide -> /nauka`
   - wejscia `facet -> question`
   - rejestracje wspierane przez publiczny klaster pytan

5. Dashboard operacyjny.
   Minimum:
   - impressions per cluster
   - CTR per cluster
   - indexed URLs per cluster
   - top queries per cluster

### Zadania SEO / content

1. Zdefiniowac, ktore rankingi maja wystarczajacy `information gain`, zeby byly indeksowane.
2. Przygotowac intro copy i wyjasnienie metodologii dla stron rankingowych.
3. Uzgodnic zasady aktualizacji dat i freshness signals.

### Definition of Done

- istnieje pierwszy batch publicznych stron opartych o nasze dane,
- strony te maja jasna metodologie i sens biznesowy,
- metryki organiczne i produktowe dla klastra sa mierzone osobno,
- mamy realna przewage trudna do skopiowania samym content scrape.

### Ryzyka sprintu

- wypuszczenie rankingow bez czytelnej metodologii,
- indexowanie zbyt wielu stron statystycznych na raz,
- nadmierne komplikowanie modelu danych przed pierwszym release.

### Oczekiwany efekt

- przewaga nie tylko w coverage, ale tez w unikalnosci,
- mocniejsze wejscia z query typu `najtrudniejsze`, `najczesciej bledne`, `za 3 punkty`,
- lepsze laczenie SEO z konwersja do produktu.

## 14. Kolejnosc wykonania i zaleznosci

### Zaleznosci twarde

1. Sprint 1 musi zostac dowieziony przed Sprintem 2.
2. Topic pages ze Sprintu 2 powinny istniec przed duza rozbudowa stron rankingowych ze Sprintu 3.
3. Polityka `index / noindex / canonical` musi byc ustalona przed wypuszczeniem faceted i ranking pages.

### Zaleznosci miekkie

1. Mozna przygotowac copy dla topic pages juz podczas Sprintu 1.
2. Mozna przygotowac modele danych do rankingow juz podczas Sprintu 2.
3. Mozna uruchomic dashboard raportowy etapami, bez czekania na pelny Sprint 3.

## 15. Rekomendowana kolejnosc ticketowania

Jesli backlog ma wejsc do systemu taskowego, rekomendowana kolejnosc to:

1. `SEO-DB-001` Header link do bazy pytan
2. `SEO-DB-002` Home module do huba pytan
3. `SEO-DB-003` Sitemap index dla hub i kategorii
4. `SEO-DB-004` Stabilne related questions
5. `SEO-DB-005` Metadata patterns dla pytan i kategorii
6. `SEO-DB-006` ADR dla schema + indexation policy
7. `SEO-DB-007` Topic index routing i controller
8. `SEO-DB-008` Topic show pages
9. `SEO-DB-009` Topic sitemap
10. `SEO-DB-010` Intent pages batch 1
11. `SEO-DB-011` Internal linking topic <-> question <-> guide
12. `SEO-DB-012` Facet / ranking pages batch 1
13. `SEO-DB-013` KPI instrumentation
14. `SEO-DB-014` Dashboard organic + product metrics

## 16. SEO-INDEX repair queue po audycie 2026-09-20

Ta kolejka powstala po ponownym sprawdzeniu aktualnego kodu, live HTML i danych GSC. Nie zastepuje istniejacych taskow NEWSROOM-N5/N6 ani starego backlogu SEO-DB. NEWSROOM-N6-012 jest juz DONE na poziomie repo i ma potwierdzony post-merge Quality Gate; ponizsza kolejka jest teraz trwalym cross-site SEO repair backlogiem. N5-007/N6-003 pozostaja osobnym torem production/runtime evidence.

### SEO-INDEX-000 — Baseline i Quality Gate przed kazdym taskiem

Status: **ACTIVE PROCESS GATE**

Przed rozpoczeciem kazdego kolejnego taska:
- odczytac aktualny `main@HEAD`,
- sprawdzic wymagane GitHub Actions dla tego exact SHA,
- wymagane joby i kroki musza miec `completed / success`,
- nie przechodzic dalej przy `in_progress`, `queued`, `failure` ani przy report-only wrapper success ukrywajacym underlying failure,
- po implementacji powtorzyc exact-head CI, a po merge rowniez post-merge Quality Gate.

Task nie jest osobna funkcja produktu; jest obowiazkowa bramka procesu dla `SEO-INDEX-001...`.

### SEO-INDEX-001 — Globalne discovery strategicznych hubow

Status: **DONE / REPO-LEVEL**

Zrealizowany zakres:
- `PublicFooter::service_links` zawiera teraz stabilne crawlable linki do `/znaki-drogowe` i `/przepisy`,
- istniejacy link do huba bazy pytan zostal zachowany,
- Blade `public-footer.blade.php` i Vue `SiteFooter.vue` nadal renderuja ten sam `footer.service_links` payload,
- minimalistyczny top-nav nie zostal rozszerzony ani przebudowany,
- nie utworzono drugiego systemu nawigacji.

Regression evidence:
- `tests/Feature/Public/NewsroomNavigationIntegrationTest.php` potwierdza dokladnie po jednym href dla bazy pytan, znakow, przepisow, aktualnosci i poradnikow,
- ten sam test potwierdza brak duplikatow `service_links` oraz wspolny renderer contract Blade/Vue.

Potwierdzony finalny stan:
- implementation PR #156,
- implementation head `89555c08a9051280d8019a5da26620eb9bebee1b`,
- exact-head CI #550: `quality` PASS i `newsroom-postgres` PASS,
- Browser Smoke #113: wszystkie uruchomione newsroom browser QA PASS; wrapper `browser-smoke` byl celowo skipped zgodnie z macierza,
- merge `main@c6d9990dba2af8cfc327020283aefb3168e49130`,
- post-merge CI #551: `quality` PASS i `newsroom-postgres` PASS, w tym backend suite, Pint/code style i frontend build.

Granica potwierdzenia:
- task jest DONE na poziomie repo,
- nie oznacza to jeszcze potwierdzonego deployu ani recrawlu Google; live verification pozostaje w `SEO-INDEX-004`, a GSC evidence w `SEO-INDEX-005`.

Definition of Done: **PASS na poziomie repo**.

### SEO-INDEX-002 — Structured data huba `/przepisy`

Status: **DONE / REPO-LEVEL**

Zrealizowany zakres:
- `LegalContentSchemaService::legalContentItemListSchema()` nie deklaruje juz zagniezdzonych, niepelnych `Article` z samym `@id/name/url`,
- kazdy `ListItem.item` na hubie `/przepisy` zawiera teraz tylko stabilna referencje `{ "@id": ... }` do Article nalezacego do strony detail,
- `CollectionPage`, `ItemList`, `ListItem`, nazwy, opisy i URL-e listy pozostaly bez zmian,
- detail page nadal jest jedynym miejscem, ktore deklaruje pelny `Article` z `headline`, datami, publisherem, autorem i innymi potwierdzonymi polami,
- nie dodano sztucznych `headline`, `image` ani `datePublished` na hubie.

Regression evidence:
- istniejacy `tests/Feature/Public/LegalTrustLayerMvpTest.php` zostal rozszerzony,
- test potwierdza 33 elementy listy oraz wymaga, aby kazde `item` mial dokladnie jeden klucz: `@id`,
- test detail page nadal potwierdza pelny `Article` pod tym samym stabilnym ID.

Potwierdzony finalny stan:
- implementation PR #158,
- implementation head `71ed905dbaf382a67a65ea3ede662c3cf3a3ea8d`,
- exact-head CI #554: `quality` PASS i `newsroom-postgres` PASS, w tym backend suite, Pint/code style i frontend build,
- merge `main@9c879a99734f81d7083032b6981dc6ef018c56a1`,
- post-merge CI #555: `quality` PASS i `newsroom-postgres` PASS.

Granica potwierdzenia:
- task jest DONE na poziomie repo,
- production validator uruchomiony przy PR #158 byl w trybie report-only i jego wrapper success nie oznacza live PASS: `static_delivery_exit=1`, `enterprise_seo_exit=1`, 102 checks / 3 failures / 2 warnings,
- te live problemy dotycza osobnego production/runtime evidence i nie sa dowodem regresji tego taska przed deployem,
- live verification poprawionej struktury `/przepisy` pozostaje w `SEO-INDEX-004`; GSC evidence pozostaje w `SEO-INDEX-005`.

Definition of Done: **PASS na poziomie repo**.

### SEO-INDEX-003 — SEO-critical raw HTML dla `/najtrudniejsze-pytania-na-prawo-jazdy`

Status: **DONE / REPO-LEVEL**
Priorytet: **P0**

Zrealizowany zakres:
- `PublicQuestionDifficultyController` nie zwraca juz client-only `Inertia::render(...)`; publiczny hub i wariant kategorii renderuja istniejacy Blade shell `layouts.public-content`,
- `PublicQuestionDifficultyService` pozostaje jedynym read modelem/source danych dla rankingu; nie dodano drugiej implementacji rankingu,
- zachowano istniejace route'y, kontrakt parametru `ranking` i URL-e kategorii,
- raw HTML zawiera title, meta description, self-canonical, H1, glowny opis, crawlable linki do kategorii i typow rankingu oraz liste rankingowa,
- `QuestionTextFormatter` nadal obsluguje istniejacy inline markup tresci pytan,
- nie wdrozono globalnego Inertia SSR ani nowego entrypointu SSR.

Regression evidence:
- `tests/Feature/PublicQuestionDifficultyPageTest.php` sprawdza teraz raw SEO HTML zamiast samych Inertia props,
- test potwierdza title, meta description, self-canonical, H1, glowna tresc, crawlable category links i link do wariantu rankingu,
- test wariantu kategorii nadal potwierdza routing po slug oraz poprawny selected ranking/category behavior.

Potwierdzony finalny stan:
- implementation PR #160,
- implementation head `04c5aba997dc76ea382d24607104797ac1b2b559`,
- exact-head CI #559: `quality` PASS i `newsroom-postgres` PASS,
- merge `main@d0a336c18bd3815dc0e8fded1331f8493ea1ad88`,
- post-merge CI #560: `quality` PASS i `newsroom-postgres` PASS, w tym smoke verification, backend suite, Pint/code style i frontend build.

Granica potwierdzenia:
- task jest DONE na poziomie repo,
- nie oznacza to jeszcze potwierdzonego deployu ani live raw-HTML production verification,
- deploy i live verification pozostaja w `SEO-INDEX-004`; GSC evidence pozostaje w `SEO-INDEX-005`.

Definition of Done: **PASS na poziomie repo**.

### SEO-INDEX-004 — Deploy i live verification po 001-003

Status: **TODO / PRODUCTION-ONLY**

Nie oznaczac jako DONE na podstawie samego CI.

Historyczne production evidence — 2026-09-21:
- repo baseline: `main@9d7b4e5f6ef317733b25e57267c908bd338db68a`,
- post-merge CI `#580` dla tego SHA zakonczyl sie pelnym PASS: `newsroom-postgres` = success oraz `quality` = success; w `quality` PASS maja smoke verification, backend test suite, code style checks i frontend build,
- w tej probie publiczne `/znaki-drogowe`, `/przepisy` i `/najtrudniejsze-pytania-na-prawo-jazdy` zwracaly `503 Service Unavailable`,
- z tego powodu live raw-HTML/on-page/schema/canonical verification nie jest mozliwa i task pozostaje otwarty,
- ten stan nie jest dowodem regresji repo; jest production blockerem do usuniecia zgodnie z istniejacym deployment runbookiem,
- nie przechodzic do `SEO-INDEX-005` dopoki te trzy URL-e nie przejda wymaganej live verification po deployu.

Aktualne partial live evidence — 2026-09-22:
- historyczny blocker `503` nie reprodukuje sie: `/znaki-drogowe`, `/przepisy` i `/najtrudniejsze-pytania-na-prawo-jazdy` sa ponownie publicznie pobieralne jako HTML,
- dla wszystkich trzech potwierdzono widoczna tresc i crawlable linki; dla `/najtrudniejsze-pytania-na-prawo-jazdy` live HTML zawiera ranking i pytania, czyli repo naprawa SEO-critical raw HTML jest obecna na publicznym endpointcie,
- repo baseline przed bieżącym PR to `main@8b3cba58dae2408756bd10072449eef47ceba458`; post-merge CI #582 dla tego SHA ma pełny PASS,
- to evidence usuwa wyłącznie availability blocker. Nie jest jeszcze kompletnym dowodem deploy provenance oraz wszystkich wymaganych self-canonical/on-page/schema checks dla 004,
- `SEO-INDEX-004` pozostaje `TODO / PRODUCTION-ONLY`; nie przechodzic do `SEO-INDEX-005` na podstawie samego odzyskania HTTP/HTML.

Po zmergowaniu i post-merge PASS dla 001-003:
- wdrozyc aktualny main zgodnie z istniejacym deployment runbookiem,
- sprawdzic live raw HTML dla `/znaki-drogowe`, `/przepisy` i `/najtrudniejsze-pytania-na-prawo-jazdy`,
- powtorzyc on-page/schema audit,
- potwierdzic 200, self-canonical, crawlable links i brak regresji,
- nie tworzyc nowej sciezki SSH/deploy z GitHub Actions.

### SEO-INDEX-005 — GSC / Indexing Tracker po deployu

Status: **TODO / OBSERVATION GATE**

Historyczny baseline z audytu:
- kontrolna probka: `0/30 indexed`,
- 25 `Crawled - currently not indexed`,
- 3 `URL is unknown to Google`,
- 1 canonical mismatch,
- 1 access forbidden 403.

Nie kasowac tego baseline — sluzy do porownania po deployu.

Odnowione evidence z 2026-09-20:
- homepage `https://prawkonaraz.pl/`: `PASS / Submitted and indexed`, last crawl 2026-09-19 17:02:38Z,
- `/oficjalna-baza-pytan-na-prawo-jazdy`: `URL is unknown to Google`,
- `/oficjalna-baza-pytan-na-prawo-jazdy/b`: `Crawled - currently not indexed`,
- `/znaki-drogowe`: `Crawled - currently not indexed`,
- `/przepisy`: `URL is unknown to Google`,
- `/najtrudniejsze-pytania-na-prawo-jazdy`: `Blocked due to access forbidden (403)`; ostatni zapisany crawl Google z 2026-06-01,
- aktualny homepage PASS oznacza, ze starszy homepage canonical-mismatch verdict jest historyczny, a nie aktualny.

Po deployu mierzyc:
- nowe crawl time,
- `URL unknown -> crawled`,
- `crawled-not-indexed -> indexed`,
- status `/najtrudniejsze-pytania-na-prawo-jazdy`,
- liczbe indexed w tej samej kontrolnej probce,
- impressions i settled Search Analytics.

Task zamyka sie dopiero na podstawie nowego GSC evidence; sam deploy, schema validator albo zielony CI nie wystarcza.

### SEO-INDEX-006 — Wiarygodnosc `lastmod` hubow

Status: **DEFERRED / P2**

Zakres:
- po zamknieciu krytycznych problemow sprawdzic authority dla `lastmod` strategicznych hubow,
- nie uzywac `lastmod = now()` jako hacka,
- nie przebudowywac obecnej generacji sitemap bez dowodu, ze obecny sygnal freshness jest realnym problemem.

Potwierdzony kontekst:
- aktualny `SeoSitemapBuilder` uwzglednia rowniez zmiany wybranych plikow routes/config/views/public frontend,
- pojedyncze huby moga nadal opierac `lastmod` glownie o dane potomne,
- raport klasyfikuje to jako nizszy priorytet niz discovery, structured data i raw HTML.

Definition of Done:
- authority `lastmod` jest jawnie ustalone dla badanego huba albo brak potrzeby zmiany jest udokumentowany dowodem,
- brak sztucznego timestamp churn,
- kazda ewentualna zmiana ma regression test i pelny Quality Gate PASS.

### SEO-INDEX-007 — Skalowanie napraw na pojedyncze pytania dopiero po pilocie

Status: **BLOCKED / P3**

Nie rozpoczynac masowych zmian na tysiacach question URL-i przed wynikiem `SEO-INDEX-005`.

Po pilocie:
- wybrac historycznie reprezentatywna probke question pages,
- porownac crawl/indexation/impressions,
- dopiero na podstawie evidence zdecydowac, czy potrzebne sa dalsze zmiany metadata, contentu, internal linkingu lub innych kontraktow.

Definition of Done:
- decyzja o dalszym scale-out wynika z danych po pilocie,
- brak szerokich zmian wykonywanych tylko dlatego, ze pojedyncze URL-e sa `Crawled - currently not indexed`.

### Kolejnosc wzgledem aktualnego toru

Aktualny punkt startowy:
- `main@b56ce943bf172ce3410fdc927e4adbf50c54fe14`,
- post-merge CI #547: `quality` PASS i `newsroom-postgres` PASS,
- NEWSROOM-N6-012: DONE.

Kolejnosc wykonawcza:
1. `SEO-INDEX-000` — zawsze potwierdzic aktualny HEAD i pelny Quality Gate,
2. `SEO-INDEX-001` — globalne discovery,
3. `SEO-INDEX-002` — schema `/przepisy`,
4. `SEO-INDEX-003` — SEO-critical raw HTML `/najtrudniejsze...`,
5. `SEO-INDEX-004` — deploy + live verification,
6. `SEO-INDEX-005` — GSC / Indexing Tracker,
7. `SEO-INDEX-006` — lastmod tylko jesli po pilocie nadal istnieje uzasadniona luka,
8. `SEO-INDEX-007` — ewentualny scale-out na question pages dopiero po danych z pilota.

Nie przebudowywac ponownie atomic sitemap publication, canonical-host redirects ani calego Inertia SSR bez nowego dowodu i jawnej decyzji architektonicznej.
