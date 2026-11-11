<?php

namespace App\Shared\Http\Middleware;

use App\Shared\Http\ApiErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return ApiErrorResponse::make(
                'غير مصرّح لك بتنفيذ هذه العملية',
                403,
            );
        }

        return $next($request);
    }
}
