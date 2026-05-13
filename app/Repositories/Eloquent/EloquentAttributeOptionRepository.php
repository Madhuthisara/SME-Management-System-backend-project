<?php

namespace App\Repositories\Eloquent;

use App\Models\AttributeOption;
use App\Repositories\AttributeOptionRepositoryInterface;

class EloquentAttributeOptionRepository extends BaseRepository implements AttributeOptionRepositoryInterface
{
    public function __construct(AttributeOption $attributeOption)
    {
        parent::__construct($attributeOption);
    }
}
