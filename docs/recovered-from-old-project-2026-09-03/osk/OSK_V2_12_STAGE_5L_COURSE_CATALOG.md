# OSK V2.12 - Etap 5L: katalog i cykl zycia kursow kategorii B

**Status:** zakonczony lokalnie 2026-08-28. Zakres tego etapu obejmuje tylko
kategorie B. Nie ma deployu, zmiany flagi srodowiskowej ani uruchomienia
produkcji.

## Cel

Administrator ma samodzielnie utworzyc, odnalezc, zarchiwizowac albo przywrocic
kurs kategorii B bez edycji kodu i bez ryzyka skasowania historii kursantow.
Katalog ma pozostac czytelny, gdy liczba kursow rosnie.

Etap nie dodaje formularza dla kategorii A, AM, B1, C, D ani innych. Model
Course nadal przechowuje ich typ i kategorie, ale ich tworzenie jest osobnym
przyszlym zakresem produktowym.

## Rozdzielone znaczenia

Kurs ma trzy niezalezne osie stanu:

1. **Wersja tresci** - DRAFT, PUBLISHED albo ARCHIVED wersji programu.
2. **Dostepnosc kursu** - is_active; aktywny kurs z gotowa wersja moze zostac
   dopuszczony do pilota wedlug istniejacych bram.
3. **Archiwum katalogu** - courses.archived_at; kurs nie jest juz przyjmowany
   do nowych zapisow, ale historia pozostaje zachowana.

Archiwizacja nie ustawia automatycznie is_active=false. Dzieki temu nie odbiera
dostepu osobie, ktora juz uczy sie na aktywnym enrollmentie. Gdy trzeba
natychmiast zatrzymac dostep wszystkim, administrator korzysta z istniejacej
akcji **Wstrzymaj kurs**. To rozroznienie jest celowe.

## Co powstalo

### Tworzenie kursu B

CourseCatalogService::createCategoryBDraft() jest jedyna nowa sciezka tworzenia
w panelu. Tylko administrator platformy podaje nazwe kursu i techniczny kod,
unikalny po normalizacji do malych liter.

Serwis zawsze tworzy kurs typu CATEGORY, kategorii B, z wersja V1 w stanie
DRAFT, bez modulow, lekcji, krokow, enrollmentow i dostepu kursantow.
is_active pozostaje false. Akcja jest zapisywana w audycie jako
osk.course.catalog_created.

### Katalog administracyjny

/admin/programy-nauki-osk ma teraz katalog, a nie liste wszystkich drzew
programu:

- do 20 kursow na stronie;
- wyszukiwanie po nazwie i kodzie od trzech znakow;
- filtry: wszystkie, robocze, aktywne i archiwum;
- lekkie liczniki stanu;
- pobieranie pelnego drzewa modulow, lekcji i krokow tylko dla aktualnie
  otwartego draftu.

Ta granica ogranicza rozmiar odpowiedzi i renderowania; nie ma ladowania setek
kursow oraz ich krokow do jednego widoku.

### Archiwizacja, przywrocenie i usuniecie

CourseCatalogService zapisuje akcje:

- osk.course.archived;
- osk.course.restored;
- osk.course.empty_draft_deleted.

Archiwum blokuje nowy enrollment oraz ponowna aktywacje kursu do pilota.
Przywrocenie cofa tylko znacznik archiwum; nie wlacza kursu, nie tworzy dostepu
i nie zmienia tresci.

Usunac wolno wylacznie pusty, nieopublikowany draft bez modulow i bez
enrollmentow. Kurs z trescia, publikacja albo historia zapisu przechodzi do
archiwum. Nie ma masowego usuwania ani masowej aktualizacji.

## Zmiana bazy

Migracja 2026_08_28_130000_add_archival_to_osk_courses dodaje nullable
archived_at oraz indeks courses_catalog_state_idx na polach katalogu:
archived_at, is_active, target_entitlement_type.

Migracja zostala zastosowana tylko lokalnie.

## Niezmienione bramy dostepu

Etap 5L nie omija zadnej z bram Etapu 5K. Aby kursant otworzyl player, nadal
potrzebne sa: zamrozona poprawna wersja, aktywny kurs, aktywny dostep, allow-lista
enrollmentu i wlaczona flaga srodowiskowa. /nauka pozostaje osobnym modulem
testow egzaminacyjnych.

## Weryfikacja lokalna

Przeszly:

- tests/Feature/Admin/OskTheoryLearningProgramsPageTest.php: 12 testow / 78 asercji;
- tests/Feature/Osk/CourseCatalogLifecycleTest.php: 5 testow / 35 asercji;
- tests/Feature/Osk: 83 testy / 838 asercji.

Pokryte sa m.in. tworzenie B, uprawnienia administratora, wyszukiwanie,
archiwizacja, przywrocenie, blokada nowego enrollmentu dla archiwum, zachowanie
dostepu juz zapisanego kursanta oraz blokada aktywacji zarchiwizowanego kursu.

Panel zostal otwarty recznie lokalnie pod /admin/programy-nauki-osk; katalog
pokazuje tylko lekka liste, a otwarty jest jeden roboczy program.

## Granice i nastepny krok

Nie dodano innych kategorii, masowego przypisywania, masowej aktualizacji
programow, formalnego ukonczenia, evidence, assessmentu, egzaminu, PAPER, maili
ani deployu.

Przed kolejnym zakresem kategorii trzeba osobno ustalic model kopiowania kursu,
widocznosc w katalogu, tresci i wlasciciela akceptacji. Przed pilotem
produkcyjnym nadal wymagana jest osobna decyzja biznesowa opisana w Etapie 5K.
