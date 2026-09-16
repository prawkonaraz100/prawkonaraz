<?php

use App\Filament\Resources\ContentTopics\ContentTopicResource;
use App\Filament\Resources\ContentTopics\Pages\CreateContentTopic;
use App\Filament\Resources\ContentTopics\Pages\EditContentTopic;
use App\Filament\Resources\ContentTopics\Pages\ListContentTopics;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentTopic;
use App\Models\User;
use App\Support\ContentTopicPublishingService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('admin can access content topic resource with corpus health counts', function () {
    $admin = User::factory()->admin()->create();
    $topic = ContentTopic::factory()->create([
        'title' => 'Zmiany w egzaminach',
        'slug' => 'zmiany-w-egzaminach',
    ]);
    $eligible = ContentArticle::factory()->published()->count(3)->create();
    $draft = ContentArticle::factory()->draft()->create();

    $topic->articles()->attach([...$eligible->pluck('id')->all(), $draft->id]);

    $this->actingAs($admin);

    $record = ContentTopicResource::getEloquentQuery()
        ->whereKey($topic->getKey())
        ->firstOrFail();

    expect((int) $record->articles_count)->toBe(4)
        ->and((int) $record->eligible_articles_count)->toBe(3);

    Livewire::test(ListContentTopics::class)
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('corpus_summary')
        ->assertTableColumnExists('editorially_promotable')
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit')
        ->assertTableActionExists('delete');
});

test('non admin cannot access content topic resource', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(ContentTopicResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});

test('admin can create a draft topic with article membership and featured article', function () {
    $admin = User::factory()->admin()->create();
    $articles = ContentArticle::factory()->published()->count(3)->create();
    $featured = $articles->first();

    $this->actingAs($admin);

    Livewire::test(CreateContentTopic::class)
        ->set('data.title', 'Prawo jazdy 2026')
        ->set('data.slug', 'prawo-jazdy-2026')
        ->set('data.description', 'Kontrolowany dossier zmian dotyczących prawa jazdy.')
        ->set('data.article_ids', $articles->pluck('id')->all())
        ->set('data.featured_article_id', $featured->id)
        ->set('data.seo_title', 'Prawo jazdy 2026')
        ->set('data.seo_description', 'Najważniejsze zmiany i materiały.')
        ->call('create')
        ->assertHasNoErrors();

    $topic = ContentTopic::query()->where('slug', 'prawo-jazdy-2026')->firstOrFail();

    expect($topic->status)->toBe(ContentTopic::STATUS_DRAFT)
        ->and($topic->published_at)->toBeNull()
        ->and($topic->featured_article_id)->toBe($featured->id)
        ->and($topic->articles()->count())->toBe(3);
});

test('featured article must belong to the selected topic corpus in admin form', function () {
    $admin = User::factory()->admin()->create();
    $included = ContentArticle::factory()->published()->create();
    $outside = ContentArticle::factory()->published()->create();

    $this->actingAs($admin);

    Livewire::test(CreateContentTopic::class)
        ->set('data.title', 'Egzaminy praktyczne')
        ->set('data.slug', 'egzaminy-praktyczne')
        ->set('data.description', 'Opis topicu.')
        ->set('data.article_ids', [$included->id])
        ->set('data.featured_article_id', $outside->id)
        ->call('create')
        ->assertHasErrors(['data.featured_article_id']);

    expect(ContentTopic::query()->where('slug', 'egzaminy-praktyczne')->exists())->toBeFalse();
});

test('topic publishing service enforces description corpus and featured eligibility', function () {
    $admin = User::factory()->admin()->create();
    $topic = ContentTopic::factory()->create([
        'description' => '',
    ]);
    $articles = ContentArticle::factory()->published()->count(3)->create();
    $topic->articles()->attach($articles->pluck('id')->all());

    $service = app(ContentTopicPublishingService::class);

    expect(fn () => $service->publish($topic, $admin))
        ->toThrow(DomainException::class);

    $topic->update([
        'description' => 'Redakcyjny opis topicu.',
    ]);

    $outside = ContentArticle::factory()->published()->create();
    $topic->update([
        'featured_article_id' => $outside->id,
    ]);

    expect(fn () => $service->publish($topic->fresh(), $admin))
        ->toThrow(DomainException::class);

    $featured = $articles->first();
    $topic->update([
        'featured_article_id' => $featured->id,
    ]);

    $published = $service->publish($topic->fresh(), $admin);

    expect($published->status)->toBe(ContentTopic::STATUS_PUBLISHED)
        ->and($published->published_at)->not->toBeNull()
        ->and($published->meetsPublicationRequirements())->toBeTrue()
        ->and($published->isEditoriallyPromotable())->toBeTrue()
        ->and(AuditLog::query()
            ->where('action', 'content_topic.published')
            ->where('entity_id', (string) $topic->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});

test('admin publish archive and republish actions use service controlled topic workflow', function () {
    $admin = User::factory()->admin()->create();
    $topic = ContentTopic::factory()->create([
        'description' => 'Pełny opis topicu.',
    ]);
    $articles = ContentArticle::factory()->published()->count(3)->create();
    $topic->articles()->attach($articles->pluck('id')->all());
    $topic->update([
        'featured_article_id' => $articles->first()->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditContentTopic::class, ['record' => $topic->getRouteKey()])
        ->callAction('publishTopic');

    $firstPublishedAt = $topic->fresh()->published_at?->toDateTimeString();

    expect($topic->fresh()->status)->toBe(ContentTopic::STATUS_PUBLISHED)
        ->and($firstPublishedAt)->not->toBeNull();

    Livewire::test(EditContentTopic::class, ['record' => $topic->getRouteKey()])
        ->callAction('archiveTopic');

    expect($topic->fresh()->status)->toBe(ContentTopic::STATUS_ARCHIVED)
        ->and($topic->fresh()->published_at?->toDateTimeString())->toBe($firstPublishedAt);

    Livewire::test(EditContentTopic::class, ['record' => $topic->getRouteKey()])
        ->callAction('republishTopic');

    expect($topic->fresh()->status)->toBe(ContentTopic::STATUS_PUBLISHED)
        ->and($topic->fresh()->published_at?->toDateTimeString())->toBe($firstPublishedAt)
        ->and(AuditLog::query()
            ->where('action', 'content_topic.archived')
            ->where('entity_id', (string) $topic->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue()
        ->and(AuditLog::query()
            ->where('action', 'content_topic.republished')
            ->where('entity_id', (string) $topic->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});

test('published topic can fall below corpus baseline without automatic status flip', function () {
    $topic = ContentTopic::factory()->create([
        'description' => 'Opis topicu.',
    ]);
    $articles = ContentArticle::factory()->published()->count(3)->create();
    $topic->articles()->attach($articles->pluck('id')->all());

    $published = app(ContentTopicPublishingService::class)->publish($topic);

    $published->articles()->detach($articles->take(2)->pluck('id')->all());
    $published = $published->fresh();

    expect($published->status)->toBe(ContentTopic::STATUS_PUBLISHED)
        ->and($published->isPubliclyVisible())->toBeTrue()
        ->and($published->isCorpusBelowBaseline())->toBeTrue()
        ->and($published->isEditoriallyPromotable())->toBeFalse();
});

test('republish rechecks current corpus baseline', function () {
    $topic = ContentTopic::factory()->create([
        'description' => 'Opis topicu.',
    ]);
    $articles = ContentArticle::factory()->published()->count(3)->create();
    $topic->articles()->attach($articles->pluck('id')->all());

    $service = app(ContentTopicPublishingService::class);
    $published = $service->publish($topic);
    $archived = $service->archive($published);

    $archived->articles()->detach($articles->take(2)->pluck('id')->all());

    expect(fn () => $service->republish($archived->fresh()))
        ->toThrow(DomainException::class);

    expect($archived->fresh()->status)->toBe(ContentTopic::STATUS_ARCHIVED);
});

test('topic slug is mutable only before first publication', function () {
    $topic = ContentTopic::factory()->create([
        'slug' => 'stary-topic',
    ]);

    $topic->update([
        'slug' => 'nowy-topic',
    ]);

    expect($topic->fresh()->slug)->toBe('nowy-topic');

    $articles = ContentArticle::factory()->published()->count(3)->create();
    $topic->articles()->attach($articles->pluck('id')->all());
    app(ContentTopicPublishingService::class)->publish($topic->fresh());

    $topic = $topic->fresh();
    $topic->slug = 'po-publikacji';

    expect(fn () => $topic->save())
        ->toThrow(ValidationException::class);

    expect($topic->fresh()->slug)->toBe('nowy-topic');
});

test('published topic cannot be deleted while never published draft can be deleted', function () {
    $draft = ContentTopic::factory()->create([
        'slug' => 'draft-do-usuniecia',
    ]);
    $published = ContentTopic::factory()->published()->create([
        'slug' => 'historyczny-topic',
    ]);

    $draft->delete();

    expect(ContentTopic::query()->whereKey($draft->id)->exists())->toBeFalse();

    expect(fn () => $published->delete())
        ->toThrow(ValidationException::class);

    expect(ContentTopic::query()->whereKey($published->id)->exists())->toBeTrue();
});
