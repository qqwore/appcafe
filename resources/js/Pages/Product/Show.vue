<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Head, router} from '@inertiajs/vue3';
import {ref, computed, watch} from 'vue'; // Используем Composition API

export default {
    name: 'ProductShow',
    layout: AppLayout,
    components: {Head},
    props: {
        product: {type: Object, required: true},
        availableExtras: {type: Object, default: () => ({milks: [], syrups: []})},
        errors: Object,
    },
    setup(props) {
        const selectedVariationId = ref(
            (props.product.variations && props.product.variations.length > 0)
                ? props.product.variations[0].id
                : props.product.display_id // Используем display_id если нет вариаций в product.variations
        );
        const quantity = ref(1);
        const selectedOptions = ref({
            sugar_quantity: 0,
            has_cinnamon: false,
            milk_extra_id: null,
            syrup_extra_id: null,
            has_condensed_milk: false,
        });
        const isLoading = ref(false);

        const currentVariation = computed(() => {
            return props.product.variations?.find(v => v.id === selectedVariationId.value) ||
                {id: props.product.display_id, price: props.product.min_price, size_name: 'Стандарт'};
        });

        const currentProductStock = computed(() => {
            // Если есть вариации, ищем остаток у выбранной вариации (если он есть в объекте вариации)
            // Иначе берем остаток из основного объекта product (props.product.count)
            const variation = props.product.variations?.find(v => v.id === selectedVariationId.value);
            return variation?.count ?? props.product.count;
        });

        const isCurrentProductStockManaged = computed(() => {
            // Аналогично, проверяем флаг для выбранной вариации или основного продукта
            const variation = props.product.variations?.find(v => v.id === selectedVariationId.value);
            return variation?.is_stock_managed ?? props.product.is_stock_managed;
        });

        const pricePerItemWithOptions = computed(() => {
            let basePrice = parseFloat(currentVariation.value.price) || 0;
            let extrasPrice = 0;
            if (selectedOptions.value.milk_extra_id && props.availableExtras.milks) {
                const milk = props.availableExtras.milks.find(m => m.id === selectedOptions.value.milk_extra_id);
                extrasPrice += parseFloat(milk?.price || 0);
            }
            if (selectedOptions.value.syrup_extra_id && props.availableExtras.syrups) {
                const syrup = props.availableExtras.syrups.find(s => s.id === selectedOptions.value.syrup_extra_id);
                extrasPrice += parseFloat(syrup?.price || 0);
            }
            // Добавьте сюда расчет для других платных опций, если они есть
            return basePrice + extrasPrice;
        });

        const totalPrice = computed(() => {
            return (pricePerItemWithOptions.value * quantity.value);
        });

        const formatPrice = (price) => {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        };

        const selectSize = (variationId) => {
            selectedVariationId.value = variationId;
            // При смене размера, можно сбросить опции и количество
            quantity.value = 1;
            // selectedOptions.value = { sugar_quantity: 0, ... }; // Если нужно
        };

        const changeQuantity = (delta) => {
            const newQuantity = quantity.value + delta;
            let maxQuantity = 10;

            if (isCurrentProductStockManaged.value && currentProductStock.value !== null) {
                maxQuantity = Math.min(10, currentProductStock.value);
            }

            if (newQuantity >= 1 && newQuantity <= maxQuantity) {
                quantity.value = newQuantity;
            } else if (newQuantity > maxQuantity && isCurrentProductStockManaged.value) {
                alert(`Максимально доступно ${currentProductStock.value} шт.`);
            } else if (newQuantity > maxQuantity) {
                alert(`Максимум 10 шт. одного товара.`);
            }
        };

        const toggleOption = (key) => {
            selectedOptions.value[key] = !selectedOptions.value[key];
        };
        const selectExtra = (key, event) => {
            selectedOptions.value[key] = event.target.value === '' ? null : parseInt(event.target.value);
        };
        const changeSugar = (delta) => {
            const current = selectedOptions.value.sugar_quantity || 0;
            const newValue = current + delta;
            if (newValue >= 0 && newValue <= 3) {
                selectedOptions.value.sugar_quantity = newValue;
            }
        };

        const addToCart = () => {
            if (isCurrentProductStockManaged.value && quantity.value > currentProductStock.value) {
                alert(`Недостаточно товара "${props.product.name}" на складе. Доступно: ${currentProductStock.value} шт.`);
                return;
            }
            isLoading.value = true;
            router.post(route('cart.add'), {
                product_id: selectedVariationId.value,
                quantity: quantity.value,
                options: selectedOptions.value,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    alert(`${props.product.name} (${currentVariation.value.size_name || 'Стандарт'}) добавлен в корзину!`);
                },
                onError: (errors) => {
                    alert('Ошибка добавления: ' + JSON.stringify(errors));
                },
                onFinish: () => {
                    isLoading.value = false;
                }
            });
        };

        const canShowMilkSelector = computed(() => {
            const productNameLower = props.product.name.toLowerCase();
            return props.product.can_add_milk && !['американо', 'эспрессо'].includes(productNameLower);
        });
        const canShowSyrupSelector = computed(() => {
            const productNameLower = props.product.name.toLowerCase();
            return props.product.can_add_syrup && !['американо', 'эспрессо'].includes(productNameLower);
        });

        watch(() => props.product, (newProductData) => {
            if (newProductData.variations && newProductData.variations.length > 0) {
                selectedVariationId.value = newProductData.variations[0].id;
            } else {
                selectedVariationId.value = newProductData.display_id; // Или newProductData.id
            }
            quantity.value = 1;
            selectedOptions.value = {
                sugar_quantity: 0,
                has_cinnamon: false,
                milk_extra_id: null,
                syrup_extra_id: null,
                has_condensed_milk: false
            };
        }, {immediate: true, deep: true}); // deep: true если product сложный объект


        return {
            selectedVariationId, quantity, selectedOptions, isLoading,
            currentVariation, pricePerItemWithOptions, totalPrice, currentProductStock, isCurrentProductStockManaged,
            formatPrice, selectSize, changeQuantity, toggleOption, selectExtra, changeSugar, addToCart,
            canShowMilkSelector, canShowSyrupSelector,
        };
    }
}
</script>

<template>
    <Head :title="product.name"/>
    <div class="py-12 bg-white">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12">
                <!-- Левая колонка: Изображение -->
                <div class="px-4 sm:px-0">
                    <div class="aspect-[4/3] bg-gray-100 rounded-lg overflow-hidden shadow-md">
                        <img v-if="product.image_url" :src="product.image_url" :alt="product.name"
                             class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">Нет фото</div>
                    </div>
                    <p v-if="isCurrentProductStockManaged && currentProductStock > 0"
                       class="text-sm text-emerald-600 mt-2 text-center">
                        В наличии: {{ currentProductStock }} шт.
                    </p>
                    <p v-if="isCurrentProductStockManaged && currentProductStock <= 0"
                       class="text-sm text-red-500 mt-2 text-center">
                        Нет в наличии
                    </p>
                </div>

                <!-- Правая колонка: Информация и опции -->
                <div class="bg-gray-50 rounded-lg shadow-md p-6 md:p-8 flex flex-col">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ product.name }}</h1>
                    <p class="text-2xl font-semibold text-gray-800 mb-4">
                        {{ formatPrice(pricePerItemWithOptions) }} ₽
                        <span v-if="currentVariation.size_name && currentVariation.size_name !== 'Стандарт'"
                              class="text-base font-normal text-gray-500">/ {{ currentVariation.size_name }}</span>
                        <span v-else-if="product.variations && product.variations.length <= 1"
                              class="text-base font-normal text-gray-500">/ шт</span>
                    </p>
                    <p v-if="product.description" class="text-gray-600 mb-4 text-base leading-relaxed">
                        {{ product.description }}</p>
                    <div v-if="product.kpfc" class="text-xs text-gray-500 bg-gray-100 p-2 rounded-md mb-4">
                        <span class="font-medium">Приблизительное КБЖУ на порцию:</span><br>
                        {{ product.kpfc.kilocalories }} ккал /
                        {{ product.kpfc.proteins }} г белков /
                        {{ product.kpfc.fats }} г жиров /
                        {{ product.kpfc.carbohydrates }} г углеводов
                    </div>

                    <div v-if="product.variations && product.variations.length > 1" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Размер:</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="variation in product.variations" :key="variation.id"
                                    @click="selectSize(variation.id)"
                                    :class="[
                                         'px-4 py-1.5 border rounded-full text-sm transition duration-150 ease-in-out focus:outline-none',
                                         selectedVariationId === variation.id
                                             ? 'bg-emerald-500 text-white border-emerald-500 ring-2 ring-offset-1 ring-emerald-300'
                                             : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100 hover:border-gray-400'
                                     ]">
                                {{ variation.size_name || 'Стандарт' }}
                                <span class="text-xs opacity-75 ml-1">({{ formatPrice(variation.price) }}₽)</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="product.category_id === 3 || product.can_add_condensed_milk"
                         class="space-y-3 mb-6 text-sm border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-800 mb-2">Дополнительно:</h4>
                        <div v-if="canShowMilkSelector && availableExtras.milks.length > 0"
                             class="flex items-center gap-2">
                            <label :for="`prod-milk`" class="text-gray-600 w-16 shrink-0">Молоко:</label>
                            <select id="prod-milk" :value="selectedOptions.milk_extra_id ?? ''"
                                    @change="selectExtra('milk_extra_id', $event)"
                                    class="flex-grow border-gray-300 rounded-md shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-xs py-1">
                                <option value="">Обычное</option>
                                <option v-for="milk in availableExtras.milks" :key="milk.id" :value="milk.id">
                                    {{ milk.name }} (+{{ formatPrice(milk.price) }}₽)
                                </option>
                            </select>
                        </div>
                        <div v-if="canShowSyrupSelector && availableExtras.syrups.length > 0"
                             class="flex items-center gap-2">
                            <label :for="`prod-syrup`" class="text-gray-600 w-16 shrink-0">Сироп:</label>
                            <select id="prod-syrup" :value="selectedOptions.syrup_extra_id ?? ''"
                                    @change="selectExtra('syrup_extra_id', $event)"
                                    class="flex-grow border-gray-300 rounded-md shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-xs py-1">
                                <option value="">Без сиропа</option>
                                <option v-for="syrup in availableExtras.syrups" :key="syrup.id" :value="syrup.id">
                                    {{ syrup.name }} (+{{ formatPrice(syrup.price) }}₽)
                                </option>
                            </select>
                        </div>
                        <div v-if="product.can_add_sugar" class="flex items-center gap-2">
                            <span class="text-gray-600 w-16 shrink-0">Сахар:</span>
                            <div class="flex items-center border border-gray-300 rounded-full overflow-hidden">
                                <button @click="changeSugar(-1)" :disabled="selectedOptions.sugar_quantity <= 0"
                                        class="px-3 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    -
                                </button>
                                <span class="px-3 py-1 text-center font-medium text-gray-700 w-10 text-sm">{{
                                        selectedOptions.sugar_quantity || 0
                                    }}</span>
                                <button @click="changeSugar(1)" :disabled="selectedOptions.sugar_quantity >= 3"
                                        class="px-3 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    +
                                </button>
                            </div>
                        </div>
                        <div v-if="product.can_add_cinnamon" class="flex items-center gap-2">
                            <label class="flex items-center text-gray-600 cursor-pointer">
                                <input type="checkbox" :checked="selectedOptions.has_cinnamon"
                                       @change="toggleOption('has_cinnamon')"
                                       class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500 h-4 w-4">
                                <span class="ml-2">Корица</span>
                            </label>
                        </div>
                        <div v-if="product.can_add_condensed_milk" class="flex items-center gap-2">
                            <label class="flex items-center text-gray-600 cursor-pointer">
                                <input type="checkbox" :checked="selectedOptions.has_condensed_milk"
                                       @change="toggleOption('has_condensed_milk')"
                                       class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500 h-4 w-4">
                                <span class="ml-2">Сгущенка</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-4 mt-auto pt-6 border-t border-gray-200">
                        <div
                            class="flex items-center border border-gray-300 rounded-full overflow-hidden flex-shrink-0">
                            <button @click="changeQuantity(-1)" :disabled="quantity <= 1"
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 focus:outline-none disabled:opacity-50 text-lg">
                                -
                            </button>
                            <span class="px-5 py-2 text-center font-medium text-gray-800 w-16 text-lg">{{
                                    quantity
                                }}</span>
                            <button @click="changeQuantity(1)"
                                    :disabled="isLoading || (isCurrentProductStockManaged && quantity >= currentProductStock) || quantity >= 10"
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 focus:outline-none disabled:opacity-50 text-lg">
                                +
                            </button>
                        </div>
                        <button @click="addToCart"
                                :disabled="isLoading || (isCurrentProductStockManaged && currentProductStock <= 0)"
                                :class="[
                                     'w-full sm:w-auto flex-grow inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-full shadow-sm text-base font-medium text-white transition duration-150 ease-in-out',
                                     isLoading ? 'bg-emerald-300 cursor-not-allowed' :
                                     (isCurrentProductStockManaged && currentProductStock <= 0) ? 'bg-gray-300 text-gray-500 cursor-not-allowed' :
                                     'bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500'
                                 ]">
                            <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else-if="!(isCurrentProductStockManaged && currentProductStock <= 0)"
                                 class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path>
                                <path fill-rule="evenodd"
                                      d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            <span>{{
                                    isLoading ? 'Добавляем...' : (isCurrentProductStockManaged && currentProductStock <= 0) ? 'Нет в наличии' : `Добавить (${formatPrice(totalPrice)} ₽)`
                                }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
