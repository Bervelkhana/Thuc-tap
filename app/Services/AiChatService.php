<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\TimeoutException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.openai.com/v1/chat/completions';
    protected $model = 'gpt-3.5-turbo';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function chat(string $userMessage, array $conversationHistory = []): array
    {
        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt()
                ]
            ];

            foreach ($conversationHistory as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $userMessage
            ];

            $response = Http::timeout(60)
                ->connectTimeout(10)
                ->acceptJson()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

            if ($response->failed()) {
                $status = $response->status();
                $body = trim($response->body() ?: 'Empty response');

                Log::error('OpenAI API Error Response', [
                    'status' => $status,
                    'body' => $body,
                    'api_url' => $this->apiUrl,
                    'model' => $this->model,
                ]);

                if ($status === 401 || $status === 403) {
                    return [
                        'success' => false,
                        'reply' => 'Lỗi xác thực API. Vui lòng liên hệ quản trị viên.',
                    ];
                }

                if ($status === 429) {
                    return [
                        'success' => false,
                        'reply' => 'Server AI đang quá tải. Vui lòng thử lại sau 30 giây.',
                    ];
                }

                return [
                    'success' => false,
                    'reply' => 'Server AI phản hồi lỗi (' . $status . '). Vui lòng thử lại sau.',
                ];
            }

            $data = $response->json();
            $reply = $data['choices'][0]['message']['content'] ?? 'Không có phản hồi từ AI.';

            if (is_string($reply) && trim($reply) !== '') {
                return [
                    'success' => true,
                    'reply' => trim($reply),
                ];
            }

            Log::warning('Unexpected OpenAI response structure', ['response' => $data]);

            return [
                'success' => false,
                'reply' => 'AI trả về phản hồi không hợp lệ. Vui lòng thử lại.',
            ];
        } catch (TimeoutException $e) {
            Log::error('OpenAI chat timeout', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'api_url' => $this->apiUrl,
                'model' => $this->model,
                'user_message_length' => strlen($userMessage),
            ]);

            return [
                'success' => false,
                'reply' => 'Server AI đang bận xử lý yêu cầu. Vui lòng thử lại sau 1-2 phút.',
            ];
        } catch (ConnectionException $e) {
            Log::error('OpenAI connection error', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'api_url' => $this->apiUrl,
                'model' => $this->model,
            ]);

            return [
                'success' => false,
                'reply' => 'Không thể kết nối tới server AI. Vui lòng kiểm tra mạng và thử lại.',
            ];
        } catch (RequestException $e) {
            Log::error('OpenAI request error', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'api_url' => $this->apiUrl,
                'model' => $this->model,
            ]);

            return [
                'success' => false,
                'reply' => 'Yêu cầu tới AI bị lỗi. Vui lòng thử lại sau.',
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI Chat Exception', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'api_url' => $this->apiUrl,
                'model' => $this->model,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'reply' => 'Có lỗi xảy ra. Vui lòng thử lại sau.',
            ];
        }
    }

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
