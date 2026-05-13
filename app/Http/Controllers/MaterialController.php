<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function __construct(
        protected MaterialService $materialService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');

        if (!$businessId) {
            return $this->errorResponse('Business ID is required', 422);
        }

        $materials = $this->materialService->getAllMaterials($businessId);

        return $this->successResponse($materials, 'Materials retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'attributes' => 'nullable|array',
                'attributes.*' => 'exists:attributes,attribute_id',
            ]);

            $material = $this->materialService->createMaterial($validatedData);
            return $this->successResponse($material, 'Material created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create material', 500, [], $e);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'mat_id' => 'required|exists:materials,mat_id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'attributes' => 'nullable|array',
                'attributes.*' => 'exists:attributes,attribute_id',
            ]);

            $material = Material::find($request->mat_id);

            if (!$material) {
                return $this->errorResponse('Material not found', 404);
            }

            $updatedMaterial = $this->materialService->updateMaterial($material, $validatedData);
            return $this->successResponse($updatedMaterial, 'Material updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update material', 500, [], $e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Material ID is required', 422);
        }

        $material = Material::find($id);

        if (!$material) {
            return $this->errorResponse('Material not found', 404);
        }

        try {
            $this->materialService->deleteMaterial($material);
            return $this->successResponse([], 'Material deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete material', 500, [], $e);
        }
    }
}