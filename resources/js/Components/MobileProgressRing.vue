<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    percent: number;
    progressAriaLabel: string;
    eyebrow: string;
    progressLabel: string;
    projectionLabel: string;
    remainingLabel: string;
}>();

const ringOffset = computed(() => `${Math.max(100 - Math.min(Math.max(props.percent, 0), 100), 0).toFixed(2)}`);
</script>

<template>
    <div
        class="mobile-progress-ring relative grid aspect-square w-[min(76vw,18.25rem)] place-items-center rounded-full shadow-[0_28px_70px_rgba(13,71,161,0.16)]"
        role="img"
        :aria-label="progressAriaLabel"
    >
        <svg
            class="pointer-events-none absolute inset-0 h-full w-full -rotate-90 overflow-visible"
            viewBox="0 0 100 100"
            aria-hidden="true"
            focusable="false"
            role="presentation"
        >
            <circle
                cx="50"
                cy="50"
                r="45"
                fill="none"
                stroke="#d9e5f5"
                stroke-width="7"
            />
            <circle
                class="mobile-progress-ring-progress"
                cx="50"
                cy="50"
                r="45"
                fill="none"
                pathLength="100"
                stroke="#0d47a1"
                stroke-linecap="round"
                stroke-width="7"
                :style="{ '--ring-offset': ringOffset }"
            />
        </svg>
        <div class="relative z-10 grid h-[calc(100%-3.2rem)] w-[calc(100%-3.2rem)] place-items-center rounded-full bg-white text-center shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
            <div>
                <p class="mobile-progress-ring-stat text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-[#0d47a1]">
                    {{ eyebrow }}
                </p>
                <p class="mobile-progress-ring-stat mt-3 text-[4.8rem] font-semibold leading-none tracking-tight text-slate-950">
                    {{ percent }}%
                </p>
                <p class="mobile-progress-ring-stat mt-2 text-[0.8rem] font-semibold uppercase tracking-[0.14em] text-slate-500">
                    {{ progressLabel }}
                </p>
                <p class="mobile-progress-ring-stat mt-4 text-sm font-semibold text-slate-950">
                    {{ projectionLabel }}
                </p>
                <p class="mobile-progress-ring-stat mt-1 text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-slate-500">
                    {{ remainingLabel }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mobile-progress-ring {
    animation: mobile-progress-ring-enter 420ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

.mobile-progress-ring-progress {
    animation: mobile-progress-ring-fill 900ms cubic-bezier(0.2, 0.8, 0.2, 1) 120ms both;
    stroke-dasharray: 100;
    stroke-dashoffset: var(--ring-offset);
}

.mobile-progress-ring-stat {
    animation: mobile-progress-ring-stat-rise 360ms ease-out both;
}

.mobile-progress-ring-stat:nth-child(1) {
    animation-delay: 180ms;
}

.mobile-progress-ring-stat:nth-child(2) {
    animation-delay: 240ms;
}

.mobile-progress-ring-stat:nth-child(3) {
    animation-delay: 300ms;
}

.mobile-progress-ring-stat:nth-child(4) {
    animation-delay: 360ms;
}

.mobile-progress-ring-stat:nth-child(5) {
    animation-delay: 420ms;
}

@keyframes mobile-progress-ring-enter {
    from {
        opacity: 0;
        transform: scale(0.96);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes mobile-progress-ring-fill {
    from {
        stroke-dashoffset: 100;
    }

    to {
        stroke-dashoffset: var(--ring-offset);
    }
}

@keyframes mobile-progress-ring-stat-rise {
    from {
        opacity: 0;
        transform: translateY(5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .mobile-progress-ring,
    .mobile-progress-ring-progress,
    .mobile-progress-ring-stat {
        animation: none !important;
        transition: none !important;
    }
}
</style>
