# Mobile /nauka dashboard redesign plan

## Status dokumentu

- Status: plan roboczy przed developmentem
- Zakres: tylko widok mobile dla `/nauka`
- Desktop: poza zakresem, nie zmieniamy obecnego widoku desktopowego
- Punkt odniesienia UX: załączona makieta mobile dashboardu z kartą "Kontynuuj naukę", szybkim startem, progresem i aktywnością
- Główna zasada: nie wycinamy całego PNG jako interfejsu; odtwarzamy widok jako komponenty aplikacji, a assety traktujemy selektywnie

## Cel

Przebudować mobilny widok `/nauka` z prostego launchera trybów w aplikacyjny dashboard nauki.

Użytkownik po wejściu na telefonie ma od razu wiedzieć:

- co powinien zrobić jako następny krok,
- gdzie kontynuować naukę,
- ile ma do powtórki,
- jaki ma ogólny postęp,
- jak wejść w główne tryby bez dodatkowego myślenia.

## Co jest poza zakresem

- Desktop `/nauka`
- Aktywne ekrany sesji `/nauka/teraz`
- Wyniki sesji `/nauka/wynik/{id}`
- Publiczne strony SEO
- Zmiana algorytmów nauki
- Zmiana payloadów formularzy startu sesji, chyba że zostanie osobno zatwierdzona

## Obecny stan kodu

| Obszar | Plik | Wniosek |
| --- | --- | --- |
| Widok `/nauka` | `resources/js/Pages/Session/Index.vue` | Komponent zawiera desktop i mobile w jednym pliku. Mobile jest obecnie launcherem z kafelkami i panelem konfiguracji. |
| Dane `/nauka` | `app/Http/Controllers/SessionPageController.php` | Controller zwraca kategorię, filtry, grupy działów, statusy, dostęp, `learning_overview.due_review_count` i PJM. |
| Szersze metryki | `app/Support/DashboardMetricsService.php` | Istnieją gotowe metryki: recent sessions, readiness, streak, answered today. Nie są jeszcze bezpośrednio podpięte do `/nauka`. |
| Trener pamięci | `resources/js/Pages/ReviewQueue/Index.vue` | Ma dobry wzorzec mobilnego app-feelingu i dziennego planu, ale nie powinien zostać skopiowany 1:1. |
| Znaki drogowe | `resources/js/Pages/TrafficSignLearning/Index.vue` | Ma dane i grafiki znaków, które mogą zasilić sekcję "Popularne działy" albo szybki start. |

## Główna decyzja produktowa

### Decyzja D1: czy `/nauka` mobile ma być pełnym dashboardem?

Rekomendacja: tak.

Uzasadnienie:

- użytkownik na telefonie oczekuje jednego jasnego wejścia do działania,
- obecny launcher jest poprawny technicznie, ale ma zbyt dużo konfiguracji na pierwszym ekranie,
- dashboard może prowadzić użytkownika bez pytania go o wszystkie parametry od razu.

Status: do akceptacji przed developmentem.

### Decyzja D2: bottom nav czy globalny header?

Opcje:

1. Zostawić globalny header i zrobić tylko dashboard.
2. Ukryć globalny footer, zostawić header.
3. Wprowadzić bottom nav tylko na `/nauka` i wybranych ekranach aplikacyjnych.

Rekomendacja na Sprint 1: opcja 2.

Nie wchodzimy od razu w bottom nav, bo to decyzja systemowa i może wejść w konflikt z aktywnymi sesjami. Najpierw robimy dashboard bez zmiany globalnej nawigacji.

Status: do decyzji.

### Decyzja D3: hero asset

Opcje:

1. Użyć istniejącego `resources/images/home/hero-main.webp`.
2. Stworzyć nowy asset `mobile-learning-hero.webp`.
3. Wyciąć fragment z dostarczonego PNG.

Rekomendacja: opcja 2.

Nie używać całego screena jako źródła UI. Jeśli potrzebujemy samochodu/drogi, tworzymy dedykowany lekki asset albo własną kompozycję graficzną.

Status: do decyzji.

### Decyzja D4: statystyki tylko realne czy placeholdery?

Rekomendacja: tylko realne dane.

Nie pokazujemy fikcyjnych wykresów, procentów i czasu nauki. Jeśli dane nie są dostępne tanio, sekcja wchodzi później.

Status: przyjęte jako zasada bezpieczeństwa.

## Docelowa architektura komponentów

### Nowy komponent główny

`resources/js/Pages/Session/Partials/MobileLearningDashboard.vue`

Rola:

- renderuje cały mobile dashboard,
- dostaje gotowe dane i callbacks/actions z `Index.vue`,
- nie zna szczegółów backendu,
- nie zmienia desktopowego renderu.

### Komponenty potomne

| Komponent | Rola | Sprint |
| --- | --- | --- |
| `MobileLearningGreeting.vue` | Odłożone po decyzji UX: nie wracamy z górnym greetingiem na pierwszy ekran mobile | później / tylko po decyzji |
| `ContinueLearningHero.vue` | Główna karta "Kontynuuj naukę / Nauka klasyczna"; tapnięcie otwiera modal ustawień, nie startuje od razu sesji | Sprint 1/2 |
| `QuickStartCards.vue` | Karty pozostałych trybów: Zen, Egzamin, Znaki, Ranking; Nauka klasyczna jest wyłącznie w hero, a Trener pamięci przechodzi do osobnego paska CTA gdy są pytania due | Sprint 1/2 |
| `LearningProgressCard.vue` | Ogólny postęp, poprawne/błędne, czas nauki, readiness | Sprint 3 |
| `MemoryTrainerStrip.vue` | Dodatkowy pasek CTA do trenera pamięci | Sprint 2 |
| `PopularTopicsCarousel.vue` | Działy albo znaki jako małe kafle z progresami | Sprint 4 |
| `RecentActivityCard.vue` | Ostatnia aktywność | Sprint 3 |
| `MobileBottomNav.vue` | Opcjonalna dolna nawigacja aplikacyjna | Sprint późniejszy, tylko po decyzji |

### Powiązany ekran: `/trener-pamieci` mobile

Po decyzji UX trener pamięci na telefonie ma działać jak osobny ekran aplikacyjny, a nie mobilna wersja desktopowego dashboardu.

Założenia:

- tylko mobile (`md:hidden`), desktop `/trener-pamieci` zostaje bez zmian,
- bez nowych query i bez zmian algorytmu; korzystamy z istniejącego `plan`,
- główne dane: `daily_target_count`, `completed_today_count`, `recommended_question_count`,
- procent w okręgu pokazuje prognozę po sesji, czyli `completed_today_count + recommended_question_count` względem planu dziennego,
- główne CTA nadal wysyła ten sam formularz `study-sessions.store` z `mode=sr_review`,
- grafika coacha jest assetem, a nie wyciętym całym screenem UI.

Asset:

| Asset | Ścieżka | Status |
| --- | --- | --- |
| Coach trenera pamięci | `resources/images/review/mobile-memory-coach.png` | Dodany z dostarczonej makiety jako lekki element ilustracyjny |

Ryzyka:

- za duży okrąg może nie mieścić się na bardzo niskich ekranach,
- grafika coacha nie może zdominować CTA,
- nie wolno dublować logiki liczenia planu po stronie frontu poza prostą prezentacją danych z `plan`.

## Kontrakt danych

Docelowo `SessionPageController` może zwrócić nowy prop:

```php
'learning_dashboard' => [
    'user' => [
        'display_name' => 'Kamil',
        'initials' => 'K',
        'avatar_url' => null,
    ],
    'today' => [
        'accuracy_percent' => 85,
        'answered_count' => 80,
        'study_streak' => 7,
    ],
    'continue_learning' => [
        'action' => 'classic',
        'eyebrow' => 'Kontynuuj naukę',
        'title' => 'Nauka klasyczna',
        'subtitle' => 'Znaki ostrzegawcze',
        'progress_percent' => 65,
        'remaining_count' => 12,
    ],
    'quick_start' => [],
    'progress' => [
        'readiness_score' => 78,
        'correct_count' => 1264,
        'incorrect_count' => 356,
        'study_time_minutes' => 1122,
    ],
    'weekly_activity' => [],
    'popular_topics' => [],
    'recent_activity' => [],
]
```

To jest propozycja docelowa, nie wszystko musi wejść w Sprint 1.

## Dane: źródła i ryzyka

| Dane | Możliwe źródło | Ryzyko | Decyzja |
| --- | --- | --- | --- |
| Imię użytkownika | `users.name` | Niskie | Można użyć od razu |
| Avatar | brak pewnego źródła | Średnie | Na start inicjały/placeholder |
| Skuteczność dzisiaj | `study_session_answers` lub `DashboardMetricsService` | Średnie, dodatkowe query | Tylko jeśli tanie/cached |
| Streak | `user_profiles.study_streak` albo `DashboardMetricsService` | Niskie/średnie | Użyć, jeśli profil jest załadowany |
| Due review | `learning_overview.due_review_count` | Niskie | Już dostępne |
| Continue learning | `recommendedStep` i topic counts | Niskie | Wykorzystać istniejącą logikę |
| Progres kursu | `UserReadinessService` albo progress counts | Średnie | Sprint 3, po pomiarze |
| Wykres tygodnia | agregacja sesji/odpowiedzi | Średnie/wysokie | Nie w Sprint 1 |
| Popularne działy | `group_options` | Niskie | Można policzyć po stronie frontu |
| Ostatnia aktywność | `DashboardMetricsService.recentSessions` | Średnie | Sprint 3, po pomiarze |

## Assety

### Wymagane

| Asset | Ścieżka docelowa | Status | Uwagi |
| --- | --- | --- | --- |
| Hero mobile dashboard | `resources/images/session/mobile-learning-hero.webp` | Do decyzji | Dedykowany asset, max ok. 100-180 KB |
| Hero mobile dashboard 2x | `resources/images/session/mobile-learning-hero@2x.webp` | Opcjonalne | Tylko jeśli nie pogorszy wydajności |

### Niewymagane, bo mamy alternatywy

| Element | Decyzja |
| --- | --- |
| Ikony trybów | Użyć lucide albo prostych istniejących symboli, nie wycinać z PNG |
| Znaki drogowe | Użyć obecnych plików z `public/traffic-signs/...` |
| Avatar | Inicjały/placeholder zamiast nowego assetu |
| Wykresy | CSS/SVG komponent, nie grafika |

### Czego nie wycinać z PNG

- tekstów,
- przycisków,
- kart,
- progress barów,
- wykresów,
- bottom nav,
- ikon systemowych,
- całego tła jako jednego obrazu.

Powód: responsywność, dostępność, rozmiar, brak kontroli nad stanami UI.

## Sprinty

### Sprint 0: pre-scan i decyzje

Status: w toku

Checklist:

- [x] Potwierdzić decyzję D1: pełny dashboard mobile.
- [x] Potwierdzić decyzję D2: na start bez bottom nav.
- [x] Potwierdzić decyzję D3: dedykowany hero asset.
- [x] Potwierdzić decyzję D4: tylko realne statystyki.
- [x] Sprawdzić aktualne propsy `SessionPageController`.
- [x] Sprawdzić, czy `DashboardMetricsService` można bezpiecznie wykorzystać bez spowolnienia `/nauka`.
- [ ] Zmierzyć obecny czas wejścia na `/nauka` dla konta testowego.

Ryzyko:

- dodanie zbyt wielu query do `/nauka`,
- rozlanie zmian na desktop.

Definition of done:

- [x] mamy decyzje,
- [x] mamy listę plików do zmiany,
- [x] wiemy, które dane wchodzą w Sprint 1.

Notatka 2026-05-18:

- Sprint 1 wchodzi bez nowych backendowych metryk i bez `DashboardMetricsService`, żeby nie spowolnić `/nauka`.
- Korzystamy z obecnych danych: `recommendedStep`, `learningPathTabs`, `learning_overview.due_review_count`, `group_options`, dostęp, PJM i obecne formularze.
- Dedykowany hero asset zostaje decyzją docelową, ale pierwszy pass może użyć istniejącego `learningHeroImage`; asset wymienimy później bez zmiany logiki.
- Lista plików do zmiany w Sprincie 1: `resources/js/Pages/Session/Index.vue`, nowy `resources/js/Pages/Session/Partials/MobileLearningDashboard.vue`, dokumentacja.

### Sprint 1: dashboard shell bez ciężkich metryk

Status: pierwszy pass wykonany

Zakres:

- stworzyć `MobileLearningDashboard.vue`,
- przenieść mobile render z `Index.vue` do nowego komponentu,
- dodać hero "Kontynuuj naukę",
- dodać szybki start,
- zachować obecne akcje startu nauki,
- nie zmieniać desktopu.

Checklist:

- [x] Mobile dashboard renderuje się tylko dla `md:hidden`.
- [x] Desktop render zostaje bez zmian.
- [x] Hero używa istniejącej logiki `recommendedStep`.
- [x] Quick start używa istniejących ścieżek: zen, exam, traffic-signs, ranking; classic jest w hero, a memory nie dubluje się, gdy widoczny jest pasek Trenera pamięci.
- [ ] Tryb PJM starter nadal działa i nie pokazuje płatnych trybów jako aktywnych.
- [ ] Konta bez pełnego dostępu nadal widzą blokady zgodnie z obecną logiką.
- [x] Nie zmieniamy payloadu `sessionForm`.

Pliki prawdopodobnie do zmiany:

- `resources/js/Pages/Session/Index.vue`
- `resources/js/Pages/Session/Partials/MobileLearningDashboard.vue`
- opcjonalnie `resources/js/Pages/Session/Partials/ContinueLearningHero.vue`
- opcjonalnie `resources/js/Pages/Session/Partials/QuickStartCards.vue`

Ryzyko:

- średnie, bo `Index.vue` ma logikę startu kilku trybów,
- niskie dla desktopu, jeśli zachowamy warunek mobile-only.

QA:

- [x] `/nauka` mobile 360x740
- [x] `/nauka` mobile 390x844
- [x] `/nauka` mobile 430x932
- [x] `/nauka` desktop bez zmian
- [ ] start nauki klasycznej
- [x] wybór Zen mode na mobile zmienia aktywną ścieżkę
- [ ] wejście do trenera pamięci
- [ ] start egzaminu
- [ ] wejście do znaków
- [ ] konto PJM starter
- [ ] konto bez pełnego dostępu

Notatka 2026-05-18:

- Dodano `resources/js/Pages/Session/Partials/MobileLearningDashboard.vue`.
- `Index.vue` zachowuje logikę i formularze, a mobile dashboard dostaje propsy oraz emituje istniejące akcje.
- Nie dodano nowych backendowych query ani nowych danych.
- Po pierwszym podglądzie usunięto górną orientację `Panel nauki / Czas na kolejny krok / Kategoria`, aby mobile zaczynał się od głównego hero i nie marnował pierwszego viewportu.
- Build: `npm run build` przeszedł.
- Playwright 360x740, 390x844, 430x932: brak poziomego scrolla i dashboard widoczny.
- Playwright 390x844: wybór Zen działa.
- Playwright desktop 1280x900: mobile dashboard nie jest widoczny.
- Do dalszego QA zostają konta PJM/locked i pełne kliknięcia startu każdego trybu.

### Sprint 2: szybki start i trener pamięci jako spokojny sygnał prowadzenia

Status: w toku

Zakres:

- nie przywracać górnego powitania, bo mobile ma zaczynać się od hero,
- dopracować kafelki szybkiego startu bez dokładania ciężkiej biblioteki ikon,
- dodać memory trainer strip albo mocniejszy quick-start state,
- dopracować mikrocopy.
- przenieść ustawienia startu do aplikacyjnego bottom sheeta, żeby pierwszy ekran nie był formularzem.

Checklist:

- [x] Górny greeting/top orientation zostaje poza pierwszym viewportem.
- [x] Nie dodajemy nowej biblioteki ikon w tym sprincie.
- [x] Brak fikcyjnej skuteczności, jeśli nie mamy danych.
- [x] Trener pamięci nie dominuje, jeśli nie ma pytań due.
- [x] Hero mobile nie zmienia się w "Wróć do trenera pamięci"; główna karta prowadzi do Nauki klasycznej.
- [x] Tapnięcie hero otwiera bottom sheet z ustawieniami i wyborem działu.
- [x] Trener pamięci jest osobnym paskiem CTA przy realnym `due_review_count`.
- [x] Szybki start nie dubluje kafelka Nauki klasycznej ani Trenera pamięci, gdy trener ma osobny pasek CTA.
- [ ] Widok nie robi się dłuższy niż potrzeba na kontach z różnym stanem dostępu.

Ryzyko:

- niskie/średnie,
- głównie ryzyko chaosu informacyjnego.

Notatka 2026-05-18:

- Po sprawdzeniu `package.json` projekt nie ma obecnie `lucide` ani podobnej biblioteki ikon.
- Nie dodajemy nowej zależności tylko po to, żeby podmienić litery w quick-start; jeśli będziemy chcieli pełny system ikon, robimy to jako osobną decyzję.
- Kierunek na dalszy Sprint 2: poprawić priorytety i mikrokopię pod hero, a nie dokładać kolejny top header.
- Quick-start nie powinien wyglądać jak ciężki segmented control; aktywny wybór jest pokazany dopiero w modalu ustawień, więc kafelki szybkiego startu mają mieć tylko subtelny stan aktywny.
- Korekta kierunku po decyzji UX: mobile hero nie może być przejmowane przez algorytm `recommendedStep`, bo główna karta ma imitować "Kontynuuj naukę / Nauka klasyczna" z makiety.
- Ustawienia startu nie są już stale widoczne pod kafelkami. Hero i kafelki szybkiego startu otwierają aplikacyjny bottom sheet konfiguracji.
- Kafelek `Nauka klasyczna` został zdjęty z szybkiego startu, bo jego rolę przejęło główne hero CTA.
- Bottom sheet dla Nauki klasycznej ma strukturę z makiety: uchwyt, zamknięcie, poziome karty działów, duże karty trybu nauki i dolny pasek startu.
- Po pierwszym podglądzie bottom sheet został skompaktowany: mniejsze karty działów, niższe karty trybów, krótsze opisy, ciaśniejszy header i niższy pasek startu.
- Po drugiej korekcie karty działów pokazują tylko dwa pierwsze wyrazy, tytuły trybów mieszczą się w jednej linii, liczba pytań i check są w jednym rzędzie, a przycisk `Rozpocznij naukę` ma niższą wysokość.
- Usunięto transform z animacji root mobile dashboardu, żeby `fixed` bottom sheet pozycjonował się względem viewportu telefonu, a nie względem animowanego kontenera.
- `Trener pamięci` jest osobnym paskiem CTA przy pełnym dostępie i realnym `due_review_count > 0`.
- Kafelek `Trener pamięci` znika z sekcji `Szybki start`, gdy ten osobny pasek CTA jest widoczny, żeby ekran nie dublował tej samej decyzji.
- Pasek `Trener pamięci` został dopasowany do makiety: biała niska karta, zielona okrągła ikona pamięci, licznik powtórek, krótki komunikat z zielonym akcentem i kompaktowy przycisk `Rozpocznij`.
- Ikona paska `Trener pamięci` została wycięta z makiety jako asset `mobile-memory-trainer-icon.png` i zastępuje ręcznie rysowany SVG, żeby zachować identyczną grafikę.
- Hero `Nauka klasyczna` zostało dopasowane do makiety mobile: osobny asset `mobile-learning-hero-road-car.webp` dla drogi i samochodu, żywy HTML dla tytułu/CTA/progresu, niższa proporcja karty i dynamiczny pasek postępu działu.
- Powierzchnia mobile dashboardu została ustawiona na czyste białe tło; z hero usunięto niebieską poświatę, a odstępy między sekcjami zostały zmniejszone, żeby ekran był bardziej aplikacyjny i mniej kartonowy.
- Globalny `SiteHeader` jest ukryty na mobile dla `/nauka`; jego rolę przejmuje `MobileLearningAppBar` w dashboardzie: po lewej pokazuje wybraną kategorię prawa jazdy z ikoną pojazdu i placeholderem ustawień, po prawej imię użytkownika oraz avatar albo inicjały. Desktop `/nauka` nadal korzysta z klasycznego headera.
- Nagłówek `Szybki start` został pomniejszony, a pionowy rytm między komponentami dodatkowo skrócony, żeby pierwszy ekran mobile był bardziej zwarty.
- Po kolejnej korekcie sekcja `Szybki start` ma niższe kafelki, mniejsze ikony, mniejsze odstępy i subtelniejszy cień, żeby nie konkurować z głównym hero.
- Dodano mobilną sekcję `Twój progres` między `Szybki start` a paskiem `Trener pamięci`: okrągły wskaźnik kursu liczy się z `totalAnsweredQuestions / totalQuestions`, poprawne bazują na przerobionych minus błędne, błędne korzystają z istniejącego `totalIncorrectQuestions`; czas nauki i tygodniowy wykres są na razie warstwą UI do późniejszego podpięcia telemetrii.
- Dodano statyczną makietę sekcji `Ostatnia aktywność` na dole mobile dashboardu. Docelowo nie może zostać hardcoded: trzeba przygotować backendowy payload `recent_learning_activity`, składany z `study_sessions` oraz `traffic_sign_learning_sessions`.
- Usunięto widoczny nagłówek `Szybki start`, bo kafelki same niosą znaczenie i odzyskujemy pionowe miejsce. Sekcja `Ostatnia aktywność` została pomniejszona, bo ma być szybkim sygnałem pomocniczym, a nie głównym CTA.
- Naprawiono mobile bottom sheet dla `Zen mode`: kafelek otwiera panel z wyborem działu, krótkim opisem Zen i dolnym paskiem startu. Wybór działu w Zen zawsze ustawia status `all`, żeby nie odziedziczyć trybu błędów z Nauki klasycznej.
- Bottom sheet dla `Egzamin` został dopasowany do makiety: duża ikona clipboard przy tytule, lista faktów w jednej karcie (`Zakres`, `Liczba pytań`, `Struktura`, `Czas`) i pełnoszeroki niebieski przycisk startu ze strzałką.
- Bottom sheet dla `Ranking` został dopasowany do makiety: ilustracja podium z assetu `mobile-ranking-podium.png`, karta `Twoja pozycja` i mocny przycisk `Zobacz pełny ranking`. Panel korzysta z realnego lekkiego `ranking_preview`; gdy użytkownik nie ma jeszcze ratingu, pokazuje stan startowy zamiast fikcyjnych punktów.
- Playwright 390x844: brak poziomego scrolla strony; tapnięcie `Kontynuuj naukę` otwiera bottom sheet z ustawieniami Nauki klasycznej i wyborem działu.

### Sprint 3: progres i ostatnia aktywność

Status: do zrobienia

Zakres:

- dodać `learning_dashboard` albo rozszerzyć `learning_overview`,
- podpiąć readiness/progress,
- dodać ostatnią aktywność,
- ewentualnie prosty wykres tygodnia, jeśli dane są tanie.

Plan logiki dla `Ostatnia aktywność`:

- utworzyć backendowy agregator, np. `LearningDashboardActivityService`,
- pobrać ostatnią ukończoną aktywność użytkownika z `study_sessions` dla klasycznej nauki, Zen, egzaminu, PJM i trenera pamięci,
- pobrać ostatnią ukończoną aktywność użytkownika z `traffic_sign_learning_sessions` dla modułu znaków,
- zwrócić jednolity payload: `type`, `title`, `subtitle`, `question_count`, `completed_at_label`, `score_percent`, `icon_variant`,
- jeśli brak aktywności, ukryć komponent albo pokazać spokojny empty state `Rozpocznij pierwszą sesję`,
- nie mieszać tego z `TrackUserIpActivity`, bo middleware loguje wizyty/IP, a nie wynik nauki.

Checklist:

- [ ] Dane są realne.
- [ ] Query są zmierzone.
- [ ] Brak N+1.
- [ ] Brak ciężkich agregacji na każde wejście.
- [ ] Jeśli danych brak, sekcja ma sensowny pusty stan albo jest ukryta.

Ryzyko:

- średnie/wysokie wydajnościowo.

Wymagane pomiary:

- czas renderu `/nauka` przed zmianą,
- czas renderu `/nauka` po zmianie,
- liczba query przed/po.

### Sprint 4: popularne działy / znaki

Status: do zrobienia

Zakres:

- dodać poziomą sekcję działów albo znaków,
- nie przeciążać pierwszego ekranu,
- wykorzystać istniejące `group_options` albo dane znaków.

Decyzja do podjęcia:

- czy "Popularne działy" oznaczają działy pytań egzaminacyjnych,
- czy kategorie znaków drogowych,
- czy "Najbliższe działy" zamiast "Popularne".

Rekomendacja:

- dla naszej aplikacji lepsze jest "Najbliższe działy" albo "Działy do nauki", bo to prowadzi użytkownika, a nie udaje popularność.

### Sprint 5: bottom nav

Status: odłożone

Nie wdrażać, dopóki nie zdecydujemy, czy aplikacja ma mieć stałą dolną nawigację na mobile.

Checklist decyzyjny:

- [ ] Czy bottom nav ma być tylko na `/nauka`, czy globalnie w części auth?
- [ ] Czy koliduje z aktywnymi sesjami?
- [ ] Czy zastępuje globalny header na mobile?
- [ ] Czy ma wpływ na checkout/profil/admin?

## Ryzyka główne

### R1: zbyt ciężki dashboard

Opis:

Makieta zawiera dużo danych i grafiki. Jeśli policzymy wszystko live, `/nauka` może zacząć ładować się wolniej.

Mitigacja:

- Sprint 1 bez ciężkich metryk,
- pomiary przed/po,
- cache lub lekkie agregaty,
- sekcje progresu dopiero po walidacji.

### R2: rozbicie obecnej logiki startu sesji

Opis:

`Index.vue` obsługuje klasyczną naukę, Zen, egzamin, trener pamięci, PJM, znaki i ranking. Nowy dashboard nie może zmienić payloadu formularzy przez przypadek.

Mitigacja:

- akcje startowe zostają w `Index.vue`,
- nowy komponent dostaje callbacks,
- brak zmian w backendowym `StudySessionStoreRequest` w Sprint 1.

### R3: przypadkowa zmiana desktopu

Opis:

Desktop i mobile siedzą w tym samym komponencie.

Mitigacja:

- nowy dashboard tylko `md:hidden`,
- desktopowy form zostaje,
- Playwright desktop po zmianie.

### R4: nieprawdziwe lub mylące statystyki

Opis:

Makieta pokazuje 85%, 78%, wykresy, streak. Jeśli dane nie są realne, użytkownik straci zaufanie.

Mitigacja:

- tylko realne dane,
- fallbacki i ukrywanie sekcji,
- brak placeholderów produkcyjnych.

### R5: zbyt "cukierkowy" styl

Opis:

Makieta jest mocno aplikacyjna i miękka. Nasza aplikacja ma być spójna z `/cennik`, `/oficjalna-baza-pytan-na-prawo-jazdy` i dojrzałym stylem produktu.

Mitigacja:

- mniej gradientów,
- więcej bieli i granatu,
- radius umiarkowany,
- cienie subtelne,
- hero atrakcyjny, ale nie zabawkowy.

## Zasady UI

- Mobile-first, ale tylko dla mobile.
- Jedna główna akcja na pierwszym ekranie.
- Nie więcej niż 2-3 mocne CTA na pierwszym viewport.
- Karty nie mogą być "box in box".
- Hero może być wizualne, ale tekst musi mieć kontrast WCAG.
- Touch target minimum 44 px, główne CTA 52-56 px.
- Żadnych samotnych liter na końcu wiersza w kluczowym copy, jeśli tekst jest kontrolowany.
- Animacje tylko krótkie i z `prefers-reduced-motion`.

## QA checklist końcowy

### Mobile viewporty

- [ ] 360x740
- [ ] 390x844
- [ ] 430x932

### Konta

- [ ] konto pełny dostęp, kategoria B
- [ ] konto pełny dostęp, kategoria C
- [ ] konto PJM starter
- [ ] konto bez pełnego dostępu
- [ ] konto nowe bez postępu
- [ ] konto z dużą liczbą powtórek

### Flow

- [ ] kontynuuj naukę z hero
- [ ] start nauki klasycznej
- [ ] start Zen mode
- [ ] start egzaminu
- [ ] wejście do trenera pamięci
- [ ] wejście do znaków drogowych
- [ ] ranking
- [ ] aktywacja pełnej nauki dla zablokowanych

### Techniczne

- [ ] `npm run build`
- [ ] brak poziomego scrolla
- [ ] brak layout shift po załadowaniu assetu hero
- [ ] desktop `/nauka` bez zmian wizualnych
- [ ] liczba query bez niekontrolowanego wzrostu
- [ ] czas wejścia na `/nauka` nie pogorszony istotnie

## Definition of done

Redesign uznajemy za gotowy, gdy:

- mobile `/nauka` wygląda jak aplikacyjny dashboard, nie jak formularz,
- użytkownik w kilka sekund rozumie następny krok,
- najważniejsza akcja jest widoczna bez scrollowania,
- tryby nauki są nadal dostępne i działają jak wcześniej,
- desktop nie zmienił się przypadkiem,
- dane są prawdziwe albo sekcja jest ukryta,
- dashboard jest szybki,
- QA na kontach i viewportach przeszło bez regresji.

## Dziennik wykonania

### 2026-05-18

- [x] Ustalono kierunek: inspiracja mobile dashboardem z hero, quick start, progres i aktywność.
- [x] Ustalono, że nie wycinamy całego PNG jako UI.
- [x] Przyjęto zasadę: assety selektywne, komponenty jako HTML/CSS/Vue.
- [ ] Decyzja D1.
- [ ] Decyzja D2.
- [ ] Decyzja D3.
- [ ] Sprint 0.
- [ ] Sprint 1.
