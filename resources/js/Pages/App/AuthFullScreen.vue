<script setup lang="ts">
import Login from '@/Pages/Auth/Login.vue';
import Register from '@/Pages/Auth/Register.vue';
import type { StudyContextCategory } from '@/types';
import { useMobileDarkScope } from '@/composables/useMobileDarkScope';

useMobileDarkScope();

withDefaults(defineProps<{
    mode: 'login' | 'register';
    canResetPassword?: boolean;
    status?: string | null;
    categories?: StudyContextCategory[];
}>(), {
    canResetPassword: true,
    status: null,
    categories: () => [],
});
</script>

<template>
    <!-- Keep existing app URLs, but share the website's light auth interface. -->
    <Login
        v-if="mode === 'login'"
        :can-reset-password="canResetPassword"
        :status="status ?? undefined"
    />
    <Register v-else :categories="categories" />
</template>
