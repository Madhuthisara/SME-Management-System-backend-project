<?php

namespace App\Services;

use App\Models\Material;
use App\Repositories\MaterialRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MaterialService
{
    public function __construct(
        protected MaterialRepositoryInterface $materialRepo
    ) {}

    public function getAllMaterials(string $businessId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->materialRepo->paginateBy(['business_id' => $businessId], $perPage, ['*'], ['attributes']);
    }

    public function createMaterial(array $data): Material
    {
        $material = $this->materialRepo->create($data);

        if (isset($data['attributes'])) {
            $material->attributes()->sync($data['attributes']);
        }

        return $material->load('attributes');
    }

    public function updateMaterial(Material $material, array $data): Material
    {
        $this->materialRepo->update($material->mat_id, $data);

        if (isset($data['attributes'])) {
            $material->attributes()->sync($data['attributes']);
        }

        return $material->fresh('attributes');
    }

    public function deleteMaterial(Material $material): void
    {
        $this->materialRepo->delete($material->mat_id);
    }
}