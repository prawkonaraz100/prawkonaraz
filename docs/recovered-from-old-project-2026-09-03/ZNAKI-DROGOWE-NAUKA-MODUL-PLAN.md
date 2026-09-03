# Modul nauki znakow drogowych - plan produktu i sprintow

Data: 2026-05-12  
Branch roboczy: `codex/traffic-sign-learning-module`  
Status: Sprint 6 w implementacji - monitoring MVP dodany, trwa release polish

## 1. Decyzja produktowa

Budujemy osobny modul nauki znakow drogowych. Nie laczymy go z `Trenerem pamieci`, nie dopinamy go do `sr_review` i nie mieszamy progresu znakow z progresami pytan egzaminacyjnych.

Powod:

- znaki wymagaja szybkiego rozpoznawania wizualnego, a nie tej samej logiki co pytania egzaminacyjne,
- trener pamieci ma pozostac lekki i skupiony na pytaniach,
- katalog znakow ma duza wartosc SEO i nie moze zostac rozchwiany przez warstwe treningowa,
- osobny modul daje prostsze QA, prostsze metryki i mniejsze ryzyko regresji.

## 2. Granice systemu

### Co jest zrodlem prawdy

Istniejacy modul `znaki drogowe` pozostaje zrodlem danych:

- kategorie znakow,
- pojedyncze znaki,
- nazwy, opisy, assety,
- publiczne URL-e,
- relacje i strony porownawcze.

Warstwa nauki tylko czyta te dane.

### Czego nie robimy

- Nie modyfikujemy logiki `ReviewPlannerService`.
- Nie zapisujemy wynikow znakow w `study_sessions`.
- Nie zapisujemy znakow w `user_question_progress`.
- Nie dodajemy znakow do `Trenera pamieci`.
- Nie przebudowujemy publicznych stron `/znaki-drogowe`.
- Nie zmieniamy URL-i SEO dla znakow.
- Nie wystawiamy modulu nauki znakow jako publicznej funkcji SEO.
- Nie robimy ciezkich zapytan w globalnych Inertia shared props.

### Co dodajemy docelowo

Osobna warstwa:

- routing modulu nauki znakow,
- osobne sesje znakow,
- osobny progres uzytkownika,
- osobne statystyki,
- osobny QA i telemetry.

## 3. Model nauki

Najlepszy model dla znakow to aktywne rozpoznawanie, potem odwrocone sprawdzanie i dopiero na koncu porownywanie podobnych znakow.

Podstawa pedagogiczna:

- aktywne testowanie wzmacnia dlugoterminowe zapamietywanie bardziej niz samo ponowne czytanie materialu: Roediger & Karpicke, 2006, `Test-Enhanced Learning` - https://pubmed.ncbi.nlm.nih.gov/16507066/
- rozlozone powtorki sa skuteczniejsze niz masowe uczenie naraz: Cepeda et al., 2006, `Distributed Practice in Verbal Recall Tasks` - https://pubmed.ncbi.nlm.nih.gov/16719566/
- mieszanie podobnych kategorii pomaga uczyc sie rozrozniania cech, a nie tylko ogolnego skojarzenia: Kornell & Bjork, 2008, `Learning Concepts and Categories` - https://journals.sagepub.com/doi/10.1111/j.1467-9280.2008.02127.x

## 4. Docelowa mechanika

### Tryb 1: Co oznacza ten znak?

Ekran pokazuje duzy znak i 4 odpowiedzi tekstowe.

Cel:

- szybkie polaczenie obrazu ze znaczeniem,
- nauka automatycznego rozpoznawania.

To jest MVP.

### Tryb 2: Wybierz wlasciwy znak

Ekran pokazuje opis, a uzytkownik wybiera znak z 4 obrazow.

Cel:

- sprawdzic, czy uzytkownik rozumie znaczenie, a nie tylko kojarzy ksztalt,
- wzmocnic pamiec odwrotna: opis -> obraz.

To robimy po MVP.

### Tryb 3: Podobne znaki

Ekran pokazuje zestaw znakow, ktore latwo pomylic.

Cel:

- uczyc roznic,
- redukowac pomylki na znakach podobnych ksztaltem, kolorem albo funkcja.

To jest wysoka wartosc edukacyjna, ale wymaga dobrych par/grup porownawczych.

## 5. Stany progresu

Prosty, osobny model progresu wystarczy na start:

- `new` - znak nie byl jeszcze cwiczony,
- `learning` - znak byl widziany, ale nie ma stabilnej serii poprawnych odpowiedzi,
- `needs_review` - znak zostal pomylony albo wymaga powrotu,
- `mastered` - znak ma stabilna serie poprawnych odpowiedzi.

Minimalne pola progresu:

- `user_id`,
- `traffic_sign_id`,
- `state`,
- `attempts_count`,
- `correct_count`,
- `incorrect_count`,
- `correct_streak`,
- `last_answered_at`,
- `next_review_at`,
- `last_confused_with_traffic_sign_id` jako pole opcjonalne.

Regula MVP:

- pierwsza poprawna odpowiedz: `learning`, `correct_streak = 1`,
- druga poprawna odpowiedz z rzedu: dalej `learning`, ale dluzszy termin powrotu,
- trzecia poprawna odpowiedz z rzedu: `mastered`,
- blad: `needs_review`, `correct_streak = 0`,
- blad na znaku podobnym: dodatkowo zapisz `last_confused_with_traffic_sign_id`.

Nie musi to byc docelowy spaced repetition. Ma byc szybkie, zrozumiale i latwe do QA.

## 6. Sklad sesji

MVP: 12 znakow na sesje.

Sklad domyslny:

- 5 nowych znakow,
- 5 znakow do powtorki,
- 2 znaki podobne lub wzmacniajace.

Jesli brakuje ktorejs grupy:

- najpierw wypelnij `needs_review`,
- potem `learning`,
- potem `new`,
- na koncu `mastered` jako lekki booster.

Nie robimy sesji z 50 znakow. Znaki sa wizualne, szybkie i moga meczyc poznawczo, jezeli idzie ich za duzo naraz.

## 7. Dobor odpowiedzi blednych

Najwazniejszy element jakosci.

Hierarchia distractorow:

1. Znaki z tej samej kategorii.
2. Znaki z grupy podobienstwa, jesli istnieje.
3. Znaki o podobnej funkcji, np. zakazy wjazdu, znaki kierunku, przejscia/przejazdy.
4. Losowe znaki aktywne jako fallback.

No-go:

- nie dawac oczywistych, zbyt latwych distractorow w kazdym pytaniu,
- nie mieszac znakow z kategorii technicznych, jesli nie maja sensu edukacyjnego,
- nie pokazywac odpowiedzi, ktore sa semantycznie zbyt bliskie i moga byc obie prawdziwe.

## 8. UI/UX

### Wejscie do modulu

Modul nauki znakow drogowych jest jednym z trybow w prywatnej nauce:

- wejscie widoczne na `/nauka`,
- tryb w tym samym obszarze co `Nauka klasyczna`, `Zen mode`, `Egzamin` i `Trener pamieci`,
- publiczny katalog `/znaki-drogowe` zostaje tylko hubem wiedzy i SEO.

Rekomendowana trasa aplikacyjna:

- `GET /nauka/znaki-drogowe`

Rekomendowana nazwa route:

- `session.traffic-signs`

Trasa powinna byc w grupie `auth + verified + product.access`, czyli tak jak pelne tryby nauki.

Pierwszy ekran:

- duzy naglowek `Nauka znakow drogowych`,
- kafelki kategorii z procentem opanowania,
- glowny przycisk `Rozpocznij trening`,
- drugi przycisk `Wybierz kategorie`.

### Ekran sesji

Zasady:

- znak bardzo duzy,
- malo tekstu,
- 4 odpowiedzi,
- duze targety klikniecia,
- brak dekoracyjnego chaosu,
- wyglad spojny z `/nauka`, `/cennik` i obecnym shellem sesji.

Po odpowiedzi:

- natychmiastowy feedback,
- poprawna odpowiedz,
- krotkie wyjasnienie,
- link `Zobacz karte znaku` jako opcja, nie jako glowna akcja.

### Podsumowanie

Po sesji:

- wynik,
- nowe opanowane,
- znaki do powtorki,
- najczestsza pomylka,
- przycisk `Kontynuuj trening`,
- link do katalogu tylko jako opcja.

## 9. Architektura bezpieczna dla repo

### Przeglad kontrolny 2026-05-12

Drugi przeglad dokumentacji zostal porownany z realnym kodem modulu znakow i ekranu `/nauka`.

Potwierdzone fakty z repo:

- publiczny katalog znakow dziala na Blade, nie na Inertia,
- interaktywne moduly nauki w aplikacji sa po stronie Inertia/Vue,
- publiczne trasy znakow maja catch-all `GET /znaki-drogowe/{signSlug}`,
- `/nauka` jest prywatnym panelem pod `auth + verified`,
- pelne tryby nauki sa pod `product.access`,
- PJM jest osobnym wyjatkiem pod `pjm.access`, ale to nie jest wzorzec dla znakow drogowych,
- istnieja modele `TrafficSign` i `TrafficSignCategory`,
- publiczny kontrakt widocznosci opiera sie o `published()`, opublikowanego autora i opublikowana kategorie,
- `TrafficSignRelatedContentService` juz umie dobierac znaki powiazane po kategorii, supporting pages i bliskosci kodu,
- sitemap, canonicale i schema dla znakow sa pokryte testami publicznymi.

Wniosek:

- nie ruszamy publicznego SEO flow,
- modul nauki moze czytac ten sam corpus, ale musi miec osobna warstwe kontrolerow, serwisow, tabel i testow,
- modul nauki ma byc trybem na `/nauka`, a nie publiczna podstrona `/znaki-drogowe`.

### Routing i miejsce w aplikacji

Nowa trasa aplikacyjna:

- `GET /nauka/znaki-drogowe`

Opcjonalne trasy po MVP:

- `GET /nauka/znaki-drogowe/teraz`,
- `POST /nauka/znaki-drogowe`,
- `POST /nauka/znaki-drogowe/odpowiedzi`,
- `GET /nauka/znaki-drogowe/wynik/{trafficSignLearningSession}`.

Wszystkie trasy treningowe musza byc w grupie:

- `auth`,
- `verified`,
- `product.access`.

Nie dodajemy trasy:

- `/znaki-drogowe/nauka`

Powod:

- to bylaby publiczna funkcja pod katalogiem SEO,
- mieszalaby intencje publicznego contentu i prywatnego produktu,
- utrudnialaby kontrolowanie dostepu i komunikacji ceny.

### Miejsce na `/nauka`

Najlepsze miejsce UI:

- w glownym panelu wyboru trybu na `/nauka`,
- jako duzy kafel `Znaki drogowe` ustawiony po lewej stronie kafla `Nauka klasyczna`,
- dalej w tym samym rzedzie lub grupie: `Nauka klasyczna`, `Zen mode`, `Egzamin`,
- przed wyborem dzialu pytan egzaminacyjnych, bo znaki nie korzystaja z `question_topic_id`.

Uzasadnienie ukladu:

- lewa pozycja daje jasny sygnal kolejnosci: najpierw poznajesz znaki drogowe, potem przechodzisz do pytan egzaminacyjnych,
- nie mieszamy znakow z dzialami pytan, bo to osobny fundament wiedzy wizualnej,
- uzytkownik widzi, ze tryb znakow jest czescia nauki, a nie dodatkiem ukrytym w publicznym katalogu SEO.

Kafel powinien komunikowac:

- osobny tryb wizualnej nauki znakow,
- postep niezalezny od pytan egzaminacyjnych,
- szybka sesja 12 znakow,
- wejscie do kategorii znakow.

W trybie PJM starter bez pelnego dostepu:

- kafel znakow powinien byc wyszarzony razem z innymi trybami pelnej nauki,
- aktywny zostaje tylko kafel PJM i CTA `Aktywuj pelna nauke`.

W testach MVP trzeba dodac osobny test:

- `/nauka` pokazuje kafel `Znaki drogowe` uzytkownikowi z pelnym dostepem po lewej stronie kafla `Nauka klasyczna`,
- `/nauka/znaki-drogowe` wymaga `auth + verified + product.access`,
- `/nauka/znaki-drogowe` nie jest dostepne dla goscia,
- `/znaki-drogowe/nauka` nie istnieje jako publiczna funkcja,
- istniejace strony znakow nadal dzialaja pod swoimi slugami.

### Kontrakt danych treningowych

Do sesji treningowej wchodza tylko znaki, ktore spelniaja minimalny kontrakt:

- `TrafficSign::published()`,
- kategoria jest `published()`,
- autor jest `published()`,
- znak ma `image_path`,
- znak ma `name`,
- znak ma co najmniej jedno pole edukacyjne do odpowiedzi lub feedbacku: `intro_definition`, `meaning` albo `driver_behavior`.

MVP nie powinien trenowac na rekordach szkicowych, przyszlych, bez autora publicznego albo bez obrazu.

Jesli aktywny znak nie spelnia kontraktu, pomijamy go w sesji i logujemy metryke QA, ale nie blokujemy calego modulu.

### Model dostepu

Decyzja MVP:

- modul nauki znakow nie jest publiczny,
- wejscie jest na `/nauka`,
- trasy treningowe wymagaja `auth + verified + product.access`,
- dla kont PJM bez pelnego dostepu kafel jest wyszarzony tak jak inne tryby pelnej nauki,
- pelny dostep odblokowuje tryb znakow razem z klasyczna nauka, Zen mode, egzaminem i rankingiem.

Powod:

- uzytkownik ma traktowac znaki jako jeden z trybow nauki, a nie jako publiczna zabawke SEO,
- progres znakow jest elementem produktu,
- unikamy anonimowego progresu i dodatkowych edge case'ow,
- zachowujemy jasny podzial: `/znaki-drogowe` to wiedza publiczna, `/nauka` to trening.

### UI implementation boundary

Publiczne strony znakow zostaja Blade.

Interaktywny trening moze byc Inertia/Vue, bo:

- reszta aplikacyjnych flow nauki jest w Inertia,
- latwiej utrzymac stan sesji, feedback i klawiature,
- nie mieszamy komponentow treningowych z publicznymi szablonami SEO.

Zakaz w MVP:

- nie przenosic publicznych stron `/znaki-drogowe` do Inertia,
- nie zmieniac layoutu publicznych kart znakow przy okazji treningu,
- nie dodawac ciezkich danych treningowych do globalnych shared props.
- nie robic publicznego CTA `Rozpocznij trening` na kartach znakow w MVP.

### Proponowane nowe modele

Nazwy do potwierdzenia przy implementacji:

- `TrafficSignLearningSession`,
- `TrafficSignLearningAnswer`,
- `UserTrafficSignProgress`,
- `TrafficSignConfusionPair` albo `TrafficSignLearningRelation`.

### Proponowane nowe tabele

- `traffic_sign_learning_sessions`,
- `traffic_sign_learning_answers`,
- `user_traffic_sign_progress`,
- `traffic_sign_confusion_pairs` w sprincie podobnych znakow.

Minimalne indeksy MVP:

- `user_traffic_sign_progress`: unique `user_id, traffic_sign_id`,
- `user_traffic_sign_progress`: index `user_id, state, next_review_at`,
- `traffic_sign_learning_sessions`: index `user_id, status, created_at`,
- `traffic_sign_learning_answers`: index `traffic_sign_learning_session_id, position`,
- `traffic_sign_learning_answers`: index `user_id, traffic_sign_id, created_at`.

### Relacje do istniejacych danych

`traffic_sign_id` wskazuje na istniejacy model znaku.

Nie zmieniamy istniejacych tabel znakow w MVP, chyba ze audyt pokaze, ze brakuje pola absolutnie koniecznego dla nauki. Wtedy zmiana musi byc addytywna i neutralna dla publicznych stron.

### Dobor distractorow w kodzie

Docelowo warto stworzyc osobny serwis, np. `TrafficSignLearningDistractorService`.

Moze on czytac sygnaly z:

- kategorii znaku,
- `TrafficSignRelatedContentService`,
- `TrafficSignSupportingPageCatalog`,
- kodu znaku, np. `A-6a`, `A-6b`, `A-6c`,
- przyszlych `traffic_sign_confusion_pairs`.

Nie powinien modyfikowac istniejacego `TrafficSignRelatedContentService`, bo ten serwis wspiera publiczne SEO i related content.

## 10. QA i bramki bezpieczenstwa

### Przed pierwszym kodowaniem

- potwierdzic nazwy modeli/tabel,
- sprawdzic realny model danych znakow w kodzie,
- ustalic, czy wszystkie znaki maja obraz nadajacy sie do treningu,
- ustalic, ktore kategorie wchodza do MVP.

### Bramki przed merge

- publiczne `/znaki-drogowe` dziala bez zmian,
- strony kategorii i pojedynczych znakow nie zmieniaja URL-i,
- sitemap/schema dla znakow bez regresji,
- trener pamieci bez zmian w testach,
- nauka klasyczna bez zmian w flow,
- build frontendu przechodzi,
- testy nowych uslug i kontrolerow przechodza.

### Testy reczne MVP

Konta QA:

- konto bez progresu znakow,
- konto z kilkoma znakami `learning`,
- konto z kilkoma znakami `needs_review`,
- konto z czescia znakow `mastered`.

Scenariusze:

- wejscie w `/nauka`,
- wejscie w tryb `/nauka/znaki-drogowe`,
- start sesji z kategorii,
- start sesji mieszanej,
- poprawna odpowiedz,
- bledna odpowiedz,
- podsumowanie,
- powrot do katalogu.

## 11. Plan sprintow

### Sprint 0 - Audyt danych i decyzje techniczne

Cel: zrozumiec obecny model znakow i wybrac minimalny zakres MVP.

Zakres:

- znalezc modele, kontrolery, seedery i widoki znakow,
- policzyc aktywne znaki per kategoria,
- sprawdzic komplet obrazow,
- sprawdzic, ktore pola nadaja sie na odpowiedzi,
- policzyc corpus spelniajacy kontrakt treningowy,
- sprawdzic, czy `A/B/C/D` maja kompletne obrazy i pola edukacyjne,
- wybrac MVP kategorie: rekomendacja `A`, `B`, `C`, `D`.

Deliverable:

- notatka audytu w tym dokumencie albo osobny QA appendix,
- lista trainable signs per kategoria,
- lista znakow pominietych z powodem,
- decyzja: ktore kategorie wchodza do MVP.

No-go:

- nie ruszac publicznych stron znakow,
- nie tworzyc migracji przed audytem.

Wynik audytu 2026-05-12:

- widocznych publicznie znakow spelniajacych kontrakt bazowy: `249 / 249`,
- brak znakow bez obrazu: `0`,
- brak znakow bez pola edukacyjnego (`intro_definition`, `meaning` albo `driver_behavior`): `0`,
- kategorie MVP `A/B/C/D`: `188` znakow,
- pozostale kategorie maja lacznie `61` znakow i moga wejsc w kolejnym rolloutcie.

Rozklad trainable signs:

| Kategoria | Slug | Liczba |
| --- | --- | ---: |
| Znaki ostrzegawcze | `znaki-ostrzegawcze` | 42 |
| Znaki zakazu | `znaki-zakazu` | 51 |
| Znaki nakazu | `znaki-nakazu` | 23 |
| Znaki informacyjne | `znaki-informacyjne` | 72 |
| Znaki kierunku i miejscowosci | `znaki-kierunku-i-miejscowosci` | 7 |
| Znaki uzupelniajace | `znaki-uzupelniajace` | 4 |
| Tabliczki do znakow drogowych | `tabliczki-do-znakow` | 4 |
| Dodatkowe znaki przed przejazdami kolejowymi | `znaki-przed-przejazdami-kolejowymi` | 5 |
| Znaki drogowe poziome | `znaki-drogowe-poziome` | 6 |
| Sygnaly swietlne | `sygnaly-swietlne` | 3 |
| Kontrolki w samochodzie | `kontrolki-w-samochodzie` | 5 |
| Osoba kierujaca ruchem | `osoba-kierujaca-ruchem` | 4 |
| Znaki wojskowe | `znaki-wojskowe` | 7 |
| Sygnaly dla tramwajow | `sygnaly-dla-tramwajow` | 4 |
| Znaki tramwajowe | `znaki-tramwajowe` | 6 |
| Urzadzenia bezpieczenstwa ruchu | `urzadzenia-bezpieczenstwa-ruchu` | 6 |

Decyzja po audycie:

- Sprint 1/2 budujemy na kategoriach `A/B/C/D`, czyli: ostrzegawcze, zakazu, nakazu, informacyjne.
- Kategorie spoza `A/B/C/D` ukrywamy w MVP treningu, zamiast pokazywac je jako `w przygotowaniu`; dzieki temu UI pozostaje prosty, a uzytkownik nie widzi martwych wejsc.
- Publiczny katalog `/znaki-drogowe` nadal pokazuje wszystkie opublikowane kategorie i znaki.

### Sprint 1 - Fundament backendu

Cel: osobny, izolowany fundament progresu znakow.

Zakres:

- migracje nowych tabel,
- modele i relacje,
- serwis planowania lekkiej sesji znakow,
- serwis filtrowania trainable signs,
- test dostepu dla `/nauka/znaki-drogowe` pod `product.access`,
- test, ze `/znaki-drogowe/nauka` nie jest publiczna trasa treningowa,
- testy jednostkowe doboru sesji,
- brak UI poza ewentualnym endpointem testowym.

Deliverable:

- `UserTrafficSignProgress`,
- `TrafficSignLearningSession`,
- testy doboru: new / learning / needs_review / mastered.

No-go:

- nie podpinac do `study_sessions`,
- nie dotykac `ReviewPlannerService`.

Status 2026-05-12:

- wykonane: migracje `traffic_sign_learning_sessions`, `traffic_sign_learning_answers`, `user_traffic_sign_progress`,
- wykonane: modele `TrafficSignLearningSession`, `TrafficSignLearningAnswer`, `UserTrafficSignProgress`,
- wykonane: `TrafficSignLearningCorpusService` filtrujacy tylko opublikowane, kompletne znaki z MVP `A/B/C/D`,
- wykonane: `TrafficSignLearningPlannerService` ukladajacy plan 12 znakow z priorytetem `needs_review`, `learning`, `new`, `mastered`,
- wykonane: prywatna trasa `GET /nauka/znaki-drogowe` pod `auth + verified + product.access`,
- wykonane: kafel `Znaki drogowe` na `/nauka` po lewej stronie `Nauka klasyczna`,
- wykonane: trasa dodana do grupy Ziggy `app`, zeby kafel dzialal po stronie Vue,
- wykonane: minimalny prywatny widok startowy z procentem opanowania, kategoriami i podgladem planu kolejnej sesji,
- wykonane: testy dostepu, braku publicznej trasy `/znaki-drogowe/nauka`, filtrowania korpusu, planera i obecnosci route w Ziggy.

QA 2026-05-12:

- `docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/TrafficSignLearningTest.php` - zielone,
- `docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/SessionPageTest.php` - zielone,
- `npm run build` - zielone,
- dev DB zmigrowana po poprawce skroconych nazw constraintow PostgreSQL,
- browser QA: `/nauka` pokazuje kafel `Znaki drogowe`, `/nauka/znaki-drogowe` laduje bez bledow konsoli.

Notatka:

- `Rozpocznij trening` jest jeszcze nieaktywny, bo zapis odpowiedzi i przejscie przez cala sesje nalezy do Sprintu 2.
- Publiczny katalog `/znaki-drogowe` nie zostal zmieniony.

### Sprint 2 - MVP UI: rozpoznaj znak

Cel: pierwszy dzialajacy trening `znak -> znaczenie`.

Zakres:

- kafel trybu na `/nauka`, ustawiony po lewej stronie `Nauka klasyczna`,
- trasa `/nauka/znaki-drogowe`,
- ekran startowy,
- ekran sesji z duzym znakiem i 4 odpowiedziami,
- zapis odpowiedzi,
- feedback po odpowiedzi,
- podstawowe podsumowanie.
- blokada dostepu bez `product.access`.

Deliverable:

- uzytkownik moze przejsc cala sesje 12 znakow.

QA:

- desktop i mobile,
- keyboard/focus,
- brak regresji na `/znaki-drogowe`.

Status 2026-05-12:

- wykonane: `POST /nauka/znaki-drogowe` startuje osobna sesje znakow,
- wykonane: `GET /nauka/znaki-drogowe/teraz` pokazuje aktualny znak i 4 odpowiedzi tekstowe,
- wykonane: `POST /nauka/znaki-drogowe/odpowiedzi` zapisuje odpowiedz, czas reakcji i feedback,
- wykonane: odpowiedz bledna zapisuje `needs_review` oraz `last_confused_with_traffic_sign_id`,
- wykonane: odpowiedz poprawna przesuwa znak do `learning`, a 3 poprawne z rzedu sa przygotowane pod `mastered`,
- wykonane: po ostatniej odpowiedzi sesja przechodzi na wynik `/nauka/znaki-drogowe/wynik/{session}`,
- wykonane: widoki Inertia `TrafficSignLearning/Show` i `TrafficSignLearning/Result`,
- wykonane: przycisk `Rozpocznij trening` na panelu znakow jest aktywny.

QA 2026-05-12:

- `docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/TrafficSignLearningTest.php` - zielone,
- `docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/SessionPageTest.php` - zielone,
- `npm run build` - zielone,
- browser QA: start sesji, pierwsza odpowiedz, feedback i przejscie `Dalej` dzialaja bez bledow konsoli.

Notatka:

- MVP dziala jako jedna mieszana sesja `A/B/C/D`; wybieranie konkretnej kategorii przechodzi do Sprintu 3.
- Publiczny katalog `/znaki-drogowe` nadal nie ma publicznego CTA treningowego.

### Sprint 3 - Progres i kategorie

Cel: uzytkownik widzi, gdzie jest i co ma opanowane.

Zakres:

- progres per kategoria,
- filtry kategorii,
- stan `mastered`,
- stan `needs_review`,
- podsumowanie kategorii.

Deliverable:

- kafelki kategorii z procentem opanowania,
- sesje kategorii.

Status 2026-05-13:

- wykonane: kafelki kategorii pokazują `mastered / total`, procent opanowania oraz liczniki `Nowe`, `Nauka`, `Powt.`,
- wykonane: przycisk `Trenuj kategorię` startuje sesję ograniczoną do wybranej kategorii MVP,
- wykonane: główny przycisk `Mieszany trening` nadal uruchamia sesję mieszaną A/B/C/D,
- wykonane: sesja zapisuje w `payload.category_slugs`, czy była mieszana, czy kategorii,
- wykonane: testy pilnują, że sesja kategorii bierze wyłącznie znaki z wybranej rodziny,
- wykonane: testy pilnują progresu per kategoria dla `new`, `learning`, `needs_review`, `mastered`.

QA 2026-05-13:

- `docker compose exec -T app php artisan test tests/Feature/TrafficSignLearningTest.php` - zielone,
- `docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php` - zielone,
- `npm run build` - zielone,
- browser QA: panel pokazuje liczniki kategorii, a `Trenuj kategorię` dla `Znaki zakazu` startuje pytania z odpowiedziami tej kategorii.

Notatka:

- Sprint 3 nie dodaje jeszcze filtrów wewnątrz trwającej sesji; wybór zakresu odbywa się przed startem sesji.
- Publiczny katalog `/znaki-drogowe` nadal pozostaje tylko katalogiem wiedzy.

### Sprint 4 - Podobne znaki

Cel: najwieksza wartosc edukacyjna.

Zakres:

- model par/grup podobienstwa,
- runtime fallback do istniejacych stron porownawczych, zeby tryb dzialal nawet przed seedowaniem tabeli,
- seed pierwszych grup z istniejacych stron porownawczych,
- tryb `Podobne znaki`,
- dobieranie odpowiedzi blednych najpierw z grup porownawczych,
- zapisywanie pomylek `confused_with`,
- podsumowanie pomylek w wyniku sesji.

Deliverable:

- trening rozrozniania podobnych znakow,
- podsumowanie: `Najczesciej mylisz z...`.

Status 2026-05-13:

- wykonane: migracja `traffic_sign_confusion_pairs` oraz model `TrafficSignConfusionPair`,
- wykonane: `TrafficSignConfusionPairService`, ktory czyta relacje z tabeli i z `TrafficSignSupportingPageCatalog`,
- wykonane: `traffic-signs:sync-confusion-pairs`, czyli komenda materializujaca pary do DB pod szybkie sesje i panel admina,
- wykonane: panel admina Filament `Podobne znaki` do przegladania, filtrowania i recznej korekty relacji,
- wykonane: przypomnienie/przycisk `Synchronizuj po deployu` w panelu admina `Podobne znaki`, ktory odswieza pary bez ruszania recznych relacji,
- wykonane: po materializacji sesja czyta najpierw pary z DB, a supporting pages zostaja jako bezpieczny fallback,
- wykonane: przy budowaniu sesji korpus znakow jest ladowany raz i przekazywany do doboru distractorow, zeby uniknac powtarzania tych samych zapytan,
- wykonane: tryb sesji `similar_signs`,
- wykonane: przycisk `Podobne znaki` na `/nauka/znaki-drogowe`,
- wykonane: w trybie podobnych znakow distractory sa dobierane najpierw z relacji porownawczych, np. `A-7` vs `B-20`,
- wykonane: wynik sesji pokazuje sekcje `Najczesciej mylisz z...`,
- wykonane: testy pokrywaja fallback ze stron porownawczych, materializacje par, panel admina i start sesji podobnych znakow.

QA 2026-05-13:

- `docker compose exec -T app php artisan test tests/Feature/TrafficSignLearningTest.php` - zielone,
- `docker compose exec -T app php artisan test tests/Feature/TrafficSignLearningTest.php tests/Feature/SessionPageTest.php` - zielone,
- `npm run build` - zielone.
- dev DB zmigrowana migracja `traffic_sign_confusion_pairs`,
- browser QA: `/nauka/znaki-drogowe` pokazuje `Podobne znaki`, sesja startuje, feedback dziala, wynik pokazuje `Najczesciej mylisz z...`, konsola bez bledow.

Do domkniecia Sprintu 4 / decyzja opcjonalna:

- po deployu uruchomic `php artisan traffic-signs:sync-confusion-pairs --fresh`, zeby tabela byla glownym szybkim zrodlem relacji,
- alternatywnie wejsc w admin panel `Podobne znaki` i kliknac `Synchronizuj po deployu`,
- runtime fallback zostaje jako zabezpieczenie na wypadek pustej tabeli albo brakujacej relacji.

### Sprint 5 - Tryb odwrocony

Cel: sprawdzic rozumienie opisu.

Zakres:

- `opis -> wybierz znak`,
- dobieranie obrazow jako odpowiedzi,
- progres wspolny z tym samym znakiem, ale z osobnym `answer_mode`.

Deliverable:

- drugi typ pytania w sesji,
- mieszanie trybow w sesji po MVP.

Status 2026-05-13:

- wykonane: nowy tryb startu sesji `description_to_sign`,
- wykonane: osobny `answer_mode = meaning_to_sign` na poziomie odpowiedzi, bez mieszania z trenerem pamieci ani z pytaniami egzaminacyjnymi,
- wykonane: ekran sesji potrafi pokazac opis znaku po lewej i kafle z obrazami znakow jako odpowiedzi,
- wykonane: odpowiedzi, scoring, progres i wynik korzystaja z tego samego izolowanego modelu `traffic_sign_learning_*` oraz `user_traffic_sign_progress`,
- wykonane: kontynuacja z ekranu wyniku zachowuje wybrany tryb `description_to_sign`,
- wykonane: test feature pokrywa start sesji `Opis -> znak`, `answer_mode`, prompt i obrazkowe odpowiedzi.

QA 2026-05-13:

- `docker compose exec -T app php artisan test tests/Feature/TrafficSignLearningTest.php` - zielone,
- `docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php` - zielone,
- `npm run build` - zielone,
- browser QA: `/nauka/znaki-drogowe` pokazuje przycisk `Opis -> znak`, sesja startuje na `/nauka/znaki-drogowe/teraz`, ekran pokazuje opis po lewej i obrazkowe odpowiedzi, feedback po odpowiedzi dziala.

Notatka:

- Sprint 5 nie miesza jeszcze typow pytan w jednej sesji. To zostaje jako osobna decyzja po MVP, zgodnie z ostroznym podejsciem do ryzyka.

### Sprint 6 - QA, polish i release

Cel: przygotowac modul do merge i obserwacji.

Zakres:

- testy feature,
- testy serwisow,
- manual QA,
- Lighthouse/basic a11y,
- monitoring podstawowych metryk,
- dokumentacja uzytkowa.

Deliverable:

- gotowy modul MVP,
- plan obserwacji metryk po releasie.

Status 2026-05-13:

- wykonane: `TrafficSignLearningMetricsService` agreguje podstawowe metryki z istniejacych tabel `traffic_sign_learning_sessions` i `traffic_sign_learning_answers`,
- wykonane: komenda `php artisan traffic-signs:learning-stats --days=7` pokazuje raport dla czlowieka,
- wykonane: `php artisan traffic-signs:learning-stats --days=7 --json` zwraca JSON pod prosty monitoring lub snapshot po deployu,
- wykonane: raport obejmuje rozpoczecia, ukonczenia, completion rate, sredni wynik, sredni czas odpowiedzi, rozbicie po trybach, najczesciej mylone znaki i najslabsze kategorie,
- wykonane: test `tests/Feature/Console/TrafficSignLearningStatsCommandTest.php` pilnuje metryk i rejestracji komendy,
- wykonane: drobny polish a11y dla trybu `Opis -> znak` - obraz w kaflu odpowiedzi nie dubluje etykiety dla czytnika ekranu, bo nazwa odpowiedzi jest juz dostepna przez ukryty tekst.

QA 2026-05-13:

- `docker compose exec -T app php artisan test tests/Feature/Console/TrafficSignLearningStatsCommandTest.php` - zielone,
- `docker compose exec -T app php artisan test tests/Feature/TrafficSignLearningTest.php tests/Feature/Console/TrafficSignLearningStatsCommandTest.php` - zielone,
- `npm run build` - zielone,
- `docker compose exec -T app php artisan list traffic-signs` - komendy `traffic-signs:learning-stats` i `traffic-signs:sync-confusion-pairs` widoczne.

Performance pass 2026-05-13:

- przyczyna wolnego ladowania byla po stronie backendu: ekran startowy kilka razy pobieral ten sam korpus znakow i progres uzytkownika, a start sesji tworzyl odpowiedzi po jednej przez 12 osobnych insertow,
- wykonane: `TrafficSignLearningCorpusService::dashboard()` buduje jeden snapshot danych dla ekranu startowego,
- wykonane: `TrafficSignLearningPlannerService::planFromSnapshot()` uklada plan z juz pobranych znakow i progresu,
- wykonane: `TrafficSignLearningSessionService::start()` pobiera korpus raz dla planu i zapisuje odpowiedzi sesji jednym `insert`,
- wykonane: zapis odpowiedzi przelicza pelny wynik sesji dopiero przy ostatnim pytaniu; w trakcie sesji robi tylko tani check, czy zostaly pytania.

Benchmark backendowy przed/po:

| Sciezka | Przed | Po | Zapytania przed | Zapytania po |
| --- | ---: | ---: | ---: | ---: |
| Panel `indexPayload` | 265.67 ms | 165.47 ms | 9 | 3 |
| Start `recognition` | 571.48 ms | 140.94 ms | 18 | 5 |
| Odpowiedz w trakcie sesji | 108.52 ms | 67.99 ms | 11 | 6 |
| Start `similar_signs` | 512.26 ms | 182.15 ms | 30 | 18 |
| Start `description_to_sign` | 505.81 ms | 117.77 ms | 18 | 5 |

Uwaga QA: probny pomiar w aktywnej karcie przegladarki trafil na `/login`, wiec miarodajne porownanie zapisujemy z powtarzalnego benchmarku serwisow backendowych. Po zalogowaniu warto jeszcze przejsc caly flow w UI i potwierdzic odczuwalna poprawe.

Feedback pass 2026-05-13:

- problem: po kliknieciu odpowiedzi frontend czekal na `POST /nauka/znaki-drogowe/odpowiedzi`, a potem na drugi pelny request `GET /nauka/znaki-drogowe/teraz?feedback=...`,
- wykonane: `Show.vue` zapisuje odpowiedz przez lekki JSON request i aktualizuje feedback lokalnie, bez redirectu Inertia przy kazdej odpowiedzi,
- wykonane: backend nadal zapisuje scoring i progres w tym samym serwisie, a klasyczny redirect zostaje dla zwyklych requestow i testow kompatybilnosci,
- wykonane: JSON zwraca tylko maly patch feedbacku (`answered`, `has_more`, `feedback`, `next_url`), zamiast renderowac cale pytanie od nowa.
- wykonane: frontend pokazuje optymistyczne podswietlenie odpowiedzi natychmiast po kliknieciu, a backend tylko potwierdza zapis i dosyla pelne wyjasnienie,
- wykonane: z walidacji requestu usunieto kosztowne `exists`, bo serwis i tak sprawdza, czy odpowiedz nalezy do uzytkownika i czy wybrany znak jest jedna z dozwolonych opcji,
- wykonane: `GET /nauka/znaki-drogowe/teraz` obsluguje JSON, dzieki czemu nastepne pytanie moze byc pobrane w tle po pokazaniu feedbacku.

Benchmark feedbacku przed/po:

| Sciezka | Przed | Po | Zapytania przed | Zapytania po |
| --- | ---: | ---: | ---: | ---: |
| Odpowiedz + feedback | 107.32 ms | 52.59 ms | 13 | 7 |
| Controller JSON po zdjeciu `exists` | - | 50.25 ms | - | 7 |
| Preload nastepnego pytania JSON | - | 31.21 ms | - | 7 |

Najwazniejsza roznica UX: potwierdzenie odpowiedzi jest teraz natychmiastowe po stronie UI, a request backendowy pracuje w tle. `Dalej` moze korzystac z pytania pobranego w tle, wiec przejscie do kolejnego kroku nie musi czekac na pelny redirect Inertia.

Snapshot/batch pass 2026-05-13:

- problem: mimo lekkiego JSON feedbacku poprawna odpowiedz nadal musiala czekac na backend przed realnym przejsciem do kolejnego znaku,
- decyzja: sesja znakow jest traktowana jako statyczny snapshot 12 pozycji; backend nadal tworzy i przechowuje wszystkie `traffic_sign_learning_answers` przy starcie sesji,
- wykonane: `TrafficSignLearningSessionService::sessionQuestionsPayload()` zwraca caly pakiet pytan sesji z opcjami, poprawna odpowiedzia i wyjasnieniem,
- wykonane: `TrafficSignLearning/Show.vue` przechodzi miedzy znakami lokalnie z pelnego snapshotu, bez czekania na backend po kazdej odpowiedzi,
- wykonane: poprawna odpowiedz nie pokazuje wyjasnienia ani przycisku `Dalej`; po krotkim zielonym potwierdzeniu ekran przechodzi do kolejnego znaku,
- wykonane: bledna odpowiedz nadal pokazuje wyjasnienie i wymaga swiadomego klikniecia `Dalej`,
- wykonane: odpowiedzi trafiaja do lokalnej kolejki i sa synchronizowane batchowo przez `POST /nauka/znaki-drogowe/odpowiedzi/sync`,
- wykonane: przed ekranem wyniku frontend wymusza flush kolejki, zeby wynik, progres i statystyki po stronie backendu byly kompletne,
- zabezpieczenie: batch sync jest idempotentny dla ponowienia tej samej odpowiedzi; backend nadal sprawdza wlasciciela odpowiedzi i dozwolone opcje,
- ryzyko resztkowe: zamkniecie karty w ulamku sekundy po kliknieciu moze przerwac zaplanowany background sync; mitigacja MVP to szybki debounce, flush przy odmontowaniu widoku i obowiazkowy flush przed wynikiem.

QA snapshot/batch:

- `docker compose exec -T app php artisan test tests/Feature/TrafficSignLearningTest.php` - zielone,
- `npm run build` - zielone,
- dodany test batch sync: kilka odpowiedzi moze zostac zapisanych jednym requestem i obecna sesja przechodzi do kolejnej pozycji.

Do domkniecia Sprintu 6:

- manual QA czterech scenariuszy kont z sekcji `Testy reczne MVP`,
- podstawowy przeglad a11y/Lighthouse na `/nauka/znaki-drogowe`, `/nauka/znaki-drogowe/teraz` i wyniku,
- decyzja, czy przed merge robimy commit calosci czy jeszcze jeden maly pass UI.

## 12. Metryki po wdrozeniu

Minimalnie:

- liczba rozpoczec sesji,
- liczba ukonczonych sesji,
- sredni wynik,
- znaki najczesciej mylone,
- kategorie z najnizszym opanowaniem,
- powroty do modulu po 24h/7d.

Nie musimy od razu robic rozbudowanej analityki. Wystarczy tyle, zeby wiedziec, czy modul pomaga i gdzie uzytkownicy sie myla.

Stan implementacji metryk:

- MVP monitoringu jest dostepne przez `php artisan traffic-signs:learning-stats --days=7`,
- do automatyzacji mozna uzyc `php artisan traffic-signs:learning-stats --days=7 --json`,
- komenda jest read-only i nie modyfikuje sesji, progresu ani publicznego katalogu znakow.

## 13. Decyzje MVP po drugim przegladzie

Przyjmujemy na start:

- MVP: `A/B/C/D`, bo te rodziny sa najlepiej opisane i maja osobne inwentarze,
- sesja: `12` znakow,
- `mastered`: 3 poprawne z rzedu,
- wejscie: kafel `Znaki drogowe` na `/nauka` po lewej stronie `Nauka klasyczna`, potem mieszane + wybor kategorii znakow,
- trening wymaga konta dla zapisu progresu,
- modul nie ma publicznego landing page'a w MVP,
- dostep: `auth + verified + product.access`,
- podobne znaki: Sprint 4, nie MVP,
- pierwsze seedowanie podobnych znakow czytamy z istniejacych supporting pages, ale zapisujemy do osobnego katalogu relacji dopiero w Sprincie 4.

Aktualny etap po Sprincie 3:

- decyzja podjeta: Sprint 4 zaczynamy od istniejacych stron porownawczych, bo sa juz merytorycznie dobrane i ograniczaja ryzyko recznego zgadywania par podobienstwa.
- decyzja podjeta: relacje materializujemy do DB komenda `traffic-signs:sync-confusion-pairs`, bo zalezy nam na szybkim dzialaniu sesji i panelu admina. Runtime fallback zostaje tylko jako zabezpieczenie.
