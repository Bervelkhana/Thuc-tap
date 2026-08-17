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
        $slugLower = strtolower($slug);

        $selectedCategory = Category::query()
            ->get()
            ->first(function (Category $category) use ($slugLower) {
                $categorySlug = strtolower((string) ($category->slug ?? Str::slug($category->name)));
                $categoryName = strtolower((string) ($category->name ?? ''));

                if ($categorySlug === $slugLower) {
                    return true;
                }

                if ($categoryName === $slugLower) {
                    return true;
                }

                $aliases = [
                    'mainboard' => ['mainboard', 'main', 'bo-mach-chu'],
                    'cpu' => ['cpu', 'processor', 'bo-xu-ly'],
                    'ram' => ['ram', 'memory', 'bo-nho'],
                    'vga' => ['vga', 'gpu', 'card-do-hoa'],
                    'ssd' => ['ssd', 'o-cung', 'storage'],
                    'psu' => ['psu', 'nguon', 'power-supply'],
                    'case' => ['case', 'vo-case', 'thung-case'],
                    'cooler' => ['cooler', 'tan-nhiet', 'fan'],
                ];

                $normalizedName = Str::slug($categoryName);

                if (isset($aliases[$normalizedName]) && in_array($slugLower, $aliases[$normalizedName], true)) {
                    return true;
                }

                return false;
            });

        abort_unless($selectedCategory, 404);

        return view('welcome', [
            'selectedCategory' => $selectedCategory,
        ]);
    }
}
