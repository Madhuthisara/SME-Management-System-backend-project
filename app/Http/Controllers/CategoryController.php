<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');

        $categories = $this->categoryService->getAllCategories($businessId);
        return $this->successResponse($categories, 'Categories retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories')->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id);
                    }),
                ],
            ]);

            $category = $this->categoryService->createCategory($validatedData);
            return $this->successResponse($category, 'Category created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create category', 500, [], $e);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
            ]);

            $category = Category::find($request->id);
            
            // Custom unique check for update
            $exists = Category::where('business_id', $category->business_id)
                ->where('name', $request->name)
                ->where('id', '!=', $category->id)
                ->exists();

            if ($exists) {
                 return $this->errorResponse('The name has already been taken.', 422);
            }

            $updatedCategory = $this->categoryService->updateCategory($category, $validatedData);
            return $this->successResponse($updatedCategory, 'Category updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update category', 500, [], $e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');
        $category = Category::find($id);

        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }

        try {
            $this->categoryService->deleteCategory($category);
            return $this->successResponse([], 'Category deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete category', 500, [], $e);
        }
    }
}
