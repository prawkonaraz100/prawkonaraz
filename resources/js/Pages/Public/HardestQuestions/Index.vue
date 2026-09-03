<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import { renderInlineFormattedHtml } from '@/utils/explanationFormatting';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PageMeta {
    title: string;
    description: string;
    canonical_path: string;
}

interface SelectedCategory {
    id: number;
    code: string;
    slug: string;
    name: string;
    short_name: string;
    description: string | null;
}

interface Summary {
    questions_total: number;
    questions_analyzed: number;
    answers_count: number;
    users_count: number;
    active_categories_count: number;
    window_label: string;
}

interface RankingDefinition {
    key: string;
    label: string;
    description: string;
    field?: string;
}

interface CategoryCard {
    id: number;
    code: string;
    slug: string;
    name: string;
    short_name: string;
    description: string | null;
    path: string;
    questions_analyzed: number;
    answers_count: number;
    users_count: number;
    avg_difficulty_score: number | null;
}

interface QuestionMedia {
    kind: string;
    url: string | null;
    poster_url: string | null;
}

interface RankedQuestion {
    question_id: number;
    external_id: string | null;
    prompt: string;
    difficulty: number;
    points: number;
    category: {
        id: number;
        code: string;
        slug: string;
        name: string;
    };
    topic: {
        id: number | null;
        key: string | null;
        name: string;
    };
    users_count: number;
    answers_count: number;
    first_try_error_pct: number;
    repeat_fail_pct: number;
    median_response_time_ms: number | null;
    mastered_rate_pct: number;
    avg_attempts: number;
    mastery_lag_score: number;
    confidence_score: number;
    difficulty_score: number;
    media: QuestionMedia | null;
    practice_url: string;
}

interface TopicCard {
    key: string;
    name: string;
    questions_count: number;
    answers_count: number;
    avg_difficulty_score: number;
}

interface MethodologyCard {
    title: string;
    description: string;
}

const props = defineProps<{
    page: PageMeta;
    selected_category: SelectedCategory | null;
    summary: Summary;
    ranking: RankingDefinition;
    ranking_options: RankingDefinition[];
    categories: CategoryCard[];
    featured_question: RankedQuestion | null;
    top_questions: RankedQuestion[];
    top_topics: TopicCard[];
    methodology: MethodologyCard[];
}>();

const basePath = computed(() =>
    props.selected_category
        ? `/najtrudniejsze-pytania-na-prawo-jazdy/kategoria/${props.selected_category.slug}`
        : '/najtrudniejsze-pytania-na-prawo-jazdy',
);

const rankingHref = (rankingKey: string) => {
    if (rankingKey === 'overall') {
        return basePath.value;
    }

    return `${basePath.value}?ranking=${rankingKey}`;
};

const categoryHref = (path: string) => {
    if (props.ranking.key === 'overall') {
        return path;
    }

    return `${path}?ranking=${props.ranking.key}`;
};

const scoreLabel = (score: number) => {
    if (score >= 75) return 'Bardzo trudne';
    if (score >= 55) return 'Wyraznie trudne';
    if (score >= 35) return 'Podchwytliwe';

    return 'Rosnaca trudnosc';
};

const confidenceLabel = (score: number) => {
    if (score >= 85) return 'Mocna proba';
    if (score >= 65) return 'Solidna proba';
    if (score >= 45) return 'Wstepna proba';

    return 'Bardzo mala proba';
};

const formatSeconds = (value: number | null) =>
    value !== null ? `${(value / 1000).toFixed(1).replace('.', ',')} s` : 'Brak danych';

const topTenQuestions = computed(() => props.top_questions.slice(0, 10));

const topTenMaxErrorPct = computed(() => {
    const values = topTenQuestions.value.map((question) => question.first_try_error_pct ?? 0);

    return Math.max(1, ...values);
});

const categoryHeat = (category: CategoryCard) => {
    const score = category.avg_difficulty_score ?? 0;
    const normalized = Math.min(Math.max(score / 100, 0), 1);
    // Warm-but-readable scale: near-white → soft amber.
    const alpha = 0.08 + normalized * 0.22;

    return `rgba(245, 158, 11, ${alpha.toFixed(3)})`;
};

const categoryBorder = (category: CategoryCard) => {
    const score = category.avg_difficulty_score ?? 0;
    const normalized = Math.min(Math.max(score / 100, 0), 1);
    const alpha = 0.12 + normalized * 0.26;

    return `rgba(245, 158, 11, ${alpha.toFixed(3)})`;
};

const faqItems = [
    {
        question: 'Skad biora sie te statystyki?',
        answer: `Liczymy je na podstawie realnych odpowiedzi kursantow: bledow przy pierwszej probie, powracajacych pomylek, czasu odpowiedzi i tempa przechodzenia pytan do utrwalenia. Dane na tej stronie obejmuja teraz ${props.summary.window_label.toLowerCase()}.`,
    },
    {
        question: 'Czy to jest ranking oficjalnych pytan?',
        answer: 'Ranking dotyczy pytan, ktore sa aktualnie aktywne w naszej bazie i maja wystarczajace dane z nauki kursantow.',
    },
    {
        question: 'Dlaczego niektore kategorie maja malo danych?',
        answer: 'Ta sekcja rośnie wraz z aktywnoscia kursantow. Nowe albo mniej popularne kategorie beda potrzebowaly czasu, zeby zbudowac mocniejsza probe.',
    },
];
</script>

<template>
    <Head :title="page.title">
        <meta name="description" :content="page.description" />
        <link rel="canonical" :href="page.canonical_path" />
    </Head>

    <div class="flex min-h-screen flex-col bg-[#fbfaf7] text-[#1f1d18]">
        <SiteHeader />

        <main class="mx-auto max-w-6xl flex-1 px-4 py-10 sm:px-6 lg:px-8">
            <section class="rounded-[2rem] border border-[#e3dccf] bg-white px-6 py-8 shadow-[0_18px_48px_rgba(46,39,26,0.06)] sm:px-8">
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                    Publiczna analityka trudnosci
                </p>

                <div class="mt-4 grid gap-8 lg:grid-cols-[1.15fr_0.85fr]">
                    <div>
                        <h1 class="text-4xl font-bold tracking-tight text-[#1f1d18] sm:text-5xl">
                            {{ page.title }}
                        </h1>
                        <p class="mt-5 max-w-3xl text-base leading-8 text-[#5d584e]">
                            {{ page.description }}
                        </p>

                        <div
                            v-if="selected_category"
                            class="mt-5 inline-flex items-center gap-2 rounded-full border border-[#d9d1c4] bg-[#f8f5ee] px-4 py-2 text-sm font-semibold text-[#1f1d18]"
                        >
                                    <span>{{ selected_category.short_name ?? selected_category.code }}</span>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.4rem] border border-[#e4ddd0] bg-[#fcfaf6] p-5">
                            <p class="text-sm text-[#6c6558]">Pytania z danymi</p>
                            <p class="mt-3 text-3xl font-bold text-[#1f1d18]">{{ summary.questions_analyzed }}</p>
                            <p class="mt-2 text-sm text-[#7d776b]">
                                z {{ summary.questions_total }} aktywnych pytan
                            </p>
                        </div>
                        <div class="rounded-[1.4rem] border border-[#e4ddd0] bg-[#fcfaf6] p-5">
                            <p class="text-sm text-[#6c6558]">Odpowiedzi kursantow</p>
                            <p class="mt-3 text-3xl font-bold text-[#1f1d18]">{{ summary.answers_count }}</p>
                            <p class="mt-2 text-sm text-[#7d776b]">
                                okno: {{ summary.window_label }}
                            </p>
                        </div>
                        <div class="rounded-[1.4rem] border border-[#e4ddd0] bg-[#fcfaf6] p-5">
                            <p class="text-sm text-[#6c6558]">Kursanci w probie</p>
                            <p class="mt-3 text-3xl font-bold text-[#1f1d18]">{{ summary.users_count }}</p>
                            <p class="mt-2 text-sm text-[#7d776b]">
                                uwzglednieni w biezacym rankingu
                            </p>
                        </div>
                        <div class="rounded-[1.4rem] border border-[#e4ddd0] bg-[#fcfaf6] p-5">
                            <p class="text-sm text-[#6c6558]">Aktywne kategorie</p>
                            <p class="mt-3 text-3xl font-bold text-[#1f1d18]">{{ summary.active_categories_count }}</p>
                            <p class="mt-2 text-sm text-[#7d776b]">
                                z aktualnymi danymi trudnosci
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-[1.2rem] border border-[#e4ddd0] bg-[#fcfaf6] px-5 py-4">
                    <p class="text-sm font-semibold text-[#1f1d18]">
                        Jak czytać ten ranking
                    </p>
                    <p class="mt-2 text-sm leading-7 text-[#5d584e]">
                        Ten widok pokazuje, co sprawia kursantom największą trudność
                        <span class="font-semibold text-[#1f1d18]">w oknie {{ summary.window_label }}</span>.
                        Nie jest to archiwum całej historii serwisu, tylko bieżący obraz najtrudniejszych pytań.
                        Starsze, dzienne trendy są później redukowane do archiwum miesięcznego, żeby zachować historię bez dociążania systemu.
                    </p>
                </div>
            </section>

            <section class="mt-8 space-y-5">
                <div>
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                        Kategorie
                    </p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            href="/najtrudniejsze-pytania-na-prawo-jazdy"
                            class="group relative overflow-hidden rounded-[1.25rem] border bg-white px-5 py-5 text-left shadow-[0_12px_28px_rgba(46,39,26,0.04)] transition hover:-translate-y-[1px] hover:shadow-[0_18px_40px_rgba(46,39,26,0.06)]"
                            :class="selected_category === null ? 'border-[#5d8a34]' : 'border-[#e4ddd0]'"
                        >
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                                Wszystkie
                            </p>
                            <p class="mt-3 text-3xl font-bold tracking-tight text-[#1f1d18]">Kategorie</p>
                            <p class="mt-2 text-sm leading-7 text-[#6a645a]">
                                Zobacz przekroj calego rankingu bez filtrowania.
                            </p>
                        </Link>

                        <Link
                            v-for="category in categories"
                            :key="category.id"
                            :href="categoryHref(category.path)"
                            class="group relative overflow-hidden rounded-[1.25rem] border px-5 py-5 text-left shadow-[0_12px_28px_rgba(46,39,26,0.04)] transition hover:-translate-y-[1px] hover:shadow-[0_18px_40px_rgba(46,39,26,0.06)]"
                            :class="selected_category?.id === category.id ? 'border-[#5d8a34]' : 'border-[#e4ddd0]'"
                            :style="{
                                backgroundColor: categoryHeat(category),
                                borderColor: selected_category?.id === category.id ? '#5d8a34' : categoryBorder(category),
                            }"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                                        Kategoria
                                    </p>
                                    <p class="mt-3 text-3xl font-bold tracking-tight text-[#1f1d18]">
                                        {{ category.code }}
                                    </p>
                                </div>
                                <div class="rounded-full border border-[#ddd5c8] bg-white/70 px-3 py-1 text-xs font-semibold text-[#1f1d18]">
                                    {{ category.questions_analyzed }} pytań
                                </div>
                            </div>
                            <p class="mt-3 text-sm leading-7 text-[#6a645a]">
                                {{ category.short_name || category.name }}
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-[#1f1d18]">
                                <span class="rounded-full border border-[#ddd5c8] bg-white/70 px-3 py-1">
                                    Próba: {{ category.users_count }} kursantów
                                </span>
                                <span class="rounded-full border border-[#ddd5c8] bg-white/70 px-3 py-1">
                                    Odp.: {{ category.answers_count }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>

                <div>
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                        Typ rankingu
                    </p>
                    <div class="mt-3 grid gap-3 lg:grid-cols-4">
                        <Link
                            v-for="option in ranking_options"
                            :key="option.key"
                            :href="rankingHref(option.key)"
                            class="rounded-[1.2rem] border px-4 py-4 text-left transition"
                            :class="ranking.key === option.key ? 'border-[#5d8a34] bg-[#f3f8ed]' : 'border-[#ddd5c8] bg-white hover:bg-[#faf7f1]'"
                        >
                            <p class="font-semibold text-[#1f1d18]">
                                {{ option.label }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-[#6a645a]">
                                {{ option.description }}
                            </p>
                        </Link>
                    </div>
                </div>
            </section>

            <section v-if="topTenQuestions.length" class="mt-10">
                <div class="rounded-[2rem] border border-[#e3dccf] bg-white px-6 py-8 shadow-[0_18px_48px_rgba(46,39,26,0.06)] sm:px-8">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                                Top 10 najtrudniejszych teraz
                            </p>
                            <h2 class="mt-3 text-2xl font-bold tracking-tight text-[#1f1d18] sm:text-3xl">
                                Najwięcej błędów przy pierwszej próbie
                            </h2>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6a645a]">
                                To pytania, które najczęściej wywracają wynik w oknie
                                <span class="font-semibold text-[#1f1d18]">{{ summary.window_label }}</span>.
                            </p>
                        </div>
                        <div class="text-sm font-semibold text-[#6a645a]">
                            Skala: 0–{{ topTenMaxErrorPct.toFixed(0) }}%
                        </div>
                    </div>

                    <ol class="mt-6 space-y-3">
                        <li
                            v-for="(question, index) in topTenQuestions"
                            :key="question.question_id"
                            class="rounded-[1.2rem] border border-[#e4ddd0] bg-[#fcfaf6] p-4 transition hover:bg-white"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                                        #{{ index + 1 }} · {{ question.category.code }} · {{ question.topic.name }}
                                    </p>
                                    <p
                                        class="mt-2 line-clamp-2 text-base font-semibold leading-7 text-[#1f1d18] [&_strong]:font-bold"
                                        v-html="renderInlineFormattedHtml(question.prompt)"
                                    />
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-[#6a645a]">Błędy</p>
                                        <p class="mt-1 text-2xl font-bold text-[#1f1d18]">
                                            {{ question.first_try_error_pct }}%
                                        </p>
                                    </div>
                                    <Link
                                        :href="question.practice_url"
                                        class="inline-flex items-center justify-center rounded-full bg-[#ae0000] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#960000]"
                                    >
                                        Przećwicz
                                    </Link>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-[#efe8dc]">
                                    <div
                                        class="h-full rounded-full bg-[#ae0000]"
                                        :style="{
                                            width: `${Math.min(100, (question.first_try_error_pct / topTenMaxErrorPct) * 100)}%`,
                                        }"
                                    />
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-[#1f1d18]">
                                    <span class="rounded-full border border-[#ddd5c8] bg-white/70 px-3 py-1">
                                        Odp.: {{ question.answers_count }}
                                    </span>
                                    <span class="rounded-full border border-[#ddd5c8] bg-white/70 px-3 py-1">
                                        Kursanci: {{ question.users_count }}
                                    </span>
                                    <span class="rounded-full border border-[#ddd5c8] bg-white/70 px-3 py-1">
                                        {{ scoreLabel(question.difficulty_score) }}
                                    </span>
                                </div>
                            </div>
                        </li>
                    </ol>
                </div>
            </section>

            <section v-if="featured_question" class="mt-10 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="overflow-hidden rounded-[1.8rem] border border-[#e1d9cd] bg-white shadow-[0_18px_48px_rgba(46,39,26,0.05)]">
                    <img
                        v-if="featured_question.media && ((featured_question.media.kind === 'image' && featured_question.media.url) || (featured_question.media.kind === 'video' && (featured_question.media.poster_url || featured_question.media.url)))"
                        :src="featured_question.media.kind === 'video' ? (featured_question.media.poster_url ?? featured_question.media.url ?? '') : (featured_question.media.url ?? '')"
                        alt=""
                        class="h-full min-h-[19rem] w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex min-h-[19rem] items-center justify-center bg-[#f6f2ea] px-8 text-center text-sm font-semibold text-[#7c7669]"
                    >
                        To pytanie aktualnie nie ma publicznego podgladu medium.
                    </div>
                </div>

                <div class="rounded-[1.8rem] border border-[#e1d9cd] bg-white p-6 shadow-[0_18px_48px_rgba(46,39,26,0.05)] sm:p-7">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                        Najmocniejszy sygnal teraz
                    </p>
                    <h2
                        class="mt-3 text-3xl font-bold leading-tight text-[#1f1d18] [&_strong]:font-bold"
                        v-html="renderInlineFormattedHtml(featured_question.prompt)"
                    />

                    <div class="mt-5 flex flex-wrap gap-2 text-sm font-semibold">
                        <span class="rounded-full border border-[#ddd5c8] bg-[#faf7f1] px-3 py-1.5 text-[#1f1d18]">
                            {{ featured_question.category.code }} · {{ featured_question.topic.name }}
                        </span>
                        <span class="rounded-full border border-[#ddd5c8] bg-[#faf7f1] px-3 py-1.5 text-[#1f1d18]">
                            {{ scoreLabel(featured_question.difficulty_score) }}
                        </span>
                        <span class="rounded-full border border-[#ddd5c8] bg-[#faf7f1] px-3 py-1.5 text-[#1f1d18]">
                            {{ confidenceLabel(featured_question.confidence_score) }}
                        </span>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.2rem] border border-[#e5ddcf] bg-[#fcfaf6] p-4">
                            <p class="text-sm text-[#6d6659]">Poziom trudnosci</p>
                            <p class="mt-2 text-3xl font-bold text-[#1f1d18]">
                                {{ featured_question.difficulty_score }}
                            </p>
                        </div>
                        <div class="rounded-[1.2rem] border border-[#e5ddcf] bg-[#fcfaf6] p-4">
                            <p class="text-sm text-[#6d6659]">Bledy przy pierwszej probie</p>
                            <p class="mt-2 text-3xl font-bold text-[#1f1d18]">
                                {{ featured_question.first_try_error_pct }}%
                            </p>
                        </div>
                        <div class="rounded-[1.2rem] border border-[#e5ddcf] bg-[#fcfaf6] p-4">
                            <p class="text-sm text-[#6d6659]">Powracajace bledy</p>
                            <p class="mt-2 text-3xl font-bold text-[#1f1d18]">
                                {{ featured_question.repeat_fail_pct }}%
                            </p>
                        </div>
                        <div class="rounded-[1.2rem] border border-[#e5ddcf] bg-[#fcfaf6] p-4">
                            <p class="text-sm text-[#6d6659]">Medianowy czas odpowiedzi</p>
                            <p class="mt-2 text-3xl font-bold text-[#1f1d18]">
                                {{ formatSeconds(featured_question.median_response_time_ms) }}
                            </p>
                        </div>
                    </div>

                    <p class="mt-6 text-base leading-7 text-[#5f594e]">
                        To pytanie jest wysoko, bo łączy realne pomylki kursantow, powracajace bledy i wolniejsze dojscie do utrwalenia.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="featured_question.practice_url"
                            class="rounded-full bg-[#5d8a34] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#4b7227]"
                        >
                            Cwicz pytania
                        </Link>
                        <Link
                            href="/nauka"
                            class="rounded-full border border-[#d8d0c2] bg-white px-5 py-3 text-sm font-semibold text-[#1f1d18]"
                        >
                            Przejdz do nauki
                        </Link>
                    </div>
                </div>
            </section>

            <section class="mt-10 grid gap-8 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                                Ranking
                            </p>
                            <h2 class="mt-2 text-3xl font-bold text-[#1f1d18]">
                                {{ ranking.label }}
                            </h2>
                        </div>
                        <p class="max-w-md text-sm leading-6 text-[#6b655a]">
                            {{ ranking.description }}
                        </p>
                    </div>

                    <article
                        v-for="(question, index) in top_questions"
                        :key="question.question_id"
                        class="rounded-[1.55rem] border border-[#e2dacd] bg-white p-5 shadow-[0_16px_36px_rgba(46,39,26,0.04)]"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex min-w-0 gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#f4efe3] text-base font-bold text-[#1f1d18]">
                                    {{ index + 1 }}
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-[#7b7468]">
                                        <span>{{ question.category.code }}</span>
                                        <span>·</span>
                                        <span>{{ question.topic.name }}</span>
                                        <span v-if="question.external_id">·</span>
                                        <span v-if="question.external_id">{{ question.external_id }}</span>
                                    </div>
                                    <h3
                                        class="mt-3 text-xl font-semibold leading-8 text-[#1f1d18] [&_strong]:font-bold"
                                        v-html="renderInlineFormattedHtml(question.prompt)"
                                    />
                                </div>
                            </div>

                            <div class="min-w-[8rem] rounded-[1rem] border border-[#d9d1c5] bg-[#faf7f1] px-4 py-3 text-right">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                                    Trudnosc
                                </p>
                                <p class="mt-2 text-2xl font-bold text-[#1f1d18]">
                                    {{ question.difficulty_score }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-[1rem] border border-[#e6dece] bg-[#fcfaf6] px-4 py-3">
                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#7a7366]">
                                    Pierwsza pomylka
                                </p>
                                <p class="mt-2 text-xl font-bold text-[#1f1d18]">
                                    {{ question.first_try_error_pct }}%
                                </p>
                            </div>
                            <div class="rounded-[1rem] border border-[#e6dece] bg-[#fcfaf6] px-4 py-3">
                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#7a7366]">
                                    Powracajace bledy
                                </p>
                                <p class="mt-2 text-xl font-bold text-[#1f1d18]">
                                    {{ question.repeat_fail_pct }}%
                                </p>
                            </div>
                            <div class="rounded-[1rem] border border-[#e6dece] bg-[#fcfaf6] px-4 py-3">
                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#7a7366]">
                                    Czas odpowiedzi
                                </p>
                                <p class="mt-2 text-xl font-bold text-[#1f1d18]">
                                    {{ formatSeconds(question.median_response_time_ms) }}
                                </p>
                            </div>
                            <div class="rounded-[1rem] border border-[#e6dece] bg-[#fcfaf6] px-4 py-3">
                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-[#7a7366]">
                                    Proba
                                </p>
                                <p class="mt-2 text-xl font-bold text-[#1f1d18]">
                                    {{ question.users_count }} os.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-[#6c6559]">
                            <span class="rounded-full border border-[#ddd5c9] bg-white px-3 py-1.5 font-semibold text-[#1f1d18]">
                                {{ confidenceLabel(question.confidence_score) }}
                            </span>
                            <span class="rounded-full border border-[#ddd5c9] bg-white px-3 py-1.5 font-semibold text-[#1f1d18]">
                                Utrwalone: {{ question.mastered_rate_pct }}%
                            </span>
                            <span class="rounded-full border border-[#ddd5c9] bg-white px-3 py-1.5 font-semibold text-[#1f1d18]">
                                Srednio {{ question.avg_attempts }} prob
                            </span>
                        </div>
                    </article>

                    <div
                        v-if="!top_questions.length"
                        class="rounded-[1.55rem] border border-dashed border-[#d8d0c2] bg-white px-5 py-8 text-sm leading-7 text-[#6c665a]"
                    >
                        Dla tego widoku nie mamy jeszcze wystarczajacej aktywnosci. Gdy kursanci rozwiaza wiecej pytan, ranking zacznie sie tu wypelniac.
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="rounded-[1.55rem] border border-[#e2dacd] bg-white p-5 shadow-[0_16px_36px_rgba(46,39,26,0.04)]">
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                            Najtrudniejsze dzialy
                        </p>
                        <div class="mt-5 space-y-3">
                            <div
                                v-for="topic in top_topics"
                                :key="topic.key"
                                class="rounded-[1rem] border border-[#e5ddcf] bg-[#fcfaf6] px-4 py-4"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-[#1f1d18]">
                                            {{ topic.name }}
                                        </p>
                                        <p class="mt-1 text-sm text-[#70695d]">
                                            {{ topic.questions_count }} pytan · {{ topic.answers_count }} odpowiedzi
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                                            Trudnosc
                                        </p>
                                        <p class="mt-2 text-2xl font-bold text-[#1f1d18]">
                                            {{ topic.avg_difficulty_score }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[1.55rem] border border-[#e2dacd] bg-white p-5 shadow-[0_16px_36px_rgba(46,39,26,0.04)]">
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                            Jak liczymy trudnosc
                        </p>
                        <div class="mt-5 space-y-3">
                            <div
                                v-for="item in methodology"
                                :key="item.title"
                                class="rounded-[1rem] border border-[#e5ddcf] bg-[#fcfaf6] px-4 py-4"
                            >
                                <p class="font-semibold text-[#1f1d18]">
                                    {{ item.title }}
                                </p>
                                <p class="mt-2 text-sm leading-6 text-[#686155]">
                                    {{ item.description }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <section class="mt-10 rounded-[1.8rem] border border-[#e2dacd] bg-white p-6 shadow-[0_16px_36px_rgba(46,39,26,0.04)] sm:p-7">
                <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#7a7366]">
                            FAQ
                        </p>
                        <h2 class="mt-3 text-3xl font-bold text-[#1f1d18]">
                            O co najczesciej pytaja kursanci
                        </h2>
                    </div>

                    <div class="space-y-3">
                        <article
                            v-for="item in faqItems"
                            :key="item.question"
                            class="rounded-[1rem] border border-[#e5ddcf] bg-[#fcfaf6] px-4 py-4"
                        >
                            <h3 class="font-semibold text-[#1f1d18]">
                                {{ item.question }}
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-[#686155]">
                                {{ item.answer }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
