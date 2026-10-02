interface CourseFlashcardSession {
    mode: string;
    ui_shell: string | null;
    scope: string;
    context?: { explanation_flashcard?: boolean } | null;
}

// Fail closed: neither a category, the Zen shell nor a URL parameter enables this view.
export const usesCourseExplanationFlashcard = (session: CourseFlashcardSession): boolean =>
    session.mode === 'learn'
    && session.ui_shell === 'zen'
    && (session.scope === 'course_module' || session.scope === 'course_review')
    && session.context?.explanation_flashcard === true;

// A separate key prevents course controls from changing regular classic/Zen preferences.
export const COURSE_FLASHCARD_PREFERENCES_KEY = 'qualification-c-accelerated-flashcard-preferences-v1';
export interface CourseFlashcardPreferences {
    explanationMode: 'never' | 'incorrect' | 'always';
    advanceMode: 'automatic' | 'manual';
}
export type CourseExplanationMode = CourseFlashcardPreferences['explanationMode'];
export type CourseAdvanceMode = CourseFlashcardPreferences['advanceMode'];
export const readCourseFlashcardPreferences = (raw: string | null): CourseFlashcardPreferences => {
    try {
        const parsed = raw ? JSON.parse(raw) : null;
        return {
            explanationMode: ['never', 'incorrect', 'always'].includes(parsed?.explanationMode)
                ? parsed.explanationMode : parsed?.afterEveryAnswer === true ? 'always' : 'incorrect',
            advanceMode: parsed?.advanceMode === 'manual' ? 'manual' : 'automatic',
        };
    } catch {
        return { explanationMode: 'incorrect', advanceMode: 'automatic' };
    }
};

export const shouldAutomaticallyTurnCourseFlashcard = (
    session: CourseFlashcardSession,
    mode: CourseExplanationMode,
    answerIsCorrect: boolean | null,
    hasExplanation: boolean,
): boolean => usesCourseExplanationFlashcard(session)
    && answerIsCorrect !== null
    && hasExplanation
    && (mode === 'always' || (mode === 'incorrect' && answerIsCorrect === false));

export type CourseShortcutEvent = Pick<KeyboardEvent,
    'key' | 'code' | 'location' | 'repeat' | 'ctrlKey' | 'metaKey' | 'altKey' | 'shiftKey' | 'isComposing'>;

export const courseNextShortcutKey = (event: CourseShortcutEvent): 'ControlLeft' | 'AltLeft' | 'AltRight' | 'Fn' | null => {
    // macOS exposes Option as Alt; some layouts expose right Alt as AltGraph.
    if (event.code === 'AltLeft' || (event.key === 'Alt' && event.location === 1 && !event.code)) return 'AltLeft';
    if (event.code === 'AltRight' || ((event.key === 'Alt' || event.key === 'AltGraph') && event.location === 2 && !event.code)) return 'AltRight';
    if (event.location === 2) return null;
    if (event.code === 'ControlLeft' || (event.key === 'Control' && event.location === 1 && !event.code)) return 'ControlLeft';
    // Fn is only available on devices that forward it to the browser.
    if (event.code === 'Fn' || event.key === 'Fn') return 'Fn';
    return null;
};

export const shouldAdvanceCourseWithKeyboard = (
    session: CourseFlashcardSession,
    event: CourseShortcutEvent,
    state: { answered: boolean; ready: boolean; blocked: boolean },
): boolean => usesCourseExplanationFlashcard(session)
    && courseNextShortcutKey(event) !== null && !event.repeat && !event.isComposing
    && (courseNextShortcutKey(event) === 'ControlLeft'
        || (courseNextShortcutKey(event) === 'AltRight' && event.key === 'AltGraph')
        || !event.ctrlKey)
    && (courseNextShortcutKey(event) === 'AltLeft' || courseNextShortcutKey(event) === 'AltRight' || !event.altKey)
    && !event.metaKey && !event.shiftKey
    && state.answered && state.ready && !state.blocked;

export const shouldAdvanceCourseExplanationWithArrow = (
    session: CourseFlashcardSession,
    event: CourseShortcutEvent,
    state: { answered: boolean; explanationVisible: boolean; ready: boolean; blocked: boolean },
): boolean => usesCourseExplanationFlashcard(session)
    && (event.key === 'ArrowRight' || event.key.toLowerCase() === 'd') && !event.repeat && !event.isComposing
    && !event.ctrlKey && !event.metaKey && !event.altKey && !event.shiftKey
    && state.answered && state.explanationVisible && state.ready && !state.blocked;

export const resolveCourseAdvanceDelay = (
    session: CourseFlashcardSession, mode: CourseAdvanceMode, showingExplanation: boolean,
    readingDelay: number, feedbackDelay: number, audioDelay: number,
): number | null => !usesCourseExplanationFlashcard(session) || mode !== 'automatic'
    ? null : Math.max(showingExplanation ? readingDelay : feedbackDelay, audioDelay);

