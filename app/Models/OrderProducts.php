<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Используем Model, не Pivot
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProducts extends Model
{
    use HasFactory;

    // Указываем имя таблицы, если оно отличается от множественного числа имени модели
    protected $table = 'order_products';
    // Отключаем автоинкремент, если первичный ключ не 'id' или составной
    // public $incrementing = false;

    protected $fillable = [
        'order_id', 'product_id', 'count',
        'sugar_quantity', 'has_cinnamon', 'milk_extra_id',
        'syrup_extra_id', 'has_condensed_milk',
        'unit_price', 'extras_price'
    ];
    // Отключаем временные метки, если их нет в таблице
    // public $timestamps = false;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Отношения для получения названий допов (опционально)
    public function milkExtra(): BelongsTo
    {
        return $this->belongsTo(Extras::class, 'milk_extra_id');
    }

    public function syrupExtra(): BelongsTo
    {
        return $this->belongsTo(Extras::class, 'syrup_extra_id');
    }
}
