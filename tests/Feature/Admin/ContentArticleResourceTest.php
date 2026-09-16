<?php

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Filament\Resources\ContentArticles\Pages\CreateContentArticle;
use App\Filament\Resources\ContentArticles\Pages\EditContentArticle;
use App\Filament\Resources\ContentArticles\Pages\ListContentArticles;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\User;
use Livewire\Livewire;

test('admin can access content article resource with eager loaded editorial relations', function () {
    $admin = User::factory()->admin()->create();
    $category = ContentCategory::factory()->create();
    $author = ContentAuthor::factory()->create();
    $reviewer = ContentAuthor::factory()->create();

    $article = ContentArticle::factory()
        ->for($category, 'category')
        ->for($author, 'author')
        ->for($reviewer, 'reviewer')
        ->create();

    $this->actingAs($admin);

    $record = ContentArticleResource::getEloquentQuery()
        ->whereKey($article->getKey())
        ->firstOrFail();

    expect($record->relationLoaded('category'))->toBeTrue()
        ->and($record->relationLoaded('author'))->toBeTrue()
        ->and($record->relationLoaded('reviewer'))->toBeTrue()
        ->and($record->category?->id)->toBe($category->id)
        ->and($record->author?->id)->toBe($author->id)
        ->and($record->reviewer?->id)->toBe($reviewer->id);

    Livewire::test(ListContentArticles::class)
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('type')
        ->assertTableColumnExists('category.name')
        ->assertTableColumnExists('workflow_status')
        ->assertTableColumnExists('author.name')
        ->assertTableColumnExists('reviewer.name')
        ->assertTableFilterExists('workflow_status')
        ->assertTableFilterExists('type')
        ->assertTableFilterExists('category_id')
        ->assertTableFilterExists('author_id')
        ->assertTableFilterExists('reviewer_id')
        ->assertTableFilterExists('is_featured')
        ->assertTableFilterExists('is_breaking')
        ->assertTableFilterExists('scheduled')
        ->assertTableFilterExists('freshness_overdue')
        ->assertTableFilterExists('published_at');
});

test('moderator and student cannot access content article resource', function () {
    $moderator = User::factory()->moderator()->create();
    $student = User::factory()->create();

    $this->actingAs($moderator)
        ->get(ContentArticleResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(ContentArticleResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});

test('admin creates draft through slug service while user actor stays separate from content author', function () {
    $admin = User::factory()->admin()->create();
    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy',
    ]);
    $author = ContentAuthor::factory()->create([
        'name' => 'Anna Redaktor',
        'slug' => 'anna-redaktor',
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateContentArticle::class)
        ->set('data.type', ContentArticleType::News->value)
        ->set('data.category_id', $category->id)
        ->set('data.title', 'Nowe zasady egzaminu praktycznego')
        ->set('data.author_id', $author->id)
        ->set('data.lead', 'Podstawowe informacje o zmianach.')
        ->set('data.editorial_note', 'Wewnętrzna notatka.')
        ->call('create')
        ->assertHasNoErrors();

    $article = ContentArticle::query()
        ->where('title', 'Nowe zasady egzaminu praktycznego')
        ->firstOrFail();

    expect($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Draft)
        ->and($article->slug)->toBe('nowe-zasady-egzaminu-praktycznego')
        ->and($article->category_id)->toBe($category->id)
        ->and($article->author_id)->toBe($author->id)
        ->and($article->editorial_note)->toBe('Wewnętrzna notatka.');

    $audit = AuditLog::query()
        ->where('action', 'content_article.created')
        ->where('entity_id', (string) $article->id)
        ->latest('id')
        ->firstOrFail();

    expect($audit->actor_user_id)->toBe($admin->id)
        ->and($article->author_id)->toBe($author->id)
        ->and($audit->actor_user_id)->not->toBe($article->author_id)
        ->and($audit->metadata['slug'])->toBe($article->slug);
});

test('article list search and filters narrow records', function () {
    $admin = User::factory()->admin()->create();
    $newsCategory = ContentCategory::factory()->create([
        'name' => 'Przepisy',
        'slug' => 'przepisy',
    ]);
    $guideCategory = ContentCategory::factory()->create([
        'name' => 'Prawo jazdy',
        'slug' => 'prawo-jazdy',
    ]);

    $matching = ContentArticle::factory()
        ->published()
        ->for($newsCategory, 'category')
        ->create([
            'type' => ContentArticleType::News,
            'title' => 'Zmiany w przepisach 2026',
            'slug' => 'zmiany-w-przepisach-2026',
            'lead' => 'Nowe wymagania dla kierowców.',
        ]);

    $other = ContentArticle::factory()
        ->guide()
        ->for($guideCategory, 'category')
        ->create([
            'title' => 'Jak przygotować się do egzaminu',
            'slug' => 'jak-przygotowac-sie-do-egzaminu',
        ]);

    $this->actingAs($admin);

    Livewire::test(ListContentArticles::class)
        ->searchTable('Zmiany w przepisach')
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$other])
        ->resetTableSearch()
        ->filterTable('type', ContentArticleType::News->value)
        ->filterTable('workflow_status', ContentArticleWorkflowStatus::Published->value)
        ->filterTable('category_id', $newsCategory->id)
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$other]);
});

test('draft edit routes slug and type changes through domain service', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->draft()->guide()->create([
        'title' => 'Roboczy poradnik',
        'slug' => 'roboczy-poradnik',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->set('data.type', ContentArticleType::News->value)
        ->set('data.slug', 'roboczy-news')
        ->set('data.title', 'Roboczy news')
        ->call('save')
        ->assertHasNoErrors();

    $article = $article->fresh();

    expect($article->type)->toBe(ContentArticleType::News)
        ->and($article->slug)->toBe('roboczy-news')
        ->and($article->title)->toBe('Roboczy news');

    expect(AuditLog::query()
        ->where('entity_type', ContentArticle::class)
        ->where('entity_id', (string) $article->id)
        ->where('action', 'content_article.type_changed')
        ->where('actor_user_id', $admin->id)
        ->exists())->toBeTrue()
        ->and(AuditLog::query()
            ->where('entity_type', ContentArticle::class)
            ->where('entity_id', (string) $article->id)
            ->where('action', 'content_article.slug_changed')
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});

test('ordinary edit of publicly visible article cannot mutate public fields', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł publiczny',
        'lead' => 'Lead publiczny',
        'editorial_note' => 'Stara notatka',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->set('data.title', 'Próba zmiany tytułu')
        ->set('data.lead', 'Próba zmiany leadu')
        ->set('data.editorial_note', 'Nowa notatka wewnętrzna')
        ->call('save')
        ->assertHasNoErrors();

    $article = $article->fresh();

    expect($article->title)->toBe('Tytuł publiczny')
        ->and($article->lead)->toBe('Lead publiczny')
        ->and($article->editorial_note)->toBe('Nowa notatka wewnętrzna');
});
