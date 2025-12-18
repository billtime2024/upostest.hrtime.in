<?php

namespace Modules\BusinessIntelligence\Entities;

use Illuminate\Database\Eloquent\Model;

class BiMetricsCache extends Model
{
    protected $table = 'bi_metrics_cache';

    protected $fillable = [
        'business_id',
        'metric_key',
        'period_type',
        'period_date',
        'metric_value',
        'metadata',
        'calculated_at',
        'expires_at',
        'is_stale',
    ];

    protected $casts = [
        'metric_value' => 'array',
        'metadata' => 'array',
        'period_date' => 'date',
        'calculated_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_stale' => 'boolean',
    ];

    protected $dates = [
        'period_date',
        'calculated_at',
        'expires_at',
    ];

    /**
     * Scope for non-expired metrics
     */
    public function scopeValid($query)
    {
        return $query->where('is_stale', false)
                     ->where(function($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope by metric key
     */
    public function scopeMetric($query, $key)
    {
        return $query->where('metric_key', $key);
    }

    /**
     * Scope by period type
     */
    public function scopePeriod($query, $type)
    {
        return $query->where('period_type', $type);
    }

    /**
     * Check if metric is expired
     */
    public function isExpired()
    {
        return $this->is_stale || ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Mark as stale
     */
    public function markStale()
    {
        $this->update(['is_stale' => true]);
    }
}

