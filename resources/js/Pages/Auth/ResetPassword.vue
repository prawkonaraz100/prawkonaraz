<script setup lang="ts">
import AuthRecoveryShell from '@/Components/Auth/AuthRecoveryShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, KeyRound, LockKeyhole, Mail } from '@lucide/vue';
import { ref } from 'vue';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Ustaw nowe hasło" />

    <AuthRecoveryShell
        title="Ustaw nowe hasło"
        lead="Wybierz nowe hasło do swojego konta. Po zapisaniu od razu wrócisz do logowania."
    >
        <template #eyebrow>
            <KeyRound :size="17" :stroke-width="2" aria-hidden="true" />
            Bezpieczne konto
        </template>

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
                        required
                        autofocus
                        autocomplete="username"
                    >
                    <Mail :size="20" :stroke-width="1.8" aria-hidden="true" class="auth-recovery-field-icon" />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="auth-recovery-field-label">
                    Nowe hasło
                </label>
                <div class="auth-recovery-field-wrap">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="auth-recovery-field"
                        placeholder="Wpisz nowe hasło"
                        required
                        autocomplete="new-password"
                    >
                    <button
                        type="button"
                        class="auth-recovery-field-action"
                        :aria-label="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" :size="20" :stroke-width="1.8" aria-hidden="true" />
                        <Eye v-else :size="20" :stroke-width="1.8" aria-hidden="true" />
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation" class="auth-recovery-field-label">
                    Powtórz nowe hasło
                </label>
                <div class="auth-recovery-field-wrap">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        class="auth-recovery-field"
                        placeholder="Powtórz nowe hasło"
                        required
                        autocomplete="new-password"
                    >
                    <button
                        type="button"
                        class="auth-recovery-field-action"
                        :aria-label="showPasswordConfirmation ? 'Ukryj hasło' : 'Pokaż hasło'"
                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                    >
                        <EyeOff v-if="showPasswordConfirmation" :size="20" :stroke-width="1.8" aria-hidden="true" />
                        <Eye v-else :size="20" :stroke-width="1.8" aria-hidden="true" />
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <button
                type="submit"
                class="auth-recovery-submit"
                :disabled="form.processing"
            >
                <span>{{ form.processing ? 'Zapisywanie...' : 'Ustaw nowe hasło' }}</span>
                <LockKeyhole :size="19" :stroke-width="1.8" aria-hidden="true" />
            </button>
        </form>

        <template #footer>
            <p>
                Pamiętasz aktualne hasło?
                <Link :href="route('login')">Wróć do logowania</Link>
            </p>
        </template>
    </AuthRecoveryShell>
</template>
