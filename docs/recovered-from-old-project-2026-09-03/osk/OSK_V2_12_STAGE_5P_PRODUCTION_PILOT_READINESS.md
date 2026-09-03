# Etap 5P - gotowosc do kontrolowanego pilota produkcyjnego

**Status:** technicznie zakończony lokalnie (2026-08-30). Nie wykonano deployu,
nie zmieniono flagi produkcyjnej i nie wybrano jeszcze prawdziwego partnera
pilota.

## Cel

Przed pierwszym prawdziwym OSK i pierwszymi kursantami chcemy mieć jedną,
fail-closed bramkę operacyjną. Ma ona dopuścić pilot tylko wtedy, gdy gotowe są
zarówno dane kursu i enrollmentu, jak i bezpieczne przekazanie danych dostępu.

Etap 5O działa lokalnie: może wydać dane na wydruku, e-mailem albo obu
kanałami. Od Etapu 5P e-mail z hasłem jest domyślnie wyłączony i znika z UI,
dopóki środowisko nie przejdzie jawnego preflightu. To nadal nie jest
równoznaczne z gotowością produkcyjną.

## Dlaczego ten etap jest potrzebny

Punktem wyjścia był `DeskAssistedCredentialDeliveryService`, który blokował
lokalny `MAIL_MAILER=log`, aby nie zapisać hasła w logach. Sam warunek
`MAIL_MAILER !== log` nie wystarczał dla produkcji:

- mailer `array` nie dostarcza e-maila, choć wywołanie notyfikacji kończy się
  bez błędu;
- domyślna konfiguracja `failover` ma `log` jako możliwy fallback, więc po
  awarii pierwszego transportu hasło mogłoby trafić do logów;
- domyślny nadawca `hello@example.com` nie jest prawidłowym nadawcą
  produkcyjnym;
- kod aplikacji może potwierdzić zlecenie wysyłki transportowi, ale bez
  integracji z dostawcą nie potwierdza dostarczenia do skrzynki kursanta.

Wydruk danych pozostaje bezpiecznym, działającym fallbackiem podczas całego
etapu.

## Pozostałe decyzje operacyjne przed pilotem produkcyjnym

1. Wskazać jedno partnerskie OSK, osobę odpowiedzialną po stronie OSK oraz
   osobę odpowiedzialną po stronie platformy.
2. Ustalić małą grupę pilota, na przykład 1-5 kursantów, i czas wsparcia po
   uruchomieniu.
3. Wybrać dostawcę e-maila oraz produkcyjny adres i nazwę nadawcy.
4. Potwierdzić SPF, DKIM i DMARC dla domeny nadawcy poza aplikacją.
5. Ustalić prosty rollback: kto może wyłączyć globalną flagę, zatrzymać kurs
   lub wyłączyć pojedynczy enrollment oraz gdzie zgłasza się problem.

Brak którejkolwiek decyzji nie blokuje wydruku lokalnego, ale blokuje
konfigurację produkcyjnego e-maila z hasłem i pierwszy prawdziwy pilot.

## Wykonana implementacja lokalna

1. Dodano `DeskAssistedCredentialEmailReadinessService` i lekki obiekt wyniku
   bez sekretów. Weryfikuje on: flagę e-maila, istniejący i jawnie zatwierdzony
   mailer, transport, produkcyjnego nadawcę oraz publiczny HTTPS `APP_URL`.
2. Dodano domyślnie wyłączoną konfigurację środowiskową:
   `OSK_DESK_ASSISTED_CREDENTIAL_EMAIL_ENABLED=false` i listę
   `OSK_DESK_ASSISTED_CREDENTIAL_EMAIL_ALLOWED_MAILERS`. Obie pozycje są też w
   `.env.example` z instrukcją bezpiecznego użycia.
3. Preflight odrzuca `log`, `array`, `failover` i `roundrobin`, nawet gdy
   ktoś ręcznie doda ich nazwę do allow-listy. Odrzuca także przykładowego albo
   lokalnego nadawcę i HTTP/lokalny adres aplikacji.
4. Ten sam serwis jest używany przez wydanie danych w panelu OSK i w panelu
   administratora. UI pokazuje wtedy prostą informację oraz tylko wydruk;
   bezpośrednie żądanie `EMAIL` po stronie serwera również bezpiecznie przechodzi
   na fallback wydruku.
5. Audit zapisuje wyłącznie kanał, fakt zlecenia i kod blokady. Widoczny tekst
   mówi „zlecono bezpieczną wysyłkę”, a nie „dostarczono e-mail”.
6. Nie dodano masowej wysyłki, importu plików, SMS, QR ani automatycznego
   włączania allow-listy.

## Weryfikacja lokalna

- `EnrollmentCreditsTest` pokrywa domyślne wyłączenie e-maila, fallback do
  wydruku, brak notyfikacji przy wyłączonej bramce, odrzucenie `array`, `log`
  oraz `failover`, dopuszczenie jawnie zatwierdzonego SMTP i brak surowego hasła
  w audycie.
- `OrganizationEnrollmentManagementTest` sprawdza, że właściciel OSK widzi
  jednoznaczny stan „wydruk” przed przygotowaniem e-maila.
- Końcowa regresja OSK, trzech paneli administracyjnych, logowania i dashboardu:
  **147 testów / 1278 asercji**. Przeszły też build Vite, cache widoków Blade,
  cache konfiguracji i `git diff --check`.
- Nie wykonano wysyłki do prawdziwej skrzynki, deployu, zmiany globalnej flagi
  ani wpisu production allow-listy.

## Odczyt produkcyjnej poczty - 2026-08-30

Wykonano wyłącznie odczyt konfiguracji i test otwarcia połączenia SMTP na
obecnym serwerze produkcyjnym. Nie wysłano wiadomości, nie odczytano sekretów i
nie zmieniono konfiguracji.

- aplikacja działa jako `production` pod publicznym HTTPS;
- domyślny mailer to rzeczywisty `smtp`, z nieprzykładowym nadawcą w domenie
  `prawkonaraz.pl` oraz skonfigurowanymi danymi SMTP;
- połączenie TCP z serwerem SMTP i jego baner SMTP odpowiadają poprawnie;
  serwer reklamuje `STARTTLS`, a bezuwierzytelniona negocjacja TLS kończy się
  poprawnie;
- obecny kod produkcyjny nie zawiera jeszcze
  `DeskAssistedCredentialEmailReadinessService`, a flagi funkcji i allow-listy
  dla e-maili z danymi kursanta są nieobecne/wyłączone.

To usuwa techniczną niewiadomą dotyczącą podstawowej infrastruktury pocztowej:
istniejący SMTP jest kandydatem do bezpiecznej wysyłki danych kursanta po
wdrożeniu Etapów 5O/5P. Nie jest to jednak automatyczna zgoda na taką wysyłkę.
Po osobnym deployu trzeba jawnie zatwierdzić `smtp` w allow-liście, włączyć
funkcję wyłącznie po decyzji operacyjnej i wykonać kontrolowaną próbę na
wskazanej skrzynce.

## Poza zakresem

- automatyczna weryfikacja CEIDG/KRS/VAT, SMS/OTP, płatności B2B, masowe
  operacje oraz inne kategorie kursów;
- formalne evidence, PAPER, formalny egzamin wewnętrzny i Browser Exam Station;
- gwarantowane potwierdzenie doręczenia bez decyzji i integracji z wybranym
  dostawcą poczty.

## Brama wyjścia do pilota produkcyjnego

Warstwa kodowa jest gotowa lokalnie. Pilot produkcyjny nadal pozostaje
zablokowany, dopóki nie zostaną wykonane wszystkie poniższe punkty:

1. wskazany partner, właściciel pilota, mała grupa testowa,
   monitoring i rollback;
2. skonfigurowany i przetestowany produkcyjny mailer, nadawca oraz
   SPF/DKIM/DMARC;
3. kontrolowana próba na wskazanej skrzynce i sposób obserwacji błędów;
4. osobna decyzja o deployu, fladze `OSK_THEORY_LEARNING_PILOT_ENABLED` i
   jednym wpisie allow-listy;
5. możliwość rollbacku bez migracji: globalna flaga `false`, wyłączenie
   pojedynczej allow-listy albo wstrzymanie kursu.
