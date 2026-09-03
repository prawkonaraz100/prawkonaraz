# Audyt mobile UX - 2026-07-10

## Status

- Status: audyt wykonany; pierwsza iteracja home, sheeta i podsumowania sesji wdrozona oraz zweryfikowana lokalnie.
- Decyzja 2026-07-10: zalogowany mobile przechodzi na wspolny kontrakt iOS-style. Projekt i kolejnosc: `IOS-NATIVE-MOBILE-REDESIGN.md`.
- Wdrozenie iOS-style objelo `/trener-pamieci`, `/profile` oraz hub/player/wynik `/nauka/znaki-drogowe`: jedno CTA, grouped lists, segmentowe wybory i drill-down sheets. Player znakow dostal nowy shell bez zmiany logiki sesji.
- Zakres: zalogowane widoki mobile zwiazane z flow nauki i kontem.
- Poza zakresem: dalsza infrastruktura PWA/TWA, Lighthouse, Android i wrapper Play.
- Cel: przed kolejnymi zmianami UI uzgodnic jeden, aplikacyjny sposob przejscia przez nauke.

## Aktualizacja 2026-07-13 - mobilny widok postepu

Widok `/analytics/categories/{licenseCategory}` byl na telefonie bezposrednim pomniejszeniem strony desktopowej: publiczny header i footer, zagniezdzone ramki, siedem osobnych kart aktywnosci oraz rozbudowane metryki kazdego pytania. Nie dawal szybkiej odpowiedzi na trzy podstawowe pytania kursanta: ile materialu przerobil, co powinien poprawic i jaki jest nastepny krok.

Wdrozone bez zmiany endpointow i obliczen backendu:

- sekcja `Postep kursu` na `/nauka` prowadzi teraz bezposrednio do szczegolowego postepu wybranej kategorii;
- mobilny app bar ma powrot do home oraz menu zmiany kategorii prawa jazdy;
- pierwszy viewport pokazuje opanowanie materialu, pokrycie pytan, skutecznosc i liczbe zaplanowanych powtorek;
- gdy sa pytania do powtorki, jedna akcja prowadzi do trenera pamieci;
- aktywnosc z siedmiu dni jest zwartym wykresem, a brak aktywnosci nie rezerwuje pustej przestrzeni;
- `Do poprawy` jest lekka lista pytan z liczba bledow i paskiem opanowania zamiast zestawu kart metryk;
- przekroje `Typ`, `Punkty`, `Trudnosc` dzialaja jako jeden segmentowy widok, a szczegoly aktywnosci sa grouped list;
- publiczny header, desktopowy naglowek i footer sa ukryte na mobile; dock pozostaje widoczny, a `Glowna` zachowuje aktywny stan jako destination nadrzedne;
- desktopowy widok analityki pozostaje bez przebudowy.

Zweryfikowane na realnych danych zalogowanego kursanta oraz w smoke tescie na `360x800`, `390x844` i `430x932`: przejscie z `/nauka`, zmiana kategorii, aktywnosc z danymi i bez danych, segmenty przekrojow, dolna czesc widoku oraz brak horizontal overflow. Konsola przegladarki nie pokazala bledow. Player `/nauka/teraz`, jego layout, feedback odpowiedzi i logika sesji nie byly zmieniane.

## Aktualizacja 2026-07-13 - mobilny wynik egzaminu

Dedykowany wynik egzaminu `/nauka/wynik/{studySession}` byl na telefonie pomniejszona wersja raportu desktopowego: cztery duze metryki, rozbudowane ramki sekcji, techniczny sidebar i pelny przeglad pytan renderowany od razu. Decyzja `zdany` albo `niezdany` konkurowala z detalami, a powrot do glownej aplikacji nie korzystal z mobilnego shella.

Wdrozone bez zmiany obliczen egzaminu, endpointow i danych Inertia:

- kompaktowy app bar pokazuje powrot, kategorie oraz rzeczywista liczbe udzielonych odpowiedzi;
- pierwszy viewport zaczyna sie od decyzji egzaminu, liczby punktow, progu, skutecznosci i jednej glownej akcji;
- podsumowanie odpowiedzi oraz czesci podstawowej i specjalistycznej jest lekkimi sekcjami i wierszami zamiast zagniezdzonych kart;
- bledne pytania i pytania bez odpowiedzi sa zwijana lista; media, odpowiedz i dotychczasowe wyjasnienie sa renderowane dopiero po rozwinieciu pozycji;
- po wyjsciu z fokusowego egzaminu wraca dolny dock aplikacji, a `Nauka` zachowuje aktywny stan;
- widok desktopowy pozostal bez zmian.

Zweryfikowane na realnej zakonczonej sesji z 32 pytaniami i 22 pozycjami do przejrzenia: `360x800`, `390x844`, `430x932` oraz desktop `1440x900`. Sprawdzono przejscie kotwica do przegladu, rozwiniecie pytania z materialem i wyjasnieniem, brak horizontal overflow oraz brak bledow konsoli. Testy flow egzaminu: 2 testy i 56 asercji. Player `/nauka/teraz` oraz ekran aktywnego egzaminu nie byly modyfikowane.

## Aktualizacja 2026-07-13 - wspolny app bar widokow szczegolowych

Postep kategorii i wynik egzaminu korzystaja teraz z jednego `MobileAppBar.vue`. Kontrakt komponentu obejmuje powrot o tap target 44 px, tytul, opcjonalny kontekst oraz jedna akcje po prawej stronie. Pasek jest sticky, ma spokojna biala powierzchnie bez dekoracyjnego cienia i uwzglednia `safe-area-inset-top`, co przygotowuje widoki na standalone PWA, TWA oraz urzadzenia z wycieciem ekranu.

Wdrozenie nie zmienilo routingu, wyboru kategorii, danych wyniku ani zachowania pytan. Zweryfikowano oba ekrany na `360x800`, `390x844` i `430x932`, sticky po przewinieciu, desktop `1440x900`, brak horizontal overflow i brak bledow konsoli. Pelny `e2e:smoke` zakonczyl sie statusem `ok`. Player pozostaje poza zakresem.

## Aktualizacja 2026-07-10 - pierwsza iteracja glownego widoku

Zrealizowano bez zmiany kontraktu backendu ani logiki zapisu odpowiedzi:

- `MobileLearningDashboard.vue` rozdziela teraz dwa stany home: aktywna sesja dostaje jednoznaczne `Wroc do sesji`, a brak aktywnej sesji dostaje `Wybierz dzial` albo `Wybierz nastepna serie`;
- hero nie pokazuje juz procentu biezacego dzialu jako gdyby byl postepem calego kursu;
- globalny postep jest osobna, jasno opisana sekcja `Postep kursu` z jednym procentem dla calej kategorii;
- konfigurator sesji jest renderowany przez Vue `Teleport` do `body`, ma warstwe ponad dolnym dockiem i safe area dla finalnego CTA;
- konfigurator zamyka sie przed otwarciem dialogu zastapienia aktywnej sesji, wiec dwa modalne stany nie nakladaja sie na siebie;
- smoke test dostal regresje mobile dla 360/390/430 px: otwiera konfigurator, przewija do `Rozpocznij nauke` i wymaga realnego klikniecia prowadzacego do potwierdzenia zastapienia aktywnej sesji.

Weryfikacja manualna Playwright na `360x800`:

- home bez aktywnej sesji: hero, tryby, postep i dock mieszcza sie bez horizontal overflow;
- home z aktywna sesja: widoczny jest tytul sesji, numer pytania, resume i drugorzedna akcja nowej sesji;
- konfigurator: dock nie przejmuje tapniecia, finalne CTA jest widoczne i przenosi do `/nauka/teraz` dla nowej sesji.

Nastepny zakres glownego flow to analityka kategorii oraz quality gate prawdziwego lifecycle rankingu. Wyszukiwarka pozostaje funkcja pomocnicza poza przebudowa mobile i poza dockiem. Player glownej nauki oraz jego ustawienia feedbacku zostaja poza zakresem: obecna logika jest zaakceptowana produktowo. PJM jest na obecnym etapie swiadomie pominiete.

## Aktualizacja 2026-07-10 - sciezka wyboru dzialow

Pozioma lista ponad 30 dzialow zostala usunieta z krotkiego stanu konfiguratora. Zastepuje ja jedna widoczna pozycja aktualnie wybranego dzialu oraz przycisk `Pokaz sciezke dzialow`.

Wdrozone:

- przycisk otwiera pelnoekranowy picker wewnatrz tego samego sheeta, bez kolejnego modala i bez konkurencji z dolnym dockiem;
- dzialy sa pogrupowane na `Pytania podstawowe` i `Pytania specjalistyczne`, maja pelne nazwy, liczbe pytan, ikony oraz czytelna pionowa trase;
- wyszukiwanie filtruje nazwy bez rozrozniania polskich znakow, np. `Dokumenty` odnajduje `Dokumenty, ubezpieczenie i obowiazki`;
- stan dzialu nie sugeruje opanowania wiedzy: pokazuje tylko `Nowy`, procent przerobionych pytan albo `Przerobiony`; wybrany dzial dostaje osobny stan `Wybrany`;
- wybor dzialu wraca od razu do konfiguratora i zachowuje istniejacy sposob uruchamiania klasycznej nauki albo Zen;
- picker nie ustawia automatycznie fokusu na wyszukiwarce, wiec mobilna klawiatura nie zaslania pierwszego widoku sciezki.

Zweryfikowane na realnych danych zalogowanego kursanta w Playwright na `360x800`, `390x844` i `430x932`: pelne nazwy, wyszukiwanie, wybor i dolne `Gotowe` mieszcza sie bez overlapu; konsola przegladarki nie pokazala bledow.

## Aktualizacja 2026-07-10 - podsumowanie zwyklej sesji

Realny widok `/nauka/wynik/{studySession}` na `360x800` zaczynal sie od wewnetrznie przewijanej mapy 31 dzialow. Wynik zakonczonej serii, liczba bledow i akcja `Popraw bledne pytania` byly dopiero pod ta mapa. To odwracalo flow po zakonczeniu nauki.

Wdrozone:

- desktopowy panel postepu zostaje na `xl`, gdzie jest prawdziwym panelem pomocniczym;
- na telefonie pierwszy viewport zaczyna sie od `Sesja zakonczona`, wyniku, kluczowych metryk i liczby pytan do poprawki;
- rekomendowana akcja jest bezposrednio pod wynikiem: poprawa bledow, powtorka dzialu albo kolejny dzial;
- rekordy czasowe nie sa juz renderowane na telefonie jako osobna tabela pod podsumowaniem; po akcjach pojawia sie lekka `Sciezka dzialow`, ktora pokazuje czas przy aktualnym dziale;
- tabela `Rekordy dzialow` zostaje desktopowym kontekstem, a przeglad pytan pozostaje ponizej jako szczegol dla osoby, ktora chce do niego wejsc.

Zweryfikowane manualnie w Playwright na `360x800`, `390x844` i `430x932`: wynik i wszystkie trzy akcje mieszcza sie bez overlapu, a pierwszym komunikatem jest stan skonczonej sesji. Dodatkowo po usunieciu tabeli rekordow sprawdzono realny wynik na `390x844`: po akcjach pojawia sie `Sciezka dzialow` z czasem aktualnej serii albo ostatnim zapisanym czasem.

## Aktualizacja 2026-07-10 - trener i profil

`/trener-pamieci` przestal byc odrebnym ilustracyjnym ekranem. Na telefonie pokazuje teraz tytul destination, dzisiejszy plan powtorek, dwie krotkie metryki, jedno CTA oraz grouped list stanu pamieci. Planner i uruchamianie sesji nie zmienily kontraktu.

`/profile` przestal byc dluga responsywna strona webowa. Na telefonie nie renderuje globalnego SiteHeader, desktopowego naglowka ani footera. Zamiast nich ma krotki blok tozsamosci, podsumowanie kategorii/dostepu oraz grouped lists `Konto`, `Bezpieczenstwo` i `Strefa ostroznosci`. Kazda pozycja otwiera dotychczasowy formularz w dolnym sheecie, wiec nie zmieniono endpointow ani walidacji.

Zweryfikowane w Playwright na `360x800`, `390x844` i `430x932`: profil nie ma poziomego overflow, dock pozostaje widoczny, a arkusz `Dane konta` otwiera sie i zamyka poprawnie. Konsola przegladarki nie pokazala bledow. PJM nie bylo dotykane i pozostaje poza obecnym zakresem.

## Aktualizacja 2026-07-10 - znaki drogowe

Hub `/nauka/znaki-drogowe` byl na telefonie zbiorem duzego ringu, trzech pelnych przyciskow i czterech rozbudowanych kart, przedluzonym o publiczny header i footer. Mobilny wariant ma teraz tytul destination, jedna akcje `Rozpocznij trening`, krotki progres, segmentowy wybor trybu, grouped list kategorii oraz lekka liste najblizszych znakow. Zmiana trybu i wybor kategorii zachowuja istniejacy POST startu sesji.

Wynik znakow jest oddzielnym krotszym flow: wynik i kontynuacja sa pierwsze, pomylone znaki dostaja liste `Warto powtorzyc`, a szczegolowe odpowiedzi sa zwijane.

Player `/nauka/znaki-drogowe/teraz` ma na telefonie fokusowy app shell: kompaktowy powrot i progres, duzy znak albo opis oraz czytelne odpowiedzi. Po bledzie wyjasnienie pojawia sie jako modalny dolny sheet nad aktualnym pytaniem, z przyciemnionym tlem i CTA `Nastepny znak`; kursant nie musi przewijac pod odpowiedzi. Rowniez reczne `Wyjasnienie znaku` otwiera ten sam sheet. Nie renderuje publicznego headera, footera ani docka. Nie zmieniono kolejek pytan, automatycznego przejscia po poprawnej odpowiedzi, synchronizacji batchowej ani endpointow odpowiedzi.

Zweryfikowane na realnych danych zalogowanego kursanta w Playwright na `360x800`, `390x844` i `430x932`: hub, player z wyjasnieniem i bledna odpowiedzia, przelaczanie trybu, wynik `100%`, wynik z pomylka i rozwiniecie odpowiedzi. Nie ma publicznego headera/footera na mobile, desktopowy widok jest zachowany, a konsola nie pokazala bledow.

## Aktualizacja 2026-07-10 - ranking

Mobilne lobby rankingu zaczynalo sie od dekoracyjnej tabeli graczy, a przycisk rozpoczynajacy matchmaking byl dopiero pod nia i zblizal sie do docka. Zostalo zastapione przez ekran `Ranking`: najblizszy mecz, kategoria, ELO, czas, jedno CTA `Start ranking`, a nizej grouped summary konta i prosta lista najlepszych graczy.

Dodano osobne mobile shells dla oczekiwania, meczu i wyniku. Oczekiwanie ma pojedynczy status oraz anulowanie. Mecz jest focus flow bez docka, z czasem, postepem, pytaniem i odpowiedziami. Wynik pokazuje bezposrednie porownanie graczy, wynik konta i powrot do rankingu. Nie zmieniono matchmakingu, realtime, odpowiedzi ani endpointow.

Zweryfikowano lobby na `360x800`, `390x844`, `430x932` i desktopie, bez bledow konsoli. Backend poprawnie przekierowuje pusty URL oczekiwania do lobby, dlatego pelny test oczekiwania, sparowania, meczu i wyniku jest nadal quality gate wymagajacym prawdziwego drugiego gracza.

### P0 - podsumowanie nie moze zaczynac sie od mapy calego kursu

Zasada dla kazdego podsumowania mobile: najpierw wynik biezacej sesji i nastepny krok, potem rekordy, roadmapa oraz pelny przeglad pytan. Dotyczy zwyklej nauki, a jako wzorzec musi byc porownana jeszcze osobna strona wyniku egzaminu oraz podsumowanie trenera pamieci.

## Material i metoda

Przeszedlem rzeczywisty flow lokalnie na viewportcie 360x800 jako zalogowany kursant:

1. `/nauka` - home nauki,
2. sheet wyboru dzialu i trybu,
3. `/trener-pamieci`,
4. `/profile`,
5. `/nauka/teraz` - aktywny player i zapis odpowiedzi.

Sprawdzone pliki:

- `resources/js/Pages/Session/Index.vue`,
- `resources/js/Pages/Session/Partials/MobileLearningDashboard.vue`,
- `resources/js/Pages/Session/Partials/MobileLearningAppBar.vue`,
- `resources/js/Pages/StudySessions/Show.vue`,
- `resources/js/Pages/ReviewQueue/Index.vue`,
- `resources/js/Pages/Profile/Edit.vue`,
- `resources/js/components/MobileBottomNavigation.vue`,
- `resources/js/Layouts/AuthenticatedLayout.vue`.

Referencje projektowe:

- [Android navigation bar](https://developer.android.com/develop/ui/compose/components/navigation-bar): dolna nawigacja jest przeznaczona dla 3-5 rownorzednych, stale dostepnych destination.
- [Android bottom sheets](https://developer.android.com/develop/ui/compose/components/bottom-sheets): modalny sheet jest osobnym stanem ekranu i musi w calosci obslugiwac swoja interakcje.
- [NN/g: visibility of system status](https://www.nngroup.com/articles/visibility-system-status/): po akcji uzytkownik musi szybko dostac zrozumialy stan i feedback.

## Diagnoza

Mobilny player ma dobry kierunek: jest spokojny, skupiony na pytaniu i nie pokazuje globalnego docka. Reszta flow nie ma jeszcze jednej wspolnej architektury. Home jest marketingowo-kafelkowy, sheet jest konfiguracja desktopowego formularza przeniesiona na telefon, trener jest osobnym ekranem ilustracyjnym, a profil nadal jest responsywna strona webowa z naglowkiem i stopka.

To nie jest problem pojedynczych kolorow ani cieni. Brakuje jednego kontraktu aplikacji mobile:

- co jest glowna akcja na danym ekranie,
- kiedy widoczny jest dock,
- ktore stany sa modalne i maja pierwszenstwo nad dockiem,
- jak uzytkownik widzi postep, wynik i nastepny krok,
- ktore widoki sa hubami, a ktore pelnym focusem.

## Usterki i priorytety

### P0 - sheet i trener sa blokowane przez dolny dock

W realnej przegladarce przycisk `Rozpocznij nauke` na dole sheeta zostal zasloniety przez pozycje `Szukaj` z `MobileBottomNavigation`. Playwright nie mogl kliknac CTA, bo dock przejmowal pointer events. Ten sam problem wizualnie dotyczy glownego CTA trenera pamieci: przycisk schodzi pod dock.

Kod:

- sheet: `MobileLearningDashboard.vue`, modal ma `z-50`,
- dock: `MobileBottomNavigation.vue`, ma `z-40`,
- mimo liczb z-index overlay siedzi w lokalnym kontekscie skladania i nie wygrywa z globalnym dockiem.

Kierunek naprawy:

- modalny sheet renderowac poza lokalnym konteksciem skladania (Teleport do `body`) albo centralnie w mobile app shell,
- podczas otwartego sheeta schowac dock,
- CTA sheeta i trenera zawsze odsunac nad `safe-area-inset-bottom` i nad miejscem docka,
- dodac e2e test klikniecia finalnego CTA, nie tylko jego widocznosci.

To jest pierwsza zmiana implementacyjna. Nie ma sensu poprawiac estetyki, dopoki podstawowa akcja jest nieklikalna.

### P0 - home komunikuje sprzeczny stan nauki

Na jednym ekranie widoczne byly jednoczesnie:

- `100%` i `0 pytan do konca dzialu` w hero,
- `74% kursu`, `148 blednych` i `394 do przerobienia` w progresie.

Uzytkownik nie rozumie, czy zakonczyl nauke, czy ma jeszcze 394 pytania. Hero powinien opisywac stan jednej, jasno nazwanej rzeczy: aktywnej sesji, biezacego dzialu albo rekomendowanego nastepnego kroku. Nie moze udawac globalnego postepu.

Kierunek naprawy:

- gdy istnieje aktywna sesja: hero `Wroc do sesji`, z tytulem, licznikiem i jedna akcja resume,
- gdy aktywnej sesji nie ma: hero `Nastepny krok` z jednym realnym celem, np. `11 bledow w znakach ostrzegawczych`,
- globalny postep zostaje nizej jako informacja pomocnicza, nie konkuruje z CTA.

### P1 - nazwa `Kontynuuj nauke` nie odpowiada zachowaniu

Na home przycisk `Kontynuuj nauke` otwiera konfigurator nowej sesji, a nie zawsze wznawia aktywna sesje. To rozjezdza jezyk produktu z efektem tapniecia.

Kierunek naprawy:

- `Wroc do sesji` tylko dla rzeczywistego resume,
- `Rozpocznij nauke` lub `Wybierz trening` dla sheeta konfiguracji,
- active session pokazac jako osobny stan, bez udawania go przez domyslny hero.

### P1 - dolna nawigacja miesza destination i akcje

Obecny dock ma piec pozycji: `Glowna`, `Trening`, `Nauka`, `Szukaj`, `Profil`. `Nauka` prowadzi do aktualnej sesji, czyli do stanu, ktory czasem nie istnieje. `Szukaj` jest funkcja publicznej bazy, a nie rownorzednym obszarem produktu. Na trenerze jest dodatkowo strzalka `Wroc do nauki`, wiec ekran ma dwa konkurujace systemy nawigacji.

Kierunek decyzji projektowej:

- dock zostawiamy tylko dla stalych destination o rownej wadze,
- immersive player i modalny sheet nie maja docka,
- wyszukiwanie przenosimy do top action lub sekcji home, chyba ze biznesowo jest pelnoprawnym filarem aplikacji,
- decyzje o docelowych pozycjach docka zatwierdzamy przed przebudowa pozostalych widokow.

Robocza propozycja: `Dzisiaj`, `Powtorki`, `Postep`, `Profil`; aktywna sesja jest mocnym CTA na home, nie permanentna pozycja menu.

Decyzja wdrozona 2026-07-10: `Szukaj` zostalo usuniete z dolnego docka i zastapione przez `Znaki`. Pozycje to `Glowna`, `Trening`, `Nauka`, `Znaki`, `Profil`; wyszukiwarka jest funkcja pomocnicza poza glowna nawigacja.

Decyzja wdrozona 2026-07-10: `Znaki` i `Ranking` nie sa juz powielane na `/nauka` jako kafle, tryby albo rekomendacje. Oba moduly sa samodzielnymi destination; home koncentruje sie na biezacej nauce, Zen, egzaminie, postepie i trenerze pamieci.

### P1 - konfigurator sesji wymaga zbyt duzo wyborow naraz

Sheet prezentuje dluga pozioma liste ponad 30 dzialow, potem trzy tryby i CTA. Dlugie nazwy sa obciete, a dwa dzialy wygladaja jak identyczne `Skrzyzowania z...`. Uzytkownik musi przewijac, pamietac i porownywac zanim zacznie nauke.

Kierunek naprawy:

- pierwszy stan sheeta pokazuje tylko rekomendowany zestaw i jedno CTA,
- `Zmien dzial` otwiera osobny, wyszukiwalny picker z pelnymi nazwami i grupami,
- tryb powtorek jest opisany przez efekt (`11 bledow do poprawy`), a nie tylko nazwe,
- dolny sticky summary pokazuje wybrany dzial, liczbe pytan i jedno widoczne CTA.

### P1 - trener pamieci ma dobre dane, ale zbyt duzo dekoracji i za slaby rytm akcji

Duzy ring z `63%`, obok `0/80`, prognoza po sesji, ilustracja i CTA tworza kilka konkurujacych punktow uwagi. Dane sa wartosciowe, ale pierwsze spojrzenie powinno odpowiedziec na trzy pytania: co robie teraz, ile to potrwa, co zyskam.

Kierunek naprawy:

- naglowek `Powtorki na dzis`,
- jedna liczba `50 pytan, ok. X min`,
- jedno CTA w zasiegu kciuka nad dockiem,
- szczegoly planu i ring pod CTA albo na drugim widoku.

### P1 - profil jest nadal strona webowa, nie widokiem aplikacji

Na mobile profil laczy globalny naglowek strony, hero, wiele rozbudowanych formularzy, stopke publiczna i dock. Uzytkownik trafia na dluga sciane ustawien zamiast na hub konta.

Kierunek naprawy:

- mobile profile home: avatar, nazwa, kategoria, dostep i krotka lista destination,
- osobne widoki lub sheety dla danych konta, hasla, avataru i polaczen,
- bez publicznej stopki na ekranach aplikacyjnych,
- pozycje niedostepne pokazac jako informacyjne, a nie jako wylaczone CTA bez kontekstu.

### P1 - player potrzebuje feedbacku po odpowiedzi

Po tapnieciu odpowiedzi player automatycznie przeszedl do kolejnego pytania. Dla trybu nauki brakuje widocznego wyniku `dobrze` / `blednie`, krotkiego wyjasnienia i swiadomej decyzji `Dalej`. To odbiera moment uczenia sie i utrudnia zrozumienie bledu.

Kierunek decyzji produktowej:

- tryb nauki: odpowiedz -> feedback -> opcjonalne wyjasnienie -> `Dalej`,
- tryb egzaminu: zachowuje szybkie przejscie bez feedbacku,
- player zachowuje brak docka, ale dostaje czytelny progress i bezpieczne zakonczenie sesji.

### P2 - wizualna hierarchia jest zbyt rozproszona

Home uzywa jednoczesnie niebieskiego hero, zielonego trenera, pomaranczowego aktywnego docka i fioletowego rankingu. Kazdy kafel chce byc pierwszoplanowy. W aplikacji edukacyjnej kolor powinien przede wszystkim kodowac stan, nie osobny nastroj kazdego modulu.

Kierunek naprawy:

- podstawowa powierzchnia: biel i grafit,
- glowny action/state: niebieski,
- sukces: zielony tylko dla pozytywnego wyniku i powtorek,
- ostrzezenie/blad: zolty/czerwony tylko w kontekscie odpowiedzi i ryzyka,
- ranking i znaki dostaja ikonografie lub ilustracje, nie kolejny dominujacy kolor calego kafla.

## Docelowy kontrakt ekranow

| Ekran | Jedno pytanie, na ktore odpowiada | Glowna akcja | Dock |
| --- | --- | --- | --- |
| Home `/nauka` | Co jest najlepszym krokiem teraz? | Wroc do sesji albo Rozpocznij rekomendowany trening | tak |
| Konfigurator sesji | Co chcesz przerobic? | Rozpocznij wybrany zestaw | nie, modalny stan |
| Trener pamieci | Co jest do powtorki dzis? | Rozpocznij sesje | tak |
| Player `/nauka/teraz` | Jaka jest poprawna odpowiedz i dlaczego? | Odpowiedz / Dalej | nie, tryb focus |
| Profil | Co chcesz ustawic w koncie? | Wejdz w konkretne ustawienie | tak |

## Teza wizualna

PrawkoNaRaz mobile powinno przypominac spokojnego, konkretnego trenera jazdy: biale tlo, czytelna orientacja, jedna mocna decyzja na ekranie i kolor tylko jako sygnal postepu albo wyniku. Nie budujemy kolejnej mozaiki kart ani marketingowej strony w obudowie telefonu.

## Plan tresci

1. Home: stan dzisiejszej nauki, jeden next step, krotki postep i powtorki.
2. Konfigurator: rekomendacja, potem opcjonalny wybor dzialu i trybu.
3. Player: pytanie, odpowiedz, feedback, nastepny krok.
4. Trener: dzisiejsza misja, czas, CTA, potem szczegoly.
5. Profil: tozsamosc i lista ustawien, nie wszystkie formularze naraz.

## Teza interakcji

- Home do sheeta: szybkie podniesienie sheeta z przygaszeniem tla; dock znika, gdy sheet kontroluje ekran.
- Odpowiedz w playerze: natychmiastowy kolorowy feedback i lekki transition do stanu wyjasnienia, bez automatycznego przeskoku w trybie nauki.
- Postep: licznik i cienki pasek aktualizuja sie po odpowiedzi; bez dekoracyjnych animacji, ktore konkuruja z pytaniem.

## Kolejnosc implementacji

### Sprint M0 - naprawa shell i tap targets

1. [x] Usunac konflikt dock <-> sheet/CTA na home.
2. [~] Zapewnic bottom safe area na home, trenerze i profilu. Home i sheet sa gotowe; trener oraz profil pozostaja w M2.
3. Usunac albo ozywic wylaczone `Ustawienia nauki`.
4. [~] Dodac testy klikniecia CTA w sheetcie i trenerze na 360/390/430 px. Sheet home jest pokryty smoke; trener pozostaje do M2.

### Sprint M1 - home i konfigurator

1. [x] Rozdzielic aktywna sesje, rekomendowany krok i globalny postep na home.
2. [x] Ujednolicic nazwy CTA na home (`Wroc do sesji` tylko dla prawdziwego resume).
3. [~] Przebudowac sheet na recommendation-first + osobny picker dzialu. Osobny picker jest gotowy; stan recommendation-first pozostaje decyzja na kolejna iteracje.
4. Zatwierdzic finalne destination dolnego docka.

### Sprint M2 - trener i profil

1. Uproscic trener do misji dziennej i jednej akcji.
2. Zrobic mobile profile home i drill-down do ustawien.
3. Usunac publiczny header/footer z mobile app flow.

### Sprint M3 - player nauki

1. Zatwierdzic osobne zachowanie learn vs exam.
2. Dodac feedback po odpowiedzi dla learn.
3. Dopracowac progress, wyjasnienie i bezpieczne wyjscie z sesji.

## Kryteria akceptacji mobile UX

- zadne CTA nie jest zaslaniane przez dock, keyboard ani safe area,
- na kazdym ekranie jest jedna oczywista akcja glowna,
- uzytkownik rozumie, czy ma aktywna sesje, co zostalo i co stanie sie po tapnieciu,
- dock wystepuje tylko na stalych, rownorzednych destination,
- player uczy: wynik odpowiedzi jest widoczny zanim uzytkownik przejdzie dalej,
- 360/390/430 px przechodza zrzuty i interakcje krytyczne,
- profil i trener wygladaja jak czesci tej samej aplikacji co home i player.
