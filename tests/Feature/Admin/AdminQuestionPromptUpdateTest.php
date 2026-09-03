<?php

use App\Models\Question;
use App\Models\User;

test('study app ziggy group exposes inline question prompt update route', function () {
    expect(config('ziggy.groups.app'))
        ->toContain('api.v1.admin.questions.prompt.update');
});

test('admin can update question prompt from the inline study endpoint', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'prompt' => 'Stare pytanie testowe?',
    ]);

    $updatedPrompt = 'Nowa treść pytania zapisana z trybu nauki?';

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.prompt.update', $question), [
            'prompt' => $updatedPrompt,
        ])
        ->assertOk()
        ->assertJsonPath('data.question.id', $question->getKey())
        ->assertJsonPath('data.question.prompt', $updatedPrompt);

    expect($question->fresh()->prompt)->toBe($updatedPrompt);
});

test('admin can update prompt for all questions sharing the same external id', function () {
    $admin = User::factory()->admin()->create();
    $firstQuestion = Question::factory()->create([
        'source' => 'gov',
        'external_id' => '12345',
        'prompt' => 'Pierwsza wersja pytania?',
    ]);
    $secondQuestion = Question::factory()->create([
        'source' => 'gov',
        'external_id' => '12345',
        'prompt' => 'Druga wersja pytania?',
    ]);
    $thirdQuestion = Question::factory()->create([
        'source' => 'gov',
        'external_id' => '99999',
        'prompt' => 'Inne pytanie kontrolne?',
    ]);

    $updatedPrompt = 'Wspólna treść pytania dla wszystkich kategorii?';

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.prompt.update', $firstQuestion), [
            'prompt' => $updatedPrompt,
            'apply_scope' => 'shared_external_id',
        ])
        ->assertOk()
        ->assertJsonPath('data.question.id', $firstQuestion->getKey())
        ->assertJsonPath('data.question.prompt', $updatedPrompt)
        ->assertJsonPath('data.apply_scope', 'shared_external_id')
        ->assertJsonCount(2, 'data.affected_questions');

    expect($firstQuestion->fresh()->prompt)->toBe($updatedPrompt);
    expect($secondQuestion->fresh()->prompt)->toBe($updatedPrompt);
    expect($thirdQuestion->fresh()->prompt)->toBe('Inne pytanie kontrolne?');
});

test('non admin cannot update question prompt from the inline study endpoint', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create([
        'prompt' => 'Oryginalna treść pytania?',
    ]);

    $this->actingAs($user)
        ->patchJson(route('api.v1.admin.questions.prompt.update', $question), [
            'prompt' => 'Nie powinno się zapisać.',
        ])
        ->assertForbidden();

    expect($question->fresh()->prompt)->toBe('Oryginalna treść pytania?');
});

test('blank prompt is rejected in the inline study endpoint', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'prompt' => 'Treść pytania do zachowania?',
    ]);

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.prompt.update', $question), [
            'prompt' => '   ',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prompt']);

    expect($question->fresh()->prompt)->toBe('Treść pytania do zachowania?');
});
