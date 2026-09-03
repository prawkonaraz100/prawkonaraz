<script setup lang="ts">
import AuthRecoveryShell from '@/Components/Auth/AuthRecoveryShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, KeyRound } from '@lucide/vue';
import { computed } from 'vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    code: '',
});
const formErrors = computed(
    () => form.errors as Record<string, string | undefined>,
);

const submit = () => {
    form.post(route('friend-invitations.code.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Kod zaproszenia" />

    <AuthRecoveryShell
        title="Odbierz zaproszenie"
        lead="Wpisz kod od znajomego, aby sprawdzić zaproszenie i otrzymać dostęp do nauki."
    >
        <p v-if="status" class="auth-recovery-notice" role="status">
            {{ status }}
        </p>

        <form class="auth-recovery-form" @submit.prevent="submit">
            <div>
                <label for="friend-invitation-code-entry" class="auth-recovery-field-label">
                    Kod zaproszenia
                </label>
                <div class="auth-recovery-field-wrap">
                    <input
                        id="friend-invitation-code-entry"
                        v-model="form.code"
                        type="text"
                        class="auth-recovery-field auth-recovery-field--code"
                        placeholder="Np. AB12CD34"
                        required
                        autocomplete="off"
                        autocapitalize="characters"
                        spellcheck="false"
                    >
                    <KeyRound :size="20" :stroke-width="1.8" aria-hidden="true" class="auth-recovery-field-icon" />
                </div>
                <InputError class="mt-2" :message="form.errors.code || formErrors.invitation" />
            </div>

            <button
                type="submit"
                class="auth-recovery-submit"
                :disabled="form.processing"
            >
                <span>{{ form.processing ? 'Sprawdzanie...' : 'Sprawdź zaproszenie' }}</span>
                <ArrowRight :size="19" :stroke-width="1.8" aria-hidden="true" />
            </button>
        </form>

        <div class="auth-recovery-help">
            <div>
                <p class="auth-recovery-help__title">Masz link od znajomego?</p>
                <p class="auth-recovery-help__text">
                    Otwórz go bezpośrednio, a zaproszenie zostanie rozpoznane automatycznie.
                </p>
            </div>
        </div>

        <template #footer>
            <p>
                Masz już konto?
                <Link :href="route('login')">Przejdź do logowania</Link>
            </p>
        </template>
    </AuthRecoveryShell>
</template>
