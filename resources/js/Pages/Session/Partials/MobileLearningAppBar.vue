<script setup lang="ts">
import type { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const displayName = computed(() => user.value?.name?.trim() || 'Kursant');
const firstName = computed(() => displayName.value.split(/\s+/)[0]);
const avatarUrl = computed(() => user.value?.avatar_url ?? null);
const initials = computed(() => {
    if (user.value?.avatar_initials) {
        return user.value.avatar_initials;
    }

    return displayName.value
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toLocaleUpperCase('pl-PL'))
        .join('') || 'K';
});
</script>

<template>
    <div class="absolute inset-x-0 top-0 z-20 flex items-center justify-end gap-3 px-5 pt-[max(1rem,env(safe-area-inset-top))] text-[#101827]">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white/90 shadow-[0_4px_20px_rgba(20,26,36,0.035)]" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" />
                <path d="M10 20a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" />
            </svg>
        </span>
        <span class="max-w-28 truncate text-[0.91rem] font-bold">{{ firstName }}</span>
        <Link
            href="/profile"
            class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-[#dbe5ee] text-sm font-semibold shadow-[0_4px_16px_rgba(15,23,42,0.08)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#a36c12]"
            aria-label="Przejdź do profilu"
        >
            <img v-if="avatarUrl" :src="avatarUrl" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
            <span v-else>{{ initials }}</span>
        </Link>
    </div>
</template>
