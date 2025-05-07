<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * App\Models\Product
 *
 * ... (остальные PHPDoc свойства)
 * @property int $count Остаток товара на складе (для штучных)
 * @property-read bool $is_stock_managed Определяет, управляется ли остаток для этого продукта
 * @property-read bool $can_add_sugar
 * @property-read bool $can_add_cinnamon
 * @property-read bool $can_add_milk
 * @property-read bool $can_add_syrup
 * @property-read bool $can_add_condensed_milk
 * @property-read \Illuminate\Database\Eloquent\Collection|Product[] $variations
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Size|null $size
 * @property-read \App\Models\KPFC|null $kpfc
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Cart[] $cartItems
 */
class Product extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'price',
        'photo',
        'description',
        'type', // Если используется для чего-то еще
        'count', // Остаток на складе
        'category_id',
        'size_id',
        'k_p_f_c_id',
        'slug'
    ];

    /**
     * Атрибуты, которые должны быть преобразованы к нативным типам.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'count' => 'integer',
        // Добавьте is_available, если он есть в БД и булев
        // 'is_available' => 'boolean',
    ];

    /**
     * Получить опции для генерации слага.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate(); // Не обновлять слаг при изменении имени (по желанию)
    }

    // --- Отношения ---
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function kpfc(): BelongsTo
    {
        return $this->belongsTo(KPFC::class, 'k_p_f_c_id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    // --- Accessors ---

    /**
     * Получить все вариации (размеры) этого продукта (по имени).
     */
    protected function variations(): Attribute
    {
        return Attribute::make(
        // Кеширование этого результата может быть полезно, если вызывается часто
            get: fn() => Product::where('name', $this->name)
                ->with('size') // Загружаем размер для каждой вариации
                ->orderBy('size_id') // Сортируем по размеру (или по другому полю, если нужно)
                ->get(),
        );
    }

    /**
     * Определяет, управляется ли остаток для этого продукта (штучный товар).
     * Остатком управляются Сытная еда (1) и Десерты (4).
     * Исключите конкретные товары по имени, если для них остаток не важен (например, сырники).
     */
    protected function isStockManaged(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (in_array($attributes['category_id'], [1, 2, 4])) { // Сытная еда или Десерты
                    // Пример исключения:
                    // if (strtolower($attributes['name']) === 'сырники') {
                    //     return false;
                    // }
                    return true;
                }
                return false;
            }
        );
    }

    // Доступен ли сахар (для Кофе(3) и Не кофе/Напитков(2))
    protected function canAddSugar(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => in_array($attributes['category_id'], [2, 3]),
        );
    }

    // Доступна ли корица (только для Кофе(3))
    protected function canAddCinnamon(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['category_id'] === 3,
        );
    }

    // Доступно ли доп. молоко (только для Кофе(3))
    protected function canAddMilk(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['category_id'] === 3,
        );
    }

    // Доступен ли сироп (для Кофе(3) и Не кофе/Напитков(2))
    protected function canAddSyrup(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => in_array($attributes['category_id'], [2, 3]),
        );
    }

    // Доступна ли сгущенка (для Десертов(4), например, сырников)
    protected function canAddCondensedMilk(): Attribute
    {
        return Attribute::make(
        // Сверяем по имени продукта (если только для сырников)
            get: fn($value, $attributes) => strtolower($attributes['name']) === 'сырники',
        // Или по категории Десерты (ID=4)
        //get: fn($value, $attributes) => $attributes['category_id'] === 4,
        );
    }
}
