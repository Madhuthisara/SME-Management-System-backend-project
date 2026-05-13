<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ProductTemplate extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'name',
        'primary_material_id',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function materials()
    {
        return $this->hasMany(ProductTemplateMaterial::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productStocks()
    {
        return $this->hasMany(ProductStock::class);
    }
}
