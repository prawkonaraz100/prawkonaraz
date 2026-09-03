<?php

namespace App\Http\Controllers;

use App\Models\RankedPlayerRating;
use App\Support\RankedMatchService;
use App\Support\RankedWebSocketTicketService;
use App\Support\StudyContextService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RankedSessionPageController extends Controller
{
    public function lobby(Request $request, StudyContextService $studyContextService): Response
    {
        return $this->renderPage($request, $studyContextService, 'lobby');
    }

    public function waiting(Request $request, StudyContextService $studyContextService): Response
    {
        return $this->renderPage($request, $studyContextService, 'waiting');
    }

    public function match(Request $request, StudyContextService $studyContextService): Response
    {
        return $this->renderPage($request, $studyContextService, 'match');
    }

    public function result(Request $request, StudyContextService $studyContextService): Response
    {
        return $this->renderPage($request, $studyContextService, 'result');
    }

    private function renderPage(
        Request $request,
        StudyContextService $studyContextService,
        string $screen,
    ): Response {
        $request->validate([
            'stay' => ['nullable', Rule::in(['lobby'])],
        ]);

        $ticketService = app(RankedWebSocketTicketService::class);
        $websocketConfigured = (bool) config('ranked.websocket_enabled')
            && is_string(config('ranked.websocket_url'))
            && config('ranked.websocket_url') !== '';
        $websocketUrl = $websocketConfigured
            ? $ticketService->issueWebSocketUrlForUser($request->user())
            : null;
        $websocketEnabled = $websocketConfigured
            && is_string($websocketUrl)
            && $websocketUrl !== '';
        $sseEnabled = (bool) config('ranked.sse_enabled');
        $preferredTransport = $websocketEnabled
            ? 'websocket'
            : ($sseEnabled ? 'sse' : 'polling');

        $categories = $studyContextService->activeCategories($request->user());
        $rankedCategory = $request->user()?->canUseAllStudyCategories()
            ? $categories->firstWhere('code', 'B')
            : null;
        $selectedCategory = $rankedCategory ?? $categories->first();
        $leaderboard = RankedPlayerRating::query()
            ->with(['user:id,name'])
            ->whereHas('user')
            ->when(
                $request->user(),
                fn ($query, $user) => $query->where('user_id', '!=', $user->getKey()),
            )
            ->orderByDesc('rating')
            ->orderByDesc('peak_rating')
            ->orderByDesc('wins')
            ->orderByDesc('matches_played')
            ->limit(10)
            ->get()
            ->values()
            ->map(fn (RankedPlayerRating $rating, int $index) => [
                'position' => $index + 1,
                'user_id' => $rating->user_id,
                'username' => $rating->user?->name ?? 'Gracz',
                'rating' => $rating->rating,
                'peak_rating' => $rating->peak_rating,
                'matches_played' => $rating->matches_played,
                'wins' => $rating->wins,
                'losses' => $rating->losses,
                'draws' => $rating->draws,
                'current_streak' => $rating->current_streak,
                'best_streak' => $rating->best_streak,
                'win_rate' => $rating->matches_played > 0
                    ? (int) round(($rating->wins / $rating->matches_played) * 100)
                    : null,
            ])
            ->all();

        return Inertia::render('Session/Ranking', [
            'selectedCategory' => $selectedCategory ? [
                'id' => $selectedCategory->getKey(),
                'code' => $selectedCategory->code,
                'name' => $selectedCategory->name,
                'short_name' => $studyContextService->shortCategoryName($selectedCategory),
            ] : null,
            'screen' => $screen,
            'navigationLock' => $request->string('stay')->value() === 'lobby'
                ? 'lobby'
                : null,
            'leaderboard' => $leaderboard,
            'specSummary' => [
                'api_version' => '1.0',
                'match_channel_pattern' => 'match:{match_id}',
                'queue_channel_pattern' => 'queue:{user_id}',
                'heartbeat_seconds' => 10,
                'disconnect_timeout_seconds' => 30,
                'reconnect_grace_seconds' => 25,
                'matchmaking_timeout_seconds' => RankedMatchService::MATCHMAKING_TIMEOUT_SECONDS,
                'match_duration_seconds' => 90,
                'total_questions' => 40,
                'max_concurrent_players' => RankedMatchService::MAX_CONCURRENT_PLAYERS,
                'implementation_status' => 'transport_negotiation_with_websocket_ready_client',
            ],
            'realtimeTransport' => [
                'preferred' => $preferredTransport,
                'websocket' => [
                    'enabled' => $websocketEnabled,
                    'url' => $websocketEnabled ? $websocketUrl : null,
                    'fallback' => $sseEnabled ? 'sse' : 'polling',
                ],
                'sse' => [
                    'enabled' => $sseEnabled,
                    'url' => $sseEnabled ? route('api.v1.ranked.stream') : null,
                    'retry_ms' => (int) config('ranked.sse_retry_ms', 2000),
                ],
                'polling' => [
                    'enabled' => true,
                    'overview_interval_ms' => 5000,
                    'events_interval_ms' => 2000,
                ],
            ],
        ]);
    }
}
