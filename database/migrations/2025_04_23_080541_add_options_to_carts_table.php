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
        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedTinyInteger('sugar_quantity')->default(0)->after('count'); // 0-3
            $table->boolean('has_cinnamon')->default(false)->after('sugar_quantity');
            $table->foreignId('milk_extra_id')->nullable()->constrained('extras')->nullOnDelete()->after('has_cinnamon'); // ID из таблицы extras (для молока)
            $table->foreignId('syrup_extra_id')->nullable()->constrained('extras')->nullOnDelete()->after('milk_extra_id'); // ID из таблицы extras (для сиропа)
            $table->boolean('has_condensed_milk')->default(false)->after('syrup_extra_id');
             $table->foreignId('selected_size_id')->nullable()->constrained('sizes')->nullOnDelete()->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     * Отменяет изменения, сделанные методом up().
     */
    public function down(): void
    {
        // Убедимся, что таблица 'carts' существует перед модификацией
        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {

                // 1. СНАЧАЛА удаляем внешние ключи (если они были добавлены)
                // Использование массива с именем столбца надежнее,
                // Laravel сам определит стандартное имя ключа (tablename_columnname_foreign)

                // Удаляем ключ для milk_extra_id (предполагается, что он ссылается на 'extras')
                // Добавляем проверку наличия столбца перед удалением ключа для большей надежности
                if (Schema::hasColumn('carts', 'milk_extra_id')) {
                    $table->dropForeign(['milk_extra_id']);
                }

                // Удаляем ключ для syrup_extra_id (предполагается, что он ссылается на 'extras')
                if (Schema::hasColumn('carts', 'syrup_extra_id')) {
                    $table->dropForeign(['syrup_extra_id']);
                }


                 if (Schema::hasColumn('carts', 'selected_size_id')) {
                    $table->dropForeign(['selected_size_id']);
                 }


                // 2. ПОСЛЕ удаления ключей, удаляем сами столбцы
                // Можно перечислить их в массиве для dropColumn
                $columnsToDrop = [
                    'sugar_quantity',
                    'has_cinnamon',
                    'milk_extra_id',
                    'syrup_extra_id',
                    'has_condensed_milk',
                    // 'selected_size_id', // Раскомментируйте, если добавляли
                ];

                // Удаляем только те столбцы, которые существуют
                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('carts', $column)) {
                        $table->dropColumn($column);
                    }
                }
                // Альтернативно, если уверены, что все столбцы есть:
                // $table->dropColumn($columnsToDrop);

            });
        }
    }
};
