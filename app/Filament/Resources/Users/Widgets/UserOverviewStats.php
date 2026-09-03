<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\StudySession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserOverviewStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Stan użytkowników';

    protected ?string $description = 'Szybki obraz skali bazy, weryfikacji, aktywności i blokad przed wejściem w wykresy trendów.';

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'md' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $window = $this->buildDailyWindow(14);
        $totalUsers = User::query()->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $activeUsers30 = $this->countActiveUsers(30);
        $bannedUsers = User::query()->whereNotNull('banned_at')->count();

        $newUsers30 = User::query()
            ->where('created_at', '>=', CarbonImmutable::now()->subDays(30))
            ->count();

        $newBans30 = User::query()
            ->whereNotNull('banned_at')
            ->where('banned_at', '>=', CarbonImmutable::now()->subDays(30))
            ->count();

        $verifiedRate = $totalUsers > 0
            ? round(($verifiedUsers / $totalUsers) * 100)
            : 0;

        $activityRate = $totalUsers > 0
            ? round(($activeUsers30 / $totalUsers) * 100)
            : 0;

        return [
            Stat::make('Wszystkie konta', number_format($totalUsers, 0, ',', ' '))
                ->description(sprintf('+%s w ostatnich 30 dniach', number_format($newUsers30, 0, ',', ' ')))
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedUsers)
                ->color('gray')
                ->chart($this->cumulativeUserSeries($window, 'created_at')),
            Stat::make('Zweryfikowani', number_format($verifiedUsers, 0, ',', ' '))
                ->description(sprintf('%s%% wszystkich kont', number_format($verifiedRate, 0, ',', ' ')))
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->chart($this->cumulativeUserSeries($window, 'email_verified_at')),
            Stat::make('Aktywni 30 dni', number_format($activeUsers30, 0, ',', ' '))
                ->description(sprintf('%s%% bazy z ukończoną sesją', number_format($activityRate, 0, ',', ' ')))
                ->descriptionColor('primary')
                ->icon(Heroicon::OutlinedChartBarSquare)
                ->color('primary')
                ->chart($this->activeUsersSeries($window)),
            Stat::make('Zablokowani', number_format($bannedUsers, 0, ',', ' '))
                ->description(sprintf('%s nowych blokad / 30 dni', number_format($newBans30, 0, ',', ' ')))
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                ->chart($this->cumulativeUserSeries($window, 'banned_at')),
        ];
    }

    /**
     * @return array<string, CarbonImmutable>
     */
    protected function buildDailyWindow(int $days): array
    {
        $cursor = CarbonImmutable::now()->startOfDay()->subDays($days - 1);
        $window = [];

        for ($index = 0; $index < $days; $index++) {
            $window[$cursor->format('Y-m-d')] = $cursor;
            $cursor = $cursor->addDay();
        }

        return $window;
    }

    /**
     * @param  array<string, CarbonImmutable>  $window
     * @return array<int, int>
     */
    protected function cumulativeUserSeries(array $window, string $column): array
    {
        $start = reset($window);
        $counts = array_fill_keys(array_keys($window), 0);

        User::query()
            ->whereNotNull($column)
            ->where($column, '>=', $start)
            ->orderBy($column)
            ->get([$column])
            ->each(function (User $user) use (&$counts, $column): void {
                $bucketKey = CarbonImmutable::parse($user->{$column})->format('Y-m-d');

                if (array_key_exists($bucketKey, $counts)) {
                    $counts[$bucketKey]++;
                }
            });

        $running = User::query()
            ->whereNotNull($column)
            ->where($column, '<', $start)
            ->count();

        $series = [];

        foreach ($counts as $count) {
            $running += $count;
            $series[] = $running;
        }

        return $series;
    }

    /**
     * @param  array<string, CarbonImmutable>  $window
     * @return array<int, int>
     */
    protected function activeUsersSeries(array $window): array
    {
        $start = reset($window);
        $counts = array_fill_keys(array_keys($window), []);

        StudySession::query()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->orderBy('completed_at')
            ->get(['user_id', 'completed_at'])
            ->each(function (StudySession $session) use (&$counts): void {
                $bucketKey = CarbonImmutable::parse($session->completed_at)->format('Y-m-d');

                if (array_key_exists($bucketKey, $counts)) {
                    $counts[$bucketKey][$session->user_id] = true;
                }
            });

        return array_values(array_map(static fn (array $users): int => count($users), $counts));
    }

    protected function countActiveUsers(int $days): int
    {
        return StudySession::query()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', CarbonImmutable::now()->subDays($days))
            ->distinct('user_id')
            ->count('user_id');
    }
}
