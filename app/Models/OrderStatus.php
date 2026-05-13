<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class OrderStatus extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'name',
        'color',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'custom_status_id');
    }
}
