<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTGuard;

/**
 * @group Admin Authentication
 */
class AuthController extends Controller
{
    /**
     * Login as an admin
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->guard()->attempt($request->credentials());

        if (! $token) {
            return response()->json(['message' => __('Invalid credentials.')], 401);
        }

        /** @var User $user */
        $user = $this->guard()->user();

        return $this->respondWithToken($token, $user);
    }

    /**
     * Current admin
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Refresh token
     */
    public function refresh(): JsonResponse
    {
        $token = $this->guard()->refresh();

        /** @var User $user */
        $user = $this->guard()->user();

        return $this->respondWithToken($token, $user);
    }

    /**
     * Logout
     */
    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json(['message' => __('Successfully logged out.')]);
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        return $guard;
    }

    private function respondWithToken(string $token, User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
        ], $status);
    }
}
