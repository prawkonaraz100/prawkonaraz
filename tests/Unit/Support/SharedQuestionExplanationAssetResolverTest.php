<?php

use App\Models\Question;
use App\Models\QuestionExplanationAsset;
use App\Models\SharedQuestionExplanationAsset;
use App\Support\SharedQuestionExplanationAssetResolver;
use Tests\TestCase;

uses(TestCase::class);

test('resolver returns shared explanation asset when question has no local override', function () {
    $question = Question::factory()->create([
        'external_id' => '13447',
        'source' => 'gov.pl-mi',
    ]);

    $sharedAsset = SharedQuestionExplanationAsset::factory()->create([
        'external_id' => '13447',
        'source_scope' => SharedQuestionExplanationAsset::sourceScopeFor('gov.pl-mi'),
        'title' => 'Wspolny znak ostrzegawczy',
    ]);

    $question->load('referenceExplanationAsset');

    $resolved = app(SharedQuestionExplanationAssetResolver::class)->resolveReferenceAsset($question);

    expect($resolved)->toBeInstanceOf(SharedQuestionExplanationAsset::class)
        ->and($resolved?->getKey())->toBe($sharedAsset->getKey())
        ->and($resolved?->title)->toBe('Wspolny znak ostrzegawczy');
});

test('resolver prefers local explanation asset over shared one', function () {
    $question = Question::factory()->create([
        'external_id' => '13447',
        'source' => 'gov.pl-mi',
    ]);

    $localAsset = QuestionExplanationAsset::factory()
        ->for($question, 'question')
        ->create([
            'title' => 'Lokalny override',
        ]);

    SharedQuestionExplanationAsset::factory()->create([
        'external_id' => '13447',
        'source_scope' => SharedQuestionExplanationAsset::sourceScopeFor('gov.pl-mi'),
        'title' => 'Wspolny znak ostrzegawczy',
    ]);

    $question->load('referenceExplanationAsset');

    $resolved = app(SharedQuestionExplanationAssetResolver::class)->resolveReferenceAsset($question);

    expect($resolved)->toBeInstanceOf(QuestionExplanationAsset::class)
        ->and($resolved?->getKey())->toBe($localAsset->getKey())
        ->and($resolved?->title)->toBe('Lokalny override');
});
