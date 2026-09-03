# Session Module Debug - 2026-03-27

## Cel

Ten dokument jest kanonicznym zapisem diagnozy dla aktualnego problemu modulu `Sesja/Pytania`.

Ma sluzyc agentowi AI jako:

- szybki punkt startowy do dalszego debugowania,
- zapis juz potwierdzonych hipotez i odrzuconych tropow,
- lista zmian wdrozonych w runtime i frontendzie,
- instrukcja jak mierzyc problem, zamiast debugowac go "na czuja".

## Scope

Dotyczy tylko flow:

1. wybor kategorii w gornym menu,
2. wejscie na `/session`,
3. wybor `Grupa pytan` i `Status pytania`,
4. `POST /study-sessions`,
5. `GET /session/current`,
6. zaznaczenie odpowiedzi i przejscie do kolejnego pytania.

Nie dotyczy:

- dashboardu,
- admina,
- review queue,
- hard questions,
- publicznego katalogu pytan.

## Objaw zgloszony przez uzytkownika

Najmocniejszy problem nie siedzial w samym backendzie pytan, tylko w odczuciu:

- start sesji bywal bardzo wolny albo "zawieszal" strone,
- odpowiedz na pytanie potrafila nie przejsc dalej,
- flow z pytaniami filmowymi byl najbardziej podatny na zwiechy,
- runtime bywal niespojny po Dockerze i lokalnym buildzie frontu.

## Ostatni potwierdzony bug i hotfix

### 2026-03-28: Znaki ostrzegawcze + Zapamiętane

To byl osobny, realny blad flow i nie byl to "subiektywny lag".

#### Reprodukcja

1. kategoria `C`,
2. `/session`,
3. grupa `Znaki ostrzegawcze`,
4. status `Zapamiętane`,
5. start sesji,
6. klikniecie odpowiedzi na pierwszym pytaniu.

#### Potwierdzone root cause'y

1. frontend nie mial w Ziggy tras:
   - `study-sessions.questions.index`
   - `study-sessions.questions.show`
   - `study-sessions.current.questions.show`

   Efekt:
   - player juz na pierwszym pytaniu nie umial wygenerowac URL do batch prefetchu,
   - na ekranie pojawialo sie `Nie udalo sie doladowac kolejnych pytan.`,
   - dalszy flow wygladal jak zawieszenie.

2. po przejsciu na sesjo-specyficzne endpointy backend nadal byl pol-zgodny:
   - `study-sessions.answers.store`
   - `study-sessions.complete`

   Front korzystal juz z tych tras, ale backend nie zawsze traktowal je jak JSON/XHR.

   Efekt:
   - odpowiedz potrafila wracac jako redirect HTML zamiast JSON,
   - Vue wywalal sie na `payload.session.status`,
   - z perspektywy UI pytanie "stalo" albo pojawial sie komunikat o bledzie zapisu.

3. reczne `fetch()` dla odpowiedzi i zakonczenia sesji byl mniej stabilny niz runtime Inertia/Axios.

   Efekt:
   - przy module `Sesja` latwiej bylo dostac niespojny kontrakt odpowiedzi,
   - szczegolnie po zmianie tras z `current` na `study-sessions/{id}`.

#### Co zostalo zmienione

Pliki:

- [config/ziggy.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/config/ziggy.php)
- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- [StudySessionAnswerController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionAnswerController.php)
- [StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)

Zmiany:

- dopisano brakujace trasy batch/question do grupy Ziggy `app`,
- local player przestal uzywac recznego `fetch()` dla odpowiedzi i zakonczenia sesji,
- krytyczne POST-y zostaly przepiete na `axios`,
- dla trybu `learn` backend zwraca JSON twardo, niezaleznie od kaprysow `expectsJson()`,
- sesja-specyficzne trasy `study-sessions.answers.store` i `study-sessions.complete` zostaly uznane za pelnoprawny kontrakt gracza `Sesja`.

#### Retest po fixie

Na aktualnym buildzie:

- `Znaki ostrzegawcze + Zapamietane` startuje bez bledu batch prefetch,
- przejscie `20` kolejnych pytan w Playwright przeszlo bez zawisu,
- nie bylo:
  - `Nie udalo sie doladowac kolejnych pytan`
  - `Nie udalo sie zapisac odpowiedzi w tle`
- po wyczerpaniu statusu `Zapamietane` strona `/session` poprawnie pokazuje:
  - `Brak aktywnych pytan dla wybranej kategorii.`

To zachowanie jest poprawne i nie jest juz zawisem.

### Objaw

- szybkie reczne klikanie odpowiedzi potrafilo zostawic player w stanie posrednim,
- w praktyce pytania czasem "staly", a czasem przeskakiwaly o wiecej niz jedno naraz,
- dlugie prompty potrafily rozepchnac uklad i wejsc na obszar odpowiedzi.

### Root cause

Byly dwa realne bledy:

1. frontend nie mial twardej blokady interakcji miedzy `selectAnswer()` i aktywacja kolejnego pytania,
2. prompt pytania mial tylko `min-height`, bez twardego limitu wysokosci i bez wewnetrznego scrolla.

Efekt uboczny:

- przy szybkim klikaniu kolejne eventy mogly wejsc, zanim Vue zakonczyl przejscie do nowego pytania,
- to dawalo wrazenie zawiechy albo nielogicznego skoku po pytaniach,
- przy dlugim tekscie prompt mogl fizycznie przykryc strefe odpowiedzi.

### Co zostalo zmienione

Pliki:

- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- [StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)

Frontend:

- dodano `answerInteractionLocked`,
- dodano `questionTransitionInFlight`,
- `selectAnswer()` od razu blokuje drugi klik dla tego samego przejscia,
- `moveToNextQuestion()` nie moze wejsc drugi raz, gdy przejscie juz trwa,
- blokada jest zwalniana dopiero po aktywacji nowego pytania,
- prompt dostal sztywny limit wysokosci i wewnetrzny scroll, zamiast rozpychac layout.

Backend:

- payload sesji przechowuje `answered_count`,
- zapis odpowiedzi nie liczy juz wszystkiego od nowa po kazdym kliknieciu,
- transakcja w `recordAnswer()` jest krotsza, co zmniejsza ryzyko blokady SQLite przy szybkim flow.

### Potwierdzenie po fixie

Na aktualnym buildzie:

- agresywny test `25` kolejnych szybkich klikniec w playerze przeszedl bez zawisu,
- numer pytania przesuwal sie zawsze dokladnie o `+1`,
- nie wykryto zachodzenia promptu na przyciski odpowiedzi,
- w najnowszym tescie logi `POST /session/current/answers` wracaly jako `200`, bez `499`.

## Reprodukcja

Najlepsza reprodukcja:

1. zaloguj sie,
2. przejdz na `/session`,
3. wybierz grupe z mediami, najlepiej `Obsluga pojazdu i bezpieczenstwo jazdy`,
4. kliknij `Rozpocznij naukę`,
5. wejdz na pierwsze pytanie filmowe,
6. zaznacz odpowiedz albo kliknij `Odtworz film`.

To jest wazniejsze niz testowanie lekkich grup tekstowych, bo one nie obciazaja playera.

## Co zostalo zmierzone

### Backend HTTP

Surowe requesty HTTP sa szybkie i same w sobie nie tlumacza "zawieszania":

- `GET /login`: ok. `60 ms`
- `POST /login`: ok. `338 ms`
- `GET /session`: ok. `195 ms`
- `POST /study-sessions`: ok. `333-411 ms`
- `GET /session/current`: ok. `186-267 ms`

To bylo potwierdzone dla grup:

- `Znaki ostrzegawcze`
- `Obsluga pojazdu i bezpieczenstwo jazdy`
- `Przepisy ogolne`

### Media host

Lokalny nginx z mediami dziala poprawnie:

- poster JPG dla pytania filmowego: ok. `17 ms` do pierwszego chunku
- mp4 ok. `6.1 MB`: ok. `188 ms` do pierwszego chunku

Wniosek:

- media host nie jest glownym bottleneckiem,
- SQLite nie jest glownym bottleneckiem dla samego startu sesji,
- glowne problemy siedza po stronie frontu i runtime integracyjnego.

## Potwierdzone root cause'y

### 1. Bledny CSRF w `Show.vue`

Plik:

- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

Problem:

- frontend preferowal cookie `XSRF-TOKEN` zamiast meta `csrf-token`,
- ten sam token byl wysylany jednoczesnie jako `X-CSRF-TOKEN` i `X-XSRF-TOKEN`,
- dla `POST /session/current/answers` dawalo to realne `419`,
- dla uzytkownika wygladalo to jak "zawieszenie pytania" po kliknieciu odpowiedzi.

Status:

- naprawione

Nowy kontrakt:

- `X-CSRF-TOKEN` bierze token z meta taga,
- `X-XSRF-TOKEN` bierze token z cookie,
- `_token` w body bierze token z meta taga.

### 2. Stary albo rozjechany frontend w Dockerze

Pliki:

- [docker-compose.yml](C:/Users/xxx/Desktop/serwistestyprawojazdy/docker-compose.yml)
- [Dockerfile PHP](C:/Users/xxx/Desktop/serwistestyprawojazdy/docker/php/Dockerfile)
- [Dockerfile nginx](C:/Users/xxx/Desktop/serwistestyprawojazdy/docker/nginx/Dockerfile)

Problem:

- kontenery kopiowaly `public/build` tylko w czasie `docker build`,
- po zmianach w `Show.vue` mozna bylo miec nowy kod w repo, ale stary bundle w kontenerze,
- to powodowalo niespojne debugowanie,
- dodatkowo brak `public/build/manifest.json` dawal realne `500`.

Status:

- czesciowo naprawione

Co zostalo zrobione:

- `public/build` jest teraz montowane do `app` i `web`,
- po `vite build` nie trzeba przebudowywac kontenerow tylko po to, by podmienic assets.

### 3. Zbyt ciezka inicjalizacja filmowego pytania

Plik:

- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

Problem:

- `session/current` z filmowym pierwszym pytaniem potrafil zawieszac przejscie z `/session`,
- backend odpowiadal szybko, ale frontendowy runtime blokowal wejscie do playera,
- najwiekszym podejrzanym okazala sie inicjalizacja `video` juz przy pierwszym renderze.

Status:

- glowna czesc naprawiona

Co zostalo zmienione:

- player nie montuje juz `video` przed aktywacja,
- najpierw renderowany jest poster albo placeholder,
- `video` pojawia sie dopiero po aktywacji,
- przygotowanie kolejnych pytan zostalo przesuniete na `requestAnimationFrame`, zeby nie konkurowalo z pierwszym paintem.

Efekt:

- `session/current` dla filmowego pierwszego pytania zaczal ladowac poprawnie,
- Playwright przestal timeoutowac juz przy samym wejsciu na strone sesji,
- pelne flow `/session -> start -> /session/current -> odpowiedz` znow przechodzi.

### 4. Auto-advance gubil sie na granicy kolejnej paczki pytan

Plik:

- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

Problem:

- panel nauki dzialal szybko przez pierwsze pytania,
- zawiecha wracala zwykle na granicy nowego batcha pytań,
- frontend uruchamial juz fetch kolejnej paczki, ale po kliknieciu odpowiedzi nie umial na ten juz-trwajacy fetch zaczekac,
- auto-przejscie przepadało, mimo ze dane za chwile byly gotowe,
- dla uzytkownika wygladalo to jak "losowe" zawieszenie na 10-15 pytaniu.

Status:

- naprawione

Co zostalo zmienione:

- player trzyma teraz referencje do aktualnie trwajacego `fetchQuestionBatch`,
- `prepareUpcomingQuestion()` czeka rowniez na juz-rozpoczety batch, zamiast traktowac go jak brak danych,
- auto-advance nie gubi sie juz na granicy kolejnych paczek pytan.

Potwierdzenie:

- po poprawce automatyczny przebieg przez `20` kolejnych pytan w Playwright przeszedl bez timeoutu.

### 5. Backend sesji byl zbyt rygorystyczny wobec optymistycznego playera nauki

Pliki:

- [StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)
- [StudySessionAnswerController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionAnswerController.php)

Problem:

- frontend w trybie nauki przechodzil lokalnie do kolejnego pytania szybciej niz backend aktualizowal `current_index`,
- backend traktowal to jak naruszenie kolejnosci pytan,
- czesc zapisow odpowiedzi konczyla sie redirectowym fallbackiem zamiast lekkiego JSON API,
- to psulo auto-advance i wzmacnialo wrazenie, ze sesja "muli" albo "staje".

Status:

- naprawione

Co zostalo zmienione:

- w `learn` backend akceptuje teraz optymistyczny, lokalny porzadek odpowiedzi w ramach sesji,
- `answeredCount()` liczy rzeczywista liczbe zapisanych odpowiedzi, a nie sam wskaznik `current_index`,
- zapis sesji i odpowiedzi zostal uszczelniony transakcyjnie,
- po poprawce `POST /session/current/answers` stabilnie wraca `200 application/json` zamiast spadac do redirectowego flow.

Potwierdzenie:

- po poprawce `15` kolejnych answer requestow w runtime przeszlo jako `200`, bez redirectu i bez HTML fallback.

### 6. Formularz startu sesji pokazywal zdublowane grupy pytan

Plik:

- [SessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/SessionPageController.php)

Problem:

- ta sama grupa pytan potrafila pojawic sie dwa razy, bo byla rozbijana osobno na `PODSTAWOWY` i `SPECJALISTYCZNY`,
- frontend i tak filtrowal tylko po `question_topic_id`, wiec oba wpisy startowaly praktycznie ten sam topic,
- to tworzylo chaos w inicjalizacji sesji i zle ustawialo oczekiwana liczbe pytan.

Status:

- naprawione

Co zostalo zmienione:

- grupy w `Sesja` sa teraz scalane po `question_topic_id`,
- lista opcji jest prostsza i zgodna z realnym filtrem backendu.

## Potwierdzone rzeczy, ktore NIE byly glowna przyczyna

### SQLite jako glowny winowajca

Nie potwierdzono.

SQLite ma swoje ograniczenia, ale przy obecnym flow start sesji i pobranie `session/current` sa szybkie.

### Lokalny nginx dla mediow

Nie potwierdzono.

Poster i pierwszy chunk mp4 sa serwowane szybko.

### Sam `POST /study-sessions`

Nie potwierdzono.

Start sesji po backendzie jest szybki i stabilny.

## Obecny stan po poprawkach

Potwierdzone dzialanie:

- `GET /session`
- `POST /study-sessions`
- `GET /session/current`
- klikniecie odpowiedzi na pierwszym pytaniu
- przejscie do nastepnego pytania
- start sesji dla grupy filmowej
- auto-przejscie przez wielokrotne kolejne pytania w trybie nauki
- stabilny JSON flow dla `POST /session/current/answers`

Najwazniejszy potwierdzony case:

- `Obsluga pojazdu i bezpieczenstwo jazdy`
- kategoria `C`
- start sesji od pytania filmowego

To byl najwazniejszy runtime case do zbicia.

## Remaining risk

Jedna rzecz wymaga jeszcze osobnego passa:

- sam interaction path `Odtworz film`

Po poprawkach nie jest juz glownym blokerem inicjalizacji ani odpowiedzi w sesji, ale jesli wroca pojedyncze zwiechy, kolejny debug trzeba prowadzic juz tylko na:

- aktywacji playera filmu,
- nie na starcie sesji ani batchowaniu pytan jako calosci.

## Jak debugowac dalej

Jesli problem wraca, debuguj w tej kolejnosci:

1. sprawdz czy `vite build` byl zrobiony po zmianach w `Show.vue`,
2. sprawdz czy `public/build` jest widoczne w kontenerach,
3. zmierz osobno:
   - `GET /session`
   - `POST /study-sessions`
   - `GET /session/current`
4. sprawdz, czy w logach jest `419` na `/session/current/answers`,
5. sprawdz, czy aktywna sesja startuje od pytania filmowego,
6. jesli tak, debuguj juz tylko aktywacje filmu, nie backend.

## Kanoniczne pliki tego modulu

Backend:

- [routes/web.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/routes/web.php)
- [SessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/SessionPageController.php)
- [StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)
- [StudySessionAnswerController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionAnswerController.php)
- [StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)
- [QuestionMediaPayloadBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionMediaPayloadBuilder.php)
- [MediaUrlResolver.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/MediaUrlResolver.php)

Frontend:

- [Session/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue)
- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- [AuthenticatedLayout.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/AuthenticatedLayout.vue)
- [app.blade.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/views/app.blade.php)

Runtime:

- [docker-compose.yml](C:/Users/xxx/Desktop/serwistestyprawojazdy/docker-compose.yml)
- [app.conf](C:/Users/xxx/Desktop/serwistestyprawojazdy/docker/nginx/app.conf)
- [media.conf](C:/Users/xxx/Desktop/serwistestyprawojazdy/docker/nginx/media.conf)

## Co jest teraz prawda robocza dla agenta AI

Agent ma przyjmowac nastepujace zalozenia jako aktualne:

- problem nie siedzi glownie w SQL,
- problem nie siedzi glownie w hostingu mediow,
- historyczny `419` byl realnym bugiem i juz nie wolno wracac do starej logiki CSRF,
- najczulszym miejscem modulu jest `StudySessions/Show.vue`,
- przy problemach z `session/current` trzeba rozroznic:
  - start sesji,
  - wejscie na pierwsze pytanie,
  - klikniecie odpowiedzi,
  - aktywacje filmu.

To sa cztery rozne fazy i nie wolno mieszac ich w jednej diagnozie.

## Update 2026-03-28 - autoplay filmow

### Objaw

- po zaznaczeniu `Automatycznie odtwarzaj filmy` player potrafil sprawiac wrazenie zawieszonego,
- w Playwright potrafil zablokowac nawet zwykle klikniecie checkboxa autoplay,
- snapshot strony po tej akcji mogl timeoutowac, mimo ze backend nadal odpowiadal `200`,
- problem byl najbardziej widoczny przy pytaniach filmowych w `Znaki ostrzegawcze` i `Sygnaly, pierwszenstwo i skrzyzowania`.

### Root cause

Problem siedzial w frontendowym lifecycle playera, nie w backendzie:

- `Show.vue` trzymal `videoElements` w reaktywnym `ref<Record<...>>`,
- callback `:ref` dla `<video>` zapisywal element DOM do tego reaktywnego stanu,
- samo zamontowanie `<video>` przy autoplay wywolywalo kolejne aktualizacje komponentu,
- przy aktywacji filmu moglo to nakrecac render churn dokladnie w momencie wlaczenia autoplay.

To byl blad architektoniczny:

- referencje do elementow DOM nie powinny byc tu przechowywane w reaktywnym stanie, bo nie sa potrzebne do renderu.

### Naprawa

W [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue):

- `videoElements` zostalo przepiete z reaktywnego `ref<Record<...>>` na zwykly `Map<string, HTMLVideoElement>`,
- autoplay nie jest juz wykonywany bezposrednio w ciezkim watcherze jako blokujace `await`,
- autoplay jest planowany po stabilizacji DOM:
  - `nextTick()`
  - `requestAnimationFrame()`
- autoplay dotyka juz tylko aktywnych mediow biezacego pytania, a nie wszystkich potencjalnie zamontowanych video refs.

### Potwierdzenie po fixie

Playwright po poprawce przeszedl:

- `25` kolejnych pytan w dziale `Sygnaly, pierwszenstwo i skrzyzowania` z wlaczonym autoplay,
- `8` kolejnych pytan w `Znaki ostrzegawcze -> Zapamietane`,
- w pytaniach filmowych video bylo w stanie:
  - `paused: false`
  - `ended: false`
  - `readyState: 4`

Czyli:

- filmy rzeczywiscie startowaly automatycznie,
- player nie wieszal juz widoku przy samym wlaczeniu autoplay,
- backend nie byl juz potrzebny do diagnozy tego bledu.

### Wniosek dla kolejnych agentow

Jesli problem z filmami wroci:

1. najpierw sprawdz `Show.vue`,
2. potem lifecycle `<video>`,
3. dopiero na koncu media server i backend.

Nie wracaj odruchowo do diagnozy `SQLite` albo `session/current/answers`, jesli objaw pojawia sie dokladnie po wlaczeniu autoplay.
