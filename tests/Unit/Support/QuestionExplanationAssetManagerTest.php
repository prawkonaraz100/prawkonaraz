<?php

use App\Models\Question;
use App\Models\QuestionExplanationAsset;
use App\Models\SharedQuestionExplanationAsset;
use App\Support\QuestionExplanationAssetManager;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

test('reference explanation assets are stored under a question-specific directory', function () {
    Storage::fake('media_local');

    $question = Question::factory()->create();

    Storage::disk('media_local')->put('question-explanations/original.png', 'image-binary');

    $asset = app(QuestionExplanationAssetManager::class)->syncReferenceSign($question, [
        'disk' => 'media_local',
        'file_path' => 'question-explanations/original.png',
        'alt_text' => 'Scoped image',
        'is_active' => true,
    ]);

    $expectedPath = "question-explanations/{$question->getKey()}/original.png";

    expect($asset)->not->toBeNull()
        ->and($asset?->question_id)->toBe($question->getKey())
        ->and($asset?->file_path)->toBe($expectedPath);

    Storage::disk('media_local')->assertMissing('question-explanations/original.png');
    Storage::disk('media_local')->assertExists($expectedPath);
});

test('shared explanation files are copied instead of being moved away from other questions', function () {
    Storage::fake('media_local');

    $firstQuestion = Question::factory()->create();
    $secondQuestion = Question::factory()->create();

    Storage::disk('media_local')->put('question-explanations/shared.png', 'shared-image');

    QuestionExplanationAsset::factory()
        ->for($firstQuestion, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/shared.png',
            'alt_text' => 'Shared original',
            'is_active' => true,
        ]);

    $asset = app(QuestionExplanationAssetManager::class)->syncReferenceSign($secondQuestion, [
        'disk' => 'media_local',
        'file_path' => 'question-explanations/shared.png',
        'alt_text' => 'Scoped copy',
        'is_active' => true,
    ]);

    $expectedPath = "question-explanations/{$secondQuestion->getKey()}/shared.png";

    expect($asset)->not->toBeNull()
        ->and($asset?->question_id)->toBe($secondQuestion->getKey())
        ->and($asset?->file_path)->toBe($expectedPath)
        ->and($firstQuestion->fresh()->referenceExplanationAsset?->file_path)->toBe('question-explanations/shared.png');

    Storage::disk('media_local')->assertExists('question-explanations/shared.png');
    Storage::disk('media_local')->assertExists($expectedPath);
});

test('deleting one question asset does not delete a shared file used by another question', function () {
    Storage::fake('media_local');

    $firstQuestion = Question::factory()->create();
    $secondQuestion = Question::factory()->create();

    Storage::disk('media_local')->put('question-explanations/shared-delete.png', 'shared-image');

    QuestionExplanationAsset::factory()
        ->for($firstQuestion, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/shared-delete.png',
            'alt_text' => 'First shared',
            'is_active' => true,
        ]);

    QuestionExplanationAsset::factory()
        ->for($secondQuestion, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/shared-delete.png',
            'alt_text' => 'Second shared',
            'is_active' => true,
        ]);

    app(QuestionExplanationAssetManager::class)->syncReferenceSign($firstQuestion, null);

    expect($firstQuestion->fresh()->referenceExplanationAsset)->toBeNull()
        ->and($secondQuestion->fresh()->referenceExplanationAsset?->file_path)->toBe('question-explanations/shared-delete.png');

    Storage::disk('media_local')->assertExists('question-explanations/shared-delete.png');
});

test('local question asset copies file instead of moving it when the file is referenced by a shared asset', function () {
    Storage::fake('media_local');

    $question = Question::factory()->create([
        'external_id' => '13447',
        'source' => 'gov.pl-mi',
    ]);

    Storage::disk('media_local')->put('question-explanations/shared/shared.png', 'shared-image');

    SharedQuestionExplanationAsset::factory()->create([
        'external_id' => '13447',
        'source_scope' => SharedQuestionExplanationAsset::sourceScopeFor('gov.pl-mi'),
        'disk' => 'media_local',
        'file_path' => 'question-explanations/shared/shared.png',
        'alt_text' => 'Shared original',
        'is_active' => true,
    ]);

    $asset = app(QuestionExplanationAssetManager::class)->syncReferenceSign($question, [
        'disk' => 'media_local',
        'file_path' => 'question-explanations/shared/shared.png',
        'alt_text' => 'Local override',
        'is_active' => true,
    ]);

    $expectedPath = "question-explanations/{$question->getKey()}/shared.png";

    expect($asset)->not->toBeNull()
        ->and($asset?->file_path)->toBe($expectedPath);

    Storage::disk('media_local')->assertExists('question-explanations/shared/shared.png');
    Storage::disk('media_local')->assertExists($expectedPath);
});
