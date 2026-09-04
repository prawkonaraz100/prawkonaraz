@extends('layouts.public-content')

@section('content')
    @include('service-legal.partials.document-header', [
        'title' => 'Polityka prywatności prawkonaraz.pl',
        'lead' => 'Wyjaśniamy, jakie dane wykorzystujemy, po co je przetwarzamy oraz jak działają logowanie zewnętrzne, cookies i ustawienia analityki.',
        'version' => $legalDocuments['privacy_version'],
    ])

    <section class="content-band">
        <div class="content-shell grid gap-10 py-10 md:py-14 lg:grid-cols-[240px_minmax(0,760px)] lg:justify-center">
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <nav aria-label="Spis treści polityki prywatności" class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm">
                    <p class="font-semibold text-slate-950">Na tej stronie</p>
                    <ol class="mt-3 space-y-2 text-slate-600">
                        <li><a class="hover:text-blue-700" href="#administrator">1. Administrator danych</a></li>
                        <li><a class="hover:text-blue-700" href="#dane">2. Jakie dane zbieramy</a></li>
                        <li><a class="hover:text-blue-700" href="#cele">3. Cele i podstawy</a></li>
                        <li><a class="hover:text-blue-700" href="#google">4. Logowanie przez Google</a></li>
                        <li><a class="hover:text-blue-700" href="#odbiorcy">5. Odbiorcy danych</a></li>
                        <li><a class="hover:text-blue-700" href="#cookies">6. Cookies i analityka</a></li>
                        <li><a class="hover:text-blue-700" href="#prawa">7. Twoje prawa</a></li>
                    </ol>
                </nav>
            </aside>

            <article class="content-prose text-base leading-7 text-slate-700">
                <section id="administrator" class="scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">1. Administrator danych</h2>
                    <p>Administratorem danych osobowych przetwarzanych w związku z korzystaniem z prawkonaraz.pl jest usługodawca wskazany poniżej. Pytania dotyczące prywatności można przesyłać na dedykowany adres kontaktowy.</p>
                    @include('service-legal.partials.operator')
                </section>

                <section id="dane" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">2. Jakie dane zbieramy</h2>
                    <p>W zależności od używanych funkcji możemy przetwarzać:</p>
                    <ul class="mt-4 list-disc space-y-2 pl-6">
                        <li>dane konta, takie jak imię, adres e-mail, zaszyfrowane hasło i status weryfikacji;</li>
                        <li>informacje otrzymane od wybranego dostawcy logowania, w szczególności identyfikator konta, imię, adres e-mail, status jego weryfikacji i zdjęcie profilowe;</li>
                        <li>wybraną kategorię prawa jazdy, ustawienia nauki, odpowiedzi, błędne pytania, postępy i statystyki;</li>
                        <li>dane dotyczące zamówień i przyznanego dostępu, jeżeli korzystasz z funkcji płatnych;</li>
                        <li>dane techniczne i bezpieczeństwa: adres IP, informacje o przeglądarce, identyfikator sesji, czas i ścieżkę żądania oraz przybliżoną lokalizację wynikającą z adresu IP;</li>
                        <li>treść korespondencji i zgłoszeń przesłanych do obsługi serwisu.</li>
                    </ul>
                </section>

                <section id="cele" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">3. Cele i podstawy przetwarzania</h2>
                    <p>Dane przetwarzamy w celu utworzenia i obsługi konta, realizacji wybranych usług, zapisywania postępów, obsługi zamówień i kontaktu z użytkownikiem — gdy jest to potrzebne do wykonania umowy lub działań przed jej zawarciem.</p>
                    <p>Dane możemy także przetwarzać w celu zapewnienia bezpieczeństwa, przeciwdziałania nadużyciom, diagnostyki błędów, obrony roszczeń i rozwijania serwisu na podstawie naszego prawnie uzasadnionego interesu. Dane wymagane przepisami rozliczeniowymi przechowujemy w celu wykonania obowiązków prawnych.</p>
                    <p>Opcjonalną analitykę uruchamiamy na podstawie zgody, o ile konfiguracja serwisu jej wymaga. Zgodę można odrzucić lub później zmienić bez wpływu na podstawowe funkcje konta.</p>
                </section>

                <section id="google" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">4. Logowanie przez Google</h2>
                    <p>Jeśli wybierzesz „Kontynuuj z Google”, Google uwierzytelnia Cię i przekazuje serwisowi dane potrzebne do odnalezienia albo utworzenia konta. Nie otrzymujemy Twojego hasła do Google. Powiązanie opieramy na stabilnym identyfikatorze dostawcy, a adres e-mail wykorzystujemy zgodnie z regułami weryfikacji konta.</p>
                    <p>Kliknięcie przycisku Google po wyświetleniu informacji prawnej oznacza akceptację <a class="font-medium text-blue-700 hover:underline" href="{{ route('legal.terms', absolute: false) }}">Regulaminu</a> i potwierdzenie zapoznania się z niniejszą Polityką prywatności. Nie jest to jednocześnie zgoda na opcjonalne cookies analityczne.</p>
                    <p>Google przetwarza dane również jako odrębny administrator zgodnie ze swoją <a class="font-medium text-blue-700 hover:underline" href="https://policies.google.com/privacy?hl=pl" target="_blank" rel="noopener noreferrer">polityką prywatności</a>.</p>
                </section>

                <section id="odbiorcy" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">5. Odbiorcy danych i okres przechowywania</h2>
                    <p>Dane mogą być powierzane dostawcom hostingu, poczty i obsługi technicznej, narzędzi bezpieczeństwa, geolokalizacji adresów IP, płatności oraz analityki. Gdy wybierzesz logowanie społecznościowe, dane są wymieniane z właściwym dostawcą, takim jak Google lub Facebook.</p>
                    <p>Niektórzy dostawcy mogą przetwarzać dane poza Europejskim Obszarem Gospodarczym. W takim przypadku transfer powinien opierać się na mechanizmie dopuszczonym przez RODO, np. decyzji stwierdzającej odpowiedni stopień ochrony lub standardowych klauzulach umownych.</p>
                    <p>Dane konta i postępów przechowujemy przez okres korzystania z konta, a po jego zamknięciu przez czas potrzebny do rozliczeń, obsługi roszczeń i wykonania obowiązków prawnych. Logi bezpieczeństwa oraz dane analityczne przechowujemy tylko tak długo, jak jest to potrzebne do wskazanych celów i zgodnie z ustawieniami wykorzystywanych usług.</p>
                </section>

                <section id="cookies" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">6. Cookies, pamięć przeglądarki i analityka</h2>
                    <p>Niezbędne pliki cookie i podobne mechanizmy utrzymują sesję, zabezpieczają formularze przed atakami, zapamiętują status logowania oraz podstawowe preferencje interfejsu. Bez nich logowanie i część funkcji serwisu nie działałyby prawidłowo.</p>
                    <p>Opcjonalne narzędzia analityczne, w tym Google Analytics, są uruchamiane dopiero zgodnie z wyborem dokonanym w komunikacie cookies, gdy wymagana jest zgoda. Odmowa nie blokuje logowania ani nauki. Samo zaakceptowanie Regulaminu lub użycie logowania Google nie stanowi zgody na opcjonalną analitykę.</p>
                    <p>Ustawienia cookies można również kontrolować w przeglądarce. Zablokowanie wszystkich cookies może jednak uniemożliwić zalogowanie i zapisanie sesji.</p>
                </section>

                <section id="prawa" class="mt-10 scroll-mt-28">
                    <h2 class="text-2xl font-semibold text-slate-950">7. Twoje prawa</h2>
                    <p>W granicach wynikających z RODO możesz żądać dostępu do danych, ich sprostowania, usunięcia, ograniczenia przetwarzania, przeniesienia danych oraz wnieść sprzeciw wobec przetwarzania opartego na prawnie uzasadnionym interesie. Udzieloną zgodę możesz wycofać w dowolnym momencie bez wpływu na zgodność wcześniejszego przetwarzania.</p>
                    <p>Masz także prawo złożyć skargę do Prezesa Urzędu Ochrony Danych Osobowych. W pierwszej kolejności zachęcamy do kontaktu na <a class="font-medium text-blue-700 hover:underline" href="mailto:{{ $legalDocuments['privacy_email'] }}">{{ $legalDocuments['privacy_email'] }}</a>, abyśmy mogli wyjaśnić sprawę.</p>
                    <p>Polityka może być aktualizowana wraz ze zmianami funkcji, dostawców lub prawa. Data i wersja obowiązującego dokumentu są wskazane na początku strony.</p>
                </section>
            </article>
        </div>
    </section>
@endsection
