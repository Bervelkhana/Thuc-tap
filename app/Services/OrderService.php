<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use DB;
use Exception;

class OrderService
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Tạo order từ cart items
     */
    public function createOrder(array $orderData, array $items): Order
    {
        return DB::transaction(function () use ($orderData, $items) {
            // Validate tất cả items trước
            foreach ($items as $item) {
                if (!$this->productService->checkStock($item['product_id'], $item['quantity'])) {
                    throw new Exception("Sản phẩm {$item['product_id']} không đủ tồn kho");
                }
            }

            // Tính total amount từ items
            $totalAmount = 0;
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $totalAmount += $product->price * $item['quantity'];
            }

            // Tạo order
            $order = Order::create([
                'user_id' => $orderData['user_id'] ?? null,
                'total_amount' => $totalAmount,
                'payment_method' => $orderData['payment_method'] ?? 'cod',
                'status' => 'pending',
            ]);

            // Tạo order items và giảm tồn kho
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'product_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $product->price * $item['quantity'],
                ]);

                // Giảm tồn kho
                $this->productService->decreaseStock($item['product_id'], $item['quantity']);
            }

            return $order;
        });
    }

    /**
     * Lấy chi tiết order
     */
    public function getOrder(int $orderId): Order
    {
        return Order::with('items')->findOrFail($orderId);
    }

    /**
     * Lấy danh sách orders của user
     */
    public function getUserOrders(int $userId, array $params = [])
    {
        $query = Order::where('user_id', $userId)
                      ->with('items')
                      ->orderBy('created_at', 'desc');

        // Filter by status
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $perPage = (int)($params['per_page'] ?? 10);
        return $query->paginate($perPage);
    }

    /**
     * Cập nhật status order
     */
    public function updateOrderStatus(int $orderId, string $status): Order
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);
        return $order;
    }

    /**
     * Hủy order và hoàn tồn kho
     */
    public function cancelOrder(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $order = Order::findOrFail($orderId);

            if (!in_array($order->status, ['pending', 'confirmed'])) {
                throw new Exception('Không thể hủy order ở trạng thái này');
            }

            // Hoàn tồn kho
            foreach ($order->items as $item) {
                $this->productService->increaseStock($item->product_id, $item->quantity);
            }

            // Cập nhật status
            $order->update(['status' => 'cancelled']);
            return true;
        });
    }
}
