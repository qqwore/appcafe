<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    use HasFactory;

    // Указываем, какие поля можно массово заполнять
    protected $fillable = [
        'user_id',
        'product_id',
        'count',
         'selected_size_id', // Если размер не определяется через product_id
         'sugar_quantity',
         'has_cinnamon',
         'milk_extra_id',
         'syrup_extra_id',
         'has_condensed_milk',
    ];

    // Отношение к пользователю (одна запись корзины принадлежит одному пользователю)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Отношение к продукту (одна запись корзины относится к одному продукту/вариации)
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


}
