<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { refreshCsrfSession } from '@/lib/csrfSession';
import { trackAnalyticsEvent } from '@/utils/analytics';
import type { PageProps, StudyContextCategory } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import authScene from '../../../images/auth/auth-reference-road-car.png';

const props = withDefaults(
    defineProps<{
        open: boolean;
        categories: StudyContextCategory[];
        retainVisual?: boolean;
        hideVisual?: boolean;
    }>(),
    {
        categories: () => [],
        retainVisual: false,
        hideVisual: false,
    },
);

const emit = defineEmits<{
    close: [];
    openLogin: [];
}>();

const form = useForm({
    _auth_panel: 'register',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    target_category_id: null as number | null,
    preferred_learning_track: 'classic',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const csrfPreparing = ref(false);
const csrfError = ref<string | null>(null);
const categoryDropdownOpen = ref(false);
const categoryDropdown = ref<HTMLElement | null>(null);
const categoryTrigger = ref<HTMLButtonElement | null>(null);
const page = usePage<PageProps>();
const googleIdentityRegistration = computed(
    () => page.props.authDrawers.googleIdentityRegistration,
);
const isGoogleIdentityRegistration = computed(
    () => googleIdentityRegistration.value?.pending === true,
);
const selectedCategory = computed(
    () =>
        props.categories.find(
            (category) => category.id === form.target_category_id,
        ) ?? null,
);
let previousBodyOverflow = '';

const socialProviderOptions = [
    {
        key: 'google',
        label: 'Google',
        mark: 'G',
        markClass: 'text-[#4285f4]',
    },
    {
        key: 'facebook',
        label: 'Facebook',
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

const close = () => {
    emit('close');
};

const selectCategory = (category: StudyContextCategory) => {
    form.target_category_id = category.id;
    categoryDropdownOpen.value = false;
    form.clearErrors('target_category_id');
};

const openLogin = () => {
    emit('openLogin');
};

const drawerEyebrow = computed(() =>
    isGoogleIdentityRegistration.value ? 'Konto Google' : 'Nowe konto',
);

const drawerTitle = computed(() =>
    isGoogleIdentityRegistration.value ? 'Wybierz kategorię' : 'Zarejestruj się',
);

const drawerLead = computed(() =>
    isGoogleIdentityRegistration.value
        ? 'Dokończ rejestrację, wybierając kategorię prawa jazdy i sposób nauki.'
        : 'Wybierz kategorię, załóż konto i potwierdź e-mail.',
);

const submitLabel = computed(() => {
    if (csrfPreparing.value) {
        return 'Sprawdzanie sesji...';
    }

    if (form.processing) {
        return isGoogleIdentityRegistration.value
            ? 'Łączenie konta...'
            : 'Tworzenie konta...';
    }

    return isGoogleIdentityRegistration.value
        ? 'Dokończ rejestrację'
        : 'Załóż konto';
});

const closeOnEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && props.open) {
        if (categoryDropdownOpen.value) {
            categoryDropdownOpen.value = false;
            return;
        }

        close();
    }
};

const closeCategoryDropdownOnOutsideClick = (event: MouseEvent) => {
    if (
        categoryDropdownOpen.value &&
        event.target instanceof Node &&
        !categoryDropdown.value?.contains(event.target)
    ) {
        categoryDropdownOpen.value = false;
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

        form.post(route('register'), {
            onSuccess: () => {
                trackAnalyticsEvent('sign_up', {
                    method: isGoogleIdentityRegistration.value ? 'google' : 'email',
                });
            },
            onFinish: () => {
                csrfPreparing.value = false;
                form.reset('password', 'password_confirmation');
            },
        });
    } catch {
        csrfPreparing.value = false;
        csrfError.value = 'Nie udało się odświeżyć sesji. Odśwież stronę i spróbuj ponownie.';
    }
};

const continueWithSocial = (provider: 'google' | 'facebook') => {
    if (!form.target_category_id) {
        form.setError(
            'target_category_id',
            'Wybierz kategorię przed rejestracją społecznościową.',
        );
        categoryTrigger.value?.focus();

        return;
    }

    window.location.href = route('social.redirect', {
        provider,
        target_category_id: form.target_category_id,
        preferred_learning_track: form.preferred_learning_track,
    });
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
            document.addEventListener('click', closeCategoryDropdownOnOutsideClick);
            return;
        }

        categoryDropdownOpen.value = false;
        document.body.style.overflow = previousBodyOverflow;
        document.removeEventListener('keydown', closeOnEscape);
        document.removeEventListener('click', closeCategoryDropdownOnOutsideClick);
    },
    { immediate: true },
);

onUnmounted(() => {
    if (typeof document === 'undefined') {
        return;
    }

    document.body.style.overflow = previousBodyOverflow;
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('click', closeCategoryDropdownOnOutsideClick);
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
            }"
            :data-auth-dialog-open="open ? 'true' : undefined"
            @click.self="close"
        >
            <aside
                role="dialog"
                aria-modal="true"
                aria-labelledby="register-drawer-title"
                class="auth-dialog auth-dialog--register"
            >
                <button
                    type="button"
                    aria-label="Zamknij rejestrację"
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
                    <div class="auth-register-drawer-body auth-dialog__content auth-dialog__content--register">
                        <header class="auth-register-drawer-header auth-dialog__header">
                            <p
                                v-if="isGoogleIdentityRegistration"
                                class="auth-register-drawer-eyebrow auth-dialog__eyebrow"
                            >
                                {{ drawerEyebrow }}
                            </p>
                            <h2
                                id="register-drawer-title"
                                class="auth-register-drawer-title auth-dialog__title"
                            >
                                {{ drawerTitle }}
                            </h2>
                            <p class="auth-register-drawer-lead auth-dialog__lead">
                                {{ drawerLead }}
                            </p>
                            <div class="auth-switcher" role="tablist" aria-label="Wybierz formularz konta">
                                <button
                                    type="button"
                                    class="auth-switcher__tab"
                                    role="tab"
                                    aria-selected="false"
                                    @click="openLogin"
                                >
                                    Logowanie
                                </button>
                                <button
                                    type="button"
                                    class="auth-switcher__tab is-active"
                                    role="tab"
                                    aria-selected="true"
                                >
                                    Rejestracja
                                </button>
                            </div>
                    </header>

                    <form class="auth-register-drawer-form auth-dialog__form space-y-4" @submit.prevent="submit">
                        <div
                            v-if="csrfError"
                            class="auth-dialog__error"
                            role="alert"
                        >
                            {{ csrfError }}
                        </div>

                        <p
                            v-if="socialProviders.some((provider) => provider.key === 'google')"
                            class="auth-dialog__legal-consent mt-5"
                        >
                            <span>Kontynuując z Google, akceptujesz <a :href="route('legal.terms')">Regulamin</a> i potwierdzasz</span>
                            <span>zapoznanie się z <a :href="route('legal.privacy')">Polityką prywatności</a>.</span>
                        </p>

                        <div
                            v-if="isGoogleIdentityRegistration && googleIdentityRegistration"
                            class="flex items-center gap-3 rounded-[6px] border border-[#d8dee8] bg-[#f8fafc] px-3 py-3"
                        >
                            <img
                                v-if="googleIdentityRegistration.avatarUrl"
                                :src="googleIdentityRegistration.avatarUrl"
                                alt=""
                                class="h-10 w-10 rounded-full"
                            >
                            <div
                                v-else
                                class="grid h-10 w-10 place-items-center rounded-full bg-white text-[1.1rem] font-medium text-[#4285f4]"
                                aria-hidden="true"
                            >
                                G
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-[0.9rem] font-medium text-[#111827]">
                                    {{ googleIdentityRegistration.name || 'Konto Google' }}
                                </p>
                                <p class="truncate text-[0.82rem] font-normal text-[#4b5563]">
                                    {{ googleIdentityRegistration.email }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p class="auth-register-section-label block text-[0.82rem] font-medium text-[#374151]">
                                Kategoria prawa jazdy
                            </p>
                            <div ref="categoryDropdown" class="relative mt-2">
                                <button
                                    ref="categoryTrigger"
                                    type="button"
                                    class="auth-register-category-trigger flex h-12 w-full items-center justify-between rounded-[6px] border border-[#d8dee8] bg-white px-4 text-left text-[0.95rem] font-medium text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition hover:border-[#c6ceda] focus:outline-none disabled:cursor-not-allowed disabled:bg-[#f8fafc] disabled:text-[#8b95a1]"
                                    :class="categoryDropdownOpen ? 'border-[#9aa3af] ring-3 ring-slate-200' : ''"
                                    aria-haspopup="listbox"
                                    :aria-expanded="categoryDropdownOpen"
                                    :disabled="categories.length === 0"
                                    @click="categoryDropdownOpen = !categoryDropdownOpen"
                                >
                                    <span :class="selectedCategory ? 'text-[#111827]' : 'text-[#9aa3af]'">
                                        {{ selectedCategory?.name ?? 'Wybierz kategorię' }}
                                    </span>
                                    <svg
                                        aria-hidden="true"
                                        viewBox="0 0 20 20"
                                        class="h-4 w-4 text-[#6b7280] transition"
                                        :class="categoryDropdownOpen ? 'rotate-180 text-[#4b5563]' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="m5 7 5 5 5-5" />
                                    </svg>
                                </button>

                                <div
                                    v-if="categoryDropdownOpen && categories.length > 0"
                                    role="listbox"
                                    class="absolute left-0 right-0 top-full z-30 mt-2 max-h-[15rem] overflow-y-auto rounded-[6px] border border-[#d8dee8] bg-white p-1.5 shadow-[0_18px_42px_rgba(15,23,42,0.16)]"
                                >
                                    <button
                                        v-for="category in categories"
                                        :key="category.id"
                                        type="button"
                                        role="option"
                                        :aria-selected="form.target_category_id === category.id"
                                        class="flex min-h-10 w-full items-center rounded-[5px] px-3 text-left text-[0.9rem] font-medium transition focus:outline-none focus:ring-2 focus:ring-[#111827]/15"
                                        :class="
                                            form.target_category_id === category.id
                                                ? 'bg-[#d01921] text-white'
                                                : 'bg-white text-[#374151] hover:bg-[#fff5f5] hover:text-[#111827]'
                                        "
                                        @click="selectCategory(category)"
                                    >
                                        {{ category.name }}
                                    </button>
                                </div>
                            </div>
                            <p
                                v-if="categories.length === 0"
                                class="mt-2 text-sm font-semibold text-[#d01921]"
                            >
                                Kategorie nie są teraz dostępne. Spróbuj odświeżyć stronę.
                            </p>
                            <InputError class="mt-2" :message="form.errors.target_category_id" />
                        </div>

                        <div
                            v-if="!isGoogleIdentityRegistration"
                            class="auth-register-field-grid grid gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label for="drawer-register-name" class="auth-register-field-label block text-[0.82rem] font-medium text-[#374151]">
                                    Imię i nazwisko
                                </label>
                                <input
                                    id="drawer-register-name"
                                    v-model="form.name"
                                    type="text"
                                    class="auth-register-field mt-2 block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                                    placeholder="Wpisz imię"
                                    required
                                    autocomplete="name"
                                >
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <label for="drawer-register-email" class="auth-register-field-label block text-[0.82rem] font-medium text-[#374151]">
                                    Adres e-mail
                                </label>
                                <input
                                    id="drawer-register-email"
                                    v-model="form.email"
                                    type="email"
                                    class="auth-register-field mt-2 block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                                    placeholder="Wpisz e-mail"
                                    required
                                    autocomplete="username"
                                >
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>
                        </div>

                        <div
                            v-if="!isGoogleIdentityRegistration"
                            class="auth-register-field-grid grid gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label for="drawer-register-password" class="auth-register-field-label block text-[0.82rem] font-medium text-[#374151]">
                                    Hasło
                                </label>
                                <div class="relative mt-2">
                                    <input
                                        id="drawer-register-password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="auth-register-field block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 pe-11 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                                        placeholder="Wpisz hasło"
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button
                                        type="button"
                                        :aria-label="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'"
                                        class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#111827] focus:outline-none focus:ring-2 focus:ring-[#111827]/15"
                                        @click="showPassword = !showPassword"
                                    >
                                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                </div>
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <div>
                                <label for="drawer-register-password-confirmation" class="auth-register-field-label block text-[0.82rem] font-medium text-[#374151]">
                                    Powtórz hasło
                                </label>
                                <div class="relative mt-2">
                                    <input
                                        id="drawer-register-password-confirmation"
                                        v-model="form.password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        class="auth-register-field block h-12 w-full rounded-[6px] border border-[#d8dee8] bg-white px-4 pe-11 text-[0.95rem] font-normal text-[#111827] shadow-[0_4px_16px_rgba(15,23,42,0.04)] transition placeholder:text-[#9aa3af] focus:outline-none"
                                        placeholder="Powtórz hasło"
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button
                                        type="button"
                                        :aria-label="showPasswordConfirmation ? 'Ukryj powtórzone hasło' : 'Pokaż powtórzone hasło'"
                                        class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-[#8b95a1] transition hover:bg-[#f3f4f6] hover:text-[#111827] focus:outline-none focus:ring-2 focus:ring-[#111827]/15"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    >
                                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                </div>
                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="auth-register-submit inline-flex h-12 w-full items-center justify-center rounded-[6px] bg-[#d01921] px-5 text-[0.95rem] font-medium text-white transition hover:bg-[#b9151c] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="csrfPreparing || form.processing || categories.length === 0"
                        >
                            {{ submitLabel }}
                        </button>
                    </form>

                    <div v-if="socialProviders.length > 0 && !isGoogleIdentityRegistration" class="auth-register-social mt-6">
                        <div class="flex items-center gap-4">
                            <div class="h-px flex-1 bg-[#e2e7ee]" />
                            <span class="text-[0.82rem] font-normal text-[#8b95a1]">Szybki start</span>
                            <div class="h-px flex-1 bg-[#e2e7ee]" />
                        </div>

                        <div
                            class="auth-register-social-grid mt-5 grid gap-3"
                            :class="socialProviders.length > 1 ? 'sm:grid-cols-2' : ''"
                        >
                            <button
                                v-for="provider in socialProviders"
                                :key="provider.key"
                                type="button"
                                :class="provider.key === 'google'
                                    ? 'auth-google-login-button sm:col-span-2'
                                    : 'auth-register-social-button inline-flex h-12 w-full items-center justify-center gap-2.5 rounded-[6px] border border-[#d1d7e0] bg-white px-3 text-[0.9rem] font-normal text-[#111827] shadow-[0_5px_16px_rgba(15,23,42,0.03)] transition hover:border-[#aeb8c7] hover:bg-[#f8fafc]'"
                                @click="continueWithSocial(provider.key)"
                            >
                                <svg
                                    v-if="provider.key === 'google'"
                                    aria-hidden="true"
                                    viewBox="0 0 48 48"
                                    class="h-[1.65rem] w-[1.65rem] flex-none"
                                >
                                    <path fill="#EA4335" d="M24 9.5c3.2 0 6.1 1.1 8.4 3.2l6.3-6.3C34.8 2.8 29.7.8 24 .8 14.8.8 6.9 6 3 13.6l7.3 5.7C12.1 13.6 17.5 9.5 24 9.5Z" />
                                    <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.5 2.8-2.2 5.2-4.7 6.8l7.2 5.6c4.2-3.9 7.3-9.6 7.3-16.4Z" />
                                    <path fill="#FBBC05" d="M10.3 28.7A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7L3 13.6A23.1 23.1 0 0 0 .5 24c0 3.7.9 7.3 2.5 10.4l7.3-5.7Z" />
                                    <path fill="#34A853" d="M24 47.2c5.7 0 10.6-1.9 14.1-5.1l-6.1-6.8c-1.7 1.1-4 1.9-8 1.9-6.5 0-11.9-4.1-13.7-9.8L3 33.1c3.9 7.8 11.8 14.1 21 14.1Z" />
                                </svg>
                                <span
                                    v-else
                                    class="grid h-8 w-8 place-items-center rounded-full bg-white text-[1.25rem] font-medium"
                                    :class="provider.markClass"
                                >
                                    {{ provider.mark }}
                                </span>
                                <span class="text-center">{{ provider.key === 'google' ? 'Kontynuuj z Google' : provider.label }}</span>
                                <span v-if="provider.key !== 'google'" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>

                    <footer class="auth-dialog__footer">
                    <p class="auth-register-login">
                        Masz już konto?
                        <button
                            type="button"
                            class="font-normal text-[#d01921] underline decoration-[#d01921]/35 underline-offset-2 transition hover:text-[#a80f16]"
                            @click="openLogin"
                        >
                            Zaloguj się
                        </button>
                    </p>
                    <p class="auth-register-invite">
                        Masz kod od znajomego?
                        <Link
                            :href="route('friend-invitations.code.create')"
                            class="font-normal text-[#0d47a1] underline decoration-[#0d47a1]/35 underline-offset-2 transition hover:text-[#083777]"
                        >
                            Wpisz kod zaproszenia
                        </Link>
                    </p>
                    </footer>
                    </div>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
