<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User; // <-- Убедитесь, что модель User существует и импортирована
use App\Providers\RouteServiceProvider; // Используется для редиректа по умолчанию
use Illuminate\Auth\Events\Registered; // Событие после регистрации
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse; // Используем псевдоним для Inertia\Response

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     * Отображение страницы регистрации.
     *
     * @return InertiaResponse
     */
    public function create(): InertiaResponse
    {
        // Возвращаем Inertia компонент 'Register.vue'
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     * Обработка входящего запроса на регистрацию.
     *
     * @param  Request  $request
     * @return RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Валидация входящих данных
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Добавляем правила для телефона (пример: обязательное, строка, макс. 20 символов)
            // Можно добавить 'unique:users,phone', если телефон должен быть уникальным
            'phone' => ['required', 'string', 'max:20'], // <-- Валидация телефона
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class], // Уникальность email в таблице users
            'password' => ['required', 'confirmed', Rules\Password::defaults()], // Пароль + подтверждение, используем стандартные правила Laravel
        ]);

        // 2. Создание пользователя
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone, // <-- Сохраняем телефон
            'email' => $request->email,
            'password' => Hash::make($request->password), // Хэшируем пароль
        ]);

        // 3. Генерируем событие Registered (полезно для отправки email подтверждения и т.д.)
        event(new Registered($user));

        // 4. Автоматически логиним пользователя после регистрации
        Auth::login($user);

        // 5. Редирект на страницу после успешной регистрации
        // По умолчанию редиректим на RouteServiceProvider::HOME (обычно '/dashboard')
        // Вы можете изменить это на любой другой маршрут, например, redirect()->route('dashboard')
        return redirect(RouteServiceProvider::HOME);
    }
}
