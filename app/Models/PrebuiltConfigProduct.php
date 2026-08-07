<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrebuiltConfigProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'prebuilt_config_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function prebuiltConfig(): BelongsTo
    {
        return $this->belongsTo(PrebuiltConfig::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
