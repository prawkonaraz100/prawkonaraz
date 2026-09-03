<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Support\PjmCoverageService;
use App\Support\PjmQuestionProgressService;
use App\Support\StudyContextService;
use App\Support\StudySessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PjmSessionPageController extends Controller
{
    public function __invoke(
        Request $request,
        StudyContextService $studyContextService,
        PjmCoverageService $pjmCoverageService,
        PjmQuestionProgressService $pjmQuestionProgressService,
    ): Response {
        $category = LicenseCategory::query()->find($studyContextService->preferredCategoryId($request->user()));
        $coverage = $category
            ? collect($pjmCoverageService->categoryCoverage([$category->code]))->first()
            : null;
        $progress = $category
            ? $pjmQuestionProgressService->progressForCategory($category, $request->user())
            : null;

        return Inertia::render('Session/PjmIndex', [
            'category' => $category ? [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'short_name' => $studyContextService->shortCategoryName($category),
            ] : null,
            'coverage' => $coverage,
            'progress' => $progress,
        ]);
    }

    public function store(
        Request $request,
        StudyContextService $studyContextService,
        StudySessionManager $studySessionManager,
        PjmQuestionProgressService $pjmQuestionProgressService,
    ): RedirectResponse {
        $request->validate([
            'question_topic_id' => ['nullable', 'integer', 'min:1'],
            'question_count' => ['nullable', 'integer', 'min:1', 'max:40'],
            'question_count_strategy' => ['nullable', 'string', 'in:fixed,topic_remaining'],
            'question_status' => ['nullable', 'string', 'in:all,unanswered,memorized,incorrect,correct'],
            'randomize_order' => ['nullable', 'boolean'],
        ]);

        $category = LicenseCategory::query()
            ->where('is_active', true)
            ->find($studyContextService->preferredCategoryId($request->user()));

        if (! $category) {
            return to_route('session.index')
                ->with('status', 'Wybierz kategorie nauki, zeby uruchomic modul PJM.');
        }

        $questionStatus = (string) ($request->string('question_status')->value() ?: 'unanswered');
        $questionCountStrategy = (string) ($request->string('question_count_strategy')->value() ?: StudySessionManager::QUESTION_COUNT_FIXED);
        $questionTopicId = $request->integer('question_topic_id') ?: null;

        if ($questionTopicId === null && $questionStatus === 'unanswered') {
            $progress = $pjmQuestionProgressService->progressForCategory($category, $request->user());
            $questionTopicId = $progress['current_topic_id'] ?? null;
        }

        if ($questionCountStrategy === StudySessionManager::QUESTION_COUNT_TOPIC_REMAINING && $questionTopicId === null) {
            throw ValidationException::withMessages([
                'question_topic_id' => 'Wybierz dzial PJM, zeby uruchomic caly pozostaly dzial.',
            ]);
        }

        $studySessionManager->start(
            $request->user(),
            $category,
            StudySessionManager::MODE_PJM,
            $request->integer('question_count') ?: 12,
            [
                'question_topic_id' => $questionTopicId,
                'question_status' => $questionStatus,
                'question_scope' => 'all',
                'question_count_strategy' => $questionCountStrategy,
                'randomize_order' => $request->boolean('randomize_order'),
                'ui_shell' => StudySessionManager::UI_SHELL_ZEN,
            ],
        );

        return to_route('study-sessions.current');
    }
}
