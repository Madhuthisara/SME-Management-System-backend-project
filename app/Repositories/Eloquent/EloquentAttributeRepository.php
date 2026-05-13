<?php

namespace App\Repositories\Eloquent;

use App\Models\Attribute;
use App\Repositories\AttributeRepositoryInterface;

class EloquentAttributeRepository extends BaseRepository implements AttributeRepositoryInterface
{
    public function __construct(Attribute $attribute)
    {
        parent::__construct($attribute);
    }
}
