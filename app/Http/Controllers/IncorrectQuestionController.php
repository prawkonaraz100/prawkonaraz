<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\UserIncorrectQuestion;
use App\Models\UserQuestionProgress;
use App\Support\IncorrectQuestionListService;
use App\Support\QuestionMediaPayloadBuilder;
use App\Support\StudyContextService;
use App\Support\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncorrectQuestionController extends Controller
{
    public function index(
        Request $request,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        StudyContextService $studyContextService,
        UserProfileService $userProfileService,
    ): Response {
        abort_unless(config('study.incorrect_question_list.ui_enabled', false), 404);

        $payload = $this->payload(
            $request,
            $questionMediaPayloadBuilder,
            $studyContextService,
            $userProfileService,
        );

        return Inertia::render('IncorrectQuestions/Index', $payload);
    }

    public function apiIndex(
        Request $request,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        StudyContextService $studyContextService,
        UserProfileService $userProfileService,
    ): JsonResponse {
        abort_unless(config('study.incorrect_question_list.ui_enabled', false), 404);

        return response()->json([
            'data' => $this->payload(
                $request,
                $questionMediaPayloadBuilder,
                $studyContextService,
                $userProfileService,
            ),
        ]);
    }

    public function destroy(
        Request $request,
        UserIncorrectQuestion $userIncorrectQuestion,
        IncorrectQuestionListService $incorrectQuestionListService,
    ): RedirectResponse {
        abort_unless(config('study.incorrect_question_list.ui_enabled', false), 404);
        abort_unless((int) $userIncorrectQuestion->user_id === (int) $request->user()->getKey(), 403);

        $incorrectQuestionListService->removeManually($request->user(), $userIncorrectQuestion);

        return back()->with('status', 'Pytanie zostało usunięte z listy.');
    }

    public function apiDestroy(
        Request $request,
        UserIncorrectQuestion $userIncorrectQuestion,
        IncorrectQuestionListService $incorrectQuestionListService,
    ): JsonResponse {
        abort_unless(config('study.incorrect_question_list.ui_enabled', false), 404);
        abort_unless((int) $userIncorrectQuestion->user_id === (int) $request->user()->getKey(), 403);

        $incorrectQuestionListService->removeManually($request->user(), $userIncorrectQuestion);

        return response()->json(['data' => ['removed' => true]]);
    }

    public function updatePreference(
        Request $request,
        UserProfileService $userProfileService,
    ): RedirectResponse {
        abort_unless(config('study.incorrect_question_list.ui_enabled', false), 404);

        $validated = $request->validate([
            'auto_remove_incorrect_questions_on_correct' => ['required', 'boolean'],
        ]);

        $userProfileService->update($request->user(), $validated);

        return back()->with('status', 'Ustawienie listy zostało zapisane.');
    }

    public function apiUpdatePreference(
        Request $request,
        UserProfileService $userProfileService,
    ): JsonResponse {
        abort_unless(config('study.incorrect_question_list.ui_enabled', false), 404);

        $validated = $request->validate([
            'auto_remove_incorrect_questions_on_correct' => ['required', 'boolean'],
        ]);

        $profile = $userProfileService->update($request->user(), $validated);

        return response()->json([
            'data' => [
                'auto_remove_incorrect_questions_on_correct' => (bool) $profile->auto_remove_incorrect_questions_on_correct,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(
        Request $request,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        StudyContextService $studyContextService,
        UserProfileService $userProfileService,
    ): array {
        $validated = $request->validate([
            'topic' => ['nullable', 'integer', 'exists:question_topics,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $user = $request->user();
        $categoryId = $studyContextService->preferredCategoryId($user);
        $category = $categoryId
            ? $studyContextService->visibleCategoriesQuery()->find($categoryId)
            : null;
        $topicId = isset($validated['topic']) ? (int) $validated['topic'] : null;

        $query = UserIncorrectQuestion::query()
            ->select('user_incorrect_questions.*')
            ->addSelect([
                'incorrect_count' => UserQuestionProgress::query()
                    ->select('incorrect_count')
                    ->whereColumn('user_question_progress.question_id', 'user_incorrect_questions.question_id')
                    ->where('user_question_progress.user_id', $user->getKey())
                    ->limit(1),
            ])
            ->with(['question.questionTopic', 'question.media'])
            ->where('user_id', $user->getKey())
            ->active()
            ->whereHas('question', function ($questionQuery) use ($categoryId, $topicId): void {
                $questionQuery
                    ->where('is_active', true)
                    ->readyForDelivery()
                    ->when($categoryId, fn ($categoryQuery) => $categoryQuery->where('license_category_id', $categoryId))
                    ->when($topicId, fn ($topicQuery) => $topicQuery->where('question_topic_id', $topicId));
            })
            ->orderByDesc('last_incorrect_at')
            ->orderByDesc('id');

        $questions = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (UserIncorrectQuestion $entry) use ($questionMediaPayloadBuilder): array {
                /** @var Question $question */
                $question = $entry->question;

                return [
                    'id' => $entry->getKey(),
                    'question_id' => $question->getKey(),
                    'external_id' => $question->external_id,
                    'prompt' => $question->prompt,
                    'topic' => $question->questionTopic ? [
                        'id' => $question->questionTopic->getKey(),
                        'name' => $question->questionTopic->name,
                    ] : null,
                    'media' => $questionMediaPayloadBuilder->forCatalog($question->media),
                    'incorrect_count' => (int) ($entry->getAttribute('incorrect_count') ?? 0),
                    'first_incorrect_at' => $entry->first_incorrect_at?->toIso8601String(),
                    'last_incorrect_at' => $entry->last_incorrect_at?->toIso8601String(),
                    'last_incorrect_at_label' => $entry->last_incorrect_at?->format('d.m.Y H:i'),
                ];
            });

        $topics = $category
            ? QuestionTopic::query()
                ->where('is_active', true)
                ->whereHas('questions', fn ($questionQuery) => $questionQuery
                    ->where('license_category_id', $category->getKey())
                    ->where('is_active', true)
                    ->readyForDelivery()
                    ->whereHas('incorrectQuestionEntries', fn ($entryQuery) => $entryQuery
                        ->where('user_id', $user->getKey())
                        ->whereNull('removed_at')))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (QuestionTopic $topic): array => [
                    'id' => $topic->getKey(),
                    'name' => $topic->name,
                ])
                ->values()
            : collect();
        $profile = $userProfileService->profileFor($user);

        return [
            'category' => $category instanceof LicenseCategory ? [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'short_name' => $studyContextService->shortCategoryName($category),
            ] : null,
            'filters' => ['topic' => $topicId],
            'topics' => $topics,
            'questions' => $questions,
            'stats' => ['active_count' => $questions->total()],
            'preferences' => [
                'auto_remove_on_correct' => (bool) $profile->auto_remove_incorrect_questions_on_correct,
            ],
            'actions' => [
                'study_home_url' => route('session.index', absolute: false),
                'start_session_url' => route('study-sessions.store', absolute: false),
                'preference_url' => route('incorrect-questions.preference.update', absolute: false),
            ],
        ];
    }
}
