<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException; // Для обработки ошибок аутентификации
use Inertia\Inertia; // <-- Импортируйте Inertia
use Inertia\Response as InertiaResponse; // <-- Импортируйте Inertia Response

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     * Отображение страницы входа.
     *
     * @return InertiaResponse
     */
    public function create(): InertiaResponse // <-- Возвращаемый тип Inertia Response
    {
        // Вместо view('auth.login') возвращаем Inertia::render()
        return Inertia::render('Auth/Login', [
            // 'canResetPassword' => Route::has('password.request'), // Пример передачи пропсов
            // 'status' => session('status'), // Пример передачи статуса сессии
        ]);
    }

    /**
     * Handle an incoming authentication request.
     * Обработка запроса на аутентификацию.
     *
     * @param  Request  $request // Замените LoginRequest на Request, если у вас нет Form Request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse // <-- Используйте ваш Form Request или простой Request
    {
        // 1. Валидация данных (можно вынести в Form Request)
        $credentials = $request->validate([
            'phone' => ['required', 'string', 'max:20'], // <-- Валидация телефона
            'password' => ['required', 'string'],
        ]);

        // 2. Попытка аутентификации
        // $request->boolean('remember') получает значение чекбокса 'remember me'
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Если аутентификация не удалась, выбрасываем ValidationException,
            // которое Laravel автоматически обработает и вернет ошибки в Inertia
            throw ValidationException::withMessages([
                // Ключ 'email' используется, чтобы ошибка отобразилась под полем email,
                // но можно использовать и общий ключ, например 'credentials'
                'phone' => __('auth.failed'), // Используем строку из lang/en/auth.php
            ]);
        }

        // 3. Регенерация сессии для безопасности
        $request->session()->regenerate();

        // 4. Редирект после успешного входа
        // redirect()->intended() попытается перенаправить на страницу,
        // которую пользователь хотел посетить до редиректа на логин,
        // или на указанный URL по умолчанию (например, '/dashboard')
        return redirect()->intended('/profile'); // <-- Замените '/dashboard' на ваш URL после логина
    }

    /**
     * Destroy an authenticated session.
     * Завершение сессии (Logout).
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/'); // Редирект на главную после выхода
    }
}
