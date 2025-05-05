<script>
// Используем Options API
import AdminLayout from '@/Layouts/AdminLayout.vue'; // Укажите правильный путь или alias
import {Head, Link, router} from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'; // Убедитесь, что этот компонент существует

export default {
    name: 'AdminOrdersIndex',
    layout: AdminLayout,
    components: {Head, Link, Pagination},
    props: {
        /**
         * Пагинированные данные заказов.
         * Структура: { data: [], links: [], meta: {...} }
         */
        orders: {
            type: Object,
            required: true,
        },
        /**
         * Идентификатор текущей активной вкладки ('new', 'ready', 'completed').
         */
        currentTab: {
            type: String,
            required: true,
        },
        /**
         * Массив доступных идентификаторов вкладок.
         */
        tabs: {
            type: Array,
            required: true,
        },
        /**
         * Объект с фильтрами, примененными на сервере (для сохранения состояния пагинации).
         */
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            /**
             * Отслеживание состояния загрузки для кнопок смены статуса.
             * Ключ - ID заказа, Значение - true/false.
             */
            loadingStatusChange: {},
            /**
             * Отображаемые имена для вкладок.
             */
            tabNames: {
                new: 'Новые',
                ready: 'Готовы к выдаче',
                completed: 'Выполненные'
            },
            /**
             * ID интервала для автоматического обновления вкладки "Новые".
             * Инициализируется в null.
             */
            pollingIntervalId: null,
        };
    },
    computed: {
        // Вычисляемые свойства здесь не требуются для основной логики,
        // но могут быть добавлены при необходимости (например, для форматирования).
    },
    methods: {
        /**
         * Возвращает CSS классы для отображения статуса заказа.
         * @param {string} status - Статус заказа ('Preparing', 'Ready', 'Completed' и т.д.).
         * @returns {string} - Строка с Tailwind классами.
         */
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

        /**
         * Возвращает текст статуса на русском языке.
         * @param {string} status - Статус заказа.
         * @returns {string} - Локализованный текст статуса.
         */
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

        /**
         * Форматирует цену (убирает копейки).
         * @param {number|string} price - Цена.
         * @returns {number|string} - Отформатированная цена.
         */
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },

        /**
         * Отправляет запрос на сервер для обновления статуса заказа.
         * @param {number} orderId - ID заказа.
         * @param {string} newStatus - Новый статус заказа.
         */
        updateStatus(orderId, newStatus) {
            this.loadingStatusChange[orderId] = true; // Блокируем кнопки для этого заказа
            router.patch(route('admin.orders.updateStatus', {order: orderId}),
                {status: newStatus}, // Данные, отправляемые на сервер
                {
                    preserveScroll: true, // Сохранить позицию прокрутки
                    preserveState: true, // Попытаться сохранить локальное состояние Vue (для раскрытия)
                    onSuccess: () => {
                        console.log(`Status updated successfully for order ${orderId}`);
                        // Сообщение об успехе будет показано через $page.props.flash.message
                    },
                    onError: (errors) => {
                        console.error('Order status update failed:', errors);
                        // Отображаем ошибку через $page.props.errors или alert
                        alert('Ошибка смены статуса: ' + JSON.stringify(errors));
                    },
                    onFinish: () => {
                        // Разблокируем кнопку в любом случае (успех или ошибка)
                        delete this.loadingStatusChange[orderId];
                    }
                }
            );
        },

        /**
         * Запрашивает обновление данных о заказах с сервера.
         * Используется для автоматического обновления вкладки "Новые".
         */
        fetchOrders() {
            // Проверяем, что мы все еще на вкладке 'new' перед запросом
            if (this.currentTab === 'new') {
                console.log('Polling for new orders...');
                router.reload({
                    only: ['orders'], // Запрашиваем только пропс 'orders'
                    preserveState: true, // Сохраняем состояние (фильтры, пагинацию)
                    preserveScroll: true, // Сохраняем прокрутку
                    onSuccess: () => {
                        console.log('Orders data reloaded via polling.');
                    },
                    onError: (errors) => {
                        console.error('Polling failed to reload orders:', errors);
                    }
                });
            } else {
                // Если вкладка сменилась, останавливаем поллинг (дополнительная проверка)
                this.stopPolling();
            }
        },

        /**
         * Запускает интервал для автоматического обновления данных.
         */
        startPolling() {
            // Останавливаем предыдущий интервал, если он был
            this.stopPolling();
            // Запускаем только если активна вкладка 'new'
            if (this.currentTab === 'new') {
                // Устанавливаем интервал и сохраняем его ID
                this.pollingIntervalId = setInterval(this.fetchOrders, 30000); // 30 секунд
                console.log('Order polling started (Interval ID:', this.pollingIntervalId, ')');
            }
        },

        /**
         * Останавливает интервал автоматического обновления.
         */
        stopPolling() {
            if (this.pollingIntervalId) {
                clearInterval(this.pollingIntervalId); // Очищаем интервал
                this.pollingIntervalId = null; // Сбрасываем ID
                console.log('Order polling stopped.');
            }
        },
    },
    watch: {
        /**
         * Следит за изменением активной вкладки.
         * Запускает/останавливает поллинг при необходимости.
         */
        currentTab(newTab, oldTab) {
            console.log(`Tab changed from ${oldTab} to ${newTab}`);
            // Если новая вкладка 'new', запускаем поллинг. Иначе - останавливаем.
            if (newTab === 'new') {
                this.startPolling();
            } else {
                this.stopPolling();
            }
        }
    },
    /**
     * Хук жизненного цикла: вызывается после монтирования компонента.
     */
    mounted() {
        console.log('Admin Orders Index mounted. Current tab:', this.currentTab);
        // Запускаем поллинг при первоначальной загрузке, если нужно
        this.startPolling();
    },
    /**
     * Хук жизненного цикла: вызывается перед уничтожением компонента.
     */
    beforeUnmount() {
        console.log('Admin Orders Index unmounting. Stopping polling.');
        // Обязательно останавливаем интервал при уходе со страницы
        this.stopPolling();
    }
}
</script>

<template>
    <Head title="Заказы"/>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-700">Заказы</h1>
        <!-- Кнопки для дополнительных действий (например, экспорт) -->
    </div>

    <!-- Табы для фильтрации заказов -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <!-- Используем Link для навигации между вкладками -->
            <Link v-for="tab in tabs" :key="tab"
                  :href="route('admin.orders.index', { tab: tab })"
                  :class="[
                      'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm',
                       currentTab === tab
                           ? 'border-emerald-500 text-emerald-600' // Стиль активной вкладки
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' // Стиль неактивной вкладки
                   ]"
                  preserve-state preserve-scroll> <!-- Сохраняем локальное состояние и прокрутку -->
                {{ tabNames[tab] || tab.charAt(0).toUpperCase() + tab.slice(1) }} <!-- Отображаем имя вкладки -->
            </Link>
        </nav>
    </div>

    <!-- Список Заказов -->
    <!-- Отображаем, если есть заказы в пагинации -->
    <div v-if="orders.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Итерация по заказам из пагинации -->
        <div v-for="order in orders.data" :key="order.id" class="bg-white shadow rounded-lg p-5">
            <!-- Краткая информация о заказе -->
            <div class="flex justify-between items-start mb-3">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <!-- Статус заказа -->
                        <span :class="getStatusClass(order.status)"
                              class="text-xs font-semibold px-2.5 py-0.5 rounded-full">{{
                                getStatusText(order.status)
                            }}</span>
                        <!-- Номер заказа -->
                        <span class="text-lg font-bold text-emerald-600">{{ order.number }}</span>
                    </div>
                    <!-- Дата создания -->
                    <span class="text-sm text-gray-500 block">от {{ order.created_date }}</span>
                    <!-- Имя клиента -->
                    <span class="text-sm text-gray-600 block">Клиент: {{ order.user_name }}</span>
                </div>
                <!-- Общая цена заказа -->
                <div class="text-right flex-shrink-0">
                    <span class="text-lg font-semibold text-gray-800">{{ formatPrice(order.total_price) }} ₽</span>
                </div>
            </div>
            <!-- Детали заказа (всегда видимы) -->
            <div class="border-t border-gray-200 pt-3 mt-3 space-y-2">
                <h4 class="text-sm font-medium text-gray-600 mb-1">Состав заказа:</h4>
                <!-- Итерация по элементам заказа -->
                <div v-for="item in order.items" :key="item.id" class="flex justify-between items-start text-sm pl-1">
                    <div class="flex-grow pr-2">
                        <!-- Название продукта и количество -->
                        <span class="font-medium text-gray-800">{{ item.product_name }}</span>
                        <span v-if="item.quantity > 1" class="text-gray-500 ml-1">x{{ item.quantity }}</span>
                        <!-- Описание опций (если есть) -->
                        <p v-if="item.options_description" class="text-xs text-gray-500 truncate"
                           :title="item.options_description">{{ item.options_description }}</p>
                    </div>
                    <!-- Цена за позицию -->
                    <div class="flex-shrink-0 font-medium text-gray-800 text-right">
                        {{ formatPrice(item.price_per_item * item.quantity) }} ₽
                    </div>
                </div>
                <!-- Кнопки действий для смены статуса -->
                <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end space-x-3">
                    <!-- Кнопка "Отметить готовым" (для статуса 'Preparing') -->
                    <button v-if="order.status === 'Preparing'"
                            @click="updateStatus(order.id, 'Ready')"
                            :disabled="loadingStatusChange[order.id]"
                            class="px-5 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-md shadow-sm disabled:opacity-50">
                        <!-- Индикатор загрузки или текст -->
                        {{ loadingStatusChange[order.id] ? 'Обновление...' : 'Отметить готовым' }}
                    </button>
                    <!-- Кнопка "Завершить (Выдан)" (для статуса 'Ready') -->
                    <button v-if="order.status === 'Ready'"
                            @click="updateStatus(order.id, 'Completed')"
                            :disabled="loadingStatusChange[order.id]"
                            class="px-5 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md shadow-sm disabled:opacity-50">
                        {{ loadingStatusChange[order.id] ? 'Завершение...' : 'Завершить (Выдан)' }}
                    </button>
                    <!-- Можно добавить кнопку "Отменить", если нужно -->
                </div>
            </div>
        </div> <!-- Конец карточки заказа -->

        <!-- Компонент Пагинации -->
        <!-- Отображаем, если ссылок больше чем "назад", "вперед" и текущая страница -->
        <Pagination v-if="orders.links.length > 3" :links="orders.links" class="mt-6 md:col-span-2 xl:col-span-3"/>

    </div> <!-- Конец сетки заказов -->

    <!-- Сообщение, если заказов нет -->
    <div v-else class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
        Нет заказов в этой категории.
    </div>
</template>

<style scoped>
/* Стили для Admin/Orders/Index */
</style>
