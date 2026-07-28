<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Đặt hàng thành công',
                'data' => [
                    'order_id' => $order->id,
                    'created_at' => $order->created_at->format('d-m-Y H:i:s'),
                    'estimated_delivery' => $order->created_at->addDays(3)->format('d-m-Y H:i:s'),
                    'total' => $order->total_amount,
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
