<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { refreshCsrfSession } from '@/lib/csrfSession';
import type { PageProps, StudyContextCategory } from '@/types';
import { trackAnalyticsEvent } from '@/utils/analytics';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff, LockKeyhole, Mail, UserRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import roadSunset from '../../../images/onboarding/road-sunset.jpg';

const props = withDefaults(defineProps<{
    mode: 'login' | 'register';
    canResetPassword?: boolean;
    status?: string | null;
    categories?: StudyContextCategory[];
}>(), {
    canResetPassword: true,
    status: null,
    categories: () => [],
});

const page = usePage<PageProps>();
const isLogin = computed(() => props.mode === 'login');
const categories = computed(() => props.categories.length > 0
    ? props.categories
    : page.props.authDrawers.registrationCategories);
const enabledSocialProviders = computed(() => page.props.authDrawers.enabledSocialProviders);
const googleRegistration = computed(() => page.props.authDrawers.googleIdentityRegistration);
const isGoogleRegistration = computed(() => !isLogin.value && googleRegistration.value?.pending === true);
const registerStep = ref<1 | 2>(1);
const visibleRegisterStep = computed(() => isGoogleRegistration.value ? 2 : registerStep.value);
const pendingSocialProvider = ref<'google' | 'facebook' | null>(null);
const providerError = computed(() => page.props.errors?.provider ?? null);
const csrfPreparing = ref(false);
const csrfError = ref<string | null>(null);
const showPassword = ref(false);
const showConfirmation = ref(false);

const loginForm = useForm({
    _auth_panel: 'login',
    email: '',
    password: '',
    remember: true,
});
const registerForm = useForm({
    _auth_panel: 'register',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    target_category_id: null as number | null,
    preferred_learning_track: 'classic',
});

const advanceRegisterStep = () => {
    const form = document.getElementById('app-register-form') as HTMLFormElement | null;
    if (!form?.reportValidity()) return;

    registerForm.clearErrors('password_confirmation');
    if (registerForm.password !== registerForm.password_confirmation) {
        registerForm.setError('password_confirmation', 'Hasła muszą być takie same.');
        document.getElementById('app-register-confirmation')?.focus();
        return;
    }

    registerStep.value = 2;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const backToAccountDetails = () => {
    pendingSocialProvider.value = null;
    registerStep.value = 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitLogin = async () => {
    if (csrfPreparing.value || loginForm.processing) return;
    csrfPreparing.value = true;
    csrfError.value = null;

    try {
        await refreshCsrfSession();
        loginForm.post(route('login'), {
            onFinish: () => {
                csrfPreparing.value = false;
                loginForm.reset('password');
            },
        });
    } catch {
        csrfPreparing.value = false;
        csrfError.value = 'Nie udało się odświeżyć sesji. Spróbuj ponownie.';
    }
};

const submitRegister = async () => {
    if (visibleRegisterStep.value === 1) {
        advanceRegisterStep();
        return;
    }

    if (!registerForm.target_category_id) {
        registerForm.setError('target_category_id', 'Wybierz kategorię, aby założyć konto.');
        document.querySelector<HTMLButtonElement>('#app-register-category button')?.focus();
        return;
    }

    if (csrfPreparing.value || registerForm.processing || categories.value.length === 0) return;
    csrfPreparing.value = true;
    csrfError.value = null;

    try {
        await refreshCsrfSession();
        registerForm.post(route('register'), {
            onSuccess: () => trackAnalyticsEvent('sign_up', {
                method: isGoogleRegistration.value ? 'google' : 'email',
            }),
            onError: (errors) => {
                if (errors.name || errors.email || errors.password || errors.password_confirmation) {
                    registerStep.value = 1;
                }
            },
            onFinish: () => {
                csrfPreparing.value = false;
                registerForm.reset('password', 'password_confirmation');
            },
        });
    } catch {
        csrfPreparing.value = false;
        csrfError.value = 'Nie udało się odświeżyć sesji. Spróbuj ponownie.';
    }
};

const startSocialRegistration = (provider: 'google' | 'facebook') => {
    pendingSocialProvider.value = provider;
    registerStep.value = 2;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const continueWithSocial = (provider: 'google' | 'facebook') => {
    if (!registerForm.target_category_id) {
        registerForm.setError('target_category_id', 'Najpierw wybierz kategorię prawa jazdy.');
        document.querySelector<HTMLButtonElement>('#app-register-category button')?.focus();
        return;
    }

    window.location.href = route('social.redirect', {
        provider,
        surface: 'app',
        target_category_id: registerForm.target_category_id,
        preferred_learning_track: registerForm.preferred_learning_track,
    });
};
</script>

<template>
    <Head :title="isLogin ? 'Logowanie do aplikacji' : 'Rejestracja w aplikacji'">
        <meta name="robots" content="noindex,nofollow">
    </Head>

    <main class="min-h-[100svh] bg-[#051624] font-[system-ui,-apple-system,'Segoe_UI',Roboto,Helvetica,Arial,sans-serif] text-white">
        <div class="relative isolate mx-auto min-h-[100svh] w-full max-w-[34rem] overflow-hidden bg-[#051624] shadow-[0_0_50px_rgba(0,0,0,0.26)]">
            <img :src="roadSunset" alt="" aria-hidden="true" fetchpriority="high" class="pointer-events-none absolute inset-0 z-0 h-full w-full object-cover object-center">
            <div class="pointer-events-none absolute inset-0 z-0 bg-[linear-gradient(180deg,rgba(3,15,31,0.84)_0%,rgba(3,15,31,0.23)_34%,rgba(3,15,31,0.19)_52%,rgba(3,15,31,0.82)_74%,#031320_100%)]" aria-hidden="true" />

            <div class="relative z-10 flex min-h-[100svh] flex-col px-6 pb-[max(env(safe-area-inset-bottom),1.5rem)] pt-[max(env(safe-area-inset-top),1.5rem)]">
                <header class="flex min-h-11 items-center justify-between gap-3">
                    <button v-if="!isLogin && visibleRegisterStep === 2 && !isGoogleRegistration" type="button" class="grid min-h-11 min-w-11 place-items-center -ml-2 rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#ffd84d]" aria-label="Wróć do danych konta" @click="backToAccountDetails"><ArrowLeft :size="23" aria-hidden="true" /></button>
                    <Link v-else-if="!isLogin" :href="route('app.login')" class="grid min-h-11 min-w-11 place-items-center -ml-2 rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#ffd84d]" aria-label="Wróć do logowania"><ArrowLeft :size="23" aria-hidden="true" /></Link>
                    <div class="flex min-w-0 items-center gap-2" :class="isLogin ? '' : 'mx-auto'" aria-label="PrawkoNaRaz">
                        <svg class="h-9 w-9 shrink-0" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                            <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="3" />
                            <circle cx="20" cy="20" r="3.2" fill="currentColor" />
                            <path d="M5 17.5h30M20 23v12M9.5 29 17 21.5M30.5 29 23 21.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        <span class="truncate text-lg font-bold tracking-[-0.04em]">PrawkoNa<span class="text-[#ffd84d]">Raz</span></span>
                    </div>
                    <Link :href="route('home')" class="inline-flex min-h-11 shrink-0 items-center text-sm font-medium text-white/90 underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#ffd84d]">Pomiń</Link>
                </header>

                <section class="mt-[clamp(2.5rem,8svh,5rem)]" aria-labelledby="app-auth-title">
                    <h1 id="app-auth-title" class="max-w-[23rem] text-[clamp(2rem,7vw,2.8rem)] font-bold leading-[1.06] tracking-[-0.045em]">
                        <template v-if="isLogin">Zdaj prawo jazdy<br>za pierwszym razem</template>
                        <template v-else>{{ visibleRegisterStep === 2 ? 'Wybierz kategorię' : 'Załóż konto' }}</template>
                    </h1>
                    <ul v-if="isLogin" class="mt-5 space-y-2.5 text-[0.95rem] leading-5 text-white/95">
                        <li class="flex items-center gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center text-[#ffd84d]" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="4" stroke="currentColor" stroke-width="2" /><path d="m7.5 12 3.1 3.1 6-6.2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg></span>Aktualne pytania egzaminacyjne</li>
                        <li class="flex items-center gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center text-[#ffd84d]" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 5.5C9.2 3.9 6.5 3.7 3 4.5V20c3.5-.8 6.2-.6 9 1 2.8-1.6 5.5-1.8 9-1V4.5c-3.5-.8-6.2-.6-9 1Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" /><path d="M12 5.5V21" stroke="currentColor" stroke-width="2" /></svg></span>Proste wyjaśnienia</li>
                        <li class="flex items-center gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center text-[#ffd84d]" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 20v-5M9 20V9M14 20v-8M19 20V5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" /></svg></span>Nauka we własnym tempie</li>
                    </ul>
                    <p v-else class="mt-3 max-w-[21rem] text-base leading-6 text-white/95">{{ visibleRegisterStep === 2 ? 'Dopasujemy pytania i testy do Twojego egzaminu.' : 'Dołącz do osób, które uczą się z nami i zdają za pierwszym razem.' }}</p>
                    <p v-if="!isLogin && !isGoogleRegistration" class="mt-3 text-xs font-bold uppercase tracking-[0.16em] text-[#ffd84d]">Krok {{ visibleRegisterStep }} z 2</p>
                </section>

                <div class="mt-auto pt-[clamp(4.5rem,17svh,9rem)]">
                    <div v-if="status" class="mb-3 rounded-xl bg-[#e8fbef] px-4 py-3 text-sm font-medium text-[#155a39]" role="status">{{ status }}</div>
                    <div v-if="providerError || csrfError" class="mb-3 rounded-xl bg-[#fff0ed] px-4 py-3 text-sm font-medium text-[#942f1b]" role="alert">{{ providerError || csrfError }}</div>

                    <form v-if="isLogin" class="space-y-3" @submit.prevent="submitLogin">
                        <div>
                            <label class="sr-only" for="app-login-email">Adres e-mail</label>
                            <div class="relative"><Mail :size="20" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#778391]" aria-hidden="true" /><input id="app-login-email" v-model="loginForm.email" data-testid="app-login-email" type="email" autocomplete="username" required class="h-14 w-full rounded-xl border border-white/80 bg-white px-12 text-base text-[#071b33] outline-none placeholder:text-[#83909d] focus:ring-2 focus:ring-[#ffd84d]" placeholder="Adres e-mail"></div>
                            <InputError class="mt-1 text-[#ffe2dc]" :message="loginForm.errors.email" />
                        </div>
                        <div>
                            <label class="sr-only" for="app-login-password">Hasło</label>
                            <div class="relative"><LockKeyhole :size="20" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#778391]" aria-hidden="true" /><input id="app-login-password" v-model="loginForm.password" data-testid="app-login-password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required class="h-14 w-full rounded-xl border border-white/80 bg-white pl-12 pr-14 text-base text-[#071b33] outline-none placeholder:text-[#83909d] focus:ring-2 focus:ring-[#ffd84d]" placeholder="Hasło"><button type="button" class="absolute inset-y-0 right-1 grid min-w-12 place-items-center text-[#68788a]" :aria-label="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'" @click="showPassword = !showPassword"><EyeOff v-if="showPassword" :size="20" aria-hidden="true" /><Eye v-else :size="20" aria-hidden="true" /></button></div>
                            <InputError class="mt-1 text-[#ffe2dc]" :message="loginForm.errors.password" />
                        </div>
                        <div class="flex min-h-11 items-center justify-between gap-3 text-sm"><label class="inline-flex min-h-11 items-center gap-2.5 text-white/95"><input v-model="loginForm.remember" type="checkbox" class="h-5 w-5 rounded accent-[#ffd84d]">Zapamiętaj mnie</label><Link v-if="canResetPassword" :href="route('password.request')" class="text-right text-white underline underline-offset-2">Nie pamiętasz hasła?</Link></div>
                        <button type="submit" data-testid="app-login-submit" class="flex min-h-14 w-full items-center justify-center gap-4 rounded-xl bg-[#ffd84d] px-5 text-base font-bold text-[#071b33] shadow-[0_10px_25px_rgba(0,0,0,0.23)] transition hover:bg-[#ffe57a] disabled:opacity-60" :disabled="csrfPreparing || loginForm.processing">{{ csrfPreparing ? 'Sprawdzanie sesji...' : loginForm.processing ? 'Logowanie...' : 'Zaloguj się' }} <span v-if="!csrfPreparing && !loginForm.processing" aria-hidden="true">→</span></button>
                    </form>

                    <form v-else id="app-register-form" class="space-y-3" @submit.prevent="submitRegister">
                        <div v-if="isGoogleRegistration && googleRegistration" class="rounded-xl border border-white/25 bg-[#061a2a]/85 px-4 py-3 text-sm"><strong>Konto Google</strong><p class="mt-0.5 text-white/80">{{ googleRegistration.name || googleRegistration.email }}</p></div>
                        <template v-if="visibleRegisterStep === 1">
                            <div><label class="sr-only" for="app-register-name">Imię i nazwisko</label><div class="relative"><UserRound :size="20" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#778391]" aria-hidden="true" /><input id="app-register-name" v-model="registerForm.name" type="text" autocomplete="name" required class="h-14 w-full rounded-xl border border-white/80 bg-white px-12 text-base text-[#071b33] outline-none placeholder:text-[#83909d] focus:ring-2 focus:ring-[#ffd84d]" placeholder="Imię i nazwisko"></div><InputError class="mt-1 text-[#ffe2dc]" :message="registerForm.errors.name" /></div>
                            <div><label class="sr-only" for="app-register-email">Adres e-mail</label><div class="relative"><Mail :size="20" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#778391]" aria-hidden="true" /><input id="app-register-email" v-model="registerForm.email" type="email" autocomplete="username" required class="h-14 w-full rounded-xl border border-white/80 bg-white px-12 text-base text-[#071b33] outline-none placeholder:text-[#83909d] focus:ring-2 focus:ring-[#ffd84d]" placeholder="Adres e-mail"></div><InputError class="mt-1 text-[#ffe2dc]" :message="registerForm.errors.email" /></div>
                            <div><label class="sr-only" for="app-register-password">Hasło</label><div class="relative"><LockKeyhole :size="20" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#778391]" aria-hidden="true" /><input id="app-register-password" v-model="registerForm.password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required class="h-14 w-full rounded-xl border border-white/80 bg-white pl-12 pr-14 text-base text-[#071b33] outline-none placeholder:text-[#83909d] focus:ring-2 focus:ring-[#ffd84d]" placeholder="Hasło"><button type="button" class="absolute inset-y-0 right-1 grid min-w-12 place-items-center text-[#68788a]" :aria-label="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'" @click="showPassword = !showPassword"><EyeOff v-if="showPassword" :size="20" aria-hidden="true" /><Eye v-else :size="20" aria-hidden="true" /></button></div><InputError class="mt-1 text-[#ffe2dc]" :message="registerForm.errors.password" /></div>
                            <div><label class="sr-only" for="app-register-confirmation">Powtórz hasło</label><div class="relative"><LockKeyhole :size="20" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#778391]" aria-hidden="true" /><input id="app-register-confirmation" v-model="registerForm.password_confirmation" :type="showConfirmation ? 'text' : 'password'" autocomplete="new-password" required class="h-14 w-full rounded-xl border border-white/80 bg-white pl-12 pr-14 text-base text-[#071b33] outline-none placeholder:text-[#83909d] focus:ring-2 focus:ring-[#ffd84d]" placeholder="Powtórz hasło"><button type="button" class="absolute inset-y-0 right-1 grid min-w-12 place-items-center text-[#68788a]" :aria-label="showConfirmation ? 'Ukryj powtórzone hasło' : 'Pokaż powtórzone hasło'" @click="showConfirmation = !showConfirmation"><EyeOff v-if="showConfirmation" :size="20" aria-hidden="true" /><Eye v-else :size="20" aria-hidden="true" /></button></div><InputError class="mt-1 text-[#ffe2dc]" :message="registerForm.errors.password_confirmation" /></div>
                        </template>
                        <div v-if="visibleRegisterStep === 2">
                            <p class="mb-3 text-sm leading-5 text-white/85">Który egzamin przygotowujesz?</p>
                            <div id="app-register-category" role="group" aria-label="Kategoria prawa jazdy" class="grid grid-cols-3 gap-2">
                                <button v-for="category in categories" :key="category.id" type="button" :aria-pressed="registerForm.target_category_id === category.id" :aria-label="category.name" class="flex min-h-14 items-center justify-center rounded-xl border px-2 text-center text-lg font-bold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#ffd84d]" :class="registerForm.target_category_id === category.id ? 'border-[#ffd84d] bg-[#ffd84d] text-[#071b33]' : 'border-white/30 bg-[#031320]/70 text-white hover:border-white/65 hover:bg-white/10'" @click="registerForm.target_category_id = category.id; registerForm.clearErrors('target_category_id')">{{ category.code }}</button>
                            </div>
                            <p v-if="categories.length === 0" class="mt-2 text-sm text-[#ffe2dc]">Kategorie są chwilowo niedostępne. Odśwież stronę.</p>
                            <p v-else-if="registerForm.target_category_id" class="mt-3 text-sm text-white/90">{{ categories.find((category) => category.id === registerForm.target_category_id)?.name }}</p>
                            <InputError class="mt-2 text-[#ffe2dc]" :message="registerForm.errors.target_category_id" />
                        </div>
                        <button v-if="visibleRegisterStep === 1" type="button" data-testid="app-register-next" class="flex min-h-14 w-full items-center justify-center gap-4 rounded-xl bg-[#ffd84d] px-5 text-base font-bold text-[#071b33] shadow-[0_10px_25px_rgba(0,0,0,0.23)] transition hover:bg-[#ffe57a]" @click="advanceRegisterStep">Dalej <span aria-hidden="true">→</span></button>
                        <button v-else :type="pendingSocialProvider ? 'button' : 'submit'" data-testid="app-register-submit" class="flex min-h-14 w-full items-center justify-center gap-4 rounded-xl bg-[#ffd84d] px-5 text-base font-bold text-[#071b33] shadow-[0_10px_25px_rgba(0,0,0,0.23)] transition hover:bg-[#ffe57a] disabled:opacity-60" :disabled="csrfPreparing || registerForm.processing || categories.length === 0" @click="pendingSocialProvider ? continueWithSocial(pendingSocialProvider) : undefined">{{ pendingSocialProvider ? `Kontynuuj z ${pendingSocialProvider === 'google' ? 'Google' : 'Facebookiem'}` : csrfPreparing ? 'Sprawdzanie sesji...' : registerForm.processing ? 'Tworzenie konta...' : isGoogleRegistration ? 'Dokończ rejestrację' : 'Załóż konto' }} <span v-if="!csrfPreparing && !registerForm.processing" aria-hidden="true">→</span></button>
                    </form>

                    <div v-if="enabledSocialProviders.length > 0 && (isLogin || visibleRegisterStep === 1)" class="mt-5">
                        <div class="flex items-center gap-4 text-sm text-white/70"><span class="h-px flex-1 bg-white/25" />lub<span class="h-px flex-1 bg-white/25" /></div>
                        <div class="mt-4 grid gap-2" :class="enabledSocialProviders.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                            <template v-for="provider in enabledSocialProviders" :key="provider">
                                <a v-if="isLogin" :href="route('social.redirect', { provider, surface: 'app' })" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/30 bg-[#031320]/45 px-2 text-center text-xs font-semibold text-white transition hover:bg-white/10"><span class="text-base font-black" :class="provider === 'google' ? 'text-[#4285f4]' : 'text-[#70a8ff]'">{{ provider === 'google' ? 'G' : 'f' }}</span>{{ provider === 'google' ? 'Kontynuuj z Google' : 'Kontynuuj z Facebookiem' }}</a>
                                <button v-else type="button" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/30 bg-[#031320]/45 px-2 text-center text-xs font-semibold text-white transition hover:bg-white/10" @click="startSocialRegistration(provider)"><span class="text-base font-black" :class="provider === 'google' ? 'text-[#4285f4]' : 'text-[#70a8ff]'">{{ provider === 'google' ? 'G' : 'f' }}</span>{{ provider === 'google' ? 'Kontynuuj z Google' : 'Kontynuuj z Facebookiem' }}</button>
                            </template>
                        </div>
                    </div>

                    <footer class="mt-6 text-center text-sm text-white/95">
                        <p v-if="isLogin">Nie masz konta? <Link :href="route('app.register')" class="ml-1 font-bold text-[#ffd84d] underline underline-offset-4">Załóż konto</Link></p>
                        <p v-else>Masz już konto? <Link :href="route('app.login')" class="ml-1 font-bold text-[#ffd84d] underline underline-offset-4">Zaloguj się</Link></p>
                        <p v-if="!isLogin && page.props.authDrawers.paymentRequired" class="mt-3">Masz kod od znajomego? <Link :href="route('friend-invitations.code.create')" class="font-semibold text-[#ffd84d] underline">Wpisz kod</Link></p>
                        <p class="mt-4 text-[0.69rem] leading-4 text-white/65">{{ isLogin ? 'Logując się' : 'Zakładając konto' }}, akceptujesz <Link :href="route('legal.terms')" class="underline">regulamin</Link> i <Link :href="route('legal.privacy')" class="underline">politykę prywatności</Link>.</p>
                    </footer>
                </div>
            </div>
        </div>
    </main>
</template>
