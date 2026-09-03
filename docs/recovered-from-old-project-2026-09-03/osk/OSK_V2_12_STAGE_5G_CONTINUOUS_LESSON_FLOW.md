# OSK V2.12 - Etap 5G: plynne przejscie miedzy lekcjami

**Status:** zaimplementowany i zweryfikowany lokalnie; bez deployu.
**Data weryfikacji:** 2026-08-26.
**Branch implementacyjny:** `codex/osk-learning-dashboard-ui`.

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

## 5. Weryfikacja lokalna

Automatycznie:

```powershell
docker exec -i -w /var/www/html serwistestyprawojazdy-app-1 php artisan test tests/Feature/Osk --compact
docker exec -i -w /app serwistestyprawojazdy-vite-1 npm run build
```

Wynik aktualnej weryfikacji: **60 testow / 606 asercje**, a `vue-tsc` i
`vite build` przeszly bez bledu.

Testy celu obejmuja:

- wyznaczenie kolejnej lekcji w tym samym dziale, w kolejnym dziale i brak
  kolejnej lekcji na koncu zamrozonego programu;
- kontynuacje jednej otwartej sesji przez kolejne lekcje i moduly;
- zachowanie fail-closed dla cudzych enrollmentow, uszkodzonych snapshotow i
  zamknietej sesji.

Manualny smoke na lokalnym pilocie powinien potwierdzic kolejno:

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
