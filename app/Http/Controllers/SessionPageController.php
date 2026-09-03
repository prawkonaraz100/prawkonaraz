<?php

namespace App\Http\Controllers;

use App\Support\LearningHomePayloadBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionPageController extends Controller
{
    public function __invoke(
        Request $request,
        LearningHomePayloadBuilder $learningHomePayloadBuilder,
    ): Response|RedirectResponse {
        $validated = $request->validate(LearningHomePayloadBuilder::validationRules());
        $accessDecisions = $learningHomePayloadBuilder->accessDecisions($request->user());

        if (! $accessDecisions['product']->allowed && ! $accessDecisions['pjm']->allowed) {
            return $this->redirectForDeniedAccess(
                $accessDecisions['pjm']->reason ?? $accessDecisions['product']->reason,
            );
        }

        $payload = $learningHomePayloadBuilder->buildForSessionPage(
            $request->user(),
            $validated,
            $accessDecisions['product'],
            $accessDecisions['pjm'],
        );
        $requestedCourseSlug = trim((string) $request->query('kurs', ''));
        $requestedCourse = collect($payload['professional_courses'] ?? [])
            ->first(fn (array $course): bool => ($course['slug'] ?? null) === $requestedCourseSlug);

        $payload['initial_professional_course_code'] = is_array($requestedCourse)
            ? ($requestedCourse['code'] ?? null)
            : null;

        return Inertia::render('Session/Index', $payload);
    }

    protected function redirectForDeniedAccess(?string $reason): RedirectResponse
    {
        if ($reason === 'temporary_account_unclaimed') {
            return to_route('account.claim.edit');
        }

        if ($reason === 'password_change_required') {
            return to_route('password.force.edit');
        }

        if ($reason === 'email_unverified') {
            return to_route('verification.notice');
        }

        $status = 'Aktywuj pełną naukę, aby korzystać z klasycznych trybów.';

        return to_route('access.activate')
            ->with('status', $status);
    }
}
