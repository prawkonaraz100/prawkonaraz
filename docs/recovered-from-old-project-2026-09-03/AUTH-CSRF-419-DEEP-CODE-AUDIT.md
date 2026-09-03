# Gleboki audyt kodu CSRF 419

Status: audyt zakonczony, naprawa wdrozona produkcyjnie 2026-06-18
Data: 2026-06-18
Zrodlo zadania: `C:\Users\xxx\Desktop\Bug tokeny 419.txt`

Ten plik jest trwalym dziennikiem ustalen. Ma pozostac czytelny bez kontekstu
rozmowy i bez polegania na wczesniejszych odpowiedziach agenta.

## 1. Zakres

Audyt obejmuje:

1. cykl zycia sesji i tokenu CSRF w Laravelu,
2. login, rejestracje i logout w Blade oraz Inertia/Vue,
3. zachowanie po utracie sesji Redis i po przywroceniu starego DOM,
4. wszystkie mutacje zwiazane z nauka, ktore moga otrzymac 419,
5. sposob wykonania testow, ktore naprawde wlaczaja walidacje CSRF,
6. bezpieczna kolejnosc implementacji i rollout.

Kod aplikacji jest zmieniany etapami zgodnie z sekcja 6.

## 2. Potwierdzone ustalenia

### 2.1 Obecny dokument planu jest dobrym szkieletem, ale nie jest kompletny

Plik `docs/AUTH-CSRF-419-AUDIT-AND-REPAIR-PLAN.md` poprawnie:

- odrzuca hipoteze wspoldzielonego full-page cache jako obecna przyczyne,
- nie proponuje wylaczenia CSRF,
- proponuje sesyjny endpoint odswiezajacy token,
- zabrania automatycznego retry mutacji nauki po 419.

Do korekty lub doprecyzowania pozostaja:

- Vue/Inertia login i rejestracja,
- wszystkie powierzchnie logout,
- konkretna mapa mutacji nauki,
- realna strategia testowania CSRF w Laravelu 12,
- nieudokumentowane twierdzenie o 419 przy usuwaniu konta.

### 2.2 Laravel 12.55.1 automatycznie wystawia cookie `XSRF-TOKEN`

`Illuminate\Foundation\Http\Middleware\VerifyCsrfToken`:

- dla poprawnego GET/HEAD/OPTIONS przepuszcza request,
- po odpowiedzi dodaje cookie `XSRF-TOKEN`,
- wartosc cookie bierze z aktualnego `session()->token()`.

Wniosek: dedykowany `GET /auth/csrf-token` moze jednoczesnie:

- zwrocic token JSON dla klasycznych formularzy Blade,
- odswiezyc cookie uzywane automatycznie przez Axios/Inertia.

### 2.3 `session()->regenerate()` zmienia token CSRF

W tej wersji frameworka `Illuminate\Session\Store::regenerate()`:

1. migruje identyfikator sesji,
2. wywoluje `regenerateToken()`.

Kontrolery login i rejestracji wywoluja `session()->regenerate()`, dlatego po
udanym auth token CSRF zmienia sie. Middleware zapisuje jednak nowy token do
cookie `XSRF-TOKEN` na odpowiedzi.

### 2.4 Utrata rekordu sesji Redis odtwarza nowy token

Jesli przegladarka nadal wysyla stare cookie sesji, ale backend nie znajduje
rekordu sesji, `Store::start()` tworzy nowy `_token`. Stary token zapisany w
DOM lub cookie nie pasuje wtedy do nowej sesji i pierwszy POST moze dostac 419.

Poprawny GET wykonany przed POST zapisze odtworzona sesje i wystawi aktualne
cookie `XSRF-TOKEN`.

### 2.5 `withMiddleware()` nie wystarcza do testu CSRF

Projekt globalnie wywoluje:

```php
$this->withoutMiddleware(ValidateCsrfToken::class);
```

w `tests/TestCase.php`.

Nawet po `withMiddleware(ValidateCsrfToken::class)` oryginalny middleware nadal
pomija walidacje, poniewaz `VerifyCsrfToken::handle()` przepuszcza request, gdy
aplikacja dziala jako test jednostkowy w konsoli.

Test wymagajacy prawdziwej walidacji musi:

- podmienic `ValidateCsrfToken` na testowy subclass, ktory zwraca `false` z
  `runningUnitTests()`, albo
- uruchomic osobny test HTTP poza srodowiskiem `testing`.

Preferowany wariant w suite: jawny testowy subclass/binding tylko w dedykowanym
pliku testowym. Nalezy sprawdzic przypadek poprawnego oraz starego tokenu.

### 2.6 Nie nalezy usuwac istniejacego `@csrf` z Blade

Lepsza strategia kompatybilnosci:

- zachowac `@csrf` jako poprawny token poczatkowy i fallback bez JavaScript,
- przy aktywnym JS przechwycic submit,
- pobrac aktualny token,
- podmienic pole `_token`,
- dopiero potem wykonac natywny submit.

Usuniecie pola calkowicie zepsuloby formularze, gdy bundle Vite nie zaladuje
sie lub JavaScript jest niedostepny.

## 3. Mapa auth potwierdzona w kodzie

### Blade

- `resources/views/components/site/login-drawer.blade.php`
  - klasyczny `POST /login`, pole z `@csrf`.
- `resources/views/components/site/register-drawer.blade.php`
  - klasyczny `POST /register`, pole z `@csrf`.
- `resources/views/components/site/public-header.blade.php`
  - dwa klasyczne formularze `POST /logout`: mobile i desktop.
- `resources/js/public-content.ts`
  - obecnie nie przechwytuje submit auth ani logout i nie odswieza CSRF.

### Vue/Inertia

- `resources/js/Components/Auth/LoginDrawer.vue`
  - `useForm().post(route('login'))`.
- `resources/js/Components/Auth/RegisterDrawer.vue`
  - `useForm().post(route('register'))`.
- `resources/js/Pages/Auth/Login.vue` i `Register.vue`
  - uzywaja tych samych drawerow, wiec naprawa komponentow pokryje rowniez
    pelne strony auth.
- `resources/js/Components/SiteHeader.vue`
  - dwa natywne formularze logout z tokenem odczytanym z meta tylko raz.
- `resources/js/Pages/Auth/LogoutConfirm.vue`
  - logout przez Inertia `Link method="post"`.

Axios uzywany wewnetrznie przez Inertia automatycznie odczytuje cookie
`XSRF-TOKEN` dla requestow same-origin i wysyla `X-XSRF-TOKEN`.

## 4. Wstepna decyzja implementacyjna

Jeden wspolny helper frontendowy powinien:

1. wykonac `GET /auth/csrf-token` z `credentials: same-origin`,
2. wymagac poprawnej odpowiedzi JSON z tokenem,
3. zwracac rowniez `authenticated`,
4. nie wykonywac automatycznego retry mutacji,
5. rozrozniac blad sieci/endpointu od wygaslej sesji.

Uzycie:

- Blade: podmienic `_token` przed natywnym submit,
- Inertia login/register: wykonac preflight, potem `form.post()`; tokenu nie
  trzeba dokladac do payloadu, bo endpoint odswiezy cookie,
- natywny logout: podmienic `_token`,
- Inertia logout: preflight, sprawdzic `authenticated`, potem request POST.

## 5. Otwarte punkty audytu

- pelna mapa mutacji nauki i ich efektow ubocznych,
- obecne zachowanie kazdego UI po 419,
- miejsca wymagajace zatrzymania zegara, kolejki albo interakcji,
- testowalnosc helpera i komponentow,
- naglowki cache endpointu i jego dokladne umiejscowienie w routingu,
- monitoring TokenMismatchException bez logowania sekretow,
- weryfikacja twierdzenia o 419 przy usuwaniu konta.
