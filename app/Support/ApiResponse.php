<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse
{
    public static function success(
        Arrayable|JsonResource|array|null $data = null,
        string $message = 'Request completed successfully.',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data instanceof JsonResource ? $data->resolve() : ($data instanceof Arrayable ? $data->toArray() : $data),
            'errors' => null,
        ], $status);
    }

    public static function error(
        string $message = 'Request could not be completed.',
        ?array $errors = null,
        int $status = 422
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
