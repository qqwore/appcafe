<script>
// Используем Options API
import AppLayout from '@/Layouts/AppLayout.vue'; // Используем alias или '../Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3';

export default {
    name: 'AboutPage',
    components: { Head },
    layout: AppLayout,
    // Эта страница может не требовать пропсов от контроллера
}
</script>

<template>
    <Head title="О нас" />

    <div class="bg-white py-16 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Заголовок -->
            <div class="text-center mb-12 lg:mb-16">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900">О кофейне "ИнтеллектУм"</h1>
                <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">
                    Ваш островок уюта, вкусного кофе и свежих идей в сердце университета.
                </p>
            </div>

            <!-- Секция с картинкой и текстом -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-16 items-center mb-16 lg:mb-24">
                <!-- Изображение -->
                <div class="rounded-lg overflow-hidden shadow-lg aspect-video">
                    <!-- Замените на ваше фото кофейни -->
                    <img src="/images/IMG_2176.JPG" alt="Интерьер кофейни" class="w-full h-full object-cover">
                </div>
                <!-- Текст -->
                <div>
                    <h2 class="text-2xl lg:text-3xl font-semibold text-gray-800 mb-4">Наша философия</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Мы верим, что чашка отличного кофе – это больше, чем просто напиток. Это ритуал, момент для перезагрузки, источник вдохновения и возможность для приятного общения. "ИнтеллектУм" создан как место, где студенты, преподаватели и гости ЮУрГУ могут не только насладиться качественными напитками и едой, но и найти комфортное пространство для учебы, работы или отдыха.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Мы тщательно отбираем кофейные зерна, используем свежие ингредиенты для нашей выпечки и закусок и стараемся создать атмосферу, в которой рождаются лучшие мысли и проходят самые теплые встречи.
                    </p>
                </div>
            </div>

            <!-- Секция с преимуществами (пример) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
                <div class="p-6 bg-emerald-50 rounded-lg">
                    <svg class="w-12 h-12 text-emerald-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path></svg>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Качественный кофе</h3>
                    <p class="text-gray-600 text-sm">Свежеобжаренные зерна и профессиональное приготовление.</p>
                </div>
                <div class="p-6 bg-emerald-50 rounded-lg">
                    <svg class="w-12 h-12 text-emerald-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.975-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Уютная атмосфера</h3>
                    <p class="text-gray-600 text-sm">Идеально для учебы, работы и встреч с друзьями.</p>
                </div>
                <div class="p-6 bg-emerald-50 rounded-lg">
                    <svg class="w-12 h-12 text-emerald-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Дружелюбный персонал</h3>
                    <p class="text-gray-600 text-sm">Мы всегда рады помочь и подсказать.</p>
                </div>
            </div>

        </div>
    </div>
</template>
