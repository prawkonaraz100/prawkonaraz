@extends('layouts.public-content')

@section('content')
    @include('service-legal.partials.document-header', [
        'title' => 'Regulamin serwisu prawkonaraz.pl',
        'lead' => 'Zasady korzystania z konta kursanta, materiałów edukacyjnych, testów i pozostałych funkcji platformy.',
        'version' => $legalDocuments['terms_version'],
    ])

    <section class="content-band">
        <div class="content-shell grid gap-10 py-10 md:py-14 lg:grid-cols-[240px_minmax(0,760px)] lg:justify-center">
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <nav aria-label="Spis treści regulaminu" class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm">
                    <p class="font-semibold text-slate-950">Na tej stronie</p>
                    <ol class="mt-3 space-y-2 text-slate-600">
                        <li><a class="hover:text-blue-700" href="#postanowienia">1. Postanowienia ogólne</a></li>
                        <li><a class="hover:text-blue-700" href="#konto">2. Konto i logowanie</a></li>
                        <li><a class="hover:text-blue-700" href="#uslugi">3. Usługi edukacyjne</a></li>
                        <li><a class="hover:text-blue-700" href="#obowiazki">4. Obowiązki użytkownika</a></li>
                        <li><a class="hover:text-blue-700" href="#platnosci">5. Dostęp płatny</a></li>
                        <li><a class="hover:text-blue-700" href="#reklamacje">6. Reklamacje i konto</a></li>
                        <li><a class="hover:text-blue-700" href="#zmiany">7. Zmiany regulaminu</a></li>
                    </ol>
                </nav>
            </aside>

            <article class="content-prose text-base leading-7 text-slate-700">
                <section id="postanowienia" class="scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">1. Postanowienia ogólne</h2>
                    <p>Regulamin określa zasady korzystania z serwisu internetowego prawkonaraz.pl, w szczególności z bazy pytań, testów, materiałów edukacyjnych, konta kursanta i narzędzi zapamiętujących postępy.</p>
                    <p>Serwis wspiera przygotowanie do egzaminu teoretycznego na prawo jazdy. Nie jest urzędem, ośrodkiem egzaminacyjnym ani gwarancją uzyskania pozytywnego wyniku. Użytkownik powinien weryfikować aktualne wymagania egzaminacyjne i przepisy w oficjalnych źródłach.</p>
                    @include('service-legal.partials.operator')
                </section>

                <section id="konto" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">2. Konto i logowanie</h2>
                    <p>Utworzenie konta wymaga podania prawdziwych danych niezbędnych do jego obsługi oraz zabezpieczenia dostępu do konta. Użytkownik odpowiada za poufność swojego hasła i nie powinien udostępniać konta osobom trzecim.</p>
                    <p>Logowanie przez Google lub innego dostępnego dostawcę oznacza skorzystanie z zewnętrznej metody uwierzytelnienia. Kontynuując rejestrację lub logowanie przez Google, użytkownik akceptuje ten regulamin i potwierdza zapoznanie się z <a class="font-medium text-blue-700 hover:underline" href="{{ route('legal.privacy', absolute: false) }}">Polityką prywatności</a>.</p>
                    <p>Jeżeli serwis wymaga potwierdzenia adresu e-mail albo wyboru kategorii prawa jazdy, dostęp do części funkcji może pozostać ograniczony do zakończenia tych czynności.</p>
                </section>

                <section id="uslugi" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">3. Usługi edukacyjne</h2>
                    <p>Serwis może udostępniać pytania, odpowiedzi, wyjaśnienia, materiały graficzne i filmowe, sesje nauki, egzaminy próbne, statystyki postępu, rankingi oraz mechanizmy powtórek. Zakres funkcji może zależeć od kategorii prawa jazdy, rodzaju konta i posiadanego dostępu.</p>
                    <p>Dokładamy starań, aby materiały były aktualne i poprawne, jednak treści edukacyjne nie zastępują aktów prawnych, komunikatów właściwych organów ani indywidualnej porady specjalisty.</p>
                    <p>Do korzystania z serwisu potrzebne są aktualna przeglądarka internetowa, dostęp do Internetu oraz włączona obsługa JavaScript i niezbędnych plików cookie.</p>
                </section>

                <section id="obowiazki" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">4. Obowiązki użytkownika</h2>
                    <p>Użytkownik powinien korzystać z serwisu zgodnie z prawem, dobrymi obyczajami i przeznaczeniem platformy. Zabronione są w szczególności próby obchodzenia zabezpieczeń, zakłócanie działania serwisu, automatyczne pozyskiwanie treści bez zgody, naruszanie praw innych osób oraz przekazywanie treści bezprawnych.</p>
                    <p>Materiały serwisu są chronione prawami własności intelektualnej. Dostęp do konta pozwala korzystać z nich na własny użytek edukacyjny; nie oznacza przeniesienia praw ani zgody na ich dalszą sprzedaż lub masowe rozpowszechnianie.</p>
                </section>

                <section id="platnosci" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">5. Dostęp płatny</h2>
                    <p>Jeżeli w serwisie zostaną udostępnione płatne plany, ich cena, czas dostępu, zakres świadczenia oraz dostępne metody płatności będą pokazane przed złożeniem zamówienia. Użytkownik otrzyma wyraźną informację, kiedy zamówienie pociąga za sobą obowiązek zapłaty.</p>
                    <p>Prawa konsumenta, w tym zasady odstąpienia od umowy i składania reklamacji, wynikają z obowiązujących przepisów oraz informacji przekazanych w procesie zakupu. Uruchomienie treści cyfrowej przed upływem terminu odstąpienia może wymagać dodatkowej, wyraźnej zgody użytkownika.</p>
                </section>

                <section id="reklamacje" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">6. Reklamacje, rezygnacja i usunięcie konta</h2>
                    <p>Zgłoszenia dotyczące działania serwisu, dostępu lub rozliczeń można przesłać na adres <a class="font-medium text-blue-700 hover:underline" href="mailto:{{ $legalDocuments['privacy_email'] }}">{{ $legalDocuments['privacy_email'] }}</a>. W zgłoszeniu warto podać adres e-mail konta i opis problemu, bez przesyłania hasła.</p>
                    <p>Użytkownik może przestać korzystać z serwisu, wylogować się lub skorzystać z dostępnej funkcji usunięcia konta. Usunięcie konta nie zawsze powoduje natychmiastowe usunięcie wszystkich danych, jeśli ich dalsze przechowywanie jest wymagane przez prawo albo potrzebne do ustalenia, dochodzenia lub obrony roszczeń.</p>
                    <p>Możemy ograniczyć lub zablokować konto w razie poważnego naruszenia regulaminu, bezpieczeństwa serwisu albo przepisów prawa, z uwzględnieniem charakteru naruszenia i możliwości wyjaśnienia sprawy.</p>
                </section>

                <section id="zmiany" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">7. Zmiany regulaminu i postanowienia końcowe</h2>
                    <p>Regulamin może zostać zmieniony, gdy zmieniają się przepisy, funkcje serwisu, sposób świadczenia usług lub wymagania bezpieczeństwa. O istotnych zmianach dotyczących zarejestrowanych użytkowników poinformujemy z wyprzedzeniem w serwisie lub wiadomością e-mail, jeśli będzie to wymagane.</p>
                    <p>Do spraw nieuregulowanych stosuje się prawo polskie, z zachowaniem bezwzględnie obowiązujących praw konsumenta. Aktualna wersja dokumentu jest zawsze dostępna pod adresem tej strony.</p>
                </section>
            </article>
        </div>
    </section>
@endsection
