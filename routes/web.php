<?php

// --- Импорты ---
use Illuminate\Support\Facades\Route;
// Убрали Inertia, так как рендеринг в контроллерах
// use Inertia\Inertia;

// Контроллеры
use App\Http\Controllers\MainController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController; // Используется для профиля
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProductController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Здесь регистрируются веб-маршруты вашего приложения. Они загружаются
| RouteServiceProvider и им всем назначается группа middleware "web".
|
*/

// --- Публичные маршруты (доступны всем) ---

// Главная страница
Route::get('/', [MainController::class, 'index'])->name('home');

// Страница меню
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');

// --- Маршруты для гостей (недоступны авторизованным) ---
Route::middleware('guest')->group(function () {
    // Регистрация
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register'); // Показ формы
    Route::post('register', [RegisteredUserController::class, 'store']); // Обработка формы

    // Вход
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login'); // Показ формы
    Route::post('login', [AuthenticatedSessionController::class, 'store']); // Обработка формы
});

// --- Маршруты для АВТОРИЗОВАННЫХ пользователей ---
// УБРАЛИ 'verified' ИЗ MIDDLEWARE! Оставили только 'auth'.
Route::middleware(['auth'])->group(function () {
    // Профиль пользователя
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    // Сюда можно добавить маршруты для обновления профиля:
    // Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Выход из системы
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Корзина
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index'); // Показать корзину
    Route::post('/cart', [CartController::class, 'store'])->name('cart.add'); // Добавить товар
    Route::patch('/cart/{cart_item}', [CartController::class, 'update'])->name('cart.update'); // Обновить позицию
    Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear'); // Очистить корзину
    Route::delete('/cart/{cart_item}', [CartController::class, 'destroy'])->name('cart.destroy'); // Удалить позицию


    // Оформление заказа
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

    // Другие ваши защищенные маршруты (например, история заказов)
    // Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    // Route::get('/dashboard', function () { /* ... */ })->name('dashboard'); // Если нужен отдельный дашборд

}); // Конец группы middleware 'auth'

// --- Маршруты Админ-панели (Пример) ---
// Оставляем auth, но может потребоваться отдельный middleware 'admin'
Route::prefix('admin')->name('admin.')->middleware(['auth'/*, 'admin' */])->group(function () {
    // Маршруты вашей админки
    // Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
});

// Маршрут для страницы одного продукта (используем ID)
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
// Laravel автоматически найдет Product по ID благодаря Route Model Binding
