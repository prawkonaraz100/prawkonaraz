<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\QuestionPublicExplanation;
use App\Support\QuestionVideoSeoDescriptionService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('filesystems.disks.public.url', 'https://prawkonaraz.pl/storage');
    config()->set('media.public_base_url', 'https://prawkonaraz.pl/storage');
    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');

    File::delete(public_path('sitemap.xml'));

    foreach (File::glob(public_path('sitemaps/*.xml')) ?: [] as $file) {
        File::delete($file);
    }
});

afterEach(function (): void {
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

    Question::factory()->create([
        'license_category_id' => $categoryA->getKey(),
        'external_id' => '99',
        'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
        'updated_at' => now(),
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
        ->toMatch('/<loc>https:\/\/prawkonaraz\.pl\/pytanie\/99\/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd<\/loc>\s*<lastmod>'.preg_quote($publicExplanationUpdatedAt->toIso8601String(), '/').'<\/lastmod>/')
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
