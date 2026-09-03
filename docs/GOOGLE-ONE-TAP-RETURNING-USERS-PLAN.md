# Google One Tap Returning Users Plan

Stan na: 2026-07-03

Zakres: poprawa UX Google One Tap po wdrozeniu login-only. One Tap ma byc widoczny na stronie glownej tylko dla osob, ktore prawdopodobnie juz maja konto w aplikacji. Nowi odwiedzajacy nadal moga uzyc przycisku Google w drawerze logowania, ale nie dostaja automatycznego promptu "Kontynuuj jako...".

## Status Implementacji

Etap 1 zostal zaimplementowany lokalnie 2026-07-03.
Etap 2 zostal zaimplementowany lokalnie 2026-07-03.

Zrobione:

- dodano centralny helper `App\Support\ReturningUserCookie`,
- cookie `prawkonaraz_returning_user` jest ustawiane po udanym logowaniu e-mail + haslo,
- cookie jest ustawiane po udanej rejestracji lokalnej,
- cookie jest ustawiane po udanym Google Identity login,
- cookie jest ustawiane po udanym klasycznym OAuth login/rejestracji social,
- One Tap na stronie glownej jest gate'owany obecnoscia cookie,
- automatyczny One Tap w drawerze tez jest gate'owany obecnoscia cookie,
- oficjalny przycisk Google w drawerze pozostaje widoczny bez cookie.
- Google Identity dla nowego konta nie konczy sie juz bledem login-only,
- backend zapisuje krotki sesyjny kontekst Google po poprawnej weryfikacji ID tokena,
- frontend przekierowuje uzytkownika do `/register?google_identity=1`,
- drawer rejestracji w trybie Google pokazuje konto Google i wymaga tylko kategorii oraz trybu nauki,
- `POST /register` finalizuje rejestracje Google, tworzy konto social-only i podpina `user_social_accounts`,
- konto nadal nie moze powstac bez kategorii prawa jazdy.

Zweryfikowane lokalnie:

- `./vendor/bin/pint` na zmienionych plikach PHP i testach,
- `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/GoogleIdentityLoginTest.php tests/Feature/Auth/SocialLoginTest.php`,
- `npm run build`.

Do zrobienia po deployu:

- smoke na produkcji po deployu,
- obserwacja logow Google Identity, `422`, `419`, `403` i bledow weryfikacji tokena.

## Problem

Aktualnie One Tap moze pojawic sie na stronie glownej kazdemu uzytkownikowi zalogowanemu w Google. Google wie, ze dana osoba ma konto Google, ale nie wie, czy ma konto w `prawkonaraz.pl`.

Dla uzytkownika bez konta w aplikacji to daje slaby UX:

- widzi prompt wygladajacy jak szybka kontynuacja,
- klika Google,
- backend poprawnie odmawia, bo zakres jest login-only,
- uzytkownik dostaje komunikat, ze musi przejsc przez rejestracje i wybor kategorii.

Mechanicznie jest to bezpieczne, bo konto nie powstaje bez kategorii. Produktowo jest to jednak zbyt obiecujace dla nowych osob.

## Decyzja

Wprowadzamy dwa poziomy zachowania:

1. Teraz: One Tap tylko dla powracajacych uzytkownikow.
2. Pozniej: pelny flow rejestracji Google z wyborem kategorii.

Do czasu wdrozenia punktu 2 nie pokazujemy automatycznego One Tap nowym odwiedzajacym na stronie glownej.

## Etap 1: One Tap Tylko Dla Powracajacych

### Cel

Pokazywac One Tap na stronie glownej tylko wtedy, gdy przegladarka ma lokalny marker, ze ten uzytkownik juz korzystal z konta w aplikacji.

### Marker Powracajacego

Rekomendowany marker:

```text
prawkonaraz_returning_user=1
```

Wariant techniczny:

- cookie niesekretne, dostepne dla JS jako nazwa markera,
- `Secure`,
- `SameSite=Lax`,
- dlugi TTL, np. 180 dni,
- bez danych osobowych,
- bez e-maila, id uzytkownika, tokenu ani statusu subskrypcji.

Frontend sprawdza obecnosc nazwy cookie, a nie wartosc. Jesli Laravel zaszyfruje wartosc cookie na wyjsciu, gate nadal dziala, bo `document.cookie` zawiera nazwe markera.

To nie jest mechanizm bezpieczenstwa. To tylko sygnal UX, ze warto pokazac One Tap. Jesli cookie zniknie, uzytkownik po prostu nie zobaczy promptu i nadal moze kliknac "Zaloguj sie".

### Kiedy Ustawiac Marker

Marker ustawiamy po udanym uwierzytelnieniu, niezaleznie od metody:

- klasyczne logowanie e-mail + haslo,
- Google Identity login,
- klasyczny Google OAuth redirect,
- Facebook OAuth, jesli pozostaje aktywny,
- rejestracja zakonczona utworzeniem konta.

Ustawienie markera po rejestracji jest OK, bo od tego momentu uzytkownik naprawde ma konto.

### Kiedy Czytac Marker

Frontend moze probowac pokazac One Tap na stronie glownej tylko jesli wszystkie warunki sa spelnione:

- `GOOGLE_IDENTITY_ENABLED=true`,
- `GOOGLE_ONE_TAP_ENABLED=true`,
- `window.location.pathname === '/'`,
- istnieje cookie `prawkonaraz_returning_user=1`,
- uzytkownik nie jest aktualnie zalogowany w aplikacji,
- Google Identity script zaladowal sie poprawnie.

Przycisk Google w drawerze logowania pozostaje dostepny dla wszystkich, niezaleznie od markera.

### Co Zrobic Z Obecnym Zachowaniem

Obecny stan produkcyjny po ostatnim tescie:

- `GOOGLE_ONE_TAP_ENABLED=true`,
- One Tap probuje pokazac sie na homepage dla kazdego goscia,
- to jest za szerokie zachowanie dla login-only.

Rekomendowana kolejnosc:

1. Wdrozyc gate po markerze `prawkonaraz_returning_user`.
2. Dopiero wtedy zostawic One Tap na homepage.
3. Jesli wdrozenie gate'a nie jest natychmiastowe, tymczasowo ustawic `GOOGLE_ONE_TAP_ENABLED=false`, zeby nie mylic nowych uzytkownikow.

## UX Po Etapie 1

Nowy odwiedzajacy:

- wchodzi na `/`,
- nie widzi automatycznego One Tap,
- moze kliknac "Zaloguj sie",
- widzi klasyczny formularz oraz oficjalny przycisk Google w drawerze.

Powracajacy uzytkownik:

- wchodzi na `/`,
- ma marker `prawkonaraz_returning_user=1`,
- Google One Tap moze pokazac "Kontynuuj jako ...",
- po kliknieciu backend nadal weryfikuje token i loguje tylko istniejace konto.

Uzytkownik, ktory wyczyscil cookies:

- jest traktowany jak nowy odwiedzajacy,
- nie widzi One Tap automatycznie,
- nadal moze zalogowac sie przez drawer.

## Backend Plan

### 1. Helper Cookie

Dodac male, centralne miejsce ustawiania markera, zeby nie duplikowac szczegolow cookie.

Kandydaci:

- metoda pomocnicza w serwisie auth,
- middleware po udanym loginie,
- helper/support class, np. `ReturningUserMarker`.

Minimalnie:

- nazwa cookie w jednym miejscu,
- TTL w jednym miejscu,
- opcja `Secure` zalezne od produkcyjnego HTTPS,
- `SameSite=Lax`,
- cookie nie-HttpOnly, bo frontend musi je odczytac.

### 2. Miejsca Ustawienia

Sprawdzic i podpiac marker w:

- `AuthenticatedSessionController` po udanym loginie e-mail,
- `GoogleIdentityLoginController` po udanym loginie,
- `SocialAuthController` / `SocialAccountService` po udanym OAuth login/link-registration,
- `RegisteredUserController` po udanej rejestracji lokalnej.

Jesli w kodzie istnieje wspolny post-auth redirect albo centralny punkt po loginie, preferowac jedno miejsce zamiast kilku recznych wywolan.

### 3. Brak Ustawiania Przy Bledach

Nie ustawiamy markera gdy:

- Google token jest poprawny, ale konto nie istnieje,
- logowanie zwrocilo `422`,
- konto jest zbanowane,
- wystapil blad CSRF,
- uzytkownik anulowal OAuth.

## Frontend Plan

### 1. Odczyt Cookie

Dodac prosty helper w `resources/js/public-content.ts`, np.:

```text
hasReturningUserMarker()
```

Nie uzywac localStorage jako jedynego zrodla, bo backend latwiej ustawi cookie po loginach formularzowych i OAuth redirectach.

### 2. Gate Dla One Tap

Zmienic warunek promptu homepage:

```text
shouldPromptOnPageLoad =
  pathname === '/'
  && oneTapEnabled
  && hasReturningUserMarker()
```

Otwarcie drawera logowania moze nadal probowac pokazac prompt tylko wtedy, gdy marker istnieje. Alternatywnie drawer moze zostawic sam przycisk Google i nie wywolywac One Tap.

Rekomendacja: One Tap tylko homepage + marker, a drawer zawiera przycisk Google.

### 3. Widocznosc Przycisku

Nie zmieniac oficjalnego przycisku Google w drawerze:

- jest dostepny dla wszystkich,
- jego klikniecie jest swiadomym wyborem logowania,
- dla nieistniejacego konta pokazuje komunikat i nie tworzy konta.

## Test Plan

Backend:

- login e-mail ustawia cookie `prawkonaraz_returning_user=1`,
- kazda uwierzytelniona sesja webowa odnawia cookie `prawkonaraz_returning_user=1`,
- logout z istniejacej sesji ustawia cookie, zeby objac uzytkownikow zalogowanych jeszcze przed wdrozeniem gate'a,
- Google Identity udany login ustawia cookie,
- Google Identity dla nieistniejacego konta nie ustawia cookie,
- ban/403 nie ustawia cookie,
- rejestracja lokalna ustawia cookie,
- cookie nie zawiera danych osobowych.

Frontend:

- homepage bez cookie nie wywoluje `google.accounts.id.prompt()`,
- homepage z cookie wywoluje prompt, jesli `GOOGLE_ONE_TAP_ENABLED=true`,
- drawer renderuje przycisk Google niezaleznie od cookie,
- drawer nie pokazuje automatycznego One Tap nowym uzytkownikom.

Produkcja smoke:

- nowa/incognito sesja: brak automatycznego One Tap na `/`,
- sesja po udanym loginie i logout: One Tap moze pojawic sie na `/`,
- konto Google bez konta aplikacji nie tworzy uzytkownika,
- health i standardowy smoke przechodza.

## Rollout

1. Wdrozyc kod markera i gate'a.
2. Ustawic `GOOGLE_ONE_TAP_ENABLED=true` tylko po wdrozeniu gate'a.
3. Przetestowac:
   - incognito bez cookie,
   - normalna przegladarka po loginie,
   - konto Google bez konta w aplikacji.
4. Monitorowac logi:
   - `422` Google Identity,
   - `419` CSRF,
   - `403` banned,
   - bledy weryfikacji tokena.

Rollback:

- szybki rollback UX: `GOOGLE_ONE_TAP_ENABLED=false` + `php artisan config:cache`,
- kod moze zostac, bo przy fladze false prompt nie powinien sie pokazac.

## Etap 2: Rejestracja Google Z Wyborem Kategorii

Status: zaimplementowane lokalnie 2026-07-03.

### Cel

Jesli uzytkownik klika Google, a konto w aplikacji nie istnieje, nie konczymy flow samym bledem. Zamiast tego prowadzimy go do rejestracji, gdzie musi wybrac kategorie prawa jazdy i preferowany tryb nauki.

### Docelowy Flow

1. Uzytkownik klika Google button albo One Tap.
2. Backend weryfikuje Google ID token.
3. Backend nie znajduje istniejacego konta ani podpietego `user_social_account`.
4. Backend zapisuje w sesji tymczasowy, zweryfikowany kontekst Google:
   - provider `google`,
   - `provider_user_id`,
   - zweryfikowany e-mail,
   - imie/nazwa,
   - avatar, jesli dostepny,
   - krotki TTL.
5. Frontend przekierowuje do `/register?google_identity=1`.
6. Rejestracja pokazuje wybor kategorii i trybu nauki bez pol hasla.
7. Po zatwierdzeniu backend tworzy konto, podpina Google i wymusza obecne reguly:
   - kategoria jest wymagana,
   - nowy uzytkownik nie omija paywalla,
   - status email jest zgodny z zaufanym Google e-mailem,
   - IP history jest zapisane.

### Granice Bezpieczenstwa

- Nie tworzyc konta bez kategorii.
- Nie przechowywac Google JWT w sesji dluzej niz potrzeba.
- Nie ufac danym z frontendu przy finalizacji rejestracji; finalizacja musi korzystac z kontekstu zapisanego po backendowej weryfikacji tokena.
- Nie pozwolic na podmiane kategorii/provider user id poza kontrolowanym formularzem.

### Decyzje Do Domkniecia

- Google-verified email oznacza `email_verified_at`, zgodnie z obecna logika `SocialAccountService`.
- Flow jest jednym ekranem `/register`, ale drawer ma tryb "Konto Google" i pokazuje tylko wybor kategorii oraz trybu nauki.
- One Tap nadal pozostaje gate'owany dla powracajacych po cookie. Jesli nowy uzytkownik swiadomie kliknie Google button, moze przejsc do rejestracji Google.
- Auto-link konta lokalnego po zweryfikowanym e-mailu pozostaje dozwolony zgodnie z dotychczasowa logika `SocialAccountService`.

## Rekomendacja

Po deployu Etapu 2 wykonac smoke produkcyjny dla:

- nowego konta Google bez konta w aplikacji,
- rejestracji Google z wyborem kategorii,
- klasycznego loginu istniejacego konta Google,
- incognito bez cookie One Tap.
