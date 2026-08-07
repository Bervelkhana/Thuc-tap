<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

final class ProductBrowserController extends Controller
{
    public function showByCategory(string $slug): View
    {
        $selectedCategory = Category::query()
            ->get()
            ->first(function (Category $category) use ($slug) {
                return ($category->slug ?? Str::slug($category->name)) === $slug;
            });

        abort_unless($selectedCategory, 404);

        return view('welcome', [
            'selectedCategory' => $selectedCategory,
        ]);
    }
}
