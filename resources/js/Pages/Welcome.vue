<script>
import AppLayout from '../Layouts/AppLayout.vue';
import {router} from '@inertiajs/vue3';

export default {
    name: 'Welcome',
    layout: AppLayout,
    props: {
        featuredProducts: {
            type: Array,
            default: () => []
        }
        // Убедитесь, что $page.props.cart_details.items передается из HandleInertiaRequests
    },
    data() {
        return {
            loadingItems: {}, // { display_id: true/false }
        };
    },
    computed: {
        cartItemsMap() {
            const map = {};
            const pageCartItems = this.$page.props.cart_details?.items || [];
            pageCartItems.forEach(item => {
                if (item.product?.id) {
                    const productInFeatured = this.featuredProducts.find(p => p.display_id === item.product.id);
                    if (productInFeatured && !this.checkIfProductHasOptions(productInFeatured.display_id, productInFeatured.category_id)) {
                        map[item.product.id] = {
                            cart_item_id: item.id,
                            quantity: item.quantity
                        };
                    }
                }
            });
            return map;
        }
    },
    methods: {
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },
        checkIfProductHasOptions(productId, categoryId) {
            if (categoryId === undefined) { // Пытаемся найти категорию, если не передана
                const product = this.featuredProducts.find(p => p.display_id === productId);
                categoryId = product?.category_id;
            }

            if ([3].includes(categoryId)) { // Кофе
                return true;
            }
            return false;
        },
        isInCart(displayId) {
            return !!this.cartItemsMap[displayId];
        },
        getCartQuantity(displayId) {
            return this.cartItemsMap[displayId]?.quantity ?? 0;
        },
        addItemToCart(product) {
            if (product.is_stock_managed && product.count <= 0) {
                alert(`Товара "${product.name}" нет в наличии.`);
                return;
            }
            this.loadingItems[product.display_id] = true;
            router.post(route('cart.add'), {
                product_id: product.display_id,
                quantity: 1,
                options: {} // Пустые опции для товаров без них
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Welcome: Simple product added!');
                },
                onError: errors => {
                    console.error('Welcome: Failed to add simple product:', errors);
                    alert('Не удалось добавить товар. Ошибка: ' + JSON.stringify(errors));
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

            if (product.is_stock_managed && newQuantity > product.count) {
                alert(`Максимально доступно ${product.count} шт. товара "${product.name}".`);
                return;
            }
            if (newQuantity > 10) {
                console.warn(`Cannot add more than 10 items for product ID: ${product.display_id}`);
                return;
            }

            this.loadingItems[product.display_id] = true;
            router.patch(route('cart.update', {cart_item: currentItem.cart_item_id}), {
                quantity: newQuantity
            }, {
                preserveScroll: true,
                preserveState: true, // Можно сохранить состояние, т.к. это не полная перезагрузка
                onSuccess: () => {
                    console.log(`Welcome: Quantity updated to ${newQuantity}`);
                },
                onError: errors => {
                    console.error('Welcome: Quantity update failed:', errors);
                },
                onFinish: () => {
                    delete this.loadingItems[product.display_id];
                }
            });
        },
        decreaseQuantity(product) {
            const currentItem = this.cartItemsMap[product.display_id];
            if (!currentItem) return;
            const newQuantity = currentItem.quantity - 1;
            if (newQuantity >= 1) {
                this.loadingItems[product.display_id] = true;
                router.patch(route('cart.update', {cart_item: currentItem.cart_item_id}), {
                    quantity: newQuantity
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        console.log(`Welcome: Quantity updated to ${newQuantity}`);
                    },
                    onError: errors => {
                        console.error('Welcome: Quantity update failed:', errors);
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
            if (!cartItemId) {
                console.error("Welcome: Cannot remove item, cart_item_id is missing for displayId:", displayId);
                return;
            }
            this.loadingItems[displayId] = true;
            router.delete(route('cart.destroy', {cart_item: cartItemId}), {
                preserveScroll: true,
                onSuccess: () => {
                    console.log(`Welcome: Item ${cartItemId} removed`);
                },
                onError: errors => {
                    console.error('Welcome: Item remove failed:', errors);
                    alert('Не удалось удалить товар. Ошибка: ' + JSON.stringify(errors));
                },
                onFinish: () => {
                    delete this.loadingItems[displayId];
                }
            });
        },
    },
}
</script>

<template>
    <div>
        <!-- Секция 1: Hero -->
        <section
            class="relative w-full bg-gradient-to-r from-stone-800 to-emerald-400 min-h-[60vh] max-h-[700px] overflow-hidden flex items-center justify-center">
            <div class="flex flex-col gap-[7%] md:flex-row w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div
                    class="w-full md:w-2/5 text-white flex flex-col justify-center py-12 md:py-16 order-2 md:order-1 z-10">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4 leading-tight">
                        Кофе для тех, кто мыслит
                    </h1>
                    <p class="text-lg md:text-xl mb-8 leading-relaxed text-stone-300">
                        Уютная кофейня в центре ЮУрГУ. Вкусный кофе, свежая выпечка. Идеальное место для работы и
                        отдыха.
                    </p>
                    <a href="#menu"
                       class="inline-block bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-8 py-3 rounded-full transition duration-150 ease-in-out self-start">
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
                <div v-if="featuredProducts && featuredProducts.length > 0"
                     class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="product in featuredProducts" :key="product.display_id"
                         class="bg-gray-50 rounded-lg shadow-md overflow-hidden p-6 flex flex-col group">
                        <a :href="route('products.show', { product: product.slug })" class="block">
                            <div class="relative mb-4 h-48 bg-gray-100 rounded-md overflow-hidden">
                                <img v-if="product.image_url" :src="product.image_url" :alt="product.name"
                                     class="w-full h-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105"
                                     loading="lazy">
                                <div v-else
                                     class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Нет
                                    фото
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
                            <p class="text-lg font-bold text-gray-700 mb-4">{{ product.price_prefix }}
                                {{ formatPrice(product.min_price) }} ₽</p>
                            <p v-if="product.is_stock_managed && product.is_available"
                               class="text-xs text-emerald-600 mt-1">
                                В наличии: {{ product.count }} шт.
                            </p>
                            <p v-if="product.is_stock_managed && !product.is_available"
                               class="text-xs text-red-500 mt-1">
                                Нет в наличии
                            </p>
                            <p v-if="product.description && !(product.is_stock_managed && product.is_available)"
                               class="text-sm text-gray-500 mt-1">
                                {{ product.description }}
                            </p>
                        </div>

                        <div class="mt-auto h-10 flex items-center">
                            <a v-if="checkIfProductHasOptions(product.display_id, product.category_id)"
                               :href="route('products.show', { product: product.slug })"
                               :class="[
                                    'w-full font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center text-center',
                                    !product.is_available ? 'bg-gray-300 text-gray-500 cursor-not-allowed pointer-events-none' : 'bg-emerald-500 hover:bg-emerald-600 text-white'
                                ]">
                                <span>{{ !product.is_available ? 'Нет в наличии' : 'Выбрать' }}</span>
                            </a>
                            <template v-else>
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
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-500">
                    Нет избранных товаров для отображения.
                </div>
                <div class="mt-12 text-center">
                    <a href="/menu"
                       class="inline-flex items-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-3 rounded-full transition duration-150 ease-in-out">
                        Подробнее
                        <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                  clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- Секция 3: Карта и адрес -->
        <section class="py-16 lg:py-24 bg-white">
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div class="w-full rounded-lg shadow-md overflow-hidden">
                    <iframe class="w-full h-[500px] border-0 rounded-lg"
                            src="https://yandex.ru/map-widget/v1/?um=constructor%3Add89e6fba7d93e4c0fa7c5380bb3c63c6b545a2be85058b7bc65f95a13133b27&source=constructor"
                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="text-center flex flex-col items-center gap-6 justify-center">
                    <h3 class="text-3xl font-bold text-gray-800 mb-4">Мы находимся по адресу</h3>
                    <div class="inline-block bg-stone-700 text-white font-semibold px-6 py-3 rounded-xl mb-6 w-max">
                        пр-т Ленина 87, 3 корпус ЮУрГУ, 3 этаж
                    </div>
                    <div class="flex justify-center md:justify-start">
                        <img src="/images/coffee-cup-icon.svg" alt="Стаканчик кофе" class="w-40 h-auto">
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.min-h-\[60vh\] {
    min-height: 60vh;
}
</style>
