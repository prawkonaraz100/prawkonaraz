<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { RotateCcw } from '@lucide/vue';

defineProps<{
    session: {
        title: string;
        subtitle: string | null;
        resume_url: string;
        progress: { answered: number; total: number; percent: number };
    };
}>();
</script>

<template>
    <section aria-label="Aktywna sesja" class="flex w-full flex-wrap items-center gap-4 rounded-2xl border border-[#dfe7f0] bg-white/95 px-4 py-3 sm:flex-nowrap sm:gap-5">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#eaf2ff] text-[#0d5cc6]" aria-hidden="true"><RotateCcw :size="20" :stroke-width="1.8" /></span>
            <div class="min-w-0">
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.1em] text-[#64799a]">Bieżąca sesja</p>
                <p class="mt-0.5 truncate text-sm font-bold text-[#071b45]">{{ session.title }}</p>
                <p class="truncate text-xs text-[#53698b]">{{ session.subtitle }} · {{ session.progress.answered }}/{{ session.progress.total }} pytań</p>
            </div>
        </div>
        <div class="w-24 shrink-0" role="progressbar" aria-label="Postęp bieżącej sesji" :aria-valuenow="session.progress.percent" aria-valuemin="0" aria-valuemax="100">
            <p class="mb-1 text-right text-xs font-semibold text-[#53698b]">{{ session.progress.percent }}%</p>
            <div class="h-1.5 overflow-hidden rounded-full bg-[#e7edf2]"><span class="block h-full rounded-full bg-[#00cf85]" :style="{ width: `${session.progress.percent}%` }" /></div>
        </div>
        <Link :href="session.resume_url" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-[#071b45] px-4 text-sm font-semibold text-white transition-colors hover:bg-[#12346e] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0876f8] focus-visible:ring-offset-2">Wróć do sesji <span aria-hidden="true">→</span></Link>
    </section>
</template>
