# PJM Starter UX Na Ekranie Nauki - Dokumentacja I Plan Sprintow

Status dokumentu: `plan wdrozenia po lokalnej realizacji sprintow 1-2`
Data: `2026-05-06`
Branch: `codex/fix-pjm-question-pool-count`
Zakres: ekran `/nauka`, widocznosc kafla PJM, wyszarzenie trybow pelnej nauki dla darmowego uzytkownika PJM, jedno CTA do aktywacji pelnej nauki.

## Cel Produktowy

Ekran `/nauka` dla osoby, ktora przy rejestracji wybrala PJM, ma bardzo jasno prowadzic do modulu PJM.

Uzytkownik PJM bez pelnego dostepu nie powinien miec wrazenia, ze moze normalnie korzystac z klasycznej nauki, Zen mode, egzaminu albo rankingu. Te opcje moga byc widoczne jako zapowiedz pelnego produktu, ale musza byc wyszarzone i nieaktywne.

Na ekranie ma zostac tylko jedno aktywne CTA zwiazane z platnym produktem:

`Aktywuj pelna nauke`

Kafel PJM ma byc osobnym, duzym, klikalnym wejsciem do darmowego modulu PJM. Ma byc widoczny tylko dla kont, ktore wybraly PJM przy rejestracji albo maja te preferencje ustawiona administracyjnie.

## Diagnoza Aktualnego Kodu

### Preferencja PJM Juz Istnieje

Model:

- `app/Models/UserProfile.php`
- stala: `UserProfile::LEARNING_TRACK_PJM = 'pjm'`
- pole: `preferred_learning_track`

Migracja:

- `database/migrations/2026_05_05_230000_add_preferred_learning_track_to_user_profiles_table.php`
- domyslna wartosc: `undecided`

Rejestracja mailowa:

- `app/Http/Controllers/Auth/RegisteredUserController.php`
- waliduje `preferred_learning_track`
- zapisuje wybor przez `UserProfileService::update()`

Rejestracja spolecznosciowa:

- `resources/js/Pages/Auth/Register.vue`
- przekazuje `preferred_learning_track` do `social.redirect`
- `app/Http/Controllers/Auth/SocialAuthController.php` zapisuje wybor w stanie OAuth
- `app/Support/SocialAccountService.php` zapisuje wybor przy utworzeniu profilu

Testy potwierdzajace:

- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/Auth/SocialLoginTest.php`

Wniosek: nie trzeba dodawac nowej kolumny ani nowego modelu. Regula widocznosci moze bazowac na istniejacym `user_profiles.preferred_learning_track`.

### Aktualny Resolver PJM Nie Wymaga Wyboru PJM

Plik:

- `app/Support/PjmFreeAccessResolver.php`

Obecnie darmowy PJM jest przyznawany, jesli:

- konto nie jest zbanowane,
- konto nie jest tymczasowe nieprzejete,
- konto nie wymaga zmiany hasla,
- konto ma potwierdzony e-mail,
- profil ma `target_category_id`,
- kategoria ma aktywne pytania z assetem PJM.

Resolver laduje `preferred_learning_track`, ale nie uzywa go jako warunku.

Skutek:

- uzytkownik, ktory nie wybral PJM przy rejestracji, moze dostac `pjmDecision->allowed = true`, jesli jego kategoria ma assety PJM,
- `SessionPageController` wysle wtedy `pjm_module.available = true`,
- frontend `/nauka` pokaze kafel PJM, bo obecnie sprawdza `pjm_module.available`, a nie `pjm_module.preferred`.

### Aktualny Ekran `/nauka`

Pliki:

- `app/Http/Controllers/SessionPageController.php`
- `resources/js/Pages/Session/Index.vue`

Backend wysyla:

```php
'pjm_module' => [
    'available' => $pjmDecision->allowed,
    'reason' => $pjmDecision->reason,
    'preferred' => $request->user()->profile?->preferred_learning_track === UserProfile::LEARNING_TRACK_PJM,
    'href' => route('session.pjm', absolute: false),
    'coverage' => $coverage,
]
```

Frontend po ostatniej zmianie pokazuje sam kafel z symbolem PJM, ale nadal warunek widocznosci to:

```vue
v-if="pjm_module.available"
```

To jest za szerokie. Dla nowego wymagania widocznosc kafla musi zalezec od preferencji PJM.

## Decyzje Wdrozeniowe

### 1. Kafel PJM Widoczny Tylko Dla Preferencji PJM

Docelowa regula:

`show_pjm_entry_tile = pjm_module.available && pjm_module.preferred`

Rekomendacja: policzyc te regule po stronie backendu i wyslac jawnie do Inertia jako:

```php
'pjm_module' => [
    'available' => $pjmDecision->allowed,
    'preferred' => $isPjmPreferred,
    'show_entry_tile' => $pjmDecision->allowed && $isPjmPreferred,
    'starter_mode' => $pjmDecision->allowed && $isPjmPreferred && ! $productDecision->allowed,
    'href' => route('session.pjm', absolute: false),
]
```

Powod:

- frontend nie duplikuje logiki biznesowej,
- testy Inertia moga latwo sprawdzic oczekiwany stan,
- pozniej mozemy zmienic zasady bez grzebania w kilku miejscach Vue.

### 2. Resolver Darmowego PJM Powinien Wymagac Wyboru PJM

Rekomendacja: `PjmFreeAccessResolver` powinien przyznawac darmowy PJM tylko wtedy, gdy:

- uzytkownik ma `preferred_learning_track = pjm`,
- ma zablokowana kategorie,
- kategoria ma aktywne assety PJM.

Wyjatek:

- konta systemowe/testowe/admin/moderator z pelnym dostepem systemowym moga pozostac dozwolone niezaleznie od preferencji, tak jak obecnie.

Nowy powod odmowy:

`pjm_track_not_selected`

Konsekwencja:

- osoba z `classic` albo `undecided`, bez pelnego produktu, nie wejdzie przez darmowy PJM i powinna trafic na aktywacje dostepu,
- osoba z `pjm`, bez pelnego produktu, nadal wejdzie na `/nauka` i zobaczy PJM starter mode.

### 3. Tryb PJM Starter Na `/nauka`

Definicja:

`starter_mode = show_entry_tile && !access.full_product.allowed`

W tym trybie:

- na gorze formularza jest duzy kafel PJM,
- po lewej i prawej stronie kafla sa duze strzalki kierujace wzrok do kafla,
- kafel jest jedynym aktywnym elementem darmowego modulu PJM,
- klasyczne tryby sa widoczne, ale wyszarzone i nieaktywne,
- jest tylko jeden aktywny button platny: `Aktywuj pelna nauke`,
- nie ma wielu linkow typu ranking -> aktywacja, osobnych CTA przy kazdej sekcji ani tekstow tlumaczacych darmowy modul.

### 4. Zachowanie Dla Pelnego Produktu

Dla uzytkownika z pelnym dostepem:

- klasyczne tryby musza pozostac aktywne,
- PJM kafel moze byc widoczny tylko, jesli `preferred_learning_track = pjm` i PJM jest dostepne dla kategorii,
- brak wyszarzenia klasycznej nauki,
- start button wraca do obecnego labela zależnego od trybu: `Rozpocznij nauke` albo `Rozpocznij egzamin`.

### 5. Zachowanie Dla Uzytkownika Bez Preferencji PJM

Dla `classic` albo `undecided`:

- kafel PJM nie jest widoczny na `/nauka`,
- jesli uzytkownik nie ma pelnego produktu, powinien trafic na `/access/activate`,
- jesli ma pelny produkt, normalnie widzi klasyczny ekran nauki bez PJM startera.

## Projekt UI

### PJM Entry

Kafel:

- asset: `resources/images/session/pjm-sign-language-symbol.png`,
- rozmiar docelowy desktop: okolo `220-260px`,
- rozmiar mobile: okolo `160-190px`,
- `Link` do `pjm_module.href`,
- `aria-label="Otworz modul PJM"`,
- obrazek dekoracyjny `alt="" aria-hidden="true"`, bo link ma etykiete.

Strzalki:

- dwie duze strzalki po lewej i prawej stronie kafla,
- `aria-hidden="true"`,
- nie sa klikalne,
- na mobile moga byc mniejsze albo ustawione blizej kafla,
- nie moga zaslaniac kafla ani powodowac przesuniecia layoutu.

Rekomendacja techniczna:

- jesli projekt ma juz biblioteke ikon, uzyc gotowej ikony strzalki,
- jesli nie, uzyc prostego elementu tekstowego albo CSS, ale traktowac go jako dekoracje i nie wprowadzac nowej zaleznosci tylko dla dwoch strzalek.

### Wyszarzenie Klasycznej Nauki

Elementy niezwiązane z PJM w `starter_mode`:

- Trener pamieci,
- segment trybow: `Nauka klasyczna`, `Zen mode`, `Tryb rankingowy`, `Egzamin`,
- wybieranie kategorii,
- status pytan,
- wybor dzialu,
- ustawienia kolejnosci,
- zakres pytan,
- lista dzialow pod formularzem.

Powinny byc:

- wizualnie wyszarzone,
- nieaktywne semantycznie,
- z `aria-disabled="true"` tam, gdzie element nie moze dostac `disabled`,
- bez dodatkowych linkow do aktywacji.

Rekomendacja:

- dodac computed `isPjmStarterMode`,
- opakowac klasyczne sekcje w stan `opacity-40 grayscale pointer-events-none select-none`,
- dla natywnych `button` dodac `:disabled="isPjmStarterMode || ..."` tam, gdzie to bezpieczne,
- dla `Link` rankingu w `starter_mode` zamienic link na nieaktywny element, bo jedyny link platny ma byc button `Aktywuj pelna nauke`.

### Jedyny Button Platny

Button:

`Aktywuj pelna nauke`

Zasady:

- prowadzi do `fullAccessUrl`,
- jest widoczny w `starter_mode`,
- jest jedynym aktywnym CTA do platnego produktu na ekranie,
- nie powinien startowac `study-sessions.store`,
- nie powinien byc zduplikowany na dole egzaminu, rankingu ani kart dzialow.

## Proponowany Kontrakt Frontendowy

```ts
interface PjmModule {
    available: boolean;
    reason: string | null;
    preferred: boolean;
    show_entry_tile: boolean;
    starter_mode: boolean;
    href: string;
    coverage: PjmCoverage | null;
}
```

Computed w Vue:

```ts
const isPjmStarterMode = computed(() => props.pjm_module.starter_mode);
const showPjmEntryTile = computed(() => props.pjm_module.show_entry_tile);
```

Warunki:

- kafel PJM: `v-if="showPjmEntryTile"`,
- wyszarzenie: `v-if/isPjmStarterMode`,
- klasyczny submit w `starter_mode`: nie submit, tylko link/button do `fullAccessUrl`.

## Sprint 1 - Backend Eligibility I Kontrakt Inertia

Status: `zrealizowany lokalnie`

Cel: uporzadkowac warunki, zeby frontend nie zgadywal, komu pokazac kafel.

Zakres:

- zmienic `PjmFreeAccessResolver`, aby darmowy PJM wymagal `preferred_learning_track = pjm`,
- dodac powod odmowy `pjm_track_not_selected`,
- zachowac wyjatek dla kont systemowych,
- w `SessionPageController` policzyc:
  - `isPjmPreferred`,
  - `show_entry_tile`,
  - `starter_mode`,
- zaktualizowac typ `PjmModule` w `Session/Index.vue`.

Testy:

- `PjmFreeAccessResolverTest`: pozwala dla `pjm`,
- `PjmFreeAccessResolverTest`: odmawia dla `classic` i `undecided` mimo assetow PJM,
- `PjmFreeAccessResolverTest`: system account nadal allowed,
- `ProductAccessGateTest`: darmowy PJM dziala tylko dla preferencji PJM,
- `ProductAccessGateTest`: free user bez preferencji PJM trafia na aktywacje.

Ryzyko:

- zmiana resolvera zmieni zachowanie obecnych darmowych kont bez preferencji PJM. To jest zgodne z nowym wymaganiem, ale musi byc opisane w release notes.

## Sprint 2 - UI PJM Starter Na `/nauka`

Status: `zrealizowany lokalnie`

Cel: zrobic jednoznaczny ekran startowy dla darmowego PJM.

Zakres:

- kafel PJM pokazac tylko przy `show_entry_tile`,
- powiekszyc kafel,
- dodac dwie duze strzalki dekoracyjne po bokach,
- usunac fallback kafla `PJM niedostepne` z widoku standardowego,
- dodac `isPjmStarterMode`,
- wyszarzyc i zablokowac klasyczne sekcje w `starter_mode`,
- zostawic jeden aktywny button `Aktywuj pelna nauke`,
- upewnic sie, ze klik w kafel nadal prowadzi do `/nauka/pjm`.

Testy:

- `npm run build`,
- test przegladarkowy `/nauka` dla konta PJM bez pelnego produktu:
  - widoczny duzy kafel,
  - widoczne dwie strzalki,
  - klasyczne opcje wyszarzone,
  - tylko jeden aktywny CTA `Aktywuj pelna nauke`,
  - kafel prowadzi do `/nauka/pjm`.
- test przegladarkowy dla konta `classic/undecided`:
  - kafel PJM niewidoczny,
  - bez pelnego produktu uzytkownik nie dostaje mylacego ekranu PJM.

Ryzyko:

- za szerokie `pointer-events-none` moze zablokowac rowniez button aktywacji, jesli trafi do tego samego wrappera. Button aktywacji musi byc poza wyszarzonym wrapperem.

## Sprint 3 - Regresja I Dostepnosc

Status: `zweryfikowany lokalnie`

Cel: upewnic sie, ze zmiana nie psuje pelnej nauki i nie wprowadza martwych elementow.

Zakres:

- testy PHP dla routingu `/nauka`, `/nauka/pjm`, `/access/activate`,
- build frontendu,
- sprawdzenie w browserze desktop/mobile,
- sprawdzenie focus states:
  - kafel PJM ma widoczny focus ring,
  - wyszarzone opcje nie lapia fokusu,
  - button aktywacji jest osiagalny klawiatura,
  - strzalki sa ignorowane przez czytnik ekranu.

Testy akceptacyjne:

- PJM preferred + no full access: `/nauka` jest starterem PJM,
- PJM preferred + full access: `/nauka` nie blokuje klasycznej nauki,
- classic/undecided + no full access: brak darmowego PJM, przejscie do aktywacji,
- system account: nie blokujemy narzedzi testowych i dostepu systemowego,
- `/nauka/pjm` nadal dziala dla uprawnionego PJM,
- klasyczny submit nie tworzy sesji bez pelnego produktu.

QA lokalne `2026-05-06`:

- testy `PjmFreeAccessResolverTest`, `ProductAccessGateTest`, `PjmStudySessionTest`, `SessionPageTest`, `RegistrationTest` i `SocialLoginTest` przechodza,
- browser desktop na `/nauka`: widoczny jest `1` link `Otworz modul PJM`, `1` obrazek symbolu PJM i `1` button `Aktywuj pelna nauke`,
- browser desktop: stare copy typu `969 pytan z tlumaczeniem PJM`, `PJM niedostepne` i `Zobacz pelny dostep` nie wystepuje w starterze,
- klik kafla PJM prowadzi do `/nauka/pjm`, gdzie widoczny jest przycisk `Kontynuuj PJM`,
- `Trener pamieci` i `Tryb rankingowy` w starterze maja `aria-disabled="true"` oraz `tabindex="-1"`,
- button `Aktywuj pelna nauke` pozostaje aktywny,
- browser mobile `390x844`: widoczny jest `1` kafel PJM, `1` button `Aktywuj pelna nauke`, bez poziomego overflow,
- browser mobile `390x844`: kafel ma okolo `168x168 px`, a button aktywacji jest bezposrednio pod kaflem, nie pod wyszarzona konfiguracja,
- dodano test regresji: uzytkownik z preferencja PJM po zakupie pelnego dostepu ma `starter_mode = false`, wiec wyszarzone elementy wracaja do normalnego dzialania.

Pozostaje:

- manualny przeglad focus ringow na realnym viewportcie,
- finalny smoke po deployu albo po uruchomieniu docelowego builda.

## Kolejnosc Bezpiecznego Wdrozenia

1. Najpierw backend i testy resolvera.
2. Potem propsy Inertia i test `ProductAccessGateTest`.
3. Dopiero potem UI w `Session/Index.vue`.
4. Na koncu build i test browserowy.

Nie robimy migracji bazy w tym wdrozeniu, bo potrzebna flaga juz istnieje.

Nie zmieniamy logiki samej sesji PJM, progresu po dzialach ani limitow pytan w tym sprincie. To zostaje w zakresie `docs/PJM-PROGRES-ROZDZIALY-SPRINT-PLAN.md`.
