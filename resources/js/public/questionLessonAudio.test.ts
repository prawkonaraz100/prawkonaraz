import { describe, expect, it } from 'vitest';
import {
    normalizeLessonAudioText,
    resolvePolishVoice,
} from './questionLessonAudio';

const voice = (
    overrides: Partial<SpeechSynthesisVoice>,
): SpeechSynthesisVoice => ({
    default: false,
    lang: 'en-US',
    localService: false,
    name: 'Default',
    voiceURI: 'default',
    ...overrides,
});

describe('questionLessonAudio', () => {
    it('normalizes whitespace for speech synthesis', () => {
        expect(normalizeLessonAudioText('  Pierwsze\n\n zdanie.   Drugie\u00a0zdanie. '))
            .toBe('Pierwsze. zdanie. Drugie zdanie.');

        expect(normalizeLessonAudioText('Pierwsze zdanie.\nDrugie zdanie.'))
            .toBe('Pierwsze zdanie. Drugie zdanie.');
    });

    it('prefers a better named pl-PL voice when available', () => {
        const polishAdam = voice({
            lang: 'pl-PL',
            localService: true,
            name: 'Microsoft Adam - Polish (Poland)',
            voiceURI: 'polish-adam',
        });
        const polishPaulina = voice({
            lang: 'pl-PL',
            localService: true,
            name: 'Microsoft Paulina - Polish (Poland)',
            voiceURI: 'polish-paulina',
        });

        expect(resolvePolishVoice([
            voice({ lang: 'en-US', name: 'English' }),
            polishAdam,
            polishPaulina,
        ])).toBe(polishPaulina);
    });

    it('prefers a natural Polish voice over a basic local voice', () => {
        const polishAdam = voice({
            lang: 'pl-PL',
            localService: true,
            name: 'Microsoft Adam - Polish (Poland)',
            voiceURI: 'polish-adam',
        });
        const polishNatural = voice({
            lang: 'pl-PL',
            localService: false,
            name: 'Microsoft Zofia Online (Natural) - Polish (Poland)',
            voiceURI: 'polish-natural',
        });

        expect(resolvePolishVoice([
            polishAdam,
            polishNatural,
        ])).toBe(polishNatural);
    });

    it('falls back to a Polish-looking voice name when language metadata is missing', () => {
        const polishByName = voice({
            lang: '',
            name: 'Microsoft Polish',
            voiceURI: 'polish-by-name',
        });

        expect(resolvePolishVoice([
            voice({ lang: 'en-US', name: 'English' }),
            polishByName,
        ])).toBe(polishByName);
    });
});
