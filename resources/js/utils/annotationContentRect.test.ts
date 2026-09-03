import { describe, expect, it } from 'vitest';
import { computeContainContentRect } from '@/utils/annotationContentRect';

describe('computeContainContentRect', () => {
    it('centers content when object-position is default', () => {
        const rect = computeContainContentRect({
            containerWidth: 800,
            containerHeight: 600,
            naturalWidth: 1920,
            naturalHeight: 1080,
            objectPosition: '50% 50%',
        });

        expect(rect.width).toBeCloseTo(800, 5);
        expect(rect.height).toBeCloseTo(450, 5);
        expect(rect.left).toBeCloseTo(0, 5);
        expect(rect.top).toBeCloseTo(75, 5);
    });

    it('aligns content to top when object-position top is used', () => {
        const rect = computeContainContentRect({
            containerWidth: 800,
            containerHeight: 600,
            naturalWidth: 1920,
            naturalHeight: 1080,
            objectPosition: '50% 0%',
        });

        expect(rect.width).toBeCloseTo(800, 5);
        expect(rect.height).toBeCloseTo(450, 5);
        expect(rect.left).toBeCloseTo(0, 5);
        expect(rect.top).toBeCloseTo(0, 5);
    });

    it('supports keyword-only object-position values', () => {
        const topRect = computeContainContentRect({
            containerWidth: 800,
            containerHeight: 600,
            naturalWidth: 1920,
            naturalHeight: 1080,
            objectPosition: 'top',
        });
        const rightRect = computeContainContentRect({
            containerWidth: 900,
            containerHeight: 600,
            naturalWidth: 800,
            naturalHeight: 600,
            objectPosition: 'right',
        });

        expect(topRect.top).toBeCloseTo(0, 5);
        expect(rightRect.left).toBeCloseTo(100, 5);
    });

    it('supports explicit keyword pairs for both axes', () => {
        const rect = computeContainContentRect({
            containerWidth: 900,
            containerHeight: 600,
            naturalWidth: 800,
            naturalHeight: 400,
            objectPosition: 'left bottom',
        });

        expect(rect.left).toBeCloseTo(0, 5);
        expect(rect.top).toBeCloseTo(150, 5);
    });

    it('supports pixel object-position offsets and clamps outside range', () => {
        const pxRect = computeContainContentRect({
            containerWidth: 800,
            containerHeight: 600,
            naturalWidth: 1920,
            naturalHeight: 1080,
            objectPosition: '0px 30px',
        });
        const clampedRect = computeContainContentRect({
            containerWidth: 800,
            containerHeight: 600,
            naturalWidth: 1920,
            naturalHeight: 1080,
            objectPosition: '300% -40%',
        });

        expect(pxRect.left).toBeCloseTo(0, 5);
        expect(pxRect.top).toBeCloseTo(30, 5);
        expect(clampedRect.left).toBeCloseTo(0, 5);
        expect(clampedRect.top).toBeCloseTo(0, 5);
    });

    it('falls back to centered alignment for unsupported tokens', () => {
        const rect = computeContainContentRect({
            containerWidth: 900,
            containerHeight: 600,
            naturalWidth: 800,
            naturalHeight: 400,
            objectPosition: 'inherit',
        });

        expect(rect.left).toBeCloseTo(0, 5);
        expect(rect.top).toBeCloseTo(75, 5);
    });

    it('returns zero rect for invalid inputs', () => {
        const rect = computeContainContentRect({
            containerWidth: 0,
            containerHeight: 600,
            naturalWidth: 1920,
            naturalHeight: 1080,
            objectPosition: '50% 50%',
        });

        expect(rect).toEqual({
            left: 0,
            top: 0,
            width: 0,
            height: 0,
        });
    });
});
