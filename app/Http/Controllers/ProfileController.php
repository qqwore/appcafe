<?php

namespace App\Http\Controllers; // Убедитесь, что неймспейс правильный

use App\Models\Order; // Используем модель Order
use App\Models\OrderProducts; // Для форматирования items
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse; // Используем псевдоним
use Illuminate\Support\Facades\Auth;     // Для получения текущего пользователя
use Illuminate\Http\RedirectResponse;   // Для типа возвращаемого значения редиректа
use Illuminate\Validation\Rule;         // Для правила unique
use Illuminate\Pagination\LengthAwarePaginator; // Для проверки типа в formatOrdersForVue

class ProfileController extends Controller
{
    /**
     * Отображение страницы профиля пользователя с его активными заказами и историей.
     * История заказов теперь пагинируется.
     */
    public function show(Request $request): InertiaResponse
    {
        $user = Auth::user(); // Получаем текущего авторизованного пользователя

        // Статусы для фильтрации заказов
        $activeStatuses = ['Preparing', 'Ready']; // Заказы, которые считаются активными
        $historyStatuses = ['Completed', 'Received', 'Cancelled']; // Заказы, которые попадают в историю

        // Получаем Активные заказы пользователя (без пагинации, их обычно немного)
        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', $activeStatuses)
            ->with([ // Жадная загрузка связанных данных
                'orderProducts' => function ($query) {
                    $query->with([
                        'product:id,name,size_id',
                        'product.size:id,volume',
                        'milkExtra:id,name',
                        'syrupExtra:id,name'
                    ]);
                }
            ])
            ->orderBy('created_at', 'desc') // Сначала самые новые активные заказы
            ->get();

        // --- ИЗМЕНЕНИЕ: Пагинация для Истории заказов ---
        $orderHistoryQuery = Order::where('user_id', $user->id)
            ->whereIn('status', $historyStatuses)
            ->with([
                'orderProducts' => function ($query) {
                    $query->with([
                        'product:id,name,size_id',
                        'product.size:id,volume',
                        'milkExtra:id,name',
                        'syrupExtra:id,name'
                    ]);
                }
            ])
            ->orderBy('created_at', 'desc');

        // Пагинируем по 5 элементов на странице
        // withQueryString() нужен, чтобы при переключении страниц сохранялся параметр ?tab=history
        $orderHistoryPaginated = $orderHistoryQuery->paginate(5)->withQueryString();
        // --- КОНЕЦ ИЗМЕНЕНИЯ ---


        // Форматируем данные заказов для удобного отображения во Vue
        $activeOrdersFormatted = $this->formatOrdersForVue($activeOrders);
        // Форматируем пагинированные данные истории
        $orderHistoryFormatted = $this->formatOrdersForVue($orderHistoryPaginated);


        // Рендерим Vue компонент 'Profile/Show.vue' и передаем ему данные
        return Inertia::render('Profile/Show', [
            'activeOrders' => $activeOrdersFormatted,
            'orderHistory' => $orderHistoryFormatted, // Теперь это пагинированный объект
            // Передаем фильтр активной вкладки, если он есть в URL (для сохранения при пагинации)
            'filters' => $request->only(['tab']),
        ]);
    }

    /**
     * Обновление данных профиля текущего аутентифицированного пользователя.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $messages = [
            'name.required' => 'Поле "Имя" обязательно для заполнения.',
            'name.string' => 'Поле "Имя" должно быть строкой.',
            'name.max' => 'Поле "Имя" не может быть длиннее :max символов.',
            'name.regex' => 'Поле "Имя" должно содержать только буквы и пробелы.',
            'email.required' => 'Поле "Email" обязательно для заполнения.',
            'email.string' => 'Поле "Email" должно быть строкой.',
            'email.email' => 'Поле "Email" должно быть действительным адресом электронной почты.',
            'email.max' => 'Поле "Email" не может быть длиннее :max символов.',
            'email.unique' => 'Этот Email уже используется другим пользователем.',
            'phone.required' => 'Поле "Телефон" обязательно для заполнения.',
            'phone.string' => 'Поле "Телефон" должно быть строкой.',
            'phone.max' => 'Поле "Телефон" не может быть длиннее :max символов.',
            'phone.unique' => 'Этот телефон уже используется другим пользователем.',
            'phone.regex' => 'Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX (11 цифр).',
        ];

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s]+$/u'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id),
                function ($attribute, $value, $fail) {
                    $cleanedPhone = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($cleanedPhone) === 11 && ($cleanedPhone[0] === '7' || $cleanedPhone[0] === '8')) {
                        return;
                    }
                    $fail('Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX (11 цифр).');
                },
            ],
        ], $messages);

        $cleanedPhone = preg_replace('/[^0-9]/', '', $validatedData['phone']);
        if (strlen($cleanedPhone) === 11 && $cleanedPhone[0] === '8') {
            $cleanedPhone = '7' . substr($cleanedPhone, 1);
        }
        $phoneToSave = '+7' . substr($cleanedPhone, -10);

        $user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $phoneToSave,
        ]);

        return redirect()->route('profile.show')->with('message', 'Данные профиля успешно обновлены!');
    }


    /**
     * Вспомогательная функция для форматирования коллекции или пагинированных заказов перед передачей во Vue.
     * @param \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $orders
     * @return \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function formatOrdersForVue($orders)
    {
        $formatFunction = function (Order $order) {
            return [
                'id' => $order->id,
                'number' => '№' . $order->id,
                'status' => $order->status,
                'created_date' => $order->created_at->format('d.m H:i'),
                'total_price' => $order->price,
                'items' => $order->orderProducts->map(function (OrderProducts $item) {
                    if (!$item->product) {
                        return [
                            'id' => $item->id,
                            'product_name' => '[Товар был удален]',
                            'quantity' => $item->count,
                            'price_per_item' => 0,
                            'options_description' => '',
                        ];
                    }

                    $optionsDesc = [];
                    if ($item->product->size?->volume) $optionsDesc[] = $item->product->size->volume;
                    if ($item->milkExtra?->name) $optionsDesc[] = $item->milkExtra->name;
                    if ($item->syrupExtra?->name) $optionsDesc[] = $item->syrupExtra->name; // Было $item.syrupExtra->name
                    if ($item->sugar_quantity > 0) $optionsDesc[] = 'Сахар x' . $item->sugar_quantity;
                    if ($item->has_cinnamon) $optionsDesc[] = 'Корица';
                    if ($item->has_condensed_milk) $optionsDesc[] = 'Сгущенка';

                    return [
                        'id' => $item->id,
                        'product_name' => $item->product->name,
                        'quantity' => $item->count,
                        'price_per_item' => round(($item->unit_price ?? 0) + ($item->extras_price ?? 0), 2),
                        'options_description' => implode(', ', $optionsDesc),
                    ];
                }),
            ];
        };

        // Проверяем, является ли $orders объектом пагинатора
        if ($orders instanceof LengthAwarePaginator) {
            return $orders->through($formatFunction); // Используем through для пагинатора
        }

        // Если это обычная коллекция
        return $orders->map($formatFunction);
    }
}
