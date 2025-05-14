<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

// Для правил пароля
use Illuminate\Validation\Rule;

// Для unique правила

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create() // Убрали InertiaResponse для совместимости с Laravel < 9, если Inertia::render используется
    {
        return \Inertia\Inertia::render('Auth/Register'); // Используем полный неймспейс Inertia
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Кастомные сообщения об ошибках на русском для регистрации
        $messages = [
            'name.required' => 'Поле "Имя" обязательно для заполнения.',
            'name.string' => 'Поле "Имя" должно быть строкой.',
            'name.max' => 'Поле "Имя" не может быть длиннее :max символов.',
            'name.regex' => 'Поле "Имя" должно содержать только буквы и пробелы.',
            'phone.required' => 'Поле "Телефон" обязательно для заполнения.',
            'phone.string' => 'Поле "Телефон" должно быть строкой.',
            'phone.max' => 'Поле "Телефон" не может быть длиннее :max символов.',
            'phone.unique' => 'Этот телефон уже зарегистрирован.',
            'phone.regex' => 'Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX (11 цифр).',
            'email.required' => 'Поле "Email" обязательно для заполнения.',
            'email.string' => 'Поле "Email" должно быть строкой.',
            'email.email' => 'Поле "Email" должно быть действительным адресом электронной почты.',
            'email.max' => 'Поле "Email" не может быть длиннее :max символов.',
            'email.unique' => 'Этот Email уже зарегистрирован.',
            'password.required' => 'Поле "Пароль" обязательно для заполнения.',
            'password.confirmed' => 'Подтверждение пароля не совпадает.',
            // Сообщения для Rules\Password::defaults() можно настроить в AppServiceProvider или здесь
            // 'password.min' => 'Пароль должен содержать не менее :min символов.',
        ];

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s]+$/u'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique(User::class), // Проверяем уникальность в таблице users
                function ($attribute, $value, $fail) {
                    $cleanedPhone = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($cleanedPhone) === 11 && ($cleanedPhone[0] === '7' || $cleanedPhone[0] === '8')) {
                        return;
                    }
                    $fail('Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX (11 цифр).');
                },
            ],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()], // Правила сложности пароля по умолчанию
        ], $messages); // Передаем кастомные сообщения

        // Нормализация телефона перед сохранением
        $cleanedPhone = preg_replace('/[^0-9]/', '', $request->phone);
        if (strlen($cleanedPhone) === 11 && $cleanedPhone[0] === '8') {
            $cleanedPhone = '7' . substr($cleanedPhone, 1);
        }
        $phoneToSave = '+7' . substr($cleanedPhone, -10);


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $phoneToSave, // Сохраняем нормализованный телефон
            'password' => Hash::make($request->password),
            // 'is_admin' => false, // По умолчанию не админ
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('profile.show')); // Редирект на профиль после регистрации
    }
}
