<?php

namespace Modules\BusinessIntelligence\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;

class BiInsight extends Model
{
    protected $table = 'bi_insights';

    protected $fillable = [
        'business_id',
        'insight_type',
        'category',
        'title',
        'description',
        'data',
        'confidence_score',
        'priority',
        'status',
        'action_items',
        'icon',
        'color',
        'insight_date',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgement_note',
    ];

    protected $casts = [
        'data' => 'array',
        'action_items' => 'array',
        'confidence_score' => 'decimal:2',
        'insight_date' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    protected $dates = [
        'insight_date',
        'acknowledged_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Relationship with user who acknowledged
     */
    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope for active insights
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by type
     */
    public function scopeType($query, $type)
    {
        return $query->where('insight_type', $type);
    }

    /**
     * Scope by priority
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for recent insights
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('insight_date', '>=', now()->subDays($days));
    }

    /**
     * Mark insight as acknowledged
     */
    public function acknowledge($userId, $note = null)
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
            'acknowledgement_note' => $note,
        ]);
    }

    /**
     * Check if insight is critical
     */
    public function isCritical()
    {
        return $this->priority === 'critical';
    }

    /**
     * Get formatted confidence score
     */
    public function getFormattedConfidenceAttribute()
    {
        return number_format($this->confidence_score, 0) . '%';
    }
}

