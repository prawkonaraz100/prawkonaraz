@php
    $previewHtml = e((string) ($explanation ?? ''));

    foreach ($references as $reference) {
        if (($reference['placement'] ?? 'replace') !== 'replace') {
            continue;
        }

        $matchText = trim((string) ($reference['match_text'] ?? ''));
        $imageUrl = trim((string) ($reference['image_url'] ?? ''));

        if ($matchText === '' || $imageUrl === '') {
            continue;
        }

        $altText = trim((string) ($reference['alt_text'] ?? $reference['code'] ?? 'Znak drogowy'));
        $image = '<img class="question-sign-overrides-preview__sign" src="'.e($imageUrl).'" alt="'.e($altText).'">';
        $pattern = '/(?<![\pL\pN])'.preg_quote($matchText, '/').'(?![\pL\pN])/iu';

        $previewHtml = preg_replace($pattern, $image, $previewHtml) ?? $previewHtml;
    }

    foreach ($references as $reference) {
        if (($reference['placement'] ?? 'replace') !== 'after') {
            continue;
        }

        $matchText = trim((string) ($reference['match_text'] ?? ''));
        $imageUrl = trim((string) ($reference['image_url'] ?? ''));

        if ($matchText === '' || $imageUrl === '') {
            continue;
        }

        $altText = trim((string) ($reference['alt_text'] ?? $reference['code'] ?? 'Znak drogowy'));
        $image = '<img class="question-sign-overrides-preview__sign" src="'.e($imageUrl).'" alt="'.e($altText).'">';
        $previewHtml = preg_replace('/'.preg_quote(e($matchText), '/').'/iu', '$0'.$image, $previewHtml, 1) ?? $previewHtml;
    }
@endphp

<div class="question-sign-overrides-preview">
    <p class="question-sign-overrides-preview__heading">Podgląd zapisanego wyjaśnienia</p>

    @if ($previewHtml !== '')
        <p class="question-sign-overrides-preview__text">{!! $previewHtml !!}</p>
        <p class="question-sign-overrides-preview__hint">Podgląd odświeży się po zapisaniu formularza.</p>
    @else
        <p class="question-sign-overrides-preview__empty">Najpierw zapisz treść wyjaśnienia, aby zobaczyć automatycznie wykryte znaki.</p>
    @endif
</div>

<style>
    .question-sign-overrides-preview {
        border: 1px solid #d7dde6;
        border-radius: 6px;
        padding: 1rem;
        background: #f8fafc;
    }

    .question-sign-overrides-preview__heading {
        margin: 0 0 .5rem;
        color: #111827;
        font-size: .875rem;
        font-weight: 700;
    }

    .question-sign-overrides-preview__text {
        margin: 0;
        color: #374151;
        line-height: 1.65;
    }

    .question-sign-overrides-preview__sign {
        display: inline-block;
        width: 1.55em;
        height: 1.55em;
        margin: 0 .16em;
        object-fit: contain;
        vertical-align: -.42em;
    }

    .question-sign-overrides-preview__hint,
    .question-sign-overrides-preview__empty {
        margin: .65rem 0 0;
        color: #64748b;
        font-size: .8125rem;
    }
</style>
