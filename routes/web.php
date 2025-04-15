<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// --- Маршрут для отображения формы входа ---
Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest') // Не пускать уже авторизованных
    ->name('login');

// --- Маршрут для обработки отправки формы ---
Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest'); // Не пускать уже авторизованных

Route::get('/register', [RegisteredUserController::class, 'create'])
    ->middleware('guest')
    ->name('register');

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest');
