<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;

// Убедитесь, что модель Category импортирована, если используется
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

// use Illuminate\Support\Facades\DB; // Не используется в текущей версии
// use Illuminate\Support\Facades\Log; // Раскомментируйте, если нужно логирование

class MainController extends Controller
{
    /**
     * Отображение главной страницы с избранными продуктами.
     * Выбирает случайные доступные товары: 1 кофе, 1 еду, и еще 2 из этих категорий.
     */
    public function index(): InertiaResponse
    {
        $numberOfFeatured = 4; // Общее количество избранных товаров
        $cacheKey = 'featured_product_names_v3'; // Обновите ключ, если меняете логику кеширования
        $cacheTtl = now()->addMinutes(30); // Время жизни кеша (например, 30 минут)

        // Пытаемся получить имена из кеша или генерируем и кешируем
        $featuredProductNames = Cache::remember($cacheKey, $cacheTtl, function () use ($numberOfFeatured) {

            $coffeeCategoryId = 3;       // ID категории "Кофе"
            $foodCategoryIds = [1, 4];    // ID категорий "Сытная еда" и "Десерты"
            $nonCoffeeDrinkCategoryId = 2; // ID категории "Не кофе" (теперь "Напитки")

            // 1. Получаем ВСЕ уникальные названия ДОСТУПНЫХ кофе
            $allAvailableCoffeeNames = Product::where('category_id', $coffeeCategoryId)
                // Кофе НЕ отслеживается по остаткам на главной, всегда доступен для показа
                ->distinct('name')
                ->pluck('name');

            // 2. Получаем ВСЕ уникальные названия ДОСТУПНОЙ еды (остаток > 0)
            $allAvailableFoodNames = Product::whereIn('category_id', $foodCategoryIds)
                ->where('count', '>', 0) // Учитываем остаток
                ->distinct('name')
                ->pluck('name');

            // 3. Получаем ВСЕ уникальные названия ДОСТУПНЫХ "Не кофе" напитков (остаток > 0)
            $allAvailableNonCoffeeDrinksNames = Product::where('category_id', $nonCoffeeDrinkCategoryId)
                ->where('count', '>', 0) // Учитываем остаток
                ->distinct('name')
                ->pluck('name');

            // Итоговый список имен для главной страницы
            $selectedNames = collect();

            // Гарантируем 1 кофе, если есть доступные
            if ($allAvailableCoffeeNames->isNotEmpty()) {
                $selectedNames->push($allAvailableCoffeeNames->random());
            }

            // Гарантируем 1 еду (сытная или десерт), если есть доступные
            if ($allAvailableFoodNames->isNotEmpty()) {
                $foodName = $allAvailableFoodNames->random();
                if (!$selectedNames->contains($foodName)) { // Убедимся, что не добавляем дубликат, если кофе=еда
                    $selectedNames->push($foodName);
                }
            }

            // Собираем пул оставшихся доступных имен из всех трех групп, исключая уже выбранные
            $remainingPool = $allAvailableCoffeeNames
                ->merge($allAvailableFoodNames)
                ->merge($allAvailableNonCoffeeDrinksNames)
                ->diff($selectedNames) // Убираем уже выбранные
                ->unique() // Убираем дубликаты, если были
                ->shuffle(); // Перемешиваем

            // Добираем до нужного количества ($numberOfFeatured)
            $needed = $numberOfFeatured - $selectedNames->count();
            if ($needed > 0 && $remainingPool->isNotEmpty()) {
                $selectedNames = $selectedNames->merge($remainingPool->take($needed));
            }

            return $selectedNames->values(); // Возвращаем коллекцию имен для кеширования
        });
        // --- КОНЕЦ ЛОГИКИ ВЫБОРА ИМЕН ---


        // --- Получение вариаций и обработка ---
        if ($featuredProductNames->isEmpty()) {
            $featuredProductsData = collect(); // Пустая коллекция, если не нашли имен
        } else {
            // Получаем все вариации для выбранных названий
            $allFeaturedVariations = Product::whereIn('name', $featuredProductNames)
                // ->orderBy('name') // Можно убрать, если порядок из $featuredProductNames важнее
                ->get(); // Получаем все поля для accessor'ов

            // Группируем по имени для обработки (если у одного "концепта" несколько вариаций)
            $groupedByName = $allFeaturedVariations->groupBy('name');

            // Обрабатываем каждую группу
            $featuredProductsData = $groupedByName->map(function (Collection $group, $name) {
                $firstProduct = $group->first(); // Для общих данных
                if (!$firstProduct) {
                    // Log::warning("Empty product group for name [{$name}] in MainController");
                    return null;
                }

                $minPrice = $group->min('price');
                $hasMultipleVariations = $group->count() > 1;

                // Определяем стандартную/отображаемую вариацию
                // (та, чьи данные, включая 'count', 'photo', 'slug', будут показаны)
                $defaultVariation = $group->firstWhere('size_name', 'Medium');
                if (!$defaultVariation) {
                    // $defaultVariation = $group->firstWhere('is_default', true);
                }
                if (!$defaultVariation) {
                    $defaultVariation = $firstProduct;
                }

                $imageUrl = !empty($defaultVariation->photo) ? asset($defaultVariation->photo) : null;

                // Получаем значения accessors от defaultVariation
                $isStockManaged = $defaultVariation->is_stock_managed;
                $currentCount = $defaultVariation->count;
                $isAvailable = $isStockManaged ? ($currentCount > 0) : true;

                $pricePrefix = ($hasMultipleVariations || in_array(strtolower($name), ['сырники', 'чипсы'])) ? 'от' : '';
                $description = (strtolower($name) === 'чипсы') ? 'В ассортименте' : $defaultVariation->description;

                return [
                    'display_id' => $defaultVariation->id, // ID стандартной/отображаемой вариации
                    'name' => $name,                       // Общее имя продукта
                    'slug' => $defaultVariation->slug,     // Слаг для URL
                    'category_id' => $defaultVariation->category_id,
                    'min_price' => $minPrice,
                    'price_prefix' => $pricePrefix,
                    'image_url' => $imageUrl,
                    'description' => $description,
                    'count' => $currentCount,               // Остаток стандартной/отображаемой вариации
                    'is_stock_managed' => $isStockManaged,  // Управляется ли сток
                    'is_available' => $isAvailable,        // Доступен ли (с учетом стока)
                    'has_variations' => $hasMultipleVariations, // Есть ли другие размеры/опции
                ];
            })->filter()->values(); // filter() уберет null, если были пустые группы

            // Сортируем итоговый массив $featuredProductsData согласно порядку в $featuredProductNames
            // чтобы сохранить заданный нами "случайный, но с приоритетами" порядок
            $featuredProductsData = $featuredProductsData->sortBy(function ($product) use ($featuredProductNames) {
                return $featuredProductNames->search($product['name']);
            })->values();
        }
        // --- КОНЕЦ ПОДГОТОВКИ ДАННЫХ ---

        // Рендерим Welcome.vue, передавая избранные продукты
        return Inertia::render('Welcome', [
            'featuredProducts' => $featuredProductsData,
        ]);
    }
}
