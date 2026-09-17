@extends('layouts.public-content')

@section('content')
    <section class="content-band">
        <div class="content-shell py-16 sm:py-20 lg:py-24">
            <div class="mx-auto max-w-[720px] border border-slate-200 bg-white px-6 py-10 sm:px-10 sm:py-12">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $status }}</p>
                <h1 class="mt-3 text-3xl font-semibold leading-tight tracking-tight text-slate-950 sm:text-4xl">
                    {{ $heading }}
                </h1>
                <p class="mt-4 text-base leading-7 text-slate-700">{{ $message }}</p>
                <a
                    href="{{ $hubUrl }}"
                    class="mt-7 inline-flex min-h-11 items-center justify-center rounded-md bg-[#efc54f] px-5 py-3 text-sm font-semibold text-slate-950 hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2"
                >
                    Wróć: {{ $hubName }}
                </a>
            </div>
        </div>
    </section>
@endsection
