<?php

use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Filament\Resources\ContentArticles\Pages\CreateContentArticle;
use App\Filament\Resources\ContentArticles\Pages\EditContentArticle;
use App\Filament\Resources\ContentArticles\Pages\ListContentArticles;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentTopic;
use App\Models\LegalAct;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Models\User;
use App\Support\NewsroomArticleRelationsEditorAdapter;
use App\Support\NewsroomBodyContract;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
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
        ->assertTableColumnExists('freshness_status_display')
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
        ->and($article->editorial_note)->toBe('Wewnętrzna notatka.')
        ->and($article->body_blocks)->toBe([])
        ->and($article->body_schema_version)->toBe(NewsroomBodyContract::CURRENT_SCHEMA_VERSION);

    $audit = AuditLog::query()
        ->where('action', 'content_article.created')
        ->where('entity_id', (string) $article->id)
        ->latest('id')
        ->firstOrFail();

    expect($audit->actor_user_id)->toBe($admin->id)
        ->and($article->author_id)->toBe($author->id)
        ->and($audit->metadata['slug'])->toBe($article->slug)
        ->and($audit->entity_type)->toBe(ContentArticle::class);
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
        ->assertCanNotSeeTableRecords([$other]);

    Livewire::test(ListContentArticles::class)
        ->filterTable('type', ContentArticleType::News->value)
        ->filterTable('workflow_status', ContentArticleWorkflowStatus::Published->value)
        ->filterTable('category_id', $newsCategory->id)
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$other]);
});

test('freshness status distinguishes not scheduled fresh and overdue without changing workflow', function () {
    Carbon::setTestNow('2026-09-20 04:00:00');

    $article = ContentArticle::factory()->published()->create([
        'freshness_review_due_at' => null,
    ]);

    expect($article->freshnessStatus())->toBe('not_scheduled')
        ->and($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($article->isActivelyDistributed())->toBeTrue();

    $article->freshness_review_due_at = Carbon::parse('2026-09-21 04:00:00');

    expect($article->freshnessStatus())->toBe('fresh')
        ->and($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Published);

    $article->freshness_review_due_at = Carbon::parse('2026-09-20 03:59:59');

    expect($article->freshnessStatus())->toBe('overdue')
        ->and($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($article->isActivelyDistributed())->toBeTrue();

    Carbon::setTestNow();
});

test('ordinary public save can maintain freshness metadata without changing public content workflow or service timestamps', function () {
    Carbon::setTestNow('2026-09-20 04:00:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'title' => 'Publiczny tytuł freshness',
        'lead' => 'Publiczny lead freshness',
        'source_checked_at' => null,
        'freshness_review_due_at' => null,
        'last_substantive_update_at' => Carbon::parse('2026-09-18 09:00:00'),
        'public_state_changed_at' => Carbon::parse('2026-09-18 10:00:00'),
    ]);

    $originalPublishedAt = $article->published_at;
    $originalLastSubstantiveUpdateAt = $article->last_substantive_update_at;
    $originalPublicStateChangedAt = $article->public_state_changed_at;

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->set('data.title', 'Próba zmiany publicznego tytułu przez ordinary save')
        ->set('data.source_checked_at', '2026-09-20 03:30:00')
        ->set('data.freshness_review_due_at', '2026-09-20 03:45:00')
        ->call('save')
        ->assertHasNoErrors();

    $article = $article->fresh();

    expect($article->title)->toBe('Publiczny tytuł freshness')
        ->and($article->source_checked_at?->toDateTimeString())->toBe('2026-09-20 03:30:00')
        ->and($article->freshness_review_due_at?->toDateTimeString())->toBe('2026-09-20 03:45:00')
        ->and($article->freshnessStatus())->toBe('overdue')
        ->and($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($article->isActivelyDistributed())->toBeTrue()
        ->and($article->published_at?->equalTo($originalPublishedAt))->toBeTrue()
        ->and($article->last_substantive_update_at?->equalTo($originalLastSubstantiveUpdateAt))->toBeTrue()
        ->and($article->public_state_changed_at?->equalTo($originalPublicStateChangedAt))->toBeTrue();

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->set('data.freshness_review_due_at', null)
        ->call('save')
        ->assertHasNoErrors();

    $article = $article->fresh();

    expect($article->freshness_review_due_at)->toBeNull()
        ->and($article->freshnessStatus())->toBe('not_scheduled')
        ->and($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($article->isActivelyDistributed())->toBeTrue()
        ->and($article->last_substantive_update_at?->equalTo($originalLastSubstantiveUpdateAt))->toBeTrue()
        ->and($article->public_state_changed_at?->equalTo($originalPublicStateChangedAt))->toBeTrue();

    Carbon::setTestNow();
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

test('admin can persist ordered canonical body blocks through the builder adapter', function () {
    $undoBuilderFake = Builder::fake();

    try {
        $admin = User::factory()->admin()->create();
        $category = ContentCategory::factory()->create([
            'name' => 'Prawo',
            'slug' => 'prawo',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateContentArticle::class)
            ->set('data.type', ContentArticleType::News->value)
            ->set('data.category_id', $category->id)
            ->set('data.title', 'Artykuł blokowy')
            ->set('data.body_blocks', [
                [
                    'type' => 'context',
                    'data' => [
                        'variant' => 'uwaga',
                        'title' => 'Najpierw',
                        'text' => 'Pierwszy blok.',
                    ],
                ],
                [
                    'type' => 'table',
                    'data' => [
                        'caption' => 'Opłaty',
                        'headers' => ['Pozycja', 'Kwota'],
                        'rows' => [
                            ['cells' => ['Egzamin', '100 zł']],
                            ['cells' => ['Powtórka', '100 zł']],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $article = ContentArticle::query()
            ->where('title', 'Artykuł blokowy')
            ->firstOrFail();

        expect($article->body_schema_version)->toBe(1)
            ->and(array_column($article->body_blocks, 'type'))->toBe(['context', 'table'])
            ->and($article->body_blocks[1]['data']['rows'])->toBe([
                ['Egzamin', '100 zł'],
                ['Powtórka', '100 zł'],
            ]);
    } finally {
        $undoBuilderFake();
    }
});

test('stale draft edit is rejected before relationship state can overwrite a concurrent source change', function () {
    Carbon::setTestNow('2026-09-16 18:45:00');

    $undoRepeaterFake = Repeater::fake();

    try {
        $admin = User::factory()->admin()->create();
        $article = ContentArticle::factory()->draft()->create([
            'title' => 'Roboczy tytuł',
        ]);
        $source = ContentArticleSource::factory()
            ->for($article, 'article')
            ->create([
                'title' => 'Źródło załadowane do formularza',
                'url' => 'https://example.test/loaded',
            ]);

        $this->actingAs($admin);

        $component = Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()]);

        $source->update([
            'title' => 'Równoległa zmiana źródła',
            'url' => 'https://example.test/concurrent',
        ]);

        $component
            ->set('data.title', 'Nie wolno nadpisać')
            ->call('save')
            ->assertHasErrors(['data.title']);

        expect($article->fresh()->title)->toBe('Roboczy tytuł')
            ->and($source->fresh()->title)->toBe('Równoległa zmiana źródła')
            ->and($source->fresh()->url)->toBe('https://example.test/concurrent');
    } finally {
        $undoRepeaterFake();
        Carbon::setTestNow();
    }
});

test('draft edit preserves existing canonical body block keys across builder hydration', function () {
    $undoBuilderFake = Builder::fake();

    try {
        $admin = User::factory()->admin()->create();
        $article = ContentArticle::factory()->draft()->create([
            'body_schema_version' => 1,
            'body_blocks' => [
                [
                    'key' => 'stable-context-key',
                    'type' => 'context',
                    'data' => [
                        'variant' => 'uwaga',
                        'title' => null,
                        'text' => 'Treść z trwałym kluczem.',
                    ],
                ],
            ],
        ]);

        $this->actingAs($admin);

        Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
            ->set('data.editorial_note', 'Zmiana bez modyfikacji body.')
            ->call('save')
            ->assertHasNoErrors();

        $article = $article->fresh();

        expect($article->body_blocks[0]['key'])->toBe('stable-context-key')
            ->and($article->body_blocks[0]['data']['text'])->toBe('Treść z trwałym kluczem.');
    } finally {
        $undoBuilderFake();
    }
});

test('unsafe rich text is rejected by the server side body contract during create', function () {
    $undoBuilderFake = Builder::fake();

    try {
        $admin = User::factory()->admin()->create();
        $category = ContentCategory::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreateContentArticle::class)
            ->set('data.type', ContentArticleType::News->value)
            ->set('data.category_id', $category->id)
            ->set('data.title', 'Niebezpieczny body')
            ->set('data.body_blocks', [
                [
                    'type' => 'rich_text',
                    'data' => [
                        'content' => [
                            'type' => 'doc',
                            'content' => [[
                                'type' => 'paragraph',
                                'content' => [[
                                    'type' => 'text',
                                    'text' => 'Kliknij',
                                    'marks' => [[
                                        'type' => 'link',
                                        'attrs' => ['href' => 'javascript:alert(1)'],
                                    ]],
                                ]],
                            ]],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasErrors(['data.body_blocks']);

        expect(ContentArticle::query()->where('title', 'Niebezpieczny body')->exists())->toBeFalse();
    } finally {
        $undoBuilderFake();
    }
});

test('ordinary edit of publicly visible article cannot mutate public fields or body blocks', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł publiczny',
        'lead' => 'Lead publiczny',
        'body_schema_version' => 1,
        'body_blocks' => [
            [
                'type' => 'context',
                'data' => [
                    'variant' => 'uwaga',
                    'title' => null,
                    'text' => 'Treść publiczna',
                ],
            ],
        ],
        'editorial_note' => 'Stara notatka',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->set('data.title', 'Próba zmiany tytułu')
        ->set('data.lead', 'Próba zmiany leadu')
        ->set('data.body_blocks', [
            [
                'type' => 'context',
                'data' => [
                    'variant' => 'uwaga',
                    'title' => null,
                    'text' => 'Próba zmiany body',
                ],
            ],
        ])
        ->set('data.editorial_note', 'Nowa notatka wewnętrzna')
        ->call('save')
        ->assertHasNoErrors();

    $article = $article->fresh();

    expect($article->title)->toBe('Tytuł publiczny')
        ->and($article->lead)->toBe('Lead publiczny')
        ->and($article->body_blocks[0]['data']['text'])->toBe('Treść publiczna')
        ->and($article->editorial_note)->toBe('Nowa notatka wewnętrzna');
});

test('explicit public update mode applies validated public fields and records the user actor', function () {
    Carbon::setTestNow('2026-09-16 19:00:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł przed public update',
        'lead' => 'Lead przed public update',
    ]);
    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Official->value,
            'title' => 'Źródło publiczne',
            'url' => 'https://example.test/public-source',
            'is_publicly_cited' => true,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->call('beginPublicUpdate')
        ->assertSet('publicUpdateMode', true)
        ->set('data.title', 'Tytuł po Apply public update')
        ->set('data.lead', 'Lead po Apply public update')
        ->call('applyPublicUpdate')
        ->assertSet('publicUpdateMode', false)
        ->assertHasNoErrors();

    $article = $article->fresh();
    $audit = AuditLog::query()
        ->where('action', 'content_article.public_updated')
        ->where('entity_id', (string) $article->id)
        ->sole();

    expect($article->title)->toBe('Tytuł po Apply public update')
        ->and($article->lead)->toBe('Lead po Apply public update')
        ->and($article->last_substantive_update_at?->toDateTimeString())->toBe('2026-09-16 19:00:00')
        ->and($audit->actor_user_id)->toBe($admin->id)
        ->and($audit->metadata)->not->toHaveKey('body_blocks')
        ->and($audit->metadata)->not->toHaveKey('lead')
        ->and($audit->metadata)->not->toHaveKey('editorial_note');

    Carbon::setTestNow();
});

test('admin correction mode publishes content and correction note through the dedicated action', function () {
    Carbon::setTestNow('2026-09-16 19:30:00');

    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł przed korektą CMS',
        'lead' => 'Lead przed korektą CMS',
    ]);
    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create([
            'source_type' => ContentArticleSourceType::Official->value,
            'title' => 'Źródło korekty CMS',
            'url' => 'https://example.test/correction-cms',
            'is_publicly_cited' => true,
        ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->call('beginCorrection')
        ->assertSet('publicUpdateMode', true)
        ->assertSet('correctionMode', true)
        ->set('data.title', 'Tytuł po korekcie CMS')
        ->set('data.lead', 'Lead po korekcie CMS')
        ->call('applyCorrection', 'Poprawiono istotną informację w materiale.')
        ->assertSet('publicUpdateMode', false)
        ->assertSet('correctionMode', false)
        ->assertHasNoErrors();

    $article = $article->fresh();
    $audit = AuditLog::query()
        ->where('action', 'content_article.corrected')
        ->where('entity_id', (string) $article->id)
        ->sole();

    expect($article->title)->toBe('Tytuł po korekcie CMS')
        ->and($article->lead)->toBe('Lead po korekcie CMS')
        ->and($article->correction_note)->toBe('Poprawiono istotną informację w materiale.')
        ->and($article->last_substantive_update_at?->toDateTimeString())->toBe('2026-09-16 19:30:00')
        ->and($audit->actor_user_id)->toBe($admin->id)
        ->and($audit->metadata['correction_applied'])->toBeTrue()
        ->and($audit->metadata)->not->toHaveKey('correction_note')
        ->and($audit->metadata)->not->toHaveKey('body_blocks')
        ->and($audit->metadata)->not->toHaveKey('lead');

    Carbon::setTestNow();
});

test('admin can persist ordered article sources including private evidence without a url', function () {
    $undoRepeaterFake = Repeater::fake();

    try {
        $admin = User::factory()->admin()->create();
        $category = ContentCategory::factory()->create();
        $author = ContentAuthor::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreateContentArticle::class)
            ->set('data.type', ContentArticleType::News->value)
            ->set('data.category_id', $category->id)
            ->set('data.author_id', $author->id)
            ->set('data.title', 'News ze źródłami')
            ->set('data.sources', [
                [
                    'source_type' => ContentArticleSourceType::Official->value,
                    'publisher' => 'Ministerstwo Infrastruktury',
                    'title' => 'Oficjalny komunikat',
                    'url' => 'https://www.gov.pl/example',
                    'published_at' => null,
                    'accessed_at' => null,
                    'is_primary' => true,
                    'is_official' => true,
                    'is_publicly_cited' => true,
                    'note' => null,
                ],
                [
                    'source_type' => ContentArticleSourceType::Interview->value,
                    'publisher' => 'Instruktor',
                    'title' => 'Rozmowa redakcyjna',
                    'url' => null,
                    'published_at' => null,
                    'accessed_at' => null,
                    'is_primary' => false,
                    'is_official' => false,
                    'is_publicly_cited' => false,
                    'note' => 'Kontakt wewnętrzny.',
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $article = ContentArticle::query()->where('title', 'News ze źródłami')->firstOrFail();
        $sources = $article->sources()->get();

        expect($sources)->toHaveCount(2)
            ->and($sources->pluck('title')->all())->toBe([
                'Oficjalny komunikat',
                'Rozmowa redakcyjna',
            ])
            ->and($sources[0]->source_type)->toBe(ContentArticleSourceType::Official)
            ->and($sources[0]->is_primary)->toBeTrue()
            ->and($sources[0]->is_official)->toBeTrue()
            ->and($sources[1]->source_type)->toBe(ContentArticleSourceType::Interview)
            ->and($sources[1]->url)->toBeNull()
            ->and($sources[1]->is_publicly_cited)->toBeFalse()
            ->and($sources[1]->note)->toBe('Kontakt wewnętrzny.');
    } finally {
        $undoRepeaterFake();
    }
});

test('ordinary public article edit cannot mutate source relationship records', function () {
    $undoRepeaterFake = Repeater::fake();

    try {
        $admin = User::factory()->admin()->create();
        $article = ContentArticle::factory()->published()->create();
        $source = ContentArticleSource::factory()->for($article, 'article')->create([
            'title' => 'Źródło publiczne',
            'url' => 'https://example.test/original',
            'sort_order' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
            ->set('data.sources', [
                [
                    'source_type' => ContentArticleSourceType::Media->value,
                    'publisher' => 'Zmiana',
                    'title' => 'Próba podmiany',
                    'url' => 'https://example.test/changed',
                    'is_primary' => true,
                    'is_official' => false,
                    'is_publicly_cited' => true,
                    'note' => null,
                ],
            ])
            ->set('data.editorial_note', 'Tylko notatka może się zmienić.')
            ->call('save')
            ->assertHasNoErrors();

        $source = $source->fresh();

        expect($article->fresh()->sources()->count())->toBe(1)
            ->and($source->title)->toBe('Źródło publiczne')
            ->and($source->url)->toBe('https://example.test/original')
            ->and($article->fresh()->editorial_note)->toBe('Tylko notatka może się zmienić.');
    } finally {
        $undoRepeaterFake();
    }
});

test('admin can persist ordered article relations and topic membership', function () {
    $undoRepeaterFake = Repeater::fake();

    try {
        $admin = User::factory()->admin()->create();
        $category = ContentCategory::factory()->create();
        $author = ContentAuthor::factory()->create();
        $topicA = ContentTopic::factory()->create(['title' => 'Egzaminy praktyczne']);
        $topicB = ContentTopic::factory()->published()->create(['title' => 'Zmiany 2026']);
        $questionA = Question::factory()->create([
            'external_id' => 'REL-Q-A',
            'prompt' => 'Pierwsze pytanie relacyjne?',
        ]);
        $questionB = Question::factory()->create([
            'external_id' => 'REL-Q-B',
            'prompt' => 'Drugie pytanie relacyjne?',
        ]);
        $legalAct = LegalAct::query()->create([
            'slug' => 'relacje-akt',
            'title' => 'Ustawa testowa relacji',
            'short_title' => 'UTR',
            'source_url' => 'https://example.test/legal-act',
            'status' => LegalAct::STATUS_VERIFIED,
        ]);
        $legalUnit = LegalUnit::query()->create([
            'legal_act_id' => $legalAct->id,
            'type' => 'article',
            'label' => 'Art. 1',
            'slug' => 'art-1',
            'title' => 'Jednostka relacyjna',
            'source_url' => 'https://example.test/legal-act#art-1',
            'status' => LegalUnit::STATUS_VERIFIED,
        ]);
        $trafficSign = TrafficSign::factory()->published()->create([
            'code' => 'A-99',
            'slug' => 'a-99-test-relacji',
            'name' => 'Test relacji',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateContentArticle::class)
            ->set('data.type', ContentArticleType::Guide->value)
            ->set('data.category_id', $category->id)
            ->set('data.author_id', $author->id)
            ->set('data.title', 'Artykuł z relacjami')
            ->set('data.topic_ids', [$topicB->id, $topicA->id])
            ->set('data.question_relations', [
                [
                    'question_id' => $questionB->id,
                    'relation_type' => 'practice',
                    'note' => 'Najpierw ćwiczenie.',
                ],
                [
                    'question_id' => $questionA->id,
                    'relation_type' => 'background',
                    'note' => null,
                ],
            ])
            ->set('data.legal_unit_relations', [
                [
                    'legal_unit_id' => $legalUnit->id,
                    'relation_type' => 'direct_basis',
                    'note' => 'Podstawa materiału.',
                ],
            ])
            ->set('data.traffic_sign_relations', [
                [
                    'traffic_sign_id' => $trafficSign->id,
                    'relation_type' => 'example',
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $article = ContentArticle::query()->where('title', 'Artykuł z relacjami')->firstOrFail();
        $questions = $article->questions()->get();
        $legalUnits = $article->legalUnits()->get();
        $signs = $article->trafficSigns()->get();

        expect($questions->pluck('id')->all())->toBe([$questionB->id, $questionA->id])
            ->and($questions[0]->pivot?->sort_order)->toBe(0)
            ->and($questions[0]->pivot?->relation_type)->toBe('practice')
            ->and($questions[0]->pivot?->note)->toBe('Najpierw ćwiczenie.')
            ->and($questions[1]->pivot?->sort_order)->toBe(1)
            ->and($legalUnits)->toHaveCount(1)
            ->and($legalUnits[0]->pivot?->relation_type)->toBe('direct_basis')
            ->and($legalUnits[0]->pivot?->note)->toBe('Podstawa materiału.')
            ->and($signs)->toHaveCount(1)
            ->and($signs[0]->pivot?->relation_type)->toBe('example')
            ->and($article->topics()->pluck('content_topics.id')->sort()->values()->all())
            ->toBe(collect([$topicA->id, $topicB->id])->sort()->values()->all())
            ->and($questionA->fresh()->prompt)->toBe('Pierwsze pytanie relacyjne?')
            ->and($legalUnit->fresh()->official_excerpt)->toBeNull();
    } finally {
        $undoRepeaterFake();
    }
});

test('ordinary public article edit cannot mutate article relations or topic membership', function () {
    $undoRepeaterFake = Repeater::fake();

    try {
        $admin = User::factory()->admin()->create();
        $article = ContentArticle::factory()->published()->create();
        $questionOriginal = Question::factory()->create();
        $questionReplacement = Question::factory()->create();
        $topicOriginal = ContentTopic::factory()->create();
        $topicReplacement = ContentTopic::factory()->create();

        $article->questions()->attach($questionOriginal->id, [
            'relation_type' => 'direct',
            'sort_order' => 0,
            'note' => null,
        ]);
        $article->topics()->attach($topicOriginal->id);

        $this->actingAs($admin);

        Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
            ->set('data.question_relations', [
                [
                    'question_id' => $questionReplacement->id,
                    'relation_type' => 'related',
                    'note' => 'Nie powinno się zapisać.',
                ],
            ])
            ->set('data.topic_ids', [$topicReplacement->id])
            ->set('data.editorial_note', 'Relacje pozostają bez zmian.')
            ->call('save')
            ->assertHasNoErrors();

        $article = $article->fresh();

        expect($article->questions()->pluck('questions.id')->all())->toBe([$questionOriginal->id])
            ->and($article->topics()->pluck('content_topics.id')->all())->toBe([$topicOriginal->id])
            ->and($article->editorial_note)->toBe('Relacje pozostają bez zmian.');
    } finally {
        $undoRepeaterFake();
    }
});

test('relations adapter rejects duplicate targets invalid relation types and missing records', function () {
    $question = Question::factory()->create();

    expect(fn () => NewsroomArticleRelationsEditorAdapter::extractArticleData([
        'question_relations' => [
            [
                'question_id' => $question->id,
                'relation_type' => 'direct',
            ],
            [
                'question_id' => $question->id,
                'relation_type' => 'related',
            ],
        ],
    ]))->toThrow(ValidationException::class);

    expect(fn () => NewsroomArticleRelationsEditorAdapter::extractArticleData([
        'question_relations' => [
            [
                'question_id' => $question->id,
                'relation_type' => 'unsupported',
            ],
        ],
    ]))->toThrow(ValidationException::class);

    expect(fn () => NewsroomArticleRelationsEditorAdapter::extractArticleData([
        'topic_ids' => [999999999],
    ]))->toThrow(ValidationException::class);
});

test('relations sync touches parent article edit token', function () {
    Carbon::setTestNow('2026-09-16 11:00:00');

    try {
        $article = ContentArticle::factory()->create();
        $question = Question::factory()->create();
        $before = $article->fresh()->updated_at;

        Carbon::setTestNow('2026-09-16 11:01:00');

        NewsroomArticleRelationsEditorAdapter::sync($article, [
            'questions' => [
                [
                    'question_id' => $question->id,
                    'relation_type' => 'related',
                    'note' => null,
                ],
            ],
            'legal_units' => [],
            'traffic_signs' => [],
            'topic_ids' => [],
        ]);

        expect($article->fresh()->updated_at?->gt($before))->toBeTrue()
            ->and($article->questions()->first()?->pivot?->sort_order)->toBe(0);
    } finally {
        Carbon::setTestNow();
    }
});
