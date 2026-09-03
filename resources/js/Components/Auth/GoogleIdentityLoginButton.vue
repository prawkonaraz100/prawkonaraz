<script setup lang="ts">
import { refreshCsrfSession } from '@/lib/csrfSession';
import { loadGoogleIdentityClient } from '@/lib/googleIdentity';
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        open?: boolean;
    }>(),
    {
        open: true,
    },
);

const page = usePage<PageProps>();
const buttonRoot = ref<HTMLElement | null>(null);
const loading = ref(false);
const submitting = ref(false);
const error = ref<string | null>(null);
const rendered = ref(false);

const googleIdentity = computed(() => page.props.googleIdentity);
const enabled = computed(() =>
    googleIdentity.value.enabled
    && typeof googleIdentity.value.clientId === 'string'
    && googleIdentity.value.clientId !== '',
);

const returningUserCookieName = 'prawkonaraz_returning_user';

const hasCookie = (name: string) =>
    document.cookie
        .split(';')
        .some((cookie) => cookie.trim().startsWith(`${name}=`));

const shouldPromptOneTap = () =>
    googleIdentity.value.oneTapEnabled && hasCookie(returningUserCookieName);

const csrfToken = () =>
    document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';

const errorFromPayload = (payload: unknown, fallback: string) => {
    if (!payload || typeof payload !== 'object') {
        return fallback;
    }

    const maybePayload = payload as {
        message?: unknown;
        errors?: Record<string, unknown>;
        error?: {
            message?: unknown;
        };
    };
    const providerError = maybePayload.errors?.provider;
    const credentialError = maybePayload.errors?.credential;
    const firstProviderError = Array.isArray(providerError)
        ? providerError[0]
        : null;
    const firstCredentialError = Array.isArray(credentialError)
        ? credentialError[0]
        : null;

    if (typeof firstProviderError === 'string') {
        return firstProviderError;
    }

    if (typeof firstCredentialError === 'string') {
        return firstCredentialError;
    }

    if (typeof maybePayload.message === 'string') {
        return maybePayload.message;
    }

    if (typeof maybePayload.error?.message === 'string') {
        return maybePayload.error.message;
    }

    return fallback;
};

const submitCredential = async (credential: string) => {
    if (submitting.value) {
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        const session = await refreshCsrfSession();
        const response = await fetch(googleIdentity.value.loginUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken() || session.token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ credential }),
        });
        const payload = await response.json().catch(() => null);

        if (!response.ok) {
            error.value = errorFromPayload(
                payload,
                'Nie udało się zalogować przez Google. Spróbuj ponownie albo użyj e-maila.',
            );
            return;
        }

        const redirect = payload && typeof payload === 'object'
            ? (payload as { redirect?: unknown }).redirect
            : null;

        window.location.assign(
            typeof redirect === 'string' && redirect !== ''
                ? redirect
                : '/dashboard',
        );
    } catch {
        error.value = 'Nie udało się połączyć z Google. Spróbuj ponownie albo użyj e-maila.';
    } finally {
        submitting.value = false;
    }
};

const handleCredential = (response: GoogleIdentityCredentialResponse) => {
    if (!response.credential) {
        error.value = 'Google nie zwrócił danych potrzebnych do logowania.';
        return;
    }

    void submitCredential(response.credential);
};

const initialize = async () => {
    if (!enabled.value || rendered.value || !buttonRoot.value) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        await loadGoogleIdentityClient();
        await nextTick();

        if (!window.google?.accounts?.id || !buttonRoot.value || !googleIdentity.value.clientId) {
            throw new Error('Google Identity API is unavailable.');
        }

        window.google.accounts.id.initialize({
            client_id: googleIdentity.value.clientId,
            callback: handleCredential,
            context: 'signin',
            ux_mode: 'popup',
            cancel_on_tap_outside: true,
        });

        buttonRoot.value.innerHTML = '';
        const buttonWidth = Math.max(
            240,
            Math.min(
                Math.round(buttonRoot.value.getBoundingClientRect().width || buttonRoot.value.clientWidth || 400),
                400,
            ),
        );

        window.google.accounts.id.renderButton(buttonRoot.value, {
            type: 'standard',
            theme: 'outline',
            size: 'large',
            text: 'continue_with',
            shape: 'rectangular',
            logo_alignment: 'left',
            width: buttonWidth,
            locale: 'pl',
        });
        rendered.value = true;

        if (props.open && shouldPromptOneTap()) {
            window.google.accounts.id.prompt();
        }
    } catch {
        error.value = 'Nie udało się załadować logowania Google. Możesz użyć e-maila albo klasycznego przekierowania Google.';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    void initialize();
});

watch(
    () => props.open,
    (open) => {
        if (!open || !enabled.value) {
            return;
        }

        void initialize();

        if (rendered.value && shouldPromptOneTap()) {
            window.google?.accounts?.id.prompt();
        }
    },
);
</script>

<template>
    <div v-if="enabled" class="grid justify-items-center gap-2">
        <div
            ref="buttonRoot"
            class="flex h-12 w-full max-w-[400px] items-center justify-center overflow-visible rounded-[6px]"
            :aria-busy="loading || submitting"
        />
        <p
            v-if="error"
            class="text-center text-sm font-normal leading-5 text-[#b9151c]"
            role="alert"
        >
            {{ error }}
        </p>
    </div>
</template>
