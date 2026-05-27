<?php

namespace App\Repositories;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function allWithSorting(array $relations = [], ?string $sort = null, ?string $businessId = null, int $perPage = 15);
}
