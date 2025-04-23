<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache; // Если используете кеш для допов
use Inertia\Middleware;
use App\Models\Cart; // Импорт модели Cart
use App\Models\Product; // Импорт модели Product (для связи)

// use Illuminate\Support\Facades\Vite; // Убедитесь, что это закомментировано или удалено, если не используется

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        // Используем стандартный вариант или ваш рабочий вариант
        return parent::version($request);
    }

    /**
     * Определяет пропсы, которые будут доступны глобально.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [

            // --- Данные аутентификации ---
            'auth' => fn () => [ // Используем замыкание для ленивой загрузки
                'user' => Auth::check() ? [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'phone' => Auth::user()->phone,
                ] : null,
            ],

            // --- Данные корзины для хедера ---
            'cart' => function () {
                if (!Auth::check()) {
                    return [
                        'count' => 0, // Общее кол-во товаров (сумма count)
                        'total_default_price' => 0, // Общая сумма БАЗОВЫХ цен
                    ];
                }

                // Получаем все элементы корзины пользователя с базовой ценой продукта
                $cartItems = Cart::where('user_id', Auth::id())
                    // Подгружаем только ID и цену связанного продукта
                    ->with('product:id,price')
                    ->get(['id', 'user_id', 'product_id', 'count']); // Выбираем нужные поля

                $totalQuantity = $cartItems->sum('count'); // Суммируем количество
                $totalDefaultPrice = $cartItems->reduce(function ($sum, $item) {
                    // Умножаем базовую цену продукта на количество в корзине
                    // Проверяем, что продукт загрузился (не был удален)
                    $price = $item->product->price ?? 0; // Базовая цена или 0
                    return $sum + ($price * $item->count);
                }, 0); // Начальное значение суммы = 0

                return [
                    'count' => (int) $totalQuantity,
                    'total_default_price' => round($totalDefaultPrice, 2),
                ];
            },
            // --- Конец данных корзины ---


            // --- Flash сообщения ---
            'flash' => fn () => [ // Используем замыкание
                'message' => $request->session()->get('message'),
                'error' => $request->session()->get('error'),
                'orderSuccess' => (bool)$request->session()->get('orderSuccess'),
            ],

            // --- Ziggy (если НЕ используете @routes в Blade) ---
            /*
            'ziggy' => fn () => array_merge((new \Tightenco\Ziggy\Ziggy)->toArray(), [
                'location' => $request->url(),
            ]),
            */

        ]);
    }
}
