<?php

namespace App\Repositories;

use App\Models\Customer;

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?Customer;
}
