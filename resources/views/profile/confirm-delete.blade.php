<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Potwierdź usunięcie konta - {{ config('app.name') }}</title>
    <x-site.favicons />
    @vite('resources/js/app.ts')
</head>
<body class="bg-slate-50 text-slate-950 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-xl items-center px-6 py-12">
        <section class="w-full border-y border-red-200 bg-red-50 px-6 py-8 text-red-950">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-700">
                Ostateczne potwierdzenie
            </p>

            <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                Usunąć konto?
            </h1>

            <p class="mt-4 text-sm leading-6 text-red-800">
                Konto {{ $user->email }} zostanie trwale usunięte razem z powiązanymi danymi aplikacji.
                Tej operacji nie da się cofnąć.
            </p>

            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex h-11 items-center justify-center rounded-md border border-red-300 px-5 text-sm font-semibold text-red-900 transition hover:border-red-500 hover:bg-red-100"
                >
                    Anuluj
                </a>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex h-11 w-full items-center justify-center rounded-md bg-red-700 px-5 text-sm font-semibold text-white transition hover:bg-red-800 sm:w-auto"
                    >
                        Tak, usuń konto
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
