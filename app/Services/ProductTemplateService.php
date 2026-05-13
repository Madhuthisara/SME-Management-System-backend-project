<?php

namespace App\Services;

use App\Models\ProductTemplate;
use App\Models\ProductTemplateMaterial;
use App\Repositories\ProductTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductTemplateService
{
    public function __construct(
        protected ProductTemplateRepositoryInterface $productTemplateRepo
    ) {}

    public function getAllTemplates(string $businessId): Collection
    {
        return $this->productTemplateRepo->findBy(['business_id' => $businessId], ['*'], ['materials.material']);
    }

    public function createTemplate(array $data): ProductTemplate
    {
        return DB::transaction(function () use ($data) {
            $template = $this->productTemplateRepo->create([
                'business_id' => $data['business_id'],
                'name' => $data['name'],
                'primary_material_id' => $data['primary_material_id'] ?? null,
            ]);

            if (isset($data['materials']) && is_array($data['materials'])) {
                foreach ($data['materials'] as $materialData) {
                    $template->materials()->create([
                        'material_id' => $materialData['material_id'],
                        'quantity' => $materialData['quantity'],
                    ]);
                }
            }

            return $template->load('materials.material');
        });
    }

    public function updateTemplate(ProductTemplate $template, array $data): ProductTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            $this->productTemplateRepo->update($template->id, [
                'name' => $data['name'],
                'primary_material_id' => $data['primary_material_id'] ?? null,
            ]);

            if (isset($data['materials'])) {
                // Remove existing materials and recreate (simple sync approach for BOM)
                $template->materials()->delete();
                
                foreach ($data['materials'] as $materialData) {
                    $template->materials()->create([
                        'material_id' => $materialData['material_id'],
                        'quantity' => $materialData['quantity'],
                    ]);
                }
            }

            return $template->fresh('materials.material');
        });
    }

    public function deleteTemplate(ProductTemplate $template): bool
    {
        return $this->productTemplateRepo->delete($template->id);
    }
}
