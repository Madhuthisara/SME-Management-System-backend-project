<?php

namespace App\Http\Controllers;

use App\Models\MaterialStock;
use App\Services\MaterialStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialStockController extends Controller
{
    public function __construct(
        protected MaterialStockService $materialStockService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');

        if (!$businessId) {
            return $this->errorResponse('Business ID is required', 422);
        }

        $perPage = (int) $request->query('per_page', 15);
        $stocks = $this->materialStockService->getAllMaterialStocks($businessId, $perPage);

        return $this->successResponse($stocks, 'Material stocks retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'material_id' => 'required|exists:materials,mat_id',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'quantity' => 'required|numeric|min:0',
                'unit_cost' => 'nullable|numeric|min:0',
                'reorder_level' => 'required|numeric|min:0',
                'sku' => 'nullable|string|max:255',
                'attribute_options' => 'nullable|array',
                'attribute_options.*' => 'exists:attribute_options,option_id',
            ]);

            $stock = $this->materialStockService->createMaterialStock($validatedData);
            return $this->successResponse($stock, 'Material stock created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create material stock', 500, [], $e);
        }
    }

    public function show(MaterialStock $materialStock): JsonResponse
    {
        return $this->successResponse($materialStock->load(['material', 'attributeOptions', 'supplier']), 'Material stock retrieved successfully');
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'stock_id' => 'required|exists:material_stocks,stock_id',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'quantity' => 'sometimes|required|numeric|min:0',
                'unit_cost' => 'nullable|numeric|min:0',
                'reorder_level' => 'sometimes|required|numeric|min:0',
                'sku' => 'nullable|string|max:255',
                'attribute_options' => 'nullable|array',
                'attribute_options.*' => 'exists:attribute_options,option_id',
            ]);

            $materialStock = MaterialStock::find($request->stock_id);

            if (!$materialStock) {
                return $this->errorResponse('Material stock not found', 404);
            }

            $stock = $this->materialStockService->updateMaterialStock($materialStock, $validatedData);
            return $this->successResponse($stock, 'Material stock updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update material stock', 500, [], $e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $id = $request->query('stock_id') ?? $request->query('id');

            if (!$id) {
                return $this->errorResponse('Material stock ID is required', 422);
            }

            $materialStock = MaterialStock::find($id);

            if (!$materialStock) {
                return $this->errorResponse('Material stock not found', 404);
            }

            $this->materialStockService->deleteMaterialStock($materialStock);
            return $this->successResponse([], 'Material stock deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete material stock', 500, [], $e);
        }
    }
}
