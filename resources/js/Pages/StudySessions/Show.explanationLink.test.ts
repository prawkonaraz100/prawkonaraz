import { describe, expect, it } from 'vitest';
import source from './Show.vue?raw';

const action = source.match(/<div\s+v-if="shouldShowExplanationCard && publicExplanationUrl"[\s\S]*?<\/a>\s*<\/div>/)?.[0] ?? '';

describe('full explanation action in the regular study and demo view', () => {
    it('places the mobile action after the explanation, in normal flow with spacing', () => {
        expect(action).toContain('data-full-explanation-action');
        expect(action).toMatch(/:class="isPhoneViewport\s+\? 'mt-3 flex shrink-0'/);
        expect(source.indexOf(action)).toBeGreaterThan(source.indexOf('key="hint"'));
        expect(source.slice(source.indexOf('key="hint"'), source.indexOf(action))).toContain('<QuestionExplanationRuntimeBlock');
    });

    it('keeps a comfortable mobile touch target and a non-shrinking icon', () => {
        expect(action).toContain('min-h-[44px]');
        expect(action).toContain('max-w-full');
        expect(action).toContain('text-sm font-semibold leading-5');
        expect(action).toContain('<ExternalLink class="shrink-0" :size="isPhoneViewport ? 15 : 14"');
        expect(action).toContain('focus-visible:outline-2');
    });

    it('shows a full-width secondary button on phones (dark theme turns bg-white buttons into secondary buttons)', () => {
        const mobileLink = action.match(/\? '(min-h-\[44px\][^']+)'/)?.[1] ?? '';
        expect(mobileLink).toContain('w-full');
        expect(mobileLink).toContain('bg-white');
        expect(mobileLink).toContain('rounded-[10px]');
        expect(action).not.toContain('border-t');
    });

    it('preserves the desktop placement and public destination in a safe new tab', () => {
        expect(action).toContain(": 'absolute right-0 bottom-0 z-10'");
        expect(action).toContain(':href="publicExplanationUrl"');
        expect(action).toContain('target="_blank"');
        expect(action).toContain('rel="noopener noreferrer"');
        expect(action).not.toContain('@click');
    });
});
