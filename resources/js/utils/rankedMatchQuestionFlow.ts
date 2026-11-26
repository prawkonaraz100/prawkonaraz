export interface RankedMatchQuestionLike {
    id: number;
    is_answered: boolean;
}

export const findNextUnansweredRankedQuestionId = (
    questions: RankedMatchQuestionLike[],
    fromQuestionId: number | null = null,
): number | null => {
    if (questions.length === 0) {
        return null;
    }

    const startIndex = fromQuestionId === null
        ? -1
        : questions.findIndex((question) => question.id === fromQuestionId);

    for (let index = startIndex + 1; index < questions.length; index += 1) {
        if (!questions[index]?.is_answered) {
            return questions[index]?.id ?? null;
        }
    }

    for (let index = 0; index <= startIndex; index += 1) {
        if (!questions[index]?.is_answered) {
            return questions[index]?.id ?? null;
        }
    }

    return null;
};

export const resolveActiveRankedQuestionId = (
    questions: RankedMatchQuestionLike[],
    selectedQuestionId: number | null,
): number | null => {
    if (questions.length === 0) {
        return null;
    }

    if (selectedQuestionId !== null) {
        const selectedQuestion = questions.find((question) => question.id === selectedQuestionId);

        if (selectedQuestion && !selectedQuestion.is_answered) {
            return selectedQuestionId;
        }

        if (selectedQuestion) {
            return findNextUnansweredRankedQuestionId(questions, selectedQuestionId) ?? selectedQuestionId;
        }
    }

    return findNextUnansweredRankedQuestionId(questions) ?? questions[0]?.id ?? null;
};

export const getRankedQuestionResponseTimeMs = (
    questionStartedAtMs: number | null | undefined,
    nowMs: number = Date.now(),
): number => {
    if (typeof questionStartedAtMs !== 'number') {
        return 1200;
    }

    return Math.max(nowMs - questionStartedAtMs, 250);
};

export interface RankedPacedQuestionLike {
    is_answered: boolean;
    available_at?: string | null;
}

/**
 * Ile ms zostało do odsłonięcia pytania w meczu z tempem (0 = można odpowiadać).
 * serverClockOffsetMs = zegar serwera minus zegar przeglądarki.
 */
export const getRankedQuestionLockRemainingMs = (
    question: RankedPacedQuestionLike | null | undefined,
    nowMs: number,
    serverClockOffsetMs = 0,
): number => {
    if (!question || question.is_answered || !question.available_at) {
        return 0;
    }

    const availableAtMs = Date.parse(question.available_at);

    if (!Number.isFinite(availableAtMs)) {
        return 0;
    }

    return Math.max(availableAtMs - (nowMs + serverClockOffsetMs), 0);
};

/**
 * Czas odsłonięcia pytania: z serwera (available_at) albo wyliczony ze startu meczu i tempa,
 * gdy szczegóły pobrano jeszcze w trakcie odliczania (wtedy available_at jest puste).
 */
export const resolveRankedQuestionAvailableAt = (
    question: { available_at?: string | null; question_number: number } | null | undefined,
    matchStartedAt: string | null | undefined,
    questionIntervalSeconds: number,
): string | null => {
    if (!question) {
        return null;
    }

    if (question.available_at) {
        return question.available_at;
    }

    const startedAtMs = matchStartedAt ? Date.parse(matchStartedAt) : Number.NaN;

    if (questionIntervalSeconds <= 0 || !Number.isFinite(startedAtMs)) {
        return null;
    }

    return new Date(startedAtMs + (question.question_number - 1) * questionIntervalSeconds * 1000).toISOString();
};
