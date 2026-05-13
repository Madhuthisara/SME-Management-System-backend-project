<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success response.
     *
     * @param mixed $output
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse(mixed $output = [], string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'output' => $output,
        ], $code);
    }

    /**
     * Error response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $output
     * @param \Throwable|null $exception
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Error', int $code = 500, mixed $output = [], \Throwable $exception = null): JsonResponse
    {
        // Log the error automatically
        if ($code >= 500) {
            Log::error('Server Error: ' . $message, [
                'url' => request()->url(),
                'method' => request()->method(),
                'error' => $exception ? $exception->getMessage() : $message,
                'trace' => $exception ? $exception->getTraceAsString() : null,
            ]);
        } else {
            Log::warning('Client Error: ' . $message, [
                'url' => request()->url(),
                'method' => request()->method(),
                'code' => $code,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'output' => $output,
        ], (int) $code);
    }
}
