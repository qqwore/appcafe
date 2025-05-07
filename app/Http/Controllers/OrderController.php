<?php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderProducts;
use App\Http\Controllers\CartController; // Для доступа к вспомогательным методам
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia; // Если будете делать метод index для истории заказов
use Inertia\Response as InertiaResponse; // Если будете делать метод index
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    protected CartController $cartController; // Типизация для внедрения

    public function __construct(CartController $cartController)
    {
        $this->cartController = $cartController;
    }

    /**
     * Создание нового заказа из корзины пользователя.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            // Это не должно произойти из-за middleware 'auth', но на всякий случай
            return redirect()->route('login');
        }

        $cartItems = Cart::where('user_id', $user->id)
            ->with(['product' => function($query){ // Загружаем продукт с его флагом is_stock_managed
                $query->select('id', 'name', 'price', 'count', 'category_id', 'photo', 'description', 'size_id') // Явно выбираем поля
                ->with('size:id,volume'); // Загружаем размер, если есть
            }])
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Ваша корзина пуста.']);
        }

        $availableExtras = $this->cartController->getAvailableExtras();

        // --- ПРОВЕРКА ОСТАТКОВ ПЕРЕД ТРАНЗАКЦИЕЙ ---
        foreach ($cartItems as $cartItem) {
            if (!$cartItem->product) { // Если продукт был удален из БД пока был в корзине
                return redirect()->route('cart.index')->withErrors(['cart' => "Товар '{$cartItem->product_id}' больше не доступен."]);
            }
            // Проверяем остаток только для "штучных" товаров
            if ($cartItem->product->is_stock_managed && $cartItem->product->count < $cartItem->count) {
                return redirect()->route('cart.index')->withErrors(['cart' => "Недостаточно товара '{$cartItem->product->name}' на складе. Доступно: {$cartItem->product->count} шт., заказано: {$cartItem->count}."]);
            }
        }
        // --- КОНЕЦ ПРОВЕРКИ ОСТАТКОВ ---


        // --- РАСЧЕТ ИТОГОВОЙ СУММЫ ЗАКАЗА НА СЕРВЕРЕ ---
        $totalPrice = 0;
        foreach($cartItems as $item) {
            // Продукт уже должен быть загружен с ценой
            $extrasPrice = $this->cartController->calculateExtrasPrice($item, $availableExtras);
            $totalPrice += ($item->product->price + $extrasPrice) * $item->count;
        }
        // --- Конец расчета ---

        DB::beginTransaction();
        try {
            // 1. Создаем заказ
            $order = Order::create([
                'user_id' => $user->id,
                'price' => round($totalPrice, 2),
                'type' => 'Оформлен', // Или другой ваш начальный тип
                'status' => 'Preparing',
            ]);

            // 2. Переносим товары и их опции в детали заказа И ВЫЧИТАЕМ ОСТАТКИ
            foreach ($cartItems as $cartItem) {
                // Продукт должен быть уже загружен
                $extrasPriceForThisItem = $this->cartController->calculateExtrasPrice($cartItem, $availableExtras);

                OrderProducts::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'count' => $cartItem->count,
                    'sugar_quantity' => $cartItem->sugar_quantity ?? 0,
                    'has_cinnamon' => $cartItem->has_cinnamon ?? false,
                    'milk_extra_id' => $cartItem->milk_extra_id,
                    'syrup_extra_id' => $cartItem->syrup_extra_id,
                    'has_condensed_milk' => $cartItem->has_condensed_milk ?? false,
                    'unit_price' => $cartItem->product->price,
                    'extras_price' => round($extrasPriceForThisItem, 2),
                ]);

                // --- ВЫЧИТАНИЕ СО СКЛАДА ДЛЯ ШТУЧНЫХ ТОВАРОВ ---
                if ($cartItem->product->is_stock_managed) {
                    // Используем decrement для безопасного уменьшения
                    // Product::find($cartItem->product_id)->decrement('count', $cartItem->count); // Можно так, чтобы точно взять актуальный
                    $cartItem->product->decrement('count', $cartItem->count); // Или так, если $cartItem->product актуален
                }
                // --- КОНЕЦ ВЫЧИТАНИЯ ---
            }

            // 3. Очищаем корзину пользователя
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return redirect()->route('profile.show')->with('message', 'Ваш заказ успешно оформлен!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed for user ' . $user->id . ': ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            // Возвращаем более информативное сообщение об ошибке, если возможно
            return redirect()->route('cart.index')->withErrors(['order' => 'Не удалось оформить заказ из-за внутренней ошибки. Пожалуйста, попробуйте снова.']);
        }
    }

    /**
     * Отображение истории заказов пользователя (пример).
     */
    // public function index(Request $request): InertiaResponse
    // {
    //     $user = $request->user();
    //     $orders = Order::where('user_id', $user->id)
    //                      ->with('orderProducts.product')
    //                      ->orderBy('created_at', 'desc')
    //                      ->paginate(10);

    //     // Здесь нужна будет функция форматирования, похожая на formatOrdersForAdmin
    //     // $formattedOrders = $this->formatUserOrders($orders);

    //     return Inertia::render('Profile/OrderHistory', [ // Предполагаем такой компонент
    //         'orders' => $orders, // Передаем пагинированные (и, возможно, форматированные) заказы
    //     ]);
    // }
}
