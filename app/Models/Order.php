<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Order extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'customer_id',
        'customer_name',
        'phone_number',
        'secondary_phone_number',
        'delivery_address',
        'district',
        'nearest_main_city',
        'main_city',
        'postal_code',
        'source',
        'payment_method',
        'status',
        'custom_status_id',
        'total_amount',
        'notes',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'custom_status_id');
    }
}
