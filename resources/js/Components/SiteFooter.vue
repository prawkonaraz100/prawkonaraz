<script setup lang="ts">
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        shellWidthClass?: string;
    }>(),
    {
        shellWidthClass: '',
    },
);

const page = usePage<PageProps>();
const footer = computed(() => page.props.footer);
</script>

<template>
    <footer class="site-footer">
        <div class="site-footer__shell" :class="props.shellWidthClass">
            <div class="site-footer__desktop-compact">
                <div class="site-footer__row site-footer__row--primary">
                    <a
                        :href="footer.home_href"
                        class="site-footer__compact-brand"
                        :aria-label="footer.brand.aria_label"
                    >
                        <span>Prawko</span><strong>NaRaz</strong>
                    </a>
                    <span class="site-footer__compact-copy">{{ footer.copyright }}</span>
                    <nav class="site-footer__compact-nav" aria-label="Informacje i dokumenty">
                        <a v-for="link in footer.legal_links" :key="link.href" :href="link.href">
                            {{ link.label }}
                        </a>
                    </nav>
                </div>

                <div class="site-footer__row site-footer__row--secondary">
                    <nav class="site-footer__compact-nav" aria-label="Najważniejsze sekcje serwisu">
                        <a v-for="link in footer.service_links" :key="link.href" :href="link.href">
                            {{ link.label }}
                        </a>
                    </nav>
                    <nav class="site-footer__socials" aria-label="Media społecznościowe">
                        <a
                            v-for="link in footer.social_links"
                            :key="link.href"
                            :href="link.href"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ link.label }}
                        </a>
                    </nav>
                    <span class="site-footer__language">
                        {{ footer.language }}
                        <svg viewBox="0 0 12 8" aria-hidden="true"><path d="m1 1.5 5 5 5-5" /></svg>
                    </span>
                </div>
            </div>
        </div>
    </footer>
</template>
