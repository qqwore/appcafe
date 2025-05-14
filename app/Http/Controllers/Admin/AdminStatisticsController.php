<?php

namespace App\Http\Controllers\Admin;
// Убедитесь, что неймспейс правильный

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\Extras;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

// Используем псевдоним
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Для DB::raw и сложных запросов
use Illuminate\Contracts\Database\Query\Expression;

// Для типа DB::raw

class AdminStatisticsController extends Controller
{
    /**
     * Отображение страницы статистики.
     * Собирает различные статистические данные и передает их во Vue компонент.
     *
     * @param Request $request
     * @return InertiaResponse
     */
    public function index(Request $request): InertiaResponse
    {
        // --- Определяем статусы для расчетов ---
        // Статусы, по которым считаем выручку (когда деньги точно получены)
        $revenueStatuses = ['Received', 'Completed']; // Замените 'Received' на ваш фактический статус получения оплаты
        // Статусы, которые не считаются отмененными (для подсчета общей активности)
        $nonCancelledStatuses = ['Preparing', 'Ready', 'Completed', 'Received'];

        // --- Определяем временные рамки ---
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfYear = Carbon::now()->startOfYear(); // Для годовых графиков
        $last30DaysStart = Carbon::now()->subDays(29)->startOfDay(); // Начало периода "последние 30 дней"

        // === 1. ОБЩИЕ ДАННЫЕ (для вкладки "Сводка" и общих показателей) ===
        $ordersTotalAllTime = Order::count();
        $ordersToday = Order::whereDate('created_at', $today)->count();
        $ordersThisWeek = Order::where('created_at', '>=', $startOfWeek)->count();
        $ordersThisMonth = Order::where('created_at', '>=', $startOfMonth)->count();

        // Выручка (только по заказам с финальным статусом оплаты)
        $revenueToday = Order::whereIn('status', $revenueStatuses)->whereDate('created_at', $today)->sum('price');
        $revenueThisWeek = Order::whereIn('status', $revenueStatuses)->where('created_at', '>=', $startOfWeek)->sum('price');
        $revenueThisMonth = Order::whereIn('status', $revenueStatuses)->where('created_at', '>=', $startOfMonth)->sum('price');

        // Средний чек (по оплаченным заказам за текущий месяц)
        $paidOrdersThisMonthCount = Order::whereIn('status', $revenueStatuses)->where('created_at', '>=', $startOfMonth)->count();
        $averageCheckThisMonth = $paidOrdersThisMonthCount > 0 ? ($revenueThisMonth / $paidOrdersThisMonthCount) : 0;

        $usersTotal = User::count();
        $usersToday = User::whereDate('created_at', $today)->count();
        $usersThisWeek = User::where('created_at', '>=', $startOfWeek)->count();
        $usersThisMonth = User::where('created_at', '>=', $startOfMonth)->count();

        $uniqueProductsCount = Product::distinct('name')->count('name'); // Количество уникальных названий товаров

        // === 2. СТАТИСТИКА ПО ТОВАРАМ (для вкладки "Товары") ===
        // Самые популярные товары по количеству продаж за текущий месяц (не отмененные)
        $topSoldProductsMonth = OrderProducts::whereHas('order', function ($query) use ($startOfMonth, $nonCancelledStatuses) {
            $query->where('orders.created_at', '>=', $startOfMonth) // Уточняем таблицу
            ->whereIn('orders.status', $nonCancelledStatuses);
        })
            ->selectRaw('product_id, SUM(count) as total_sold')
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5) // Топ 5
            ->get();

        // Товары, приносящие наибольшую выручку за текущий месяц (только оплаченные)
        $topRevenueProductsMonth = OrderProducts::whereHas('order', function ($query) use ($startOfMonth, $revenueStatuses) {
            $query->where('orders.created_at', '>=', $startOfMonth)
                ->whereIn('orders.status', $revenueStatuses);
        })
            // Предполагаем, что unit_price и extras_price уже в order_products
            ->selectRaw('product_id, SUM(count * (unit_price + extras_price)) as total_revenue')
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->limit(5) // Топ 5
            ->get();

        // Статистика по категориям (выручка и количество проданных товаров за месяц, только оплаченные)
        $categoryStatsMonth = Product::select(
            'categories.name as category_name',
            DB::raw('SUM(order_products.count * (order_products.unit_price + order_products.extras_price)) as total_revenue'),
            DB::raw('SUM(order_products.count) as total_sold')
        )
            ->join('order_products', 'products.id', '=', 'order_products.product_id')
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.created_at', '>=', $startOfMonth)
            ->whereIn('orders.status', $revenueStatuses)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        // Популярные допы (по количеству использований за все время, не отмененные)
        $popularMilkExtras = OrderProducts::whereHas('order', fn($q) => $q->whereIn('status', $nonCancelledStatuses))
            ->whereNotNull('milk_extra_id')
            ->groupBy('milk_extra_id')
            ->selectRaw('milk_extra_id, COUNT(*) as count')
            ->with('milkExtra:id,name') // Отношение 'milkExtra' в модели OrderProducts
            ->orderByDesc('count')
            ->limit(3) // Топ 3 молока
            ->get();
        $popularSyrupExtras = OrderProducts::whereHas('order', fn($q) => $q->whereIn('status', $nonCancelledStatuses))
            ->whereNotNull('syrup_extra_id')
            ->groupBy('syrup_extra_id')
            ->selectRaw('syrup_extra_id, COUNT(*) as count')
            ->with('syrupExtra:id,name') // Отношение 'syrupExtra' в модели OrderProducts
            ->orderByDesc('count')
            ->limit(3) // Топ 3 сиропа
            ->get();

        // === 3. ДАННЫЕ ДЛЯ ГРАФИКОВ (для вкладки "Заказы") ===
        // График выручки по дням за последние 30 дней (оплаченные)
        $dailyRevenueChart = $this->prepareChartData(
            Order::where('created_at', '>=', $last30DaysStart)->whereIn('status', $revenueStatuses),
            DB::raw('DATE(created_at)'),
            DB::raw('SUM(price)'),
            'd.m', // Формат метки для PHP/Carbon (labelFormat) - оставляем короткий
            'Y-m-d', // Формат ключа из БД (dbPeriodFormat)
            $last30DaysStart, Carbon::now()
        );
        // График выручки по месяцам за последний год (оплаченные)
        $monthlyRevenueChart = $this->prepareChartData(
            Order::where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())->whereIn('status', $revenueStatuses),
            DB::raw("TO_CHAR(created_at, 'YYYY-MM')"),
            DB::raw('SUM(price)'),
            'm.Y', // <-- ИЗМЕНЕНО: 'MM.YYYY' на 'm.Y' (например, "06.2024") или 'M \'y' (например, "Июн '24")
            'Y-m',
            Carbon::now()->subMonths(11)->startOfMonth(), Carbon::now()
        );
        // График количества заказов по дням за последние 30 дней (не отмененные)
        $dailyOrdersChart = $this->prepareChartData(
            Order::where('created_at', '>=', $last30DaysStart)->whereIn('status', $nonCancelledStatuses),
            DB::raw('DATE(created_at)'),
            DB::raw('COUNT(*)'),
            'd.m', // Оставляем короткий
            'Y-m-d',
            $last30DaysStart, Carbon::now()
        );
        // График количества заказов по месяцам за последний год (не отмененные)
        $monthlyOrdersChart = $this->prepareChartData(
            Order::where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())->whereIn('status', $nonCancelledStatuses),
            DB::raw("TO_CHAR(created_at, 'YYYY-MM')"),
            DB::raw('COUNT(*)'),
            'm.Y', // <-- ИЗМЕНЕНО: 'MM.YYYY' на 'm.Y' или 'M \'y'
            'Y-m',
            Carbon::now()->subMonths(11)->startOfMonth(), Carbon::now()
        );
        // Пиковые часы (заказы за последние 30 дней, не отмененные)
        $peakHours = Order::where('created_at', '>=', $last30DaysStart)
            ->whereIn('status', $nonCancelledStatuses)
            ->selectRaw("EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour', 'asc') // Сортируем по часу для графика
            ->get();

        // Пиковые дни недели (заказы за последние 30 дней, не отмененные)
        $dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        $peakDaysOfWeek = Order::where('created_at', '>=', $last30DaysStart)
            ->whereIn('status', $nonCancelledStatuses)
            // ISO day of week (1 = Monday, 7 = Sunday) для PostgreSQL это 'ID'
            ->selectRaw("EXTRACT(ISODOW FROM created_at) as day_iso, COUNT(*) as count")
            ->groupBy('day_iso')
            ->orderBy('day_iso', 'asc')
            ->get()
            ->map(function ($item) use ($dayNames) {
                $item->day_name = $dayNames[$item->day_iso - 1] ?? 'Н/Д'; // Преобразуем номер дня в название
                return $item;
            });


        // === 4. КЛИЕНТСКАЯ СТАТИСТИКА (для вкладки "Клиенты") ===
        $usersWithOrdersCount = Order::whereIn('status', $nonCancelledStatuses)->distinct('user_id')->count('user_id');
        $avgOrdersPerUser = $usersTotal > 0 ? ($ordersTotalAllTime / $usersTotal) : 0;

        $usersWithMultipleOrders = DB::table('orders')
            ->whereIn('status', $nonCancelledStatuses)
            ->select('user_id')
            ->groupBy('user_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get()->count();
        $repeatOrderRate = $usersWithOrdersCount > 0 ? round(($usersWithMultipleOrders / $usersWithOrdersCount) * 100, 2) : 0;

        $topClientsMonth = Order::where('created_at', '>=', $startOfMonth)
            ->whereIn('status', $revenueStatuses) // Потрачено по оплаченным
            ->selectRaw('user_id, SUM(price) as total_spent')
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // --- Передача всех данных в Inertia ---
        return Inertia::render('Admin/Statistics/Index', [
            'generalStats' => [
                'orders_total_all_time' => $ordersTotalAllTime,
                'orders_today' => $ordersToday,
                'orders_this_week' => $ordersThisWeek,
                'orders_this_month' => $ordersThisMonth,
                'revenue_today' => round($revenueToday, 2),
                'revenue_this_week' => round($revenueThisWeek, 2),
                'revenue_this_month' => round($revenueThisMonth, 2),
                'average_check_this_month' => round($averageCheckThisMonth, 2),
                'users_total' => $usersTotal,
                'users_today' => $usersToday,
                'users_this_week' => $usersThisWeek,
                'users_this_month' => $usersThisMonth,
                'unique_products_count' => $uniqueProductsCount,
            ],
            'productStats' => [
                'top_sold_month' => $topSoldProductsMonth,
                'top_revenue_month' => $topRevenueProductsMonth,
                'category_stats_month' => $categoryStatsMonth,
                'popular_milk' => $popularMilkExtras,
                'popular_syrup' => $popularSyrupExtras,
            ],
            'orderCharts' => [
                'daily_revenue' => $dailyRevenueChart,
                'monthly_revenue' => $monthlyRevenueChart,
                'daily_orders' => $dailyOrdersChart,
                'monthly_orders' => $monthlyOrdersChart,
                'peak_hours' => $peakHours,
                'peak_days' => $peakDaysOfWeek,
            ],
            'clientStats' => [
                'avg_orders_per_user' => round($avgOrdersPerUser, 1),
                'repeat_order_rate' => $repeatOrderRate,
                'top_clients_month' => $topClientsMonth,
            ],
        ]);
    }

    /**
     * Вспомогательный метод для подготовки данных для линейных графиков.
     * Заполняет пропуски в датах нулями.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Базовый запрос
     * @param \Illuminate\Contracts\Database\Query\Expression $dateColumn SQL для группировки по дате
     * @param \Illuminate\Contracts\Database\Query\Expression $valueColumn SQL для агрегации значения
     * @param string $labelFormatDisplay Формат PHP для отображаемых меток на графике
     * @param string $dbPeriodFormatKey Формат периода, который будет ключом в $results
     * @param Carbon $periodStart Начало периода для графика
     * @param Carbon $periodEnd Конец периода для графика
     * @return array ['labels' => [], 'data' => []]
     */
    private function prepareChartData($query, Expression $dateColumn, Expression $valueColumn, string $labelFormatDisplay, string $dbPeriodFormatKey, Carbon $periodStart, Carbon $periodEnd): array
    {
        $results = $query
            ->selectRaw("{$dateColumn->getValue(DB::connection()->getQueryGrammar())} as period_key, {$valueColumn->getValue(DB::connection()->getQueryGrammar())} as total_value")
            ->groupBy('period_key') // Группируем по псевдониму, т.к. selectRaw его создал
            ->orderBy('period_key', 'asc')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->period_key => $item->total_value
            ]);

        $labels = [];
        $data = [];
        $currentDate = $periodStart->copy()->startOfDay(); // Начинаем с начала дня
        $loopEndDate = $periodEnd->copy()->endOfDay();   // Заканчиваем концом дня

        $isMonthly = (strlen($dbPeriodFormatKey) === 7 && strpos($dbPeriodFormatKey, 'Y-m') === 0);

        $lastAddedLabel = null; // Для отслеживания последней добавленной метки (для месяцев)

        while ($currentDate->lte($loopEndDate)) { // Используем lte (меньше или равно)
            $lookupKey = $currentDate->format($dbPeriodFormatKey);
            $currentLabel = $currentDate->translatedFormat($labelFormatDisplay);

            if ($isMonthly) {
                // Для месячных графиков добавляем метку и данные только ОДИН РАЗ для каждого УНИКАЛЬНОГО месяца
                if ($currentLabel !== $lastAddedLabel) {
                    $labels[] = $currentLabel;
                    // Данные для месяца - это сумма, полученная по ключу 'YYYY-MM'
                    $data[] = (float)($results->get($lookupKey, 0));
                    $lastAddedLabel = $currentLabel;
                }
                // Переходим к первому дню СЛЕДУЮЩЕГО месяца, чтобы избежать дублей в том же месяце
                $currentDate->addMonthNoOverflow()->startOfMonth();
            } else {
                // Для дневных графиков
                $labels[] = $currentLabel;
                $data[] = (float)($results->get($lookupKey, 0));
                $currentDate->addDay();
            }
        }
        return ['labels' => $labels, 'data' => $data];
    }
    }
