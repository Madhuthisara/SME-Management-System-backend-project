<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AttributeOption extends Model
{
    use HasFactory, HasUlids;

    protected $primaryKey = 'option_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'attribute_id',
        'name',
        'code',
        'description',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    public function materialStocks()
    {
        return $this->belongsToMany(MaterialStock::class, 'material_stock_attribute_options', 'option_id', 'stock_id');
    }
}
