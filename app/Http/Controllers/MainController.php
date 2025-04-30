<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Для отладки, если нужно

class MainController extends Controller
{
    /**
     * Отображение главной страницы с избранными продуктами.
     */
    public function index(): InertiaResponse
    {
        // --- ЛОГИКА ВЫБОРА "ИЗБРАННЫХ" ПРОДУКТОВ (с кешированием) ---

        $numberOfFeatured = 4;
        $cacheKey = 'featured_product_names';
        $cacheTtl = now()->addHours(1);

        $featuredProductNames = Cache::remember($cacheKey, $cacheTtl, function () use ($numberOfFeatured) {
            $coffeeCategoryId = 3;
            $foodCategoryIds = [1, 4];

            $allAvailableCoffeeNames = Product::where('category_id', $coffeeCategoryId)
                // ->where('count', '>', 0) // Добавьте фильтр доступности
                ->distinct('name')
                ->pluck('name');
            $allAvailableFoodNames = Product::whereIn('category_id', $foodCategoryIds)
                // ->where('count', '>', 0) // Добавьте фильтр доступности
                ->distinct('name')
                ->pluck('name');

            $names = collect();
            if ($allAvailableCoffeeNames->isNotEmpty()) $names->push($allAvailableCoffeeNames->random());
            if ($allAvailableFoodNames->isNotEmpty()) $names->push($allAvailableFoodNames->random());
            $names = $names->unique();

            $remainingNames = $allAvailableCoffeeNames->merge($allAvailableFoodNames)
                ->diff($names)
                ->shuffle();

            $needed = $numberOfFeatured - $names->count();
            if ($needed > 0) $names = $names->merge($remainingNames->take($needed));

            return $names->take($numberOfFeatured)->values();
        });
        // --- КОНЕЦ ЛОГИКИ ВЫБОРА ИМЕН ---

        // --- Получение вариаций и обработка ---
        if ($featuredProductNames->isEmpty()) {
            $featuredProducts = collect();
        } else {
            // Подгружаем нужные связи СРАЗУ для всех вариаций
            $allFeaturedVariations = Product::whereIn('name', $featuredProductNames)
                ->with(['size', 'kpfc', 'category']) // Загружаем size, kpfc, category
                ->orderBy('name')
                ->get();

            $groupedByName = $allFeaturedVariations->groupBy('name');

            $featuredProducts = $groupedByName->map(function (Collection $group, $name) {
                $firstProduct = $group->first();
                if (!$firstProduct) return null;

                $minPrice = $group->min('price');
                $hasMultipleVariations = $group->count() > 1;

                // Ищем стандартную вариацию (например, Medium)
                $defaultVariation = $group->firstWhere('size.name', 'Medium'); // Ищем по имени размера
                if(!$defaultVariation) {
                    $defaultVariation = $group->firstWhere('is_default', true); // Или по флагу
                }
                if (!$defaultVariation) {
                    $defaultVariation = $firstProduct; // Или берем первую
                }

                $imageUrl = !empty($defaultVariation->photo) ? asset($defaultVariation->photo) : null;
                $isAvailable = true; // Замените на реальную логику

                $pricePrefix = ($hasMultipleVariations || in_array(strtolower($name), ['сырники', 'чипсы'])) ? 'от' : '';
                $description = (strtolower($name) === 'чипсы') ? 'В ассортименте' : $defaultVariation->description;

                // --- ЯВНО ДОБАВЛЯЕМ ЗНАЧЕНИЯ ACCESSOR'ОВ ---
                // Мы обращаемся к ним у объекта $defaultVariation (или $firstProduct)
                // Это заставит Laravel вычислить их значения
                $canAddSugar = $defaultVariation->can_add_sugar;
                $canAddCinnamon = $defaultVariation->can_add_cinnamon;
                $canAddMilk = $defaultVariation->can_add_milk;
                $canAddSyrup = $defaultVariation->can_add_syrup;
                $canAddCondensedMilk = $defaultVariation->can_add_condensed_milk;
                // -----------------------------------------

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
                    'slug' => $defaultVariation->slug, // <-- ДОБАВЛЯЕМ СЛАГ (берем от стандартной вариации)
                    // --- ПЕРЕДАЕМ ФЛАГИ ВО VUE ---
                    'can_add_sugar' => $canAddSugar,
                    'can_add_cinnamon' => $canAddCinnamon,
                    'can_add_milk' => $canAddMilk,
                    'can_add_syrup' => $canAddSyrup,
                    'can_add_condensed_milk' => $canAddCondensedMilk,
                    // Передаем вариации для страницы продукта (если вдруг нужно)
                    // или для проверки has_variations в checkIfProductHasOptions во Vue
                    'variations' => $hasMultipleVariations ? $group->map(fn($p) => ['id' => $p->id])->all() : [], // Передаем только ID вариаций
                    // Передаем КБЖУ, если оно есть
                    'kpfc' => $defaultVariation->kpfc ? $defaultVariation->kpfc->toArray() : null,
                ];
            })->filter()->values();

            // Опциональная сортировка
            $featuredProducts = $featuredProducts->sortBy(function($product) use ($featuredProductNames) {
                return $featuredProductNames->search($product['name']);
            })->values();
        }
        // --- КОНЕЦ ПОДГОТОВКИ ДАННЫХ ---

        // Рендеринг Welcome.vue
        return Inertia::render('Welcome', [
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
