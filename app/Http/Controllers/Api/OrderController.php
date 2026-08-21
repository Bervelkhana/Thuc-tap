<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\PCCompatibilityValidator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected PCCompatibilityValidator $compatibilityValidator
    ) {
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $validated = $request->validated();
            $items = $validated['items'] ?? [];

            $productIds = array_column($items, 'product_id');
            $products = Product::whereIn('id', $productIds)->get()->all();

            if (count($products) >= 2) {
                $compatibility = $this->compatibilityValidator->validate($products);

                if (!($compatibility['is_compatible'] ?? true)) {
                    $errorMessages = array_column($compatibility['errors'], 'message');
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cấu hình PC không tương thích: ' . implode('; ', $errorMessages),
                        'data' => [
                            'compatibility' => $compatibility,
                        ],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            $order = $this->orderService->createOrder($validated);

            $serverTotal = (float) $order->total_amount;

            $clientTotal = 0;
            foreach ($items as $item) {
                $product = Product::find((int) ($item['product_id'] ?? 0));
                if ($product) {
                    $clientTotal += (float) $product->price * (int) ($item['quantity'] ?? 1);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Đặt hàng thành công',
                'data' => [
                    'order_id' => $order->id,
                    'created_at' => $order->created_at->format('d-m-Y H:i:s'),
                    'estimated_delivery' => $order->created_at->addDays(3)->format('d-m-Y H:i:s'),
                    'total' => $order->total_amount,
                    'server_total' => (int) round($serverTotal),
                    'client_total' => (int) round($clientTotal),
                    'is_total_valid' => (int) round($serverTotal) === (int) round($clientTotal),
                    'compatibility' => [
                        'is_compatible' => true,
                        'errors' => [],
                        'warnings' => [],
                        'details' => [],
                    ],
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
