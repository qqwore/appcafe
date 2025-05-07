<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

// Для транзакций
use Illuminate\Support\Facades\Session;

// Для хранения последнего действия

class AdminMenuStockController extends Controller
{
    public function index(): Response
    {
        // Получаем продукты, для которых ведется учет стока
        $stockableProducts = Product::whereIn('category_id', [1, 4]) // Сытная еда и Десерты
        // ->where('name', '!=', 'Сырники')
        ->select('id', 'name', 'count')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/MenuStock/Index', [
            'stockableProducts' => $stockableProducts,
            // allProductsForSelect больше не нужен в этой логике
        ]);
    }

    /**
     * Массовое добавление количества к стоку продуктов.
     */
    public function updateMultipleStock(Request $request): RedirectResponse
    {
        // ... (валидация) ...
        $updates = $request->input('stock_updates', []); // Используем input для безопасности
        $previousStockState = [];

        DB::beginTransaction();
        try {
            foreach ($updates as $productId => $quantityToAdd) {
                if ($quantityToAdd > 0) {
                    $product = Product::find($productId);
                    if ($product && in_array($product->category_id, [1, 4])) {
                        $previousStockState[] = [
                            'product_id' => $product->id,
                            'previous_count' => $product->count, // Сохраняем КОЛИЧЕСТВО ДО добавления
                            'added_quantity' => $quantityToAdd,
                        ];
                        $product->increment('count', $quantityToAdd);
                    }
                }
            }
            DB::commit();

            if (!empty($previousStockState)) {
                // --- ИСПОЛЬЗУЕМ Session::put() ---
                Session::put('last_stock_update', $previousStockState);
                Session::put('can_undo_stock_update', true);
                // --- КОНЕЦ ИЗМЕНЕНИЯ ---
            } else {
                // Если ничего не было обновлено, очищаем старые данные для отмены
                Session::forget(['last_stock_update', 'can_undo_stock_update']);
            }

            return redirect()->back()->with('message', "Сток успешно обновлен.");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating multiple stock: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Ошибка при обновлении стока.']);
        }
    }

    public function undoLastStockUpdate(Request $request): RedirectResponse
    {
        // Проверяем флаг can_undo_stock_update (он мог быть сброшен таймером на фронте, но здесь главное - наличие данных)
        // Главное, чтобы last_stock_update существовал
        if (!Session::has('last_stock_update')) { // Проверяем только last_stock_update
            return redirect()->back()->withErrors(['undo' => 'Нечего отменять или данные для отмены устарели.']);
        }

        $lastUpdate = Session::get('last_stock_update');
        DB::beginTransaction();
        try {
            foreach ($lastUpdate as $change) {
                $product = Product::find($change['product_id']);
                if ($product) {
                    // Возвращаем предыдущее состояние
                    $product->count = $change['previous_count'];
                    // ИЛИ, если хотим просто вычесть то, что добавили:
                    // $currentCount = $product->count;
                    // $product->count = max(0, $currentCount - $change['added_quantity']);
                    $product->save();
                }
            }
            DB::commit();

            // --- УДАЛЯЕМ ДАННЫЕ ИЗ СЕССИИ ПОСЛЕ УСПЕШНОЙ ОТМЕНЫ ---
            Session::forget('last_stock_update');
            Session::forget('can_undo_stock_update'); // Также удаляем флаг
            // --- КОНЕЦ ИЗМЕНЕНИЯ ---

            return redirect()->back()->with('message', "Последнее обновление стока отменено.");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error undoing stock update: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Ошибка при отмене обновления стока.']);
        }
    }
}
