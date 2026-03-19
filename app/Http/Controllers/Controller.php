<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;

abstract class Controller
{
    protected function successResponse($data = null, string $message = 'Request completed successfully.', int $status = 200)
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function errorResponse(string $message = 'Request could not be completed.', ?array $errors = null, int $status = 422)
    {
        return ApiResponse::error($message, $errors, $status);
    }
}
