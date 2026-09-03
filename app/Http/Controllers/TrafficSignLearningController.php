<?php

namespace App\Http\Controllers;

use App\Models\TrafficSignLearningSession;
use App\Support\TrafficSignLearningCorpusService;
use App\Support\TrafficSignLearningPlannerService;
use App\Support\TrafficSignLearningSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrafficSignLearningController extends Controller
{
    public function index(
        Request $request,
        TrafficSignLearningCorpusService $corpusService,
        TrafficSignLearningPlannerService $plannerService,
    ): Response {
        $user = $request->user();
        $dashboard = $corpusService->dashboard($user);
        $plannedSigns = $plannerService->planFromSnapshot(
            $dashboard['signs'],
            $dashboard['progress_by_sign_id'],
        );

        return Inertia::render('TrafficSignLearning/Index', [
            'overview' => $dashboard['overview'],
            'categories' => $dashboard['categories'],
            'session_preview' => [
                'limit' => TrafficSignLearningPlannerService::SESSION_LIMIT,
                'signs' => $plannedSigns
                    ->map(fn ($sign): array => $corpusService->signPayload($sign))
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function store(Request $request, TrafficSignLearningSessionService $sessionService): RedirectResponse
    {
        $validated = $request->validate([
            'category_slug' => ['nullable', 'string', Rule::in(TrafficSignLearningCorpusService::MVP_CATEGORY_SLUGS)],
            'mode' => ['nullable', 'string', Rule::in([
                TrafficSignLearningSession::MODE_RECOGNITION,
                TrafficSignLearningSession::MODE_SIMILAR_SIGNS,
                TrafficSignLearningSession::MODE_DESCRIPTION_TO_SIGN,
            ])],
        ]);
        $categorySlug = $validated['category_slug'] ?? null;
        $mode = $validated['mode'] ?? TrafficSignLearningSession::MODE_RECOGNITION;

        $sessionService->start(
            $request->user(),
            is_string($categorySlug) && $categorySlug !== '' ? [$categorySlug] : null,
            $mode,
        );

        return to_route('traffic-sign-learning.current');
    }

    public function current(Request $request, TrafficSignLearningSessionService $sessionService): Response|RedirectResponse|JsonResponse
    {
        $session = $sessionService->activeSession($request->user());

        if (! $session instanceof TrafficSignLearningSession) {
            if ($request->expectsJson()) {
                return response()->json([
                    'redirect_url' => route('session.traffic-signs', absolute: false),
                ]);
            }

            return to_route('session.traffic-signs');
        }

        $answer = $sessionService->currentAnswer($session, $request->integer('feedback') ?: null);

        if ($answer === null) {
            $session = $sessionService->syncSessionScore($session);

            if ($request->expectsJson()) {
                return response()->json([
                    'redirect_url' => route('traffic-sign-learning.results.show', $session, absolute: false),
                ]);
            }

            return to_route('traffic-sign-learning.results.show', $session);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'session' => [
                    'id' => $session->getKey(),
                    'mode' => $session->mode,
                    'status' => $session->status,
                    'total_signs_count' => $session->total_signs_count,
                ],
                'question' => $sessionService->questionPayload($session, $answer),
                'questions' => $sessionService->sessionQuestionsPayload($session),
            ]);
        }

        return Inertia::render('TrafficSignLearning/Show', [
            'session' => [
                'id' => $session->getKey(),
                'mode' => $session->mode,
                'status' => $session->status,
                'total_signs_count' => $session->total_signs_count,
            ],
            'question' => $sessionService->questionPayload($session, $answer),
            'questions' => $sessionService->sessionQuestionsPayload($session),
        ]);
    }

    public function answer(Request $request, TrafficSignLearningSessionService $sessionService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'answer_id' => ['required', 'integer'],
            'selected_traffic_sign_id' => ['required', 'integer'],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);

        $answer = $sessionService->submitAnswer(
            $request->user(),
            (int) $validated['answer_id'],
            (int) $validated['selected_traffic_sign_id'],
            isset($validated['response_time_ms']) ? (int) $validated['response_time_ms'] : null,
        );
        $session = $answer->learningSession;

        if ($session->status === TrafficSignLearningSession::STATUS_COMPLETED) {
            if ($request->expectsJson()) {
                return response()->json([
                    'completed' => true,
                    'redirect_url' => route('traffic-sign-learning.results.show', $session, absolute: false),
                    'session' => [
                        'id' => $session->getKey(),
                        'mode' => $session->mode,
                        'status' => $session->status,
                        'total_signs_count' => $session->total_signs_count,
                    ],
                ]);
            }

            return to_route('traffic-sign-learning.results.show', $session);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'completed' => false,
                'session' => [
                    'id' => $session->getKey(),
                    'mode' => $session->mode,
                    'status' => $session->status,
                    'total_signs_count' => $session->total_signs_count,
                ],
                'feedback' => $sessionService->answerFeedbackPayload($session, $answer),
            ]);
        }

        return to_route('traffic-sign-learning.current', ['feedback' => $answer->getKey()]);
    }

    public function syncAnswers(Request $request, TrafficSignLearningSessionService $sessionService): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1', 'max:50'],
            'answers.*.answer_id' => ['required', 'integer'],
            'answers.*.selected_traffic_sign_id' => ['required', 'integer'],
            'answers.*.response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);

        return response()->json($sessionService->syncAnswers(
            $request->user(),
            $validated['answers'],
        ));
    }

    public function result(
        Request $request,
        TrafficSignLearningSession $trafficSignLearningSession,
        TrafficSignLearningSessionService $sessionService,
    ): Response|RedirectResponse {
        abort_unless($trafficSignLearningSession->user_id === $request->user()->getKey(), 404);

        $session = $trafficSignLearningSession->status === TrafficSignLearningSession::STATUS_COMPLETED
            ? $trafficSignLearningSession
            : $sessionService->syncSessionScore($trafficSignLearningSession);

        if ($session->status !== TrafficSignLearningSession::STATUS_COMPLETED) {
            return to_route('traffic-sign-learning.current');
        }

        return Inertia::render('TrafficSignLearning/Result', [
            'result' => $sessionService->resultPayload($session),
        ]);
    }
}
