<?php

namespace App\Http\Controllers;

use App\Filament\Pages\ProfessionalCourses;
use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use App\Support\AuditLogService;
use App\Support\StudySessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminQuestionCollectionPreviewController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator($request);

        return redirect(ProfessionalCourses::getUrl(panel: 'admin'));
    }

    public function start(
        Request $request,
        QuestionCollection $questionCollection,
        QuestionModule $module,
        StudySessionManager $studySessionManager,
    ): RedirectResponse {
        $this->authorizeAdministrator($request);
        abort_unless($module->question_collection_id === $questionCollection->getKey(), 404);

        $studySessionManager->startQuestionModule(
            $request->user(),
            $questionCollection,
            $module,
        );

        return to_route('study-sessions.current');
    }

    public function updateAvailability(
        Request $request,
        QuestionCollection $questionCollection,
        AuditLogService $auditLogService,
    ): RedirectResponse {
        $this->authorizeAdministrator($request);

        $data = $request->validate([
            'is_available_to_learners' => ['required', 'boolean'],
        ]);
        $wasAvailable = (bool) $questionCollection->is_available_to_learners;
        $isAvailable = (bool) $data['is_available_to_learners'];

        if ($wasAvailable !== $isAvailable) {
            $questionCollection->forceFill([
                'is_available_to_learners' => $isAvailable,
            ])->save();

            $auditLogService->record(
                'admin.question_collection.availability_updated',
                'question_collection',
                $questionCollection->getKey(),
                $request->user(),
                [
                    'source' => 'admin_panel',
                    'collection_code' => $questionCollection->code,
                    'collection_name' => $questionCollection->name,
                    'was_available_to_learners' => $wasAvailable,
                    'is_available_to_learners' => $isAvailable,
                ],
                $request,
            );
        }

        return redirect(ProfessionalCourses::getUrl(panel: 'admin'))
            ->with('status', $isAvailable
                ? 'Kurs jest dostępny dla uprawnionych kursantów.'
                : 'Kurs został ukryty przed kursantami.');
    }

    protected function authorizeAdministrator(Request $request): void
    {
        abort_unless($request->user()?->isAdministrator(), 403);
    }
}
