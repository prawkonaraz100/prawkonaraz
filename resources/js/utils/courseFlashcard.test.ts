import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { describe, expect, it } from 'vitest';
import studySessionSource from '../Pages/StudySessions/Show.vue?raw';
import { parse } from '@vue/compiler-sfc';
import { parse as parseTemplate, NodeTypes, type ElementNode, type RootNode, type TemplateChildNode } from '@vue/compiler-dom';
import CourseExplanationFlashcard from '../Components/CourseExplanationFlashcard.vue';
import CourseFlashcardSettings from '../Components/CourseFlashcardSettings.vue';
import CourseKeyboardHelp from '../Components/CourseKeyboardHelp.vue';
import { COURSE_KEYBOARD_HELP_DISMISSED_KEY, isCourseKeyboardHelpDismissed } from './courseKeyboardHelp';
import { usesCourseExplanationFlashcard, readCourseFlashcardPreferences, shouldAutomaticallyTurnCourseFlashcard,
    shouldAdvanceCourseWithKeyboard, shouldAdvanceCourseExplanationWithArrow,
    resolveCourseAdvanceDelay, type CourseExplanationMode, type CourseAdvanceMode } from './courseFlashcard';

const course = {
    mode: 'learn', ui_shell: 'zen', scope: 'course_module',
    context: { explanation_flashcard: true },
};

describe('isolated course explanation flashcard', () => {
    it('bounds both course faces in a shared viewport grid without hiding answer tiles', () => {
        expect(studySessionSource).toContain("usesCourseFlashcard.value\n        ? 'course-session-workspace'");
        expect(studySessionSource).toContain("return 'course-session-content'");
        expect(studySessionSource).toContain('grid-template-rows: minmax(0, 1fr) auto');
        expect(studySessionSource).toContain('!localSessionCompleted.value && !usesCourseFlashcard.value');
        expect(studySessionSource).not.toContain('v-show="!usesCourseFlashcard || !courseFlashcardBackVisible"');
    });
    it('keeps responsive sizing and normal-flow answer docking behind the course guard', () => {
        for (const [name, result] of [
            ['workspaceHeightClass', "'course-session-workspace'"],
            ['activeQuestionShellClass', "'course-session-shell max-w-[78rem]'"],
            ['activeQuestionInnerClass', "'course-session-inner'"],
            ['mediaFrameClass', "'h-full min-h-0'"],
        ]) {
            expect(studySessionSource).toContain(`const ${name} = computed(() =>\n    usesCourseFlashcard.value\n        ? ${result}`);
        }
        expect(studySessionSource).toContain("if (usesCourseFlashcard.value) {\n        return 'course-session-answer-dock';");
        expect(studySessionSource).toContain("usesCourseFlashcard ? 'course-session-answer-options' : undefined");
    });
    it('keeps course answer feedback rings inside the scrollable answer tiles', () => {
        expect(studySessionSource).toContain('.course-session-answer-options [data-course-answer] { --tw-ring-inset: inset; }');
        expect(studySessionSource).toContain('ring-2 ring-[#2f7d4a]');
        expect(studySessionSource).toContain('ring-2 ring-[#8a4949]');
    });
    it('hides only regular audio, hints and video settings in the course sidebar', () => {
        const template = parse(studySessionSource).descriptor.template;
        expect(template).not.toBeNull();
        const sections: ElementNode[] = [];
        const collectSections = (node: RootNode | TemplateChildNode) => {
            if (node.type === NodeTypes.ELEMENT && node.tag === 'section') sections.push(node);
            if (node.type === NodeTypes.ROOT || node.type === NodeTypes.ELEMENT) {
                node.children.forEach(collectSections);
            }
        };
        collectSections(parseTemplate(template!.content));
        for (const [heading, condition] of [
            ['Czytanie pytania na głos', '!usesCourseFlashcard && canUseQuestionAudio'],
            ['Wskazówki do pytania', '!usesCourseFlashcard'],
            ['Pytania z filmem', '!usesCourseFlashcard'],
        ]) {
            const section = sections.filter((node) => node.loc.source.includes(heading))
                .sort((a, b) => a.loc.source.length - b.loc.source.length)[0];
            expect(section, heading).toBeDefined();
            const visibility = section.props.find((prop) => prop.type === NodeTypes.DIRECTIVE && prop.name === 'if');
            expect(visibility?.type === NodeTypes.DIRECTIVE ? visibility.exp?.loc.source : null, heading).toBe(condition);
        }
    });
    it('automatically shows course keyboard help unless explicitly dismissed in separate storage', () => {
        expect(COURSE_KEYBOARD_HELP_DISMISSED_KEY).toBe('qualification-c-accelerated-keyboard-help-dismissed-v1');
        expect(isCourseKeyboardHelpDismissed('1')).toBe(true);
        for (const value of [null, '', '0', 'false', 'true', '{}', 'invalid']) {
            expect(isCourseKeyboardHelpDismissed(value)).toBe(false);
        }
    });
    it('shows course keyboard help with correct three-answer mappings and flashcard navigation', async () => {
        const context: { teleports?: Record<string, string> } = {};
        const html = await renderToString(createSSRApp({
            render: () => h(CourseKeyboardHelp, { open: true, answerCount: 3 }),
        }), context);
        expect(html).toContain('aria-haspopup="dialog"');
        const popup = context.teleports?.body ?? '';
        expect(popup).toContain('aria-labelledby="course-keyboard-help-title"');
        for (const text of ['Odpowiedź A', 'Odpowiedź B', 'Odpowiedź C', 'D lub prawa strzałka przechodzi dalej.', 'lewy Ctrl', 'lewy albo prawy Alt', 'Option (⌥)', 'bez łączenia go z innymi', 'Nie pokazuj więcej', 'Rozumiem']) {
            expect(popup).toContain(text);
        }
        expect(popup).toMatch(/<kbd\b[^>]*>A<\/kbd> lub <kbd\b[^>]*>←<\/kbd>/);
        expect(popup).toMatch(/<kbd\b[^>]*>S<\/kbd> lub <kbd\b[^>]*>↓<\/kbd>/);
        expect(popup).toMatch(/<kbd\b[^>]*>D<\/kbd> lub <kbd\b[^>]*>→<\/kbd>/);
    });

    it('keeps server-rendered help closed before mounting and adapts to boolean questions', async () => {
        const closed: { teleports?: Record<string, string> } = {};
        await renderToString(createSSRApp({ render: () => h(CourseKeyboardHelp, { open: false, answerCount: 3 }) }), closed);
        expect(closed.teleports?.body).not.toContain('course-keyboard-help-popup');
        const context: { teleports?: Record<string, string> } = {};
        await renderToString(createSSRApp({ render: () => h(CourseKeyboardHelp, { open: true, answerCount: 2 }) }), context);
        expect(context.teleports?.body).toMatch(/<strong\b[^>]*>TAK<\/strong>/);
        expect(context.teleports?.body).toMatch(/<strong\b[^>]*>NIE<\/strong>/);
        expect(context.teleports?.body).not.toContain('Odpowiedź C');
    });

    it('defaults to errors and automatic navigation, tolerating invalid storage', () => {
        for (const raw of [null, '', 'invalid', '{}', 'null', '{"afterEveryAnswer":"true"}']) {
            expect(readCourseFlashcardPreferences(raw)).toEqual({ explanationMode: 'incorrect', advanceMode: 'automatic' });
        }
        expect(readCourseFlashcardPreferences('{"afterEveryAnswer":true}')).toEqual({ explanationMode: 'always', advanceMode: 'automatic' });
        expect(readCourseFlashcardPreferences('{"explanationMode":"never","advanceMode":"manual"}'))
            .toEqual({ explanationMode: 'never', advanceMode: 'manual' });
        expect(readCourseFlashcardPreferences('{"explanationMode":"invalid","advanceMode":false}'))
            .toEqual({ explanationMode: 'incorrect', advanceMode: 'automatic' });
    });

    it('covers all six independent setting combinations for correct and incorrect answers', () => {
        for (const scope of ['course_module', 'course_review']) {
            const session = { ...course, scope };
            for (const explanationMode of ['never', 'incorrect', 'always'] as CourseExplanationMode[]) {
                for (const advanceMode of ['automatic', 'manual'] as CourseAdvanceMode[]) {
                    for (const correct of [true, false]) {
                        const show = shouldAutomaticallyTurnCourseFlashcard(session, explanationMode, correct, true);
                        expect(show).toBe(explanationMode === 'always' || (explanationMode === 'incorrect' && !correct));
                        expect(resolveCourseAdvanceDelay(session, advanceMode, show, 5000, 600, 0))
                            .toBe(advanceMode === 'manual' ? null : show ? 5000 : 600);
                        expect(shouldAutomaticallyTurnCourseFlashcard(session, explanationMode, correct, false)).toBe(false);
                    }
                    // A new unanswered question must not reveal the explanation.
                    expect(shouldAutomaticallyTurnCourseFlashcard(session, explanationMode, null, true)).toBe(false);
                }
            }
        }
    });

    it('never applies the every-answer preference to regular learning or an unapproved course', () => {
        for (const ui_shell of ['zen', 'exam_like']) {
            const regular = { ...course, ui_shell, scope: 'regular_category' };
            expect(shouldAutomaticallyTurnCourseFlashcard(regular, 'always', true, true)).toBe(false);
            expect(resolveCourseAdvanceDelay(regular, 'automatic', true, 5000, 600, 0)).toBeNull();
        }
        expect(shouldAutomaticallyTurnCourseFlashcard({ ...course, context: null }, 'always', true, true)).toBe(false);
    });

    it('accepts left Ctrl on keydown only after an answer, once ready and outside controls or side panels', () => {
        const event = { key: 'Control', code: 'ControlLeft', location: 1, repeat: false, ctrlKey: true, metaKey: false, altKey: false, shiftKey: false, isComposing: false };
        const state = { answered: true, ready: true, blocked: false };
        expect(shouldAdvanceCourseWithKeyboard(course, event, state)).toBe(true);
        for (const key of ['repeat', 'metaKey', 'altKey', 'shiftKey', 'isComposing']) {
            expect(shouldAdvanceCourseWithKeyboard(course, { ...event, [key]: true }, state)).toBe(false);
        }
        for (const key of ['Enter', ' ', 'd', 'ArrowRight', 'a']) {
            expect(shouldAdvanceCourseWithKeyboard(course, { ...event, key, code: '', location: 0 }, state)).toBe(false);
        }
        expect(shouldAdvanceCourseWithKeyboard(course, { ...event, code: 'ControlRight', location: 2 }, state)).toBe(false);
        expect(shouldAdvanceCourseWithKeyboard(course, { ...event, code: '', location: 0 }, state)).toBe(false);
        for (const patch of [{ answered: false }, { ready: false }, { blocked: true }]) {
            expect(shouldAdvanceCourseWithKeyboard(course, event, { ...state, ...patch })).toBe(false);
        }
        expect(shouldAdvanceCourseWithKeyboard({ ...course, scope: 'regular_category' }, event, state)).toBe(false);
    });

    it('accepts both Alt / macOS Option keys, including right AltGr, on keydown', () => {
        const event = { key: 'Alt', code: 'AltLeft', location: 1, repeat: false, ctrlKey: false, metaKey: false, altKey: true, shiftKey: false, isComposing: false };
        const state = { answered: true, ready: true, blocked: false };
        for (const alt of [{ code: 'AltLeft', location: 1 }, { code: 'AltRight', location: 2 }]) {
            const altEvent = { ...event, ...alt };
            for (const scope of ['course_module', 'course_review']) {
                expect(shouldAdvanceCourseWithKeyboard({ ...course, scope }, altEvent, state)).toBe(true);
            }
            expect(shouldAdvanceCourseWithKeyboard(course, { ...altEvent, code: '' }, state)).toBe(true);
            for (const key of ['ctrlKey', 'metaKey', 'shiftKey', 'repeat', 'isComposing']) {
                expect(shouldAdvanceCourseWithKeyboard(course, { ...altEvent, [key]: true }, state)).toBe(false);
            }
            for (const patch of [{ answered: false }, { ready: false }, { blocked: true }]) {
                expect(shouldAdvanceCourseWithKeyboard(course, altEvent, { ...state, ...patch })).toBe(false);
            }
            for (const ui_shell of ['zen', 'exam_like']) {
                expect(shouldAdvanceCourseWithKeyboard({ ...course, ui_shell, scope: 'regular_category' }, altEvent, state)).toBe(false);
            }
        }
        for (const ctrlKey of [false, true]) {
            const altGr = { ...event, key: 'AltGraph', code: 'AltRight', location: 2, ctrlKey };
            expect(shouldAdvanceCourseWithKeyboard(course, altGr, state)).toBe(true);
            expect(shouldAdvanceCourseWithKeyboard(course, { ...altGr, code: '' }, state)).toBe(true);
            for (const key of ['metaKey', 'shiftKey', 'repeat', 'isComposing']) {
                expect(shouldAdvanceCourseWithKeyboard(course, { ...altGr, [key]: true }, state)).toBe(false);
            }
            expect(shouldAdvanceCourseWithKeyboard(course, altGr, { ...state, blocked: true })).toBe(false);
            expect(shouldAdvanceCourseWithKeyboard(course, altGr, { ...state, answered: false })).toBe(false);
            expect(shouldAdvanceCourseWithKeyboard({ ...course, scope: 'regular_category' }, altGr, state)).toBe(false);
        }
        expect(shouldAdvanceCourseWithKeyboard(course, { ...event, code: '', location: 0 }, state)).toBe(false);
    });

    it('accepts a browser-delivered Fn, but not a right Fn or a modified Fn combination', () => {
        const event = { key: 'Fn', code: 'Fn', location: 0, repeat: false, ctrlKey: false, metaKey: false, altKey: false, shiftKey: false, isComposing: false };
        const state = { answered: true, ready: true, blocked: false };
        expect(shouldAdvanceCourseWithKeyboard(course, event, state)).toBe(true);
        expect(shouldAdvanceCourseWithKeyboard(course, { ...event, location: 1 }, state)).toBe(true);
        expect(shouldAdvanceCourseWithKeyboard(course, { ...event, location: 2 }, state)).toBe(false);
        expect(shouldAdvanceCourseWithKeyboard(course, { ...event, ctrlKey: true }, state)).toBe(false);
        expect(shouldAdvanceCourseWithKeyboard(course, { ...event, repeat: true }, state)).toBe(false);
    });

    it.each(['ArrowRight', 'd', 'D'])('advances with %s only from an answered explanation in the approved course', (key) => {
        const event = { key, code: key === 'ArrowRight' ? 'ArrowRight' : 'KeyD', location: 0, repeat: false, ctrlKey: false, metaKey: false, altKey: false, shiftKey: false, isComposing: false };
        const state = { answered: true, explanationVisible: true, ready: true, blocked: false };
        for (const scope of ['course_module', 'course_review']) {
            expect(shouldAdvanceCourseExplanationWithArrow({ ...course, scope }, event, state)).toBe(true);
        }
        for (const key of ['ctrlKey', 'metaKey', 'altKey', 'shiftKey', 'repeat', 'isComposing']) {
            expect(shouldAdvanceCourseExplanationWithArrow(course, { ...event, [key]: true }, state)).toBe(false);
        }
        for (const key of ['ArrowLeft', 'ArrowDown', 'ArrowUp', 'a', 's', 'Enter']) {
            expect(shouldAdvanceCourseExplanationWithArrow(course, { ...event, key }, state)).toBe(false);
        }
        for (const patch of [{ answered: false }, { explanationVisible: false }, { ready: false }, { blocked: true }]) {
            expect(shouldAdvanceCourseExplanationWithArrow(course, event, { ...state, ...patch })).toBe(false);
        }
        for (const ui_shell of ['zen', 'exam_like']) {
            expect(shouldAdvanceCourseExplanationWithArrow({ ...course, scope: 'regular_category', ui_shell }, event, state)).toBe(false);
        }
        expect(shouldAdvanceCourseExplanationWithArrow({ ...course, context: null }, event, state)).toBe(false);
    });

    it.each(['ArrowRight', 'd', 'D'])('does not reuse %s advancement on a new question or a held-key repeat', (key) => {
        const event = { key, code: key === 'ArrowRight' ? 'ArrowRight' : 'KeyD', location: 0, repeat: false, ctrlKey: false, metaKey: false, altKey: false, shiftKey: false, isComposing: false };
        const explanation = { answered: true, explanationVisible: true, ready: true, blocked: false };
        expect(shouldAdvanceCourseExplanationWithArrow(course, event, explanation)).toBe(true);
        const nextQuestion = { ...explanation, answered: false, explanationVisible: false };
        expect(shouldAdvanceCourseExplanationWithArrow(course, event, nextQuestion)).toBe(false);
        expect(shouldAdvanceCourseExplanationWithArrow(course, { ...event, repeat: true }, nextQuestion)).toBe(false);
        expect(shouldAdvanceCourseExplanationWithArrow(course, { ...event, repeat: true }, explanation)).toBe(false);
    });

    it('does not schedule manual advancement and respects audio time in automatic mode', () => {
        expect(resolveCourseAdvanceDelay(course, 'manual', true, 5000, 600, 1000)).toBeNull();
        expect(resolveCourseAdvanceDelay(course, 'automatic', false, 5000, 600, 1000)).toBe(1000);
    });

    it('shows timing only for automatic explanations, and Ctrl/Alt/Option/Fn help for manual navigation', async () => {
        for (const explanationMode of ['never', 'incorrect', 'always'] as CourseExplanationMode[]) {
            for (const advanceMode of ['automatic', 'manual'] as CourseAdvanceMode[]) {
                const html = await renderToString(createSSRApp({ render: () => h(CourseFlashcardSettings, {
                    explanationMode, advanceMode, timingIndex: 0, timingOptions: [{ value: 'auto', label: '0s' }],
                }) }));
                expect(html).toContain('course-explanation-mode');
                expect(html).toContain('course-advance-mode');
                expect(html.includes('id="course-explanation-timing"')).toBe(advanceMode === 'automatic' && explanationMode !== 'never');
                expect(/<kbd\b[^>]*>lewy Ctrl<\/kbd>/.test(html)).toBe(advanceMode === 'manual');
                expect(/<kbd\b[^>]*>Alt \/ Option<\/kbd>/.test(html)).toBe(advanceMode === 'manual');
                expect(/<kbd\b[^>]*>Fn<\/kbd>/.test(html)).toBe(advanceMode === 'manual');
                expect(/<kbd\b[^>]*>→<\/kbd>/.test(html)).toBe(advanceMode === 'manual');
                expect(/<kbd\b[^>]*>D<\/kbd>/.test(html)).toBe(advanceMode === 'manual');
            }
        }
    });

    it('requires an explicit backend opt-in and a course learning scope', () => {
        expect(usesCourseExplanationFlashcard(course)).toBe(true);
        expect(usesCourseExplanationFlashcard({ ...course, scope: 'course_review' })).toBe(true);
        for (const context of [null, {}, { explanation_flashcard: false }]) {
            expect(usesCourseExplanationFlashcard({ ...course, context })).toBe(false);
        }
        for (const mode of ['exam', 'sr_review', 'review', 'pjm', 'quick']) {
            expect(usesCourseExplanationFlashcard({ ...course, mode })).toBe(false);
        }
        for (const ui_shell of ['exam_like', 'exam', null]) {
            expect(usesCourseExplanationFlashcard({ ...course, ui_shell })).toBe(false);
        }
    });

    it('never enables ordinary classic or Zen learning, even with a stale course opt-in', () => {
        for (const category of ['AM', 'A1', 'A2', 'A', 'B1', 'B', 'C1', 'C', 'D1', 'D', 'T']) {
            for (const ui_shell of ['zen', 'exam_like']) {
                expect(usesCourseExplanationFlashcard({
                    ...course, scope: 'regular_category', ui_shell, license_category_code: category,
                } as typeof course)).toBe(false);
            }
        }
    });

    it('renders exactly the original front markup when disabled, even if back state is stale', async () => {
        const front = () => [h('div', { class: 'original-media' }, 'Original media'), h('h2', 'Original prompt')];
        const html = await renderToString(createSSRApp({
            render: () => h(CourseExplanationFlashcard,
                { enabled: false, showBack: true, compact: false, correctAnswer: 'C', question: 'Course question must not render' },
                { default: front, explanation: () => h('p', 'Course explanation must not render') }),
        }));
        expect(html.replace(/<!--.*?-->/gs, '')).toBe('<div class="original-media">Original media</div><h2>Original prompt</h2>');
        expect(html).not.toContain('course-flashcard');
        expect(html).not.toContain('Course explanation must not render');
        expect(html).not.toContain('Course question must not render');
    });

    it('keeps the course front mounted but inaccessible while showing the full explanation', async () => {
        const html = await renderToString(createSSRApp({
            render: () => h(CourseExplanationFlashcard,
                { enabled: true, showBack: true, compact: false, correctAnswer: 'C. Correct option', question: 'Z czego wynika wzrost zużycia paliwa?' },
                { default: () => h('h2', 'Question'), explanation: () => h('p', 'Full explanation including its last sentence.') }),
        }));
        expect(html).toContain('data-face="explanation"');
        expect(html).toContain('inert');
        expect(html).toContain('aria-hidden="true"');
        expect(html).toContain('Wróć do pytania');
        expect(html).toContain('data-testid="course-flashcard-question"');
        expect(html).toContain('Z czego wynika wzrost zużycia paliwa?');
        expect(html.indexOf('Z czego wynika wzrost zużycia paliwa?')).toBeLessThan(html.indexOf('C. Correct option'));
        expect(html).toContain('C. Correct option');
        expect(html).toContain('Full explanation including its last sentence.');
    });

    it('renders the question on the back as safe text rather than HTML', async () => {
        const html = await renderToString(createSSRApp({ render: () => h(CourseExplanationFlashcard,
            { enabled: true, showBack: true, compact: true, correctAnswer: null, question: 'A < B & C? <img src=x onerror=alert(1)>' },
            { default: () => h('h2', 'Front') }),
        }));
        expect(html).toContain('A &lt; B &amp; C? &lt;img src=x onerror=alert(1)&gt;');
        expect(html).not.toContain('<img');
    });
});
