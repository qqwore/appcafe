<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Order extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'price', 'type', 'status'];


    public function user(): BelongsTo // <-- Тип для belongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function orderProducts(): HasMany // <-- Используем импортированный HasMany
    {
        return $this->hasMany(OrderProducts::class);
    }
}
