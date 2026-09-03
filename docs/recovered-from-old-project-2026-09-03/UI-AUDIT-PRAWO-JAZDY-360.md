# UI Audit: Prawo-Jazdy-360

Data audytu: 2026-03-26  
Status audytu: szosty, ostateczny coverage pass; praktycznie pelne pokrycie shelli, stanów i glownego checkoutu dostepne na realnym koncie  
Zakres: ponowny, pelen audyt serwisu [Prawo-Jazdy-360.pl](https://www.prawo-jazdy-360.pl/testy-na-prawo-jazdy) po skutecznym logowaniu, ze szczegolnym naciskiem na:

- sesje egzaminacyjna,
- review po zakonczeniu egzaminu,
- kurs pytań,
- wyklady,
- podrecznik,
- szkolenie z instruktorem,
- statystyki,
- ekran konta i historii platnosci.

## Status dostepu

W tej iteracji audytu logowanie powiodlo sie poprawnie.

- konto zalogowane: `tomaszkulewicz@gmail.com`
- sesja prywatna dziala
- czesc produktow nie ma pelnego pakietu premium, ale serwis udostepnia wystarczajaco szerokie tryby demo, aby przejsc wszystkie kluczowe typy ekranow

Wniosek praktyczny:
- mamy juz nie tylko audyt publicznych layoutow,
- mamy audit calego systemu stron i shelli, ktore sa najwazniejsze dla naszego redesignu.

## Metodologia i poziom pewnosci

Ten dokument opiera sie na szesciu passach audytu:

- pass 1: warstwa publiczna i glowne strony marketingowo-produktowe,
- pass 2: czesc prywatna po logowaniu i glowne shelle nauki,
- pass 3: rygorystyczny rerun po zalogowaniu, z naciskiem na macierz pokrycia tras i stany sesji `exam -> result -> review`.
- pass 4: finalny pass defensywny, skupiony na rzeczach najlatwiejszych do pominiecia: dropdownach, mobile, widgetach wsparcia, shellach contentowych i stronach katalogowych spoza glownego flow nauki.
- pass 5: delta pass skupiony na rzeczach, ktore nadal mogly umknac przy adaptacji naszego UI: utility actions w sesji, modale pytan, detail pytania z bazy, publiczne selektory kategorii/jezyka, shell forum oraz dokladny review detail z explanation stackiem.
- pass 6: coverage pass skupiony juz nie na nowych hipotezach, tylko na rzeczach granicznych: `cennik`, `platnosc`, `kontakt`, `opinie`, watek forum, dropdown konta oraz policzenie pokrycia po rodzinach ekranow zamiast po przypadkowych pojedynczych URL-ach.

W trzecim i czwartym passie zostaly potwierdzone:
- trasy odkryte z realnego headera, dropdownow i linkow same-origin,
- glowne ekrany po zalogowaniu,
- kluczowe stany interakcyjne sesji egzaminacyjnej,
- przynajmniej jeden detail lub player dla kazdego glownego modulu edukacyjnego,
- utility screens i shell konta,
- dropdown `Moje konto`,
- dropdown `Baza wiedzy`,
- floating widget pomocy z osobnym formularzem kontaktowym,
- shell mobile dla home i `testy-na-prawo-jazdy`,
- shell knowledge-base article,
- shell katalogowo-rankingowy.
- shell forum/community,
- publiczny selector kategorii i selector jezyka,
- detail pytania z bazy egzaminacyjnej,
- modal `Zadaj pytanie`,
- pelny detail review po kliknieciu numeru pytania po egzaminie,
- shell `cennik`,
- shell `platnosc` / checkout pre-gateway,
- shell `kontakt`,
- listing `opinie-o-prawo-jazdy-360`,
- watek forum z paginacja i edytorem odpowiedzi,
- dropdown `Moje konto` z potwierdzonym zestawem linkow.

Poziom pewnosci:
- `bardzo wysoki` dla architektury layoutow, shelli i przeplywow ekranow,
- `wysoki` dla sesji egzaminacyjnej, review i modulu kursu,
- `wysoki` dla wariantow premium, ktorych konto nie odblokowuje w calosci, ale ktorych shell zostal potwierdzony przez demo, `cennik` i checkout pre-gateway.

Granice audytu:
- nie potwierdzono kazdego pojedynczego micro-state'u platnego kontentu, jesli serwis ukrywa go za dodatkowym upsellem,
- nie probowano enumerowac losowych, nieskonczonych kombinacji query params,
- celem byl kompletny audyt systemu UI i wszystkich glownych shelli, a nie reverse engineering prywatnej logiki biznesowej.
- nie przechodzono przez zewnetrzna platnosc operatora (`/zaplac`, PayU), bo to byloby juz realnym flow transakcyjnym.

## Pokrycie koncowe

### Pokrycie praktyczne shelli i stanów: `100%`

Na potrzeby redesignu i implementacji UI liczymy pokrycie po rodzinach ekranow i stanach produktu, a nie po kazdym pojedynczym paginowanym URL-u.

Potwierdzone zostalo `40/40` kluczowych shelli lub stanów:

1. home / public landing  
2. public header shell  
3. mega footer  
4. public selector kategorii  
5. public selector jezyka  
6. dropdown `Moje konto`  
7. floating help widget  
8. mobile home shell  
9. mobile `testy-na-prawo-jazdy` shell  
10. public `testy-na-prawo-jazdy` landing  
11. sesja egzaminacyjna `in-progress`  
12. sesja po zaznaczeniu odpowiedzi  
13. wynik egzaminu  
14. review detail po wyniku  
15. modal `Zadaj pytanie`  
16. publiczna lista pytan egzaminacyjnych  
17. publiczny detail pytania  
18. `kurs` overview  
19. `kurs` detail grupy pytan  
20. `wyklady` overview  
21. `wyklady` player/detail  
22. `podrecznik` overview  
23. `podrecznik` detail chapter  
24. `szkolenie z instruktorem` overview  
25. `szkolenie z instruktorem` detail lesson  
26. `statystyki`  
27. `ustawienia`  
28. `historia platnosci`  
29. `cennik`  
30. `platnosc` / checkout pre-gateway  
31. sekcja instrukcji platnosci  
32. `kontakt`  
33. FAQ/support page shell  
34. `opinie-o-prawo-jazdy-360`  
35. `ranking-szkol-jazdy`  
36. knowledge article shell (`znaki-drogowe`)  
37. `forum` listing  
38. `forum/kategoria/...` category shell  
39. `forum/watek/...` thread shell  
40. breadcrumb + paginacja + content-heavy repeatable shells

Wniosek:
- dla celu projektowego, kompozycyjnego i implementacyjnego mamy komplet.
- mozemy traktowac ten audyt jako `100% coverage` dla przebudowy naszego UI.

### Pokrycie wykrytych sciezek: `100% rodzin tras`, nie `100% pojedynczych instancji`

W coverage passie wykryto `170` sciezek same-origin z glownych modulow (`home`, `testy`, `kurs`, `wyklady`, `podrecznik`, `cennik`, `forum`).

Uczciwa interpretacja:
- `100%` rodzin tras i typow ekranow mamy pokryte,
- `nie ma sensu` recznie otwierac kazdego pojedynczego artykulu, kazdej strony opinii i kazdej strony forum, jesli uzywaja tego samego shellu,
- dlatego nie twierdzimy, ze obejrzano `170/170` osobnych stron instancyjnych, tylko ze domknieto `wszystkie istotne rodziny ekranow`.

To jest poziom pokrycia, ktory jest profesjonalnie wystarczajacy do implementacji.

## Artefakty wizualne

### Public / pierwszy pass
- [01-logowanie.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/01-logowanie.png)
- [02-kurs.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/02-kurs.png)
- [03-wyklady.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/03-wyklady.png)
- [04-podrecznik.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/04-podrecznik.png)
- [05-statystyki.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/05-statystyki.png)
- [06-cennik.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/06-cennik.png)
- [07-ranking-szkol-jazdy.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/07-ranking-szkol-jazdy.png)
- [08-pytania-egzaminacyjne.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/08-pytania-egzaminacyjne.png)
- [09-opinie.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/09-opinie.png)
- [10-pytanie-detail.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/10-pytanie-detail.png)
- [11-szkola-detail.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit/11-szkola-detail.png)

### Authenticated / drugi pass
- [test-session-initial.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/test-session-initial.png)
- [test-session-answered.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/test-session-answered.png)
- [test-session-result.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/test-session-result.png)
- [course-demo-overview.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/course-demo-overview.png)
- [course-group-detail.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/course-group-detail.png)
- [lectures-overview.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/lectures-overview.png)
- [lecture-player.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/lecture-player.png)
- [handbook-overview.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/handbook-overview.png)
- [handbook-chapter.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/handbook-chapter.png)
- [training-overview.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/training-overview.png)
- [training-lesson.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/training-lesson.png)
- [stats-overview.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/stats-overview.png)
- [profile-settings.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/profile-settings.png)
- [payment-history.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/payment-history.png)

### Authenticated / trzeci pass - coverage confirmation
- [test-session-pass3-answered.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/test-session-pass3-answered.png)
- [test-session-pass3-result.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/test-session-pass3-result.png)
- [test-session-pass3-review.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/test-session-pass3-review.png)
- [question-bank-list.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/question-bank-list.png)
- [course-demo-overview-pass3.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/course-demo-overview-pass3.png)
- [stats-overview-pass3-actual.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/stats-overview-pass3-actual.png)
- [payment-history-pass3.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/payment-history-pass3.png)

### Authenticated / czwarty pass - omissions check
- [home-mobile-pass4.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/home-mobile-pass4.png)
- [test-session-mobile-pass4.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/test-session-mobile-pass4.png)
- [ranking-shell-pass4.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/ranking-shell-pass4.png)
- [knowledge-shell-pass4.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/knowledge-shell-pass4.png)

### Authenticated / piaty pass - delta confirmation
- [question-detail-pass5.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/question-detail-pass5.png)
- [category-dropdown-pass5.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/category-dropdown-pass5.png)
- [pass5-language-dropdown.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/pass5-language-dropdown.png)

### Authenticated / szosty pass - coverage confirmation
- [pricing-pass6.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/pricing-pass6.png)
- [checkout-pass6.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/checkout-pass6.png)
- [contact-pass6.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/contact-pass6.png)
- [forum-thread-pass6.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/forum-thread-pass6.png)
- [opinions-pass6.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/prawojazdy360-audit-auth/opinions-pass6.png)

## Macierz pokrycia

### Trasy i shelle potwierdzone bezposrednio
- `public shell`: `testy-na-prawo-jazdy`, `kurs`, `wyklady`, `podrecznik-kursanta`, `szkolenie-z-instruktorem`, `statystyki`, `cennik`, `ranking szkol jazdy`, `pytania egzaminacyjne`
- `auth utility shell`: `ustawienia`, `historia-platnosci`, dropdown `Moje konto`
- `exam shell`: in-progress, answered state, end-exam result state, review state po kliknieciu numeru pytania
- `learning shells`: kurs overview, kurs detail, lecture player, handbook detail, training lesson
- `stats shell`: overview statystyk po zalogowaniu
- `question bank shell`: listing i detail pytania egzaminacyjnego
- `question bank detail shell`: media + odpowiedzi + meta rail + paywall explanation
- `knowledge shell`: `znaki-drogowe`
- `directory shell`: `ranking-szkol-jazdy`
- `community shell`: `forum`
- `support shell`: floating help widget z formularzem kontaktowym
- `mobile shell`: home i `testy-na-prawo-jazdy`

### Stany potwierdzone w 100%
- `test -> answer selected -> end exam -> result -> review`
- `kurs overview -> grupa pytan`
- `wyklady overview -> player`
- `podrecznik overview -> detail chapter -> checkpoint`
- `szkolenie overview -> detail lesson`
- `konto -> ustawienia`
- `konto -> historia platnosci`
- `result -> review detail -> explanation -> faq -> feedback form`
- `exam question -> ask modal`
- `question detail -> locked explanation`

### Stany potwierdzone posrednio lub przez demo
- zablokowane premium explanations/cards, jesli serwis pokazuje tylko teaser `Media dostepne po wykupieniu`
- niektore szczegoly checkoutu i pelnego pakietu dostepu
- czesc content-heavy knowledge pages, ktore nie zmieniaja systemu shelli

Wniosek:
- dla naszego celu, czyli przebudowy kompozycji, layoutow, shelli i przeplywow nauki, pokrycie jest praktycznie kompletne.

## Rzeczy, ktore najlatwiej bylo pominac

### 1. Dropdown `Moje konto`
Potwierdzone elementy:
- `Profil`
- `Historia platnosci`
- `Wyloguj`

Znaczenie:
- utility nawigacja nie jest osobnym ekranem modalowym,
- to lekki dropdown doklejony do glownego shellu.

### 2. Dropdown `Baza wiedzy`
Potwierdzone elementy:
- `Opinie o zdawalnosci WORD`
- `Znaki drogowe`
- `Kodeks Drogowy`
- `Aktualnosci`
- `Punkty karne`
- `Zmiany na prawo jazdy 2026`
- `Ogolne zasady zdawania prawa jazdy`
- `Ekojazda na egzaminie`
- `Co zrobic po egzaminie`
- `Zielony listek`
- `Obowiazkowe wyposazenie samochodu`
- `Jak mozna stracic prawo jazdy`
- `Umowa kupna sprzedazy samochodu`
- `Forum`

Znaczenie:
- PJ360 ma rozbudowane otoczenie contentowe, ale wciaz domkniete w tym samym headerze produktu.

### 3. Floating help widget
Po kliknieciu pojawia sie osobna nakladka z:
- formularzem kontaktowym,
- captcha,
- numerami telefonu,
- sekcja klient indywidualny / biznesowy.

Znaczenie:
- support jest dostepny z kazdego ekranu,
- ale nie odrywa uzytkownika od glownego shellu.

### 4. FAQ accordion na stronach glownych
Potwierdzone:
- FAQ jest realnie interaktywne,
- pierwsze pytanie rozwija dluzsza odpowiedz z linkami do innych modulow.

Znaczenie:
- FAQ nie jest tylko martwa sekcja SEO,
- to aktywny komponent domykajacy pitch i kierujacy do kolejnych modulow.

### 5. Mobile
Potwierdzone:
- home ma osobny, zageszczony shell mobile,
- `testy-na-prawo-jazdy` na mobile zachowuja ten sam uklad logiczny: upsell/demo, panel sesji, pytanie, odpowiedzi, CTA, potem reszta tresci,
- header redukuje sie do zwartego top bara z triggerem menu, kontem i selectorem kategorii/jezyka.

Znaczenie:
- PJ360 nie ma osobnego produktu mobilnego,
- ten sam system shelli zostaje skompresowany do pojedynczej kolumny.

### 6. Shell bazy wiedzy
Na przykladzie `znaki-drogowe` potwierdzono uklad:
- hero tytul + intro,
- grid kategorii/tematow,
- spis tresci,
- dlugi content article,
- sidebar z popularnymi artykulami,
- CTA do glownego produktu nizej.

Znaczenie:
- knowledge pages maja osobny rytm,
- ale dalej sa podpiete pod ten sam header, footer i CTA loop do glownego produktu.

### 7. Shell rankingu
Na przykladzie `ranking-szkol-jazdy` potwierdzono uklad:
- search hero,
- liczby globalne,
- top miasta,
- dlugi katalog miast,
- stream najnowszych opinii,
- content blocks i CTA do glownego produktu.

Znaczenie:
- ranking jest osobnym wertykalem katalogowym,
- ale nadal pozostaje tym samym systemem wizualnym, a nie obca mini-aplikacja.

### 8. Publiczne selektory w headerze
Potwierdzone:
- selector kategorii z poziomu headera, z listą: `A, AM, A1, A2, B, B1, C, C1, D, D1, T`,
- selector jezyka z poziomu headera, z wariantami: `Polski`, `English`, `Deutsch`, `Українська мова`.

Znaczenie:
- PJ360 ma jeden globalny header obslugujacy nie tylko nawigacje, ale tez konfiguracje kontekstu produktu,
- to nie jest zwykla lista linkow, tylko shell z publicznymi sterownikami.

### 9. Utility actions w sesji egzaminacyjnej
Potwierdzone:
- `Zapisz pytanie`,
- `Odswiez pytanie`,
- `Zadaj pytanie`,
- `Pelny ekran`,
- `Zakoncz egzamin`.

Wazne doprecyzowanie:
- czesc utility jest w darmowym trybie zamknieta premium-tooltipem,
- utility jest jednak integralnym elementem layoutu i buduje poczucie kompletnego produktu.

### 10. Modal `Zadaj pytanie`
Potwierdzony modal/overlay zawiera:
- tytul `Zadaj pytanie`,
- tekst pomocy,
- pole wiadomosci,
- pionowy stepper/tabstrip z etapami:
  - `Twoje pytanie`
  - `Najczestsze problemy`
  - `Wyjasnienie wideo`
  - `Dodatkowe wyjasnienia`
  - `Wyslij wiadomosc`
- akcje `Przejdz dalej` i `Anuluj`.

Znaczenie:
- to nie jest prosty textarea popup,
- to mini-support workflow osadzony w samym ekranie nauki.

### 11. Detail review po egzaminie
Po kliknieciu numeru pytania po sesji pojawia sie pelny review detail z:
- panelem meta pytania po prawej,
- CTA `Zobacz wyjasnienie`,
- odpowiedziami oznaczonymi ikonami `poprawna / bledna`,
- stanem `Brak udzielonej odpowiedzi`,
- sekcja `Wyjasnienie`,
- sekcja `Wyjasnienia eksperta`,
- kartami znakow i kontekstu drogowego,
- rozbudowanym blokiem FAQ,
- formularzem feedbacku `Czy to wytlumaczenie bylo pomocne?`.

Znaczenie:
- review nie konczy sie na pokazaniu poprawnej odpowiedzi,
- review samo w sobie jest osobnym produktem edukacyjnym.

### 12. Shell forum/community
Potwierdzono osobny wertykal `forum` z:
- breadcrumbem,
- tabelopodobnymi listingami kategorii,
- licznikami `watki / posty / odslony`,
- ostatnim postem i autorem,
- osobnymi sekcjami: produkt, pomoc, szkoly jazdy, luzne tematy, partnerzy.

Znaczenie:
- PJ360 ma dodatkowa warstwe community/support/trust,
- to nie jest priorytet dla naszego MVP, ale jest istotna roznica wzgledem tego, co mamy dzisiaj.

## Mapa wykrytych tras

Podczas trzeciego passu zostala zebrana tez lista tras odkrytych bezposrednio z DOM po zalogowaniu.

### Glowny produkt
- `/testy-na-prawo-jazdy`
- `/testy-na-prawo-jazdy/pytania-egzaminacyjne`
- `/kurs`
- `/wyklady`
- `/podrecznik-kursanta`
- `/szkolenie-z-instruktorem`
- `/statystyki`
- `/cennik`
- `/ustawienia`
- `/historia-platnosci`

### Wiedza / content / trust
- `/aktualnosci`
- `/kodeks-drogowy`
- `/word`
- `/punkty-karne`
- `/prawo-jazdy`
- `/znaki-drogowe`
- `/ranking-szkol-jazdy`
- `/opinie-o-prawo-jazdy-360`
- `/kontakt`
- `/regulamin`
- `/polityka-prywatnosci`

Znaczenie:
- system ma bardzo szerokie otoczenie contentowe,
- ale trzon produktu, ktory chcemy adaptowac, miesci sie w wyraznie ograniczonej grupie shelli.

## Executive summary

Prawo-Jazdy-360 to nie jeden landing i nie jedna aplikacja. To system wielu spolaryzowanych shelli:

- publiczny shell marketingowy,
- shell sesji egzaminacyjnej,
- shell review po egzaminie,
- shell katalogu nauki,
- shell playera lekcji wideo,
- shell podrecznika,
- shell statystyk,
- shell utility/account.

Najwazniejsza lekcja z trzeciego passu:

`PJ360 nie wygrywa pojedynczym hero, tylko spojnosc calego ciagu ekranow po zalogowaniu`

To jest dla nas duzo cenniejsze niz kopiowanie samych kolorow.

## Najwazniejsze wnioski

1. Strona `testy-na-prawo-jazdy` po zalogowaniu nie jest osobna apka ani czysty landing.
   To hybryda: na gorze zywa sesja egzaminacyjna, nizej dlugi publiczny marketing i proof.

2. Ekran sesji to najwazniejszy wzorzec do adaptacji.
   Ma bardzo czytelny podzial:
   - lewa scena pytania,
   - prawa kolumna stanu,
   - jeden glowny przycisk akcji,
   - stale quick actions.

3. Wynik egzaminu i review to osobny stan tego samego URL, nie oddzielna sekcja produktu.
   To bardzo wazne, bo skraca flow i utrzymuje uzytkownika w jednej mentalnej przestrzeni.

4. Kurs, wyklady, podrecznik i szkolenie z instruktorem nie sa przypadkowymi wariantami.
   Kazdy z tych modulow jest zbudowany na powtarzalnym shellu z lewym indeksem i prawa trescia.

5. Statystyki maja funkcje motywacyjno-nawigacyjna, nie enterprise-analytics.
   Z tego powodu sa bardziej przystepne i lepiej wspieraja dalsze kroki nauki.

6. Konto i platnosci sa wizualnie proste, ale nadal wpisane w ten sam system.
   Nic nie wyglada jak obcy panel.

## Audytowane strony i typy ekranow

## 1. Testy na prawo jazdy - publiczny i zalogowany exam shell
URL: [https://www.prawo-jazdy-360.pl/testy-na-prawo-jazdy](https://www.prawo-jazdy-360.pl/testy-na-prawo-jazdy)

Rola:
- landing produktu,
- demo egzaminu,
- glowny prywatny ekran testu,
- wynik egzaminu,
- review pytan po zakonczeniu.

### 1.1 Stan poczatkowy sesji
W sklad layoutu wchodza:
- top exam bar z wartoscia punktowa, kategoria i czasem do konca,
- przycisk `Zakoncz egzamin`,
- prawa kolumna statusu:
  - pytania podstawowe,
  - pytania specjalistyczne,
  - czas na zapoznanie sie albo odpowiedz,
  - identyfikator pytania,
  - quick actions:
    - `Zapisz pytanie`
    - `Odswiez pytanie`
    - `Zadaj pytanie`
    - `Pelny ekran`
- duza scena medialna po lewej,
- tresc pytania pod medium,
- odpowiedzi na calosciowej szerokosci sceny,
- glowny CTA `Nastepne pytanie`.

Najwazniejsza obserwacja:
- odpowiedzi nie sa kartami rozrzuconymi w siatce,
- to szerokie, egzaminowe paski,
- przez to ekran wyglada bardziej jak narzedzie niz landing.

### 1.2 Stan po zaznaczeniu odpowiedzi
Po kliknieciu odpowiedzi:
- wybrany przycisk staje sie wypelniony,
- prawa kolumna zmienia label czasu z `zapoznanie` na `udzielenie odpowiedzi`,
- reszta struktury pozostaje identyczna.

Najwazniejsza lekcja:
- stan selected jest bardzo prosty,
- nie ma wyskakujacych modalow ani chaosu,
- UI pozostaje stabilne.

### 1.3 Stan wyniku egzaminu
Klikniecie `Zakoncz egzamin` nie przenosi do oddzielnego produktu.
Na tym samym URL pojawia sie:
- duzy band z wynikiem `0 z 74 pkt.`,
- komunikat sukcesu lub porazki,
- CTA `Rozwiaz nastepny test`,
- siatka numerow pytan podstawowych i specjalistycznych,
- nizej cards/statystyki wyniku,
- dopiero dalej upsell do platnego pakietu.

Najwazniejsza lekcja:
- wynik i review sa w tym samym mentalnym kontenerze co sesja,
- to buduje szybki loop: `test -> wynik -> review -> kolejny test`.

### 1.4 Review pojedynczego pytania po zakonczeniu
Po kliknieciu numeru pytania dostajemy:
- mini shell wyniku na gorze,
- nizej review konkretnego pytania,
- oznaczenie poprawnej i wybranej odpowiedzi,
- etykiete `Niepoprawna odpowiedz`,
- bardzo rozbudowany blok `Wyjasnienie`.

Blok wyjasnienia ma warstwy:
- `Wyjasnienie wideo`,
- `Wyjasnienia eksperta`,
- powiazane znaki/kodeks,
- FAQ do pytania,
- formularz feedbacku do wyjasnienia.

To jest najcenniejszy wzorzec calego serwisu.
W praktyce oznacza:

`sesja nie konczy sie na wyniku, tylko przechodzi w modul nauki`

## 2. Kurs - learning catalog shell
URL: [https://www.prawo-jazdy-360.pl/kurs](https://www.prawo-jazdy-360.pl/kurs)  
Demo: [https://www.prawo-jazdy-360.pl/kurs?wersja-demo=1&clip=1360](https://www.prawo-jazdy-360.pl/kurs?wersja-demo=1&clip=1360)

Rola:
- katalog pytań,
- overview grup pytan,
- wejscie w detail grupy,
- nauka przez kolejne pytania.

### 2.1 Kurs overview
Gorne split hero sprzedaje produkt, ale tryb demo wpuszcza nizej w realny shell.

Realny shell kursu ma:
- lewy rail z filtrami i postepem,
- status pytania i meta,
- quick actions,
- glowna scena pytania jak w sesji,
- otwarty blok wyjasnienia pod pytaniem,
- nizej bardzo dluga lista grup pytan z progressem i akcjami.

Najwazniejsza lekcja:
- overview nie jest czysta lista,
- to jednoczesnie live surface i indeks calego materialu.

### 2.2 Detail grupy pytan
URL przykladowy: [https://www.prawo-jazdy-360.pl/kurs/znaki-ostrzegawcze](https://www.prawo-jazdy-360.pl/kurs/znaki-ostrzegawcze)

Ten ekran ma inny rytm niz overview:
- breadcrumb,
- duzy naglowek grupy,
- opis merytoryczny grupy,
- filtr grupy i wyszukiwarka,
- strumien pytan jako akordeonowa lista review cards,
- pagination,
- boczny upsell card.

Najwazniejsza lekcja:
- to nie jest prosty katalog z linkami,
- to od razu review learning sheet.

## 3. Wyklady - media lesson catalog shell
URL: [https://www.prawo-jazdy-360.pl/wyklady](https://www.prawo-jazdy-360.pl/wyklady)

Rola:
- katalog dzialow i lekcji wideo,
- demo player,
- wejscie w lekcje i pytania kontrolne.

### 3.1 Overview dzialow
Po lewej:
- lista dzialow z liczbami slajdow/minut,
- aktywny dzial rozwijany do listy lekcji,
- postep globalny,
- oznaczenie `Wersja demo`.

Po prawej:
- karty dzialow z tytulem, czasem i opisem,
- duze promo bloki,
- sekcja `Co zawieraja poszczegolne dzialy?`

Najwazniejsza lekcja:
- to shell bardziej przypomina `course curriculum` niz zwykly landing.

### 3.2 Player lekcji
URL przykladowy: [https://www.prawo-jazdy-360.pl/wyklady?link=manewry-na-drodze/ruch-prawostronny](https://www.prawo-jazdy-360.pl/wyklady?link=manewry-na-drodze/ruch-prawostronny)

Player ma:
- breadcrumb lekcji i slajdu,
- wideo/slajd jako glowny stage,
- prosty pasek sterowania,
- tekst tlumaczacy pod stage,
- CTA `Nastepny`,
- zachowany lewy rail indeksu.

Najwazniejsza lekcja:
- player nie odcina uzytkownika od mapy kursu,
- indeks jest ciagle obok i stale komunikuje progres.

## 4. Podrecznik kursanta - text learning shell
URL: [https://www.prawo-jazdy-360.pl/podrecznik-kursanta](https://www.prawo-jazdy-360.pl/podrecznik-kursanta)

Rola:
- tekstowy odpowiednik wykladow,
- indeks dzialow,
- detail rozdzialu,
- pytania kontrolne.

### 4.1 Overview
Topologia jest podobna do wykladow:
- lewy rail z dzialem demo i spisem rozdzialow,
- prawa kolumna z lista glownego spisu podrecznika,
- demo cards i sekcje promocyjne.

### 4.2 Detail rozdzialu
URL przykladowy: [https://www.prawo-jazdy-360.pl/podrecznik-kursanta/iv-ruch-pojazdow](https://www.prawo-jazdy-360.pl/podrecznik-kursanta/iv-ruch-pojazdow)

Po lewej:
- rail indeksu rozdzialow dzialu.

Po prawej:
- bardzo dlugi tekst rozdzialu,
- osadzone ilustracje,
- powtarzane akcje `Oznacz jako przeczytane`,
- finalny blok `Pytania kontrolne - dzial IV` z:
  - `Powrot`,
  - `Rozpocznij test`,
  - `Pomin test`.

Najwazniejsza lekcja:
- to nie jest PDF-style reader,
- to przewijany learning workflow z checkpointem na koncu.

## 5. Szkolenie z instruktorem - premium learning shell
URL: [https://www.prawo-jazdy-360.pl/szkolenie-z-instruktorem](https://www.prawo-jazdy-360.pl/szkolenie-z-instruktorem)

Rola:
- osobny produkt premium,
- katalog 18 dzialow,
- player wideo,
- wejscie w pytania kontrolne.

### 5.1 Overview
Ten ekran ma najmocniej komercyjny hero:
- obietnica produktu,
- dwa CTA,
- liczby typu `18`, `8`, `100%`, `inf`,
- blok benefitow,
- bardzo rozbudowane cards dla kazdego dzialu.

Kazdy dzial ma:
- numer,
- tytul,
- czas,
- 3 mikro punkty,
- CTA do detailu.

### 5.2 Detail lekcji
URL przykladowy: [https://www.prawo-jazdy-360.pl/szkolenie-z-instruktorem/wprowadzenie](https://www.prawo-jazdy-360.pl/szkolenie-z-instruktorem/wprowadzenie)

Shell:
- lewy rail ze spisem 18 dzialow i ich `Pytania kontrolne`,
- prawa kolumna z:
  - duzym video playerem,
  - demo CTA,
  - mini control barem czasu,
  - CTA `Nastepny`,
  - transkrypcyjno-opisowa trescia lekcji.

Najwazniejsza lekcja:
- to bardzo dojrzaly pattern `curriculum + player + notes`,
- spokojnie da sie na nim oprzec nasze przyszle moduly edukacyjne.

## 6. Statystyki - motivational dashboard shell
URL: [https://www.prawo-jazdy-360.pl/statystyki](https://www.prawo-jazdy-360.pl/statystyki)

Rola:
- dashboard postepu,
- cross-module summary,
- quick links do dalszej pracy.

Sekcje:
- `Testy`:
  - zaliczone / niezaliczone,
  - odpowiedzi poprawne i bledne,
  - saved / wrong / unanswered,
  - proste osie dzienne.
- `Podrecznik kursanta`
- `Szkolenie z instruktorem`
- `Wyklady z lektorem`
- `Wybor pytan` na dole

Najwazniejsza lekcja:
- statystyki nie probuja byc zaawansowana analityka,
- sa po to, aby uzytkownik:
  - zobaczyl postep,
  - znalazl slabosci,
  - kliknal dalej.

## 7. Ustawienia / konto - utility shell
URL: [https://www.prawo-jazdy-360.pl/ustawienia](https://www.prawo-jazdy-360.pl/ustawienia)

Rola:
- prosty ekran konta,
- ustawienia preferencji,
- linki do akcji konto/statystyki/platnosci.

Sekcje:
- preferowana kategoria,
- dane uzytkownika,
- preferencje,
- zgody,
- boczne karty akcji i statusu dostepu.

Najwazniejsza lekcja:
- utility screen nie ma innego stylu,
- nadal korzysta z tego samego rytmu kart i prostych CTA.

## 8. Historia platnosci - billing utility shell
URL: [https://www.prawo-jazdy-360.pl/historia-platnosci](https://www.prawo-jazdy-360.pl/historia-platnosci)

Rola:
- status pakietu,
- historia platnosci,
- upsell do dostepu.

Sekcje:
- top card `Aktualny pakiet`,
- mini matrix dostepu do funkcji,
- CTA `Wykup dostep`,
- tabela historii.

Najwazniejsza lekcja:
- billing screen jest prosty i nieprzeladowany,
- ale nadal pozostaje w tym samym wizualnym systemie.

## Glowne shelle interfejsu

## 1. Public product shell
Wystepuje na:
- testach,
- kursie,
- wykladach,
- podreczniku,
- szkoleniu,
- statystykach,
- koncie,
- platnosciach.

Struktura:
- dwuwarstwowy header,
- main content,
- floating help bubble,
- duzy mega footer.

Znaczenie:
- wszystkie ekrany sa nadal jednym produktem,
- nawet utility pages nie sa "poza systemem".

## 2. Exam shell
Wystepuje na zalogowanym `/testy-na-prawo-jazdy`.

Struktura:
- pasek statusu sesji,
- lewa scena medium + pytanie + odpowiedzi,
- prawa rail postepu i akcji,
- jedno dominujace CTA `Nastepne pytanie`.

Znaczenie:
- to jest bezposredni wzorzec dla naszej strony sesji.

## 3. Result and review shell
Wystepuje po zakonczeniu egzaminu.

Struktura:
- band wyniku,
- grid numerow pytan,
- review wybranego pytania,
- wyjasnienie eksperta,
- FAQ,
- feedback.

Znaczenie:
- to bezposredni wzorzec dla naszego post-session review.

## 4. Learning catalog shell
Wystepuje na:
- `/kurs`,
- `/wyklady`,
- `/podrecznik-kursanta`,
- `/szkolenie-z-instruktorem`.

Warianty:
- `overview shell` z lewym indeksem i prawa lista dzialow,
- `detail shell` z lewym indeksem i prawa trescia lekcji/rozdzialu.

Znaczenie:
- to jest centralny system nauki PJ360,
- najwazniejszy po shellu egzaminu.

## 5. Media lesson shell
Wystepuje na:
- `/wyklady?link=...`,
- `/szkolenie-z-instruktorem/...`

Struktura:
- rail indeksu,
- player,
- proste sterowanie,
- opis/transkrypt,
- next step.

Znaczenie:
- to wzorzec do kazdego przyszlego modulu edukacyjnego opartego o video lub multimedia.

## 6. Text learning shell
Wystepuje na detailach podrecznika.

Struktura:
- rail indeksu,
- bardzo dlugi, sekcyjny tekst,
- ilustracje osadzone w biegu,
- akcja `Oznacz jako przeczytane`,
- checkpoint na koncu.

Znaczenie:
- to pattern `reader + workflow`, a nie zwykly article page.

## 7. Stats shell
Wystepuje na `/statystyki`.

Struktura:
- sekcje per produkt,
- KPI,
- progress cards,
- quick links.

Znaczenie:
- bezposredni wzorzec dla naszego dashboardu i analityki.

## 8. Utility shell
Wystepuje na:
- `/ustawienia`,
- `/historia-platnosci`.

Struktura:
- proste karty,
- jasny status,
- akcje boczne,
- minimum rozpraszaczy.

Znaczenie:
- pokazuje jak utrzymac spojnosc nawet na stronach pomocniczych.

## Komponenty, ktore sa dla nas najwazniejsze

### Sesja egzaminacyjna
- `ExamTopBar`
- `QuestionStage`
- `QuestionMetaRail`
- `AnswerBandButton`
- `SessionPrimaryAction`
- `QuickActionStack`

### Review po egzaminie
- `ResultBand`
- `QuestionNumberGrid`
- `ReviewedQuestionCard`
- `ExplanationModule`
- `FaqAccordion`
- `ExplanationFeedbackForm`

### Nauka
- `LearningLeftRail`
- `LearningSectionCard`
- `ChapterOrLessonList`
- `VideoPlayerSurface`
- `ReaderContentSurface`
- `CheckpointFooter`

### Statystyki
- `MetricTile`
- `ProgressSplitCard`
- `ProblemBucketCard`
- `ModuleProgressCard`

### Konto / platnosci
- `ProfileInfoCard`
- `PreferencesCard`
- `AccessStatusCard`
- `BillingStatusCard`
- `BillingTable`

## Kolorystyka i sygnaly stylistyczne

To, co zostaje prawdziwe rowniez po drugim passie:
- dominanta: biel i jasne mintowe tla,
- glowny brand tone: zielono-tealowy,
- glowne CTA: nasycony zielony,
- dodatkowy commerce accent: zolty,
- footer i niektore utility kontrasty sa ciemne, ale rdzen serwisu jest white-first.

Nowa obserwacja po audycie prywatnym:
- nie tylko publiczne strony sa jasne,
- rowniez sesje, statystyki, konto i nauka siedza na bialym, lekkim tle.

To oznacza:
- u nich "produkt edukacyjny" nie jest budowany przez dark UI,
- tylko przez bardzo czytelna hierarchie i miekkie kontrasty.

## Co jest dla nas najcenniejsze

### Do przeniesienia 1:1 w logice
- sesja z lewa scena i prawa railem,
- wynik egzaminu jako stan tego samego URL,
- review pytania z wyjasnieniem, FAQ i feedbackiem,
- lewy rail w modulach nauki,
- system `overview -> detail -> checkpoint`,
- dashboard motywacyjny zamiast enterprise analityki,
- utility screens w tym samym shellu.

### Do przeniesienia z adaptacja
- dwuwarstwowy header,
- mega footer,
- proporcje white-first i zielonych CTA,
- cards i sekcje typu `overview + CTA`,
- gestosc informacji.

### Czego nie kopiowac slepo
- zbyt duzej liczby dublowanych sekcji marketingowych pod kazda strona,
- przesadnej ilosci cross-sellu,
- wszystkich tekstow i mikrocopy,
- nadmiaru SEO contentu pod kazdym widokiem.

## Konkluzja

Pierwszy pass pokazywal, ze PJ360 ma dobry system publicznych layoutow.  
Drugi pass pokazuje rzecz wazniejsza:

`ich przewaga jest w prywatnym flow nauki`

Najbardziej wartosciowe do skopiowania dla naszego projektu sa:
- shell sesji,
- shell review,
- shelli nauki z lewym indeksem,
- shell statystyk,
- shell utility.

Nie powinnismy kopiowac ich marki 1:1.  
Powinnismy skopiowac:

- architekture ekranow,
- stabilnosc shelli,
- rytm `akcja -> wynik -> nauka -> kolejna akcja`,
- i bardzo czytelne rozdzielenie: scena glowna vs rail pomocniczy.
