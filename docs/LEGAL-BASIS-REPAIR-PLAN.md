# Legal Basis Repair Plan

Status: plan naprawczy przed commitem/deployem inline edycji `Uzasadnienia prawnego`  
Data: 2026-06-16  
Zakres: publiczne pytania, `question_legal_references`, katalog przepisow, tematy `/przepisy`, schema

## 0. Aktualny status developmentu

Stan na 2026-06-16:

- inline edycja `Uzasadnienia prawnego` ma lokalnie wyszukiwarke przepisu i opcjonalny temat, ale nadal nie jest gotowa do merge/deploy,
- lokalny katalog ma 1270 zweryfikowanych jednostek dla `Prawo o ruchu drogowym`,
- wybor tematu nie jest juz warunkiem zapisu podstawy prawnej,
- link do artykulu `/przepisy` musi pozostac opcjonalny do czasu publikacji opracowania,
- schema publicznego pytania aktualizuje teraz `acceptedAnswer` dla sekcji `Wyjasnienie`, ale nie ma jeszcze docelowej reprezentacji osobnej podstawy prawnej,
- raport pokrycia `legal-basis:coverage` mierzy stan bez zmiany danych i jest checkpointem po kazdym imporcie albo batchu recznym.

Checklist statusu:

| Obszar | Status | Co to znaczy |
| --- | --- | --- |
| Publiczne `Wyjasnienie` | gotowe jako manual-first MVP | `Edytuj`/`Zapisz` aktualizuje HTML i `acceptedAnswer`. |
| Inline `Uzasadnienie prawne` | zablokowane przed deployem | Lokalnie jest edytor z wyszukiwarka przepisu i opcjonalnym tematem; brakuje decyzji deploy oraz pracy redakcyjnej przy pytaniach. |
| Pelna baza przepisow | pierwszy akt zweryfikowany lokalnie | `Prawo o ruchu drogowym` ma 1270/1270 jednostek `verified`. |
| Hierarchia `legal_units` | gotowe lokalnie | Model ma `parent_legal_unit_id`, `canonical_path` i `effective_from`. |
| Import manifestu jednostek | gotowe lokalnie | Komenda `legal-basis:import-units` ma preview i zapis przez `--write`. |
| Generator manifestu z ELI HTML | gotowe lokalnie | Komenda `legal-basis:build-units-manifest` buduje manifest z oficjalnego `text.html`. |
| Searchable picker przepisu | gotowe lokalnie | Endpoint `/api/v1/admin/legal-units/search` szuka w katalogu; zapis pozwala wybrac tylko `verified`. |
| Temat prawny | gotowe lokalnie | `legal_topic_id` moze byc puste; UI pokazuje `Bez tematu`. |
| Audyt jednostek | gotowe lokalnie | `legal-basis:audit-units --write` promowal 1257 jednostek PoRD do `verified`. |
| Artykul `/przepisy` | opcjonalny | Link dodajemy dopiero po publikacji opracowania. |
| Schema podstawy prawnej | do decyzji | Nie laczymy jej automatycznie z `acceptedAnswer`. |
| Raport pokrycia | gotowe lokalnie | Komenda `legal-basis:coverage` pokazuje calosc/zrobione/zostalo i braki. |

### 0.1 Co dal raport `legal-basis:coverage`

Raport zamienil problem z "czy mamy wystarczajaco danych?" na konkretne liczby. Od tego momentu nie oceniamy gotowosci edytora na oko, tylko przez pokrycie bazy pytan i komplet katalogu przepisow.

Lokalny wynik z 2026-06-16:

| Metryka | Wynik | Znaczenie |
| --- | ---: | --- |
| Publiczne rekordy pytan | 16605 | Surowe rekordy `questions` w widocznych kategoriach. |
| Publiczne pytania kanoniczne | 3151 | Realna liczba publicznych grup pytan do opracowania. |
| Zweryfikowana podstawa prawna | 53/3151 | Tylko 1.7% pytan ma gotowa podstawe. |
| Brak zweryfikowanej podstawy | 3098 | Tyle zostaje do przypisania lub weryfikacji. |
| Z opublikowanym artykulem `/przepisy` | 52 | Tyle pytan ma link do gotowego opracowania. |
| Bez opublikowanego artykulu | 1 | Podstawa istnieje, ale artykul nie jest publiczny. |
| Z tematem opcjonalnym do uzupelnienia | 0 | Po zmianie nullable obecne zweryfikowane rekordy nadal maja temat. |
| Wskazanie na poziomie samego artykulu | 50 | Te relacje trzeba pozniej doprecyzowac do ustepu/punktu, jesli pytanie tego wymaga. |
| Wszystkie jednostki prawne | 1270 | Lokalny import z ELI HTML dla `Prawo o ruchu drogowym`. |
| Zweryfikowane jednostki prawne | 1270 | Po audycie technicznym wszystkie jednostki PoRD sa lokalnie `verified`. |
| Jednostki z `canonical_path` | 1270 | Pola hierarchii sa uzupelnione po imporcie manifestu. |
| Jednostki z rodzicem | 1118 | Wiekszosc jednostek jest podpieta pod artykul/ustep/punkt. |
| Opublikowane tematy i artykuly | 10/10 | Warstwa `/przepisy` istnieje, ale nie pokrywa calego zakresu pytan. |

Wniosek operacyjny:

- obecny inline prototyp `Uzasadnienia prawnego` nie nadaje sie jeszcze do produkcji,
- mamy lokalnie zweryfikowany katalog pierwszego aktu, ale nadal potrzebujemy przypisac podstawy do pytan,
- `Wybierz przepis` nie jest juz statycznym selectem z kilkunastoma pozycjami,
- raport jest teraz checkpointem po kazdym imporcie i po kazdym batchu recznej pracy,
- merge/deploy tej warstwy jest bezpieczny dopiero wtedy, gdy raport potwierdzi sensowne pokrycie i workflow nie wymusza nietrafionych wyborow.

### 0.2 Najblizszy tor developmentu

Najblizsza praca nie polega na dopisywaniu kolejnych recznych rekordow do obecnego selecta. Najpierw budujemy fundament danych:

1. Ustalic zakres aktow dla pierwszego importu.
2. Zaczac od `Prawo o ruchu drogowym`, bo juz teraz jest glownym aktem w MVP.
3. Dodac brakujaca strukture danych dla hierarchii jednostek, jesli obecny model `legal_units` nie wystarczy.
4. Zbudowac importer idempotentny: ponowne uruchomienie aktualizuje rekordy, ale nie tworzy duplikatow. Gotowe.
5. Zbudowac generator manifestu z ELI HTML. Gotowe.
6. Po imporcie uruchomic `php artisan legal-basis:coverage` i porownac katalog oraz pokrycie. Gotowe lokalnie.
7. Przejrzec/oznaczyc zakres jednostek, ktore moga przejsc z `needs_review` do `verified`. Gotowe lokalnie dla PoRD przez `legal-basis:audit-units --write`.
8. Przebudowac UI na searchable picker. Gotowe lokalnie: pokazuje `verified` i `needs_review`, ale wybrac do zapisu mozna tylko `verified`.
9. Zmienic temat na opcjonalny, zeby nie wymuszac nietrafionego wyboru. Gotowe lokalnie.
10. Uruchomic audyt/promocje jednostek `needs_review` do `verified`. Gotowe lokalnie dla PoRD.
11. Nastepny krok: recznie przypisywac zweryfikowane przepisy do pytan i mierzyc postep `legal-basis:coverage`.

Definition of done dla nastepnego etapu:

- mamy lokalnie wygenerowana liste artykulow/ustepow/punktow/liter dla pierwszego aktu,
- kazda jednostka ma stabilny identyfikator techniczny i oficjalne zrodlo,
- importer manifestu mozna uruchomic wiele razy bez duplikatow,
- raport pokazuje wyraznie wiekszy katalog niz poczatkowe 13 jednostek,
- nowe jednostki sa `needs_review`, dopoki nie przejda kontroli; PoRD przeszedl lokalnie kontrole techniczna,
- edytor przepisu korzysta z wyszukiwarki, nie ze statycznego selecta,
- edytor nie wymaga wyboru tematu ani artykulu,
- nie robimy jeszcze schema podstawy prawnej.

## 1. Problem

Pierwotny prototyp inline edycji `Uzasadnienia prawnego` nie byl gotowy do produkcji, bo korzystal z waskiego katalogu MVP:

- lokalnie bylo tylko kilkanascie `legal_units`,
- `Wybierz przepis` pokazywal kilka recznie dodanych podstaw prawnych, a nie pelny katalog,
- `legal_topic_id` byl traktowany jako wymagany wybor, co moglo wymuszac nietrafiony temat,
- artykul `/przepisy/{slug}` jest opcjonalny, ale przeplyw nie rozroznia jeszcze wyraznie: przepis, temat SEO i gotowe opracowanie,
- schema pytania aktualizuje `acceptedAnswer` dla sekcji `Wyjasnienie`, ale nie opisuje jeszcze osobnej podstawy prawnej.

Wniosek: nie deployujemy obecnego selekta przepisow jako docelowego narzedzia dla 3 tys. pytan.

## 2. Zasady docelowe

1. `Przepis` musi byc precyzyjny i wybierany z pelnej bazy jednostek prawnych, nie z recznej listy kilku pozycji.
2. `Temat` jest warstwa redakcyjna/SEO, a nie warunek poprawnosci prawnej. Nie wolno wybierac go na sile.
3. `Artykul` jest linkiem do gotowego opracowania. Moze byc pusty do czasu publikacji tekstu.
4. Publiczny blok moze pokazac zweryfikowana podstawe prawna bez artykulu, ale tylko wtedy, gdy przepis i notatka sa zweryfikowane.
5. Schema dla podstawy prawnej dodajemy dopiero po ustaleniu formatu i testow, oddzielnie od `acceptedAnswer`.

## 3. Docelowy model danych

### 3.1 Pelny katalog `legal_units`

Katalog powinien przechowywac jednostki na poziomie potrzebnym do odpowiedzi egzaminacyjnych:

- akt prawny,
- artykul,
- ustęp,
- punkt,
- litera,
- ewentualnie paragraf dla rozporzadzen.

Minimalne pola logiczne:

- `legal_act_id`,
- `type`, np. `article`, `paragraph`, `section`, `point`, `letter`,
- `label`, np. `art. 26 ust. 6`, `art. 24 ust. 2 pkt 1`,
- `canonical_path`, np. `26/6`, `24/2/1`,
- `parent_legal_unit_id` albo rownowazny sposob odtworzenia hierarchii,
- `official_excerpt`,
- `source_url`,
- `effective_from`,
- `last_checked_at`,
- `status`.

Obecny `label` jako tekst moze zostac, ale nie wystarczy jako jedyne zrodlo prawdy.

### 3.2 Opcjonalny temat

`question_legal_references.legal_topic_id` powinien przestac byc wymagany dla samej podstawy prawnej.

Opcje techniczne:

- preferowane: migracja pozwalajaca na `nullable` dla `question_legal_references.legal_topic_id`,
- alternatywa tymczasowa: status `needs_topic` albo temat roboczy `Do przypisania`, ale nie jako docelowe SEO.

Docelowo temat przypinamy wtedy, gdy istnieje sensowna grupa redakcyjna lub gotowy artykul.

### 3.3 Opcjonalny artykul

`legal_content_page_id` pozostaje opcjonalne.

Zasada renderowania:

- jest przepis + status `verified` -> pokazujemy `Uzasadnienie prawne`,
- `public_note` jest opcjonalnym komentarzem redakcyjnym, gdy sama tresc przepisu nie domyka kontekstu pytania,
- jest dodatkowo opublikowany artykul -> pokazujemy `Zobacz opracowanie przepisu`,
- artykul nieopublikowany/draft -> brak linku publicznego.

## 4. Import pelnej bazy przepisow

### Faza A: Inwentaryzacja aktow

Ustalic, z jakich aktow prawnych realnie korzystaja pytania:

- Prawo o ruchu drogowym,
- rozporzadzenie w sprawie znakow i sygnalow drogowych,
- ustawa o kierujacych pojazdami,
- rozporzadzenia dotyczace egzaminowania, szkolenia, warunkow technicznych albo inne akty, jesli wynikaja z katalogu pytan.

Zrodla musza byc oficjalne: ISAP, ELI, Dziennik Ustaw, gov.pl.

### Faza B: Parser/importer

Importer dzielimy na dwa poziomy:

1. `legal-basis:build-units-manifest` - gotowy lokalnie generator manifestu z oficjalnego ELI HTML.
2. `legal-basis:import-units` - gotowa lokalnie bramka zapisu manifestu do bazy.

Parser/generator:

- pobiera lub wczytuje oficjalny tekst aktu,
- dzieli go na jednostki,
- tworzy hierarchie artykul/ustep/punkt/litera,
- generuje stabilny `canonical_path`,
- zapisuje `official_excerpt`,
- oznacza rekordy jako `verified` dopiero po weryfikacji z oficjalnym zrodlem.

Importer musi byc idempotentny. Ponowne uruchomienie nie moze tworzyc duplikatow.

Aktualna komenda budowy manifestu z ELI:

```bash
php artisan legal-basis:build-units-manifest https://eli.gov.pl/api/acts/DU/1997/602/text.html --output=storage/app/legal-units/prawo-o-ruchu-drogowym.generated.json
```

Lokalny wynik:

- wygenerowane jednostki: 1270,
- pominiete fragmenty zmian innych aktow: 6,
- pominiete jednostki bez rodzica: 0.

Aktualna komenda importu manifestu:

```bash
php artisan legal-basis:import-units storage/app/legal-units/prawo-o-ruchu-drogowym.json
php artisan legal-basis:import-units storage/app/legal-units/prawo-o-ruchu-drogowym.json --write
php artisan legal-basis:import-units storage/app/legal-units/prawo-o-ruchu-drogowym.json --json
```

Lokalny zapis po wygenerowaniu manifestu:

```bash
php artisan legal-basis:import-units storage/app/legal-units/prawo-o-ruchu-drogowym.generated.json --write
```

Wynik lokalny:

- input: 1270,
- created: 1257,
- updated: 13,
- invalid: 0.

Zasady:

- bez `--write` komenda robi tylko preview,
- `--write` zapisuje akt i jednostki,
- jednostki sa upsertowane po `legal_act_id + canonical_path`, a dla starych rekordow awaryjnie po `slug`,
- `parent_canonical_path` laczy jednostki w hierarchie,
- brakujacy rodzic blokuje import manifestu,
- istniejacy status `verified` nie jest degradowany do `needs_review`,
- istniejacy opisowy `title`, `summary` i `official_excerpt` nie sa kasowane pustym albo technicznym wpisem z parsera,
- status `verified` ustawiamy tylko dla danych po review z oficjalnego zrodla.

Minimalny format manifestu:

```json
{
  "act": {
    "slug": "prawo-o-ruchu-drogowym",
    "title": "Ustawa z dnia 20 czerwca 1997 r. - Prawo o ruchu drogowym",
    "short_title": "Prawo o ruchu drogowym",
    "source_url": "https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602",
    "eli_url": "https://eli.gov.pl/eli/DU/1997/602/ogl",
    "effective_from": "1998-01-01",
    "last_checked_at": "2026-06-16 10:00:00",
    "status": "verified"
  },
  "units": [
    {
      "type": "article",
      "label": "art. 26",
      "canonical_path": "26",
      "slug": "art-26",
      "title": "Obowiazki kierujacego wobec pieszych",
      "status": "verified"
    },
    {
      "type": "section",
      "label": "art. 26 ust. 6",
      "canonical_path": "26/6",
      "parent_canonical_path": "26",
      "slug": "art-26-ust-6",
      "title": "Obowiazek zatrzymania przy przystanku tramwajowym",
      "official_excerpt": "Fragment z oficjalnego tekstu po review.",
      "status": "verified"
    }
  ]
}
```

Oficjalny punkt startowy dla pierwszego aktu:

- ELI: `https://eli.gov.pl/eli/DU/1997/602/ogl`
- tekst ujednolicony PDF wskazany na ELI/ISAP: `D19970602Lj.pdf`
- ISAP moze wymagac weryfikacji antyspamowej w przegladarce, ale ELI podaje linki do tekstu HTML, tekstu ogloszonego i tekstu ujednoliconego.

### Faza C: Pokrycie

Przed wlaczeniem produkcyjnego edytora potrzebujemy raportu:

- ile jest aktywnych pytan publicznych,
- ile pytan ma zweryfikowana podstawe prawna,
- ile pytan ma podstawe bez tematu,
- ile pytan ma podstawe z linkiem do artykulu,
- ile jednostek prawnych jest dostepnych per akt,
- ile relacji wskazuje na szeroki artykul zamiast precyzyjnego ustępu/punktu.

Aktualna komenda raportu:

```bash
php artisan legal-basis:coverage
php artisan legal-basis:coverage --json
php artisan legal-basis:coverage --sample-limit=30
```

Definicja liczenia:

- `calosc` to publiczne pytania kanoniczne po widocznym `external_id`, czyli `99` i `pj360:99` licza sie jako jedno pytanie `99`,
- `zrobione` to pytania kanoniczne, ktore maja co najmniej jedna relacje `question_legal_references` ze statusem `verified` i `verified_at`,
- `zostalo` to pytania bez zweryfikowanej podstawy prawnej,
- ukryte/testowe kategorie nie wchodza do raportu,
- link do artykulu liczy sie tylko wtedy, gdy `legal_content_page` jest opublikowany.

### Faza D: Audyt i promocja jednostek

Audyt jednostek prawnych jest techniczny. Odpowiada na pytanie, czy rekord `legal_units` wiernie odpowiada oficjalnemu `text.html`, ale nie decyduje jeszcze, czy ten przepis pasuje do konkretnego pytania egzaminacyjnego.

Aktualna komenda:

```bash
php artisan legal-basis:audit-units prawo-o-ruchu-drogowym
php artisan legal-basis:audit-units prawo-o-ruchu-drogowym --json
php artisan legal-basis:audit-units prawo-o-ruchu-drogowym --write
```

Zasady:

- bez `--write` komenda robi tylko preview,
- `--write` promuje do `verified` tylko jednostki `needs_review`, ktore przejda wszystkie kontrole,
- jednostka musi istniec w oficjalnym `text.html`,
- `type`, `label`, `canonical_path`, `source_url`, rodzic i `official_excerpt` musza zgadzac sie z manifestem zbudowanym z oficjalnego HTML,
- akt prawny musi miec status `verified`,
- kazda promowana jednostka dostaje `last_checked_at` oraz wpis w `legal_source_checks`,
- jednostki z problemami zostaja `needs_review` i trafiaja do probki raportu.

Wazne rozroznienie:

- audyt jednostki = czy przepis w katalogu jest poprawnie zapisany,
- redakcyjna weryfikacja pytania = czy ten przepis jest dobra podstawa dla konkretnego pytania.

## 5. UX edytora

### 5.1 Przepis

Nie uzywamy zwyklego selecta z pelna baza, bo lista bedzie za duza.

Stan lokalny:

- [x] wyszukiwarka/asynchroniczny combobox,
- [x] szukanie po `art. 26`, `ust. 6`, slowach z tytulu i fragmencie przepisu,
- [x] wynik pokazuje akt, etykiete, status i krotki excerpt,
- [x] `needs_review` jest widoczne w katalogu, ale zablokowane przed wyborem; po audycie PoRD wszystkie lokalne jednostki tego aktu sa `verified`,
- [ ] mozliwosc wygodnego podejrzenia oficjalnego zrodla z poziomu edytora.

### 5.2 Temat

Temat jako opcjonalny wybor:

- [x] `Bez tematu` jest dozwolone,
- [x] jesli nie ma pasujacego tematu, zapisujemy podstawe prawna bez tematu,
- osobna kolejka/raport pokazuje rekordy `verified_without_topic`.

### 5.3 Artykul

Artykul jako opcjonalny wybor:

- `Bez linku do artykulu` jest dozwolone,
- mozna podpiac artykul pozniej,
- lista artykulow moze byc filtrowana po temacie, ale nie moze blokowac zapisu przepisu.

## 6. Workflow redakcyjny

1. Admin otwiera publiczne pytanie.
2. Admin moze dodac publiczna notatke prawna, jesli trzeba uzupelnic kontekst.
3. Admin wybiera precyzyjny przepis z pelnej bazy.
4. Temat i artykul sa opcjonalne.
5. Rekord startuje jako `needs_review` albo `draft`, jesli brakuje weryfikacji.
6. Rekord staje sie `verified` dopiero po sprawdzeniu oficjalnego zrodla.
7. Publiczny blok pokazuje tylko rekordy `verified`.
8. Publiczny blok pokazuje etykiete przepisu oraz `official_excerpt`, jesli jednostka prawna ma tresc z oficjalnego zrodla.
9. Raport pokazuje postep: calosc, zrobione, zostalo, bez tematu, bez artykulu, do review.

## 7. Schema

Obecnie `acceptedAnswer` aktualizuje sie po zapisie sekcji `Wyjasnienie`.

`Uzasadnienie prawne` wymaga osobnej decyzji schema. Proponowany kierunek:

- nie mieszac podstawy prawnej z `acceptedAnswer.text`,
- dodac do `Question` albo `WebPage` osobne odniesienie do przepisu, np. przez `citation`, `isBasedOn` albo `about`,
- linkowac do publicznego artykulu tylko wtedy, gdy istnieje,
- dla samego przepisu bez artykulu uzyc stabilnego opisu i oficjalnego `source_url`.

Przed wdrozeniem schema potrzebne sa testy JSON-LD:

- pytanie z podstawa prawna bez artykulu,
- pytanie z podstawa prawna i artykulem,
- pytanie bez podstawy prawnej,
- draft/needs_review nie trafia do schema.

## 8. Blokery przed deployem inline edytora

- [ ] Pelny albo wystarczajaco kompletny importer/katalog przepisow dla zakresu pytan.
- [x] Edytor przepisu jako wyszukiwarka, nie statyczny select.
- [x] Temat opcjonalny.
- [ ] Artykul opcjonalny.
- [ ] Statusy `draft` / `needs_review` / `verified` w workflow.
- [ ] Publiczny render tylko dla `verified`.
- [x] Raport pokrycia pytan.
- [ ] Decyzja i testy dla schema podstawy prawnej.

## 9. Najbezpieczniejszy nastepny sprint

1. [x] Zablokowac decyzyjnie deploy obecnego prototypu prawnego do czasu naprawy.
2. [x] Dodac raport `legal-basis:coverage`.
3. [x] Dodac hierarchie `legal_units` i idempotentny importer manifestu.
4. [x] Zbudowac parser/generator oficjalnego aktu do manifestu.
5. [x] Zaimportowac lokalnie katalog jednostek dla Prawa o ruchu drogowym jako `needs_review`.
6. [x] Dodac audyt/promocje jednostek z `needs_review` do `verified`.
7. [x] Przebudowac edytor na searchable picker.
8. [x] Zmienic temat na opcjonalny.
9. [x] Uruchomic audyt na lokalnym katalogu Prawa o ruchu drogowym.
10. [ ] Recznie przypisywac zweryfikowane przepisy do pytan przez inline edytor i mierzyc postep.
11. [ ] Dopiero po smoke/review tej pracy wrocic do decyzji o deployu.

Smoke test lokalny 2026-06-16:

- admin otwiera publiczne pytanie,
- sekcja `Uzasadnienie prawne` pokazuje `Dodaj`,
- po kliknieciu formularz pokazuje wyszukiwarke przepisu,
- fraza `art. 26 ust. 6` zwraca zweryfikowany wynik `Prawo o ruchu drogowym art. 26 ust. 6`,
- klikniecie wyniku ustawia `legal_unit_id = 13`,
- formularz zostal anulowany, bez zapisu danych.

Testy lokalne po zmianie tematu na opcjonalny:

```bash
php artisan test tests/Feature/Admin/AdminQuestionLegalReferenceUpdateTest.php tests/Feature/Admin/AdminLegalUnitSearchTest.php tests/Feature/PublicQuestionDatabasePageTest.php tests/Feature/Console/LegalBasisCoverageCommandTest.php
```

Wynik: 19 testow, 250 asercji, zielone.

Audyt lokalnego katalogu PoRD:

```bash
php artisan legal-basis:audit-units prawo-o-ruchu-drogowym
php artisan legal-basis:audit-units prawo-o-ruchu-drogowym --write
```

Wynik preview: 1257/1257 jednostek `needs_review` przeszlo kontrole, 0 odrzuconych.

Wynik `--write`: 1257 jednostek promowanych do `verified`. Po audycie katalog pokazuje 1270/1270 jednostek PoRD jako `verified`.
