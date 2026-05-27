<?php

namespace App\Services;

use App\Models\Attribute;
use App\Repositories\AttributeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AttributeService
{
    public function __construct(
        protected AttributeRepositoryInterface $attributeRepo
    ) {}

    /**
     * Get all attributes, optionally filtered by business_id.
     *
     * @param string|null $businessId
     * @return Collection
     */
    public function getAllAttributes(?string $businessId = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $criteria = [];
        if ($businessId) {
            $criteria['business_id'] = $businessId;
        }

        return $this->attributeRepo->paginateBy($criteria, $perPage, ['*'], ['options']);
    }

    /**
     * Create a new attribute.
     *
     * @param array $data
     * @return Attribute
     */
    public function createAttribute(array $data): Attribute
    {
        return $this->attributeRepo->create($data);
    }

    /**
     * Update an existing attribute.
     *
     * @param Attribute $attribute
     * @param array $data
     * @return Attribute
     */
    public function updateAttribute(Attribute $attribute, array $data): Attribute
    {
        $this->attributeRepo->update($attribute->attribute_id, $data);
        return $attribute->fresh();
    }

    /**
     * Delete an attribute.
     *
     * @param Attribute $attribute
     * @return void
     */
    public function deleteAttribute(Attribute $attribute): void
    {
        $this->attributeRepo->delete($attribute->attribute_id);
    }
}
