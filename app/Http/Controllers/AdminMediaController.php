<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminMediaConfirmRequest;
use App\Http\Requests\AdminMediaPresignRequest;
use App\Http\Requests\AdminMediaReorderRequest;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Support\AdminMediaUploadService;
use App\Support\AuditLogService;
use App\Support\MediaUrlResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMediaController extends Controller
{
    public function presign(
        AdminMediaPresignRequest $request,
        AdminMediaUploadService $adminMediaUploadService,
    ): JsonResponse {
        $question = Question::query()->findOrFail($request->integer('question_id'));

        $payload = $adminMediaUploadService->presign(
            $request->user(),
            $question,
            [
                'kind' => (string) $request->string('kind'),
                'mime_type' => (string) $request->string('mime_type'),
                'bytes' => $request->integer('bytes'),
                'variant' => (string) $request->string('variant'),
            ],
            $request->header('Idempotency-Key'),
        );

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function confirm(
        AdminMediaConfirmRequest $request,
        AdminMediaUploadService $adminMediaUploadService,
        AuditLogService $auditLogService,
        MediaUrlResolver $mediaUrlResolver,
    ): JsonResponse {
        $result = $adminMediaUploadService->confirm($request->user(), $request->validated());

        /** @var QuestionMedia $media */
        $media = $result['media'];
        $media->refresh();

        $auditLogService->record(
            $result['status'] === 201 ? 'admin.question_media.created' : 'admin.question_media.updated',
            'question_media',
            $media->getKey(),
            $request->user(),
            [
                'source' => 'admin_api',
                'question_id' => $media->question_id,
                'kind' => $media->kind,
                'disk' => $media->disk,
                'path' => $media->path,
                'poster_path' => $media->poster_path,
                'variant' => $media->variant,
                'sort_order' => $media->sort_order,
                'bytes' => $media->bytes,
            ],
            $request,
        );

        return response()->json([
            'data' => $this->media($media, $mediaUrlResolver),
        ], $result['status']);
    }

    public function reorder(
        AdminMediaReorderRequest $request,
        Question $question,
        AdminMediaUploadService $adminMediaUploadService,
        AuditLogService $auditLogService,
        MediaUrlResolver $mediaUrlResolver,
    ): JsonResponse {
        $items = $adminMediaUploadService->reorder(
            $question,
            array_map('intval', $request->validated('media_ids', [])),
        );

        $auditLogService->record(
            'admin.question_media.reordered',
            'question',
            $question->getKey(),
            $request->user(),
            [
                'source' => 'admin_api',
                'question_id' => $question->getKey(),
                'media_ids' => array_values(array_map('intval', $request->validated('media_ids', []))),
                'media_count' => $items->count(),
            ],
            $request,
        );

        return response()->json([
            'data' => [
                'items' => $items
                    ->map(fn (QuestionMedia $media): array => $this->media($media, $mediaUrlResolver))
                    ->values(),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        QuestionMedia $questionMedia,
        AdminMediaUploadService $adminMediaUploadService,
        AuditLogService $auditLogService,
        MediaUrlResolver $mediaUrlResolver,
    ): JsonResponse {
        abort_unless((bool) $request->user()?->is_admin, 403);

        $snapshot = [
            'question_id' => $questionMedia->question_id,
            'kind' => $questionMedia->kind,
            'disk' => $questionMedia->disk,
            'path' => $questionMedia->path,
            'poster_path' => $questionMedia->poster_path,
            'variant' => $questionMedia->variant,
            'sort_order' => $questionMedia->sort_order,
            'bytes' => $questionMedia->bytes,
        ];
        $question = $questionMedia->question()->first();
        $entityId = $questionMedia->getKey();
        $adminMediaUploadService->delete($questionMedia);

        $auditLogService->record(
            'admin.question_media.deleted',
            'question_media',
            $entityId,
            $request->user(),
            [
                'source' => 'admin_api',
                ...$snapshot,
            ],
            $request,
        );

        $items = $question
            ? $question->fresh('media')->media
                ->map(fn (QuestionMedia $media): array => $this->media($media, $mediaUrlResolver))
                ->values()
            : collect();

        return response()->json([
            'data' => [
                'deleted' => true,
                'items' => $items,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function media(QuestionMedia $media, MediaUrlResolver $mediaUrlResolver): array
    {
        return [
            'id' => $media->getKey(),
            'question_id' => $media->question_id,
            'kind' => $media->kind,
            'disk' => $media->disk,
            'path' => $media->path,
            'poster_path' => $media->poster_path,
            'url' => $mediaUrlResolver->resolve($media->path, $media->disk),
            'poster_url' => $mediaUrlResolver->resolve($media->poster_path, $media->disk),
            'mime_type' => $media->mime_type,
            'bytes' => $media->bytes,
            'width' => $media->width,
            'height' => $media->height,
            'duration_seconds' => $media->duration_seconds,
            'variant' => $media->variant,
            'sort_order' => $media->sort_order,
            'metadata' => $media->metadata,
        ];
    }
}
