# iOS-style mobile redesign

## Decyzja

Zalogowana czesc PrawkoNaRaz ma byc odczuwana jak jedna spokojna aplikacja mobilna, nie zbior responsywnych stron webowych. Inspirujemy sie wzorcami iOS, ale nie kopiujemy komponentow Apple ani nie udajemy aplikacji natywnej tam, gdzie web jest lepszy.

Cel: kazdy ekran mobilny ma miec jedna jasna intencje, stabilny app shell, przewidywalne przejscia i minimalna liczbe rownorzednych akcji.

## Zakres

Wchodzi:

- app shell zalogowanej aplikacji: top bar, tab bar, safe area, sheets, powroty i loading states;
- `/nauka`, konfigurator i wyniki sesji;
- `/trener-pamieci`, `/profile`, `/nauka/znaki-drogowe`, `/nauka/ranking`;
- analityka kategorii oraz ustawienia konta;
- widoki wynikow, historii i pustych stanow powyzszych modulow.

Poza zakresem tej przebudowy:

- aktywny player pytan `/nauka/teraz` i jego logika odpowiedzi, feedbacku oraz ustawien;
- publiczne strony SEO i marketingowe;
- admin, moderator i checkout.

Interaktywne playery znakow i rankingu zachowuja swoja logike. Mobilny shell playera znakow jest juz przebudowany bez zmiany kolejki i synchronizacji; player rankingu dostanie osobny przeglad po glownych destination. PJM jest swiadomie pominiete na obecny etap przebudowy.

## Stan obecny

`AuthenticatedLayout.vue` daje wspolny footer, SiteHeader i czteropozycyjny dock, ale poszczegolne widoki omijaja go na mobile w rozny sposob. Skutek:

- `/nauka` jest najblizej aplikacyjnego home;
- trener pamieci i profil maja juz pierwszy mobilny kontrakt aplikacyjny;
- ranking ma inna palete, duze karty i inny rytm;
- hub, player i wynik znakow oraz analityka kategorii maja juz mobilny kontrakt;
- zwykle podsumowanie sesji i dedykowany wynik egzaminu maja aplikacyjna hierarchie: decyzja i nastepny krok przed detalami.

To nie jest problem jednego CSS. Brakuje wspolnego kontraktu komponentow i nawigacji.

## Kontrakt iOS-style

### App shell

- top bar ma tylko nawigacje, tytul i maksymalnie jedna akcje kontekstowa;
- ekrany glowne maja duzy tytul i przewijana tresc; widoki szczegolowe maja kompaktowy navigation bar z przyciskiem powrotu;
- docelowy tab bar ma cztery stale destination: `Dzisiaj`, `Trening`, `Postep`, `Profil`;
- do czasu wdrozenia realnego widoku `Postep` obecny dock ma szesc pozycji: `Glowna`, `Trening`, `Nauka`, `Znaki`, `Ranking`, `Profil`; `Szukaj` nie wraca do dolnej nawigacji;
- wyszukiwanie nie jest stalym tabem, tylko akcja w top barze albo w obrebie `Postep`;
- player i modalny sheet nie pokazuje tab bara;
- kazdy fixed element respektuje `safe-area-inset-*` i nie przejmuje tapniec CTA.

### Powierzchnie i hierarchia

- tlo aplikacji: neutralna biel lub bardzo jasny szary systemowy;
- sekcje: grupowane listy albo spokojne pasma tresci, bez kaskady duzych kart;
- promien kart i przyciskow: maksymalnie 8 px; brak dekoracyjnych gradientow i nadmiaru cieni;
- niebieski oznacza akcje glowna, zielony sukces, czerwony blad lub material do poprawy;
- metryki sa krotkie i podporzadkowane decyzji; rekord czasu nalezy do dzialu lub mapy postepu, nie do glownego wyniku sesji.

### Interakcje

- jedna akcja glowna na pierwszy viewport;
- akcje drugorzedne trafiaja do grouped list, menu `Wiecej` albo sheeta;
- ustawienia i filtry otwieraja sheet, a nie rozbudowuja stalego ekranu;
- wyszukiwanie uruchamia klawiature dopiero po tapnieciu pola;
- loading, empty, blocked i error states maja wlasny ekranowy stan, nie pusta przestrzen albo wylaczony przycisk bez wyjasnienia.

## Docelowe widoki

| Destination | Pytanie uzytkownika | Pierwszy viewport | Model mobile |
| --- | --- | --- | --- |
| Dzisiaj `/nauka` | Co mam zrobic teraz? | aktywna sesja lub jeden rekomendowany krok | large title + next action + map picker |
| Trening `/trener-pamieci` | Co warto powtorzyc? | dzisiejsza kolejka i jedno CTA | grouped summary + sheet zakresu |
| Postep | Gdzie jestem w kursie? | opanowanie, aktywnosc, slabe pytania i przekroje | mobilny widok wdrozony; explorer mapy pozostaje kolejnym etapem |
| Znaki `/nauka/znaki-drogowe` | Czego chce sie nauczyc? | postep + kategorie znakow | grouped list + detail route |
| Ranking `/nauka/ranking` | Czy chce grac teraz? | status rankingu i jedno CTA | compact lobby + match focus |
| Profil `/profile` | Co chce ustawic? | tozsamosc, kategoria, dostep i lista ustawien | grouped settings, drill-down sheets/routes |
| Wynik sesji | Co jest najlepszym krokiem po tej serii? | wynik + jedno CTA | brief result, sciezka po akcjach, details on demand |

## Kolejnosc wdrozenia

### Faza I - wspolny shell

1. Czesc zakonczona: dock ma szesc pozycji (`Glowna`, `Trening`, `Nauka`, `Znaki`, `Ranking`, `Profil`); wyszukiwarka nie jest juz jego elementem. Docelowe zastapienie `Nauka` przez `Postep` wymaga najpierw wdrozenia prawdziwego destination postepu.
2. [~] Wspolny `MobileAppBar` jest wdrozony dla postepu i wyniku egzaminu, razem z top safe area i slotem jednej akcji. `MobileEmptyState` obsluguje neutralny brak danych i pozytywny brak problemow w analityce, wyniku egzaminu, trenerze pamieci i znakach. `MobileSection`, `MobileListRow` i `MobileMetricRow` pozostaja do wydzielenia wtedy, gdy usuna realne duplikacje.
3. Ujednolicic safe area, tytuly, back actions, loading i error states w `AuthenticatedLayout.vue`.

### Faza II - codzienne flow

1. Dokonczyc `/nauka`: recommendation-first, mapa postepu i zapamietany wybor dzialu.
2. [x] Przebudowac trener pamieci z ilustracyjnego hero na dzisiejsza kolejke oraz kontekstowa liste postepu; pusty plan ma pozytywny stan i jedno wyjscie do nauki.
3. Przebudowac profil na settings home z drill-down, bez wszystkich formularzy na jednym ekranie.
4. [x] Uproscic zwykly wynik sesji i dedykowany wynik egzaminu do jednego wyniku i jednej decyzji; rekordy i szczegoly sa podawane tylko w odpowiednim kontekscie.

### Faza III - pozostale destination

1. Znaki drogowe: wdrozone dla hubu, playera i wyniku; player zachowuje istniejaca kolejke oraz synchronizacje odpowiedzi.
2. Ranking: mobilny shell lobby, oczekiwania, meczu i wyniku jest wdrozony; potrzebny jest test prawdziwego sparowanego meczu.
3. Analityka: mobilny widok kategorii jest wdrozony jako opanowanie, aktywnosc tygodnia, pytania do poprawy i segmentowe przekroje. Wyszukiwarka pozostaje funkcja pomocnicza poza zakresem przebudowy i poza dolnym dockiem.

### Faza IV - jakosc

1. Playwright dla 360x800, 390x844 i 430x932 na kazdym krytycznym destination.
2. Testy Back, keyboard, safe area, empty/loading/error, aktywnej sesji i powrotu z sheeta.
3. Kontrast, tap targets co najmniej 44 px i brak poziomego overflow.

## Kryteria akceptacji

- kursant zawsze wie, gdzie jest, co jest stanem i jaki ma nastepny krok;
- na mobile nie wystepuje publiczny header ani footer w aplikacyjnym flow;
- dock sluzy tylko do stalej nawigacji, a nie do uruchamiania playera lub wyszukiwania SEO;
- destination obecne w docku nie sa duplikowane jako tryby ani rekomendacje na `/nauka`; `Znaki` i `Ranking` otwiera sie tylko z dolnej nawigacji;
- pierwszy viewport nie miesza wyniku, rekordow, danych historycznych i kilku rownorzednych CTA;
- wszystkie ekrany respektuja safe area i nie maja nakladajacych sie elementow;
- zachowanie playera `/nauka/teraz` pozostaje nienaruszone.

## Najblizszy sprint

Glowne widoki home, trener, profil, znaki, ranking, postep oraz oba typy podsumowania maja juz pierwszy aplikacyjny kontrakt mobile. Kolejna iteracja powinna ujednolicic wspolne komponenty app bara, grouped rows i empty/error states oraz domknac quality gate prawdziwego meczu rankingowego. Player `/nauka/teraz` pozostaje bez zmian, wyszukiwarka jest poza dockiem, a PJM poza tym etapem.

## Wdrozone 2026-07-10

- `/trener-pamieci` na telefonie dostal pierwszy ekran zgodny z kontraktem: tytul destination, dzisiejszy plan, jedna akcja glowna, grupowana lista stanu pamieci i systemowy wybor kategorii;
- usunieto ozdobny ring, postac trenera, duzy hero i dekoracyjne cienie, bez zmiany planera ani uruchamiania sesji;
- `/profile` na telefonie jest settings home: tozsamosc, kategoria, seria nauki i dostep sa krotkim podsumowaniem, a konto, bezpieczenstwo i strefa ostroznosci sa grouped lists;
- formularze zdjecia, danych konta, zaproszen, hasla, polaczen spolecznosciowych oraz usuniecia konta zachowuja istniejaca logike i sa otwierane we wspolnym dolnym sheecie;
- globalny header, desktopowy naglowek strony i footer sa ukryte na mobile profilu; desktop pozostaje bez zmiany;
- `/nauka/znaki-drogowe` ma mobilny hub bez publicznego headera i footera: jeden trening, progres, segmentowy wybor trybu, grouped list kategorii oraz podglad nastepnych znakow;
- player `/nauka/znaki-drogowe/teraz` na telefonie jest focus flow bez publicznego headera, footera i docka: kompaktowy pasek postepu, glowny znak lub opis, odpowiedzi oraz stan poprawnej odpowiedzi; po bledzie wyjasnienie wyjezdza jako modalny dolny sheet z CTA, bez przewijania pod odpowiedzi;
- zachowano istniejace automatyczne przejscie po poprawnej odpowiedzi, przycisk przejscia po bledzie, kolejke lokalna oraz synchronizacje batchowa;
- wynik treningu znakow na telefonie pokazuje wynik i kontynuacje przed detalami; pomylone znaki sa lista do powtorki, a pelny przeglad odpowiedzi rozwija sie na zadanie;
- wizualnie sprawdzone na `360x800`, `390x844` i `430x932`: CTA i listy nie wpadaja pod dock, formularz danych konta poprawnie otwiera sie w sheecie, a konsola nie pokazuje bledow.
- znaki drogowe sprawdzone na realnych danych w `360x800`, `390x844` i `430x932`, lacznie ze zmiana trybu oraz wynikiem z pomylka; desktopowy widok pozostaje bez zmiany.
- wyszukiwarka zostala usunieta z mobilnego docka i zastapiona przez `Znaki`; `Ranking` jest osobnym, bezposrednim destination w tym samym docku. Dolna nawigacja ma obecnie szesc rownorzednych destination.
- `Znaki` i `Ranking` zostaly usuniete z glownego widoku `/nauka`, konfiguratora i desktopowego zestawu trybow, aby nie dublowac destination z docka. Home zostaje miejscem klasycznej nauki, Zen, egzaminu, postepu i trenera pamieci.
- ranking ma nowy mobilny lobby z CTA przed tabela, stan konta i prosta lista najlepszych graczy; oczekiwanie, mecz i wynik maja oddzielne mobile shells, a mecz nie pokazuje docka;
- ranking lobby zweryfikowano na `360x800`, `390x844`, `430x932` i desktopie. Przed uznaniem calego rankingu za domkniety trzeba manualnie przejsc prawdziwy lifecycle kolejki, sparowania, meczu i wyniku.
- `/analytics/categories/{licenseCategory}` ma mobilny app shell bez publicznego headera i footera, bez zagniezdzonych kart: opanowanie, aktywnosc, `Do poprawy`, segmentowe przekroje i grouped details. Wejscie prowadzi z `Postep kursu` na `/nauka`; desktop pozostaje bez zmiany.
- dedykowany wynik egzaminu `/nauka/wynik/{studySession}` na mobile zaczyna sie od decyzji, punktow, progu i jednej glownej akcji; odpowiedzi oraz czesci egzaminu sa grouped rows, a pytania do przejrzenia rozwijaja media i wyjasnienie na zadanie. Dock wraca po fokusowym egzaminie, desktop i kontrakt backendu pozostaja bez zmiany.
- wynik egzaminu zweryfikowano na realnej zakonczonej sesji w `360x800`, `390x844`, `430x932` i `1440x900`; brak horizontal overflow, bledow konsoli oraz regresji w testach flow egzaminu.
- `MobileAppBar.vue` jest wspolnym paskiem widokow szczegolowych: powrot, tytul z kontekstem i jedna akcja. Postep oraz wynik egzaminu respektuja teraz `safe-area-inset-top`; pelny smoke test 360/390/430 px zakonczyl sie statusem `ok`.
- `MobileEmptyState.vue` ujednolica puste stany aktywnosci, pytan do poprawy, przekrojow, wyniku egzaminu `100%`, zakonczonego planu trenera i braku korpusu znakow; komunikaty rozrozniaja brak historii, brak materialu i sukces zamiast pokazywac martwe CTA albo samotne zdanie w pustej sekcji.
- warianty zerowe trenera i znakow sprawdzono bez modyfikowania danych przez kontrolowana odpowiedz Inertia na `360x800` i `430x932`; testy funkcjonalne obu obszarow: 30 testow, 432 asercje.
- player `/nauka/teraz` nie byl czescia tych iteracji i pozostaje nietkniety.

## Wdrozone 2026-07-13

- zwykly wynik nauki `/nauka/wynik/{studySession}` ma osobny wariant tylko dla telefonu ponizej `640 px`; desktop, PJM, trener pamieci, demo i dedykowany wynik egzaminu pozostaja na swoich dotychczasowych sciezkach;
- pierwszy viewport korzysta ze wspolnego `MobileAppBar`, pokazuje stan sesji, skutecznosc, cienki progress i jedna glowna decyzje: poprawke bledow, nastepny dzial albo powtorzenie dzialu;
- odpowiedzi sa trzema lekkimi metrykami, a czas sesji i sredni czas odpowiedzi sa grouped rows zamiast osobnych kart;
- drugorzedne akcje trafily do sekcji `Dalsza nauka`; mapa dzialow i rekordy pozostaja we wlasciwym kontekscie ponizej wyniku;
- pytania do poprawki sa na mobile zwijanymi wierszami z detalami na zadanie, zgodnie z wynikiem egzaminu; poprawne odpowiedzi sa domyslnie ukryte;
- dock wraca na ekranie wyniku, ma osobny bottom safe area i nie jest montowany w aktywnym playerze;
- QA na realnych ukonczonych sesjach objelo wyniki z bledami i bez bledow na `360x800` oraz `390x844`; brak poziomego overflow, desktop `1280x800` zachowuje artykuly i dotychczasowy wynik, a build produkcyjny przechodzi poprawnie.
- na wyniku sesji naglowek `Sciezka dzialow` i pierwsza karta zawsze odnosza sie do dzialu zapisanego w sesji, rowniez gdy jest juz zaliczony; osobna karta `Dalej` moze wskazac pierwszy nieukonczony dzial. Nie wolno zastepowac dzialu wyniku rekomendacja kolejnego kroku.
- mobilny `/profile` zostal dopracowany jako settings home: biala strefa tozsamosci, bezposrednia edycja avatara, zwarty status kategorii/serii/dostepu oraz pelne grouped rows z ikonami i stanami metod logowania;
- poprawiono szerokosc wierszy ustawien, przez ktora separatory konczyly sie przy szerokosci tekstu, oraz odmiane `1 dzien`; formularze nadal otwieraja sie w istniejacych dolnych sheetach, a `Escape` i linki `#sekcja` maja obsluge mobile;
- wszystkie szesc opcji profilu ma osobny kontrakt mobilnego sheeta bez zmiany endpointow i logiki desktopu: jeden naglowek systemowy, krotki kontekst, zwarta grupa danych lub statusu i jedna wyrazna akcja;
- mobilny profil udostepnia bezposrednia akcje `Wyloguj` w osobnej sekcji `Sesja`; korzysta ona z istniejacego bezpiecznego wylogowania z odswiezeniem CSRF, pokazuje stan oczekiwania i blad bez zmiany widoku desktopowego;
- mobilny ekran glowny `/nauka` nie powiela wejscia do `Trenera pamieci`: usunieto jego pasek promocyjny i kafel z `Trybow nauki`, poniewaz stale wejscie `Trening` pozostaje w dolnej nawigacji; desktop i sam modul `/trener-pamieci` pozostaja bez zmian;
- glowna akcja `/nauka` prowadzi bezposrednio do rekomendowanej klasycznej serii: najpierw pierwsze nieprzerobione pytania, potem bledy, a na koncu caly dzial; karta zawsze nazywa dzial i liczbe pytan przed rozpoczeciem;
- `Zmien dzial lub tryb` otwiera pelnoekranowy konfigurator `Nowa seria` zamiast czesciowo widocznego dolnego sheeta. Konfigurator grupuje dzial, zakres i segment `Standardowy | Zen`, a jedna stala akcja na dole podsumowuje wybor;
- `Sciezka dzialow` jest drugim poziomem tej samej nawigacji: wskazanie dzialu jest tymczasowe, `Wybierz dzial` zatwierdza zmiane, a powrot ja anuluje. Egzamin korzysta z tego samego pelnoekranowego shellu i stalej akcji;
- nowy przeplyw `/nauka` nie zmienia kontraktu backendu ani playera `/nauka/teraz`; QA konfiguratora, anulowania wyboru i egzaminu wykonano na `360x800` oraz `390x844`, bez poziomego overflow i z pelnym pokryciem viewportu przez modal;
- avatar pokazuje natychmiastowy podglad i przycisk zapisu dopiero po wyborze pliku; dane konta i haslo maja pola o wysokosci 48 px, pelna szerokosc CTA i nie otwieraja klawiatury automatycznie po wysunieciu sheeta;
- zaproszenia pokazuja plan, slot i oczekujace w jednym pasie oraz czytelny stan braku kwalifikujacego planu; metody logowania maja pelny empty state, gdy backend nie udostepnia providerow;
- usuniecie konta ma spokojny ekran konsekwencji, osobna destrukcyjna akcje i drugi modal z haslem lub e-mailem; `Escape` zamyka najpierw modal potwierdzenia, a dopiero kolejne nacisniecie sheet;
- QA paneli profilu wykonano na `360x800` i `390x844`; akcje mieszcza sie w pierwszym widoku, tresc nie ma poziomego overflow, a desktop `1280x800` zachowuje dotychczasowy uklad;
- lokalny Nginx ma wiekszy bufor FastCGI dla pelnego odswiezenia rozbudowanych stron Inertia; bezposredni `/profile` nie powinien juz konczyc sie `502 upstream sent too big header`.
