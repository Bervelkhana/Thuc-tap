<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeminiChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $geminiChatService;

    public function __construct(GeminiChatService $geminiChatService)
    {
        $this->geminiChatService = $geminiChatService;
    }

    /**
     * Send a message and get AI response
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|max:1000',
                'history' => 'nullable|array',
            ]);

            $userMessage = $validated['message'];
            $conversationHistory = $validated['history'] ?? [];

            $aiResponse = $this->geminiChatService->chat($userMessage, $conversationHistory);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'reply' => $aiResponse,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Chat Error in Controller', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi kết nối AI: ' . $e->getMessage(),
            ], 500);
        }
    }
}

