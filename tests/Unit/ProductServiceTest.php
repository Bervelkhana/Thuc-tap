<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = new ProductService();
    }

    /** @test */
    public function can_check_stock_availability()
    {
        // Arrange
        $product = Product::factory()->create(['stock_quantity' => 10]);

        // Act & Assert
        $this->assertTrue($this->productService->checkStock($product->id, 5));
        $this->assertTrue($this->productService->checkStock($product->id, 10));
        $this->assertFalse($this->productService->checkStock($product->id, 11));
    }

    /** @test */
    public function can_decrease_stock()
    {
        // Arrange
        $product = Product::factory()->create(['stock_quantity' => 20]);

        // Act
        $result = $this->productService->decreaseStock($product->id, 5);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(15, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function can_increase_stock()
    {
        // Arrange
        $product = Product::factory()->create(['stock_quantity' => 10]);

        // Act
        $result = $this->productService->increaseStock($product->id, 5);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(15, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function cannot_decrease_stock_below_available()
    {
        // Arrange
        $product = Product::factory()->create(['stock_quantity' => 5]);

        // Act
        $result = $this->productService->decreaseStock($product->id, 10);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals(5, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function can_get_product_with_stock()
    {
        // Arrange
        $product = Product::factory()->create(['stock_quantity' => 10]);
        Product::factory()->create(['stock_quantity' => 0]);

        // Act
        $result = $this->productService->getProductWithStock($product->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($product->id, $result->id);
    }

    /** @test */
    public function can_filter_products_by_category()
    {
        // Arrange
        $category = Category::factory()->create();
        Product::factory(5)->create(['category_id' => $category->id]);
        Product::factory(3)->create();

        // Act
        $products = $this->productService->filterByCategoryAndPrice($category->id, [], 50);

        // Assert
        $this->assertEquals(5, $products->count());
    }

    /** @test */
    public function can_filter_products_by_price_range()
    {
        // Arrange
        Product::factory()->create(['price' => 1000000]);
        Product::factory()->create(['price' => 5000000]);
        Product::factory()->create(['price' => 10000000]);

        // Act
        $products = $this->productService->filterByCategoryAndPrice(null, [2000000, 8000000], 50);

        // Assert
        $this->assertEquals(1, $products->count());
        $this->assertEquals(5000000, $products->first()->price);
    }
}
