<?php

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentTopic;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\QuestionPublicExplanation;
use App\Support\QuestionVideoSeoDescriptionService;
use App\Support\SeoSitemapBuilder;
use App\Support\SeoSitemapGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('filesystems.disks.public.url', 'https://prawkonaraz.pl/storage');
    config()->set('media.public_base_url', 'https://prawkonaraz.pl/storage');
    config()->set('newsroom.public_enabled', false);
    config()->set('newsroom.article_sitemap_shard_id_span', 10000);
    config()->set('newsroom.news_sitemap_max_entries', SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES);
    config()->set('seo.sitemap_max_urls_per_file', SeoSitemapGenerator::MAX_URLS_PER_FILE);
    config()->set('seo.sitemap_max_uncompressed_bytes', SeoSitemapGenerator::MAX_UNCOMPRESSED_BYTES);
    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');

    File::delete(public_path('sitemap.xml'));

    foreach (File::glob(public_path('sitemaps/*.xml')) ?: [] as $file) {
        File::delete($file);
    }
});

afterEach(function (): void {
    Carbon::setTestNow();
    URL::forceRootUrl(null);
    URL::forceScheme(null);

    File::delete(public_path('sitemap.xml'));

    foreach (File::glob(public_path('sitemaps/*.xml')) ?: [] as $file) {
        File::delete($file);
    }
});

test('seo sitemap generator splits canonical question urls by category', function () {
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'sort_order' => 2,
    ]);

    $canonicalQuestion = Question::factory()->create([
        'license_category_id' => $categoryB->getKey(),
        'external_id' => '99',
        'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
        'updated_at' => now()->subDay(),
    ]);
    QuestionMedia::factory()->create([
        'question_id' => $canonicalQuestion->getKey(),
        'kind' => 'image',
        'path' => 'questions/99/full.webp',
        'poster_path' => null,
        'variant' => 'full',
        'sort_order' => 0,
    ]);

    $latestQuestionUpdatedAt = now();

    Question::factory()->create([
        'license_category_id' => $categoryA->getKey(),
        'external_id' => '99',
        'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
        'updated_at' => $latestQuestionUpdatedAt,
    ]);

    Question::factory()->create([
        'license_category_id' => $categoryA->getKey(),
        'external_id' => '100',
        'prompt' => 'Czy możesz kontynuować jazdę?',
    ]);

    $publicExplanationUpdatedAt = Carbon::parse('2026-06-20 10:00:00');
    QuestionPublicExplanation::factory()
        ->published()
        ->create([
            'external_id' => '99',
            'body' => 'Publiczne omówienie sytuacji dla pytania 99.',
            'updated_at' => $publicExplanationUpdatedAt,
            'created_at' => $publicExplanationUpdatedAt->copy()->subHour(),
        ]);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemap.xml')))->toBeTrue();
    expect(File::exists(public_path('sitemaps/questions-b.xml')))->toBeTrue();
    expect(File::exists(public_path('sitemaps/questions-a.xml')))->toBeTrue();
    expect(File::exists(public_path('sitemaps/questions.xml')))->toBeTrue();
    expect(File::exists(public_path('sitemaps/videos.xml')))->toBeTrue();

    $index = File::get(public_path('sitemap.xml'));
    expect($index)
        ->toContain('https://prawkonaraz.pl/sitemaps/questions-a.xml')
        ->toContain('https://prawkonaraz.pl/sitemaps/questions-b.xml')
        ->toContain('https://prawkonaraz.pl/sitemaps/videos.xml')
        ->not->toContain('https://prawkonaraz.pl/sitemaps/questions.xml');
    expect($index)->toMatch('/<loc>https:\/\/prawkonaraz\.pl\/sitemaps\/static\.xml<\/loc>\s*<lastmod>[^<]+<\/lastmod>/');

    $legacyIndex = File::get(public_path('sitemaps/questions.xml'));
    expect($legacyIndex)
        ->toContain('https://prawkonaraz.pl/sitemaps/questions-a.xml')
        ->toContain('https://prawkonaraz.pl/sitemaps/questions-b.xml');

    $questionsB = File::get(public_path('sitemaps/questions-b.xml'));
    $questionsA = File::get(public_path('sitemaps/questions-a.xml'));

    expect(substr_count($questionsB, '/pytanie/99/'))->toBe(1);
    expect($questionsB)
        ->toContain('https://prawkonaraz.pl/pytanie/99/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd')
        ->toMatch('/<loc>https:\/\/prawkonaraz\.pl\/pytanie\/99\/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd<\/loc>\s*<lastmod>'.preg_quote($latestQuestionUpdatedAt->toIso8601String(), '/').'<\/lastmod>/')
        ->toContain('<image:loc>https://prawkonaraz.pl/storage/questions/99/full.webp</image:loc>');
    expect($questionsA)
        ->not->toContain('/pytanie/99/')
        ->toContain('/pytanie/100/');

    $this->artisan('seo:audit-sitemaps')
        ->assertSuccessful();
});

test('dynamic legacy question sitemap route is lightweight index instead of full question urlset', function () {
    LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    Question::factory()->create([
        'license_category_id' => LicenseCategory::query()->firstOrFail()->getKey(),
        'external_id' => '99',
        'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
    ]);

    $this->get('/sitemaps/questions.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<sitemapindex', false)
        ->assertSee('https://prawkonaraz.pl/sitemaps/questions-b.xml', false)
        ->assertDontSee('/pytanie/99/');

    $this->get('/sitemaps/questions-b.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('https://prawkonaraz.pl/pytanie/99/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd', false);
});

test('question image sitemap uses video posters instead of raw video files', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '102',
        'prompt' => 'Czy film pokazuje sytuację wymagającą ustąpienia pierwszeństwa?',
    ]);

    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/102/clip.mp4',
            'poster_path' => 'questions/102/poster.webp',
            'mime_type' => 'video/mp4',
            'sort_order' => 0,
        ]);
    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    $questionsB = File::get(public_path('sitemaps/questions-b.xml'));

    expect($questionsB)
        ->toContain('<image:loc>https://prawkonaraz.pl/storage/questions/102/poster.webp</image:loc>')
        ->not->toContain('<image:loc>https://prawkonaraz.pl/storage/questions/102/clip.mp4</image:loc>');
});

test('question video sitemap exposes public video metadata without duplicating raw video as page loc', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '103',
        'prompt' => 'Czy film pokazuje sytuację wymagającą zatrzymania pojazdu?',
        'explanation' => 'Film przedstawia sytuację egzaminacyjną, w której należy zatrzymać pojazd.',
        'published_at' => now()->setDate(2026, 5, 10)->setTime(9, 30),
    ]);

    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/103/clip.mp4',
            'poster_path' => 'questions/103/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 22,
            'width' => 1920,
            'height' => 1080,
            'sort_order' => 0,
        ]);

    QuestionPublicExplanation::factory()
        ->published()
        ->create([
            'external_id' => '103',
            'body' => 'Publiczne omówienie SEO mówi szerzej o odpowiedzi, ale nie może trafić do video description.',
        ]);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    $videos = File::get(public_path('sitemaps/videos.xml'));

    expect($videos)
        ->toContain('xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"')
        ->toContain('<loc>https://prawkonaraz.pl/pytanie/103/czy-film-pokazuje-sytuacje-wymagajaca-zatrzymania-pojazdu</loc>')
        ->toContain('<video:thumbnail_loc>https://prawkonaraz.pl/storage/questions/103/poster.webp</video:thumbnail_loc>')
        ->toContain('<video:title>Film do pytania 103: Czy film pokazuje sytuację wymagającą zatrzymania pojazdu?</video:title>')
        ->toContain('Temat sceny: ocena, czy film pokazuje sytuację wymagającą zatrzymania pojazdu.')
        ->not->toContain('<video:description>Film przedstawia sytuację egzaminacyjną, w której należy zatrzymać pojazd.</video:description>')
        ->not->toContain('Publiczne omówienie SEO')
        ->toContain('<video:content_loc>https://prawkonaraz.pl/storage/questions/103/clip.mp4</video:content_loc>')
        ->toContain('<video:duration>22</video:duration>')
        ->toContain('<video:publication_date>2026-05-10T09:30:00')
        ->toContain('<video:family_friendly>yes</video:family_friendly>')
        ->not->toContain('<loc>https://prawkonaraz.pl/storage/questions/103/clip.mp4</loc>');

    preg_match_all('/<video:description>(.*?)<\/video:description>/u', $videos, $descriptionMatches);
    $descriptions = $descriptionMatches[1] ?? [];

    expect($descriptions)->toHaveCount(1);
    expect($descriptions[0])
        ->not->toStartWith('Tak.')
        ->not->toStartWith('Nie.');

    $this->get('/sitemaps/videos.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<video:content_loc>https://prawkonaraz.pl/storage/questions/103/clip.mp4</video:content_loc>', false);

    $this->artisan('seo:audit-sitemaps')
        ->assertSuccessful();
});

test('question video sitemap prefers editorial media description when present', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '104',
        'prompt' => 'Czy film pokazuje sytuację wymagającą zatrzymania pojazdu?',
        'explanation' => 'Tak. W tej sytuacji masz obowiązek zatrzymać pojazd.',
    ]);
    $editorialDescription = 'Miejska ulica z przejściem dla pieszych, sygnalizacją i pojazdami przed kamerą pokazuje scenę obserwacji drogi. Kadr akcentuje światła, pobocze oraz przestrzeń przed autem.';

    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/104/clip.mp4',
            'poster_path' => 'questions/104/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 18,
            'seo_video_description' => $editorialDescription,
        ]);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    $videos = File::get(public_path('sitemaps/videos.xml'));

    expect($videos)
        ->toContain('<video:description>'.$editorialDescription.'</video:description>')
        ->not->toContain('Temat sceny:')
        ->not->toContain('Tak. W tej sytuacji masz obowiązek zatrzymać pojazd.');
});

test('question video sitemap titles are not truncated with ellipsis', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji policjant kierujący ruchem umożliwia Ci wykonanie manewru skrętu w lewo przez skrzyżowanie z kilku pasów ruchu?';
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '10107',
        'prompt' => $prompt,
    ]);

    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $question->getKey(),
            'disk' => 'public',
            'path' => 'questions/10107/clip.mp4',
            'poster_path' => 'questions/10107/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 11,
        ]);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    $videos = File::get(public_path('sitemaps/videos.xml'));
    $title = 'Film do pytania 10107: '.$prompt;

    expect($videos)
        ->toContain('<video:title>'.$title.'</video:title>')
        ->not->toContain('Film do pytania 10107: Czy w tej sytuacji policjant kierujący ruchem umożliwia Ci wykonanie manewru skrętu w l...');
});

test('question sitemap canonicalizes prefixed question ids without exposing source prefix', function () {
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'sort_order' => 2,
    ]);

    $nonCollidingQuestion = Question::factory()->create([
        'license_category_id' => $categoryB->getKey(),
        'external_id' => 'pj360:2858',
        'prompt' => 'Zamierzasz skręcić w prawo na najbliższym skrzyżowaniu?',
    ]);
    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $nonCollidingQuestion->getKey(),
            'disk' => 'public',
            'path' => 'questions/2858/clip.mp4',
            'poster_path' => 'questions/2858/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 14,
        ]);

    $standardQuestion = Question::factory()->create([
        'license_category_id' => $categoryB->getKey(),
        'external_id' => '3540',
        'prompt' => 'Czy standardowe pytanie o numerze 3540 dotyczy zatrzymania pojazdu?',
    ]);
    QuestionMedia::factory()->create([
        'question_id' => $standardQuestion->getKey(),
        'kind' => 'image',
        'path' => 'questions/3540/full.webp',
        'poster_path' => null,
        'variant' => 'full',
        'sort_order' => 0,
    ]);

    $collidingQuestion = Question::factory()->create([
        'license_category_id' => $categoryA->getKey(),
        'external_id' => 'pj360:3540',
        'prompt' => 'Czy pomocnicze pytanie o numerze 3540 pokazuje inną sytuację drogową?',
    ]);
    QuestionMedia::factory()
        ->video()
        ->create([
            'question_id' => $collidingQuestion->getKey(),
            'disk' => 'public',
            'path' => 'questions/auxiliary-3540/clip.mp4',
            'poster_path' => 'questions/auxiliary-3540/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 19,
        ]);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    $questionsA = File::get(public_path('sitemaps/questions-a.xml'));
    $questionsB = File::get(public_path('sitemaps/questions-b.xml'));
    $videos = File::get(public_path('sitemaps/videos.xml'));

    expect($questionsB)
        ->toContain('https://prawkonaraz.pl/pytanie/2858/zamierzasz-skrecic-w-prawo-na-najblizszym-skrzyzowaniu')
        ->toContain('https://prawkonaraz.pl/pytanie/3540/czy-standardowe-pytanie-o-numerze-3540-dotyczy-zatrzymania-pojazdu')
        ->not->toContain('pj360');
    expect($questionsA)
        ->toContain('https://prawkonaraz.pl/pytanie/pytanie-pomocnicze-3540/czy-pomocnicze-pytanie-o-numerze-3540-pokazuje-inna-sytuacje-drogowa')
        ->not->toContain('pj360');
    expect($videos)
        ->toContain('https://prawkonaraz.pl/pytanie/2858/zamierzasz-skrecic-w-prawo-na-najblizszym-skrzyzowaniu')
        ->toContain('https://prawkonaraz.pl/pytanie/pytanie-pomocnicze-3540/czy-pomocnicze-pytanie-o-numerze-3540-pokazuje-inna-sytuacje-drogowa')
        ->not->toContain('pj360')
        ->not->toContain('pj360:');
});

test('question video seo descriptions are unique and do not reuse answer explanations', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    $firstQuestion = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '103',
        'prompt' => 'Czy film pokazuje sytuację wymagającą zatrzymania pojazdu?',
        'explanation' => 'Tak. W tej sytuacji masz obowiązek zatrzymać pojazd.',
    ]);
    $secondQuestion = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '104',
        'prompt' => 'Czy nagranie przedstawia manewr omijania przeszkody?',
        'explanation' => 'Nie. W tej sytuacji nie wolno kontynuować manewru.',
    ]);

    $firstQuestion->load('licenseCategory');
    $secondQuestion->load('licenseCategory');

    $service = app(QuestionVideoSeoDescriptionService::class);
    $descriptions = [
        $service->forQuestion($firstQuestion),
        $service->forQuestion($secondQuestion),
    ];

    expect(array_unique($descriptions))->toHaveCount(2);

    foreach ($descriptions as $description) {
        expect(mb_strlen($description))->toBeGreaterThan(160);
        expect(mb_strlen($description))->toBeLessThanOrEqual(320);
        expect($description)
            ->toContain('Temat sceny:')
            ->not->toStartWith('Tak.')
            ->not->toStartWith('Nie.')
            ->not->toContain('obowiązek zatrzymać pojazd.')
            ->not->toContain('nie wolno kontynuować manewru');
    }
});

test('seo refresh command generates files and runs the audit', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '101',
        'prompt' => 'Czy możesz jechać dalej?',
    ]);

    $this->artisan('seo:refresh-sitemaps')
        ->expectsOutputToContain('sitemap.xml urls=')
        ->expectsOutput('Sitemap files generated and audit passed.')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemap.xml')))->toBeTrue();
    expect(File::exists(public_path('sitemaps/questions-b.xml')))->toBeTrue();
});

test('newsroom article sitemap includes only indexable canonical public articles and indexable hubs', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');
    config()->set('newsroom.public_enabled', true);

    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy-sitemap',
        'updated_at' => now()->subMinutes(20),
    ]);
    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Autor sitemap',
        'slug' => 'autor-sitemap',
    ]);

    $publishedLastmod = now()->subMinutes(5);
    $published = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Publiczna aktualność sitemap',
        'slug' => 'publiczna-aktualnosc-sitemap',
        'first_published_at' => now()->subDays(2),
        'published_at' => now()->subDays(2),
        'last_substantive_update_at' => now()->subHour(),
        'public_state_changed_at' => $publishedLastmod,
    ]);
    $guide = ContentArticle::factory()->published()->guide()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Publiczny poradnik sitemap',
        'slug' => 'publiczny-poradnik-sitemap',
    ]);
    $needsReview = ContentArticle::factory()->needsReview()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Materiał needs review sitemap',
        'slug' => 'material-needs-review-sitemap',
    ]);
    $archived = ContentArticle::factory()->archived()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Archiwalny indeksowalny sitemap',
        'slug' => 'archiwalny-indeksowalny-sitemap',
    ]);

    ContentArticle::factory()->published()->noindex()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Noindex poza sitemap',
        'slug' => 'noindex-poza-sitemap',
    ]);
    ContentArticle::factory()->draft()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Draft poza sitemap',
        'slug' => 'draft-poza-sitemap',
    ]);
    ContentArticle::factory()->withdrawn()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Withdrawn poza sitemap',
        'slug' => 'withdrawn-poza-sitemap',
    ]);

    $inactiveCategory = ContentCategory::factory()->inactive()->create([
        'name' => 'Nieaktywna',
        'slug' => 'nieaktywna-sitemap',
    ]);
    ContentArticle::factory()->published()->create([
        'category_id' => $inactiveCategory->id,
        'author_id' => $author->id,
        'title' => 'Nieaktywna kategoria poza sitemap',
        'slug' => 'nieaktywna-kategoria-poza-sitemap',
    ]);

    $unpublishedAuthor = ContentAuthor::factory()->create([
        'name' => 'Niepubliczny autor sitemap',
        'slug' => 'niepubliczny-autor-sitemap',
    ]);
    ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $unpublishedAuthor->id,
        'title' => 'Niepubliczny autor poza sitemap',
        'slug' => 'niepubliczny-autor-poza-sitemap',
    ]);

    $redirectSource = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Kolizja redirect source',
        'slug' => 'kolizja-redirect-source',
    ]);
    ContentArticleRedirect::query()->create([
        'article_id' => $redirectSource->id,
        'from_path' => '/aktualnosci/kolizja-redirect-source',
        'to_path' => '/aktualnosci/publiczna-aktualnosc-sitemap',
        'http_status' => 301,
    ]);

    $topic = ContentTopic::factory()->published()->create([
        'title' => 'Topic sitemap',
        'slug' => 'topic-sitemap',
        'updated_at' => now()->subMinutes(15),
    ]);
    $topic->articles()->attach($published->id);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemaps/articles.xml')))->toBeTrue();

    $articles = File::get(public_path('sitemaps/articles.xml'));
    $static = File::get(public_path('sitemaps/static.xml'));
    $index = File::get(public_path('sitemap.xml'));

    expect($index)->toContain('https://prawkonaraz.pl/sitemaps/articles.xml');

    expect($articles)
        ->toContain('https://prawkonaraz.pl/aktualnosci/publiczna-aktualnosc-sitemap')
        ->toContain('https://prawkonaraz.pl/poradniki/publiczny-poradnik-sitemap')
        ->toContain('https://prawkonaraz.pl/aktualnosci/material-needs-review-sitemap')
        ->toContain('https://prawkonaraz.pl/aktualnosci/archiwalny-indeksowalny-sitemap')
        ->toContain('<lastmod>'.$publishedLastmod->toIso8601String().'</lastmod>')
        ->not->toContain('noindex-poza-sitemap')
        ->not->toContain('draft-poza-sitemap')
        ->not->toContain('withdrawn-poza-sitemap')
        ->not->toContain('nieaktywna-kategoria-poza-sitemap')
        ->not->toContain('niepubliczny-autor-poza-sitemap')
        ->not->toContain('kolizja-redirect-source');

    expect($static)
        ->toContain('https://prawkonaraz.pl/aktualnosci')
        ->toContain('https://prawkonaraz.pl/poradniki')
        ->toContain('https://prawkonaraz.pl/aktualnosci/kategoria/egzaminy-sitemap')
        ->toContain('https://prawkonaraz.pl/aktualnosci/temat/topic-sitemap')
        ->not->toContain('https://prawkonaraz.pl/aktualnosci/kategoria/nieaktywna-sitemap');

    $this->artisan('seo:audit-sitemaps')
        ->assertSuccessful();
});

test('newsroom sitemap coverage stays disabled behind the public gate', function () {
    config()->set('newsroom.public_enabled', false);

    ContentArticle::factory()->published()->create([
        'title' => 'Dark deployed article',
        'slug' => 'dark-deployed-article-sitemap',
    ]);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemaps/articles.xml')))->toBeFalse();
    expect(File::glob(public_path('sitemaps/news*.xml')) ?: [])->toBeEmpty();

    $static = File::get(public_path('sitemaps/static.xml'));
    $index = File::get(public_path('sitemap.xml'));

    expect($index)
        ->not->toContain('/sitemaps/articles')
        ->not->toContain('/sitemaps/news');
    expect($static)
        ->not->toContain('<loc>https://prawkonaraz.pl/aktualnosci</loc>')
        ->not->toContain('<loc>https://prawkonaraz.pl/poradniki</loc>');
});

test('Google News sitemap uses required metadata and first publication eligibility', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');
    config()->set('newsroom.public_enabled', true);
    config()->set('content.organization.name', 'PrawkoNaRaz');

    $category = ContentCategory::factory()->create([
        'name' => 'Aktualności',
        'slug' => 'aktualnosci-news-sitemap',
    ]);
    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Redakcja News Sitemap',
        'slug' => 'redakcja-news-sitemap',
    ]);

    $freshPublishedAt = now()->subHours(6);
    $fresh = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Nowe przepisy & egzamin',
        'seo_title' => 'SEO title nie może wejść do news:title - PrawkoNaRaz',
        'slug' => 'nowe-przepisy-egzamin-news-sitemap',
        'first_published_at' => $freshPublishedAt,
        'published_at' => $freshPublishedAt,
        'last_substantive_update_at' => now()->subMinute(),
        'public_state_changed_at' => now()->subMinute(),
    ]);

    $boundaryPublishedAt = now()->subDays(SeoSitemapBuilder::NEWS_SITEMAP_WINDOW_DAYS);
    $boundary = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Materiał dokładnie na granicy dwóch dni',
        'slug' => 'material-granica-dwoch-dni',
        'first_published_at' => $boundaryPublishedAt,
        'published_at' => $boundaryPublishedAt,
    ]);

    $old = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Stary news z nową aktualizacją',
        'slug' => 'stary-news-z-nowa-aktualizacja',
        'first_published_at' => now()->subDays(2)->subSecond(),
        'published_at' => now()->subMinute(),
        'last_substantive_update_at' => now(),
        'public_state_changed_at' => now(),
    ]);

    ContentArticle::factory()->published()->guide()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Świeży poradnik poza News Sitemap',
        'slug' => 'swiezy-poradnik-poza-news-sitemap',
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ]);

    ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::Explainer->value,
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Świeży explainer poza News Sitemap',
        'slug' => 'swiezy-explainer-poza-news-sitemap',
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ]);

    ContentArticle::factory()->needsReview()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Needs review poza News Sitemap',
        'slug' => 'needs-review-poza-news-sitemap',
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ]);

    ContentArticle::factory()->published()->noindex()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Noindex poza News Sitemap',
        'slug' => 'noindex-poza-news-sitemap',
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ]);

    $inactiveCategory = ContentCategory::factory()->inactive()->create([
        'name' => 'Nieaktywna News',
        'slug' => 'nieaktywna-news',
    ]);
    ContentArticle::factory()->published()->create([
        'category_id' => $inactiveCategory->id,
        'author_id' => $author->id,
        'title' => 'Nieaktywna kategoria poza News Sitemap',
        'slug' => 'nieaktywna-kategoria-poza-news-sitemap',
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ]);

    $privateAuthor = ContentAuthor::factory()->create([
        'name' => 'Niepubliczny autor News',
        'slug' => 'niepubliczny-autor-news',
    ]);
    ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $privateAuthor->id,
        'title' => 'Niepubliczny autor poza News Sitemap',
        'slug' => 'niepubliczny-autor-poza-news-sitemap',
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ]);

    $redirectSource = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Redirect source poza News Sitemap',
        'slug' => 'redirect-source-poza-news-sitemap',
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ]);
    ContentArticleRedirect::query()->create([
        'article_id' => $redirectSource->id,
        'from_path' => '/aktualnosci/redirect-source-poza-news-sitemap',
        'to_path' => '/aktualnosci/nowe-przepisy-egzamin-news-sitemap',
        'http_status' => 301,
    ]);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemaps/news.xml')))->toBeTrue();

    $news = File::get(public_path('sitemaps/news.xml'));
    $index = File::get(public_path('sitemap.xml'));

    expect($index)->toContain('https://prawkonaraz.pl/sitemaps/news.xml');

    expect($news)
        ->toContain('xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"')
        ->toContain('<loc>https://prawkonaraz.pl/aktualnosci/'.$fresh->slug.'</loc>')
        ->toContain('<news:name>PrawkoNaRaz</news:name>')
        ->toContain('<news:language>pl</news:language>')
        ->toContain('<news:publication_date>'.$freshPublishedAt->toIso8601String().'</news:publication_date>')
        ->toContain('<news:title>Nowe przepisy &amp; egzamin</news:title>')
        ->toContain('<loc>https://prawkonaraz.pl/aktualnosci/'.$boundary->slug.'</loc>')
        ->not->toContain('SEO title nie może wejść')
        ->not->toContain($old->slug)
        ->not->toContain('swiezy-poradnik-poza-news-sitemap')
        ->not->toContain('swiezy-explainer-poza-news-sitemap')
        ->not->toContain('needs-review-poza-news-sitemap')
        ->not->toContain('noindex-poza-news-sitemap')
        ->not->toContain('nieaktywna-kategoria-poza-news-sitemap')
        ->not->toContain('niepubliczny-autor-poza-news-sitemap')
        ->not->toContain('redirect-source-poza-news-sitemap');

    expect(substr_count($news, '<news:news>'))->toBe(2);

    $this->artisan('seo:audit-sitemaps')
        ->assertSuccessful();
});

test('Google News sitemap splits above the verified 1000 entry limit', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');
    config()->set('newsroom.public_enabled', true);

    expect(SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES)->toBe(1000);
    expect(config('newsroom.news_sitemap_max_entries'))->toBe(1000);

    $category = ContentCategory::factory()->create();
    $author = ContentAuthor::factory()->published()->create();

    ContentArticle::factory()
        ->count(SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES + 1)
        ->published()
        ->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemaps/news.xml')))->toBeFalse();

    $files = collect(File::glob(public_path('sitemaps/news-*.xml')) ?: [])->sort()->values();

    expect($files->count())->toBeGreaterThan(1);

    $totalEntries = 0;
    $index = File::get(public_path('sitemap.xml'));

    foreach ($files as $file) {
        $xml = File::get($file);
        $entries = substr_count($xml, '<news:news>');

        expect($entries)->toBeGreaterThan(0);
        expect($entries)->toBeLessThanOrEqual(SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES);
        expect($index)->toContain('https://prawkonaraz.pl/sitemaps/'.basename($file));

        $totalEntries += $entries;
    }

    expect($totalEntries)->toBe(SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES + 1);

    $this->artisan('seo:audit-sitemaps')
        ->assertSuccessful();
});

test('newsroom article shards cross deterministic id ranges and keep stable assignment', function () {
    config()->set('newsroom.public_enabled', true);
    config()->set('newsroom.article_sitemap_shard_id_span', 2);

    $category = ContentCategory::factory()->create();
    $author = ContentAuthor::factory()->published()->create();

    $articles = collect(range(1, 3))
        ->map(fn (int $index): ContentArticle => ContentArticle::factory()->published()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'title' => 'Shard article '.$index,
            'slug' => 'shard-article-'.$index,
        ]));

    $shardPathFor = static function (int $id): string {
        $bucket = intdiv($id - 1, 2);
        $start = ($bucket * 2) + 1;
        $end = ($bucket + 1) * 2;

        return sprintf('sitemaps/articles-%06d-%06d.xml', $start, $end);
    };

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemaps/articles.xml')))->toBeFalse();

    $paths = $articles
        ->map(fn (ContentArticle $article): string => $shardPathFor((int) $article->id))
        ->unique()
        ->values();

    expect($paths->count())->toBeGreaterThan(1);

    $index = File::get(public_path('sitemap.xml'));

    foreach ($paths as $relativePath) {
        expect(File::exists(public_path($relativePath)))->toBeTrue();
        expect($index)->toContain('https://prawkonaraz.pl/'.$relativePath);
    }

    foreach ($articles as $article) {
        $relativePath = $shardPathFor((int) $article->id);
        $xml = File::get(public_path($relativePath));

        expect($xml)->toContain('/aktualnosci/'.$article->slug);
    }

    $stableArticle = $articles->last();
    $stableShardPath = $shardPathFor((int) $stableArticle->id);

    $articles->first()->update(['robots' => 'noindex,follow']);

    $this->artisan('seo:generate-sitemaps')
        ->assertSuccessful();

    expect(File::exists(public_path($stableShardPath)))->toBeTrue();
    expect(File::get(public_path($stableShardPath)))
        ->toContain('/aktualnosci/'.$stableArticle->slug);

    $allArticleXml = collect(File::glob(public_path('sitemaps/articles-*.xml')) ?: [])
        ->map(fn (string $file): string => File::get($file))
        ->implode("\n");

    foreach ($articles->slice(1) as $article) {
        expect(substr_count($allArticleXml, '/aktualnosci/'.$article->slug))->toBe(1);
    }

    $this->artisan('seo:audit-sitemaps')
        ->assertSuccessful();
});

test('sitemap generator rejects payloads above the configured url count guard', function () {
    config()->set('seo.sitemap_max_urls_per_file', 1);

    $exception = null;

    try {
        app(SeoSitemapGenerator::class)->generate(true);
    } catch (RuntimeException $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeInstanceOf(RuntimeException::class);
    expect($exception?->getMessage())->toContain('Generated sitemap exceeds URL limit');
});

test('sitemap generator rejects payloads above the configured byte size guard', function () {
    config()->set('seo.sitemap_max_uncompressed_bytes', 128);

    $exception = null;

    try {
        app(SeoSitemapGenerator::class)->generate(true);
    } catch (RuntimeException $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeInstanceOf(RuntimeException::class);
    expect($exception?->getMessage())->toContain('Generated sitemap exceeds uncompressed byte limit');
});
