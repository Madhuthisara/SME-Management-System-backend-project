<?php

namespace App\Repositories\Eloquent;

use App\Models\ProductTemplate;
use App\Repositories\ProductTemplateRepositoryInterface;

class EloquentProductTemplateRepository extends BaseRepository implements ProductTemplateRepositoryInterface
{
    public function __construct(ProductTemplate $model)
    {
        parent::__construct($model);
    }
}
