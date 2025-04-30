<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            // Добавляем столбцы для опций после 'extra_id' (или 'count')
            $table->unsignedTinyInteger('sugar_quantity')->default(0)->after('count');
            $table->boolean('has_cinnamon')->default(false)->after('sugar_quantity');
            $table->foreignId('milk_extra_id')->nullable()->constrained('extras')->nullOnDelete()->after('has_cinnamon');
            $table->foreignId('syrup_extra_id')->nullable()->constrained('extras')->nullOnDelete()->after('milk_extra_id');
            $table->boolean('has_condensed_milk')->default(false)->after('syrup_extra_id');

            // Добавляем столбцы для хранения цены на момент заказа
            $table->decimal('unit_price', 8, 2)->nullable()->after('has_condensed_milk'); // Цена за 1 шт. базового продукта
            $table->decimal('extras_price', 8, 2)->nullable()->default(0.00)->after('unit_price'); // Цена допов для 1 шт.

            // Удаляем старый extra_id, если он больше не нужен и не используется
            // if (Schema::hasColumn('order_products', 'extra_id')) {
            //    $table->dropForeign(['extra_id']); // Сначала удалить внешний ключ, если есть
            //    $table->dropColumn('extra_id');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $columnsToDrop = [
                'sugar_quantity',
                'has_cinnamon',
                'milk_extra_id', // Сначала удаляем ключ, потом столбец
                'syrup_extra_id', // Сначала удаляем ключ, потом столбец
                'has_condensed_milk',
                'unit_price',
                'extras_price',
            ];
            // Удаляем внешние ключи, если они есть
            if (Schema::hasColumn('order_products', 'milk_extra_id')) $table->dropForeign(['milk_extra_id']);
            if (Schema::hasColumn('order_products', 'syrup_extra_id')) $table->dropForeign(['syrup_extra_id']);
            // Удаляем столбцы
            $table->dropColumn($columnsToDrop);
            // Если удаляли extra_id, здесь нужно его вернуть (или в отдельной миграции)
            // $table->foreignId('extra_id')->nullable()->constrained('extras');
        });
    }
};
