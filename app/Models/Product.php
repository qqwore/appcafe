<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // Для accessors
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ... (PHPDoc) ...
 * @property-read bool $can_add_sugar
 * @property-read bool $can_add_cinnamon
 * @property-read bool $can_add_milk
 * @property-read bool $can_add_syrup
 * @property-read bool $can_add_condensed_milk
 * @property-read array|null $available_sizes_ids // Пример
 */
class Product extends Model
{
    use HasFactory;
    protected $fillable = [/*...*/ 'photo', /*...*/ 'category_id', 'size_id']; // Добавьте все нужные

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function size(): BelongsTo
    {
        // Если связь с размером есть через size_id
        return $this->belongsTo(Size::class);
    }

    public function kpfc(): BelongsTo
    {
        // Если связь с КБЖУ есть через k_p_f_c_id
        return $this->belongsTo(KPFC::class, 'k_p_f_c_id');
    }


    // --- Accessors для определения доступных опций ---

    // Доступен ли сахар (для Кофе и Не кофе)
    protected function canAddSugar(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->category_id, [2, 3]), // ID Не кофе и Кофе
        );
    }

    // Доступна ли корица (только для Кофе)
    protected function canAddCinnamon(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->category_id === 3, // ID Кофе
        );
    }

    // Доступно ли доп. молоко (только для Кофе)
    protected function canAddMilk(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->category_id === 3, // ID Кофе
        );
    }

    // Доступен ли сироп (для Кофе и Не кофе)
    protected function canAddSyrup(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->category_id, [2, 3]), // ID Не кофе и Кофе
        );
    }

    // Доступна ли сгущенка (только для сырников)
    protected function canAddCondensedMilk(): Attribute
    {
        return Attribute::make(
        // Сверяем по имени продукта ИЛИ по категории Десерты(4)? Выберите что точнее.
            get: fn () => strtolower($this->name) === 'сырники', // По имени
        // get: fn () => $this->category_id === 4, // Или по категории Десерты
        );
    }

    // --- Получение доступных размеров (если нужно) ---
    // Этот accessor понадобится, если в корзине можно менять размер
    // Он найдет все продукты с таким же именем, но разными size_id
    protected function availableSizesIds(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Оптимизация: Кешировать результат, если запросов много
                return Product::where('name', $this->name)
                    ->whereNotNull('size_id')
                    ->pluck('size_id') // Получаем только ID размеров
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            }
        );
    }
    // Не забудьте добавить 'available_sizes_ids' в PHPDoc @property-read

}
