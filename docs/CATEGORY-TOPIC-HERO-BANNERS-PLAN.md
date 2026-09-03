# Zdjecia dzialow per kategoria - plan bezpiecznego wdrozenia

Status: MVP gotowe do wdrozenia produkcyjnego
Branch: `codex/topic-hero-banners-docs`
Data: 2026-06-09

Aktualny etap: dodano model danych, resolver, payload `/nauka`, frontendowy fallback w banerze, panel admina dla zdjec dzialow, kompaktowy layout konfiguratora nauki oraz testy regresyjne. Release zostal przygotowany do commita, merge i deployu.

## Cel

Chcemy, aby baner "Twoja aktualna sesja" na `/nauka` mogl pokazywac inne zdjecie dla kazdego dzialu. Docelowo ten sam dzial techniczny moze miec inne zdjecie w roznych kategoriach prawa jazdy, np.:

- kategoria B + `warning_signs` -> zdjecie ostrzezenia w ruchu samochodem osobowym,
- kategoria C + `warning_signs` -> zdjecie ostrzezenia w kontekscie pojazdu ciezarowego,
- kategoria AM + `warning_signs` -> zdjecie bardziej pasujace do motoroweru.

Najwazniejsza zasada: to jest tylko warstwa prezentacyjna. Nie zmieniamy przypisania pytan, licznikow, postepow, kolejnosci nauki ani mechaniki startu sesji.

## Aktualny stan po skanie kodu

### `/nauka`

Glowne pliki:

- `app/Http/Controllers/SessionPageController.php`
- `app/Support/StudyTopicGroupsService.php`
- `resources/js/Pages/Session/Index.vue`

Obecny przeplyw:

1. `SessionPageController` pobiera `group_options` z `StudyTopicGroupsService`.
2. `StudyTopicGroupsService` buduje liste widocznych dzialow dla aktywnej kategorii.
3. `resources/js/Pages/Session/Index.vue` wybiera `desktopHeroTopic` z:
   - aktualnie wybranego dzialu,
   - pierwszego nieprzerobionego dzialu,
   - pierwszego dzialu z listy.
4. Obraz banera jest obecnie jednym statycznym importem:
   - `resources/images/session/learning-dashboard-hero.png`

Aktualny kontrakt `GroupOption` w Vue zawiera:

- `id`
- `key`
- `label`
- `questions_count`
- `counts`

Nie zawiera jeszcze zadnych danych obrazu.

### Dzialy techniczne

Model:

- `app/Models/QuestionTopic.php`

Tabela:

- `question_topics`

Obecne pola:

- `key`
- `name`
- `description`
- `sort_order`
- `is_active`

To jest globalny dzial techniczny. Nie powinien byc zmieniany w celu dostosowania samego widoku dla kategorii.

### Customowe nazwy dzialow per kategoria

W ostatnim etapie dodalismy:

- `question_topic_category_labels`
- `app/Models/QuestionTopicCategoryLabel.php`
- `app/Support/QuestionTopicLabelResolver.php`
- relation manager w `LicenseCategoryResource`

Ta warstwa zmienia tylko etykiete dzialu dla pary:

`license_category_id + question_topic_id`

To potwierdza, ze mamy juz sprawdzony kierunek dla danych per kategoria. Zdjecia powinny korzystac z tej samej osi biznesowej, ale nie musza byc wymuszane przez rekord etykiety.

### Obrazki i URL-e mediow

Istniejacy resolver:

- `app/Support/MediaUrlResolver.php`

Istotne cechy:

- przyjmuje sciezke wzgledna albo absolutny URL,
- dla plikow w `public/` dodaje cache-busting po `filemtime`,
- dla dyskow storage korzysta z `Storage::disk(...)->url(...)`,
- normalizuje URL przez `PublicUrlResolver`.

Konfiguracja mediow:

- `config/media.php`
- `config/filesystems.php`

Istniejacy wzorzec uploadu obrazow w adminie:

- `app/Filament/Resources/Questions/Schemas/QuestionForm.php`
- `FileUpload::make(...)`
- `->image()`
- `->disk((string) config('media.public_disk', 'public'))`
- `->visibility('public')`
- `->acceptedFileTypes(config('media.allowed_mime_types.image', []))`
- `->maxSize(...)`

Ten wzorzec mozna wykorzystac dla zdjec banerow dzialow.

## Decyzja architektoniczna

Rekomendacja: rozdzielic:

1. globalne domyslne zdjecie dzialu,
2. nadpisanie zdjecia dla konkretnej kategorii.

## Zasada dziedziczenia obrazow

Kazda kategoria ma miec efektywny obraz dla widocznego dzialu, ale nie oznacza to, ze kazda para `kategoria + dzial` musi miec osobny rekord obrazu.

Docelowy model:

1. Dzial ma obraz globalny, jesli jego sens wizualny jest taki sam w kazdej kategorii.
2. Kategoria dostaje wlasny obraz tylko wtedy, gdy kontekst tej kategorii istotnie zmienia wizualna interpretacje dzialu.
3. Jesli kategoria nie ma wlasnego nadpisania, automatycznie dziedziczy obraz globalny dzialu.
4. Jesli nie ma ani nadpisania kategorii, ani globalnego obrazu dzialu, frontend uzywa obecnego technicznego fallbacku `learning-dashboard-hero.png`.

Przyklady:

- `warning_signs` / Znaki ostrzegawcze - zwykle jeden obraz globalny wystarczy, bo znaki ostrzegawcze sa takie same dla A, B, C, D itd.
- `prohibition_and_mandatory_signs` / Znaki zakazu i nakazu - zwykle jeden obraz globalny wystarczy.
- `road_markings` / Oznakowanie poziome - prawdopodobnie jeden obraz globalny wystarczy, chyba ze konkretna kategoria wymaga innego kontekstu.
- `speed_limits` / Predkosci i ograniczenia - moze miec inne zdjecie dla B, C, D lub T, bo kontekst pojazdu i ograniczen jest inny.
- `vehicle_load_and_passenger_safety` / Ladunek i przewozone osoby - dobry kandydat na osobne zdjecia dla B, C i D.
- `driving_technique` / Technika kierowania - dobry kandydat na osobne zdjecia dla motocykli, samochodow osobowych, ciezarowych i autobusow.
- `mechanical_aspects_of_safety` / Mechanika i bezpieczenstwo pojazdu - dobry kandydat na osobne zdjecia per typ pojazdu.

Nie rekomenduje trzymania zdjec w samym Vue jako duzej mapy importow. Powody:

- admin nie bedzie mogl tym zarzadzac,
- dla B/C trzeba by hardkodowac warunki po stronie frontendu,
- kazda zmiana zdjecia wymagalaby buildu frontendu,
- latwo stworzyc dluga i krucha liste importow.

Nie rekomenduje tez wymuszania zdjec przez `question_topic_category_labels`, bo wtedy admin musialby tworzyc customowa nazwe tylko po to, zeby ustawic zdjecie. Nazwa i obraz sa powiazane prezentacyjnie, ale powinny byc niezalezne.

## Proponowany model danych

### Globalny fallback na `question_topics`

Migracja rozszerzajaca `question_topics`:

| Pole | Typ | Cel |
| --- | --- | --- |
| `hero_image_path` | string nullable | Domyslna sciezka obrazu dla dzialu. |
| `hero_image_alt` | string nullable | Opis techniczny/SEO na przyszlosc. W obecnym banerze obraz moze zostac dekoracyjny. |
| `hero_image_position` | string nullable | Pozycja `object-position`, np. `center`, `right center`, `55% center`. |

Globalny fallback pozwala szybko pokryc wszystkie dzialy jednym zestawem obrazow.

To jest warstwa bazowa. Powinna pokrywac wszystkie dzialy, ktore maja uniwersalny sens niezaleznie od kategorii. Dzieki temu panel admina nie bedzie wypelniony niepotrzebnymi duplikatami tych samych zdjec.

### Nadpisania per kategoria

Nowa tabela:

`question_topic_category_heroes`

Pola:

| Pole | Typ | Cel |
| --- | --- | --- |
| `id` | bigint | Klucz rekordu. |
| `license_category_id` | foreign id | Kategoria prawa jazdy. |
| `question_topic_id` | foreign id | Globalny dzial. |
| `hero_image_path` | string | Sciezka obrazu dla tej kategorii i dzialu. |
| `hero_image_alt` | string nullable | Opis obrazu, przydatny dla przyszlych publicznych uzyc. |
| `hero_image_position` | string nullable | Kadrowanie obrazu w banerze. |
| `admin_note` | text nullable | Notatka, dlaczego kategoria ma wlasny obraz. |
| `is_active` | boolean | Czy nadpisanie jest aktywne. |
| `created_by` | foreign id nullable | Kto utworzyl wpis. |
| `updated_by` | foreign id nullable | Kto ostatnio zmienil wpis. |
| `created_at`, `updated_at` | timestamps | Historia techniczna. |

Indeksy:

- unique: `license_category_id + question_topic_id`
- index: `question_topic_id`
- index: `is_active`

Nadpisania dodajemy tylko tam, gdzie rzeczywiscie potrzebujemy innego obrazu dla danej kategorii. Nie tworzymy osobnych rekordow dla wszystkich kategorii na sile.

Fallback:

1. Aktywny rekord z `question_topic_category_heroes` dla pary `category + topic`.
2. Globalne pola z `question_topics`.
3. Obecny statyczny obraz `learning-dashboard-hero.png`.

## Proponowany resolver

Nazwa:

`QuestionTopicHeroResolver`

Odpowiedzialnosc:

- przyjmuje `LicenseCategory` i kolekcje `QuestionTopic`,
- pobiera nadpisania kategorii jednym zapytaniem,
- pobiera globalne pola z `QuestionTopic`,
- zwraca dane obrazu per `question_topic_id`,
- nie sprawdza ani nie zmienia postepow uzytkownika,
- nie zmienia zadnych danych pytan.

Proponowany wynik resolvera:

```php
[
    10 => [
        'image_url' => 'https://prawkonaraz.pl/storage/study/topic-heroes/b/warning-signs.webp',
        'image_path' => 'study/topic-heroes/b/warning-signs.webp',
        'image_alt' => 'Znaki ostrzegawcze w kategorii B',
        'image_position' => 'center',
        'source' => 'category',
    ],
]
```

`source` jest opcjonalne, ale przydatne w testach i debugowaniu.

## Zmiana kontraktu `/nauka`

`StudyTopicGroupsService` powinien dolaczyc do kazdego `GroupOption`:

- `hero_image_url`
- `hero_image_alt`
- `hero_image_position`
- opcjonalnie `hero_image_source`

Nowy kontrakt:

```ts
interface GroupOption {
    id: number;
    key: string;
    label: string;
    questions_count: number;
    counts: Record<string, number>;
    hero_image_url?: string | null;
    hero_image_alt?: string | null;
    hero_image_position?: string | null;
}
```

Vue powinno uzyc:

1. `desktopHeroTopic?.hero_image_url`,
2. fallbacku `learningDashboardHero`.

Obraz w banerze pozostaje dekoracyjny:

- `alt=""`
- `aria-hidden="true"`

To jest bezpieczne, bo tekst dzialu i sens banera sa obok obrazu. `hero_image_alt` zapisujemy z mysla o przyszlych publicznych stronach albo adminowym podgladzie, nie jako konieczny element obecnego banera.

## Panel admina

### Globalne zdjecia dzialow

Rekomendowane miejsce:

`/admin/question-topics`

Nowy zasob Filament:

- `app/Filament/Resources/QuestionTopics/QuestionTopicResource.php`

Cel:

- ustawianie globalnego zdjecia dzialu,
- ustawianie globalnego opisu obrazu,
- ustawianie globalnego kadrowania,
- podglad liczby pytan i liczby wyjatkow kategorii.
- zapis audit logu przy zmianie globalnego zdjecia.

Ten widok nie sluzy do zmiany klasyfikacji pytan. Pola techniczne `key`, `name` i `description` sa pokazane jako kontekst, ale w formularzu globalnego zdjecia nie powinny byc edytowane.

Globalne zdjecie ustawiamy tam, gdzie sens obrazu jest taki sam dla wszystkich kategorii, np. znaki ostrzegawcze.

### Zdjecia per kategoria

Rekomendowane miejsce:

`/admin/license-categories/{id}/edit`

Admin wchodzi przez:

1. `/admin/license-categories`
2. wybiera konkretna kategorie, np. B albo C,
3. przechodzi do edycji rekordu,
4. zarzadza zdjeciami dzialow w osobnej sekcji relacji.

Zasob Filament:

- `app/Filament/Resources/LicenseCategories/LicenseCategoryResource.php`

Ten zasob ma juz podpięty relation manager:

- `Nazwy dzialow w tej kategorii`
- plik: `app/Filament/Resources/LicenseCategories/RelationManagers/QuestionTopicCategoryLabelsRelationManager.php`

Nowy relation manager:

`Zdjecia dzialow w tej kategorii`

Proponowany plik:

- `app/Filament/Resources/LicenseCategories/RelationManagers/QuestionTopicCategoryHeroesRelationManager.php`

Dlaczego osobny relation manager:

- nie mieszamy nazwy dzialu ze zdjeciem,
- admin moze ustawic zdjecie dla B/C bez zmiany etykiety,
- latwiej audytowac zmiany obrazow,
- mniejsze ryzyko przypadkowego wplywu na resolver nazw.
- admin moze miec kategorie, ktora uzywa domyslnej nazwy dzialu, ale ma wlasne zdjecie dla tego dzialu.

Nie dodajemy zdjec do `QuestionTopicCategoryLabelsRelationManager`, poniewaz nazwa i obraz sa dwiema roznymi decyzjami:

- nazwa odpowiada za tekst widoczny dla kursanta,
- obraz odpowiada za kontekst wizualny banera.

Laczenie ich wymuszaloby tworzenie customowej nazwy tylko po to, zeby ustawic zdjecie, co byloby mylace i trudniejsze w utrzymaniu.

Formularz:

- `question_topic_id` - dzial techniczny, po utworzeniu nieedytowalny,
- `hero_image_path` - upload obrazu,
- `hero_image_alt` - opis,
- `hero_image_position` - wybor kadrowania,
- `is_active`,
- `admin_note`.

Admin powinien widziec, czy dana kategoria korzysta z:

- obrazu wlasnego dla kategorii,
- obrazu globalnego dzialu,
- technicznego fallbacku.

To jest wazne, bo pozwoli szybko ocenic, ktore dzialy wymagaja jeszcze dedykowanych zdjec, a gdzie swiadomie korzystamy z jednego wspolnego obrazu.

Proponowane pozycje kadrowania:

- `center`
- `left center`
- `right center`
- `center top`
- `center bottom`
- opcjonalnie custom text input dla zaawansowanych, np. `62% center`.

Upload:

- dysk: `config('media.public_disk', 'public')`,
- katalog: `study/topic-heroes/{category_code}`,
- typy: `config('media.allowed_mime_types.image')`,
- limit: `config('media.max_bytes.image')`.

## Zasady assetow

Rekomendowany format:

- WebP albo JPG,
- proporcja okolo `16:7` albo `2:1`,
- minimalnie okolo `1200 x 520`,
- bez waznego tekstu na obrazie,
- glowny obiekt po prawej stronie, bo lewa strona banera ma gradient i tekst.

Wazne:

- obraz ma byc tlem emocjonalno-kontekstowym, nie zrodlem informacji egzaminacyjnej,
- brak obrazu nie moze wysypac widoku,
- jesli obraz nie pasuje kadrowaniem, poprawiamy `hero_image_position`, nie layout calej strony.

## Miejsca do zmiany w implementacji

### Faza 1 - dokumentacja i migracje

- `docs/CATEGORY-TOPIC-HERO-BANNERS-PLAN.md`
- migracja dodajaca pola globalne do `question_topics`
- migracja tworzaca `question_topic_category_heroes`
- model `QuestionTopicCategoryHero`
- relacje w:
  - `LicenseCategory`
  - `QuestionTopic`

### Faza 2 - resolver i testy

- `app/Support/QuestionTopicHeroResolver.php`
- testy:
  - nadpisanie kategorii wygrywa z globalnym obrazem,
  - nieaktywne nadpisanie jest ignorowane,
  - globalny obraz dziala jako fallback,
  - brak obrazu zwraca `null`, a Vue uzywa statycznego fallbacku.

### Faza 3 - payload `/nauka`

- `app/Support/StudyTopicGroupsService.php`
- test `SessionPageTest` sprawdzajacy, ze `group_options` zawiera `hero_image_url` dla dzialu.

### Faza 4 - frontend banera

- `resources/js/Pages/Session/Index.vue`
- dopisanie pol w `GroupOption`,
- computed dla URL obrazu i pozycji kadrowania,
- podmiana `:src="learningDashboardHero"` na dynamiczny URL z fallbackiem,
- zachowanie obecnego przycisku i startu sesji.

Uwaga: w `Index.vue` sa co najmniej dwa miejsca, ktore korzystaja ze statycznego `learningDashboardHero`. Trzeba swiadomie zdecydowac, czy nowy obraz ma dotyczyc tylko glownego desktopowego banera, czy rowniez drugiego wariantu/sekcji w tym komponencie.

### Faza 5 - admin

- nowy zasob `QuestionTopicResource` do globalnych zdjec dzialow,
- nowy relation manager przy `LicenseCategoryResource` do wyjatkow per kategoria,
- audit log dla globalnych zmian zdjec dzialow,
- audit log dla create/update/delete wyjatkow per kategoria,
- podglad obrazu w tabeli albo infolist,
- bez bulk delete albo z osobnym audytem.

### Faza 6 - build i testy manualne

- `php artisan test tests/Feature/SessionPageTest.php`
- test resolvera,
- `npm run build`,
- sprawdzenie `/nauka` dla:
  - kategorii B z globalnym obrazem,
  - kategorii B z category override,
  - kategorii C z innym override,
  - kategorii bez obrazu,
  - braku dostepu / przekierowania do zakupu.

## Czego nie dotykamy

Nie zmieniamy:

- `questions.question_topic_id`,
- `question_topics.key`,
- klasyfikatora dzialow,
- kolejnosci pytan,
- rekordow postepu,
- logiki liczenia `questions_count`,
- startu sesji klasycznej,
- Zen mode,
- egzaminu,
- `/nauka/teraz`,
- odtwarzacza pytan,
- wideo, adnotacji i timerow,
- publicznych sitemap i SEO.

## Ryzyka i zabezpieczenia

### Ryzyko: brak pliku albo bledny URL

Zabezpieczenie:

- resolver zwraca `null`, jesli nie da sie ustalic obrazu,
- frontend zawsze ma fallback `learning-dashboard-hero.png`,
- admin powinien uzywac uploadu zamiast recznego wpisywania URL.

### Ryzyko: pomieszanie nazw i obrazow

Zabezpieczenie:

- nazwy zostaja w `QuestionTopicLabelResolver`,
- obrazy ida przez osobny `QuestionTopicHeroResolver`,
- osobne testy dla obu resolverow.

### Ryzyko: osobne zdjecie dla B/C przypadkowo zmieni inne kategorie

Zabezpieczenie:

- unikalny rekord per `license_category_id + question_topic_id`,
- fallback globalny tylko wtedy, gdy brak aktywnego nadpisania kategorii,
- testy na dwie kategorie z tym samym topicem.

### Ryzyko: frontend zacznie zalezec od pola, ktore nie zawsze istnieje

Zabezpieczenie:

- pola `hero_*` sa opcjonalne w TypeScript,
- computed zawsze ma fallback,
- backend moze wdrazac dane stopniowo.

## Rekomendowany MVP

Najbezpieczniejszy pierwszy krok implementacyjny:

1. Dodac model danych i resolver.
2. Wyswietlac dynamiczny obraz tylko w banerze "Twoja aktualna sesja" na desktopowym `/nauka`.
3. Zostawic wszystkie pozostale miejsca ze statycznym obrazem.
4. Dodac jeden test category override dla B i C.
5. Dopiero po potwierdzeniu lokalnym dodac adminowy upload i pelniejszy zestaw obrazow.

Kolejnosc uzupelniania obrazow:

1. Najpierw ustawic globalne obrazy dla dzialow uniwersalnych, szczegolnie znakow i ogolnych zasad.
2. Potem dodac nadpisania kategorii dla dzialow specjalistycznych, gdzie pojazd i kontekst zmieniaja przekaz.
3. Na koncu przejrzec kategorie bez nadpisan i potwierdzic, czy dziedziczenie globalnego obrazu jest swiadome.

## Status prac

- [x] Przeskanowano `/nauka`, `StudyTopicGroupsService`, `SessionPageController` i `Index.vue`.
- [x] Przeskanowano obecny model `QuestionTopic`.
- [x] Przeskanowano customowe etykiety `question_topic_category_labels`.
- [x] Przeskanowano wzorzec uploadu obrazow w adminie.
- [x] Potwierdzono, ze zmiana moze byc prezentacyjna i odseparowana od logiki nauki.
- [x] Implementacja migracji.
- [x] Implementacja resolvera obrazow.
- [x] Podpiecie payloadu `/nauka`.
- [x] Podpiecie frontendu dla glownego desktopowego banera.
- [x] Panel admina do globalnych zdjec dzialow.
- [x] Panel admina przy edycji kategorii do wyjatkow per kategoria.
- [x] Audit log dla globalnych zmian zdjec dzialow.
- [x] Audit log dla wyjatkow zdjec per kategoria.
- [x] Testy resolvera obrazow.
- [x] Testy regresyjne payloadu `/nauka`.
- [x] Build frontendu.
- [ ] Commit.
- [ ] Deploy.

## Weryfikacja lokalna

Wykonano:

- `docker compose exec -T app php artisan migrate`
- `docker compose exec -T app php artisan test tests/Feature/Support/QuestionTopicHeroResolverTest.php tests/Feature/SessionPageTest.php`
- `docker compose exec -T app php artisan test tests/Feature/Support/QuestionTopicLabelResolverTest.php tests/Feature/QuestionLearningOrderTest.php tests/Feature/PjmStudySessionTest.php`
- `docker compose exec -T app ./vendor/bin/pint --dirty`
- `npm run build`
- `docker compose exec -T app php artisan route:list --path=admin/question-topics`
- `git diff --check`

Wynik:

- resolver obrazow i payload `/nauka`: 30 testow, 457 asercji,
- nazwy dzialow, kolejność pytań i PJM: 26 testow, 213 asercji,
- build frontendu zakonczony powodzeniem,
- routing `/admin/question-topics` zarejestrowany poprawnie.
