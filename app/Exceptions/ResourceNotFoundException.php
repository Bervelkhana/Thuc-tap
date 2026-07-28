<?php

namespace App\Exceptions;

use Exception;

class ResourceNotFoundException extends Exception
{
    public $resource;
    public $identifier;

    public function __construct($resource, $identifier = null)
    {
        $this->resource = $resource;
        $this->identifier = $identifier;
        
        $message = "Không tìm thấy {$resource}";
        if ($identifier) {
            $message .= " có ID: {$identifier}";
        }
        
        parent::__construct($message . '.');
    }
}
