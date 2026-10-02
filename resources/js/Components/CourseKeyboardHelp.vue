<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { Keyboard, X } from '@lucide/vue';
import { COURSE_KEYBOARD_HELP_DISMISSED_KEY, isCourseKeyboardHelpDismissed } from '../utils/courseKeyboardHelp';

const props = defineProps<{ open: boolean; answerCount: number }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();
const dialog = ref<HTMLDialogElement | null>(null);
const doNotShowAgain = ref(false);
const close = () => emit('update:open', false);
const syncDialog = () => {
    if (props.open && !dialog.value?.open) dialog.value?.showModal();
    else if (!props.open && dialog.value?.open) dialog.value.close();
};
watch(() => props.open, syncDialog, { flush: 'post' });
onMounted(() => {
    try {
        doNotShowAgain.value = isCourseKeyboardHelpDismissed(localStorage.getItem(COURSE_KEYBOARD_HELP_DISMISSED_KEY));
    } catch {
        // Storage can be unavailable; help should still be usable in this session.
    }
    if (!doNotShowAgain.value) emit('update:open', true);
    syncDialog();
});
watch(doNotShowAgain, (dismissed) => {
    try {
        if (dismissed) localStorage.setItem(COURSE_KEYBOARD_HELP_DISMISSED_KEY, '1');
        else localStorage.removeItem(COURSE_KEYBOARD_HELP_DISMISSED_KEY);
    } catch {
        // A blocked storage must not prevent closing or manually opening help.
    }
});
onBeforeUnmount(() => dialog.value?.close());
</script>

<template>
    <button type="button" class="course-keyboard-help__trigger" data-testid="course-keyboard-help-trigger"
        aria-label="Skróty klawiaturowe" aria-haspopup="dialog" :aria-expanded="open"
        @click="emit('update:open', true)">
        <Keyboard :size="17" aria-hidden="true" />
        <span>Klawiatura</span>
    </button>
    <Teleport to="body">
        <dialog ref="dialog" class="course-keyboard-help__dialog" aria-labelledby="course-keyboard-help-title"
            @cancel.prevent="close">
            <div class="course-keyboard-help__overlay" @click.self="close">
                <section v-if="open" class="course-keyboard-help__card" data-testid="course-keyboard-help-popup">
                    <header>
                        <h2 id="course-keyboard-help-title">Ucz się bez używania myszy</h2>
                        <button type="button" class="course-keyboard-help__close" aria-label="Zamknij pomoc klawiatury"
                            autofocus @click="close"><X :size="20" aria-hidden="true" /></button>
                    </header>
                    <p>Gdy widzisz pytanie, wybierz odpowiedź:</p>
                    <ul v-if="answerCount === 3">
                        <li><span><kbd>A</kbd> lub <kbd>←</kbd></span><strong>Odpowiedź A</strong></li>
                        <li><span><kbd>S</kbd> lub <kbd>↓</kbd></span><strong>Odpowiedź B</strong></li>
                        <li><span><kbd>D</kbd> lub <kbd>→</kbd></span><strong>Odpowiedź C</strong></li>
                    </ul>
                    <ul v-else-if="answerCount === 2">
                        <li><span><kbd>A</kbd> lub <kbd>←</kbd></span><strong>TAK</strong></li>
                        <li><span><kbd>S</kbd>, <kbd>D</kbd> lub <kbd>↓</kbd>, <kbd>→</kbd></span><strong>NIE</strong></li>
                    </ul>
                    <p v-else>Strzałkami wybierasz odpowiedź, a spacją ją zatwierdzasz.</p>
                    <div class="course-keyboard-help__next">
                        <span class="course-keyboard-help__next-keys"><kbd>D</kbd><kbd>→</kbd></span>
                        <p>Gdy po odpowiedzi widzisz fiszkę z wyjaśnieniem, <strong>D lub prawa strzałka przechodzi dalej.</strong></p>
                    </div>
                    <p class="course-keyboard-help__modifiers">Po udzieleniu odpowiedzi możesz też przejść dalej, naciskając <strong>lewy Ctrl</strong> lub <strong>lewy albo prawy Alt</strong> — na Macu <strong>Option (⌥)</strong>. Wystarczy jeden klawisz, bez łączenia go z innymi.</p>
                    <p class="course-keyboard-help__note">Na nowym pytaniu D i → znów wybierają odpowiedź C (lub NIE przy dwóch odpowiedziach). Naciśnij klawisz ponownie — nie przytrzymuj go.</p>
                    <label class="course-keyboard-help__dismiss">
                        <input v-model="doNotShowAgain" type="checkbox" />
                        <span>Nie pokazuj więcej</span>
                    </label>
                    <button type="button" class="course-keyboard-help__confirm" @click="close">Rozumiem</button>
                </section>
            </div>
        </dialog>
    </Teleport>
</template>

<style scoped>
.course-keyboard-help__trigger { display: inline-flex; align-items: center; gap: 7px; padding: 8px 10px; border-radius: 6px; color: #475569; font-size: 12px; }
.course-keyboard-help__trigger:hover { background: #edf2f7; color: #17243a; }
.course-keyboard-help__dialog { position: fixed; inset: 0; margin: 0; padding: 0; border: 0; width: 100%; max-width: none; height: 100%; max-height: none; background: transparent; color: #17243a; }
.course-keyboard-help__dialog::backdrop { background: rgb(15 23 42 / .38); backdrop-filter: blur(3px); }
.course-keyboard-help__overlay { display: grid; place-items: center; width: 100%; min-height: 100%; padding: 20px; }
.course-keyboard-help__card { width: min(100%, 440px); padding: 24px; border-radius: 18px; background: #fff; box-shadow: 0 24px 80px rgb(15 23 42 / .2); }
header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 14px; }
h2 { font-size: 21px; line-height: 1.35; font-weight: 650; }
.course-keyboard-help__close { display: grid; place-items: center; flex-shrink: 0; padding: 5px; border-radius: 6px; color: #64748b; }
.course-keyboard-help__close:hover { background: #f1f5f9; }
p { font-size: 14px; line-height: 1.6; color: #64748b; }
ul { display: grid; gap: 8px; margin: 18px 0; padding: 0; list-style: none; }
li { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 10px 12px; border-radius: 8px; background: #f5f7fb; font-size: 14px; }
li > span { display: inline-flex; align-items: center; flex-wrap: wrap; gap: 7px; color: #64748b; }
li strong { flex-shrink: 0; font-weight: 600; }
kbd { display: inline-grid; place-items: center; min-width: 30px; padding: 3px 7px; border: 1px solid #d6deea; border-bottom-width: 2px; border-radius: 5px; background: white; color: #17243a; font-size: 14px; font-family: inherit; font-weight: 600; }
.course-keyboard-help__next { display: flex; align-items: center; gap: 13px; padding: 14px; border-radius: 10px; background: #edf5ff; }
.course-keyboard-help__next p { color: #365479; }
.course-keyboard-help__next kbd { flex-shrink: 0; }
.course-keyboard-help__next-keys { display: grid; gap: 5px; flex-shrink: 0; }
.course-keyboard-help__modifiers { margin-top: 12px; color: #365479; }
.course-keyboard-help__note { margin: 12px 0 18px; font-size: 12px; }
.course-keyboard-help__dismiss { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; color: #475569; font-size: 14px; cursor: pointer; }
.course-keyboard-help__dismiss input { width: 17px; height: 17px; border-radius: 4px; accent-color: #1769c2; }
.course-keyboard-help__confirm { width: 100%; padding: 11px 16px; border-radius: 9px; background: #17243a; color: #fff; font-size: 14px; font-weight: 600; }
button:focus-visible { outline: 2px solid #1769c2; outline-offset: 3px; }
@media (max-width: 600px) { .course-keyboard-help__trigger span { display: none; } .course-keyboard-help__card { padding: 20px; } }
</style>
