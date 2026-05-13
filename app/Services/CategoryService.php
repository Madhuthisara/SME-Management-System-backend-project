<?php

namespace App\Services;

use App\Repositories\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepo
    ) {}

    public function getAllCategories(?string $businessId = null): Collection
    {
        if ($businessId) {
            return $this->categoryRepo->findBy(['business_id' => $businessId]);
        }
        return $this->categoryRepo->all();
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
