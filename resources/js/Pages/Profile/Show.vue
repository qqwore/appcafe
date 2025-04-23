

<template>
    <!-- Предполагается, что у вас есть основной Layout с хедером и футером -->
    <!-- Если нет, добавьте их сюда или создайте Layout -->

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Заголовок страницы (если нужен) -->
            <!-- <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
              Личный кабинет
            </h2> -->

            <!-- Навигация по вкладкам -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        @click="activeTab = 'personal'"
                        :class="[
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg',
              activeTab === 'personal'
                ? 'border-emerald-500 text-emerald-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]">
                        Личные данные
                    </button>
                    <button
                        @click="activeTab = 'history'"
                        :class="[
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg',
              activeTab === 'history'
                ? 'border-emerald-500 text-emerald-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]">
                        История заказов
                    </button>
                </nav>
            </div>

            <!-- Содержимое вкладок -->
            <div>
                <!-- Вкладка: Личные данные -->
                <div v-if="activeTab === 'personal'">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-8">
                            <!-- Имя -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Имя</label>
                                <div class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user.name }}
                                </div>
                            </div>

                            <!-- Телефон -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Телефон</label>
                                <div class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user.phone }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                                <div class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>

                            <!-- Кнопка Изменить - можно добавить ссылку на страницу редактирования -->
                            <div class="md:col-span-3 flex justify-end items-center mt-4">
                                <!-- Замените # на реальный маршрут редактирования -->
                                <a href="#"
                                   class="inline-flex items-center px-4 py-2 border border-emerald-500 rounded-md shadow-sm text-sm font-medium text-emerald-600 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    Изменить
                                </a>
                            </div>
                        </div>

                        <!-- Кнопка Выход -->
                        <div class="mt-8 pt-6 border-t border-gray-200 flex justify-start">
                            <button
                                @click="logout"
                                type="button"
                                class="text-red-600 hover:text-red-800 hover:underline focus:outline-none focus:underline">
                                Выход из личного кабинета
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Вкладка: История заказов -->
                <div v-if="activeTab === 'history'">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <p class="text-gray-600">История заказов будет доступна здесь.</p>
                        <!-- Сюда будете добавлять список заказов -->
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
// Импортируем router из нового адаптера Inertia для программных запросов
import { router } from '@inertiajs/vue3';
import AppLayout from "../../Layouts/AppLayout.vue";

export default {
    name: 'Dashboard', // Или Dashboard
    layout: AppLayout,
    // Если данные пользователя передаются как пропс (а не только через $page)
    // props: {
    //   user: Object, // Или более точное определение { id: Number, name: String, ... }
    // },

    data() {
        return {
            activeTab: 'personal', // Какая вкладка активна по умолчанию
        };
    },

    methods: {
        logout() {
            // Отправляем POST-запрос на именованный маршрут 'logout'
            // Убедитесь, что этот маршрут существует в routes/web.php
            // и указывает на AuthenticatedSessionController@destroy
            router.post(route('logout'));
        },
    },

    // Если вы используете общий Layout компонент:
    // layout: import('@/Layouts/AuthenticatedLayout.vue').then(m => m.default) // Пример
}
</script>

<style scoped>
/* Стили для этого конкретного компонента, если Tailwind недостаточно */
</style>
