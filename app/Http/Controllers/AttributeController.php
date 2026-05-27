<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Services\AttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function __construct(
        protected AttributeService $attributeService
    ) {}

    /**
     * Get all attributes, optionally filtered by business_id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');
        $perPage = (int) $request->query('per_page', 15);
        $attributes = $this->attributeService->getAllAttributes($businessId, $perPage);

        return $this->successResponse($attributes, 'Attributes retrieved successfully');
    }

    /**
     * Create a new attribute.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:attributes,name,NULL,attribute_id,business_id,' . $request->input('business_id'),
                ],
            ], [
                'name.unique' => 'The name has already been taken for this business.',
            ]);

            $attribute = $this->attributeService->createAttribute($validatedData);
            return $this->successResponse($attribute, 'Attribute created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create attribute', 500, [], $e);
        }
    }

    /**
     * Update an existing attribute.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'attribute_id' => 'required|exists:attributes,attribute_id',
                'name' => 'sometimes|required|string|max:100',
            ]);

            $attribute = Attribute::find($request->attribute_id);

            if (!$attribute) {
                return $this->errorResponse('Attribute not found', 404);
            }

            // Custom unique check logic
            if ($request->has('name')) {
                $exists = Attribute::where('business_id', $attribute->business_id)
                    ->where('name', $request->input('name'))
                    ->where('attribute_id', '!=', $request->input('attribute_id'))
                    ->exists();
                
                if ($exists) {
                    return $this->errorResponse('The name has already been taken for this business.', 422);
                }
            }

            $updatedAttribute = $this->attributeService->updateAttribute($attribute, $validatedData);
            return $this->successResponse($updatedAttribute, 'Attribute updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update attribute', 500, [], $e);
        }
    }

    /**
     * Delete an attribute.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Attribute ID is required', 422);
        }

        $attribute = Attribute::find($id);

        if (!$attribute) {
            return $this->errorResponse('Attribute not found', 404);
        }

        if ($attribute->materials()->exists()) {
            return $this->errorResponse('This attribute is used by one or more materials and cannot be deleted.', 422);
        }

        try {
            $this->attributeService->deleteAttribute($attribute);
            return $this->successResponse([], 'Attribute deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete attribute', 500, [], $e);
        }
    }
}