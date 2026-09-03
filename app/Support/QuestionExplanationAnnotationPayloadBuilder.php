<?php

namespace App\Support;

use App\Models\QuestionExplanationAnnotation;
use Illuminate\Support\Collection;

class QuestionExplanationAnnotationPayloadBuilder
{
    /**
     * @param  iterable<QuestionExplanationAnnotation>  $annotations
     * @return array<int, array<string, mixed>>
     */
    public function forRuntime(iterable $annotations): array
    {
        return Collection::make($annotations)
            ->filter(fn (QuestionExplanationAnnotation $annotation) => $annotation->is_active)
            ->sortBy([
                ['position', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn (QuestionExplanationAnnotation $annotation): array => [
                'id' => $annotation->getKey(),
                'target_kind' => $annotation->target_kind,
                'frame_time_seconds' => $annotation->frame_time_seconds,
                'annotation_type' => $annotation->annotation_type,
                'x_percent' => $annotation->x_percent,
                'y_percent' => $annotation->y_percent,
                'width_percent' => $annotation->width_percent,
                'height_percent' => $annotation->height_percent,
                'arrow_length_percent' => $annotation->arrow_length_percent,
                'arrow_angle_degrees' => $annotation->arrow_angle_degrees,
                'arrow_stroke_percent' => $annotation->arrow_stroke_percent,
                'arrow_head_percent' => $annotation->arrow_head_percent,
                'label' => $annotation->label,
                'tone' => $annotation->tone,
                'position' => $annotation->position,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  iterable<QuestionExplanationAnnotation>  $annotations
     * @return array<int, array<string, mixed>>
     */
    public function forQuestionImage(iterable $annotations): array
    {
        return $this->forTargetKind($annotations, QuestionExplanationAnnotation::TARGET_KIND_QUESTION_IMAGE);
    }

    /**
     * @param  iterable<QuestionExplanationAnnotation>  $annotations
     * @return array<int, array<string, mixed>>
     */
    public function forVideoFrame(iterable $annotations): array
    {
        return $this->forTargetKind($annotations, QuestionExplanationAnnotation::TARGET_KIND_VIDEO_FRAME);
    }

    /**
     * @param  iterable<QuestionExplanationAnnotation>  $annotations
     * @return array<int, array<string, mixed>>
     */
    protected function forTargetKind(iterable $annotations, string $targetKind): array
    {
        return Collection::make($this->forRuntime($annotations))
            ->filter(fn (array $annotation): bool => ($annotation['target_kind'] ?? null) === $targetKind)
            ->values()
            ->all();
    }
}
