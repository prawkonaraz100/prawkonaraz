@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $filters = $this->getFilters();
    $isCollapsible = $this->isCollapsible();
    $data = $this->getCachedData();
    $labels = $data['labels'] ?? [];
    $newUsers = $data['datasets'][0]['data'] ?? [];
    $activeUsers = $data['datasets'][1]['data'] ?? [];
    $sharedMax = max(max($newUsers ?: [1]), max($activeUsers ?: [1]), 1);
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section
        :description="$description"
        :heading="$heading"
        :collapsible="$isCollapsible"
    >
        @if ($filters)
            <x-slot name="afterHeader">
                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="filter"
                    class="fi-wi-chart-filter"
                >
                    <x-filament::input.select
                        inline-prefix
                        wire:model.live="filter"
                    >
                        @foreach ($filters as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-slot>
        @endif

        <div
            {{
                (new ComponentAttributeBag)
                    ->color(ChartWidgetComponent::class, $color)
                    ->class(['adm-users-static-chart-card'])
            }}
        >
            @include('filament.resources.users.widgets.partials.static-time-series-chart', [
                'headingLabel' => 'Nowi użytkownicy vs aktywność',
                'labels' => $labels,
                'series' => [
                    [
                        'label' => 'Nowi użytkownicy',
                        'type' => 'line',
                        'values' => $newUsers,
                        'color' => '#0284c7',
                        'strokeWidth' => 2.5,
                        'max' => $sharedMax,
                    ],
                    [
                        'label' => 'Aktywni w nauce',
                        'type' => 'line',
                        'values' => $activeUsers,
                        'color' => '#0f172a',
                        'strokeWidth' => 2.5,
                        'max' => $sharedMax,
                    ],
                ],
            ])
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
