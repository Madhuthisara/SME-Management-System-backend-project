<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'base_price' => $this->base_price,
            'discount' => $this->discount,
            'thumbnail_url' => $this->thumbnail_url,
            'category' => $this->category ? ['name' => $this->category->name] : null,
            'created_at' => $this->created_at,
            'images' => $this->whenLoaded('images', function() {
                return $this->images->map(function($img) {
                    return [
                        'id' => $img->id,
                        'image_url' => $img->image_url,
                    ];
                });
            }),
        ];
    }
}
