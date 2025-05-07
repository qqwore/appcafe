<script>
// Используем Options API
import AppLayout from '../Layouts/AppLayout.vue';
import {router} from '@inertiajs/vue3';

export default {
    name: 'MenuPage',
    layout: AppLayout,
    props: {
        productsByCategory: {
            type: Object,
            default: () => ({})
        },
        categories: {
            type: Array,
            default: () => []
        },
    },
    data() {
        return {
            categoryOrder: [3, 2, 1, 4],
            selectedCategoryId: null,
            loadingItems: {}, // { display_id: true/false }
        };
    },
    computed: {
        cartItemsMap() {
            const map = {};
            const pageCartItems = this.$page.props.cart_details?.items || [];
            pageCartItems.forEach(item => {
                if (item.product?.id) {
                    const categoryId = this.findProductCategory(item.product.id);
                    // Используем this.checkIfProductHasOptions для определения, простой ли товар
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
        selectedCategoryName() {
            if (!this.selectedCategoryId || !this.sortedCategories) return 'Меню';
            const selectedCategory = this.sortedCategories.find(cat => cat.id === this.selectedCategoryId);
            return selectedCategory ? selectedCategory.name : 'Меню';
        },
    },
    methods: {
        selectCategory(categoryId) {
            this.selectedCategoryId = categoryId;
        },
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },
        checkIfProductHasOptions(productId, categoryId) {
            if (categoryId === undefined) {
                categoryId = this.findProductCategory(productId);
            }
            if ([3].includes(categoryId)) return true; // Кофе

            return false; // Остальные (Сытная еда (1), Напитки (2))
        },
        findProductById(productId) {
            for (const catId in this.productsByCategory) {
                const product = this.productsByCategory[catId]?.products.find(p => p.display_id === productId);
                if (product) return product;
            }
            // Дополнительный поиск по $page.props, если продукт из другой категории (маловероятно для этого контекста)
            for (const catId in this.$page.props.productsByCategory) {
                const p = this.$page.props.productsByCategory[catId]?.products.find(prod => prod.display_id === productId);
                if (p) return p;
            }
            return null;
        },
        findProductCategory(productId) {
            const product = this.findProductById(productId);
            return product?.category_id ?? null;
        },
        isInCart(displayId) {
            return !!this.cartItemsMap[displayId];
        },
        getCartQuantity(displayId) {
            return this.cartItemsMap[displayId]?.quantity ?? 0;
        },
        addItemToCart(product) {
            // Проверка доступности для штучных товаров перед добавлением
            if (product.is_stock_managed && product.count <= 0) {
                // alert(`Товара "${product.name}" нет в наличии.`); // Можно раскомментировать для отладки
                console.warn(`Attempted to add out of stock item: ${product.name}`);
                return; // Не добавляем, если нет в наличии и управляется стоком
            }
            this.loadingItems[product.display_id] = true;
            router.post(route('cart.add'), {
                product_id: product.display_id,
                quantity: 1,
                options: {} // Пустые опции для товаров без выбора
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Simple product added!');
                },
                onError: errors => {
                    console.error('Failed to add simple product:', errors);
                },
                onFinish: () => {
                    delete this.loadingItems[product.display_id];
                }
            });
        },
        increaseQuantity(product) {
            const currentItem = this.cartItemsMap[product.display_id];
            if (!currentItem) return;
            const newQuantity = currentItem.quantity + 1;

            let maxAllowed = 10; // Общий максимум
            if (product.is_stock_managed) {
                // Максимум - это остаток на складе, но не более 10
                maxAllowed = Math.min(10, product.count);
            }

            if (newQuantity <= maxAllowed) {
                this.loadingItems[product.display_id] = true;
                router.patch(route('cart.update', {cart_item: currentItem.cart_item_id}), {
                    quantity: newQuantity
                }, {
                    preserveScroll: true, preserveState: true,
                    onSuccess: () => {
                    }, onError: errors => {
                    },
                    onFinish: () => {
                        delete this.loadingItems[product.display_id];
                    }
                });
            } else {
                // Сообщение, если достигнут лимит (склада или общий)
                if (product.is_stock_managed && newQuantity > product.count) {
                    alert(`Максимально доступно ${product.count} шт. товара "${product.name}".`);
                } else {
                    console.warn(`Cannot add more than 10 items for product ID: ${product.display_id}`);
                }
            }
        },
        decreaseQuantity(product) { // Принимаем product
            const currentItem = this.cartItemsMap[product.display_id];
            if (!currentItem) return;
            const newQuantity = currentItem.quantity - 1;
            if (newQuantity >= 1) {
                this.loadingItems[product.display_id] = true;
                router.patch(route('cart.update', {cart_item: currentItem.cart_item_id}), {
                    quantity: newQuantity
                }, {
                    preserveScroll: true, preserveState: true,
                    onSuccess: () => {
                    }, onError: errors => {
                    },
                    onFinish: () => {
                        delete this.loadingItems[product.display_id];
                    }
                });
            } else {
                this.removeItem(currentItem.cart_item_id, product.display_id);
            }
        },
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

                            <!-- Отображение остатка для штучных товаров, ЕСЛИ ОН БОЛЬШЕ 0 -->
                            <p v-if="product.is_stock_managed && product.count > 0"
                               class="text-xs text-emerald-600 mt-1">
                                В наличии: {{ product.count }} шт.
                            </p>
                            <!-- Надпись "Нет в наличии" отсюда убрана, она будет на кнопке -->
                        </div>

                        <!-- Гибридная логика кнопок -->
                        <div class="mt-auto h-10 flex items-center">
                            <!-- Вариант 1: Кнопка "Выбрать опции" (для товаров с опциями) -->
                            <a v-if="checkIfProductHasOptions(product.display_id, product.category_id)"
                               :href="route('products.show', { product: product.slug })"
                               :class="[
                        'w-full font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center text-center',
                         // Используем product.is_available, которое учитывает сток для штучных
                        !product.is_available ? 'bg-gray-300 text-gray-500 cursor-not-allowed pointer-events-none' : 'bg-emerald-500 hover:bg-emerald-600 text-white'
                    ]">
                                <span>{{ !product.is_available ? 'Нет в наличии' : 'Выбрать' }}</span>
                            </a>

                            <!-- Вариант 2: Логика для товаров БЕЗ опций -->
                            <template v-else>
                                <!-- Кнопка "В корзину" или "Нет в наличии" -->
                                <button
                                    v-if="!isInCart(product.display_id)"
                                    @click="addItemToCart(product)"
                                    :disabled="!product.is_available || loadingItems[product.display_id]"
                                    :class="[
                                'w-full font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center',
                                !product.is_available ? 'bg-gray-300 text-gray-500 cursor-not-allowed' :
                                loadingItems[product.display_id] ? 'bg-emerald-300 text-white cursor-wait' :
                                'bg-emerald-500 hover:bg-emerald-600 text-white'
                                ]">
                                    <svg v-if="product.is_available && !loadingItems[product.display_id]"
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
                                            !product.is_available ? 'Нет в наличии' : (loadingItems[product.display_id] ? 'Добавляем...' : 'В корзину')
                                        }}</span>
                                </button>
                                <!-- Селектор +/- и Удаление -->
                                <div v-else class="flex items-center justify-between space-x-2 w-full">
                                    <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                                        <button @click="decreaseQuantity(product)"
                                                :disabled="loadingItems[product.display_id]"
                                                class="px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none disabled:opacity-50">
                                            -
                                        </button>
                                        <span class="px-3 py-1 text-center font-medium text-gray-700 w-10">{{
                                                getCartQuantity(product.display_id)
                                            }}</span>
                                        <button @click="increaseQuantity(product)"
                                                :disabled="loadingItems[product.display_id] || getCartQuantity(product.display_id) >= 10 || (product.is_stock_managed && getCartQuantity(product.display_id) >= product.count)"
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
                                             fill="currentColor" viewBox="0 0 20 20">
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
