<?php

use App\Models\Question;
use App\Models\QuestionExplanationAnnotation;
use App\Support\QuestionExplanationAnnotationPayloadBuilder;
use Tests\TestCase;

uses(TestCase::class);

test('forRuntime includes arrow geometry fields and skips inactive rows', function () {
    $question = Question::factory()->create();

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->arrow()
        ->create([
            'x_percent' => 48.0,
            'y_percent' => 32.0,
            'arrow_length_percent' => 22.0,
            'arrow_angle_degrees' => 300,
            'arrow_stroke_percent' => 1.5,
            'arrow_head_percent' => 4.4,
            'position' => 2,
            'is_active' => true,
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->label('Ukryta etykieta')
        ->create([
            'position' => 1,
            'is_active' => false,
        ]);

    $payload = app(QuestionExplanationAnnotationPayloadBuilder::class)
        ->forRuntime($question->explanationAnnotations()->get());

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['annotation_type'])->toBe('arrow')
        ->and($payload[0]['x_percent'])->toBe(48.0)
        ->and($payload[0]['y_percent'])->toBe(32.0)
        ->and($payload[0]['arrow_length_percent'])->toBe(22.0)
        ->and($payload[0]['arrow_angle_degrees'])->toBe(300)
        ->and($payload[0]['arrow_stroke_percent'])->toBe(1.5)
        ->and($payload[0]['arrow_head_percent'])->toBe(4.4);
});

test('target helpers keep arrow rows on image and video targets', function () {
    $question = Question::factory()->create();

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->arrow()
        ->create([
            'target_kind' => QuestionExplanationAnnotation::TARGET_KIND_QUESTION_IMAGE,
            'position' => 1,
            'is_active' => true,
        ]);

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->arrow()
        ->videoFrame(7)
        ->create([
            'target_kind' => QuestionExplanationAnnotation::TARGET_KIND_VIDEO_FRAME,
            'position' => 2,
            'is_active' => true,
        ]);

    $builder = app(QuestionExplanationAnnotationPayloadBuilder::class);
    $annotations = $question->explanationAnnotations()->get();

    $imagePayload = $builder->forQuestionImage($annotations);
    $videoPayload = $builder->forVideoFrame($annotations);

    expect($imagePayload)->toHaveCount(1)
        ->and($imagePayload[0]['target_kind'])->toBe('question_image')
        ->and($imagePayload[0]['annotation_type'])->toBe('arrow');

    expect($videoPayload)->toHaveCount(1)
        ->and($videoPayload[0]['target_kind'])->toBe('video_frame')
        ->and($videoPayload[0]['annotation_type'])->toBe('arrow');
});

test('forRuntime keeps text marker rows with label content', function () {
    $question = Question::factory()->create();

    QuestionExplanationAnnotation::factory()
        ->for($question, 'question')
        ->text('LEWA STRONA')
        ->create([
            'target_kind' => QuestionExplanationAnnotation::TARGET_KIND_QUESTION_IMAGE,
            'tone' => 'warning',
            'x_percent' => 21.0,
            'y_percent' => 44.0,
            'position' => 1,
            'is_active' => true,
        ]);

    $payload = app(QuestionExplanationAnnotationPayloadBuilder::class)
        ->forRuntime($question->explanationAnnotations()->get());

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['annotation_type'])->toBe('text')
        ->and($payload[0]['label'])->toBe('LEWA STRONA')
        ->and($payload[0]['tone'])->toBe('warning')
        ->and($payload[0]['x_percent'])->toBe(21.0)
        ->and($payload[0]['y_percent'])->toBe(44.0);
});
