<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category; // Импортируем Category, если еще не было
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB; // Если используете сложные запросы
use Illuminate\Support\Facades\Log; // Для логирования

class MainController extends Controller
{
    /**
     * Отображение главной страницы с избранными продуктами.
     * Выбирает 4 случайных доступных продукта (минимум 1 кофе, 1 еда),
     * подготавливает их данные и рендерит Welcome.vue.
     */
    public function index(): InertiaResponse
    {
        // --- ЛОГИКА ВЫБОРА "ИЗБРАННЫХ" ПРОДУКТОВ ---

        $coffeeCategoryId = 3;
        $foodCategoryIds = [1, 4]; // Сытная еда и Десерты
        $numberOfFeatured = 4; // Желаемое количество товаров на главной

        // 1. Получаем уникальные названия ДОСТУПНЫХ кофе
        // Предполагаем, что доступность определяется полем count > 0
        // Если у вас нет поля count, уберите ->where('count', '>', 0)
        $allCoffeeNames = Product::where('category_id', $coffeeCategoryId)
            //->where('count', '>', 0) // <--- Условие доступности (если есть поле count)
            ->distinct('name')
            ->pluck('name');

        // 2. Получаем уникальные названия ДОСТУПНОЙ еды
        $allFoodNames = Product::whereIn('category_id', $foodCategoryIds)
            //->where('count', '>', 0) // <--- Условие доступности (если есть поле count)
            ->distinct('name')
            ->pluck('name');

        // 3. Выбираем случайные имена, гарантируя по одному из каждой группы (если они есть)
        $randomCoffeeName = $allCoffeeNames->isNotEmpty() ? $allCoffeeNames->random() : null;
        $randomFoodName = $allFoodNames->isNotEmpty() ? $allFoodNames->random() : null;

        // 4. Собираем все доступные имена (кофе + еда), исключая уже выбранные
        // Применяем фильтр доступности еще раз на всякий случай, если он не применялся выше
        $allAvailableNames = Product::where(function ($query) use ($coffeeCategoryId, $foodCategoryIds) {
            $query->where('category_id', $coffeeCategoryId)
                ->orWhereIn('category_id', $foodCategoryIds);
        })
            //->where('count', '>', 0) // <--- Условие доступности (если есть поле count)
            ->distinct('name')
            ->pluck('name');

        // Убираем уже выбранные случайные и перемешиваем
        $remainingNames = $allAvailableNames->diff(array_filter([$randomCoffeeName, $randomFoodName]))
            ->shuffle();

        // 5. Формируем итоговый список имен
        $featuredProductNames = collect(array_filter([$randomCoffeeName, $randomFoodName]));

        // Добираем недостающие из оставшихся перемешанных
        $needed = $numberOfFeatured - $featuredProductNames->count();
        if ($needed > 0) {
            $featuredProductNames = $featuredProductNames->merge($remainingNames->take($needed));
        }

        // Ограничиваем итоговое количество
        $featuredProductNames = $featuredProductNames->take($numberOfFeatured);
        // --- КОНЕЦ ЛОГИКИ ВЫБОРА ИМЕН ---


        // 6. Получаем все ВАРИАЦИИ для выбранных НАЗВАНИЙ
        if ($featuredProductNames->isEmpty()) {
            $featuredProducts = collect(); // Пустая коллекция, если не нашли имен
            Log::warning('No featured products could be selected for the main page.'); // Логируем
        } else {
            $allFeaturedVariations = Product::whereIn('name', $featuredProductNames)
                // ->with('category', 'size') // Подгружаем связи, если нужны дальше
                ->orderBy('name')
                ->get();

            // 7. Группируем и обрабатываем вариации
            $groupedByName = $allFeaturedVariations->groupBy('name');

            $featuredProducts = $groupedByName->map(function (Collection $group, $name) {
                $firstProduct = $group->first();
                if (!$firstProduct) return null;

                $minPrice = $group->min('price');
                $hasMultipleVariations = $group->count() > 1;
                // Находим стандартную вариацию
                $defaultVariation = $group->firstWhere('size_name', 'Medium') ?? $firstProduct;

                // Генерируем URL картинки
                $imageUrl = !empty($defaultVariation->photo) ? asset($defaultVariation->photo) : null;

                // Определяем префикс цены
                $pricePrefix = ($hasMultipleVariations || in_array(strtolower($name), ['сырники', 'чипсы'])) ? 'от' : '';

                // Определяем описание
                $description = (strtolower($name) === 'чипсы') ? 'В ассортименте' : $defaultVariation->description;

                // Формируем итоговый объект
                return [
                    'display_id' => $defaultVariation->id,
                    'name' => $name,
                    'category_id' => $defaultVariation->category_id,
                    'min_price' => $minPrice,
                    'price_prefix' => $pricePrefix,
                    'image_url' => $imageUrl,
                    'description' => $description,
                    'is_available' => true, // Устанавливаем true, т.к. выбирали из доступных (по логике выше)
                    'has_variations' => $hasMultipleVariations,
                ];
            })->filter()->values(); // Убираем null и сбрасываем ключи

            // (Опционально) Пересортировать $featuredProducts согласно $featuredProductNames, если порядок важен
            // $featuredProducts = $featuredProducts->sortBy(function($product) use ($featuredProductNames) {
            //     return array_search($product['name'], $featuredProductNames->all());
            // })->values();

        }
        // --- КОНЕЦ ПОДГОТОВКИ ДАННЫХ ---

        // 8. Рендерим Welcome.vue, передавая избранные продукты
        return Inertia::render('Welcome', [
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
