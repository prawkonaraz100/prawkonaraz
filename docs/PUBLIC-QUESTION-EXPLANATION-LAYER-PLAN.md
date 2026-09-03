# Public Question Explanation Layer: Plan wdrozenia

Status: plan wdrozenia  
Data: 2026-06-15  
Zakres: publiczne strony pytan `/pytanie/{id}/{slug}`  
Priorytet: wysokie SEO / E-E-A-T, niskie ryzyko dla modulu nauki

Aktualny stan MVP:

- [x] dokumentacja i metodologia batchowa,
- [x] osobna tabela `question_public_explanations`,
- [x] model, factory, resolver i seeder MVP,
- [x] publiczny render jednej sekcji `Wyjasnienie` z publiczna trescia albo systemowym fallbackiem,
- [x] pytanie `99` jako pierwszy golden sample,
- [x] batch 1: 16 opublikowanych publicznych wyjasnien dla pytan powiazanych z `/przepisy`,
- [x] test seedera kontrolujacy status `published`, daty review, zakres 80-150 slow i zakaz wgrywania do `questions.explanation`,
- [x] admin-only inline edycja publicznego `Wyjasnienia` na stronie `/pytanie/{id}/{slug}`,
- [x] sitemap pytan uwzglednia `updated_at` publicznego omowienia w `lastmod`,
- [x] schema `acceptedAnswer` uzywa tej samej tresci, ktora jest widoczna jako publiczne `Wyjasnienie`,
- [x] video sitemap nie uzywa publicznego omowienia jako `video:description`,
- [ ] produkcyjny workflow `Uzasadnienia prawnego` jest osobnym zadaniem naprawczym; obecny inline prototyp nie jest gotowy do merge/deploy.

## 1. Cel

Chcemy dodac do publicznych stron pytan dluzsze, niezalezne wyjasnienie pod SEO i zaufanie uzytkownika, bez zmieniania tresci wykorzystywanych w produkcie edukacyjnym.

Docelowo publiczna strona pytania ma trzy osobne warstwy:

1. `Odpowiedz` - poprawna odpowiedz i punkty.
2. `Wyjasnienie` - publiczna warstwa SEO z `question_public_explanations`, a w okresie przejsciowym fallback do `questions.explanation`.
3. `Uzasadnienie prawne` - warstwa `QuestionLegalReference` z podstawa prawna, opcjonalnym `public_note` i linkiem do `/przepisy`.

## 2. Problem, ktory rozwiazujemy

Obecne `questions.explanation` jest dobre jako szybka odpowiedz po pytaniu, ale czesto jest zbyt krotkie dla publicznego SEO.

Nie mozemy jednak po prostu wydluzyc `questions.explanation`, bo to pole jest uzywane w wielu miejscach aplikacji:

- `app/Http/Controllers/StudySessionController.php` - payload pytania w klasycznej nauce i wynikach sesji.
- `app/Support/StudySessionApiPayloadBuilder.php` - odpowiedzi i wyjasnienia po rozwiazaniu pytania.
- `resources/js/Pages/StudySessions/Show.vue` - widok nauki, Zen, wyjasnienia i inline edycja.
- `app/Support/PublicQuestionCatalogService.php` - publiczny model strony pytania.
- `resources/views/questions-database/show.blade.php` - publiczny blok `Wyjasnienie`.
- `app/Support/PublicQuestionSchemaService.php` - `acceptedAnswer` w schema.
- `app/Filament/Resources/Questions/Schemas/QuestionForm.php` - adminowa edycja pytania.
- `docs/ADMIN-INLINE-QUESTION-EXPLANATION.md` - dokumentacja edycji inline, ktora nadpisuje `questions.explanation`.

Wniosek: `questions.explanation` zostaje nietkniete. Nowa tresc publiczna musi byc osobna warstwa danych.

## 3. Granice bezpieczenstwa

W ramach tego wdrozenia NIE robimy:

- nie zmieniamy `questions.prompt`,
- nie zmieniamy `questions.explanation`,
- nie zmieniamy odpowiedzi `option_a`, `option_b`, `option_c`, `correct_answer`,
- nie zmieniamy mediow, playera, adnotacji, timera wideo ani logiki `/nauka`,
- nie zmieniamy procesu importu pytan,
- nie podmieniamy `QuestionLegalReference.public_note`,
- nie kopiujemy tresci z konkurencji.

Nowa warstwa ma byc widoczna tylko na publicznych stronach SEO.

## 4. Proponowana architektura danych

Dodajemy osobna tabele:

`question_public_explanations`

Proponowane pola MVP:

| Pole | Typ | Cel |
| --- | --- | --- |
| `id` | bigint | Klucz glowny. |
| `external_id` | string, unique | Identyfikator grupy pytania, np. `99`. |
| `question_id` | bigint, nullable | Opcjonalny override dla pojedynczego rekordu pytania, jesli grupa nie wystarcza. |
| `title` | string, nullable | Opcjonalny tytul redakcyjny. Publiczny widok pytania pokazuje sekcje jako `Wyjasnienie`. |
| `body` | longText | Publiczne omowienie SEO. |
| `status` | string | `draft`, `published`, `needs_review`, `archived`. |
| `author_id` | bigint, nullable | Autor tresci, jesli laczymy z tabela autorow/reviewerow. |
| `reviewer_id` | bigint, nullable | Reviewer merytoryczny. |
| `published_at` | timestamp, nullable | Data publikacji. |
| `last_reviewed_at` | date, nullable | Data ostatniej weryfikacji. |
| `source_note` | text, nullable | Krotka notatka redakcyjna o zrodle kontekstu. |
| `internal_note` | text, nullable | Notatka dla zespolu, niewidoczna publicznie. |
| `created_at`, `updated_at` | timestamps | Standardowy audyt techniczny. |

Decyzja MVP:

- podstawowym kluczem publikacji jest `external_id`, bo jedno pytanie publiczne moze wystepowac w wielu kategoriach,
- `question_id` zostawiamy jako bezpieczny awaryjny override na przyszlosc,
- publicznie renderujemy tylko rekordy ze statusem `published`.

## 5. Warstwa aplikacyjna

Minimalne elementy implementacji:

1. Model `QuestionPublicExplanation`.
2. Migracja tabeli `question_public_explanations`.
3. Resolver, np. `PublicQuestionExplanationService`.
4. Rozszerzenie publicznego modelu pytania o `public_explanation`.
5. Render w `resources/views/questions-database/show.blade.php`.
6. Seeder MVP z wzorcowym omowieniem dla pytania `99` i kolejnymi batchami redakcyjnymi.

Kolejnosc wyszukiwania publicznego wyjasnienia:

1. Najpierw `question_id`, jesli istnieje opublikowany override.
2. Potem `external_id` dla calej grupy pytania.
3. Jesli brak rekordu albo status nie jest `published`, publiczna strona uzywa systemowego `questions.explanation` jako fallbacku.

Wazne: fallback jest stanem przejsciowym. Pozwala utrzymac kompletne publiczne strony dla tysiecy pytan, zanim wszystkie dostana dopracowane publiczne wyjasnienia. Nie wolno przez to edytowac `questions.explanation` pod SEO.

## 6. Publiczny layout strony pytania

Docelowa kolejnosc sekcji:

1. Pytanie, odpowiedz, punkty.
2. `Wyjasnienie` - jedna sekcja widoczna publicznie:
   - publiczne wyjasnienie, jesli istnieje,
   - systemowe `questions.explanation` tylko jako fallback przejsciowy.
3. `Uzasadnienie prawne` - zweryfikowana podstawa prawna.
4. Zakres kategorii i powiazania.

Nie pokazujemy obok siebie `Wyjasnienie` i `Omowienie sytuacji`, bo wtedy dwie podobne warstwy konkuruja ze soba i oslabiaja hierarchie strony.

Nazwa sekcji publicznej:

`Wyjasnienie`

Dlaczego tak: dla uzytkownika z Google to jest glowna odpowiedz edukacyjna. Technicznie moze pochodzic z nowej tabeli albo fallbacku, ale UI pokazuje jedna jasna warstwe.

## 7. Standard redakcyjny

Kazde publiczne `Wyjasnienie` powinno spelniac te zasady:

- 80-150 slow,
- prosty jezyk,
- unikalna tresc dla konkretnego pytania,
- opis widocznej sytuacji i logiki poprawnej odpowiedzi,
- bez przepisywania samej odpowiedzi jednym zdaniem,
- bez kopiowania `questions.explanation`,
- bez kopiowania tresci konkurencji,
- bez zbyt kategorycznych podstaw prawnych, jesli nie mamy ich zweryfikowanych,
- bez zmiany sensu pytania,
- bez wchodzenia w instrukcje produktu typu "kliknij odpowiedz".

Przyklad roli tekstu:

- `questions.explanation` odpowiada szybko: co jest poprawne.
- `QuestionPublicExplanation.body` wyjasnia szerzej: dlaczego ta sytuacja wymaga takiego zachowania.
- `QuestionLegalReference.public_note` jest opcjonalnym komentarzem: dopowiada kontekst pytania wtedy, gdy sama tresc przepisu nie wystarcza.

## 8. Schema i SEO

Aktualna decyzja:

- publiczna strona pytania generuje jeden blok JSON-LD z `@graph`,
- graf zawiera: `Organization`, `WebSite`, `BreadcrumbList`, `WebPage`, `Question` oraz opcjonalnie `VideoObject`,
- encje sa polaczone przez stabilne `@id`, np. `#webpage`, `#question`, `#video-99`, `/#organization`, `/#website`,
- `acceptedAnswer.text` uzywa tej samej tresci, ktora jest widoczna w publicznej sekcji `Wyjasnienie`,
- jesli istnieje opublikowane `question_public_explanations`, schema uzywa publicznego wyjasnienia,
- jesli publicznego wyjasnienia jeszcze nie ma, schema uzywa systemowego fallbacku `questions.explanation`,
- nie pokazujemy w schema tresci draftow ani niepublikowanych publicznych wyjasnien,
- `Question` nie publikuje `suggestedAnswer` ani `answerCount`, zeby nie udawac forumowej listy odpowiedzi,
- `VideoObject.contentUrl` wskazuje bezposredni plik MP4,
- `VideoObject.url` wskazuje kanoniczna strone pytania,
- `VideoObject.embedUrl` jest pomijane, dopoki nie mamy osobnego URL-a playera/embed,
- sitemapy pytan uwzgledniaja `updated_at` opublikowanego omowienia w `lastmod`,
- video sitemap nie korzysta z tej warstwy i zostaje przy neutralnym `question_media.seo_video_description`,
- publiczny HTML i schema maja byc spojne: Google powinien widziec w schema to, co uzytkownik widzi na stronie.

Decyzja o sitemap:

- zwykla sitemap pytan nie przechowuje tresci odpowiedzi ani omowien,
- publikacja albo edycja omowienia zmienia jednak publiczna zawartosc strony pytania,
- dlatego `lastmod` dla URL-a pytania powinien byc maksimum z dat: `questions.updated_at`, mediow publicznych i `question_public_explanations.updated_at`,
- dzieki temu Google dostaje sygnal, ze warto ponownie pobrac konkretny URL pytania.

Decyzja o schema:

- `acceptedAnswer` nie moze korzystac z ukrytego tekstu,
- `questions.explanation` nie jest juz docelowa trescia publiczna, ale moze byc przejsciowym fallbackiem,
- docelowo po uzupelnieniu bazy wiekszosc publicznych URL-i powinna miec wyjasnienie z `question_public_explanations`.

## 9. Admin i workflow

Pierwszy MVP byl seed-driven, ale po batchu 1 decyzja operacyjna jest inna: publiczne wyjasnienia maja byc tworzone recznie z kontekstu konkretnej publicznej strony pytania.

Obecny workflow manual-first:

1. admin otwiera publiczny URL pytania,
2. sekcja `Wyjasnienie` pokazuje przycisk `Edytuj`,
3. admin wpisuje albo poprawia publiczny tekst w textarea,
4. `Zapisz` tworzy albo aktualizuje rekord w `question_public_explanations`,
5. zapis ustawia rekord jako `published`, aktualizuje `last_reviewed_at`, nie zmienia `questions.explanation` i po sukcesie odswieza strone,
6. po odswiezeniu widoczny HTML i schema `acceptedAnswer` sa liczone z tego samego zapisanego rekordu,
7. zwykly uzytkownik i gosc nie widza kontrolek edycji,
8. `/nauka` dalej korzysta ze starego systemowego `questions.explanation`.

Workflow dla `Uzasadnienia prawnego`:

Status: osobny tor naprawczy, nie czesc gotowego MVP publicznych wyjasnien.

Obecny inline prototyp `Uzasadnienia prawnego` nie powinien isc do produkcji, dopoki nie ma pelnego albo wystarczajaco kompletnego katalogu przepisow i ustalonego procesu review. Statyczny select kilku jednostek zostal lokalnie zastapiony wyszukiwarka, a temat jest opcjonalny.

Docelowy workflow po naprawie:

1. admin otwiera publiczny URL pytania,
2. sekcja `Uzasadnienie prawne` pokazuje `Dodaj` albo `Edytuj`,
3. admin wybiera precyzyjny przepis z wyszukiwarki pelnej bazy jednostek prawnych,
4. admin moze wpisac publiczna notatke prawna jako opcjonalne uzupelnienie,
5. temat jest opcjonalny; jesli nie ma pasujacego tematu, nie wybieramy go na sile,
6. artykul `/przepisy` jest opcjonalny i moze zostac podpiety pozniej,
7. rekord moze zaczac jako `draft` albo `needs_review`,
8. publiczny blok pokazuje podstawe prawna dopiero po statusie `verified`,
9. schema podstawy prawnej jest wdrazana dopiero po osobnej decyzji i testach JSON-LD.

Stan lokalny 2026-06-16: wyszukiwarka przepisu jest gotowa w prototypie i potrafi znalezc `art. 26 ust. 6` z pelniejszego katalogu. Temat moze zostac pusty jako `Bez tematu`. Audyt techniczny PoRD promowal 1270/1270 jednostek tego aktu do `verified`. Nadal do zrobienia jest reczne przypisywanie podstaw prawnych do pytan i decyzja, kiedy ten workflow moze isc na produkcje.

Autorytatywny plan naprawy tej warstwy jest w `docs/LEGAL-BASIS-REPAIR-PLAN.md`.

Zasada: inline edycja na publicznej stronie sluzy tylko do publicznej warstwy SEO/E-E-A-T. Nie wolno jej mylic z adminowa inline edycja systemowego `questions.explanation` w trybie nauki.

Seeder moze zostac jako narzedzie startowe, migracyjne albo awaryjne, ale docelowa praca redakcyjna powinna isc przez reczny zapis przy pytaniu.

Docelowo panel admina powinien pozwalac:

- dodac/edytowac publiczne omowienie dla pytania,
- dodac/edytowac publiczna podstawe prawna i podpiac artykul wtedy, gdy bedzie gotowy,
- oznaczyc status `draft` / `published` / `needs_review`,
- widziec date ostatniej weryfikacji,
- wskazac autora i reviewera,
- podejrzec publiczny URL pytania.

Wazne: panel admina albo inline edycja dla tej warstwy nie powinny byc tym samym formularzem, ktory edytuje `questions.explanation`.

## 10. Plan wdrozenia

### Faza 1: Fundament danych

- [x] Dodac migracje `question_public_explanations`.
- [x] Dodac model `QuestionPublicExplanation`.
- [x] Dodac resolver publicznych omowien.
- [x] Dodac testy jednostkowe/feature dla resolvera.

Definition of done:

- rekord `published` jest znajdowany po `external_id`,
- rekord `draft` nie jest renderowany,
- brak rekordu nie psuje strony pytania.

### Faza 2: Publiczna strona pytania

- [x] Rozszerzyc publiczny model pytania o publiczne wyjasnienie z fallbackiem.
- [x] Renderowac jedna sekcje `Wyjasnienie` w `questions-database/show.blade.php`.
- [x] Gdy istnieje opublikowane publiczne wyjasnienie, nie renderowac systemowego `questions.explanation`.
- [x] Gdy publicznego wyjasnienia brak, renderowac systemowe `questions.explanation` jako fallback przejsciowy.
- [x] Upewnic sie, ze `Uzasadnienie prawne` zostaje osobna warstwa.

Definition of done:

- `/pytanie/99/...` pokazuje publiczne wyjasnienie jako glowna sekcje `Wyjasnienie`,
- inne pytania bez rekordu nadal pokazuja systemowy fallback,
- layout nie dubluje naglowkow i nie miesza warstw.

### Faza 3: Pierwsza tresc wzorcowa

- [x] Utworzyc seed dla pytania `99`.
- [x] Napisac omowienie 80-150 slow.
- [x] Zweryfikowac, ze tekst nie jest kopia `questions.explanation`.
- [x] Zweryfikowac, ze tekst nie ujawnia nic sprzecznego z odpowiedzia i podstawa prawna.

Definition of done:

- pytanie `99` jest wzorcem jakosci dla kolejnych pytan,
- tresc ma autora/reviewera albo przynajmniej gotowe pola na te dane,
- dokumentacja mowi, ze tego tekstu nie wolno wgrywac do `questions.explanation`.

### Faza 4: Testy regresji

- [x] Test publicznej strony z opublikowanym omowieniem.
- [x] Test publicznej strony bez omowienia.
- [x] Test, ze publiczne wyjasnienie zastepuje systemowe wyjasnienie w HTML.
- [x] Test, ze publiczne wyjasnienie trafia do schema `acceptedAnswer`, gdy jest widoczne na stronie.
- [x] Test, ze draft publicznego wyjasnienia nie trafia do HTML ani schema.
- [x] Test, ze fallback systemowy jest nadal widoczny i obecny w schema dla pytań bez publicznego wyjasnienia.
- [x] Test, ze sitemap pytania uwzglednia `updated_at` opublikowanego omowienia w `lastmod`.
- [x] Test, ze video sitemap nie uzywa publicznego omowienia jako `video:description`.
- [x] Build frontu nie jest wymagany, bo zmiana dotyka PHP/Blade bez nowych assetow.

Definition of done:

- publiczna warstwa dziala,
- modul nauki nie widzi nowej tresci SEO,
- schema jest spojna z widoczna trescia.

### Faza 5: Deploy i smoke test

- [ ] Uruchomic migracje na produkcji.
- [ ] Uruchomic seeder publicznych omowien.
- [ ] Sprawdzic publicznie `/pytanie/99/...`.
- [ ] Sprawdzic losowe pytanie bez publicznego omowienia.
- [ ] Sprawdzic `/nauka` na pytaniu 99, czy pokazuje stare krotkie wyjasnienie.
- [ ] Sprawdzic HTML strony w narzedziu Rich Results / URL Inspection po deployu.
- [ ] Odswiezyc sitemapy z produkcyjnym hostem i mediami:

```bash
php artisan seo:refresh-sitemaps
```

Nie wymuszaj `MEDIA_PUBLIC_BASE_URL` recznie przy deployu. Na produkcji wartosc ma byc brana z `.env` i aktualnie wskazuje na publiczny storage mediów `https://prawkonaraz.pl/storage-bulk`. Wymuszenie zlej wartosci przed `config:cache` popsuje URL-e mediow w publicznych stronach i module nauki.

Definition of done:

- produkcja pokazuje nowa sekcje tylko tam, gdzie mamy rekord `published`,
- nie ma regresji w module nauki,
- nie ma pustych blokow SEO.

### Faza 6: Skalowanie

Po zatwierdzeniu wzorca dla pytania `99` przechodzimy partiami:

1. pytania powiazane z pierwszymi 10 stronami `/przepisy`,
2. pytania z wysokim ruchem albo wysokim potencjalem SEO,
3. pytania z filmami i obrazami, gdzie dodatkowy opis pomaga indeksacji,
4. dopiero pozniej szersze serie masowe.

Kazda partia powinna miec osobny audit:

- liczba pytan,
- lista `external_id`,
- licznik postepu: wszystkie publiczne grupy pytan / zrobione / zostalo,
- status tekstow,
- QA antyduplikacyjne,
- smoke test publicznych URL-i.

## 10.1 Metodologia pracy przy duzej liczbie pytan

Nie robimy jednego masowego generowania dla calej bazy. Taka operacja dalaby duze ryzyko duplikatow, bledow merytorycznych i tekstow wygladajacych automatycznie.

Przyjmujemy model batchowy:

1. `golden sample` - najpierw pytanie `99` jako wzorzec struktury, tonu i testow.
2. `batch 20-50` - male paczki tematyczne, np. tramwaje, piesi, pierwszenstwo, sygnalizacja.
3. `QA per batch` - kontrola dlugosci, unikalnosci, braku kopiowania `questions.explanation` i braku niezweryfikowanych twierdzen prawnych.
4. `published-only` - publicznie widoczne sa tylko rekordy ze statusem `published`.
5. `lastmod after publish` - publikacja batcha aktualizuje sygnal sitemap dla konkretnych URL-i.

Priorytety publikacji:

1. pytania z publicznym ruchem lub najwiekszym potencjalem wyszukiwania,
2. pytania powiazane z obecnymi stronami `/przepisy`,
3. pytania kategorii `B`,
4. pytania wystepujace w wielu kategoriach,
5. pytania z mediami, gdzie dodatkowy opis pomaga zrozumiec sytuacje.

Zasada jakosci:

Lepiej miec `500` dobrych, unikalnych omowien niz `10 000` slabych tekstow podobnych do siebie. Skalowanie zaczynamy dopiero po zatwierdzeniu wzorca i pierwszych malych batchy.

## 10.2 Workflow kontroli postepu

Przy publicznych wyjasnieniach nie liczymy surowych rekordow z tabeli `questions`, bo to samo pytanie moze wystepowac w wielu kategoriach, np. `99` dla A, B, C, D itd.

Obowiazujace definicje:

- `calosc` - publiczne, kanoniczne grupy pytan liczone po `external_id`, tym samym filtrem, ktory zasila publiczne URL-e pytan i sitemap.
- `zrobione` - unikalne `external_id`, dla ktorych istnieje rekord `question_public_explanations` ze statusem `published` i data `published_at`.
- `zostalo` - `calosc - zrobione`.
- `draft`, `needs_review` i `archived` nie licza sie jako zrobione, bo nie sa widoczne publicznie.
- `question_id` jest awaryjnym override dla pojedynczego rekordu pytania; standard batchowy nadal powinien uzywac `external_id`.

Ostatni lokalny pomiar po batchu 1:

| Data pomiaru | Calosc | Zrobione | Zostalo | Uwagi |
| --- | ---: | ---: | ---: | --- |
| 2026-06-16 | 3156 | 16 | 3140 | Pomiar z lokalnej bazy developerskiej po uruchomieniu `PublicQuestionExplanationSeeder`. Po deployu przeliczyc na produkcji. |

Batch 1 opublikowany w `PublicQuestionExplanationSeeder`:

```text
99, 10314, 10249, 10369, 10474, 10154, 10247, 10107,
469, 10237, 13562, 13781, 13782, 4170, 7237, 13035
```

Aktualny sposob sprawdzania statusu w lokalnym Dockerze:

```powershell
$code = @'
$catalog = app(App\Support\PublicQuestionCatalogService::class);
$rows = $catalog->canonicalQuestionSitemapRowsByCategory()->flatten(1);
$publicIds = $rows->map(fn ($row) => (string) $row["question"]->external_id)->unique()->values();
$done = App\Models\QuestionPublicExplanation::query()
    ->published()
    ->whereIn("external_id", $publicIds->all())
    ->pluck("external_id")
    ->map(fn ($id) => (string) $id)
    ->unique()
    ->values();
$remaining = $publicIds->diff($done)->values();
dump([
    "calosc" => $publicIds->count(),
    "zrobione" => $done->count(),
    "zostalo" => $remaining->count(),
    "zrobione_ids" => $done->sort()->values()->all(),
    "pierwsze_brakujace_ids" => $remaining->take(30)->all(),
]);
'@; docker compose exec -T app php artisan tinker --execute="$code"
```

Docelowo warto zamienic ten pomiar na mala komende artisan:

```bash
php artisan public-explanations:status
php artisan public-explanations:status --done
php artisan public-explanations:status --missing
php artisan public-explanations:status --topic=piesi-i-przejscia
php artisan public-explanations:status --export=storage/app/public-question-explanations-status.csv
```

Ta komenda powinna uzywac tych samych definicji: calosc z publicznego katalogu pytan, zrobione tylko przez `published`, a brakujace przez roznice `external_id`.

## 10.3 QA batcha przed merge

Kazdy batch publicznych wyjasnien powinien przejsc minimalna kontrole:

1. `php -l` dla zmienionego seedera.
2. `pint` dla zmienionych plikow.
3. Test seedera, publicznej strony pytania, sitemap i warstwy `/przepisy`.
4. Kontrola dlugosci 80-150 slow.
5. Kontrola, ze `body` nie jest doslowna kopia `questions.explanation`.
6. Kontrola, ze nie ma dwoch identycznych `body` w batchu.
7. Smoke test przynajmniej jednego publicznego URL-a z batcha.

Komendy uzyte dla batcha 1:

```powershell
docker compose exec -T app php -l database/seeders/PublicQuestionExplanationSeeder.php
docker compose exec -T app ./vendor/bin/pint database/seeders/PublicQuestionExplanationSeeder.php tests/Feature/PublicQuestionExplanationSeederTest.php
docker compose exec -T app php artisan test tests/Feature/PublicQuestionExplanationSeederTest.php tests/Feature/PublicQuestionDatabasePageTest.php tests/Feature/Public/SeoSitemapGenerationTest.php
docker compose exec -T app php artisan test tests/Feature/Public/LegalTrustLayerMvpTest.php
git diff --check
```

## 11. Ryzyka

| Ryzyko | Jak ograniczamy |
| --- | --- |
| Przypadkowa zmiana tresci w `/nauka` | Osobna tabela i test payloadu nauki. |
| Duplikacja tresci miedzy pytaniami | Batch QA, test seedera i unikalne omowienia per pytanie. |
| Pomieszanie SEO z podstawa prawna | Oddzielne sekcje: `Wyjasnienie` oraz `Uzasadnienie prawne`. |
| Niezweryfikowane twierdzenia prawne | Prawne twierdzenia w opcjonalnym `public_note` tylko po review. |
| Zbyt szybkie skalowanie | Najpierw pytanie `99`, potem male batch'e. |
| Brak kontroli postepu | Liczyc calosc/zrobione/zostalo po kanonicznym `external_id`, nie po surowych rekordach `questions`. |
| Schema regression | `acceptedAnswer` zawsze ma byc spojne z widoczna sekcja `Wyjasnienie`. |

## 12. Rollback

Rollback jest prosty, bo nowa warstwa nie zmienia danych produktowych:

1. przestac uzywac `question_public_explanations` w publicznym `Wyjasnieniu`,
2. ustawic rekordy na `archived` albo `draft`,
3. cofnac seeder/dane publiczne, jesli trzeba,
4. nie trzeba odtwarzac `questions.explanation`, bo nie bylo ruszane.

## 13. Najblizszy krok

Stan po batchu 1:

- fundament danych i publiczny render sa gotowe,
- pytanie `99` zostalo wzorcem jakosci,
- pierwszy batch 16 pytan powiazanych z `/przepisy` jest w seederze,
- test seedera pilnuje, zeby batch byl opublikowany, mial daty review, miescil sie w 80-150 slowach i nie byl traktowany jak `questions.explanation`.

Najbezpieczniejszy kolejny sprint:

1. dodac komende raportujaca `public-explanations:status`, zeby status byl dostepny bez tinker/SQL,
2. pracowac recznie na publicznych URL-ach pytan przez przyciski `Edytuj` i `Zapisz`,
3. po kazdej serii recznych zapisow przeliczyc `calosc`, `zrobione`, `zostalo`,
4. smoke testowac kilka publicznych URL-i po zapisie,
5. dopiero po ustabilizowaniu recznego standardu zdecydowac, czy batch 1 zostaje opublikowany, wraca do review, czy jest przepisywany recznie.

Dla osobnej warstwy `Uzasadnienia prawnego` status sprawdzamy komenda:

```bash
php artisan legal-basis:coverage
```

Ta komenda nie zmienia danych. Pokazuje, ile publicznych pytan kanonicznych ma zweryfikowana podstawe prawna, ile zostalo bez podstawy, ile ma opublikowany artykul `/przepisy` i gdzie wskazanie jest jeszcze zbyt szerokie.

Ostatni lokalny snapshot dla `Uzasadnienia prawnego`:

| Data pomiaru | Pytania kanoniczne | Zweryfikowana podstawa | Zostalo | Katalog przepisow | Uwagi |
| --- | ---: | ---: | ---: | ---: | --- |
| 2026-06-16 | 3151 | 53 | 3098 | 1270 jednostek, 1270 z `canonical_path`, 1270 `verified` | Po lokalnym audycie ELI HTML dla `Prawo o ruchu drogowym`; 1257 jednostek promowano z `needs_review` do `verified`, temat opcjonalny ma 0 obecnych brakow. |

Co nam to daje operacyjnie:

1. Mamy licznik postepu dla podstaw prawnych tak jak dla publicznych wyjasnien.
2. Wiemy, ze nastepny development to importer/katalog przepisow, a nie deploy obecnego selecta.
3. Po kazdym imporcie albo batchu recznym odpalamy raport i porownujemy `zrobione` oraz `zostalo`.
4. Wyszukiwarka przepisu, opcjonalny temat i audyt PoRD sa juz lokalnie zrobione; teraz trzeba recznie przypisywac podstawy do pytan i mierzyc postep przed decyzja o deployu.

Aktualny stan kolejnego etapu:

- dodana jest hierarchia `legal_units`: `parent_legal_unit_id`, `canonical_path`, `effective_from`,
- dodana jest komenda `legal-basis:import-units` do idempotentnego importu manifestu,
- domyslnie import robi preview, a zapis wymaga `--write`,
- dodana jest komenda `legal-basis:build-units-manifest`, ktora buduje manifest z oficjalnego ELI `text.html`,
- lokalnie wygenerowano i zaimportowano 1270 jednostek dla `Prawo o ruchu drogowym`,
- po `legal-basis:audit-units --write` wszystkie 1270 jednostek PoRD jest lokalnie `verified`,
- dodany jest endpoint `/api/v1/admin/legal-units/search` oraz wyszukiwarka przepisu w inline edytorze,
- `legal_topic_id` dla `question_legal_references` jest opcjonalny, a UI pokazuje `Bez tematu`,
- dodana i uruchomiona jest komenda `legal-basis:audit-units`, ktora porownuje `legal_units` z oficjalnym `text.html` i z `--write` promuje czyste jednostki do `verified`,
- publiczny blok `Uzasadnienie prawne` pokazuje teraz takze `official_excerpt`, czyli tresc wybranej jednostki przepisu, jezeli katalog ja posiada; `Opis prawny` jest opcjonalny,
- lokalny smoke test potwierdzil wybor `Prawo o ruchu drogowym art. 26 ust. 6` bez zapisu danych,
- testy feature potwierdzily zapis podstawy prawnej bez tematu i publiczny render takiego rekordu,
- nastepny krok to reczne przypisywanie podstaw prawnych do pytan przez inline edytor i kontrola `legal-basis:coverage`, nie schema i nie deploy.
