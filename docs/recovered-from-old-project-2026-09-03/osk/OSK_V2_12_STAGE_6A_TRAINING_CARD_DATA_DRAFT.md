# OSK V2.12 - Etap 6A: robocze dane do karty PAPER

**Status:** zaimplementowane lokalnie, bez deployu. To jest etap przygotowania
danych z systemu. Nie jest to formalna karta przeprowadzonych zajec, PDF,
potwierdzenie ukonczenia teorii ani podpis OSK.

## Po co powstal ten etap

Wczesniej repozytorium mialo osobno:

- wybor `PAPER` w modelu enrollmentu;
- przypisanego pracownika OSK;
- zamrozona wersje programu;
- zrodlowe sesje i walidator czasu;
- audit log.

Brakowalo warstwy, ktora zbiera te dane w powtarzalny, niezmienny zapis bez
przepisywania ich z wielu ekranow. Etap 6A dodaje wlasnie taki zapis.

```text
CourseEnrollment
  -> wybor PAPER przed formalnym startem
  -> TrainingCardDataDraft
  -> append-only TrainingCardDataDraftRevision
  -> przyszly review OSK
  -> przyszle zaakceptowane dane dokumentacji
```

## Co istnieje w kodzie

### 1. Wybor trybu PAPER

`DocumentationModeSelectionService` pozwala administratorowi platformy albo
operatorowi OSK z uprawnieniem `DOCUMENT_MANAGE` ustawic `PAPER` dla enrollmentu
OSK.

- wybor nie jest robiony automatycznie przy zapisie kursanta;
- mozna go zmienic tylko przed `formal_started_at` albo blokada trybu;
- operacja trafia do audytu z organizacja i kanalem wykonania;
- nie zmienia `formal_state` i nie oznacza formalnego rozpoczecia kursu.

### 2. Niezmienny roboczy zapis

Migracja `2026_08_30_190000_create_osk_training_card_data_draft_foundation`
dodaje:

- `training_card_data_drafts` - jeden roboczy kontener na enrollment;
- `training_card_data_draft_revisions` - kolejne, append-only rewizje z
  canonical hashem snapshotu.

Model i rewizje odrzucaja aktualizacje oraz usuwanie z poziomu aplikacji.
Gdy stan zrodlowy rzeczywiscie sie zmieni, odnowienie danych nie nadpisuje
starej rewizji, tylko tworzy kolejna z powodem `INITIAL_SOURCE_CAPTURE` albo
`REFRESHED_SOURCE_CAPTURE`.

### 3. Brak kopii, gdy dane sa takie same

Migracja `2026_08_30_191000_add_source_state_hash_to_osk_training_card_data_draft_revisions`
dodaje hash stanu zrodlowego. Sa dwa rozne hashe:

- `source_hash` zabezpiecza dokladny snapshot, wraz z czasem jego pobrania;
- `source_state_hash` sluzy tylko do decyzji, czy potrzebna jest nowa rewizja.
  Pomija techniczne znaczniki `captured_at` i `source_time_evidence.as_of`.

Drugie klikniecie `Odswiez dane`, gdy pozostale dane sa identyczne, zachowuje
istniejaca rewizje i zapisuje audit `capture_skipped`. Nie powstaje kolejna
kopia tylko dlatego, ze zmienila sie godzina klikniecia.

### 4. Retencja historii roboczej

To nie jest retencja formalnej karty. Dotyczy wylacznie starszych rewizji
roboczych, ktore zostaly juz zastapione nowsza rewizja tego samego enrollmentu.

- domyslny okres techniczny to `90` dni, ustawiany przez
  `OSK_PAPER_DRAFT_SUPERSEDED_REVISION_RETENTION_DAYS`;
- komenda `php artisan osk:prune-training-card-draft-history` najpierw tylko
  pokazuje kandydatow; usuniecie wymaga jawnego `--apply`;
- harmonogram uruchamia usuwanie automatycznie tylko na produkcji;
  `OSK_PAPER_DRAFT_HISTORY_PRUNING_ENABLED=false` pozwala je natychmiast
  wstrzymac;
- najnowsza rewizja dla danego kursanta nigdy nie jest kasowana przez ten
  mechanizm.

Przykladowo 10 000 kursantow oznacza maksymalnie jeden aktualny roboczy
snapshot na enrollment oraz ograniczana, opcjonalna historie poprzednich
stanow. Zasady usuwania calego zestawu po zakonczeniu relacji z kursantem
pozostaja decyzja Etapu 6B, bo zaleza od formalnego procesu.

### 5. Zakres snapshotu

`TrainingCardDataDraftService` zapisuje tylko aktualnie dostepne zrodla:

- organizacje i jej nazwe prawna, gdy istnieje;
- enrollment, kategorie i wybor `PAPER`;
- identyfikator oraz nazwe profilu aplikacyjnego kursanta;
- zamrozona wersje programu i jej hash;
- przypisanego instruktora oraz numer ewidencyjny, gdy sa dostepne;
- odczytowy wynik `TimeEvidenceValidator`, laczny czas, przedzialy `UNION` i
  kody problemow;
- jasna liste brakow, ktore blokuja pozniejszy formalny proces.

Snapshot nie zapisuje hasla, tokenu, linku aktywacyjnego ani danych z
formularza urzedowego, ktorych system jeszcze nie pozyskuje.

### 6. Panel administratora

Nowa strona `/admin/dokumentacja-teorii-osk` jest dostepna tylko dla
administratora platformy. Najpierw pokazuje stronicowane grupy:

```text
szkola + kurs -> lista kursantow (maksymalnie 50 na stronie)
```

Z listy kursantow administrator moze:

1. jawnie wybrac tryb `PAPER`;
2. utworzyc pierwsza rewizje danych roboczych;
3. odswiezyc dane: kolejna rewizja powstaje tylko wtedy, gdy dane zrodlowe sie
   zmienily.

Ekran celowo komunikuje, ze nie tworzy dokumentu do druku ani podpisu. Metody
domenowe dla operatora OSK z `DOCUMENT_MANAGE` juz istnieja, ale osobny ekran
operatora OSK nie jest czescia 6A.

## Czego ten etap nie robi

- nie zbiera PESEL, daty urodzenia, numeru z rejestru OSK ani dat wpisow;
- nie uznaje danych profilu aplikacyjnego za dane urzedowe;
- nie wylicza ani nie wpisuje automatycznie zajec do formularza;
- nie wykonuje review instruktora, formalnej akceptacji ani podpisu;
- nie tworzy `PaperTrainingCardExport`, PDF ani materialu z brandingiem;
- nie oznacza teorii, kursu albo egzaminu wewnetrznego jako ukonczonych;
- nie wlacza trybu `ELECTRONIC`.

## Weryfikacja lokalna

`tests/Feature/Osk/TrainingCardDataDraftFoundationTest.php` potwierdza:

1. wybor PAPER jest blokowany po formalnym starcie;
2. capture wymaga jawnego wyboru PAPER;
3. kolejna captura tworzy nowa rewizje, a poprzednia pozostaje niezmienna;
4. identyczna captura nie tworzy kolejnej rewizji i zapisuje audit;
5. retencja obejmuje tylko stara, zastapiona rewizje i zachowuje najnowsza;
6. panel jest dostepny tylko dla administratora i przechodzi podstawowy flow.

Wynik przy implementacji: `6` testow, `50` asercji.

## Brama do Etapu 6B

Przed rozpoczeciem formalnych danych, review lub PDF potrzebne sa pisemne
decyzje wlasciciela procesu OSK/compliance:

1. ktory wariant formularza z zalacznika nr 3 jest operacyjnym wzorem (A4 czy
   A5) i jak obslugiwane sa podpisy;
2. podstawa, zakres, retencja i uprawnieni pracownicy dla danych urzedowych
   kursanta;
3. odpowiedzialnosc za wpisy zajec, korekty i review instruktora;
4. warunki utworzenia immutable eksportu i sposob oznaczania korekt.

Po tej decyzji Etap 6B moze dodac kontrolowany formularz danych formalnych i
review OSK. Generator PDF pozostaje osobnym krokiem po 6B.

W szczegolnosci okres `90` dni powyzej nie jest interpretacja przepisowej
retencji formalnej karty. Zgodnie z par. 18 ust. 2 rozporzadzenia karta
przeprowadzonych zajec oraz kopia karty osoby, ktora przerwala kurs, sa
przechowywane 24 miesiace od ostatniego wpisu, a po tym terminie podlegaja
zniszczeniu. Przed zniszczeniem trzeba wpisac liczbe godzin do rejestru
prowadzonych zajec (par. 18 ust. 3). Ten etap nie prowadzi jeszcze formalnej
karty ani rejestru, wiec nie automatyzuje tego procesu.

## Zrodla

- [Zrodlo wzoru PAPER](./OSK_V2_12_STAGE_6_PAPER_SOURCE_TEMPLATE.md)
- [Ustawa o kierujacych pojazdami - Dz. U. 2025 poz. 1226](https://api.sejm.gov.pl/eli/acts/DU/2025/1226/text.pdf)
- [Rozporzadzenie - Dz. U. 2018 poz. 1885](https://api.sejm.gov.pl/eli/acts/DU/2018/1885/text.html), w tym par. 18

To jest dokumentacja projektowo-compliance, a nie samodzielna opinia prawna.
