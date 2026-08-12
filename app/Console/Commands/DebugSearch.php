<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugSearch extends Command
{
    protected $signature = 'debug:search';
    protected $description = 'Debug categories and products for search feature';

    public function handle()
    {
        \Log::info('=== DEBUG START ===');

        echo "=== Categories ===\n";
        $categories = \App\Models\Category::all();
        foreach ($categories as $cat) {
            echo sprintf("  id=%d | slug='%s' | name='%s'\n", $cat->id, $cat->slug, $cat->name);
        }
        echo "\n";

        // Test mainboard lookup
        echo "=== Mainboard lookup ===\n";
        $mb_exact = \App\Models\Category::where('slug', 'mainboard')->first();
        echo "Exact slug='mainboard': " . ($mb_exact ? "FOUND (id={$mb_exact->id}, name={$mb_exact->name})" : "NOT FOUND") . "\n";

        $mb_name = \App\Models\Category::where('name', 'Mainboard')->first();
        echo "Exact name='Mainboard': " . ($mb_name ? "FOUND (id={$mb_name->id}, name={$mb_name->name})" : "NOT FOUND") . "\n";

        $mb_like = \App\Models\Category::where('slug', 'like', '%mainboard%')
                       ->orWhere('name', 'like', '%mainboard%')
                       ->first();
        echo "LIKE '%mainboard%': " . ($mb_like ? "FOUND (id={$mb_like->id}, name={$mb_like->name})" : "NOT FOUND") . "\n";

        // List all synonyms
        $synonyms = ['CPU', 'bộ vi xử lý', 'vi xử lý', 'processor',
                     'Mainboard', 'bo mạch chủ', 'motherboard',
                     'RAM', 'bộ nhớ trong', 'memory',
                     'VGA', 'card màn hình', 'card đồ họa', 'graphics card'];
        echo "\n=== Synonym check ===\n";
        foreach ($synonyms as $syn) {
            $found = \App\Models\Category::where('name', $syn)->first();
            echo "  '$syn': " . ($found ? "FOUND (id={$found->id})" : "NOT FOUND") . "\n";
        }

        // Products in mainboard category
        echo "\n=== Products in mainboard category ===\n";
        if ($mb_exact || $mb_name || $mb_like) {
            $cid = $mb_exact?->id ?? $mb_name?->id ?? $mb_like->id;
            $products = \App\Models\Product::where('category_id', $cid)->limit(3)->get(['id', 'name']);
            foreach ($products as $p) {
                echo "  id={$p->id} | name='{$p->name}'\n";
            }
            $totalCount = \App\Models\Product::where('category_id', $cid)->count();
            echo "  Total count: {$totalCount}\n";
        } else {
            echo "  Cannot determine category ID.\n";
        }

        // Products matching 'z' keyword anywhere
        echo "\n=== Products matching keyword 'z' (any category) ===\n";
        $zProducts = \App\Models\Product::where(function($q) {
            $q->where('name', 'like', '%z%')
              ->orWhere('sku', 'like', '%z%')
              ->orWhere('brand', 'like', '%z%');
        })->limit(5)->get(['id', 'name', 'category_id']);
        foreach ($zProducts as $p) {
            echo "  id={$p->id} | name='{$p->name}' | category_id={$p->category_id}\n";
        }
        $zCount = \App\Models\Product::where(function($q) {
            $q->where('name', 'like', '%z%')
              ->orWhere('sku', 'like', '%z%')
              ->orWhere('brand', 'like', '%z%');
        })->count();
        echo "  Total matches: {$zCount}\n";

        \Log::info('=== DEBUG END ===');

        return Command::SUCCESS;
    }
}
