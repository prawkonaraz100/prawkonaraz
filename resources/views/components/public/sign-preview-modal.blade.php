@once
    <div
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0f172a]/60 px-4 py-6"
        data-sign-preview-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="sign-preview-title"
    >
        <div class="absolute inset-0" data-sign-preview-close aria-hidden="true"></div>

        <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-[6px] bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-[#e9edf2] px-5 py-4">
                <div class="min-w-0">
                    <p class="text-[0.78rem] font-bold uppercase tracking-[0.02em] text-[#d01921]" data-sign-preview-code></p>
                    <h2 id="sign-preview-title" class="mt-1 text-[1.15rem] font-bold leading-6 text-[#111827]" data-sign-preview-title></h2>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc] hover:text-[#111827] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d01921]/35"
                    data-sign-preview-close
                    aria-label="Zamknij podgląd znaku"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-5 px-5 py-5 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-start">
                <div class="flex min-h-[160px] items-center justify-center border border-[#e9edf2] bg-[#fbfcfe] p-4" data-sign-preview-image-box>
                    <img
                        src=""
                        alt=""
                        class="max-h-[160px] w-full object-contain"
                        data-sign-preview-image
                    >
                    <span class="hidden text-center text-[0.82rem] font-semibold text-[#64748b]" data-sign-preview-no-image>Brak miniatury w bazie.</span>
                </div>

                <div class="min-w-0">
                    <p class="text-[0.95rem] leading-7 text-[#334155]" data-sign-preview-description></p>
                    <a
                        href="#"
                        class="mt-5 inline-flex min-h-10 items-center justify-center rounded-[4px] bg-[#d01921] px-4 text-[0.86rem] font-bold text-white transition hover:bg-[#b9151c]"
                        data-sign-preview-link
                    >
                        Zobacz opis znaku
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const modal = document.querySelector('[data-sign-preview-modal]');

                if (!modal) {
                    return;
                }

                const codeNode = modal.querySelector('[data-sign-preview-code]');
                const titleNode = modal.querySelector('[data-sign-preview-title]');
                const descriptionNode = modal.querySelector('[data-sign-preview-description]');
                const imageNode = modal.querySelector('[data-sign-preview-image]');
                const noImageNode = modal.querySelector('[data-sign-preview-no-image]');
                const imageBox = modal.querySelector('[data-sign-preview-image-box]');
                const linkNode = modal.querySelector('[data-sign-preview-link]');
                const closeButtons = modal.querySelectorAll('[data-sign-preview-close]');
                let previousFocus = null;

                const setText = (node, value) => {
                    if (node) {
                        node.textContent = value || '';
                    }
                };

                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');

                    if (previousFocus && typeof previousFocus.focus === 'function') {
                        previousFocus.focus();
                    }
                };

                const open = (trigger) => {
                    const code = trigger.getAttribute('data-sign-code') || '';
                    const name = trigger.getAttribute('data-sign-name') || '';
                    const title = trigger.getAttribute('data-sign-title') || [code, name].filter(Boolean).join(' ');
                    const description = trigger.getAttribute('data-sign-description') || 'Ten znak mamy w bazie znaków drogowych. Szczegóły możesz sprawdzić w pełnym opisie znaku.';
                    const imageUrl = trigger.getAttribute('data-sign-image-url') || '';
                    const imageAlt = trigger.getAttribute('data-sign-image-alt') || title || 'Znak drogowy';
                    const signUrl = trigger.getAttribute('data-sign-url') || '';
                    const linkLabel = trigger.getAttribute('data-sign-link-label') || 'Zobacz opis znaku';

                    previousFocus = document.activeElement;
                    setText(codeNode, code);
                    setText(titleNode, title || code);
                    setText(descriptionNode, description);

                    if (imageNode && noImageNode && imageBox) {
                        if (imageUrl !== '') {
                            imageNode.src = imageUrl;
                            imageNode.alt = imageAlt;
                            imageNode.classList.remove('hidden');
                            noImageNode.classList.add('hidden');
                        } else {
                            imageNode.removeAttribute('src');
                            imageNode.alt = '';
                            imageNode.classList.add('hidden');
                            noImageNode.classList.remove('hidden');
                        }
                    }

                    if (linkNode) {
                        if (signUrl !== '') {
                            linkNode.href = signUrl;
                            linkNode.textContent = linkLabel;
                            linkNode.classList.remove('hidden');
                        } else {
                            linkNode.classList.add('hidden');
                        }
                    }

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                    modal.querySelector('[data-sign-preview-close]')?.focus();
                };

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-sign-preview]');

                    if (trigger) {
                        event.preventDefault();
                        event.stopPropagation();
                        open(trigger);

                        return;
                    }

                    if (event.target.closest('[data-sign-preview-close]')) {
                        event.preventDefault();
                        close();
                    }
                });

                closeButtons.forEach((button) => {
                    button.addEventListener('click', close);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        close();
                    }
                });
            })();
        </script>
    @endpush
@endonce
