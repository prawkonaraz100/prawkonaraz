const normalizeLineEndings = (value: string) =>
    value.replace(/\r\n?/g, '\n');

export type InlineFormattingPalette = 'classic' | 'zen';

type InlineFormattingOptions = {
    palette?: InlineFormattingPalette;
    enableBold?: boolean;
    enableColors?: boolean;
};

type InlineFormattingColorPalette = {
    successText: string;
    dangerText: string;
};

export type ExplanationSignReference = {
    code: string;
    image_url: string;
    alt_text?: string | null;
    match_text?: string | null;
    placement?: 'replace' | 'after';
    reference_key?: string;
};

const INLINE_FORMATTING_COLOR_PALETTES: Record<InlineFormattingPalette, InlineFormattingColorPalette> = {
    classic: {
        successText: '#163222',
        dangerText: '#612d2d',
    },
    zen: {
        successText: '#163222',
        dangerText: '#4e2727',
    },
};

const escapeHtml = (value: string) =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

const escapeRegularExpression = (value: string) =>
    value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const signReferenceKey = (code: string) => code.trim().toLocaleUpperCase('pl-PL');

const signImageMarkup = (reference: ExplanationSignReference) => {
    const altText = reference.alt_text?.trim() || `Znak ${reference.code}`;

    return `<img class="explanation-inline-sign" src="${escapeHtml(reference.image_url)}" alt="${escapeHtml(altText)}" loading="lazy" decoding="async">`;
};

const colorSuccessMarkupPattern = /\[(?:green|zielony)\]([\s\S]+?)\[\/(?:green|zielony)\]/gi;
const colorDangerMarkupPattern = /\[(?:red|czerwony)\]([\s\S]+?)\[\/(?:red|czerwony)\]/gi;

const resolveInlineFormattingColorPalette = (
    palette: InlineFormattingPalette | undefined,
) =>
    INLINE_FORMATTING_COLOR_PALETTES[palette ?? 'classic']
    ?? INLINE_FORMATTING_COLOR_PALETTES.classic;

const applyColorMarkup = (
    value: string,
    palette: InlineFormattingPalette | undefined,
) => {
    const colors = resolveInlineFormattingColorPalette(palette);

    return value
        .replace(
            colorSuccessMarkupPattern,
            `<span style="color:${colors.successText};">$1</span>`,
        )
        .replace(
            colorDangerMarkupPattern,
            `<span style="color:${colors.dangerText};">$1</span>`,
        );
};

const applyBoldMarkup = (value: string) =>
    value
        .replace(/\*\*(.+?)\*\*/gs, '<strong>$1</strong>')
        .replace(/__(.+?)__/gs, '<strong>$1</strong>');

const applyInlineMarkup = (
    value: string,
    options: InlineFormattingOptions,
) => {
    const withColor = options.enableColors === false
        ? stripColorMarkers(value)
        : applyColorMarkup(value, options.palette);

    return options.enableBold === false
        ? stripBoldMarkers(withColor)
        : applyBoldMarkup(withColor);
};

const stripColorMarkers = (value: string) =>
    value
        .replace(colorSuccessMarkupPattern, '$1')
        .replace(colorDangerMarkupPattern, '$1');

const stripBoldMarkers = (value: string) =>
    value
        .replace(/\*\*(.+?)\*\*/gs, '$1')
        .replace(/__(.+?)__/gs, '$1');

const stripInlineMarkers = (value: string) =>
    stripBoldMarkers(stripColorMarkers(value));

export const normalizeExplanationPlainText = (
    explanation: string | null | undefined,
) =>
    stripInlineMarkers(normalizeLineEndings(explanation ?? ''))
        .replace(/\s+/g, ' ')
        .trim();

export const renderExplanationHtml = (
    explanation: string | null | undefined,
    options: InlineFormattingOptions = {},
) => {
    const normalized = normalizeLineEndings(explanation ?? '').trim();

    if (!normalized) {
        return '';
    }

    const escaped = escapeHtml(normalized);
    const withInlineMarkup = applyInlineMarkup(escaped, options);

    return withInlineMarkup
        .split(/\n{2,}/)
        .map((paragraph) => `<p>${paragraph.replace(/\n/g, '<br>')}</p>`)
        .join('');
};

export const renderInlineFormattedHtml = (
    value: string | null | undefined,
    options: InlineFormattingOptions = {},
) => {
    const normalized = normalizeLineEndings(value ?? '').trim();

    if (!normalized) {
        return '';
    }

    return applyInlineMarkup(escapeHtml(normalized), options).replace(/\n/g, '<br>');
};

/**
 * Replaces a recognized traffic-sign code in already-sanitized explanation HTML.
 * Tags are preserved untouched so image markup can only be inserted into text nodes.
 */
export const injectExplanationSignImages = (
    html: string,
    signReferences: ExplanationSignReference[] = [],
) => {
    if (!html || signReferences.length === 0) {
        return html;
    }

    const replacementReferencesByCode = new Map<string, ExplanationSignReference>();
    const inlineAdditions: Array<ExplanationSignReference & {
        matchText: string;
        referenceKey: string;
    }> = [];

    signReferences.forEach((reference, index) => {
        const matchText = reference.match_text?.trim() || reference.code?.trim();

        if (!matchText || !reference.image_url) {
            return;
        }

        if (reference.placement === 'after') {
            inlineAdditions.push({
                ...reference,
                matchText,
                referenceKey: reference.reference_key
                    ?? `after:${matchText}:${reference.code}:${index}`,
            });

            return;
        }

        replacementReferencesByCode.set(signReferenceKey(matchText), reference);
    });

    const codes = [...replacementReferencesByCode.keys()]
        .sort((left, right) => right.length - left.length);

    if (codes.length === 0 && inlineAdditions.length === 0) {
        return html;
    }

    const replacementMatcher = codes.length > 0
        ? new RegExp(
            `(^|[^\\p{L}\\p{N}])(${codes.map(escapeRegularExpression).join('|')})(?![\\p{L}\\p{N}])`,
            'giu',
        )
        : null;
    const usedInlineAdditionKeys = new Set<string>();
    const orderedInlineAdditions = [...inlineAdditions]
        .sort((left, right) => right.matchText.length - left.matchText.length);

    return html
        .split(/(<[^>]+>)/u)
        .map((part) => {
            if (!part || part.startsWith('<')) {
                return part;
            }

            const withReplacements = replacementMatcher
                ? part.replace(replacementMatcher, (match, prefix: string, code: string) => {
                    const reference = replacementReferencesByCode.get(signReferenceKey(code));

                    return reference ? `${prefix}${signImageMarkup(reference)}` : match;
                })
                : part;

            return orderedInlineAdditions.reduce((text, reference) => {
                if (usedInlineAdditionKeys.has(reference.referenceKey)) {
                    return text;
                }

                const anchorMatcher = new RegExp(escapeRegularExpression(reference.matchText), 'iu');

                return text.replace(anchorMatcher, (match) => {
                    usedInlineAdditionKeys.add(reference.referenceKey);

                    return `${match}${signImageMarkup(reference)}`;
                });
            }, withReplacements);
        })
        .join('');
};
