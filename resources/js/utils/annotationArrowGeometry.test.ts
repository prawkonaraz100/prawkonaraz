import { describe, expect, it } from 'vitest';
import { computeArrowPercentGeometry } from './annotationArrowGeometry';

describe('annotationArrowGeometry', () => {
    it('computes default arrow geometry in percent space like the annotation editor', () => {
        const geometry = computeArrowPercentGeometry({
            x_percent: 50,
            y_percent: 50,
            arrow_length_percent: null,
            arrow_angle_degrees: null,
            arrow_stroke_percent: null,
            arrow_head_percent: null,
        });

        expect(geometry.startX).toBe(50);
        expect(geometry.startY).toBe(50);
        expect(geometry.endX).toBeCloseTo(62.7279, 4);
        expect(geometry.endY).toBeCloseTo(62.7279, 4);
        expect(geometry.strokeWidth).toBe(1.6);

        const [tip, left, right] = geometry.headPoints.split(' ');
        const [tipX, tipY] = tip.split(',').map(Number);
        const [leftX, leftY] = left.split(',').map(Number);
        const [rightX, rightY] = right.split(',').map(Number);

        expect(tipX).toBeCloseTo(62.7279, 3);
        expect(tipY).toBeCloseTo(62.7279, 3);
        expect(leftX).toBeCloseTo(58.8176, 3);
        expect(leftY).toBeCloseTo(61.6884, 3);
        expect(rightX).toBeCloseTo(61.6884, 3);
        expect(rightY).toBeCloseTo(58.8176, 3);
    });

    it('normalizes angle and clamps geometry bounds', () => {
        const geometry = computeArrowPercentGeometry({
            x_percent: 99,
            y_percent: 2,
            arrow_length_percent: 40,
            arrow_angle_degrees: 450,
            arrow_stroke_percent: 20,
            arrow_head_percent: 50,
        });

        expect(geometry.startX).toBe(99);
        expect(geometry.startY).toBe(2);
        expect(geometry.endX).toBe(99);
        expect(geometry.endY).toBe(42);
        expect(geometry.strokeWidth).toBe(8);
        expect(geometry.headPoints.split(' ').length).toBe(3);
    });

    it('falls back to safe defaults when coordinates are missing', () => {
        const geometry = computeArrowPercentGeometry({
            x_percent: null,
            y_percent: undefined,
            arrow_length_percent: undefined,
            arrow_angle_degrees: undefined,
            arrow_stroke_percent: undefined,
            arrow_head_percent: undefined,
        });

        expect(geometry.startX).toBe(50);
        expect(geometry.startY).toBe(50);
        expect(geometry.strokeWidth).toBe(1.6);
        expect(geometry.endX).toBeGreaterThan(50);
        expect(geometry.endY).toBeGreaterThan(50);
    });

    it('clamps out-of-range start coordinates to visible area', () => {
        const geometry = computeArrowPercentGeometry({
            x_percent: -120,
            y_percent: 999,
            arrow_length_percent: 10,
            arrow_angle_degrees: 180,
            arrow_stroke_percent: 1,
            arrow_head_percent: 4,
        });

        expect(geometry.startX).toBe(0);
        expect(geometry.startY).toBe(100);
        expect(geometry.endX).toBe(0);
        expect(geometry.endY).toBe(100);
    });
});
