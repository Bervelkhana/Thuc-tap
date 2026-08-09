<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a JSON response with proper UTF-8 encoding
     */
    protected function jsonResponse(
        array $data,
        int $status = 200,
        array $headers = []
    ) {
        $headers = array_merge([
            'Content-Type' => 'application/json; charset=utf-8',
        ], $headers);

        return response()->json($data, $status, $headers, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Return a success JSON response
     */
    protected function successResponse($data = null, string $message = 'Success', int $status = 200)
    {
        return $this->jsonResponse([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error JSON response
     */
    protected function errorResponse(string $error, int $status = 400, $data = null)
    {
        return $this->jsonResponse([
            'status' => 'error',
            'error' => $error,
            'data' => $data,
        ], $status);
    }
}
