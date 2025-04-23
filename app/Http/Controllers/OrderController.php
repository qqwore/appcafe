<?php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderProducts; // Ваша модель для связи заказа и продуктов
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Ваша корзина пуста.']);
        }

        // --- РАСЧЕТ ИТОГОВОЙ СУММЫ ЗАКАЗА НА СЕРВЕРЕ ---
        $totalPrice = 0;
        $availableExtras = app(CartController::class)->getAvailableExtras(); // Получаем допы (или передаем как сервис)
        foreach($cartItems as $item) {
            if(!$item->product) continue; // Пропускаем если продукт удален
            $extrasPrice = app(CartController::class)->calculateExtrasPrice($item, $availableExtras);
            $totalPrice += ($item->product->price + $extrasPrice) * $item->count;
        }
        // --- Конец расчета ---

        // Используем транзакцию для атомарности
        DB::beginTransaction();
        try {
            // 1. Создаем заказ
            $order = Order::create([
                'user_id' => $user->id,
                'price' => round($totalPrice, 2),
                'type' => 'pickup', // Или другой тип, если есть
                // Добавьте другие поля заказа (статус и т.д.)
            ]);

            // 2. Переносим товары из корзины в детали заказа
            foreach ($cartItems as $cartItem) {
                if(!$cartItem->product) continue;
                // Создаем запись в order_products
                OrderProducts::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'count' => $cartItem->count,

                     'sugar_quantity' => $cartItem->sugar_quantity,
                     'has_cinnamon' => $cartItem->has_cinnamon,
                     'milk_extra_id' => $cartItem->milk_extra_id,
                     'syrup_extra_id' => $cartItem->syrup_extra_id,
                     'has_condensed_milk' => $cartItem->has_condensed_milk,
                     'unit_price' => $cartItem->product->price,
                     'extras_price' => $this->calculateExtrasPrice($cartItem, $availableExtras),
                ]);
            }

            // 3. Очищаем корзину пользователя
            Cart::where('user_id', $user->id)->delete();

            DB::commit(); // Фиксируем транзакцию

            // Редирект на корзину с флагом успеха
            return redirect()->route('cart.index')->with('orderSuccess', true);

        } catch (\Exception $e) {
            DB::rollBack(); // Откатываем транзакцию при ошибке
            Log::error('Order placement failed: ' . $e->getMessage());
            return redirect()->route('cart.index')->withErrors(['order' => 'Не удалось оформить заказ. Попробуйте снова.']);
        }
    }
}
