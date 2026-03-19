<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdminHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'area' => 'admin',
            'version' => request()->attributes->get('api_version', 'v1'),
        ]);
    }
}
