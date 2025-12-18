<?php

namespace Modules\Scheme\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Scheme\Entities\Relations\JsonBelongsToMany;

class SchemeSlab extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_id',
        'from_amount',
        'to_amount',
        'commission_type',
        'value',
        'variation_ids'
    ];

    protected $casts = [
        'from_amount' => 'decimal:4',
        'to_amount' => 'decimal:4',
        'value' => 'decimal:4',
        'variation_ids' => 'array'
    ];

    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }

    public function variations()
    {
        return new JsonBelongsToMany($this, $this->variation_ids);
    }

    protected static function newFactory()
    {
        return \Modules\Scheme\Database\factories\SchemeSlabFactory::new();
    }
}