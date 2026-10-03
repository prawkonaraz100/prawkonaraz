<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type QuestionScope = 'all' | 'basic' | 'specialist';

interface GroupOption {
    id: number;
    label: string;
    questions_count: number;
    counts: Record<string, number>;
}

interface GroupBucket {
    label: string;
    options: GroupOption[];
}

const props = defineProps<{
    groups: GroupBucket[];
    processing: boolean;
}>();

const emit = defineEmits<{
    (event: 'start-topic', topicId: number, scope: QuestionScope): void;
}>();

const scopes: Array<{ value: QuestionScope; label: string }> = [
    { value: 'all', label: 'Wszystkie' },
    { value: 'basic', label: 'Podstawowe' },
    { value: 'specialist', label: 'Specjalistyczne' },
];

const selectedScope = ref<QuestionScope>('basic');
const visibleGroups = computed(() => props.groups.filter((group) => {
    if (selectedScope.value === 'basic') {
        return group.label === 'Pytania podstawowe';
    }

    if (selectedScope.value === 'specialist') {
        return group.label === 'Pytania specjalistyczne';
    }

    return true;
}).filter((group) => group.options.length > 0));

const iconLabel = (label: string) => label
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0])
    .join('')
    .toUpperCase();

const answeredCount = (option: GroupOption) => Math.min(
    Math.max((option.counts.all ?? option.questions_count) - (option.counts.unanswered ?? option.questions_count), 0),
    option.questions_count,
);

const progressPercent = (option: GroupOption) => option.questions_count > 0
    ? Math.round((answeredCount(option) / option.questions_count) * 100)
    : 0;
</script>

<template>
    <section class="mobile-topic-index mx-auto min-h-[calc(100svh-4.75rem)] max-w-[30rem] px-4 pb-6 pt-[max(env(safe-area-inset-top),1.1rem)] text-[#080b13] md:hidden" aria-labelledby="mobile-topic-index-title">
        <Link :href="route('session.index')" class="inline-flex min-h-10 items-center gap-2 text-[1rem] font-medium text-[#737b91] focus:outline-none focus-visible:rounded-lg focus-visible:ring-2 focus-visible:ring-[#ffba28]">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m14.5 4.5-7.5 7.5 7.5 7.5" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Wróć
        </Link>

        <h1 id="mobile-topic-index-title" class="mb-4 mt-2 text-[clamp(2.2rem,9vw,2.65rem)] font-extrabold leading-[1.08] tracking-[-0.055em]">Wybierz dział</h1>

        <div class="grid grid-cols-3 rounded-[1.1rem] bg-white p-1.5 shadow-[0_10px_30px_rgba(41,38,32,0.045)]" role="group" aria-label="Rodzaj pytań">
            <button
                v-for="scope in scopes"
                :key="scope.value"
                type="button"
                class="min-h-10 rounded-[0.8rem] px-1 text-[clamp(0.69rem,3.4vw,0.96rem)] font-medium tracking-[-0.025em] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e4a400]"
                :class="selectedScope === scope.value ? 'bg-gradient-to-br from-[#ffd876] to-[#ffbe49] text-[#0a0b0f]' : 'text-[#777e91] hover:bg-[#f7f6f3]'"
                :aria-pressed="selectedScope === scope.value"
                @click="selectedScope = scope.value"
            >
                {{ scope.label }}
            </button>
        </div>

        <div v-if="visibleGroups.length" class="mt-4 space-y-5">
            <section v-for="group in visibleGroups" :key="group.label" :aria-label="group.label">
                <h2 class="mb-2 px-1 text-[0.95rem] font-semibold text-[#737b91]">{{ group.label }}</h2>
                <ol class="space-y-2">
                    <li v-for="(option, index) in group.options" :key="option.id">
                        <button
                            type="button"
                            class="mobile-topic-card flex min-h-[4.15rem] w-full items-center gap-3 rounded-[1.05rem] bg-white px-2.5 py-2 text-left shadow-[0_7px_22px_rgba(41,38,32,0.045)] transition hover:-translate-y-0.5 hover:shadow-[0_9px_26px_rgba(41,38,32,0.09)] disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e4a400]"
                            :disabled="processing || option.questions_count === 0"
                            :aria-label="`Rozpocznij naukę: ${option.label}, ${answeredCount(option)} z ${option.questions_count} pytań`"
                            @click="emit('start-topic', option.id, selectedScope)"
                        >
                            <span class="mobile-topic-card__icon grid h-11 w-11 shrink-0 place-items-center rounded-[0.8rem] text-[1.05rem] font-bold tracking-[-0.04em]" :class="`mobile-topic-card__icon--${index % 6}`" aria-hidden="true">{{ iconLabel(option.label) }}</span>
                            <span class="min-w-0 flex-1 self-stretch py-0.5">
                                <span class="block text-[0.91rem] font-semibold leading-[1.15rem] tracking-[-0.03em]">{{ option.label }}</span>
                                <span class="mt-0.5 block text-[0.78rem] leading-4 text-[#858ba0] tabular-nums">{{ answeredCount(option) }} / {{ option.questions_count }} pytań</span>
                                <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-[#e8ecea]" aria-hidden="true"><span class="block h-full rounded-full bg-[#21c482]" :style="{ width: `${progressPercent(option)}%` }" /></span>
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-[#838ba4]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 4.5 7 7.5-7 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </button>
                    </li>
                </ol>
            </section>
        </div>

        <p v-else class="mt-8 rounded-2xl bg-white px-5 py-8 text-center text-sm text-[#737b91]">Brak działów dla wybranego rodzaju pytań.</p>
    </section>
</template>

<style scoped>
.mobile-topic-index {
    background: radial-gradient(circle at 75% 7%, rgba(239, 233, 220, 0.32), transparent 17rem), #faf9f6;
}

.mobile-topic-card__icon--0 { background: #fff7e5; color: #ed9c00; }
.mobile-topic-card__icon--1 { background: #fff0f0; color: #e42331; }
.mobile-topic-card__icon--2 { background: #eaf3ff; color: #1768e4; }
.mobile-topic-card__icon--3 { background: #f1efff; color: #6538e6; }
.mobile-topic-card__icon--4 { background: #e6f8f0; color: #078f60; }
.mobile-topic-card__icon--5 { background: #fff1e2; color: #ed7a00; }

@media (prefers-reduced-motion: reduce) {
    .mobile-topic-card { transition: none; }
}
</style>
