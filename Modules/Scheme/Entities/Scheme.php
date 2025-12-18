<?php

namespace Modules\Scheme\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Scheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'scheme_name',
        'scheme_amount',
        'scheme_type',
        'enable_slab',
        'multi_level',
        'slab_calculation_type',
        'supplier_id',
        'product_id',
        'starts_at',
        'ends_at',
        'scheme_note',
        'total_eligible_amount',
        'created_by'
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'scheme_amount' => 'decimal:4',
        'total_eligible_amount' => 'decimal:4',
        'enable_slab' => 'boolean',
        'multi_level' => 'boolean'
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Contact::class, 'supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function variations()
    {
        return $this->belongsToMany(\App\Variation::class, 'scheme_variations', 'scheme_id', 'variation_id');
    }

    public function slabs()
    {
        return $this->hasMany(\Modules\Scheme\Entities\SchemeSlab::class);
    }

    protected static function newFactory()
    {
        return \Modules\Scheme\Database\factories\SchemeFactory::new();
    }
}
