<?php

namespace App\Http\Middleware;

use App\Models\StudySession;
use App\Models\User;
use App\Support\ApiErrorResponseFactory;
use App\Support\PjmFreeAccessResolver;
use App\Support\ProductAccessResolver;
use App\Support\QuestionCollectionAccessDecision;
use App\Support\QuestionCollectionAccessService;
use App\Support\StudySessionManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudySessionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $productDecision = app(ProductAccessResolver::class)->forUser($user);
        $studySession = $this->studySession($request, $user);

        if ($productDecision->allowed) {
            $collectionDecision = app(QuestionCollectionAccessService::class)->forStudySession($user, $studySession);

            if ($collectionDecision instanceof QuestionCollectionAccessDecision && ! $collectionDecision->allowed) {
                return $this->collectionDeniedResponse($request, $collectionDecision);
            }

            if ($collectionDecision instanceof QuestionCollectionAccessDecision && $request->is('api/*')) {
                return app(ApiErrorResponseFactory::class)->make(
                    $request,
                    'COURSE_WEB_ONLY',
                    'Ten kurs jest obecnie dostępny wyłącznie w wersji desktopowej serwisu.',
                    403,
                );
            }

            $retiredResponse = $this->retireOutOfScopeReviewSession($request, $user, $studySession);

            if ($retiredResponse instanceof Response) {
                return $retiredResponse;
            }

            return $next($request);
        }

        $pjmDecision = app(PjmFreeAccessResolver::class)->forUser($user);

        if ($pjmDecision->allowed && $studySession?->mode === StudySessionManager::MODE_PJM) {
            return $next($request);
        }

        if ($studySession?->mode === StudySessionManager::MODE_SR_REVIEW && $studySession->status === 'in_progress') {
            app(StudySessionManager::class)->retireReviewSession($studySession, 'access_lost');
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            abort(403, 'Aktywny dostęp do produktu jest wymagany.');
        }

        if ($productDecision->reason === 'temporary_account_unclaimed' || $pjmDecision->reason === 'temporary_account_unclaimed') {
            return redirect()->route('account.claim.edit');
        }

        if ($productDecision->reason === 'password_change_required' || $pjmDecision->reason === 'password_change_required') {
            return redirect()->route('password.force.edit');
        }

        return redirect()
            ->route('access.activate')
            ->with('status', 'Aktywuj pełny dostęp, żeby kontynuować tę sesję.');
    }

    private function collectionDeniedResponse(
        Request $request,
        QuestionCollectionAccessDecision $decision,
    ): Response {
        $message = match ($decision->reason) {
            'collection_inactive' => 'Ten kurs jest obecnie wyłączony.',
            'collection_missing' => 'Ten kurs nie jest już dostępny.',
            default => 'Ten kurs jest chwilowo niedostępny.',
        };

        if ($request->is('api/*') || $request->expectsJson()) {
            return app(ApiErrorResponseFactory::class)->make(
                $request,
                'COURSE_NOT_AVAILABLE',
                $message,
                403,
                ['reason' => $decision->reason],
            );
        }

        return redirect()
            ->route('session.index')
            ->with('status', $message);
    }

    private function retireOutOfScopeReviewSession(Request $request, User $user, ?StudySession $studySession): ?Response
    {
        if (! $studySession instanceof StudySession) {
            return null;
        }

        $retired = app(StudySessionManager::class)->retireReviewSessionIfOutsideActiveScope($studySession, $user);

        if (! $retired) {
            return null;
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => 'Ten trening pamięci jest poza aktualną kategorią. Uruchom nowy plan.',
            ], 409);
        }

        return redirect()
            ->route('review-queue.index')
            ->with('status', 'Poprzedni trening pamięci zamknęliśmy po zmianie kategorii. Uruchom nowy plan.');
    }

    private function studySession(Request $request, User $user): ?StudySession
    {
        $routeSession = $request->route('studySession');

        if ($routeSession instanceof StudySession) {
            return $routeSession->user_id === $user->getKey() ? $routeSession : null;
        }

        return StudySession::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->latest('id')
            ->first();
    }
}
