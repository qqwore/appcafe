<script>
// Используем Options API
import AppLayout from '../Layouts/AppLayout.vue';
import {router} from '@inertiajs/vue3'; // Импортируем router для запросов

export default {
    name: 'MenuPage',
    layout: AppLayout,
    // components: { Head }, // Head не используется в шаблоне ниже
    props: {
        productsByCategory: {
            type: Object,
            default: () => ({})
        },
        categories: {
            type: Array,
            default: () => []
        },
        // Убедитесь, что $page.props.cart_details передается!
    },
    data() {
        return {
            categoryOrder: [3, 2, 1, 4],
            selectedCategoryId: null,
            loadingItems: {}, // { display_id: true/false }
        };
    },
    computed: {
        /**
         * Карта товаров БЕЗ ОПЦИЙ, которые уже есть в корзине.
         */
        cartItemsMap() {
            const map = {};
            const pageCartItems = this.$page.props.cart_details?.items || [];
            pageCartItems.forEach(item => {
                if (item.product?.id) {
                    const categoryId = this.findProductCategory(item.product.id);
                    const hasOptions = this.checkIfProductHasOptions(item.product.id, categoryId);
                    if (!hasOptions) {
                        map[item.product.id] = {
                            cart_item_id: item.id,
                            quantity: item.quantity
                        };
                    }
                }
            });
            return map;
        },
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
            if (!this.selectedCategoryId || !this.productsByCategory || typeof this.productsByCategory !== 'object') {
                return [];
            }
            const categoryData = this.productsByCategory[this.selectedCategoryId];
            if (!categoryData || !Array.isArray(categoryData.products)) {
                return [];
            }
            return categoryData.products;
        },
        /**
         * Возвращает имя выбранной категории.
         */
        selectedCategoryName() {
            if (!this.selectedCategoryId || !this.sortedCategories) return 'Меню';
            const selectedCategory = this.sortedCategories.find(cat => cat.id === this.selectedCategoryId);
            return selectedCategory ? selectedCategory.name : 'Меню';
        },
    },
    methods: {
        /**
         * Выбирает категорию.
         */
        selectCategory(categoryId) {
            this.selectedCategoryId = categoryId;
        },
        /**
         * Форматирует цену.
         */
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },
        /**
         * Определяет, есть ли у продукта опции.
         */
        checkIfProductHasOptions(productId, categoryId) {
            if (categoryId === undefined) {
                categoryId = this.findProductCategory(productId);
            }
            if ([3].includes(categoryId)) return true; // Кофе
            if ([4].includes(categoryId)) { // Десерты
                const product = this.findProductById(productId);
                // Проверяем флаг can_add_condensed_milk ИЛИ имя
                return product?.can_add_condensed_milk ?? (product?.name === 'Сырники');
            }
            return false; // Остальные
        },
        /**
         * Вспомогательный метод: находит продукт по ID.
         */
        findProductById(productId) {
            for (const catId in this.productsByCategory) {
                const product = this.productsByCategory[catId]?.products.find(p => p.display_id === productId);
                if (product) return product;
            }
            return null;
        },
        /**
         * Вспомогательный метод для поиска ID категории продукта.
         */
        findProductCategory(productId) {
            const product = this.findProductById(productId);
            return product?.category_id ?? null;
        },
        /**
         * Проверяет, есть ли товар БЕЗ ОПЦИЙ в корзине.
         */
        isInCart(displayId) {
            return !!this.cartItemsMap[displayId];
        },
        /**
         * Возвращает количество из корзины для товара БЕЗ ОПЦИЙ.
         */
        getCartQuantity(displayId) {
            return this.cartItemsMap[displayId]?.quantity ?? 0;
        },
        /**
         * Добавляет 1 штуку товара БЕЗ ОПЦИЙ в корзину.
         */
        // resources/js/Pages/Menu.vue -> methods

        addItemToCart(displayId) { // Добавляет 1 шт товара БЕЗ ОПЦИЙ
            this.loadingItems[displayId] = true;
            router.post(route('cart.add'), {
                product_id: displayId,
                quantity: 1,
                options: {} // Пустые опции
            }, {
                preserveScroll: true,
                // --- ИЗМЕНЕНИЕ ЗДЕСЬ ---
                // preserveState: true, // Можно установить true, чтобы сохранить другое состояние
                // ИЛИ просто убрать эту строку (по умолчанию false)
                // Главное, чтобы НЕ БЫЛО preserveState: false
                // --- КОНЕЦ ИЗМЕНЕНИЯ ---
                onSuccess: () => {
                    console.log('Simple product added!');
                    // НЕ удаляем loadingItems здесь, т.к. ждем обновления props
                    // Vue DevTools покажет обновление cartItemsMap, и v-if/v-else сработают
                },
                onError: errors => {
                    console.error('Failed to add simple product:', errors);
                    // Разблокируем кнопку при ошибке
                    delete this.loadingItems[displayId];
                },
                onFinish: () => {
                    // Разблокируем кнопку ПОСЛЕ завершения запроса (успех или ошибка)
                    // Это важно, т.к. onSuccess может не сработать при определенных редиректах
                    // (хотя с preserveScroll=true обычно срабатывает)
                    // Мы УЖЕ удаляем его в onError, так что здесь может быть лишним,
                    // но оставим для надежности на случай других ошибок.
                    // Если будут проблемы с двойным удалением - убрать отсюда.
                    delete this.loadingItems[displayId];
                }
            });
        },
        /**
         * Увеличивает количество товара БЕЗ ОПЦИЙ В КОРЗИНЕ.
         */
        increaseQuantity(displayId) {
            const currentItem = this.cartItemsMap[displayId];
            if (!currentItem) return;
            const newQuantity = currentItem.quantity + 1;
            if (newQuantity <= 10) {
                this.loadingItems[displayId] = true;
                router.patch(route('cart.update', {cart_item: currentItem.cart_item_id}), {
                    quantity: newQuantity
                }, {
                    preserveScroll: true, preserveState: true,
                    onSuccess: () => {
                    }, onError: errors => {
                    },
                    onFinish: () => {
                        delete this.loadingItems[displayId];
                    }
                });
            }
        },
        /**
         * Уменьшает количество товара БЕЗ ОПЦИЙ В КОРЗИНЕ. Если 0 - удаляет.
         */
        decreaseQuantity(displayId) {
            const currentItem = this.cartItemsMap[displayId];
            if (!currentItem) return;
            const newQuantity = currentItem.quantity - 1;
            if (newQuantity >= 1) {
                this.loadingItems[displayId] = true;
                router.patch(route('cart.update', {cart_item: currentItem.cart_item_id}), {
                    quantity: newQuantity
                }, {
                    preserveScroll: true, preserveState: true,
                    onSuccess: () => {
                    }, onError: errors => {
                    },
                    onFinish: () => {
                        delete this.loadingItems[displayId];
                    }
                });
            } else {
                this.removeItem(currentItem.cart_item_id, displayId);
            }
        },
        /**
         * Удаляет товар БЕЗ ОПЦИЙ из корзины.
         */
        removeItem(cartItemId, displayId) {
            if (!cartItemId) return;
            this.loadingItems[displayId] = true;
            router.delete(route('cart.destroy', {cart_item: cartItemId}), {
                preserveScroll: true,
                onSuccess: () => {
                }, onError: errors => {
                },
                onFinish: () => {
                    delete this.loadingItems[displayId];
                }
            });
        },
    },
    mounted() {
        if (this.sortedCategories.length > 0) {
            if (!this.selectedCategoryId) {
                this.selectedCategoryId = this.sortedCategories[0].id;
            }
        }
    }
}
</script>

<template>
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

                        <!-- Картинка/Название - всегда ссылка -->
                        <a :href="route('products.show', { product: product.slug })" class="block">
                            <div class="relative mb-4 h-48 bg-gray-100 rounded-md overflow-hidden">
                                <img v-if="product.image_url" :src="product.image_url" :alt="product.name"
                                     class="w-full h-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105"
                                     loading="lazy">
                                <div v-else
                                     class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Нет
                                    изображения
                                </div>
                            </div>
                        </a>
                        <div class="flex-grow mb-4">
                            <h3 class="text-xl font-semibold text-gray-800 mb-1">
                                <a :href="route('products.show', { product: product.slug })"
                                   class="hover:text-emerald-600">
                                    {{ product.name }}
                                </a>
                            </h3>
                            <p class="text-lg font-bold text-gray-700">{{ product.price_prefix }}
                                {{ formatPrice(product.min_price) }} ₽</p>
                            <p v-if="product.description" class="text-sm text-gray-500 mt-1">{{
                                    product.description
                                }}</p>
                        </div>

                        <!-- Гибридная логика кнопок -->
                        <div class="mt-auto h-10 flex items-center">
                            <!-- Вариант 1: Кнопка "Выбрать опции" -->
                            <a v-if="checkIfProductHasOptions(product.display_id, product.category_id)"
                               :href="route('products.show', { product: product.slug })"
                               :class="[
                        'w-full font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center text-center',
                        product.is_available === false ? 'bg-gray-300 text-gray-500 cursor-not-allowed pointer-events-none' : 'bg-emerald-500 hover:bg-emerald-600 text-white'
                    ]">
                                <span>{{ product.is_available === false ? 'Сегодня нет' : 'Выбрать' }}</span>
                            </a>
                            <!-- Вариант 2: Старая логика для товаров без опций -->
                            <template v-else>
                                <!-- Кнопка "В корзину" -->
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
                                         class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"
                                         xmlns="http://www.w3.org/2000/svg">
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
                                <!-- Селектор +/- и Удаление -->
                                <div v-else class="flex items-center justify-between space-x-2 w-full">
                                    <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                                        <button @click="decreaseQuantity(product.display_id)"
                                                :disabled="loadingItems[product.display_id]"
                                                class="px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none disabled:opacity-50">
                                            -
                                        </button>
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
                                        <svg v-if="!loadingItems[product.display_id]" class="w-5 h-5"
                                             fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
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
                            </template>
                        </div> <!-- Конец гибридной логики -->
                    </div> <!-- Конец Карточки Товара -->
                </div> <!-- Конец Сетки -->
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
