<script setup lang="ts">
const props = withDefaults(defineProps<{
    href?: string | null;
    type?: 'button' | 'submit';
    variant?: 'primary' | 'secondary';
    disabled?: boolean;
    loading?: boolean;
}>(), {
    href: null,
    type: 'button',
    variant: 'primary',
    disabled: false,
    loading: false,
});

const baseClass = 'mobile-bottom-action inline-flex h-14 w-full items-center justify-center rounded-none px-6 text-base font-semibold transition duration-200 active:translate-y-px active:scale-[0.985] disabled:cursor-not-allowed disabled:opacity-60';
const variantClass = props.variant === 'primary'
    ? 'bg-[#0d47a1] text-white shadow-[0_18px_36px_rgba(13,71,161,0.22)] active:bg-blue-800'
    : 'border border-slate-300 bg-white text-slate-950';
</script>

<template>
    <a
        v-if="href"
        :href="href"
        :class="[baseClass, variantClass]"
    >
        <slot />
    </a>
    <button
        v-else
        :type="type"
        :class="[baseClass, variantClass]"
        :disabled="disabled || loading"
    >
        <span
            v-if="loading"
            class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
            aria-hidden="true"
        />
        <slot />
    </button>
</template>

<style scoped>
.mobile-bottom-action {
    animation: mobile-bottom-action-rise 360ms ease-out 300ms both;
}

@keyframes mobile-bottom-action-rise {
    from {
        opacity: 0;
        transform: translateY(8px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .mobile-bottom-action {
        animation: none !important;
        transition: none !important;
    }
}
</style>
