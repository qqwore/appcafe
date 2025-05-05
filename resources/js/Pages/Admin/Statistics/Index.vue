<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {Head} from '@inertiajs/vue3';

export default {
    name: 'AdminStatisticsIndex',
    layout: AdminLayout,
    components: {Head},
    props: {
        stats: {
            type: Object,
            default: () => ({
                orders_today: 0,
                orders_month: 0,
                users_today: 0,
                users_total: 0,
                products_total: 0,
            })
        }
    }
}
</script>

<template>
    <Head title="Статистика"/>
    <div>
        <h1 class="text-2xl font-semibold text-gray-700 mb-6">Статистика</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Карточка Заказы -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-sm font-medium text-gray-500 mb-1">Заказы</h2>
                <p class="text-3xl font-semibold text-gray-900">{{ stats.orders_month }} <span
                    class="text-base font-normal text-gray-500">за месяц</span></p>
                <p v-if="stats.orders_today > 0" class="text-sm font-medium text-emerald-600 mt-1">
                    +{{ stats.orders_today }} сегодня</p>
                <p v-else class="text-sm text-gray-500 mt-1">Сегодня нет новых заказов</p>
            </div>

            <!-- Карточка Пользователи -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-sm font-medium text-gray-500 mb-1">Пользователи</h2>
                <p class="text-3xl font-semibold text-gray-900">{{ stats.users_total }} <span
                    class="text-base font-normal text-gray-500">всего</span></p>
                <p v-if="stats.users_today > 0" class="text-sm font-medium text-emerald-600 mt-1">+{{
                        stats.users_today
                    }} сегодня</p>
                <p v-else class="text-sm text-gray-500 mt-1">Сегодня нет новых пользователей</p>
            </div>

            <!-- Карточка Товары -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-sm font-medium text-gray-500 mb-1">Товары</h2>
                <p class="text-3xl font-semibold text-gray-900">{{ stats.products_total }} <span
                    class="text-base font-normal text-gray-500">уник. названий</span></p>
                <p class="text-sm text-gray-500 mt-1"> </p> <!-- Пустая строка для выравнивания -->
            </div>
        </div>
    </div>
</template>
