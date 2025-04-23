<script>
import AppLayout from '../Layouts/AppLayout.vue';
// --- ДОБАВЛЯЕМ Импорты ---
import { ref } from 'vue'; // Используем ref для productQuantities
import { router } from '@inertiajs/vue3'; // Для отправки в корзину

export default {
    name: 'Welcome',
    layout: AppLayout,
    props:{
        // Переименовываем пропс для ясности (или оставьте products, если контроллер передает так)
        featuredProducts: { // <-- ИЗМЕНЕНО ИМЯ ПРОПСА (опционально)
            type: Array,
            default: () => []
        }
    },
    // --- ДОБАВЛЯЕМ setup() ---
    setup(props) {
        // Реактивное состояние для количества
        const productQuantities = ref({});

        // --- КОПИРУЕМ МЕТОДЫ ИЗ Menu.vue ---
        const formatPrice = (price) => {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        };

        const initiateAddToCart = (displayId) => {
            console.log('Welcome: initiateAddToCart called for ID:', displayId);
            // В Composition API используем .value для доступа к ref
            productQuantities.value[displayId] = 1;
        };

        const increaseQuantity = (displayId) => {
            if (productQuantities.value.hasOwnProperty(displayId)) {
                if (productQuantities.value[displayId] < 10) {
                    productQuantities.value[displayId]++;
                } else {
                    console.warn(`Cannot add more than 10 items for product ID: ${displayId}`);
                }
            } else {
                productQuantities.value[displayId] = 1;
            }
        };

        const decreaseQuantity = (displayId) => {
            if (productQuantities.value.hasOwnProperty(displayId) && productQuantities.value[displayId] > 1) {
                productQuantities.value[displayId]--;
            } else if (productQuantities.value.hasOwnProperty(displayId) && productQuantities.value[displayId] === 1) {
                // Используем delete напрямую для ref-объекта
                delete productQuantities.value[displayId];
            }
        };

        const addToCart = (displayId) => {
            const quantity = productQuantities.value[displayId];
            if (!quantity || quantity < 1) {
                console.error("Quantity not set or invalid for product:", displayId);
                return;
            }
            console.log(`Welcome: Adding product ${displayId} (default variation) with quantity ${quantity} to cart.`);
            router.post(route('cart.add'), {
                product_id: displayId,
                quantity: quantity,
            }, {
                preserveScroll: true,
                preserveState: false, // Сбрасываем состояние здесь
                onSuccess: () => {
                    console.log('Product added from Welcome page!');
                    delete productQuantities.value[displayId];
                },
                onError: errors => {
                    console.error('Failed to add product from Welcome:', errors);
                }
            });
        };
        // --- КОНЕЦ СКОПИРОВАННЫХ МЕТОДОВ ---

        // Возвращаем все необходимое для шаблона
        return {
            productQuantities,
            formatPrice,
            initiateAddToCart,
            increaseQuantity,
            decreaseQuantity,
            addToCart,
        }
    },
    // mounted() можно убрать, если он только для console.log
    // mounted() {
    //     console.log(this.featuredProducts) // Используем новое имя пропса
    // }
}
</script>

<template>
    <div>
        <!-- Секция 1: Hero (без изменений) -->
        <section class="relative w-full bg-gradient-to-r from-stone-800 to-emerald-400 min-h-[60vh] max-h-[700px] overflow-hidden flex items-center justify-center">
            <div class="flex flex-col gap-[7%] md:flex-row w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="w-full md:w-2/5 text-white flex flex-col justify-center py-12 md:py-16 order-2 md:order-1 z-10">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4 leading-tight">
                        Кофе для тех, кто мыслит
                    </h1>
                    <p class="text-lg md:text-xl mb-8 leading-relaxed text-stone-300">
                        Уютная кофейня в центре ЮУрГУ. Вкусный кофе, свежая выпечка. Идеальное место для работы и отдыха.
                    </p>
                    <a href="#menu" class="inline-block bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-8 py-3 rounded-full transition duration-150 ease-in-out self-start">
                        Посмотреть меню
                    </a>
                </div>
                <div class="w-full md:w-3/5 order-1 md:order-2 flex items-center justify-center py-12 md:py-16">
                    <img src="/images/IMG_2176.JPG" alt="Интерьер кофейни"
                         class="w-full max-w-xl lg:max-w-2xl aspect-[4/3] object-cover rounded-2xl shadow-xl">
                </div>
            </div>
        </section>

        <!-- Секция 2: Меню на сегодня -->
        <section id="menu" class="py-16 lg:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-12">
                    Меню на сегодня
                </h2>
                <!-- ИСПОЛЬЗУЕМ featuredProducts ИЗ ПРОПСОВ -->
                <div v-if="featuredProducts && featuredProducts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Карточка товара -->
                    <div v-for="product in featuredProducts" :key="product.display_id"
                         class="bg-gray-50 rounded-lg shadow-md overflow-hidden p-6 flex flex-col group"> <!-- Добавлен group -->
                        <!-- Изображение -->
                        <div class="relative mb-4 h-48 bg-gray-100 rounded-md overflow-hidden">
                            <img v-if="product.image_url" :src="product.image_url" :alt="product.name"
                                 class="w-full h-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105" loading="lazy">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Нет фото</div>
                        </div>
                        <!-- Название и цена -->
                        <h3 class="text-xl font-semibold text-gray-800 mb-1 flex-grow">{{ product.name }}</h3> <!-- Добавлен flex-grow -->
                        <p class="text-lg font-bold text-gray-700 mb-4">{{ product.price_prefix }} {{ formatPrice(product.min_price) }} ₽</p>

                        <!-- --- НАЧАЛО ИЗМЕНЕНИЙ КНОПКИ --- -->
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
                                <svg v-if="product.is_available !== false" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path><path fill-rule="evenodd" d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z" clip-rule="evenodd"></path></svg>
                                <span>{{ product.is_available === false ? 'Сегодня нет' : 'В корзину' }}</span>
                            </button>

                            <!-- Селектор Количества и Кнопка Добавления -->
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
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path><path fill-rule="evenodd" d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                        </div>
                        <!-- --- КОНЕЦ ИЗМЕНЕНИЙ КНОПКИ --- -->
                    </div>
                    <!-- Комментарии для остальных карточек можно удалить -->
                </div>
                <!-- Сообщение, если нет избранных товаров -->
                <div v-else class="text-center text-gray-500">
                    Нет избранных товаров для отображения.
                </div>

                <!-- Кнопка Подробнее -->
                <div class="mt-12 text-center">
                    <a href="/menu"
                       class="inline-flex items-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-3 rounded-full transition duration-150 ease-in-out">
                        Подробнее
                        <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- Секция 3: Карта и адрес (без изменений) -->
        <section class="py-16 lg:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div class="w-full rounded-lg shadow-md overflow-hidden">
                    <iframe class="w-full h-[500px] border-0 rounded-lg" src="https://yandex.ru/map-widget/v1/?um=constructor%3Add89e6fba7d93e4c0fa7c5380bb3c63c6b545a2be85058b7bc65f95a13133b27&source=constructor" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="text-center flex flex-col items-center gap-6 justify-center">
                    <h3 class="text-3xl font-bold text-gray-800 mb-4">Мы находимся по адресу</h3>
                    <div class="inline-block bg-stone-700 text-white font-semibold px-6 py-3 rounded-xl mb-6 w-max"> <!-- Исправлено rounded-4xl на rounded-xl -->
                        пр-т Ленина 87, 3 корпус ЮУрГУ, 3 этаж
                    </div>
                    <div class="flex justify-center md:justify-start">
                        <img src="/images/coffee-cup-icon.svg" alt="Стаканчик кофе" class="w-40 h-auto">
                    </div>
                </div>
            </div>
        </section>

        <!-- Футер (обычно в Layout) -->
    </div>
</template>
