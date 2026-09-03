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
