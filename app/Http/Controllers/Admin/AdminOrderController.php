<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

// Предполагается для связей
use App\Models\Extras;

// Предполагается для связей
use App\Models\Size;

// Предполагается для связей
use App\Models\User;

// Предполагается для связей
use App\Models\OrderProducts;

// Предполагается для связей
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// Для типа пагинации

class AdminOrderController extends Controller
{
    /**
     * Отображение списка заказов с фильтрацией по статусу или поиск заказа.
     */
    public function index(Request $request): InertiaResponse
    {

        $statuses = [
            'new' => ['Preparing'],
            'ready' => ['Ready'],
            'completed' => ['Completed', 'Received', 'Cancelled'],
        ];
        $tabs = array_merge(array_keys($statuses), ['search']);

        $currentTab = $request->input('tab', 'new');
        $searchQuery = $request->input('search_query', null);
        $searchedOrder = null;
        $orders = null; // Инициализируем как null

        if (!in_array($currentTab, $tabs)) {
            $currentTab = 'new';
        }

        $baseQuery = Order::query(); // Базовый запрос

        if ($currentTab === 'search' && $searchQuery) {
            // --- ОТЛАДКА ---
            // dd($searchQuery, ltrim($searchQuery, '#№ '));
            // --- КОНЕЦ ОТЛАДКИ ---
            $orderId = ltrim($searchQuery, '#№ ');
            if (is_numeric($orderId)) {
                $foundOrder = $baseQuery->with([ // Применяем with к $baseQuery
                    'user:id,name',
                    'orderProducts' => function ($query) {
                        $query->with([
                            'product:id,name,size_id',
                            'product.size:id,volume',
                            'milkExtra:id,name',
                            'syrupExtra:id,name']);
                    }
                ])
                    ->find($orderId);
                if ($foundOrder) {
                    // Передаем коллекцию с одним элементом для форматирования
                    $searchedOrder = $this->formatOrdersForAdmin(collect([$foundOrder]))->first();
                }
            }
            // Для поиска пагинация основного списка не нужна
        } elseif (array_key_exists($currentTab, $statuses)) {
            $ordersQuery = $baseQuery->whereIn('status', $statuses[$currentTab]) // Применяем whereIn к $baseQuery
            ->with([
                'user:id,name',
                'orderProducts' => function ($query) {
                    $query->with([
                        'product:id,name,size_id',
                        'product.size:id,volume',
                        'milkExtra:id,name',
                        'syrupExtra:id,name'
                    ]);
                }
            ]);

            if ($currentTab === 'new') {
                $ordersQuery->orderBy('created_at', 'asc');
            } else {
                $ordersQuery->orderBy('created_at', 'desc');
            }
            $orders = $ordersQuery->paginate(6)->withQueryString(); // Пагинация
            if ($orders) { // Форматируем, только если есть результат
                $orders = $this->formatOrdersForAdmin($orders);
            }
        } else {
            // На случай, если currentTab = 'search', но searchQuery пуст,
            // или для любой другой непредвиденной вкладки
            $orders = Order::where('id', 0)->paginate(6); // Пустая пагинация
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders, // Может быть пагинированным объектом или null
            'searchedOrder' => $searchedOrder,
            'currentTab' => $currentTab,
            'tabs' => $tabs,
            'filters' => ['tab' => $request->input('tab'), 'search_query' => $request->input('search_query')],
        ]);
    }

    /**
     * Обновление статуса заказа.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $newStatus = $request->input('status');
        $allowedStatuses = ['Preparing', 'Ready', 'Completed', 'Received', 'Cancelled'];

        if (!$newStatus || !in_array($newStatus, $allowedStatuses)) {
            return redirect()->back()->withErrors(['status' => 'Недопустимый статус заказа.']);
        }

        $canTransition = false;
        if (($order->status === 'Preparing' && $newStatus === 'Ready') ||
            ($order->status === 'Ready' && in_array($newStatus, ['Completed', 'Received']))) {
            $canTransition = true;
        } elseif (($order->status === 'Preparing' || $order->status === 'Ready') && $newStatus === 'Cancelled') {
            $canTransition = true; // Разрешаем отмену из Готовится или Готов
        }


        if ($canTransition) {
            $order->status = $newStatus;
            $order->save();
            $russianStatusText = $this->getRussianStatusText($newStatus);
            return redirect()->back()->with('message', "Статус заказа №{$order->id} изменен на '{$russianStatusText}'.");
        } else {
            return redirect()->back()->withErrors(['status' => "Невозможно изменить статус с '{$order->status}' на '{$newStatus}'."]);
        }
    }

    /**
     * Возвращает русский текст для статуса заказа.
     */
    private function getRussianStatusText(string $status): string
    {
        switch (strtolower($status)) {
            case 'preparing':
                return 'Готовится';
            case 'ready':
                return 'Готов к выдаче';
            case 'completed':
                return 'Завершен';
            case 'received':
                return 'Выдан';
            case 'cancelled':
                return 'Отменен';
            default:
                return $status;
        }
    }

    /**
     * Вспомогательная функция форматирования заказов.
     * @param \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $orders
     * @return \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function formatOrdersForAdmin($orders)
    {
        $formatFunction = function (Order $order) {
            return [
                'id' => $order->id,
                'number' => '№' . $order->id,
                'status' => $order->status,
                'created_date' => $order->created_at->format('d.m H:i'),
                'user_name' => $order->user?->name ?? 'N/A',
                'total_price' => $order->price,
                'items' => $order->orderProducts->map(function (OrderProducts $item) {
                    if (!$item->product) {
                        return [
                            'id' => $item->id,
                            'product_name' => '[Товар удален]',
                            'quantity' => $item->count,
                            'price_per_item' => 0,
                            'options_description' => 'Нет данных',
                        ];
                    }
                    $optionsDesc = [];
                    if ($item->product->size?->volume) $optionsDesc[] = $item->product->size->volume;
                    if ($item->milkExtra?->name) $optionsDesc[] = $item->milkExtra->name;
                    if ($item->syrupExtra?->name) $optionsDesc[] = $item->syrupExtra->name;
                    if ($item->sugar_quantity > 0) $optionsDesc[] = 'Сахар x' . $item->sugar_quantity;
                    if ($item->has_cinnamon) $optionsDesc[] = 'Корица';
                    if ($item->has_condensed_milk) $optionsDesc[] = 'Сгущенка';
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product->name,
                        'quantity' => $item->count,
                        'price_per_item' => round(($item->unit_price ?? 0) + ($item->extras_price ?? 0), 2),
                        'options_description' => implode(', ', $optionsDesc),
                    ];
                }),
            ];
        };

        if ($orders instanceof LengthAwarePaginator) {
            return $orders->through($formatFunction);
        }
        // Если это обычная коллекция (например, один найденный заказ)
        return $orders->map($formatFunction);
    }
}
