<?php

namespace Modules\BusinessIntelligence\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;

class BiAlert extends Model
{
    protected $table = 'bi_alerts';

    protected $fillable = [
        'business_id',
        'alert_type',
        'title',
        'message',
        'severity',
        'related_data',
        'action_url',
        'action_label',
        'status',
        'triggered_at',
        'resolved_at',
        'resolved_by',
        'resolution_note',
        'notification_sent',
        'notification_sent_at',
        'notified_users',
    ];

    protected $casts = [
        'related_data' => 'array',
        'notified_users' => 'array',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
    ];

    protected $dates = [
        'triggered_at',
        'resolved_at',
        'notification_sent_at',
    ];

    /**
     * Relationship with user who resolved
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope for active alerts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by alert type
     */
    public function scopeType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope by severity
     */
    public function scopeSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for critical alerts
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope for unresolved alerts
     */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['active', 'acknowledged']);
    }

    /**
     * Mark alert as acknowledged
     */
    public function acknowledge()
    {
        $this->update(['status' => 'acknowledged']);
    }

    /**
     * Resolve the alert
     */
    public function resolve($userId, $note = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'resolution_note' => $note,
        ]);
    }

    /**
     * Dismiss the alert
     */
    public function dismiss()
    {
        $this->update(['status' => 'dismissed']);
    }

    /**
     * Mark notification as sent
     */
    public function markNotificationSent(array $userIds = [])
    {
        $this->update([
            'notification_sent' => true,
            'notification_sent_at' => now(),
            'notified_users' => $userIds,
        ]);
    }

    /**
     * Check if alert is critical
     */
    public function isCritical()
    {
        return $this->severity === 'critical';
    }

    /**
     * Get severity badge class
     */
    public function getSeverityBadgeClassAttribute()
    {
        return match($this->severity) {
            'critical' => 'badge-danger',
            'danger' => 'badge-danger',
            'warning' => 'badge-warning',
            'info' => 'badge-info',
            default => 'badge-secondary',
        };
    }
}

