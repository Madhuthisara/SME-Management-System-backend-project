<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ProductStockMaterial extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_stock_id',
        'material_id',
        'material_stock_id',
        'quantity_used',
    ];

    public function productStock()
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id', 'mat_id');
    }

    public function materialStock()
    {
        return $this->belongsTo(MaterialStock::class, 'material_stock_id', 'stock_id');
    }
}
