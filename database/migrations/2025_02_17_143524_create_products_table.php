<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Size;
use App\Models\KPFC;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', $precision = 8, $scale = 2);
            $table->string('photo')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['Напиток', 'Еда']);
            $table->integer('count')->default(0);
            $table->foreignIdFor(Category::class);
            $table->foreignIdFor(Size::class)->nullable();
            $table->foreignIdFor(KPFC::class)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
