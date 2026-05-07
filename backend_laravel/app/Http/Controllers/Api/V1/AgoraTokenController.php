<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Agora\IssueAgoraTokenRequest;
use App\Services\Voice\Agora\AgoraVoiceTokenService;
use Illuminate\Http\JsonResponse;

class AgoraTokenController extends Controller
{
    public function __construct(
        private readonly AgoraVoiceTokenService $agoraVoiceTokenService
    ) {
    }

    public function __invoke(IssueAgoraTokenRequest $request): JsonResponse
    {
        $payload = $this->agoraVoiceTokenService->issueForUser(
            user: $request->user(),
            channelName: (string) $request->query('channel', $request->input('channel')),
            requestedUid: $request->filled('uid') ? (int) $request->input('uid') : null
        );

        return $this->successResponse($payload, 'Agora token generated successfully.');
    }
}
