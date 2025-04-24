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
        Schema::table('orders', function (Blueprint $table) {
            // Добавляем столбец status после 'price' или где удобно
            // 'Preparing', 'Ready', 'Completed', 'Cancelled' - возможные значения
            $table->string('status')->default('Preparing')->after('price');
            $table->index('status'); // Добавляем индекс
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']); // Удаляем индекс
            $table->dropColumn('status');
        });
    }
};
