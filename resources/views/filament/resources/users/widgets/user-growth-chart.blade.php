@php
    use Filament\Schemas\Concerns\InteractsWithSchemas;
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
    $cumulative = $data['datasets'][1]['data'] ?? [];
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
                'headingLabel' => 'Wzrost użytkowników',
                'labels' => $labels,
                'series' => [
                    [
                        'label' => 'Nowi użytkownicy',
                        'type' => 'bar',
                        'values' => $newUsers,
                        'color' => '#0284c7',
                        'fill' => 'rgba(2, 132, 199, 0.18)',
                        'max' => max(max($newUsers ?: [1]), 1),
                    ],
                    [
                        'label' => 'Łączna liczba kont',
                        'type' => 'line',
                        'values' => $cumulative,
                        'color' => '#0f172a',
                        'strokeWidth' => 2.75,
                        'max' => max(max($cumulative ?: [1]), 1),
                    ],
                ],
            ])
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
