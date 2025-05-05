Vladislav, [05.05.2025 19:14]
<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3'; // Добавили Link

export default {
    name: 'AdminDashboard',
    layout: AdminLayout,
    components: { Head, Link }, // Добавили Link
}
</script>

<template>
    <Head title="Админ-панель" />
    <div>
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Добро пожаловать в Админ-панель!</h1>
        <p class="text-gray-600">Выберите раздел в меню слева или перейдите к <Link :href="route('admin.statistics.index')" class="text-emerald-600 hover:underline">статистике</Link>.</p>
        <!-- Сюда можно добавить какие-то виджеты позже -->
    </div>
</template>
