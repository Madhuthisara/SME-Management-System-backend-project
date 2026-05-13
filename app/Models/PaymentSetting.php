<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class PaymentSetting extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'business_id',
        'gateway_name',
        'credentials',
        'is_active',
        'display_order',
        'environment',
    ];

    /**
     * Credentials are automatically AES-256-CBC encrypted/decrypted at rest.
     * The 'encrypted:array' cast uses Laravel's APP_KEY — never stored as plain text.
     */
    protected $casts = [
        'credentials'   => 'encrypted:array',
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the business this gateway setting belongs to.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
