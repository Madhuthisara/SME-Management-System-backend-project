<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class MaterialStock extends Model
{
    use HasFactory, HasUlids;

    protected $primaryKey = 'stock_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'business_id',
        'material_id',
        'quantity',
        'reorder_level',
        'sku',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id', 'mat_id');
    }

    public function attributeOptions()
    {
        return $this->belongsToMany(AttributeOption::class, 'material_stock_attribute_options', 'stock_id', 'option_id');
    }

    public function productStockMaterials()
    {
        return $this->hasMany(ProductStockMaterial::class, 'material_stock_id', 'stock_id');
    }
}
