<script>
// Используем Options API
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
// --- ИМПОРТИРУЕМ КОМПОНЕНТ ГРАФИКА ---
import {Line as LineChart} from 'vue-chartjs';

export default {
    name: 'AdminStatisticsIndex',
    layout: AdminLayout,
    components: {
        Head,
        Link,
        LineChart, // Регистрируем компонент графика
    },
    props: {
        generalStats: {
            type: Object,
            default: () => ({}),
        },
        productStats: {
            type: Object,
            default: () => ({}),
        },
        orderCharts: { // { daily_revenue: {labels, data}, monthly_revenue: {...}, ... }
            type: Object,
            default: () => ({
                daily_revenue: {labels: [], data: []},
                monthly_revenue: {labels: [], data: []},
                daily_orders: {labels: [], data: []},
                monthly_orders: {labels: [], data: []},
                peak_hours: [],
                peak_days: [],
            }),
        },
        clientStats: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            activeTab: 'summary', // 'summary', 'orders', 'products', 'clients'
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            },
        };
    },
    computed: {
        dailyRevenueChartData() {
            return {
                labels: this.orderCharts?.daily_revenue?.labels || [],
                datasets: [
                    {
                        label: 'Выручка, ₽',
                        data: this.orderCharts?.daily_revenue?.data || [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true,
                    },
                ],
            };
        },
        dailyOrdersChartData() {
            return {
                labels: this.orderCharts?.daily_orders?.labels || [],
                datasets: [
                    {
                        label: 'Заказы, шт',
                        data: this.orderCharts?.daily_orders?.data || [],
                        borderColor: 'rgb(255, 159, 64)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        tension: 0.1,
                        fill: true,
                    },
                ],
            };
        },
        monthlyRevenueChartData() {
            return {
                labels: this.orderCharts?.monthly_revenue?.labels || [],
                datasets: [
                    {
                        label: 'Выручка, ₽',
                        data: this.orderCharts?.monthly_revenue?.data || [],
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1,
                        fill: true,
                    },
                ],
            };
        },
        monthlyOrdersChartData() {
            return {
                labels: this.orderCharts?.monthly_orders?.labels || [],
                datasets: [
                    {
                        label: 'Заказы, шт',
                        data: this.orderCharts?.monthly_orders?.data || [],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1,
                        fill: true,
                    },
                ],
            };
        }
    },
    methods: {
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },
        setTab(tabName) {
            this.activeTab = tabName;
        }
    }
}
</script>

<template>
    <Head title="Статистика"/>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-semibold text-gray-800">Статистика</h1>
        </div>

        <!-- Табы для навигации по статистике -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 overflow-x-auto pb-px" aria-label="Tabs">
                <button @click="setTab('summary')"
                        :class="[activeTab === 'summary' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-base']">
                    Сводка
                </button>
                <button @click="setTab('orders')"
                        :class="[activeTab === 'orders' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-base']">
                    Заказы
                </button>
                <button @click="setTab('products')"
                        :class="[activeTab === 'products' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-base']">
                    Товары
                </button>
                <button @click="setTab('clients')"
                        :class="[activeTab === 'clients' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-base']">
                    Клиенты
                </button>
            </nav>
        </div>

        <!-- Содержимое вкладок -->
        <div id="stats-content">

            <!-- ВКЛАДКА: СВОДКА -->
            <section v-if="activeTab === 'summary'" class="space-y-8">
                <h2 class="text-xl font-semibold text-gray-700">Общая сводка</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Заказы
                        (всего)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{
                                generalStats.orders_total_all_time || 0
                            }}</p></div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Заказы
                        (сегодня)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{ generalStats.orders_today || 0 }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Заказы
                        (неделя)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{
                                generalStats.orders_this_week || 0
                            }}</p></div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Заказы
                        (месяц)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{
                                generalStats.orders_this_month || 0
                            }}</p></div>

                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Выручка
                        (сегодня)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{
                                formatPrice(generalStats.revenue_today)
                            }} ₽</p></div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Выручка
                        (неделя)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">
                            {{ formatPrice(generalStats.revenue_this_week) }} ₽</p></div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Выручка
                        (месяц)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">
                            {{ formatPrice(generalStats.revenue_this_month) }} ₽</p></div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Средний
                        чек (месяц)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">
                            {{ formatPrice(generalStats.average_check_this_month) }} ₽</p></div>

                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">
                        Пользователи (всего)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{ generalStats.users_total || 0 }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Новых
                        пользователей (сегодня)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{ generalStats.users_today || 0 }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Новых
                        пользователей (неделя)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{ generalStats.users_this_week || 0 }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Новых
                        пользователей (месяц)</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{
                                generalStats.users_this_month || 0
                            }}</p></div>

                    <div class="bg-white p-6 rounded-lg shadow md:col-span-2 lg:col-span-4"><h3
                        class="text-sm font-medium text-gray-500">Уникальных товаров в меню</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{
                                generalStats.unique_products_count || 0
                            }}</p></div>
                </div>
            </section>

            <!-- ВКЛАДКА: ЗАКАЗЫ (графики, пики) -->
            <section v-if="activeTab === 'orders'" class="space-y-8">
                <h2 class="text-xl font-semibold text-gray-700">Аналитика Заказов</h2>
                <!-- Графики -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-md font-medium text-gray-600 mb-3">Выручка по дням (30 дн.), ₽</h3>
                        <div class="h-72 relative">
                            <LineChart v-if="orderCharts?.daily_revenue?.labels.length" :data="dailyRevenueChartData"
                                       :options="chartOptions"/>
                            <p v-else class="text-center text-gray-500 pt-20">Нет данных для графика.</p>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-md font-medium text-gray-600 mb-3">Кол-во заказов по дням (30 дн.), шт</h3>
                        <div class="h-72 relative">
                            <LineChart v-if="orderCharts?.daily_orders?.labels.length" :data="dailyOrdersChartData"
                                       :options="chartOptions"/>
                            <p v-else class="text-center text-gray-500 pt-20">Нет данных для графика.</p>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-md font-medium text-gray-600 mb-3">Выручка по месяцам (год), ₽</h3>
                        <div class="h-72 relative">
                            <LineChart v-if="orderCharts?.monthly_revenue?.labels.length"
                                       :data="monthlyRevenueChartData" :options="chartOptions"/>
                            <p v-else class="text-center text-gray-500 pt-20">Нет данных для графика.</p>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-md font-medium text-gray-600 mb-3">Кол-во заказов по месяцам (год), шт</h3>
                        <div class="h-72 relative">
                            <LineChart v-if="orderCharts?.monthly_orders?.labels.length" :data="monthlyOrdersChartData"
                                       :options="chartOptions"/>
                            <p v-else class="text-center text-gray-500 pt-20">Нет данных для графика.</p>
                        </div>
                    </div>
                </div>
                <!-- Пиковые часы и дни -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-md font-semibold text-gray-700 mb-3">Пиковые часы (заказы за 30 дн.)</h3>
                        <ul v-if="orderCharts.peak_hours && orderCharts.peak_hours.length" class="space-y-1 text-sm">
                            <li v-for="peak in orderCharts.peak_hours" :key="peak.hour" class="flex justify-between">
                                <span>{{ peak.hour }}:00 - {{ parseInt(peak.hour) + 1 }}:00</span> <span
                                class="font-medium">{{ peak.count }} зак.</span></li>
                        </ul>
                        <p v-else class="text-sm text-gray-500">Нет данных.</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-md font-semibold text-gray-700 mb-3">Пиковые дни недели (заказы за 30 дн.)</h3>
                        <ul v-if="orderCharts.peak_days && orderCharts.peak_days.length" class="space-y-1 text-sm">
                            <li v-for="peak in orderCharts.peak_days" :key="peak.day_iso" class="flex justify-between">
                                <span>{{ peak.day_name }}</span> <span class="font-medium">{{ peak.count }} зак.</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500">Нет данных.</p>
                    </div>
                </div>
            </section>

            <!-- ВКЛАДКА: ТОВАРЫ (популярные, категории, допы) -->
            <section v-if="activeTab === 'products'" class="space-y-8">
                <h2 class="text-xl font-semibold text-gray-700">Аналитика Товаров</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-md font-semibold text-gray-700 mb-3">Топ
                        продаваемых (месяц)</h3>
                        <ul v-if="productStats.top_sold_month && productStats.top_sold_month.length > 0"
                            class="space-y-2 text-sm">
                            <li v-for="(item, index) in productStats.top_sold_month" :key="item.product_id"
                                class="flex justify-between"><span>{{ index + 1 }}. {{
                                    item.product?.name || 'N/A'
                                }}</span><span class="font-medium">{{ item.total_sold }} шт.</span></li>
                        </ul>
                        <p v-else class="text-sm text-gray-500">Нет данных.</p></div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-md font-semibold text-gray-700 mb-3">Топ
                        по выручке (месяц)</h3>
                        <ul v-if="productStats.top_revenue_month && productStats.top_revenue_month.length > 0"
                            class="space-y-2 text-sm">
                            <li v-for="(item, index) in productStats.top_revenue_month" :key="item.product_id"
                                class="flex justify-between"><span>{{ index + 1 }}. {{
                                    item.product?.name || 'N/A'
                                }}</span><span class="font-medium">{{ formatPrice(item.total_revenue) }} ₽</span></li>
                        </ul>
                        <p v-else class="text-sm text-gray-500">Нет данных.</p></div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-md font-semibold text-gray-700 mb-3">Популярные допы</h3>
                        <div v-if="productStats.popular_milk && productStats.popular_milk.length > 0" class="mb-2"><h4
                            class="text-xs font-medium text-gray-500 uppercase mb-1">Молоко:</h4>
                            <ul class="space-y-1 text-sm">
                                <li v-for="extra in productStats.popular_milk" :key="extra.milk_extra_id"
                                    class="flex justify-between"><span>{{ extra.milk_extra?.name || 'N/A' }}</span><span
                                    class="font-medium">{{ extra.count }} раз</span></li>
                            </ul>
                        </div>
                        <div v-if="productStats.popular_syrup && productStats.popular_syrup.length > 0"><h4
                            class="text-xs font-medium text-gray-500 uppercase mb-1">Сиропы:</h4>
                            <ul class="space-y-1 text-sm">
                                <li v-for="extra in productStats.popular_syrup" :key="extra.syrup_extra_id"
                                    class="flex justify-between"><span>{{
                                        extra.syrup_extra?.name || 'N/A'
                                    }}</span><span class="font-medium">{{ extra.count }} раз</span></li>
                            </ul>
                        </div>
                        <p v-if="(!productStats.popular_milk || productStats.popular_milk.length === 0) && (!productStats.popular_syrup || productStats.popular_syrup.length === 0)"
                           class="text-sm text-gray-500">Нет данных по допам.</p>
                    </div>
                </div>
                <div class="mt-6 bg-white p-6 rounded-lg shadow">
                    <h3 class="text-md font-semibold text-gray-700 mb-3">Статистика по категориям (выручка за
                        месяц)</h3>
                    <ul v-if="productStats.category_stats_month && productStats.category_stats_month.length > 0"
                        class="space-y-1 text-sm">
                        <li v-for="cat in productStats.category_stats_month" :key="cat.category_name"
                            class="flex justify-between">
                            <span>{{ cat.category_name }}</span>
                            <span class="font-medium">{{ formatPrice(cat.total_revenue) }} ₽ ({{
                                    cat.total_sold
                                }} шт.)</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">Нет данных.</p>
                </div>
            </section>

            <!-- ВКЛАДКА: КЛИЕНТЫ -->
            <section v-if="activeTab === 'clients'" class="space-y-8">
                <h2 class="text-xl font-semibold text-gray-700">Аналитика Клиентов</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Среднее
                        кол-во заказов</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{ clientStats.avg_orders_per_user || 0 }}
                            <span class="text-sm font-normal">на клиента</span></p></div>
                    <div class="bg-white p-6 rounded-lg shadow"><h3 class="text-sm font-medium text-gray-500">Повторные
                        заказы</h3>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">
                            {{ formatPrice(clientStats.repeat_order_rate) }} %</p>
                        <p class="text-xs text-gray-500">клиентов с >1 заказом</p></div>
                </div>
                <div class="mt-6 bg-white p-6 rounded-lg shadow">
                    <h3 class="text-md font-semibold text-gray-700 mb-3">Топ клиенты (потрачено за месяц)</h3>
                    <ul v-if="clientStats.top_clients_month && clientStats.top_clients_month.length > 0"
                        class="space-y-2 text-sm">
                        <li v-for="(client, index) in clientStats.top_clients_month" :key="client.user_id"
                            class="flex justify-between">
                            <span>{{ index + 1 }}. {{ client.user?.name || 'Клиент #' + client.user_id }}</span>
                            <span class="font-medium">{{ formatPrice(client.total_spent) }} ₽</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">Нет данных.</p>
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
/* Стили только для этой страницы, если Tailwind не справляется */
</style>
