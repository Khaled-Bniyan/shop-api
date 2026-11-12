<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Http\Requests\LoginRequest;
use App\Modules\Users\Http\Requests\RegisterRequest;
use App\Modules\Users\Http\Resources\UserResource;
use App\Modules\Users\Models\User;
use App\Shared\Http\ApiErrorResponse;
use Illuminate\Http\JsonResponse;
use LogicException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create(
            $request->safe()->only(['name', 'email', 'password'])
        );

        return $this->tokenResponse($this->guard()->login($user), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->guard()->attempt(
            $request->safe()->only(['email', 'password'])
        );

        if (! is_string($token)) {
            return ApiErrorResponse::make('بيانات الدخول غير صحيحة', 401);
        }

        return $this->tokenResponse($token);
    }

    public function me(): JsonResponse
    {
        $user = $this->guard()->user();

        if (! $user instanceof User) {
            return ApiErrorResponse::make('يجب تسجيل الدخول', 401);
        }

        return (new UserResource($user))
            ->response()
            ->setStatusCode(200);
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    protected function tokenResponse(string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) $this->guard()->getTTL() * 60,
        ], $status);
    }

    private function guard(): JWTGuard
    {
        $guard = auth('api');

        if (! $guard instanceof JWTGuard) {
            throw new LogicException('The api guard must use the JWT driver.');
        }

        return $guard;
    }
}
