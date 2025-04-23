import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3' // <-- ИСПОЛЬЗУЙТЕ ЭТОТ ИМПОРТ
import { InertiaProgress } from '@inertiajs/progress'

// --- Импорты Ziggy и CSS ---
import { ZiggyVue } from 'ziggy-js';
import '../css/app.css';

createInertiaApp({
    // resolve: name => { ... ваш код ... }, // Оставляем как есть
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        // !!! ВАЖНО: Убедитесь, что этот путь соответствует размещению Auth/Register.vue
        // Если Register.vue в resources/js/Pages/Auth/Register.vue,
        // то имя, передаваемое из контроллера должно быть 'Auth/Register'
        // и этот код должен правильно разрешить './Pages/Auth/Register.vue'
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Vue page not found: ${name}. Path checked: ./Pages/${name}.vue`);
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mount(el);
    },
    // Новый адаптер использует опцию progress здесь:
    progress: {
        // delay: 250, // Задержка перед показом
        color: '#4B5563', // Цвет линии прогресса
        includeCSS: true, // Включать ли CSS по умолчанию
        showSpinner: true, // Показывать ли спиннер
    },
});

// Удалите эту строку, т.к. progress настраивается в createInertiaApp
// InertiaProgress.init({
//     color: '#4B5563',
//     showSpinner: true,
// });
