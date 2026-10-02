<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use App\Models\StudySession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('course flashcard is not offered to any regular category in classic or zen learning', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    foreach (['AM', 'A1', 'A2', 'A', 'B1', 'B', 'C1', 'C', 'D1', 'D', 'T'] as $code) {
        $category = LicenseCategory::factory()->withCode($code)->create();
        $question = Question::factory()->for($category, 'licenseCategory')->create(['is_active' => true]);
        foreach (['zen', 'exam_like'] as $shell) {
            $session = StudySession::factory()->inProgress()->create([
                'user_id' => $user->getKey(), 'license_category_id' => $category->getKey(),
                'mode' => 'learn', 'total_questions_count' => 1, 'correct_answers_count' => 0,
                'payload' => ['question_ids' => [$question->getKey()], 'current_index' => 0, 'answered_count' => 0, 'ui_shell' => $shell],
            ]);
            $this->actingAs($user)->get(route('study-sessions.current'))
                ->assertOk()->assertInertia(fn (Assert $page) => $page
                    ->component('StudySessions/Show')
                    ->where('session.ui_shell', $shell)
                    ->where('session.scope', 'regular_category')
                    ->where('session.context', null));
            $session->update(['status' => 'completed', 'completed_at' => now()]);
        }
    }
});

test('course flashcard opt in is limited to the exact qualification C collection and zen shell', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryC()->create();
    $collection = QuestionCollection::query()->create([
        'license_category_id' => $category->getKey(), 'code' => 'qualification-c-accelerated',
        'slug' => 'kwalifikacja-wstepna-przyspieszona-c', 'name' => 'Course', 'kind' => 'professional_qualification',
        'is_active' => true, 'is_available_to_learners' => true,
    ]);
    $module = QuestionModule::query()->create([
        'question_collection_id' => $collection->getKey(), 'code' => '1.1', 'slug' => 'module',
        'name' => 'Module', 'is_active' => true,
    ]);
    $question = Question::factory()->for($category, 'licenseCategory')->create();
    $module->questions()->attach($question->getKey(), ['position' => 1]);
    $session = StudySession::factory()->inProgress()->create([
        'user_id' => $user->getKey(), 'license_category_id' => $category->getKey(),
        'question_collection_id' => $collection->getKey(), 'question_module_id' => $module->getKey(),
        'mode' => 'learn', 'total_questions_count' => 1, 'correct_answers_count' => 0,
        'payload' => ['question_ids' => [$question->getKey()], 'current_index' => 0, 'ui_shell' => 'zen',
            'context' => ['type' => 'question_module']],
    ]);
    $assertFlag = function (bool $expected) use ($user, $session): void {
        $this->actingAs($user)->get(route('study-sessions.current'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('session.context.explanation_flashcard', $expected));
    };
    $assertFlag(true);
    foreach ([['code' => 'other-course'], ['slug' => 'other-course'], ['kind' => 'other']] as $override) {
        $collection->update($override);
        $assertFlag(false);
        $collection->update(['code' => 'qualification-c-accelerated', 'slug' => 'kwalifikacja-wstepna-przyspieszona-c', 'kind' => 'professional_qualification']);
    }
    $session->update(['payload' => [...$session->payload, 'ui_shell' => 'exam_like']]);
    $assertFlag(false);
});
