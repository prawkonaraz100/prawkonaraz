export interface ExplanationAnnotationLike {
    target_kind: string;
    frame_time_seconds: number | null;
}

export const RENDERABLE_ANNOTATION_TYPES = ['label', 'text', 'circle', 'arrow'] as const;

export const isRenderableAnnotationType = (
    annotationType: string | null | undefined,
): boolean => {
    if (typeof annotationType !== 'string') {
        return false;
    }

    return RENDERABLE_ANNOTATION_TYPES.includes(annotationType as (typeof RENDERABLE_ANNOTATION_TYPES)[number]);
};

export const annotationsForTargetKind = <T extends ExplanationAnnotationLike>(
    annotations: T[] | null | undefined,
    targetKind: string,
): T[] => {
    if (!Array.isArray(annotations)) {
        return [];
    }

    return annotations.filter((annotation) => annotation.target_kind === targetKind);
};

export const resolveImageAnnotations = <T extends ExplanationAnnotationLike>(
    annotations: T[] | null | undefined,
): T[] => {
    const imageAnnotations = annotationsForTargetKind(annotations, 'question_image');

    if (imageAnnotations.length > 0) {
        return imageAnnotations;
    }

    if (!Array.isArray(annotations)) {
        return [];
    }

    // Backward compatibility: legacy records might not have target_kind
    // or may use a non-standard value, so we treat "not video_frame"
    // as image-overlay data when explicit image markers are missing.
    return annotations.filter((annotation) => annotation.target_kind !== 'video_frame');
};

export const resolveVideoFrameAnnotations = <T extends ExplanationAnnotationLike>(
    annotations: T[] | null | undefined,
): T[] => {
    const frameAnnotations = annotationsForTargetKind(annotations, 'video_frame');

    if (frameAnnotations.length > 0) {
        return frameAnnotations;
    }

    return resolveImageAnnotations(annotations);
};
