<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AdminMenuStockController extends Controller
{
    public function index(): Response
    {
        // Получаем продукты, для которых ведется учет стока (НЕ кофе, НЕ напитки)
        // Исключаем сырники, если для них нет штучного учета?
        $stockableProducts = Product::whereIn('category_id', [1, 4]) // Сытная еда и Десерты
        // ->where('name', '!=', 'Сырники') // Пример исключения
        // Показываем все вариации или только одну? Пока все.
        ->select('id', 'name', 'count') // Выбираем нужные поля
        ->orderBy('name')
            ->get();

        // Получаем список всех продуктов для выпадающего списка добавления
        $allProductsForSelect = Product::whereIn('category_id', [1, 4])
            // ->where('name', '!=', 'Сырники')
            ->orderBy('name')
            ->get(['id', 'name']); // ID и имя

        return Inertia::render('Admin/MenuStock/Index', [
            'stockableProducts' => $stockableProducts,
            'allProductsForSelect' => $allProductsForSelect,
        ]);
    }

    /**
     * Добавление количества к стоку продукта.
     */
    public function addStock(Request $request, Product $product): RedirectResponse // Route Model Binding
    {
        // Проверяем, можно ли вообще управлять стоком этого продукта (доп. защита)
        if (!in_array($product->category_id, [1, 4]) /* || $product->name === 'Сырники' */) {
            return redirect()->back()->withErrors(['product' => 'Сток для этого товара не отслеживается.']);
        }

        $validated = $request->validate([
            'quantity_to_add' => ['required', 'integer', 'min:1', 'max:999'], // Максимум 999 за раз
        ]);

        // Используем increment для безопасного увеличения
        $product->increment('count', $validated['quantity_to_add']);

        return redirect()->back()->with('message', "Добавлено {$validated['quantity_to_add']} шт. к товару '{$product->name}'.");
    }
}
