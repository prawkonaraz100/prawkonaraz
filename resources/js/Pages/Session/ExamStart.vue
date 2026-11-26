<script setup lang="ts">
import MobileBackButton from '@/Components/MobileBackButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SessionReplacementDialog from '@/Components/SessionReplacementDialog.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import mobileDashboardHero from '../../../images/session/optimized/mobile-dashboard-hero.webp';
import '../../../css/barlow.css';

const props = defineProps<{
    category: { id: number; code: string; name: string };
    courseProgress: {
        percent: number;
        answered_questions: number;
        total_questions: number;
    };
    activeSession: {
        mode: string;
        title: string;
        resume_url: string;
    } | null;
    activeAdrSessionId?: number | null;
}>();

const form = useForm({
    license_category_id: props.category.id,
    mode: 'exam',
    ui_shell: 'exam',
    question_count: 32,
    question_topic_id: null as number | null,
    question_scope: 'all',
    question_status: 'all',
    randomize_order: false,
    replace_active_session_id: null as number | null,
});

const progressPercent = computed(() => Math.max(0, Math.min(100, Number(props.courseProgress.percent) || 0)));
const activeExam = computed(() => props.activeSession?.mode === 'exam' ? props.activeSession : null);
const formError = computed(() => Object.values(form.errors)[0] ?? null);

const showSessionReplacement = ref(false);

const submitExam = () => {
    if (form.processing) return;

    showSessionReplacement.value = false;
    form.replace_active_session_id = props.activeAdrSessionId ?? null;
    form.post(route('study-sessions.store'));
};

const startExam = () => {
    if (form.processing) return;

    if (props.activeSession || props.activeAdrSessionId) {
        showSessionReplacement.value = true;
        return;
    }

    submitExam();
};
</script>

<template>
    <Head :title="`Egzamin próbny — kategoria ${category.code}`" />

    <AuthenticatedLayout class="max-md:bg-[#302e2b]">
        <SessionReplacementDialog
            :show="showSessionReplacement"
            :session-id="activeAdrSessionId"
            :session-title="activeAdrSessionId ? 'Testy ADR' : (activeSession?.title ?? 'Bieżąca sesja')"
            :resume-href="activeAdrSessionId ? undefined : activeSession?.resume_url"
            dialog-title="Rozpocząć nowy egzamin?"
            description="Nowy egzamin zakończy obecną sesję."
            confirm-label="Rozpocznij egzamin"
            :busy="form.processing"
            @cancel="showSessionReplacement = false"
            @confirm="submitExam"
        />
        <section class="exam-start mx-auto w-full max-w-[36rem] bg-[#faf9f7] px-4 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-3 text-[#080c16] max-md:bg-[#302e2b] max-md:pb-6 max-md:pt-[max(env(safe-area-inset-top),1rem)] max-md:text-[#ffffffd9] md:my-8 md:rounded-[2rem] md:px-7 md:pb-7 md:pt-7" aria-labelledby="exam-start-title">
            <Link :href="route('session.index')" class="hidden min-h-8 items-center gap-2 text-base font-semibold text-[#747d94] transition hover:text-[#101827] focus:outline-none focus-visible:rounded-lg focus-visible:ring-2 focus-visible:ring-[#e7ac22] md:inline-flex" aria-label="Wróć do strony głównej nauki">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true"><path d="m14.5 4.5-7.5 7.5 7.5 7.5" /></svg>
                Wróć
            </Link>

            <header class="px-0.5 max-md:flex max-md:items-center max-md:gap-3 max-md:px-0 md:mt-2">
                <MobileBackButton :href="route('session.index')" label="Wróć do strony głównej" />
                <div class="min-w-0">
                    <h1 id="exam-start-title" class="text-[clamp(1.9rem,8vw,3rem)] font-extrabold leading-[1.03] tracking-[-0.055em] max-md:text-[1.75rem] max-md:font-bold max-md:leading-[1.1] max-md:tracking-[-0.015em]">Rozpocznij egzamin</h1>
                    <p class="mt-1 text-[clamp(1rem,4vw,1.3rem)] font-medium text-[#737c93] max-md:text-sm max-md:font-normal max-md:text-[#ffffffb8]">Egzamin próbny — kategoria {{ category.code }}</p>
                </div>
            </header>

            <section class="exam-start__panel relative mt-4 min-h-[15rem] overflow-hidden rounded-[1.4rem] bg-white p-4 shadow-[0_14px_35px_rgba(30,40,55,0.045)] max-md:min-h-0" aria-labelledby="exam-format-title">
                <img :src="mobileDashboardHero" alt="" class="pointer-events-none absolute -bottom-3 -right-20 h-auto w-[92%] max-w-none opacity-95 max-md:hidden" aria-hidden="true">
                <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(90deg,#fff_0%,#fff_37%,rgba(255,255,255,.92)_57%,rgba(255,255,255,.08)_100%)] max-md:hidden" aria-hidden="true" />
                <svg class="pointer-events-none absolute right-7 top-12 h-24 w-20 rotate-[8deg] opacity-80 max-md:right-5 max-md:top-10 max-md:opacity-90" viewBox="0 0 96 116" fill="none" aria-hidden="true">
                    <rect x="8" y="11" width="79" height="101" rx="8" fill="#f8fbfd" stroke="#718198" stroke-width="5" />
                    <path d="M35 12a13 13 0 0 1 26 0h8v12H27V12h8Z" fill="#50617b" />
                    <circle cx="48" cy="10" r="4" fill="white" />
                    <path d="m22 43 5 5 9-11M22 65l5 5 9-11M22 87l5 5 9-11" stroke="#0ac780" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M45 44h27M45 66h27M45 88h20" stroke="#d0d7df" stroke-width="6" stroke-linecap="round" />
                </svg>
                <div class="relative z-10">
                    <h2 id="exam-format-title" class="text-sm font-semibold text-[#8993a8] max-md:text-[#ffffffb8]">Oficjalny format egzaminu</h2>
                    <ul class="mt-3 space-y-2">
                        <li class="flex min-h-9 items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#e9f3ff] text-[#1874e7] max-md:bg-[#1c2c3d] max-md:text-[#7ab8ff]" aria-hidden="true">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2.5" width="14" height="19" rx="2"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4"/></svg>
                            </span>
                            <span class="text-xl font-extrabold tracking-tight">32 pytania</span>
                        </li>
                        <li class="flex min-h-9 items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#e6f8f1] text-[#08aa70] max-md:bg-[#81b64c26] max-md:text-[#81b64c]" aria-hidden="true">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 6.5V12l3.5 2"/></svg>
                            </span>
                            <span class="text-xl font-extrabold tracking-tight">25 minut</span>
                        </li>
                        <li class="flex min-h-9 items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#fff5df] text-[#e6a300] max-md:bg-[#3a2f1c] max-md:text-[#ffc94a]" aria-hidden="true">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h10v7a5 5 0 0 1-10 0V3ZM7 6H4v3a4 4 0 0 0 4 4M17 6h3v3a4 4 0 0 1-4 4M12 15v5M8 21h8"/></svg>
                            </span>
                            <span class="text-sm font-bold leading-tight">Minimum <strong class="text-base">68</strong> punktów</span>
                        </li>
                        <li class="flex min-h-9 items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#f1f4f6] text-[#8191a4] max-md:bg-[#ffffff1a] max-md:text-[#ffffffb8]" aria-hidden="true">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="13" width="4" height="8" rx="1"/><rect x="10" y="8" width="4" height="13" rx="1"/><rect x="16" y="3" width="4" height="18" rx="1"/></svg>
                            </span>
                            <span class="text-sm font-bold leading-tight">Maksymalnie <strong class="text-base">74</strong> punkty</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="mt-3 hidden rounded-[1.4rem] bg-white px-4 py-3 shadow-[0_14px_35px_rgba(30,40,55,0.045)] md:block" aria-labelledby="exam-before-title">
                <h2 id="exam-before-title" class="text-xl font-extrabold tracking-tight">Przed rozpoczęciem</h2>
                <ul class="mt-2 divide-y divide-[#e9ebef]">
                    <li class="flex items-start gap-3 py-2">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#fff5e2] text-[#e8a30b]" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9"><path d="M12 20c-2.7-2.3-5.3-2.8-9-2.2V4.5C6.5 3.9 9.3 4.5 12 6.6c2.7-2.1 5.5-2.7 9-2.1v13.3c-3.7-.6-6.3-.1-9 2.2ZM12 6.6V20"/></svg>
                        </span>
                        <span><strong class="block text-sm font-bold">Czytaj pytania uważnie</strong><span class="mt-0.5 block text-xs leading-5 text-[#8993a8]">Zastanów się przed wyborem odpowiedzi.</span></span>
                    </li>
                    <li class="flex items-start gap-3 py-2">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#ffeded] text-[#e92b42]" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="9"/><path d="M7 12h10" stroke-linecap="round"/></svg>
                        </span>
                        <span><strong class="block text-sm font-bold">Nie cofaj odpowiedzi</strong><span class="mt-0.5 block text-xs leading-5 text-[#8993a8]">Po zatwierdzeniu nie będzie możliwości zmiany.</span></span>
                    </li>
                    <li class="flex items-start gap-3 py-2">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#e8f9ef] text-[#0bb56c]" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3"><path d="m4 12.5 5.5 5.5L20 6"/></svg>
                        </span>
                        <span><strong class="block text-sm font-bold">Skup się i odpowiadaj bez pośpiechu</strong><span class="mt-0.5 block text-xs leading-5 text-[#8993a8]">Masz wystarczająco dużo czasu na każdy krok.</span></span>
                    </li>
                </ul>
            </section>

            <section class="exam-start__panel mt-2.5 rounded-[1.4rem] bg-white px-4 py-2.5 shadow-[0_14px_35px_rgba(30,40,55,0.045)] max-md:mt-3 max-md:py-3.5" aria-labelledby="exam-progress-title">
                <h2 id="exam-progress-title" class="text-xl font-extrabold tracking-tight max-md:font-bold">Twoje przygotowanie</h2>
                <div class="mt-1.5 flex items-center gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#e6f8f1] text-[#0cbb79] max-md:bg-[#81b64c26] max-md:text-[#81b64c]" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="14" width="4" height="7" rx="1"/><rect x="10" y="9" width="4" height="12" rx="1"/><rect x="16" y="4" width="4" height="17" rx="1"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold">Przerobione pytania <strong class="text-[#0dbb78] max-md:text-[#81b64c]">{{ progressPercent }}%</strong></p>
                            <span class="text-right text-[0.65rem] text-[#8993a8] max-md:text-[0.75rem] max-md:text-[#ffffffb8]">{{ courseProgress.answered_questions }} / {{ courseProgress.total_questions }}</span>
                        </div>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-[#e7e9eb] max-md:bg-[#ffffff1a]" role="progressbar" :aria-valuenow="progressPercent" aria-valuemin="0" aria-valuemax="100" aria-label="Przerobione pytania">
                            <span class="block h-full rounded-full bg-[#13c98e] max-md:bg-[#81b64c]" :style="{ width: `${progressPercent}%` }" />
                        </div>
                    </div>
                </div>
            </section>

            <Link v-if="activeExam" :href="activeExam.resume_url" class="exam-start__secondary mt-3 flex min-h-11 items-center justify-center rounded-xl bg-white px-4 text-sm font-semibold text-[#515d72] shadow-[0_8px_20px_rgba(30,40,55,0.04)] max-md:min-h-12 max-md:text-[0.9375rem]">Kontynuuj rozpoczęty egzamin <span class="hidden md:inline md:ml-1" aria-hidden="true">→</span></Link>

            <p v-if="formError" role="alert" class="mt-4 rounded-xl bg-[#fff0ee] px-4 py-3 text-sm text-[#ab382c] max-md:bg-[#3a1f22] max-md:text-[#ffb4b4]">{{ formError }}</p>
            <button type="button" class="exam-start__cta mt-1.5 flex min-h-[4.25rem] w-full items-center justify-center gap-3 rounded-[1.05rem] bg-gradient-to-r from-[#ffc13d] to-[#ffbd37] px-5 text-base font-bold text-[#101827] shadow-[0_10px_25px_rgba(224,158,32,0.14)] transition hover:brightness-[1.04] disabled:cursor-wait disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#aa7114] focus-visible:ring-offset-2 max-md:mt-4 max-md:min-h-14 max-md:text-[1.125rem] max-md:font-bold max-md:text-white max-md:focus-visible:ring-white max-md:focus-visible:ring-offset-[#302e2b]" :disabled="form.processing" @click="startExam">
                {{ form.processing ? 'Uruchamianie egzaminu...' : 'Rozpocznij egzamin' }}
                <span class="hidden md:inline text-2xl font-normal leading-none" aria-hidden="true">→</span>
            </button>
        </section>
    </AuthenticatedLayout>
</template>

<style scoped>
@media (max-width: 767px) {
    .exam-start {
        font-family: Barlow, Figtree, ui-sans-serif, system-ui, sans-serif;
    }

    .exam-start :is(h1, h2) {
        font-family: inherit;
    }

    /* chess.com-style surfaces (see .pk-* in app.css for the measured tokens). */
    .exam-start__panel {
        border-radius: 5px;
        background: rgb(0 0 0 / 20%);
        box-shadow: none;
    }

    .exam-start__secondary {
        border-radius: 10px;
        background-color: transparent;
        background-image: linear-gradient(rgb(255 255 255 / 10%), rgb(255 255 255 / 5%));
        color: rgb(255 255 255 / 85%);
        box-shadow: inset 0 1px 0 rgb(255 255 255 / 8%), 0 1px 2px rgb(0 0 0 / 14%), 0 2px 4px rgb(0 0 0 / 10%);
    }

    .exam-start__cta {
        border-radius: 10px;
        background-color: #81b64c;
        background-image: linear-gradient(#81b64c, #5d9948);
        box-shadow:
            inset 0 1px 0 rgb(178 224 104 / 40%),
            inset 0 -1px 0 #45753c,
            inset 0 2px 4px rgb(178 224 104 / 50%),
            inset 0 -2px 4px rgb(69 117 60 / 50%),
            0 1px 2px rgb(0 0 0 / 14%),
            0 2px 4px rgb(0 0 0 / 10%);
    }

    .exam-start__cta:active:not(:disabled) {
        filter: brightness(0.94);
    }
}
</style>
