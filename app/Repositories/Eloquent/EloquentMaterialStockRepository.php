<?php

namespace App\Repositories\Eloquent;

use App\Models\MaterialStock;
use App\Repositories\MaterialStockRepositoryInterface;

class EloquentMaterialStockRepository extends BaseRepository implements MaterialStockRepositoryInterface
{
    public function __construct(MaterialStock $materialStock)
    {
        parent::__construct($materialStock);
    }
}
