<?php

namespace App\Shared\Http;

use Illuminate\Http\JsonResponse;

final class ApiErrorResponse
{
    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, string>  $headers
     */
    public static function make(
        string $message,
        int $status,
        ?array $errors = null,
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $status, $headers);
    }
}
