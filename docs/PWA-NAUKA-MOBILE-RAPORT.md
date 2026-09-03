# PWA /nauka i aplikacja mobile - raport roboczy

## Status dokumentu

- Status: zrodlo wiedzy i raport roboczy
- Data zalozenia: 2026-07-06
- Zakres: `/nauka`, aktywna sesja nauki, PWA, przygotowanie pod Google Play
- Tryb pracy: analiza kodu i kolejne sprinty implementacyjne PWA/TWA

Ten dokument ma byc miejscem, w ktorym zapisujemy istotne ustalenia dotyczace tego, co juz mamy w aplikacji, czego brakuje i jak profesjonalnie dojsc do PWA oraz pozniejszej aplikacji mobilnej w Google Play.

## Decyzja 2026-07-10 - najpierw mobilny UX

PWA foundation pozostaje w kodzie i jest objete quality gate, ale kolejne prace PWA/TWA sa na razie wstrzymane. Nie rozpoczynamy teraz Lighthouse, telemetry, testow Android, wrappera TWA/AAB ani konfiguracji finalnego certyfikatu Play.

Najblizszy etap to audyt i przebudowa widokow mobilnych. Miernikiem sukcesu jest wyrazny, aplikacyjny flow nauki: szybka orientacja po wejsciu, jedna dominujaca akcja, przewidywalna nawigacja, czytelne stany sesji i malo konkurujacego chrome.

## Aktualizacja 2026-07-10 - mobile home i konfigurator

Pierwsza iteracja glownego widoku `/nauka` jest wdrozona bez zmian backendu:

- aktywna sesja ma wlasny stan z prawdziwym CTA `Wroc do sesji`, numerem pytania i secondary action nowej sesji;
- gdy aktywnej sesji nie ma, home prowadzi do wyboru kolejnej serii, a globalny postep pozostaje nizej jako osobna informacja;
- konfigurator sesji jest globalnym bottom sheetem Vue `Teleport`, wiec dolny dock nie blokuje finalnego CTA;
- konfigurator zamyka sie przed potwierdzeniem zastapienia aktywnej sesji;
- `npm run e2e:smoke` sprawdza teraz klikniecie finalnego CTA sheeta na 360/390/430 px.
- podsumowanie zwyklej sesji na telefonie zaczyna sie od wyniku i nastepnej akcji, a nie od przewijanej mapy wszystkich dzialow; pelny panel roadmapy zostaje desktopowym kontekstem.
- rekordy czasowe dzialow nie sa na telefonie osobna tabela w podsumowaniu. Po akcjach wynikowych widoczna jest lekka sciezka aktualnego i nastepnego dzialu, gdzie czas zostaje przy aktualnym punkcie.
- wybor dzialu ma teraz osobny, pelnoekranowy widok wewnatrz konfiguratora: pionowa sciezka z ikonami, pelnymi nazwami, wyszukiwaniem, grupami podstawowymi/specjalistycznymi i faktycznym postepem przerobienia pytan;
- wybor w sciezce wraca do konfiguratora, nie zmienia backendowego kontraktu sesji i nie otwiera kolejnego modala.

Zakres kolejnych prac UI: recommendation-first pierwszy stan konfiguratora, potem trener i profil. Player pozostaje poza tym zakresem, bo jego obecna logika feedbacku jest zaakceptowana produktowo. Dalsze zadania PWA/TWA pozostaja wstrzymane zgodnie z decyzja powyzej.

## Aktualizacja 2026-07-10 - quality gate PWA i mobile

Mamy automatyczna bramke browserowa `npm run e2e:smoke`, dopasowana do aktualnego `/nauka`. Zamiast starych selektorow dashboardu test loguje sie, uruchamia albo wznawia sesje, zapisuje odpowiedz, wraca do `/nauka` i nastepnie sprawdza runtime PWA.

Potwierdzony zakres:

- manifest ma `display: standalone`, start `/nauka` i ikony,
- service worker jest aktywny i przejmuje strone,
- prywatne trasy, w tym `/nauka` i `/api/*`, nie trafiaja do Cache Storage,
- offline nawigacja do `/nauka` pokazuje `offline.html`,
- mobilny panel i dolna nawigacja przechodza na 360x800, 390x844 i 430x932 bez poziomego overflow,
- lokalny tryb przeciwko Nginxowi dodatkowo sprawdza `no-store` dla service workera.

Przy okazji test ujawnil i poprawil realna usterke: zewnetrzny `-mx-4` w `Session/Index.vue` rozszerzal `/nauka` o 16 px na 360 px. Nie zmienialo to zawartosci panelu, ale umozliwialo poziomy scroll.

Nie zamyka to jeszcze PWA/TWA release: pozostaja Lighthouse, telemetry bledow klienta, budzet wydajnosci, testy Android Back/instalacji oraz realny certyfikat Google Play App Signing dla Digital Asset Links.

## Aktualizacja 2026-07-07 - Sprint 7 PWA/TWA infrastruktura

Stan po sprincie:

- aplikacja ma juz `public/manifest.webmanifest` ze startem na `/nauka`,
- sa ikony PWA `any` i `maskable` w `public/pwa/`,
- jest konserwatywny `public/service-worker.js` online-first z offline fallbackiem,
- service worker jest rejestrowany w entrypointach Inertia i public content,
- `x-site.favicons` dodaje manifest i meta mobile/PWA,
- `/.well-known/assetlinks.json` jest endpointem zasilanym przez `config/pwa.php`,
- `TWA_PACKAGE_NAME` i `TWA_SHA256_CERT_FINGERPRINTS` sa dodane do `.env.example`,
- lokalny i produkcyjny Nginx maja wyjatki cache dla SW/manifest/offline/assetlinks,
- Apache `.htaccess` ma typ manifestu i naglowki cache.

Wczesniejsze sekcje raportu, ktore mowia o braku manifestu, service workera lub assetlinks, nalezy traktowac jako historyczny skan sprzed Sprintu 7. Otwarte przed TWA pozostaje wpisanie realnego SHA-256 z Google Play App Signing, finalne potwierdzenie package name i test instalacji na Androidzie.

## Najkrotszy wniosek

Aplikacja ma bardzo dobry fundament pod PWA, bo core nauki jest juz webowy, responsywny i ma osobny mobilny dashboard `/nauka`. Najrozsadniejsza droga to:

1. dopracowac `/nauka` i `/nauka/teraz` jako online-first PWA,
2. utrzymac instalowalnosc, manifest, service worker i podstawowy cache shell,
3. pozniej rozwaznie dodawac offline-lite dla aktywnej sesji,
4. dopiero po stabilizacji PWA opakowac aplikacje jako TWA pod Google Play.

Nie rekomenduje zaczynac od osobnej natywnej aplikacji ani pisac produktu od nowa. Obecny kod jest naturalnym kandydatem na PWA/TWA: wykorzystujemy istniejacy backend, webowy core `/nauka` i mobilny blueprint, a osobna natywna aplikacja ma sens dopiero jako pozniejsza decyzja biznesowa po stabilizacji PWA/TWA.

## Co juz mamy

### Stack aplikacji

- Laravel 12
- Inertia
- Vue
- TypeScript po stronie frontendu
- sesje webowe i CSRF
- Vite
- osobne widoki dla publicznych stron, panelu, nauki i aktywnych sesji

### Core produktu

Core nauki znajduje sie w dziale `/nauka`. Ten dzial nie jest zwyklym landing page'em ani prostym launcherem. To produktowy hub nauki, ktory laczy:

- dobor kategorii prawa jazdy,
- dobor dzialu/tematu,
- filtry statusu pytan,
- tryby nauki,
- rekomendacje kolejnego kroku,
- postep kursanta,
- bledne pytania,
- powtorki,
- PJM,
- ranking,
- znaki drogowe,
- trener pamieci,
- osobny UX mobilny.

## Mapa kluczowych plikow

### Routing

- [routes/web.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/routes/web.php)
  Glowne trasy webowe dla `/nauka`, startu sesji, aktywnej sesji, odpowiedzi i wynikow.

Najwazniejsze trasy:

- `GET /nauka` - hub nauki
- `POST /study-sessions` - start sesji
- `GET /nauka/teraz` - aktywna sesja
- `POST /nauka/teraz/odpowiedzi` - odpowiedz w aktywnej sesji
- `POST /nauka/teraz/zakoncz` - zakonczenie sesji
- `GET /nauka/wynik/{studySession}` - wynik sesji
- `GET /nauka/teraz/pytania/batch` - batch danych pytan dla aktywnej sesji
- `GET /nauka/wynik/{studySession}/pytania/batch` - batch danych pytan dla historii/wyniku

### Backend `/nauka`

- [app/Http/Controllers/SessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/SessionPageController.php)
  Buduje dane dla hubu `/nauka`.

- [app/Http/Controllers/StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)
  Start, render aktywnej sesji, egzamin, batch danych pytan, wynik.

- [app/Http/Controllers/StudySessionAnswerController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionAnswerController.php)
  Zapis odpowiedzi, reveal poprawnej odpowiedzi i synchronizacja postepu.

- [app/Support/StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)
  Glowna logika sesji: wybor pytan, start, aktywna sesja, zapis odpowiedzi, progres.

- [app/Support/StudyContextService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudyContextService.php)
  Kontekst nauki: dostepne kategorie, preferowana kategoria, blokada kategorii, licznik powtorek.

- [app/Support/StudyTopicGroupsService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudyTopicGroupsService.php)
  Grupy tematow i liczniki postepu po statusach.

- [app/Http/Requests/StudySessionStoreRequest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/StudySessionStoreRequest.php)
  Walidacja startu sesji.

- [app/Http/Middleware/EnsureStudySessionAccess.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Middleware/EnsureStudySessionAccess.php)
  Ochrona tras aktywnej sesji.

### Frontend `/nauka`

- [resources/js/Pages/Session/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue)
  Glowny ekran `/nauka`: desktop, logika formularzy, wybor trybow, rekomendowany krok.

- [resources/js/Pages/Session/Partials/MobileLearningDashboard.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Partials/MobileLearningDashboard.vue)
  Osobny mobilny dashboard `/nauka`.

- [resources/js/Pages/StudySessions/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
  Aktywna sesja nauki, zen, PJM, powtorki i tryby nieegzaminacyjne.

- [resources/js/Pages/StudySessions/Exam.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Exam.vue)
  Aktywna sesja egzaminacyjna.

- [resources/js/Layouts/AuthenticatedLayout.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/AuthenticatedLayout.vue)
  Layout aplikacji z rozpoznawaniem widoku `/nauka`.

## Jak dziala `/nauka`

### Dostep

`/nauka` jest w grupie `auth` i `verified`, ale ma wlasna logike dostepu:

- pelny produkt daje dostep do normalnej nauki,
- darmowy PJM moze dac ograniczony dostep,
- brak dostepu powoduje redirect do aktywacji/zakupu.

Wazne: nie wszystkie podmoduly dzialu nauki maja taki sam poziom dostepu. Czesc tras jest dodatkowo chroniona przez `product.access`.

### Dane budowane przez backend

`SessionPageController` zwraca do Inertia m.in.:

- aktywna kategorie,
- filtry,
- grupy tematow,
- statusy pytan,
- informacje o dostepie,
- licznik powtorek,
- preview rankingu,
- dane modulu PJM,
- CTA zaproszenia znajomego,
- ostatni stan nauki,
- rekomendowane domyslne ustawienia.

To oznacza, ze `/nauka` jest juz mocno personalizowane pod kursanta.

### Stan kursanta

System korzysta z postepu pytan i sesji, zeby wiedziec:

- ile pytan jest poprawnych,
- ile jest blednych,
- ile jest zapamietanych,
- ile nie bylo jeszcze ruszonych,
- ile czeka do powtorki,
- jaka byla ostatnia sesja nauki,
- jaki temat mozna zaproponowac jako nastepny.

## Tryby nauki

### Nauka klasyczna

- `mode=learn`
- `ui_shell=exam_like`
- zwykle dziala na wybranym temacie i statusie pytan
- moze startowac z pytaniami blednymi, nieodpowiedzianymi, wszystkimi itd.

### Zen

- `mode=learn`
- `ui_shell=zen`
- spokojniejszy tryb UI
- domyslnie status `all`

### Egzamin

- `mode=exam`
- `ui_shell=exam`
- 32 pytania
- osobny ekran `StudySessions/Exam.vue`
- synchronizacja stanu egzaminu z backendem

### Bledne pytania globalnie

- startuje nauke z `question_status=incorrect`
- moze dzialac bez konkretnego tematu
- przydatne jako szybka akcja mobilna

### Trener pamieci

- oparty o `sr_review`
- korzysta z planera powtorek
- w `/nauka` pojawia sie przez licznik `due_review_count`
- ma sens jako jeden z glownych filarow PWA, ale wymaga osobnej analizy offline

### PJM

- osobny tryb/modul
- moze miec ograniczony darmowy dostep
- wazny produktowo, bo `/nauka` moze pokazywac specjalny entry tile dla PJM

### Znaki drogowe i ranking

- sa powiazane z hubem `/nauka`, ale maja swoje osobne trasy i logike
- dla PWA trzeba zdecydowac, czy wchodza do pierwszego zakresu, czy jako etap 2

## Jak dziala start sesji

Start sesji idzie przez `POST /study-sessions`.

Backend:

1. waliduje kategorie, tryb, filtr tematu, status pytan i liczbe pytan,
2. sprawdza dostep do kategorii,
3. wybiera pytania,
4. zamyka poprzednie aktywne sesje uzytkownika,
5. tworzy nowa sesje,
6. zapisuje w `payload` liste pytan, indeks, filtry i shell UI,
7. przekierowuje do `/nauka/teraz`.

Wazna konsekwencja PWA: aplikacja zaklada jedna aktywna sesje po stronie serwera. To upraszcza produkt, ale utrudnia pelny offline.

## Aktywna sesja `/nauka/teraz`

Aktywna sesja nie jest tylko frontendowym quizem. Jest mocno powiazana z backendem:

- backend zna aktywna sesje,
- backend zna aktualny indeks,
- backend zapisuje odpowiedzi,
- backend liczy wynik,
- backend aktualizuje postep pytan,
- frontend pobiera batch kolejnych pytan,
- frontend pokazuje reveal odpowiedzi w trybach nauki.

### Prefetch i batch pytan

Dla nauki system ma juz mechanizm pobierania puli lub okna pytan:

- male sesje moga dostac pelna pule,
- wieksze sesje dzialaja okienkowo,
- frontend dopobiera kolejne pytania batchem,
- batch ma limit, aktualnie maksymalnie 20 ID w requestach.

To jest bardzo dobre pod PWA online-first, bo mozna rozszerzac ten mechanizm w kierunku cache lokalnego.

### Zapis odpowiedzi

`Show.vue` ma kolejke synchronizacji odpowiedzi i lokalny stan odpowiedzi, ale nie jest to jeszcze trwaly offline queue.

Obecnie przy problemie synchronizacji:

- frontend moze oznaczyc blad synchronizacji,
- aktywna sesja dalej zalezy od powodzenia zapisu,
- po odswiezeniu/powrocie offline nie ma jeszcze pewnego lokalnego dziennika zdarzen.

Wniosek: fundament istnieje, ale offline wymaga osobnej warstwy persystencji, np. IndexedDB.

## Mobilny `/nauka`

Mobilny widok nie jest tylko responsywnym desktopem. Istnieje osobny komponent:

- `MobileLearningDashboard.vue`

Obecny mobilny dashboard zawiera m.in.:

- hero/continue learning,
- szybkie tryby,
- bottom sheet konfiguracji nauki,
- wybor tematu,
- wybor statusu,
- specjalna obsluge globalnych bledow,
- pasek trenera pamieci,
- blokade scrolla body przy sheet/modal.

To jest wazny atut. Z punktu widzenia PWA oznacza, ze aplikacja juz mysli telefonem, a nie tylko zmniejsza desktop.

## Aktualna gotowosc PWA

### Co sprzyja PWA

- core flow jest webowy,
- UI mobile juz istnieje,
- `/nauka` jest aplikacyjnym hubem,
- `/nauka/teraz` ma batch/prefetch pytan,
- backend ma jasne endpointy sesji,
- start sesji i zapis odpowiedzi sa dobrze wydzielone,
- tryby nauki sa opisane przez parametry `mode` i `ui_shell`,
- aplikacja dziala jako Inertia SPA-like experience.

### Czego brakuje

Na podstawie dotychczasowej analizy nie widac jeszcze kompletnej warstwy PWA:

- brak kanonicznego `manifest.webmanifest`,
- brak service workera aplikacyjnego,
- brak strategii cache assets/shell,
- brak strony offline,
- brak persistent offline answer queue,
- brak IndexedDB/local persistence dla aktywnej sesji,
- brak Digital Asset Links pod TWA,
- brak `.well-known/assetlinks.json`,
- brak jasnej polityki update'ow service workera,
- brak testow instalowalnosci PWA,
- brak decyzji, czy PWA ma obejmowac tylko `/nauka`, czy cala aplikacje.

## Rekomendowany zakres PWA

### Etap 1: online-first PWA

To powinien byc pierwszy profesjonalny etap.

Zakres:

- manifest aplikacji,
- ikony PWA,
- theme color,
- display mode `standalone`,
- start URL, prawdopodobnie `/nauka`,
- service worker cache'ujacy assets i shell,
- fallback offline page,
- poprawne meta tagi w layoutach,
- test instalowalnosci w Chrome/Android,
- zachowanie sesji logowania i CSRF.

Cel: aplikacja ma dac sie zainstalowac, szybko startowac i dzialac stabilnie jako aplikacja webowa na telefonie, ale nie udawac jeszcze pelnego offline.

### Etap 2: offline-lite dla aktywnej sesji

Mozliwy dopiero po dobrym opisaniu kontraktu danych.

Zakres:

- lokalny snapshot aktywnej sesji,
- cache aktualnego pytania i najblizszego batcha,
- trwala kolejka odpowiedzi w IndexedDB,
- ekran statusu synchronizacji,
- retry po odzyskaniu internetu,
- twarde zasady konfliktow przy wielu urzadzeniach.

Cel: kursant moze kontynuowac krotki fragment nauki przy slabszym internecie, a aplikacja dosynchronizuje odpowiedzi.

### Etap 3: pelny offline

Nie rekomendowane jako pierwszy etap.

Pelny offline wymagalby:

- lokalnej bazy pytan,
- lokalnego wyboru pytan,
- lokalnego postepu,
- lokalnego planera powtorek,
- konflikt resolution po synchronizacji,
- ochrony dostepu do platnych tresci offline,
- wersjonowania paczek danych i mediow.

To jest osobny produktowo-techniczny projekt, nie "dodanie service workera".

## Google Play

### Rekomendowana sciezka: PWA -> TWA

Najkrotsza i najbardziej spojna droga:

1. najpierw robimy porzadna PWA,
2. potem opakowujemy ja jako TWA,
3. publikujemy w Google Play.

TWA ma sens, bo:

- zachowujemy jeden frontend,
- nie dublujemy logiki natywnie,
- szybciej dostarczamy aplikacje mobilna,
- backend i web pozostaja jednym produktem,
- aktualizacje UI ida przez web deploy.

### Wymagania pod TWA

Potrzebne beda:

- poprawny PWA manifest,
- HTTPS,
- stabilna domena produkcyjna,
- Digital Asset Links,
- `.well-known/assetlinks.json`,
- pakiet Android z poprawnym `applicationId`,
- ikony i splash zgodne z brandem,
- testy na realnym Androidzie,
- decyzja dotyczaca zakupow cyfrowych i zasad Google Play.

### Ryzyko platnosci

Jesli aplikacja w Google Play sprzedaje cyfrowy dostep do kursu, trzeba osobno sprawdzic aktualne wymagania Google Play Billing. To jest ryzyko produktowo-prawne, nie tylko techniczne.

## Glowna decyzja architektoniczna

### Rekomendacja

Traktowac `/nauka` jako glowny ekran aplikacji mobilnej.

Nie budowac osobnego "mobile home" poza produktem, jesli uzytkownik po instalacji ma przede wszystkim uczyc sie do prawa jazdy.

Praktycznie oznacza to:

- start URL PWA: `/nauka`,
- mobile dashboard jako pierwszy ekran po zalogowaniu,
- aktywna sesja jako fullscreen/app-like flow,
- wynik sesji jako naturalne domkniecie flow,
- trener pamieci i bledne pytania jako szybkie akcje.

## Co trzeba jeszcze zbadac

### Backend

- dokladny kontrakt odpowiedzi `StudySessionAnswerController`,
- co musi byc synchroniczne przy zapisie odpowiedzi,
- czy `StudySessionManager` mozna bezpiecznie rozszerzyc o idempotency key dla offline queue,
- jak dziala `ReviewPlannerService` i czy nadaje sie do offline-lite,
- jak rozliczac dostep premium przy cache'owanych pytaniach,
- czy API `/api/v1` moze byc wykorzystane dla mobile, czy PWA/TWA powinno zostac przy trasach webowych.

### Frontend

- pelny flow w `Show.vue`: start, wybor odpowiedzi, reveal, next, finish,
- miejsca, gdzie UI zaklada stale polaczenie,
- jak dziala recovery po 419/CSRF/session expiry,
- czy aktywna sesja dobrze dziala po odswiezeniu,
- jak zachowuja sie media pytan na slabym laczu,
- czy mobile active session jest wystarczajaco app-like.

### UX

- czy po instalacji aplikacja ma startowac od `/nauka`, dashboardu, czy ostatniej aktywnej sesji,
- jak komunikowac tryb offline/slaby internet,
- czy globalny header zostaje w PWA mobile,
- czy potrzebna jest bottom navigation,
- jakie elementy sa pierwszoplanowe w Google Play screenshots.

### DevOps / wydanie

- domena produkcyjna i HTTPS,
- cache headers dla assetow,
- wersjonowanie buildow,
- strategia update service workera,
- monitoring bledow PWA/mobile,
- testy Lighthouse PWA,
- test na Android Chrome,
- test TWA przed publikacja.

## Drugi pass krytycznych obszarow

Ta sekcja zapisuje obszary, ktore nie sa samym `/nauka`, ale moga byc krytyczne przy profesjonalnym wdrozeniu PWA i pozniejszej aplikacji mobilnej.

### 1. PWA shell, manifest i service worker

Ustalenia:

- aplikacja ma Vite build manifest w `public/build/manifest.json`, ale to nie jest manifest PWA,
- nie znaleziono kanonicznego `manifest.webmanifest`,
- nie znaleziono rejestracji service workera w `resources/js/app.ts`,
- layout Inertia `resources/views/app.blade.php` nie linkuje jeszcze manifestu PWA,
- publiczny layout `resources/views/layouts/public-content.blade.php` rowniez nie linkuje manifestu PWA,
- brak wykrytej obslugi `beforeinstallprompt`,
- brak aplikacyjnego offline fallbacku.

Konsekwencja:

PWA foundation trzeba dodac od zera, ale bez przebudowy core aplikacji. Obecny frontend jest gotowy jako baza, natomiast instalowalnosc, update service workera i strategie cache trzeba zaprojektowac jawnie.

### 2. Auth, sesje i CSRF

Ustalenia:

- aplikacja uzywa webowych sesji i CSRF,
- `resources/js/app.ts` uruchamia `setupCsrfSessionLifecycle()`,
- `resources/js/lib/csrfSession.ts` odswieza token po BFCache/pageshow i po dlugiej nieaktywnosci,
- `resources/js/lib/apiClient.ts` preferuje aktualny cookie `XSRF-TOKEN` nad meta tagiem CSRF,
- write requesty przed wyslaniem moga wykonac refresh CSRF, jesli sesja byla oznaczona jako wymagajaca odswiezenia,
- aplikacja celowo nie powtarza automatycznie write requestu po 419,
- `app/Http/Controllers/Auth/CsrfTokenController.php` zwraca `Cache-Control: no-store, private`,
- `bootstrap/app.php` renderuje JSON dla `CSRF_TOKEN_MISMATCH` i loguje kontekst 419.

Konsekwencja:

To jest dobry fundament pod PWA/TWA online-first. Dla offline-lite nie wolno jednak po prostu dodac agresywnego retry. Zapis odpowiedzi musi dostac osobny, idempotentny kontrakt, bo odpowiedz to operacja domenowa, nie zwykly fetch.

### 3. API i granica mobile

Ustalenia:

- nie ma osobnego `routes/api.php`,
- `/api/v1` jest zdefiniowane w `routes/web.php`,
- prywatne `/api/v1` jest chronione przez `auth` i `verified`, czyli nadal pracuje na webowych cookie/sesji,
- publiczne `/api/v1/health` i `/api/v1/categories` sa dostepne bez logowania,
- w `composer.json` jest `laravel/sanctum`, ale w kodzie nie znaleziono uzycia `auth:sanctum`, Bearer tokenow ani osobnego tokenowego mobile auth,
- `ApiStudySessionController` zwraca sesje i liste pytan jako JSON,
- `ApiStudySessionAnswerController` zapisuje odpowiedzi przez ten sam `StudySessionManager`, ale nie przyjmuje idempotency key.

Konsekwencja:

Dla TWA obecny model jest dobry, bo TWA korzysta z webowego kontekstu przegladarki. Dla pelnej natywnej aplikacji Android/iOS trzeba bedzie zaprojektowac osobny auth/token model albo swiadomie zostac przy webview/TWA.

### 4. Alternatywny kontrakt `/api/v1/sessions`

Ustalenia:

- `POST /api/v1/sessions` tworzy sesje i zwraca pytania w JSON,
- `GET /api/v1/sessions/{studySession}` zwraca szczegoly sesji, progres, aktualne pytanie i pytania,
- API nie ujawnia poprawnych odpowiedzi przed czasem,
- API ma testy w `tests/Feature/ApiSessionTest.php`,
- API jest mniej konfigurowalne niz webowy start `/study-sessions`, bo `ApiStudySessionStoreRequest` ma tylko `category_id`, `mode`, `question_count`,
- API nie obsluguje w tym kontrakcie wszystkich filtrow z `/nauka`, np. `question_topic_id`, `question_status`, `ui_shell`, `randomize_order`.

Konsekwencja:

To API moze byc dobra baza pod przyszly mobile/offline-lite, ale trzeba je zrownac domenowo z webowym flow `/nauka`, inaczej PWA/TWA i potencjalny klient mobile beda mialy rozne mozliwosci.

### 5. Media, storage i cache

Ustalenia:

- media pytan sa budowane przez `QuestionMediaPayloadBuilder` i `MediaUrlResolver`,
- resolver obsluguje publiczne assety, lokalne storage i potencjalnie R2,
- publiczne assety z repo dostaja cache-busting przez `?v=filemtime`,
- konfiguracja mediow dopuszcza obrazy, video MP4, audio pytan i PJM,
- `config/media.php` zaklada `media_local`, `storage-bulk` i docelowe R2,
- `docker/nginx/media.conf` ma 30-dniowy immutable cache i CORS dla serwera mediow,
- produkcyjny przyklad `deploy/mikrus/nginx/prawkobit.conf.example` ma cache 7 dni dla obrazow/fontow/static, ale nie obejmuje jawnie `mp4` ani audio,
- `/storage-bulk` w obecnym etapie jest pomostem przed docelowym R2/CDN.

Konsekwencja:

Cache PWA musi rozrozniac male assety aplikacyjne, obrazy, video, audio i PJM. Nie wolno wrzucic wszystkich mediow pytan w precache. Potrzebna jest strategia limitow, runtime cache i ewentualnie cache tylko aktualnej sesji.

### 6. Zaleznosci zewnetrzne i fonty

Ustalenia:

- `resources/css/app.css` importuje Google Fonts,
- `resources/views/app.blade.php` linkuje Bunny Fonts,
- logowanie Google Identity laduje skrypt z `https://accounts.google.com/gsi/client?hl=pl`.

Konsekwencja:

Offline/PWA nie bedzie w pelni samowystarczalna, dopoki fonty i logowanie zaleza od zewnetrznych zasobow. Dla online-first to akceptowalne. Dla bardziej profesjonalnej PWA warto ujednolicic fonty i rozwazyc self-hosting krytycznych fontow aplikacyjnych.

### 7. Dostep premium i checkout

Ustalenia:

- dostep do produktu rozstrzyga `ProductAccessResolver`,
- zrodla dostepu obejmuja system, purchase, moderator grant i invitation guest,
- zakup tworzy `PurchaseOrder` i `ProductAccessGrant`,
- plany sa jednorazowe/czasowe: `start-30`, `start-90`, `start-365`,
- konfiguracja platnosci ma `default_provider=sandbox`,
- obecny checkout ma sandbox completion, a nie pelna integracje realnego operatora w tym fragmencie kodu,
- aktywny uzytkownik z zakupowym dostepem nie startuje kolejnego checkoutu; aktywny gosc zaproszeniowy moze konwertowac na zakup.

Konsekwencja:

Dla Google Play/TWA trzeba wczesnie podjac decyzje: aplikacja w sklepie tylko konsumuje dostep kupiony przez web, czy sprzedaje dostep cyfrowy w aplikacji. Drugi wariant moze wymusic osobna integracje billingowa i zmiany w checkout/access.

### 8. Prune historii sesji i offline queue

Ustalenia:

- `ops:prune-study-history` usuwa stare zakonczone sesje oraz porzucone `in_progress`,
- schedule produkcyjny uruchamia prune codziennie,
- trener pamieci ma dodatkowe ledger/event tables, ktore sa sprzatane ostrozniej.

Konsekwencja:

Jesli offline-lite pozwoli trzymac odpowiedzi lokalnie, trzeba okreslic deadline synchronizacji. Odpowiedzi wyslane po usunieciu/retire sesji musza dostac czytelny stan konfliktu, a nie milczaco zniknac.

### 9. Monitoring i operacje

Ustalenia:

- istnieje `HealthApiController` i publiczne `/api/v1/health`,
- `HealthCheckService` sprawdza baze i backup,
- `AssignRequestId` dodaje `X-Request-Id` i kontekst logow,
- sa audit logi dla waznych operacji,
- sa smoke/ops komendy i harmonogram backupow, agregacji, snapshotow i prune,
- nie znaleziono monitoringu specyficznego dla PWA: installability, service worker, offline fallback, client-side JS errors/mobile.

Konsekwencja:

Warstwa operacyjna jest dobra jak na webowy MVP. PWA potrzebuje dodatkowego QA/monitoringu na poziomie przegladarki i Androida.

### 10. Testy

Ustalenia:

- sa testy dla `/nauka`, flow sesji, API sesji, CSRF recovery, checkoutu, product access, PJM, trenera pamieci, batchy i prune,
- `tests/Feature/ApiSessionTest.php` pokrywa JSON API sesji,
- `tests/Feature/StudySessionFlowTest.php` pokrywa wiele wariantow aktywnej sesji,
- `tests/Feature/Security/CsrfSessionRecoveryTest.php` pilnuje zachowania 419/CSRF,
- `resources/js/lib/apiClient.test.ts` i `resources/js/lib/csrfSession.test.ts` pilnuja klienta fetch/CSRF,
- nie ma jeszcze testow PWA: manifest, service worker, offline fallback, cache strategie, Android installability.

Konsekwencja:

Pierwszy etap PWA powinien dodac testy/regresje dla instalowalnosci i braku cache'owania prywatnych odpowiedzi HTML/API. Dobre istniejace testy backendowe zmniejszaja ryzyko zmian w sesji nauki.

### 11. Ranking realtime

Ustalenia:

- ranking korzysta z endpointow `/api/v1/ranked/*`,
- `ApiRankedStreamController` wystawia `text/event-stream`,
- stream ma naglowki `Cache-Control: no-cache, no-transform`, `Connection: keep-alive` i `X-Accel-Buffering: no`,
- frontend ma klienta EventSource z `withCredentials: true`,
- istnieje tez klient WebSocket jako alternatywny transport,
- realtime ranking nie jest naturalnym kandydatem do offline.

Konsekwencja:

Service worker powinien omijac streamy SSE/WebSocket i nie probowac ich cache'owac. Ranking w PWA v1 powinien zostac online-only z jasnym stanem rozlaczenia.

### 12. Znaki drogowe jako wzorzec batch sync

Ustalenia:

- `TrafficSignLearningController` ma endpoint `/nauka/znaki-drogowe/odpowiedzi/sync`,
- batch przyjmuje do 50 odpowiedzi,
- `TrafficSignLearningSessionService::syncAnswers()` dziala w transakcji,
- odpowiedzi sa pobierane z `lockForUpdate`,
- ponowny sync tej samej odpowiedzi z tym samym wyborem jest akceptowany,
- ponowny sync z innym wyborem jest odrzucany,
- progres znakow jest aktualizowany batchowo przez upsert.

Konsekwencja:

To jest najlepszy znaleziony wzorzec dla przyszlej offline-lite kolejki. Glowne pytania egzaminacyjne maja inny model (`study_session_answers` unikalne po `study_session_id + question_id`), ale mozna przeniesc idee: batch, blokady, idempotencja, lista `synced_answer_ids`, czytelny konflikt i dopiero potem aktualizacja progresu.

## Trzeci pass: `/nauka/teraz` i kontrakt mobile API

Ta sekcja zapisuje glebszy scan aktywnej sesji nauki oraz porownanie webowego kontraktu `/study-sessions` z JSON API `/api/v1/sessions`.

### 1. Co realnie robi `/nauka/teraz`

Ustalenia:

- `/nauka/teraz` nie jest osobnym klientem API, tylko Inertia widokiem aktywnej sesji,
- jesli uzytkownik nie ma aktywnej sesji, backend odsyla go do `/nauka`,
- aktywna sesja jest wybierana przez `StudySessionManager::activeSessionForUser()`, czyli po stronie serwera istnieje pojecie jednej aktualnej sesji,
- tryb `exam` renderuje `StudySessions/Exam`,
- zakonczony `exam` renderuje `StudySessions/ExamResult`,
- tryby `learn`, `pjm` i `sr_review` renderuja `StudySessions/Show`,
- backend przekazuje do playera m.in. `session`, `questionIds`, `progress`, `examUi`, `currentQuestion`, `questionPool`, `questionPoolMode`, `questionBatchSize`, `prefetchedQuestions`, `sessionFilters`, `topicGroups`, `topicCompletionOverview`, `pjmCompletion`, `reviewCompletion`,
- dla nauki interaktywnej male sesje moga dostac cala pule pytan,
- dla wiekszych sesji player dziala okienkowo,
- staly rozmiar okna/prefetchu w webowym flow to `12`,
- batch endpoint przyjmuje maksymalnie `20` ID pytan na request,
- trener pamieci (`sr_review`) celowo nie dostaje prefetchu kolejnych pytan na pierwszym renderze,
- pytania w `sr_review` ukrywaja poprawna odpowiedz, wyjasnienie i assety do momentu odpowiedzi,
- tryb `learn` moze dostac audio pytania,
- tryb `pjm` moze dostac assety jezyka migowego,
- tryb `exam` ma osobny endpoint synchronizacji stanu egzaminu i timerow.

Konsekwencja:

`/nauka/teraz` jest online-first playerem sterowanym przez serwer, ale z lokalnym cache pytan w pamieci frontendu. To jest dobry fundament pod PWA, natomiast mobile API musi umiec opisac te same stany: aktywna sesja, okno pytan, reveal po odpowiedzi, progres, zakonczona sesja, tryb egzaminu i tryb trenera pamieci.

### 2. Wnetrze frontendu `StudySessions/Show.vue`

Ustalenia:

- player trzyma lokalny `questionCache`, zasilany przez `currentQuestion`, `questionPool` i `prefetchedQuestions`,
- `preparedNextQuestion` jest uzywany do natychmiastowego przejscia do kolejnego pytania,
- `fetchQuestionBatch()` pobiera kolejne pytania przez `GET /nauka/teraz/pytania?ids[]=...`,
- frontend pilnuje, zeby nie pobierac ponownie pytan, ktore juz sa w cache albo sa aktualnie pobierane,
- `sessionAnswersRoute`, `sessionCompleteRoute` i `sessionQuestionBatchRoute` wybieraja trasy aktualnej sesji albo historycznej sesji/wyniku,
- odpowiedz w trybach lokalnej nauki najpierw trafia do `localResults`,
- UI optymistycznie aktualizuje progres i wynik,
- zapis odpowiedzi idzie przez sekwencyjna `answerSyncQueue`,
- po odpowiedzi serwer moze zwrocic reveal: poprawna odpowiedz, tekst odpowiedzi, wyjasnienie, asset wyjasnienia i adnotacje,
- po odpowiedzi serwer moze zwrocic `topicCompletionOverview`, `pjmCompletion`, `reviewCompletion`,
- dla `sr_review` serwer moze zwrocic `nextQuestion` i `nextQuestionNumber`, bo trener pamieci uzywa nastepnego nieodpowiedzianego pytania,
- przy bledzie zapisu frontend wraca do pytania, ustawia `answerSyncFailedQuestionId`, kasuje przygotowane kolejne pytanie i pokazuje blad synchronizacji,
- `finishSession()` dla lokalnej nauki czeka na pending sync requests przez `syncCompletion()`,
- preferencje sesji sa w `localStorage`, ale odpowiedzi i kolejka sync nie sa trwale zapisywane,
- `useSessionExpiry()` traktuje `401` i `419` jako wygasniecie sesji i blokuje dalsze akcje.

Konsekwencja:

Frontend ma juz prawie wszystkie klocki do offline-lite, ale sa one nietrwale. Po odswiezeniu, zamknieciu aplikacji albo ubiciu procesu Androida znika pamiec `answerSyncQueue`, `pendingSyncRequests`, `questionCache` i lokalne odpowiedzi. Profesjonalny offline-lite wymaga przeniesienia tych elementow do jawnego snapshotu sesji i kolejki zdarzen, np. IndexedDB.

### 3. Semantyka backendowego zapisu odpowiedzi

Ustalenia:

- `StudySessionManager::recordAnswer()` dziala w transakcji,
- backend sprawdza, czy pytanie nalezy do sesji,
- jesli odpowiedz na pytanie juz istnieje, backend zwraca istniejaca odpowiedz zamiast tworzyc druga,
- dla zwyklej kolejnosci pytan backend wymaga odpowiedzi na aktualne pytanie,
- wyjatkiem sa tryby z elastyczna kolejnoscia, np. trener pamieci,
- egzamin wymaga fazy `answer`; nie mozna odpowiedziec w fazie preview/media,
- `answer_kind=unknown` jest dozwolone tylko w `sr_review`,
- po odpowiedzi backend aktualizuje `current_index`, licznik odpowiedzi, wynik, status sesji i `completed_at`,
- po odpowiedzi aktualizowany jest progres pytania i progres trenera pamieci,
- zakonczona sesja moze zapisac snapshot `topicCompletionOverview` albo `reviewCompletion`.

Konsekwencja:

Backend ma naturalna czesciowa idempotencje przez unikalna odpowiedz na pytanie w sesji. To pomaga, ale nie wystarcza dla offline queue, bo klient mobilny potrzebuje wlasnego `client_answer_id` albo `Idempotency-Key`, czytelnej odpowiedzi `accepted/already_synced/conflict` i zasad konfliktu, gdy lokalna odpowiedz rozni sie od tej juz zapisanej na serwerze.

### 4. Porownanie webowego kontraktu i `/api/v1/sessions`

| Obszar | Web `/study-sessions` i `/nauka/teraz` | API `/api/v1/sessions` | Co ujednolicic |
| --- | --- | --- | --- |
| Auth | `auth`, `verified`, web session, CSRF | tez w `routes/web.php`, `auth`, `verified`, `product.access` | nazwac to swiadomie jako browser/TWA API albo zaprojektowac osobny token auth dla native |
| Start sesji | `POST /study-sessions` | `POST /api/v1/sessions` | jeden kanoniczny kontrakt startu |
| ID kategorii | `license_category_id` | `category_id` | jedna nazwa pola albo jawne aliasy |
| Tryby | `exam`, `learn`, `review`, `sr_review`, `hard`, `quick`, `pjm` | `exam`, `learn`, `review`, `sr_review`, `hard`, `quick` | zdecydowac, czy `pjm` wchodzi do API mobile |
| Filtry nauki | `ui_shell`, `question_scope`, `question_topic_id`, `question_status`, `randomize_order`, `question_count` | tylko `category_id`, `mode`, `question_count` | API musi przyjac filtry z `/nauka`, inaczej mobile nie odtworzy UX |
| Response po starcie | redirect do `/nauka/teraz`; dla switch/follow-up maly JSON `session + redirect` | JSON `session + category + questions` | zdecydowac, czy mobile start ma zwracac pelny snapshot, czy tylko session + first window |
| Aktualna sesja | `GET /nauka/teraz` bierze aktywna sesje uzytkownika | brak odpowiednika `current`; jest tylko `GET /sessions/{id}` | dodac `GET /api/v1/sessions/current` albo jasno wymagac session id |
| Batch pytan | `GET /nauka/teraz/pytania?ids[]=...`, max 20 | brak batch endpointu dla sesji API | dodac batch/window endpoint, zeby nie wysylac calej sesji zawsze |
| Pojedyncze pytanie | `GET /nauka/teraz/pytania/{question}` | brak osobnego endpointu pytania w sesji API | dodac lub zastapic window endpointem |
| Payload pytania | `prompt`, `options[]`, `structure_scope`, `source`, `correct_answer`, `audio`, `sign_language_assets`, `explanation_asset`, `explanation_annotations` | `question_text`, `answers{A,B,C}`, bez pelnej parytetowej struktury z web playera | wybrac jeden DTO pytania dla web/mobile |
| Zapis odpowiedzi | `selected_answer`, `answer_kind`, `response_time_ms` | `user_answer`, `answer_kind`, `response_time_ms` | jedna nazwa pola; najlepiej przyjac `selected_answer` i traktowac `user_answer` jako legacy alias |
| Response odpowiedzi | `answer`, `session`, `progress`, `completed`, reveal, completion summaries | `data.accepted`, `question_id`, `is_correct`, `answered_questions`, `session_status`; reveal glownie dla `sr_review` | API mobile musi zwracac reveal i progres tak samo jak web |
| Trener pamieci | web zwraca `nextQuestion`, `nextQuestionNumber`, `reviewCompletion` | API tez ma czesc tych danych dla `sr_review` | ujednolicic nazwy i ksztalt danych |
| PJM | web ma `pjmCompletion`, assety PJM i osobne dostepy | API sesji nie dopuszcza `pjm` | zdecydowac zakres PWA/TWA dla PJM |
| Egzamin | web ma `POST /nauka/teraz/egzamin/stan` | API nie ma odpowiednika exam state | dodac API exam state albo pozostawic egzamin jako web-only |
| Zakonczenie | web zwraca `redirect`, `topicCompletionOverview`, `reviewCompletion` | API zwraca pelne `sessionDetails` | potrzebny jeden sposob domkniecia i wynikow |
| Switch/follow-up | web uzywa `X-Study-Session-Switch` | API nie ma odpowiednika | zaprojektowac jawny `source`/`intent` w body |
| Idempotencja | brak jawnego `client_answer_id`, ale duplicate answer wraca jako istniejaca odpowiedz | API ma `accepted`, ale brak idempotency key | dodac jawna idempotencje pod offline queue |

Konsekwencja:

Obecnie webowy kontrakt i API v1 sa domenowo blisko, bo oba korzystaja z `StudySessionManager`, ale klientowo sa niespojne. Dla PWA/TWA nie trzeba od razu przepisywac playera na `/api/v1`, ale przed aplikacja mobile trzeba ustalic jeden kontrakt kanoniczny. Inaczej bedziemy utrzymywac dwa rozne produkty: webowa nauke i uproszczone API sesji.

### 5. Rekomendowany kierunek kontraktu mobile

Rekomendacja:

- potraktowac obecny webowy model `/nauka/teraz` jako zrodlo prawdy funkcjonalnej,
- zbudowac albo rozszerzyc API v1 tak, zeby zwracalo te same stany domenowe,
- nie kopiowac 1:1 nazw z obecnego API, jesli sa slabsze niz webowy kontrakt playera,
- zachowac kompatybilnosc starych pol API tylko jako aliasy, jezeli sa juz uzywane.

Minimalny kontrakt mobile/PWA v1 powinien miec:

- `POST /api/v1/sessions` z pelnymi filtrami z `/nauka`,
- `GET /api/v1/sessions/current`,
- `GET /api/v1/sessions/{id}`,
- `GET /api/v1/sessions/{id}/questions?ids[]=...`,
- `POST /api/v1/sessions/{id}/answers` z `client_answer_id`,
- `POST /api/v1/sessions/{id}/complete`,
- `POST /api/v1/sessions/{id}/exam/state` albo jawna decyzje, ze egzamin zostaje w webowym kontrakcie na etap 1.

Minimalny DTO sesji powinien zawierac:

- `session`,
- `question_ids`,
- `progress`,
- `current_question`,
- `current_question_number`,
- `question_pool_mode`,
- `question_batch_size`,
- `prefetched_questions`,
- `session_filters`,
- `topic_completion_overview`,
- `pjm_completion`,
- `review_completion`,
- `capabilities`, np. `can_answer_offline`, `can_reveal_answer`, `can_switch_topic`, `requires_online`.

Minimalny DTO odpowiedzi powinien zawierac:

- `accepted`,
- `dedupe_status`: `created`, `already_synced`, `conflict`,
- `client_answer_id`,
- `answer`,
- `session`,
- `progress`,
- `completed`,
- `next_question`,
- `next_question_number`,
- `topic_completion_overview`,
- `pjm_completion`,
- `review_completion`,
- `sync_policy`.

### 6. Co to znaczy dla PWA/TWA

Wniosek:

- PWA online-first moze na start jechac na obecnym webowym kontrakcie,
- TWA moze rowniez korzystac z webowego kontraktu, bo dziala w przegladarkowym kontekscie cookie/CSRF,
- czysty kontrakt mobile API jest potrzebny zanim zaczniemy offline-lite albo prawdziwa natywna aplikacje,
- nie nalezy obiecywac offline odpowiedzi bez `client_answer_id`, trwalej kolejki i jawnych konfliktow,
- najpierw trzeba ujednolicic dane sesji, potem dopiero robic IndexedDB/offline queue.

## Czwarty pass: `/testy-na-prawo-jazdy` to demo, nie core nauki

Ta sekcja zapisuje wazne rozroznienie produktowo-backendowe: publiczna zakladka `/testy-na-prawo-jazdy` powstala po to, zeby pokazac jak dzialaja testy i player. To nie jest ta sama liga co zalogowana sekcja `/nauka`, w ktorej kursant realnie sie uczy, buduje progres, robi powtorki i pracuje na pelnym kontrakcie sesji.

### 1. Rola `/testy-na-prawo-jazdy`

Ustalenia:

- `/testy-na-prawo-jazdy` jest publicznym landingiem/locked preview demo,
- `/testy-na-prawo-jazdy/demo` jest publicznym demo playera bez logowania,
- oba widoki renderuja ten sam komponent `StudySessions/Show.vue`, ale z propem `publicDemo`,
- demo dziala w trybie `publicDemo.mode = frozen_packet`,
- landing ma glass overlay/gate i CTA do startu demo albo cennika,
- public demo pokazuje wartosc playera: media, pytania, odpowiedzi, szybki feedback, wyjasnienia, adnotacje,
- demo nie jest pelnoprawnym trybem nauki, egzaminu ani trenera pamieci.

Konsekwencja:

W analizie backendu PWA/mobile nie wolno traktowac `/testy-na-prawo-jazdy` jako kanonicznego kontraktu nauki. To jest mechanizm pokazowy, a nie mechanizm progresu kursanta.

### 2. Backend publicznego demo

Ustalenia:

- trasy demo sa publiczne i znajduja sie poza grupa `auth`,
- glowne trasy to:
  - `GET /testy-na-prawo-jazdy`,
  - `GET /testy-na-prawo-jazdy/demo`,
  - `POST /testy-na-prawo-jazdy/demo/answers`,
  - `POST /testy-na-prawo-jazdy/demo/complete`,
  - `POST /testy-na-prawo-jazdy/demo/restart`,
- `PublicDemoStudyController` renderuje `StudySessions/Show`,
- `PublicDemoSessionService` trzyma stan demo w Laravel session pod kluczem `public_demo.player_demo`,
- `PublicDemoQuestionSetService` wybiera stabilny zestaw pytan,
- `config/public_demo.php` ustawia kategorie `B`, limit `20` i kuratorowana liste `external_id`,
- `PublicDemoQuestionPayloadBuilder` buduje payload zgodny z playerem, ale z pelnym revelem, jesli demo tego potrzebuje,
- public demo ma throttling: odpowiedzi `60/min`, complete/restart `20/min`,
- `session.id` w payloadzie demo to `0`, czyli nie jest to realne `study_sessions.id`.

Konsekwencja:

Demo ma celowo kompatybilny ksztalt UI payloadu, zeby mozna bylo uzyc tego samego playera Vue, ale backendowo nie korzysta z `StudySessionManager` jako glownego modelu sesji kursanta. To jest adapter pokazowy, nie zrodlo prawdy dla mobile API.

### 3. Granica bezpieczenstwa demo

Ustalenia z testow:

- landing `/testy-na-prawo-jazdy` nie zapisuje stanu demo w sesji,
- guest moze otworzyc `/testy-na-prawo-jazdy/demo` bez logowania,
- demo nie tworzy wpisow w `study_sessions`,
- demo nie tworzy wpisow w `study_session_answers`,
- demo nie zapisuje `user_question_progress`,
- demo nie zapisuje `review_memory_progress`,
- odpowiedzi demo moga byc liczone lokalnie w zamrozonym pakiecie,
- zakonczenie demo moze wyslac jeden zbiorczy sync `answers` do endpointu `complete`,
- stale answers sa odrzucane statusem `409`,
- restart demo resetuje tylko publiczny stan demo.

Konsekwencja:

Ten mechanizm jest bardzo dobry do marketingowego "poczuj player", ale nie wolno go przenosic jako wzorca do `/nauka/teraz`, gdzie odpowiedzi sa domenowymi zdarzeniami progresu.

### 4. Co to zmienia w kontrakcie PWA/mobile

Zasady od teraz:

- core PWA ma byc projektowany wokol `/nauka`, `/nauka/teraz`, `StudySessionManager`, `StudySessionController` i `StudySessionAnswerController`,
- `/testy-na-prawo-jazdy` traktujemy jako publiczny demo shell i ewentualny onboarding do PWA, nie jako mobile API,
- public demo moze byc cache'owane agresywniej niz prywatna nauka, bo nie zawiera prywatnego progresu,
- prywatnej nauki nie wolno cache'owac wedlug logiki demo frozen packet bez jawnej decyzji offline-lite,
- public demo moze inspirowac UX pierwszego kontaktu, ale nie model sesji,
- przy zmianach w `StudySessions/Show.vue` trzeba testowac osobno: public demo, klasyczna nauka, Zen, PJM, trener pamieci, wynik sesji i egzamin.

Konsekwencja:

Dla Google Play/TWA public demo moze zostac widoczne jako marketingowa zakladka albo pierwszy kontakt niezalogowanego uzytkownika. Jednak po instalacji aplikacji i zalogowaniu glownym ekranem powinno pozostac `/nauka`, bo to jest prawdziwy produkt edukacyjny.

## Piaty pass: PWA runtime, sesje, cache i media

Cel tego passu:

Sprawdzic, czy aplikacja ma juz techniczna warstwe PWA oraz jakie sa realne granice cache/offline dla `/nauka` i `/nauka/teraz`.

### 1. Aktualny stan PWA runtime

Ustalenia:

- w kodzie zrodlowym nie ma rejestracji `serviceWorker`,
- nie znaleziono `manifest.webmanifest`,
- nie znaleziono obslugi `beforeinstallprompt`,
- nie znaleziono globalnej obslugi `navigator.onLine`, `online` ani `offline` dla aplikacji nauki,
- nie znaleziono `assetlinks.json` ani konfiguracji Digital Asset Links,
- layout Inertia `resources/views/app.blade.php` ma CSRF meta, favicony, fonty i Vite assets, ale nie ma manifestu PWA,
- layout publiczny `resources/views/layouts/public-content.blade.php` takze nie ma manifestu PWA.

Konsekwencja:

Nie mamy jeszcze warstwy PWA. To jest dobra sytuacja projektowo, bo mozemy ustawic zasady od zera: manifest, service worker, polityke cache, fallback offline, start URL i docelowy TWA bez dziedziczenia przypadkowych decyzji.

### 2. CSRF i powrot z backgroundu

Ustalenia:

- `setupCsrfSessionLifecycle()` jest uruchamiany globalnie w `resources/js/app.ts`,
- frontend ma dedykowany helper `resources/js/lib/csrfSession.ts`,
- endpoint odswiezania tokena to `GET /auth/csrf-token`,
- backendowy `CsrfTokenController` zwraca aktualny token sesji i flage `authenticated`,
- odpowiedz `/auth/csrf-token` ma naglowek `Cache-Control: no-store, private`,
- frontend wymusza `cache: 'no-store'` przy odswiezaniu CSRF,
- po powrocie z BFCache (`pageshow.persisted`) token jest oznaczany jako wymagajacy odswiezenia,
- po ukryciu karty na ponad 15 minut token jest oznaczany jako wymagajacy odswiezenia po powrocie,
- `apiClient` przed mutacjami odswieza CSRF tylko wtedy, gdy jest to wymagane,
- `apiClient` preferuje cookie `XSRF-TOKEN`, a dopiero potem fallback do meta `csrf-token`,
- status `419` albo kod `CSRF_TOKEN_MISMATCH` sa klasyfikowane jako problem sesji/CSRF,
- status `401` i `419` sa traktowane przez UI jako wygasniecie sesji,
- `useSessionExpiry` przechodzi w terminalny stan `sessionExpired`,
- `SessionExpiredNotice.vue` pokazuje uzytkownikowi modal z opcja logowania/odswiezenia,
- testy potwierdzaja brak automatycznego retry po `419`.

Konsekwencja:

Pod PWA/TWA mamy solidna baze dla trybu online-first. Service worker musi jednak bezwzglednie respektowac te zasady:

- nie cache'owac `/auth/csrf-token`,
- nie cache'owac odpowiedzi mutujacych,
- nie replayowac automatycznie requestu po `419`,
- po powrocie aplikacji z backgroundu pozwolic frontendowi odswiezyc CSRF przed zapisem,
- nie chowac `401/419` za ogolnym komunikatem offline.

### 3. Prywatny HTML/Inertia i granica cache

Ustalenia:

- `HandleInertiaRequests` wspoldzieli do frontendu `requestId`, `auth.user`, nawigacje i `studyContext`,
- `studyContext` zawiera aktywne kategorie, kategorie docelowa i kontekst profilu uzytkownika,
- `/nauka` i `/nauka/teraz` sa prywatnymi widokami Inertia,
- widoki nauki sa powiazane z webowa sesja Laravel,
- `config/session.php` uzywa domyslnie drivera `database`,
- lifetime sesji to 120 minut,
- cookie sesji jest `http_only`,
- `same_site` to `lax`,
- `secure` zalezy od konfiguracji srodowiska,
- layout nie ustawia osobnych naglowkow cache dla prywatnych widokow,
- obecnie brak service workera, wiec nie ma jeszcze ryzyka przypadkowego SW cache prywatnego HTML.

Konsekwencja:

W PWA prywatne strony Inertia musza miec polityke `NetworkOnly` albo rownowazna ochrone. Nie powinnismy cache'owac HTML dla:

- `/nauka`,
- `/nauka/teraz`,
- `/study-sessions*`,
- `/session*`,
- prywatnych odpowiedzi Inertia JSON,
- `/api/v1/*` z danymi uzytkownika, dopoki nie zaprojektujemy jawnego offline snapshotu.

Jesli chcemy cache'owac shell aplikacji, to powinny to byc assety Vite i statyczne ikony, a nie gotowy HTML z prywatnym stanem kursanta.

### 4. Dostep produktowy i konflikty, ktore PWA musi rozrozniac

Ustalenia:

- `ProductAccessResolver` moze odrzucic uzytkownika przez:
  - `banned`,
  - `temporary_account_unclaimed`,
  - `password_change_required`,
  - `missing_access`,
- pelny dostep moze pochodzic z:
  - systemowego dostepu,
  - zakupu,
  - nadania moderatora,
  - zaproszenia jako gosc,
- grant musi byc aktywny, nieodwolany i niewygasly,
- `PjmFreeAccessResolver` pozwala na darmowy tor PJM tylko przy spelnieniu warunkow profilu i dostepnosci assetow PJM,
- `EnsureStudySessionAccess` dopuszcza darmowy PJM tylko wtedy, gdy aktywna sesja jest trybem PJM,
- utrata dostepu moze zamknac aktywna sesje trenera pamieci z powodem `access_lost`,
- zmiana zakresu kategorii moze zamknac aktywna sesje SR z powodem `category_scope_changed`,
- API potrafi zwrocic `409` dla sesji SR poza aktualnym zakresem kategorii,
- API dla uzytkownika bez aktywnego dostepu zwraca `403`.

Konsekwencja:

PWA/mobile nie moze miec jednego ogolnego stanu "blad synchronizacji". Musimy rozroznic przynajmniej:

- `401` - brak zalogowanej sesji,
- `419` - CSRF/sesja wygasla,
- `403` - uzytkownik jest zalogowany, ale stracil albo nie ma dostepu,
- `409` - konflikt domenowy aktywnej sesji,
- blad sieci - brak lacznosci albo timeout,
- blad walidacji - request dotarl, ale payload jest niepoprawny.

To bedzie krytyczne przy offline-lite, bo odpowiedz zapisana lokalnie po utracie dostepu albo po zmianie kategorii nie powinna byc cicho synchronizowana jak zwykly retry.

### 5. Static assets i media

Ustalenia:

- lokalny `docker/nginx/app.conf` cache'uje statyczne pliki aplikacji przez `expires 7d`,
- przykladowy produkcyjny Nginx `deploy/mikrus/nginx/prawkobit.conf.example` daje assetom publicznym `Cache-Control: public, max-age=604800, immutable`,
- produkcyjny regex statyczny obejmuje `css`, `js`, obrazy, `svg`, `ico`, `woff`, `woff2`,
- lokalny regex obejmuje dodatkowo `mjs`, `mp4` i `ttf`,
- serwer mediow `docker/nginx/media.conf` ustawia `Cache-Control: public, max-age=2592000, immutable`,
- serwer mediow dopuszcza `GET` i `OPTIONS`,
- serwer mediow dopuszcza naglowek `Range`,
- serwer mediow ustawia `Access-Control-Allow-Origin: *`,
- serwer mediow ustawia `Cross-Origin-Resource-Policy: cross-origin`,
- `config/media.php` przewiduje `public`, `r2` oraz `media_local`,
- PJM sign language i audio pytan domyslnie korzystaja z `media_local`,
- dozwolone media pytan to obrazy `png/jpeg/webp/avif` i wideo `mp4`,
- limit obrazu to 8 MB,
- limit wideo to 25 MB,
- `MediaUrlResolver` wersjonuje checked-in public assets przez `?v=filemtime`,
- `QuestionMediaPayloadBuilder` zwraca dla pytan typ media, URL-e, mime, rozmiar w bajtach, wymiary i czas wideo,
- `QuestionAudioPayloadBuilder` zwraca tylko wygenerowane audio, ktore istnieje w storage, wraz z URL-em, formatem, czasem i transkrypcja.

Konsekwencja:

Media sa dobrym kandydatem do runtime cache, ale tylko w kontrolowanym zakresie. Najbezpieczniejsza strategia:

- precache: Vite app shell, ikony PWA, favicony, ewentualnie statyczne fonty,
- runtime cache: media aktualnej sesji i najblizszych pobranych pytan,
- brak precache calej bazy pytan,
- osobne limity dla obrazow, audio, wideo i PJM,
- czyszczenie cache po zakonczeniu sesji albo po przekroczeniu limitu miejsca,
- unikanie cache prywatnych JSON-ow bez jawnego offline snapshotu.

Do sprawdzenia przed wdrozeniem:

- czy produkcyjny Nginx powinien objac `mp4`, `mjs`, `ttf` i audio,
- czy media z R2/CDN maja stabilne naglowki `Cache-Control`,
- czy Range requests dzialaja poprawnie przez docelowa domene/CDN,
- czy CORS/CORP nie blokuje wideo/audio w TWA.

### 6. Rekomendowana polityka cache dla PWA v1

Propozycja robocza:

| Obszar | Polityka PWA v1 | Dlaczego |
| --- | --- | --- |
| `/nauka` | NetworkOnly | prywatny Inertia HTML ze stanem usera |
| `/nauka/teraz` | NetworkOnly | aktywna sesja i progres kursanta |
| `/study-sessions*` | NetworkOnly | mutacje i prywatny stan nauki |
| `/session*` | NetworkOnly | prywatny dashboard/trasy startu |
| `/api/v1/*` prywatne | NetworkOnly na start | brak jeszcze jawnego mobile/offline kontraktu |
| `/auth/csrf-token` | NetworkOnly + no-store | CSRF/session lifecycle |
| Vite assets `/build/*` | CacheFirst/precache | wersjonowane assety aplikacji |
| ikony/favicons/manifest | CacheFirst/precache | wymagane dla installability |
| media aktualnej sesji | Runtime Cache z limitami | poprawia UX przy slabym internecie |
| publiczne SEO strony | ewentualnie StaleWhileRevalidate | nie sa core aplikacji nauki |
| `/testy-na-prawo-jazdy` | osobna decyzja | demo marketingowe, nie core progresu |

Najwazniejsza decyzja:

PWA v1 powinna byc "installable online-first", a nie "offline learning". Offline-lite mozna zaprojektowac dopiero po ujednoliceniu kontraktu mobile API i snapshotu aktywnej sesji.

### 7. Co to oznacza dla Google Play/TWA

Ustalenia:

- TWA bedzie dziedziczyc webowa sesje, cookies, CSRF i routing,
- PWA musi miec poprawny manifest, ikony, theme color i start URL,
- Google Play/TWA wymaga Digital Asset Links,
- start URL powinien prowadzic do `/nauka`, bo to jest core produktu,
- przy braku sesji aplikacja powinna przejsc do logowania/rejestracji, ale po zalogowaniu wrocic do `/nauka`,
- public demo moze zostac dostepne, ale nie powinno definiowac glownego flow aplikacji.

Konsekwencja:

Pierwsza profesjonalna sciezka to:

1. Online-first PWA dla `/nauka`.
2. Manifest, ikony, theme color, installability.
3. Service worker z konserwatywna polityka cache.
4. Kontrakt mobile API dla aktywnej sesji.
5. Dopiero potem TWA i Google Play.
6. Offline-lite jako osobny projekt, jezeli biznesowo ma sens.

## Szosty pass: start i resume flow PWA/TWA

Cel tego passu:

Sprawdzic, co powinno sie wydarzyc po uruchomieniu aplikacji z ikony, po powrocie z backgroundu, po logowaniu oraz przy aktywnej lub utraconej sesji nauki.

### 1. Kanoniczne punkty wejscia

Ustalenia:

- `/dashboard` jest pod `auth`,
- `/dashboard` nie renderuje dashboardu, tylko dziala jako router po zalogowaniu,
- `/nauka` jest kanonicznym hubem nauki i ma route name `session.index`,
- `/nauka/teraz` jest kanonicznym widokiem aktywnej sesji i ma route name `study-sessions.current`,
- stare `/session` przekierowuje do `/nauka`,
- stare `/session/current` przekierowuje do `/nauka/teraz`,
- stare `/study-sessions/{studySession}` dla sesji `in_progress` przekierowuje do `/nauka/teraz`,
- `/access/activate` jest ekranem aktywacji dostepu,
- `/nauka` jest w grupie `auth + verified`, ale nie w middleware `product.access`, bo musi obslugiwac tez darmowy scenariusz PJM.

Konsekwencja:

Najlepszy kandydat na `start_url` PWA/TWA to `/nauka`, nie `/dashboard` i nie `/nauka/teraz`.

Powod:

- `/nauka` jest realnym ekranem produktu,
- `/dashboard` jest technicznym routerem po auth,
- `/nauka/teraz` zalezy od istnienia aktywnej sesji i bez niej wraca do `/nauka`,
- `/nauka` obsluguje pelny produkt i darmowy PJM,
- layout traktuje `/nauka` jako osobny panel produktowy: pelna szerokosc, mobilny dashboard, ukryty footer.

### 2. Router po logowaniu

Ustalenia z `PostAuthRedirectController`:

- konto tymczasowe bez przejecia idzie do `account.claim.edit`,
- konto wymagajace zmiany hasla idzie do `password.force.edit`,
- konto bez zweryfikowanego e-maila idzie do `verification.notice`,
- pending friend invitation idzie do `friend-invitations.pending.show`,
- moderator idzie do panelu moderatora,
- uzytkownik z pelnym dostepem idzie do `/nauka`,
- uzytkownik z darmowym PJM idzie do `/nauka`,
- uzytkownik bez dostepu idzie do `/access/activate`.

Ustalenia z auth:

- login po sukcesie robi `redirect()->intended(route('dashboard'))`,
- klasyczne social login po sukcesie idzie do `/dashboard`,
- Google Identity po sukcesie zwraca JSON z `redirect: /dashboard`,
- nowe konto Google Identity dostaje `registration_required` i redirect do `/register?google_identity=1`,
- klasyczna rejestracja po utworzeniu konta idzie do weryfikacji e-maila,
- rejestracja Google Identity po wyborze kategorii idzie do `/dashboard`.

Konsekwencja:

`/dashboard` powinien zostac oficjalnym routerem po auth rowniez dla PWA/TWA. Nie warto go omijac w aplikacji mobilnej, bo ma w jednym miejscu cala logike pierwszego wejscia po zalogowaniu.

### 3. Flow niezalogowanego uzytkownika

Ustalenia:

- `/nauka` wymaga `auth + verified`,
- guest na chronionej trasie trafia do loginu przez standardowy mechanizm Laravel,
- `Auth/Login.vue` renderuje pelnoekranowy backdrop i drawer logowania/rejestracji,
- zamkniecie panelu loginu robi `window.history.back()`, a jesli nie ma historii, `router.visit('/')`,
- `LoginDrawer` przed POST `/login` odswieza CSRF przez `refreshCsrfSession()`,
- `RegisterDrawer` przed POST `/register` tez odswieza CSRF,
- `SessionExpiredNotice` prowadzi do `/login?session_expired=1`,
- `AuthenticatedSessionController::create` pokazuje status "Twoja sesja wygasla..." dla `session_expired=1`.

Konsekwencja:

Flow niezalogowanego dla PWA v1 moze zostac webowy:

1. Uzytkownik otwiera aplikacje z ikony.
2. Start URL `/nauka`.
3. Brak sesji przekierowuje do `/login`.
4. Po loginie backend wraca przez `intended` albo `/dashboard`.
5. `/dashboard` kieruje do `/nauka`, aktywacji, weryfikacji lub innego wymaganego kroku.

Do przetestowania w TWA:

- bezposrednie wejscie z ikony na `/nauka` bez historii przegladarki,
- przycisk zamkniecia login drawer w trybie standalone,
- zachowanie Android back na `/login`,
- powrot po Google Identity i klasycznym OAuth.

### 4. Aktywna sesja i resume `/nauka/teraz`

Ustalenia:

- `StudySessionController::current` pobiera `StudySessionManager::activeSessionForUser($user)`,
- jesli aktywnej sesji nie ma, `/nauka/teraz` przekierowuje do `/nauka`,
- jesli aktywna sesja istnieje, renderowany jest `StudySessions/Show` albo `StudySessions/Exam`,
- `StudySessionManager::activeSessionForUser` najpierw zamyka treningi pamieci poza aktualnym zakresem kategorii,
- aktywna sesja jest wybierana jako najnowsza `status = in_progress`,
- wynikowa trasa `/nauka/wynik/{studySession}` dla sesji `in_progress` przekierowuje do `/nauka/teraz`,
- mobilny dashboard `/nauka` ma tekst "Kontynuuj nauke", ale oznacza kontynuacje wybranego dzialu/sciezki, niekoniecznie aktywna rekordowa `study_session`.

Konsekwencja:

PWA powinna startowac na `/nauka`, a nie automatycznie na `/nauka/teraz`. Automatyczny powrot do playera bylby ryzykowny, bo:

- aktywna sesja moze byc egzaminem z biegnacym czasem,
- aktywna sesja moze byc poza zakresem po zmianie kategorii,
- aktywna sesja moze wymagac pelnego dostepu, ktory wygasl,
- uzytkownik moze chciec zaczac nowy dzial, ale start nowej sesji zamyka poprzednia.

Potrzebny brakujacy element:

`/nauka` powinno dostawac z backendu male `active_session` summary albo mobile API powinno miec `GET /api/v1/sessions/current`.

Minimalny ksztalt:

```json
{
  "active_session": {
    "id": 123,
    "mode": "learn",
    "status": "in_progress",
    "category_id": 1,
    "category_code": "B",
    "ui_shell": "zen",
    "answered": 8,
    "total": 20,
    "started_at": "2026-07-06T10:30:00+00:00",
    "current_url": "/nauka/teraz"
  }
}
```

### 5. Start nowej sesji zamyka poprzednia

Ustalenia:

- `StudySessionManager::start` zamyka wszystkie `in_progress` sesje uzytkownika przed utworzeniem nowej,
- wyjatek dotyczy trenera pamieci `sr_review` z tym samym planem dziennym: wtedy backend moze zwrocic istniejaca sesje,
- przy zamianie sesji SR logger zapisuje `review.replaced`,
- `StudySessionController::store` dla zwyklego startu przekierowuje do `/nauka/teraz`,
- dla przejsc typu topic/follow-up endpoint moze zwrocic JSON z `redirect: /nauka/teraz`.

Konsekwencja:

W PWA/TWA przy aktywnej sesji nie powinnismy ukrywac faktu, ze start nowej sesji zamknie poprzednia. Potrzebujemy jednej z decyzji UX:

- zawsze pokazujemy kafel "Wroc do aktywnej sesji" i osobny przycisk "Zacznij nowa",
- albo start nowej sesji pokazuje potwierdzenie, jesli istnieje aktywna sesja,
- albo backend dostaje jawny parametr intencji typu `replace_active_session=true`.

Dla mobile API to oznacza, ze `POST /api/v1/sessions` powinien jasno dokumentowac semantyke zamykania poprzedniej sesji.

### 6. Egzamin po powrocie z backgroundu

Ustalenia:

- egzamin ma `exam_started_at` i `exam_deadline_at` w payloadzie sesji,
- `renderSessionPage` przy aktywnym egzaminie wywoluje `syncExamState`,
- `currentExamState` tez synchronizuje stan egzaminu,
- po przekroczeniu czasu etapu backend tworzy odpowiedz `TIMEOUT`,
- po przekroczeniu limitu calego egzaminu backend moze zakonczyc sesje,
- frontend `Exam.vue` po syncu z `completed=true` przekierowuje na wynik,
- po `401/419` `Exam.vue` zatrzymuje countdown i pokazuje stan wygaslej sesji.

Konsekwencja:

Powrot z backgroundu w trakcie egzaminu musi byc traktowany jako normalny scenariusz produktowy. PWA nie moze udawac, ze timer lokalny nadal jest prawda. Prawda jest na backendzie.

Do test planu Android trzeba dodac:

- start egzaminu, zablokowanie telefonu, powrot po 30 sekundach,
- start egzaminu, powrot po kilku minutach,
- powrot po przekroczeniu deadline calego egzaminu,
- powrot przy wygaslej sesji Laravel,
- powrot przy utracie dostepu.

### 7. Access states i mozliwe rozgalezienia

Ustalenia:

- `AccessActivationController` odsyla uzytkownika z aktywnym pelnym dostepem z powrotem do `/nauka`,
- `EnsureProductAccess` dla braku dostepu kieruje do `/access/activate`,
- `EnsureStudySessionAccess` przy braku pelnego dostepu dopuszcza tylko aktywna sesje PJM, jesli darmowy PJM jest dozwolony,
- `EnsurePjmModuleAccess` przy braku PJM kieruje do `/dashboard`,
- zbanowany uzytkownik jest globalnie wylogowywany i kierowany do `/login`,
- dla API zbanowany uzytkownik dostaje `403` z komunikatem blokady,
- `TrackUserIpActivity` rozpoznaje source `nauka` dla tras `study-sessions.*` i `session.*`.

Konsekwencja:

Mobile UI musi rozroznic:

- guest -> login,
- session expired -> login z komunikatem,
- banned -> login z komunikatem blokady,
- unverified email -> ekran weryfikacji,
- temporary account -> przejecie konta,
- forced password change -> zmiana hasla,
- missing access -> aktywacja,
- free PJM -> `/nauka` i `/nauka/pjm`,
- full product -> pelny `/nauka`.

To nie powinno byc robione po stronie klienta przez zgadywanie. Najlepiej, zeby backendowy router `/dashboard` pozostal centralnym miejscem decyzji webowej, a mobile API dostalo analogiczny endpoint "learning home/bootstrap".

### 8. Luka w API pod resume mobile

Ustalenia:

- `/api/v1/me/dashboard` jest pod middleware `product.access`,
- przez to nie odzwierciedla webowego `/nauka`, ktore obsluguje tez darmowy PJM,
- `/api/v1/me/dashboard` zwraca metryki, kategorie i ostatnia aktywnosc, ale nie zwraca aktywnej sesji,
- `/api/v1/sessions` ma operacje po konkretnym `studySession`,
- nie ma `GET /api/v1/sessions/current`,
- nie ma JSON-owego odpowiednika webowego `PostAuthRedirectController`,
- nie ma JSON-owego "learning home" z access state, PJM state, active session i recommended next action.

Konsekwencja:

Przed TWA da sie zyc na webowym Inertia flow. Przed prawdziwsza aplikacja mobilna albo offline-lite powinnismy dodac czysty kontrakt startowy.

Proponowany kontrakt:

- `GET /api/v1/mobile/bootstrap` albo `GET /api/v1/me/learning-home`,
- dziala dla zalogowanego i zweryfikowanego uzytkownika,
- nie wymaga pelnego `product.access`, tylko zwraca access decisions,
- zwraca:
  - `auth_state`,
  - `required_action`,
  - `access.full_product`,
  - `access.pjm`,
  - `target_category`,
  - `active_session`,
  - `recommended_next_action`,
  - `routes`.

Przyklad decyzji:

```json
{
  "data": {
    "required_action": "learning_home",
    "routes": {
      "learning_home": "/nauka",
      "current_session": "/nauka/teraz",
      "activate": "/aktywuj-dostep",
      "login": "/login"
    }
  }
}
```

### 9. Decyzje robocze po tym passie

- `start_url` PWA: `/nauka`.
- `/dashboard` zostaje routerem po auth.
- `/nauka/teraz` nie powinno byc domyslnym startem z ikony.
- Hub `/nauka` powinien dostac jawne info o aktywnej sesji.
- API mobile potrzebuje odpowiednika "learning home/bootstrap".
- Start nowej sesji musi jawnie komunikowac zamkniecie poprzedniej aktywnej sesji.
- Egzamin resume musi bazowac na backendowym czasie, nie lokalnym timerze.

## Siodmy pass: istniejacy mobile blueprint w repo

### 1. Czy istnieje osobna aplikacja mobilna?

Na ten moment nie widze w repo osobnego projektu natywnego ani wrappera Android/TWA:

- brak katalogow typu `android`, `ios`, `mobile`, `capacitor`, `expo`, `react-native`, `flutter`, `twa`, `bubblewrap`,
- `package.json` pokazuje webowy stack Vue/Inertia/Vite/Tailwind oraz narzedzia testowe Playwright/Vitest,
- skan plikow nie pokazal `AndroidManifest`, `gradle`, `assetlinks`, `service-worker`, `manifest.webmanifest`, `workbox`.

Wniosek: istniejacy kod mobile to blueprint i czesciowa implementacja mobilnego web/PWA UI, nie osobna aplikacja mobilna. Dla Google Play nadal trzeba bedzie dodac PWA foundation oraz osobny wrapper TWA/Android.

### 2. Gdzie jest blueprint mobile

Glowne zrodla wiedzy i kodu:

- `docs/MOBILE-VIEWS-IMPLEMENTATION-PLAN.md` - ogolny plan doprowadzenia kluczowych ekranow do mobilnego app-feelingu,
- `docs/MOBILE-NAUKA-DASHBOARD-REDESIGN-PLAN.md` - szczegolowy plan mobilnego dashboardu `/nauka`,
- `resources/js/Pages/Session/Partials/MobileLearningDashboard.vue` - realnie podlaczony mobilny dashboard `/nauka`,
- `resources/js/Pages/ReviewQueue/Index.vue` - dzialajacy lokalny mobile shell trenera pamieci,
- `resources/js/Components/MobileAppBar.vue`,
- `resources/js/Components/MobileBottomAction.vue`,
- `resources/js/Components/MobileProgressRing.vue`,
- mobilne assety w `resources/images/session/*` i `resources/images/review/*`.

### 3. Stan `/nauka` mobile

`resources/js/Pages/Session/Index.vue` importuje `MobileLearningDashboard.vue` i przekazuje mu obecne dane oraz eventy startu nauki. To oznacza, ze dashboard mobile `/nauka` nie jest tylko planem - jest juz wpiety w core flow.

`MobileLearningDashboard.vue` robi obecnie:

- render tylko na mobile przez `md:hidden`,
- hero "Kontynuuj nauke" z assetem `mobile-learning-hero-road-car.webp`,
- quick start dla trybow nauki,
- bottom-sheet ustawien startu nauki,
- obsluge klasycznej nauki, Zen, egzaminu, znakow, rankingu, PJM i trenera pamieci,
- uzycie realnych danych dla liczby pytan, bledow, powtorek i rankingu,
- `prefers-reduced-motion` dla animacji.

Wazna uwaga: w komponencie sa tez dane wygladajace na placeholdery lub blueprint:

- `weeklyProgressBars` jest lokalna, statyczna tablica,
- `weeklyProgressLabels` jest lokalna, statyczna tablica,
- sekcja "Ostatnia aktywnosc" ma twardo wpisane `Dzis, 14:32`,
- wynik aktywnosci `85%` jest wpisany na stale,
- czas nauki pokazuje `--`.

To trzeba wyczyscic przed profesjonalna PWA/TWA: albo podpinamy prawdziwe metryki, albo ukrywamy te elementy, zeby aplikacja mobilna nie udawala danych.

### 4. Shared mobile components sa blueprintem, nie standardem produkcyjnym

W repo istnieja komponenty `MobileAppBar`, `MobileBottomAction` i `MobileProgressRing`, ale aktualny search pokazuje, ze nie sa uzywane w `resources/js` poza ich wlasnymi plikami. Dokument `MOBILE-VIEWS-IMPLEMENTATION-PLAN.md` twierdzi, ze zostaly wyciagniete i podpiete do `ReviewQueue/Index.vue`, ale obecny kod trenera pamieci ma lokalny markup `memory-mobile-shell` i lokalne style.

Wniosek: komponenty wspolne sa wartosciowym kierunkiem, ale nie mozna jeszcze traktowac ich jako przyjetego design systemu mobile. Przed PWA/TWA trzeba zdecydowac:

- czy standaryzujemy mobile app shell na tych komponentach,
- czy zostawiamy ekrany z lokalnymi shellami,
- jak wyglada polityka naglowka: globalny `SiteHeader` vs `MobileAppBar`,
- jak wyglada polityka dolnego CTA i safe-area,
- ktore komponenty musza przejsc QA na Androidzie.

Dodatkowe obserwacje:

- `MobileAppBar` uzywa Inertia `Link`,
- `MobileBottomAction` dla `href` renderuje zwykly `<a>`, nie Inertia `Link`,
- `MobileBottomAction` jest samym przyciskiem/linkiem i nie zalatwia wrappera safe-area,
- API propsow w dokumencie nie jest w 100% zgodne z aktualnym komponentem.

### 5. Trener pamieci jako osobny proof of concept

`ReviewQueue/Index.vue` ma juz wyrazny mobilny app shell:

- `min-h-[100svh]`,
- padding oparty o `env(safe-area-inset-top)` i `env(safe-area-inset-bottom)`,
- lokalny mobile top bar,
- lokalny ring progresu,
- mobile asset `mobile-memory-coach.png`,
- wyrazne glowne CTA,
- reduced motion.

To jest dobry wzorzec UX, ale technicznie nie jest jeszcze ujednolicony z `/nauka` ani ze wspolnymi komponentami mobile.

### 6. Co to oznacza dla PWA/TWA

Najwazniejszy wniosek: mobile app juz istnieje koncepcyjnie w web app, ale nie jako osobny projekt. To jest dobra sytuacja dla PWA/TWA, bo pierwsza aplikacja ze sklepu Google Play moze byc oparta o obecny web core, zamiast przepisywania produktu natywnie.

Przed publikacja trzeba jednak oddzielic trzy warstwy:

- `mobile UI blueprint` - obecne widoki, assety i dokumenty,
- `PWA foundation` - manifest, service worker, installability, cache, offline fallback,
- `TWA/Android wrapper` - Digital Asset Links, package, store config, testy na fizycznym Androidzie.

Nie mieszalbym tego z React Native/Flutter na tym etapie. TWA pozostaje najbardziej naturalna sciezka, bo core nauki, auth, sesje, Inertia i backend sa juz webowe.

### 7. Decyzje robocze po tym passie

- Istniejacy mobile blueprint traktujemy jako material bazowy dla PWA app-shell.
- Nie mamy jeszcze osobnej aplikacji mobilnej w repo.
- Nie piszemy aplikacji od nowa; przerabiamy i profesjonalizujemy obecny webowy core nauki.
- Nie budujemy natywnego mobile przed uporzadkowaniem PWA i `/nauka`.
- `/nauka` mobile jest wazniejszym ekranem startowym niz publiczne `/testy-na-prawo-jazdy`.
- Shared mobile components wymagaja decyzji: standaryzacja albo swiadome pozostawienie lokalnych shelli.
- Przed TWA trzeba usunac lub podpiac placeholdery metryk w `MobileLearningDashboard.vue`.

## Osmy pass: kontrakt danych mobilnego `/nauka`

### 1. Co `/nauka` dostaje dzis z backendu

Glowny payload dla `/nauka` buduje `SessionPageController`. Aktualnie frontend `Session/Index.vue` dostaje:

| Obszar | Prop / zrodlo | Status dla mobile |
| --- | --- | --- |
| Kategoria | `category` | realne dane, lekkie i potrzebne |
| Filtry startu | `filters` | realne dane, potrzebne do startu nauki |
| Tematy i postep tematow | `group_options` z `StudyTopicGroupsService` | realne dane, zawiera counts: all/unanswered/incorrect/correct/memorized |
| Statusy pytan | `status_options` | realne dane slownikowe |
| Dostep | `access.full_product` | realne dane, ale obejmuje tylko full product |
| Powtorki | `learning_overview.due_review_count` | realne dane z `StudyContextService::dueReviewCount()` |
| Ranking | `ranking_preview` | realne lekkie dane: position/rating/matches/message |
| PJM | `pjm_module` | realne dane: available/reason/preferred/starter/coverage/href |
| Zaproszenia | `friend_invitation_cta` | realne dane, pomocnicze |

To jest dobry lekki payload dla webowego huba. Problem pod PWA/TWA nie polega na braku core danych, tylko na tym, ze mobile app home potrzebuje dodatkowych, jawnych agregatow zamiast lokalnych wyliczen i placeholderow.

### 2. Co `MobileLearningDashboard.vue` liczy lokalnie

Mobile dashboard dostaje propsy z `Session/Index.vue`, ale duza czesc logiki jest liczona po stronie frontendu:

| Element UI | Dzisiejsze zrodlo | Ocena |
| --- | --- | --- |
| Hero "Kontynuuj nauke" | `selectedTopic`, `selectedPath`, `group_options`, `filters` | OK jako prezentacja, ale nazwa myli sie z resume aktywnej sesji |
| Progres dzialu | `questions_count - counts.unanswered` | OK, pochodzi z realnych counts |
| Progres kursu | `totalAnsweredQuestions / (answered + unanswered)` | OK, realne, ale backend nie nazywa tego jako osobny kontrakt |
| Poprawne/bledne | `totalAnsweredQuestions - totalIncorrectQuestions`, `totalIncorrectQuestions` | OK, realne z topic groups |
| Rekomendowany krok | computed `recommendedStep` w `Session/Index.vue` | dziala, ale reguly sa frontendowe, nie kontraktowe |
| Trener pamieci strip | `learning_overview.due_review_count` | OK, realne |
| Ranking panel | `ranking_preview` | OK, realne |
| Tygodniowy wykres | `weeklyProgressBars` statycznie w komponencie | placeholder |
| Czas nauki | twarde `--` | placeholder |
| Ostatnia aktywnosc | twarde `Dzis, 14:32` i `85%` | placeholder |

Najwieksza luka: aplikacja mobilna nie powinna miec w UI danych, ktore wygladaja jak statystyki kursanta, jesli nie pochodza z backendu. Przed TWA trzeba albo podpiac backend, albo ukryc te sekcje.

### 3. Istniejace zrodla metryk, ktore mozna wykorzystac

W repo juz istnieja gotowe klocki:

| Zrodlo | Co daje | Uwagi |
| --- | --- | --- |
| `DashboardMetricsService` | `sessions_today`, `answered_today`, `classic_*`, `memory_trainer_*`, `ready_for_review_count`, `hard_questions_count`, `readiness_score`, `study_streak`, `last_session_at`, `recentSessions`, kategorie | jest uzywany przez `GET /api/v1/me/dashboard`, ale nie przez `/nauka` |
| `DashboardApiController` | JSON `data` z metrykami i `meta.categories` | endpoint istnieje jako `api.v1.me.dashboard`, pod `product.access` |
| `ReviewQueueController` | `plan`, `telemetry`, `stats.ready_for_review_count`, kategorie `due_count` | dobry wzorzec kontraktu dla trenera pamieci |
| `ReviewTrainerAnalyticsService` | sesje, completion rate, average score/duration, today counts, last completed | przydatne do memory summary, ale nie jako ciezki payload huba na kazde wejscie bez kontroli |
| `StudySessionManager::activeSessionForUser()` | najnowsza sesja `study_sessions` w statusie `in_progress` | istnieje, ale `/nauka` nie dostaje jej summary |
| `TrafficSignLearningSessionService::activeSession()` | najnowsza aktywna sesja znakow | osobny swiat aktywnej sesji, trzeba uwzglednic w mobile home |
| `traffic_sign_learning_sessions` | status, mode, total_signs_count, correct_answers_count, score_percent, started_at, completed_at | dobre zrodlo dla `recent_learning_activity` |

### 4. Aktualne luki kontraktowe pod mobile home

| Potrzeba mobile `/nauka` | Stan dzis | Co powinno byc docelowo |
| --- | --- | --- |
| Resume aktywnej sesji | backend ma `activeSessionForUser()`, ale `/nauka` nie dostaje `active_session` | `active_session` w payloadzie huba albo `GET /api/v1/sessions/current` |
| Resume znakow drogowych | osobny active session w `TrafficSignLearningSessionService` | `active_learning.signs_session` albo wspolne `active_items` |
| Rekomendowany krok | liczony w Vue | backendowy `recommended_next_action`, z frontendem jako prezentacja |
| Ostatnia aktywnosc | hardcoded w Vue | `recent_learning_activity[]` z `study_sessions` + `traffic_sign_learning_sessions` |
| Tygodniowy wykres | statyczne `weeklyProgressBars` | `weekly_activity[]` albo ukryc sekcje |
| Czas nauki | `--` | `study_time_seconds/minutes` z sesji albo brak UI |
| Dzisiaj | czesciowo w `DashboardMetricsService` | `today` w `learning_dashboard` albo wykorzystanie `api.v1.me.dashboard` |
| Readiness/streak | istnieje w `DashboardMetricsService` | jawne pola w mobile bootstrap, jesli wydajnosc OK |
| Access states | web ma redirecty i `access.full_product`; PJM jest osobno | bootstrap musi zwracac `required_action` i access decisions |
| PJM starter | `pjm_module` istnieje | utrzymac, ale w kontrakcie mobile jawnie opisac jako tryb startowy |

### 5. Proponowany docelowy kontrakt `learning_dashboard`

Najbezpieczniej nie rozpychac bez konca obecnego `learning_overview`. Lepiej dodac osobny obiekt `learning_dashboard` dla huba `/nauka` i pozniejszy odpowiednik API:

```json
{
  "learning_dashboard": {
    "target_category": {
      "id": 1,
      "code": "B",
      "name": "Kategoria B"
    },
    "access": {
      "full_product": true,
      "pjm": false,
      "required_action": "learning_home"
    },
    "active_session": {
      "id": 123,
      "kind": "study_session",
      "mode": "learn",
      "status": "in_progress",
      "title": "Nauka klasyczna",
      "subtitle": "Znaki ostrzegawcze",
      "answered_count": 8,
      "total_count": 30,
      "progress_percent": 27,
      "resume_url": "/nauka/teraz",
      "started_at": "2026-07-06T10:30:00+02:00"
    },
    "recommended_next_action": {
      "action": "memory",
      "title": "Wroc do trenera pamieci",
      "metric_label": "Do powtorki",
      "metric_value": "12",
      "href": "/trener-pamieci"
    },
    "today": {
      "answered_count": 42,
      "accuracy_percent": 85,
      "study_streak": 7
    },
    "course_progress": {
      "answered_count": 1264,
      "correct_count": 1120,
      "incorrect_count": 144,
      "progress_percent": 61
    },
    "review": {
      "due_count": 12
    },
    "ranking": {
      "position": 24,
      "rating": 1020,
      "matches_played": 4
    },
    "weekly_activity": [
      {
        "date": "2026-07-06",
        "answered_count": 42,
        "study_time_minutes": 18
      }
    ],
    "recent_learning_activity": [
      {
        "type": "study_session",
        "title": "Nauka klasyczna",
        "subtitle": "Znaki ostrzegawcze",
        "question_count": 30,
        "score_percent": 85,
        "completed_at": "2026-07-06T14:32:00+02:00",
        "completed_at_label": "Dzis, 14:32",
        "href": "/nauka/wynik/123"
      }
    ]
  }
}
```

To jest szkic docelowy. Nie wszystkie pola musza wejsc w pierwszy etap. W PWA v1 najwazniejsze sa: `active_session`, `recommended_next_action`, `today`, `course_progress`, `review`, `ranking`, `recent_learning_activity`.

### 6. Rekomendowana kolejnosc implementacji kontraktu

1. Najpierw dodac `active_session` summary do `/nauka` albo osobny endpoint current session.
2. Potem przeniesc `recommendedStep` z Vue do backendowego `recommended_next_action` albo przynajmniej utrzymac te same reguly w jednym builderze.
3. Potem podpiac `recent_learning_activity` i usunac hardcoded `Dzis, 14:32` / `85%`.
4. Potem zdecydowac, czy `weekly_activity` i `study_time` sa tanie i potrzebne w PWA v1; jesli nie, ukryc UI.
5. Na koncu wystawic odpowiednik jako `GET /api/v1/me/learning-home` dla TWA/mobile bootstrap.

### 7. Decyzje robocze po tym passie

- `learning_overview` jest za waskie jako docelowy kontrakt mobile home.
- Docelowo potrzebny jest osobny `learning_dashboard` albo `GET /api/v1/me/learning-home`.
- `MobileLearningDashboard.vue` powinien prezentowac dane, nie udawac statystyk lokalnymi placeholderami.
- `active_session` jest pierwszym brakujacym elementem, bo decyduje o resume po starcie PWA/TWA.
- `recent_learning_activity` powinno laczyc `study_sessions` i `traffic_sign_learning_sessions`.
- `DashboardMetricsService` jest dobrym zrodlem, ale trzeba kontrolowac wydajnosc i zakres, zeby `/nauka` nie stalo sie ciezkim dashboardem.

## Dziewiaty pass: obszary do doskanowania przed projektowaniem PWA

Zeby moc wejsc w projektowanie aplikacji mobilnej PWA/TWA profesjonalnie, brakuje jeszcze kilku skanow. Celem nie jest pisanie kodu, tylko zamkniecie ryzyk, granic i decyzji produktowo-technicznych.

### Rejestr skanow PWA/mobile

Ten rejestr odhaczamy w trakcie pracy. Statusy:

- `TODO` - jeszcze nieprzeskanowane,
- `W toku` - aktualnie badane,
- `Czesciowo` - mamy istotne ustalenia, ale zostaly otwarte luki,
- `Gotowe` - wystarczajaco przeskanowane do projektowania.

| Nr | Obszar | Status | Wynik / nastepny krok |
| --- | --- | --- | --- |
| 1 | PWA foundation i runtime | Gotowe | Brak manifestu/SW; Vite build jest dobry pod precache; potrzebne meta, ikony, SW, cache exceptions |
| 2 | Auth, sesja, CSRF i background | Gotowe | Jest dobry router po auth i CSRF lifecycle; mobile bootstrap musi zwracac `required_action` |
| 3 | Aktywna sesja `/nauka/teraz` | Gotowe | Player i backend aktywnej sesji przeskanowane statycznie; osobno zostaje visual QA Android/PWA |
| 4 | Mobile API i kontrakty backendowe | Gotowe | API v1 przeskanowane; baza istnieje, ale wymaga kontraktu `current`, filtrow, bledow i spec update |
| 5 | Offline, cache i lokalne przechowywanie | Gotowe | Brak SW/IndexedDB; PWA v1 online-first; macierz cache/no-cache dopisana |
| 6 | Media, assety i waga aplikacji | Gotowe | Media przeskanowane; app shell lekki, katalog kursowy duzy; runtime cache tylko z limitami |
| 7 | Platnosci, dostep premium i Google Play policy | Gotowe | Najbezpieczniejszy start Play/TWA: consumption-only bez checkoutu w aplikacji; zakup w appce to osobny projekt billingowy |
| 8 | Android/TWA release path | Gotowe | Brak wrappera Android; TWA dopiero po PWA manifest/SW; potrzebne AAB, Play App Signing i `assetlinks.json` z wlasciwym certyfikatem |
| 9 | UX/design system mobile | Gotowe | Mobile blueprint jest wartosciowy, ale rozproszony; potrzebny wspolny app shell, Android Back contract i usuniecie placeholderow |
| 10 | QA, monitoring i wydajnosc | Gotowe | Backend QA jest mocny; brakuje PWA quality gate: Lighthouse, mobile E2E matrix, SW/cache tests i client-side monitoring |

### 1. PWA foundation i runtime

Status: krytyczne przed projektem.

Co trzeba sprawdzic:

- czy istnieje manifest, service worker, offline fallback, ikony, theme color i meta mobile,
- jak Vite/Laravel buduje assety i gdzie najlepiej wpiac manifest/SW,
- jakie strony maja byc installable i jaki bedzie `start_url`,
- jakie cache headers maja `public/build`, obrazy, media i fonty,
- czy obecny layout dobrze zachowuje sie w trybie standalone.

Wynik skanu:

- techniczna specyfikacja PWA v1,
- lista wymaganych ikon i screenshotow,
- decyzja cache strategy dla shell/assets.

### 2. Auth, sesja, CSRF i powrot z backgroundu

Status: krytyczne przed projektem.

Co trzeba sprawdzic:

- login flow z ikony PWA/TWA,
- powrot po wygaslej sesji,
- 419 CSRF i retry po background/foreground,
- email unverified, forced password change, temporary account claim,
- czy `/dashboard`, `/nauka`, `/nauka/teraz` daja przewidywalny routing po auth,
- jak TWA/Chrome Custom Tabs utrzymuje cookies i sesje.

Wynik skanu:

- test plan auth dla PWA/TWA,
- kontrakt `required_action` dla mobile bootstrap,
- zasady obslugi wygaslej sesji i 419 w UI.

### 3. Aktywna sesja `/nauka/teraz` jako ekran aplikacji

Status: krytyczne przed projektem.

Co trzeba sprawdzic:

- mobile UX `StudySessions/Show.vue`,
- mobile UX `StudySessions/Exam.vue`,
- Zen mode, PJM, trener pamieci i media w aktywnej sesji,
- Android back button i historia przegladarki,
- safe-area, fixed docks, viewport 360/390/430,
- powrot do aktywnej sesji z `/nauka`,
- co sie dzieje po refreshu, utracie sieci i backgroundzie.

Wynik skanu:

- lista zmian mobile polish dla playera,
- decyzja, czy player potrzebuje osobnego app shell,
- test plan dla najwazniejszego flow nauki.

### 4. Mobile API i kontrakty backendowe

Status: krytyczne przed projektem.

Co trzeba sprawdzic:

- finalny ksztalt `GET /api/v1/me/learning-home`,
- `active_session` i ewentualny `GET /api/v1/sessions/current`,
- roznice web `/study-sessions` vs `/api/v1/sessions`,
- envelope bledow walidacji, 403, 419, 401,
- wersjonowanie API,
- czy TWA idzie tylko Inertia/web, czy potrzebuje JSON bootstrapu juz w v1.

Wynik skanu:

- kontrakt API mobile v1,
- minimalny payload dla PWA online-first,
- lista endpointow do dodania albo ujednolicenia.

### 5. Offline, cache i lokalne przechowywanie

Status: wazne, ale nie musi blokowac PWA v1, jesli przyjmujemy online-first.

Co trzeba sprawdzic:

- ktore dane mozna cache'owac bezpiecznie,
- czego nie cache'owac: prywatny HTML/Inertia, odpowiedzi, stan egzaminu bez strategii sync,
- czy offline fallback ma byc tylko informacyjny,
- czy IndexedDB jest potrzebne w v1,
- jak wyglada kolejka odpowiedzi w offline-lite,
- jak rozwiazywac konflikty przy wielu urzadzeniach.

Wynik skanu:

- macierz `cache / no-cache / future offline-lite`,
- decyzja, czy PWA v1 ma jakikolwiek tryb offline poza fallbackiem,
- zarys przyszlej kolejki synchronizacji.

### 6. Media, assety i waga aplikacji

Status: krytyczne przed PWA/TWA polish.

Co trzeba sprawdzic:

- obrazy pytan, video, audio, PJM, wyjasnienia graficzne,
- storage URLs i cache headers,
- rozmiary mediow i limity cache,
- preloading/prefetch w aktywnej sesji,
- zachowanie na wolnym internecie,
- ikony PWA, splash, screenshoty Google Play.

Wynik skanu:

- polityka cache mediow,
- lista assetow PWA/TWA do przygotowania,
- zasady prefetchu mediow w playerze.

### 7. Platnosci, dostep premium i Google Play policy

Status: wazne przed TWA/Google Play.

Co trzeba sprawdzic:

- czy aplikacja ze sklepu ma sprzedawac dostep,
- czy ma tylko logowac uzytkownika z dostepem kupionym przez web,
- jak obecny checkout i `product.access` zachowa sie w TWA,
- czy Google Play wymusi Billing dla cyfrowego dostepu,
- co pokazujemy uzytkownikowi bez pelnego dostepu,
- jak traktujemy PJM starter.

Wynik skanu:

- decyzja biznesowo-prawna: zakup w app vs zakup przez web,
- flow dla braku dostepu,
- ryzyka Google Play przed publikacja.

### 8. Android/TWA release path

Status: potrzebne przed projektowaniem sklepowej aplikacji, niekoniecznie przed pierwszym designem PWA.

Co trzeba sprawdzic:

- Bubblewrap/TWA albo inny wrapper,
- Digital Asset Links i `assetlinks.json`,
- package name, signing, keystore,
- status bar/navigation bar colors,
- handling Android back,
- testy na realnych urzadzeniach,
- listing Google Play, screenshoty, polityki prywatnosci.

Wynik skanu:

- release checklist TWA,
- lista rzeczy poza web app,
- decyzja, kiedy tworzymy projekt Android.

### 9. UX/design system mobile

Status: krytyczne dla projektowania.

Co trzeba sprawdzic:

- czy standaryzujemy `MobileAppBar`, `MobileBottomAction`, `MobileProgressRing`,
- kiedy uzywamy globalnego `SiteHeader`, a kiedy app bar,
- bottom CTA, bottom sheet, safe-area, empty states, errors,
- docelowe ekrany: `/nauka`, `/nauka/teraz`, wynik, trener pamieci, znaki, ranking,
- accessibility: focus, aria, reduced motion, tap targets.

Wynik skanu:

- mobile app shell spec,
- komponenty bazowe i zasady uzycia,
- lista ekranow do projektu UI.

### 10. QA, monitoring i wydajnosc

Status: wazne przed planem implementacji.

Co trzeba sprawdzic:

- Playwright mobile matrix: 360x740, 390x844, 430x932, 768x1024,
- Lighthouse PWA,
- performance `/nauka` po dodaniu metryk,
- logs/telemetry dla sesji i bledow 419/500,
- Sentry albo obecny monitoring produkcyjny,
- testy end-to-end start/resume/answer/complete.

Wynik skanu:

- QA checklist PWA/TWA,
- minimalny zestaw testow przed publikacja,
- budzet wydajnosci dla `/nauka` i `/nauka/teraz`.

### Kolejnosc rekomendowana

Najpierw skanowalbym:

1. PWA foundation i runtime.
2. Auth/sesja/CSRF/background.
3. Aktywna sesja `/nauka/teraz` jako ekran aplikacji.
4. UX/design system mobile.
5. Mobile API finalizacja: `learning-home`, `active_session`, `recent_learning_activity`.

Dopiero potem:

6. Offline/cache.
7. Media i assety.
8. Platnosci/Google Play policy.
9. Android/TWA release path.
10. QA/monitoring/wydajnosc jako plan przekrojowy.

## Dziesiaty pass: PWA foundation i runtime

### 1. Stan obecny

Skan lokalnego kodu pokazuje, ze aplikacja nie ma jeszcze PWA runtime:

| Element | Stan |
| --- | --- |
| Web app manifest | brak `manifest.webmanifest`, `site.webmanifest` i `manifest.json` aplikacyjnego |
| Service worker | brak `service-worker.js`, `sw.js`, rejestracji `navigator.serviceWorker` i Workbox |
| Vite PWA plugin | brak `vite-plugin-pwa` / Workbox w `package.json` i `vite.config.js` |
| Meta PWA | brak `theme-color`, `mobile-web-app-capable`, `apple-mobile-web-app-capable`, `apple-mobile-web-app-title` |
| Favicons | istnieje `favicon.ico`, `favicon.png` 256x256 i `apple-touch-icon.png` 180x180 |
| Start URL | decyzja robocza: `/nauka`, ale nie jest jeszcze zapisana w manifest |
| Scope | brak, docelowo prawdopodobnie `/` |
| Offline fallback | brak |
| Standalone mode | brak detekcji `display-mode: standalone` i brak dedykowanego testu |

Najwazniejsze pliki:

- `resources/views/app.blade.php` - glowny shell Inertia dla aplikacji,
- `resources/views/layouts/public-content.blade.php` - shell publicznych stron content/SEO,
- `resources/views/components/site/favicons.blade.php` - wspolny komponent favicon,
- `resources/js/app.ts` - glowny entrypoint Inertia,
- `resources/js/public-content.ts` - publiczny entrypoint contentowy,
- `vite.config.js` - Vite/Laravel/Vue build,
- `public/.htaccess` - Apache rewrite, bez naglowkow PWA/cache,
- `deploy/mikrus/nginx/prawkobit.conf.example` - produkcyjny przyklad cache statycznych assetow.

### 2. Co juz sprzyja PWA

Sa dobre fundamenty:

- Vite generuje hashowane assety w `public/build/assets`.
- Istnieje `public/build/manifest.json` Vite, ktory mapuje entrypointy i lazy chunki.
- Build jest code-splitowany; aktualny `public/build/assets` ma okolo 109 plikow i okolo 8.8 MB.
- `app.ts` uruchamia `setupCsrfSessionLifecycle()`, czyli aplikacja ma juz mechanizm oznaczania potrzeby odswiezenia CSRF po `pageshow` z bfcache i po dluzszym backgroundzie.
- Layouty maja `viewport` i `csrf-token`.
- Wspolny komponent `x-site.favicons` jest dobrym miejscem do dodania manifestu i meta PWA.
- Deployment Nginx ma cache dla statycznych assetow, co jest dobre dla hashowanych plikow Vite.

### 3. Luki i ryzyka

Najwazniejsze luki:

- Nie ma manifestu aplikacji, wiec przegladarka nie ma nazwy, ikon, `start_url`, `display`, `scope`, `theme_color`.
- Nie ma service workera, wiec aplikacja nie jest instalowalna jako pelna PWA i nie ma offline fallbacku.
- Nie ma rejestracji service workera w `app.ts` ani `public-content.ts`.
- Nie ma rozroznienia cache dla:
  - hashowanych assetow Vite,
  - prywatnego HTML/Inertia,
  - API/JSON,
  - service workera,
  - manifestu,
  - mediow pytan.
- Produkcyjny Nginx cache'uje `css/js/jpg/png/webp/avif/svg/ico/woff/woff2` przez 7 dni z `immutable`. To jest dobre dla hashowanych assetow, ale service worker i manifest nie moga byc potraktowane tak samo.
- `public/.htaccess` nie ustawia naglowkow cache; jesli produkcja albo staging uzyje Apache, trzeba dopisac odpowiedniki.
- Aplikacja ma zewnetrzne fonty:
  - `resources/css/app.css` importuje Google Fonts `Open Sans` i `Space Grotesk`,
  - `resources/views/app.blade.php` laduje Bunny `Figtree`,
  - `StudySessions/Show.vue` importuje Bunny `Inter`.
- Zewnetrzne fonty nie blokuja PWA, ale dla app-shell i offline fallback sa ryzykiem stabilnosci i spojnosc typografii wymaga uporzadkowania.

### 4. Gdzie najlepiej wpiac PWA

Najczystsze miejsca:

| Potrzeba | Proponowane miejsce |
| --- | --- |
| Link do manifestu i meta PWA | `resources/views/components/site/favicons.blade.php` albo bezposrednio oba layouty |
| Rejestracja SW dla aplikacji | `resources/js/app.ts` |
| Rejestracja SW dla publicznych stron | `resources/js/public-content.ts`, jesli chcemy instalowanie z publicznych stron |
| Service worker | `public/service-worker.js` albo generowany przez Vite plugin |
| Offline fallback | `public/offline.html` albo dedykowana trasa statyczna |
| Ikony PWA | `public/icons/*` albo `public/pwa/*` |
| Digital Asset Links pozniej | `public/.well-known/assetlinks.json` |

Decyzja do podjecia:

- Jesli PWA ma byc aplikacja dla zalogowanych, wystarczy zaczac od `app.ts` i `/nauka`.
- Jesli uzytkownik ma moc instalowac aplikacje z publicznego landing/SEO, trzeba tez dodac manifest/SW do publicznego shellu.

Moja rekomendacja: manifest i meta dac globalnie, ale service worker v1 projektowac pod start `/nauka` i online-first learning app.

### 5. Manifest PWA v1

Minimalny manifest powinien zawierac:

```json
{
  "name": "Prawko na raz",
  "short_name": "Prawko",
  "start_url": "/nauka",
  "scope": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#064f9e",
  "orientation": "portrait",
  "icons": [
    {
      "src": "/icons/icon-192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "/icons/icon-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "/icons/icon-maskable-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "maskable"
    }
  ]
}
```

Potrzebne assety:

- 192x192,
- 512x512,
- 512x512 maskable z bezpiecznym paddingiem,
- Apple touch 180x180 juz istnieje, ale trzeba zweryfikowac wizualnie,
- opcjonalnie screenshoty pod install prompt i Google Play/TWA.

Obecne logo/favicons moga byc zrodlem, ale nie zakladam, ze sa gotowe jako maskable icon.

### 6. Service worker PWA v1

Dla pierwszej wersji rekomenduje online-first:

| Request | Strategia PWA v1 |
| --- | --- |
| Nawigacje HTML/Inertia | network-only albo network-first z offline fallbackiem, bez cache prywatnego HTML |
| `/nauka`, `/nauka/teraz` HTML | network-only; offline pokazuje fallback "Brak polaczenia" |
| Vite hashed JS/CSS/assets | cache-first / precache, bo nazwy sa hashowane |
| `service-worker.js` | no-cache albo bardzo krotki cache |
| `manifest.webmanifest` | no-cache albo krotki cache |
| `/auth/csrf-token` | no-store |
| `/api/*` i odpowiedzi sesji | no-store w PWA v1 |
| Media pytan | osobny pass; nie wrzucac masowo do precache |
| Fonty | najlepiej self-host albo cache ostroznie po uporzadkowaniu |

Nie rekomenduje w PWA v1 cache'owac:

- prywatnego HTML Inertia,
- odpowiedzi uzytkownika,
- aktywnego stanu egzaminu,
- payloadow sesji nauki,
- mediow pytan bez limitow i wersjonowania.

### 7. Deployment/cache headers

`deploy/mikrus/nginx/prawkobit.conf.example` ma:

- `expires 7d`,
- `Cache-Control: public, max-age=604800, immutable`,
- dla statycznych rozszerzen: `css`, `js`, `jpg`, `jpeg`, `gif`, `png`, `webp`, `avif`, `svg`, `ico`, `woff`, `woff2`.

To jest OK dla `public/build/assets/*`, ale trzeba dodac wyjatki:

- `/service-worker.js` - `Cache-Control: no-cache, no-store albo max-age=0, must-revalidate`,
- `/manifest.webmanifest` - `Cache-Control: no-cache albo max-age=300`,
- `/offline.html` - krotki cache albo precache przez SW,
- `/.well-known/assetlinks.json` - publiczny JSON, bez blokady przez reguly dotfiles.

Obecny Nginx blokuje dotfiles przez `location ~ /\.(?!well-known).*`, czyli `.well-known` jest dopuszczone. To dobrze pod Digital Asset Links, ale sam plik jeszcze nie istnieje.

### 8. Decyzje robocze po tym passie

- Obszar PWA foundation/runtime jest przeskanowany wystarczajaco do projektowania.
- PWA runtime trzeba dodac od zera: manifest, SW, rejestracja, offline fallback, meta.
- Nie trzeba przebudowywac core aplikacji ani Vite; obecny pipeline jest dobrym fundamentem.
- `start_url` zostaje `/nauka`.
- PWA v1 powinna byc online-first.
- Haskowane assety Vite moga byc cache-first/precache.
- Prywatny HTML/Inertia, API, CSRF i stan sesji nauki zostaja network/no-store w v1.
- Przed implementacja trzeba zdecydowac, czy service worker rejestrujemy tylko w `app.ts`, czy tez w `public-content.ts`.
- Trzeba przygotowac pelny zestaw ikon PWA, w tym maskable 512.
- Trzeba dodac wyjatki cache dla `service-worker.js`, `manifest.webmanifest` i pozniej `assetlinks.json`.

## Jedenasty pass: auth, sesja, CSRF i background

### 1. Stan obecny

Auth i sesja sa juz calkiem dobrze przygotowane pod PWA/TWA, ale brakuje jednego wspolnego kontraktu `required_action` dla mobile bootstrap.

Kluczowe elementy:

| Element | Stan |
| --- | --- |
| Login | `AuthenticatedSessionController`, po loginie `redirect()->intended('/dashboard')` |
| Router po auth | `PostAuthRedirectController` na `/dashboard` |
| CSRF refresh | `GET /auth/csrf-token`, `Cache-Control: no-store, private` |
| Lifecycle background | `setupCsrfSessionLifecycle()` w `app.ts` |
| API client | preferuje `X-XSRF-TOKEN` cookie, fallback do meta `csrf-token` |
| 419 JSON | `CSRF_TOKEN_MISMATCH` przez global exception handler dla API/JSON |
| 401 JSON | `UNAUTHORIZED` dla `/api/*` |
| Session expired UI | `SessionExpiredNotice` + `useSessionExpiry` |
| Sesja Laravel | database driver, lifetime 120 min, SameSite `lax`, secure cookie z env |

### 2. Router po logowaniu

`PostAuthRedirectController` jest dzisiaj najlepszym zrodlem prawdy dla startu aplikacji po logowaniu:

1. konto tymczasowe nieprzejete -> `account.claim.edit`,
2. wymuszona zmiana hasla -> `password.force.edit`,
3. email niezweryfikowany -> `verification.notice`,
4. pending friend invitation -> `friend-invitations.pending.show`,
5. moderator -> `moderator.accounts.index`,
6. pelny dostep -> `/nauka`,
7. darmowy PJM -> `/nauka`,
8. brak dostepu -> `access.activate`.

To trzeba przepisac na kontrakt dla PWA/TWA:

```json
{
  "required_action": "learning_home",
  "routes": {
    "dashboard": "/dashboard",
    "learning_home": "/nauka",
    "activate": "/aktywuj-dostep",
    "verify_email": "/verify-email",
    "claim_account": "/konto/przejmij",
    "change_password": "/konto/zmien-haslo-startowe"
  }
}
```

Proponowane wartosci `required_action`:

- `login`,
- `claim_account`,
- `change_password`,
- `verify_email`,
- `pending_invitation`,
- `moderator_dashboard`,
- `learning_home`,
- `activate_access`,
- `pjm_learning_home`,
- `session_expired`.

### 3. Granice dostepu w routingu

Najwazniejsze rozroznienie:

- `/nauka` jest pod `auth` + `verified`, ale nie jest bezposrednio pod `product.access`, bo moze obsluzyc tez PJM starter.
- pelne tryby nauki, znaki, ranking, trener pamieci i start `/study-sessions` sa pod `product.access`.
- `/nauka/pjm` jest pod `pjm.access`.
- `/nauka/teraz` i wynik/sesja sa pod `study.session.access`, ktory dopuszcza pelny produkt albo aktywna sesje PJM.
- `/api/v1` jest pod `auth` + `verified`, a sesje API sa dodatkowo pod `product.access`.

Wniosek dla mobile: `GET /api/v1/me/learning-home` nie powinien byc pod `product.access`, bo musi zwracac stan braku dostepu i akcje `activate_access`. Powinien byc raczej pod `auth` + odpowiednia obsluga stanu email/claim/password albo miec wlasny resolver required action.

### 4. CSRF i powrot z backgroundu

Istniejacy mechanizm jest dobry:

- `CsrfTokenController` zwraca `token` i `authenticated`,
- odpowiedz ma `Cache-Control: no-store, private`,
- `csrfSession.ts` deduplikuje rownolegle refresh requesty,
- po `pageshow` z bfcache oznacza token jako wymagajacy refreshu,
- po powrocie z backgroundu dluzszym niz 15 minut oznacza token jako wymagajacy refreshu,
- przed kolejna mutacja `apiClient` probuje odswiezyc token,
- write requesty preferuja aktualny cookie `XSRF-TOKEN` nad potencjalnie starym meta tokenem.

To jest dobre pod PWA, bo aplikacja czesto wraca z backgroundu. Nie jest to jeszcze pelna obsluga offline ani utraty sieci, ale jest solidna baza dla online-first.

### 5. Obsluga wygaslej sesji w UI

Frontend ma wspolny wzorzec:

- `useSessionExpiry` wykrywa `401` i `419`,
- `SessionExpiredNotice` blokuje dalsze operacje i daje akcje:
  - "Zaloguj sie ponownie",
  - "Odswiez strone".

Ten wzorzec jest juz uzywany w:

- `StudySessions/Show.vue`,
- `StudySessions/Exam.vue`,
- `TrafficSignLearning/Show.vue`,
- `Session/Ranking.vue`.

To jest wazne, bo najwieksze ryzyko PWA to odpowiedz wyslana po wygaslej sesji albo po backgroundzie. Aktualny kod juz probuje zatrzymac dalsze zapisy zamiast ponawiac w ciemno.

### 6. Luki pod PWA/TWA

| Luka | Znaczenie |
| --- | --- |
| Brak `required_action` JSON | mobile home nie ma jednego kontraktu stanu uzytkownika |
| Access middleware dla API daje ogolny `FORBIDDEN` | frontend traci powod: brak dostepu, PJM niedostepny, email, haslo itd. |
| `/api/v1` jest pod `verified` | niezweryfikowany user nie dostanie bootstrapu API, jesli nie zrobimy osobnego endpointu/wyjatku |
| Brak centralnego mappingu PostAuthRedirect -> JSON | ryzyko rozjazdu web redirectow i mobile kontraktu |
| Brak test planu PWA launch z ikony | trzeba sprawdzic login, powrot, wygasla sesje, redirecty |
| Brak dedykowanego UI dla `activate_access` w app shell | na razie web redirect wystarczy, ale app powinna miec spojny stan |

### 7. Proponowany kontrakt auth/bootstrap

`GET /api/v1/me/learning-home` albo `GET /api/v1/mobile/bootstrap` powinien zwracac przynajmniej:

```json
{
  "auth_state": "authenticated",
  "required_action": "learning_home",
  "user": {
    "id": 1,
    "name": "Kamil",
    "email_verified": true
  },
  "access": {
    "full_product": {
      "allowed": true,
      "reason": null
    },
    "pjm": {
      "allowed": false,
      "reason": "pjm_track_not_selected"
    }
  },
  "routes": {
    "login": "/login",
    "dashboard": "/dashboard",
    "learning_home": "/nauka",
    "current_session": "/nauka/teraz",
    "activate": "/aktywuj-dostep",
    "verify_email": "/verify-email",
    "claim_account": "/konto/przejmij",
    "change_password": "/konto/zmien-haslo-startowe"
  }
}
```

Wersja PWA online-first moze nadal uzywac web redirectow, ale kontrakt jest potrzebny, zeby TWA/mobile app miala czysty start i zeby frontend nie musial zgadywac z HTML redirectow.

### 8. Decyzje robocze po tym passie

- Obszar auth/sesja/CSRF/background jest przeskanowany wystarczajaco do projektowania.
- `PostAuthRedirectController` powinien stac sie zrodlem prawdy dla `required_action`.
- `GET /api/v1/me/learning-home` nie powinien byc chroniony przez `product.access`.
- Dla PWA v1 mozemy zostac przy sesjach webowych i CSRF.
- `/auth/csrf-token` musi zostac `no-store`.
- Wygasla sesja powinna byc pokazywana jako stan UI, nie jako cichy retry odpowiedzi.
- API access errors powinny docelowo zwracac bardziej semantyczne kody niz samo `FORBIDDEN`.
- Przed TWA trzeba przetestowac: launch z ikony -> login -> dashboard router -> `/nauka`; oraz background > 15 min -> odpowiedz w sesji.

## Dwunasty pass: aktywna sesja `/nauka/teraz` jako ekran aplikacji

Ten pass dotyczy srodka playera, czyli miejsca, w ktorym kursant realnie sie uczy. To jest wazniejsze dla PWA/TWA niz sama instalowalnosc, bo aplikacja mobilna bedzie oceniana glownie po tym, czy sesja nauki jest stabilna, szybka i przewidywalna na telefonie.

### 1. Wejscie i routing

Kanonicznym adresem aktywnej sesji jest `/nauka/teraz`.

Glowne trasy web:

| Trasa | Nazwa | Rola |
| --- | --- | --- |
| `GET /nauka/teraz` | `study-sessions.current` | ekran aktywnej sesji |
| `POST /nauka/teraz/egzamin/stan` | `study-sessions.current.exam.state` | synchronizacja fazy/timera egzaminu |
| `GET /nauka/teraz/pytania` | `study-sessions.current.questions.index` | paczka pytan dla aktywnej sesji |
| `GET /nauka/teraz/pytania/{question}` | `study-sessions.current.questions.show` | pojedyncze pytanie |
| `POST /nauka/teraz/odpowiedzi` | `study-sessions.current.answers.store` | zapis odpowiedzi aktywnej sesji |
| `POST /nauka/teraz/zakoncz` | `study-sessions.current.complete` | zakonczenie aktywnej sesji |
| `GET /nauka/wynik/{studySession}` | `study-sessions.show` | wynik / zakonczona sesja |

Stare webowe adresy `/study-sessions/...` sa w praktyce przekierowywane na nowe adresy `/nauka/...`. Dodatkowo `StudySessions/Show.vue` robi `history.replaceState` do `/nauka/teraz`, jezeli aktywna sesja zostanie otwarta przez stary adres albo przez `/nauka/wynik/...`.

Wniosek dla PWA: `start_url` powinien prowadzic do `/nauka`, ale resume aktywnej sesji powinno isc jawnie do `/nauka/teraz`. Nie startowalbym aplikacji bezposrednio od playera, bo brak aktywnej sesji przekierowuje do huba.

### 2. Backend aktywnej sesji

`StudySessionController::current()` pobiera jedna aktywna sesje przez `StudySessionManager::activeSessionForUser()`. Jezeli jej nie ma, kieruje do `session.index`, czyli huba `/nauka`.

`renderSessionPage()` jest centralnym rendererem playera. Decyduje o komponencie Inertia:

- aktywny egzamin: `StudySessions/Exam`,
- zakonczony egzamin: `StudySessions/ExamResult`,
- pozostale tryby nauki: `StudySessions/Show`.

Payload strony zawiera m.in.:

- `session`,
- `questionIds`,
- `progress`,
- `examUi`,
- `currentQuestion`,
- `questionPool`,
- `questionPoolMode`,
- `questionBatchSize`,
- `prefetchedQuestions`,
- `sessionFilters`,
- `topicGroups`,
- `topicCompletionOverview`,
- `results`.

Dla trybow nauki backend wybiera strategia pytan:

- pelny pool dla mniejszych sesji,
- tryb `windowed` i prefetch dla wiekszych sesji albo Trenera pamieci.

To jest dobre pod mobile, bo nie trzeba zawsze wysylac calego zasobu pytan. Trzeba jednak ujednolicic ten kontrakt z API mobile, zeby PWA/TWA nie musiala odczytywac semantyki tylko z Inertia props.

### 3. Zapis odpowiedzi i odpornosc backendu

`StudySessionAnswerController::storeCurrent()` zapisuje odpowiedz dla aktywnej sesji. W srodku uzywa `StudySessionManager::recordAnswer()`.

Istotne zachowania:

- backend sprawdza wlasciciela sesji,
- egzamin przed zapisem synchronizuje stan timerow,
- pytanie musi nalezec do sesji,
- dla trybow bez elastycznej kolejnosci odpowiedz musi dotyczyc aktualnego pytania,
- w Trenerze pamieci dopuszczony jest answer kind `unknown`,
- jesli odpowiedz dla pytania juz istnieje, backend zwraca istniejacy rekord zamiast tworzyc duplikat,
- po zapisie aktualizowane sa `answered_count`, `current_index`, `correct_answers_count`, `score_percent`, `status` i `completed_at`,
- zapis dziala w transakcji z retry dla `database is locked`.

To daje dobra baze pod retry i mobile. Nie jest to jednak pelny kontrakt offline. Zeby robic trwaly outbox IndexedDB, potrzebujemy jeszcze jawnego `client_operation_id` albo innej idempotency key, bo samo "jedna odpowiedz na pytanie" nie opisuje wszystkich przypadkow retry, anulowania i konfliktu na wielu urzadzeniach.

### 4. Frontend playera: `StudySessions/Show.vue`

`Show.vue` jest glownym playerem nauki dla trybow innych niz aktywny egzamin. To bardzo duzy komponent, ktory laczy:

- nauke klasyczna,
- Zen shell,
- PJM,
- Trenera pamieci,
- wynik sesji,
- public demo,
- media pytan,
- audio pytan i odpowiedzi,
- wyjasnienia,
- ustawienia nauki,
- wybor dzialu,
- mobile bottom sheet i dock odpowiedzi.

Najwazniejsze mobilne mechanizmy:

- viewport jest mierzony przez `window.innerWidth` i `window.innerHeight`,
- telefon to `<= 1023px`,
- waski telefon to `< 640px`,
- bardzo waskie etykiety zaczynaja sie przy `<= 440px`,
- `activeSessionLayout` wybiera `SessionExamLayout` dla `ui_shell === exam_like`, a w przeciwnym razie `SessionZenLayout`,
- na telefonie panel odpowiedzi jest przypinany do dolu ekranu, gdy sesja nie jest zakonczona,
- dock odpowiedzi uzywa `env(safe-area-inset-bottom, 0px)`,
- wysokosc docka jest mierzona przez `ResizeObserver`,
- pod dockiem jest dynamiczny spacer, zeby tresc nie byla zaslonieta,
- mobilne menu sesji jest bottom sheetem z backdropem i akcjami: dzialy oraz ustawienia nauki,
- `Escape` zamyka otwarty panel pomocniczy,
- wygasla sesja blokuje dalsze akcje przez `SessionExpiredNotice`.

To oznacza, ze player nie jest "desktopem zmniejszonym do telefonu". Jest juz realnie projektowany pod mobile.

### 5. Frontend egzaminu: `StudySessions/Exam.vue`

Egzamin jest osobnym komponentem. Ma osobny rytm niz nauka:

- backend jest zrodlem prawdy dla fazy pytania i timerow,
- frontend ma lokalny countdown i synchronizuje backend po wygasnieciu czasu,
- fazy to `preview`, `media`, `answer`,
- odpowiedz jest wysylana przez `apiClient.post()` do `study-sessions.current.answers.store`,
- po zakonczeniu egzaminu frontend przechodzi do wyniku,
- mobile ma osobny header z czasem/pytaniem/punktami,
- mobile ma przypiety dolny panel odpowiedzi z safe-area i spacerem,
- mobile menu egzaminu jest bottom sheetem.

Dobra wiadomosc: egzaminowy layout `SessionExamLayout` blokuje viewport dopiero na `xl`, wiec na telefonach i tabletach przewijanie nie powinno byc brutalnie uciete.

### 6. Layouty sesji

`SessionExamLayout.vue`:

- root ma `min-h-screen`,
- przy `lockViewport` blokuje overflow pionowy dopiero od `xl`,
- to jest bezpieczniejsze dla telefonu.

`SessionZenLayout.vue`:

- root ma `min-h-screen`,
- przy `lockViewport` uzywa `sm:h-screen sm:overflow-y-hidden`,
- to moze byc ryzykiem dla mniejszych tabletow, landscape i standalone mode, bo `sm` zaczyna sie nisko.

Wniosek: przed finalnym projektem PWA trzeba zweryfikowac Zen shell na wysokosciach mobilnych i tabletowych. Potencjalnie lepiej bedzie ujednolicic blokowanie viewportu z `SessionExamLayout`, albo oprzec sie o `dvh/svh` i visual viewport.

### 7. Obsluga sync, bledow i backgroundu

W `Show.vue` dla lokalnych trybow nauki odpowiedzi sa zapisywane optymistycznie i synchronizowane przez kolejke promise w pamieci:

- `enqueueAnswerSync()`,
- `pendingSyncRequests`,
- `pendingAnswerSyncCount`,
- `learningSyncInFlight`,
- `waitForPendingSyncRequests()`.

To poprawia UX online, bo kursant moze isc dalej bez czekania na kazdy request. Ale kolejka jest tylko w pamieci przegladarki. Po ubiciu aplikacji, refreshu albo awarii procesu nie mamy trwalego outboxa.

Wniosek dla PWA v1: online-first jest dobrym zakresem. Offline-lite z zapisem odpowiedzi powinien byc osobnym etapem z IndexedDB, idempotency key i konfliktem wielu urzadzen.

### 8. Android back i historia

Znalezione:

- `Escape` zamyka side panel,
- klik w backdrop zamyka side panel,
- `history.replaceState` kanonizuje aktywna sesje do `/nauka/teraz`.

Nie znalazlem obslugi `popstate` dla bottom sheetow i paneli. W TWA przycisk Android Back jest krytyczny: uzytkownik bedzie oczekiwal, ze najpierw zamknie bottom sheet/menu, a dopiero potem cofnie ekran albo wyjdzie z aplikacji.

Decyzja projektowa: dla PWA/TWA trzeba opisac Android Back contract:

- jesli otwarte jest menu sesji, zamknij menu,
- jesli otwarty jest wybor dzialu, zamknij wybor,
- jesli otwarte sa ustawienia, zamknij ustawienia,
- jesli jest aktywna sesja, cofniecie powinno prowadzic do `/nauka` albo pokazac potwierdzenie,
- dopiero potem pozwolic TWA wyjsc z aplikacji.

### 9. Co musi wejsc do czystego kontraktu mobile

Web PWA moze na start uzywac obecnych tras, ale kontrakt mobile/TWA powinien byc nazwany i stabilny. Minimum:

| Potrzeba | Proponowany kontrakt |
| --- | --- |
| status aktywnej sesji | `GET /api/v1/sessions/current` |
| start/wznowienie sesji | `POST /api/v1/sessions` + `active_session` w `learning-home` |
| zapis odpowiedzi | `POST /api/v1/sessions/current/answers` |
| paczka pytan | `GET /api/v1/sessions/current/questions` |
| pojedyncze pytanie | `GET /api/v1/sessions/current/questions/{question}` |
| zakonczenie sesji | `POST /api/v1/sessions/current/complete` |
| stan egzaminu | `POST /api/v1/sessions/current/exam-state` |
| wymagane akcje usera | `GET /api/v1/me/learning-home` z `required_action` |

Wazne: aplikacja mobilna nie powinna opierac aktywnej sesji o stare `/study-sessions/{id}`. Dla wyniku historycznego `id` ma sens, ale dla resume najczystsze jest `current`.

### 10. Ryzyka aktywnej sesji pod PWA/TWA

| Ryzyko | Znaczenie |
| --- | --- |
| `Show.vue` jest bardzo duzy | zmiana app shell moze przypadkiem zepsuc jeden z trybow nauki |
| Kolejka sync jest tylko w pamieci | offline zapisu odpowiedzi nie mozna obiecac w v1 |
| Brak `popstate` dla sheetow | Android Back w TWA moze zachowywac sie jak cofniecie strony zamiast zamkniecia menu |
| `SessionZenLayout` blokuje scroll od `sm` | ryzyko ucietej tresci na tabletach/standalone |
| Media i PJM zmieniaja wysokosci ekranow | dock i spacer musza byc testowane z video/audio/PJM |
| Egzamin ma twarde timery | background i utrata sieci musza miec osobny test plan |

### 11. Decyzje robocze po tym passie

- Aktywna sesja jest wystarczajaco przeskanowana statycznie do projektowania PWA/TWA.
- Nie pisalbym playera od nowa. Obecny player ma juz realne wzorce mobile, ktore warto wykorzystac.
- Przed implementacja PWA trzeba zaprojektowac app shell wokol istniejacego playera, a nie odwrotnie.
- PWA v1 powinna byc online-first; offline odpowiedzi dopiero po projekcie outboxa.
- `active_session` w hube `/nauka` jest konieczne dla profesjonalnego resume.
- Android Back contract trzeba zaprojektowac przed wrapperem TWA.
- `SessionZenLayout` wymaga visual QA na telefonach, tabletach i standalone.
- Czysty mobile API powinien miec endpointy `current`, nawet jesli web nadal dziala na obecnych trasach.

## Trzynasty pass: mobile API i kontrakt backendowy

Ten pass domyka porownanie webowego playera z obecnym `/api/v1/sessions`. Wniosek jest prosty: API v1 jest dobra baza techniczna, bo korzysta z tego samego `StudySessionManager`, ale nie jest jeszcze kanonicznym kontraktem mobilnym dla `/nauka`.

### 1. Aktualne API v1

Prywatne `/api/v1` jest w `routes/web.php` pod middleware:

- `auth`,
- `verified`.

Endpointy sesji sa dodatkowo pod:

- `product.access`.

Publiczne bez logowania sa tylko:

- `GET /api/v1/health`,
- `GET /api/v1/categories`,
- `GET /api/v1/categories/{licenseCategory}`.

Aktualne endpointy sesji:

| Endpoint | Kontroler | Status dla mobile |
| --- | --- | --- |
| `POST /api/v1/sessions` | `ApiStudySessionController::store` | istnieje, ale ma za malo filtrow |
| `GET /api/v1/sessions/{studySession}` | `ApiStudySessionController::show` | istnieje, ale dziala po id, nie po `current` |
| `POST /api/v1/sessions/{studySession}/answers` | `ApiStudySessionAnswerController::store` | istnieje, ale ma inny payload niz web current |
| `POST /api/v1/sessions/{studySession}/complete` | `ApiStudySessionController::complete` | istnieje |

Brakuje:

- `GET /api/v1/sessions/current`,
- `GET /api/v1/sessions/current/questions`,
- `GET /api/v1/sessions/current/questions/{question}`,
- `POST /api/v1/sessions/current/answers`,
- `POST /api/v1/sessions/current/complete`,
- `POST /api/v1/sessions/current/exam-state`,
- `GET /api/v1/me/learning-home` albo `GET /api/v1/mobile/bootstrap`.

### 2. Rozjazd startu sesji

Web `StudySessionStoreRequest` przyjmuje:

- `license_category_id`,
- `mode`: `exam`, `learn`, `review`, `sr_review`, `hard`, `quick`, `pjm`,
- `ui_shell`: `exam`, `exam_like`, `zen`,
- `question_scope`,
- `question_count`,
- `question_topic_id`,
- `question_status`,
- `randomize_order`.

API `ApiStudySessionStoreRequest` przyjmuje tylko:

- `category_id`,
- `mode`: `exam`, `learn`, `review`, `sr_review`, `hard`, `quick`,
- `question_count`.

Najwazniejsze luki API:

- brak `pjm`,
- brak `ui_shell`,
- brak wyboru dzialu,
- brak zakresu `basic/specialist`,
- brak statusu pytan: `all`, `unanswered`, `memorized`, `incorrect`, `correct`,
- brak `randomize_order`,
- brak strategii typu `topic_remaining`.

To oznacza, ze API nie umie jeszcze wystartowac tych samych sesji, ktore webowy `/nauka` potrafi wystartowac. Dla PWA webowej mozemy dalej uzywac obecnych tras, ale dla czystej aplikacji mobile trzeba to ujednolicic.

### 3. Rozjazd zapisu odpowiedzi

Web current answer:

- endpoint: `POST /nauka/teraz/odpowiedzi`,
- request field: `selected_answer`,
- request field: `answer_kind`,
- request field: `response_time_ms`,
- response zwraca `answer`, `session`, `progress`, `completed`, `nextQuestion`, `nextQuestionNumber`, `topicCompletionOverview`, `pjmCompletion`, `reviewCompletion`.

API answer:

- endpoint: `POST /api/v1/sessions/{studySession}/answers`,
- request field: `user_answer`,
- request field: `answer_kind`,
- request field: `response_time_ms`,
- response zwraca glownie `accepted`, `question_id`, `answer_kind`, `is_correct`, `answered_questions`, `session_status`,
- tylko `sr_review` dostaje bogatszy reveal, `daily_progress`, `next_question`, `next_question_number`, `review_completion`.

Wniosek: API answer dziala, ale nie ma tego samego kontraktu UX co aktywny player web. Dla mobile trzeba wybrac jeden jezyk payloadu. Proponuje API-style snake_case, ale semantyke z web current.

### 4. Payload pytania

`StudySessionApiPayloadBuilder::question()` zwraca:

- `id`,
- `category_id`,
- `external_id`,
- `question_text`,
- `question_type`,
- `difficulty`,
- `points`,
- `answers`,
- `media`,
- `explanation_asset`,
- `explanation_annotations`,
- `topic`,
- `selected_answer`,
- `answer_kind`,
- `is_answered`,
- `response_time_ms`,
- po reveal: `correct_answer`, `is_correct`, `explanation`.

To jest dobry kierunek dla mobile, bo jest prostszy niz Inertia props. Trzeba jednak dopisac brakujace elementy z playera:

- `current_question_number`,
- `question_pool_mode`,
- `question_batch_size`,
- `prefetched_questions` albo jasny endpoint batch,
- `exam_ui`,
- `session_filters`,
- `topic_completion_overview`,
- `pjm` assets, jesli PJM ma wejsc do mobile v1,
- audio pytan, jesli klasyczna nauka ma miec audio w mobile.

### 5. Dokumentacja API jest czesciowo nieaktualna

`docs/API-SPEC.md` opisuje MVP API, ale sa rozjazdy z kodem:

- przyklad `POST /api/v1/sessions` pokazuje `category_id` jako `"B"`, a kod oczekuje integer ID,
- przyklad odpowiedzi zawiera pola typu `score`, `max_score`, `passed`, a aktualny builder zwraca m.in. `correct_answers_count`, `score_percent`, `review_plan`,
- spec mowi o snapshotach `session_questions`, a aktualny manager trzyma kolejnosc w payloadzie `study_sessions`,
- spec nie opisuje `sr_review` reveal, `daily_progress`, `review_completion`,
- spec nie opisuje obecnych ograniczen filtrow i braku `current`.

Przed projektowaniem mobilki `API-SPEC.md` trzeba potraktowac jako szkic historyczny i zaktualizowac do prawdziwego kontraktu.

### 6. Testy API

Jest realne pokrycie w `tests/Feature/ApiSessionTest.php`:

- public health i kategorie,
- start i inspect learn session,
- brak reveal poprawnych odpowiedzi w egzaminie przed zakonczeniem,
- learn session wybiera caly matching topic set,
- zapis odpowiedzi i idempotentny drugi zapis,
- complete session,
- alias `review` -> `sr_review`,
- reveal dla Trenera pamieci po odpowiedzi,
- `unknown` tylko dla `sr_review`,
- quick mode cap do 10 pytan,
- cudza sesja -> forbidden.

Dodatkowo:

- `StudyCategoryLockTest` pokrywa lock kategorii w API,
- `ProductAccessGateTest` pokrywa brak aktywnego dostepu dla API product routes.

To jest mocny punkt. Przy dodaniu kontraktu mobile trzeba dopisac testy kontraktowe, zamiast projektowac tylko z frontendu.

### 7. Error contract

Obecnie API ma ogolne odpowiedzi typu:

- `FORBIDDEN`,
- `UNAUTHORIZED`,
- `CONFLICT`,
- `CSRF_TOKEN_MISMATCH`.

Dla mobile to za malo. Potrzebne sa semantyczne kody, np.:

- `EMAIL_VERIFICATION_REQUIRED`,
- `PASSWORD_CHANGE_REQUIRED`,
- `ACCOUNT_CLAIM_REQUIRED`,
- `PRODUCT_ACCESS_REQUIRED`,
- `CATEGORY_LOCKED`,
- `PJM_ACCESS_REQUIRED`,
- `SESSION_NOT_FOUND`,
- `SESSION_COMPLETED`,
- `QUESTION_NOT_CURRENT`,
- `SESSION_EXPIRED`.

Bez tego aplikacja bedzie zgadywala, czy pokazac logowanie, aktywacje dostepu, wybor kategorii, czy komunikat sesji.

### 8. Rekomendowany kontrakt mobile v1

Minimalny zestaw dla PWA/TWA:

| Endpoint | Cel |
| --- | --- |
| `GET /api/v1/me/learning-home` | bootstrap `/nauka`, `required_action`, dostep, kategoria, `active_session`, rekomendacje |
| `POST /api/v1/sessions` | start sesji z pelnymi filtrami web `/nauka` |
| `GET /api/v1/sessions/current` | resume aktywnej sesji |
| `GET /api/v1/sessions/current/questions` | batch pytan dla aktywnej sesji |
| `GET /api/v1/sessions/current/questions/{question}` | pojedyncze pytanie |
| `POST /api/v1/sessions/current/answers` | zapis odpowiedzi z `client_answer_id` |
| `POST /api/v1/sessions/current/complete` | zakonczenie aktywnej sesji |
| `POST /api/v1/sessions/current/exam-state` | synchronizacja egzaminu |

`GET /api/v1/me/learning-home` nie powinien byc pod `product.access`, bo ma powiedziec userowi, ze dostepu nie ma i jaka akcja jest wymagana. Endpointy faktycznej nauki moga zostac pod `product.access`, ale z lepszym error code.

### 9. Decyzje robocze po tym passie

- Mobile API jest przeskanowane wystarczajaco do projektowania.
- Obecne `/api/v1/sessions` nie jest jeszcze rownowazne webowemu `/nauka`.
- Nie trzeba wyrzucac API: trzeba je rozszerzyc wokol `current` i tych samych filtrow co web.
- `API-SPEC.md` trzeba zaktualizowac przed implementacja mobile kontraktu.
- Dla PWA v1 mozemy zostac na web/Inertia trasach, ale kontrakt API powinien powstac przed TWA/natywnym klientem.
- Naming requestow trzeba ujednolicic: `selected_answer` vs `user_answer` to niepotrzebny rozjazd.
- Dla offline-lite trzeba dodac `client_answer_id` albo `client_operation_id`.
- Kontrakt bledow jest tak samo wazny jak happy path.

## Czternasty pass: offline, cache i lokalne przechowywanie

Ten pass ustala, co realnie mozemy obiecac w PWA v1. Najwazniejszy wniosek: aplikacja nie ma jeszcze mechanizmu offline. Ma za to dobre podstawy do online-first PWA i bezpiecznego cache assetow.

### 1. Stan obecny

Nie znalazlem w kodzie zrodlowym:

- `navigator.serviceWorker`,
- `serviceWorker.register`,
- `indexedDB`,
- `caches.*`,
- `CacheStorage`,
- trwalego outboxa odpowiedzi,
- obslugi `navigator.onLine` dla playera.

Znalezione lokalne przechowywanie to glownie `localStorage` dla preferencji UI:

- zamkniety pasek zrodla publicznego,
- zamkniety CTA zaproszenia,
- widocznosc paneli/guide w aktywnej sesji,
- preferencje sesji: auto-advance, auto-play video/audio, predkosc video, tryb feedbacku, formatowanie,
- preferencje wyjasnien wizualnych.

Nie widze przechowywania pytan, odpowiedzi ani snapshotu sesji w IndexedDB. Kolejka odpowiedzi w `StudySessions/Show.vue` jest tylko w pamieci procesu przegladarki.

### 2. Server cache i sesja

Backend:

- Laravel cache default: `database`,
- Laravel session default: `database`,
- session lifetime: 120 minut,
- session cookie: `http_only`,
- SameSite: `lax`,
- secure cookie zalezy od `SESSION_SECURE_COOKIE`.

CSRF:

- `GET /auth/csrf-token` zwraca `Cache-Control: no-store, private`,
- frontend odpytuje go z `fetch(..., cache: 'no-store')`,
- po BFCache `pageshow` i powrocie z dlugiego backgroundu CSRF jest oznaczany do refreshu.

Static assets:

- nginx example dla glownej aplikacji daje `Cache-Control: public, max-age=604800, immutable` dla `css/js/jpg/jpeg/gif/png/webp/avif/svg/ico/woff/woff2`,
- media server daje `Cache-Control: public, max-age=2592000, immutable` i CORS dla mediow,
- Vite build ma hash-named assets, co pasuje do precache/cache-first.

Ryzyko: dynamiczne HTML/API nie maja jeszcze jawnej polityki PWA cache. Przy service workerze trzeba je swiadomie ustawic na `NetworkOnly` albo `NetworkFirst` bez zwracania starego stanu sesji.

### 3. Macierz cache/no-cache

| Zasob | Strategia PWA v1 | Uwagi |
| --- | --- | --- |
| `public/build/assets/*` | Precache / CacheFirst | hash-named, dobre do dlugiego cache |
| favicony i ikony PWA | Precache / CacheFirst | potrzebne do installability |
| manifest PWA | NetworkFirst albo krotki cache | musi szybko lapac zmiany ikon/start_url |
| `service-worker.js` | No-cache / update check | musi miec osobny nginx exception |
| `/offline.html` | Precache | prosty fallback bez danych usera |
| fonty self-hosted | Precache / CacheFirst | obecnie czesc fontow idzie z zewnatrz |
| obrazy publiczne strony | Runtime Cache z limitem | SEO/public content, bez danych usera |
| media pytan | Runtime Cache z limitem i TTL | nie precache calej bazy |
| PJM video | Runtime Cache z bardzo ostrym limitem | duze pliki, osobna decyzja produktowa |
| `/nauka` HTML | NetworkFirst albo NetworkOnly | zawiera stan usera, nie CacheFirst |
| `/nauka/teraz` HTML | NetworkOnly w v1 | aktywna sesja nie moze byc stara |
| `/api/v1/*` private | NetworkOnly w v1 | do czasu mobile contract i snapshotu |
| `/auth/csrf-token` | NetworkOnly/no-store | juz dobrze ustawione |
| `/login`, `/logout`, `/register` | NetworkOnly/no-store | bez cache formularzy auth |
| odpowiedzi kursanta | brak cache w v1 | dopiero IndexedDB outbox w offline-lite |
| wynik sesji | NetworkFirst, pozniej snapshot read-only | nie blokuje v1 |
| publiczne `/testy-na-prawo-jazdy` demo | NetworkFirst + static/runtime media | osobny produkt, nie core `/nauka` |

### 4. Service worker v1

Najbezpieczniejszy service worker na start:

- precache tylko app shell assets z Vite i ikony,
- offline fallback dla nawigacji, ale bez udawania, ze sesja nauki dziala offline,
- NetworkOnly dla endpointow auth/API i aktywnej sesji,
- Runtime Cache dla publicznych obrazow i mediow pytan z limitami,
- brak Background Sync odpowiedzi w v1,
- jawne czyszczenie/wersjonowanie cache przy zmianie service workera.

Nginx musi dostac wyjatki dla:

- `/service-worker.js`,
- `/manifest.webmanifest`,
- `/offline.html`,
- `/.well-known/assetlinks.json`.

Bez tego `immutable` dla statycznych rozszerzen moze utrudnic aktualizacje SW/manifestu.

### 5. Offline-lite jako etap pozniejszy

Offline-lite dla nauki wymaga osobnego projektu:

- IndexedDB store per user/session,
- snapshot sesji i pytan,
- `client_answer_id` albo `client_operation_id`,
- statusy sync: `pending`, `syncing`, `synced`, `conflict`, `failed`,
- retry z backoffem,
- konflikt wielu urzadzen,
- purge po logout/session expired,
- limit miejsca i reakcja na storage eviction,
- osobne UI: "odpowiedz czeka na synchronizacje".

Bez tego offline odpowiedzi beda pozorne i ryzykowne.

### 6. Test plan cache/offline

Minimalne scenariusze przed PWA release:

- instalacja PWA i launch z ikony bez internetu,
- wejscie `/nauka` bez internetu,
- wejscie `/nauka/teraz` bez internetu,
- odpowiedz w aktywnej sesji po utracie internetu,
- powrot z backgroundu po 15+ minutach,
- BFCache/back-forward po sesji nauki,
- wygasla sesja + service worker aktywny,
- update nowego builda Vite po publikacji,
- storage clear / storage pressure,
- media pytan na wolnym laczu.

### 7. Decyzje robocze po tym passie

- Offline/cache jest przeskanowane wystarczajaco do projektowania.
- PWA v1 ma byc online-first.
- Nie obiecujemy offline odpowiedzi w v1.
- Service worker powinien byc konserwatywny: assets tak, dane usera nie.
- `/nauka/teraz` w v1 powinno byc NetworkOnly.
- Media moga miec runtime cache, ale z limitami rozmiaru i TTL.
- `localStorage` zostawiamy dla preferencji UI, nie dla danych sesji.
- IndexedDB/outbox to osobny etap po mobile API contract.

## Pietnasty pass: media, assety i waga aplikacji

Ten pass oddziela dwa swiaty: lekkie assety aplikacji, ktore PWA moze precache'owac, oraz duze media kursowe, ktore trzeba traktowac jak strumieniowany katalog z limitami.

### 1. Kategorie assetow

Statyczny app shell:

- `public/build/assets` ma okolo 109 plikow i okolo 8.76 MB,
- assety Vite sa hash-named, wiec dobrze nadaja sie do precache/cache-first,
- `public/images` ma 48 plikow i okolo 8.86 MB,
- `public/audio` i `public/media` nie istnieja w lokalnym snapshocie.

Media kursowe:

- lokalny `storage/app/public` ma okolo 1325 plikow i okolo 613.47 MB,
- `config/media.php` dopuszcza obrazy `png/jpeg/jpg/webp/avif` i wideo `mp4`,
- limity uploadu sa ustawione na 8 MB dla obrazu i 25 MB dla wideo,
- `question_media` przechowuje `bytes`, `duration_seconds`, `width`, `height`, `variant`, `poster_path` i `metadata`,
- `question_sign_language_assets` przechowuje PJM video per `external_id` i rola: `question`, `answer_a`, `answer_b`, `answer_c`,
- `question_audio_assets` przechowuje audio pytania i poprawnej odpowiedzi z `asset_key`, `bytes`, `duration_seconds`, `encoding_format`, `status`,
- `question_explanation_assets` i powiazane assety znakow drogowych dostarczaja obrazy/assety do wyjasnien.

Wniosek: assety aplikacji sa male. Media kursowe nie sa male i nie moga wejsc do precache.

### 2. Storage i URL-e

Konfiguracja:

- `media_local` wskazuje domyslnie na `storage/app/public-media`,
- `MEDIA_LOCAL_URL` domyslnie buduje publiczny URL przez `/storage-bulk`,
- `r2` jest skonfigurowane jako dysk S3-compatible pod przyszle/produkcyjne media,
- `MediaUrlResolver` obsluguje absolutne URL-e, publiczne assety z `?v=filemtime`, `public_base_url` i standardowe `Storage::url()`,
- nginx dla serwera mediow daje `Cache-Control: public, max-age=2592000, immutable` i CORS.

To jest dobra baza pod CDN/R2, ale przed release trzeba potwierdzic realne produkcyjne naglowki dla `/storage-bulk` albo R2/CDN.

### 3. Payload mediow

`QuestionMediaPayloadBuilder` zwraca dla obrazu/wideo:

- `kind`,
- `url`,
- `full_url`,
- `thumb_url`,
- `poster_url`,
- `mime_type`,
- `bytes`,
- `width`,
- `height`,
- `duration_seconds`,
- `variant`.

`QuestionAudioPayloadBuilder` zwraca:

- audio pytania,
- audio poprawnej odpowiedzi,
- `duration_seconds`,
- `encoding_format`,
- `transcript`,
- `asset_key`.

`PjmSignLanguageAssetPayloadBuilder` zwraca aktywne assety PJM z rola, URL-em, rozmiarem, czasem trwania, wymiarami, wariantem i statusem przetwarzania.

To oznacza, ze PWA moze podejmowac decyzje cache na podstawie danych z backendu, a nie tylko po URL-u.

### 4. Zachowanie frontendu

W aktywnej nauce `StudySessions/Show.vue`:

- `primeQuestionMedia()` prefetchuje obrazy i postery wideo przez `new Image()`,
- nie prefetchuje pelnych filmow,
- glowne wideo pytania ma `preload="none"` i laduje sie dopiero po aktywacji,
- poster wideo jest pokazywany jako lekki stan startowy,
- audio pytania w `QuestionAudioControl.vue` ma `preload="none"` i ustawia `src` dopiero przy odtworzeniu/autoplay,
- PJM w `PjmVideoBlock.vue` ma `preload="metadata"`,
- egzamin `Exam.vue` uzywa wideo z `autoplay muted preload="metadata"`, bo timer egzaminacyjny zalezy od faz mediow.

To jest sensowne pod mobile: obecny web nie probuje pobierac calego katalogu mediow. Ryzykiem zostaja PJM i egzamin, bo tam metadata/autoplay moze inicjowac wiekszy ruch niz w zwyklej nauce.

### 5. Polityka cache mediow dla PWA

Rekomendacja v1:

| Typ | Strategia |
| --- | --- |
| Vite app shell | Precache / CacheFirst |
| ikony i manifest | Precache + krotki update manifestu |
| obrazy pytan | Runtime Cache z limitem |
| postery wideo | Runtime Cache z limitem |
| pelne wideo pytan | bez cache w v1 albo bardzo niski opt-in limit |
| PJM wideo | bez cache w v1 albo osobny limit i osobna decyzja produktowa |
| audio pytan | Runtime Cache dopiero po decyzji audio w mobile v1 |
| assety wyjasnien | Runtime Cache z limitem |

Proponowane pierwsze limity do dyskusji:

- obrazy pytan i wyjasnien: 50-100 MB,
- postery wideo: 20-50 MB,
- audio pytan: 50-100 MB, jesli audio wchodzi do v1,
- pelne wideo pytan: 0 MB w v1 albo maksymalnie 150-250 MB po opt-in,
- PJM wideo: 0 MB w v1 albo osobny limit po decyzji, czy PJM ma wejsc do pierwszej aplikacji.

Limity trzeba dobrac po produkcyjnej inwentaryzacji, bo lokalne 613 MB w `storage/app/public` pokazuje tylko skale, nie finalny rozklad plikow.

### 6. Ryzyka mediow

| Ryzyko | Znaczenie |
| --- | --- |
| Zbyt szeroki precache mediow | PWA szybko zjada miejsce na telefonie |
| Cache pelnych MP4 | Service worker moze gorzej obslugiwac range requests i duze pliki |
| PJM jako osobny katalog wideo | mocno zwieksza transfer i storage |
| Publiczne URL-e mediow | premium nie moze opierac sie na tajnosci adresu pliku |
| Brak produkcyjnego media budget | trudno dobrac limity cache i wymagania CDN |
| Brak weryfikacji R2/CDN headers | cache moze dzialac inaczej niz lokalny nginx |
| Duration video w egzaminie | brak/blad duration wplywa na flow egzaminu |

### 7. Decyzje robocze po tym passie

- Media i assety sa przeskanowane wystarczajaco do projektowania PWA.
- Precache robimy tylko dla app shell, ikon, fontow self-hosted i ewentualnego offline fallback.
- Media kursowe ida tylko w runtime cache z limitami, nie w precache.
- Pelne wideo i PJM nie powinny byc cachowane w PWA v1 bez osobnej decyzji.
- Payload mediow ma metadane potrzebne do inteligentnego cache: `bytes`, `duration_seconds`, `variant`.
- Przed release trzeba zrobic produkcyjna inwentaryzacje mediow i potwierdzic naglowki R2/CDN.

## Szesnasty pass: platnosci, dostep premium i Google Play policy

Ten pass odpowiada na pytanie, czy obecny checkout i paywall mozna po prostu pokazac w aplikacji z Google Play. Wniosek: technicznie mamy fundament dostepu i zamowien, ale sklepowy wariant mobilny powinien na start unikac sprzedazy w aplikacji, jesli nie wdrazamy pelnego billingowego compliance.

### 1. Aktualny model dostepu w kodzie

Pelny dostep do `/nauka` rozstrzyga `ProductAccessResolver`.

Kolejnosc zrodel pelnego dostepu:

- blokada konta zawsze wygrywa,
- konto tymczasowe nieprzejete blokuje dostep,
- wymuszona zmiana hasla blokuje dostep,
- `system`,
- `purchase`,
- `moderator_grant`,
- `invitation_guest`,
- inaczej `missing_access`.

Oddzielnie istnieje `PjmFreeAccessResolver`, ktory pozwala na bezplatny modul PJM, jesli:

- konto nie jest zablokowane,
- konto nie jest tymczasowe nieprzejete,
- user nie musi zmienic hasla,
- email jest zweryfikowany,
- profil ma `preferred_learning_track = pjm`,
- kategoria ma aktywne assety PJM.

To oznacza, ze aplikacja mobilna musi rozrozniac co najmniej trzy stany:

- pelny dostep premium,
- darmowy starter PJM,
- brak dostepu.

### 2. Paywall i checkout

Trasy checkoutu sa pod `auth` + `verified`:

- `POST /checkout/plany/{plan}`,
- `GET /checkout/{order}`,
- `POST /checkout/{order}/sandbox/potwierdz`,
- `POST /checkout/{order}/anuluj`,
- `GET /checkout/{order}/sukces`.

Modele:

- `ProductPlan`,
- `PurchaseOrder`,
- `ProductAccessGrant`.

Plany sa czasowe, nie subskrypcyjne:

| Kod | Cena | Dostep |
| --- | --- | --- |
| `start-30` | 39.00 PLN | 30 dni |
| `start-90` | 69.00 PLN | 90 dni |
| `start-365` | 149.00 PLN | 365 dni |

`ProductCheckoutService::markPaid()` aktywuje dostep dopiero po serwerowym potwierdzeniu platnosci. To jest poprawny fundament: samo wejscie na checkout nie nadaje dostepu, a drugie potwierdzenie tej samej platnosci nie tworzy kolejnego grantu.

Obecny provider:

- `config/payments.php` ma `default_provider` z env `PAYMENT_PROVIDER`, domyslnie `sandbox`,
- `sandbox_enabled` jest wlaczony poza produkcja,
- nie widze integracji z realnym operatorem typu Stripe/PayU/Tpay/Przelewy24,
- nie widze Google Play Billing, Android Billing Client, TWA billing bridge ani backendowej obslugi purchase tokenow Google.

### 3. Miejsca, ktore sa ryzykowne dla aplikacji z Play

Obecne UI sprzedazowe:

- publiczny `/cennik` pokazuje plany i formularz `checkout.store`,
- `/aktywuj-dostep` linkuje do cennika i kontaktu,
- `Checkout/Show.vue` pokazuje zamowienie i sandbox/final provider state,
- `Checkout/Result.vue` pokazuje sukces i CTA do nauki,
- `AuthPricingBackdrop.vue` ma hardcoded plans/ceny jako tlo auth.

W zwyklym webie to ma sens. W aplikacji z Google Play to trzeba rozdzielic, bo link lub button do zewnetrznej platnosci za cyfrowy dostep w appce moze naruszyc polityke platnosci, jezeli nie jestesmy w formalnym programie alternatywnego billing/linkowania.

### 4. Aktualne zasady Google Play sprawdzone 2026-07-06

Oficjalne zrodla:

- [Google Play Payments policy](https://support.google.com/googleplay/android-developer/answer/9858738?hl=en),
- [Understanding Google Play Payments policy](https://support.google.com/googleplay/android-developer/answer/10281818?hl=en),
- [External content links program for users in the US](https://support.google.com/googleplay/android-developer/answer/16470497?hl=en),
- [US policy update](https://support.google.com/googleplay/android-developer/answer/15582165?hl=en),
- [Alternative billing for EEA](https://support.google.com/googleplay/android-developer/answer/12348241?hl=en).

Wnioski z polityk:

- aplikacje dystrybuowane przez Play, ktore sprzedaja dostep do cyfrowych funkcji/tresci w aplikacji, zasadniczo musza uzyc Google Play Billing, chyba ze kwalifikuja sie do wyjatku albo formalnego programu alternatywnego,
- edukacyjny cyfrowy dostep/subskrypcja/funkcje premium sa typem produktu, ktory zwykle wpada w te zasady,
- consumption-only jest dopuszczalne: user moze zalogowac sie i korzystac z tresci kupionej poza aplikacja, o ile w aplikacji nie dajemy zakupu ani linkowania/CTA do alternatywnej platnosci,
- poza aplikacja mozna komunikowac oferty innymi kanalami,
- EEA ma programy alternatywnego billing/linkowania, ale wymagaja rejestracji, spelnienia wymogow, integracji API i raportowania,
- USA ma osobny program external content links, rowniez wymagajacy zapisania aplikacji i spelnienia warunkow,
- wymogi i programy moga sie zmieniac, wiec przed publikacja trzeba zrobic finalny policy check.

Nie jest to porada prawna. To techniczno-produktowy odczyt oficjalnych zasad na potrzeby architektury PWA/TWA.

### 5. Rekomendowane warianty dla nas

| Wariant | Co widzi aplikacja Play | Plusy | Minusy |
| --- | --- | --- | --- |
| A. Consumption-only | login, `/nauka`, status dostepu, brak cennika/checkoutu/linkow do platnosci | najprostsze i najczystsze na start | zakup trzeba robic poza appka |
| B. Play Billing | appka sprzedaje dostep przez Google Play Billing | zgodne z klasycznym modelem Play | osobna integracja Android/backend, mapowanie produktow, tokeny, refundy |
| C. EEA/US alternative billing/linking | appka moze uzyc formalnego programu alternatywnego | moze zachowac czesc webowego modelu | onboarding, API, raportowanie, regiony, policy risk |
| D. Tylko web PWA poza Play | webowy checkout bez Play Store | najprostsze biznesowo | brak dystrybucji w Google Play |

Moja rekomendacja na projekt PWA/TWA v1:

1. Web PWA poza sklepem moze zachowac `/cennik` i checkout webowy.
2. Wariant Google Play/TWA powinien byc consumption-only.
3. W aplikacji z Play pokazujemy status: aktywny dostep, PJM starter, brak dostepu.
4. Dla braku dostepu pokazujemy neutralna informacje i kontakt/support, bez linku/buttona do zakupu, chyba ze wybierzemy formalny billing/linking program.
5. Play Billing albo alternatywny billing traktujemy jako oddzielny projekt po stabilizacji PWA core.

### 6. Co trzeba przygotowac w backendzie/mobile contract

Potrzebny jest jawny endpoint bootstrap, np. `GET /api/v1/me/learning-home`, z polami:

- `required_action`,
- `full_product_access.allowed`,
- `full_product_access.source`,
- `full_product_access.expires_at`,
- `pjm_access.allowed`,
- `pjm_access.reason`,
- `purchase_mode`, np. `hidden`, `web`, `play_billing`, `external_program`,
- `support_url`,
- `can_show_pricing_link`.

Dla web/Inertia mozemy nadal renderowac cennik. Dla TWA/Play endpoint lub server props powinny umiec ukryc:

- `/cennik` CTA,
- `checkout.store`,
- link "Przejdz do cennika" na `/aktywuj-dostep`,
- hardcoded pricing backdrop w auth,
- komunikaty "platnosc" sugerujace zakup w aplikacji.

### 7. Testy i compliance przed publikacja

Minimum test plan:

- user bez dostepu w web PWA widzi cennik i moze przejsc do checkoutu,
- user bez dostepu w Play/TWA nie widzi linku do zewnetrznej platnosci,
- user z zakupem webowym moze zalogowac sie w Play/TWA i korzystac z `/nauka`,
- user z `invitation_guest` dziala jak pelny dostep w nauce,
- user PJM free widzi tylko starter PJM, nie pelny produkt,
- wygasly/refundowany grant zamyka dostep,
- Play review ma testowe konto z opisanym dostepem w Play Console App Access,
- brak zakupu in-app nie blokuje reviewerowi sprawdzenia funkcji.

### 8. Decyzje robocze po tym passie

- Dostep premium i platnosci sa przeskanowane wystarczajaco do projektowania PWA/TWA.
- Obecny checkout jest fundamentem webowym/pre-gateway, nie gotowym sklepem mobilnym.
- Do Google Play v1 rekomenduje consumption-only.
- Sprzedaz w aplikacji przez Play Billing lub alternatywny billing to osobny projekt.
- Mobile bootstrap musi zwracac nie tylko `allowed`, ale tez polityke pokazywania zakupu: `purchase_mode` / `can_show_pricing_link`.
- `/aktywuj-dostep` musi miec wariant Play/TWA bez linku do webowego checkoutu.

## Siedemnasty pass: Android/TWA release path

Ten pass sprawdza, czy mamy juz material pod publikacje w Google Play jako TWA. Wniosek: nie mamy jeszcze Android wrappera. To dobrze porzadkuje kolejnosc: najpierw robimy PWA foundation, potem generujemy TWA, potem dopiero sklep.

### 1. Stan repo

Nie znalazlem:

- katalogu `android`,
- `AndroidManifest.xml`,
- `build.gradle` / `settings.gradle`,
- Bubblewrap config,
- Capacitor/Cordova/Expo/Flutter/React Native projektu,
- `public/.well-known/assetlinks.json`,
- `manifest.webmanifest`,
- `service-worker.js`.

`package.json` ma tylko webowy stack Vite/Vue/Inertia i testy JS. Nie ma zaleznosci typu `@bubblewrap/cli`, `vite-plugin-pwa`, Android Billing Client ani narzedzi mobilnych.

### 2. Oficjalne zrodla sprawdzone 2026-07-06

- [Trusted Web Activities Quick Start Guide](https://developer.android.com/develop/ui/views/layout/webapps/guide-trusted-web-activities-version2),
- [Trusted Web Activity overview](https://developer.chrome.com/docs/android/trusted-web-activity),
- [Android concepts for web developers: Digital Asset Links](https://developer.chrome.com/docs/android/trusted-web-activity/android-for-web-devs),
- [Google Digital Asset Links overview](https://developers.google.com/digital-asset-links/v1/getting-started),
- [Android App Bundles](https://developer.android.com/guide/app-bundle),
- [Play App Signing](https://developer.android.com/studio/publish/app-signing),
- [TWA Play Billing via Digital Goods API](https://developer.chrome.com/docs/android/trusted-web-activity/receive-payments-play-billing).

Wnioski z dokumentacji:

- TWA otwiera web app w pelnym ekranie przez przegladarke uzytkownika, bez UI przegladarki, tylko dla zweryfikowanych domen,
- weryfikacja opiera sie o Digital Asset Links,
- jesli weryfikacja sie nie uda, aplikacja spada do Custom Tab z paskiem przegladarki,
- Bubblewrap generuje projekt Android na podstawie web manifestu, wiec manifest PWA jest warunkiem startowym,
- nowe aplikacje w Google Play powinny byc publikowane jako Android App Bundle (`.aab`),
- Play App Signing rozdziela app signing key i upload key,
- dla TWA `assetlinks.json` musi pasowac do certyfikatu, ktory podpisuje aplikacje na urzadzeniu; przy dystrybucji przez Play bedzie to Play app signing certificate, niekoniecznie lokalny/upload certificate,
- jesli aplikacja ma sprzedawac cyfrowy dostep w Play, TWA moze isc w Play Billing przez Payment Request API + Digital Goods API, ale to osobny projekt.

### 3. Kolejnosc prac dla TWA

Rekomendowana kolejnosc:

1. Domknac PWA installability: `manifest.webmanifest`, ikony, `start_url`, `display`, `theme_color`, service worker.
2. Ustalic produkcyjny host: `https://prawkonaraz.pl` jako glowny origin TWA.
3. Ustalic package name, np. `pl.prawkonaraz.app` albo `pl.prawkonaraz.nauka`.
4. Wygenerowac Android wrapper przez Bubblewrap po gotowym manifescie.
5. Zbudowac i przetestowac local signed APK/AAB.
6. Skonfigurowac Play App Signing.
7. Pobrac Play app signing certificate fingerprint z Play Console.
8. Wystawic `/.well-known/assetlinks.json` z release package name i SHA-256 fingerprint.
9. Zweryfikowac, ze TWA startuje bez paska Custom Tab.
10. Przygotowac internal testing track w Google Play.
11. Dopiero potem produkcyjny release.

### 4. Pliki, ktore trzeba bedzie dodac

Po stronie web/Laravel:

- `public/manifest.webmanifest`,
- `public/service-worker.js` albo generowany SW,
- `public/offline.html`,
- ikony PWA: 192, 512, maskable, adaptive-friendly,
- `public/.well-known/assetlinks.json`,
- wyjatki cache dla `service-worker.js`, `manifest.webmanifest`, `offline.html`, `assetlinks.json`,
- meta `theme-color`, `apple-mobile-web-app-capable`, `mobile-web-app-capable` w layoutach.

Po stronie Android:

- katalog Android/Bubblewrap, np. `android/` albo `twa/`,
- `AndroidManifest.xml`,
- Gradle config,
- package id,
- versionCode/versionName,
- signing config dla upload key,
- build workflow dla `.aab`,
- dokumentacja release runbook.

### 5. Start URL i scope

Wczesniejszy skan wskazuje:

- `start_url`: `/nauka`,
- `scope`: `/`,
- `display`: `standalone` dla PWA, TWA i tak bedzie pelnoekranowa po weryfikacji,
- nie startujemy z `/nauka/teraz`, bo aktywna sesja wymaga kontrolowanego resume,
- `/dashboard` zostaje routerem po auth, ale ikona aplikacji powinna prowadzic do huba nauki.

### 6. Originy i linki zewnetrzne

TWA fullscreen dotyczy zweryfikowanych originow. Dla nas:

- core: `https://prawkonaraz.pl`,
- `www.prawkonaraz.pl` przekierowuje na apex, wiec package powinien celowac w apex,
- media przez `/storage-bulk` na tym samym hostcie nie wymagaja osobnego originu,
- jesli R2/CDN dostanie osobna domene i bedzie uzywana tylko jako media resource, nie musi byc dodatkowym originem nawigacji,
- jesli checkout albo auth mialby prowadzic na inny host, pojawi sie Custom Tab UI lub potrzeba multi-origin DAL,
- dla rekomendowanego wariantu consumption-only nie chcemy checkoutu w TWA v1.

### 7. Release checklist TWA

Minimalna checklist przed internal testing:

- manifest PWA przechodzi Lighthouse installability,
- service worker aktywny i nie cachuje prywatnych danych,
- `/.well-known/assetlinks.json` publicznie dostepny z `Content-Type: application/json`,
- Digital Asset Links pasuja do release certificate z Play App Signing,
- TWA odpala sie bez browser bar,
- Android Back contract dziala w playerze,
- login/register/forgot password dzialaja w TWA,
- powrot z backgroundu odswieza CSRF/sesje,
- brak webowego checkoutu/linkow do platnosci w wariancie Play,
- app review ma testowe konto i instrukcje dostepu,
- AAB zbudowany, podpisany upload key i wrzucony na internal testing.

### 8. Ryzyka

| Ryzyko | Znaczenie |
| --- | --- |
| Brak PWA manifestu | Bubblewrap nie ma stabilnego zrodla konfiguracji |
| Zly SHA-256 w `assetlinks.json` | TWA spadnie do Custom Tab z paskiem przegladarki |
| Pomylenie upload key z app signing key | dziala lokalnie, psuje sie po instalacji z Play |
| Checkout w Play/TWA | ryzyko policy rejection |
| Brak Android Back contract | appka bedzie sprawiac wrazenie webview bez kontroli |
| Zewnetrzne originy | wyjscie do Custom Tab UI albo potrzeba multi-origin DAL |
| Brak internal testing | ryzyko dopiero na review produkcyjnym |

### 9. Decyzje robocze po tym passie

- Android/TWA release path jest przeskanowany wystarczajaco do projektowania.
- TWA robimy po PWA foundation, nie rownolegle jako pierwszy krok.
- Pierwszy wrapper powinien byc Bubblewrap/Android Browser Helper, nie natywna aplikacja pisana od zera.
- `assetlinks.json` dopiero po ustaleniu package name i certyfikatu Play App Signing.
- Google Play v1 laczymy z decyzja consumption-only z poprzedniego passu.
- Przed implementacja trzeba przygotowac osobny release runbook TWA.

## Osiemnasty pass: UX/design system mobile

Ten pass porzadkuje, co realnie mamy jako mobile UX, a co jest jeszcze blueprintem. Wniosek: nie piszemy aplikacji od nowa, bo mobilny kierunek jest juz w kodzie. Trzeba jednak ujednolicic warstwe app shell i usunac dane udajace prawdziwe statystyki.

### 1. Co juz istnieje

Najwazniejsze elementy mobile:

- `resources/js/Pages/Session/Partials/MobileLearningDashboard.vue` - realnie wpiety mobile dashboard `/nauka`.
- `resources/js/Pages/StudySessions/Show.vue` - aktywny player nauki z mobile bottom dock, safe-area i bottom sheetem.
- `resources/js/Pages/StudySessions/Exam.vue` - osobny mobile shell egzaminu z timerami i bottom dockiem.
- `resources/js/Pages/ReviewQueue/Index.vue` - osobny mobile shell trenera pamieci.
- `resources/js/Pages/Session/Ranking.vue` - osobny mobile layout rankingu.
- `resources/js/Pages/TrafficSignLearning/Show.vue` - trening znakow z lokalna kolejka sync i flip-card UI.
- `resources/js/Pages/Session/PjmIndex.vue` - responsywny ekran PJM.
- `resources/js/Components/MobileAppBar.vue`, `MobileBottomAction.vue`, `MobileProgressRing.vue` - wspolne komponenty mobilne, ale nie sa jeszcze szeroko uzywane.

### 2. Najmocniejsze strony obecnego UX

- `/nauka` ma osobny mobile dashboard, a nie tylko scisniety desktop.
- Aktywny player ma mobile bottom dock, dynamiczny spacer, `ResizeObserver` i safe-area.
- Egzamin ma osobny mobile header i synchronizuje prawde czasu z backendem.
- Trener pamieci ma gotowy proof of concept aplikacyjnego ekranu mobile z `100svh` i safe-area.
- Ranking ma osobny mobile layout zamiast tabeli desktopowej.
- Znaki drogowe maja lokalna kolejke batch sync i mobilnie akceptowalny flow pytan.
- W wielu miejscach jest `prefers-reduced-motion`, czyli podstawa accessibility jest juz obecna.

### 3. Najwieksza niespojnosc

Mobile UX jest dzisiaj rozproszony. Kazdy ekran ma wlasny shell:

| Ekran | Dzisiejszy shell | Ocena |
| --- | --- | --- |
| `/nauka` | `MobileLearningDashboard` | dobry start, ale ma placeholdery i wlasny sheet |
| `/nauka/teraz` learn/zen/PJM/review | custom shell w `Show.vue` | nie przepisywac, ale potrzebuje Android Back i QA |
| `/nauka/teraz` exam | custom shell w `Exam.vue` | dobry, backend-driven, wymaga background QA |
| `/trener-pamieci` | lokalny `memory-mobile-shell` | mocny POC, ale nie uzywa wspolnych komponentow |
| `/nauka/ranking` | lokalny mobile ranking | dobry wizualnie, ale oddzielny od app shell |
| `/nauka/znaki-drogowe` | responsive desktop-first | dziala, ale mniej app-like niz dashboard/trener |
| `/nauka/pjm` | responsive, spokojny layout | dziala, ale jeszcze nie ma osobnego app shell |
| globalny auth/header | `SiteHeader` + drawery | dobry webowo, ale wymaga wariantu Play/TWA bez pricing CTA |

To znaczy, ze mamy material do wykorzystania, ale nie mamy jeszcze jednego design systemu aplikacji mobilnej.

### 4. Placeholdery i dane do wyczyszczenia

Przed PWA/TWA nie mozemy zostawic w UI danych, ktore wygladaja jak prawdziwe statystyki kursanta, jesli sa wpisane na stale.

W `MobileLearningDashboard.vue` sa nadal:

- `weeklyProgressBars` jako statyczna tablica,
- `weeklyProgressLabels` jako statyczna tablica,
- `--` jako czas nauki,
- sekcja "Ostatnia aktywnosc" z twarda godzina,
- wynik ostatniej aktywnosci `85%`.

Dodatkowo w desktopowych fragmentach `Session/Index.vue` sa placeholdery sredniego czasu typu `--` / `--:--`.

Decyzja: przed publikacja PWA/TWA te elementy trzeba albo podpiac do backendu (`learning_dashboard`, `recent_learning_activity`, `weekly_activity`, `study_time`), albo ukryc.

### 5. Shared components

`MobileAppBar`, `MobileBottomAction` i `MobileProgressRing` sa dobrym kierunkiem, ale aktualnie nie sa produkcyjnym standardem:

- `MobileAppBar` nie jest uzywany szeroko przez ekrany nauki.
- `MobileBottomAction` jest tylko przyciskiem/linkiem i nie zalatwia wrappera safe-area.
- `MobileProgressRing` jest gotowy jako atom, ale trener pamieci ma wlasny ring.
- Dokumenty mobile sugeruja wieksza standaryzacje niz realny aktualny kod.

Wniosek: nie nalezy na sile przepisywac playera na male komponenty. Lepiej zdefiniowac wspolne zasady i dopiero potem wyciagac komponenty tam, gdzie to zmniejsza ryzyko.

### 6. Proponowany mobile app shell

Minimalny shell PWA/TWA powinien opisac:

- app bar: tytul, back, kategoria, status sesji,
- bottom action/dock: safe-area, stale wysokosci, loading/error,
- bottom sheet: backdrop, focus, scroll, max height, Android Back,
- status offline/sync/session expired,
- wariant `standalone` i wariant zwykly web,
- zasady ukrywania publicznego headera/footer w core `/nauka`,
- zasady dla checkout/pricing w wariancie Google Play.

Nie musi to oznaczac jednego komponentu dla wszystkiego. Aktywny player i egzamin moga zachowac specjalistyczny shell, ale powinny respektowac ten sam kontrakt zachowan.

### 7. Android Back contract

Nie znalazlem spojnej obslugi `popstate` dla bottom sheetow i paneli. Dla TWA to krytyczne.

Proponowany kontrakt:

1. Jesli otwarty jest bottom sheet/menu, Android Back zamyka sheet.
2. Jesli otwarty jest panel ustawien/wyboru dzialu, Back zamyka panel.
3. Jesli user jest w aktywnej sesji, Back prowadzi do `/nauka` albo pokazuje potwierdzenie.
4. Jesli user jest na `/nauka`, Back moze wyjsc z aplikacji.
5. Egzamin powinien miec ostrozniejszy wariant, bo przypadkowe wyjscie ma konsekwencje czasowe.

### 8. Accessibility i mobile polish

Przed projektem trzeba sprawdzic:

- tap targety min. okolo 44 px,
- fokus w bottom sheetach i modalach,
- zamykanie Escape/Back/backdrop,
- `aria-label` dla ikon i samych symboli,
- reduced motion,
- brak overlapu tekstu na 360 px,
- safe-area top/bottom w standalone,
- kontrast w niebieskich i czerwonych CTA,
- scroll lock bez ucietej tresci na niskich ekranach.

### 9. Decyzje robocze po tym passie

- UX/design system mobile jest przeskanowany wystarczajaco do projektowania.
- Obecny mobile blueprint wykorzystujemy; nie piszemy aplikacji od nowa.
- Najpierw definiujemy app shell contract, potem dopiero wyciagamy komponenty.
- `MobileLearningDashboard` jest kandydatem na home PWA, ale wymaga usuniecia placeholderow.
- Aktywny player zostaje specjalistycznym ekranem, nie wymuszamy na nim generycznego layoutu.
- Android Back contract jest wymogiem przed TWA.
- `SiteHeader` i publiczne CTA musza miec wariant dla PWA/TWA/Play.
- Znaki, ranking i PJM sa do wykorzystania, ale wymagaja mobile QA i decyzji, czy wchodza do v1.

## Dziewietnasty pass: QA, monitoring i wydajnosc

Ten pass sprawdza, czy mamy jakosciowa bramke pod PWA/TWA. Wniosek: backend i domena nauki maja dobre pokrycie testami oraz narzedzia operacyjne. Brakuje jeszcze testow i monitoringu specyficznych dla PWA, service workera, mobile viewportow i klienta w przegladarce.

### 1. Istniejace testy backendowe

Repo ma szeroki zestaw Pest/PHPUnit:

- `tests/Feature/SessionPageTest.php`,
- `tests/Feature/StudySessionFlowTest.php`,
- `tests/Feature/ApiSessionTest.php`,
- `tests/Feature/ProductAccessGateTest.php`,
- `tests/Feature/ProductAccessResolverTest.php`,
- `tests/Feature/Security/CsrfSessionRecoveryTest.php`,
- `tests/Feature/PjmStudySessionTest.php`,
- `tests/Feature/ReviewQueueTest.php`,
- `tests/Feature/ReviewTrainerTelemetryTest.php`,
- `tests/Feature/TrafficSignLearningTest.php`,
- testy rankingu, health, backupow, smoke, performance, importow i admina.

To jest mocna baza dla zmian w backendzie. Najwazniejsze dla PWA/mobile: sesja, zapis odpowiedzi, CSRF, API, product access, PJM, trener pamieci i znaki juz maja testy.

### 2. Testy frontendu

`npm run test:unit` uruchamia Vitest. Istniejace testy JS pokrywaja m.in.:

- `apiClient`,
- `csrfSession`,
- `useSessionExpiry`,
- `useSafeLogout`,
- media/question source helpers,
- adnotacje i geometrie adnotacji,
- ranking realtime clients, reducers, polling i clocks,
- helpery wyjasnien sesji.

Brakuje natomiast testow komponentowych/visual dla:

- `MobileLearningDashboard`,
- bottom sheetow,
- mobile player dock,
- Android Back/popstate,
- offline banner/fallback,
- PWA standalone mode.

### 3. E2E i smoke

W `package.json` sa:

- `npm run e2e:smoke`,
- `npm run stress:session`,
- `npm run build`,
- `npm run test:unit`.

`scripts/e2e-smoke.mjs`:

- bootstrapuje dane smoke,
- uruchamia lokalny serwer,
- loguje usera,
- startuje sesje,
- przechodzi pytania,
- sprawdza obraz i wideo,
- robi screenshot i raport JSON.

`scripts/session-stress.mjs`:

- loguje usera,
- przechodzi kategorie, dzialy i statusy,
- mierzy przejscia,
- zapisuje raport JSON/MD.

To jest dobre jako smoke/stress dla desktopowego flow, ale nie jest jeszcze mobile PWA matrix.

### 4. Monitoring i operacje

Istniejace mechanizmy:

- `GET /api/v1/health`,
- `HealthCheckService` sprawdza baze i backup,
- `AssignRequestId` dodaje `X-Request-Id` i kontekst logow,
- `ApiErrorResponseFactory` zwraca `request_id` i `server_time`,
- globalny exception handler loguje CSRF mismatch z kontekstem,
- `MonitoringSnapshotService` zapisuje dzienne snapshoty licznikow,
- `PerformanceSmokeService` mierzy start sesji, pierwsza odpowiedz, finalizacje i dashboard,
- komendy `ops:health-report`, `ops:perf-smoke`, `ops:smoke-test`, `ops:capture-monitoring-snapshot`,
- scheduler odpala backup, assert backup fresh, agregaty, monitoring snapshot, prune historii, sitemapy i maintenance.

To jest dobre dla backendu i operacji. Dla PWA brakuje jednak warstwy client-side:

- brak Sentry/Bugsnag/Rollbar albo odpowiednika dla bledow JS,
- brak monitoringu service workera,
- brak telemetry dla installability/offline fallback,
- brak Web Vitals/RUM,
- brak dashboardu mobile-only bledow 401/419/offline/network timeout,
- brak korelacji `X-Request-Id` z bledem pokazanym w UI.

### 5. Wydajnosc

Plusy:

- Vite assets sa hashowane i code-splitowane.
- App shell w `public/build/assets` jest relatywnie maly wobec mediow kursowych.
- Media nie sa masowo prefetchowane.
- `PerformanceSmokeService` mierzy najwazniejsze domenowe operacje backendowe.
- `session-stress` moze wykryc wolne przejscia przez pytania.

Ryzyka:

- `/nauka` po dodaniu `learning_dashboard` moze stac sie ciezkim dashboardem.
- `StudySessions/Show.vue` jest bardzo duzy i kazda zmiana shellu moze odbic sie na bundle/TTI.
- Media katalogu kursowego sa duze, wiec cache bez limitow zabije storage na Androidzie.
- Zewnetrzne fonty pogarszaja przewidywalnosc app shell i offline fallback.
- Ranking realtime i SSE/WebSocket powinny zostac poza cache service workera.

### 6. Minimalny PWA quality gate

Przed pierwszym PWA release rekomenduje wymagac:

- `composer test` albo wybrany pakiet testow krytycznych,
- `npm run test:unit`,
- `npm run build`,
- `npm run e2e:smoke`,
- Playwright mobile smoke dla 360x740, 390x844, 430x932, 768x1024,
- Lighthouse PWA dla `/nauka` po zalogowaniu i publicznej strony startowej,
- test offline fallbacku,
- test, ze SW nie cache'uje `/nauka`, `/nauka/teraz`, `/api/v1/*`, `/auth/csrf-token`,
- test update'u nowego builda Vite po aktywnym SW,
- test background > 15 minut i odpowiedz po powrocie,
- test Android Back dla bottom sheetow i aktywnej sesji.

### 7. Mobile E2E matrix

Minimalne scenariusze:

| Flow | Viewporty | Co sprawdzic |
| --- | --- | --- |
| launch `/nauka` jako guest | 390x844 | login redirect i powrot po auth |
| `/nauka` home | 360/390/430 | brak overlapu, quick actions, active session tile |
| start klasycznej nauki | 360/390/430 | wybor dzialu/statusu, przejscie do `/nauka/teraz` |
| aktywna sesja learn | 360/390/430 | answer dock, media, reveal, next, complete |
| egzamin | 390/430 | timer, background resume, wynik |
| trener pamieci | 360/390 | ring, CTA, plan, session expired |
| znaki drogowe | 390/430 | batch sync, blad sieci, result |
| ranking | 390/430 | online-only state, SSE/polling fallback |
| standalone/TWA | real Android | safe-area, Back, no browser bar |

### 8. Monitoring PWA po wdrozeniu

Do dodania przed TWA albo razem z nim:

- client-side error reporting dla JS i promise rejection,
- tagowanie bledow `route`, `display_mode`, `viewport`, `service_worker_state`,
- raportowanie PWA lifecycle: installed/standalone/opened from icon,
- raportowanie offline fallback views,
- raportowanie failed fetch dla `/nauka/teraz` i odpowiedzi,
- zachowanie `X-Request-Id` w komunikatach diagnostycznych albo logach klienta,
- dashboard 401/419/403/409 dla nauki mobile,
- alerty na wzrost CSRF/session expired w mobile.

### 9. Decyzje robocze po tym passie

- QA/monitoring/wydajnosc jest przeskanowane wystarczajaco do projektowania.
- Obecne testy backendowe sa mocnym fundamentem.
- Brakuje PWA-specific testow i nie wolno ich zostawic na koniec.
- `e2e:smoke` i `session-stress` trzeba rozszerzyc o mobile viewporty.
- Lighthouse PWA i service-worker cache tests musza wejsc do quality gate.
- Przed TWA trzeba dodac client-side error monitoring albo przynajmniej lekki endpoint telemetryczny.
- Performance budzet dla `/nauka` powinien powstac zanim dodamy ciezszy `learning_dashboard`.

## Ryzyka

### Ryzyko 1: zbyt szybkie obiecanie offline

Service worker nie wystarczy, zeby nauka dzialala offline. Aktywna sesja zapisuje odpowiedzi i postep na backendzie. Bez kolejki zdarzen i konflikt resolution offline bedzie pozorne.

### Ryzyko 2: sesje i CSRF

Aplikacja uzywa webowych sesji i CSRF. To jest dobre dla PWA/TWA, ale wymaga ostroznego testowania:

- wygasniecia sesji,
- 419,
- powrotu z backgroundu,
- retry requestow,
- formularzy Inertia,
- zapisu odpowiedzi w aktywnej sesji.

### Ryzyko 3: wiele urzadzen

Poniewaz system zaklada jedna aktywna sesje, offline-lite moze powodowac konflikty, gdy kursant zacznie nauke na telefonie i desktopie.

### Ryzyko 4: media

Pytania moga miec media, audio, video, PJM i assety wyjasnien. Cache mediow trzeba projektowac ostroznie, z limitami rozmiaru i wersjonowaniem.

### Ryzyko 5: Google Play i platnosci

Jesli aplikacja mobilna bedzie dawac zakup cyfrowego dostepu, trzeba uwzglednic polityki Google Play. To moze wplynac na checkout i model subskrypcji.

## Proponowana mapa prac

### Faza A: dokumentacja i decyzje

- opisac pelny flow `/nauka -> start sesji -> /nauka/teraz -> odpowiedz -> wynik`,
- spisac kontrakt danych dla aktywnej sesji,
- zdecydowac zakres PWA v1,
- zdecydowac start URL,
- zdecydowac, czy PWA v1 obejmuje tylko zalogowana nauke czy tez publiczne strony.

### Faza B: PWA foundation

- dodac manifest,
- dodac ikony,
- dodac theme color i meta,
- dodac service worker,
- dodac offline fallback,
- ustawic cache dla assetow,
- przetestowac installability.

### Faza C: mobile polish

- dopracowac `/nauka` mobile jako ekran startowy aplikacji,
- dopracowac aktywna sesje mobile,
- sprawdzic odswiezenie, powrot z backgroundu i utrate sieci,
- przygotowac screenshoty/app store assets.

### Faza D: offline-lite

- zaprojektowac lokalny snapshot sesji,
- dodac IndexedDB,
- dodac kolejke odpowiedzi,
- dodac retry i status synchronizacji,
- dodac idempotency po stronie backendu,
- przetestowac konflikty.

### Faza E: Google Play / TWA

- przygotowac Digital Asset Links,
- przygotowac wrapper TWA,
- skonfigurowac pakiet Android,
- sprawdzic polityki Google Play,
- przetestowac na fizycznych urzadzeniach,
- przygotowac listing sklepu.

## Decyzje robocze na teraz

- PWA v1 powinno byc online-first.
- Startowym ekranem PWA powinno byc `/nauka`.
- Nie robic pelnego offline w pierwszym etapie.
- Nie budowac osobnej natywnej aplikacji przed PWA.
- Nie pisac aplikacji od zera: wykorzystac obecny backend, Inertia/Vue core i mobilny blueprint `/nauka`.
- TWA jest najlepszym kandydatem na pierwsza wersje Google Play.
- `/nauka` mobile trzeba traktowac jako produktowy dashboard, nie jako poboczny widok.
- `/dashboard` powinien zostac routerem po logowaniu.
- `/nauka/teraz` nie powinno byc domyslnym startem z ikony.
- Hub `/nauka` powinien dostac jawne info o aktywnej sesji.
- Istniejacy mobile blueprint jest web/PWA blueprintem, nie osobna aplikacja natywna.
- Przed TWA trzeba uporzadkowac mobile app shell, dane dashboardu i placeholdery.
- `learning_overview` nie wystarczy jako kontrakt mobile home; potrzebny jest `learning_dashboard` albo `GET /api/v1/me/learning-home`.
- Pierwszym brakujacym polem dla PWA/TWA jest `active_session`, bo decyduje o poprawnym resume.
- Hardcoded statystyki z `MobileLearningDashboard.vue` musza zniknac przed publikacja PWA/TWA.
- Rejestr skanow PWA/mobile jest domkniety do poziomu potrzebnego do projektowania.
- Rownolegle trzeba zaprojektowac i opisac docelowy mobile API contract: `learning-home`, `sessions/current`, odpowiedzi, bledy i idempotencja.
- Nastepny krok to projekt docelowej architektury PWA/TWA: zakres v1, app shell, quality gate i kontrakt backendowy.

## Otwarte pytania

1. Czy PWA ma byc dostepna tylko po zalogowaniu, czy ma tez obejmowac publiczne SEO strony?
2. Czy aplikacja w Google Play ma pozwalac na zakup dostepu, czy tylko logowanie do konta kupionego przez web?
3. Czy offline-lite jest wymaganiem biznesowym na start, czy etapem po publikacji?
4. Czy pierwsza wersja ma obejmowac PJM, ranking i znaki drogowe, czy tylko klasyczna nauke/zen/egzamin/trener pamieci?
5. Czy aktywna sesja na `/nauka` ma byc tylko kaflem "Wroc do sesji", czy tez powinna blokowac start nowej sesji bez potwierdzenia?

## Linki do dokumentow powiazanych

- [SESSION-MODULE-ARCHITECTURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SESSION-MODULE-ARCHITECTURE.md)
- [MOBILE-NAUKA-DASHBOARD-REDESIGN-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MOBILE-NAUKA-DASHBOARD-REDESIGN-PLAN.md)
- [MOBILE-VIEWS-IMPLEMENTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MOBILE-VIEWS-IMPLEMENTATION-PLAN.md)
- [PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md)
- [PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md)
- [PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md)
- [PWA-TWA-V1-MOBILE-API-CONTRACT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-MOBILE-API-CONTRACT.md)
- [PREMIUM-REVIEW-TRAINER-ROADMAP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PREMIUM-REVIEW-TRAINER-ROADMAP.md)
- [ADAPTIVE-LEARNING-ARCHITECTURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ADAPTIVE-LEARNING-ARCHITECTURE.md)
- [API-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/API-SPEC.md)
- [TEST-STRATEGY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TEST-STRATEGY.md)

## Aktualny backlog wiedzy do dopisania

- Szczegolowy diagram przeplywu `/nauka`.
- Szczegolowy kontrakt endpointow aktywnej sesji.
- Przeniesienie macierzy cache/no-cache do przyszlego planu implementacji service workera.
- Kontrakt `active_session` dla huba `/nauka`.
- Kontrakt `GET /api/v1/sessions/current` albo `GET /api/v1/me/learning-home`.
- Aktualizacja `API-SPEC.md` do prawdziwego kontraktu mobile API.
- Test plan start/resume dla TWA: login, Android back, background, wygasla sesja, egzamin.
- Matryca PWA v1 / offline-lite / TWA.
- Checklist Lighthouse PWA.
- Checklist Google Play/TWA.
- Test plan dla Androida.
- Spec mobile app shell: app bar, bottom dock, bottom sheet, safe-area, standalone, Android Back.
- Uzgodnienie dokumentacji mobile z aktualnym kodem: `MobileAppBar`, `MobileBottomAction`, `MobileProgressRing`.
- Decyzja, ktore wspolne komponenty standaryzujemy, a ktore ekrany zostaja specjalistyczne.
- Produkcyjna inwentaryzacja mediow i docelowych limitow cache pod PWA/TWA.
- Usuniecie albo backendowe podpiecie placeholderow z `MobileLearningDashboard.vue`.
- Projekt `learning_dashboard` / `GET /api/v1/me/learning-home`.
- Projekt `recent_learning_activity` laczacy `study_sessions` i `traffic_sign_learning_sessions`.
- Decyzja, czy `weekly_activity` i `study_time` wchodza do PWA v1, czy UI ma je ukryc.
- Visual QA aktywnej sesji `/nauka/teraz` na telefonach, tabletach i standalone.
- Weryfikacja produkcyjnych naglowkow R2/CDN dla mediow.
- Release runbook TWA: Bubblewrap, package name, Play App Signing, `assetlinks.json`, internal testing.
- PWA quality gate: Playwright mobile matrix, Lighthouse, SW cache tests, Android Back, background, session expiry.
- Client-side monitoring plan: JS errors, service worker state, offline fallback, Web Vitals/RUM, request correlation.
- Decyzja produktowa: Google Play v1 jako consumption-only czy od razu projekt Play Billing/alternative billing.
