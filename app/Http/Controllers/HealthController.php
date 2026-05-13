<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthController extends Controller
{
    /**
     * Check the health of the application and its dependencies.
     *
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $status = [
            'app' => 'up',
            'database' => 'down',
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            // Check database connection
            DB::connection()->getPdo();
            $status['database'] = 'up';

            return $this->successResponse($status, 'System is healthy');
        } catch (Exception $e) {
            return $this->errorResponse('System is unhealthy', 503, [
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
