<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CourseModules from '@/Pages/QuestionCollections/Partials/CourseModules.vue';
import { Head, Link } from '@inertiajs/vue3';

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

const props = defineProps<{
    collection: {
        code: string;
        name: string;
        description: string | null;
        category_name: string | null;
        progress: {
            answered_count: number;
            total_questions: number;
            percent: number;
        };
        modules: CourseModule[];
    };
    active_session: {
        id: number;
        title: string;
    } | null;
    incorrect_questions: {
        count: number;
        url: string;
    };
    learning_url: string;
}>();

</script>

<template>
    <Head :title="collection.name" />

    <AuthenticatedLayout>
        <main class="min-h-screen bg-[#f7f8fa] px-5 py-8 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-6xl">
                <Link
                    :href="learning_url"
                    class="inline-flex items-center gap-2 text-sm font-medium text-[#475467] transition hover:text-[#101828] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                >
                    <span aria-hidden="true">←</span>
                    Wróć do nauki
                </Link>

                <header class="mt-8 border-b border-[#d0d5dd] pb-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#667085]">Kurs zawodowy</p>
                    <h1 class="mt-3 max-w-3xl text-3xl font-semibold text-[#101828] sm:text-4xl">{{ collection.name }}</h1>
                    <p v-if="collection.description" class="mt-4 max-w-2xl text-base leading-7 text-[#475467]">
                        {{ collection.description }}
                    </p>
                    <p v-if="collection.category_name" class="mt-4 text-sm text-[#667085]">
                        {{ collection.category_name }}
                    </p>
                    <p class="mt-4 text-sm font-medium text-[#344054]">
                        Postęp kursu: {{ collection.progress.answered_count }} z {{ collection.progress.total_questions }} pytań
                        <span class="text-[#667085]">({{ collection.progress.percent }}%)</span>
                    </p>
                </header>

                <section class="mt-7 flex flex-col gap-4 border-y border-[#dfe3e8] bg-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6" aria-labelledby="course-incorrect-questions-heading">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-[#667085]">Powtórki kursu</p>
                        <h2 id="course-incorrect-questions-heading" class="mt-1 text-lg font-semibold text-[#101828]">Pytania do poprawy</h2>
                        <p class="mt-1 text-sm text-[#667085]">
                            {{ incorrect_questions.count === 0 ? 'Nie masz teraz pytań na tej liście.' : `Czeka ${incorrect_questions.count} ${incorrect_questions.count === 1 ? 'pytanie' : 'pytań'} z tego kursu.` }}
                        </p>
                    </div>
                    <Link
                        :href="incorrect_questions.url"
                        class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-[5px] border border-[#cfd6de] px-5 text-sm font-semibold text-[#344054] transition hover:border-[#98a2b3] hover:bg-[#f9fafb] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff]"
                    >
                        Otwórz listę
                    </Link>
                </section>

                <CourseModules class="mt-7" :modules="collection.modules" :active-session="active_session" />
            </div>
        </main>
    </AuthenticatedLayout>
</template>
