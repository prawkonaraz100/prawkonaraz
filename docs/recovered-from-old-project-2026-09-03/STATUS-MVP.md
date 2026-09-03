# Status MVP

## 1. Snapshot

Data snapshotu bazowego: `2026-03-20`
Ostatnia aktualizacja produkcyjna: `2026-05-26`

Ten dokument zapisuje rzeczywisty stan projektu po zakończeniu technicznego MVP i pełnym lokalnym imporcie oficjalnego wsadu `gov.pl`.

To jest dokument statusowy, nie roadmapa. Ma odpowiedzieć na trzy pytania:

- co jest już naprawdę dowiezione,
- co nadal blokuje publiczny launch,
- co jest następnym sensownym ruchem.

## 1.1 Aktualizacja produkcyjna 2026-05-25

Projekt ma dzialajace pierwsze wdrozenie produkcyjne:

- adres kanoniczny po decyzji klienta: `https://prawkonaraz.pl`,
- `www.prawkonaraz.pl` przekierowuje `301` na `https://prawkonaraz.pl`,
- `http://` przekierowuje na `https://` przez Cloudflare `Always Use HTTPS`,
- Cloudflare obsluguje DNS i proxy dla domeny,
- aplikacja dziala na `Mikrus 4.1 PRO` na VPS `henryk153`,
- produkcyjny `APP_URL` jest ustawiony na `https://prawkonaraz.pl`,
- produkcyjna baza PostgreSQL zawiera `19` uzytkownikow, `12` kategorii i `17044` pytania,
- `ops:health-report` i `ops:smoke-test` przechodza poprawnie,
- produkcyjne maile wychodza z `kontakt@prawkonaraz.pl`,
- Google OAuth jest skonfigurowany i przeszedl realny test rejestracji/logowania,
- Facebook OAuth jest odlozony, bo Meta blokuje utworzenie konta developerskiego
  na obecnym urzadzeniu,
- UI ukrywa Facebook OAuth, gdy nie ma ustawionych produkcyjnych sekretow,
- konta Google/OAuth-only moga ustawic pierwsze haslo w profilu,
- konta OAuth-only bez hasla usuwaja konto przez link potwierdzajacy wyslany na
  email, a nie przez samo aktywne zalogowanie,
- zmiana adresu e-mail jest zabezpieczona: konto z haslem musi podac obecne
  haslo, a konto OAuth-only potwierdza zmiane linkiem wyslanym na dotychczasowy
  adres,
- realny produkcyjny test zmiany e-maila przeszedl 2026-05-26, odbior trzech
  maili testowych w Gmailu zostal potwierdzony, a konta testowe usuniete,
- sekcja profilu `Logowanie` jasno pokazuje, kiedy Google/Facebook mozna
  odpiac, a kiedy trzeba najpierw ustawic haslo,
- anulowany albo wygasly OAuth wraca z czytelnym polskim komunikatem,
- admin aplikacji przed zmiana konta admina: `admin@prawkoapp.pl`.

Rzeczy nadal otwarte po pierwszym deployu:

- wrocic do Facebook OAuth dopiero po odblokowaniu Meta Developers,
- media i backupy trzeba przeniesc z lokalnego etapu pomostowego na Cloudflare R2,
- trzeba domknac plan naprawy auth/OAuth/email opisany w `docs/AUTH-DEPLOYMENT-REPAIR-PLAN.md`,
- trzeba utwardzic SSH: wylaczyc logowanie haslem i najlepiej utworzyc osobnego uzytkownika deployowego,
- trzeba wykonac test restore backupu.

## 2. Werdykt

Na dziś projekt jest:

- `technicznie pilot-ready`,
- `operacyjnie wdrozony na pierwszej produkcji`,
- `niegotowy do publicznego launchu bez decyzji prawnej`.

Najkrótsza uczciwa ocena:

- MVP jest domknięte technicznie,
- produkt nie jest jeszcze domknięty biznesowo,
- najwiekszy blocker nie jest juz w kodzie ani hostingu, tylko w prawach do tresci, mailach, R2 i braku realnej walidacji na uzytkownikach.

## 3. Status względem roadmapy

### Etap 0: Decyzje i fundament

`Done`

- stack jest ustalony,
- architektura modularnego monolitu jest wdrożona,
- strategia mediów poza DB jest wdrożona,
- kontrakty API, baza i import pipeline mają kanoniczną dokumentację.

### Etap 1: MVP Core

`Done`

- auth, profil i dashboard działają,
- katalog pytań działa,
- sesje `learn`, `exam`, `quick`, `sr_review` i `hard` działają,
- odpowiedzi, wyniki, review queue i analytics działają,
- panel admina i podstawowy backoffice działają.

### Etap 2: Media i wydajność

`Done dla MVP`

- media są poza bazą,
- staging `gov.pl` działa,
- obrazy są materializowane,
- `WMV -> MP4` działa,
- generowanie `thumb.webp` i posterów działa,
- importer potrafi odchudzić pojedyncze za duże oficjalne obrazy zamiast failować cały batch,
- ciężkie media mogą być ładowane na osobny lokalny bulk disk poza repo.

### Etap 3: Launch i stabilizacja

`Done dla MVP`

- backup i restore istnieją,
- health checks istnieją,
- smoke test operatorski istnieje,
- browser smoke istnieje,
- audit log dla admin mutacji istnieje,
- CI istnieje,
- request correlation i standard błędów API istnieją.

### Etap 4: Traction i optymalizacja

`Partially done`

- readiness score istnieje,
- hard questions istnieją,
- review queue istnieje,
- analytics istnieją,
- nie ma jeszcze realnych danych o retencji i zachowaniu użytkowników.

### Etap 5+ V2 / B2B / Enterprise

`Not started productowo`

I to jest zgodne z roadmapą. Nie ma uzasadnienia, żeby iść dalej bez pilota i bez sygnałów z rynku.

## 4. Stan wdrożonego systemu

## Aplikacja

- backend: `Laravel`
- frontend: `Inertia.js + Vue 3 + TypeScript`
- admin: `Filament`
- auth: sesje i cookies
- testy backendu: `Pest`
- browser smoke: `Playwright`

## Import i dane

Lokalnie został wykonany pełny staging i pełny realny import oficjalnego wsadu `gov.pl`.

Stan lokalny po imporcie:

- `12` kategorii,
- `18375` pytań w bazie,
- `23498` rekordów mediów w bazie,
- `31680` plików mediów na lokalnym storage bulk,
- około `17.06 GB` assetów lokalnych,
- `14` zarejestrowanych runów importu/stagingu,
- `5` oversized oficjalnych JPG zostało automatycznie przekształconych do lżejszego `.webp`.

Uwaga:

- liczby pytań i mediów w bazie są wyższe niż same liczby z raportu pełnego importu `gov.pl`, bo lokalna baza zawiera też wcześniejsze rekordy smoke/testowe,
- dla publicznego MVP nadal obowiązuje decyzja produktowa: `start od kategorii B`, nawet jeśli technicznie lokalnie załadowaliśmy więcej.

## 5. Jakość i weryfikacja

Ostatni potwierdzony stan walidacji:

- `php artisan test` -> `121 passed`
- `vue-tsc --noEmit` -> `pass`
- `vite build` -> `pass`
- pełny `catalog:import-manifest-series` dla `gov-full-series` -> `0 błędów`
- pełny `catalog:import-manifest-series --dry-run` dla `gov-full-series` -> `0 błędów`

To oznacza, że system przeszedł z etapu "projekt działa na sample" do etapu "projekt udźwignął pełny realny wsad lokalnie".

## 6. Główne blokery

### 1. Prawa do treści

To jest blocker numer jeden.

Na dziś:

- techniczny pipeline dla oficjalnych pytań i mediów działa,
- publiczny launch z tym contentem nadal nie jest bezpieczny bez odpowiedzi Ministerstwa i oceny prawnej.

W praktyce:

- testowy deploy zamknięty lub pilot wewnętrzny jest sensowny,
- publiczna komercyjna publikacja oficjalnego wsadu nie powinna ruszać bez wyjaśnienia statusu prawnego.

### 2. Braki w oficjalnych paczkach

Oficjalne paczki nadal mają realne luki:

- nie pokrywają 100% głównych mediów,
- nie zawierają PJM.

To nie blokuje samego silnika produktu, ale blokuje traktowanie tej bazy jako idealnie kompletnej.

### 3. Brak walidacji rynkowej

Roadmapa zakłada, że MVP jest naprawdę zamknięte dopiero wtedy, gdy pojawią się:

- pierwsi realni użytkownicy,
- pierwsze oznaki retencji,
- pierwsze sygnały użyteczności produktu.

Tego jeszcze nie mamy.

## 7. Co nie jest blockerem

Na dziś blockerem nie są już:

- architektura aplikacji,
- model danych,
- media pipeline,
- import manifestów,
- staging gov.pl,
- backup/restore,
- smoke tests,
- CI,
- admin/backoffice.

To wszystko jest już na poziomie wystarczającym do pilota i testowego środowiska.

## 8. Rekomendowany następny krok

Najrozsadniejsza kolejnosc po pierwszym produkcyjnym deployu:

1. domknac twardnienie SSH i uzytkownika deployowego,
2. skonfigurowac prawdziwy mailer oraz SPF/DKIM/DMARC,
3. domknac krytyczne naprawy auth/OAuth/email z `docs/AUTH-DEPLOYMENT-REPAIR-PLAN.md`,
4. przeniesc media i backupy do Cloudflare R2,
5. wykonac test restore backupu,
6. przejsc reczny QA na realnej tresci i prawdziwych mediach,
7. uzyc `B-first` jako wariantu produktowego do pilota, nawet jesli pelny wsad jest zaladowany,
8. poczekac na odpowiedz Ministerstwa,
9. dopiero potem podjac decyzje:
   - publiczny launch,
   - pilot zamknięty,
   - albo pivot treściowy.

## 9. Czego nie robić teraz

Nie robić teraz:

- V2,
- B2B,
- multi-tenancy,
- white-label,
- SSO,
- rozbudowy enterprise,
- kolejnej dużej architektury "na zapas".

Największa wartość z następnej rundy nie przyjdzie z nowych feature'ów, tylko z:

- legal clarity,
- pilota,
- sprawdzenia realnego zachowania użytkownika.

## 10. Status końcowy

Na dzien `2026-05-25` projekt nalezy traktowac jako:

- `MVP done technicznie`,
- `pierwsza produkcja dziala pod prawkonaraz.pl`,
- `public launch pending legal and product validation`.
