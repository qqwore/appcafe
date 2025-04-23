<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile information.
     */
    public function show(Request $request): Response
    {
        // Просто отображаем страницу, данные пользователя
        // будут добавлены автоматически через HandleInertiaRequests middleware
        return Inertia::render('Auth/Dashboard'); // Укажите правильное имя вашего Vue компонента
    }
}
