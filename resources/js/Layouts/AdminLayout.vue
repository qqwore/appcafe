<script>
// Используем Options API
import {Link, router} from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'; // Убедитесь, что путь к Pagination.vue правильный

export default {
    name: 'AdminLayout',
    components: {
        Link,
        Pagination,
    },
    data() {
        return {
            navigation: [
                {name: 'Статистика', hrefName: 'admin.statistics.index', currentMatcher: '/admin/statistics'},
                {name: 'Управление запасами', hrefName: 'admin.menu-stock.index', currentMatcher: '/admin/menu-stock'},
                {name: 'Заказы', hrefName: 'admin.orders.index', currentMatcher: '/admin/orders'},
                // { name: 'Пользователи', hrefName: 'admin.users.index', currentMatcher: '/admin/users' },
                // { name: 'Допы', hrefName: 'admin.extras.index', currentMatcher: '/admin/extras' },
                // { name: 'Товары', hrefName: 'admin.products.index', currentMatcher: '/admin/products' },
            ],
            availableRoutes: [],
            // Для управления видимостью и текстом flash-сообщений
            showFlashMessage: false,
            showFlashError: false,
            flashMessageText: null,
            flashErrorText: null,
            flashMessageTimer: null,
            flashErrorTimer: null,
        };
    },
    computed: {
        computedNavigation() {
            return this.navigation.map(item => ({
                ...item,
                href: this.routeExists(item.hrefName) ? route(item.hrefName) : '#'
            }));
        },
        pageErrors() {
            const errors = this.$page.props.errors || {};
            if (typeof errors === 'object' && !Array.isArray(errors)) {
                return Object.values(errors).flat();
            }
            return Array.isArray(errors) ? errors : [];
        },
        hasPageErrors() {
            return this.pageErrors.length > 0;
        }
    },
    methods: {
        isCurrent(matcher) {
            if (typeof window === 'undefined') return false;
            return window.location.pathname.startsWith(matcher);
        },
        routeExists(name) {
            return typeof route === 'function' && this.availableRoutes.includes(name);
        },
        loadRoutes() {
            if (typeof route !== 'undefined' && typeof Ziggy !== 'undefined' && Ziggy.routes) {
                this.availableRoutes = Object.keys(Ziggy.routes);
            }
        },
        logout() {
            router.post(route('logout'));
        },
        triggerFlashMessage(message) {
            this.flashMessageText = message;
            this.showFlashMessage = true;
            clearTimeout(this.flashMessageTimer);
            this.flashMessageTimer = setTimeout(() => {
                this.showFlashMessage = false;
                this.flashMessageText = null;
            }, 15000); // 15 секунд
        },
        triggerFlashError(error) {
            this.flashErrorText = error;
            this.showFlashError = true;
            clearTimeout(this.flashErrorTimer);
            this.flashErrorTimer = setTimeout(() => {
                this.showFlashError = false;
                this.flashErrorText = null;
            }, 15000); // 15 секунд
        }
    },
    watch: {
        '$page.props': {
            handler(newProps, oldProps) {
                this.loadRoutes(); // Обновляем роуты

                // Обработка flash сообщений
                const newMessage = newProps.flash?.message;
                // Сравниваем с this.flashMessageText чтобы не показывать одно и то же сообщение повторно при других изменениях props
                if (newMessage && newMessage !== this.flashMessageText) {
                    this.triggerFlashMessage(newMessage);
                } else if (!newMessage && this.showFlashMessage) {
                    // Если сообщение было удалено из пропсов (например, другим компонентом), скрываем его
                    // this.showFlashMessage = false; // Опционально, таймер должен справиться
                }


                const newError = newProps.flash?.error;
                if (newError && newError !== this.flashErrorText) {
                    this.triggerFlashError(newError);
                } else if (!newError && this.showFlashError) {
                    // this.showFlashError = false; // Опционально
                }
            },
            deep: true,
            immediate: true
        }
    },
    beforeUnmount() {
        // Очищаем таймеры при уничтожении компонента
        clearTimeout(this.flashMessageTimer);
        clearTimeout(this.flashErrorTimer);
    }
}
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside class="w-64 bg-stone-800 text-stone-300 flex flex-col flex-shrink-0">
            <!-- Logo -->
            <div class="h-16 flex items-center justify-center px-4 border-b border-stone-700">
                <Link :href="routeExists('admin.dashboard') ? route('admin.dashboard') : '/admin/dashboard'"
                      class="text-2xl font-bold">
                    <span class="text-stone-100">Интеллект</span><span class="text-emerald-400">Ум</span>
                    <span class="block text-xs text-stone-400 text-center">Admin Panel</span>
                </Link>
            </div>
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4">
                <ul>
                    <li v-for="item in computedNavigation" :key="item.name">
                        <Link :href="item.href"
                              :class="[
                                  isCurrent(item.currentMatcher)
                                      ? 'bg-stone-700 text-white'
                                      : 'text-stone-300 hover:bg-stone-700 hover:text-white',
                                  'group flex items-center px-4 py-2.5 text-base font-medium'
                              ]">
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </nav>
            <!-- Footer Links -->
            <div class="p-4 border-t border-stone-700">
                <Link :href="routeExists('home') ? route('home') : '/'"
                      class="text-base text-stone-400 hover:text-emerald-400 block mb-2">Вернуться на сайт
                </Link>
                <button @click="logout" type="button"
                        class="text-base text-stone-400 hover:text-red-500 w-full text-left">Выход
                </button>
            </div>
        </aside>

        <!-- Правая часть: Основной контент + Пагинация -->
        <div class="flex flex-col flex-1 justify-between overflow-hidden">

            <!-- Основной контент (добавляем relative для позиционирования flash) -->
            <main class="relative flex-1 overflow-y-auto p-6 md:p-8">

                <!-- Flash Messages -->
                <div
                    class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-md sm:max-w-lg md:max-w-xl z-[60] pt-6 px-4 pointer-events-none">
                    <div class="space-y-3 pointer-events-auto">
                        <!-- Сообщение об успехе -->
                        <transition name="flash">
                            <div v-if="showFlashMessage && flashMessageText"
                                 class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-lg"
                                 role="alert">
                                <p>{{ flashMessageText }}</p>
                            </div>
                        </transition>
                        <!-- Сообщение об ошибке (из flash.error) -->
                        <transition name="flash">
                            <div v-if="showFlashError && flashErrorText"
                                 class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow-lg"
                                 role="alert">
                                <p>{{ flashErrorText }}</p>
                            </div>
                        </transition>
                        <!-- Ошибки валидации Laravel -->
                        <div v-if="hasPageErrors"
                             class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow-lg"
                             role="alert">
                            <p class="font-bold">Обнаружены ошибки:</p>
                            <ul class="mt-1 list-disc list-inside text-sm">
                                <li v-for="(error, index) in pageErrors" :key="index">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- Конец Flash Messages -->

                <slot/>
            </main>

            <!-- Пагинация будет отображаться здесь, если она передана -->
            <div v-if="$page.props.orders && $page.props.orders.links && $page.props.orders.links.length > 3"
                 class="flex-shrink-0 bg-gray-100 px-6 pb-4 pt-2 border-t border-gray-200">
                <Pagination :links="$page.props.orders.links"/>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Стили для AdminLayout */

/* Анимация для flash-сообщений */
.flash-enter-active, .flash-leave-active {
    transition: opacity 0.5s ease;
}

.flash-enter-from, .flash-leave-to {
    opacity: 0;
}
</style>
