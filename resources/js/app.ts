import '../css/app.css';
import '../images/analytics/cookie-mascot-blink.webp';
import '../images/analytics/cookie-mascot.png';
import './bootstrap';

import { setupCsrfSessionLifecycle } from '@/lib/csrfSession';
import { registerPwaServiceWorker } from '@/lib/pwa';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'prawkonaraz.pl';

setupCsrfSessionLifecycle();
registerPwaServiceWorker();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    progress: false,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
});
