<script setup lang="ts">
import type { CourseExplanationMode, CourseAdvanceMode } from '@/utils/courseFlashcard';
defineProps<{
    explanationMode: CourseExplanationMode;
    advanceMode: CourseAdvanceMode;
    timingIndex: number;
    timingOptions: Array<{ value: string; label: string }>;
}>();
defineEmits<{
    'update:explanationMode': [value: CourseExplanationMode];
    'update:advanceMode': [value: CourseAdvanceMode];
    'update:timingIndex': [value: string];
}>();
const explanations: Array<{ value: CourseExplanationMode; title: string; description: string }> = [
    { value: 'never', title: 'Nigdy', description: 'Fiszka nie odwraca się sama. Wyjaśnienie możesz otworzyć ręcznie.' },
    { value: 'incorrect', title: 'Tylko po błędzie', description: 'Po błędnej odpowiedzi fiszka pokaże wyjaśnienie.' },
    { value: 'always', title: 'Po każdej odpowiedzi', description: 'Wyjaśnienie pojawi się po poprawnej i błędnej odpowiedzi.' },
];
const advances: Array<{ value: CourseAdvanceMode; title: string; description: string }> = [
    { value: 'automatic', title: 'Automatycznie', description: 'Po czasie na przeczytanie wyjaśnienia lub krótkiej informacji o wyniku.' },
    { value: 'manual', title: 'Lewy Ctrl / Alt (Option) / Fn lub „Dalej”', description: 'Nic nie przechodzi samo. Ty decydujesz, kiedy skończyłeś czytać.' },
];
</script>

<template>
    <div class="course-settings" data-testid="course-flashcard-settings">
        <fieldset>
            <legend>Kiedy pokazywać wyjaśnienie?</legend>
            <p class="course-settings__intro">Wybierz, kiedy fiszka ma odwracać się automatycznie.</p>
            <label v-for="option in explanations" :key="option.value" :class="{ selected: explanationMode === option.value }">
                <input type="radio" name="course-explanation-mode" :value="option.value"
                    :checked="explanationMode === option.value" :data-testid="`course-explanation-${option.value}`"
                    @change="$emit('update:explanationMode', option.value)" />
                <span><strong>{{ option.title }}</strong><small>{{ option.description }}</small></span>
            </label>
        </fieldset>
        <fieldset>
            <legend>Jak przechodzić do następnego pytania?</legend>
            <p class="course-settings__intro">Ten wybór nie zmienia ustawienia wyjaśnień.</p>
            <label v-for="option in advances" :key="option.value" :class="{ selected: advanceMode === option.value }">
                <input type="radio" name="course-advance-mode" :value="option.value"
                    :checked="advanceMode === option.value" :data-testid="`course-advance-${option.value}`"
                    @change="$emit('update:advanceMode', option.value)" />
                <span><strong>{{ option.title }}</strong><small>{{ option.description }}</small></span>
            </label>
        </fieldset>
        <div v-if="advanceMode === 'automatic' && explanationMode !== 'never'" class="course-settings__timing">
            <label for="course-explanation-timing"><strong>Jak długo pokazywać wyjaśnienie?</strong></label>
            <p class="course-settings__intro">Dopasowujemy czas do tekstu. Możesz dodać więcej czasu.</p>
            <input id="course-explanation-timing" type="range" min="0" :max="timingOptions.length - 1" step="1"
                :value="timingIndex" aria-label="Dodatkowy czas wyjaśnienia"
                @input="$emit('update:timingIndex', ($event.target as HTMLInputElement).value)" />
            <div class="course-settings__ticks"><span v-for="(option, index) in timingOptions" :key="option.value"
                :class="{ active: timingIndex === index }">{{ option.label }}</span></div>
        </div>
        <p v-if="advanceMode === 'manual'" class="course-settings__hint">
            Po odpowiedzi naciśnij <kbd>lewy Ctrl</kbd>, <kbd>Alt / Option</kbd> (lewy lub prawy) lub <kbd>Fn</kbd>, aby przejść dalej.
            <small>Na fiszce z wyjaśnieniem możesz też nacisnąć <kbd>D</kbd> lub <kbd>→</kbd>. Na nowym pytaniu oba klawisze ponownie wybierają odpowiedź C (lub NIE przy dwóch odpowiedziach).</small>
            <small>Fn działa tylko wtedy, gdy klawiatura przekazuje ten klawisz do przeglądarki.</small>
        </p>
    </div>
</template>

<style scoped>
.course-settings { color: #334155; display: grid; gap: 24px; }
fieldset { min-width: 0; }
legend { font-size: 14px; font-weight: 650; margin-bottom: 6px; }
.course-settings__intro { font-size: 13px; line-height: 1.6; color: #64748b; margin-bottom: 12px; }
fieldset > label { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #e5e7eb; background: white; margin-top: 8px; cursor: pointer; }
fieldset > label.selected { border-color: #334155; background: #fafafa; }
input[type=radio] { flex-shrink: 0; margin-top: 3px; color: #161414; }
strong { display: block; font-size: 13px; font-weight: 600; }
small { display: block; margin-top: 4px; font-size: 12px; line-height: 1.6; color: #64748b; }
.course-settings__timing { padding: 14px; border: 1px solid #e5e7eb; background: #fafafa; }
.course-settings__timing label { display: block; margin-bottom: 6px; }
input[type=range] { width: 100%; accent-color: #334155; }
.course-settings__ticks { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; }
.course-settings__ticks .active { color: #161414; font-weight: 600; }
.course-settings__hint { font-size: 13px; line-height: 1.6; padding: 12px; background: #f1f5f9; }
kbd { padding: 2px 5px; border: 1px solid #cbd5e1; background: white; border-radius: 3px; }
</style>
