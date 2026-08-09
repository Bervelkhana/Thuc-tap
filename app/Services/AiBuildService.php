<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AiBuildService
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
            $apiKey = (string) config('services.gemini.api_key');
            if ($apiKey === '') {
                throw new RuntimeException('GEMINI_API_KEY is missing.');
            }

            $endpoint = $this->geminiEndpoint($apiKey);

            $response = Http::timeout(45)
                ->connectTimeout(15)
                ->acceptJson()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 1200,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->failed()) {
                $body = trim($response->body());
                throw new RuntimeException(sprintf(
                    'Gemini API request failed (%d): %s',
                    $response->status(),
                    $body !== '' ? $body : 'Empty response body'
                ));
            }

            $responseData = $response->json();
            if (!is_array($responseData)) {
                throw new RuntimeException('Gemini API returned invalid JSON response: ' . $response->body());
            }

            $rawText = (string) data_get($responseData, 'candidates.0.content.parts.0.text', '');
            if ($rawText === '') {
                throw new RuntimeException('Gemini API response missing candidates[0].content.parts[0].text. Raw: ' . $response->body());
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
            logger()->error('Gemini build failed', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'prompt' => $prompt,
                'exception' => $e,
            ]);

            throw new RuntimeException('Gemini build failed: ' . $e->getMessage(), previous: $e);
        }
    }

    private function buildFallbackResult(int $budget, string $purpose, ?string $subPurpose, string $prompt, string $errorMessage): array
    {
        $fallback = $this->buildFallbackConfiguration($budget, $purpose, $subPurpose);

        return [
            'status' => 'fallback',
            'budget' => $budget,
            'purpose' => $purpose,
            'sub_purpose' => $subPurpose,
            'prompt' => $prompt,
            'raw_response' => $errorMessage,
            'configuration' => $fallback['configuration'],
            'ai_payload' => $fallback['ai_payload'],
        ];
    }

    private function geminiEndpoint(string $apiKey): string
    {
        return 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey);
    }

    /**
     * @return array<string, array<int, Product>>
     */
    private function loadProductPool(): array
    {
        $categoryMap = [
            'cpu' => ['CPU'],
            'mainboard' => ['Mainboard', 'Motherboard'],
            'ram' => ['RAM', 'Memory'],
            'vga' => ['VGA', 'GPU', 'Graphics Card'],
            'ssd' => ['SSD', 'Storage'],
            'psu' => ['PSU', 'Power Supply'],
            'case' => ['Case', 'PC Case'],
        ];

        $pool = [];

        foreach ($categoryMap as $key => $names) {
            $categoryIds = Category::query()
                ->whereIn('name', $names)
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
        $json = trim($rawText);
        $json = preg_replace('/^```json\s*/i', '', $json) ?? $json;
        $json = preg_replace('/^```\s*/i', '', $json) ?? $json;
        $json = preg_replace('/\s*```$/', '', $json) ?? $json;
        $decoded = json_decode(trim($json), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON: ' . json_last_error_msg() . '. Raw: ' . $rawText);
        }

        return $decoded;
    }

    private function resolveConfigurationFromProducts(array $payload): array
    {
        $items = data_get($payload, 'items', []);
        $result = [];

        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $key) {
            $aiItem = data_get($items, $key);
            $resolved = $this->resolveSingleItem($key, is_array($aiItem) ? $aiItem : []);

            $result[] = [
                'category' => strtoupper($key),
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
            'summary' => (string) data_get($payload, 'summary', ''),
            'total_price' => (int) data_get($payload, 'total_price', 0),
            'notes' => array_values(array_filter((array) data_get($payload, 'notes', []), static fn ($note) => is_string($note) && $note !== '')),
            'items' => $result,
        ];
    }

    private function buildFallbackConfiguration(int $budget, string $purpose, ?string $subPurpose): array
    {
        $items = [];
        $total = 0;

        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $key) {
            $product = $this->pickFallbackProduct($this->productPool[$key] ?? [], $key);
            $price = $product ? (int) round((float) $product->price) : 0;
            $total += $price;

            $items[] = [
                'category' => strtoupper($key),
                'id' => $product?->id,
                'name' => $product?->name,
                'price' => $price,
                'reason' => $product ? 'Fallback theo DB vì AI hoặc kết nối AI bị lỗi.' : 'Không tìm thấy sản phẩm phù hợp.',
                'matched' => $product !== null,
                'match_source' => 'fallback',
                'matched_text' => '',
            ];
        }

        return [
            'configuration' => [
                'summary' => 'Đây là cấu hình dự phòng được tạo từ dữ liệu DB do AI trả lỗi hoặc phản hồi không hợp lệ.',
                'total_price' => $total,
                'notes' => [
                    'Hệ thống đang dùng cấu hình dự phòng để đảm bảo trang không bị lỗi.',
                    'Bạn nên kiểm tra lại API key hoặc kết nối Gemini sau.',
                ],
                'items' => $items,
            ],
            'ai_payload' => [
                'error' => 'fallback_used',
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'budget' => $budget,
            ],
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

        if ($best === null || $best['score'] < 0.42) {
            return null;
        }

        return $best;
    }

    /**
     * @param array<int, Product> $pool
     */
    private function pickFallbackProduct(array $pool, string $categoryKey): ?Product
    {
        if ($pool === []) {
            return null;
        }

        return match ($categoryKey) {
            'vga' => $this->pickCheapestWithKeyword($pool, ['rtx', 'gtx', 'rx', 'vga', 'graphics']) ?? $pool[0],
            'psu' => $this->pickCheapestWithKeyword($pool, ['80', 'power', 'psu']) ?? $pool[0],
            'case' => $this->pickCheapestWithKeyword($pool, ['case', 'mid', 'mini']) ?? $pool[0],
            default => $pool[0],
        };
    }

    /**
     * @param array<int, Product> $pool
     * @param array<int, string> $keywords
     */
    private function pickCheapestWithKeyword(array $pool, array $keywords): ?Product
    {
        $filtered = array_values(array_filter($pool, function (Product $product) use ($keywords): bool {
            $text = $this->normalizeText($product->name . ' ' . ($product->description ?? ''));
            foreach ($keywords as $keyword) {
                if (str_contains($text, $this->normalizeText($keyword))) {
                    return true;
                }
            }

            return false;
        }));

        if ($filtered === []) {
            return null;
        }

        usort($filtered, static fn (Product $a, Product $b): int => (int) round((float) $a->price) <=> (int) round((float) $b->price));

        return $filtered[0];
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function similarityScore(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }
}
