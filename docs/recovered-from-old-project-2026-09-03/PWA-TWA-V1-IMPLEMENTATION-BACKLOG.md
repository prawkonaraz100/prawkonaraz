# PWA/TWA v1 - backlog implementacyjny

## Status dokumentu

- Status: backlog wykonawczy przed kodowaniem
- Data zalozenia: 2026-07-06
- Bazuje na: `PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md`
- Zakres: PWA v1, przygotowanie TWA, mobile home `/nauka`, player `/nauka/teraz`
- Zasada: zadania sa planem implementacji, ale nie oznaczaja jeszcze zgody na kodowanie

Ten dokument zamienia projekt PWA/TWA v1 na konkretne sprinty, zadania, pliki, testy i kryteria akceptacji. Ma byc uzywany jako mapa prac, kiedy przejdziemy z projektowania do implementacji.

## Decyzja 2026-07-10 - pauza PWA/TWA, priorytet mobile UX

Na ten etap swiadomie wstrzymujemy dalsze zadania infrastrukturalne PWA/TWA. Zostaja zachowane w backlogu, ale nie sa kolejnym sprintem.

Odlozone:

- Lighthouse PWA,
- telemetry bledow klienta i budzet wydajnosci `/nauka`,
- formalna release checklist PWA,
- test instalacji i Android Back na realnym urzadzeniu,
- wrapper TWA/AAB, Play App Signing i finalne Digital Asset Links.

Priorytetem jest teraz audyt i poprawa istniejacych widokow mobilnych. Celem jest spojny, aplikacyjny flow `/nauka` i powiazanych ekranow przed przejsciem do kolejnej warstwy PWA/TWA.

## Aktualizacja 2026-07-10 - mobile UX, glowne widoki

W ramach priorytetu mobile UX zrealizowano pierwsza czesc home i konfiguratora:

- rozdzielono stan aktywnej sesji od stanu postepu calego kursu na `/nauka`,
- CTA `Wroc do sesji` prowadzi tylko do rzeczywistego resume, a uruchomienie nowej serii ma osobny jezyk,
- sheet konfiguracji jest renderowany nad globalnym dockiem przez `Teleport` i ma safe area dla finalnego CTA,
- smoke mobile sprawdza realne klikniecie finalnego CTA sheeta na 360/390/430 px.
- podsumowanie zwyklej sesji pokazuje na telefonie najpierw wynik, metryki i nastepna akcje; roadmapa wszystkich dzialow zostaje na desktopie.
- rekordy czasowe nie obciazaja mobilnego podsumowania jako tabela; czas pozostaje przy aktualnym punkcie lekkiej sciezki dzialow po akcjach wynikowych.
- krotki stan konfiguratora pokazuje wybrany dzial, a `Pokaz sciezke dzialow` otwiera jeden pelnoekranowy, wyszukiwalny picker z grupami podstawowymi i specjalistycznymi.
- picker pokazuje tylko faktyczny postep przerobienia pytan, wraca do konfiguratora po wyborze i zostal sprawdzony na 360/390/430 px.

Pozostaje: recommendation-first pierwszy stan konfiguratora, docelowa decyzja dla destination docka, feedback playera learn, trener i profil. Stan szczegolowy prowadzi `MOBILE-UX-AUDYT-2026-07-10.md`.

## Aktualizacja 2026-07-10 - Sprint 8: PWA/mobile quality gate

Zrealizowana czesc Sprintu 5:

- `scripts/e2e-smoke.mjs` zostal zrownany z aktualnym UI `/nauka`; sprawdza logowanie, start albo wznowienie sesji i zapis jednej odpowiedzi,
- dodana zostala mobile matrix Playwright dla 360x800, 390x844 i 430x932,
- test wymaga braku horizontal overflow oraz pelnego kontraktu pieciu pozycji dolnej nawigacji,
- test PWA sprawdza manifest, aktywny service worker, brak prywatnych sciezek w Cache Storage i offline fallback,
- test moze uruchomic sie przeciwko realnemu Nginxowi przez `E2E_SMOKE_BASE_URL`; z `E2E_SMOKE_EXPECT_PWA_CACHE_HEADERS=true` wymaga `no-store` dla service workera,
- naprawiony zostal horizontal overflow `/nauka` na 360 px: zewnetrzny `-mx-4` powodowal dodatkowe 16 px szerokosci.

Zweryfikowane lokalnie na `http://localhost:8000`:

- manifest `standalone`, start `/nauka`, cztery ikony,
- service worker kontroluje strone, a prywatne sciezki nie trafiaja do Cache Storage,
- fallback offline dla `/nauka` dziala,
- `service-worker.js` ma `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`,
- mobile matrix i dolna nawigacja przechodza na 360/390/430 px.

Otwarte ze Sprintu 5: Lighthouse PWA, telemetry bledow klienta, budzet wydajnosci `/nauka` i formalna release checklist.

## Aktualizacja 2026-07-07 - Sprint 7: PWA/TWA infrastruktura

Sprint 7 realizuje technicznie zakres opisany w backlogu jako `Sprint 2: PWA foundation`, rozszerzony o przygotowanie TWA.

Zrobione:

- dodany `public/manifest.webmanifest` ze startem na `/nauka`, trybem `standalone`, ikonami `any` i `maskable`,
- dodany konserwatywny `public/service-worker.js`: precache tylko statycznych zasobow i offline fallback, bez runtime cache prywatnego HTML/API,
- dodany `public/offline.html`,
- dodana rejestracja service workera w entrypointach Inertia i public content,
- dodane meta PWA/mobile w komponencie `x-site.favicons`,
- dodany endpoint `/.well-known/assetlinks.json` zasilany konfiguracja `config/pwa.php`,
- dodane env pod TWA: `TWA_PACKAGE_NAME`, `TWA_SHA256_CERT_FINGERPRINTS`,
- dodane cache exceptions dla Nginx i Apache,
- dodane testy feature dla manifestu, service workera i Digital Asset Links.

Do domkniecia przed Google Play/TWA:

- wpisac realny SHA-256 certyfikatu z Google Play App Signing do `TWA_SHA256_CERT_FINGERPRINTS`,
- potwierdzic finalny package name `pl.prawkonaraz.app`,
- przejsc Lighthouse PWA i test instalacji na Androidzie,
- zbudowac wrapper TWA/AAB i sprawdzic, ze Digital Asset Links przechodzi weryfikacje.

## Zasady pracy

1. Nie robimy kolejnego pelnego skanu repo przed kazdym sprintem.
2. Robimy krotki skan celowany przed sprintem, zgodnie z zakresem sprintu.
3. Nie przepisujemy `StudySessions/Show.vue`.
4. Nie obiecujemy offline odpowiedzi w v1.
5. Nie zmieniamy payloadow formularzy bez testu kontraktowego.
6. Nie cache'ujemy prywatnego HTML/API.
7. Nie zostawiamy placeholderow wygladajacych jak realne statystyki.
8. Kazdy sprint musi miec mobile QA na minimum 360/390/430 px.

## Decyzje zamrozone na potrzeby backlogu

| ID | Decyzja | Status |
| --- | --- | --- |
| D1 | v1 to PWA online-first, potem TWA | przyjete roboczo |
| D2 | start URL PWA/TWA: `/nauka` | przyjete roboczo |
| D3 | `/dashboard` zostaje routerem po auth | przyjete roboczo |
| D4 | offline odpowiedzi poza v1 | przyjete roboczo |
| D5 | Google Play v1: consumption-only | przyjete roboczo |
| D6 | nie piszemy native app od zera | przyjete roboczo |
| D7 | `active_session` jest pierwszym brakujacym kontraktem | przyjete roboczo |
| D8 | placeholdery w `MobileLearningDashboard` musza zniknac | przyjete roboczo |
| D9 | Android Back contract przed TWA | przyjete roboczo |
| D10 | `pl.prawkonaraz.app` jako package roboczy | do zatwierdzenia |

## Zakres techniczny v1

### Must have

- mobile home `/nauka` bez fikcyjnych statystyk,
- `active_session` summary,
- minimalny `learning_dashboard`,
- PWA manifest,
- ikony PWA,
- service worker online-first,
- offline fallback,
- cache/no-cache polityka,
- mobile QA matrix,
- Android Back dla sheetow i playera,
- TWA release runbook,
- Google Play consumption-only mode.

### Should have

- `GET /api/v1/me/learning-home` jako czysty bootstrap,
- `GET /api/v1/sessions/current`,
- client-side error monitoring,
- Web Vitals/RUM albo lekki odpowiednik,
- testy Playwright dla mobile matrix,
- performance budget dla `/nauka`.

### Could have

- `recent_learning_activity`,
- `weekly_activity`,
- `study_time`,
- osobny endpoint telemetry PWA,
- runtime cache obrazow/posterow mediow z limitami.

### Won't have w v1

- pelny offline learning,
- IndexedDB outbox odpowiedzi,
- Play Billing,
- natywny klient Android/iOS,
- masowy cache mediow,
- przebudowa calego playera.

## Sprint 0: finalizacja decyzji i bramki wejscia

### Cel

Zamknac decyzje, ktore blokuja implementacje. Sprint 0 nie zmienia aplikacji.

### Zadania

| ID | Zadanie | Pliki/dokumenty | Wynik |
| --- | --- | --- | --- |
| S0-1 | Zatwierdzic zakres PWA v1 | projekt + backlog | lista must/should/could zamknieta |
| S0-2 | Zatwierdzic Google Play consumption-only | projekt + policy notes | decyzja dla checkout/pricing |
| S0-3 | Wybrac package name TWA | projekt | np. `pl.prawkonaraz.app` |
| S0-4 | Zatwierdzic app shell contract | projekt | zasady top bar, dock, sheet, Back |
| S0-5 | Zatwierdzic quality gate | backlog | minimalne testy przed release |
| S0-6 | Ustalic, czy `weekly_activity` wchodzi do v1 | projekt/backlog | decyzja: backend albo ukrycie |

### Definition of done

- decyzje D1-D10 maja status zaakceptowane albo jawnie zmienione,
- wiadomo, ktory sprint kodujemy pierwszy,
- nie ma otwartego pytania blokujacego Sprint 1.

## Sprint 1: mobile home `/nauka`

### Cel

Zrobic z `/nauka` realny start aplikacji mobilnej: bez placeholderow, z resume aktywnej sesji i minimalnym kontraktem dashboardu.

### Celowany pre-scan

Przed kodowaniem sprawdzic:

- `routes/web.php` dla `/nauka`, `/study-sessions`, `/nauka/teraz`,
- `app/Http/Controllers/SessionPageController.php`,
- `app/Support/StudySessionManager.php`,
- `resources/js/Pages/Session/Index.vue`,
- `resources/js/Pages/Session/Partials/MobileLearningDashboard.vue`,
- `resources/js/Layouts/AuthenticatedLayout.vue`,
- wszystkie miejsca z placeholderami `weeklyProgressBars`, `czas nauki`, `Ostatnia aktywnosc`, `85%`.

### Zadania backend

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S1-BE-1 | Dodac `active_session` summary dla `/nauka` | `SessionPageController.php`, ewentualny presenter | `/nauka` dostaje id, mode, ui_shell, title, progress, resume_url |
| S1-BE-2 | Dodac minimalny `learning_dashboard` | `SessionPageController.php`, nowy support/presenter opcjonalnie | frontend nie musi liczyc wszystkiego sam |
| S1-BE-3 | Ustalic `purchase_mode` / `can_show_pricing_link` | access presenter albo props | web moze pokazac pricing, Play/TWA bedzie mogl ukryc |
| S1-BE-4 | Nie dodawac ciezkich metryk bez pomiaru | brak albo osobny builder | `/nauka` nie robi kosztownych agregacji |

### Zadania frontend

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S1-FE-1 | Usunac albo ukryc placeholdery | `MobileLearningDashboard.vue`, `Session/Index.vue` | nie ma fikcyjnej godziny, `85%`, statycznego tygodnia jako danych usera |
| S1-FE-2 | Dodac active session tile/CTA | `MobileLearningDashboard.vue` | user moze wrocic do `/nauka/teraz` |
| S1-FE-3 | Jawnie obsluzyc start nowej sesji przy aktywnej | `MobileLearningDashboard.vue`, `Index.vue` | start nie jest mylony z resume |
| S1-FE-4 | Uporzadkowac quick actions | `MobileLearningDashboard.vue` | klasyczna, zen, egzamin, trener, znaki, ranking, PJM sa czytelne |
| S1-FE-5 | Dodac tryb braku dostepu bez checkoutu Play | `MobileLearningDashboard.vue`, access props | app potrafi ukryc pricing CTA |

### Testy

- `php artisan test tests/Feature/SessionPageTest.php`
- `php artisan test tests/Feature/ProductAccessGateTest.php`
- `npm run build`
- manual/Playwright: 360x740, 390x844, 430x932
- sprawdzic: full access, missing access, PJM starter, aktywna sesja, brak aktywnej sesji

### Definition of done

- `/nauka` na mobile nie pokazuje fikcyjnych statystyk,
- active session tile dziala,
- start nowej sesji przy aktywnej jest swiadomy UX,
- desktop `/nauka` nie jest popsuty,
- mobile 360/390/430 bez poziomego scrolla i overlapu.

## Sprint 2: PWA foundation

### Cel

Dodac instalowalnosc PWA i konserwatywny service worker online-first.

### Celowany pre-scan

Sprawdzic:

- `resources/views/app.blade.php`,
- `resources/views/layouts/public-content.blade.php`,
- `resources/views/components/site/favicons.blade.php`,
- `resources/js/app.ts`,
- `resources/js/public-content.ts`,
- `vite.config.js`,
- `public/`,
- `deploy/mikrus/nginx/prawkobit.conf.example`,
- `public/.htaccess`.

### Zadania

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S2-1 | Dodac `manifest.webmanifest` | `public/manifest.webmanifest` | start_url `/nauka`, display standalone, ikony |
| S2-2 | Przygotowac ikony PWA | `public/icons/*` | 192, 512, maskable |
| S2-3 | Dodac meta PWA | layouty lub favicons component | theme-color, apple/mobile capable |
| S2-4 | Dodac offline fallback | `public/offline.html` albo widok | prosty, bez prywatnych danych |
| S2-5 | Dodac service worker online-first | `public/service-worker.js` albo Vite plugin | precache assets, NetworkOnly prywatne |
| S2-6 | Zarejestrowac service worker | `resources/js/app.ts` i decyzja dla public content | rejestracja tylko w produkcji/stabilnym trybie |
| S2-7 | Dodac cache exceptions | nginx/htaccess docs/config | SW/manifest/offline/assetlinks nie immutable |
| S2-8 | Dodac testy no-cache | testy albo Playwright script | prywatne trasy nie sa cache-first |

### Cache policy v1

| Zasob | Strategia |
| --- | --- |
| `/build/assets/*` | precache/cache-first |
| ikony PWA | precache/cache-first |
| `/manifest.webmanifest` | network-first/krotki cache |
| `/service-worker.js` | no-cache/update check |
| `/offline.html` | precache |
| `/nauka` | network-only albo network-first bez cache prywatnego HTML |
| `/nauka/teraz` | network-only |
| `/api/v1/*` private | network-only |
| `/auth/csrf-token` | network-only/no-store |
| media | runtime cache z limitami, nie precache |

### Testy

- `npm run build`
- Lighthouse PWA
- launch `/nauka` offline
- launch `/nauka/teraz` offline
- verify SW cache does not store private HTML/API
- update build i reload po aktywnym SW

### Definition of done

- aplikacja jest instalowalna jako PWA,
- offline fallback dziala,
- prywatne dane nie sa cache'owane,
- CSRF endpoint nie jest cache'owany,
- nowy deploy nie blokuje sie na starym SW.

## Sprint 3: player mobile polish i Android Back

### Cel

Doprowadzic aktywna sesje do app-like zachowania na telefonie i przygotowac ja pod TWA.

### Celowany pre-scan

Sprawdzic:

- `resources/js/Pages/StudySessions/Show.vue`,
- `resources/js/Pages/StudySessions/Exam.vue`,
- `resources/js/Layouts/SessionExamLayout.vue`,
- `resources/js/Layouts/SessionZenLayout.vue`,
- `SessionExpiredNotice.vue`,
- media components: `QuestionImageWithAnnotations`, `QuestionVideoFrameWithAnnotations`, `QuestionAudioControl`, `PjmVideoBlock`,
- miejsca z bottom sheet, fixed dock, safe-area, `history.replaceState`.

### Zadania

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S3-1 | Zdefiniowac helper/kontrakt Android Back | nowy composable opcjonalnie | Back zamyka sheet przed cofaniem |
| S3-2 | Podpiac Back dla sheetow playera | `Show.vue`, `Exam.vue` | menu/settings zamyka sie na Back |
| S3-3 | Sprawdzic fixed dock i spacer | `Show.vue`, `Exam.vue` | odpowiedzi nie sa zasloniete |
| S3-4 | Zweryfikowac Zen scroll lock | `SessionZenLayout.vue` | brak ucietej tresci na mobile/tablet |
| S3-5 | Egzamin background resume | `Exam.vue`, backend state | backendowy czas jest prawda |
| S3-6 | Session expired polish | `Show.vue`, `Exam.vue`, `SessionExpiredNotice.vue` | stan czytelny w standalone |

### Testy

- `php artisan test tests/Feature/StudySessionFlowTest.php`
- `php artisan test tests/Feature/Security/CsrfSessionRecoveryTest.php`
- `npm run build`
- mobile matrix:
  - learn,
  - zen,
  - exam,
  - PJM,
  - sr_review,
  - public demo smoke.
- manual Android/TWA later:
  - Back closes sheet,
  - Back from active session,
  - background 30s,
  - background > 15 min,
  - expired session.

### Definition of done

- Back behavior jest przewidywalny,
- dock nie przykrywa tresci,
- egzamin po backgroundzie synchronizuje backend,
- Zen nie ucina tresci,
- sesja wygasla nie wyglada jak zwykly offline.

## Sprint 4: mobile API contract i backend bootstrap

### Cel

Zaczac oddzielac czysty kontrakt mobile od webowego Inertia flow bez przepisywania frontendu.

### Dokument zaleznosci

Ten sprint powinien zaczac sie od dokumentu:

- [PWA-TWA-V1-MOBILE-API-CONTRACT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-MOBILE-API-CONTRACT.md)

### Zadania

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S4-1 | Opisac `GET /api/v1/me/learning-home` | nowy dokument + test plan | kontrakt zatwierdzony |
| S4-2 | Dodac endpoint learning-home | nowy controller/support | zwraca required_action/access/dashboard |
| S4-3 | Dodac `GET /api/v1/sessions/current` | API controller | zwraca aktywna sesje albo null/404 semantycznie |
| S4-4 | Ujednolicic filtry startu sesji API | request/controller | API moze wystartowac to co web `/nauka` |
| S4-5 | Zaprojektowac error codes | middleware/factory/docs | 401/419/403/409 semantyczne |
| S4-6 | Zaprojektowac idempotency | docs + ewentualnie endpoint | gotowe pod offline-lite, niekoniecznie wdrozone w pelni |

### Testy

- nowe tests/Feature dla `learning-home`,
- nowe tests/Feature dla `sessions/current`,
- rozszerzenie `ApiSessionTest`,
- `ProductAccessGateTest`,
- `PjmFreeAccessResolverTest`.

### Definition of done

- mobile bootstrap nie wymaga `product.access`,
- brak dostepu jest stanem danych, nie tylko redirectem,
- API umie opisac aktywna sesje,
- spec API jest zgodna z realnym kodem.

## Sprint 5: QA, monitoring i performance gate

### Cel

Zrobic z PWA/TWA releasowalny produkt, nie tylko dzialajacy kod.

### Zadania

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S5-1 | Rozszerzyc `e2e:smoke` o mobile viewporty | `scripts/e2e-smoke.mjs` albo nowy script | 360/390/430 |
| S5-2 | Dodac PWA/Lighthouse script | package scripts / docs | raport PWA |
| S5-3 | Dodac SW cache tests | Playwright/script | prywatne dane nie sa cache |
| S5-4 | Dodac client-side error telemetry | frontend + backend endpoint albo Sentry | JS errors raportowane |
| S5-5 | Dodac performance budget `/nauka` | script/docs | mierzymy payload/render |
| S5-6 | Dodac release checklist | docs | PWA/TWA gate gotowy |

### Minimalny quality gate

- backend critical tests,
- `npm run test:unit`,
- `npm run build`,
- `npm run e2e:smoke`,
- mobile e2e matrix,
- Lighthouse PWA,
- SW cache no-private-data test,
- background/session expiry test,
- Android Back test.

### Definition of done

- jest jedna komenda albo runbook do sprawdzenia PWA release,
- bledy klienta maja monitoring lub telemetry,
- mamy performance budget dla `/nauka`,
- QA matrix jest zapisana i odtwarzalna.

## Sprint 6: TWA internal testing

### Cel

Przygotowac pierwsza paczke Android/TWA do internal testing w Google Play.

### Zadania

| ID | Zadanie | Pliki | Akceptacja |
| --- | --- | --- | --- |
| S6-1 | Utworzyc wrapper TWA | `android/` albo `twa/` | Bubblewrap/Android Browser Helper config |
| S6-2 | Ustawic package/version | Android config | package zatwierdzony |
| S6-3 | Przygotowac AAB | Android build | build przechodzi |
| S6-4 | Skonfigurowac Play App Signing | Play Console | mamy release SHA-256 |
| S6-5 | Dodac `assetlinks.json` | `public/.well-known/assetlinks.json` | DAL verify |
| S6-6 | Sprawdzic TWA bez browser bar | real Android | fullscreen TWA |
| S6-7 | Przygotowac Play review assets | docs/assets | test account + instrukcje |
| S6-8 | Zweryfikowac consumption-only | app routes/props | brak web checkout CTA w Play mode |

### Testy

- real Android install from internal testing,
- launch z ikony -> `/nauka`,
- guest -> login -> `/dashboard` -> `/nauka`,
- active session answer flow,
- background + CSRF,
- Back behavior,
- missing access without external payment link.

### Definition of done

- AAB na internal testing,
- TWA bez paska przegladarki,
- Play review ma testowe konto,
- payment policy risk ograniczony przez consumption-only.

## Zadania przekrojowe

### Dokumentacja

| ID | Zadanie | Kiedy |
| --- | --- | --- |
| DOC-1 | Aktualizacja `API-SPEC.md` do realnego mobile API | Sprint 4 |
| DOC-2 | Release runbook TWA | Sprint 6 |
| DOC-3 | PWA cache policy runbook | Sprint 2 |
| DOC-4 | QA matrix mobile | Sprint 5 |
| DOC-5 | Play Console reviewer instructions | Sprint 6 |

### Design/UX

| ID | Zadanie | Kiedy |
| --- | --- | --- |
| UX-1 | Finalny app shell spec | Sprint 0/1 |
| UX-2 | Active session tile copy | Sprint 1 |
| UX-3 | Missing access copy dla Play mode | Sprint 1/6 |
| UX-4 | Offline fallback copy | Sprint 2 |
| UX-5 | Session expired copy w standalone | Sprint 3 |

### Assets

| ID | Zadanie | Kiedy |
| --- | --- | --- |
| A-1 | PWA icons 192/512/maskable | Sprint 2 |
| A-2 | Apple touch icon verification | Sprint 2 |
| A-3 | Google Play screenshots | Sprint 6 |
| A-4 | Media cache budget inventory | Sprint 2/5 |

## Pliki wysokiego ryzyka

| Plik | Ryzyko | Zasada |
| --- | --- | --- |
| `resources/js/Pages/StudySessions/Show.vue` | bardzo duzy komponent wspoldzielony | zmiany minimalne, testowac tryby |
| `resources/js/Pages/StudySessions/Exam.vue` | timer/egzamin | backend jest zrodlem prawdy |
| `resources/js/Pages/Session/Index.vue` | hub wszystkich trybow | nie psuc desktopu |
| `MobileLearningDashboard.vue` | home aplikacji | bez placeholderow |
| `StudySessionManager.php` | domena odpowiedzi/progresu | tylko z testami |
| `routes/web.php` | routing web/API | nie rozbijac middleware access |
| `bootstrap/app.php` | error handling | uwazac na API/web rozroznienie |

## Test matrix v1

### Viewporty

| Nazwa | Rozmiar |
| --- | --- |
| small Android | 360x740 |
| default Android/iPhone-like | 390x844 |
| large phone | 430x932 |
| tablet portrait | 768x1024 |

### Flow

| Flow | Sprint | Wymagane |
| --- | --- | --- |
| guest launch `/nauka` | S1/S5 | redirect/login/return |
| full access `/nauka` | S1 | dashboard + quick actions |
| missing access | S1/S6 | no Play checkout CTA in Play mode |
| PJM starter | S1/S3 | correct lock/unlock |
| active session resume | S1/S3 | tile -> `/nauka/teraz` |
| learn session | S3 | answer/reveal/next/complete |
| Zen session | S3 | no clipped content |
| exam session | S3 | timer/background/result |
| memory trainer | S3/S5 | ring/CTA/session expired |
| signs training | S3/S5 | sync error/result |
| ranking | S3/S5 | online-only fallback |
| offline launch | S2/S5 | fallback, no fake offline learning |

## Open questions przed Sprint 1

1. Czy `active_session` ma pokazywac tylko `study_sessions`, czy tez aktywne sesje znakow?
2. Czy start nowej sesji przy aktywnej ma wymagac potwierdzenia?
3. Czy `recent_learning_activity` wchodzi od razu, czy w Sprint 4/5?
4. Czy ukrywamy `weekly_activity` w v1, jesli nie ma taniego backendu?
5. Jak wykrywamy tryb Play/TWA dla ukrywania checkoutu: env, feature flag, user-agent/display-mode, czy server config?

## Kolejny rekomendowany krok

Przed kodowaniem Sprintu 1 przygotowac krotki dokument:

- [PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md)

Powinien zawierac:

- dokladny shape `active_session`,
- dokladny shape minimalnego `learning_dashboard`,
- lista placeholderow do usuniecia,
- screen states dla full access, missing access, PJM starter, active session,
- test plan dla 360/390/430.
