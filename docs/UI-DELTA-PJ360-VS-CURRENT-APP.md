# UI Delta: PJ360 vs nasze obecne UI

Data: 2026-03-26  
Dokument zrodlowy: [UI-AUDIT-PRAWO-JAZDY-360.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-AUDIT-PRAWO-JAZDY-360.md)

Cel tego dokumentu:
- pokazac nie tylko, jak wyglada referencja,
- ale bardzo konkretnie: czego jeszcze nie mamy u siebie.

To jest dokument delta, nie ogolny opis.

## Werdykt

Najwieksza roznica nie polega na kolorach ani ozdobnikach.
Najwieksza roznica polega na tym, ze PJ360 ma duzo pelniejszy system shelli i stanow pobocznych wokol nauki.

U nas:
- glowne ekrany juz istnieja,
- przeplyw sesji dziala,
- ale wiele warstw `support / utility / review / trust / global controls` jest jeszcze uproszczonych.

## P0: Najwazniejsze luki produktowe

### 1. Ekran sesji nie ma pelnego shellu egzaminacyjnego
PJ360 ma w sesji:
- podzial na `pytania podstawowe` i `specjalistyczne`,
- licznik czasu i licznik czasu na pytanie,
- panel meta pytania,
- utility actions,
- mozliwosc zakonczenia egzaminu,
- widoczne `id pytania`,
- bardzo czytelne answer states.

U nas w [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue):
- jest dobra baza 2-kolumnowa,
- ale nie ma jeszcze pelnej dramaturgii i gestosci egzaminowego shellu.

Brakuje u nas:
- segmentacji `20 podstawowych / 12 specjalistycznych`,
- utility rail `zapisz / odswiez / zadaj / fullscreen`,
- stanu `brak odpowiedzi`,
- review shellu spinajacego wynik i detail pytania w jednym produkcie.

### 2. Review po sesji jest za lekkie
PJ360 po wyniku pokazuje:
- wynik,
- siatke numerow pytan,
- klikany detail pytania,
- wyjasnienie eksperta,
- znaki i kontekst,
- FAQ,
- formularz feedbacku.

U nas review jest nadal zbyt blisko zwyklego wyniku.

Brakuje u nas:
- siatki numerow jako glownego narzedzia review,
- dedykowanego detailu review,
- bloku `Wyjasnienie`,
- bloku `FAQ`,
- formularza `czy to wyjasnienie bylo pomocne?`.

### 3. Nie mamy utility support workflow w samym pytaniu
PJ360 ma modal `Zadaj pytanie`.

To daje:
- poczucie opieki,
- mozliwosc eskalacji watpliwosci,
- warstwe edukacyjno-supportowa bez wychodzenia z sesji.

U nas tego nie ma.

## P1: Braki duze, ale nie krytyczne

### 4. Publiczny header nie ma sterownikow produktu
PJ360 ma w headerze:
- selector kategorii,
- selector jezyka,
- dropdown konta,
- dropdown bazy wiedzy.

U nas:
- header jest bardziej klasyczna nawigacja aplikacji,
- mniej przypomina publiczny, wielowarstwowy shell produktu.

Brakuje u nas:
- globalnego selectora kategorii w shellu publicznym,
- globalnego selectora jezyka,
- drugiego rzedu content navigation.

### 5. Detail pytania w bazie jest u nas praktycznie nieobecny jako osobny produkt
PJ360 ma osobna strone detail pytania:
- media,
- prompt,
- odpowiedzi,
- meta rail,
- CTA do testu,
- locked explanation,
- floating support avatar.

U nas:
- mamy katalog,
- ale nie mamy pelnego `question detail public/product shell`.

### 6. Lista pytan ma inny model kompozycji
PJ360 na liscie pytań:
- nie robi rozbuchanego panelu filtrow,
- pokazuje prosty listing `tresc / kategoria / rodzaj`,
- daje pagination,
- dopiero nizej domyka narracje edukacyjna i CTA.

U nas [Questions/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Questions/Index.vue):
- jest bardziej app-first,
- ma szybki start i filtry jako glowne elementy.

To nie jest blad, ale jest to wyrazna roznica wzgledem referencji.

## P2: Braki warstwy trust / content / support

### 7. Nie mamy shellu forum/community
PJ360 ma osobny vertical:
- forum kategorii,
- pomoc,
- partnerzy,
- szkoly jazdy,
- luzne tematy,
- propozycje dla serwisu.

U nas nie ma:
- warstwy community,
- publicznego support forum,
- katalogu watkow jako trust surface.

To nie jest must-have dla MVP, ale to realna roznica systemowa.

### 8. Nie mamy stalego floating support widgetu
PJ360 ma avatar/wsparcie przypiete do wielu stron.

U nas nie ma:
- stalego punktu pomocy,
- lekkiego skrótu do kontaktu z dowolnego ekranu.

### 9. Nie mamy tylu warstw upsell / paywall states
PJ360 bardzo konsekwentnie osadza upsell:
- przed sesja,
- po sesji,
- w detailu pytania,
- w utility actions,
- w wyjasnieniach.

U nas prawie nie ma jeszcze prawdziwych `locked states`.

## P3: Braki stricte layoutowe

### 10. Nasz landing i dashboard sa nadal bardziej "czyste appki" niz "hybryda product + trust"
PJ360 spina prawie wszystko:
- live product shell,
- content,
- statystyki,
- opinie,
- podstawe prawna,
- app promo,
- CTA loop.

U nas:
- jest juz porzadek,
- ale nadal mniej warstw budujacych poczucie kompletnego produktu.

### 11. Footer nie gra jeszcze roli pelnego publicznego hubu
PJ360 footer jest:
- duzy,
- wielokolumnowy,
- kontaktowy,
- produktowy,
- contentowy,
- socialowy.

U nas nie jest jeszcze osobnym narzedziem nawigacji i zaufania.

## Mapa delta: ich ekran -> nasza luka

- `testy-na-prawo-jazdy / in-progress` -> nasz [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue) wymaga dopelnienia o utility, segmentacje i mocniejszy rail meta.
- `testy-na-prawo-jazdy / result + review` -> nasz [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue) wymaga osobnego review detailu z explanation stackiem.
- `pytania-egzaminacyjne list` -> nasz [Questions/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Questions/Index.vue) wymaga decyzji: zachowac app-first albo dodac osobny publiczny list shell.
- `pytania-egzaminacyjne detail` -> u nas brak osobnej strony produktu dla pojedynczego pytania.
- `public header` -> nasz [Home.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Home.vue) i shell publiczny wymagaja drugiego poziomu sterowania.
- `forum` -> u nas brak odpowiednika i trzeba swiadomie uznac to za `nie robimy teraz` albo zaplanowac jako dalszy vertical.

## Priorytet wdrozenia

### P0
- przebudowa [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- dodanie review detail shell
- dodanie explanation stack

### P1
- nowy publiczny question detail
- decyzja o nowym shellu listy pytan
- global controls w publicznym headerze

### P2
- support widget
- locked states / upsell states
- mocniejszy footer

### P3
- ewentualny vertical community/forum

## Najkrotszy wniosek

To, co jeszcze pominelismy, nie sa drobiazgi kosmetyczne.
To sa glownie:
- stany poboczne sesji,
- warstwa review i wyjasnien,
- utility support,
- publiczne sterowniki shellu,
- osobny question detail,
- warstwa trust/community.

Jesli mamy wybierac jeden ruch o najwyzszej wartosci, to nadal jest nim:
- przebudowa [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

