<script>
import AppLayout from '@/Layouts/AppLayout.vue'; // Используем alias @/ или '../Layouts/AppLayout.vue'
import {Head, router} from '@inertiajs/vue3';
import {ref, computed} from 'vue';

export default {
    name: 'ProfileShow',
    layout: AppLayout,
    components: {Head},
    props: {
        activeOrders: {
            type: Array,
            default: () => []
        },
        orderHistory: {
            type: Array,
            default: () => []
        },
        // errors: Object, // Если будете передавать ошибки
    },
    setup(props) {
        const activeTab = ref('personal'); // 'personal' или 'history'

        const formatPrice = (price) => {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        };

        const getStatusClass = (status) => {
            switch (status?.toLowerCase()) {
                case 'preparing':
                    return 'bg-orange-100 text-orange-800';
                case 'ready':
                    return 'bg-blue-100 text-blue-800';
                case 'completed':
                case 'received':
                    return 'bg-green-100 text-green-800';
                case 'cancelled':
                    return 'bg-red-100 text-red-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        };

        const getStatusText = (status) => {
            switch (status?.toLowerCase()) {
                case 'preparing':
                    return 'Готовится';
                case 'ready':
                    return 'Готов';
                case 'completed':
                case 'received':
                    return 'Завершен'; // Или 'Получен'
                case 'cancelled':
                    return 'Отменен';
                default:
                    return status || 'Неизвестно';
            }
        };

        const logout = () => {
            router.post(route('logout'));
        };

        return {
            activeTab,
            formatPrice,
            getStatusClass,
            getStatusText,
            logout,
        };
    }
}
</script>

<template>
    <Head title="Личный кабинет"/>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">

            <!-- Секция Активных заказов -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4 px-4 sm:px-0">Активные заказы</h2>
                <div v-if="activeOrders.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Карточка Активного Заказа -->
                    <div v-for="order in activeOrders" :key="order.id" class="bg-white shadow-md rounded-lg p-6">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                 <span :class="getStatusClass(order.status)"
                                       class="inline-block text-xs font-semibold mr-2 px-2.5 py-0.5 rounded-full">
                                     {{ getStatusText(order.status) }}
                                 </span>
                                <span class="text-sm text-gray-500">от {{ order.created_date }}</span>
                            </div>
                            <div class="text-lg font-bold text-emerald-600">{{ order.number }}</div>
                        </div>
                        <p v-if="order.status?.toLowerCase() === 'ready'" class="text-sm text-blue-700 mb-3">Для
                            получения назовите номер заказа</p>
                        <p v-else-if="order.status?.toLowerCase() === 'preparing'" class="text-sm text-orange-700 mb-3">
                            Ваш заказ готовится</p>

                        <div class="space-y-2 border-t border-b border-gray-200 py-3 my-3">
                            <div v-for="item in order.items" :key="item.id"
                                 class="flex justify-between items-start text-sm">
                                <div class="flex-grow pr-2">
                                    <span class="font-medium text-gray-800">{{ item.product_name }}</span>
                                    <span v-if="item.quantity > 1" class="text-gray-500 ml-1">x{{
                                            item.quantity
                                        }}</span>
                                    <p v-if="item.options_description" class="text-xs text-gray-500 truncate">
                                        {{ item.options_description }}</p>
                                </div>
                                <div class="flex-shrink-0 font-medium text-gray-800">
                                    {{ formatPrice(item.price_per_item * item.quantity) }} ₽
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center font-semibold text-gray-800">
                            <span>Стоимость заказа</span>
                            <span>{{ formatPrice(order.total_price) }} ₽</span>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
                    Нет активных заказов
                </div>
            </section>

            <!-- Секция с Табами -->
            <section>
                <!-- Навигация по вкладкам -->
                <div class="mb-6 border-b border-gray-200 px-4 sm:px-0">
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
                    <div v-if="activeTab === 'personal'" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-8">
                            <!-- Имя -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Имя</label>
                                <div
                                    class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user?.name || 'Не указано' }}
                                </div>
                            </div>
                            <!-- Телефон -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Телефон</label>
                                <div
                                    class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user?.phone || 'Не указано' }}
                                </div>
                            </div>
                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                                <div
                                    class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user?.email || 'Не указано' }}
                                </div>
                            </div>
                            <!-- Кнопка Изменить -->
                            <div class="md:col-span-3 flex justify-end items-center mt-4">
                                <a href="#"
                                   class="inline-flex items-center px-4 py-2 border border-emerald-500 rounded-md shadow-sm text-sm font-medium text-emerald-600 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    Изменить
                                </a>
                            </div>
                        </div>
                        <!-- Кнопка Выход -->
                        <div class="mt-8 pt-6 border-t border-gray-200 flex justify-start">
                            <button @click="logout" type="button"
                                    class="text-red-600 hover:text-red-800 hover:underline focus:outline-none focus:underline">
                                Выход из личного кабинета
                            </button>
                        </div>
                    </div>

                    <!-- Вкладка: История заказов -->
                    <div v-if="activeTab === 'history'">
                        <div v-if="orderHistory.length > 0" class="space-y-6">
                            <!-- Карточка Истории Заказа -->
                            <details v-for="order in orderHistory" :key="order.id"
                                     class="bg-white shadow-sm rounded-lg group">
                                <summary class="flex justify-between items-center p-4 cursor-pointer list-none">
                                    <div class="flex items-center space-x-4">
                                         <span :class="getStatusClass(order.status)"
                                               class="inline-block text-xs font-semibold mr-2 px-2.5 py-0.5 rounded-full">
                                             {{ getStatusText(order.status) }}
                                         </span>
                                        <span class="text-sm text-gray-500">от {{ order.created_date }}</span>
                                        <span class="text-lg font-bold text-emerald-600">{{ order.number }}</span>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="font-semibold text-gray-800">{{
                                                formatPrice(order.total_price)
                                            }} ₽</span>
                                        <svg
                                            class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform duration-200"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </summary>
                                <!-- Детали заказа (скрыты по умолчанию) -->
                                <div class="border-t border-gray-200 px-6 py-4 space-y-2">
                                    <div v-for="item in order.items" :key="item.id"
                                         class="flex justify-between items-start text-sm">
                                        <div class="flex-grow pr-2">
                                            <span class="font-medium text-gray-800">{{ item.product_name }}</span>
                                            <span v-if="item.quantity > 1" class="text-gray-500 ml-1">x{{
                                                    item.quantity
                                                }}</span>
                                            <p v-if="item.options_description" class="text-xs text-gray-500 truncate">
                                                {{ item.options_description }}</p>
                                        </div>
                                        <div class="flex-shrink-0 font-medium text-gray-800">
                                            {{ formatPrice(item.price_per_item * item.quantity) }} ₽
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                        <div v-else class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
                            У вас еще нет завершенных заказов.
                        </div>
                    </div>

                </div> <!-- Конец Содержимого вкладок -->
            </section>

        </div>
    </div>
</template>
