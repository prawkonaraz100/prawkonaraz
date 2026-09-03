<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Support\ProductAccessResolver;
use App\Support\PublicDemoQuestionPayloadBuilder;
use App\Support\PublicDemoQuestionSetService;
use App\Support\PublicDemoSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PublicDemoStudyController extends Controller
{
    public function landing(
        Request $request,
        PublicDemoSessionService $demoSessionService,
        PublicDemoQuestionPayloadBuilder $questionPayloadBuilder,
        PublicDemoQuestionSetService $questionSetService,
        ProductAccessResolver $productAccessResolver,
    ): Response {
        $questions = $questionSetService->questions();
        $state = $demoSessionService->previewState();

        return $this->renderPlayer(
            request: $request,
            state: $state,
            activeQuestion: $questions->first(),
            questions: $questions,
            demoSessionService: $demoSessionService,
            questionPayloadBuilder: $questionPayloadBuilder,
            productAccessResolver: $productAccessResolver,
            lockedPreview: true,
        );
    }

    public function show(
        Request $request,
        PublicDemoSessionService $demoSessionService,
        PublicDemoQuestionPayloadBuilder $questionPayloadBuilder,
        PublicDemoQuestionSetService $questionSetService,
        ProductAccessResolver $productAccessResolver,
    ): Response {
        $state = $request->boolean('fresh')
            ? $demoSessionService->restart($request)
            : $demoSessionService->state($request);

        return $this->renderPlayer(
            request: $request,
            state: $state,
            activeQuestion: $demoSessionService->currentQuestion($request),
            questions: $questionSetService->questions(),
            demoSessionService: $demoSessionService,
            questionPayloadBuilder: $questionPayloadBuilder,
            productAccessResolver: $productAccessResolver,
        );
    }

    public function answer(Request $request, PublicDemoSessionService $demoSessionService): JsonResponse
    {
        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_answer' => ['required', 'string', Rule::in(['a', 'b', 'c', 'A', 'B', 'C'])],
            'answer_kind' => ['nullable', 'string', Rule::in(['choice'])],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);

        $result = $demoSessionService->recordAnswer(
            $request,
            (int) $validated['question_id'],
            (string) $validated['selected_answer'],
            isset($validated['response_time_ms']) ? (int) $validated['response_time_ms'] : null,
        );
        $summary = $demoSessionService->summary($result['state']);

        return response()->json([
            'answer' => $result['answer'],
            'session' => $this->sessionPayload($summary),
            'progress' => [
                'answered' => $summary['answered'],
                'remaining' => $summary['remaining'],
                'total' => $summary['total'],
            ],
            'completed' => (bool) data_get($result, 'state.completed'),
            'nextQuestion' => $result['next_question'],
            'nextQuestionNumber' => $summary['completed'] ? null : $summary['position'],
        ]);
    }

    public function complete(Request $request, PublicDemoSessionService $demoSessionService): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['sometimes', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_answer' => ['required', 'string', Rule::in(['a', 'b', 'c', 'A', 'B', 'C'])],
            'answers.*.answer_kind' => ['nullable', 'string', Rule::in(['choice'])],
            'answers.*.response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);

        $state = $demoSessionService->complete($request, $validated['answers'] ?? []);
        $summary = $demoSessionService->summary($state);

        return response()->json([
            'session' => $this->sessionPayload($summary),
            'redirect' => route('public.tests.demo.show', absolute: false),
            'reviewCompletion' => null,
        ]);
    }

    public function restart(Request $request, PublicDemoSessionService $demoSessionService): RedirectResponse
    {
        $demoSessionService->restart($request);

        return to_route('public.tests.demo.show', ['fresh' => 1]);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    protected function sessionPayload(array $summary): array
    {
        return [
            'id' => 0,
            'mode' => 'learn',
            'ui_shell' => 'exam_like',
            'status' => $summary['completed'] ? 'completed' : 'in_progress',
            'license_category_id' => null,
            'license_category_code' => $summary['category_code'],
            'license_category_name' => 'Kategoria '.$summary['category_code'],
            'started_at' => $summary['started_at'],
            'completed_at' => $summary['completed_at'],
            'correct_answers_count' => $summary['correct'],
            'total_questions_count' => $summary['total'],
            'score_percent' => $summary['score_percent'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function examUiPayload(): array
    {
        return [
            'duration_seconds' => 1500,
            'remaining_seconds' => 1500,
            'started_at' => null,
            'deadline_at' => null,
            'pass_threshold' => 68,
            'max_points' => 74,
            'current_scope' => null,
            'current_points' => null,
            'basic' => [
                'total' => 0,
                'answered' => 0,
                'correct' => 0,
                'points' => 0,
            ],
            'specialist' => [
                'total' => 0,
                'answered' => 0,
                'correct' => 0,
                'points' => 0,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function completionTimingPayload(): array
    {
        return [
            'duration_seconds' => null,
            'average_correct_response_time_ms' => null,
            'comparison' => [
                'previous_duration_seconds' => null,
                'previous_average_correct_response_time_ms' => null,
                'same_question_count' => false,
                'score_not_worse' => false,
                'saved_duration_seconds' => null,
                'saved_average_correct_response_time_ms' => null,
                'show_reward' => false,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  Collection<int, Question>  $questions
     */
    protected function renderPlayer(
        Request $request,
        array $state,
        ?Question $activeQuestion,
        Collection $questions,
        PublicDemoSessionService $demoSessionService,
        PublicDemoQuestionPayloadBuilder $questionPayloadBuilder,
        ProductAccessResolver $productAccessResolver,
        bool $lockedPreview = false,
    ): Response {
        $summary = $demoSessionService->summary($state);
        $user = $request->user();
        $hasFullAccess = $user ? $productAccessResolver->forUser($user)->allowed : false;
        $publicExplanationUrls = $questionPayloadBuilder->publicExplanationUrlsFor($questions);
        $questionPool = $questions
            ->map(fn ($demoQuestion): array => $questionPayloadBuilder->forQuestion(
                $demoQuestion,
                includeReveal: true,
                publicExplanationUrl: $publicExplanationUrls->get((int) $demoQuestion->getKey()),
            ))
            ->values();

        return Inertia::render('StudySessions/Show', [
            'session' => $this->sessionPayload($summary),
            'questionIds' => $questionPool->pluck('id')->values(),
            'progress' => [
                'answered' => $summary['answered'],
                'remaining' => $summary['remaining'],
                'total' => $summary['total'],
            ],
            'examUi' => $this->examUiPayload(),
            'currentQuestionNumber' => $summary['completed'] ? null : $summary['position'],
            'currentQuestion' => $activeQuestion && ! $summary['completed']
                ? $questionPayloadBuilder->forQuestion(
                    $activeQuestion,
                    includeReveal: true,
                    publicExplanationUrl: $publicExplanationUrls->get((int) $activeQuestion->getKey()),
                )
                : null,
            'questionPool' => $questionPool,
            'questionPoolMode' => 'full',
            'questionBatchSize' => 12,
            'prefetchedQuestions' => [],
            'sessionFilters' => [
                'question_topic_id' => null,
                'question_scope' => 'all',
                'question_status' => 'all',
                'randomize_order' => false,
                'question_count' => max((int) $summary['total'], 1),
            ],
            'topicGroups' => [],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'Wszystkie pytania demo'],
            ],
            'results' => $lockedPreview ? [] : $demoSessionService->results($state),
            'completionTiming' => $this->completionTimingPayload(),
            'pjmCompletion' => null,
            'reviewCompletion' => null,
            'publicDemo' => [
                'enabled' => true,
                'mode' => 'frozen_packet',
                'routes' => $this->demoRoutes(),
                'viewer' => [
                    'authenticated' => $user !== null,
                    'has_full_access' => $hasFullAccess,
                ],
                'gate' => [
                    'enabled' => $lockedPreview,
                    'primary_label' => 'Sprawdź '.$summary['planned_total'].' pytań próbnych',
                    'secondary_label' => 'Zobacz pełny dostęp',
                ],
            ],
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    protected function demoRoutes(): array
    {
        return [
            'demo' => route('public.tests.demo.show', ['fresh' => 1], absolute: false),
            'current' => route('public.tests.demo.show', absolute: false),
            'answer' => route('public.tests.demo.answers.store', absolute: false),
            'complete' => route('public.tests.demo.complete', absolute: false),
            'restart' => route('public.tests.demo.restart', absolute: false),
            'landing' => route('public.tests', absolute: false),
            'pricing' => route('public.pricing', absolute: false),
            'register' => route('register', absolute: false),
            'learning' => route('session.index', absolute: false),
            'activate' => route('access.activate', absolute: false),
            'questions' => null,
        ];
    }
}
