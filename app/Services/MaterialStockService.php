<?php

namespace App\Services;

use App\Models\MaterialStock;
use App\Repositories\MaterialStockRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MaterialStockService
{
    public function __construct(
        protected MaterialStockRepositoryInterface $stockRepo
    ) {}

    public function getAllMaterialStocks(string $businessId): Collection
    {
        return $this->stockRepo->findBy(
            ['business_id' => $businessId],
            ['*'],
            ['material', 'attributeOptions']
        );
    }

    public function createMaterialStock(array $data): MaterialStock
    {
        $materialStock = $this->stockRepo->create($data);

        if (isset($data['attribute_options'])) {
            $materialStock->attributeOptions()->sync($data['attribute_options']);
        }

        return $materialStock->load(['material', 'attributeOptions']);
    }

    public function updateMaterialStock(MaterialStock $materialStock, array $data): MaterialStock
    {
        $this->stockRepo->update($materialStock->stock_id, $data);

        if (isset($data['attribute_options'])) {
            $materialStock->attributeOptions()->sync($data['attribute_options']);
        }

        return $materialStock->fresh(['material', 'attributeOptions']);
    }

    public function deleteMaterialStock(MaterialStock $materialStock): void
    {
        $this->stockRepo->delete($materialStock->stock_id);
    }
}
