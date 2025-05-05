<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;

// Добавляем необходимые модели, если они используются внутри класса
use App\Models\Product;

// Используется для связи в with()
use App\Models\Extras;

// Используется для связи в with()
use App\Models\Size;

// Используется для связи в with()
use App\Models\User;

// Используется для связи в with()
use App\Models\OrderProducts;

// Используется для связи в with()
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

// Используем псевдоним
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// Тип для пагинации

class AdminOrderController extends Controller
{
    /**
     * Отображение списка заказов с фильтрацией по статусу.
     */
    public function index(Request $request): InertiaResponse
    {
        // Определяем статусы для вкладок
        $statuses = [
            'new' => ['Preparing'], // 'Новые' - это те, что готовятся
            'ready' => ['Ready'],   // 'Готовы к выдаче'
            'completed' => ['Completed', 'Received', 'Cancelled'], // 'Выполненные' (включая отмененные и полученные)
        ];

        // Получаем текущую вкладку из запроса, по умолчанию 'new'
        $currentTab = $request->input('tab', 'new');
        // Проверяем, что запрошенная вкладка существует в нашем списке
        if (!array_key_exists($currentTab, $statuses)) {
            $currentTab = 'new'; // Возвращаемся к 'new', если вкладка некорректна
        }

        // Получаем заказы для текущей вкладки
        $ordersQuery = Order::whereIn('status', $statuses[$currentTab])
            ->with([
                // Загружаем только ID и имя пользователя
                'user:id,name',
                // Загружаем элементы заказа
                'orderProducts' => function ($query) {
                    // Для каждого элемента заказа загружаем связанный продукт (только ID и имя)
                    // и связанные допы (только ID и имя)
                    $query->with([
                        'product:id,name,size_id', // Добавили size_id для получения размера
                        'product.size:id,volume', // Загружаем размер через продукт
                        'milkExtra:id,name',
                        'syrupExtra:id,name'
                    ]);
                }
            ]);
            if ($currentTab === 'new') {
                // Для вкладки "Новые" сортируем старые сначала
                $ordersQuery->orderBy('created_at', 'asc');
            } else {
                // Для остальных вкладок (Готовы, Выполненные) сортируем новые сначала
                $ordersQuery->orderBy('created_at', 'desc');
            }

        // Применяем пагинацию
        $orders = $ordersQuery->paginate(15)->withQueryString(); // withQueryString() сохраняет параметры URL (например, ?tab=ready)

        // Форматируем пагинированные заказы для Vue
        $formattedOrders = $this->formatOrdersForAdmin($orders);

        // Передаем данные в Vue компонент
        return Inertia::render('Admin/Orders/Index', [
            'orders' => $formattedOrders,          // Пагинированные и форматированные заказы
            'currentTab' => $currentTab,          // Активная вкладка
            'tabs' => array_keys($statuses),      // Список доступных вкладок
            'filters' => $request->only(['tab']), // Фильтры для сохранения состояния пагинации
        ]);
    }

    /**
     * Обновление статуса заказа.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $newStatus = $request->input('status');
        // Список всех возможных статусов, которые может установить админ
        $allowedStatuses = ['Preparing', 'Ready', 'Completed', 'Received', 'Cancelled'];

        // Валидация входного статуса
        if (!$newStatus || !in_array($newStatus, $allowedStatuses)) {
            return redirect()->back()->withErrors(['status' => 'Недопустимый статус заказа.']);
        }

        // --- Логика разрешенных переходов статусов ---
        $canTransition = false;
        // Из 'Preparing' можно перейти в 'Ready' или 'Cancelled'
        if ($order->status === 'Preparing' && in_array($newStatus, ['Ready', 'Cancelled'])) {
            $canTransition = true;
        } // Из 'Ready' можно перейти в 'Completed', 'Received' или 'Cancelled'
        elseif ($order->status === 'Ready' && in_array($newStatus, ['Completed', 'Received', 'Cancelled'])) {
            $canTransition = true;
        }
        // Добавьте другие разрешенные переходы, если нужно (например, из Cancelled обратно?)
        // elseif (...) { $canTransition = true; }

        if ($canTransition) {
            $order->status = $newStatus;
            $order->save();
            return redirect()->back()->with('message', "Статус заказа №{$order->id} изменен на '{$newStatus}'.");
        } else {
            // Если переход не разрешен
            return redirect()->back()->withErrors(['status' => "Невозможно изменить статус заказа с '{$order->status}' на '{$newStatus}'."]);
        }
    }

    /**
     * Вспомогательная функция форматирования заказов для передачи во Vue.
     * Работает с пагинированной коллекцией.
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $orders
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function formatOrdersForAdmin(LengthAwarePaginator $orders): LengthAwarePaginator
    {
        // Используем ->through() для модификации данных в пагинированной коллекции
        return $orders->through(function (Order $order) { // Явно указываем тип Order
            return [
                'id' => $order->id,
                'number' => '№' . $order->id,
                'status' => $order->status,
                'created_date' => $order->created_at->format('d.m H:i'),
                'user_name' => $order->user?->name ?? 'N/A',
                'total_price' => $order->price,
                'items' => $order->orderProducts->map(function (OrderProducts $item) { // Явно указываем тип OrderProducts
                    // Проверяем, существует ли продукт
                    if (!$item->product) {
                        return [
                            'id' => $item->id,
                            'product_name' => '[Товар удален]',
                            'quantity' => $item->count,
                            'price_per_item' => 0,
                            'options_description' => 'Нет данных',
                        ];
                    }

                    // Собираем описание опций
                    $optionsDesc = [];
                    // Получаем размер через загруженное отношение product.size
                    if ($item->product->size?->volume) $optionsDesc[] = $item->product->size->volume;
                    if ($item->milkExtra?->name) $optionsDesc[] = $item->milkExtra->name;
                    if ($item->syrupExtra?->name) $optionsDesc[] = $item->syrupExtra->name;
                    if ($item->sugar_quantity > 0) $optionsDesc[] = 'Сахар x' . $item->sugar_quantity;
                    if ($item->has_cinnamon) $optionsDesc[] = 'Корица';
                    if ($item->has_condensed_milk) $optionsDesc[] = 'Сгущенка';

                    return [
                        'id' => $item->id, // ID строки в order_products
                        'product_name' => $item->product->name,
                        'quantity' => $item->count,
                        // Цена за 1 шт с учетом допов
                        'price_per_item' => round(($item->unit_price ?? 0) + ($item->extras_price ?? 0), 2),
                        'options_description' => implode(', ', $optionsDesc),
                    ];
                }), // Конец map для items
            ]; // Конец массива для одного заказа
        }); // Конец through для пагинации
    } // Конец метода formatOrdersForAdmin

} // Конец класса AdminOrderController
