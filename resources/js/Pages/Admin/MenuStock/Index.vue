<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';

export default {
    name: 'AdminMenuStockIndex',
    layout: AdminLayout,
    components: {Head, Link},
    props: {
        stockableProducts: Array, // Продукты с отслеживаемым стоком {id, name, count}
        allProductsForSelect: Array, // Все продукты для выпадающего списка {id, name}
        errors: Object,
    },
    data() {
        return {
            // Форма для добавления стока
            addStockForm: useForm({ // Используем useForm для удобства
                product_id: null,
                quantity_to_add: '',
            }),
            // Флаг загрузки для формы добавления
            isAddingStock: false,
        };
    },
    methods: {
        formatPrice(price) { /* ... как раньше ... */
        }, // Если нужно будет цена
        // Отправка формы добавления стока
        submitAddStock() {
            this.isAddingStock = true; // Блокируем кнопку
            this.addStockForm.patch(route('admin.menu-stock.add', {product: this.addStockForm.product_id}), {
                preserveScroll: true,
                onSuccess: () => {
                    this.addStockForm.reset('quantity_to_add'); // Сбрасываем только количество
                    console.log('Stock added!');
                },
                onError: () => {
                    // Ошибки валидации будут в $page.props.errors
                    console.error('Stock add failed');
                },
                onFinish: () => {
                    this.isAddingStock = false; // Разблокируем кнопку
                }
            });
        },
        // Метод для удаления (если нужен) - требует роута и контроллера
        // removeStockItem(productId) {
        //     if (confirm('Удалить этот товар из отслеживания стока? (Само наличие товара не удалится)')) {
        //         router.delete(route('admin.menu-stock.destroy', { product: productId }), {
        //             preserveScroll: true,
        //             onError: errors => alert('Ошибка удаления: ' + JSON.stringify(errors)),
        //         });
        //     }
        // }
    }
}
</script>


<template>
    <Head title="Меню на день" />
    <!-- Увеличиваем mb-6 до mb-8 -->
    <h1 class="text-2xl font-semibold text-gray-700 mb-8">Меню на день (Сток)</h1>

    <!-- Форма добавления стока -->
    <form @submit.prevent="submitAddStock" class="bg-white p-6 rounded-lg shadow mb-8">
        <!-- Увеличиваем mb-4 до mb-5 -->
        <h2 class="text-lg font-medium text-gray-800 mb-5">Добавить приход товара</h2>
        <div class="flex flex-col sm:flex-row items-end gap-4">
            <div class="flex-grow w-full sm:w-auto">
                <!-- Увеличиваем mb-1 до mb-1.5 -->
                <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1.5">Товар</label>
                <!-- Добавляем text-base -->
                <select id="product_id" v-model="addStockForm.product_id" required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-base">
                    <option :value="null" disabled>Выберите товар...</option>
                    <option v-for="product in allProductsForSelect" :key="product.id" :value="product.id">
                        {{ product.name }}
                    </option>
                </select>
                <div v-if="addStockForm.errors.product_id" class="text-red-500 text-xs mt-1">{{ addStockForm.errors.product_id }}</div>
            </div>
            <div class="w-full sm:w-32 flex-shrink-0">
                <!-- Увеличиваем mb-1 до mb-1.5 -->
                <label for="quantity_to_add" class="block text-sm font-medium text-gray-700 mb-1.5">Количество, шт</label>
                <!-- Добавляем text-base -->
                <input type="number" id="quantity_to_add" v-model="addStockForm.quantity_to_add" required min="1" max="999"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-base">
                <div v-if="addStockForm.errors.quantity_to_add" class="text-red-500 text-xs mt-1">{{ addStockForm.errors.quantity_to_add }}</div>
            </div>
            <div class="flex-shrink-0">
                <!-- Увеличиваем py-2 до py-2.5, добавляем text-base -->
                <button type="submit" :disabled="isAddingStock || !addStockForm.product_id || !addStockForm.quantity_to_add"
                        class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-md shadow-sm disabled:opacity-50 disabled:cursor-not-allowed text-base">
                    {{ isAddingStock ? '...' : 'Добавить' }}
                </button>
            </div>
        </div>
    </form>

    <!-- Таблица текущего стока -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <!-- Увеличиваем px-6 py-3 до px-6 py-4, text-xs до text-sm -->
                <th scope="col" class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                <th scope="col" class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Количество, шт</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="stockableProducts.length === 0">
                <!-- Увеличиваем py-4 до py-5, text-sm до text-base -->
                <td colspan="2" class="px-6 py-5 whitespace-nowrap text-base text-gray-500 text-center">Нет товаров для отслеживания стока.</td>
            </tr>
            <!-- Увеличиваем py-4 до py-5, text-sm до text-base -->
            <tr v-for="product in stockableProducts" :key="product.id">
                <td class="px-6 py-5 whitespace-nowrap text-base font-medium text-gray-900">{{ product.name }}</td>
                <td class="px-6 py-5 whitespace-nowrap text-base text-gray-500">{{ product.count ?? 0 }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
