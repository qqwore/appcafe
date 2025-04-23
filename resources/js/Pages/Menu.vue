// resources/js/Pages/Menu.vue

<script>
import AppLayout from '../Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

export default {
    name: 'MenuPage',
    layout: AppLayout,
    props: {
        // Ожидаем объект, где ключ - ID категории, значение - { name: '...', products: [...] }
        productsByCategory: Object,
        // Ожидаем массив объектов категорий { id: number, name: string }
        categories: Array,
    },
    data() {
        return {
            // ID желаемого порядка категорий
            categoryOrder: [3, 2, 1, 4], // 3:Кофе, 2:Не кофе->Напитки, 1:Сытная еда, 4:Десерты
            // ID выбранной категории (реальный ID из БД)
            // Инициализируем в null, установим в mounted.
            selectedCategoryId: null,
            // Объект для хранения количества { display_id: quantity }
            productQuantities: {},
        };
    },
    computed: {
        /**
         * Возвращает категории в заданном порядке и переименовывает "Не кофе".
         */
        sortedCategories() {
            const orderedCategories = [];
            // Создаем Map для быстрого поиска категории по ID
            const categoryMap = new Map(this.categories.map(cat => [cat.id, { ...cat }])); // Сразу копируем

            // Проходим по нашему порядку ID
            this.categoryOrder.forEach(id => {
                const category = categoryMap.get(id);
                if (category) {
                    // Переименовываем "Не кофе" (ID 2) в "Напитки"
                    if (category.id === 2) {
                        category.name = 'Напитки';
                    }
                    orderedCategories.push(category);
                    categoryMap.delete(id); // Удаляем из карты, чтобы не добавить дважды
                }
            });

            // Добавляем остальные категории, если они есть и не были в targetOrder
            categoryMap.forEach(category => {
                orderedCategories.push(category);
            });

            return orderedCategories;
        },

        /**
         * Возвращает продукты только для выбранной категории.
         */
        filteredProducts() {
            // Если категория не выбрана или для нее нет данных, возвращаем пустой массив
            if (!this.selectedCategoryId || !this.productsByCategory || !this.productsByCategory[this.selectedCategoryId]) {
                return [];
            }
            // Возвращаем массив продуктов для выбранной категории
            return this.productsByCategory[this.selectedCategoryId].products;
        },

        /**
         * Возвращает имя выбранной категории (с учетом переименования).
         */
        selectedCategoryName() {
            if (!this.selectedCategoryId || !this.sortedCategories) {
                return 'Меню'; // Заголовок по умолчанию
            }
            // Находим категорию в отсортированном массиве по ID и возвращаем ее имя
            const selectedCategory = this.sortedCategories.find(cat => cat.id === this.selectedCategoryId);
            return selectedCategory ? selectedCategory.name : 'Меню';
        }
    },
    methods: {
        /**
         * Выбирает категорию и сбрасывает количества.
         * @param {number} categoryId - ID выбранной категории.
         */
        selectCategory(categoryId) {
            this.selectedCategoryId = categoryId;
            this.productQuantities = {}; // Сбрасываем выбранные количества
        },

        /**
         * Форматирует цену, убирая десятичную часть.
         * @param {number|string} price - Цена продукта.
         * @returns {number|string} - Отформатированная цена или исходное значение.
         */
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },

        /**
         * Инициирует добавление товара, показывая селектор количества со значением 1.
         * @param {number} displayId - ID продукта (стандартной вариации).
         */
        initiateAddToCart(displayId) {
            console.log('initiateAddToCart called for ID:', displayId);
            // Используем прямое присваивание (Vue 3 реактивно)
            this.productQuantities[displayId] = 1;
        },

        /**
         * Увеличивает количество товара, но не более 10.
         * @param {number} displayId - ID продукта.
         */
        increaseQuantity(displayId) {
            if (this.productQuantities.hasOwnProperty(displayId)) {
                if (this.productQuantities[displayId] < 10) { // Проверка на максимум 10
                    this.productQuantities[displayId]++;
                } else {
                    console.warn(`Cannot add more than 10 items for product ID: ${displayId}`);
                }
            } else {
                // На всякий случай, если вызвали без initiateAddToCart
                this.productQuantities[displayId] = 1;
            }
        },

        /**
         * Уменьшает количество товара. Если становится 0, убирает селектор.
         * @param {number} displayId - ID продукта.
         */
        decreaseQuantity(displayId) {
            if (this.productQuantities.hasOwnProperty(displayId) && this.productQuantities[displayId] > 1) {
                this.productQuantities[displayId]--;
            } else if (this.productQuantities.hasOwnProperty(displayId) && this.productQuantities[displayId] === 1) {
                // Удаляем ключ из объекта, чтобы v-if сработал и показал кнопку "В корзину"
                delete this.productQuantities[displayId];
            }
        },

        /**
         * Отправляет запрос на добавление стандартной вариации товара в корзину.
         * @param {number} displayId - ID продукта (стандартной вариации).
         */
        addToCart(displayId) {
            const quantity = this.productQuantities[displayId];
            if (!quantity || quantity < 1) {
                console.error("Quantity not set or invalid for product:", displayId);
                return;
            }
            console.log(`Adding product ${displayId} (default variation) with quantity ${quantity} to cart.`);

            // Отправляем POST-запрос на именованный маршрут 'cart.add'
            router.post(route('cart.add'), {
                product_id: displayId, // ID стандартной вариации
                quantity: quantity,    // Выбранное количество
            }, {
                preserveScroll: true, // Сохранить прокрутку
                preserveState: false, // Не сохранять локальное состояние этой карточки (сбросить quantity)
                onSuccess: () => { // При успехе
                    console.log('Default product variation added to cart!');
                    // Удаляем ключ, чтобы скрыть селектор и показать кнопку "В корзину"
                    delete this.productQuantities[displayId];
                    // Можно добавить уведомление пользователю
                },
                onError: errors => { // При ошибке
                    console.error('Failed to add product to cart:', errors);
                    // Можно показать ошибки пользователю
                }
            });
        },
    },
    /**
     * Хук жизненного цикла: вызывается после монтирования компонента.
     * Устанавливает выбранную категорию по умолчанию.
     */
    mounted() {
        // Устанавливаем первую категорию из отсортированного списка как активную
        if (this.sortedCategories.length > 0) {
            this.selectedCategoryId = this.sortedCategories[0].id;
        }
    }
}
</script>

<template>
    <!-- Основной контейнер страницы -->
    <div class="py-12 bg-white">
        <!-- Контейнер с ограничением ширины и отступами -->
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
                ? 'bg-emerald-500 text-white shadow-md' // Стиль активной кнопки
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300' // Стиль неактивной кнопки
                ]">
                {{ category.name }} <!-- Вывод имени категории (уже переименованной, если нужно) -->
                </button>
            </div>

            <!-- Секция с Продуктами для выбранной категории -->
            <section v-if="filteredProducts.length > 0">
                <!-- Динамический заголовок секции -->
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-10">
                    {{ selectedCategoryName }}
                </h2>
                <!-- Сетка для карточек товаров -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

                    <!-- Карточка Товара (цикл по отфильтрованным продуктам) -->
                    <div
                        v-for="product in filteredProducts"
                        :key="product.display_id"
                        class="bg-gray-50 rounded-lg shadow-md overflow-hidden p-6 flex flex-col transition duration-150 ease-in-out hover:shadow-lg group">

                        <!-- Блок Изображения -->
                        <div class="relative mb-4 h-48 bg-gray-100 rounded-md overflow-hidden group">
                            <!-- Изображение или заглушка -->
                            <img
                                v-if="product.image_url"
                                :src="product.image_url"
                                :alt="product.name"
                                class="w-full h-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105"
                                loading="lazy"
                            >
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-sm">
                                Нет изображения
                            </div>
                        </div>

                        <!-- Блок Названия и Цены -->
                        <div class="flex-grow mb-4">
                            <h3 class="text-xl font-semibold text-gray-800 mb-1">{{ product.name }}</h3>
                            <p class="text-lg font-bold text-gray-700">
                                {{ product.price_prefix }} {{ formatPrice(product.min_price) }} ₽
                            </p>
                            <p v-if="product.description" class="text-sm text-gray-500 mt-1">
                                {{ product.description }}
                            </p>
                        </div>

                        <!-- Блок Кнопки/Селектора -->
                        <div class="mt-auto">
                            <!-- Кнопка "В корзину" (показывается, если количество не выбрано) -->
                            <button
                                v-if="!productQuantities[product.display_id]"
                                @click="initiateAddToCart(product.display_id)"
                                :disabled="product.is_available === false"
                                :class="[
                      'w-full font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center',
                      product.is_available === false
                         ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                         : 'bg-emerald-500 hover:bg-emerald-600 text-white'
                   ]">
                                <!-- Иконка корзины (только если товар доступен) -->
                                <svg v-if="product.is_available !== false" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path><path fill-rule="evenodd" d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z" clip-rule="evenodd"></path></svg>
                                <!-- Текст кнопки -->
                                <span>{{ product.is_available === false ? 'Сегодня нет' : 'В корзину' }}</span>
                            </button>

                            <!-- Селектор Количества и Кнопка Добавления (показывается, если количество выбрано) -->
                            <div v-else class="flex items-center justify-between space-x-2">
                                <!-- Селектор +/- -->
                                <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                                    <button @click="decreaseQuantity(product.display_id)" class="px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none">-</button>
                                    <span class="px-3 py-1 text-center font-medium text-gray-700 w-10">{{ productQuantities[product.display_id] }}</span>
                                    <button @click="increaseQuantity(product.display_id)" class="px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none">+</button>
                                </div>
                                <!-- Кнопка "Добавить в корзину" (серая корзинка) -->
                                <button
                                    @click="addToCart(product.display_id)"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold p-2 rounded-full transition duration-150 ease-in-out flex items-center justify-center focus:outline-none">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path><path fill-rule="evenodd" d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                        </div>

                    </div><!-- Конец Карточки Товара -->
                </div><!-- Конец Сетки -->
            </section>

            <!-- Сообщение, если в выбранной категории нет товаров -->
            <div v-else class="text-center py-16 text-gray-500">
                <p v-if="selectedCategoryId">В категории "{{ selectedCategoryName }}" пока нет товаров.</p>
                <p v-else>Выберите категорию, чтобы увидеть меню.</p>
            </div>

        </div> <!-- Конец max-w-7xl -->
    </div> <!-- Конец основного контейнера -->
</template>

<style scoped>

</style>
