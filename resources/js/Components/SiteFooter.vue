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
                <a :href="footer.home_href" class="site-footer__brand-lockup" :aria-label="footer.brand.aria_label">
                    <img src="/images/site-brand-mark-shield-v2.png" alt="" aria-hidden="true" />
                    <span>prawko<strong>naraz</strong><em>.pl</em></span>
                    <small>{{ footer.brand.tagline }}</small>
                </a>

                <nav v-for="(group, index) in footer.groups" :key="group.title" class="site-footer__group" :aria-labelledby="`site-footer-vue-group-${index}`">
                    <h2 :id="`site-footer-vue-group-${index}`">{{ group.title }}</h2>
                    <ul>
                        <li v-for="link in group.links" :key="link.href"><a :href="link.href">{{ link.label }}</a></li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="site-footer__network-row">
            <div class="site-footer__shell site-footer__network-inner" :class="props.shellWidthClass">
                <div class="site-footer__join">
                    <strong>Dołącz do nas:</strong>
                    <nav class="site-footer__socials" aria-label="Media społecznościowe">
                        <a v-for="link in footer.social_links" :key="link.href" :href="link.href" target="_blank" rel="noopener noreferrer" :aria-label="link.label">
                            <svg v-if="link.icon === 'facebook'" viewBox="0 0 24 24" aria-hidden="true"><path d="M14.4 8.2h3V4.3c-.5-.1-2.3-.2-4.3-.2-4.2 0-7.1 2.6-7.1 7.4v4.1H1.2V20H6v11h5.8V20h4.4l.7-4.4h-5.1v-3.7c0-1.3.4-3.7 2.6-3.7Z" transform="scale(.75)" /></svg>
                            <svg v-else-if="link.icon === 'youtube'" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.5 7.1a2.8 2.8 0 0 0-2-2C18.8 4.6 12 4.6 12 4.6s-6.8 0-8.5.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 1 12a29 29 0 0 0 .5 4.9 2.8 2.8 0 0 0 2 2c1.7.5 8.5.5 8.5.5s6.8 0 8.5-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 23 12a29 29 0 0 0-.5-4.9ZM9.8 15.3V8.7l5.7 3.3-5.7 3.3Z" /></svg>
                            <svg v-else-if="link.icon === 'instagram'" class="is-stroked" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4.2" /><circle cx="17.5" cy="6.7" r="1" class="is-filled" /></svg>
                            <svg v-else class="is-stroked" viewBox="0 0 24 24" aria-hidden="true"><path d="M14.8 3v11.3a4.7 4.7 0 1 1-4-4.6v3.1a1.7 1.7 0 1 0 1 1.5V3h3Zm0 0c.5 2.5 1.9 4 4.5 4.5v3.1a8.4 8.4 0 0 1-4.5-1.7" /></svg>
                        </a>
                    </nav>
                </div>

                <span class="site-footer__network-divider" aria-hidden="true"></span>

                <div class="site-footer__apps">
                    <strong>Aplikacje mobilne:</strong>
                    <div class="site-footer__store-list">
                        <span v-for="app in footer.mobile_apps" :key="app.label" class="site-footer__store-badge" :aria-label="`${app.label} — ${app.status}`">
                            <svg v-if="app.icon === 'apple'" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.7 12.8c0-2.7 2.2-4 2.3-4.1a5 5 0 0 0-3.9-2.1c-1.7-.2-3.2 1-4 1s-2-1-3.4-1A5.1 5.1 0 0 0 3.4 9c-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.3 2.5 1.3-.1 1.8-.8 3.4-.8 1.6 0 2 .8 3.4.8s2.3-1.2 3.2-2.5a11 11 0 0 0 1.4-2.9 4.6 4.6 0 0 1-2.7-3.5ZM14 4.8A4.6 4.6 0 0 0 15.1 1 4.7 4.7 0 0 0 12 2.8a4.3 4.3 0 0 0-1.1 3.1A3.9 3.9 0 0 0 14 4.8Z" /></svg>
                            <svg v-else viewBox="0 0 24 24" aria-hidden="true"><path d="m4 3 10.4 9L4 21V3Z" class="play-main" /><path d="m14.4 12 3.1-2.7 2.8 1.6c.9.5.9 1.7 0 2.2l-2.8 1.6-3.1-2.7Z" class="play-accent" /></svg>
                            <span><small>{{ app.status }}</small><b>{{ app.label }}</b></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="site-footer__shell site-footer__bottom-bar" :class="props.shellWidthClass">
            <span>{{ footer.copyright }}</span>
            <nav aria-label="Dokumenty prawne">
                <a v-for="link in footer.legal_links" :key="link.href" :href="link.href">{{ link.label }}</a>
            </nav>
        </div>
    </footer>
</template>
