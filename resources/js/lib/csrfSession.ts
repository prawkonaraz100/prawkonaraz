export interface CsrfSessionSnapshot {
    token: string;
    authenticated: boolean;
}

type CsrfSessionErrorKind = 'network' | 'invalid-response';

export class CsrfSessionError extends Error {
    kind: CsrfSessionErrorKind;

    constructor(kind: CsrfSessionErrorKind, message: string, options?: ErrorOptions) {
        super(message, options);
        this.name = 'CsrfSessionError';
        this.kind = kind;
    }
}

const CSRF_SESSION_ENDPOINT = '/auth/csrf-token';
const LONG_INACTIVITY_MS = 15 * 60 * 1000;

let refreshPromise: Promise<CsrfSessionSnapshot> | null = null;
let refreshRequired = false;
let hiddenAt: number | null = null;
let lifecycleListenersInstalled = false;

const updateMetaToken = (token: string) => {
    if (typeof document === 'undefined') {
        return;
    }

    document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.setAttribute('content', token);
};

export const updateFormCsrfToken = (
    form: HTMLFormElement,
    token: string,
) => {
    const input = form.querySelector<HTMLInputElement>('input[name="_token"]');

    if (input) {
        input.value = token;
        return;
    }

    const createdInput = document.createElement('input');
    createdInput.type = 'hidden';
    createdInput.name = '_token';
    createdInput.value = token;
    form.prepend(createdInput);
};

const requestCsrfSession = async (): Promise<CsrfSessionSnapshot> => {
    let response: Response;

    try {
        response = await fetch(CSRF_SESSION_ENDPOINT, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
        });
    } catch (error) {
        throw new CsrfSessionError(
            'network',
            'Nie udało się odświeżyć sesji.',
            { cause: error },
        );
    }

    const contentType = response.headers.get('content-type') ?? '';

    if (!response.ok || !contentType.includes('application/json')) {
        throw new CsrfSessionError(
            'invalid-response',
            `Endpoint sesji zwrócił status ${response.status}.`,
        );
    }

    const payload = await response.json().catch((error: unknown) => {
        throw new CsrfSessionError(
            'invalid-response',
            'Endpoint sesji zwrócił nieprawidłową odpowiedź.',
            { cause: error },
        );
    }) as Partial<CsrfSessionSnapshot>;

    if (
        typeof payload.token !== 'string'
        || payload.token === ''
        || typeof payload.authenticated !== 'boolean'
    ) {
        throw new CsrfSessionError(
            'invalid-response',
            'Endpoint sesji nie zwrócił wymaganego kontraktu.',
        );
    }

    updateMetaToken(payload.token);
    refreshRequired = false;

    return {
        token: payload.token,
        authenticated: payload.authenticated,
    };
};

export const refreshCsrfSession = (): Promise<CsrfSessionSnapshot> => {
    if (refreshPromise !== null) {
        return refreshPromise;
    }

    refreshPromise = requestCsrfSession().finally(() => {
        refreshPromise = null;
    });

    return refreshPromise;
};

export const refreshCsrfSessionIfRequired = async () => {
    if (!refreshRequired) {
        return null;
    }

    return refreshCsrfSession();
};

export const markCsrfSessionRefreshRequired = () => {
    refreshRequired = true;
};

export const isCsrfSessionRefreshRequired = () => refreshRequired;

export const setupCsrfSessionLifecycle = () => {
    if (
        lifecycleListenersInstalled
        || typeof window === 'undefined'
        || typeof document === 'undefined'
    ) {
        return;
    }

    lifecycleListenersInstalled = true;

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            markCsrfSessionRefreshRequired();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            hiddenAt = Date.now();
            return;
        }

        if (
            hiddenAt !== null
            && Date.now() - hiddenAt >= LONG_INACTIVITY_MS
        ) {
            markCsrfSessionRefreshRequired();
        }

        hiddenAt = null;
    });
};

export const expiredSessionLoginUrl = () => '/login?session_expired=1';
