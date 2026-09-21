<?php

namespace Database\Seeders;

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Support\NewsroomBodyContract;
use App\Support\NewsroomTaxonomyContract;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class NewsroomEditorialLaunchSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (NewsroomTaxonomyContract::categories() as $category) {
                ContentCategory::query()->updateOrCreate(['slug' => $category['slug']], $category);
            }

            $jakub = ContentAuthor::query()->published()->where('slug', 'jakub-wisniewski')->first();
            $katarzyna = ContentAuthor::query()->published()->where('slug', 'katarzyna-wisniewska')->first();

            if (! $jakub || ! $katarzyna) {
                throw new RuntimeException('Newsroom launch requires the published Jakub and Katarzyna author profiles.');
            }

            foreach ($this->articles() as $index => $definition) {
                $author = $definition['author'] === 'jakub' ? $jakub : $katarzyna;
                $category = ContentCategory::query()->where('slug', $definition['category'])->firstOrFail();
                $existing = ContentArticle::query()->where('slug', $definition['slug'])->first();
                $media = $existing?->hero_image_path
                    ? ['path' => $existing->hero_image_path, 'width' => $existing->hero_image_width, 'height' => $existing->hero_image_height]
                    : $this->storeHero($definition['image']);
                // Keep launch records safely in the past across PHP/PostgreSQL timezone settings.
                $publishedAt = now('UTC')->subHours(4)->addMinutes($index * 5);

                $article = ContentArticle::query()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        'type' => ContentArticleType::Explainer->value,
                        'category_id' => $category->getKey(),
                        'author_id' => $author->getKey(),
                        'reviewer_id' => $definition['author'] === 'jakub' ? $katarzyna->getKey() : $jakub->getKey(),
                        'origin_type' => ContentArticleOriginType::Compiled->value,
                        'title' => $definition['title'],
                        'lead' => $definition['lead'],
                        'body_blocks' => $this->body($definition['sections']),
                        'body_schema_version' => NewsroomBodyContract::CURRENT_SCHEMA_VERSION,
                        'key_points' => $definition['key_points'],
                        'regulatory_status' => $definition['regulatory'] ? ContentArticleRegulatoryStatus::InForce->value : ContentArticleRegulatoryStatus::NotApplicable->value,
                        'applies_to' => $definition['applies_to'],
                        'exam_impact' => $definition['exam_impact'],
                        'workflow_status' => ContentArticleWorkflowStatus::Published->value,
                        'published_at' => $publishedAt,
                        'first_published_at' => $publishedAt,
                        'reviewed_at' => $publishedAt,
                        'is_featured' => $index < 3,
                        'editorial_priority' => 100 - $index,
                        'hero_image_path' => $media['path'],
                        'hero_image_alt' => $definition['alt'],
                        'hero_image_width' => $media['width'],
                        'hero_image_height' => $media['height'],
                        'hero_image_caption' => $definition['caption'],
                        'hero_focal_x' => 0.5000,
                        'hero_focal_y' => 0.5000,
                        'og_image_path' => $media['path'],
                        'og_image_alt' => $definition['alt'],
                        'og_image_width' => $media['width'],
                        'og_image_height' => $media['height'],
                        'image_credit' => 'PrawkoNaRaz.pl',
                        'image_license_note' => 'Materiał własny PrawkoNaRaz.pl.',
                        'seo_title' => $definition['seo_title'],
                        'seo_description' => $definition['seo_description'],
                        'robots' => 'index,follow',
                        'source_checked_at' => $publishedAt,
                        'freshness_review_due_at' => now('UTC')->addMonths(3),
                        'last_substantive_update_at' => $publishedAt,
                        'public_state_changed_at' => $publishedAt,
                    ],
                );

                $article->sources()->delete();
                foreach ($definition['sources'] as $sourceIndex => $source) {
                    $article->sources()->create([
                        'source_type' => $source['type'],
                        'publisher' => $source['publisher'],
                        'title' => $source['title'],
                        'url' => $source['url'],
                        'accessed_at' => now(),
                        'is_primary' => $sourceIndex === 0,
                        'is_official' => true,
                        'is_publicly_cited' => true,
                        'sort_order' => $sourceIndex,
                    ]);
                }
            }
        });
    }

    /** @param list<array{heading:string,paragraphs:list<string>,bullets?:list<string>}> $sections */
    private function body(array $sections): array
    {
        $content = [];
        foreach ($sections as $section) {
            $content[] = ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $section['heading']]]];
            foreach ($section['paragraphs'] as $paragraph) {
                $content[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $paragraph]]];
            }
            if ($section['bullets'] ?? []) {
                $content[] = ['type' => 'bulletList', 'content' => array_map(
                    fn (string $item): array => ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $item]]]]],
                    $section['bullets'],
                )];
            }
        }

        return NewsroomBodyContract::normalize([
            ['type' => NewsroomBodyContract::BLOCK_RICH_TEXT, 'data' => ['content' => ['type' => 'doc', 'content' => $content]]],
            ['type' => NewsroomBodyContract::BLOCK_PRODUCT_CTA, 'data' => ['kind' => 'learning']],
        ]);
    }

    /** @return array{path:string,width:int,height:int} */
    private function storeHero(string $source): array
    {
        $absolute = resource_path($source);
        $info = @getimagesize($absolute);
        if (! is_array($info)) {
            throw new RuntimeException("Cannot inspect newsroom launch image [{$source}].");
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $path = 'newsroom/articles/source/'.Str::lower((string) Str::ulid()).'.'.$extension;
        Storage::disk((string) config('media.newsroom_disk', 'public'))->put($path, file_get_contents($absolute));

        return ['path' => $path, 'width' => (int) $info[0], 'height' => (int) $info[1]];
    }

    private function official(string $title, string $url, string $type = 'official'): array
    {
        return ['type' => $type, 'publisher' => str_contains($url, 'eli.gov.pl') ? 'Elektroniczny Dziennik Ustaw' : 'Ministerstwo Infrastruktury', 'title' => $title, 'url' => $url];
    }

    /** @return list<array<string,mixed>> */
    private function articles(): array
    {
        $govLicence = $this->official('Jak uzyskać prawo jazdy?', 'https://www.gov.pl/web/infrastruktura/jak-uzyskac-prawo-jazdy');
        $govDriving = $this->official('Prawo jazdy — informacje Ministerstwa Infrastruktury', 'https://www.gov.pl/web/infrastruktura/prawo-jazdy');
        $trafficLaw = $this->official('Ustawa — Prawo o ruchu drogowym, tekst jednolity', 'https://eli.gov.pl/api/acts/DU/2024/1251/text.html', ContentArticleSourceType::Legislation->value);

        return [
            [
                'category' => 'prawo-jazdy', 'author' => 'jakub',
                'slug' => 'jak-zdobyc-prawo-jazdy-krok-po-kroku',
                'title' => 'Jak zdobyć prawo jazdy krok po kroku: PKK, kurs i egzaminy',
                'lead' => 'Od badania lekarskiego i numeru PKK po egzamin w WORD i odbiór dokumentu — porządkujemy całą drogę do prawa jazdy w jednym praktycznym planie.',
                'key_points' => ['Najpierw skompletuj dokumenty i uzyskaj numer PKK.', 'Wybierz tryb szkolenia i przygotuj się do teorii na aktualnych materiałach.', 'Po zdanych egzaminach sprawdź status dokumentu i odbierz prawo jazdy.'],
                'sections' => [
                    ['heading' => '1. Zacznij od Profilu Kandydata na Kierowcę', 'paragraphs' => ['PKK to elektroniczny profil, który porządkuje dane kandydata i jest potrzebny w dalszych etapach szkolenia oraz egzaminowania. Zanim złożysz wniosek, przygotuj fotografię, dokument tożsamości i wymagane orzeczenie lekarskie.'], 'bullets' => ['Sprawdź wymagania dla wybranej kategorii.', 'Zadbaj o aktualne i czytelne dokumenty.', 'Zachowaj numer PKK — będzie potrzebny w OSK i WORD.']],
                    ['heading' => '2. Wybierz kurs i zaplanuj teorię', 'paragraphs' => ['Dobra nauka nie polega na jednorazowym zapamiętaniu odpowiedzi. Najlepszy efekt daje połączenie znajomości przepisów, regularnych krótkich sesji i analizy własnych błędów.', 'W PrawkoNaRaz możesz pracować z oficjalną bazą pytań, wracać do trudnych zagadnień i sprawdzać wyjaśnienia.']],
                    ['heading' => '3. Egzamin teoretyczny i praktyczny', 'paragraphs' => ['Po przygotowaniu zapisz się na egzamin w wojewódzkim ośrodku ruchu drogowego. Po zaliczeniu teorii przychodzi czas na część praktyczną właściwą dla kategorii. Na egzamin zabierz dokument tożsamości i przyjdź z wyprzedzeniem.']],
                    ['heading' => '4. Po zdanym egzaminie', 'paragraphs' => ['WORD przekazuje informację o wyniku do urzędu. Po spełnieniu wymaganych formalności możesz śledzić status dokumentu. Nie rozpoczynaj samodzielnej jazdy, dopóki nie masz ważnego uprawnienia.']],
                ],
                'image' => 'images/home/hero-main.webp', 'alt' => 'Kursant przygotowujący się do kolejnych etapów zdobycia prawa jazdy', 'caption' => 'Droga do prawa jazdy jest łatwiejsza, gdy każdy etap ma swoje miejsce w planie.',
                'seo_title' => 'Jak zdobyć prawo jazdy? PKK, kurs i egzaminy krok po kroku', 'seo_description' => 'Sprawdź, jak zdobyć prawo jazdy: dokumenty, PKK, kurs, egzamin teoretyczny i praktyczny oraz odbiór dokumentu.',
                'regulatory' => true, 'applies_to' => 'Kandydaci na kierowców wszystkich kategorii.', 'exam_impact' => 'Porządkuje formalności przed rozpoczęciem szkolenia i egzaminów.', 'sources' => [$govLicence],
            ],
            [
                'category' => 'egzaminy', 'author' => 'katarzyna',
                'slug' => 'egzamin-teoretyczny-32-pytania-25-minut',
                'title' => 'Egzamin teoretyczny na prawo jazdy: 32 pytania i 25 minut bez zaskoczeń',
                'lead' => 'Wyjaśniamy strukturę państwowego testu, sposób pracy z czasem oraz plan przygotowań, który pomaga podejść do egzaminu spokojniej.',
                'key_points' => ['Egzamin teoretyczny trwa 25 minut.', 'Najpierw czytaj treść, potem analizuj materiał i dopiero wybieraj odpowiedź.', 'Ćwicz w warunkach zbliżonych do egzaminu i wracaj do błędów.'],
                'sections' => [
                    ['heading' => 'Jak wygląda egzamin', 'paragraphs' => ['Państwowy egzamin teoretyczny trwa 25 minut i obejmuje 32 pytania. Zadania mają różną wartość punktową, dlatego liczy się nie tylko tempo, ale przede wszystkim uważne rozpoznanie sytuacji.', 'Forma testu wymaga samodzielnej decyzji. Warto wcześniej oswoić się z filmami, zdjęciami oraz krótkim czasem na odpowiedź.']],
                    ['heading' => 'Strategia pracy z pytaniem', 'paragraphs' => ['Najpierw przeczytaj całe polecenie. Zwróć uwagę na słowa określające obowiązek, zakaz, możliwość lub pierwszeństwo. Następnie oceń to, co rzeczywiście widać na materiale — nie dopowiadaj okoliczności, których nie ma w pytaniu.'], 'bullets' => ['Czytaj do końca i nie odpowiadaj na podstawie pierwszego skojarzenia.', 'W filmie obserwuj znaki, sygnalizację i innych uczestników ruchu.', 'Po sesji sprawdź przyczynę błędu, nie tylko prawidłową literę.']],
                    ['heading' => 'Plan na ostatni tydzień', 'paragraphs' => ['Zamiast wielogodzinnej nauki dzień przed terminem zaplanuj krótkie, regularne sesje. Przeplataj pełne egzaminy z pracą tematyczną i listą błędów. Ostatniego dnia skup się na utrwaleniu zasad, odpoczynku i logistyce dojazdu do WORD.']],
                ],
                'image' => 'images/home/proof/exam.webp', 'alt' => 'Widok próbnego egzaminu teoretycznego na ekranie komputera', 'caption' => 'Regularny trening w formacie egzaminacyjnym pomaga opanować tempo i stres.',
                'seo_title' => 'Egzamin teoretyczny: 32 pytania, 25 minut — jak się przygotować', 'seo_description' => 'Poznaj strukturę egzaminu teoretycznego na prawo jazdy i praktyczny plan nauki przed testem w WORD.',
                'regulatory' => true, 'applies_to' => 'Osoby przygotowujące się do państwowego egzaminu teoretycznego.', 'exam_impact' => 'Pomaga przećwiczyć format testu, zarządzanie czasem i analizę pytań.', 'sources' => [$govDriving],
            ],
            [
                'category' => 'przepisy', 'author' => 'jakub',
                'slug' => 'hierarchia-polecen-sygnalow-i-znakow-drogowych',
                'title' => 'Hierarchia poleceń, sygnałów i znaków drogowych — zasada, która rozwiązuje wiele pytań',
                'lead' => 'Kiedy polecenie osoby kierującej ruchem jest ważniejsze od sygnalizatora, a kiedy sygnał świetlny wyprzedza znak? Pokazujemy prostą kolejność analizy.',
                'key_points' => ['Polecenia osoby kierującej ruchem mają pierwszeństwo przed sygnałami świetlnymi i znakami.', 'Sygnały świetlne mają pierwszeństwo przed znakami regulującymi pierwszeństwo.', 'Zawsze analizuj całą sytuację i bezpieczeństwo manewru.'],
                'sections' => [
                    ['heading' => 'Najważniejsza kolejność', 'paragraphs' => ['Art. 5 Prawa o ruchu drogowym ustala podstawową hierarchię. Najpierw stosujesz się do poleceń i sygnałów osoby uprawnionej do kierowania ruchem. Następnie bierzesz pod uwagę sygnalizację świetlną, a później znaki regulujące pierwszeństwo i pozostałe przepisy.'], 'bullets' => ['Osoba kierująca ruchem.', 'Sygnały świetlne.', 'Znaki drogowe i zasady ogólne — zależnie od sytuacji.']],
                    ['heading' => 'Jak użyć tej zasady na egzaminie', 'paragraphs' => ['W pytaniu najpierw wyszukaj element położony najwyżej w hierarchii. Jeżeli policjant kieruje ruchem, jego sygnał decyduje nawet wtedy, gdy sygnalizator lub znak wskazuje inne zachowanie. Jeżeli osoby kierującej nie ma, sprawdź światła.']],
                    ['heading' => 'Pułapka: hierarchia nie zwalnia z ostrożności', 'paragraphs' => ['Prawo do kontynuowania jazdy nie oznacza prawa do stworzenia zagrożenia. Kierujący nadal obserwuje drogę, pieszych i pojazdy, a prędkość dostosowuje do warunków. Na egzaminie wybieraj odpowiedź, która łączy właściwą hierarchię z bezpiecznym wykonaniem manewru.']],
                ],
                'image' => 'images/traffic-signs/traffic-signs-hero-reference.png', 'alt' => 'Zestaw polskich znaków drogowych ilustrujący naukę przepisów', 'caption' => 'Znaki są jednym z elementów sytuacji — o kolejności decyduje ustawowa hierarchia.',
                'seo_title' => 'Hierarchia poleceń, świateł i znaków drogowych — zasady', 'seo_description' => 'Poznaj kolejność stosowania poleceń osoby kierującej ruchem, sygnałów świetlnych i znaków drogowych.',
                'regulatory' => true, 'applies_to' => 'Wszyscy uczestnicy ruchu drogowego.', 'exam_impact' => 'Zasada pomaga prawidłowo analizować skrzyżowania i pytania o pierwszeństwo.', 'sources' => [$trafficLaw],
            ],
            [
                'category' => 'word', 'author' => 'katarzyna',
                'slug' => 'jak-zapisac-sie-na-egzamin-w-word',
                'title' => 'Jak zapisać się na egzamin w WORD i co zabrać ze sobą',
                'lead' => 'Praktyczna lista przed wizytą w WORD: numer PKK, termin, płatność, dokument tożsamości i przygotowanie organizacyjne.',
                'key_points' => ['Przygotuj numer PKK i wybierz właściwą kategorię egzaminu.', 'Sprawdź zasady zapisów i płatności w wybranym WORD.', 'W dniu egzaminu miej dokument tożsamości i przyjdź wcześniej.'],
                'sections' => [
                    ['heading' => 'Przed rezerwacją terminu', 'paragraphs' => ['Upewnij się, że Twój profil PKK jest gotowy, a dane i kategoria egzaminu są prawidłowe. Sposób rezerwacji oraz dostępne terminy mogą zależeć od ośrodka, dlatego korzystaj z oficjalnych kanałów wybranego WORD.']],
                    ['heading' => 'Lista kontrolna', 'paragraphs' => ['Przed zatwierdzeniem zapisu sprawdź miejsce, datę, godzinę i rodzaj egzaminu. Zachowaj potwierdzenie płatności lub rezerwacji oraz zanotuj zasady ewentualnej zmiany terminu.'], 'bullets' => ['Numer PKK.', 'Prawidłowa kategoria prawa jazdy.', 'Potwierdzenie rezerwacji i płatności.', 'Ważny dokument tożsamości na dzień egzaminu.']],
                    ['heading' => 'W dniu egzaminu', 'paragraphs' => ['Zaplanuj dojazd z zapasem czasu. Po przybyciu sprawdź salę lub punkt obsługi i wykonuj polecenia pracowników ośrodka. Ostatnie minuty wykorzystaj na uspokojenie oddechu, a nie na chaotyczne przeglądanie setek odpowiedzi.']],
                ],
                'image' => 'images/home/learning/classic-mode.png', 'alt' => 'Ekran nauki do egzaminu teoretycznego z panelem postępu', 'caption' => 'Dobre przygotowanie merytoryczne i organizacyjne zmniejsza liczbę niespodzianek w WORD.',
                'seo_title' => 'Jak zapisać się na egzamin w WORD? Dokumenty i lista kontrolna', 'seo_description' => 'Sprawdź, czego potrzebujesz do zapisu na egzamin w WORD i jak przygotować się organizacyjnie do terminu.',
                'regulatory' => true, 'applies_to' => 'Kandydaci zapisujący się na egzamin państwowy w WORD.', 'exam_impact' => 'Ogranicza ryzyko pomyłki w rezerwacji i braków w dniu egzaminu.', 'sources' => [$govLicence],
            ],
            [
                'category' => 'kierowcy', 'author' => 'katarzyna',
                'slug' => 'plan-nauki-do-egzaminu-teoretycznego',
                'title' => 'Plan nauki do egzaminu teoretycznego: jak pracować z błędami',
                'lead' => 'Systematyczność wygrywa z nauką na ostatnią chwilę. Oto prosty plan, który łączy teorię, pytania, powtórki i próbne egzaminy.',
                'key_points' => ['Ucz się krótko, ale regularnie.', 'Każdy błąd zamień w konkretną zasadę do zapamiętania.', 'Pełne egzaminy wykonuj dopiero po opanowaniu podstawowych działów.'],
                'sections' => [
                    ['heading' => 'Najpierw diagnoza, potem tempo', 'paragraphs' => ['Rozpocznij od krótkiej sesji przekrojowej i sprawdź, które działy sprawiają Ci największą trudność. Dzięki temu nie poświęcasz tyle samo czasu na zagadnienia dobrze znane i te, które wymagają pracy.']],
                    ['heading' => 'Schemat skutecznej sesji', 'paragraphs' => ['Jedna sesja może mieć trzy części: powtórzenie zasady, rozwiązanie serii pytań i analizę błędów. Przy błędnej odpowiedzi nazwij powód: brak znajomości przepisu, przeoczenie znaku, pośpiech czy błędna interpretacja polecenia.'], 'bullets' => ['5 minut: powtórka wcześniejszych błędów.', '10–15 minut: nowy dział lub seria pytań.', '5 minut: zapisanie zasad i zaplanowanie kolejnej powtórki.']],
                    ['heading' => 'Kiedy uruchomić próbny egzamin', 'paragraphs' => ['Pełny test służy do sprawdzania gotowości, a nie zastępuje nauki. Uruchamiaj go regularnie, ale po każdym wyniku wracaj do przyczyn pomyłek. Stabilne rezultaty z kilku sesji są lepszym sygnałem gotowości niż jeden przypadkowo wysoki wynik.']],
                ],
                'image' => 'images/review/mobile-memory-coach.png', 'alt' => 'Mobilny trener powtórek wspierający regularną naukę', 'caption' => 'Najwięcej daje regularna praca z błędami i powrót do trudnych zagadnień.',
                'seo_title' => 'Plan nauki do egzaminu teoretycznego — skuteczne powtórki', 'seo_description' => 'Ułóż skuteczny plan nauki do teorii: krótkie sesje, analiza błędów, powtórki i próbne egzaminy.',
                'regulatory' => false, 'applies_to' => 'Osoby uczące się do egzaminu teoretycznego.', 'exam_impact' => 'Pomaga utrwalić wiedzę i ograniczyć błędy wynikające z pośpiechu.', 'sources' => [$govDriving],
            ],
            [
                'category' => 'osk', 'author' => 'jakub',
                'slug' => 'jak-wybrac-szkole-jazdy-osk',
                'title' => 'Jak wybrać szkołę jazdy? 10 rzeczy, które warto sprawdzić w OSK',
                'lead' => 'Cena kursu to tylko jeden element. Sprawdź program, instruktorów, samochody, organizację jazd i sposób wspierania kursanta.',
                'key_points' => ['Porównuj pełny zakres usługi, a nie tylko cenę początkową.', 'Zapytaj o organizację jazd, instruktorów i pojazdy szkoleniowe.', 'Sprawdź, jak OSK monitoruje postęp i pomaga poprawiać błędy.'],
                'sections' => [
                    ['heading' => 'Zacznij od konkretnej rozmowy', 'paragraphs' => ['Dobre OSK potrafi jasno wyjaśnić przebieg kursu, zasady zapisów, harmonogram i wszystkie koszty. Przed podpisaniem umowy zapytaj, co dokładnie zawiera cena i jak wygląda kontakt z biurem oraz instruktorem.']],
                    ['heading' => '10 kryteriów wyboru', 'paragraphs' => ['Porównaj kilka szkół według tego samego zestawu kryteriów. Dzięki temu opinia znajomych lub atrakcyjna promocja nie przesłonią jakości szkolenia.'], 'bullets' => ['Czytelna umowa i pełny cennik.', 'Dostępność terminów zajęć i jazd.', 'Doświadczenie oraz sposób pracy instruktorów.', 'Stan i typ pojazdów szkoleniowych.', 'Możliwość zmiany instruktora.', 'Sposób odwoływania jazd.', 'Materiały do nauki teorii.', 'Monitorowanie postępu kursanta.', 'Przygotowanie do placu i ruchu miejskiego.', 'Rzetelne opinie opisujące konkretne doświadczenia.']],
                    ['heading' => 'Najtańszy kurs nie zawsze kosztuje najmniej', 'paragraphs' => ['Niska cena początkowa może nie obejmować wszystkich materiałów, badań lub dodatkowych jazd. Poproś o całkowity koszt i sprawdź, co dzieje się w razie przerwy w szkoleniu. Najważniejsza jest jakość przygotowania do bezpiecznej, samodzielnej jazdy.']],
                ],
                'image' => 'images/home/feature-manager.png', 'alt' => 'Instruktor i kursant podczas omawiania planu szkolenia', 'caption' => 'Dobre OSK jasno komunikuje zasady kursu i regularnie omawia postęp kursanta.',
                'seo_title' => 'Jak wybrać szkołę jazdy? 10 kryteriów dobrego OSK', 'seo_description' => 'Sprawdź, jak wybrać szkołę jazdy: instruktorzy, terminy, samochody, umowa, koszty i wsparcie kursanta.',
                'regulatory' => false, 'applies_to' => 'Kandydaci wybierający ośrodek szkolenia kierowców.', 'exam_impact' => 'Pomaga wybrać szkolenie nastawione na bezpieczną jazdę i rzetelne przygotowanie.', 'sources' => [$govLicence],
            ],
        ];
    }
}
