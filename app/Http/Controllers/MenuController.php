<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
// asset() не требует импорта фасада Storage

class MenuController extends Controller
{
    /**
     * Отображение страницы меню.
     * Продукты группируются по реальным категориям из БД,
     * затем внутри категорий обрабатываются вариации (группировка по имени).
     *
     * @param Request $request
     * @return InertiaResponse
     */
    public function index(Request $request): InertiaResponse
    {
        // 1. Получаем все категории для кнопок фильтров и для названий секций
        // Сразу создаем коллекцию, индексированную по ID, для удобного доступа к именам
        $categories = Category::orderBy('id') // Или orderBy('name')
        ->get();
        $categoriesById = $categories->keyBy('id');

        // 2. Получаем все вариации продуктов
        $allProducts = Product::with('category')
            // ->where('is_visible', true)
            ->orderBy('name')
            ->get();

        // 3. Группируем продукты по их реальному category_id из БД
        $productsGroupedByDbCategory = $allProducts->groupBy('category_id');

        // 4. Обрабатываем каждую группу (категорию из БД)
        $processedProductsByCategory = collect($productsGroupedByDbCategory)->mapWithKeys(function (Collection $productsInDbCategory, $dbCategoryId) use ($categoriesById) {

            // --- ВНУТРИ КАЖДОЙ КАТЕГОРИИ БД, ГРУППИРУЕМ ВАРИАЦИИ ПО ИМЕНИ ---
            $groupedByName = $productsInDbCategory->groupBy('name');

            // --- Обрабатываем каждую подгруппу (например, все "Латте") ---
            $displayProductsForCategory = $groupedByName->map(function (Collection $group, $name) {
                $firstProduct = $group->first();
                if (!$firstProduct) return null; // Пропускаем пустые подгруппы

                $minPrice = $group->min('price');
                $hasMultipleVariations = $group->count() > 1;

                // Определяем стандартную вариацию
                $defaultVariation = $group->firstWhere('size_name', 'Medium') ?? $firstProduct;

                // Генерируем URL изображения из поля 'photo' стандартной вариации
                $imageUrl = !empty($defaultVariation->photo) ? asset($defaultVariation->photo) : null;

                // Определяем доступность
                $isAvailable = true; // По умолчанию доступен

                // --- Примеры установки недоступности для конкретных имен ---
                // Список названий ТОЧНО ТАК, как они записаны в БД
                $unavailableNames = [
                    'Брускетта с ветчиной и сыром',
                    'Чай RICH'
                ];

                // Используем $name напрямую (без strtolower) для сравнения
                if (in_array($name, $unavailableNames)) {
                    $isAvailable = false; // Устанавливаем false, если имя найдено в списке
                }
                // Определяем префикс цены
                $pricePrefix = ($hasMultipleVariations || in_array(strtolower($name), ['сырники', 'чипсы'])) ? 'от' : '';

                // Определяем описание
                $description = (strtolower($name) === 'чипсы') ? 'В ассортименте' : $defaultVariation->description;

                // Собираем объект для Vue
                return [
                    'display_id' => $defaultVariation->id,
                    'name' => $name,
                    'category_id' => $firstProduct->category_id, // Сохраняем реальный ID категории
                    'min_price' => $minPrice,
                    'price_prefix' => $pricePrefix,
                    'image_url' => $imageUrl,
                    'description' => $description,
                    'is_available' => $isAvailable,
                    'has_variations' => $hasMultipleVariations,
                ];
            })->filter()->values(); // Убираем null и сбрасываем ключи

            // --- Возвращаем данные для этой категории БД ---
            // Используем ID категории из БД как ключ
            return [
                $dbCategoryId => [
                    // Получаем имя категории из загруженной ранее коллекции
                    'name' => $categoriesById->get($dbCategoryId)->name ?? 'Неизвестная категория',
                    'products' => $displayProductsForCategory, // Массив обработанных продуктов
                ]
            ];
        }); // Используем mapWithKeys, чтобы сохранить ID категорий как ключи

        // 5. Передаем данные в Inertia
        return Inertia::render('Menu', [
            // Передаем объект, где ключ - ID категории, значение - { name: '...', products: [...] }
            'productsByCategory' => $processedProductsByCategory,
            // Передаем все категории для создания кнопок-фильтров
            'categories' => $categories,
        ]);
    }
}
