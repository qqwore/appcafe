<script>
// Используем Options API
import AdminLayout from '@/Layouts/AdminLayout.vue'; // Убедитесь, что путь к Layout правильный
import {Head, Link, router, useForm} from '@inertiajs/vue3';

export default {
    name: 'AdminMenuStockIndex',
    layout: AdminLayout,
    components: {Head, Link},
    props: {
        stockableProducts: {
            type: Array,
            default: () => []
        },
        // allProductsForSelect больше не нужен, если убрали форму для единичного добавления
        errors: Object,
        flash: Object, // $page.props.flash (содержит message, error, can_undo_stock_update)
    },
    data() {
        return {
            // Объект для хранения вводимых количеств для добавления
            // Ключ - ID продукта, значение - добавляемое количество (строка для v-model)
            quantitiesToAdd: {},
            isProcessing: false, // Флаг для кнопки "Применить изменения"
            // canUndo инициализируется из пропса flash при первой загрузке
            // и затем управляется таймером и ответами сервера
            canUndo: this.flash?.can_undo_stock_update || false,
            undoTimer: null, // ID таймера для кнопки "Отменить"
        };
    },
    watch: {
        // Следим за флагом can_undo_stock_update из flash-сообщений
        // Это в основном для случаев, когда страница перезагружается, а отмена еще возможна
        // или когда бэкенд принудительно сбрасывает флаг (например, после успешной отмены).
        '$page.props.flash.can_undo_stock_update': {
            handler(newValue, oldValue) {
                // console.log(`WATCH: $page.props.flash.can_undo_stock_update changed from ${oldValue} to ${newValue}`);
                if (newValue === true && !this.undoTimer && !this.canUndo) {
                    // Если флаг пришел true, а кнопка еще не активна и таймер не запущен
                    // (например, после обновления страницы)
                    this.canUndo = true;
                    this.startUndoTimer();
                } else if (newValue === false && this.canUndo) {
                    // Если бэкенд явно сказал, что отмена больше невозможна
                    this.canUndo = false;
                    clearTimeout(this.undoTimer);
                    this.undoTimer = null;
                }
            },
            deep: true, // Необходимо для отслеживания вложенных свойств
            immediate: true // Выполнить при монтировании
        }
    },
    methods: {
        // Инициализация quantitiesToAdd на основе текущих продуктов
        initializeQuantities() {
            const newQuantities = {};
            if (this.stockableProducts) {
                this.stockableProducts.forEach(product => {
                    newQuantities[product.id] = ''; // Изначально пустые поля для ввода
                });
            }
            this.quantitiesToAdd = newQuantities;
        },

        // Отправка формы массового обновления
        submitMultipleStockUpdate() {
            // Перед отправкой сбрасываем предыдущее состояние кнопки отмены, если оно было
            this.canUndo = false;
            clearTimeout(this.undoTimer);
            this.undoTimer = null;

            this.isProcessing = true;
            const updates = {};
            for (const productId in this.quantitiesToAdd) {
                const quantityStr = this.quantitiesToAdd[productId];
                // Проверяем, что строка не пустая, прежде чем парсить
                if (quantityStr !== null && quantityStr !== '') {
                    const quantity = parseInt(quantityStr);
                    if (!isNaN(quantity) && quantity > 0) {
                        updates[productId] = quantity;
                    } else if (!isNaN(quantity) && quantity < 0) {
                        alert(`Количество для товара (ID: ${productId}) не может быть отрицательным.`);
                        this.isProcessing = false;
                        return;
                    }
                    // Если quantity === 0 или не число (кроме пустой строки), оно просто не попадет в updates
                }
            }

            if (Object.keys(updates).length === 0) {
                alert('Не введено количество для добавления ни для одного товара (или введено 0).');
                this.isProcessing = false;
                return;
            }

            router.post(route('admin.menu-stock.updateMultiple'), {
                stock_updates: updates
            }, {
                preserveScroll: true,
                onSuccess: (page) => { // page содержит обновленные пропсы
                    this.initializeQuantities(); // Очищаем поля ввода
                    console.log('Multiple stock updated! New props:', page.props);
                    // Явно управляем состоянием кнопки отмены на основе ответа сервера
                    if (page.props.flash?.can_undo_stock_update) {
                        this.canUndo = true;
                        this.startUndoTimer();
                        console.log('onSuccess: Undo button enabled, timer started.');
                    } else {
                        this.canUndo = false; // Если сервер не установил флаг
                        clearTimeout(this.undoTimer); // На всякий случай
                        this.undoTimer = null;
                        console.log('onSuccess: Undo not available from backend (or no items were updated).');
                    }
                },
                onError: (errors) => {
                    console.error('Multiple stock update failed', errors);
                    // Ошибки валидации будут в $page.props.errors и обработаны в AdminLayout
                },
                onFinish: () => {
                    this.isProcessing = false;
                }
            });
        },

        // Отмена последнего обновления
        undoLastUpdate() {
            if (!this.canUndo) {
                console.warn('Undo button clicked but canUndo is false.');
                return;
            }
            this.isProcessing = true; // Можно использовать отдельный флаг, например, isUndoing
            router.post(route('admin.menu-stock.undoLast'), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Undo successful!');
                    // Бэкенд сбросит can_undo_stock_update в сессии.
                    // При следующем обновлении props, watch его подхватит и this.canUndo станет false.
                    this.canUndo = false; // Принудительно скрываем кнопку сразу
                    clearTimeout(this.undoTimer);
                    this.undoTimer = null;
                },
                onError: (errors) => {
                    console.error('Undo failed', errors);
                    this.canUndo = false; // Скрываем кнопку при ошибке
                    clearTimeout(this.undoTimer);
                    this.undoTimer = null;
                    // Ошибки из withErrors(['undo' => ...]) будут в $page.props.flash.error
                },
                onFinish: () => {
                    this.isProcessing = false;
                }
            });
        },

        // Запуск таймера для кнопки "Отменить"
        startUndoTimer() {
            clearTimeout(this.undoTimer); // Очищаем предыдущий, если есть
            console.log('Starting undo timer (15s) for undo button visibility.');
            this.undoTimer = setTimeout(() => {
                console.log('Undo timer expired. Hiding undo button on frontend.');
                this.canUndo = false;
                // Важно: это только скрывает кнопку на фронте.
                // Сервер все еще может позволить отмену, если запрос придет быстро после этого.
                // Но так как кнопка скрыта, пользователь не сможет нажать.
            }, 15000); // 15 секунд
        }
    },
    // Инициализируем поля ввода при создании компонента
    created() {
        this.initializeQuantities();
    },
    // Очищаем таймер при уходе со страницы
    beforeUnmount() {
        clearTimeout(this.undoTimer);
    }
}
</script>

<template>
    <Head title="Управление складом"/>
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-semibold text-gray-700">Наличие счётных продуктов</h1>
        <!-- Кнопка "Отменить" (появляется после обновления) -->
        <button v-if="canUndo"
                @click="undoLastUpdate"
                :disabled="isProcessing"
                class="px-5 py-2.5 bg-yellow-400 hover:bg-yellow-500 text-yellow-800 font-semibold rounded-md shadow-sm disabled:opacity-50 text-base">
            {{ isProcessing ? 'Отменяем...' : 'Отменить последнее обновление' }}
        </button>
    </div>

    <!-- Таблица текущего стока с полями для ввода -->
    <form @submit.prevent="submitMultipleStockUpdate">
        <div class="bg-white shadow rounded-lg overflow-x-auto"> <!-- Добавлен overflow-x-auto для таблицы -->
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider w-2/5">
                        Товар
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider w-1/5">
                        Кол-во, шт (тек.)
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider w-2/5">
                        Добавить приход, шт
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="!stockableProducts || stockableProducts.length === 0">
                    <td colspan="3" class="px-6 py-5 whitespace-nowrap text-base text-gray-500 text-center">Нет товаров
                        для отслеживания стока.
                    </td>
                </tr>
                <tr v-for="product in stockableProducts" :key="product.id">
                    <td class="px-6 py-4 whitespace-nowrap text-base font-medium text-gray-900">{{ product.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-base text-gray-700">{{ product.count ?? 0 }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-base text-gray-500">
                        <input type="number" min="0" max="999" placeholder="0"
                               v-model.number="quantitiesToAdd[product.id]"
                               class="w-24 border-gray-300 rounded-md shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-base">
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <!-- Кнопка "Применить изменения" -->
        <div class="mt-6 flex justify-end">
            <button type="submit"
                    :disabled="isProcessing"
                    class="px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-md shadow-sm disabled:opacity-50 text-lg">
                {{ isProcessing ? 'Сохранение...' : 'Применить изменения' }}
            </button>
        </div>
    </form>
</template>

<style scoped>
/* Можно добавить стили, если Tailwind классов недостаточно */
</style>
