<script>
// Используем Options API
import AppLayout from '@/Layouts/AppLayout.vue'; // Убедитесь, что alias @/ настроен или используйте относительный путь
import { Head, router, useForm, Link } from '@inertiajs/vue3'; // Добавляем Link, если нужен
import Pagination from '@/Components/Pagination.vue';     // Импортируем компонент пагинации

export default {
    name: 'ProfileShow',
    layout: AppLayout,
    components: {
        Head,
        Link,       // Зарегистрировали Link
        Pagination, // Зарегистрировали Pagination
    },
    props: {
        activeOrders: {
            type: Array,
            default: () => []
        },
        orderHistory: { // Теперь это объект пагинации от Laravel
            type: Object,
            default: () => ({ data: [], links: [] }) // По умолчанию содержит data и links
        },
        filters: { // Для сохранения состояния вкладки при пагинации
            type: Object,
            default: () => ({})
        }
        // errors пропс не нужен, form.errors используется для ошибок валидации формы
    },
    data() {
        return {
            // Инициализируем activeTab из URL параметра 'tab' или по умолчанию 'personal'
            activeTab: this.filters?.tab || 'personal',
            showEditModal: false,

            form: useForm({
                name: this.$page.props.auth.user?.name || '',
                email: this.$page.props.auth.user?.email || '',
                phone: this.$page.props.auth.user?.phone || '',
            }),
        };
    },
    watch: {
        '$page.props.auth.user': {
            handler(newUser) {
                if (newUser && !this.showEditModal) {
                    this.form.name = newUser.name || '';
                    this.form.email = newUser.email || '';
                    this.form.phone = newUser.phone || '';
                    this.form.clearErrors();
                }
            },
            deep: true,
        },
        // Следим за изменением параметра 'tab' в URL (из пропса filters)
        // и обновляем локальную активную вкладку
        '$page.props.filters.tab': {
            handler(newTabValue) {
                this.activeTab = newTabValue || 'personal';
            },
            immediate: true // Проверить при монтировании
        }
    },
    methods: {
        formatPrice(price) {
            const numPrice = parseFloat(price);
            return !isNaN(numPrice) ? Math.floor(numPrice) : price;
        },
        getStatusClass(status) {
            switch (status?.toLowerCase()) {
                case 'preparing': return 'bg-orange-100 text-orange-800';
                case 'ready': return 'bg-blue-100 text-blue-800';
                case 'completed':
                case 'received': return 'bg-green-100 text-green-800';
                case 'cancelled': return 'bg-red-100 text-red-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        },
        getStatusText(status) {
            switch (status?.toLowerCase()) {
                case 'preparing': return 'Готовится';
                case 'ready': return 'Готов';
                case 'completed':
                case 'received': return 'Завершен';
                case 'cancelled': return 'Отменен';
                default: return status || 'Неизвестно';
            }
        },
        logout() {
            router.post(route('logout'));
        },
        openEditModal() {
            const currentUser = this.$page.props.auth.user || {};
            this.form.name = currentUser.name || '';
            this.form.email = currentUser.email || '';
            this.form.phone = currentUser.phone || '';
            this.form.clearErrors();
            this.showEditModal = true;
        },
        closeEditModal() {
            this.showEditModal = false;
            const currentUser = this.$page.props.auth.user || {};
            this.form.name = currentUser.name || '';
            this.form.email = currentUser.email || '';
            this.form.phone = currentUser.phone || '';
            this.form.clearErrors();
        },
        submitProfileUpdate() {
            this.form.patch(route('profile.update'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeEditModal();
                },
                onError: (errors) => {
                    console.error("Profile update failed:", errors);
                }
            });
        },
        goToAdminPanel() {
            if (typeof route === 'function' && route().has('admin.dashboard')) {
                router.get(route('admin.dashboard'));
            } else {
                router.get('/admin/dashboard');
            }
        },
        /**
         * Переключение вкладок с обновлением URL для сохранения состояния при пагинации.
         */
        setActiveTab(tabName) {
            // this.activeTab = tabName; // Обновится через watch за props.filters.tab
            router.get(route('profile.show'), { tab: tabName }, {
                preserveState: true,
                preserveScroll: true,
                replace: true, // Не добавлять в историю браузера каждый клик по табу
            });
        }
    },
    created() {
        // Инициализируем форму данными пользователя при создании компонента
        // (уже делается в data() для useForm)
        // const currentUser = this.$page.props.auth.user || {};
        // this.form.name = currentUser.name || '';
        // this.form.email = currentUser.email || '';
        // this.form.phone = currentUser.phone || '';

        // Устанавливаем активную вкладку из URL при первой загрузке
        // это уже делается в watch с immediate: true
        // this.activeTab = this.$page.props.filters?.tab || 'personal';
    },
}
</script>

<template>
    <Head title="Личный кабинет" />

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">

            <!-- Секция Активных заказов -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4 px-4 sm:px-0">Активные заказы</h2>
                <div v-if="activeOrders.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="order in activeOrders" :key="order.id" class="bg-white shadow-md rounded-lg p-6">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                 <span :class="getStatusClass(order.status)" class="inline-block text-xs font-semibold mr-2 px-2.5 py-0.5 rounded-full">
                                     {{ getStatusText(order.status) }}
                                 </span>
                                <span class="text-sm text-gray-500">от {{ order.created_date }}</span>
                            </div>
                            <div class="text-lg font-bold text-emerald-600">{{ order.number }}</div>
                        </div>
                        <p v-if="order.status?.toLowerCase() === 'ready'" class="text-sm text-blue-700 mb-3">Для получения назовите номер заказа</p>
                        <p v-else-if="order.status?.toLowerCase() === 'preparing'" class="text-sm text-orange-700 mb-3">Ваш заказ готовится</p>

                        <div class="space-y-2 border-t border-b border-gray-200 py-3 my-3">
                            <div v-for="item in order.items" :key="item.id" class="flex justify-between items-start text-sm">
                                <div class="flex-grow pr-2">
                                    <span class="font-medium text-gray-800">{{ item.product_name }}</span>
                                    <span v-if="item.quantity > 1" class="text-gray-500 ml-1">x{{ item.quantity }}</span>
                                    <p v-if="item.options_description" class="text-xs text-gray-500 truncate" :title="item.options_description">{{ item.options_description }}</p>
                                </div>
                                <div class="flex-shrink-0 font-medium text-gray-800">{{ formatPrice(item.price_per_item * item.quantity) }} ₽</div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center font-semibold text-gray-800">
                            <span>Стоимость заказа</span>
                            <span>{{ formatPrice(order.total_price) }} ₽</span>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
                    Нет активных заказов
                </div>
            </section>

            <!-- Секция с Табами -->
            <section>
                <div class="mb-6 border-b border-gray-200 px-4 sm:px-0">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button
                            @click="setActiveTab('personal')"
                            :class="[
                         'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg',
                         activeTab === 'personal'
                             ? 'border-emerald-500 text-emerald-600'
                             : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                         ]">
                            Личные данные
                        </button>
                        <button
                            @click="setActiveTab('history')"
                            :class="[
                         'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg',
                         activeTab === 'history'
                             ? 'border-emerald-500 text-emerald-600'
                             : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                         ]">
                            История заказов
                        </button>
                    </nav>
                </div>

                <div>
                    <!-- Вкладка: Личные данные -->
                    <div v-if="activeTab === 'personal'" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Имя</label>
                                <div class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user?.name || 'Не указано' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Телефон</label>
                                <div class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user?.phone || 'Не указано' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                                <div class="mt-1 p-3 bg-gray-100 rounded-md border border-gray-200 text-gray-800 text-lg">
                                    {{ $page.props.auth.user?.email || 'Не указано' }}
                                </div>
                            </div>
                            <div class="md:col-span-3 flex justify-end items-center mt-4">
                                <button @click="openEditModal"
                                        class="inline-flex items-center px-4 py-2 border border-emerald-500 rounded-md shadow-sm text-sm font-medium text-emerald-600 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    Изменить данные
                                </button>
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <button v-if="$page.props.auth.user?.is_admin"
                                    @click="goToAdminPanel"
                                    type="button"
                                    class="w-full sm:w-auto order-first sm:order-none inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Панель администратора
                            </button>

                            <button @click="logout" type="button" class="w-full sm:w-auto text-red-600 hover:text-red-800 hover:underline focus:outline-none focus:underline sm:ml-auto">
                                Выход из личного кабинета
                            </button>
                        </div>
                    </div>

                    <!-- Вкладка: История заказов -->
                    <div v-if="activeTab === 'history'">
                        <div v-if="orderHistory.data && orderHistory.data.length > 0" class="space-y-6">
                            <details v-for="order in orderHistory.data" :key="order.id" class="bg-white shadow-sm rounded-lg group">
                                <summary class="flex justify-between items-center p-4 cursor-pointer list-none">
                                    <div class="flex items-center space-x-4">
                                         <span :class="getStatusClass(order.status)" class="inline-block text-xs font-semibold mr-2 px-2.5 py-0.5 rounded-full">
                                             {{ getStatusText(order.status) }}
                                         </span>
                                        <span class="text-sm text-gray-500">от {{ order.created_date }}</span>
                                        <span class="text-lg font-bold text-emerald-600">{{ order.number }}</span>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="font-semibold text-gray-800">{{ formatPrice(order.total_price) }} ₽</span>
                                        <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </summary>
                                <div class="border-t border-gray-200 px-6 py-4 space-y-2">
                                    <div v-for="item in order.items" :key="item.id" class="flex justify-between items-start text-sm">
                                        <div class="flex-grow pr-2">
                                            <span class="font-medium text-gray-800">{{ item.product_name }}</span>
                                            <span v-if="item.quantity > 1" class="text-gray-500 ml-1">x{{ item.quantity }}</span>
                                            <p v-if="item.options_description" class="text-xs text-gray-500 truncate" :title="item.options_description">{{ item.options_description }}</p>
                                        </div>
                                        <div class="flex-shrink-0 font-medium text-gray-800">{{ formatPrice(item.price_per_item * item.quantity) }} ₽</div>
                                    </div>
                                </div>
                            </details>
                            <!-- Пагинация для истории заказов -->
                            <Pagination
                                v-if="orderHistory.links && orderHistory.links.length > 3"
                                :links="orderHistory.links"
                                class="mt-6" />
                        </div>
                        <div v-else class="bg-white shadow-sm rounded-lg p-6 text-center text-gray-500">
                            У вас еще нет завершенных заказов.
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Модальное Окно Редактирования Профиля -->
    <div v-if="showEditModal" class="fixed inset-0 z-[70] overflow-y-auto flex items-center justify-center" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal"></div>
        <form @submit.prevent="submitProfileUpdate" class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full p-6">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Редактировать профиль</h3>
                <button @click="closeEditModal" type="button" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Закрыть</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="mt-5 space-y-4">
                <div>
                    <label for="profile_name" class="block text-sm font-medium text-gray-700">Имя</label>
                    <input type="text" id="profile_name" v-model="form.name"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                           :class="{ 'border-red-500': form.errors.name }">
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label for="profile_email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="profile_email" v-model="form.email"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                           :class="{ 'border-red-500': form.errors.email }">
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label for="profile_phone" class="block text-sm font-medium text-gray-700">Телефон</label>
                    <input type="tel" id="profile_phone" v-model="form.phone"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                           :class="{ 'border-red-500': form.errors.phone }">
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                </div>
            </div>
            <div class="mt-6 sm:flex sm:flex-row-reverse">
                <button type="submit"
                        :disabled="form.processing"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                    {{ form.processing ? 'Сохранение...' : 'Сохранить изменения' }}
                </button>
                <button @click="closeEditModal" type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Отмена
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
/* Стили для ProfileShow, если необходимо */
</style>
