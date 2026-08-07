<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeminiChatService;
use Illuminate\Http\Request;

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

            // Get AI response from Gemini
            $aiResponse = $this->geminiChatService->chat($userMessage, $conversationHistory);
            
            // Ensure proper UTF-8 encoding
            $aiResponse = mb_convert_encoding($aiResponse, 'UTF-8', 'UTF-8');
            
            $responseData = [
                'status' => 'success',
                'data' => [
                    'reply' => $aiResponse,
                ]
            ];
            
            // Ensure all strings are valid UTF-8 before JSON encoding
            array_walk_recursive($responseData, function(&$value) {
                if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            });

            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error('Chat Error in Controller: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau.'
            ], 500);
        }
    }
}
