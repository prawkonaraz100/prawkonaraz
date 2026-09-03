import { isAuthenticationExpiredError } from '@/lib/apiClient';
import { ref } from 'vue';

export const useSessionExpiry = (onExpired?: () => void) => {
    const sessionExpired = ref(false);

    const expireSession = () => {
        if (sessionExpired.value) {
            return;
        }

        sessionExpired.value = true;
        onExpired?.();
    };

    const handleSessionExpiryError = (error: unknown) => {
        if (!isAuthenticationExpiredError(error)) {
            return false;
        }

        expireSession();

        return true;
    };

    return {
        expireSession,
        handleSessionExpiryError,
        sessionExpired,
    };
};
