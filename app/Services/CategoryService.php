<?php

namespace App\Services;

use App\Repositories\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepo
    ) {}

    public function getAllCategories(?string $businessId = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if ($businessId) {
            return $this->categoryRepo->paginateBy(['business_id' => $businessId], $perPage);
        }
        return $this->categoryRepo->paginate($perPage);
    }

    public function createCategory(array $data)
    {
        return $this->categoryRepo->create($data);
    }

    public function updateCategory($category, array $data)
    {
        $this->categoryRepo->update($category->id, $data);
        return $category->fresh();
    }

    public function deleteCategory($category)
    {
        return $this->categoryRepo->delete($category->id);
    }
}
