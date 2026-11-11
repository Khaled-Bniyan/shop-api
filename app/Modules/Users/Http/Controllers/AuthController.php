<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Http\Requests\LoginRequest;
use App\Modules\Users\Http\Requests\RegisterRequest;
use App\Modules\Users\Http\Resources\UserResource;
use App\Modules\Users\Models\User;
use App\Shared\Http\ApiErrorResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create(
            $request->safe()->only(['name', 'email', 'password'])
        );

        return $this->tokenResponse(auth('api')->login($user), 201);
    }

    public function login(LoginRequest $request)
    {
        $token = auth('api')->attempt(
            $request->safe()->only(['email', 'password'])
        );

        if (! $token) {
            return ApiErrorResponse::make('بيانات الدخول غير صحيحة', 401);
        }

        return $this->tokenResponse($token);
    }

    public function me()
    {
        return (new UserResource(auth('api')->user()))
            ->response()
            ->setStatusCode(200);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    protected function tokenResponse(string $token, int $status = 200)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], $status);
    }
}
