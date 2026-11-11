<?php

use App\Shared\Http\ApiErrorResponse;
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
use Symfony\Component\HttpFoundation\Response;
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
        $exceptions->respond(function (Response $response) {
            if (request()->is('api/*')) {
                return SecurityHeaders::apply($response);
            }

            return $response;
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof InventoryReservationRejectedException) {
                return ApiErrorResponse::make(
                    'تعذر حجز الكمية المطلوبة من المخزون',
                    422,
                    ['inventory' => [$e->getMessage()]],
                );
            }

            if ($e instanceof InventoryServiceUnavailableException) {
                return ApiErrorResponse::make(
                    'خدمة المخزون غير متاحة مؤقتاً، حاول مرة أخرى لاحقاً',
                    503,
                );
            }

            if ($e instanceof ValidationException) {
                return ApiErrorResponse::make(
                    'بيانات غير صالحة',
                    422,
                    $e->errors(),
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiErrorResponse::make('يجب تسجيل الدخول', 401);
            }

            if ($e instanceof HttpExceptionInterface) {
                $messages = [
                    403 => 'غير مصرّح لك بتنفيذ هذه العملية',
                    404 => 'المورد المطلوب غير موجود',
                    405 => 'طريقة الطلب غير مسموحة',
                    429 => 'محاولات كثيرة، حاول لاحقاً',
                ];
                $status = $e->getStatusCode();

                return ApiErrorResponse::make(
                    $messages[$status] ?? ($e->getMessage() ?: 'حدث خطأ في الطلب'),
                    $status,
                    headers: $e->getHeaders(),
                );
            }

            return ApiErrorResponse::make(
                'حدث خطأ داخلي، حاول مرة أخرى لاحقاً',
                500,
            );
        });
    })->create();
