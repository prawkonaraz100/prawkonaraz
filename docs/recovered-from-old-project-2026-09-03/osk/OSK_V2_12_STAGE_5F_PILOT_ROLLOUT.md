# OSK V2.12 - Etap 5F: kontrolowany rollout pilota nauki teorii

**Status:** zakonczony lokalnie 2026-08-26; istnieje recznie uruchamiany, fikcyjny seed tylko dla local/testing. Brak deployu, seeda produkcyjnego i otwartego dostepu dla kursantow.

## Cel

Etap 5F nie uruchamia produktu dla wszystkich. Dodaje bezpieczna, odwracalna kontrole dostepu do istniejacego, odizolowanego playera OSK.

Player pozostaje calkowicie oddzielony od obecnej sciezki /nauka. Nie zmieniono StudySession, pytan egzaminacyjnych, kolekcji, B2C accessu ani checkoutu.

## Dwie niezalezne bramy

Kursant moze zobaczyc nauke teorii OSK tylko wtedy, gdy wszystkie warunki sa spelnione:

1. OSK_THEORY_LEARNING_PILOT_ENABLED=true jest swiadomie ustawione w srodowisku;
2. administrator recznie dopuscil konkretny CourseEnrollment;
3. enrollment nalezy do aktualnie zalogowanego kursanta;
4. StudentCourseAccess jest aktywny;
5. enrollment wskazuje zamrozony, kompletny snapshot opublikowanego programu.

Brak ktoregokolwiek warunku jest fail-closed: indeks kursow jest pusty, a bezposredni adres playera zwraca 404.

### Brama srodowiskowa

Konfiguracja:

```text
OSK_THEORY_LEARNING_PILOT_ENABLED=false
```

To twardy wylacznik awaryjny i domyslny stan po wdrozeniu. Jest poza panelem administratora celowo: przypadkowe klikniecie w panelu nie moze globalnie otworzyc technicznie niedojrzalego pilota.

### Dopuszczenie enrollmentu

Tabela theory_learning_pilot_accesses przechowuje jedna aktualna decyzje dla jednego enrollmentu:

- is_enabled;
- czas i administrator ostatniego wlaczenia;
- czas i administrator ostatniego wylaczenia.

Zmiana stanu jest serializowana transakcja i blokada enrollmentu. Kazda rzeczywista zmiana trafia rowniez do istniejacego audit_logs z identyfikatorem kursanta, enrollmentu i wersji kursu.

Nie ma zadnego automatycznego dopuszczenia po utworzeniu enrollmentu, aktywacji tokenu ani publikacji kursu.

## Panel administratora

Nowa pozycja w grupie Dostep:

```text
/admin/pilot-nauki-teorii-osk
```

Panel pokazuje:

- stan bramy srodowiskowej;
- liczbe wszystkich enrollmentow i liczbe recznie dopuszczonych;
- wyszukiwarke kursanta, OSK, kursu lub ID enrollmentu;
- biezacy dostep kursowy i stan pilota;
- potwierdzane akcje Wlacz pilot oraz Wylacz pilot.

Administrator moze przygotowac liste pilotowa, gdy globalna brama jest jeszcze wylaczona. Nie daje to kursantowi dostepu, dopoki nie zostanie wykonana osobna, swiadoma zmiana srodowiskowa.

## Zmiany techniczne

- dodano addytywna migracje 2026_08_26_100000_create_osk_theory_learning_pilot_accesses;
- dodano model TheoryLearningPilotAccess i factory;
- TheoryLearningPilotAccessService jest jedyna sciezka administracyjnej zmiany allow-listy;
- TheoryLearningAccessService wymaga obu bram takze dla sesji i postepu, nie tylko dla strony playera;
- dodano strone Filament OskTheoryLearningPilot.

## Czego etap nie robi

- nie tworzy prawdziwego kursu ani zatwierdzonej tresci pilota; lokalny seed demonstracyjny nie jest materialem produkcyjnym;
- nie dodaje linku do glownego menu;
- nie uruchamia pilota na produkcji;
- nie uznaje ukonczenia, nie tworzy evidence ani assessmentu;
- nie otwiera dostepu w obecnym /nauka;
- nie zmienia formalnego egzaminu, PAPER, rezerwacji jazd ani platnosci.

## Lokalny scenariusz demonstracyjny

Wyłącznie na `local` lub `testing` można świadomie uruchomić przykładowy kurs
kategorii B:

```powershell
docker compose exec -T app php artisan osk:seed-demo-theory-pilot
```

Polecenie tworzy albo wykorzystuje oznaczone jako testowe konta z domeny
`example.test`, fikcyjnego partnera `OSK Bezpieczny Start - DEMO lokalne` oraz
jeden aktualnie dopuszczony enrollment. Na pustej bazie program ma dwa moduly,
trzy krotkie lekcje i pietnascie nieformalnych krokow. Nie zmienia globalnej
bramy pilota i nie działa poza `local`/`testing`.

Jesli lokalna baza zawiera wczesniejszy, rozpoznany przyklad z jedna lekcja,
polecenie nie zmienia opublikowanej wersji 1. Tworzy draft wersji 2 przez
`CourseProgramService::forkNextDraft`, dopisuje dodatkowe lekcje, publikuje
nowy snapshot i dopuszcza nowy enrollment. Poprzedni enrollment, dostep
kursanta i jego historia zostaja nienaruszone; seed wylacza wylacznie jego
pilotowa allow-liste przez serwis domenowy z audytem. Nieznany, niekompletny lub
ambiwalentny stan wersji konczy sie bledem bez zgadywania i bez mutacji danych.

LessonPlayer wymaga dokładnie jednej aktywnej polityki czasu. Seed wykorzystuje
istniejącą taką politykę, a na całkiem pustej lokalnej bazie tworzy pojedynczą
wersję demonstracyjną. Kilka aktywnych wersji jest błędem fail-closed i seed nie
zmienia wtedy danych.

W Docker Compose flaga środowiskowa jest przekazywana jawnie do usług `app` i
`scheduler`. Po lokalnej zmianie `OSK_THEORY_LEARNING_PILOT_ENABLED` trzeba
odtworzyć te kontenery, aby otrzymały nową wartość:

```powershell
docker compose up -d --force-recreate app scheduler
```

Nie przenoś tego seeda ani tych kont na produkcję. Prawdziwy pilot wymaga
osobnej, zatwierdzonej treści i enrollmentu wskazanego przez administratora.

## Bezpieczne uruchomienie pozniej

1. Zatwierdzic pierwsze OSK, kategorie, tresc, zrodla oraz wlasciciela merytorycznego.
2. Wdrozyc kod i addytywna migracje, zachowujac globalna brame jako false.
3. W panelu administratora wskazac jeden poprawny enrollment z aktywnym dostepem i zamrozonym programem, a nastepnie kliknac Wlacz pilot.
4. Osoba wdrazajaca ustawia OSK_THEORY_LEARNING_PILOT_ENABLED=true, odswieza cache konfiguracji zgodnie z runbookiem i wykonuje smoke test wlascicielem enrollmentu.
5. Sprawdzic, ze cudzy kursant, enrollment wygasly i /nauka nadal nie maja dostepu.
6. W razie problemu ustawic brame srodowiskowa z powrotem na false. Nie usuwa to enrollmentow, sesji ani postepu.

## Weryfikacja lokalna

Potwierdzono lokalnie:

- rozszerzony seed: 3 testy / 70 asercji, w tym swiezy program, idempotencja i przejscie z wczesniejszej lokalnej wersji 1 do wersji 2;
- pelny pakiet OSK wraz z panelem pilota i seedem: 60 testow / 526 asercji;
- rzeczywiste lokalne przejscie: V1 zachowana jako immutable, V2 opublikowana z 2 modulami, 3 lekcjami i 15 krokami, a widoczny pozostaje tylko enrollment V2;
- pakiet celu playera, sesji, kursora i panelu administratora: 28 testow / 283 asercje;
- regresje obecnej nauki, kolekcji, dostepu B2C i audytu: 72 testy / 1265 asercji;
- plan migracji PostgreSQL i lokalna migracja: przeszly;
- build Vue/Vite, Laravel Pint oraz kontrola diffu: przeszly.

Pakiet obejmuje globalna blokade, allow-liste, cofniecie dostepu, audyt i ochrone przed zmiana przez nieadministratora.

Rozszerzony seed ma dodatkowo test regresji przejscia z wczesniejszej lokalnej
wersji 1 do wersji 2 oraz potwierdzenie, ze kursant widzi po migracji tylko
aktualny enrollment pilota.

## Nastepny krok

Najpierw decyzja produktowa o realnej tresci oraz pierwszym partnerze OSK. Dopiero potem mozna wykonac kontrolowany smoke test z jednym prawdziwym enrollmentem. Formalne evidence, ukonczenie i assessment nadal wymagaja osobnego kontraktu.
