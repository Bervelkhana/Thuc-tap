<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'payment_method' => 'nullable|in:cod,vietqr',
            'customer' => 'nullable|array',
            'customer.name' => 'nullable|string',
            'customer.phone' => 'nullable|string',
            'customer.address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $total = 0;
                $orderItems = [];
                $maxItemQty = 1;

                foreach ($request->input('items') as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    $qty = (int) $item['quantity'];

                    if ($product->stock_quantity < $qty) {
                        throw new \Exception("Sản phẩm \"{$product->name}\" chỉ còn {$product->stock_quantity} trong kho.");
                    }

                    $product->decrement('stock_quantity', $qty);

                    $total += $product->price * $qty;
                    $maxItemQty = max($maxItemQty, $qty);

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'price' => $product->price,
                    ];
                }

                // Ước tính ngày giao hàng: 3 - 5 ngày tùy điều kiện
                $deliveryDays = 3;
                if ($total >= 20000000 || $maxItemQty >= 5) {
                    $deliveryDays = 5;
                } elseif ($total >= 5000000) {
                    $deliveryDays = 4;
                }

                $createdAt = Carbon::now();
                $estimatedDelivery = $createdAt->copy()->addDays($deliveryDays);

                $order = Order::create([
                    'user_id' => $request->input('user_id'),
                    'total_amount' => $total,
                    'status' => 'pending',
                    'payment_method' => $request->input('payment_method', 'cod'),
                ]);

                $order->items()->createMany($orderItems);

                return [
                    'order' => $order,
                    'created_at' => $createdAt,
                    'estimated_delivery' => $estimatedDelivery,
                ];
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Đặt hàng thành công',
            'data' => [
                'order_id' => $result['order']->id,
                'created_at' => $result['created_at']->format('d-m-Y H:i:s'),
                'estimated_delivery' => $result['estimated_delivery']->format('d-m-Y H:i:s'),
                'total' => $result['order']->total_amount,
            ],
        ], Response::HTTP_CREATED);
    }
}
