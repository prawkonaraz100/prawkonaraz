import { describe, expect, it } from 'vitest';
import {
    canToggleStudySessionExplanationOnDemand,
    hasOnDemandExplanationContent,
    resolveStudySessionExplanation,
    shouldShowStudySessionExplanationCard,
} from './studySessionExplanation';

describe('studySessionExplanation', () => {
    it('treats text, asset body, or asset image as valid on-demand explanation content', () => {
        expect(hasOnDemandExplanationContent({
            explanationText: 'Wyjasnienie',
        })).toBe(true);

        expect(hasOnDemandExplanationContent({
            explanationAssetBody: 'Dodatkowy opis',
        })).toBe(true);

        expect(hasOnDemandExplanationContent({
            explanationAssetImageUrl: 'http://127.0.0.1:8081/example.png',
        })).toBe(true);
    });

    it('ignores empty strings when checking on-demand explanation content', () => {
        expect(hasOnDemandExplanationContent({
            explanationText: '   ',
            explanationAssetBody: '',
            explanationAssetImageUrl: null,
        })).toBe(false);
    });

    it('allows rendering the explanation card on demand before answering when content exists', () => {
        expect(shouldShowStudySessionExplanationCard({
            showExplanation: true,
            showExplanationOnDemand: true,
            usesExplanationFeedbackMode: false,
            currentAnswerIsCorrect: null,
            hasOnDemandExplanationContent: true,
        })).toBe(true);
    });

    it('preserves automatic explanation display after an incorrect answer', () => {
        expect(shouldShowStudySessionExplanationCard({
            showExplanation: true,
            showExplanationOnDemand: false,
            usesExplanationFeedbackMode: true,
            currentAnswerIsCorrect: false,
            hasOnDemandExplanationContent: false,
        })).toBe(true);
    });

    it('allows toggling back to the question while manual explanation is already open', () => {
        expect(canToggleStudySessionExplanationOnDemand({
            isLocalLearningMode: true,
            questionStage: 'answer',
            localSessionCompleted: false,
            hasOnDemandExplanationContent: false,
            isManualExplanationVisible: true,
            isAutoExplanationVisible: false,
        })).toBe(true);
    });

    it('blocks manual toggling when an automatic incorrect-answer explanation is already visible', () => {
        expect(canToggleStudySessionExplanationOnDemand({
            isLocalLearningMode: true,
            questionStage: 'answer',
            localSessionCompleted: false,
            hasOnDemandExplanationContent: true,
            isManualExplanationVisible: false,
            isAutoExplanationVisible: true,
        })).toBe(false);
    });

    it('prefers answer reveal over the pre-answer question payload', () => {
        const resolved = resolveStudySessionExplanation({
            answerExplanation: 'Wyjaśnienie po odpowiedzi',
            questionExplanation: null,
            answerExplanationAsset: { id: 10, title: 'Reveal asset' },
            questionExplanationAsset: null,
            answerExplanationAnnotations: [{ id: 20, label: 'Reveal annotation' }],
            questionExplanationAnnotations: [],
        });

        expect(resolved.explanation).toBe('Wyjaśnienie po odpowiedzi');
        expect(resolved.explanationAsset).toEqual({ id: 10, title: 'Reveal asset' });
        expect(resolved.explanationAnnotations).toEqual([{ id: 20, label: 'Reveal annotation' }]);
    });
});
