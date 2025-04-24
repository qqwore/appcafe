<?php
namespace App\Http\Controllers;

use App\Models\Order; // Используем модель Order
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Отображение страницы профиля пользователя с заказами.
     */
    public function show(Request $request): InertiaResponse
    {
        $user = Auth::user();

        // Статусы активных заказов
        $activeStatuses = ['Preparing', 'Ready'];
        // Статусы завершенных заказов
        $historyStatuses = ['Completed', 'Received', 'Cancelled']; // Добавьте свои статусы

        // Получаем Активные заказы с товарами, продуктами и допами
        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', $activeStatuses)
            ->with([
                'orderProducts' => function ($query) {
                    // Загружаем продукт и названия допов для каждой строки заказа
                    $query->with(['product:id,name', 'milkExtra:id,name', 'syrupExtra:id,name']);
                }
            ])
            ->orderBy('created_at', 'desc') // Сначала новые
            ->get();

        // Получаем Историю заказов (аналогично)
        $orderHistory = Order::where('user_id', $user->id)
            ->whereIn('status', $historyStatuses)
            ->with([
                'orderProducts' => function ($query) {
                    $query->with(['product:id,name', 'milkExtra:id,name', 'syrupExtra:id,name']);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->limit(20) // Ограничиваем историю для производительности
            ->get();


        // Преобразуем данные для Vue (форматируем даты, опции и т.д.)
        $activeOrdersFormatted = $this->formatOrdersForVue($activeOrders);
        $orderHistoryFormatted = $this->formatOrdersForVue($orderHistory);

        return Inertia::render('Profile/Show', [
            'activeOrders' => $activeOrdersFormatted,
            'orderHistory' => $orderHistoryFormatted,
            // Данные пользователя ($page.props.auth.user) передаются из HandleInertiaRequests
        ]);
    }

    /**
     * Вспомогательная функция для форматирования заказов перед передачей во Vue.
     */
    private function formatOrdersForVue($orders)
    {
        return $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'number' => '№' . $order->id, // Формируем номер заказа
                'status' => $order->status,
                // Форматируем дату и время
                'created_date' => $order->created_at->format('d.m H:i'), // Пример: 25.02 15:34
                'total_price' => $order->price,
                'items' => $order->orderProducts->map(function ($item) {
                    // Формируем строку описания опций
                    $optionsDesc = [];
                    if ($item->product?->size?->name) $optionsDesc[] = $item->product->size->name; // Если размер определяется продуктом
                    if ($item->milkExtra) $optionsDesc[] = $item->milkExtra->name;
                    if ($item->syrupExtra) $optionsDesc[] = $item->syrupExtra->name;
                    if ($item->sugar_quantity > 0) $optionsDesc[] = 'Сахар x' . $item->sugar_quantity;
                    if ($item->has_cinnamon) $optionsDesc[] = 'Корица';
                    if ($item->has_condensed_milk) $optionsDesc[] = 'Сгущенка';

                    return [
                        'id' => $item->id, // ID строки order_products
                        'product_name' => $item->product?->name ?? 'Товар удален',
                        'quantity' => $item->count,
                        // Цена за 1 шт с учетом допов = unit_price + extras_price
                        'price_per_item' => round($item->unit_price + $item->extras_price, 2),
                        'options_description' => implode(', ', $optionsDesc), // Строка с опциями
                    ];
                }),
            ];
        });
    }
}
