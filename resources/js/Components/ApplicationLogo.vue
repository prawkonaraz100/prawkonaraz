<script setup lang="ts">
withDefaults(
    defineProps<{
        name?: string;
        tagline?: string;
        variant?: 'default' | 'header';
    }>(),
    {
        name: 'prawkonaraz.pl',
        tagline: 'Pytania na Prawo Jazdy',
        variant: 'default',
    },
);
</script>

<template>
    <span :class="variant === 'header' ? 'site-header-logo' : 'site-brand'">
        <img
            src="/images/site-header-logo.png"
            :alt="`${name} - ${tagline}`"
            :class="variant === 'header' ? 'site-header-logo__image' : 'site-brand__image'"
            width="457"
            height="158"
        >
    </span>
</template>
