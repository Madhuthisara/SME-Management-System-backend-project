<?php

namespace App\Http\Controllers;

use App\Models\ProductTemplate;
use App\Services\ProductTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductTemplateController extends Controller
{
    public function __construct(
        protected ProductTemplateService $productTemplateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');

        if (!$businessId) {
            return $this->errorResponse('Business ID is required', 422);
        }

        $perPage = (int) $request->query('per_page', 15);
        $templates = $this->productTemplateService->getAllTemplates($businessId, $perPage);

        return $this->successResponse($templates, 'Product templates retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'name' => 'required|string|max:255',
                'primary_material_id' => 'nullable|string|exists:materials,mat_id',
                'materials' => 'required|array|min:1',
                'materials.*.material_id' => 'required|string|exists:materials,mat_id',
                'materials.*.quantity' => 'required|numeric|min:0.0001',
            ]);

            $template = $this->productTemplateService->createTemplate($validatedData);
            return $this->successResponse($template, 'Product template created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create product template', 500, [], $e);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|exists:product_templates,id',
                'name' => 'required|string|max:255',
                'primary_material_id' => 'nullable|string|exists:materials,mat_id',
                'materials' => 'required|array|min:1',
                'materials.*.material_id' => 'required|string|exists:materials,mat_id',
                'materials.*.quantity' => 'required|numeric|min:0.0001',
            ]);

            $template = ProductTemplate::find($request->id);

            if (!$template) {
                return $this->errorResponse('Product template not found', 404);
            }

            $updatedTemplate = $this->productTemplateService->updateTemplate($template, $validatedData);
            return $this->successResponse($updatedTemplate, 'Product template updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product template', 500, [], $e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Product template ID is required', 422);
        }

        $template = ProductTemplate::find($id);

        if (!$template) {
            return $this->errorResponse('Product template not found', 404);
        }

        try {
            $this->productTemplateService->deleteTemplate($template);
            return $this->successResponse([], 'Product template deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product template', 500, [], $e);
        }
    }
}
