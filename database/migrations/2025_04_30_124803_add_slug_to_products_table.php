<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Добавляем столбец slug после 'name' (или где удобно)
            // Он должен быть уникальным, чтобы не было двух товаров с одинаковым URL
            $table->string('slug')->nullable()->unique()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']); // Удаляем индекс уникальности
            $table->dropColumn('slug');
        });
    }
};
