<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class OrderItem extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_stock_id',
        'quantity',
        'unit_price',
        'total_price',
        'selected_attributes'
    ];

    protected function casts(): array
    {
        return [
            'selected_attributes' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function productStock()
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
