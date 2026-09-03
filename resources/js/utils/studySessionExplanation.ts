interface OnDemandExplanationAvailabilityOptions {
    explanationText?: string | null;
    explanationAssetBody?: string | null;
    explanationAssetImageUrl?: string | null;
}

interface StudySessionExplanationCardOptions {
    showExplanation: boolean;
    showExplanationOnDemand: boolean;
    usesExplanationFeedbackMode: boolean;
    currentAnswerIsCorrect: boolean | null;
    hasOnDemandExplanationContent: boolean;
}

interface StudySessionExplanationToggleOptions {
    isLocalLearningMode: boolean;
    questionStage: 'preview' | 'answer';
    localSessionCompleted: boolean;
    hasOnDemandExplanationContent: boolean;
    isManualExplanationVisible: boolean;
    isAutoExplanationVisible: boolean;
}

interface StudySessionResolvedExplanationOptions<TAsset, TAnnotation> {
    answerExplanation?: string | null;
    questionExplanation?: string | null;
    answerExplanationAsset?: TAsset | null;
    questionExplanationAsset?: TAsset | null;
    answerExplanationAnnotations?: TAnnotation[] | null;
    questionExplanationAnnotations?: TAnnotation[] | null;
}

const hasRenderableValue = (value?: string | null) =>
    typeof value === 'string' && value.trim().length > 0;

export const hasOnDemandExplanationContent = ({
    explanationText,
    explanationAssetBody,
    explanationAssetImageUrl,
}: OnDemandExplanationAvailabilityOptions): boolean =>
    hasRenderableValue(explanationText)
    || hasRenderableValue(explanationAssetBody)
    || hasRenderableValue(explanationAssetImageUrl);

export const shouldShowStudySessionExplanationCard = ({
    showExplanation,
    showExplanationOnDemand,
    usesExplanationFeedbackMode,
    currentAnswerIsCorrect,
    hasOnDemandExplanationContent,
}: StudySessionExplanationCardOptions): boolean => {
    if (!showExplanation) {
        return false;
    }

    if (showExplanationOnDemand) {
        return hasOnDemandExplanationContent;
    }

    return usesExplanationFeedbackMode && currentAnswerIsCorrect === false;
};

export const canToggleStudySessionExplanationOnDemand = ({
    isLocalLearningMode,
    questionStage,
    localSessionCompleted,
    hasOnDemandExplanationContent,
    isManualExplanationVisible,
    isAutoExplanationVisible,
}: StudySessionExplanationToggleOptions): boolean => {
    if (!isLocalLearningMode || localSessionCompleted || questionStage !== 'answer') {
        return false;
    }

    if (isAutoExplanationVisible) {
        return false;
    }

    if (isManualExplanationVisible) {
        return true;
    }

    return hasOnDemandExplanationContent;
};

export const resolveStudySessionExplanation = <TAsset, TAnnotation>({
    answerExplanation,
    questionExplanation,
    answerExplanationAsset,
    questionExplanationAsset,
    answerExplanationAnnotations,
    questionExplanationAnnotations,
}: StudySessionResolvedExplanationOptions<TAsset, TAnnotation>) => ({
    explanation: answerExplanation ?? questionExplanation ?? null,
    explanationAsset: answerExplanationAsset ?? questionExplanationAsset ?? null,
    explanationAnnotations: answerExplanationAnnotations ?? questionExplanationAnnotations ?? [],
});
