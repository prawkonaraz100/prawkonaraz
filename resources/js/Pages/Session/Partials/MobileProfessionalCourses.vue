<script setup lang="ts">
import MobileBackButton from '@/Components/MobileBackButton.vue';
import { router } from '@inertiajs/vue3';
import CourseModules from '@/Pages/QuestionCollections/Partials/CourseModules.vue';
import QualificationProgressReset from '@/Components/QualificationProgressReset.vue';
import ProfessionalCourseOverview from './ProfessionalCourseOverview.vue';
import type { LearningProgressMessage } from '@/types/learningProgress';
import type { QualificationProgressReset as ProgressReset } from '@/types/qualificationProgress';
import { computed, watch } from 'vue';

interface Course {
    progress_reset?: ProgressReset | null;
    id: number;
    code: string;
    slug: string;
    name: string;
    is_adr?: boolean;
    learning_parts_enabled?: boolean;
    learning_parts_error?: string | null;
    progress_message: LearningProgressMessage;
    progress: { answered_count: number; total_questions: number; percent: number };
    incorrect_questions: { count: number; url: string };
    modules: Array<{ id: number; code: string; name: string; description: string | null; questions_count: number; progress: { answered_count: number; total_questions: number; percent: number }; start_url: string }>;
}
const props = defineProps<{
    courses: Course[];
    initialCode: string | null;
    activeSession: { id: number; title: string; collection_code?: string | null; resume_url?: string } | null;
    adrSessionToReplace: { id: number; title: string } | null;
    hideList?: boolean;
    dark?: boolean;
}>();
const emit = defineEmits<{ (event: 'select-course', active: boolean): void }>();
const selected = computed(() => props.courses.find(c => c.code === props.initialCode) ?? null);
watch(selected, course => emit('select-course', course !== null), { immediate: true });

function selectCourse(course: Course | null): void {
    const url = new URL(window.location.href);
    if (course) {
        url.searchParams.set('kurs', course.slug);
    } else {
        url.searchParams.delete('kurs');
    }
    router.replace({
        url: url.pathname + url.search + url.hash,
        preserveState: true,
        props: current => ({ ...current, initial_professional_course_code: course?.code ?? null }),
    });
}

function selectCourseByCode(code: string): void {
    const course = props.courses.find(c => c.code === code);
    if (course) {
        selectCourse(course);
    }
}

defineExpose({ selectCourseByCode });
</script>

<template>
    <section
        v-if="courses.length && (selected || !hideList)"
        class="md:hidden"
        :class="selected
            ? (dark ? 'mobile-course-dark mx-auto min-h-[calc(100svh-4.75rem)] max-w-[30rem] px-4 pb-6 pt-[max(env(safe-area-inset-top),1rem)] text-[#ffffffd9]' : 'mx-3 my-4')
            : (dark ? 'pk-panel mx-3 my-4 p-3' : 'mx-3 my-4 rounded-2xl bg-white p-4')"
        aria-label="Kursy zawodowe"
    >
        <template v-if="!selected">
            <p class="text-xs font-semibold uppercase tracking-wider" :class="dark ? 'text-[#ffc94a]' : 'text-[#946115]'">Kursy zawodowe</p>
            <button v-for="course in courses" :key="course.id" type="button" class="mt-2 flex min-h-12 w-full items-center justify-between gap-3 rounded-xl p-3 text-left text-sm font-bold" :class="dark ? 'pk-tile' : 'bg-[#fff4d9] text-[#071b45]'" @click="selectCourse(course)"><span>{{ course.name }}</span><span aria-hidden="true">→</span></button>
        </template>
        <template v-else>
            <ProfessionalCourseOverview :course="selected" :dark="dark">
                <template #navigation>
                    <MobileBackButton v-if="dark" label="Wróć do działów" @click="selectCourse(null)" />
                    <button v-else type="button" class="mb-2 min-h-11 text-sm font-semibold text-[#53698b]" @click="selectCourse(null)">← Wróć do panelu</button>
                </template>
                <a v-if="activeSession?.resume_url && activeSession.collection_code === selected.code" :href="activeSession.resume_url" class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold underline" :class="dark ? 'text-[#81b64c]' : 'text-[#071b45]'">Wznów bieżącą sesję →</a>
            </ProfessionalCourseOverview>
            <CourseModules :key="selected.id" compact :dark="dark" :modules="selected.modules" :chapters="selected.learning_parts_enabled" :active-session="activeSession ?? adrSessionToReplace" :review-count="selected.incorrect_questions.count" :review-url="selected.incorrect_questions.url">
                <template #actions><QualificationProgressReset :key="selected.id" :reset="selected.progress_reset ?? null" :answered="selected.progress.answered_count" /></template>
            </CourseModules>
            <p v-if="selected.learning_parts_error" role="alert" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800">{{ selected.learning_parts_error }}</p>
        </template>
    </section>
</template>

<style scoped>
.mobile-course-dark {
    background: #302e2b;
    font-family: Barlow, Figtree, ui-sans-serif, system-ui, sans-serif;
}

.mobile-course-dark :deep(:is(h1, h2)) {
    font-family: inherit;
}

/* The shared reset control sits in the course header; keep it readable on the dark surface. */
.mobile-course-dark :deep([data-testid='qualification-progress-reset']) {
    color: #ffffffb8;
}

.mobile-course-dark :deep([data-testid='qualification-progress-reset']:hover) {
    color: #ffffffd9;
}
</style>
