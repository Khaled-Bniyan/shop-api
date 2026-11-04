<?php

use App\Shared\Http\Middleware\RoleMiddleware;
use App\Shared\Http\Middleware\SecurityHeaders;
use App\Shared\Inventory\Exceptions\InventoryReservationRejectedException;
use App\Shared\Inventory\Exceptions\InventoryServiceUnavailableException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
        $middleware->appendToGroup('api', SecurityHeaders::class);
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof InventoryReservationRejectedException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['inventory' => [$e->getMessage()]],
                ], 422);
            }

            if ($e instanceof InventoryServiceUnavailableException) {
                return response()->json([
                    'message' => 'Inventory service is temporarily unavailable. Please try again shortly.',
                    'errors' => null,
                ], 503);
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'بيانات غير صالحة',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'يجب تسجيل الدخول',
                    'errors' => null,
                ], 401);
            }

            if ($e instanceof HttpExceptionInterface) {
                $messages = [
                    403 => 'غير مصرّح لك بتنفيذ هذه العملية',
                    404 => 'المورد المطلوب غير موجود',
                    429 => 'محاولات كثيرة، حاول لاحقاً',
                ];
                $status = $e->getStatusCode();

                return response()->json([
                    'message' => $messages[$status] ?? ($e->getMessage() ?: 'خطأ'),
                    'errors' =>'يرجى الانتظار قليلا قبل اعادة المحاولة',
                ], $status, $e->getHeaders());
            }

            return null;
        });
    })->create();
