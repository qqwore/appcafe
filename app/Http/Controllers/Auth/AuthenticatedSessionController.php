<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException; // Для обработки ошибок аутентификации
use Inertia\Inertia; // <-- Импортируйте Inertia
use App\Providers\RouteServiceProvider;
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
    // app/Http/Controllers/Auth/AuthenticatedSessionController.php -> store()
    public function store(Request $request): RedirectResponse // Замените LoginRequest на Request
    {
        // Кастомные сообщения
        $messages = [
            'phone.required' => 'Поле "Телефон" обязательно для заполнения.',
            // 'email.required' => 'Поле "Email" обязательно для заполнения.',
            // 'email.email'    => 'Введите корректный Email.',
            'password.required' => 'Поле "Пароль" обязательно для заполнения.',
            // Сообщение для неудачной попытки входа можно оставить стандартным или кастомизировать
        ];

        // Валидация
        $credentials = $request->validate([
            'phone' => ['required', 'string'], // Замените 'email' на 'phone', если вход по телефону
            // 'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], $messages);

        // Нормализация телефона (если вход по телефону)
        $cleanedPhoneInput = preg_replace('/[^0-9]/', '', $request->phone);
        if (strlen($cleanedPhoneInput) === 11 && $cleanedPhoneInput[0] === '8') {
            $cleanedPhoneInput = '7' . substr($cleanedPhoneInput, 1);
        }
        $phoneToAuth = '+7' . substr($cleanedPhoneInput, -10);

        // Попытка аутентификации
        if (!Auth::attempt(['phone' => $phoneToAuth, 'password' => $request->password], $request->boolean('remember'))) {
            // Если вход по email:
            // if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            // Управление Rate Limiting (если используете)
            // RateLimiter::hit($this->throttleKey($request));
// dd(
//     app()->getLocale(), // Текущая локаль
//     app('translator')->get('auth.failed'), // Как переводится ключ
//     \Illuminate\Support\Facades\Lang::has('auth.failed'), // Есть ли ключ в текущей локали?
//     \Illuminate\Support\Facades\Lang::get('auth.failed', [], 'ru'), // Принудительно получить из 'ru'
//     \Illuminate\Support\Facades\Lang::get('auth.failed', [], 'en')  // Принудительно получить из 'en'
// );
            throw ValidationException::withMessages([
                'phone' => __('auth.failed'), // Или 'email' => __('auth.failed'),
            ]);
        }

        // RateLimiter::clear($this->throttleKey($request)); // Если используете

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
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
