<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Collection;

class PCBuilderService
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Lấy danh sách sản phẩm theo danh mục
     */
    public function getProductsByCategory(string $categoryName)
    {
        $category = Category::where('name', $categoryName)->firstOrFail();
        return $category->products()
            ->where('stock_quantity', '>', 0)
            ->select(['id', 'name', 'price', 'stock_quantity'])
            ->get();
    }

    /**
     * Kiểm tra compatibility giữa các component
     * Logic cơ bản: kiểm tra socket CPU, power consumption, etc.
     */
    public function checkCompatibility(array $selectedProducts): array
    {
        $issues = [];

        // Lấy thông tin các sản phẩm được chọn
        $cpu = $selectedProducts['cpu'] ?? null;
        $mainboard = $selectedProducts['mainboard'] ?? null;
        $ram = $selectedProducts['ram'] ?? null;
        $vga = $selectedProducts['vga'] ?? null;
        $psu = $selectedProducts['psu'] ?? null;

        // Kiểm tra CPU và Mainboard
        if ($cpu && $mainboard) {
            if (!$this->checkCPUMainboardCompatibility($cpu, $mainboard)) {
                $issues[] = [
                    'type' => 'warning',
                    'message' => 'CPU và Mainboard có thể không tương thích. Vui lòng kiểm tra socket.',
                ];
            }
        }

        // Kiểm tra Power Supply
        if ($psu) {
            $estimatedPower = $this->estimatePowerConsumption($selectedProducts);
            $psuPower = $this->extractPowerFromName($psu['name']);

            if ($psuPower > 0 && $estimatedPower > $psuPower * 0.85) {
                $issues[] = [
                    'type' => 'warning',
                    'message' => "PSU có thể không đủ. Dự kiến tiêu thụ: {$estimatedPower}W, PSU: {$psuPower}W",
                ];
            }
        }

        // Kiểm tra RAM và Mainboard
        if ($ram && $mainboard) {
            if (!$this->checkRAMMainboardCompatibility($ram, $mainboard)) {
                $issues[] = [
                    'type' => 'info',
                    'message' => 'Kiểm tra loại RAM (DDR4/DDR5) phù hợp với mainboard.',
                ];
            }
        }

        return [
            'compatible' => count($issues) === 0,
            'issues' => $issues,
        ];
    }

    /**
     * Tính toán tổng giá của build
     */
    public function calculateTotalPrice(array $selectedProducts): float
    {
        $total = 0;
        foreach ($selectedProducts as $product) {
            if ($product) {
                $total += (float)$product['price'];
            }
        }
        return $total;
    }

    /**
     * Kiểm tra compatibility CPU-Mainboard
     */
    protected function checkCPUMainboardCompatibility(array $cpu, array $mainboard): bool
    {
        $cpuSocket = $this->extractSocket($cpu['name']);
        $mbSocket = $this->extractSocket($mainboard['name']);

        return $cpuSocket && $mbSocket && strpos($cpuSocket, $mbSocket) !== false;
    }

    /**
     * Kiểm tra compatibility RAM-Mainboard
     */
    protected function checkRAMMainboardCompatibility(array $ram, array $mainboard): bool
    {
        $hasDDR4 = stripos($ram['name'], 'DDR4') !== false;
        $hasDDR5 = stripos($ram['name'], 'DDR5') !== false;

        $mbDDR4 = stripos($mainboard['name'], 'DDR4') !== false;
        $mbDDR5 = stripos($mainboard['name'], 'DDR5') !== false;

        return ($hasDDR4 && $mbDDR4) || ($hasDDR5 && $mbDDR5);
    }

    /**
     * Estimate power consumption dựa trên component
     */
    protected function estimatePowerConsumption(array $selectedProducts): int
    {
        $power = 0;

        // CPU: ~50-200W
        if (isset($selectedProducts['cpu'])) {
            $power += $this->estimateCPUPower($selectedProducts['cpu']['name']);
        }

        // GPU: ~50-450W
        if (isset($selectedProducts['vga'])) {
            $power += $this->estimateGPUPower($selectedProducts['vga']['name']);
        }

        // RAM: ~3-5W per stick
        if (isset($selectedProducts['ram'])) {
            $power += 5;
        }

        // SSD: ~2-5W
        if (isset($selectedProducts['ssd'])) {
            $power += 5;
        }

        // Other: ~50W
        $power += 50;

        return $power;
    }

    /**
     * Extract CPU power estimate
     */
    protected function estimateCPUPower(string $cpuName): int
    {
        if (stripos($cpuName, 'i3') !== false || stripos($cpuName, 'Ryzen 3') !== false) {
            return 65;
        }
        if (stripos($cpuName, 'i5') !== false || stripos($cpuName, 'Ryzen 5') !== false) {
            return 125;
        }
        if (stripos($cpuName, 'i7') !== false || stripos($cpuName, 'Ryzen 7') !== false) {
            return 165;
        }
        if (stripos($cpuName, 'i9') !== false || stripos($cpuName, 'Ryzen 9') !== false) {
            return 200;
        }

        return 100;
    }

    /**
     * Extract GPU power estimate
     */
    protected function estimateGPUPower(string $gpuName): int
    {
        if (stripos($gpuName, '1030') !== false || stripos($gpuName, '6400') !== false) {
            return 25;
        }
        if (stripos($gpuName, '1650') !== false || stripos($gpuName, '6500') !== false) {
            return 75;
        }
        if (stripos($gpuName, '3060') !== false || stripos($gpuName, '4060') !== false || stripos($gpuName, '7600') !== false) {
            return 170;
        }
        if (stripos($gpuName, '4070') !== false || stripos($gpuName, '7700') !== false) {
            return 250;
        }
        if (stripos($gpuName, '4090') !== false || stripos($gpuName, '7900') !== false) {
            return 450;
        }

        return 150;
    }

    /**
     * Extract power wattage from PSU name
     */
    protected function extractPowerFromName(string $psName): int
    {
        if (preg_match('/(\d+)W/', $psName, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }

    /**
     * Extract socket from CPU/Mainboard name
     */
    protected function extractSocket(string $name): ?string
    {
        if (preg_match('/(LGA\d+|AM\d+|SOCKET\d+)/i', $name, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /**
     * Lấy thông tin build categories
     */
    public function getBuildCategories(): array
    {
        return [
            'cpu' => 'CPU',
            'mainboard' => 'Mainboard',
            'ram' => 'RAM',
            'vga' => 'VGA',
            'ssd' => 'SSD',
            'psu' => 'PSU',
        ];
    }
}
