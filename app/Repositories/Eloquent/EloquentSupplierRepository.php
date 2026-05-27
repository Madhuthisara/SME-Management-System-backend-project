<?php

namespace App\Repositories\Eloquent;

use App\Models\Supplier;
use App\Repositories\SupplierRepositoryInterface;

class EloquentSupplierRepository extends BaseRepository implements SupplierRepositoryInterface
{
    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }
}
