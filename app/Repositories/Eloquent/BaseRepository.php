<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    public function find(string $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id, $columns);
    }

    public function findBy(array $criteria, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->where($criteria)->get($columns);
    }

    public function paginateBy(array $criteria, int $perPage = 15, array $columns = ['*'], array $relations = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model->with($relations)->where($criteria)->paginate($perPage, $columns);
    }

    public function create(array $details): Model
    {
        return $this->model->create($details);
    }

    public function update(string $id, array $details): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            return $model->update($details);
        }
        return false;
    }

    public function delete(string $id): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            return $model->delete();
        }
        return false;
    }
}
