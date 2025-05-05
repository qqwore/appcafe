<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Используем Auth
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Проверяем, авторизован ли пользователь ВООБЩЕ
        if (!Auth::check()) {
            return redirect()->route('login'); // Если нет - на страницу входа
        }

        // 2. Проверяем, является ли пользователь админом
        if (!Auth::user()->is_admin) {
            // Если не админ - можно редиректить на главную или показать ошибку 403
            // abort(403, 'Доступ запрещен.');
            return redirect()->route('home')->with('error', 'Доступ запрещен.'); // Редирект на главную с ошибкой
        }

        // Если обе проверки пройдены - пропускаем запрос дальше
        return $next($request);
    }
}
