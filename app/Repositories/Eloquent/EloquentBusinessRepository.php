<?php

namespace App\Repositories\Eloquent;

use App\Models\Business;
use App\Repositories\BusinessRepositoryInterface;

class EloquentBusinessRepository extends BaseRepository implements BusinessRepositoryInterface
{
    public function __construct(Business $business)
    {
        parent::__construct($business);
    }
}
