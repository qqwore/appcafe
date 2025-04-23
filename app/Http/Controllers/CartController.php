<?php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Extras;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;    // Для расчета цены с допами
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;        // Для правил валидации

class CartController extends Controller
{
    /**
     * Отображение страницы корзины.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user(); // Получаем авторизованного пользователя
        if (!$user) {
            // Если пользователь не авторизован, корзина пуста (или редирект на логин)
            return Inertia::render('Cart', ['cartItems' => [], 'availableExtras' => $this->getAvailableExtras()]);
        }

        // Получаем ВСЕ записи корзины для пользователя с подгрузкой продукта и его категории/размера
        $cartItemsData = Cart::where('user_id', $user->id)
            ->with(['product' => function ($query) {
                // Подгружаем нужные отношения продукта
                $query->with(['category', 'size']); // Добавьте 'kpfc' если нужно
            }])
            ->get();

        // Получаем доступные допы
        $availableExtras = $this->getAvailableExtras();

        // Обрабатываем каждый элемент корзины для передачи во Vue
        $cartItems = $cartItemsData->map(function ($cartItem) use ($availableExtras) {
            if (!$cartItem->product) { // Если продукт был удален из БД, пропускаем
                return null;
            }
            // Рассчитываем цену допов для этой позиции
            $extrasPrice = $this->calculateExtrasPrice($cartItem, $availableExtras);

            // Рассчитываем итоговую цену за позицию (цена продукта + цена допов) * количество
            $itemTotalPrice = ($cartItem->product->price + $extrasPrice) * $cartItem->count;

            // Формируем данные для Vue
            return [
                'id' => $cartItem->id, // ID записи в таблице carts
                'quantity' => $cartItem->count,
                'product' => [ // Данные о продукте
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'price' => $cartItem->product->price, // Базовая цена вариации
                    'image_url' => $cartItem->product->photo ? asset($cartItem->product->photo) : null,
                    'category_id' => $cartItem->product->category_id,
                    // Передаем флаги доступности опций из Accessors
                    'can_add_sugar' => $cartItem->product->can_add_sugar,
                    'can_add_cinnamon' => $cartItem->product->can_add_cinnamon,
                    'can_add_milk' => $cartItem->product->can_add_milk,
                    'can_add_syrup' => $cartItem->product->can_add_syrup,
                    'can_add_condensed_milk' => $cartItem->product->can_add_condensed_milk,
                    // Передаем доступные размеры (если нужно менять в корзине)
                    // 'available_sizes_ids' => $cartItem->product->available_sizes_ids,
                    // Можно передать имя текущего размера, если есть
                    'size_name' => $cartItem->product->size?->name,
                ],
                'selected_options' => [ // Выбранные опции
                    // 'size' => $cartItem->product->size?->name, // Текущий размер
                    'sugar_quantity' => $cartItem->sugar_quantity ?? 0,
                    'has_cinnamon' => (bool)($cartItem->has_cinnamon ?? false),
                    'milk_extra_id' => $cartItem->milk_extra_id,
                    'syrup_extra_id' => $cartItem->syrup_extra_id,
                    'has_condensed_milk' => (bool)($cartItem->has_condensed_milk ?? false),
                ],
                'item_total_price' => round($itemTotalPrice, 2), // Итоговая цена за позицию
            ];
        })->filter(); // Убираем null элементы (если продукт был удален)

        return Inertia::render('Cart', [
            'cartItems' => $cartItems->values(), // Передаем обработанные данные
            'availableExtras' => $availableExtras,
            'orderSuccess' => (bool)$request->session()->get('orderSuccess', false), // Получаем флаг из сессии
        ]);
    }

    /**
     * Добавление товара в корзину (вызывается из Menu.vue).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login'); // Редирект на логин, если не авторизован

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'], // Ограничение кол-ва
        ]);

        $productId = $validated['product_id'];
        $quantity = $validated['quantity'];

        // Ищем существующую запись в корзине для этого продукта БЕЗ УЧЕТА ОПЦИЙ (т.к. добавляем стандартную)
        // Если нужно различать по опциям - логика усложнится
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            // ->where('milk_extra_id', null) // Пример: ищем только записи без молока
            // ->where(...) // И т.д. для всех опций по умолчанию
            ->first();

        if ($cartItem) {
            // Если нашли - обновляем количество, проверяя максимум
            $newQuantity = $cartItem->count + $quantity;
            if ($newQuantity > 10) {
                // Можно вернуть ошибку или просто установить 10
                return redirect()->back()->withErrors(['quantity' => 'Максимальное количество товара в корзине - 10 шт.']);
                // $cartItem->count = 10;
            } else {
                $cartItem->count = $newQuantity;
            }
            $cartItem->save();
        } else {
            // Если не нашли - создаем новую запись с опциями по умолчанию
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'count' => $quantity,
                // Устанавливаем опции по умолчанию
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
    public function update(Request $request, Cart $cart_item): RedirectResponse // Используем Route Model Binding
    {
        // Проверяем, что пользователь может изменять эту запись корзины
        if ($request->user()->id !== $cart_item->user_id) {
            abort(403);
        }

        // Получаем ID допустимых допов
        $availableExtras = $this->getAvailableExtras();
        $validMilkIds = collect($availableExtras['milks'])->pluck('id')->all(); // Массив ID молока [6]
        $validSyrupIds = collect($availableExtras['syrups'])->pluck('id')->all(); // Массив ID сиропов [7, 8, ...]

        // Валидация данных
        $validated = $request->validate([
            'quantity' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'options' => ['sometimes', 'required', 'array'],
            'options.sugar_quantity' => ['sometimes', 'required', 'integer', 'min:0', 'max:3'],
            'options.has_cinnamon' => ['sometimes', 'required', 'boolean'],
            // ИСПОЛЬЗУЕМ Rule::in() для проверки ID
            'options.milk_extra_id' => ['nullable', 'integer', Rule::in($validMilkIds)],
            'options.syrup_extra_id' => ['nullable', 'integer', Rule::in($validSyrupIds)],
            'options.has_condensed_milk' => ['sometimes', 'required', 'boolean'],
        ]);

        if ($request->has('quantity')) {
            $cart_item->update(['count' => $validated['quantity']]);
        }

        if ($request->has('options')) {
            $options = $validated['options'];
            // Проверяем применимость опций к товару перед обновлением (ВАЖНО!)
            $product = $cart_item->product; // Получаем связанный продукт

            if (isset($options['sugar_quantity']) && !$product->can_add_sugar) unset($options['sugar_quantity']);
            if (isset($options['has_cinnamon']) && !$product->can_add_cinnamon) unset($options['has_cinnamon']);
            if (isset($options['milk_extra_id']) && !$product->can_add_milk) unset($options['milk_extra_id']);
            if (isset($options['syrup_extra_id']) && !$product->can_add_syrup) unset($options['syrup_extra_id']);
            if (isset($options['has_condensed_milk']) && !$product->can_add_condensed_milk) unset($options['has_condensed_milk']);

            // Обновляем только разрешенные опции
            $cart_item->update($options);
        }

        return redirect()->back(); // Возвращаемся в корзину
    }

    /**
     * Удаление товара из корзины.
     */
    public function destroy(Request $request, Cart $cart_item): RedirectResponse
    {
        // Проверяем права
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


    // --- Вспомогательные методы ---

    /**
     * Получает список доступных допов из БД.
     */
    private function getAvailableExtras(): array
    {
        $extras = Extras::orderBy('name')->get();
        // Группируем по типу (предполагаем, что есть поле type или определяем по ID/имени)
        return [
            // Фильтруем по ID или добавляем поле 'type' в таблицу extras
            'milks' => $extras->whereIn('id', [6])->values()->toArray(), // Пример ID для молока
            'syrups' => $extras->whereIn('id', [7, 8, 9, 10, 11, 12, 13, 14])->values()->toArray(), // Пример ID для сиропов
            // 'condensed_milk_price' => $extras->firstWhere('id', 5)?->price ?? 0, // Цена сгущенки
        ];
    }

    /**
     * Рассчитывает суммарную стоимость выбранных допов для одной позиции корзины.
     */
    private function calculateExtrasPrice(Cart $cartItem, array $availableExtras): float
    {
        $price = 0;
        // Цена молока
        if ($cartItem->milk_extra_id && isset($availableExtras['milks'])) {
            $milk = collect($availableExtras['milks'])->firstWhere('id', $cartItem->milk_extra_id);
            $price += $milk['price'] ?? 0;
        }
        // Цена сиропа
        if ($cartItem->syrup_extra_id && isset($availableExtras['syrups'])) {
            $syrup = collect($availableExtras['syrups'])->firstWhere('id', $cartItem->syrup_extra_id);
            $price += $syrup['price'] ?? 0;
        }
        // Цена сгущенки (если она платная и выбрана)
        // if ($cartItem->has_condensed_milk && isset($availableExtras['condensed_milk_price'])) {
        //     $price += $availableExtras['condensed_milk_price'];
        // }

        // Добавьте цену за сахар/корицу, если они платные
        // if ($cartItem->sugar_quantity > 0) $price += SUGAR_PRICE_PER_UNIT * $cartItem->sugar_quantity;
        // if ($cartItem->has_cinnamon) $price += CINNAMON_PRICE;

        return $price;
    }
}
