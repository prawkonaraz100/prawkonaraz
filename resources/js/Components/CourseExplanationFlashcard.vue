<script setup lang="ts">
defineProps<{
    enabled: boolean;
    showBack: boolean;
    compact: boolean;
    correctAnswer: string | null;
    question?: string;
}>();

defineEmits<{ returnToQuestion: [] }>();
</script>

<template>
    <!-- No element, styling or interaction is introduced for ordinary learning. -->
    <template v-if="!enabled">
        <slot />
    </template>
    <section
        v-else
        class="course-flashcard"
        :class="{ 'course-flashcard--compact': compact }"
        :data-face="showBack ? 'explanation' : 'question'"
        data-testid="course-explanation-flashcard"
        aria-label="Fiszka kursu Kod 95"
    >
        <div
            class="course-flashcard__front"
            :class="{ 'course-flashcard__front--hidden': showBack }"
            :inert="showBack || undefined"
            :aria-hidden="showBack ? true : undefined"
        >
            <slot />
        </div>
        <Transition name="course-card-turn">
            <div
                v-if="showBack"
                class="course-flashcard__back"
                data-testid="course-flashcard-explanation"
            >
                <header class="course-flashcard__header">
                    <div>
                        <h2>Dlaczego ta odpowiedź?</h2>
                    </div>
                    <button type="button" aria-label="Wróć do pytania" @click="$emit('returnToQuestion')">
                        <span aria-hidden="true">↶</span>
                        <span class="course-flashcard__return-label">Wróć do pytania</span>
                        <span class="course-flashcard__return-short" aria-hidden="true">Wróć</span>
                    </button>
                </header>
                <div
                    class="course-flashcard__reading"
                    tabindex="0"
                    role="region"
                    aria-label="Pełna treść wyjaśnienia"
                >
                    <div v-if="question" class="course-flashcard__question" data-testid="course-flashcard-question">
                        <p class="course-flashcard__eyebrow">Pytanie</p>
                        <h3>{{ question }}</h3>
                    </div>
                    <p v-if="correctAnswer" class="course-flashcard__answer">
                        <span>Poprawna odpowiedź</span>
                        <strong>{{ correctAnswer }}</strong>
                    </p>
                    <div class="course-flashcard__explanation">
                        <slot name="explanation" />
                    </div>
                </div>
            </div>
        </Transition>
    </section>
</template>

<style scoped>
@font-face {
    font-family: 'Course Reading Inter'; font-style: normal; font-weight: 100 900; font-display: swap;
    src: url('/fonts/filament/filament/inter/inter-latin-ext-wght-normal-HA22NDSG.woff2') format('woff2');
    unicode-range: U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF;
}
@font-face {
    font-family: 'Course Reading Inter'; font-style: normal; font-weight: 100 900; font-display: swap;
    src: url('/fonts/filament/filament/inter/inter-latin-wght-normal-NRMW37G5.woff2') format('woff2');
    unicode-range: U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD;
}
.course-flashcard { position: relative; min-width: 0; min-height: 0; perspective: 1200px; }
.course-flashcard__front { display: grid; height: 100%; min-height: 0; grid-template-rows: minmax(4rem, 1fr) auto; gap: .5625rem; overflow-y: auto; overscroll-behavior: contain; }
.course-flashcard__front > :last-child { max-height: 45dvh; overflow-y: auto; }
.course-flashcard__front--hidden { visibility: hidden; pointer-events: none; }
.course-flashcard__back { position: absolute; inset: 0; display: flex; flex-direction: column; overflow: hidden; background: #fff; color: #17243a; border-radius: 12px; border: 1px solid #e2e8f0; backface-visibility: hidden; }
.course-flashcard__header { display: flex; flex-shrink: 0; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 24px; border-bottom: 1px solid #e8edf3; }
.course-flashcard__header > div { display: flex; min-width: 0; flex-wrap: wrap; align-items: baseline; gap: 4px 12px; }
.course-flashcard__eyebrow { margin-bottom: 6px; font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: #67758a; }
.course-flashcard__header h2 { font-size: 17px; line-height: 1.3; font-weight: 650; }
.course-flashcard__header button { display: inline-flex; flex-shrink: 0; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 7px; background: #f1f5fa; color: #173d76; font-size: 12px; font-weight: 600; }
.course-flashcard__header button:hover { background: #e5edf8; }
.course-flashcard__return-short { display: none; }
.course-flashcard__header button:focus-visible, .course-flashcard__reading:focus-visible { outline: 2px solid #1769c2; outline-offset: -3px; }
.course-flashcard__reading { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 24px 32px; font-family: 'Course Reading Inter', 'Open Sans', ui-sans-serif, system-ui, sans-serif; font-size: 1.0625rem; }
.course-flashcard__reading > * { max-width: 70ch; margin-inline: auto; }
.course-flashcard__explanation :deep(.order-2 > div), .course-flashcard__explanation :deep(.order-2 > p) { font-family: inherit; font-size: 1.0625rem; font-weight: 400; line-height: 1.7; letter-spacing: normal; color: #334155; text-align: left; overflow-wrap: anywhere; }
.course-flashcard__explanation :deep(p + p) { margin-top: .9em; }
.course-flashcard__explanation :deep(li + li) { margin-top: .35em; }
.course-flashcard__explanation :deep(strong) { font-weight: 600; }
.course-flashcard__question { margin-bottom: 22px; }
.course-flashcard__question h3 { font-family: inherit; font-size: 20px; line-height: 1.5; font-weight: 600; overflow-wrap: anywhere; white-space: pre-line; }
.course-flashcard__answer { display: flex; flex-direction: column; gap: 7px; margin-bottom: 24px; padding: 16px 20px; background: #effaf4; border-radius: 8px; }
.course-flashcard__answer span { font-size: 12px; color: #426850; }
.course-flashcard__answer strong { font-size: 18px; line-height: 1.5; color: #176b3f; }
.course-card-turn-enter-active { transition: transform 180ms ease-out, opacity 180ms ease-out; transform-origin: center; }
.course-card-turn-enter-from { opacity: 0; transform: rotateY(-18deg); }
.course-flashcard[data-face="explanation"] .course-flashcard__front { overflow: hidden; }
@media (min-width: 1024px) and (max-height: 850px) {
    .course-flashcard__reading { padding: 20px 28px; }
    .course-flashcard__question { margin-bottom: 16px; }
    .course-flashcard__question h3 { font-size: 18px; line-height: 1.45; }
    .course-flashcard__answer { margin-bottom: 18px; padding: 12px 16px; gap: 5px; }
    .course-flashcard__answer strong { font-size: 17px; }
}
@media (max-width: 600px) {
    .course-flashcard__header { gap: 10px; padding: 10px 14px; }
    .course-flashcard__header > div { flex-direction: column; align-items: flex-start; gap: 2px; }
    .course-flashcard__header h2 { font-size: 15px; }
    .course-flashcard__header button { padding: 6px 8px; max-width: 120px; font-size: 11px; }
    .course-flashcard__reading { padding: 18px 16px; }
    .course-flashcard__explanation :deep(.order-2 > div), .course-flashcard__explanation :deep(.order-2 > p) { font-size: 1rem; line-height: 1.65; }
    .course-flashcard__question h3 { font-size: 17px; }
}
@media (prefers-reduced-motion: reduce) {
    .course-card-turn-enter-active { transition: none; }
    .course-card-turn-enter-from { transform: none; }
}
@media (max-width: 380px) {
    .course-flashcard__header { padding: 8px 12px; }
    .course-flashcard__header h2 { font-size: 14px; }
    .course-flashcard__return-label { display: none; }
    .course-flashcard__return-short { display: inline; }
}
</style>
