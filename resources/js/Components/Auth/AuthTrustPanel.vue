<script setup lang="ts">
import trustArtwork from '../../../images/auth/auth-driver-trust-v1.png';

const benefits = [
    {
        title: 'Oficjalna baza pytań egzaminacyjnych',
        description: 'Aktualne pytania takie jak na egzaminie w WORD',
    },
    {
        title: 'Ucz się gdzie i kiedy chcesz',
        description: 'Na komputerze, telefonie i tablecie',
    },
    {
        title: 'Śledź swoje postępy',
        description: 'Zobacz, co już umiesz i nad czym jeszcze popracować',
    },
    {
        title: 'Szczegółowe wyjaśnienia i powtórki',
        description: 'Zrozum przepisy i zapamiętaj je na dłużej',
    },
];
</script>

<template>
    <section class="auth-dialog__trust" aria-label="Dlaczego warto uczyć się z PrawkoNaRaz">
        <img
            :src="trustArtwork"
            alt="Kursantka pokazująca aplikację do nauki na telefonie"
            class="auth-dialog__trust-artwork"
        >

        <p class="auth-dialog__trust-note">Prawo jazdy na wyciągnięcie ręki!</p>

        <div class="auth-dialog__trust-copy">
            <h2><span>Zaufały nam</span> tysiące przyszłych kierowców</h2>
            <ul>
                <li v-for="benefit in benefits" :key="benefit.title">
                    <span class="auth-dialog__trust-check" aria-hidden="true">✓</span>
                    <span>
                        <strong>{{ benefit.title }}</strong>
                        <small>{{ benefit.description }}</small>
                    </span>
                </li>
            </ul>
        </div>

        <p class="auth-dialog__trust-footnote">Krok bliżej<br>do prawka!</p>
    </section>
</template>
