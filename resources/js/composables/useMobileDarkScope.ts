import { onBeforeUnmount, onMounted } from 'vue';

const SCOPE_CLASS = 'pk-dark-scope';
let activeScopes = 0;

/**
 * Puts the whole page (including teleported sheets and dialogs) under the dark mobile theme
 * (.pk-dark-scope in app.css, active only below 768 px) while the calling component is mounted.
 */
export function useMobileDarkScope(): void {
    onMounted(() => {
        activeScopes += 1;
        document.body.classList.add(SCOPE_CLASS);
    });

    onBeforeUnmount(() => {
        activeScopes = Math.max(activeScopes - 1, 0);
        if (activeScopes === 0) {
            document.body.classList.remove(SCOPE_CLASS);
        }
    });
}
