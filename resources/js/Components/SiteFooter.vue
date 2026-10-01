<script setup lang="ts">
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ shellWidthClass?: string }>(), {
    shellWidthClass: '',
});

const page = usePage<PageProps>();
const footer = computed(() => page.props.footer);
</script>

<template>
    <footer class="site-footer">
        <div class="site-footer__shell" :class="props.shellWidthClass">
            <div class="site-footer__main">
                <section class="site-footer__brand" aria-labelledby="site-footer-vue-brand">
                    <a
                        id="site-footer-vue-brand"
                        :href="footer.home_href"
                        class="site-footer__brand-name"
                        :aria-label="footer.brand.aria_label"
                    >
                        PrawkoNaRaz.pl
                    </a>
                    <p class="site-footer__description">{{ footer.description }}</p>
                    <p class="site-footer__email">
                        Adres e-mail:
                        <a :href="`mailto:${footer.email}`">{{ footer.email }}</a>
                    </p>
                </section>

                <nav
                    v-for="(group, index) in footer.groups"
                    :key="group.title"
                    class="site-footer__group"
                    :aria-labelledby="`site-footer-vue-group-${index}`"
                >
                    <h2 :id="`site-footer-vue-group-${index}`">{{ group.title }}</h2>
                    <ul>
                        <li v-for="link in group.links" :key="`${group.title}-${link.href}`">
                            <a :href="link.href">{{ link.label }}</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="site-footer__legal-row">
            <div class="site-footer__shell site-footer__bottom-bar" :class="props.shellWidthClass">
                <span>{{ footer.copyright }}</span>
                <nav aria-label="Dokumenty prawne">
                    <a v-for="link in footer.legal_links" :key="link.href" :href="link.href">{{ link.label }}</a>
                </nav>
            </div>
        </div>
    </footer>
</template>
