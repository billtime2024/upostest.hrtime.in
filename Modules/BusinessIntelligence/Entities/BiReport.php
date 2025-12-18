<?php

namespace Modules\BusinessIntelligence\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;

class BiReport extends Model
{
    protected $table = 'bi_reports';

    protected $fillable = [
        'business_id',
        'report_name',
        'report_type',
        'description',
        'report_date_from',
        'report_date_to',
        'filters',
        'report_data',
        'summary_metrics',
        'chart_configs',
        'status',
        'error_message',
        'file_path',
        'generated_by',
        'generated_at',
        'view_count',
        'last_viewed_at',
        'is_scheduled',
        'schedule_frequency',
    ];

    protected $casts = [
        'filters' => 'array',
        'report_data' => 'array',
        'summary_metrics' => 'array',
        'chart_configs' => 'array',
        'report_date_from' => 'date',
        'report_date_to' => 'date',
        'generated_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'view_count' => 'integer',
    ];

    protected $dates = [
        'report_date_from',
        'report_date_to',
        'generated_at',
        'last_viewed_at',
    ];

    /**
     * Relationship with user who generated the report
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scope for completed reports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope by report type
     */
    public function scopeType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope for scheduled reports
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
    }

    /**
     * Check if report is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if report is failed
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Get date range as formatted string
     */
    public function getDateRangeAttribute()
    {
        return $this->report_date_from->format('M d, Y') . ' - ' . $this->report_date_to->format('M d, Y');
    }
}

