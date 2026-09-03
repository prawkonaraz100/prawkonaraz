@props([
    'items' => [],
    'navClass' => '',
    'listClass' => '',
])

<nav aria-label="Breadcrumb" class="{{ $navClass }}">
    <ol class="flex flex-wrap gap-2 text-sm text-slate-500 {{ $listClass }}">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->first)
                    <span aria-hidden="true">/</span>
                @endif
                @if (! $loop->last && ! empty($item['url'] ?? null))
                    <a href="{{ $item['url'] }}" class="hover:text-slate-900">{{ $item['label'] }}</a>
                @else
                    <span class="text-slate-700">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
