<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class NvidiaNimChatService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected ProductService $productService;
    protected IntentAnalyzerService $intentAnalyzer;

    public function __construct(ProductService $productService, IntentAnalyzerService $intentAnalyzer)
    {
        $this->apiKey = trim((string) config('services.nvidia_nim.api_key', ''));
        $this->apiUrl = rtrim((string) config('services.nvidia_nim.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->model = (string) config('services.nvidia_nim.model', 'meta/llama-3.1-70b-instruct');
        $this->productService = $productService;
        $this->intentAnalyzer = $intentAnalyzer;
    }

    public function chat(string $userMessage, array $conversationHistory = []): array
    {
        try {
            // ========== FAST-FAIL ==========
            if ($validationError = $this->validateConfiguration()) {
                return ['success' => false, 'reply' => $validationError];
            }

            $productContext = $this->buildProductContext($userMessage);

            if ($this->isInDemoMode()) {
                return $this->getDemoResponse($userMessage, $productContext);
            }

            // Xử lý message hợp lệ cho API
            $messages = $this->buildMessages($this->getSystemPrompt(), $productContext, $userMessage, $conversationHistory);

            // ========== GỌI API BẰNG LARAVEL HTTP FACADE ==========
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json; charset=utf-8',
            ])
            ->timeout(15)
            ->connectTimeout(10)
            ->post($this->apiUrl . '/chat/completions', [
                'model'       => $this->model,
                'messages'    => $messages,
                'temperature' => 0.7,
                'max_tokens'  => 800, // Tăng lên 800 vì 300 token thường bị cắt cụt câu tiếng Việt
                'stream'      => false,
            ]);

            // =========== BẮT LỖI TỪ API (HTTP 4xx, 5xx) ===========
            if ($response->failed()) {
                Log::error('NVIDIA NIM API Error', [
                    'status' => $response->status(),
                    'body'   => $response->json(), // Lấy chi tiết lỗi từ API trả về để dễ fix
                    'model'  => $this->model,
                ]);

                return $this->getFallbackResponse($userMessage, $productContext, 'Lỗi từ AI API: ' . $response->status());
            }

            $reply = $response->json('choices.0.message.content');

            if (is_string($reply) && trim($reply) !== '') {
                // Fix lỗi font nếu có
                if (!mb_check_encoding($reply, 'UTF-8')) {
                    $reply = mb_convert_encoding($reply, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
                }
                return ['success' => true, 'reply' => trim($reply)];
            }

            return $this->getFallbackResponse($userMessage, $productContext, 'AI trả về phản hồi rỗng.');

        } catch (ConnectionException $e) {
            Log::error('NVIDIA NIM Connection Timeout', ['message' => $e->getMessage()]);
            return $this->getFallbackResponse($userMessage, $productContext, 'Server AI đang bận hoặc quá hạn kết nối.');
        } catch (\Throwable $e) {
            Log::error('NVIDIA NIM Chat Exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'reply' => 'Có lỗi hệ thống xảy ra. Vui lòng thử lại sau.'];
        }
    }

    private function validateConfiguration(): ?string
    {
        if ($this->apiKey === '') {
            return 'Chưa cấu hình API key cho AI. Vui lòng liên hệ quản trị viên.';
        }

        if (str_starts_with($this->apiKey, '"') || str_ends_with($this->apiKey, '"')) {
            Log::warning('Malformed NVIDIA NIM API key detected (has quotes)');
            return 'Cấu hình API key không hợp lệ. Vui lòng liên hệ quản trị viên.';
        }

        if ($this->apiUrl === '') {
            return 'Chưa cấu hình endpoint URL cho AI. Vui lòng liên hệ quản trị viên.';
        }

        return null;
    }

    protected function buildMessages(string $systemPrompt, string $productContext, string $userMessage, array $history): array
    {
        // 1. Gộp System Prompt và Context thành MỘT message duy nhất để tránh lỗi 400
        $fullSystemContent = $systemPrompt;
        if (!empty($productContext)) {
            $fullSystemContent .= "\n\nDữ liệu sản phẩm hiện có:\n" . $productContext;
        }

        $messages = [
            ['role' => 'system', 'content' => $fullSystemContent]
        ];

        // 2. Trim History
        $trimmedHistory = count($history) > 4 ? array_slice($history, -4) : $history;

        // 3. Chuẩn hóa History (Đảm bảo luồng user/assistant xen kẽ, không có role liên tiếp)
        $lastRole = 'system';
        foreach ($trimmedHistory as $msg) {
            if (isset($msg['role'], $msg['content']) && trim($msg['content']) !== '') {
                if ($msg['role'] !== $lastRole) {
                    $messages[] = [
                        'role'    => $msg['role'],
                        'content' => $msg['content'],
                    ];
                    $lastRole = $msg['role'];
                }
            }
        }

        // 4. Đẩy User Message hiện tại vào
        if ($lastRole === 'user') {
            // Nếu message trước đó cũng là user (VD: lỗi UI gửi đúp), thì gộp nội dung lại thay vì ghi đè
            $messages[count($messages) - 1]['content'] .= "\n\n" . $userMessage;
        } else {
            $messages[] = [
                'role'    => 'user',
                'content' => $userMessage,
            ];
        }

        return $messages;
    }

    protected function isInDemoMode(): bool
    {
        // Dùng config() thay vì env() để không bị dính lỗi khi chạy artisan config:cache
        return filter_var(config('services.nvidia_nim.demo_mode', false), FILTER_VALIDATE_BOOL) === true;
    }

    protected function getDemoResponse(string $userMessage, string $productContext): array
    {
        // Giữ nguyên logic của bạn
        if (!empty($productContext)) {
            return [
                'success' => true,
                'reply' => "Tìm thấy thông tin sau:\n\n" . $productContext . "\n\nGhi chú: Đây là chế độ demo hoặc chưa cấu hình API key NVIDIA NIM.",
            ];
        }
        return ['success' => true, 'reply' => 'Tôi là AI Assistant. Hỏi tôi điều gì tùy bạn!'];
    }

    protected function getFallbackResponse(string $userMessage, string $productContext, string $errorNote): array
    {
        // Giữ nguyên logic của bạn
        if (!empty($productContext)) {
            return [
                'success' => true,
                'reply' => "Mình gặp chút vấn đề khi gọi AI ({$errorNote}), nhưng đây là thông tin liên quan từ kho của mình nhé:\n\n" . $productContext,
            ];
        }
        return [
            'success' => true,
            'reply' => "Mình đang gặp vấn đề kết nối tới AI ({$errorNote}). Bạn vui lòng thử lại sau 1-2 phút, hoặc hỏi cụ thể hơn về sản phẩm để mình tra kho trực tiếp giúp bạn.",
        ];
    }

    protected function buildProductContext(string $userMessage): string
    {
        // Giữ nguyên logic xử lý Intent và Context của bạn
        try {
            $analysis = $this->intentAnalyzer->analyze($userMessage);
            $primaryIntent = $analysis['primary_intent'];
            $extractedInfo = $this->intentAnalyzer->extractInfo($userMessage);

            $context = '';

            if ($primaryIntent['confidence'] > 0.6) {
                switch ($primaryIntent['type']) {
                    case 'stock_check':
                        $context = $this->productService->getProductsContext(5);
                        break;
                    case 'category_search':
                        if (!empty($primaryIntent['category'])) {
                            $context = $this->productService->getProductsByCategoryContext($primaryIntent['category'], 3);
                        }
                        break;
                    case 'product_search':
                        if (!empty($extractedInfo['product_name'])) {
                            $context = $this->productService->getProductsByNameContext($extractedInfo['product_name'], 3);
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

    protected function getSystemPrompt(): string
    {
        // Sử dụng Nowdoc (<<<'PROMPT') của PHP để code sạch hơn, không cần nối chuỗi (.)
        return <<<'PROMPT'
        Bạn là chuyên gia tư vấn mua sắm của TechGear, chuyên về linh kiện và cấu hình PC.

        [VAI TRÒ & THÁI ĐỘ]
        - Chuyên nghiệp, thân thiện, tư vấn tận tâm. Xưng hô là "mình/TechGear" và gọi khách là "bạn".
        - Luôn giữ thái độ hỗ trợ, trả lời ngắn gọn, súc tích, đi thẳng vào trọng tâm.

        [NGUYÊN TẮC CỐT LÕI - TUYỆT ĐỐI TUÂN THỦ]
        1. BÁM SÁT DỮ LIỆU KHO (RAG): Chỉ tư vấn dựa trên "Dữ liệu sản phẩm hiện có" được cung cấp. Tuyệt đối không tự bịa (hallucinate) tên sản phẩm, thông số hoặc giá cả không có trong kho.
        2. XỬ LÝ HẾT HÀNG: Nếu khách hỏi sản phẩm không có trong dữ liệu kho, hãy trả lời: "Hiện tại TechGear đang tạm hết mã này, bạn tham khảo sang các mẫu sau nhé..." (nếu có gợi ý) hoặc hỏi thêm nhu cầu.
        3. KHAI THÁC THÔNG TIN: Nếu khách yêu cầu build PC nhưng chưa rõ, BẮT BUỘC phải hỏi lại 2 yếu tố: Mức ngân sách tối đa và Mục đích sử dụng chính (Gaming, Đồ họa, Code, Văn phòng...).
        4. GIỚI HẠN CHỦ ĐỀ: Từ chối lịch sự và khéo léo lùi về chủ đề công nghệ nếu khách hỏi các vấn đề ngoài lề (chính trị, tôn giáo, đời sống...).

        [ĐỊNH DẠNG ĐẦU RA]
        - Sử dụng Markdown để trình bày.
        - In đậm **Tên sản phẩm** và **Giá tiền**.
        - Sử dụng gạch đầu dòng (-) khi liệt kê linh kiện hoặc ưu điểm.
        - Trình bày thoáng, cách dòng rõ ràng giữa các đoạn.

        QUAN TRỌNG: Giao tiếp 100% bằng tiếng Việt tự nhiên.
        PROMPT;
    }