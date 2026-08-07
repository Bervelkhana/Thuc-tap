<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiChatService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.openai.com/v1/chat/completions';
    protected $model = 'gpt-3.5-turbo';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * Send a message and get AI response
     */
    public function chat(string $userMessage, array $conversationHistory = []): string
    {
        // Build messages array with system prompt and conversation history
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt()
            ]
        ];

        // Add conversation history
        foreach ($conversationHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if ($response->failed()) {
                \Log::error('OpenAI API Error: ' . $response->body());
                return 'Xin lỗi, tôi gặp sự cố kết nối. Vui lòng thử lại.';
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? 'Không có phản hồi từ AI.';
        } catch (\Exception $e) {
            \Log::error('AI Chat Error: ' . $e->getMessage());
            return 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }

    /**
     * System prompt for the AI
     */
    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Bạn là trợ lý tư vấn mua sắm linh kiện máy tính cho cửa hàng TechGear. 
Nhiệm vụ của bạn:
1. Giúp khách hàng tìm kiếm sản phẩm phù hợp
2. Tư vấn cấu hình PC theo nhu cầu (gaming, design, office, etc)
3. Trả lời các câu hỏi về thông số kỹ thuật
4. Gợi ý linh kiện tương thích
5. Hỗ trợ so sánh các mặt hàng

Hướng dẫn:
- Trả lời ngắn gọn, rõ ràng, dễ hiểu
- Nếu cần biết thêm thông tin hãy hỏi lại (ngân sách, mục đích, yêu cầu)
- Luôn thân thiện và chuyên nghiệp
- Nếu không chắc chắn, hãy đề xuất tư vấn thêm từ nhân viên
- Chỉ trả lời liên quan đến máy tính, linh kiện, build PC
PROMPT;
    }
}
