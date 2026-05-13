<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\Factories\HasFactory;   
use Illuminate\Database\Eloquent\Concerns\HasUlids;


class Category extends Model
{
   use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'name',
    ];

     public function business()
     {
        return $this->belongsTo(Business::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
}
