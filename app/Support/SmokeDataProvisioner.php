<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Support\Facades\Storage;

class SmokeDataProvisioner
{
    /**
     * @return array<string, mixed>
     */
    public function provision(): array
    {
        $disk = (string) config('media.public_disk', config('media.default_disk', 'public'));
        $assets = $this->writeAssets($disk);

        [$category, $categoryAction] = $this->upsertCategory();
        [$imageQuestion, $imageQuestionAction] = $this->upsertImageQuestion($category);
        [$videoQuestion, $videoQuestionAction] = $this->upsertVideoQuestion($category);
        [, $imageMediaAction] = $this->upsertMedia($imageQuestion, [
            'kind' => 'image',
            'disk' => $disk,
            'path' => $assets['image']['path'],
            'poster_path' => null,
            'mime_type' => 'image/webp',
            'bytes' => $assets['image']['bytes'],
            'duration_seconds' => null,
            'width' => null,
            'height' => null,
            'variant' => 'full',
            'sort_order' => 0,
            'metadata' => [
                'purpose' => 'smoke',
            ],
        ]);
        [, $videoMediaAction] = $this->upsertMedia($videoQuestion, [
            'kind' => 'video',
            'disk' => $disk,
            'path' => $assets['video']['path'],
            'poster_path' => $assets['poster']['path'],
            'mime_type' => 'video/mp4',
            'bytes' => $assets['video']['bytes'],
            'duration_seconds' => 8,
            'width' => null,
            'height' => null,
            'variant' => 'full',
            'sort_order' => 0,
            'metadata' => [
                'purpose' => 'smoke',
            ],
        ]);

        return [
            'disk' => $disk,
            'category' => [
                'code' => $category->code,
                'action' => $categoryAction,
            ],
            'questions' => [
                [
                    'external_id' => $imageQuestion->external_id,
                    'action' => $imageQuestionAction,
                ],
                [
                    'external_id' => $videoQuestion->external_id,
                    'action' => $videoQuestionAction,
                ],
            ],
            'media' => [
                [
                    'kind' => 'image',
                    'path' => $assets['image']['path'],
                    'action' => $imageMediaAction,
                ],
                [
                    'kind' => 'video',
                    'path' => $assets['video']['path'],
                    'poster_path' => $assets['poster']['path'],
                    'action' => $videoMediaAction,
                ],
            ],
            'assets' => array_values($assets),
        ];
    }

    /**
     * @return array{0: LicenseCategory, 1: string}
     */
    protected function upsertCategory(): array
    {
        $category = LicenseCategory::query()->firstOrNew([
            'code' => 'B',
        ]);
        $action = $category->exists ? 'updated' : 'created';

        $category->fill([
            'slug' => 'kategoria-b',
            'name' => 'Kategoria B',
            'description' => 'Deterministyczna kategoria bootstrapowana pod smoke testy i CI.',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $category->save();

        return [$category, $action];
    }

    /**
     * @return array{0: Question, 1: string}
     */
    protected function upsertImageQuestion(LicenseCategory $category): array
    {
        return $this->upsertQuestion($category, 'SMOKE-B-IMAGE-001', [
            'prompt' => 'Czy przed ruszeniem nalezy upewnic sie, ze pasy sa zapiete?',
            'explanation' => 'Pytanie smoke dla obrazu. Sluzy do automatycznej walidacji krytycznego flow.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'option_c' => null,
            'correct_answer' => 'a',
            'difficulty' => 1,
            'points' => 3,
            'question_type' => 'boolean',
        ]);
    }

    /**
     * @return array{0: Question, 1: string}
     */
    protected function upsertVideoQuestion(LicenseCategory $category): array
    {
        return $this->upsertQuestion($category, 'SMOKE-B-VIDEO-001', [
            'prompt' => 'Czy przed zmiana pasa nalezy ocenic sytuacje w lusterkach i martwym polu?',
            'explanation' => 'Pytanie smoke dla wideo. Sluzy do automatycznej walidacji mediow i sesji.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'option_c' => null,
            'correct_answer' => 'a',
            'difficulty' => 2,
            'points' => 3,
            'question_type' => 'boolean',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Question, 1: string}
     */
    protected function upsertQuestion(LicenseCategory $category, string $externalId, array $attributes): array
    {
        $question = Question::query()->firstOrNew([
            'license_category_id' => $category->getKey(),
            'external_id' => $externalId,
        ]);
        $action = $question->exists ? 'updated' : 'created';

        $question->fill([
            ...$attributes,
            'license_category_id' => $category->getKey(),
            'external_id' => $externalId,
            'is_active' => true,
            'source' => 'ops:seed-smoke-data',
            'published_at' => now(),
        ]);
        $question->save();

        return [$question, $action];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: QuestionMedia, 1: string}
     */
    protected function upsertMedia(Question $question, array $attributes): array
    {
        $media = QuestionMedia::query()->firstOrNew([
            'question_id' => $question->getKey(),
            'kind' => (string) $attributes['kind'],
            'variant' => (string) ($attributes['variant'] ?? 'full'),
        ]);
        $action = $media->exists ? 'updated' : 'created';

        $media->fill([
            ...$attributes,
            'question_id' => $question->getKey(),
        ]);
        $media->save();

        QuestionMedia::query()
            ->where('question_id', $question->getKey())
            ->where('kind', (string) $attributes['kind'])
            ->where('variant', (string) ($attributes['variant'] ?? 'full'))
            ->whereKeyNot($media->getKey())
            ->delete();

        return [$media, $action];
    }

    /**
     * @return array<string, array{path: string, bytes: int}>
     */
    protected function writeAssets(string $disk): array
    {
        $assets = [
            'image' => [
                'path' => 'smoke/b/image/full.webp',
                'contents' => 'smoke-image-webp-placeholder',
            ],
            'video' => [
                'path' => 'smoke/b/video/clip.mp4',
                'contents' => 'smoke-video-mp4-placeholder',
            ],
            'poster' => [
                'path' => 'smoke/b/video/poster.webp',
                'contents' => 'smoke-video-poster-webp-placeholder',
            ],
        ];

        $written = [];

        foreach ($assets as $key => $asset) {
            Storage::disk($disk)->put($asset['path'], $asset['contents']);

            $written[$key] = [
                'path' => $asset['path'],
                'bytes' => strlen($asset['contents']),
            ];
        }

        return $written;
    }
}
