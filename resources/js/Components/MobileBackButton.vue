<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

// Shared phone back control (chess.com-style secondary button, see .pk-btn-secondary in app.css).
// Pass `href` to navigate, or listen to `click` for in-page state changes.
const props = defineProps<{ href?: string; label: string }>();
const emit = defineEmits<{ click: [] }>();

const onClick = () => {
    if (!props.href) emit('click');
};
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        :type="href ? undefined : 'button'"
        :aria-label="label"
        :title="label"
        class="pk-btn-secondary grid h-11 w-11 shrink-0 place-items-center md:hidden"
        @click="onClick"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="m14.5 5-7 7 7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </component>
</template>
