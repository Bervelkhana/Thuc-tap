<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * PHẦN 1: BỘ LỌC DATABASE HARD-CODED 100% THEO NGHIỆP VỤ
 * Không giao phó cho AI, xóa sạch dữ liệu TRƯỚC khi gửi
 */
final class HardwarePreFilter
{
    /**
     * Lọc danh sách linh kiện theo ngân sách + loại linh kiện
     * Áp dụng WHERE hoặc xóa từ mảng PHP
     */
    public static function filterForBudget(array $products, int $budget, string $componentKey): array
    {
        // ========== NGÂN SÁCH 10-20TR ==========
        if ($budget <= 15000000) {
            return self::filterFor10To20M($products, $componentKey);
        }
        // ========== NGÂN SÁCH 20-30TR ==========
        elseif ($budget <= 25000000) {
            return self::filterFor20To30M($products, $componentKey);
        }
        // ========== NGÂN SÁCH >30TR ==========
        else {
            return self::filterForAbove30M($products, $componentKey);
        }
    }

    /**
     * NGÂN SÁCH 10-20TR: Xóa sạch high-end components
     */
    private static function filterFor10To20M(array $products, string $componentKey): array
    {
        $filtered = [];

        foreach ($products as $product) {
            $name = strtolower($product['name'] ?? '');
            $price = (int) ($product['price'] ?? 0);
            $keep = true;

            // === SSD: CHỈ 500GB + 1TB, XÓA 2TB/4TB ===
            if ($componentKey === 'ssd') {
                $keep = preg_match('/^(500gb|1\s*tb|1000gb)$/i', $name) === 1;
                if (!$keep) {
                    Log::info('SSD_REJECTED_10_20M', ['name' => $name, 'reason' => 'Size > 1TB']);
                }
            }
            // === PSU: CHỈ ≤650W, XÓA ≥700W ===
            elseif ($componentKey === 'psu') {
                if (preg_match('/(\d+)\s*w/i', $name, $m)) {
                    $wattage = (int) $m[1];
                    $keep = $wattage <= 650;
                    if (!$keep) {
                        Log::info('PSU_REJECTED_10_20M', ['name' => $name, 'wattage' => $wattage, 'reason' => '>650W']);
                    }
                }
            }
            // === CPU: Chỉ i3/i5, Ryzen 3/5 ===
            elseif ($componentKey === 'cpu') {
                $keep = preg_match('/(core i[35]|ryzen [35])/i', $name) === 1;
                if (!$keep) {
                    Log::info('CPU_REJECTED_10_20M', ['name' => $name, 'reason' => 'Not i3/i5/R3/R5']);
                }
            }
            // === MAINBOARD: Chỉ H/B tier, XÓA Z/X ===
            elseif ($componentKey === 'mainboard') {
                $keep = !preg_match('/(z790|z890|z690|x870|x670)/i', $name);
                if (!$keep) {
                    Log::info('MAINBOARD_REJECTED_10_20M', ['name' => $name, 'reason' => 'Z/X tier']);
                }
            }

            if ($keep) {
                $filtered[] = $product;
            }
        }

        Log::info('SSD_PSU_FILTER_10_20M_APPLIED', [
            'component' => $componentKey,
            'before' => count($products),
            'after' => count($filtered),
        ]);

        return $filtered;
    }

    /**
     * NGÂN SÁCH 20-30TR: Cho phép mid-range
     */
    private static function filterFor20To30M(array $products, string $componentKey): array
    {
        $filtered = [];

        foreach ($products as $product) {
            $name = strtolower($product['name'] ?? '');
            $keep = true;

            // === SSD: CHỈ 1TB + 2TB, XÓA 500GB, 4TB+ ===
            if ($componentKey === 'ssd') {
                $keep = preg_match('/^(1\s*tb|1000gb|2\s*tb|2000gb)$/i', $name) === 1;
                if (!$keep) {
                    Log::info('SSD_REJECTED_20_30M', ['name' => $name]);
                }
            }
            // === PSU: CHỈ 650W-750W ===
            elseif ($componentKey === 'psu') {
                if (preg_match('/(\d+)\s*w/i', $name, $m)) {
                    $wattage = (int) $m[1];
                    $keep = ($wattage >= 650 && $wattage <= 750);
                    if (!$keep) {
                        Log::info('PSU_REJECTED_20_30M', ['wattage' => $wattage]);
                    }
                }
            }

            if ($keep) {
                $filtered[] = $product;
            }
        }

        return $filtered;
    }

    /**
     * NGÂN SÁCH >30TR: Cho phép high-end
     */
    private static function filterForAbove30M(array $products, string $componentKey): array
    {
        // >30tr: không cần filter gắt gao, cho phép all
        return $products;
    }

    /**
     * Query DB với WHERE clause hard-coded
     */
    public static function queryProductsByBudgetAndType(
        int $budget,
        string $componentKey,
        int $minPrice,
        int $maxPrice
    ): array {
        $query = Product::query()
            ->whereHas('category', function ($q) use ($componentKey) {
                $categoryNames = match ($componentKey) {
                    'cpu' => ['CPU', 'Processor'],
                    'mainboard' => ['Mainboard', 'Motherboard'],
                    'ram' => ['RAM', 'Memory'],
                    'vga' => ['VGA', 'Graphics Card', 'GPU'],
                    'ssd' => ['SSD', 'Storage'],
                    'psu' => ['PSU', 'Power Supply'],
                    'case' => ['Case', 'Chassis'],
                    default => [],
                };
                $q->whereIn('name', $categoryNames);
            })
            ->where('stock_quantity', '>', 0)
            ->where('price', '>=', $minPrice)
            ->where('price', '<=', $maxPrice);

        // === HARD-CODED WHERE CLAUSE THEO NGÂN SÁCH ===
        if ($budget <= 15000000) {
            // 10-20tr: XÓA high-end
            if ($componentKey === 'ssd') {
                $query->whereRaw("(name LIKE '%500GB%' OR name LIKE '%1TB%' OR name LIKE '%1000GB%')")
                    ->whereRaw("(name NOT LIKE '%2TB%' AND name NOT LIKE '%4TB%' AND name NOT LIKE '%8TB%')");
            }
            if ($componentKey === 'psu') {
                $query->whereRaw("(name NOT LIKE '%750W%' AND name NOT LIKE '%850W%' AND name NOT LIKE '%1000W%' AND name NOT LIKE '%1200W%')");
            }
        } elseif ($budget <= 25000000) {
            // 20-30tr
            if ($componentKey === 'ssd') {
                $query->whereRaw("(name LIKE '%1TB%' OR name LIKE '%2TB%' OR name LIKE '%1000GB%' OR name LIKE '%2000GB%')")
                    ->whereRaw("(name NOT LIKE '%500GB%' AND name NOT LIKE '%4TB%' AND name NOT LIKE '%8TB%')");
            }
            if ($componentKey === 'psu') {
                $query->whereRaw("(name LIKE '%650W%' OR name LIKE '%750W%')")
                    ->whereRaw("(name NOT LIKE '%850W%' AND name NOT LIKE '%1000W%')");
            }
        }

        $products = $query
            ->orderBy('price', 'desc')
            ->limit(40)
            ->get(['id', 'name', 'price'])
            ->map(fn($p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'price' => (int) round((float) $p->price),
            ])
            ->toArray();

        // === LỌC THÊM BẰNG PHP NẾU CẦN ===
        return self::filterForBudget($products, $budget, $componentKey);
    }
}
