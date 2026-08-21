<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\NvidiaNimBuildService;
use App\Services\PCCompatibilityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PcBuilderRecommendationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_recommendation_returns_server_validated_totals()
    {
        $cpuCategory = Category::factory()->create(['name' => 'CPU']);
        $mainboardCategory = Category::factory()->create(['name' => 'Mainboard']);
        $ramCategory = Category::factory()->create(['name' => 'RAM']);
        $vgaCategory = Category::factory()->create(['name' => 'VGA']);
        $ssdCategory = Category::factory()->create(['name' => 'SSD']);
        $psuCategory = Category::factory()->create(['name' => 'PSU']);
        $caseCategory = Category::factory()->create(['name' => 'Case']);

        $cpu = Product::factory()->create([
            'category_id' => $cpuCategory->id,
            'name' => 'Intel Core i5-13400',
            'price' => 3500000,
            'stock_quantity' => 10,
        ]);
        $mainboard = Product::factory()->create([
            'category_id' => $mainboardCategory->id,
            'name' => 'ASUS Prime B760M',
            'price' => 2500000,
            'stock_quantity' => 10,
        ]);
        $ram = Product::factory()->create([
            'category_id' => $ramCategory->id,
            'name' => 'Corsair Vengeance 16GB',
            'price' => 1200000,
            'stock_quantity' => 10,
        ]);
        $vga = Product::factory()->create([
            'category_id' => $vgaCategory->id,
            'name' => 'NVIDIA RTX 4060',
            'price' => 4500000,
            'stock_quantity' => 10,
        ]);
        $ssd = Product::factory()->create([
            'category_id' => $ssdCategory->id,
            'name' => 'Samsung 970 EVO 500GB',
            'price' => 1200000,
            'stock_quantity' => 10,
        ]);
        $psu = Product::factory()->create([
            'category_id' => $psuCategory->id,
            'name' => 'Cooler Master 650W',
            'price' => 1200000,
            'stock_quantity' => 10,
        ]);
        $case = Product::factory()->create([
            'category_id' => $caseCategory->id,
            'name' => 'NZXT H510',
            'price' => 1200000,
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson('/api/pc-builder/recommend', [
            'budget' => 16000000,
            'purpose' => 'gaming',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'configuration',
                'server_total',
                'client_total',
                'is_total_valid',
                'compatibility',
            ],
        ]);

        $data = $response->json('data');

        $this->assertIsInt($data['server_total']);
        $this->assertIsInt($data['client_total']);
        $this->assertEquals($data['server_total'], $data['client_total']);
        $this->assertTrue((bool) $data['is_total_valid']);
    }

    /** @test */
    public function test_recommendation_handles_missing_product_ids_gracefully()
    {
        $cpuCategory = Category::factory()->create(['name' => 'CPU']);
        $mainboardCategory = Category::factory()->create(['name' => 'Mainboard']);
        $ramCategory = Category::factory()->create(['name' => 'RAM']);

        $cpu = Product::factory()->create([
            'category_id' => $cpuCategory->id,
            'name' => 'Intel Core i5-13400',
            'price' => 3500000,
            'stock_quantity' => 10,
        ]);

        $buildService = Mockery::mock(NvidiaNimBuildService::class);
        $buildService->shouldReceive('buildConfiguration')
            ->andReturn([
                'status' => 'success',
                'configuration' => [
                    'items' => [
                        ['id' => $cpu->id, 'name' => $cpu->name, 'price' => 3500000],
                        ['id' => 999999, 'name' => 'Ghost Product', 'price' => 1000000],
                    ],
                    'total_price' => 4500000,
                ],
            ]);

        $compatibilityValidator = Mockery::mock(PCCompatibilityValidator::class);
        $compatibilityValidator->shouldReceive('validate')
            ->andReturn(['is_compatible' => true, 'errors' => [], 'warnings' => []]);

        $this->app->instance(NvidiaNimBuildService::class, $buildService);
        $this->app->instance(PCCompatibilityValidator::class, $compatibilityValidator);

        $response = $this->postJson('/api/pc-builder/recommend', [
            'budget' => 10000000,
            'purpose' => 'gaming',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonPath('data.missing_product_ids', [999999]);
    }

    /** @test */
    public function test_recommendation_enforces_compatibility_before_return()
    {
        $cpuCategory = Category::factory()->create(['name' => 'CPU']);
        $mainboardCategory = Category::factory()->create(['name' => 'Mainboard']);
        $cpu = Product::factory()->create([
            'category_id' => $cpuCategory->id,
            'name' => 'Intel Core i5-13400',
            'price' => 3500000,
            'stock_quantity' => 10,
            'socket_type' => 'LGA1700',
        ]);
        $mainboard = Product::factory()->create([
            'category_id' => $mainboardCategory->id,
            'name' => 'ASUS Prime B760M',
            'price' => 2500000,
            'stock_quantity' => 10,
            'socket_type' => 'AM5',
        ]);

        $buildService = Mockery::mock(NvidiaNimBuildService::class);
        $buildService->shouldReceive('buildConfiguration')
            ->andReturn([
                'status' => 'success',
                'configuration' => [
                    'items' => [
                        ['id' => $cpu->id, 'name' => $cpu->name, 'price' => 3500000],
                        ['id' => $mainboard->id, 'name' => $mainboard->name, 'price' => 2500000],
                    ],
                    'total_price' => 6000000,
                ],
            ]);

        $compatibilityValidator = Mockery::mock(PCCompatibilityValidator::class);
        $compatibilityValidator->shouldReceive('validate')
            ->andReturn([
                'is_compatible' => false,
                'errors' => ['CPU và Mainboard không tương thích'],
                'warnings' => [],
                'details' => [],
            ]);

        $this->app->instance(NvidiaNimBuildService::class, $buildService);
        $this->app->instance(PCCompatibilityValidator::class, $compatibilityValidator);

        $response = $this->postJson('/api/pc-builder/recommend', [
            'budget' => 10000000,
            'purpose' => 'gaming',
        ]);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertFalse($data['compatibility']['is_compatible']);
        $this->assertNotEmpty($data['compatibility']['errors']);
    }
}
