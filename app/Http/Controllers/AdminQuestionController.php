<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminQuestionUpsertRequest;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Support\AdminQuestionService;
use App\Support\AuditLogService;
use App\Support\MediaUrlResolver;
use Illuminate\Http\JsonResponse;

class AdminQuestionController extends Controller
{
    public function store(
        AdminQuestionUpsertRequest $request,
        AdminQuestionService $adminQuestionService,
        AuditLogService $auditLogService,
        MediaUrlResolver $mediaUrlResolver,
    ): JsonResponse {
        $result = $adminQuestionService->upsert($request->validated());

        /** @var Question $question */
        $question = $result['question'];
        $question->refresh()->load('licenseCategory', 'media');

        $action = $result['action'] === 'created'
            ? 'admin.question.created'
            : 'admin.question.updated';

        $auditLogService->record(
            $action,
            'question',
            $question->getKey(),
            $request->user(),
            [
                'source' => 'admin_api',
                'external_id' => $question->external_id,
                'license_category_id' => $question->license_category_id,
                'question_type' => $question->question_type,
                'difficulty' => $question->difficulty,
                'points' => $question->points,
                'media_count' => $question->media->count(),
            ],
            $request,
        );

        return response()->json([
            'data' => [
                'question' => $this->question($question, $mediaUrlResolver),
            ],
            'meta' => [
                'action' => $result['action'],
            ],
        ], $result['action'] === 'created' ? 201 : 200);
    }

    /**
     * @return array<string, mixed>
     */
    protected function question(Question $question, MediaUrlResolver $mediaUrlResolver): array
    {
        return [
            'id' => $question->getKey(),
            'license_category' => [
                'id' => $question->licenseCategory?->getKey(),
                'code' => $question->licenseCategory?->code,
                'name' => $question->licenseCategory?->name,
            ],
            'external_id' => $question->external_id,
            'prompt' => $question->prompt,
            'explanation' => $question->explanation,
            'option_a' => $question->option_a,
            'option_b' => $question->option_b,
            'option_c' => $question->option_c,
            'correct_answer' => strtoupper((string) $question->correct_answer),
            'difficulty' => $question->difficulty,
            'points' => $question->points,
            'question_type' => $question->question_type,
            'is_active' => $question->is_active,
            'source' => $question->source,
            'published_at' => optional($question->published_at)->toIso8601String(),
            'media' => $question->media
                ->map(fn (QuestionMedia $media): array => [
                    'id' => $media->getKey(),
                    'kind' => $media->kind,
                    'disk' => $media->disk,
                    'path' => $media->path,
                    'poster_path' => $media->poster_path,
                    'url' => $mediaUrlResolver->resolve($media->path, $media->disk),
                    'poster_url' => $mediaUrlResolver->resolve($media->poster_path, $media->disk),
                    'mime_type' => $media->mime_type,
                    'bytes' => $media->bytes,
                    'duration_seconds' => $media->duration_seconds,
                    'width' => $media->width,
                    'height' => $media->height,
                    'variant' => $media->variant,
                    'sort_order' => $media->sort_order,
                    'metadata' => $media->metadata,
                ])
                ->values(),
        ];
    }
}
