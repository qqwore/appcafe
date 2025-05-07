<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'; // Импортируем пагинацию

export default {
    name: 'AdminOrdersIndex',
    layout: AdminLayout,
    components: {Head, Link, Pagination},
    props: {
        orders: Object, // Пагинированные данные ИЛИ null
        searchedOrder: Object, // Найденный заказ ИЛИ null
        currentTab: String,
        tabs: Array,
        filters: Object, // { tab: '...', search_query: '...' }
    },
    data() {
        return {
            loadingStatusChange: {},
            tabNames: {
                new: 'Новые',
                ready: 'Готовы к выдаче',
                completed: 'Выполненные',
                search: 'Поиск заказа'
            },
            searchForm: useForm({ // Используем useForm для формы поиска
                query: this.filters.search_query || '',
            }),
            pollingIntervalId: null,
        };
    },
    methods: {
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
                    return 'Готов к выдаче';
                case 'completed':
                    return 'Завершен';
                case 'received':
                    return 'Выдан';
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
            this.loadingStatusChange[orderId] = true;
            router.patch(route('admin.orders.updateStatus', {order: orderId}),
                {status: newStatus},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => console.log(`Status updated for order ${orderId}`),
                    onError: (errors) => alert('Ошибка смены статуса: ' + JSON.stringify(errors)),
                    onFinish: () => {
                        delete this.loadingStatusChange[orderId];
                    }
                }
            );
        },
        fetchOrders() {
            if (this.currentTab === 'new') { // Обновляем только вкладку "Новые"
                console.log('Polling for new orders...');
                router.reload({
                    only: ['orders'], // Перезапрашиваем только пропс orders
                    data: {tab: 'new', search_query: this.filters.search_query}, // Передаем текущие фильтры
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        },
        startPolling() {
            this.stopPolling();
            if (this.currentTab === 'new') {
                this.pollingIntervalId = setInterval(this.fetchOrders, 30000);
                console.log('Order polling started (Interval ID:', this.pollingIntervalId, ')');
            }
        },
        stopPolling() {
            if (this.pollingIntervalId) {
                clearInterval(this.pollingIntervalId);
                this.pollingIntervalId = null;
                console.log('Order polling stopped.');
            }
        },

        submitSearch() {
            router.get(route('admin.orders.index'), { // Передаем параметры как второй аргумент router.get
                tab: 'search',
                search_query: this.searchForm.query // Явно указываем ключ search_query
            }, {
                preserveState: true,
                preserveScroll: true,
                replace: true
            });
        },
    },
    watch: {
        currentTab(newTab) {
            if (newTab === 'new') {
                this.startPolling();
            } else {
                this.stopPolling();
            }
            // Если переключились на вкладку, не являющуюся поиском, и был поисковый запрос,
            // то можно его очистить из URL, если не передаем его в Link
            if (newTab !== 'search' && this.filters.search_query) {
                // router.get(route('admin.orders.index', { tab: newTab }), {}, { preserveState: true, replace: true });
            }
        },
        'filters.search_query': function (newQuery) {
            this.searchForm.query = newQuery || '';
        }
    },
    mounted() {
        this.startPolling();
        // Если при загрузке страницы currentTab = 'search' и есть filters.search_query,
        // а searchedOrder пуст, возможно, стоит выполнить поиск (но контроллер уже это делает)
    },
    beforeUnmount() {
        this.stopPolling();
    }
}
</script>

<template>
    <Head title="Заказы"/>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-700">Заказы</h1>
    </div>

    <!-- Табы -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <Link v-for="tabKey in tabs" :key="tabKey"
                  :href="route('admin.orders.index', { tab: tabKey })"
                  :class="[
                      'whitespace-nowrap py-3.5 px-2 border-b-2 font-medium text-base', // ИЗМЕНЕНО: text-sm -> text-base, py-3 px-1 -> py-3.5 px-2
                       currentTab === tabKey ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                   ]"
                  preserve-state preserve-scroll>
                {{ tabNames[tabKey] || tabKey }}
            </Link>
        </nav>
    </div>

    <!-- Блок для вкладки "Поиск заказа" -->
    <div v-if="currentTab === 'search'">
        <form @submit.prevent="submitSearch" class="mb-8 bg-white p-6 rounded-lg shadow">
            <!-- Увеличили mb-6 до mb-8, p-4 до p-6 -->
            <div class="flex flex-col sm:flex-row items-end space-y-3 sm:space-y-0 sm:space-x-4">
                <!-- Увеличили space-x-3 до space-x-4 -->
                <div class="flex-grow w-full">
                    <!-- Увеличили mb-1 до mb-1.5, text-sm до text-base -->
                    <label for="search_query" class="block text-base font-medium text-gray-700 mb-1.5">Номер
                        заказа</label>
                    <!-- Увеличили text-base, добавили padding py-2.5 -->
                    <input type="text" id="search_query" v-model="searchForm.query"
                           placeholder="Введите номер заказа, например, 122"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-base py-2.5">
                </div>
                <!-- Увеличили px-5 py-2.5 до px-6 py-3, text-base до text-lg -->
                <button type="submit"
                        :disabled="searchForm.processing || !searchForm.query"
                        class="px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-md shadow-sm disabled:opacity-50 text-lg w-full sm:w-auto">
                    Найти
                </button>
            </div>
            <div v-if="searchForm.errors.query" class="text-red-500 text-sm mt-1">{{ searchForm.errors.query }}</div>
            <!-- Ошибку оставим text-sm или text-xs -->
        </form>

        <!-- Отображение найденного заказа -->
        <div v-if="searchedOrder" class="bg-white shadow rounded-lg p-5">
            <div class="flex justify-between items-start mb-3">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span :class="getStatusClass(searchedOrder.status)"
                              class="text-xs font-semibold px-2.5 py-0.5 rounded-full">{{
                                getStatusText(searchedOrder.status)
                            }}</span>
                        <span class="text-lg font-bold text-emerald-600">{{ searchedOrder.number }}</span>
                    </div>
                    <span class="text-sm text-gray-500 block">от {{ searchedOrder.created_date }}</span>
                    <span class="text-sm text-gray-600 block">Клиент: {{ searchedOrder.user_name }}</span>
                </div>
                <div class="text-right flex-shrink-0">
                    <span class="text-lg font-semibold text-gray-800">{{
                            formatPrice(searchedOrder.total_price)
                        }} ₽</span>
                </div>
            </div>
            <div class="border-t border-gray-200 pt-3 mt-3 space-y-2">
                <h4 class="text-sm font-medium text-gray-600 mb-1">Состав заказа:</h4>
                <div v-for="item in searchedOrder.items" :key="item.id"
                     class="flex justify-between items-start text-sm pl-1">
                    <div class="flex-grow pr-2">
                        <span class="font-medium text-gray-800">{{ item.product_name }}</span>
                        <span v-if="item.quantity > 1" class="text-gray-500 ml-1">x{{ item.quantity }}</span>
                        <p v-if="item.options_description" class="text-xs text-gray-500 truncate"
                           :title="item.options_description">{{ item.options_description }}</p>
                    </div>
                    <div class="flex-shrink-0 font-medium text-gray-800 text-right">
                        {{ formatPrice(item.price_per_item * item.quantity) }} ₽
                    </div>
                </div>
                <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end space-x-3">
                    <button v-if="searchedOrder.status === 'Preparing'" @click="updateStatus(searchedOrder.id, 'Ready')"
                            :disabled="loadingStatusChange[searchedOrder.id]"
                            class="px-5 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-md shadow-sm disabled:opacity-50">
                        {{ loadingStatusChange[searchedOrder.id] ? '...' : 'Отметить готовым' }}
                    </button>
                    <button v-if="searchedOrder.status === 'Ready'" @click="updateStatus(searchedOrder.id, 'Completed')"
                            :disabled="loadingStatusChange[searchedOrder.id]"
                            class="px-5 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md shadow-sm disabled:opacity-50">
                        {{ loadingStatusChange[searchedOrder.id] ? '...' : 'Завершить (Выдан)' }}
                    </button>
                </div>
            </div>
        </div>
        <div v-else-if="filters.search_query && !searchedOrder && !searchForm.processing"
             class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
            Заказ с номером "{{ filters.search_query }}" не найден.
        </div>
    </div>

    <!-- Список заказов для вкладок 'new', 'ready', 'completed' -->
    <template v-else>
        <div v-if="orders && orders.data && orders.data.length > 0"
             class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div v-for="order in orders.data" :key="order.id" class="bg-white shadow rounded-lg p-5">
                <div class="flex justify-between items-start mb-3">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span :class="getStatusClass(order.status)"
                                  class="text-xs font-semibold px-2.5 py-0.5 rounded-full">{{
                                    getStatusText(order.status)
                                }}</span>
                            <span class="text-lg font-bold text-emerald-600">{{ order.number }}</span>
                        </div>
                        <span class="text-sm text-gray-500 block">от {{ order.created_date }}</span>
                        <span class="text-sm text-gray-600 block">Клиент: {{ order.user_name }}</span>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-lg font-semibold text-gray-800">{{ formatPrice(order.total_price) }} ₽</span>
                    </div>
                </div>
                <div class="border-t border-gray-200 pt-3 mt-3 space-y-2">
                    <h4 class="text-sm font-medium text-gray-600 mb-1">Состав заказа:</h4>
                    <div v-for="item in order.items" :key="item.id"
                         class="flex justify-between items-start text-sm pl-1">
                        <div class="flex-grow pr-2">
                            <span class="font-medium text-gray-800">{{ item.product_name }}</span>
                            <span v-if="item.quantity > 1" class="text-gray-500 ml-1">x{{ item.quantity }}</span>
                            <p v-if="item.options_description" class="text-xs text-gray-500 truncate"
                               :title="item.options_description">{{ item.options_description }}</p>
                        </div>
                        <div class="flex-shrink-0 font-medium text-gray-800 text-right">
                            {{ formatPrice(item.price_per_item * item.quantity) }} ₽
                        </div>
                    </div>
                    <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end space-x-3">
                        <button v-if="order.status === 'Preparing'" @click="updateStatus(order.id, 'Ready')"
                                :disabled="loadingStatusChange[order.id]"
                                class="px-5 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-md shadow-sm disabled:opacity-50">
                            {{ loadingStatusChange[order.id] ? '...' : 'Отметить готовым' }}
                        </button>
                        <button v-if="order.status === 'Ready'" @click="updateStatus(order.id, 'Completed')"
                                :disabled="loadingStatusChange[order.id]"
                                class="px-5 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md shadow-sm disabled:opacity-50">
                            {{ loadingStatusChange[order.id] ? '...' : 'Завершить (Выдан)' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
            Нет заказов в этой категории.
        </div>
    </template>
</template>

<style scoped>
/* Стили для AdminOrdersIndex */
</style>
