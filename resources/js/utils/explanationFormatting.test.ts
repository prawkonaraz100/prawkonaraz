import { describe, expect, it } from 'vitest';
import {
    injectExplanationSignImages,
    normalizeExplanationPlainText,
    renderExplanationHtml,
    renderInlineFormattedHtml,
} from './explanationFormatting';

describe('explanationFormatting', () => {
    it('renders nested color and bold markers together', () => {
        const rendered = renderInlineFormattedHtml('[green]**tekst**[/green]', {
            palette: 'classic',
            enableBold: true,
            enableColors: true,
        });

        expect(rendered).toBe('<span style="color:#163222;"><strong>tekst</strong></span>');
    });

    it('keeps bold and strips color markers when colors are disabled', () => {
        const rendered = renderInlineFormattedHtml('[green]**tekst**[/green]', {
            palette: 'classic',
            enableBold: true,
            enableColors: false,
        });

        expect(rendered).toBe('<strong>tekst</strong>');
    });

    it('keeps color and strips bold markers when bold is disabled', () => {
        const rendered = renderInlineFormattedHtml('[green]**tekst**[/green]', {
            palette: 'classic',
            enableBold: false,
            enableColors: true,
        });

        expect(rendered).toBe('<span style="color:#163222;">tekst</span>');
    });

    it('strips both marker types when both toggles are disabled', () => {
        const rendered = renderInlineFormattedHtml('[green]**tekst**[/green]', {
            palette: 'classic',
            enableBold: false,
            enableColors: false,
        });

        expect(rendered).toBe('tekst');
    });

    it('uses shell-specific danger color palettes', () => {
        const classic = renderInlineFormattedHtml('[red]blad[/red]', {
            palette: 'classic',
        });
        const zen = renderInlineFormattedHtml('[red]blad[/red]', {
            palette: 'zen',
        });

        expect(classic).toBe('<span style="color:#612d2d;">blad</span>');
        expect(zen).toBe('<span style="color:#4e2727;">blad</span>');
    });

    it('keeps paragraph rendering while respecting formatting toggles', () => {
        const rendered = renderExplanationHtml('[green]**pierwszy**[/green]\n\n[red]drugi[/red]', {
            palette: 'classic',
            enableBold: true,
            enableColors: false,
        });

        expect(rendered).toBe('<p><strong>pierwszy</strong></p><p>drugi</p>');
    });

    it('normalizes plain text by stripping all inline markers', () => {
        const plain = normalizeExplanationPlainText('[green]**Tekst**[/green] [red]drugi[/red]');

        expect(plain).toBe('Tekst drugi');
    });

    it('replaces only recognized traffic-sign codes inside explanation text nodes', () => {
        const rendered = injectExplanationSignImages(
            '<p>Znak <strong>B-20</strong> nakazuje zatrzymanie. P-12 wskazuje miejsce. S-3 zostaje tekstem.</p>',
            [
                {
                    code: 'B-20',
                    image_url: 'https://media.example.test/b-20.webp',
                    alt_text: 'Znak B-20 Stop',
                },
                {
                    code: 'P-12',
                    image_url: 'https://media.example.test/p-12.webp',
                    alt_text: 'Znak P-12',
                },
            ],
        );

        expect(rendered).toContain('<strong><img class="explanation-inline-sign" src="https://media.example.test/b-20.webp" alt="Znak B-20 Stop" loading="lazy" decoding="async"></strong>');
        expect(rendered).toContain('<img class="explanation-inline-sign" src="https://media.example.test/p-12.webp" alt="Znak P-12" loading="lazy" decoding="async"> wskazuje miejsce');
        expect(rendered).toContain('S-3 zostaje tekstem.');
    });

    it('leaves HTML attributes untouched while replacing matching text', () => {
        const rendered = injectExplanationSignImages(
            '<p data-sign="B-20">B-20 jest wazny.</p>',
            [{
                code: 'B-20',
                image_url: 'https://media.example.test/b-20.webp',
                alt_text: 'Znak B-20 Stop',
            }],
        );

        expect(rendered).toContain('data-sign="B-20"');
        expect(rendered).toContain('><img class="explanation-inline-sign"');
    });

    it('replaces every occurrence of the same recognized sign code', () => {
        const rendered = injectExplanationSignImages(
            '<p>G-3 stoi przed przejazdem. Za G-3 kierujacy zachowuje ostroznosc.</p>',
            [{
                code: 'G-3',
                image_url: 'https://media.example.test/g-3.webp',
                alt_text: 'Znak G-3',
            }],
        );

        expect(rendered.match(/explanation-inline-sign/g)).toHaveLength(2);
        expect(rendered.replace(/<[^>]+>/g, '')).not.toContain('G-3');
    });

    it('adds one manual sign after its unique explanation fragment', () => {
        const rendered = injectExplanationSignImages(
            '<p>Po zatrzymaniu upewnij się, że możesz ruszyć.</p>',
            [{
                code: 'B-20',
                image_url: 'https://media.example.test/b-20.webp',
                alt_text: 'Znak B-20 Stop',
                match_text: 'Po zatrzymaniu',
                placement: 'after',
                reference_key: 'shared_override:12',
            }],
        );

        expect(rendered).toContain('Po zatrzymaniu<img class="explanation-inline-sign"');
        expect(rendered.match(/explanation-inline-sign/g)).toHaveLength(1);
    });

    it('does not duplicate a manual addition when its anchor occurs again', () => {
        const rendered = injectExplanationSignImages(
            '<p>Zatrzymaj się. Zatrzymaj się przed przejściem.</p>',
            [{
                code: 'B-20',
                image_url: 'https://media.example.test/b-20.webp',
                match_text: 'Zatrzymaj się',
                placement: 'after',
                reference_key: 'local_override:9',
            }],
        );

        expect(rendered.match(/explanation-inline-sign/g)).toHaveLength(1);
    });
});
