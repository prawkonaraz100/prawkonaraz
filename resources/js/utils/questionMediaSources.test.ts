import { describe, expect, it } from 'vitest';
import {
    resolveResultMediaPreviewUrl,
    resolveStudyQuestionImageUrl,
} from '@/utils/questionMediaSources';

describe('resolveStudyQuestionImageUrl', () => {
    it('prefers full image when annotations are visible', () => {
        const url = resolveStudyQuestionImageUrl({
            full_url: 'full.webp',
            thumb_url: 'thumb.webp',
            url: 'url.webp',
        }, true);

        expect(url).toBe('full.webp');
    });

    it('prefers thumb in regular study flow without annotations', () => {
        const url = resolveStudyQuestionImageUrl({
            full_url: 'full.webp',
            thumb_url: 'thumb.webp',
            url: 'url.webp',
        }, false);

        expect(url).toBe('thumb.webp');
    });

    it('falls back to available image variant', () => {
        expect(resolveStudyQuestionImageUrl({ url: 'url.webp' }, true)).toBe('url.webp');
        expect(resolveStudyQuestionImageUrl({ full_url: 'full.webp' }, false)).toBe('full.webp');
    });

    it('returns empty string when no image source is available', () => {
        expect(resolveStudyQuestionImageUrl({}, true)).toBe('');
        expect(resolveStudyQuestionImageUrl({}, false)).toBe('');
    });
});

describe('resolveResultMediaPreviewUrl', () => {
    it('prefers poster for video media', () => {
        const url = resolveResultMediaPreviewUrl({
            kind: 'video',
            poster_url: 'poster.webp',
            thumb_url: 'thumb.webp',
            url: 'video.mp4',
        });

        expect(url).toBe('poster.webp');
    });

    it('prefers full for image media in results', () => {
        const url = resolveResultMediaPreviewUrl({
            kind: 'image',
            full_url: 'full.webp',
            thumb_url: 'thumb.webp',
            url: 'url.webp',
        });

        expect(url).toBe('full.webp');
    });

    it('falls back correctly when video poster is missing', () => {
        expect(resolveResultMediaPreviewUrl({
            kind: 'video',
            thumb_url: 'thumb.webp',
            url: 'video.mp4',
        })).toBe('thumb.webp');

        expect(resolveResultMediaPreviewUrl({
            kind: 'video',
            url: 'video.mp4',
        })).toBe('video.mp4');
    });

    it('returns empty string when result preview source is unavailable', () => {
        expect(resolveResultMediaPreviewUrl({
            kind: 'image',
        })).toBe('');
    });
});
