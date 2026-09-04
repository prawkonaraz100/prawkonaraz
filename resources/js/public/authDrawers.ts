import '../../images/auth/login-panel-illustration.png';
import { setupCsrfRefreshForms } from '../lib/csrfForms';
import { refreshCsrfSession } from '../lib/csrfSession';
import { loadGoogleIdentityClient } from '../lib/googleIdentity';
import type { AuthDrawerName } from './authDrawerLoader';

interface GoogleIdentityConfig {
    clientId: string;
    loginUrl: string;
    oneTapEnabled: boolean;
}

const drawerSelector = (name: AuthDrawerName) =>
    name === 'login' ? '[data-login-drawer]' : '[data-register-drawer]';

const openStateKey = (name: AuthDrawerName) =>
    name === 'login' ? 'loginDrawerOpen' : 'registerDrawerOpen';

const csrfToken = () => document
    .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
    ?.content ?? '';

const googleIdentityErrorFromPayload = (payload: unknown, fallback: string) => {
    if (!payload || typeof payload !== 'object') {
        return fallback;
    }

    const maybePayload = payload as {
        message?: unknown;
        errors?: Record<string, unknown>;
        error?: { message?: unknown };
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

export const createPublicAuthDrawers = (root: HTMLElement) => {
    const initializedDrawers = new WeakSet<HTMLElement>();
    const renderedGoogleButtons = new WeakSet<HTMLElement>();
    const drawerPromises = new Map<AuthDrawerName, Promise<HTMLElement>>();
    const googleErrors = new Set<HTMLElement>();
    let activeDrawer: AuthDrawerName | null = null;
    let visualHostDrawer: AuthDrawerName | null = null;
    let requestedDrawer: AuthDrawerName | null = null;
    let previousBodyOverflow = '';
    let googleInitializationPromise: Promise<void> | null = null;
    let googleSubmitting = false;
    const oneTapHost = root.querySelector<HTMLElement>('[data-google-one-tap-host]');

    const setOneTapVisible = (visible: boolean) => {
        if (!oneTapHost) {
            return;
        }

        oneTapHost.classList.toggle('is-visible', visible);
        oneTapHost.setAttribute('aria-hidden', String(!visible));
    };

    const closeOneTap = () => {
        window.google?.accounts?.id.cancel();
        setOneTapVisible(false);
    };

    root.querySelector<HTMLButtonElement>('[data-google-one-tap-close]')
        ?.addEventListener('click', closeOneTap);

    const getDrawer = (name: AuthDrawerName) =>
        root.querySelector<HTMLElement>(drawerSelector(name));

    const getDrawerEntries = (): Array<[AuthDrawerName, HTMLElement]> => {
        const entries: Array<[AuthDrawerName, HTMLElement]> = [];
        const login = getDrawer('login');
        const register = getDrawer('register');

        if (login) {
            entries.push(['login', login]);
        }

        if (register) {
            entries.push(['register', register]);
        }

        return entries;
    };

    const resetRegisterCategoryDropdown = (drawer: HTMLElement) => {
        const list = drawer.querySelector<HTMLElement>('[data-register-category-list]');
        const trigger = drawer.querySelector<HTMLButtonElement>(
            '[data-register-category-trigger]',
        );
        const chevron = drawer.querySelector<HTMLElement>(
            '[data-register-category-chevron]',
        );

        list?.classList.add('hidden');
        trigger?.setAttribute('aria-expanded', 'false');
        trigger?.classList.remove('border-[#9aa3af]', 'ring-3', 'ring-slate-200');
        chevron?.classList.remove('rotate-180', 'text-[#4b5563]');
    };

    const setDrawerDisplay = (
        name: AuthDrawerName,
        drawer: HTMLElement,
        open: boolean,
        retainVisual = false,
    ) => {
        const displayed = open || retainVisual;

        drawer.classList.toggle('hidden', !displayed);
        drawer.classList.toggle('flex', displayed);
        drawer.classList.toggle('auth-dialog-overlay--retain-visual', retainVisual);
        drawer.classList.toggle(
            'auth-dialog-overlay--form-layer',
            open && visualHostDrawer !== null && name !== visualHostDrawer,
        );
        drawer.setAttribute('aria-hidden', String(!open));
        drawer.dataset[openStateKey(name)] = String(open);

        if (name === 'register' && !open) {
            resetRegisterCategoryDropdown(drawer);
        }
    };

    const anyDrawerOpen = () => getDrawerEntries().some(
        ([name, drawer]) => drawer.dataset[openStateKey(name)] === 'true',
    );

    const closeDrawer = (name: AuthDrawerName) => {
        const drawer = getDrawer(name);

        if (!drawer) {
            return;
        }

        const closesActiveDrawer = activeDrawer === name;

        setDrawerDisplay(name, drawer, false);

        if (closesActiveDrawer) {
            getDrawerEntries()
                .filter(([entryName]) => entryName !== name)
                .forEach(([entryName, entryDrawer]) => {
                    setDrawerDisplay(entryName, entryDrawer, false);
                });
            activeDrawer = null;
            visualHostDrawer = null;
        }

        if (!anyDrawerOpen()) {
            document.body.style.overflow = previousBodyOverflow;
        }
    };

    const closeActiveDrawer = () => {
        if (activeDrawer !== null) {
            closeDrawer(activeDrawer);
            return;
        }

        getDrawerEntries().forEach(([name]) => closeDrawer(name));
    };

    const googleConfigFromRoot = (): GoogleIdentityConfig | null => {
        const clientId = root.dataset.googleClientId ?? '';
        const loginUrl = root.dataset.googleLoginUrl ?? '';

        if (
            root.dataset.googleIdentityEnabled !== 'true'
            || clientId === ''
            || loginUrl === ''
        ) {
            return null;
        }

        return {
            clientId,
            loginUrl,
            oneTapEnabled: root.dataset.googleOneTapEnabled === 'true',
        };
    };

    const googleConfigFromDrawer = (drawer: HTMLElement): GoogleIdentityConfig | null => {
        const googleRoot = drawer.querySelector<HTMLElement>('[data-google-identity-login]');
        const clientId = googleRoot?.dataset.googleClientId ?? '';
        const loginUrl = googleRoot?.dataset.googleLoginUrl ?? '';

        if (!googleRoot || clientId === '' || loginUrl === '') {
            return null;
        }

        return {
            clientId,
            loginUrl,
            oneTapEnabled: googleRoot.dataset.googleOneTapEnabled === 'true',
        };
    };

    const setGoogleError = (message: string | null) => {
        googleErrors.forEach((error) => {
            error.textContent = message ?? '';
            error.classList.toggle('hidden', message === null);
        });
    };

    const submitGoogleCredential = async (
        credential: string,
        config: GoogleIdentityConfig,
    ) => {
        if (googleSubmitting) {
            return;
        }

        googleSubmitting = true;
        setGoogleError(null);
        setOneTapVisible(false);

        try {
            const session = await refreshCsrfSession();
            const response = await fetch(config.loginUrl, {
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
                setGoogleError(googleIdentityErrorFromPayload(
                    payload,
                    'Nie udało się zalogować przez Google. Spróbuj ponownie albo użyj e-maila.',
                ));
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
            setGoogleError('Nie udało się połączyć z Google. Spróbuj ponownie albo użyj e-maila.');
        } finally {
            googleSubmitting = false;
        }
    };

    const ensureGoogleInitialized = (config: GoogleIdentityConfig) => {
        if (googleInitializationPromise === null) {
            googleInitializationPromise = loadGoogleIdentityClient()
                .then(() => {
                    if (!window.google?.accounts?.id) {
                        throw new Error('Google Identity API is unavailable.');
                    }

                    window.google.accounts.id.initialize({
                        client_id: config.clientId,
                        callback: (response) => {
                            if (!response.credential) {
                                setGoogleError('Google nie zwrócił danych potrzebnych do logowania.');
                                return;
                            }

                            void submitGoogleCredential(response.credential, config);
                        },
                        context: 'signin',
                        ux_mode: 'popup',
                        cancel_on_tap_outside: true,
                    });
                })
                .catch((error: unknown) => {
                    googleInitializationPromise = null;
                    throw error;
                });
        }

        return googleInitializationPromise;
    };

    const renderGoogleButton = (
        buttonRoot: HTMLElement,
        theme: 'outline' | 'filled_black' = 'outline',
    ) => {
        if (renderedGoogleButtons.has(buttonRoot)) {
            return;
        }

        window.google?.accounts?.id.renderButton(buttonRoot, {
            type: 'standard',
            theme,
            size: 'large',
            text: 'continue_with',
            shape: 'rectangular',
            logo_alignment: 'left',
            width: Math.max(
                240,
                Math.min(
                    Math.round(
                        buttonRoot.getBoundingClientRect().width
                        || buttonRoot.clientWidth
                        || 400,
                    ),
                    400,
                ),
            ),
            locale: 'pl',
        });
        renderedGoogleButtons.add(buttonRoot);
    };

    const promptOneTap = async (config: GoogleIdentityConfig) => {
        if (!config.oneTapEnabled) {
            return;
        }

        await ensureGoogleInitialized(config);
        setOneTapVisible(true);
        const fallbackButton = root.querySelector<HTMLElement>('[data-google-one-tap-button]');

        if (fallbackButton) {
            renderGoogleButton(fallbackButton, 'filled_black');
        }
    };

    const setupGoogleButton = async (drawer: HTMLElement) => {
        const googleRoot = drawer.querySelector<HTMLElement>('[data-google-identity-login]');
        const buttonRoot = googleRoot?.querySelector<HTMLElement>(
            '[data-google-identity-button]',
        );
        const error = googleRoot?.querySelector<HTMLElement>(
            '[data-google-identity-error]',
        );
        const config = googleConfigFromDrawer(drawer) ?? googleConfigFromRoot();

        if (!googleRoot || !buttonRoot || !config) {
            return;
        }

        if (error) {
            googleErrors.add(error);
        }

        try {
            await ensureGoogleInitialized(config);

            renderGoogleButton(buttonRoot);

        } catch {
            setGoogleError('Nie udało się załadować logowania Google. Możesz użyć e-maila albo klasycznego przekierowania Google.');
        }
    };

    const initializeLoginDrawer = (drawer: HTMLElement) => {
        const input = drawer.querySelector<HTMLInputElement>('[data-login-password]');
        const toggle = drawer.querySelector<HTMLButtonElement>(
            '[data-login-password-toggle]',
        );

        toggle?.addEventListener('click', () => {
            if (!input) {
                return;
            }

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-label', show ? 'Ukryj hasło' : 'Pokaż hasło');
        });
    };

    const initializeRegisterDrawer = (drawer: HTMLElement) => {
        drawer
            .querySelectorAll<HTMLButtonElement>('[data-register-password-toggle]')
            .forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.registerPasswordTarget;
                    const input = targetId
                        ? drawer.querySelector<HTMLInputElement>(`#${targetId}`)
                        : null;

                    if (!input) {
                        return;
                    }

                    const show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    button.setAttribute('aria-label', show ? 'Ukryj hasło' : 'Pokaż hasło');
                });
            });

        const dropdown = drawer.querySelector<HTMLElement>('[data-register-category-dropdown]');
        const trigger = drawer.querySelector<HTMLButtonElement>('[data-register-category-trigger]');
        const label = drawer.querySelector<HTMLElement>('[data-register-category-label]');
        const list = drawer.querySelector<HTMLElement>('[data-register-category-list]');
        const chevron = drawer.querySelector<HTMLElement>('[data-register-category-chevron]');
        const value = drawer.querySelector<HTMLInputElement>('[data-register-category-value]');
        const options = Array.from(
            drawer.querySelectorAll<HTMLButtonElement>('[data-register-category-option]'),
        );

        const setCategoryOpen = (open: boolean) => {
            list?.classList.toggle('hidden', !open);
            trigger?.setAttribute('aria-expanded', String(open));
            trigger?.classList.toggle('border-[#9aa3af]', open);
            trigger?.classList.toggle('ring-3', open);
            trigger?.classList.toggle('ring-slate-200', open);
            chevron?.classList.toggle('rotate-180', open);
            chevron?.classList.toggle('text-[#4b5563]', open);
        };

        const setCategory = (categoryId: string) => {
            if (value) {
                value.value = categoryId;
            }

            const selectedOption = options.find(
                (option) => option.dataset.categoryId === categoryId,
            );

            if (label) {
                label.textContent = selectedOption?.dataset.categoryName ?? 'Wybierz kategorię';
                label.classList.toggle('text-[#111827]', Boolean(selectedOption));
                label.classList.toggle('text-[#9aa3af]', !selectedOption);
            }

            options.forEach((option) => {
                const selected = option.dataset.categoryId === categoryId;
                option.setAttribute('aria-selected', String(selected));
                option.classList.toggle('bg-[#d01921]', selected);
                option.classList.toggle('text-white', selected);
                option.classList.toggle('bg-white', !selected);
                option.classList.toggle('text-[#374151]', !selected);
                option.classList.toggle('hover:bg-[#fff5f5]', !selected);
                option.classList.toggle('hover:text-[#111827]', !selected);
            });
        };

        trigger?.addEventListener('click', () => {
            setCategoryOpen(trigger.getAttribute('aria-expanded') !== 'true');
        });

        options.forEach((option) => {
            option.addEventListener('click', () => {
                const categoryId = option.dataset.categoryId ?? '';

                if (categoryId === '') {
                    return;
                }

                setCategory(categoryId);
                setCategoryOpen(false);
                drawer
                    .querySelector<HTMLElement>('[data-register-category-client-error]')
                    ?.classList.add('hidden');
            });
        });

        document.addEventListener('click', (event) => {
            if (
                trigger?.getAttribute('aria-expanded') !== 'true'
                || !(event.target instanceof Node)
                || dropdown?.contains(event.target)
            ) {
                return;
            }

            setCategoryOpen(false);
        });

        if (value?.value) {
            setCategory(value.value);
        }

        drawer
            .querySelectorAll<HTMLButtonElement>('[data-register-social-provider]')
            .forEach((button) => {
                button.addEventListener('click', () => {
                    const provider = button.dataset.registerSocialProvider;

                    if (provider !== 'google' && provider !== 'facebook') {
                        return;
                    }

                    const categoryId = value?.value ?? '';
                    const error = drawer.querySelector<HTMLElement>(
                        '[data-register-category-client-error]',
                    );

                    if (categoryId === '') {
                        error?.classList.remove('hidden');
                        trigger?.focus();
                        return;
                    }

                    error?.classList.add('hidden');

                    const selectedTrack = drawer.querySelector<HTMLInputElement>(
                        'input[name="preferred_learning_track"]:checked',
                    );
                    const url = new URL(
                        `/auth/${provider}/redirect`,
                        window.location.origin,
                    );

                    url.searchParams.set('target_category_id', categoryId);
                    url.searchParams.set(
                        'preferred_learning_track',
                        selectedTrack?.value ?? 'classic',
                    );

                    window.location.href = url.toString();
                });
            });
    };

    const initializeDrawer = (name: AuthDrawerName, drawer: HTMLElement) => {
        if (initializedDrawers.has(drawer)) {
            return;
        }

        initializedDrawers.add(drawer);
        setupCsrfRefreshForms(drawer);

        drawer.addEventListener('click', (event) => {
            if (event.target === drawer) {
                closeDrawer(name);
            }
        });

        drawer
            .querySelectorAll<HTMLElement>(
                '[data-login-drawer-close], [data-register-drawer-close]',
            )
            .forEach((button) => {
                button.addEventListener('click', () => closeDrawer(name));
            });

        drawer
            .querySelectorAll<HTMLElement>('[data-auth-drawer-switch]')
            .forEach((switchControl) => {
                switchControl.addEventListener('click', (event) => {
                    const nextDrawer = switchControl.dataset.authDrawerSwitch;

                    if (nextDrawer !== 'login' && nextDrawer !== 'register') {
                        return;
                    }

                    event.preventDefault();
                    void open(nextDrawer);
                });
            });

        if (name === 'login') {
            initializeLoginDrawer(drawer);
        } else {
            initializeRegisterDrawer(drawer);
        }
    };

    const loadDrawer = async (name: AuthDrawerName) => {
        const url = name === 'login'
            ? root.dataset.loginFragmentUrl
            : root.dataset.registerFragmentUrl;

        if (!url) {
            throw new Error(`Missing ${name} drawer URL.`);
        }

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const contentType = response.headers.get('content-type') ?? '';
        const responseDrawer = response.headers.get('x-prawkonaraz-auth-drawer');

        if (
            !response.ok
            || response.redirected
            || !contentType.includes('text/html')
            || responseDrawer !== name
        ) {
            throw new Error(`Invalid ${name} drawer response.`);
        }

        const template = document.createElement('template');
        template.innerHTML = (await response.text()).trim();
        const drawer = template.content.querySelector<HTMLElement>(drawerSelector(name));

        if (!drawer) {
            throw new Error(`Missing ${name} drawer markup.`);
        }

        root.append(template.content);

        const insertedDrawer = getDrawer(name);

        if (!insertedDrawer) {
            throw new Error(`Unable to mount ${name} drawer.`);
        }

        initializeDrawer(name, insertedDrawer);

        return insertedDrawer;
    };

    const ensureDrawer = (name: AuthDrawerName): Promise<HTMLElement> => {
        const existing = getDrawer(name);

        if (existing) {
            initializeDrawer(name, existing);
            return Promise.resolve(existing);
        }

        const pending = drawerPromises.get(name);

        if (pending) {
            return pending;
        }

        const promise = loadDrawer(name).catch((error: unknown) => {
            drawerPromises.delete(name);
            throw error;
        });
        drawerPromises.set(name, promise);

        return promise;
    };

    async function open(name: AuthDrawerName) {
        closeOneTap();
        requestedDrawer = name;
        const drawer = await ensureDrawer(name);

        if (requestedDrawer !== name) {
            return;
        }

        if (activeDrawer === null) {
            previousBodyOverflow = document.body.style.overflow;
        }

        if (activeDrawer === null) {
            visualHostDrawer = name;
        }

        getDrawerEntries().forEach(([entryName, entryDrawer]) => {
            const retainVisual = entryName === visualHostDrawer && entryName !== name;

            setDrawerDisplay(
                entryName,
                entryDrawer,
                entryName === name,
                retainVisual,
            );
        });

        activeDrawer = name;
        document.body.style.overflow = 'hidden';
        document.dispatchEvent(new CustomEvent('public-auth-drawer-opened', {
            detail: { name },
        }));

        if (name === 'login') {
            void setupGoogleButton(drawer);
        }
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const registerDrawer = getDrawer('register');
        const registerTrigger = registerDrawer?.querySelector<HTMLButtonElement>(
            '[data-register-category-trigger]',
        );

        if (
            registerDrawer
            && registerTrigger?.getAttribute('aria-expanded') === 'true'
        ) {
            resetRegisterCategoryDropdown(registerDrawer);
            return;
        }

        closeActiveDrawer();
    });

    return {
        open,
        setupOneTap: async () => {
            const config = googleConfigFromRoot();

            if (!config) {
                return;
            }

            await promptOneTap(config);
        },
    };
};
