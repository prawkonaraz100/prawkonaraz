# Public Demo 20 Pytan - Player Klasycznej Nauki

Stan na: 2026-05-14

## 0. Status wdrozenia

Status: **MVP + zamrozony pakiet demo wdrozone w branchu `codex/study-page-main-layout`**.

Zaimplementowano:

- landing `/testy-na-prawo-jazdy` jako ten sam player co demo, przykryty glass overlay z CTA,
- demo `/testy-na-prawo-jazdy/demo` dostepne bez logowania,
- demo renderuje ten sam komponent playera co nauka klasyczna: `StudySessions/Show.vue`,
- landing-preview rowniez renderuje `StudySessions/Show.vue`, ale bez zapisu stanu sesji demo i z blokada interakcji pod szyba,
- izolowany backend demo oparty o Laravel session, bez zapisu do prawdziwych tabel nauki,
- publiczny payload pytania kompatybilny z multimediami, wyjasnieniami i adnotacjami,
- answer endpoint zwraca format zgodny z klasycznym playerem i ma walidacje aktywnego pytania oraz blokade stale answers,
- `/testy-na-prawo-jazdy/demo` pracuje w trybie `publicDemo.mode = frozen_packet`,
- frontend liczy odpowiedz demo lokalnie z pelnego pakietu pytan, wiec nie wysyla requestu po kazdym kliknieciu TAK/NIE,
- koniec demo wysyla jeden lekki sync `answers` do publicznego endpointu `complete`,
- `cacheQuestion()` w publicznym demo nie nadpisuje pelnego pytania ubozszym payloadem bez `correct_answer`, wyjasnien i adnotacji,
- ekran wyniku z CTA zaleznym od statusu uzytkownika,
- testy feature potwierdzajace brak zapisow do `study_sessions`, `study_session_answers`, `user_question_progress` i `review_memory_progress`.

Weryfikacja wykonana:

- `docker compose exec -T app php artisan test tests/Feature/PublicDemoStudyTest.php` - zielone,
- `npm run build` - zielone.
- smoke test w przegladarce: `/testy-na-prawo-jazdy` pokazuje player pod glass overlay, CTA przechodzi do `/testy-na-prawo-jazdy/demo?fresh=1`.

Do pozniejszego dopracowania:

- manual QA desktop/mobile na realnym zestawie pytan,
- kuratorski wybor konkretnych 20 `external_id` w `config/public_demo.php`,
- opcjonalne eventy analityczne demo,
- ewentualny panel admina do zarzadzania zestawem demo, jesli config przestanie wystarczac.

## 0.1 Decyzja architektoniczna po MVP - zamrozony pakiet demo

Po testach MVP podjelismy decyzje i wdrozylismy, ze publiczny tryb demo dziala jako **zamrozony pakiet 20 pytan**, a nie jako lekka wersja pelnej sesji backendowej.

Problem, ktory chcemy usunac:

- po kliknieciu odpowiedzi w niektorych momentach przycisk TAK/NIE pokazuje kolor niebieski zamiast zielonego/czerwonego,
- niebieski oznacza stan posredni: frontend wie, co wybral uzytkownik, ale nie ma jeszcze pewnego wyniku,
- w demo ten stan pojawia sie wtedy, gdy kolejne pytanie jest aktywowane z payloadu bez `correct_answer` albo gdy odpowiedz musi poczekac na backend,
- dla demo produktowego to jest niepotrzebne, bo chcemy natychmiastowy, plynny feedback.

Rekomendowany kierunek:

- backend przy starcie `/testy-na-prawo-jazdy/demo` zwraca caly, kuratorowany pakiet 20 pytan,
- kazde pytanie w tym pakiecie ma komplet danych potrzebnych do lokalnej pracy playera: odpowiedzi, poprawna odpowiedz, wyjasnienie, adnotacje i media,
- frontend liczy wynik odpowiedzi lokalnie,
- backend nie zapisuje odpowiedzi po kazdym pytaniu,
- po zakonczeniu demo wysylamy jeden lekki sync odpowiedzi do sesji publicznego demo, ale nie zapisujemy go jako normalnej nauki.

Najwazniejsza granica bezpieczenstwa:

- ten pelny publiczny payload moze istniec **tylko** w `publicDemo.enabled === true`,
- nie wolno przeniesc tej logiki do egzaminu,
- nie wolno przeniesc jej do trenera pamieci,
- nie wolno uzyc jej jako domyslnego zachowania `StudySessions/Show.vue`,
- klasyczna nauka zalogowanego uzytkownika moze dalej uzywac swojego obecnego kontraktu, bo tam zapis progresu jest potrzebny.

## 1. Cel dokumentu

Ten dokument opisuje plan wdrozenia publicznego demo playera nauki na stronie:

- `/testy-na-prawo-jazdy`

Cel produktu:

- pokazac uzytkownikowi realna wartosc naszego playera przed rejestracja lub zakupem,
- dac szybka probe 20 pytan w stylu klasycznej nauki,
- pokazac media, adnotacje, wyjasnienia i flow odpowiedzi,
- nie mieszac demo z prawdziwa nauka, statystykami, trenerem pamieci ani postepem uzytkownika.

To ma byc demo produktu, nie kolejny pelny tryb nauki.

## 2. Aktualny stan kodu

### Trasa publiczna

`/testy-na-prawo-jazdy` ma juz dedykowany locked preview demo:

- [routes/web.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/routes/web.php)
- komponent: [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

Ten sam player pod spodem jest przykryty glass overlay. Overlay ma tylko dwa wejscia:

- `Sprawdz 20 pytan probnych` -> `/testy-na-prawo-jazdy/demo?fresh=1`,
- `Zobacz pelny dostep` -> `/cennik`.

Landing-preview nie zapisuje ani nie resetuje sesji demo. Dopiero wejscie w `/demo?fresh=1` startuje swiezy flow.

Landing prowadzi do izolowanego demo:

- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- [PublicDemoStudyController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/PublicDemoStudyController.php)
- [PublicDemoSessionService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/PublicDemoSessionService.php)

### Player klasycznej nauki

Glowny player siedzi w:

- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

Backend sesji:

- [StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)
- [StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)
- [StudySessionAnswerController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionAnswerController.php)

Istotne ograniczenie:

- `study_sessions.user_id` jest wymagane i powiazane z realnym kontem.
- Normalne odpowiedzi moga wplywac na progres i inne mechanizmy nauki.
- Demo publiczne nie powinno korzystac z normalnej tabeli `study_sessions` jako sesja goscia.

## 3. Decyzja produktowa

Rekomendowany wariant: **20 pytan probnych w klasycznej nauce jako izolowany publiczny demo-flow**.

Nie robimy:

- publicznego Zen mode,
- publicznego egzaminu,
- publicznego trenera pamieci,
- sesji zapisywanej do normalnych statystyk uzytkownika,
- automatycznego dodawania pytan demo do progresu po pozniejszej rejestracji.

Dlaczego klasyczna nauka:

- najlepiej pokazuje player,
- najlepiej pokazuje przewage produktu: media, adnotacje, wyjasnienia, szybki feedback,
- nie stresuje jak egzamin,
- nie jest tak ascetyczna jak Zen mode,
- naturalnie prowadzi do CTA: "Chcesz tak przerobic cala kategorie? Zaloz konto / Aktywuj pelna nauke".

## 4. Zasady nienaruszalne

1. Demo nie zapisuje do `user_question_progress`.
2. Demo nie zapisuje do `review_memory_progress`.
3. Demo nie tworzy realnych sesji w `study_sessions` dla gosci.
4. Demo nie tworzy odpowiedzi w `study_session_answers`.
5. Demo nie zmienia `target_category_id`.
6. Demo nie wymaga logowania.
7. Demo ma miec limit 20 pytan.
8. Demo ma byc stabilne i QA-owalne: ten sam zestaw pytan, ta sama kolejnosc lub kontrolowana rotacja.
9. Demo ma byc szybkie: minimalne zapytania, cache payloadu pytan, prefetch nastepnego pytania.
10. Demo ma miec jasny koniec i CTA do rejestracji/zakupu.

## 5. Docelowy flow UX

### 5.1 Landing `/testy-na-prawo-jazdy`

Na stronie publicznej pokazujemy ten sam ekran playera co w `/testy-na-prawo-jazdy/demo`, ale w trybie locked preview:

- player jest widoczny pod polprzezroczysta szyba,
- elementy pod spodem nie sa klikalne,
- na szybie sa tylko dwa CTA: `Sprawdz 20 pytan probnych` i `Zobacz pelny dostep`,
- nie ma dodatkowych sekcji, statystyk, kart marketingowych ani dlugiego copy.

Cel UX: uzytkownik ma poczuc, ze realny modul egzaminacyjny jest juz pod spodem, a oddziela go tylko szyba.

### 5.2 Start demo

Po kliknieciu CTA:

- uzytkownik trafia do `/testy-na-prawo-jazdy/demo`,
- widzi od razu pierwsze pytanie,
- nie przechodzi przez konfigurator kategorii,
- nie musi wybierac dzialu ani liczby pytan,
- nie widzi elementow platnych trybow.

### 5.3 W trakcie demo

UI powinien przypominac klasyczna nauke:

- licznik `1 / 20`,
- medium po lewej lub u gory zależnie od breakpointu,
- pytanie i odpowiedzi,
- player wideo z realnymi kontrolkami,
- po odpowiedzi: feedback i wyjasnienie,
- przy bledzie: mocniejsze wskazanie poprawnej odpowiedzi,
- przy pytaniu z adnotacjami: pokazujemy adnotacje tak jak w nauce.

Nie pokazujemy:

- globalnych statystyk,
- trenera pamieci,
- postepu dzialow,
- rankingu,
- wyboru kategorii,
- menu rozpraszajacego sesje.

### 5.4 Koniec demo

Po 20 pytaniach:

- wynik: liczba poprawnych odpowiedzi,
- krotkie podsumowanie: "Tak dziala klasyczna nauka w pelnym dostepie",
- CTA primary: `Zaloz konto i kontynuuj nauke`,
- CTA secondary: `Zobacz pelny dostep`,
- opcja: `Powtorz demo`.

Nie obiecujemy przeniesienia wyniku demo do konta. To ma byc jasne i uczciwe.

## 6. Dobor pytan demo

### 6.1 Rekomendacja

Na MVP wybieramy staly, kuratorowany zestaw 20 pytan, najlepiej z kategorii B.

Powody:

- stabilne QA,
- stabilne screeny i testy,
- szybki cache,
- brak losowych regresji,
- kontrola jakosci mediow i wyjasnien,
- mozemy swiadomie pokazac najlepsze elementy playera.

### 6.2 Kryteria pytan

Pytanie moze trafic do demo, jesli:

- jest `is_active = true`,
- nie ma `delivery_issue`,
- ma oficjalne ID/source,
- ma komplet odpowiedzi,
- ma poprawna odpowiedz,
- ma wyjasnienie lub shared explanation,
- jesli ma media, media musza dzialac szybko i stabilnie,
- jesli ma wideo, musi dobrze pokazywac wartosc odtwarzacza,
- nie powinno byc skrajnie niszowe ani mylace bez kontekstu kursu.

Zestaw powinien zawierac:

- kilka pytan z obrazem,
- kilka pytan z wideo,
- kilka pytan z adnotacjami,
- kilka pytan z wyraznym wyjasnieniem po bledzie,
- miks pytan podstawowych i specjalistycznych, ale bez udawania pelnego egzaminu.

### 6.3 Model danych dla zestawu

Rekomendowany model docelowy:

Nowa tabela `public_demo_questions`:

- `id`
- `question_id`
- `slot` lub `sort_order`
- `is_active`
- `label` nullable, np. `player-demo-2026`
- `notes` nullable
- timestamps

Unikalnosc:

- `unique(label, question_id)`
- indeks `label, is_active, sort_order`

Dlaczego tabela zamiast configu:

- admin moze docelowo wymieniac pytania bez deploya,
- mozna robic kilka zestawow demo,
- latwiej walidowac czy pytanie nadal jest aktywne,
- latwiej napisac testy integralnosci zestawu.

MVP dopuszczalny:

- config `config/public_demo.php` z lista `external_id + source`.

Ale lepszy kierunek to tabela, bo pytania sa danymi redakcyjnymi.

### 6.4 Wybrany kuratorowany zestaw MVP

Na obecnym etapie konfigurujemy demo przez `config/public_demo.php`, pole `player_demo.question_external_ids`.

Zestaw zostal dobrany dla kategorii B:

- 10 pytan ze zdjeciem,
- 10 pytan z filmem,
- wszystkie aktywne,
- wszystkie bez `delivery_issue`,
- wszystkie z poprawna odpowiedzia,
- wszystkie z wyjasnieniem,
- miks dzialow, zeby demo nie bylo monotonne.

#### Pytania ze zdjeciem

| external_id | DB id | temat | adnotacje |
|---:|---:|---|---:|
| 891 | 18130 | Znaki ostrzegawcze | 0 |
| 1091 | 18173 | Znaki zakazu/nakazu | 0 |
| 352 | 18093 | Znaki informacyjne | 0 |
| 589 | 18101 | Oznakowanie poziome | 5 |
| 469 | 18094 | Sygnaly swietlne | 0 |
| 3566 | 27555 | Skrzyzowania rownorzedne | 0 |
| 980 | 18145 | Pierwszenstwo przejazdu | 0 |
| 3122 | 25975 | Skrzyzowania z sygnalizacja | 0 |
| 1647 | 21677 | Przejscia/przystanki | 0 |
| 4391 | 29995 | Piesi | 0 |

#### Pytania z filmem

| external_id | DB id | temat | adnotacje |
|---:|---:|---|---:|
| 6010 | 30029 | Znaki ostrzegawcze | 1 |
| 3809 | 28522 | Znaki zakazu/nakazu | 2 |
| 1688 | 21687 | Znaki informacyjne | 3 |
| 599 | 18104 | Pierwszenstwo/STOP | 2 |
| 109 | 18091 | Sygnaly swietlne | 0 |
| 942 | 18140 | Skrzyzowania rownorzedne | 0 |
| 1172 | 20213 | Sygnalizacja | 1 |
| 630 | 18114 | Przejscia dla pieszych | 2 |
| 99 | 18089 | Piesi | 0 |
| 1068 | 18170 | Rowerzysci | 0 |

#### Adnotacje w adminie

Do wszystkich powyzszych pytan mozemy dodawac adnotacje z panelu admina w zasobie `Pytania`, sekcja `Adnotacje do medium`.

Zasady:

- pytania ze zdjeciem adnotujemy jako `question_image`,
- pytania z filmem adnotujemy jako `video_frame`,
- dla filmu trzeba ustawic `frame_time_seconds`,
- dostepne typy markerow: `label`, `text`, `circle`, `arrow`,
- mozna zapisywac tylko dla jednego pytania albo dla wszystkich pytan z tym samym `external_id`.

Nie dodajemy automatycznych adnotacji bez decyzji redakcyjnej. Markery powinny wskazywac realnie wazne miejsce w obrazie lub kadrze filmu.

## 7. Architektura backendu

### 7.1 Nowe trasy

Proponowane trasy publiczne:

- `GET /testy-na-prawo-jazdy`
  - landing publiczny
  - route name: `public.tests`

- `GET /testy-na-prawo-jazdy/demo`
  - ekran aktywnego demo
  - route name: `public.tests.demo.show`

- `POST /testy-na-prawo-jazdy/demo/answers`
  - zapis odpowiedzi w izolowanym stanie demo
  - route name: `public.tests.demo.answers.store`

- `POST /testy-na-prawo-jazdy/demo/restart`
  - restart demo
  - route name: `public.tests.demo.restart`

Opcjonalnie:

- `GET /testy-na-prawo-jazdy/demo/questions/{question}`
  - endpoint prefetchu nastepnego pytania,
  - route name: `public.tests.demo.questions.show`.

### 7.2 Middleware

Trasy demo:

- `web`,
- bez `auth`,
- bez `verified`,
- bez `product.access`,
- z limitem requestow dla answer endpointu.

Warto dodac throttle:

- landing: standardowy brak throttle albo bardzo wysoki limit,
- answer endpoint: np. `throttle:demo-answers`.

### 7.3 Kontroler

Nowy kontroler:

- `PublicDemoStudyController`

Odpowiedzialnosci:

- render landing/demo,
- pobranie zestawu 20 pytan,
- utworzenie lub odczytanie anonimowego stanu demo,
- transformacja pytania do payloadu frontendu,
- przyjecie odpowiedzi,
- zwrocenie feedbacku i wyjasnienia.

Kontroler nie powinien sam wybierac pytan ani liczyc reguly poprawnosci. To ida do serwisow.

### 7.4 Serwisy

Proponowane serwisy:

#### `PublicDemoQuestionSetService`

Odpowiada za:

- znalezienie aktywnego zestawu demo,
- walidacje, czy ma 20 pytan,
- odfiltrowanie pytan niedostarczalnych,
- zachowanie stabilnej kolejnosci.

#### `PublicDemoSessionService`

Odpowiada za:

- stan anonimowej sesji w Laravel session albo cache,
- biezacy indeks pytania,
- liste odpowiedzi,
- restart,
- ukonczenie demo.

Rekomendacja MVP:

- przechowywac stan w Laravel session,
- nie tworzyc tabeli sesji demo na start,
- nie zapisywac odpowiedzi w bazie.

Uzasadnienie:

- demo jest krotkie,
- nie potrzebujemy trwalej historii,
- mniejsze ryzyko zasmiecenia bazy,
- prostszy rollback.

#### Answer flow w `PublicDemoSessionService`

Odpowiada za:

- porownanie odpowiedzi z poprawna odpowiedzia,
- policzenie wyniku,
- zbudowanie feedbacku,
- zwrocenie poprawnej odpowiedzi i wyjasnienia dopiero po odpowiedzi.

#### Reuse payload builderow

Demo powinno reuse'owac:

- `QuestionMediaPayloadBuilder`
- `QuestionExplanationAnnotationPayloadBuilder`
- `QuestionExplanationAssetPayloadBuilder`
- `SharedQuestionExplanationAssetResolver` jesli potrzebny przez buildery

Nie powinno duplikowac logiki mediow.

## 8. Architektura frontendu

### 8.1 Widoki

Pliki:

- `resources/js/Pages/StudySessions/Show.vue`

### 8.2 Reuse playera

Aktualny [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue) jest glownym playerem nauki klasycznej.

Decyzja po doprecyzowaniu produktu:

- nie tworzymy osobnego playera demo,
- nie kopiujemy layoutu klasycznej nauki,
- nie utrzymujemy osobnego landing-playera dla `/testy-na-prawo-jazdy`,
- `/testy-na-prawo-jazdy` renderuje `StudySessions/Show.vue` jako locked preview z glass overlay,
- `/testy-na-prawo-jazdy/demo` renderuje `StudySessions/Show.vue`,
- do `Show.vue` dodajemy tylko maly adapter `publicDemo`, ktory podmienia endpoint odpowiedzi, endpoint zakonczenia i CTA wyniku,
- backend demo zwraca kontrakt taki sam, jak klasyczne `study-sessions.current.answers.store`.

To daje uzytkownikowi realny produkt:

- ten sam odtwarzacz,
- ten sam uklad,
- te same ustawienia nauki,
- te same odpowiedzi i feedback,
- ten sam ekran wyniku.

Rownoczesnie izolacja zostaje po stronie backendu:

- brak zapisow do realnej nauki,
- brak zapisow do progresu uzytkownika,
- brak zasilania trenera pamieci.

### 8.3 Minimalny MVP bez duzego refaktoru

MVP wdrozone:

- `PublicDemoStudyController` buduje propsy dla `StudySessions/Show.vue`,
- `PublicDemoSessionService` trzyma stan w Laravel session,
- `PublicDemoQuestionPayloadBuilder` buduje payload zgodny z `CurrentQuestion`,
- `Show.vue` ma opcjonalny `publicDemo` context do podmiany tras i CTA.

## 9. Stan odpowiedzi i feedbacku

### 9.1 Przed odpowiedzia

Frontend dostaje:

- pytanie,
- media,
- opcje odpowiedzi,
- numer pytania,
- liczbe wszystkich pytan,
- informacja czy to ostatnie pytanie.
- technicznie takze poprawna odpowiedz i wyjasnienie, bo tak dziala obecny player klasycznej nauki; UI nie ujawnia ich przed odpowiedzia.

Nie dostaje:

- danych adminowych,
- danych progresu uzytkownika.

### 9.1.1 Docelowy wariant frozen demo

W docelowym wariancie publiczne demo dostaje caly pakiet 20 pytan juz przy starcie. To jest celowe i ograniczone tylko do demo.

Kazdy element pakietu powinien miec:

- `id`,
- `external_id`,
- `source`,
- `prompt`,
- `question_type`,
- `structure_scope`,
- `difficulty`,
- `points`,
- `topic`,
- `options`,
- `media`,
- `correct_answer`,
- `explanation`,
- `explanation_asset`,
- `explanation_annotations`,
- `sign_language_assets` jako pusta lista, bo demo klasycznej nauki nie pokazuje PJM,
- flage pomocnicza np. `demo_reveal_payload: true`.

Dlaczego dajemy `correct_answer` od razu:

- demo nie jest egzaminem,
- demo ma sprzedac szybkosc i intuicyjnosc playera,
- bez `correct_answer` frontend nie moze natychmiast pokazac zielonego/czerwonego feedbacku,
- w publicznym demo pytania i tak sa kuratorowane i ograniczone do 20 sztuk.

Zasada UI:

- `correct_answer` jest w stanie aplikacji, ale nie jest renderowane przed odpowiedzia,
- poprawna odpowiedz pokazuje sie dopiero po wyborze,
- wyjasnienie pokazuje sie dopiero po wyborze lub zgodnie z ustawieniami feedbacku,
- nie pokazujemy zadnego "podgladu odpowiedzi" przed kliknieciem.

Granica izolacji:

- frozen payload jest budowany przez `PublicDemoQuestionPayloadBuilder`,
- nie modyfikujemy `StudySessionController::transformQuestion()` pod potrzeby demo,
- nie modyfikujemy `StudySessionApiPayloadBuilder::question()` pod potrzeby demo,
- nie modyfikujemy `StudySessionAnswerController` pod potrzeby demo,
- nie dotykamy `StudySessionManager`.

### 9.2 Po odpowiedzi

Endpoint odpowiedzi zwraca:

- `is_correct`,
- `selected_answer`,
- `correct_answer`,
- `correct_answer_text`,
- `explanation`,
- `explanation_asset`,
- `explanation_annotations`,
- zaktualizowany wynik demo,
- dane nastepnego pytania albo `completed = true`.

### 9.3 Automatyczne przejscie

W demo mozemy zastosowac podobny rytm jak w nauce:

- poprawna odpowiedz: krotki feedback i automatyczne przejscie po chwili,
- bledna odpowiedz: zatrzymanie na wyjasnieniu i przycisk `Dalej`.

Nie wolno chowac wyjasnienia przy bledzie.

## 10. Integracja z platnym produktem

Demo ma prowadzic do:

- rejestracji,
- aktywacji pelnej nauki,
- cennika.

Po zakonczonym demo:

- jesli user jest niezalogowany: CTA `Zaloz konto i kontynuuj`.
- jesli user jest zalogowany bez dostepu: CTA `Aktywuj pelna nauke`.
- jesli user jest zalogowany z dostepem: CTA `Przejdz do nauki`.

Nie przenosimy wyniku demo do konta. Ewentualnie mozemy pokazac copy:

`To demo nie zapisuje postepu. Pelna nauka zapisuje Twoje odpowiedzi, postep i powtorki.`

## 11. SEO i publiczny landing

`/testy-na-prawo-jazdy` powinno byc indeksowalne.

`/testy-na-prawo-jazdy/demo` moze byc indeksowalne tylko wtedy, gdy ma sensowny content bez duplikacji. Rekomendacja:

- landing indeksowalny,
- demo najlepiej `noindex`, bo to aplikacyjny ekran interaktywny.

Landing powinien miec:

- title SEO: `Testy na prawo jazdy 2026 - pytania i nauka z wyjasnieniami`
- meta description o oficjalnej bazie, nauce i 20 pytaniach probnych,
- link do oficjalnej bazy pytan,
- link do cennika,
- CTA do demo.

## 12. Analityka

MVP:

- bez zapisu szczegolowych odpowiedzi do bazy,
- mozna logowac tylko agregaty requestowe lub eventy frontendowe, jesli mamy gotowy system.

Docelowo mozna dodac `public_demo_events`:

- `session_started`,
- `question_answered`,
- `demo_completed`,
- `cta_clicked`.

Nie przechowujemy danych osobowych goscia.

## 13. Wydajnosc

Wymagania:

- wejscie na landing szybkie jak normalna publiczna strona,
- start demo bez dlugiego generowania losowej sesji,
- pierwszy payload demo moze byc cache'owany,
- media ladowane lazy,
- prefetch nastepnego pytania po zaladowaniu aktualnego,
- answer endpoint powinien odpowiadac szybko, bo feedback jest czescia UX.

Rekomendacje techniczne:

- cache zestawu 20 pytan per label, np. `public_demo_questions:player-demo-2026`,
- preload tylko nastepnego pytania, nie wszystkich filmow naraz,
- nie wykonywac ciezkich agregacji w publicznym demo,
- nie liczyc progresu uzytkownika,
- nie odpytywac trenera pamieci.

### 13.1 Docelowy model wydajnosci - jeden payload, lokalne odpowiedzi

Docelowo demo powinno zejsc z liczby requestow w trakcie sesji prawie do zera.

Aktualny MVP:

- `GET /testy-na-prawo-jazdy/demo` renderuje player,
- `POST /testy-na-prawo-jazdy/demo/answers` idzie po kazdej odpowiedzi,
- `POST /testy-na-prawo-jazdy/demo/complete` domyka sesje,
- backend pilnuje aktywnego pytania i zwraca `nextQuestion`.

Docelowy frozen demo:

- `GET /testy-na-prawo-jazdy/demo` zwraca pelny pakiet 20 pytan i pusty lokalny stan,
- frontend sam:
  - zapisuje odpowiedz w pamieci komponentu,
  - liczy `is_correct`,
  - aktualizuje licznik,
  - przechodzi do kolejnego pytania,
  - buduje wynik koncowy,
- `POST /testy-na-prawo-jazdy/demo/answers` staje sie niepotrzebny dla UI,
- `POST /testy-na-prawo-jazdy/demo/complete` jest opcjonalny i sluzy tylko do lekkiej analityki,
- restart demo resetuje lokalny stan i ewentualnie sessionStorage.

Rekomendowany zapis lokalny:

- podstawowy stan w Vue `ref`,
- opcjonalnie `sessionStorage` pod kluczem `public-demo-player-demo-2026`,
- zapis w `sessionStorage` powinien obejmowac tylko:
  - indeks aktualnego pytania,
  - odpowiedzi uzytkownika,
  - timestamp startu,
  - czy demo jest zakonczone.

Nie zapisujemy lokalnie:

- danych uzytkownika,
- danych konta,
- zadnych tokenow,
- zadnych informacji platniczych,
- niczego, co mogloby byc potraktowane jako oficjalny progres nauki.

Oczekiwany efekt:

- brak niebieskiego stanu po kliknieciu TAK/NIE,
- feedback zielony/czerwony natychmiast,
- brak oczekiwania na backend przy kazdej odpowiedzi,
- mniejsze obciazenie PHP/Postgres/session,
- mniej race condition przy ekranie wyniku,
- prostsze QA, bo zestaw i stan sa deterministyczne.

## 14. Bezpieczenstwo i anty-abuse

Ryzyka:

- publiczny endpoint odpowiedzi moze byc spamowany,
- demo ujawnia fragment produktu,
- pytania demo moga byc scrapowane,
- media moga generowac koszt transferu.

Mitigacje:

- throttle na answer endpoint,
- staly limit 20 pytan,
- brak API do pobierania dowolnego pytania,
- demo tylko dla kuratorowanego zestawu,
- opcjonalny cache i CDN dla mediow,
- brak zapisu danych osobowych,
- brak tworzenia kont tymczasowych po stronie backendu.

## 15. Ryzyka implementacyjne

### Ryzyko 1: skopiowanie calego `Show.vue`

Problem:

- powstanie drugi ogromny player,
- kazda poprawka w nauce trzeba bedzie robic dwa razy.

Mitigacja:

- nie kopiowac calego pliku,
- wydzielac komponenty prezentacyjne etapami.

### Ryzyko 2: uzycie normalnych `study_sessions` dla gosci

Problem:

- `study_sessions.user_id` jest wymagane,
- normalny flow jest zwiazany z realnym userem i progressem,
- latwo zanieczyscic statystyki.

Mitigacja:

- osobny demo-state w session/cache,
- brak zapisu do normalnych tabel nauki.

### Ryzyko 3: pytania bez wyjasnien albo popsute media

Problem:

- demo ma sprzedawac wartosc playera,
- slabe pytanie w demo obniza zaufanie.

Mitigacja:

- kuratorowany zestaw,
- test integralnosci zestawu,
- komenda/admin raport: brak mediow, brak wyjasnien, delivery_issue.

### Ryzyko 4: zbyt duzy zakres MVP

Problem:

- refaktor playera + publiczny landing + demo + admin naraz moze rozlac regresje.

Mitigacja:

- sprinty rozdzielone,
- najpierw dokument i zestaw pytan,
- potem backend demo,
- potem UI,
- na koncu landing/CTA.

### Ryzyko 5: wyciek `correct_answer` do egzaminu

Problem:

- frozen demo potrzebuje `correct_answer` przed odpowiedzia, zeby lokalnie kolorowac feedback,
- egzamin absolutnie nie moze dostac takiego payloadu,
- przypadkowe rozszerzenie wspolnego transformera mogloby zlamac logike egzaminu.

Mitigacja:

- frozen payload buduje tylko `PublicDemoQuestionPayloadBuilder`,
- `PublicDemoQuestionPayloadBuilder` nie moze byc wstrzykiwany do `StudySessionController`,
- nie zmieniamy `shouldRevealQuestionAnswer()` dla normalnych sesji,
- nie zmieniamy `StudySessionApiPayloadBuilder::question()` dla egzaminu,
- dodajemy test, ze `StudySessionController` dla egzaminu in-progress nie zwraca `correct_answer`.

### Ryzyko 6: trener pamieci zaczyna korzystac z lokalnego reveal

Problem:

- trener pamieci celowo ma odzielac faktyczna wiedze od zgadywania,
- zbyt wczesne ujawnienie poprawnej odpowiedzi mogloby znieksztalcic logike `Nie wiem`, recovery i due,
- trener pamieci nie powinien dziedziczyc demo shortcutow.

Mitigacja:

- warunek frontendowy dla frozen demo musi byc jawny: `isPublicDemoMode`,
- nie uzywamy `props.session.mode === 'learn'` jako sygnalu do lokalnego reveal,
- nie rozszerzamy `isLocalLearningMode` o nowe zachowanie dla demo,
- testujemy, ze `sr_review` nadal nie dostaje `correct_answer` w aktywnym pytaniu,
- `recordAnswer` trenera pamieci nadal idzie przez normalny backend i zapisuje progres.

### Ryzyko 7: normalna nauka przestaje zapisywac odpowiedzi

Problem:

- demo ma liczyc odpowiedzi lokalnie,
- normalna nauka musi dalej zapisywac progres, statystyki, powtorki i postep dzialow.

Mitigacja:

- lokalny answer engine wlaczamy tylko gdy `props.publicDemo?.mode === 'frozen_packet'`,
- `sessionAnswersRoute` dla normalnej nauki zostaje bez zmian,
- `StudySessionAnswerController` zostaje bez zmian,
- test regression: odpowiedz w `/nauka/teraz` tworzy/aktualizuje `study_session_answers` i `user_question_progress`.

### Ryzyko 8: publiczne demo robi sie drugim playerem

Problem:

- jezeli zaczniemy kopiowac player dla demo, powstana dwa uklady do utrzymania,
- kazda poprawka UI bedzie musiala byc robiona w dwoch miejscach.

Mitigacja:

- nadal uzywamy `StudySessions/Show.vue`,
- dodajemy maly adapter stanu demo, nie drugi layout,
- wydzielamy helpery tylko tam, gdzie zmniejszaja ryzyko, np. `usePublicDemoPacket`,
- nie robimy osobnego `PublicDemoPlayer.vue`, dopoki nie bedzie realnego powodu.

## 16. Plan sprintow

### Sprint 0 - decyzja i dokumentacja

Status: **wykonany**.

Zakres:

- zatwierdzic, ze demo ma byc klasyczna nauka,
- zatwierdzic limit 20 pytan,
- zatwierdzic izolacje od realnej nauki,
- zatwierdzic miejsce: `/testy-na-prawo-jazdy`.

### Sprint 1 - zestaw pytan demo

Status: **wykonany jako config MVP**.

Zakres:

- dodac model/tabele `public_demo_questions` albo config MVP,
- wybrac 20 pytan kategorii B,
- dodac test integralnosci zestawu,
- dodac serwis `PublicDemoQuestionSetService`.

Acceptance criteria:

- zestaw ma dokladnie 20 aktywnych pytan,
- kazde pytanie jest deliverable,
- minimum kilka pytan ma media,
- minimum kilka pytan ma wyjasnienia/adnotacje,
- test failuje, jesli zestaw traci jakosc.

### Sprint 2 - backend demo session

Status: **wykonany**.

Zakres:

- `PublicDemoStudyController`,
- `PublicDemoSessionService`,
- odpowiedzialnosc answer flow w `PublicDemoSessionService`,
- trasy demo,
- answer endpoint,
- stan w Laravel session/cache,
- brak zapisow do normalnych tabel nauki.

Acceptance criteria:

- gosc moze zaczac demo,
- gosc moze odpowiedziec na pytanie,
- backend zwraca feedback i nastepne pytanie,
- po 20 pytaniach demo jest completed,
- w bazie nie powstaje `study_sessions`,
- w bazie nie powstaje `study_session_answers`,
- w bazie nie powstaje `user_question_progress`.

### Sprint 3 - frontend player demo

Status: **wykonany**.

Zakres:

- `StudySessions/Show.vue` jako realny player demo,
- `publicDemo` adapter tras i CTA,
- UI klasycznej nauki bez osobnego klona,
- media stage,
- odpowiedzi,
- feedback,
- wyjasnienie,
- wynik koncowy,
- CTA.

Acceptance criteria:

- demo wyglada spojnie z klasyczna nauka,
- dziala desktop i mobile,
- poprawna odpowiedz daje szybki feedback,
- bledna odpowiedz pokazuje wyjasnienie,
- wideo i obraz dzialaja,
- nie ma rozpraszajacych elementow platnych trybow.

### Sprint 4 - locked preview `/testy-na-prawo-jazdy`

Status: **wykonany**.

Zakres:

- renderowac na landingu ten sam `StudySessions/Show.vue` co w demo,
- dodac glass overlay blokujacy interakcje pod spodem,
- zostawic tylko CTA `Sprawdz 20 pytan probnych` i `Zobacz pelny dostep`,
- nie zapisywac i nie resetowac sesji demo podczas samego wejscia na landing,
- usunac osobny custom landing player.

Acceptance criteria:

- strona wyglada jak prawdziwy player pod szyba,
- CTA prowadzi do swiezego `/testy-na-prawo-jazdy/demo?fresh=1`,
- klikniecia pod szyba nie uruchamiaja odpowiedzi,
- landing jest szybki,
- layout jest identyczny z playerem demo i spójny z klasyczna nauka.

### Sprint 5 - QA, mierniki i dopracowanie

Status: **czesciowo wykonany**.

Wykonane:

- testy feature demo,
- build frontendu,
- automatyczna kontrola braku zapisow do tabel prawdziwej nauki.

Nadal do wykonania:

- manual QA desktop/mobile,
- sprawdzenie realnego zestawu 20 pytan pod jakosc multimediow,
- opcjonalne mierniki analityczne.

Zakres:

- testy feature,
- testy frontend/build,
- manual QA w przegladarce,
- sprawdzenie mobile,
- sprawdzenie braku zapisow do realnych tabel,
- opcjonalne eventy analityczne.

Acceptance criteria:

- `php artisan test` dla demo przechodzi,
- `npm run build` przechodzi,
- demo dziala jako guest,
- zalogowany user z dostepem ma CTA do pelnej nauki,
- zalogowany user bez dostepu ma CTA do aktywacji,
- nie ma regresji w `/nauka`.

### Sprint 6 - frozen packet i lokalny answer engine demo

Status: **wdrozone**.

Cel:

- uproscic publiczne demo,
- usunac request backendowy po kazdej odpowiedzi,
- usunac niebieski stan TAK/NIE po kliknieciu,
- nie dotknac egzaminu, trenera pamieci ani normalnej nauki.

Zakres backend:

- `PublicDemoQuestionPayloadBuilder` zwraca pelny reveal payload dla demo,
- `publicDemo` context ma jawna flage:
  - `mode: 'frozen_packet'`,
- `PublicDemoStudyController::renderPlayer()` przekazuje `questionPool` jako kompletny pakiet 20 pytan,
- `PublicDemoSessionService` obsluguje:
  - start/restart,
  - kompatybilny endpoint `answers`,
  - finalny sync `complete` z lista odpowiedzi,
  - summary i wyniki demo,
- frontend demo nie potrzebuje endpointu `answers` do feedbacku.

Zakres frontend:

- dodac izolowany helper, np. `usePublicDemoPacket`, albo sekcje funkcji w `Show.vue`,
- po wyborze odpowiedzi w `isPublicDemoMode && publicDemo.mode === 'frozen_packet'`:
  - zbudowac `ResultItem` lokalnie,
  - porownac `selected_answer` z `activeQuestion.correct_answer`,
  - ustawic `is_correct`,
  - pokazac zielony/czerwony feedback natychmiast,
  - zapisac odpowiedz w `localResults`,
  - zaktualizowac lokalny progress,
  - przejsc do nastepnego pytania bez backendu,
  - po ostatnim pytaniu pokazac summary bez reloadu,
- nie zmieniac `selectAnswer()` dla egzaminu i trenera pamieci poza jawna bramka demo,
- zostawic obecne CTA wyniku: `Wroc do demo` i `Zaloz konto i kontynuuj`.

Zakres testow:

- test feature: `GET /testy-na-prawo-jazdy/demo` zwraca `publicDemo.mode = frozen_packet`,
- test feature: wszystkie pytania demo maja `correct_answer`,
- test feature: demo nie tworzy `study_sessions`,
- test feature: demo nie tworzy `study_session_answers`,
- test feature: demo nie tworzy `user_question_progress`,
- test feature: aktywny egzamin nie zwraca `correct_answer`,
- test feature: aktywny trener pamieci nie zwraca `correct_answer` przed odpowiedzia,
- test frontend/build: `npm run build`,
- test manual: TAK/NIE po kliknieciu nigdy nie zostaje niebieskie w demo.

Acceptance criteria:

- po kliknieciu dowolnej odpowiedzi w demo kolor zmienia sie od razu na zielony albo czerwony,
- DevTools network nie pokazuje requestu po kazdej odpowiedzi demo,
- ekran wyniku pojawia sie bez twardego reloadu,
- szybkie klikniecie CTA z wyniku nie jest nadpisywane przez pending redirect,
- egzamin nadal nie zna poprawnej odpowiedzi przed zapisem,
- trener pamieci nadal zapisuje odpowiedzi przez backend,
- klasyczna nauka zalogowanego usera nadal aktualizuje progres.

Rollback:

- zostawiamy endpoint `POST /testy-na-prawo-jazdy/demo/answers` przez jeden sprint,
- jesli frozen packet ma regresje, fallback to uzycie kompatybilnego endpointu `answers`,
- fallback przywraca poprzedni MVP bez migracji bazy.

## 17. Plan testow

### Feature tests

1. Guest widzi landing `/testy-na-prawo-jazdy`.
2. Guest moze wejsc w `/testy-na-prawo-jazdy/demo`.
3. Demo zwraca 20 pytan.
4. Demo nie wymaga auth.
5. Answer endpoint zwraca correct/incorrect.
6. Po ostatnim pytaniu demo pokazuje completed.
7. Demo nie tworzy `study_sessions`.
8. Demo nie tworzy `study_session_answers`.
9. Demo nie tworzy `user_question_progress`.
10. Demo nie pozwala odpowiedziec na pytanie spoza zestawu.
11. Zestaw demo nie zawiera pytan z `delivery_issue`.
12. Zestaw demo nie zawiera nieaktywnych pytan.
13. Frozen demo zwraca dokladnie 20 pytan w `questionPool`.
14. Frozen demo zwraca `correct_answer` tylko w kontekscie `publicDemo.enabled = true`.
15. Frozen demo nie wymaga `POST /testy-na-prawo-jazdy/demo/answers` do pokazania wyniku lokalnego.
16. Aktywny egzamin nie zwraca `correct_answer` w `currentQuestion`.
17. Aktywny egzamin nie zwraca `correct_answer` w `questionPool`.
18. Aktywny trener pamieci nie zwraca `correct_answer` w `currentQuestion` przed odpowiedzia.
19. Aktywny trener pamieci nie zwraca `correct_answer` w `questionPool` przed odpowiedzia.
20. Klasyczna nauka zalogowanego usera nadal zapisuje odpowiedz przez `StudySessionAnswerController`.

### Browser QA

1. Desktop: landing -> CTA -> demo -> 20 pytan -> result.
2. Mobile: landing -> CTA -> demo -> result.
3. Wideo: play, odtworz caly film, koncowka filmu.
4. Obraz: skala i czytelnosc.
5. Adnotacje: widoczne po odpowiedzi lub zgodnie z trybem demo.
6. Bledna odpowiedz: wyjasnienie widoczne i czytelne.
7. Poprawna odpowiedz: szybki feedback i przejscie dalej.
8. Restart demo dziala.
9. CTA po wyniku prowadzi poprawnie.
10. Klik TAK/NIE w demo nigdy nie zostawia niebieskiego stanu po wyborze.
11. Szybkie klikniecie CTA po wyniku nie cofa do `/demo`.
12. Odwiezenie `/demo` po zakonczeniu nie startuje przypadkowo nowego demo, chyba ze uzywamy `fresh=1`.

### Regression QA

1. `/nauka` startuje normalna sesje.
2. `/nauka/teraz` nadal zapisuje normalne odpowiedzi.
3. Trener pamieci nie dostaje odpowiedzi demo.
4. Blokada kategorii nadal dziala.
5. Publiczna baza pytan nadal dziala.
6. Egzamin nie ujawnia poprawnych odpowiedzi przed zakonczeniem.
7. Trener pamieci nadal ma niezalezny answer flow i nie uzywa lokalnego demo engine.
8. PJM nie zaczyna pokazywac demo payloadu ani `sign_language_assets` w publicznym demo.

### Kontrola network/performance

1. Wejscie na `/testy-na-prawo-jazdy/demo` wykonuje jeden glowny request dokumentu/Inertia.
2. Klikniecie odpowiedzi nie wykonuje requestu `answers`.
3. Po 20 pytaniach nie ma serii 20 POST-ow.
4. Media nadal laduja sie lazy i nie pobieraja wszystkich filmow naraz.
5. Czas reakcji po kliknieciu odpowiedzi jest natychmiastowy z perspektywy UI.

### Kontrola izolacji kodu

1. `PublicDemoQuestionPayloadBuilder` jest jedynym builderem, ktory zwraca pelny reveal przed odpowiedzia publicznego demo.
2. `StudySessionController::shouldRevealQuestionAnswer()` pozostaje bez zmian.
3. `StudySessionAnswerController` pozostaje flow zapisu dla normalnej nauki.
4. `StudySessionManager` nie dostaje zadnej zaleznosci od public demo.
5. Warunek frontendowy jest jawny: `isPublicDemoMode` plus flaga frozen/local, a nie ogolne `isLocalLearningMode`.

## 18. Definition of Done

Demo uznajemy za gotowe, gdy:

- `/testy-na-prawo-jazdy` ma realny landing i CTA,
- `/testy-na-prawo-jazdy/demo` uruchamia 20 pytan,
- demo pokazuje realny player klasycznej nauki,
- odpowiedzi i wyjasnienia dzialaja,
- demo nie zapisuje nic do realnego progresu,
- testy feature i build przechodza,
- manual QA potwierdza desktop/mobile,
- produktowo jasne jest, co uzytkownik dostaje za darmo, a co w pelnym dostepie.

Po wdrozeniu frozen packet dodajemy dodatkowe DoD:

- demo nie wysyla requestu po kazdej odpowiedzi,
- wszystkie odpowiedzi w demo koloruje lokalnie na zielono/czerwono,
- niebieski stan wyboru nie wystepuje jako stan finalny po kliknieciu,
- `correct_answer` w publicznym payloadzie jest ograniczone do `publicDemo.enabled`,
- egzamin in-progress nie ujawnia `correct_answer`,
- trener pamieci in-progress nie ujawnia `correct_answer`,
- normalna nauka nadal zapisuje odpowiedzi do backendu i aktualizuje progres.

## 19. Rekomendacja koncowa

Robimy to, ale ostroznie:

1. Nie jako "darmowy tryb nauki".
2. Nie jako normalna `StudySession`.
3. Tak jako publiczne, izolowane demo playera.
4. Z kuratorowanymi 20 pytaniami.
5. Z bardzo mocnym CTA do pelnej nauki.

To powinno podniesc konwersje, bo uzytkownik zobaczy realna przewage aplikacji przed decyzja o koncie lub platnosci.

## 20. Szczegolowy plan wdrozenia frozen packet

Ten rozdzial opisuje wdrozony kierunek i zostaje jako checklista utrzymaniowa. Nie rozszerzac tego na egzamin, trenera pamieci ani normalna nauke.

### 20.1 Docelowy kontrakt `publicDemo`

Rozszerzyc `publicDemo` props o jawne pola:

```ts
interface PublicDemoContext {
    enabled: boolean;
    mode: 'session_backed' | 'frozen_packet';
    routes: PublicDemoRoutes;
    viewer: {
        authenticated: boolean;
        has_full_access: boolean;
    };
    gate?: PublicDemoGate | null;
}
```

Zasady:

- fallback serwerowy moze zostac jako `session_backed`,
- nowy flow ustawiamy na `frozen_packet`,
- frontend nie moze zgadywac strategii po samej obecnosci `publicDemo`,
- jesli pola nie istnieja, fallback to obecne zachowanie serverowe.

### 20.2 Backend - pakiet pytan

`PublicDemoStudyController::renderPlayer()`:

- pobiera 20 pytan przez `PublicDemoQuestionSetService`,
- mapuje wszystkie pytania przez `PublicDemoQuestionPayloadBuilder::forQuestion($question, includeReveal: true)`,
- przekazuje caly wynik jako `questionPool`,
- ustawia `questionPoolMode = 'full'`,
- ustawia `currentQuestion` na pierwsze pytanie z tego samego pakietu,
- ustawia `publicDemo.mode = 'frozen_packet'`.

Nie zmieniac:

- tras `/nauka`,
- `StudySessionController`,
- `StudySessionAnswerController`,
- `StudySessionManager`,
- `ReviewQueueController`,
- `ApiStudySessionAnswerController`.

### 20.3 Frontend - lokalny answer engine

W `Show.vue` dodac computed:

```ts
const usesFrozenPublicDemo = computed(() =>
    isPublicDemoMode.value
    && props.publicDemo?.mode === 'frozen_packet'
);
```

Nastepnie w `selectAnswer()` albo w osobnej funkcji przed normalnym flow:

```ts
if (usesFrozenPublicDemo.value && activeQuestion.value) {
    persistLocalAnswer(activeQuestion.value, answerKey, ANSWER_KIND_CHOICE);
    return;
}
```

Warunek musi byc bardziej szczegolowy niz `isLocalLearningMode`, bo normalna nauka i trener pamieci nie moga wejsc w demo path przypadkiem.

`buildLocalResult()` juz ma dobra baze:

- bierze `question.correct_answer`,
- liczy `is_correct`,
- buduje `ResultItem`,
- moze pokazac zielony/czerwony stan bez backendu.

Trzeba dopilnowac:

- wszystkie pytania w `questionCache` maja `correct_answer`,
- `cacheQuestion()` nie nadpisuje pelnego pytania ubozszym payloadem bez `correct_answer`,
- `payload.nextQuestion` nie jest wymagane w frozen demo,
- `syncCompletion()` dla frozen demo nie robi twardego redirectu.

### 20.4 Ochrona przed nadpisaniem pelnego pytania ubozszym payloadem

Dodac helper merge, zamiast prostego:

```ts
questionCache.value[question.id] = question;
```

Zasada merge dla public demo:

- jesli stare pytanie ma `correct_answer`, a nowe nie ma, zachowaj stare `correct_answer`,
- jesli stare pytanie ma `explanation`, a nowe nie ma, zachowaj stare `explanation`,
- jesli stare pytanie ma `explanation_asset`, a nowe nie ma, zachowaj stare,
- jesli stare pytanie ma `explanation_annotations`, a nowe ma puste, zachowaj stare.

Ale:

- tego merge nie stosujemy globalnie dla egzaminu,
- najlepiej ograniczyc go do `usesFrozenPublicDemo`,
- normalny payload egzaminu nie moze zostac wzbogacony z cache o poprawna odpowiedz.

### 20.5 Endpointy po refaktorze

Zostawiamy trasy:

- `GET /testy-na-prawo-jazdy`,
- `GET /testy-na-prawo-jazdy/demo`,
- `POST /testy-na-prawo-jazdy/demo/restart`.

Tymczasowo zostawiamy:

- `POST /testy-na-prawo-jazdy/demo/answers`,
- `POST /testy-na-prawo-jazdy/demo/complete`.

Ale w frozen demo:

- `answers` nie jest uzywany do feedbacku,
- `complete` moze byc uzyty tylko do telemetryki albo wcale,
- brak tych endpointow w Network podczas klikania odpowiedzi jest oczekiwany.

### 20.6 SessionStorage

Opcjonalnie dodac:

```text
public-demo-player-demo-2026
```

Payload:

```json
{
  "label": "player-demo-2026",
  "current_index": 4,
  "answers": {
    "123": {
      "selected_answer": "A",
      "is_correct": true,
      "response_time_ms": 2400
    }
  },
  "completed": false,
  "started_at": "ISO-8601"
}
```

Zasady:

- jesli label configu sie zmieni, ignorujemy stary storage,
- `fresh=1` czysci storage,
- restart czysci storage,
- storage nie ma wplywu na normalna nauke.

### 20.7 Testy przed merge

Minimalny zestaw przed merge frozen packet:

```bash
docker compose exec -T app php artisan test tests/Feature/PublicDemoStudyTest.php
docker compose exec -T app php artisan test tests/Feature/StudySessionTest.php
docker compose exec -T app php artisan test tests/Feature/ReviewQueueTest.php
npm run build
```

Jesli nazwy testow beda inne, uzyc najblizszych feature tests dla:

- public demo,
- normalnej sesji nauki,
- egzaminu,
- trenera pamieci.

### 20.8 Manual QA przed merge

1. `/testy-na-prawo-jazdy` pokazuje szybe na playerze.
2. CTA startuje `/testy-na-prawo-jazdy/demo?fresh=1`.
3. Pierwsze pytanie ma zielony/czerwony feedback natychmiast.
4. Pytanie 2, 3, 10 i ostatnie tez maja zielony/czerwony feedback natychmiast.
5. Network nie pokazuje `POST /demo/answers` przy klikaniu odpowiedzi.
6. Wynik pokazuje tylko dwa CTA.
7. Szybkie klikniecie CTA na wyniku nie jest nadpisywane.
8. `/nauka/teraz` dalej zapisuje odpowiedzi.
9. Egzamin nie pokazuje poprawnej odpowiedzi przed koncem.
10. Trener pamieci nadal dziala przez backend.

### 20.9 Decyzja koncowa

Frozen packet to najlepszy kierunek dla demo, bo:

- demo ma byc szybkie i pokazowe,
- nie potrzebuje prawdziwej sesji backendowej,
- nie powinno obciazac bazy ani session po kazdej odpowiedzi,
- daje natychmiastowy feedback,
- izoluje ryzyko od krytycznych trybow nauki.

Nie traktujemy tego jako wzorca dla calego produktu. To jest specjalna optymalizacja publicznej probki produktu.
