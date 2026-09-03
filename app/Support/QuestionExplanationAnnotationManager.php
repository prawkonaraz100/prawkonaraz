<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationAnnotation;

class QuestionExplanationAnnotationManager
{
    /**
     * @param  array<int, array<string, mixed>>|null  $rows
     */
    public function syncAnnotations(Question $question, ?array $rows, ?int $actorId = null): void
    {
        $normalizedRows = $this->normalizeRows($rows);

        $question->explanationAnnotations()->delete();

        if ($normalizedRows === []) {
            return;
        }

        $question->explanationAnnotations()->createMany(
            array_map(
                fn (array $row): array => [
                    ...$row,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ],
                $normalizedRows,
            ),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRows(?array $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function (mixed $row, int $index): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $targetKind = $this->normalizeString($row['target_kind'] ?? null);
                $frameTimeSeconds = $this->normalizeInteger($row['frame_time_seconds'] ?? null);
                $annotationType = $this->normalizeString($row['annotation_type'] ?? null);
                $label = $this->normalizeString($row['label'] ?? null);
                $tone = $this->normalizeString($row['tone'] ?? null);
                $xPercent = $this->normalizeNumber($row['x_percent'] ?? null);
                $yPercent = $this->normalizeNumber($row['y_percent'] ?? null);
                $widthPercent = $this->normalizeNumber($row['width_percent'] ?? null);
                $heightPercent = $this->normalizeNumber($row['height_percent'] ?? null);
                $arrowLengthPercent = $this->normalizeNumber($row['arrow_length_percent'] ?? null);
                $arrowAngleDegrees = $this->normalizeInteger($row['arrow_angle_degrees'] ?? null);
                $arrowStrokePercent = $this->normalizeNumber($row['arrow_stroke_percent'] ?? null);
                $arrowHeadPercent = $this->normalizeNumber($row['arrow_head_percent'] ?? null);
                $isActive = (bool) ($row['is_active'] ?? true);
                $position = max((int) ($row['position'] ?? ($index + 1)), 1);

                if (
                    $annotationType === null
                    && $label === null
                    && $xPercent === null
                    && $yPercent === null
                    && $widthPercent === null
                    && $heightPercent === null
                    && $arrowLengthPercent === null
                    && $arrowAngleDegrees === null
                    && $arrowStrokePercent === null
                    && $arrowHeadPercent === null
                    && ! $isActive
                ) {
                    return null;
                }

                $resolvedTargetKind = in_array($targetKind, [
                    QuestionExplanationAnnotation::TARGET_KIND_QUESTION_IMAGE,
                    QuestionExplanationAnnotation::TARGET_KIND_VIDEO_FRAME,
                ], true)
                    ? $targetKind
                    : QuestionExplanationAnnotation::TARGET_KIND_QUESTION_IMAGE;
                $resolvedAnnotationType = in_array($annotationType, [
                    QuestionExplanationAnnotation::ANNOTATION_TYPE_LABEL,
                    QuestionExplanationAnnotation::ANNOTATION_TYPE_TEXT,
                    QuestionExplanationAnnotation::ANNOTATION_TYPE_CIRCLE,
                    QuestionExplanationAnnotation::ANNOTATION_TYPE_ARROW,
                ], true)
                    ? $annotationType
                    : QuestionExplanationAnnotation::ANNOTATION_TYPE_LABEL;
                $resolvedWidthPercent = $resolvedAnnotationType === QuestionExplanationAnnotation::ANNOTATION_TYPE_CIRCLE
                    ? $widthPercent
                    : null;
                $resolvedHeightPercent = $resolvedAnnotationType === QuestionExplanationAnnotation::ANNOTATION_TYPE_CIRCLE
                    ? $heightPercent
                    : null;
                $resolvedArrowLengthPercent = $resolvedAnnotationType === QuestionExplanationAnnotation::ANNOTATION_TYPE_ARROW
                    ? ($arrowLengthPercent ?? QuestionExplanationAnnotation::ARROW_DEFAULT_LENGTH_PERCENT)
                    : null;
                $resolvedArrowAngleDegrees = $resolvedAnnotationType === QuestionExplanationAnnotation::ANNOTATION_TYPE_ARROW
                    ? ($arrowAngleDegrees ?? QuestionExplanationAnnotation::ARROW_DEFAULT_ANGLE_DEGREES)
                    : null;
                $resolvedArrowStrokePercent = $resolvedAnnotationType === QuestionExplanationAnnotation::ANNOTATION_TYPE_ARROW
                    ? ($arrowStrokePercent ?? QuestionExplanationAnnotation::ARROW_DEFAULT_STROKE_PERCENT)
                    : null;
                $resolvedArrowHeadPercent = $resolvedAnnotationType === QuestionExplanationAnnotation::ANNOTATION_TYPE_ARROW
                    ? ($arrowHeadPercent ?? QuestionExplanationAnnotation::ARROW_DEFAULT_HEAD_PERCENT)
                    : null;

                return [
                    'target_kind' => $resolvedTargetKind,
                    'frame_time_seconds' => $resolvedTargetKind === QuestionExplanationAnnotation::TARGET_KIND_VIDEO_FRAME
                        ? $frameTimeSeconds
                        : null,
                    'annotation_type' => $resolvedAnnotationType,
                    'x_percent' => $xPercent ?? 0.0,
                    'y_percent' => $yPercent ?? 0.0,
                    'width_percent' => $resolvedWidthPercent,
                    'height_percent' => $resolvedHeightPercent,
                    'arrow_length_percent' => $resolvedArrowLengthPercent,
                    'arrow_angle_degrees' => $resolvedArrowAngleDegrees,
                    'arrow_stroke_percent' => $resolvedArrowStrokePercent,
                    'arrow_head_percent' => $resolvedArrowHeadPercent,
                    'label' => $label,
                    'tone' => $tone ?? 'info',
                    'position' => $position,
                    'is_active' => $isActive,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function normalizeNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }
}
