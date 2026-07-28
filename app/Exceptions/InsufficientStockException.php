<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public $product;
    public $available;
    public $requested;

    public function __construct($product, $available, $requested)
    {
        $this->product = $product;
        $this->available = $available;
        $this->requested = $requested;
        
        parent::__construct(
            "Sản phẩm \"{$product}\" chỉ còn {$available} trong kho, nhưng bạn yêu cầu {$requested}."
        );
    }
}
