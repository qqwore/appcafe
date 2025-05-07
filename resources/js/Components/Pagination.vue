<script>
// Используем Options API
import {Link} from '@inertiajs/vue3';

export default {
    name: 'Pagination',
    components: {
        Link,
    },
    props: {
        links: {
            type: Array,
            default: () => [],
        },
    },
    methods: { // <-- МЕТОДЫ ДОЛЖНЫ БЫТЬ ЗДЕСЬ
        decodeHtml(html) {
            if (typeof document === 'undefined') return html;
            const txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        },
        getLinkLabel(label) {
            if (label.includes('Previous')) {
                return 'Пред.';
            }
            if (label.includes('Next')) {
                return 'След.';
            }
            // Для числовых страниц и "..." используем decodeHtml
            return this.decodeHtml(label);
        }
    }
}
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex justify-center mt-6">
            <nav class="isolate inline-flex -space-x-px rounded-lg shadow-sm" aria-label="Pagination">
                <template v-for="(link, index) in links" :key="index">
                    <Link v-if="link.url"
                          :href="link.url"
                          preserve-scroll
                          preserve-state
                          class="relative inline-flex items-center px-6 py-3 text-base font-semibold ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0 transition-colors duration-150 ease-in-out"
                          :class="{
                      // Стили для АКТИВНОЙ ссылки: ЯВНО указываем цвет текста и фона, БЕЗ hover, который меняет фон на серый
                      'bg-emerald-500 text-white border-emerald-500 focus:ring-emerald-400': link.active,
                      // Стили для НЕАКТИВНОЙ, но КЛИКАБЕЛЬНОЙ ссылки
                      'text-gray-900 hover:bg-gray-100': !link.active,
                      // Стили для НЕКЛИКАБЕЛЬНЫХ ссылок (<< Prev, Next >> когда на крайних страницах)
                      'text-gray-400 cursor-default': !link.url && !link.active, // Это для < и > когда они неактивны
                      // Скругления
                      'rounded-l-lg': index === 0,
                      'rounded-r-lg': index === links.length - 1
                  }"
                          v-html="getLinkLabel(link.label)"
                    />
                    <span v-else
                          class="relative inline-flex items-center px-6 py-3 text-base font-semibold text-gray-400 ring-1 ring-inset ring-gray-300 cursor-default"
                          :class="{
                      'rounded-l-lg': index === 0,
                      'rounded-r-lg': index === links.length - 1
                   }"
                          v-html="getLinkLabel(link.label)"
                    />
                </template>
            </nav>
        </div>
    </div>
</template>

<style scoped>
/* Стили для Pagination */
</style>
