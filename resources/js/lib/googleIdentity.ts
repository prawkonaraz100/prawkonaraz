const GOOGLE_IDENTITY_SCRIPT_ID = 'google-identity-services-client';
const GOOGLE_IDENTITY_SCRIPT_SRC = 'https://accounts.google.com/gsi/client?hl=pl';

let googleIdentityLoadPromise: Promise<void> | null = null;

const googleIdentityReady = () =>
    typeof window !== 'undefined'
    && Boolean(window.google?.accounts?.id);

export const loadGoogleIdentityClient = (): Promise<void> => {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return Promise.reject(new Error('Google Identity can only load in a browser.'));
    }

    if (googleIdentityReady()) {
        return Promise.resolve();
    }

    if (googleIdentityLoadPromise !== null) {
        return googleIdentityLoadPromise;
    }

    const loadPromise = new Promise<void>((resolve, reject) => {
        const existingScript = document.getElementById(
            GOOGLE_IDENTITY_SCRIPT_ID,
        ) as HTMLScriptElement | null;

        const finish = () => {
            if (googleIdentityReady()) {
                resolve();
                return;
            }

            reject(new Error('Google Identity script loaded without API.'));
        };

        if (existingScript) {
            existingScript.addEventListener('load', finish, { once: true });
            existingScript.addEventListener('error', () => {
                reject(new Error('Google Identity script failed to load.'));
            }, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.id = GOOGLE_IDENTITY_SCRIPT_ID;
        script.src = GOOGLE_IDENTITY_SCRIPT_SRC;
        script.async = true;
        script.defer = true;
        script.addEventListener('load', finish, { once: true });
        script.addEventListener('error', () => {
            reject(new Error('Google Identity script failed to load.'));
        }, { once: true });

        document.head.appendChild(script);
    }).finally(() => {
        googleIdentityLoadPromise = null;
    });

    googleIdentityLoadPromise = loadPromise;

    return loadPromise;
};
