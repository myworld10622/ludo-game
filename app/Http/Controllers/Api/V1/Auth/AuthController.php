<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ProfileRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\SocialLoginRequest;
use App\Http\Resources\Api\V1\AuthTokenResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse(
            new AuthTokenResource($result),
            'Registration completed successfully.',
            201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());
        } catch (HttpException $exception) {
            return $this->errorResponse(
                $exception->getMessage(),
                ['identity' => [$exception->getMessage()]],
                $exception->getStatusCode()
            );
        }

        return $this->successResponse(
            new AuthTokenResource($result),
            'Login completed successfully.'
        );
    }

    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->socialLogin($request->validated());
        } catch (HttpException $exception) {
            return $this->errorResponse(
                $exception->getMessage(),
                ['provider' => [$exception->getMessage()]],
                $exception->getStatusCode()
            );
        }

        return $this->successResponse(
            new AuthTokenResource($result),
            'Social login completed successfully.'
        );
    }

    public function logout(ProfileRequest $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(
            null,
            'Logout completed successfully.'
        );
    }

    public function me(ProfileRequest $request): JsonResponse
    {
        $user = $this->authService->profile($request->user());

        return $this->successResponse(
            new UserResource($user),
            'Profile fetched successfully.'
        );
    }
}
