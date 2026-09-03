# TODO / PRD: Adaptacja ukladu Prawo-Jazdy-360 do naszego produktu

Data: 2026-03-26  
Dokument bazowy: [UI-AUDIT-PRAWO-JAZDY-360.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-AUDIT-PRAWO-JAZDY-360.md)

Status tego PRD:
- wersja po szostym, ostatecznym coverage passie audytu,
- obejmuje juz nie tylko publiczny front PJ360,
- obejmuje tez prywatne flow: egzamin, wynik, review, kurs, wyklady, podrecznik, szkolenie, statystyki i utility screens.
- ma potwierdzona macierz pokrycia glownego produktu po zalogowaniu,
- ma potwierdzony `cennik`, checkout pre-gateway, `kontakt`, listing opinii i watek forum,
- obejmuje tez rzeczy najlatwiejsze do pominiecia: mobile, widget wsparcia, knowledge pages, ranking, forum, dropdowny drugiego poziomu i utility actions w sesji.

Poziom pewnosci tego PRD:
- `bardzo wysoki` dla shelli egzaminu, wyniku, review i glownych overview pages,
- `wysoki` dla utility screens i modulu statystyk,
- `wystarczajacy do implementacji` dla przyszlych verticali typu player/reader/curriculum,
- `praktycznie kompletny` dla redesignu, bo audyt ma juz `100% pokrycia rodzin shelli i stanow`.

## 0. Co zostalo potwierdzone w audycie finalnym

Bezposrednio potwierdzone zostaly:
- glowny shell produktu po zalogowaniu,
- pelny flow `exam -> result -> review`,
- listing i detail oficjalnej bazy pytan,
- shell `kurs` jako hybrid `overview + review sheet`,
- shell `wyklady` jako `curriculum + player`,
- shell `podrecznik` jako `curriculum + reader + checkpoint`,
- shell `szkolenie z instruktorem` jako `curriculum + player + notes`,
- shell `statystyki`,
- shell `ustawienia` i `historia platnosci`,
- knowledge-base shell,
- ranking shell,
- forum/community shell,
- support widget shell,
- podstawowy mobile shell,
- publiczny selector kategorii i selector jezyka,
- question detail shell,
- modal `Zadaj pytanie`,
- detail review z explanation stackiem.

To znaczy, ze ten PRD nie opiera sie juz na samych screenach marketingowych.
Opiera sie na realnym przejsciu glownego produktu po zalogowaniu.
Lista tego, czego jeszcze nie mamy u siebie, jest rozpisana osobno w:
- [UI-DELTA-PJ360-VS-CURRENT-APP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-DELTA-PJ360-VS-CURRENT-APP.md)

## 1. Cel
Przebudowac warstwe UI naszego produktu tak, aby:

- miala te sama klase kompozycyjna i spoje layoutowa co Prawo-Jazdy-360,
- byla czytelna dla kursanta i od razu komunikowala bogactwo produktu,
- zachowala nasza logike domenowa, trasy i komponenty backendowe,
- nie kopiowala cudzej marki, assetow ani copy 1:1.

Docelowo chcemy uzyskac:
- `ten sam poziom organizacji ekranow`,
- `ten sam rytm sekcji`,
- `ta sama architekture shelli`,
- ale na naszym kodzie, naszej tresci i naszej identyfikacji.

## 2. Zakres

### In scope
- landing publiczny
- publiczny shell nawigacji i footer
- logowanie / rejestracja
- dashboard
- lista pytan
- kolejka powtorek
- hard questions
- ekran sesji nauki / egzaminu
- ekran wyniku i review po sesji
- ekran analityki kategorii
- ekran profilu
- ekran utility / dostep / billing status

### Out of scope na ten etap
- panel Filament
- pelna replika rankingow szkol jazdy
- pelna replika SEO-content pages
- kopiowanie assetow, ikon, zdjec i copy 1:1

## 3. Zasady projektowe

### 3.1 Co kopiujemy
- system shelli
- uklad sekcji
- relacje miedzy sidebar / content / CTA
- kompozycje hero
- model info density
- wzorzec `proof + action + support`

### 3.2 Czego nie kopiujemy
- logo
- assetow graficznych
- copy marketingowego 1:1
- zewnetrznych integracji katalogowych
- nachalnego SEO spamu

### 3.3 Zasady estetyczne dla nas
- mozemy byc odrobine spokojniejsi i czysci wizualnie niz PJ360
- nie schodzimy w portalowy chaos
- nie powielamy przesadnej liczby CTA na jednym ekranie
- utrzymujemy jedna wyrazna logike kompozycji

## 4. Docelowe shelle do wdrozenia

### Shell A: Public marketing shell
Wzor:
- `testy-na-prawo-jazdy`
- `kurs`
- `cennik`

Docelowo u nas:
- [Home.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Home.vue)
- [Auth/Login.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Auth/Login.vue)
- [Auth/Register.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Auth/Register.vue)

Wymagania:
- dwuwarstwowy header
- hero z realnym preview produktu
- proof strip
- sekcja `ucz sie z nami`
- sekcja kategorii
- sekcja aplikacja / media / zaufanie
- duzy mega footer

### Shell B: Learning catalog shell
Wzor:
- `kurs`
- `wyklady`
- `podrecznik`
- `szkolenie z instruktorem`

Docelowo u nas:
- [Dashboard.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Dashboard.vue)
- [Questions/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Questions/Index.vue)
- [ReviewQueue/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/ReviewQueue/Index.vue)
- [HardQuestions/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/HardQuestions/Index.vue)

Wymagania:
- lewy rail z filtrem, postepem i szybkimi wejsciami
- prawa kolumna z duzymi modulami / kartami dzialan
- sekcje w pionowym rytmie
- jedno glowne CTA na modul

Notatka:
- nie budujemy 1:1 wszystkich produktow PJ360,
- ale ich shell nauki jest naszym glownym wzorcem dla overview pages.

### Shell C: Stats shell
Wzor:
- `statystyki`

Docelowo u nas:
- [Dashboard.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Dashboard.vue)
- [Analytics/Categories/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Analytics/Categories/Show.vue)

Wymagania:
- KPI tiles
- saved / wrong / due / readiness widgets
- sekcje per modul
- czytelne puste stany

### Shell D: Public list shell
Wzor:
- `pytania egzaminacyjne`
- `opinie`

Docelowo u nas:
- [Questions/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Questions/Index.vue)

Wymagania:
- duzy title + meta intro
- lista/tabela z wyraznymi kolumnami
- lekkie filtry nad lista
- pagination
- powiazane bloki edukacyjne pod lista

### Shell E: Session shell
Wzor:
- zalogowany shell `testy-na-prawo-jazdy`

Docelowo u nas:
- [StudySessions/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

Wymagania:
- glowna scena 2-kolumnowa
- lewo: media, prompt, odpowiedzi
- prawo: meta, postep, akcje, timer, summary rail
- wyrazne answer bands zamiast lekkich kart
- jedno glowne CTA `dalej`
- zachowanie stabilnego ukladu po zaznaczeniu odpowiedzi
- proste quick actions w prawej kolumnie
- prosty, egzaminowy charakter

### Shell F: Post-session result and review shell
Wzor:
- wynik egzaminu na `testy-na-prawo-jazdy`
- review pytania po kliknieciu numeru

Docelowo u nas:
- [StudySessions/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

Wymagania:
- wynik jako stan tego samego widoku, nie osobny swobodny ekran
- duzy band z wynikiem
- grid numerow pytan
- review pojedynczego pytania
- `wybrano` kontra `poprawna`
- blok `Wyjasnienie`
- blok FAQ
- formularz feedbacku do wyjasnienia

### Shell G: Utility shell
Wzor:
- `ustawienia`
- `historia-platnosci`

Docelowo u nas:
- [Profile/Edit.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Profile/Edit.vue)
- przyszly ekran dostepu/subskrypcji

Wymagania:
- proste cards z informacjami o koncie
- preferencje i zgody
- osobny status dostepu
- osobny status platnosci / planu
- quick links do statystyk i nauki

## 5. Mapa ekranow 1:1

### Public / marketing
- `PJ360 /testy-na-prawo-jazdy` -> nasze `/`
- `PJ360 /logowanie` -> nasze `/login`
- `PJ360 /cennik` -> u nas: przyszla publiczna sekcja pricing na landing page lub osobna strona

### Learning product
- `PJ360 /kurs` -> nasze `/questions`, `/review-queue`, `/hard-questions`
- `PJ360 /wyklady` -> nasze `/dashboard` oraz przyszle overview moduly nauki
- `PJ360 /podrecznik-kursanta` -> nasze future long-form learning pages
- `PJ360 /szkolenie-z-instruktorem` -> nasze future premium learning pages
- `PJ360 /statystyki` -> nasze `/dashboard` i `/analytics/categories/{id}`

### Question product
- `PJ360 /testy-na-prawo-jazdy/pytania-egzaminacyjne` -> nasze `/questions`
- `PJ360 zalogowane /testy-na-prawo-jazdy` -> nasze `/study-sessions/{id}`
- `PJ360 wynik + review na tym samym URL` -> nasz wynik i review w [StudySessions/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

### Trust / proof
- `PJ360 /opinie-o-prawo-jazdy-360` -> u nas: przyszla sekcja opinii / social proof na landing page

### Utility
- `PJ360 /ustawienia` -> nasze `/profile`
- `PJ360 /historia-platnosci` -> nasz przyszly `billing / access status`

## 6. Backlog wdrozeniowy

### P0: Public shell foundation
- przebudowac [AuthenticatedLayout.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/AuthenticatedLayout.vue) na bardziej `PJ360-like public product shell`
- dodac dwuwarstwowa nawigacje
- wdrozyc nowy mega footer w warstwie public/app
- zunifikowac spacing, border radius i CTA classes w [app.css](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/css/app.css)

Definition of done:
- kazda strona user-facing korzysta z jednego spójnego shellu
- header i footer sa identyczne kompozycyjnie na calym froncie

### P1: Landing / login / register
- przebudowac [Home.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Home.vue) pod shell `hero + demo + proof + categories + support`
- przebudowac [Auth/Login.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Auth/Login.vue) na split-shell auth
- dopasowac [Auth/Register.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Auth/Register.vue) do tego samego modelu

Definition of done:
- landing przypomina duzy produkt, nie pojedynczy splash page
- auth nie wyglada juz jak techniczny formularz

### P2: Dashboard and analytics
- przebudowac [Dashboard.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Dashboard.vue) na `stats + modules + progression`
- przebudowac [Analytics/Categories/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Analytics/Categories/Show.vue) na sekcyjny shell statystyk

Definition of done:
- user po wejsciu wie od razu co zrobic dalej
- widoki wygladaja jak overview duzego produktu edukacyjnego

### P3: Questions, review, hard, learning overview
- przebudowac [Questions/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Questions/Index.vue) na hybrid `overview + list + detail teasers`
- dopasowac [ReviewQueue/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/ReviewQueue/Index.vue)
- dopasowac [HardQuestions/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/HardQuestions/Index.vue)
- zrobic bardziej rail-based nawigacje tych widokow
- dodac cards lub rowy modulow zamiast samych lokalnych paneli

Definition of done:
- listy maja spójny rytm, filtry i CTA
- nie sa przypadkowymi kartami o roznej wadze

### P4: Session shell
- przebudowac [StudySessions/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- rozdzielic lewa scene pytania od prawego railu
- poprawic hierarchie odpowiedzi, timera, postepu i feedbacku
- wprowadzic wyrazny result band po zakonczeniu
- wprowadzic grid pytan do review
- wprowadzic sekcje `Wyjasnienie`, `FAQ`, `Feedback`

Definition of done:
- ekran zachowuje sie i wyglada jak prawdziwy modul nauki/egzaminu
- sesja ma najmocniejsza hierarchie z calego produktu
- wynik i review wygladaja jak naturalna kontynuacja sesji, nie oddzielna strona

### P5: Utility / profile / billing
- uproscic i ustrukturyzowac [Profile/Edit.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Profile/Edit.vue)
- zrobic utility/account shell zgodny z reszta produktu
- przygotowac osobny ekran statusu planu lub dostepu
- dodac boczne karty akcji typu `statystyki`, `pakiet`, `ustawienia`

### P6: Future learning verticals
- jesli uruchomimy dluzsze moduly nauki, oprzec je o shell:
  - `left curriculum rail`
  - `main player / reader surface`
  - `next step / checkpoint`
- nie wymyslac wtedy nowego layoutu od zera

## 7. Komponenty do zbudowania lub ujednolicenia

### Global
- `PublicHeaderPrimary`
- `PublicHeaderSecondary`
- `MegaFooter`
- `SectionHeading`
- `PromoBand`
- `ProofStrip`

### Catalog / dashboard
- `MetricTile`
- `ProgressCard`
- `ModuleOverviewCard`
- `SidebarProgressRail`
- `CategoryPill`
- `QuestionRow`

### Session
- `QuestionStage`
- `QuestionMetaRail`
- `AnswerBandButton`
- `SessionProgressRail`
- `ResultBand`
- `QuestionReviewGrid`
- `ExplanationBox`
- `QuestionFaqAccordion`
- `ExplanationFeedbackForm`

### Learning modules
- `LearningRail`
- `LearningModuleCard`
- `LearningPlayerSurface`
- `ReaderSurface`
- `CheckpointActions`

### Trust / proof
- `ReviewCard`
- `ReviewWall`
- `AppStoreProofRow`
- `ContactSupportBand`

## 8. UX wymagania niefunkcjonalne
- mobile-first ma byc traktowany serio, nie jako pomniejszona desktopowa siatka
- strony maja byc gestsze niz obecnie, ale nadal czytelne
- CTA musza byc konsekwentne w calym systemie
- kazda strona ma miec jeden dominujacy cel
- footer ma domykac produkt, a nie byc tylko lista linkow
- po kliknieciu w odpowiedz lub wykonaniu akcji layout nie moze "skakac"
- wynik, review i wyjasnienie maja byc czescia tego samego flow

## 9. Ryzyka
- zbyt doslowne kopiowanie moze zrobic portalowy chaos
- zbyt duza ilosc sekcji na landing page moze rozwodnic produkt
- skopiowanie zieleni/yellow bez umiaru moze wygladac tanio
- brak porzadku shelli sprawi, ze skopiujemy tylko powierzchnie, nie system
- zbyt dlugie przepisywanie SEO sections z PJ360 moze zabic czytelnosc naszego produktu
- brak silnego shellu sesji sprawi, ze nawet dobry landing nie uratuje experience'u

## 10. Decyzje projektowe na start
- kopiujemy `system layoutow`, nie brand
- kopiujemy `kompozycje`, nie copy
- kopiujemy `gestosc informacji`, ale czyścimy nadmiar
- zaczynamy od shelli, potem dopiero od pojedynczych komponentow

## 11. Rekomendowana kolejnosc implementacji
1. `app.css` + global shell
2. landing + auth
3. dashboard + analytics
4. questions + review + hard
5. session shell + result/review shell
6. profile + billing utility shell
7. future learning verticals, jesli produktowo beda potrzebne

Jesli celem najblizszego sprintu jest maksymalna zgodnosc z najwazniejszym wzorcem PJ360, mozna swiadomie odwrocic punkt 4 i 5:

1. `app.css` + global shell
2. landing + auth
3. session shell + result/review shell
4. dashboard + analytics
5. questions + review + hard
6. profile + billing utility shell
7. future learning verticals

To jest sensowna opcja, bo najcenniejszym wzorcem calego audytu pozostaje shell sesji egzaminacyjnej i review.

## 12. Definition of success
Projekt uznamy za udana adaptacje, jesli:

- uzytkownik po wejściu na nasza strone ma wrazenie duzego, dojrzalego produktu edukacyjnego
- wszystkie glówne widoki sprawiaja wrazenie jednego systemu
- session screen wyglada jak glowny modul produktu, nie zwykly formularz
- wynik i review po sesji wygladaja jak realny moduł nauki, a nie doklejony summary screen
- dashboard i listy sa bardziej `produktowe`, mniej `developerskie`
- nie kopiujemy wprost cudzej identyfikacji, ale widać to samo uporzadkowanie i skale
