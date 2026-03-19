<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Wallet\WalletHistoryRequest;
use App\Http\Resources\Api\V1\WalletResource;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    public function summary(WalletHistoryRequest $request): JsonResponse
    {
        $wallet = $this->walletService->summary($request->user());

        return $this->successResponse(
            $wallet ? new WalletResource($wallet) : null,
            'Wallet summary fetched successfully.'
        );
    }

    public function history(WalletHistoryRequest $request): JsonResponse
    {
        $history = $this->walletService->history(
            $request->user(),
            $request->integer('per_page', 20)
        );

        return $this->successResponse([
            'items' => WalletTransactionResource::collection($history->items())->resolve(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ], 'Wallet transaction history fetched successfully.');
    }
}
