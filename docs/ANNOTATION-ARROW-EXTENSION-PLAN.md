# Rozszerzenie adnotacji medium o strzalki

## 1. Cel dokumentu

Ten dokument opisuje, jak bezpiecznie rozszerzyc obecny system adnotacji medium o nowy typ `arrow`, tak aby redakcja mogla ustawic:

- dlugosc strzalki,
- wielkosc strzalki (grubosc i grot),
- kierunek,
- kolor.

Zakres obejmuje caly flow:

- admin (`/admin/questions/{id}/edit`),
- zapis i walidacje backend,
- payload runtime,
- render w nauce, review i wyniku egzaminu,
- testy i rollout.

### Status dokumentu

- status: `wdrozone (MVP)`
- ostatnia aktualizacja: `2026-04-16`
- zakres: `nauka/review/wynik`, bez aktywnego egzaminu

To jest dokument wykonawczy. Moze byc realizowany commitami etapowymi bez dodatkowych decyzji architektonicznych.

## 1.1. Status implementacji (2026-04-16)

Wdrozenie `arrow` jest wykonane end-to-end:

- backend:
  - migracja `2026_04_16_180000_add_arrow_fields_to_question_explanation_annotations_table`,
  - model/factory/manager/payload wspiera pola `arrow_*`,
  - walidacja admina obejmuje zakresy:
    - `arrow_length_percent: 1..100`,
    - `arrow_angle_degrees: 0..359`,
    - `arrow_stroke_percent: 0.5..8`,
    - `arrow_head_percent: 2..30`.
- admin:
  - helper ma `Dodaj strzalke`,
  - typy markera: `Etykieta / Okrag / Strzalka`,
  - strzalka ma uchwyt koncowy `↗` do ustawiania kierunku i dlugosci,
  - panel ma szybkie presety kata i precyzyjne pola `arrow_*`,
  - tryb zaawansowany (repeater) ma pola `arrow_*`.
- runtime:
  - obraz i stopklatka filmu renderuja strzalke jako SVG (linia + grot),
  - nieznane `annotation_type` sa pomijane (bez fallbacku do label).
- testy:
  - `tests/Feature/Admin/QuestionVisualExplanationEditPageTest.php` (zapis i walidacja),
  - `tests/Unit/Support/QuestionExplanationAnnotationPayloadBuilderTest.php`,
  - `resources/js/utils/explanationAnnotations.test.ts`,
  - `npm run build` przechodzi (`vue-tsc + vite build`).

### Hotfix po wdrozeniu (2026-04-16)

Po pierwszym rolloutcie naprawiono dwa bledy UI helpera:

- strzalka na canvasie mogla byc niewidoczna dla kolejnych markerow, bo warstwa SVG brala udzial w normalnym flow; poprawka: `position:absolute; inset:0` dla warstwy SVG,
- parser Alpine mogl rzucac `Invalid or unexpected token` przez placeholder z nieprawidlowym escapowaniem; poprawka: uproszczony tekst placeholdera bez konfliktu cudzyslowow.

Po hotfixie reprodukcja w adminie (`/admin/questions/51118/edit`) pokazuje poprawny render strzalki na kadrze i brak bledow JS.

## 2. Audyt stanu obecnego (2026-04-16)

### 2.1. Dane i zapis

Aktualne typy adnotacji:

- `label`
- `circle`

Aktualne miejsca odpowiedzialne za logike:

- formularz admin: `app/Filament/Resources/Questions/Schemas/QuestionForm.php`
- zapis i walidacja: `app/Filament/Resources/Questions/Pages/Concerns/InteractsWithQuestionExplanationAsset.php`
- normalizacja i sync: `app/Support/QuestionExplanationAnnotationManager.php`
- payload runtime: `app/Support/QuestionExplanationAnnotationPayloadBuilder.php`
- model: `app/Models/QuestionExplanationAnnotation.php`
- tabela: `database/migrations/2026_04_06_102000_create_question_explanation_annotations_table.php`

Aktualne pola tabeli:

- `target_kind`, `frame_time_seconds`, `annotation_type`
- `x_percent`, `y_percent`, `width_percent`, `height_percent`
- `label`, `tone`, `position`, `is_active`

### 2.2. Edytor admin

Widok helpera:

- `resources/views/filament/resources/questions/partials/annotation-editor.blade.php`

Aktualne mozliwosci:

- dodanie `label` i `circle`,
- klik na canvasie (`x/y`),
- drag pozycji,
- resize `circle`,
- `tone`, `target_kind`, `frame_time_seconds`,
- precyzyjne pola fallback w repeaterze.

### 2.3. Runtime frontend

Render overlayow:

- `resources/js/Components/QuestionImageWithAnnotations.vue`
- `resources/js/Components/QuestionVideoFrameWithAnnotations.vue`

Miejsca uzycia:

- `resources/js/Pages/StudySessions/Show.vue`
- `resources/js/Pages/StudySessions/ExamResult.vue`

Wniosek:

- runtime zna tylko `circle` oraz fallback "inne -> zachowanie jak label".
- przed dodaniem `arrow` trzeba jawnie obsluzyc nowy typ, bo inaczej trafi do fallbacku label.

## 3. Luki do zamkniecia

1. Brak typu `arrow` w walidacji backend.
2. Brak pol danych pozwalajacych stabilnie opisac geometrie strzalki.
3. Brak narzedzi edycji strzalki w helperze admin.
4. Brak renderowania strzalki na frontendzie.
5. Brak testow dla nowego typu.

## 4. Proponowany model danych (v1 dla strzalek)

### 4.1. Typ adnotacji

- nowy `annotation_type = arrow`

### 4.2. Geometria i styl

Rekomendowany model:

- `x_percent`, `y_percent` = punkt startowy (zostaje jak jest),
- `arrow_length_percent` (decimal 6,2) = dlugosc wektora,
- `arrow_angle_degrees` (unsigned smallint) = kierunek `0..359`,
- `arrow_stroke_percent` (decimal 6,2) = grubosc linii,
- `arrow_head_percent` (decimal 6,2) = wielkosc grotu.

Powod wyboru:

- bezposrednio pokrywa wymagania usera (dlugosc, wielkosc, kierunek),
- jest niezalezny od aktualnego viewportu i responsywny,
- nie miesza semantyki `width_percent/height_percent` od `circle`.

### 4.3. Kolor

W v1 strzalka korzysta z istniejacego `tone`:

- `info`, `warning`, `danger` (spojnie z obecnym systemem).

Rozszerzenie do dodatkowych kolorow (`success`, `neutral`, custom hex) mozna dodac jako v2 bez zmiany geometrii.

## 5. Zmiany implementacyjne (plan techniczny)

### 5.1. Backend i schema

1. Migracja:
- dodac pola `arrow_*` do `question_explanation_annotations`.

2. Model:
- stale:
  - `ANNOTATION_TYPE_ARROW`
- `fillable` + `casts` dla nowych pol.

3. Walidacja:
- `annotation_type` dopuscza `arrow`.
- reguly dla `arrow`:
  - `arrow_length_percent` wymagane, zakres `1..100`
  - `arrow_angle_degrees` wymagane, zakres `0..359`
  - `arrow_stroke_percent` wymagane, zakres np. `0.5..8`
  - `arrow_head_percent` wymagane, zakres np. `2..30`
- dla `arrow` pola `width_percent/height_percent` nie sa wymagane.

4. Manager:
- normalizacja `arrow_*` z domyslnymi wartosciami.
- bezpieczny fallback dla starszych rekordow (jesli null -> domyslne runtime).

5. PayloadBuilder:
- dolaczyc `arrow_*` do payloadu runtime.

### 5.2. Admin helper (annotation-editor)

1. Akcje tworzenia:
- dodac `Dodaj strzalke`.

2. Typ markera:
- segment `Etykieta | Okrag | Strzalka`.

3. Interakcje canvas:
- strzalka ma:
  - uchwyt startowy (przesuniecie),
  - uchwyt koncowy (ustawienie kierunku i dlugosci).

4. Panel ustawien:
- suwaki / inputy:
  - dlugosc,
  - kat,
  - grubosc,
  - grot.
- szybkie presety kierunku: `0 / 45 / 90 / 135 / 180 / 225 / 270 / 315`.

5. Tryb zaawansowany (repeater):
- dodac pola `arrow_*`,
- utrzymac jako fallback reczny.

### 5.3. Runtime render

1. `QuestionImageWithAnnotations.vue` i `QuestionVideoFrameWithAnnotations.vue`:
- dodac jawny branch `annotation_type === 'arrow'`.
- render przez `svg` line + polygon (grot), pozycjonowany na tym samym obszarze medium co pozostale overlaye.

2. Fallback safety:
- nieznane `annotation_type` nie powinno udawac `label`.
- nieznane typy powinny byc ignorowane w renderze (bez crasha).

3. Kolory:
- mapowanie strzalki do tej samej palety `toneClasses`.

## 6. Ryzyka i zabezpieczenia

### 6.1. Ryzyko kompatybilnosci cache frontendu

Jesli backend zacznie zwracac `arrow` szybciej niz frontend zostanie odswiezony, stary frontend zinterpretuje typ zle.

Zabezpieczenie:

- wdrozenie backend+frontend w jednym release,
- dodatkowo runtime fallback "unknown -> ignore" zamiast "unknown -> label".

### 6.2. Ryzyko regression przy `circle`

Nowe interakcje drag/resize moga wejsc w kolizje z obecnym flow `circle`.

Zabezpieczenie:

- osobne uchwyty i osobna logika pointer action (`move-circle`, `resize-circle`, `move-arrow`, `rotate-arrow`),
- testy manualne i feature testy przed merge.

### 6.3. Ryzyko danych historycznych

Starsze rekordy nie maja `arrow_*`.

Zabezpieczenie:

- pola nullable + bezpieczne defaulty runtime.

## 7. Plan wdrozenia (fazy)

### Faza A - kontrakt danych

1. migracja `arrow_*`
2. model + manager + payload builder
3. walidacja backend
4. testy backend

### Faza B - admin helper

1. UI `Dodaj strzalke`
2. ustawienia i uchwyty
3. repeater advanced dla `arrow_*`
4. testy feature admin

### Faza C - runtime

1. render strzalki image/video frame
2. unknown-type safe fallback
3. testy sesji (`Show`, `ExamResult`, payload)

### Faza D - QA i release

1. scenariusze manualne na mobile + desktop
2. kontrola czytelności na jasnym i ciemnym tle mediow
3. release i monitoring logow walidacji

## 8. Minimalny pakiet testow

### Backend

- zapis `arrow` przechodzi walidacje.
- brak `arrow_length_percent` blokuje zapis.
- `arrow_angle_degrees > 359` blokuje zapis.
- payload sesji zawiera `arrow_*`.

### Frontend

- strzalka renderuje sie na obrazie.
- strzalka renderuje sie na stopklatce video.
- `unknown annotation_type` jest pomijany.

### E2E / feature

- admin dodaje strzalke, zapisuje pytanie, user widzi strzalke po odpowiedzi.

## 9. Decyzje produktowe na teraz

1. V1: kolory strzalek tylko przez `tone` (bez custom hex).
2. V1: jedna strzalka = jeden wektor z jednym grotem.
3. V1: limit 5 adnotacji na pytanie zostaje bez zmian.

To podejscie daje szybkie, bezpieczne wdrozenie bez rozbijania obecnego modelu i bez regresji dla `label/circle`.

## 10. Zakres renderu i zachowanie produktu

Strzalka (`annotation_type = arrow`) po wdrozeniu ma byc widoczna:

- w `http://localhost:8000/nauka/teraz` po odpowiedzi,
- w review po sesji,
- w wyniku egzaminu po fakcie.

Strzalka nie moze byc widoczna:

- w aktywnym egzaminie.

To zachowuje spojna polityke z obecnymi adnotacjami `label/circle`.

## 11. Checklista implementacji (kolejnosc commitow)

1. Kontrakt danych:
- migracja `arrow_*`,
- model i walidacja backend,
- payload builder.

2. Admin helper:
- `Dodaj strzalke`,
- segment typu `Etykieta | Okrag | Strzalka`,
- uchwyt startowy i koncowy,
- ustawienia dlugosci, kata, grubosci i grotu.

3. Runtime:
- render `arrow` na obrazie i stopklatce video,
- fallback `unknown type -> ignore`.

4. Testy:
- backend walidacja i payload,
- frontend render image/video,
- feature e2e admin -> nauka.

5. QA release:
- desktop + mobile,
- kontrola czytelnosci na jasnych i ciemnych kadrach.

## 12. Definition of Done dla `arrow` (MVP)

MVP strzalek uznajemy za domkniety, gdy:

- admin doda i zapisze `arrow` bez recznej edycji JSON,
- po zapisie strzalka wraca do edytora bez przesuniecia,
- strzalka jest widoczna i poprawnie pozycjonowana w nauce/review/wyniku,
- strzalka nie jest widoczna w aktywnym egzaminie,
- nie ma regresji dla `label` i `circle`,
- testy backend + frontend + feature przechodza.
