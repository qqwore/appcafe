<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Используется
use Illuminate\Support\Facades\Cache;

// Если используете кеш для допов
use Inertia\Middleware;
use App\Models\Cart;

// Используется
use App\Models\Product;

// Используется

class HandleInertiaRequests extends Middleware
{
    /**
     * Корневой шаблон Blade.
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Версия ассетов.
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        // Используйте parent::version() или ваш метод версионирования
        return parent::version($request);
    }

    /**
     * Глобальные пропсы, передаваемые во все Vue компоненты.
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Оптимизация: Получаем данные корзины один раз, если пользователь авторизован
        $user = Auth::user();
        $cartItems = null;
        if ($user) {
            // Получаем ID элемента корзины, ID продукта и количество.
            // Также подгружаем базовую цену продукта для расчета total_default_price
            $cartItems = Cart::where('user_id', $user->id)
                ->with('product:id,price,name') // Загружаем ID, цену и имя продукта
                ->get(['id', 'product_id', 'count']); // Выбираем нужные поля из carts
        }

        // Возвращаем массив с глобальными данными
        return array_merge(parent::share($request), [

            // --- Данные аутентификации ---
            'auth' => fn() => [
                'user' => $user ? [ // Используем уже полученного $user
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_admin' => (bool) Auth::user()->is_admin, // (bool) для явного преобразования
                ] : null,
            ],

            // --- Суммарные данные корзины (для AppLayout) ---
            'cart' => function () use ($cartItems) { // Используем $cartItems
                if (!$cartItems) {
                    return ['count' => 0, 'total_default_price' => 0];
                }

                $totalQuantity = $cartItems->sum('count');
                $totalDefaultPrice = $cartItems->reduce(function ($sum, $item) {
                    $price = $item->product->price ?? 0; // Базовая цена продукта
                    return $sum + ($price * $item->count);
                }, 0);

                return [
                    'count' => (int)$totalQuantity,
                    'total_default_price' => round($totalDefaultPrice, 2),
                ];
            },
            // --- Конец суммарных данных ---

            // --- НОВЫЙ БЛОК: Детальные данные корзины (для Menu.vue, Cart.vue) ---
            'cart_details' => function () use ($cartItems) { // Используем $cartItems
                if (!$cartItems) {
                    return ['items' => []]; // Возвращаем пустой массив items
                }

                // Преобразуем коллекцию $cartItems в нужный формат для Vue
                $items = $cartItems->map(function ($item) {
                    // Проверяем, что продукт действительно загрузился
                    if (!$item->product) return null;

                    return [
                        'id' => $item->id, // ID строки в таблице carts (cart_item_id)
                        'quantity' => $item->count, // Количество
                        'product' => [ // Минимальная информация о продукте
                            'id' => $item->product_id, // ID продукта/вариации
                            'name' => $item->product->name, // Имя продукта для информации
                        ]
                    ];
                })->filter()->values(); // filter() удалит null, values() сбросит ключи

                return [
                    'items' => $items, // Передаем массив под ключом 'items'
                ];
            },
            // --- Конец детальных данных ---


            // --- Flash сообщения ---
            'flash' => fn() => [
                'message' => $request->session()->get('message'),
                'error' => $request->session()->get('error'),
                'orderSuccess' => (bool)$request->session()->get('orderSuccess'),
                'can_undo_stock_update' => (bool)$request->session()->get('can_undo_stock_update'), // Получаем из сессии
            ],


        ]);
    }
}
