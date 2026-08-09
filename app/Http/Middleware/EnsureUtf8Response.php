<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUtf8Response
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Nếu response có Content-Type là JSON, thêm charset utf-8
        $contentType = $response->headers->get('Content-Type', '');
        
        if (strpos($contentType, 'application/json') !== false) {
            $response->header('Content-Type', 'application/json; charset=utf-8');
        }

        return $response;
    }
}
