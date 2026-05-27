<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');
        $sort = $request->query('sort');
        $perPage = (int) $request->query('per_page', 15);

        $products = $this->productService->getAllProducts($businessId, $sort, $perPage);

        return $this->successResponse(ProductResource::collection($products), 'Products retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        return $this->successResponse(new ProductResource($product), 'Product retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'category_id' => 'required|exists:categories,id',
                'product_template_id' => 'nullable|exists:product_templates,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sku' => 'required|string|unique:products,sku',
                'base_price' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0|max:100',
                'thumbnail_url' => 'nullable|string',
                'gallery' => 'nullable|array',
                'gallery.*' => 'string',
            ]);

            $product = $this->productService->createProduct($validatedData);
            return $this->successResponse($product, 'Product created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, [], $e);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->errorResponse('Product ID is required', 422);
        }

        $product = Product::find($id);
        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        try {
            $validatedData = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'product_template_id' => 'nullable|exists:product_templates,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sku' => 'required|string|unique:products,sku,' . $product->id,
                'base_price' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0|max:100',
                'thumbnail_url' => 'nullable|string',
                'gallery' => 'nullable|array',
                'gallery.*' => 'string',
            ]);

            $updatedProduct = $this->productService->updateProduct($product, $validatedData);
            return $this->successResponse($updatedProduct, 'Product updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, [], $e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Product ID is required', 422);
        }

        $product = Product::find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        try {
            $this->productService->deleteProduct($product);
            return $this->successResponse([], 'Product deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product', 500, [], $e);
        }
    }

    public function getVariants(string $id): JsonResponse
    {
        try {
            $variants = $this->productService->getProductVariants($id);
            return $this->successResponse($variants, 'Product variants retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getRequiredAttributes(string $id): JsonResponse
    {
        try {
            $attributes = $this->productService->getRequiredAttributes($id);
            return $this->successResponse($attributes, 'Required attributes retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
