<script setup lang="ts">
import AuthRecoveryShell from '@/Components/Auth/AuthRecoveryShell.vue';
import { useSafeLogout } from '@/composables/useSafeLogout';
import type { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { AlertCircle } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ problem: 'invalid-link' | 'wrong-account' }>();
const page = usePage<PageProps>();
const { logout, logoutError, logoutPreparing } = useSafeLogout();
const wrongAccount = computed(() => props.problem === 'wrong-account');
const title = computed(() => wrongAccount.value ? 'Zaloguj się na właściwe konto' : 'Ten link nie jest już ważny');
const lead = computed(() => wrongAccount.value
    ? 'Ten link dotyczy innego konta niż obecnie zalogowane. Wyloguj się i zaloguj adresem, na który otrzymałeś wiadomość.'
    : 'Link mógł wygasnąć, zostać zmieniony lub dotyczyć poprzedniego adresu e-mail. Twoje konto nie zostało usunięte.');
const unverified = computed(() => page.props.auth.user && !page.props.auth.user.email_verified_at);
</script>

<template>
    <Head :title="title" />
    <AuthRecoveryShell :title="title" :lead="lead" back-href="/" back-label="Wróć na stronę główną">
        <template #eyebrow><AlertCircle :size="18" aria-hidden="true" /> Potwierdzenie e-maila</template>
        <div class="auth-recovery-form">
            <template v-if="wrongAccount">
                <button class="auth-recovery-submit" :disabled="logoutPreparing" @click="logout(route('logout'))">
                    {{ logoutPreparing ? 'Sprawdzanie sesji...' : 'Wyloguj się, aby zmienić konto' }}
                </button>
                <p class="link-problem-help">Po zalogowaniu na właściwe konto otwórz ponownie link z wiadomości.</p>
            </template>
            <template v-else-if="unverified">
                <Link :href="route('verification.notice')" class="auth-recovery-submit">Uzyskaj nowy link potwierdzający</Link>
                <p class="link-problem-help">Na stronie potwierdzenia wybierz „Wyślij link ponownie”.</p>
            </template>
            <Link v-else :href="route('dashboard')" class="auth-recovery-submit">Wróć do swojego konta</Link>
            <p v-if="logoutError" class="link-problem-error" role="alert">{{ logoutError }}</p>
        </div>
    </AuthRecoveryShell>
</template>

<style scoped>
.link-problem-help { margin-top: 1rem; color: #687384; font-size: 0.85rem; line-height: 1.6; }
.link-problem-error { margin-top: 1rem; color: #b42318; font-size: 0.85rem; }
</style>
