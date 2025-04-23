// resources/js/Layouts/AppLayout.vue

<template>
    <div class="flex flex-col min-h-screen bg-gray-100"> <!-- Основной контейнер -->

        <!-- ======================== -->
        <!--         Хедер         -->
        <!-- ======================== -->
        <header class="bg-white shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Левая часть: Логотип -->
                    <div class="flex-shrink-0">
                        <a href="/">
                            <h1 class="text-2xl font-bold">
                                <span class="text-stone-700">Интеллект</span><span class="text-emerald-500">Ум</span>
                            </h1>
                        </a>
                    </div>

                    <!-- Центральная часть: Навигация (для десктопа) -->
                    <nav class="hidden md:flex space-x-8">
                        <a :href="routeExists('home') ? route('home') : '/'"
                           class="font-medium text-gray-600 hover:text-emerald-500 transition duration-150 ease-in-out"
                           :class="{ 'text-emerald-600 border-b-2 border-emerald-500': isUrl('/') }">
                            Главная
                        </a>
                        <a :href="routeExists('menu.index') ? route('menu.index') : '/menu'"
                           class="font-medium text-gray-600 hover:text-emerald-500 transition duration-150 ease-in-out"
                           :class="{ 'text-emerald-600 border-b-2 border-emerald-500': isUrl('/menu') || isUrl('/menu/*') }">
                            Меню
                        </a>
                        <!-- Добавьте другие ссылки сюда -->
                    </nav>

                    <!-- Правая часть: Иконки/Профиль -->
                    <div class="hidden md:flex items-center space-x-4">

                        <!-- Иконка Корзины (Только для авторизованных) -->
                        <a v-if="$page.props.auth.user"
                           :href="routeExists('cart.index') ? route('cart.index') : '/cart'"
                           class="text-gray-500 hover:text-emerald-500 relative group"
                           title="Корзина">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <!-- Опционально: Счетчик товаров в корзине -->
                            <!-- <span v-if="$page.props.cartCount > 0" class="absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                                {{ $page.props.cartCount }}
                            </span> -->
                        </a>

                        <!-- Профиль / Вход -->
                        <div v-if="$page.props.auth.user" class="relative">
                            <!-- Иконка пользователя (ссылка на профиль) -->
                            <!-- ИЗМЕНЕНО: Используем profile.show -->
                            <a :href="routeExists('profile.show') ? route('profile.show') : '/profile'"
                               class="text-gray-500 hover:text-emerald-500" title="Личный кабинет">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </a>
                        </div>
                        <!-- Кнопки Вход/Регистрация (для гостей) -->
                        <div v-else class="space-x-4">
                            <a :href="routeExists('login') ? route('login') : '/login'"
                               class="text-sm font-medium text-gray-600 hover:text-emerald-500">Вход</a>
                            <a :href="routeExists('register') ? route('register') : '/register'"
                               class="text-sm font-medium text-gray-600 hover:text-emerald-500">Регистрация</a>
                        </div>
                    </div>

                    <!-- Кнопка мобильного меню -->
                    <div class="md:hidden flex items-center">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-500 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                                <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Мобильное меню -->
            <div v-show="mobileMenuOpen" class="md:hidden border-t border-gray-200">
                <!-- Основные ссылки -->
                <div class="pt-2 pb-3 space-y-1 px-2">
                    <a :href="routeExists('home') ? route('home') : '/'" class="block px-3 py-2 rounded-md text-base font-medium" :class="isUrl('/') ? 'text-emerald-700 bg-emerald-50' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'">Главная</a>
                    <a :href="routeExists('menu.index') ? route('menu.index') : '/menu'" class="block px-3 py-2 rounded-md text-base font-medium" :class="isUrl('/menu') || isUrl('/menu/*') ? 'text-emerald-700 bg-emerald-50' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'">Меню</a>
                </div>
                <!-- Профиль/Корзина в мобильном меню -->
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <!-- Иконка Корзины (Только для авторизованных) -->
                    <div v-if="$page.props.auth.user" class="flex items-center px-5 mb-3">
                        <a :href="routeExists('cart.index') ? route('cart.index') : '/cart'"
                           class="text-gray-500 hover:text-emerald-500 relative group"
                           title="Корзина">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <!-- Опционально: Счетчик товаров -->
                            <!-- <span v-if="$page.props.cartCount > 0" ...> -->
                        </a>
                    </div>
                    <!-- Блок Авторизованного пользователя -->
                    <div v-if="$page.props.auth.user" class="px-5">
                        <div class="font-medium text-base text-gray-800">{{ $page.props.auth.user.name }}</div>
                        <div class="font-medium text-sm text-gray-500 mb-2">{{ $page.props.auth.user.email }}</div>
                        <!-- ИЗМЕНЕНО: Используем profile.show -->
                        <a :href="routeExists('profile.show') ? route('profile.show') : '/profile'"
                           class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">Личный кабинет</a>
                        <button @click="logout"
                                class="block w-full text-left mt-1 px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50 hover:text-red-800">
                            Выход
                        </button>
                    </div>
                    <!-- Блок Гостя -->
                    <div v-else class="px-5 space-y-1">
                        <a :href="routeExists('login') ? route('login') : '/login'" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">Вход</a>
                        <a :href="routeExists('register') ? route('register') : '/register'" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">Регистрация</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ======================== -->
        <!--     Основной контент     -->
        <!-- ======================== -->
        <main class="flex-grow">
            <slot/>
        </main>

        <!-- ======================== -->
        <!--          Футер         -->
        <!-- ======================== -->
        <footer class="bg-stone-800 text-stone-400 py-8 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8 items-center text-center md:text-left">
                <!-- Левая часть: Логотип -->
                <div class="flex justify-center md:justify-start">
                    <h1 class="text-2xl font-bold">
                        <span class="text-stone-300">Интеллект</span><span class="text-emerald-400">Ум</span>
                    </h1>
                </div>
                <!-- Центральная часть: Адрес -->
                <div class="text-sm flex items-center justify-center text-stone-300">
                    <svg class="w-5 h-5 mr-2 inline-block flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                    <span>пр-т Ленина 87, 3 корпус ЮУрГУ, 3 этаж</span>
                </div>
                <!-- Правая часть: Телеграм -->
                <div class="text-sm text-center md:text-right">
                    <span>Мы в телеграм: </span>
                    <a href="https://t.me/IntelektUm_susu" target="_blank" rel="noopener noreferrer" class="text-emerald-400 hover:text-emerald-300 hover:underline">
                        @IntelektUm_susu
                    </a>
                </div>
            </div>
        </footer>

    </div>
</template>

<script>
import { router } from '@inertiajs/vue3'; // Используем для logout

export default {
    name: 'AppLayout',
    data() {
        return {
            mobileMenuOpen: false,
            routes: [],
        };
    },
    methods: {
        logout() {
            router.post(route('logout'));
            this.mobileMenuOpen = false;
        },
        isUrl(urlPattern) {
            const currentUrl = this.$page.url;
            if (urlPattern.endsWith('/*')) {
                const basePattern = urlPattern.slice(0, -2);
                return currentUrl.startsWith(basePattern);
            }
            return currentUrl === urlPattern;
        },
        routeExists(name) {
            return typeof route === 'function' && this.routes.includes(name);
        },
        loadRoutes() {
            if (typeof route !== 'undefined' && typeof Ziggy !== 'undefined' && Ziggy.routes) {
                this.routes = Object.keys(Ziggy.routes);
            }
        }
    },
    watch: {
        '$page.url': function () {
            this.mobileMenuOpen = false;
        }
    },
    mounted() {
        this.loadRoutes();
    }
}
</script>

<style scoped>
/* Стили только для этого Layout, если необходимо */
</style>
