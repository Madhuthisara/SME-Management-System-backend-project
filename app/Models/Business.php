<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Business extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_name',
        'business_address',
        'business_email',
        'business_phone',
        'br_number',
        'tax_id',
        'website',
    ];

    /**
     * Get the users for the business.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the attributes for the business.
     */
    public function attributes()
    {
        return $this->hasMany(Attribute::class, 'business_id');
    }

    /**
     * Get the materials for the business.
     */
    public function materials()
    {
        return $this->hasMany(Material::class, 'business_id');
    }

    /**
     * Get the material stocks for the business.
     */
    public function materialStocks()
    {
        return $this->hasMany(MaterialStock::class, 'business_id');
    }
}
