<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ProductStock extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'product_id',
        'batch_id',
        'quantity',
        'reorder_level',
        'notes',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMaterials()
    {
        return $this->hasMany(ProductStockMaterial::class);
    }

    public function attributeOptions()
    {
        return $this->belongsToMany(AttributeOption::class, 'product_stock_attribute_options', 'stock_id', 'option_id');
    }
}
