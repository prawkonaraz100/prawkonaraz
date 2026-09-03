@php
    $assetPath = trim((string) $schemaComponent->getContainer()->getStatePath());
    $assetPath = $assetPath !== '' ? "{$assetPath}.explanation_asset" : 'explanation_asset';
    $explanationPath = trim((string) $schemaComponent->getContainer()->getStatePath());
    $explanationPath = $explanationPath !== '' ? "{$explanationPath}.explanation" : 'explanation';
    $publicDisk = (string) config('media.public_disk', 'public');
    $publicBaseUrl = rtrim((string) config('media.public_base_url', ''), '/');
    $fallbackImageUrl = '/images/session/explanation-fallback.svg';

    $initialAsset = data_get($this, $assetPath, []);
    $initialAsset = is_array($initialAsset) ? $initialAsset : [];
    $initialExplanation = data_get($this, $explanationPath, '');
    $initialExplanation = is_string($initialExplanation) ? trim($initialExplanation) : '';

    $normalizePreviewString = static function (mixed $value): string {
        if (is_array($value)) {
            $flattened = array_reverse(\Illuminate\Support\Arr::flatten($value));

            foreach ($flattened as $item) {
                if (! is_string($item)) {
                    continue;
                }

                $trimmed = trim($item);

                if ($trimmed !== '') {
                    return $trimmed;
                }
            }

            return '';
        }

        return is_string($value) ? trim($value) : '';
    };

    $toPreviewHtml = static function (string $value): string {
        if ($value === '') {
            return '';
        }

        $escapedBody = e(preg_replace("/\r\n?/", "\n", $value) ?? $value);
        $bodyWithBold = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escapedBody) ?? $escapedBody;
        $bodyWithBold = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $bodyWithBold) ?? $bodyWithBold;
        $paragraphs = preg_split("/\n{2,}/", $bodyWithBold) ?: [];

        return implode('', array_map(
            fn (string $paragraph): string => '<p>'.str_replace("\n", '<br>', $paragraph).'</p>',
            $paragraphs,
        ));
    };

    $initialFilePath = $normalizePreviewString($initialAsset['file_path'] ?? '');
    $initialTrafficSignImageUrl = $normalizePreviewString($initialAsset['traffic_sign_image_url'] ?? '');
    $initialDisk = $normalizePreviewString($initialAsset['disk'] ?? '') ?: $publicDisk;
    $initialTitle = $normalizePreviewString($initialAsset['title'] ?? '');
    $initialAltText = $normalizePreviewString($initialAsset['alt_text'] ?? '');
    $initialBody = $normalizePreviewString($initialAsset['body'] ?? '');
    $initialImageUrl = null;
    $diskBaseUrls = collect((array) config('filesystems.disks', []))
        ->mapWithKeys(function (mixed $diskConfig, string $disk): array {
            $url = is_array($diskConfig) ? trim((string) ($diskConfig['url'] ?? '')) : '';

            return $url !== '' ? [$disk => rtrim($url, '/')] : [];
        })
        ->all();

    if ($publicBaseUrl !== '') {
        $diskBaseUrls[$publicDisk] = $publicBaseUrl;
    }

    if ($initialTrafficSignImageUrl !== '') {
        $initialImageUrl = $initialTrafficSignImageUrl;
    } elseif ($initialFilePath !== '') {
        $initialImageUrl = app(\App\Support\MediaUrlResolver::class)->resolve($initialFilePath, $initialDisk);
    }

    $initialCardBody = $initialExplanation !== '' ? $initialExplanation : $initialBody;
    $initialBodyHtml = $toPreviewHtml($initialCardBody);
@endphp

<div
    x-data="questionReferenceAssetPreview({
        asset: $wire.entangle('{{ $assetPath }}').live,
        explanation: $wire.entangle('{{ $explanationPath }}').live,
        publicDisk: @js($publicDisk),
        diskBaseUrls: @js($diskBaseUrls),
        fallbackImageUrl: @js($fallbackImageUrl),
    })"
    class="rounded-2xl border border-[#e5e7eb] bg-[#f8fafc] p-4"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="space-y-1">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#6b7280]">
                Podgląd karty odpowiedzi
            </p>
            <p class="text-sm leading-6 text-[#475569]">
                To podgląd gotowej karty odpowiedzi. Grafika z tego formularza połączy się tu z tekstem z pola Wyjaśnienie. Gdy grafiki brak, runtime pokaże przykładowy fallback.
            </p>
        </div>

        <span
            class="inline-flex items-center rounded-full border px-3 py-1 text-[0.72rem] font-semibold uppercase tracking-[0.12em]"
            :class="asset?.is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500'"
            x-text="asset?.is_active ? 'Aktywny' : 'Wyłączony'"
        ></span>
    </div>

    <div class="mt-4 border border-[#e5e7eb] bg-white px-4 py-4 text-neutral-950">
        <div class="grid gap-4 md:grid-cols-[9rem_minmax(0,1fr)] md:items-center">
            <div class="overflow-hidden border border-[#e5e7eb] bg-[#fafafa] px-2.5 py-2.5">
                <div class="flex min-h-[7.5rem] items-center justify-center bg-white px-2 py-2">
                    <img
                        :src="imageUrl()"
                        :alt="normalize(asset?.alt_text) || normalize(asset?.title) || 'Grafika pomocnicza do wyjaśnienia'"
                        src="{{ $initialImageUrl ?: $fallbackImageUrl }}"
                        alt="{{ $initialAltText !== '' ? $initialAltText : ($initialTitle !== '' ? $initialTitle : 'Grafika pomocnicza do wyjaśnienia') }}"
                        class="max-h-[9rem] w-full object-contain"
                    />
                </div>
            </div>

            <div class="min-w-0">
                <div
                    x-show="formattedBody() !== ''"
                    class="text-[0.98rem] leading-7 text-[#374151] [&_p:not(:first-child)]:mt-3 [&_strong]:font-semibold [&_strong]:text-[#111827]"
                    x-html="formattedBody()"
                >{!! $initialBodyHtml !!}</div>

                <p
                    x-show="formattedBody() === ''"
                    class="text-[0.98rem] leading-7 text-[#64748b]"
                >
                    Do tego pytania nie mamy jeszcze gotowego wyjaśnienia. Warto wrócić do niego od razu po tej sesji.
                </p>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        window.isQuestionExplanationUploadInProgress = function () {
            const statusSelectors = [
                '.filepond--file-status-main',
                '.filepond--file-status-sub',
            ]

            const hasBusyFilepondItem = Array.from(
                document.querySelectorAll('[data-filepond-item-state]'),
            ).some((node) => {
                const state = (node.getAttribute('data-filepond-item-state') ?? '').trim()

                if (state === '') {
                    return false
                }

                return /\bbusy\b/i.test(state) || (/\bprocessing\b/i.test(state) && !/\bprocessing-complete\b/i.test(state))
            })

            const hasUploadingStatusText = statusSelectors.some((selector) =>
                Array.from(document.querySelectorAll(selector)).some((node) =>
                    /przesyłanie|wczytywanie|wysyłanie pliku/i.test(node.textContent ?? '')
                ),
            )

            return hasUploadingStatusText || hasBusyFilepondItem
        }

        window.syncQuestionExplanationUploadGuards = function () {
            const isUploading = window.isQuestionExplanationUploadInProgress?.() ?? false

            document
                .querySelectorAll('[data-upload-guard="question-explanation-asset"]')
                .forEach((button) => {
                    if (!(button instanceof HTMLButtonElement)) {
                        return
                    }

                    if (isUploading) {
                        button.disabled = true
                        button.setAttribute('disabled', 'disabled')
                        button.dataset.uploadGuardDisabled = 'true'

                        return
                    }

                    if (button.dataset.uploadGuardDisabled !== 'true') {
                        return
                    }

                    button.disabled = false
                    button.removeAttribute('disabled')
                    delete button.dataset.uploadGuardDisabled
                })
        }

        window.questionReferenceAssetPreview = function (config) {
            return {
                asset: config.asset,
                explanation: config.explanation,
                publicDisk: typeof config.publicDisk === 'string' ? config.publicDisk : 'public',
                diskBaseUrls: config.diskBaseUrls ?? {},
                fallbackImageUrl: typeof config.fallbackImageUrl === 'string'
                    ? config.fallbackImageUrl
                    : '/images/session/explanation-fallback.svg',
                liveUploadUrl: '',
                uploadFieldObserver: null,
                init() {
                    this.syncLiveUploadUrl()
                    window.syncQuestionExplanationUploadGuards?.()

                    const uploadField = this.uploadFieldElement()

                    if (! uploadField || typeof MutationObserver === 'undefined') {
                        return
                    }

                    this.uploadFieldObserver = new MutationObserver(() => {
                        this.syncLiveUploadUrl()
                        window.syncQuestionExplanationUploadGuards?.()
                    })

                    this.uploadFieldObserver.observe(uploadField, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['class', 'href', 'src', 'style', 'data-filepond-item-state'],
                    })
                },
                normalize(value) {
                    if (Array.isArray(value)) {
                        for (const item of value.flat(Infinity).reverse()) {
                            const normalized = this.normalize(item)

                            if (normalized !== '') {
                                return normalized
                            }
                        }

                        return ''
                    }

                    if (value && typeof value === 'object') {
                        for (const item of Object.values(value)) {
                            const normalized = this.normalize(item)

                            if (normalized !== '') {
                                return normalized
                            }
                        }

                        return ''
                    }

                    return typeof value === 'string' ? value.trim() : ''
                },
                escapeHtml(value) {
                    return value
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;')
                },
                resolveDiskBaseUrl(disk) {
                    const normalizedDisk = this.normalize(disk) || this.publicDisk
                    const explicitBaseUrl = this.diskBaseUrls?.[normalizedDisk]

                    if (typeof explicitBaseUrl === 'string' && explicitBaseUrl !== '') {
                        return explicitBaseUrl
                    }

                    return normalizedDisk === 'public' ? '/storage' : ''
                },
                uploadFieldElement() {
                    return document.getElementById('form.explanation_asset.file_path')
                },
                resolveLiveUploadUrlFromDom() {
                    const uploadField = this.uploadFieldElement()

                    if (! uploadField) {
                        return ''
                    }

                    const link = uploadField.querySelector('.filepond--open-icon, .filepond--download-icon')

                    return this.normalize(link?.getAttribute('href'))
                },
                syncLiveUploadUrl() {
                    this.liveUploadUrl = this.resolveLiveUploadUrlFromDom()
                },
                imageUrl() {
                    const liveUploadUrl = this.normalize(this.liveUploadUrl)

                    if (liveUploadUrl) {
                        return liveUploadUrl
                    }

                    const previewUrl = this.normalize(this.asset?.preview_url)

                    if (previewUrl) {
                        return previewUrl
                    }

                    const signImageUrl = this.normalize(this.asset?.traffic_sign_image_url)

                    if (signImageUrl) {
                        return signImageUrl
                    }

                    const path = this.normalize(this.asset?.file_path)

                    if (! path) {
                        return this.fallbackImageUrl
                    }

                    if (/^https?:\/\//i.test(path)) {
                        return path
                    }

                    const baseUrl = this.resolveDiskBaseUrl(this.asset?.disk)

                    if (! baseUrl) {
                        return this.fallbackImageUrl
                    }

                    return `${baseUrl.replace(/\/+$/, '')}/${path.replace(/^\/+/, '')}`
                },
                formattedBody() {
                    const body = this.normalize(this.explanation) || this.normalize(this.asset?.body)

                    if (! body) {
                        return ''
                    }

                    const escaped = this.escapeHtml(body.replace(/\r\n?/g, '\n'))
                    const withBold = escaped
                        .replace(/\*\*(.+?)\*\*/gs, '<strong>$1</strong>')
                        .replace(/__(.+?)__/gs, '<strong>$1</strong>')

                    return withBold
                        .split(/\n{2,}/)
                        .map((paragraph) => `<p>${paragraph.replace(/\n/g, '<br>')}</p>`)
                        .join('')
                },
            }
        }
    </script>
@endonce
