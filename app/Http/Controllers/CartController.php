<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Extras;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Добавлен импорт

class CartController extends Controller
{
    /**
     * Отображение страницы корзины.
     */
    /**
     * Отображение страницы корзины.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        if (!$user) {
            return Inertia::render('Cart', ['cartItems' => [], 'availableExtras' => $this->getAvailableExtras()]);
        }

        // --- ЗАПРОС ДАННЫХ КОРЗИНЫ ---
        $cartItemsData = Cart::where('user_id', $user->id)
            ->with(['product' => function ($query) {
                $query->with(['category', 'size']);
            }])
            ->orderBy('created_at', 'asc') // Сортируем по времени добавления
            ->orderBy('id', 'asc')         // <-- ДОБАВЛЯЕМ сортировку по ID как вторую
            ->get();


        $availableExtras = $this->getAvailableExtras();

        // Обрабатываем каждый элемент корзины для передачи во Vue
        $cartItems = $cartItemsData->map(function ($cartItem) use ($availableExtras) {
            if (!$cartItem->product) {
                return null;
            }
            $extrasPrice = $this->calculateExtrasPrice($cartItem, $availableExtras);
            $itemTotalPrice = ($cartItem->product->price + $extrasPrice) * $cartItem->count;

            // Формируем данные для Vue
            return [
                'id' => $cartItem->id,
                'quantity' => $cartItem->count,
                'product' => [
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'price' => $cartItem->product->price,
                    'image_url' => $cartItem->product->photo ? asset($cartItem->product->photo) : null,
                    'category_id' => $cartItem->product->category_id,
                    'can_add_sugar' => $cartItem->product->can_add_sugar,
                    'can_add_cinnamon' => $cartItem->product->can_add_cinnamon,
                    'can_add_milk' => $cartItem->product->can_add_milk,
                    'can_add_syrup' => $cartItem->product->can_add_syrup,
                    'can_add_condensed_milk' => $cartItem->product->can_add_condensed_milk,
                    'size_name' => $cartItem->product->size?->name,
                ],
                'selected_options' => [
                    'sugar_quantity' => $cartItem->sugar_quantity ?? 0,
                    'has_cinnamon' => (bool)($cartItem->has_cinnamon ?? false),
                    'milk_extra_id' => $cartItem->milk_extra_id,
                    'syrup_extra_id' => $cartItem->syrup_extra_id,
                    'has_condensed_milk' => (bool)($cartItem->has_condensed_milk ?? false),
                ],
                'item_total_price' => round($itemTotalPrice, 2),
            ];
        })->filter();

        return Inertia::render('Cart', [
            'cartItems' => $cartItems->values(), // Передаем обработанные данные
            'availableExtras' => $availableExtras,
            'orderSuccess' => (bool)$request->session()->get('orderSuccess', false),
        ]);
    }

    /**
     * Добавление товара в корзину.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $productId = $validated['product_id'];
        $quantity = $validated['quantity'];

        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            // Добавьте сюда where для опций по умолчанию, если нужно различать
            // ->whereNull('milk_extra_id')
            // ->whereNull('syrup_extra_id')
            // ...
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->count + $quantity;
            if ($newQuantity > 10) {
                return redirect()->back()->withErrors(['quantity' => 'Максимальное количество товара в корзине - 10 шт.']);
            } else {
                $cartItem->count = $newQuantity;
            }
            $cartItem->save();
        } else {
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'count' => $quantity,
                'sugar_quantity' => 0,
                'has_cinnamon' => false,
                'milk_extra_id' => null,
                'syrup_extra_id' => null,
                'has_condensed_milk' => false,
            ]);
        }

        return redirect()->back()->with('message', 'Товар добавлен в корзину!');
    }

    /**
     * Обновление количества или опций товара в корзине.
     */
    public function update(Request $request, Cart $cart_item): RedirectResponse
    {
        if ($request->user()->id !== $cart_item->user_id) {
            abort(403);
        }

        $product = $cart_item->product; // Получаем связанный продукт для проверок

        // --- Дополнительная проверка перед основной валидацией ---
        if ($request->has('options.syrup_extra_id')) {
            $syrupId = $request->input('options.syrup_extra_id');
            if ($syrupId !== null) {
                $productNameLower = strtolower($product->name);
                if (in_array($productNameLower, ['американо', 'эспрессо'])) {
                    throw ValidationException::withMessages([
                        'options.syrup_extra_id' => 'Сироп нельзя добавить к этому напитку.',
                    ]);
                }
            }
        }
        // Проверка для молока
        if ($request->has('options.milk_extra_id')) {
            $milkId = $request->input('options.milk_extra_id');
            if ($milkId !== null) { // Пытаемся добавить доп. молоко
                $productNameLower = strtolower($product->name);
                if (in_array($productNameLower, ['американо', 'эспрессо'])) {
                    throw ValidationException::withMessages([
                        'options.milk_extra_id' => 'Молоко нельзя добавить к этому напитку.',
                    ]);
                }
            }
        }
        // --- Конец дополнительной проверки ---


        $availableExtras = $this->getAvailableExtras();
        $validMilkIds = collect($availableExtras['milks'])->pluck('id')->all();
        $validSyrupIds = collect($availableExtras['syrups'])->pluck('id')->all();

        $validated = $request->validate([
            'quantity' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'options' => ['sometimes', 'required', 'array'],
            'options.sugar_quantity' => ['sometimes', 'required', 'integer', 'min:0', 'max:3'],
            'options.has_cinnamon' => ['sometimes', 'required', 'boolean'],
            'options.milk_extra_id' => ['nullable', 'integer', Rule::in($validMilkIds)],
            'options.syrup_extra_id' => ['nullable', 'integer', Rule::in($validSyrupIds)],
            'options.has_condensed_milk' => ['sometimes', 'required', 'boolean'],
        ]);

        if ($request->has('quantity')) {
            $cart_item->update(['count' => $validated['quantity']]);
        }

        if ($request->has('options')) {
            $options = $validated['options'];

            // Проверка применимости опций к товару
            if (isset($options['sugar_quantity']) && !$product->can_add_sugar) unset($options['sugar_quantity']);
            if (isset($options['has_cinnamon']) && !$product->can_add_cinnamon) unset($options['has_cinnamon']);
            if (isset($options['milk_extra_id']) && !$product->can_add_milk) unset($options['milk_extra_id']);
            if (isset($options['syrup_extra_id']) && !$product->can_add_syrup) unset($options['syrup_extra_id']);
            if (isset($options['has_condensed_milk']) && !$product->can_add_condensed_milk) unset($options['has_condensed_milk']);

            // Дополнительно: Сбросить неактуальные опции, если их пытаются установить в null, но товар их не поддерживает
            if (isset($options['milk_extra_id']) && $options['milk_extra_id'] === null && !$product->can_add_milk) unset($options['milk_extra_id']);
            if (isset($options['syrup_extra_id']) && $options['syrup_extra_id'] === null && !$product->can_add_syrup) unset($options['syrup_extra_id']);
            // и т.д.


            $cart_item->update($options);
        }

        return redirect()->back();
    }

    /**
     * Удаление товара из корзины.
     */
    public function destroy(Request $request, Cart $cart_item): RedirectResponse
    {
        if ($request->user()->id !== $cart_item->user_id) {
            abort(403);
        }
        $cart_item->delete();
        return redirect()->back()->with('message', 'Товар удален из корзины.');
    }

    /**
     * Очистка всей корзины пользователя.
     */
    public function clear(Request $request): RedirectResponse
    {
        Cart::where('user_id', $request->user()->id)->delete();
        return redirect()->back()->with('message', 'Корзина очищена.');
    }

    /**
     * Получает список доступных допов из БД.
     */
    public function getAvailableExtras(): array
    {
        // Кеширование этого запроса может быть полезно
        $extras = Cache::remember('available_extras', now()->addHour(), function () {
            return Extras::orderBy('name')->get();
        });
        // $extras = Extras::orderBy('name')->get(); // Без кеширования

        // Предполагаем наличие поля 'type' или фильтруем по ID/имени
        return [
            'milks' => $extras->whereIn('id', [6])->values()->toArray(), // Используйте ID или ->where('type', 'milk')
            'syrups' => $extras->whereIn('id', [7, 8, 9, 10, 11, 12, 13, 14])->values()->toArray(), // Используйте ID или ->where('type', 'syrup')
            // 'condensed_milk_price' => $extras->firstWhere('id', 5)?->price ?? 0,
        ];
    }

    /**
     * Рассчитывает суммарную стоимость выбранных допов для одной позиции корзины.
     */
    public function calculateExtrasPrice(Cart $cartItem, array $availableExtras): float // Сделал public для OrderController
    {
        $price = 0;
        if ($cartItem->milk_extra_id && !empty($availableExtras['milks'])) {
            $milk = collect($availableExtras['milks'])->firstWhere('id', $cartItem->milk_extra_id);
            if ($milk && $cartItem->product->can_add_milk) { // Проверяем, что молоко можно добавить
                $price += $milk['price'] ?? 0;
            }
        }
        if ($cartItem->syrup_extra_id && !empty($availableExtras['syrups'])) {
            $syrup = collect($availableExtras['syrups'])->firstWhere('id', $cartItem->syrup_extra_id);
            if ($syrup && $cartItem->product->can_add_syrup && !in_array(strtolower($cartItem->product->name), ['американо', 'эспрессо'])) { // Проверяем, что сироп можно добавить
                $price += $syrup['price'] ?? 0;
            }
        }

        // Добавьте расчет для сахара, корицы, сгущенки, если они ПЛАТНЫЕ

        return $price;
    }
}
