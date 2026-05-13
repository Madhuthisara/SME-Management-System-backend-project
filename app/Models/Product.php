<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Product extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'category_id',
        'product_template_id',
        'name',
        'description',
        'sku',
        'base_price',
        'discount',
        'thumbnail_url',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productTemplate()
    {
        return $this->belongsTo(ProductTemplate::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
