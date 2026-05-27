<?php

namespace App\Services;

use App\Models\MaterialStock;
use App\Models\MaterialStockTransaction;
use App\Repositories\MaterialStockRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MaterialStockService
{
    public function __construct(
        protected MaterialStockRepositoryInterface $stockRepo
    ) {}

    public function getAllMaterialStocks(string $businessId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->stockRepo->paginateBy(
            ['business_id' => $businessId],
            $perPage,
            ['*'],
            ['material.attributes', 'attributeOptions', 'supplier']
        );
    }

    public function createMaterialStock(array $data): MaterialStock
    {
        $materialStock = $this->stockRepo->create($data);

        if (isset($data['attribute_options'])) {
            $materialStock->attributeOptions()->sync($data['attribute_options']);
        }

        // Log the initial purchase transaction
        $unitCost = $data['unit_cost'] ?? 0;
        $qty = $data['quantity'] ?? 0;
        MaterialStockTransaction::create([
            'stock_id'         => $materialStock->stock_id,
            'supplier_id'      => $data['supplier_id'] ?? null,
            'transaction_type' => 'purchase',
            'quantity'         => $qty,
            'unit_cost'        => $unitCost,
            'total_cost'       => $unitCost * $qty,
            'notes'            => $data['notes'] ?? 'Initial stock entry',
        ]);

        return $materialStock->load(['material.attributes', 'attributeOptions', 'supplier']);
    }

    public function updateMaterialStock(MaterialStock $materialStock, array $data): MaterialStock
    {
        $this->stockRepo->update($materialStock->stock_id, $data);

        if (isset($data['attribute_options'])) {
            $materialStock->attributeOptions()->sync($data['attribute_options']);
        }

        return $materialStock->fresh(['material.attributes', 'attributeOptions', 'supplier']);
    }

    public function deleteMaterialStock(MaterialStock $materialStock): void
    {
        $this->stockRepo->delete($materialStock->stock_id);
    }
}
