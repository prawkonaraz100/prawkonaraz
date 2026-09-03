# UI Audit: ZdamyTo

Data audytu: 2026-03-26  
Zakres: analiza kompozycji, układu, hierarchii, komponentów i wzorców interfejsu serwisu [ZdamyTo](https://www.zdamyto.com/) po zalogowaniu.

Artefakty wizualne:
- [03-home.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/03-home.png)
- [04-pricing.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/04-pricing.png)
- [05-exam-initial.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/05-exam-initial.png)
- [06-exam-question.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/06-exam-question.png)
- [07-learning-overview.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/07-learning-overview.png)
- [08-learning-question.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/08-learning-question.png)
- [09-review-overview.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/09-review-overview.png)
- [10-review-question.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/10-review-question.png)
- [11-ecourse-overview.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/11-ecourse-overview.png)
- [12-ecourse-lesson.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/12-ecourse-lesson.png)
- [13-ebook.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/13-ebook.png)
- [14-question-list.png](/Users/xxx/Desktop/serwistestyprawojazdy/output/zdamyto-audit/14-question-list.png)

## Streszczenie
ZdamyTo nie jest wizualnie nowoczesnym, minimalistycznym produktem. Jest za to bardzo spójne systemowo. Cały serwis opiera się na kilku powtarzalnych shellach i bardzo konsekwentnym rytmie:

- cienki utility bar,
- biały lub jasnoszary główny shell,
- prostokątne sekcje z lekkim cieniem,
- cięższa typografia nagłówków,
- mocno funkcjonalny układ informacji,
- bardzo czytelny podział na tryby nauki.

Najważniejsza cecha do przeniesienia nie brzmi "kolory", tylko:  
`każdy ekran od razu mówi, w jakim trybie jesteś i jaki jest następny ruch`.

## Audytowane obszary
- public landing page
- pricing page
- profile / account area
- statistics / progress area
- question catalog list
- learning overview
- learning session screen
- review overview
- review session screen
- exam session screen
- e-course overview
- e-course lesson screen
- ebook / resources page

## Główne shelle interfejsu

### 1. Marketing shell
Występuje na:
- landing
- pricing
- ebook
- listy publiczne / sprzedażowe

Charakterystyka:
- czarny lub bardzo ciemny utility bar u góry
- biały główny pasek nawigacyjny z logo i pięcioma głównymi wejściami
- szerokie sekcje jedna pod drugą
- dużo modułów o charakterze sprzedażowym
- CTA i claimy powtarzane wielokrotnie

To nie jest hero-driven landing w stylu nowoczesnego SaaS. To raczej:
- hero,
- moduły funkcjonalne,
- proof,
- wideo,
- opinie,
- app promo,
- ebook promo,
- blog,
- partnerzy / szkoły,
- kategorie.

Wniosek:
- kompozycja jest długa i ciężka,
- ale system jest czytelny, bo sekcje mają jasno rozdzielone role.

### 2. Learning catalog shell
Występuje na:
- nauka overview
- zaliczenie overview
- listy pytań
- statystyki

Charakterystyka:
- ten sam górny shell co w marketingu
- środek strony to lista modułów / działów
- każdy dział ma ten sam zestaw elementów:
  - miniaturę,
  - tytuł,
  - liczbę pytań,
  - podstawowe CTA,
  - linki trudność / filtrowanie,
  - czasem film

Wniosek:
- to nie jest dashboard z kartami,
- to jest katalog działań,
- rytm opiera się na pionowej powtarzalności.

### 3. Session shell
Występuje na:
- egzamin
- nauka pytania
- zaliczenie pytania

To najważniejszy shell w całym systemie.

Struktura:
- cienki jasnoszary top bar z utility controls
- główna scena pytania w dwóch kolumnach
- lewa kolumna:
  - pasek działu / punktów
  - media lub placeholder
  - prompt
  - odpowiedzi
- prawa kolumna:
  - zakończenie sesji
  - licznik pytań
  - timer / progress
  - start
  - akcje pomocnicze

Wniosek:
- interfejs sesji jest niemal "urządzeniem egzaminacyjnym",
- ma mało ozdobników,
- bazuje na dużych kontrastowych przyciskach akcji,
- ma bardzo prosty model percepcyjny.

### 4. E-course shell
Występuje na:
- overview e-kursu
- pojedyncza lekcja

To oddzielny produkt w środku produktu.

Charakterystyka:
- osobna nawigacja
- pełne wykorzystanie media slide
- treść lekcji pod obrazem
- bardzo wyraźny pasek postępu i nawigacji na dole
- interfejs bliższy prezentacji / slideshow niż klasycznemu dashboardowi

Wniosek:
- e-kurs nie próbuje wyglądać jak ten sam ekran co egzamin,
- ma swój własny shell, ale zachowuje tę samą logikę prostoty i sterowania.

## System hierarchii

### Tożsamość informacji
ZdamyTo dobrze rozdziela 4 poziomy informacji:
- globalny tryb produktu,
- bieżący tryb ekranu,
- moduł / dział,
- konkretna akcja użytkownika.

Przykład:
- górna nawigacja: `Testy`, `Nauka`, `Zaliczenie`, `Książki`, `E-kurs`
- nagłówek strony: `Znaki ostrzegawcze`
- karta / wiersz: `Lista pytań`, `Rozpocznij naukę`, `Obejrzyj film`
- ekran sesji: `Tak / Nie`, `Start`, `Następne pytanie`

To daje bardzo dobrą orientację.

### Rytm sekcji
Powtarzalny rytm wygląda tak:
- krótki nagłówek sekcji,
- jeden akapit objaśnienia,
- lista działań albo powtarzalny blok kart/wierszy,
- prosty CTA.

Nie ma tu dużej abstrakcji ani eksperymentów kompozycyjnych.  
System działa, bo użytkownik natychmiast rozpoznaje wzorzec.

## Kolorystyka

### Potwierdzone twarde sygnały z frontu
Font bazowy:
- `Open Sans`

Tło bazowe:
- `rgb(255, 255, 255)` lub jasny neutralny szary

Marketing / link accent:
- jasny cyan około `rgb(5, 181, 232)`

Wybrane state / category:
- fioletowo-niebieski około `rgb(146, 146, 244)`

Session shell:
- primary blue około `rgb(13, 71, 161)`
- warning yellow około `rgb(255, 193, 7)`
- success lime około `rgb(149, 219, 9)`
- muted gray button około `rgb(108, 117, 125)`

### Ocena systemu koloru
ZdamyTo nie ma jednej wyrafinowanej palety brandowej. Ma raczej praktyczny zestaw:
- ciemny utility bar,
- biały shell,
- niebieski dla działań głównych,
- żółty dla działań krytycznych / następnego ruchu,
- zielony / lime dla statusów poprawności,
- cyan jako marketing link accent.

Wniosek:
- to nie jest piękna paleta,
- ale jest skuteczna poznawczo,
- szczególnie dobrze działa w sesji egzaminacyjnej.

## Typografia

Charakter:
- bardzo użytkowa,
- prosta,
- bez estetycznego napięcia,
- ale czytelna.

Wzorce:
- duże, ciężkie H1/H2 w marketingu
- mniejsze, techniczne nagłówki w appce
- dużo uppercase labels i mikrolabeli
- w sesji pytania same odpowiedzi są wizualnie większe od wielu opisów pomocniczych

Wniosek:
- dla naszego produktu warto przejąć hierarchię,
- nie trzeba przejmować całej surowości estetycznej.

## Komponenty powtarzalne

### Top utility bar
Rola:
- linki pomocnicze,
- szybki dostęp do kategorii,
- konto,
- promocja.

### Main nav
Rola:
- pięć głównych obszarów produktu,
- każdy z dwuliniowym opisem.

To bardzo ważne: główne wejścia są opisane nie tylko nazwą, ale też funkcją.

### Service / feature module card
W marketingu:
- mały obrazek lub ikonę,
- średni nagłówek,
- akapit,
- link tekstowy.

### Learning section row
W katalogach:
- miniatura,
- tytuł działu,
- liczba pytań,
- primary CTA,
- secondary CTA,
- film,
- filtr trudności.

### Session control stack
Na ekranach pytań:
- prominent action
- status / progress
- secondary utilities
- odpowiedzi na dole

### Dense data tables
W statystykach i koncie:
- dane tabelaryczne,
- alert box,
- małe wykresy/progress,
- dużo drilldownów.

## Mocne strony ZdamyTo
- Bardzo wyraźne rozdzielenie trybów produktu.
- Bardzo dobra orientacja użytkownika.
- Ekran sesji jest prosty i skuteczny.
- Katalog nauki i zaliczeń ma bardzo dobry "operacyjny" rytm.
- Każdy ważny obszar ma własny shell, ale całość jest spójna.

## Słabe strony ZdamyTo
- Marketing jest przeładowany.
- Za dużo sekcji i za dużo powtórzeń claimów.
- Kolorystyka jest funkcjonalna, ale nie premium.
- Session UI jest skuteczne, ale wizualnie twarde i stare.
- Statystyki są czytelne, ale mało eleganckie i bardzo gęste.

## Co przenosimy do naszego produktu

### Przenosimy
- dwupoziomowy shell nawigacji,
- bardzo wyraźny podział na tryby produktu,
- katalogowy układ `dział -> akcje -> filtrowanie`,
- egzaminowy shell `scene + sidebar controls`,
- utility-first copy na ekranach produktowych,
- większą dyscyplinę sekcji i hierarchii.

### Nie przenosimy 1:1
- nadmiaru claimów marketingowych,
- nadmiaru sekcji blogowych / SEO na landing page,
- zbyt starej estetyki cieni i gradientów,
- czysto portalowego zagęszczenia,
- przypadkowego miksu cyan / lime / purple w całym produkcie.

## Decyzja projektowa dla naszego projektu
Nasza adaptacja powinna być:
- `systemowo jak ZdamyTo`,
- `czyściej i nowocześniej niż ZdamyTo`,
- `bardziej restrained niż ZdamyTo`,
- `bez rozlewania marketingu na ekrany robocze`.

To oznacza:
- landing bierze z ZdamyTo układ i sprzedażową logikę,
- dashboard i katalog biorą z ZdamyTo katalogowy rytm,
- sesja bierze z ZdamyTo shell egzaminacyjny,
- ewentualny e-kurs później dostanie osobny shell,
- ale wizualnie pilnujemy większej dyscypliny niż oryginał.

## Mapowanie na nasz projekt

### Home
Ma przejąć:
- utility bar,
- główną nav z obszarami produktu,
- hero z obietnicą i jednoznacznym CTA,
- moduły produktu zamiast dekoracyjnych feature cards,
- sekcje proof / opinie / kategorie.

### Authenticated layout
Ma przejąć:
- dwuwarstwowy header,
- wyraźne główne wejścia,
- mniejsze znaczenie dropdownu konta,
- silniejszą orientację trybów.

### Dashboard
Ma przejść z card-mosaic do:
- summary + quick actions,
- tabelaryczno-operacyjnego układu,
- jasnego rozdziału: testy, nauka, review, trudne.

### Questions / Review / Hard
Mają przejąć:
- układ podobny do `nauka / zaliczenie overview`,
- powtarzalne wiersze modułów,
- mniejszy nacisk na duże dekoracyjne karty,
- więcej utility i skanowalności.

### Study session
Ma przejąć:
- egzaminowy shell z prawdziwym podziałem lewa scena / prawa kontrola,
- dużo prostszą powierzchnię,
- mocniejsze CTA,
- niższy poziom ozdobników.

## Wniosek końcowy
Największa przewaga ZdamyTo nie wynika z piękna, tylko z dyscypliny produktu.  
Użytkownik cały czas wie:
- gdzie jest,
- co robi,
- co może kliknąć dalej.

To właśnie powinniśmy skopiować najwierniej.
