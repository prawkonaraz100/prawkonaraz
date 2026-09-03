export type InlineFormattingMarker = 'bold' | 'green' | 'red';

type ApplyInlineFormattingSelectionInput = {
    value: string | null | undefined;
    selectionStart: number | null | undefined;
    selectionEnd: number | null | undefined;
    marker: InlineFormattingMarker;
};

type ApplyInlineFormattingSelectionResult = {
    value: string;
    selectionStart: number;
    selectionEnd: number;
};

const INLINE_FORMATTING_MARKERS: Record<InlineFormattingMarker, { open: string; close: string }> = {
    bold: {
        open: '**',
        close: '**',
    },
    green: {
        open: '[green]',
        close: '[/green]',
    },
    red: {
        open: '[red]',
        close: '[/red]',
    },
};

const clampSelectionIndex = (value: number, length: number) =>
    Math.min(Math.max(value, 0), length);

export const applyInlineFormattingSelection = ({
    value,
    selectionStart,
    selectionEnd,
    marker,
}: ApplyInlineFormattingSelectionInput): ApplyInlineFormattingSelectionResult => {
    const normalizedValue = value ?? '';
    const markerPair = INLINE_FORMATTING_MARKERS[marker];
    const rawStart = clampSelectionIndex(selectionStart ?? 0, normalizedValue.length);
    const rawEnd = clampSelectionIndex(selectionEnd ?? rawStart, normalizedValue.length);
    const start = Math.min(rawStart, rawEnd);
    const end = Math.max(rawStart, rawEnd);
    const selectedFragment = normalizedValue.slice(start, end);
    const nextValue = normalizedValue.slice(0, start)
        + markerPair.open
        + selectedFragment
        + markerPair.close
        + normalizedValue.slice(end);

    return {
        value: nextValue,
        selectionStart: start + markerPair.open.length,
        selectionEnd: start + markerPair.open.length + selectedFragment.length,
    };
};
