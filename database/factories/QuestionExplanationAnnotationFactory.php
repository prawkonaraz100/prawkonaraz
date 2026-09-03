<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionExplanationAnnotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionExplanationAnnotation>
 */
class QuestionExplanationAnnotationFactory extends Factory
{
    protected $model = QuestionExplanationAnnotation::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'target_kind' => QuestionExplanationAnnotation::TARGET_KIND_QUESTION_IMAGE,
            'frame_time_seconds' => null,
            'annotation_type' => QuestionExplanationAnnotation::ANNOTATION_TYPE_CIRCLE,
            'x_percent' => 42.5,
            'y_percent' => 31.0,
            'width_percent' => 14.0,
            'height_percent' => 18.0,
            'arrow_length_percent' => null,
            'arrow_angle_degrees' => null,
            'arrow_stroke_percent' => null,
            'arrow_head_percent' => null,
            'label' => null,
            'tone' => 'info',
            'position' => 1,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function label(string $label = 'Ten znak jest kluczowy'): static
    {
        return $this->state(fn () => [
            'annotation_type' => QuestionExplanationAnnotation::ANNOTATION_TYPE_LABEL,
            'label' => $label,
            'width_percent' => null,
            'height_percent' => null,
            'arrow_length_percent' => null,
            'arrow_angle_degrees' => null,
            'arrow_stroke_percent' => null,
            'arrow_head_percent' => null,
        ]);
    }

    public function text(string $text = 'LEWA STRONA'): static
    {
        return $this->state(fn () => [
            'annotation_type' => QuestionExplanationAnnotation::ANNOTATION_TYPE_TEXT,
            'label' => $text,
            'width_percent' => null,
            'height_percent' => null,
            'arrow_length_percent' => null,
            'arrow_angle_degrees' => null,
            'arrow_stroke_percent' => null,
            'arrow_head_percent' => null,
        ]);
    }

    public function arrow(): static
    {
        return $this->state(fn () => [
            'annotation_type' => QuestionExplanationAnnotation::ANNOTATION_TYPE_ARROW,
            'label' => null,
            'width_percent' => null,
            'height_percent' => null,
            'arrow_length_percent' => QuestionExplanationAnnotation::ARROW_DEFAULT_LENGTH_PERCENT,
            'arrow_angle_degrees' => QuestionExplanationAnnotation::ARROW_DEFAULT_ANGLE_DEGREES,
            'arrow_stroke_percent' => QuestionExplanationAnnotation::ARROW_DEFAULT_STROKE_PERCENT,
            'arrow_head_percent' => QuestionExplanationAnnotation::ARROW_DEFAULT_HEAD_PERCENT,
        ]);
    }

    public function videoFrame(int $frameTimeSeconds = 3): static
    {
        return $this->state(fn () => [
            'target_kind' => QuestionExplanationAnnotation::TARGET_KIND_VIDEO_FRAME,
            'frame_time_seconds' => $frameTimeSeconds,
        ]);
    }
}
