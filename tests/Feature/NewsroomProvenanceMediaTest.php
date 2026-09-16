<?php

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleSourceType;
use App\Filament\Resources\ContentArticles\Pages\EditContentArticle;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\User;
use App\Support\ContentArticlePublicationChecklist;
use App\Support\NewsroomArticleMediaService;
use App\Support\NewsroomArticleProvenanceMediaAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;

function newsroomTinyPngUpload(string $name = 'hero.png'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'newsroom-image-');

    file_put_contents(
        $path,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQMcAAAAASUVORK5CYII=', true),
    );

    return new UploadedFile(
        $path,
        $name,
        'image/png',
        null,
        true,
    );
}

function configureNewsroomFakeMediaDisk(): void
{
    Storage::fake('public');

    config()->set('media.public_disk', 'public');
    config()->set('media.newsroom_disk', 'public');
    config()->set('media.newsroom_prefix', 'newsroom/articles');
    config()->set('media.public_base_url', 'https://cdn.example.test/media');
}

test('newsroom media service stores immutable managed source path and verified metadata', function () {
    configureNewsroomFakeMediaDisk();

    $stored = app(NewsroomArticleMediaService::class)->store(newsroomTinyPngUpload());

    expect($stored['path'])->toMatch('/\Anewsroom\/articles\/source\/[0-9a-hjkmnp-tv-z]{26}\.png\z/')
        ->and($stored['mime_type'])->toBe('image/png')
        ->and($stored['bytes'])->toBeGreaterThan(0)
        ->and($stored['width'])->toBe(1)
        ->and($stored['height'])->toBe(1)
        ->and($stored['public_url'])->toStartWith('https://cdn.example.test/media/')
        ->and(Storage::disk('public')->exists($stored['path']))->toBeTrue();
});

test('provenance media adapter verifies stored image metadata and focal point', function () {
    configureNewsroomFakeMediaDisk();

    $stored = app(NewsroomArticleMediaService::class)->store(newsroomTinyPngUpload());

    $normalized = NewsroomArticleProvenanceMediaAdapter::normalizeArticleData([
        'origin_type' => ContentArticleOriginType::Original->value,
        'regulatory_status' => ContentArticleRegulatoryStatus::NotApplicable->value,
        'hero_image_path' => $stored['path'],
        'hero_image_width' => 999,
        'hero_image_height' => 999,
        'hero_focal_x' => '0.25',
        'hero_focal_y' => '0.75',
    ]);

    expect($normalized['hero_image_width'])->toBe(1)
        ->and($normalized['hero_image_height'])->toBe(1)
        ->and($normalized['hero_focal_x'])->toBe(0.25)
        ->and($normalized['hero_focal_y'])->toBe(0.75);

    expect(fn () => NewsroomArticleProvenanceMediaAdapter::normalizeArticleData([
        'hero_image_path' => 'newsroom/articles/source/manual.png',
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => NewsroomArticleProvenanceMediaAdapter::normalizeArticleData([
        'hero_focal_x' => 0.4,
        'hero_focal_y' => null,
    ]))->toThrow(InvalidArgumentException::class, 'requires both X and Y');
});

test('regulatory publication contract requires coherent official source and effective date', function () {
    $article = ContentArticle::factory()->inReview()->create([
        'origin_type' => ContentArticleOriginType::OfficialSource->value,
        'regulatory_status' => ContentArticleRegulatoryStatus::InForce->value,
        'effective_from' => null,
        'change_summary' => null,
        'applies_to' => null,
        'exam_impact' => null,
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Official->value,
            'title' => 'Komunikat urzędowy',
            'url' => 'https://example.test/official',
            'is_publicly_cited' => true,
        ]);

    $items = collect(app(ContentArticlePublicationChecklist::class)->items($article))
        ->keyBy('key');

    expect($items['origin']['state'])->toBe(ContentArticlePublicationChecklist::STATE_OK)
        ->and($items['regulatory']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['regulatory']['message'])->toContain('effective_from');

    $article->update([
        'effective_from' => '2026-10-01',
    ]);

    $items = collect(app(ContentArticlePublicationChecklist::class)->items($article->fresh()))
        ->keyBy('key');

    expect($items['regulatory']['state'])->toBe(ContentArticlePublicationChecklist::STATE_OK)
        ->and($items['regulatory_change_summary_missing']['state'])->toBe(ContentArticlePublicationChecklist::STATE_WARNING)
        ->and($items['regulatory_applies_to_missing']['state'])->toBe(ContentArticlePublicationChecklist::STATE_WARNING)
        ->and($items['regulatory_exam_impact_missing']['state'])->toBe(ContentArticlePublicationChecklist::STATE_WARNING);
});

test('regulatory and official-source origin cannot pass without a public official source url', function () {
    $article = ContentArticle::factory()->inReview()->create([
        'origin_type' => ContentArticleOriginType::OfficialSource->value,
        'regulatory_status' => ContentArticleRegulatoryStatus::Proposal->value,
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Interview->value,
            'title' => 'Rozmowa',
            'url' => null,
            'is_publicly_cited' => false,
        ]);

    $items = collect(app(ContentArticlePublicationChecklist::class)->items($article))
        ->keyBy('key');

    expect($items['origin']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['regulatory']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING);
});

test('publication media check re-inspects the stored managed hero instead of trusting database dimensions', function () {
    configureNewsroomFakeMediaDisk();

    $stored = app(NewsroomArticleMediaService::class)->store(newsroomTinyPngUpload());

    $article = ContentArticle::factory()->inReview()->create([
        'hero_image_path' => $stored['path'],
        'hero_image_alt' => 'Jednopikselowy obraz testowy',
        'hero_image_width' => 1200,
        'hero_image_height' => 630,
        'hero_focal_x' => 0.5,
        'hero_focal_y' => 0.5,
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    $hero = collect(app(ContentArticlePublicationChecklist::class)->items($article))
        ->firstWhere('key', 'hero');

    expect($hero['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($hero['message'])->toContain('dimensions do not match');

    $article->update([
        'hero_image_width' => 1,
        'hero_image_height' => 1,
    ]);

    $hero = collect(app(ContentArticlePublicationChecklist::class)->items($article->fresh()))
        ->firstWhere('key', 'hero');

    expect($hero['state'])->toBe(ContentArticlePublicationChecklist::STATE_OK);
});

test('apply public update persists provenance regulatory and verified media fields through the existing atomic path', function () {
    configureNewsroomFakeMediaDisk();

    $stored = app(NewsroomArticleMediaService::class)->store(newsroomTinyPngUpload());
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create();

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Official->value,
            'title' => 'Oficjalne źródło',
            'url' => 'https://example.test/official',
            'is_publicly_cited' => true,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->call('beginPublicUpdate')
        ->set('data.origin_type', ContentArticleOriginType::OfficialSource->value)
        ->set('data.regulatory_status', ContentArticleRegulatoryStatus::InForce->value)
        ->set('data.effective_from', '2026-10-01')
        ->set('data.change_summary', 'Nowe zasady egzaminu.')
        ->set('data.applies_to', 'Kandydaci na kategorię B.')
        ->set('data.exam_impact', 'Zmiana zakresu egzaminu.')
        ->set('data.hero_image_path', $stored['path'])
        ->set('data.hero_image_alt', 'Hero artykułu')
        ->set('data.hero_focal_x', 0.25)
        ->set('data.hero_focal_y', 0.75)
        ->set('data.image_credit', 'PrawkoNaRaz')
        ->set('data.image_license_note', 'Własny asset testowy.')
        ->call('applyPublicUpdate')
        ->assertSet('publicUpdateMode', false)
        ->assertHasNoErrors();

    $article = $article->fresh();

    expect($article->origin_type)->toBe(ContentArticleOriginType::OfficialSource)
        ->and($article->regulatory_status)->toBe(ContentArticleRegulatoryStatus::InForce)
        ->and($article->effective_from?->toDateString())->toBe('2026-10-01')
        ->and($article->hero_image_path)->toBe($stored['path'])
        ->and($article->hero_image_width)->toBe(1)
        ->and($article->hero_image_height)->toBe(1)
        ->and((float) $article->hero_focal_x)->toBe(0.25)
        ->and((float) $article->hero_focal_y)->toBe(0.75)
        ->and($article->image_license_note)->toBe('Własny asset testowy.');
});

test('article edit form exposes provenance media and crop preview controls', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->draft()->create();

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->assertSee('Pochodzenie i kontekst regulacyjny')
        ->assertSee('Media / art direction')
        ->assertSee('Hero image')
        ->assertSee('Focal X')
        ->assertSee('Podgląd cropów z focal point');
});

test('draft edit persists controlled provenance and regulatory metadata through ordinary save', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->draft()->create();

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->set('data.origin_type', ContentArticleOriginType::DataAnalysis->value)
        ->set('data.regulatory_status', ContentArticleRegulatoryStatus::Proposal->value)
        ->set('data.change_summary', 'Projekt zmienia zakres szkolenia.')
        ->set('data.applies_to', 'Kandydaci i OSK.')
        ->set('data.exam_impact', 'Możliwa zmiana pytań.')
        ->call('save')
        ->assertHasNoErrors();

    $article = $article->fresh();

    expect($article->origin_type)->toBe(ContentArticleOriginType::DataAnalysis)
        ->and($article->regulatory_status)->toBe(ContentArticleRegulatoryStatus::Proposal)
        ->and($article->change_summary)->toBe('Projekt zmienia zakres szkolenia.')
        ->and($article->applies_to)->toBe('Kandydaci i OSK.')
        ->and($article->exam_impact)->toBe('Możliwa zmiana pytań.');
});

test('public ordinary save can update private image license note without mutating public provenance', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'origin_type' => ContentArticleOriginType::Original->value,
        'image_license_note' => 'Stara notatka licencyjna.',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->set('data.origin_type', ContentArticleOriginType::Compiled->value)
        ->set('data.image_license_note', 'Nowa prywatna notatka licencyjna.')
        ->call('save')
        ->assertHasNoErrors();

    $article = $article->fresh();

    expect($article->origin_type)->toBe(ContentArticleOriginType::Original)
        ->and($article->image_license_note)->toBe('Nowa prywatna notatka licencyjna.');
});
