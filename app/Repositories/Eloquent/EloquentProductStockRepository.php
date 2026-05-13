<?php

namespace App\Repositories\Eloquent;

use App\Models\ProductStock;
use App\Repositories\ProductStockRepositoryInterface;

class EloquentProductStockRepository extends BaseRepository implements ProductStockRepositoryInterface
{
    public function __construct(ProductStock $model)
    {
        parent::__construct($model);
    }
}
