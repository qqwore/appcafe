// resources/js/Pages/Login.vue

<template>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 pt-6 sm:pt-0">
        <!-- Логотип -->
        <div class="mb-8">
            <!-- Замените на ваш реальный логотип (img, svg) -->
            <a href="/" class="text-3xl font-bold text-center">
                <span class="text-stone-700">Интеллект</span><span class="text-emerald-500">Ум</span>
            </a>
        </div>

        <!-- Форма входа -->
        <div class="w-full sm:max-w-md px-8 py-6 bg-gray-50 shadow-md overflow-hidden rounded-lg">
            <h2 class="text-2xl font-bold text-center mb-6 text-gray-700">Вход</h2>

            <form @submit.prevent="submit">
                <!-- Поле Телефон -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="phone">
                        Телефон
                    </label>
                    <input type="text"
                           placeholder="Введите ваш номер телефона"
                           id="phone"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-transparent transition duration-150 ease-in-out"
                           v-model="form.phone"
                           required
                           autocomplete="username"> <!-- autocomplete="username" часто помогает для полей логина -->
                    <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                </div>

                <!-- Поле Пароль -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="password">
                        Пароль
                    </label>
                    <input type="password"
                           placeholder="Введите ваш пароль"
                           id="password"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-transparent transition duration-150 ease-in-out"
                           v-model="form.password"
                           required
                           autocomplete="current-password">
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                    <!-- Ошибка аутентификации (если используете ключ 'email' или другой общий в контроллере) -->
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>

                <!-- Кнопка Войти -->
                <div class="mb-4">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-emerald-500 border border-transparent rounded-md font-semibold text-white hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150 ease-in-out"
                            :disabled="form.processing">
                        <span v-if="!form.processing">Войти</span>
                        <span v-else>Вход...</span> <!-- Индикатор загрузки -->
                    </button>
                </div>

                <!-- Ссылка на регистрацию -->
                <div class="text-center text-sm text-gray-600">
                    Нет аккаунта?
                    <a :href="route('register')" class="font-medium text-emerald-600 hover:text-emerald-500 hover:underline">
                        Зарегистрируйтесь
                    </a>
                    <!-- Или используйте Inertia Link, если есть страница регистрации -->
                    <!-- <Link :href="route('register')" class="font-medium text-emerald-600 hover:text-emerald-500 hover:underline">
                        Зарегистрируйтесь
                    </Link> -->
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import {useForm} from '@inertiajs/vue3';
// import { Link } from '@inertiajs/vue3'; // Раскомментируйте, если используете <Link>

export default {
    name: 'Login',

    // components: { // Раскомментируйте, если используете <Link>
    //   Link,
    // },

    data() {
        return {
            form: useForm({
                // Используем 'phone' вместо 'email'
                phone: '',
                password: '',
                remember: false // Оставим, если нужна галочка "Запомнить меня" (нужно добавить в template)
            })
        };
    },

    methods: {
        submit() {
            // Отправляем на именованный маршрут 'login'
            // Убедитесь, что маршрут POST /login существует в routes/web.php
            // и указывает на AuthenticatedSessionController@store
            this.form.post(route('login'), { // Требует настроенный Ziggy
                // или this.form.post('/login', { ... });
                onFinish: () => {
                    this.form.reset('password'); // Сбрасываем пароль после попытки
                },
            });
        }
    },

    // Если нужен Layout (например, гостевой)
    // layout: import('@/Layouts/GuestLayout.vue').then(m => m.default)
}
</script>

<style scoped>
/* Дополнительные стили, если Tailwind недостаточно */
</style>
