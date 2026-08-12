<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Product;

echo "=== Categories ===\n";
Category::all()->each(function($c) {
    echo $c->id . ' | slug=' . $c->slug . ' | name=' . $c->name . PHP_EOL;
});

echo "\n=== Products by Mainboard category ===\n";
$mb = Category::where('name', 'Mainboard')->orWhere('slug', 'like', '%mainboard%')->first();
if ($mb) {
    echo "Found mainboard category: id=" . $mb->id . ", name=" . $mb->name . PHP_EOL;
    $count = Product::where('category_id', $mb->id)->count();
    echo "Products count: " . $count . PHP_EOL;
} else {
    echo "No mainboard category found!" . PHP_EOL;
}
