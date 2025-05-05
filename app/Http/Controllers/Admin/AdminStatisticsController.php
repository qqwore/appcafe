<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon; // Для работы с датами

class AdminStatisticsController extends Controller
{
    public function index(): Response
    {
        // Статистика по Заказам
        $ordersTodayCount = Order::whereDate('created_at', Carbon::today())->count();
        $ordersMonthCount = Order::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Статистика по Пользователям
        $usersTodayCount = User::whereDate('created_at', Carbon::today())->count();
        $usersMonthCount = User::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        $usersTotalCount = User::count(); // Общее число пользователей


        // Общее количество типов товаров (уникальных имен)
        $productsTotalCount = Product::distinct('name')->count('name');


        return Inertia::render('Admin/Statistics/Index', [
            'stats' => [
                'orders_today' => $ordersTodayCount,
                'orders_month' => $ordersMonthCount,
                'users_today' => $usersTodayCount,
                // 'users_month' => $usersMonthCount, // Если нужно за месяц
                'users_total' => $usersTotalCount,
                'products_total' => $productsTotalCount,
            ]
        ]);
    }
}
