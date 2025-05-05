<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {Head, Link, router} from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'; // Импортируем пагинацию

export default {
    name: 'AdminOrdersIndex',
    layout: AdminLayout,
    components: {Head, Link, Pagination},
    props: {
        orders: Object, // Пагинированные данные { data: [], links: [], ... }
        currentTab: String,
        tabs: Array, // ['new', 'ready', 'completed']
        filters: Object,
    },
    data() {
        return {
            expandedOrderId: null, // ID раскрытого заказа
            loadingStatusChange: {}, // { orderId: true/false }
            tabNames: { // Имена для вкладок
                new: 'Новые',
                ready: 'Готовы к выдаче',
                completed: 'Выполненные'
            },
        };
    },
    methods: {
        toggleOrderDetails(orderId) {
            this.expandedOrderId = this.expandedOrderId === orderId ? null : orderId;
        },
        getStatusClass(status) {
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
        },
        getStatusText(status) {
            switch (status?.toLowerCase()) {
                case 'preparing':
                    return 'Готовится';
                case 'ready':
                    return 'Готов';
                case 'completed':
                case 'received':
                    return 'Завершен';
                case 'cancelled':
                    return 'Отменен';
                default:
                    return status || 'Неизвестно';
            }
        },
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },
        updateStatus(orderId, newStatus) {
            this.loadingStatusChange[orderId] = true; // Блокируем кнопки
            router.patch(route('admin.orders.updateStatus', {order: orderId}),
                {status: newStatus},
                {
                    preserveScroll: true,
                    preserveState: true, // Сохраняем раскрытие
                    onSuccess: () => console.log(`Status updated for order ${orderId}`),
                    onError: (errors) => alert('Ошибка смены статуса: ' + JSON.stringify(errors)),
                    onFinish: () => {
                        delete this.loadingStatusChange[orderId];
                    } // Разблокируем
                }
            );
        },
    }
}
</script>

<template>
    <Head title="Заказы"/>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-700">Заказы</h1>
    </div>

    <!-- Табы -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <Link v-for="tab in tabs" :key="tab"
                  :href="route('admin.orders.index', { tab: tab })"
                  :class="[
                      'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm',
                       currentTab === tab
                           ? 'border-emerald-500 text-emerald-600'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                   ]"
                  preserve-state preserve-scroll>
                {{ tabNames[tab] || tab }}
            </Link>
        </nav>
    </div>

    <!-- Список Заказов -->
    <div v-if="orders.data.length > 0" class="space-y-4">
        <div v-for="order in orders.data" :key="order.id" class="bg-white shadow rounded-lg">
            <!-- Краткая информация (кликабельная) -->
            <div @click="toggleOrderDetails(order.id)"
                 class="flex justify-between items-center p-4 cursor-pointer hover:bg-gray-50">
                <div class="flex items-center space-x-4 flex-wrap gap-y-1"> <!-- Добавлен flex-wrap -->
                    <span :class="getStatusClass(order.status)"
                          class="text-xs font-semibold px-2.5 py-0.5 rounded-full">{{
                            getStatusText(order.status)
                        }}</span>
                    <span class="text-lg font-bold text-emerald-600">{{ order.number }}</span>
                    <span class="text-sm text-gray-500">от {{ order.created_date }}</span>
                    <span class="text-sm text-gray-600 hidden md:inline">Клиент: {{ order.user_name }}</span>
                </div>
                <div class="flex items-center space-x-4 flex-shrink-0"> <!-- Добавлен flex-shrink-0 -->
                    <span class="font-semibold text-gray-800">{{ formatPrice(order.total_price) }} ₽</span>
                    <svg :class="{'rotate-180': expandedOrderId === order.id}"
                         class="w-5 h-5 text-gray-400 transition-transform duration-200" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            <!-- Детали заказа (раскрывающиеся) -->
            <div v-if="expandedOrderId === order.id" class="border-t border-gray-200 px-6 py-4 space-y-2">
                <p class="text-sm text-gray-600 mb-2">Клиент: {{ order.user_name }}</p>
                <h4 class="text-sm font-medium text-gray-600 mb-1">Состав заказа:</h4>
                <div v-for="item in order.items" :key="item.id" class="flex justify-between items-start text-sm pl-2">
                    <div class="flex-grow pr-2">
                        <span class="font-medium text-gray-800">{{ item.product_name }}</span>
                        <span v-if="item.quantity > 1" class="text-gray-500 ml-1">x{{ item.quantity }}</span>
                        <p v-if="item.options_description" class="text-xs text-gray-500 truncate"
                           :title="item.options_description">{{ item.options_description }}</p>
                    </div>
                    <div class="flex-shrink-0 font-medium text-gray-800">
                        {{ formatPrice(item.price_per_item * item.quantity) }} ₽
                    </div>
                </div>
                <!-- Кнопки действий -->
                <div class="pt-4 mt-2 border-t border-gray-100 flex justify-end space-x-3">
                    <button v-if="order.status === 'Preparing'"
                            @click="updateStatus(order.id, 'Ready')"
                            :disabled="loadingStatusChange[order.id]"
                            class="px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-md shadow-sm disabled:opacity-50">
                        {{ loadingStatusChange[order.id] ? '...' : 'Отметить готовым' }}
                    </button>
                    <button v-if="order.status === 'Ready'"
                            @click="updateStatus(order.id, 'Completed')"
                            :disabled="loadingStatusChange[order.id]"
                            class="px-4 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-md shadow-sm disabled:opacity-50">
                        {{ loadingStatusChange[order.id] ? '...' : 'Завершить (Выдан)' }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Пагинация -->
        <Pagination v-if="orders.links.length > 3" :links="orders.links" class="mt-6"/>
    </div>
    <div v-else class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
        Нет заказов в этой категории.
    </div>
</template>
