# Główny plan odtworzenia projektu i bezpiecznych wdrożeń

Status dokumentu: **aktywny — główny punkt kontrolny**

Utworzono: **2026-09-03**

Projekt lokalny: `F:\serwistestyprawojazdy\workspace`

Produkcja: `https://prawkonaraz.pl`

## 1. Cel dokumentu

Ten dokument odpowiada na trzy pytania:

1. Co zostało już odzyskane i zweryfikowane po awarii systemu?
2. Jaki jest obecny punkt odniesienia dla kodu, danych i dokumentacji?
3. Co musimy jeszcze wykonać, aby bezpiecznie rozwijać projekt i wdrażać go na produkcję?

Każdy kolejny agent powinien przeczytać ten dokument oraz
`README-LOCAL-WORKSPACE.md` przed zmianami w repozytorium, Dockerze lub na VPS.

## 2. Znaczenie statusów

- `[x]` — wykonane i potwierdzone.
- `[ ]` — niewykonane.
- `[!]` — znane ryzyko albo decyzja wymagająca szczególnej ostrożności.
- `[~]` — element istnieje częściowo, ale nie jest jeszcze rozwiązaniem docelowym.

## 3. Aktualny punkt kontrolny

### 3.1. Kopia produkcji

- [x] Oryginalna migawka produkcji znajduje się w
  `F:\serwistestyprawojazdy\production-source`.
- [x] Migawka pochodzi z aktywnego wydania
  `20260527230800-admin-email-verified` i została pobrana 2 września 2026 r.
- [x] Oryginalne archiwum aplikacji znajduje się w
  `F:\serwistestyprawojazdy\production-source\archives\codex-export-20260902T202234Z\prawkobit-files.tar`.
- [x] W tym samym katalogu znajdują się `SHA256SUMS`, konfiguracja serwera,
  zrzut PostgreSQL i migawka Redis.
- [x] Archiwum i zrzut bazy zostały zweryfikowane podczas odzyskiwania.
- [!] `production-source` zawiera dane i sekrety produkcyjne. Nie wolno go
  modyfikować, dodawać do Git ani wysyłać do GitHuba.

Szczegóły: `F:\serwistestyprawojazdy\production-source\README-PRODUCTION-SOURCE.md`.

### 3.2. Lokalne środowisko robocze

- [x] Edytowalny kod roboczy znajduje się wyłącznie na dysku F w
  `F:\serwistestyprawojazdy\workspace`.
- [x] Media robocze znajdują się w
  `F:\serwistestyprawojazdy\workspace-data\media`.
- [x] Dane Dockera zostały przeniesione poza dysk systemowy do `F:\DockerData`.
- [x] Produkcyjny zrzut PostgreSQL został odtworzony do lokalnego wolumenu
  Dockera.
- [x] Produkcyjne media zostały skopiowane do lokalnego katalogu roboczego.
- [x] Redis działa lokalnie jako czysty magazyn; produkcyjnych sesji i cache nie
  odtwarzano.
- [x] Lokalne integracje zewnętrzne i wysyłka poczty są wyłączone w lokalnym
  `.env`.
- [x] Docker Compose działa pod nazwą projektu `serwistestyprawojazdy_f`.
- [x] Aplikacja jest dostępna pod `http://localhost:8000`, a media pod
  `http://localhost:8081`.
- [x] Błąd `502 Bad Gateway` na `/nauka` został naprawiony przez zwiększenie
  buforów FastCGI lokalnego Nginx; widok po zalogowaniu zwraca HTTP 200.
- [x] Domyślnie używane są assety `public/build` skopiowane z produkcji. Vite
  pozostaje opcjonalny w profilu `frontend-dev`.

Lokalna kopia jest środowiskiem do rozwoju i testów. Nie jest dodatkowym
serwerem produkcyjnym i nie może łączyć się z produkcyjną bazą ani usługami.

### 3.3. Repozytorium Git

- [x] Nowe, sprawne repozytorium Git znajduje się w katalogu roboczym na F.
- [x] Gałąź `main` reprezentuje bazę odzyskaną z aktywnej produkcji.
- [x] Bazowy commit to `e5dcaec`.
- [x] Bazowy tag to `production-recovery-2026-09-02`.
- [x] Naprawa lokalnego Nginx znajduje się w commicie `a5ab30d`.
- [x] Odzyskana dokumentacja znajduje się w commicie `103948a`.
- [x] Stan `main` był czysty podczas utworzenia tego planu.
- [x] Zdalne `origin` wskazuje prywatne repozytorium
  `https://github.com/prawkonaraz100/prawkonaraz`.
- [x] Gałąź `main`, historia oraz tag odzyskania zostały wysłane do prywatnego
  repozytorium GitHub.
- [!] Stare repozytorium na dysku G jest częściowo uszkodzone. Nie wolno
  kopiować jego `.git` nad repozytorium na F ani usuwać go przed zakończeniem
  odzyskiwania OSK.

### 3.4. Dokumentacja i OSK

- [x] 424 pliki starej dokumentacji zostały skopiowane bez nadpisywania
  aktualnej dokumentacji do
  `docs/recovered-from-old-project-2026-09-03`.
- [x] Integralność skopiowanych plików została potwierdzona sumami plików.
- [x] Dokumentacja OSK jest zachowana w
  `docs/recovered-from-old-project-2026-09-03/osk`.
- [!] Odzyskana dokumentacja jest materiałem historycznym, nie automatycznie
  aktualnym opisem produkcji.
- [!] Moduł OSK nie został wdrożony na produkcję przed awarią. Nie może być
  dopisywany bezpośrednio do `main` jako część produkcyjnej bazy.
- [ ] Kod OSK nie został jeszcze odzyskany z uszkodzonego repozytorium.

## 4. Ustalone decyzje techniczne

1. Prywatny GitHub będzie zdalnym repozytorium kodu i historii zmian.
2. `main` będzie reprezentował kod dopuszczony do produkcji.
3. Zmiany wykonujemy na gałęzi funkcjonalnej, sprawdzamy w CI, a następnie
   łączymy do `main`.
4. Deployment produkcyjny pozostaje uruchamiany ręcznie. Sam merge nie może
   automatycznie zmieniać produkcji.
5. Na produkcję wdrażamy konkretny commit jako pełne, wersjonowane wydanie.
6. Docelowo przełączamy dowiązanie `current` atomowo na nowe wydanie, zamiast
   stale nadpisywać pojedyncze pliki aktywnej aplikacji.
7. Produkcyjny `.env`, trwałe `storage` i inne dane współdzielone nie należą do
   repozytorium ani paczki wydania.
8. Automatyczny deploy użyje osobnego użytkownika `deploy`, a nie konta `root`.
9. GitHub Actions będzie podstawową automatyzacją, ale lokalny deployment przez
   PowerShell i SSH pozostanie pełnoprawną ścieżką awaryjną.
10. Ustawiamy twardy limit kosztów GitHub Actions. Wyczerpanie darmowych minut
    może zatrzymać automatyzację, ale nie może zatrzymać strony ani możliwości
    ręcznego wdrożenia.
11. Kopie bazy, mediów, `.env` i konfiguracji serwera są utrzymywane oddzielnie
    od GitHuba.

## 5. Plan prac

### Etap 0 — zabezpieczenie odzyskanego stanu

- [x] Zachować nieedytowalną migawkę produkcji w `production-source`.
- [x] Zachować stary projekt i uszkodzone `.git` na dysku G.
- [x] Oddzielić kod roboczy, media, dane Dockera i kopię źródłową na F.
- [ ] Wykonać drugą, niezależną kopię `production-source` na innym nośniku lub
  bezpiecznym magazynie backupowym.
- [ ] Sprawdzić możliwość odtworzenia z kopii na próbnej ścieżce, bez dotykania
  produkcji.
- [ ] Ponownie zmienić ujawnione wcześniej dane logowania administracyjnego.
- [ ] Po potwierdzeniu logowania kluczem ograniczyć logowanie hasłem i codzienne
  używanie `root`, nie odcinając wcześniej działającego dostępu awaryjnego.

Warunek zakończenia: istnieją co najmniej dwie niezależne, zweryfikowane kopie
krytycznych danych i bezpieczny dostęp administracyjny.

### Etap 1 — prywatne repozytorium GitHub

- [x] Utworzyć prywatne, puste repozytorium GitHub.
- [x] Przed pierwszym wysłaniem ponownie przeskanować historię i indeks Git pod
  kątem `.env`, kluczy, haseł, dumpów, mediów i plików prywatnych.
- [x] Dodać zdalne `origin`.
- [x] Wysłać `main` oraz tag `production-recovery-2026-09-02`.
- [x] Zweryfikować przez świeże klonowanie, że repozytorium daje się pobrać i że
  nie zawiera wykluczonych danych.
- [ ] Włączyć ochronę `main`: zmiany przez pull request, zielone kontrole przed
  merge i brak force-push.
- [ ] Skonfigurować alerty użycia Actions oraz twarde zatrzymanie kosztów po
  przekroczeniu ustawionego budżetu.
- [ ] Ustawić krótkie przechowywanie artefaktów, aby nie przekraczać bez potrzeby
  darmowego limitu przestrzeni.

Warunek zakończenia: czysty klon z GitHuba odtwarza kod i historię, ale nie
zawiera sekretów ani danych produkcyjnych.

### Etap 2 — potwierdzenie CI

- [x] Istnieją workflow `.github/workflows/ci.yml` i
  `.github/workflows/browser-smoke.yml`.
- [x] Przejrzeć główny workflow CI po odzyskaniu i dopasować go do PHP 8.3,
  SQLite, Redis oraz lokalnego storage używanego przez testy.
- [x] Uruchomić lokalnie testy PHP, kontrolę formatowania oraz produkcyjny build
  frontendu.
- [x] Uruchomić pierwszy pipeline na GitHubie.
- [~] Potwierdzono zielony test na `pull_request`; wymuszenie blokady merge po
  nieudanym CI wymaga jeszcze ochrony gałęzi `main` z Etapu 1.
- [~] Zmierzyć rzeczywisty czas pipeline i zapisać przewidywane miesięczne użycie
  darmowych minut. Zielony przebieg trwał 9 min 14 s; prognoza miesięczna nie
  została jeszcze ustalona.
- [x] Nie przechowywać pełnej kopii produkcji ani wielkich paczek wdrożeniowych
  jako artefaktów GitHub Actions.

Historyczny wynik pierwszego PR:

- pull request `#1` naprawia uruchamianie odzyskanej bazy w CI oraz rozbieżności
  ujawnione przez pierwszy przebieg,
- bootstrap, migracje, seed danych, smoke test i komplet 814 testów backendu
  przeszły,
- kontrola Laravel Pint przeszła dla 922 plików,
- produkcyjny build frontendu przeszedł,
- zielony przebieg dla commita `2d64b73` ma identyfikator `33803149375` i trwał
  9 min 14 s,
- lokalny Docker montuje teraz także katalog `scripts`, dzięki czemu Pint nie
  sprawdza już jego nieaktualnej kopii zapisanej w obrazie,
- PR `#1` został połączony z `main` 2026-09-03 w commicie
  `5e795b05a36a0d03469fff0ea846e23f70a12729`; sam merge nie uruchomił
  automatycznego deploymentu.

### Aktualna weryfikacja CI — 2026-09-15

- `main@4b10738705f3696bc2bcce730a707473eab8cd2b` nie ma obecnie zielonego
  wyniku CI. Run `34348998381` z 2026-09-09 doszedł do pełnego backendowego
  test suite i zakończył się wynikiem **830 passed / 4 failed / 2 skipped
  (18226 assertions)**.
- Trzy failure'y dotyczyły `ContactMessageTest`: żądania testowe otrzymały
  HTTP `429` od throttlingu zamiast oczekiwanych redirectów / error bag.
- Czwarty failure dotyczył `TrafficSignPagesTest`: hub znaków nie zawierał
  oczekiwanego tekstu `Nauka`.
- Przebiegi dokumentacyjnego PR `#6` od run `34995086557` (#19) do
  `35002036529` (#25) powtarzały ten sam wzorzec awarii podczas inicjalizacji
  joba `quality`: runner nie został przydzielony, a job miał **0 kroków**.
  Tych przebiegów nie wolno traktować jako test evidence kodu ani dokumentacji;
  przed merge trzeba sprawdzić najnowszy run dla aktualnego headu PR.
- Bieżący PR `#6` zmienia wyłącznie dokumentację OSK/recovery. Powyższe
  runtime failures na `main` są odnotowane jako istniejący baseline i nie są
  naprawiane w tym dokumentacyjnym kroku.


Warunek zakończenia: zielony CI potwierdza, że świeży checkout można zbudować i
przetestować bez dostępu do sekretów produkcyjnych.

### Etap 3 — przygotowanie produkcji do wersjonowanych wydań

- [~] Produkcja ma katalog `releases` i dowiązanie
  `/var/www/prawkobit/current`, ale trwałe elementy nie są jeszcze w pełni
  wydzielone do modelu `shared`.
- [ ] Wykonać i zweryfikować backup bazy, mediów, `.env` oraz konfiguracji
  serwera przed migracją układu katalogów.
- [ ] Utworzyć osobnego użytkownika `deploy` z minimalnymi uprawnieniami.
- [ ] Utworzyć oddzielny klucz SSH dla GitHub Actions i oddzielny klucz awaryjny
  dla komputera lokalnego.
- [ ] Przenieść trwały `.env` i `storage` do
  `/var/www/prawkobit/shared` oraz podłączyć je dowiązaniami.
- [ ] Ustawić poprawne własności i uprawnienia plików dla Nginx/PHP-FPM.
- [ ] Ujednolicić skrypt serwerowy tak, aby tworzył `releases/<commit-oraz-czas>`.
- [ ] Dodać atomowe przełączenie `current` dopiero po pomyślnym przygotowaniu
  wydania.
- [ ] Zachowywać ograniczoną liczbę poprzednich wydań, ale nie usuwać żadnego
  przed potwierdzonym backupem i testem rollbacku.
- [ ] Przećwiczyć rollback do poprzedniego wydania bez utraty danych.

Warunek zakończenia: nieudane nowe wydanie nie uszkadza poprzedniej działającej
wersji, a cofnięcie jest udokumentowane i sprawdzone.

### Etap 4 — ręcznie zatwierdzany deployment z GitHub Actions

- [ ] Dodać osobny workflow deploymentu uruchamiany przez `workflow_dispatch`.
- [ ] Wymagać wskazania konkretnego commita lub zatwierdzonego wydania.
- [ ] Budować paczkę bez `.env`, `storage`, mediów, dumpów, `node_modules` i
  innych danych lokalnych.
- [ ] Sprawdzać sumę kontrolną paczki przed rozpakowaniem.
- [ ] Wysyłać paczkę po SSH na konto `deploy`.
- [ ] Przed migracjami wykonać wymagany backup produkcyjnej bazy.
- [ ] Uruchamiać instalację zależności, cache, migracje i smoke testy w nowym
  katalogu wydania.
- [ ] Przełączać `current` dopiero po przejściu wymaganych kontroli.
- [ ] W przypadku błędu nie przełączać ruchu albo automatycznie wrócić do
  poprzedniego dowiązania.
- [ ] Zapisać numer commita, czas, wynik testów i aktywne wydanie w logu deployu.

Warunek zakończenia: ręcznie uruchomiony workflow wdraża dokładnie wskazany
commit, a test nieudanego wdrożenia potwierdza brak przerwy lub poprawny rollback.

### Etap 5 — bezpłatna ścieżka awaryjna bez GitHub Actions

- [ ] Utworzyć lokalny skrypt PowerShell w repozytorium, który korzysta z tej
  samej logiki paczki i wersjonowanego wydania co workflow GitHub.
- [ ] Dodać tryb `dry-run`, który pokazuje commit, listę plików, cel i planowane
  operacje bez zmiany produkcji.
- [ ] Zablokować wdrożenie z brudnego katalogu roboczego albo z commita
  niezatwierdzonego do wdrożenia.
- [ ] Dodać weryfikację backupu, sumy paczki, health check, smoke test i rollback.
- [ ] Przetestować awaryjny deployment bez używania minut GitHub Actions.
- [ ] Opisać procedurę w `docs/DEPLOYMENT-RUNBOOK.md`.

Warunek zakończenia: wyczerpanie limitu Actions lub awaria GitHuba nie blokuje
kontrolowanego deploymentu z dysku F.

### Etap 6 — odzyskanie niedokończonego modułu OSK

**Stan weryfikacji 2026-09-15 — aktualna implementacja, bez zmiany decyzji architektonicznych:**

- [x] Zweryfikowano aktualne `main@4b10738705f3696bc2bcce730a707473eab8cd2b`
  oraz branch `docs/osk-learning-flow-verification-2026-09-15`.
- [x] Potwierdzono, że dostępne drzewo kodu nie zawiera runtime'u OSK opisanego
  w odzyskanych dokumentach: brak trasy `/osk/nauka/{...}`, katalogu
  `resources/js/Pages/Osk`, `LessonPlayer.vue`, `TheoryLearningController`
  i `PublishedCourseProgramPayloadBuilder`.
- [x] Potwierdzono, że dokumentacja w
  `docs/recovered-from-old-project-2026-09-03/osk` pozostaje historycznym
  snapshotem; jej sierpniowe statusy implementacyjne nie są dowodem stanu
  aktualnego `main`.
- [!] Ta weryfikacja nie oznacza odzyskania kodu OSK ani zakończenia Etapu 6.
  Nie wolno rekonstruować bieżącego statusu implementacji wyłącznie z dokumentacji.
- [ ] Nadal trzeba wskazać rzeczywisty working tree / commit uruchamiany lokalnie
  dla `/osk/nauka/...`, zanim będzie można porównać zachowanie runtime z
  kontraktami Etapów 4B/5D/5E/5G.

- [ ] Utworzyć gałąź `recovery/osk-after-crash` z aktualnego `main`.
- [ ] Sporządzić inwentarz możliwych do odzyskania commitów, obiektów i plików ze
  starego repozytorium na G.
- [ ] Porównać odzyskany kod z dokumentacją w
  `docs/recovered-from-old-project-2026-09-03/osk`.
- [ ] Odzyskiwać małymi, sprawdzalnymi porcjami bez kopiowania starego `.git`.
- [ ] Uruchomić migracje i testy OSK wyłącznie lokalnie.
- [ ] Uzupełnić brakujące testy oraz przegląd bezpieczeństwa i zgodności danych.
- [ ] Dopiero po zakończeniu przeglądu przygotować pull request do `main`.
- [ ] Modułu OSK nie wdrażać automatycznie razem z samym odzyskaniem kodu.

Warunek zakończenia: odzyskany OSK działa lokalnie, ma zielone testy, został
porównany z dokumentacją i przeszedł osobną decyzję o wdrożeniu.

### Etap 7 — znane zadania bezpieczeństwa i utrzymania

- [ ] Osobno przeanalizować 16 zgłoszonych podatności zależności JavaScript pod
  kątem realnego wpływu na produkcję.
- [ ] Nie wykonywać automatycznej zbiorczej aktualizacji zależności na bazowym
  odzyskanym wydaniu.
- [ ] Aktualizacje wykonywać na osobnej gałęzi, z testami regresji i buildem.
- [ ] Ustalić cykliczny backup PostgreSQL, mediów i konfiguracji poza tym samym
  VPS.
- [ ] Dodać okresowy test odtworzenia backupu.
- [ ] Dodać monitoring ważności backupu, stanu aplikacji i miejsca na dysku VPS.

## 6. Docelowy przebieg codziennej pracy

```text
main -> gałąź funkcjonalna -> commit -> push -> CI -> pull request
     -> przegląd i merge -> ręczne zatwierdzenie deployu
     -> backup -> nowe wydanie -> smoke test -> przełączenie current
     -> obserwacja albo rollback
```

Nie wysyłamy na produkcję przypadkowego stanu katalogu roboczego. Jednostką
wdrożenia jest zawsze identyfikowalny commit.

## 7. Procedura awaryjna po wyczerpaniu GitHub Actions

1. Produkcja pozostaje uruchomiona; wyczerpanie limitu nie zatrzymuje VPS.
2. Nadal można wykonywać commit, push, pull request i merge.
3. Nie omijamy testów tylko dlatego, że runner GitHuba jest niedostępny.
4. Uruchamiamy wymagane testy lokalnie w Dockerze.
5. Używamy lokalnego skryptu deploymentu z Etapu 5.
6. Wdrażamy pełne, wersjonowane wydanie wskazanego commita.
7. Zapisujemy wynik i numer wydania w dzienniku deploymentów.

Do czasu ukończenia Etapu 5 awaryjną metodą pozostaje kontrolowana paczka przez
SSH według `docs/DEPLOYMENT-RUNBOOK.md`. Nie należy wykonywać improwizowanych
zmian bezpośrednio w aktywnym katalogu produkcji.

## 8. Warunki przed pierwszym nowym wdrożeniem

Przed pierwszym wdrożeniem zmian wykonanych po odzyskaniu muszą być spełnione:

- [x] prywatne repozytorium zdalne i zweryfikowany push,
- [x] skan sekretów przed publikacją repozytorium,
- [x] zielone testy adekwatne do zmiany i produkcyjny build frontendu,
- [ ] aktualny, zweryfikowany backup produkcyjnej bazy i mediów,
- [ ] potwierdzony plan rollbacku,
- [ ] wskazany konkretny commit do wdrożenia,
- [ ] brak niezamierzonych zmian w katalogu roboczym,
- [ ] jawna decyzja użytkownika o rozpoczęciu deploymentu.

## 9. Najbliższy następny krok

**Bieżący krok po weryfikacji z 2026-09-15:** zidentyfikować rzeczywisty working
tree / commit uruchamiający lokalny `/osk/nauka/...`. Dopiero w tym drzewie
należy prześledzić `LessonPlayer -> zapis ostatniego kroku -> progress ->
session close / heartbeat -> następna lekcja lub dział` i porównać zachowanie
z odzyskanymi kontraktami OSK.

Do tego czasu nie odzyskiwać ani nie promować modułu OSK do `main` na podstawie
samej dokumentacji historycznej.

**Historia:** 2026-09-03 najbliższym krokiem był przegląd i merge PR `#1`.
PR `#1` został później połączony do `main` w commicie
`5e795b05a36a0d03469fff0ea846e23f70a12729`, więc ten punkt nie jest już
bieżącym zadaniem. Zielony CI nadal nie jest zgodą na deployment; wymagania
backup/rollback i osobnej decyzji o wdrożeniu pozostają bez zmian.

## 10. Jak aktualizować ten dokument

Po zakończeniu zadania agent powinien:

1. zaznaczyć właściwe pole `[x]`,
2. zmienić status `[~]`, jeżeli rozwiązanie częściowe stało się kompletne,
3. dopisać datę i krótki wpis do dziennika poniżej,
4. nie oznaczać etapu jako zakończonego bez sprawdzenia jego warunku zakończenia,
5. nie zapisywać tutaj haseł, tokenów, prywatnych kluczy ani treści `.env`.

## 11. Dziennik wykonania

| Data | Zmiana | Dowód / identyfikator |
|---|---|---|
| 2026-09-02 | Utworzono bazę Git z aktywnej produkcji | commit `e5dcaec`, tag `production-recovery-2026-09-02` |
| 2026-09-03 | Naprawiono lokalny błąd 502 na `/nauka` | commit `a5ab30d` |
| 2026-09-03 | Zachowano odzyskaną dokumentację starego projektu | commit `103948a` |
| 2026-09-03 | Utworzono ten główny plan dalszych prac | ten dokument |
| 2026-09-03 | Utworzono prywatne repozytorium i wysłano produkcyjną bazę Git | `prawkonaraz100/prawkonaraz`, `main`, tag odzyskania |
| 2026-09-03 | Zweryfikowano historię przed publikacją i świeże klonowanie | Gitleaks 8.30.1, `git fsck --full --strict` |
| 2026-09-03 | Uruchomiono pierwszy PR i ujawniono stan testów odzyskanej bazy | PR `#1`, 743 zaliczone / 69 niezaliczonych / 2 pominięte |
| 2026-09-03 | Doprowadzono główny pipeline PR do stanu zielonego | PR `#1`, commit `2d64b73`, run `33803149375`, 814 testów, Pint 922 pliki, build OK |
| 2026-09-15 | Zweryfikowano bieżący stan odzyskanego modułu OSK względem dostępnego kodu; potwierdzono brak runtime'u OSK w aktualnym `main` i pozostawiono status odzyskania jako otwarty | `main@4b107387`, branch `docs/osk-learning-flow-verification-2026-09-15`, audyt drzewa 3209 wpisów |

## 12. Dokumenty powiązane

- [Lokalne środowisko robocze](../README-LOCAL-WORKSPACE.md)
- [Procedura deploymentu](DEPLOYMENT-RUNBOOK.md)
- [Stan CI/CD](CI-CD.md)
- [Operacje i utrzymanie](RUNBOOK-OPS.md)
- [Infrastruktura Mikrus](INFRA-MVP-MIKRUS-4.1-R2.md)
- [Notatka o odzyskanej dokumentacji](recovered-from-old-project-2026-09-03/_RECOVERY-NOTE.md)
- [Dokumentacja odzyskanego modułu OSK](recovered-from-old-project-2026-09-03/osk/README.md)
