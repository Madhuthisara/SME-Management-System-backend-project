<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Services\ProductStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductStockController extends Controller
{
    public function __construct(
        protected ProductStockService $productStockService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');

        if (!$businessId) {
            return $this->errorResponse('Business ID is required', 422);
        }

        $perPage = (int) $request->query('per_page', 15);
        $stocks = $this->productStockService->getAllStocks($businessId, $perPage);

        return $this->successResponse($stocks, 'Product stocks retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'product_id' => 'required|exists:products,id',
                'batch_id' => 'nullable|string|max:255',
                'quantity' => 'required|numeric|min:0.0001',
                'reorder_level' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'materials' => 'required|array|min:1',
                'materials.*.material_id' => 'required|string|exists:materials,mat_id',
                'materials.*.material_stock_id' => 'required|string|exists:material_stocks,stock_id',
            ]);

            $stock = $this->productStockService->createOrUpdateStock($validatedData);
            return $this->successResponse($stock, 'Product stock added and materials deducted successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, [], $e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Product stock ID is required', 422);
        }

        $stock = ProductStock::find($id);

        if (!$stock) {
            return $this->errorResponse('Product stock not found', 404);
        }

        try {
            $this->productStockService->deleteStock($stock);
            return $this->successResponse([], 'Product stock deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product stock', 500, [], $e);
        }
    }
}
