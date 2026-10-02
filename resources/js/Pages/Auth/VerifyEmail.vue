<script setup lang="ts">
import AuthRecoveryShell from '@/Components/Auth/AuthRecoveryShell.vue';
import { useSafeLogout } from '@/composables/useSafeLogout';
import { expiredSessionLoginUrl, refreshCsrfSession } from '@/lib/csrfSession';
import type { PageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, LogOut, Mail, MailCheck, Send } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{ status?: string }>();
const page = usePage<PageProps>();
const email = computed(() => page.props.auth.user?.email ?? '');
const form = useForm({});
const { logout, logoutError, logoutPreparing } = useSafeLogout();
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
const emailChangeConfirmed = computed(() => props.status === 'email-change-confirmed');
const verificationLinkFailed = computed(() => props.status === 'verification-link-failed');
const csrfPreparing = ref(false);
const csrfError = ref<string | null>(null);
const sendErrors = computed(() => Object.values(form.errors).filter((error): error is string => typeof error === 'string'));

const submit = async () => {
    if (form.processing || csrfPreparing.value || logoutPreparing.value) return;
    csrfPreparing.value = true;
    csrfError.value = null;
    form.clearErrors();
    try {
        const session = await refreshCsrfSession();
        if (!session.authenticated) {
            csrfPreparing.value = false;
            router.visit(expiredSessionLoginUrl());
            return;
        }
        form.post(route('verification.send'), {
            preserveScroll: true,
            onFinish: () => { csrfPreparing.value = false; },
        });
    } catch {
        csrfPreparing.value = false;
        csrfError.value = 'Nie udało się odświeżyć sesji. Odśwież stronę i spróbuj ponownie.';
    }
};

const steps = [
    { title: 'Otwórz wiadomość', description: 'Szukaj maila od PrawkoNaRaz z tematem „Potwierdź adres e-mail”.' },
    { title: 'Potwierdź adres', description: 'Kliknij przycisk „Potwierdź adres e-mail” w wiadomości.' },
    { title: 'Przejdź dalej', description: 'Po potwierdzeniu wrócisz do serwisu i dokończysz przygotowanie konta do nauki.' },
];
</script>

<template>
    <Head title="Potwierdź adres e-mail" />
    <AuthRecoveryShell
        title="Potwierdź adres e-mail"
        lead="Jeszcze jeden krok! Otwórz wiadomość od PrawkoNaRaz i kliknij link, aby potwierdzić swoje konto."
        back-href="/"
        back-label="Wróć na stronę główną"
    >
        <template #eyebrow>
            <MailCheck :size="17" :stroke-width="2" aria-hidden="true" />
            Jesteś o krok od nauki
        </template>
        <div v-if="email" class="verification-address">
            <span class="verification-address__icon"><Mail :size="23" :stroke-width="1.8" aria-hidden="true" /></span>
            <div class="min-w-0">
                <p>Adres do potwierdzenia</p>
                <strong>{{ email }}</strong>
            </div>
        </div>
        <div v-if="verificationLinkFailed" class="verification-delivery-failed" role="alert">
            <AlertCircle :size="22" aria-hidden="true" />
            <div><strong>Konto jest gotowe, ale wiadomość nie została wysłana</strong>
                <p>Wystąpił problem z wysyłką. Nie zakładaj konta ponownie — użyj przycisku poniżej, aby ponowić wysyłkę linku.</p>
            </div>
        </div>
        <div v-if="verificationLinkSent || emailChangeConfirmed" class="auth-recovery-notice" role="status" aria-live="polite">
            <CheckCircle2 :size="22" :stroke-width="2.1" aria-hidden="true" />
            <div>
                <strong>{{ emailChangeConfirmed ? 'Adres e-mail został zmieniony' : 'Link potwierdzający został wysłany' }}</strong>
                <p>{{ emailChangeConfirmed ? 'Wysłaliśmy link potwierdzający na nowy adres e-mail.' : 'Sprawdź swoją skrzynkę. Wiadomość może też trafić do folderu spam.' }}</p>
            </div>
        </div>
        <ol class="verification-steps" aria-label="Jak potwierdzić adres e-mail">
            <li v-for="(step, index) in steps" :key="step.title">
                <span class="verification-steps__number" aria-hidden="true">{{ index + 1 }}</span>
                <div><strong>{{ step.title }}</strong><p>{{ step.description }}</p></div>
            </li>
        </ol>
        <form class="auth-recovery-form" @submit.prevent="submit" :aria-busy="form.processing || csrfPreparing">
            <p class="verification-help">Nie widzisz wiadomości? Sprawdź folder spam lub wyślij link ponownie.</p>
            <button type="submit" class="auth-recovery-submit" :disabled="form.processing || csrfPreparing || logoutPreparing">
                <span>{{ form.processing ? 'Wysyłanie...' : csrfPreparing ? 'Sprawdzanie sesji...' : 'Wyślij link ponownie' }}</span>
                <Send :size="19" :stroke-width="1.8" aria-hidden="true" />
            </button>
            <p v-for="error in sendErrors" :key="error" class="verification-error" role="alert">{{ error }}</p>
            <p v-if="csrfError" class="verification-error" role="alert">{{ csrfError }}</p>
        </form>
        <template #footer>
            <p class="verification-help">Chcesz użyć innego konta?</p>
            <button type="button" class="verification-logout" :disabled="logoutPreparing || form.processing || csrfPreparing" @click="logout(route('logout'))">
                <LogOut :size="17" :stroke-width="1.8" aria-hidden="true" />
                <span>{{ logoutPreparing ? 'Sprawdzanie sesji...' : 'Wyloguj się' }}</span>
            </button>
            <p v-if="logoutError" class="verification-error" role="alert">{{ logoutError }}</p>
        </template>
    </AuthRecoveryShell>
</template>

<style scoped>
.verification-address {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-top: 1.4rem;
    padding: 0.95rem 1rem;
    border-radius: 0.75rem;
    background: #f2f5f9;
    color: #062b5d;
}
.verification-address__icon {
    display: grid;
    flex: none;
    width: 2.65rem;
    height: 2.65rem;
    place-items: center;
    border-radius: 50%;
    background: #e5ecf6;
}
.verification-address p { margin: 0 0 0.15rem; color: #687384; font-size: 0.75rem; }
.verification-address strong { display: block; font-size: 0.9rem; line-height: 1.4; overflow-wrap: anywhere; }
.verification-steps { display: grid; gap: 1rem; margin: 1.5rem 0 0; padding: 0; list-style: none; }
.verification-steps li { display: flex; align-items: flex-start; gap: 0.8rem; }
.verification-steps__number {
    display: grid;
    flex: none;
    width: 1.7rem;
    height: 1.7rem;
    place-items: center;
    border-radius: 50%;
    background: #fff2d6;
    color: #8b5b00;
    font-size: 0.75rem;
    font-weight: 700;
}
.verification-steps strong { color: #062b5d; font-size: 0.85rem; }
.verification-steps p,
.verification-help { margin: 0.2rem 0 0; color: #687384; font-size: 0.8rem; line-height: 1.5; }
.verification-logout {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    min-height: 2.75rem;
    margin-top: 0.45rem;
    padding: 0.4rem 0.8rem;
    border-radius: 0.4rem;
    color: #0b376d;
    font-size: 0.85rem;
    font-weight: 600;
    transition: background-color 160ms ease;
}
.verification-logout:hover:not(:disabled) { background: #f2f5f9; }
.verification-logout:focus-visible { outline: 3px solid #244e8340; outline-offset: 3px; }
.verification-logout:disabled { cursor: not-allowed; opacity: 0.6; }
.verification-error { margin-top: 0.75rem; color: #b42318; font-size: 0.8rem; line-height: 1.5; }
.verification-delivery-failed { display: flex; gap: 0.75rem; margin-top: 1rem; padding: 1rem; border-radius: 0.75rem; background: #fff4e5; color: #854d0e; }
.verification-delivery-failed svg { flex: none; }
.verification-delivery-failed strong { font-size: 0.85rem; }
.verification-delivery-failed p { margin-top: 0.3rem; font-size: 0.8rem; line-height: 1.5; }
</style>
