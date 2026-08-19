<?php

namespace Tests\Unit;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Services\ProductAttributeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAttributeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductAttributeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductAttributeService();
    }

    /** @test */
    public function can_set_and_get_string_attribute()
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create(['type' => Attribute::TYPE_STRING]);

        $this->service->setAttributeValue($product->id, $attribute->id, 'LGA1700');
        $value = $this->service->getAttributeValue($product->id, $attribute->id);

        $this->assertEquals('LGA1700', $value);
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'value_string' => 'LGA1700',
        ]);
    }

    /** @test */
    public function can_set_and_get_number_attribute()
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create(['type' => Attribute::TYPE_NUMBER]);

        $this->service->setAttributeValue($product->id, $attribute->id, 95);
        $value = $this->service->getAttributeValue($product->id, $attribute->id);

        $this->assertEquals(95, $value);
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'value_number' => 95,
        ]);
    }

    /** @test */
    public function can_set_and_get_boolean_attribute()
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create(['type' => Attribute::TYPE_BOOLEAN]);

        $this->service->setAttributeValue($product->id, $attribute->id, true);
        $value = $this->service->getAttributeValue($product->id, $attribute->id);

        $this->assertTrue($value);
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'value_boolean' => true,
        ]);
    }

    /** @test */
    public function can_set_and_get_date_attribute()
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create(['type' => Attribute::TYPE_DATE]);
        $date = '2024-01-15 00:00:00';

        $this->service->setAttributeValue($product->id, $attribute->id, $date);
        $value = $this->service->getAttributeValue($product->id, $attribute->id);

        $this->assertEquals($date, $value->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
        ]);
    }

    /** @test */
    public function can_set_and_get_json_attribute()
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create(['type' => Attribute::TYPE_JSON]);
        $jsonValue = ['cores' => 8, 'threads' => 16];

        $this->service->setAttributeValue($product->id, $attribute->id, $jsonValue);
        $value = $this->service->getAttributeValue($product->id, $attribute->id);

        $this->assertEquals($jsonValue, $value);
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
        ]);
    }

    /** @test */
    public function can_get_product_attributes_list()
    {
        $product = Product::factory()->create();
        $stringAttr = Attribute::factory()->create(['name' => 'Socket', 'type' => Attribute::TYPE_STRING]);
        $numberAttr = Attribute::factory()->create(['name' => 'TDP', 'type' => Attribute::TYPE_NUMBER]);

        $this->service->setAttributeValue($product->id, $stringAttr->id, 'AM5');
        $this->service->setAttributeValue($product->id, $numberAttr->id, 65);

        $attributes = $this->service->getProductAttributes($product->id);

        $this->assertCount(2, $attributes);
        $this->assertEquals('Socket', $attributes[0]['name']);
        $this->assertEquals('AM5', $attributes[0]['value']);
        $this->assertEquals('TDP', $attributes[1]['name']);
        $this->assertEquals(65, $attributes[1]['value']);
    }

    /** @test */
    public function can_update_existing_attribute_value()
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create(['type' => Attribute::TYPE_STRING]);

        $this->service->setAttributeValue($product->id, $attribute->id, 'LGA1700');
        $this->service->setAttributeValue($product->id, $attribute->id, 'AM5');

        $value = $this->service->getAttributeValue($product->id, $attribute->id);
        $this->assertEquals('AM5', $value);

        $this->assertDatabaseCount('product_attribute_values', 1);
    }
}
