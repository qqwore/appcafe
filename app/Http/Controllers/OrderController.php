<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderProducts;

// Используем новую модель
use App\Http\Controllers\CartController;

// Для доступа к calculateExtrasPrice
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Для логирования ошибок

class OrderController extends Controller
{
    // Внедряем CartController для доступа к его публичным методам
    protected $cartController;

    public function __construct(CartController $cartController)
    {
        $this->cartController = $cartController;
    }

    public function store(Request $request)
    {
        $user = $request->user();
        // Получаем элементы корзины С ОПЦИЯМИ и продуктом
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Ваша корзина пуста.']);
        }

        // Получаем допы для расчета цен
        $availableExtras = $this->cartController->getAvailableExtras(); // Используем метод из CartController

        // --- РАСЧЕТ ИТОГОВОЙ СУММЫ ЗАКАЗА НА СЕРВЕРЕ ---
        $totalPrice = 0;
        foreach ($cartItems as $item) {
            if (!$item->product) continue;
            $extrasPrice = $this->cartController->calculateExtrasPrice($item, $availableExtras); // Используем метод
            $totalPrice += ($item->product->price + $extrasPrice) * $item->count;
        }
        // --- Конец расчета ---

        DB::beginTransaction();
        try {
            // 1. Создаем заказ со статусом 'Preparing'
            $order = Order::create([
                'user_id' => $user->id,
                'price' => round($totalPrice, 2),
                'type' => 'Готовится', // Установите ваш тип по умолчанию
                'status' => 'Preparing', // <-- УСТАНАВЛИВАЕМ СТАТУС
            ]);

            // 2. Переносим товары и их опции в детали заказа
            foreach ($cartItems as $cartItem) {
                if (!$cartItem->product) continue;
                $extrasPrice = $this->cartController->calculateExtrasPrice($cartItem, $availableExtras); // Рассчитываем цену допов для этой позиции

                OrderProducts::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'count' => $cartItem->count,
                    // --- СОХРАНЯЕМ ВЫБРАННЫЕ ОПЦИИ ---
                    'sugar_quantity' => $cartItem->sugar_quantity ?? 0,
                    'has_cinnamon' => $cartItem->has_cinnamon ?? false,
                    'milk_extra_id' => $cartItem->milk_extra_id, // null если не выбрано
                    'syrup_extra_id' => $cartItem->syrup_extra_id, // null если не выбрано
                    'has_condensed_milk' => $cartItem->has_condensed_milk ?? false,
                    // --- СОХРАНЯЕМ ЦЕНЫ НА МОМЕНТ ЗАКАЗА ---
                    'unit_price' => $cartItem->product->price, // Базовая цена
                    'extras_price' => round($extrasPrice, 2),  // Цена допов за 1 шт.
                ]);
            }

            // 3. Очищаем корзину пользователя
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            // --- ИЗМЕНЕН РЕДИРЕКТ: На страницу профиля с сообщением об успехе ---
            return redirect()->route('profile.show')->with('message', 'Ваш заказ успешно оформлен!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed for user ' . $user->id . ': ' . $e->getMessage());
            // Редирект обратно в корзину с общей ошибкой
            return redirect()->route('cart.index')->withErrors(['order' => 'Не удалось оформить заказ. Пожалуйста, попробуйте снова.']);
        }
    }

    // Добавьте метод index для будущей истории заказов
    public function index(Request $request)
    {
        // Логика для получения истории заказов пользователя
        // return Inertia::render('Orders/Index', [...]);
    }
}
