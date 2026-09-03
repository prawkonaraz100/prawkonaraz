import { describe, expect, it } from 'vitest';
import { applyInlineFormattingSelection } from './inlineFormattingSelection';

describe('inlineFormattingSelection', () => {
    it('wraps selected text with bold markers and preserves the inner selection', () => {
        const result = applyInlineFormattingSelection({
            value: 'To jest pytanie',
            selectionStart: 8,
            selectionEnd: 15,
            marker: 'bold',
        });

        expect(result.value).toBe('To jest **pytanie**');
        expect(result.selectionStart).toBe(10);
        expect(result.selectionEnd).toBe(17);
    });

    it('inserts green markers at the caret and keeps the cursor inside the pair', () => {
        const result = applyInlineFormattingSelection({
            value: 'To jest pytanie',
            selectionStart: 2,
            selectionEnd: 2,
            marker: 'green',
        });

        expect(result.value).toBe('To[green][/green] jest pytanie');
        expect(result.selectionStart).toBe(9);
        expect(result.selectionEnd).toBe(9);
    });

    it('supports chaining markers on already wrapped text', () => {
        const result = applyInlineFormattingSelection({
            value: '[green]tekst[/green]',
            selectionStart: 7,
            selectionEnd: 12,
            marker: 'bold',
        });

        expect(result.value).toBe('[green]**tekst**[/green]');
        expect(result.selectionStart).toBe(9);
        expect(result.selectionEnd).toBe(14);
    });

    it('normalizes reversed selections before wrapping text', () => {
        const result = applyInlineFormattingSelection({
            value: 'Lewa strona',
            selectionStart: 10,
            selectionEnd: 5,
            marker: 'red',
        });

        expect(result.value).toBe('Lewa [red]stron[/red]a');
        expect(result.selectionStart).toBe(10);
        expect(result.selectionEnd).toBe(15);
    });

    it('clamps out-of-range selection indexes to the current value length', () => {
        const result = applyInlineFormattingSelection({
            value: 'Test',
            selectionStart: -10,
            selectionEnd: 100,
            marker: 'bold',
        });

        expect(result.value).toBe('**Test**');
        expect(result.selectionStart).toBe(2);
        expect(result.selectionEnd).toBe(6);
    });
});
