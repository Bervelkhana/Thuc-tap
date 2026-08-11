<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

final class SmartProductFilter
{
    /**
     * Smart Pre-filtering: Xóa sạch các sản phẩm không phù hợp TRƯỚC khi gửi cho AI
     * Áp dụng Waterfall Budgeting strategy
     */
    public static function filterProductsByBudgetAndPurpose(
        array $rawProducts,
        int $budget,
        string $purpose,
        ?string $subPurpose,
        string $componentKey
    ): array {
        $filtered = $rawProducts;

        // ========== RULE 1: Ngân sách 10-20tr ==========
        if ($budget <= 15000000) {
            $filtered = self::filterFor10To20M($filtered, $componentKey, $purpose, $subPurpose);
        }
        // ========== RULE 2: Ngân sách 20-30tr ==========
        elseif ($budget <= 25000000) {
            $filtered = self::filterFor20To30M($filtered, $componentKey, $purpose, $subPurpose);
        }
        // ========== RULE 3: Ngân sách >30tr ==========
        else {
            $filtered = self::filterForAbove30M($filtered, $componentKey, $purpose, $subPurpose);
        }

        Log::info('SMART_FILTER_APPLIED', [
            'component' => $componentKey,
            'budget' => $budget,
            'purpose' => $purpose,
            'before_count' => count($rawProducts),
            'after_count' => count($filtered),
        ]);

        return $filtered;
    }

    /**
     * Filter for 10-20tr: Chỉ entry-level hardware, BAN những cái đắt
     */
    private static function filterFor10To20M(array $products, string $componentKey, string $purpose, ?string $subPurpose): array
    {
        $filtered = [];

        foreach ($products as $product) {
            $name = strtolower($product['name'] ?? '');
            $price = (int) ($product['price'] ?? 0);
            $shouldKeep = true;

            // ========== CPU: Chỉ i3/i5, Ryzen 3/5 ==========
            if ($componentKey === 'cpu') {
                $shouldKeep = preg_match('/(core i[35]|ryzen [35])/i', $name) === 1;
            }
            // ========== MAINBOARD: BAN Z/X, chỉ H/B/A ==========
            elseif ($componentKey === 'mainboard') {
                $banPatterns = ['/z790|z890|z690|x870|x670|x870e/i'];
                $isBanned = false;
                foreach ($banPatterns as $pattern) {
                    if (preg_match($pattern, $name)) {
                        $isBanned = true;
                        break;
                    }
                }
                $shouldKeep = !$isBanned;
            }
            // ========== PSU: BAN > 700W ==========
            elseif ($componentKey === 'psu') {
                if (preg_match('/(\d+)\s*w/i', $name, $matches)) {
                    $wattage = (int) $matches[1];
                    $shouldKeep = $wattage <= 700;
                }
            }
            // ========== VGA: Cho Gaming/Esports, BAN RTX 4080/4090 ==========
            elseif ($componentKey === 'vga') {
                if ($purpose === 'gaming' || ($purpose === 'lam_viec' && $subPurpose === 'dung_video_do_hoa')) {
                    $shouldKeep = !preg_match('/rtx 4090|rtx 4080/i', $name);
                } else {
                    $shouldKeep = false; // Office không cần VGA
                }
            }

            if ($shouldKeep) {
                $filtered[] = $product;
            }
        }

        return $filtered;
    }

    /**
     * Filter for 20-30tr: Mid-level hardware, xóa thấp tinh
     */
    private static function filterFor20To30M(array $products, string $componentKey, string $purpose, ?string $subPurpose): array
    {
        $filtered = [];

        foreach ($products as $product) {
            $name = strtolower($product['name'] ?? '');
            $shouldKeep = true;

            // ========== CPU: i3/i5/i7 hoặc Ryzen 3/5/7 ==========
            if ($componentKey === 'cpu') {
                $shouldKeep = preg_match('/(core i[357]|ryzen [357])/i', $name) === 1;
            }
            // ========== MAINBOARD: B/H tier, some Z allowed ==========
            elseif ($componentKey === 'mainboard') {
                // Allow B760, Z790 cho ngân sách cao này
                $shouldKeep = !preg_match('/a620|a320|h310/i', $name);
            }
            // ========== PSU: BAN > 850W ==========
            elseif ($componentKey === 'psu') {
                if (preg_match('/(\d+)\s*w/i', $name, $matches)) {
                    $wattage = (int) $matches[1];
                    $shouldKeep = $wattage <= 850;
                }
            }
            // ========== VGA: Cho Gaming/Video/Office(>20M) ==========
            elseif ($componentKey === 'vga') {
                if (in_array($purpose, ['gaming', 'lam_viec'])) {
                    // Ban thấp tinh
                    $shouldKeep = !preg_match('/gtx 1650|rtx 3050/i', $name);
                } else {
                    $shouldKeep = false;
                }
            }

            if ($shouldKeep) {
                $filtered[] = $product;
            }
        }

        return $filtered;
    }

    /**
     * Filter for >30tr: High-end only, BAN hết cái thấp
     */
    private static function filterForAbove30M(array $products, string $componentKey, string $purpose, ?string $subPurpose): array
    {
        $filtered = [];

        foreach ($products as $product) {
            $name = strtolower($product['name'] ?? '');
            $shouldKeep = true;

            // ========== CPU: BẮTBUỘC i7/i9 hoặc Ryzen 7/9 ==========
            if ($componentKey === 'cpu') {
                $shouldKeep = preg_match('/(core i[79]|ryzen [79])/i', $name) === 1;
            }
            // ========== MAINBOARD: BẮTBUỘC Z/X tier, BAN H/B/A ==========
            elseif ($componentKey === 'mainboard') {
                $shouldKeep = preg_match('/(z790|z890|x870|x670)/i', $name) === 1;
            }
            // ========== PSU: Cho phép all (850W+) ==========
            elseif ($componentKey === 'psu') {
                $shouldKeep = true; // No restrictions
            }
            // ========== VGA: BẮTBUỘC High tier, BAN low/mid ==========
            elseif ($componentKey === 'vga') {
                $shouldKeep = !preg_match('/gtx 1650|rtx 3050|rtx 3060|rtx 4060/i', $name);
            }

            if ($shouldKeep) {
                $filtered[] = $product;
            }
        }

        return $filtered;
    }

    /**
     * Auto-fix CPU-Mainboard compatibility
     * Nếu CPU thấp nhưng Mainboard cao (hoặc ngược lại), tự động query DB thay thế
     */
    public static function fixCpuMainboardCompatibility(
        ?string $cpuName,
        ?string $mainboardName,
        int $budget
    ): ?array {
        if (!$cpuName || !$mainboardName) {
            return null;
        }

        $cpuName = strtolower($cpuName);
        $mainboardName = strtolower($mainboardName);

        $cpuTier = self::detectTier($cpuName);
        $mbTier = self::detectTier($mainboardName);

        Log::info('CPU_MB_COMPATIBILITY_CHECK', [
            'cpu' => $cpuName,
            'cpu_tier' => $cpuTier,
            'mb' => $mainboardName,
            'mb_tier' => $mbTier,
        ]);

        // ========== Không match: CPU thấp nhưng Main cao ==========
        if ($cpuTier === 'entry' && in_array($mbTier, ['high', 'ultra'])) {
            Log::warning('CPU_MB_MISMATCH_DOWNGRADING_MAINBOARD', [
                'issue' => 'i3 with Z790',
                'fix' => 'Replace with B760/H610',
            ]);

            // Query DB: lấy B-tier mainboard thay vì Z
            $mainboard = Product::query()
                ->where('category_id', function ($q) {
                    $q->from('categories')->whereIn('name', ['Mainboard', 'Motherboard']);
                })
                ->where('name', 'like', '%B760%')
                ->where('stock_quantity', '>', 0)
                ->where('price', '<=', $budget * 0.15)
                ->orderBy('price', 'desc')
                ->first(['id', 'name', 'price']);

            if ($mainboard) {
                return [
                    'id' => (int) $mainboard->id,
                    'name' => (string) $mainboard->name,
                    'price' => (int) round((float) $mainboard->price),
                ];
            }
        }

        // ========== Không match: CPU cao nhưng Main thấp ==========
        if (in_array($cpuTier, ['high', 'ultra']) && $mbTier === 'entry') {
            Log::warning('CPU_MB_MISMATCH_UPGRADING_MAINBOARD', [
                'issue' => 'i7/i9 with H610',
                'fix' => 'Replace with Z790/X870',
            ]);

            $mainboard = Product::query()
                ->where('category_id', function ($q) {
                    $q->from('categories')->whereIn('name', ['Mainboard', 'Motherboard']);
                })
                ->whereRaw("(name LIKE '%Z790%' OR name LIKE '%X870%')")
                ->where('stock_quantity', '>', 0)
                ->where('price', '<=', $budget * 0.2)
                ->orderBy('price', 'asc')
                ->first(['id', 'name', 'price']);

            if ($mainboard) {
                return [
                    'id' => (int) $mainboard->id,
                    'name' => (string) $mainboard->name,
                    'price' => (int) round((float) $mainboard->price),
                ];
            }
        }

        return null;
    }

    /**
     * Detect tier dari product name
     */
    private static function detectTier(string $name): string
    {
        $name = strtolower($name);

        if (preg_match('/(i[79]|ryzen [79]|z790|z890|x870|rtx 4080|rtx 4090)/i', $name)) {
            return 'ultra';
        }
        if (preg_match('/(i[57]|ryzen [57]|z690|x670|rtx 3080|rtx 4070)/i', $name)) {
            return 'high';
        }
        if (preg_match('/(i5|ryzen 5|b760|b650|rtx 3060|rtx 4060)/i', $name)) {
            return 'mid';
        }

        return 'entry';
    }
}
