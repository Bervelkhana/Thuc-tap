<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class AutoAssignProductTier extends Command
{
    protected $signature = 'tier:auto-assign';

    protected $description = 'Auto-assign tier to products based on price and name keywords';

    public function handle()
    {
        $this->info('Starting auto-assign tier for NULL products...');
        $this->newLine();

        // Get all products with NULL tier
        $productsToUpdate = Product::whereNull('tier')->get();
        $total = $productsToUpdate->count();

        if ($total === 0) {
            $this->info('✓ All products already have tier assigned!');
            return Command::SUCCESS;
        }

        $this->line("Found $total products without tier");
        $this->newLine();

        $updated = 0;
        $progressBar = $this->output->createProgressBar($total);

        foreach ($productsToUpdate as $product) {
            $tier = $this->determineTier($product);
            
            $product->tier = $tier;
            $product->save();

            $updated++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("✓ Successfully assigned tier to $updated products");
        $this->newLine();

        // Show summary
        $this->showSummary();

        return Command::SUCCESS;
    }

    /**
     * Determine tier based on:
     * 1. Price thresholds
     * 2. Name keywords
     * 3. Category hints
     */
    private function determineTier(Product $product): string
    {
        $price = (int) $product->price;
        $name = strtolower($product->name);

        // ========== TIER KEYWORDS ==========
        $ultraKeywords = ['9990', '11990', '14500', '15000', 'core i9', 'ryzen 9', 'rtx 4090', 'rtx 4080', 'extreme'];
        $highKeywords = ['core i7', 'ryzen 7', 'rtx 4070', 'rtx 3080', 'z790', 'x870', 'x670', 'premium'];
        $midKeywords = ['core i5', 'ryzen 5', 'rtx 3060', 'rtx 4060', 'b760', 'b650', 'ddr5'];
        $entryKeywords = ['core i3', 'ryzen 3', 'gtx 1650', 'rtx 3050', 'h610', 'a620', 'ddr4'];

        // ========== KEYWORD MATCHING (Priority 1) ==========
        foreach ($ultraKeywords as $keyword) {
            if (strpos($name, strtolower($keyword)) !== false) {
                return 'ultra';
            }
        }

        foreach ($highKeywords as $keyword) {
            if (strpos($name, strtolower($keyword)) !== false) {
                return 'high';
            }
        }

        foreach ($midKeywords as $keyword) {
            if (strpos($name, strtolower($keyword)) !== false) {
                return 'mid';
            }
        }

        foreach ($entryKeywords as $keyword) {
            if (strpos($name, strtolower($keyword)) !== false) {
                return 'entry';
            }
        }

        // ========== PRICE-BASED FALLBACK (Priority 2) ==========
        // Phân tier dựa trên giá mặc định
        if ($price >= 10000000) {
            return 'ultra';
        } elseif ($price >= 6000000) {
            return 'high';
        } elseif ($price >= 2500000) {
            return 'mid';
        } else {
            return 'entry';
        }
    }

    /**
     * Show tier distribution after update
     */
    private function showSummary(): void
    {
        $this->line('Tier Distribution After Update:');
        $this->newLine();

        $stats = DB::table('products')
            ->select('tier', DB::raw('count(*) as cnt'))
            ->groupBy('tier')
            ->orderBy('tier')
            ->get();

        $total = 0;
        foreach ($stats as $row) {
            $tierLabel = $row->tier ?? 'NULL';
            $total += $row->cnt;
            $this->line("  $tierLabel: {$row->cnt}");
        }

        $this->newLine();
        $this->line("Total: $total products");
    }
}

