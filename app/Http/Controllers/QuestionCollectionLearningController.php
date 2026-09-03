<?php

namespace App\Http\Controllers;

use App\Models\QuestionCollection;
use App\Models\QuestionCollectionIncorrectQuestion;
use App\Models\QuestionModule;
use App\Models\StudySession;
use App\Support\QuestionCollectionAccessService;
use App\Support\QuestionCollectionIncorrectQuestionService;
use App\Support\QuestionMediaPayloadBuilder;
use App\Support\StudySessionManager;
use App\Support\UserProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionCollectionLearningController extends Controller
{
    public function show(
        Request $request,
        QuestionCollection $questionCollection,
        QuestionCollectionAccessService $questionCollectionAccessService,
    ): RedirectResponse {
        $this->ensureLearnerCanUseCollection(
            $request,
            $questionCollection,
            $questionCollectionAccessService,
        );

        return to_route('session.index', ['kurs' => $questionCollection->slug]);
    }

    public function start(
        Request $request,
        QuestionCollection $questionCollection,
        QuestionModule $module,
        QuestionCollectionAccessService $questionCollectionAccessService,
        StudySessionManager $studySessionManager,
    ): RedirectResponse {
        $decision = $questionCollectionAccessService->forModule(
            $request->user(),
            $questionCollection,
            $module,
        );

        abort_unless($decision->allowed, 404);

        $data = $request->validate([
            'replace_active_session' => ['nullable', 'boolean'],
        ]);

        $studySessionManager->startQuestionModule(
            $request->user(),
            $questionCollection,
            $module,
            replaceActiveSession: (bool) ($data['replace_active_session'] ?? false),
            returnUrl: route('learning.question-collections.show', $questionCollection, absolute: false),
        );

        return to_route('study-sessions.current');
    }

    public function incorrectQuestions(
        Request $request,
        QuestionCollection $questionCollection,
        QuestionCollectionAccessService $questionCollectionAccessService,
        QuestionCollectionIncorrectQuestionService $questionCollectionIncorrectQuestionService,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        StudySessionManager $studySessionManager,
        UserProfileService $userProfileService,
    ): Response {
        $this->ensureLearnerCanUseCollection(
            $request,
            $questionCollection,
            $questionCollectionAccessService,
        );

        $questions = $questionCollectionIncorrectQuestionService
            ->activeEntriesQuery($request->user(), $questionCollection)
            ->with([
                'question.media',
                'question.modules' => fn ($query) => $query
                    ->where('question_collection_id', $questionCollection->getKey())
                    ->where('is_active', true),
            ])
            ->orderByDesc('last_incorrect_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(function (QuestionCollectionIncorrectQuestion $entry) use ($questionCollection, $questionMediaPayloadBuilder): array {
                $question = $entry->question;

                return [
                    'id' => $entry->getKey(),
                    'prompt' => $question->prompt,
                    'media' => $questionMediaPayloadBuilder->forCatalog($question->media),
                    'modules' => $question->modules
                        ->map(fn (QuestionModule $module): array => [
                            'id' => $module->getKey(),
                            'code' => $module->code,
                            'name' => $module->name,
                        ])
                        ->values(),
                    'incorrect_count' => (int) $entry->incorrect_count,
                    'last_incorrect_at_label' => $entry->last_incorrect_at?->format('d.m.Y H:i'),
                    'remove_url' => route('learning.question-collections.incorrect-questions.destroy', [
                        'questionCollection' => $questionCollection,
                        'courseIncorrect' => $entry,
                    ], absolute: false),
                ];
            });
        $profile = $userProfileService->profileFor($request->user());

        return Inertia::render('QuestionCollections/IncorrectQuestions', [
            'collection' => [
                'code' => $questionCollection->code,
                'name' => $questionCollection->name,
                'url' => route('learning.question-collections.show', $questionCollection, absolute: false),
            ],
            'questions' => $questions,
            'stats' => ['active_count' => $questions->total()],
            'preferences' => [
                'auto_remove_on_correct' => (bool) $profile->auto_remove_incorrect_questions_on_correct,
            ],
            'actions' => [
                'start_url' => route('learning.question-collections.incorrect-questions.start', $questionCollection, absolute: false),
                'preference_url' => route('learning.question-collections.incorrect-questions.preference.update', $questionCollection, absolute: false),
            ],
            'active_session' => $this->activeSessionPayload(
                $studySessionManager->inProgressSessionForUser($request->user()),
            ),
        ]);
    }

    public function startIncorrectQuestions(
        Request $request,
        QuestionCollection $questionCollection,
        QuestionCollectionAccessService $questionCollectionAccessService,
        StudySessionManager $studySessionManager,
    ): RedirectResponse {
        $this->ensureLearnerCanUseCollection(
            $request,
            $questionCollection,
            $questionCollectionAccessService,
        );

        $data = $request->validate([
            'replace_active_session' => ['nullable', 'boolean'],
        ]);

        $studySessionManager->startQuestionCollectionReview(
            $request->user(),
            $questionCollection,
            replaceActiveSession: (bool) ($data['replace_active_session'] ?? false),
            returnUrl: route('learning.question-collections.incorrect-questions.index', $questionCollection, absolute: false),
        );

        return to_route('study-sessions.current');
    }

    public function destroyIncorrectQuestion(
        Request $request,
        QuestionCollection $questionCollection,
        QuestionCollectionIncorrectQuestion $courseIncorrect,
        QuestionCollectionAccessService $questionCollectionAccessService,
        QuestionCollectionIncorrectQuestionService $questionCollectionIncorrectQuestionService,
    ): RedirectResponse {
        $this->ensureLearnerCanUseCollection(
            $request,
            $questionCollection,
            $questionCollectionAccessService,
        );
        abort_unless(
            (int) $courseIncorrect->question_collection_id === (int) $questionCollection->getKey()
                && (int) $courseIncorrect->user_id === (int) $request->user()->getKey(),
            404,
        );

        $questionCollectionIncorrectQuestionService->removeManually(
            $request->user(),
            $courseIncorrect,
        );

        return back()->with('status', 'Pytanie zostało usunięte z listy kursu.');
    }

    public function updateIncorrectQuestionPreference(
        Request $request,
        QuestionCollection $questionCollection,
        QuestionCollectionAccessService $questionCollectionAccessService,
        UserProfileService $userProfileService,
    ): RedirectResponse {
        $this->ensureLearnerCanUseCollection(
            $request,
            $questionCollection,
            $questionCollectionAccessService,
        );

        $userProfileService->update($request->user(), $request->validate([
            'auto_remove_incorrect_questions_on_correct' => ['required', 'boolean'],
        ]));

        return back()->with('status', 'Ustawienie listy kursu zostało zapisane.');
    }

    protected function ensureLearnerCanUseCollection(
        Request $request,
        QuestionCollection $questionCollection,
        QuestionCollectionAccessService $questionCollectionAccessService,
    ): void {
        abort_unless(
            $questionCollectionAccessService->forUser($request->user(), $questionCollection)->allowed,
            404,
        );
    }

    /**
     * @return array<string, int|string>|null
     */
    protected function activeSessionPayload(?StudySession $studySession): ?array
    {
        if (! $studySession) {
            return null;
        }

        $moduleName = data_get($studySession->payload, 'context.module_name');
        $isCollectionReview = data_get($studySession->payload, 'context.type') === 'question_collection_review';

        return [
            'id' => $studySession->getKey(),
            'title' => $isCollectionReview
                ? 'Pytania do poprawy'
                : (is_string($moduleName) && $moduleName !== ''
                ? $moduleName
                : 'Bieżąca sesja nauki'),
        ];
    }
}
