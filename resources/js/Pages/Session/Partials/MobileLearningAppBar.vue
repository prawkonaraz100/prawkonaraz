<script setup lang="ts">
import type { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SessionCategory {
    id: number;
    code: string;
    name: string;
    short_name: string;
    questions_count: number;
}

const props = defineProps<{
    category: SessionCategory | null;
}>();

const page = usePage<PageProps>();

const user = computed(() => page.props.auth.user);
const displayName = computed(() => user.value?.name?.trim() || 'Kursant');
const firstName = computed(() => displayName.value.split(/\s+/).filter(Boolean)[0] ?? 'Kursant');
const avatarUrl = computed(() => user.value?.avatar_url ?? null);
const initials = computed(() => {
    if (user.value?.avatar_initials) {
        return user.value.avatar_initials;
    }

    const derived = displayName.value
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toLocaleUpperCase('pl-PL'))
        .join('');

    return derived || 'K';
});

const categoryCode = computed(() => (
    props.category?.short_name || props.category?.code || 'Kat.'
).toLocaleUpperCase('pl-PL'));
const categoryName = computed(() => props.category?.name ?? 'Kategoria prawa jazdy');
const normalizedCategoryCode = computed(() => categoryCode.value.replace(/\s+/g, ''));
const vehicleKind = computed<'moped' | 'motorcycle' | 'car' | 'truck' | 'bus' | 'tractor'>(() => {
    const code = normalizedCategoryCode.value;

    if (code === 'AM') {
        return 'moped';
    }

    if (code.startsWith('A')) {
        return 'motorcycle';
    }

    if (code.startsWith('C')) {
        return 'truck';
    }

    if (code.startsWith('D')) {
        return 'bus';
    }

    if (code.startsWith('T')) {
        return 'tractor';
    }

    return 'car';
});
</script>

<template>
    <div class="mobile-learning-app-bar flex items-center justify-between gap-3 bg-white px-4 pb-2.5 pt-3">
        <div
            class="inline-flex min-w-0 items-center rounded-[0.9rem] bg-[#f1f2f4] p-1 shadow-[0_8px_22px_rgba(15,23,42,0.05)]"
            :aria-label="`Wybrana kategoria: ${categoryName}`"
        >
            <div class="inline-flex h-11 items-center gap-2 rounded-[0.72rem] bg-white px-3 text-[#101827] shadow-[0_5px_14px_rgba(15,23,42,0.08)]">
                <span class="text-[1.05rem] font-semibold tracking-[0]">
                    {{ categoryCode }}
                </span>
                <svg
                    aria-hidden="true"
                    viewBox="0 0 36 24"
                    class="h-6 w-9 text-[#1f2937]"
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.7"
                >
                    <g v-if="vehicleKind === 'motorcycle'">
                        <path d="M9 17.5h8.5l3.8-7H17l-3.1 4.3" />
                        <path d="M20.8 10.5h3.7l2.8 7" />
                        <circle cx="8" cy="17.5" r="3.4" />
                        <circle cx="28" cy="17.5" r="3.4" />
                        <path d="M15.8 7.2h4.6" />
                    </g>
                    <g v-else-if="vehicleKind === 'moped'">
                        <path d="M9.2 17.5h7.5l2.9-6.2h-4.1l-2.6 3.8" />
                        <path d="M20 11.3h3.3l3.2 6.2" />
                        <circle cx="8.2" cy="17.5" r="3.1" />
                        <circle cx="27.2" cy="17.5" r="3.1" />
                        <path d="M14.2 8.5h3.7" />
                    </g>
                    <g v-else-if="vehicleKind === 'truck'">
                        <path d="M4.8 9.2h16.4v8.4H4.8z" />
                        <path d="M21.2 12h5.5l4.2 5.6h-9.7z" />
                        <circle cx="10" cy="18.2" r="2.5" />
                        <circle cx="26.5" cy="18.2" r="2.5" />
                    </g>
                    <g v-else-if="vehicleKind === 'bus'">
                        <rect x="5" y="6.5" width="25.5" height="11.2" rx="2.4" />
                        <path d="M9.2 10.2h4.7M17 10.2h4.7M24.8 10.2h2.4" />
                        <circle cx="11" cy="18.2" r="2.3" />
                        <circle cx="25.2" cy="18.2" r="2.3" />
                    </g>
                    <g v-else-if="vehicleKind === 'tractor'">
                        <path d="M13 15.8h8.4V9.5h-5.3l-3.1 6.3Z" />
                        <path d="M21.4 15.8h4.8" />
                        <circle cx="10" cy="17" r="4.2" />
                        <circle cx="26.5" cy="17" r="2.8" />
                        <path d="M17.2 6.2h4.6" />
                    </g>
                    <g v-else>
                        <path d="M5.2 15.6h2.9l2.9-5.4h12.4l4 5.4h3.1" />
                        <path d="M11 10.2l-2 5.4h18.4" />
                        <circle cx="11" cy="17.1" r="2.6" />
                        <circle cx="25.2" cy="17.1" r="2.6" />
                    </g>
                </svg>
            </div>

            <button
                type="button"
                class="grid h-11 w-11 place-items-center rounded-[0.72rem] text-[#6b7280]"
                aria-label="Ustawienia nauki"
                aria-disabled="true"
                title="Ustawienia nauki"
            >
                <svg
                    aria-hidden="true"
                    viewBox="0 0 24 24"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="1.9"
                >
                    <path d="M5 8h8" />
                    <path d="M17 8h2" />
                    <path d="M5 16h2" />
                    <path d="M11 16h8" />
                    <circle cx="15" cy="8" r="2" />
                    <circle cx="9" cy="16" r="2" />
                </svg>
            </button>
        </div>

        <Link
            href="/profile"
            class="inline-flex min-w-0 items-center gap-2 rounded-[0.9rem] py-1 pl-2 pr-0.5 text-[#0f172a] transition active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
            aria-label="Przejdź do profilu"
        >
            <span class="max-w-[8.5rem] truncate text-[1.05rem] font-semibold tracking-[0]">
                {{ firstName }}
            </span>
            <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-[0.78rem] bg-[#e7edf7] text-sm font-semibold text-[#0f172a] shadow-[0_8px_20px_rgba(15,23,42,0.12)]">
                <img
                    v-if="avatarUrl"
                    :src="avatarUrl"
                    alt=""
                    class="h-full w-full object-cover"
                    referrerpolicy="no-referrer"
                >
                <span v-else>{{ initials }}</span>
            </span>
        </Link>
    </div>
</template>

<style scoped>
.mobile-learning-app-bar {
    animation: mobile-learning-app-bar-enter 220ms ease-out both;
}

@keyframes mobile-learning-app-bar-enter {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .mobile-learning-app-bar {
        animation: none !important;
    }
}
</style>
