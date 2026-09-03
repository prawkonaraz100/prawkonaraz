# Session Module Architecture

## Cel

Ten dokument jest kanoniczna dokumentacja modulu `Sesja`.

Ma sluzyc agentowi AI i developerowi jako:

- glowny punkt wejscia do dalszego rozwoju playera pytan,
- opis aktualnej architektury UI + backend,
- zbior zasad, ktorych nie wolno lamac przy kolejnych zmianach,
- instrukcja gdzie dodawac nowe funkcje, a gdzie nie dokladac kolejnego chaosu.

Ten dokument uzupelnia, a nie zastepuje:

- [SESSION-MODULE-DEBUG-2026-03-27.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SESSION-MODULE-DEBUG-2026-03-27.md)
- [PLAYER-AUDIT-ZDAMYTO-NAUKA.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PLAYER-AUDIT-ZDAMYTO-NAUKA.md)

Debug log opisuje symptomy i root cause'y. Ten dokument opisuje docelowa strukture modulu i zasady dalszego developmentu.

## Scope

Dotyczy tylko flow:

1. wybor kategorii w gornym menu aplikacji,
2. wejscie na `/nauka`,
3. wybor grupy pytan i statusu pytan,
4. start sesji,
5. aktywna sesja pod `/nauka/teraz`,
6. odpowiedz na pytanie,
7. przejscie do kolejnego pytania,
8. zakonczenie sesji i review na koncu.

Nie dotyczy:

- dashboardu,
- admina,
- importu pytan,
- publicznego katalogu pytan,
- review queue,
- hard questions.

## Gdzie jest kod

### Frontend

- glowny player: [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- ekran przygotowania sesji: [Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue)
- layout trybu skupienia: [SessionZenLayout.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/SessionZenLayout.vue)

### Backend

- glowna logika sesji: [StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)
- kontroler sesji: [StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)
- kontroler zapisu odpowiedzi: [StudySessionAnswerController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionAnswerController.php)
- ekran konfiguracji sesji: [SessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/SessionPageController.php)
- trasy: [web.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/routes/web.php)

## Model produktu

Modul `Sesja` ma dwa rozne ekrany:

1. `Session/Index.vue`
2. `StudySessions/Show.vue`

To sa dwa rozne swiaty i nie wolno ich mieszac.

### 1. `/nauka`

To jest ekran konfiguracji nauki.

Ma tylko:

- wybor grupy pytan,
- wybor statusu pytan,
- start sesji.

To nie jest player.

### 2. `/nauka/teraz`

To jest aktywna sesja w trybie skupienia.

Ma byc traktowana jako `zen mode`, czyli:

- bez globalnego menu aplikacji,
- bez standardowego headera,
- bez paneli pobocznych,
- bez rozproszonych statystyk po ekranie,
- tylko top bar, medium, pytanie i odpowiedzi.

## Docelowy model UX

Aktualna sesja jest projektowana jako srodowisko do wykonania jednego zadania poznawczego.

Zasada:

- top bar = meta i sterowanie,
- srodek = medium,
- dol = pytanie i odpowiedzi.

Nie dokladamy nowego sidebara.
Nie rozpraszamy gracza dodatkowymi boxami.

### Co zostaje na ekranie aktywnej sesji

- numer pytania,
- nazwa dzialu,
- punkty,
- podstawowe liczniki sesji,
- ustawienia sesji,
- zakoncz sesje,
- medium,
- prompt,
- odpowiedzi,
- opcjonalnie wyjasnienie.

### Co nie powinno wracac do tego widoku

- globalne menu aplikacji,
- selector kategorii,
- dashboardowy shell,
- prawa kolumna ze statystykami,
- duze kolorowe boxy pomocnicze,
- osobne ekrany miedzy pytaniami.

## Aktualna architektura frontendowa

Glowny player jest w tej chwili monolitem w [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue).

To znaczy, ze w jednym pliku siedza naraz:

- layout sesji,
- render medium,
- prompt,
- odpowiedzi,
- ustawienia,
- tryby feedbacku,
- autoplay video,
- batch prefetch pytan,
- zapis odpowiedzi,
- zakonczenie sesji,
- sterowanie klawiatura.

To jest akceptowalne na teraz, ale docelowo ten plik powinien zostac rozbity.

### Docelowy kierunek refaktoru

W kolejnych iteracjach trzeba dazyc do wydzielenia:

- `SessionTopBar`
- `SessionMediaStage`
- `SessionPrompt`
- `SessionAnswers`
- `SessionExplanationPanel`
- `SessionSettingsPopover`
- `useSessionPlayer`
- `useSessionMedia`

Nie robic tego wszystkiego naraz. Refaktor ma byc etapowy i bez regresji runtime.

## Kontrakt backendowy

### Start sesji

Za tworzenie sesji odpowiada [StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php).

Najwazniejsze metody:

- `start()`
- `orderedQuestions()`
- `currentQuestion()`
- `activeSessionForUser()`
- `recordAnswer()`

### Zasada trybu nauki

Tryb `learn` nie jest egzaminem.

To oznacza:

- nie ograniczamy go sztucznie do `32` pytan,
- sesja moze obejmowac cala grupe pytan,
- gracz moze miec rozne sposoby feedbacku,
- wydajnosc musi byc utrzymana przez cache i prefetch, a nie przez brutalne ciecie funkcji.

### Zasada aktywnej sesji

Aktywna sesja ma byc dostepna pod stalego typu URL:

- `/nauka/teraz`

Nie opieramy UX gracza na numerowanym URL sesji.

Sesjo-specyficzne endpointy backendowe moga istniec, ale aktywny frontend powinien rozmawiac z flow `current`, nie ze starym modelem "otwarta historyczna sesja po ID".

## Model danych po stronie frontu

W playerze sa trzy kluczowe warstwy stanu:

### 1. Stan sesji

- `sessionState`
- `progressState`
- `localSessionCompleted`
- `currentQuestionNumberState`

To jest aktualny stan sesji widoczny na ekranie.

### 2. Stan pytania

- `activeQuestionState`
- `selectedAnswer`
- `currentAnswerResult`
- `showExplanation`
- `questionStage`

To steruje tym, co gracz widzi i co juz odpowiedzial.

### 3. Stan wydajnosciowy

- `questionCache`
- `preparedNextQuestion`
- `preparedNextQuestionNumber`
- `questionBatchFetchInFlight`
- `pendingQuestionBatchRequest`
- `answerSyncQueue`

To sa mechanizmy szybkosciowe.

Nie wolno ich kasowac "bo kod wyglada prosciej", jesli nie ma zamiennika o tej samej wydajnosci.

## Performance invariants

To jest najwazniejsza sekcja dla dalszego developmentu.

Kazda zmiana w playerze ma szanowac te zasady:

### 1. Odpowiedz ma dawac natychmiastowy efekt lokalny

Po kliknieciu odpowiedzi UI nie moze czekac na backend, zeby:

- zmienic kolor zaznaczonego buttona,
- zwiekszyc licznik odpowiedzianych,
- przeliczyc lokalny wynik.

To ma sie dziac optymistycznie lokalnie.

### 2. Zapis do backendu jest wtorny wobec odczucia szybkosci

Backend ma sie zsynchronizowac w tle.

UI ma byc subiektywnie szybkie nawet wtedy, gdy request do backendu trwa odrobine dluzej.

### 3. Kolejne pytanie powinno byc przygotowane z wyprzedzeniem

Jesli pytanie nie jest w cache, frontend ma prefetchowac batch.

Nie wolno wracac do modelu:

- klik odpowiedzi,
- potem pelne czekanie na nowe pytanie,
- potem dopiero render.

### 4. Media nie moga blokowac calego flow

Obrazy i wideo maja byc przygotowane tak, by:

- nie rozbijaly layoutu,
- nie blokowaly przejscia,
- nie wymuszaly agresywnego preloadu wszystkiego naraz.

### 5. Tryb nauki nie moze zdradzac wyniku, jesli user wybral ocene na koncu

To dotyczy nie tylko koloru odpowiedzi, ale tez:

- top bara,
- licznika poprawnych,
- procentu wyniku,
- explanation.

## Tryby feedbacku odpowiedzi

Aktualny system ma 3 tryby i to jest kanoniczny model produktu.

### 1. `instant`

UI label:

- `Od razu: dobrze / zle`

Zachowanie:

- po kliknieciu odpowiedz zmienia kolor na zielony albo czerwony,
- bez dodatkowego banera tekstowego,
- bardzo krotki impuls koloru,
- mozliwy auto-advance.

To jest tryb najszybszy.

### 2. `instant_explanation`

UI label:

- `Od razu + wyjasnienie`

Zachowanie:

- po kliknieciu odpowiedz zmienia kolor na zielony albo czerwony,
- po bledzie poprawny wariant moze dostac delikatny zielony stan,
- obszar pytania przechodzi chwilowo w stan `hint-only`,
- zamiast promptu pokazuje sie krotka wskazowka instruktora,
- auto-advance moze byc wlaczony albo wylaczony przez usera,
- na swiezym starcie sesji ten tryb jest ustawiany jako domyslny.

To jest tryb na nauke z rozumieniem.

### 3. `review`

UI label:

- `Ocena na koncu sesji`

Zachowanie:

- w trakcie odpowiedzi nic nie zdradza wyniku,
- top bar nie pokazuje licznika poprawnych ani procentu,
- explanation nie powinno byc pokazywane od razu,
- ocena i review sa dopiero na koncu.

To jest tryb na samosprawdzenie bez podpowiedzi.

### Domyslny pierwszy start

Dla nowego usera bez zapisanych preferencji:

- domyslny tryb to `Od razu + wyjasnienie`,
- domyslne tempo to `Sam klikam dalej`,
- `Odtwarzaj filmy automatycznie` jest wylaczone.

To jest kanoniczny start onboardingowy dla modulu `Sesja`.

## Sterowanie klawiatura

To jest kanoniczna funkcja modulu i mozna ja rozwijac, ale nie wolno psuc obecnej prostoty.

Aktualne zasady:

- pytania boolean:
  - `ArrowLeft` / `ArrowUp` = pierwsza odpowiedz
  - `ArrowRight` / `ArrowDown` = druga odpowiedz
- pytania wielowariantowe:
  - strzalki przesuwaja aktywna odpowiedz
  - `Enter` lub `Space` zatwierdzaja
- po odpowiedzi:
  - `Enter`, `Space` albo `ArrowRight` moga przejsc dalej, jesli sesja jest w trybie recznym

Sterowanie klawiatura jest blokowane, gdy:

- otwarte sa `Ustawienia`,
- focus jest w elemencie formularza lub innym interaktywnym elemencie.

To jest celowe. Nie psuc tego.

## Zasady prezentacji pytania i odpowiedzi

To sa aktualne decyzje produktowe:

- medium moze byc szerokie,
- prompt ma byc wezszy niz medium,
- prompt ma stabilny kontener,
- nie chcemy scrollbara w prompt,
- odpowiedzi musza byc zawsze widoczne,
- prompt nie moze nachodzic na buttony,
- feedback natychmiastowy ma byc glownie kolorem, nie tekstem.

## Zasady prezentacji mediow

- obrazy i wideo maja wspolny frame logiczny,
- zachowuja proporcje,
- nie sa rozciagane na sile,
- nie moga wychodzic poza kontener,
- wideo nie moze pokazywac natywnych kontrolek przegladarki,
- autoplay ma byc opcja, nie przymusem.

## Gdzie dodawac nowe funkcje

### Dobre miejsca

- nowe ustawienia sesji: popover `Ustawienia` w [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- nowe tryby feedbacku: logika przy `feedbackMode`
- nowe skroty klawiaturowe: `handleSessionKeydown`
- nowa logika zapisu odpowiedzi: `persistLocalAnswer`
- nowa logika prefetchu: `prepareUpcomingQuestion` i `fetchQuestionBatch`

### Zle miejsca

- dopychanie globalnego layoutu aplikacji do sesji,
- dorzucanie nowego sidebara,
- dodawanie osobnych requestow po kazdej odpowiedzi tylko po to, by policzyc licznik,
- rozpraszanie logiki po przypadkowych watcherach bez opisu.

## Zasady zmian dla AI-agenta

Przy kazdej zmianie w module `Sesja` agent ma:

1. okreslic, czy zmiana dotyczy:
   - UX,
   - logiki sesji,
   - wydajnosci,
   - kontraktu backendowego,
   - mediow,
   - klawiatury.
2. sprawdzic, czy nie przecieka wynik w trybie `review`.
3. sprawdzic, czy nie dodaje nowego czekania po odpowiedzi.
4. sprawdzic, czy prompt nadal nie nachodzi na odpowiedzi.
5. sprawdzic, czy media nie wychodza poza frame.
6. uruchomic:
   - `vue-tsc --noEmit`
   - `vite build`
7. jesli zmiana dotyczy flow odpowiedzi, zrobic szybki smoke na `/nauka/teraz`.

## Minimalna smoke checklist

Po kazdej wazniejszej zmianie:

1. wejdz na `/nauka`
2. uruchom nowa sesje
3. odpowiedz na kilka pytan bez myszy i z myszka
4. sprawdz tryb:
   - `Od razu: dobrze / zle`
   - `Od razu + wyjasnienie`
   - `Ocena na koncu sesji`
5. sprawdz pytanie z obrazem
6. sprawdz pytanie z video
7. sprawdz, czy `Zakoncz sesje` nadal konczy sesje bez bledu sync

## Known risks

### 1. Monolit w `Show.vue`

To nadal jest najwiekszy dlug techniczny modulu.

### 2. Watchery i flagi przejsc

Player ma juz kilka flag ochronnych:

- `answerInteractionLocked`
- `questionTransitionInFlight`
- `questionBatchFetchInFlight`
- `learningSyncInFlight`

To sa prawdziwe potrzeby runtime, ale sygnalizuja, ze refaktor na mniejsze komponenty i composables bedzie potrzebny.

### 3. Nadmierne dokladanie funkcji do top bara

Top bar ma byc lekki.

Nie wolno znowu zamienic go w dashboard sesji.

## Roadmap modulu

Najlepsza kolejnosc dalszego rozwoju:

1. ustabilizowac aktualny player,
2. rozbic `Show.vue` na mniejsze komponenty,
3. wydzielic logike do composables,
4. dopiero potem dokladac bardziej zaawansowane funkcje nauki,
5. nie ruszac backendu bez realnej potrzeby kontraktowej albo wydajnosciowej.

## Decyzja kanoniczna

Na dzisiaj przyjmujemy:

- `Sesja` jest osobnym modulem produktu,
- aktywna sesja ma byc `zen mode`,
- priorytetem jest odczuwalna szybkosc po kliknieciu odpowiedzi,
- feedback natychmiastowy ma byc glownie wizualny,
- system ma wspierac zarowno myszke, jak i klawiature,
- dalszy rozwoj ma isc w strone modularizacji playera, a nie dalszego dokladania logiki do przypadkowych miejsc.
