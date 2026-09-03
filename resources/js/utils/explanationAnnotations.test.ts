import { describe, expect, it } from 'vitest';
import {
    annotationsForTargetKind,
    isRenderableAnnotationType,
    resolveImageAnnotations,
    resolveVideoFrameAnnotations,
    type ExplanationAnnotationLike,
} from './explanationAnnotations';

type Annotation = ExplanationAnnotationLike & {
    id: number;
    label?: string | null;
};

const makeAnnotation = (overrides: Partial<Annotation>): Annotation => ({
    id: overrides.id ?? 1,
    target_kind: overrides.target_kind ?? 'question_image',
    frame_time_seconds: overrides.frame_time_seconds ?? null,
    label: overrides.label ?? null,
});

describe('explanationAnnotations', () => {
    it('knows which annotation types are renderable at runtime', () => {
        expect(isRenderableAnnotationType('label')).toBe(true);
        expect(isRenderableAnnotationType('text')).toBe(true);
        expect(isRenderableAnnotationType('circle')).toBe(true);
        expect(isRenderableAnnotationType('arrow')).toBe(true);
        expect(isRenderableAnnotationType('box')).toBe(false);
        expect(isRenderableAnnotationType('')).toBe(false);
        expect(isRenderableAnnotationType(null)).toBe(false);
    });

    it('returns only annotations for selected target kind', () => {
        const annotations: Annotation[] = [
            makeAnnotation({ id: 1, target_kind: 'question_image' }),
            makeAnnotation({ id: 2, target_kind: 'video_frame', frame_time_seconds: 7 }),
            makeAnnotation({ id: 3, target_kind: 'question_image' }),
        ];

        expect(annotationsForTargetKind(annotations, 'question_image').map((annotation) => annotation.id)).toEqual([1, 3]);
        expect(annotationsForTargetKind(annotations, 'video_frame').map((annotation) => annotation.id)).toEqual([2]);
    });

    it('returns empty array for invalid annotations input', () => {
        expect(annotationsForTargetKind(null, 'question_image')).toEqual([]);
        expect(resolveImageAnnotations(null)).toEqual([]);
        expect(resolveVideoFrameAnnotations(undefined)).toEqual([]);
    });

    it('prefers explicit question image annotations when they exist', () => {
        const annotations: Annotation[] = [
            makeAnnotation({ id: 1, target_kind: 'legacy' }),
            makeAnnotation({ id: 2, target_kind: 'question_image' }),
        ];

        expect(resolveImageAnnotations(annotations).map((annotation) => annotation.id)).toEqual([2]);
    });

    it('uses non-video legacy annotations as image fallback', () => {
        const annotations: Annotation[] = [
            makeAnnotation({ id: 20, target_kind: 'legacy' }),
            makeAnnotation({ id: 21, target_kind: '' }),
            makeAnnotation({ id: 22, target_kind: 'video_frame', frame_time_seconds: 6 }),
        ];

        expect(resolveImageAnnotations(annotations).map((annotation) => annotation.id)).toEqual([20, 21]);
    });

    it('prefers dedicated video frame annotations when they exist', () => {
        const annotations: Annotation[] = [
            makeAnnotation({ id: 1, target_kind: 'question_image' }),
            makeAnnotation({ id: 2, target_kind: 'video_frame', frame_time_seconds: 3 }),
        ];

        expect(resolveVideoFrameAnnotations(annotations).map((annotation) => annotation.id)).toEqual([2]);
    });

    it('falls back to question image annotations when video frame annotations are missing', () => {
        const annotations: Annotation[] = [
            makeAnnotation({ id: 10, target_kind: 'question_image' }),
            makeAnnotation({ id: 11, target_kind: 'question_image' }),
        ];

        expect(resolveVideoFrameAnnotations(annotations).map((annotation) => annotation.id)).toEqual([10, 11]);
    });

    it('falls back to legacy non-video annotations when no video frame is present', () => {
        const annotations: Annotation[] = [
            makeAnnotation({ id: 31, target_kind: 'legacy' }),
            makeAnnotation({ id: 32, target_kind: 'video_frame', frame_time_seconds: 4 }),
        ];

        expect(resolveVideoFrameAnnotations([annotations[0]]).map((annotation) => annotation.id)).toEqual([31]);
        expect(resolveVideoFrameAnnotations(annotations).map((annotation) => annotation.id)).toEqual([32]);
    });
});
