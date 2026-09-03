# Mail Startowy Dla Kont Tworzonych Przez Moderatora

Status: wdrożone lokalnie w bieżącej zmianie; dokument pełni rolę planu, kontraktu zachowania i checklisty regresji.

Powiązany dokument główny: `docs/REJESTRACJA-I-TWARDA-KATEGORIA-NAUKI.md`.

## Cel

Pełne konto utworzone przez moderatora w wariancie `znam e-mail` ma dostać wiadomość na podany adres e-mail z danymi startowymi i linkiem potwierdzającym adres. Moderator nadal widzi login i hasło startowe jednorazowo w panelu jako kopię awaryjną, gdy mail nie dotrze albo użytkownik zgubi wiadomość.

Zmiana nie może naruszyć:

- zwykłej rejestracji użytkownika,
- logowania i rejestracji przez Google albo Facebook,
- kont tymczasowych moderatora z loginem `@moderator.local`,
- standardowego mechanizmu resetu hasła,
- zasady, że hasło startowe nie jest później możliwe do podejrzenia.

## Stan Obecny

Obecnie pełne konto moderatorskie:

1. powstaje z docelowym e-mailem użytkownika,
2. dostaje hasło startowe,
3. wymaga zmiany hasła przy pierwszym logowaniu,
4. ma `email_verified_at = null`,
5. nie dostaje automatycznego maila z danymi startowymi,
6. po zmianie hasła trafia na ekran weryfikacji e-mail, gdzie użytkownik może wysłać standardowy link weryfikacyjny.

Hasło startowe jest zwracane wyłącznie przez flash session do widoku moderatora i po odświeżeniu strony nie jest już dostępne.

## Decyzja Produktowo-Techniczna

Dla konta pełnego moderatora wysyłamy jeden mail startowy zawierający:

- login,
- hasło startowe,
- kategorię nauki,
- link do logowania,
- podpisany link potwierdzający e-mail,
- informację, że po pierwszym wejściu użytkownik ustawi własne hasło.

Nie oznaczamy e-maila jako zweryfikowanego tylko dlatego, że mail został wysłany. Adres zostaje potwierdzony dopiero po kliknięciu podpisanego linku weryfikacyjnego.

## Kolejność Użytkownika

Rekomendowany przepływ:

1. Moderator tworzy konto pełne i podaje e-mail użytkownika.
2. System generuje hasło startowe.
3. System wysyła mail startowy na e-mail użytkownika.
4. Panel pokazuje moderatorowi login i hasło tylko raz jako kopię awaryjną.
5. Użytkownik klika link weryfikacyjny z maila.
6. Jeśli użytkownik nie jest zalogowany, Laravel kieruje go do logowania i zapamiętuje intended URL.
7. Użytkownik loguje się hasłem startowym.
8. System wraca na podpisany URL weryfikacji i potwierdza e-mail.
9. `/dashboard` kieruje użytkownika do obowiązkowej zmiany hasła startowego.
10. Po zmianie hasła użytkownik może przejść dalej do produktu, jeśli e-mail został już potwierdzony.

Fallback:

- jeśli mail nie dotarł, moderator może przekazać jednorazowo widoczne dane ręcznie,
- jeśli moderator nie zapisał hasła albo użytkownik zgubił dane, moderator generuje nowe hasło startowe i wysyła nowy mail,
- stare hasło przestaje wtedy działać,
- jeśli użytkownik dostał dane ręcznie i nie kliknął linku weryfikacyjnego z maila, po zmianie hasła nadal trafia na standardowy ekran potwierdzenia e-mail.

## Kolejność Systemowa

Nowa logika jest zawężona do `ModeratorAccountProvisioningService`, osobnej notyfikacji `ModeratorAccountStartCredentials` i akcji regeneracji hasła startowego.

Nie wolno podmieniać globalnej konfiguracji `Illuminate\Auth\Notifications\VerifyEmail`, ponieważ używają jej:

- standardowa rejestracja,
- ręczna ponowna wysyłka linku weryfikacyjnego,
- social login dla niezaufanych lub niejednoznacznych sygnałów e-mail,
- zmiana e-maila w profilu.

Mail startowy powinien być osobną notyfikacją, np. `App\Notifications\ModeratorAccountStartCredentials`.

## Regeneracja Hasła Startowego

Moderator może wygenerować nowe hasło startowe tylko wtedy, gdy konto nadal wymaga zmiany hasła.

Warunki dopuszczenia akcji:

- aktor jest moderatorem,
- konto należy do puli tego moderatora przez `moderator_owner_id`,
- konto nie jest kontem tymczasowym,
- konto ma `requires_password_change = true`,
- konto nie jest zablokowane,
- konto jest kontem utworzonym przez flow moderatorski.

Akcja regeneracji:

1. generuje nowe hasło startowe,
2. zapisuje je wyłącznie jako hash w `users.password`,
3. pozostawia `password_login_enabled = true`,
4. pozostawia albo wymusza `requires_password_change = true`,
5. wysyła mail startowy z nowym hasłem,
6. pokazuje nowe hasło moderatorowi tylko raz przez flash session,
7. zapisuje audyt bez hasła i bez tokenów.

Po `requires_password_change = false` moderator nie może już regenerować hasła. Od tego momentu użytkownik używa standardowego resetu hasła.

## Autoryzacja Endpointu Regeneracji

Trasa:

```text
POST /moderator/konta/{account}/haslo-startowe
```

Middleware:

- `auth`,
- `verified`,
- `moderator.panel`.

Nie należy opierać bezpieczeństwa na samym route bindingu użytkownika. Controller albo serwis musi jawnie sprawdzić `moderator_owner_id` względem aktualnego moderatora. Administrator może dostać osobną decyzję produktową później; w pierwszym wdrożeniu najbezpieczniej ograniczyć akcję do właściciela puli.

## Obsługa Błędów Wysyłki Maila

Wysyłka maila startowego nie powinna powodować rollbacku utworzenia konta.

Rekomendowane zachowanie:

- konto powstaje niezależnie od wyniku mailera,
- panel dostaje `start_credentials_email_sent = true` albo `false`,
- przy błędzie panel informuje moderatora, że dane trzeba przekazać ręcznie albo wygenerować nowe hasło później,
- błąd wysyłki jest logowany technicznie,
- audyt nie zapisuje hasła ani linku.

## Reset Hasła

Standardowy reset hasła powinien po skutecznym ustawieniu nowego hasła czyścić `requires_password_change`.

Uzasadnienie:

- jeśli użytkownik użyje resetu zamiast hasła startowego, ustawił już własne hasło,
- nie powinien po resecie trafiać ponownie na obowiązkową zmianę hasła startowego,
- reset hasła pozostaje niezależny od maila startowego moderatora.

## E-mail I Weryfikacja

Mail startowy może zawierać standardowy podpisany link do `verification.verify`.

Nie tworzymy publicznego endpointu "potwierdź e-mail bez logowania", ponieważ obecny mechanizm Laravel wymaga zalogowanego użytkownika i chroni przed potwierdzeniem konta w cudzej sesji.

Jeśli użytkownik kliknie link niezalogowany:

1. framework kieruje go do logowania,
2. po loginie wraca do intended URL,
3. standardowy kontroler `VerifyEmailController` potwierdza e-mail.

## Izolacja Od Innych Flow

Zmiany nie powinny dotykać:

- `RegisteredUserController`,
- `SocialAccountService`,
- `SocialAuthController`,
- globalnego `VerifyEmail`,
- standardowych tras `verification.send` i `password.email`,
- kont tymczasowych do przejęcia.

Klasyczna rejestracja nadal używa `event(new Registered($user))`.

Social login nadal używa swojej polityki:

- zaufany Google może oznaczyć e-mail jako zweryfikowany,
- niezaufany lub niejednoznaczny sygnał wysyła standardowe `VerifyEmail`,
- Facebook bez zaufanego sygnału nie powinien automatycznie linkować istniejącego konta.

## Zmiany W UI

Panel po utworzeniu pełnego konta powinien pokazać:

- czy mail startowy został wysłany,
- login,
- hasło startowe,
- kategorię,
- informację, że hasło jest widoczne tylko teraz.

Proponowany komunikat przy sukcesie:

> Wysłaliśmy dane startowe na e-mail użytkownika. Pokazujemy je też tutaj jednorazowo jako kopię awaryjną.

Proponowany komunikat przy błędzie wysyłki:

> Nie udało się wysłać maila z danymi startowymi. Przekaż dane ręcznie albo wygeneruj nowe hasło startowe później.

W tabeli kont można dodać akcję `Wyślij nowe hasło` tylko dla kont pełnych, które nadal wymagają zmiany hasła.

## Audyt

Nowe zdarzenia audytu:

- `moderator.account_start_credentials_sent`,
- `moderator.account_start_password_regenerated`,
- `moderator.account_start_credentials_resent`.

Metadane mogą zawierać:

- `account_type`,
- `target_category_id`,
- `target_category_code`,
- `email_sent`,
- `access_expires_at`,
- `temporary_account_expires_at`,
- `moderator_owner_id`.

Metadane nie mogą zawierać:

- hasła startowego,
- tokenu resetu,
- podpisanego linku weryfikacyjnego,
- nagłówków maila,
- żadnych sekretów.

## Testy Wymagane Przed Merge

Uruchomione po implementacji:

```text
.tools\php83\php.exe artisan test tests\Feature\Moderator\ModeratorAccountCreationTest.php tests\Feature\Auth\PasswordResetTest.php
.tools\php83\php.exe artisan test tests\Feature\Auth\RegistrationTest.php tests\Feature\Auth\EmailVerificationTest.php tests\Feature\Auth\SocialLoginTest.php
npm run build
```

Testy nowego flow:

- pełne konto moderatora wysyła `ModeratorAccountStartCredentials`,
- konto tymczasowe moderatora nie wysyła maila startowego,
- mail startowy zawiera login, kategorię i akcję z linkiem,
- podpisany link weryfikacyjny potwierdza e-mail po zalogowaniu,
- moderator widzi hasło tylko w następnym response po utworzeniu,
- regeneracja hasła działa przed zmianą hasła startowego,
- po regeneracji stare hasło nie działa, nowe działa,
- regeneracja pokazuje nowe hasło tylko raz,
- regeneracja jest zablokowana po `requires_password_change = false`,
- moderator nie regeneruje hasła konta innego moderatora,
- student nie ma dostępu do endpointu regeneracji,
- zbanowane konto nie pozwala użyć danych startowych.

Testy antyregresyjne:

- standardowa rejestracja nadal wysyła standardowe `VerifyEmail`,
- Google z potwierdzonym e-mailem nadal ustawia `email_verified_at`,
- social login bez zaufanego sygnału nadal wysyła standardowe `VerifyEmail`,
- niezaufany social nie linkuje automatycznie istniejącego konta,
- social login nadal zachowuje grant moderatorski,
- konto tymczasowe nadal wymaga przejęcia przez użytkownika,
- przejęcie konta tymczasowego nadal wysyła standardową weryfikację e-mail,
- standardowy reset hasła czyści `requires_password_change`,
- standardowy reset hasła nadal działa dla zwykłych użytkowników.

## Kolejność Implementacji

1. Dodać testy antyregresyjne dla obecnych flow.
2. Dodać notyfikację maila startowego i test jej treści.
3. Podpiąć wysyłkę przy tworzeniu pełnego konta moderatorskiego.
4. Zwrócić status wysyłki do panelu.
5. Dodać serwis i endpoint regeneracji hasła startowego.
6. Dodać UI akcji regeneracji i komunikaty po utworzeniu/regeneracji.
7. Poprawić standardowy reset hasła, żeby czyścił `requires_password_change`.
8. Uruchomić pakiety testów auth, moderator, social login i product access.

## Rollback

Jeśli po wdrożeniu pojawi się problem z mailami:

- można tymczasowo wyłączyć wysyłkę notyfikacji startowej flagą konfiguracyjną albo warunkiem w serwisie,
- konto nadal może być tworzone, bo moderator widzi dane jednorazowo,
- regenerację można zostawić jako ręczny fallback, o ile działa bez wysyłki maila albo jasno pokazuje moderatorowi nowe hasło.

Nie należy rollbackować zmian przez przywrócenie możliwości podejrzenia starego hasła.
