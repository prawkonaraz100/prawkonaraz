<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SessionReplacementDialog from '@/Components/SessionReplacementDialog.vue';
import { BookOpen } from '@lucide/vue';
import { adrModuleArtworkForCode, kod95ChapterArtworkForCode } from '@/lib/topicArtwork';
import { learningListSurfaceClass, learningListActionClass } from '@/utils/learningListPresentation';

interface CourseModule {
    id: number;
    code: string;
    source_code?: string;
    name: string;
    description: string | null;
    questions_count: number;
    progress: {
        answered_count: number;
        total_questions: number;
        percent: number;
    };
    start_url: string;
}

const props = withDefaults(defineProps<{
    modules: CourseModule[];
    activeSession: {
        id?: number;
        title: string;
    } | null;
    reviewCount?: number;
    reviewUrl?: string | null;
    heading?: string;
    lead?: string;
    compact?: boolean;
    chapters?: boolean;
    flush?: boolean;
    /** Dark mobile app list (learning-list mode only). */
    dark?: boolean;
}>(), {
    heading: 'Moduły kursu',
    lead: 'Wybierz moduł, od którego chcesz zacząć.',
    reviewCount: 0,
    reviewUrl: null,
    compact: false,
    chapters: false,
    flush: false,
    dark: false,
});

const startForm = useForm({
    replace_active_session: false,
    replace_active_session_id: null as number | null,
});
const pendingModule = ref<CourseModule | null>(null);
let pendingSessionId: number | null = null;
const startError = computed(() => Object.values(startForm.errors).join(' '));
const usesLearningList = computed(() => props.compact || props.chapters);
const unitName = computed(() => props.chapters ? 'rozdział' : 'dział');
const modulesWithArtwork = computed(() => props.modules.map((module) => ({
    ...module,
    artwork: props.chapters
        ? kod95ChapterArtworkForCode(module.code)
        : adrModuleArtworkForCode(module.source_code ?? module.code),
})));

const submitStart = (module: CourseModule, replaceActiveSession: boolean) => {
    startForm.replace_active_session = replaceActiveSession;
    startForm.replace_active_session_id = replaceActiveSession ? pendingSessionId : null;
    startForm.post(module.start_url, {
        preserveScroll: true,
        onFinish: () => {
            startForm.replace_active_session = false;
            startForm.replace_active_session_id = null;
        },
    });
};

const startModule = (module: CourseModule) => {
    if (startForm.processing) return;
    if (props.activeSession) {
        pendingSessionId = props.activeSession.id ?? null;
        pendingModule.value = module;

        return;
    }

    submitStart(module, false);
};

const confirmReplacement = () => {
    if (! pendingModule.value) {
        return;
    }

    submitStart(pendingModule.value, true);
    pendingModule.value = null;
};

const moduleActionLabel = (module: CourseModule): string => module.progress.answered_count > 0
    ? 'Kontynuuj'
    : 'Rozpocznij';

const questionCountLabel = (count: number): string => {
    const suffix = count % 10 === 1 && count % 100 !== 11
        ? 'pytanie'
        : count % 10 >= 2 && count % 10 <= 4 && (count % 100 < 12 || count % 100 > 14)
            ? 'pytania'
            : 'pytań';

    return `${count} ${suffix}`;
};

const learningListActionLabel = (module: CourseModule) =>
    module.progress.percent === 100 ? `Powtórz ${unitName.value}` : 'Rozpocznij naukę';
</script>

<template>
    <section :class="flush ? '' : 'mt-7'" :aria-label="chapters ? 'Rozdziały Kod 95' : 'Moduły kursu'">
        <p v-if="startError" role="alert" class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-800">{{ startError }}</p>
        <div v-if="usesLearningList" class="flex min-h-11 flex-wrap items-center justify-between gap-x-5 gap-y-2 pb-3">
            <h2 :class="dark ? 'px-1 text-[0.95rem] font-semibold text-[#ffffffb8]' : 'text-base font-semibold text-[#071b45]'">Działy do nauki</h2>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 [&>div]:mt-0">
                <Link
                    v-if="reviewUrl"
                    :href="reviewUrl"
                    class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2"
                    :class="dark
                        ? 'pk-btn-secondary focus-visible:ring-[#ffffffd9]'
                        : 'bg-white text-[#071b45] shadow-[0_5px_17px_rgba(31,66,110,0.05)] hover:bg-[#f8fbff] focus-visible:ring-[#0876f8] focus-visible:ring-offset-2'"
                >
                    Pytania do poprawy <span class="rounded-md px-1.5 py-0.5 text-xs" :class="dark ? 'bg-[#3a1f22] text-[#ff8a8a]' : 'bg-[#fff0e9] text-[#b54728]'">{{ reviewCount }}</span><span aria-hidden="true">→</span>
                </Link>
                <slot name="actions" />
            </div>
        </div>
        <div v-else class="flex flex-wrap items-end justify-between gap-x-8 gap-y-5 border-b border-[#d7dde1] pb-5">
            <div>
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h2 id="course-modules-heading" class="text-2xl font-semibold text-[#101828]">{{ heading }}</h2>
                    <span class="text-sm font-medium text-[#667085]">{{ modules.length }} modułów</span>
                </div>
                <p class="mt-2 text-sm leading-6 text-[#667085]">{{ lead }}</p>
            </div>
            <div
                v-if="reviewUrl"
                class="flex min-h-[4.5rem] min-w-[21rem] items-center gap-4 border-l-2 border-[#ef3b26] bg-[#fffafa] px-4 py-3"
                aria-labelledby="course-incorrect-questions-heading"
            >
                <strong class="min-w-7 text-2xl font-semibold text-[#d92d20]">{{ reviewCount }}</strong>
                <div class="min-w-0">
                    <h3 id="course-incorrect-questions-heading" class="text-sm font-semibold text-[#202a33]">Pytania do poprawy</h3>
                    <p class="mt-1 text-xs leading-5 text-[#667085]">
                        {{ reviewCount === 0
                            ? 'Nie masz teraz pytań na tej liście.'
                            : `${reviewCount} ${reviewCount === 1 ? 'pytanie czeka' : 'pytań czeka'} na powtórkę.` }}
                    </p>
                </div>
                <Link
                    :href="reviewUrl"
                    class="ml-auto shrink-0 text-sm font-semibold text-[#202a33] underline decoration-[#d3a7a1] underline-offset-4 transition hover:text-[#b42318] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                >
                    Otwórz <span aria-hidden="true">→</span>
                </Link>
            </div>
            <slot name="actions" />
        </div>

        <div v-if="modules.length === 0" :class="dark ? 'pk-panel mt-4 px-5 py-8 text-center' : 'mt-6 border-y border-[#dfe3e8] bg-white px-6 py-9'">
            <p class="text-base font-semibold" :class="dark ? 'text-[#ffffffd9]' : 'text-[#101828]'">{{ chapters ? 'Rozdziały' : usesLearningList ? 'Działy' : 'Moduły' }} są chwilowo niedostępne.</p>
            <p class="mt-2 text-sm leading-6" :class="dark ? 'text-[#ffffffb8]' : 'text-[#667085]'">Wróć później lub wybierz inny tryb nauki.</p>
        </div>

        <ol v-else-if="usesLearningList && dark" class="w-full space-y-2" :aria-label="chapters ? 'Lista rozdziałów Kod 95' : 'Lista modułów kursu'">
            <li v-for="module in modulesWithArtwork" :key="module.id">
                <button
                    type="button"
                    class="pk-tile flex min-h-[4.15rem] w-full items-center gap-3 px-2.5 py-2 text-left disabled:cursor-wait disabled:opacity-60"
                    :aria-label="`${module.progress.percent === 100 ? 'Powtórz' : 'Rozpocznij'} ${unitName} ${module.code}: ${module.name}`"
                    :disabled="startForm.processing"
                    @click="startModule(module)"
                >
                    <img v-if="module.artwork" :src="module.artwork" alt="" width="44" height="44" class="h-11 w-11 shrink-0 rounded-[0.7rem] bg-[#ffffffd9] object-contain p-1" loading="lazy" decoding="async" />
                    <span v-else class="grid h-11 w-11 shrink-0 place-items-center rounded-[0.8rem] bg-[#1c2c3d] text-[#7ab8ff]" aria-hidden="true"><BookOpen :size="22" :stroke-width="1.9" /></span>
                    <span class="min-w-0 flex-1 self-stretch py-0.5">
                        <span class="block text-[0.98rem] font-bold leading-[1.2rem]">{{ module.name }}</span>
                        <span class="mt-0.5 block text-[0.8rem] leading-4 text-[#ffffffb8] tabular-nums">{{ module.progress.answered_count }} / {{ questionCountLabel(module.questions_count) }}</span>
                        <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-[#ffffff1a]" role="progressbar" :aria-label="`Postęp: ${module.name}`" :aria-valuenow="module.progress.percent" aria-valuemin="0" aria-valuemax="100">
                            <span class="block h-full rounded-full bg-[#81b64c]" :style="{ width: `${module.progress.percent}%` }" />
                        </span>
                    </span>
                    <svg class="h-5 w-5 shrink-0 text-[#ffffffb8]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 4.5 7 7.5-7 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </button>
            </li>
        </ol>

        <ol v-else-if="usesLearningList" class="w-full space-y-1.5" :aria-label="chapters ? 'Lista rozdziałów Kod 95' : 'Lista modułów kursu'">
            <li v-for="module in modulesWithArtwork" :key="module.id">
                <button type="button" :class="[learningListSurfaceClass, 'grid-cols-[2.25rem_minmax(0,1fr)_auto] gap-y-1 py-3 disabled:cursor-wait disabled:opacity-50 min-[1150px]:grid-cols-[4rem_2.5rem_minmax(0,1fr)_6.5rem_12rem_11.5rem] min-[1150px]:py-1.5']"
                    :aria-label="`${module.progress.percent === 100 ? 'Powtórz' : 'Rozpocznij'} ${unitName} ${module.code}: ${module.name}`"
                    :disabled="startForm.processing" @click="startModule(module)">
                    <span class="row-span-3 flex h-8 w-full items-center justify-center border-r border-[#e5eaf2] text-[#0876f8] min-[1150px]:row-span-1" aria-hidden="true">
                        <img v-if="module.artwork" :src="module.artwork" alt="" width="28" height="28" class="size-7 object-contain" loading="lazy" decoding="async" />
                        <BookOpen v-else :size="25" :stroke-width="1.8" />
                    </span>
                    <span class="hidden text-xs font-medium text-[#7188ac] min-[1150px]:block" aria-hidden="true">{{ String(module.code).padStart(2, '0') }}</span>
                    <span class="col-span-2 col-start-2 min-w-0 break-words text-sm font-semibold leading-5 text-[#071b45] min-[1150px]:col-span-1 min-[1150px]:col-auto min-[1150px]:text-[0.94rem]">{{ module.name }}</span>
                    <span class="col-start-2 row-start-2 text-xs text-[#7188ac] min-[1150px]:col-auto min-[1150px]:row-auto min-[1150px]:text-sm">{{ questionCountLabel(module.questions_count) }}</span>
                    <span class="col-span-2 col-start-2 row-start-3 flex min-w-0 items-center gap-2 min-[1150px]:col-span-1 min-[1150px]:col-auto min-[1150px]:row-auto">
                        <span class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-[#e6edf0]" role="progressbar" :aria-label="`Postęp: ${module.name}`" :aria-valuenow="module.progress.percent" aria-valuemin="0" aria-valuemax="100">
                            <span class="block h-full rounded-full bg-[#00cf85]" :style="{ width: `${module.progress.percent}%` }" />
                        </span>
                        <span class="w-9 shrink-0 text-xs font-semibold text-[#00bd78]">{{ module.progress.percent }}%</span>
                    </span>
                    <span :class="[learningListActionClass, 'col-start-3 row-start-2 min-[1150px]:col-auto min-[1150px]:row-auto']">{{ learningListActionLabel(module) }} <span aria-hidden="true">→</span></span>
                </button>
            </li>
        </ol>

        <ol v-else class="mt-6 divide-y divide-[#dfe3e8] border-y border-[#dfe3e8] bg-white">
            <li v-for="module in modulesWithArtwork" :key="module.id" class="group grid gap-5 px-5 py-6 transition-colors hover:bg-[#fbfcfd] sm:grid-cols-[4.5rem_minmax(0,1fr)_auto] sm:items-center sm:px-6 xl:px-8">
                <img v-if="module.artwork" :src="module.artwork" alt="" width="48" height="48" class="size-12 object-contain" loading="lazy" decoding="async" />
                <div v-else class="flex h-12 w-12 items-center justify-center border border-[#d7dde1] bg-[#f8fafb] text-sm font-semibold text-[#344054]">
                    {{ module.code }}
                </div>
                <div class="min-w-0">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.1em] text-[#7b8790]">Moduł {{ module.code }}</p>
                    <h3 class="mt-2 text-base font-semibold leading-6 text-[#101828] xl:text-[1.05rem]">{{ module.name }}</h3>
                    <p v-if="module.description" class="mt-2 text-sm leading-6 text-[#667085]">{{ module.description }}</p>
                    <div class="mt-5 max-w-2xl">
                        <div class="flex items-center justify-between gap-4 text-sm text-[#596671]">
                            <span>{{ module.progress.answered_count }} z {{ module.progress.total_questions }} przerobionych</span>
                            <span class="shrink-0 font-semibold text-[#344054]">{{ module.progress.percent }}%</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden bg-[#e8edf0]" role="progressbar" :aria-valuenow="module.progress.percent" aria-valuemin="0" aria-valuemax="100" :aria-label="`Postęp modułu ${module.code}`">
                            <span class="block h-full bg-[#ef3b26] transition-[width] duration-300" :style="{ width: `${module.progress.percent}%` }" />
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 self-start rounded-[5px] bg-[#101820] px-5 text-sm font-semibold text-white transition hover:bg-[#26323d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[#98a2b3] sm:self-center"
                    :aria-label="`${moduleActionLabel(module)}: ${module.name}`"
                    :disabled="startForm.processing"
                    @click="startModule(module)"
                >
                    {{ moduleActionLabel(module) }} <span aria-hidden="true">→</span>
                </button>
            </li>
        </ol>
    </section>

    <SessionReplacementDialog
        v-if="pendingModule"
        :show="true"
        :session-id="pendingSessionId"
        :session-title="activeSession?.title ?? 'Bieżąca sesja'"
        :next-session-title="pendingModule.name"
        :busy="startForm.processing"
        @cancel="pendingModule = null"
        @confirm="confirmReplacement"
    />
</template>
