# Redesign glownego komponentu `/nauka`

## Cel redesignu

Ekran `/nauka` ma byc prywatnym centrum wyboru trybu nauki. Uzytkownik powinien w kilka sekund zrozumiec:

1. jaka kategoria jest aktywna,
2. jaki tryb moze teraz uruchomic,
3. co stanie sie po kliknieciu glownego przycisku.

Obecny widok dziala funkcjonalnie, ale miesza decyzje pierwszego poziomu z ustawieniami technicznymi sesji. Redesign rozdziela te warstwy: najpierw wybor sciezki, potem konfiguracja tylko dla trybow, ktore jej potrzebuja.

## Problem, ktory rozwiazujemy

Aktualny komponent jest zbyt obciazony poznawczo. Na jednym ekranie konkuruja:

- Trener pamieci,
- Znaki drogowe,
- Nauka klasyczna,
- Zen mode,
- Egzamin,
- Tryb rankingowy,
- wybor kategorii,
- status pytan,
- zakres pytan,
- dzial,
- kolejnosc,
- lista dzialow ponizej.

Najwiekszy problem UX: uzytkownik widzi formularz zanim zdecyduje, po co tu przyszedl. To spowalnia start nauki i oslabia intuicyjnosc aplikacji.

## Persony

### Kursant poczatkujacy

Chce zaczac nauke bez rozumienia mechaniki aplikacji. Potrzebuje jasnej kolejki: znaki, pytania, powtorki.

### Kursant wracajacy

Wie, czego chce. Oczekuje szybkiego wejscia do nauki klasycznej, trenera pamieci albo znakow bez czytania opisow.

### Kursant przed egzaminem

Potrzebuje trybu egzaminacyjnego i powtorek. Nie chce przypadkowo uruchomic zlego trybu.

### Uzytkownik PJM starter

Ma widziec modul PJM jako glowna sciezke. Pozostale tryby maja byc wyszarzone i prowadzic do aktywacji pelnej nauki.

## User flow

### Standardowy uzytkownik z pelnym dostepem

1. Wchodzi na `/nauka`.
2. Widzi kontekst: panel nauki i aktywna kategoria.
3. Wybiera sciezke: Znaki, Nauka klasyczna, Zen, Egzamin, Trener pamieci albo Ranking.
4. Jesli wybral Nauke klasyczna lub Zen, widzi konfigurator sesji.
5. Jesli wybral Egzamin, widzi proste zasady egzaminu i start.
6. Jesli wybral Znaki / Trener pamieci / Ranking, dostaje pojedyncza akcje wejscia.
7. Uruchamia tryb bez kontaktu z ustawieniami, ktore nie dotycza wybranej sciezki.

### Uzytkownik PJM starter

1. Wchodzi na `/nauka`.
2. Widzi duzy kafel PJM i jasne skierowanie do tego modulu.
3. Widzi pozostale tryby jako niedostepne.
4. Moze wejsc do PJM albo aktywowac pelna nauke.

## Design decisions

### `/nauka` jako launcher, nie formularz

Pierwsza decyzja uzytkownika brzmi: "co chce teraz robic?". Dopiero druga: "jak ustawic sesje?". To zmniejsza obciazenie poznawcze.

### Znaki drogowe przed Nauka klasyczna

Znaki drogowe powinny byc pierwszym kaflem po lewej. To komunikuje naturalna kolejnosc nauki: najpierw poznajesz znaki, potem pytania egzaminacyjne.

### Kategoria jako kontekst, nie centralny wybor

Kategoria jest zablokowana dla zwyklego uzytkownika po przypisaniu. Dlatego nie powinna wygladac jak glowny mechanizm strony. W redesignie bedzie kompaktowym kontekstem w naglowku.

Aktualizacja: w kolejnym przebiegu usunieto widoczny naglowek "Panel nauki" oraz pasek kategorii z pierwszego ekranu. Kategoria nadal zostaje w kontrakcie danych i formularzu startu sesji, ale nie jest pokazywana jako element wyboru na `/nauka`, bo mogla sugerowac uzytkownikowi mozliwosc swobodnej zmiany kategorii.

### Konfigurator tylko dla trybow, ktore go potrzebuja

Nauka klasyczna i Zen potrzebuja wyboru dzialu/statusu/zakresu. Egzamin, Znaki, Trener pamieci i Ranking nie powinny pokazywac tych samych kontrolek, bo to wprowadza falszywe oczekiwania.

### Jeden system wizualny

Uzywamy spokojnej palety: biel, jasne szarosci, czern tekstowa i jeden akcent niebieski `#023ea4`. Radiusy i cienie maja byc ograniczone, zblizone do stylu `/cennik` i innych dojrzalszych widokow.

### Inspiracja gov.pl bez kopiowania portalu

Glowny "Nastepny krok" ma dzialac jak mocny pas informacyjny: niebieskie tlo, subtelne zdjecie pod spodem i jedna konkretna akcja. Wybór trybu przenosimy z bocznego panelu do poziomego menu pod pasem, podobnie do wzorca gov.pl, gdzie uzytkownik najpierw wybiera kontekst, a dopiero potem widzi tresc dla tego kontekstu.

Ta decyzja usuwa efekt "box in box": nie ma juz osobnej bocznej karty z trybami obok kolejnej karty konfiguratora. Jest jeden pas rekomendacji, jedno menu trybow i jeden panel aktywnej sciezki.

### Mobile-first

Na mobile kafle trybow ukladaja sie w jedna kolumne albo dwie zwarte kolumny, a konfigurator pokazuje sie pod wyborem trybu. Tap targety minimum okolo 44 px wysokosci.

## Komponenty / obszary do zmiany

- `resources/js/Pages/Session/Index.vue`
  - przebudowa hierarchii template,
  - dodanie stanu aktywnej sciezki,
  - oddzielenie kafli trybow od konfiguratora,
  - uproszczenie tekstow,
  - zachowanie istniejacych formularzy i endpointow.

Nie zmieniamy w pierwszym przebiegu:

- `SessionPageController`,
- `StudySessionController::store`,
- `StudyContextService`,
- routingu startu sesji,
- mechaniki blokady kategorii.

## Plan wdrozenia

1. Dodac dokument projektowy i zachowac aktualny kontrakt danych.
2. W `Session/Index.vue` dodac typ `LearningPath` i stan `selectedPath`.
3. Zastapic gorna strukture formularza ukladem:
   - naglowek kontekstowy,
   - pas rekomendowanego kroku,
   - poziome menu sciezek inspirowane gov.pl,
   - panel akcji wybranej sciezki,
   - szybki wybor dzialow ponizej.
4. Zostawic `startLearning`, `activateFullLearning`, `rankingModeHref`, `trafficSignLearningHref` i logike PJM bez zmiany endpointow.
5. Przepiac preset klasyczny/zen/egzamin na nowy wybor sciezki.
6. Zachowac liste dzialow jako dolny modul "Szybki start z dzialu".
7. Uruchomic `npm run build`.
8. Manualnie sprawdzic:
   - pelny dostep,
   - PJM starter,
   - brak dostepu,
   - mobile breakpoint.

## Ryzyko

### Bezpieczne zmiany

- teksty,
- hierarchia template,
- klasy CSS/Tailwind,
- kolejnosc kafli,
- ukrywanie konfiguratora w zaleznosci od wybranego trybu.

### Wymaga testow

- start sesji klasycznej,
- start Zen mode,
- start egzaminu,
- link do znakow,
- link do trenera pamieci,
- link do rankingu,
- PJM starter i wyszarzenie platnych trybow.

### Breaking changes

Nie przewidujemy breaking changes, jesli zachowamy:

- nazwy propsow,
- `sessionForm.post(route('study-sessions.store'))`,
- `profile.product.update`,
- istniejace route names,
- logike `canUseFullProduct` i `isPjmStarterMode`.

## Definition of done

Redesign uznajemy za udany, gdy:

- pierwszy ekran jasno pokazuje dostepne sciezki nauki,
- uzytkownik nie widzi ustawien, ktore nie dotycza wybranego trybu,
- Znaki drogowe sa pierwszym krokiem przed Nauka klasyczna,
- kategoria jest widoczna jako kontekst, ale nie dominuje ekranu,
- PJM starter dalej pokazuje PJM jako glowna sciezke i blokuje tryby platne,
- start Nauki klasycznej, Zen i Egzaminu dziala jak przed redesignem,
- `/nauka` jest czytelne na mobile bez przewijania przez duzy formularz przed wyborem trybu,
- `npm run build` przechodzi bez bledow.

## Status wdrozenia

Wykonane w branchu `codex/learning-main-component-redesign`:

- przebudowano `resources/js/Pages/Session/Index.vue` z formularza pierwszego widoku na launcher sciezek nauki,
- dodano sekcje "Nastepny krok", ktora prowadzi uzytkownika do rekomendowanej akcji,
- sekcja "Nastepny krok" dostala niebieski pas z subtelnym zdjeciem w tle i pojedynczym panelem akcji,
- boczny wybor trybow zastapiono poziomym menu pod pasem rekomendacji,
- tryby Znaki drogowe, Trener pamieci i Ranking maja w menu swoje lekkie panele akcji zamiast przepinac uzytkownika od razu po kliknieciu w zakladke,
- Znaki drogowe ustawiono jako pierwszy krok przed Nauka klasyczna,
- kategorie przeniesiono do kompaktowego kontekstu w naglowku,
- nastepnie ukryto naglowek i widoczny pasek kategorii, zeby pierwszy ekran zaczynal sie od rekomendowanego kroku, a kategoria nie wygladala jak przełącznik dostepny dla kursanta,
- konfigurator dzialow/statusow pokazuje sie tylko dla Nauki klasycznej i Zen,
- Egzamin ma osobny prosty panel zasad i startu,
- PJM starter nadal pokazuje duzy kafel PJM oraz wyszarza tryby platne,
- zostawiono istniejace endpointy startu sesji bez zmian,
- dodano lekki backendowy licznik `learning_overview.due_review_count` dla rekomendacji trenera pamieci,
- `npm run build` przechodzi poprawnie.

Logika "Nastepnego kroku" w pierwszym przebiegu:

1. PJM starter -> modul PJM,
2. brak pelnego dostepu -> aktywacja pelnej nauki,
3. brak rozpoczetych pytan -> Znaki drogowe,
4. zalegle powtorki -> Trener pamieci,
5. pierwszy dzial z nieprzerobionymi pytaniami -> Nauka klasyczna,
6. pierwszy dzial z bledami -> poprawka dzialu,
7. brak zaleglosci -> Egzamin.

Do manualnego QA:

- klik startu Nauki klasycznej na zalogowanym koncie,
- klik startu Zen mode,
- klik startu Egzaminu,
- wejscie do Znakow drogowych,
- wejscie do Trenera pamieci,
- wejscie do Rankingu,
- wariant PJM starter,
- mobile breakpoint.
