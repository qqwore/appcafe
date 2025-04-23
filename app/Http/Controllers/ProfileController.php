<?php

// Убедитесь, что namespace правильный
namespace App\Http\Controllers;

// Добавьте необходимые use стейтменты
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProfileController extends Controller
{
    /**
     * Отображение страницы профиля пользователя.
     * Данные пользователя будут добавлены автоматически
     * middleware HandleInertiaRequests.
     */
    public function show(Request $request): InertiaResponse
    {
        // Просто рендерим Vue компонент
        // Убедитесь, что файл существует: resources/js/Pages/Profile/Show.vue
        // Или измените имя, если ваш компонент называется иначе (например, 'Dashboard')
        return Inertia::render('Profile/Show');
    }

    // Сюда можно добавить методы edit, update, destroy для профиля позже
}
