<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLegalContentQuestionReferenceStoreRequest;
use App\Models\LegalContentPage;
use App\Models\LegalUnit;
use App\Models\QuestionLegalReference;
use App\Support\AuditLogService;
use App\Support\QuestionLegalReferenceAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLegalContentQuestionReferenceController extends Controller
{
    public function index(
        Request $request,
        LegalContentPage $legalContentPage,
        QuestionLegalReferenceAssignmentService $assignmentService,
    ): JsonResponse {
        abort_unless((bool) $request->user()?->isAdministrator(), 403);

        return response()->json([
            'data' => [
                'questions' => $assignmentService
                    ->searchForPage(
                        $legalContentPage,
                        (string) $request->string('q'),
                        $request->integer('limit', 30),
                    )
                    ->all(),
            ],
        ]);
    }

    public function store(
        AdminLegalContentQuestionReferenceStoreRequest $request,
        LegalContentPage $legalContentPage,
        QuestionLegalReferenceAssignmentService $assignmentService,
        AuditLogService $auditLogService,
    ): JsonResponse {
        $legalUnit = LegalUnit::query()->findOrFail((int) $request->validated('legal_unit_id'));
        $result = $assignmentService->assign(
            $legalContentPage,
            $legalUnit,
            $request->validated('external_ids'),
            $request->validated('public_notes', []),
        );

        $auditLogService->record(
            'admin.legal_content_question_references.assigned',
            'legal_content_page',
            $legalContentPage->getKey(),
            $request->user(),
            [
                'source' => 'public_legal_content_inline',
                'legal_content_page_id' => $legalContentPage->getKey(),
                'legal_content_page_slug' => $legalContentPage->slug,
                'legal_unit_id' => $legalUnit->getKey(),
                ...$result,
            ],
            $request,
        );

        return response()->json([
            'data' => [
                'assignment' => $result,
            ],
        ]);
    }

    public function destroy(
        Request $request,
        LegalContentPage $legalContentPage,
        QuestionLegalReference $questionLegalReference,
        QuestionLegalReferenceAssignmentService $assignmentService,
        AuditLogService $auditLogService,
    ): JsonResponse {
        abort_unless((bool) $request->user()?->isAdministrator(), 403);

        $result = $assignmentService->remove($legalContentPage, $questionLegalReference);

        $auditLogService->record(
            'admin.legal_content_question_references.removed',
            'legal_content_page',
            $legalContentPage->getKey(),
            $request->user(),
            [
                'source' => 'public_legal_content_inline',
                'legal_content_page_id' => $legalContentPage->getKey(),
                'legal_content_page_slug' => $legalContentPage->slug,
                'question_legal_reference_id' => $questionLegalReference->getKey(),
                ...$result,
            ],
            $request,
        );

        return response()->json([
            'data' => [
                'removal' => $result,
            ],
        ]);
    }
}
