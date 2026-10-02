// Kept separate from regular learning preferences and course session settings.
export const COURSE_KEYBOARD_HELP_DISMISSED_KEY = 'qualification-c-accelerated-keyboard-help-dismissed-v1';

export const isCourseKeyboardHelpDismissed = (storedValue: string | null): boolean => storedValue === '1';
