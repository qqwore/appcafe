<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Убедитесь, что Auth импортирован
use Inertia\Middleware;
use App\Models\Cart; // <-- ДОБАВЬТЕ ИМПОРТ МОДЕЛИ Cart

// Убедитесь, что Ziggy импортирован, ЕСЛИ вы его используете в share()
// Если вы используете только директиву @routes в Blade, этот импорт НЕ НУЖЕН здесь
// use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * Корневой шаблон Blade, который загружается при первом посещении страницы.
     * Обычно 'app', что соответствует resources/views/app.blade.php.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Определяет текущую версию ассетов.
     * Полезно для автоматического сброса кеша браузера при изменениях.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        // Если вы используете Vite, можно использовать Vite::manifestHash()
        // return parent::version($request); // Стандартный вариант
        // В production можно возвращать md5 файла manifest.json для автоматического сброса кеша
        return parent::version($request);
    }

    /**
     * Определяет пропсы (данные), которые будут доступны
     * глобально на всех страницах Inertia по умолчанию.
     * `$page.props` в Vue компонентах.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Используем array_merge, чтобы добавить наши данные к стандартным данным Inertia
        return array_merge(parent::share($request), [

            // --- Данные аутентификации ---
            'auth' => [
                // Передаем информацию о текущем пользователе или null
                'user' => Auth::check() ? [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'phone' => Auth::user()->phone, // Убедитесь, что поле phone есть у модели User
                    // 'is_admin' => Auth::user()->is_admin, // Пример передачи роли
                ] : null,
            ],

            // --- Счетчик товаров в корзине ---
            'cartCount' => function () {
                // Проверяем, что пользователь авторизован
                if (Auth::check()) {
                    // Получаем ID пользователя
                    $userId = Auth::id();
                    // Считаем ОБЩЕЕ количество товаров (поле 'count')
                    // для всех записей корзины этого пользователя.
                    // Если у вас нет поля 'count' в таблице carts,
                    // а каждая строка = 1 товар, используйте ->count() вместо ->sum('count')
                    return Cart::where('user_id', $userId)->sum('count'); // Используем модель Cart
                }
                // Если пользователь не авторизован, в корзине 0 товаров
                return 0;
            },
            // --- Конец счетчика товаров ---

            // --- Передача Ziggy маршрутов (если вы НЕ используете @routes в Blade) ---
            // Если вы ИСПОЛЬЗУЕТЕ @routes в app.blade.php, этот блок НЕ НУЖЕН здесь,
            // так как @routes уже создает глобальный объект Ziggy.
            // Если вы НЕ используете @routes, раскомментируйте этот блок:
            /*
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            */

            // --- Flash сообщения (пример) ---
            // Позволяет передавать одноразовые сообщения из сессии
            'flash' => [
                'message' => fn () => $request->session()->get('message'), // Сообщение об успехе
                'error' => fn () => $request->session()->get('error'),   // Сообщение об ошибке
                // Передаем флаг успеха заказа
                'orderSuccess' => fn () => (bool)$request->session()->get('orderSuccess'),
            ],

        ]);
    }
}
