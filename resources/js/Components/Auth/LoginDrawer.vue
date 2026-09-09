<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { refreshCsrfSession } from '@/lib/csrfSession';
import type { PageProps } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import authScene from '../../../images/home/hero-composite-v3.webp';

const props = withDefaults(
    defineProps<{
        open: boolean;
        canResetPassword?: boolean;
        status?: string;
        retainVisual?: boolean;
        hideVisual?: boolean;
        standalone?: boolean;
    }>(),
    {
        canResetPassword: true,
        status: '',
        retainVisual: false,
        hideVisual: false,
        standalone: false,
    },
);

const emit = defineEmits<{
    close: [];
    openRegister: [];
}>();

const form = useForm({
    _auth_panel: 'login',
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const csrfPreparing = ref(false);
const csrfError = ref<string | null>(null);
let previousBodyOverflow = '';
const page = usePage<PageProps>();

const socialProviderOptions = [
    {
        key: 'google',
        label: 'Zaloguj się z Google',
        mark: 'G',
        markClass: 'text-[#4285f4]',
    },
    {
        key: 'facebook',
        label: 'Zaloguj się z Facebooka',
        mark: 'f',
        markClass: 'text-[#1877f2]',
    },
] as const;

const socialProviders = computed(() => {
    const enabledProviders = new Set(page.props.authDrawers.enabledSocialProviders);

    return socialProviderOptions.filter((provider) =>
        enabledProviders.has(provider.key),
    );
});
const googleProvider = computed(() =>
    socialProviders.value.find((provider) => provider.key === 'google') ?? null,
);
const secondarySocialProviders = computed(() =>
    socialProviders.value.filter((provider) => provider.key !== 'google'),
);

const close = () => {
    emit('close');
};

const openRegister = () => {
    emit('openRegister');
};

const closeOnEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && props.open) {
        close();
    }
};

const submit = async () => {
    if (csrfPreparing.value || form.processing) {
        return;
    }

    csrfPreparing.value = true;
    csrfError.value = null;

    try {
        await refreshCsrfSession();

        form.post(route('login'), {
            onFinish: () => {
                csrfPreparing.value = false;
                form.reset('password');
            },
        });
    } catch {
        csrfPreparing.value = false;
        csrfError.value = 'Nie udało się odświeżyć sesji. Odśwież stronę i spróbuj ponownie.';
    }
};

watch(
    () => props.open,
    (open) => {
        if (typeof document === 'undefined') {
            return;
        }

        if (open) {
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', closeOnEscape);
            return;
        }

        document.body.style.overflow = previousBodyOverflow;
        document.removeEventListener('keydown', closeOnEscape);
    },
    { immediate: true },
);

onUnmounted(() => {
    if (typeof document === 'undefined') {
        return;
    }

    document.body.style.overflow = previousBodyOverflow;
    document.removeEventListener('keydown', closeOnEscape);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open || retainVisual"
            class="auth-dialog-overlay fixed inset-0 z-[90] flex font-[system-ui,-apple-system,'Segoe_UI',Roboto,Helvetica,Arial,sans-serif]"
            :class="{
                'auth-dialog-overlay--retain-visual': retainVisual,
                'auth-dialog-overlay--form-layer': open && hideVisual,
                'auth-dialog-overlay--standalone': standalone,
            }"
            :data-auth-dialog-open="open ? 'true' : undefined"
            @click.self="close"
        >
            <aside
                role="dialog"
                aria-modal="true"
                aria-labelledby="login-drawer-title"
                class="auth-dialog auth-dialog--login"
            >
                <button
                    type="button"
                    aria-label="Zamknij logowanie"
                    class="auth-dialog__close"
                    @click="close"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>

                <figure class="auth-dialog__visual" aria-hidden="true">
                    <img :src="authScene" alt="" class="auth-dialog__visual-image">
                </figure>

                <div class="auth-dialog__panel">
                    <div class="auth-login-drawer-body auth-dialog__content">
                    <header class="auth-dialog__header">
                        <h2
                            id="login-drawer-title"
                            class="auth-login-drawer-title auth-dialog__title"
                        >
                            Zaloguj się i kontynuuj naukę
                        </h2>
                        <p class="auth-login-drawer-lead auth-dialog__lead">
                            Wróć dokładnie do miejsca, w którym skończyłeś.
                        </p>
                        <div class="auth-switcher" role="tablist" aria-label="Wybierz formularz konta">
                            <button
                                type="button"
                                class="auth-switcher__tab is-active"
                                role="tab"
                                aria-selected="true"
                            >
                                Logowanie
                            </button>
                            <button
                                type="button"
                                class="auth-switcher__tab"
                                role="tab"
                                aria-selected="false"
                                @click="openRegister"
                            >
                                Rejestracja
                            </button>
                        </div>
                    </header>

                    <div
                        v-if="status"
                        class="auth-dialog__notice"
                        role="status"
                    >
                        {{ status }}
                    </div>

                    <form class="auth-login-drawer-form auth-dialog__form space-y-4" @submit.prevent="submit">
                        <div
                            v-if="csrfError"
                            class="auth-dialog__error"
                            role="alert"
                        >
                            {{ csrfError }}
                        </div>

                        <div>
                            <label for="drawer-login-email" class="block text-[0.82rem] font-normal text-[#374151]">
                                Adres e-mail
                            </label>
                            <div class="relative mt-2">
                                <input
                                    id="drawer-login-email"
                                    v-model="form.email"
                                    type="email"
                                    data-testid="login-email"
                                    class="auth-login-field block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 pe-11 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                                    placeholder="Wpisz adres e-mail"
                                    required
                                    autocomplete="username"
                                >
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#8b95a1]" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="m3 7 9 6 9-6" />
                                </svg>
                            </div>
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <label for="drawer-login-password" class="block text-[0.82rem] font-normal text-[#374151]">
                                Hasło
                            </label>

                            <div class="relative mt-2">
                                <input
                                    id="drawer-login-password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    data-testid="login-password"
                                    class="auth-login-field block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 pe-11 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                                    placeholder="Wpisz hasło"
                                    required
                                    autocomplete="current-password"
                                >
                                <button
                                    type="button"
                                    :aria-label="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'"
                                    class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#111827] focus:outline-none focus:ring-2 focus:ring-[#111827]/15"
                                    @click="showPassword = !showPassword"
                                >
                                    <svg v-if="!showPassword" aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg v-else aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M3 3l18 18" />
                                        <path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6" />
                                        <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-3.1 3.8" />
                                        <path d="M6.6 6.6A15.7 15.7 0 0 0 2 12s3.5 7 10 7a10.6 10.6 0 0 0 4.2-.9" />
                                    </svg>
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="auth-login-options flex items-center justify-between gap-4">
                            <label class="inline-flex items-center gap-3 text-[0.85rem] font-normal text-[#4b5563]">
                                <input
                                    v-model="form.remember"
                                    name="remember"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-[#cfd6e2] text-[#0a66c2] focus:ring-[#0a66c2]/25"
                                >
                                <span>Zapamiętaj mnie</span>
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-[0.82rem] font-normal text-[#0a66c2] underline decoration-[#0a66c2]/35 underline-offset-2 transition hover:text-[#084f96]"
                            >
                                Nie pamiętasz hasła?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            data-testid="login-submit"
                            class="auth-login-drawer-submit inline-flex h-12 w-full items-center justify-center rounded-[6px] bg-[#0a66c2] px-5 text-[0.95rem] font-medium text-white transition hover:bg-[#084f96] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="csrfPreparing || form.processing"
                        >
                            {{
                                csrfPreparing
                                    ? 'Sprawdzanie sesji...'
                                    : form.processing
                                        ? 'Logowanie...'
                                        : 'Zaloguj się'
                            }}
                        </button>
                    </form>

                    <div v-if="socialProviders.length > 0" class="auth-login-drawer-social auth-dialog__social">
                        <div class="auth-login-drawer-social-grid grid gap-3">
                            <a
                                v-if="googleProvider"
                                :href="route('social.redirect', { provider: 'google' })"
                                class="auth-google-login-button"
                            >
                                <svg aria-hidden="true" viewBox="0 0 48 48" class="h-[1.65rem] w-[1.65rem] flex-none">
                                    <path fill="#EA4335" d="M24 9.5c3.2 0 6.1 1.1 8.4 3.2l6.3-6.3C34.8 2.8 29.7.8 24 .8 14.8.8 6.9 6 3 13.6l7.3 5.7C12.1 13.6 17.5 9.5 24 9.5Z" />
                                    <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.5 2.8-2.2 5.2-4.7 6.8l7.2 5.6c4.2-3.9 7.3-9.6 7.3-16.4Z" />
                                    <path fill="#FBBC05" d="M10.3 28.7A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7L3 13.6A23.1 23.1 0 0 0 .5 24c0 3.7.9 7.3 2.5 10.4l7.3-5.7Z" />
                                    <path fill="#34A853" d="M24 47.2c5.7 0 10.6-1.9 14.1-5.1l-6.1-6.8c-1.7 1.1-4 1.9-8 1.9-6.5 0-11.9-4.1-13.7-9.8L3 33.1c3.9 7.8 11.8 14.1 21 14.1Z" />
                                </svg>
                                <span>Kontynuuj z Google</span>
                            </a>
                            <a
                                v-for="provider in secondarySocialProviders"
                                :key="provider.key"
                                :href="route('social.redirect', { provider: provider.key })"
                                class="auth-login-drawer-social-button inline-flex h-12 w-full items-center justify-center gap-2.5 rounded-[6px] border border-[#d1d7e0] bg-white px-3 text-[0.9rem] font-normal text-[#111827] shadow-[0_5px_16px_rgba(15,23,42,0.03)] transition hover:border-[#aeb8c7] hover:bg-[#f8fafc]"
                            >
                                <span
                                    class="grid h-7 w-7 place-items-center rounded-full bg-white text-[1.2rem] font-medium"
                                    :class="provider.markClass"
                                >
                                    {{ provider.mark }}
                                </span>
                                <span>{{ provider.label }}</span>
                            </a>
                        </div>

                        <div class="auth-dialog__divider">
                            <span>albo</span>
                        </div>
                    </div>

                    <footer class="auth-dialog__footer">
                    <p class="auth-login-drawer-return">
                        Po zalogowaniu wrócisz dokładnie tam, gdzie skończyłeś naukę.
                    </p>
                    <p v-if="googleProvider" class="auth-dialog__legal-consent">
                        Kontynuując z Google, akceptujesz <a :href="route('legal.terms')">Regulamin</a>
                        i potwierdzasz zapoznanie się z <a :href="route('legal.privacy')">Polityką prywatności</a>.
                    </p>
                    </footer>
                    </div>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
