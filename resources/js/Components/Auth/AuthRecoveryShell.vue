<script setup lang="ts">
import AuthBrand from '@/Components/Auth/AuthBrand.vue';
import AuthTrustPanel from '@/Components/Auth/AuthTrustPanel.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';

defineProps<{
    title: string;
    lead: string;
}>();
</script>

<template>
    <main class="auth-recovery-page font-[system-ui,-apple-system,'Segoe_UI',Roboto,Helvetica,Arial,sans-serif]">
        <section class="auth-recovery-shell" aria-labelledby="auth-recovery-title">
            <AuthTrustPanel />

            <section class="auth-recovery-shell__panel">
                <div class="auth-recovery-shell__content">
                    <AuthBrand />
                    <Link
                        :href="route('login')"
                        class="auth-recovery-shell__back"
                    >
                        <ArrowLeft :size="17" :stroke-width="1.9" aria-hidden="true" />
                        <span>Wróć do logowania</span>
                    </Link>

                    <header class="auth-recovery-shell__header">
                        <p class="auth-recovery-shell__eyebrow">
                            <slot name="eyebrow" />
                        </p>
                        <h1 id="auth-recovery-title" class="auth-recovery-shell__title">
                            {{ title }}
                        </h1>
                        <p class="auth-recovery-shell__lead">
                            {{ lead }}
                        </p>
                    </header>

                    <slot />

                    <footer v-if="$slots.footer" class="auth-recovery-shell__footer">
                        <slot name="footer" />
                    </footer>
                </div>
            </section>
        </section>
    </main>
</template>
