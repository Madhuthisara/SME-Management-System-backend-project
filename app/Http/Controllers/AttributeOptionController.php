<?php

namespace App\Http\Controllers;

use App\Models\AttributeOption;
use App\Services\AttributeOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeOptionController extends Controller
{
    public function __construct(
        protected AttributeOptionService $attributeOptionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $attributeId = $request->query('attribute_id');

        if (!$attributeId) {
            return $this->errorResponse('Attribute ID is required', 422);
        }

        $options = $this->attributeOptionService->getOptionsByAttributeId($attributeId);

        return $this->successResponse($options, 'Attribute options retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'attribute_id' => 'required|exists:attributes,attribute_id',
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('attribute_options')->where(function ($query) use ($request) {
                        return $query->where('attribute_id', $request->attribute_id);
                    }),
                ],
                'description' => 'nullable|string',
            ], [
                'code.unique' => 'The code has already been taken for this attribute.',
            ]);

            $option = $this->attributeOptionService->createOption($validatedData);
            return $this->successResponse($option, 'Attribute option created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create attribute option', 500, [], $e);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $option = AttributeOption::find($request->option_id);
            
            if (!$option) {
                return $this->errorResponse('Attribute option not found', 404);
            }

            $validatedData = $request->validate([
                'option_id' => 'required|exists:attribute_options,option_id',
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('attribute_options')->ignore($request->option_id, 'option_id')->where(function ($query) use ($option) {
                        return $query->where('attribute_id', $option->attribute_id);
                    }),
                ],
                'description' => 'nullable|string',
            ], [
                'code.unique' => 'The code has already been taken for this attribute.',
            ]);

            $updatedOption = $this->attributeOptionService->updateOption($option, $validatedData);
            return $this->successResponse($updatedOption, 'Attribute option updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update attribute option', 500, [], $e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Option ID is required', 422);
        }

        $option = AttributeOption::find($id);

        if (!$option) {
            return $this->errorResponse('Attribute option not found', 404);
        }

        if ($option->materialStocks()->exists()) {
            return $this->errorResponse('This option is used by one or more material stocks and cannot be deleted.', 422);
        }

        try {
            $this->attributeOptionService->deleteOption($option);
            return $this->successResponse([], 'Attribute option deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete attribute option', 500, [], $e);
        }
    }
}
