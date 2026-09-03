<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { renderInlineFormattedHtml } from '@/utils/explanationFormatting';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Category {
    id: number;
    code: string;
    name: string;
    questions_count: number;
}

interface QuestionMedia {
    kind: string;
    url: string | null;
    poster_url: string | null;
    mime_type: string | null;
}

interface QuestionItem {
    id: number;
    external_id: string;
    prompt: string;
    question_type: string;
    difficulty: number;
    points: number;
    media_count: number;
    topic: {
        id: number;
        key: string;
        name: string;
    } | null;
    license_category: {
        id: number | null;
        name: string | null;
        code: string | null;
    };
    media: QuestionMedia[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface QuestionsPagination {
    data: QuestionItem[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

type SortKey =
    | 'latest'
    | 'difficulty_asc'
    | 'difficulty_desc'
    | 'points_asc'
    | 'points_desc';

const props = defineProps<{
    categories: Category[];
    filters: {
        category: number | null;
        sort: SortKey;
    };
    questions: QuestionsPagination;
}>();

const sortOptions: Array<{ value: SortKey; label: string }> = [
    { value: 'latest', label: 'Najnowsze' },
    { value: 'difficulty_asc', label: 'Najlatwiejsze' },
    { value: 'difficulty_desc', label: 'Najtrudniejsze' },
    { value: 'points_asc', label: 'Najmniej punktow' },
    { value: 'points_desc', label: 'Najwiecej punktow' },
];

const selectedSort = ref<SortKey>(props.filters.sort);

watch(
    () => props.filters.sort,
    (sort) => {
        selectedSort.value = sort;
    },
);

const applySort = () => {
    const params: Record<string, string | number> = {
        sort: selectedSort.value,
    };

    if (props.filters.category !== null) {
        params.category = props.filters.category;
    }

    router.get(route('questions.index'), params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const difficultyLabel = (difficulty: number) =>
    ({
        1: 'Latwe',
        2: 'Podstawowe',
        3: 'Srednie',
        4: 'Trudne',
        5: 'Bardzo trudne',
    })[difficulty] ?? `Poziom ${difficulty}`;
</script>

<template>
    <Head title="Pytania" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.18em]">
                    Pytania
                </p>
                <h2 class="mt-4 text-3xl font-bold tracking-tight">
                    Lista pytan w wybranej kategorii.
                </h2>
            </div>
        </template>

        <div class="space-y-8">
            <section class="border border-black p-6">
                <div class="max-w-md">
                    <label for="questions_sort" class="text-sm font-medium">
                        Sortowanie
                    </label>
                    <select
                        id="questions_sort"
                        v-model="selectedSort"
                        class="mt-2 block w-full border border-black bg-white px-3 py-3 text-sm"
                        @change="applySort"
                    >
                        <option
                            v-for="option in sortOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>
            </section>

            <section class="space-y-4">
                <article
                    v-for="question in questions.data"
                    :key="question.id"
                    class="border border-black p-5"
                >
                    <div class="grid gap-5 lg:grid-cols-[15rem_1fr]">
                        <div class="overflow-hidden border border-black">
                            <img
                                v-if="question.media[0]?.kind === 'image' && question.media[0]?.url"
                                :src="question.media[0].url"
                                alt=""
                                loading="lazy"
                                class="h-40 w-full object-cover"
                            />
                            <img
                                v-else-if="question.media[0]?.kind === 'video' && question.media[0]?.poster_url"
                                :src="question.media[0].poster_url"
                                alt=""
                                loading="lazy"
                                class="h-40 w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-40 items-center justify-center px-4 text-center text-sm text-black/60"
                            >
                                Podglad medium pojawi sie tutaj.
                            </div>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.18em]">
                                <span class="font-medium">
                                    {{ question.license_category.code ?? 'Brak kategorii' }}
                                </span>
                                <span class="text-black/50">
                                    {{ question.external_id }}
                                </span>
                            </div>

                            <h3
                                class="mt-4 text-xl font-bold leading-8 [&_strong]:font-bold"
                                v-html="renderInlineFormattedHtml(question.prompt)"
                            />

                            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                                <span class="border border-black px-3 py-1">
                                    {{ difficultyLabel(question.difficulty) }}
                                </span>
                                <span class="border border-black px-3 py-1">
                                    {{ question.points }} pkt
                                </span>
                                <span class="border border-black px-3 py-1">
                                    {{ question.media_count }} mediow
                                </span>
                                <span v-if="question.topic" class="border border-black px-3 py-1">
                                    {{ question.topic.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>

                <div
                    v-if="!questions.data.length"
                    class="border border-black p-6 text-sm text-black/70"
                >
                    Brak pytan dla wybranego widoku.
                </div>
            </section>

            <nav v-if="questions.links.length > 3" class="flex flex-wrap items-center gap-2">
                <template v-for="link in questions.links" :key="`${link.label}-${link.url}`">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="border border-black px-4 py-2 text-sm font-medium"
                        :class="link.active ? 'bg-black text-white' : 'bg-white text-black'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="border border-black px-4 py-2 text-sm text-black/50"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AuthenticatedLayout>
</template>
