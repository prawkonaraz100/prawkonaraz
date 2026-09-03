# System wizualnych objasnien do pytan

## Status

Ten dokument zostal juz wykorzystany jako pierwszy krok wdrozeniowy warstwy premium nauki.

Jawna kolejnosc rekomendowana:

1. `QUESTION-EXPLANATION-VISUAL-SYSTEM.md`
2. `PREMIUM-REVIEW-TRAINER-ROADMAP.md` - planner i silnik decyzji
3. `ADAPTIVE-LEARNING-ARCHITECTURE.md` - integracja trenera z warstwa wyjasnien

Powod:

- system wizualnych objasnien jest niezalezny od inteligentnego planera,
- moze dawac wartosc edukacyjna od razu,
- nie wymaga jeszcze event logu review ani adaptive policy,
- tworzy materialy i komponenty, ktore trener wykorzysta pozniej jako interwencje.

Stan na `2026-04-13`:

- mamy juz tekstowe `questions.explanation`,
- mamy inline edycje wyjasnienia z trybu nauki dla admina,
- mamy bezpieczne pogrubienia `**tekst**`,
- mamy kolorowanie inline:
  - `[green]tekst[/green]` dla akcentu poprawnej odpowiedzi,
  - `[red]tekst[/red]` dla akcentu blednej odpowiedzi,
  - runtime dobiera palete per shell sesji:
    - `zen` -> kolory Zen,
    - `exam_like` (Nauka klasyczna) -> kolory klasyczne,
  - kursant moze tez przelaczac widocznosc formatowania w panelu `Ustawienia nauki`:
    - `Pogrubienia` (`**tekst**`),
    - `Kolory` (`[green]/[red]`),
- mamy shared flow dla grafiki karty odpowiedzi po `external_id + source`:
  - runtime rozwiazuje asset w kolejnosci `local override -> shared asset -> fallback SVG`,
  - admin moze zapisac grafike tylko dla jednego pytania albo dla calej grupy wspolnego pytania,
  - lokalny override nadal jest wspierany,
- mamy wdrozony `Sprint 1 / MVP v1` dla warstwy wizualnej:
  - tabele `question_explanation_assets`,
  - jeden aktywny blok referencyjny `reference_sign`,
  - globalne ustawienie `visual_explanations_enabled`,
  - lokalny toggle widocznosci w sesji nauki,
  - render bloku referencyjnego po odpowiedzi w nauce,
  - render bloku referencyjnego w review po sesji,
  - render bloku referencyjnego w wyniku egzaminu po fakcie,
  - podglad bloku referencyjnego bezposrednio w formularzu pytania w adminie,
  - brak tych pomocy w aktywnym egzaminie.
- mamy tez wdrozony `Etap 2 / adnotacje MVP`:
  - tabele `question_explanation_annotations`,
  - typy `label`, `circle` i `arrow`,
  - formularzowe zarzadzanie adnotacjami w panelu admina,
  - render overlayow na obrazie pytania po blednej odpowiedzi w nauce,
  - render overlayow w review po sesji,
  - render overlayow w wyniku egzaminu po fakcie,
  - podstawowe wsparcie `video_frame` i `frame_time_seconds` dla pytan filmowych,
  - render stopklatki z filmu z overlayami w nauce, review i wyniku egzaminu,
  - brak overlayow w aktywnym egzaminie.
- helper admina jest juz bardziej rozbudowany UX-owo:
  - ma panel wybranej adnotacji,
  - pozwala zmieniac typ, ton i aktywnosc bez schodzenia do repeatera,
  - pozwala przesuwac, zmieniac rozmiar `circle`, duplikowac i zmieniac kolejnosc,
  - pozwala przeciagac marker bezposrednio na canvasie,
  - pokazuje uchwyt resize dla `circle`,
  - ma tez scrubber / timeline stopklatki wideo dla wybranej sekundy,
  - pokazuje zapisane klatki jako szybkie przeskoki po bucketach `frame_time_seconds`,
  - pozwala nudge'owac czas stopklatki o `±1s` i `±5s`,
  - zostal uproszczony redakcyjnie:
    - mniej technicznego copy,
    - prostsze nazwy sekcji,
    - mocniejsze rozdzielenie trybu glownego od pol zaawansowanych,
    - krokowy uklad `Dodaj marker -> Kliknij w kadr -> Ustawienia markera`,
    - jednokolumnowy flow lepiej dopasowany do realnej szerokosci formularza Filament,
    - styling przeniesiony do adminowego `theme.css`, zeby helper nie byl zalezny od inline `<style>` w partialu,
    - wybrany `circle` pokazuje teraz od razu finalny obrys na kadrze, a uchwyty edycyjne sa odsuniete na rant zaznaczenia zamiast zaslaniac sam obszar,
    - akcje `duplikuj / usun / ukryj` sa przeniesione do panelu wybranego markera zamiast obciazac gore helpera,
    - precyzyjne pola `x / y / width / height` sa schowane pod jednym rozwijanym blokiem `Precyzyjne ustawienia`,
    - pusty stan helpera prowadzi redakcje przez prosty flow `dodaj marker -> kliknij w obraz -> dopracuj po prawej`,
  - repeater zostaje fallbackiem dla surowych pol procentowych i bardziej zlozonej konfiguracji.
  - lista `Baza pytan` w adminie ma teraz tez:
    - licznik `Markery` przy kazdym pytaniu,
    - filtr `Markery: Z markerami / Bez markerow`, zeby redakcja mogla szybko wyciagac pytania z warstwa wizualna.

## Aktualizacja `2026-04-16`

### Stabilizacja zapisu markerow i runtime sesji

- panel admina ma teraz bezpieczny fallback bindowania stanu adnotacji:
  - domyslny path to `data.explanation_annotations`,
  - dodatkowy guard w JS normalizuje starszy path `explanation_annotations` do poprawnej sciezki Livewire,
  - submit formularza wymusza tez snapshot markerow do payloadu (`$wire.set(..., false)`), zeby uniknac race condition miedzy entangle a kliknieciem `Zapisz`,
  - efekt: etykiety i okregi zapisane w adminie wracaja do bazy i sa widoczne w trybie nauki.
- panel `Wybrany marker` jest tez prostszy dla redakcji:
  - pole `Tekst etykiety` jest stale widoczne,
  - gdy wybrany marker to `circle`, input jest blokowany i pokazuje akcje `Wlacz etykiete`,
  - po przelaczeniu na `label` fokus trafia od razu do pola edycji tekstu.
- silnik progresu pytan ma guard na `interval_days`:
  - wartosc jest clampowana do bezpiecznego limitu runtime (`1..32767`) zgodnego z PostgreSQL `smallint`,
  - eliminuje to blad SQL `Numeric value out of range` podczas zapisu odpowiedzi,
  - efekt: znika chwilowy komunikat o "wstrzymanym zapisie odpowiedzi" wywolywany przez overflow.
- pokrycie testami:
  - `tests/Feature/Admin/QuestionVisualExplanationEditPageTest.php` sprawdza path i helper adnotacji,
  - `tests/Feature/QuestionProgressOverflowGuardTest.php` zabezpiecza scenariusz overflow progresu.

### Plan rozszerzenia o strzalki

- przygotowany zostal osobny, techniczny plan wdrozenia typu adnotacji `arrow`:
  - [ANNOTATION-ARROW-EXTENSION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ANNOTATION-ARROW-EXTENSION-PLAN.md)
- plan zostal wdrozony:
  - model danych ma `arrow_length_percent`, `arrow_angle_degrees`, `arrow_stroke_percent`, `arrow_head_percent`,
  - admin helper obsluguje dodawanie i edycje strzalek (w tym uchwyt kierunku `↗`),
  - runtime renderuje strzalki na obrazie i stopklatce filmu,
  - testy backend + frontend zabezpieczaja regresje `label/circle/arrow`.

### Kompatybilnosc markerow image/video

- runtime markerow w sesji i wyniku egzaminu ma teraz jawny fallback dla pytan filmowych:
  - jesli sa adnotacje `video_frame`, renderujemy je jako priorytet,
  - jesli pytanie filmowe nie ma `video_frame`, ale ma starsze markery `question_image`, traktujemy je jako fallback i pokazujemy na stopklatce filmu,
- dzieki temu starsze rekordy z markerami obrazowymi na pytaniach video nie znikaja po odpowiedzi.
- aktywne pytanie filmowe w nauce nie blokuje juz playera po dodaniu adnotacji `video_frame`:
  - gdy film nie gra, mozemy dalej pokazac adnotowana stopklatke,
  - gdy film zaczyna grac, priorytet przejmuje prawdziwy element `<video>`,
  - po zatrzymaniu albo zakonczeniu filmu preview z adnotacjami moze znowu byc widoczne,
  - dzieki temu autoplay, ponowne odtwarzanie i `Pokaz koncowke filmu` dzialaja tak samo jak bez adnotacji.
  - sam preview jest teraz ujawniany dopiero po doseekowaniu do docelowej klatki:
    - nie pokazujemy juz userowi posredniego stanu `pierwsza klatka -> klatka docelowa`,
    - przy aktywnym playerze overlay nie zaslania filmu posterem podczas przygotowania stopklatki,
    - efekt UX: strzalka pojawia sie bez agresywnego blysku i bez cofania obrazu do startu filmu.
  - dodatkowy guard layoutu pilnuje, zeby preview nie dostawalo naraz `relative` i `absolute`:
    - overlay na aktywnym playerze dostaje tylko `absolute inset-0 h-full w-full`,
    - standalone preview w review / wyniku dostaje jawne `relative w-full`,
    - eliminuje to regresje typu:
      - niewidoczna strzalka mimo poprawnych danych runtime,
      - zerowy rozmiar kontenera overlayu,
      - dodatkowe "boczne" medium obok filmu po pauzie albo po zakonczeniu.

### Inline formatting: runtime i UX

- parser inline obsluguje bez konfliktu laczenie koloru i pogrubienia:
  - `[green]**tekst**[/green]` renderuje sie jako kolor + `<strong>`,
  - to samo dotyczy wariantu `[red]**tekst**[/red]`,
- kolejnosc przetwarzania jest stabilna:
  - najpierw kolory,
  - potem pogrubienia,
- przy toggle `Kolory = off` markery koloru sa usuwane, ale pogrubienia dalej dzialaja,
- przy toggle `Pogrubienia = off` markery pogrubienia sa usuwane, ale kolory dalej dzialaja,
- przy obu toggle `off` runtime zwraca czysty tekst bez markerow,
- obslugiwane aliasy markerow:
  - `[green]...[/green]` i `[zielony]...[/zielony]`,
  - `[red]...[/red]` i `[czerwony]...[/czerwony]`,
- inline edycja w trybie nauki ma teraz tez szybszy workflow redakcyjny:
  - modale `Edytuj pytanie` i `Edytuj wyjasnienie` maja toolbar `Pogrub / Zielony / Czerwony`,
  - redakcja zaznacza fragment myszka i klika przycisk zamiast recznie dopisywac markery,
  - klik na przycisku zachowuje zaznaczenie wewnatrz nowego wrappera, wiec da sie od razu laczyc style na tym samym fragmencie,
  - przy braku zaznaczenia marker jest wstawiany w miejscu kursora jako gotowa para do dopisania tekstu,
- preferencje usera dla `Pogrubienia` i `Kolory` sa trwale na przegladarce:
  - klucz `localStorage`: `study-session-preferences`,
  - domyslnie oba toggles sa `true`.
- preferencje usera maja teraz tez trzeci toggle:
  - `Adnotacje na mediach` (`showVisualAnnotations`),
  - ukrywa/pokazuje cala warstwe markerow (`label`, `circle`, `arrow`) na obrazach i stopklatkach filmu,
  - zapis jest w tym samym kluczu `study-session-preferences`,
  - domyslnie `true`.

### Copy w panelu `Ustawienia nauki`

- sekcja `Formatowanie tresci` ma teraz copy produktowe (bez technicznych markerow):
  - `Pogrubienia`: "Do szybkiego czytania pytan. Pokazuj wyroznione slowa i fragmenty.",
  - `Kolory`: "Do kojarzenia i zapamietywania. Pokazuj zielone i czerwone akcenty w tresci.",
- karta `Usprawnienia nauki` w klasycznym widoku pokazuje tez skrot stanu adnotacji:
  - `Adnotacje`: `Widoczne` albo `Ukryte`,
  - zrodlem jest ten sam toggle `Adnotacje na mediach` (`showVisualAnnotations`),
- w klasycznej nauce przycisk nawigacyjny `Pokaz wyjasnienie` dziala teraz tez przed odpowiedzia:
  - aktywuje sie, gdy pytanie ma realne wyjasnienie tekstowe albo asset wyjasnienia,
  - po kliknieciu zamienia prompt na karte wyjasnienia,
  - drugi klik wraca do pytania jako `Pokaz pytanie`,
  - gdy po blednej odpowiedzi wlacza sie automatyczne wyjasnienie, przycisk nie nadpisuje tego stanu,
- intencja UX:
  - user nie musi znac skladni markerow redakcyjnych,
  - user wybiera efekt, nie format zapisu.

### Admin `Baza pytan`: search odporny na markery

- globalna wyszukiwarka kolumny `Pytanie` ignoruje:
  - `**...**`,
  - `[green]...[/green]`,
  - `[red]...[/red]`,
  - interpunkcje z zapytania,
- efekt:
  - admin moze szukac po "czystej" frazie pytania, nawet gdy prompt w bazie ma markery inline,
  - frazy z `?` lub bez markerow nie zrywaja juz matchu.

### Admin `Baza pytan`: ergonomia pola wyszukiwania

- pole global search na liscie `admin/questions` zostalo:
  - wyraznie poszerzone,
  - wycentrowane,
  - lekko powiekszone typograficznie,
- scope zmian jest ograniczony do strony zasobu pytan.

### Pokrycie testami

- `resources/js/utils/explanationFormatting.test.ts`:
  - nesting `kolor + bold`,
  - niezalezne toggle `Pogrubienia` / `Kolory`,
  - shell-specific palety (`classic`, `zen`),
  - normalizacja plain text bez markerow,
- `resources/js/utils/inlineFormattingSelection.test.ts`:
  - owijanie zaznaczonego tekstu markerami,
  - wstawianie pustej pary markerow w miejscu kursora,
  - laczenie kolejnych markerow na tym samym fragmencie,
  - clamp / normalizacja zakresu zaznaczenia,
- `tests/Feature/Admin/QuestionListPageTest.php`:
  - search listy pytan dziala po czystej frazie mimo markerow inline w `prompt`.

Nie mamy jeszcze:

- workflow canvas dla stopklatek video i bardziej zaawansowanych typow overlayow,
- integracji adaptive learning z poziomami interwencji.

Ten dokument opisuje:

- wdrozony fundament,
- docelowy kierunek,
- oraz szczegolowy plan dalszego wdrozenia funkcji:

- blokow wizualnych w wyjasnieniu,
- ilustracji znaku obok tekstu,
- adnotacji na mediach pytania,
- docelowo anotowanych kadrow i materialow premium.

## Stan implementacji na `2026-04-07`

### Co jest juz dowiezione w kodzie

- `question_explanation_assets` jako osobna warstwa danych dla blokow referencyjnych,
- `kind = reference_sign` jako pierwszy aktywnie obslugiwany typ assetu,
- ograniczenie UI do `1 aktywnego bloku referencyjnego` na pytanie,
- panel admina do uploadu obrazu, tekstu, podpisu i aktywnosci bloku,
- zywy podglad bloku referencyjnego bezposrednio w formularzu admina,
- lista pytan w adminie ma licznik i filtr po obecnosci bloku referencyjnego,
- lista pytan w adminie ma licznik markerow i filtr po obecnosci adnotacji,
- komponent renderujacy blok referencyjny w sesji i wyniku,
- preferencja usera `visual_explanations_enabled`,
- runtime override w sesji nauki bez zmiany preferencji globalnej,
- respektowanie tego ustawienia w nauce, review i wyniku egzaminu,
- zachowanie czystego aktywnego egzaminu bez warstwy wizualnej,
- `question_explanation_annotations` jako osobna warstwa danych dla overlayow,
- typy `label`, `circle` i `arrow` jako wspierane warianty adnotacji,
- formularzowe zarzadzanie adnotacjami w panelu admina,
- podglad aktualnie zapisanego obrazu pytania i zapisanych adnotacji w panelu admina,
- klikalny helper pozycji w panelu admina:
  - klik ustawia `x/y`,
  - dla `circle` klik i przeciaganie ustawia rozmiar,
  - dla `arrow` uchwyt koncowy ustawia kierunek i dlugosc,
  - helper jest podpiety do tego samego stanu co repeater formularza,
- rozbudowany panel edycji helpera:
- zmiana typu `label / text / circle / arrow`,
  - zmiana tonu `info / warning / danger`,
  - szybki toggle aktywnosci,
  - przelaczanie `obraz pytania / stopklatka wideo`,
  - ustawianie `frame_time_seconds` dla wybranej adnotacji,
  - scrubber / timeline stopklatki wideo,
  - szybkie skoki po zapisanych klatkach,
  - szybkie przesuwanie czasu `-5s / -1s / +1s / +5s`,
  - szybkie przesuwanie `x/y`,
  - szybka zmiana rozmiaru `circle`,
  - duplikowanie i zmiana kolejnosci adnotacji,
  - przeciaganie markerow bezposrednio na canvasie,
  - uchwyt resize dla `circle`,
  - zsynchronizowany preview stopklatki wideo z liczba markerow na widocznej klatce,
  - uproszczona warstwa UX:
    - `Dodaj marker i ustaw go bezposrednio na kadrze`,
    - `Kliknij w kadr, zeby ustawic marker`,
    - `Ustawienia markera`,
    - `Lista markerow`,
    - `Zaawansowane pola adnotacji` jako fallback,
    - precyzyjne wartosci sa ukryte pod `Precyzyjne ustawienia`,
    - akcje destrukcyjne i duplikacja sa widoczne dopiero przy pracy z wybranym markerem,
  - helper jest teraz stylistycznie osadzony w adminowym theme asset, a nie tylko w partialu,
  - helper po otwarciu pytania hydratuje tez juz zapisane adnotacje, wiec admin nie zaczyna od pustego canvasu,
  - wybrany okrag ma bardziej runtime'owy preview:
    - finalny obrys jest widoczny od razu na kadrze,
    - obrys jest rysowany jako SVG dokladnie na obszarze samego medium, a nie na wrapperze sekcji,
    - numer markera siedzi na gornej krawedzi zaznaczenia,
    - uchwyt resize jest odsuniety do prawego dolnego rogu poza glowny obszar adnotacji,
  - repeater zdegradowany do roli fallbacku,
- wspolrzedne procentowe i render overlayu wzgledem faktycznego obszaru obrazu,
- komponent renderujacy adnotacje w nauce, review i wyniku egzaminu.
- podstawowy runtime dla pytan wideo:
  - `target_kind = video_frame`,
  - `frame_time_seconds`,
  - stopklatka renderowana w oparciu o pauzowany element `<video>`,
  - overlaye widoczne po odpowiedzi, w review i w wyniku egzaminu,
- aktywny egzamin dalej pozostaje czysty.
- poprawka adminowego zapisu bloku referencyjnego:
  - edycja tytulu / opisu / alt textu istniejacego assetu nie gubi juz `file_path`,
  - formularz poprawnie zachowuje istniejacy obraz, gdy admin zmienia tylko tekst.
- wspolna warstwa storage i odczytu dla grafiki karty odpowiedzi:
  - tabela `shared_question_explanation_assets`,
  - shared zapis po `external_id + source`,
  - czyszczenie lokalnych override'ow po wspolnym zapisie calej grupy,
  - ostrzezenie admina, gdy grupa ma rozjazd lokalnych grafik,
  - guardy plikowe chroniace lokalne i shared assety przed przypadkowym usunieciem tego samego pliku,
- runtime po odpowiedzi, w review i po egzaminie jest teraz jedna karta:
  - tekst bierze z pola `Wyjasnienie`,
  - grafike bierze z `Materialu referencyjnego` (pliku lub powiazanego `traffic_sign_id`),
  - gdy brakuje grafiki, pokazuje fallback `explanation-fallback.svg`,
  - dawny blok referencyjny zostaje tylko jako prostsza warstwa wizualna dla trybu `before_answer`.
- obsluga znakow drogowych z bazy (2026-04-29):
  - pole `traffic_sign_id` w `question_explanation_assets` i `shared_question_explanation_assets`,
  - reaktywny wybor znaku w panelu admina zastepujacy reczny upload pliku,
  - dynamiczne ladowanie `image_url`, `title` i `alt_text` ze slownika znakow w payload builderze,
  - przycisk "Edytuj grafikę" w oknie sesji pozwalajacy redakcji szybko otworzyc panel edycji pytania.

### Co pozostaje nastepnym krokiem

- pelny wizualny edytor canvas z uchwytami resize / drag handles i workflow pod stopklatki wideo,
- workflow wielu klatek dla jednego wideo z bardziej rozbudowana nawigacja po timeline,
- kolejne typy interwencji i bogatsza taxonomia overlayow,
- ewentualny bezpieczny backfill historycznych lokalnych grafik do shared assetow dla grup jednoznacznych,
- integracja tych interwencji z `Trenerem pamieci`.

## Cel produktu

Zbudowac najbardziej zaawansowany modul objasnien do pytan, w ktorym uzytkownik:

- nie tylko czyta, dlaczego odpowiedz jest poprawna,
- ale widzi, co dokladnie bylo wazne na obrazie lub filmie,
- rozumie znaczenie znaku lub tabliczki,
- szybciej zapamietuje pulapki pytania,
- uczy sie rozpoznawac kluczowy sygnal w realnej scenie.

Docelowo przewaga produktu nie ma wynikac tylko z bazy pytan, ale z jakosci warstwy:

- dydaktycznej,
- wizualnej,
- redakcyjnej,
- analitycznej.

## Problem, ktory rozwiazujemy

Samo `questions.explanation` nie wystarcza w wielu pytaniach.

Typowy problem:

- uzytkownik widzi kilka znakow lub elementow sceny,
- nie wie, ktory z nich jest kluczowy,
- tekst tlumaczy zasade, ale nie pokazuje, gdzie patrzec.

Przyklad:

- pytanie o zwezenie jezdni,
- na kadrze jest kilka znakow,
- uzytkownik musi wiedziec:
  - ktory znak niesie glowna informacje,
  - co doprecyzowuje tabliczka,
  - dlaczego z tego wynika utrudnienie ruchu.

Wniosek:

- potrzebujemy nie tylko tekstu,
- ale tez warstwy wizualnej, ktora wyjasnia pytanie.

## Zasada architektoniczna

Nie projektujemy tego jako:

- `jedno zdjecie do explanation`

Tylko jako:

- `system materialow objasniajacych`
- `system warstw wizualnych do pytania`

To pozwala nam dzisiaj wdrozyc prosty MVP, a w przyszlosci obsluzyc:

- zdjecia znaku,
- schematy,
- anotowane screeny,
- porownania `dobrze / zle`,
- stopklatki z filmu,
- docelowo timed annotations na video.

## Docelowe typy elementow objasnienia

### 1. Tekst glownego wyjasnienia

To, co mamy juz dzisiaj:

- glowne explanation,
- pogrubienia `**tekst**`,
- kolorowe akcenty `[green]tekst[/green]` i `[red]tekst[/red]`,
- krotkie hinty typu `Zwroc uwage`.

### 2. Blok referencyjny znaku lub ilustracji

Komponent typu:

- po lewej obrazek znaku / schematu / tabliczki,
- po prawej tekst wyjasniajacy,
- opcjonalny podpis.

Cel:

- wyjasnic sam znak albo symbol,
- pokazac czysta, czytelna referencje,
- odciazyc uzytkownika od analizowania samego kadru pytania.

Najlepsze zastosowania:

- znaki ostrzegawcze,
- znaki zakazu i nakazu,
- tabliczki pod znakami,
- podobne do siebie znaki,
- schemat pierwszenstwa.

### 3. Warstwa adnotacji na medium pytania

Overlay nakladany na:

- zdjecie pytania,
- docelowo stopklatke z filmu,
- w dalszym etapie na sam film.

Cel:

- pokazac, co na tym konkretnym medium bylo kluczowe.

Przyklady:

- obwodka wokol znaku,
- strzalka wskazujaca pas ruchu,
- marker z etykieta,
- zaznaczenie pojazdu lub punktu kolizyjnego.

## Zakres systemu

Docelowy najblizszy zakres systemu obejmuje dwa filary:

1. `Blok referencyjny znaku / ilustracji`
2. `Statyczne adnotacje na zdjeciu pytania`

Calosc od poczatku musi tez przewidywac:

3. `Mozliwosc wylaczenia wizualnych objasnien przez uzytkownika`

Stan wdrozenia na dzis:

- `Sprint 1 / MVP v1` jest zrobiony i obejmuje:
  - blok referencyjny,
  - ustawienie globalne,
  - lokalny toggle w sesji nauki.
- `Etap 2 / annotations MVP` jest zrobiony i obejmuje:
  - `question_explanation_annotations`,
- overlaye `label / text / circle / arrow` na zdjeciu pytania,
  - podstawowy runtime dla `video_frame` z `frame_time_seconds`,
  - helper admina dla stopklatki wideo i pola `frame_time_seconds`,
  - geometryczne sterowanie strzalka (`dlugosc / kat / grubosc / grot`).

W calym systemie nie wdrazamy jeszcze:

- timed annotations na video,
- wielu zlozonych blokow na pytanie,
- rozbudowanego edytora canvas dla stopklatek wideo,
- warstw pojawiajacych sie w czasie filmu,
- automatycznych rekomendacji AI.

## Sterowanie funkcja przez uzytkownika

To jest wymog produktowy.

Nie wszyscy uzytkownicy beda chcieli widziec:

- markery na mediach,
- bloki referencyjne,
- dodatkowe warstwy wizualne po odpowiedzi.

Dla czesci osob taki feature bedzie pomoca, a dla czesci moze byc rozpraszajacy.

### Zasada

Warstwa wizualna musi byc opcjonalna.

Uzytkownik musi miec mozliwosc jej wylaczenia bez psucia pozostalego flow nauki.

### Poziom 1 - preferencja globalna

Dodajemy preferencje uzytkownika typu:

- `Wizualne objasnienia`

Wartosci MVP:

- `before_answer`
- `after_incorrect`
- `off`

Ta preferencja steruje:

- blokiem referencyjnym znaku,
- adnotacjami na medium pytania,
- przyszlymi materialami wizualnymi tego systemu.

### Poziom 2 - szybkie nadpisanie w sesji

W trybach nauki uzytkownik powinien miec mozliwosc szybkiego przelaczenia tego trybu:

- `Pokaz przed odpowiedzia`
- `Pokaz po blednej odpowiedzi`
- `Wylacz calkiem`

To pozwala:

- porownac nauke z i bez warstwy wizualnej,
- przelaczyc sie miedzy podpowiedzia przed odpowiedzia a interwencja dopiero po bledzie,
- wylaczyc feature chwilowo bez zmiany ustawien konta,
- ograniczyc rozpraszacze w konkretnej sesji.

### Zasada dziedziczenia

Flow powinno byc takie:

- przy starcie sesji brana jest preferencja globalna,
- w trakcie sesji uzytkownik moze tymczasowo ja nadpisac lokalnym wybierakiem trybu,
- po zakonczeniu sesji globalna preferencja pozostaje bez zmian, chyba ze user zmieni ja jawnie w ustawieniach.

### Egzamin aktywny

W aktywnym egzaminie:

- warstwa wizualna zawsze pozostaje wylaczona,
- nie pokazujemy toggli,
- nie pokazujemy materialow pomocniczych.

To nie jest kwestia preferencji usera, tylko zasady trybu egzaminacyjnego.

### Wynik egzaminu

Po zakonczonym egzaminie mozemy pokazac te warstwy:

- zgodnie z preferencja usera,
- albo zawsze w review po fakcie, jesli produktowo uznamy to za lepsze.

Decyzja MVP:

- w wyniku egzaminu po fakcie respektujemy ustawienie `Wizualne objasnienia`.

### Decyzja MVP

MVP musi zawierac:

- globalne ustawienie usera `wizualne objasnienia`,
- runtime toggle w sesji nauki,
- brak tych pomocy w aktywnym egzaminie.

Nie wolno wdrozyc systemu adnotacji bez mozliwosci jego wylaczenia.

## Docelowa decyzja produktowa

### Blok referencyjny

Do pytania mozna przypisac opcjonalnie:

- jeden glowny material referencyjny,
- krotki tekst obok,
- opcjonalny podpis.

Render:

- w nauce po odpowiedzi,
- w review po sesji,
- w wyniku egzaminu po fakcie.

Nie renderujemy:

- w aktywnym egzaminie przed odpowiedzia.

### Adnotacje na medium

Do medium pytania mozna przypisac od 0 do kilku adnotacji.

MVP obsluguje tylko:

- zdjecia,
- statyczne pozycje procentowe,
- niewielka liczbe markerow.

Render:

- po odpowiedzi,
- w review,
- w wyniku egzaminu.

Nie renderujemy:

- w aktywnym egzaminie,
- przed udzieleniem odpowiedzi.

## Typy adnotacji w MVP

Aktualnie wspieramy cztery typy:

### `label`

- marker z kropka i dymkiem,
- krotka etykieta tekstowa przy konkretnym punkcie,
- np. `Ten znak jest kluczowy`.

### `text`

- duzy tekst w jednej linii,
- bez kropki i bez dymka,
- renderowany prostym stylem (duze litery, bez efektow ozdobnych),
- do komunikatow typu `LEWA STRONA`, `PRAWA STRONA`.

### `circle`

- obwodka / zaznaczenie obszaru,
- dobra do znakow, tabliczek, fragmentow sceny.

### `arrow`

- wskazanie kierunku i toru uwagi,
- regulacja dlugosci, kata, grubosci i grotu.

To dobrze pokrywa pytania z zakresu:

- znaki,
- tabliczki,
- pierwszenstwo,
- oznakowanie poziome,
- ustawienie pojazdow.

## Etap 2 i dalej

Po MVP dokladamy:

### Etap 2

- `label / text / circle / arrow` na obrazie pytania,
- podstawowy `video_frame` na wybranej stopklatce,
- `frame_time_seconds`.

### Etap 3

- wiecej niz jeden material referencyjny,
- kolejnosc materialow,
- bloki porownawcze `dobrze / zle`.

### Etap 4

- prosty edytor wizualny dla admina,
- klikanie i pozycjonowanie adnotacji na obrazie.

### Etap 5

- timed annotations na video,
- pojawianie sie markerow w konkretnych sekundach.

## Model danych - kierunek docelowy

### Zasada

Nie dodajemy pojedynczych kolumn typu:

- `explanation_image_path`
- `explanation_image_caption`

To byloby zbyt waskie i szybko zamkneloby droge do rozwoju.

### Preferowany model

Potrzebujemy osobnej warstwy danych dla `explanation assets`.

Docelowo pytanie powinno miec:

- glowne explanation tekstowe,
- powiazane materialy objasniajace,
- powiazane adnotacje do medium.

### Proponowane byty

#### `question_explanation_assets`

Jeden rekord to jeden material objasniajacy, np.:

- obraz znaku,
- schemat,
- anotowany screen,
- ilustracja porownawcza.

Minimalne pola:

- `id`
- `question_id`
- `traffic_sign_id` (opcjonalny)
- `kind`
- `title`
- `body`
- `file_path`
- `alt_text`
- `caption`
- `position`
- `is_active`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

Przykladowe `kind`:

- `reference_sign`
- `reference_diagram`
- `annotated_frame`
- `comparison`

#### `question_explanation_annotations`

Jeden rekord to jedna adnotacja na medium pytania.

Minimalne pola:

- `id`
- `question_id`
- `target_kind`
- `frame_time_seconds`
- `annotation_type`
- `x_percent`
- `y_percent`
- `width_percent`
- `height_percent`
- `label`
- `tone`
- `position`
- `is_active`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

Przykladowe `annotation_type`:

- `label`
- `text`
- `circle`
- `arrow`
- w kolejnych etapach:
  - `box`
  - `marker`

Przykladowe `target_kind`:

- `question_image`
- `video_frame`

## Dlaczego wspolrzedne procentowe

Adnotacje powinny byc zapisywane procentowo, nie w pikselach.

Powod:

- media sa responsywne,
- ten sam obraz renderuje sie w roznych rozmiarach,
- procenty latwiej zachowuja zgodnosc na desktopie i mobile.

Przyklad:

- `x_percent = 62.5`
- `y_percent = 38.0`
- `width_percent = 12.0`
- `height_percent = 15.0`

## Zasady skalowania i pozycjonowania overlayow

To jest krytyczna czesc projektu.

Musimy zalozyc, ze:

- obrazy maja rozne proporcje,
- pliki maja rozne rozdzielczosci,
- media zmieniaja rozmiar zalezne od viewportu,
- ten sam material moze byc pokazany w kilku miejscach UI.

### Zasada 1

Adnotacje liczymy wzgledem faktycznego obszaru wyrenderowanego obrazu, a nie wzgledem calego wrappera komponentu.

Powod:

- przy `object-contain` moga pojawic sie puste marginesy,
- przy nich marker nie moze byc liczony od krawedzi boxa, bo rozjedzie sie z obrazem.

### Zasada 2

W MVP dla obrazow z adnotacjami trzymamy jeden przewidywalny tryb renderu:

- preferowane `object-contain`
- bez przycinania kadru

Powod:

- `object-cover` wprowadza crop,
- crop komplikuje liczenie overlayow,
- na MVP nie chcemy wspierac nieprzewidywalnych przyciec.

### Zasada 3

Wspolrzedne sa zapisywane wzgledem oryginalnego medium zrodlowego, a frontend przelicza je na aktualny rect obrazu.

To oznacza, ze:

- admin ustawia marker na obrazie zrodlowym,
- runtime tylko skaluje jego polozenie,
- nie zapisujemy polozen zalezncych od jednego widoku.

### Zasada 4

Komponent renderujacy overlay musi znac:

- naturalna szerokosc medium,
- naturalna wysokosc medium,
- aktualna szerokosc wyrenderowanego obrazu,
- aktualna wysokosc wyrenderowanego obrazu,
- offset obrazu wewnatrz wrappera.

Bez tego nie da sie poprawnie obsluzyc:

- roznych proporcji,
- letterboxingu,
- zmiany rozmiaru okna.

### Zasada 5

Pozycje markerow musza byc przeliczane przy:

- pierwszym renderze,
- zmianie rozmiaru kontenera,
- zmianie viewportu,
- przelaczeniu orientacji na tabletach i telefonach.

Technicznie oznacza to, ze komponent powinien byc gotowy na:

- `ResizeObserver`
- przeliczenie rect po zaladowaniu obrazu.

### Zasada 6

Blok referencyjny znaku jest prostszy i nie potrzebuje tego samego poziomu precyzji.

Tam zakladamy:

- staly, kontrolowany box,
- `object-contain`,
- brak overlayow w MVP.

Czyli rozne wielkosci plikow sa akceptowalne, o ile render jest spójny.

### Zasada 7

Dla filmow nie nakladamy adnotacji bezposrednio na aktywnie odtwarzane video w MVP.

Zamiast tego:

- wybieramy stopklatke,
- zapisujemy `frame_time_seconds`,
- renderujemy adnotacje na wybranym kadrze jak na zwyklym obrazie.

To upraszcza:

- skalowanie,
- pozycjonowanie,
- zachowanie na roznych ekranach.

### Decyzja MVP

Na pierwszym etapie wspieramy tylko takie przypadki, w ktorych:

- obraz jest pokazany w przewidywalnym komponencie,
- overlay liczony jest wzgledem samego obrazu,
- nie ma cropa typu `cover`,
- adnotacje sa statyczne.

To pozwoli uniknac najczestszych rozjazdow na:

- laptopach,
- tabletach,
- mobile,
- roznych szerokosciach sekcji review i wyniku.

## Gdzie renderujemy elementy

### Tryby nauki

Pokazujemy:

- blok referencyjny znaku,
- adnotacje na mediach,
- tekst explanation.

Miejsca:

- `Zwroc uwage`,
- pelne explanation po odpowiedzi,
- review po sesji.

### Wynik egzaminu

Pokazujemy:

- pelne explanation,
- blok referencyjny,
- adnotacje po fakcie.

### Aktywny egzamin

Nie pokazujemy:

- blokow referencyjnych,
- adnotacji,
- dodatkowych pomocy wizualnych.

To musi zostac czystym, formalnym flow egzaminacyjnym.

## UX admina - MVP

Na start edycja powinna byc tylko w panelu admina.

Powody:

- upload plikow,
- podglad materialu,
- walidacja,
- prostsze wdrozenie i mniejsze ryzyko niz inline upload z nauki.

### Sekcja 1: material referencyjny

Admin powinien miec mozliwosc:

- dodania obrazka,
- wpisania krotkiego tytulu,
- wpisania krotkiego opisu obok,
- wpisania podpisu opcjonalnego,
- usuniecia lub podmiany pliku.

### Sekcja 2: adnotacje do medium

MVP admina moze byc formularzowy:

- typ adnotacji,
- `x/y/w/h`,
- label,
- pozycja,
- aktywnosc.

To nie jest idealne UX, ale pozwoli szybko ruszyc bez budowy edytora canvas.

Docelowo:

- prosty wizualny edytor na obrazie.

## Walidacja i ograniczenia MVP

### Materialy referencyjne

- formaty: `jpg`, `png`, `webp`
- limit pliku: do ustalenia, rekomendacja `5 MB`
- alt tekst wymagany albo silnie rekomendowany
- obraz powinien byc automatycznie przygotowany do webowego wyswietlania

### Adnotacje

- max `3-5` adnotacji na pytanie w MVP
- dlugosc labeli ograniczona
- tylko statyczne pozycje

## Spec MVP v1

Ta sekcja zamyka decyzje potrzebne do bezpiecznego startu implementacji.

Jesli ponizsze zalozenia sie nie zmienia, MVP v1 mozna wdrazac bez dalszego zgadywania zakresu.

### Zakres MVP v1

W pierwszym wdrozeniu robimy tylko:

1. `Blok referencyjny znaku / ilustracji`
2. `Globalne ustawienie wizualnych objasnien`
3. `Lokalny toggle wizualnych objasnien w sesji nauki`

Nie robimy jeszcze w MVP v1:

- adnotacji na medium pytania,
- markerow `label / text / circle`,
- stopklatek z filmu,
- timed overlays,
- galerii kilku blokow na pytanie,
- inline uploadu z poziomu nauki,
- wizualnego edytora admina.

Czyli:

- dokument docelowo obejmuje oba filary systemu,
- ale pierwszy wdrozony sprint obejmuje tylko blok referencyjny i sterowanie jego widocznoscia.

### Twarda decyzja o modelu danych MVP v1

Na MVP v1 tworzymy tabele:

- `question_explanation_assets`

Nie tworzymy jeszcze:

- `question_explanation_annotations`

#### `question_explanation_assets` - zakres MVP v1

Tabela wspiera docelowo wiecej typow, ale w MVP v1 aktywnie uzywamy tylko:

- `kind = reference_sign`

Minimalne pola wymagane w MVP v1:

- `id`
- `question_id`
- `kind`
- `title`
- `body`
- `file_path`
- `alt_text`
- `caption`
- `position`
- `is_active`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

Decyzje:

- w MVP v1 jedno pytanie moze miec wiele rekordow w tabeli,
- ale UI i render wspieraja tylko `1 aktywny blok referencyjny`,
- aktywnym blokiem jest rekord:
  - `is_active = true`
  - o najnizszym `position`
  - i `kind = reference_sign`

To daje nam:

- prosty UX teraz,
- droge do wielu blokow w przyszlosci,
- brak potrzeby przebudowy tabeli przy etapie 2+.

### Twarda decyzja o ustawieniach usera

W MVP v1 dodajemy preferencje usera:

- `visual_explanations_enabled`

Decyzja implementacyjna:

- jesli mamy juz miejsce na preferencje nauki usera, rozszerzamy istniejaca strukture preferencji,
- nie tworzymy osobnej tabeli tylko pod ten jeden toggle, jesli mozna tego uniknac.

Domyslna wartosc:

- `true`

Powod:

- feature ma byc domyslnie wartoscia dodana,
- ale uzytkownik musi moc go latwo wylaczyc.

### UX admina - spec MVP v1

W panelu pytania dodajemy nowa sekcje:

- `Material referencyjny`

Sekcja obsluguje tylko jeden blok referencyjny w UI.

#### Pola formularza admina

1. `Obraz`
- wymagany, jesli blok ma byc aktywny

2. `Tytul`
- opcjonalny
- krotki
- np. `Znak A-12a`

3. `Opis`
- wymagany, jesli istnieje obraz
- glowny tekst po prawej stronie obrazka

4. `Podpis`
- opcjonalny
- krotki tekst pod blokiem lub pod obrazem

5. `Alt text`
- wymagany lub silnie rekomendowany

6. `Aktywny`
- checkbox / toggle

#### Zasady walidacji admina

- nie mozna zapisac aktywnego bloku bez obrazu,
- nie mozna zapisac aktywnego bloku bez opisu,
- `body` ma limit sensownej dlugosci, zeby blok nie zamienial sie w artykul,
- `caption` ma byc krotki,
- plik musi byc obrazem webowym.

#### Zachowanie admina

Admin moze:

- dodac blok,
- podmienic obraz,
- zmienic tekst,
- wylaczyc blok,
- usunac blok.

Na MVP v1 nie moze:

- dodac drugiego widocznego bloku przez UI,
- ustawiac kilku aktywnych blokow,
- edytowac pozycjonowania markerow.

### UX usera - spec MVP v1

#### Gdzie pokazujemy blok referencyjny

Pokazujemy go:

- po odpowiedzi w trybach nauki,
- w review po sesji,
- w wyniku egzaminu po fakcie.

Nie pokazujemy go:

- w aktywnym egzaminie,
- przed udzieleniem odpowiedzi w nauce,
- gdy user ma wylaczone wizualne objasnienia.

#### Uklad bloku

Desktop:

- lewa kolumna: obraz,
- prawa kolumna: tytul + opis,
- podpis opcjonalnie pod blokiem albo pod obrazem.

Mobile:

- obraz nad tekstem,
- brak poziomego upychania.

#### Hierarchia po odpowiedzi

MVP v1 powinien zachowac nastepujaca kolejnosc:

1. glowne wyjasnienie tekstowe,
2. blok referencyjny znaku / ilustracji,
3. reszta review / meta informacji.

To ma ograniczyc przebodzcowanie i nie wypychac explanation na dalszy plan.

### Tryb pokazywania markerow i objasnien wizualnych

#### Globalne ustawienie

User ma stale ustawienie:

- `Wizualne objasnienia`

W profilu jest to juz nie prosty checkbox, tylko tryb z 3 opcjami:

- `before_answer`
- `after_incorrect`
- `off`

Znaczenie:

- `before_answer` pokazuje markery na medium jeszcze przed zaznaczeniem odpowiedzi,
- `after_incorrect` pokazuje je dopiero po blednej odpowiedzi,
- `off` wylacza markery i blok referencyjny.

#### Lokalne ustawienie w sesji

W aktywnej sesji nauki jest widoczny lokalny wybierak tych samych 3 trybow:

- `Pokaz przed odpowiedzia`
- `Pokaz po blednej odpowiedzi`
- `Wylacz calkiem`

Zasady:

- widoczny tylko w trybach nauki,
- niewidoczny w aktywnym egzaminie,
- nie zmienia globalnej preferencji z profilu,
- dziala tylko na runtime biezacej sesji.

#### Zachowanie review

Review po sesji respektuje lokalny tryb ustawiony w danej sesji.

Decyzja obecnego etapu:

- `off` ukrywa markery i blok referencyjny takze w review tej sesji,
- `before_answer` i `after_incorrect` nadal pokazuja warstwe wizualna w review i wyniku po fakcie,
- nie dodajemy jeszcze osobnego toggla tylko dla review.

### Walidacje i edge case'y MVP v1

#### Edge case 1

Pytanie nie ma bloku referencyjnego:

- UI nie pokazuje pustego boxa,
- sesja dziala normalnie.

#### Edge case 2

User ma tryb `off`:

- blok referencyjny nie renderuje sie nigdzie w nauce,
- markery nie renderuja sie na medium,
- review i wynik egzaminu tez ich nie pokazuja.

#### Edge case 3

Admin zapisze nieaktywny blok:

- rekord istnieje,
- ale user go nie widzi.

#### Edge case 4

Istnieje wiecej niz jeden aktywny rekord przez blad danych:

- frontend pokazuje tylko pierwszy po `position`,
- backend/admin powinien minimalizowac szanse takiego stanu.

#### Edge case 5

Plik obrazu zostal usuniety albo jest uszkodzony:

- blok nie renderuje pustego martwego kontenera,
- najlepiej fallback do samego tekstu albo calkowite ukrycie sekcji.

### Testy MVP v1

#### Testy backend

- zapis i odczyt rekordu `question_explanation_assets`
- relacja pytanie -> aktywny blok referencyjny
- preferencja `visual_explanations_enabled`
- brak renderu tych danych dla aktywnego egzaminu

#### Testy frontend / feature

- blok pojawia sie po odpowiedzi w nauce
- blok nie pojawia sie, gdy user ma feature wylaczony
- blok renderuje sie w review
- blok renderuje sie w wyniku egzaminu po fakcie
- aktywny egzamin go nie pokazuje

#### Testy responsywnosci

- desktop
- tablet
- mobile

### Definition of Done - Sprint 1

Sprint 1 uznajemy za zakonczony, gdy:

- istnieje tabela `question_explanation_assets`,
- admin moze dodac i zapisac jeden aktywny blok referencyjny,
- user widzi blok po odpowiedzi w trybach nauki,
- user widzi blok w review,
- user widzi blok w wyniku egzaminu po fakcie,
- aktywny egzamin pozostaje bez zmian,
- user moze globalnie i lokalnie wylaczyc wizualne objasnienia,
- brak pustych kontenerow przy braku danych,
- layout dziala poprawnie na desktopie i mobile.

## Szczegolowy plan wdrozenia

### Faza 0 - dokumentacja i decyzje

Cel:

- potwierdzic kierunek produktu,
- ustalic model danych i naming,
- rozdzielic MVP od etapu premium.

Deliverable:

- ten dokument,
- decyzja: `system materialow objasniajacych`, a nie pojedyncze pole na obrazek.

### Faza 1 - model danych dla materialu referencyjnego

Cel:

- dodac pierwszy typ materialu objasniajacego:
  - obraz po lewej + tekst po prawej.

Zakres:

- migracja dla `question_explanation_assets`
- model / relacja do `Question`
- upload i przechowywanie pliku
- render jednego aktywnego bloku referencyjnego

Pliki do ruszenia:

- migracje
- `app/Models/Question.php`
- nowy model np. `QuestionExplanationAsset`
- Filament resource / schema pytania
- player nauki
- review i wynik egzaminu

Akceptacja:

- admin moze przypisac 1 obrazek referencyjny do pytania,
- uzytkownik widzi go po odpowiedzi i w review,
- aktywny egzamin pozostaje czysty.

### Faza 2 - model danych dla adnotacji

Cel:

- umozliwic wskazanie kluczowego miejsca na obrazie pytania.

Zakres:

- migracja dla `question_explanation_annotations`
- relacja do pytania
- render overlay na obrazie pytania
- typy `label` i `circle`

Pliki do ruszenia:

- migracje
- model pytania
- nowy model adnotacji
- komponent renderu medium z overlayem
- review i wynik
- panel admina

Akceptacja:

- pytanie moze miec statyczne adnotacje na zdjeciu,
- markery sa widoczne po odpowiedzi,
- pozycjonowanie dziala responsywnie.

### Faza 3 - UX admina dla adnotacji

Cel:

- odejsc od recznego wpisywania `x/y/w/h`.

Zakres:

- prosty edytor wizualny:
  - klik,
  - przeciaganie,
  - zapis.

Akceptacja:

- redakcja nie musi pracowac na surowych procentach.

Stan na dzis:

- mamy juz helper `click / drag` podpiety do stanu formularza,
- mamy juz tez panel wybranej adnotacji z szybkimi akcjami i reorderem,
- mamy juz bezposrednie przeciaganie markerow i uchwyt resize dla `circle` na obrazie,
- mamy juz podstawowy preview stopklatki wideo dla wybranej sekundy,
- nadal nie mamy jeszcze workflow canvas z timeline scrubberem dla stopklatek wideo i bardziej zaawansowanych overlayow.

### Faza 4 - stopklatki z filmu

Cel:

- objac systemem takze pytania filmowe.

Zakres:

- `frame_time_seconds`
- podglad wybranego kadru
- adnotacje na stopklatce

Akceptacja:

- po odpowiedzi przy filmie mozemy pokazac wybrany kluczowy kadr z markerami.

Stan na dzis:

- backend i payloady wspieraja juz `target_kind = video_frame`,
- frontend renderuje pauzowany kadr z filmu z overlayami w nauce, review i wyniku egzaminu,
- aktywny egzamin dalej nie pokazuje tych pomocy,
- panel admina ma juz podstawowy preview stopklatki, ale nie ma jeszcze timeline scrubbera ani pelnego workflow canvas dla wielu kadrów.

### Faza 5 - wersja premium

Cel:

- zbudowac przewage edukacyjna.

Zakres:

- wiele materialow do pytania,
- porownania `dobrze / zle`,
- timed video annotations,
- wersjonowanie tresci,
- analityka skutecznosci explanation.

## Akceptacja wdrozenia

### Akceptacja Sprintu 1 - stan obecny

Sprint 1 uznajemy za domkniety, gdy:

- admin moze dodac do pytania 1 blok referencyjny z obrazem i tekstem,
- uzytkownik moze wylaczyc wizualne objasnienia globalnie i lokalnie w sesji,
- w trybach nauki po odpowiedzi user widzi:
  - tekst explanation,
  - blok referencyjny,
- review i wyniki poprawnie to renderuja,
- aktywny egzamin nie pokazuje tych pomocy,
- layout dobrze dziala na desktopie i mobile.

Ten warunek jest juz spelniony w kodzie.

### Akceptacja Etapu 2 - adnotacje

Etap 2 uznajemy za domkniety, gdy:

- admin moze dodac podstawowe adnotacje do zdjecia pytania,
- w trybach nauki po odpowiedzi user widzi:
  - tekst explanation,
  - blok referencyjny,
  - overlay na medium,
- review i wyniki poprawnie to renderuja,
- overlay zachowuje sie poprawnie responsywnie.

Ten warunek jest juz spelniony w kodzie.

## Ryzyka

### 1. Przepalenie scope

Najwieksze ryzyko to probowac od razu zrobic:

- upload,
- galerie,
- timed video,
- edytor canvas,
- inline upload z nauki.

To trzeba rozdzielic etapami.

### 2. Za slaby model danych

Jesli zrobimy to jako pojedyncze pole na obrazek, szybko zabraknie miejsca na:

- schematy,
- anotowane kadry,
- porownania.

### 3. Przebodzcowanie UI

Po odpowiedzi nie mozemy zalac usera:

- tekstem,
- obrazkiem,
- overlayem,
- kilkoma badge'ami,
- dodatkowymi CTA.

Trzeba zachowac hierarchie:

1. najwazniejszy wniosek,
2. medium z markerami,
3. dodatkowy blok znaku,
4. pelne explanation.

## Decyzja koncowa

Wchodzimy w ten feature jako w:

- `system wizualnych objasnien do pytan`

Nie jako w:

- `dodanie obrazka pod explanation`.

Wdrozone juz teraz:

- blok referencyjny znaku / ilustracji,
- sterowanie widocznoscia warstwy wizualnej.

Najblizszy kolejny etap:

- adnotacje na zdjeciu pytania.

W wersji docelowej rozwijamy to do:

- anotowanych kadrow z filmu,
- wielu warstw wyjasnienia,
- najbardziej zaawansowanego systemu obiasnien pytan.

## Zabezpieczenia regresji (2026-04-16)

Dodalismy testy, ktore maja lapac najczestsze rozjazdy markerow i zrodla medium:

- `resources/js/utils/annotationContentRect.test.ts`
  - sprawdza geometrie overlay dla `object-position` (center/top/left/right, `%`, `px`, clamp, fallback).
- `resources/js/utils/questionMediaSources.test.ts`
  - pilnuje kolejnosci fallbackow `full/thumb/poster` dla nauki i wynikow.
- `tests/Unit/Support/QuestionMediaPayloadBuilderTest.php`
  - pilnuje backendowego payloadu mediów (`forQuestion` vs `forCatalog`),
  - sprawdza grupowanie po `asset_group`,
  - sprawdza fallbacki (np. brak `full`) i payload video (`poster_url`, `full_url`).

To razem daje szybki sygnal, czy problem jest:

- w geometrii renderu (frontend),
- w wyborze URL medium (frontend),
- czy w budowie payloadu mediów (backend).
