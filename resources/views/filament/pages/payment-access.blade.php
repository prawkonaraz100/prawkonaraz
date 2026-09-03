<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]">
        <x-filament::section>
            <x-slot name="heading">Aktualny stan</x-slot>
            <x-slot name="description">
                To ustawienie decyduje, czy zweryfikowany kursant potrzebuje zakupu do rozpoczęcia pełnej nauki.
            </x-slot>

            <div class="grid gap-5 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-start">
                <div class="inline-flex w-fit items-center rounded-full px-3 py-1.5 text-sm font-semibold {{ $requiresPayment ? 'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400' : 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400' }}">
                    {{ $requiresPayment ? 'Płatność wymagana' : 'Dostęp otwarty' }}
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $requiresPayment ? 'Pełna nauka po aktywacji dostępu' : 'Pełna nauka po potwierdzeniu e-maila' }}
                    </h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-400">
                        {{ $requiresPayment
                            ? 'Konto można założyć bezpłatnie, ale pełna nauka wymaga zakupu albo innego aktywnego dostępu.'
                            : 'Konto można założyć bezpłatnie. Po potwierdzeniu e-maila kursant zaczyna naukę bez zakupu.' }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Ostatnia zmiana</x-slot>

            @if ($lastChange)
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Czas</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $lastChange['at'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Administrator</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $lastChange['actor'] }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm leading-6 text-gray-600 dark:text-gray-400">
                    Nie ma jeszcze zmiany zapisanej z panelu. Obowiązuje bezpieczny stan domyślny: płatność wymagana.
                </p>
            @endif
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Co zmieni przełączenie</x-slot>

        <div class="grid gap-5 md:grid-cols-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Rejestracja</h3>
                <p class="mt-1.5 text-sm leading-6 text-gray-600 dark:text-gray-400">Zawsze bezpłatna. Kursant nadal potwierdza adres e-mail.</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Dostęp otwarty</h3>
                <p class="mt-1.5 text-sm leading-6 text-gray-600 dark:text-gray-400">Nie tworzy zamówień ani grantów. Działa od razu dla zweryfikowanych kont.</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Powrót do płatności</h3>
                <p class="mt-1.5 text-sm leading-6 text-gray-600 dark:text-gray-400">Zakupy, zaproszenia i ręcznie nadane dostępy pozostają ważne.</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
