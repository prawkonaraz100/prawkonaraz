<script setup lang="ts">
import AuthPricingBackdrop from '@/Components/Auth/AuthPricingBackdrop.vue';
import LoginDrawer from '@/Components/Auth/LoginDrawer.vue';
import RegisterDrawer from '@/Components/Auth/RegisterDrawer.vue';
import type { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const page = usePage<PageProps>();
const activePanel = ref<'login' | 'register'>('login');
const visualHostPanel = ref<'login' | 'register'>('login');
const registrationCategories = computed(
    () => page.props.authDrawers.registrationCategories,
);

const closePanel = () => {
    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    router.visit('/');
};

const openLoginPanel = () => {
    activePanel.value = 'login';
};

const openRegisterPanel = () => {
    activePanel.value = 'register';
};
</script>

<template>
    <Head title="Logowanie" />

    <div class="relative min-h-screen overflow-hidden bg-white text-[#111827]">
        <div inert aria-hidden="true">
            <AuthPricingBackdrop />
        </div>

        <LoginDrawer
            :open="activePanel === 'login'"
            :retain-visual="visualHostPanel === 'login' && activePanel === 'register'"
            :hide-visual="visualHostPanel === 'register' && activePanel === 'login'"
            :can-reset-password="canResetPassword"
            :status="status"
            @close="closePanel"
            @open-register="openRegisterPanel"
        />

        <RegisterDrawer
            :open="activePanel === 'register'"
            :retain-visual="visualHostPanel === 'register' && activePanel === 'login'"
            :hide-visual="visualHostPanel === 'login' && activePanel === 'register'"
            :categories="registrationCategories"
            @close="closePanel"
            @open-login="openLoginPanel"
        />
    </div>
</template>
