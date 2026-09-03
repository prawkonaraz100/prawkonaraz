<?php

use App\Models\Question;
use App\Models\User;

test('admin can update question explanation from the inline study endpoint', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'explanation' => 'Stare wyjasnienie pytania.',
    ]);

    $updatedExplanation = 'Nowe wyjasnienie zapisane z trybu nauki. To jest **bardzo wazne**.';

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.explanation.update', $question), [
            'explanation' => $updatedExplanation,
        ])
        ->assertOk()
        ->assertJsonPath('data.question.id', $question->getKey())
        ->assertJsonPath('data.question.explanation', $updatedExplanation);

    expect($question->fresh()->explanation)->toBe($updatedExplanation);
});

test('admin can update explanation for all questions sharing the same external id', function () {
    $admin = User::factory()->admin()->create();
    $firstQuestion = Question::factory()->create([
        'source' => 'gov',
        'external_id' => '12345',
        'explanation' => 'Pierwsza wersja wyjasnienia.',
    ]);
    $secondQuestion = Question::factory()->create([
        'source' => 'gov',
        'external_id' => '12345',
        'explanation' => 'Druga wersja wyjasnienia.',
    ]);
    $thirdQuestion = Question::factory()->create([
        'source' => 'gov',
        'external_id' => '99999',
        'explanation' => 'Kontrolne wyjasnienie.',
    ]);

    $updatedExplanation = 'Wspolne wyjasnienie dla wszystkich kategorii.';

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.explanation.update', $firstQuestion), [
            'explanation' => $updatedExplanation,
            'apply_scope' => 'shared_external_id',
        ])
        ->assertOk()
        ->assertJsonPath('data.question.id', $firstQuestion->getKey())
        ->assertJsonPath('data.question.explanation', $updatedExplanation)
        ->assertJsonPath('data.apply_scope', 'shared_external_id')
        ->assertJsonCount(2, 'data.affected_questions');

    expect($firstQuestion->fresh()->explanation)->toBe($updatedExplanation);
    expect($secondQuestion->fresh()->explanation)->toBe($updatedExplanation);
    expect($thirdQuestion->fresh()->explanation)->toBe('Kontrolne wyjasnienie.');
});

test('non admin cannot update question explanation from the inline study endpoint', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create([
        'explanation' => 'Oryginalne wyjasnienie.',
    ]);

    $this->actingAs($user)
        ->patchJson(route('api.v1.admin.questions.explanation.update', $question), [
            'explanation' => 'Nie powinno sie zapisac.',
        ])
        ->assertForbidden();

    expect($question->fresh()->explanation)->toBe('Oryginalne wyjasnienie.');
});

test('blank explanation is normalized to null in the inline study endpoint', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'explanation' => 'Wyjasnienie do wyczyszczenia.',
    ]);

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.explanation.update', $question), [
            'explanation' => '   ',
        ])
        ->assertOk()
        ->assertJsonPath('data.question.explanation', null);

    expect($question->fresh()->explanation)->toBeNull();
});
