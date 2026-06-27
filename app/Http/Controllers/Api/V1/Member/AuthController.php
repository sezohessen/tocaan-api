<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Auth\RegisterMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\Member\LoginRequest;
use App\Http\Requests\Api\V1\Auth\Member\RegisterRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTGuard;

/**
 * @group Member Authentication
 */
class AuthController extends Controller
{
    private const GUARD = 'member-api';

    /**
     * Register a member
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request, RegisterMemberAction $action): JsonResponse
    {
        $member = $action->execute($request->toData());

        $token = $this->guard()->login($member);

        return $this->respondWithToken($token, $member, 201);
    }

    /**
     * Login as a member
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->guard()->attempt($request->credentials());

        if (! $token) {
            return response()->json(['message' => __('Invalid credentials.')], 401);
        }

        /** @var Member $member */
        $member = $this->guard()->user();

        return $this->respondWithToken($token, $member);
    }

    /**
     * Current member
     */
    public function me(): JsonResponse
    {
        /** @var Member $member */
        $member = $this->guard()->user();

        return response()->json(['data' => new MemberResource($member)]);
    }

    /**
     * Refresh token
     */
    public function refresh(): JsonResponse
    {
        $token = $this->guard()->refresh();

        /** @var Member $member */
        $member = $this->guard()->user();

        return $this->respondWithToken($token, $member);
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
        $guard = Auth::guard(self::GUARD);

        return $guard;
    }

    private function respondWithToken(string $token, Member $member, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => new MemberResource($member),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
        ], $status);
    }
}
