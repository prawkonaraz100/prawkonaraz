import { describe, expect, it } from 'vitest';
import {
    injectExplanationSignImages,
    normalizeExplanationPlainText,
    renderExamPromptHtml,
    renderExplanationHtml,
    renderInlineFormattedHtml,
} from './explanationFormatting';

describe('explanationFormatting', () => {
    it('keeps exam prompts plain while preserving escaped text', () => {
        expect(renderExamPromptHtml('Czy [red]**musisz**[/red] ustąpić?'))
            .toBe('Czy musisz ustąpić?');
        expect(renderExamPromptHtml('A < B'))
            .toBe('A &lt; B');
    });

    it('renders nested color and bold markers together', () => {
        const rendered = renderInlineFormattedHtml('[green]**tekst**[/green]', {
            palette: 'classic',
            enableBold: true,
            enableColors: true,
        });

        expect(rendered).toBe('<span data-inline-color="success" style="color:#163222;"><strong>tekst</strong></span>');
    });

    it('renders a training question without leaking markers or applying their colors and bold text', () => {
        expect(renderExamPromptHtml('Czy w tym miejscu [red]**wolno** Ci[/red] **skręcić w lewo** i **wjechać** na **stację paliw**?'))
            .toBe('Czy w tym miejscu wolno Ci skręcić w lewo i wjechać na stację paliw?');
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

        expect(rendered).toBe('<span data-inline-color="success" style="color:#163222;">tekst</span>');
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

        expect(classic).toBe('<span data-inline-color="danger" style="color:#612d2d;">blad</span>');
        expect(zen).toBe('<span data-inline-color="danger" style="color:#4e2727;">blad</span>');
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

describe('repeated sign thumbnails in full study explanations', () => {
    it('adds an image to every code while preserving the code in paragraphs and bold text', () => {
        const html = '<p>C-12 oraz C-12.</p><p><strong>C-12</strong> i c-12.</p>';
        const rendered = injectExplanationSignImages(html, [
            { code: 'C-12', image_url: '/signs/c12.svg', placement: 'after_each' },
        ]);
        expect(rendered.match(/<img /g)).toHaveLength(4);
        expect(rendered.replace(/<[^>]+>/g, '')).toBe('C-12 oraz C-12.C-12 i c-12.');
        expect(rendered).toContain('<strong>C-12<img ');
    });

    it('handles every provided sign and overlapping codes without matching larger codes or words', () => {
        const html = '<p>A-1 A-1 A-10 A-10 B-20 B-20 D-9 D-9 C-12 C-12 C-120 XC-12 C-12a.</p>';
        const rendered = injectExplanationSignImages(html, ['A-1', 'A-10', 'B-20', 'D-9', 'C-12'].map(code => ({
            code, image_url: '/signs/' + code + '.svg', placement: 'after_each' as const,
        })));
        expect(rendered.match(/<img /g)).toHaveLength(10);
        expect(rendered).toContain('C-120 XC-12 C-12a.');
    });

    it('does not modify attributes or recursively decorate inserted image metadata', () => {
        const rendered = injectExplanationSignImages('<p title="C-12">C-12 C-12</p>', [
            { code: 'C-12', image_url: '/C-12.svg', alt_text: 'Znak C-12', placement: 'after_each' },
        ]);
        expect(rendered).toContain('title="C-12"');
        expect(rendered.match(/<img /g)).toHaveLength(2);
        expect(rendered.match(/alt="Znak C-12"/g)).toHaveLength(2);
    });

    it('keeps missing graphics as text and escapes image attributes', () => {
        const rendered = injectExplanationSignImages('<p>D-9 C-12 C-12</p>', [
            { code: 'D-9', image_url: '', placement: 'after_each' },
            { code: 'C-12', image_url: '/sign.svg?label="C-12"', alt_text: 'C-12 "znak"', placement: 'after_each' },
        ]);
        expect(rendered).toContain('<p>D-9 C-12<img ');
        expect(rendered).toContain('src="/sign.svg?label=&quot;C-12&quot;"');
        expect(rendered.match(/<img /g)).toHaveLength(2);
    });

    it('keeps manual single-anchor additions unchanged alongside repeated sign codes', () => {
        const rendered = injectExplanationSignImages('<p>Uwaga. C-12. Uwaga. C-12.</p>', [
            { code: 'B-20', match_text: 'Uwaga', image_url: '/stop.svg', placement: 'after' },
            { code: 'C-12', image_url: '/roundabout.svg', placement: 'after_each' },
        ]);
        expect(rendered.split('src="/stop.svg"')).toHaveLength(2);
        expect(rendered.split('src="/roundabout.svg"')).toHaveLength(3);
    });
});
