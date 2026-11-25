<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
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

        $selectedCategory = $this->resolveRankedCategory($request, $studyContextService);
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
            ->map(fn (RankedPlayerRating $rating, int $index) => $this->leaderboardEntry($rating, $index + 1))
            ->all();
        $currentUserRanking = $this->currentUserRanking($request);

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
            'currentUserRanking' => $currentUserRanking,
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

    /**
     * Kategoria kolejki: z linku (?category=), jeśli gracz może jej używać; potem kategoria nauki
     * z profilu; na końcu B albo pierwsza dostępna. Kursy samodzielne (ADR, Kod 95) nie mają rankingu.
     */
    private function resolveRankedCategory(Request $request, StudyContextService $studyContextService): ?LicenseCategory
    {
        $user = $request->user();
        $candidates = $studyContextService->activeCategories($user);

        if ($candidates->isEmpty()) {
            return null;
        }

        $requestedId = $request->integer('category') ?: null;
        $user?->loadMissing('profile:user_id,target_category_id');
        $profileCategoryId = $user?->profile?->target_category_id;

        foreach ([$requestedId, $profileCategoryId] as $categoryId) {
            if (! $categoryId) {
                continue;
            }

            $match = $candidates->first(fn (LicenseCategory $category): bool => (int) $category->getKey() === (int) $categoryId);

            if ($match) {
                return $match;
            }
        }

        return $candidates->firstWhere('code', 'B') ?? $candidates->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function leaderboardEntry(RankedPlayerRating $rating, int $position): array
    {
        return [
            'position' => $position,
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
        ];
    }

    /**
     * Własne miejsce w rankingu (ta sama kolejność co lista „Najlepsi gracze”). Null przed pierwszym meczem.
     *
     * @return array<string, mixed>|null
     */
    private function currentUserRanking(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $rating = RankedPlayerRating::query()
            ->with(['user:id,name'])
            ->where('user_id', $user->getKey())
            ->first();

        if (! $rating || $rating->matches_played < 1) {
            return null;
        }

        $playersAhead = RankedPlayerRating::query()
            ->whereHas('user')
            ->where('user_id', '!=', $user->getKey())
            ->where(function ($query) use ($rating): void {
                $query->where('rating', '>', $rating->rating)
                    ->orWhere(fn ($tie) => $tie->where('rating', $rating->rating)->where('peak_rating', '>', $rating->peak_rating))
                    ->orWhere(fn ($tie) => $tie->where('rating', $rating->rating)->where('peak_rating', $rating->peak_rating)->where('wins', '>', $rating->wins))
                    ->orWhere(fn ($tie) => $tie->where('rating', $rating->rating)->where('peak_rating', $rating->peak_rating)->where('wins', $rating->wins)->where('matches_played', '>', $rating->matches_played));
            })
            ->count();

        return [
            ...$this->leaderboardEntry($rating, $playersAhead + 1),
            'total_players' => RankedPlayerRating::query()->whereHas('user')->count(),
        ];
    }
}
