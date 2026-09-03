<?php

use App\Filament\Pages\QuestionLearningOrder as QuestionLearningOrderPage;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionLearningOrderItem;
use App\Models\QuestionLearningOrderSet;
use App\Models\QuestionMedia;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryLabel;
use App\Models\StudySession;
use App\Models\User;
use App\Support\QuestionLearningOrderAdminService;
use Livewire\Livewire;

test('fixed learn session uses active manual question order for selected topic', function (): void {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $topic = QuestionTopic::factory()->create(['name' => 'Znaki ostrzegawcze']);
    $otherTopic = QuestionTopic::factory()->create(['name' => 'Inny dział']);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 1, 'published_at' => now()->subDays(3)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 2, 'published_at' => now()->subDays(2)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 3, 'published_at' => now()->subDay()],
        )
        ->create();
    $staleQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $otherTopic->getKey(),
            'difficulty' => 1,
        ]);

    $orderSet = QuestionLearningOrderSet::factory()
        ->active()
        ->for($category, 'licenseCategory')
        ->for($topic, 'questionTopic')
        ->create(['question_scope' => 'all', 'version' => 4]);

    QuestionLearningOrderItem::factory()->for($orderSet, 'orderSet')->for($questions[2], 'question')->create(['position' => 10]);
    QuestionLearningOrderItem::factory()->for($orderSet, 'orderSet')->for($staleQuestion, 'question')->create(['position' => 20]);
    QuestionLearningOrderItem::factory()->for($orderSet, 'orderSet')->for($questions[0], 'question')->create(['position' => 30]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'ui_shell' => 'exam_like',
            'question_topic_id' => $topic->getKey(),
            'question_scope' => 'all',
            'question_status' => 'all',
            'randomize_order' => false,
            'question_count' => 3,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->firstOrFail();

    expect($studySession->questionIds()->all())->toBe([
        $questions[2]->getKey(),
        $questions[0]->getKey(),
        $questions[1]->getKey(),
    ]);
    expect(data_get($studySession->payload, 'filters.learning_order.source'))->toBe('manual');
    expect(data_get($studySession->payload, 'filters.learning_order.order_set_id'))->toBe($orderSet->getKey());
    expect(data_get($studySession->payload, 'filters.learning_order.order_set_version'))->toBe(4);
    expect(data_get($studySession->payload, 'filters.learning_order.manual_count'))->toBe(2);
    expect(data_get($studySession->payload, 'filters.learning_order.fallback_count'))->toBe(1);
    expect(data_get($studySession->payload, 'filters.learning_order.stale_count'))->toBe(1);
    expect(data_get($studySession->payload, 'filters.learning_order.missing_count'))->toBe(1);
});

test('manual order is ignored for random learn sessions and sessions without topic', function (): void {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $topic = QuestionTopic::factory()->create(['name' => 'Znaki ostrzegawcze']);

    $questions = Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 1],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 2],
        )
        ->create();

    $orderSet = QuestionLearningOrderSet::factory()
        ->active()
        ->for($category, 'licenseCategory')
        ->for($topic, 'questionTopic')
        ->create(['question_scope' => 'all']);

    QuestionLearningOrderItem::factory()->for($orderSet, 'orderSet')->for($questions[1], 'question')->create(['position' => 10]);
    QuestionLearningOrderItem::factory()->for($orderSet, 'orderSet')->for($questions[0], 'question')->create(['position' => 20]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_topic_id' => $topic->getKey(),
            'randomize_order' => true,
            'question_count' => 2,
        ])
        ->assertRedirect();

    $randomSession = StudySession::query()->firstOrFail();

    expect(data_get($randomSession->payload, 'filters.learning_order'))->toBeNull();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'randomize_order' => false,
            'question_count' => 2,
        ])
        ->assertRedirect();

    $withoutTopicSession = StudySession::query()->latest('id')->firstOrFail();

    expect(data_get($withoutTopicSession->payload, 'filters.learning_order'))->toBeNull();
});

test('admin question learning order page is available only for admins', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get('/admin/kolejnosc-pytan')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/admin/kolejnosc-pytan')
        ->assertOk()
        ->assertSee('Kolejność pytań', false)
        ->assertSee('Utwórz draft', false);
});

test('admin can move draft questions and sees cleaned prompts', function (): void {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $topic = QuestionTopic::factory()->create(['name' => 'Znaki ostrzegawcze']);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['question_topic_id' => $topic->getKey(), 'prompt' => '**Czy** [green]pierwsze[/green] pytanie?', 'difficulty' => 1, 'published_at' => now()->subDays(3)],
            ['question_topic_id' => $topic->getKey(), 'prompt' => 'Drugie pytanie?', 'difficulty' => 2, 'published_at' => now()->subDays(2)],
            ['question_topic_id' => $topic->getKey(), 'prompt' => 'Trzecie pytanie?', 'difficulty' => 3, 'published_at' => now()->subDay()],
        )
        ->create();
    QuestionMedia::factory()
        ->for($questions[0], 'question')
        ->create([
            'kind' => 'image',
            'path' => 'https://media.example.test/questions/thumb-warning.webp',
            'variant' => 'thumb',
            'sort_order' => 0,
            'metadata' => ['asset_group' => 'warning-thumb'],
        ]);

    $service = app(QuestionLearningOrderAdminService::class);
    $set = $service->createDraftFromFallback(
        (int) $category->getKey(),
        (int) $topic->getKey(),
        'all',
        $admin,
    );

    $lastItem = $set->items()
        ->where('question_id', $questions[2]->getKey())
        ->firstOrFail();

    $service->moveItem((int) $set->getKey(), (int) $lastItem->getKey(), 'top', $admin);

    $orderedQuestionIds = $set->items()
        ->orderBy('position')
        ->pluck('question_id')
        ->all();

    expect($orderedQuestionIds)->toBe([
        $questions[2]->getKey(),
        $questions[0]->getKey(),
        $questions[1]->getKey(),
    ]);

    $overview = $service->build(
        (int) $category->getKey(),
        (int) $topic->getKey(),
        'all',
        (int) $set->getKey(),
    );

    expect($overview['rows'][1]['prompt'])->toBe('Czy pierwsze pytanie?')
        ->and($overview['rows'][1]['raw_prompt'])->toBe('**Czy** [green]pierwsze[/green] pytanie?')
        ->and($overview['rows'][1]['media_preview']['thumbnail_url'])->toBe('https://media.example.test/questions/thumb-warning.webp')
        ->and($overview['rows'][1]['media_preview']['kind'])->toBe('image');
});

test('admin question learning order uses category specific topic labels without changing topic ids', function (): void {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create(['code' => 'AM']);
    $topic = QuestionTopic::factory()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    QuestionTopicCategoryLabel::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'display_name' => 'Ostrzeżenia dla motorowerów',
    ]);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $overview = app(QuestionLearningOrderAdminService::class)->build(
        (int) $category->getKey(),
        (int) $topic->getKey(),
        'all',
    );

    expect($overview['topics'][0]['id'])->toBe($topic->getKey())
        ->and($overview['topics'][0]['name'])->toBe('Ostrzeżenia dla motorowerów')
        ->and($overview['topics'][0]['technical_name'])->toBe('Znaki ostrzegawcze')
        ->and($overview['selected_topic']['id'])->toBe($topic->getKey())
        ->and($overview['selected_topic']['name'])->toBe('Ostrzeżenia dla motorowerów');

    Livewire::actingAs($admin)
        ->test(QuestionLearningOrderPage::class)
        ->set('categoryId', $category->getKey())
        ->set('topicId', $topic->getKey())
        ->assertSee('Ostrzeżenia dla motorowerów')
        ->assertSee('technicznie: Znaki ostrzegawcze');
});

test('admin page hydrates draft position inputs and can delete drafts', function (): void {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $topic = QuestionTopic::factory()->create(['name' => 'Znaki ostrzegawcze']);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 1, 'published_at' => now()->subDays(2)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 2, 'published_at' => now()->subDay()],
        )
        ->create();

    $this->actingAs($admin);

    $component = Livewire::test(QuestionLearningOrderPage::class)
        ->set('categoryId', $category->getKey())
        ->set('topicId', $topic->getKey())
        ->call('createDraft');

    $set = QuestionLearningOrderSet::query()->where('status', QuestionLearningOrderSet::STATUS_DRAFT)->firstOrFail();
    $items = $set->items()->orderBy('position')->get();

    expect($items)->toHaveCount(2);

    $component
        ->assertSet('editableSetId', $set->getKey())
        ->assertSet('positions.'.$items[0]->getKey(), 10)
        ->assertSet('positions.'.$items[1]->getKey(), 20)
        ->assertSeeHtml('value="10"')
        ->assertSeeHtml('value="20"')
        ->call('deleteDraft', $set->getKey());

    expect(QuestionLearningOrderSet::query()->whereKey($set->getKey())->exists())->toBeFalse()
        ->and(QuestionLearningOrderItem::query()->where('question_learning_order_set_id', $set->getKey())->exists())->toBeFalse();
});

test('admin can group selected draft questions as a block', function (): void {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $topic = QuestionTopic::factory()->create(['name' => 'Znaki ostrzegawcze']);

    $questions = Question::factory()
        ->count(5)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 1, 'published_at' => now()->subDays(5)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 2, 'published_at' => now()->subDays(4)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 3, 'published_at' => now()->subDays(3)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 4, 'published_at' => now()->subDays(2)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 5, 'published_at' => now()->subDay()],
        )
        ->create();

    $service = app(QuestionLearningOrderAdminService::class);
    $set = $service->createDraftFromFallback(
        (int) $category->getKey(),
        (int) $topic->getKey(),
        'all',
        $admin,
    );

    $itemsByQuestionId = $set->items()
        ->get()
        ->keyBy('question_id');

    $service->moveItems(
        (int) $set->getKey(),
        [
            (int) $itemsByQuestionId[$questions[3]->getKey()]->getKey(),
            (int) $itemsByQuestionId[$questions[1]->getKey()]->getKey(),
        ],
        'before',
        (int) $itemsByQuestionId[$questions[4]->getKey()]->getKey(),
        $admin,
    );

    $orderedItems = $set->items()
        ->orderBy('position')
        ->get(['question_id', 'position']);

    expect($orderedItems->pluck('question_id')->all())->toBe([
        $questions[0]->getKey(),
        $questions[2]->getKey(),
        $questions[1]->getKey(),
        $questions[3]->getKey(),
        $questions[4]->getKey(),
    ])->and($orderedItems->pluck('position')->all())->toBe([10, 20, 30, 40, 50]);
});

test('admin page can select visible questions and move them together', function (): void {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $topic = QuestionTopic::factory()->create(['name' => 'Znaki ostrzegawcze']);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 1, 'published_at' => now()->subDays(3)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 2, 'published_at' => now()->subDays(2)],
            ['question_topic_id' => $topic->getKey(), 'difficulty' => 3, 'published_at' => now()->subDay()],
        )
        ->create();

    $this->actingAs($admin);

    $component = Livewire::test(QuestionLearningOrderPage::class)
        ->set('categoryId', $category->getKey())
        ->set('topicId', $topic->getKey())
        ->call('createDraft');

    $set = QuestionLearningOrderSet::query()->where('status', QuestionLearningOrderSet::STATUS_DRAFT)->firstOrFail();
    $items = $set->items()->orderBy('position')->get();

    $component
        ->call('selectVisibleItems', [
            (int) $items[1]->getKey(),
            (int) $items[2]->getKey(),
        ])
        ->assertSet('selectedItems.'.$items[1]->getKey(), true)
        ->assertSet('selectedItems.'.$items[2]->getKey(), true)
        ->call('moveSelectedItems', $set->getKey(), 'top')
        ->assertSet('selectedItems', []);

    expect($set->items()->orderBy('position')->pluck('question_id')->all())->toBe([
        $questions[1]->getKey(),
        $questions[2]->getKey(),
        $questions[0]->getKey(),
    ]);
});
