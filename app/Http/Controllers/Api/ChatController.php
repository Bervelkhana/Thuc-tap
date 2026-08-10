<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NvidiaNimChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(NvidiaNimChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function sendMessage(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|max:1000',
                'history' => 'nullable|array',
            ]);

            $result = $this->chatService->chat(
                $validated['message'],
                $validated['history'] ?? []
            );

            return response()->json($result, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Dữ liệu đầu vào không hợp lệ.', 422, $e->errors());
        } catch (\Throwable $e) {
            Log::error('Chat Error in Controller', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'reply' => 'Có lỗi xảy ra. Vui lòng thử lại sau.',
            ], 200);
        }
    }
}


