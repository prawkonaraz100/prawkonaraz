# Etap 5O - konto gotowe przy biurku OSK

**Status:** zakończony lokalnie (2026-08-30). Brak deployu.

## Cel

Kursant siedzący przy biurku w OSK ma wyjść z gotowymi danymi do nauki.
Sekretarka wpisuje imię, nazwisko i e-mail, wybiera kurs oraz sposób przekazania
danych. Kursant nie musi klikać linku aktywacyjnego, potwierdzać e-maila ani
ustawiać hasła przed pierwszą nauką.

Do wyboru są trzy świadome kanały:

1. **Wydruk** - sekretarka od razu drukuje kartę z adresem logowania, loginem
   i hasłem.
2. **E-mail** - te same dane są wysyłane bezpośrednio na adres kursanta.
3. **Wydruk i e-mail** - kursant dostaje kartkę oraz kopię na e-mail.

W tej ścieżce konto i dostęp do kursu są aktywne od razu. Zwykła ścieżka
`PENDING` z jednorazowym linkiem aktywacyjnym pozostaje bez zmian dla osób,
które zakładają konto samodzielnie albo mają już istniejące konto.

## Ustalone zasady produktu

- Login jest prostym, technicznym identyfikatorem wygenerowanym przez system,
  oddzielnym od e-maila.
- Hasło startowe ma format `Imie123456`: bez myślnika, z losowymi cyframi.
  Cyfry nie mogą wynikać z PESEL-u, daty urodzenia, numeru telefonu ani
  innych danych kursanta.
- System tworzy takie dane wyłącznie dla **nowego** konta. Gdy e-mail należy
  już do użytkownika, OSK nie może poznać, zmienić ani wydrukować jego hasła.
  Wtedy używa dotychczasowej ścieżki zapisu/aktywacji lub kursant odzyskuje
  hasło samodzielnie.
- Sekretarka przed utworzeniem konta potwierdza, że kursant jest przy biurku
  albo poprosił o przygotowanie danych oraz że wskazany e-mail należy do niego.
- Kursant może później samodzielnie zmienić hasło. Nie zmuszamy go do tego
  przy pierwszym logowaniu.
- Na prośbę kursanta uprawniony operator może wydać nowe dane. Stare hasło
  natychmiast przestaje działać; ta operacja jest wyraźnie potwierdzona i
  audytowana.

## Granice bezpieczeństwa

- Surowe hasło istnieje tylko w bieżącym żądaniu, bezpośrednim e-mailu albo
  wydruku. Nie trafia do `audit_logs`, metadanych, logów aplikacji, tokenów,
  kolejek ani trwałej sesji.
- Baza przechowuje wyłącznie standardowy hash hasła oraz sam login. Login nie
  jest sekretem, ale nie jest powielany w audycie bez potrzeby.
- `PRINT`, `EMAIL` i `PRINT_AND_EMAIL` są audytowane jako sposób przekazania,
  bez zapisywania treści danych dostępu.
- Wysyłka e-maila następuje dopiero po commitcie. Błąd wysyłki nie cofa
  aktywnego dostępu ani nie pobiera dodatkowego miejsca; operator dostaje
  jednorazowy ekran wydruku jako bezpieczny fallback.
- Od Etapu 5P e-mail z hasłem jest domyślnie wyłączony. Wydruk pozostaje
  dostępny, a e-mail wymaga jawnej flagi, zatwierdzonego nietestowego mailera,
  prawidłowego nadawcy i publicznego HTTPS. Szczegóły są w
  [Etapie 5P](./OSK_V2_12_STAGE_5P_PRODUCTION_PILOT_READINESS.md).
- Nie zdejmujemy globalnie middleware `verified`. Nieweryfikowany kursant
  utworzony przy biurku może wejść wyłącznie do własnego modułu nauki OSK,
  jeśli ma aktywny i dopuszczony dostęp. B2C, płatności, profil oraz panele
  OSK nadal zachowują zwykłe zasady weryfikacji e-maila.
- Obecne bramy pilota (flaga środowiskowa, aktywny kurs i per-enrollment
  allow-lista) zostają nienaruszone. Utworzenie konta nie włącza pilota
  samoczynnie.

## Kontrakt techniczny

1. Dodajemy do `users` opcjonalny, unikalny `login_identifier` oraz uczymy
   standardowe logowanie przyjmować e-mail albo login.
2. Dodajemy do `student_course_accesses` metodę provisioningu
   `SELF_SERVICE_LINK` albo `DESK_ASSISTED` oraz ślad czasu i aktora
   przygotowania danych przy biurku.
3. `OrganizationEnrollmentService` dostaje osobną, autoryzowaną metodę
   desk-assisted dla właściciela/delegowanego operatora oraz administratora
   platformy. W jednej transakcji tworzy konto, enrollment, aktywny dostęp,
   rezerwację i konsumowanie jednego miejsca.
4. Wydanie danych odbywa się poza transakcją: wydruk w bieżącej odpowiedzi,
   e-mail przez bezpośrednią notyfikację. Żaden sekret nie wraca w późniejszym
   odświeżeniu strony.
5. Oddzielne, OSK-only middleware dopuszcza nieweryfikowanego kursanta tylko
   do playera, gdy istnieje jego aktywny desk-assisted dostęp przechodzący
   bieżący preflight. Redirect po logowaniu zachowuje ten sam warunek.
6. Oba interfejsy zapisu - panel właściciela OSK i panel administratora
   platformy - udostępniają tę samą ścieżkę oraz prosty wydruk danych.

## Zrealizowany zakres lokalny

- Dodano `users.login_identifier` oraz logowanie przez e-mail albo login.
- Dodano metodę provisioningu `DESK_ASSISTED`; nowy kursant otrzymuje aktywny
  dostęp na 90 dni od razu, a jedno miejsce OSK jest rezerwowane i konsumowane
  w tej samej transakcji.
- Właściciel OSK, delegowany operator i administrator platformy mogą utworzyć
  konto przy biurku. Dotychczasowy kanał `SELF_SERVICE_LINK` pozostał bez
  zmiany.
- Można wydać nowe dane tylko aktywnemu kontu utworzonemu przy biurku. Nowe
  hasło unieważnia poprzednie, nie pobiera drugiego miejsca i zapisuje audit
  bez sekretu.
- Odpowiedź z danymi jest jednorazowa: po jej pokazaniu adres przeglądarki
  wraca do listy kursantów, a wpis historii jest zastępowany bez danych.
  Odświeżenie nie odsłania loginu ani hasła i nie prowadzi do endpointu POST.
- Etap 5P zastąpił prostą blokadę `MAIL_MAILER=log` pełnym preflightem.
  Odrzuca on również `array`, `failover` i `roundrobin`, a operator widzi
  jasną informację oraz może użyć wydruku. Test zatwierdzonego SMTP jest
  wyłącznie testem zlecenia notyfikacji, bez trwałej kolejki z sekretem.

## Weryfikacja lokalna

- Migracja `2026_08_29_100000_add_desk_assisted_osk_credentials` przeszła
  lokalnie.
- `docker compose exec -T vite npm run build` przeszło.
- Pakiet regresji `EnrollmentCreditsTest`,
  `OrganizationEnrollmentManagementTest`, `AuthenticationTest` i
  `DashboardTest` przeszedł: **50 testów / 343 asercje**.
- Ręczny smoke właściciela OSK potwierdził utworzenie danych do wydruku,
  ponowne wydanie danych, natychmiastową zmianę adresu na listę kursantów i
  bezpieczne odświeżenie bez ponownego pokazania sekretu.

## Pozostałe bramy

- Nie wykonano deployu ani nie zmieniono flagi produkcyjnego pilota.
- Utworzenie konta nadal nie dodaje kursanta do allow-listy pilota.
- Produkcyjny e-mail nadal wymaga operacyjnej decyzji o mailerze, nadawcy,
  SPF/DKIM/DMARC oraz monitoringu dostarczalności, mimo że bramka kodowa
  została dodana lokalnie w Etapie 5P.

## Testy wymagane przed zamknięciem

- nowy kursant otrzymuje aktywny dostęp, login i hasło `Imie` plus losowe
  cyfry; jedna rezerwacja jest od razu skonsumowana;
- istniejący e-mail nie może zostać użyty do nadpisania hasła ani wydania
  danych desk-assisted;
- operator innego OSK nie może utworzyć konta ani wydać nowych danych;
- administrator platformy może wykonać tę samą operację bez podszywania się
  pod OSK;
- login działa zarówno dla e-maila, jak i `login_identifier`; B2C logowanie
  e-mailem nadal działa;
- nieweryfikowany desk-assisted kursant ma wejście wyłącznie do dozwolonego
  playera OSK, a zwykły nieweryfikowany użytkownik nadal trafia do weryfikacji;
- e-mail i wydruk nie zapisują surowego hasła w audycie ani w payloadzie
  późniejszego odświeżenia;
- ponowne wydanie danych unieważnia stare hasło i pozostawia ślad bez sekretu;
- regresje aktywacji linkiem, kredytów, pilota, B2C i redirectów pozostają
  zielone.

## Poza zakresem

- SMS, QR, masowe wysyłki, import plików, płatności i automatyczna
  weryfikacja firmy;
- produkcyjny mailer, SPF/DKIM/DMARC, retry kolejek i monitoring
  dostarczalności;
- automatyczne włączanie kursanta do allow-listy pilota albo deploy.
