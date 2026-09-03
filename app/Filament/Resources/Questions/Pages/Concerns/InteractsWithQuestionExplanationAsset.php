<?php

namespace App\Filament\Resources\Questions\Pages\Concerns;

use App\Models\Question;
use App\Models\QuestionExplanationAnnotation;
use App\Models\QuestionExplanationAsset;
use App\Models\SharedQuestionExplanationAsset;
use App\Models\TrafficSign;
use App\Support\AuditLogService;
use App\Support\MediaUrlResolver;
use App\Support\QuestionExplanationAnnotationManager;
use App\Support\QuestionExplanationAssetManager;
use App\Support\SharedQuestionExplanationAssetManager;
use App\Support\SharedQuestionExplanationAssetResolver;
use App\Support\SharedQuestionScopeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait InteractsWithQuestionExplanationAsset
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $questionExplanationAssetData = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $questionExplanationAnnotationsData = null;

    protected string $questionExplanationAssetApplyScope = 'single';

    protected string $questionExplanationAnnotationsApplyScope = 'shared_external_id';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractQuestionExplanationAssetData(array $data): array
    {
        if (is_array($data['explanation_asset'] ?? null)) {
            $assetData = $data['explanation_asset'];
            $assetData['file_path'] = $this->normalizeExplanationAssetFilePath($assetData['file_path'] ?? null);
            $assetData['disk'] = $this->normalizeExplanationAssetValue($assetData['disk'] ?? null)
                ?? ($assetData['file_path'] !== null ? (string) config('media.public_disk', 'public') : null);
            $assetData['traffic_sign_id'] = isset($assetData['traffic_sign_id']) && $assetData['traffic_sign_id'] ? (int) $assetData['traffic_sign_id'] : null;
            $this->questionExplanationAssetData = $assetData;
        } else {
            $this->questionExplanationAssetData = null;
        }
        $this->questionExplanationAnnotationsData = is_array($data['explanation_annotations'] ?? null)
            ? array_values(array_filter($data['explanation_annotations'], 'is_array'))
            : null;
        $this->questionExplanationAssetApplyScope = in_array(($data['explanation_asset_apply_scope'] ?? 'single'), ['single', 'shared_external_id'], true)
            ? (string) $data['explanation_asset_apply_scope']
            : 'single';
        $this->questionExplanationAnnotationsApplyScope = in_array(($data['explanation_annotations_apply_scope'] ?? 'shared_external_id'), ['single', 'shared_external_id'], true)
            ? (string) $data['explanation_annotations_apply_scope']
            : 'shared_external_id';

        unset($data['explanation_asset']);
        unset($data['explanation_annotations']);
        unset($data['explanation_asset_apply_scope']);
        unset($data['explanation_annotations_apply_scope']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillQuestionExplanationAssetData(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Question) {
            return $data;
        }

        $asset = app(SharedQuestionExplanationAssetResolver::class)->resolveReferenceAsset($record);

        $trafficSignImageUrl = null;
        if ($asset?->traffic_sign_id) {
            $sign = $asset->trafficSign ?? TrafficSign::find($asset->traffic_sign_id);
            if ($sign?->image_path) {
                $trafficSignImageUrl = app(MediaUrlResolver::class)->resolve($sign->image_path, 'public');
            }
        }

        $data['explanation_asset'] = [
            'disk' => $asset?->disk,
            'file_path' => filled($asset?->file_path) ? [$asset->file_path] : [],
            'traffic_sign_id' => $asset?->traffic_sign_id,
            'traffic_sign_image_url' => $trafficSignImageUrl,
            'title' => $asset?->title,
            'body' => $asset?->body,
            'caption' => $asset?->caption,
            'alt_text' => $asset?->alt_text,
            'is_active' => $asset?->is_active ?? false,
        ];
        $data['explanation_asset_apply_scope'] = $asset instanceof SharedQuestionExplanationAsset
            ? 'shared_external_id'
            : 'single';
        $data['explanation_annotations'] = $record->explanationAnnotations()
            ->get()
            ->map(fn (QuestionExplanationAnnotation $annotation): array => [
                'target_kind' => $annotation->target_kind,
                'frame_time_seconds' => $annotation->frame_time_seconds,
                'annotation_type' => $annotation->annotation_type,
                'tone' => $annotation->tone,
                'label' => $annotation->label,
                'x_percent' => $annotation->x_percent,
                'y_percent' => $annotation->y_percent,
                'width_percent' => $annotation->width_percent,
                'height_percent' => $annotation->height_percent,
                'arrow_length_percent' => $annotation->arrow_length_percent,
                'arrow_angle_degrees' => $annotation->arrow_angle_degrees,
                'arrow_stroke_percent' => $annotation->arrow_stroke_percent,
                'arrow_head_percent' => $annotation->arrow_head_percent,
                'position' => $annotation->position,
                'is_active' => $annotation->is_active,
            ])
            ->values()
            ->all();
        $data['explanation_annotations_apply_scope'] = 'shared_external_id';

        return $data;
    }

    protected function syncQuestionExplanationAsset(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof Question) {
            return;
        }

        $assetData = $this->prepareQuestionExplanationAssetDataForSync(
            $record,
            $this->questionExplanationAssetData,
            $this->currentExplanationAssetForSelectedScope($record),
        );

        $this->validateQuestionExplanationAssetData($assetData);

        $actorId = Auth::id();
        $applyScope = $this->resolveQuestionExplanationAssetApplyScope($record);

        if ($applyScope === 'shared_external_id') {
            $questionsToUpdate = app(SharedQuestionScopeService::class)->questionsForSharedExternalId($record);

            app(SharedQuestionExplanationAssetManager::class)->syncReferenceSign(
                $record,
                $assetData,
                $actorId,
            );

            $questionsToUpdate->each(fn (Question $questionToUpdate) => app(QuestionExplanationAssetManager::class)->syncReferenceSign(
                $questionToUpdate,
                null,
                $actorId,
            ));

            app(AuditLogService::class)->record(
                'admin.question.explanation_asset_updated',
                'question',
                $record->getKey(),
                Auth::user(),
                [
                    'source' => 'question_edit_form',
                    'question_id' => $record->getKey(),
                    'apply_scope' => $applyScope,
                    'shared_external_id' => $record->external_id,
                    'source_scope' => SharedQuestionExplanationAsset::sourceScopeFor($record->source),
                    'affected_question_ids' => $questionsToUpdate->pluck('id')->map(fn (mixed $id) => (int) $id)->values()->all(),
                    'has_asset_after' => is_array($assetData),
                ],
            );

            return;
        }

        app(QuestionExplanationAssetManager::class)->syncReferenceSign(
            $record,
            $assetData,
            $actorId,
        );

        app(AuditLogService::class)->record(
            'admin.question.explanation_asset_updated',
            'question',
            $record->getKey(),
            Auth::user(),
            [
                'source' => 'question_edit_form',
                'question_id' => $record->getKey(),
                'apply_scope' => $applyScope,
                'shared_external_id' => $record->external_id,
                'affected_question_ids' => [$record->getKey()],
                'has_asset_after' => is_array($assetData),
            ],
        );
    }

    protected function syncQuestionExplanationAnnotations(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof Question) {
            return;
        }

        $this->validateQuestionExplanationAnnotationsData($this->questionExplanationAnnotationsData);

        $applyScope = $this->questionExplanationAnnotationsApplyScope;
        $actorId = Auth::id();

        if ($applyScope === 'shared_external_id' && filled($record->external_id)) {
            $questionsToUpdate = app(SharedQuestionScopeService::class)->questionsForSharedExternalId($record);

            $questionsToUpdate->each(fn (Question $questionToUpdate) => app(QuestionExplanationAnnotationManager::class)->syncAnnotations(
                $questionToUpdate,
                $this->questionExplanationAnnotationsData,
                $actorId,
            ));

            app(AuditLogService::class)->record(
                'admin.question.explanation_annotations_updated',
                'question',
                $record->getKey(),
                Auth::user(),
                [
                    'source' => 'question_edit_form',
                    'question_id' => $record->getKey(),
                    'apply_scope' => $applyScope,
                    'shared_external_id' => $record->external_id,
                    'affected_question_ids' => $questionsToUpdate->pluck('id')->map(fn (mixed $id) => (int) $id)->values()->all(),
                    'count' => count($this->questionExplanationAnnotationsData ?? []),
                ],
            );
        } else {
            app(QuestionExplanationAnnotationManager::class)->syncAnnotations(
                $record,
                $this->questionExplanationAnnotationsData,
                $actorId,
            );

            app(AuditLogService::class)->record(
                'admin.question.explanation_annotations_updated',
                'question',
                $record->getKey(),
                Auth::user(),
                [
                    'source' => 'question_edit_form',
                    'question_id' => $record->getKey(),
                    'apply_scope' => 'single',
                    'affected_question_ids' => [$record->getKey()],
                    'count' => count($this->questionExplanationAnnotationsData ?? []),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     */
    protected function validateQuestionExplanationAssetData(?array $attributes): void
    {
        if (! is_array($attributes)) {
            return;
        }

        $filePath = $this->normalizeExplanationAssetFilePath($attributes['file_path'] ?? null);
        $trafficSignId = isset($attributes['traffic_sign_id']) && $attributes['traffic_sign_id'] ? (int) $attributes['traffic_sign_id'] : null;
        $title = $this->normalizeExplanationAssetValue($attributes['title'] ?? null);
        $body = $this->normalizeExplanationAssetText($attributes['body'] ?? null);
        $caption = $this->normalizeExplanationAssetValue($attributes['caption'] ?? null);
        $altText = $this->normalizeExplanationAssetValue($attributes['alt_text'] ?? null);
        $isActive = (bool) ($attributes['is_active'] ?? false);
        $primaryExplanation = $this->normalizeExplanationAssetText(data_get($this, 'data.explanation'));
        $hasAnyContent = $filePath !== null || $trafficSignId !== null || $title !== null || $body !== null || $caption !== null || $altText !== null;

        if (! $hasAnyContent && ! $isActive) {
            return;
        }

        $errors = [];

        if ($filePath === null && $trafficSignId === null) {
            $errors['explanation_asset.file_path'] = 'Dodaj obraz materiału referencyjnego lub wybierz znak z bazy.';
        }

        if (($filePath !== null || $isActive) && $trafficSignId === null && $body === null && $primaryExplanation === null) {
            $errors['explanation_asset.body'] = 'Dodaj tekst zapasowy albo uzupełnij pole Wyjaśnienie.';
        }

        if (($filePath !== null || $isActive) && $trafficSignId === null && $altText === null) {
            $errors['explanation_asset.alt_text'] = 'Alt text materiału referencyjnego jest wymagany.';
        }

        if ($title !== null && mb_strlen($title) > 120) {
            $errors['explanation_asset.title'] = 'Tytuł materiału referencyjnego może mieć maksymalnie 120 znaków.';
        }

        if ($body !== null && mb_strlen($body) > 1600) {
            $errors['explanation_asset.body'] = 'Opis materiału referencyjnego może mieć maksymalnie 1600 znaków.';
        }

        if ($caption !== null && mb_strlen($caption) > 255) {
            $errors['explanation_asset.caption'] = 'Podpis materiału referencyjnego może mieć maksymalnie 255 znaków.';
        }

        if ($altText !== null && mb_strlen($altText) > 255) {
            $errors['explanation_asset.alt_text'] = 'Alt text materiału referencyjnego może mieć maksymalnie 255 znaków.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $rows
     */
    protected function validateQuestionExplanationAnnotationsData(?array $rows): void
    {
        if (! is_array($rows)) {
            return;
        }

        $errors = [];
        $meaningfulRows = 0;

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $path = "explanation_annotations.{$index}";
            $targetKind = $this->normalizeExplanationAssetValue($row['target_kind'] ?? null);
            $frameTimeSeconds = $this->normalizeExplanationAnnotationNumber($row['frame_time_seconds'] ?? null);
            $annotationType = $this->normalizeExplanationAssetValue($row['annotation_type'] ?? null);
            $label = $this->normalizeExplanationAssetValue($row['label'] ?? null);
            $tone = $this->normalizeExplanationAssetValue($row['tone'] ?? null);
            $xPercent = $this->normalizeExplanationAnnotationNumber($row['x_percent'] ?? null);
            $yPercent = $this->normalizeExplanationAnnotationNumber($row['y_percent'] ?? null);
            $widthPercent = $this->normalizeExplanationAnnotationNumber($row['width_percent'] ?? null);
            $heightPercent = $this->normalizeExplanationAnnotationNumber($row['height_percent'] ?? null);
            $arrowLengthPercent = $this->normalizeExplanationAnnotationNumber($row['arrow_length_percent'] ?? null);
            $arrowAngleDegrees = $this->normalizeExplanationAnnotationInteger($row['arrow_angle_degrees'] ?? null);
            $arrowStrokePercent = $this->normalizeExplanationAnnotationNumber($row['arrow_stroke_percent'] ?? null);
            $arrowHeadPercent = $this->normalizeExplanationAnnotationNumber($row['arrow_head_percent'] ?? null);
            $isActive = (bool) ($row['is_active'] ?? true);
            $hasAnyContent = $annotationType !== null
                || $label !== null
                || $xPercent !== null
                || $yPercent !== null
                || $widthPercent !== null
                || $heightPercent !== null
                || $arrowLengthPercent !== null
                || $arrowAngleDegrees !== null
                || $arrowStrokePercent !== null
                || $arrowHeadPercent !== null
                || $frameTimeSeconds !== null
                || $isActive;

            if (! $hasAnyContent) {
                continue;
            }

            $meaningfulRows++;

            if (! in_array($targetKind, [null, 'question_image', 'video_frame'], true)) {
                $errors["{$path}.target_kind"] = 'Wybierz poprawny obszar adnotacji.';
            }

            if (! in_array($annotationType, [
                QuestionExplanationAnnotation::ANNOTATION_TYPE_LABEL,
                QuestionExplanationAnnotation::ANNOTATION_TYPE_TEXT,
                QuestionExplanationAnnotation::ANNOTATION_TYPE_CIRCLE,
                QuestionExplanationAnnotation::ANNOTATION_TYPE_ARROW,
            ], true)) {
                $errors["{$path}.annotation_type"] = 'Wybierz poprawny typ adnotacji.';
            }

            if (! in_array($tone, [null, 'info', 'warning', 'danger'], true)) {
                $errors["{$path}.tone"] = 'Wybierz poprawny ton adnotacji.';
            }

            foreach ([
                'x_percent' => $xPercent,
                'y_percent' => $yPercent,
            ] as $field => $value) {
                if ($value === null) {
                    $errors["{$path}.{$field}"] = 'To pole jest wymagane.';

                    continue;
                }

                if ($value < 0 || $value > 100) {
                    $errors["{$path}.{$field}"] = 'Wspolrzedne musza byc w zakresie 0-100.';
                }
            }

            if ($targetKind === 'video_frame') {
                if ($frameTimeSeconds === null) {
                    $errors["{$path}.frame_time_seconds"] = 'Podaj sekunde stopklatki dla adnotacji wideo.';
                } elseif ($frameTimeSeconds < 0) {
                    $errors["{$path}.frame_time_seconds"] = 'Sekunda stopklatki nie moze byc ujemna.';
                }
            }

            if (
                in_array($annotationType, [
                    QuestionExplanationAnnotation::ANNOTATION_TYPE_LABEL,
                    QuestionExplanationAnnotation::ANNOTATION_TYPE_TEXT,
                ], true)
                && $label === null
            ) {
                $errors["{$path}.label"] = 'Tekst jest wymagany dla typu label i text.';
            }

            if ($annotationType === QuestionExplanationAnnotation::ANNOTATION_TYPE_CIRCLE) {
                if ($widthPercent === null || $widthPercent <= 0 || $widthPercent > 100) {
                    $errors["{$path}.width_percent"] = 'Szerokosc okregu musi byc w zakresie 0-100.';
                }

                if ($heightPercent === null || $heightPercent <= 0 || $heightPercent > 100) {
                    $errors["{$path}.height_percent"] = 'Wysokosc okregu musi byc w zakresie 0-100.';
                }
            }

            if ($annotationType === QuestionExplanationAnnotation::ANNOTATION_TYPE_ARROW) {
                if ($arrowLengthPercent === null || $arrowLengthPercent < 1 || $arrowLengthPercent > 100) {
                    $errors["{$path}.arrow_length_percent"] = 'Dlugosc strzalki musi byc w zakresie 1-100.';
                }

                if ($arrowAngleDegrees === null || $arrowAngleDegrees < 0 || $arrowAngleDegrees > 359) {
                    $errors["{$path}.arrow_angle_degrees"] = 'Kat strzalki musi byc w zakresie 0-359.';
                }

                if ($arrowStrokePercent === null || $arrowStrokePercent < 0.5 || $arrowStrokePercent > 8) {
                    $errors["{$path}.arrow_stroke_percent"] = 'Grubosc strzalki musi byc w zakresie 0.5-8.';
                }

                if ($arrowHeadPercent === null || $arrowHeadPercent < 2 || $arrowHeadPercent > 30) {
                    $errors["{$path}.arrow_head_percent"] = 'Grot strzalki musi byc w zakresie 2-30.';
                }
            }

            if ($label !== null && mb_strlen($label) > 120) {
                $errors["{$path}.label"] = 'Etykieta adnotacji moze miec maksymalnie 120 znakow.';
            }
        }

        if ($meaningfulRows > 5) {
            $errors['explanation_annotations'] = 'MVP wspiera maksymalnie 5 adnotacji na pytanie.';
        }

        $record = $this->getRecord();

        if ($meaningfulRows > 0 && $record instanceof Question) {
            $record->loadMissing('media');
            $hasVideo = $record->media->contains(
                fn (mixed $media) => data_get($media, 'kind') === 'video',
            );

            if (! $hasVideo) {
                foreach ($rows as $index => $row) {
                    if (($row['target_kind'] ?? null) === QuestionExplanationAnnotation::TARGET_KIND_VIDEO_FRAME) {
                        $errors["explanation_annotations.{$index}.target_kind"] = 'To pytanie nie ma medium wideo, więc nie moze miec adnotacji stopklatki.';
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function normalizeExplanationAssetValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function normalizeExplanationAssetFilePath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->flatten()
                ->reverse()
                ->first(fn (mixed $item): bool => is_string($item) && trim($item) !== '');
        }

        return $this->normalizeExplanationAssetValue($value);
    }

    protected function normalizeExplanationAssetText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim(preg_replace("/\r\n?/", "\n", $value) ?? $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function normalizeExplanationAnnotationNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function normalizeExplanationAnnotationInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>|null
     */
    protected function prepareQuestionExplanationAssetDataForSync(
        Question $question,
        ?array $attributes,
        QuestionExplanationAsset|SharedQuestionExplanationAsset|null $existingAsset = null,
    ): ?array {
        if (! is_array($attributes)) {
            return null;
        }

        if ($existingAsset !== null && ! array_key_exists('caption', $attributes)) {
            $attributes['caption'] = $existingAsset->caption;
        }

        if ($existingAsset === null) {
            return $attributes;
        }

        $normalizedFilePath = $this->normalizeExplanationAssetFilePath($attributes['file_path'] ?? null);
        $trafficSignId = isset($attributes['traffic_sign_id']) && $attributes['traffic_sign_id'] ? (int) $attributes['traffic_sign_id'] : null;
        $hasMeaningfulContent = $this->normalizeExplanationAssetValue($attributes['title'] ?? null) !== null
            || $this->normalizeExplanationAssetText($attributes['body'] ?? null) !== null
            || $this->normalizeExplanationAssetValue($attributes['caption'] ?? null) !== null
            || $this->normalizeExplanationAssetValue($attributes['alt_text'] ?? null) !== null
            || (bool) ($attributes['is_active'] ?? false)
            || $trafficSignId !== null;

        if ($normalizedFilePath !== null || $trafficSignId !== null || ! $hasMeaningfulContent) {
            return $attributes;
        }

        $attributes['file_path'] = $existingAsset->file_path;
        $attributes['disk'] = $existingAsset->disk;

        return $attributes;
    }

    protected function resolveQuestionExplanationAssetApplyScope(Question $question): string
    {
        if (! filled($question->external_id)) {
            return 'single';
        }

        return in_array($this->questionExplanationAssetApplyScope, ['single', 'shared_external_id'], true)
            ? $this->questionExplanationAssetApplyScope
            : 'single';
    }

    protected function currentExplanationAssetForSelectedScope(
        Question $question,
    ): QuestionExplanationAsset|SharedQuestionExplanationAsset|null {
        if ($this->resolveQuestionExplanationAssetApplyScope($question) === 'shared_external_id') {
            return app(SharedQuestionExplanationAssetManager::class)->referenceSignForQuestion($question);
        }

        return $question->referenceExplanationAsset()->first();
    }
}
