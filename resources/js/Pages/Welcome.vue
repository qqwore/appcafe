<template>
    <div>
        <!-- Секция 1: Hero -->
        <section class="relative w-full bg-gradient-to-r from-stone-800 to-emerald-400 min-h-[60vh] max-h-[700px] overflow-hidden flex items-center justify-center">
            <!-- Добавили items-center justify-center сюда -->

            <!-- Внутренний контейнер для ограничения ширины и отступов -->
            <!-- Убираем h-full, высота будет по контенту. Добавляем max-w и отступы -->
            <div class="flex flex-col gap-[7%] md:flex-row w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Левая часть: Текст -->
                <!-- Убрали горизонтальные отступы (px), так как они теперь на родителе -->
                <!-- Добавили вертикальные отступы (py) для выравнивания с изображением -->
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

                <!-- Правая часть: Изображение -->
                <!-- Убрали px, добавили py -->
                <div class="w-full md:w-3/5 order-1 md:order-2 flex items-center justify-center py-12 md:py-16">
                    <!-- Замените src на реальный путь к вашему изображению -->
                    <img src="/images/IMG_2176.JPG" alt="Интерьер кофейни"
                         class="w-full max-w-xl lg:max-w-2xl aspect-[4/3] object-cover rounded-2xl shadow-xl">
                    <!-- Добавили aspect-[4/3] и object-cover -->
                </div>

            </div>
        </section>

        <!-- Секция 2: Меню на сегодня -->
        <section id="menu" class="py-16 lg:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-12">
                    Меню на сегодня
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Карточка товара 1 -->
                    <div v-for="product in products" class="bg-gray-50 rounded-lg shadow-md overflow-hidden p-6 flex flex-col">
                        <div class="relative mb-4 flex-grow">
                            <!-- Замените src -->
                            <img :src="product.photo" alt="Ролл Цезарь"
                                 class="w-full h-48 object-cover rounded-md">
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-1">{{ product.name }}</h3>
                        <p class="text-lg font-bold text-gray-700 mb-4">{{ Math.floor(product.price) }} ₽</p>
                        <button
                            class="mt-auto w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center cursor-pointer">
                            <!-- Иконка корзины (SVG или Font Icon) -->
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path>
                                <path fill-rule="evenodd"
                                      d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            В корзину
                        </button>
                    </div>

<!--                    &lt;!&ndash; Карточка товара 2 &ndash;&gt;-->
<!--                    <div class="bg-gray-50 rounded-lg shadow-md overflow-hidden p-6 flex flex-col">-->
<!--                        <div class="relative mb-4 flex-grow">-->
<!--                            &lt;!&ndash; Замените src &ndash;&gt;-->
<!--                            <img src="/images/bruschetta.jpg" alt="Брускетта с ветчиной и сыром"-->
<!--                                 class="w-full h-48 object-cover rounded-md">-->
<!--                        </div>-->
<!--                        <h3 class="text-xl font-semibold text-gray-800 mb-1">Брускетта с ветчиной и сыром</h3>-->
<!--                        <p class="text-lg font-bold text-gray-700 mb-4">190 ₽</p>-->
<!--                        <button-->
<!--                            class="mt-auto w-full bg-stone-600 hover:bg-stone-700 text-white font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center">-->
<!--                            &lt;!&ndash; Иконка корзины &ndash;&gt;-->
<!--                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"-->
<!--                                 xmlns="http://www.w3.org/2000/svg">-->
<!--                                <path-->
<!--                                    d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path>-->
<!--                                <path fill-rule="evenodd"-->
<!--                                      d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z"-->
<!--                                      clip-rule="evenodd"></path>-->
<!--                            </svg>-->
<!--                            В корзину-->
<!--                        </button>-->
<!--                    </div>-->

<!--                    &lt;!&ndash; Карточка товара 3 &ndash;&gt;-->
<!--                    <div class="bg-gray-50 rounded-lg shadow-md overflow-hidden p-6 flex flex-col">-->
<!--                        <div class="relative mb-4 flex-grow">-->
<!--                            &lt;!&ndash; Замените src &ndash;&gt;-->
<!--                            <img src="/images/syrniki.jpg" alt="Сырники" class="w-full h-48 object-cover rounded-md">-->
<!--                        </div>-->
<!--                        <h3 class="text-xl font-semibold text-gray-800 mb-1">Сырники</h3>-->
<!--                        <p class="text-lg font-bold text-gray-700 mb-4">от 60 ₽</p>-->
<!--                        <button-->
<!--                            class="mt-auto w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center">-->
<!--                            &lt;!&ndash; Иконка корзины &ndash;&gt;-->
<!--                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"-->
<!--                                 xmlns="http://www.w3.org/2000/svg">-->
<!--                                <path-->
<!--                                    d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path>-->
<!--                                <path fill-rule="evenodd"-->
<!--                                      d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z"-->
<!--                                      clip-rule="evenodd"></path>-->
<!--                            </svg>-->
<!--                            В корзину-->
<!--                        </button>-->
<!--                    </div>-->

<!--                    &lt;!&ndash; Карточка товара 4 &ndash;&gt;-->
<!--                    <div class="bg-gray-50 rounded-lg shadow-md overflow-hidden p-6 flex flex-col">-->
<!--                        <div class="relative mb-4 flex-grow">-->
<!--                            &lt;!&ndash; Замените src &ndash;&gt;-->
<!--                            <img src="/images/cheesecake.jpg" alt="Чизкейк" class="w-full h-48 object-cover rounded-md">-->
<!--                        </div>-->
<!--                        <h3 class="text-xl font-semibold text-gray-800 mb-1">Чизкейк</h3>-->
<!--                        <p class="text-lg font-bold text-gray-700 mb-4">130 ₽</p>-->
<!--                        <button-->
<!--                            class="mt-auto w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center justify-center">-->
<!--                            &lt;!&ndash; Иконка корзины &ndash;&gt;-->
<!--                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"-->
<!--                                 xmlns="http://www.w3.org/2000/svg">-->
<!--                                <path-->
<!--                                    d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H4.72l-.21-.842A1 1 0 003 1z"></path>-->
<!--                                <path fill-rule="evenodd"-->
<!--                                      d="M6 16a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z"-->
<!--                                      clip-rule="evenodd"></path>-->
<!--                            </svg>-->
<!--                            В корзину-->
<!--                        </button>-->
<!--                    </div>-->
              </div>

                <!-- Кнопка Подробнее -->
                <div class="mt-12 text-center">
                    <!-- Замените href на ссылку на полную страницу меню -->
                    <a href="/menu"
                       class="inline-flex items-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-3 rounded-full transition duration-150 ease-in-out">
                        Подробнее
                        <!-- Иконка стрелки -->
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
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-center">
                <!-- Карта -->
                <!-- Этот div задает общую форму, тень и скругление контейнера карты -->
                <div class="w-full rounded-lg shadow-md overflow-hidden">
                    <iframe
                        class="w-full h-[500px] border-0 rounded-lg"
                    src="https://yandex.ru/map-widget/v1/?um=constructor%3Add89e6fba7d93e4c0fa7c5380bb3c63c6b545a2be85058b7bc65f95a13133b27&amp;source=constructor"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                    <!-- Убраны атрибуты width, height, frameborder -->
                    </iframe>
                    <!-- Убрали лишний комментарий -->
                </div>
                <!-- Адрес -->
                <div class="text-center flex flex-col items-center gap-6 justify-center">
                    <h3 class="text-3xl font-bold text-gray-800 mb-4">Мы находимся по адресу</h3>
                    <!-- Увеличил скругление для адреса -->
                    <div class="inline-block bg-stone-700 text-white font-semibold px-6 py-3 rounded-4xl mb-6 w-max">
                        пр-т Ленина 87, 3 корпус ЮУрГУ, 3 этаж
                    </div>
                    <div class="flex justify-center md:justify-start">
                        <!-- Замените src на иконку стаканчика -->
                        <img src="/images/coffee-cup-icon.svg" alt="Стаканчик кофе" class="w-40 h-auto">
                    </div>
                </div>
            </div>
        </section>

        <!-- Футер: Обычно является частью Layout -->
        <!-- Если у вас нет Layout, раскомментируйте и настройте этот блок -->
        <!--
        <footer class="bg-stone-800 text-stone-400 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl font-bold text-center">
                        <span class="text-stone-300">Интеллект</span><span class="text-emerald-400">Ум</span>
                    </h1>
                </div>
                <div class="text-center md:text-left mb-4 md:mb-0">
                    <p><i class="fas fa-map-marker-alt mr-2"></i>пр-т Ленина 87, 3 корпус ЮУрГУ, 3 этаж</p>
                </div>
                <div class="text-center md:text-right">
                    <p>Мы в телеграм: <a href="https://t.me/IntelektUm_susu" target="_blank" class="text-emerald-400 hover:text-emerald-300 hover:underline">@IntelektUm_susu</a></p>
                </div>
            </div>
        </footer>
        -->

    </div>
</template>

<script>
import AppLayout from '../Layouts/AppLayout.vue'
// Пока для статической страницы скрипт может быть простым
export default {
    name: 'Welcome',
    layout: AppLayout,
    props:{
        products: Array,
    },
    mounted() {
        console.log(this.products)
    }
    // Если эта страница использует общий Layout:
    // layout: import('@/Layouts/AppLayout.vue').then(m => m.default) // Укажите ваш Layout
}
</script>

<style scoped>
/* Специфичные стили для этой страницы, если Tailwind не справляется */
.min-h-\[60vh\] { /* Пример пользовательской высоты, если нужно */
    min-height: 60vh;
}
</style>
