<script setup lang="ts">
import AuthRecoveryShell from '@/Components/Auth/AuthRecoveryShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Mail, Send } from '@lucide/vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Odzyskaj dostęp" />

    <AuthRecoveryShell
        title="Odzyskaj dostęp"
        lead="Podaj adres e-mail użyty przy rejestracji. Wyślemy Ci link do ustawienia nowego hasła."
    >
        <div v-if="status" class="auth-recovery-notice" role="status">
            <CheckCircle2 :size="22" :stroke-width="2.1" aria-hidden="true" />
            <div>
                <strong>Link został wysłany</strong>
                <p>{{ status }}</p>
            </div>
        </div>

        <form class="auth-recovery-form" @submit.prevent="submit">
            <div>
                <label for="email" class="auth-recovery-field-label">
                    Adres e-mail
                </label>
                <div class="auth-recovery-field-wrap">
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="auth-recovery-field"
                        placeholder="Wpisz adres e-mail"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    <Mail :size="20" :stroke-width="1.8" aria-hidden="true" class="auth-recovery-field-icon" />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <button
                type="submit"
                class="auth-recovery-submit"
                :disabled="form.processing"
            >
                <span>{{ form.processing ? 'Wysyłanie...' : 'Wyślij link do resetu' }}</span>
                <Send :size="19" :stroke-width="1.8" aria-hidden="true" />
            </button>
        </form>

        <template #footer>
            <p>
                Pamiętasz hasło?
                <Link :href="route('login')">Zaloguj się</Link>
            </p>
        </template>
    </AuthRecoveryShell>
</template>
