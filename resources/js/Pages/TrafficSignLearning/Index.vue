<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MobileEmptyState from '@/Components/MobileEmptyState.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface SampleSign {
    id: number;
    code: string;
    name: string;
    image_url: string | null;
}

interface TrafficSignCategorySummary {
    slug: string;
    name: string;
    total_signs: number;
    mastered_signs: number;
    learning_signs: number;
    needs_review_signs: number;
    new_signs: number;
    progress_percent: number;
    sample_signs: SampleSign[];
}

interface SessionPreviewSign extends SampleSign {
    category: {
        slug: string | null;
        name: string | null;
    };
    public_url: string;
}

type TrainingMode = 'recognition' | 'similar_signs' | 'description_to_sign';

const props = defineProps<{
    overview: {
        trainable_count: number;
        mastered_count: number;
        learning_count: number;
        needs_review_count: number;
        new_count: number;
        progress_percent: number;
        mvp_category_slugs: string[];
    };
    categories: TrafficSignCategorySummary[];
    session_preview: {
        limit: number;
        signs: SessionPreviewSign[];
    };
}>();

const progressCircleStyle = computed(() => ({
    background: `conic-gradient(#023ea4 ${props.overview.progress_percent * 3.6}deg, #e5e7eb 0deg)`,
}));
const startForm = useForm<{
    category_slug: string | null;
    mode: TrainingMode;
}>({
    category_slug: null,
    mode: 'recognition',
});
const startTraining = (
    categorySlug: string | null = null,
    mode: TrainingMode = 'recognition',
) => {
    if (startForm.processing || props.overview.trainable_count === 0) {
        return;
    }

    startForm.category_slug = categorySlug;
    startForm.mode = mode;
    startForm.post(route('traffic-sign-learning.store'));
};

const selectedMode = ref<TrainingMode>('recognition');
const mobileModes: Array<{ value: TrainingMode; label: string; shortLabel: string }> = [
    { value: 'recognition', label: 'Rozpoznawanie', shortLabel: 'Rozpoznaj' },
    { value: 'similar_signs', label: 'Podobne znaki', shortLabel: 'Podobne' },
    { value: 'description_to_sign', label: 'Opis do znaku', shortLabel: 'Opis' },
];
const selectedModeLabel = computed(() => (
    mobileModes.find((mode) => mode.value === selectedMode.value)?.label ?? 'Rozpoznawanie'
));
</script>

<template>
    <Head title="Nauka znaków drogowych" />

    <AuthenticatedLayout>
        <section class="min-h-screen bg-[#f7f8fa] pb-[calc(5.5rem+env(safe-area-inset-bottom))] text-[#17191d] md:hidden">
            <header class="px-4 pb-5 pt-[calc(1.25rem+env(safe-area-inset-top))]">
                <p class="text-[0.78rem] font-semibold text-[#6b7280]">Nauka</p>
                <h1 class="mt-1 text-[1.72rem] font-bold leading-8 text-[#17191d]">Znaki drogowe</h1>
            </header>

            <section class="border-y border-[#dfe3e8] bg-white px-4 py-5" aria-labelledby="signs-today-heading">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[0.76rem] font-semibold text-[#6b7280]">Dzisiejszy trening</p>
                        <h2 id="signs-today-heading" class="mt-1 text-[1.2rem] font-bold leading-6 text-[#17191d]">
                            {{ overview.trainable_count > 0 ? `${overview.trainable_count} znaków w kolejce` : 'Brak znaków do treningu' }}
                        </h2>
                    </div>
                    <p class="shrink-0 text-[1.45rem] font-bold tabular-nums text-[#0b63ce]">{{ overview.progress_percent }}%</p>
                </div>

                <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#e8edf3]" aria-label="Postęp opanowania znaków">
                    <div class="h-full rounded-full bg-[#0b63ce]" :style="{ width: `${overview.progress_percent}%` }" />
                </div>

                <button
                    v-if="overview.trainable_count > 0"
                    type="button"
                    class="mt-5 flex min-h-12 w-full items-center justify-center rounded-[8px] bg-[#0b63ce] px-4 text-[0.96rem] font-semibold text-white transition hover:bg-[#084fa8] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-55"
                    :disabled="startForm.processing"
                    @click="startTraining(null, selectedMode)"
                >
                    {{ startForm.processing ? 'Uruchamianie...' : 'Rozpocznij trening' }}
                </button>

                <MobileEmptyState
                    v-else
                    class="mt-5"
                    title="Brak dostępnego materiału"
                    description="Gdy znaki zostaną dodane do zakresu, trening pojawi się w tym miejscu."
                    icon="inbox"
                />
            </section>

            <section class="mt-5 px-4" aria-labelledby="signs-progress-heading">
                <h2 id="signs-progress-heading" class="text-[0.82rem] font-semibold text-[#6b7280]">Postęp</h2>
                <div class="mt-2 divide-y divide-[#e3e7eb] rounded-[8px] border border-[#dfe3e8] bg-white">
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-[0.92rem] font-medium text-[#17191d]">Opanowane</span>
                        <span class="text-[0.92rem] font-semibold tabular-nums text-[#17191d]">{{ overview.mastered_count }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-[0.92rem] font-medium text-[#17191d]">Do powtórki</span>
                        <span class="text-[0.92rem] font-semibold tabular-nums text-[#b42318]">{{ overview.needs_review_count }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-[0.92rem] font-medium text-[#17191d]">Nowe</span>
                        <span class="text-[0.92rem] font-semibold tabular-nums text-[#17191d]">{{ overview.new_count }}</span>
                    </div>
                </div>
            </section>

            <section v-if="overview.trainable_count > 0" class="mt-5 px-4" aria-labelledby="signs-mode-heading">
                <h2 id="signs-mode-heading" class="text-[0.82rem] font-semibold text-[#6b7280]">Tryb treningu</h2>
                <div class="mt-2 grid grid-cols-3 gap-1 rounded-[8px] bg-[#e8edf3] p-1" role="group" aria-label="Wybór trybu treningu">
                    <button
                        v-for="mode in mobileModes"
                        :key="mode.value"
                        type="button"
                        class="min-h-10 rounded-[6px] px-1 text-[0.7rem] font-semibold leading-4 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b63ce]"
                        :class="selectedMode === mode.value ? 'bg-white text-[#17191d] shadow-sm' : 'text-[#667085]'"
                        :aria-pressed="selectedMode === mode.value"
                        @click="selectedMode = mode.value"
                    >
                        {{ mode.shortLabel }}
                    </button>
                </div>
                <p class="mt-2 text-[0.8rem] text-[#667085]">{{ selectedModeLabel }}</p>
            </section>

            <section v-if="categories.length > 0" class="mt-5 px-4" aria-labelledby="signs-categories-heading">
                <h2 id="signs-categories-heading" class="text-[0.82rem] font-semibold text-[#6b7280]">Kategorie</h2>
                <div class="mt-2 divide-y divide-[#e3e7eb] rounded-[8px] border border-[#dfe3e8] bg-white">
                    <button
                        v-for="category in categories"
                        :key="category.slug"
                        type="button"
                        class="flex min-h-[4.6rem] w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-[#f8fafc] focus:outline-none focus-visible:bg-[#f1f5f9] disabled:cursor-not-allowed disabled:opacity-55"
                        :disabled="startForm.processing || category.total_signs === 0"
                        @click="startTraining(category.slug, selectedMode)"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-[0.92rem] font-semibold leading-5 text-[#17191d]">{{ category.name }}</p>
                                <span class="shrink-0 text-[0.82rem] font-semibold tabular-nums text-[#667085]">{{ category.progress_percent }}%</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#e8edf3]">
                                <div class="h-full rounded-full bg-[#0b63ce]" :style="{ width: `${category.progress_percent}%` }" />
                            </div>
                            <p class="mt-1.5 text-[0.76rem] text-[#667085]">{{ category.mastered_signs }} z {{ category.total_signs }} opanowanych</p>
                        </div>
                        <span aria-hidden="true" class="text-[1.45rem] leading-none text-[#98a2b3]">›</span>
                    </button>
                </div>
            </section>

            <section v-if="session_preview.signs.length > 0" class="mt-5 px-4" aria-labelledby="signs-next-heading">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="signs-next-heading" class="text-[0.82rem] font-semibold text-[#6b7280]">Następne znaki</h2>
                    <Link :href="route('session.index')" class="text-[0.82rem] font-semibold text-[#0b63ce]">Nauka</Link>
                </div>
                <div class="mt-2 divide-y divide-[#e3e7eb] rounded-[8px] border border-[#dfe3e8] bg-white">
                    <div v-for="sign in session_preview.signs.slice(0, 4)" :key="sign.id" class="flex min-h-[4.25rem] items-center gap-3 px-4 py-2">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[6px] bg-[#f7f8fa] p-1">
                            <img v-if="sign.image_url" :src="sign.image_url" :alt="sign.name" class="max-h-full max-w-full object-contain" loading="lazy">
                            <span v-else class="text-[0.68rem] font-bold text-[#667085]">{{ sign.code }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[0.86rem] font-semibold text-[#17191d]">{{ sign.code }}</p>
                            <p class="truncate text-[0.76rem] text-[#667085]">{{ sign.name }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </section>

        <main class="hidden min-h-screen bg-[#f6f8fb] text-[#020309] md:block">
            <section class="border-b border-[#e5e7eb] bg-white">
                <div class="mx-auto grid w-full max-w-[76rem] gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_21rem] lg:px-8">
                    <div class="flex flex-col justify-center">
                        <p class="text-[0.76rem] font-semibold uppercase tracking-[0.16em] text-[#023ea4]">
                            Tryb nauki
                        </p>
                        <h1 class="mt-2 max-w-[42rem] text-[2rem] font-bold leading-[2.2rem] tracking-normal text-[#020309] sm:text-[2.55rem] sm:leading-[2.7rem]">
                            Nauka znaków drogowych
                        </h1>
                        <div class="mt-5 grid gap-2.5 sm:grid-cols-3">
                            <div class="rounded-[0.75rem] border border-[#e5e7eb] bg-[#f9fafb] px-4 py-3">
                                <p class="text-[0.73rem] font-semibold uppercase tracking-[0.12em] text-[#6b7280]">Zakres</p>
                                <p class="mt-1 text-[1.25rem] font-bold text-[#020309]">{{ overview.trainable_count }}</p>
                            </div>
                            <div class="rounded-[0.75rem] border border-[#e5e7eb] bg-[#f9fafb] px-4 py-3">
                                <p class="text-[0.73rem] font-semibold uppercase tracking-[0.12em] text-[#6b7280]">Nowe</p>
                                <p class="mt-1 text-[1.25rem] font-bold text-[#020309]">{{ overview.new_count }}</p>
                            </div>
                            <div class="rounded-[0.75rem] border border-[#e5e7eb] bg-[#f9fafb] px-4 py-3">
                                <p class="text-[0.73rem] font-semibold uppercase tracking-[0.12em] text-[#6b7280]">Powtórka</p>
                                <p class="mt-1 text-[1.25rem] font-bold text-[#020309]">{{ overview.needs_review_count }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[0.9rem] border border-[#dbe4f0] bg-white p-5 shadow-[0_18px_42px_rgba(15,23,42,0.08)]">
                        <div
                            class="mx-auto flex aspect-square w-full max-w-[13rem] items-center justify-center rounded-full p-3"
                            :style="progressCircleStyle"
                            aria-label="Postęp opanowania znaków"
                        >
                            <div class="flex h-full w-full flex-col items-center justify-center rounded-full bg-white text-center">
                                <span class="text-[2.05rem] font-bold leading-none text-[#023ea4]">{{ overview.progress_percent }}%</span>
                                <span class="mt-1 text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-[#6b7280]">opanowane</span>
                            </div>
                        </div>
                        <form class="mt-5 grid gap-2" @submit.prevent="startTraining(null)">
                            <button
                                type="submit"
                                class="flex h-12 w-full items-center justify-center rounded-[0.75rem] bg-[#023ea4] px-5 text-[0.98rem] font-semibold text-white transition hover:bg-[#012b7a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-55"
                                :disabled="startForm.processing || overview.trainable_count === 0"
                            >
                                Mieszany trening
                            </button>
                            <button
                                type="button"
                                class="flex h-12 w-full items-center justify-center rounded-[0.75rem] border border-[#d1d5db] bg-white px-5 text-[0.98rem] font-semibold text-[#020309] transition hover:border-[#023ea4] hover:text-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-55"
                                :disabled="startForm.processing || overview.trainable_count === 0"
                                @click="startTraining(null, 'similar_signs')"
                            >
                                Podobne znaki
                            </button>
                            <button
                                type="button"
                                class="flex h-12 w-full items-center justify-center rounded-[0.75rem] border border-[#d1d5db] bg-white px-5 text-[0.98rem] font-semibold text-[#020309] transition hover:border-[#023ea4] hover:text-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-55"
                                :disabled="startForm.processing || overview.trainable_count === 0"
                                @click="startTraining(null, 'description_to_sign')"
                            >
                                Opis -> znak
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-[76rem] px-4 py-7 sm:px-6 lg:px-8">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="category in categories"
                        :key="category.slug"
                        class="flex h-full flex-col rounded-[0.85rem] border border-[#e5e7eb] bg-white p-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)]"
                    >
                        <div class="grid min-h-[4.65rem] grid-cols-[1fr_auto] items-start gap-3">
                            <div class="min-w-0">
                                <h2 class="min-h-[2.5rem] text-[0.98rem] font-bold leading-[1.22rem] text-[#020309]">{{ category.name }}</h2>
                                <p class="mt-1 text-[0.78rem] font-medium text-[#6b7280]">
                                    {{ category.mastered_signs }} / {{ category.total_signs }}
                                </p>
                            </div>
                            <span class="rounded-full bg-[#eef2fa] px-2.5 py-1 text-[0.72rem] font-bold text-[#023ea4]">
                                {{ category.progress_percent }}%
                            </span>
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#eef2f7]">
                            <div
                                class="h-full rounded-full bg-[#023ea4]"
                                :style="{ width: `${category.progress_percent}%` }"
                            />
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="min-h-[3.55rem] rounded-[0.55rem] bg-[#f9fafb] px-2.5 py-2">
                                <p class="text-[0.66rem] font-semibold uppercase tracking-[0.1em] text-[#6b7280]">Nowe</p>
                                <p class="mt-0.5 text-[0.96rem] font-bold text-[#020309]">{{ category.new_signs }}</p>
                            </div>
                            <div class="min-h-[3.55rem] rounded-[0.55rem] bg-[#f9fafb] px-2.5 py-2">
                                <p class="text-[0.66rem] font-semibold uppercase tracking-[0.1em] text-[#6b7280]">Nauka</p>
                                <p class="mt-0.5 text-[0.96rem] font-bold text-[#020309]">{{ category.learning_signs }}</p>
                            </div>
                            <div class="min-h-[3.55rem] rounded-[0.55rem] bg-[#f9fafb] px-2.5 py-2">
                                <p class="text-[0.66rem] font-semibold uppercase tracking-[0.1em] text-[#6b7280]">Powt.</p>
                                <p class="mt-0.5 text-[0.96rem] font-bold text-[#020309]">{{ category.needs_review_signs }}</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div
                                v-for="sign in category.sample_signs"
                                :key="sign.id"
                                class="flex h-14 w-full items-center justify-center rounded-[0.55rem] border border-[#edf0f4] bg-[#f9fafb] p-1.5"
                            >
                                <img
                                    v-if="sign.image_url"
                                    :src="sign.image_url"
                                    :alt="sign.name"
                                    class="max-h-full max-w-full object-contain"
                                    loading="lazy"
                                >
                                <span v-else class="text-[0.68rem] font-bold text-[#6b7280]">{{ sign.code }}</span>
                            </div>
                        </div>

                        <div class="mt-auto pt-4">
                            <button
                                type="button"
                                class="flex h-10 w-full items-center justify-center rounded-[0.65rem] border border-[#d1d5db] bg-white px-4 text-[0.86rem] font-semibold text-[#020309] transition hover:border-[#023ea4] hover:text-[#023ea4] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#023ea4] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-55"
                                :disabled="startForm.processing || category.total_signs === 0"
                                @click="startTraining(category.slug)"
                            >
                                Trenuj kategorię
                            </button>
                        </div>
                    </article>
                </div>

                <div class="mt-7 rounded-[0.9rem] border border-[#dbe4f0] bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-[1.1rem] font-bold text-[#020309]">Następna sesja</h2>
                            <p class="text-[0.86rem] font-medium text-[#6b7280]">{{ session_preview.signs.length }} z {{ session_preview.limit }} znaków</p>
                        </div>
                        <Link
                            :href="route('session.index')"
                            class="inline-flex h-10 items-center justify-center rounded-[0.7rem] border border-[#d1d5db] bg-white px-4 text-[0.9rem] font-semibold text-[#020309] transition hover:border-[#023ea4] hover:text-[#023ea4]"
                        >
                            Wróć do nauki
                        </Link>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="sign in session_preview.signs"
                            :key="sign.id"
                            class="flex items-center gap-3 rounded-[0.7rem] border border-[#edf0f4] bg-[#f9fafb] p-3"
                        >
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[0.55rem] bg-white p-1.5">
                                <img
                                    v-if="sign.image_url"
                                    :src="sign.image_url"
                                    :alt="sign.name"
                                    class="max-h-full max-w-full object-contain"
                                    loading="lazy"
                                >
                                <span v-else class="text-[0.68rem] font-bold text-[#6b7280]">{{ sign.code }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-[0.8rem] font-bold text-[#020309]">{{ sign.code }}</p>
                                <p class="truncate text-[0.74rem] font-medium text-[#6b7280]">{{ sign.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
