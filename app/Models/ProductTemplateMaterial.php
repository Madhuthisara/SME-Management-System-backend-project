<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ProductTemplateMaterial extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_template_id',
        'material_id',
        'quantity',
    ];

    public function productTemplate()
    {
        return $this->belongsTo(ProductTemplate::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id', 'mat_id');
    }
}
