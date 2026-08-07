<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

final class BuildController extends Controller
{
    public function index(): View
    {
        $targetCategories = [
            'CPU',
            'Mainboard',
            'VGA',
            'RAM',
            'SSD',
            'PSU',
            'Case',
        ];

        $products = Product::with('category')
            ->whereHas('category', static function ($query) use ($targetCategories): void {
                $query->whereIn('name', $targetCategories);
            })
            ->orderBy('name')
            ->get();

        $groupedProducts = [];

        foreach ($targetCategories as $categoryName) {
            $groupedProducts[$categoryName] = $products->filter(static function (Product $product) use ($categoryName): bool {
                return $product->category?->name === $categoryName;
            })->values();
        }

        return view('build', [
            'groupedProducts' => $groupedProducts,
            'targetCategories' => $targetCategories,
        ]);
    }
}
