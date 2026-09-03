import {
    findNextUnansweredRankedQuestionId,
    getRankedQuestionResponseTimeMs,
    resolveActiveRankedQuestionId,
} from '@/utils/rankedMatchQuestionFlow';
import { describe, expect, it } from 'vitest';

const question = (id: number, isAnswered = false) => ({
    id,
    is_answered: isAnswered,
});

describe('rankedMatchQuestionFlow', () => {
    it('chooses the first unanswered question when no selection exists', () => {
        expect(resolveActiveRankedQuestionId([
            question(1, true),
            question(2, false),
            question(3, false),
        ], null)).toBe(2);
    });

    it('keeps the current selection when the question still exists', () => {
        expect(resolveActiveRankedQuestionId([
            question(1, true),
            question(2, false),
            question(3, false),
        ], 3)).toBe(3);
    });

    it('wraps to another unanswered question after the current one was answered', () => {
        expect(findNextUnansweredRankedQuestionId([
            question(1, true),
            question(2, true),
            question(3, false),
            question(4, false),
        ], 4)).toBe(3);
    });

    it('moves forward automatically when the current question was already answered', () => {
        expect(resolveActiveRankedQuestionId([
            question(10, true),
            question(11, true),
            question(12, false),
        ], 11)).toBe(12);
    });

    it('keeps the answered question only when there is nothing left to solve', () => {
        expect(resolveActiveRankedQuestionId([
            question(10, true),
            question(11, true),
        ], 11)).toBe(11);
    });

    it('calculates response time from the moment the question was opened', () => {
        expect(getRankedQuestionResponseTimeMs(1_000, 2_650)).toBe(1650);
        expect(getRankedQuestionResponseTimeMs(1_000, 1_050)).toBe(250);
        expect(getRankedQuestionResponseTimeMs(undefined, 2_000)).toBe(1200);
    });
});
