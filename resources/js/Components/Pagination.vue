<script>
import {Link} from '@inertiajs/vue3'; // Используем Link от Inertia

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
    methods: {
        // Вспомогательная функция для очистки HTML сущностей (для « и »)
        decodeHtml(html) {
            if (typeof document === 'undefined') return html; // Защита для SSR, если используется
            const txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }
    }
}
</script>

<template>
    <!-- Показываем пагинацию только если есть ссылки -->
    <div v-if="links.length > 3"
         class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 mt-4 rounded-md shadow-sm">
        <!-- Информация о показанных элементах (опционально, требует данных `from` и `to` от пагинатора) -->
        <!-- <div class="flex flex-1 justify-between sm:hidden"> ... </div> -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <!-- Информация о страницах (можно получить из props пагинатора, если передавать full object) -->
                <!-- <p class="text-sm text-gray-700">
                  Showing
                  <span class="font-medium">{{ $page.props.orders.from }}</span>
                  to
                  <span class="font-medium">{{ $page.props.orders.to }}</span>
                  of
                  <span class="font-medium">{{ $page.props.orders.total }}</span>
                  results
                </p> -->
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <template v-for="(link, index) in links" :key="index">
                        <!-- Ссылка "Назад" / "Вперед" -->
                        <Link v-if="link.url"
                              :href="link.url"
                              preserve-scroll
                              preserve-state
                              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                              :class="{
                      'text-gray-900': !link.active,
                      'text-gray-400 cursor-default': !link.url, // Если ссылка неактивна (первая/последняя страница)
                      'rounded-l-md': index === 0,
                      'rounded-r-md': index === links.length - 1
                  }"
                              v-html="decodeHtml(link.label)"
                        />
                        <!-- Неактивная ссылка -->
                        <span v-else
                              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-400 ring-1 ring-inset ring-gray-300 cursor-default"
                              :class="{
                      'rounded-l-md': index === 0,
                      'rounded-r-md': index === links.length - 1
                   }"
                              v-html="decodeHtml(link.label)"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>
