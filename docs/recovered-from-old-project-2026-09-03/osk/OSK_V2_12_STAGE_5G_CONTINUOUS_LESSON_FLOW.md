# OSK V2.12 - Etap 5G: plynne przejscie miedzy lekcjami

**Historyczny status local worktree:** zaimplementowany i zweryfikowany lokalnie; bez deployu.
**Historyczna data weryfikacji:** 2026-08-26.
**Historyczny branch implementacyjny:** `codex/osk-learning-dashboard-ui`.

Ten etap usuwa niepotrzebny przystanek po ostatnim kroku lekcji. Nie zmienia
znaczenia czasu, postepu ani formalnego ukonczenia kursu OSK.

## 1. Kontrakt UX

Kursant wykonuje normalnie jedna glowna akcje po prawej stronie playera.

| Stan biezacej lekcji | Glowny przycisk |
| --- | --- |
| sa kolejne kroki | `Dalej` |
| ostatni krok, kolejna lekcja w tym samym dziale | `Przejdz do nastepnej lekcji` |
| ostatni krok, kolejny dzial | `Przejdz do nastepnego dzialu` |
| ostatni krok ostatniej lekcji kursu | `Zakoncz nauke` |

`Zakoncz sesje` zostaje obok jako drugorzedna, swiadoma akcja przerwania
nauki. Tylko ta akcja oraz timeout prowadza do bramki `Sesja zakonczona`.
Zwykle ukonczenie lekcji nie powinno pokazywac tej bramki.

Przy ostatniej lekcji `Zakoncz nauke` najpierw bezpiecznie zamyka aktywna
sesje przez istniejacy endpoint, a nastepnie prowadzi do planu kursu. To nie
oznacza formalnego ukonczenia kursu, modulu ani wymagania prawnego.

## 2. Granice techniczne

1. `PublishedCourseProgramPayloadBuilder::nextLessonAfter()` wyznacza kolejna
   lekcje wylacznie z zamrozonego programu przypietego do enrollmentu.
2. `TheoryLearningController` przekazuje do playera gotowy, sprawdzony URL,
   tytul lekcji, tytul dzialu i typ przejscia. Vue nie uklada routingu z
   kodow ani identyfikatorow.
3. `LessonPlayer.vue` przed przejsciem zapisuje ostatni widoczny krok przez
   istniejacy endpoint postepu. Gdy zapis nie powiedzie sie albo sesja nie
   jest juz aktywna, nie zmienia strony.
4. Gdy zapis sie powiedzie, Inertia przechodzi do nastepnej lekcji z
   `preserveState`. Ta sama otwarta sesja oraz jej heartbeat pozostaja
   aktywne, a nowy pierwszy widoczny krok jest od razu zapisany jako kursor.
5. Zmiana nie dodaje migracji, nowych tabel, nowego cron-a ani logiki
   formalnego ukonczenia.

## 3. Zachowanie przy bledach

| Sytuacja | Zachowanie |
| --- | --- |
| zapis ostatniego kroku nie powiedzie sie | kursant pozostaje na obecnej lekcji; istnieje ostrzezenie polaczenia i brak optymistycznego przejscia |
| Inertia anuluje lub odrzuci przejscie | przycisk odblokowuje sie na obecnej lekcji |
| backend zwraca `SESSION_CLOSED` | player zatrzymuje nauke i pokazuje istniejaca bramke ponownego startu |
| kursant wybiera `Zakoncz sesje` | sesja konczy sie swiadomie, zgodnie z dotychczasowym kontraktem Etapu 5D |

## 4. Co pozostaje poza zakresem

- formalne ukonczenie lekcji, modulu lub kursu;
- `TheoryCompletionGate`, assessment i formalne evidence;
- zmiany w istniejacym `/nauka`, `StudySession`, `QuestionCollection` albo
  egzaminie B2C;
- deploy lub wlaczenie pilota produkcyjnego.

## 5. Historyczna weryfikacja lokalna — 2026-08-26

Automatycznie:

```powershell
docker exec -i -w /var/www/html serwistestyprawojazdy-app-1 php artisan test tests/Feature/Osk --compact
docker exec -i -w /app serwistestyprawojazdy-vite-1 npm run build
```

Wynik historycznej weryfikacji z 2026-08-26: **60 testow / 606 asercje**, a `vue-tsc` i
`vite build` przeszly bez bledu.

Testy celu obejmuja:

- wyznaczenie kolejnej lekcji w tym samym dziale, w kolejnym dziale i brak
  kolejnej lekcji na koncu zamrozonego programu;
- kontynuacje jednej otwartej sesji przez kolejne lekcje i moduly;
- zachowanie fail-closed dla cudzych enrollmentow, uszkodzonych snapshotow i
  zamknietej sesji.

Historyczny manualny smoke dla tego local worktree mial potwierdzic kolejno:

1. ostatni krok pierwszej lekcji pokazuje `Przejdz do nastepnej lekcji`;
2. klikniecie otwiera kolejna lekcje bez bramki startu i bez `close` sesji;
3. ostatni krok drugiej lekcji pokazuje `Przejdz do nastepnego dzialu`;
4. ostatnia lekcja pokazuje `Zakoncz nauke` i prowadzi do planu kursu po
   zapisaniu zamkniecia sesji;
5. `Zakoncz sesje` klikniete w srodku lekcji nadal pokazuje `Sesja zakonczona`.

## 6. Wskazowka dla kolejnego agenta

Nie zamieniaj `Zakoncz nauke` na `Zakoncz kurs` i nie dodawaj automatycznego
uznania ukonczenia tylko dlatego, ze kursant dotarl do konca listy krokow.
Ta decyzja wymaga osobnego kontraktu formalnego, polityki i testow.

## 7. Biezaca weryfikacja po odzyskaniu repozytorium — 2026-09-15

### 7.1. Decyzja architektoniczna

Kontrakt opisany w sekcjach 1-4 pozostaje decyzja projektowa dla przeplywu
Learning Engine: `Zakoncz sesje` jest akcja swiadomego przerwania nauki, a nie
obowiazkowa bramka pomiedzy poprawnie ukonczona lekcja i kolejnym dozwolonym
elementem. Nie zmieniac tej decyzji tylko dlatego, ze aktualnie uruchomiony
lokalny build zachowuje sie inaczej.

### 7.2. Aktualny stan implementacji potwierdzony w dostepnym repozytorium

Stan `zaimplementowany i zweryfikowany lokalnie` z poczatku tego dokumentu
jest **historycznym wynikiem z 2026-08-26** dla branchu
`codex/osk-learning-dashboard-ui`. Nie jest sam w sobie dowodem, ze ta
implementacja znajduje sie obecnie na `main`.

Weryfikacja GitHub z 2026-09-15 wykazala, ze aktualny
`prawkonaraz100/prawkonaraz@main`:

- nie zawiera trasy `/osk/nauka/{...}` ani osobnego `routes/osk.php`;
- nie zawiera katalogu `resources/js/Pages/Osk` z historycznym
  `LessonPlayer.vue`;
- nie zawiera potwierdzonych w tym dokumencie klas
  `TheoryLearningController` i
  `PublishedCourseProgramPayloadBuilder`;
- przechowuje ten plik jako odzyskana dokumentacje historyczna w
  `docs/recovered-from-old-project-2026-09-03/osk/`.

Branch `codex/osk-learning-dashboard-ui` nie jest obecnie dostepny wsrod
aktywnych branchy tego repozytorium. Z tego powodu nie wolno oznaczac Etapu 5G
jako potwierdzonego elementu aktualnego `main` bez odzyskania lub wskazania
rzeczywistego drzewa kodu, z ktorego dziala lokalny adres `/osk/nauka/...`.

### 7.3. Zaobserwowana rozbieznosc runtime

W lokalnym runtime zaobserwowano obecnie przeplyw:

```text
ostatni Step
-> Dalej staje sie nieaktywne
-> wymagane Zakoncz sesje
-> ekran Sesja zakonczona
-> Plan kursu / Rozpocznij ponownie
```

Ten stan jest **rozbiezny z kontraktem Etapu 5G**, ale bez dostepu do
rzeczywistego kodu tego lokalnego builda nie wiadomo jeszcze, czy przyczyna jest:

- regresja w `LessonPlayer`;
- uruchomienie starszego checkoutu/branchu;
- brak historycznej implementacji 5G w aktualnym drzewie;
- zmiana backendowego payloadu/nawigacji;
- inna konfiguracja lokalnego srodowiska.

Nie dopasowywac decyzji architektonicznej do tej rozbieznosci. Najpierw
zidentyfikowac rzeczywiste zrodlo uruchomionego kodu.

### 7.4. Ukończone w ramach wznowienia 2026-09-15

- sprawdzono aktualne branche i `main` dostepnych repozytoriow GitHub;
- sprawdzono aktualny `routes/web.php` oraz drzewo plikow
  `prawkonaraz100/prawkonaraz@main`;
- potwierdzono, ze bieżący `main` nie zawiera runtime'u OSK opisanego w tym
  dokumencie;
- nie zmieniono kodu runtime, kontraktu czasu ani logiki sesji;
- zachowano historyczne wyniki testow jako evidence historyczne, a nie jako
  nowa walidacje aktualnego `main`.

### 7.5. Pozostala praca przed jakakolwiek zmiana UX

1. Ustalic dokladny working tree / commit / branch uruchamiany pod lokalnym
   `http://localhost:8000/osk/nauka/...`.
2. W tym drzewie prześledzic end-to-end:
   `LessonPlayer` -> zapis ostatniego kroku -> progress -> session close /
   heartbeat -> wyznaczenie kolejnej lekcji lub dzialu.
3. Zidentyfikowac wszystkie side effects `Zakoncz sesje` przed jego
   oddzieleniem od normalnej nawigacji.
4. Dodac lub uruchomic testy regresyjne obecnego zachowania sesji i postepu.
5. Dopiero potem wykonac najmniejszy refactor przywracajacy glowny CTA zgodny
   z kontraktem 5G / Continuation Contract.

Do czasu wykonania punktow 1-4 status bieżącej implementacji tego przeplywu
nalezy traktowac jako **WYMAGA WERYFIKACJI W RZECZYWISTYM LOCAL WORKTREE**.

