<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TimeoutException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class NvidiaNimBuildService
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function buildConfiguration(int $budget, string $purpose, ?string $subPurpose = null): array
    {
        try {
            // Read AI_TEST_MODE environment variable correctly (as boolean)
            $useTestMode = filter_var(env('AI_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN);
            
            Log::info('BUILD_CONFIGURATION_STARTED', [
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'use_test_mode' => $useTestMode,
            ]);

            $productContextData = $this->buildProductContext($budget, $purpose, $subPurpose);

            // ========== TEST MODE: HARDCODED MOCK DATA ==========
            // Use first product from each category for testing purposes
            
            if ($useTestMode === true) {
                Log::info('TEST_MODE_ENABLED: Using mock data instead of NVIDIA NIM API');
                
                // Get first product from each category to ensure IDs exist in DB
                $mockProductIds = [];
                foreach ($productContextData as $category => $products) {
                    if (!empty($products)) {
                        $mockProductIds["{$category}_id"] = (int) $products[0]['id'];
                    }
                }
                
                Log::info('MOCK_PRODUCT_IDS_SELECTED', [
                    'ids' => $mockProductIds,
                    'budget' => $budget,
                    'purpose' => $purpose,
                ]);
                
                // Simulate AI response with real product IDs from DB
                $mockAiResponse = json_encode(array_merge([
                    'ai_advice' => '[TEST MODE] Cấu hình được chọn từ sản phẩm đầu tiên của mỗi category (Budget: ' . $budget . ', Purpose: ' . $purpose . ')',
                ], $mockProductIds), JSON_UNESCAPED_UNICODE);
                
                Log::info('MOCK_AI_RESPONSE_GENERATED', [
                    'mock_json' => $mockAiResponse,
                ]);
                
                $aiResponseRaw = $mockAiResponse;
                $aiConfig = json_decode($aiResponseRaw, true);
                
                Log::info('TEST_MODE_PARSED_CONFIG', [
                    'config' => $aiConfig,
                ]);
                
                $finalConfiguration = $this->buildFinalConfigurationStructure($aiConfig, $productContextData);
                
                Log::info('TEST_MODE_FINAL_CONFIGURATION', [
                    'total_price' => $finalConfiguration['total_price'],
                    'items_count' => count($finalConfiguration['items']),
                    'test_mode_active' => true,
                ]);
                
                return [
                    'status' => 'success',
                    'budget' => $budget,
                    'purpose' => $purpose,
                    'sub_purpose' => $subPurpose,
                    'configuration' => $finalConfiguration,
                    'ai_payload' => $aiConfig,
                    'raw_response' => $aiResponseRaw,
                    'test_mode' => true,
                ];
            }
            
            // ========== NORMAL MODE: CALL NVIDIA NIM API ==========
            
            $apiKey = (string) config('services.nvidia_nim.api_key');
            if ($apiKey === '') {
                throw new RuntimeException('NVIDIA_NIM_API_KEY is missing.');
            }

            $apiUrl = (string) config('services.nvidia_nim.base_url', 'https://integrate.api.nvidia.com/v1');
            $model = 'meta/llama-3.1-8b-instruct';

            Log::debug('Product context built', [
                'total_categories' => count($productContextData),
                'budget' => $budget,
            ]);

            // Tính khoảng ngân sách để truyền vào prompt
            $budgetRange = $this->analyzeBudgetRange($budget);

            $systemPrompt = $this->buildSystemPrompt($purpose, $subPurpose, $budgetRange);
            $userPrompt = $this->buildUserPrompt($budget, $purpose, $subPurpose, $productContextData, $budgetRange);

            Log::debug('NVIDIA NIM build request prepared', [
                'api_key_preview' => $this->maskApiKey($apiKey),
                'api_url' => $apiUrl,
                'model' => $model,
                'system_prompt_length' => strlen($systemPrompt),
                'user_prompt_length' => strlen($userPrompt),
            ]);

            $response = Http::timeout(180)
                ->connectTimeout(60)
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->post($apiUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1024,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                $body = trim($response->body());
                Log::error('NVIDIA NIM API request failed', [
                    'status' => $response->status(),
                    'body' => $body,
                    'api_url' => $apiUrl,
                ]);

                throw new RuntimeException(sprintf(
                    'NVIDIA NIM API request failed (%d): %s',
                    $response->status(),
                    $body !== '' ? $body : 'Empty response body'
                ));
            }

            $responseBody = $response->body();

            Log::debug('Raw response body from NVIDIA NIM', [
                'length' => strlen($responseBody),
                'first_300_chars' => substr($responseBody, 0, 300),
            ]);

            if (!mb_check_encoding($responseBody, 'UTF-8')) {
                $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
            }

            $responseData = @json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);

            if ($responseData === null) {
                Log::info('Direct JSON parse failed, trying encoding conversion');
                $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'ISO-8859-1');
                $responseData = @json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);

                if ($responseData === null) {
                    Log::error('JSON parse still failed after encoding conversion', [
                        'error' => json_last_error_msg(),
                        'error_code' => json_last_error(),
                    ]);
                    throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
                }
            }

            if (!is_array($responseData)) {
                throw new RuntimeException('NVIDIA NIM API returned invalid JSON response');
            }

            $aiResponseRaw = (string) data_get($responseData, 'choices.0.message.content', '');
            if ($aiResponseRaw === '') {
                Log::error('NVIDIA NIM response missing content', ['response' => $responseData]);
                throw new RuntimeException('NVIDIA NIM API response missing content');
            }

            if (!mb_check_encoding($aiResponseRaw, 'UTF-8')) {
                $aiResponseRaw = iconv('ISO-8859-1', 'UTF-8//IGNORE', $aiResponseRaw);
            }

            Log::info('RAW_AI_STRING', ['data' => $aiResponseRaw]);

            $cleanedJson = $this->extractJsonStrict($aiResponseRaw);
            Log::info('CLEANED_JSON_EXTRACTED', ['data' => $cleanedJson]);

            $aiConfig = $this->decodeJsonStrictly($cleanedJson);
            Log::info('PARSED_AI_CONFIG', ['config' => $aiConfig]);

            $finalConfiguration = $this->buildFinalConfigurationStructure($aiConfig, $productContextData);

            Log::info('FINAL_CONFIGURATION_BEFORE_VALIDATION', [
                'total_price' => $finalConfiguration['total_price'],
                'items_count' => count($finalConfiguration['items']),
            ]);

            // ========== FORCE FILL: Đảm bảo tất cả linh kiện bắt buộc có ID ==========
            $finalConfiguration = $this->forceFillMissingComponents($finalConfiguration, $productContextData);

            // ========== CHỐT CHẶN CỨNG: Validation + Fallback ==========
            $budgetRange = $this->analyzeBudgetRange($budget);
            $hardMaxBudget = $budgetRange['max'] + $budgetRange['tolerance'];
            
            $finalConfiguration = $this->validateAndFixConfigurationPrice(
                $finalConfiguration,
                $hardMaxBudget,
                $budget,
                $purpose,
                $productContextData
            );

        // ========== CHỐT CHẶN: Validate Mandatory Components ==========
        $finalConfiguration = $this->validateAndFillMandatoryComponents(
            $finalConfiguration,
            $productContextData,
            $budget
        );

        Log::info('FINAL_CONFIGURATION_AFTER_ALL_VALIDATION', [
            'total_price' => $finalConfiguration['total_price'],
            'items_count' => count($finalConfiguration['items']),
            'is_valid_price' => $finalConfiguration['total_price'] <= $hardMaxBudget,
            'has_ssd' => !empty($finalConfiguration['ssd']['id']),
        ]);

        // ========== CHỐT CHẶN: Kiểm tra 1M Tolerance & Downgrade ==========
        $finalConfiguration = $this->enforceStrictBudgetTolerance(
            $finalConfiguration,
            $budget,
            $purpose,
            $subPurpose,
            $productContextData
        );

        // ========== FINAL GUARDRAIL: Kiểm tra + Bù đắp + Downgrade ==========
        $finalConfiguration = $this->finalizeConfigurationWithGuardrails(
            $finalConfiguration,
            $budget,
            $purpose,
            $subPurpose,
            $productContextData
        );

            Log::info('CONFIGURATION_READY_FOR_FRONTEND', [
                'total_price' => $finalConfiguration['total_price'],
                'has_all_essentials' => !empty($finalConfiguration['ram']['id']) && !empty($finalConfiguration['ssd']['id']),
            ]);

            return [
                'status' => 'success',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'configuration' => $finalConfiguration,
                'ai_payload' => $aiConfig,
                'raw_response' => $aiResponseRaw,
            ];
        } catch (TimeoutException $e) {
            Log::warning('NVIDIA NIM request timeout (llama-3.1-8b)', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
            ]);

            return [
                'status' => 'timeout',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'error' => 'Server AI đang bận xử lý. Vui lòng thử lại sau.',
            ];
        } catch (ConnectException $e) {
            Log::warning('NVIDIA NIM connection timeout/error (llama-3.1-8b)', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
            ]);

            return [
                'status' => 'timeout',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'error' => 'Server AI đang bận xử lý. Vui lòng thử lại sau.',
            ];
        } catch (RequestException $e) {
            Log::warning('NVIDIA NIM request error (llama-3.1-8b)', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
            ]);

            return [
                'status' => 'unavailable',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'error' => 'Không thể kết nối tới server AI. Vui lòng thử lại sau.',
            ];
        } catch (\Exception $e) {
            Log::error('AI Build Error: ' . $e->getMessage() . ' at line ' . $e->getLine());
            Log::error('NVIDIA NIM build failed (llama-3.1-8b)', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'error' => 'Có lỗi xảy ra trong quá trình xử lý. Vui lòng thử lại sau.',
            ];
        }
    }

    private function buildProductContext(int $budget, string $purpose = '', ?string $subPurpose = null): array
    {
        // Phân tích khoảng ngân sách
        $budgetRange = $this->analyzeBudgetRange($budget);
        $minPrice = $budgetRange['min'];
        $maxPrice = $budgetRange['max'];
        $tolerance = $budgetRange['tolerance'] ?? 1000000;
        $hardMaxPrice = $maxPrice + $tolerance;
        $maxAllowedPrice = $budgetRange['max_allowed'] ?? $hardMaxPrice;
        
        Log::info('BUDGET_RANGE_ANALYZED', [
            'total_budget' => $budget,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'hard_max_price' => $hardMaxPrice,
            'max_allowed_price' => $maxAllowedPrice,
            'tolerance' => $tolerance,
            'purpose' => $purpose,
            'sub_purpose' => $subPurpose,
        ]);

        $categoryKeys = ['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'];
        $context = [];

        foreach ($categoryKeys as $key) {
            $names = match ($key) {
                'cpu' => ['CPU', 'Processor'],
                'mainboard' => ['Mainboard', 'Motherboard', 'MAIN', 'Main', 'Bo mạch'],
                'ram' => ['RAM', 'Memory'],
                'vga' => ['VGA', 'Graphics Card', 'GPU', 'Card màn hình'],
                'ssd' => ['SSD', 'Storage'],
                'psu' => ['PSU', 'Power Supply', 'Nguồn', 'Nguon', 'Power'],
                'case' => ['Case', 'Chassis', 'Vỏ case', 'Vỏ Case'],
                default => [],
            };

            $categoryIds = Category::query()
                ->whereIn('name', $names)
                ->orWhere(function ($query) use ($names): void {
                    foreach ($names as $name) {
                        $query->orWhere('slug', 'like', '%' . str($name)->slug() . '%');
                    }
                })
                ->pluck('id');

            // ========== BASE QUERY: Query sản phẩm từ DB ==========
            $categoryMinPrice = max(0, $minPrice * 0.1);
            $categoryMaxPrice = $maxAllowedPrice * 0.5;

            if ($subPurpose === 'lam_viec_van_phong' && $key === 'vga') {
                $context[$key] = [];
                Log::info("OFFICE_VGA_HIDDEN", [
                    'sub_purpose' => $subPurpose,
                    'budget' => $budget,
                ]);
                continue;
            }

            $query = Product::query()
                ->whereIn('category_id', $categoryIds)
                ->where('stock_quantity', '>', 0)
                ->where('price', '>=', $categoryMinPrice)
                ->where('price', '<=', $categoryMaxPrice);

            // Hard filters theo ngân sách trước khi gửi cho AI
            if ($budget < 20000000) {
                if ($key === 'ssd') {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%500GB%')
                          ->orWhere('name', 'like', '%1TB%')
                          ->orWhere('name', 'like', '%1000GB%');
                    })->where(function ($q) {
                        $q->where('name', 'not like', '%2TB%')
                          ->where('name', 'not like', '%4TB%')
                          ->where('name', 'not like', '%8TB%');
                    });
                }
                if ($key === 'psu') {
                    $query->whereRaw("(name NOT LIKE '%700W%' AND name NOT LIKE '%750W%' AND name NOT LIKE '%850W%' AND name NOT LIKE '%1000W%' AND name NOT LIKE '%1200W%')");
                }
                if ($key === 'mainboard') {
                    $query->whereRaw("(name NOT LIKE '%Z690%' AND name NOT LIKE '%Z790%' AND name NOT LIKE '%X670%' AND name NOT LIKE '%X870%')");
                }
                if ($key === 'cpu') {
                    $query->whereRaw("(name LIKE '%i3%' OR name LIKE '%i5%' OR name LIKE '%Ryzen 3%' OR name LIKE '%Ryzen 5%')");
                }
            } elseif ($budget >= 20000000) {
                if ($key === 'psu') {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%600W%')
                          ->orWhere('name', 'like', '%650W%')
                          ->orWhere('name', 'like', '%700W%')
                          ->orWhere('name', 'like', '%750W%');
                    });
                }
            }

            $rawProducts = $query
                ->orderBy('price', 'desc')
                ->limit(50)
                ->get(['id', 'name', 'price', 'category_id'])
                ->map(function (Product $product) {
                    return [
                        'id' => (int) $product->id,
                        'name' => (string) $product->name,
                        'price' => (int) round((float) $product->price),
                    ];
                })
                ->toArray();

            if ($key === 'vga' && empty($rawProducts) && $budget < 20000000) {
                $rawProducts = Product::query()
                    ->where('stock_quantity', '>', 0)
                    ->where(function ($q) {
                        $q->where('name', 'like', '%GTX 1650%')
                          ->orWhere('name', 'like', '%RTX 3050%')
                          ->orWhere('name', 'like', '%RTX 4060%')
                          ->orWhere('name', 'like', '%RX 6600%')
                          ->orWhere('name', 'like', '%RX 7600%');
                    })
                    ->orderBy('price', 'asc')
                    ->limit(25)
                    ->get(['id', 'name', 'price', 'category_id'])
                    ->map(function (Product $product) {
                        return [
                            'id' => (int) $product->id,
                            'name' => (string) $product->name,
                            'price' => (int) round((float) $product->price),
                        ];
                    })
                    ->toArray();
            }

            // ========== SMART FILTER: Lọc sạch dữ liệu không phù hợp ==========
            $filteredProducts = SmartProductFilter::filterProductsByBudgetAndPurpose(
                $rawProducts,
                $budget,
                $purpose,
                $subPurpose,
                $key
            );

            if ($key === 'vga' && empty($filteredProducts)) {
                $filteredProducts = $rawProducts;
            }

            $context[$key] = !empty($filteredProducts) ? $filteredProducts : [];

            Log::info("PRODUCTS_FILTERED_FOR_{$key}", [
                'category' => $key,
                'purpose' => $purpose,
                'budget' => $budget,
                'raw_count' => count($rawProducts),
                'filtered_count' => count($filteredProducts),
                'price_range' => [
                    'min' => $categoryMinPrice,
                    'max' => $categoryMaxPrice,
                ],
            ]);
        }

        return $context;
    }

    /**
     * Lớp 1: Xác định giới hạn giá tối đa CỨNG theo ngân sách
     */
    private function getMaxAllowedPrice(int $budget): int
    {
        if ($budget <= 15000000) {
            return 21000000;  // 10-20M → Max 21M (20M + 1M tolerance)
        } elseif ($budget <= 25000000) {
            return 31000000;  // 20-30M → Max 31M (30M + 1M tolerance)
        } else {
            return 999000000; // Trên 30M → Không giới hạn (tùy ngân sách)
        }
    }

    private function analyzeBudgetRange(int $budget): array
    {
        // Quy tắc mới: Dung sai tối đa 1,000,000 VNĐ
        $maxAllowed = $this->getMaxAllowedPrice($budget);
        
        if ($budget <= 15000000) {
            return [
                'name' => '10-20 triệu',
                'min' => 10000000,
                'max' => 20000000,
                'tolerance' => 1000000,  // 1M tolerance
                'max_allowed' => $maxAllowed,
                'target' => 18000000,
            ];
        } elseif ($budget <= 25000000) {
            return [
                'name' => '20-30 triệu',
                'min' => 20000000,
                'max' => 30000000,
                'tolerance' => 1000000,  // 1M tolerance
                'max_allowed' => $maxAllowed,
                'target' => 27000000,
            ];
        } else {
            return [
                'name' => 'Trên 30 triệu',
                'min' => 30000000,
                'max' => 999000000,
                'tolerance' => 5000000,
                'max_allowed' => $maxAllowed,
                'target' => $budget * 0.85,
            ];
        }
    }

    private function buildSystemPrompt(string $purpose, ?string $subPurpose, array $budgetRange = []): string
    {
        $min = $budgetRange['min'] ?? 0;
        $max = $budgetRange['max'] ?? 0;
        $tolerance = $budgetRange['tolerance'] ?? 1000000;
        $hardMax = $max + $tolerance;
        $name = $budgetRange['name'] ?? 'không xác định';

        // Get rule for this purpose + subpurpose + budget
        $specs = PcBuildRuleConfig::getSpecsRule($purpose, $subPurpose, (int)$max);

        $purposeLabel = match ($purpose) {
            'lam_viec' => 'Làm việc chuyên nghiệp',
            'gaming' => 'Gaming',
            default => $purpose,
        };

        $subPurposeLabel = match ($subPurpose) {
            'lam_viec_van_phong' => 'Văn phòng cơ bản',
            'dung_video_do_hoa' => 'Dựng video / Đồ họa',
            'esports_co_ban' => 'Esports cơ bản',
            'aaa_do_hoa_nang' => 'Game AAA / Đồ họa nặng',
            default => '',
        };

        $ramSpec = "RAM {$specs['ram_min_gb']}GB - {$specs['ram_max_gb']}GB";
        $vgaSpec = $specs['vga_required'] ? "VGA ({$specs['vga_tier']} tier)" : "VGA tùy chọn";
        $cpuSpec = "CPU ({$specs['cpu_tier']} tier)";
        $ssdSpec = "SSD {$specs['ssd_min_gb']}GB - {$specs['ssd_max_gb']}GB";

        return <<<PROMPT
Bạn là chuyên gia tư vấn xây dựng cấu hình PC cho TechGear.

NGÂN SÁCH KHÁCH HÀNG:
- Mục đích: {$purposeLabel} {$subPurposeLabel}
- Phân khúc: {$name}
- Khoảng ngân sách: từ {$min} VNĐ đến {$max} VNĐ
- HARD LIMIT (tối đa cho phép): {$hardMax} VNĐ
- ⚠️ TUYỆT ĐỐI: Không được vượt quá {$hardMax} VNĐ

QUY TẮC CHUYÊN BIỆT CHO MỤC ĐÍCH & NGÂN SÁCH:

SPEC YÊU CẦU CHÍNH:
- {$ramSpec}
- {$vgaSpec} (Bắt buộc nếu dùng cho graphics/gaming)
- {$cpuSpec}
- {$ssdSpec}
- PSU: {$specs['psu_wattage']}
- Mainboard: ({$specs['mainboard_tier']} tier, phù hợp với CPU tier)

NGUYÊN TẮC TƯƠNG THÍCH PHẦN CỨNG:
1. CPU & MAINBOARD PHẢI PHỐI HỢP TIER:
   - CPU Low tier (i3, Ryzen 3): Mainboard H/B/A tier
   - CPU Mid tier (i5, Ryzen 5): Mainboard H/B/Mid tier
   - CPU High tier (i7, Ryzen 7, i9, Ryzen 9): Mainboard Z/X/High tier

2. VGA SIZING THEO MỤC ĐÍCH:
   - Office: Không cần VGA rời (iGPU)
   - Esports: ƯUTIÊN ĐẬP TIỀN VÀO VGA (45% ngân sách nếu có)
   - AAA Gaming: VGA Mid-High tier để chạy game smooth
   - Video/Graphic: VGA Mid-High tier cho render nhanh

3. PSU SIZING THEO PHẦN CỨNG:
   - Không VGA: 400-500W
   - VGA Low: 550-650W
   - VGA Mid: 650-850W
   - VGA High: 850-1200W

4. SSD CAPACITY THEO NGÂN SÁCH:
   - Cấu hình có dư tiền → ƯUTIÊN NÂNG CẤP SSD (500GB→1TB→2TB)
   - {$ssdSpec} là khoảng cho phép

HƯỚNG DẪN BẮTBUỘC - TUYỆT ĐỐI PHẢI TUÂN THỦ:

⚠️ CẤM TUYỆT ĐỐI BỎ TRỐNG RAM VÀ SSD TRONG BẤT KỲ TRƯỜNG HỢP NÀO!
Mọi máy tính đều PHẢI có RAM và SSD để hoạt động.

✓ RAM: BẮTBUỘC PHẢI CÓ trong mọi cấu hình (không được null)
✓ SSD: BẮTBUỘC PHẢI CÓ trong mọi cấu hình (không được null)
✓ MAINBOARD: BẮTBUỘC PHẢI CÓ trong mọi cấu hình (không được null)
✓ PSU: BẮTBUỘC PHẢI CÓ trong mọi cấu hình (không được null)
✓ VGA: 
  - Máy Văn phòng ngân sách < 20M: Tùy chọn (có thể dùng iGPU)
  - Máy Văn phòng ngân sách >= 20M: BẮTBUỘC chọn VGA Low/Mid tier (không null)
  - Máy Gaming/Video: BẮTBUỘC chọn theo tier yêu cầu

1. Trả về ĐÚNG MỘT mảng JSON duy nhất
2. Không có markdown, không có lời chào, không có giải thích
3. BẮTBUỘC bao gồm ĐẦY ĐỦ 7 TRƯỜNG: cpu_id, mainboard_id, ram_id, vga_id, ssd_id, psu_id, case_id, ai_advice
4. ⚠️ TUYỆT ĐỐI KHÔNG ĐƯỢC PHÉP TRẢ VỀ null CHO BẤT KỲ TRƯỜNG NÀO TRỪ vga_id (chỉ được null khi máy văn phòng ngân sách < 20M)
5. ⚠️ Nếu ngân sách >= 20,000,000 VNĐ → BẮTBUỘC chọn VGA (vga_id không null)
6. ⚠️ RAM & SSD TUYỆT ĐỐI KHÔNG ĐƯỢC NULL TRONG BẤT KỲ TRƯỜNG HỢP NÀO
7. ⚠️ MAINBOARD & PSU TUYỆT ĐỐI KHÔNG ĐƯỢC NULL TRONG BẤT KỲ TRƯỜNG HỢP NÀO
8. CHỈ ĐƯỢC PHÉP chọn từ danh sách sản phẩm JSON được cung cấp
9. ⚠️ TUYỆT ĐỐI: Tổng giá tiền của cấu hình PHẢI nằm trong {$min} VNĐ đến {$hardMax} VNĐ

JSON RETURN FORMAT:
{
  "cpu_id": 123,
  "mainboard_id": 456,
  "ram_id": 789,
  "vga_id": 101 hoặc null,
  "ssd_id": 102,
  "psu_id": 103,
  "case_id": 104,
  "ai_advice": "Lý do chọn cấu hình này"
}

TRẢVỀ JSON ĐÚNG CẤU TRÚC, KHÔNG CÓ CHỮ KHÁC!
PROMPT;
    }

    /**
     * FINAL MANDATORY GUARDRAIL: Cấp buộc RAM, SSD, VGA (nếu ngân sách >= 20M)
     * Chạy TRƯỚC khi return response
     */
    private function finalMandatoryComponentCheck(
        array $configuration,
        int $budget,
        string $purpose,
        ?string $subPurpose,
        array $productContext
    ): array {
        Log::info('FINAL_MANDATORY_COMPONENT_CHECK_START', [
            'total_price_before' => $configuration['total_price'],
            'budget' => $budget,
            'purpose' => $purpose,
            'sub_purpose' => $subPurpose,
        ]);

        $priceDelta = 0;

        // ========== 1️⃣ RAM: BẮTBUỘC PHẢI CÓ ==========
        $ramId = $configuration['ram']['id'] ?? null;
        if (empty($ramId) || is_null($ramId)) {
            Log::warning('RAM_MISSING_AUTO_FILLING', [
                'current_ram' => $configuration['ram'] ?? null,
            ]);

            $ramProducts = $productContext['ram'] ?? [];
            if (!empty($ramProducts)) {
                // Chọn RAM phù hợp: 16GB hoặc 32GB tùy ngân sách
                $selectedRam = null;
                foreach ($ramProducts as $ram) {
                    if (preg_match('/32\s*GB/i', $ram['name'])) {
                        if ($budget >= 20000000) {
                            $selectedRam = $ram;
                            break;
                        }
                    }
                }
                
                if (!$selectedRam) {
                    // Fallback: lấy RAM 16GB hoặc sản phẩm đầu tiên
                    foreach ($ramProducts as $ram) {
                        if (preg_match('/16\s*GB/i', $ram['name'])) {
                            $selectedRam = $ram;
                            break;
                        }
                    }
                }
                
                if (!$selectedRam && !empty($ramProducts)) {
                    $selectedRam = reset($ramProducts);
                }

                if ($selectedRam) {
                    $ramPrice = (int) $selectedRam['price'];
                    $configuration['ram'] = [
                        'id' => (int) $selectedRam['id'],
                        'name' => (string) $selectedRam['name'],
                        'price' => $ramPrice,
                        'category' => 'RAM',
                    ];
                    $priceDelta += $ramPrice;

                    Log::info('RAM_AUTO_FILLED', [
                        'ram_name' => $selectedRam['name'],
                        'ram_price' => $ramPrice,
                    ]);
                }
            }
        }

        // ========== 2️⃣ SSD: BẮTBUỘC PHẢI CÓ ==========
        $ssdId = $configuration['ssd']['id'] ?? null;
        if (empty($ssdId) || is_null($ssdId)) {
            Log::warning('SSD_MISSING_AUTO_FILLING', [
                'current_ssd' => $configuration['ssd'] ?? null,
            ]);

            $ssdProducts = $productContext['ssd'] ?? [];
            if (!empty($ssdProducts)) {
                // Chọn SSD phù hợp: 1TB cho ngân sách cao, 500GB cho thấp
                $selectedSsd = null;
                if ($budget >= 20000000) {
                    foreach ($ssdProducts as $ssd) {
                        if (preg_match('/1\s*TB|1000\s*GB/i', $ssd['name'])) {
                            $selectedSsd = $ssd;
                            break;
                        }
                    }
                }

                if (!$selectedSsd) {
                    // Fallback: 500GB hoặc sản phẩm đầu tiên
                    foreach ($ssdProducts as $ssd) {
                        if (preg_match('/500\s*GB/i', $ssd['name'])) {
                            $selectedSsd = $ssd;
                            break;
                        }
                    }
                }

                if (!$selectedSsd && !empty($ssdProducts)) {
                    $selectedSsd = reset($ssdProducts);
                }

                if ($selectedSsd) {
                    $ssdPrice = (int) $selectedSsd['price'];
                    $configuration['ssd'] = [
                        'id' => (int) $selectedSsd['id'],
                        'name' => (string) $selectedSsd['name'],
                        'price' => $ssdPrice,
                        'category' => 'SSD',
                    ];
                    $priceDelta += $ssdPrice;

                    Log::info('SSD_AUTO_FILLED', [
                        'ssd_name' => $selectedSsd['name'],
                        'ssd_price' => $ssdPrice,
                    ]);
                }
            }
        }

        // ========== 3️⃣ VGA: BẮTBUỘC cho Văn phòng ngân sách >= 20M ==========
        $vgaId = $configuration['vga']['id'] ?? null;
        $isOfficeHighBudget = ($purpose === 'lam_viec' && $subPurpose === 'lam_viec_van_phong' && $budget >= 20000000);

        if ($isOfficeHighBudget && (empty($vgaId) || is_null($vgaId) || $vgaId === 0)) {
            Log::warning('VGA_MISSING_FOR_OFFICE_HIGH_BUDGET_AUTO_FILLING', [
                'budget' => $budget,
                'current_vga' => $configuration['vga'] ?? null,
            ]);

            $vgaProducts = $productContext['vga'] ?? [];
            if (!empty($vgaProducts)) {
                // Chọn VGA Low/Mid tier (rẻ nhất)
                $selectedVga = reset($vgaProducts);  // Chọn sản phẩm rẻ nhất

                if ($selectedVga) {
                    $vgaPrice = (int) $selectedVga['price'];
                    $configuration['vga'] = [
                        'id' => (int) $selectedVga['id'],
                        'name' => (string) $selectedVga['name'],
                        'price' => $vgaPrice,
                        'category' => 'VGA',
                    ];
                    $priceDelta += $vgaPrice;

                    Log::info('VGA_AUTO_FILLED_FOR_HIGH_BUDGET_OFFICE', [
                        'vga_name' => $selectedVga['name'],
                        'vga_price' => $vgaPrice,
                    ]);
                }
            }
        }

        // ========== 4️⃣ Cập nhật total_price ==========
        $configuration['total_price'] += $priceDelta;

        Log::info('FINAL_MANDATORY_COMPONENT_CHECK_COMPLETE', [
            'total_price_before' => $configuration['total_price'] - $priceDelta,
            'total_price_after' => $configuration['total_price'],
            'price_delta' => $priceDelta,
            'has_ram' => !empty($configuration['ram']['id']),
            'has_ssd' => !empty($configuration['ssd']['id']),
            'has_vga' => !empty($configuration['vga']['id']) && $configuration['vga']['id'] !== 0,
        ]);

        return $configuration;
    }
    private function enforceStrictBudgetTolerance(
        array $configuration,
        int $budget,
        string $purpose,
        ?string $subPurpose,
        array $productContext
    ): array {
        $budgetRange = $this->analyzeBudgetRange($budget);
        $hardLimit = $budgetRange['max'] + $budgetRange['tolerance'];  // 1M tolerance
        $totalPrice = (int) $configuration['total_price'];

        Log::info('STRICT_BUDGET_CHECK_START', [
            'total_price' => $totalPrice,
            'hard_limit' => $hardLimit,
            'is_over' => $totalPrice > $hardLimit,
            'overage' => $totalPrice - $hardLimit,
        ]);

        // ========== Kiểm tra: Có vượt quá 1M không? ==========
        if ($totalPrice <= $hardLimit) {
            Log::info('BUDGET_WITHIN_TOLERANCE');
            
            // ========== Tối ưu hóa: Nâng cấp SSD nếu còn dư ==========
            $remainingBudget = $hardLimit - $totalPrice;
            if ($remainingBudget > 1000000 && !empty($productContext['ssd'])) {
                $configuration = $this->upgradeStorageIfBudgetRemains(
                    $configuration,
                    $productContext['ssd'],
                    $remainingBudget
                );
            }
            
            return $configuration;
        }

        // ========== ĐẬP: Vượt quá 1M, phải downgrade ==========
        Log::warning('BUDGET_EXCEEDED_DOWNGRADING', [
            'overage' => $totalPrice - $hardLimit,
        ]);

        // Priority downgrade: VGA → Mainboard → Case → RAM → PSU
        $downgradeAttempts = [
            ['component' => 'vga', 'productContext' => $productContext['vga'] ?? []],
            ['component' => 'mainboard', 'productContext' => $productContext['mainboard'] ?? []],
            ['component' => 'case', 'productContext' => $productContext['case'] ?? []],
            ['component' => 'ram', 'productContext' => $productContext['ram'] ?? []],
            ['component' => 'psu', 'productContext' => $productContext['psu'] ?? []],
        ];

        foreach ($downgradeAttempts as $attempt) {
            $component = $attempt['component'];
            $products = $attempt['productContext'];

            if (empty($products)) continue;

            $currentPrice = $configuration[$component]['price'] ?? 0;
            if ($currentPrice <= 0) continue;
            
            // Tìm sản phẩm rẻ hơn
            foreach ($products as $product) {
                if ((int) $product['price'] < $currentPrice) {
                    $oldPrice = $configuration[$component]['price'] ?? 0;
                    $newPrice = (int) $product['price'];
                    $savings = $oldPrice - $newPrice;

                    $configuration[$component] = [
                        'id' => (int) $product['id'],
                        'name' => (string) $product['name'],
                        'price' => $newPrice,
                        'category' => strtoupper($component),
                    ];

                    $configuration['total_price'] -= $savings;

                    Log::info('DOWNGRADE_COMPONENT', [
                        'component' => $component,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'savings' => $savings,
                        'new_total' => $configuration['total_price'],
                    ]);

                    // Kiểm tra lại có trong budget không
                    if ($configuration['total_price'] <= $hardLimit) {
                        Log::info('DOWNGRADE_SUCCESS_WITHIN_BUDGET');
                        return $configuration;
                    }
                }
            }
        }

        Log::error('DOWNGRADE_FAILED_STILL_OVER_BUDGET', [
            'final_total' => $configuration['total_price'],
            'hard_limit' => $hardLimit,
        ]);

        return $configuration;
    }

    /**
     * Nâng cấp SSD nếu còn dư ngân sách
     */
    private function upgradeStorageIfBudgetRemains(
        array $configuration,
        array $ssdProducts,
        int $remainingBudget
    ): array {
        $currentSsdPrice = (int) ($configuration['ssd']['price'] ?? 0);
        $targetPrice = $currentSsdPrice + $remainingBudget;

        // Tìm SSD đắt hơn nhưng không vượt targetPrice
        $bestUpgrade = null;
        $bestPrice = $currentSsdPrice;

        foreach ($ssdProducts as $product) {
            $productPrice = (int) $product['price'];
            if ($productPrice > $bestPrice && $productPrice <= $targetPrice) {
                $bestUpgrade = $product;
                $bestPrice = $productPrice;
            }
        }

        if ($bestUpgrade) {
            $oldPrice = $configuration['ssd']['price'];
            $newPrice = (int) $bestUpgrade['price'];
            $upcharge = $newPrice - $oldPrice;

            $configuration['ssd'] = [
                'id' => (int) $bestUpgrade['id'],
                'name' => (string) $bestUpgrade['name'],
                'price' => $newPrice,
                'category' => 'SSD',
            ];

            $configuration['total_price'] += $upcharge;

            Log::info('SSD_UPGRADED_BUDGET_OPTIMIZATION', [
                'old_ssd' => $oldPrice,
                'new_ssd' => $newPrice,
                'upcharge' => $upcharge,
                'new_total' => $configuration['total_price'],
            ]);
        }

        return $configuration;
    }

    /**
     * Detect CPU tier (dùng để match mainboard)
     */
    private function detectCpuTier(string $cpuName): string
    {
        if (preg_match('/(i9|ryzen\s*9|core\s*i9)/i', $cpuName)) {
            return 'extreme';
        }
        if (preg_match('/(i7|ryzen\s*7|core\s*i7)/i', $cpuName)) {
            return 'high';
        }
        if (preg_match('/(i5|ryzen\s*5|core\s*i5)/i', $cpuName)) {
            return 'mainstream';
        }
        if (preg_match('/(i3|ryzen\s*3|core\s*i3)/i', $cpuName)) {
            return 'budget';
        }
        return 'mainstream';
    }

    /**
     * Detect Mainboard tier
     */
    private function detectMainboardTier(string $mainboardName): string
    {
        if (preg_match('/(Z790|Z890|X870|X670|ROG APEX|MEG|TRX)/i', $mainboardName)) {
            return 'high';
        }
        if (preg_match('/(H770|H770F|H670|H550|MPG|TUF)/i', $mainboardName)) {
            return 'mainstream';
        }
        if (preg_match('/(B850|B650|A620|B550|B450)/i', $mainboardName)) {
            return 'budget';
        }
        return 'budget';
    }

    /**
     * Detect Storage capacity (GB)
     */
    private function detectStorageCapacity(string $ssdName): int
    {
        if (preg_match('/(\d+)\s*TB/i', $ssdName, $matches)) {
            return (int) $matches[1] * 1024;
        }
        if (preg_match('/(\d+)\s*GB/i', $ssdName, $matches)) {
            return (int) $matches[1];
        }
        return 500;
    }

    /**
     * Detect PSU wattage from product name
     * Returns wattage in watts (e.g., 500, 650, 850)
     */
    private function detectPsuWattage(string $psuName): int
    {
        if (preg_match('/(\d+)\s*W/i', $psuName, $matches)) {
            return (int) $matches[1];
        }
        return 500;  // Default
    }

    /**
     * Detect VGA tier to determine required PSU wattage
     * 'none' = No VGA (iGPU only)
     * 'entry' = GTX 1650, RTX 3050/4060
     * 'mid' = RTX 3060, RTX 4070
     * 'high' = RTX 4080, RTX 4090
     */
    private function detectVgaTier(?string $vgaName): string
    {
        if (empty($vgaName) || $vgaName === null) {
            return 'none';
        }

        $nameUpper = strtoupper($vgaName);
        
        // High-end: RTX 4090, 4080, RTX 39xx
        if (preg_match('/(RTX 4090|RTX 4080|RTX 39\d+|RTX 3090)/i', $vgaName)) {
            return 'high';
        }
        
        // Mid-range: RTX 4070, RTX 3080, RTX 3070, RTX 3060 Ti
        if (preg_match('/(RTX 4070|RTX 3080|RTX 3070|RTX 3060.*TI|RTX 40 TI)/i', $vgaName)) {
            return 'mid';
        }
        
        // Entry-level: RTX 3060, RTX 4060, RTX 3050, GTX 1650
        if (preg_match('/(RTX 3060|RTX 4060|RTX 3050|GTX 1650|RTX 4050)/i', $vgaName)) {
            return 'entry';
        }
        
        return 'entry';  // Default conservative
    }

    /**
     * Calculate recommended PSU wattage based on system config
     */
    private function getRecommendedPsuWattage(?string $vgaName): int
    {
        $vgaTier = $this->detectVgaTier($vgaName);
        
        return match ($vgaTier) {
            'none' => 500,      // No VGA: 400-500W
            'entry' => 650,     // Entry VGA: 550-650W
            'mid' => 750,       // Mid VGA: 650-850W
            'high' => 1000,     // High VGA: 850-1200W
            default => 500,
        };
    }

    /**
     * Validate mandatory components: CPU, Main, RAM, SSD, PSU, Case
     * Auto-fill RAM + SSD nếu thiếu (bắt buộc)
     * Handle VGA: nếu rỗng → Dùng iGPU thân thiện
     */
    private function validateAndFillMandatoryComponents(
        array $configuration,
        array $productContextData,
        int $budget
    ): array {
        $mandatoryCategories = ['cpu', 'mainboard', 'ram', 'ssd', 'psu', 'case'];
        
        Log::info('MANDATORY_COMPONENTS_CHECK_START', [
            'configuration_keys' => array_keys($configuration),
            'budget' => $budget,
        ]);

        foreach ($mandatoryCategories as $category) {
            $componentData = $configuration[$category] ?? [];
            $componentId = $componentData['id'] ?? null;

            // Nếu component bị thiếu hoặc null - AUTO-FILL
            if (empty($componentId)) {
                Log::warning("MISSING_COMPONENT: {$category}", [
                    'category' => $category,
                    'reason' => 'ID is null or missing',
                ]);

                // Auto-fill: Lấy sản phẩm rẻ nhất từ danh sách có sẵn
                $availableProducts = $productContextData[$category] ?? [];
                
                if (!empty($availableProducts)) {
                    // Lấy sản phẩm rẻ nhất (logic an toàn) - cái cuối của mảng (sorted DESC)
                    $selectedProduct = end($availableProducts);
                    
                    $configuration[$category] = [
                        'id' => (int) $selectedProduct['id'],
                        'name' => (string) $selectedProduct['name'],
                        'price' => (int) $selectedProduct['price'],
                        'category' => strtoupper($category),
                    ];

                    // Add to items array
                    if (!isset($configuration['items'])) {
                        $configuration['items'] = [];
                    }
                    $configuration['items'][] = $configuration[$category];
                    $configuration['total_price'] += (int) $selectedProduct['price'];

                    Log::info("AUTO_FILL_COMPONENT_{$category}", [
                        'component_id' => $selectedProduct['id'],
                        'component_name' => $selectedProduct['name'],
                        'component_price' => $selectedProduct['price'],
                        'new_total_price' => $configuration['total_price'],
                    ]);
                } else {
                    // Không có sản phẩm trong kho - Emergency fallback
                    Log::error("NO_PRODUCTS_AVAILABLE_FOR_{$category}", [
                        'category' => $category,
                        'available_categories' => array_keys($productContextData),
                    ]);
                    
                    // Tạo placeholder object (sẽ bị detect bởi guardrail)
                    $configuration[$category] = [
                        'id' => null,
                        'name' => "Không có {$category} phù hợp",
                        'price' => 0,
                        'category' => strtoupper($category),
                    ];
                }
            }
        }

        // ========== Xử lý VGA đặc biệt: Nếu rỗng → iGPU ==========
        $vgaData = $configuration['vga'] ?? [];
        $vgaId = $vgaData['id'] ?? null;

        // Nếu VGA rỗng (config văn phòng/học tập), gán iGPU thân thiện
        if (empty($vgaId)) {
            Log::info('VGA_EMPTY_ASSIGNING_IGPU', [
                'reason' => 'No discrete VGA - using integrated graphics',
            ]);

            $configuration['vga'] = [
                'id' => 0,  // Placeholder ID = 0 cho iGPU
                'name' => 'Đồ họa tích hợp theo CPU (Onboard)',
                'price' => 0,
                'category' => 'VGA',
            ];
        }

        // ========== Validation: RAM + SSD PHẢI CÓ ==========
        $ramId = $configuration['ram']['id'] ?? null;
        $ssdId = $configuration['ssd']['id'] ?? null;

        if (empty($ramId)) {
            Log::error('CRITICAL_RAM_MISSING', [
                'ram_config' => $configuration['ram'] ?? null,
            ]);
        }

        if (empty($ssdId)) {
            Log::error('CRITICAL_SSD_MISSING', [
                'ssd_config' => $configuration['ssd'] ?? null,
            ]);
        }

        // ========== Final validation ==========
        $requiredComponentsForValidation = ['cpu', 'mainboard', 'ram', 'ssd', 'psu', 'case'];
        $allComponentsPresent = true;

        foreach ($requiredComponentsForValidation as $cat) {
            $catId = $configuration[$cat]['id'] ?? null;
            if (empty($catId)) {
                $allComponentsPresent = false;
                Log::error("MANDATORY_COMPONENT_STILL_MISSING: {$cat}", [
                    'category' => $cat,
                    'config' => $configuration[$cat] ?? null,
                ]);
            }
        }

        // ========== Chuẩn hóa key JSON ==========
        // Đảm bảo tất cả category có key đầy đủ
        $categories = ['cpu', 'mainboard', 'ram', 'ssd', 'vga', 'psu', 'case'];
        foreach ($categories as $cat) {
            if (!isset($configuration[$cat])) {
                $configuration[$cat] = [
                    'id' => null,
                    'name' => "Không có {$cat}",
                    'price' => 0,
                    'category' => strtoupper($cat),
                ];
            }
            
            // Đảm bảo mỗi component có đầy đủ các field
            if (!isset($configuration[$cat]['id'])) {
                $configuration[$cat]['id'] = null;
            }
            if (!isset($configuration[$cat]['name'])) {
                $configuration[$cat]['name'] = "Không có {$cat}";
            }
            if (!isset($configuration[$cat]['price'])) {
                $configuration[$cat]['price'] = 0;
            }
            if (!isset($configuration[$cat]['category'])) {
                $configuration[$cat]['category'] = strtoupper($cat);
            }
        }

        Log::info('MANDATORY_COMPONENTS_CHECK_COMPLETE', [
            'all_required_present' => $allComponentsPresent,
            'total_price' => $configuration['total_price'],
            'has_ram' => !empty($configuration['ram']['id']),
            'has_ssd' => !empty($configuration['ssd']['id']),
            'vga_mode' => empty($configuration['vga']['id']) ? 'onboard' : 'discrete',
        ]);

        return $configuration;
    }

    /**
     * Force fill any missing mandatory component from context pool.
     * This is a safety net in case AI returns null for required fields.
     */
    private function forceFillMissingComponents(array $configuration, array $productContext): array
    {
        $mandatory = ['cpu', 'mainboard', 'ram', 'ssd', 'psu', 'case'];
        
        foreach ($mandatory as $cat) {
            $id = $configuration[$cat]['id'] ?? null;
            if (empty($id)) {
                $available = $productContext[$cat] ?? [];
                if (!empty($available)) {
                    // Chọn sản phẩm rẻ nhất để an toàn ngân sách
                    $fallback = end($available);
                    if ($fallback !== false) {
                        $configuration[$cat] = [
                            'id' => (int) $fallback['id'],
                            'name' => (string) $fallback['name'],
                            'price' => (int) $fallback['price'],
                            'category' => strtoupper($cat),
                        ];
                        Log::info("FORCE_FILL_MISSING_{$cat}", [
                            'component_id' => $fallback['id'],
                            'component_name' => $fallback['name'],
                            'component_price' => $fallback['price'],
                        ]);
                    }
                }
            }
        }

        // Recalculate total after force fill
        $configuration['items'] = [];
        $configuration['total_price'] = 0;
        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $cat) {
            if (!empty($configuration[$cat]['id'])) {
                $configuration['items'][] = $configuration[$cat];
                $configuration['total_price'] += (int) ($configuration[$cat]['price'] ?? 0);
            }
        }

        return $configuration;
    }

    private function getMaxPsuWattageForFiltering(?string $vgaName): int
    {
        $recommended = $this->getRecommendedPsuWattage($vgaName);
        return $recommended + 150;  // Add 150W margin
    }

    /**
     * GUARDRAIL FUNCTION: Kiểm tra + Bù đắp + Downgrade trước khi trả về
     * Xử lý: RAM, SSD, VGA văn phòng, PSU quá cao cấp
     */
    private function finalizeConfigurationWithGuardrails(
        array $configuration,
        int $budget,
        string $purpose,
        ?string $subPurpose = null,
        array $productContext = []
    ): array {
        Log::info('FINAL_GUARDRAIL_START', [
            'purpose' => $purpose,
            'sub_purpose' => $subPurpose,
            'current_total' => $configuration['total_price'] ?? 0,
        ]);

        // ========== 1️⃣ Check & Fill RAM (from Context Pool) ==========
        $ramData = $configuration['ram'] ?? [];
        $ramId = $ramData['id'] ?? null;
        if (empty($ramId) || empty($ramData['name'])) {
            $ramBudgetMin = $budget < 20000000 ? 16 : 32;
            $availableRams = $productContext['ram'] ?? [];
            $selectedRam = null;

            foreach ($availableRams as $ram) {
                $name = (string) ($ram['name'] ?? '');
                if ($ramBudgetMin >= 32) {
                    if (preg_match('/(32\s*GB|64\s*GB)/i', $name)) {
                        $selectedRam = $ram;
                        break;
                    }
                } else {
                    if (preg_match('/(16\s*GB|32\s*GB)/i', $name)) {
                        $selectedRam = $ram;
                        break;
                    }
                }
            }

            if ($selectedRam === null && !empty($availableRams)) {
                $selectedRam = reset($availableRams);
            }

            if ($selectedRam !== null) {
                $configuration['ram'] = [
                    'id' => (int) $selectedRam['id'],
                    'name' => (string) $selectedRam['name'],
                    'price' => (int) $selectedRam['price'],
                    'category' => 'RAM',
                ];
            }
        }

        // ========== 2️⃣ Check & Fill SSD (from Context Pool) ==========
        $ssdData = $configuration['ssd'] ?? [];
        $ssdId = $ssdData['id'] ?? null;
        if (empty($ssdId) || empty($ssdData['name'])) {
            $availableSsds = $productContext['ssd'] ?? [];
            $selectedSsd = null;

            foreach ($availableSsds as $ssd) {
                $name = (string) ($ssd['name'] ?? '');
                if ($budget < 20000000) {
                    if (preg_match('/(500\s*GB|1\s*TB|1000\s*GB)/i', $name)) {
                        $selectedSsd = $ssd;
                        break;
                    }
                } else {
                    if (preg_match('/(1\s*TB|2\s*TB|1000\s*GB|2000\s*GB)/i', $name)) {
                        $selectedSsd = $ssd;
                        break;
                    }
                }
            }

            if ($selectedSsd === null && !empty($availableSsds)) {
                $selectedSsd = reset($availableSsds);
            }

            if ($selectedSsd !== null) {
                $configuration['ssd'] = [
                    'id' => (int) $selectedSsd['id'],
                    'name' => (string) $selectedSsd['name'],
                    'price' => (int) $selectedSsd['price'],
                    'category' => 'SSD',
                ];
            }
        }

        // ========== 3️⃣ Check & Fill VGA (from Context Pool) ==========
        $vgaData = $configuration['vga'] ?? [];
        $vgaId = $vgaData['id'] ?? null;
        $needVga = in_array($purpose, ['dung_video_do_hoa', 'gaming', 'esports_co_ban', 'aaa_do_hoa_nang'], true)
            || ($subPurpose === 'lam_viec_van_phong' && $budget >= 20000000);

        $invalidVgaCategory = in_array(strtoupper((string) ($vgaData['category'] ?? '')), ['PSU', 'POWER SUPPLY'], true)
            || str_contains(strtoupper((string) ($vgaData['name'] ?? '')), 'PSU')
            || str_contains(strtoupper((string) ($vgaData['name'] ?? '')), 'POWER SUPPLY');

        if ($needVga && (empty($vgaId) || empty($vgaData['name']) || $invalidVgaCategory)) {
            $availableVgas = $productContext['vga'] ?? [];
            
            // Nếu đã có VGA rời trong cấu hình, không cần fill thêm
            if (!empty($vgaId) && !$invalidVgaCategory) {
                // VGA đã tốt, giữ nguyên
            } else {
                // Tìm VGA phù hợp với ngân sách còn lại
                $currentTotal = $configuration['total_price'] ?? 0;
                $budgetRange = $this->analyzeBudgetRange($budget);
                $hardMaxBudget = $budgetRange['max'] + $budgetRange['tolerance'];
                $remainingBudget = $hardMaxBudget - $currentTotal;
                
                $selectedVga = null;
                
                // Chỉ chọn VGA nếu còn đủ ngân sách (ít nhất 2M cho VGA)
                if ($remainingBudget >= 2000000 && !empty($availableVgas)) {
                    foreach ($availableVgas as $vga) {
                        $name = (string) ($vga['name'] ?? '');
                        $price = (int) ($vga['price'] ?? 0);
                        if ($price <= $remainingBudget) {
                            if (preg_match('/(GTX 1650|RTX 3050|RTX 4060|RX 6600|RX 7600|VGA)/i', $name)) {
                                $selectedVga = $vga;
                                break;
                            }
                        }
                    }
                    
                    if ($selectedVga === null) {
                        foreach ($availableVgas as $vga) {
                            if ((int) ($vga['price'] ?? 0) <= $remainingBudget) {
                                $selectedVga = $vga;
                                break;
                            }
                        }
                    }
                }
                
                if ($selectedVga !== null) {
                    $configuration['vga'] = [
                        'id' => (int) $selectedVga['id'],
                        'name' => (string) $selectedVga['name'],
                        'price' => (int) $selectedVga['price'],
                        'category' => 'VGA',
                    ];
                } elseif ($subPurpose === 'lam_viec_van_phong') {
                    $configuration['vga'] = [
                        'id' => 0,
                        'name' => 'Đồ họa tích hợp',
                        'price' => 0,
                        'category' => 'VGA',
                    ];
                }
            }
        } elseif ($subPurpose === 'lam_viec_van_phong' && $budget < 20000000 && empty($vgaId)) {
            $configuration['vga'] = [
                'id' => 0,
                'name' => 'Đồ họa tích hợp',
                'price' => 0,
                'category' => 'VGA',
            ];
        }

        // ========== 4️⃣ Cross-check CPU ↔ Mainboard ==========
        $cpuName = (string) ($configuration['cpu']['name'] ?? '');
        $mainboardName = (string) ($configuration['mainboard']['name'] ?? '');
        if ($cpuName !== '' && $mainboardName !== '') {
            $isLowCpu = preg_match('/(i3|ryzen\s*3)/i', $cpuName) === 1;
            $hasHighBoard = preg_match('/(z690|z790|x670|x870)/i', $mainboardName) === 1;
            if ($isLowCpu && $hasHighBoard) {
                $fallbackMainboards = $productContext['mainboard'] ?? [];
                $selectedMainboard = null;
                foreach ($fallbackMainboards as $mainboard) {
                    $name = (string) ($mainboard['name'] ?? '');
                    if (preg_match('/(B760|B660|H610|H510)/i', $name)) {
                        $selectedMainboard = $mainboard;
                        break;
                    }
                }
                if ($selectedMainboard === null && !empty($fallbackMainboards)) {
                    $selectedMainboard = reset($fallbackMainboards);
                }
                if ($selectedMainboard !== null) {
                    $configuration['mainboard'] = [
                        'id' => (int) $selectedMainboard['id'],
                        'name' => (string) $selectedMainboard['name'],
                        'price' => (int) $selectedMainboard['price'],
                        'category' => 'MAINBOARD',
                    ];
                }
            }
        }

        // ========== 5️⃣ Final category/type safety pass ==========
        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $cat) {
            $category = strtoupper((string) ($configuration[$cat]['category'] ?? ''));
            if ($category !== '' && $category !== strtoupper($cat)) {
                $fallback = $this->fallbackProductForCategory($cat, $productContext);
                if ($fallback !== null) {
                    $configuration[$cat] = $fallback;
                }
            }
        }

        // ========== 6️⃣ Recalculate totals ==========
        $configuration['items'] = [];
        $configuration['total_price'] = 0;
        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $cat) {
            if (!empty($configuration[$cat]['id'])) {
                $configuration['items'][] = $configuration[$cat];
            }
            $configuration['total_price'] += (int) ($configuration[$cat]['price'] ?? 0);
        }

        // ========== 7️⃣ Budget tolerance + emergency downgrade ==========
        $tolerance = 1000000;
        $maxAllowed = $budget + $tolerance;
        if ($configuration['total_price'] > $maxAllowed) {
            $downgradeOrder = ['mainboard', 'psu', 'case', 'ram'];
            foreach ($downgradeOrder as $cat) {
                $currentPrice = (int) ($configuration[$cat]['price'] ?? 0);
                if ($currentPrice <= 0) {
                    continue;
                }

                $candidate = null;
                foreach (($productContext[$cat] ?? []) as $product) {
                    if ((int) $product['price'] < $currentPrice) {
                        $candidate = $product;
                        break;
                    }
                }

                if ($candidate !== null) {
                    $configuration['total_price'] = max(0, $configuration['total_price'] - $currentPrice + (int) $candidate['price']);
                    $configuration[$cat] = [
                        'id' => (int) $candidate['id'],
                        'name' => (string) $candidate['name'],
                        'price' => (int) $candidate['price'],
                        'category' => strtoupper($cat),
                    ];
                    if ($configuration['total_price'] <= $maxAllowed) {
                        break;
                    }
                }
            }
        }

        return $configuration;
    }

    private function isCompatibleCpuMainboard(string $cpuName, string $mainboardName): bool
    {
        $cpuTier = $this->detectCpuTier($cpuName);
        $mbTier = $this->detectMainboardTier($mainboardName);
        
        if (in_array($cpuTier, ['budget', 'mainstream'])) {
            return in_array($mbTier, ['budget', 'mainstream']);
        }
        
        if (in_array($cpuTier, ['high', 'extreme'])) {
            return $mbTier === 'high';
        }
        
        return true;
    }

    private function buildUserPrompt(int $budget, string $purpose, ?string $subPurpose, array $productContext, array $budgetRange = []): string
    {
        $purposeLabel = match ($purpose) {
            'lam_viec' => 'Làm việc chuyên nghiệp',
            'gaming' => 'Gaming',
            default => $purpose,
        };

        $subPurposeLabel = match ($subPurpose) {
            'lam_viec_van_phong' => 'Văn phòng cơ bản',
            'dung_video_do_hoa' => 'Dựng video / Đồ họa',
            'esports_co_ban' => 'Esports cơ bản',
            'aaa_do_hoa_nang' => 'Game AAA / Đồ họa nặng',
            default => '',
        };

        $productJson = json_encode($productContext, JSON_UNESCAPED_UNICODE);
        $minBudget = $budgetRange['min'] ?? $budget * 0.8;
        $maxBudget = $budgetRange['max'] ?? $budget;
        $targetBudget = $budgetRange['target'] ?? ($minBudget + ($maxBudget - $minBudget) * 0.85);

        return <<<PROMPT
THÔNG TIN KHÁCH HÀNG:
- Mục đích: $purposeLabel $subPurposeLabel
- Ngân sách tối thiểu: $minBudget VND
- Ngân sách mục tiêu: $targetBudget VND
- Ngân sách tối đa (HARD LIMIT): $maxBudget VND

DANH SÁCH LINH KIỆN CÓ TRONG KHO:
$productJson

HƯỚNG DẪN CHỌN LINH KIỆN (QUAN TRỌNG):
1. PHẢI chọn các linh kiện có tổng giá NẰM TRONG từ $minBudget đến $maxBudget VND
2. TUYỆT ĐỐI KHÔNG được vượt quá $maxBudget VND
3. KHÔNG được chọn cấu hình quá thấp (dưới $minBudget VND)
4. Mục tiêu: Tận dụng ngân sách ($targetBudget VND) để chọn linh kiện tốt nhất
5. Nếu còn dư ngân sách → ƯUTIÊN NÂNG CẤP SSD (500GB→1TB, 1TB→2TB, vv)
6. CHỈ chọn từ danh sách sản phẩm được cung cấp ở trên, KHÔNG tự bịa
7. Đặc biệt chú ý quy tắc specs cho mục đích này (xem System Prompt)
8. Khi trả về JSON, BẠN BẮT BUỘC PHẢI ĐẢM BẢO id của linh kiện phải tương ứng chính xác với field đó. vga_id CHỈ ĐƯỢC PHÉP chứa id của danh mục Card màn hình (VGA). psu_id CHỈ ĐƯỢC PHÉP chứa id của danh mục Nguồn (PSU). Tuyệt đối cấm tráo đổi id giữa các danh mục.

TRẢVỀ JSON theo cấu trúc bắt buộc, KHÔNG CÓ CHỮ KHÁC!
PROMPT;
    }

    private function extractJsonStrict(string $text): string
    {
        $text = trim($text);

        $text = preg_replace('/^\s*```json\s*/i', '', $text);
        $text = preg_replace('/^\s*```\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/i', '', $text);
        $text = trim($text);

        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');

        if ($firstBrace === false || $lastBrace === false || $firstBrace > $lastBrace) {
            Log::error('EXTRACT_JSON_FAILED: No valid braces found', [
                'text_preview' => substr($text, 0, 200),
                'first_brace' => $firstBrace,
                'last_brace' => $lastBrace,
            ]);
            throw new RuntimeException('Cannot extract JSON: No valid braces found in response');
        }

        $extracted = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
        $extracted = trim($extracted);

        Log::info('EXTRACT_JSON_SUCCESS', [
            'extracted_preview' => substr($extracted, 0, 200),
            'extracted_length' => strlen($extracted),
        ]);

        return $extracted;
    }

    private function decodeJsonStrictly(string $jsonString): array
    {
        $decoded = json_decode($jsonString, true);

        if ($decoded === null) {
            $error = json_last_error();
            $errorMsg = json_last_error_msg();

            Log::error('DECODE_JSON_FAILED', [
                'json_error_code' => $error,
                'json_error_message' => $errorMsg,
                'input_length' => strlen($jsonString),
                'input_preview' => substr($jsonString, 0, 300),
            ]);

            throw new RuntimeException('JSON decode error: ' . $errorMsg);
        }

        if (!is_array($decoded)) {
            Log::error('DECODE_JSON_NOT_ARRAY', [
                'type' => gettype($decoded),
            ]);

            throw new RuntimeException('JSON must decode to an array');
        }

        return $decoded;
    }

    private function buildFinalConfigurationStructure(array $aiConfig, array $productContext): array
    {
        $categoryMap = [
            'cpu_id' => 'cpu',
            'mainboard_id' => 'mainboard',
            'ram_id' => 'ram',
            'vga_id' => 'vga',
            'ssd_id' => 'ssd',
            'psu_id' => 'psu',
            'case_id' => 'case',
        ];

        $result = [
            'cpu' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'CPU'],
            'mainboard' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'MAINBOARD'],
            'ram' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'RAM'],
            'vga' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'VGA'],
            'ssd' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'SSD'],
            'psu' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'PSU'],
            'case' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'CASE'],
            'total_price' => 0,
            'ai_advice' => (string) ($aiConfig['ai_advice'] ?? 'Cấu hình được đề xuất bởi AI'),
            'items' => [],
        ];

        foreach ($categoryMap as $configKey => $categoryKey) {
            $productId = $aiConfig[$configKey] ?? null;
            if ($productId === null || $productId === '') {
                Log::info("CATEGORY_SKIPPED: $categoryKey has no ID");
                continue;
            }

            $productId = (int) $productId;
            $products = $productContext[$categoryKey] ?? [];
            $matchedProduct = null;

            foreach ($products as $product) {
                if ((int) $product['id'] === $productId) {
                    $matchedProduct = $product;
                    break;
                }
            }

            if ($matchedProduct !== null) {
                $result[$categoryKey] = [
                    'id' => (int) $matchedProduct['id'],
                    'name' => (string) $matchedProduct['name'],
                    'price' => (int) $matchedProduct['price'],
                    'category' => strtoupper($categoryKey),
                ];
                $result['items'][] = $result[$categoryKey];
                $result['total_price'] += (int) $matchedProduct['price'];
            }
        }

        // Cross-validation: nếu AI map sai category thì fallback sang đúng category
        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $cat) {
            $current = $result[$cat] ?? ['id' => null, 'name' => '', 'price' => 0, 'category' => strtoupper($cat)];
            $currentCategory = strtoupper((string) ($current['category'] ?? ''));
            $expectedCategory = strtoupper($cat);

            if ($currentCategory !== '' && $currentCategory !== $expectedCategory) {
                Log::warning('CATEGORY_MISMATCH_DETECTED', [
                    'component' => $cat,
                    'current_category' => $currentCategory,
                    'expected_category' => $expectedCategory,
                    'current_name' => $current['name'] ?? null,
                ]);
                $result[$cat] = ['id' => null, 'name' => '', 'price' => 0, 'category' => $expectedCategory];
            }

            if (empty($result[$cat]['id'])) {
                $fallback = $this->fallbackProductForCategory($cat, $productContext);
                if ($fallback !== null) {
                    $result[$cat] = $fallback;
                }
            }
        }

        $result['items'] = [];
        $result['total_price'] = 0;
        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $cat) {
            if (!empty($result[$cat]['id'])) {
                $result['items'][] = $result[$cat];
                $result['total_price'] += (int) ($result[$cat]['price'] ?? 0);
            }
        }

        Log::info('POST_MAPPING_VALIDATION', [
            'total_price' => $result['total_price'],
            'budget_range' => $this->analyzeBudgetRange($result['total_price'] > 0 ? $result['total_price'] : 1),
        ]);

        return $result;
    }

    private function fallbackProductForCategory(string $category, array $productContext): ?array
    {
        $products = $productContext[$category] ?? [];
        if (empty($products)) {
            return null;
        }

        $selected = null;
        foreach ($products as $product) {
            $categoryName = strtoupper((string) ($product['category'] ?? $category));
            if ($categoryName === strtoupper($category)) {
                $selected = $product;
                break;
            }
        }

        if ($selected === null) {
            $selected = reset($products);
        }

        if ($selected === false || $selected === null) {
            return null;
        }

        return [
            'id' => (int) $selected['id'],
            'name' => (string) $selected['name'],
            'price' => (int) $selected['price'],
            'category' => strtoupper($category),
        ];
    }

    /**
     * Chốt chặn cứng (Guardrail): Kiểm tra và sửa giá tiền nếu vượt budget
     * Fallback: Tự động hạ cấp các linh kiện đắt tiền hoặc loại bỏ chúng
     */
    private function validateAndFixConfigurationPrice(
        array $configuration,
        int $hardMaxBudget,
        int $budget,
        string $purpose,
        array $productContextData
    ): array {
        $totalPrice = $configuration['total_price'] ?? 0;

        Log::info('GUARDRAIL_CHECK_START', [
            'current_total_price' => $totalPrice,
            'hard_max_budget' => $hardMaxBudget,
            'exceeded' => $totalPrice > $hardMaxBudget,
        ]);

        // Nếu tổng giá trong phạm vi cho phép, trả về như bình thường
        if ($totalPrice <= $hardMaxBudget) {
            Log::info('GUARDRAIL_CHECK_PASSED', [
                'total_price' => $totalPrice,
                'hard_max_budget' => $hardMaxBudget,
            ]);
            return $configuration;
        }

        // ========== CHỐT CHẶN: Tổng giá vượt quá! ==========
        Log::warning('GUARDRAIL_TRIGGERED: Configuration vượt budget', [
            'current_price' => $totalPrice,
            'max_allowed' => $hardMaxBudget,
            'exceeded_by' => $totalPrice - $hardMaxBudget,
        ]);

        // Fallback: Hạ cấp các linh kiện đắt tiền (VGA, RAM, CPU)
        $configuration = $this->downgradeExpensiveComponents(
            $configuration,
            $hardMaxBudget,
            $purpose,
            $productContextData
        );

        Log::info('GUARDRAIL_FIXED: Configuration sau downgrade', [
            'new_total_price' => $configuration['total_price'],
            'hard_max_budget' => $hardMaxBudget,
            'is_valid' => $configuration['total_price'] <= $hardMaxBudget,
        ]);

        return $configuration;
    }

    /**
     * Lớp 3: Fallback - Hạ cấp các linh kiện đắt tiền (Ép giá tự động bằng PHP)
     */
    private function downgradeExpensiveComponents(
        array $configuration,
        int $hardMaxBudget,
        string $purpose,
        array $productContextData
    ): array {
        $currentPrice = $configuration['total_price'];
        $excessAmount = $currentPrice - $hardMaxBudget;

        if ($excessAmount <= 0) {
            return $configuration;
        }

        Log::warning('LAYER3_GUARDRAIL_TRIGGERED', [
            'current_price' => $currentPrice,
            'max_allowed' => $hardMaxBudget,
            'excess_amount' => $excessAmount,
            'purpose' => $purpose,
        ]);

        // Thứ tự downgrade: VGA → RAM → CPU → Mainboard
        $degradeOrder = ['vga', 'ram', 'cpu', 'mainboard'];
        $maxAttempts = 20;
        $attempts = 0;

        while ($currentPrice > $hardMaxBudget && $attempts < $maxAttempts) {
            $attempts++;
            $downgraded = false;

            foreach ($degradeOrder as $category) {
                if ($currentPrice <= $hardMaxBudget) {
                    break 2;  // Exit both loops
                }

                if (empty($configuration[$category]) || $configuration[$category]['id'] === null) {
                    continue;
                }

                $currentComponentPrice = $configuration[$category]['price'] ?? 0;
                $products = $productContextData[$category] ?? [];

                // Tìm sản phẩm rẻ hơn (dùng phương pháp linear search từ đắt đến rẻ)
                $betterAlternatives = [];
                foreach ($products as $product) {
                    if ($product['price'] < $currentComponentPrice) {
                        $betterAlternatives[] = $product;
                    }
                }

                if (empty($betterAlternatives)) {
                    continue;
                }

                // Chọn sản phẩm rẻ nhất từ danh sách
                $cheapestProduct = reset($betterAlternatives);
                
                $savingsAmount = $currentComponentPrice - $cheapestProduct['price'];
                $configuration[$category] = [
                    'id' => (int) $cheapestProduct['id'],
                    'name' => (string) $cheapestProduct['name'],
                    'price' => (int) $cheapestProduct['price'],
                    'category' => strtoupper($category),
                ];

                // Update items array
                $found = false;
                foreach ($configuration['items'] as $key => $item) {
                    if (strtoupper($item['category']) === strtoupper($category)) {
                        $configuration['items'][$key] = $configuration[$category];
                        $found = true;
                        break;
                    }
                }

                $currentPrice -= $savingsAmount;
                $configuration['total_price'] = $currentPrice;
                $downgraded = true;

                Log::warning("LAYER3_DOWNGRADE_{$category}", [
                    'attempt' => $attempts,
                    'from_price' => $currentComponentPrice,
                    'to_price' => $cheapestProduct['price'],
                    'savings' => $savingsAmount,
                    'new_total' => $currentPrice,
                    'is_within_budget' => $currentPrice <= $hardMaxBudget,
                ]);

                // Nếu đã hạ được, tiếp tục vòng lặp từ đầu để check lại
                if ($downgraded && $currentPrice <= $hardMaxBudget) {
                    break 2;
                }
            }

            // Nếu không downgrade được cái nào trong vòng này, thoát
            if (!$downgraded) {
                break;
            }
        }

        // Cuối cùng: Nếu vẫn vượt, remove VGA hoàn toàn
        if ($currentPrice > $hardMaxBudget && !empty($configuration['vga']) && $configuration['vga']['id'] !== null) {
            $vgaPrice = $configuration['vga']['price'] ?? 0;
            $configuration['vga'] = [
                'id' => null,
                'name' => 'Đang cập nhật...',
                'price' => 0,
                'category' => 'VGA',
            ];

            $configuration['items'] = array_values(array_filter(
                $configuration['items'],
                fn($item) => strtoupper($item['category']) !== 'VGA'
            ));

            $currentPrice -= $vgaPrice;
            $configuration['total_price'] = $currentPrice;

            Log::warning('LAYER3_REMOVED_VGA_FINAL', [
                'removed_price' => $vgaPrice,
                'new_total' => $currentPrice,
                'is_within_budget' => $currentPrice <= $hardMaxBudget,
            ]);
        }

        // Nếu vẫn vượt sau loại bỏ VGA, cảnh báo nhưng vẫn trả về
        if ($currentPrice > $hardMaxBudget) {
            Log::error('LAYER3_STILL_EXCEEDED', [
                'current_price' => $currentPrice,
                'max_allowed' => $hardMaxBudget,
                'exceeded_by' => $currentPrice - $hardMaxBudget,
                'purpose' => $purpose,
            ]);
        }

        Log::warning('LAYER3_GUARDRAIL_COMPLETE', [
            'final_price' => $configuration['total_price'],
            'max_allowed' => $hardMaxBudget,
            'is_valid' => $configuration['total_price'] <= $hardMaxBudget,
            'attempts' => $attempts,
        ]);

        return $configuration;
    }

    private function maskApiKey(string $apiKey): string
    {
        $length = strlen($apiKey);
        if ($length <= 8) {
            return str_repeat('*', max($length, 4));
        }

        return substr($apiKey, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($apiKey, -4);
    }
}
