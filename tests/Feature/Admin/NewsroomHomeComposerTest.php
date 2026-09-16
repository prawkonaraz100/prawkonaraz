<?php

use App\Enums\ContentArticleWorkflowStatus;
use App\Filament\Pages\NewsroomHomeComposer;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use App\Models\User;
use App\Support\ContentArticlePublishingService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function (): void {
    Carbon::setTestNow();
});

function composerScheduledArticleForPreview(
    ContentCategory $category,
    string $scheduledFor,
): ContentArticle {
    $article = ContentArticle::factory()->inReview()->create([
        'category_id' => $category->id,
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    $service = app(ContentArticlePublishingService::class);
    $reviewed = $service->markReviewed($article);

    return $service->schedule($reviewed, Carbon::parse($scheduledFor));
}

test('admin home composer exposes fixed slots fallback and article search', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $admin = User::factory()->admin()->create();
    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy',
    ]);

    $fallback = ContentArticle::factory()->published()->featured()->create([
        'category_id' => $category->id,
        'title' => 'Fallback lead newsroomu',
    ]);

    $draft = ContentArticle::factory()->draft()->create([
        'category_id' => $category->id,
        'title' => 'Roboczy materiał wyszukiwany',
        'slug' => 'roboczy-material-wyszukiwany',
    ]);

    $this->actingAs($admin)
        ->get(NewsroomHomeComposer::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Układ /aktualnosci');

    $component = Livewire::test(NewsroomHomeComposer::class)
        ->assertSee('Lead')
        ->assertSee('Secondary 1')
        ->assertSee('Secondary 4')
        ->assertSee('Kategoria: Egzaminy')
        ->assertSee('Poradniki — lead')
        ->assertSee('Ważne teraz 6')
        ->assertSee($fallback->title);

    $component
        ->set('searches.lead--global--0', 'Roboczy materiał')
        ->assertSee($draft->title);
});

test('moderator and ordinary user cannot access newsroom home composer', function () {
    foreach ([
        User::factory()->moderator()->create(),
        User::factory()->create(),
    ] as $user) {
        $this->actingAs($user)
            ->get(NewsroomHomeComposer::getUrl(panel: 'admin'))
            ->assertForbidden();
    }
});

test('composer saves fixed placement with audit actor and can return slot to fallback', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'title' => 'Ręczny lead',
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(NewsroomHomeComposer::class)
        ->call('chooseArticle', 'lead--global--0', $article->id)
        ->call('saveSlot', 'lead--global--0');

    $placement = ContentHomePlacement::query()
        ->where('slot_key', ContentHomePlacement::SLOT_LEAD)
        ->where('position', 0)
        ->sole();

    expect($placement->article_id)->toBe($article->id)
        ->and($placement->created_by_user_id)->toBe($admin->id)
        ->and($placement->updated_by_user_id)->toBe($admin->id)
        ->and(AuditLog::query()
            ->where('action', 'content_home_placement.created')
            ->where('entity_id', (string) $placement->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();

    $component->call('removePlacement', 'lead--global--0');

    expect(ContentHomePlacement::query()->whereKey($placement->id)->exists())->toBeFalse()
        ->and(AuditLog::query()
            ->where('action', 'content_home_placement.deleted')
            ->where('entity_id', (string) $placement->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});

test('composer rejects stale placement update instead of overwriting concurrent state', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $admin = User::factory()->admin()->create();
    $original = ContentArticle::factory()->published()->create();
    $concurrent = ContentArticle::factory()->published()->create();
    $attempted = ContentArticle::factory()->published()->create();

    $placement = ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'position' => 0,
        'article_id' => $original->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(NewsroomHomeComposer::class);

    ContentHomePlacement::query()
        ->whereKey($placement->id)
        ->update([
            'article_id' => $concurrent->id,
            'updated_at' => $placement->updated_at,
        ]);

    $component
        ->call('chooseArticle', 'lead--global--0', $attempted->id)
        ->call('saveSlot', 'lead--global--0');

    expect($placement->fresh()->article_id)->toBe($concurrent->id)
        ->and(AuditLog::query()
            ->where('action', 'content_home_placement.updated')
            ->where('entity_id', (string) $placement->id)
            ->exists())->toBeFalse();
});

test('composer warns when one article is selected for more than one card slot', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create();

    $this->actingAs($admin);

    Livewire::test(NewsroomHomeComposer::class)
        ->call('chooseArticle', 'lead--global--0', $article->id)
        ->call('chooseArticle', 'secondary--global--0', $article->id)
        ->assertSee('Ten artykuł jest wybrany także w innym card slocie');
});

test('future newsroom home preview is admin only private no-store and uses scheduled composition without mutation', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-HOME-PREVIEW');
    config()->set('services.google_analytics.consent_required', true);

    $category = ContentCategory::factory()->create();
    $scheduled = composerScheduledArticleForPreview($category, '2026-09-16 10:00:00');

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $scheduled->id,
    ]);

    $url = route('admin.newsroom.home-preview', [
        'at' => '2026-09-16 10:00:00',
    ]);

    $this->get($url)->assertRedirect();

    $this->actingAs(User::factory()->moderator()->create())
        ->get($url)
        ->assertForbidden();

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get($url);

    $response
        ->assertOk()
        ->assertSee('Podgląd /aktualnosci — niepubliczne')
        ->assertSee($scheduled->title)
        ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('google-analytics-consent', false);

    expect((string) $response->headers->get('Cache-Control'))
        ->toContain('private')
        ->toContain('no-store')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow')
        ->and($scheduled->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and($scheduled->fresh()->first_published_at)->toBeNull();
});
