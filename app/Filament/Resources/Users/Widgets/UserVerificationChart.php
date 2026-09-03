<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class UserVerificationChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public ?string $filter = 'all';

    protected ?string $maxHeight = '15rem';

    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(
            '<span class="adm-users-chart-heading"><span class="adm-users-chart-heading__eyebrow">Status kont</span><span class="adm-users-chart-heading__title">Zweryfikowani vs nieweryfikowani</span></span>',
        );
    }

    public function getDescription(): ?string
    {
        $query = $this->baseQuery();

        return sprintf(
            'Segmentacja kont według statusu e-mail. W wybranym segmencie jest %s rekordów i %s zablokowanych.',
            number_format((clone $query)->count(), 0, ',', ' '),
            number_format((clone $query)->whereNotNull('banned_at')->count(), 0, ',', ' '),
        );
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return [
            'all' => 'Wszyscy',
            'learners' => 'Kursanci',
            'admins' => 'Administratorzy',
        ];
    }

    protected function getData(): array
    {
        $query = $this->baseQuery();

        $verified = (clone $query)->whereNotNull('email_verified_at')->count();
        $pending = (clone $query)->whereNull('email_verified_at')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Status weryfikacji',
                    'data' => [$verified, $pending],
                    'backgroundColor' => [
                        'rgba(15, 23, 42, 0.92)',
                        'rgba(2, 132, 199, 0.72)',
                    ],
                    'borderColor' => [
                        '#0f172a',
                        '#0284c7',
                    ],
                    'borderWidth' => 1,
                    'hoverOffset' => 0,
                ],
            ],
            'labels' => [
                'Zweryfikowani',
                'Bez weryfikacji',
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'cutout' => '68%',
            'animation' => false,
            'transitions' => [
                'active' => [
                    'animation' => [
                        'duration' => 0,
                    ],
                ],
                'show' => [
                    'animations' => [
                        'colors' => [
                            'duration' => 0,
                        ],
                    ],
                ],
                'hide' => [
                    'animations' => [
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
        ];
    }

    protected function baseQuery(): Builder
    {
        return User::query()
            ->when(
                $this->filter === 'learners',
                fn (Builder $query): Builder => $query->where('is_admin', false),
            )
            ->when(
                $this->filter === 'admins',
                fn (Builder $query): Builder => $query->where('is_admin', true),
            );
    }
}
