<?php

namespace App\Services;

use App\Models\Supplier;
use App\Repositories\SupplierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function __construct(
        protected SupplierRepositoryInterface $supplierRepo
    ) {}

    public function getAllSuppliers(?string $businessId = null, int $perPage = 15): LengthAwarePaginator
    {
        $criteria = [];
        if ($businessId) {
            $criteria['business_id'] = $businessId;
        }

        return $this->supplierRepo->paginateBy($criteria, $perPage);
    }

    public function createSupplier(array $data): Supplier
    {
        return $this->supplierRepo->create($data);
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $this->supplierRepo->update($supplier->id, $data);
        return $supplier->fresh();
    }

    public function deleteSupplier(Supplier $supplier): void
    {
        $this->supplierRepo->delete($supplier->id);
    }
}
