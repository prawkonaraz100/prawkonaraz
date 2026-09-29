<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionCollection;
use App\Models\QuestionCollectionIncorrectQuestion;
use App\Models\QuestionModule;
use App\Models\StudySession;
use App\Models\User;
use App\Models\UserQuestionProgress;
use App\Support\QuestionCollectionProgressService;
use Inertia\Testing\AssertableInertia as Assert;

function qualificationSessionFixture(): array
{
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryC()->create();
    $collection = QuestionCollection::query()->create([
        'license_category_id' => $category->getKey(),
        'code' => 'qualification-c-accelerated',
        'slug' => 'kwalifikacja-wstepna-przyspieszona-c',
        'name' => 'Kwalifikacja wstępna przyspieszona — kat. C',
        'kind' => 'qualification',
        'is_active' => true,
        'is_public' => false,
        'is_available_to_learners' => true,
    ]);
    $module = QuestionModule::query()->create([
        'question_collection_id' => $collection->getKey(),
        'code' => '1.1',
        'slug' => 'modul-1-1',
        'name' => 'Pierwszy moduł',
        'is_active' => true,
    ]);
    $courseQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'is_active' => false,
        'correct_answer' => 'a',
    ]);
    $regularQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'is_active' => true,
        'correct_answer' => 'a',
    ]);
    $module->questions()->attach($courseQuestion->getKey(), ['position' => 1]);

    return compact('user', 'category', 'collection', 'module', 'courseQuestion', 'regularQuestion');
}

test('course module keeps its scope through answer result and restart', function () {
    ['user' => $user, 'collection' => $collection, 'module' => $module,
        'courseQuestion' => $courseQuestion] = qualificationSessionFixture();

    $startUrl = route('learning.question-collections.modules.start', [$collection, $module]);
    $this->actingAs($user)->post($startUrl)->assertRedirect(route('study-sessions.current'));

    $firstSession = StudySession::query()->latest('id')->firstOrFail();
    expect($firstSession->question_collection_id)->toBe($collection->getKey())
        ->and($firstSession->question_module_id)->toBe($module->getKey())
        ->and($firstSession->questionIds()->all())->toBe([$courseQuestion->getKey()]);

    $this->actingAs($user)->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.scope', 'course_module')
            ->where('session.context.restart_url', route('learning.question-collections.modules.start', [
                'questionCollection' => $collection->slug,
                'module' => $module->slug,
            ], absolute: false))
            ->where('session.context.incorrect_questions_url', route('learning.question-collections.incorrect-questions.index', [
                'questionCollection' => $collection->slug,
            ], absolute: false))
            ->has('topicGroups', 0));

    $this->actingAs($user)->post(route('study-sessions.answers.store', $firstSession), [
        'question_id' => $courseQuestion->getKey(),
        'selected_answer' => 'b',
    ])->assertRedirect(route('study-sessions.show', $firstSession));

    expect($firstSession->fresh()->status)->toBe('completed')
        ->and(QuestionCollectionIncorrectQuestion::query()->where('question_collection_id', $collection->getKey())->count())->toBe(1)
        ->and(UserQuestionProgress::query()->where('user_id', $user->getKey())->count())->toBe(0);

    $this->actingAs($user)->get(route('study-sessions.show', $firstSession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.scope', 'course_module'));

    // A stale client must not turn a course-result follow-up into an ordinary C session.
    $this->actingAs($user)
        ->withHeader('X-Study-Session-Switch', 'follow-up')
        ->postJson(route('study-sessions.store'), [
            'license_category_id' => $firstSession->license_category_id,
            'mode' => 'learn',
            'question_count' => 1,
            'question_status' => 'incorrect',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('source_study_session_id');
    expect(StudySession::query()->count())->toBe(1);

    $this->actingAs($user)
        ->withHeader('X-Study-Session-Switch', 'follow-up')
        ->postJson(route('study-sessions.store'), [
            'source_study_session_id' => $firstSession->getKey(),
            'license_category_id' => $firstSession->license_category_id,
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('source_study_session_id');
    expect(StudySession::query()->count())->toBe(1);

    $this->actingAs($user)->post($startUrl)->assertRedirect(route('study-sessions.current'));
    $restarted = StudySession::query()->latest('id')->firstOrFail();

    expect($restarted->getKey())->not->toBe($firstSession->getKey())
        ->and($restarted->question_collection_id)->toBe($collection->getKey())
        ->and($restarted->question_module_id)->toBe($module->getKey())
        ->and($restarted->questionIds()->all())->toBe([$courseQuestion->getKey()])
        ->and(UserQuestionProgress::query()->where('user_id', $user->getKey())->count())->toBe(0);

    $progress = app(QuestionCollectionProgressService::class)
        ->moduleProgressFor($user, collect([$collection->load(['modules' => fn ($query) => $query->withCount('questions')])]))
        ->get($module->getKey());
    expect($progress['answered_count'])->toBe(1);
});

test('course incorrect review keeps its own scope and ordinary category C remains separate', function () {
    ['user' => $user, 'category' => $category, 'collection' => $collection,
        'module' => $module, 'courseQuestion' => $courseQuestion,
        'regularQuestion' => $regularQuestion] = qualificationSessionFixture();

    $this->actingAs($user)->post(route('learning.question-collections.modules.start', [$collection, $module]))->assertRedirect();
    $moduleSession = StudySession::query()->latest('id')->firstOrFail();
    $this->actingAs($user)->post(route('study-sessions.answers.store', $moduleSession), [
        'question_id' => $courseQuestion->getKey(),
        'selected_answer' => 'b',
    ])->assertRedirect();

    $this->actingAs($user)
        ->post(route('learning.question-collections.incorrect-questions.start', $collection))
        ->assertRedirect(route('study-sessions.current'));
    $reviewSession = StudySession::query()->latest('id')->firstOrFail();

    expect($reviewSession->question_collection_id)->toBe($collection->getKey())
        ->and($reviewSession->question_module_id)->toBeNull()
        ->and($reviewSession->questionIds()->all())->toBe([$courseQuestion->getKey()]);

    $this->actingAs($user)->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.scope', 'course_review')
            ->where('session.context.restart_url', null)
            ->where('session.context.incorrect_questions_url', route('learning.question-collections.incorrect-questions.index', [
                'questionCollection' => $collection->slug,
            ], absolute: false))
            ->has('topicGroups', 0));

    $this->actingAs($user)->post(route('study-sessions.answers.store', $reviewSession), [
        'question_id' => $courseQuestion->getKey(),
        'selected_answer' => 'a',
    ])->assertRedirect();

    expect($reviewSession->fresh()->status)->toBe('completed')
        ->and(UserQuestionProgress::query()->where('user_id', $user->getKey())->count())->toBe(0);

    $this->actingAs($user)->get(route('study-sessions.show', $reviewSession))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.scope', 'course_review')
            ->where('session.context.return_url', route('learning.question-collections.incorrect-questions.index', [
                'questionCollection' => $collection->slug,
            ], absolute: false)));

    $courseIncorrect = QuestionCollectionIncorrectQuestion::query()->firstOrFail();
    $this->actingAs($user)->delete(route('learning.question-collections.incorrect-questions.destroy', [
        $collection,
        $courseIncorrect,
    ]))->assertRedirect();
    $this->actingAs($user)->get(route('learning.question-collections.incorrect-questions.index', $collection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('stats.active_count', 0));
    $this->actingAs($user)
        ->post(route('learning.question-collections.incorrect-questions.start', $collection))
        ->assertSessionHasErrors('question_collection_id');
    expect(StudySession::query()->count())->toBe(2);

    $this->actingAs($user)->post(route('study-sessions.store'), [
        'license_category_id' => $category->getKey(),
        'mode' => 'learn',
        'question_count' => 1,
    ])->assertRedirect();
    $regularSession = StudySession::query()->latest('id')->firstOrFail();

    expect($regularSession->question_collection_id)->toBeNull()
        ->and($regularSession->question_module_id)->toBeNull()
        ->and($regularSession->questionIds()->all())->toBe([$regularQuestion->getKey()]);

    $this->actingAs($user)->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.scope', 'regular_category'));

    $this->actingAs($user)->post(route('study-sessions.answers.store', $regularSession), [
        'question_id' => $regularQuestion->getKey(),
        'selected_answer' => 'b',
    ])->assertRedirect();
    $this->actingAs($user)
        ->withHeader('X-Study-Session-Switch', 'follow-up')
        ->postJson(route('study-sessions.store'), [
            'source_study_session_id' => $regularSession->getKey(),
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
            'question_status' => 'incorrect',
        ])
        ->assertOk();

    $regularFollowUp = StudySession::query()->latest('id')->firstOrFail();
    expect($regularFollowUp->question_collection_id)->toBeNull()
        ->and($regularFollowUp->questionIds()->all())->toBe([$regularQuestion->getKey()]);
});

test('legacy course reviews are excluded from regular category sessions', function () {
    ['user' => $user, 'category' => $category, 'collection' => $collection,
        'courseQuestion' => $courseQuestion] = qualificationSessionFixture();

    $legacyReview = StudySession::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'mode' => 'learn',
        'status' => 'completed',
        'started_at' => now()->subMinute(),
        'completed_at' => now(),
        'correct_answers_count' => 0,
        'total_questions_count' => 1,
        'payload' => [
            'question_ids' => [$courseQuestion->getKey()],
            'context' => [
                'type' => 'question_collection_review',
                'collection_code' => $collection->code,
                'collection_name' => $collection->name,
            ],
        ],
    ]);

    expect(StudySession::query()->regularCategory()->whereKey($legacyReview->getKey())->exists())->toBeFalse();

    $this->actingAs($user)->get(route('study-sessions.show', $legacyReview))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.scope', 'course_review')
            ->where('session.context.restart_url', null)
            ->where('session.context.incorrect_questions_url', route('learning.question-collections.incorrect-questions.index', [
                'questionCollection' => $collection->slug,
            ], absolute: false)));
});

test('course restart never silently replaces an active session or bypasses course access', function () {
    ['user' => $user, 'collection' => $collection, 'module' => $module] = qualificationSessionFixture();
    $startUrl = route('learning.question-collections.modules.start', [$collection, $module]);

    $this->actingAs($user)->post($startUrl)->assertRedirect(route('study-sessions.current'));
    $activeSession = StudySession::query()->firstOrFail();

    $this->actingAs($user)->post($startUrl)
        ->assertSessionHasErrors('replace_active_session');
    expect(StudySession::query()->count())->toBe(1)
        ->and($activeSession->fresh()->status)->toBe('in_progress');

    $this->actingAs($user)->post($startUrl, ['replace_active_session' => true])
        ->assertRedirect(route('study-sessions.current'));
    $restartedSession = StudySession::query()->orderByDesc('id')->firstOrFail();
    expect(StudySession::query()->count())->toBe(2)
        ->and($activeSession->fresh()->status)->toBe('completed')
        ->and($restartedSession->status)->toBe('in_progress')
        ->and($restartedSession->question_collection_id)->toBe($collection->getKey())
        ->and($restartedSession->question_module_id)->toBe($module->getKey());

    $this->actingAs($user)->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.id', $restartedSession->getKey())
            ->where('session.status', 'in_progress')
            ->where('session.scope', 'course_module'));

    $otherCollection = QuestionCollection::query()->create([
        'license_category_id' => $collection->license_category_id,
        'code' => 'other-qualification-c',
        'slug' => 'inna-kwalifikacja-c',
        'name' => 'Inna kwalifikacja C',
        'kind' => 'qualification',
        'is_active' => true,
        'is_available_to_learners' => true,
    ]);
    $this->actingAs($user)->post(route('learning.question-collections.modules.start', [
        'questionCollection' => $otherCollection->slug,
        'module' => $module->slug,
    ]))->assertNotFound();
    expect(StudySession::query()->count())->toBe(2);

    $collection->forceFill(['is_available_to_learners' => false])->save();
    $this->actingAs($user)->post($startUrl)->assertNotFound();
    expect(StudySession::query()->count())->toBe(2);
});
