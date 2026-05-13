<?php

namespace App\Services;

use App\Models\AttributeOption;
use App\Repositories\AttributeOptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AttributeOptionService
{
    public function __construct(
        protected AttributeOptionRepositoryInterface $optionRepo
    ) {}

    public function getOptionsByAttributeId(string $attributeId): Collection
    {
        return $this->optionRepo->findBy(['attribute_id' => $attributeId]);
    }

    public function createOption(array $data): AttributeOption
    {
        return $this->optionRepo->create($data);
    }

    public function updateOption(AttributeOption $option, array $data): AttributeOption
    {
        $this->optionRepo->update($option->option_id, $data);
        return $option->fresh();
    }

    public function deleteOption(AttributeOption $option): void
    {
        $this->optionRepo->delete($option->option_id);
    }
}
