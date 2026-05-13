<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Attribute extends Model
{
    use HasFactory, HasUlids;
    // Specify custom primary key
    protected $primaryKey = 'attribute_id';
    
    // Disable auto-increment for ULIDs
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'business_id',
        'name',
    ];
    // Optional: Define relationship to Business
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function options()
    {
        return $this->hasMany(AttributeOption::class, 'attribute_id');
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'material_attribute', 'attribute_id', 'material_id');
    }
}