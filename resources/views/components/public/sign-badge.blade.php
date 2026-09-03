@props([
    'sign',
    'variant' => 'inline',
])

@php
    $sign = is_array($sign) ? $sign : [];
    $variant = in_array($variant, ['inline', 'comparison', 'list', 'detail'], true) ? $variant : 'inline';
    $code = (string) ($sign['code'] ?? '');
    $name = (string) ($sign['name'] ?? '');
    $title = (string) ($sign['title'] ?? trim($code.' '.$name));
    $description = (string) ($sign['description'] ?? '');
    $imageUrl = (string) ($sign['image_url'] ?? '');
    $imageAlt = (string) ($sign['image_alt'] ?? ($title !== '' ? $title : 'Znak drogowy'));
    $url = (string) ($sign['url'] ?? '');
    $linkLabel = (string) ($sign['link_label'] ?? 'Zobacz opis znaku');
    $buttonLabel = trim('Pokaż znak '.$title);
    $baseClass = 'sign-ref-badge group cursor-pointer border border-[#dce3eb] bg-white text-[#111827] shadow-sm transition hover:border-[#c4cfdb] hover:bg-[#fbfcfe] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d01921]/35';
    $buttonClass = match ($variant) {
        'comparison' => $baseClass.' flex w-full items-center gap-3 rounded-[4px] p-3 text-left',
        'list' => $baseClass.' flex w-full flex-col items-center gap-1.5 rounded-[4px] p-2.5 text-center min-[1180px]:w-[128px]',
        'detail' => $baseClass.' flex h-24 w-24 shrink-0 items-center justify-center rounded-[4px] p-2',
        default => $baseClass.' mx-0.5 inline-flex min-h-8 items-center gap-1.5 rounded-[4px] px-1.5 py-0.5 align-middle text-[0.86em] font-bold leading-none',
    };
    $imageWrapClass = match ($variant) {
        'comparison' => 'flex h-14 w-14 shrink-0 items-center justify-center',
        'list' => 'flex h-11 w-11 shrink-0 items-center justify-center min-[1180px]:h-12 min-[1180px]:w-12',
        'detail' => 'flex h-full w-full items-center justify-center',
        default => 'flex h-8 w-8 shrink-0 items-center justify-center',
    };
    $imageClass = match ($variant) {
        'comparison' => 'max-h-14 max-w-14 object-contain transition group-hover:scale-[1.06]',
        'list' => 'max-h-11 max-w-11 object-contain transition group-hover:scale-[1.06] min-[1180px]:max-h-12 min-[1180px]:max-w-12',
        'detail' => 'h-full w-full object-contain transition group-hover:scale-[1.04]',
        default => 'max-h-8 max-w-8 object-contain transition group-hover:scale-[1.08]',
    };
@endphp

@if ($code !== '')
    <button
        type="button"
        class="{{ $buttonClass }}"
        aria-label="{{ $buttonLabel }}"
        title="{{ $title }}"
        data-sign-preview
        data-sign-code="{{ $code }}"
        data-sign-name="{{ $name }}"
        data-sign-title="{{ $title }}"
        data-sign-description="{{ $description }}"
        data-sign-image-url="{{ $imageUrl }}"
        data-sign-image-alt="{{ $imageAlt }}"
        data-sign-url="{{ $url }}"
        data-sign-link-label="{{ $linkLabel }}"
    >
        @if ($imageUrl !== '')
            <span class="{{ $imageWrapClass }}" aria-hidden="true">
                <img
                    src="{{ $imageUrl }}"
                    alt=""
                    class="{{ $imageClass }}"
                    loading="lazy"
                >
            </span>
        @elseif ($variant !== 'inline')
            <span class="{{ $imageWrapClass }} border border-dashed border-[#cbd5e1] text-[0.68rem] font-bold text-[#64748b]" aria-hidden="true">
                {{ $code }}
            </span>
        @endif

        @if ($variant === 'inline')
            <span>{{ $code }}</span>
        @elseif ($variant === 'detail')
            <span class="sr-only">{{ $title }}</span>
        @else
            <span class="min-w-0 max-w-full">
                <span class="block text-[0.82rem] font-bold leading-5 text-[#111827]">{{ $code }}</span>
                @if ($name !== '')
                    <span class="mt-0.5 block max-w-full break-words text-[0.72rem] font-semibold leading-4 text-[#4b5563]">{{ $name }}</span>
                @endif
            </span>
        @endif
    </button>
@endif
