<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'ok',
            'version' => request()->attributes->get('api_version', 'v1'),
        ], 'Health check completed successfully.');
    }
}
