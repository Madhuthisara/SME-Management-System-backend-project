<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepo
    ) {}

    public function getAllProducts(?string $businessId = null): Collection
    {
        if ($businessId) {
            return $this->productRepo->findBy(['business_id' => $businessId], ['*'], ['category']);
        }
        return $this->productRepo->all(['*'], ['category']);
    }

    public function getProductById(string $id): ?Product
    {
        /** @var Product|null $product */
        $product = $this->productRepo->find($id, ['*'], ['category', 'images']);
        return $product;
    }

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = $this->productRepo->create([
                'business_id' => $data['business_id'],
                'category_id' => $data['category_id'],
                'product_template_id' => $data['product_template_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'sku' => $data['sku'],
                'base_price' => $data['base_price'],
                'discount' => $data['discount'] ?? 0,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
            ]);

            if (isset($data['gallery']) && is_array($data['gallery'])) {
                foreach ($data['gallery'] as $imageUrl) {
                    $product->images()->create(['image_url' => $imageUrl]);
                }
            }

            return $product->load('category', 'productTemplate.materials.material', 'images');
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $updateData = [
                'category_id' => $data['category_id'],
                'product_template_id' => $data['product_template_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'sku' => $data['sku'],
                'base_price' => $data['base_price'],
                'discount' => $data['discount'] ?? 0,
            ];

            if (isset($data['thumbnail_url'])) {
                $updateData['thumbnail_url'] = $data['thumbnail_url'];
            }

            $this->productRepo->update($product->id, $updateData);

            if (isset($data['gallery']) && is_array($data['gallery'])) {
                $product->images()->delete();
                foreach ($data['gallery'] as $imageUrl) {
                    $product->images()->create(['image_url' => $imageUrl]);
                }
            }

            return $product->fresh(['category', 'productTemplate.materials.material', 'images']);
        });
    }

    public function deleteProduct(Product $product): bool
    {
        return $this->productRepo->delete($product->id);
    }

    public function getProductVariants(string $productId): array
    {
        $stocks = \App\Models\ProductStock::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->with(['attributeOptions.attribute'])
            ->get();

        $variants = [];
        foreach ($stocks as $stock) {
            $attributes = $stock->attributeOptions->map(function ($option) {
                return [
                    'attribute_name' => $option->attribute->name,
                    'attribute_id' => $option->attribute->attribute_id,
                    'option_id' => $option->option_id,
                    'option_name' => $option->name,
                    'option_code' => $option->code,
                ];
            });

            // Group by a unique key of option IDs to find unique combinations
            $optionIds = $stock->attributeOptions->pluck('option_id')->sort()->implode(',');
            
            if (!isset($variants[$optionIds])) {
                $variants[$optionIds] = [
                    'attributes' => $attributes,
                    'total_stock' => $stock->quantity,
                    'option_ids' => $stock->attributeOptions->pluck('option_id')->toArray(),
                ];
            } else {
                $variants[$optionIds]['total_stock'] += $stock->quantity;
            }
        }

        return array_values($variants);
    }

    public function getRequiredAttributes(string $productId): array
    {
        $product = Product::with(['productTemplate.materials.material.attributes.options'])->find($productId);
        
        if (!$product || !$product->productTemplate) {
            return [];
        }

        $attributesList = [];

        foreach ($product->productTemplate->materials as $templateMaterial) {
            $material = $templateMaterial->material;
            if (!$material) continue;

            foreach ($material->attributes as $attribute) {
                if (!isset($attributesList[$attribute->attribute_id])) {
                    $attributesList[$attribute->attribute_id] = [
                        'attribute_id' => $attribute->attribute_id,
                        'name' => $attribute->name,
                        'options' => $attribute->options->map(function($opt) {
                            return [
                                'option_id' => $opt->option_id,
                                'name' => $opt->name,
                                'code' => $opt->code
                            ];
                        })->toArray()
                    ];
                }
            }
        }

        return array_values($attributesList);
    }
}
