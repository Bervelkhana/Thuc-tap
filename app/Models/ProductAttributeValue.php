<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'attribute_id',
        'value',
        'value_string',
        'value_number',
        'value_boolean',
        'value_date',
        'value_json',
    ];

    protected $casts = [
        'value_boolean' => 'boolean',
        'value_date' => 'datetime',
        'value_json' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function getValueAttribute()
    {
        return match ($this->attribute->type) {
            Attribute::TYPE_NUMBER => $this->value_number,
            Attribute::TYPE_BOOLEAN => $this->value_boolean,
            Attribute::TYPE_DATE => $this->value_date,
            Attribute::TYPE_JSON => $this->value_json,
            default => $this->value_string,
        };
    }

    public function setValueAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['value'] = null;
            $this->value_string = null;
            $this->value_number = null;
            $this->value_boolean = null;
            $this->value_date = null;
            $this->value_json = null;

            return;
        }

        $type = $this->attribute->type ?? Attribute::TYPE_STRING;

        switch ($type) {
            case Attribute::TYPE_NUMBER:
                $this->value_number = (int) $value;
                $this->attributes['value'] = (string) $value;
                break;
            case Attribute::TYPE_BOOLEAN:
                $this->value_boolean = (bool) $value;
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            case Attribute::TYPE_DATE:
                $this->value_date = $value;
                $this->attributes['value'] = (string) $value;
                break;
            case Attribute::TYPE_JSON:
                $this->value_json = $value;
                $this->attributes['value'] = $value !== null ? json_encode($value) : null;
                break;
            default:
                $this->value_string = (string) $value;
                $this->attributes['value'] = (string) $value;
        }
    }
}
