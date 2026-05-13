<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Models\ProductStockMaterial;
use App\Models\MaterialStock;
use App\Models\ProductTemplate;
use App\Repositories\ProductStockRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductStockService
{
    public function __construct(
        protected ProductStockRepositoryInterface $productStockRepo
    ) {}

    public function getAllStocks(string $businessId): Collection
    {
        return $this->productStockRepo->findBy(['business_id' => $businessId], ['*'], ['product', 'stockMaterials.materialStock.attributeOptions', 'stockMaterials.material']);
    }

    public function createOrUpdateStock(array $data): ProductStock
    {
        return DB::transaction(function () use ($data) {
            $businessId = $data['business_id'];
            $productId = $data['product_id'];
            $quantityToAdd = $data['quantity'];
            $reorderLevel = $data['reorder_level'] ?? 0;
            $batchId = $data['batch_id'] ?? null;
            $notes = $data['notes'] ?? null;

            $product = \App\Models\Product::with('productTemplate.materials')->findOrFail($productId);
            $template = $product->productTemplate;

            if (!$template) {
                throw new Exception("Product does not have a linked template/BOM.");
            }

            // 1. Verify and Deduct Materials
            if (isset($data['materials']) && is_array($data['materials'])) {
                foreach ($data['materials'] as $matEntry) {
                    $materialId = $matEntry['material_id'];
                    $materialStockId = $matEntry['material_stock_id'];

                    // Get BOM quantity for this material
                    $bomMaterial = $template->materials->where('material_id', $materialId)->first();
                    if (!$bomMaterial) {
                        throw new Exception("Material {$materialId} is not part of the Product Template BOM.");
                    }

                    $totalNeeded = $bomMaterial->quantity * $quantityToAdd;

                    $matStock = MaterialStock::where('stock_id', $materialStockId)
                        ->where('business_id', $businessId)
                        ->firstOrFail();

                    if ($matStock->quantity < $totalNeeded) {
                        throw new Exception("Insufficient stock for material variant: " . ($matStock->sku ?? $materialStockId));
                    }

                    // Deduct
                    $matStock->decrement('quantity', $totalNeeded);
                }
            }

            // 2. Create or Update Product Stock (Differentiate by batch_id and Attributes)
            // Priority 1: Explicitly provided attribute_option_ids (Common Solution)
            // Priority 2: Inherited from primary material (Fallback)
            
            $targetAttributeOptionIds = $data['attribute_option_ids'] ?? [];

            if (empty($targetAttributeOptionIds)) {
                $primaryMatId = $template->primary_material_id;
                if ($primaryMatId && isset($data['materials'])) {
                    $primaryEntry = collect($data['materials'])->where('material_id', $primaryMatId)->first();
                    if ($primaryEntry) {
                        $matStockId = $primaryEntry['material_stock_id'];
                        $matStock = MaterialStock::with('attributeOptions')->findOrFail($matStockId);
                        $targetAttributeOptionIds = $matStock->attributeOptions->pluck('option_id')->toArray();
                    }
                }
            }

            // Find existing stock with same product, batch, and EXACT SAME attributes
            $productStockQuery = ProductStock::where('business_id', $businessId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId);

            if (empty($targetAttributeOptionIds)) {
                $productStockQuery->whereDoesntHave('attributeOptions');
            } else {
                $productStockQuery->whereHas('attributeOptions', function ($q) use ($targetAttributeOptionIds) {
                    $q->whereIn('attribute_options.option_id', $targetAttributeOptionIds);
                }, '=', count($targetAttributeOptionIds))
                ->whereDoesntHave('attributeOptions', function ($q) use ($targetAttributeOptionIds) {
                    $q->whereNotIn('attribute_options.option_id', $targetAttributeOptionIds);
                });
            }

            $productStock = $productStockQuery->first();

            if ($productStock) {
                $productStock->increment('quantity', $quantityToAdd);
                $productStock->update([
                    'reorder_level' => $reorderLevel,
                    'notes' => $notes ?? $productStock->notes,
                ]);
            } else {
                $productStock = $this->productStockRepo->create([
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity' => $quantityToAdd,
                    'reorder_level' => $reorderLevel,
                    'notes' => $notes,
                ]);

                // Attach inherited attributes
                if (!empty($targetAttributeOptionIds)) {
                    $productStock->attributeOptions()->attach($targetAttributeOptionIds);
                }
            }

            // 3. Log used materials (optional but good for tracking)
            if (isset($data['materials']) && is_array($data['materials'])) {
                foreach ($data['materials'] as $matEntry) {
                    $bomMaterial = $template->materials->where('material_id', $matEntry['material_id'])->first();
                    ProductStockMaterial::create([
                        'product_stock_id' => $productStock->id,
                        'material_id' => $matEntry['material_id'],
                        'material_stock_id' => $matEntry['material_stock_id'],
                        'quantity_used' => $bomMaterial->quantity * $quantityToAdd,
                    ]);
                }
            }

            return $productStock->load('product', 'stockMaterials.material', 'attributeOptions.attribute');
        });
    }

    public function deleteStock(ProductStock $stock): bool
    {
        return $this->productStockRepo->delete($stock->id);
    }
}
