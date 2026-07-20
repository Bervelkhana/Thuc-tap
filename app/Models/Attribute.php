<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Các danh mục sử dụng thuộc tính này
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_attribute');
    }

    // Các dòng giá trị EAV của thuộc tính này
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    // Các sản phẩm có thuộc tính này (kèm value trong pivot)
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attribute_values')
            ->withPivot('value')
            ->withTimestamps();
    }
}
