<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Supplier extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'address'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
