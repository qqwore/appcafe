<template>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 pt-6 sm:pt-0">
        <!-- Логотип -->
        <div class="mb-8">
            <!-- Замените на ваш реальный логотип (img, svg) -->
            <a :href="route('home')" v-if="routes.includes('home')"> <!-- Ссылка на главную, если есть -->
                <h1 class="text-3xl font-bold text-center">
                    <span class="text-stone-700">Интеллект</span><span class="text-emerald-500">Ум</span>
                </h1>
            </a>
            <h1 class="text-3xl font-bold text-center" v-else> <!-- Просто текст, если нет роута 'home' -->
                <span class="text-stone-700">Интеллект</span><span class="text-emerald-500">Ум</span>
            </h1>
        </div>

        <!-- Форма регистрации -->
        <div class="w-full sm:max-w-md px-8 py-6 bg-gray-50 shadow-md overflow-hidden rounded-lg">
            <h2 class="text-2xl font-bold text-center mb-6 text-gray-700">Регистрация</h2>

            <form @submit.prevent="submit">
                <!-- Поле Имя -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="name">
                        Имя
                    </label>
                    <input type="text"
                           placeholder="Введите ваше имя"
                           id="name"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-transparent transition duration-150 ease-in-out"
                           v-model="form.name"
                           required
                           autofocus
                           autocomplete="name">
                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                </div>

                <!-- Поле Телефон -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="phone">
                        Телефон
                    </label>
                    <!-- Тип 'tel' для семантики и мобильных клавиатур -->
                    <input type="tel"
                           placeholder="Введите ваш номер телефона"
                           id="phone"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2
                    focus:ring-emerald-300 focus:border-transparent transition duration-150 ease-in-out"
                           v-model="form.phone"
                           required
                           autocomplete="tel">
                    <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                </div>

                <!-- Поле Email -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="email">
                        Email
                    </label>
                    <input type="email"
                           placeholder="Введите ваш email"
                           id="email"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-transparent transition duration-150 ease-in-out"
                           v-model="form.email"
                           required
                           autocomplete="username">
                    <!-- 'username' часто используется браузерами для автозаполнения email при регистрации -->
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>

                <!-- Поле Пароль -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="password">
                        Пароль
                    </label>
                    <input type="password"
                           placeholder="Создайте пароль"
                           id="password"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-transparent transition duration-150 ease-in-out"
                           v-model="form.password"
                           required
                           autocomplete="new-password"> <!-- 'new-password' для регистрации -->
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                </div>

                <!-- Поле Подтверждение пароля -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="password_confirmation">
                        Подтвердите пароль
                    </label>
                    <input type="password"
                           placeholder="Повторите пароль"
                           id="password_confirmation"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-transparent transition duration-150 ease-in-out"
                           v-model="form.password_confirmation"
                           required
                           autocomplete="new-password">
                    <div v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">
                        {{ form.errors.password_confirmation }}
                    </div>
                </div>

                <!-- Кнопка Зарегистрироваться -->
                <div class="mb-4">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-emerald-500 border border-transparent rounded-md font-semibold text-white hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150 ease-in-out"
                            :disabled="form.processing">
                        <span v-if="!form.processing">Зарегистрироваться</span>
                        <span v-else>Регистрация...</span> <!-- Индикатор загрузки -->
                    </button>
                </div>

                <!-- Ссылка на вход -->
                <div class="text-center text-sm text-gray-600">
                    Уже зарегистрированы?
                    <a :href="route('login')"
                       class="font-medium text-emerald-600 hover:text-emerald-500 hover:underline">
                        Войдите
                    </a>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import {useForm} from '@inertiajs/vue3';
import {route} from "ziggy-js";

export default {
    name: 'Register',

    components: {

    },

    data() {
        return {
            form: useForm({
                name: '',
                phone: '', // Добавлено поле phone
                email: '',
                password: '',
                password_confirmation: '', // Обязательно для валидации Laravel
                // terms: false, // Можно добавить поле для согласия с условиями
            }),
            routes: [], // Для проверки существования роута 'home'
        };
    },

    methods: {
        submit() {
            // Отправляем POST-запрос на маршрут 'register'
            // Убедитесь, что маршрут POST /register существует в routes/web.php
            this.form.post(route('register'), {
                onFinish: () => {
                    // Сбрасываем поля пароля после попытки (успешной или нет)
                    this.form.reset('password', 'password_confirmation');
                },
            });
        },
// Проверяем, какие роуты доступны из Ziggy
        checkRoutes() {
            if (typeof route !== 'undefined' && typeof Ziggy !== 'undefined' && Ziggy.routes) {
                this.routes = Object.keys(Ziggy.routes);
            }
        }
    },

    mounted() {
        this.checkRoutes(); // Проверяем роуты при монтировании
    }

// Если нужен Layout (например, гостевой)
// layout: import('@/Layouts/GuestLayout.vue').then(m => m.default)
}
</script>

<style scoped>
/* Дополнительные стили, если Tailwind недостаточно */
</style>
