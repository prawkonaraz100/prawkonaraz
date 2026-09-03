interface QuestionVideoFramePreviewOptions {
    shouldShowVideoFrameAnnotations: boolean;
    isVideoPlaying: boolean;
}

interface QuestionVideoFramePreviewPresentationClassOptions {
    isVideoActivated: boolean;
}

interface QuestionVideoFramePreviewPreparationOptions {
    isVideoActivated: boolean;
}

export const shouldRenderQuestionVideoFramePreview = ({
    shouldShowVideoFrameAnnotations,
    isVideoPlaying,
}: QuestionVideoFramePreviewOptions): boolean =>
    shouldShowVideoFrameAnnotations && !isVideoPlaying;

export const shouldShowPosterWhilePreparingQuestionVideoFrame = ({
    isVideoActivated,
}: QuestionVideoFramePreviewPreparationOptions): boolean =>
    !isVideoActivated;

export const resolveQuestionVideoFramePreviewPresentationClass = ({
    isVideoActivated,
}: QuestionVideoFramePreviewPresentationClassOptions): string =>
    isVideoActivated
        ? 'absolute inset-0 h-full w-full'
        : 'relative h-full w-full';
