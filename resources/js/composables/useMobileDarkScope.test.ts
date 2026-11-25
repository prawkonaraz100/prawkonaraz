import { afterEach, describe, expect, it, vi } from 'vitest';

const mounted: Array<() => void> = [];
const unmounted: Array<() => void> = [];

vi.mock('vue', () => ({
    onMounted: (callback: () => void) => mounted.push(callback),
    onBeforeUnmount: (callback: () => void) => unmounted.push(callback),
}));

const { useMobileDarkScope } = await import('./useMobileDarkScope');

afterEach(() => {
    mounted.length = 0;
    unmounted.length = 0;
    vi.unstubAllGlobals();
});

describe('useMobileDarkScope', () => {
    it('keeps the body scope until the last user unmounts (layout swap zen -> exam)', () => {
        const classes = new Set<string>();
        vi.stubGlobal('document', {
            body: { classList: { add: (name: string) => classes.add(name), remove: (name: string) => classes.delete(name) } },
        });

        useMobileDarkScope();
        useMobileDarkScope();
        mounted.forEach(callback => callback());
        expect(classes.has('pk-dark-scope')).toBe(true);

        unmounted[0]();
        expect(classes.has('pk-dark-scope')).toBe(true);

        unmounted[1]();
        expect(classes.has('pk-dark-scope')).toBe(false);
    });
});
