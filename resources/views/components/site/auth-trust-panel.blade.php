@php
    $trustArtwork = \Illuminate\Support\Facades\Vite::asset('resources/images/auth/auth-driver-trust-v1.png');
    $benefits = [
        ['Oficjalna baza pytań egzaminacyjnych', 'Aktualne pytania takie jak na egzaminie w WORD'],
        ['Ucz się gdzie i kiedy chcesz', 'Na komputerze, telefonie i tablecie'],
        ['Śledź swoje postępy', 'Zobacz, co już umiesz i nad czym jeszcze popracować'],
        ['Szczegółowe wyjaśnienia i powtórki', 'Zrozum przepisy i zapamiętaj je na dłużej'],
    ];
@endphp

<section class="auth-dialog__trust" aria-label="Dlaczego warto uczyć się z PrawkoNaRaz">
    <img
        src="{{ $trustArtwork }}"
        alt="Kursantka pokazująca aplikację do nauki na telefonie"
        class="auth-dialog__trust-artwork"
        width="1254"
        height="1254"
    >

    <p class="auth-dialog__trust-note">Prawo jazdy na wyciągnięcie ręki!</p>

    <div class="auth-dialog__trust-copy">
        <h2><span>Zaufały nam</span> tysiące przyszłych kierowców</h2>
        <ul>
            @foreach ($benefits as [$title, $description])
                <li>
                    <span class="auth-dialog__trust-check" aria-hidden="true">✓</span>
                    <span>
                        <strong>{{ $title }}</strong>
                        <small>{{ $description }}</small>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    <p class="auth-dialog__trust-footnote">Krok bliżej<br>do prawka!</p>
</section>
