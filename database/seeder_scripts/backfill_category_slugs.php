<?php

use App\Models\Category;
use Illuminate\Support\Str;

// Backfill existing categories with slugs that don't have them
$categories = Category::all();
$updated = 0;

foreach ($categories as $category) {
    if (empty($category->slug)) {
        $category->update(['slug' => Str::slug($category->name)]);
        $updated++;
        echo "Updated category: {$category->name} -> slug: {$category->slug}\n";
    }
}

echo "Done. Updated {$updated} category(s).\n";
