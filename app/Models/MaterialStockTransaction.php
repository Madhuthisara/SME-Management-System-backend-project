<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class MaterialStockTransaction extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'stock_id',
        'supplier_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity'   => 'decimal:4',
        'unit_cost'  => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function stock(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MaterialStock::class, 'stock_id', 'stock_id');
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
