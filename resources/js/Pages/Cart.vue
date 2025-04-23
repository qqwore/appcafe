<script>
import AppLayout from '../Layouts/AppLayout.vue';
import { router, Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

export default {
    name: 'CartPage',
    layout: AppLayout,
    components: { Head },
    props: {
        cartItems: {
            type: Array,
            default: () => [],
        },
        availableExtras: {
            type: Object,
            default: () => ({ milks: [], syrups: [] }),
        },
        orderSuccess: {
            type: Boolean,
            default: false
        }
    },
    setup(props) {
        const localCartItems = ref([]);
        const orderSuccessModalVisible = ref(props.orderSuccess);

        const isEmpty = computed(() => localCartItems.value.length === 0);

        const cartTotal = computed(() => {
            return localCartItems.value.reduce((total, item) => {
                return total + (parseFloat(item.item_total_price) || 0);
            }, 0);
        });

        const formatPrice = (price) => {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        };

        const findExtraPrice = (type, id) => {
            if (!id || !props.availableExtras || !props.availableExtras[type]) return 0;
            const extra = props.availableExtras[type].find(e => e.id === id);
            return extra ? parseFloat(extra.price) : 0;
        };

        const canShowSyrupSelector = (product) => {
            const productNameLower = product.name.toLowerCase();
            return product.can_add_syrup && !['американо', 'эспрессо'].includes(productNameLower);
        };

        // Новый метод для проверки, можно ли показывать выбор молока
        const canShowMilkSelector = (product) => {
            const productNameLower = product.name.toLowerCase();
            // Молоко можно показать, если категория позволяет И это не американо/эспрессо
            return product.can_add_milk && !['американо', 'эспрессо'].includes(productNameLower);
        };

        const updateQuantity = (item, delta) => {
            const newQuantity = item.quantity + delta;
            if (newQuantity >= 1 && newQuantity <= 10) {
                router.patch(route('cart.update', { cart_item: item.id }), {
                    quantity: newQuantity,
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => console.log(`Quantity updated for ${item.id} to ${newQuantity}`),
                    onError: errors => console.error('Quantity update failed:', errors),
                });
            }
        };

        const updateOption = (item, optionKey, newValue) => {
            console.log(`Updating option ${optionKey} for ${item.id} to ${newValue}`);
            router.patch(route('cart.update', { cart_item: item.id }), {
                options: {
                    [optionKey]: newValue
                }
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => console.log(`Option ${optionKey} updated for ${item.id}`),
                onError: errors => console.error('Option update failed:', errors),
            });
        };

        const removeItem = (itemId) => {
            if (confirm('Удалить товар из корзины?')) {
                router.delete(route('cart.destroy', { cart_item: itemId }), {
                    preserveScroll: true,
                    onSuccess: () => console.log(`Item ${itemId} removed`),
                    onError: errors => console.error('Item remove failed:', errors),
                });
            }
        };

        const clearCart = () => {
            if (confirm('Очистить корзину?')) {
                router.delete(route('cart.clear'), {
                    preserveScroll: true,
                    onSuccess: () => console.log('Cart cleared'),
                    onError: errors => console.error('Cart clear failed:', errors),
                });
            }
        };

        const placeOrder = () => {
            console.log('Placing order...');
            router.post(route('orders.store'), {}, {
                onSuccess: () => {
                    console.log('Order placed successfully!');
                },
                onError: errors => {
                    console.error('Order placement failed:', errors);
                    alert('Не удалось оформить заказ. Ошибки: ' + JSON.stringify(errors));
                },
            });
        };

        const closeSuccessModal = () => {
            orderSuccessModalVisible.value = false;
            router.visit(route('profile.show') || '/profile');
        };

        const initializeLocalCart = () => {
            localCartItems.value = JSON.parse(JSON.stringify(props.cartItems));
        };

        initializeLocalCart();

        watch(() => props.cartItems, (newItems) => {
            console.log('Cart items prop changed, updating local copy.');
            localCartItems.value = JSON.parse(JSON.stringify(newItems));
        }, { deep: true });

        watch(() => props.orderSuccess, (newValue) => {
            orderSuccessModalVisible.value = newValue;
        });


        return {
            localCartItems,
            orderSuccessModalVisible,
            isEmpty,
            cartTotal,
            formatPrice,
            findExtraPrice,
            updateQuantity,
            updateOption,
            removeItem,
            clearCart,
            placeOrder,
            closeSuccessModal,
            availableExtras: props.availableExtras,
            canShowSyrupSelector,
            canShowMilkSelector // <-- Добавляем новый метод
        };
    }
}
</script>

<template>
    <Head title="Корзина" />

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="flex justify-between items-center mb-6 px-4 sm:px-0">
                <h1 class="text-3xl font-bold text-gray-800">Корзина</h1>
                <button
                    v-if="!isEmpty"
                    @click="clearCart"
                    class="text-sm text-gray-500 hover:text-red-600 hover:underline transition duration-150 ease-in-out">
                    Очистить
                </button>
            </div>

            <div v-if="isEmpty" class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
                Ваша корзина пуста
            </div>

            <div v-else class="flex flex-col lg:flex-row gap-8">

                <div class="lg:w-2/3 space-y-4">
                    <div v-for="item in localCartItems" :key="item.id"
                         class="bg-white shadow-sm rounded-lg p-4 flex flex-col md:flex-row md:items-start gap-4">

                        <div class="flex-shrink-0 w-24 h-24 bg-gray-100 rounded-md overflow-hidden mx-auto md:mx-0">
                            <img v-if="item.product.image_url" :src="item.product.image_url" :alt="item.product.name" class="w-full h-full object-cover">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">Фото</div>
                        </div>

                        <div class="flex-grow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ item.product.name }}</h3>

                            <div v-if="item.product.category_id === 3 || item.product.category_id === 4" class="space-y-2 mt-2 mb-3 text-sm">
                                <!-- Выбор размера (если нужно) -->
                                <!-- <div v-if="item.product.available_sizes && item.product.available_sizes.length > 0">...</div> -->

                                <details class="group" v-if="canShowMilkSelector(item.product) || canShowSyrupSelector(item.product) || item.product.can_add_sugar || item.product.can_add_cinnamon || item.product.can_add_condensed_milk">
                                    <summary class="cursor-pointer text-emerald-600 hover:text-emerald-800 list-none flex items-center">
                                        <span>Дополнительно</span>
                                        <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </summary>
                                    <div class="mt-2 space-y-2 pl-4 border-l border-gray-200">
                                        <!-- Молоко (с проверкой canShowMilkSelector) -->
                                        <div v-if="canShowMilkSelector(item.product) && availableExtras.milks.length > 0" class="flex items-center gap-2">
                                            <label :for="`milk-${item.id}`" class="text-gray-600 w-16 shrink-0">Молоко:</label>
                                            <select :id="`milk-${item.id}`"
                                                    :value="item.selected_options.milk_extra_id ?? ''"
                                                    @change="updateOption(item, 'milk_extra_id', $event.target.value === '' ? null : parseInt($event.target.value))"
                                                    class="flex-grow border-gray-300 rounded-md shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-xs py-1">
                                                <option value="">Обычное</option>
                                                <option v-for="milk in availableExtras.milks" :key="milk.id" :value="milk.id">
                                                    {{ milk.name }} (+{{ formatPrice(milk.price) }}₽)
                                                </option>
                                            </select>
                                        </div>
                                        <!-- Сироп (с проверкой canShowSyrupSelector) -->
                                        <div v-if="canShowSyrupSelector(item.product) && availableExtras.syrups.length > 0" class="flex items-center gap-2">
                                            <label :for="`syrup-${item.id}`" class="text-gray-600 w-16 shrink-0">Сироп:</label>
                                            <select :id="`syrup-${item.id}`"
                                                    :value="item.selected_options.syrup_extra_id ?? ''"
                                                    @change="updateOption(item, 'syrup_extra_id', $event.target.value === '' ? null : parseInt($event.target.value))"
                                                    class="flex-grow border-gray-300 rounded-md shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-xs py-1">
                                                <option value="">Без сиропа</option>
                                                <option v-for="syrup in availableExtras.syrups" :key="syrup.id" :value="syrup.id">
                                                    {{ syrup.name }} (+{{ formatPrice(syrup.price) }}₽)
                                                </option>
                                            </select>
                                        </div>
                                        <!-- Сахар -->
                                        <div v-if="item.product.can_add_sugar" class="flex items-center gap-2">
                                            <span class="text-gray-600 w-16 shrink-0">Сахар:</span>
                                            <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                                                <button @click="updateOption(item, 'sugar_quantity', Math.max(0, (item.selected_options.sugar_quantity || 0) - 1))" :disabled="(item.selected_options.sugar_quantity || 0) <= 0" class="px-2 py-0.5 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">-</button>
                                                <span class="px-2 py-0.5 text-center font-medium text-gray-700 w-8 text-xs">{{ item.selected_options.sugar_quantity || 0 }}</span>
                                                <button @click="updateOption(item, 'sugar_quantity', Math.min(3, (item.selected_options.sugar_quantity || 0) + 1))" :disabled="(item.selected_options.sugar_quantity || 0) >= 3" class="px-2 py-0.5 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">+</button>
                                            </div>
                                        </div>
                                        <!-- Корица -->
                                        <div v-if="item.product.can_add_cinnamon" class="flex items-center gap-2">
                                            <label class="flex items-center text-gray-600">
                                                <input type="checkbox"
                                                       :checked="item.selected_options.has_cinnamon"
                                                       @change="updateOption(item, 'has_cinnamon', $event.target.checked)"
                                                       class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-offset-0 focus:ring-emerald-200 focus:ring-opacity-50 h-4 w-4">
                                                <span class="ml-2">Корица</span>
                                            </label>
                                        </div>
                                        <!-- Сгущенка (для сырников) -->
                                        <label v-if="item.product.can_add_condensed_milk" class="flex items-center text-gray-600">
                                            <input type="checkbox"
                                                   :checked="item.selected_options.has_condensed_milk"
                                                   @change="updateOption(item, 'has_condensed_milk', $event.target.checked)"
                                                   class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-offset-0 focus:ring-emerald-200 focus:ring-opacity-50 h-4 w-4">
                                            <span class="ml-2">Сгущенка</span>
                                        </label>
                                    </div>
                                </details>
                            </div>

                        </div>

                        <div class="flex flex-col md:flex-col md:items-end md:justify-between md:text-right mt-2 md:mt-0 md:w-40 flex-shrink-0">
                            <div class="flex items-center border border-gray-300 rounded-full overflow-hidden self-center md:self-end mb-2">
                                <button @click="updateQuantity(item, -1)" class="px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none">-</button>
                                <span class="px-3 py-1 text-center font-medium text-gray-700 w-12">{{ item.quantity }}</span>
                                <button @click="updateQuantity(item, 1)" class="px-3 py-1 text-gray-600 hover:bg-gray-100 focus:outline-none">+</button>
                            </div>
                            <div class="text-lg font-semibold text-gray-800 mb-2 md:mb-4 text-center md:text-right">
                                {{ formatPrice(item.item_total_price) }} ₽
                                <span v-if="item.quantity > 1" class="text-xs text-gray-500 block md:inline md:ml-1">({{ formatPrice(item.item_total_price / item.quantity) }} ₽/шт)</span>
                            </div>
                            <button @click="removeItem(item.id)" class="text-gray-400 hover:text-red-500 self-center md:self-end" title="Удалить из корзины">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="lg:w-1/3">
                    <div class="bg-white shadow-sm rounded-lg p-6 sticky top-20">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">Ваш заказ</h2>
                        <div class="space-y-3 mb-6 text-gray-700">
                            <div class="flex justify-between">
                                <span>Оплата</span>
                                <span>При получении</span>
                            </div>
                            <div class="flex justify-between text-xl font-bold border-t border-gray-200 pt-3">
                                <span>Итого</span>
                                <span>{{ formatPrice(cartTotal) }} ₽</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">
                            *Мы начнем готовить заказ сразу после его оформления
                        </p>
                        <button
                            @click="placeOrder"
                            class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 px-4 rounded-lg transition duration-150 ease-in-out text-lg">
                            Оформить заказ
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <div v-if="orderSuccessModalVisible" class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-xl leading-6 font-medium text-gray-900 mb-2" id="modal-title">Ваш заказ оформлен!</h3>
                <div class="text-sm text-gray-500 mb-6">
                    <p>Отследить его можно в <a :href="route('profile.show') || '/profile'" class="text-emerald-600 hover:underline font-medium">личном кабинете</a>.</p>
                </div>
                <button @click="closeSuccessModal" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-500 text-base font-medium text-white hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:text-sm">
                    Окей
                </button>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* Стили только для CartPage, если необходимо */
</style>
