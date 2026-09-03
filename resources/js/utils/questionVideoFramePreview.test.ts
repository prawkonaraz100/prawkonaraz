import { describe, expect, it } from 'vitest';
import {
    resolveQuestionVideoFramePreviewPresentationClass,
    shouldShowPosterWhilePreparingQuestionVideoFrame,
    shouldRenderQuestionVideoFramePreview,
} from './questionVideoFramePreview';

describe('questionVideoFramePreview', () => {
    it('renders annotated video frame preview before the video is activated', () => {
        expect(shouldRenderQuestionVideoFramePreview({
            shouldShowVideoFrameAnnotations: true,
            isVideoPlaying: false,
        })).toBe(true);
    });

    it('stops rendering annotated frame preview once the video starts playing', () => {
        expect(shouldRenderQuestionVideoFramePreview({
            shouldShowVideoFrameAnnotations: true,
            isVideoPlaying: true,
        })).toBe(false);
    });

    it('does not render preview when there are no video frame annotations', () => {
        expect(shouldRenderQuestionVideoFramePreview({
            shouldShowVideoFrameAnnotations: false,
            isVideoPlaying: false,
        })).toBe(false);
    });

    it('uses an absolute overlay class only after the real video is activated', () => {
        expect(resolveQuestionVideoFramePreviewPresentationClass({
            isVideoActivated: true,
        })).toBe('absolute inset-0 h-full w-full');
    });

    it('uses a relative standalone class before the real video is activated', () => {
        expect(resolveQuestionVideoFramePreviewPresentationClass({
            isVideoActivated: false,
        })).toBe('relative h-full w-full');
    });

    it('keeps the poster while preparing only before the real video is activated', () => {
        expect(shouldShowPosterWhilePreparingQuestionVideoFrame({
            isVideoActivated: false,
        })).toBe(true);

        expect(shouldShowPosterWhilePreparingQuestionVideoFrame({
            isVideoActivated: true,
        })).toBe(false);
    });
});
