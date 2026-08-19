<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'price',
        'sale_price',
        'is_on_sale',
        'discount_percentage',
        'stock_quantity',
        'description',
        'thumbnail_url',
        'datasheet_pdf_url',
        'brand',
        'socket_type',
        'chipset',
        'platform',
        'tier',
        'tdp',
        'memory_type',
        'memory_speed',
        'gpu_length_mm',
        'max_gpu_length_mm',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_on_sale' => 'boolean',
        'tdp' => 'integer',
        'memory_speed' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // EAV: các dòng giá trị thuộc tính của sản phẩm
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * EAV: quan hệ nhiều-nhiều tới Attribute, kèm 'value' trong pivot.
     * Cho phép: $product->attributes  => mỗi Attribute có ->pivot->value
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute_values')
            ->withPivot('value_string', 'value_number', 'value_boolean', 'value_date', 'value_json')
            ->withTimestamps();
    }
}
