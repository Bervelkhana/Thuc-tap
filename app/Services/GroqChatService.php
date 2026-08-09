<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqChatService
{
    protected $apiKey;
    protected $apiUrl;
    protected $model;
    protected $productService;
    protected $intentAnalyzer;

    public function __construct(ProductService $productService, IntentAnalyzerService $intentAnalyzer)
    {
        $this->apiKey = (string) config('services.groq.api_key', '');
        $this->apiUrl = (string) config('services.groq.api_url', 'https://api.groq.com/openai/v1');
        $this->model = (string) config('services.groq.model', 'llama-3.3-70b-versatile');
        $this->productService = $productService;
        $this->intentAnalyzer = $intentAnalyzer;
    }

    public function chat(string $userMessage, array $conversationHistory = []): string
    {
        try {
            $systemPrompt = $this->getSystemPrompt();
            $productContext = $this->buildProductContext($userMessage);

            if ($this->isInDemoMode()) {
                return $this->getDemoResponse($userMessage, $productContext);
            }

            $messages = $this->buildMessages($systemPrompt, $productContext, $userMessage, $conversationHistory);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->post($this->apiUrl . '/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

            if ($response->failed()) {
                Log::error('Groq API Error Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 'Xin lỗi, tôi gặp sự cố kết nối tới Groq. Vui lòng thử lại.';
            }

            $data = $response->json();
            $reply = data_get($data, 'choices.0.message.content');

            if (is_string($reply) && $reply !== '') {
                if (!mb_check_encoding($reply, 'UTF-8')) {
                    $reply = mb_convert_encoding($reply, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
                }
                return $reply;
            }

            Log::warning('Unexpected Groq response structure', ['response' => $data]);
            return 'Không có phản hồi hợp lệ từ AI.';
        } catch (\Throwable $e) {
            Log::error('Groq Chat Exception: ' . $e->getMessage());
            return 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }

    protected function isInDemoMode(): bool
    {
        return filter_var(env('GROQ_DEMO_MODE', false), FILTER_VALIDATE_BOOL) || $this->apiKey === '';
    }

    protected function getDemoResponse(string $userMessage, string $productContext): string
    {
        if (!empty($productContext)) {
            return "Tìm thấy thông tin sau:\n\n" . $productContext . "\n\nGhi chú: Đây là chế độ demo hoặc chưa cấu hình API key Groq.";
        }

        return 'Tôi là AI Assistant. Hỏi tôi điều gì tùy bạn!';
    }

    protected function buildProductContext(string $userMessage): string
    {
        try {
            $analysis = $this->intentAnalyzer->analyze($userMessage);
            $primaryIntent = $analysis['primary_intent'];
            $extractedInfo = $this->intentAnalyzer->extractInfo($userMessage);

            $context = '';

            if ($primaryIntent['confidence'] > 0.6) {
                switch ($primaryIntent['type']) {
                    case 'stock_check':
                        $context = $this->productService->getProductsContext(10);
                        break;
                    case 'category_search':
                        if (!empty($primaryIntent['category'])) {
                            $context = $this->productService->getProductsByCategoryContext(
                                $primaryIntent['category'],
                                5
                            );
                        }
                        break;
                    case 'product_search':
                        if (!empty($extractedInfo['product_name'])) {
                            $context = $this->productService->getProductsByNameContext(
                                $extractedInfo['product_name'],
                                5
                            );
                        }
                        break;
                }
            }

            return $context;
        } catch (\Exception $e) {
            Log::warning('Failed to build product context: ' . $e->getMessage());
            return '';
        }
    }

    protected function buildMessages(string $systemPrompt, string $productContext, string $userMessage, array $history): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        if (!empty($productContext)) {
            $messages[] = [
                'role' => 'system',
                'content' => "Dữ liệu sản phẩm hiện có:\n" . $productContext,
            ];
        }

        foreach ($history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }

    protected function getSystemPrompt(): string
    {
        return "You are a computer hardware consultant for TechGear store.\n\n"
            . "Your responsibilities:\n"
            . "1. Help customers find suitable products from the inventory database\n"
            . "2. Recommend PC configurations based on their needs (gaming, design, office, etc)\n"
            . "3. Answer questions about product technical specifications\n"
            . "4. Suggest compatible components\n"
            . "5. Help compare different products\n"
            . "6. Provide information about stock availability\n\n"
            . "Guidelines:\n"
            . "- Keep answers concise, clear, and easy to understand\n"
            . "- Always reference inventory data when relevant\n"
            . "- If you need more information, ask clarifying questions (budget, purpose, requirements)\n"
            . "- Always be friendly and professional\n"
            . "- Only recommend products that are in stock\n"
            . "- If unsure, suggest additional consultation\n"
            . "- Only answer questions related to computers, components, and PC building\n\n"
            . "IMPORTANT: Always respond in English. Start with a friendly greeting.";
    }
}
