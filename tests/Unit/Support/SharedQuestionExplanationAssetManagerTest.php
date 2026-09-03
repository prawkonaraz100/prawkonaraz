<?php

use App\Models\Question;
use App\Models\QuestionExplanationAsset;
use App\Models\SharedQuestionExplanationAsset;
use App\Support\SharedQuestionExplanationAssetManager;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('shared explanation asset copies file instead of moving it when the file is referenced by a local question asset', function () {
    Storage::fake('media_local');

    $question = Question::factory()->create([
        'external_id' => '13447',
        'source' => 'gov.pl-mi',
    ]);

    $otherQuestion = Question::factory()->create();

    Storage::disk('media_local')->put('question-explanations/shared-source.png', 'shared-image');

    QuestionExplanationAsset::factory()
        ->for($otherQuestion, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => 'question-explanations/shared-source.png',
            'alt_text' => 'Local original',
            'is_active' => true,
        ]);

    $asset = app(SharedQuestionExplanationAssetManager::class)->syncReferenceSign($question, [
        'disk' => 'media_local',
        'file_path' => 'question-explanations/shared-source.png',
        'alt_text' => 'Shared asset',
        'is_active' => true,
    ]);

    expect($asset)->not->toBeNull()
        ->and($asset?->file_path)->toStartWith('question-explanations/shared/govpl-mi/13447/')
        ->and($otherQuestion->fresh()->referenceExplanationAsset?->file_path)->toBe('question-explanations/shared-source.png');

    Storage::disk('media_local')->assertExists('question-explanations/shared-source.png');
    Storage::disk('media_local')->assertExists($asset->file_path);
});

test('deleting a shared explanation asset does not delete a file still referenced by a local question asset', function () {
    Storage::fake('media_local');

    $question = Question::factory()->create([
        'external_id' => '13447',
        'source' => 'gov.pl-mi',
    ]);

    $otherQuestion = Question::factory()->create();
    $sharedPath = 'question-explanations/shared/govpl-mi/13447/shared-delete.png';

    Storage::disk('media_local')->put($sharedPath, 'shared-image');

    SharedQuestionExplanationAsset::factory()->create([
        'external_id' => '13447',
        'source_scope' => SharedQuestionExplanationAsset::sourceScopeFor('gov.pl-mi'),
        'disk' => 'media_local',
        'file_path' => $sharedPath,
        'alt_text' => 'Shared original',
        'is_active' => true,
    ]);

    QuestionExplanationAsset::factory()
        ->for($otherQuestion, 'question')
        ->create([
            'disk' => 'media_local',
            'file_path' => $sharedPath,
            'alt_text' => 'Local override',
            'is_active' => true,
        ]);

    app(SharedQuestionExplanationAssetManager::class)->syncReferenceSign($question, null);

    expect(
        SharedQuestionExplanationAsset::query()
            ->where('external_id', '13447')
            ->where('source_scope', SharedQuestionExplanationAsset::sourceScopeFor('gov.pl-mi'))
            ->exists()
    )->toBeFalse()
        ->and($otherQuestion->fresh()->referenceExplanationAsset?->file_path)->toBe($sharedPath);

    Storage::disk('media_local')->assertExists($sharedPath);
});
