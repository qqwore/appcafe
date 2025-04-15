import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/inertia-vue3'
import { InertiaProgress } from '@inertiajs/progress'

// --- Импортируйте Ziggy и плагин ZiggyVue ---
import { ZiggyVue } from 'ziggy-js';
import '../css/app.css';

createInertiaApp({
    resolve: name => {
        // Убедитесь, что путь здесь правильный относительно ЭТОГО файла
        // Если app.js в resources/js, а Pages в resources/js/Pages, то './Pages/' верно
        // Если Pages в корне проекта, путь может быть '../Pages/' или '../../Pages/'
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true }); // <-- ПРОВЕРЬТЕ ЭТОТ ПУТЬ!
        const page = pages[`./Pages/${name}.vue`]; // <-- И ЭТОТ ПУТЬ!
        if (!page) {
            throw new Error(`Vue page not found: ${name}. Path checked: ./Pages/${name}.vue`);
        }
        // Если используете Layout по умолчанию:
        // page.default.layout = page.default.layout || import('./Layouts/DefaultLayout.vue')
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy) // Если используете Ziggy для роутов Laravel
            .mount(el);
    },
});

InertiaProgress.init({
    color: '#4B5563',
    showSpinner: true,
});
