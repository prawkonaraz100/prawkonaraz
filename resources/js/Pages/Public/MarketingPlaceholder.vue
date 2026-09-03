<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    title: string;
    eyebrow: string;
    description: string;
}>();
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen flex-col bg-white text-[#171717]">
        <SiteHeader />

        <main class="flex-1 bg-[#f7f8fa]">
            <section class="mx-auto max-w-[84rem] px-4 py-14 sm:px-6 lg:px-8">
                <div class="border border-[#e4e8ec] bg-white px-6 py-10 shadow-[0_18px_42px_rgba(15,23,42,0.06)] sm:px-10">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.22em] text-[#717b86]">
                        {{ eyebrow }}
                    </p>

                    <h1 class="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-[#171717] sm:text-5xl">
                        {{ title }}
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-8 text-[#5c6570]">
                        {{ description }}
                    </p>

                    <div class="mt-10 border border-dashed border-[#dbe2e8] bg-[#f7f8fa] px-5 py-8 text-sm leading-7 text-[#66707b]">
                        Ta sekcja jest przygotowana jako pusty punkt startowy pod dalsze wdrożenie treści.
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
