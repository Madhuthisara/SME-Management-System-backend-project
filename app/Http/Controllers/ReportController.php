<?php
// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function getTopSellingProductsReport(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            
            $filters = [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'size' => $request->query('size'),
                'color' => $request->query('color'),
                'product_name' => $request->query('product_name'),
                'district' => $request->query('district'),
                'status' => $request->query('status'),
            ];

            $data = $this->reportService->getTopSellingProductsData((int) $limit, $filters);

            return response()->json([
                'success' => true,
                'message' => 'Top selling products report fetched successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function getSalesRevenueReport(Request $request): JsonResponse
    {
        try {
            $filters = [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'size' => $request->query('size'),
                'color' => $request->query('color'),
                'product_name' => $request->query('product_name'),
                'district' => $request->query('district'),
                'status' => $request->query('status'),
            ];

            $data = $this->reportService->getSalesRevenueData($filters);

            return response()->json([
                'success' => true,
                'message' => 'Sales revenue report fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}