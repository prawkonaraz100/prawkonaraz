export interface QuestionMediaSourceLike {
    kind?: string | null;
    url?: string | null;
    full_url?: string | null;
    thumb_url?: string | null;
    poster_url?: string | null;
}

export const resolveStudyQuestionImageUrl = (
    media: QuestionMediaSourceLike,
    preferFullForAnnotations = false,
): string => {
    if (preferFullForAnnotations) {
        return media.full_url ?? media.url ?? media.thumb_url ?? '';
    }

    return media.thumb_url ?? media.url ?? media.full_url ?? '';
};

export const resolveResultMediaPreviewUrl = (media: QuestionMediaSourceLike): string => {
    if ((media.kind ?? null) === 'video') {
        return media.poster_url ?? media.thumb_url ?? media.url ?? '';
    }

    return media.full_url ?? media.thumb_url ?? media.url ?? '';
};
