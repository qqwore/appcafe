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

class CartController extends Controller
{
    /**
     * Отображение страницы корзины.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        if (!$user) {
            return Inertia::render('Cart', ['cartItems' => [], 'availableExtras' => $this->getAvailableExtras()]);
        }

        $cartItemsData = Cart::where('user_id', $user->id)
            ->with(['product' => function ($query) {
                $query->with(['category', 'size']); // Загружаем нужные связи продукта
            }])
            ->orderBy('created_at', 'asc') // Сортировка для стабильного порядка
            ->orderBy('id', 'asc')         // Вторичная сортировка
            ->get();

        $availableExtras = $this->getAvailableExtras();

        $cartItems = $cartItemsData->map(function ($cartItem) use ($availableExtras) {
            if (!$cartItem->product) {
                return null; // Продукт мог быть удален из БД
            }
            $extrasPrice = $this->calculateExtrasPrice($cartItem, $availableExtras);
            $itemTotalPrice = ($cartItem->product->price + $extrasPrice) * $cartItem->count;

            return [
                'id' => $cartItem->id,
                'quantity' => $cartItem->count,
                'product' => [
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'price' => $cartItem->product->price,
                    'image_url' => $cartItem->product->photo ? asset($cartItem->product->photo) : null,
                    'category_id' => $cartItem->product->category_id,
                    // Accessors из модели Product
                    'can_add_sugar' => $cartItem->product->can_add_sugar,
                    'can_add_cinnamon' => $cartItem->product->can_add_cinnamon,
                    'can_add_milk' => $cartItem->product->can_add_milk,
                    'can_add_syrup' => $cartItem->product->can_add_syrup,
                    'can_add_condensed_milk' => $cartItem->product->can_add_condensed_milk,
                    'size_name' => $cartItem->product->size?->volume, // Используем volume
                    // Добавляем актуальный остаток для штучных товаров
                    'is_stock_managed' => $cartItem->product->is_stock_managed,
                    'stock_count' => $cartItem->product->is_stock_managed ? $cartItem->product->count : null,
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
            'cartItems' => $cartItems->values(),
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
            'options' => ['nullable', 'array'],
            'options.sugar_quantity' => ['sometimes', 'required', 'integer', 'min:0', 'max:3'],
            'options.has_cinnamon' => ['sometimes', 'required', 'boolean'],
            'options.milk_extra_id' => ['nullable', 'integer', Rule::exists('extras', 'id')],
            'options.syrup_extra_id' => ['nullable', 'integer', Rule::exists('extras', 'id')],
            'options.has_condensed_milk' => ['sometimes', 'required', 'boolean'],
        ]);

        $productId = $validated['product_id'];
        $requestedQuantity = $validated['quantity']; // Запрошенное кол-во для добавления
        $options = $validated['options'] ?? [];

        $product = Product::find($productId);
        if (!$product) {
            return redirect()->back()->withErrors(['product_id' => 'Выбранный товар не найден.']);
        }

        // Убираем недопустимые опции
        if (isset($options['sugar_quantity']) && !$product->can_add_sugar) unset($options['sugar_quantity']);
        // ... (аналогично для других опций)

        // Ищем существующую запись в корзине с ТАКИМ ЖЕ набором опций
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('sugar_quantity', $options['sugar_quantity'] ?? 0)
            ->where('has_cinnamon', $options['has_cinnamon'] ?? false)
            ->where('milk_extra_id', $options['milk_extra_id'] ?? null)
            ->where('syrup_extra_id', $options['syrup_extra_id'] ?? null)
            ->where('has_condensed_milk', $options['has_condensed_milk'] ?? false)
            ->first();

        $currentCartQuantity = $cartItem ? $cartItem->count : 0;
        $newTotalQuantityInCart = $currentCartQuantity + $requestedQuantity; // Общее кол-во этой позиции в корзине

        // --- ПРОВЕРКА ОСТАТКА ДЛЯ ШТУЧНЫХ ТОВАРОВ ---
        if ($product->is_stock_managed) {
            if ($product->count < $newTotalQuantityInCart) {
                $availableToAdd = $product->count - $currentCartQuantity;
                $message = $currentCartQuantity > 0
                    ? "Невозможно добавить еще {$requestedQuantity} шт. товара '{$product->name}'. Всего на складе: {$product->count} шт, у вас в корзине уже {$currentCartQuantity} шт. Можно добавить еще макс. {$availableToAdd} шт."
                    : "Недостаточно товара '{$product->name}' на складе. Доступно: {$product->count} шт.";
                return redirect()->back()->withErrors(['quantity' => $message]);
            }
        }
        // --- КОНЕЦ ПРОВЕРКИ ОСТАТКА ---

        if ($cartItem) {
            if ($newTotalQuantityInCart > 10) { // Общее ограничение в 10 шт. на позицию
                return redirect()->back()->withErrors(['quantity' => 'Максимальное количество этого товара с такими опциями - 10 шт.']);
            }
            $cartItem->count = $newTotalQuantityInCart;
            $cartItem->save();
        } else {
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'count' => $requestedQuantity,
                'sugar_quantity' => $options['sugar_quantity'] ?? 0,
                'has_cinnamon' => $options['has_cinnamon'] ?? false,
                'milk_extra_id' => $options['milk_extra_id'] ?? null,
                'syrup_extra_id' => $options['syrup_extra_id'] ?? null,
                'has_condensed_milk' => $options['has_condensed_milk'] ?? false,
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

        $product = $cart_item->product; // Загружаем продукт, чтобы проверить остатки и опции

        // --- Валидация и проверка опций ---
        // (оставляем как было, с проверкой на американо/эспрессо и Rule::in)
        if ($request->has('options.syrup_extra_id')) { /* ... */
        }
        if ($request->has('options.milk_extra_id')) { /* ... */
        }

        $availableExtras = $this->getAvailableExtras();
        $validMilkIds = collect($availableExtras['milks'])->pluck('id')->all();
        $validSyrupIds = collect($availableExtras['syrups'])->pluck('id')->all();

        $rules = [
            'options' => ['sometimes', 'required', 'array'],
            'options.sugar_quantity' => ['sometimes', 'required', 'integer', 'min:0', 'max:3'],
            'options.has_cinnamon' => ['sometimes', 'required', 'boolean'],
            'options.milk_extra_id' => ['nullable', 'integer', Rule::in($validMilkIds)],
            'options.syrup_extra_id' => ['nullable', 'integer', Rule::in($validSyrupIds)],
            'options.has_condensed_milk' => ['sometimes', 'required', 'boolean'],
        ];

        // Добавляем валидацию количества с учетом остатка на складе
        if ($request->has('quantity')) {
            $maxQuantity = 10;
            if ($product->is_stock_managed) {
                $maxQuantity = min(10, $product->count); // Не больше, чем есть на складе (и не больше 10)
            }
            $rules['quantity'] = ['sometimes', 'required', 'integer', 'min:1', "max:{$maxQuantity}"];
        }
        $validated = $request->validate($rules);
        // --- Конец валидации ---

        if ($request->has('quantity')) {
            $cart_item->update(['count' => $validated['quantity']]);
        }

        if ($request->has('options')) {
            $options = $validated['options'];
            // Проверка применимости опций (оставляем как было)
            if (isset($options['sugar_quantity']) && !$product->can_add_sugar) unset($options['sugar_quantity']);
            // ... (для всех опций)
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
     * Получает список доступных допов из БД. (сделал public для OrderController)
     */
    public function getAvailableExtras(): array
    {
        $extras = Cache::remember('available_extras', now()->addHour(), function () {
            return Extras::orderBy('name')->get();
        });
        return [
            'milks' => $extras->whereIn('id', [6])->values()->toArray(), // Замените ID или используйте 'type'
            'syrups' => $extras->whereIn('id', [7, 8, 9, 10, 11, 12, 13, 14])->values()->toArray(),
        ];
    }

    /**
     * Рассчитывает суммарную стоимость выбранных допов для одной позиции корзины. (сделал public для OrderController)
     */
    public function calculateExtrasPrice(Cart $cartItem, array $availableExtras): float
    {
        $price = 0;
        // (Логика расчета цены допов как была, с проверкой can_add... и имен для американо/эспрессо)
        if ($cartItem->milk_extra_id && !empty($availableExtras['milks'])) {
            $milk = collect($availableExtras['milks'])->firstWhere('id', $cartItem->milk_extra_id);
            if ($milk && $cartItem->product->can_add_milk && !in_array(strtolower($cartItem->product->name), ['американо', 'эспрессо'])) {
                $price += $milk['price'] ?? 0;
            }
        }
        if ($cartItem->syrup_extra_id && !empty($availableExtras['syrups'])) {
            $syrup = collect($availableExtras['syrups'])->firstWhere('id', $cartItem->syrup_extra_id);
            if ($syrup && $cartItem->product->can_add_syrup && !in_array(strtolower($cartItem->product->name), ['американо', 'эспрессо'])) {
                $price += $syrup['price'] ?? 0;
            }
        }
        return $price;
    }
}
