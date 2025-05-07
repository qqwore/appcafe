<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Extras;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    // Свойство $arr не нужно, если вы не используете его вне метода
    // private $arr;

    /**
     * Отображение страницы одного продукта.
     */
    public function show(Product $product): InertiaResponse // $product - это вариация, на которую кликнули
    {
        // Загружаем отношения для переданного продукта (вариации)
        $product->load(['category', 'size', 'kpfc']);

        // Получаем доступные допы
        $extras = Cache::remember('available_extras', now()->addHour(), function () {
            return Extras::orderBy('name')->get();
        });
        $availableExtras = [
            'milks' => $extras->whereIn('id', [6])->values()->toArray(),
            'syrups' => $extras->whereIn('id', range(7, 14))->values()->toArray(),
        ];

        // --- ПОЛУЧАЕМ ВСЕ ВАРИАЦИИ ЭТОГО ПРОДУКТА ПО ИМЕНИ И ЗАГРУЖАЕМ ДЛЯ НИХ ДАННЫЕ ---
        $allVariationsOfProduct = Product::where('name', $product->name) // Ищем все продукты с таким же именем
        ->with(['size']) // Загружаем размер для каждой вариации
        ->orderBy('size_id')
            ->get(); // Получаем коллекцию всех вариаций
        // ---------------------------------------------------------------------------

        // Формируем данные для Vue
        // Используем $product (тот, что пришел в метод) для основных данных
        $productDataForVue = [
            'id' => $product->id, // ID текущей вариации (для начального выбора размера)
            'name' => $product->name,
            'description' => $product->description,
            'image_url' => $product->photo ? asset($product->photo) : null,
            'category_id' => $product->category_id,
            'kpfc' => $product->kpfc ? [
                'kilocalories' => $product->kpfc->kilocalories,
                'proteins' => $product->kpfc->proteins,
                'fats' => $product->kpfc->fats,
                'carbohydrates' => $product->kpfc->carbohydrates,
            ] : null,
            'can_add_sugar' => $product->can_add_sugar,
            'can_add_cinnamon' => $product->can_add_cinnamon,
            'can_add_milk' => $product->can_add_milk,
            'can_add_syrup' => $product->can_add_syrup,
            'can_add_condensed_milk' => $product->can_add_condensed_milk,
            // Общий флаг управления стоком для этого "концептуального" продукта
            // Берем у текущей вариации, т.к. он должен быть одинаков для всех вариаций одного товара
            'is_stock_managed_concept' => $product->is_stock_managed,

            // Передаем все вариации (размеры) этого продукта с их остатками и доступностью
            // Передаем $product (основной) в замыкание через use
            'variations' => $allVariationsOfProduct->map(function ($variation) use ($product) {
                $isStockManaged = $variation->is_stock_managed;
                return [
                    'id' => $variation->id,
                    'size_name' => $variation->size?->volume ?? 'Стандарт',
                    'price' => $variation->price,
                    // Картинка для КАЖДОЙ вариации.
                    // Если у вариации нет своего фото, ИСПОЛЬЗУЕМ ФОТО ОСНОВНОГО ПРОДУКТА (на который кликнули)
                    'image_url' => $variation->photo ? asset($variation->photo) : ($product->photo ? asset($product->photo) : null),
                    'count' => $variation->count,
                    'is_stock_managed' => $isStockManaged,
                    'is_available' => $isStockManaged ? ($variation->count > 0) : true,
                ];
            }),
        ];

        return Inertia::render('Product/Show', [
            'product' => $productDataForVue,
            'availableExtras' => $availableExtras,
        ]);
    }
}
