import {
    expiredSessionLoginUrl,
    refreshCsrfSession,
    updateFormCsrfToken,
} from './csrfSession';

const initializedForms = new WeakSet<HTMLFormElement>();

export const setupCsrfRefreshForms = (scope: ParentNode = document) => {
    const forms = Array.from(
        scope.querySelectorAll<HTMLFormElement>('[data-csrf-refresh-form]'),
    );

    if (scope instanceof HTMLFormElement && scope.matches('[data-csrf-refresh-form]')) {
        forms.unshift(scope);
    }

    forms.forEach((form) => {
        if (initializedForms.has(form)) {
            return;
        }

        initializedForms.add(form);

        let preparing = false;
        const submitButton = form.querySelector<HTMLButtonElement>(
            '[data-csrf-submit]',
        );
        const error = form.querySelector<HTMLElement>(
            '[data-csrf-form-error]',
        );
        const initialButtonLabel = submitButton?.textContent?.trim() ?? '';
        const initiallyDisabled = submitButton?.disabled ?? false;

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (preparing) {
                return;
            }

            preparing = true;
            error?.classList.add('hidden');

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Sprawdzanie sesji...';
            }

            try {
                const session = await refreshCsrfSession();

                if (
                    form.dataset.csrfFormKind === 'logout'
                    && !session.authenticated
                ) {
                    window.location.assign(expiredSessionLoginUrl());
                    return;
                }

                updateFormCsrfToken(form, session.token);
                HTMLFormElement.prototype.submit.call(form);
            } catch {
                error?.classList.remove('hidden');
                preparing = false;

                if (submitButton) {
                    submitButton.disabled = initiallyDisabled;
                    submitButton.textContent = initialButtonLabel;
                }
            }
        });
    });
};
