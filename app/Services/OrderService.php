<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderService
{
    protected ?ProductService $productService = null;

    public function __construct(ProductService $productService = null)
    {
        $this->productService = $productService;
    }

    public function createOrder(array $validated): Order
    {
        $items = $validated['items'] ?? [];

        if (empty($items)) {
            throw new Exception('Danh sách sản phẩm không được để trống.');
        }

        return DB::transaction(function () use ($validated, $items) {
            $groupedItems = [];
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $groupedItems[$productId] = ($groupedItems[$productId] ?? 0) + (int) $item['quantity'];
            }

            $productIds = array_keys($groupedItems);

            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($groupedItems as $productId => $totalQty) {
                if (! isset($products[$productId])) {
                    throw new Exception("Sản phẩm #{$productId} không tồn tại.");
                }

                if ($totalQty <= 0) {
                    throw new Exception("Số lượng sản phẩm phải lớn hơn 0.");
                }

                if ($products[$productId]->stock_quantity < $totalQty) {
                    throw new Exception("Sản phẩm {$products[$productId]->name} không đủ hàng.");
                }
            }

            $this->assertCpuMainboardCompatible($items, $products);

            $order = Order::create([
                'user_id' => auth()->id(),
                'status' => Order::STATUS_PENDING,
                'total_amount' => 0,
                'payment_method' => $validated['payment_method'] ?? 'cod',
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $total = 0;

            foreach ($groupedItems as $productId => $quantity) {
                $product = $products[$productId];
                $product->stock_quantity -= $quantity;
                $product->save();

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'snapshot' => [
                        'name' => $product->name,
                        'price' => $product->price,
                        'thumbnail_url' => $product->thumbnail_url,
                    ],
                ]);

                $total += $product->price * $quantity;
            }

            $order->total_amount = $total;
            $order->snapshot = [
                'items' => $order->items()->with('product')->get()->map(function (OrderItem $item) {
                    return [
                        'product_id' => $item->product_id,
                        'name' => $item->snapshot['name'] ?? ($item->product->name ?? null),
                        'price' => $item->snapshot['price'] ?? $item->price,
                        'quantity' => $item->quantity,
                        'subtotal' => ($item->snapshot['price'] ?? $item->price) * $item->quantity,
                    ];
                })->toArray(),
                'total' => $total,
                'created_at' => $order->created_at?->toIso8601String(),
            ];
            $order->save();

            Log::info('Order created', ['order_id' => $order->id, 'total' => $total]);

            return $order;
        });
    }

    public function getOrder(int $orderId): Order
    {
        return Order::with('items')->findOrFail($orderId);
    }

    public function getUserOrders(int $userId, array $params = [])
    {
        $query = Order::where('user_id', $userId)
            ->with('items')
            ->orderBy('created_at', 'desc');

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $perPage = (int) ($params['per_page'] ?? 10);

        return $query->paginate($perPage);
    }

    public function updateOrderStatus(int $orderId, string $status): Order
    {
        return DB::transaction(function () use ($orderId, $status) {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            if (! $order->transitionTo($status)) {
                throw new Exception('Không thể chuyển trạng thái order này.');
            }

            return $order;
        });
    }

    public function cancelOrder(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            if (! $order->canTransitionTo(Order::STATUS_CANCELLED)) {
                throw new Exception('Không thể hủy order ở trạng thái này.');
            }

            $productIds = $order->items()->pluck('product_id')->toArray();
            Product::whereIn('id', $productIds)->lockForUpdate()->get();

            $order->transitionTo(Order::STATUS_CANCELLED);

            Log::info('Order cancelled', ['order_id' => $order->id]);

            return true;
        });
    }

    private function assertCpuMainboardCompatible(array $items, $products): void
    {
        $cpu = null;
        $mainboard = null;

        foreach ($items as $item) {
            $product = $products[$item['product_id']] ?? null;
            if (! $product) {
                continue;
            }

            $categoryName = strtolower($product->category->name ?? '');

            if (str_contains($categoryName, 'cpu') || str_contains($categoryName, 'vi xử lý')) {
                $cpu = $product;
            }

            if (str_contains($categoryName, 'mainboard') || str_contains($categoryName, 'bo mạch chủ')) {
                $mainboard = $product;
            }
        }

        if ($cpu && $mainboard && $cpu->socket_type && $mainboard->socket_type && $cpu->socket_type !== $mainboard->socket_type) {
            throw new Exception('CPU và Mainboard không tương thích.');
        }
    }
}
