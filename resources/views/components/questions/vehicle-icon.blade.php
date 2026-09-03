@props(['code' => ''])

@php
    $normalizedCode = strtolower((string) $code);
    $assetCode = in_array($normalizedCode, ['a', 'a1', 'a2', 'am', 'b', 'b1', 'c', 'c1', 'd', 'd1', 't'], true)
        ? $normalizedCode
        : 'a';
@endphp

<img
    {{ $attributes->merge(['class' => 'object-contain']) }}
    src="{{ \Illuminate\Support\Facades\Vite::asset('resources/images/questions/category-icons/'.$assetCode.'.png') }}"
    alt=""
    aria-hidden="true"
    loading="lazy"
>
