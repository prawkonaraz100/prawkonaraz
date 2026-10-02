<script setup lang="ts">
import type { LearningProgressMessage } from '@/types/learningProgress';
import { ArrowRight, BookOpen, CircleCheck, ClipboardCheck, Info, Trophy } from '@lucide/vue';

defineProps<{ icon: LearningProgressMessage['icon'] }>();

const icons = {
    book: BookOpen,
    arrow: ArrowRight,
    clipboard: ClipboardCheck,
    trophy: Trophy,
    check: CircleCheck,
    info: Info,
};
</script>

<template>
    <component
        :is="icons[icon]"
        :size="22"
        class="shrink-0"
        :class="icon === 'trophy' ? 'text-[#f3ad2c]' : icon === 'check' ? 'text-[#00a86b]' : 'text-[#1769e8]'"
        aria-hidden="true"
    />
</template>
