<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class PaymentTransaction extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'order_id',
        'gateway_name',
        'gateway_txn_id',
        'amount',
        'currency',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the business that owns this transaction.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the related order (may be null for pre-order flows).
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
