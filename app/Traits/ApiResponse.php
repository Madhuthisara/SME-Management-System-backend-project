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
        $formattedData = $output;

        if ($output instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $formattedData = [
                'total_records' => $output->total(),
                'current_page' => $output->currentPage(),
                'per_page' => $output->perPage(),
                'values' => $output->items(),
            ];
        } elseif ($output instanceof \Illuminate\Http\Resources\Json\AnonymousResourceCollection && $output->resource instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $paginator = $output->resource;
            $formattedData = [
                'total_records' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'values' => $output->collection,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'output' => $formattedData,
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
