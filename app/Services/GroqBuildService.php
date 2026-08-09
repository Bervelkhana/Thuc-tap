<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class GroqBuildService
{
    /**
     * @var array<string, array<int, Product>>
     */
    private array $productPool = [];

    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function buildConfiguration(int $budget, string $purpose, ?string $subPurpose = null): array
    {
        $this->productPool = $this->loadProductPool();
        $prompt = $this->buildPrompt($budget, $purpose, $subPurpose, $this->productPool);

        try {
            $apiKey = (string) config('services.groq.api_key');
            if ($apiKey === '') {
                throw new RuntimeException('GROQ_API_KEY is missing.');
            }

            $apiUrl = (string) config('services.groq.api_url');
            $model = (string) config('services.groq.model');

            Log::debug('Groq build request prepared', [
                'api_key_preview' => $this->maskApiKey($apiKey),
                'api_url' => $apiUrl,
                'model' => $model,
                'prompt_length' => strlen($prompt),
            ]);

            $response = Http::timeout(45)
                ->connectTimeout(15)
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->post($apiUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 2048,
                ]);

            if ($response->failed()) {
                $body = trim($response->body());
                Log::error('Groq API request failed', [
                    'status' => $response->status(),
                    'body' => $body,
                    'api_url' => $apiUrl,
                ]);

                throw new RuntimeException(sprintf(
                    'Groq API request failed (%d): %s',
                    $response->status(),
                    $body !== '' ? $body : 'Empty response body'
                ));
            }

            // Lấy raw body
            $responseBody = $response->body();
            
            Log::debug('Raw response body from Groq', [
                'length' => strlen($responseBody),
                'first_200_bytes' => bin2hex(substr($responseBody, 0, 200)),
                'first_100_chars' => substr($responseBody, 0, 100),
            ]);
            
            // Cố gắng parse JSON trực tiếp trước
            $responseData = @json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);
            
            if ($responseData === null) {
                // Nếu thất bại, thử convert encoding
                Log::info('Direct JSON parse failed, trying encoding conversion');
                
                // Thử ISO-8859-1 -> UTF-8
                $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'ISO-8859-1');
                $responseData = @json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);
                
                if ($responseData === null) {
                    Log::error('JSON parse still failed after encoding conversion', [
                        'error' => json_last_error_msg(),
                        'error_code' => json_last_error(),
                        'body_hex' => bin2hex(substr($responseBody, 0, 200)),
                    ]);
                    throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
                }
            }
            Log::debug('Groq raw response received', [
                'response_data_keys' => is_array($responseData) ? array_keys($responseData) : null,
                'has_choices' => is_array($responseData) ? array_key_exists('choices', $responseData) : false,
            ]);

            if (!is_array($responseData)) {
                throw new RuntimeException('Groq API returned invalid JSON response: ' . $response->body());
            }

            $rawText = (string) data_get($responseData, 'choices.0.message.content', '');
            if ($rawText === '') {
                Log::error('Groq response missing content', [
                    'response' => $responseData,
                    'api_url' => $apiUrl,
                ]);
                throw new RuntimeException('Groq API response missing choices[0].message.content. Raw: ' . $response->body());
            }

            // Handle encoding issues: if response contains non-UTF8, convert from ISO-8859-1
            if (!mb_check_encoding($rawText, 'UTF-8')) {
                $rawText = iconv('ISO-8859-1', 'UTF-8//IGNORE', $rawText);
                Log::info('Converted response from ISO-8859-1 to UTF-8');
            }

            $decoded = $this->decodeAiPayload($rawText);

            return [
                'status' => 'success',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'prompt' => $prompt,
                'raw_response' => $rawText,
                'configuration' => $this->resolveConfigurationFromProducts($decoded),
                'ai_payload' => $decoded,
            ];
        } catch (\Throwable $e) {
            Log::error('Groq build failed', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'prompt' => $prompt,
                'exception_class' => get_class($e),
            ]);

            throw $e;
        }
    }

    private function maskApiKey(string $apiKey): string
    {
        $length = strlen($apiKey);

        if ($length <= 8) {
            return str_repeat('*', max($length, 4));
        }

        return substr($apiKey, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($apiKey, -4);
    }

    /**
     * @return array<string, array<int, Product>>
     */
    private function loadProductPool(): array
    {
        $pool = [];
        $categoryKeys = ['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'];

        foreach ($categoryKeys as $key) {
            $names = match ($key) {
                'cpu' => ['CPU', 'Processor'],
                'mainboard' => ['Mainboard', 'Motherboard'],
                'ram' => ['RAM', 'Memory'],
                'vga' => ['VGA', 'Graphics Card', 'GPU'],
                'ssd' => ['SSD', 'Storage'],
                'psu' => ['PSU', 'Power Supply'],
                'case' => ['Case', 'Chassis'],
                default => [],
            };

            $categoryIds = Category::query()
                ->where('name', $names)
                ->orWhere(function ($query) use ($names): void {
                    foreach ($names as $name) {
                        $query->orWhere('slug', 'like', '%' . str($name)->slug() . '%');
                    }
                })
                ->pluck('id');

            $products = Product::query()
                ->with('category')
                ->whereIn('category_id', $categoryIds)
                ->where('stock_quantity', '>', 0)
                ->orderBy('price')
                ->get()
                ->values()
                ->all();

            $pool[$key] = $products;
        }

        return $pool;
    }

    private function buildPrompt(int $budget, string $purpose, ?string $subPurpose, array $products): string
    {
        $purposeLabel = match ($purpose) {
            'hoc_tap' => 'Học tập',
            'lam_viec' => 'Làm việc',
            'gaming' => 'Gaming',
            default => $purpose,
        };

        $subPurposeLabel = match ($subPurpose) {
            'lam_viec_van_phong' => 'Làm việc văn phòng cơ bản',
            'dung_video_do_hoa' => 'Dựng video / Đồ họa nặng',
            'esports_co_ban' => 'Game eSports cơ bản',
            'aaa_do_hoa_nang' => 'Game AAA / Đồ họa nặng',
            default => 'Không áp dụng',
        };

        $context = '';
        foreach ($products as $categoryKey => $items) {
            $context .= strtoupper($categoryKey) . "\n";
            foreach ($items as $product) {
                $price = (int) round((float) $product->price);
                $context .= sprintf(
                    "- ID: %d | NAME: %s | PRICE: %d | STOCK: %d | DESC: %s\n",
                    $product->id,
                    $product->name,
                    $price,
                    (int) $product->stock_quantity,
                    $product->description ? trim(str_replace(["\r", "\n"], ' ', $product->description)) : 'N/A'
                );
            }
            $context .= "\n";
        }

        return <<<PROMPT
Bạn là chuyên gia tư vấn cấu hình PC của TechGear.

QUY TẮC BẮT BUỘC:
- CHỈ trả về đúng 1 chuỗi JSON thuần túy.
- KHÔNG bọc markdown, KHÔNG dùng ```json, KHÔNG giải thích thêm, KHÔNG thêm bất kỳ văn bản nào ngoài JSON.
- JSON phải parse được trực tiếp bằng json_decode.
- Không được thêm text trước hoặc sau JSON.
- Nếu không chắc chắn, vẫn phải trả về JSON hợp lệ theo schema.

Mục tiêu:
- Ngân sách tối đa: {$budget} VND
- Mục đích chính: {$purposeLabel}
- Mục đích chi tiết: {$subPurposeLabel}
- Chỉ được chọn từ danh sách sản phẩm DB bên dưới.
- Nếu một danh mục không cần thiết, vẫn trả về null cho danh mục đó.
- Ưu tiên sản phẩm còn hàng và cân đối ngân sách.
- Khi chọn sản phẩm, trả về đúng product_id từ DB.

Schema JSON bắt buộc:
{
  "summary": "string",
  "total_price": 0,
  "items": {
    "cpu": {"id": 0, "name": "", "price": 0, "reason": ""},
    "mainboard": {"id": 0, "name": "", "price": 0, "reason": ""},
    "ram": {"id": 0, "name": "", "price": 0, "reason": ""},
    "vga": {"id": 0, "name": "", "price": 0, "reason": ""},
    "ssd": {"id": 0, "name": "", "price": 0, "reason": ""},
    "psu": {"id": 0, "name": "", "price": 0, "reason": ""},
    "case": {"id": 0, "name": "", "price": 0, "reason": ""}
  },
  "notes": ["string"]
}

Dữ liệu sản phẩm DB hiện có:
{$context}
PROMPT;
    }

    private function decodeAiPayload(string $rawText): array
    {
        $json = $rawText;
        
        // XỬ LÝ ENCODING: Force convert ISO-8859-1 -> UTF-8 tại raw bytes level
        // Sử dụng iconv thay vì mb_convert vì iconv xử lý bytes level tốt hơn
        $json = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $json);
        if ($json === false) {
            $json = $rawText; // Fallback nếu iconv fail
        }
        
        $json = trim($json);
        
        Log::debug('Payload after encoding fix', [
            'length' => strlen($json),
            'first_80_chars' => substr($json, 0, 80),
        ]);
        
        // Làm sạch Markdown
        $json = preg_replace('/^\s*```json\s*/i', '', $json);
        $json = preg_replace('/^\s*```\s*/i', '', $json);
        $json = preg_replace('/\s*```\s*$/i', '', $json);
        $json = trim($json);
        
        // Kiểm tra trống
        if (empty($json) || strlen($json) < 2) {
            Log::error('JSON is empty after cleanup');
            throw new RuntimeException('JSON response is empty');
        }
        
        // Parse JSON
        $decoded = json_decode($json, true, 512, JSON_UNESCAPED_UNICODE);
        
        if ($decoded === null) {
            $error = json_last_error_msg();
            Log::error('JSON decode failed', [
                'error' => $error,
                'code' => json_last_error(),
                'json_preview' => substr($json, 0, 200),
            ]);
            throw new RuntimeException('JSON parse error: ' . $error);
        }
        
        if (!is_array($decoded)) {
            throw new RuntimeException('JSON is not an array');
        }

        return $decoded;
    }

    private function resolveConfigurationFromProducts(array $payload): array
    {
        $items = data_get($payload, 'items', []);
        $result = [];

        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $categoryKey) {
            $aiItem = is_array($items) ? ($items[$categoryKey] ?? []) : [];
            $resolved = $this->resolveSingleItem($categoryKey, $aiItem);

            $result[] = [
                'category' => strtoupper($categoryKey),
                'id' => $resolved['product']?->id,
                'name' => $resolved['product']?->name,
                'price' => $resolved['product'] ? (int) round((float) $resolved['product']->price) : 0,
                'reason' => $resolved['reason'],
                'matched' => $resolved['product'] !== null,
                'match_source' => $resolved['source'],
                'matched_text' => $resolved['matched_text'],
            ];
        }

        return [
            'summary' => (string) ($payload['summary'] ?? ''),
            'total_price' => (int) data_get($payload, 'total_price', 0),
            'notes' => array_values(array_filter((array) data_get($payload, 'notes', []), static fn ($note) => is_string($note) && $note !== '')),
            'items' => $result,
        ];
    }

    /**
     * @param array<string, mixed> $aiItem
     * @return array{product:?Product, reason:string, source:string, matched_text:string}
     */
    private function resolveSingleItem(string $categoryKey, array $aiItem): array
    {
        $pool = $this->productPool[$categoryKey] ?? [];
        $reason = (string) ($aiItem['reason'] ?? 'AI không cung cấp lý do.');
        $matchedText = trim((string) ($aiItem['name'] ?? ''));
        $aiId = isset($aiItem['id']) ? (int) $aiItem['id'] : null;

        if ($aiId !== null) {
            $byId = $this->findById($pool, $aiId);
            if ($byId !== null) {
                return [
                    'product' => $byId,
                    'reason' => $reason,
                    'source' => 'ai_id',
                    'matched_text' => $matchedText,
                ];
            }
        }

        if ($matchedText !== '') {
            $best = $this->findBestMatchByName($pool, $matchedText);
            if ($best !== null) {
                return [
                    'product' => $best['product'],
                    'reason' => $reason . ' | Fallback match từ tên AI: ' . $best['match_label'],
                    'source' => 'name_fallback',
                    'matched_text' => $matchedText,
                ];
            }
        }

        $fallback = $this->pickFallbackProduct($pool, $categoryKey);

        return [
            'product' => $fallback,
            'reason' => $reason . ($fallback ? ' | Fallback theo sản phẩm phù hợp nhất trong DB.' : ' | Không tìm thấy sản phẩm phù hợp trong DB.'),
            'source' => $fallback ? 'category_fallback' : 'none',
            'matched_text' => $matchedText,
        ];
    }

    /**
     * @param array<int, Product> $pool
     */
    private function findById(array $pool, int $id): ?Product
    {
        foreach ($pool as $product) {
            if ((int) $product->id === $id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @param array<int, Product> $pool
     * @return array{product:Product, match_label:string, score:float}|null
     */
    private function findBestMatchByName(array $pool, string $needle): ?array
    {
        $needleNormalized = $this->normalizeText($needle);
        $best = null;

        foreach ($pool as $product) {
            $score = $this->similarityScore($needleNormalized, $this->normalizeText($product->name));
            if ($best === null || $score > $best['score']) {
                $best = [
                    'product' => $product,
                    'match_label' => $product->name,
                    'score' => $score,
                ];
            }
        }

        return $best;
    }

    /**
     * @param array<int, Product> $pool
     */
    private function pickFallbackProduct(array $pool, string $categoryKey): ?Product
    {
        return $pool[0] ?? null;
    }

    private function normalizeText(string $text): string
    {
        return mb_strtolower(preg_replace('/[^a-z0-9\s]/i', '', $text) ?? '', 'UTF-8');
    }

    private function similarityScore(string $a, string $b): float
    {
        $a = str_split($a);
        $b = str_split($b);
        $matches = 0;

        foreach ($a as $char) {
            if (in_array($char, $b, true)) {
                $matches++;
            }
        }

        $maxLen = max(count($a), count($b));

        return $maxLen === 0 ? 0 : $matches / $maxLen;
    }
}
