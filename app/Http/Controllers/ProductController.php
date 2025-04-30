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
    private $arr;

    /**
     * Отображение страницы одного продукта.
     */
    public function show(Product $product): InertiaResponse
    {
        // Загружаем основные отношения для ГЛАВНОГО продукта
        $product->load(['category', 'size', 'kpfc']); // <-- УБРАЛИ 'variations.size'

        // Получаем доступные допы
        $extras = Cache::remember('available_extras', now()->addHour(), function () {
            return Extras::orderBy('name')->get();
        });
        $availableExtras = [
            'milks' => $extras->whereIn('id', [6])->values()->toArray(),
            'syrups' => $extras->whereIn('id', range(7, 14))->values()->toArray(), // Используем range для ID сиропов
        ];

        // --- ПОЛУЧАЕМ ВАРИАЦИИ ЧЕРЕЗ ACCESSOR ---
        // Аксессор $product->variations уже должен содержать коллекцию
        // моделей Product с загруженным отношением 'size' (как мы определили в модели)
        $variationsData = $product->variations; // Просто обращаемся как к свойству
        // ----------------------------------------

        // Формируем данные для Vue
        $this->arr = [
            'id' => $product->id,
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
            // --- ПЕРЕДАЕМ ДАННЫЕ ВАРИАЦИЙ ИЗ АКСЕССОРА ---
            'variations' => $variationsData->map(function ($variation) {
                return [
                    'id' => $variation->id, // ID вариации
                    'size_name' => $variation->size?->volume ?? 'Стандарт', // Имя размера (уже загружено аксессором)
                    'price' => $variation->price, // Цена вариации
                ];
            }),
            // ---------------------------------------------
        ];
        $productData = $this->arr;

        return Inertia::render('Product/Show', [
            'product' => $productData,
            'availableExtras' => $availableExtras,
        ]);
    }
}
