import {
    expiredSessionLoginUrl,
    refreshCsrfSession,
} from '@/lib/csrfSession';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export const useSafeLogout = () => {
    const logoutPreparing = ref(false);
    const logoutError = ref<string | null>(null);

    const logout = async (url: string) => {
        if (logoutPreparing.value) {
            return;
        }

        logoutPreparing.value = true;
        logoutError.value = null;

        try {
            const session = await refreshCsrfSession();

            if (!session.authenticated) {
                window.location.assign(expiredSessionLoginUrl());
                return;
            }

            router.post(url, {}, {
                onError: () => {
                    logoutError.value = 'Nie udało się bezpiecznie wylogować. Spróbuj ponownie.';
                },
                onFinish: () => {
                    logoutPreparing.value = false;
                },
            });
        } catch {
            logoutError.value = 'Nie udało się sprawdzić sesji. Odśwież stronę i spróbuj ponownie.';
            logoutPreparing.value = false;
        }
    };

    return {
        logout,
        logoutError,
        logoutPreparing,
    };
};
