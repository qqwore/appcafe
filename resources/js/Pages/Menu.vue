<script>
// Используем Options API
import AppLayout from '../Layouts/AppLayout.vue';
import {router} from '@inertiajs/vue3'; // Импортируем router для запросов

export default {
    name: 'MenuPage',
    layout: AppLayout,
    // Head компонент можно добавить, если нужен title для страницы
    // components: { Head },
    props: {
        // Данные продуктов, сгруппированные по ID категории
        productsByCategory: {
            type: Object,
            default: () => ({})
        },
        // Массив всех категорий {id: number, name: string}
        categories: {
            type: Array,
            default: () => []
        },
        // Опционально: детальные данные корзины (если не хотите полагаться только на $page)
        // cartDetailedItems: {
        //   type: Array,
        //   default: () => []
        // }
    },
    data() {
        return {
            // ID желаемого порядка категорий
            categoryOrder: [3, 2, 1, 4], // 3:Кофе, 2:Не кофе->Напитки, 1:Сытная еда, 4:Десерты
            // ID выбранной категории (установим в mounted)
            selectedCategoryId: null,
            // Отслеживание состояния загрузки для кнопок
            loadingItems: {}, // { display_id: true/false }
        };
    },
    computed: {
        /**
         * Возвращает категории в заданном порядке и переименовывает "Не кофе".
         */
        sortedCategories() {
            const orderedCategories = [];
            const categoryMap = new Map(this.categories.map(cat => [cat.id, {...cat}]));
            this.categoryOrder.forEach(id => {
                const category = categoryMap.get(id);
                if (category) {
                    if (category.id === 2) category.name = 'Напитки';
                    orderedCategories.push(category);
                    categoryMap.delete(id);
                }
            });
            categoryMap.forEach(category => orderedCategories.push(category));
            return orderedCategories;
        },

        /**
         * Возвращает продукты только для выбранной категории.
         */
        filteredProducts() {
            if (!this.selectedCategoryId || !this.productsByCategory || !this.productsByCategory[this.selectedCategoryId]) {
                return [];
            }
            return this.productsByCategory[this.selectedCategoryId].products;
        },

        /**
         * Возвращает имя выбранной категории.
         */
        selectedCategoryName() {
            if (!this.selectedCategoryId || !this.sortedCategories) return 'Меню';
            const selectedCategory = this.sortedCategories.find(cat => cat.id === this.selectedCategoryId);
            return selectedCategory ? selectedCategory.name : 'Меню';
        },

        /**
         * Карта товаров, которые уже есть в корзине.
         * Использует $page.props.cart_details, убедитесь, что они передаются.
         */
        cartItemsMap() {
            const map = {};
            // Используем $page.props.cart_details или cartDetailedItems, если передаете пропсом
            const pageCartItems = this.$page.props.cart_details?.items || [];
            pageCartItems.forEach(item => {
                if (item.product?.id) { // Проверка на наличие product.id
                    map[item.product.id] = {
                        cart_item_id: item.id, // ID строки в таблице carts
                        quantity: item.quantity
                    };
                }
            });
            return map;
        }
    },
    methods: {
        /**
         * Выбирает категорию.
         */
        selectCategory(categoryId) {
            this.selectedCategoryId = categoryId;
            // Не нужно сбрасывать productQuantities, т.к. оно больше не используется локально
        },

        /**
         * Форматирует цену.
         */
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },

        /**
         * Проверяет, есть ли товар в корзине.
         */
        isInCart(displayId) {
            return !!this.cartItemsMap[displayId];
        },

        /**
         * Возвращает количество из корзины.
         */
        getCartQuantity(displayId) {
            return this.cartItemsMap[displayId]?.quantity ?? 0;
        },

        /**
         * Добавляет 1 штуку товара в корзину.
         */
        addItemToCart(displayId) {
            // Используем прямое присваивание для loadingItems
            this.loadingItems[displayId] = true;
            console.log(`Adding 1 unit of product ${displayId} to cart.`);
            router.post(route('cart.add'), {
                product_id: displayId,
                quantity: 1,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Product added!');
                },
                onError: errors => {
                    console.error('Failed to add product to cart:', errors);
                    alert('Не удалось добавить товар. Ошибка: ' + JSON.stringify(errors));
                },
                onFinish: () => {
                    // Используем delete для loadingItems
                    delete this.loadingItems[displayId];
                }
            });
        },

        /**
         * Увеличивает количество товара В КОРЗИНЕ.
         */
        increaseQuantity(displayId) {
            const currentItem = this.cartItemsMap[displayId];
            if (!currentItem) return;
            const newQuantity = currentItem.quantity + 1;

            if (newQuantity <= 10) {
                this.loadingItems[displayId] = true; // Блокируем
                router.patch(route('cart.update', {cart_item: currentItem.cart_item_id}), {
                    quantity: newQuantity,
                }, {
                    preserveScroll: true,
                    preserveState: true, // Можно оставить true, т.к. меняем существующий
                    onSuccess: () => console.log(`Quantity updated to ${newQuantity}`),
                    onError: errors => console.error('Quantity update failed:', errors),
                    onFinish: () => {
                        delete this.loadingItems[displayId];
                    } // Разблокируем
                });
            } else {
                console.warn(`Cannot add more than 10 items for product ID: ${displayId}`);
            }
        },

        /**
         * Уменьшает количество товара В КОРЗИНЕ. Если 0 - удаляет.
         */
        decreaseQuantity(displayId) {
            const currentItem = this.cartItemsMap[displayId];
            if (!currentItem) return;
            const newQuantity = currentItem.quantity - 1;

            if (newQuantity >= 1) {
                this.loadingItems[displayId] = true; // Блокируем
                router.patch(route('cart.update', {cart_item: currentItem.cart_item_id}), {
                    quantity: newQuantity,
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => console.log(`Quantity updated to ${newQuantity}`),
                    onError: errors => console.error('Quantity update failed:', errors),
                    onFinish: () => {
                        delete this.loadingItems[displayId];
                    } // Разблокируем
                });
            } else {
                // Если уменьшаем до нуля - вызываем удаление
                this.removeItem(currentItem.cart_item_id, displayId);
            }
        },

        /**
         * Удаляет товар из корзины.
         */
        removeItem(cartItemId, displayId) {
            if (!cartItemId) {
                console.error("Cannot remove item, cart_item_id is missing for displayId:", displayId);
                return;
            }
            this.loadingItems[displayId] = true; // Блокируем
            router.delete(route('cart.destroy', {cart_item: cartItemId}), {
                preserveScroll: true,
                onSuccess: () => {
                    console.log(`Item ${cartItemId} removed`);
                },
                onError: errors => {
                    console.error('Item remove failed:', errors);
                    alert('Не удалось удалить товар. Ошибка: ' + JSON.stringify(errors));
                },
                onFinish: () => {
                    delete this.loadingItems[displayId];
                } // Разблокируем
            });
        },
    },
    /**
     * Хук жизненного цикла Options API.
     */
    mounted() {
        // Устанавливаем категорию по умолчанию
        if (this.sortedCategories.length > 0) {
            // Проверяем, есть ли уже выбранная категория (например, после F5)
            // Если нет, устанавливаем первую из отсортированного списка
            if (!this.selectedCategoryId) {
                this.selectedCategoryId = this.sortedCategories[0].id;
            }
        }
        console.log("Initial cart map:", this.cartItemsMap); // Отладка
        console.log("Page Props:", this.$page.props); // Отладка
    }
}
</script>

<template>
    <!-- <Head title="Меню" /> Используйте, если нужно -->
    <div class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Кнопки Фильтров Категорий -->
            <div class="flex justify-center flex-wrap gap-4 mb-12">
                <button
                    v-for="category in sortedCategories"
                    :key="category.id"
                    @click="selectCategory(category.id)"
                    :class="[
             'px-6 py-2 rounded-full font-medium transition duration-150 ease-in-out text-lg',
             selectedCategoryId === category.id
               ? 'bg-emerald-500 text-white shadow-md'
               : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300'
           ]">
                    {{ category.name }}
                </button>
            </div>

            <!-- Секция с Продуктами -->
            <section v-if="filteredProducts.length > 0">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-10">
                    {{ selectedCategoryName }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Карточка товара -->
                    <div v-for="product in filteredProducts" :key="product.display_id"
                         class="bg-gray-50 rounded-lg shadow-md overflow-hidden p-6 flex flex-col group">
                        <!-- Изображение -->
                        <div class="relative mb-4 h-48 bg-gray-100 rounded-md overflow-hidden group">
                            <img v-if="product.image_url" :src="product.image_url" :alt="product.name"
                                 class="w-full h-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105"
                                 loading="lazy">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Нет
                                изображения
                            </div>
                        </div>
                        <!-- Название и Цена -->
                        <div class="flex-grow mb-4">
                            <h3 class="text-xl font-semibold text-gray-800 mb-1">{{ product.name }}</h3>
                            <p class="text-lg font-bold text-gray-700">{{ product.price_prefix }}
                                {{ formatPrice(product.min_price) }} ₽</p>
                            <p v-if="product.description" class="text-sm text-gray-500 mt-1">{{
                                    product.description
                                }}</p>
                        </div>

                        <!-- Логика кнопок -->
                        <div class="mt-auto h-10 flex items-center"> <!-- Задаем высоту для стабильности -->
                            <!-- Кнопка "В корзину" (если товара НЕТ в корзине) -->
                            <button
                                v-if="!isInCart(product.display_id)"
                                @click="addItemToCart(product.display_id)"
                                :disabled="product.is_available === false || loadingItems[product.display_id]"
                                :class="[
                      'w-full font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center',
                      product.is_available === false ? 'bg-gray-300 text-gray-500 cursor-not-allowed' :
                      loadingItems[product.display_id] ? 'bg-emerald-300 text-white cursor-wait' :
                      'bg-emerald-500 hover:bg-emerald-600 text-white'
                   ]">
                                <svg v-if="product.is_available !== false && !loadingItems[product.display_id]"
                                     class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path>
                                    <path fill-rule="evenodd"
                                          d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z"
                                          clip-rule="evenodd"></path>
                                </svg>
                                <svg v-if="loadingItems[product.display_id]"
                                     class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{
                                        product.is_available === false ? 'Сегодня нет' : (loadingItems[product.display_id] ? 'Добавляем...' : 'В корзину')
                                    }}</span>
                            </button>

                            <!-- Селектор +/- и Удаление (если товар ЕСТЬ в корзине) -->
                            <div v-else class="flex items-center justify-between space-x-2 w-full">
                                <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                                    <button @click="decreaseQuantity(product.display_id)"
                                            :disabled="loadingItems[product.display_id]"
                                            class="px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none disabled:opacity-50">
                                        -
                                    </button>
                                    <!-- Отображаем количество из cartItemsMap -->
                                    <span class="px-3 py-1 text-center font-medium text-gray-700 w-10">{{
                                            getCartQuantity(product.display_id)
                                        }}</span>
                                    <button @click="increaseQuantity(product.display_id)"
                                            :disabled="loadingItems[product.display_id] || getCartQuantity(product.display_id) >= 10"
                                            class="px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none disabled:opacity-50">
                                        +
                                    </button>
                                </div>
                                <button
                                    @click="removeItem(cartItemsMap[product.display_id]?.cart_item_id, product.display_id)"
                                    :disabled="loadingItems[product.display_id] || !cartItemsMap[product.display_id]?.cart_item_id"
                                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-100 rounded-full transition duration-150 ease-in-out focus:outline-none disabled:opacity-50"
                                    title="Удалить из корзины">
                                    <svg v-if="!loadingItems[product.display_id]" class="w-5 h-5" fill="currentColor"
                                         viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                    <svg v-else class="animate-spin h-5 w-5 text-red-600"
                                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <!-- --- Конец логики кнопок --- -->
                    </div>
                </div>
            </section>
            <!-- Сообщение, если нет товаров -->
            <div v-else class="text-center py-16 text-gray-500">
                <p v-if="selectedCategoryId">В категории "{{ selectedCategoryName }}" пока нет товаров.</p>
                <p v-else>Выберите категорию, чтобы увидеть меню.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Стили только для MenuPage */
</style>
