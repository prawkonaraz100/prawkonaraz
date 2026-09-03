<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface CourseModule {
    id: number;
    code: string;
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
        title: string;
    } | null;
    reviewCount?: number;
    reviewUrl?: string | null;
    heading?: string;
    lead?: string;
}>(), {
    heading: 'Moduły kursu',
    lead: 'Wybierz moduł, od którego chcesz zacząć.',
    reviewCount: 0,
    reviewUrl: null,
});

const startForm = useForm({
    replace_active_session: false,
});
const pendingModule = ref<CourseModule | null>(null);

const submitStart = (module: CourseModule, replaceActiveSession: boolean) => {
    startForm.replace_active_session = replaceActiveSession;
    startForm.post(module.start_url, {
        preserveScroll: true,
        onFinish: () => {
            startForm.replace_active_session = false;
        },
    });
};

const startModule = (module: CourseModule) => {
    if (props.activeSession) {
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
</script>

<template>
    <section aria-labelledby="course-modules-heading">
        <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-5 border-b border-[#d7dde1] pb-5">
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
        </div>

        <div v-if="modules.length === 0" class="mt-6 border-y border-[#dfe3e8] bg-white px-6 py-9">
            <p class="text-base font-semibold text-[#101828]">Moduły są chwilowo niedostępne.</p>
            <p class="mt-2 text-sm leading-6 text-[#667085]">Wróć później lub wybierz inny tryb nauki.</p>
        </div>

        <ol v-else class="mt-6 divide-y divide-[#dfe3e8] border-y border-[#dfe3e8] bg-white">
            <li v-for="module in modules" :key="module.id" class="group grid gap-5 px-5 py-6 transition-colors hover:bg-[#fbfcfd] sm:grid-cols-[4.5rem_minmax(0,1fr)_auto] sm:items-center sm:px-6 xl:px-8">
                <div class="flex h-12 w-12 items-center justify-center border border-[#d7dde1] bg-[#f8fafb] text-sm font-semibold text-[#344054]">
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
                    :disabled="startForm.processing"
                    @click="startModule(module)"
                >
                    {{ moduleActionLabel(module) }} <span aria-hidden="true">→</span>
                </button>
            </li>
        </ol>
    </section>

    <div v-if="pendingModule" class="fixed inset-0 z-50 flex items-center justify-center bg-[#101828]/45 p-5" role="presentation">
        <section
            class="w-full max-w-md rounded-[6px] bg-white p-6 shadow-xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="replace-session-title"
        >
            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-[#667085]">Bieżąca sesja</p>
            <h2 id="replace-session-title" class="mt-2 text-xl font-semibold text-[#101828]">Rozpocząć nowy moduł?</h2>
            <p class="mt-3 text-sm leading-6 text-[#475467]">
                Masz rozpoczętą sesję: {{ activeSession?.title }}. Uruchomienie modułu {{ pendingModule.code }} zakończy ją w obecnym miejscu.
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    class="min-h-11 rounded-[5px] border border-[#d0d5dd] px-4 text-sm font-semibold text-[#344054] transition hover:bg-[#f9fafb] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                    :disabled="startForm.processing"
                    @click="pendingModule = null"
                >
                    Zostań przy sesji
                </button>
                <button
                    type="button"
                    class="min-h-11 rounded-[5px] bg-[#101820] px-4 text-sm font-semibold text-white transition hover:bg-[#26323d] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] disabled:cursor-not-allowed disabled:bg-[#98a2b3]"
                    :disabled="startForm.processing"
                    @click="confirmReplacement"
                >
                    Rozpocznij nowy moduł
                </button>
            </div>
            <p v-if="startForm.errors.replace_active_session" class="mt-4 text-sm text-[#b42318]">
                {{ startForm.errors.replace_active_session }}
            </p>
        </section>
    </div>
</template>
