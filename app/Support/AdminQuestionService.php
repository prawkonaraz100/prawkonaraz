<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AdminQuestionService
{
    public function __construct(
        protected AdminMediaUploadService $adminMediaUploadService,
        protected QuestionDeliveryReadinessService $questionDeliveryReadinessService,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{question:Question,action:string}
     */
    public function upsert(array $attributes): array
    {
        $question = DB::transaction(function () use ($attributes): Question {
            $question = $this->resolveQuestion($attributes);
            $category = $this->resolveCategory($attributes, $question);

            $question->fill([
                'license_category_id' => $category->getKey(),
                'external_id' => $attributes['external_id'] ?? null,
                'prompt' => (string) $attributes['prompt'],
                'explanation' => $attributes['explanation'] ?? null,
                'option_a' => (string) $attributes['option_a'],
                'option_b' => (string) $attributes['option_b'],
                'option_c' => $attributes['option_c'] ?? null,
                'correct_answer' => strtolower((string) $attributes['correct_answer']),
                'difficulty' => (int) ($attributes['difficulty'] ?? 1),
                'points' => (int) ($attributes['points'] ?? 1),
                'question_type' => (string) ($attributes['question_type'] ?? 'single_choice'),
                'is_active' => (bool) ($attributes['is_active'] ?? true),
                'source' => $attributes['source'] ?? null,
                'published_at' => $attributes['published_at'] ?? now(),
            ]);
            $question->save();

            if (Arr::exists($attributes, 'media')) {
                $this->syncMedia($question, (array) $attributes['media']);
            }

            $this->questionDeliveryReadinessService->sync($question);

            return $question;
        });

        return [
            'question' => $question,
            'action' => $question->wasRecentlyCreated ? 'created' : 'updated',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveQuestion(array $attributes): Question
    {
        if (isset($attributes['question_id'])) {
            return Question::query()->findOrFail((int) $attributes['question_id']);
        }

        $categoryId = Arr::get($attributes, 'license_category_id');
        $externalId = Arr::get($attributes, 'external_id');

        if ($categoryId !== null && filled($externalId)) {
            return Question::query()->firstOrNew([
                'license_category_id' => (int) $categoryId,
                'external_id' => (string) $externalId,
            ]);
        }

        return new Question;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveCategory(array $attributes, Question $question): LicenseCategory
    {
        if (isset($attributes['license_category_id'])) {
            return LicenseCategory::query()->findOrFail((int) $attributes['license_category_id']);
        }

        if (filled($attributes['category_code'] ?? null)) {
            return LicenseCategory::query()
                ->where('code', (string) $attributes['category_code'])
                ->firstOrFail();
        }

        /** @var LicenseCategory $category */
        $category = $question->licenseCategory()->firstOrFail();

        return $category;
    }

    /**
     * @param  array<int, mixed>  $mediaEntries
     */
    protected function syncMedia(Question $question, array $mediaEntries): void
    {
        $currentMedia = $question->media()->get()->keyBy('path');
        $seenPaths = [];

        foreach (array_values($mediaEntries) as $index => $media) {
            if (! is_array($media)) {
                continue;
            }

            $path = (string) $media['path'];
            $seenPaths[] = $path;

            $question->media()->updateOrCreate(
                [
                    'path' => $path,
                ],
                [
                    'kind' => (string) ($media['kind'] ?? 'image'),
                    'disk' => (string) ($media['disk'] ?? config('media.default_disk')),
                    'poster_path' => $media['poster_path'] ?? null,
                    'mime_type' => $media['mime_type'] ?? null,
                    'bytes' => isset($media['bytes']) ? (int) $media['bytes'] : null,
                    'duration_seconds' => isset($media['duration_seconds']) ? (int) $media['duration_seconds'] : null,
                    'width' => isset($media['width']) ? (int) $media['width'] : null,
                    'height' => isset($media['height']) ? (int) $media['height'] : null,
                    'variant' => (string) ($media['variant'] ?? 'full'),
                    'sort_order' => (int) ($media['sort_order'] ?? $index),
                    'metadata' => is_array($media['metadata'] ?? null) ? $media['metadata'] : null,
                ],
            );
        }

        $currentMedia
            ->reject(fn (QuestionMedia $media): bool => in_array($media->path, $seenPaths, true))
            ->each(fn (QuestionMedia $media) => $this->adminMediaUploadService->delete($media));

        $question->media()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(fn (QuestionMedia $media, int $index) => $media->update([
                'sort_order' => $index,
            ]));
    }
}
