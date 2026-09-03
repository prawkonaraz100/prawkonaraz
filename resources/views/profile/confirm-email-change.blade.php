<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Potwierdź zmianę e-maila - {{ config('app.name') }}</title>
    <x-site.favicons />
    @vite('resources/js/app.ts')
</head>
<body class="bg-slate-50 text-slate-950 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-xl items-center px-6 py-12">
        <section class="w-full border-y border-blue-200 bg-blue-50 px-6 py-8 text-slate-950">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                Potwierdzenie zmiany
            </p>

            <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                Zmienić adres e-mail?
            </h1>

            <p class="mt-4 text-sm leading-6 text-slate-700">
                Konto {{ $user->email }} zostanie przełączone na adres {{ $newEmail }}.
                Po potwierdzeniu wyślemy na nowy adres osobny link weryfikacyjny.
            </p>

            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex h-11 items-center justify-center rounded-md border border-blue-300 px-5 text-sm font-semibold text-[#0d47a1] transition hover:border-blue-500 hover:bg-blue-100"
                >
                    Anuluj
                </a>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex h-11 w-full items-center justify-center rounded-md bg-[#0d47a1] px-5 text-sm font-semibold text-white transition hover:bg-blue-800 sm:w-auto"
                    >
                        Tak, zmień e-mail
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
