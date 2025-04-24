<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache; // <-- Добавляем Cache
use Illuminate\Support\Facades\DB;

class MainController extends Controller
{
    public function index(): InertiaResponse
    {
        // --- НОВАЯ ЛОГИКА С КЕШИРОВАНИЕМ ID ---

        $numberOfFeatured = 4;
        $cacheKey = 'featured_product_names'; // Ключ для кеша
        $cacheTtl = now()->addHours(1); // Время жизни кеша (1 час)

        // Пытаемся получить имена из кеша или генерируем и кешируем
        $featuredProductNames = Cache::remember($cacheKey, $cacheTtl, function () use ($numberOfFeatured) {

            $coffeeCategoryId = 3;
            $foodCategoryIds = [1, 4];

            // Получаем все УНИКАЛЬНЫЕ и ДОСТУПНЫЕ имена
            $allAvailableCoffeeNames = Product::where('category_id', $coffeeCategoryId)
                // ->where('count', '>', 0) // Добавьте фильтр доступности, если есть
                ->distinct('name')
                ->pluck('name');
            $allAvailableFoodNames = Product::whereIn('category_id', $foodCategoryIds)
                // ->where('count', '>', 0) // Добавьте фильтр доступности
                ->distinct('name')
                ->pluck('name');

            // Гарантируем по одному, если возможно
            $names = collect();
            if ($allAvailableCoffeeNames->isNotEmpty()) {
                $names->push($allAvailableCoffeeNames->random());
            }
            if ($allAvailableFoodNames->isNotEmpty()) {
                $names->push($allAvailableFoodNames->random());
            }
            $names = $names->unique(); // Убираем дубликат, если кофе=еда случайно

            // Собираем все остальные доступные имена, исключая уже выбранные
            $remainingNames = $allAvailableCoffeeNames->merge($allAvailableFoodNames)
                ->diff($names)
                ->shuffle();

            // Добираем до нужного количества
            $needed = $numberOfFeatured - $names->count();
            if ($needed > 0) {
                $names = $names->merge($remainingNames->take($needed));
            }

            return $names->take($numberOfFeatured)->values(); // Возвращаем коллекцию имен для кеширования
        });
        // --- КОНЕЦ ЛОГИКИ ВЫБОРА ИМЕН ---


        // --- Получение вариаций и обработка (без изменений) ---
        if ($featuredProductNames->isEmpty()) {
            $featuredProducts = collect();
        } else {
            $allFeaturedVariations = Product::whereIn('name', $featuredProductNames)
                ->orderBy('name')
                ->get();
            $groupedByName = $allFeaturedVariations->groupBy('name');
            $featuredProducts = $groupedByName->map(function (Collection $group, $name) {
                // ... (вся логика map остается прежней: defaultVariation, imageUrl, pricePrefix...) ...
                $firstProduct = $group->first();
                if (!$firstProduct) return null;
                $minPrice = $group->min('price');
                $hasMultipleVariations = $group->count() > 1;
                $defaultVariation = $group->firstWhere('size_name', 'Medium') ?? $firstProduct;
                $imageUrl = !empty($defaultVariation->photo) ? asset($defaultVariation->photo) : null;
                $isAvailable = true; // Замените на реальную логику
                $pricePrefix = ($hasMultipleVariations || in_array(strtolower($name), ['сырники', 'чипсы'])) ? 'от' : '';
                $description = (strtolower($name) === 'чипсы') ? 'В ассортименте' : $defaultVariation->description;

                return [
                    'display_id' => $defaultVariation->id,
                    'name' => $name,
                    'category_id' => $defaultVariation->category_id,
                    'min_price' => $minPrice,
                    'price_prefix' => $pricePrefix,
                    'image_url' => $imageUrl,
                    'description' => $description,
                    'is_available' => $isAvailable,
                    'has_variations' => $hasMultipleVariations,
                ];
            })->filter()->values();

            // (Опционально) Сортируем результат согласно порядку в $featuredProductNames
            $featuredProducts = $featuredProducts->sortBy(function($product) use ($featuredProductNames) {
                return $featuredProductNames->search($product['name']);
            })->values();
        }
        // --- Конец обработки ---


        // Рендеринг (без изменений)
        return Inertia::render('Welcome', [
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
