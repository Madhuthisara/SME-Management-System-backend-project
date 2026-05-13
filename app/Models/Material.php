<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Material extends Model
{
    use HasFactory, HasUlids;

    protected $primaryKey = 'mat_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'business_id',
        'name',
        'description',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'material_attribute', 'material_id', 'attribute_id');
    }

    public function stocks()
    {
        return $this->hasMany(MaterialStock::class, 'material_id', 'mat_id');
    }

    public function templateMaterials()
    {
        return $this->hasMany(ProductTemplateMaterial::class, 'material_id', 'mat_id');
    }

    public function productStockMaterials()
    {
        return $this->hasMany(ProductStockMaterial::class, 'material_id', 'mat_id');
    }
}
