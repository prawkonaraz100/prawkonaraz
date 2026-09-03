<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import SiteFooter from "@/Components/SiteFooter.vue";
import SiteHeader from "@/Components/SiteHeader.vue";
import type { PageProps } from "@/types";
import { Head, Link, usePage } from "@inertiajs/vue3";
import heroDesktopImg from "../../images/home/hero-desktop.png";
import heroMobileImg from "../../images/home/hero-mobile.png";

const page = usePage<PageProps>();
const currentQuestionBankUpdatedAt = new Intl.DateTimeFormat("pl-PL", {
    day: "numeric",
    month: "long",
    year: "numeric",
    timeZone: "Europe/Warsaw",
}).format(new Date());

const examHighlights = [
    "Szybkie czytanie – Strategiczne pogrubienia w tekście pozwalają na błyskawiczne czytanie i łatwiejsze zrozumienie sensu pytań.",
    "Psychologia kolorów – System kolorystyczny naprowadza Cię na właściwy tor i pomaga szybko zapamiętać nawet najbardziej podchwytliwe pytania.",
    "Wizualne adnotacje – Bezpośrednio na zdjęciach i wideo rysujemy strzałki, od razu kierując Twój wzrok na to, co najważniejsze, by nie marnować czasu.",
    "Projektowanie pod skupienie – Interfejs i Tryb Zen stworzone od zera tak, aby odcinać rozpraszacze i utrzymywać Cię cały czas w stanie maksymalnej koncentracji.",
    "Intuicyjny system wyjaśnień – Gdy popełnisz błąd, rozwiązanie pojawia się od razu w zasięgu wzroku, dzięki czemu nie wybijasz się z rytmu zapamiętywania.",
    `Gwarancja bazy WORD – Uczysz się na oficjalnych pytaniach państwowych (stan na: ${currentQuestionBankUpdatedAt}).`,
];



interface ExpandedMediaItem {
    title: string;
    image?: string;
}

const expandedFeatureMedia = ref<ExpandedMediaItem | null>(null);

const openExpandedMedia = (media: ExpandedMediaItem) => {
    if (!media.image) {
        return;
    }

    expandedFeatureMedia.value = media;
    document.body.style.overflow = "hidden";
};

const closeExpandedMedia = () => {
    expandedFeatureMedia.value = null;
    document.body.style.overflow = "";
};

const closeExpandedMediaOnEscape = (event: KeyboardEvent) => {
    if (event.key === "Escape" && expandedFeatureMedia.value) {
        closeExpandedMedia();
    }
};

onMounted(() => document.addEventListener("keydown", closeExpandedMediaOnEscape));

onUnmounted(() => {
    document.removeEventListener("keydown", closeExpandedMediaOnEscape);
    document.body.style.overflow = "";
});

</script>

<template>
    <Head :title="page.props.app.name" />

    <div class="flex min-h-screen flex-col bg-white text-[#1f1d18]">
        <SiteHeader />

        <main class="flex-1 bg-white">
            <section
                class="mx-auto max-w-[81.75rem] px-4 pb-20 pt-14 sm:px-6 lg:pb-24 lg:pt-16 xl:px-0"
            >
                <div
                    class="grid items-center gap-12 xl:grid-cols-[minmax(0,0.96fr)_minmax(340px,1.04fr)] xl:gap-8"
                >
                    <div class="max-w-[44rem]">
                        <p class="mb-3 text-[0.8rem] font-bold uppercase tracking-[0.15em] text-[#023ea4]">
                            Oficjalny Program Orły na Drodze
                        </p>
                        <h1
                            class="max-w-[12ch] text-[2.45rem] font-semibold leading-[0.94] tracking-[-0.05em] text-[#2a2521] sm:max-w-none sm:text-[3.45rem] lg:text-[4.35rem]"
                        >
                            Testy na prawo jazdy 2026
                        </h1>

                        <p
                            class="mt-6 max-w-[29rem] text-[1.08rem] font-medium leading-[1.45] tracking-[-0.01em] text-[#2f2a26] sm:text-[1.15rem] lg:max-w-[36rem] lg:text-[1.25rem]"
                        >
                            Poznaj innowacyjny system nauki. Ucz się mądrzej dzięki narzędziom PRO:
                        </p>

                        <ul
                            class="mt-7 space-y-3.5 text-[0.97rem] leading-6 text-[#302c27] sm:text-[1.06rem] sm:leading-7"
                        >
                            <li
                                v-for="highlight in examHighlights"
                                :key="highlight"
                                class="flex items-start gap-4"
                            >
                                <span
                                    class="mt-[0.55rem] h-2 w-2 shrink-0 rounded-full bg-[#023ea4]"
                                    aria-hidden="true"
                                />
                                <span>{{ highlight }}</span>
                            </li>
                        </ul>

                        <div class="mt-10">
                            <Link
                                href="/testy-na-prawo-jazdy"
                                class="inline-flex items-center justify-center rounded-[24px] bg-[#023ea4] px-7 py-3.5 text-[1.05rem] font-semibold text-white shadow-[0_8px_20px_rgba(2,62,164,0.25)] transition hover:-translate-y-0.5 hover:bg-[#012b7a] hover:shadow-[0_12px_24px_rgba(2,62,164,0.3)]"
                            >
                                Rozpocznij naukę z programem
                            </Link>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-[40rem] pt-10 xl:mx-0 xl:pt-0 xl:justify-self-end">
                        <div class="relative w-full pt-[62%]">
                            <!-- Laptop Mockup -->
                            <div
                                class="absolute inset-x-0 top-0 mx-auto w-[85%] cursor-zoom-in rounded-t-xl border-[4px] border-[#1a1a1a] bg-[#1a1a1a] shadow-[0_20px_50px_rgba(15,23,42,0.15)] sm:w-[90%] sm:rounded-t-2xl sm:border-[6px]"
                                role="button"
                                tabindex="0"
                                aria-label="Otworz powiekszony podglad: Widok aplikacji na laptopie"
                                @click="openExpandedMedia({ title: 'Widok aplikacji na laptopie', image: heroDesktopImg })"
                                @keydown.enter.prevent="openExpandedMedia({ title: 'Widok aplikacji na laptopie', image: heroDesktopImg })"
                                @keydown.space.prevent="openExpandedMedia({ title: 'Widok aplikacji na laptopie', image: heroDesktopImg })"
                            >
                                <div class="relative w-full overflow-hidden rounded-t-md sm:rounded-t-xl bg-white aspect-[16/10]">
                                    <img :src="heroDesktopImg" alt="Widok aplikacji na laptopie" class="absolute inset-0 h-full w-full object-cover object-top" />
                                </div>
                                <div class="absolute -bottom-3 sm:-bottom-4 left-1/2 w-[112%] -translate-x-1/2 h-3 sm:h-4 rounded-b-xl sm:rounded-b-2xl bg-[#2a2a2a] shadow-md flex justify-center">
                                    <div class="w-16 h-1 bg-[#1a1a1a] rounded-b-md"></div>
                                </div>
                            </div>
                            
                            <!-- Mobile Mockup -->
                            <div
                                class="absolute -bottom-6 -left-2 w-[30%] min-w-[120px] cursor-zoom-in overflow-hidden rounded-[1.5rem] border-[4px] border-[#1a1a1a] bg-[#1a1a1a] shadow-[0_25px_60px_rgba(15,23,42,0.25)] aspect-[9/19] sm:-bottom-10 sm:-left-6 sm:rounded-[2rem] sm:border-[6px]"
                                role="button"
                                tabindex="0"
                                aria-label="Otworz powiekszony podglad: Widok aplikacji na telefonie"
                                @click="openExpandedMedia({ title: 'Widok aplikacji na telefonie', image: heroMobileImg })"
                                @keydown.enter.prevent="openExpandedMedia({ title: 'Widok aplikacji na telefonie', image: heroMobileImg })"
                                @keydown.space.prevent="openExpandedMedia({ title: 'Widok aplikacji na telefonie', image: heroMobileImg })"
                            >
                                <div class="relative h-full w-full bg-[#fdfdfc]">
                                    <div class="absolute top-0 inset-x-0 flex justify-center z-10">
                                        <div class="w-[40%] h-3 sm:h-4 bg-[#1a1a1a] rounded-b-xl"></div>
                                    </div>
                                    <img :src="heroMobileImg" alt="Widok aplikacji na telefonie" class="absolute inset-0 h-full w-full object-cover object-left" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>

        <div
            v-if="expandedFeatureMedia?.image"
            class="fixed inset-0 z-[90] p-3 sm:p-6"
        >
            <div
                class="absolute inset-0 bg-[#04070d]/94"
                aria-hidden="true"
                @click="closeExpandedMedia"
            />

            <button
                type="button"
                class="absolute right-4 top-4 z-20 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/92 text-[#0f172a] shadow-[0_14px_40px_rgba(15,23,42,0.3)] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                aria-label="Zamknij podglad"
                title="Zamknij"
                @click.stop="closeExpandedMedia"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>

            <div
                class="relative z-10 flex h-full w-full items-center justify-center"
                @click="closeExpandedMedia"
            >
                <div class="w-full max-w-[72rem]" @click.stop>
                    <img
                        :src="expandedFeatureMedia.image"
                        :alt="`${expandedFeatureMedia.title} - powiekszony podglad`"
                        class="mx-auto block max-h-[calc(100vh-1.5rem)] max-w-full rounded-[1.5rem] bg-white shadow-[0_30px_80px_rgba(0,0,0,0.45)] sm:max-h-[calc(100vh-3rem)]"
                    />
                </div>
            </div>
        </div>

        <SiteFooter />
    </div>
</template>
