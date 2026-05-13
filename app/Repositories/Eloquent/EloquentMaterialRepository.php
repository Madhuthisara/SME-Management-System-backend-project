<?php

namespace App\Repositories\Eloquent;

use App\Models\Material;
use App\Repositories\MaterialRepositoryInterface;

class EloquentMaterialRepository extends BaseRepository implements MaterialRepositoryInterface
{
    public function __construct(Material $material)
    {
        parent::__construct($material);
    }
}
