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
use App\Http\Controllers\ProfileController;

// Используется для профиля
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminMenuStockController;
use App\Http\Controllers\Admin\AdminStatisticsController;

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

Route::get('/about', [PageController::class, 'about'])->name('about');


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


// --- ГРУППА МАРШРУТОВ АДМИНКИ ---
Route::prefix('admin')         // Все URL будут начинаться с /admin/...
->name('admin.')           // Имена всех роутов будут начинаться с admin. (напр., admin.dashboard)
->middleware(['auth', 'admin']) // Применяем middleware аутентификации И проверки админа
->group(function () {

    // Дашборд (Главная страница админки)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Заказы
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus'); // Обновление статуса заказа

    // Меню на день / Сток
    Route::get('/menu-stock', [AdminMenuStockController::class, 'index'])->name('menu-stock.index');
// ИЗМЕНЕН МАРШРУТ: теперь это POST для массового обновления
    Route::post('/menu-stock/update-multiple', [AdminMenuStockController::class, 'updateMultipleStock'])->name('menu-stock.updateMultiple');
// НОВЫЙ МАРШРУТ для отмены
    Route::post('/menu-stock/undo-last-update', [AdminMenuStockController::class, 'undoLastStockUpdate'])->name('menu-stock.undoLast');
    // Статистика
    Route::get('/statistics', [AdminStatisticsController::class, 'index'])->name('statistics.index');

    // Сюда можно добавить маршруты для управления пользователями, товарами, допами и т.д.
    // Route::resource('/users', AdminUserController::class); // Пример CRUD для пользователей
    // Route::resource('/products', AdminProductController::class); // Пример CRUD для продуктов

});
