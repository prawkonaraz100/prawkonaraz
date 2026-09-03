# Mobile views implementation plan

## Cel

Celem tego etapu jest doprowadzenie kluczowych widokow aplikacji do spojnego, szybkiego i przewidywalnego UX na telefonach.

Nie chodzi o pojedyncze poprawki CSS na kazdej stronie. Chodzi o stworzenie wspolnego mobilnego sposobu prowadzenia uzytkownika przez nauke:

- prosty top bar albo pelna nawigacja tylko tam, gdzie ma sens,
- jedna dominujaca akcja na ekranie,
- brak poziomego scrolla,
- stabilne panele bez skakania wysokosci,
- duze, czytelne targety dotykowe,
- ograniczenie tekstu na pierwszym ekranie,
- spojnosc z desktopem bez kopiowania desktopowego ukladu na telefon.

## Diagnoza kodu

### Glowne layouty

| Plik | Rola | Wnioski mobile |
| --- | --- | --- |
| `resources/js/Layouts/AuthenticatedLayout.vue` | Layout dla prywatnej czesci aplikacji | Ma juz route-aware klasy szerokosci i paddingu. Dla `review-queue.*` ukrywa globalny header/footer na mobile. To dobry wzorzec app-shell, ale na razie zaszyty tylko dla trenera pamieci. |
| `resources/js/Layouts/GuestLayout.vue` | Layout logowania/rejestracji/guest | Ma klasyczny header/footer i centrowana karte. Dobre dla auth, ale wymaga sprawdzenia dlugich formularzy i kart na malych ekranach. |
| `resources/js/Layouts/SessionExamLayout.vue` | Layout egzaminu i trybow sesyjnych | Ma `lockViewport`, `overflow-x-hidden` i wysokosci desktopowe od `xl`. Na mobile nie powinien blokowac naturalnego scrolla bez potrzeby. |
| `resources/js/Layouts/SessionZenLayout.vue` | Zen shell | Ma dekoracyjne warstwy fixed dla desktopu i `lockViewport` od `sm`. Wymaga osobnego testu na telefonie, bo Zen powinien byc najbardziej skupiony. |

### Wspolne komponenty

| Plik | Rola | Wnioski mobile |
| --- | --- | --- |
| `resources/js/Components/SiteHeader.vue` | Globalna nawigacja | Mobile header dziala, ale jest webowy: logo, akcja, menu. Dla ekranow stricte aplikacyjnych lepszy jest lekki app bar z powrotem, tytulem i kontekstem. |
| `resources/js/Components/SiteFooter.vue` | Footer strony | Na ekranach aplikacyjnych mobile czesto przeszkadza. Powinien zostac na publicznych/formularzowych widokach, ale nie w aktywnej nauce. |
| `resources/js/Components/QuestionExplanationRuntimeBlock.vue` | Uzasadnienie pytania | Ma juz poprawke mobile: media/znak po lewej, tekst po prawej. To nalezy zachowac i traktowac jako wzorzec dla uzasadnien. |
| `resources/js/Components/QuestionImageWithAnnotations.vue` i `QuestionVideoFrameWithAnnotations.vue` | Media z adnotacjami | Krytyczne dla nauki. Zmiany mobile musza pilnowac proporcji, widocznosci adnotacji i braku zaslaniania waznych elementow. |

### Glowne trasy i komponenty

| URL | Komponent | Priorytet mobile |
| --- | --- | --- |
| `/nauka` | `resources/js/Pages/Session/Index.vue` | P0. Glowny hub. Musi byc czytelny i szybki. |
| `/nauka/teraz` | `resources/js/Pages/StudySessions/Show.vue` albo `StudySessions/Exam.vue` zalezne od trybu | P0. Najbardziej ryzykowny ekran, najwiecej logiki i fixed paneli. |
| `/nauka/wynik/{id}` | `resources/js/Pages/StudySessions/Show.vue` | P0. Wynik musi miec dobre CTA na telefonie. |
| `/trener-pamieci` | `resources/js/Pages/ReviewQueue/Index.vue` | P1. Ma juz mobile app-shell, moze byc wzorcem do ekstrakcji. |
| `/nauka/znaki-drogowe` | `resources/js/Pages/TrafficSignLearning/Index.vue` | P1. Wymaga uproszczenia dashboardu kategorii na telefonach. |
| `/nauka/znaki-drogowe/teraz` | `resources/js/Pages/TrafficSignLearning/Show.vue` | P1. Ma osobny szybki flow, lokalny snapshot pytan i batch sync. Warto zachowac. |
| `/nauka/znaki-drogowe/wynik/{id}` | `resources/js/Pages/TrafficSignLearning/Result.vue` | P2. Podsumowanie powinno miec proste CTA. |
| `/testy-na-prawo-jazdy` | public demo landing/gate | P2. Publiczny widok, nie app-shell. |
| `/testy-na-prawo-jazdy/demo` | `resources/js/Pages/StudySessions/Show.vue` z `publicDemo` | P1/P2. Dzieli renderer z nauka, wiec trzeba uwazac, aby zmiany sesji nie zepsuly demo. |
| `/login`, `/register`, `/verify-email` | `resources/js/Pages/Auth/*` | P2. Formularze musza byc czyste, bez overflow i z wygodnymi przyciskami. |
| `/checkout/*`, `/aktywuj-dostep` | `Checkout/*`, `Access/Activate.vue` | P2. Krytyczne biznesowo, ale mniej zlozone niz sesja. |
| `/profile`, `/moderator/konta` | `Profile/Edit.vue`, `Moderator/Accounts/Index.vue` | P3. Wazne, lecz nie pierwsze dla kursanta. |
| publiczne SEO: `/`, `/cennik`, `/znaki-drogowe`, `/oficjalna-baza-pytan-na-prawo-jazdy` | rozne kontrolery/public content | P3. Responsive web, nie aplikacyjny app-shell. |

## Najwazniejsze ryzyka

### 1. `StudySessions/Show.vue` jest zbyt duzy na szybki refaktor

Ten komponent obsluguje:

- klasyczna nauke,
- Zen,
- PJM,
- trener pamieci,
- wynik sesji,
- publiczne demo,
- adnotacje,
- media,
- modale,
- bottom dock,
- panele boczne,
- podsumowania.

Plan: nie przepisywac go w jednym sprincie. Robic male, izolowane poprawki mobile i po kazdej uruchamiac build oraz manualny flow.

### 2. Fixed bottom dock moze zaslaniac tresc

W `StudySessions/Show.vue` i `StudySessions/Exam.vue` istnieja klasy `fixed inset-x-0 bottom-0`, spacery i `safe-area`. Kazda zmiana wysokosci odpowiedzi albo CTA musi sprawdzac:

- iPhone-like viewport 390x844,
- mniejszy Android 360x740,
- wiekszy telefon 430x932,
- brak przykrycia ostatniej odpowiedzi,
- poprawny padding `env(safe-area-inset-bottom)`.

### 3. Globalny header nie pasuje do wszystkich ekranow

`SiteHeader.vue` jest dobry dla publicznych i klasycznych stron, ale ekran aktywnej nauki potrzebuje app bar:

- powrot,
- tytul,
- kontekst, np. kategoria/progres,
- bez pelnego menu i footera.

Plan: nie ukrywac globalnego headera losowo w kolejnych plikach. Wprowadzic jawna polityke layoutu.

### 4. Kilka ekranow ma poziome menu albo gridy z duzymi min-width

Przyklady:

- `/nauka` ma taby z `overflow-x-auto`,
- `StudySessions/Show.vue` ma customowe gridy i panele,
- `TrafficSignLearning/Index.vue` ma karty kategorii,
- `Moderator/Accounts/Index.vue` ma tabele z `overflow-x-auto`.

Plan: rozroznic poziomy scroll jako kontrolowany pattern od przypadkowego overflow strony.

### 5. Motion musi byc spokojny i dostepny

Trener pamieci ma juz `prefers-reduced-motion`. Ten standard musi zostac powtorzony:

- animacje tylko jako mikrofeedback,
- zero animacji ciaglych,
- `prefers-reduced-motion` obowiazkowo,
- brak opozniania glownej akcji.

## Strategia wdrozenia

Nie robimy "strona po stronie" bez systemu. Robimy warstwowo:

1. Definiujemy mobilne wzorce.
2. Wzmacniamy wspolne layouty.
3. Przechodzimy przez najwazniejsze flow kursanta.
4. Dopiero potem poprawiamy widoki poboczne.

## Dokumenty powiazane

- `docs/MOBILE-NAUKA-DASHBOARD-REDESIGN-PLAN.md` - szczegolowy plan przebudowy mobile `/nauka` w kierunku aplikacyjnego dashboardu: decyzje, assety, komponenty, sprinty, ryzyka i checklisty QA.

## Obowiazkowy pre-sprint code scan

Kazdy sprint musi zaczac sie od krotkiego, celowanego skanu kodu. Bez tego nie wchodzimy w development. Celem nie jest ponowne czytanie calego repozytorium, tylko upewnienie sie, ze znamy zaleznosci konkretnego flow i nie dotykamy niczego przypadkiem.

### Bramka wejscia do sprintu

Przed pierwsza zmiana w kodzie trzeba miec odpowiedzi na pytania:

1. Jaka trasa uruchamia widok?
2. Jaki controller buduje propsy?
3. Jaki komponent Inertia renderuje ekran?
4. Jakie formularze, endpointy i route names sa uzywane?
5. Jakie wspolne komponenty/layouty sa w sciezce renderowania?
6. Jakie tryby specjalne wspoldziela ten widok?
7. Jakie klasy/layouty mobile sa ryzykowne?
8. Jakie testy i manualne flow musza przejsc po zmianie?

### Minimalna komenda/skan dla kazdego sprintu

Przed sprintem sprawdzamy:

- `routes/web.php` dla trasy i middleware,
- controller odpowiedzialny za Inertia props,
- komponent `.vue` i jego `defineProps`,
- wszystkie `useForm`, `router.visit`, `router.get`, `Link`, `route(...)`,
- wspolne layouty uzyte przez widok,
- komponenty media/adnotacji/CTA,
- klasy `fixed`, `sticky`, `overflow`, `min-h`, `h-[...]`, `grid-cols-[...]`, `safe-area`, `100vh`, `100svh`, `100dvh`,
- warunki `isPublicDemoMode`, `isPjmMode`, `isReviewTrainerMode`, `isExamLikeShell`, `canUseFullProduct`, `isPjmStarterMode`, jesli wystepuja w danym widoku.

### Dokumentacja wyniku skanu

Przed kodowaniem w danym sprincie dopisujemy do dokumentu albo do notatek sprintu:

- pliki, ktore wolno zmieniac,
- pliki tylko do odczytu,
- rzeczy, ktorych nie wolno ruszac,
- lista flow do sprawdzenia,
- decyzja: app shell czy web shell,
- ryzyko: niskie/srednie/wysokie,
- plan rollbacku, czyli najblizszy commit, do ktorego mozna bezpiecznie wrocic.

### Twarde zasady

- Jesli skan wykaze, ze ekran jest wspoldzielony przez kilka trybow, zmiana musi byc minimalna i warunkowana.
- Jesli zmiana dotyka `StudySessions/Show.vue`, trzeba najpierw wskazac, ktore tryby moga byc dotkniete: klasyczna nauka, Zen, PJM, trener pamieci, public demo, wynik sesji.
- Jesli zmiana dotyka formularza, nie wolno zmieniac payloadu bez osobnego testu.
- Jesli zmiana dotyka app-shella/layoutu, trzeba sprawdzic desktop i mobile.
- Jesli pojawia sie poziomy scroll na `document.documentElement`, sprint nie jest gotowy.
- Jesli fixed bottom dock zaslania odpowiedz, sprint nie jest gotowy.
- Jesli media/adnotacje traca widocznosc, sprint nie jest gotowy.

## Mobilne standardy do wprowadzenia

### App shell

Stosowany na prywatnych ekranach aplikacyjnych:

- `/trener-pamieci`,
- aktywna sesja nauki,
- aktywna sesja znakow,
- potencjalnie wynik sesji, jesli ekran jest kontynuacja flow nauki.

Elementy:

- wysokosc top bara okolo 56 px,
- lewy przycisk powrotu 40-44 px,
- tytul maksymalnie 1 linia,
- prawy kontekst: kategoria, postep albo licznik,
- brak globalnego footera,
- dolne CTA w thumb zone, jesli ekran ma jedna glowna akcje.

### Web shell

Stosowany na:

- publicznych stronach,
- cenniku,
- auth,
- checkout,
- profilu,
- moderatorze.

Elementy:

- globalny `SiteHeader`,
- globalny footer tam, gdzie nie przeszkadza,
- formularze/karty responsive,
- brak app-like ukrywania menu.

### Tap targety

Minimalne:

- glowny przycisk: 52-56 px wysokosci,
- drugorzedny przycisk: 44-48 px,
- ikona powrotu/menu: 40-44 px,
- odpowiedzi w quizie: minimum 56 px albo wiecej, gdy tekst jest dluzszy.

### Tekst

Na pierwszym ekranie mobile:

- maksymalnie jeden mocny naglowek,
- jedno krotkie zdanie pomocnicze albo zadne,
- zadnych dlugich opisow przed glowna akcja,
- dluzsze uzasadnienia dopiero po odpowiedzi albo po kliknieciu.

### Przestrzen i scroll

Zasady:

- `body`/main nie moze miec przypadkowego poziomego scrolla,
- dopuszczalny jest kontrolowany poziomy scroll w tabach, ale tylko wewnatrz komponentu,
- kazdy fixed bottom dock wymaga spacera,
- na mobile unikamy kart w kartach,
- dla aktywnych quizow preferujemy jeden ekran pracy i przewidywalny dolny panel.

### Kolor i hierarchia

Utrzymujemy obecny dojrzaly kierunek:

- biel,
- jasne szarosci,
- mocny granat/niebieski `#023ea4` / `#0d47a1`,
- czerwien tylko dla bledu,
- zielony tylko dla poprawnej odpowiedzi,
- zolty/amber bardzo oszczednie.

## Proponowane komponenty / ekstrakcje

Nie trzeba od razu robic duzego design systemu. Wystarczy kilka malych komponentow lub helperow.

### `MobileAppBar.vue`

Cel: jeden standard top bara dla aplikacyjnych ekranow mobile.

Props:

- `title`,
- `backHref`,
- `contextLabel`,
- opcjonalnie `progressLabel`.

Uzycie:

- trener pamieci,
- znaki drogowe - sesja,
- wynik nauki, jesli przejdzie na app-shell,
- potencjalnie aktywna nauka.

### `MobileBottomAction.vue`

Cel: jeden standard dolnego CTA.

Props:

- `label`,
- `processingLabel`,
- `disabled`,
- `variant`,
- `href` albo submit slot.

Uzycie:

- trener pamieci start,
- znaki `Dalej` / wynik,
- wynik sesji CTA,
- ewentualnie start z `/nauka` na mobile.

### `MobileProgressBar.vue`

Cel: spojnosc paskow postepu.

Uzycie:

- znaki,
- klasyczna nauka,
- wynik,
- onboarding/demo.

### Wspolne klasy albo male component wrappers

Zamiast przenosic wszystko do komponentow, mozna tez dodac kilka utility patternow w CSS:

- `.mobile-app-screen`,
- `.mobile-app-bar`,
- `.mobile-bottom-action`,
- `.mobile-safe-bottom-spacer`.

Decyzja techniczna do podjecia w Sprincie 0: komponenty Vue beda czytelniejsze tam, gdzie jest routing i propsy; utility classes beda lepsze dla czystego layoutu.

## Sprinty

### Sprint 0 - Audyt bazowy i standard mobile

Cel:

- przygotowac fundament, zanim zaczniemy przebudowe ekranow.

Pre-sprint scan:

- sprawdzic aktualne zachowanie `AuthenticatedLayout`, `GuestLayout`, `SessionExamLayout`, `SessionZenLayout`,
- sprawdzic, gdzie obecnie ukrywamy `SiteHeader` i `SiteFooter`,
- sprawdzic, czy mamy juz lokalne mobile app-shell patterns w `ReviewQueue/Index.vue` i `StudySessions/*`,
- spisac, ktore route names powinny dostac app shell, a ktore web shell.

Zakres:

- stworzyc baseline screen checklist dla:
  - 360x740,
  - 390x844,
  - 430x932,
  - 768x1024,
- opisac decyzje: kiedy `SiteHeader`, kiedy `MobileAppBar`,
- zidentyfikowac strony z footerem, ktory nie powinien byc widoczny na mobile,
- przygotowac liste flow do Playwright.

Pliki do przegladu:

- `resources/js/Layouts/AuthenticatedLayout.vue`,
- `resources/js/Layouts/GuestLayout.vue`,
- `resources/js/Layouts/SessionExamLayout.vue`,
- `resources/js/Layouts/SessionZenLayout.vue`,
- `resources/js/Components/SiteHeader.vue`.

Ryzyko:

- niskie, jesli w tym sprincie glownie dokumentujemy i ewentualnie dodajemy male komponenty bez podpinania ich wszedzie.

Definition of done:

- dokument planu gotowy,
- lista ekranow i viewportow gotowa,
- decyzja app shell vs web shell zapisana,
- brak zmian funkcjonalnych w flow nauki.

### Sprint 1 - `/nauka` jako mobilny hub

Cel:

- zrobic z `/nauka` szybki launcher na telefonie.

Pre-sprint scan:

- trasa: `GET /nauka`, route name `session.index`,
- controller: `SessionPageController`,
- komponent: `resources/js/Pages/Session/Index.vue`,
- sprawdzic propsy: kategoria, dostep produktu, `learning_overview`, PJM, taby trybow, dzialy,
- sprawdzic formularze: `sessionForm`, `globalIncorrectForm`, profile/activation forms,
- sprawdzic akcje: start klasycznej nauki, Zen, egzamin, znaki, trener pamieci, ranking, PJM, aktywacja pelnej nauki,
- sprawdzic warianty: konto pelne B, konto pelne C, PJM starter, brak pelnego dostepu,
- wskazac elementy mobile-risk: taby `overflow-x-auto`, konfigurator dzialow, dropdown dzialow, szybki start z dzialu.

Stan kodu:

- `resources/js/Pages/Session/Index.vue` ma juz redesign desktop/web z pasem "Nastepny krok", tabami trybow i konfiguratorami.
- Na mobile nadal mamy sporo tresci, taby poziome i konfiguracje w jednym ciagu.

Plan:

1. Zachowac desktop bez duzych zmian.
2. Dodac mobilny wariant pierwszego ekranu:
   - rekomendowany krok jako pierwszy,
   - tryby jako prosta lista/segmenty,
   - kafelki w kolejnosci: Nauka klasyczna, Zen mode, Trener pamieci, Egzamin, Znaki drogowe, Ranking,
   - start najwazniejszej akcji bez przechodzenia przez caly konfigurator.
3. Dla trybow wymagajacych konfiguracji pokazac ustawienia dopiero po wyborze.
4. Ukryc teksty pomocnicze, ktore nie pomagaja w decyzji.
5. Upewnic sie, ze user PJM starter nadal widzi glowny kafel PJM i zablokowane tryby platne.

Testy:

- `/nauka` konto pelne B,
- `/nauka` konto pelne C,
- `/nauka` PJM starter,
- start klasycznej nauki,
- wejscie do znakow,
- wejscie do trenera pamieci,
- wejscie do egzaminu.

Ryzyko:

- srednie, bo `Session/Index.vue` uruchamia sesje i ma logike dostepu.

Guardrails:

- nie zmieniac endpointow,
- nie zmieniac payloadu `sessionForm`,
- nie zmieniac blokady kategorii,
- nie zmieniac `canUseFullProduct` i `isPjmStarterMode`.

Aktualny pre-sprint scan 2026-05-15:

- trasa i middleware: `GET /nauka`, route `session.index`, `auth` + `verified`;
- controller: `app/Http/Controllers/SessionPageController.php`;
- komponent: `resources/js/Pages/Session/Index.vue`;
- formularze w komponencie: `sessionForm` wysyla POST do `study-sessions.store`, `globalIncorrectForm` wysyla POST do tego samego endpointu z `question_status = incorrect`, `categoryForm` sluzy tylko do aktualizacji profilu;
- linki/akcje bez formularza: `session.traffic-signs`, `/trener-pamieci`, `session.ranking`, `pjm_module.href`, `access.activate`;
- warianty specjalne: PJM starter wymusza sciezke PJM i blokuje pozostale tryby, brak pelnego dostepu kieruje do aktywacji, konto pelne widzi wszystkie tryby;
- pliki dozwolone w Sprincie 1: `resources/js/Pages/Session/Index.vue`, `resources/js/Layouts/AuthenticatedLayout.vue`, `docs/MOBILE-VIEWS-IMPLEMENTATION-PLAN.md`;
- pliki tylko do odczytu: `routes/web.php`, `app/Http/Controllers/SessionPageController.php`, serwisy kontekstu nauki i dostepu;
- nie ruszac: endpointow startu sesji, route names, zasad blokady PJM/full access, kategorii docelowej, logiki wyboru pytan;
- app shell vs web shell: `/nauka` zostaje hubem w `AuthenticatedLayout`; na mobile dostaje kompaktowy launcher, ale bez ukrywania globalnej nawigacji w tym sprincie;
- ryzyko: srednie, bo komponent startuje sesje i ma kilka trybow; zmiana ma byc ograniczona do warstwy prezentacji i ponownego uzycia istniejacych metod;
- rollback: commit startowy `4c47e2e docs: plan mobile views rollout`, plus niecommitowana aktualizacja dokumentacji pre-sprint jako punkt kontroli;
- flow do sprawdzenia po zmianie: wejscie na `/nauka` na 360/390/430 px, brak poziomego scrolla, wybor trybow, start klasycznej nauki, start egzaminu, wejscie do znakow, wejscie do trenera, link aktywacji, brak odblokowania trybow dla PJM starter.

Status 2026-05-15:

- wykonano mobilny launcher `/nauka` w `resources/js/Pages/Session/Index.vue`;
- desktopowy hero, taby i konfigurator zostaly zachowane od breakpointu `md`;
- mobile pokazuje rekomendowany krok, siatke trybow i kompaktowy panel wybranej sciezki;
- klasyczna nauka i Zen uzywaja dalej `sessionForm`, a bledy ze wszystkich dzialow dalej uzywaja `globalIncorrectForm`;
- direct links dla znakow, trenera, rankingu i PJM pozostaly na istniejacych trasach;
- potwierdzono `npm run build`;
- potwierdzono Playwright: 360x740, 390x844, 430x932 bez poziomego overflow i z widocznym mobilnym launcherem;
- potwierdzono Playwright: 1280x900 ukrywa mobile launcher i pokazuje desktopowy hero oraz taby.

Korekta UX 2026-05-15:

- usunieto duzy mobilny blok "Nastepny krok", bo dublowal kafelek trybu i spychal wybor nizej;
- rekomendowany tryb na mobile jest teraz sygnalizowany subtelna ramka kafelka, bez dodatkowego tekstu nad launcherem;
- stopka w `AuthenticatedLayout` jest ukryta na mobile tylko dla route `session.index`; desktop `/nauka` nadal pokazuje stopke;
- potwierdzono `npm run build`;
- potwierdzono Playwright: 390x844 zaczyna widok od kafelkow, nie pokazuje hero, nie pokazuje widocznej stopki i nie ma poziomego overflow;
- potwierdzono Playwright: 1280x900 nadal pokazuje desktopowy hero, desktopowy uklad i stopke.

Korekta layoutu mobile 2026-05-15:

- usunieto efekt "box w boxie" na telefonie: launcher jest jedna biala powierzchnia z sekcjami, zamiast kart w kartach;
- usunieto dodatkowy top gap wynikajacy z `space-y-6` po ukrytym desktopowym hero;
- zmniejszono boczne marnowanie miejsca przez rozciagniecie mobilnego launchera do szerokosci ekranu telefonu;
- usunieto stale rekomendacyjne podswietlenie kafelka Trenera pamieci; kafelki podswietlaja sie tylko po zaznaczeniu;
- potwierdzono `npm run build`;
- potwierdzono Playwright: 390x844, brak hero, brak widocznej stopki, brak poziomego overflow, top panel zaczyna sie od razu pod headerem;
- potwierdzono Playwright: 1280x900 nadal pokazuje desktopowy hero i stopke.

Korekta konfiguracji klasycznej nauki mobile 2026-05-15:

- uproszczono statusy w mobilnym panelu klasycznej nauki do `Wszystkie z tego dzialu` i `Z bledami`;
- przeniesiono wybor `Dzial` przed statusy, bo uzytkownik najpierw wybiera material, a dopiero potem zakres powtorki;
- usunieto z mobilnego wariantu klasycznej nauki etykiete `Wybrana sciezka` i boks `Zestaw`, bo dublowaly informacje i wzmacnialy efekt panelu w panelu;
- zamieniono mocny blok `Bledy ze wszystkich dzialow` na kompaktowa akcje alternatywna pod glownym startem;
- ukryto na mobile zaawansowane ustawienia kolejnosci i zakresu pytan, bo obciazaly pierwszy ekran decyzji;
- desktopowy konfigurator nadal pokazuje pelny zestaw opcji;
- potwierdzono `npm run build`;
- potwierdzono Playwright: 390x844 bez poziomego overflow, bez widocznej stopki, z dwoma statusami klasycznej nauki i kompaktowym CTA do bledow ze wszystkich dzialow;
- potwierdzono Playwright: 1280x900 ukrywa mobile launcher, pokazuje desktopowy konfigurator i stopke.

Korekta kolejnosci kafelkow 2026-05-15:

- ustawiono kolejnosc trybow w launcherze: `Nauka klasyczna`, `Zen mode`, `Trener pamieci`, `Egzamin`, `Znaki drogowe`, `Ranking`;
- zachowano osobny kafel `PJM` na poczatku tylko dla kont PJM starter, bo ten wariant nadal wymusza sciezke PJM i blokuje pozostale tryby.

Re-audyt Sprintu 1 2026-05-16:

- branch: `codex/mobile-views-polish`, status gita przed re-audytem czysty;
- `resources/js/Pages/Session/Index.vue` ma mobilny launcher w sekcji `md:hidden`, a desktopowy hero/konfigurator zostaje poza mobile;
- `learningPathTabs` utrzymuje docelowa kolejnosc kafelkow: `Nauka klasyczna`, `Zen mode`, `Trener pamieci`, `Egzamin`, `Znaki drogowe`, `Ranking`;
- wyjatek PJM zostaje zachowany: kafel `PJM` jest dokladany na poczatek tylko dla kont starter PJM;
- `mobileClassicStatusOptions` ogranicza mobile do `Wszystkie z tego dzialu` i `Z bledami`;
- `globalIncorrectForm` nadal wysyla osobny start sesji z `question_status = incorrect`, wiec akcja `Bledy ze wszystkich dzialow` nie ingeruje w zwykly `sessionForm`;
- linki do `Znaki drogowe`, `Trener pamieci`, `Ranking`, `PJM` i `Aktywuj pelna nauke` ida po istniejacych trasach;
- `AuthenticatedLayout.vue` ukrywa stopke na mobile dla `session.index`, ale nie zmienia desktopowego ukladu `/nauka`;
- wniosek: Sprint 1 jest domkniety funkcjonalnie. Kolejne zmiany w `/nauka` powinny byc tylko drobnymi korektami mobile albo regresja po innych sprintach.

### Sprint 2 - Aktywna nauka `/nauka/teraz`

Cel:

- aktywna sesja ma byc szybka, czytelna i nie zaslaniac pytan/mediow.

Pre-sprint scan:

- trasa: `GET /nauka/teraz`, route name `study-sessions.current`,
- controller: `StudySessionController::current`,
- komponenty: `resources/js/Pages/StudySessions/Show.vue` oraz dla egzaminu `StudySessions/Exam.vue`,
- sprawdzic propsy sesji, pytan, wynikow, `publicDemo`, `pjmCompletion`, `completionTiming`,
- sprawdzic zapis odpowiedzi: `StudySessionAnswerController::storeCurrent`, lokalne odpowiedzi, public demo answer routes,
- sprawdzic media: `QuestionImageWithAnnotations`, `QuestionVideoFrameWithAnnotations`, player controls, PJM assets,
- sprawdzic tryby: klasyczna nauka, Zen, egzamin, PJM, trener pamieci, public demo,
- sprawdzic mobile-risk: fixed bottom dock, side panels, topic handles, answer dock spacer, `safe-area`, play overlay, adnotacje.

Stan kodu:

- `StudySessions/Show.vue` ma rozbudowany system klas dla mobile, bottom dock, side panels, handles, media i adnotacje.
- `StudySessions/Exam.vue` ma osobny mobile exam layout i bottom dock.

Plan:

1. Najpierw nie ruszac logiki odpowiedzi.
2. Zrobic wizualny audyt:
   - pytanie z obrazem,
   - pytanie z wideo,
   - pytanie TAK/NIE,
   - pytanie ABC,
   - tryb klasyczny,
   - Zen,
   - PJM,
   - trener pamieci.
3. Ujednolicic top context:
   - postep,
   - licznik,
   - powrot/wyjscie,
   - bez zbednych informacji.
4. Sprawdzic media:
   - button play nie moze zaslaniac kluczowych detali,
   - adnotacje musza byc widoczne,
   - koncowka filmu i caly film dalej dzialaja jak ustalono.
5. Sprawdzic bottom answer dock:
   - nie przykrywa odpowiedzi,
   - ma spacer,
   - ma safe-area,
   - nie skacze po odpowiedzi.
6. Uzasadnienia:
   - media po lewej, tekst po prawej tam, gdzie to ustalono,
   - na bardzo malych ekranach bez uciekajacego contentu.

Testy:

- `npm run build`,
- pytanie z obrazem,
- pytanie z wideo,
- odpowiedz poprawna,
- odpowiedz bledna,
- "nie wiem" w trenerze pamieci,
- zakonczenie sesji,
- powrot z wyniku.

Ryzyko:

- wysokie, bo `StudySessions/Show.vue` jest najbardziej wspoldzielonym komponentem.

Guardrails:

- male commity,
- po kazdej zmianie manualny Playwright,
- bez masowego refaktoru,
- kazdy wariant trybu sprawdzony przed mergem.

Re-audyt Sprintu 2 2026-05-16:

- komponent `resources/js/Pages/StudySessions/Show.vue` jest wspoldzielony przez klasyczna nauke, Zen, PJM, Trenera pamieci i public demo, wiec kazda zmiana musi byc minimalna i warunkowa;
- trasy aktywnej nauki sa w grupie `study.session.access`: `/nauka/teraz`, `/nauka/teraz/pytania`, `/nauka/teraz/odpowiedzi`, `/nauka/teraz/zakoncz`;
- `StudySessionAnswerController::storeCurrent` zwraca JSON z `answer`, `session`, `progress`, `completed`, `nextQuestion`, `nextQuestionNumber`, `pjmCompletion`, `reviewCompletion`;
- `StudySessionController::current` i batch routes buduja payload pytan z mediami, adnotacjami i wyjasnieniami, wiec UI nie powinien obchodzic tego kontraktu lokalnymi domyslami;
- mobile answer dock istnieje: `shouldPinMobileAnswerPanel`, `mobileAnswerDockWrapperClass`, `mobileAnswerDockSpacerStyle`;
- mobilne menu sesji istnieje: `showMobileSessionMenuTrigger`, `mobileSessionMenuSheetClass`, przyciski `Dzialy` i `Ustawienia nauki`;
- logika odpowiedzi i feedbacku jest dalej centralna: `selectedAnswerKey`, `correctAnswerKey`, `answerOptionStateClass`, `examLikeAnswerOptionClass`;
- znany punkt ryzyka: w trybach instant feedback wybrana odpowiedz moze pokazac stan neutralny, dopoki backend/lokalny wynik nie ustawi `currentAnswerResult`;
- media i adnotacje w aktywnej sesji sa nadal krytyczne: `QuestionImageWithAnnotations`, `QuestionVideoFrameWithAnnotations`, player controls i tryb koncowki filmu;
- wynik sesji i aktywna sesja sa w tym samym pliku, dlatego zmiany przy odpowiedziach moga przypadkiem dotknac ekran podsumowania;
- wniosek: przed wejsciem w Sprint 2 robimy najpierw wizualna regresje mobile `/nauka/teraz`, potem poprawiamy tylko jeden problem naraz.

Mikro-krok Sprintu 2 2026-05-16:

- wykonano regresje mobile 390x844 na koncie `smoke@local.test`: `/nauka` -> start klasycznej nauki -> `/nauka/teraz`;
- znaleziono drobny problem UX: nieaktywny przycisk `Nastepne pytanie` w dolnym doku mobile mial niebieskie tlo i wygladal jak aktywne CTA;
- poprawiono etykiety na `Przejdz do odpowiedzi` -> `Przejdź do odpowiedzi` i `Nastepne pytanie` -> `Następne pytanie`;
- dla exam-like shell nieaktywny primary action ma teraz neutralny kolor (`disabled:bg-[#f3f4f6]`, `disabled:text-[#9ca3af]`) zamiast przyciemnionego niebieskiego;
- nie zmieniono logiki odpowiedzi, routingu, zapisow backendowych ani autoadvance;
- potwierdzono `npm run build`;
- potwierdzono Browser/Playwright 390x844: przed odpowiedzia przycisk `Następne pytanie` jest neutralny i nie przyciaga uwagi jak glowna akcja.

Mikro-krok Sprintu 2 2026-05-16, czystosc widoku telefonu:

- podczas regresji na koncie admin `smoke@local.test` wykryto, ze przyciski `Edytuj pytanie`, `Edytuj wyjaśnienie`, `Edytuj grafikę` zabieraja miejsce w telefonowym trybie nauki i utrudniaja ocene realnego widoku kursanta;
- dodano `canShowInlineEditControls`, ktore zostawia inline edit dla admina na desktopie/tablecie, ale ukrywa te akcje na najwezszym widoku telefonu (`useShortMobileSessionLabels`);
- nie zmieniono uprawnien admina ani funkcji edycji, tylko widocznosc pomocniczych przyciskow w telefonowym playerze;
- potwierdzono `npm run build`;
- potwierdzono Browser/Playwright 390x844: pytanie i reczne `Pokaż wyjaśnienie` sa czytelniejsze, a uklad miniatury znaku po lewej i tekstu po prawej zostaje zachowany.

Mikro-krok Sprintu 2 2026-05-16, chwilowy kolor odpowiedzi:

- diagnoza: w `examLikeAnswerOptionClass` wybrana odpowiedz w trybie instant feedback dostawala niebieski stan zanim backend zwrocil `currentAnswerResult`;
- to tlumaczylo krotkie "niebieskie" migniecie przy wolniejszym zapisie odpowiedzi, mimo ze docelowo odpowiedz powinna byc zielona albo czerwona;
- zmieniono tylko stan oczekiwania w trybie instant feedback na neutralny szary;
- po zwrocie wyniku klasy dla poprawnej i blednej odpowiedzi pozostaja bez zmian;
- nie zmieniono logiki wyboru odpowiedzi, `selectedAnswerKey`, zapisu odpowiedzi ani autoadvance;
- potwierdzono `npm run build`.

Mikro-krok Sprintu 2 2026-05-16, top bar telefonu:

- diagnoza UX: top bar aktywnej nauki na telefonie wygladal jak "box w boxie" - zewnetrzna karta, wewnetrzne kafelki `PYT/KAT` i przyciski akcji zabieraly za duzo miejsca na pierwszym ekranie;
- zmiana ograniczona do `StudySessions/Show.vue` i tylko do exam-like shell na szerokosci telefonu/tabletu ponizej 1024 px;
- na mobile top bar jest teraz plaskim paskiem z dolna linia, bez zewnetrznej karty, cienia i ramek wokol statystyk;
- desktop zachowuje dotychczasowy wyglad przez klasy `min-[1024px]`, wiec zmiana nie powinna ruszyc ukladu desktopowego;
- nie zmieniono logiki `Opcje`, `Kończę naukę`, licznika pytan, kategorii ani routingu sesji;
- potwierdzono `npm run build`;
- potwierdzono Playwright 390x844 i 1366x768: mobile jest plaski, a desktop zachowuje klasyczny top bar z ramkami.
- po korekcie wizualnej zwiekszono mobile padding top bara i usunieto efekt przyklejenia do lewej krawedzi, nadal bez powrotu do zagniezdzonego boxa.

Mikro-krok Sprintu 2 2026-05-16, media edge-to-edge na telefonie:

- diagnoza UX: odtwarzacz/medium w aktywnej nauce na telefonie tracil szerokosc przez zewnetrzna ramke workspace oraz dodatkowy `px-4` w chromie media;
- dodano warunek `useEdgeToEdgeSessionMedia` tylko dla exam-like shell, widoku ponizej 640 px i pytan z medium;
- na telefonie sekcja media wychodzi teraz do krawedzi kontenera aplikacji (`-mx-3`), a padding chromu media spada do zera;
- dla tego wariantu usunieto ramke bezposrednio z medium, aby obraz/wideo wykorzystalo maksymalna szerokosc ekranu;
- nie zmieniono logiki odtwarzacza, adnotacji, przyciskow `Cały film` / `Końcówka`, odpowiedzi ani widoku desktopowego;
- potwierdzono `npm run build`;
- potwierdzono Playwright 390x844: wideo wykorzystuje pelna szerokosc telefonu, a przyciski filmu i adnotacje pozostaja na miejscu.

Mikro-krok Sprintu 2 2026-05-16, treść pytania bez bocznej ramki telefonu:

- diagnoza UX: po rozszerzeniu media nadal zostawala boczna ramka rodzica `workspaceShellClass`, przez co `Treść pytania` wygladala jak osobny box w boxie i tracila piksele po bokach;
- dodano `isNarrowPhoneViewport` oraz `useFlatPhoneExamWorkspace`, aby spłaszczenie dotyczylo tylko exam-like shell ponizej 640 px;
- na wąskim telefonie workspace aktywnej nauki nie ma juz bocznej ramki ani cienia, a prompt korzysta z pelnej szerokosci kontenera;
- inline przyciski admina (`Edytuj pytanie`, `Edytuj wyjaśnienie`, `Edytuj grafikę`) sa ukryte na wąskim telefonie, aby nie zabieraly miejsca kursantowi podczas nauki;
- desktop, tablet powyzej 640 px, logika pytan, odpowiedzi i adnotacji pozostaja bez zmian;
- potwierdzono `npm run build`;
- potwierdzono Playwright 390x844: prompt nie ma bocznej ramki, tekst korzysta z calej szerokosci telefonu, a bottom dock pozostaje bez zmian.

Mikro-krok Sprintu 2 2026-05-16, kompaktowe przyciski wideo telefonu:

- diagnoza UX: przyciski `Cały film` i `Końcówka` na telefonie zajmowaly zbyt duzo wysokosci i szerokosci na samym odtwarzaczu;
- zmniejszono tylko wariant mobile: nizszy `min-height`, mniejszy padding, mniejszy tekst, mniejszy gap i troche wezszy wrapper;
- zachowano czytelne etykiety, focus ring oraz oddzielne akcje pelnego filmu i koncowki;
- klasy `sm:*` przywracaja dotychczasowy rozmiar poza telefonem, wiec tablet i desktop nie sa zmieniane;
- potwierdzono `npm run build`;
- potwierdzono Playwright 390x844 na realnym pytaniu wideo: przyciski sa nizsze i zajmuja mniej miejsca na odtwarzaczu.

Korekta po weryfikacji wizualnej:

- pierwsze zmniejszenie bylo zbyt subtelne, bo przyciski nadal rozciagaly sie prawie na cala szerokosc odtwarzacza;
- na telefonie ustawiono stala, krotsza szerokosc przyciskow (`w-[7.25rem]`), nizsza wysokosc (`min-h-[2rem]`) i mniejszy wrapper (`max-w-[16.5rem]`);
- od `sm` w gore nadal wraca poprzedni rozmiar.
- druga korekta po feedbacku: docelowy mobile rozmiar to okolo `100x30px` (`w-[6.25rem]`, `min-h-[1.85rem]`), mniejsze ikony i wrapper `max-w-[13rem]`, z zachowaniem poprzedniego rozmiaru od `sm`.
- trzecia korekta po feedbacku: na telefonie tlo przyciskow jest mocno transparentne (`bg-[#111827]/28`), z mniejszym blur/cieniem, aby kontrolki nie zaslanialy kluczowych miejsc filmu; od `sm` wraca ciemniejszy wariant.

Mikro-krok Sprintu 2 2026-05-16, dolny dock akcji telefonu:

- diagnoza UX: dolne akcje `Poprzednie pytanie`, `Pokaż wyjaśnienie`, `Następne pytanie` mialy za dlugie etykiety i zbyt wysoki pasek na wąskim telefonie;
- skrócono etykiety tylko w pinned mobile dock exam-like shell ponizej 640 px: `Poprzednie`, `Wyjaśnienie`/`Pytanie`, `Następne`;
- zmniejszono mobile-only padding, gap i rozmiar tekstu przyciskow, aby pasek zajmowal mniej wysokosci bez zmiany logiki przejsc;
- pelne etykiety, desktop, tablet oraz inne tryby pozostaja bez zmian;
- nie zmieniono `handlePrimaryAction`, `moveToPreviousQuestion`, `toggleExplanationOnDemand`, zapisu odpowiedzi ani routingu sesji.

Mikro-krok Sprintu 2 2026-05-16, Zen mode telefonu:

- diagnoza UX: Zen mode na telefonie powinien korzystac z tych samych usprawnien co Nauka klasyczna mobile, ale bez przypadkowego objecia PJM, Trenera pamieci i wyniku sesji;
- dodano warunek `useFlatPhoneZenWorkspace` tylko dla `ui_shell=zen`, `mode=learn`, aktywnej sesji i szerokosci ponizej 640 px;
- na telefonie Zen ma plaski top bar, ukryty zbędny badge zrodla pytania, media edge-to-edge oraz pytanie bez dodatkowego boxowania;
- dolny dock Zen korzysta z kompaktowych etykiet `Poprzednie`, `Wyjaśnienie`/`Pytanie`, `Następne`, tak jak klasyczna nauka mobile;
- zmiany nie dotykaja desktopu/tabletu, PJM, trenera pamieci, public demo, logiki odpowiedzi, routingu ani zapisu odpowiedzi;
- potwierdzono `npm run build`;
- potwierdzono Browser/Playwright 390x844: start Zen z `/nauka`, widok `/nauka/teraz`, zamkniecie paneli pomocniczych i stan odpowiedzi po kliknieciu.

Korekta po feedbacku:

- przywrocono poprzedni wizualny styl top bara Zen, bo pierwsza wersja zbyt mocno zmieniala charakter naglowka;
- zostawiono tylko adaptacje na wąskim telefonie: mniej istotny badge zrodla pytania chowa sie ponizej 480 px, zeby `Opcje` i `Kończę naukę` nie byly scisniete;
- media, prompt i dolny dock pozostaja w wariancie mobile polish.

Mikro-krok Sprintu 2 2026-05-16, aktywny trener pamieci telefonu:

- diagnoza UX: po starcie z `/trener-pamieci` aktywna sesja `sr_review` wygladala jak stary Zen shell i nie korzystala z mobile polish;
- dodano osobny warunek `useFlatPhoneReviewWorkspace` dla `sr_review`/`review`, aktywnej sesji i szerokosci ponizej 640 px;
- wprowadzono wspolny `useFlatPhoneFocusWorkspace`, z ktorego korzystaja Zen mobile i aktywny trener pamieci mobile;
- trener pamieci dostaje edge-to-edge media, plaski prompt, kompaktowe etykiety dolnego docka i ukryty techniczny badge zrodla pytania na najwezszym telefonie;
- zachowano specyfike trenera: przycisk `Nie wiem`, server-driven next question, brak lokalnego prefetcha i brak zmian w logice `sr_review`;
- nie zmieniono klasycznej nauki, PJM, public demo, wyniku sesji ani desktopu/tabletu;
- potwierdzono `npm run build`;
- potwierdzono Playwright 390x844: `/trener-pamieci` -> `Start` -> `/nauka/teraz`, a potem klikniecie `Nie wiem`.

Mikro-krok Sprintu 2/3 2026-05-18, trener pamieci telefon - top bar i wynik:

- diagnoza UX: w aktywnej sesji trenera pamieci na telefonie przycisk `Opcje` wygladal jak zbyt mocny przycisk akcji i konkurowal z `Kończę naukę`;
- wyciszono `Opcje` tylko dla wąskiego mobile focus shell (`useFlatPhoneFocusWorkspace`), bez ukrywania dostepu do menu sesji;
- rozszerzono `useFlatPhoneCompletionSummary` na ukonczona sesje trenera pamieci, aby wynik korzystał z tego samego plaskiego mobile standardu co Nauka klasyczna i Zen;
- podsumowanie `Mapa po tej sesji` na telefonie ma teraz ciasniejsza typografie, neutralne kafle metryk, delikatniejszy blok nastepnego kroku i kompaktowe statystyki bez desktopowego boxowania;
- desktop/tablet, logika `sr_review`, kolejka powtorek, zapis odpowiedzi i routing pozostaja bez zmian.

Mikro-krok Sprintu 2 2026-05-18, aktywny egzamin telefonu:

- diagnoza UX: `StudySessions/Exam.vue` ma osobny mobile shell, ale pierwszy ekran egzaminu nadal byl zlozony z kilku zagniezdzonych ramek: top bar jako cztery kafle, karta pytania, ramka medium i ramka tresci;
- rozpoczęto osobny tor mobile dla egzaminu bez ruszania desktopowego layoutu i bez zmian w logice timera, faz pytania, zapisu odpowiedzi ani synchronizacji egzaminu;
- na telefonie top bar jest plaskim paskiem z dolna linia, statystyki nie sa juz osobnymi kaflami, `Opcje` jest tekstowym wejściem pomocniczym, a akcja konczenia egzaminu miesci sie jako kompaktowe `Zakończ`;
- po feedbacku przywrócono lekki charakter top bara przez subtelne obramowania statystyk i `Opcje`, bez powrotu do ciezkich kafli;
- glowna karta pytania i media na telefonie tracą zewnetrzne boxowanie i boczne ramki, zeby wykorzystac szerokosc ekranu podobnie jak w dopracowanej nauce klasycznej;
- przy okazji poprawiono polskie etykiety w `Exam.vue` widoczne w mobile flow, m.in. `Treść pytania`, `Materiał`, `Następne pytanie`, `Źródło`;
- zneutralizowano nieaktywny przycisk `Następne pytanie`, aby zablokowany stan nie wygladal jak aktywne zolte CTA;
- potwierdzono `npm run build`;
- potwierdzono Playwrightem viewport telefonu 390x844 dla aktywnego egzaminu `/nauka/teraz`: top bar z lekkimi obramowaniami, media bez zbednych ramek, neutralny disabled przy `Następne pytanie`;
- commit kontrolny: `596d804 Polish exam mobile session view`;
- desktop/tablet zachowuja dotychczasowe klasy, siatke, aside, ramki i rozmiary.

Mikro-krok Sprintu 3 2026-05-18, wynik egzaminu telefonu:

- diagnoza UX: `StudySessions/ExamResult.vue` byl czytelny desktopowo, ale na telefonie przenosil zbyt duzo desktopowego boxowania: zewnetrzne ramki, siatke z szarymi separatorami i zagniezdzone ramki mediow w przegladzie pytan;
- spłaszczono mobile wrapper wyniku przez `-mx-3/-my-3`, sekcje z obramowaniem tylko gora/dol i brak bocznych ramek na telefonie;
- metryki wyniku i podsumowanie sekcji na mobile sa teraz zwartymi kaflami 2 kolumny, a od `sm` wraca desktopowa struktura separatorow;
- CTA `Przejrzyj pytania` i `Wróć do nauki` maja na telefonie pełną szerokość i zachowują dotychczasowy desktop od `sm`;
- przeglad pytan na telefonie ma mniej zagniezdzone media i nizszy podglad, bez zmiany danych, adnotacji, wyjasnien ani listy bledow;
- poprawiono polskie komunikaty `statusLead` z zachowaniem dotychczasowej logiki zdane/niezdane.
- potwierdzono `npm run build`;
- potwierdzono Playwrightem viewport telefonu 390x844: aktywny egzamin -> `Zakończ` -> `/nauka/wynik/{id}`, pierwszy ekran wyniku ma spłaszczone CTA i metryki;
- commit kontrolny: `1c8cdb6 Polish exam mobile result view`.

Mikro-krok Sprintu 3 2026-05-16, gorny komponent podsumowania telefonu:

- diagnoza UX: pierwszy blok `/nauka/wynik/{id}` na telefonie nadal wygladal jak box w boxie przez zewnetrzna karte z ramka, cieniem i dodatkowym paddingiem;
- dodano `useFlatPhoneCompletionSummary`, aktywne tylko dla exam-like shell, ukonczonej sesji i szerokosci ponizej 640 px;
- na telefonie gorny summary shell nie ma zewnetrznej ramki/cienia, a body traci dodatkowe boczne wciecie;
- desktop, tablet i dalsze sekcje wyniku pozostaja bez zmian;
- nie zmieniono CTA, logiki follow-up sesji, wyboru dzialu, obliczania statystyk ani routingu wyniku.

Korekta Zen mode:

- rozszerzono ten sam warunek flat summary na Zen `mode=learn` ponizej 640 px;
- ekran podsumowania Zen korzysta teraz z tej samej plaskiej struktury mobile co Nauka klasyczna;
- nie objęto tym PJM, Trenera pamieci ani public demo;
- potwierdzono `npm run build`;
- potwierdzono Browser/Playwright 390x844: Zen `/nauka/teraz` -> `Kończę naukę` -> podsumowanie bez zewnetrznego box-in-box.

Mikro-krok Sprintu 3 2026-05-16, sciezka dzialow telefonu:

- diagnoza UX: desktopowy poziomy roadmap dzialow nie miescil sie naturalnie na telefonie i tworzyl bardzo szeroka strukture przewijania;
- dodano osobny wariant mobile-only pod `useFlatPhoneCompletionSummary`: kompaktowy blok `Ścieżka działów` pokazuje tylko pozycje, ogolny postep, aktualny dzial i nastepny dzial;
- pelny poziomy carousel z legenda, strzalkami i drag-scroll zostaje bez zmian poza telefonem;
- mobile nie renderuje juz szerokiej listy wszystkich dzialow, wiec nie generuje sztucznie ogromnej szerokosci na ekranie wyniku;
- nie zmieniono danych roadmapy, obliczen postepu, topic pickera ani logiki startowania kolejnych sesji.

Mikro-krok Sprintu 3 2026-05-16, wyróżnione pytanie wyniku telefonu:

- diagnoza UX: sekcja `featuredCompletionResult` byla kolejnym desktopowym boxem z ramka, cieniem i duzym paddingiem;
- dodano mobile-only klasy `featuredCompletion*`, korzystajace z tego samego warunku `useFlatPhoneCompletionSummary`;
- na telefonie sekcja ma plaski podzial linią, bez zewnetrznej karty, nizsze media i mniejszy naglowek pytania;
- odpowiedzi i wyjasnienie zostaja w tej samej kolejnosci oraz z tym samym kontraktem danych;
- desktop/tablet zachowuja dotychczasowy grid, ramke, cien i duza typografie.

Mikro-krok Sprintu 3 2026-05-16, lista pytan wyniku telefonu:

- diagnoza UX: `Pytania do poprawki` renderowaly na telefonie desktopowy wrapper z ramka/cieniem, a kazde pytanie bylo kolejnym boxem z cieniem;
- dodano mobile-only klasy `completionReview*`, aktywne tylko przez `useFlatPhoneCompletionSummary`;
- na telefonie lista jest plaska, rozdzielona liniami, z nizszym preview media i ciaśniejszą typografia pytania;
- zachowano te same dane, kolejność pytan, odpowiedzi uzytkownika, poprawna odpowiedz, wyjasnienia i przyciski admina;
- desktop/tablet zachowuja dotychczasowy wrapper, grid, karty, cienie i rozmiary mediow.

### Sprint 3 - Wynik sesji i CTA po nauce

Cel:

- wynik ma byc czytelny i prowadzic do kolejnego kroku bez chaosu przyciskow.

Pre-sprint scan:

- trasa: `GET /nauka/wynik/{studySession}`, route name `study-sessions.show`,
- controller: `StudySessionController::show`,
- komponent: `resources/js/Pages/StudySessions/Show.vue`,
- sprawdzic warunki: `showCompletionResults`, `isPublicDemoMode`, `isPjmMode`, `isReviewTrainerMode`,
- sprawdzic CTA: powrot, powtorz bledne, powtorz dzial, nastepny dzial, PJM continuation, demo register, demo pricing,
- sprawdzic przeglad wynikow: media, adnotacje, uzasadnienia, poprawne i bledne odpowiedzi,
- sprawdzic mobile-risk: liczba przyciskow, dlugie listy pytan, popup demo, modal legendy roadmapy.

Zakres:

- `/nauka/wynik/{id}`,
- public demo result,
- PJM completion,
- review trainer completion.

Plan:

1. Zrobic mobile hierarchy:
   - wynik,
   - co dalej,
   - opcjonalny przeglad pytan.
2. CTA:
   - primary: nastepny najlepszy krok,
   - secondary: powrot do panelu,
   - tertiary tylko gdy potrzebne.
3. Dla public demo zostawic tylko:
   - `Wroc do demo`,
   - `Zaloz konto i kontynuuj`,
   - dodatkowy popup demo jako osobny, zamykalny element.
4. Dla trenera pamieci nie pokazywac od razu dlugiej listy, jesli mobile ma byc szybki.

Ryzyko:

- srednie/wysokie, bo wynik jest w tym samym komponencie co aktywna sesja.

Guardrails:

- nie zmieniac liczenia wynikow,
- nie zmieniac route names,
- sprawdzic public demo osobno.

Status Sprintu 3 po przebiegu 2026-05-16:

- wykonane: gorny komponent podsumowania telefonu, kompaktowa sciezka dzialow, wyróżnione pytanie wyniku oraz pelna lista pytan wyniku;
- wszystkie zmiany zostaly ograniczone do mobile przez warunek `useFlatPhoneCompletionSummary`, wiec desktop i tablet zachowuja dotychczasowe wrappery, gridy, cienie i rozmiary;
- potwierdzono `npm run build` po zmianach w komponencie sesji;
- potwierdzono Playwrightem viewport telefonu 390x844 dla wyniku z pytaniami do poprawki oraz wyniku bez wyróżnionego pytania;
- potwierdzono Playwrightem desktop 1366x768: pelna sciezka dzialow, desktopowe karty i lista pytan nadal sa renderowane;
- pozostaje QA manualne: przejsc caly wynik `/nauka/wynik/{id}` od gory do dolu na telefonie, sprawdzic zwijanie dobrych odpowiedzi i modal wyboru dzialu, jesli uzytkownik otworzy go z podsumowania;
- nastepny bezpieczny krok: po QA Sprintu 3 przejsc do kolejnego ekranu z planu, bez laczenia tego z refaktorem logiki nauki.

Aktualizacja Sprintu 3 2026-05-18:

- wynik egzaminu `StudySessions/ExamResult.vue` zostal objety mobile polish jako osobny wariant, poniewaz nie korzysta z `useFlatPhoneCompletionSummary` z klasycznej nauki;
- aktywny ekran egzaminu `StudySessions/Exam.vue` ma juz pierwszy mobile pass: subtelny top bar, mniej ramek wokol pytania i poprawione etykiety;
- wynik egzaminu na telefonie ma spłaszczony pierwszy ekran, CTA pełnej szerokości, zwarte metryki i mniej zagniezdzone media w przegladzie pytan;
- testy: `npm run build`, Playwright 390x844 dla aktywnego egzaminu i dla wyniku po zakonczeniu egzaminu;
- najnowsze commity: `596d804` oraz `1c8cdb6`;
- pozostaje do omowienia/decyzji: czy wynik egzaminu ma na mobile pokazywac od razu pełny przeglad pytan, czy domyślnie tylko pytania wymagajace uwagi.

Checklist Sprintu 3:

- [x] Top summary wyniku na telefonie bez box-in-box.
- [x] Sciezka dzialow na telefonie jako kompaktowy blok zamiast szerokiego carouselu.
- [x] Wyróżnione pytanie wyniku na telefonie jako plaska sekcja.
- [x] Lista pytan wyniku na telefonie jako plaska lista z separatorami.
- [x] Aktywny egzamin na telefonie: pierwszy pass top bar/media/pytanie/dock.
- [x] Wynik egzaminu na telefonie: pierwszy pass top summary, CTA, metryki i przeglad pytan.
- [x] Desktop/tablet regresyjnie sprawdzone po zmianach.
- [ ] Pelne QA manualne mobile od gory do dolu na kilku wynikach sesji.
- [ ] Sprawdzenie modali/akcji dodatkowych uruchamianych z podsumowania na telefonie.

### Sprint 4 - Znaki drogowe mobile

Cel:

- modul znakow ma dzialac jak szybka aplikacja quizowa.

Pre-sprint scan:

- trasy:
  - `GET /nauka/znaki-drogowe`,
  - `POST /nauka/znaki-drogowe`,
  - `GET /nauka/znaki-drogowe/teraz`,
  - `POST /nauka/znaki-drogowe/odpowiedzi`,
  - `POST /nauka/znaki-drogowe/odpowiedzi/sync`,
  - `GET /nauka/znaki-drogowe/wynik/{trafficSignLearningSession}`,
- controller: `TrafficSignLearningController`,
- komponenty: `TrafficSignLearning/Index.vue`, `Show.vue`, `Result.vue`,
- sprawdzic batch sync: `queueAnswerSync`, `flushPendingAnswers`, `SYNC_BATCH_SIZE`, `SYNC_DEBOUNCE_MS`,
- sprawdzic tryby: recognition, similar_signs, description_to_sign,
- sprawdzic mobile-risk: flip card, wysokosc kontenera znaku/opisu, wysokosc odpowiedzi, auto-advance, sync error.

Stan kodu:

- `TrafficSignLearning/Show.vue` jest juz zoptymalizowany lokalnie: snapshot pytan, batch sync, auto-advance po poprawnej odpowiedzi.
- Ma flip card z wyjasnieniem, co jest dobrym wzorcem, ale wymaga dopracowania na mniejszych ekranach.

Plan:

1. `TrafficSignLearning/Index.vue`:
   - uproscic pierwszy ekran mobile,
   - kategorie jako przewidywalne listy/karty,
   - CTA zawsze widoczne,
   - brak nierownych kart przez dlugie nazwy.
2. `TrafficSignLearning/Show.vue`:
   - app bar z powrotem i postepem,
   - pytanie/znak jako glowna karta,
   - odpowiedzi stabilne wysokosciowo,
   - flip explanation bez przesuwania calego layoutu.
3. `TrafficSignLearning/Result.vue`:
   - proste CTA: powtorz, nastepna sesja, wroc do nauki.

Ryzyko:

- srednie. Logika jest bardziej odseparowana niz `StudySessions/Show.vue`, ale trzeba zachowac batch sync.

Guardrails:

- nie zmieniac `queueAnswerSync`,
- nie zmieniac `flushPendingAnswers`,
- nie zmieniac auto-advance timing bez decyzji produktowej.

### Sprint 5 - Trener pamieci mobile jako wzorzec

Cel:

- obecny mobile trener pamieci traktujemy jako proof of concept i standaryzujemy.

Pre-sprint scan:

- trasa: `GET /trener-pamieci`, route name `review-queue.index`,
- controller: `ReviewQueueController::index`,
- komponent: `resources/js/Pages/ReviewQueue/Index.vue`,
- layout: `AuthenticatedLayout` i warunek `isReviewQueuePage`,
- sprawdzic formularz startu: `reviewForm.post(route('study-sessions.store'))`,
- sprawdzic dane planu: `plan`, `telemetry`, `categories`, `daily_target_count`, `recommended_question_count`,
- sprawdzic mobile-risk: lokalny app-shell, ring, animacje, ukryty globalny header/footer, desktop fallback.

Stan kodu:

- `ReviewQueue/Index.vue` ma lokalny mobile app shell, ring, animacje i CTA.
- Globalny header/footer sa ukryte przez `AuthenticatedLayout` dla `review-queue.*`.

Plan:

1. Zdecydowac, czy wyciagamy:
   - `MobileAppBar`,
   - `MobileBottomAction`,
   - `MobileProgressRing`.
2. Jesli tak, przepiac trener pamieci jako pierwszy klient tych komponentow.
3. Desktop zostawic bez zmian.
4. Sprawdzic, czy `AuthenticatedLayout` powinien miec ogolniejszy mechanizm app-shell route.

Ryzyko:

- niskie/srednie. Widok jest juz dzialajacy, ale ekstrakcja komponentow moze dotknac layoutu.

Guardrails:

- najpierw snapshot current behavior,
- komponenty bez nowej biblioteki,
- zachowac `prefers-reduced-motion`.

Status 2026-05-16:

- wykonano pre-sprint scan dla `/trener-pamieci`: route `review-queue.index`, controller `ReviewQueueController::index`, komponent `resources/js/Pages/ReviewQueue/Index.vue`, formularz `reviewForm.post(route('study-sessions.store'))`, dane `plan`, `telemetry`, `categories` i `recommended_question_count`;
- potwierdzono, ze webowy `/trener-pamieci` pozostaje `plan-first` i nie laduje preview pytan z mediami przy zwyklym wejsciu;
- wyciagnieto z lokalnego mobilnego UI trzy male komponenty wzorcowe: `MobileAppBar`, `MobileBottomAction`, `MobileProgressRing`;
- `ReviewQueue/Index.vue` korzysta z tych komponentow tylko w widoku mobile, bez zmiany desktopowego fallbacku;
- zachowano jedno glowne CTA, okragly wskaznik planu dnia, ukryty globalny header/footer na mobile i `prefers-reduced-motion`;
- kierunek powrotu z mobilnego trenera zostal uproszczony: strzalka uzywa bezposredniego `Link` Inertia bez animowania calego wychodzacego ekranu, bo exit animation potrafila wygladac jak przyciecie podczas nawigacji;
- mobilny launcher `/nauka` zachowuje bardzo krotkie wejscie z lewej, z fallbackiem dla `prefers-reduced-motion`;
- nie zmieniono planera, payloadu startu sesji, route names, polityki `sr_review`, kategorii ani daily ledgeru;
- potwierdzono `npm run build`;
- potwierdzono Playwright: 390x844 bez poziomego overflow, bez widocznej stopki, top bar/ring/CTA w jednym ekranie;
- potwierdzono Playwright: klikniecie strzalki powrotu prowadzi do `/nauka`, a mobilny launcher jest widoczny bez poziomego overflow;
- potwierdzono Playwright: 1280x900 bez poziomego overflow, mobile shell ukryty, desktopowy widok i stopka widoczne.

### Sprint 6 - Public demo i publiczne strony

Cel:

- publiczny uzytkownik ma zobaczyc produkt bez wrazenia "desktop zmniejszony do telefonu".

Pre-sprint scan:

- trasy:
  - `GET /testy-na-prawo-jazdy`,
  - `GET /testy-na-prawo-jazdy/demo`,
  - demo answers/complete/restart,
  - publiczne SEO routes z `routes/web.php`,
- controller: `PublicDemoStudyController` oraz kontrolery publicznych stron,
- komponent demo: `StudySessions/Show.vue` z `publicDemo`,
- sprawdzic gate: `isPublicDemoGate`, `publicDemoGateDemoHref`, `publicDemoPricingHref`,
- sprawdzic result: `isPublicDemoMode`, popup completion prompt, register/activate routes,
- sprawdzic mobile-risk: glass overlay, Link vs anchor, popupy fixed, media w demo.

Zakres:

- `/testy-na-prawo-jazdy`,
- `/testy-na-prawo-jazdy/demo`,
- `/`,
- `/cennik`,
- `/oficjalna-baza-pytan-na-prawo-jazdy`,
- publiczne `/znaki-drogowe`.

Plan:

1. Public demo:
   - gate/glass musi wygladac dobrze na mobile,
   - przyciski bez `Link` powodujacego pomniejszenie strony,
   - podsumowanie demo bez zbednych CTA.
2. Publiczne SEO:
   - dopilnowac pierwszego viewportu,
   - CTA czytelne,
   - bez poziomego scrolla,
   - grafiki/media nie moga uciekac poza ekran.

Ryzyko:

- srednie dla demo, niskie dla stron SEO.

Guardrails:

- demo dzieli `StudySessions/Show.vue`, wiec testowac po zmianach sesji,
- publiczne strony nie powinny korzystac z app-shella.

Pre-sprint scan 2026-05-16:

- trasy public demo: `GET /testy-na-prawo-jazdy`, `GET /testy-na-prawo-jazdy/demo`, `POST /testy-na-prawo-jazdy/demo/answers`, `POST /testy-na-prawo-jazdy/demo/complete`, `POST /testy-na-prawo-jazdy/demo/restart`;
- controller: `app/Http/Controllers/PublicDemoStudyController.php`;
- serwisy: `PublicDemoSessionService`, `PublicDemoQuestionSetService`, `PublicDemoQuestionPayloadBuilder`;
- konfiguracja pakietu: `config/public_demo.php` z 20 pytaniami, 10 obraz + 10 wideo;
- komponent renderujacy: `resources/js/Pages/StudySessions/Show.vue`, wspoldzielony z klasyczna nauka, Zen, PJM, trenerem pamieci i wynikami;
- kluczowe warunki w komponencie: `isPublicDemoMode`, `usesFrozenPublicDemo`, `isPublicDemoGate`, `showPublicDemoCompletionPrompt`;
- endpointy demo sa odseparowane przez `props.publicDemo.routes.*`, a zamrozony pakiet uzywa lokalnego postepu i synchronizacji `complete` na koncu;
- pliki dozwolone w pierwszym kroku Sprintu 6: `StudySessions/Show.vue` i ta dokumentacja;
- pliki tylko do odczytu na tym etapie: trasy, controller, serwisy demo i `config/public_demo.php`;
- nie ruszac: wyboru pytan demo, zapisu odpowiedzi, payloadu `answers`, `complete`, logiki frozen packet, publicznych route names;
- ryzyko: srednie, bo UI demo siedzi w najwiekszym rendererze sesji; poprawki musza byc warunkowane przez public demo albo dotyczyc tylko modali/gate;
- rollback: commit `40e9c4a Extract mobile memory trainer shell`;
- flow do sprawdzenia: `/testy-na-prawo-jazdy` mobile gate 360/390 px, `/testy-na-prawo-jazdy/demo?fresh=1` mobile, zakonczenie demo i popup, desktop bez regresji.

Status 2026-05-16:

- rozpoczecie Sprintu 6 po commicie Sprintu 5;
- pierwsza poprawka jest ograniczona do UX public demo: etykieta konczenia demo mowi `Zakoncz demo`, a nie `Koncze nauke`;
- modal gate i popup po demo dostaly zabezpieczenie przed przycieciem na niskich telefonach: `overflow-y-auto` oraz limit `max-h` oparty o `100svh`;
- logika odpowiedzi, pakiet pytan, trasy i serwisy pozostaja bez zmian.
- potwierdzono `npm run build`;
- potwierdzono Browser 360x740: `/testy-na-prawo-jazdy` pokazuje gate bez ucietej tresci i bez poziomego overflow;
- potwierdzono Browser 360x740: `/testy-na-prawo-jazdy/demo?fresh=1` pokazuje przycisk `Zakoncz demo` i zachowuje dotychczasowy flow pytania.
- wykonano drugi scan publicznych stron: `/cennik`, `/oficjalna-baza-pytan-na-prawo-jazdy`, `/znaki-drogowe`;
- na `/znaki-drogowe` znaleziono mobile squeeze licznika kategorii w naglowku sekcji, bo uklad nie mial `flex-wrap`;
- poprawiono tylko publiczny widok `resources/views/traffic-signs/index.blade.php`, bez dotykania prywatnego modulu `/nauka/znaki-drogowe`.
- potwierdzono `npm run build` po poprawce publicznego Blade;
- potwierdzono Browser 390x844: `/znaki-drogowe` pokazuje licznik `16 grup znakow` pod opisem, bez scisnietej kolumny po prawej.
- wykonano scan `/` na 390x844: hero byl poprawny wizualnie, ale glowne CTA bylo wypchniete ponizej dlugiej listy argumentow;
- poprawiono mobile-only CTA w `resources/views/home/index.blade.php`: na telefonie przycisk `Rozpocznij nauke z programem` pojawia sie od razu po opisie, a desktopowy uklad pozostaje bez zmian.
- potwierdzono `npm run build`;
- potwierdzono Browser 390x844: `/` pokazuje glowne CTA w pierwszym ekranie mobile przed lista argumentow.
- po dodatkowej korekcie home hero zmniejszono top spacing pierwszej sekcji na mobile i desktop, aby `Oficjalny program Orly na Drodze` startowal blizej top bara;
- na mobile dodano kompaktowy podglad produktu nad glownym CTA, a duza kompozycja laptop+telefon zostala zachowana od breakpointu `sm`;
- potwierdzono `npm run build`;
- potwierdzono Browser: `/` na 390x844 pokazuje grafike produktu nad CTA, a 1440x900 zachowuje desktopowa kompozycje hero.

### Sprint 7 - Auth, checkout, profil, moderator

Cel:

- formularze i widoki konta maja byc bez tarcia na telefonie.

Pre-sprint scan:

- trasy z `routes/auth.php`, checkout routes, profile routes, moderator routes,
- komponenty: `Auth/*`, `Checkout/*`, `Access/Activate.vue`, `Profile/Edit.vue`, `Moderator/Accounts/Index.vue`,
- sprawdzic formularze: login, register, verify email resend, checkout confirm/cancel, profile update, moderator account create,
- sprawdzic walidacje i komunikaty bledow,
- sprawdzic mobile-risk: dwukolumnowe gridy, sticky aside, tabele `overflow-x-auto`, widocznosc submit buttona po otwarciu klawiatury.

Zakres:

- `/login`,
- `/register`,
- `/verify-email`,
- `/forgot-password`,
- `/checkout/*`,
- `/aktywuj-dostep`,
- `/profile`,
- `/moderator/konta`.

Plan:

1. Auth:
   - ograniczyc zbyt szerokie dwukolumnowe layouty,
   - sprawdzic klawiature mobile i widocznosc submit buttona,
   - komunikaty bledow blisko pol.
2. Checkout:
   - cena/plan i CTA zawsze czytelne,
   - brak zbyt dlugich kart.
3. Profil/moderator:
   - nawigacja sekcji jako sticky/scrollable list,
   - tabele tylko jako kontrolowany horizontal scroll.

Ryzyko:

- niskie/srednie.

Guardrails:

- nie zmieniac walidacji,
- nie zmieniac endpointow,
- testowac wpisywanie i submit.

## Kolejnosc rekomendowana

1. Sprint 0: standard i baseline.
2. Sprint 1: `/nauka`.
3. Sprint 2: `/nauka/teraz`.
4. Sprint 3: wynik sesji.
5. Sprint 4: znaki drogowe.
6. Sprint 5: ekstrakcja wzorca trenera pamieci.
7. Sprint 6: demo/public.
8. Sprint 7: auth/checkout/profil/admin.

Dlaczego tak:

- `/nauka` jest wejsciem do prywatnego produktu.
- `/nauka/teraz` jest najwazniejszym ekranem uzytkowym.
- wynik decyduje o kolejnym kroku i retencji.
- znaki i trener pamieci maja juz wlasne flow, wiec latwiej je dopracowac po ustaleniu standardu.
- public/auth/checkout sa wazne, ale mniej ryzykowne wzgledem logiki nauki.

## Plan testow dla kazdego sprintu

### Automatyczne

- `npm run build`

Jesli sprint dotyka logiki backendowej albo propsow Inertia:

- wybrane testy feature/unit zwiazane z danym flow,
- przy wiekszej zmianie: pelniejszy zestaw testow backendowych.

### Playwright/manual

Viewporty:

- 360 x 740,
- 390 x 844,
- 430 x 932,
- 768 x 1024.

Checklist:

- brak poziomego scrolla na `document.documentElement.scrollWidth > innerWidth`,
- glowna akcja widoczna bez szukania,
- dolny fixed panel nie zaslania contentu,
- top bar/header nie przykrywa tresci,
- wszystkie przyciski maja minimum okolo 44 px wysokosci,
- tekst nie nachodzi na inne elementy,
- media nie uciekaja poza ekran,
- adnotacje sa widoczne,
- stan loading/processing jest jasny,
- `prefers-reduced-motion` nie psuje layoutu.

### Krytyczne flow do sprawdzenia

1. Logowanie na konto testowe.
2. `/nauka` -> start klasycznej nauki.
3. `/nauka/teraz` -> odpowiedz poprawna.
4. `/nauka/teraz` -> odpowiedz bledna -> uzasadnienie.
5. `/nauka/teraz` -> pytanie z obrazem.
6. `/nauka/teraz` -> pytanie z wideo.
7. `/nauka/wynik/{id}` -> CTA kolejnego kroku.
8. `/trener-pamieci` -> start.
9. `/nauka/znaki-drogowe` -> start mieszany.
10. `/nauka/znaki-drogowe/teraz` -> poprawna odpowiedz i auto-advance.
11. `/nauka/znaki-drogowe/teraz` -> bledna odpowiedz i flip explanation.
12. `/testy-na-prawo-jazdy` -> demo gate.
13. `/testy-na-prawo-jazdy/demo` -> przejscie do podsumowania.
14. `/login`, `/register`, `/checkout`.

## Zasady bezpieczenstwa wdrozenia

- Nie mieszac refaktoru UI z logika nauki.
- Nie zaczynac sprintu bez wypelnionego pre-sprint code scan.
- Nie zmieniac payloadow formularzy bez osobnego testu.
- Nie zmieniac route names.
- Nie wyciagac duzych fragmentow `StudySessions/Show.vue` w pierwszym przebiegu.
- Nie ukrywac globalnego headera route-by-route bez wpisania decyzji w plan.
- Nie dodawac nowej biblioteki animacji bez potrzeby.
- Kazdy sprint ma byc commitowalny osobno.
- Po kazdym sprincie zostawic czysty punkt powrotu.

## Definition of done calego etapu

Etap mobile uznajemy za domkniety, gdy:

- najwazniejsze flow nauki przechodzi na telefonie bez poziomego scrolla,
- `/nauka` dziala jako czytelny launcher,
- aktywna nauka nie zaslania mediow, pytania ani odpowiedzi,
- wynik sesji ma zrozumiale CTA,
- trener pamieci ma kompaktowy app-shell,
- znaki drogowe dzialaja szybko i stabilnie,
- public demo wyglada wiarygodnie na telefonie,
- auth/checkout/profil nie maja problemow z formularzami,
- `npm run build` przechodzi po kazdym sprincie,
- najwazniejsze viewporty sa sprawdzone manualnie lub Playwrightem.

## Rekomendowany pierwszy krok implementacyjny

Zaczac od Sprintu 0 i 1:

1. Dodac bardzo male wspolne komponenty albo utility patterny:
   - `MobileAppBar`,
   - `MobileBottomAction`,
   - opcjonalnie `MobileProgressBar`.
2. Nie podpinac ich od razu wszedzie.
3. Dopracowac mobile `/nauka` jako pierwszy pelny klient standardu.
4. Dopiero po zatwierdzeniu rytmu przejsc do `/nauka/teraz`.

To daje najlepszy stosunek bezpieczenstwa do efektu: szybko poprawiamy pierwszy ekran aplikacji, ale nie dotykamy jeszcze najbardziej ryzykownego renderera sesji.
