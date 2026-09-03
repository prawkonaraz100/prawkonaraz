# PWA/TWA v1 - projekt aplikacji mobilnej

## Status dokumentu

- Status: dokument projektowy przed implementacja
- Data zalozenia: 2026-07-06
- Zakres: PWA v1 oraz przygotowanie do TWA/Google Play
- Core produktu: `/nauka` oraz `/nauka/teraz`
- Tryb: projektujemy na bazie obecnego webowego core, nie piszemy aplikacji od zera

Ten dokument przeklada raport badawczy na projekt pierwszej wersji aplikacji mobilnej. Ma odpowiadac na pytania:

- co dokladnie budujemy w v1,
- co wykorzystujemy z obecnego kodu i dokumentacji mobile,
- czego nie ruszamy w pierwszym etapie,
- jakie kontrakty backendowe sa potrzebne,
- jakie ekrany i flow maja byc app-like,
- jakie testy i quality gate musza przejsc przed PWA/TWA.

## Czy korzystamy z dokumentacji mobile?

Tak. Istniejaca dokumentacja mobile jest przydatna, ale trzeba jej uzywac w dobry sposob.

### Dokumentacja, ktora wykorzystujemy

- [MOBILE-VIEWS-IMPLEMENTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MOBILE-VIEWS-IMPLEMENTATION-PLAN.md)
  Zrodlo zasad mobilnego UX: app shell, tap targety, rozroznienie app shell/web shell, ryzyka `StudySessions/Show.vue`, fixed bottom dock, safe-area i pre-sprint code scan.

- [MOBILE-NAUKA-DASHBOARD-REDESIGN-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MOBILE-NAUKA-DASHBOARD-REDESIGN-PLAN.md)
  Zrodlo intencji dla mobilnego dashboardu `/nauka`: dashboard zamiast launchera, realne dane zamiast placeholderow, komponent `MobileLearningDashboard.vue`, hero, quick start, progres, aktywnosc.

- [PWA-NAUKA-MOBILE-RAPORT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-NAUKA-MOBILE-RAPORT.md)
  Zrodlo prawdy po skanie kodu: aktualny stan backendu/frontendu, PWA runtime, API, auth, media, Google Play, TWA, UX i QA.

### Jak z niej korzystamy

Dokumenty mobile sa dla nas:

- materialem projektowym,
- zapisem decyzji i intencji,
- dobrym zrodlem checklist,
- inspiracja dla app shell i mobile dashboardu.

Nie traktujemy ich jako bezwarunkowej prawdy o aktualnym kodzie, bo skan pokazal rozjazdy:

- czesc wspolnych komponentow mobile istnieje, ale nie jest szeroko uzywana,
- trener pamieci ma lokalny shell zamiast wspolnych komponentow,
- `MobileLearningDashboard.vue` jest wpiety, ale ma placeholdery,
- nie ma jeszcze jednego design systemu mobile,
- nie ma jeszcze PWA runtime ani Android/TWA wrappera.

Zasada: dokumentacja mobile wyznacza kierunek, a raport PWA weryfikuje stan faktyczny.

## Glowna decyzja projektowa

Budujemy aplikacje mobilna jako online-first PWA, a pozniej opakowujemy ja jako TWA do Google Play.

Nie budujemy w v1 osobnej natywnej aplikacji Android/iOS.

Uzasadnienie:

- core nauki jest juz webowy,
- `/nauka` ma realny mobilny dashboard,
- `/nauka/teraz` ma mobilny player i backendowy model sesji,
- auth, CSRF i sesje sa spojne z PWA/TWA,
- TWA pozwala wejsc do Google Play bez dublowania produktu,
- natywna aplikacja mialaby sens dopiero po stabilizacji kontraktu mobile API i PWA.

## Cele PWA/TWA v1

### Cel produktu

Uzytkownik instaluje aplikacje i po uruchomieniu trafia do realnego centrum nauki:

- widzi co ma robic dalej,
- moze wrocic do aktywnej sesji,
- moze wystartowac nauke,
- moze przejsc przez sesje na telefonie,
- dostaje czytelne stany: brak dostepu, wygasla sesja, brak internetu, blad synchronizacji.

### Cel techniczny

Dodac profesjonalna warstwe aplikacyjna bez przebudowy core:

- manifest PWA,
- service worker online-first,
- offline fallback,
- app shell mobile,
- bezpieczna polityka cache,
- podstawowy kontrakt `learning_home` / `active_session`,
- QA gate dla mobile/PWA,
- przygotowanie do TWA.

## Zakres v1

### Wchodzi do v1

| Obszar | Decyzja |
| --- | --- |
| `/nauka` | glowny ekran startowy aplikacji |
| `/nauka/teraz` | kluczowy ekran pracy kursanta |
| klasyczna nauka | w zakresie v1 |
| Zen | w zakresie v1, po visual QA |
| Egzamin | w zakresie v1, z testem background/timer |
| Trener pamieci | w zakresie v1 jako wazna szybka akcja |
| PJM | w zakresie v1, jesli obecny dostep/asset flow przejdzie QA |
| Znaki drogowe | w zakresie v1 jako modul app, ale mozna przyciac zakres do wejscia/linku |
| Ranking | online-only, bez offline i bez cache streamow |
| Public demo | zostaje dostepne, ale nie definiuje core app |
| PWA installability | tak |
| TWA readiness | przygotowanie, release po PWA |
| Offline odpowiedzi | nie w v1 |
| Google Play payment | consumption-only na start |

### Nie wchodzi do v1

- pelny offline learning,
- IndexedDB outbox odpowiedzi,
- lokalna baza pytan,
- natywna aplikacja React Native/Flutter/Kotlin,
- Play Billing w pierwszej wersji,
- alternatywny billing/linking w Play,
- przebudowa calego `StudySessions/Show.vue`,
- masowe cache mediow kursowych.

## Start URL i routing

### Start URL

`start_url`: `/nauka`

Powod:

- `/nauka` jest realnym hubem produktu,
- `/dashboard` jest routerem po auth,
- `/nauka/teraz` zalezy od aktywnej sesji,
- `/nauka` umie pokazac pelny produkt i PJM starter,
- mobilny dashboard jest juz w kodzie.

### Router po auth

`/dashboard` zostaje centralnym routerem po logowaniu.

Nie obchodzimy `PostAuthRedirectController`, bo zawiera wazne rozgalezienia:

- przejecie konta tymczasowego,
- wymuszona zmiana hasla,
- weryfikacja email,
- pending invitation,
- moderator,
- pelny dostep,
- darmowy PJM,
- aktywacja dostepu.

### Resume aktywnej sesji

PWA nie startuje automatycznie od `/nauka/teraz`.

Zamiast tego `/nauka` dostaje kafel lub CTA:

- "Wroc do aktywnej sesji",
- widoczne tylko gdy backend zwraca `active_session`,
- start nowej sesji przy istniejacej aktywnej powinien byc jawny.

## Ekrany v1

### 1. `/nauka` - mobile home

Rola:

- glowny ekran aplikacji,
- centrum decyzji,
- resume aktywnej sesji,
- rekomendowany nastepny krok,
- szybki start trybow.

Wykorzystujemy:

- `Session/Index.vue`,
- `MobileLearningDashboard.vue`,
- obecne dane `category`, `filters`, `group_options`, `learning_overview`, `ranking_preview`, `pjm_module`, `access`.

Zmiany projektowe:

- dodac `active_session`,
- usunac albo podpiac placeholdery,
- zdefiniowac app shell,
- oddzielic realne metryki od pozniejszych metryk,
- dodac wariant Play/TWA bez pricing CTA.

Najwazniejsze braki:

- `active_session`,
- `recent_learning_activity`,
- `study_time`,
- `weekly_activity`,
- backendowy `recommended_next_action` albo wspolny builder rekomendacji.

### 2. `/nauka/teraz` - player nauki

Rola:

- najwazniejszy ekran kursanta,
- zapis odpowiedzi,
- reveal,
- media,
- progres,
- zakonczenie sesji.

Wykorzystujemy:

- `StudySessions/Show.vue`,
- `StudySessions/Exam.vue`,
- `StudySessionController`,
- `StudySessionAnswerController`,
- `StudySessionManager`.

Zasada:

Nie przepisujemy playera. Projektujemy app shell i QA wokol istniejacego playera.

Wymagane przed TWA:

- Android Back contract,
- visual QA 360/390/430/768,
- test safe-area,
- test media/image/video/audio/PJM,
- test session expired,
- test background > 15 minut,
- test egzaminu po backgroundzie.

### 3. Wynik sesji

Rola:

- domkniecie flow,
- CTA do kolejnej nauki,
- powrot do `/nauka`,
- ewentualnie wejscie w bledy/trener pamieci.

Wymagane:

- app-like CTA na mobile,
- brak globalnego chaosu header/footer,
- jasna kontynuacja flow.

### 4. Trener pamieci

Rola:

- szybka akcja z `/nauka`,
- powrot do zaleglych powtorek,
- mocny kandydat na codzienny nawyk.

Wykorzystujemy:

- `ReviewQueue/Index.vue`,
- istniejacy mobile shell,
- `sr_review`.

Zasada:

Trener pamieci moze zostac osobnym app-like ekranem, ale jego shell powinien respektowac wspolny kontrakt: app bar, safe-area, Android Back, session expired.

### 5. Znaki drogowe

Rola:

- osobny modul nauki,
- dobry start dla nowych uzytkownikow,
- juz ma batch sync.

Wariant v1:

- minimum: wejscie z `/nauka`, trening i wynik dzialaja na mobile,
- nie wymagamy offline,
- nie cachujemy masowo obrazow znakow w precache.

### 6. Ranking

Rola:

- online-only tryb rywalizacji,
- atrakcyjny modul, ale technicznie odrebny przez realtime.

Zasada:

- nie cache'owac SSE/WebSocket,
- jasny stan offline/rozlaczenia,
- nie mieszac z offline-lite.

### 7. PJM

Rola:

- wazny modul dostepowy,
- moze byc darmowym starterem,
- ma ciezsze media video.

Zasada:

- w v1 online-first,
- bez szerokiego cache PJM video,
- osobna decyzja, czy pokazujemy PJM w pierwszych screenshotach sklepu.

## Mobile app shell contract

### App shell

Stosowany na prywatnych ekranach aplikacyjnych:

- `/nauka`,
- `/nauka/teraz`,
- wynik sesji,
- trener pamieci,
- znaki drogowe,
- ranking,
- PJM.

Elementy:

- top app bar 52-56 px,
- powrot 40-44 px,
- tytul 1 linia,
- kontekst: kategoria/progres/status,
- brak publicznego footera,
- dolny dock/CTA tylko gdy ekran ma jedna dominujaca akcje,
- safe-area top/bottom,
- stabilne wysokosci bez skakania layoutu.

### Web shell

Stosowany na:

- publiczne SEO,
- cennik web,
- auth,
- checkout web,
- profil,
- moderator.

W TWA/Play shell webowy musi miec wariant bez zewnetrznych CTA zakupu.

### Bottom sheet

Wymagania:

- max height oparty o `svh`,
- scroll wewnatrz sheeta,
- backdrop zamyka sheet,
- Escape zamyka sheet,
- Android Back zamyka sheet,
- fokus nie ucieka pod sheet,
- body nie ma przypadkowego horizontal scrolla.

### Android Back

Kontrakt:

1. Jesli otwarty sheet/menu: zamknij sheet.
2. Jesli otwarte ustawienia/wybor dzialu: zamknij panel.
3. Jesli aktywna sesja: powrot do `/nauka` albo potwierdzenie.
4. Jesli egzamin: ostrozniejszy wariant z komunikatem.
5. Jesli `/nauka`: pozwol wyjsc z aplikacji.

## Kontrakt danych v1

### `learning_dashboard`

Docelowy obiekt dla `/nauka`:

```json
{
  "learning_dashboard": {
    "required_action": "learning_home",
    "target_category": {
      "id": 1,
      "code": "B",
      "name": "Kategoria B"
    },
    "access": {
      "full_product": {
        "allowed": true,
        "reason": null,
        "source": "purchase",
        "expires_at": "2026-08-05T12:00:00+02:00"
      },
      "pjm": {
        "allowed": false,
        "reason": "pjm_track_not_selected"
      },
      "purchase_mode": "web",
      "can_show_pricing_link": true
    },
    "active_session": {
      "id": 123,
      "kind": "study_session",
      "mode": "learn",
      "ui_shell": "exam_like",
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
    "recent_learning_activity": []
  }
}
```

### Minimalne pola do wdrozenia najpierw

1. `required_action`
2. `access`
3. `active_session`
4. `recommended_next_action`
5. `course_progress`
6. `review`
7. `ranking`
8. `recent_learning_activity`

`weekly_activity` i `study_time` sa opcjonalne. Jesli nie wejda do v1, UI musi je ukryc.

### Mobile API

Minimalny kontrakt przyszly:

| Endpoint | Cel |
| --- | --- |
| `GET /api/v1/me/learning-home` | bootstrap mobile/PWA, access, required action, dashboard |
| `GET /api/v1/sessions/current` | resume aktywnej sesji |
| `POST /api/v1/sessions` | start sesji z filtrami web `/nauka` |
| `GET /api/v1/sessions/current/questions` | batch/window pytan |
| `POST /api/v1/sessions/current/answers` | zapis odpowiedzi |
| `POST /api/v1/sessions/current/complete` | zakonczenie sesji |
| `POST /api/v1/sessions/current/exam-state` | synchronizacja egzaminu |

Dla PWA v1 mozemy jeszcze korzystac z obecnych tras Inertia/web, ale ten kontrakt powinien powstac przed offline-lite i przed pelniejszym mobile klientem.

## PWA runtime v1

### Manifest

Minimalna decyzja:

- `name`: `Prawko na raz`
- `short_name`: `Prawko`
- `start_url`: `/nauka`
- `scope`: `/`
- `display`: `standalone`
- `background_color`: `#ffffff`
- `theme_color`: `#064f9e`
- `orientation`: `portrait`

Ikony:

- 192x192,
- 512x512,
- 512x512 maskable,
- Apple touch 180x180 do weryfikacji.

### Service worker

Strategia v1: online-first.

| Zasob | Strategia |
| --- | --- |
| Vite hashed assets | precache/cache-first |
| ikony PWA | precache/cache-first |
| offline page | precache |
| manifest | krotki cache albo network-first |
| service worker | no-cache/update check |
| `/nauka` | network-only albo network-first bez cache prywatnego HTML |
| `/nauka/teraz` | network-only |
| `/api/v1/*` private | network-only |
| `/auth/csrf-token` | network-only/no-store |
| media pytan | runtime cache z limitami |
| SSE/WebSocket ranking | bez cache |

### Offline fallback

Offline fallback nie udaje nauki offline.

Ma powiedziec:

- brak polaczenia,
- ostatnie odpowiedzi nie beda zapisywane offline w v1,
- wroc po odzyskaniu sieci,
- jesli sesja wygasla, zaloguj sie ponownie.

## TWA / Google Play v1

### Strategia

Najpierw PWA, potem TWA.

Kolejnosc:

1. PWA installability.
2. Manifest i service worker.
3. Ikony i theme color.
4. Android Back contract.
5. Quality gate mobile.
6. Bubblewrap/TWA wrapper.
7. Play App Signing.
8. `assetlinks.json` z release SHA-256.
9. Internal testing.
10. Release.

### Package

Do decyzji:

- `pl.prawkonaraz.app`
- `pl.prawkonaraz.nauka`

Rekomendacja robocza: `pl.prawkonaraz.app`, bo aplikacja moze rozszerzyc sie poza sama nauke.

### Digital Asset Links

Plik:

- `public/.well-known/assetlinks.json`

Wazne:

- fingerprint musi odpowiadac certyfikatowi, ktory podpisuje aplikacje instalowana z Play,
- przy Play App Signing bedzie to Play app signing certificate, nie upload key,
- bledny SHA-256 oznacza Custom Tab z paskiem przegladarki zamiast TWA.

### Platnosci

Wariant Google Play v1: consumption-only.

Oznacza:

- uzytkownik moze zalogowac sie i korzystac z dostepu kupionego poza aplikacja,
- w aplikacji z Play nie pokazujemy webowego checkoutu,
- nie linkujemy do zewnetrznej platnosci,
- brak Play Billing w v1,
- brak alternative billing/linking w v1.

Web PWA poza Play moze zachowac cennik i checkout.

## Media i cache

### Zasady

- app shell i ikony moga byc precache,
- media kursowe nie moga byc precache,
- obrazy/postery moga wejsc w runtime cache z limitem,
- pelne MP4 i PJM video nie wchodza do cache v1 bez osobnej decyzji,
- audio pytan opcjonalnie po limicie,
- R2/CDN headers wymagaja weryfikacji przed release.

### Robocze limity do decyzji

- obrazy i wyjasnienia: 50-100 MB,
- postery video: 20-50 MB,
- audio: 50-100 MB, jesli wchodzi do v1,
- pelne video/PJM: 0 MB w v1.

## Quality gate

### Minimalny zestaw przed PWA release

- `composer test` albo krytyczny pakiet backendowy,
- `npm run test:unit`,
- `npm run build`,
- `npm run e2e:smoke`,
- Playwright mobile matrix:
  - 360x740,
  - 390x844,
  - 430x932,
  - 768x1024,
- Lighthouse PWA,
- test offline fallbacku,
- test SW no-cache dla prywatnych danych,
- test background > 15 minut,
- test session expired,
- test Android Back dla sheetow i playera.

### Minimalny zestaw przed TWA release

- wszystkie testy PWA release,
- test na realnym Androidzie,
- TWA bez browser bar,
- Digital Asset Links verify,
- login/register/reset password,
- `/nauka` jako start z ikony,
- `/nauka/teraz` answer flow,
- egzamin background/timer,
- brak checkout/pricing CTA w wariancie Play,
- testowe konto dla reviewerow Google Play.

## Monitoring

### Backend juz mamy

- `X-Request-Id`,
- JSON error envelope,
- health check,
- monitoring snapshots,
- performance smoke,
- CSRF mismatch logging,
- audit logs dla waznych operacji.

### Do dodania dla PWA/TWA

- client-side JS error reporting,
- unhandled promise rejection reporting,
- tagi: `route`, `display_mode`, `viewport`, `service_worker_state`,
- telemetry offline fallback,
- telemetry failed fetch dla odpowiedzi,
- dashboard 401/419/403/409 dla nauki mobile,
- korelacja `X-Request-Id` w logach klienta,
- Web Vitals/RUM albo lekki odpowiednik.

## Kolejnosc prac

### Sprint 0: decyzje projektowe i spec

Cel:

- zatwierdzic zakres v1,
- zatwierdzic app shell,
- zatwierdzic Google Play consumption-only,
- zatwierdzic PWA quality gate,
- wybrac package name.

DoD:

- ten dokument zaakceptowany,
- lista decyzji zamknieta,
- brak nowego pelnego skanu repo przed implementacja.

### Sprint 1: mobile home `/nauka`

Cel:

- dodac/podpiac `active_session`,
- usunac placeholdery dashboardu albo ukryc ich sekcje,
- przygotowac `learning_dashboard` minimum,
- poprawic mobile home jako start app.

DoD:

- brak fikcyjnych statystyk,
- active session tile dziala,
- start sesji nie zamyka aktywnej bez jasnej decyzji UX,
- 360/390/430 bez overlapu.

### Sprint 2: PWA foundation

Cel:

- manifest,
- ikony,
- theme color/meta,
- service worker online-first,
- offline fallback,
- nginx/cache exceptions.

DoD:

- Lighthouse PWA przechodzi,
- private HTML/API nie sa cache-first,
- `/auth/csrf-token` nie jest cache'owany,
- update SW nie blokuje nowego builda.

### Sprint 3: player mobile polish

Cel:

- Android Back contract,
- bottom sheet/dock polish,
- safe-area,
- Zen visual QA,
- egzamin background QA.

DoD:

- `/nauka/teraz` learn/zen/exam przechodzi mobile matrix,
- bottom dock niczego nie zaslania,
- Back zamyka sheet zanim cofnie strone,
- session expired jest czytelny.

### Sprint 4: QA i monitoring PWA

Cel:

- mobile Playwright matrix,
- PWA cache tests,
- client-side error monitoring,
- performance budget.

DoD:

- quality gate mozna uruchomic lokalnie/CI,
- bledy JS ida do monitoringu albo telemetry endpointu,
- raportujemy route/display mode/viewport.

### Sprint 5: TWA internal testing

Cel:

- Bubblewrap wrapper,
- package name,
- Play App Signing,
- assetlinks,
- AAB,
- internal testing.

DoD:

- TWA startuje bez browser bar,
- `/nauka` z ikony dziala,
- consumption-only flow przechodzi review checklist,
- mamy testowe konto i instrukcje dla Play Console.

## Decyzje do zatwierdzenia

| ID | Decyzja | Rekomendacja |
| --- | --- | --- |
| D1 | Czy v1 jest PWA/TWA, bez native? | Tak |
| D2 | Start URL | `/nauka` |
| D3 | Czy v1 obiecuje offline odpowiedzi? | Nie |
| D4 | Google Play payment | consumption-only |
| D5 | Czy publiczne SEO wchodzi w PWA scope? | Manifest globalnie, app focus `/nauka` |
| D6 | Czy PJM wchodzi do v1? | Tak, jesli QA mediow przejdzie |
| D7 | Czy ranking wchodzi do v1? | Tak, online-only |
| D8 | Package name | `pl.prawkonaraz.app` roboczo |
| D9 | Czy `weekly_activity` wchodzi do v1? | Tylko jesli backendowo tanie; inaczej ukryc |
| D10 | Czy wspolne mobile komponenty sa obowiazkowe wszedzie? | Nie, kontrakt zachowan jest wazniejszy |

## Najwieksze ryzyka

| Ryzyko | Odpowiedz projektowa |
| --- | --- |
| Zbyt szybkie offline | v1 online-first, offline tylko fallback |
| Placeholdery w mobile dashboardzie | usunac albo podpiac do backendu w Sprint 1 |
| `Show.vue` jest duzy | nie przepisywac, robic punktowy polish i QA |
| Android Back | osobny kontrakt i test przed TWA |
| Google Play payments | consumption-only w v1 |
| Media/PJM storage | runtime cache z limitami, video bez cache v1 |
| Brak client monitoring | dodac przed TWA lub w Sprint 4 |
| Rozjazd API web/mobile | projekt `learning-home` i `sessions/current` |

## Zrodla zewnetrzne

Oficjalne dokumenty, ktore trzeba traktowac jako aktualne punkty odniesienia przed release:

- [Google Play Payments policy](https://support.google.com/googleplay/android-developer/answer/9858738?hl=en)
- [Understanding Google Play Payments policy](https://support.google.com/googleplay/android-developer/answer/10281818?hl=en)
- [Trusted Web Activities Quick Start Guide](https://developer.android.com/develop/ui/views/layout/webapps/guide-trusted-web-activities-version2)
- [Trusted Web Activity overview](https://developer.chrome.com/docs/android/trusted-web-activity)
- [Digital Asset Links overview](https://developers.google.com/digital-asset-links/v1/getting-started)
- [Android App Bundles](https://developer.android.com/guide/app-bundle)
- [Play App Signing](https://developer.android.com/studio/publish/app-signing)

## Nastepny dokument po tym

Po akceptacji tego projektu powinny powstac dwa dokumenty wykonawcze:

1. [PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md)
   Utworzony backlog wykonawczy: konkretna lista zadan/sprintow z plikami, testami i kryteriami akceptacji.

2. [PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md)
   Utworzona specyfikacja wykonawcza Sprintu 1: mobile home `/nauka`, `active_session`, `learning_dashboard`, placeholdery i testy.

3. [PWA-TWA-V1-MOBILE-API-CONTRACT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-MOBILE-API-CONTRACT.md)
   Utworzony szczegolowy kontrakt `learning-home`, `sessions/current`, odpowiedzi, bledow i idempotencji.
