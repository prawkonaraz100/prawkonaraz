import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { route as ziggyRoute } from 'ziggy-js';
import { PageProps as AppPageProps } from './';

declare global {
    /* eslint-disable no-var */
    var route: typeof ziggyRoute;

    interface Window {
        google?: {
            accounts: {
                id: {
                    initialize(options: {
                        client_id: string;
                        callback: (response: GoogleIdentityCredentialResponse) => void;
                        context?: 'signin' | 'signup' | 'use';
                        ux_mode?: 'popup' | 'redirect';
                        cancel_on_tap_outside?: boolean;
                    }): void;
                    renderButton(
                        parent: HTMLElement,
                        options: {
                            type?: 'standard' | 'icon';
                            theme?: 'outline' | 'filled_blue' | 'filled_black';
                            size?: 'large' | 'medium' | 'small';
                            text?: 'signin_with' | 'signup_with' | 'continue_with' | 'signin';
                            shape?: 'rectangular' | 'pill' | 'circle' | 'square';
                            logo_alignment?: 'left' | 'center';
                            width?: number;
                            locale?: string;
                        },
                    ): void;
                    prompt(callback?: (notification: unknown) => void): void;
                    cancel(): void;
                };
            };
        };
    }

    interface GoogleIdentityCredentialResponse {
        credential?: string;
        select_by?: string;
        clientId?: string;
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
