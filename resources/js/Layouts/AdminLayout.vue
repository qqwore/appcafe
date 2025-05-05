<script>
// Используем Options API
import { Link, router } from '@inertiajs/vue3'; // Импортируем Link и router

export default {
    name: 'AdminLayout',
    components: {
        Link,
    },
    data() {
        return {
            // Список навигации
            navigation: [
                { name: 'Статистика', hrefName: 'admin.statistics.index', currentMatcher: '/admin/statistics' },
                { name: 'Меню на день', hrefName: 'admin.menu-stock.index', currentMatcher: '/admin/menu-stock' },
                { name: 'Заказы', hrefName: 'admin.orders.index', currentMatcher: '/admin/orders' },
                // Добавьте другие пункты по мере необходимости
            ],
            // Доступные роуты Ziggy (загрузим в mounted)
            availableRoutes: [],
        };
    },
    computed: {
        // Динамически генерируем href для навигации
        computedNavigation() {
            return this.navigation.map(item => ({
                ...item,
                href: this.routeExists(item.hrefName) ? route(item.hrefName) : '#' // Генерируем URL или #
            }));
        },
        // Получаем flash-сообщения из пропсов
        flashMessage() {
            return this.$page.props.flash?.message || null;
        },
        flashError() {
            return this.$page.props.flash?.error || null;
        },
        pageErrors() {
            return this.$page.props.errors || {};
        },
        hasPageErrors() {
            return Object.keys(this.pageErrors).length > 0;
        }
    },
    methods: {
        // Проверка текущего URL
        isCurrent(matcher) {
            if (typeof window === 'undefined') return false; // Защита SSR
            return window.location.pathname.startsWith(matcher);
        },
        // Проверка существования роута Ziggy
        routeExists(name) {
            return typeof route === 'function' && this.availableRoutes.includes(name);
        },
        // Загрузка доступных роутов
        loadRoutes() {
            if (typeof route !== 'undefined' && typeof Ziggy !== 'undefined' && Ziggy.routes) {
                this.availableRoutes = Object.keys(Ziggy.routes);
            }
        },
        // Метод для выхода
        logout() {
            router.post(route('logout'));
        },
    },
    mounted() {
        // Загружаем роуты при монтировании компонента
        this.loadRoutes();
    },
    watch: {
        // Следим за изменением пропсов (например, после навигации)
        // и перезагружаем роуты, если нужно (хотя обычно не требуется)
        '$page.props': {
            handler() {
                this.loadRoutes();
            },
            deep: true,
            immediate: true // Загрузить сразу при инициализации
        }
    }
}
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside class="w-64 bg-stone-800 text-stone-300 flex flex-col flex-shrink-0">
            <!-- Logo -->
            <div class="h-16 flex items-center justify-center px-4 border-b border-stone-700">
                <Link :href="routeExists('admin.dashboard') ? route('admin.dashboard') : '/admin'" class="text-2xl font-bold">
                    <span class="text-stone-100">Интеллект</span><span class="text-emerald-400">Ум</span>
                    <span class="block text-xs text-stone-400 text-center">Admin Panel</span>
                </Link>
            </div>
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4">
                <ul>
                    <!-- Используем computedNavigation из setup -->
                    <li v-for="item in computedNavigation" :key="item.name">
                        <!-- Увеличиваем размер шрифта с text-sm до text-base -->
                        <Link :href="item.href"
                              :class="[
                                  isCurrent(item.currentMatcher)
                                      ? 'bg-stone-700 text-white'
                                      : 'text-stone-300 hover:bg-stone-700 hover:text-white',
                                   'group flex items-center px-4 py-2.5 text-base font-medium' /* ИЗМЕНЕНО: py-2 -> py-2.5, text-sm -> text-base */
                              ]">
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </nav>
            <!-- Footer Links -->
            <div class="p-4 border-t border-stone-700">
                <!-- Увеличиваем размер шрифта с text-sm до text-base -->
                <Link :href="routeExists('home') ? route('home') : '/'" class="text-base text-stone-400 hover:text-emerald-400 block mb-2">Вернуться на сайт</Link>
                <button @click="logout" type="button" class="text-base text-stone-400 hover:text-red-500 w-full text-left">Выход</button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <!-- Flash Messages -->
            <div v-if="flashMessage" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ flashMessage }}</span>
            </div>
            <div v-if="flashError" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ flashError }}</span>
            </div>
            <div v-if="hasPageErrors" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Ошибка!</strong>
                <ul><li v-for="(error, key) in pageErrors" :key="key">- {{ error }}</li></ul>
            </div>

            <!-- Содержимое страницы админки -->
            <slot />
        </main>
    </div>
</template>

<style scoped>
/* Стили для AdminLayout */
</style>
