import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'; // <--- Импортируйте плагин Tailwind v4

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'], // <--- Убедитесь, что ваш CSS включен здесь
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(), // <--- Добавьте плагин Tailwind v4 сюда
                       // Конфигурация 'content' не нужна здесь,
                       // т.к. плагин автоматически сканирует
                       // файлы, обрабатываемые Vite (включая ваш app.js и Vue-компоненты)
                       // Если автосканирование не сработает, можно добавить:
                       // tailwindcss({
                       //   content: [
                       //      "./resources/**/*.blade.php",
                       //      "./resources/js/**/*.{vue,js,ts,jsx,tsx}",
                       //      "./Pages/**/*.vue",
                       //   ]
                       // }),
    ],
});
