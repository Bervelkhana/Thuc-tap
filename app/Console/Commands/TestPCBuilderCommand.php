<?php

namespace App\Console\Commands;

use App\Services\PCCompatibilityValidator;
use App\Models\Product;
use Illuminate\Console\Command;

class TestPCBuilderCommand extends Command
{
    protected $signature = 'test:pc-builder';
    protected $description = 'Test PC Builder compatibility validator';

    public function handle(PCCompatibilityValidator $validator)
    {
        $this->info("\n========== PC BUILDER COMPATIBILITY VALIDATOR TESTS ==========\n");

        // Load products
        $this->info("📌 Loading sample products...");

        $intelCpu = Product::where('name', 'Core i7-13700K')->first();
        $intelMb = Product::where('chipset', 'Z790')->first();
        $highGpu = Product::where('name', 'NVIDIA RTX 4090')->first();
        $ddr5Ram = Product::where('memory_type', 'DDR5')->first();
        $psuHigh = Product::where('tdp', '>=', 1000)->first();
        $budgetMb = Product::where('chipset', 'H610')->first();
        $amdMb = Product::where('chipset', 'X870')->first();
        $lowPsu = Product::where('tdp', '<=', 650)->first();
        $budgetCpu = Product::where('tier', 'entry')->where('category_id', 1)->first();

        $this->info("✅ Products loaded successfully\n");

        // Test 1: Valid high-end build
        $this->info("========== TEST 1: VALID HIGH-END BUILD ==========");
        $this->line("Components:");
        $this->line("  - CPU: {$intelCpu->name} (Tier: {$intelCpu->tier}, TDP: {$intelCpu->tdp}W)");
        $this->line("  - Mainboard: {$intelMb->name} (Chipset: {$intelMb->chipset})");
        $this->line("  - GPU: {$highGpu->name} (Tier: {$highGpu->tier}, TDP: {$highGpu->tdp}W)");
        $this->line("  - RAM: {$ddr5Ram->name} ({$ddr5Ram->memory_type})");
        $this->line("  - PSU: {$psuHigh->name} ({$psuHigh->tdp}W)\n");

        $result = $validator->validate([
            'cpu' => $intelCpu,
            'mainboard' => $intelMb,
            'ram' => $ddr5Ram,
            'vga' => $highGpu,
            'psu' => $psuHigh,
        ]);

        $this->printResult($result);

        // Test 2: Socket mismatch
        $this->info("\n========== TEST 2: SOCKET MISMATCH (INTEL CPU + AMD MAINBOARD) ==========");
        $this->line("Components:");
        $this->line("  - CPU: {$intelCpu->name} (Socket: {$intelCpu->socket_type})");
        $this->line("  - Mainboard: {$amdMb->name} (Socket: {$amdMb->socket_type})\n");

        $result = $validator->validate([
            'cpu' => $intelCpu,
            'mainboard' => $amdMb,
        ]);

        $this->printResult($result);

        // Test 3: Tier mismatch
        $this->info("\n========== TEST 3: TIER MISMATCH (HIGH-END CPU + BUDGET MAINBOARD) ==========");
        $this->line("Components:");
        $this->line("  - CPU: {$intelCpu->name} (Tier: {$intelCpu->tier})");
        $this->line("  - Mainboard: {$budgetMb->name} (Tier: {$budgetMb->tier})\n");

        $result = $validator->validate([
            'cpu' => $intelCpu,
            'mainboard' => $budgetMb,
        ]);

        $this->printResult($result);

        // Test 4: Insufficient PSU
        $this->info("\n========== TEST 4: INSUFFICIENT PSU ==========");
        $this->line("Components:");
        $this->line("  - CPU: {$intelCpu->name} (TDP: {$intelCpu->tdp}W)");
        $this->line("  - GPU: {$highGpu->name} (TDP: {$highGpu->tdp}W)");
        $this->line("  - PSU: {$lowPsu->name} ({$lowPsu->tdp}W) - SHOULD BE INSUFFICIENT\n");

        $result = $validator->validate([
            'cpu' => $intelCpu,
            'vga' => $highGpu,
            'psu' => $lowPsu,
        ]);

        $this->printResult($result);

        // Test 5: GPU bottleneck
        $this->info("\n========== TEST 5: GPU BOTTLENECK (HIGH-END GPU + BUDGET CPU) ==========");
        $this->line("Components:");
        $this->line("  - CPU: {$budgetCpu->name} (Tier: {$budgetCpu->tier})");
        $this->line("  - GPU: {$highGpu->name} (Tier: {$highGpu->tier})\n");

        $result = $validator->validate([
            'cpu' => $budgetCpu,
            'vga' => $highGpu,
        ]);

        $this->printResult($result);

        $this->info("\n========== ✅ ALL TESTS COMPLETED ==========\n");
    }

    private function printResult(array $result)
    {
        $this->line("📊 Result:");
        $compatible = $result['is_compatible'] ? '✅ YES' : '❌ NO';
        $this->line("  Is Compatible: {$compatible}");
        $this->line("  Errors: " . count($result['errors']));
        $this->line("  Warnings: " . count($result['warnings']));

        if ($result['errors']) {
            $this->line("\n  ❌ Errors:");
            foreach ($result['errors'] as $e) {
                $this->line("    - {$e['type']}: {$e['message']}");
            }
        }

        if ($result['warnings']) {
            $this->line("\n  ⚠️  Warnings:");
            foreach ($result['warnings'] as $w) {
                $this->line("    - {$w['type']}: {$w['message']}");
                if (isset($w['details']) && isset($w['details']['recommended_psu'])) {
                    $this->line("      Required PSU: " . $w['details']['recommended_psu'] . "W");
                    $this->line("      Selected PSU: " . $w['details']['selected_psu'] . "W");
                }
            }
        }
    }
}
