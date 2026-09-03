@php
    $series = $series ?? [];
    $labels = array_values($labels ?? []);
    $pointCount = max(count($labels), 1);
    $width = 900;
    $height = 290;
    $paddingTop = 16;
    $paddingRight = 18;
    $paddingBottom = 42;
    $paddingLeft = 18;
    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - $paddingTop - $paddingBottom;
    $slotWidth = $plotWidth / $pointCount;
    $baselineY = $paddingTop + $plotHeight;
    $gridLines = 4;

    $normalizePoint = static function (float|int $value, float|int $maxValue) use ($paddingTop, $plotHeight): float {
        if ($maxValue <= 0) {
            return $paddingTop + $plotHeight;
        }

        return $paddingTop + $plotHeight - (($value / $maxValue) * $plotHeight);
    };

    $gridPositions = [];

    for ($index = 0; $index <= $gridLines; $index++) {
        $gridPositions[] = $paddingTop + (($plotHeight / $gridLines) * $index);
    }
@endphp

<div class="adm-static-chart">
    <div class="adm-static-chart__legend">
        @foreach ($series as $item)
            <div class="adm-static-chart__legend-item">
                <span
                    class="adm-static-chart__legend-swatch"
                    style="--adm-chart-swatch: {{ $item['color'] }};"
                ></span>

                <span class="adm-static-chart__legend-label">
                    {{ $item['label'] }}
                </span>
            </div>
        @endforeach
    </div>

    <div class="adm-static-chart__canvas">
        <svg
            viewBox="0 0 {{ $width }} {{ $height }}"
            role="img"
            aria-label="{{ $headingLabel ?? 'Wykres użytkowników' }}"
        >
            @foreach ($gridPositions as $position)
                <line
                    x1="{{ $paddingLeft }}"
                    y1="{{ $position }}"
                    x2="{{ $width - $paddingRight }}"
                    y2="{{ $position }}"
                    class="adm-static-chart__grid-line"
                ></line>
            @endforeach

            <line
                x1="{{ $paddingLeft }}"
                y1="{{ $baselineY }}"
                x2="{{ $width - $paddingRight }}"
                y2="{{ $baselineY }}"
                class="adm-static-chart__baseline"
            ></line>

            @foreach ($series as $item)
                @if (($item['type'] ?? 'line') === 'bar')
                    @php
                        $values = array_values($item['values'] ?? []);
                        $maxValue = max($item['max'] ?? max($values ?: [1]), 1);
                        $barWidth = min(28, $slotWidth * 0.56);
                    @endphp

                    @foreach ($values as $index => $value)
                        @php
                            $x = $paddingLeft + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2);
                            $y = $normalizePoint($value, $maxValue);
                            $barHeight = max(2, $baselineY - $y);
                        @endphp

                        <rect
                            x="{{ round($x, 2) }}"
                            y="{{ round($y, 2) }}"
                            width="{{ round($barWidth, 2) }}"
                            height="{{ round($barHeight, 2) }}"
                            rx="4"
                            fill="{{ $item['fill'] ?? $item['color'] }}"
                            stroke="{{ $item['color'] }}"
                            stroke-width="1"
                        ></rect>
                    @endforeach
                @else
                    @php
                        $values = array_values($item['values'] ?? []);
                        $maxValue = max($item['max'] ?? max($values ?: [1]), 1);
                        $points = [];

                        foreach ($values as $index => $value) {
                            $x = $paddingLeft + ($slotWidth * $index) + ($slotWidth / 2);
                            $y = $normalizePoint($value, $maxValue);
                            $points[] = round($x, 2) . ',' . round($y, 2);
                        }
                    @endphp

                    @if (count($points) > 1)
                        <polyline
                            points="{{ implode(' ', $points) }}"
                            fill="none"
                            stroke="{{ $item['color'] }}"
                            stroke-width="{{ $item['strokeWidth'] ?? 2.5 }}"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        ></polyline>
                    @elseif (count($points) === 1)
                        @php [$pointX, $pointY] = explode(',', $points[0]); @endphp

                        <circle
                            cx="{{ $pointX }}"
                            cy="{{ $pointY }}"
                            r="3"
                            fill="{{ $item['color'] }}"
                        ></circle>
                    @endif
                @endif
            @endforeach

            @foreach ($labels as $index => $label)
                @php
                    $x = $paddingLeft + ($slotWidth * $index) + ($slotWidth / 2);
                @endphp

                <text
                    x="{{ round($x, 2) }}"
                    y="{{ $height - 10 }}"
                    text-anchor="middle"
                    class="adm-static-chart__label"
                >
                    {{ $label }}
                </text>
            @endforeach
        </svg>
    </div>
</div>
