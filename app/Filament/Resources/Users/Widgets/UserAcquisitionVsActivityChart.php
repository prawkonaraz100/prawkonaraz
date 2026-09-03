<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\StudySession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class UserAcquisitionVsActivityChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.resources.users.widgets.user-acquisition-vs-activity-chart';

    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 3,
    ];

    public ?string $filter = '30';

    protected ?string $maxHeight = '15rem';

    public function getHeading(): string | Htmlable | null
    {
        return new HtmlString(
            '<span class="adm-users-chart-heading"><span class="adm-users-chart-heading__eyebrow">Aktywacja</span><span class="adm-users-chart-heading__title">Nowi użytkownicy vs aktywność</span></span>',
        );
    }

    public function getDescription(): ?string
    {
        $mode = $this->resolveMode();
        $buckets = $this->generateBuckets($mode);
        $start = reset($buckets)['start'];

        $newUsers = User::query()
            ->where('created_at', '>=', $start)
            ->count();

        $activeUsers = StudySession::query()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->distinct('user_id')
            ->count('user_id');

        return sprintf(
            'Porównanie nowych kont z realną aktywnością nauki w tym samym oknie. Nowi: %s, aktywni w sesjach: %s.',
            number_format($newUsers, 0, ',', ' '),
            number_format($activeUsers, 0, ',', ' '),
        );
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '30' => '30 dni',
            '90' => '90 dni',
            '365' => '12 miesięcy',
        ];
    }

    protected function getData(): array
    {
        $mode = $this->resolveMode();
        $buckets = $this->generateBuckets($mode);

        $newUsers = array_fill_keys(array_keys($buckets), 0);
        $activeUsers = array_fill_keys(array_keys($buckets), []);

        User::query()
            ->where('created_at', '>=', reset($buckets)['start'])
            ->orderBy('created_at')
            ->get(['created_at'])
            ->each(function (User $user) use (&$newUsers, $mode): void {
                $bucketKey = $this->bucketKey(CarbonImmutable::parse($user->created_at), $mode);

                if (array_key_exists($bucketKey, $newUsers)) {
                    $newUsers[$bucketKey]++;
                }
            });

        StudySession::query()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', reset($buckets)['start'])
            ->orderBy('completed_at')
            ->get(['user_id', 'completed_at'])
            ->each(function (StudySession $session) use (&$activeUsers, $mode): void {
                $bucketKey = $this->bucketKey(CarbonImmutable::parse($session->completed_at), $mode);

                if (array_key_exists($bucketKey, $activeUsers)) {
                    $activeUsers[$bucketKey][$session->user_id] = true;
                }
            });

        return [
            'datasets' => [
                [
                    'label' => 'Nowi użytkownicy',
                    'data' => array_values($newUsers),
                    'borderColor' => '#0284c7',
                    'backgroundColor' => 'rgba(2, 132, 199, 0.10)',
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 0,
                    'tension' => 0.32,
                    'fill' => false,
                ],
                [
                    'label' => 'Aktywni w nauce',
                    'data' => array_map(static fn (array $users): int => count($users), array_values($activeUsers)),
                    'borderColor' => '#0f172a',
                    'backgroundColor' => 'rgba(15, 23, 42, 0.10)',
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 0,
                    'tension' => 0.32,
                    'fill' => false,
                ],
            ],
            'labels' => array_values(array_map(static fn (array $bucket): string => $bucket['label'], $buckets)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'animation' => false,
            'transitions' => [
                'active' => [
                    'animation' => [
                        'duration' => 0,
                    ],
                ],
                'show' => [
                    'animations' => [
                        'x' => [
                            'duration' => 0,
                        ],
                        'y' => [
                            'duration' => 0,
                        ],
                        'colors' => [
                            'duration' => 0,
                        ],
                    ],
                ],
                'hide' => [
                    'animations' => [
                        'x' => [
                            'duration' => 0,
                        ],
                        'y' => [
                            'duration' => 0,
                        ],
                        'colors' => [
                            'duration' => 0,
                        ],
                    ],
                ],
                'resize' => [
                    'animation' => [
                        'duration' => 0,
                    ],
                ],
            ],
            'animations' => [
                'x' => [
                    'duration' => 0,
                ],
                'y' => [
                    'duration' => 0,
                ],
                'colors' => [
                    'duration' => 0,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'boxWidth' => 10,
                        'padding' => 14,
                        'color' => '#334155',
                        'font' => [
                            'size' => 11,
                            'family' => 'Open Sans',
                        ],
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'color' => '#64748b',
                        'maxRotation' => 0,
                        'autoSkip' => true,
                        'maxTicksLimit' => match ($this->resolveMode()) {
                            'daily' => 10,
                            'weekly' => 10,
                            default => 12,
                        },
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.16)',
                    ],
                    'ticks' => [
                        'color' => '#64748b',
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function resolveMode(): string
    {
        return match ((string) ($this->filter ?? '30')) {
            '90' => 'weekly',
            '365' => 'monthly',
            default => 'daily',
        };
    }

    /**
     * @return array<string, array{start: CarbonImmutable, label: string}>
     */
    protected function generateBuckets(string $mode): array
    {
        $today = CarbonImmutable::now();
        $buckets = [];

        if ($mode === 'monthly') {
            $cursor = $today->startOfMonth()->subMonths(11);

            for ($index = 0; $index < 12; $index++) {
                $key = $cursor->format('Y-m');
                $buckets[$key] = [
                    'start' => $cursor,
                    'label' => $cursor->translatedFormat('m.Y'),
                ];

                $cursor = $cursor->addMonth();
            }

            return $buckets;
        }

        if ($mode === 'weekly') {
            $cursor = $today->startOfWeek()->subWeeks(12);

            for ($index = 0; $index < 13; $index++) {
                $key = $cursor->format('o-W');
                $buckets[$key] = [
                    'start' => $cursor,
                    'label' => $cursor->format('d.m'),
                ];

                $cursor = $cursor->addWeek();
            }

            return $buckets;
        }

        $cursor = $today->startOfDay()->subDays(29);

        for ($index = 0; $index < 30; $index++) {
            $key = $cursor->format('Y-m-d');
            $buckets[$key] = [
                'start' => $cursor,
                'label' => $cursor->format('d.m'),
            ];

            $cursor = $cursor->addDay();
        }

        return $buckets;
    }

    protected function bucketKey(CarbonImmutable $date, string $mode): string
    {
        return match ($mode) {
            'monthly' => $date->startOfMonth()->format('Y-m'),
            'weekly' => $date->startOfWeek()->format('o-W'),
            default => $date->startOfDay()->format('Y-m-d'),
        };
    }
}
