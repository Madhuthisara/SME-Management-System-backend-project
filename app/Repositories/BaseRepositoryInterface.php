<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*'], array $relations = []): Collection;

    public function find(string $id, array $columns = ['*'], array $relations = []): ?Model;

    public function findBy(array $criteria, array $columns = ['*'], array $relations = []): Collection;

    public function create(array $details): Model;

    public function update(string $id, array $details): bool;

    public function delete(string $id): bool;
}
